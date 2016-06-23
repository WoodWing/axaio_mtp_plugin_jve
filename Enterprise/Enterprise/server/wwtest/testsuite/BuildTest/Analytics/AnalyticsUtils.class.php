<?php

/**
 * Contains helper functions for the MultiChannelPublishing tests.
 *
 * @package 	Enterprise
 * @subpackage 	Testsuite
 * @since 		v9.4.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class AnalyticsUtils
{
	private $testCase = null;
	private $vars = null;
	private $ticket = null;
	private $utils = null;
	private $anaDirectory = null;
	
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
		$this->anaDirectory = TEMPDIRECTORY . '/Ana';

		$tip = 'Please enable the "WflLogon" entry and try again.';
		do {
			// Check LogOn ticket.
			$this->ticket = @$this->vars['BuildTest_Analytics']['ticket'];
			if( !$this->ticket ) {
				$testCase->setResult( 'ERROR',  'Could not find ticket to test with.', $tip );
				break;
			}

			// Check presence of test data.
			if( !isset($this->vars['BuildTest_Analytics']['publication'] ) ||
				!isset($this->vars['BuildTest_Analytics']['category'] ) ||
				!isset($this->vars['BuildTest_Analytics']['issue'] ) ||
				!isset($this->vars['BuildTest_Analytics']['printTarget'] ) ||
				!isset($this->vars['BuildTest_Analytics']['articleStatus'] ) ||
				!isset($this->vars['BuildTest_Analytics']['dossierStatus'] )
			) {
				$testCase->setResult( 'ERROR',  'Could not find data to test with.', $tip );
				break;
			}
			// Make sure there's no other jobs in the queue before starting this test.
			$this->emptyServerJobsQueue();

			if( !$this->checksIfAnaDirExists() ) {
				$testCase->setResult( 'ERROR',  'Failed creating directory "'. $this->anaDirectory . '".',
					'Please make sure the directory "'.TEMPDIRECTORY.'" is writable.' );
				break;
			}

			if( !$this->clearAnaDir( false ) ) {
				$testCase->setResult( 'ERROR',  'Failed cleaning directory "'. $this->anaDirectory . '".' );
				break;
			}

			$valid = true;
		} while( false );

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		return $valid;
	}

	/**
	 * Empty the server jobs in the job queue created by this test.
	 *
	 * In case of error in the BuildTest, the server jobs cannot be processed,
	 * they are left in the queue. This function clears all the jobs in the queue
	 * to make sure that the next run of the test, it starts with a cleared queue.
	 *
	 * This function can be called before and after the test.
	 */
	public function emptyServerJobsQueue()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		// Clear all the jobs created in the job queue.
		$bizServerJob = new BizServerJob;
		$jobs = $bizServerJob->listJobs();
		if ( count( $jobs ) > 0 ) {
			foreach( array_keys( $jobs ) as $jobId ) {
				$bizServerJob->deleteJob( $jobId );
			}
		}
	}

	/**
	 * Checks if $this->anaDirectory directory exists.
	 *
	 * The function creates the directory when it doesn't exists yet.
	 *
	 * @return bool Returns true when the directory exists, false otherwise.
	 */
	public function checksIfAnaDirExists()
	{
		require_once BASEDIR .'/server/utils/FolderUtils.class.php';
		if ( !file_exists( $this->anaDirectory ) && !is_dir( $this->anaDirectory )) {
			$result = FolderUtils::mkFullDir( $this->anaDirectory );
		} else {
			$result = true;
		}
		return $result;
	}

	/**
	 * Checks if $this->anaDirectory is empty.
	 *
	 * Function cleans the directory and remove the directory when it is not empty.
	 *
	 * @param bool $removeTopFolder Whether to remove top folder 'Ana'.
	 * @return bool Returns true when there's no files in the directory, false otherwise.
	 */
	public function clearAnaDir( $removeTopFolder = true )
	{
		require_once BASEDIR .'/server/utils/FolderUtils.class.php';
		if( !FolderUtils::isEmptyDirectory( $this->anaDirectory )) {
			$result = FolderUtils::cleanDirRecursive( $this->anaDirectory, $removeTopFolder );
		} else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Run the job scheduler by calling the jobindex.php.
	 *
	 * @param int $maxexectime The max execution time of jobindex.php in seconds.
	 */
	public function runServerJobs( $maxexectime = 5 )
	{
		$retVal = true;
		try {
			require_once 'Zend/Http/Client.php';
			$url = LOCALURL_ROOT.INETROOT.'/jobindex.php';
			$client = new Zend_Http_Client();
			$client->setUri( $url );
			$client->setParameterGet( 'maxexectime', $maxexectime );
			$response = $client->request( Zend_Http_Client::GET );

			if( !$response->isSuccessful() ) {
				$this->testCase->setResult( 'ERROR', 'Failed calling jobindex.php: '.$response->getHeadersAsString( true, '<br/>' ) );
				$retVal = false;
			}
		} catch ( Zend_Http_Client_Exception $e ) {
			$this->testCase->setResult( 'ERROR', 'Failed calling jobindex.php: '.$e->getMessage() );
			$retVal = false;
		}

		// This is needed to ensure that the jobindex.php ( serverjob ) have enough time to finishes its job before
		// returning to the test. Otherwise the sub-sequent tests might fail if the jobs are not yet completed.
		LogHandler::Log('AnalyticsBuildTest','INFO',__METHOD__.
								': Sleeping for 3 seconds to ensure the jobindex can finish its job.');
		sleep( 3 );
		return $retVal;
	}

	/**
	 * Returns the current date time stamp.
	 *
	 * The format returned is:
	 * YrMthDay HrMinSec MiliSec
	 * For example:
	 * 140707 173315 176
	 *
	 * @return string
	 */
	public function getUniqueTimeStamp()
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$uniqueTimeStamp = date( 'ymd His', $microTime[1] ).' '.$miliSec;

		return $uniqueTimeStamp;
	}

	/**
	 * Creates a complete but empty MetaData data tree in memory.
	 * This is to simplify adding properties to an Object's MetaData element.
	 *
	 * @return MetaData
	 */
	public function buildEmptyMetaData()
	{
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->ExtraMetaData = array();
		return $metaData;
	}

	/**
	 * Returns Publication based on $this->vars['BuildTest_Analytics']['publication'].
	 *
	 * @return Publication
	 */
	public function getPublication()
	{
		$publicationInfo   = $this->vars['BuildTest_Analytics']['publication'];
		$publication = new Publication();
		$publication->Id = $publicationInfo->Id;
		$publication->Name = $publicationInfo->Name;
		return $publication;
	}

	/**
	 * Returns Category based on $this->vars['BuildTest_WebServices_WflServices']['category']
	 *
	 * @return Category
	 */
	private function getCategory()
	{
		$categoryInfo      = $this->vars['BuildTest_Analytics']['category'];
		$category = new Category();
		$category->Id = $categoryInfo->Id;
		$category->Name =  $categoryInfo->Name;
		return $category;
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
		$request->Ticket = $this->ticket;
		$request->Lock = $lock;
		$request->Messages = array();
		$request->ReadMessageIDs = false;
		$request->Objects = array( $object );

		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		return isset($response->Objects[0]) ? $response->Objects[0] : null;
	}

	/**
	 * Creates an Image.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param null|string $imageName When not given, function will compose one with datetime postfix.
	 * @param null|array $relations List of Relation for this image if there's any. Default is empty.
	 * @param null|array $targets List of Target for this image if there's any. Default is empty.
	 * @param null|Category $category When not given, it will be retrieved from the BuildTest session 'BuildTest_WebServices_WflServices'
	 * @param null|State $imageStatus State of the image to be created. When not given, it takes from BuildTest session.
	 * @param null|string $routeTo Auto route to which user for this image to be created.
	 * @return null|Object
	 */
	public function createImageObject( $stepInfo, $imageName=null, $relations=null, $targets=null, $category=null,
	                                   $imageStatus=null, $routeTo=null )
	{

		$imgStatusInfo     = $this->vars['BuildTest_Analytics']['imageStatus'];
		$imageStatus = !is_null( $imageStatus ) ? $imageStatus : new State( $imgStatusInfo->Id, $imgStatusInfo->Name );

		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'image/jpeg';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = null;

		$inputPath = dirname(__FILE__).'/Analytics_TestData/image1.jpg'; // just pick an image

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );

		$imageName = $imageName ? $imageName : 'Image '. $this->getUniqueTimeStamp();

		$routeTo = $routeTo ? $routeTo : null;

		$imageObj = new Object();
		$imageObj->MetaData = $this->buildEmptyMetaData();
		$imageObj->MetaData->BasicMetaData->Name = $imageName;
		$imageObj->MetaData->BasicMetaData->Type = 'Image';
		$imageObj->MetaData->BasicMetaData->Publication = $this->getPublication();
		$imageObj->MetaData->BasicMetaData->Category = $category ? $category : $this->getCategory();
		$imageObj->MetaData->ContentMetaData->Format = 'image/jpeg';
		$imageObj->MetaData->ContentMetaData->FileSize = filesize($inputPath);
		$imageObj->MetaData->WorkflowMetaData->State = $imageStatus;
		$imageObj->MetaData->WorkflowMetaData->RouteTo = $routeTo;

		if( $relations ) {
			$imageObj->Relations = $relations;
		}

		$imageObj->Pages = null;
		$imageObj->Files = array();
		$imageObj->Files[0] = $attachment;
		$imageObj->Messages = null;
		$imageObj->Elements = array();
		if( $targets ) {
			$imageObj->Targets = $targets;
		}
		$imageObj->Renditions = null;
		$imageObj->MessageList = null;

		return $this->createObject( $imageObj, $stepInfo );
	}

	/**
	 * Creates an article.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $articleName To give the article a name. Pass NULL to auto-name it: 'BuildTestArticle'+<datetime>
	 * @param array $relations List of Relation for the article. Default is empty(null).
	 * @param array $targets List of Target for the article. Default is empty(null).
	 * @return null|Object The created article or null if unsuccessful.
	 */
	public function createArticleObject( $stepInfo, $articleName=null, $relations=null, $targets=null )
	{
		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->ticket );

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$publication = $this->getPublication();

		// Determine unique article name.
		$postfix = $this->getUniqueTimeStamp();
		$articleName = is_null( $articleName ) ? 'Article '.$postfix : $articleName;

		// BasicMetaData
		$basicMD = new BasicMetaData();
		$basicMD->ID = null;
		$basicMD->DocumentID = null;
		$basicMD->Name = $articleName;
		$basicMD->Type = 'Article';
		$basicMD->Publication = $publication;
		$basicMD->Category = BizObjectComposer::getFirstCategory( $user, $publication->Id) ;
		$basicMD->ContentSource = null;

		// ContentMetaData
		$contentMD = new ContentMetaData();
		$contentMD->Description = 'Temporary article to test for Analytics. Created by BuildTest class '.__CLASS__;
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
		$contentMD->Channels = '';
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
		$metaData = $this->buildEmptyMetaData();
		$metaData->BasicMetaData = $basicMD; // Overwrite the BasicMetaData.
		$metaData->ContentMetaData->Slugline = 'A test slugline';
		$metaData->WorkflowMetaData = $workflowMD;

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
		$inputPath = dirname(__FILE__).'/Analytics_TestData/rec#001_att#000_native.wcml';
		$transferServer->copyToFileTransferServer( $inputPath, $fileAttach );

		// Relation
		$relations = $relations ? $relations : null;

		// Target
		$targets = $targets ? $targets : null;

		// Create the Article object.
		$articleObj = new Object();
		$articleObj->MetaData = $metaData;
		$articleObj->Relations = $relations;
		$articleObj->Pages = null;
		$articleObj->Files = array( $fileAttach );
		$articleObj->Messages = null;
		$articleObj->Elements = null;
		$articleObj->Targets = $targets;
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
	public function createDossierObject( $stepInfo, $dossierName = null, $publicationChannel='web' )
	{
		$publication = $this->vars['BuildTest_Analytics']['publication'];
		$issue = $this->vars['BuildTest_Analytics']['webIssue'];

		// Retrieve the State.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->vars['BuildTest_Analytics']['ticket'] );

		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		$state = BizObjectComposer::getFirstState($user, $publication->Id, null, null, 'Dossier');
		$category = BizObjectComposer::getFirstCategory($user, $publication->Id);

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$objectPublication = new Publication();
		$objectPublication->Id = $publication->Id;
		$objectPublication->Name = $publication->Name;

		// Determine uninque dossier name.
		$postfix = $this->getUniqueTimeStamp();
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
		$pubChannel = $this->vars['BuildTest_Analytics']['webPubChannel'];

		if( $publicationChannel == 'web' ) {
			$templateTarget = new Target();
			$templateTarget->PubChannel = new PubChannel($pubChannel->Id, $pubChannel->Name); // Send the correct type of object
			$templateTarget->Issue = new Issue($issue->Id, $issue->Name, $issue->OverrulePublication); // Send the correct type of object
		} else if( $publicationChannel == 'print' ) {
			$pubChannelInfo = $this->vars['BuildTest_Analytics']['printTarget']->PubChannel; // Take from the print channel
			$issueInfo = $this->vars['BuildTest_Analytics']['issue']; // Take from the print channel
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
		$request->Ticket = $this->ticket;
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

}