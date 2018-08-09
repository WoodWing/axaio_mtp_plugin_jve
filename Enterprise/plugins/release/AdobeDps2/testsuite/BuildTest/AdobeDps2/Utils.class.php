<?php

/**
 * Contains helper functions for the AdobeDps2 tests.
 *
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class AdobeDps2_Utils
{
	private $testCase = null;

	public function __construct( TestCase $testCase )
	{
		$this->testCase = $testCase;
	}

	/**
	 * Empty the server jobs in the job queue created by this test.
	 *
	 * In case of error in the BuildTest, the server jobs cannot be processed,
	 * they are left in the queue. This function clears all the jobs in the queue
	 * to make sure that the next run of the test, it starts with a cleared queue.
	 *
	 * This function can be called before and after the test.
	 */
	public function emptyServerJobsQueue()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		// Clear all the jobs created in the job queue.
		$bizServerJob = new BizServerJob;
		$jobs = $bizServerJob->listJobs();
		if ( count( $jobs ) > 0 ) {
			foreach( array_keys( $jobs ) as $jobId ) {
				$bizServerJob->deleteJob( $jobId );
			}
		}
	}

	/**
	 * Run the job scheduler by calling the jobindex.php.
	 *
	 * @param int $maxexectime The max execution time of jobindex.php in seconds.
	 * @param int $maxJobProcesses The maximum number of jobs that the job processor is allowed to pick up at any one time.
	 * @return bool
	 */
	public function runServerJobs( $maxexectime = 5, $maxJobProcesses = 3 )
	{
		$retVal = true;
		try {
			require_once 'Zend/Http/Client.php';
			$url = LOCALURL_ROOT.INETROOT.'/jobindex.php';
			$client = new Zend_Http_Client();
			$client->setUri( $url );
			$client->setParameterGet( 'maxexectime', $maxexectime );
			$client->setParameterGet( 'maxjobprocesses', $maxJobProcesses );
			$client->setConfig( array( 'timeout' => $maxexectime + 30 ) ); // before breaking connection, let's give the jobprocessor 30s more to complete
			$response = $client->request( Zend_Http_Client::GET );

			if( !$response->isSuccessful() ) {
				$this->testCase->setResult( 'ERROR', 'Failed calling jobindex.php: '.$response->getHeadersAsString( true, '<br/>' ) );
				$retVal = false;
			}
		} catch ( Zend_Http_Client_Exception $e ) {
			$this->testCase->setResult( 'ERROR', 'Failed calling jobindex.php: '.$e->getMessage() );
			$retVal = false;
		}

		return $retVal;
	}

}