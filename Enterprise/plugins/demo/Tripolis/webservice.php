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

// this script must be located in a plugins directory
require_once dirname( __FILE__) . '/../../../config/config.php';

main();

function showPreview ($ticket)
{
	require_once BASEDIR . '/server/interfaces/services/PublishingDataClasses.php';
	require_once BASEDIR . '/server/services/pub/PubPreviewDossiersService.class.php';
	
	$dossierId = 0;
	if (isset( $_REQUEST['dossierId'] )) {
		$dossierId = intval( $_REQUEST['dossierId'] );
	}
	$pubChannelId = 0;
	if (isset( $_REQUEST['pubChannelId'] )) {
		$pubChannelId = intval( $_REQUEST['pubChannelId'] );
	}
	$issuelId = 0;
	if (isset( $_REQUEST['issuelId'] )) {
		$issuelId = intval( $_REQUEST['issuelId'] );
	}
	
	$target = new PublishTarget( $pubChannelId, $issuelId );
	/*$filePath = TEMPDIRECTORY . DIRECTORY_SEPARATOR . $ticket . '_Tripolis_preview';
	if (file_exists($filePath)){
		readfile($filePath);
	}*/
	$req = new PubPreviewDossiersRequest( $ticket, array($dossierId), array($target) );
	$service = new PubPreviewDossiersService( );
	$service->execute( $req );
}

function main ()
{
	require_once BASEDIR . '/server/secure.php';
	if (isset( $_GET['ticket'] )) {
		$ticket = checkSecure( null, null, true, $_GET['ticket'] );
		//$ticket = $_GET['ticket'];
		//BizSession::checkTicket( $ticket );
		showPreview( $ticket );
	}
}