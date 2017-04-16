<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmPubChannels_TestCase extends TestCase
{
	public function getDisplayName() { return 'Publication Channel properties'; }
	public function getTestGoals()   { return 'Checks if Publication Channel properties can be round-tripped and deleted successfully. '; }
	public function getTestMethods() { return 'Call admin PubChannel services (create, modify, get and delete) using property values.'; }
    public function getPrio()        { return 120; }

	private $pubChannel = null; // AdmPubChannel
	private $ticket = null; // string, session ticket for admin user
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;

	const SUGGESTION_PROVIDER = 'OpenCalais';
	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		// Retrieve the Ticket that has been determined by AdmInitData TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_AdmServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the AdmInitData test.' );
			return;
		}
   		$this->publicationId = @$vars['BuildTest_WebServices_AdmServices']['publicationId'];
		if( !$this->publicationId ) {
			$this->setResult( 'ERROR',  'Could not find publicationId to test with.', 'Please enable the AdmInitData test.' );
			return;
		}

		// Perform the test with CustomAdminPropsDemo plug-in activated.
		$didActivate = $this->utils->activatePluginByName( $this, 'CustomAdminPropsDemo' );
		if( is_null($didActivate) ) { // error?
			return;
		}

		// Run tests.
		$this->testCreatePubChannel();
		$this->testModifyPubChannel();
		$this->testGetPubChannel();
		$this->testDeletePubChannel();

		// Restore plugin activation.
		if( !$didActivate ) { // if we did not activate, it was activated before, so we restore by activation.
			$this->utils->activatePluginByName( $this, 'CustomAdminPropsDemo' );
		}
	}
	
	/**
	 * Creates a test PubChannel in the DB by calling CreatePubChannels admin web service.
	 * Errors when the returned pubChannel is not the same as the created one.
	 */
	private function testCreatePubChannel()
	{
		// Create a new test PubChannel data object in memory.
		$pubChan = new AdmPubChannel();
		$pubChan->Name              = 'PubChannel_C_' . date('dmy_his');
		$pubChan->Description       = 'Created PubChannel'; 
		$pubChan->Type              = 'other';
		$pubChan->SortOrder         = 10;
		$pubChan->PublishSystem     = 'Enterprise';
		$pubChan->CurrentIssueId    = 0;
		$pubChan->SuggestionProvider = self::SUGGESTION_PROVIDER;
		$pubChan->ExtraMetaData     = array( // since 9.0
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_USERNAME', array('create channel test 01a') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_PASSWORD', array('create channel test 01b') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_TRAFFIC',  array('red') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SHOPPING', array('create channel test 01c') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_KEYWORDS', array('create channel test 01d', 'create channel test 01e', 'create channel test 01f') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_STORY',    array('create channel test 01g') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_PROFITS',  array(0.1) ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_HITCOUNT', array(1) ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SINCE',    array('2013-01-01T01:01:01') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SAVE',     array(true) ),
		);
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$pubChan->ExtraMetaData = BizAdmProperty::sortCustomProperties( $pubChan->ExtraMetaData );
		$this->pubChannel = $pubChan;
		
		// Create the test PubChannel in the DB.
		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';
		$request = new AdmCreatePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannels = array( $this->pubChannel );
		
		$stepInfo = 'Testing on CreatePubChannels web service.';
		$response = $this->utils->callService( $this,  $request, $stepInfo );
		
		// Error when the returned pubChannel is not the same as the created one.
		if( !is_null( $response ) ) {
			$responseChan = isset( $response->PubChannels[0] ) ? $response->PubChannels[0] : null;
			// Copy the read-only props to avoid validation errors.
			if( $responseChan ) {
				$this->pubChannel->Id            = $responseChan->Id;
				$this->pubChannel->DirectPublish = $responseChan->DirectPublish;
				$this->pubChannel->SupportsForms = $responseChan->SupportsForms;
				$this->pubChannel->SupportsCropping = $responseChan->SupportsCropping;
			}
			$this->validateAdmPubChannel( $responseChan, 'CreatePubChannels' );
			$this->pubChannel = $responseChan;
		}
	}	

	/**
	 * Updates the DB with the modified test PubChannel by calling ModifyPubChannels admin web service.
	 * Errors when the returned pubChannel is not the same as the modified one.
	 */	
	private function testModifyPubChannel()
	{
		// When creation failed, bail out sliently since there is nothing to test here.
		if( !$this->pubChannel ) {
			return;
		}
		
		// Modifiy the test PubChannel by changing some of its properties.
		$pubChan = &$this->pubChannel;
		$pubChan->Name              = 'PubChannel_M_' . date('dmy_his');
		$pubChan->Description       = 'Modified PubChannel';
		$pubChan->Type              = 'web';
		$pubChan->PublishSystem     = 'Drupal7';
		$pubChan->CurrentIssueId    = 0;
		$pubChan->SuggestionProvider    = ''; // Clear the SuggestionsProvider.
		$pubChan->ExtraMetaData     = array( // since 9.0
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_USERNAME', array('modify channel test 02a') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_PASSWORD', array('modify channel test 02b') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_TRAFFIC',  array('green') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SHOPPING', array('modify channel test 02c') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_KEYWORDS', array('modify channel test 02d', 'modify channel test 02e', 'modify channel test 02f') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_STORY',    array('modify channel test 02g') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_PROFITS',  array(0.2) ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_HITCOUNT', array(2) ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SINCE',    array('2013-02-02T02:02:02') ),
			new AdmExtraMetaData( 'C_CUSTADMPROPDEMO_SAVE',     array(false) ),
		);
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$pubChan->ExtraMetaData = BizAdmProperty::sortCustomProperties( $pubChan->ExtraMetaData );
		
		// Update the DB with the modified test PubChannel
		require_once BASEDIR.'/server/services/adm/AdmModifyPubChannelsService.class.php';
		$request = new AdmModifyPubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannels = array( $this->pubChannel );
		
		$stepInfo = 'Testing on ModifyPubChannels web service.';
		$response = $this->utils->callService( $this,  $request, $stepInfo );
		
		// Error when the returned pubChannel is not the same as the modified one.
		if( !is_null( $response ) ) {
			$responseChan = isset( $response->PubChannels[0] ) ? $response->PubChannels[0] : null;
			// Copy the read-only props to avoid validation errors.
			if( $responseChan ) {
				$this->pubChannel->DirectPublish = $responseChan->DirectPublish;
				$this->pubChannel->SupportsForms = $responseChan->SupportsForms;
				$this->pubChannel->SupportsCropping = $responseChan->SupportsCropping;
			}
			$this->validateAdmPubChannel( $responseChan, 'ModifyPubChannels' );
			$this->pubChannel = $responseChan;
		}
	}
	
	/**
	 * Retrieves the test PubChannel from DB by calling GetPubChannels admin web service.
	 * Errors when the returned pubChannel is not the same as the test PubChannel.
	 */		
	private function testGetPubChannel()
	{
		// When creation failed, bail out sliently since there is nothing to test here.
		if( !$this->pubChannel ) {
			return;
		}
		
		// Retrieve the test PubChannel from DB.
		require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';
		$request = new AdmGetPubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannelIds = array( $this->pubChannel->Id );
		
		$stepInfo = 'Testing on GetPubChannels web service.';
		$response = $this->utils->callService( $this,  $request, $stepInfo );
		
		// Error when the returned pubChannel is not the same as the one we already have.
		if( !is_null( $response ) ) {
			$responseChan = isset( $response->PubChannels[0] ) ? $response->PubChannels[0] : null;
			if( $responseChan ) {
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$responseChan->ExtraMetaData = BizAdmProperty::sortCustomProperties( $responseChan->ExtraMetaData );
			}
			$this->validateAdmPubChannel( $responseChan, 'GetPubChannels' );
			$this->pubChannel = $responseChan;
		}
	}

	/**
	 * Deletes the test PubChannel by calling DeletePubChannels admin web service.
	 * Validates is the deletion was successful by calling GetPubChannels service.
	 * Errors when that service returns a PubChannel (as shown in the BuildTest).
	 */
	private function testDeletePubChannel()
	{
		// When creation failed, bail out sliently since there is nothing to test here.
		if( !$this->pubChannel ) {
			return;
		}
		
		// Remove the test PubChannel from DB.
		require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';
		$request = new AdmDeletePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->PubChannelIds = array( $this->pubChannel->Id );
		
		$stepInfo = 'Testing on DeletePubChannels web service (delete operation itself).';
		/* $response = */ $this->utils->callService( $this,  $request, $stepInfo );
		
		// Try to retrieve the deleted test PubChannel, which should fail.
		require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';
		$request = new AdmGetPubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannelIds = array( $this->pubChannel->Id );
		
		$stepInfo = 'Testing on DeletePubChannels web service (get operation after delete).';
		$response = $this->utils->callService( $this,  $request, $stepInfo, '(S1056)' );
		
		// Error when the deleted test PubChannel still exists in DB.
		if( $response && ($response->PubChannels[0]->Id == $this->pubChannel->Id) ) {
			$this->setResult( 'ERROR',  'Error occured in DeletePubChannels service call.', 
								'PubChannel [id='.$this->pubChannel->Id.'] is not deleted via DeletePubChannels service call.' );
		}
		LogHandler::Log( 'AdmPubChannels', 'INFO', 'Completed validating DeletePubChannels.' );
	}

	/**
	 * Validates the response returned by $operation.
	 * BuildTest shows error when the PubChannel is not round-tripped.
	 *
	 * @param AdmPubChannel|null $pubChan Response returned by $operation
	 * @param string $operation The service name to be validated, possible values: 'CreatePubChannels', 'ModifyPubChannels', 'GetPubChannels'
	 */
	private function validateAdmPubChannel( $pubChan, $operation )
	{
		if( is_null( $pubChan )) {
			$this->setResult( 'ERROR',  'Invalid response returned.', 
								'No response found for ['.$operation.'] service request.' );
		} else {
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(
				'AdmPubChannel->PublishSystemId' => true,
			) );
			if( !$phpCompare->compareTwoObjects( $this->pubChannel, $pubChan ) ){
				$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 'Error occurred in '.$operation.' response.');
			}
			LogHandler::Log( 'AdmPubChannels', 'INFO', 'Completed validating '.$operation.' response.' );
		}
	}
}
