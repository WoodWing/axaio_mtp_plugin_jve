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

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetDialog2_EnterpriseConnector.class.php';

class PrintChannelFilter_WflGetDialog2 extends WflGetDialog2_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_AFTER; }

	final public function runAfter( WflGetDialog2Request $req, WflGetDialog2Response &$resp )
	{
		LogHandler::Log( 'PrintChannelFilter', 'INFO', 'PrintChannelFilter WflGetDialog2 runAfter' );

		// Just show print channels in the Layout dialogs
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		$objType = BizWorkflow::getMetaDataValue( $req->MetaData, 'Type' );
		if( $objType != 'Layout' ) {
			return;
		}
		if( !$resp->PubChannels ) {
			return;
		}

		require_once dirname(__FILE__) . '/config.php';

		$pubChannels = array();
		foreach( $resp->PubChannels as $pubChannelInfo ) {
			if( $pubChannelInfo->Type != 'print' ) {
				continue;
			}
			if( !$pubChannelInfo->Issues ) {
				continue;
			}
			$issues = array();
			foreach( $pubChannelInfo->Issues as $issueinfo ) {
				$now = substr(date('c'),0,19);
				$end = substr(date('c', time() + PRINTCHANNELFILTER_RANGE * 24 * 60 * 60),0,19);

				// From the issues, just display issues from now() and for the next _RANGE days
				if ((strcmp($issueinfo->PublicationDate,$now)>=0 && strcmp($end,$issueinfo->PublicationDate)>=0) || 
							$issueinfo->PublicationDate == '' || $issueinfo->Id == $pubChannelInfo->CurrentIssue ) {
					$issues[] = $issueinfo;
				}

				// Maximize the number of issues
				if( count($issues) == PRINTCHANNELFILTER_MAXISSUES ) {
					break;
				}
			}
			
			$pubChannelInfo->Issues = $issues;
			$pubChannels[] = $pubChannelInfo;
		}
		$resp->PubChannels = $pubChannels;
	} 
	
	final public function runBefore( WflGetDialog2Request &$req )
	{
		$req = $req; // make code analyzer happy
	} 
	
	final public function runOverruled( WflGetDialog2Request $req )
	{
		$req = $req; // make code analyzer happy
	} 
}
