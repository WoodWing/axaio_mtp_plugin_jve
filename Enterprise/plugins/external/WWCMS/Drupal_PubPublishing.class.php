<?php
/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class with static functions called to do publishing to an external publishing system.
 * For example: .
**/

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/lib/xmlrpc.inc';

class Drupal_PubPublishing extends PubPublishing_EnterpriseConnector
{
	/**
	 * @var xmlrpc_client
	 */
	private $RPCClient = null;

	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }

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
	public function publishDossier(&$dossier, &$objectsindossier, $publishtarget)
	{
		self::startDrupal();
		$saveResult = $this->nodeSave($publishtarget, $dossier,$objectsindossier,"1"); // Std we push to frontpage

		$dossier->ExternalId = $saveResult->me['struct']['nodeId']->scalarval();
		// map Drupal ids to Enterprise objects
		if (isset($saveResult->me['struct']['idArray']->me['struct'])){
			$idArray = $saveResult->me['struct']['idArray']->me['struct'];
			foreach ($objectsindossier as $object){
				if (isset($idArray[$object->MetaData->BasicMetaData->ID])){
					$object->ExternalId = $idArray[$object->MetaData->BasicMetaData->ID]->scalarval();
				}
			}
		}

		$result = array();
		$result[] = self::getNewPubField('URL','string',array(DRUPAL_URL . '?q=node/' . $dossier->ExternalId));
		return $result;
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
	public function updateDossier(&$dossier, &$objectsindossier, $publishtarget)
	{
		self::startDrupal();
		$updateResult = $this->nodeUpdate($publishtarget, $dossier->ExternalId, $dossier,$objectsindossier);
		// map Drupal ids to Enterprise objects
		if (isset($updateResult->me['struct']['idArray']->me['struct'])){
			$idArray = $updateResult->me['struct']['idArray']->me['struct'];
			foreach ($objectsindossier as $object){
				if (isset($idArray[$object->MetaData->BasicMetaData->ID])){
					$object->ExternalId = $idArray[$object->MetaData->BasicMetaData->ID]->scalarval();
				}
			}
		}

		$result = array();
		$result[] = self::getNewPubField('URL','string',array(DRUPAL_URL . '?q=node/' . $dossier->ExternalId));
		return $result;
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
	public function unpublishDossier ($dossier, $objectsindossier, $publishtarget)
	{
		$publishtarget = $publishtarget; // prevent analyzer warning
		self::startDrupal();

		$images = array();
		foreach ($objectsindossier as $object){
			switch ($object->MetaData->BasicMetaData->Type ){
				case 'Image':
					$images[] = new xmlrpcval($object->ExternalId, 'string');
					break;
			}
		}

		$xmlrpc_msg = new xmlrpcmsg('enterprise.nodeUnpublish',
			array(
		        new xmlrpcval( array(
		        	'username'=>new xmlrpcval(DRUPAL_USERNAME, 'string'),
		        	'password' => new xmlrpcval(base64_encode(DRUPAL_PASSWORD), 'string')
		        ), 'struct'),
				new xmlrpcval($dossier->ExternalId, 'int') ,
				new xmlrpcval($images, 'array')
			)
		);
		$rpcval = $this->sendMessage($xmlrpc_msg);
		$rpcval = $rpcval; // prevent analyzer warning

		$externalid = $dossier->ExternalId;
		$dossier->ExternalId = - 1 * $externalid;

		return null;
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
	public function requestPublishFields($dossier, $objectsindossier, $publishtarget)
	{
		$objectsindossier = $objectsindossier; // prevent analyzer warning
		$publishtarget = $publishtarget; // prevent analyzer warning
		self::startDrupal();

		$result = array();

		$xmlrpc_msg = new xmlrpcmsg('enterprise.nodeGetInfo',
			array(
				new xmlrpcval($dossier->ExternalId, 'int') ,
			)
		);
		$xmlrpcval = $this->sendMessage($xmlrpc_msg);

		// convert XML RPC values to array of PubField
		if (isset($xmlrpcval->me['struct']) && is_array($xmlrpcval->me['struct'])){
			$map = array('Views' => 'int', 'Rating' => 'double', 'Raters' => 'int', 'CommentsCount' => 'int', 'Comments' => 'multistring', 'URL' => 'string');
			foreach ($xmlrpcval->me['struct'] as $fieldKey => $fieldxmlrpcval){
				if (isset($map[$fieldKey])){
					$result[] = self::xmlrpcGetField($fieldKey, $map[$fieldKey], $fieldxmlrpcval);
				} else {
					// all other values as string
					$result[] = self::xmlrpcGetField($fieldKey, 'string', $fieldxmlrpcval);
				}
			}
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
	 * @return array of PubFields containing information from Drupal
	 */
	public function getDossierURL($dossier, $objectsindossier, $publishtarget)
	{
		$objectsindossier = $objectsindossier; // prevent analyzer warning
		$publishtarget = $publishtarget; // prevent analyzer warning
		return DRUPAL_URL . '?q=node/' . $dossier->ExternalId;
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
	 *
	**/
	public function previewDossier(&$dossier, &$objectsindossier, $publishtarget)
	{
		self::startDrupal();
		$saveResult = $this->nodeSave($publishtarget, $dossier,$objectsindossier, '0', '0');

		$dossier->ExternalId = $saveResult->me['struct']['nodeId']->scalarval();
		$url = '';
		if (isset($saveResult->me['struct']['url'])){
			$url = $saveResult->me['struct']['url']->scalarval();
		} else {
			// build url ourself
			$url = DRUPAL_URL . '?q=ww_enterprise/' . $dossier->ExternalId;
		}
		// map Drupal ids to Enterprise objects
		if (isset($saveResult->me['struct']['idArray']->me['struct'])){
			$idArray = $saveResult->me['struct']['idArray']->me['struct'];
			foreach ($objectsindossier as $object){
				if (isset($idArray[$object->MetaData->BasicMetaData->ID])){
					$object->ExternalId = $idArray[$object->MetaData->BasicMetaData->ID]->scalarval();
				}
			}
		}

		$result = array();
		$result[] = self::getNewPubField('URL','string',array($url));
		return $result;
	}

	/**
	 * Test configuration by calling enterprise.testConfig in Drupal
	 *
	 * @return array with keys "Errors" and "Warnings"
	 */
	public function testConfig()
	{
		self::startDrupal();
		$xmlrpc_msg = new xmlrpcmsg('enterprise.testConfig',
			array(
		        new xmlrpcval( array(
		        	'username'=>new xmlrpcval(DRUPAL_USERNAME, 'string'),
		        	'password' => new xmlrpcval(base64_encode(DRUPAL_PASSWORD), 'string')
		        ), 'struct'),
			)
		);
		$result = array('Errors' => array(), 'Warnings'=>array());
		$xmlrpcval = $this->sendMessage($xmlrpc_msg);
		$valueArray = $xmlrpcval->getval();
		if (isset($valueArray['Errors'])){
			for ($i = 0, $count = count($valueArray); $i < $count; $i++){
				$result['Errors'][] = $valueArray['Errors'][$i]->getval();
			}
		}

		return $result;
	}

	protected function startDrupal()
	{
		$drupalServer = DRUPAL_URL; // this is the path to Drupal server root
		$servicesPath = "xmlrpc.php"; // this is the path to Services module within Drupal directory tree
		$debugLevel = 0; // Debug level: 0 - show none, 1 - show response only, 2 - show request and response
		if ( LogHandler::debugMode() ) {
			$debugLevel = 2;
		}

		$this->RPCClient = new xmlrpc_client($drupalServer.$servicesPath);
		$this->RPCClient->request_charset_encoding = 'UTF-8';
		$this->RPCClient->setDebug($debugLevel);
		//TODO check if rpc client is valid?
	}

	protected function askRenditionType($object)
	{
		// default is native;
		$rendition = 'native';
		switch ($object->MetaData->BasicMetaData->Type) {
			case 'Image':
			{
				// For PDF, EPS, AI we use native (will be an attachment), otherwise preview (will be an image)
				if( $object->MetaData->ContentMetaData->Format != 'application/pdf' && 
					$object->MetaData->ContentMetaData->Format != 'application/postscript' && 
					$object->MetaData->ContentMetaData->Format != 'application/illustrator' ) {
					$rendition = 'preview';
				}
				break;
			}
			case 'Layout':
			{
				$rendition = 'output';
				break;
			}
		}
		return $rendition;
	}

    /**
    * Saves a node under given News ID
    *
    * @param int $newsId The News ID
    *
    * @return object
    * Returns a Drupal node ID
    *
    */
    // public function nodeSave($newsId, $promote) {
    private function nodeSave( $publishtarget, $dossier, $children, $promote, $publish = '1' )
    {
      	$textcomponents = array();
        $images = array();
        $videos = array();
        $audios = array();
        $hyperlinks = array();
        $fields = '';

		$this->nodePrepareAndValidate( $publishtarget, $dossier, $children, $textcomponents, $images, $videos, $fields );

        $xmlrpc_msg = new xmlrpcmsg('enterprise.nodePublish',
	        array (
		        new xmlrpcval( array(
		        	'username'=>new xmlrpcval(DRUPAL_USERNAME, 'string'),
		        	'password' => new xmlrpcval(base64_encode(DRUPAL_PASSWORD), 'string')
		        ), 'struct'),
		        $fields,
				new xmlrpcval( $textcomponents, 'array'),
				new xmlrpcval( $images, 'array'),
				new xmlrpcval( $videos, 'array'),
				new xmlrpcval( $audios, 'array'),
				new xmlrpcval( $hyperlinks, 'array'),
		        new xmlrpcval($promote, 'int'),
		        new xmlrpcval($publish, 'int')
        ));

        $rpcval = $this -> sendMessage($xmlrpc_msg);
        $valueArray = $rpcval->getval();
        if (isset($valueArray['Errors'])){
        	// for now, only pick first error
        	throw new BizException('ERR_PUBLISH', 'Server', '', null, array('Drupal', strip_tags($valueArray['Errors'][0]->getval())));
        }
        return $rpcval;
    }

    /**
    * Updates a node under given Node ID
    *
    * @param int $newsId The News ID
    *
    * @return object
    * Returns a Drupal node ID
    *
    * TBD: media content update
    *
    */
    private function nodeUpdate ( $publishtarget, $nid, $dossier, $children )
    {
      	$textcomponents = array();
        $images = array();
        $videos = array();
        $fields = '';

		$this->nodePrepareAndValidate( $publishtarget, $dossier, $children, $textcomponents, $images, $videos, $fields );

        $xmlrpc_msg = new xmlrpcmsg('enterprise.nodeUpdate',
	        array (
		        new xmlrpcval( array(
		        	'username'=>new xmlrpcval(DRUPAL_USERNAME, 'string'),
		        	'password' => new xmlrpcval(base64_encode(DRUPAL_PASSWORD), 'string')
		        ), 'struct'),
		        new xmlrpcval($nid, 'int'),
		        $fields,
		        new xmlrpcval( $textcomponents, 'array'),
		        new xmlrpcval( $images, 'array'),
		        new xmlrpcval( $videos, 'array'),
        	)
        );
        $rpcval = $this -> sendMessage($xmlrpc_msg);
        $valueArray = $rpcval->getval();
        if (isset($valueArray['Errors'])){
        	// for now, only pick first error
        	throw new BizException('ERR_PUBLISH', 'Server', '', null, array('Drupal', strip_tags($valueArray['Errors'][0]->getval())));
        }
        return $rpcval;
    }
    /**
    * Sends a message to XML-RPC server
    *
    * @param object $message The given message
    *
    * @return xmlrpcval
    * Returns xmlrpcval object
    *
    */
    private function sendMessage($message)
    {
    	$method = substr(DRUPAL_URL, 0, strpos(DRUPAL_URL, ':'));
	LogHandler::Log( 'Drupal', 'DEBUG','Pre RPC' );
        $xmlrpc_resp = $this->RPCClient->send($message, 60/*timeout*/, $method);
	LogHandler::Log( 'Drupal', 'DEBUG','Post RPC' );

		if ($xmlrpc_resp->faultCode() <> 0){
			throw new BizException('ERR_PUBLISH', 'Server', DRUPAL_URL, null, array('Drupal', $xmlrpc_resp->faultString()));
		}
        return $xmlrpc_resp->value();
    }

    /**
    * Validates if all is fine to publish to Web and collects necessary data
    *
    * @param int $newsId The News ID
    *
    * @return nothing, fills in reference params.
    * Throws BizException in case validation fails.
    */
	private function nodePrepareAndValidate( $publishtarget, $dossier, $children, &$textcomponents, &$images, &$videos, &$fields )
	{
		$this->getNodeComponents($children, $textcomponents, $images, $videos);

		$subCategory = '';
		$subSubCategory = '';
		$tagsArray = array();
		$vocabularyArray = array();
		$prodVersion = '';
		$extraMetaData = $dossier->MetaData->ExtraMetaData;
		foreach( $extraMetaData as $md ) {
			if( $md->Property == 'C_SUBCATEGORY' ) {
				$subCategory = trim( $md->Values[0] );
			}
			if( $md->Property == 'C_SUBSUBCATEGORY' ) {
				$subSubCategory = trim( $md->Values[0] );
			}
			// And we take all custom props that start with TAG_
			if( strncmp($md->Property, 'C_TAG_', 6 ) == 0 && !empty($md->Values) ) {
			    $vocabularyArray[]	= substr( $md->Property, 6 );
			    $tagsArray[] = self::xmlrpcTagMetaData( $md->Values );
			}
		}

		// Create Drupal content type out of issue description and
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		$issue = DBIssue::getIssue( $publishtarget->IssueID );
		$issueDescription = $issue['description'];
		if( empty($issueDescription) ) {
        	throw new BizException('ERR_PUBLISH', 'Client', '', 'Configuration problem: Web Issue is missing description, which will act as the start of the content type for Drupal');
		}
		require_once BASEDIR . '/server/dbclasses/DBSection.class.php';
		$category = DBSection::getSectionObj( $dossier->MetaData->BasicMetaData->Category->Id );
		$categoryDescription = $category->Description;
		if( empty($categoryDescription) ) {
        	throw new BizException('ERR_PUBLISH', 'Client', '', 'Configuration problem: Category is missing description, which will act as part of the content type for Drupal');
		}

//		if( empty($subCategory) ) {
//        	throw new BizException('ERR_PUBLISH', 'Client', '', "Dossier SubCategory not set, which is required to make the Drupal content type");
//		}
		$contentType = $issueDescription.'_'.$categoryDescription;

		if( !empty($subCategory) && $subCategory != 'null' ) {
			$contentType .= '_'.$subCategory;
		}

		// TODO #WWPC
		if( $dossier->MetaData->BasicMetaData->Publication->Name != 'Events' &&
		    !empty($subSubCategory) && $subSubCategory != 'null' ) {
//		if( !empty($subSubCategory) ) {
			$contentType .= '_'.$subSubCategory;
		}

		$contentType = strtolower( $contentType );

        $paramArray = array(
   	      				'ID' 				=> new xmlrpcval( $dossier->MetaData->BasicMetaData->ID),
   	      				'Name' 				=> new xmlrpcval( $dossier->MetaData->BasicMetaData->Name),
         				'Category' 			=> new xmlrpcval( $dossier->MetaData->BasicMetaData->Category->Name),
         				'SubCategory' 		=> new xmlrpcval( $subCategory ),
         				'SubSubCategory' 	=> new xmlrpcval( $subSubCategory ),
         				'ProdVersion' 		=> new xmlrpcval( $prodVersion ),
         				'ContentType' 		=> new xmlrpcval( $contentType),
         				'Description' 		=> new xmlrpcval( $dossier->MetaData->ContentMetaData->Description)
// TODO: add prio, keywords, byline?
//    	$prio 		= $dossier->MetaData->WorkflowMetaData->Urgency;
//      	$keywords 	= $dossier->MetaData->ContentMetaData->Keywords;
         				);
         // Empty array causes trouble in XML RPC serialization, so test for empty values:
         $vocID = 1;
         $order = 0;
         foreach( $vocabularyArray as $vocabulary ) {
             if( !empty($vocabulary) && !empty($tagsArray[$order]) ) {
         	   $paramArray['Vocabulary'.$vocID] = new xmlrpcval( $vocabulary);
               $paramArray['Tags'.$vocID]	      = $tagsArray[$order];
               $vocID++;
             }
             $order++;
        }

		$fields = new xmlrpcval( $paramArray, 'struct');
	}

	private function getFileContentEx($object, $rendition, &$format)
	{
		$objectid = $object->MetaData->BasicMetaData->ID;
		$user = BizSession::getShortUserName();
		$tempobject = BizObject::getObject($objectid, $user, false, $rendition);

		require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		
		if( $rendition == 'output' ) {
			// Output is per page, for now we just take the first page:
			$filePath = $tempobject->Pages[0]->Files[0]->FilePath;
			$format= $tempobject->Pages[0]->Files[0]->Type;
			$fileContent = $transferServer->getContent($tempobject->Pages[0]->Files[0]);
		} else {
			$filePath = $tempobject->Files[0]->FilePath;
			$format = $tempobject->Files[0]->Type;
			$fileContent = $transferServer->getContent($tempobject->Files[0]);
		}

		return $fileContent;
	}

	private function getNodeComponents ($children, &$textcomponents, &$images, &$videos)
	{
		$hyperlinks = array();
		$texts = array();
		$headFound = false;
		//TODO remove images from parameters and rename $videos to $files
		$images = $images;

		foreach ($children as $child) {
			// Check availability of files if needed:
			$rendition = $this->askRenditionType($child);
			$format = '';
			$fileContent = $this->getFileContentEx($child, $rendition, $format);
			if( empty($fileContent) ) {
				$msg = "No $rendition available for ".$child->MetaData->BasicMetaData->Name.' ('.$child->MetaData->BasicMetaData->Type.')';
				throw new BizException('ERR_PUBLISH', 'Client', '', $msg, $msg );
			}

			switch ($child->MetaData->BasicMetaData->Type) {
				case 'Article':
					// for Text, InCopy and HTML articles we parse the content, the rest treated as file.
					if( $format=='text/plain' || $format=='application/incopy' || $format=='application/incopyinx' || $format=='text/wwea' || $format=='text/html' ) {
						$elements = $this->getArticleElements($child, $fileContent );
						foreach ($elements as $element){
							if ($element->Label == 'head'){
								$headFound = true;
							}
						}
						$texts = array_merge($texts, $elements);
					} else {
						$rpcImage = new xmlrpcval(
							array('name' 		=> new xmlrpcval($child->MetaData->BasicMetaData->Name) ,
								'type' 		=> new xmlrpcval($format) ,
								'ext' 		=> new xmlrpcval(MimeTypeHandler::mimeType2FileExt($format)) ,
								'contents' 	=> new xmlrpcval(base64_encode($fileContent)),
								'enterprise_id' => new xmlrpcval($child->MetaData->BasicMetaData->ID) ,
								'iid' 		=> new xmlrpcval($child->ExternalId),
								'keywords' 	=> self::xmlrpcGetKeywords($child)
								), 'struct');
						// files, which are now stored in videos which are files as well
						$videos[] = $rpcImage;
					}
					break;
				case 'Image':
					$fileName = $child->MetaData->BasicMetaData->Name . MimeTypeHandler::mimeType2FileExt($format);
					$fid = self::uploadAttachment($fileName, $fileContent, $format);
					$rpcImage = new xmlrpcval(
						array('name' 		=> new xmlrpcval($child->MetaData->BasicMetaData->Name) ,
							'type' 			=> new xmlrpcval($format) ,
							'ext' 			=> new xmlrpcval(MimeTypeHandler::mimeType2FileExt($format)) ,
							'enterprise_id' => new xmlrpcval($child->MetaData->BasicMetaData->ID) ,
							'iid' 			=> new xmlrpcval($child->ExternalId),
							'keywords' 		=> self::xmlrpcGetKeywords($child),
							'fid' 			=> new xmlrpcval($fid),
							), 'struct');
					// JPG, GIF and PNG are detected as image in Drupal now, not here
					$videos[] = $rpcImage;
					break;
				case 'Audio':
					// TODO
					break;
				case 'Video':
					//TODO for now, assume Format is application/x-shockwave-flash and ext is .flv when format is empty
					$type = $child->MetaData->ContentMetaData->Format;
					$ext = MimeTypeHandler::mimeType2FileExt($child->MetaData->ContentMetaData->Format);
					if (empty($type)){
						$type = 'application/x-shockwave-flash';
					}
					if (empty($ext)){
						$ext = '.flv';
					}
					$videos[] = new xmlrpcval(
						array('name' => new xmlrpcval($child->MetaData->BasicMetaData->Name) ,
							'type' => new xmlrpcval($type) ,
							'ext' => new xmlrpcval($ext
								) ,
							'contents' => new xmlrpcval(
								base64_encode(
									$this->getFileContent($child,
										'native'))) ,
							'enterprise_id' => new xmlrpcval(
								$child->MetaData->BasicMetaData->ID) ,
							'iid' => new xmlrpcval($child->ExternalId) ,
							'keywords' => self::xmlrpcGetKeywords($child)),
						'struct');
					break;
				case 'Hyperlink':
					$hyperlinks[] = new xmlrpcval(
						array(
							$child->MetaData->BasicMetaData->Name => new xmlrpcval(
								$child->MetaData->BasicMetaData->DocumentID)), 'struct');
					break;
				case 'Layout':
					$rpcLayout = new xmlrpcval(
						array('name' 		=> new xmlrpcval($child->MetaData->BasicMetaData->Name) ,
							'type' 			=> new xmlrpcval('application/pdf'),
							'ext' 			=> new xmlrpcval('.pdf') ,
							'contents' 		=> new xmlrpcval(base64_encode($fileContent)),
							'enterprise_id' => new xmlrpcval($child->MetaData->BasicMetaData->ID) ,
							'iid' 			=> new xmlrpcval($child->ExternalId),
							'keywords' 		=> self::xmlrpcGetKeywords($child)
							), 'struct');
					// PDF is treated as files, which are now stored in videos which are files as well
					$videos[] = $rpcLayout;
					break;
				case 'Other':
				default:
					$fileName = $child->MetaData->BasicMetaData->Name . MimeTypeHandler::mimeType2FileExt($format);
					$fid = self::uploadAttachment($fileName, $fileContent, $format);
					$rpcImage = new xmlrpcval(
						array('name' 		=> new xmlrpcval($child->MetaData->BasicMetaData->Name) ,
							'type' 			=> new xmlrpcval($format) ,
							'ext' 			=> new xmlrpcval(MimeTypeHandler::mimeType2FileExt($format)) ,
							'enterprise_id' => new xmlrpcval($child->MetaData->BasicMetaData->ID) ,
							'iid' 			=> new xmlrpcval($child->ExternalId),
							'keywords' 		=> self::xmlrpcGetKeywords($child),
							'fid' 			=> new xmlrpcval($fid) ,
							), 'struct');
					// other files are now stored in videos which are files as well
					$videos[] = $rpcImage;
					break;
			}
		}
		//TEMP method to determine head
/*		if (! $headFound && count($texts) > 1) {
			// sort texts on length
			usort($texts, array('Drupal_PubPublishing', 'getNodeComponents_textsCmp'));
			// change label
			$texts[0]->Label = 'head';
			// if more than 2 texts then assume text 2 is teaser
			if (count($texts) > 2){
				$texts[1]->Label = 'intro';
			}
		}
*/
		// return texts as rpc values
		foreach ($texts as $text) {
			$textcomponents[] = new xmlrpcval(
				array($text->Label => new xmlrpcval($text->Content) ,
					'category' => new xmlrpcval(
						$text->Article->MetaData->BasicMetaData->Category->Name) ,
					'writer' => new xmlrpcval($text->Article->MetaData->WorkflowMetaData->Creator) ,
					'keywords' => self::xmlrpcGetKeywords($text->Article)), 'struct');
		}
	}

	private static function getNodeComponents_textsCmp($a, $b)
	{
		if ($a->ContentLength === $b->ContentLength){
			return 0;
		}
		return $a->ContentLength < $b->ContentLength ? -1 : 1;
	}

	private static function xmlrpcGetKeywords ($bizObject)
	{
		return self::xmlrpcTagMetaData( $bizObject->MetaData->ContentMetaData->Keywords );
	}

	private static function xmlrpcTagMetaData( $keywords )
	{
		$xmlrpcKeywords = array();
		foreach ($keywords as $keyword) {
			if ($keyword != '') {
				$xmlrpcKeywords[] = new xmlrpcval($keyword, 'string');
			}
		}

		return new xmlrpcval($xmlrpcKeywords, 'array');
	}

	private function getArticleElements ($article, $content )
	{
		$elements = array();
		$format = $article->MetaData->ContentMetaData->Format;
		switch ($format){
			case 'application/incopy':
				// Convert article into XHTML frames (tinyMCE compatible)
				require_once BASEDIR . '/server/appservices/textconverters/TextConverter.class.php';
				$fc = TextConverter::createTextImporter($format);
				$xFrames = array();
				$stylesCSS = '';
				$stylesMap = '';
				$artDoc = new DOMDocument();
				$artDoc->loadXML($content);
				$domVersion = '0';
				$fc->importBuf($artDoc, $xFrames, $stylesCSS, $stylesMap, $domVersion);
				// handle multi element article
				foreach ($xFrames as $xFrame){
					// content is in body element
					$doc = new SimpleXMLElement($xFrame->Content);
					$content = '';
					foreach ($doc->body->children() as $bodyChild){
						$content .= $bodyChild->asXML();
					}
					$element = new stdClass();
					// standard label is body (InCopy articles without Smart Layout have the name of the article)
					if ($xFrame->Label != 'head' && $xFrame->Label != 'intro'){
						$element->Label = 'body';
					} else {
						$element->Label = $xFrame->Label;
					}
					$element->Content = $content;
					$element->ContentLength = strlen($content);
					$element->Article = $article;
					$elements[] = $element;
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
						$artDoc = new DOMDocument();
						$data = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');
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
						} else {
							$content = $data;
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
					// TO DO - handle multi-element articles
					if ($article->Elements[0]->Name != ''){
						// only filled labels
						$element->Label = $article->Elements[0]->Name;
					}
				}

				$elements[] = $element;
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

				$elements[] = $element;
		}

		return $elements;
	}

	/**
	 * Returns a string or array from a xmlrpc value
	 *
	 * @param xmlrpcval $value
	 * @return string or array
	 */
	private static function xmlrpcGetValue(xmlrpcval $value)
	{
		$result = null;
		switch ($value->kindOf()){
			case 'scalar':
				$result = $value->scalarval();
				break;
			case 'array':
				$result = array();
				for ($i=0; $i < $value->arraysize(); $i++){
					$result[] = self::xmlrpcGetValue($value->arraymem($i));
				}
				break;
		}

		return $result;
	}

	/**
	 * Returns a PubField object from a xmlrpc value object
	 *
	 * @param string $key
	 * @param string $type
	 * @param xmlrpcval $value
	 * @return PubField or null if $value couldn't be converted
	 */
	private static function xmlrpcGetField($key, $type, xmlrpcval $value)
	{
		$result = null;
		$fieldValue = self::xmlrpcGetValue($value);
		if (! is_null($value)){
			switch ($type){
				case 'int':
					$fieldValue = array(intval($fieldValue));
					break;
				case 'double':
					$fieldValue = array(doubleval($fieldValue));
					break;
				case 'string':
					$fieldValue = array(strval($fieldValue));
					break;
				default:
					break;
			}
			$result = self::getNewPubField($key, $type, $fieldValue);
		}
		//TODO BizException if $fieldValue is null?

		return $result;
	}
	
	public static function uploadAttachment($fileName, $content, $contentType)
	{
		require_once 'Zend/Http/Client.php';
		
		$client = new Zend_Http_Client(self::getUploadUri());
		$client->setParameterPost('username', DRUPAL_USERNAME);
		$client->setParameterPost('password', base64_encode(DRUPAL_PASSWORD));
		$client->setFileUpload($fileName, 'files[upload]', $content, $contentType);
		$reponse = $client->request('POST');
		if ($reponse->isError()){
			// error
			throw new BizException('ERR_PUBLISH', 'Server', '', '', array('Drupal', 'Could not upload attachment'));
		}
		$dom = new DOMDocument();
		$dom->loadXML($reponse->getBody());
		$xpath = new DOMXPath($dom);
		$fidNode = $xpath->query('//fid')->item(0);
		$fid = 0;
		if ($fidNode){
			$fid = $fidNode->nodeValue;
		}
		
		return $fid;
	}
	
	public static function getUploadUri()
	{
		//TODO get from Drupal server and cache it
		return DRUPAL_URL . '?q=ww_enterprise/upload';
	}
	
	/**
	 * Return new PubField or Field object depending on the server version.
	 *
	 * @param string $Key
	 * @param PropertyType $Type
	 * @param array $Values
	 * @return PubField|Field
	 */
	private static function getNewPubField( $Key=null, $Type=null, $Values=null )
	{
		// PubField only exists in 6.5
		if (class_exists('PubField')){
			return new PubField($Key, $Type, $Values);
		}
		return new Field($Key, $Type, $Values);
	}
}

