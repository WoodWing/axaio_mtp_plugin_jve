<?php
/**
 * TestCase class that checks dropped integrations and belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package SCEnterprise
 * @subpackage TestSuite
 * @since v10.2.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_DroppedIntegrations_TestCase extends TestCase
{
	public function getDisplayName() { return 'Check dropped integrations'; }
	public function getTestGoals()   { return 'Looks for the existence of resources belonging to integrations which are not supported anymore.'; }
	public function getPrio()        { return 35; }

	public function getTestMethods()
	{
		return 'Scenario:'.
			'<ol>'.
				'<li></li>'.
			'</ol>'; }

	final public function runTest()
	{
		$this->verifyAdobeDps();

		/**
		 * Let Health Check show help page to clean up:
		 * Custom object property definitions in DB (C_DPS_IS_FREE, C_READER_LABEL, C_DOSSIER_IS_AD, C_OVERLAYS_IN_BROWSE, C_DOSSIER_INTENT, C_HIDE_FROM_TOC, C_DPS_SECTION, C_DOSSIER_NAVIGATION)
		 * Custom issue property definitions in DB (C_DPS_PRODUCTID, C_DPS_PAGE_ORIENTATION, C_DPS_NAVIGATION, C_DPS_READINGDIRECTION, C_DPS_PUBLICATION_TITLE, C_DPS_VOLUMENUMBER, C_DPS_FILTER, C_DPS_TARGET_VIEWER_VERSION, C_DPS_COVER_DATE)
		 */
	}

	/**
	 * Adobe DPS support is dropped since 10.2.0. This test looks for any leftover resources from that integration.
	 *
	 * Test for the existence of the following things:
	 * - Objects with Type == Other and Format == application/vnd.adobe.folio+zip
	 * - EXTENSIONMAP setting having entry '.folio' => 'application/vnd.adobe.folio+zip'
	 * - Export folder in filesystem: define( 'ADOBEDPS_EXPORTDIR', EXPORTDIRECTORY.'AdobeDps/' );
	 * - Persistent folder in filesystem: define( 'ADOBEDPS_PERSISTENTDIR', PERSISTENTDIRECTORY.'/AdobeDps' );
	 * - The config file: config/config_dps.php
	 * - Adobe DPS server jobs (JobType == 'AdobeDps')
	 * - Adobe DPS pub channels (type == dps)
	 * - DPS_ prefixed resources found in Enterprise/config/configlang.php
	 */
	private function verifyAdobeDps()
	{
		$message = 'The Adobe DPS plug-in is not supported anymore.';

		// Check objects with Type == Other and Format == application/vnd.adobe.folio+zip
		$queryParams = array();
		$queryParams[] = new QueryParam( 'Type', '=', 'Other' );
		$queryParams[] = new QueryParam( 'Format', '=', 'application/vnd.adobe.folio+zip' );
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
		$request = new WflQueryObjectsRequest();
		$request->Areas = array( 'Workflow', 'Trash' );
		$request->Params = $queryParams;

		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		$response = BizQuery::queryObjects2( $request, '', 0 );
		if( $response->TotalEntries ) {
			$message = 'Found existing folio objects';
			$help = 'Please completely remove all objects with Format "application/vnd.adobe.folio+zip"';
			//TODO: Change this severity to a proper INFO message
			$this->setResult( 'ERROR', $message, $help );
		}

		// EXTENSIONMAP setting having entry '.folio' => 'application/vnd.adobe.folio+zip'
		$map = unserialize( EXTENSIONMAP );
		if( array_key_exists( '.folio', $map ) ) {
			$message = 'The .folio extension still exists in the Enterprise Server configuration';
			$help = 'Please use the latest configserver.php';
			$this->setResult( 'ERROR', $message, $help );
		}

		// Export folder in filesystem: define( 'ADOBEDPS_EXPORTDIR', EXPORTDIRECTORY.'AdobeDps/' );
		if( file_exists( EXPORTDIRECTORY.'AdobeDps/' ) ) {
			$message = 'The Adobe DPS export directory still exists.';
			$help = 'Please remove the Adobe DPS export directory found at "'.EXPORTDIRECTORY.'AdobeDps/"';
			$this->setResult( 'ERROR', $message, $help );
		}

		// Persistent folder in filesystem: define( 'ADOBEDPS_PERSISTENTDIR', PERSISTENTDIRECTORY.'/AdobeDps' );
		if( file_exists( PERSISTENTDIRECTORY.'/AdobeDps' ) ) {
			$message = 'The Adobe DPS persistent directory still exists.';
			$help = 'Please remove the Adobe DPS export directory found at "'.PERSISTENTDIRECTORY.'/AdobeDps"';
			$this->setResult( 'ERROR', $message, $help );
		}

		// The config file: config/config_dps.php
		if( file_exists( BASEDIR.'/config/config_dps.php' ) ) {
			$message = 'Found an Adobe DPS config file that is not supported anymore.';
			$help = 'Please remove the following file: "'.BASEDIR.'/config/config_dps.php"';
			$this->setResult( 'ERROR', $message, $help );
		}

		// Adobe DPS server jobs (JobType == 'AdobeDps')
		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
		$biz = new BizServerJob();
		$params = array(
			'jobtype' => 'AdobeDps'
		);
		$result = $biz->listJobs( $params, null, null, null, null, 1 );

		//TODO: Filter on non-completed jobs
		if( !empty( $result ) ) {
			$message = '';
			$help = '';
			$this->setResult( 'ERROR', $message, $help );
		}

		// Adobe DPS pub channels (type == 'dps')
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		$channelInfos = DBChannel::getChannelsByType( 'dps' );
		if( $channelInfos ) {
			$help = '';
			$this->setResult( 'ERROR', $message, $help );
		}

		// DPS_ prefixed resources found in Enterprise/config/configlang.php
		require_once BASEDIR.'/server/bizclasses/BizResources.class.php';
		$terms = BizResources::getConfigTerms();
		$result = array_filter( $terms, function( $termKey ) {
			return substr( $termKey, 0, 4 ) === 'DPS_';
		}, ARRAY_FILTER_USE_KEY);
		if( $result ) {
			$help = '';
			$this->setResult( 'ERROR', $message, $help );
		}
	}
}
