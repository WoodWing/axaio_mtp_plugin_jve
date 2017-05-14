<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

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

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubUnPublishDossiers_EnterpriseConnector.class.php';

class QRCode_PubUnPublishDossiers extends PubUnPublishDossiers_EnterpriseConnector
{
	// Determine how we want to get called by core server
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	// Not called. See getRunMode().
	final public function runBefore( PubUnPublishDossiersRequest &$req ) 
	{ 
	}

	/**
	 * When dossier is unpublished, this function is called by core server.
	 * This function deletes the QR code created for the dossier / issue.
	 */
	final public function runAfter( PubUnPublishDossiersRequest $req, PubUnPublishDossiersResponse &$resp ) 
	{
		// Get first dossier & target, multiple dossier are not (yet) passed by client, so ignore that for this demo:
		$dossierID = $req->DossierIDs[0];

		// Get the Dossier object (being unpublished)
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR.'/server/bizclasses/BizDeletedObject.class.php';
		$user = BizSession::getShortUserName();
		$dossier = BizObject::getObject( $dossierID, $user, false, 'none', array() );
				
		// Iterate thru dossier targets to find the right channel to get channel name:
		$issueName = '';
		foreach( $dossier->Targets as $aDossierTarget ) {
			if( $aDossierTarget->Issue->Id == $req->Targets[0]->IssueID ) {
				$issueName = $aDossierTarget->Issue->Name;
			}
		}
		
		// Find the QR image based on name, type and dossier id (stored in documentid)
		$queryParams 	= array();
		$queryParams[] 	= new QueryParam( 'Name', '=', 'QR '.$issueName.' - '.$dossier->MetaData->BasicMetaData->Name );
		$queryParams[] 	= new QueryParam( 'Type', '=', 'Image' );
		$queryParams[] 	= new QueryParam( 'DocumentID', '=', 'QR'.$dossierID );
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $req->Ticket;
		$request->Params = $queryParams;
		$request->FirstEntry = 0;
		$request->MaxEntries = 1;
		require_once BASEDIR."/server/bizclasses/BizQuery.class.php";
		$objects = BizQuery::queryObjects2( $request, $user ) ;
		
		// Remove the QR code image from Enterprise DB
		if( !empty($objects->Rows) ) {
			$qrObjectID = $objects->Rows[0][0];
			BizDeletedObject::deleteObject( $user, $qrObjectID, false /*permanent*/ );
		}
	}
	
	// Not called. See getRunMode().
	final public function runOverruled( PubUnPublishDossiersRequest $req ) 
	{
	}
}
