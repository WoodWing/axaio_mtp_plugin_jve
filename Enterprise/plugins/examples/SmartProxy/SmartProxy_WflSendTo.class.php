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

require_once BASEDIR . '/server/interfaces/services/wfl/WflSendTo_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class SmartProxy_WflSendTo extends WflSendTo_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_OVERRULE; }

	final public function runAfter (WflSendToRequest $req, WflSendToResponse &$resp) 
	{
	} 
	
	final public function runBefore (WflSendToRequest &$req)	
	{
	} 
	
	final public function runOverruled (WflSendToRequest $req) 	
	{
		SmartProxy::prepare_request($req);
		$resp = SmartProxy::client()->SendTo($req);		
		$resp = SmartProxy::finish_request($resp);
		return $resp;
	} 
}