<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * HTML Conversion to InCopy
 *
**/

class InCopyHTMLConversion
{
	/**
	 * convertHTMLArticle
	 * Convert HTML articles (wwea / html) to InCopy articles
	 * using a template and the (existing) InCopyTextExport class
	 *
	 * @param Object $object
	 */
	public static function convertHTMLArticle ( &$object )
	{
		$replaceVersionGUID = false; //If wwea article contains proper version guids for elements they must be reused.
		
		// No attachment returning means nothing to do (BZ#10948)
		if( empty($object->Files[0]->FilePath) ) {
			return;
		}
		
		$format = $object->MetaData->ContentMetaData->Format;
		$elements = array();
		$elementLabels = array();
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$content = $transferServer->getContent($object->Files[0]);
		
		// @todo: place the switch below in a function
		switch ($format){
			case 'text/wwea':
				//Remove all whitespace characters between closing tag and next opening tag.
				//If element content only contains whitespace it is cleaned (this is a accepted side effect).
				$content = preg_replace('/(>)(\\s+)(<)/m', '\\1\\3', $content); 
				$content = preg_replace('/(<p>)(\&\#160\;)(<\/p>)/m', '\\1\\3', $content);
				//Remove single non-breaking space from paragraph element so empty paragraphs
				//do not start with a space character.				
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
					
					$contentId = $component->getAttribute('contentId');
					
					$content = "";
					foreach($component->getElementsByTagNameNS("urn:EnterpriseArticle", 'data') as $text) {						
						$data = $text->nodeValue;
						$data = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');
						
						$artDoc = new DOMDocument();
						$artDoc->loadHTML($data);
						
						// compose an array of element objects
						$elements[] = self::getElementInfo( $object, $label, $artDoc, $contentId );
						$elementLabels[] = strtolower($label);
					}
				}
				break;
			case 'text/html':
				// only get body content from html
				$artDoc = new DOMDocument();
				$artDoc->loadHTML($content);
				// compose an array of element objects
				$elements[] = self::getElementInfo( $object, "body", $artDoc );
				break;
		}
		
		// load the wwcx template
		$templateDoc = self::loadTemplate( $object->MetaData->BasicMetaData->Publication->Id );
		$templateDoc->formatOutput = true;
		
		// initialize empty story array's
		$finalStories = array();
		
		$xpath = new DOMXPath($templateDoc);

		// get template elements
		$allTemplateStories = array();
		$templateStories = self::getTemplateStories( $xpath, $allTemplateStories );
		// L> $templateStories is based on element names, and therefor does not contain 'duplicates'
		// L> $allTemplateStories contains all stories, including 'duplicates' (to remove them later on: BZ#20793)
		
		// BZ#19505 create a new story for each element in the same order as the elements
		foreach( $elements as $element ) {
			$elementLabel = strtolower($element->Label);
			// pick template story
			$templateStory = self::getTemplateStory($templateStories, $elementLabel);
			$newStoryGUID = self::cloneStory( $xpath, $templateStory, $elementLabel );
			$finalStories[$element->GUID] = $newStoryGUID;
		}
		
		// remove template stories
		foreach( $allTemplateStories as $templateStory ) {
			LogHandler::Log('InCopyHTMLConversion', 'DEBUG', 'Removing story with GUID "'.$templateStory->getAttribute('GUID').'" originated from template.' );
			$templateStory->parentNode->removeChild($templateStory);
		}
		
		// now, go over the finalStories array and parse the final template
		foreach( $finalStories as $elementGUID => $storyGUID ) {
			// find the right element
			$element = null;
			foreach( $elements as &$_element ) {
				if( $_element->GUID == $elementGUID ) {
					$element = $_element;
					break;
				}
			}
			if( is_null($element) ) {
				LogHandler::Log('InCopyHTMLConversion', 'ERROR', 'Could not find element '.$elementGUID.' at story '.$storyGUID );
				continue; // skip!
			}
						
			// find the right story
			$story = $xpath->query( '//Stories/Story[@GUID="'.$storyGUID.'"]');
			$story = $story->item(0);

			if( is_null($story) ) {
				LogHandler::Log('InCopyHTMLConversion', 'ERROR', 'Could not find story '.$storyGUID );
				continue; // skip!
			}

			// define StoryInfo elements
			$siElements = array("SI_EL"			=> $element->Label,
								"SI_Words" 		=> $element->oElement->LengthWords,
								"SI_Chars" 		=> $element->oElement->LengthChars,
								"SI_Paras" 		=> $element->oElement->LengthParas,
								"SI_Lines" 		=> $element->oElement->LengthLines,
								"SI_Snippet" 	=> $element->oElement->Snippet);
			
			if ($element->oElement->Version == '0') {
				$replaceVersionGUID	= true; //All elements have a guid or none has a guid.
			} 
			else {
				$siElements["SI_Version"] = $element->oElement->Version;
			}
			
			// replace StoryInfo on the story
			foreach( $siElements as $siNode => $siValue ) {
				$siDocNode = $xpath->query("StoryInfo/".$siNode, $story);
				if( $siDocNode->length > 0 ) {
					if ($siDocNode->item(0)->hasChildNodes()) { // First check if default values must be removed.
						$childNode = $siDocNode->item(0)->firstChild;
						$siDocNode->item(0)->removeChild($childNode);
					}
					$siDocNode->item(0)->appendChild(new DOMText($siValue));
				} else {
					LogHandler::Log('InCopyHTMLConversion', 'WARN', 'Could not replace StoryInfo for node '.$siNode );
				}
			}
			
			// replace the GUID of existing story with the GUIDs of the matching component
			$story->setAttribute('GUID', $element->GUID);
			
			foreach( $xpath->query('SnippetRoot/*', $story) as $srChild ) {
				switch( $srChild->nodeName ) {
					case 'psty':
						// map the paragraph style
						foreach( $element->paragraphStyles as $className => $icRef ) {
							$element->paragraphStyles[$className] = self::mapParagraphStyle($srChild, $className);
						}
						break;
						
					case 'csty':
						// map the character style
						foreach( $element->characterStyles as $className => $icRef ) {
							$element->characterStyles[$className] = self::mapCharacterStyle($srChild, $className);
						}
						break;
					
					case 'cflo':
						// replace the GUID of the cflo element
						if( substr($srChild->getAttribute('GUID'), 0, 2) == "c_" )
							$srChild->setAttribute('GUID', 'c_'.$element->GUID);
							
						// replace the story title (sTtl) with the element label
						$srChild->setAttribute('sTtl', 'c_'.$element->Label);
						break;
				}
			}
		}		
		
		$storyFrames = array();
		foreach( $elements as $element ) {			
			
			// check if there are any styles (paragraph/character) that aren't mapped
			// check if these styles exist on document/template level, and map them anyway
			$xpath = new DOMXPath($templateDoc);
			foreach( $element->paragraphStyles as $className => $icRef ) {
				if( !$icRef || $icRef == "" ) {
					$element->paragraphStyles[$className] = self::mapParagraphStyleByQuery( $xpath->query("//psty"), $className );
				}
			}
			
			foreach( $element->characterStyles as $className => $icRef ) {
				if( !$icRef || $icRef == "" ) {
					$element->characterStyles[$className] = self::mapCharacterStyleByQuery( $xpath->query("//csty"), $className );
				}
			}
			
			$xpath1 = new DOMXPath($element->Frame);
			// replace the mapped character styles
			foreach( $xpath1->query('//span') as $span ) {
				if( $span->getAttribute('class') ) {
					// BZ#26516 - Replace "_" as "~sep~" to retrieve the character style
					$spanClass = str_replace( '_', '~sep~', $span->getAttribute('class') );
					$span->setAttribute('class', $element->characterStyles[$spanClass]);
				}
			}
			
			// replace the mapped paragraph styles
			foreach( $xpath1->query('//p') as $paragraph ) {
				if( $paragraph->getAttribute('class') ) {
					// BZ#26516 - Replace "_" as "~sep~" to retrieve the paragraph style
					$paraClass = str_replace( '_', '~sep~', $paragraph->getAttribute('class') );
					$paragraph->setAttribute('class', $element->paragraphStyles[$paraClass]);
				}
				
				//if( trim($paragraph->nodeValue) != "" ) {
					//$paragraph->nodeValue = $paragraph->nodeValue."\n";
				//}
			}
			
			$storyFrames[$element->GUID] = $element->Frame;
		}
				
		// merge the articles into the template using the InCopyTextExport class
		require_once BASEDIR."/server/appservices/textconverters/InCopyTextExport.class.php";
		$fc = new InCopyTextExport();
		$fc->ihcPlugin = true;
		$fc->ihcPluginReplaceVersionGuid = $replaceVersionGUID;
		$fc->exportBuf( $storyFrames, $templateDoc, "" );
		
		// set the article object (new format + attachment)
		$object->MetaData->ContentMetaData->Format = "application/incopy";
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$attachment = new Attachment('native', 'application/incopy');
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer($templateDoc->saveXML(), $attachment);		
		$object->Files = array(	$attachment );
	}
	
	/**
	 * Loads and validates the configured article template file (into DOM doc).
	 *
	 * @param string $brandId 
	 * @return DOMDocument The template
	 * @throws BizException When template could not be loaded when its format is not supported.
	 */
	private static function loadTemplate( $brandId )
	{
		$templateFile = self::getTemplateFile( $brandId );
		LogHandler::Log( 'InCopyHTMLConversion', 'DEBUG', 'Loading article template file: "'.$templateFile.'".' );
		$templateDoc = new DOMDocument();
		$templateContent = file_get_contents($templateFile);
		if( !$templateContent ) {
			throw new BizException( 'ERR_NO_DEF_TEMPLATE', 'Server', 
				'The configured article template "'.$templateFile.'" could not be read or found.' );
		}
		if( !$templateDoc->loadXML( $templateContent ) ) {
			throw new BizException( 'ERR_NO_DEF_TEMPLATE', 'Server', 
				'The configured article template "'.$templateFile.'" is not valid XML.' );
		}
		if( $templateDoc->documentElement->nodeName != 'Stories' ) {
			LogHandler::Log( 'InCopyHTMLConversion', 'ERROR', 'Unknown root node name of article template file: "'.$templateDoc->documentElement->nodeName.'".' );
			throw new BizException( 'ERR_NO_DEF_TEMPLATE', 'Server', 
					'The file format of installed article template "'.$templateFile.'" is not supported.' );
		}
		return $templateDoc;
	}
	
	/**
	 * Resolves the article template file path from the componentsDef.xml config file.
	 * When not configured for the given brand, it takes the one for the default brand.
	 * Relative path is accepted and resolved to absolute path. It checks read access to the file too.
	 *
	 * @param string $brandId Publication ID
	 * @return string Absolute file path
	 * @throws BizException When the template file can not be read.
	 */
	private static function getTemplateFile( $brandId )
	{
		// Pick the configurated article template path from componentsDef.xml file
		$templatefile = "";
		$componentDefsFile = BASEDIR."/config/componentDefs.xml";
		
		if( is_file( $componentDefsFile) && is_readable($componentDefsFile) ) {
			$componentDefs = new DOMDocument();
			if( !$componentDefs->loadXML( file_get_contents( $componentDefsFile ) ) ) {
				throw new BizException( 'ERR_NO_DEF_TEMPLATE', 'Server', 
					'The file "'.$componentDefsFile.'" is not valid XML.' );
			}
			
			$xpath = new DOMXPath( $componentDefs );
			if( intval($brandId) > 0 && $brandId != "default" ) {
				foreach( $xpath->query('//brands/brand[@id="'.intval($brandId).'"]/template') as $template ) {
					$templatefile = $template->getAttribute('url');
					break;
				}
				
				if( trim($templatefile) == "" ) {
					return self::getTemplateFile( 'default' );
				}
			} else {
				$brandId = "default";
				foreach( $xpath->query('//brands/brand[@id="'.$brandId.'"]/template') as $template ) {
					$templatefile = $template->getAttribute('url');
					break;
				}
			}
			
		} else {
			throw new BizException( 'ERR_NO_DEF_TEMPLATE', 'Server', 
				'The file "'.$componentDefsFile.'" was not found or readable.' );
		}
		LogHandler::Log( 'InCopyHTMLConversion', 'DEBUG', 'Using configuration file "'.$componentDefsFile.'".' );

		// Resolve relative path and check if the configuted article template file can be read.		
		$isAccessible = false;
		// check if it is an absolute path, and readable
		if( is_file($templatefile) && is_readable($templatefile) ) {
			$isAccessible = true;	
		}else{
			// check if it is a relative path, and readable
			$templatefile = dirname($componentDefsFile)."/".$templatefile;
			if( is_file($templatefile) && is_readable($templatefile) ) {
				$isAccessible = true;
			}
		}
		
		if( !$isAccessible ) {
			throw new BizException( 'ERR_NO_DEF_TEMPLATE', 'Server', 
				'The article template "'.$templatefile.'" was not found or readable. '.
				'(Configured at "'.$componentDefsFile.'" for brand id "'.$brandId.'".)' );
		}
		LogHandler::Log( 'InCopyHTMLConversion', 'DEBUG', 'Found article template "'.$templatefile.'" for brand id "'.$brandId.'".' );
		return $templatefile;
	}
	
	/**
	 * Compose an object with element information
	 *
	 * @param Object 		$object
	 * @param Element Label $label
	 * @param DOMDocument 	$artDoc
	 * @return Element Object
	 * 
	 * @todo; refactor ... horrible name
	 */
	private static function getElementInfo( $object, $label, $artDoc, $contentId = "" )
	{	
		$element = new stdClass();
		$element->Frame = $artDoc;
		$element->Label = $label;
		$element->hasStory = false;
		
		// extract paragraph styles from the frame
		$element->paragraphStyles = self::extractParagraphStyles($artDoc);
		// extract character styles from the frame
		$element->characterStyles = self::extractCharacterStyles($artDoc);
						
		$element->GUID = InCopyUtils::createGUID();
		if( $contentId != "" ) {
			// replace the GUID if the element already has one
			foreach( $object->Elements as $objElement ) {
				if( $objElement->ID == $contentId ) {
					$element->GUID = $contentId;
					$element->oElement = $objElement;
				}
			}
		}
		
		return $element;
	}
	
	private static function extractParagraphStyles( $artDoc )
	{
		$paragraphStyles = array();
		
		$xpath1 = new DOMXPath($artDoc);
		foreach( $xpath1->query('//p') as $paragraph ) {
			if( $paragraph->getAttribute('class') ) {
				// BZ#26516 - Replace "_" as "~sep~" for later comparison on InCopy Template
				$paraClass = str_replace( '_', '~sep~', $paragraph->getAttribute('class') );
				$paragraphStyles[$paraClass] = "";
			}
		}
		
		return $paragraphStyles;
	}
	
	private static function extractCharacterStyles( $artDoc )
	{
		$characterStyles = array();
		
		$xpath1 = new DOMXPath($artDoc);
		foreach( $xpath1->query('//span') as $span ) {
			if( $span->getAttribute('class') ) {
				// BZ#26516 - Replace "_" as "~sep~" for later comparison on InCopy Template
				$spanClass = str_replace( '_', '~sep~', $span->getAttribute('class') );
				$characterStyles[$spanClass] = "";
			}
		}
		
		return $characterStyles;
	}

	/**
	 * Clone a story into the DOM tree
	 *
	 * @param DOMXPath $xpath
	 * @param DOMElement $cloneStory
	 * @param string $elementLabel
	 * @return string new story GUID
	 */
	private static function cloneStory( $xpath, $cloneStory, $elementLabel = "" )
	{
		$newStory = $cloneStory->cloneNode(true);
		$newStory->setAttribute('GUID', InCopyUtils::createGUID() );
		$cloneStory->parentNode->appendChild( $newStory );	

		if( LogHandler::debugMode() ) { // avoid getNodePath() in production
			LogHandler::Log( 'InCopyHTMLConversion', 'DEBUG', 'Cloning node "'.self::getNodePath( $cloneStory ).'" '.
							'with GUID "'.$cloneStory->getAttribute( 'GUID' ).'" to new node '.
							'with GUID "'.$newStory->getAttribute( 'GUID' ).'".');
		}
		
		if( $elementLabel != "" ) {
			// update the element label of the story
			$siDocNode = $xpath->query("StoryInfo/SI_EL", $newStory);
			$siDocNode->item(0)->nodeValue = $elementLabel;
		}
		
		return $newStory->getAttribute('GUID');
	}

	// remove a story from the DOM tree
	private static function removeStory( &$templateDoc, $removeStoryGUID )
	{
		$xpath = new DOMXPath($templateDoc);
		$removeStory = $xpath->query( '//Stories/Story[@GUID="'.$removeStoryGUID.'"]');
		
		foreach( $removeStory as $story ) {
			$story->parentNode->removeChild($story);
		}
	}
	
	private static function mapCharacterStyleByQuery( $xpathQuery, $className )
	{
		$icRef = "";
		foreach( $xpathQuery as $csty ) {
			$icRef = self::mapCharacterStyle( $csty, $className );
			if( $icRef != "" ) {
				break;
			}
		}
		
		return $icRef;
	}
	
	private static function mapCharacterStyle( $csty, $className )
	{
		$icRef = "";
		
		if( $csty->getAttribute('pnam') && $csty->getAttribute('Self') && $className == substr($csty->getAttribute('pnam'),2) ) {
			$icRef = "charo_".substr($csty->getAttribute('Self'), 3);
		}
		
		return $icRef;
	}
	
	private static function mapParagraphStyleByQuery( $xpathQuery, $className )
	{
		$icRef = "";
		foreach( $xpathQuery as $psty ) {
			$icRef = self::mapParagraphStyle( $psty, $className );
			if( $icRef != "" ) {
				break;
			}
		}
		
		return $icRef;
	}
	
	private static function mapParagraphStyle( $psty, $className )
	{
		$icRef = "";
		if( $psty->getAttribute('pnam') && $psty->getAttribute('nxpa') && $className == substr($psty->getAttribute('pnam'),2) ) {
			$icRef = "para".$psty->getAttribute('nxpa');
		}
		
		return $icRef;
	}

	/**
	 * Get a template story element that best matches the element label
	 *
	 * @param array $templateStories
	 * @param string $elementLabel lowercase element label
	 * @return DOMElement
	 */
	protected static function getTemplateStory(array $templateStories, $elementLabel)
	{
		$templateStory = null;
		if (isset($templateStories[$elementLabel])){
			// template with same element name
			$templateStory = $templateStories[$elementLabel];
		} else {
			if (isset($templateStories[''])){
				// template with empty element name
				$templateStory = $templateStories[''];
			} else {
				// first template
				$templateStory = reset($templateStories);
			}
		}
		
		return $templateStory;
	}

	/**
	 * Get template story elements from a InCopy template file.
	 * When there are duplicate story labels, only the first one (of duplicates) is included.
	 *
	 * @param DOMXPath $xpath
	 * @param array $allTemplateStories (BZ#20793) Returns -all- story nodes (array of DOMElement).
	 * @return array with key: lowercase element name; value: DOMElement story
	 */
	protected static function getTemplateStories( DOMXPath $xpath, array &$allTemplateStories )
	{
		$siElements = $xpath->query('//Stories/Story');
		$templateStories = array();
		for ($i = 0; $i < $siElements->length; $i++){
			$elNameNodes = $xpath->query("StoryInfo/SI_EL", $siElements->item($i));
			$elName = strtolower($elNameNodes->item(0)->nodeValue);
			if( !isset($templateStories[$elName]) ) {
				$templateStories[$elName] = $siElements->item($i);
				LogHandler::Log( 'InCopyHTMLConversion', 'DEBUG', 'Found story "'.$elName.'" with GUID "'.$templateStories[$elName]->getAttribute('GUID').'".' );
			} else {
				LogHandler::Log( 'InCopyHTMLConversion', 'DEBUG', 'Ignoring duplicate story "'.$elName.'" with GUID "'.$siElements->item($i)->getAttribute('GUID').'".' );
			}
			$allTemplateStories[] = $siElements->item($i);
		}
		if (count($templateStories) == 0){
			throw new BizException('ERR_NO_DEF_TEMPLATE', 'Server', 
				'Could not find story elements in article template');
		}
		
		return $templateStories;
	}
	
	/**
	 * Returns the full XML/DOM path for a given XML node. This used for heavy debuging only.
	 * When anything goes wrong during document conversion, you can pass in any XML node
	 * to see which node is processed at a certain point in the source code.
	 *
	 * @param DOMNode $node
	 * @return string Full DOM/XML path
	 */
	private static function getNodePath( DOMNode $node )
	{
		$names = array();
		while( $node ) {
			$names[] = $node->nodeName;
			$node = $node->parentNode;
		}
		return implode( '/', array_reverse( $names ) );
	}
}
