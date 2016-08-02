<?php
/**
 * Solr TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/config/config_solr.php';
require_once dirname(__FILE__) . '/../../SolariumClient.class.php';

class WW_TestSuite_HealthCheck2_Solr_TestCase extends TestCase
{

	public function getDisplayName() { return 'Solr Search Server'; }
	public function getTestGoals()   { return 'Checks if the Solr search integration has been configured correctly.'; }
	public function getTestMethods() { return 'Checks if options at config_solr.php file are correctly set.'; }
	public function getPrio()        { return 23; }

	private $solariumClient = null;

	final public function runTest()
	{
		if( !$this->checkInstalled() ) {
			return;
		}

		if( !$this->checkServerURLSettings() ) {
			return;
		}

		if( !$this->checkSolrTimeOut() ) {
			return;
		}

		$solrConfigName = '';
		$solrSchemaName = '';

		$this->solariumClient = new SolariumClient();

		if ( !$this->checkSolrConnection() ) {
			// Connection might fail because we are still using Solr 3 or because the core name is invalid
			// Check these settings before returning a "Could not connect" error
			if( !$this->checkVersion( true ) ) {
				return;
			}
			if ( !$this->checkSolrCoreConfiguration( $solrConfigName, $solrSchemaName, true ) ) {
				return;
			}

			$this->setResult( 'ERROR', 'Could not connect to Solr server.',
				'Check if Solr is running and check the SOLR_SERVER_URL setting in the config_solr.php file.' );
			return;
		}

		if( !$this->checkVersion() ) {
			return;
		}

		// Can connect to Solr, so core configuration name must be valid
		// Still run core config check to get the solr config and schema filenames
		if ( !$this->checkSolrCoreConfiguration( $solrConfigName, $solrSchemaName ) ) {
			return;
		}

		if( !$this->checkObsoletedSettings() ) {
			return;
		}

		if ( !$this->checkSolrConfigInfo( $solrConfigName ) ) {
			return;
		}

		 if (!$this->checkUTF8GetRequest()) {
			 return;
		 }

		 $schemaInfo = array();
		 if( !$this->getSchemaInfo( $solrSchemaName, $schemaInfo ) ) {
			 return;
		 }

		 // Test the schema info version.
		 if ( !$this->checkSchemaVersion( $schemaInfo ) ) {
			 return;
		 }

		 // Test various schema configuration settings
		 $this->checkIndexFields($schemaInfo);
		 $this->checkMandatoryFields($schemaInfo);
		 $this->checkUniqueKey($schemaInfo);
		 $this->checkFacetFields($schemaInfo);
		 $this->checkCatchAllField($schemaInfo);
		 $this->checkNGramSize($schemaInfo);

		 require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		 $objCnt = DBObject::countObjectsToIndex( true );
		 if( $objCnt > 0 ) {
			 $this->setResult( 'WARN', $objCnt.' objects have not been indexed yet and will therefore not appear in the search results.',
				 'Click <a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/searchindexing.php">here</a> to start indexing from the Search Server Maintenance page.' );
		 }

		 LogHandler::Log('wwtest', 'INFO', 'Solr configuration has been tested');
	}

	/**
	 * Checks if the Solr Search server plug-in is installed and enabled.
	 *
	 * @return boolean True when 'good enough'. False when is makes no sense to continue testing.
	 */
	private function checkInstalled()
	{
		// Check if Solr server plug-in is installed and enabled
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$retVal = true;
		$pluginObj = BizServerPlugin::getPluginForConnector( 'SolrSearch_Search' );
		if( $pluginObj && $pluginObj->IsInstalled ) {
			if( !$pluginObj->IsActive ) {
				$this->setResult('ERROR', 'The Solr Search server plug-in is disabled.',
					'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>');
				$retVal = false; // no plugin so stop here to avoid other configuration errors.
			}
		} else {
			$this->setResult('ERROR', 'The Solr Search server plug-in is not installed.',
				'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>');
			$retVal = false; // no plugin so stop here to avoid other configuration errors.
		}
		return $retVal;
	}

	/**
	 * Checks if there are still some obsoleted defines and settings made at the config file or DB.
	 *
	 * @return boolean True when 'good enough'. False when is makes no sense to continue testing.
	 */
	private function checkObsoletedSettings()
	{
		// Check obsoleted defines
		$defines = array( 'SOLR_INSTALLED', // now checking server plug-in instead
			'SOLR_SERVER', 'SOLR_PORT', 'SOLR_PATH',  // use SOLR_SERVER_URL instead
			'SOLR_SYNCHRONOUS_INDEX', 'SOLR_USER', 'SOLR_PASSWORD', 'SOLR_INDEX_MAXDOCS', // synchron only!
			'SOLR_MLT_GENERAL_FIELDS', 'SOLR_HL_GENERAL_FIELDS' ); // No longer supported
		foreach( $defines as $define ) {
			if( defined($define) ) {
				$this->setResult( 'WARN', 'The '.$define.' option is no longer supported.',
					'Remove the option from your config_solr.php file.' );
				// continue
			}
		}

		// Check obsolete options at SERVERFEATURES
		if( BizSettings::isFeatureEnabled( 'HotInbox' ) ) {
			$this->setResult( 'WARN', 'The "HotInbox" option at SERVERFEATURES setting is no longer supported.',
				'Remove the option from your configserver.php file.' );
				// continue
		}

		// Check if the obsoleted Inbox or Name Search queries are still in DB
		require_once BASEDIR.'/server/dbclasses/DBQuery.class.php';
		$dbdriver = DBDriverFactory::gen();
		$sth = DBQuery::getNamedQueries( 'Inbox' );
		if( $dbdriver->fetch($sth) ) {
			$this->setResult( 'WARN', 'The Named Query "Inbox" is found at your database, which is no longer supported.',
				'Remove the query from your database.' );
				// continue
		}
		$sth = DBQuery::getNamedQueries( 'Name Search' );
		if( $dbdriver->fetch($sth) ) {
			$this->setResult( 'WARN', 'The Named Query "Name Search" is found at your database, which is no longer supported.',
				'Remove the query from your database.' );
				// continue
		}

		return true;
	}

	/**
	 * Checks if the SOLR_SERVER_URL at the config_solr.php file is set correct.
	 *
	 * @return boolean True when 'good enough'. False when is makes no sense to continue testing.
	 */
	private function checkServerURLSettings()
	{
		if( !defined('SOLR_SERVER_URL') || !($tmp = SOLR_SERVER_URL) || empty( $tmp ) ) {
			$this->setResult( 'ERROR', 'Solr server URL is not configured.', 'Check the SOLR_SERVER_URL option in the config_solr.php file.' );
			return false;
		}

		if( trim(SOLR_SERVER_URL) != SOLR_SERVER_URL ) {
			$this->setResult( 'ERROR', 'Solr server URL has beginning or ending spaces which are not allowed.',
				'Check the SOLR_SERVER_URL option in the config_solr.php file.' );
			return false;
		}

		if( substr(SOLR_SERVER_URL, -1, 1) == '/' ) {
			$this->setResult( 'ERROR', 'Solr server URL has ending slash "/" which is not allowed.',
				'Check the SOLR_SERVER_URL option in the config_solr.php file.' );
			return false;
		}

		$urlParts = parse_url( SOLR_SERVER_URL );
		if( $urlParts === false ) {
			$this->setResult( 'ERROR', 'Solr server URL seems to be malformed.',
				'Check the SOLR_SERVER_URL option in the config_solr.php file.' );
			return false;
		}

		return true;
	}

	/**
	 * Checks if the SOLR_TIMEOUT at the config_solr.php file is set correct.
	 *
	 * @return boolean True when 'good enough'. False when is makes no sense to continue testing.
	 */
	private function checkSolrTimeOut()
	{
		if( !defined('SOLR_TIMEOUT') || !($tmp = SOLR_TIMEOUT) || empty( $tmp ) ) {
			$this->setResult( 'ERROR', 'Solr timeout is not configured.', 'Check the SOLR_TIMEOUT option in the config_solr.php file.' );
			return false;
		}

		// In case of curl adapter, curl_setopt is used to set the timeout: http://nl3.php.net/curl_setopt
		// According to the documentation the timeout should be an integer and 0 is used to wait "indefinitely"
		if( !is_int( SOLR_TIMEOUT ) ) {
			$this->setResult( 'ERROR', 'Solr timeout is not a valid number.', 'Check the SOLR_TIMEOUT option in the config_solr.php file.' );
			return false;
		}

		return true;
	}

	/**
	 * Checks if SOLR_CORE at the config_solr.php file is set correct and exists.
	 *
	 * @param string $solrConfigName Set to the used solr config xml filename upon success
	 * @param string $solrSchemaName Set to the used schema xml filename upon success
	 * @param bool $ignoreFailRetrieve Does not set an error message if it failed to retrieve the version
	 * @return boolean True when 'good enough'. False when is makes no sense to continue testing.
	 */
	private function checkSolrCoreConfiguration( &$solrConfigName, &$solrSchemaName, $ignoreFailRetrieve = false )
	{
		// Check if the SOLR_CORE setting is present in the config_solr.php file.
		if ( !defined( 'SOLR_CORE' ) ) {
			$this->setResult( 'ERROR', 'Solr Core is not configured.', 'Check the SOLR_CORE option in the config_solr.php file.' );
			return false;
		}

		// Check for spaces in the core setting.
		if( trim(SOLR_CORE) != SOLR_CORE ) {
			$this->setResult( 'ERROR', 'Solr Core has beginning or ending spaces which are not allowed.',
				'Check the SOLR_CORE option in the config_solr.php file.' );
			return false;
		}

		// Check if the core exists, and is up and running.
		$url = SOLR_SERVER_URL . '/admin/cores?action=STATUS';

		$config = file_get_contents($url);

		// Retrieve the Solr version number.
		try{
			$xml = new SimpleXMLElement($config);

			$coreIsActive = $xml->xpath('/response/lst[@name="status"]/lst[@name="'.SOLR_CORE.'"]');
			if ( !$coreIsActive ) {
				// An error occured, the specified core in the config_solr.php file cannot be found, determine which
				// cores ARE available and give them as a hint to the end user.
				$cores = '';
				$availableCores = $xml->xpath('/response/lst[@name="status"]/lst/str[@name="name"]');

				foreach ($availableCores as $core) {
					if (!empty($cores)) {
						$cores .= ', ';
					}
					$cores .= $core;
				}
				$corestip = 'no available cores found';
				if( !empty($cores) ) {
					$corestip = 'available cores: ' . $cores;
				}

				$this->setResult( 'ERROR', 'The Solr Core: `' . SOLR_CORE . '` does not exist, or is not loaded.',
					'Check the SOLR_CORE option in the config_solr.php file, ' . $corestip );
				return false;
			}

			// Store the solr config and schema name, since they can be different from the defaults.
			// These names are used by further tests.
			$configNameElem = $xml->xpath('/response/lst[@name="status"]/lst[@name="'.SOLR_CORE.'"]/str[@name="config"]');
			if( $configNameElem != false )
				$solrConfigName = (string)$configNameElem[0];
			$schemaNameElem = $xml->xpath('/response/lst[@name="status"]/lst[@name="'.SOLR_CORE.'"]/str[@name="schema"]');
			if( $schemaNameElem != false )
				$solrSchemaName = (string)$schemaNameElem[0];

		} catch( Exception $e ) {
			if( !$ignoreFailRetrieve ) {
				$this->setResult( 'ERROR', 'Could not get Solr Core `' . SOLR_CORE . '` status: '.$e->getMessage(),
					'Check if Solr is running and check the SOLR_SERVER_URL setting in the config_solr.php file.' );
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks the connection to the Solr Core.
	 *
	 * Creates a new client to connect to solr and performs a ping to the core.
	 *
	 * @return bool Whether or not the connection was successfully established.
	 */
	private function checkSolrConnection()
	{
		try {
			if( !$this->solariumClient->pingSolrHost() ) {
				return false;
			}
		} catch( Exception $e ) {
			$e = $e; // Keep analyzer happy...
			return false;
		}
		return true;
	}

	/**
	 * This method reads the schema.xml of the solr server.
	 *
	 * It retrieves specific information that is used to check if the set up of
	 * Solr is in line with the settings of config_solr.php.
	 *
	 * @param string $solrSchemaName Core schema file name
	 * @param array $schemaInfo Is filled with the specific information
	 * @return true if schema.xml is read otherwise false.
	 */
	private function getSchemaInfo( $solrSchemaName, &$schemaInfo )
	{
		// If the DOM document could not be loaded return.
		$xmlDoc = $this->getSchemaAsDomDocument( $solrSchemaName );
		if (false == $xmlDoc){
			return false;
		}

		$xmlSchema = $xmlDoc->getElementsByTagName('schema');
		$fieldName = $xmlSchema->item(0)->getAttribute('name');
		if ($fieldName != 'WoodWing') {
			$this->setResult( 'WARN', 'The used Solr '.$solrSchemaName.' file is not the "WoodWing" schema. ' .
				'This typically happens when you did not copy the file shipped with Enterprise Server to the Solr/conf folder. '.
				'Please check the &lt;schema name="WoodWing" version="1.5"&gt; entry in the schema.xml file. ' .
				'Renaming the schema is harmless but not using the delivered schema can cause a lot of errors/warnings '.
				'This can be fixed by copying the Enterprise file to the Solr/conf folder. '.
				'Obviously, your customizations made to the file will then be lost.');
		}

		$schemaInfo['version'] = $xmlSchema->item(0)->getAttribute('version');

		$xmlfields = $xmlDoc->getElementsByTagName('field');
		for ($index = 0; $index < $xmlfields->length; $index++) {
			$fieldName = $xmlfields->item($index)->getAttribute('name');
			$fieldAttributes['indexed'] = $xmlfields->item($index)->getAttribute('indexed');
			$fieldAttributes['stored'] = $xmlfields->item($index)->getAttribute('stored');
			$fieldAttributes['required'] = $xmlfields->item($index)->getAttribute('required');
			$fieldAttributes['termVectors'] = $xmlfields->item($index)->getAttribute('termVectors');
			$schemaInfo[$fieldName] = $fieldAttributes;
		}

		$xmlUniqueKey = $xmlDoc->getElementsByTagName('uniqueKey');
		$schemaInfo['uniqueKey'] = $xmlUniqueKey->item(0)->nodeValue;

		$xmldefaultSearchField = $xmlDoc->getElementsByTagName('defaultSearchField');
		$schemaInfo['defaultSearchField'] = $xmldefaultSearchField->item(0)->nodeValue;

		$xmlFieldTypes = $xmlDoc->getElementsByTagName('filter');
		for( $index = 0; $index < $xmlFieldTypes->length; $index++ ) {
			$fieldTypeName =  $xmlFieldTypes->item($index)->getAttribute('class');
			if( $fieldTypeName == 'solr.NGramFilterFactory') {
				$schemaInfo['minGramSize'] = $xmlFieldTypes->item($index)->getAttribute('minGramSize');
				$schemaInfo['maxGramSize'] = $xmlFieldTypes->item($index)->getAttribute('maxGramSize');
				break;
			}
		}

		//Check if CJK setup is used.
		$xpath = new DOMXPath($xmlDoc);
		$query = '//schema/types/fieldType[@name="textNGram"]/analyzer[@class="org.apache.lucene.analysis.cjk.CJKAnalyzer"]';
		$entries = $xpath->query($query);
		$length = $entries->length;
		if ($length > 0) {
			$schemaInfo['CJKsetup'] = true;
		}
		else {
			$schemaInfo['CJKsetup'] = false;
		}

		return true;
	}

	/**
	 * This method reads the solrconfig.xml of the solr server and checks it.
	 *
	 * It retrieves specific information that is used to check if the set up of
	 * Solr is in line with the settings of config_solr.php.
	 *
	 * @param string $solrConfigName Core solr config filename
	 * @return true if solrconfig.xml is read otherwise false.
	 */
	private function checkSolrConfigInfo( $solrConfigName )
	{
		$xmlDoc = new DOMDocument();
		$contents = file_get_contents( SOLR_SERVER_URL.'/'.SOLR_CORE.'/admin/file/?file='.$solrConfigName );
		if( $contents === false ) {
			$this->setResult( 'ERROR', 'Reading Solr '.$solrConfigName.' failed.',
				'Check if the file exists in your Solr/conf folder and check the read access rights for the web user.');
			return false;
		}
		if( !$xmlDoc->loadXML($contents) ) {
			$this->setResult( 'ERROR', 'Parsing Solr '.$solrConfigName.' failed.',
				'This typically happens when you have manually edited the file and accidentally made a typo. '.
				'Check if the file is well formed by opening it in a Web browser (or in an XML editor). '.
				'Fix the typos in a plain text editor. Alternatively, install the original solrconfig.xml '.
				'file shipped with Enterprise Server. This can be done by copying the Enterprise file to the Solr/conf folder. '.
				'Obviously, your customizations made to the file will then be lost.');
			return false;
		}

		// Check the Lucene version, should match 4.5.
		$luceneVersion = (string)$xmlDoc->getElementsByTagName('luceneMatchVersion')->item(0)->nodeValue;

		if( version_compare($luceneVersion, '4.5', '<') ) {
			$this->setResult( 'ERROR', $solrConfigName . ' version is incorrect.',
				'This typically happens when you have manually edited the file and accidentally made a typo. '.
				'Check if the "luceneMatchVersion" is well formed by opening it in a Web browser (or in an XML editor). '.
				'Fix the typos in a plain text editor. Alternatively, or if you upgraded Solr, install the original solrconfig.xml '.
				'file shipped with Enterprise Server. This can be done by copying the Enterprise file to the Solr/conf folder. '.
				'Obviously, your customizations made to the file will then be lost.');
			return false;
		}

		$foundSearch = false;
		$foundWoodwing = false;
		$xmlfields = $xmlDoc->getElementsByTagName('requestHandler');
		$result = false;
		for ($index = 0; $index < $xmlfields->length; $index++) {
			$fieldName = $xmlfields->item($index)->getAttribute('name');
			if ($fieldName == 'WoodWing') {
				$foundWoodwing = true;
			}
			elseif ($fieldName == 'search') {
				$foundSearch = true;
			}
			if ($foundSearch && $foundWoodwing) {
				$result = true;
				break;
			}
		}

		if (!$foundWoodwing) {
			$this->setResult( 'ERROR', 'The "WoodWing" requestHandler is not found in '.$solrConfigName.'. ',
				'This typically happens when you have manually edited the file and accidentally made a typo. '.
				'Check '.$solrConfigName.' file on the defined requestHandlers. One of the handlers must have attribute name="WoodWing". '.
				'If not found copy the handler from solrconfig.xml file shipped with Enterprise Server. ' .
				'This can also be done by copying the Enterprise file to the Solr/conf folder. '.
				'Obviously, your customizations made to the file will then be lost.');
		}
		if (!$foundSearch) {
			$this->setResult( 'ERROR', 'The "search" requestHandler is not found in '.$solrConfigName.'. ',
				'This typically happens when you have manually edited the file and accidentally made a typo. '.
				'Check '.$solrConfigName.' file on the defined requestHandlers. One of the handlers must have attribute name="search". '.
				'If not found copy the handler from solrconfig.xml file shipped with Enterprise Server. ' .
				'This can also be done by copying the Enterprise file to the Solr/conf folder. '.
				'Obviously, your customizations made to the file will then be lost.');
		}

		$autoCommit = false;
		$xpath = new DOMXPath($xmlDoc);
		$query = '//config/updateHandler/autoCommit';
		$entries = $xpath->query($query);
		$length = $entries->length;
		if ($length > 0) {
			$autoCommit = true;
		}

		$this->checkAutoCommit($autoCommit);

		return $result;
	}
	/**
	 * This method checks if the index fields set in config_solr.php match with
	 * the ones of schema.xml
	 *
	 * @param array $schemaInfo Info read from the schema.xml file.
	 */
	private function checkIndexFields($schemaInfo)
	{
		if (defined('SOLR_INDEX_FIELDS')) {
			$configFieldsToIndex = unserialize(SOLR_INDEX_FIELDS);
		}
		else {
			$this->setResult( 'ERROR', 'No Solr index fields defined', 'Check SOLR_INDEX_FIELDS in config_solr.php.' );
			return;
		}

		foreach ($configFieldsToIndex as $configFieldToIndex) {
			if (!array_key_exists($configFieldToIndex, $schemaInfo)) {
				$this->setResult( 'ERROR', 'Field ' . "'$configFieldToIndex'" . ' not in &lt;fields&gt; section of schema.xml.' , 'Check &lt;fields&gt; tag in schema.xml.' );
			}
		}
	}

	/**
	 * This method checks if the mandatory fields are set in config_solr.php and if
	 * they match with the settings of schema.xml
	 *
	 * @param array $schemaInfo Info read from the schema.xml file.
	 */
	private function checkMandatoryFields($schemaInfo)
	{
		$mandatoryFields = array('ID', 'PublicationId', 'IssueId', 'CategoryId', 'StateId', 'IssueIds', 'Issues', 'Closed');

		foreach ($mandatoryFields as $mandatoryField) {
			if (!array_key_exists($mandatoryField, $schemaInfo)) {
					$this->setResult( 'ERROR', 'Mandatory field ' . "'$mandatoryField'" . ' not in &lt;fields&gt; section of schema.xml.' , 'Check &lt;fields&gt; tag in schema.xml.');
			}
			elseif ($schemaInfo[$mandatoryField]['indexed'] !== 'true') {
					$this->setResult( 'ERROR', "Mandatory field $mandatoryField must be indexed." , "Add 'indexed = \"true\"' on field $mandatoryField in schema.xml.");
			}
		}
	}

	/**
	 * This method checks if the unique key field is set in config_solr.php and if
	 * it matches with the settings of schema.xml
	 *
	 * @param array $schemaInfo Info read from the schema.xml file.
	 */

	private function checkUniqueKey($schemaInfo)
	{
		if (!array_key_exists('uniqueKey', $schemaInfo) || $schemaInfo['uniqueKey'] == null) {
				$this->setResult( 'ERROR', 'No unique key set in schema.xml.' , 'Add &lt;uniqueKey&gt;ID&lt;/uniqueKey&gt; to schema.xml.');
		}
		elseif ($schemaInfo['uniqueKey'] !== 'ID') {
				if ($schemaInfo['uniqueKey'] == ''){
					$this->setResult( 'ERROR', 'No unique key is set in schema.xml.' , 'Change setting to &lt;uniqueKey&gt;ID&lt;/uniqueKey&gt; in schema.xml.');
				}
				else {
					$this->setResult( 'ERROR', $schemaInfo['uniqueKey']. ' is set as unique key in schema.xml.' , 'Change setting to &lt;uniqueKey&gt;ID&lt;/uniqueKey&gt; in schema.xml.');
				}
		}
	}

	/**
	 * This method checks if fields used for facets are set in config_solr.php and if
	 * they match with the settings of schema.xml
	 *
	 * @param array $schemaInfo Info read from the schema.xml file.
	 */
	private function checkFacetFields($schemaInfo)
	{
		if (defined('SOLR_GENERAL_FACETS')) {
			$facetFields = unserialize(SOLR_GENERAL_FACETS);

			foreach($facetFields as $facetField) {
				if (!array_key_exists($facetField, $schemaInfo)) {
					$this->setResult( 'ERROR', 'Facet field ' . "'$facetField'" . ' not in &lt;fields&gt; section of schema.xml.' , 'Check &lt;fields&gt; tag in schema.xml.');
				}
				elseif ($schemaInfo[$facetField]['indexed'] !== 'true') {
					$this->setResult( 'ERROR', "Facet field $facetField is not indexed." , "Add 'indexed = \"true\"' on field $facetField in schema.xml.");
				}
			}
		}
		else {
			$this->setResult( 'WARN', 'Facets not used' , "Check SOLR_GENERAL_FACETS in config_solr.php.");
		}
	}

	/**
	 * This method checks if the 'catch all' field is set in config_solr.php and if
	 * it matches with the settings of schema.xml
	 *
	 * @param array $schemaInfo Info read from the schema.xml file.
	 */
	private function checkCatchAllField($schemaInfo)
	{
		if (!array_key_exists('defaultSearchField', $schemaInfo) || $schemaInfo['defaultSearchField'] == null ) {
				$this->setResult( 'ERROR', 'No Catch All field set in schema.xml.' , 'Add &lt;defaultSearchField&gt;WW_CATCHALL&lt;/defaultSearchField&gt; to schema.xml.');
		}

		$catchAllField = null;
		if (defined('SOLR_CATCHALL_FIELD')) {
			$catchAllField = SOLR_CATCHALL_FIELD;
		}
		else {
			$this->setResult( 'ERROR', 'No Catch All field set in config_solr.php.' , "Add 'define ('SOLR_CATCHALL_FIELD', 'WW_CATCHALL')' to config_solr.php.");
		}

		if ($schemaInfo['defaultSearchField'] !== $catchAllField) {
				$this->setResult( 'ERROR', $catchAllField .  'is not set as defaultSearchField in schema.xml.' , 'Change setting to &lt;defaultSearchField&gt;'.$catchAllField.'&lt;/defaultSearchField&gt; in schema.xml.');
		}
	}

	/**
	 * This method checks if the request encoding is UTF-8.
	 *
	 * It sends a query through Solarium and echos the parameters.
	 * The query parameter is expected to be unmodified.
	 */
	private function checkUTF8GetRequest()
	{
		$urlDecoded = urldecode('h%C3%A9llo');

		// Start a new Query
		$query = $this->solariumClient->createSelect( array(
			'query' => $urlDecoded, // send decoded, solarium will encode the parameters again
		));

		// Tell Solr to echo the parameters.
		$query->addParam( "echoParams", "explicit" );
		$query->setOmitHeader( false );

		// Execute the echo Query and get the response data
		$resultSet = $this->solariumClient->executeSelect( $query );
		$data = $resultSet->getData();
		$response = $data['responseHeader']['params']['q'];

		// The Query should be the same as the original parameter
		if ( $response == $urlDecoded) {
			return true;
		}

		if( $response ) {
			// Note: Tomcat/Jeti no longer needs to be configured for UTF-8 since Solr 4.1
			//       Solr now parses the request parameters.
			$this->setResult( 'ERROR', 'UTF8 not suppported for Solr requests.' );
		}
		else {
			$this->setResult( 'ERROR', 'Could not perform UTF-8 Solr request check' );
		}

		return false;

	}

	/**
	 * This method checks if fields used for NGram size are set in config_solr.php and if
	 * they match with the settings of schema.xml
	 *
	 * @param array $schemaInfo Info read from the schema.xml file.
	 */
	private function checkNGramSize($schemaInfo)
	{
		//Ngram size is not applicable in case of CJK-laguage setup.
		if ($schemaInfo['CJKsetup']) {
			return;
		}

		if (defined('SOLR_NGRAM_SIZE')) {
			$NGramSizes = unserialize(SOLR_NGRAM_SIZE);
			if( $NGramSizes[0] != $schemaInfo['minGramSize'] ) {
				$this->setResult( 'WARN', $NGramSizes[0] . 'The minGramSize defined value is not the same in the schema.xml.' .  $schemaInfo['minGramSize'] , "Check minGramSize attribute in schema.xml");
			}
			if( $NGramSizes[1] != $schemaInfo['maxGramSize'] ) {
				$this->setResult( 'WARN', "The maxGramSize defined value is not the same in the schema.xml.", "Check maxGramSize attribute in schema.xml");
			}
		}
		else {
			$this->setResult( 'WARN', 'No minimum and maximum defined for N-gram analysis.' , "Check SOLR_NGRAM_SIZE in config_solr.php.");
		}
	}

	/**
	 * This method checks if the autoCommit is enabled in config_solr.xml.
	 *
	 * If not a warning is diplayed. Autocommit improves performance when a lot of documents
	 * are added to or deleted from Solr (e.g. during reindexing). If the SOLR_AUTOCOMMIT
	 * in the config_solr.php is set to true but the autoCommit in solrconfig.xml is not set
	 * an error is set because in that case documents are not added to or deleted from Solr.
	 *
	 * @param boolean $autoCommit Auto Commit Info read from the solrconfig.xml file.
	 */
	private function checkAutoCommit($autoCommit)
	{
		if (!defined('SOLR_AUTOCOMMIT') || !SOLR_AUTOCOMMIT) {
			$this->setResult( 'WARN', 'No autoCommit enabled. This can have a serious performance drawback when many documents are added to or deleted from Solr ' ,
						"Check the autoCommit setting in solrconfig.xml and set the SOLR_AUTOCOMMIT in config_solr.php to true.");
		} elseif (!$autoCommit) { // If SOLR_AUTOCOMMIT is true the autoCommit in solrconfig.xml is mandatory.
			$this->setResult( 'ERROR', 'AutoCommit is enabled in config_solr.php (SOLR_AUTOCOMMIT), but not in the solrconfig.xml configuration file.' ,
						"Check the autoCommit setting in solrconfig.xml.");
		}
	}

	/**
	 * Checks the version of the currently used Solr instance.
	 *
	 * Solr versions prior to version 4.5.0 are not supported, and thus if one of those versions is detected
	 * then an error message should be displayed accordingly.
	 *
	 * @param bool $ignoreFailRetrieve Does not set an error message if it failed to retrieve the version
	 * @return bool Whether or not the version is supported.
	 */
	private function checkVersion($ignoreFailRetrieve = false)
	{
		$versionNumber = $this->getVersion();

		if( $versionNumber == '0.0.0' ) {
			if( $ignoreFailRetrieve ) {
				return true;
			}
			$this->setResult( 'ERROR', 'Could not retrieve Solr version number',
				"Verify your Solr connection and verify Solr is at least version 4.5.0.");
			return false;
		}
		else {
			// Solr versions pre 4.5 are not supported.
			if (version_compare($versionNumber, '4.5.0', '<')){
				$this->setResult( 'ERROR', 'The currently configured Solr version ( ' . $versionNumber . ' ) is not supported, version 4.5.0 or higher is required.',
					"Upgrade Solr to at least version 4.5.0.");
				return false;
			}
		}
		return true;
	}

	/**
	 * Retrieves the Solr version number used with Enterprise server.
	 *
	 * Uses the configuration settings to retrieve information from the used Solr instance.
	 * By specifying the $extended parameter extended or simple version information can be
	 * retrieved. If the Solr $versionNumber cannot be determined '0.0.0' is returned.
	 *
	 * @param bool $extended Whether or not to retrieve extended information, defaults to false.
	 * @return string The version number or '0.0.0' if the version number could not be determined.
	 */
	private function getVersion($extended = false)
	{
		// Get Solr 4 system info. This url is only valid for Solr 4.
		$url = SOLR_SERVER_URL .'/admin/info/system';
		$config = file_get_contents($url);

		// Retrieve the Solr version number.
		$default = '0.0.0';
		$versionNumber = $default;

		try {
			$xml = new SimpleXMLElement($config);

			// We check the specification version as it adheres to a stricter scheme than the
			// implementation version. In practice the numeric part that matters is always the
			// same for both.
			$versionNumber = $xml->xpath('/response/lst[@name="lucene"]/str[@name="solr-spec-version"]');
			$versionNumber = (false != $versionNumber) ? $versionNumber[0] : $default;
		} catch( Exception $e ) {
			// Continue, return default
			$e = $e; // Keep analyzer happy
		}

		// Found nothing? Try get the Solr 3 version number instead
		if( $versionNumber == $default ) {
			try {
				$url = SOLR_SERVER_URL . '/admin/registry.jsp';
				$config = file_get_contents($url);

				// Retrieve the Solr version number.
				$xml = new SimpleXMLElement($config);
				$default = '0.0.0';
				$versionNumber = $default;
				if ($xml instanceof SimpleXmlElement){
					// We check the specification version as it adheres to a stricter scheme than the
					// implementation version. In practice the numeric part that matters is always the
					// same for both.
					$versionNumber = $xml->xpath('/solr/solr-spec-version');
					$versionNumber = (false != $versionNumber) ? $versionNumber[0] : $default;
				}
			} catch( Exception $e ) {
				// Continue, return default
				$e = $e; // Keep analyzer happy
			}
		}

		// Determine what type of number to return, specs say the number should be three numbers,
		// the exception being nightly builds which can contain extended information.
		if (!$extended){
			$versionParts = explode('.', $versionNumber, 4);
			$majorVersion = isset($versionParts[0]) ? $versionParts[0] : '0';
			$minorVersion = isset($versionParts[1]) ? $versionParts[1] : '0';
			$releaseVersion = isset($versionParts[2]) ? $versionParts[2] : '0';
			$versionNumber = $majorVersion . '.' . $minorVersion . '.' . $releaseVersion;
		}
		return $versionNumber;
	}

	/**
	 * Check the version of the schema.xml file.
	 *
	 * The schema should adhere to the Solr 4.5 version.
	 *
	 * @return bool Whether or not the test was passed.
	 */
	private function checkSchemaVersion($schemaInfo)
	{
		$fieldName = $schemaInfo['version'];
		if ($fieldName != '1.5') {
			$this->setResult( 'ERROR', 'The used Solr schema.xml file has an incorrect version. ' .
				'This typically happens when you did not copy the file shipped with Enterprise Server to the Solr/conf folder. '.
				'Please check the &lt;schema name="WoodWing" version="1.5"&gt; entry in the schema.xml file. ' .
				'This can be fixed by copying the Enterprise file to the Solr/conf folder. '.
				'Obviously, your customizations made to the file will then be lost.');
			return false;
		}
		return true;
	}

	/**
	 * Retrieves the Schema as a DOM document.
	 *
	 * Retrieves the schema.xml as a Dom Document.
	 *
	 * @return bool|DOMDocument Either false if there are errors, or the parsed DOMDocument object.
	 */
	private function getSchemaAsDomDocument( $solrSchemaName )
	{
		// Retrieve the schema.xml by calling the Solr admin interface.
		$xmlDoc = new DOMDocument();
		$contents = file_get_contents( SOLR_SERVER_URL.'/'.SOLR_CORE.'/admin/file/?file='.$solrSchemaName );
		if( $contents === false ) {
			$this->setResult( 'ERROR', 'Reading Solr '.$solrSchemaName.' failed.','Check if the file exists in your Solr/conf folder and check the read access rights for the web user.');
			return false;
		}
		if( !$xmlDoc->loadXML($contents) ) {
			$this->setResult( 'ERROR', 'Parsing Solr '.$solrSchemaName.' failed.',
				'This typically happens when you have manually edited the file and accidentally made a typo. '.
					'Check if the file is well formed by opening it in a Web browser (or in an XML editor). '.
					'Fix the typos in a plain text editor. Alternatively, install the original schema.xml '.
					'file shipped with Enterprise Server. This can be done by copying the Enterprise file to the Solr/conf folder. '.
					'Obviously, your customizations made to the file will then be lost.');
			return false;
		}
		return $xmlDoc;
	}
}