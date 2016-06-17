echo off
echo ------------------------------------------------
echo SCEnterprise (workflow interface)...
wsdl.exe ..\..\interfaces\SCEnterprise.wsdl

echo ------------------------------------------------
echo SmartConnectionAdmin (administration interface)...
wsdl.exe ..\..\interfaces\SmartConnectionAdmin.wsdl

echo ------------------------------------------------
echo SystemAdmin (system administration interface)...
wsdl.exe ..\..\interfaces\SystemAdmin.wsdl

echo ------------------------------------------------
echo SmartEditorialPlan (planning interface)...
wsdl.exe ..\..\interfaces\SmartEditorialPlan.wsdl

echo ------------------------------------------------
echo PlutusAdmin (Smart Catalog - administration interface)...
wsdl.exe ..\..\interfaces\PlutusAdmin.wsdl

echo ------------------------------------------------
echo PlutusDataSource (Smart Catalog - data source interface)...
wsdl.exe ..\..\interfaces\PlutusDataSource.wsdl

echo ------------------------------------------------
echo Enterprise Publishing ...
wsdl.exe ..\..\interfaces\EnterprisePublishing.wsdl

echo ------------------------------------------------
echo Completed!