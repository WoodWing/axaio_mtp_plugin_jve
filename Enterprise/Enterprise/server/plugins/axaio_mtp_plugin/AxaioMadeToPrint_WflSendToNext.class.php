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

require_once BASEDIR . '/server/interfaces/services/wfl/WflSendToNext_EnterpriseConnector.class.php';

class AxaioMadeToPrint_WflSendToNext extends WflSendToNext_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflSendToNextRequest &$req )
	{
		$req = $req; // keep code analyzer happy
		
        //NOTE: do nothing. WflSetObjectProperties will be called
	} 

	final public function runAfter( WflSendToNextRequest $req, WflSendToNextResponse &$resp )
	{
		$req = $req; $resp = $resp; // keep code analyzer happy

        //NOTE: do nothing. WflSetObjectProperties will be called
	} 
	
	// Not called.
	final public function runOverruled( WflSendToNextRequest $req )
	{
		$req = $req; // keep code analyzer happy

        //NOTE: do nothing. WflSetObjectProperties will be called
	} 
}
