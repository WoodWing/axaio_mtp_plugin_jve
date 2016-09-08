<?php

/**
 * Contains helper functions for the MultiChannelPublishing tests.
 *
 * @package 	Enterprise
 * @subpackage 	Testsuite
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class WW_TestSuite_BuildTest_WebServices_WflServices_Utils
{
	/** @var TestCase $testCase */
	private $testCase = null;
	/** @var string[] $vars  */
	private $vars = null;
	/** @var string $ticket  */
	private $ticket = null;
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	/** @var callable $requestComposer  */
	private $requestComposer = null;
	/** @var string $namePrefix  */
	private $namePrefix = null;

	/**
	 * Initializes the utils to let it work for a TestCase.
	 *
	 * @param TestCase $testCase
	 * @param string $namePrefix Prefix to use for autofill names when creating entities.
	 * @param string|null $protocol [v10.0.0] The used protocol for service calls. (Options: SOAP or JSON.) If null a regular service call is made.
	 * @return bool Whether or not all session variables are complete.
	 */
	public function initTest( TestCase $testCase, $namePrefix = 'BT ', $protocol = null )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$valid = false;
		$this->vars = $testCase->getSessionVariables();
		$this->testCase = $testCase;
		$this->expectedError = null;
		$this->namePrefix = $namePrefix;

		$tip = 'Please enable the "Setup test data" entry (WflLogon_TestCase.php) and try again. '.
			'Please also check the TESTSUITE setting in the configserver.php file.';
		do {		
			// Check LogOn ticket.
			$this->ticket = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
			if( !$this->ticket ) {
				$testCase->setResult( 'ERROR',  'Could not find ticket to test with.', $tip );
				break;
			}
			
			// Check presence of test data.
			if( !isset($this->vars['BuildTest_WebServices_WflServices']['publication'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['category'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['printPubChannel'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['printIssue'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['printTarget'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['imageStatus'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['articleStatus'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['dossierStatus'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['layoutStatus'] ) ||
				!isset($this->vars['BuildTest_WebServices_WflServices']['articleTemplateStatus'] )
			) {
				$testCase->setResult( 'ERROR',  'Could not find data to test with.', $tip );
				break;
			}
			
			$valid = true;
		} while( false );

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( $protocol );

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
	 *
	 * @param callable $callback
	 */
	public function setRequestComposer( $callback ) 
	{
		$this->requestComposer = $callback;
	}
	
	/**
	 * Calls a web service using given request.
	 *
	 * @param object $request
	 * @param string $stepInfo Logical test description.
	 * @return mixed A corresponding response object when service was successful or NULL on error.
	 * @throws BizException on unexpected system response
	 */
	public function callService( $request, $stepInfo )
	{
		// Copy and clear expected error since callService() can throw exception.
		$expectedError = $this->expectedError;
		$this->expectedError = null; // reset (has to be set per function call)
		
		// Copy and clear request composer since it can throw exception.
		$requestComposer = $this->requestComposer;
		$this->requestComposer = null; // reset (has to be set per function call)
		
		// Let caller overrule request composition.
		if( $requestComposer ) {
			call_user_func( $requestComposer, $request );
		}
		
		// Call the web service.
		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $expectedError, true );

		return $response;
	}
	
	/**
	 * Composes a name based on a given prefix and a generated time stamp.
	 *
	 * @return string Generated name
	 */
	private function composeName()
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$name = $this->namePrefix.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;
		return $name;
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
	 * Creates a new category.
	 *
	 * @param int $pubId Publication Id for the category.
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $name Name of the category. When not given, function will compose one with datetime postfix.
	 * @param int|null $issueId Optional issueId for the Category (Overrule Issues)
	 * @return Category on success
	 * @throws BizException on failure
	 */
	public function createCategory( $pubId, $stepInfo, $name=null, $issueId = 0 )
	{
		$section = new AdmSection();
		$section->Name = !is_null($name) ? $name : $this->composeName();

		require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';
		$request = new AdmCreateSectionsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $pubId;
		$request->IssueId = $issueId;
		$request->Sections = array( $section );

		$response = $this->callService( $request, $stepInfo );

		$this->testCase->assertAttributeInternalType( 'array', 'Sections', $response );
		$this->testCase->assertCount( 1, $response->Sections );
		$this->testCase->assertInstanceOf( 'AdmSection', $response->Sections[0] );

		$section = $response->Sections[0];
		$category = new Category();
		$category->Id = $section->Id;
		$category->Name = $section->Name;

		return $category;
	}

	/**
	 * Deletes an Category.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $publicationId
	 * @param integer $categoryId
	 * @param integer $issueId
	 */
	public function deleteCategory( $stepInfo, $publicationId, $categoryId, $issueId = 0 )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
		$request = new AdmDeleteSectionsRequest();
		$request->Ticket        = $this->ticket;
		$request->PublicationId = $publicationId;
		$request->IssueId       = $issueId;
		$request->SectionIds    = array( $categoryId );

		$this->callService( $request, $stepInfo );

		// Try to retrieve the deleted test Section, which should fail.
		require_once BASEDIR.'/server/services/adm/AdmGetSectionsService.class.php';
		$request = new AdmGetSectionsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->IssueId       = $issueId;
		$request->SectionIds = array($categoryId);
		
		$this->expectedError = '(S1056)';
		$stepInfo = 'Calling AdmGetSections to validate AdmDeleteSections as called before.';
		$this->callService( $request, $stepInfo );

		LogHandler::Log( 'BuildTestUtils', 'INFO', 'Completed validating AdmDeleteSections.' );
	}	

	/**
	 * Creates a new Object type status.
	 *
	 * @param string|null $statusName Name of the status. Null to use autofill name.
	 * @param string $objectType The object type for the status to be created.
	 * @param int $pubId Publication id of which the status will be bound to.
	 * @param int $issueId Overrule Issue id of which the status will be bound to.
	 * @return AdmStatus Object on success
	 * @throws BizException on failure
	 */
	public function createStatus( $statusName, $objectType, $publicationId, $nextStatusId = 0, $issueId = 0 )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		// TODO: call web service layer (instead of calling biz layer)
		try {
			require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
			$status = new AdmStatus(null, $statusName, $objectType, false, null, 'WoodWing Software');
			$status->Id = 0;
			$status->PublicationId	= $publicationId;
			$status->Type = $objectType;
			$status->Phase = 'Production';
			$status->Name = $statusName;
			$status->Produce = false;
			$status->Color = '#FFFF99';
			$status->NextStatusId = $nextStatusId;
			$status->SortOrder = 0;
			$status->IssueId = $issueId;
			$status->SectionId = 0;
			$status->DeadlineStatusId = 0;
			$status->DeadlineRelative = 0;
			$status->CreatePermanentVersion = false;
			$status->RemoveIntermediateVersions = false;
			$status->AutomaticallySendToNext = false;
			$status->ReadyForPublishing = false;
			$status->SkipIdsa = false;
		
			$statusCreated = BizAdmStatus::createStatus( $status );

		} catch( BizException $e ) {
			$this->testCase->throwError( $e->getMessage().'<br/>'.$e->getDetail() );
		}
		$this->testCase->assertInstanceOf( 'stdClass', $statusCreated );
		$statusId = $statusCreated->Id;
		$this->testCase->assertGreaterThan( 0, $statusId );
		
		// TODO: call web service layer (instead of calling biz layer)
		try {
			$statusCreated = BizAdmStatus::getStatusWithId( $statusId );
		} catch( BizException $e ) {
			$this->testCase->throwError( $e->getMessage().'<br/>'.$e->getDetail() );
			throw $e;
		}
		$this->testCase->assertInstanceOf( 'stdClass', $statusCreated );

		BizAdmStatus::restructureMetaDataStatusColor( $statusCreated->Id, $status->Color);
		$statusCreated->Color = $status->Color;
		$statusCreated->DefaultRouteTo = null;

		return $statusCreated;
	}

	/**
	 * Returns Publication based on $this->vars['BuildTest_WebServices_WflServices']['publication'].
	 *
	 * @return Publication
	 */
	public function getPublication()
	{
		$publicationInfo = $this->vars['BuildTest_WebServices_WflServices']['publication'];
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
		$categoryInfo = $this->vars['BuildTest_WebServices_WflServices']['category'];
		$category = new Category();
		$category->Id = $categoryInfo->Id;
		$category->Name = $categoryInfo->Name;
		return $category;
	}

	/**
	 * Gets a list of group id for user $userId.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param int $userId User id where the user's group to be retrieved.
	 * @return array List of group ids where the $userId belongs to, when success expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function getUserGroups( $stepInfo, $userId )
	{
		require_once BASEDIR . '/server/services/adm/AdmGetUserGroupsService.class.php';
		$request = new AdmGetUserGroupsRequest();
		$request->Ticket       = $this->ticket;
		$request->RequestModes = array();
		$request->UserId       = $userId;

		$expectedError = $this->expectedError;
		$response = $this->callService( $request, $stepInfo );

		$groupIds = null;
		if( !$expectedError ) {
			$groupIds = array();
			$this->testCase->assertAttributeInternalType( 'array', 'UserGroups', $response );
			$userGrps = $response->UserGroups;
			foreach( $userGrps as $userGrp ) {
				$groupIds[] = $userGrp->Id;
			}
		}
		return $groupIds;
	}

	/**
	 * Add the user into one or multiple user group(s) $groupIds.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param int $userId User id where the user will be added to the user groups given.
	 * @param array $groupIds List of group db id where the user will be added into.
	 * @throws BizException on unexpected system response
	 */
	public function addUserGroupToUser( $stepInfo, $userId, array $groupIds )
	{
		require_once BASEDIR . '/server/services/adm/AdmAddGroupsToUserService.class.php';
		$request = new AdmAddGroupsToUserRequest();
		$request->Ticket = $this->ticket;
		$request->UserId = $userId;
		$request->GroupIds = $groupIds;
		$this->callService( $request, $stepInfo );
	}

	/**
	 * Creates a new user.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $userName User short name. Null to use autofill name.
	 * @param string|null $fullName User full name. Null to use autofill name.
	 * @return AdmUser|null User object when success is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function createUser( $stepInfo, $userName=null, $fullName=null )
	{
		require_once BASEDIR . '/server/services/adm/AdmCreateUsersService.class.php';
		$userObj = new AdmUser();
		$userObj->Name      = !is_null($userName) ? $userName : $this->composeName();
		$userObj->FullName  = !is_null($fullName) ? $fullName : $userObj->Name . ' Fullname';
		$userObj->Password  = 'ww';
		$userObjs = array( $userObj );

		$request = new AdmCreateUsersRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Users  = $userObjs;

		$expectedError = $this->expectedError;
		$response = $this->callService( $request, $stepInfo );

		if( !$expectedError ) {
			$this->testCase->assertAttributeInternalType( 'array', 'Users', $response );
			$this->testCase->assertAttributeCount( 1, 'Users', $response ); // check $response->Users[0]
			$this->testCase->assertInstanceOf( 'AdmUser', $response->Users[0] );
		}
		return isset($response->Users[0]) ? $response->Users[0] : null;
	}

	/**
	 * Creates an object in the database.
	 *
	 * @param Object $object The object to be created.
	 * @param string $stepInfo Extra logging info.
	 * @param bool $lock Whether or not the lock the object.
	 * @return Object|null. Object when success is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	private function createObject( $object, $stepInfo, $lock = false )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = $lock;
		$request->Messages = null;
		$request->Objects = array( $object );

		$expectedError = $this->expectedError;
		$response = $this->callService( $request, $stepInfo );

		if( !$expectedError ) {
			$this->testCase->assertAttributeInternalType( 'array', 'Objects', $response );
			$this->testCase->assertAttributeCount( 1, 'Objects', $response ); // check $response->Objects[0]
			$this->testCase->assertInstanceOf( 'Object', $response->Objects[0] );
		}
		return isset($response->Objects[0]) ? $response->Objects[0] : null;
	}

	/**
	 * Creates an Image.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param null|string $imageName When not given, function will compose one with datetime postfix.
	 * @param null|Relation[] $relations List of Relation for this image if there's any. Default is empty.
	 * @param null|Target[] $targets List of Target for this image if there's any. Default is empty.
	 * @param null|Category $category When not given, it will be retrieved from the BuildTest session 'BuildTest_WebServices_WflServices'
	 * @param null|State $imageStatus State of the image to be created. When not given, it takes from BuildTest session.
	 * @param null|string $routeTo Auto route to which user for this image to be created.
	 * @return null|Object Image object when success is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function createImageObject( $stepInfo, $imageName=null, $relations=null, $targets=null, $category=null,
	                                   $imageStatus=null, $routeTo=null )
	{
		$imgStatusInfo = $this->vars['BuildTest_WebServices_WflServices']['imageStatus'];
		$imageStatus = !is_null( $imageStatus ) ? $imageStatus : new State( $imgStatusInfo->Id, $imgStatusInfo->Name );

		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'image/jpeg';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = null;

		$inputPath = dirname(__FILE__).'/testdata/trashcan.jpg'; // just pick an image

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );

		$imageObj = new Object();
		$imageObj->MetaData = $this->buildEmptyMetaData();
		$imageObj->MetaData->BasicMetaData->Name = !is_null($imageName) ? $imageName : $this->composeName();
		$imageObj->MetaData->BasicMetaData->Type = 'Image';
		$imageObj->MetaData->BasicMetaData->Publication = $this->getPublication();
		$imageObj->MetaData->BasicMetaData->Category = $category ? $category : $this->getCategory();
		$imageObj->MetaData->ContentMetaData->Format = 'image/jpeg';
		$imageObj->MetaData->ContentMetaData->FileSize = filesize($inputPath);
		$imageObj->MetaData->WorkflowMetaData->State = $imageStatus;
		$imageObj->MetaData->WorkflowMetaData->RouteTo = $routeTo ? $routeTo : null;

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
	 * @param string|null $articleName Name of article. Null to autofill name.
	 * @param Relation[]|null $relations List of Relation for the article. Default is empty(null).
	 * @param Target[]|null $targets List of Target for the article. Default is empty(null).
	 * @return Object|null Article object when success is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function createArticleObject( $stepInfo, $articleName=null, $relations=null, $targets=null )
	{
		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->ticket );

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$publication = $this->getPublication();

		// BasicMetaData
		$basicMD = new BasicMetaData();
		$basicMD->ID = null;
		$basicMD->DocumentID = null;
		$basicMD->Name = !is_null($articleName) ? $articleName : $this->composeName();
		$basicMD->Type = 'Article';
		$basicMD->Publication = $publication;
		$basicMD->Category = BizObjectComposer::getFirstCategory( $user, $publication->Id ) ;
		$basicMD->ContentSource = null;

		// ContentMetaData
		$contentMD = new ContentMetaData();
		$contentMD->Description = 'Temporary article to test for workflow services. '.
			'Created by BuildTest class '.__CLASS__;
		$contentMD->Keywords = array();
		$contentMD->Slugline = 'the headthe introthe body';
		$contentMD->Format = 'application/incopyicml';
		$contentMD->LengthWords = 6;
		$contentMD->LengthChars = 25;
		$contentMD->LengthParas = 3;
		$contentMD->LengthLines = null;
		$contentMD->PlainContent = 'the headthe introthe body';
		$contentMD->FileSize = 160706;
		$contentMD->Channels = 'MWPublishing';

		// WorkflowMetaData
		$state = BizObjectComposer::getFirstState( $user, $publication->Id, null, null, 'Article');
		$workflowMD = new WorkflowMetaData();
		$workflowMD->State = $state;

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
		$inputPath = dirname(__FILE__).'/testdata/rec#001_att#000_native.wcml';
		$transferServer->copyToFileTransferServer( $inputPath, $fileAttach );

		// Create the Article object.
		$articleObj = new Object();
		$articleObj->MetaData = $metaData;
		$articleObj->Relations = $relations ? $relations : null;
		$articleObj->Files = array( $fileAttach );
		$articleObj->Targets = $targets ? $targets : null;

		return $this->createObject( $articleObj, $stepInfo );
	}

	/**
	 * Creates a dossier.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $dossierName Name of template. Null to autofill name.
	 * @param Relation[]|null $relations Add relations to dossier. Null for none.
	 * @param Target[]|null $targets Assign dossier to targets. Null for none.
	 * @return Object|null Dossier template object when success is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function createDossierObject( $stepInfo, $dossierName=null, $relations=null, $targets=null )
	{
		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->ticket );

		$publication = $this->getPublication();

		$basicMD = new BasicMetaData();
		$basicMD->ID = null;
		$basicMD->DocumentID = null;
		$basicMD->Name = !is_null($dossierName) ? $dossierName : $this->composeName();
		$basicMD->Type = 'Dossier';
		$basicMD->Publication = $publication;
		$basicMD->Category = BizObjectComposer::getFirstCategory( $user, $publication->Id );
		$basicMD->ContentSource = null;

		$state = BizObjectComposer::getFirstState( $user, $publication->Id, null, null, 'Dossier' );
		$workflowMD = new WorkflowMetaData();
		$workflowMD->State = $state;

		// MetaData
		$metaData = $this->buildEmptyMetaData();
		$metaData->BasicMetaData = $basicMD; // Overwrite the BasicMetaData.
		$metaData->ContentMetaData->Slugline = 'A test slugline';
		$metaData->WorkflowMetaData = $workflowMD;

		// Create the dossier template object.
		$dossierObj = new Object();
		$dossierObj->MetaData = $metaData;
		$dossierObj->Relations = $relations ? $relations : null;
		$dossierObj->Targets = $targets ? $targets : null;

		return $this->createObject( $dossierObj, $stepInfo );
	}

	/**
	 * Creates a dossier template.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $dossierTemplateName Name of template. Null to autofill name.
	 * @param Relation[]|null $relations Add relations to dossier. Null for none.
	 * @param Target[]|null $targets Assign dossier to targets. Null for none.
	 * @return Object|null Dossier template object when success is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function createDossierTemplateObject( $stepInfo, $dossierTemplateName=null, $relations=null, $targets=null )
	{
		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->ticket );

		$publication = $this->getPublication();

		$basicMD = new BasicMetaData();
		$basicMD->ID = null;
		$basicMD->DocumentID = null;
		$basicMD->Name = !is_null($dossierTemplateName) ? $dossierTemplateName : $this->composeName();
		$basicMD->Type = 'DossierTemplate';
		$basicMD->Publication = $publication;
		$basicMD->Category = BizObjectComposer::getFirstCategory( $user, $publication->Id) ;
		$basicMD->ContentSource = null;

		$state = BizObjectComposer::getFirstState( $user, $publication->Id, null, null, 'DossierTemplate');
		$workflowMD = new WorkflowMetaData();
		$workflowMD->State = $state;

		// MetaData
		$metaData = $this->buildEmptyMetaData();
		$metaData->BasicMetaData = $basicMD; // Overwrite the BasicMetaData.
		$metaData->ContentMetaData->Slugline = 'A test slugline';
		$metaData->WorkflowMetaData = $workflowMD;

		// Create the dossier template object.
		$dossierTemplateObj = new Object();
		$dossierTemplateObj->MetaData = $metaData;
		$dossierTemplateObj->Relations = $relations ? $relations : null;
		$dossierTemplateObj->Targets = $targets ? $targets : null;

		return $this->createObject( $dossierTemplateObj, $stepInfo );
	}

	/**
	 * Deletes a workflow object.
	 *
	 * @param int $objId The id of the object to be removed.
	 * @param string $stepInfo Extra logging info.
	 * @param string &$errorReport To fill in the error message if there's any during the delete operation.
	 * @param bool $permanent Whether or not to delete the object permanently.
	 * @param array $areas The areas to test against.
	 * @throws BizException on failure
	 */
	public function deleteObject( $objId, $stepInfo, &$errorReport, $permanent=true, $areas=array('Workflow') )
	{
		$this->deleteObjects( array($objId), $stepInfo, $errorReport, $permanent, $areas );
	}
	
	/**
	 * Deletes workflow objects.
	 *
	 * @param array $objIds List of ids of objects to be removed.
	 * @param string $stepInfo Extra logging info.
	 * @param string &$errorReport To fill in the error message if there's any during the delete operation.
	 * @param bool $permanent Whether or not to delete the object permanently.
	 * @param array $areas The areas to test against.
	 * @throws BizException on failure
	 */
	public function deleteObjects( array $objIds, $stepInfo, &$errorReport, $permanent=true, $areas=array('Workflow') )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $objIds;
		$request->Permanent = $permanent;
		$request->Areas = $areas;

		$response = $this->callService( $request, $stepInfo );

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
		$this->testCase->assertTrue( $deleteSuccessful );
	}

	/**
	 * Deletes a user given the user id.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $userId User id of the user to be deleted.
	 * @throws BizException on failure
	 */
	public function deleteUser( $stepInfo, $userId )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function
		
		require_once BASEDIR . '/server/services/adm/AdmDeleteUsersService.class.php';
		$request = new AdmDeleteUsersRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds= array( $userId );

		$this->callService( $request, $stepInfo );

		// Try to retrieve the deleted test User, which should fail.
		require_once BASEDIR.'/server/services/adm/AdmGetUsersService.class.php';
		$request = new AdmGetUsersRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->UserIds = array( $userId );
		
		$this->expectedError = '(S1056)';
		$stepInfo = 'Calling AdmGetUsers to validate AdmDeleteUsers as called before.';
		$this->callService( $request, $stepInfo );

		LogHandler::Log( 'BuildTestUtils', 'INFO', 'Completed validating AdmDeleteUsers.' );
	}

	/**
	 * Deletes a status
	 *
	 * @param integer $statusId
	 * @throws BizException on failure
	 */
	public function deleteStatus( $statusId )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		// TODO: call web service layer (instead of calling biz layer)
		try {
			require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
			BizCascadePub::deleteStatus( $statusId );
		} catch( BizException $e ) {
			$this->testCase->throwError( $e->getMessage().'<br/>'.$e->getDetail() );
		}
	}

	/**
	 * Creates a Publication to let successor test cases work on it.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $publicationName Name of the publication, if NULL one will be generated.
	 * @return AdmPublication|null Brand when success is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function createAdmPublication( $stepInfo, $publicationName )
	{
		// Create a new test Publication data object in memory.
		$admPub = new AdmPublication();
		$admPub->Name              = !is_null($publicationName) ? $publicationName : $this->composeName();
		$admPub->Description       = 'Created Brand by BuildTest '.__CLASS__;
		$admPub->SortOrder         = 0;
		$admPub->EmailNotify       = false;
		$admPub->ReversedRead      = false;
		$admPub->AutoPurge         = 0;
		$admPub->DefaultChannelId  = 0;
		$admPub->ExtraMetaData     = array(); // since 9.0

		// Create the test Publication in the DB.
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationsService.class.php';
		$request = new AdmCreatePublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Publications = array( $admPub );

		$expectedError = $this->expectedError;
		$response = $this->callService( $request, $stepInfo );

		if( !$expectedError ) {
			$this->testCase->assertAttributeInternalType( 'array', 'Publications', $response );
			$this->testCase->assertAttributeCount( 1, 'Publications', $response ); // check $response->Publications[0]
			$this->testCase->assertInstanceOf( 'AdmPublication', $response->Publications[0] );
		}
		return isset($response->Publications[0]) ? $response->Publications[0] : null;
	}

	/**
	 * Deletes publications given their ids.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param array $publicationIds Ids of the publication to be deleted.
	 * @throws BizException on unexpected system response
	 */
	public function deletePublications( $stepInfo, array $publicationIds )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function
		
		// Remove the test Publication from DB.
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';
		$request = new AdmDeletePublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationIds = $publicationIds;

		$this->callService( $request, $stepInfo );

		// Try to retrieve the deleted test Publication, which should fail.
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationIds = $publicationIds;
		
		$this->expectedError = '(S1056)';
		$stepInfo = 'Calling AdmGetPublications to validate AdmDeletePublications as called before.';
		$this->callService( $request, $stepInfo );

		LogHandler::Log( 'BuildTestUtils', 'INFO', 'Completed validating AdmDeletePublication.' );
	}

	/**
	 * Create routing profile.
	 *
	 * Adds a record into smart_routing table.
	 *
	 * @param int $pubId Publication filter when creating the RouteTo profile.
	 * @param int $catId Category filter when creating the RouteTo profile.
	 * @param int $objectStatusId Object status filter when creating the RouteTo profile.
	 * @param string $routeTo User short name, to which user it should route to when an object matches the above filters.
	 * @return int The newly inserted record id.
	 * @throws BizException on failure
	 */
	public function createRoutingProfile( $pubId, $catId, $objectStatusId, $routeTo )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		// Very hackish approach to adding authorization
		// There's no webservice call available for this yet
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$row = array(
			'publication' => intval( $pubId ),
			'issue' => 0,
			'section' => intval( $catId ),
			'state' => intval( $objectStatusId ),
			'routeto' => $routeTo
		);
		$routingId = DBBase::insertRow( 'routing', $row );
		
		$this->testCase->assertInternalType( 'integer', $routingId );
		$this->testCase->assertGreaterThan( 0, $routingId );
		return $routingId;
	}
	
	/**
	 * Removes a routing profile.
	 * 
	 * @throws BizException on failure
	 */
	public function deleteRoutingProfile( $routingId ) 
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		// Very hackish approach to adding authorization
		// There's no webservice call available for this yet
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$where = '`id` = ? ';
		$params = array( $routingId );
		$isDeleted = DBBase::deleteRows( 'routing', $where, $params );
		
		$this->testCase->assertInternalType( 'boolean', $isDeleted );
		$this->testCase->assertTrue( $isDeleted );
	}

	/**
	 * Adds authorization for a group.
	 *
	 * @param integer $publId
	 * @param integer $issueId
	 * @param integer $groupId
	 * @param integer $sectionId
	 * @param integer $stateId
	 * @param integer $profileId
	 * @return integer Authorization record id.
	 * @throws BizException on unexpected system response
	 */
	public function addAuthorization( $publId, $issueId, $groupId, $sectionId = 0, $stateId = 0, $profileId = 0, $rights = '' )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function
		$this->testCase->assertGreaterThan( 0, $profileId );

		$dbh = DBDriverFactory::gen();
		$dba = $dbh->tablename("authorizations");
		$params = array( $publId, $issueId, $groupId, $sectionId, $stateId, $profileId, $rights );
		$sql = "INSERT INTO $dba (`publication`, `issue`, `grpid`, `section`, `state`, `profile`, `rights`) ".
			"VALUES (?, ?, ?, ?, ?, ?, ?)";
		$sql = $dbh->autoincrement($sql);
		$sth = $dbh->query($sql, $params);

		$this->testCase->assertNotNull( $sth );

		$id = $dbh->newid($dba, true);
		
		$this->testCase->assertInternalType( 'integer', $id );
		$this->testCase->assertGreaterThan( 0, $id );
		return $id;
	}

	/**
	 * Removes authorization for a group.
	 *
	 * @param integer $publId
	 * @param integer $issueId
	 * @param integer $groupId
	 * @param integer $sectionId
	 * @param integer $stateId
	 * @param integer $profileId
	 */
	public function removeAuthorization( $publId, $issueId, $groupId, $sectionId = 0, $stateId = 0 )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		$dbh = DBDriverFactory::gen();
		$dba = $dbh->tablename("authorizations");
		$params = array( $publId, $issueId, $groupId, $sectionId, $stateId);
		$sql = "DELETE FROM ".$dba." WHERE `publication` = ? AND `issue` = ? AND `grpid` = ? AND `section` = ? AND `state` = ?";
		$sth = $dbh->query($sql, $params);
		
		$this->testCase->assertNotNull( $sth );
	}
	
	/**
	 * Create a user group
	 *
	 * @param string $stepInfo Extra logging info
	 * @param string $groupName Name of the user group. Null to autofill name.
	 * @throws BizException on unexpected system response
	 */
	public function createUserGroup( $stepInfo, $groupName=null ) {

		$group = new AdmUserGroup();
		$group->Name = !is_null($groupName) ? $groupName : $this->composeName();
		$group->Description = 'Created by BuildTest class '.__CLASS__;
		$group->Admin = true;
		$group->Routing = false;

		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';
		$request = new AdmCreateUserGroupsRequest();
		$request->Ticket 		= $this->ticket;
		$request->RequestModes 	= array();
		$request->UserGroups 	= array($group);

		$expectedError = $this->expectedError;
		$response = $this->callService( $request, $stepInfo );

		if( !$expectedError ) {
			$this->testCase->assertAttributeInternalType( 'array', 'UserGroups', $response );
			$this->testCase->assertAttributeCount( 1, 'UserGroups', $response ); // check $response->UserGroups[0]
			$this->testCase->assertInstanceOf( 'AdmUserGroup', $response->UserGroups[0] );
		}
		return isset($response->UserGroups[0]) ? $response->UserGroups[0] : null;
	}

	/**
	 * Deletes a user group given the group id.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param int $groupId Group id of the group to be deleted.
	 * @throws BizException on failure
	 */
	public function deleteUserGroup( $groupId )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$where = '`id` = ? ';
		$params = array( $groupId );
		$isDeleted = DBBase::deleteRows( 'groups', $where, $params );

		$this->testCase->assertInternalType( 'boolean', $isDeleted );
		$this->testCase->assertTrue( $isDeleted );
	}
	
	/**
	 * Adds users to a user group.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $groupId
	 * @param integer[] $userIds
	 * @throws BizException on unexpected system response
	 */
	public function addUsersToGroup( $stepInfo, $groupId, $userIds ) 
	{
		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket	= $this->ticket;
		$request->UserIds 	= $userIds;
		$request->GroupId 	= $groupId;

		$this->callService( $request, $stepInfo );
	}

	/**
	 * Removes users from a user group.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $groupId
	 * @param integer[] $userIds
	 * @throws BizException on unexpected system response
	 */
	public function removeUsersFromGroup( $stepInfo, $groupId, $userIds ) 
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';
		$request = new AdmRemoveUsersFromGroupRequest();
		$request->Ticket	= $this->ticket;
		$request->UserIds 	= $userIds;
		$request->GroupId 	= $groupId;
		
		$this->callService( $request, $stepInfo );
	}

	/**
	 * Creates an issue at the DB through the CreateIssues admin web service.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $publicationId
	 * @param integer $pubChannelId
	 * @param string $issueName Name of issue. Null to use autofill name.
	 * @param boolean $isOverrule When true, this issue is created as overrule issue.
	 * @return AdmIssue|null Issue when success is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function createIssue( $stepInfo, $publicationId, $pubChannelId, $issueName=null, $isOverrule=false )
	{
		$issue = new AdmIssue();
		$issue->Name                 = !is_null($issueName) ? $issueName : $this->composeName();
		$issue->Description          = 'Created by BuildTest class '.__CLASS__;
		$issue->SortOrder            = 2;
		$issue->EmailNotify          = false;
		$issue->ReversedRead         = false;
		$issue->OverrulePublication  = $isOverrule;
		$issue->Deadline             = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+1, date('Y')));
		$issue->PublicationDate      = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
		$issue->ExpectedPages        = 32;
		$issue->Subject              = $stepInfo;
		$issue->Activated            = true;
		$issue->ExtraMetaData        = null;

		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$request = new AdmCreateIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->PubChannelId = $pubChannelId;
		$issue = unserialize( serialize( $issue ) );
		$request->Issues = array( $issue );

		$expectedError = $this->expectedError;
		$response = $this->callService( $request, $stepInfo );
		
		if( !$expectedError ) {
			$this->testCase->assertAttributeInternalType( 'array', 'Issues', $response );
			$this->testCase->assertAttributeCount( 1, 'Issues', $response ); // check $response->Issues[0]
			$this->testCase->assertInstanceOf( 'AdmIssue', $response->Issues[0] );
		}
		return isset($response->Issues[0]) ? $response->Issues[0] : null;
	}

	/**
	 * Delete issues through the DeleteIssues admin web service.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param $publicationId
	 * @param $issueId
	 * @throws BizException on failure
	 */
	public function deleteIssue( $stepInfo, $publicationId, $issueId )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
		$request = new AdmDeleteIssuesRequest();
		$request->Ticket                = $this->ticket;
		$request->PublicationId         = $publicationId;
		$request->IssueIds              = array( $issueId );
		$this->callService( $request, $stepInfo );

		// Try to retrieve the deleted test PubChannel, which should fail.
		require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';
		$request = new AdmGetIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->IssueIds = array( $issueId );
		
		$this->expectedError = '(S1056)';
		$stepInfo = 'Calling AdmGetIssues to validate AdmDeleteIssues as called before.';
		$this->callService( $request, $stepInfo );

		LogHandler::Log( 'BuildTestUtils', 'INFO', 'Completed validating AdmDeleteIssues.' );
	}

	/**
	 * Creates an issue at the DB through the CreateIssues admin web service.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $publicationId
	 * @param string|null $pubChannelName Name of PubChannel. Null to use autofill name.
	 * @return AdmPubChannel|null Channel when succes is expected. Null when error is expected.
	 * @throws BizException on unexpected system response
	 */
	public function createPubChannel( $stepInfo, $publicationId, $pubChannelName=null )
	{
		$pubChan = new AdmPubChannel();
		$pubChan->Name              = !is_null($pubChannelName) ? $pubChannelName : $this->composeName();
		$pubChan->Description       = 'Created by BuildTest class '.__CLASS__;
		$pubChan->Type              = 'print';
		$pubChan->PublishSystem     = 'Enterprise';
		$pubChan->CurrentIssueId    = 0;
		$pubChan->SuggestionProvider = 'OpenCalais';
		$pubChan->ExtraMetaData     = array();

		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';
		$request = new AdmCreatePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$pubChan = unserialize( serialize( $pubChan ) );
		$request->PubChannels = array( $pubChan );

		$expectedError = $this->expectedError;
		$response = $this->callService( $request, $stepInfo );
		
		if( !$expectedError ) {
			$this->testCase->assertAttributeInternalType( 'array', 'PubChannels', $response );
			$this->testCase->assertAttributeCount( 1, 'PubChannels', $response ); // check $response->PubChannels[0]
			$this->testCase->assertInstanceOf( 'AdmPubChannel', $response->PubChannels[0] );
		}
		return isset($response->PubChannels[0]) ? $response->PubChannels[0] : null;
	}

	/**
	 * Deletes the test PubChannel by calling DeletePubChannels admin web service.
	 * Validates is the deletion was successful by calling GetPubChannels service.
	 * Errors when that service returns a PubChannel (as shown in the BuildTest).
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param string $pubChannelName
	 * @param integer $publicationId
	 * @throws BizException on failure
	 */
	public function deletePubChannel( $stepInfo, $publicationId, $pubChannelId )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		// Remove the test PubChannel from DB.
		require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';
		$request = new AdmDeletePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $publicationId;
		$request->PubChannelIds = array( $pubChannelId );
		$this->callService( $request, $stepInfo );

		// Try to retrieve the deleted test PubChannel, which should fail.
		require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';
		$request = new AdmGetPubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->PubChannelIds = array( $pubChannelId );

		$this->expectedError = '(S1056)';
		$stepInfo = 'Calling AdmGetPubChannels to validate AdmDeletePubChannels as called before.';
		$this->callService( $request, $stepInfo );

		LogHandler::Log( 'BuildTestUtils', 'INFO', 'Completed validating AdmDeletePubChannels.' );
	}
	
	/**
	 * Creates an edition.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $publicationId
	 * @param integer $pubChannelId
	 * @param string|null $editionName Name of edition. Null to use autofill name.
	 * @return AdmEdition
	 * @throws BizException on unexpected system response
	 */
	public function createEdition( $stepInfo, $publicationId, $pubChannelId, $editionName=null )
	{
		$edition = new AdmEdition();
		$edition->Name = !is_null($editionName) ? $editionName : $this->composeName();
		$edition->Description = 'Created by BuildTest class '.__CLASS__;

		require_once BASEDIR.'/server/services/adm/AdmCreateEditionsService.class.php';
		$request = new AdmCreateEditionsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $publicationId;
		$request->PubChannelId = $pubChannelId;
		$request->Editions = array( $edition );
		$request->IssueId = 0;

		$expectedError = $this->expectedError;
		$response = $this->callService( $request, $stepInfo );

		if( !$expectedError ) {
			$this->testCase->assertAttributeInternalType( 'array', 'Editions', $response );
			$this->testCase->assertAttributeCount( 1, 'Editions', $response ); // check $response->Editions[0]
			$this->testCase->assertInstanceOf( 'stdClass', $response->Editions[0] ); // TODO: should be AdmEdition
		}
		return isset($response->Editions[0]) ? $response->Editions[0] : null;
	}

	/**
	 * Delete editions through the DeleteEditions admin web service.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $publicationId
	 * @param integer $editionId Id of edition to be deleted
	 * @throws BizException on failure
	 */
	public function deleteEdition( $stepInfo, $publicationId, $editionId )
	{
		$this->testCase->assertNull( $this->expectedError ); // not supported by this function

		require_once BASEDIR.'/server/services/adm/AdmDeleteEditionsService.class.php';
		$request = new AdmDeleteEditionsRequest();
		$request->Ticket         = $this->ticket;
		$request->PublicationId  = $publicationId;
		$request->EditionIds     = array( $editionId );
		$this->callService( $request, $stepInfo );

		// Try to retrieve the deleted test Edition, which should fail.
		require_once BASEDIR.'/server/services/adm/AdmGetEditionsService.class.php';
		$request = new AdmGetEditionsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->EditionIds = array( $editionId );

		$this->expectedError = '(S1056)';
		$stepInfo = 'Calling AdmGetEditions to validate AdmDeleteEditions as called before.';
		$this->callService( $request, $stepInfo );

		LogHandler::Log( 'BuildTestUtils', 'INFO', 'Completed validating AdmDeleteEditions.' );
	}
}
