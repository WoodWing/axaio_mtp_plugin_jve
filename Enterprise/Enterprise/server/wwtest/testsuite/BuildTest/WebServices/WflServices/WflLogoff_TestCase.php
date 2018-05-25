<?php
/**
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflLogoff_TestCase extends TestCase
{
	/** @var WW_TestSuite_BuildTest_WebServices_WflServices_Utils $wflServicesUtils */
	private $wflServicesUtils = null;

	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Tries logoff the user from Enterprise. '; }
	public function getTestMethods() { return 'Calls the LogOff workflow service at application server. '; }
    public function getPrio()        { return 500; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();

		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();

		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();
		$ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$ticket ){ // when there's no ticket, for sure you can't log off, just bail out.
			$this->setResult( 'ERROR', 'There is no ticket for logging off.',
								'Please enable the "Setup test data" entry and try again.' );
			return;
		}

		/** @var PublicationInfo $publication */
		$publication = @$vars['BuildTest_WebServices_WflServices']['publication'];
		if( $publication ) {
			if( $this->wflServicesUtils->initTest( $this, 'BT', null, false ) ) {
				/** @var AdmIssue $webIssue */
				$webIssue = @$vars['BuildTest_WebServices_WflServices']['webIssue'];
				if( $webIssue ) {
					$this->deleteAdmIssue( $publication->Id, $webIssue->Id );
				}
				/** @var AdmPubChannel $webPubChannel */
				$webPubChannel = @$vars['BuildTest_WebServices_WflServices']['webPubChannel'];
				if( $webPubChannel ) {
					$this->deleteAdmPubChannel( $publication->Id, $webPubChannel->Id );
				}
			}
		}

		// Deactivate the MultiChannelPublishingSample plugin when we did activate before.
		$activatedMcpPlugin = @$vars['BuildTest_WebServices_WflServices']['activatedMcpPlugin'];
		if( $activatedMcpPlugin ) {
			$utils->deactivatePluginByName( $this, 'MultiChannelPublishingSample' );
		}

		$utils->wflLogOff( $this, $ticket );
	}

	/**
	 * Deletes the publication channel that was used for testing.
	 *
	 * @param int $publicationId
	 * @param int $pubChannelId
	 */
	private function deleteAdmPubChannel( $publicationId, $pubChannelId )
	{
		try {
			$stepInfo = 'Deleting channel for WflServices tests.';
			$this->wflServicesUtils->deletePubChannel( $stepInfo, $publicationId, $pubChannelId );
		} catch( BizException $e ) {
		}
	}

	/**
	 * Deletes the publication channel that was used for testing.
	 *
	 * @param int $publicationId
	 * @param int $issueId
	 */
	private function deleteAdmIssue( $publicationId, $issueId )
	{
		try {
			$stepInfo = 'Deleting channel for WflServices tests.';
			$this->wflServicesUtils->deleteIssue( $stepInfo, $publicationId, $issueId );
		} catch( BizException $e ) {
		}
	}
}