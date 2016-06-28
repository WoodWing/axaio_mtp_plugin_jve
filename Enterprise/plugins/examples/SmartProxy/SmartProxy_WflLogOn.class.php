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

require_once BASEDIR . '/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class SmartProxy_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_AFTER; }

	final public function runAfter (WflLogOnRequest $req, WflLogOnResponse &$resp) 
	{	
		if ($req->ClientAppName == 'Web')
	   		return;
	    
		$localticket = $resp->Ticket;
	
		require_once BASEDIR.'/server/protocols/soap/WflClient.php';
		$options = array();
		$options['location'] = REMOTE_SERVER_URL;

		$workflowClient = new WW_SOAP_WflClient($options);
	
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnResponse.class.php';	
		
		$resp2 = $workflowClient->LogOn( $req );
		
		
		$remoteticket = $resp2->Ticket;
		
		// save ticket in DB
		$dbh = DBDriverFactory::gen();
		$sql = "insert into `smart_ticketmapping` (`localticket`,`remoteticket`) values ('$localticket','$remoteticket')";
		$sth1 = $dbh->query($sql);
		$row = $dbh->fetch($sth1);		

		$resp = $resp2; 
		$resp->Ticket = $localticket;
		
	}
	
	final public function runBefore (WflLogOnRequest &$req)	{$req=$req;} 
	final public function runOverruled (WflLogOnRequest $req) 	{$req=$req;} // Not called because we're just doing run before
}
