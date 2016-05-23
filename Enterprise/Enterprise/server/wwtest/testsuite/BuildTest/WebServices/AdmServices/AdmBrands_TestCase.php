<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmBrands_TestCase extends TestCase
{
	public function getDisplayName() { return 'Brand properties'; }
	public function getTestGoals()   { return 'Checks if brand properties can be round-tripped and deleted successfully. '; }
	public function getTestMethods() { return 'Call admin brand services with initial property values, modified property values, and delete the created publication'; }
    public function getPrio()        { return 110; }

	private $publication = null; // AdmPublication
	private $ticket = null; // string, session ticket for admin user
	private $utils = null; // WW_Utils_TestSuite

	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the Ticket that has been determined by AdmInitData TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_AdmServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the AdmInitData test.' );
			return;
		}

		// Perform the test with CustomAdminPropsDemo plug-in activated.
		$didActivate = $this->utils->activatePluginByName( $this, 'CustomAdminPropsDemo' );
		if( is_null($didActivate) ) { // error?
			return;
		}

		// Run tests.
		$this->testCreatePublication();
		$this->testModifyPublication();
		$this->testGetPublication();
		$this->testDeletePublication();
		
		// Restore plugin activation.
		if( !$didActivate ) { // if we did not activate, it was activated before, so we restore by activation.
			$this->utils->activatePluginByName( $this, 'CustomAdminPropsDemo' );
		}
	}
	
	/**
	 * Creates a test Publication in the DB by calling CreatePublications admin web service.
	 * Errors when the returned publication is not the same as the created one.
	 */
	private function testCreatePublication()
	{
		// Create a new test Publication data object in memory.
		$admPub = new AdmPublication();
		$admPub->Name = 'Brand_C_' . date('dmy_his');
		$admPub->Description       = 'Created Brand'; 
		$admPub->SortOrder         = 1;
		$admPub->EmailNotify       = true;
		$admPub->ReversedRead      = true;
		$admPub->AutoPurge         = 1; // 'threshold'(in days) of which deletedObjects should be purged by auto purge
		$admPub->DefaultChannelId  = 0;
		$admPub->ExtraMetaData     = array( // since 9.0
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_USERNAME', array('create brand test 01a') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_PASSWORD', array('create brand test 01b') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_TRAFFIC',  array('red') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SHOPPING', array('create brand test 01c') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_KEYWORDS', array('create brand test 01d', 'create brand test 01e', 'create brand test 01f') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_STORY',    array('create brand test 01g') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_PROFITS',  array(0.1) ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_HITCOUNT', array(1) ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SINCE',    array('2013-01-01T01:01:01') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SAVE',     array(true) ),
		);
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$admPub->ExtraMetaData = BizAdmProperty::sortCustomProperties( $admPub->ExtraMetaData );
		$this->publication = $admPub;
		
		// Create the test Publication in the DB.
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationsService.class.php';
		$request = new AdmCreatePublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Publications = array( $this->publication );
		
		$stepInfo = 'Testing on CreatePublications web service.';
		$response = $this->utils->callService( $this,  $request, $stepInfo );
		
		// Error when the returned publication is not the same as the created one.
		if( !is_null( $response ) ) {
			$responsePub = isset( $response->Publications[0] ) ? $response->Publications[0] : null;
			$this->publication->Id = $responsePub->Id;
			$this->validateAdmPublication( $responsePub, 'CreatePublications' );
			$this->publication = $responsePub;
		}
	}	

	/**
	 * Updates the DB with the modified test Publication by calling ModifyPublications admin web service.
	 * Errors when the returned publication is not the same as the modified one.
	 */	
	private function testModifyPublication()
	{
		// When creation failed, bail out sliently since there is nothing to test here.
		if( !$this->publication ) {
			return;
		}
		
		// Modifiy the test Publication by changing some of its properties.
		$admPub = &$this->publication;
		$admPub->Name              = 'Brand_M_' . date('dmy_his');
		$admPub->Description       = 'Modified Brand';
		$admPub->SortOrder         = 10;
		$admPub->EmailNotify       = false;
		$admPub->ReversedRead      = false;
		$admPub->AutoPurge         = 7; // 'threshold'(in days) of which deletedObjects should be purged by auto purge
		$admPub->ExtraMetaData     = array( // since 9.0
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_USERNAME', array('modify brand test 02a') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_PASSWORD', array('modify brand test 02b') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_TRAFFIC',  array('green') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SHOPPING', array('modify brand test 02c') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_KEYWORDS', array('modify brand test 02d', 'modify brand test 02e', 'modify brand test 02f') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_STORY',    array('modify brand test 02g') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_PROFITS',  array(0.2) ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_HITCOUNT', array(2) ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SINCE',    array('2013-02-02T02:02:02') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SAVE',     array(false) ),
		);
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$admPub->ExtraMetaData = BizAdmProperty::sortCustomProperties( $admPub->ExtraMetaData );
		
		// Update the DB with the modified test Publication
		require_once BASEDIR.'/server/services/adm/AdmModifyPublicationsService.class.php';
		$request = new AdmModifyPublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Publications = array( $this->publication );
		
		$stepInfo = 'Testing on ModifyPublications web service.';
		$response = $this->utils->callService( $this,  $request, $stepInfo );
		
		// Error when the returned publication is not the same as the modified one.
		if( !is_null( $response ) ) {
			$responsePub = isset( $response->Publications[0] ) ? $response->Publications[0] : null;
			$this->validateAdmPublication( $responsePub, 'ModifyPublications' );
			$this->publication = $responsePub;
		}
	}
	
	/**
	 * Retrieves the test Publication from DB by calling GetPublications admin web service.
	 * Errors when the returned publication is not the same as the test Publication.
	 */		
	private function testGetPublication()
	{
		// When creation failed, bail out sliently since there is nothing to test here.
		if( !$this->publication ) {
			return;
		}
		
		// Retrieve the test Publication from DB.
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationIds = array( $this->publication->Id );
		
		$stepInfo = 'Testing on GetPublications web service.';
		$response = $this->utils->callService( $this,  $request, $stepInfo );
		
		// Error when the returned publication is not the same as the one we already have.
		if( !is_null( $response ) ) {
			$responsePub = isset( $response->Publications[0] ) ? $response->Publications[0] : null;
			if( $responsePub ) {
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$responsePub->ExtraMetaData = BizAdmProperty::sortCustomProperties( $responsePub->ExtraMetaData );
			}
			$this->validateAdmPublication( $responsePub, 'GetPublications' );
			$this->publication = $responsePub;
		}
	}

	/**
	 * Deletes the test Publication by calling DeletePublications admin web service.
	 * Validates is the deletion was successful by calling GetPublications service.
	 * Errors when that service returns a Publication (as shown in the BuildTest).
	 */
	private function testDeletePublication()
	{
		// When creation failed, bail out sliently since there is nothing to test here.
		if( !$this->publication ) {
			return;
		}
		
		// Remove the test Publication from DB.
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';
		$request = new AdmDeletePublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationIds = array( $this->publication->Id );
		
		$stepInfo = 'Testing on DeletePublications web service (delete operation itself).';
		/* $response = */ $this->utils->callService( $this,  $request, $stepInfo );
		
		// Try to retrieve the deleted test Publication, which should fail.
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationIds = array( $this->publication->Id );
		
		$stepInfo = 'Testing on DeletePublications web service (get operation after delete).';
		$response = $this->utils->callService( $this,  $request, $stepInfo, '(S1056)' );
		
		// Error when the deleted test Publication still exists in DB.
		if( $response && ($response->Publications[0]->Id == $this->publication->Id) ) {
			$this->setResult( 'ERROR',  'Error occured in DeletePublications service call.', 
								'Publication [id='.$this->publication->Id.'] is not deleted via DeletePublications service call.' );
		}
		LogHandler::Log( 'AdmBrands', 'INFO', 'Completed validating DeletePublications.' );
	}

	/**
	 * Validates the response returned by $operation.
	 * BuildTest shows error when the Publication is not round-tripped.
	 *
	 * @param AdmPublication|null $admPub Response returned by $operation
	 * @param string $operation The service name to be validated, possible values: 'CreatePublications', 'ModifyPublications', 'GetPublications'
	 */
	private function validateAdmPublication( $admPub, $operation )
	{
		if( is_null( $admPub )) {
			$this->setResult( 'ERROR',  'Invalid response returned.', 
								'No response found for ['.$operation.'] service request.' );
		} else {
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(
				'AdmPublication->CalculateDeadlines' => true, // Can be skipped since this is not the interest of testing at this point.
			) );
			if( !$phpCompare->compareTwoObjects( $this->publication, $admPub ) ){
				$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 'Error occured in '.$operation.' response.');		
			}
			LogHandler::Log( 'AdmBrands', 'INFO', 'Completed validating '.$operation.' response.' );
		}
	}
}
