<?php
/**
 * Spelling checker and suggestions using Enchant PHP integration.
 *
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/interfaces/plugins/connectors/Spelling_EnterpriseConnector.class.php';

class EnchantPhpSpelling_Spelling extends Spelling_EnterpriseConnector
{
	/**
	 * Refer to abstract class checkSpelling() header.
	 *
	 * @param array $dictionaries Language of the dictionary in the format of en_US, du_DU, ru_RU and etc.
	 * @param array $wordsToCheck
	 * @throws BizException when there's no dictionary installed
	 *
	 * @return array Misspelled words found in array of words passed in.
	 */
	public function checkSpelling( array $dictionaries, array $wordsToCheck )
	{
		$this->checkInstallation();
		$broker = enchant_broker_init();
		$returnData = array();
		// Only picks one dictionary unlike other spell engines (Aspell,Hunspell) that can takes multiple dictionaries in single command.
		$enchantSpellLang = $dictionaries[0];
		if( enchant_broker_dict_exists( $broker, $enchantSpellLang) ){
			$dictionaryResource = enchant_broker_request_dict( $broker, $enchantSpellLang );
			foreach( $wordsToCheck as $word ) {
				if( trim( $word) ){
					if( !enchant_dict_check( $dictionaryResource, $word ) ) {
						$returnData[] = $word;
					}
				}	
			}
			enchant_broker_free_dict( $dictionaryResource );
		} else {
			$detail = 'Enchant Php Spelling engine failed to check spelling for language(s) "'.$dictionaries[0].'". '.
				'Make sure that the dictionary is installed and configurations are set correctly. ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		enchant_broker_free( $broker );
		return $returnData;
	}

	/**
	 * Refer to abstract class getSuggestions() header.
	 * @inheritdoc
	 */
	public function getSuggestions( array $dictionaries, $wordForSuggestions ) 
	{
		$this->checkInstallation();
		$broker = enchant_broker_init();
		$suggestedWords = array();
		// Only picks one dictionary unlike other spell engines (Aspell,Hunspell) that can takes multiple dictionaries in single command.
		$enchantSpellLang = $dictionaries[0];
		if( enchant_broker_dict_exists( $broker, $enchantSpellLang ) ) {
			$dictionaryResource = enchant_broker_request_dict( $broker, $enchantSpellLang );
			$suggestedWords = enchant_dict_suggest( $dictionaryResource, $wordForSuggestions );
			if( $suggestedWords ){
				$suggestedWords = array_slice( $suggestedWords, 0, $this->config['suggestions'] );
			}
			enchant_broker_free_dict( $dictionaryResource );
		}else{
			$detail = 'Enchant spelling engine failed to get suggestions for language(s) "'.$dictionaries[0].'". '.
				'Make sure that the dictionary is installed and configurations are set correctly. ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		enchant_broker_free( $broker );
		return $suggestedWords;
	}
	
	/*
	* Enumerates Enchant providers.
	* @throws BizException when there's no Enchant installed.
	* 
	* @return array of Enchant providers(e.g aspell,myspell,ispell). Each provider has property of [name],[desc] and [file] (location of the provider).
	*/
	private function getEngines()
	{
		$this->checkInstallation();
		$broker = enchant_broker_init();
		$aboutEnchant = null;
		$aboutEnchant = enchant_broker_describe( $broker );
		enchant_broker_free( $broker );
		return $aboutEnchant;
	}
	
	private function checkInstallation()
	{
		if( !$this->isInstalled() ) {
			$detail = 'Enchant is not installed for PHP. Please follow instructions '.
				'at http://nl3.php.net/manual/en/book.enchant.php.';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
	}

	private function isInstalled()
	{
		return extension_loaded('enchant') && function_exists( 'enchant_broker_init' );
	}
	
	/**
	 * Refer to abstract class getEngineVersion() header.
	 */
	public function getEngineVersion()
	{
		if( $this->isInstalled() ) {
			// TODO: replace shell_exec() call with phpInfo() call ???
			$stdErrOutputFile = $this->getTempFileName();
			$command = '"'.$this->config['location']. '" -v 2>' . $stdErrOutputFile;
			$enchantVersionInFull = shell_exec( $command );
			$positionOfEnchant = strripos( $enchantVersionInFull, 'Enchant ' );
			if( $positionOfEnchant ) {
				$totalCharactersToBeReplaced = $positionOfEnchant + strlen('Enchant') + 1;
				$enchantVersion = substr_replace( $enchantVersionInFull, '', 0, $totalCharactersToBeReplaced );
				$enchantVersion = trim(str_replace( ')', '', $enchantVersion ));
			} else {
				$errFromSpellEngine = '';
				if( filesize( $stdErrOutputFile ) > 0 ){
					$errFromSpellEngine = 'Error from spelling engine: "'.
										file_get_contents( $stdErrOutputFile ).'". ';
				}
				$message = 'Could not determined the version of the Enchant Spelling engine. '.
				$errFromSpellEngine .
				'Command used: "'.$command.'". ';
				LogHandler::Log( 'Spelling', 'ERROR', $message );
				$enchantVersion = '?'; // Since cannot retrieve version, just return '?'.
			}
			@unlink( $stdErrOutputFile );
		} else {
			$enchantVersion = '?';
		}
		return $enchantVersion;
	}

	/**
	 * Refer to abstract class getInstalledDictionaries() header.
	 */	
	public function getInstalledDictionaries()
	{
		$dictionaries = array();
		if( $this->isInstalled() ) {
			$broker = enchant_broker_init();
			$dictionariesInfo = enchant_broker_list_dicts( $broker );
			foreach( $dictionariesInfo as $dictionaryInfo ){
				$dictionaries[] = $dictionaryInfo['lang_tag'];
			}
			enchant_broker_free( $broker );
		}
		return $dictionaries;	
	}
	
	public function getEnchantBackendPath( $backendName )
	{
		$enchantBackends = $this->getEngines();
		$backendLocatedPath = null;
		if( $enchantBackends ) foreach( $enchantBackends as $enchantBackend ){
			if( $enchantBackend['name'] == $backendName ){
				$backendLocatedPath = $enchantBackend['file'];
			}
		}
		return $backendLocatedPath;
	}

	/*
	* Returns temporary file name with prefix 'esp'.
	*
	* @return string temporary file name
	*/
	private function getTempFileName()
	{
		$tmpDir = sys_get_temp_dir();
		$tmpFile = tempnam( $tmpDir, 'esp' ); // esp = Enterprise Spelling
		if( !$tmpFile ) {
			$detail = 'Enchant Spelling could not generate temporary file at "'.$tmpDir.'" folder. '.
				'Please check if www/inet user has write access to the system temp folder. ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		return $tmpFile;
	}	
}

