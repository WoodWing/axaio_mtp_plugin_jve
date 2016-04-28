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

require_once BASEDIR . '/server/interfaces/services/wfl/WflSendTo_EnterpriseConnector.class.php';

class AxaioMadeToPrint_WflSendTo extends WflSendTo_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	final public function runBefore( WflSendToRequest &$req )
	{
		LogHandler::Log( 'AxaioMadeToPrint', 'DEBUG', 'Called: AxaioMadeToPrint_WflSendTo->runBefore()' );
		require_once dirname(__FILE__) . '/config.php';
		$req = $req; // keep code analyzer happy
		
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_AFTER with RUNMODE_AFTER when this hook is not needed.

		LogHandler::Log( 'AxaioMadeToPrint', 'DEBUG', 'Returns: AxaioMadeToPrint_WflSendTo->runBefore()' );
	} 

	final public function runAfter( WflSendToRequest $req, WflSendToResponse &$resp )
	{
		LogHandler::Log( 'AxaioMadeToPrint', 'DEBUG', 'Called: AxaioMadeToPrint_WflSendTo->runAfter()' );
		require_once dirname(__FILE__) . '/config.php';
		$req = $req; $resp = $resp; // keep code analyzer happy

		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_AFTER with RUNMODE_BEFORE when this hook is not needed.

		LogHandler::Log( 'AxaioMadeToPrint', 'DEBUG', 'Returns: AxaioMadeToPrint_WflSendTo->runAfter()' );
	} 
	
	// Not called.
	final public function runOverruled( WflSendToRequest $req )
	{
		$req = $req; // keep code analyzer happy
	} 
}
