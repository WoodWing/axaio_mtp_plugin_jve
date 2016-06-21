<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class with functions called to publish to the Drupal publishing system.
**/

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once BASEDIR . '/config/config_drupal.php';

class Drupal_PubPublishing extends PubPublishing_EnterpriseConnector
{
	/**
	 * XML-RPC client (of the Zend Framework) connecting to Drupal.
	 *
	 * @var Zend_XmlRpc_Client
	 */
	private $rpcClient = null;

	/**
	 * The target (channel/issue) where to publish the dossier to. 
	 *
	 * @var PubPublishTarget
	 */
	private $publishtarget = null;
	
	/**
	 * The dossier object being published / unpublished / previewed.
	 *
	 * @var PubPublishedDossier
	 */
	private $dossier = null;
	
	// - - - - - - - - - - Drupal credentials - - - - - - - - - - 
	/**
	 * URL entry point, used to connect to Drupal.
	 *
	 * @var string
	 */
	private $drupalUrl;
	
	/**
	 * Class wide variable that holds the username for Drupal.
	 *
	 * @var string
	 */
	private $drupalUsername;
	
	/**
	 * Class wide variable that holds the password for Drupal.
	 *
	 * @var string
	 */
	private $drupalPassword;
	
	/**
	 * File path to the CA certificate file (PEM). Required for HTTPS (SSL) connections.
	 *
	 * @var string
	 */
	private $drupalCertificate;
	
	// - - - - - - - - - - Site specific options - - - - - - - - - - 
	/**
	 * Site specific options to send to Drupal.
	 *
	 * @var array
	 */
	private $drupalSiteOptions;
	
	/**
	 * Site specific mapping to send to Drupal.
	 *
	 * @var array
	 */
	private $drupalSiteMapping;
	
	// - - - - - - - - - - Enterprise data - - - - - - - - - - 
	/**
	 * Enterprise Admin issue.
	 *
	 * @var AdmIssue
	 */
	private $enterpriseIssue;
	
	/**
	 * ExtraMetaData for the issue.
	 *
	 * @var array
	 */
	private $enterpriseIssueExtraMetaData;
	
	/**
	 * Fields data of the published dossier
	 *
	 * @var array
	 */
	private $publishFieldsMetaData = array();


	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * Publishes a dossier with contained objects (articles. images, etc.) to Drupal.
	 * The plugin is supposed to publish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of PubFields containing information from Drupal
	**/
	public function publishDossier( &$dossier, &$objectsindossier, $publishtarget )
	{
		$this->publishtarget = $publishtarget;
		$this->dossier = $dossier;

		$this->startDrupal();
		$saveResult = $this->nodeSave( $dossier, $objectsindossier, 1 );

		$dossier->ExternalId = $saveResult['nodeId'];
		// map Drupal ids to Enterprise objects
		if( isset($saveResult['idArray']) ) {
			foreach( $objectsindossier as $object ) {
				$objId = $object->MetaData->BasicMetaData->ID;
				if( isset( $saveResult['idArray'][$objId] ) ) {
					$object->ExternalId = $saveResult['idArray'][$objId];
				}
			}
		}

		$url = $this->drupalUrl . '?q=node/' . $dossier->ExternalId;
		return array( new PubField( 'URL', 'string', array( $url ) ) );
	}

	/**
	 * Updates/republishes a published dossier with contained objects (articles. images, etc.) to Drupal,
	 * using the $dossier->ExternalId to identify the dosier to Drupal.
	 * The plugin is supposed to update/republish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of PubFields containing information from Drupal
	**/
	public function updateDossier( &$dossier, &$objectsindossier, $publishtarget )
	{
		$this->publishtarget = $publishtarget;
		$this->dossier = $dossier;

		$this->startDrupal();
		$updateResult = $this->nodeUpdate( $dossier->ExternalId, $dossier, $objectsindossier );

		// map Drupal ids to Enterprise objects
		if( isset($updateResult['idArray']) ) {
			foreach( $objectsindossier as $object ) {
				$objId = $object->MetaData->BasicMetaData->ID;
				if( isset( $updateResult['idArray'][$objId] ) ) {
					$object->ExternalId = $updateResult['idArray'][$objId];
				}
			}
		}

		$url = $this->drupalUrl . '?q=node/' . $dossier->ExternalId;
		return array( new PubField( 'URL', 'string', array( $url ) ) );
	}

	/**
	 * Removes/unpublishes a published dossier from Drupal
	 * using the $dossier->ExternalId to identify the dosier to Drupal.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of PubFields containing information from Drupal
	 */
	public function unpublishDossier( $dossier, $objectsindossier, $publishtarget )
	{
		$this->publishtarget = $publishtarget;
		$this->dossier = $dossier;

		$this->startDrupal();

		$images = array();
		foreach( $objectsindossier as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'Image' ) {
				$images[] = intval( $object->ExternalId );
			}
		}
		
		if( isset($dossier->Fields) ) {
			$this->getDialogPublishFields($dossier->Fields);
		}

		$this->callRpcService( 'enterprise.nodeUnpublish', array(
			array(
				'username'=> $this->drupalUsername,
				'password' => base64_encode( $this->drupalPassword )
			),
			intval( $dossier->ExternalId ),
			$images,
			$this->publishFieldsMetaData
		));

		//$externalid = $dossier->ExternalId;
		//$dossier->ExternalId = - 1 * $externalid;
		// There is no external id anymore because the node is always removed for now. 
		$dossier->ExternalId = "";

		// Return a empty array so this published dossier is saved in the database
		return array();
	}

	/**
	 * Requests fieldvalues from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of PubFields containing information from Drupal
	**/
	public function requestPublishFields( $dossier, $objectsindossier, $publishtarget )
	{
		// keep analyzer happy
		$objectsindossier = $objectsindossier;
		
		$this->publishtarget = $publishtarget;
		$this->dossier = $dossier;

		$this->startDrupal();
		$response = $this->callRpcService( 'enterprise.nodeGetInfo', array(
			intval( $dossier->ExternalId )
		));
		
		$map = array( 
			'Views'    => 'int',
			'Rating'   => 'double',
			'Raters'   => 'int',
			'CommentsCount' => 'int',
			'Comments' => 'multistring',
			'URL'      => 'string'
		);
		$result = array();
		if( $response ) foreach( $response as $fieldKey => $fieldVal ) {
			if( $fieldVal == 'N/A' ) {
				$type = 'string';
				$fieldVal = BizResources::localize('NOT_AVAILABLE'); 
			} else {
				$type = isset( $map[$fieldKey] ) ? $map[$fieldKey] : 'string';
			}
			$result[] = self::getField( $fieldKey, $type, $fieldVal );
		}

		return $result;
	}

	/**
	 * Requests dossier URL from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return string
	 */
	public function getDossierURL( $dossier, $objectsindossier, $publishtarget )
	{
		// keep analyzer happy
		$objectsindossier = $objectsindossier; 
		
		$this->publishtarget = $publishtarget;
		$this->dossier = $dossier;
		
		$this->startDrupal();
		$url = $this->callRpcService( 'enterprise.getUrl', array(
			intval( $dossier->ExternalId )
		));
		
		if(empty($url)) {
			$url = $this->drupalUrl . '?q=node/' . $dossier->ExternalId;
		}
		
		return $url;
	}

	/**
	 * Previews a dossier with contained objects (articles. images, etc.) to an external publishing system.
	 * The plugin is supposed to send the dossier and it's articles to the publishing system and fill in the URL field for reference.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of PubFields containing information from Publishing system
	**/
	public function previewDossier( &$dossier, &$objectsindossier, $publishtarget )
	{
		$this->publishtarget = $publishtarget;
		$this->dossier = $dossier;

		$this->startDrupal();
		$saveResult = $this->nodeSave( $dossier, $objectsindossier, 0, 0, true );

		$dossier->ExternalId = $saveResult['nodeId'];
		$url = '';
		if( isset( $saveResult['url'] ) ) {
			$url = $saveResult['url'];
		} else { // build url ourself
			$url = $this->drupalUrl . '?q=node/' . $dossier->ExternalId;
		}
		// map Drupal ids to Enterprise objects
		if( isset($saveResult['idArray']) ) {
			foreach( $objectsindossier as $object ) {
				$objId = $object->MetaData->BasicMetaData->ID;
				if( isset( $saveResult['idArray'][$objId] ) ) {
					$object->ExternalId = $saveResult['idArray'][$objId];
				}
			}
		}
		return array( new PubField( 'URL', 'string', array($url) ) );
	}

	/**
	 * This function is called when the GetDossier is called for PublishDossier, UnPublishDossier or UpdateDossier.
	 * In this function an array of arrays can be created as:
	 * array( 'errors' => array(), 'warnings' => array(), 'infos' => array());
	 * Errors stop the publishing of the object. Warnings are shown to the user, but a user is still allowed to publish.
	 * Infos are just informational strings.
	 *
	 * @param string $type the type of the validation. PublishDossier, UnPublishDossier or UpdateDossier
	 * @param int $dossierId The id of the dossier to publish
	 * @param int $issueId The id of the issue to publish in
	 * @return array
	 */
	public function validateDossierForPublishing( $type, $dossierId, $issueId )
	{
		$type = $type; $dossierId = $dossierId; $issueId = $issueId; // keep analyzer happy
		// If Content Station 7.1.x is used you can use this to validate the input before publishing or updaing
		return array('errors' => array(), 'warnings' => array(), 'infos' => array());	
	}

	/**
	 * Creates the XML RPC client to talk to Drupal. 
	 * Deals with HTTPS / SSL connections too for which the certificate option must be set.
	 *
	 * @param string $uri The URL to Drupal
	 * @param string $localCert File path to the certificate file (PEM). Required for HTTPS (SSL) connection.
	 * @throws BizException When HTTPS uri given used without certificate.
	 * @return Zend_Http_Client
	 */
	private function createHttpClient( $uri, $localCert )
	{
		try {
			require_once 'Zend/Uri.php';
			$uri = Zend_Uri::factory( $uri );
			$isHttps = $uri && $uri->getScheme() == 'https';
		} catch( Zend_Http_Client_Exception $e ) {
			throw new BizException( null, 'Server', null, $e->getMessage().
			'. Check your "url" option at the DRUPAL_SITES setting of the drupal_config.php file.' );
		} catch( Zend_Uri_Exception $e ) {
			throw new BizException( null, 'Server', null, $e->getMessage().
			'. Check your "url" option at the DRUPAL_SITES setting of the drupal_config.php file.' );
		}

		require_once 'Zend/Http/Client.php';
		$httpClient = new Zend_Http_Client( $uri );

		// Because the Zend_XmlRpc_Client class supports SSL, but does not validate certificates / hosts / peers (yet),
		// its connections are NOT safe! Therefore we use CURL by passing the Zend_Http_Client_Adapter_Curl
		// adapter into the Zend_Http_Client class for which we set the secure options and certificate.
		if( $localCert ) {
			if( !file_exists($localCert) ) {
				throw new BizException( null, 'Server', null, 
					'The file "'.$localCert.'" specified at "local_cert" option does not exists.' );
			}
			if( $isHttps ) {
				$httpClient->setConfig(
					array(
						'adapter' => 'Zend_Http_Client_Adapter_Curl',
						'curloptions' => $this->getCurlOptionsForSsl( $localCert )
					)
				);
			}
		} else {
			if( $isHttps ) {
				throw new BizException( null, 'Server', null, 
					'Using HTTPS, but no "local_cert" option defined at DRUPAL_SITES setting.' );
			}
		}
		return $httpClient;
	}

	/**
	 * Returns a list of options to set to Curl to make HTTP secure (HTTPS).
	 *
	 * @param string $localCert File path to the certificate file (PEM). Required for HTTPS (SSL) connection.
	 * @return array
	 */
	private function getCurlOptionsForSsl( $localCert )
	{
		return array(
		//	CURLOPT_SSLVERSION => 2, Let php determine itself. Otherwise 'unknow SSL-protocol' error. 
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_CAINFO => $localCert
		);
	}
	
	/**
	 * Test configuration by calling enterprise.testConfig in Drupal
	 *
	 * @return array with keys "Errors" and "Version"
	 */
	public function testConfig()
	{
		$sites = unserialize(DRUPAL_SITES);
		
		$result = array('Errors' => array(), 'Warnings'=>array(), 'Version'=>array());
		$orgRpcClient = $this->rpcClient;
		foreach( $sites as $name => $site ) {

			try {
				if( empty($site['url']) ) {
					throw new BizException( null, 'Server', null, 
						'No Drupal web location specified at "url" option.' );
				}
				require_once 'Zend/XmlRpc/Client.php';
				$uri = $site['url'] . 'xmlrpc.php';
				$httpClient = $this->createHttpClient( $uri, $site['local_cert'] );
				$this->rpcClient = new Zend_XmlRpc_Client( $uri, $httpClient );
				$valueArray = $this->callRpcService( 'enterprise.testConfig', array( 
					array(
						'username' => $site['username'],
						'password' => base64_encode( $site['password'] )
					)
				));
			} catch( BizException $e ) {
				$result['Errors'][] = $name.' - '.$e->getMessage();
			}

			if (isset($valueArray['Version'])) {
				$version = $valueArray['Version'];
				if(SERVERVERSION != $version) {
					$result['Version'][] = 'Site: ' . $name . " - Drupal module version " . $version . " doesn't equal server version " . SERVERVERSION . ". Install the newest Drupal module.";
				}
			} else {
				$result['Version'][] = 'Site: ' . $name . " - Drupal module doesn't send a version. This means Drupal has an old module installed. Install the newest Drupal module.";
			}
		}
		$this->rpcClient = $orgRpcClient;
		return $result;
	}

	/**
	 * Initializes the XMLRPC functionality for Drupal. It sets the correct credentials for this dossier
	 * and retrieves corresponding MetaData. It also creates the XML RPC client.
	 */
	private function startDrupal()
	{
		$this->getCredentials();
		$this->getExtraMetadata();

		require_once 'Zend/XmlRpc/Client.php';
		$uri = $this->drupalUrl . 'xmlrpc.php';
		$httpClient = $this->createHttpClient( $uri, $this->drupalCertificate );
		$this->rpcClient = new Zend_XmlRpc_Client( $uri, $httpClient );
	}

	/**
	 * Get and set the variables that holds the cridentials. The config array
	 * in config_drupal.php is first checked if there is an overruled site. Otherwise
	 * get the default site credentials. The corresponding mapping and options are also set.
	 */
	private function getCredentials()
	{
		if(isset($this->drupalUrl) && isset($this->drupalUsername) && isset($this->drupalPassword)) {
			return;
		}

		$sites = unserialize(DRUPAL_SITES);
		$config = unserialize(DRUPAL_CONFIG);

		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';

		// Get info about the channel
		$pubChannel = DBChannel::getChannel( $this->publishtarget->PubChannelID );
		// Get the names of the channel, issue and brand(publication)
		$pubChannelName = $pubChannel['name'];
		$pubIssueName = DBIssue::getIssueName( $this->publishtarget->IssueID );
		$publicationName = DBPublication::getPublicationName( $pubChannel['publicationid'] );

		// Select the default site in this case the first one in the array so reset the internal pointer for safety
		$site = reset($sites);
		$name = key($sites);

		$stages = array( 'issue' => 3, 'channel' => 2, 'brand' => 1, 'default' => 0 );

		$type = "default";

		foreach( $config as $overrule ) {
			foreach( $stages as $stage => $number ) {
				//check if there is information on this stage or overrule
				if(isset($overrule[$stage])) {
					// Check if the issuename, channel name and publiction name or ids matches. if the number is lower the boolean is automatically set to true
					$iss = ($number >= 3) ? ($overrule[$stage] == $pubIssueName || $overrule[$stage] == $this->publishtarget->IssueID) : true;
					$chan = ($number >= 2) ? ($overrule[$stage] == $pubChannelName || $overrule[$stage] == $this->publishtarget->PubChannelID) : true;
					$pub = ($number >= 1) ? ($overrule[$stage] == $publicationName || $overrule[$stage] == $pubChannel['publicationid']) : true;
					// Check if the issue, channel and brand match
					if($iss && $chan && $pub) {
						// Check if the unique name is set and not empty
						if(isset($overrule['site']) && !empty($overrule['site'])) {
							// Check if the stage is more important. So the default type is classified least and the issue type is classified highest.
							if($stages[$type] < $stages[$stage]) {
								// Change the site information, name of the site and type.
								$site = $sites[$overrule['site']];
								$name = $overrule['site'];
								$type = $stage;
								break;
							}
						}
					}
				}
			}
		}

		if(!isset($site) || empty($site)) {
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('Drupal', BizResources::localize('ERR_DRUPAL_NO_SITE_INFORMATION')));
		}

		LogHandler::Log( 'Drupal', 'DEBUG', 'Publish to: ' . $name );

		// Set the credentials of the found site
		$this->drupalUrl         = $site['url'];
		$this->drupalUsername    = $site['username'];
		$this->drupalPassword    = $site['password'];
		$this->drupalCertificate = $site['local_cert'];

		$this->getSiteSpecificOptions( $site );
	}

	/**
	 * Function to get and merge the site specific options and mapping.
	 *
	 * @param array $site
	 */
	private function getSiteSpecificOptions( $site )
	{
		// Read the DRUPAL_OPTIONS setting
		$drupalOptions = unserialize( DRUPAL_OPTIONS );
		if( isset($site['options']) && is_array($site['options']) ) {
			$drupalOptions = array_merge( $drupalOptions, $site['options'] );
		}
		$this->drupalSiteOptions = $drupalOptions;

		// Read the DRUPAL_MAPPING setting
		$drupalMapping = unserialize( DRUPAL_MAPPING );
		if( isset($site['mapping']) && is_array($site['mapping']) ) {
			$mappingTaxonomy = (isset($drupalMapping['taxonomy'])) ? $drupalMapping['taxonomy'] : array();
			// Preserve the default taxonomy			
			$drupalMapping = array_merge( $drupalMapping, $site['mapping'] );
			$siteTaxonomy = (isset($site['mapping']['taxonomy'])) ? $site['mapping']['taxonomy'] : array();
			$taxonomy = array_merge( $mappingTaxonomy, $siteTaxonomy );
			if( !empty($taxonomy) ) {
				$drupalMapping['taxonomy'] = $taxonomy;
			}
		}
		$this->drupalSiteMapping = $drupalMapping;
	}

	/**
	 * Get and set the ExtraMetaData for the used issue.
	 */
	private function getExtraMetadata()
	{
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';

		$issueId = $this->publishtarget->IssueID;

		$categoryId = null;
		if(isset($this->dossier) && !empty($this->dossier)) {
			$categoryId = $this->dossier->MetaData->BasicMetaData->Category->Id;
		}

		// If the issue isn't already set and the issue id is not empty and bigger than zero get the issue object en save is in ENTERPRISE_ISSUE
		if(!isset($this->enterpriseIssueExtraMetaData) && !empty($issueId) && $issueId > 0) {
			$issue = DBAdmIssue::getIssueObj($issueId);

			// Create two array one in format of xmlrpc and the other as normal data
			$extraInfo = array();
			$found = false;
			foreach($issue->SectionMapping as $section) {
				if(isset($section->SectionId) && $section->SectionId == $categoryId) {
					$key = $section->Property;
					$key = preg_replace("/^C_/", "", $key);
					$key = strtolower($key);

					$extraInfo[$key] = $section->Values;
					$found = true;
				}
			}

			// If the custom properties are not found in the section mapping proceed to the normal metadata
			if(!$found) {
				foreach($issue->ExtraMetaData as $extraMetaData) {
					$key = $extraMetaData->Property;
					$key = preg_replace("/^C_/", "", $key);
					$key = strtolower($key);

					$extraInfo[$key] = $extraMetaData->Values;
				}
			}

			$this->enterpriseIssueExtraMetaData = $extraInfo;
			$this->enterpriseIssue = $issue;
		}
	}

	/**
	 * Get the correct rendition for a to publish object.
	 *
	 * @param PubPublishedObject $object
	 * @return string
	 */
	protected function askRenditionType( $object )
	{
		// default is native;
		$rendition = 'native';
		switch( $object->MetaData->BasicMetaData->Type ) {
			case 'Layout':
			{
				$options = $this->drupalSiteOptions;
				$found = false;
				if( isset( $options['layout_rendition'] ) && !empty( $options['layout_rendition'] ) ) {
					$rendition = $options['layout_rendition'];
					$found = true;
				}

				if( !$found ) {
					$rendition = 'output';
				}

				break;
			}
		}
		return $rendition;
	}


	/**
	 * Gets the correct file content for a layout. The rendition is taken care of
	 * and if the file isn't available as one file, all the pages of the file will be returned.
	 *
	 * @param PubPublishedObject $object
	 * @param string $rendition
	 * @return array of arrays with the type and body of the file
	 */
	private function getFileContentForLayouts( $object, $rendition )
	{
		if( $rendition ) {
			require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';

			$objectid = $object->MetaData->BasicMetaData->ID;
			$user = BizSession::getShortUserName();
			$tempobject = BizObject::getObject($objectid, $user, false, $rendition);

			if( $rendition != 'native' && ( empty($tempobject->Files) || count($tempobject->Pages) > 1 ) ) {
				$files = array();
				foreach($tempobject->Pages as $page) {
					if( !empty($page->Files[0]->FilePath) ) {
						$file = array();
						$file['type'] = $page->Files[0]->Type;
						$transferServer = new BizTransferServer();
						$file['file'] = $transferServer->getContent($page->Files[0]);						
						$files[] = $file;
					}
				}

				return ( !empty( $files) ) ? $files : null;
			} else {
				$file = array();
				$file['type'] = $tempobject->Files[0]->Type;
				$transferServer = new BizTransferServer();
				$file['file'] = $transferServer->getContent($tempobject->Files[0]);

				return array($file);
			}
		} else {
			return null;
		}
	}

    /**
     * Saves a node in Drupal
     *
     * @param PubPublishedDossier $dossier
     * @param array $children
     * @param int $promote
     * @param int $publish
     * @param boolean $preview
     * @return array Returned data from Drupal
     */
    private function nodeSave( $dossier, $children, $promote, $publish = 1, $preview = false )
    {
      	$textcomponents = array();
        $files = array();
        $author = array();
		$this->getNodeComponents( $children, $textcomponents, $files, $author );
		
		if( isset($dossier->Fields) ) {
			$this->getDialogPublishFields( $dossier->Fields );
		}

		$valueArray = $this->callRpcService( 'enterprise.nodePublish', array(
			array(
				'username' => $this->drupalUsername,
				'password' => base64_encode( $this->drupalPassword )
			),
			array(
				'ID'          => $dossier->MetaData->BasicMetaData->ID,
				'Name'        => $dossier->MetaData->BasicMetaData->Name,
				'Category'    => $dossier->MetaData->BasicMetaData->Category->Name,
				'Description' => $dossier->MetaData->ContentMetaData->Description
			),
			$textcomponents,
			array(), // images
			$files,  // attachments
			array(), // audios
			array(), // hyperlinks
			intval( $promote ),
			intval( $publish ),
			$this->getAuthorShortname( $author ),
			$this->enterpriseIssueExtraMetaData,
			$this->getXmlRpcSiteOptions(),
			(bool)$preview,
			$this->publishFieldsMetaData
		));        
        return $valueArray;
    }

	/**
	 * Updates a node in Drupal with the given node id.
	 *
	 * @param int $nodeId
	 * @param PubPublishedDossier $dossier
	 * @param array $children
	 * @return array Returned data from Drupal
	 */
    private function nodeUpdate( $nodeId, $dossier, $children )
    {
      	$textcomponents = array();
        $files = array();
        $author = array();
		$this->getNodeComponents( $children, $textcomponents, $files, $author );

    	if(isset($dossier->Fields)) {
			$this->getDialogPublishFields( $dossier->Fields );
		}
		$valueArray = $this->callRpcService( 'enterprise.nodeUpdate', array(
			array(
				'username' => $this->drupalUsername,
				'password' => base64_encode( $this->drupalPassword )
			),
			intval( $nodeId ),
			array(
				'ID'          => $dossier->MetaData->BasicMetaData->ID,
				'Name'        => $dossier->MetaData->BasicMetaData->Name,
				'Category'    => $dossier->MetaData->BasicMetaData->Category->Name,
				'Description' => $dossier->MetaData->ContentMetaData->Description
			),
			$textcomponents,
			array(), // images
			$files,
			$this->getAuthorShortname( $author ),
			$this->enterpriseIssueExtraMetaData,
			$this->getXmlRpcSiteOptions(),
			$this->publishFieldsMetaData
		));        
        return $valueArray;
    }
    
    /**
     * Sends a message to a XML-RPC server at Drupal with the Zend_XmlRpc classes.
     *
     * @param string $action
     * @param mixed $params
     * @return mixed - If answer is recieved the object will be returned otherwise null is returned.
     */
    private function callRpcService( $action, $params )
    {
    	// Log the request as PHP objects
    	$debugMode = LogHandler::debugMode();
    	if( $debugMode ) {
			LogHandler::Log( 'Drupal', 'DEBUG', 'Procesing service request: '.$action );
			$this->logXmlRpc( $action, print_r($params,true), true, 'TXT' );
		}
		
		// Call the Drupal service (using RPC)
    	$retVal = null;
    	$e = null;
		PerformanceProfiler::startProfile( 'Drupal - '.$action, 3 );
    	try {
    		$retVal = $this->rpcClient->call( $action, $params );
    	} catch( Exception $e ) {
    		$e = $e;
    	}
		PerformanceProfiler::stopProfile( 'Drupal - '.$action, 3 );

   		// Log request and response (or fault) as XML
		if( $debugMode ) { // check here since saveXML() calls below are expensive
			$this->logXmlRpc( $action, $this->rpcClient->getLastRequest()->saveXML(), true, 'XML' );
			$lastResponse = $this->rpcClient->getLastResponse();
			if( $lastResponse ) {
				if( $lastResponse->isFault() ) {
					$this->logXmlRpc( $action, $lastResponse->getFault()->saveXML(), null, 'XML' );
				} else {
					$this->logXmlRpc( $action, $lastResponse->saveXML(), false, 'XML' );
				}
			} else { // HTTP error
				$httpClient = $this->rpcClient->getHttpClient();
				$lastResponse = $httpClient->getLastResponse();
				if( $lastResponse ) {
					$message = $lastResponse->getMessage().' (HTTP '.$lastResponse->getStatus().')';
				} else if( $e ) {
					$message = $e->getMessage();
				} else {
					$message = 'unknown error';
				}
				$this->logXmlRpc( $action, $message, null, 'TXT' );
			}
		}

		// Log the response at PHP objects
		if( $debugMode ) {
			$this->logXmlRpc( $action, print_r($retVal,true), false, 'TXT' );
			LogHandler::Log( 'Drupal', 'DEBUG', 'Received service response: '.$action );
		}
		
		// Now the service I/O is logged above, throw exception in case of a fault.
		if( $e ) {
			LogHandler::Log( 'Drupal', 'ERROR', 'RPC call "'.$action.'" failed at Drupal URL "'.$this->drupalUrl.'".' );
			$lastResponse = $this->rpcClient->getLastResponse();
			if( $lastResponse ) {
				if( $lastResponse->isFault() ) {
					$fault = $lastResponse->getFault();
					$detail = $fault->getMessage();
					$code = $fault->getCode();
					if( $code ) {
						$detail .= ' ('.$code.')';
					}
				} else {
					$detail = null;
				}
			} else { // HTTP error
				$httpClient = $this->rpcClient->getHttpClient();
				$lastResponse = $httpClient->getLastResponse();
				if( $lastResponse ) {
					$detail = $lastResponse->getMessage().' (HTTP '.$lastResponse->getStatus().')';
				} else {
					$detail = $e->getMessage();
				}
			}
    		throw new BizException( 'ERR_PUBLISH', 'ERROR' /*ugly but needed*/, 
    								$detail, null, array('Drupal', $e->getMessage()) );
    	}

		if( isset($retVal['Errors']) && count($retVal['Errors']) > 0 ) {
			throw new BizException( 'ERR_PUBLISH', 'ERROR', /*ugly but needed*/
				null, null, array('Drupal', strip_tags($retVal['Errors'][0])) );
		}

    	return $retVal;
    }
    
    /**
     * Function used by callRpcService to log the XML-RPC request and response (or fault)
     * if the DEBUGLEVELS is set to 'DEBUG' and the OUTPUTDIRECTORY is set. 
     *
     * @param string $methodName
     * @param string $data
     * @param boolean $request TRUE for request, FALSE for response or NULL for error.
     * @param string $format 'XML' for RPC data or 'TXT' for PHP object structure
     */
	private function logXmlRpc( $methodName, $data, $request, $format )
	{
		// build file path for log file
		$sLogFolder = LogHandler::getLogFolder();
		$sLogFile = $sLogFolder.'xmlrpc/';
		if( !file_exists( $sLogFile ) ) {
			mkdir( $sLogFile, 0777 );
			chmod( $sLogFile, 0777 );
		}

		if( file_exists($sLogFile) ) {
			$microtime = explode(" ", microtime());
			$time = sprintf( '%03d', round($microtime[0]*1000) );
			$sLogFile .= date('His').'_'.$time.'_'.$methodName;

			$postfix = is_null($request) ? '_Err' : ($request ? '_Req' : '_Resp');
			$postfix .= ($format == 'XML') ? '.xml' : '.txt';
		
			// log request/response
			$file = fopen( $sLogFile.$postfix, 'a' );
			fwrite( $file, $data );
			fclose( $file );
		}
	}
	
	/**
	 * Get the node components of the chilren of the dossier. All other files than the type Article
	 * will be saved in the files array. These are uploaded as attachements in Drupal. Articles will be
	 * returned as elements and the author will be site according to the option writer in config_drupal.php.
	 *
	 * @param array $children
	 * @param writable array $textcomponents
	 * @param writable array $files
	 * @param writable author $author
	 */
	private function getNodeComponents( $children, &$textcomponents, &$files, &$author )
	{
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$hyperlinks = array();
		$texts = array();

		$foundComponents = array();
		$imageFound = false;

		$titleLabel = '';
		$bodyLabel = '';
		$teaserLabel = '';

		$options = $this->drupalSiteOptions;
		if(isset($options['components']) && is_array($options['components'])) {
			$titleLabel = $options['components']['title'];
			$bodyLabel = $options['components']['body'];
			$teaserLabel = $options['components']['teaser'];

			LogHandler::Log('Drupal' ,'DEBUG', 	"Title label: {$titleLabel}, body label: {$bodyLabel} and teaser label: {$teaserLabel}");
		}

		if(empty($titleLabel) || empty($bodyLabel) || empty($teaserLabel)) {
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('Drupal', BizResources::localize('ERR_DRUPAL_NO_COMPONENTS_MAPPING')));
		}

		foreach ($children as $child) {
			$format = $child->MetaData->ContentMetaData->Format;
			switch ($child->MetaData->BasicMetaData->Type) {
				case 'Article':
					// for Text, InCopy and HTML articles we parse the content, the rest treated as file.
					$incopy = ($format=='application/incopy' || $format=='application/incopyinx' || $format=='application/incopyicml');
					if( $incopy || $format=='text/plain' || $format=='text/html' || $format=='text/wwea' ) {
						$elements = $this->getArticleElements($child);
						
						foreach( $elements as $element ) { // BZ#25748 Re-designed the if-part below to set $author for -all- text components
							// New version of Content Station uses header instead of head.
							if( $element->Label == $titleLabel || ($incopy && $element->Label == 'head') ) {
								$drupalElemLabel = 'title';
							} else if( $element->Label == $bodyLabel || ($incopy && $element->Label == 'body') ) {
								$drupalElemLabel = 'body';
							} else if( $element->Label == $teaserLabel || ($incopy && $element->Label == 'intro') ) {
								$drupalElemLabel = 'teaser';
							} else {
								$drupalElemLabel = '';
							}
							if( $drupalElemLabel ) {
								if( !in_array( $drupalElemLabel, $foundComponents ) ) {
									$foundComponents[] = $drupalElemLabel;
								}
								if( empty($author) || $drupalElemLabel == 'body' ) { // body always overrules others, like teaser and head
									$author = array(
										'object_id' => $element->Article->MetaData->BasicMetaData->ID, 
										'creator'   => $element->Article->MetaData->WorkflowMetaData->Creator,
										'modifier'  => $element->Article->MetaData->WorkflowMetaData->Modifier );
								}
							}
						}
						$texts = array_merge($texts, $elements);
					} else {
						$fileExt = MimeTypeHandler::mimeType2FileExt( $format );
						$fileName = $child->MetaData->BasicMetaData->Name . $fileExt;
						$fileId = $this->uploadAttachment( $child->MetaData->BasicMetaData->ID, $fileName, 
								$this->getFileContent( $child, $this->askRenditionType( $child ) ),  
								$format, $child->MetaData->WorkflowMetaData->Version );
						$files[] = array(
							'name' 		=> $child->MetaData->BasicMetaData->Name,
							'type' 		=> $format,
							'ext' 		=> $fileExt,
							'enterprise_id' => $child->MetaData->BasicMetaData->ID,
							'iid' 		=> intval( $child->ExternalId ),
							'keywords' 	=> $child->MetaData->ContentMetaData->Keywords,
							'fid' 		=> intval( $fileId ) // fileId
							);
					}
					break;
				case 'Image':
					// TODO validate availability of files etc.
					$fileExt = MimeTypeHandler::mimeType2FileExt( $format );
					$fileName = $child->MetaData->BasicMetaData->Name . $fileExt;
					$fileId = $this->uploadAttachment( $child->MetaData->BasicMetaData->ID, $fileName, 
							$this->getFileContent( $child, $this->askRenditionType( $child ) ), 
							$format, $child->MetaData->WorkflowMetaData->Version );
					$files[] = array(
						'name' 		=> $child->MetaData->BasicMetaData->Name,
						'type' 		=> $format,
						'ext' 		=> $fileExt,
						'enterprise_id' => $child->MetaData->BasicMetaData->ID,
						'iid' 		=> intval( $child->ExternalId ),
						'keywords' 	=> $child->MetaData->ContentMetaData->Keywords,
						'fid' 		=> intval( $fileId )
					);
					// JPG, GIF and PNG are detected as image in Drupal now, not here

					/* Targeting images
					$objectId = $child->MetaData->BasicMetaData->ID;
					$shorUserName = BizSession::getShortUserName();
					// make sure image object is targetted too
					// check if target already exists because BizRelation::updateObjectRelations deletes the current one
					// and then externalid is removed
					$externalId = $this->getExternalId( $this->dossier->MetaData->BasicMetaData->ID, $objectId, $this->publishtarget );
					if (strlen( $externalId ) > 0) {
						// create object relation with the same target as the dossier
						$relation = new Relation( $this->dossier->MetaData->BasicMetaData->ID, $objectId, 'Contained', null, null, null, null, null, $this->publishtarget );
						BizRelation::updateObjectRelations( $shorUserName, array($relation) );
					}*/

					// Set the image found to true to indicate that at least one image is found
					$imageFound = true;
					break;
				case 'Layout':
					// Separate handler for the content layouts. This function returns a array with the files.
					$content = $this->getFileContentForLayouts($child, $this->askRenditionType($child));
					foreach( $content as $key => $file ) {
						$fileName = $child->MetaData->BasicMetaData->Name;
						if(count($content) > 1) {
							$fileName = $fileName . '_' . $key;
						}
						$fileName .= MimeTypeHandler::mimeType2FileExt($file['type']);

						$fileId = $this->uploadAttachment( $child->MetaData->BasicMetaData->ID, 
								$fileName, $file['file'], $file['type'], $child->MetaData->WorkflowMetaData->Version );
						$files[] = array(
							'name' 		=> $child->MetaData->BasicMetaData->Name,
							'type' 		=> $file['type'] . '_Layout',
							'ext' 		=> MimeTypeHandler::mimeType2FileExt( $file['type'] ),
							'enterprise_id' => $child->MetaData->BasicMetaData->ID,
							'iid' 		=> intval( $child->ExternalId ),
							'keywords' 	=> $child->MetaData->ContentMetaData->Keywords,
							'fid' 		=> intval( $fileId )
						);
					}
					break;
				case 'Audio':
					// TODO
					break;
				case 'Video':
					//TODO for now, assume Format is application/x-shockwave-flash and ext is .flv when format is empty
					$videoFormat = $child->MetaData->ContentMetaData->Format;
					$fileExt = MimeTypeHandler::mimeType2FileExt( $videoFormat );
					if( empty( $videoFormat ) ) {
						$videoFormat = 'application/x-shockwave-flash';
					}
					if( empty( $fileExt ) ) {
						$fileExt = '.flv';
					}
					$files[] = array(
						'name' 		=> $child->MetaData->BasicMetaData->Name,
						'type' 		=> $videoFormat,
						'ext' 		=> $fileExt,
						'contents'  => base64_encode( $this->getFileContent( $child, 'native' ) ),
						'enterprise_id' => $child->MetaData->BasicMetaData->ID,
						'iid' 		=> intval( $child->ExternalId ),
						'keywords' 	=> $child->MetaData->ContentMetaData->Keywords
					);
					break;
				case 'Hyperlink':
					$hyperlinks[] = array(
						$child->MetaData->BasicMetaData->Name => $child->MetaData->BasicMetaData->DocumentID 
					);
					break;
				default:
					$fileExt = MimeTypeHandler::mimeType2FileExt( $format );
					$fileName = $child->MetaData->BasicMetaData->Name . $fileExt;
					$fileId = $this->uploadAttachment( $child->MetaData->BasicMetaData->ID, $fileName, 
							$this->getFileContent( $child, $this->askRenditionType( $child ) ), 
							$format, $child->MetaData->WorkflowMetaData->Version );
					$files[] = array(
						'name' 		=> $child->MetaData->BasicMetaData->Name,
						'type' 		=> $format,
						'ext' 		=> $fileExt,
						'enterprise_id' => $child->MetaData->BasicMetaData->ID,
						'iid' 		=> intval( $child->ExternalId ),
						'keywords' 	=> $child->MetaData->ContentMetaData->Keywords,
						'fid' 		=> intval( $fileId )
					);
					break;
			}
		}

		//TEMP method to determine head
		if( !in_array('title', $foundComponents) && count($texts) > 1) {
			// sort texts on length
			usort($texts, array('Drupal_PubPublishing', 'getNodeComponents_textsCmp'));
			// change label
			$texts[0]->Label = $titleLabel;
			// if more than 2 texts then assume text 2 is teaser
			if (count($texts) > 2){
				$texts[1]->Label = $teaserLabel;
			}
		}

		// Create a new array to store the required components
		$reqComponents = array();

		// Set a boolean if an image is required
		$imageRequired = false;

		// Get the enterprise issue information
		//$issue = $this->enterpriseIssue;
		$extraMetaDatas = $this->enterpriseIssueExtraMetaData;
		
		foreach($extraMetaDatas as $key => $extraMetaData) {
			// If the property is equal to the required components string then continue
			if($key == "drupal_required_components") {
				// If the values is actually bigger than 0 and the first value isn't empty
				if(count($extraMetaData) > 0 && !empty($extraMetaData[0])) {
					$reqComponents = $extraMetaData;
				}
			}

			if($key == "drupal_img_required") {
				// If the values is actually bigger than 0 and the first value isn't empty and the value is 1
				if(count($extraMetaData) > 0 && !empty($extraMetaData[0]) && $extraMetaData[0]) {
					// Set image required to true
					$imageRequired = true;
				}
			}
		}

		// If the requiredComponents array is empty follow the normal process to validate in Drupal else check
		if(!empty($reqComponents)) {
			// Diff the required components against the found components
			$diff = array_diff($reqComponents, $foundComponents);

			// If there is one or more components aren't found throw a biz exception and show which components are missing.
			if(!empty($diff)) {
				$notFound = implode(', ', $diff);
				$localized = BizResources::localize('ERR_DRUPAL_REQUIRED_COMPONENTS_NOT_FOUND', true, array($notFound));
				throw new BizException('ERR_PUBLISH', 'Server', '', null, array('Drupal', $localized));
			}
		}

		// Throw a biz exception when an image is required but not found.
		if($imageRequired && !$imageFound) {
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('Drupal', BizResources::localize('ERR_DRUPAL_IMAGE_REQUIRED')));
		}

		// return texts as rpc values
		foreach( $texts as $text ) {
			$textcomponents[] = array(
				$text->Label => $text->Content,
				'category'   => $text->Article->MetaData->BasicMetaData->Category->Name,
				'writer'     => $text->Article->MetaData->WorkflowMetaData->Creator,
				'keywords'   => $text->Article->MetaData->ContentMetaData->Keywords
			);
		}
	}

	/**
	 * Function for the usort functionality. Look for the ContentLength of the given objects
	 * and decide which one is bigger. 
	 *
	 * @param Object $a
	 * @param Object $b
	 * @return int 0 - equal, -1 - $b bigger than $a, 1 - $a bigger than $b
	 */
	private static function getNodeComponents_textsCmp($a, $b)
	{
		if ($a->ContentLength === $b->ContentLength){
			return 0;
		}
		return $a->ContentLength < $b->ContentLength ? -1 : 1;
	}

	/**
	 * Gets the elements for an Article object. This function can handle
	 * incopy articles, text/wwea articles and normal text/html articles.
	 *
	 * @param Object $article
	 * @return array with the elements
	 */
	private function getArticleElements( $article )
	{
		$elements = array();
		$content = $this->getFileContent($article, 'native');
		$format = $article->MetaData->ContentMetaData->Format;
		switch ($format){
			case 'application/incopy':
			case 'application/incopyinx':
			case 'application/incopyicml':
				// Convert article into XHTML frames (tinyMCE compatible)
				require_once BASEDIR . '/server/appservices/textconverters/TextConverter.class.php';
				$fc = TextConverter::createTextImporter($format);
				$xFrames = array();
				$stylesCSS = '';
				$stylesMap = '';
				$domVersion = '0';
				$artDoc = new DOMDocument();
				$artDoc->loadXML($content);
				$fc->enableInlineImageProcessing(); // Enable export Inline image processing
				$fc->importBuf($artDoc, $xFrames, $stylesCSS, $stylesMap, $domVersion);
				// heavy debug only: file_put_contents( OUTPUTDIRECTORY.'article1.txt', print_r($xFrames,true) );
				// handle multi element article
				foreach ($xFrames as $xFrame){
					$content = '';
					$data = mb_convert_encoding($xFrame->Content, 'HTML-ENTITIES', 'UTF-8');
					$data = str_replace( "\n", '', $data );
					$artDoc = new DOMDocument();
					$artDoc->loadHTML($data);
					$xpath = new DOMXPath($artDoc);
					$bodies = $xpath->query('/html/body');
					$body = '';
					if ($bodies->length > 0){
						$childs = $bodies->item(0)->childNodes;
						foreach ($childs as $child){
							$content .= $artDoc->saveXML($child);
							$content = trim($content);
						}
					}
							
					$element = new stdClass();
					// BZ#30926 Just respect the component name as defined by user.
					// This makes it possible to map an Enterprise component 'myhead' to drupal field 'title'.
					$element->Label = $xFrame->Label;
					$element->Content = $content;
					$element->ContentLength = strlen($content);
					$element->Article = $article;
					
					if(!empty($element->Content)) {
						$elements[] = $element;
					}
				}
				break;
			case 'text/wwea':
				$eaDoc = new DOMDocument();
				$eaDoc->loadXML($content);

				$xpath = new DOMXPath($eaDoc);
				$xpath->registerNamespace('ea', "urn:EnterpriseArticle");

				foreach($xpath->query('/ea:article/ea:component') as $component) {
					$label = "";
					foreach($component->getElementsByTagNameNS("urn:EnterpriseArticle", 'name') as $name) {
						$label = $name->nodeValue;
					}

					$content = "";
					foreach($component->getElementsByTagNameNS("urn:EnterpriseArticle", 'data') as $text) {
						$data = $text->nodeValue;
						$data = trim($data);
						if(!empty($data)) {
							$artDoc = new DOMDocument();
							$data = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');
							$artDoc->loadHTML($data);
							$xpath1 = new DOMXPath($artDoc);
							$bodies = $xpath1->query('/html/body');
							$body = '';
							if ($bodies->length > 0){
								$childs = $bodies->item(0)->childNodes;
								foreach ($childs as $child){
									$content .= $artDoc->saveXML($child);
									$content = trim($content);
								}
							} else {
								$content = $data;
							}
						}
					}

					if(!empty($content)) {
						$element = new stdClass();

						$element->Label = $label;
						$element->Content = $content;
						$element->ContentLength = strlen($content);
						$element->Article = $article;

						$elements[] = $element;
					}
				}
				break;
			case 'text/html':
				$element = new stdClass();
				$element->Label = 'body';
				// only get body content from html
				$artDoc = new DOMDocument();
				$content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
				$artDoc->loadHTML($content);
				$xpath = new DOMXPath($artDoc);
				$bodies = $xpath->query('/html/body');
				$body = '';
				if ($bodies->length > 0){
					$childs = $bodies->item(0)->childNodes;
					foreach ($childs as $child){
						$body .= $artDoc->saveXML($child);
					}
				} else {
					$body = $content;
				}
				$element->Content = $body;
				$element->ContentLength = strlen($body);
				$element->Article = $article;
				// find label
				if (! empty($article->Elements)) {
					if ($article->Elements[0]->Name != ''){
						// only filled labels
						$element->Label = $article->Elements[0]->Name;
					}
				}

				if(!empty($element->Content)) {
					$elements[] = $element;
				}
				break;
			default:
				$element = new stdClass();
				$element->Label = 'body';
				$element->Content = $content;
				$element->ContentLength = strlen($content);
				$element->Article = $article;
				// find label
				if (! empty($article->Elements)) {
					// TO DO - handle multi-element articles
					if ($article->Elements[0]->Name != ''){
						// only filled labels
						$element->Label = $article->Elements[0]->Name;
					}
				}

				if(!empty($element->Content)) {
					$elements[] = $element;
				}
		}

		return $elements;
	}

	/**
	 * Returns a Field object from a xmlrpc value object
	 *
	 * @param string $key
	 * @param string $type
	 * @param mixed $value
	 * @return Field or null if $value couldn't be converted
	 */
	private static function getField( $key, $type, $value )
	{
		$result = null;
		if( !is_null( $value ) ) {
			switch( $type ) {
				case 'int':
					$value = array(intval($value));
					break;
				case 'double':
					$value = array(doubleval($value));
					break;
				case 'string':
					$value = array(strval($value));
					break;
				case 'multistring':
					// Create a string with double break rules.. between them, to display correctly in CS
					$value = strval(implode("<br /><br />", $value));
					$value = array(nl2br($value));
					$type = 'string';
				default:
					break;
			}
			$result = new PubField( $key, $type, $value );
		}
		//TODO BizException if $fieldValue is null?

		return $result;
	}

	/**
	 * Upload a file with a standard HTTP file upload to Drupal.
	 *
	 * @param int 	 $objectId	  Enterprise object id
	 * @param string $fileName    filename
	 * @param string $content     file contents
	 * @param string $contentType file content type
	 * @param string $version	  file version in Enterprise
	 * @return int                file id
	 */
	private function uploadAttachment( $objectId, $fileName, $content, $contentType, $version )
	{
		// Check if this version is already saved in Drupal
		LogHandler::Log( 'Drupal', 'DEBUG', 'Check if file exists' );
		$fileId = $this->checkFileExists( $objectId, $fileName, $contentType, $version );

		// if fileId is not null then the version is already saved in drupal. return this file id
		if( !is_null($fileId) ) {
			return $fileId;
		}

		LogHandler::Log( 'Drupal', 'DEBUG', 'File does not exists or is a newer version. Sending the file.' );

		// if not found in Drupal upload the file
		require_once 'Zend/Http/Client.php';
		$client = $this->createHttpClient( $this->getUploadUri(), $this->drupalCertificate );
		$client->setParameterPost( 'username', $this->drupalUsername );
		$client->setParameterPost( 'password', base64_encode($this->drupalPassword) );
		$client->setFileUpload( $fileName, 'files[upload]', $content, $contentType );
		
		// Enable the nex statements to enable debugging in NetBeans
		//$client->setParameterGet('XDEBUG_SESSION_START', 'netbeans-xdebug');		
		
		// Enable the nex statements to enable debugging in Zend Studio
		//$client->setParameterGet('debug_port', '10137');
		//$client->setParameterGet('start_debug', '1');
		//$client->setParameterGet('debug_host', '127.0.0.1');
		//$client->setParameterGet('debug_stop', '1');

		$reponse = $client->request('POST');
		if( $reponse->isError() ) {
			throw new BizException('ERR_PUBLISH', 'Server', '', '', array('Drupal', BizResources::localize('ERR_DRUPAL_UPLOAD_FAILED')));
		}
		$dom = new DOMDocument();
		$dom->loadXML( $reponse->getBody() );
		$xpath = new DOMXPath( $dom );
		$fidNode = $xpath->query('//fid')->item(0);
		$fileId = $fidNode ? $fidNode->nodeValue : 0;
		LogHandler::Log( 'Drupal', 'DEBUG', 'File send to drupal. FileId: ' . $fileId );
		return $fileId;
	}

	/**
	 * Check if the file already exists in Drupal
	 *
	 * @param int	 $objectId    Enterprise object id
	 * @param string $fileName    filename
	 * @param string $content     file contents
	 * @param string $contentType file content type
	 * @param string $version     file version in Enterprise
	 * @param string $type        file type in Enterprise
	 * @return array              file information
	 */
	private function checkFileExists( $objectId, $filename, $contentType, $version )
	{
		$publishedVersion = '';
		if(!is_null($this->dossier)) {
			foreach( $this->dossier->Relations as $relation ) {
				foreach( $relation->Targets as $target ) {
					if( $target->PubChannel->Id == $this->publishtarget->PubChannelID && 
							$target->Issue->Id == $this->publishtarget->IssueID && 
							$relation->Child == $objectId ) {
						$publishedVersion = $target->PublishedVersion;
						break 2; // Break once found the correct target
					}
				}
			}
		}

		LogHandler::Log( 'Drupal', 'DEBUG', "Current version: $version published version: $publishedVersion" );
		$fileId = null;
		if(!empty($publishedVersion) && (trim($publishedVersion) == trim($version))) {
			LogHandler::Log( 'Drupal', 'DEBUG', 'Look for the file in Drupal.' );
			$this->startDrupal();
			$response = $this->callRpcService( 'enterprise.getFileId', array(
				array(
					'filename'    => $filename,
					'contentType' => $contentType,
					'version'     => $publishedVersion,
					'nodeId'      => intval( $this->dossier->ExternalId )
				)
			));
			if( isset($response[0]['fileid']) ) {
				$fileId = $response[0]['fileid'];
			}
			if( LogHandler::debugMode() ) {
				$details = "objectId: $objectId, filename: $filename, contentType: $contentType, version: $version";
				if( empty($fileId) ) { // just checking, so not an error
					LogHandler::Log( 'Drupal', 'DEBUG', 'Did not find the file in Drupal for '.$details );
				} else {
					LogHandler::Log( 'Drupal', 'DEBUG', 'Found file id "'.$fileId.'" in Drupal for '.$details );
				}
			}
		}
		return $fileId;
	}

	/**
	 * Return the file upload uri.
	 *
	 * @return string
	 */
	private function getUploadUri()
	{
		//TODO get from Drupal server and cache it
		return $this->drupalUrl . 'index.php?q=ww_enterprise/upload';
	}

	/**
	 * Sets the publish target. Needed in Drupal_AdminProperties.
	 *
	 * @param PubPublishTarget $target
	 */
	public function setPublishTarget( $target )
	{
		$this->publishtarget = $target;
	}

	/**
	 * Sets the publish dossier. Needed in Drupal_AdminProperties.
	 *
	 * @param integer $dossier
	 */
	public function setPublishDossier( $dossier )
	{
		$this->dossier = $dossier;
	}
		
	/**
	 * Gets and returns the contenttypes from the Drupal side. Needed in Drupal_AdminProperties.
	 *
	 * @return array with contenttypes
	 */
	public function getContentTypes()
	{
		$this->startDrupal();
		$contentTypes = array();
		$response = $this->callRpcService( 'enterprise.getContentTypes', array(
			array(
				'username' => $this->drupalUsername, 
				'password' => base64_encode( $this->drupalPassword )
			)
		));

		if( !is_null($response) ) { // no error?
			if( $response ) foreach( $response as $type ) {
				$contentTypes[] = array(
					'type'        => $type['type'], 
					'name'        => $type['name'], 
					'description' => $type['description']
				);
			}
		} else {
			$contentTypes[] = array(
				'type'        => '', 
				'name'        => 'Error getting data from Drupal', 
				'description' => ''
			);
		}
		return $contentTypes;
	}

	/**
	 * Gets and returns the vocabularies available for the given content type. Needed in Drupal_AdminProperties.
	 *
	 * @param string $contentType
	 * @return array with vocabularies
	 */
	public function getVocabularies($contentType)
	{
		$this->startDrupal();
		$response = $this->callRpcService( 'enterprise.getVocabularies', array( 
			array( 
				'username' => $this->drupalUsername, 
				'password' => base64_encode( $this->drupalPassword)
			), 
			$contentType
		));
		$vocabularies = array();
		if( !is_null($response) ) { // no error?
			if( $response ) foreach( $response as $voc ) {
				$vocabularies[] = array(
					'vid'    => $voc['vid'],
					'name'   => $voc['name'], 
					'description' => $voc['description'], 
					'module' => $voc['module']
				);
			}
		} else {
			$vocabularies[] = array(
				'vid'    => '',
				'name'   => 'Error getting data from Drupal', 
				'description' => '', 
				'module' => ''
			);
		}
		return $vocabularies;
	}
	
	/**
	 * Gets the terms for the given vocabularies. Needed in Drupal_AdminProperties.
	 *
	 * @param array $vocabularies
	 * @return array with terms
	 */
	public function getTerms( $vocabularies )
	{
		$this->startDrupal();
		$response = $this->callRpcService( 'enterprise.getTerms', array( 
			array(
				'username' => $this->drupalUsername,
				'password' => base64_encode( $this->drupalPassword )
			),
			$vocabularies
		));
		
		$terms = array();
		if( !is_null($response) ) { // no error?
			$terms = $response;
		} else {
			$terms['errorDrupal'] = array( 'error' => 'Error getting data from Drupal' );
		}
		return $terms;
	}

	/**
	 * Get the external id for the given dossier id, child id and target.
	 *
	 * @param int $dossierId
	 * @param int $childId
	 * @param PubPublishTarget $publishTarget
	 * @return int external result
	 */
	/*private function getExternalId( $dossierId, $childId, $publishTarget )
	{
		$result = '';
		$row = DBBase::getRow( 'objectrelations',
			'`parent` = ' . intval( $dossierId ) . ' AND `child` = ' . intval( $childId ) . ' AND `type` = \'Contained\'' );
		if ($row) {
			$row = DBBase::getRow( 'targets',
				'`objectrelationid` = ' . intval( $row['id'] ) . ' AND `channelid` = ' . intval(
					$publishTarget->PubChannelID ) . ' AND `issueid` = ' . intval( $publishTarget->IssueID ) );
			$result = $row['externalid'];
		}

		return $result;
	}*/

	/**
	 * Gets the short name and fullname for the author. If the option writer in config_drupal.php is set
	 * choose this writer else returns the publisher.
	 *
	 * @param string $author
	 * @return array of user(name) and fullname
	 */
	private function getAuthorShortname( $author )
	{
		$options = $this->drupalSiteOptions;
	    $writerOption = (isset($options['writer'])) ? $options['writer'] : '';

		if( $writerOption == 'writer' || $writerOption == 'modifier' ) {
			require_once BASEDIR.'/server/dbclasses/DBQuery.class.php';
			$dbdriver = DBDriverFactory::gen();
			$objectstable = $dbdriver->tablename('objects');
			$sqlarray = array();
			$sqlarray['from'] = "/*FROM*/ " . "FROM $objectstable o ";
			$sqlarray['select'] = "/*SELECT*/ SELECT o.`creator`, o.`modifier` ";
			$sqlarray['joins'] = "";
			$object = DBQuery::getObjectRow( $author['object_id'], $sqlarray );
		}

	    switch( $writerOption ) {
	    	case 'none':
	    		$user = '';
    			$fullname = '';
	    		break;
    		case 'writer':
    			$user = $object['creator'];
    			$fullname = $author['creator'];
    			break;
    		case 'modifier':
    			$user = $object['modifier'];
    			$fullname = $author['modifier'];
    			break;
    		case 'publisher':
    		default:
		    	require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
   				$user = BizSession::getUserInfo('user');
   				$fullname = BizSession::getUserInfo('fullname');
    			break;
    	}
		return array(
    		'user'     => $user,
    		'fullname' => $fullname
    	);
	}

	/**
	 * Resolve the site mapping MetaData names to actual values.
	 *
	 * @return array
	 */
	private function resolveMappings()
	{
		$mapping = $this->drupalSiteMapping;
		$resolved = array();
		foreach( $mapping as $map => $mapped ) {
			if( !empty($mapped) ) {
				if( $map == 'taxonomy' ) {
					$taxonomyResolved = array();
					if( is_array($mapped) ) {
						foreach( $mapped as $taxonomy => $field ) {
							if( !empty($field) ) {
								$value = $this->getMetadataValue($field);
								if(!is_null($value)) {
									$taxonomyResolved[$taxonomy] = $value;
								}
							}
						}
					}
					if( !empty($taxonomyResolved) ) {
						$resolved[$map] = $taxonomyResolved;
					}
				} else {
					$value = $this->getMetadataValue($mapped);
					$resolved[$map] = $value;
				}
			}
		}
		return $resolved;
	}

	/**
	 * Get the value of an MetaData property
	 *
	 * @param string $name
	 * @return string
	 */
	private function getMetadataValue($name)
	{
		$dossier = $this->dossier;

		foreach($dossier->MetaData as $type => $meta) {
			if(!empty($meta)) {
				// If there are custom metadata properties take care of them differently
				if($type == "ExtraMetaData") {
					foreach($meta as $data) {
						$searchfor = (substr($name, 0, 2) != 'C_') ? 'C_' . $name : $name;
						if($data->Property == $searchfor) {
							$value = (count($data->Values) != 1) ? implode(',', $data->Values) : reset($data->Values);
							return $value;
						}
					}
				} else {
					foreach($meta as $key => $data) {
						if($key == $name && (is_scalar($data) || is_array($data))) {
							$value = (is_array($data)) ? implode(',', $data) : $data;
							return $value;
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Get the site options as xmlrpcval values so it can be send directly to Drupal.
	 *
	 * @return array
	 */
	private function getXmlRpcSiteOptions()
	{
		$options = $this->drupalSiteOptions;
		$mapping = $this->resolveMappings();
		return array_merge( $options, $mapping );
	}
	
	/**
	 * Returns the site options.
	 *
	 * @return array
	 */
	public function getSiteOptions()
	{
		return $this->drupalSiteOptions;
	}
	
	/**
	 * Get the values from drupal to show in the GetDialog functionality
	 *
	 * @param int $nodeId
	 * @param array $properties
	 * @return array
	 */
	public function getCurrentDataForGetDialog( $nodeId, $properties )
	{
		$this->startDrupal();
		
		$contentType = '';
		foreach( $this->enterpriseIssueExtraMetaData as $key => $md ) {
			if($key == 'drupal_content_type') {
				$contentType = $md[0];
				break;
			}
		}

		$response = $this->callRpcService( 'enterprise.getCurrentDataForGetDialog', array(
			array(
				'username' => $this->drupalUsername, 
				'password' => base64_encode($this->drupalPassword)
			),
			$nodeId,
			$contentType,
			$properties
		));
		return $response;
	}
	
	/**
	 * Parse the optional fields of the published dialog
	 *
	 * @param array $fields the fields array
	 */
	public function getDialogPublishFields( $fields ) 
	{
		$this->publishFieldsMetaData = array();
		foreach( $fields as $field ) {
			$key = $field->Key;
			
			// If the field type is list resolve the options to the english key defined in Drupal_WflGetDialog2
			if( $field->Type == 'list' ) {
				$this->resolveDialogValuesFromLanguage( $key, $field->Values );
			}
			$key = preg_replace("/^C_DIALOG_DRUPAL_/", "DIALOG_", $key);
			$key = strtolower($key);
			$this->publishFieldsMetaData[$key] = $field->Values;
		}
	}
	
	/**
	 * For list fields, resolve the translated options to the english key.
	 *
	 * @param string $key
	 * @param array $values
	 */
	public function resolveDialogValuesFromLanguage( $key, &$values )
	{
		// Get the options from the Drupal_WflGetDialog2 class. So we have one point of definition.
		require_once dirname(__FILE__) . '/Drupal_WflGetDialog2.class.php';
		$listOptions = Drupal_WflGetDialog2::getOptions();
				
		if(array_key_exists($key, $listOptions)) {
			$optionValues = $listOptions[$key]['values'];
			foreach($values as &$value) {
				foreach($optionValues as $option => $translation) {
					if($translation == $value) {
						$value = $option;
						break;
					}
				}
			}
		}
	}

	/**
	 * Ensure that the necessary PubFields are stored as part of the PublishHistory.
	 *
	 * @return string[] An array of string keys of the PubFields to be stored in the PublishHistory.
	 */
	public function getPublishDossierFieldsForDB()
	{
		// Ensure that the URL for the Drupal Node is stored in the PublishHistory.
		return array('URL');
	}
}
