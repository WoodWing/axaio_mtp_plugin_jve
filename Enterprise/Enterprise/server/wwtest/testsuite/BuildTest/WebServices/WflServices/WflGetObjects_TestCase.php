<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflGetObjects_TestCase extends TestCase
{
	/** @var string $ticket  */
	private $ticket = null;
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	/** @var string $ticket  */
	private $user;
	/** @var Object $wflArticle  */
	private $wflArticle = null;
		
	public function getDisplayName() { return 'Get Objects'; }
	public function getTestGoals()   { return 'Checks if GetObjects returns valid response as requested.'; }
	public function getTestMethods() { return 'Calls GetObjects service call with RequestInfo set to "NoMetaData" and rendition set to "thumb".'; }
	public function getPrio()        { return 8; }
	
	final public function runTest()
	{
		// Getting session variables
		// get ticket ( retrieved from wflLogon Test )
		$this->vars = $this->getSessionVariables();
		$this->ticket = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR', 'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}
		$suiteOpts = unserialize( TESTSUITE );
		$this->user = $suiteOpts['User'];

		require_once BASEDIR . '/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		$this->runGetObjectsTest();
	}

	/**
	 * Setup the test environment, runs the getObjects test and tear down the test environment.
	 */
	private function runGetObjectsTest()
	{
		if( $this->setupGetObjectsTest() ) {
			do {
				if( !$this->getObjectWithNoMetaData() )   { break; }
 			} while ( false );
		}
		$this->tearDownGetObjectsTest();
	}

	/**
	 * Creates object to prepare for the BuildTest.
	 *
	 * @return bool True when all the necessary objects have been successfully created; False otherwise.
	 */
	private function setupGetObjectsTest()
	{
		$retVal = true;
		// Create Article to do a GetObjects service call.
		$stepInfo = 'Create the Article object.';
		$this->wflArticle = $this->createArticle( $stepInfo );
		if( is_null( $this->wflArticle ) ) {
			$this->setResult( 'ERROR', 'Could not create the Article' );
			$retVal = false;
		}

		return $retVal;
	}

	/**
	 * Call GetObjects service call by setting the RequestInfo to be 'NoMetaData' and rendition to 'thumb'.
	 * The GetObjectsResponse is validated.
	 *
	 * @return bool
	 */
	private function getObjectWithNoMetaData()
	{
		$stepInfo = 'Calling GetObjects by requesting the Article thumb rendition and NoMetaData.';
		$ids = array( $this->wflArticle->MetaData->BasicMetaData->ID );
		$requestInfo = array( 'NoMetaData' ); // When 'NoMetaData is specified, only several properties in the BasicMD is returned.
		$response = $this->getObjects( $stepInfo, $ids, false, 'thumb', $requestInfo, null, array('Workflow') );
		$result = $this->validateGetObjectsResponse( $response );
		return $result;
	}

	/**
	 * Validates the response returned by the GetObjects service call.
	 * Since the RequestInfo in the request was set to 'NoMetaData', and rendition is set to 'thumb',
	 * not full Object tree should be returned. This function ensure that the minimum properties are returned
	 * in the MetaData->BasicMetaData, and the rest should all be null.
	 *
	 * @param GetObjectsResponse $response
	 * @return bool Whether the response was returned correctly; False otherwise.
	 */
	private function validateGetObjectsResponse( $response )
	{
		$result = true;
		do {
			foreach( $response->Objects as $object ) {
				foreach( array_keys( get_class_vars( 'Object' ) ) as $objAttr ) { // MetaData, Files, Relations, etc
					if( !is_null( $object->$objAttr ) ) {
						if( $objAttr != 'MetaData' && $objAttr != 'Files' ) {
							// error: $objAttr should be null
							$this->setResult( 'ERROR', $objAttr . ' is not Null which is wrong1.',
								'When RequestInfo for GetObjects request is set to "NoMetaData", "'.$objAttr.'" is '.
								'expected to be Null.');
							$result = false;
							break 3; // break from the 2 foreach loop and 1 do-while loop.
						}
					}
					if( $objAttr == 'MetaData' ) {
						if( is_null( $object->MetaData ) ) {
							foreach( array_keys( get_class_vars( 'MetaData' ) ) as $mdAttr ) { // BasicMetaData, ContentMetaData, etc
								if( is_null( $object->MetaData->$mdAttr ) ) {
									if( $mdAttr != 'BasicMetaData' ) {
										// error: $mdAttr should be null
										$this->setResult( 'ERROR', $mdAttr . ' is not Null which is wrong2.',
											'When RequestInfo for GetObjects request is set to "NoMetaData", "MetaData->'.
												$objAttr.'" is expected to be Null.');
										$result = false;
										break 4; // break from the 3 foreach loop and 1 do-while loop.
									}
								}
							}
						}
					}
				}
				if( !isset( $object->MetaData->BasicMetaData->ID ) ) {
					$this->setResult( 'ERROR', 'MetaData->BasicMetaData->ID is not set, which is wrong.',
						'When RequestInfo for GetObjects request is set to "NoMetaData", '.
						'MetaData->BasicMetaData->ID should be set.' );
					break 2; // break from 1 foreach loop and 1 do-while loop.
				}
				if( !isset( $object->MetaData->BasicMetaData->Name ) ) {
					$this->setResult( 'ERROR', 'MetaData->BasicMetaData->Name is not set, which is wrong.',
						'When RequestInfo for GetObjects request is set to "NoMetaData", '.
							'MetaData->BasicMetaData->Name should be set.' );
					break 2; // break from 1 foreach loop and 1 do-while loop.
				}
				if( !isset( $object->MetaData->BasicMetaData->Type ) ) {
					$this->setResult( 'ERROR', 'MetaData->BasicMetaData->Type is not set, which is wrong.',
						'When RequestInfo for GetObjects request is set to "NoMetaData", '.
							'MetaData->BasicMetaData->Type should be set.' );
					break 2; // break from 1 foreach loop and 1 do-while loop.
				}

			}
		} while( false );

		return $result;
	}

	/**
	 * Tears down the objects created in the {@link: setupGetObjectsTest()} function.
	 *
	 * @return bool
	 */
	private function tearDownGetObjectsTest()
	{
		$result = true;
		// Permanent delete Article.
		if( $this->wflArticle ) {
			$id = $this->wflArticle->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Article object.';
			if( !$this->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflArticle = null;
		}
		return $result;
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
		$user = $this->user;

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$publication = $this->vars['BuildTest_WebServices_WflServices']['publication'];
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
		$contentMD->Description = 'Temporary article to test for Workflow service GetObjects. '.
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
		$contentMD->Channels = 'Print';
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

		require_once BASEDIR.'/server/utils/TransferClient.class.php';
		$transferClient = new WW_Utils_TransferClient( $this->ticket );
		if( !$transferClient->uploadFile( $fileAttach ) ) {
			$articleName = $metaData->BasicMetaData->Name;
			$this->setResult( 'ERROR', 'Failed uploading native file for article "' . $articleName . '".', 'Check if all the Transfer Server settings are set in configserver.php.' );
		}

		// Target
		$target = $this->vars['BuildTest_WebServices_WflServices']['printTarget'];

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
		$request->Objects = array( $object );

		$response = $this->utils->callService( $this, $request, $stepInfo );
		return isset($response->Objects[0]) ? $response->Objects[0] : null;
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
	private function getObjects( $stepInfo, $ids, $lock, $rendition, $requestInfo, $haveVersions=null, $areas=null  )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $ids;
		$request->Lock = $lock;
		$request->Rendition = $rendition;
		$request->RequestInfo = $requestInfo;
		$request->HaveVersions = $haveVersions;
		$request->Areas = $areas;
		$request->EditionId = null;

		$response = $this->utils->callService( $this, $request, $stepInfo );
		return $response ? $response : null;

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
	private function deleteObject( $objId, $stepInfo, &$errorReport, $permanent=true, $areas=array('Workflow'))
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array($objId);
		$request->Permanent = $permanent;
		$request->Areas = $areas;
		$response = $this->utils->callService( $this, $request, $stepInfo );
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