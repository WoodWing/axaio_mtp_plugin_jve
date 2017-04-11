<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmInitData_TestCase extends TestCase
{
	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Creates test data through services in preparation for following test cases. '; }
	public function getTestMethods() { return 'Does LogOn through admin services.'; }
    public function getPrio()        { return 1; }

	private $utils = null; // WW_Utils_TestSuite
	private $publicationId = null;
	private $pubChannelId = null;
	
	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Logon TESTSUITE user through admin interface.
		$response = $this->utils->admLogOn( $this );
		$this->ticket = $response ? $response->Ticket : null;

		// Create a publication to let successor test cases work on it.
		$publication = $this->createPublication();
		$this->createPubChannel();
		$this->setDefaultPubChannel( $publication );

		// Save the retrieved data into session for successor TestCase modules within this TestSuite.
		$vars = array();
		$vars['BuildTest_WebServices_AdmServices']['ticket'] = $this->ticket;
		$vars['BuildTest_WebServices_AdmServices']['publicationId'] = $this->publicationId;
		$vars['BuildTest_WebServices_AdmServices']['pubChannelId'] = $this->pubChannelId;
		$this->setSessionVariables( $vars );
	}

	/**
	 * Creates a Publication to let successor test cases work on it.
	 */
	private function createPublication()
	{
		// Without ticket we can not do anything.
		if( !$this->ticket ) {
			return null;
		}
		
		// Create a new test Publication data object in memory.
		$admPub = new AdmPublication();
		$admPub->Name              = 'Brand_T_' . date('dmy_his');
		$admPub->Description       = 'Created Brand'; 
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
		
		$stepInfo = 'AdmInitData is creating a Publication to let successor test cases work on it.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$this->publicationId = $response ? $response->Publications[0]->Id : null;
		return $response->Publications[0];
	}
	
	/**
	 * Creates a PubChannel under the test Publication to let successor test cases work on it.
	 */
	private function createPubChannel()
	{
		// Without ticket or parental brand we can not do anything.
		if( !$this->ticket || !$this->publicationId ) {
			return;
		}
		
		// Create a new test PubChannel data object in memory.
		$pubChan = new AdmPubChannel();
		$pubChan->Name              = 'PubChannel_T_' . date('dmy_his');
		$pubChan->Description       = 'Created PubChannel'; 
		$pubChan->Type              = 'print';
		$pubChan->PublishSystem     = 'Enterprise';
		$pubChan->CurrentIssueId    = 0;
		$pubChan->ExtraMetaData     = array(); // since 9.0
		
		// Create the test PubChannel in the DB.
		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';
		$request = new AdmCreatePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannels = array( $pubChan );
		
		$stepInfo = 'AdmInitData is creating a PubChannel to let successor test cases work on it.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$this->pubChannelId = $response ? $response->PubChannels[0]->Id : null;
	}

	/**
	 * Sets a default publication channel for the Test publication.
	 *
	 * @param AdmPublication $publication
	 */
	private function setDefaultPubChannel( AdmPublication $publication )
	{
		$publication->DefaultChannelId = $this->pubChannelId;

		require_once BASEDIR.'/server/services/adm/AdmModifyPublicationsService.class.php';
		$request = new AdmModifyPublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Publications = array( $publication );
		$stepInfo = 'AdmInitData sets the created pubChannel as default channel for the publication.';
		$this->utils->callService( $this, $request, $stepInfo );
	}
}