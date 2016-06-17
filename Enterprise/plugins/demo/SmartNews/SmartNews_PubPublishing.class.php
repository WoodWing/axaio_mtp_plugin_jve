<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once BASEDIR . '/config/plugins/SmartNews/config.php';
require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

class SmartNews_PubPublishing extends PubPublishing_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * Publishes a dossier to SmartNews.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 * 
	 * @return array of Fields containing information from SmartNews
	**/	
	public function publishDossier(&$dossier, &$objectsindossier, $publishtarget, $action="PUBLISH")
	{
		$publishObj = $this->composeArticleFromDossier( $dossier, $objectsindossier );
		
		$postdata = array(	"AUTH_KEY" => md5(SMARTNEWS_USERNAME.SMARTNEWS_PASSWORD),
							"ACTION" => $action,
							"PUBLISH_DESCRIPTION" => $publishObj->Description,
							"PUBLISH_ARTICLE" => $publishObj->Article,
							"PUBLISH_TITLE" => $publishObj->Title,
							"PUBLISH_CATEGORY" => $publishObj->Category,
							"PUBLISH_IMAGES" => $publishObj->Images,
							"PUBLISH_EXTERNALID" => $dossier->ExternalId );
		
		$key = $this->post(SMARTNEWS_PUBLISHINTERFACE, $postdata);
		$key = trim(str_replace("\n","",strip_tags($key)));
		
		if( $key && $key != "" ) {
			LogHandler::Log("SERVER","INFO","SmartNews returned the ExternalId: ".$key);
			$dossier->ExternalId = $key;
		}else{
			if( $action != "UNPUBLISH" ) {
				$msg = "SmartNews did not return an ExternalId. Publishing failed.";	
				throw new BizException( 'SMARTNEWS_SENDERROR', 'Server', $msg, $msg );
			}
		}
		
		$result = array();
		$result[] = self::getNewPubField('URL','string',array(SMARTNEWS_PUBLISHINTERFACE."?node=".$dossier->ExternalId) );
		return $result = array();
	}
	
	private function logPrintr( $array ) {
		LogHandler::Log("SERVER", "DEBUG", "<pre>".print_r($array, true)."</pre>");
	}
	
	private function log2File( $array ) {
		file_put_contents(dirname(__FILE__)."/debug/log.txt", print_r($array, true));
	}
	
	private function getNodeComponents ($children, $dossier)
	{
		
		$elements = array();
		$publishObjElements = array("head" => "", "intro" => "", "article" => "", "images" => array());

		foreach ($children as $child) {
			$format = $child->MetaData->ContentMetaData->Format;
			switch ($child->MetaData->BasicMetaData->Type) {
				case 'Article':
					// for Text, InCopy and HTML articles we parse the content, the rest treated as file.
					if( $format=='text/plain' || $format=='application/incopy' || $format=='application/incopyinx' || $format=='text/html' || $format=='text/wwea' ) {
						$elements = array_merge($elements, $this->getArticleElements($child));
					}
					break;
				case 'Image':
					// add to array, and upload to smart news
					$publishObjElements["images"][] = array("name" => $child->MetaData->BasicMetaData->Name,
									  						"contents" => base64_encode($this->getFileContent($child, $this->askRenditionType($child))),
									  						"ext" => MimeTypeHandler::mimeType2FileExt($child->MetaData->ContentMetaData->Format));
					break;
					
				case 'Audio':
				case 'Video':
				case 'Hyperlink':
				default:
					//ignore
					break;
			}
		}
		
		$headFound = false;
		$introFound = false;
		$bodyFound = false;
		foreach( $elements as $element ) {
			$content = $element->Content;
			switch( $element->Label ) {
				case ARTICLE_HEADER:
					$publishObjElements['head'] = trim(str_replace("\n","",strip_tags($content)));
					if( $publishObjElements['head'] != "" )
						$headFound = true;
					break;
				case ARTICLE_INTRO:
					$publishObjElements["intro"] = trim(strip_tags($content));
					if( $publishObjElements["intro"] != "" )
						$introFound = true;
					break;
					
				case ARTICLE_BODY:
					$content = nl2br($content);
					$publishObjElements["article"] = trim($content);
					if( $publishObjElements["article"] != "" )
						$bodyFound = true;
					break;
			}
		}
		
		// if we didn't find a head element, take the name of the dossier
		if ( !$headFound ) {
			$publishObjElements['head'] = $dossier->MetaData->BasicMetaData->Name;
		}
		
		if( !$bodyFound ) {
			throw new BizException( 'SMARTNEWS_SENDERROR', 'Server', "No valid body element was found.", "No valid body element was found." );
		}
		
		// if no intro found, use the article
		if( !$introFound ) {
			$publishObjElements["intro"] = substr(trim(strip_tags($publishObjElements["article"])), 0, 252) . "...";
		}else{
			// if found, but too big, shorten it
			if(strlen($publishObjElements["intro"]) > 255) {
				$publishObjElements["intro"] = substr($publishObjElements["intro"], 0, 252) . "...";
			}
		}
		
		return $publishObjElements;
	}
	
	private function getArticleElements ($article)
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
					if ($xFrame->Label != ARTICLE_HEADER && $xFrame->Label != ARTICLE_INTRO){
						$element->Label = ARTICLE_BODY;
					} else {
						$element->Label = $xFrame->Label;
					}
					$element->Content = $content;
					$element->ContentLength = strlen($content);
					$elements[] = $element;
				}
				break;
			case 'text/wwea':
				$eaDoc = new DOMDocument();
				$eaDoc->loadXML($content);
				
				$eaDoc->formatOutput = true;

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
						$data = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');
						
						$htmlDoc = new DOMDocument();
						$htmlDoc->loadHTML($data);
						
						$xpath1 = new DOMXPath($htmlDoc);
						foreach( $xpath1->query("/html/body") as $body ) {
							$content = $htmlDoc->saveXML($body);
							break;
						}
					}
					
					$element = new stdClass();
									
					$element->Label = $label;
					$element->Content = $content;
					$element->ContentLength = strlen($content);
						
					$elements[] = $element;
				}
				
				break;
			case 'text/html':
				
				$element = new stdClass();
				$element->Label = ARTICLE_BODY;
				// only get body content from html
				$content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
				$element->Content = $content;
				$element->ContentLength = strlen($content);
	
				$elements[] = $element;
				break;
		}

		return $elements;
	}

	private static function getNodeComponents_textsCmp($a, $b)
	{
		if ($a->ContentLength === $b->ContentLength){
			return 0;
		}
		return $a->ContentLength < $b->ContentLength ? -1 : 1;
	}
	
	private function composeArticleFromDossier( $dossier, $objectsindossier ) {
		
		$publishObj = new stdClass();

		$publishObjElements = $this->getNodeComponents( $objectsindossier, $dossier );

		// compose publish parameters
		$publishObj->Description = $publishObjElements["intro"];
		$publishObj->Article 	 = $publishObjElements["article"];
		$publishObj->Title 		 = $publishObjElements["head"];
		$publishObj->Category 	 = $dossier->MetaData->BasicMetaData->Category->Name;
		$publishObj->Images 	 = $publishObjElements["images"];
		
		return $publishObj;
	}
	
	private function post($url, $data) {
		
		$params = array('http' => array('method' => 'POST',
										'header' => "Content-type: application/x-www-form-urlencoded\r\n",
										'content' => http_build_query($data) ));
		
		$ctx = stream_context_create($params);
		
		$response = file_get_contents($url, false, $ctx);
		
		return $response; 
	}

	/**
	 * updateDossier
	 * 
	 * Just resend
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from SmartNews
	**/	
	public function updateDossier(&$dossier, &$objectsindossier, $publishtarget)
	{
		return $this->publishDossier( $dossier, $objectsindossier, $publishtarget );
	}

	/**
	 * unpublishDossier
	 * 
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from SmartNews
	 */
	public function unpublishDossier ($dossier, $objectsindossier, $publishtarget)
	{
		$this->publishDossier($dossier, $objectsindossier, $publishtarget, "UNPUBLISH");
		return null;
	}

	public function previewDossier(&$dossier, &$objectsindossier, $publishtarget)
	{
		$objectsindossier=$objectsindossier; $publishtarget=$publishtarget;
		
		$result = array();
		$result[] = self::getNewPubField('URL','string',array(SMARTNEWS_PUBLISHINTERFACE."?node=".$dossier->ExternalId) );
		return $result;
	}
		
	/**
	 * requestPublishFields
	 * 
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from SmartNews
	**/	
	public function requestPublishFields($dossier, $objectsindossier, $publishtarget)
	{
		$objectsindossier=$objectsindossier; $publishtarget=$publishtarget;
		$result = array();
		$result[] = self::getNewPubField('URL','string',array(SMARTNEWS_PUBLISHINTERFACE."?node=".$dossier->ExternalId) );
		return $result;
	}
	
	/**
	 * getDossierURL
	 * 
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 * 
	 * @return string
	 */	
	public function getDossierURL($dossier, $objectsindossier, $publishtarget)
	{
		$objectsindossier=$objectsindossier; $publishtarget=$publishtarget;
		
		return self::getNewPubField('URL','string',array(SMARTNEWS_PUBLISHINTERFACE."?node=".$dossier->ExternalId) );
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
}
