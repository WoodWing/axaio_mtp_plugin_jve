<?php
/****************************************************************************
   Copyright 2013 WoodWing Software BV

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

require_once BASEDIR . '/server/interfaces/services/sys/SysGetSubApplications_EnterpriseConnector.class.php';

class AddSubApplication_SysGetSubApplications extends SysGetSubApplications_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called.
	final public function runBefore( SysGetSubApplicationsRequest &$req )
	{
		$req = $req; // keep code analyzer happy
	} 

	final public function runAfter( SysGetSubApplicationsRequest $req, SysGetSubApplicationsResponse &$resp )
	{
		if( is_null($req->ClientAppName) || // request for all clients?
			$req->ClientAppName == 'FooClient' ) { // request for this client only?

			require_once BASEDIR.'/server/interfaces/services/sys/DataClasses.php';
			$subApp = new SysSubApplication();
			$subApp->ID = 'FooClient_FooSubApp';
			$subApp->Version = '1.0.0 Build 1';
			$subApp->PackageUrl = 'http://foosubapp.com';
			$subApp->DisplayName = 'Foo Sub App';
			$subApp->ClientAppName = 'FooClient';
			$resp->SubApplications[] = $subApp;
		}
	} 
	
	// Not called.
	final public function runOverruled( SysGetSubApplicationsRequest $req )
	{
		$req = $req; // keep code analyzer happy
	} 
}
