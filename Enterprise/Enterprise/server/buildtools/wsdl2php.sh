#!/bin/sh
# @package      Enterprise
# @subpackage   Build
# @since        7.6
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# This bash shell script does the following:
# 1. validate manual changes applied to any of the supported WSDL files against 3rd party SOAP parser (Java)
# 2. create a clean workspace in Git by stashing all your current changes
# 3. re-generate PHP and Java files (data classes, service classes, reponses, requests, etc) from WSDL file
# 4. add all changed files to the staged area in Git
# 5. restore your workspace by applying the previously made stash over it
#
# After running the script, the changed files can be found as Staged files in Sourcetree (or run `git diff --name-only --cached` on the command line.
# Those changes needs to be reviewed (check for unexpected changes) and committed manually.
#
# This script uses the Git command line client, you should not have to change any settings, provided this project was
# retrieved from the repository.
# Overview of commands: https://git-scm.com/docs

ENT_DIR=../..
ENT_INTF=$1

echo ------------------------------------------------
echo "[Step#1] Validating parameters..."
if [ "${ENT_INTF}" = "" ]; then
	echo "Usage: $0 <interface>"
	echo "Supported interfaces: adm, sys, ads, dat, pln, pub, wfl"
	exit 1
elif [ "${ENT_INTF}" = "adm" ]; then
	ENT_WSDL="SmartConnectionAdmin.wsdl"
	ENT_INTF_CAMEL="Adm"
	ENT_WSDL_DOC="Admin.htm"
	ENT_ENTRY="adminindex.php"
	ENT_SERVICE_LOCATOR="SmartConnectionAdminServiceLocator.java"
elif [ "${ENT_INTF}" = "sys" ]; then
	ENT_WSDL="SystemAdmin.wsdl"
	ENT_INTF_CAMEL="Sys"
	ENT_WSDL_DOC="SysAdmin.htm"
	ENT_ENTRY="sysadminindex.php"
	ENT_SERVICE_LOCATOR="SmartConnectionSysAdminServiceLocator.java"
elif [ "${ENT_INTF}" = "ads" ]; then
	ENT_WSDL="PlutusAdmin.wsdl"
	ENT_INTF_CAMEL="Ads"
	ENT_WSDL_DOC="AdmDatSrc.htm"
	ENT_ENTRY="datasourceadminindex.php"
	ENT_SERVICE_LOCATOR="PlutusAdminServiceLocator.java"
elif [ "${ENT_INTF}" = "dat" ]; then
	ENT_WSDL="PlutusDataSource.wsdl"
	ENT_INTF_CAMEL="Dat"
	ENT_WSDL_DOC="DataSource.htm"
	ENT_ENTRY="datasourceindex.php"
	ENT_SERVICE_LOCATOR="PlutusDatasourceServiceLocator.java"
elif [ "${ENT_INTF}" = "pln" ]; then
	ENT_WSDL="SmartEditorialPlan.wsdl"
	ENT_INTF_CAMEL="Pln"
	ENT_WSDL_DOC="Planning.htm"
	ENT_ENTRY="editorialplan.php"
	ENT_SERVICE_LOCATOR="SmartEditorialPlanServiceLocator.java"
elif [ "${ENT_INTF}" = "pub" ]; then
	ENT_WSDL="EnterprisePublishing.wsdl"
	ENT_INTF_CAMEL="Pub"
	ENT_WSDL_DOC="Publishing.htm"
	ENT_ENTRY="publishindex.php"
	ENT_SERVICE_LOCATOR="EnterprisePublishingServiceLocator.java"
elif [ "${ENT_INTF}" = "wfl" ]; then
	ENT_WSDL="SCEnterprise.wsdl"
	ENT_INTF_CAMEL="Wfl"
	ENT_WSDL_DOC="Workflow.htm"
	ENT_ENTRY="index.php"
	ENT_SERVICE_LOCATOR="SmartConnectionServiceLocator.java"
else
	echo "Unknown interface: $1"
	exit 1
fi

ENT_HTTP_ROOT=`php -r "require_once '${ENT_DIR}/config/config.php'; print LOCALURL_ROOT.INETROOT;"`
ENT_HTTP_ENTRY="${ENT_HTTP_ROOT}/${ENT_ENTRY}"
ENT_HTTP_WSDL="${ENT_HTTP_ENTRY}?wsdl=ws-i"

echo "Working folder: ${ENT_DIR}"
echo "Interface file: ${ENT_HTTP_WSDL}"

echo ------------------------------------------------
echo "[Step#2] Generating Java classes from ${ENT_WSDL} file..."
OUT_FOLDER="${ENT_DIR}/sdk/java/src/com/woodwing/enterprise/interfaces/services/${ENT_INTF}"
OUT_CLASS="com.woodwing.enterprise.interfaces.services.${ENT_INTF}"
test -d "${OUT_FOLDER}" && rm -R "${OUT_FOLDER}"
java -cp './wsdl2java/axis-1_4/*:./wsdl2java/javamail-1.4.5/mail.jar' org.apache.axis.wsdl.WSDL2Java -u -W -p "${OUT_CLASS}" "${ENT_HTTP_WSDL}" -o "${ENT_DIR}/sdk/java/src"
if [ $? -ne 0 ]; then
	echo "ERROR: The ${ENT_WSDL} file is not valid. Please fix and try again."
	exit 1
fi
# Because we asked Enterprise Server to return WSDL through HTTP, it replaces the entry point for us.
# The Java classes are generated on that and the entry point is stored in the service locator.
# However, that is an temporary URL and so we replace it again, now with something more generic.
ENT_HTTP_OLD_ENTRY=`php -r "print str_replace('/', '\/', '${ENT_HTTP_ENTRY}');"`
ENT_HTTP_NEW_ENTRY=`php -r "print str_replace('/', '\/', 'http://127.0.0.1/Enterprise/${ENT_ENTRY}');"`
sed -i "" -e "s/${ENT_HTTP_OLD_ENTRY}/${ENT_HTTP_NEW_ENTRY}/g" "${OUT_FOLDER}/${ENT_SERVICE_LOCATOR}"

echo "WSDL file is valid."

echo ------------------------------------------------
echo "[Step#3] Generating PHP classes locally..."
php genservices/wsdl2phpcli.php "${ENT_INTF}"
if [ $? -ne 0 ]; then
	echo "The following command has failed: php genservices/wsdl2phpcli.php ${ENT_INTF}"
	echo "Please fix the problem and try again."
	exit 1
fi

echo ------------------------------------------------
echo "[Step#4] Adding all changed files to the stage area..."
git add "${ENT_DIR}/server/interfaces/services/${ENT_INTF}/"*
git add "${ENT_DIR}/sdk/"*
git add "${ENT_DIR}/server/protocols/"*
git add "${ENT_DIR}/server/services/${ENT_INTF}/"*


echo ------------------------------------------------
echo "All done!"
echo "In SourceTree, review all staged files before committing."
