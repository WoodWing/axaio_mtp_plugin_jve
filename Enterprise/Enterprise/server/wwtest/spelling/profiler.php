<?php
/**
 * Test application to measure the performance differences between all kind of installed spelling 
 * integrations. Those integrations are established through Server Plug-ins.
 *
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

ini_set('display_errors', 1);
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
require_once BASEDIR.'/server/utils/FolderUtils.class.php';
require_once BASEDIR.'/server/utils/FolderInterface.intf.php';

/*$ticket =*/ checkSecure('publadmin');

class EnterpriseSpellingProfilerApplication implements FolderIterInterface
{
	private $testSamples = array();
	
	private function getMicrotime() 
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}

	/**
	 * Called by parent class for each file found at the testdata folder.
	 *
	 * @param $filePath string  Full file path of the file.
	 * @param $level    integer Current ply in folder structure of recursion search.
	 */
	public function iterFile( $filePath, $level )
	{
		$level = $level; // make analyzer happy
		$fileName = basename( $filePath, '.txt' );
		$parts = explode( '_', $fileName );
		$testSample = array();
		$testSample['testFile'] = $filePath;
		$testSample['language'] = $parts[0];
		if( $parts[1] == 'Correct' ) {
			$testSample['description'] = 'Good Words';
		} else if( $parts[1] == 'Wrong' ) {
			$testSample['description'] = 'Bad Words';
		} else {
			$testSample['description'] = $parts[1];
		}
		$testSample['wordcount'] = ltrim( $parts[2], 0 ); // take out the leading zero(s)
		$this->testSamples[] = $testSample;
	}

	// These three functions are called by parent class, but have no meaning here.
	public function skipFile( $filePath, $level )
	{
		$filePath = $filePath; $level = $level; // make analyzer happy
	}
	public function iterFolder( $folderPath, $level )
	{
		$folderPath = $folderPath; $level = $level; // make analyzer happy
	}
	public function skipFolder( $folderPath, $level )
	{
		$folderPath = $folderPath; $level = $level; // make analyzer happy
	}

	public function runTest( $bizSpelling, $plugins )
	{
		// Collect all test sample files at the testdata folder (collected at $this->testSamples).
		$exclFolders = array();
		FolderUtils::scanDirForFiles( $this, dirname(__FILE__).'/testdata', array('txt'), $exclFolders );
		sort( $this->testSamples );

		// Show the header of the test results table
		echo '<table border="1"><thead><tr><th>Engine</th><th>Version</th><th>Description</th>';
		foreach( $this->testSamples as $testSample ) {
			echo
				'<th>'.
					'<nobr>'.$testSample['language'].': '.$testSample['wordcount'].'</nobr><br/>'.
					'<nobr>'.$testSample['description'].'</nobr>'.
				'</th>'.PHP_EOL;
		}
		echo '</tr></thead><tbody>';
	
		// Iterate through the spelling integrations and run tests.
		$tdCounter = 0;
		$oddCount = false;
		foreach( $plugins as $pluginObj ) {
			try {
				// Show the first 3 columns at the test result table with server plugin info.
				$pluginName = $pluginObj->UniqueName;
				echo '<tr valign="top">'.
						'<td>'.$pluginName.'</td>'.
						'<td>'.$bizSpelling->getEngineVersion( $pluginName ).'</td>'.
						'<td align="right">Misspelled:<br/>Duration:<br/></td>'.PHP_EOL;
	
				// Check the spelling integration and configuration.
				$bizSpelling->validateSpellingConfiguration( $pluginName );

				// Run all test sample files against the spelling integration.
				foreach( $this->testSamples as $testSample ) {
					$tdCounter++;
					// Read and parse the test sample file.
					$passage = file_get_contents( $testSample['testFile'] );
					$wordsToCheck = mb_split('\n', $passage ); // the test files must have binary file type at Perforce to let this work on both.
					
					// Run the test and measure performance.
					$startTime = $this->getMicrotime();
					$errorWords = $bizSpelling->checkSpelling( null, $testSample['language'], $wordsToCheck, $pluginName );
					$duration = round( $this->getMicrotime() - $startTime, 3 );

					// Check if the misspelled words counts are expected. If not, show counts in red.
					if( ( count( $errorWords ) == 0 && $testSample['description'] == 'Good Words' ) ||
						( count( $wordsToCheck ) == count( $errorWords ) && $testSample['description'] == 'Bad Words') ){
						$misspelled = '<font color="black">'.count($errorWords).'</font>';
					} else {
						$misspelled = '<font color="red">'.count($errorWords).'*</font>';
						$oddCount = true;
					}
					
					// Show misspelled word counts and duration of the test.
					echo '<td>'.$misspelled.'<br/>'.$duration.' s<br/></td>'.PHP_EOL;
				}
			} catch ( BizException $e ) {
				echo '<td colspan='.$tdCounter.'><font color="red">ERROR: '.$e->getMessage().'<br/>'.
						$e->getDetail().'<br/></font></td>'.PHP_EOL;
			}
			echo '</tr>' . PHP_EOL;
		}
		echo '</tbody></table>' . PHP_EOL;
		echo '<p>Test completed.</p>' . PHP_EOL;
	
		// Show remarks.
		if( $oddCount ) {
			echo '<p><b>Remarks:</b><br/><font color="red">*</font> When \'Good Words\' are tested, the result should be ZERO errors.<br/>'.
				'When \'Bad Words\' are tested, the errors found should be equal to the total of words tested.<br/>' . 
				'Unexpected behaviors are marked red.<br/></p>' . PHP_EOL;
		}
	}
}
?>
<html>
<head>
	<title>Enterprise Spelling - Performance Profiler</title>
	<meta http-equiv="Content-Type" content="text/plain; charset=UTF-8" />
</head>
<body style="font-family: Arial;">
<?php

// Show title	
echo '<h1>Enterprise Spelling - Performance Profiler</h1>'.
		'<p>Performance test report for installed spelling engines.</p>';

// Show installed server plug-ins for Spelling
$bizSpelling = new BizSpelling();
$plugins = $bizSpelling->getInstalledSpellingPlugins(); 
if( count($plugins) == 0 ) {
	echo '<font color="red">ERROR: No Spelling Server Plug-ins installed.<br/></font>';
}
/*if( count($plugins) > 0 ) {
	echo 'Found installed Server Plug-ins with Spelling integration: <ul>';
	foreach( $plugins as $plugin ) {
		echo '<li>'.$plugin->DisplayName.'</li>';
	}
	echo '</ul>' . PHP_EOL;
}*/

$testApp = new EnterpriseSpellingProfilerApplication();
$testApp->runTest( $bizSpelling, $plugins );

// Show legend
if( count($plugins) > 0 ) {
	echo '<table border="0">' . PHP_EOL;
	echo '<tr><th colspan="3" align="left">Legend:</th></tr>'. PHP_EOL;
	echo '<tr><td>-&nbsp;</td><td>enUS:</td><td>American English</td></tr>' . PHP_EOL;
	echo '<tr><td>-&nbsp;</td><td>ruRU:</td><td>Russian</td></tr>' . PHP_EOL;
	echo '<tr><td>-&nbsp;</td><td>Good Words:</td><td>Number of valid words passed to spelling engine for checking.</td></tr>' . PHP_EOL;
	echo '<tr><td>-&nbsp;</td><td>Bad Words:</td><td>Number of invallid words passed to spelling engine for checking.</td></tr>' . PHP_EOL;
	echo '<tr><td>-&nbsp;</td><td>Misspelled:</td><td>Number of words returned by spelling engine marked as misspelled.</td></tr>' . PHP_EOL;
	echo '<tr><td>-&nbsp;</td><td>Duration:</td><td>Execution time taken by spelling engine for checking all words.</td></tr>' . PHP_EOL;
	echo '</table><br/>' .  PHP_EOL;
}

?>
</body>
</html>