<?php
/****************************************************************************
   Copyright 2014 WoodWing Software BV

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

require_once BASEDIR . '/server/interfaces/services/wfl/WflCopyObject_EnterpriseConnector.class.php';

class AxaioMadeToPrint_WflCopyObject extends WflCopyObject_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflCopyObjectRequest &$req )
	{
		$req = $req; // keep code analyzer happy
		
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_AFTER with RUNMODE_AFTER when this hook is not needed.

	} 

	final public function runAfter( WflCopyObjectRequest $req, WflCopyObjectResponse &$resp )
	{
		$req = $req; $resp = $resp; // keep code analyzer happy

        require_once dirname(__FILE__) . '/config.php';
		require_once dirname(__FILE__) . '/AxaioMadeToPrintDispatcher.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';

		$ticket = BizSession::getTicket();
		
		AxaioMadeToPrintDispatcher::doPrint( $resp->MetaData->BasicMetaData->ID, $ticket );

	} 
	
	// Not called.
	final public function runOverruled( WflCopyObjectRequest $req )
	{
		$req = $req; // keep code analyzer happy
	} 
}
