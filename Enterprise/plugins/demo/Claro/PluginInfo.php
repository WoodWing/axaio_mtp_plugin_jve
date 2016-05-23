<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class Claro_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Claro';
		$info->Version     = "v8.0 20100823"; // don't use PRODUCTVERSION
		$info->Description = 'Integrates Elpical Claro for Image Enhancement.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces() 
	{ 
		return array( 'WflSetObjectProperties_EnterpriseConnector' ); 
	}
	
	public function isInstalled()
	{
		$installed = false;
		$dbDriver	= DBDriverFactory::gen();
		$dbClaro 	= $dbDriver->tablename("claro");
		$sql = "select count(1) as `c` from $dbClaro";
		$sth = $dbDriver->query($sql);
		if( $sth ) {
			$installed = true;
		}
		return $installed;
	}
	
	public function runInstallation()
	{
		if( !$this->isInstalled() ) {
			$dbDriver	= DBDriverFactory::gen();
			$sqlScript = dirname(__FILE__) . '/claro.' . DBTYPE . '.sql';
			$sqlTxt = file_get_contents($sqlScript);
			$sqlStatements = explode(';', $sqlTxt);
			array_pop($sqlStatements); // remove the last empty element (after the ;)

			foreach ($sqlStatements as $sqlStatement) {
				$dbDriver->query(trim($sqlStatement));
			}
		}
	}
}