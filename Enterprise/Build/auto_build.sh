#!/bin/sh
# @package      Enterprise
# @subpackage   Build
# @since        9.5
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# Build script for Enterprise Server 9.5 (or later). Designed to run on the Zetes build machine running CentOS7.
#
# It retrieves source code files from Perforce and updates version info in the core, plugins and 3rd party modules.
# Some source code files are encoded with ionCube. The server, plugins and 3rd party modules are archived at last.
#

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
		exit 1
	fi
}

#
# Replaces a version in a given file. It searches for a given regular expression prefix followed by "<x.y.z> Build <nr>".
#
# @param string $1 Full local file path of PHP file to update version in.
# @param string $2 Regular expression of some text just before the old version.
# @param string $3 New version number (x.y.z) to replace the old one with.
# @param integer $4 New build number to replace the old one with.
#
function updateVersion {
	p4 edit "${1}"
	mv "${1}" "${1}.old"
	sed -r "s/(${2})([0-9]+\.[0-9]+(\.[0-9]+)?)\s[Bb]uild\s([0-9]+)/\1${3} Build ${4}/g" "${1}.old" > "${1}"
	rm -f "${1}.old"

	# Error when the new version can not be found in the updated file.
	set +e
	testVersion=`grep "${3} Build ${4}" "${1}"`
	if [ ! -n "${testVersion}" ]; then
		echo "ERROR: Could not update version in ${1}"
		exit 1
	fi
	set -e
}

#
# Replaces the version info for all plugins that reside in a given parent folder.
#
# @param string $1 Root path of plugin folders.
# @param string $2 New version number (x.y.z) to replace the old one with.
# @param integer $3 New build number to replace the old one with.
#
function updatePluginVersions {
	# Get the full path of the plugin info files.
	# Note that the pwd command (within the find command) is a trick to get FULL paths.
	# To let the for loop handle file paths containing spaces, the IFS setting is temporary changed.
	orgIFS=$IFS
	IFS=$(echo -en "\n\b")
	cd "${1}"
	pluginPath=`pwd`
	pluginFiles=`find "${pluginPath}" -name PluginInfo.php`
	for pluginFile in ${pluginFiles}; do
		updateVersion "${pluginFile}" ".*->Version\s*=\s*'" ${2} ${3}
	done
	cd -
	IFS=${orgIFS}
}

#
# Updates a value in a define() statement in a given PHP file.
#
# @param string $1 Full local file path of PHP file to update define in.
# @param string $2 Name of the define to update.
# @param string $3 Value to be filled into the define.
#
function updatePhpDefine {
	p4 edit "${1}"
	mv "${1}" "${1}.old"
	sed -r "s/(^define\s*\(\s*'${2}'\s*,\s*')([^']*)/\1${3}/g" "${1}.old" > "${1}"
	rm -f "${1}.old"

	# Error when the new version can not be found in the updated file.
	set +e
	testValue=`grep "'${3}'" "${1}"`
	if [ ! -n "${testValue}" ]; then
		echo "ERROR: Could not update ${2} in ${1}"
		exit 1
	fi
	set -e
}

#
# Compresses a folder into a ZIP file and moves it to a given location.
#
# @param string $1 Full path of parent folder of source subfolder to compress.
# @param string $2 Name of source subfolder to compress.
# @param string $3 Full path of destination folder to move the ZIP file to.
# @param string $4 File name of the ZIP file.
#
function zipFolder {
	cd "${1}"
	chmod -R +w "${2}"
	7za a "${4}" "${2}" 1>/dev/null
	mv "${4}" "${3}"
	cd -
}

#
# Derives the postfix to use for naming artifact files. After call, SERVER_VERSION_ZIP is set.
# It is derived from SERVER_VERSION, SERVER_RELEASE_TYPE and P4_BRANCH. Some examples:
#    -----------------------------------
#    SERVER_VERSION: [10.0.0]
#    SERVER_RELEASE_TYPE: [Pre-release 1]
#    P4_BRANCH: [SmartConnection/Server.main]
#    SERVER_VERSION_ZIP: [v10.0.0_Prerelease1_Build539.zip]
#    -----------------------------------
#    SERVER_VERSION: [9.5.0]
#    SERVER_RELEASE_TYPE: [Daily]
#    P4_BRANCH: [SmartConnection/Server.v9.x.x.work2]
#    SERVER_VERSION_ZIP: [v9.5_Work2_Daily_Build539.zip]
#    -----------------------------------
#    SERVER_VERSION: [9.4.1]
#    SERVER_RELEASE_TYPE: [Release]
#    P4_BRANCH: [SmartConnection.archive/Server.v9.4.1]
#    SERVER_VERSION_ZIP: [v9.4.1_Build539.zip]
#    -----------------------------------
#
function determineZipPostfix {
	if test "${SERVER_RELEASE_TYPE}" = "Daily"
	then
		serverVersion=`echo "${SERVER_VERSION}" | sed -r "s/([0-9]+\.[0-9]+)\.[0-9]+/\1/g"`
	else
		serverVersion="${SERVER_VERSION}"
	fi
	if [[ "${P4_BRANCH}" == SmartConnection.archive/* ]] ;
	then
		SERVER_VERSION_ZIP="v${SERVER_VERSION}"
	else
		if test "${P4_BRANCH}" = "SmartConnection/Server.main"
		then
			SERVER_VERSION_ZIP="v${serverVersion}"
		else
			if test "${P4_BRANCH}" = "SmartConnection/Server.main.work"
			then
				SERVER_VERSION_ZIP="v${serverVersion}_Work"
			else
				workPostfix=`echo "${P4_BRANCH}" | sed -r "s/SmartConnection\/Server\.v[[:digit:]]+\.x\.x(\.work)?(([[:digit:]]+))?/\1\2/g"`
				if test "${workPostfix}" = ""
				then
					SERVER_VERSION_ZIP="v${serverVersion}"
				else
					if test "${workPostfix}" = ".work"
					then
						SERVER_VERSION_ZIP="v${serverVersion}_Work"
					else
						if [[ "${workPostfix}" = .work* ]]
						then
							workDigit=`echo "${workPostfix}" | sed -r "s/\.work([[:digit:]]+)/\1/g"`
							SERVER_VERSION_ZIP="v${serverVersion}_Work${workDigit}"
						else
							SERVER_VERSION_ZIP=""
							echo "Error detected: [${workPostfix}]"
							exit 1
						fi
					fi
				fi
			fi
		fi
	fi
	releaseType=`echo "${SERVER_RELEASE_TYPE}" | sed -r "s/[ -]//g"`
	if test "${releaseType}" != "Release"
	then
		SERVER_VERSION_ZIP="${SERVER_VERSION_ZIP}_${releaseType}"
	fi
	SERVER_VERSION_ZIP="${SERVER_VERSION_ZIP}_Build${BUILD_NUMBER}.zip"
}

#
# Validates environment variables that are required by this script.
#
function step0_validateEnvironment {
	set +x
	builder=$(whoami)
	echo "Process is executed by: ${builder}"
	if [ ${builder} != "autobuild" ];
	then
    	echo "Jenkins slave process on Zetes is not created by the 'autobuild' user. Stop the process ('ps -ef|grep 'slave.jar'') on Zetes and restart it from 'bob/Jenkins/Manage Jenkins'. "
    	exit 1
	fi
	echo "step0a: Validating required environment variables..."
	validateEnvironmentVariableNotEmpty ANALYTICS_BUILDNR "${ANALYTICS_BUILDNR}"
	validateEnvironmentVariableNotEmpty ADOBEDPS2_BUILDNR "${ADOBEDPS2_BUILDNR}"
	validateEnvironmentVariableNotEmpty ELVIS_BUILDNR "${ELVIS_BUILDNR}"
	validateEnvironmentVariableNotEmpty BUILD_NUMBER "${BUILD_NUMBER}"
	validateEnvironmentVariableNotEmpty PROXYFORSC_VERSION "${PROXYFORSC_VERSION}"
	validateEnvironmentVariableNotEmpty PROXYFORSC_BUILDNR "${PROXYFORSC_BUILDNR}"
	validateEnvironmentVariableNotEmpty P4CLIENT "${P4CLIENT}"
	validateEnvironmentVariableNotEmpty P4_BRANCH "${P4_BRANCH}"
	validateEnvironmentVariableNotEmpty SERVER_VERSION "${SERVER_VERSION}"
	validateEnvironmentVariableNotEmpty SERVER_RELEASE_TYPE "${SERVER_RELEASE_TYPE}"
	validateEnvironmentVariableNotEmpty WORKSPACE_SERVER "${WORKSPACE_SERVER}"
	
	echo "step0b: Determining the postfix to use for artifacts (SERVER_VERSION_ZIP)..."
	determineZipPostfix
	echo "${SERVER_VERSION_ZIP}: [${SERVER_VERSION_ZIP}]" 
	set -x
}

#
# Retrieves latest version from Perforce and cleans release- and artifact folders locally.
#
function step1_cleanGetWorkspace {
	# When a previous batch has checked out files, but the batch got badly interrupted, those files are still opened for edit.
	# Even a forget get from Perforce does not retrieve those kind of files. Therefore we revert changes and get again.

	echo "step1a: Checking for files in depot that are opened for editing:"
	depotFiles=`p4 opened -C ${P4CLIENT} | sed -r 's/(.*)#(.*)/\1/g'`

	echo "step1b: Retrieve files from depot that are opened for editing, but are missing in the current workspace: ${P4CLIENT}"
	for depotFile in $depotFiles
	do
		workspaceFile=`php -r "print str_replace('//ww5/${P4_BRANCH}', '${WORKSPACE_SERVER}', '${depotFile}');"`
		if [ ! -f "${workspaceFile}" ]; then
			p4 revert ${depotFile}
			p4 sync -f ${depotFile}
		fi
	done
	
	echo "step1c: Revert files that are opened for editing due to unfinished (aborted) previous build process:"
	p4 revert -c default ...

	echo "step1d: Delete local build folders (forced, recursively)."
	rm -rf ./Enterprise_release
	mkdir ./Enterprise_release
	rm -rf ./tms_resources
	mkdir ./tms_resources
	rm -rf ./reports
	mkdir ./reports
	mkdir ./reports/phpstorm_strict
	rm -rf "${WORKSPACE}/artifacts"
	mkdir "${WORKSPACE}/artifacts"
}

#
# When TMS has newer resource files, they are downloaded to update workspace and Perforce depot.
#
function step2_updateResourceFiles {
	echo "step2a: Retrieve timestamp of last update from TMS for core Enterprise Server."
	lastUpdate=`curl http://tms.woodwing.net/product/lastupdateversion/productname/Enterprise%20server/version/10.0/`
	if [ ! -n "${lastUpdate}"  ]; then
		echo 'Could not retrieve last modification timestamp from TMS (for the core Enterprise Server project). Is TMS down?';
		exit 1;
	fi

	echo "step2b: Retrieve timestamp of last update from local resource file for core Enterprise Server."
	currentUpdate=`grep "Last edit date in TMS" ./Enterprise/Enterprise/config/resources/enUS.xml | sed -r "s/<\!--Last edit date in TMS: (.*)-->/\1/g"`
	if [ "${lastUpdate}" == "${currentUpdate}" ]; then
		echo "step2b1: Perforce and TMS are in sync. No update needed."
	else
		echo "step2b2: Perforce is out-of-sync with TMS. Downloading resources..."
		wget "http://tms.woodwing.net/product/getexport/user/woodwing/pass/QjQjI2VyVmxAQDE=/versionid/116" -O ./tms_resources/core.zip
		# L> update the versionid param when migrating to new Enterprise major version: 10=7.0, 22=8.0, 73=9.0, 116=10.0

		echo "step2b3: At Perforce depot, open resource files for editing."
		p4 edit ./Enterprise/Enterprise/config/resources/...

		echo "step2b4: Extract resource archive and overwrite local resources."
		cd ./Enterprise/Enterprise/config/resources
		7za e -y "${WORKSPACE_SERVER}/tms_resources/core.zip" "Server/config/resources"
		cd -

		echo "step2b5: Submit latest resource files to Perforce."
		p4 submit -d "[Ent Server ${SERVER_VERSION}] Jenkins: Updated latest (${lastUpdate}) core resource files from TMS for server build ${BUILD_NUMBER}."
	fi

	echo "step2d: Retrieve timestamp of last update from TMS for AdobeDps2 plugin."
	lastUpdate=`curl http://tms.woodwing.net/product/lastupdateversion/productname/Enterprise%20Server%20AdobeDps2/version/10.0/`
	if [ ! -n "${lastUpdate}"  ]; then
		echo 'Could not retrieve last modification timestamp from TMS (for the AdobeDps2 project). Is TMS down?';
		exit 1;
	fi

	echo "step2e: Retrieve timestamp of last update from local resource file for AdobeDps2 plugin."
	currentUpdate=`grep "Last edit date in TMS" ./Enterprise/plugins/release/AdobeDps2/resources/enUS.xml | sed -r "s/<\!--Last edit date in TMS: (.*)-->/\1/g"`
	if [ "${lastUpdate}" == "${currentUpdate}" ]; then
		echo "step2e1: Perforce and TMS are in sync. No update needed."
	else
		echo "step2e2: Perforce is out-of-sync with TMS. Downloading resources..."
		wget "http://tms.woodwing.net/product/getexport/user/woodwing/pass/QjQjI2VyVmxAQDE=/versionid/117" -O ./tms_resources/adobedps2.zip
		# L> update the versionid param when migrating to new AdobeDps2 major version: 99=9.0, 117-10.0

		echo "step2e3: At Perforce depot, open resource files for editing."
		p4 edit ./Enterprise/plugins/release/AdobeDps2/resources/...

		echo "step2e4: Extract resource archive and overwrite local resources."
		cd ./Enterprise/plugins/release/AdobeDps2/resources
		7za e -y "${WORKSPACE_SERVER}/tms_resources/adobedps2.zip" "Server/config/resources"
		cd -
		
		echo "step2e5: Prefix the resource keys with AdobeDps2."
		php "${WORKSPACE_SERVER}/Enterprise/Build/replace_resource_keys.php" "${WORKSPACE_SERVER}/Enterprise/plugins/release/AdobeDps2/resources" AdobeDps2

		echo "step2e6: Submit latest resource files to Perforce."
		p4 submit -d "[Ent Server ${SERVER_VERSION}] Jenkins: Updated latest (${lastUpdate}) AdobeDps2 resource files from TMS for server build ${BUILD_NUMBER}."
	fi
}

#
# Updates version info embedded in PHP modules.
#
function step3_updateVersionInfo {
	echo "step3a: Update version info in server modules."
	updateVersion ./Enterprise/Enterprise/server/serverinfo.php "^define\s*\(\s*'SERVERVERSION'\s*,\s*[\"']" ${SERVER_VERSION} ${BUILD_NUMBER}
	if [ "${SERVER_RELEASE_TYPE}" == "Release" ]; then
		updatePhpDefine ./Enterprise/Enterprise/server/serverinfo.php "SERVERVERSION_EXTRAINFO" ""
	else
		updatePhpDefine ./Enterprise/Enterprise/server/serverinfo.php "SERVERVERSION_EXTRAINFO" ${SERVER_RELEASE_TYPE}
	fi

	echo "step3b: Update version info in server plugins."
	updatePluginVersions ./Enterprise/Enterprise/config/plugins ${SERVER_VERSION} ${BUILD_NUMBER}
	updatePluginVersions ./Enterprise/Enterprise/server/plugins ${SERVER_VERSION} ${BUILD_NUMBER}
	updatePluginVersions ./Enterprise/plugins/release ${SERVER_VERSION} ${BUILD_NUMBER}

	echo "step3c: Update version info in Analytics, AdobeDps2 and Elvis plugins. They have their own buildnr, but use the major.minor of Enterprise."
	twoDigitVersion=`echo "${SERVER_VERSION}" | sed -r "s/([0-9]+\.[0-9]+)(\.[0-9]+)?/\1/g"` # ignores patch nr
	updatePluginVersions ./Enterprise/plugins/release/Analytics "${twoDigitVersion}" ${ANALYTICS_BUILDNR}
	updatePluginVersions ./Enterprise/plugins/release/AdobeDps2 "${twoDigitVersion}" ${ADOBEDPS2_BUILDNR}
	updatePluginVersions ./Enterprise/plugins/release/Elvis "${twoDigitVersion}" ${ELVIS_BUILDNR}

	echo "step3d: Update version info of the ProxyForSC solution."
	updateVersion ./Enterprise/ProxyForSC/proxyserver/serverinfo.php "^define\s*\(\s*'PRODUCT_VERSION'\s*,\s*[\"']" ${PROXYFORSC_VERSION} ${PROXYFORSC_BUILDNR}
	updateVersion ./Enterprise/ProxyForSC/proxystub/serverinfo.php "^define\s*\(\s*'PRODUCT_VERSION'\s*,\s*[\"']" ${PROXYFORSC_VERSION} ${PROXYFORSC_BUILDNR}
	updateVersion ./Enterprise/ProxyForSC/speedtest/serverinfo.php "^define\s*\(\s*'PRODUCT_VERSION'\s*,\s*[\"']" ${PROXYFORSC_VERSION} ${PROXYFORSC_BUILDNR}

	echo "step3e: Update version info in 3rd party modules."
	updateVersion ./Enterprise/Drupal/modules/ww_enterprise/ww_enterprise.info "^version\s*=\s*[\"']" ${SERVER_VERSION} ${BUILD_NUMBER}
	updateVersion ./Enterprise/Drupal7/modules/ww_enterprise/ww_enterprise.info "^version\s*=\s*[\"']" ${SERVER_VERSION} ${BUILD_NUMBER}
	updateVersion ./Enterprise/Drupal8/modules/ww_enterprise/ww_enterprise.info.yml "^version\s*:\s*[\"']" ${SERVER_VERSION} ${BUILD_NUMBER}
	updateVersion ./Enterprise/WordPress/plugins/ww_enterprise/ww_enterprise.php "^\s*\*\s*Version:\s*" ${SERVER_VERSION} ${BUILD_NUMBER}
}

#
# Checks if the PHP sources can be loaded and have correct syntax.
#
function step4_validatePhpCode {
	echo "step4a: Run the PHP Coding Test (testsuite)."
	cd "${WORKSPACE_SERVER}/Enterprise/Enterprise/server/wwtest/"
	php testphpcodingcli.php "${WORKSPACE_SERVER}/reports"
	cd -

	echo "step4b: Run phpStorm's code inspection on the server folder."
	# mkdir ./reports/phpstorm_strict
	# inspect.sh params: <project file path> <inspection profile path> <output path> -d <directory to be inspected>
	# see more info: http://www.jetbrains.com/phpstorm/webhelp/running-inspections-offline.html
	sh /opt/phpstorm/bin/inspect.sh "${WORKSPACE_SERVER}/Enterprise" "${WORKSPACE_SERVER}/Enterprise/.idea/inspectionProfiles/EnterpriseCodeInspection.xml" "${WORKSPACE_SERVER}/reports/phpstorm_strict" -d "${WORKSPACE_SERVER}/Enterprise/Enterprise"

	cd "${WORKSPACE_SERVER}/Enterprise/Build/"
	echo "step4c: Convert folder with XML files (output of phpStorm's code inspection) to one JUnit XML file to display in UI of Jenkins."
	# phpstorm2junit params: <folder path with code inspector output> <output path for jUnit>
	php phpstorm2junit.php "\"${WORKSPACE_SERVER}/reports/phpstorm_strict\"" "\"${WORKSPACE_SERVER}/reports/TEST-PhpStormCodeInspection.xml\""
	cd -
}

#
# Makes a copy of core and plugins into the release folder and encodes some PHP files.
#
function step5_ionCubeEncodePhpFiles {
	echo "step5a: Make local copy of Enterprise Server source (and plugins) to release folder."
	cp -r ./Enterprise/Enterprise ./Enterprise_release/Enterprise
	cp -r ./Enterprise/plugins ./Enterprise_release/plugins

	echo "step5b: Remove some unwanted files and folder from the release folder that should NOT be released."
	rm -f -r ./Enterprise_release/Enterprise/phpunit
	rm -f -r ./Enterprise_release/Enterprise/server/buildtools
	rm -f -r ./Enterprise_release/Enterprise/server/useful
	rm -f -r ./Enterprise_release/Enterprise/server/vendor/solarium/solarium/examples
	rm -f -r ./Enterprise_release/Enterprise/server/vendor/solarium/solarium/tests
	rm -f -r ./Enterprise_release/Enterprise/server/wwtest/development
	rm -f -r ./Enterprise_release/Enterprise/server/wwtest/testsuite/BuildTest/PhpCoding
	rm -f -r ./Enterprise_release/Enterprise/server/wwtest/testsuite/BuildTest2

	echo "step5c: ionCube encode some files and folders in Enterprise Server release folder."
	sudo /usr/local/ioncube/9.0/ioncube_encoder54_9.0_64 --acquire-license
	/usr/local/ioncube/9.0/ioncube_encoder54_9.0_64 -V
	# /usr/local/ioncube/9.0/ioncube_encoder54_9.0_64 --help | more
	thisYear=`date +%Y`
	echo "--replace-target --add-comment \"(c) Copyright 2000-${thisYear} WoodWing Software, www.woodwing.com\" --obfuscate locals --obfuscation-key \"de bocht van de ronde tocht\" --optimize max --no-doc-comments --property \"magic='the windmill keeps on turning'\" --message-if-no-loader \"'No Ioncube loader installed. Please run the Health Check page (e.g. http://localhost/Enterprise/server/wwtest/testsuite.php).'\"" > ./Enterprise/encodeoptions.txt

	ionCubeEncode="/usr/local/ioncube/9.0/ioncube_encoder54_9.0_64 --project-file ./Enterprise/encodeoptions.txt"
	${ionCubeEncode} ./Enterprise/Enterprise/server/admin/license --into ./Enterprise_release/Enterprise/server/admin
	${ionCubeEncode} --include-if-property "magic='the windmill keeps on turning'" ./Enterprise/Enterprise/server/utils/license --into ./Enterprise_release/Enterprise/server/utils
	${ionCubeEncode} ./Enterprise/Enterprise/server/dbclasses --into ./Enterprise_release/Enterprise/server
	${ionCubeEncode} ./Enterprise/Enterprise/server/dbdrivers --into ./Enterprise_release/Enterprise/server
	${ionCubeEncode} ./Enterprise/Enterprise/server/services --into ./Enterprise_release/Enterprise/server
	${ionCubeEncode} ./Enterprise/Enterprise/server/appservices --into ./Enterprise_release/Enterprise/server
	${ionCubeEncode} ./Enterprise/Enterprise/server/wwtest/ngrams --into ./Enterprise_release/Enterprise/server/wwtest
	${ionCubeEncode} ./Enterprise/Enterprise/server/plugins/AdobeDps --into ./Enterprise_release/Enterprise/server/plugins
	${ionCubeEncode} ./Enterprise/Enterprise/server/apps/webapplicense.inc.php -o ./Enterprise_release/Enterprise/server/apps/webapplicense.inc.php
	${ionCubeEncode} ./Enterprise/Enterprise/server/regserver.inc.php -o ./Enterprise_release/Enterprise/server/regserver.inc.php
	${ionCubeEncode} ./Enterprise/Enterprise/server/bizclasses/BizServerJob.class.php -o ./Enterprise_release/Enterprise/server/bizclasses/BizServerJob.class.php
	${ionCubeEncode} ./Enterprise/Enterprise/server/bizclasses/BizPublishing.class.php -o ./Enterprise_release/Enterprise/server/bizclasses/BizPublishing.class.php
	${ionCubeEncode} ./Enterprise/Enterprise/server/utils/DigitalPublishingSuiteClient.class.php -o ./Enterprise_release/Enterprise/server/utils/DigitalPublishingSuiteClient.class.php
	${ionCubeEncode} ./Enterprise/Enterprise/server/wwtest/testsuite/HealthCheck2/Licenses_TestCase.php -o ./Enterprise_release/Enterprise/server/wwtest/testsuite/HealthCheck2/Licenses_TestCase.php
	${ionCubeEncode} ./Enterprise/plugins/release/Analytics --into ./Enterprise_release/plugins/release --exclude monitor_config.php
	${ionCubeEncode} ./Enterprise/plugins/release/Elvis/Elvis_WflLogOn.class.php -o ./Enterprise_release/plugins/release/Elvis/Elvis_WflLogOn.class.php
	${ionCubeEncode} ./Enterprise/plugins/release/Elvis/Elvis_ContentSource.class.php -o ./Enterprise_release/plugins/release/Elvis/Elvis_ContentSource.class.php

	echo "step5d: Validate some of the ionCube Encoded files."
	icFiles="./Enterprise_release/Enterprise/server/dbclasses/DBBase.class.php ./Enterprise_release/Enterprise/server/apps/webapplicense.inc.php ./Enterprise_release/Enterprise/server/utils/license/license.class.php"
	for icFile in ${icFiles}; do
		checkSum=`grep 'ionCube Loader' ${icFile}`
		if [ ! -n "${checkSum}"  ]; then
		   echo '${icFile} is not ionCube Encoded! Tip: Maybe needed to acquire license for ionCube Encoder?';
		   exit 1
		fi	
	done
}

#
# Archives the Enterprise Server in a ZIP file. The BuildTest and large sample data are put in separate ZIP files.
#
function step6_zipEnterpriseServer {
	echo "step6a: Zipping BuildTest ..."
	zipFolder "${WORKSPACE_SERVER}/Enterprise_release/Enterprise/server/wwtest/testsuite" "BuildTest" "${WORKSPACE}/artifacts" "BuildTest1_${SERVER_VERSION_ZIP}"

	echo "step6b: Zipping large sample data..."
	zipFolder "${WORKSPACE_SERVER}/Enterprise_release/Enterprise/server/wwtest/testdata" "largeSpeedTestData" "${WORKSPACE}/artifacts" "largeSpeedTestData_${SERVER_VERSION_ZIP}"

	echo "step6c: Excluding (removing) testsuite stuff that not needed for production..."
	rm -rf "${WORKSPACE_SERVER}/Enterprise_release/Enterprise/server/wwtest/testsuite/BuildTest"
	rm -rf "${WORKSPACE_SERVER}/Enterprise_release/Enterprise/server/wwtest/testsuite/BuildTest2"
	rm -rf "${WORKSPACE_SERVER}/Enterprise_release/Enterprise/server/wwtest/testsuite/PhpCodingTest"
	rm -rf "${WORKSPACE_SERVER}/Enterprise_release/Enterprise/server/wwtest/testdata/largeSpeedTestData"

	echo "step6d: Zipping Enterprise Server ..."
	zipFolder "${WORKSPACE_SERVER}/Enterprise_release" "Enterprise" "${WORKSPACE}/artifacts" "EnterpriseServer_${SERVER_VERSION_ZIP}"
}

#
# Archives PHP modules for 3rd party integrations.
#
function step7_zipExternalModules {
	echo "step7a: Zipping 3rd party modules module ..."
	zipFolder "${WORKSPACE_SERVER}/Enterprise/Drupal/modules" "ww_enterprise" "${WORKSPACE}/artifacts" "DrupalEnterprise_${SERVER_VERSION_ZIP}"
	zipFolder "${WORKSPACE_SERVER}/Enterprise/Drupal7/modules" "ww_enterprise" "${WORKSPACE}/artifacts" "Drupal7Enterprise_${SERVER_VERSION_ZIP}"
	zipFolder "${WORKSPACE_SERVER}/Enterprise/Drupal8/modules" "ww_enterprise" "${WORKSPACE}/artifacts" "Drupal8_Drupal_Module_${SERVER_VERSION_ZIP}"
	zipFolder "${WORKSPACE_SERVER}/Enterprise/WordPress/plugins" "ww_enterprise" "${WORKSPACE}/artifacts" "WordPress_Plugin_${SERVER_VERSION_ZIP}"
	zipFolder "${WORKSPACE_SERVER}/Enterprise" "Solr" "${WORKSPACE}/artifacts" "SolrEnterprise_${SERVER_VERSION_ZIP}"

	echo "step7b: Zipping release plug-ins ..."
	twoDigitVersion=`echo "${SERVER_VERSION}" | sed -r "s/([0-9]+\.[0-9]+)(\.[0-9]+)?/\1/g"` # ignores patch nr
	zipFolder "${WORKSPACE_SERVER}/Enterprise_release/plugins/release" "Analytics" "${WORKSPACE}/artifacts" "Enterprise_Analytics_Build_${ANALYTICS_BUILDNR}_for_Enterprise_${twoDigitVersion}.zip"
	zipFolder "${WORKSPACE_SERVER}/Enterprise_release/plugins/release" "AdobeDps2" "${WORKSPACE}/artifacts" "AdobeDPS_Build_${ADOBEDPS2_BUILDNR}_for_Enterprise_${twoDigitVersion}.zip"
	zipFolder "${WORKSPACE_SERVER}/Enterprise_release/plugins/release" "Elvis" "${WORKSPACE}/artifacts" "Elvis_Build_${ELVIS_BUILDNR}_for_Enterprise_${twoDigitVersion}.zip"
	plugins="Facebook Twitter WordPress Drupal8"
	for plugin in ${plugins}; do
		# For Drupal 8 we want to modify the name to indicate this is the plugin (and not the module)
		if [ "${plugin}" == "Drupal8" ]; then
			zipFolder "${WORKSPACE_SERVER}/Enterprise_release/plugins/release" "${plugin}" "${WORKSPACE}/artifacts" "Drupal8_Enterprise_Plugin_${SERVER_VERSION_ZIP}"
		else
			zipFolder "${WORKSPACE_SERVER}/Enterprise_release/plugins/release" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"	
		fi
	done
	
	echo "step7c: Zipping demo plug-ins ..."
	plugins="Celum Claro EZPublish FlickrPublish FlickrSearch Fotoware Guardian Imprezzeo NYTimes QRCode SMS Tripolis YouTubePublish AspellShellSpelling GoogleWebSpelling Tika"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE_SERVER}/Enterprise_release/plugins/demo" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7d: Zipping examples plug-ins ..."
	plugins="CopyrightValidationDemo SimpleFileSystem CustomObjectPropsDemo CustomAdminPropsDemo MultiChannelPublishingSample AddSubApplication StandaloneAutocompleteSample"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE_SERVER}/Enterprise_release/plugins/examples" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7e: Zipping buildtest plug-ins ..."
	plugins="AnalyticsTest PublishingTest AutoTargetingTest AutoNamingTest"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE_SERVER}/Enterprise_release/plugins/buildtest" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7f: Copying the ionCube Loaders to the artifacts folder ..."
	cp "${WORKSPACE_SERVER}/Enterprise/ionCube/loaders/v5.0.14_at_2015_07_29/ioncube_loaders_all_platforms.zip" "${WORKSPACE}/artifacts"
	chmod +w "${WORKSPACE}/artifacts/ioncube_loaders_all_platforms.zip"
	# Note that we COPY (not MOVE) since P4 won't get the ZIP file again once retrieved before. (With a MOVE, the next build would fail.)
}

#
# Archives the ProxyForSC solution in a ZIP file.
#
function step8_zipProxyForSC {
	echo "step8a: Zipping ProxyForSC ..."
	zipFolder "${WORKSPACE_SERVER}/Enterprise" "ProxyForSC" "${WORKSPACE}/artifacts" "ProxyForSC_v${PROXYFORSC_VERSION}_Build${PROXYFORSC_BUILDNR}.zip"
}

#
# For a Daily, local changes (version info updates) are reverted. For (pre)release builds, this info is submit to Perforce.
#
function step9_submitOrRevertLocalVersionInfoUpdates {
	if [ "${SERVER_RELEASE_TYPE}" == "Daily" ]; then
		echo "step8: Revert local version info updates because it is a Daily build."
		p4 revert -c default ...
	else
		echo "step8: Submit local version info updates because it is NOT a Daily build."
		p4 submit -d "[Ent Server ${SERVER_VERSION}] Jenkins: Updated version info for build ${BUILD_NUMBER}."
	fi
}

# exit on unset variables
set -u

# Main build procedure
set +x; echo "================ Step 0 ================"; set -x
step0_validateEnvironment
cd "${WORKSPACE_SERVER}"
set +x; echo "================ Step 1 ================"; set -x
step1_cleanGetWorkspace
set +x; echo "================ Step 2 ================"; set -x
step2_updateResourceFiles
set +x; echo "================ Step 3 ================"; set -x
step3_updateVersionInfo
set +x; echo "================ Step 4 ================"; set -x
step4_validatePhpCode
set +x; echo "================ Step 5 ================"; set -x
step5_ionCubeEncodePhpFiles
set +x; echo "================ Step 6 ================"; set -x
step6_zipEnterpriseServer
set +x; echo "================ Step 7 ================"; set -x
step7_zipExternalModules
set +x; echo "================ Step 8 ================"; set -x
step8_zipProxyForSC
set +x; echo "================ Step 9 ================"; set -x
step9_submitOrRevertLocalVersionInfoUpdates