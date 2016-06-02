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

require_once BASEDIR . '/server/interfaces/services/wfl/WflLogOff_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class SmartProxy_WflLogOff extends WflLogOff_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_AFTER; }

	final public function runAfter (WflLogOffRequest $req, WflLogOffResponse &$resp) 
	{
		$localticket = $req->Ticket;
		
		$dbh = DBDriverFactory::gen();
		$sql = "select `remoteticket` from `smart_ticketmapping` where `localticket` = '$localticket'";
		$sth1 = $dbh->query($sql);
		$row = $dbh->fetch($sth1);	
		$remoteticket = $row['remoteticket'];		
	
		require_once BASEDIR.'/server/protocols/soap/WflClient.php';
		$options = array();
		$options['location'] = REMOTE_SERVER_URL;

		$workflowClient = new WW_SOAP_WflClient($options);
	
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffResponse.class.php';	
		
		$req->Ticket = $remoteticket;
		$resp2 = $workflowClient->LogOff( $req );
	} 
	
	final public function runBefore (WflLogOffRequest &$req)	{$req=$req;} 
	final public function runOverruled (WflLogOffRequest $req) 	{$req=$req;} // Not called because we're just doing run before
}
