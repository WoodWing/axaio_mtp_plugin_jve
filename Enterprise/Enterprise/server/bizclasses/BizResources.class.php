<?php
/**
 * Business class which manages localization of resource strings as shown in GUI.
 *
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizResources
{
	static private $pubChanIdsPerType = null;
	
	/**
	 * Returns the table of configured terms in {@link: getUiTerms()} at configlang.php.
	 * Those terms are typically customized when customer wants different names for pub/iss/sec etc
	 * Instead or returning objects, this function returns an array of key-value pairs.
	 * This format matches the resource table return from {@link: getResourceTable()}
	 *
	 * @return array Key-value pairs of configured resource strings.
	 */
	public static function getConfigTerms()
	{
		static $configTerms;
		if( !isset( $configTerms ) ) {
			$uiTermObjs = getUiTerms();
			$uiTerms = array();
			foreach( $uiTermObjs as $uiTerm ) {
				$uiTerms[$uiTerm->Term] = $uiTerm->Translation;
			}
			$configTerms = array_change_key_case( $uiTerms, CASE_UPPER ); // terms are lower, config keys are upper
		}
		return $configTerms;
	}
	
	/**
	 * Returns a localized term from the resource string table. 
	 * It prefers the configured terms from {@link: getUiTerms()}. 
	 * When not found, it takes the term from resource table. 
	 * When both not found it returns error string instead. 
	 * The configured terms lookup can be optionally supressed. 
	 *
	 * When resource string contains parameters indicated with %1, %2, etc 
	 * they can be filled in automatically passing params array. 
	 * Params can even contain keys that needs to be localized! 
	 * Example: BizResources::localize( 'ERR_NO_SUBJECTS_FOUND', true, array( '{OBJECTS}' ) );
	 *
	 * @param $reqKey string  The key of the term to be localized.
	 * @param $full   boolean Whether or not to take configured terms into account.
	 * @param $params array   List of parameters to fill in resource string during localization.
	 * @return string         The localized term.
	 */
	public static function localize( $reqKey, $full=true, $params=null ) // text id without prefix, e.g. "PUBLICATION"
	{
		// Get resource tables defined by core server and configlang.php.
		$allTerms = &self::getResourceTable();
		$uiTerms = $full ? self::getConfigTerms() : array();
		
		// See if resource key can be localized.
		$localized = '';
		if( isset($uiTerms[$reqKey] ) ) {
			$localized = $uiTerms[$reqKey];
		} else if( isset( $allTerms[$reqKey] ) ) { // performance: don't use array_key_exists
			$localized = $allTerms[$reqKey];
		}
		
		// If resource key not found, try grabbing resources from custom server plugins.
		if( !$localized ) {
		
			// Resource keys provided by server plugins have this format: "<serverpluginname>.<resourcekeyname>"
			// whereby the <serverpluginname> exactly matches with the internal name of the plugin.
			if( strpos( $reqKey, '.' ) !== false ) {
				list( $pluginName, $key ) = explode( '.', $reqKey, 2 );
				global $sLanguage_code;
				
				// Respect the user's language. If the plugin does not provide a resource table
				// for that language, fallback to English.
				require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
				$langCode = BizUser::validUserLanguage( $sLanguage_code );
				$resFile = BASEDIR.'/config/plugins/'.$pluginName.'/resources/'.$langCode.'.xml';
				$resFileFound = file_exists( $resFile );
				if ( !$resFileFound ) {
					$resFile = BASEDIR.'/server/plugins/'.$pluginName.'/resources/'.$langCode.'.xml';
					$resFileFound = file_exists( $resFile );
				}
				if( !$resFileFound && $langCode != 'enUS' ) {
					$langCode = 'enUS';
					$resFile = BASEDIR.'/config/plugins/'.$pluginName.'/resources/'.$langCode.'.xml';
					$resFileFound = file_exists( $resFile );
					if ( !$resFileFound ) {
						$resFile = BASEDIR.'/server/plugins/'.$pluginName.'/resources/'.$langCode.'.xml';
						$resFileFound = file_exists( $resFile );
					}
				}
				if( $resFileFound ) {
					$resourceTable = self::readResourceTable( $resFile );
					$allTerms += $resourceTable;
					self::resolveUITerms( $allTerms );
					$localized = $allTerms[$reqKey];
				}
			}
		}
		
		// Error when key was not found in any of the resource tables.
		if( !$localized ) {
			global $sLanguage_code;
			$err = 'PROGRAM ERROR: NO TEXT FOR '.$reqKey.' IN LANGUAGE '.$sLanguage_code;
			LogHandler::Log( 'resources', 'ERROR', $err );
			return $err;
		}

		// Optionally fill in parameters: %1, %2, etc
		if( !is_null( $params ) ) {
			$i = 0;
			foreach( $params as $param ) {
				$i++;
				$localized = str_replace( '%'.$i, $param, $localized );
				self::resolveVariable( $allTerms, $uiTerms, $localized );
			}
		}
		return $localized;
	}
	
	/**
	 * Returns the localized dictionary of all terms in the user's language. 
	 * On the first call of session, the dictionary is read from file system, which is cached then. 
	 * For all calls after that, the cached dictionary is returned directly. 
	 *
	 * @param bool $reload
	 * @return array  Dictionary with all keys and their localized representations (all strings).
	 */
	public static function &getResourceTable( $reload = false )
	{
		static $lastUserLang = '';
		global $sLanguage_code;
		static $resourceTable = array();

		// Only reload if user has changed the language.
		$reload = ($reload === true && $lastUserLang != $sLanguage_code );
		if( $reload ) {
			$resourceTable = array(); // Delete memory cached resource table.
		}

		// For the first call of this session, the resource table need to be populated and cached in memory.
		if( count($resourceTable) == 0 ) {

			// Get the user language and the file location of the XML resource file.
			require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
			$sLanguage_code = BizUser::validUserLanguage( $sLanguage_code );
			$lastUserLang = $sLanguage_code;
			$resFile = self::getFileName( $sLanguage_code );

			// Read resource table from file cache. Skipped for first time use after installation only.
			// Since 10.0.0 the file cache implementation based on Zend_Cache is replaced with the home brewed
			// implementation below, which is 3x faster.
			$esVersion = str_replace( array(' ', '.'), '_', SERVERVERSION );

			//sys_get_temp_dir is unreliable in adding a trailing slash, so we need to check for this before using it.
			$tmpDir = sys_get_temp_dir();
			if( substr($tmpDir, -1) != DIRECTORY_SEPARATOR ) {
				$tmpDir .= DIRECTORY_SEPARATOR;
			}
			$cacheFile = $tmpDir.'ww_ent_server_' . $esVersion . '_BizResources_' . filemtime($resFile) . '_' . $sLanguage_code;
			if( file_exists( $cacheFile ) ) {
				LogHandler::Log( __CLASS__, 'DEBUG', 'Reading resource table from file cache '.$cacheFile );
				$resourceTable = unserialize( file_get_contents( $cacheFile ) );
			}

			// Read resource table from installed XML file. Happens for first time use after installation only.
			if( count( $resourceTable ) == 0 ) {
				LogHandler::Log( __CLASS__, 'DEBUG', 'Reading resource table from XML file ' . $resFile );
				$resourceTable = self::readResourceTable( $resFile );
				// Resolve UI terms here and not in readResourceTable itself because of round tripping
				// static $resourceTable is now filled so we won't be here again.
				self::resolveUITerms( $resourceTable );
			}

			// Write resource table in file cache. Happens for first time use after installation only.
			if( !file_exists( $cacheFile ) ) {
				LogHandler::Log( __CLASS__, 'DEBUG', 'Writing resource table into file cache '.$cacheFile );
				file_put_contents( $cacheFile, serialize( $resourceTable ) );
			}
		}

		// Return the memory cached resource table to caller.
		return $resourceTable;
	}
	
	/**
	 * Resolve all variables beteen {} in the resource table to their values.
	 * e.g. {ISSUE} will become Issue
	 *
	 * @param array $resourceTable key value pairs
	 */
	private static function resolveUITerms( &$resourceTable )
	{
		$uiTerms = self::getConfigTerms();
		foreach( $resourceTable as /*$key => */&$localized ) {
			if( strpos( $localized, '{' ) !== false ) {
				self::resolveVariable( $resourceTable, $uiTerms, $localized );
			}
			// * Performance improvement: The strpos check avoids preg_match_all usage inside resolveVariable() 
			// which makes readResourceTable() 6x faster! At the whole, the logon becomes 1 second faster.
			// Also, many web/admin pages respond a lot faster.
		}
	}
	
	/**
	 * Reads and parses the given string resources file in XML format 
	 * and returns a dictionary of key-value pairs read from file. 
	 *
	 * @param $file string  The full path name of the resource file.
	 * @return array        Dictionary with all keys and their localized representations (all strings).
	 */
	private static function readResourceTable( $file )
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
					LogHandler::Log( 'resources', 'ERROR', 'Duplicate resource key "'.$key.'" while reading file "'.$file.'"' );
				}
				$retDict[$key] = $term->childNodes->item( 0 )->nodeValue;
			}
		}
		// Don't resolve variables here, because it needs getConfigTerms which calls BizResources::localize
		// via configlang.php and reads the resource file again.
		/*
		// Now we have the complete table, let's resolve variables like "Current {ISSUE}" into "Current Issue"
		$uiTerms = self::getConfigTerms();
		foreach( $retDict as $key => &$localized ) {
			if( strpos( $localized, '{' ) !== false ) { // *
				self::resolveVariable( $retDict, $uiTerms, $localized );
			}
			// * Performance improvement: This check avoids preg_match_all usage inside resolveVariable() 
			// which makes readResourceTable() 6x faster! At the whole, the logon becomes 1 second faster.
			// Also, many web/admin pages respond a lot faster.
		}*/
		return $retDict;
	}
	
	/**
	 * Resolves/localizes all variables (such as {ISSUE}) at the given term ($localized).
	 *
	 * @param $allTerms  array  Dictionary with all keys and their localized representations (all strings).
	 * @param $uiTerms   array  Key-value pairs of configured resource strings.
	 * @param $localized string [IN/OUT] Term that might hold variables to be resolved.
	 */
	private static function resolveVariable( $allTerms, $uiTerms, &$localized )
	{
		$locKeys = array();
		if( preg_match_all( '/{([a-zA-Z0-9_\.]*)}/', $localized, $locKeys ) ) {
			$locKeys = array_unique( $locKeys[1] );
			foreach( $locKeys as $locKey ) {
				if( array_key_exists( $locKey, $uiTerms ) ) {
					$locPar = $uiTerms[$locKey];
				} else if( array_key_exists( $locKey, $allTerms ) ) {
					$locPar = $allTerms[$locKey];
				} else {
					global $sLanguage_code;
					$err = 'PROGRAM ERROR: NO TEXT FOR '.$locKey.' IN LANGUAGE '.$sLanguage_code;
					LogHandler::Log( 'resources', 'ERROR', $err );
					$locPar = $err;
				}
				$localized = str_replace('{'.$locKey.'}', $locPar, $localized );
			}
		}
	}
	
	/**
	 * Returns the full path of the XML resource file which contains all key-values 
	 * used for localization of terms displayed in GUI. 
	 * The resource table file path depends on given language: config/resources/<language>.xml 
	 *
	 * @param $sLanguageCode string   The user's language (using Adobe's language abbreviation).
	 * @return string                 The full path to the resource file in Unix notation (foreward slashes).
	 */
	private static function getFileName( $sLanguageCode )
	{
		return BASEDIR.'/config/resources/'.$sLanguageCode.'.xml';
	}

	/**
	 * Returns all languages that are shipped with the Enterprise system. 
	 *
	 * @return array List of languages in Adobe's abbrivated notation, e.g. enUS, deDE, etc
	 */
	public static function getLanguageCodes()
	{
		$langs = array('csCZ', 'deDE', 'enUS', 'esES', 'frFR', 'itIT', 'jaJP', 'koKR', 
					'nlNL', 'plPL', 'ptBR', 'ruRU', 'zhCN', 'zhTW', 'fiFI' );
		return array_combine( $langs, $langs ); // key/values are the same
		
		/* // COMMENTED OUT: Taking 40ms to scan folder, which is far too expensive!
		$masterDir =  opendir( BASEDIR.'/config/resources' );
		$aLanguageCodes = array(); 
		// Search in resource folder for language files with name length 4 and xml extension
		while($langDir = readdir($masterDir)) {	
			$fullName = BASEDIR.'/config/resources/'.$langDir;
			if (is_file($fullName) ) {
				$baseName = basename( $fullName, '.xml' );
				if( mb_strlen($baseName) == 4)  {	
					$aLanguageCodes[$baseName] = $baseName;
				}
			}
		}
		closedir($masterDir);
		return $aLanguageCodes;*/
	}

	/**
	 * Reads icons from Enterprise/config/images/objecticons folder that represent objects listed at search results.
	 *
	 * @param array $iconMetrics List of IconMetric typed strings. Supported are: '16x16', '32x32' and '48x48'.
	 * @return array Two-dimensional array [object type][file format] of ObjectIcon icons with icon attachments.
	 */
	public static function getObjectIcons( array $iconMetrics )
	{
		require_once BASEDIR.'/server/appservices/DataClasses.php'; // ObjectIcon
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';

		$retIcons = array();
		if( empty($iconMetrics) ) {
			$iconMetrics = array('16x16');
		}
		sort($iconMetrics);
		$extensionMap = MimeTypeHandler::getExtensionMap();
		foreach( $extensionMap as $values ) {
			self::getObjectIconsWithAttachments( $retIcons, $iconMetrics, $values[1], $values[0] );
		}
		$objTypes = array_keys( getObjectTypeMap() );
		foreach( $objTypes as $objType ) {
			self::getObjectIconsWithAttachments( $retIcons, $iconMetrics, $objType, null );
		}
		return $retIcons;
	}

	/**
	 * Reads icons from Enterprise/config/images/objecticons folder that represent objects listed at search results.
	 *
	 * @param array $retIcons Two-dimensional array [object type][file format] of ObjectIcon icons with icon attachments.
	 * @param array $iconMetrics List of IconMetric typed strings. Supported are: '16x16', '32x32' and '48x48'.
	 * @param string $objType Object Type
	 * @param string $mimeType File Format
	 */
	private static function getObjectIconsWithAttachments( array &$retIcons, array $iconMetrics, $objType, $mimeType )
	{
		if( !isset( $retIcons[$objType][$mimeType] ) ) {
			$mimeTypeUndr = str_replace( '/', '_', $mimeType );
			// search for most explict one; file format + object type
			$fileNameExpr = BASEDIR.'/config/images/objecticons/'.$objType.'/'.$mimeTypeUndr.'_%s.png';
			$fileName = sprintf( $fileNameExpr, '16x16' ); // no matter requested, 16x16 is leading!
			// when not found, try more global one; object type only
			if( !file_exists( $fileName ) ) { 
				$fileNameExpr = BASEDIR.'/config/images/objecticons/'.$objType.'_%s.png';
				$fileName = sprintf( $fileNameExpr, '16x16' ); // no matter requested, 16x16 is leading!
				$mimeType = ''; // clear !
			}
			// build structure to return caller
			if( file_exists( $fileName ) ) {
				if( !isset( $retIcons[$objType][$mimeType] )) { // Create new ObjIcon when it is not created before
					require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
					$attachments = array();
					$objIcon = new ObjectIcon();
					$objIcon->ObjectType = $objType;
					$objIcon->Format = empty($mimeType) ? null : $mimeType;
					foreach( $iconMetrics as $iconMetric ) {
						$fileName = sprintf( $fileNameExpr, $iconMetric );
						if( file_exists( $fileName ) ) {
							$attachment = new Attachment($iconMetric, 'image/png');
							$transferServer = new BizTransferServer();
							$transferServer->copyToFileTransferServer($fileName, $attachment);
							$attachments[] = $attachment;
						}
					}
					$objIcon->Attachments = $attachments;
					$retIcons[$objType][$mimeType] = $objIcon;
				}
			}
		}
	}

	/**
	 * This function reads icons from icon folder that represent publication channels.
	 * Those icons are to be shown by clients in their UI wherever channels are listed.
	 * 
	 * 1. First it reads icon files from the config/images/pubchannelicons folder that have 
	 * the following file name convention: 
	 *    <PubChannelType>_NNxNN.png
	 * The NNxNN are metrics. Supported are 16x16, 24x24, 32x32 and 48x48. Note that the
	 * PNG format is supported only. The options to use for the <PubChannelType> can be found 
	 * in the SCEnterprise.wsdl which are 'print', 'web', 'sms', etc.
	 * The function reads all channels (system wide) from the DB that are configured and  
	 * checks their types and picks the configured icon.
	 *
	 * 2. The icons read from step 1 can be overruled by publish connectors providing their 
	 * own specific icons, such as done by the Twitter plugin. Those icons are located at 
	 * the plugin folder itself in its 'pubchannelicons' subfolder. For performance reasons
	 * the Publish connector must return true in its hasPubChannelIcons function to tell
	 * such folder is present. The core server (this function) reads icon files that have 
	 * the following file name convention from the plugin's pubchannelicons folder:
	 *    NNxNN.png
	 *
	 * 3. Icons read from steps 1 and 2 can be overruled by customers. The system admin user 
	 * can click on the Publication Channel Maintenance page to find out the <PubChannelId> 
	 * which is a numeric value that needs to be put in the file name shown below. Then he/she 
	 * creates their own icon files and stores them in the config/images/pubchannelicons
	 * using the following file name convention:
	 *    <PubChannelId>_NNxNN.png
	 *
	 * It could be the case that for a certain channel no icon is configured at all.
	 * Therefore clients should fallback to the icon that has the PubChannelId set to zero.
	 * These icon files are read from the config/images/pubchannelicons folder and have
	 * the following file name convention:
	 *    0_NNxNN.png
	 *
	 * Once all icons are collected in the steps above, this function returns the icons in
	 * the requested metrics (when available on disk) and groups them per channel.
	 *
	 * @param array $iconMetrics List of IconMetric typed strings. Supported are: '16x16', '32x32' and '48x48'.
	 * @return array of PubChannelIcon (key=chanId, value=PubChannelIcon) with icon attachments.
	 */
	public static function getPubChannelIcons( array $iconMetrics )
	{
		require_once BASEDIR.'/server/appservices/DataClasses.php'; // PubChannelIcon
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		// When caller / client does not request any metrics, at least provide 16x16 icons.
		if( empty($iconMetrics) ) {
			$iconMetrics = array('16x16');
		}
		
		// Collect all channels (system wide) and build type-id map for easy lookup later.
		$pubChannels = DBAdmPubChannel::listPubChannelsObj();
		self::$pubChanIdsPerType = array();
		if( $pubChannels ) foreach( $pubChannels as $pubChannel ) {
			self::$pubChanIdsPerType[$pubChannel->Type][] = $pubChannel->Id;
		}
		
		// Read the icon files from the folders as explained in the function header.
		// While running through the steps, accumulate all the icon files so that the
		// icons found in the next step overwrite (overrule) the icons collected in
		// previous steps...
		
		// Step 1:
		$filesPerChannelId = array();
		self::collectPubChannelIconFiles( BASEDIR.'/config/images/pubchannelicons', 
											$iconMetrics, 'type', null, $filesPerChannelId );

		// Step 2:
		if( $pubChannels ) foreach( $pubChannels as $pubChannel ) {
			$hasIcons = BizServerPlugin::runChannelConnector( $pubChannel->Id, 'hasPubChannelIcons', array(), false );
			if( $hasIcons ) {
				$pluginFolder = BizServerPlugin::getPluginFolderForChannelId( $pubChannel->Id, false );
				$iconFolder = $pluginFolder.'/pubchannelicons';
				self::collectPubChannelIconFiles( $iconFolder, $iconMetrics, 'none', 
													$pubChannel->Id, $filesPerChannelId );
			}
		}

		// Step 3:
		self::collectPubChannelIconFiles( BASEDIR.'/config/images/pubchannelicons', 
											$iconMetrics, 'id', null, $filesPerChannelId );
		
		// Upload the icon files to the Transfer Folder and build structure of
		// PubChannelIcon and Attachment data classes to return caller / client.
		return self::buildPubChannelIconAttachments( $filesPerChannelId );
	}
	
	private static function collectPubChannelIconFiles( $iconFolder, array $iconMetrics, $reqMode, $chanId, &$filesPerChannelId )
	{
		$masterDir =  opendir( $iconFolder );
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		while( ($iconFile = readdir($masterDir)) ) {	
			$fileName = $iconFolder.'/'.$iconFile;
			if( is_file($fileName) ) {

				// Parse file name. Note that icon files have the following syntax:
				//     [ channel id | channel type ]_NNxNN.png
				$iconMetric = substr($fileName,-9,5);
				$fileExt = substr($fileName,-4);
				
				// Only take into account this icon file when caller requested for its 
				// metrics and the file format is known to us.
				if( in_array($iconMetric, $iconMetrics) && $fileExt == '.png' ) {
				
					// The channel id or type could be in the icon file name.
					// Ids can be taken into account, but types are only taken into account
					// when there are channels configured with matching type (optimization).
					$pubChanIds = null;
					switch( $reqMode ) {
						case 'id':
							$pubChanId = basename( $fileName, '_'.$iconMetric.$fileExt );
							if( is_numeric($pubChanId) ) {
								$pubChanIds = array( $pubChanId );
							}
							break;
						case 'type': // string: assume channel type
							$pubChanType = basename( $fileName, '_'.$iconMetric.$fileExt );
							if( !is_numeric($pubChanType) ) {
								if( array_key_exists( $pubChanType, self::$pubChanIdsPerType ) ) {
									$pubChanIds = self::$pubChanIdsPerType[$pubChanType]; // resolve ids from type
								}
							}
							break;
						case 'none':
							$pubChanIds = array( $chanId );
							break;
					}
					if( $pubChanIds ) foreach( $pubChanIds as $pubChanId ) {
						$filesPerChannelId[$pubChanId][$iconMetric] = $fileName;
					}

				} // else ignore: unknown file name convention
			} // else ignore: no file
		} // end while
		closedir($masterDir);
	}

	/**
	 * Build an Attachment for each icon file and collect them per
	 * pub channel (id) in a PubChannelIcon data class to return to caller.
	 *
	 * @param array $filesPerChannelId Two-dim array: [ pub channel id ][ icon metric ] => icon file name
	 * @return array of PubChannelIcon
	 */
	private static function buildPubChannelIconAttachments( $filesPerChannelId )
	{
		$retIcons = array();
		if( $filesPerChannelId ) foreach( $filesPerChannelId as $pubChanId => $metricFile )
			foreach( $metricFile as $iconMetric => $fileName ) {
			if( file_exists( $fileName ) ) {
				if( isset($retIcons[$pubChanId]) ) {
					$chanIcon = $retIcons[$pubChanId];
				} else {
					$chanIcon = new PubChannelIcon();
					$chanIcon->Id = $pubChanId;
					$retIcons[$pubChanId] = $chanIcon;
				}
				$attachment = new Attachment($iconMetric, 'image/png');					
				$transferServer = new BizTransferServer();
				$transferServer->copyToFileTransferServer($fileName , $attachment);
				$chanIcon->Attachments[] = $attachment;
			}
		}
		return $retIcons;
	}
}