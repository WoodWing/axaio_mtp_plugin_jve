<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v7.4
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * This class provides Spelling integration for Enterprise.
 * Spelling integrations are 3rd party components, but BizSpelling prepares
 * the fundamental functions needed for spelling integration like
 * validations, check spelling, get word suggestions, get installed spelling
 * integrators and installed dictionaries.
 */

class BizSpelling
{
	/**
	 * Returns all installed Spelling plugins.
	 *
	 * @return PluginInfoData[] List of spelling plug-ins found in DB. Empty list when none found.
	 */
	public function getInstalledSpellingPlugins()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connectors = BizServerPlugin::searchConnectors( 'Spelling', null );
		$pluginObjs = array();
		foreach( array_keys($connectors) as $connName ) {
			$pluginObj = BizServerPlugin::getPluginForConnector( $connName );
			if( $pluginObj && $pluginObj->IsInstalled && $pluginObj->IsActive ) {
				$pluginObjs[] = $pluginObj;
			}
		}
		return $pluginObjs;
	}
	
	/**
	 * Retrieves installed spelling plug-in connector from DB.
	 * 
	 * @param integer $publicationId Publication Db Id. Zero to get system-wide config.
	 * @param string &$language Language code in [llCC] format (l = language code, C = county code) configured for the requested $publicationId
	 * @param string &$pluginName Unique spelling plugin name configured for the requested $publicationId.
	 * @throws BizException When plugin requested( $pluginName ) is not installed.
	 * @return EnterpriseConnector Spelling plug-in connector
	 */
	public function getInstalledSpellingConnector( $publicationId, &$language, &$pluginName )
	{
		$configs = $this->getConfiguredSpelling( $publicationId, $language, $pluginName, true ); // only one
		$configs = reset($configs);
		$config = reset($configs);
		if( !$pluginName ) { 
			$pluginName = $config['serverplugin']; 
		}
		if( !$language ) { 
			$language = $config['language']; 
		}

		static $connCache = array();
		if( isset( $connCache[$pluginName] ) ) {
			$connector = $connCache[$pluginName];
		} else {
			// Run through spelling connectors, respecting the configured priority
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$pluginObj = BizServerPlugin::getPluginForConnector( $pluginName.'_Spelling' );
			if( $pluginObj ){
				$connector = BizServerPlugin::searchConnectorByPluginId(
								'Spelling', null, $pluginObj->Id, $pluginObj->IsActive, $pluginObj->IsInstalled );
			}
			if( isset($connector) ) {
				$connCache[$pluginName] = $connector;
			} else {
				throw new BizException( null, 'Server', 
					'Could not find Spelling connector for Server Plug-in "'.$pluginName.'".' .
					'Please enable the plugin in server plugin page on web admin.',
					'Configuration error' );
			}
		}
		$connector->setConfiguration( $config );
		return $connector;
	}
	
	/**
	 * See header of Enterprise/server/interfaces/plugins/connectors/EnterpriseSpelling.class.php
	 *
	 * @param string|null $pluginName Internal name of the Server Plug-in with a Spelling connector. Null for all.
	 * @return string[]|null List of installed dictionaries.
	 */
	private function getInstalledDictionaries( $pluginName )
	{
		$language = null;
		$connector = $this->getInstalledSpellingConnector( null, $language, $pluginName );
		return $connector->getInstalledDictionaries();
	}

	/**
	 * See header of Enterprise/server/interfaces/plugins/connectors/EnterpriseSpelling.class.php
	 *
	 * @param integer $publicationId Brand DB id. Zero to get system-wide config.
	 * @param string|null $language Language in [llCC] format (l = language code, C = county code). Null for all.
	 * @param string[] $wordsToCheck The words to check spelling.
	 * @param string|null $pluginName Internal name of the Server Plug-in with a Spelling connector. Null for all.
	 * @return string[] Badly spelled words.
	 * @throws BizException when no configuration was found.
	 */
	public function checkSpelling( $publicationId, $language, array $wordsToCheck, $pluginName = null ) 
	{
		$connector = $this->getInstalledSpellingConnector( $publicationId, $language, $pluginName );
		$configs = $this->getConfiguredSpelling( $publicationId, $language, $pluginName, true ); // return only one config
		$dictName = key($configs[0]);
		LogHandler::Log('bizspelling','INFO','checkSpelling using dictionary [' . $dictName . ']');
		return $connector->checkSpelling( $configs[0][$dictName]['dictionaries'], $wordsToCheck );
	}

	/**
	 * See header of Enterprise/server/interfaces/plugins/connectors/EnterpriseSpelling.class.php
	 *
	 * @param integer $publicationId Brand DB id. Zero to get system-wide config.
	 * @param string|null $language Language in [llCC] format (l = language code, C = county code). Null for all.
	 * @param string $wordForSuggestions The corresponding word to get suggestions for.
	 * @param string|null $pluginName Internal name of the Server Plug-in with a Spelling connector. Null for all.
	 * @return Suggestion[]
	 * @throws BizException when no configuration was found.
	 */
	public function getSuggestions( $publicationId, $language, $wordForSuggestions, $pluginName = null )
	{
		$connector = $this->getInstalledSpellingConnector( $publicationId, $language, $pluginName );
		$configs = $this->getConfiguredSpelling( $publicationId, $language, $pluginName, true ); // return only one config
		$dictName = key($configs[0]);
		LogHandler::Log('bizspelling','INFO','getSuggestions using dictionary [' . $dictName . ']');
		return $connector->getSuggestions( $configs[0][$dictName]['dictionaries'], $wordForSuggestions );
	}

	/**
	 * See header of Enterprise/server/interfaces/plugins/connectors/EnterpriseSpelling.class.php
	 * *
	 * @param string $pluginName Internal name of the Server Plug-in with a Spelling connector.
	 */
	public function getEngineVersion( $pluginName ) 
	{
		$language = null;
		$connector = $this->getInstalledSpellingConnector( null, $language, $pluginName );
		return $connector->getEngineVersion();
	}

	/**
	 * Returns spelling configurations made at the ENTERPRISE_SPELLING option.
	 *
	 * $showInstalledOnly is a boolean to determine the below:
	 * TRUE: Returns configured spelling that is already installed.
	 * FALSE: Returns all configured spelling regardless of whether it is installed or not.
	 * By default it is always TRUE except during plugin installation, it should be set to FALSE, this is because it should return all 
	 * configured spelling instead of configured + installed spelling as at that stage, the plugin is not installed yet.
	 *
	 * @param integer $publicationId Brand DB id. Zero to get the system-wide config. Null to get all configs.
	 * @param boolean $showInstalledOnly Refer to function header.
	 * @return array Found configuration settings at the ENTERPRISE_SPELLING option.
	 */
	private function getConfiguredSpellingForPublication( $publicationId, $showInstalledOnly = true )
	{
		static $cacheDicts = null;
		if( !$cacheDicts ) { // Initialize cache
			$definedDictionaries = unserialize(ENTERPRISE_SPELLING);
			$installedPlugins = $this->getInstalledSpellingPlugins();
	
			if( $showInstalledOnly ){ // Collect dictionaries only of the installed spelling plugin
				foreach( $installedPlugins as $installedPlugin ){
					foreach( $definedDictionaries as $pubId => $pubConfig ) {
						foreach( $pubConfig as $language => $langConfig ) {
							if( $installedPlugin->UniqueName == $langConfig['serverplugin'] ){
								$cacheDicts[ $pubId ] = isset( $cacheDicts[ $pubId ] ) ? $cacheDicts[ $pubId ] : array();
								$cacheDicts[ $pubId ] = array_merge( $cacheDicts[ $pubId ], array( $language => $langConfig) );
							}
						}
					}
				}
			}else{	
				$cacheDicts = $definedDictionaries;
			}	
			
		}
		if( is_null( $publicationId ) ) { // request for entire configuration
			$dicts = $cacheDicts;
		} else if( isset( $cacheDicts[$publicationId] ) ) { // request for brand specific configuration
			$dicts = array( $publicationId => $cacheDicts[$publicationId] );
			// Do not add 'all' (publicationId=0) config to allow overruling with a full new definition.
			// This implies there is no fall-back to 'all' level.
		} else { // request for (or fall back at) system-wide configuration
			$dicts = array( 0 => $cacheDicts[0] );
		}
		// Auto-resolve the InCopy language tag (based on the specified language code)
		if( $dicts ) foreach( $dicts as $pubId => $pubConfig ) {
			if( $pubConfig ) foreach( $pubConfig as $dictName => $dictConfig ) {
				if( !isset($dictConfig['doclanguage']) ) { // allow admins to overrule!
					$dicts[$pubId][$dictName]['doclanguage'] = 
						$this->getFullNameForLanguageCode( $dictConfig['language'] );
				}
			}
		}
		return $dicts;
	}
	
	/**
	 * Provides the dictionaries for a given brand as configured at the ENTERPRISE_SPELLING option.
	 *
	 * @param string $publicationId Brand DB id. Pass in zero (0) for system-wide config.
	 * @return array of Dictionary objects. Returns NULL when none configured for the given brand.
	 */
	static public function getDictionariesForPublication( $publicationId )
	{
		$dicts = null;
		$bizSpelling = new BizSpelling();
		$configs = $bizSpelling->getConfiguredSpellingForPublication( null ); // get all
		if( isset($configs[$publicationId]) ) {
			$dicts = array();
			foreach( $configs[$publicationId] as $dictName => $dictConfig ) {
				$dict = new Dictionary();
				$dict->Name        = $dictName;
				$dict->Language    = $dictConfig['language'];
				$dict->DocLanguage = $dictConfig['doclanguage'];
				$dict->WordChars   = $dictConfig['wordchars'];
				$dicts[] = $dict;
			}
		}
		return $dicts;
	}
	
	/**
	 * Returns the publication's dictionary configured for the requested Server Plug-in.
 	 * $showInstalledOnly is a boolean to determine the below:
	 * TRUE: Returns configured spelling that is already installed.
	 * FALSE: Returns all configured spelling regardless of whether it is installed or not.
	 * Refer to getConfiguredSpellingForPublication() function header for more explanation.
	 *
	 * @param integer $publicationId Brand DB id. Zero to get system-wide config.
	 * @param string $language Language in [llCC] format (l = language code, C = county code). Null for all.
	 * @param string $pluginName Internal name of the Server Plug-in with a Spelling connector. Null for all.
	 * @param boolean $oneOnly Stop searching when one config found. FALSE to return all matching configs.
	 * @param boolean $showInstalledOnly Refer to function header.
	 * @return array Found configuration settings at the ENTERPRISE_SPELLING option.
	 * @throws BizException when no configuration was found.
	 */
	public function getConfiguredSpelling( $publicationId, $language, $pluginName, $oneOnly, $showInstalledOnly = true )
	{
		$retDicts = array();
		$configs = $this->getConfiguredSpellingForPublication( $publicationId, $showInstalledOnly );
		if( $configs ) foreach( $configs as $pubId => $pubConfig ) {
			if( $pubConfig ) foreach( $pubConfig as $lang => $langConfig ) {
				if( (!$pluginName || $langConfig['serverplugin'] == $pluginName) &&
					(!$language || $langConfig['language'] == $language) ) {
					$retDicts[$pubId][$lang] = $langConfig;
					if( $oneOnly ) {
						break; // found
					}
				}
			}
		}
		if( count($retDicts) == 0 ) {
			if( !$publicationId ) {
				$publicationId = '0';
			}
			$details = 'for brand (id='.$publicationId.')';
			if( $pluginName ) {
				$details .= ' and Server Plug-in "'.$pluginName.'"';
			}
			if( $language ) {
				$details .= ' and language "'.$language.'"';
			}
			throw new BizException( null, 'Server', 
				'Could not find a spelling configuration at the ENTERPRISE_SPELLING option '.$details.'.',
				'Configuration error' );
		}
		return $retDicts;
	}
	
	/**
	 * Validates through spelling configurations defined in configserver.php.
	 * The options checked are:
	 * 1) 'language', 'serverplugin', 'location', 'dictionaries' and 'suggestions' are defined and set.
	 * 2) 'suggestions' should not be more than five.
	 * 3) 'serverplugin' name should end with 'Spelling'; E.g. 'PhpSpelling', 'ShellSpelling', 'WebSpelling'
	 * 4) 'location' is set correctly if the spelling is ShellSpelling.
	 * 5) 'dictionaries' being set are installed in the server.
	 *
	 * @param string $pluginName The name of the plugin that to be validated.
	 * @param boolean $showInstalledOnly Refer to getConfiguredSpellingForPublication() function header.
	 * @throws BizException if any of the above fail.
	 * 
	 */
	public function validateSpellingConfiguration( $pluginName, $showInstalledOnly = true )
	{
		if( !defined( 'ENTERPRISE_SPELLING' ) ) {
			throw new BizException( null, 'Server', 
				'The ENTERPRISE_SPELLING option is missing at configserver.php file.',
				'Configuration error' );
		}
		
		$found = false;
		$help = 'Please check the ENTERPRISE_SPELLING option at your configserver.php file.';
		$options = array( 'language', 'serverplugin', 'location', 'dictionaries', 'suggestions' );
		$configs = $this->getConfiguredSpelling( null, null, $pluginName, false, $showInstalledOnly );
		foreach( $configs as $publicationId => $pubConfig ) {
			foreach( $pubConfig as $language => $langConfig ) {
				$help = 'Please check the ENTERPRISE_SPELLING option at your configserver.php file.';
				foreach( $options as $option ) {
					if( !array_key_exists( $option, $langConfig ) ) {
						throw new BizException( null, 'Server', 
							'The "'.$option.'" setting is missing at the ENTERPRISE_SPELLING option '.
							'for brand id = '.$publicationId.' and language '.$language.'. '.$help, 
							'Configuration error' );
					}
				}
				// Due to CS display limitations, configured 'sugestions' should not be more than 10
				if( $langConfig['suggestions'] > 10 ){
					throw new BizException( null, 'Server', 
							'The [suggestions] is set to (' . $langConfig['suggestions'].') at ENTERPRISE_SPELLING option '.
							'for brand id = '.$publicationId.' and language '.$language.'. It should be less than or equal to 10. '.$help, 
							'Configuration error' );
				}
				
				if( $pluginName == $langConfig['serverplugin'] ) {
					$found = true;
					$last11 = substr( $pluginName, -11 );
					$last13 = substr( $pluginName, -13 );
					if( $last13 != 'ShellSpelling' && $last11 != 'PhpSpelling' && $last11 != 'WebSpelling' ) {
						throw new BizException( null, 'Server', 
							'The spelling connector for Server Plug-in "'.$pluginName.'" should end with '.
							'"ShellSpelling", "PhpSpelling" or "WebSpelling". Please rename.',
							'Configuration error' );
					}
					if( $last13 == 'ShellSpelling' && !file_exists( $langConfig['location'] ) ) {
						throw new BizException( null, 'Server', 
							'The location "'.$langConfig['location'].'" does not exists on disk '.
							'for Server Plug-in "'.$pluginName.'" and brand id "'.$publicationId.'". '.$help,
							'Configuration error' );
					}
					
					// Check for dictionaries
					$dictionaries = $this->getInstalledDictionaries( $pluginName );
					if( !is_null( $dictionaries) ) foreach( $langConfig['dictionaries'] as $configuredDictionary ){
						if( !in_array( $configuredDictionary, $dictionaries ) ){
							if( count( $dictionaries ) == 0 ) {
								$detail = 'There is NO dictionary installed for "'.$pluginName.'" ';
							} else {
								$detail = 'Installed dictionaries for "'.$pluginName.'" are "'.implode( ',', $dictionaries ).'" ';
							}
							$detail .= ', but "'.$configuredDictionary.'" is configured in the configserver.php file. '.
										'Either the dictionary is not installed or it is configured wrongly. '.$help;
							throw new BizException( null, 'Server', $detail, 'Configuration error' );
						}
					}
					
					// Check whether ]'language' defined can be resolved into Doc Language
					if( !isset($langConfig['doclanguage']) ) { // When 'doclanguage' is defined, meaning admin overrules the doc language definition, thus no auto-resolve needed.
						$docLang = $this->getFullNameForLanguageCode( $langConfig['language'] );
						if( is_null( $docLang ) ){
							$detail = 'Could not auto resolve doc language with the defined [language] = \''. $langConfig['language'].'\'.'.
										'Make sure there is no typo for [language] defined in configserver.php OR ' .
										'if it is not a typo, try choosing another [language] OR ' .
										'if all failed, kindly contact WoodWing for the [language] required to be added into doc language list.';
							throw new BizException( null, 'Server', $detail, 'Configuration error' );
						}
					}	
				}
			}
		}

		if( !$found ) {
			throw new BizException( null, 'Server', 
				'Could not find a spelling configuration for Server Plug-in "'.$pluginName.'". '.$help,
				'Configuration error' );
		}
	}
	
	/**
	 * Gives the InCopy language tag for a given language code.
	 * See getLanguageCodesTable function for details.
	 *
	 * @param string $code Language code in "llCC" format (l = language code, C = county code).
	 * @return string Language tag to be used in InCopy document.
	 */
	private function getFullNameForLanguageCode( $code )
	{
		static $langCodes = null;
		if( !$langCodes ) {
			$langCodes = getLanguageCodesTable();
		}
		return $langCodes[$code];
	}
}
