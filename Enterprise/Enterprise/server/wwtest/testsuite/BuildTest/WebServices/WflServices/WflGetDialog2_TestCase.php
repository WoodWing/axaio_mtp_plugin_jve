<?php
/**
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflGetDialog2_TestCase extends TestCase
{
	// Session related stuff	
	private $ticket = null;
	private $user = null;
	private $wflServicesUtils = null;
	
	// properties used for testing
	private $pub = null;
	private $sec = null;
	private $sections = null;
	private $issues = null; // all issues belongs to the Brand chosen is remembered.
	private $iss	= null;  // one issue is remembered
	private $statuses = null;
	private $status  = null;
	private $routeTo = null;
	private $category2 = null;
	private $status2 = null;

	private $image1 = null;
	private $image2 = null;
	private $image3 = null;
	private $image4 = null;
	private $image5 = null;
	private $image6 = null;
	private $article1 = null;
	private $imageRouteTo = null;
	private $testUser1 = null; // AdmUser object.
	private $testUser2 = null; // AdmUser object.
	private $routing1 = null; // Routing id.
	private $routing2 = null; // Routing id.
	private $routeToDialogSetupId = null; // DialogSetup id in smart_actionproperties table.

	public function getDisplayName() { return 'GetDialog2'; }
	public function getTestGoals()   { return 'Checks if the workflow dialog service2 works well.'; }
	public function getTestMethods() { return 'Call GetDialog2 service and see whether it returns a good data structure that can be used to draw a dialog.'; }
	public function getPrio()        { return 101; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
		if( !$this->wflServicesUtils->initTest( $this ) ) {
			return;
		}
		$this->vars = $this->getSessionVariables();
		$this->ticket = $this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->user = BizSession::checkTicket( $this->ticket );

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		$this->testSingleObjectGetDialog2();
		$this->testMultiSetPropertiesGetDialog2();
	}

	/**
	 * Do a getDialog2 call with 'Create' action and validate its response.
	 */
	private function testSingleObjectGetDialog2()
	{
		require_once BASEDIR . '/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket	= $this->ticket;
		$request->Action    = 'Create';

		$pubInfo = $this->vars['BuildTest_WebServices_WflServices']['publication'];
		$this->setupTestData( $pubInfo );
		$this->constructMetaData( $request );
		$stepInfo = 'Calling GetDialog2 web service for new Article with pre-selected Brand and Category only.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->checkRespMetaData( $request, $response ); // check MetaData on the response

		$this->setupTestDataForRoundTrip( $request ); // change the getDialog2 req
		$stepInfo = 'Calling GetDialog2 web service for roundtrip test.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->checkRespMetaData( $request, $response ); // check whether the MetaData and Targets are roundtripped.
	}
	
	/**
	 * Initializes the following class members based on the given brand ($pubInfo):
	 * - $this->pub
	 * - $this->issues and $this->iss
	 * - $this->sections and $this->sec
	 * - $this->statuses and $this->status
	 *
	 * @param PublicationInfo $pubInfo
	 */
	private function setupTestData( $pubInfo )
	{
		$this->pub = new Publication( $pubInfo->Id, $pubInfo->Name );
		$this->setupCategories( $pubInfo );
		$this->setupIssues( $pubInfo );
		$this->setupStatuses( $pubInfo );
	}
	
	/**
	 * Construct getDialog2 request ($req->MetaData)
	 * Only publication, Section and Type will be filled up with value.
	 * It is expected that Enterprise already has at least one publication and
	 * category defined, when either one is not defined, it will raise error.
	 * ID, Issue, State are left empty and is expected to be filled up
	 * in the getDialog2 response when the getDialog2 request is sent to server.
	 * All values filled in are remembered so that it can be used to check
	 * in the getDialog2 response later.
	 *
	 * @param WflGetDialog2Request $req Request to be sent for getDialog2
	 * @param Target $targets
	 */
	private function constructMetaData( $req, $targets=null )
	{
		$metaDataValues = array();
		$metaDataValue = new MetaDataValue();
		$metaDataValue->Property = 'ID';
		$metaDataValue->Values = null;
		$metaDataValue->PropertyValues = array();
		$metaDataValues['ID'] = $metaDataValue;

		$propertyValue = new PropertyValue();
		$propertyValue->Value = $this->pub->Id;
		$propertyValue->Display = $this->pub->Name;

		$metaDataValue = new MetaDataValue();
		$metaDataValue->Property = 'Publication';
		$metaDataValue->Values = null;
		$metaDataValue->PropertyValues = array( $propertyValue );
		$metaDataValues['Publication'] = $metaDataValue;

		$metaDataValue = new MetaDataValue();
		$metaDataValue->Property = 'Issue';
		$metaDataValue->Values = null;
		$metaDataValue->PropertyValues = array();
		$metaDataValues['Issue'] = $metaDataValue;

		$propertyValue = new PropertyValue();
		$propertyValue->Value = $this->sec->Id;
		$propertyValue->Display = $this->sec->Name;

		$metaDataValue = new MetaDataValue();
		$metaDataValue->Property = 'Category';
		$metaDataValue->Values = null;
		$metaDataValue->PropertyValues = array( $propertyValue );
		$metaDataValues['Category'] = $metaDataValue;

		$metaDataValue = new MetaDataValue();
		$metaDataValue->Property = 'State';
		$metaDataValue->Values = null;
		$metaDataValue->PropertyValues = array();
		$metaDataValues['State'] = $metaDataValue;

		$propertyValue = new PropertyValue();
		$propertyValue->Value = 'Article';
		$propertyValue->Display = null;

		$metaDataValue = new MetaDataValue();
		$metaDataValue->Property = 'Type';
		$metaDataValue->Values = null;
		$metaDataValue->PropertyValues = array( $propertyValue );
		$metaDataValues['Type'] = $metaDataValue;
		
		$req->MetaData = $metaDataValues;
		if(!is_null($targets)){
			$req->Targets = $targets;
		}
	}
	
	/**
	 * It retrieves another PublicationInfo other than $oldPubInfo.
	 * If there's only one Publication defined in Enterprise,
	 * which is already in used by $oldPubInfo, Null is returned.
	 *
	 * @param PublicationInfo $oldPubInfo Publication info for the brand defined in TESTSUITE setting.
	 * @return PublicationInfo $newPubInfo New Publication info asides from $oldPubInfo
	 */
	function getAnotherPubInfo( $oldPubInfo )
	{
		$newPubInfo = null;
		$pubInfos = BizPublication::getPublications( $this->user, 'full' );
		if( count($pubInfos) > 1 ) {
			foreach( $pubInfos as $pubInfo ) {
				if( $pubInfo->Name != $oldPubInfo->Name ) { // BuildTest found another pub that is not used for the first getDialog request.
					$newPubInfo = $pubInfo;
					break;
				}
			}
		}
		return $newPubInfo;
	}
	
	/**
	 * Retrieves the Categories from the given brand info ($pubInfo).
	 * The section returned is remembered so that it can
	 * be used to check with the getDialog2 response later.
	 *
	 * @param PublicationInfo $pubInfo Publication info for a brand.
	 * @throws BizException When $this->sections or $this->sec could not be initialized.
	 */
	private function setupCategories( $pubInfo )
	{
		$this->sections = $pubInfo->Categories;
		$this->sec = $this->sections[0];
		$this->assertInstanceOf( 'CategoryInfo', $this->sec,
			'The TESTSUITE Brand named "'.$pubInfo->Name.'" has no Categories setup.' );
	}
	
	/**
	 * Initializes $this->issues with all non-overrule issues under the given brand ($pubInfo)
	 * and $this->iss with this first non-overrule issue found under that brand.
	 *
	 * @param PublicationInfo $pubInfo Brand setup info.
	 * @throws BizException When $this->issues or $this->iss could not be initialized.
	 */
	private function setupIssues( $pubInfo )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		$pubRow = DBPublication::getPublication( $pubInfo->Id );
		$channelId = $pubRow['defaultchannelid'];

		$this->iss = null;
		$this->issues = null;
		
		// Remember first issue and all issues of the Brand (except overrules).
		if( $pubInfo->PubChannels ) foreach( $pubInfo->PubChannels as $pubChannel ) {
			if( $pubChannel->Id == $channelId ) {
				if( $pubChannel->Issues ) foreach( $pubChannel->Issues as $issue ) {
					if( !$issue->OverrulePublication ) {
						if( !$this->iss ) { // first non-overrule issue
							$this->iss = new Issue( $issue->Id, $issue->Name );
						}
						$this->issues[] = $issue; // all non-overrule issues
					}
				}
			}
		}
		$this->assertInstanceOf( 'Issue', $this->iss,
				'The TESTSUITE Brand named "'.$pubInfo->Name.'" '.
				'has no non-overrule Issues setup under the default Publication Channel.' );
	}
	
	/**
	 * Initializes $this->statuses with all statuses under the given brand ($pubInfo)
	 * and $this->status with this first Article status found under that brand.
	 *
	 * @param PublicationInfo $pubInfo Brand setup info.
	 */
	private function setupStatuses( $pubInfo )
	{
		$this->status = null;
		$this->statuses = $pubInfo->States;
		if( $this->statuses ) foreach( $this->statuses as $status ) {
			if( $status->Type == 'Article' ){
				$this->status = $status;
				break;
			}
		}
		$this->assertInstanceOf( 'State', $this->status,
			'The TESTSUITE Brand named "'.$pubInfo->Name.'" has no Statuses setup for Articles.' );
	}
	
	/**
	 * Re-adjust the MetaData in getDialog2 request for second roundtrip.
	 * The first round of getDialog2 request, constructMetaData() is called;
	 * for second round, this function is called and it will change 
	 * Section, Issue and State in the request->MetaData; nevertheless, it is still
	 * subject to the availability of the sections, issues and statuses defined in 
	 * Enterprise. 
	 *
	 * @param WflGetDialog2Request $req Request to be sent for getDialog2
	 * @return bool
	 */
	private function setupTestDataForRoundTrip( $req )
	{
		// Prefer picking another Category. If not available, use the current one.
		$sec = null;
		if( $this->sections ) foreach( $this->sections as $sec ) {
			if( $this->sec->Id != $sec->Id ) {
				$this->sec = $sec;
				break;
			}
		}
		$req->MetaData['Category'] = new MetaDataValue( 'Category', null, 
			array( new PropertyValue( $sec->Id, $sec->Name ) ) );

		// Prefer picking another Issue. If not available, use the current one.
		$iss = null;
		if( $this->issues ) foreach( $this->issues as $iss ) {
			if( $this->iss->Id != $iss->Id ){
				$this->iss = $iss;
				break;
			}
		}
		$req->MetaData['Issue'] = new MetaDataValue( 'Issue', null, 
			array( new PropertyValue( $iss->Id, $iss->Name ) ) );

		// Prefer picking another Status. If not available, use the current one.
		$status = null;
		if( $this->statuses ) foreach( $this->statuses as $status ){
			if( $this->status->Id != $status->Id && $status->Type == 'Article' ) {
				$this->status = $status;
				break;
			}
		}
		$req->MetaData['State'] = new MetaDataValue( 'State', null, 
			array( new PropertyValue( $status->Id, $status->Name ) ) );
		
		// Prefer picking default routeto user. If not configured, use acting user.
		$this->setupRouteToForRoundTrip();
		$req->MetaData['RouteTo'] = new MetaDataValue( 'RouteTo', null, 
			array( new PropertyValue( $this->routeTo->Value, $this->routeTo->Display ) ) );
		
		// Set target to our issue.
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		$req->Targets = array( BizTarget::buildTargetFromIssueId( $iss->Id ) );
		
		return true;
	}
	
	/**
	 * It is called to change the route to in getDialog2 request.
	 * It will get the default route to user/usergroup of the $this->status.
	 */
	private function setupRouteToForRoundTrip()
	{
		$this->routeTo = new stdClass();
		if ( $this->status && $this->status->DefaultRouteTo ) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$user = DBUser::getUser( $this->status->DefaultRouteTo );
			if ( $user ) {
				$this->routeTo->Value = $user['user'];
				$this->routeTo->Display = $user['fullname'];
			} else {
				// When the user isn't found, assume this is an usergroup
				$this->routeTo->Value = $this->status->DefaultRouteTo;
				$this->routeTo->Display = $this->status->DefaultRouteTo;
			}
		} else { // when there's no defaultRoutTo defined, we fallback to TestSuite user.
			require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
			$this->routeTo->Value = $this->user;
			$this->routeTo->Display = BizUser::resolveFullUserName( $this->user );
		}
	}
	
	/**
	 * It is called after getDialog2 request.
	 * This function validates whether the getDialog2 response MetaData has the
	 * values roundtripped as it was sent in the getDialog2 request.
	 * It checks for Publication, Section, Category, Issue, Issues,Type and State.
	 * If any of the above has different value compared to the getDialog2 request or
	 * having unexpected value, it will raise error.
	 *
	 * @param WflGetDialog2Request $req
	 * @param WflGetDialog2Response $resp GetDialog2 response returned by getDialog2 service.
	 */
	private function checkRespMetaData( $req, $resp )
	{
		if( $resp->Dialog->MetaData ) foreach( $resp->Dialog->MetaData as $metaData ){
			switch( $metaData->Property ){
				case 'Publication':
					if( $metaData->PropertyValues[0]->Value != $this->pub->Id ){
						$this->setResult( 'ERROR',  'Publication is not matched with the chosen one:' . PHP_EOL .
													$this->pub->Name .'(id=' . $this->pub->Id .') is sent on request,but ' .
													$metaData->PropertyValues[0]->Display . '(id=' . $metaData->PropertyValues[0]->Value . ') '. 
													'is returned on response.',
													'Something went wrong in publication round trip!, check on getDialog2 resp.' );
					}
					break;
				case 'Section':
				case 'Category':
					if( $metaData->PropertyValues[0]->Value != $this->sec->Id ){
						$this->setResult( 'ERROR',  $metaData->Property . ' is not matched with the chosen one:' . PHP_EOL .
													$this->sec->Name . ' (id=' . $this->sec->Id . ') is sent on request,but '.
													$metaData->PropertyValues[0]->Display . ' (id=' . $metaData->PropertyValues[0]->Value . ') ' .
													'is returned on response',
													'Something went wrong in sec round trip!, check on getDialog2 resp.' );
					}
					break;
				case 'Issue':
				case 'Issues':
					$found = false;
					if( $this->issues )foreach( $this->issues as $iss ){
						if( $iss->Id == $metaData->PropertyValues[0]->Value &&
							$iss->Name == $metaData->PropertyValues[0]->Display ){
							$found=true;
							break;
						}
					}
					if( !$found ){
						if( $this->iss ){ // getDialog request is being sent to server second time onwards
							$errorMessage	= $metaData->Property . ' is not matched with the chosen one:' . PHP_EOL .
								$this->iss->Name . '(id=' . $this->iss->Id . ') is sent on request,but ' .
								$metaData->PropertyValues[0]->Display . '(id=' . $metaData->PropertyValues[0]->Value . ') '.
								'is returned on response';
							$tipsMessage	= 'Something went wrong in issue round trip!, check on getDialog2 resp.';
						} else { // happens when there's no issue defined in the very first getDialog request
							$errorMessage	= $metaData->Property . ' returned in response is not correct:' . PHP_EOL . 
											  'No issue is sent on request, but issue '. $metaData->PropertyValues[0]->Display .
											  '(id=' . $metaData->PropertyValues[0]->Value .
											  ') returned is not found in the testing Brand.' . PHP_EOL;
							$tipsMessage	= 'getDialog2 response is not returning the correct issue. Make sure there are issues ' .
											  'defined for testing Brand(s).';
						}
						$this->setResult( 'ERROR',  $errorMessage, $tipsMessage );
					}
					break;
				case 'Type':
					if( $metaData->PropertyValues[0]->Value != 'Article' ){
						$this->setResult( 'ERROR',  'Object type is not correct:' . PHP_EOL . 
													'Article is sent on request, but ' . $metaData->PropertyValues[0]->Value .
													' is returned on response.', 
													'Something went wrong in object type round trip!, check on getDialog2 resp.' );
					}
					break;
				case 'State':
					$this->statuses	= BizWorkflow::getStates($this->user, $this->pub->Id, $this->iss->Id, $this->sec->Id, 
							'Article', true, false);
					$found = false;
					if( $metaData->PropertyValues[0]->Value == -1 ) { // personal status
						$found = true;
					} else {
						if( $this->statuses ) foreach( $this->statuses as $status ){
							if( $status->Id == $metaData->PropertyValues[0]->Value && 
								$status->Name == $metaData->PropertyValues[0]->Display ){
									$this->status = $status; // remembered for later usage.
									$found=true;
									break;
								}
						}
					}
					if( !$found ){
						if( $this->status ){ // getDialog request is being sent to server second time onwards
							$errorMessage	= 'Status does not matched with the chosen one:' . PHP_EOL .
								$this->status->Name . '(id=' . $this->status->Id . ') is sent on request,but ' .
								$metaData->PropertyValues[0]->Display . '(id=' . $metaData->PropertyValues[0]->Value . ') '.
								'is returned on response';
							$tipsMessage	= 'Something went wrong in status round trip!, check on getDialog2 resp.';
						} else { // happens when there's no status defined in the very first getDialog request
							$errorMessage	= 'Status returned in response is not correct:' . PHP_EOL .
											  'No status is sent on request, but status ' . $metaData->PropertyValues[0]->Display .
											  '(id=' . $metaData->PropertyValues[0]->Value . 
											  ') returned is not defined under the testing Brand.' . PHP_EOL;
							
							$tipsMessage	= 'getDialog2 response is not returning the correct status. Make sure there are status(es) ' .
											  'defined for testing Brand.';
						}
						$this->setResult( 'ERROR',  $errorMessage, $tipsMessage );
					}
					break;
				case 'RouteTo':
					if ( $this->routeTo ) {
						$errorMessage = '';
						$tipsMessage	= 'Check the default route to for the brand/object type.';
						if( $metaData->PropertyValues[0]->Value != $this->routeTo->Value ) {
							$errorMessage	= 'The routeto(Value) user/usergroup ('.$this->routeTo.') that was send to'.
								' in the request isn\'t returned ('.$metaData->PropertyValues[0]->Display.').';
						}
						if( $metaData->PropertyValues[0]->Display != $this->routeTo->Display ) {
							$errorMessage	= 'The routeto(Display) user/usergroup ('.$this->routeTo.') that was send to'.
								' in the request isn\'t returned ('.$metaData->PropertyValues[0]->Display.').';											
						}
						if( $errorMessage ) {
							$this->setResult( 'ERROR',  $errorMessage, $tipsMessage );
						}
					}
					break;
			}
		}
		
		if( $req->Targets ){ // whenever there's Targets sent on request, we check on the response.
			$error = false;
			$errorMessage = '';
			if( !$resp->Targets ){
				$error			= true;
				$errorMessage	= 'There\'s no Targets returned on response which is not expected.';
			}
			
			if( $resp->Targets ){
				foreach( $req->Targets as $target ){
					$pubChannelId = $target->PubChannel->Id;
					// Only test pubchannel and issue data
					$reqTarget[$pubChannelId]	= array( 'name' => $target->PubChannel->Name,
														 'issueId' => $target->Issue->Id,
														 'issueName' => $target->Issue->Name);
				}
				
				foreach( $resp->Targets as $target ){
					$thisPubErr=false;
					$thisPubErrMesg = '';
					$pubChannelId = $target->PubChannel->Id;
					if( isset( $reqTarget[$pubChannelId]) ){
						if( $reqTarget[$pubChannelId]['name'] != $target->PubChannel->Name ){
							$thisPubErr = true;
							$thisPubErrMesg .= 'PubChannel ' . $reqTarget[$pubChannelId]['name'] . '(id=' . $pubChannelId . ') is sent but,' .
											 'PubChannel ' . $target->PubChannel->Name . '(id='. $pubChannelId .') is returned on response.' . PHP_EOL;
						}
						if( $reqTarget[$pubChannelId]['issueId'] != $target->Issue->Id ){
							$thisPubErr = true;
							$thisPubErrMesg .= 'IssueId \'' . $reqTarget[$pubChannelId]['issueId'] . '\' is sent but ' .
											   'IssueId \'' . $target->Issue->Id . '\' is returned on response.' . PHP_EOL;
						}
						if( $reqTarget[$pubChannelId]['issueName'] != $target->Issue->Name ){
							$thisPubErr = true;
							$thisPubErrMesg .= 'IssueName \'' . $reqTarget[$pubChannelId]['issueName'] . '\' is sent but ' .
											  'IssueName \'' . $target->Issue->Name . '\' is returned on response.' . PHP_EOL;
						}
						
						if($thisPubErr){
							$error = true;
							$errorMessage	.= 'Target sent on getDialog request is not being round-tripped in the response for ' .
											  'PubChannel '. $reqTarget[$pubChannelId]['name'] . '(id=' . $pubChannelId . '):: ' . PHP_EOL .
											  $thisPubErrMesg. PHP_EOL; 
						}
					}
				}
			}
			
			if( $error ){
				$tipsMessage	= 'Something went wrong in Targets round trip!' . PHP_EOL .
								  'check on the getDialog2->Targets with getDialog2->Response->Targets';
				$this->setResult( 'ERROR',  $errorMessage, $tipsMessage );
			}
		}
	}

	/********************************************** Multi Set Properties **********************************************/
	/**
	 * Do a getDialog2 service call to test for multi set properties.
	 */
	private function testMultiSetPropertiesGetDialog2()
	{
		try {
			$this->setupTestDataForMultiSetPropertiesTest();
			$this->testIdAndIdsParam();
			$this->testIdParam();
			$this->testIdsParam();
			$this->testActionParam();
			$this->testUnwantedParams();
			$this->testTrashAreaParam();
			$this->testDifferentObjType();
			$this->testWithValidParams();
			$this->testWithRouteTo();
			$this->testWithSendToActionParam();
		} catch( BizException $e ) {
		}
		$this->teardownTestDataForMultiSetPropertiesTest();
	}

	/**
	 * Setup test data for the build test.
	 */
	private function setupTestDataForMultiSetPropertiesTest()
	{
		require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
		BizWorkflow::clearRouteToCache();
		$pubInfo = $this->vars['BuildTest_WebServices_WflServices']['publication'];

		// Create new category for testing.
		$stepInfo = 'Creating category for getDialgo2 test case.';
		$this->category2 = $this->wflServicesUtils->createCategory( $pubInfo->Id, $stepInfo );

		// Create new status for Image.
		require_once BASEDIR .'/server/interfaces/services/wfl/DataClasses.php';
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$statusName = 'ImageReadyTest'.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;
		$statusObj = $this->wflServicesUtils->createStatus( $statusName, 'Image', $pubInfo->Id );
		$this->status2 = new State();
		$this->status2->Id = $statusObj->Id;
		$this->status2->Name = $statusObj->Name;

		$this->doDialogSetup();
		$this->setupUsersAndRouting();

		$this->imageRouteTo = $this->testUser2->FullName;
		$this->image1 = $this->wflServicesUtils->createImageObject( 'Create image 1.', 
							'imageForMultiSetProp1', null, null, null, null, $this->imageRouteTo );
		$this->image2 = $this->wflServicesUtils->createImageObject( 'Create image 2.', 
							'imageForMultiSetProp2', null, null, null, null, $this->imageRouteTo );
		$this->image3 = $this->wflServicesUtils->createImageObject( 'Create image 3.', 
							'imageForMultiSetProp3', null, null, null, null, $this->imageRouteTo );
		$this->image4 = $this->wflServicesUtils->createImageObject( 'Create image 4.', 
							'imageForMultiSetProp4', null, null, $this->category2, $this->status2, $this->imageRouteTo );
		$this->image5 = $this->wflServicesUtils->createImageObject( 'Create image 5.', 
							'imageForMultiSetProp5', null, null, null, $this->status2, $this->imageRouteTo );
		$this->image6 = $this->wflServicesUtils->createImageObject( 'Create image 6.', 
							'imageForMultiSetProp6', null, null, null, $this->status2, null );
		$this->image7 = $this->wflServicesUtils->createImageObject( 'Create image 7.', 
							'imageForMultiSetProp7', null, null, $this->category2, $this->status2, null );
		$this->article1 = $this->wflServicesUtils->createArticleObject( 'Create article 1.', 
							'articleForMultiSetProp1' );
	}

	/**
	 * Add 'RouteTo' property into dialog.
	 */
	private function doDialogSetup()
	{
		// Setup RouteTo dialog.
		$pubInfo = $this->vars['BuildTest_WebServices_WflServices']['publication'];
		$pubId = $pubInfo->Id;
		$row = array(
			'publication' => intval( $pubId ),
			'action' => 'SetProperties',
			'type' => 'Image',
			'orderid' => 0,
			'property' => 'RouteTo',
			'edit' => 'on',
			'mandatory' => '', // off
			'restricted' => '', // off
			'refreshonchange' => '', // off
			'multipleobjects' => 'on'
		);
		$routeToDialogSetupId = DBBase::insertRow( 'actionproperties', $row );
		$this->assertInternalType( 'integer', $routeToDialogSetupId );
		$this->assertGreaterThan( 0, $routeToDialogSetupId );
		$this->routeToDialogSetupId = $routeToDialogSetupId;
	}

	/**
	 * Creates Users, Routing profiles.
	 */
	private function setupUsersAndRouting()
	{
		// Create test Users
		$stepInfo = 'Creates a new user to test RouteTo field in GetDialog2.';
		$this->testUser1 = $this->wflServicesUtils->createUser( $stepInfo );
		$this->testUser2 = $this->wflServicesUtils->createUser( $stepInfo );

		// Add created users into admin gorup.
		$stepInfo = 'Requesting user group for user:' . $this->user;
		$groupIds = $this->wflServicesUtils->getUserGroupsIds( $stepInfo, BizSession::getUserInfo( 'id' ) );

		$stepInfo = 'Adding user "'.$this->testUser1->Name.'" into group';
		$this->wflServicesUtils->addUserGroupToUser( $stepInfo, $this->testUser1->Id, $groupIds );

		$stepInfo = 'Adding user "'.$this->testUser2->Name.'" into group';
		$this->wflServicesUtils->addUserGroupToUser( $stepInfo, $this->testUser2->Id, $groupIds );

		// Setup Routing
		$pubInfo = $this->vars['BuildTest_WebServices_WflServices']['publication'];
		$pubId = $pubInfo->Id;
		$categoryInfo = $this->vars['BuildTest_WebServices_WflServices']['category'];
		$catId = $categoryInfo->Id;
		$imgStatusInfo = $this->vars['BuildTest_WebServices_WflServices']['imageStatus'];

		$this->routing1 = $this->createRoutingProfile( $pubId, $catId, $imgStatusInfo->Id, $this->testUser1->Name );
		$this->routing2 = $this->createRoutingProfile( $pubId, $catId, $this->status2->Id, $this->testUser2->Name );
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
	 */
	private function createRoutingProfile( $pubId, $catId, $objectStatusId, $routeTo )
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$row = array(
			'publication' => intval( $pubId ),
			'issue' => 0,
			'section' => intval( $catId ),
			'state' => intval( $objectStatusId ),
			'routeto' => $routeTo
		);

		$routingId = DBBase::insertRow( 'routing', $row );
		$this->assertInternalType( 'integer', $routingId );
		$this->assertGreaterThan( 0, $routingId );
		return $routingId;
	}

	/**
	 * Deletes object that were setup for testing in {@link: setupTestDataForMultiSetPropertiesTest()}.
	 */
	private function teardownTestDataForMultiSetPropertiesTest()
	{
		// Permanent delete the images.
		$ids = array();
		if( $this->image1 ) {
			$ids[] = $this->image1->MetaData->BasicMetaData->ID;
			$this->image1 = null;
		}
		if( $this->image2 ) {
			$ids[] = $this->image2->MetaData->BasicMetaData->ID;
			$this->image2 = null;
		}
		if( $this->image3 ) {
			$ids[] = $this->image3->MetaData->BasicMetaData->ID;
			$this->image3 = null;
		}
		if( $this->image4 ) {
			$ids[] = $this->image4->MetaData->BasicMetaData->ID;
			$this->image4 = null;
		}
		if( $this->image5 ) {
			$ids[] = $this->image5->MetaData->BasicMetaData->ID;
			$this->image5 = null;
		}
		if( $this->image6 ) {
			$ids[] = $this->image6->MetaData->BasicMetaData->ID;
			$this->image6 = null;
		}
		if( $this->image7 ) {
			$ids[] = $this->image7->MetaData->BasicMetaData->ID;
			$this->image7 = null;
		}
		if( $ids ) {
			$errorReport = '';
			$stepInfo = 'Tear down the Image objects.';
			try {
				$this->wflServicesUtils->deleteObjects( $ids, $stepInfo, $errorReport );
			} catch( BizException $e ) {
			}
		}

		// Permanent delete the article.
		if( $this->article1 ) {
			$id = $this->article1->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Article object.';
			$this->wflServicesUtils->deleteObject( $id, $stepInfo, $errorReport );
			$this->article1 = null;
		}

		// Delete RouteTo dialog setup.
		if( $this->routeToDialogSetupId ) {
			try {
				$where = '`id` = ? ';
				$params = array( $this->routeToDialogSetupId );
				DBBase::deleteRows( 'actionproperties', $where, $params );
			} catch( BizException $e ) {
			}
			$this->routeToDialogSetupId = null;
		}

		// Delete routing profile.
		if( $this->routing1 ) {
			try {
				$where = '`id` = ? ';
				$params = array( $this->routing1 );
				DBBase::deleteRows( 'routing', $where, $params );
			} catch( BizException $e ) {
			}
			$this->routing1 = null;
		}

		if( $this->routing2 ) {
			try {
				$where = '`id` = ? ';
				$params = array( $this->routing2 );
				DBBase::deleteRows( 'routing', $where, $params );
			} catch( BizException $e ) {
			}
			$this->routing2 = null;
		}

		// Deletes test user.
		if( $this->testUser1 ) {
			try {
				$stepInfo = 'Deletes a user created by the build test.';
				$this->wflServicesUtils->deleteUser( $stepInfo, $this->testUser1->Id );
			} catch( BizException $e ) {
			}
			$this->testUser1 = null;
		}

		if( $this->testUser2 ) {
			try {
				$stepInfo = 'Deletes a user created by the build test.';
				$this->wflServicesUtils->deleteUser( $stepInfo, $this->testUser2->Id );
			} catch( BizException $e ) {
			}
			$this->testUser2 = null;
		}

		// Deletes object status.
		if( $this->status2 ) {
			try {
				$this->wflServicesUtils->deleteStatus( $this->status2->Id );
			} catch( BizException $e ) {
			}
			$this->status2 = null;
		}

		// Deletes category.
		if( $this->category2 ) {
			try {
				$pubInfo = $this->vars['BuildTest_WebServices_WflServices']['publication'];
				$stepInfo = 'Delete category that was created for this build test.';
				$this->wflServicesUtils->deleteCategory( $stepInfo, $pubInfo->Id, $this->category2->Id );
			} catch( BizException $e ) {
			}
			$this->category2 = null;
		}
	}

	/**
	 * Call GetDialog2 for multi set properties by passing in both ID and IDs params.
	 *
	 * This should not be allowed when 'action' = "SetProperties" and 'MultipleObjects' set to true.
	 * So this function expects the server to raise the S1019 error.
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function testIdAndIdsParam()
	{
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testIdAndIdsParam' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;
		
		$stepInfo = 'Calling SetProperties getDialog2 with ID and IDs parameter.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );
	}

	/**
	 * Call GetDialog2 for single object property by passing in IDs.
	 *
	 * This should not be allowed when 'action' = "SetProperties" and 'MultipleObjects' 
	 * set to false. This is incorrect, ID is expected and not IDs.
	 * So this function expects the server to raise the S1019 error.
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function testIdParam()
	{
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testIdParam' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = false;

		$stepInfo = 'Calling SetProperties getDialog2 with IDs parameter when MultipleObjects is set to false.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );
	}

	/**
	 * Call GetDialog2 for multi set properties by passing in ID and not IDs.
	 *
	 * This should not be allowed when 'action' = "SetProperties" and 'MultipleObjects' 
	 * set to true. This is incorrect, IDs is expected and not ID.
	 * So this function expects the server to raise the S1019 error.
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function testIdsParam()
	{
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testIdsParam' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with ID parameter when MultipleObjects is set to true.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );
	}

	/**
	 * Call GetDialog2 for multi set properties by passing in invalid Action parameter.
	 *
	 * It should not be allowed to pass 'Action' = "Preview" with 'MultipleObjects' set to true.
	 * This is incorrect, only 'Action' = "SetProperties" is allowed when 'MultipleObjects' is set to true.
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function testActionParam()
	{
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testActionParam' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'Preview'; // Invalid GetDialog2 Action for multi set properties.
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with "SetProperties" Action when MultipleObjects is set to true.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );
	}

	/**
	 * Call GetDialog2 for multi set properties by passing in unwanted parameters like
	 * 'Targets', 'DefaultDossier', 'Parent', 'Template'.
	 *
	 * Function expects an error for this request, when there's no error raised from server, this
	 * function will set error to the build test and return false.
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function testUnwantedParams()
	{
		$target = $this->vars['BuildTest_WebServices_WflServices']['printTarget'];
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testUnwantedParams' );
		$metaData = unserialize( serialize( $metaData )); // Deep clone.
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';

		// Unwanted 'Targets': When "MultipleObjects" is set to true and "Targets" is not 
		// set to empty, server should raise the S1019 error.
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->Targets      = array( $target );
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with Targets set when MultipleObjects is set to true.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );

		// Unwanted 'DefaultDossier': When "MultipleObjects" is set to true and 
		// "DefaultDossier" is not set to empty, server should raise the S1019 error.
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->DefaultDossier  = 100; // just one random object id.
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with DefaultDossier set when MultipleObjects is set to true.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );

		// Unwanted 'Parent': When "MultipleObjects" is set to true and 
		// "Parent" is not set to empty, server should raise the S1019 error.
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->Parent  = 100; // just one random object id.
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with Parent set when MultipleObjects is set to true.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );

		// Unwanted 'Targets', 'DefaultDossier', 'Parent' and 'Template':
		// "MultipleObjects" is set to true and when any of the following "Targets", "DefaultDossier", 
		// "Parent", "Template" are not set to empty, server should raise the S1019 error.
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->Targets      = array( $target );
		$request->DefaultDossier  = 100; // just one random object id.
		$request->Parent          = 100; // just one random object id.
		$request->Template        = 100; // just one random object id.
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with Targets, DefaultDossier, Parent and Template parameter '.
					'set when MultipleObjects is set to true.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );
	}

	/**
	 * Call GetDialog2 for multi set properties by passing in Areas as "Trash".
	 *
	 * This should not be allowed and so the S1019 error is expected.
	 * @throws BizException on unexpected server response.
	 */
	private function testTrashAreaParam()
	{
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testTrashAreaParam' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->Areas        = array( 'Trash' );
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with area set to Trash and MultipleObjects is set to true.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );
	}

	/**
	 * Call GetDialog2 for multi set properties and a mixture of object types.
	 *
	 * This should not be allowed and so the S1019 error is expected.
	 * @throws BizException on unexpected server response.
	 */
	private function testDifferentObjType()
	{
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testDifferentObjType' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with different object types and MultipleObjects is set to true.';
		$this->wflServicesUtils->setExpectedError( '(S1019)' );
		$this->wflServicesUtils->callService( $request, $stepInfo );
	}

	/**
	 * Call GetDialog2 for multi set properties by passing in valid parameters.
	 *
	 * Function expects no error for this request, it validates the response returned and make sure that
	 * the response are valid.
	 * @throws BizException on unexpected server response.
	 */
	private function testWithValidParams()
	{
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testWithValidParams' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 with valid parameters.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );

		// Validate the response returned...
		$this->assertNull( $response->PubChannels );
		$this->assertNull( $response->MetaData );
		$this->assertNull( $response->Targets );
		$this->assertNull( $response->RelatedTargets );

		$this->assertGreaterThan( 0, count($response->Dialog->Tabs) );

		require_once BASEDIR .'/server/bizclasses/BizProperty.class.php';
		$staticProps = BizProperty::getStaticPropIds();
		$nonEditableProperties = array( 'Name', 'Publication', 'PubChannels', 'Targets', 'Issues', 'Issue', 'Editions' );
		foreach( $response->Dialog->Tabs as $dialogTab ) {
			if( $dialogTab->Widgets ) foreach( $dialogTab->Widgets as $widget ) {
				$propInfo = $widget->PropertyInfo;
				// Checking for PropertyInfo->MixedValues value.
				if( $propInfo->Name == 'Category' ) { // The testing images don't share the same Category.
					$this->assertTrue( $propInfo->MixedValues,
							'PropertyInfo->MixedValues should be set to "true" for property widget ' .
							'named "'.$propInfo->Name.'". The object ids sent in GetDialog2 request ' .
							'has different category and thus the MixedValue should be "true".' );
				}

				if( $propInfo->Name == 'State' ) { // The testing images don't share the same Status.
					$this->assertTrue( $propInfo->MixedValues,
							'PropertyInfo->MixedValues should be set to "true" for property widget ' .
							'named "'.$propInfo->Name.'". The object ids sent in GetDialog2 request ' .
							'has different statuses and thus the MixedValue should be "true".' );
				}

				if( $propInfo->Name == 'RouteTo' ) { // The testing images -share- the same RouteTo.
					$this->assertFalse( $propInfo->MixedValues,
							'PropertyInfo->MixedValues should be set to "false" for property widget ' .
							'named "'.$propInfo->Name.'". The object ids sent in GetDialog2 request ' .
							'has same RouteTo user and thus the MixedValue should be "false".' );
				}

				if( $widget->PropertyUsage ) {
					// PropertyUsage->MultipleObjects are expected to be filled in with boolean.
					$multipleObjects = $widget->PropertyUsage->MultipleObjects;
					$propName = $widget->PropertyUsage->Name;
					// PropertyUsage->MultipleObjects should be a boolean in multi-set properties context.
					$this->assertInternalType( 'boolean', $multipleObjects,
							'PropertyUsage->MultipleObjects should be set as boolean true/false ' .
							'when getDialog2 is for multi-set properties. Please check in the ' .
							'PropertyUsage for widget named "'.$propName.'".' );

					// Static props should not be on the multi-set properties dialog.
					$isStaticProp = in_array( $propName, $staticProps ) &&
						( $propName != 'Category' && $propName != 'State' ); // Category&State are allowed in multi-set
					$this->assertFalse( $isStaticProp && $multipleObjects,
							'For static property, PropertyUsage->MultipleObjects should be set '.
							'as false even when it is for multi-set properties. Please check '.
							'in the PropertyUsage for widget named "'.$propName.'".' );

					$this->assertNotContains( $propName, $nonEditableProperties,
							'Property "'.$propName.'" cannot be changed for multiple objects ' .
							'at one time, thus should not be shown in the GetDialog2 response.' );
				}
			}
		}

		$this->assertGreaterThan( 0, count($response->Dialog->MetaData) );
		$foundIds = false;
		foreach( $response->Dialog->MetaData as $propName => $mdValue ) {
			switch( $propName ) {
				case 'IDs': // "IDs" is expected in GetDialog2Resp->Dialog->MetaData.
					$returnedIds = array();
					if( $mdValue->PropertyValues ) {
						foreach( $mdValue->PropertyValues as $propValue ) {
							$returnedIds[] = $propValue->Value;
						}
					}
					$objIds = array( $this->image1->MetaData->BasicMetaData->ID,
						$this->image2->MetaData->BasicMetaData->ID,
						$this->image3->MetaData->BasicMetaData->ID,
						$this->image4->MetaData->BasicMetaData->ID );

					// Checking for IDs that should present in the response.
					$invalidIds = array_diff( $objIds, $returnedIds );
					$this->assertCount( 0, $invalidIds,
							'Object ids "'.implode(',',$invalidIds).'" are expected in "IDs" ' .
							'returned in Dialog->MetaData. Please check getDialog2 response.' );

					// Checking for IDs that are not suppose to be present in the response.
					$invalidIds = array_diff( $returnedIds, $objIds );
					$this->assertCount( 0, $invalidIds, 
							'Object ids "'.implode(',',$invalidIds).'" are not expected in "IDs" ' .
							'returned in Dialog->MetaData.Please check getDialog2 response.');

					$foundIds = true;
					break;
				case 'Category':
				case 'State':
					$this->assertFalse( $mdValue->Values || $mdValue->PropertyValues,
							'"Values" and "PropertyValues" in Dialog->MetaData ' .
							'should be empty for property name "'.$propName.'" because this property has mixed ' .
							'values displayed in a dialog(PropertyInfo->MixedValues = true). ');
					break;
				case 'RouteTo':
					$this->assertTrue( $mdValue->PropertyValues[0]->Display == $this->imageRouteTo,
							'"PropertyValues" in Dialog->MetaData should be set to ' .
							'"'.$this->imageRouteTo.'" for property name "RouteTo" '.
							'because this property has the same value for all images. ' );
					break;
			}
		}
		$this->assertTrue( $foundIds,
				'For multi-set properties dialog, "IDs" is expected in the getDialog2->Dialog->MetaData '.
				'response but it was not found, please check the getDialog2 request and response.' );
	}

	/**
	 * Call GetDialog2 to test with RouteTo property.
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function testWithRouteTo()
	{
		$this->callGetDialog2RouteTo1();
		$this->callGetDialog2RouteTo2();
		$this->callGetDialog2RouteTo3();
	}

	/**
	 * Calling GetDialog2 for multi-setproperties with single value Category and RouteTo but multiple statuses.
	 *
	 * The function simulates a initial draw of multi-set dialog and a re-draw multi-set dialog.
	 * Each call is validated.
	 *
	 * Scenario example:
	 * Initial Draw:
	 *      Category: News
	 *      Status: <multiple values>
	 *      RouteTo: User2
	 * User change Status to: "Draft".
	 * Redraw dialog: Route to changes to: "User1". <multiple values>
	 * (All the Names used above will be varied for each and every test being carried out)
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function callGetDialog2RouteTo1()
	{
		// InitialDraw
		$metaData = $this->constructMetaDataForMultiSetProperties( 'callGetDialog2RouteTo1_1' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 for initial dialog drawing.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->validateCallGetDialog2RouteTo1_1Resp( $response );

		// Re-draw dialog when user change status.
		$metaData = $this->constructMetaDataForMultiSetProperties( 'callGetDialog2RouteTo1_2', $response );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 for redrawing dialog.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->validateCallGetDialog2RouteTo1_2Resp( $response );
	}

	/**
	 * Calling GetDialog2 for multi-setproperties with single value Category but multiple Statuses and RouteTos.
	 *
	 * The function simulates a initial draw of multi-set dialog and a re-draw multi-set dialog.
	 * Each call is validated.
	 *
	 * Scenario example:
	 * Initial Draw:
	 *      Category: News
	 *      Status: <multiple values>
	 *      RouteTo: <multiple values>
	 * User change Status to: "Draft".
	 * Redraw dialog: Route to changes to: "User1". (Defined in routing profile)
	 * (All the Names used above will be varied for each and every test being carried out)
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function callGetDialog2RouteTo2()
	{
		// InitialDraw
		$metaData = $this->constructMetaDataForMultiSetProperties( 'callGetDialog2RouteTo2_1' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 for initial dialog drawing.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->validateCallGetDialog2RouteTo2_1Resp( $response );

		// Re-draw dialog when user change status.
		$metaData = $this->constructMetaDataForMultiSetProperties( 'callGetDialog2RouteTo2_2', $response );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 for redrawing dialog.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->validateCallGetDialog2RouteTo2_2Resp( $response );
	}

	/**
	 * Calling GetDialog2 for multi-setproperties with mixed values of Categories, Statuses and RouteTos.
	 *
	 * The function simulates two initial draws of multi-set dialog:
	 * 1st dialog with mixed values of Category, Status and RouteTo:
	 * - Category and Status properties should be disabled.
	 * - There should be notification on Category and Status property informing the user why the properties are disabled.
	 *
	 * 2nd dialog with mixed values of Status and RouteTo:
	 * - No propertes should be disabled.
	 * - No notification should be found.
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function callGetDialog2RouteTo3()
	{
		// GetDialog where Category, Status and RouteTo has mixed values.
		$metaData = $this->constructMetaDataForMultiSetProperties( 'callGetDialog2RouteTo3_1' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 for mixed values in Category,Status and RouteTo.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->validateCallGetDialog2RouteTo3_1Resp( $response );

		// GetDialog where Status and RouteTo has mixed values.
		$metaData = $this->constructMetaDataForMultiSetProperties( 'callGetDialog2RouteTo3_2', $response );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SetProperties';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SetProperties getDialog2 for mixed values in Status and RouteTo.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->validateCallGetDialog2RouteTo3_2Resp( $response );
	}

	/**
	 * Call GetDialog2 for multi set properties by passing in valid parameters and action set to 'SendTo'.
	 *
	 * Function expects no error for this request, it validates the response returned and make sure that
	 * the response are valid.
	 *
	 * @throws BizException on unexpected server response.
	 */
	private function testWithSendToActionParam()
	{
		$metaData = $this->constructMetaDataForMultiSetProperties( 'testWithSendToActionParam' );
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket       = $this->ticket;
		$request->Action       = 'SendTo';
		$request->MetaData     = $metaData;
		$request->MultipleObjects = true;

		$stepInfo = 'Calling SendTo getDialog2 with valid parameters.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );

		// Validate the response returned...
		$this->assertNull( $response->PubChannels );
		$this->assertNull( $response->MetaData );
		$this->assertNull( $response->Targets );
		$this->assertNull( $response->RelatedTargets );

		$this->assertGreaterThan( 0, count($response->Dialog->Tabs) );

		require_once BASEDIR .'/server/bizclasses/BizProperty.class.php';
		$staticProps = BizProperty::getStaticPropIds();
		$nonEditableProperties = array( 'Name', 'Publication', 'PubChannels', 'Targets', 'Issues', 'Issue', 'Editions' );
		foreach( $response->Dialog->Tabs as $dialogTab ) {
			if( $dialogTab->Widgets ) foreach( $dialogTab->Widgets as $widget ) {
				$propInfo = $widget->PropertyInfo;
				// Checking for PropertyInfo->MixedValues value.
				if( $propInfo->Name == 'Category' ) { // The testing images share the same Category.
					$this->assertFalse( $propInfo->MixedValues,
							'PropertyInfo->MixedValues should be set to "false" for property widget ' .
							'named "'.$propInfo->Name.'". The object ids sent in GetDialog2 request ' .
							'has the same category and thus the MixedValue should be "false".' );
				}

				if( $propInfo->Name == 'State' ) { // The testing images share the same Status.
					$this->assertFalse( $propInfo->MixedValues,
							'PropertyInfo->MixedValues should be set to "false" for property widget ' .
							'named "'.$propInfo->Name.'". The object ids sent in GetDialog2 request ' .
							'has the same statuses and thus the MixedValue should be "false".' );
				}

				if( $propInfo->Name == 'RouteTo' ) { // The testing images -share- the same RouteTo.
					$this->assertFalse( $propInfo->MixedValues,
							'PropertyInfo->MixedValues should be set to "false" for property widget ' .
							'named "'.$propInfo->Name.'". The object ids sent in GetDialog2 request ' .
							'has same RouteTo user and thus the MixedValue should be "false".' );
				}

				$propUsage = $widget->PropertyUsage;
				if( $propUsage ) {
					$propName = $propUsage->Name;
					switch( $propName ) {
						case 'Category':
							$this->assertFalse( $propUsage->Editable,
									'PropertyUsage->Editable should be set to "false" for property ' .
									'widget named "'.$propInfo->Name.'". The Action sent in ' .
									'GetDialog2 request is "SendTo". ' );
							break;
						case 'State':
							$this->assertTrue( $propUsage->Editable,
									'PropertyUsage->Editable should be set to "true" for property ' .
									'widget named "'.$propInfo->Name.'". The Action sent in ' .
									'GetDialog2 request is "SendTo". ' );
							break;
						case 'RouteTo':
							$this->assertTrue( $propUsage->Editable,
									'PropertyUsage->Editable should be set to "true" for property ' .
									'widget named "'.$propInfo->Name.'". The Action sent in ' .
									'GetDialog2 request is "SendTo". ' );
					}

					// PropertyUsage->MultipleObjects are expected to be filled in with boolean.
					$multipleObjects = $propUsage->MultipleObjects;
					// PropertyUsage->MultipleObjects should be a boolean in multi-set properties context.
					$this->assertInternalType( 'boolean', $multipleObjects,
							'PropertyUsage->MultipleObjects should be set as boolean true/false ' .
							'when getDialog2 is for multi-set properties. Please check in the ' .
							'PropertyUsage for widget named "'.$propName.'".' );

					// Static props should not be on the multi-set properties dialog.
					$isStaticProp = in_array( $propName, $staticProps ) &&
						( $propName != 'Category' && $propName != 'State' ); // Category&State are allowed in multi-set
					$this->assertFalse( $isStaticProp && $multipleObjects,
							'For static property, PropertyUsage->MultipleObjects should be set '.
							'as false even when it is for multi-set properties. Please check '.
							'in the PropertyUsage for widget named "'.$propName.'".' );

					$this->assertNotContains( $propName, $nonEditableProperties,
							'Property "'.$propName.'" cannot be changed for multiple objects ' .
							'at one time, thus should not be shown in the GetDialog2 response.' );
				}
			}
		}

		$dialogMetaData = $response->Dialog->MetaData;
		if( $dialogMetaData ) {
			$foundIds = false;
			foreach( $response->Dialog->MetaData as $propName => $mdValue ) {
				switch( $propName ) {
					case 'IDs': // "IDs" is expected in GetDialog2Resp->Dialog->MetaData.
						$returnedIds = array();
						if( $mdValue->PropertyValues ) {
							foreach( $mdValue->PropertyValues as $propValue ) {
								$returnedIds[] = $propValue->Value;
							}
						}
						$objIds = array( $this->image1->MetaData->BasicMetaData->ID,
							$this->image2->MetaData->BasicMetaData->ID,
							$this->image3->MetaData->BasicMetaData->ID );

						// Checking for IDs that should present in the response.
						$invalidIds = array_diff( $objIds, $returnedIds );
						$this->assertCount( 0, $invalidIds, 
								'Object ids "'.implode(',',$invalidIds).'" are expected in "IDs" ' .
								'returned in Dialog->MetaData. Please check getDialog2 response.');

						// Checking for IDs that are not suppose to be present in the response.
						$invalidIds = array_diff( $returnedIds, $objIds );
						$this->assertCount( 0, $invalidIds, 
								'Object ids "'.implode(',',$invalidIds).'" are not expected in "IDs" ' .
								'returned in Dialog->MetaData. Please check getDialog2 response.');

						$foundIds = true;
						break;
					case 'Category':
						$categoryName = $this->image1->MetaData->BasicMetaData->Category->Name;
						$this->assertEquals( $mdValue->PropertyValues[0]->Display, $categoryName,
								'"PropertyValues" in Dialog->MetaData should be set to ' .
								'"'. $categoryName . '" for property name "'.$propName.
								'" because this property has same value for all images. ');
						break;
					case 'State':
						$stateName = $this->image1->MetaData->WorkflowMetaData->State->Name;
						$this->assertEquals( $mdValue->PropertyValues[0]->Display, $stateName,
								'"PropertyValues" in Dialog->MetaData should be set to ' .
								'"'. $stateName . '" for property name "'.$propName.
								'" because this property has same value for all images. ');
						break;
					case 'RouteTo':
						$this->assertEquals( $mdValue->PropertyValues[0]->Display, $this->imageRouteTo,
								'"PropertyValues" in Dialog->MetaData should be set to ' .
								'"'.$this->imageRouteTo.'" for property name "RouteTo" '.
								'because this property has the same value for all images. ' );
						break;
				}
			}
			$this->assertTrue( $foundIds,
					'For multi-set properties dialog, "IDs" is expected in the '.
					'getDialog2->Dialog->MetaData response but it was not found. '.
					'Please check the getDialog2 request and response.' );
		}
	}

	/**
	 * Compose getDialog2->MetaData request.
	 *
	 * Depending on the $context to test with, the MetaData is composed differently by
	 * adding extra/invalid/unwanted parameters.
	 *
	 * @param string $context In which test case context the MetaData is being composed for.
	 * @param WflGetDialog2Response|null $getDialogResponse GetDialog2 response to redraw the next dialog|Null for first draw.
	 * @return array List of MetaDataValue.
	 */
	private function constructMetaDataForMultiSetProperties( $context, $getDialogResponse=null )
	{
		require_once BASEDIR .'/server/interfaces/services/wfl/DataClasses.php';
		$pubInfo = $this->vars['BuildTest_WebServices_WflServices']['publication'];
		$metaData = array();

		// ID / IDs
		switch( $context ) {
			case 'testIdAndIdsParam':
				// Invalid MetaData construction for multi-set properties getDialog request.
				// ID
				$propValues = array();
				$propValue = new PropertyValue();
				$propValue->Value = $this->image1->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'ID';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['ID'] = $metaDataValue;

				// IDs
				$propValues = array();
				$propValue = new PropertyValue();
				$propValue->Value = $this->image1->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image2->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'IDs';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['IDs'] = $metaDataValue;
				break;
			case 'testIdParam':
			case 'testActionParam':
			case 'testUnwantedParams':
			case 'testTrashAreaParam':
			case 'testWithValidParams':
				// Valid MetaData construction for multi-set properties getDialog request.
				// IDs
				$propValues = array();
				$propValue = new PropertyValue();
				$propValue->Value = $this->image1->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image2->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image3->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image4->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'IDs';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['IDs'] = $metaDataValue;
				break;
			case 'callGetDialog2RouteTo1_1':
			case 'callGetDialog2RouteTo1_2':
				$propValues = array();
				// Valid MetaData construction for multi-set properties getDialog request.
				// Needs images that have same categories, same routeTo and different statuses.
				// IDs
				$propValue = new PropertyValue();
				$propValue->Value = $this->image1->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image2->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image3->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image5->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'IDs';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['IDs'] = $metaDataValue;
				break;
			case 'callGetDialog2RouteTo2_1':
			case 'callGetDialog2RouteTo2_2':
				$propValues = array();
				// Valid MetaData construction for multi-set properties getDialog request.
				// Needs images that have same categories, different routeTo and different statuses.
				// IDs
				$propValue = new PropertyValue();
				$propValue->Value = $this->image1->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image2->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image3->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image5->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image6->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'IDs';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['IDs'] = $metaDataValue;
				break;
			case 'testIdsParam':
				// Invalid MetaData construction for multi-set properties getDialog request.
				// ID
				$propValues = array();
				$propValue = new PropertyValue();
				$propValue->Value = $this->image1->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'ID'; // Invalid.
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['ID'] = $metaDataValue;
				break;
			case 'testDifferentObjType':
				// Invalid MetaData for multi-set properties getDialog request.
				// IDs
				$propValues = array();
				$propValue = new PropertyValue();
				$propValue->Value = $this->image1->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image2->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->article1->MetaData->BasicMetaData->ID; // Invalid (to have article and image)
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'IDs';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['IDs'] = $metaDataValue;
				break;
			case 'testWithSendToActionParam':
				// Valid MetaData construction for multi-set properties getDialog request.
				// IDs
				$propValues = array();
				$propValue = new PropertyValue();
				$propValue->Value = $this->image1->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image2->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image3->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'IDs';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['IDs'] = $metaDataValue;
				break;
			case 'callGetDialog2RouteTo3_1':
				$propValues = array();
				// Valid MetaData construction for multi-set properties getDialog request.
				// Needs images that have same categories, different routeTo and different statuses.
				// IDs
				$propValue = new PropertyValue();
				$propValue->Value = $this->image3->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image7->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'IDs';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['IDs'] = $metaDataValue;
				break;
			case 'callGetDialog2RouteTo3_2':
				$propValues = array();
				// Valid MetaData construction for multi-set properties getDialog request.
				// Needs images that have same categories, different routeTo and different statuses.
				// IDs
				$propValue = new PropertyValue();
				$propValue->Value = $this->image3->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$propValue = new PropertyValue();
				$propValue->Value = $this->image6->MetaData->BasicMetaData->ID;
				$propValues[] = $propValue;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'IDs';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $propValues;
				$metaData['IDs'] = $metaDataValue;
				break;
		}

		// Publication
		$this->pub = new Publication( $pubInfo->Id, $pubInfo->Name );
		$propertyValue = new PropertyValue();
		$propertyValue->Value = $this->pub->Id;
		$propertyValue->Display = $this->pub->Name;

		$metaDataValue = new MetaDataValue();
		$metaDataValue->Property = 'Publication';
		$metaDataValue->Values = null;
		$metaDataValue->PropertyValues = array( $propertyValue );
		$metaData['Publication'] = $metaDataValue;

		// Type
		$propertyValue = new PropertyValue();
		$propertyValue->Value = 'Image';
		$propertyValue->Display = null;

		$metaDataValue = new MetaDataValue();
		$metaDataValue->Property = 'Type';
		$metaDataValue->Values = null;
		$metaDataValue->PropertyValues = array( $propertyValue );
		$metaData['Type']		  = $metaDataValue;

		// Handles other parameters such as Issue, Category, States, RouteTo and etc.
		switch( $context ) {
			case 'callGetDialog2RouteTo1_2':
			case 'callGetDialog2RouteTo2_2':
				$respMetaData = $getDialogResponse->Dialog->MetaData;

				// Category
				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'Category';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = $respMetaData['Category']->PropertyValues;
				$metaData['Category']	  = $metaDataValue;

				// Status
				$propertyValue = new PropertyValue();
				$propertyValue->Value =  $this->image3->MetaData->WorkflowMetaData->State->Id;

				$metaDataValue = new MetaDataValue();
				$metaDataValue->Property = 'State';
				$metaDataValue->Values = null;
				$metaDataValue->PropertyValues = array( $propertyValue );
				$metaData['State']	  = $metaDataValue;
				break;
		}

		return $metaData;
	}

	/**
	 * Validates GetDialog2 response for an initial dialog draw.
	 *
	 * @param WflGetDialog2Response $response
	 * @throws BizException on unexpected server response.
	 */
	private function validateCallGetDialog2RouteTo1_1Resp( WflGetDialog2Response $response )
	{
		$dialogTabs = $response->Dialog->Tabs;
		foreach( $dialogTabs as $dialogTab ) {
			if( $dialogTab->Title == 'General' ) {
				if( $dialogTab->Widgets ) foreach( $dialogTab->Widgets as $dialogWidget ) {
					$propInfo = $dialogWidget->PropertyInfo;
					switch( $propInfo->Name ) {
						case 'Category':
						case 'RouteTo':
							$this->assertFalse( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set '.
									'to "false" as all the testing images share the same "'.
									$propInfo->Name.'" value.' );
							break;
						case 'State':
							$this->assertTrue( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set '.
									'to "true" as the testing images do not share the same object status.' );
							break;
					}

					$propUsage = $dialogWidget->PropertyUsage;
					switch( $propUsage->Name ) {
						case 'Category':
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propUsage->MultipleObjects,
									'MultipleObjects should be set to "true" as the ' .
									'getDialog2 request was for multi-setproperties.' );
							break;
					}
				}
			}
		}

		$dialogMetaData = $response->Dialog->MetaData;
		if( $dialogMetaData ) {
			$foundIds = false;
			foreach( $response->Dialog->MetaData as $propName => $mdValue ) {
				switch( $propName ) {
					case 'IDs': // "IDs" is expected in GetDialog2Resp->Dialog->MetaData.
						$returnedIds = array();
						if( $mdValue->PropertyValues ) {
							foreach( $mdValue->PropertyValues as $propValue ) {
								$returnedIds[] = $propValue->Value;
							}
						}
						$objIds = array( $this->image1->MetaData->BasicMetaData->ID,
							$this->image2->MetaData->BasicMetaData->ID,
							$this->image3->MetaData->BasicMetaData->ID,
							$this->image5->MetaData->BasicMetaData->ID );

						// Checking for IDs that should present in the response.
						$invalidIds = array_diff( $objIds, $returnedIds );
						$this->assertCount( 0, $invalidIds, 
								'Object ids "'.implode(',',$invalidIds).'" are expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.');

						// Checking for IDs that are not suppose to be present in the response.
						$invalidIds = array_diff( $returnedIds, $objIds );
						$this->assertCount( 0, $invalidIds, 
								'Object ids "'.implode(',',$invalidIds).'" are not expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.');
								
						$foundIds = true;
						break;
					case 'Category':
						$categoryInfo = $this->vars['BuildTest_WebServices_WflServices']['category'];
						$this->assertEquals( $mdValue->PropertyValues[0]->Value, $categoryInfo->Id,
								'"PropertyValues" in Dialog->MetaData should be filled in for property ' .
								'name "'.$propName.'" because this property has the same value for all ' .
								'images. The value filled in should be "'.$categoryInfo->Id.'"');
						break;
					case 'State':
						$this->assertFalse( $mdValue->Values || $mdValue->PropertyValues,
								'"PropertyValues" in Dialog->MetaData should be empty for ' .
								'property name "'.$propName.'" because this property has mixed ' .
								'values displayed in a dialog (PropertyInfo->MixedValues = true). ');
						break;
					case 'RouteTo':
						$this->assertEquals( $mdValue->PropertyValues[0]->Display, $this->testUser2->FullName,
								'"PropertyValues" in Dialog->MetaData should be set to ' .
								'"'.$this->testUser2->FullName .'" for property name "RouteTo" '.
								'because this property has the same value for all images. ' );
						break;
				}
			}
			$this->assertTrue( $foundIds,
					'For multi-set properties dialog, "IDs" is expected in the '.
					'getDialog2->Dialog->MetaData response but it was not found. '.
					'Please check the getDialog2 request and response.' );
		}
	}

	/**
	 * Validates GetDialog2 for a redraw scenario.
	 *
	 * @param WflGetDialog2Response $response
	 * @throws BizException on unexpected server response.
	 */
	private function validateCallGetDialog2RouteTo1_2Resp( WflGetDialog2Response $response )
	{
		$dialogTabs = $response->Dialog->Tabs;
		foreach( $dialogTabs as $dialogTab ) {
			if( $dialogTab->Title == 'General' ) {
				if( $dialogTab->Widgets ) foreach( $dialogTab->Widgets as $dialogWidget ) {
					$propInfo = $dialogWidget->PropertyInfo;
					switch( $propInfo->Name ) {
						case 'Category':
						case 'RouteTo':
						case 'State':
							$this->assertFalse( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set '.
									'to "false" as all the testing images share the same "'.
									$propInfo->Name.'" value.' );
							break;
					}

					$propUsage = $dialogWidget->PropertyUsage;
					switch( $propUsage->Name ) {
						case 'Category':
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propUsage->MultipleObjects,
									'MultipleObjects should be set to "true" as the ' .
									'getDialog2 request was for multi-setproperties.' );
							break;
					}
				}
			}
		}

		$dialogMetaData = $response->Dialog->MetaData;
		if( $dialogMetaData ) {
			$foundIds = false;
			foreach( $response->Dialog->MetaData as $propName => $mdValue ) {
				switch( $propName ) {
					case 'IDs': // "IDs" is expected in GetDialog2Resp->Dialog->MetaData.
						$returnedIds = array();
						if( $mdValue->PropertyValues ) {
							foreach( $mdValue->PropertyValues as $propValue ) {
								$returnedIds[] = $propValue->Value;
							}
						}
						$objIds = array( $this->image1->MetaData->BasicMetaData->ID,
							$this->image2->MetaData->BasicMetaData->ID,
							$this->image3->MetaData->BasicMetaData->ID,
							$this->image5->MetaData->BasicMetaData->ID );

						// Checking for IDs that should present in the response.
						$invalidIds = array_diff( $objIds, $returnedIds );
						$this->assertCount( 0, $invalidIds, 
								'Object ids "'.implode(',',$invalidIds).'" are expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.' );

						// Checking for IDs that are not suppose to be present in the response.
						$invalidIds = array_diff( $returnedIds, $objIds );
						$this->assertCount( 0, $invalidIds, 
								'Object ids "'.implode(',',$invalidIds).'" are not expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.' );

						$foundIds = true;
						break;
					case 'Category':
						$categoryInfo = $this->vars['BuildTest_WebServices_WflServices']['category'];
						$this->assertEquals( $mdValue->PropertyValues[0]->Value, $categoryInfo->Id,
								'"PropertyValues" in Dialog->MetaData should be filled in for property ' .
								'name "'.$propName.'" because this property has the same value for ' .
								'all images. The value filled in should be "'.$categoryInfo->Id.'"' );
						break;
					case 'State':
						$this->assertEquals( $mdValue->PropertyValues[0]->Value, $this->image3->MetaData->WorkflowMetaData->State->Id,
								'"PropertyValues" in Dialog->MetaData should be filled in for property ' .
								'name "'.$propName.'" because this property has the same value ' .
								'for all images. The value filled in should be "' .
								$this->image3->MetaData->WorkflowMetaData->State->Id );
						break;
					case 'RouteTo':
						$this->assertEquals( $mdValue->PropertyValues[0]->Display, $this->testUser1->FullName,
								'"PropertyValues" in Dialog->MetaData should be set to ' .
								'"'.$this->testUser1->FullName.'" for property name "RouteTo" '.
								'because this property has the same value for all images. ' );
						break;
				}
			}
			$this->assertTrue( $foundIds,
					'For multi-set properties dialog, "IDs" is expected in the '.
					'getDialog2->Dialog->MetaData response but it was not found. '.
					'Please check the getDialog2 request and response.' );
		}
	}

	/**
	 * Validates GetDialog2 response for an initial dialog draw.
	 *
	 * @param WflGetDialog2Response $response
	 * @throws BizException on unexpected server response.
	 */
	private function validateCallGetDialog2RouteTo2_1Resp( WflGetDialog2Response $response )
	{
		$dialogTabs = $response->Dialog->Tabs;
		foreach( $dialogTabs as $dialogTab ) {
			if( $dialogTab->Title == 'General' ) {
				if( $dialogTab->Widgets ) foreach( $dialogTab->Widgets as $dialogWidget ) {
					$propInfo = $dialogWidget->PropertyInfo;
					switch( $propInfo->Name ) {
						case 'Category':
							$this->assertFalse( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set '.
									'to "false" as all the testing images share the same "'.
									$propInfo->Name.'" value.' );
							break;
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set '.
									'to "true" as the testing images do not share the same value for ' .
									'property "'.$propInfo->Name.'"');
							break;
					}

					$propUsage = $dialogWidget->PropertyUsage;
					switch( $propUsage->Name ) {
						case 'Category':
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propUsage->MultipleObjects,
									'MultipleObjects should be set to "true" as the ' .
									'getDialog2 request was for multi-setproperties.' );
							break;
					}
				}
			}
		}

		$dialogMetaData = $response->Dialog->MetaData;
		if( $dialogMetaData ) {
			$foundIds = false;
			foreach( $response->Dialog->MetaData as $propName => $mdValue ) {
				switch( $propName ) {
					case 'IDs': // "IDs" is expected in GetDialog2Resp->Dialog->MetaData.
						$returnedIds = array();
						if( $mdValue->PropertyValues ) {
							foreach( $mdValue->PropertyValues as $propValue ) {
								$returnedIds[] = $propValue->Value;
							}
						}
						$objIds = array( $this->image1->MetaData->BasicMetaData->ID,
							$this->image2->MetaData->BasicMetaData->ID,
							$this->image3->MetaData->BasicMetaData->ID,
							$this->image5->MetaData->BasicMetaData->ID,
							$this->image6->MetaData->BasicMetaData->ID );

						// Checking for IDs that should present in the response.
						$invalidIds = array_diff( $objIds, $returnedIds );
						$this->assertCount( 0, $invalidIds, 
								'Object ids "'.implode(',',$invalidIds).'" are expected in "IDs" ' .
								'returned in Dialog->MetaData. Please check getDialog2 response.' );

						// Checking for IDs that are not suppose to be present in the response.
						$invalidIds = array_diff( $returnedIds, $objIds );
						$this->assertCount( 0, $invalidIds, 
								'Object ids "'.implode(',',$invalidIds).'" are not expected in "IDs" ' .
								'returned in Dialog->MetaData. Please check getDialog2 response.' );

						$foundIds = true;
						break;
					case 'Category':
						$categoryInfo = $this->vars['BuildTest_WebServices_WflServices']['category'];
						$this->assertEquals( $mdValue->PropertyValues[0]->Value, $categoryInfo->Id,
								'"PropertyValues" in Dialog->MetaData should be filled in ' .
								'for property name "'.$propName.'" because this property ' .
								'has the same value for all images. The value filled in should be "'.
								$categoryInfo->Id.'"' );
						break;
					case 'State':
					case 'RouteTo':
						$this->assertFalse( $mdValue->Values || $mdValue->PropertyValues,
								'"PropertyValues" in Dialog->MetaData should be empty for property ' .
								'name "'.$propName.'" because this property has mixed ' .
								'values displayed in a dialog (PropertyInfo->MixedValues = true). ' );
						break;
				}
			}
			$this->assertTrue( $foundIds,
					'For multi-set properties dialog, "IDs" is expected in the '.
					'getDialog2->Dialog->MetaData response but it was not found. '.
					'Please check the getDialog2 request and response.' );
		}
	}

	/**
	 * Validates GetDialog2 for a redraw scenario.
	 *
	 * @param WflGetDialog2Response $response
	 * @throws BizException on unexpected server response.
	 */
	private function validateCallGetDialog2RouteTo2_2Resp( WflGetDialog2Response $response )
	{
		$dialogTabs = $response->Dialog->Tabs;
		foreach( $dialogTabs as $dialogTab ) {
			if( $dialogTab->Title == 'General' ) {
				if( $dialogTab->Widgets ) foreach( $dialogTab->Widgets as $dialogWidget ) {
					$propInfo = $dialogWidget->PropertyInfo;
					switch( $propInfo->Name ) {
						case 'Category':
						case 'State':
						case 'RouteTo':
							$this->assertFalse( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set '.
									'to "false" as all the testing images share the same "'.
									$propInfo->Name.'" value.' );
							break;
					}

					$propUsage = $dialogWidget->PropertyUsage;
					switch( $propUsage->Name ) {
						case 'Category':
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propUsage->MultipleObjects,
									'MultipleObjects should be set to "true" as the ' .
									'getDialog2 request was for multi-setproperties.' );
							break;
					}
				}
			}
		}

		$dialogMetaData = $response->Dialog->MetaData;
		if( $dialogMetaData ) {
			$foundIds = false;
			foreach( $response->Dialog->MetaData as $propName => $mdValue ) {
				switch( $propName ) {
					case 'IDs': // "IDs" is expected in GetDialog2Resp->Dialog->MetaData.
						$returnedIds = array();
						if( $mdValue->PropertyValues ) {
							foreach( $mdValue->PropertyValues as $propValue ) {
								$returnedIds[] = $propValue->Value;
							}
						}
						$objIds = array( $this->image1->MetaData->BasicMetaData->ID,
							$this->image2->MetaData->BasicMetaData->ID,
							$this->image3->MetaData->BasicMetaData->ID,
							$this->image5->MetaData->BasicMetaData->ID,
							$this->image6->MetaData->BasicMetaData->ID );

						// Checking for IDs that should present in the response.
						$invalidIds = array_diff( $objIds, $returnedIds );
						$this->assertCount( 0, $invalidIds,
								'Object ids "'.implode(',',$invalidIds).'" are expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.' );

						// Checking for IDs that are not suppose to be present in the response.
						$invalidIds = array_diff( $returnedIds, $objIds );
						$this->assertCount( 0, $invalidIds,
								'Object ids "'.implode(',',$invalidIds).'" are not expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.' );
								
						$foundIds = true;
						break;
					case 'Category':
						$categoryInfo = $this->vars['BuildTest_WebServices_WflServices']['category'];
						$this->assertEquals( $mdValue->PropertyValues[0]->Value, $categoryInfo->Id,
								'"PropertyValues" in Dialog->MetaData should be filled in ' .
								'for property name "'.$propName.'" because this property ' .
								'has the same value for all images. The value filled in should be "'.
								$categoryInfo->Id.'"' );
						break;
					case 'State':
						$this->assertEquals( $mdValue->PropertyValues[0]->Value,
							$this->image3->MetaData->WorkflowMetaData->State->Id,
								'"PropertyValues" in Dialog->MetaData should be filled in ' .
								'for property name "'.$propName.'" because this property ' .
								'has the same value for all images. The value filled in should be "'.
								$this->image3->MetaData->WorkflowMetaData->State->Id.'"' );
						break;
					case 'RouteTo':
						$this->assertEquals( $mdValue->PropertyValues[0]->Display, $this->testUser1->FullName,
								'"PropertyValues" in Dialog->MetaData should be set to ' .
								'"'.$this->testUser1->FullName.'" for property name "RouteTo" '.
								'because this property has the same value for all images. ' );
						break;
				}
			}
			$this->assertTrue( $foundIds,
					'For multi-set properties dialog, "IDs" is expected in the '.
					'getDialog2->Dialog->MetaData response but it was not found. '.
					'Please check the getDialog2 request and response.' );
		}
	}

	/**
	 * Validates GetDialog2 for a scenario where Category, Status and RouteTo have mixed values.
	 *
	 * @param WflGetDialog2Response $response
	 * @throws BizException on unexpected server response.
	 */
	private function validateCallGetDialog2RouteTo3_1Resp( WflGetDialog2Response $response )
	{
		$dialogTabs = $response->Dialog->Tabs;
		foreach( $dialogTabs as $dialogTab ) {
			if( $dialogTab->Title == 'General' ) {
				if( $dialogTab->Widgets ) foreach( $dialogTab->Widgets as $dialogWidget ) {
					$propInfo = $dialogWidget->PropertyInfo;
					switch( $propInfo->Name ) {
						case 'Category':
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set to "true" '.
									'as the testing images do not share the same value for ' .
									'property "'.$propInfo->Name.'"');
							if( $propInfo->Name == 'Category' || $propInfo->Name == 'State' ) {
								$this->assertNotCount( 0, $propInfo->Notifications,
										'There should be Notifications in GetDialog2 response '.
										'informing the user that property "'.$propInfo->Name.'" is not supported.' );
							}
						break;
					}

					$propUsage = $dialogWidget->PropertyUsage;
					switch( $propUsage->Name ) {
						case 'Category':
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propUsage->MultipleObjects,
									'MultipleObjects should be set to "true" as the ' .
									'getDialog2 request was for multi-setproperties.' );
							break;
					}
				}
			}
		}

		$dialogMetaData = $response->Dialog->MetaData;
		if( $dialogMetaData ) {
			$foundIds = false;
			foreach( $response->Dialog->MetaData as $propName => $mdValue ) {
				switch( $propName ) {
					case 'IDs': // "IDs" is expected in GetDialog2Resp->Dialog->MetaData.
						$returnedIds = array();
						if( $mdValue->PropertyValues ) {
							foreach( $mdValue->PropertyValues as $propValue ) {
								$returnedIds[] = $propValue->Value;
							}
						}
						$objIds = array( $this->image3->MetaData->BasicMetaData->ID,
							$this->image7->MetaData->BasicMetaData->ID );

						// Checking for IDs that should present in the response.
						$invalidIds = array_diff( $objIds, $returnedIds );
						$this->assertCount( 0, $invalidIds,
								'Object ids "'.implode(',',$invalidIds).'" are expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.');

						// Checking for IDs that are not suppose to be present in the response.
						$invalidIds = array_diff( $returnedIds, $objIds );
						$this->assertCount( 0, $invalidIds,
								'Object ids "'.implode(',',$invalidIds).'" are not expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.' );
						
						$foundIds = true;
						break;
					case 'Category':
					case 'State':
					case 'RouteTo':
						$this->assertFalse( $mdValue->Values || $mdValue->PropertyValues,
								'"PropertyValues" in Dialog->MetaData should be empty ' .
								'for property name "'.$propName.'" because this property has mixed ' .
								'values displayed in a dialog (PropertyInfo->MixedValues = true). ' );
						break;
				}
			}
			$this->assertTrue( $foundIds,
					'For multi-set properties dialog, "IDs" is expected in the '.
					'getDialog2->Dialog->MetaData response but it was not found. '.
					'Please check the getDialog2 request and response.' );
		}
	}

	/**
	 * Validates GetDialog2 for a scenario where Status and RouteTo have mixed values.
	 *
	 * @param WflGetDialog2Response $response
	 * @throws BizException on unexpected server response.
	 */
	private function validateCallGetDialog2RouteTo3_2Resp( WflGetDialog2Response $response )
	{
		$dialogTabs = $response->Dialog->Tabs;
		foreach( $dialogTabs as $dialogTab ) {
			if( $dialogTab->Title == 'General' ) {
				if( $dialogTab->Widgets ) foreach( $dialogTab->Widgets as $dialogWidget ) {
					$propInfo = $dialogWidget->PropertyInfo;
					switch( $propInfo->Name ) {
						case 'Category':
							$this->assertFalse( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set '.
									'to "false" as the testing images share the same value for ' .
									'property "'.$propInfo->Name.'"');
							$this->assertNotCount( 0, $propInfo->Notifications,
									'There should be Notifications in GetDialog2 response for ' .
									'property "'.$propInfo->Name.'" as this property should be disabled.' );
							break;
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propInfo->MixedValues,
									'MixedValues in GetDialog2 response should be set to "true" '.
									'as the testing images do not share the same value for ' .
									'property "'.$propInfo->Name.'"');
							if( $propInfo->Name == 'State' ) {
								$this->assertCount( 0, $propInfo->Notifications,
										'There should not be any Notifications in GetDialog2 response for ' .
										'property "'.$propInfo->Name.'" as this property should be supported.' );
							}
							break;
					}

					$propUsage = $dialogWidget->PropertyUsage;
					switch( $propUsage->Name ) {
						case 'Category':
						case 'State':
						case 'RouteTo':
							$this->assertTrue( $propUsage->MultipleObjects,
									'MultipleObjects should be set to "true" as the ' .
									'getDialog2 request was for multi-setproperties.' );
							break;
					}
				}
			}
		}

		$dialogMetaData = $response->Dialog->MetaData;
		if( $dialogMetaData ) {
			$foundIds = false;
			foreach( $response->Dialog->MetaData as $propName => $mdValue ) {
				switch( $propName ) {
					case 'IDs': // "IDs" is expected in GetDialog2Resp->Dialog->MetaData.
						$returnedIds = array();
						if( $mdValue->PropertyValues ) {
							foreach( $mdValue->PropertyValues as $propValue ) {
								$returnedIds[] = $propValue->Value;
							}
						}
						$objIds = array( $this->image3->MetaData->BasicMetaData->ID,
							$this->image6->MetaData->BasicMetaData->ID );

						// Checking for IDs that should present in the response.
						$invalidIds = array_diff( $objIds, $returnedIds );
						$this->assertCount( 0, $invalidIds,
								'Object ids "'.implode(',',$invalidIds).'" are expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.' );

						// Checking for IDs that are not suppose to be present in the response.
						$invalidIds = array_diff( $returnedIds, $objIds );
						$this->assertCount( 0, $invalidIds,
								'Object ids "'.implode(',',$invalidIds).'" are not expected in "IDs" ' .
								'returned in Dialog->MetaData.Please check getDialog2 response.' );

						$foundIds = true;
						break;
					case 'Category':
						$categoryInfo = $this->vars['BuildTest_WebServices_WflServices']['category'];
						$this->assertEquals( $mdValue->PropertyValues[0]->Value, $categoryInfo->Id,
								'"PropertyValues" in Dialog->MetaData should be filled in ' .
								'for property name "'.$propName.'" because this property ' .
								'has the same value for all images. The value filled in should be "'.
								$categoryInfo->Id.'"' );
						break;
					case 'State':
					case 'RouteTo':
						$this->assertFalse( $mdValue->Values || $mdValue->PropertyValues,
								'"PropertyValues" in Dialog->MetaData should be empty ' .
								'for property name "'.$propName.'" because this property has mixed ' .
								'values displayed in a dialog (PropertyInfo->MixedValues = true). ' );
					break;
				}
			}
			$this->assertTrue( $foundIds,
					'For multi-set properties dialog, "IDs" is expected in the '.
					'getDialog2->Dialog->MetaData response but it was not found. '.
					'Please check the getDialog2 request and response.' );
		}
	}
}