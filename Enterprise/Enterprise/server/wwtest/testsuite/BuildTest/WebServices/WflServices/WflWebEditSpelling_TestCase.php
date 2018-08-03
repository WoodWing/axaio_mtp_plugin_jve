<?php
/**
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflWebEditSpelling_TestCase extends TestCase
{
	private $ticket = null;      // User ticket of current PHP session.
	private $pubInfo = null;
	private $utils = null; // WW_Utils_TestSuite

	public function getDisplayName() { return 'Web Edit Spelling'; }
	public function getTestGoals()   { return 'Checks if the spelling services are working well.'; }
	public function getTestMethods() { return 'Call all installed spelling services and see whether they return a good data structure.'; }
	public function getPrio()        { return 104; }

	/**
	 * Entry point of this TestCase called by the core server to run the test.
	 */
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
   		$this->pubInfo = $vars['BuildTest_WebServices_WflServices']['publication'];
		if( !$this->ticket || !$this->pubInfo ) {
			$this->setResult( 'ERROR', 'Could not find test data to work on.', 
								'Please enable the "Setup test data" entry and try again.' );
			return;
		}
		
		// We pick the HunspellShellSpelling server plug-in, and so it must be installed.
		require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
		$bizSpelling = new BizSpelling();
		$plugins = $bizSpelling->getInstalledSpellingPlugins();
		$hunspellFound = false;
		if( $plugins ) foreach( $plugins as $plugin ) {
			if( $plugin->UniqueName == 'HunspellShellSpelling' ) {
				$hunspellFound = true;
				break; // found
			}
		}
		if( !$hunspellFound ) {
			$this->setResult( 'ERROR', 
					'The "HunspellShellSpelling" server plug-in is not enabled.', 
					'Please enable it at the Server Plug-ins page.' );
			return;
		}
		
		// Check if HunspellShellSpelling has support for American English (enUS)
		// and is configured for all brands or the brand we have picked for testing.
		try {
			$bizSpelling->getConfiguredSpelling( $this->pubInfo->Id, 'enUS', 'HunspellShellSpelling', false );
		} catch( BizException $e ) {
			// Include details to ease solving problems at Build Test page.
			$this->setResult( 'ERROR', $e->getMessage() . '; '. $e->getDetail(), 
					'Please check the ENTERPRISE_SPELLING option in the configserver.php file.' ) ;
			return;
		}

		// Run the tests.
		$maxTests = 1; // Already prepared to make several runs, but we use 1 run only.
		for( $testId = 1; $testId <= $maxTests; $testId++ ) {
			$this->testCheckSpelling( $testId );
			$this->testGetSuggestions( $testId );
			$this->testCheckSpellingAndSuggest( $testId );
		}
	}
	
	/**
	 * Tests the CheckSpelling workflow service.
	 *
	 * @param integer $testId Test run identifier [1...n].
	 */
	private function testCheckSpelling( $testId )
	{
		require_once BASEDIR . '/server/services/wfl/WflCheckSpellingService.class.php';
		$request = new WflCheckSpellingRequest();
		$request->Ticket        = $this->ticket;
		$request->PublicationId = $this->pubInfo->Id;
		$request->Language      = 'enUS';
		$request->WordsToCheck  = array_merge( $this->getGoodWordsToCheck( $testId ), $this->getBadWordsToCheck( $testId ) );

		$stepInfo = 'CheckSpelling service';
		/*$response =*/ $this->runService( $request, $stepInfo );
	}
	
	/**
	 * Tests the GetSuggestions workflow service.
	 *
	 * @param integer $testId Test run identifier [1...n].
	 */
	private function testGetSuggestions( $testId )
	{
		require_once BASEDIR . '/server/services/wfl/WflGetSuggestionsService.class.php';
		$request = new WflGetSuggestionsRequest();
		$request->Ticket        = $this->ticket;
		$request->PublicationId = $this->pubInfo->Id;
		$request->Language      = 'enUS';
		$request->WordsToCheck  = $this->getBadWordsToCheck( $testId );

		$stepInfo = 'GetSuggestions service';
		$response = $this->runService( $request, $stepInfo );
		if( $response ) {
			$this->validateSuggestions( $testId, $response->Suggestions );
		}
	}
	
	/**
	 * Tests the AndSuggest workflow service.
	 *
	 * @param integer $testId Test run identifier [1...n].
	 */
	private function testCheckSpellingAndSuggest( $testId )
	{
		require_once BASEDIR . '/server/services/wfl/WflCheckSpellingAndSuggestService.class.php';
		$request = new WflCheckSpellingAndSuggestRequest();
		$request->Ticket        = $this->ticket;
		$request->PublicationId = $this->pubInfo->Id;
		$request->Language      = 'enUS';
		$request->WordsToCheck  = array_merge( $this->getGoodWordsToCheck( $testId ), $this->getBadWordsToCheck( $testId ) );

		$stepInfo = 'CheckSpellingAndSuggest service';
		$response = $this->runService( $request, $stepInfo );
		if( $response ) {
			$this->validateSuggestions( $testId, $response->Suggestions );
		}
	}
	
	/**
	 * Composes correctly spelled words to be checked by spelling engines.
	 *
	 * @param integer $testId Test run identifier [1...n].
	 * @return string[] List of words.
	 */
	private function getGoodWordsToCheck( $testId )
	{
		$wordsToCheck = array();
		switch( $testId ) {
			case 1:
				$wordsToCheck = array( 'obvious' );
				break;
		}
		return $wordsToCheck;
	}

	/**
	 * Composes misspelled words to be checked by spelling engines.
	 *
	 * @param integer $testId Test run identifier [1...n].
	 * @return string[] List of words.
	 */
	private function getBadWordsToCheck( $testId )
	{
		$wordsToCheck = array();
		switch( $testId ) {
			case 1:
				$wordsToCheck = array( 'firstt' );
				break;
		}
		return $wordsToCheck;
	}
	
	/**
	 * Validates suggestions returned by the spelling services.
	 *
	 * @param integer $testId Test run identifier [1...n].
	 * @param Suggestion[] $suggestions
	 * @throws BizException
	 */
	private function validateSuggestions( $testId, $suggestions )
	{
		$errDetails = '';
		$expectedCount = 1;
		switch( $testId ) {
			case 1:
				if( empty( $suggestions ) ) {
					$errDetails = 'Expected '.$expectedCount.' suggestion(s), but returned no suggestions.';
				} else if( count($suggestions) != $expectedCount ) {
					$errDetails = 'Expected '.$expectedCount.' suggestion(s), but returned '.
									count($suggestions).' suggestion(s): '.print_r($suggestions,true);
				} else {	
					$found = false;
					foreach( $suggestions as $suggestion ) {
						if( $suggestion->MisspelledWord == 'firstt' ) {
							$found = true;
						}
					}
					if( !$found ) {
						$errDetails = 'Expected "first" to be misspelled, but no suggestions given.';
					}
				}
			break;
		}
		if( $errDetails ) {
			throw new BizException( null, 'Server', $errDetails, 'Bad suggestions returned.' );
		}
	}
	
	/**
	 * Calls a web service.
	 *
	 * @param object $request
	 * @param string $stepInfo Additional info to log.
	 * @return object Response data object
	 */
	private function runService( $request, $stepInfo )
	{
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( isset($response->Reports[0]) ) { // Introduced in v8.0 (only certain services support ErrorReports)
			$errMsg = '';
			foreach( $response->Reports as $report ){
				foreach( $report->Entries as $reportEntry ) {
					$errMsg .= $reportEntry->Message . PHP_EOL;
				}
			}
			$serviceName = get_class( $request ); // e.g. returns 'WflDeleteObjectsRequest'
			$serviceName = substr( $serviceName, strlen('Wfl'), strlen($serviceName) - strlen('Wfl') - strlen('Request') );
			$this->setResult( 'ERROR', $serviceName.': failed: "'.$errMsg.'"' );
		}				
		return $response;
	}
}
		