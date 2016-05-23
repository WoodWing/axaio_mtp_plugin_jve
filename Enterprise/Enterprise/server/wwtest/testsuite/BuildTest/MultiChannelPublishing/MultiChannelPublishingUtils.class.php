<?php

/**
 * Contains helper functions for the MultiChannelPublishing tests.
 *
 * @package 	Enterprise
 * @subpackage 	Testsuite
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class MultiChannelPublishingUtils
{
	const RELATION_NORMAL = 'correct';
	const RELATION_MISSING_ERROR = 'missing';
	const RELATION_TARGET_ERROR = 'target';

	private $testCase = null;
	private $vars = null;
	private $ticket = null;
	private $utils = null;
	
	/**
	 * Initializes the utils to let it work for a TestCase.
	 *
	 * @param TestCase $testCase
	 * @return bool Whether or not all session variables are complete.
	 */
	public function initTest( TestCase $testCase )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$valid = false;
		$this->vars = $testCase->getSessionVariables();
		$this->testCase = $testCase;
		$this->expectedError = null;
		
		$tip = 'Please enable the "Setup test data" entry and try again.';
		do {		
			// Check LogOn ticket.
			$this->ticket = @$this->vars['BuildTest_MultiChannelPublishing']['ticket'];
			if( !$this->ticket ) {
				$testCase->setResult( 'ERROR',  'Could not find ticket to test with.', $tip );
				break;
			}
			
			// Check presence of test data.
			if( !isset($this->vars['BuildTest_MultiChannelPublishing']['testOptions'] ) ||
				!isset($this->vars['BuildTest_MultiChannelPublishing']['publication'] ) ||
				!isset($this->vars['BuildTest_MultiChannelPublishing']['printPubChannel'] ) ||
				!isset($this->vars['BuildTest_MultiChannelPublishing']['webPubChannel'] ) ||
				!isset($this->vars['BuildTest_MultiChannelPublishing']['pubChannels'] ) ||
				!isset($this->vars['BuildTest_MultiChannelPublishing']['printIssue'] ) ||
				!isset($this->vars['BuildTest_MultiChannelPublishing']['webIssue'] )
			) {
				$testCase->setResult( 'ERROR',  'Could not find data to test with.', $tip );
				break;
			}
			
			$valid = true;
		} while( false );
		
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		return $valid;
	}
	
	/**
	 * Defines the error message or server error (S-code) for the next function call
	 * that executes a service request. This settings get automatically cleared after
	 * the call.
	 *
	 * @param string $expectedError
	 */
	public function setExpectedError( $expectedError ) 
	{
		$this->expectedError = $expectedError;
	}
	
	/**
	 * Creates a new State Object in the Database.
	 *
	 * @static
	 * @param String $objectType   The objectType to create the state for.
	 * @param String $name         The name of the state, should be unique.
	 * @param String $publicationId    The publication Id for which to create the state.
	 * @return null|object  The created State, or null if it fails.
	 */
	public static function createState( $objectType, $name, $publicationId )
	{
		// Compose an object.
		$object = new stdClass();
		$object->Id = 0;
		$object->PublicationId	= $publicationId;
		$object->Type = $objectType;
		$object->Name = $name;
		$object->Produce = false;
		$object->Color = '#FFFF99';
		$object->NextStatusId = 0;
		$object->SortOrder = 0;
		$object->IssueId = 0;
		$object->SectionId = 0;
		$object->DeadlineStatusId = 0;
		$object->DeadlineRelative = 0;
		$object->CreatePermanentVersion = false;
		$object->RemoveIntermediateVersions = false;
		$object->AutomaticallySendToNext = false;

		// Insert the State.
		try {
			require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
			$status = BizAdmStatus::createStatus( $object );
		} catch( BizException $e ) {
			self::setResult( 'ERROR', 'Creating states failed: ' . $e->getMessage());
			$status = null;
		}
		return $status;
	}

	/**
	 * Removes a state based on an array of states.
	 *
	 * @param Array $states The states to be removed.
	 * @return bool Whether or not the state was successfully removed.
	 */
	public static  function removeStates(array $states)
	{
		foreach ($states as $state) {
			try {
				require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
				$stateId = $state->Id;
				BizCascadePub::deleteStatus( $stateId );
			} catch( BizException $e ) {
				self::setResult( 'ERROR', 'Removing state failed: ' . $e->getMessage());
				return false;
			}
		}
		return true;
	}

	/**
	 * Creates an object in the database.
	 *
	 * @param Object $object The object to be created.
	 * @param string $stepInfo Extra logging info.
	 * @param bool $lock Whether or not the lock the object.
	 * @return Object|null. The created object. NULL on failure.
	 */
	private function createObject( $object, $stepInfo, $lock = false )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$request->Lock = $lock;
		$request->Objects = array( $object );

		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		return isset($response->Objects[0]) ? $response->Objects[0] : null;
	}

	/**
	 * Creates an article.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $articleName To give the article a name. Pass NULL to auto-name it: 'BuildTestArticle'+<datetime>
	 * @return null|Object The created article or null if unsuccessful.
	 */
	public function createArticle( $stepInfo, $articleName=null )
	{
		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->vars['BuildTest_MultiChannelPublishing']['ticket'] );

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$publication = $this->vars['BuildTest_MultiChannelPublishing']['publication'];
		$objectPublication = new Publication();
		$objectPublication->Id = $publication->Id;
		$objectPublication->Name = $publication->Name;

		// Determine unique article name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;
		$articleName = is_null( $articleName ) ? 'Article '.$postfix : $articleName;

		// BasicMetaData
		$basicMD = new BasicMetaData();
		$basicMD->ID = null;
		$basicMD->DocumentID = null;
		$basicMD->Name = $articleName;
		$basicMD->Type = 'Article';
		$basicMD->Publication = $objectPublication;
		$basicMD->Category = BizObjectComposer::getFirstCategory( $user, $publication->Id) ;
		$basicMD->ContentSource = null;

		// ContentMetaData
		$contentMD = new ContentMetaData();
		$contentMD->Description = 'Temporary article to test for publishForm workflow. '.
									'Created by BuildTest class '.__CLASS__;
		$contentMD->DescriptionAuthor = null;
		$contentMD->Keywords = array();
		$contentMD->Slugline = 'the headthe introthe body';
		$contentMD->Format = 'application/incopyicml';
		$contentMD->Columns = null;
		$contentMD->Width = null;
		$contentMD->Height = null;
		$contentMD->Dpi = null;
		$contentMD->LengthWords = 6;
		$contentMD->LengthChars = 25;
		$contentMD->LengthParas = 3;
		$contentMD->LengthLines = null;
		$contentMD->PlainContent = 'the headthe introthe body';
		$contentMD->FileSize = 160706;
		$contentMD->ColorSpace = null;
		$contentMD->HighResFile = null;
		$contentMD->Encoding = null;
		$contentMD->Compression = null;
		$contentMD->KeyFrameEveryFrames = null;
		$contentMD->Channels = 'MWPublishing';
		$contentMD->AspectRatio = null;

		// WorkflowMetaData
		$state = BizObjectComposer::getFirstState( $user, $publication->Id, null, null, 'Article');
		$workflowMD = new WorkflowMetaData();
		$workflowMD->Deadline = null;
		$workflowMD->Urgency = null;
		$workflowMD->Modifier = null;
		$workflowMD->Modified = null;
		$workflowMD->Creator = null;
		$workflowMD->Created = null;
		$workflowMD->Comment = null;
		$workflowMD->State = $state;
		$workflowMD->RouteTo = null;
		$workflowMD->LockedBy = null;
		$workflowMD->Version = null;
		$workflowMD->DeadlineSoft = null;
		$workflowMD->Rating = null;
		$workflowMD->Deletor = null;
		$workflowMD->Deleted = null;

		// MetaData
		$metaData = new MetaData();
		$metaData->BasicMetaData = $basicMD;
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->ContentMetaData->Slugline = 'A test slugline';
		$metaData->WorkflowMetaData = $workflowMD;
		$metaData->ExtraMetaData = array();

		// Files
		// Transfer server
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();

		$fileAttach = new Attachment();
		$fileAttach->Rendition = 'native';
		$fileAttach->Type = 'application/incopyicml';
		$fileAttach->Content = null;
		$fileAttach->FilePath = '';
		$fileAttach->FileUrl = null;
		$fileAttach->EditionId = null;
		$inputPath = dirname(__FILE__).'/testdata/rec#001_att#000_native.wcml';
		$transferServer->copyToFileTransferServer( $inputPath, $fileAttach );

		// Target
		$pubChannel = $this->vars['BuildTest_MultiChannelPublishing']['webPubChannel'];
		$issue = $this->vars['BuildTest_MultiChannelPublishing']['webIssue'];
		$target = new Target();
		$target->PubChannel = new PubChannel();
		$target->PubChannel->Id = $pubChannel->Id;
		$target->PubChannel->Name = $pubChannel->Name;
		$target->Issue = new Issue();
		$target->Issue->Id = $issue->Id;
		$target->Issue->Name = $issue->Name;
		$target->Issue->OverrulePublication = null;
		$target->Editions = null;
		$target->PublishedDate = null;
		$target->PublishedVersion = null;

		// Create the Article object.
		$articleObj = new Object();
		$articleObj->MetaData = $metaData;
		$articleObj->Relations = array();
		$articleObj->Pages = null;
		$articleObj->Files = array( $fileAttach );
		$articleObj->Messages = null;
		$articleObj->Elements = null;
		$articleObj->Targets = array( $target );
		$articleObj->Renditions = null;
		$articleObj->MessageList = null;
		
		return $this->createObject( $articleObj, $stepInfo );
	}

	/**
	 * Creates a Dossier.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $dossierName To give the article a name. Pass NULL to auto-name it: 'BuildTestDossier'+<datetime>
	 * @param string|null $publicationChannel 'web'(default) or 'print. To assign the Dossier target if it should be 'print' or 'web' pub channel
	 * @return null|Object The created dossier or null if unsuccessful.
	 */
	public function createDossier( $stepInfo, $dossierName = null, $publicationChannel='web' )
	{
		$publication = $this->vars['BuildTest_MultiChannelPublishing']['publication'];
		$issue = $this->vars['BuildTest_MultiChannelPublishing']['webIssue'];

		// Retrieve the State.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->vars['BuildTest_MultiChannelPublishing']['ticket'] );

		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		$state = BizObjectComposer::getFirstState($user, $publication->Id, null, null, 'Dossier');
		$category = BizObjectComposer::getFirstCategory($user, $publication->Id);

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$objectPublication = new Publication();
		$objectPublication->Id = $publication->Id;
		$objectPublication->Name = $publication->Name;
		
		// Determine uninque dossier name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;
		$dossierName = is_null( $dossierName ) ? 'Dossier '.$postfix : $dossierName;

		// MetaData
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->BasicMetaData->Name = $dossierName;
		$metaData->BasicMetaData->Type = 'Dossier';
		$metaData->BasicMetaData->Publication = $objectPublication;
		$metaData->BasicMetaData->Category = $category;
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->ContentMetaData->Description = 'Temporary dossier to contain a publishForm. '.
									'Created by BuildTest class '.__CLASS__;
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->WorkflowMetaData->State = $state;
		$metaData->ExtraMetaData = array();

		// Get the PubChannel.
		$pubChannel = $this->vars['BuildTest_MultiChannelPublishing']['webPubChannel'];

		if( $publicationChannel == 'web' ) {
			$templateTarget = new Target();
			$templateTarget->PubChannel = new PubChannel($pubChannel->Id, $pubChannel->Name); // Send the correct type of object
			$templateTarget->Issue = new Issue($issue->Id, $issue->Name, $issue->OverrulePublication); // Send the correct type of object
		} else if( $publicationChannel == 'print' ) {
			$pubChannelInfo = $this->vars['BuildTest_MultiChannelPublishing']['printPubChannel']; // Take from the print channel
			$issueInfo = $this->vars['BuildTest_MultiChannelPublishing']['printIssue']; // Take from the print channel
			$templateTarget = new Target();
			$templateTarget->PubChannel = new PubChannel($pubChannelInfo->Id, $pubChannelInfo->Name);
			$templateTarget->Issue = new Issue($issueInfo->Id, $issueInfo->Name, $issueInfo->OverrulePublication);
			$templateTarget->Editions = $pubChannelInfo->Editions;
		} else {
			$templateTarget = null;
		}
		$dosObject = new Object();
		$dosObject->MetaData = $metaData;
		$dosObject->Targets = array( $templateTarget );

		return $this->createObject( $dosObject, $stepInfo );
	}

	/**
	 * Creates a layout.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $layoutName To give the article a name. Pass NULL to auto-name it: 'BuildTestLayout'+<datetime>
	 * @param bool $lock Whether to lock the Layout during creation.
	 * @return Object|null The created layout; Null otherwise.
	 */
	public function createLayout( $stepInfo, $layoutName=null, $lock=false, $publicationChannel='print' )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';

		// Determine unique layout name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;
		$layoutName = !is_null( $layoutName ) ? $layoutName : 'Layout '.$postfix;

		$user = BizSession::checkTicket( $this->vars['BuildTest_MultiChannelPublishing']['ticket'] );
		$publication = $this->vars['BuildTest_MultiChannelPublishing']['publication'];
		$category = BizObjectComposer::getFirstCategory( $user, $publication->Id );
		$state = BizObjectComposer::getFirstState( $user, $publication->Id, null, null, 'Layout' );
		$transferServer = new BizTransferServer();

		if( $publicationChannel == 'web' ) {
			// Take from 'web' channel
			$pubChannelInfo = $this->vars['BuildTest_MultiChannelPublishing']['webPubChannel'];
			$issueInfo = $this->vars['BuildTest_MultiChannelPublishing']['webIssue'];
		} else if( $publicationChannel == 'print' ) {
			// Take from 'print' channel
			$pubChannelInfo = $this->vars['BuildTest_MultiChannelPublishing']['printPubChannel'];
			$issueInfo = $this->vars['BuildTest_MultiChannelPublishing']['printIssue'];
		}

		$basicMD = new BasicMetaData();
		$basicMD->ID = null;
		$basicMD->DocumentID = 'xmp.did:A970C7BF66206811A531935ADCC0E2BE';
		$basicMD->Name = $layoutName;
		$basicMD->Type = 'Layout';
		$basicMD->Publication = new Publication();
		$basicMD->Publication->Id = $publication->Id;
		$basicMD->Publication->Name = $publication->Name;
		$basicMD->Category = $category;

		$contentMD = new ContentMetaData();
		$contentMD->Description = 'Created by BuildTest class '.__CLASS__;
		$contentMD->DescriptionAuthor = null;
		$contentMD->Keywords = null;
		$contentMD->Slugline = null;
		$contentMD->Format = 'application/indesign';
		$contentMD->Columns = 0;
		$contentMD->Width = 0;
		$contentMD->Height = 0;
		$contentMD->Dpi = 0;
		$contentMD->LengthWords = 0;
		$contentMD->LengthChars = 0;
		$contentMD->LengthParas = 0;
		$contentMD->LengthLines = 0;
		$contentMD->PlainContent = null;
		$contentMD->FileSize = 417792;
		$contentMD->ColorSpace = null;
		$contentMD->HighResFile = null;
		$contentMD->Encoding = null;
		$contentMD->Compression = null;
		$contentMD->KeyFrameEveryFrames = null;
		$contentMD->Channels = null;
		$contentMD->AspectRatio = null;

		$workflowMD = new WorkflowMetaData();
		$workflowMD->Deadline = null;
		$workflowMD->Urgency = null;
		$workflowMD->Modifier = null;
		$workflowMD->Modified = '2013-04-15T17:19:28';
		$workflowMD->Creator = null;
		$workflowMD->Created = '2013-04-15T15:55:04';
		$workflowMD->Comment = '';
		$workflowMD->State = $state;
		$workflowMD->RouteTo = '';
		$workflowMD->LockedBy = null;
		$workflowMD->Version = null;
		$workflowMD->DeadlineSoft = null;
		$workflowMD->Rating = null;
		$workflowMD->Deletor = null;
		$workflowMD->Deleted = null;

		$page = new Page();
		$page->Width = 595.275591;
		$page->Height = 841.889764;
		$page->PageNumber = '1';
		$page->PageOrder = 1;

		$pageFiles = array();
		$attachment = new Attachment();
		$attachment->Rendition = 'thumb';
		$attachment->Type = 'image/jpeg';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#008_att#000_thumb.jpg';
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );
		$pageFiles[] = $attachment;

		$attachment = new Attachment();
		$attachment->Rendition = 'preview';
		$attachment->Type = 'image/jpeg';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#008_att#001_preview.jpg';
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );
		$pageFiles[] = $attachment;
		$page->Files = $pageFiles;

		$page->Edition = null;
		$page->Master = 'Master';
		$page->Instance = 'Production';
		$page->PageSequence = 1;
		$page->Renditions = null;
		$page->Orientation = null;

		$files = array();
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'application/indesign';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#008_att#002_native.indd';
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );
		$files[] = $attachment;

		$attachment = new Attachment();
		$attachment->Rendition = 'thumb';
		$attachment->Type = 'image/jpeg';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#008_att#003_thumb.jpg';
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );
		$files[] = $attachment;

		$attachment = new Attachment();
		$attachment->Rendition = 'preview';
		$attachment->Type = 'image/jpeg';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#008_att#004_preview.jpg';
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );
		$files[] = $attachment;

		$pubChannel = new PubChannel();
		$pubChannel->Id = $pubChannelInfo->Id;
		$pubChannel->Name = $pubChannelInfo->Name;
		$issue = new Issue();
		$issue->Id = $issueInfo->Id;
		$issue->Name = $issueInfo->Name;
		$issue->OverrulePublication = null;

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue = $issue;
		$target->Editions = $pubChannelInfo->Editions;
		$target->PublishedDate = null;
		$target->PublishedVersion = null;

		$messageList = new MessageList();
		$messageList->Messages = null;
		$messageList->ReadMessageIDs = null;
		$messageList->DeleteMessageIDs = null;

		$layout = new Object();
		$layout->MetaData = new MetaData();
		$layout->MetaData->BasicMetaData = $basicMD;
		$layout->MetaData->RightsMetaData = null;
		$layout->MetaData->SourceMetaData = null;
		$layout->MetaData->ContentMetaData = $contentMD;
		$layout->MetaData->WorkflowMetaData = $workflowMD;
		$layout->MetaData->ExtraMetaData = null;
		$layout->Relations = array();
		$layout->Pages = array( $page );
		$layout->Files = $files;
		$layout->Messages = null;
		$layout->Elements = null;
		$layout->Targets = array( $target );
		$layout->Renditions = null;
		$layout->MessageList = $messageList;

		return $this->createObject( $layout, $stepInfo, $lock );
	}


	/**
	 * Creates a Publish Form object.
	 *
	 * @param Object $template The template to base an Object Relation on.
	 * @param Object $dossier The Dossier to create the Object Relation for
	 * @param string $stepInfo Extra logging info.
	 * @param $relationOption how to set up the relations for this object.
	 * @param MetaData $metaData Optional MetaData object to be set for the object.
	 * @param array|null $formRelationTargets The form relational targets. When null is sent, the first dossier target is used.
	 * @return null|Object The created object or null if unsucessful.
	 */
	public function createPublishFormObject( Object $template, $dossier, $stepInfo,
		$relationOption=self::RELATION_NORMAL, $metaData=null, $formRelationTargets=null )
	{
		$object = new Object();
		$object->MetaData 	= null;
		$object->Files     	= array();
		$object->Targets	= array();

		// Build the ObjectRelations.
		$object->Relations = self::createRelationsForFormObject( $template, $dossier, $formRelationTargets, $relationOption );

		if( !is_null($metaData) ) {
			$object->MetaData = $metaData;
		}

		return $this->createObject( $object, $stepInfo );
	}
	
	/**
	 * Composes an object Relation data object.
	 * Optionally, it problematic relations can be built too, as specified through $case.
	 *
	 * @param Object $template
	 * @param Object $dossier
	 * @param array|null $formRelationTargets The form relational targets. When null is sent, the first dossier target is used.
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $case self::RELATION_NORMAL, self::RELATION_MISSING_ERROR or self::RELATION_TARGET_ERROR
	 */
	private static function createRelationsForFormObject( $template, $dossier, $formRelationTargets, $case )
	{
		$relation1 = new Relation();
		$relation1->Parent = $template->MetaData->BasicMetaData->ID;
		$relation1->Type = 'InstanceOf';
		$relations = array($relation1);
		switch( $case ) {
			case self::RELATION_NORMAL: // normal / correct behaviour.
				if( !$formRelationTargets ) {
					// @TODO: By getting the first Target of the Dossier, it is assumed that the first Target supports
					// PublishForm. Preferably, iterate through the dossier Targets and check if the target supports
					// PublishForm, when it does, only assign to the Form relation targets.
					$formRelationTargets = array( $dossier->Targets[0] );
				}
				$relation2 = new Relation();
				$relation2->Parent = $dossier->MetaData->BasicMetaData->ID;
				$relation2->Type = 'Contained';
				$relation2->Targets = $formRelationTargets;
				$relations[] = $relation2;
				break;
			case self::RELATION_MISSING_ERROR: // Trigger an error because of missing relation of type Contained.
				break;
			case self::RELATION_TARGET_ERROR;
				$relation2 = new Relation();
				$relation2->Parent = $dossier->MetaData->BasicMetaData->ID;
				$relation2->Type = 'Contained';
				$relations[] = $relation2;
		}
		return $relations;
	}
	
	/**
	 * Create a Relation of type 'Placed' where an Image or Article will be placed on the Form.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param array $relations List of Relations to be created.
	 * @return array|null List of Relation object that have been successfully created. Null if creation of Relation has failed.
	 */
	public function createPlacementRelationsForForm( $stepInfo, $relations )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$request->Relations = $relations;

		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		return $response->Relations ? $response->Relations : null;
	}
	
	/** 
	 * Construct the Placed Relation object given the parent(form) and the child(the placement).
	 * 
	 * @param int $formId The Id of the form where the image(s) or article(s) will be placed on.
	 * @param int $placedObjId The Id of the object to be placed on the Form such as Image, Article
	 * @param int $frameOrder The order of the placement object within the widget.Default is 0(There's only one object).
	 * @param string|null $formWidgetId The widget id(name) where the placement object is placed in. Passed in null to get a dummy widget id(name).
	 * @param array|null $placements List of placements to be included in the Relation. Null to create a dummy placement.
	 * @return Relation Relation object
	 */
	public static function composePlacedRelation( $formId, $placedObjId, $frameOrder=0, $formWidgetId=null ,$placements=null )
	{
		if( is_null( $formWidgetId ) ) {
			$formWidgetId = 'C_DUMMY_WIDGETID_' . $placedObjId;
		}
		if( is_null( $placements )) {
			$placement = self::composePlacementObject( $frameOrder, $formWidgetId );
			$placements = array( $placement );
		}
		$relation = new Relation();
		$relation->Parent = $formId;
		$relation->Child = $placedObjId;
		$relation->Type = 'Placed';
		$relation->Placements = $placements;
		$relation->ParentVersion = null;
		$relation->ChildVersion = null;
		$relation->Geometry = null;
		$relation->Rating = null;
		$relation->Targets = null;
		
		return $relation;
	}

	/**
	 * Construct the Placement object given the placement frame order and Widget id the placement should be placed on.
	 *
	 * @param int $frameOrder
	 * @param string $formWidgetId
	 * @return Placement The composed placement object.
	 */
	public static function composePlacementObject( $frameOrder, $formWidgetId )
	{
		$placement = new Placement();
		$placement->Page = null;
		$placement->Element = null;
		$placement->ElementID = '';
		$placement->FrameOrder = $frameOrder;
		$placement->FrameID = '209';
		$placement->Left = 0;
		$placement->Top = 0;
		$placement->Width = 4;
		$placement->Height = 384;
		$placement->Overset = null;
		$placement->OversetChars = null;
		$placement->OversetLines = null;
		$placement->Layer = null;
		$placement->Content = '';
		$placement->Edition = null;
		$placement->ContentDx = 0;
		$placement->ContentDy = 0;
		$placement->ScaleX = null;
		$placement->ScaleY = null;
		$placement->PageSequence = null;
		$placement->PageNumber = null;
		$placement->Tiles = array();
		$placement->FormWidgetId = $formWidgetId;

		return $placement;

	}

	/**
	 * Construct the Contained Relation object given the parent(form) and the child(the placement).
	 *
	 * @param int $parentId The Id of the parent object where the child will be contained in.
	 * @param int $placedObjId The Id of the child object to be contained by the parent.
	 * @return Relation Relation object
	 */
	public static function composeContainedRelation( $parentId, $placedObjId )
	{
		$relation = new Relation();
		$relation->Parent = $parentId;
		$relation->Child = $placedObjId;
		$relation->Type = 'Contained';
		$relation->ParentVersion = null;
		$relation->ChildVersion = null;
		$relation->Geometry = null;
		$relation->Rating = null;
		$relation->Targets = null;

		return $relation;
	}

	/**
	 * Create an Image object.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $imageName To give the article a name. Pass NULL to auto-name it: 'BuildTestImage'+<datetime>
	 * @return Object|null The Image created.Null when Image failed to be created.
	 */
	public function createPublishFormPlacedImage( $stepInfo, $imageName=null )
	{
		$publication = $this->vars['BuildTest_MultiChannelPublishing']['publication'];

		// Retrieve the State.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->vars['BuildTest_MultiChannelPublishing']['ticket'] );

		$category = BizObjectComposer::getFirstCategory( $user, $publication->Id );

		// Flush the states cache to ensure we retrieve the latest from the Database.
		require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
		BizWorkflow::flushStatesCache();

		require_once BASEDIR.'/server/bizclasses/BizObjectComposer.class.php';
		$state = BizObjectComposer::getFirstState( $user, $publication->Id, null, null, 'Image' );
		
		//Transfer Server
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		
		// Determine unique image name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;
		$imageName = !is_null( $imageName ) ? $imageName : 'Image '.$postfix;

		// Create image.
		$objImg = new Object();
		$objImg->MetaData = new MetaData();
		$objImg->MetaData->BasicMetaData = new BasicMetaData();
		$objImg->MetaData->BasicMetaData->ID = null;
		$objImg->MetaData->BasicMetaData->DocumentID = null;
		$objImg->MetaData->BasicMetaData->Name = $imageName;
		$objImg->MetaData->BasicMetaData->Type = 'Image';
		$objImg->MetaData->BasicMetaData->Publication = new Publication();
		$objImg->MetaData->BasicMetaData->Publication->Id = $publication->Id;
		$objImg->MetaData->BasicMetaData->Publication->Name = $publication->Name;
		$objImg->MetaData->BasicMetaData->Category = new Category();
		$objImg->MetaData->BasicMetaData->Category->Id = $category->Id;
		$objImg->MetaData->BasicMetaData->Category->Name = $category->Name;
		$objImg->MetaData->BasicMetaData->ContentSource = null;
		$objImg->MetaData->RightsMetaData = new RightsMetaData();
		$objImg->MetaData->RightsMetaData->CopyrightMarked = false;
		$objImg->MetaData->RightsMetaData->Copyright = null;
		$objImg->MetaData->RightsMetaData->CopyrightURL = null;
		$objImg->MetaData->SourceMetaData = new SourceMetaData();
		$objImg->MetaData->SourceMetaData->Credit = null;
		$objImg->MetaData->SourceMetaData->Source = null;
		$objImg->MetaData->SourceMetaData->Author = null;
		$objImg->MetaData->ContentMetaData = new ContentMetaData();
		$objImg->MetaData->ContentMetaData->Description = 'Created by BuildTest class '.__CLASS__;
		$objImg->MetaData->ContentMetaData->DescriptionAuthor = null;
		$objImg->MetaData->ContentMetaData->Keywords = array();
		$objImg->MetaData->ContentMetaData->Slugline = null;
		$objImg->MetaData->ContentMetaData->Format = 'image/jpeg';
		$objImg->MetaData->ContentMetaData->Columns = null;
		$objImg->MetaData->ContentMetaData->Width = null;
		$objImg->MetaData->ContentMetaData->Height = null;
		$objImg->MetaData->ContentMetaData->Dpi = null;
		$objImg->MetaData->ContentMetaData->LengthWords = null;
		$objImg->MetaData->ContentMetaData->LengthChars = null;
		$objImg->MetaData->ContentMetaData->LengthParas = null;
		$objImg->MetaData->ContentMetaData->LengthLines = null;
		$objImg->MetaData->ContentMetaData->PlainContent = null;
		$objImg->MetaData->ContentMetaData->FileSize = 179508;
		$objImg->MetaData->ContentMetaData->ColorSpace = null;
		$objImg->MetaData->ContentMetaData->HighResFile = null;
		$objImg->MetaData->ContentMetaData->Encoding = null;
		$objImg->MetaData->ContentMetaData->Compression = null;
		$objImg->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$objImg->MetaData->ContentMetaData->Channels = null;
		$objImg->MetaData->ContentMetaData->AspectRatio = null;
		$objImg->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$objImg->MetaData->WorkflowMetaData->Deadline = null;
		$objImg->MetaData->WorkflowMetaData->Urgency = null;
		$objImg->MetaData->WorkflowMetaData->Modifier = null;
		$objImg->MetaData->WorkflowMetaData->Modified = null;
		$objImg->MetaData->WorkflowMetaData->Creator = null;
		$objImg->MetaData->WorkflowMetaData->Created = null;
		$objImg->MetaData->WorkflowMetaData->Comment = null;
		$objImg->MetaData->WorkflowMetaData->State = new State();
		$objImg->MetaData->WorkflowMetaData->State = $state;
		$objImg->MetaData->WorkflowMetaData->RouteTo = null;
		$objImg->MetaData->WorkflowMetaData->LockedBy = null;
		$objImg->MetaData->WorkflowMetaData->Version = null;
		$objImg->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$objImg->MetaData->WorkflowMetaData->Rating = null;
		$objImg->MetaData->WorkflowMetaData->Deletor = null;
		$objImg->MetaData->WorkflowMetaData->Deleted = null;
		$objImg->MetaData->ExtraMetaData = null;
		$objImg->Relations = array();
		$objImg->Pages = null;
		$objImg->Files = array();
		$objImg->Files[0] = new Attachment();
		$objImg->Files[0]->Rendition = 'native';
		$objImg->Files[0]->Type = 'image/jpeg';
		$objImg->Files[0]->Content = null;
		$objImg->Files[0]->FilePath = '';
		$objImg->Files[0]->FileUrl = null;
		$objImg->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/testdata/rec#001_att#000_native.jpg';
		$transferServer->copyToFileTransferServer( $inputPath, $objImg->Files[0] );
		$objImg->Messages = null;
		$objImg->Elements = array();
		$objImg->Targets = null;
		$objImg->Renditions = null;
		$objImg->MessageList = null;

		return $this->createObject( $objImg, $stepInfo );
	}
	
	/**
	 * Creates a new PublishingFormTemplateObject.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $templateName To give the template a name. Pass NULL to auto-name it: 'BuildTestPublishFormTemplate'+<datetime>
	 * @return null|Object The created Object or null if unsuccessful.
	 */
	public function createPublishFormTemplateObject( $stepInfo, $templateName=null )
	{
		$publication = $this->vars['BuildTest_MultiChannelPublishing']['publication'];
		$issue = $this->vars['BuildTest_MultiChannelPublishing']['webIssue'];

		// Retrieve the State.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->vars['BuildTest_MultiChannelPublishing']['ticket'] );

		// Flush the states cache to ensure we retrieve the latest from the Database.
		require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
		BizWorkflow::flushStatesCache();

		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		$state = BizObjectComposer::getFirstState($user, $publication->Id, null, null, 'PublishFormTemplate');

		$categoryId = BizObjectComposer::getFirstCategory($user, $publication->Id);

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$objectPublication = new Publication();
		$objectPublication->Id = $publication->Id;
		$objectPublication->Name = $publication->Name;
		
		// Determine the template name:
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;
		$templateName = !is_null( $templateName ) ? $templateName : 'PublishFormTemplate '.$postfix;

		// MetaData
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->BasicMetaData->Name = $templateName;
		$metaData->BasicMetaData->Type = 'PublishFormTemplate';
		$metaData->BasicMetaData->Publication = $objectPublication;
		$metaData->BasicMetaData->Category = $categoryId;
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->ContentMetaData->Description = 'Temporary template to simulate multi '.
													'channel publishing form template, '.
													'Created by BuildTest class '.__CLASS__;
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->WorkflowMetaData->State = $state;
		$metaData->ExtraMetaData = array();

		// Get the issue object for the publication channel:
		$pubChannel = $this->vars['BuildTest_MultiChannelPublishing']['webPubChannel'];

		$templateTarget = new Target();
		$templateTarget->PubChannel = new PubChannel($pubChannel->Id, $pubChannel->Name); // Send the correct object type
		$templateTarget->Issue = new Issue($issue->Id, $issue->Name, $issue->OverrulePublication); // Send the correct object type

		$templateObj = new Object();
		$templateObj->MetaData = $metaData;
		$templateObj->Targets = array( $templateTarget );

		return $this->createObject( $templateObj, $stepInfo );
	}

	/**
	 * Deletes the object.
	 *
	 * @param int $objId The id of the object to be removed.
	 * @param string $stepInfo Extra logging info.
	 * @param string &$errorReport To fill in the error message if there's any during the delete operation.
	 * @param bool $permanent Whether or not to delete the object permanently.
	 * @param array $areas The areas to test against.
	 */
	public function deleteObject( $objId, $stepInfo, &$errorReport, $permanent=true, $areas=array('Workflow'))
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$request->IDs = array($objId);
		$request->Permanent = $permanent;
		$request->Areas = $areas;
		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		if( is_null( $response ) ) {
			return false;
		}
		
		$deleteSuccessful = true;
		if( $response->Reports && count( $response->Reports ) > 0 ) {
			foreach( $response->Reports as $report ) {
				$errorReport .= 'Failed deleted ObjectID:"' . $report->BelongsTo->ID . '" </br>';
				$errorReport .= 'Reason:';
				if( $report->Entries ) foreach( $report->Entries as $reportEntry ) {
					$errorReport .= $reportEntry->Message . '</br>';
				}
				$errorReport .= '</br>';
			}
			$deleteSuccessful = false;
		}
		return $deleteSuccessful;
	}

	/**
	 * Restore an Object.
	 *
	 * Restores a previously removed object (moved to the Trashcan.
	 *
	 * @static
	 * @param integer $objectId The ID of the Object to be restored.
	 * @param string $stepInfo Extra logging info.
	 * @param string &$errorReport To fill in the error message if there's any during the restore operation.
	 * @return bool Whether or not restoring the object was succesful.
	 */
	public function restoreObject( $objectId, $stepInfo, &$errorReport )
	{
		// Attempt to restore the object.
		require_once BASEDIR.'/server/services/wfl/WflRestoreObjectsService.class.php';
		$request = new WflRestoreObjectsRequest();
		$request->Ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$request->IDs = array($objectId);
		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		if( is_null( $response ) ) {
			return false;
		}

		$deleteSuccessful = true;
		if( $response->Reports && count( $response->Reports ) > 0 ) {
			foreach( $response->Reports as $report ) {
				$errorReport .= 'Failed to restore Object with ID:"' . $report->BelongsTo->ID . '" </br>';
				$errorReport .= 'Reason:';
				if( $report->Entries ) foreach( $report->Entries as $reportEntry ) {
					$errorReport .= $reportEntry->Message . '</br>';
				}
				$errorReport .= '</br>';
			}
			$deleteSuccessful = false;
		}
		return $deleteSuccessful;
	}

	/**
	 * Logs the result message for these test cases.
	 *
	 * @static
	 * @param String $status The status (level) to be written.
	 * @param String $message The message to be written
	 * @param String $configTip The tip to be written.
	 */
	public static function setResult( $status, $message, $configTip='' )
	{
		$configTip = $configTip;
		$level = $status == 'NOTINSTALLED' ? 'WARN' : $status;
		$level = $status == 'FATAL' ? 'ERROR' : $status;
		LogHandler::Log( 'wwtest', $level, $message );
	}

	/**
	 * Allows the manual removing of a ObjectRelation / Placement.
	 *
	 * @static
	 * @param integer $formId The PublishForm ID.
	 * @param integer $placementObjectId The ID of the placed object.
	 * @param string $type The type of relation to be removed. Defaults to 'Placed'.
	 */
	public static function removeRelation($formId, $placementObjectId, $type = 'Placed')
	{
		// Attempt to remove the Placement / Object Relation.
		require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
		DBObjectRelation::deleteObjectRelation($formId, $placementObjectId, $type);
	}

	/**
	 * Construct a Relation object and call CreateObjectRelations service.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string $parentId
	 * @param string $childId
	 * @param string $relationType
	 * @param Target[] $targets
	 * @param WflCreateObjectRelationsResponse|null
	 */
	public function createRelationObject( $stepInfo, $parentId, $childId, $relationType, $targets=null )
	{
		$relation = new Relation();
		$relation->Parent = $parentId;
		$relation->Child = $childId;
		$relation->Type = $relationType;
		$relation->Placements = null;
		$relation->ParentVersion = null;
		$relation->ChildVersion = null;
		$relation->Geometry = null;
		$relation->Rating = null;
		$relation->Targets = $targets;
		$relation->ParentInfo = null;
		$relation->ChildInfo = null;

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$request->Relations = array( $relation );
		
		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		
		return isset($response->Relations[0]) ? $response->Relations[0] : null;
	}

	/**
	 * Creates a new Admin Issue.
	 *
	 * @param string $stepInfo
	 * @param int $pubId
	 * @param int $pubChannelId
	 * @param array $newIssues
	 * @return AdmCreateIssuesResponse The Issue(s) created.
	 */
	public function createIssues( $stepInfo, $pubId, $pubChannelId, $newIssues )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$request = new AdmCreateIssuesRequest();
		$request->Ticket          = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$request->RequestModes    = array( 'GetIssues' );
		$request->PublicationId   = $pubId;
		$request->PubChannelId    = $pubChannelId;
		$request->Issues          = $newIssues;

		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		if( is_null( $response ) ) {
			return false;
		}
		return $response;
	}

	/**
	 * Performs a GetObjects service call.
	 *
	 * @param string $stepInfo
	 * @param array $ids List of Object ids.
	 * @param bool $lock True to lock the file; False otherwise.
	 * @param string $rendition The rendition of the file requested.
	 * @param null|array $requestInfo List of other info such as MetaData, Relations and etc.
	 * @param null|array $haveVersions List of object's version.
	 * @param null|array $areas Area where the object resides in. 'Workflow' or 'Trash'
	 * @return null|GetObjectResponse
	 */
	public function getObjects( $stepInfo, $ids, $lock, $rendition, $requestInfo, $haveVersions=null, $areas=null  )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$request->IDs = $ids;
		$request->Lock = $lock;
		$request->Rendition = $rendition;
		$request->RequestInfo = $requestInfo;
		$request->HaveVersions = $haveVersions;
		$request->Areas = $areas;
		$request->EditionId = null;

		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		return $response ? $response : null;

	}

	/**
	 * To delete a list of Term Entities and Terms belong to MultiChannelPublishingSample provider.
	 *
	 * @param string $provider The Autocomplete provider of the Term Entities and Terms to be deleted.
	 */
	public function clearAutocompleteTermEntitiesAndTerms( $provider )
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermsService.class.php';

		// Delete the Terms.
		$service = new AdmDeleteAutocompleteTermsService();
		$request = new AdmDeleteAutocompleteTermsRequest();
		$request->Ticket = $this->ticket;

		$termEntities = DBAdmAutocompleteTermEntity::getTermEntityByProvider( $provider );
		if( $termEntities ) foreach( $termEntities as $termEntity ) {
			$terms = array();
			$admTerms = DBAdmAutocompleteTerm::getTermsByTermEntityId( $termEntity->Id );
			if( $admTerms ) foreach( $admTerms as $admTerm ) {
				$terms[] = $admTerm->DisplayName;
			}
			$request->TermEntity = $termEntity;
			$request->Terms = $terms;
			$service->execute( $request );
		}

		// Delete the Term Entities.
		if( $termEntities ) {
			$service = new AdmDeleteAutocompleteTermEntitiesService();
			$request = new AdmDeleteAutocompleteTermEntitiesRequest();
			$request->Ticket = $this->ticket;
			$request->TermEntities = $termEntities;
			$service->execute( $request );
		}
	}
}
