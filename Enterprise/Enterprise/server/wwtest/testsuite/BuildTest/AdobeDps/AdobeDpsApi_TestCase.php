<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps_AdobeDpsApi_TestCase extends TestCase
{
	private $issueId = null;

	public function getDisplayName() { return 'Adobe DPS API'; }
	public function getTestGoals()   { return 'Check if the Adobe Digital Publishing Suite API works'; }
	public function getTestMethods() { return 'Calls services defined for the Adobe DPS, as used by Enterprise Server integration.'; }
    public function getPrio()        { return 5102; }

    final public function runTest()
	{
		// web admin: https://digitalpublishing.acrobat.com/app.html
		// InDesign: Folio Builder (and Overlay Creator) panels
		
		require_once BASEDIR.'/config/config_dps.php';
		$dpsConfigs = unserialize( ADOBEDPS_ACCOUNTS );
		if( $dpsConfigs ) foreach( $dpsConfigs as $channelId => $dpsAccs ) {
			foreach( $dpsAccs as $editionId => $dpsAccSettings ) {
				if( $channelId == 0 && $editionId == 0 ) { // Get the config for test Channel = All and Edition = All
					$dpsConfig = $dpsAccSettings;
				}
			}
		}

		$dpsConfig = isset( $dpsConfigs[0][0] ) ? $dpsConfigs[0][0] : null; // Get the config for test Channel = All and Edition = All
		if( is_null( $dpsConfig )) {
			$this->setResult( 'ERROR', 'No DPS account is set for all channel(id=0) and all edition(id=0). Please check your config_dps.php file' );
			return;
		}

		if( !isset($dpsConfig['serverurl']) || !isset($dpsConfig['username']) || !isset($dpsConfig['password']) ) {
			$this->setResult( 'ERROR', 'DPS account is set but some keys are missing. '.
									   'There should be \'serverurl\', \'username\' and \'password\'. Please check your config_dps.php file.' );
			return;
		}

		require_once BASEDIR.'/server/utils/DigitalPublishingSuiteClient.class.php';
		$service = new WW_Utils_DigitalPublishingSuiteClient( $dpsConfig['serverurl'], $dpsConfig['username'] );
		$service->signIn( $dpsConfig['username'], $dpsConfig['password'] );

		$this->testIssue( $service, null, 'LatestIssue'/*DPS filter*/ ); // Create new Issue through API
		$this->testIssue( $service, $this->issueId, 'UpdatedIssue'/*DPS filter*/ ); // Use existing Folio that created earlier
	}

	/**
	 * 
	 * @param WW_Utils_DigitalPublishingSuiteClient $service
	 * @param string $issueId DPS issue id. Pass Null to create a new DPS issue.
	 * @param string $dpsFilter Keyword to filter on titles to show only selected issues in the DPS viewer's store.
	 */
	private function testIssue( $service, $issueId, $dpsFilter )
	{
		if( !$issueId ) {
			try {
				$issueId = $service->createIssue( array('noChargeStore'), null, $dpsFilter );
			} catch ( BizException $e ) {
				$this->setResult( 'ERROR', $e->getMessage() );
			}
		}

		$articleIds = array();
		try {
			$articlePath = dirname(__FILE__).'/testdata/Enjoy_2011_9_14_12_43_14.folio';
			$articleId = $service->uploadArticle( $issueId, $articlePath, 1 );
			$articleIds[] = $articleId;
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage() );
		}
		try {
			$coverPath = dirname(__FILE__).'/testdata/Cover.jpg';
			$service->uploadIssueLibraryPreview( $issueId, $coverPath, 'image/jpeg', false );
			
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage() );
		}
		try {
			// Since this test only focus on the API, the BuildTest will not focus on whether
			// the dossier has C_DPS_SECTION defined with value. ( The normal case should be that
			// the section cover will only be uploaded when the dossier has C_DPS_SECTION defined. )
			$coverPath = dirname(__FILE__).'/testdata/Cover.jpg';
			// $articleId is from the uploadArticle above.
			$service->uploadSectionCover( $issueId, $coverPath, 'image/jpeg', $articleId, false );
			
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage() );
		}	
		try {
			$articlePath = dirname(__FILE__).'/testdata/Wifi_2011_9_14_11_2_49.folio';
			$articleIds[] = $service->uploadArticle( $issueId, $articlePath, 2 );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage() );
		}
		
		try {
			$manifestPath = dirname(__FILE__).'/testdata/Folio.xml';
			$service->uploadIssueManifest( $issueId, $manifestPath );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage() );
		}

		try {
			$service->updateIssue( $issueId, null, 'test', null, $dpsFilter );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage() );
		}

		try {
			$service->downloadIssueCatalog( $issueId );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage() );
		}

		// The below getIssues call is commented out.
		// Now and then, the BuildTest fails due to the connection time out with DPS server,
		// which is something we want to avoid, the BuildTest should fail only when there's
		// really an error/problem in the server.
		// Furthermore, the results of the getIssues call is not validated here
		// and it is currently only used by the issue admin page, so will be commented out for now.
		//try {
		//	$service->getIssues( 
		//		true, 	// include all
		//		null, 	// title
		//		true, 	// includeDisabled
		//		true, 	// includeTest
		//		'all' );// targetDimension
		//} catch( BizException $e ) {
		//	$this->setResult( 'ERROR', $e->getMessage() );
		//}

		if( $this->issueId ) {
			try {
				$service->deleteIssue( $issueId );
			} catch( BizException $e ) {
				$this->setResult( 'ERROR', $e->getMessage() );
			}
		}

		if( $issueId ) {
			$this->issueId = $issueId;
		}
	}
}
