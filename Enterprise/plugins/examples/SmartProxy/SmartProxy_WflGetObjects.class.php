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

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetObjects_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class SmartProxy_WflGetObjects extends WflGetObjects_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_OVERRULE; }

	final public function runAfter (WflGetObjectsRequest $req, WflGetObjectsResponse &$resp) 
	{
	} 
	
	final public function runBefore (WflGetObjectsRequest &$req)	
	{
	} 
	
	final public function runOverruled (WflGetObjectsRequest $req) 	
	{	
		$cached = false;
		$rendition = $req->Rendition;
		
		if ($req->Rendition == 'native')
		{
			$tfile = CACHE_DIR. $req->Rendition . '-' . $req->IDs[0];
				
			if (file_exists($tfile))
			{
				$req->Rendition = 'none';
				$cached = true;
			}
		}

		SmartProxy::prepare_request($req);
		
		$resp = SmartProxy::client()->GetObjects($req);
		
		if ($cached)
		{
			if (file_exists($tfile))
			{
				require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
				$attachment = new Attachment($rendition, 'application/indesign');					
				$transferServer = new BizTransferServer();
				$transferServer->copyToFileTransferServer($tfile , $attachment);			
				$resp->Objects[0]->Files = array( $attachment );
			}
			else
			{
				$req->Rendition = $rendition;
				$resp = SmartProxy::client()->GetObjects($req);
			}
		}
		
		$resp = SmartProxy::finish_request($resp);
		return $resp;
	} 
}
