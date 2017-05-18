<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps_AdobeDpsFolio_TestCase extends TestCase
{
	public function getDisplayName() { return 'Adobe DPS integration'; }
	public function getTestGoals()   { return 'Checks if Adobe DPS folio output attachments can be stored in dossier objects per edition/device.'; }
	public function getTestMethods() { return 'Performs create-, save- and delete operations on dossier objects with Adobe DPS folio output attachment.'; }
    public function getPrio()        { return 5101; }

    private $ticket       = null;
    private $pubInfo      = null;
    private $pubChannel   = null;
    private $issue        = null;
    private $objId        = null;

    const FOLIO_FORMAT    = 'application/vnd.adobe.folio+zip';
    const FOLIO_RENDITION = 'output';
    const FOLIO_CHANTYPE  = 'dps';

    private $firstFolio   = 'folio_1';
    private $secondFolio  = 'folio_2';

    final public function runTest()
	{
		// Use TESTSUITE defined test user (for wwtest)
		$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
		if( !$suiteOpts ){
			$this->setResult( 'ERROR', 'Could not find the test user: ', 'Please check the TESTSUITE setting in configserver.php.' );
			return;
		}

		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		$logonResponse = $utils->wflLogOn( $this );
		if( is_null($logonResponse) ) {
			return;
		}
		if( !isset( $suiteOpts['DM_Brand'] ) || !isset( $suiteOpts['DM_Issue'] ) ) { // check since those are introduced since 7.5
			$this->setResult( 'ERROR', 'Could not find the DM_Brand or DM_Issue option at the TESTSUITE setting.', 
					'Please check the TESTSUITE setting in configserver.php.' );
			return;
		}
		if( !$this->resolveBrandPubChannelIssue( $logonResponse, $suiteOpts['DM_Brand'], $suiteOpts['DM_Issue'] ) ) {
			return;
		}
		$this->ticket = $logonResponse->Ticket;

		$this->createDossierObj();
		$dossierObj = $this->getDossierObj( true ); // Get Dossier object with Renditions info
		$editionIds = $this->getEditionIds( $dossierObj ); // Get the editionIds from the Renditions Info

		// Validate the content of the output file with the test data file
		foreach( $editionIds as $editionId ) {
			$dossierObj = $this->getDossierObj( false, false, $editionId );
			$fileName 	= $this->getTestFileName( $editionId );
			$this->validateDossierObj( $dossierObj, $fileName );
		}

		// Change the test data before update dossier attachment for all editions
		$this->firstFolio = 'Folio_2';
		$this->secondFolio= 'Folio_1';
		$dossierObj 	= $this->getDossierObj( false, true );
		$attachments 	= $this->createFolioAttachment( $dossierObj->Targets[0]->Editions );
		$dossierObj->Files = $attachments;
		$this->saveDossierObj( $dossierObj );

		// Validate the content of the updated output file with the test data file
		foreach( $editionIds as $editionId ) {
			$dossierObj = $this->getDossierObj( false, false, $editionId );
			$fileName 	= $this->getTestFileName( $editionId );
			$this->validateDossierObj( $dossierObj, $fileName );
		}

		// Save dossier object with output file attachment per edition
		$this->firstFolio = 'Folio_1';
		$this->secondFolio= 'Folio_2';
		foreach( $editionIds as $editionId ) {
			$dossierObj = $this->getDossierObj( false, true, $editionId );
			$editionObj = null;
			foreach( $dossierObj->Targets[0]->Editions as $edition ) {
				if( $edition->Id == $editionId ) {
					$editionObj = $edition;
					break;
				}
			}
			$attachments 	= $this->createFolioAttachment( array( $editionObj ) );
			$dossierObj->Files = $attachments;
			$this->saveDossierObj( $dossierObj );

			$updateDossierObj = $this->getDossierObj( false, false, $editionId );
			$fileName 	= $this->getTestFileName( $editionId );
			$this->validateDossierObj( $updateDossierObj, $fileName );
		}

		$this->deleteDossierObj();
		$utils->wflLogOff( $this, $this->ticket );
	}

	/**
	 * Resolves the Brand, PubChannel and Issue from LogOn response and given brand- and issue name.
	 *
	 * @param WflLogOnResponse $logonResponse
	 * @param string $brandName
	 * @param string $issueName
	 * @return bool Whether or not successful
	 */
	private function resolveBrandPubChannelIssue( $logonResponse, $brandName, $issueName )
	{
		$this->pubInfo = null;
		$this->pubChannel = null;
		$this->issue = null;
		if( count($logonResponse->Publications) > 0 ) {
			if( $logonResponse->Publications ) foreach( $logonResponse->Publications as $pub ) {
				if( $pub->Name == $brandName ) {
					$this->pubInfo = $pub;
					if( $pub->PubChannels ) foreach( $pub->PubChannels as $pubChannel ) {
						if( $pubChannel->Issues ) foreach( $pubChannel->Issues as $issue ) {
							if( $issue->Name == $issueName ) {
								$this->issue = $issue;
								if( $pubChannel->Type == self::FOLIO_CHANTYPE ) {
									$this->pubChannel = $pubChannel;
									if( count($pubChannel->Editions) ) {
										break 3; // found; quit all loops
									}
								}
							}
						}
					}
				}
			}
		}
		if( !$this->pubInfo ) {
			$this->setResult( 'ERROR', 'Could not find the test Brand: '.$brandName, 
					'Please check the TESTSUITE setting in configserver.php.' );
			$error = true;
		} else if( !$this->issue ) {
			$this->setResult( 'ERROR', 'Could not find the test Issue: '.$issueName, 
					'Please check the TESTSUITE setting in configserver.php.' );
			$error = true;
		} else if( !$this->pubChannel ) {
			$this->setResult( 'ERROR', 'The issue "'.$issueName.'" does not '.
				'belong to a Publication Channel of Type "'.self::FOLIO_CHANTYPE.'".', 
				'Please check the TESTSUITE setting in configserver.php.' );
			$error = true;
		} else if( count($this->pubChannel->Editions) == 0 ) {
			$this->setResult( 'ERROR', 'The issue "'.$issueName.'" does '.
				'belong to a Publication Channel "'.$this->pubChannel->Name.'" that '.
				'does not have any editions/devices configured.', 
				'Please check the TESTSUITE setting in configserver.php.' );
			$error = true;
		} else {
			$error = false;
		}
		if( $error ) {
			$this->pubInfo = null;
			$this->pubChannel = null;
			$this->issue = null;
		}
		return !$error;
	}

	/**
	 * Create the dossier object with the output file attachments
	 *
	 */
	private function createDossierObj()
	{
		// Build MetaData
		$basicMD                = new BasicMetaData();
		$basicMD->Name          = 'DPS_'.date('Y-m-d_H-i-s');
		$basicMD->Type          = 'Dossier';
		$basicMD->Publication   = new Publication( $this->pubInfo->Id, $this->pubInfo->Name );
		$category               = $this->pubInfo->Categories[0];
		$basicMD->Category      = new Category( $category->Id, $category->Name );

		$wflMD           = new WorkflowMetaData();
		$wflMD->State    = $this->getStatus( 'Dossier' );
		
		$contentMD           = new ContentMetaData();
		$contentMD->Format   = '';
		$contentMD->FileSize = 0;

		$metaData 					= new MetaData();
		$metaData->BasicMetaData    = $basicMD;
		$metaData->RightsMetaData   = new RightsMetaData();
		$metaData->SourceMetaData   = new SourceMetaData();
		$metaData->ContentMetaData  = $contentMD;
		$metaData->WorkflowMetaData = $wflMD;
		$metaData->ExtraMetaData    = array();
		
		$target		= $this->createTarget();
		$attachments= $this->createFolioAttachment( $target->Editions );
		
		// Create dossier object
		$object = new Object();
		$object->MetaData 	= $metaData;
		$object->Relations 	= array();
		$object->Files     	= $attachments;
		$object->Targets	= array( $target );
		
		try {
			require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
			$service 	  = new WflCreateObjectsService();
			$req          = new WflCreateObjectsRequest();
			$req->Ticket  = $this->ticket;
			$req->Lock    = false;
			$req->Objects = array( $object );
			$response = $service->execute( $req );

			$this->objId = $response->Objects[0]->MetaData->BasicMetaData->ID;
			LogHandler::Log( 'AdobeDps', 'DEBUG', 'Dossier Id Created:'.$this->objId );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'CreateDossierObj: failed: "'.$e->getMessage().'"' );
		}
	}

	/**
	 * Get the dossier object
	 *
	 * @param boolean $renditionsInfo Indicator to request for renditionsInfo for an object
	 * @param boolean $lock Indicator whether to lock the object
	 * @param integer $editionId EditionId that the attachment belongs to
	 * @return object
	 */
	private function getDossierObj( $renditionsInfo = false, $lock = false, $editionId = null )
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$service      		= new WflGetObjectsService();
			$req          		= new WflGetObjectsRequest();
			$req->Ticket  		= $this->ticket;
			$req->IDs     		= array( $this->objId );
			$req->Lock    		= $lock;
			$req->Rendition 	= self::FOLIO_RENDITION;
			$req->RequestInfo 	= $renditionsInfo ? array( 'RenditionsInfo' ) : array();
			$req->EditionId		= $editionId;
			$response = $service->execute( $req );

			$obj = $response->Objects[0];
			return $obj;
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'getDossierObj: failed: "'.$e->getMessage().'"' );
		}
		return null;
	}

	/**
	 * Save the dossier object
	 *
	 * @param object $dossierObj Dossier object with updated file attachments
	 */
	private function saveDossierObj( $dossierObj )
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
			$service 	  		= new WflSaveObjectsService();
			$req          		= new WflSaveObjectsRequest();
			$req->Ticket  		= $this->ticket;
			$req->CreateVersion	= true;
			$req->ForceCheckIn  = true;
			$req->Unlock		= true;
			$req->Objects		= array( $dossierObj );

			$service->execute( $req );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'saveDossierObj: failed: "'.$e->getMessage().'"' );
		}
	}

	/**
	 * Delete the dossier object
	 */
	private function deleteDossierObj()
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$service 		= new wflDeleteObjectsService();
			$req            = new WflDeleteObjectsRequest();
			$req->Ticket    = $this->ticket;
			$req->IDs       = array( $this->objId );
			$req->Permanent = true;
			$req->Areas 	= array("Workflow");

			$resp = $service->execute( $req );
			if( $resp->Reports ){ // Introduced since v8.0
				$errMsg = '';
				foreach( $resp->Reports as $report ){
					foreach( $report->Entries as $reportEntry ) {
						$errMsg .= $reportEntry->Message . PHP_EOL;
					}
				}
				$this->setResult( 'ERROR', 'deleteDossierObj: failed: "'.$errMsg .'"' );
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'deleteDossierObj: failed: "'.$e->getMessage().'"' );
		}
	}

	/**
	 * Create a new Target with the found PubChannel and Issue information.
	 *
	 * @return Target
	 */
	private function createTarget()
	{
		$target 			= new Target();
		$target->PubChannel	= new PubChannel( $this->pubChannel->Id, $this->pubChannel->Name );
		$target->Issue		= new Issue( $this->issue->Id, $this->issue->Name );
		$target->Editions	= $this->pubChannel->Editions;
		return $target;
	}

	/**
	 * Create the output folio file attachment per edition
	 *
	 * @param array $editions array of Edition object
	 * @return array of Attachment objects
	 */
	private function createFolioAttachment( $editions )
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$bizTransfer = new BizTransferServer();

		$fileAttachments = array();
		foreach( $editions as $edition ) {
			$fileName = $this->getTestFileName( $edition->Id );
			$fileName = dirname(__FILE__).'/testdata/'.$fileName;

			$fileAttachment = new Attachment();
			$fileAttachment->Rendition 	= self::FOLIO_RENDITION;
			$fileAttachment->Type      	= self::FOLIO_FORMAT;
			$fileAttachment->EditionId 	= $edition->Id;

			$bizTransfer->copyToFileTransferServer( $fileName, $fileAttachment );

			$fileAttachments[] = $fileAttachment;
		}

		return $fileAttachments;
	}

	/**
	 * Return the first status found for the object type in publicationInfo.
	 *
	 * @param string $objecType
	 * @return Status
	 */
	private function getStatus( $objecType )
	{
		$statuses = $this->pubInfo->States;
		if( $statuses )foreach( $statuses as $status ){
			if( $status->Type == $objecType ){
				return $status;
			}
		}
		return null;
	}

	/**
	 * Validate the dossier object file attachment content with the test data content
	 *
	 * @param object $dossierObj
	 * @param string $fileName
	 */
	private function validateDossierObj( $dossierObj, $fileName )
	{
		$testFileContent = $this->getFileContent( $fileName );

		$dbFileContent = file_get_contents($dossierObj->Files[0]->FilePath);

		$sameContent = strcmp($testFileContent, $dbFileContent);

		if( $sameContent !== 0 ) {
			$this->setResult( 'ERROR', 'The folio file saved into database found different content than the test data' . $sameContent, 'Please check the TESTSUITE setting in configserver.php.' );
		}
	}

	/**
	 * Get the file content
	 *
	 * @param string $fileName
	 * @return string $fileContent
	 */
	private function getFileContent( $fileName )
	{
		$fileContentPath = dirname(__FILE__).'/testdata/'.$fileName;
		$fileContent = file_get_contents( $fileContentPath );

		return $fileContent;
	}

	/**
	 * Get the editionids of a dossier object
	 *
	 * @param object $dossierObj
	 * @return array of Edition Ids
	 */
	private function getEditionIds( $dossierObj )
	{
		$editionIds = array();
		if( $dossierObj->Renditions ) {
			foreach( $dossierObj->Renditions as $editionRenditionsInfo ) {
				foreach( $editionRenditionsInfo->Renditions as $renditionTypeInfo ) {
					if( $renditionTypeInfo->Rendition == self::FOLIO_RENDITION && 
						$renditionTypeInfo->Type == self::FOLIO_FORMAT ) {
						$editionIds[] = $editionRenditionsInfo->Edition->Id;
						break;
					}
				}
			}
		}
		return $editionIds;
	}

	/**
	 * Get the test data for different edition by modulo function
	 *
	 * @param string $editionId
	 * @return string File name
	 */
	private function getTestFileName( $editionId )
	{
		$fileName = ( intval($editionId) % 2 === 0 ) ? $this->secondFolio : $this->firstFolio;
		$fileName .= '.folio';
		return $fileName;
	}
}
