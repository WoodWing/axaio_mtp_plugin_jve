<?php
/****************************************************************************
   Copyright 2008-2010 WoodWing Software BV

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

/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR . '/server/interfaces/plugins/PluginInfoData.class.php';

class ElvisSearch_EnterprisePlugin extends EnterprisePlugin
{

	public function getPluginInfo ()
	{
		$info = new PluginInfoData( );
		$info->DisplayName = 'Elvis Content Source';
		$info->Version = 'v8.0 20100823'; // don't use PRODUCTVERSION
		$info->Description = 'Elvis Content Source';
		$info->Copyright = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces ()
	{
		return array('ContentSource_EnterpriseConnector');
	}

	public function isInstalled ()
	{
		$isInstalled = false;
		
		require_once dirname( __FILE__ ) . '/config.php';
		if (defined( 'ELVIS_URL' ) && defined( 'ELVIS_USERNAME' ) && defined( 'ELVIS_PASSWORD' )) {
			// check values
			if (preg_match( '|^https?://|', ELVIS_URL ) === 1 && strlen( ELVIS_USERNAME ) > 0 && strlen( 
				ELVIS_PASSWORD ) > 0) {
				$isInstalled = true;
			}
		}
		return $isInstalled;
	}

	public function runInstallation ()
	{
		if (! $this->isInstalled()) {
			$msg = 'Configuration of this plug-in is not done or not correct in "' . dirname( __FILE__ ) . '/config.php' .
				 '"';
			throw new BizException( '', 'Server', null, $msg );
		}
	}
}