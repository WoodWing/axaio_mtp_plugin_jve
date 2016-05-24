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

require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectProperties_EnterpriseConnector.class.php';

class Claro_WflSetObjectProperties extends WflSetObjectProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflSetObjectPropertiesRequest &$req )
	{
		$req=$req;
	}
	
	final public function runAfter( WflSetObjectPropertiesRequest $req, WflSetObjectPropertiesResponse &$resp )
	{
		// make analyzer happy
		$resp = $resp;

		try {
			require_once dirname(__FILE__) . '/Claro.class.php';
			$claro = new Claro( $req->Ticket, $req->ID, $req->MetaData->BasicMetaData->Publication, $req->MetaData->BasicMetaData->Type, $req->MetaData->WorkflowMetaData->State );
		} catch ( BizException $e ) {
			return;
		}
		
		$claro->addObject();
	}
	
	final public function runOverruled( WflSetObjectPropertiesRequest $req )
	{
		// make analyzer happy
		$req = $req;
	}
}
