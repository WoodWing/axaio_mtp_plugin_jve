<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmStatuses_TestCase extends TestCase
{
	public function getDisplayName() { return 'Statuses properties'; }
	public function getTestGoals() { return 'Checks if status properties can be round-tripped and deleted successfully.'; }
	public function getTestMethods() { return 'Call admin status services with initial values and modified values.'; }
	public function getPrio() { return 150; }
	//public function isSelfCleaning() { return true; }

	private $publicationId = null;
	private $pubChannelId = null;
	private $ticket = null;
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;

	//for easy access and garbage collection
	private $overruleIssueId = null;
	private $issueId = null;
	private $sparePublId = null;
	private $sparePublStatusId = null;
	private $noRightsUserIds = array();

	private $existingBrandStatuses = array();
	private $existingIssueStatuses = array();
	private $brandStatusIds = array();
	private $issueStatusIds = array();

	private $statusIds = array(); //created status ids (for garbage collector)
	private $postfix = 0; //internal counter to make statuses unique
	private $action = ''; //the current action being tested

	private $objectTypes = array('Advert', 'Article', 'Image', 'LayoutTemplate', 'Library'); //used to select a semi-random object type for variety of statuses

	/**
	 * The main test function
	 *
	 * Called to execute a test case. Needs to be implemented by subclass of TestCase.
	 * There can be many steps to be tested, which all need to take place within this
	 * function. The setResult() function can be used by the implementer to report any
	 * problems found during the test. It is up to the implementer to decide whether or
	 * not to continue with the next step. Precessing errors can be detected by calling
	 * the hasError() function.
	 */
	public final function runTest()
	{
		// Init utils.
		require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR . '/server/utils/TestSuite.php';

		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		$vars = $this->getSessionVariables();
		$this->ticket = @$vars['BuildTest_WebServices_AdmServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR', 'Could not find ticket to test with.', 'Please enable the AdmInitData test.' );
			return;
		}
		$this->publicationId = @$vars['BuildTest_WebServices_AdmServices']['publicationId'];
		if( !$this->publicationId ) {
			$this->setResult( 'ERROR', 'Could not find publicationId to test with.', 'Please enable the AdmInitData test.' );
			return;
		}
		$this->pubChannelId = @$vars['BuildTest_WebServices_AdmServices']['pubChannelId'];
		if( !$this->pubChannelId ) {
			$this->setResult( 'ERROR', 'Could not find pubChannelId to test with.', 'Please enable the AdmInitData test.' );
			return;
		}

		$this->createOverrulingIssue(); //set overruling issue id
		$this->createIssue(); //set regular issue id
		$this->createSparePublication(); //set spare publication id

		$this->testCreateStatuses();
		$this->testModifyStatuses();
		$this->testGetStatuses();
		$this->testDeleteStatuses();

		$this->cleanupStatuses();
		$this->cleanupLeftoverObjects();

		//resets the changed ticket
		$vars = array();
		$vars['BuildTest_WebServices_AdmServices']['ticket'] = $this->ticket;
		$vars['BuildTest_WebServices_AdmServices']['publicationId'] = $this->publicationId;
		$vars['BuildTest_WebServices_AdmServices']['pubChannelId'] = $this->pubChannelId;
		$this->setSessionVariables( $vars );
	}

	/**
	 * Contains all functions that deal with testing the AdmCreateStatusesService
	 */
	private function testCreateStatuses()
	{
		$this->action = 'create';
		$request = $this->getRequestByAction();
		$request->PublicationId = $this->publicationId; //error is not dependent on brand or issue

		$status = $this->buildStatus();
		$status->Id = PHP_INT_MAX-1;
		$request->Statuses = array( $status );
		$stepInfo = 'Create a new status with an id given.';
		$expError = '(S1000)';
		$this->utils->callService( $this, $request, $stepInfo, $expError );

		$this->existingBrandStatuses = $this->testCreateAndModifyGoodStatuses( true ); //brand
		$this->existingIssueStatuses = $this->testCreateAndModifyGoodStatuses( false ); //issue

		$this->testCreateBadBrandStatuses();
		$this->testCreateBadIssueStatuses();
	}

	/**
	 * Contains all functions that deal with testing the AdmModifyStatusesService
	 */
	private function testModifyStatuses()
	{
		$this->action = 'modify';
		$request = $this->getRequestByAction();

		$this->testCreateAndModifyGoodStatuses( true ); //brand
		$this->testCreateAndModifyGoodStatuses( false ); //issue

		$this->testCreateAndModifyBadStatuses( $request, end($this->existingBrandStatuses), true );
		$request = $this->getRequestByAction(); //we have to get a new request with a correct ticket
		$this->testCreateAndModifyBadStatuses( $request, end($this->existingIssueStatuses), false );
	}

	/**
	 * Tests good creation and modification scenarios
	 *
	 * Next to going through some simple scenarios, this function
	 * will also be used to create some statuses to be used in
	 * further tests.
	 *
	 * @param boolean $isBrand Is brand when true, overruling issue when false
	 * @return array of AdmStatus objects to be used for testing with duplicates
	 */
	private function testCreateAndModifyGoodStatuses( $isBrand )
	{
		$request = $this->getRequestByAction();

		if( $this->action == 'create' ) {
			if( $isBrand ) {
				$request->PublicationId = $this->publicationId;
			} else {
				$request->IssueId = $this->overruleIssueId;
			}
		}
		$enviro = $isBrand ? 'brand' : 'overruling issue';

		$statuses = array();
		for( $i = 1; $i <= 3; $i++ ) {
			$status = $this->buildStatus();
			$stepInfo = '';
			switch( $i ) {
				case 1:
					$stepInfo = $this->action . ' status in ' . $enviro . ' with perfectly valid properties.';
					break;
				case 2:
					$status->Name = str_pad($status->Name, 40, '_');
					$stepInfo = $this->action . ' status in ' . $enviro . ' with an exactly fitting name.';
					break;
				case 3:
					$status->Produce = true;
					$status->ReadyForPublishing = false;
					if( count($statuses) > 0 ) {
						$lastInsertedStatus = reset($statuses);
						if( $lastInsertedStatus->Id && $lastInsertedStatus->Name )
							$status->NextStatus = new AdmIdName($lastInsertedStatus->Id, $lastInsertedStatus->Name);
					}
					$stepInfo = $this->action . ' a status in ' . $enviro . ' with some random properties set (produce,readyForPublishing,AdmIdName).';
					break;
			}

			if( $this->action == 'modify' ) {
				$status->Id = $this->getStatusId();
			}
			$request->Statuses = array( $status );
			$response = $this->utils->callService( $this, $request, $stepInfo );
			if( $this->action != 'modify' ) {
				$this->collectStatuses( @$response->Statuses );
			}
			if( $response && count($response->Statuses) ) {
				$newStatus = reset( $response->Statuses );
				$statuses[$newStatus->Id] = $newStatus; //adds status if create, replaces status if modify
			}
		}
		return $statuses;
	}

	/**
	 * Tests bad creation and modification scenarios for brands.
	 *
	 * First this function performs a couple of tests specific for
	 * brands, after which it calls the general test function for
	 * the remainder of the tests.
	 */
	private function testCreateBadBrandStatuses()
	{
		$request = $this->getRequestByAction();
		$request->PublicationId = $this->publicationId;

		// x issue id is given (alongside) a brand id (would just try to add it to an overruling issue (provided they match))
		// x give only issue id (would just try to add it to an overruling issue)

		for( $i = 1; $i <= 2; $i++ ) {
			$status = $this->buildStatus();
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$stepInfo = $this->action . ' a status in a brand with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = null;
					$stepInfo = $this->action . ' a status in a brand without giving a brand id.';
					$expError = '(S1000)';
					break;
			}

			$request->Statuses = array( $status );
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			$this->collectStatuses( @$response->Statuses );
		}
		$request->PublicationId = $this->publicationId; //set the brand id again

		$this->testCreateAndModifyBadStatuses( $request, end($this->existingBrandStatuses), true );
	}

	/**
	 * Tests bad creation and modification scenarios for overruling issues.
	 *
	 * First this function performs a couple of tests specific for
	 * overruling issues, after which it calls the general test
	 * function for the remainder of the tests.
	 */
	private function testCreateBadIssueStatuses()
	{
		$request = $this->getRequestByAction();
		$request->IssueId = $this->overruleIssueId;

		// x give only a publication id (would simply add the status for the brand)

		for( $i = 1; $i <= 3; $i++ ) {
			$stepInfo = '';
			$expError = '';
			$status = $this->buildStatus();
			switch( $i ) {
				case 1:
					$request->IssueId = PHP_INT_MAX-1;
					$stepInfo = $this->action . ' a status in overruling issue with a non-existing issue id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->IssueId = $this->issueId;
					$stepInfo = $this->action . ' a status in overruling issue with an issue id of a normal issue.';
					$expError = '(S1000)';
					break;
				case 3:
					$request->IssueId = $this->overruleIssueId; //reset the overruling issue id
					$request->PublicationId = $this->sparePublId;
					$stepInfo = $this->action . ' a status in overruling issue when the given publication id is not that of the brand related to the overruling issue.';
					$expError = '(S1000)';
					break;

			}

			$request->Statuses = array( $status );
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			$this->collectStatuses( @$response->Statuses );
		}
		$request->PublicationId = $this->publicationId; //reset the publication id to normal
		$this->testCreateAndModifyBadStatuses( $request, end($this->existingIssueStatuses), false );
	}

	/**
	 * Tests bad attempts of creating/modifying statuses
	 *
	 * This function contains general tests used for brands and
	 * overruling issues alike.
	 *
	 * @param object $request The necessary, pre-configured request object
	 * @param AdmStatus $existingStatus Used to test with a duplicate
	 * @param boolean $isBrand True if testing for brand, false if testing for overruling issue
	 */
	private function testCreateAndModifyBadStatuses( $request, $existingStatus, $isBrand )
	{
		/*
		 x any of the ids contain non-numeric string or negative number or sql injection (no ids to be concerned with)
		 */
		$request->Ticket = $this->ticket;
		$enviro = ($isBrand) ? 'brand' : 'overruling issue';
		//$noRightsUser = $this->createUser();

		for( $i = 1; $i <= 3; $i++ ) {
			$status = $this->buildStatus();
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$status->Name = str_pad('Issue_T_' . date( 'dmy_his' ), 45, '_');
					$stepInfo = $this->action . ' status in ' . $enviro . ' with name of >40 characters ';
					$expError = '(S1026)';
					break;
				case 2:
					$this->postfix += 1;
					$status->Name = 'any'.substr($this->action, 0, 2).'_T_'.date('dmy_his').'#'.$this->postfix.'` OR `x`=`x`';
					$stepInfo = $this->action . ' a status in ' . $enviro . ' with a name that tries SQL injection.';
					$expError = null; //is supposed to be handled without problem.
					break;
				case 3:
					$status->Type = $existingStatus->Type;
					$status->Name = $existingStatus->Name;
					$stepInfo = $this->action . ' a status in ' . $enviro . ' with an already existing type/name combination.';
					$expError = '(S1010)';
					break;
				/*case 4:
					DBTicket::expireTicket( $this->ticket );
					$stepInfo = $this->action . ' a status in ' . $enviro . ' while the ticket is expired.';
					$expError = '(S1043)';
					break;
				case 5:
					$this->ticket = '';
					$response = $this->utils->admLogOn( $this, $noRightsUser->Name, $noRightsUser->Password );
					BizSession::endSession();
					$request->Ticket = $response->Ticket;
					$this->ticket = $response->Ticket;
					$stepInfo = $this->action . ' a status in ' . $enviro . ' that the user has no access to.';
					$expError = '(S1002)';
					break;*/
			}

			if( $this->action == 'modify' ) {
				$status->Id = $this->getStatusId();
			}
			$request->Statuses = array( $status );
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}

		//$this->utils->admLogOff( $this, $this->ticket );
		//$this->ticket = '';

		//$response = $this->utils->admLogOn( $this );
		//$this->ticket = $response->Ticket; //reset ticket to the new admin user's ticket
		//BizSession::endSession();
	}

	/**
	 * Contains all functions that deal with testing the AdmGetStatusesService
	 */
	private function testGetStatuses()
	{
		$this->action = 'get';
		$request = $this->getRequestByAction();

		foreach( $this->existingBrandStatuses as $brandStatus ) {
			$this->brandStatusIds[] = $brandStatus->Id;
		}
		foreach( $this->existingIssueStatuses as $issueStatus ) {
			$this->issueStatusIds[] = $issueStatus->Id;
		}

		$this->testGetStatusesForBrand( $request );
		$request->Ticket = $this->ticket;
		$this->testGetStatusesForIssue( $request );
		$request->Ticket = $this->ticket;
	}

	/**
	 * Contains all functions that deal with testing the AdmDeleteStatusesService
	 */
	private function testDeleteStatuses()
	{
		$this->action = 'delete';
		$request = $this->getRequestByAction();

		$request->PublicationId = $this->publicationId;
		$request->StatusIds = $this->brandStatusIds;
		$this->testGetAndDeleteStatusesForAll( $request, true, '(some)' );
		$request->StatusIds = array( reset($this->brandStatusIds) );
		$this->testGetAndDeleteStatusesForAll( $request, true, '(one)' );

		unset( $request->PublicationId );
		$request->IssueId = $this->overruleIssueId;
		$request->StatusIds = $this->issueStatusIds;
		$this->testGetAndDeleteStatusesForAll( $request, false, '(some)' );
		$request->StatusIds = array( reset($this->issueStatusIds) );
		$this->testGetAndDeleteStatusesForAll( $request, false, '(one)' );
	}

	/**
	 * Tests all get scenarios for a brand
	 *
	 * First performs tests specifically catered to brands before calling
	 * other test functions to do more general testing.
	 *
	 * @param object $request The necessary request
	 */
	private function testGetStatusesForBrand( $request )
	{
		$request->PublicationId = PHP_INT_MAX-1;
		$stepInfo = $this->action . ' status(es) in a brand with a non-existing brand id.';

		$request->StatusIds = null;
		$this->utils->callService( $this, $request, $stepInfo . ' (empty)', '(S1056)' );
		$request->StatusIds = $this->brandStatusIds;
		$this->utils->callService( $this, $request, $stepInfo . ' (some)', '(S1000)' );
		$request->StatusIds = array( reset($this->brandStatusIds) );
		$this->utils->callService( $this, $request, $stepInfo . ' (one)', '(S1000)' );

		$request->PublicationId = $this->publicationId; //set brand id back to normal

		$request->StatusIds = null;
		$this->testGetAndDeleteStatusesForAll( $request, true, '(empty)' );
		$request->StatusIds = $this->issueStatusIds;
		$this->testGetAndDeleteStatusesForAll( $request, true, '(some)' );
		$request->StatusIds = array( reset($this->issueStatusIds) );
		$this->testGetAndDeleteStatusesForAll( $request, true, '(one)' );
	}

	/**
	 * Tests all get scenarios for an overruling issue
	 *
	 * First performs tests specifically catered to overruling issues before calling
	 * other test functions to do more general testing.
	 *
	 * @param object $request The necessary request
	 */
	private function testGetStatusesForIssue( $request )
	{
		for( $i = 1; $i <= 3; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->IssueId = PHP_INT_MAX-1;
					$stepInfo = $this->action . ' status(es) in overruling issue with a non-existing issue id.';

					$request->StatusIds = null;
					$this->utils->callService( $this, $request, $stepInfo . ' (empty)', '(S1056)' );
					$request->StatusIds = $this->issueStatusIds;
					$this->utils->callService( $this, $request, $stepInfo . ' (some)', '(S1000)' );
					$request->StatusIds = array( reset($this->issueStatusIds) );
					$this->utils->callService( $this, $request, $stepInfo . ' (one)', '(S1000)' );
					break;
				case 2:
					$request->IssueId = $this->issueId;
					$stepInfo = $this->action . ' a status in overruling issue with the issue id of a normal issue.';
					$expError = '(S1000)';
					break;
				case 3:
					$request->IssueId = $this->overruleIssueId; //reset the issue id to the overruling issue
					$request->PublicationId = $this->sparePublId;
					$stepInfo = $this->action . ' a status in overruling issue with a brand id that does not match the owner of the issue.';
					$expError = '(S1000)';
					break;
			}
			if( $i == 1) {
				continue;
			}
			$request->StatusIds = null;
			$this->utils->callService( $this, $request, $stepInfo . ' (empty)', $expError );
			$request->StatusIds = $this->issueStatusIds;
			$this->utils->callService( $this, $request, $stepInfo . ' (some)', $expError );
			$request->StatusIds = array( reset($this->issueStatusIds) );
			$this->utils->callService( $this, $request, $stepInfo . ' (one)', $expError );
		}

		$request->PublicationId = $this->publicationId; //reset the publicationid to negate the last test

		$request->StatusIds = null;
		$this->testGetAndDeleteStatusesForAll( $request, false, '(empty)' );
		$request->StatusIds = $this->issueStatusIds;
		$this->testGetAndDeleteStatusesForAll( $request, false, '(some)' );
		$request->StatusIds = array( reset($this->issueStatusIds) );
		$this->testGetAndDeleteStatusesForAll( $request, false, '(one)' );
	}

	/**
	 * Tests bad get scenarios
	 *
	 * Contains tests that are shared between brands and issues
	 * and all types of get scenarios (all / some / one)
	 *
	 * @param object $request The necessary request
	 * @param boolean $isBrand Brand if true, overruling issue if false
	 * @param string $statusIdsType Used to keep track of what kind of StatusIds are to be used in a test
	 */
	private function testGetAndDeleteStatusesForAll( $request, $isBrand, $statusIdsType = 'input' )
	{
		//reinforce the right ticket, when this function is called multiple times the old ticket is still in the request (due to test 7)
		$request->Ticket = $this->ticket;
		$enviro = ($isBrand) ? 'brand' : 'overruling issue';
		//$noRightsUser = $this->createUser();

		//if( $request->StatusIds ) { $tempFirstStatusId = reset( $request->StatusIds ); }
		$i = ($request->StatusIds) ? 1 : 4; //not all tests can be done when statusIds is null
		for( $i = 1; $i <= 2; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1: //id with string
					$request->StatusIds[0] = 'illegal id';
					$stepInfo = $this->action . ' status(es) in ' . $enviro . ' using a non-numeric id. ' . $statusIdsType;
					$expError = '(S1000)';
					break;
				case 2:
					$request->StatusIds[0] = '123 OR 1=1';
					$stepInfo = $this->action . ' status(es) in ' . $enviro . ' with an id that tries SQL injection. ' . $statusIdsType;
					$expError = '(S1000)';
					break;
				/*case 3:
					$request->StatusIds[0] = -1 * intval( $request->StatusIds[0] );
					$stepInfo = $this->action . ' status(es) in ' . $enviro . ' when an id is negative. ' . $statusIdsType;
					$expError = '(S1000)';
					break;
				case 4:
					if( $request->StatusIds ) {
						$request->StatusIds[0] = $tempFirstStatusId; //reset statusid
					}
					DBTicket::expireTicket( $this->ticket );
					$stepInfo = $this->action . ' status(es) in ' . $enviro . ' while the ticket is expired. ' . $statusIdsType;
					$expError = '(S1043)';
					break;
				case 5:
					$this->ticket = '';
					$response = $this->utils->admLogOn( $this, $noRightsUser->Name, $noRightsUser->Password );
					BizSession::endSession();
					$request->Ticket = $response->Ticket;
					$this->ticket = $response->Ticket;
					$stepInfo = $this->action . ' status(es) in ' . $enviro . ' that the user has no access to. ' . $statusIdsType;
					$expError = '(S1002)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		//$this->utils->admLogOff( $this, $this->ticket );
		//$this->ticket = '';

		//$response = $this->utils->admLogOn( $this );
		//$this->ticket = $response->Ticket; //reset ticket to the new admin user's ticket
		//BizSession::endSession();
	}

	/**
	 * Returns the first created status id.
	 *
	 * @return integer
	 */
	private function getStatusId()
	{
		return reset( $this->statusIds );
	}

	/**
	 * Builds a valid base status for testing purposes.
	 *
	 * @return AdmStatus
	 */
	private function buildStatus()
	{
		$this->postfix += 1; //avoid duplicate names when many created within the same second
		$status = new AdmStatus();
		$status->Name = 'Status'.substr($this->action, 0, 2).'_T_' . date( 'dmy_his' ) . '#' . $this->postfix;
		$status->Produce = false;
		$status->Color = 'A0A0A0';
		$status->SortOrder = 0 + $this->postfix;
		$status->Type = $this->objectTypes[rand(0, count($this->objectTypes)-1)];
		return $status;
	}

	/**
	 * Garbage collector, caching statuses to cleanup later.
	 *
	 * @param array $statuses AdmStatus[]
	 */
	private function collectStatuses( $statuses )
	{
		if( $statuses ) foreach( $statuses as $status ) {
			$this->statusIds[] = $status->Id;
		}
	}

	/**
	 * Called after the tests are done to clean up all statuses.
	 */
	private function cleanupStatuses()
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';
		$request = new AdmDeleteStatusesRequest();
		$request->Ticket = $this->ticket;
		$request->StatusIds = $this->brandStatusIds;
		$stepInfo = 'Garbage collector removing statuses from brand';
		$this->utils->callService( $this, $request, $stepInfo );
		$request->StatusIds = $this->issueStatusIds;
		$stepInfo = 'Garbage collector removing statuses from issue';
		$this->utils->callService( $this, $request, $stepInfo );

	}

	/**
	 * Called after all the tests are done to clean up all
	 * objects used to test the statuses with.
	 */
	private function cleanupLeftoverObjects()
	{
		require_once( BASEDIR . '/server/services/adm/AdmDeleteIssuesService.class.php' );
		$request = new AdmDeleteIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->IssueIds = array( $this->overruleIssueId, $this->issueId );
		$this->utils->callService( $this, $request, 'Delete issues created for tests (garbage collection).' );

		require_once( BASEDIR . '/server/services/adm/AdmDeletePublicationsService.class.php' );
		$request = new AdmDeletePublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationIds = array( $this->sparePublId );
		$this->utils->callService( $this, $request, 'Delete spare publication created for tests (garbage collection).' );

		require_once( BASEDIR . '/server/services/adm/AdmDeleteUsersService.class.php' );
		$request = new AdmDeleteUsersRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds = $this->noRightsUserIds;
		$this->utils->callService( $this, $request, 'Delete users created for tests (garbage collection).' );
	}

	/**
	 * Provides a request object based on the current action of the buildtest
	 * @return AdmCreateStatusesRequest|AdmDeleteStatusesRequest|AdmGetStatusesRequest|AdmModifyStatusesRequest|null
	 */
	private function getRequestByAction()
	{
		$request = null;
		switch( $this->action ) {
			case 'create':
				require_once(BASEDIR . '/server/services/adm/AdmCreateStatusesService.class.php');
				$request = new AdmCreateStatusesRequest();
				break;
			case 'modify':
				require_once(BASEDIR . '/server/services/adm/AdmModifyStatusesService.class.php');
				$request = new AdmModifyStatusesRequest();
				break;
			case 'get':
				require_once(BASEDIR . '/server/services/adm/AdmGetStatusesService.class.php');
				$request = new AdmGetStatusesRequest();
				break;
			case 'delete':
				require_once(BASEDIR . '/server/services/adm/AdmDeleteStatusesService.class.php');
				$request = new AdmDeleteStatusesRequest();
				break;
		}
		$request->Ticket = $this->ticket;
		return $request;
	}


	/* - - - - - - - - - LEFTOVER UTIL FUNCTIONS - - - - - - - - - */

	/**
	 * Creates an overruling issue.
	 */
	private function createOverrulingIssue()
	{
		$overruleIssue = new AdmIssue();
		$overruleIssue->Name = 'OverruleIssue_T_' . date( 'dmy_his' );
		$overruleIssue->Description = 'Created overrule issue';
		$overruleIssue->OverrulePublication = true; //overrule the brand
		$overruleIssue->EmailNotify = false;
		$overruleIssue->Subject = 'An issue that overrules the brand and is created for the AdmStatuses_TestCase.';
		$overruleIssue->Activated = true;

		require_once(BASEDIR . '/server/services/adm/AdmCreateIssuesService.class.php');
		$service = new AdmCreateIssuesService();
		$req = new AdmCreateIssuesRequest();
		$req->RequestModes = array();
		$req->Ticket = $this->ticket;
		$req->PublicationId = $this->publicationId;
		$req->PubChannelId = $this->pubChannelId;
		$req->Issues = array( $overruleIssue );
		$response = $service->execute( $req );
		$this->overruleIssueId = $response ? $response->Issues[0]->Id : null;
	}

	/**
	 * Creates a regular issue
	 *
	 * The regular issue is used to test doing requests with an issue that does not
	 * overrule its brand.
	 */
	private function createIssue()
	{
		$issue = new AdmIssue();
		$issue->Name = 'Issue_T_' . date( 'dmy_his' );
		$issue->Description = 'Created issue';
		$issue->OverrulePublication = false;
		$issue->EmailNotify = false;
		$issue->Subject = 'A regular issue that is created for the AdmStatuses_TestCase.';
		$issue->Activated = true;

		require_once( BASEDIR . '/server/services/adm/AdmCreateIssuesService.class.php' );
		$service = new AdmCreateIssuesService();
		$request = new AdmCreateIssuesRequest();
		$request->RequestModes = array();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->PubChannelId = $this->pubChannelId;
		$request->Issues = array( $issue );
		$response = $service->execute( $request );
		$this->issueId = $response ? $response->Issues[0]->Id : null;
	}


	/**
	 * Creates a spare publication that has no ties to any issue
	 *
	 *
	 */
	private function createSparePublication()
	{
		$publication = new AdmPublication();
		$publication->Name = 'SparePublication_T_' . date( 'dmy_his' );
		$publication->Description = 'Created spare publication';

		require_once( BASEDIR . '/server/services/adm/AdmCreatePublicationsService.class.php' );
		$service = new AdmCreatePublicationsService();
		$request = new AdmCreatePublicationsRequest();
		$request->RequestModes = array();
		$request->Ticket = $this->ticket;
		$request->Publications = array( $publication );
		$response = $service->execute( $request );
		$this->sparePublId = $response ? $response->Publications[0]->Id : null;
	}

	/**
	 * Creates a regular user
	 *
	 * This regular user is made to have no rights over any brand or issue.
	 *
	 * @return AdmUser
	 */
	private function createUser()
	{
		$password = 'norightsuser';

		$this->postfix += 1;
		$user = new AdmUser();
		$user->Name = 'User_Temp_NoRights_' . date( 'dmy_his' ) . '#' . $this->postfix;
		$user->FullName = 'Temporary Test User' . date( 'dmy_his' ) . '#' . $this->postfix;
		$user->Password = $password;

		require_once( BASEDIR . '/server/services/adm/AdmCreateUsersService.class.php' );
		$service = new AdmCreateUsersService();
		$request = new AdmCreateUsersRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Users = array( $user );
		$response = $service->execute( $request );

		$this->noRightsUserIds[] = $response->Users[0]->Id; //store id for cleanup purposes
		$user = $response->Users[0];
		$user->Password = $password; //set password back so it can be used to login with (NOT NORMAL PROCEDURE)
		return $user;
	}
}