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




require_once BASEDIR . '/server/interfaces/services/wfl/WflSaveObjects_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class AlertLayoutUpdate_WflSaveObjects extends WflSaveObjects_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_BEFOREAFTER; }

	final public function runAfter (WflSaveObjectsRequest $req, WflSaveObjectsResponse &$resp) 
	{
		LogHandler::Log("AlertLayoutUpdate","INFO","AlertLayoutUpdate SaveObjects runAfter");
		
		$dbdriver = DBDriverFactory::gen();
		require_once BASEDIR.'/config/plugins/WWCommon/SCECommonFunction.class.php';
		$scecommonfunction = new SCECommonFunction();

		$object1 = $req->Objects[0];
		if ($object1->MetaData->BasicMetaData->Type == 'Layout')
		{
			foreach($object1->Relations as $relation)
			{
				$child = $relation->Child;
				$sql = "SELECT `lockoffline` FROM `smart_objectlocks` where `object` = $child";
				$sth = $dbdriver->query($sql);
				$row = $dbdriver->fetch($sth);
				if (current($row) == 'on')
				{
					$arrMessages = array();
					$msg = 'Layout has changed during offline period, please update geometry';
					$scecommonfunction->sendmessage($req->Ticket,$child,$msg);
				}
			}
		}
	} 
	
	final public function runBefore (WflSaveObjectsRequest &$req)	
	{
		LogHandler::Log("AlertLayoutUpdate","INFO","AlertLayoutUpdate SaveObjects runBefore");
	} 
	
	final public function runOverruled (WflSaveObjectsRequest $req) 	
	{
	} 
}
