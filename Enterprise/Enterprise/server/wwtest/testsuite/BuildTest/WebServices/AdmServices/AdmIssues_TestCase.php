<?php
/**
 * @since v7.4.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmIssues_TestCase extends TestCase
{
	public function getDisplayName() { return 'Issues properties'; }
	public function getTestGoals()   { return 'Checks if issue properties can be round-tripped. '; }
	public function getTestMethods() { return 'Call admin issue services with initial property values and modified property values.'; }
	public function getPrio()        { return 130; }
	public function isSelfCleaning() { return true; }

	/** @var integer $publicationId  */
	private $publicationId = null;
	/** @var integer $pubChannelId  */
	private $pubChannelId = null;
	/** @var string $ticket  */
	private $ticket = null;
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;

	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		// Retrieve the test data that has been determined by AdmInitData TestCase.
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
   		$this->pubChannelId = @$vars['BuildTest_WebServices_AdmServices']['pubChannelId'];
		if( !$this->pubChannelId ) {
			$this->setResult( 'ERROR',  'Could not find pubChannelId to test with.', 'Please enable the AdmInitData test.' );
			return;
		}

		// Call CreateIssues service
		$sentIssue1 = new AdmIssue();
		$this->setIssueProperties( $sentIssue1, 'create1' );
		$resp = $this->createIssue( $sentIssue1 );
		$recvIssue1 = $resp->Issues[0];
		$issueId = $recvIssue1->Id;

		// Validate CreateIssues service
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(			
			'AdmIssue->Id' => true, // Issue Id does not exists before creation, so allowed to be excluded.
			'AdmIssue->EmailNotify' => true, // Isn't used anymore??? It now returns null.
			'AdmIssue->ExtraMetaData' => true, // These are returned in a different order and also not sent in the request.
			'AdmIssue->SectionMapping' => true, // Section mapping is returned, no need to check
			'AdmIssue->CalculateDeadlines' => true, // Can be skipped since this is not the interest of testing at this point.
		));
		if( !$phpCompare->compareTwoObjects( $sentIssue1, $recvIssue1 ) ) {
			$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 'Error occurred in CreateIssues response.');
			$this->deleteIssues( array( $recvIssue1->Id ) );
			return;
		}
		
		// Call GetIssues service
		$sentIssue2 = unserialize( serialize( $recvIssue1 ) ); // deep clone. Do not use: clone $issue;
		$resp = $this->getIssue( $issueId );
		$recvIssue2 = $resp->Issues[0];
		
		// Validate GetIssues service
		$phpCompare->initCompare( array() ); // all issue properties should be checked
		if( !$phpCompare->compareTwoObjects( $sentIssue2, $recvIssue2 ) ) {
			$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 'Error occured in GetIssues response.');
			$this->deleteIssues( array( $sentIssue2->Id ) );
			return;
		}

		// Call ModifyIssues service
		$sentIssue3 = unserialize( serialize( $recvIssue2 ) ); // deep clone. Do not use: clone $issue;
		$this->setIssueProperties( $sentIssue3, 'modify3' );
		$resp = $this->modifyIssue( $sentIssue3 );
		$recvIssue3 = $resp->Issues[0];

		// Validate ModifyIssues service
		$phpCompare->initCompare( array(
							'AdmIssue->ExtraMetaData' => true, // In the request, ExtraMetaData is not sent in, but it is returned in Resp.
		));		
		if( !$phpCompare->compareTwoObjects( $sentIssue3, $recvIssue3 ) ) {
			$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 'Error occured in ModifyIssues1 response.');
			$this->deleteIssues( array( $sentIssue3->Id ) );
			return;
		}
		
		// Call ModifyIssues service again, now testing custom admin props
		$sentIssue4 = unserialize( serialize( $recvIssue3 ) ); // deep clone. Do not use: clone $issue;
		$this->setIssueProperties( $sentIssue4, 'modify4' );
		$resp = $this->modifyIssue( $sentIssue4 );
		$recvIssue4 = $resp->Issues[0];

		// Validate ModifyIssues service again
		$phpCompare->initCompare( array() ); // all issue properties should be checked
		if( !$phpCompare->compareTwoObjects( $sentIssue4, $recvIssue4 ) ) {
			$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 'Error occured in ModifyIssues2 response.');
			$this->deleteIssues( array( $sentIssue4->Id ) );
			return;
		}
		
		// Call CopyIssues service
		$sentIssue5 = unserialize( serialize( $recvIssue4 ) ); // deep clone, instead of re-using the top sentIssue: Easy for code maintenance
		$this->setIssueProperties( $sentIssue5, 'copy5' );
		$resp = $this->copyIssue( $sentIssue5 );
		$recvIssue5 = $resp->Issues[0];
		
		// Validate CopyIssues service
		$phpCompare->initCompare( array( 'AdmIssue->Id' => true ) ); // New copied issue will has its own issue id, so exclude here.
		if(!$phpCompare->compareTwoObjects( $sentIssue5, $recvIssue5 ) ) {
			$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 'Error occured in CopyIssues response.');
			$this->deleteIssues( array( $sentIssue5->Id, $recvIssue5->Id ) );
			return;
		}
		
		// Call DeleteIssues service
		/* $resp = */$this->deleteIssues( array( $sentIssue5->Id, $recvIssue5->Id ) );
		
		// Validate DeleteIssues service
		$this->validateDeleteIssuesService( array( $sentIssue5, $recvIssue5 ) );
	}
	
	/** 
	 * Modifies a given AdmIssue properties for testing.
	 * @param AdmIssue $issue The issue to be created/modified on.
	 * @param boolean $mode Testing mode. Must be one of the following values:
	 *    'createN' to initialize all properties
	 *	  'modifyN' to change properties
	 *     where N > 0.
	 * @return AdmIssue
	 */
	private function setIssueProperties( AdmIssue $issue, $mode )
	{
		switch( $mode ) {
			case 'create1':
				$issue->Name                 = 'Issue_C_' . date('dmy_his');
				$issue->Description          = 'Created Issue';
				$issue->SortOrder            = 2;
				$issue->EmailNotify          = false;
				$issue->ReversedRead         = true;
				$issue->OverrulePublication  = false;
				$issue->Deadline             = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+1, date('Y')));
				$issue->PublicationDate      = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
				$issue->ExpectedPages        = 32;
				$issue->Subject              = 'Build Test for the CreateIssues service';
				$issue->Activated            = false;
				$issue->ExtraMetaData        = null;
			break;
			case 'modify3':
				$issue->Name                 = 'Issue_M_' . date('dmy_his');
				$issue->Description          = 'Modified Issue';
				$issue->SortOrder            = 3;
				//$issue->EmailNotify          = true; // not supported
				$issue->ReversedRead         = false;
				//$issue->OverrulePublication  = true; // skipped; too complicated
				$issue->Deadline             = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+1, date('Y')));
				$issue->PublicationDate      = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
				$issue->ExpectedPages        = 36;
				$issue->Subject              = 'Build Test for the ModifyIssues service';
				$issue->Activated            = true;
				$issue->ExtraMetaData        = null;
			break;
			case 'modify4':
				foreach( $issue->ExtraMetaData as $modExtraMD ){
					if( $modExtraMD->Property == 'C_BUILDTEST_PROPERTY' ){
						$modExtraMD->Values = array( '123' );
						break;
					}
				}

				break;
			case 'copy5':
				$issue->Name = 'Issue_C_' . date('dmy_his');
			break;
		}
		return $issue;
	}
	
	/**
	 * Creates an issue at the DB through the CreateIssues admin web service.
	 *
	 * @param AdmIssue $issue
	 * @return AdmCreateIssuesResponse
	 */
	private function createIssue( AdmIssue $issue )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$request = new AdmCreateIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannelId = $this->pubChannelId;
		$issue = unserialize( serialize( $issue ) );
		$request->Issues = array( $issue );
		$response = $this->utils->callService( $this, $request, 'Create Issues' );
		return $response;
	}

	/**
	 * Retrieves an issue from the DB through the GetIssues admin web service.
	 *
	 * @param integer $issueId
	 * @return AdmGetIssuesResponse
	 */	
	private function getIssue( $issueId )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';
		$request = new AdmGetIssuesRequest();
		$request->Ticket            = $this->ticket;
		$request->RequestModes      = array( 'GetSections', 'GetEditions');
		$request->PublicationId     = $this->publicationId;
		$request->PubChannelId      = $this->pubChannelId;
		$request->IssueIds          = array( $issueId );
		$response = $this->utils->callService( $this, $request, 'Get Issues' );
		return $response;
	}

	/**
	 * Updates an issue at the DB through the ModifyIssues admin web service.
	 *
	 * @param AdmIssue $issue
	 * @return AdmModifyIssuesResponse
	 */	
	private function modifyIssue( AdmIssue $issue )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyIssuesService.class.php';
		$request = new AdmModifyIssuesRequest();
		$request->Ticket                = $this->ticket;
		$request->RequestModes          = array();
		$request->PublicationId 		= $this->publicationId;
		$request->PubChannelId          = $this->pubChannelId;
		$issue = unserialize( serialize( $issue ));		
		$request->Issues 				= array( $issue );
		$response = $this->utils->callService( $this, $request, 'Modify Issues' );
		return $response;
	}
	
	/**
	 * Copy $issue to another issue through the CopyIssues admin web service.
	 *
	 * @param AdmIssue $issue
	 * @return AdmCopyIssuesResponse
	 */	 
	private function copyIssue( AdmIssue $issue )
	{
		require_once BASEDIR.'/server/services/adm/AdmCopyIssuesService.class.php';
		$request = new AdmCopyIssuesRequest();
		$request->Ticket                = $this->ticket;
		$request->RequestModes          = array();
		$request->IssueId               = $issue->Id;
		$issue = unserialize( serialize( $issue ) );
		$request->Issues                = array( $issue );
		$response = $this->utils->callService( $this, $request, 'Modify Issues' );
		return $response;
		
	}

	/**
	 * Delete issues through the DeleteIssues admin web service.
	 *
	 * @param array $issuesToBeDeleted Array of issues id to be deleted
	 */
	private function deleteIssues( $issuesToBeDeleted )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
		$request = new AdmDeleteIssuesRequest();
		$request->Ticket                = $this->ticket;
		$request->PublicationId         = $this->publicationId;
		$request->IssueIds              = $issuesToBeDeleted;
		/* $response = */ $this->utils->callService( $this, $request, 'Delete Issues' );
	}
	
	/**
	 * Validates DeleteIssues admin web service. The validation is done by trying to retrieve
	 * the deleted issues. Unexpected results are shown at the BuildTest page.
	 *
	 * @param array $deletedIssues Array of AdmIssue issues that were deleted and to be validated.
	 */
	private function validateDeleteIssuesService( $deletedIssues )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';
		if( $deletedIssues ) foreach( $deletedIssues as $deletedIssue ) {
			$request = new AdmGetIssuesRequest();
			$request->Ticket            = $this->ticket;
			$request->RequestModes      = array( 'GetSections', 'GetEditions');
			$request->PublicationId     = $this->publicationId;
			$request->PubChannelId      = $this->pubChannelId;
			$request->IssueIds          = array( $deletedIssue->Id );
			$stepInfo = 'Try to Get Issues after Delete Issues.';
			$this->utils->callService( $this, $request, $stepInfo, '(S1056)' );
		}
		LogHandler::Log( 'AdmIssues', 'INFO', 'Completed validating DeleteIssues service.' );
	}
}
