<?php

/**
 * Test utils to operate on the Elvis queue that contains pending asset updates to be synced to Enterprise objects.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
class Elvis_TestSuite_BuildTest_Elvis_SyncUtils
{
	/**
	 * Simulate a scheduler by calling the sync.php module.
	 *
	 * @param TestCase $testCase
	 * @param int $maxUpdates Maximum updates from the Elvis queue to sync to Enterprise.
	 * @return bool
	 */
	public function callSyncPhpModule( TestCase $testCase, int $maxUpdates = 1 ) : bool
	{
		$result = true;
		try {
			$maxExecTime = 10;
			$client = new Zend\Http\Client();
			$client->setUri( LOCALURL_ROOT.INETROOT.'/config/plugins/Elvis/sync.php' );
			$client->setMethod( Zend\Http\Request::METHOD_GET );
			$client->setParameterGet( array(
				'maxexectime' => $maxExecTime, // Max number of seconds wait for the whole operation.
				'maxtimeoutperrun' => 1, // Number of seconds waiting at Elvis side reading updates from the queue (for each run of updates).
				'maxupdates' => $maxUpdates,
				'production' => 'false',
				//'XDEBUG_SESSION_START' => 'PHPSTORM',
				// L> To debug a job, uncomment the above and clear the TESTSUITE['SoapUrlDebugParams'] option.
			) );
			$client->setOptions( array(
				'timeout' => $maxExecTime + 5, // give 5 seconds slack to let sync.php complete before disconnecting
				'connecttimeout' => ELVIS_CONNECTION_TIMEOUT,
				'adapter' => 'Zend\Http\Client\Adapter\Curl' ) );
			$client->send();
			$response = $client->getResponse();
			if( !$response->isSuccess() ) {
				$testCase->setResult( 'ERROR', 'Failed calling sync.php: '.$response->getReasonPhrase().PHP_EOL );
				$result = false;
			}
		} catch( Exception $e ) {
			$testCase->setResult( 'ERROR', 'Failed calling sync.php: '.$e->getMessage() );
			$result = false;
		}

		return $result;
	}

	/**
	 * Clear the Elvis queue to avoid side effects for succeeding test scripts.
	 *
	 * When the BuildTest hit an error, there might be some changes left in the Elvis queue.
	 * However, a succeeding test script could assume to start with an empty queue.
	 * This function can be called before and after the test to avoid one script badly affecting another.
	 */
	public function emptyElvisQueue() : void
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		$service = new ElvisContentSourceService();
		do {
			$updates = $service->retrieveAssetUpdates( 1 ); // Get updates from Elvis.
			$updateIds = array_map( function( $update ) { return $update->id; }, $updates );
			if( $updateIds ) {
				$service->confirmAssetUpdates( $updateIds ); // Confirm updates to Elvis, removing them from the queue.
			}
		} while( $updateIds );
	}

	/**
	 * Retrieve the number of asset updates pending in the Elvis queue (to be synced to Enterprise).
	 *
	 * @return int
	 */
	public function countAssetUpdates() : int
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		$service = new ElvisContentSourceService();
		return count( $service->retrieveAssetUpdates( 1 ) );
	}

	/**
	 * Push the metadata configuration to Elvis, so Elvis keeps track of those metadata fields we're interested in.
	 */
	public function pushMetadataConfig()
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		$service = new ElvisContentSourceService();
		$metadataHandler = new Elvis_BizClasses_Metadata();
		$fields = $metadataHandler->getMetadataToReturn();
		$service->configureMetadataFields( $fields );
	}
}
