#!/bin/sh
# @package      Enterprise
# @subpackage   Build
# @since        9.5
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# Test script for Enterprise Server 9.5 (or later). Designed to run on the WWSW101 build machine running MacOSX.
# It extracts the Enterprise Server build (from the upstream project in Jenkins) and deploys it on the locally 
# installed web server. Once installed, it runs Health Check and Build Test to validate Enterprise functionality.
# 

DOCROOT=/Library/WebServer/Documents
DRUPAL_DIR=drupal7
PLUGINS="Twitter Facebook CopyrightValidationDemo SimpleFileSystem CustomObjectPropsDemo CustomAdminPropsDemo MultiChannelPublishingSample AddSubApplication StandaloneAutocompleteSample PublishingTest AutoTargetingTest AutoNamingTest Adobe_AEM"
SED_BIN=/opt/local/bin/gsed

#
# Compares two versions in x.y.z notation (whereby y and z are optional).
#
# @param $1 string Version to compare (LHS)
# @param $2 string Version to compare (RHS)
# @return $? 0=lesser, 1=equal, 2=greater
#
function vercomp {
    if [[ $1 == $2 ]]
    then
        return 1
    fi
    local IFS=.
    local i ver1=($1) ver2=($2)
    # fill empty fields in ver1 with zeros
    for ((i=${#ver1[@]}; i<${#ver2[@]}; i++))
    do
        ver1[i]=0
    done
    for ((i=0; i<${#ver1[@]}; i++))
    do
        if [[ -z ${ver2[i]} ]]
        then
            # fill empty fields in ver2 with zeros
            ver2[i]=1
        fi
        if ((10#${ver1[i]} > 10#${ver2[i]}))
        then
            return 2
        fi
        if ((10#${ver1[i]} < 10#${ver2[i]}))
        then
            return 0
        fi
    done
    return 1
}

#
# Logs a given param name and value and exits with error when param value is empty.
#
# @param string $1 Parameter name
# @param string $2 Parameter value
#
function validateEnvironmentVariableNotEmpty {
	echo "${1}: [${2}]"
	if [ ! -n "${2}" ]; then
		echo "ERROR: Environment variable ${1} has no value!"
		echo "Tip: Check if the BUILD project in Jenkins has this variable defined and filled in."
		echo "Tip: Also note that you should start the BUILD project in Jenkins (NOT the TEST project)."
		exit 1
	fi
}

#
# Validates environment variables that are required by this script.
#
function step0_validateEnvironment {
	set +x
	echo "step0a: Validating required environment variables..."
	validateEnvironmentVariableNotEmpty GIT_BRANCH "${GIT_BRANCH}"
	validateEnvironmentVariableNotEmpty SERVER_VERSION "${SERVER_VERSION}"

	if [[ "${GIT_BRANCH}" != "master" && "${GIT_BRANCH}" != work* && "${GIT_BRANCH}" != release/*  ]]; then
		echo "ERROR: Environment variable GIT_BRANCH has unsupported value: ${GIT_BRANCH}"
		exit 1
	fi

	echo "step0b: Validating required tools..."
	if [ ! -f "${SED_BIN}" ]; then
		echo "Could not find SED executable: ${SED_BIN}"
		exit 1
	fi
	set -x
}

#
# Determines the HTTP post and PHP version to use for testing. After calling, HTTP_PORT and PHP_BIN are set.
#
# When SERVER_VERSION >= 10.2.0 it uses PHP 7.0
# When SERVER_VERSION >=  9.7.0 it uses PHP 5.6
# When SERVER_VERSION >=  9.5.0 it uses PHP 5.5
# When SERVER_VERSION >=  9.0.0 it uses PHP 5.4 else 5.3
#
function step1_determineHttpPortAndPhpVersion {
	echo "step1a: Determining the PHP_BIN (binary path) and HTTP_PORT..."
	vercomp "${SERVER_VERSION}" "10.2.0"
    compareWith102=$? # catch result of vercomp
	if (( ${compareWith102} >= 1 )) # >= 10.2.0 ?
    then
    	HTTP_PORT=8070
    	PHP_BIN=/opt/local/bin/php70
    else
		vercomp "${SERVER_VERSION}" "9.7.0"
		compareWith97=$? # catch result of vercomp
		if (( ${compareWith97} >= 1 )) # >= 9.7.0 ?
		then
			HTTP_PORT=8056
			PHP_BIN=/opt/local/bin/php56
		else
			vercomp "${SERVER_VERSION}" "9.5.0"
			compareWith95=$? # catch result of vercomp
			if (( ${compareWith95} >= 1 )) # >= 9.5.0 ?
			then
				HTTP_PORT=8055
				PHP_BIN=/opt/local/bin/php55
			else
				vercomp "${SERVER_VERSION}" "9.0.0"
				compareWith90=$? # catch result of vercomp
				if (( ${compareWith90} >= 1 )) # >= 9.0.0 ?
				then
					HTTP_PORT=8054
					PHP_BIN=/opt/local/bin/php54
				else
					HTTP_PORT=8053
					PHP_BIN=/opt/local/bin/php53
				fi
			fi
		fi
	fi
	echo "HTTP_PORT: [${HTTP_PORT}]"
	echo "PHP_BIN: [${PHP_BIN}]"
	if [ ! -f "${PHP_BIN}" ]; then
		echo "Could not find PHP executable: ${PHP_BIN}"
		exit 1
	fi

	echo "step1a: Resolving the PHP version..."
	phpVersion=`${PHP_BIN} -r "echo phpversion();"`
	echo "Using PHP version ${phpVersion}"
}

#
# Determines the Enterprise Server web directory. After calling ENT_DIR is set.
#
function step2_determineEnterpriseDir {
	echo "step2a: Deriving the Enterprise Server web directory (ENT_DIR) from the Git branch (GIT_BRANCH)."
	if [[ "${GIT_BRANCH}" == "master" ]]; then
		ENT_DIR="EntMaster"
	elif [[ "${GIT_BRANCH}" == work* ]]; then
		masterWorkNr=`echo "${GIT_BRANCH}" | ${SED_BIN} -r "s/work(([[:digit:]]+))?/\2/g"`
		ENT_DIR="EntMasterWork${masterWorkNr}"
	elif [[ "${GIT_BRANCH}" == release/* ]]; then
		releaseNr=`echo "${GIT_BRANCH}" | cut -d "/" -f2- | tr -d '.'`
		ENT_DIR="EntRelease${releaseNr}"
	else
		echo "Could not interpret the GIT_BRANCH value: ${GIT_BRANCH}"
		ENT_DIR=""
		exit 1
	fi

	if [ ! -d "${DOCROOT}/${ENT_DIR}" ]; then
		echo "step2b: Enterprise Server web directory does not exist: ${DOCROOT}/${ENT_DIR}"
		exit 1
	fi
	echo "step2b: Determined ENT_DIR: [${ENT_DIR}]"
}

#
# Extracts the Enterprise Server artifacts, Jenkins has copied from the build machine.
#
function step3_extractArtifacts() {
	# The artifacts are automatically copied from the build machine to the test machine by Jenkins.
	# So here we simply extract the Enterprise artifacts. (Note that those are ionCube encoded.)
	cd "${WORKSPACE}/artifacts"
	unzip EnterpriseServer*.zip 1>/dev/null
	unzip BuildTest1*.zip 1>/dev/null
	unzip largeSpeedTestData*.zip 1>/dev/null
	unzip Drupal7Enterprise*.zip 1>/dev/null
	for plugin in ${PLUGINS}; do
		unzip ${plugin}*.zip 1>/dev/null
	done
	cd -
}

#
# Copies Enterprise and the BuildTest to the web server for testing.
#
function step4_deployArtifactsToWebServer {
	rsync -av --exclude "config/config_overrule.php" --delete "${WORKSPACE}/artifacts/Enterprise/" "${DOCROOT}/${ENT_DIR}/" 1>/dev/null
	rsync -av --delete "${WORKSPACE}/artifacts/BuildTest/" "${DOCROOT}/${ENT_DIR}/server/wwtest/testsuite/BuildTest/" 1>/dev/null
	rsync -av --delete "${WORKSPACE}/artifacts/largeSpeedTestData/" "${DOCROOT}/${ENT_DIR}/server/wwtest/testsuite/largeSpeedTestData/" 1>/dev/null
	rsync -av --delete "${WORKSPACE}/artifacts/Enterprise/config/configlang.php" "${DOCROOT}/${ENT_DIR}/config/configlang.php" 1>/dev/null
	rsync -av --delete "${WORKSPACE}/artifacts/ww_enterprise/" "${DOCROOT}/${DRUPAL_DIR}/sites/all/modules/ww_enterprise/" 1>/dev/null
	for plugin in ${PLUGINS}; do
		# We need to map the Adobe_AEM plugin name to its internal name in order to be able to find it in the workspace.
		if [ ${plugin} == "Adobe_AEM" ]; then
			plugin="AdobeDps2" 
		fi
		rsync -av --delete "${WORKSPACE}/artifacts/${plugin}" "${DOCROOT}/${ENT_DIR}/config/plugins/" 1>/dev/null
	done
}

#
# Runs some admin pages of Enterprise Server to complete the setup.
#
function step5_initializeEnterpriseServer {
	# Clear WSDL cache
	cd "${DOCROOT}/${ENT_DIR}/"
	${PHP_BIN} server/wwtest/clearWsdlCacheCli.php
	cd -

	# Refresh the Server Plug-ins and Server Jobs Config admin pages to install all plug-ins and jobs.
	# TODO: Auto pre-select a system user for new server job types.
	cookiejar=/tmp/cookies.txt
	curl -d "usr=woodwing&psswd=ww&login=login" -c "${cookiejar}" http://127.0.0.1:${HTTP_PORT}/${ENT_DIR}/server/apps/login.php > /dev/null
	curl -b "${cookiejar}" http://127.0.0.1:${HTTP_PORT}/${ENT_DIR}/server/admin/serverplugins.php > /dev/null
	curl -b "${cookiejar}" http://127.0.0.1:${HTTP_PORT}/${ENT_DIR}/server/admin/serverjobconfigs.php > /dev/null
	curl -b "${cookiejar}" http://127.0.0.1:${HTTP_PORT}/${ENT_DIR}/server/apps/login.php?logout=true > /dev/null
	rm -f "${cookiejar}"
}

#
# Runs the Health Check and the Build Test.
#
function step6_testEnterpriseServer {
	echo "step6a: Deleting any reports of previous builds..."
	rm -rf "${WORKSPACE}/reports"
	mkdir "${WORKSPACE}/reports"
	
	cd "${DOCROOT}/${ENT_DIR}/"
	echo "step6b: Running the Health Check..."
	${PHP_BIN} server/wwtest/testsuite/junitcliclient.php HealthCheck2 "${WORKSPACE}/reports"
	echo "step6c: Running the Build Test..."
	${PHP_BIN} server/wwtest/testsuite/junitcliclient.php BuildTest "${WORKSPACE}/reports"
	cd -
}

set +x; echo "================ Step 0 ================"; set -x
step0_validateEnvironment
set +x; echo "================ Step 1 ================"; set -x
step1_determineHttpPortAndPhpVersion
set +x; echo "================ Step 2 ================"; set -x
step2_determineEnterpriseDir
set +x; echo "================ Step 3 ================"; set -x
step3_extractArtifacts
set +x; echo "================ Step 4 ================"; set -x
step4_deployArtifactsToWebServer
set +x; echo "================ Step 5 ================"; set -x
step5_initializeEnterpriseServer
set +x; echo "================ Step 6 ================"; set -x
step6_testEnterpriseServer
