<?php
/**
 * @package 	Enterprise
 * @subpackage 	TestSuite
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_PhpCodingTest_PhpCoding_ResourceStrings_TestCase extends TestCase
{
	public function getDisplayName() { return 'Resource string validation'; }
	public function getTestGoals()   { return 'Avoids inconsistencies between the supported languages, avoid invalid SOAP respsonses due to bad Unicode chars (e.g. triple-dot), avoids duplicate resource keys, etc.'; }
	public function getTestMethods() { return 'Parses the XML resource files for all languages and checks then all against English. Referenced resource in brackets {}, % params and S-codes are checked on existence and consistency.'; }
    public function getPrio()        { return 30; }
    
	/**
	 * Performs the test as written in module header.
	 */
	final public function runTest()
	{
		
		// The "{} variables (count) mismatch" errors can be suppressed because for German, Polish  
		// and Russian languages some strings can not be taken literally. For example:
		//    {} variables (count) mismatch for INFO_DOSSIER_UNPUBLISHED;
		//    -> English: "{DOSSIER} successfully unpublished"
		//    -> deDE: "Veröffentlichung des Dossiers erfolgreich aufgehoben"
		// Those cases can be explicitly suppressed by adding them to the list below.
		$varCountExceptions = array( 
			'deDE' => array( 
				'INFO_DOSSIER_UNPUBLISHED' => 'DOSSIER',
				),
			'plPL' => array( 
				'ACT_MULT_PLACEMENT' => 'EDITION',
				'WARN_ELEMENT_PLACED_EDITION' => 'EDITION',
				),
			'ruRU' => array(
				'ACT_ADD_CATEGORY' => 'CATEGORY',
				'ACT_ADD_STATUS' => 'STATE',
				'ACT_SURE_DELETE_STATES_IN_PUBLICATION' => 'STATES',
				'ACT_SURE_DELETE_STATUS_QMK' => 'STATE',
				'ERR_DELETE_ISSUE' => 'ISSUE',
				'ERR_DELETE_SECTION' => 'SECTION',
				'ERR_INVALIDSTATE' => 'STATE',
				'ERR_NAME_EXISTS' => 'ISSUE',
				'NEW_PUBLICATION' => 'PUBLICATION',
				'OBJ_NUMBER_OF_OBJECTS_WITH_THIS_STATUS' => 'STATE',
				'OBJ_STATUS_INFO' => 'STATE',
				'PUB_PUBLICATION_ID' => 'PUBLICATION',
				'SEC_SECTION_ID' => 'SECTION',
				'WARN_ELEMENT_PLACED_EDITION' => 'EDITION',
				'WFL_NEXT_STATUS' => 'STATE',
				'WFL_STATUS_ID' => 'STATE',
				),
		);
		
		// Determine supported languages
		require_once BASEDIR.'/server/bizclasses/BizResources.class.php';
		$langCodes = BizResources::getLanguageCodes();
		
		// Read all resource tables (all languages)
		$resTables = array();
		foreach( $langCodes as $langCode ) {
			$file = BASEDIR.'/config/resources/'.$langCode.'.xml';
			$resTables[$langCode] = $this->readRawResourceTable( $file ); // Do not use the function from BizResources since we need {} variables!
		}
		
		// Compare all languages against English
		foreach( $resTables as $langCode => $resTable ) {
			$row = 2; // first row in Excel starts at line 2
			foreach( $resTable as $key => $localized ) {
				if( $langCode != 'enUS' ) { 
					$foreignKeys = array();
					$englishKeys = array();
				
					// 1. Validate the {} variables...
					$foreignMatch = preg_match_all( '/{([^}]+)}/', $localized, $foreignKeys );
					$englishMatch = preg_match_all( '/{([^}]+)}/', $resTables['enUS'][$key], $englishKeys );
					if( $foreignMatch != $englishMatch ) { // Does one have {} vars defined and other not?
						$suppressMsg = false;
						if( isset($varCountExceptions[$langCode]) ) {
							$missingKeys = array_diff( $englishKeys[1], $foreignKeys[1] );
							$langExcepts = $varCountExceptions[$langCode];
							$suppress = 0;
							foreach( $missingKeys as $missingKey ) {
								if( isset($langExcepts[$key]) && $langExcepts[$key] == $missingKey ) {
									$suppress++;
								}
							}
							//$this->setResult( 'INFO', 'sup='.$suppress.'count='.count($missingKeys).implode(',',$missingKeys) );
							$suppressMsg = ($suppress == count($missingKeys));
						}
						if( !$suppressMsg ) {
							$message = '[Line#'.$row.'] {} variables (count) mismatch for '.$key.'; <br/>'.
										'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
										'-> '.$langCode.': "'.$localized.'"<br/>';
							$this->setResult( 'WARN', $message );
						}
					} else if( $foreignMatch && $englishMatch ) { // Does both have {} vars defined?
						if( count( array_diff( $foreignKeys[1], $englishKeys[1] ) ) > 0 ||
							count( array_diff( $englishKeys[1], $foreignKeys[1] ) ) > 0 ) { // Do the {} vars differ by name?
							if( $key != 'MNU_EDGEN' && $key != 'HEA_EDITION_GEN' ) { // accepted difference: EDITION and EDITIONS
								$message = '[Line#'.$row.'] {} variables (names) mismatch for '.$key.'; <br/>'.
											'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
											'-> '.$langCode.': "'.$localized.'"<br/>';
								$this->setResult( 'WARN', $message );
							}
						}
					}
					if( $foreignMatch ) {
						foreach( $foreignKeys[1] as $keyName ) { // Do all keys exist at resource table?
							if( !isset( $resTable[$keyName]) || empty($resTable[$keyName]) ) {
								$message = '[Line#'.$row.'] {'.$keyName.'} variable does not exist for '.$key.'; <br/>'.
											'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
											'-> '.$langCode.': "'.$localized.'"<br/>';
								$this->setResult( 'ERROR', $message );
							}
						}
					}
					
					// 2a. Validate the % parameters...
					$foreignMatch = preg_match_all( '/%([0-9]+)/', $localized, $foreignKeys );
					$englishMatch = preg_match_all( '/%([0-9]+)/', $resTables['enUS'][$key], $englishKeys );
					if( $foreignMatch != $englishMatch ) { // Does one have % params defined and other not?
						$message = '[Line#'.$row.'] % parameters (count) mismatch for '.$key.'; <br/>'.
									'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
									'-> '.$langCode.': "'.$localized.'"<br/>';
						$this->setResult( 'ERROR', $message );
					} else if( $foreignMatch && $englishMatch ) { // Does both have % params defined?
						if( count( array_diff( $foreignKeys[1], $englishKeys[1] ) ) > 0 ||
							count( array_diff( $englishKeys[1], $foreignKeys[1] ) ) > 0 ) { // Do the {} vars differ by name?
							$message = '[Line#'.$row.'] % parameters (numbers) mismatch for '.$key.'; <br/>'.
										'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
										'-> '.$langCode.': "'.$localized.'"<br/>';
							$this->setResult( 'ERROR', $message );
						}
					}

					// 2b. Validate the %-% tokens...
					$foreignMatch = preg_match_all( '/%([A-Za-z_]+%)/', $localized, $foreignKeys );
					$englishMatch = preg_match_all( '/%([A-Za-z_]+%)/', $resTables['enUS'][$key], $englishKeys );
					if( $foreignMatch != $englishMatch ) { // Does one have %-% tokens defined and other not?
						$message = '[Line#'.$row.'] %-% token (count) mismatch for '.$key.'; <br/>'.
									'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
									'-> '.$langCode.': "'.$localized.'"<br/>';
						$this->setResult( 'ERROR', $message );
					} else if( $foreignMatch && $englishMatch ) { // Does both have %-% tokens defined?
						if( count( array_diff( $foreignKeys[1], $englishKeys[1] ) ) > 0 ||
							count( array_diff( $englishKeys[1], $foreignKeys[1] ) ) > 0 ) { // Do the {} vars differ by name?
							$message = '[Line#'.$row.'] %-% token (numbers) mismatch for '.$key.'; <br/>'.
										'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
										'-> '.$langCode.': "'.$localized.'"<br/>';
							$this->setResult( 'ERROR', $message );
						}
					}
		
					// 3. Validate the S-codes (server error codes)...
					$foreignMatch = preg_match_all( '/\(S([0-9]+)\)/', $localized, $foreignKeys );
					$englishMatch = preg_match_all( '/\(S([0-9]+)\)/', $resTables['enUS'][$key], $englishKeys );
					if( $foreignMatch != $englishMatch ) { // Does one have S-codes defined and other not?
						if( mb_strpos( $localized, chr(0xEF).chr(0xBC).chr(0x88) ) != false ||  // wrong ( bracket
							mb_strpos( $localized, chr(0xEF).chr(0xBC).chr(0x89) ) != false ) { // wrong ) bracket
							// Typically could happen for asian languages, which is bad because S-codes are parsed by client apps !!!
							$message = '[Line#'.$row.'] S-codes (bracket type) mismatch for '.$key.'; (use normal brackets instead)<br/>'.
										'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
										'-> '.$langCode.': "'.$localized.'"<br/>';
							$this->setResult( 'ERROR', $message );
						} else {			
							$message = '[Line#'.$row.'] S-codes (count) mismatch for '.$key.'; <br/>'.
										'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
										'-> '.$langCode.': "'.$localized.'"<br/>';
							$this->setResult( 'ERROR', $message );
						}
					} else if( $foreignMatch && $englishMatch ) { // Does both have {} vars defined?
						if( count( array_diff( $foreignKeys[1], $englishKeys[1] ) ) > 0 ||
							count( array_diff( $englishKeys[1], $foreignKeys[1] ) ) > 0 ) { // Do the {} vars differ by name?
							$message = '[Line#'.$row.'] S-codes (numbers) mismatch for '.$key.'; <br/>'.
										'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
										'-> '.$langCode.': "'.$localized.'"<br/>';
							$this->setResult( 'ERROR', $message );
						}
					}
					
					// 4. Special counts
					// temp patch wrong () brackets to be able to compare bracket counts
					/*$localized = str_replace( chr(0xEF).chr(0xBC).chr(0x88), '(', $localized );
					$localized = str_replace( chr(0xEF).chr(0xBC).chr(0x89), ')', $localized );
					$specials = array( '"' => 'double quotes', 
						'[' => 'square bracket', ']' => 'square bracket', 
						'(' => 'text bracket', ')' => 'text bracket',
						'\'' => 'single quote', '&' => 'ampersant', 
						'<' => 'less than', '>' => 'greater than' );
					foreach( $specials as $pattern => $meaning ) {
						if( count( split( $pattern, $localized ) ) != 
							count( split( $pattern, $resTables['enUS'][$key] ) ) ) {
							$message ='[Line#'.$row.'] Special char ('.$meaning.') mismatch for '.$key.'; <br/>'.
										'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
										'-> '.$langCode.': "'.$localized.'"<br/>';
							$this->setResult( 'WARN', $message );
						}
					}*/
					
					// 5. Detect dangerous escape sequences (due to reserved chars for JavaScript/XHTML)
					$needles = array(
						'\\\'' => 'an escaped single quote (bad solution; please remove the slash)',
						'\\"' => 'an escaped double quote  (bad solution; please remove the slash)',
						'"' => 'a double quote (bad solution; please use single quote instead)',
					);
					foreach( $needles as $needle => $meaning ) {
						if( strpos( $localized, $needle ) !== false ) {
							$message ='[Line#'.$row.'] Detected '.$meaning.' for '.$key.'; <br/>'.
										'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
										'-> '.$langCode.': "'.$localized.'"<br/>';
							$this->setResult( 'ERROR', $message );
						}
					}
				}
				
				// 6. Validate special characters
				if( mb_strpos( $localized, chr(0xE2).chr(0x80).chr(0xA6) ) != false ) { // triple-dot
					$message = '[Line#'.$row.'] Special char (triple-dot) not allowed for '.$key.'; (use three dots instead) <br/>'.
								'-> English: "'.$resTables['enUS'][$key].'" <br/>'.
								'-> '.$langCode.': "'.$localized.'"<br/>';
					$this->setResult( 'ERROR', $message );
				}
				$row++;
			}
		}
	}
	
	private function readRawResourceTable( $file )
	{
		// Read and parse string resources from XML file
		$retDict = array();
		$doc = new DOMDocument();
		$doc->load( $file );
	
		// Walk through all terms of string resources file
		$xpath = new DOMXPath( $doc );
		$query = '//LocalizationMap/Term'; 
		$terms = $xpath->query( $query );
		foreach( $terms as $term ) {
			if( $term->childNodes->length > 0 ) {
				// Collect key-value pairs in dictionary to return caller
				$key = $term->getAttribute( 'key' );
				if( isset($retDict[$key]) ) {
					$message = 'Duplicate resource key "'.$key.'" while reading file "'.$file.'" <br/>';
					$this->setResult( 'ERROR', $message );
				}
				$retDict[$key] = $term->childNodes->item( 0 )->nodeValue;
			}
		}
		return $retDict;
	}	
}
