ENT_INTF=$1

if [ "${ENT_INTF}" = "" ]; then
	sh ./sce2java.sh "adm"
	sh ./sce2java.sh "sys"
	sh ./sce2java.sh "ads"
	sh ./sce2java.sh "dat"
	sh ./sce2java.sh "pln"
	sh ./sce2java.sh "pub"
	sh ./sce2java.sh "wfl"
	echo "Completed"
	exit 0
elif [ "${ENT_INTF}" = "adm" ]; then
	ENT_WSDL="SmartConnectionAdmin.wsdl"
	ENT_ENTRY="adminindex.php"
	ENT_SERVICE_LOCATOR="SmartConnectionAdminServiceLocator.java"
elif [ "${ENT_INTF}" = "sys" ]; then
	ENT_WSDL="SystemAdmin.wsdl"
	ENT_ENTRY="sysadminindex.php"
	ENT_SERVICE_LOCATOR="SmartConnectionSysAdminServiceLocator.java"
elif [ "${ENT_INTF}" = "ads" ]; then
	ENT_WSDL="PlutusAdmin.wsdl"
	ENT_ENTRY="datasourceadminindex.php"
	ENT_SERVICE_LOCATOR="PlutusAdminServiceLocator.java"
elif [ "${ENT_INTF}" = "dat" ]; then
	ENT_WSDL="PlutusDataSource.wsdl"
	ENT_ENTRY="datasourceindex.php"
	ENT_SERVICE_LOCATOR="PlutusDatasourceServiceLocator.java"
elif [ "${ENT_INTF}" = "pln" ]; then
	ENT_WSDL="SmartEditorialPlan.wsdl"
	ENT_ENTRY="editorialplan.php"
	ENT_SERVICE_LOCATOR="SmartEditorialPlanServiceLocator.java"
elif [ "${ENT_INTF}" = "pub" ]; then
	ENT_WSDL="EnterprisePublishing.wsdl"
	ENT_ENTRY="publishindex.php"
	ENT_SERVICE_LOCATOR="EnterprisePublishingServiceLocator.java"
elif [ "${ENT_INTF}" = "wfl" ]; then
	ENT_WSDL="SCEnterprise.wsdl"
	ENT_ENTRY="index.php"
	ENT_SERVICE_LOCATOR="SmartConnectionServiceLocator.java"
else
	echo "Unknown interface: $1"
	exit 1
fi

ENT_DIR=../../..
ENT_HTTP_ROOT=`php -r "require_once '${ENT_DIR}/config/config.php'; print LOCALURL_ROOT.INETROOT;"`
ENT_HTTP_ENTRY="${ENT_HTTP_ROOT}/${ENT_ENTRY}"
ENT_HTTP_WSDL="${ENT_HTTP_ENTRY}?wsdl=ws-i"
OUT_FOLDER="com/woodwing/enterprise/interfaces/services/${ENT_INTF}"
OUT_CLASS="com.woodwing.enterprise.interfaces.services.${ENT_INTF}"

echo "Generating Java classes for ${ENT_WSDL}"
test -d "${OUT_FOLDER}" && rm -R "${OUT_FOLDER}"
java -cp './axis-1_4/*:./javamail-1.4.5/mail.jar' org.apache.axis.wsdl.WSDL2Java -u -W -p "${OUT_CLASS}" "${ENT_HTTP_WSDL}" -o "out"

# Replace our temporary entry point (service locator) with something generic.
ENT_HTTP_OLD_ENTRY=`php -r "print str_replace('/', '\/', '${ENT_HTTP_ENTRY}');"`
ENT_HTTP_NEW_ENTRY=`php -r "print str_replace('/', '\/', 'http://127.0.0.1/Enterprise/${ENT_ENTRY}');"`
sed -i "" -e "s/${ENT_HTTP_OLD_ENTRY}/${ENT_HTTP_NEW_ENTRY}/g" "out/${OUT_FOLDER}/${ENT_SERVICE_LOCATOR}"
