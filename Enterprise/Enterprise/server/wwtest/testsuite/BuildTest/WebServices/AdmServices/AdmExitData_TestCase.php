<?php
/**
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmExitData_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Deletes test data through services that was setup by AdmInitData test. '; }
	public function getTestMethods() { return 'Does LogOff through admin services.'; }
    public function getPrio()        { return 999; }
	
	private $ticket = null;
	private $publicationId = null;
	private $pubChannelId = null;
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;

	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		// Get ticket as retrieved by the AdmInitData test.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_AdmServices']['ticket'];
   		$this->publicationId = @$vars['BuildTest_WebServices_AdmServices']['publicationId'];
   		$this->pubChannelId = @$vars['BuildTest_WebServices_AdmServices']['pubChannelId'];

		// Delete the test Publication and PubChannel that were created by the AdmInitData test.
		$this->deletePubChannel();
		$this->deletePublication();

		// LogOff TESTSUITE user through admin interface.
		if( $this->ticket ) {
			require_once BASEDIR.'/server/utils/TestSuite.php';
			$utils = new WW_Utils_TestSuite();
			$utils->admLogOff( $this, $this->ticket );
		}

	}

	/**
	 * Removes the Publication that was created by AdmInitData test.
	 */
	private function deletePublication()
	{
		// When creation failed, bail out sliently since there is nothing to test here.
		if( !$this->ticket || !$this->publicationId ) {
			return;
		}
		
		// Remove the test Publication from DB.
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';
		$request = new AdmDeletePublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationIds = array( $this->publicationId );
		
		$stepInfo = 'Delete Publication that was created by AdmInitData test.';
		/* $response = */ $this->utils->callService( $this,  $request, $stepInfo );
	}

	/**
	 * Removes the PubChannel that was created by AdmInitData test.
	 */
	private function deletePubChannel()
	{
		// When creation failed, bail out sliently since there is nothing to test here.
		if( !$this->ticket || !$this->pubChannelId ) {
			return;
		}
		
		// Remove the test PubChannel from DB.
		require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';
		$request = new AdmDeletePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->PubChannelIds = array( $this->pubChannelId );
	}
}