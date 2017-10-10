<?php
/****************************************************************************
   Copyright %year% WoodWing Software BV

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

require_once BASEDIR . '/server/interfaces/services/%interface%/%service%_EnterpriseConnector.class.php';

class %plugin%_%service% extends %service%_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( %service%Request &$req )
	{
		LogHandler::Log( '%plugin%', 'DEBUG', 'Called: %plugin%_%service%->runBefore()' );
		require_once dirname(__FILE__) . '/config.php';

		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_AFTER when this hook is not needed.

		LogHandler::Log( '%plugin%', 'DEBUG', 'Returns: %plugin%_%service%->runBefore()' );
	} 

	final public function runAfter( %service%Request $req, %service%Response &$resp )
	{
		LogHandler::Log( '%plugin%', 'DEBUG', 'Called: %plugin%_%service%->runAfter()' );
		require_once dirname(__FILE__) . '/config.php';

		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_BEFORE when this hook is not needed.

		LogHandler::Log( '%plugin%', 'DEBUG', 'Returns: %plugin%_%service%->runAfter()' );
	} 
	
	final public function onError( %service%Request $req, BizException $e )
	{
		LogHandler::Log( '%plugin%', 'DEBUG', 'Called: %plugin%_%service%->onError()' );
		require_once dirname(__FILE__) . '/config.php';

		LogHandler::Log( '%plugin%', 'DEBUG', 'Returns: %plugin%_%service%->onError()' );
	} 
	
	// Not called.
	final public function runOverruled( %service%Request $req )
	{
	}
}
