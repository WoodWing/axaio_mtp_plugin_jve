#!/bin/sh
# @since        9.5
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# Build script for Enterprise Server 10.2 (or later). Designed to run on the Zetes build machine running CentOS7.
# Initially, for 9.5 it was written for Perforce integration but since 10.1 it is redesigned for Git integration.
#
# It retrieves source code files from Git and updates version info in the core, plugins and 3rd party modules.
# Some source code files are encoded with ionCube. The server, plugins and 3rd party modules are archived at last.
#

SOURCE_BASE="./Enterprise/"
TARGET_BASE="./Enterprise_release/"
iONCUBE_ENCODER="/usr/local/ioncube/10.0/ioncube_encoder71_10.0_64"
PHPSTORM_INSPECTOR="/opt/phpstorm2017.1.4/bin/inspect.sh"
PHPSTORM_LOGFILE=~/.PhpStorm2017.1/system/log/idea.log
: ${PHP_EXE:=php} #Set default value to base php executable.

# To maintain the es_php_encoder.php and php_define.php tools that are installed on Zetes, please use the Git repository
# named "enterprise-server-build-tools" and run the Jenkins project named "Enterprise Server Build Tools" to upgrade.
ES_PHP_ENCODER="/home/autobuild/workspace/Enterprise Server Build Tools/es_php_encoder.php"
ES_PHP_DEFINE="/home/autobuild/workspace/Enterprise Server Build Tools/php_define.php"
ES_REPLACE_RESOURCE_KEYS="/home/autobuild/workspace/Enterprise Server Build Tools/replace_resource_keys.php"

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
# Overwrites the version label with a new given version for a given _productversion.txt file.
#
# After updating the version, the file is added to Git in await for the next commit and push.
# Because the given _productversion.txt files are added to the .gitignore file this is done with force.
#
# @param string $1 Full local file path of the _productversion.txt file to update.
# @param string $2 New version number (x.y.z) to replace the old one with.
# @param integer $3 New build number to replace the old one with.
#
function replaceVersionFile {
	echo -n "${2} Build ${3}" > "${1}"

	# Error when the new version can not be found in the updated file.
	set +e
	testVersion=`grep "${2} Build ${3}" "${1}"`
	if [ ! -n "${testVersion}" ]; then
		echo "ERROR: Could not update version in ${1}"
		exit 1
	fi
	set -e
}

#
# Replaces a version in a given PHP file. It searches for a given regular expression prefix followed by "<x.y.z> Build <nr>".
#
# After updating the version, the file is added to Git in await for the next commit and push.
#
# @param string $1 Full local file path of PHP file to update version in.
# @param string $2 Regular expression of some text just before the old version.
# @param string $3 New version number (x.y.z) to replace the old one with.
# @param integer $4 New build number to replace the old one with.
#
function updateVersion {
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
	git add "${1}"
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
	pluginsBaseFolder=`pwd`
	for pluginFolder in $(find "${pluginsBaseFolder}" -maxdepth 1 -mindepth 1 -type d); do
		replaceVersionFile "${pluginFolder}/_productversion.txt" ${2} ${3}
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
	git add "${1}"
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
# It is derived from SERVER_VERSION, SERVER_RELEASE_TYPE and GIT_BRANCH. Some examples:
#    -----------------------------------
#    SERVER_VERSION: [10.1.0]
#    SERVER_RELEASE_TYPE: [Daily]
#    GIT_BRANCH: [master]
#    SERVER_VERSION_ZIP: [v10.1_Master_Daily_Build123.zip]
#    -----------------------------------
#    SERVER_VERSION: [10.1.0]
#    SERVER_RELEASE_TYPE: [Daily]
#    GIT_BRANCH: [work2]
#    SERVER_VERSION_ZIP: [v10.1_Master_Work2_Daily_Build123.zip]
#    -----------------------------------
#    SERVER_VERSION: [10.0.0]
#    SERVER_RELEASE_TYPE: [Prerelease]
#    GIT_BRANCH: [release/v10.0.x]
#    SERVER_VERSION_ZIP: [v10.0.0_Prerelease_Build123.zip]
#    -----------------------------------
#    SERVER_VERSION: [10.0.1]
#    SERVER_RELEASE_TYPE: [Daily]
#    GIT_BRANCH: [release/v10.0.x]
#    SERVER_VERSION_ZIP: [v10.0.1_Daily_Build123.zip]
#    -----------------------------------
#    SERVER_VERSION: [10.0.1]
#    SERVER_RELEASE_TYPE: [Release]
#    GIT_BRANCH: [release/v10.0.1]
#    SERVER_VERSION_ZIP: [v10.0.1_Build123.zip]
#    -----------------------------------
#
function determineZipPostfix {
	# Start with the server version, but remove the patch digit for daily master builds.
	SERVER_VERSION_ZIP="${SERVER_VERSION}"
	if [[ "${SERVER_RELEASE_TYPE}" == "Daily" && ( "${GIT_BRANCH}" == "master" || "${GIT_BRANCH}" == work* ) ]]; then
		SERVER_VERSION_ZIP=`echo "${SERVER_VERSION_ZIP}" | sed -r "s/([0-9]+\.[0-9]+)\.[0-9]+/\1/g"`
	fi

	# Prefix with "v".
	SERVER_VERSION_ZIP="v${SERVER_VERSION_ZIP}"

	# Add "_Master" for master branches.
	if [[ "${GIT_BRANCH}" == "master" || "${GIT_BRANCH}" == work* ]]; then
		SERVER_VERSION_ZIP="${SERVER_VERSION_ZIP}_Master"
	fi

	# Add "_Work", "_Work2" or "_Work3" for master work branches.
	if [[ "${GIT_BRANCH}" == work* ]]; then
		SERVER_VERSION_ZIP="${SERVER_VERSION_ZIP}_Work"
		workDigit=`echo "${GIT_BRANCH}" | sed -r "s/work([[:digit:]]+)/\1/g"`
		if [ -n "${workDigit}" ]; then
			SERVER_VERSION_ZIP="${SERVER_VERSION_ZIP}${workDigit}"
		fi
	fi

	# Add "_Daily" or "_Prelease" for non-release builds.
	if [[ "${SERVER_RELEASE_TYPE}" != "Release" ]]; then
		SERVER_VERSION_ZIP="${SERVER_VERSION_ZIP}_${SERVER_RELEASE_TYPE}"
	fi

	# Add "_Build<nr>.zip" postfix.
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
	validateEnvironmentVariableNotEmpty ADOBEDPS2_BUILDNR "${ADOBEDPS2_BUILDNR}"
	validateEnvironmentVariableNotEmpty BUILD_NUMBER "${BUILD_NUMBER}"
	validateEnvironmentVariableNotEmpty PROXYFORSC_VERSION "${PROXYFORSC_VERSION}"
	validateEnvironmentVariableNotEmpty PROXYFORSC_BUILDNR "${PROXYFORSC_BUILDNR}"
	validateEnvironmentVariableNotEmpty GIT_BRANCH "${GIT_BRANCH}"
	validateEnvironmentVariableNotEmpty SERVER_VERSION "${SERVER_VERSION}"
	validateEnvironmentVariableNotEmpty SERVER_RELEASE_TYPE "${SERVER_RELEASE_TYPE}"

	if [[ "${GIT_BRANCH}" != release/* && "${GIT_BRANCH}" != "master" && "${GIT_BRANCH}" != work* ]]; then
		echo "ERROR: Environment variable GIT_BRANCH has unsupported value: ${GIT_BRANCH}"
		exit 1
	fi
	if [[ "${SERVER_RELEASE_TYPE}" != "Release" && "${SERVER_RELEASE_TYPE}" != "Daily" && "${SERVER_RELEASE_TYPE}" != "Prerelease" ]]; then
		echo "ERROR: Environment variable SERVER_RELEASE_TYPE has unsupported value: ${SERVER_RELEASE_TYPE}"
		exit 1
	fi

	echo "step0b: Determining the postfix to use for artifacts (SERVER_VERSION_ZIP)..."
	determineZipPostfix
	echo "${SERVER_VERSION_ZIP}: [${SERVER_VERSION_ZIP}]"
	set -x
}

#
# Cleans release-, resources-, reports- and artifacts folders locally.
#
function step1_cleanGetWorkspace {

	echo "step1a: Checkout the ${GIT_BRANCH} branch in the workspace."
	git checkout --track "origin/${GIT_BRANCH}"

	echo "step1b: Get latest changes from remote repository."
	git pull

	echo "step1c: Remove all files and folders that are not tracked by Git (e.g. generated by previous build runs)."
	git clean -f -d

	echo "step1d: Create release folder for Enterprise Server."
	mkdir ${TARGET_BASE}

	echo "step1e: Create download folder for TMS resource strings."
	mkdir "${WORKSPACE}/tms_resources"

	echo "step1f: Create report folder for our home brewed PHP code validation."
	mkdir "${WORKSPACE}/reports"

	echo "step1g: Create report folder for phpStorm code analyser."
	mkdir "${WORKSPACE}/reports/phpstorm_strict"

	echo "step1h: Create folder for the ZIP packages (to ship)."
	mkdir "${WORKSPACE}/artifacts"

	echo "step1i: Create folder for the internal files (not to ship)."
	mkdir "${WORKSPACE}/internals"

	echo "step1j: Create folder to temporary exclude files that do not need validation by phpStorm code analyser."
	mkdir "${WORKSPACE}/code_analyser"

	# The .default needs to be removed in order to successfully run the coding tests. The tests expect a healthy system,
	# which includes an existing config_overrule file. When creating the artifact the filename will be restored.
	echo "step1k: Install empty placeholder for Enterprise administrators to overrule config options."
	cp "${WORKSPACE}/Enterprise/Build/config_overrule.php.default" "${WORKSPACE}/Enterprise/Enterprise/config/config_overrule.php"
}

function updateResourceFiles {
	product=$1
	dir=$2
	step=$3
	version=$4
	productlc=$( echo ${product} | tr "[:upper:]" "[:lower:]" )

	echo "${step}1: Retrieve timestamp of last update from TMS for the ${product} plugin."
	URL="http://tms.woodwing.net/product/lastupdateversion/productname/Enterprise%20Server%20${product}/version/10.0/"
	tmsLastUpdate=$( curl ${URL} )
	if [ ! -n "${tmsLastUpdate}"  ]; then
		echo "Could not retrieve last modification timestamp from TMS (for the ${product} project). Is TMS down?";
		exit 1;
	fi

	echo "${step}2: Retrieve timestamp of last update from local resource file for ${product} plugin."
	resLastUpdate=$( cat "${SOURCE_BASE}${dir}${product}/resources/_lastupdate.txt" )
	if [ "${tmsLastUpdate}" == "${resLastUpdate}" ]; then
		echo "${step}3: Repository and TMS are in sync. No update needed."
	else
		echo "${step}3: Repository is out-of-sync with TMS. Downloading resources..."
		wget "http://tms.woodwing.net/product/getexport/user/woodwing/pass/QjQjI2VyVmxAQDE=/versionid/${version}" -O ./tms_resources/${productlc}.zip

		echo "${step}4: Extract resource archive and overwrite local resources."
		cd ${SOURCE_BASE}${dir}${product}/resources
		7za e -y "${WORKSPACE}/tms_resources/${productlc}.zip" "Server/config/resources"
		rm -f "${WORKSPACE}/tms_resources/${productlc}.zip"
		cd -

		echo "${step}5: Prefix the resource keys with ${product}."
		${PHP_EXE} "${ES_REPLACE_RESOURCE_KEYS}" "${WORKSPACE}/Enterprise/${dir}${product}/resources" ${product}

		echo "${step}6: Write timestamp of last update from TMS into the resource folder of ${product} plugin."
		echo "${tmsLastUpdate}" > ${SOURCE_BASE}${dir}${product}/resources/_lastupdate.txt

		echo "${step}7: Remove the timestamp from the downloaded XML files."
		for icFile in $(find "${SOURCE_BASE}${dir}${product}/resources/" -name '*.xml'); do
			sed '/<!--Last edit date in TMS:.*-->/d' "${icFile}" > ./temp && mv ./temp "${icFile}"
		done

		echo "${step}8: Commit changed resource files to repository."
		git add ${SOURCE_BASE}${dir}${product}/resources/*.xml
		git add --force ${SOURCE_BASE}${dir}${product}/resources/_lastupdate.txt
		git commit -m "[Ent Server ${SERVER_VERSION}] Jenkins: Updated latest (${tmsLastUpdate}) ${product} resource files from TMS for server build ${BUILD_NUMBER}."

		echo "${step}9: Push changed resource files to Git."
		git push --set-upstream origin "${GIT_BRANCH}"
	fi
}


#
# Downloads the latest Enterprise Server resource files from TMS and submits changes to the repository.
#
# Note that for historical reasons the XML resource files downloaded from TMS contain a timestamp
# at the second line, such as: <!--Last edit date in TMS: 31-05-2016 07:08:39 GMT-->
# However, this leads to conflicts when merging code branches and so we take out those lines.
# Nevertheless, to avoid unnecessary daily submits without changes (that would blur the view)
# we keep track of the last modification timestamp of TMS in the a file named "_lastupdate.txt"
# (that resides in the resource folder) which allows us to compare timestamps and skip submits.
#
function step2a_updateResourceFilesForCoreServer {
	echo "step2a1: Retrieve timestamp of last update from TMS for core Enterprise Server."
	tmsLastUpdate=`curl http://tms.woodwing.net/product/lastupdateversion/productname/Enterprise%20server/version/10.0/`
	if [ ! -n "${tmsLastUpdate}"  ]; then
		echo 'Could not retrieve last modification timestamp from TMS (for the core Enterprise Server project). Is TMS down?';
		exit 1;
	fi

	echo "step2a2: Retrieve timestamp of last update from local resource file for core Enterprise Server."
	resLastUpdate=`cat "${SOURCE_BASE}Enterprise/config/resources/_lastupdate.txt"`
	if [ "${tmsLastUpdate}" == "${resLastUpdate}" ]; then
		echo "step2a3: Repository and TMS are in sync. No update needed."
	else
		echo "step2a3: Repository is out-of-sync with TMS. Downloading resources..."
		wget "http://tms.woodwing.net/product/getexport/user/woodwing/pass/QjQjI2VyVmxAQDE=/versionid/116" -O ./tms_resources/core.zip
		# L> update the versionid param when migrating to new Enterprise major version: 10=7.0, 22=8.0, 73=9.0, 116=10.0

		echo "step2a4: Extract resource archive and overwrite local resources."
		cd ${SOURCE_BASE}Enterprise/config/resources
		7za e -y "${WORKSPACE}/tms_resources/core.zip" "Server/config/resources"
		rm -f "${WORKSPACE}/tms_resources/core.zip"
		cd -

		echo "step2a5: Write timestamp of last update from TMS into the resource folder of Enterprise Server."
		echo -n "${tmsLastUpdate}" > ${SOURCE_BASE}Enterprise/config/resources/_lastupdate.txt

		echo "step2a6: Remove the timestamp from the downloaded XML files."
		for icFile in $(find "${SOURCE_BASE}Enterprise/config/resources/" -name '*.xml'); do
			sed '/<!--Last edit date in TMS:.*-->/d' "${icFile}" > ./temp && mv ./temp "${icFile}"
		done

		echo "step2a7: Commit changed resource files to repository."
		git add ${SOURCE_BASE}Enterprise/config/resources/*.xml
		git add --force ${SOURCE_BASE}Enterprise/config/resources/_lastupdate.txt
		git commit -m "[Ent Server ${SERVER_VERSION}] Jenkins: Updated latest (${tmsLastUpdate}) core resource files from TMS for server build ${BUILD_NUMBER}."

		echo "step2a8: Push changed resource files to Git."
		git push --set-upstream origin "${GIT_BRANCH}"
	fi
}

#
# Downloads the latest Adobe AEM resource files from TMS and submits changes to the repository.
#
# Note that for historical reasons the XML resource files downloaded from TMS contain a timestamp
# at the second line, such as: <!--Last edit date in TMS: 31-05-2016 07:08:39 GMT-->
# However, this leads to conflicts when merging code branches and so we take out those lines.
# Nevertheless, to avoid unnecessary daily submits without changes (that would blur the view)
# we keep track of the last modification timestamp of TMS in the a file named "_lastupdate.txt"
# (that resides in the resource folder) which allows us to compare timestamps and skip submits.
#
function step2b_updateResourceFilesForAdobeAEM {
	updateResourceFiles AdobeDps2 plugins/release/ step2b 117
}

#
# Downloads the latest Maintenance Mode resource files from TMS and submits changes to the repository.
#
# Note that for historical reasons the XML resource files downloaded from TMS contain a timestamp
# at the second line, such as: <!--Last edit date in TMS: 31-05-2016 07:08:39 GMT-->
# However, this leads to conflicts when merging code branches and so we take out those lines.
# Nevertheless, to avoid unnecessary daily submits without changes (that would blur the view)
# we keep track of the last modification timestamp of TMS in the a file named "_lastupdate.txt"
# (that resides in the resource folder) which allows us to compare timestamps and skip submits.
#
function step2c_updateResourceFilesForMaintenanceMode {
	updateResourceFiles MaintenanceMode Enterprise/server/plugins/ step2c 122
}

#
# Downloads the latest Elvis content source resource files from TMS and submits changes to the repository.
#
# Note that for historical reasons the XML resource files downloaded from TMS contain a timestamp
# at the second line, such as: <!--Last edit date in TMS: 31-05-2016 07:08:39 GMT-->
# However, this leads to conflicts when merging code branches and so we take out those lines.
# Nevertheless, to avoid unnecessary daily submits without changes (that would blur the view)
# we keep track of the last modification timestamp of TMS in the a file named "_lastupdate.txt"
# (that resides in the resource folder) which allows us to compare timestamps and skip submits.
#
function step2d_updateResourceFilesForElvis {
	updateResourceFiles Elvis plugins/release/ step2d 124
}

#
# Updates version info embedded in PHP modules.
#
function step3a_updateVersionInfo {
	echo "step3a1: Update version info in serverinfo.php."
	replaceVersionFile ${SOURCE_BASE}Enterprise/server/_productversion.txt ${SERVER_VERSION} ${BUILD_NUMBER}
	if [ "${SERVER_RELEASE_TYPE}" == "Release" ]; then
		echo "" > "${SOURCE_BASE}Enterprise/server/_productversionextra.txt"
	else
		echo -n "${SERVER_RELEASE_TYPE}" > "${SOURCE_BASE}Enterprise/server/_productversionextra.txt"
	fi

	echo "step3a2: Update version info in server plugins."
	updatePluginVersions ${SOURCE_BASE}Enterprise/config/plugins ${SERVER_VERSION} ${BUILD_NUMBER}
	updatePluginVersions ${SOURCE_BASE}Enterprise/server/plugins ${SERVER_VERSION} ${BUILD_NUMBER}
	updatePluginVersions ${SOURCE_BASE}plugins/release ${SERVER_VERSION} ${BUILD_NUMBER}

	echo "step3a3: Update version info in AdobeDps2 plugin. It has its own buildnr, but use the major.minor of Enterprise."
	twoDigitVersion=`echo "${SERVER_VERSION}" | sed -r "s/([0-9]+\.[0-9]+)(\.[0-9]+)?/\1/g"` # ignores patch nr
	replaceVersionFile ${SOURCE_BASE}plugins/release/AdobeDps2/_productversion.txt "${twoDigitVersion}" ${ADOBEDPS2_BUILDNR}

	echo "step3a4: Update version info of the ProxyForSC solution."
	replaceVersionFile ${SOURCE_BASE}ProxyForSC/proxyserver/_productversion.txt ${PROXYFORSC_VERSION} ${PROXYFORSC_BUILDNR}
	replaceVersionFile ${SOURCE_BASE}ProxyForSC/proxystub/_productversion.txt ${PROXYFORSC_VERSION} ${PROXYFORSC_BUILDNR}
	replaceVersionFile ${SOURCE_BASE}ProxyForSC/speedtest/_productversion.txt ${PROXYFORSC_VERSION} ${PROXYFORSC_BUILDNR}

	echo "step3a5: Update version info in 3rd party modules."
	updateVersion ${SOURCE_BASE}Integrations/Drupal7/modules/ww_enterprise/ww_enterprise.info "^version\s*=\s*[\"']" ${SERVER_VERSION} ${BUILD_NUMBER}
	updateVersion ${SOURCE_BASE}Integrations/Drupal8/modules/ww_enterprise/ww_enterprise.info.yml "^version\s*:\s*[\"']" ${SERVER_VERSION} ${BUILD_NUMBER}
	updateVersion ${SOURCE_BASE}Integrations/WordPress/plugins/ww_enterprise/ww_enterprise.php "^\s*\*\s*Version:\s*" ${SERVER_VERSION} ${BUILD_NUMBER}

	if [ "${SERVER_RELEASE_TYPE}" == "Daily" ]; then
		echo "step3a6: Skip pushing version info changes to Git since it is a Daily build."
	else
		echo "step3a6: Push version info changes to Git."
		git commit -m "[Ent Server ${SERVER_VERSION}] Jenkins: Updated product version info files with ${BUILD_NUMBER}."
		git push --set-upstream origin "${GIT_BRANCH}"
	fi
}

#
# Adds a label to the repository. This step is skipped for Daily builds.
#
function step3b_updateVersionInRepository {
	if [ "${SERVER_RELEASE_TYPE}" == "Daily" ]; then
		echo "step3b1: Skip version labeling in Git since it is a Daily build."
	else
		echo "step3b1: Version labeling in Git."
		git tag "${SERVER_VERSION}_Build_${BUILD_NUMBER}"
		git push --tags
	fi
}

#
# Checks if the PHP sources can be loaded and have correct syntax.
#
function step4_validatePhpCode {
	echo "step4a: Run the PHP Coding Test (testsuite)."
	cd "${WORKSPACE}/Enterprise/Enterprise/server/wwtest/testsuite/"
	${PHP_EXE} junitcliclient.php PhpCodingTest "${WORKSPACE}/reports"
	cd -

	echo "step4b: Temporary move 3rd party libraries aside (to exclude them from phpStorm's code inspection)."
	mv "${WORKSPACE}/Enterprise/Enterprise/server/dgrid" "${WORKSPACE}/code_analyser"
	mv "${WORKSPACE}/Enterprise/Enterprise/server/jquery" "${WORKSPACE}/code_analyser"
	mv "${WORKSPACE}/Enterprise/Enterprise/server/vendor" "${WORKSPACE}/code_analyser"
	mv "${WORKSPACE}/Enterprise/Enterprise/server/ZendFramework" "${WORKSPACE}/code_analyser"

	echo "step4c: Run phpStorm's code inspection on the server folder."
	# mkdir ./reports/phpstorm_strict
	# inspect.sh params: <project file path> <inspection profile path> <output path> -d <directory to be inspected>
	# see more info: http://www.jetbrains.com/phpstorm/webhelp/running-inspections-offline.html
	# Use "set +e" and "set -e" to temporary suppress exiting the script in case the code inspector exits.
	set +e
	sh ${PHPSTORM_INSPECTOR} "${WORKSPACE}/Enterprise" "${WORKSPACE}/Enterprise/.idea/inspectionProfiles/EnterpriseCodeInspection.xml" "${WORKSPACE}/reports/phpstorm_strict" -d "${WORKSPACE}/Enterprise/Enterprise"
	if [ $? -ne 0 ]; then
		# The process could fail e.g. due to license expiration, so dump the log to see what is causing the failure.
		echo "phpStorm code inspection has failed. Now dumping the phpStorm system log file..."
		echo "----------------------8<----------------------8<----------------------"
		cat "${PHPSTORM_LOGFILE}"
		echo "----------------------8<----------------------8<----------------------"
		exit 1
	fi
	set -e

	echo "step4d: Move back the 3rd party libraries (that were temporary moved aside) to their original location."
	mv "${WORKSPACE}/code_analyser/dgrid" "${WORKSPACE}/Enterprise/Enterprise/server"
	mv "${WORKSPACE}/code_analyser/jquery" "${WORKSPACE}/Enterprise/Enterprise/server"
	mv "${WORKSPACE}/code_analyser/vendor" "${WORKSPACE}/Enterprise/Enterprise/server"
	mv "${WORKSPACE}/code_analyser/ZendFramework" "${WORKSPACE}/Enterprise/Enterprise/server"

	cd "${WORKSPACE}/Enterprise/Build/"
	echo "step4e: Convert folder with XML files (output of phpStorm's code inspection) to one JUnit XML file to display in UI of Jenkins."
	# phpstorm2junit params: <folder path with code inspector output> <output path for jUnit>
	${PHP_EXE} phpstorm2junit.php "\"${WORKSPACE}/reports/phpstorm_strict\"" "\"${WORKSPACE}/reports/TEST-PhpStormCodeInspection.xml\""
	cd -
}

#
# Makes a copy of core and plugins into the release folder and encodes some PHP files.
#
function step5_ionCubeEncodePhpFiles {

	echo 'step5a: Make local copy of Enterprise Server source (and plugins) to release folder.'
	cp -r ${SOURCE_BASE}Enterprise ${TARGET_BASE}Enterprise
	cp -r ${SOURCE_BASE}plugins ${TARGET_BASE}plugins
	sync

	echo "step5b: Remove some unwanted files and folder from the release folder that should NOT be released."
	rm -f -r ${TARGET_BASE}Enterprise/phpunit
	rm -f -r ${TARGET_BASE}Enterprise/server/buildtools
	rm -f -r ${TARGET_BASE}Enterprise/server/useful
	rm -f -r ${TARGET_BASE}Enterprise/server/vendor/solarium/solarium/examples
	rm -f -r ${TARGET_BASE}Enterprise/server/vendor/solarium/solarium/tests
	rm -f -r ${TARGET_BASE}Enterprise/server/wwtest/development
	rm -f -r ${TARGET_BASE}Enterprise/server/wwtest/testsuite/PhpCoding
	rm -f -r ${TARGET_BASE}Enterprise/server/wwtest/testsuite/BuildTest2
	sync

	echo 'step5c: Determine the last Enterprise Server version that introduced a new ionCube Encoder.'
	esBaseVersion=`${PHP_EXE} "${ES_PHP_DEFINE}" --get --define=WW_ES_BASE_VERSION_FOR_IONCUBE --stripquotes < "${SOURCE_BASE}/Enterprise/server/serverinfo.php"`
	if [ ! -n "${esBaseVersion}" ]; then
		echo "ERROR: Failed reading the WW_ES_BASE_VERSION_FOR_IONCUBE option from serverinfo.php."
		exit 1
	fi
	echo "Detected last Enterprise Server version that introduced a new ionCube Encoder: ${esBaseVersion}"

	# TODO: Add the --acquire-license feature to the ES_PHP_ENCODER so that we can remove use of iONCUBE_ENCODER
	echo 'step5d: Acquire license for ionCube Encoded to make sure it is still valid.'
	sudo "${iONCUBE_ENCODER}" --acquire-license
	"${iONCUBE_ENCODER}" -V
	# "${iONCUBE_ENCODER}" --help | more

	echo "step5e: Encode specific Enterprise core files and folders."
	ioncubeEncodeParams="--sourcebase=${SOURCE_BASE} --targetbase=${TARGET_BASE} --esversion=${esBaseVersion} --deployment=integrated"
	icFoldersOrFiles="\
		Enterprise/server/admin/license/ \
		Enterprise/server/dbclasses/ \
		Enterprise/server/dbdrivers/ \
		Enterprise/server/services/ \
		Enterprise/server/appservices/ \
		Enterprise/server/wwtest/ngrams/ \
		Enterprise/server/regserver.inc.php \
		Enterprise/server/bizclasses/BizServerJob.class.php \
		Enterprise/server/bizclasses/BizPublishing.class.php \
		Enterprise/server/bizclasses/BizSemaphore.class.php \
		Enterprise/server/wwtest/testsuite/HealthCheck2/Licenses_TestCase.php \
		plugins/release/Elvis/Elvis_WflLogOn.class.php \
		plugins/release/Elvis/Elvis_ContentSource.class.php \
	"
	for icFoldersOrFile in ${icFoldersOrFiles}; do
		${PHP_EXE} "${ES_PHP_ENCODER}" ${ioncubeEncodeParams} --encodelevel=1 --phppath="${icFoldersOrFile}"
	done

	echo "step5f: Encode specific Enterprise core files and folders (in 2nd level of security)."
	icFoldersOrFiles="\
		Enterprise/server/utils/license/ \
	"
	for icFoldersOrFile in ${icFoldersOrFiles}; do
		${PHP_EXE} "${ES_PHP_ENCODER}" ${ioncubeEncodeParams} --encodelevel=2 --phppath="${icFoldersOrFile}"
	done
}

#
# Archives the Enterprise Server in a ZIP file. The BuildTest and large sample data are put in separate ZIP files.
#
function step6_zipEnterpriseServer {
	echo "step6a: Renaming the config file for the archive..."
	mv "${WORKSPACE}/Enterprise_release/Enterprise/config/config_overrule.php" "${WORKSPACE}/Enterprise_release/Enterprise/config/config_overrule.php.default"

	echo "step6b: Zipping BuildTest..."
	zipFolder "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testsuite" "BuildTest" "${WORKSPACE}/artifacts" "BuildTest1_${SERVER_VERSION_ZIP}"

	echo "step6c: Zipping large sample data..."
	zipFolder "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testdata" "largeSpeedTestData" "${WORKSPACE}/artifacts" "largeSpeedTestData_${SERVER_VERSION_ZIP}"

	echo "step6d: Excluding (removing) testsuite stuff that not needed for production..."
	rm -rf "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testsuite/BuildTest"
	rm -rf "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testsuite/BuildTest2"
	rm -rf "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testsuite/PhpCodingTest"
	rm -rf "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testdata/largeSpeedTestData"

	echo "step6e: Zipping Enterprise Server..."
	zipFolder "${WORKSPACE}/Enterprise_release" "Enterprise" "${WORKSPACE}/artifacts" "EnterpriseServer_${SERVER_VERSION_ZIP}"
}

#
# Archives PHP modules for 3rd party integrations.
#
function step7_zipExternalModules {
	echo "step7a: Zipping 3rd party modules module ..."
	zipFolder "${WORKSPACE}/Enterprise/Integrations/Drupal7/modules" "ww_enterprise" "${WORKSPACE}/artifacts" "Drupal7Enterprise_${SERVER_VERSION_ZIP}"
	zipFolder "${WORKSPACE}/Enterprise/Integrations/Drupal8/modules" "ww_enterprise" "${WORKSPACE}/artifacts" "Drupal8_Drupal_Module_${SERVER_VERSION_ZIP}"
	zipFolder "${WORKSPACE}/Enterprise/Integrations/WordPress/plugins" "ww_enterprise" "${WORKSPACE}/artifacts" "WordPress_Plugin_${SERVER_VERSION_ZIP}"
	zipFolder "${WORKSPACE}/Enterprise/Integrations" "Solr" "${WORKSPACE}/artifacts" "SolrEnterprise_${SERVER_VERSION_ZIP}"

	echo "step7b: Zipping release plug-ins ..."
	twoDigitVersion=`echo "${SERVER_VERSION}" | sed -r "s/([0-9]+\.[0-9]+)(\.[0-9]+)?/\1/g"` # ignores patch nr
	zipFolder "${WORKSPACE}/Enterprise_release/plugins/release" "AdobeDps2" "${WORKSPACE}/artifacts" "Adobe_AEM_Build_${ADOBEDPS2_BUILDNR}_for_Enterprise_${twoDigitVersion}.zip"
	plugins="Facebook Twitter WordPress Drupal8 Elvis"
	for plugin in ${plugins}; do
		# For Drupal 8 we want to modify the name to indicate this is the plugin (and not the module)
		if [ "${plugin}" == "Drupal8" ]; then
			zipFolder "${WORKSPACE}/Enterprise_release/plugins/release" "${plugin}" "${WORKSPACE}/artifacts" "Drupal8_Enterprise_Plugin_${SERVER_VERSION_ZIP}"
		else
			zipFolder "${WORKSPACE}/Enterprise_release/plugins/release" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
		fi
	done

	echo "step7c: Zipping demo plug-ins ..."
	plugins="Celum Claro EZPublish FlickrPublish FlickrSearch Fotoware Guardian NYTimes QRCode SMS Tripolis YouTubePublish AspellShellSpelling GoogleWebSpelling Tika"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE}/Enterprise_release/plugins/demo" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7d: Zipping examples plug-ins ..."
	plugins="CopyrightValidationDemo CopyWithPlacements SimpleFileSystem CustomObjectPropsDemo CustomAdminPropsDemo MultiChannelPublishingSample AddSubApplication StandaloneAutocompleteSample"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE}/Enterprise_release/plugins/examples" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7e: Zipping buildtest plug-ins ..."
	plugins="PublishingTest AutoTargetingTest AutoNamingTest"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE}/Enterprise_release/plugins/buildtest" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7f: Copying the ionCube Loaders to the artifacts folder ..."
	cp "${WORKSPACE}/Enterprise/Libraries/ionCube/loaders/v10.0.2_at_2017_09_13/ioncube_loaders_all_platforms.zip" "${WORKSPACE}/artifacts"
	chmod +w "${WORKSPACE}/artifacts/ioncube_loaders_all_platforms.zip"
}

#
# Archives the ProxyForSC solution in a ZIP file.
#
function step8_zipProxyForSC {
	echo "step8a: Zipping ProxyForSC ..."
	zipFolder "${WORKSPACE}/Enterprise" "ProxyForSC" "${WORKSPACE}/artifacts" "ProxyForSC_v${PROXYFORSC_VERSION}_Build${PROXYFORSC_BUILDNR}.zip"
}

#
# Copy the test script to the internals folder that is sent by Jenkins from this build server
# to the test server.
#
function step9_prepareScriptForTestServer {
	cp "${WORKSPACE}/Enterprise/Build/build_test_macosx.sh" "${WORKSPACE}/internals"
}

# exit on unset variables
set -u

# init global variables
ionCubeBadAttempts=0
ionCubeEncodedFiles=0

# Main build procedure
set +x; echo "================ Step 0 ================"; set -x
step0_validateEnvironment
cd "${WORKSPACE}"
set +x; echo "================ Step 1 ================"; set -x
step1_cleanGetWorkspace
set +x; echo "================ Step 2 ================"; set -x
step2a_updateResourceFilesForCoreServer
step2b_updateResourceFilesForAdobeAEM
step2c_updateResourceFilesForMaintenanceMode
step2d_updateResourceFilesForElvis
set +x; echo "================ Step 3 ================"; set -x
step3a_updateVersionInfo
step3b_updateVersionInRepository
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
step9_prepareScriptForTestServer
