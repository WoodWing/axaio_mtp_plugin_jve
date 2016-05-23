#!/bin/sh
# @package      Enterprise
# @subpackage   Build
# @since        7.6
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# This bash shell script does the following:
# 1. validate manual changes applied to any of the supported WSDL files against 3rd party SOAP parser (Java)
# 2. sync to latest and check out PHP files at Perforce that can be re-generated due to WSDL changes
# 3. re-generate PHP files (data classes, service classes, reponses, requests, etc) from WSDL file
# 4. revert re-generated PHP files at Perforce that turn out not to be changed compared to last version
#
# After running the script, the changed files can be found at the default Pending Changelist at Perforce.
# Those changes needs to be reviewed (check for unexpected changes) and submit manually.
#
# This script uses the Perforce client command line utility (p4), for which some settings must set correctly.
# Settings are P4CLIENT, P4USER and P4PORT which can be viewed by running the 'p4 set' command at the Terminal.
# For WW HeadQuarter, the port needs to be set as follows:
#    P4PORT=perforce.woodwing.net:1666
# For WW APAC:
#    P4PORT=172.20.22.27:1666
# The Perforce Command-Line Client (P4) can be downloaded from here: http://www.perforce.com/downloads/complete_list
# Overview of commands: http://www.perforce.com/perforce/doc.current/manuals/cmdref/index.html
# Under Windows, you can change the settings like this:
#    p4 set P4PORT=perforce.woodwing.net:1666
# Under Mac OSX, edit the ~/.profile or ~/.bash_profile files to make settings like this:
#    export P4PORT=perforce.woodwing.net:1666

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
echo "[Step#2] Getting latest version of Enterprise Server from Perforce..."
p4 sync "${ENT_DIR}/...#head"
if [ $? -ne 0 ]; then
	echo "Perforce sync command failed. Please check settings and try again."
	echo "Used settings: P4CLIENT=${P4CLIENT}, P4USER=${P4USER}, P4PORT=${P4PORT}"
	exit 1
fi
echo "Got latest."

echo ------------------------------------------------
echo "[Step#3] Checking out Java classes (to be re-generated) from WSDL..."
p4 edit "${ENT_DIR}/sdk/java/src/com/woodwing/enterprise/interfaces/services/${ENT_INTF}/..."

echo ------------------------------------------------
echo "[Step#4] Generating Java classes from ${ENT_WSDL} file..."
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
echo "[Step#5] Checking out PHP classes (to be re-generated) from Perforce..."
p4 edit "${ENT_DIR}/server/interfaces/services/${ENT_INTF}/..."
p4 edit "${ENT_DIR}/server/services/${ENT_INTF}/..."
p4 edit "${ENT_DIR}/server/protocols/soap/${ENT_INTF_CAMEL}Client.php"
p4 edit "${ENT_DIR}/server/protocols/soap/${ENT_INTF_CAMEL}Server.php"
p4 edit "${ENT_DIR}/server/protocols/soap/${ENT_INTF_CAMEL}Services.php"
p4 edit "${ENT_DIR}/server/protocols/amf/${ENT_INTF_CAMEL}Services.php"
p4 edit "${ENT_DIR}/server/protocols/amf/${ENT_INTF_CAMEL}DataTypeMap.php"
p4 edit "${ENT_DIR}/server/protocols/amf/${ENT_INTF_CAMEL}RequestTypeMap.php"
p4 edit "${ENT_DIR}/server/protocols/json/${ENT_INTF_CAMEL}Services.php"
p4 edit "${ENT_DIR}/sdk/doc/interfaces/${ENT_WSDL_DOC}"
p4 edit "${ENT_DIR}/sdk/flex/src/com/woodwing/enterprise/interfaces/services/${ENT_INTF}/..."
p4 edit "${ENT_DIR}/sdk/flex/src/com/woodwing/enterprise/interfaces/services/WoodWingUtils.as"

# The following is needed when PHP is run from HTTP... but that is not the case here.
#echo "Perforce takes the current 'user' (you) to apply access rights. But, generating files requires write access for the www user."
#echo "Therefore taking over write access rights from 'user' account to 'group' and 'other' accounts..."
#chmod go=u "${ENT_DIR}/server/interfaces/services/${ENT_INTF}/"*
#chmod go=u "${ENT_DIR}/server/services/${ENT_INTF}/"*
# allow 'group' and 'other' to have write access in folders (to create/generate new files)
#chmod go+w "${ENT_DIR}/server/interfaces/services/${ENT_INTF}/."
#chmod go+w "${ENT_DIR}/server/services/${ENT_INTF}/."

echo ------------------------------------------------
echo "[Step#6] Generating PHP classes locally..."
php genservices/wsdl2phpcli.php "${ENT_INTF}"
if [ $? -ne 0 ]; then
	echo "The following command has failed: php genservices/wsdl2phpcli.php ${ENT_INTF}"
	echo "Please fix the problem and try again."
	exit 1
fi

echo ------------------------------------------------
echo "[Step#7] Adding new PHP classes at Perforce..."
p4 add -f "${ENT_DIR}/server/interfaces/services/${ENT_INTF}/"*.php
p4 add -f "${ENT_DIR}/server/services/${ENT_INTF}/"*.php
p4 add -f "${ENT_DIR}/sdk/flex/src/com/woodwing/enterprise/interfaces/services/${ENT_INTF}/"*.as
p4 add -f "${ENT_DIR}/sdk/flex/src/com/woodwing/enterprise/interfaces/services/${ENT_INTF}/dataclasses/"*.as
p4 add -f "${ENT_DIR}/sdk/java/src/com/woodwing/enterprise/interfaces/services/${ENT_INTF}/"*.java

echo ------------------------------------------------
echo "[Step#8] Reverting unchanged PHP classes at Perforce..."
p4 revert -a "${ENT_DIR}/server/interfaces/services/${ENT_INTF}/..."
p4 revert -a "${ENT_DIR}/server/services/${ENT_INTF}/..."
p4 revert -a "${ENT_DIR}/server/protocols/soap/${ENT_INTF_CAMEL}Client.php"
p4 revert -a "${ENT_DIR}/server/protocols/soap/${ENT_INTF_CAMEL}Server.php"
p4 revert -a "${ENT_DIR}/server/protocols/soap/${ENT_INTF_CAMEL}Services.php"
p4 revert -a "${ENT_DIR}/server/protocols/amf/${ENT_INTF_CAMEL}Services.php"
p4 revert -a "${ENT_DIR}/server/protocols/json/${ENT_INTF_CAMEL}Services.php"
p4 revert -a "${ENT_DIR}/server/protocols/amf/${ENT_INTF_CAMEL}DataTypeMap.php"
p4 revert -a "${ENT_DIR}/server/protocols/amf/${ENT_INTF_CAMEL}RequestTypeMap.php"
p4 revert -a "${ENT_DIR}/sdk/doc/interfaces/${ENT_WSDL_DOC}"
p4 revert -a "${ENT_DIR}/sdk/flex/src/com/woodwing/enterprise/interfaces/services/${ENT_INTF}/..."
p4 revert -a "${ENT_DIR}/sdk/flex/src/com/woodwing/enterprise/interfaces/services/WoodWingUtils.as"
p4 revert -a "${ENT_DIR}/sdk/java/src/com/woodwing/enterprise/interfaces/services/${ENT_INTF}/..."

echo ------------------------------------------------
echo "All done!"
echo "At Perforce, refresh your default Pending Changelist and review re-generated files before submit."
