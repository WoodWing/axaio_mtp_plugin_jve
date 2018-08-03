<?php
/**
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmAccessProfiles_TestCase extends TestCase
{
	public function getDisplayName() { return 'Access profiles and profile features'; }
	public function getTestGoals() { return 'Checks if access profiles and their features can be round-tripped and deleted successfully.'; }
	public function getTestMethods() { return 'Call admin access profile services with initial values and modified values.'; }
	public function getPrio() { return 160; }
	public function isSelfCleaning() { return true; }

	private $ticket = null;
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;

	private $accessProfileIds = array(); //list of ids of created access profiles
	private $postfix = 0; //incremented after every created object to ensure unique names etc.

	private $existingName = ''; //holds the name of an access profile to test existing-name scenarios
	private $existingAccessProfile = null; //holds an access profile for 'duplicate' tests
	private $sysProfileFeatures = null; //holder for all

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

		$this->testCreateAccessProfiles();
		$this->testModifyAccessProfiles();
		$this->testGetAccessProfiles();
		$this->testDeleteAccessProfiles();
	}

	/**
	 * Tests all access profile scenarios related to creation.
	 *
	 * Tests both good and bad access scenarios.
	 */
	private function testCreateAccessProfiles()
	{
		$this->testCreateGoodAccessProfiles();
		$this->testCreateBadAccessProfiles();
	}

	/**
	 * Tests good access profile creation scenarios.
	 *
	 * Access profiles created in this function are used in all further testing.
	 */
	private function testCreateGoodAccessProfiles()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAccessProfilesService.class.php';
		$request = new AdmCreateAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		for( $i = 1; $i <= 4; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->AccessProfiles = array( $this->buildAccessProfile() );
					$stepInfo = 'Create a regular access profile with some features.';
					break;
				case 2:
					$request->AccessProfiles = array( $this->buildAccessProfile( false ) );
					$this->existingName = $request->AccessProfiles[0]->Name; //set the existing name for later
					$stepInfo = 'Create an access profile without any features.';
					break;
				case 3:
					$accessProfile = $this->buildAccessProfile();
					$accessProfile->Name = 'any_T_'.date('dmy_his').'#'.$this->postfix.'` OR `x`=`x`';
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Create an access profile with sql injection.';
					break;
				case 4:
					$accessProfile = $this->buildAccessProfile();
					$profileFeatures = $accessProfile->ProfileFeatures;
					$profileFeatures += array_slice( $profileFeatures, 0, ceil( count( $profileFeatures )/3 ), true );
					$accessProfile->ProfileFeatures = $profileFeatures;
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Create an access profile with some features occurring twice.';
					//no expected error, should be ignored
					break;
			}
			$response = $this->utils->callService( $this, $request, $stepInfo );

			if( $response && count( $response->AccessProfiles ) ) {
				$this->collectAccessProfileIds( $response->AccessProfiles );
				if( !$this->existingAccessProfile ) {
					$this->existingAccessProfile = $response->AccessProfiles[0];
				}
				//only add these if created AccessProfiles have to be returned
				//$newAccessProfile = $response->AccessProfiles[0];
				//$accessProfiles[$newAccessProfile->Id] = $newAccessProfile; //adds access profile if create, replaces access profile if modify
			}
		}
	}

	/**
	 * Tests bad access profile creation scenarios.
	 */
	private function testCreateBadAccessProfiles()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAccessProfilesService.class.php';
		$request = new AdmCreateAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		$accessProfile = $this->buildAccessProfile( false );

		for( $i = 1; $i <= 4; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$accessProfile->Id = 1;
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Create an access profile while providing an id.';
					$expError = '(S1000)';
					break;
				case 2:
					$accessProfile->Id = null;
					$accessProfile->Name = null;
					$stepInfo = 'Create an access profile with an empty name';
					$expError = '(S1000)';
					break;
				case 3:
					$accessProfile->Id = null;
					$accessProfile->Name = $this->existingName;
					$stepInfo = 'Create an access profile with an already-existing name.';
					$expError = '(S1010)';
					break;
				case 4:
					$accessProfile = $this->buildAccessProfile();
					$profileFeature = new AdmProfileFeature();
					$profileFeature->Name = 'Do_not_exist';
					$profileFeature->DisplayName = 'I am not a valid profile feature.';
					$profileFeature->Value = 'Yes';
					$accessProfile->ProfileFeatures[] = $profileFeature;
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Create an access profile with a non-existing profile feature.';
					$expError = null; //for now ignore it, as it is solved during resolving for ProfileFeature. TODO: Discuss with Edwin
					break;
				/*case 5:
					$stepInfo = 'Create an access profile with a user that has no access to the access profiles.';
					$expError = '(S1002)';
					break;
				case 6:
					$stepInfo = 'Create an access profile while the ticket is expired.';
					$expError = '(S1043)';
					break;*/
			}
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			if( $response && count( $response->AccessProfiles ) > 0) { //access profile might have evaded tests, still needs to be deleted in the end
				$this->collectAccessProfileIds( $response->AccessProfiles );
			}
		}
	}

	/**
	 * Tests all access profile scenarios related to modification
	 */
	private function testModifyAccessProfiles()
	{
		$this->testModifyGoodAccessProfiles();
		$this->testModifyBadAccessProfiles();
	}

	/**
	 * Tests all good access profile modification scenarios.
	 *
	 * This function only uses the first created access
	 * profile and keeps modifying and saving that one.
	 */
	private function testModifyGoodAccessProfiles()
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';
		$request = new AdmModifyAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		for( $i = 1; $i <= 5; $i++ ) {
			$stepInfo = '';
			$expError = '';
			$accessProfile = $this->buildAccessProfile(); //reset the access profile
			$accessProfile->Id = $this->existingAccessProfile->Id; //give it an existing id so it is a legitimate modify
			switch( $i ) {
				case 1:
					$accessProfile->Name = 'Modified_T_' . date( 'dmy_his' ) . '_' . $this->postfix;
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Modify an access profile to give it a different, valid name.';
					break;
				case 2:
					$accessProfile->ProfileFeatures = array();
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Modify an access profile to remove all its features.';
					break;
				case 3:
					$accessProfile->Name = null;
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Modify an access profile to give it an empty name.';
					break;
				case 4:
					$accessProfile->Name = 'sql_T_' . date( 'dmy_his' ) . '_' . $this->postfix . '\' OR `1`=`1`';
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Modify an access profile to give it a name with SQL injection.';
					break;
				case 5:
					$profileFeatures = $accessProfile->ProfileFeatures;
					$profileFeatures += array_slice( $profileFeatures, 0, ceil( count( $profileFeatures )/3 ), true );
					$accessProfile->ProfileFeatures = $profileFeatures;
					$request->AccessProfiles = array( $accessProfile );
					$stepInfo = 'Modify an access profile to give it some profile features occurring twice.';
					break;
			}
			$response = $this->utils->callService( $this, $request, $stepInfo );

			if( $response && count($response->AccessProfiles) > 0 ) {
				$this->collectAccessProfileIds( $response->AccessProfiles );
			}
		}
	}

	/**
	 * Tests all bad access profile modification scenarios.
	 */
	private function testModifyBadAccessProfiles()
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';
		$request = new AdmModifyAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		for( $i = 1; $i <= 6; $i++ ) {
			$accessProfile = $this->buildAccessProfile( false );
			$request->AccessProfiles = array( $accessProfile );
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$accessProfile->Id = $this->existingAccessProfile->Id;
					$accessProfile->Name = $this->existingName;
					$stepInfo = 'Modify an access profile to give it the name of an already existing access profile.';
					$expError = '(S1010)';
					break;
				case 2:
					$accessProfile->Id = PHP_INT_MAX-1;
					$stepInfo = 'Modify an access profile with a non-existing access profile id.';
					$expError = '(S1056)';
					break;
				case 3:
					$accessProfile->Id = -30;
					$stepInfo = 'Modify an access profile to give it an id that contains a negative number.';
					$expError = '(S1000)';
					break;
				case 4:
					$accessProfile->Id = 'Wrong id';
					$stepInfo = 'Modify an access profile to give it an id that contains a non-numeric string.';
					$expError = '(S1000)';
					break;
				case 5:
					$accessProfile->Id = '1` OR 1=1';
					$stepInfo = 'Modify an access profile to give it an id that contains SQL injection.';
					$expError = '(S1000)';
					break;
				case 6:
					$accessProfile->Id = $this->existingAccessProfile->Id;
					$profileFeature = new AdmProfileFeature();
					$profileFeature->Name = 'Do_not_exist';
					$profileFeature->DisplayName = 'I am not a valid profile feature.';
					$profileFeature->Value = 'Yes';
					$accessProfile->ProfileFeatures[] = $profileFeature;
					$stepInfo = 'Modify an access profile with non-existing profile features.';
					$expError = null; //for now ignore it, as it is solved during resolving for ProfileFeature. TODO: Discuss with Edwin
					break;
				/*case 7:
					$stepInfo = 'Modify an access profile with a user that has no access to the access profiles.';
					$expError = '(S1002)';
					break;
				case 8:
					$stepInfo = 'Modify an access profile while the ticket is expired.';
					$expError = '(S1043)';
					break;*/
			}
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			if( $response && count( $response->AccessProfiles ) > 0) { //access profile might have evaded tests, still needs to be deleted in the end
				$this->collectAccessProfileIds( $response->AccessProfiles );
			}
		}
	}

	/**
	 * Tests all access profile scenarios related to getting
	 *
	 * Tests both good and bad scenarios
	 */
	private function testGetAccessProfiles()
	{
		//Testing with GetProfileFeatures and requesting all access profiles
		$this->testGetGoodAccessProfiles( true, null );
		//Testing with GetProfileFeatures and requesting some access profiles
		$this->testGetGoodAccessProfiles( true, $this->accessProfileIds );
		//Testing without GetProfileFeatures and requesting all access profiles
		$this->testGetGoodAccessProfiles( false, null );
		//Testing without GetProfileFeatures and an empty access profile id array
		$this->testGetGoodAccessProfiles( false, $this->accessProfileIds );

		$this->testGetBadAccessProfiles();
	}

	/**
	 * Tests all good access profile get scenarios.
	 *
	 * @param boolean $getProfileFeatures If true, RequestModes is set with 'GetProfileFeatures'.
	 * @param integer[]|null $profileIds
	 */
	private function testGetGoodAccessProfiles( $getProfileFeatures, $profileIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';
		$request = new AdmGetAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		if( $getProfileFeatures ) {
			$request->RequestModes = array( 'GetProfileFeatures' );
			$stepInfoFeatures = 'with';
		} else {
			$request->RequestModes = array();
			$stepInfoFeatures = 'without';
		}
		$request->AccessProfileIds = $profileIds;
		$stepInfoAmount = ($profileIds) ? 'some' : 'all';
		$stepInfo = 'Get ' . $stepInfoAmount . ' access profiles ' . $stepInfoFeatures . ' profile features.';

		$this->utils->callService( $this, $request, $stepInfo );

		//TODO: add validation of the get if deemed necessary by Edwin
	}

	/**
	 * Tests all bad access profile get scenarios.
	 */
	private function testGetBadAccessProfiles()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';
		$request = new AdmGetAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array( 'GetProfileFeatures' );

		for( $i = 1; $i <= 4; $i++ ) {
			$request->AccessProfileIds = $this->accessProfileIds;
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->AccessProfileIds[0] = PHP_INT_MAX-1;
					$stepInfo = 'Get one or more access profiles with non-existing profile ids.';
					$expError = null; //is simply ignored by the database layer, not queried
					break;
				case 2:
					$request->AccessProfileIds[0] = 'Wrong id';
					$stepInfo = 'Get one or more access profiles with any of the ids containing a non-numeric string.';
					$expError = '(S1000)';
					break;
				case 3:
					$request->AccessProfileIds[0] = -30;
					$stepInfo = 'Get one or more access profiles with any of the ids containing a negative number.';
					$expError = '(S1000)';
					break;
				case 4:
					$request->AccessProfileIds[0] = '1 OR 1=1;';
					$stepInfo = 'Get one or more access profiles with any of the ids containing SQL injection.';
					$expError = '(S1000)';
					break;
				/*case 5:
					$stepInfo = 'Get one or more access profiles with a user that has no access to the access profiles.';
					$expError = '(S1002)';
					break;
				case 6:
					$stepInfo = 'Get one or more access profiles while the ticket expired.';
					$expError = '(S1043)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
	}

	/**
	 * Tests all access profile scenarios related to deletion
	 */
	private function testDeleteAccessProfiles()
	{
		$this->testDeleteBadAccessProfiles();
		$this->testDeleteGoodAccessProfiles();
	}

	/**
	 * Tests bad access profile deletion scenarios
	 *
	 * NOTE: This function is not used at the time,
	 * as the only tests it needs to do are troubling to
	 * get right due to current application restrictions.
	 */
	private function testDeleteBadAccessProfiles()
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';
		$request = new AdmDeleteAccessProfilesRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 2; $i++ ) {
			$request->AccessProfileIds = $this->accessProfileIds;
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->AccessProfileIds = array( 'Invalid id' );
					$stepInfo = 'Delete an access profile with a non-numeric string as id.';
					$expError = '(S1000)';
					break;
				case 2:
					$request->AccessProfileIds = array( '1 OR 1=1' );
					$stepInfo = 'Delete an access profile with SQL injection.';
					$expError = '(S1000)';
					break;
				/*case 3:
					$request->AccessProfileIds = array( -30 );
					$stepInfo = 'Delete an access profile with a negative id.';
					$expError = '(S1000)';
					break;
				case 4:
					$stepInfo = 'Delete one or more access profiles with a user that has no access to the access profiles.';
					$expError = '(S1002)';
					break;
				case 5:
					$stepInfo = 'Delete one or more access profiles while the ticket expired.';
					$expError = '(S1043)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
	}

	/**
	 * Tests good access profile deletion scenarios.
	 *
	 * This function is also used as garbage collector
	 * for the database.
	 */
	private function testDeleteGoodAccessProfiles()
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';
		$request = new AdmDeleteAccessProfilesRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 1; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->AccessProfileIds = $this->accessProfileIds;
					$stepInfo = 'Delete all leftover access profiles (cleanup).';
					break;
			}
			$this->utils->callService( $this, $request, $stepInfo );
		}
	}


	/*********************** UTILITY FUNCTIONS ***********************/

	/**
	 * Creates an access profile object to be used in test scenarios.
	 *
	 * @param bool $setProfileFeatures (standard=true)
	 * @return AdmAccessProfile
	 */
	private function buildAccessProfile( $setProfileFeatures=true )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$this->postfix += 1;
		$accessProfile = new AdmAccessProfile();
		$accessProfile->Name = 'AccessProfile_T_' . date( 'dmy_his' ) . '_' . $this->postfix;
		$accessProfile->Description = 'This is an access profile.';
		$accessProfile->SortOrder = $this->postfix;
		BizSession::startSession( $this->ticket );
		$accessProfile->ProfileFeatures = ( $setProfileFeatures ) ? $this->buildArrayOfProfileFeatures( 10 ) : null;
		BizSession::endSession();

		return $accessProfile;
	}

	/**
	 * Creates a list of profile features, based on number requested.
	 *
	 * @param integer $nFeatures
	 * @return AdmProfileFeature[]
	 */
	private function buildArrayOfProfileFeatures( $nFeatures )
	{
		$profileFeatures = array();

		require_once BASEDIR . '/server/bizclasses/BizAccessFeatureProfiles.class.php';

		if(	!$this->sysProfileFeatures ) { //prevent from getting them multiple times, once is enough
			$this->sysProfileFeatures = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
		}

		foreach( $this->sysProfileFeatures as $profileFeatureId => $profileFeature ) {
			$admProfileFeature = new AdmProfileFeature();
			$admProfileFeature->Name = $profileFeature->Name;
			$admProfileFeature->Value = ( $profileFeatureId%2 == 0 ) ? 'Yes' : 'No'; //every service must be able to handle both at all times
			$profileFeatures[] = $admProfileFeature;

			if( $nFeatures > 0 ) {
				$nFeatures--;
			} else {
				break;
			}
		}
		return $profileFeatures;
	}

	/**
	 * Collects ids of creates access profiles for clean-up.
	 * @param AdmAccessProfile[] $accessProfiles
	 */
	private function collectAccessProfileIds( $accessProfiles )
	{
		if( $accessProfiles ) foreach( $accessProfiles as $accessProfile ) {
			if( !in_array( $accessProfile->Id, $this->accessProfileIds ) )
				$this->accessProfileIds[] = $accessProfile->Id;
		}
	}
}
