<?php
/**
 * HunspellShellSpelling TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_HunspellShellSpelling_TestCase extends TestCase
{
	public function getDisplayName() { return 'Hunspell Spelling (shell)'; }
	public function getTestGoals()   { return 'Checks if HunspellShellSpelling is installed and configured correctly.'; }
	public function getTestMethods() { return 'ENTERPRISE_SPELLING option in configserver.php for HunspellShellSpelling is checked.'; }
    public function getPrio()        { return 25; }
	
	final public function runTest()
	{
		require_once BASEDIR . '/server/bizclasses/BizSpelling.class.php';
		
		$pluginName = 'HunspellShellSpelling';
		$bizSpelling = new BizSpelling();
		try {
			// #General configuration check
			$bizSpelling->validateSpellingConfiguration( $pluginName );
			
			// Check for environment variable setting (only for Windows)
			if( OS == 'WIN' ) {
				
				require_once BASEDIR.'/server/plugins/HunspellShellSpelling/HunspellShellSpelling_Spelling.class.php';
				$hunspell = new HunspellShellSpelling_Spelling();
				$dicPath = $hunspell->getEnvVar( 'DICPATH' );
				LogHandler::Log('wwtest', 'INFO', 'Environment Variable \'DICPATH\' is \''.$dicPath.'\'');
				
				$dictionary = $hunspell->getEnvVar( 'DICTIONARY' );
				LogHandler::Log('wwtest', 'INFO', 'Environment Variable \'DICTIONARY\' is \''.$dictionary.'\'');
			}
			
			
			// #Dictionaries encoding check
			// At this point, the HealthCheck can assume that the dictionaries defined in configserver are installed.
			// Get configured dictionaries
			$configs = $bizSpelling->getConfiguredSpelling( 0 /*ALL pubId*/, null/*language*/, $pluginName, false);
			
			// Get full path of ALL the INSTALLED dictionaries
			$language = null;
			$connector = $bizSpelling->getInstalledSpellingConnector( null, $language, $pluginName );
			$dictionariesPath = $connector->getInstalledDictionariesAndPath( $pluginName );
			
			// Retrieves full path of the CONFIGURED Hunspell dictionaries 
			foreach( $configs as $pubConfig ){
				foreach( $pubConfig as $language => $langConfig ) {
					foreach( $langConfig['dictionaries'] as $configuredDictionary ){
						foreach( $dictionariesPath as $dictionaryPath ){
							if( basename( $dictionaryPath, '.aff') == $configuredDictionary ){
								$affixFileNames[] = $dictionaryPath;
								break;
							}
						}
					}
				}
			}
			
			$notUtf8Encoded = array();	
			// Now, do the encoding check.
			$notReadableAffixFile = array();					
			foreach( $affixFileNames as $affixFileName ){
				$affixFileName = $affixFileName . '.aff';
				if( is_readable( $affixFileName ) ){
					$affixFile = file_get_contents( $affixFileName );
					$affixFileContents = mb_split( '\n', $affixFile );
					foreach( $affixFileContents as $affixFileContent ){
						if( mb_ereg_match( '^SET ', $affixFileContent ) ){
							list( , $encoding ) = mb_split( '\s', $affixFileContent );
							if( !mb_ereg_match( 'UTF-8', $encoding ) ){ // Encoding not in UTF-8, mark it
								$notUtf8Encoded[ baseName( $affixFileName, '.aff' ) ] = dirname( $affixFileName );
							}
							break;
						}
					}
				}else{
					$notReadableAffixFile[ baseName( $affixFileName, '.aff' ) ] = dirname( $affixFileName );
				}
			}
			
			if( count( $notReadableAffixFile ) > 0 ) {
				$detail = 'The following language of Hunspell dictionary(s) are not readable [' .
				implode( ',', array_keys( $notReadableAffixFile ) ) . '].';
				$help = 'Please ensure www/inet user has read access to these dictionaries affix files.';
				$this->setResult('ERROR', $detail, $help );
			}else if( count( $notUtf8Encoded ) > 0 ) {
				$detail = 'The following language of Hunspell dictionary(s) are not in UTF-8 encoding [' .
				implode( ',', array_keys( $notUtf8Encoded ) ) . '].';
				$help = 'Please click <a href=\'../plugins/HunspellShellSpelling/dictioencoder.php\'>here</a> to convert into UTF-8.';
				$this->setResult('ERROR', $detail, $help );
			}
			
			LogHandler::Log('wwtest', 'INFO', 'Validated HunspellShellSpelling configuration.' );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), $e->getDetail() );
			return;
		}
	}
	
}
