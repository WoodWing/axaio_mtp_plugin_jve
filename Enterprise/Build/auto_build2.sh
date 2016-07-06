#!/bin/sh
# @package      Enterprise
# @subpackage   Build
# @since        9.5
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# Build script for Enterprise Server 10.1 (or later). Designed to run on the Zetes build machine running CentOS7.
# Initially, for 9.5 it was written for Perforce integration but since 10.1 it is redesigned for Git integration.
#
# It retrieves source code files from Git and updates version info in the core, plugins and 3rd party modules.
# Some source code files are encoded with ionCube. The server, plugins and 3rd party modules are archived at last.
#

SOURCE_BASE="./Enterprise/"
TARGET_BASE="./Enterprise_release/"
iONCUBE_ENCODER="/usr/local/ioncube/9.0/ioncube_encoder54_9.0_64"

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
	echo "${2} Build ${3}" > "${1}"

	# Error when the new version can not be found in the updated file.
	set +e
	testVersion=`grep "${2} Build ${3}" "${1}"`
	if [ ! -n "${testVersion}" ]; then
		echo "ERROR: Could not update version in ${1}"
		exit 1
	fi
	set -e
	if [ "${SERVER_RELEASE_TYPE}" != "Daily" ]; then
		git add --force "${1}"
	fi
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
	if [[ "${SERVER_RELEASE_TYPE}" == "Daily" && 
		( "${GIT_BRANCH}" == "master" || "${GIT_BRANCH}" == work* ) ]]; then
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
# ionCube Encode a given PHP file. 
#
# It reads from the ${SOURCE_BASE} folder and writes into the ${TARGET_BASE} folder.
# 
# @param string ${1} ionCube Encoder project options file
# @param string ${2} Relative path of file to be ionCube encoded
#
function ionCubeFile {
	sourceFile="${SOURCE_BASE}${2}"
	targetFile="${TARGET_BASE}${2}"
	for i in `seq 1 10`;
	do
		"${iONCUBE_ENCODER}" --project-file "${1}" "${sourceFile}" -o "${targetFile}"
		set +e
		checkSum=`grep 'ionCube Loader' ${targetFile}`
		set -e
		if [ -n "${checkSum}"  ]; then
			ionCubeEncodedFiles=$((ionCubeEncodedFiles+1))
			break
		else
			# Sometimes ionCube Encoder silently fails. It turns out that we need
			# to wait a bit and try again. The exact reason of failure is unknown (EN-87512).
			echo "WARNING: ${targetFile} is not ionCube Encoded! (will retry)";
			ionCubeBadAttempts=$((ionCubeBadAttempts+1))
			sleep 3s
		fi
	done
	if [ ! -n "${checkSum}"  ]; then
		echo "ERROR: ${targetFile} is not ionCube Encoded! (gave up)";
		head ${targetFile}
		exit 1
	fi
}

#
# ionCube Encode all PHP files in a given folder recursively.
#
# It reads from the ${SOURCE_BASE} folder and writes into the ${TARGET_BASE} folder.
# 
# @param string ${1} ionCube Encoder project options file
# @param string ${2} Relative path of folder to be ionCube encoded recursively
#
function ionCubeFolder {
	for icFile in $(find "${SOURCE_BASE}${2}" -name '*.php'); do
		ionCubeFile "${1}" "${icFile#$SOURCE_BASE}"
	done
}

#
# ionCube encode specific files and folders in Enterprise Server release folder.
#
# Encoding is done for files that are related to the licensing of Enterprise to avoid
# hackers working with our product without licenses. But also for some files that are
# rather hard to develop which we do not want to give away for free and for files that 
# integrate with a 3rd party API that should not be exposed to the outside world.
#
function ionCubeEnterpriseFiles {
	# Disable debugger; Else this function gives tons of noise. It validates encodings by itself.
	set +x
	
	echo "Encode specific Enterprise core folders."
	thisYear=`date +%Y`
	encodeOptionFile="${SOURCE_BASE}encodeoptions.txt"
	echo "--replace-target --add-comment \"(c) Copyright 2000-${thisYear} WoodWing Software, www.woodwing.com\" --obfuscate locals --obfuscation-key \"de bocht van de ronde tocht\" --optimize max --no-doc-comments --property \"magic='the windmill keeps on turning'\" --message-if-no-loader \"'No Ioncube loader installed. Please run the Health Check page (e.g. http://localhost/Enterprise/server/wwtest/testsuite.php).'\"" > "${encodeOptionFile}"
	icFolders="\
		Enterprise/server/admin/license/ \
		Enterprise/server/dbclasses/ \
		Enterprise/server/dbdrivers/ \
		Enterprise/server/services/ \
		Enterprise/server/appservices/ \
		Enterprise/server/wwtest/ngrams/ \
		Enterprise/server/plugins/AdobeDps/ \
	"
	for icFolder in ${icFolders}; do
		ionCubeFolder "${encodeOptionFile}" "${icFolder}"
	done
	
	echo "Encode specific Enterprise core files and Elvis plugin files."
	icFiles="\
		Enterprise/server/apps/webapplicense.inc.php \
		Enterprise/server/regserver.inc.php \
		Enterprise/server/bizclasses/BizServerJob.class.php \
		Enterprise/server/bizclasses/BizPublishing.class.php \
		Enterprise/server/utils/DigitalPublishingSuiteClient.class.php \
		Enterprise/server/wwtest/testsuite/HealthCheck2/Licenses_TestCase.php \
		plugins/release/Elvis/Elvis_WflLogOn.class.php \
		plugins/release/Elvis/Elvis_ContentSource.class.php \
	"
	for icFile in ${icFiles}; do
		ionCubeFile "${encodeOptionFile}" "${icFile}"
	done
	
	echo "Encode the license folder of Enterprise core."
	# Note that this step is separately done because it requires extra security options.
	# These options block non-encoded PHP files from directly including one of our license files.
	encodeOptionFile2="${SOURCE_BASE}encodeoptions2.txt"
	cp "${encodeOptionFile}" "${encodeOptionFile2}"
	echo "--include-if-property \"magic='the windmill keeps on turning'\"" >> "${encodeOptionFile2}"
	icFolder="Enterprise/server/utils/license/"
	ionCubeFolder "${encodeOptionFile2}" "${icFolder}"
	
	# Restore the debugger; Now the encoding is done.
	set -x

	# Remove the temporary encoding options files.
	rm -f "${encodeOptionFile}"
	rm -f "${encodeOptionFile2}"
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
	
	# Note that the config_overrule.php file is not tracked in Git to avoid merge conflicts.
	# Instead, it is added to the .gitignore file and here copied to the config folder.
	# This way we can provide an empty placeholder file, so admins don't have to create one.
	echo "step1j: Install empty placeholder for Enterprise administrators to overrule config options."
	cp "${WORKSPACE}/Enterprise/Build/config_overrule.php" "${WORKSPACE}/Enterprise/Enterprise/config"
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
		echo "${tmsLastUpdate}" > ${SOURCE_BASE}Enterprise/config/resources/_lastupdate.txt
		
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
	echo "step2b1: Retrieve timestamp of last update from TMS for AdobeDps2 plugin."
	tmsLastUpdate=`curl http://tms.woodwing.net/product/lastupdateversion/productname/Enterprise%20Server%20AdobeDps2/version/10.0/`
	if [ ! -n "${tmsLastUpdate}"  ]; then
		echo 'Could not retrieve last modification timestamp from TMS (for the AdobeDps2 project). Is TMS down?';
		exit 1;
	fi

	echo "step2b2: Retrieve timestamp of last update from local resource file for AdobeDps2 plugin."
	resLastUpdate=`cat "${SOURCE_BASE}plugins/release/AdobeDps2/resources/_lastupdate.txt"`
	if [ "${tmsLastUpdate}" == "${resLastUpdate}" ]; then
		echo "step2b3: Repository and TMS are in sync. No update needed."
	else
		echo "step2b3: Repository is out-of-sync with TMS. Downloading resources..."
		wget "http://tms.woodwing.net/product/getexport/user/woodwing/pass/QjQjI2VyVmxAQDE=/versionid/117" -O ./tms_resources/adobedps2.zip
		# L> update the versionid param when migrating to new AdobeDps2 major version: 99=9.0, 117=10.0

		echo "step2b4: Extract resource archive and overwrite local resources."
		cd ${SOURCE_BASE}plugins/release/AdobeDps2/resources
		7za e -y "${WORKSPACE}/tms_resources/adobedps2.zip" "Server/config/resources"
		cd -
		
		echo "step2b5: Prefix the resource keys with AdobeDps2."
		php "${WORKSPACE}/Enterprise/Build/replace_resource_keys.php" "${WORKSPACE}/Enterprise/plugins/release/AdobeDps2/resources" AdobeDps2

		echo "step2b6: Write timestamp of last update from TMS into the resource folder of AdobeDps2 plugin."
		echo "${tmsLastUpdate}" > ${SOURCE_BASE}plugins/release/AdobeDps2/resources/_lastupdate.txt
		
		echo "step2b7: Remove the timestamp from the downloaded XML files."
		for icFile in $(find "${SOURCE_BASE}plugins/release/AdobeDps2/resources/" -name '*.xml'); do
			sed '/<!--Last edit date in TMS:.*-->/d' "${icFile}" > ./temp && mv ./temp "${icFile}"
		done
		
		echo "step2b8: Commit changed resource files to repository."
		git add ${SOURCE_BASE}plugins/release/AdobeDps2/resources/*.xml
		git add --force ${SOURCE_BASE}plugins/release/AdobeDps2/resources/_lastupdate.txt
		git commit -m "[Ent Server ${SERVER_VERSION}] Jenkins: Updated latest (${tmsLastUpdate}) AdobeDps2 resource files from TMS for server build ${BUILD_NUMBER}."

		echo "step2b9: Push changed resource files to Git."
		git push --set-upstream origin "${GIT_BRANCH}"
	fi
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
		echo "${SERVER_RELEASE_TYPE}" > "${SOURCE_BASE}Enterprise/server/_productversionextra.txt"
	fi
	if [ "${SERVER_RELEASE_TYPE}" != "Daily" ]; then
		git add --force "${SOURCE_BASE}Enterprise/server/_productversionextra.txt"
	fi

	echo "step3a2: Update version info in server plugins."
	updatePluginVersions ${SOURCE_BASE}Enterprise/config/plugins ${SERVER_VERSION} ${BUILD_NUMBER}
	updatePluginVersions ${SOURCE_BASE}Enterprise/server/plugins ${SERVER_VERSION} ${BUILD_NUMBER}
	updatePluginVersions ${SOURCE_BASE}plugins/release ${SERVER_VERSION} ${BUILD_NUMBER}

	echo "step3a3: Update version info in AdobeDps2 and Elvis plugins. They have their own buildnr, but use the major.minor of Enterprise."
	twoDigitVersion=`echo "${SERVER_VERSION}" | sed -r "s/([0-9]+\.[0-9]+)(\.[0-9]+)?/\1/g"` # ignores patch nr
	updatePluginVersions ${SOURCE_BASE}plugins/release/AdobeDps2 "${twoDigitVersion}" ${ADOBEDPS2_BUILDNR}
	updatePluginVersions ${SOURCE_BASE}plugins/release/Elvis "${twoDigitVersion}" ${ELVIS_BUILDNR}

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
	cd "${WORKSPACE}/Enterprise/Enterprise/server/wwtest/"
	php testphpcodingcli.php "${WORKSPACE}/reports"
	cd -

	echo "step4b: Run phpStorm's code inspection on the server folder."
	# mkdir ./reports/phpstorm_strict
	# inspect.sh params: <project file path> <inspection profile path> <output path> -d <directory to be inspected>
	# see more info: http://www.jetbrains.com/phpstorm/webhelp/running-inspections-offline.html
	sh /opt/phpstorm/bin/inspect.sh "${WORKSPACE}/Enterprise" "${WORKSPACE}/Enterprise/.idea/inspectionProfiles/EnterpriseCodeInspection.xml" "${WORKSPACE}/reports/phpstorm_strict" -d "${WORKSPACE}/Enterprise/Enterprise"

	cd "${WORKSPACE}/Enterprise/Build/"
	echo "step4c: Convert folder with XML files (output of phpStorm's code inspection) to one JUnit XML file to display in UI of Jenkins."
	# phpstorm2junit params: <folder path with code inspector output> <output path for jUnit>
	php phpstorm2junit.php "\"${WORKSPACE}/reports/phpstorm_strict\"" "\"${WORKSPACE}/reports/TEST-PhpStormCodeInspection.xml\""
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
	rm -f -r ${TARGET_BASE}Enterprise/server/wwtest/testsuite/BuildTest/PhpCoding
	rm -f -r ${TARGET_BASE}Enterprise/server/wwtest/testsuite/BuildTest2
	sync

	echo 'step5c: Acquire license for ionCube Encoded to make sure it is still valid.'
	sudo "${iONCUBE_ENCODER}" --acquire-license
	"${iONCUBE_ENCODER}" -V
	# "${iONCUBE_ENCODER}" --help | more

	echo 'step5d: ionCube encode some files and folders in Enterprise Server release folder.'
	ionCubeEnterpriseFiles
}

#
# Archives the Enterprise Server in a ZIP file. The BuildTest and large sample data are put in separate ZIP files.
#
function step6_zipEnterpriseServer {
	echo "step6a: Zipping BuildTest ..."
	zipFolder "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testsuite" "BuildTest" "${WORKSPACE}/artifacts" "BuildTest1_${SERVER_VERSION_ZIP}"

	echo "step6b: Zipping large sample data..."
	zipFolder "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testdata" "largeSpeedTestData" "${WORKSPACE}/artifacts" "largeSpeedTestData_${SERVER_VERSION_ZIP}"

	echo "step6c: Excluding (removing) testsuite stuff that not needed for production..."
	rm -rf "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testsuite/BuildTest"
	rm -rf "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testsuite/BuildTest2"
	rm -rf "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testsuite/PhpCodingTest"
	rm -rf "${WORKSPACE}/Enterprise_release/Enterprise/server/wwtest/testdata/largeSpeedTestData"

	echo "step6d: Zipping Enterprise Server ..."
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
	zipFolder "${WORKSPACE}/Enterprise_release/plugins/release" "AdobeDps2" "${WORKSPACE}/artifacts" "AdobeDPS_Build_${ADOBEDPS2_BUILDNR}_for_Enterprise_${twoDigitVersion}.zip"
	zipFolder "${WORKSPACE}/Enterprise_release/plugins/release" "Elvis" "${WORKSPACE}/artifacts" "Elvis_Build_${ELVIS_BUILDNR}_for_Enterprise_${twoDigitVersion}.zip"
	plugins="Facebook Twitter WordPress Drupal8"
	for plugin in ${plugins}; do
		# For Drupal 8 we want to modify the name to indicate this is the plugin (and not the module)
		if [ "${plugin}" == "Drupal8" ]; then
			zipFolder "${WORKSPACE}/Enterprise_release/plugins/release" "${plugin}" "${WORKSPACE}/artifacts" "Drupal8_Enterprise_Plugin_${SERVER_VERSION_ZIP}"
		else
			zipFolder "${WORKSPACE}/Enterprise_release/plugins/release" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"	
		fi
	done
	
	echo "step7c: Zipping demo plug-ins ..."
	plugins="Celum Claro EZPublish FlickrPublish FlickrSearch Fotoware Guardian Imprezzeo NYTimes QRCode SMS Tripolis YouTubePublish AspellShellSpelling GoogleWebSpelling Tika"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE}/Enterprise_release/plugins/demo" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7d: Zipping examples plug-ins ..."
	plugins="CopyrightValidationDemo SimpleFileSystem CustomObjectPropsDemo CustomAdminPropsDemo MultiChannelPublishingSample AddSubApplication StandaloneAutocompleteSample"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE}/Enterprise_release/plugins/examples" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7e: Zipping buildtest plug-ins ..."
	plugins="PublishingTest AutoTargetingTest AutoNamingTest"
	for plugin in ${plugins}; do
		zipFolder "${WORKSPACE}/Enterprise_release/plugins/buildtest" "${plugin}" "${WORKSPACE}/artifacts" "${plugin}_${SERVER_VERSION_ZIP}"
	done

	echo "step7f: Copying the ionCube Loaders to the artifacts folder ..."
	cp "${WORKSPACE}/Enterprise/Libraries/ionCube/loaders/v5.0.14_at_2015_07_29/ioncube_loaders_all_platforms.zip" "${WORKSPACE}/artifacts"
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
	cp "${WORKSPACE}/Enterprise/Build/build_test2_macosx.sh" "${WORKSPACE}/internals"
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
