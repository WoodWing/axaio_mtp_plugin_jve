<?php
/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class with static functions called to do publishing to an external publishing system.
 * For example: .
 *
 * Copyright 2009 WoodWing Software BV Licensed under the Apache
 * License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *  
 *    http://www.apache.org/licenses/LICENSE-2.0
 *  
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
**/

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
require_once dirname(__FILE__) . '/config.php';

class EZPublish_PubPublishing extends PubPublishing_EnterpriseConnector
{
	
	// soap client
	private $soapclient;
	
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	
	/**
	 * Publishes a dossier with contained objects (articles. images, etc.) to eZ Publish.
	 * The plugin is supposed to publish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from eZ Publish
	**/
	public function publishDossier(&$dossier, &$objectsindossier, $publishtarget)
	{
		// Start eZ Publish soap client
		$this->startEZPublish();
		
		// Get images and textcomponents
		$images = array();
		$videos = array();
		$textcomponents = array();
		
		$dossierhascomponents = $this->getDossierComponents($objectsindossier, $textcomponents, $images, $videos);
		if(!$dossierhascomponents){
			// There were no or not enough text components
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "No text or not enough text components for an article. Make sure there is a head, intro and body."));
		}
		
		// Create a draft article (so we can store images there, using new article as parent node in the node tree)
		$draftcreated = $this->createDraftArticle($textcomponents, $images);
		if(!isset($draftcreated)){
			// Draft could not be created
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not create a draft article."));
		}
		
		// Upload images to eZ Publish
		$imagessend = $this->publishBodyImages($textcomponents['body'], $draftcreated->contentObject_id, true, $dossier, $publishtarget, false);
		if(!$imagessend){
			// Something went wrong while sending images to eZ Publish, throw error
			$this->unpublishRequest($draftcreated->contentObject_id);
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not send images."));
		}
		
		// Upload videos to eZ Publish
		$videossend = $this->publishVideos($textcomponents['body'], $videos, $draftcreated->contentObject_id, true, $dossier, $publishtarget, false);
		if(!$videossend){
			// Something went wrong while sending videos to eZ Publish, throw error
			$this->unpublishRequest($draftcreated->contentObject_id);
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not send videos."));
		}
		
		// When all images and videos are uploaded, upload final article
		$finalpublished = $this->createFinalArticle($textcomponents, $images, $draftcreated->contentObject_id);
		if(!$finalpublished){
			// Could not publish final article, throw error
			$this->unpublishRequest($draftcreated->contentObject_id);
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not publish final article."));
		}
		
		// After uploading final article, create url to send back to Enterprise and set ExternalId of dossier
		$dossier->ExternalId = $finalpublished->contentObject_id;
		$result = array();
		$result[] = self::getNewPubField('URL','string',array(EZPUBLISH_EXTERNAL_URL . 'index.php/' . $finalpublished->path_identification_string));
		return $result;
	}
	
	/**
	 * Updates/republishes a published dossier with contained objects (articles. images, etc.) to eZ Publish,
	 * using the $dossier->ExternalId to identify the dosier to eZ Publish.
	 * The plugin is supposed to update/republish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from eZ Publish
	**/
	public function updateDossier(&$dossier, &$objectsindossier, $publishtarget)
	{
		// start eZ Publish soap client
		$this->startEZPublish();
		
		// Get images and textcomponents
		$images = array();
		$videos = array();
		$textcomponents = array();
		$dossierhascomponents = $this->getDossierComponents($objectsindossier, $textcomponents, $images, $videos);
		if(!$dossierhascomponents){
			// There were no or not enough text components
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "No text or not enough text components for an article. Make sure there is a head, intro and body."));
		}
		
		// Upload images to eZ Publish
		$imagessend = $this->publishBodyImages($textcomponents['body'], $dossier->ExternalId, false, $dossier, $publishtarget, false);
		if(!$imagessend){
			// Something went wrong while sending images to eZ Publish, throw error
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not send images."));
		}
		
		// Upload videos to eZ Publish
		$videossend = $this->publishVideos($textcomponents['body'], $videos, $dossier->ExternalId, true, $dossier, $publishtarget, false);
		if(!$videossend){
			// Something went wrong while sending videos to eZ Publish, throw error
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not send videos."));
		}
		
		// When all images are uploaded, upload new article
		$finalpublished = $this->createFinalArticle($textcomponents, $images, $dossier->ExternalId);
		if(!$finalpublished){
			// Could not publish final article, throw error
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not publish new article."));
		}
		
		// After uploading final article, create url to send back to Enterprise and set ExternalId of dossier
		$result = array();
		$result[] = self::getNewPubField('URL','string',array(EZPUBLISH_EXTERNAL_URL . 'index.php/' . $finalpublished->path_identification_string));
		return $result;
	}
	
	/**
	 * Removes/unpublishes a published dossier from eZ Publish
	 * using the $dossier->ExternalId to identify the dosier to eZ Publish.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from eZ Publish
	 */
	public function unpublishDossier($dossier, $objectsindossier, $publishtarget)
	{
		// Start eZ Publish soap client
		$this->startEZPublish();
		
		if(strlen($dossier->ExternalId) > 0){
			$exists = $this->checkExistanceOnServer($dossier->ExternalId);			
			if($exists == true){
				$this->unpublishRequest($dossier->ExternalId);
			}
		}
		
		return null;
	}
	
	/**
	 * Sends the unpublishing request to eZ Publish
	 *
	 * @param string $objectContentId
	 * @return boolean - true if succesfully removed
	 */
	private function unpublishRequest($objectContentId)
	{
		// Set up parameter array
		$params = array(new SoapParam($objectContentId, 'contentObjectId'),
						new SoapParam(EZPUBLISH_USERNAME, 'username'),
						new SoapParam(base64_encode(EZPUBLISH_PASSWORD), 'password'));
		
		try{
			// Send request
			$result = $this->soapclient->__soapCall('removeObject', $params);
			return true;
		}
		catch(SoapFault $fault){
			// Error occured, throw exception
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', $fault->faultstring));
		}
	}
	
	/**
	 * Requests fieldvalues from an external publishing system
	 * using the $dossier->ExternalId to identify the dossier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from eZ Publish
	**/
	public function requestPublishFields($dossier, $objectsindossier, $publishtarget)
	{
		$objectsindossier=$objectsindossier; $publishtarget=$publishtarget;
		$result = array();
		$result[] = self::getNewPubField('URL','string',array( self::getEZPublishPageURL() ) );
		return $result;
	}
	
	/**
	 * Requests dossier URL from an external publishing system
	 * using the $dossier->ExternalId to identify the dossier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 * 
	 * @return string
	 */	
	public function getDossierURL($dossier, $objectsindossier, $publishtarget)
	{
		// Show the page where is being published to
		return self::getEZPublishPageURL();
	}
	
	/**
	 * Previews a dossier with contained objects (articles. images, etc.) to an external publishing system.
	 * The plugin is supposed to send the dossier and it's articles to the publishing system and fill in the URL field for reference.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 * 
	 * @return array of Fields containing information from Publishing system
	 * 
	**/	
	public function previewDossier(&$dossier, &$objectsindossier, $publishtarget)
	{
		// Start eZ Publish soap client
		$this->startEZPublish();
		
		// Get images and textcomponents
		$images = array();
		$videos = array();
		$textcomponents = array();
		
		$dossierhascomponents = $this->getDossierComponents($objectsindossier, $textcomponents, $images, $videos);
		if(!$dossierhascomponents){
			// There were no or not enough text components
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "No text or not enough text components for an article. Make sure there is a head, intro and body."));
		}
		
		// Create a draft article (so we can store images there, using new article as parent node in the node tree) and make it hidden (preview = true)
		 $draftcreated = $this->createDraftArticle($textcomponents, $images, true);
 		if(!isset($draftcreated)){
 			// Draft could not be created
 			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not create a draft article."));
 		}
 		
 		// Upload images to eZ Publish
 		$imagessend = $this->publishBodyImages($textcomponents['body'], $draftcreated->contentObject_id, true, $dossier, $publishtarget, true);
		if(!$imagessend){
 			// Something went wrong while sending images to eZ Publish, throw error
 			$this->unpublishRequest($draftcreated->contentObject_id);
 			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not send images."));
 		}
 		
 		// Upload videos to eZ Publish
 		$videossend = $this->publishVideos($textcomponents['body'], $videos, $draftcreated->contentObject_id, true, $dossier, $publishtarget, true);
 		if(!$videossend){
 			// Something went wrong while sending videos to eZ Publish, throw error
 			$this->unpublishRequest($draftcreated->contentObject_id);
 			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not send videos."));
 		}
 		
 		// When all images and videos are uploaded, upload final article
 		$finalpublished = $this->createFinalArticle($textcomponents, $images, $draftcreated->contentObject_id);
 		if(!$finalpublished){
 			// Could not publish final article, throw error
 			$this->unpublishRequest($draftcreated->contentObject_id);
 			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Could not publish final article."));
 		}
		
		// Return array with URL data for preview
		$result = array();
		$result[] = self::getNewPubField('URL', 'string', array(EZPUBLISH_EXTERNAL_URL . 'index.php' . '/ww_preview/previewframe/' . $finalpublished->contentObject_id . '/' . $finalpublished->version));
		return $result;
		
	}
	
	/**
	 * Initializes variables used by the eZ Soap system
	 *
	 */
	private function startEZPublish()
	{
		$this->soapclient = new SoapClient(null, array('location'=>"http://".EZPUBLISH_URL.EZPUBLISH_PATH_ON_SERVER, 'uri'=>'ez.no'));
	}

	/**
	 * Create a new (draft)article in eZ Publish
	 *
	 * @param array of strings $textcomponents
	 * @param array of (image) Objects $images
	 * @return array with values of eZ Publish
	 */
	private function createDraftArticle($textcomponents, $images, $preview=false)
	{
		// Set up parameter array
		$intro = ' ';
		if(!$textcomponents['intro'] == NULL && !$textcomponents['intro'] == ''){
			$intro = $textcomponents['intro'];
		}
		
		$imagestring = 'none';
		$filename = 'none';
		if(count($images) > 0){
			$image = $this->getHeaderImage($images);
			$imagestring = base64_encode($this->getFileContent($image, $this->askRenditionType($image)));
			$filename = $image->MetaData->BasicMetaData->Name . $this->getFileExtension($image);
		}
		
		$params = array(new SoapParam($textcomponents['title'], 'title'),
						new SoapParam($textcomponents['short_title'], 'short_title'),
						new SoapParam($intro, 'intro'),
						new SoapParam($textcomponents['body'], 'body'),
						new SoapParam($imagestring, 'encoded_file'),
						new SoapParam($filename, 'filename'),
						new SoapParam(EZPUBLISH_USERNAME, 'username'),
						new SoapParam(base64_encode(EZPUBLISH_PASSWORD), 'password'),
						new SoapParam($preview, 'preview'));
		
		try{
			// Send request
			$result = $this->soapclient->__soapCall('createObject', $params);
			return $result;
		}
		catch(SoapFault $fault){
			// Error occured, throw exception
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Publishing draft failed: " . $fault->faultstring));
		}
	}
	
	/**
	 * Publishes final / updated article to eZ Publish
	 *
	 * @param array of strings $textcomponents
	 * @param array of (image) Objects $images
	 * @param string $contentObjectId
	 * @return array with values of eZ Publish
	 */
	private function createFinalArticle($textcomponents, $images, $contentObjectId, $preview=false)
	{
		// Set up parameter array
		$intro = ' ';
		if(!$textcomponents['intro'] == NULL && !$textcomponents['intro'] == ''){
			$intro = $textcomponents['intro'];
		}
		
		$imagestring = 'none';
		$filename = 'none';
		if(count($images) > 0){
			$image = $this->getHeaderImage($images);
			$imagestring = base64_encode($this->getFileContent($image, $this->askRenditionType($image)));
			$filename = $image->MetaData->BasicMetaData->Name . $this->getFileExtension($image);
		}
		
		$params = array(new SoapParam($contentObjectId, 'contentObjectId'),
						new SoapParam($textcomponents['title'], 'title'),
						new SoapParam($textcomponents['short_title'], 'short_title'),
						new SoapParam($intro, 'intro'),
						new SoapParam($textcomponents['body'], 'body'),
						new SoapParam($imagestring, 'encoded_file'),
						new SoapParam($filename, 'filename'),
						new SoapParam(EZPUBLISH_USERNAME, 'username'),
						new SoapParam(base64_encode(EZPUBLISH_PASSWORD), 'password'));
		
		try{
			// Send request
			$result = $this->soapclient->__soapCall('updateObject', $params);
			return $result;
		}
		catch(SoapFault $fault){
			// Error occured, throw exception
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', "Publishing final failed: " . $fault->faultstring));
		}
	}
	
	/**
	 * Gets all the different components of a dossier, which are selected for publication
	 *
	 * @param writable array of Object $children
	 * @param writable array of string $textcomponents
	 * @param writable array of (Image) Object $images
	 * @param writable array of (Video) Objects $videos
	 * @return boolean - true if there are enough textcomponents, false if not
	 */
	private function getDossierComponents(&$children, &$textcomponents, &$images, &$videos)
	{
		$heads_found = array();
		$bodies_found = array();
		$intros_found = array();
		$others_found = array();
		
		foreach ($children as $child) {
			switch ($child->MetaData->BasicMetaData->Type) {
				case 'Article':
					// Get all elements of this article
					$elements = $this->getArticleElements($child);
					foreach($elements as $element){
						// Check for different elements
						if($element->Label == 'head'){
							$heads_found[] = $element->Content;
						}
						else if($element->Label == 'body'){
							$bodies_found[] = $element->Content;
						}
						else if($element->Label == 'intro'){
							$intros_found[] = $element->Content;
						}
						else{
							// No specific element, just add, could be usefull for sorting at end
							$others_found[] = $element->Content;
						}
					}
					
					break;
				case 'Image':
					$images[] = $child;
					break;
				case 'Audio':
					
					break;
				case 'Video':
					$videos[] = $child;
					break;
				case 'Hyperlink':
					
					break;
				default:
					
					break;
			}
		}
		
		// Sort found components
		return $this->sortTextComponents($textcomponents, $heads_found, $intros_found, $bodies_found, $others_found);
	}
	
	/**
	 * Sorts available text components so that a body, intro and head are set
	 *
	 * @param array $textcomponents
	 * @param array $heads_found
	 * @param array $intros_found
	 * @param array $bodies_found
	 * @param array $others_found
	 * @return boolean, true if enough textcomponents found
	 */
	private function sortTextComponents(&$textcomponents, $heads_found, $intros_found, $bodies_found, $others_found){
		// Check for all text components		
		$head_found = false;
		$intro_found = false;
		$body_found = false;
		
		if(count($heads_found) > 0){
			$head_found = true;
			
			// Sort heads on length, if more than 1 head
			if(count($heads_found) > 1){
				usort($heads_found, array($this, 'getDossierComponents_textsCmp'));
				
				// If there is more than 1 head, then create normal and short
				$textcomponents['short_title'] = $heads_found[0];
				$textcomponents['title'] = $heads_found[1];
				
				// Copy remaining items to other texts
				for($i = 2; $i < count($heads_found); $i++){
					$others_found[] = $heads_found[$i];
				}
			}
			else{
				// Short and normal are one and the same
				$textcomponents['title'] = $heads_found[0];
				$textcomponents['short_title'] = $heads_found[0];
			}
		}
		
		if(count($intros_found) > 0){
			$intro_found = true;
			
			// Sort intros on length, if more than 1 intros
			if(count($intros_found) > 1){
				usort($intros_found, array($this, 'getDossierComponents_textsCmp'));
				
				// Copy remaining items to other texts
				for($i = 1; $i < count($intros_found); $i++){
					$others_found[] = $intros_found[$i];
				}
			}
			
			// Assume the first is the right one (shortest)
			$textcomponents['intro'] = $intros_found[0];
		}
		
		if(count($bodies_found) > 0){
			$body_found = true;
			
			// Sort body's on length, if more than one
			if(count($bodies_found) > 1){
				usort($bodies_found, array($this, 'getDossierComponents_textsCmp'));
				
				// Copy remaining items to other texts
				for($i = 0; $i < count($bodies_found)-1; $i++){
					$others_found[] = $bodies_found[$i];
				}
			}
			
			// Assume the longest is the right one
			$textcomponents['body'] = $bodies_found[count($bodies_found)-1];
		}
		
		// Check if any components are missing and could be filled in
		if((!$head_found || !$body_found || !$intro_found) && count($others_found) > 0){
			// Sort texts on length
			usort($others_found, array($this, 'getDossierComponents_textsCmp'));
			
			if(!$head_found){
				// Head is probably the shortest
				$textcomponents['short_title'] = $others_found[0];
				$textcomponents['title'] = $others_found[0];
				$head_found = true;
			}
			
			if(!$body_found){
				// Body is probably the longest
				$textcomponents['body'] = $others_found[count($others_found)-1];
				$body_found = true;
			}
			
			if(!$intro_found){
				// Intro is probably the but one shortest, but it should be shorter than the body!
				if(count($others_found) > 1){
					// There is more than 1 element, which is good
					if(strlen($textcomponents['body']) > strlen($others_found[1])){
						// Body is longer, all set
						$textcomponents['intro'] = $others_found[1];
					}
					else{
						// Try first element
						if(strlen($textcomponents['body']) > strlen($others_found[0])){
							// Body is longer, all set
							$textcomponents['intro'] = $others_found[0];
							$intro_found = true;
						}
						else{
							// No intro...
							$textcomponents['intro'] = " ";
							$intro_found = true;
						}
					}
				}
				else{
					// There is only one element, check if it is shorter than body
					if(strlen($textcomponents['body']) > strlen($others_found[0])){
						// Body is longer, all set
						$textcomponents['intro'] = $others_found[0];
						$intro_found = true;
					}
					else{
						// No intro...
						$textcomponents['intro'] = " ";
						$intro_found = true;
					}
				}
			}
			
			// Text found
			if($head_found && $body_found && $intro_found){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			if($head_found && $body_found && $intro_found){
				// All components found
				return true;
			}
		}
		
		// Not enough text components
		return false;
	}
	
	/**
	 * Function to compare the length of two Strings, used for usort
	 *
	 * @param string $a
	 * @param string $b
	 * @return boolean
	 */
	private function getDossierComponents_textsCmp($a, $b)
	{
		if (strlen($a) === strlen($b)){
			return 0;
		}
		
		return strlen($a) < strlen($b) ? -1 : 1;
	}
	
	/**
	 * Get different elements of an article (head, intro, body, ...)
	 *
	 * @param Object $article
	 * @return array of elements
	 */
	private function getArticleElements($article)
	{
		$elements = array();
		$content = $this->getFileContent($article, 'native');
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
				$fc->importBuf($artDoc, $xFrames, $stylesCSS, $stylesMap);
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
	 * Publishes all images used in bodytext to eZ Publish.
	 * Changes bodytext to replace image tags (<img>) with eZ Publish tag
	 *
	 * @param string $bodyText
	 * @param array of image Objects $images
	 * @param int $parentObjectId
	 * @return boolean, true if succesfully published all images
	 */
	private function publishBodyImages(&$bodyText, $parentObjectId, $unpublishOnError = true, $dossier, $publishtarget, $preview=false)
	{
		// Search for all image tags (id part)
		$preg_results = array();
		preg_match_all('/<img.*id="ent_([^"]*)/', $bodyText, $preg_results);
		$image_ids = $preg_results[1];
			
		foreach($image_ids as $image_id){
			// Get image
			$user = BizSession::getShortUserName();
			$imageobject = BizObject::getObject($image_id, $user, false, 'preview');
			
			// Fetch ExternalId
			require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';
			require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
			
			$channelId = $publishtarget->PubChannelID;
			$issueId = $publishtarget->IssueID;
			$dossierId = $dossier->MetaData->BasicMetaData->ID;
			$childId = $imageobject->MetaData->BasicMetaData->ID;
			$imageobject->ExternalId = DBPublishedObjectsHist::getObjectExternalId($dossierId, $childId, $channelId, $issueId);
			
			// Check if image exists on eZ Publish
			if(!strlen($imageobject->ExternalId) > 0 || !$this->checkExistanceOnServer($imageobject->ExternalId)){
				// Not on server yet, send it!
				$filename = $imageobject->MetaData->BasicMetaData->Name . $this->getFileExtension($imageobject);
				$contents = base64_encode($this->getFileContent($imageobject, 'preview'));
				
				// Find description
				$description_results = array();
				preg_match('/<img.*id="ent_'.$imageobject->MetaData->BasicMetaData->ID.'".*alt="([^"]*)"[^>]*/', $bodyText, $description_results);
				$description = !isset($description_results[1]) || $description_results[1] == "" || $description_results[1] == null ? " " : $description_results[1];
				
				$params = array(new SoapParam($parentObjectId, 'parent_object'),
								new SoapParam($contents, 'encoded_file'),
								new SoapParam($filename, 'filename'),
								new SoapParam($description, 'caption'),
								new SoapParam(EZPUBLISH_USERNAME, 'username'),
								new SoapParam(base64_encode(EZPUBLISH_PASSWORD), 'password'));
				
				try{
					$result = $this->soapclient->__soapCall('createImage', $params);
					
					// Image is created, set external id of image and save it, but not if it is a preview
 					$imageobject->ExternalId = $result;
 					
 					if(!$preview){
 						// Save ExternalId of object
 						$version = '0.0';
 						$action = 'publishDossier';
 						$publisheddate = $publishtarget->PublishedDate;
 						$publishId = DBTarget::updatePublishInfoDossier($dossierId, $channelId, $issueId, $parentObjectId, $action, $publisheddate, $version, $user);

						$imageName = $imageobject->MetaData->BasicMetaData;
						$imageType = $imageobject->MetaData->BasicMetaData->Type;
						$imageFormat = $imageobject->MetaData->ContentMetaData->Format;
 						DBPublishedObjectsHist::addPublishedObjectsHistory($publishId, $childId, $version, $imageobject->ExternalId, $imageName, $imageType, $imageFormat);
 					}
				}
				catch(SoapFault $fault){
					if($unpublishOnError){
 						$this->unpublishRequest($parentObjectId);
 					}
 					throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', $fault->faultstring));
				}
			}
			
			// Image is on server, update bodytext (<img> tag to <embed> tag)
			$alignment_results = array();
			preg_match('/<img[^>]*id="ent_'.$imageobject->MetaData->BasicMetaData->ID.'"[^>]*align="(.*)"[^>]*>/', $bodyText, $alignment_results);
			$alignment = isset($alignment_results[1]) && $alignment_results[1] != 'middle' ? $alignment_results[1]: 'center';
			
			$replace_regex = '/<img[^>]*id="ent_'.$imageobject->MetaData->BasicMetaData->ID.'[^>]*>/';
			$bodyText = preg_replace($replace_regex, "<embed href='ezobject://" . $imageobject->ExternalId . "' align='".$alignment."' />", $bodyText);
			
		}
		
		return true;
	}
	
	/**
	 * Publishes videos to the eZ Publish system
	 *
	 * @param writable string $bodytext
	 * @param writable array $videos
	 */
	private function publishVideos(&$bodytext, &$videos, $parentObjectId, $unpublishOnError = true, $dossier, $publishtarget, $preview=false)
	{
		foreach($videos as $video){
			// Check if video is already in eZ Publish
			if(!strlen($video->ExternalId) > 0 || !$this->checkExistanceOnServer($video->ExternalId)){
				// Video not yet in eZ Publish, upload it
				$filename = $video->MetaData->BasicMetaData->Name . $this->getFileExtension($video);
				$contents = base64_encode($this->getFileContent($video, 'preview'));
				
				$params = array(new SoapParam($parentObjectId, 'parent_object'),
								new SoapParam($contents, 'encoded_file'),
								new SoapParam($filename, 'filename'),
								new SoapParam(EZPUBLISH_USERNAME, 'username'),
								new SoapParam(base64_encode(EZPUBLISH_PASSWORD), 'password'));
				
				try{
					$result = $this->soapclient->__soapCall('createVideo', $params);
					
					// Video is created, set external id of video and save it, but not if it is a preview
 					$video->ExternalId = $result;
 					
 					if(!$preview){
 						// Save ExternalId of object
						require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';
						require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
				
						$channelId = $publishtarget->PubChannelID;
						$issueId = $publishtarget->IssueID;
						$dossierId = $dossier->MetaData->BasicMetaData->ID;
						$childId = $video->MetaData->BasicMetaData->ID;					
						$version = '0.0';
						$action = 'publishDossier';
						$publisheddate = $publishtarget->PublishedDate;
						$user = BizSession::getShortUserName();
						$publishId = DBTarget::updatePublishInfoDossier($dossierId, $channelId, $issueId, $parentObjectId, $action, $publisheddate, $version, $user);

						$videoName = $video->MetaData->BasicMetaData;
						$videoType = $video->MetaData->BasicMetaData->Type;
						$videoFormat = $video->MetaData->ContentMetaData->Format;
						DBPublishedObjectsHist::addPublishedObjectsHistory($publishId, $childId, $version, $video->ExternalId, $videoName, $videoType, $videoFormat);
 					}
				}
				catch(SoapFault $fault){
					if($unpublishOnError){
 						$this->unpublishRequest($parentObjectId);
 					}
 					throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', $fault->faultstring));
				}
			}
			
			// Video in eZ Publish, add to body text
			$bodytext .= "<embed href='ezobject://" . $video->ExternalId . "' align='center' />";
		}
		
		return true;
	}
	
	/**
	 * Checks if an image (or other eZContentObject) already exists on the eZ server
	 *
	 * @param string $contentObjectId
	 * @return Boolean, true if it exists, false if not
	 */
	private function checkExistanceOnServer($contentObjectId)
	{
		// Set up paramter array
		$params = array(new SoapParam($contentObjectId, 'contentObjectId'),
						new SoapParam(EZPUBLISH_USERNAME, 'username'),
						new SoapParam(base64_encode(EZPUBLISH_PASSWORD), 'password'));
		
		try{
			// Send request to server
			$result = $this->soapclient->__soapCall('checkExistance', $params);
			
			if($result == "true"){
				return true;
			}
			else{
				return false;
			}
		}
		catch(SoapFault $fault){
			// Error occured
			throw new BizException('ERR_PUBLISH', 'Server', '', null, array('eZ Publish', $fault->faultstring));
		}
	}
	
	/**
	 * Return the image which should be used in the header.
	 * Probably the largest in the array.
	 * 
	 * Pre-condition: $images should not be empty
	 *
	 * @param array of (Image)Object $images
	 * @return (Image)Object
	 */
	private function getHeaderImage($images)
	{
		$largestImage = $images[0];
		
		foreach($images as $image){
			// Gets lengths of both images
			$widthLargest = $largestImage->MetaData->ContentMetaData->Width;
			$heightLargest = $largestImage->MetaData->ContentMetaData->Height;
			$width = $image->MetaData->ContentMetaData->Width;
			$height = $image->MetaData->ContentMetaData->Height;
			
			// Calculate longest side
			$largestLongest = $widthLargest > $heightLargest ? $widthLargest : $heightLargest;
			$longest = $width > $height ? $width : $height;
			
			// Check which one is the biggest
			$largestImage = $largestLongest > $longest ? $largestImage : $image;
		}
		
		return $largestImage;
	}
	
	/**
	 * Returns the file extension of an object, including a dot
	 * 
	 * @param Object $object
	 * @return string - File extension, including dot (.)
	 */
	private function getFileExtension($object)
	{
		$objectType = $object->MetaData->BasicMetaData->Type;
		$extension = ".";
		
		//check if child is of supported type
		if($objectType == "Image"){
			$format = $object->MetaData->ContentMetaData->Format;
			
			//split on /
			//list($type, $sort) = split('[/]', $format);
			list($type, $sort) = explode('/', $format);
			$extension .= $sort;			
		}
		else if($objectType == "Video"){
			$extension .= "flv";
		}
		
		if($extension != ".")
			return $extension;
		else
			return false; 
	}
	
	/**
	 * Gets the contents of a file / object
	 *
	 * @param Object $object
	 * @param string $rendition
	 * @return content of file / object
	 */
	protected function getFileContent($object, $rendition)
	{
		if ($rendition) {
			$objectid = $object->MetaData->BasicMetaData->ID;
			$user = BizSession::getShortUserName();
			$tempobject = BizObject::getObject($objectid, $user, false, $rendition);
			require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			return $transferServer->getContent($tempobject->Files[0]);
		}
		else {
			return null;
		}
	}
	
	protected function askRenditionType($object)
	{
		$rendition = null;
		switch ($object->MetaData->BasicMetaData->Type) {
			case 'Image':
				$rendition = 'preview';
				break;
		}
		return $rendition;
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
		// PubField only exists in 7.0
		if (class_exists('PubField')){
			return new PubField($Key, $Type, $Values);
		}
		return new Field($Key, $Type, $Values);
	}	
	
	private static function getEZPublishPageURL()
	{
		return EZPUBLISH_EXTERNAL_URL;
	}
}
?>