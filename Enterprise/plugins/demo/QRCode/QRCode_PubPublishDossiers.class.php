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

require_once BASEDIR.'/server/interfaces/services/pub/PubPublishDossiers_EnterpriseConnector.class.php';

class QRCode_PubPublishDossiers extends PubPublishDossiers_EnterpriseConnector
{
	// Determine how we want to get called by core server
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	// Not called. See getRunMode().
	final public function runBefore( PubPublishDossiersRequest &$req ) 
	{ 
		$req = $req;
	}
	
	/**
	 * After a Dossier is published to web, this function is called by core server.
	 * It checks if the Dossier's URL is set by any publishing channel, typically a web channel.
	 * (The URL points to the web location where the published Dossier can be found.)
	 * The URL is used here to create an Image object representing the QR code of the URL.
	 * The new Image object is added to the Dossier and is named after the Issue and the Dossier.
	 * Then the user will see the QR code (image) appear in the Dossier that can be placed on 
	 * a layout, that is typically assigned to a print channel.
	 */
	final public function runAfter( PubPublishDossiersRequest $req, PubPublishDossiersResponse &$resp ) 
	{
		$resp = $resp;
		
		// Get first dossier & target, multiple dossier are not (yet) passed by client, so ignore that for this demo:
		$dossierID  = $req->DossierIDs[0];
		$dossierURL = $resp->PublishedDossiers[0]->URL;

		if( !empty( $dossierURL ) ) {
		
			// Get the Dossier object (being published)
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
			require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			$user = BizSession::getShortUserName();
			$dossier = BizObject::getObject( $dossierID, $user, false, 'none', array() );
					
			// Iterate thru dossier targets to find the right channel to get issue name:
			$issueName = '';
			foreach( $dossier->Targets as $dossierTarget ) {
				if( $dossierTarget->Issue->Id == $req->Targets[0]->IssueID ) {
					$issueName = $dossierTarget->Issue->Name;
				}
			}

			// Create QR code Image object	
			$qrObject = $this->createQROBject( $dossier, $dossierURL, $issueName, $user);
	
			// Add the Image object to the Dossier object
			$relation 			= new Relation;
			$relation->Parent	= $dossier->MetaData->BasicMetaData->ID;
			$relation->Child	= $qrObject->MetaData->BasicMetaData->ID;
			$relation->Type		= 'Contained';
			BizRelation::createObjectRelations( array( $relation), $user );
		}
	}
		
	// Not called. See getRunMode().
	final public function runOverruled( PubPublishDossiersRequest $req ) 
	{
		$req = $req;
	}

	/**
	 * Generates a QR code and creates a new Image object for it.
	 *
	 * @param Object $dossier The Dossier (being published). Used to inherit metadata from.
	 * @param string $dossierURL The Dossier URL for which QR code must be generated.
	 * @param string $issueName The channel's issue name where the dossier was published.
	 * @param string $user Short name of current Enterprise user.
	 * @return Object The Image object.
	 */
	private function createQRObject( $dossier, $dossierURL, $issueName, $user )
	{
		// Build object for QR code Image
		$format = 'image/jpeg';
		$qrObject = new Object;
		$qrObject->MetaData = new MetaData;
		
		$qrObject->MetaData->BasicMetaData = new BasicMetaData;
		$qrObject->MetaData->BasicMetaData->Name  		= 'QR '.$issueName.' - '.$dossier->MetaData->BasicMetaData->Name;
		$qrObject->MetaData->BasicMetaData->DocumentID 	= 'QR'.$dossier->MetaData->BasicMetaData->ID;
		$qrObject->MetaData->BasicMetaData->Type 		= 'Image';
		$qrObject->MetaData->BasicMetaData->Publication = $dossier->MetaData->BasicMetaData->Publication;
		$qrObject->MetaData->BasicMetaData->Category	= $dossier->MetaData->BasicMetaData->Category;
		
		$qrObject->MetaData->WorkflowMetaData = new WorkflowMetaData;
		$states=BizWorkflow::getStates($user, $dossier->MetaData->BasicMetaData->Publication->Id, null, null, 'Image' );
		$qrObject->MetaData->WorkflowMetaData->State = $states[0];
		
		$qrObject->MetaData->ContentMetaData = new ContentMetaData;
		$qrObject->MetaData->ContentMetaData->Description = "QR code for '".$dossier->MetaData->BasicMetaData->Name."'";
		$qrObject->MetaData->ContentMetaData->Slugline    = "QR code for '".$dossier->MetaData->BasicMetaData->Name."'";
		$qrObject->MetaData->ContentMetaData->Format      = $format;

		$qrObject->MetaData->RightsMetaData = new RightsMetaData;
		$qrObject->MetaData->SourceMetaData = new SourceMetaData;

		$qrObject->Relations 	= array();
		$qrObject->Pages 		= array();
		$qrObject->Message 		= array();
		$qrObject->Elements 	= array();
		$qrObject->Targets 		= array();
		
		// Generate QR code file and add it to the Image object
		$content = $this->createQRCode( $dossierURL, $format );
		require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
		$nativeQRFile = new Attachment('native', $format);
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer($content, $nativeQRFile);
		$qrObject->Files = array($nativeQRFile);

		// Create the Image object at Enterprise DB
		BizObject::createObject( $qrObject, $user, false, false );
		return $qrObject;
	}
	
	/**
	 * Wrapper function for 3rd party integration that generates QR codes. This function calls
	 * the qr_img0.50g/php/qr_img.php module through HTTP connection to be able to pass the required HTTP params.
	 *
	 * @param string $dossierURL The Dossier URL for which QR code must be generated.
	 * @param string $expContentType Expected content type. Must be 'image/jpeg' or 'image/png'.
	 * @return string File stream of the QR code.
	 * @throws BizException When HTTP error occurs, when content type is unexpected or when QR Code system failed.
	 */
	private function createQRCode( $dossierURL, $expContentType )
	{
		require_once 'Zend/Http/Client.php';
		
		switch( $expContentType ) {
			case 'image/jpeg':
				$qrType = 'J';
				break;
			case 'image/png':
				$qrType = 'P';
				break;
			default:
				$msg = 'Bad content type requested: "'.$expContentType.'"';
				throw new BizException( '', 'Server', null, $msg );
		}
		
		try {
			// Setup URL to 3rd party module: qr_img.php
			$http = new Zend_Http_Client();
			$lastslash = strrpos( SERVERURL_SCRIPT, '/' );
			$qrURL = substr( SERVERURL_SCRIPT, 0, $lastslash ) . '/config/plugins/QRCode/qr_img0.50g/php/qr_img.php';
			$http->setUri( $qrURL );

			// Setup HTTP parameters to send with the URL
			$params = array(
				'd' => $dossierURL, // data string
				'e' => 'M',    // error correct
				's' => '8',    // module size
				'v' => null,   // version
				't' => $qrType // image type ('J'=jpeg, other=png)
			);
			foreach( $params as $parKey => $parValue ) {
				$http->setParameterGet( $parKey, $parValue );
			}

			// Log the full URL we want to run.
			$urlParams = http_build_query( $params );
			LogHandler::Log('QRCode', 'INFO', "QR Code request: $qrURL?$urlParams");

			// Run the URL. We act as HTTP client here.
			$response = $http->request( Zend_Http_Client::GET );

			// Error handling. Take out content of QR code when successful.
			if( $response->isSuccessful() ) {
				$responseHeaders = $response->getHeaders();
				$gotContentType = $responseHeaders['Content-type'];
				if( $gotContentType == $expContentType ) {
					$retVal = $response->getBody();
				} else { // error on unhandled content
					if( $gotContentType == 'text/plain' ) {
						$msg = 'QR Code system failed. '.$response->getBody();
					} else {
						$msg = "QR Code has unexpected content type. Received: $gotContentType. Expected: $expContentType.";
						LogHandler::Log('QRCode', 'ERROR', $msg .'. First 100 bytes: '. substr( $response->getBody(), 0, 100) );
					}
					throw new BizException( '', 'Server', null, $msg );
				}
			} else {
				$respStatusCode = $response->getStatus();
				$respStatusText = $response->responseCodeAsText( $respStatusCode );
				$msg = "QR Code connection problem: $respStatusText (HTTP code: $respStatusCode)";
				LogHandler::Log('QRCode', 'ERROR', $msg .'. First 100 bytes: '. substr( $response->getBody(), 0, 100) );
				throw new BizException( '', 'Server', null, $msg );
			}
		} catch (Zend_Http_Client_Exception $e) {
			throw new BizException( '', 'Server', 'QR Code could not be created.', 'QR Code error: '.$e->getMessage() );
		}
		return $retVal;
	}
}
