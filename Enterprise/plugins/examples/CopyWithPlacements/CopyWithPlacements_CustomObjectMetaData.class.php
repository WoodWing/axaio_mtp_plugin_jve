<?php
/****************************************************************************
   Copyright 2015 WoodWing Software BV

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

require_once BASEDIR . '/server/interfaces/plugins/connectors/CustomObjectMetaData_EnterpriseConnector.class.php';

class CopyWithPlacements_CustomObjectMetaData extends CustomObjectMetaData_EnterpriseConnector
{
	/**
	 * See introduction at module header above.
	 */
	public function collectCustomProperties( $coreInstallation )
	{
		$props = array();
		$props[] = new PropertyInfo( 'C_CWP_DEEPCOPY',		'Copy with placements',	'',		'bool',		'1' 	);

		$retVal= array();
		$retVal[0][0] = $props;
		return $retVal;
	}

	public function getPrio() { return self::PRIO_DEFAULT; }

}
