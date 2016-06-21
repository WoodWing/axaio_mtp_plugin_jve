<?php

/**
 * Converts WCML (WoodWing InCopy XML) article into XHTML 1.1 format using CSS 2.1.
 * WCML is a container that bundles multiple ICML (InCopy XML) formatted files.
 *
 * @package Enterprise
 * @subpackage WebEditor
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/appservices/textconverters/TextImportExport.intf.php'; // TextImport
require_once BASEDIR.'/server/appservices/textconverters/InCopyTextUtils.php';

class WW_TextConverters_Wcml2Xhtml extends HtmlTextImport
{
	// custom styling
	protected $customCSS = null;
	
	// InCopy input
	protected $docDomVersion = null;
	protected $icDoc = null;
	protected $icXPath = null;
	protected $icStoryLabels = null; // key=guid, value=label
	protected $icFootnotes = null;
	protected $icChangeParentNode = null;
	protected $prevParaNode = null;
	protected $prevCharNode = null;
	protected $prevChangeCursor = null;
	protected $firstChangeParaNode = null;
	
	// XHTML output
	protected $xDoc = null;
	protected $xCursor = null; // insertion point
	protected $xColors = array();
	protected $xFrames = array(); // structured output
	protected $xStyles = null;
	protected $imageWidth = null;
	protected $imageHeight = null;
	
	// The name of the paragraph element
	protected $paragraphElementName = 'p';
	
	// Export inline images indicator
	protected $exportInlineImages = null;
	protected $uniqueInlineImageIds = array();

	/**
	 * Convert a file from InCopy (WCML) text format to collection of XHTML frames.
	 *
	 * @param string $icFile  Full path of file to be read.
	 * @param array  $xFrames   (out) Returned collection of XHTML DOMDocument each representing one text frame.
	 * @param string $stylesCSS (out) Cascade para+char style definition.
	 * @param string $stylesMap (out) Para+char styles; GUI display name mapped onto CSS name.
	 * @param integer $domVersion (out) Major version number of the document. Zero when undetermined. CS1=3, CS2=4, CS3=5, CS4=6, etc
	 */
	public function importFile( $icFile, &$xFrames, &$stylesCSS, &$stylesMap, &$domVersion )
	{
		LogHandler::Log( 'textconv', 'INFO', 'Wcml2Xhtml->import(): Reading InCopy Text from ['.$icFile.']');
		$icDoc = new DOMDocument();
		$icDoc->loadXML( file_get_contents( $icFile ) ); // URL encoded paths fail: $icDoc->load( $icFile ); (BZ#6561)
		LogHandler::Log( 'textconv', 'INFO', 'Wcml2Xhtml->import(): Convert InCopy to HTML for ['.$icFile.']');
		$this->importBuf( $icDoc, $xFrames, $stylesCSS, $stylesMap, $domVersion );
	}
	
	/**
	 * Convert a memory buffer from InCopy (WCML) text format to collection of XHTML frames.
	 *
	 * @param DOMDocument $icDoc  InCopy document to be parsed.
	 * @param array $xFrames    (out) Returned collection of XHTML DOMDocument each representing one text frame.
	 * @param string $stylesCSS (out) Cascade para+char style definition.
	 * @param string $stylesMap (out) Para+char styles; GUI display name mapped onto CSS name.
	 * @param integer $domVersion (out) Major version number of the document. Zero when undetermined. CS1=3, CS2=4, CS3=5, CS4=6, etc
	 */
	public function importBuf( $icDoc, &$xFrames, &$stylesCSS, &$stylesMap, &$domVersion )
	{
		$this->icDoc = $icDoc;
		$this->convert( null );
		$stylesCSS = $this->getStylesCSS();
		$xFrames = $this->xFrames;
		$stylesMap = $this->xStyles;
		$domVersion = $this->docDomVersion;
	}

	/**
	 * Major version number of the document. Zero when undetermined. CS1=3, CS2=4, CS3=5, CS4=6, CS5=7, etc
	 * Must be called AFTER calling convert() function.
	 *
	 * @return integer
	 */
	public function getDocDomVersion()
	{
		return $this->docDomVersion;
	}
	
	/**
	 * By default, the text converter derives CSS para/char styles from the WCML file.
	 *    => See postProcessStyles() to customize that process.
	 * These styles can be extended by calling setCustomCSS() with your own CSS file content.
	 * Must be called BEFORE calling getOutputAsString() or getOutputAsDOM() function.
	 *
	 * @param string $customCSS The CSS file content.
	 */
	public function setCustomCSS( $customCSS )
	{
		$this->customCSS = $customCSS;
	}

	/**
	 * List of story information with content. Can be used to iterate through XHTML output fragments.
	 * Each element has the following attributes: ID, Label, Content and Document.
	 * Must be called AFTER calling convert() function.
	 *
	 * @return array
	 */
	public function getStories()
	{
		return $this->xFrames;
	}

	/**
	 * Returns the DOCTYPE instruction that needs to be inserted before the XHTML output.
	 * This is needed since the XML parser does not do so.
	 *
	 * @return string
	 */
	public function getDocType()
	{
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.PHP_EOL;
	}
	
	/**
	 * Returns the XHTML document as DOM tree. This can be used to manipulate the document
	 * after calling the convert() function. Do not use this to adjust styles; Please use the
	 * more powerful/dedicated parseStyles() and setCustomCSS() functions for that.
	 * Note that the DOCTYPE is not ouputed by XML parser, so this is needs to be inserted by 
	 * caller by using the getDocType() function.
	 *
	 * @return DOMDocument The XHTML document.
	 */
	public function getOutputAsDOM()
	{
		// Look up 'head' at XHTML as created by convert() before
		$xPath = new DOMXPath( $this->xDoc );
		$xHeads = $xPath->query( '/html/head' );
		$xHead = $xHeads->item(0);

		// Add CSS styles to XHTML
		$xStyle = $this->xDoc->createElement( 'style' );
		$xStyle->setAttribute( 'type', 'text/css' );
		$xStyle->setAttribute( 'media', 'screen' );
		$xHead->appendChild( $xStyle );
		$xText = $this->xDoc->createComment( PHP_EOL.$this->getStylesCSS() );
		$xStyle->appendChild( $xText );

		// Return the built XHTML to caller.
		return $this->xDoc;
	}
	
	/**
	 * Returns the XHTML document as string. 
	 * Unlike getOutputAsDOM(), the DOCTYPE statement is included.
	 *
	 * @return string The XHTML document.
	 */
	public function getOutputAsString()
	{
		$dom = $this->getOutputAsDOM();
		$docType = $this->getDocType();
		return $docType.$dom->saveXML();
	}
	
	/**
	 * Returns all CSS styles embedded at the XHTML document.
	 * This includes the styles provided through setCustomCSS().
	 *
	 * @return string CSS
	 */
	public function getStylesCSS()
	{
		// Derive styles from WCML and append the custom CSS file (when provided)
		$xCSS = $this->buildCSS();
		if( !is_null($this->customCSS) ) { 
			$xCSS .= $this->customCSS;
		}
		return $xCSS;
	}
	
	/**
	 * Converts WoodWing InCopy (WCML/XML) article into XHTML format.
	 * Call getOutputAsString() or getOutputAsDOM() to retrieve the XHTML results.
	 * During processing the WCML document, the internal XHTML insertion cursor ($this->xCursor) is 
	 * updated before adding nodes to the XHTML document. 
	 * All functions in the class that are prefixed with 'convert' are helper functions to pass
	 * all kinds of substructures found at the WCML document. Through all these functions, the XHTML
	 *  document ($this->xDoc) is built and is complete after calling this function.
	 * It also builds a list of stories/frames ($this->xFrames) that can be access after calling
	 * this function.
	 *
	 * @param string $icContent  The WCML file content
	 */
	public function convert( $icContent = null )
	{
		// Load InCopy file
		LogHandler::Log( 'textconv', 'INFO', __METHOD__.': Converting WCML article.' );
		if( !is_null($icContent) ) {
			$this->icDoc = new DOMDocument();
			$this->icDoc->loadXML( $icContent ); // URL encoded paths fail: $icDoc->load( $icContent ); (BZ#6561)
		}
		$this->icXPath = new DOMXPath( $this->icDoc );
		$this->icXPath->registerNamespace('ea', 'urn:SmartConnection_v3');

		// Create XHTML document
		$this->xDoc = new DOMDocument('1.0','UTF-8');
		//$this->xDoc->formatOutput = true; // BZ#22685: No format since whitespaces gets intepreted badly in output
		$xRoot = $this->xDoc->createElement( 'html' );
		$xRoot->setAttribute( 'xmlns', 'http://www.w3.org/1999/xhtml' );
		$this->xDoc->appendChild( $xRoot );

		// Add head to XHTML with meta info
		$xHead = $this->xDoc->createElement( 'head' );
		$xRoot->appendChild( $xHead ); // add 'head' to 'html' node
		$xMeta = $this->xDoc->createElement( 'meta' );
		$xMeta->setAttribute( 'http-equiv', 'content-type' );
		$xMeta->setAttribute( 'content', 'text/html;charset=utf-8' );
		$xHead->appendChild( $xMeta );

		// Add body to XHTML
		$xBody = $this->xDoc->createElement( 'body' );
		$xRoot->appendChild( $xBody );

		// Convert InCopy to XHTML
		$this->buildColors();
		$this->buildStyles();
		$this->convertIcDoc( $xBody ); 
		LogHandler::Log( 'textconv', 'INFO', __METHOD__.': Converted WCML article.' );
	}

	/**
	 * Passes on para/char/override style information read from given InCopy style node that is
	 * about to get applied to CSS (for para/char styles) or to 'style' attribute (for overrides).
	 * Allowed to overrule this function with your custom subclass to adjust/add/remove style info.
	 * Note that the CSS style can be built with single quotes to fit into 'style' attribute
	 * for overrides, or can have double quotes for para/char style definitions (CSS).
	 *
	 * @param DOMNode $icNode WCML/input node being processed
	 * @param DOMNode $xNode XHTML/output node being processed. Set for overrides. Null for para/char style definitions.
	 * @param array $css Style map to post-process. CSS style properties (keys) and their values.
	 * @param bool $isOverride TRUE when override style or FALSE when para/char style definition
	 * @param bool $isPara TRUE when para style or FALSE when char style
	 * @param string $styleName para/char style applied. Also set for overrides. Name is not encoded for CSS (yet).
	 * @return array The adjusted $css.
	 */
	protected function postprocessStyles( array $css, DOMNode $icNode, $xNode, $isOverride, $isPara, $styleName )
	{
		$icNode = $icNode; $xNode = $xNode; $isOverride = $isOverride; $isPara = $isPara; $styleName = $styleName; // keep analyzer happy
		return $css;
	}
	
	// - - - - - - - - - - - - - PRIVATE FUNCTIONS - - - - - - - - - - - - - - - - - - - 

	/**
	 * Does the actual conversion of the WCML article to XHTML. The converted document
	 * is added to the given XHTML body node ($xBody). See convert() for more details.
	 *
	 * @param DOMNode $xBody
	 */
	private function convertIcDoc( DOMNode $xBody )
	{
		// Collect story labels to lookup later
		$this->icStoryLabels = array();
		$this->icFootnotes = array();

		// BZ#32485 - Use SI_EL node for element label, as Story->StoryTitle sometimes out of sync
		$icStoryLabels = $this->icXPath->query( '/ea:Stories/ea:Story/ea:StoryInfo/ea:SI_EL/text()' );
		foreach( $icStoryLabels as $icStoryLabelNode ) {
			$guid = $icStoryLabelNode->parentNode->parentNode->parentNode->getAttribute('ea:GUID');
			$icStoryLabel = $icStoryLabelNode->nodeValue;
			$this->icStoryLabels[$guid] = $icStoryLabel;

			// Build compatible frame structure with other text converters
			$xFrame = new stdClass();
			$xFrame->ID           = $guid;
			$xFrame->Label        = $icStoryLabel;
			$xFrame->Content      = null; // set at convertStory()
			$xFrame->Document     = null; // "
			$xFrame->Left         = null; // TODO
			$xFrame->Top          = null; // "
			$xFrame->Width        = null; // "
			$xFrame->Height       = null; // "
			$xFrame->PageSequence = null; // "
			$xFrame->InlineImageIds = array(); // set at convertContent()
			$this->xFrames[$guid] = $xFrame;
		}

		// Read Document from InCopy
		$icStoryDocs = $this->icXPath->query( '/ea:Stories/ea:Story/Document' );
		foreach( $icStoryDocs as $icStoryDoc ) {

			// Detect the internal document model version
			$majorMinor = $icStoryDoc->getAttribute('DOMVersion');
			$regs = array();
			preg_match('/([0-9]+\.[0-9]+)/i', $majorMinor, $regs ); 
			if( count($regs) > 0 ) {
				$this->docDomVersion = ($regs[1]); // remember for later use
			}

			$this->xCursor = $xBody; // insertion point
			$this->convertDocument( $icStoryDoc );
		}
	}
	
	/**
	 * Converts a WCML document/story to XHTML. See convert() for more details.
	 * InCopy path: /ea:Stories/ea:Story/Document
	 */
	private function convertDocument( DOMNode $icStoryDoc )
	{
		// Read Story from InCopy
		$xThisCursor = $this->xCursor;
		$icStories = $this->icXPath->query( 'Story', $icStoryDoc );
		foreach( $icStories as $icStory ) {
			$this->xCursor = $xThisCursor; // insertion point
			$this->convertStory( $icStory );
		}
	}

	/**
	 * Converts a WCML document/story to XHTML. See convert() for more details.
	 * InCopy path: ../Document/Story
	 */
	protected function convertStory( DOMNode $icStory )
	{
		// Add span to XHTML
		$xStory = $this->xDoc->createElement( 'div' ); // Use 'div' cauz 'p' inside 'span' does not work for iPad reader
		$guid = $icStory->getAttribute('Guid');
		$classes = isset($this->icStoryLabels[$guid]) ? $this->icStoryLabels[$guid] : '';
		$xStory->setAttribute( 'class', 'story '.$this->cssEncode( 'story_'.$classes, false ) );
		$xStory->setAttribute( 'id', $guid );
		$this->xCursor->appendChild( $xStory );

		// Convert WCML story childs (e.g. ParagraphStyleRange, XMLElement, etc) to XHTML
		$this->xCursor = $xStory; // insertion point
		$this->currentGuid = $guid;
		$this->convertContent( $icStory );

		// Add footnotes to XHTML at end of story (if there are any)
		$this->convertFootnotes( $xStory, $guid );
		
		// Update frame info (to let caller iterate through content per story)
		$xFrame = isset($this->xFrames[$guid]) ? $this->xFrames[$guid] : new stdClass();
		$xFrame->Content = $this->xDoc->saveXML( $xStory );
		$xFrame->Document = $xStory;
	}

	/**
	 * During WCML->XHTML conversion, footnotes were collected.
	 * Adds all collected footnotes to XHTML at end of given story.
	 *
	 * @param DOMNode $xStory
	 * @param string $guid Story's ID
	 */
	private function convertFootnotes( DOMNode $xStory, $guid )
	{
		if( isset($this->icFootnotes[$guid]) && count($this->icFootnotes[$guid]) > 0 ) {

			// Add container (div) to XHTML to group footnotes of this story
			$xGroup = $this->xDoc->createElement( 'div' );
			$xGroup->setAttribute( 'class', 'footnote_group' );
			$xStory->appendChild( $xGroup );
			
			foreach( $this->icFootnotes[$guid] as $footnoteId => $icFootnote ) {

				// Add footnote container (div) to XHTML
				$xFoot = $this->xDoc->createElement( 'div' );
				$xFoot->setAttribute( 'class', 'footnote' );
				$xFoot->setAttribute( 'style', 'counter-reset: footnote_counter '.$footnoteId );
				$xFoot->setAttribute( 'id', 'footnote_'.$footnoteId );
				$xGroup->appendChild( $xFoot );

				// Process paras inside the footnote (that were skipped by processor above)
				$icParas = $this->icXPath->query( 'ParagraphStyleRange', $icFootnote );
				foreach( $icParas as $icPara ) {
					$this->xCursor = $xFoot; // insertion point
					$this->convertParagraphStyleRange( $icPara );
				}
			}
		}
	}

	/**
	 * Converts a WCML paragraph. See convert() for more details.
	 * InCopy path: ../Document/Story/ParagraphStyleRange
	 *
	 * @param DOMNode $icPara ParagraphStyleRange node
	 * @param DOMNode $icOneChar CharacterStyleRange node
	 * @param boolean $skipCharacterStyleRange Skip creation of CharacterStyle <span> element
	 * @param boolean $includeStyles True to create <strong> or <em> style element
	 */
	protected function convertParagraphStyleRange( DOMNode $icPara, $icOneChar = null, $skipCharacterStyleRange = false, $includeStyles = true )
	{
		$styleName = $icPara->getAttribute('AppliedParagraphStyle');
		$styleName = str_replace( 'ParagraphStyle/', 'para_', $styleName );

		// Parse inline para/list styles defined at WCML
		$xStyleMap = $this->parseStyles( $icPara, true, true ); // override para
		
		// The below deal with lists. Numbered lists are leveled while bullet lists are not. So list trees 
		// should be made of numbered lists (ol), NOT from bullets (ul) ...! Only at the -deepest- level
		// of a numbered list, you can possibly find bullet lists, like they could appear anywhere else
		// at the story (like div->p->span or table). In other terms, numbered lists should never appear
		// inside bullet lists. The way bullets can be 'leveled' is manually changing (override) the numbered
		// list type at ID/IC into a bullet, but that clears the level attribute and so they are hooked up
		// at the structure (and level) of the numbered list.
		
		// Take over numbered/bullet list from para def, since that is structural info (not style info)
		if( isset($this->xStyles[$styleName]['__counter__']) ) {
			foreach( $this->xStyles[$styleName]['__counter__'] as $cntAttrKey => $cntAttrVal ) {
				if( !isset($xStyleMap['__counter__'][$cntAttrKey]) ) {
					$xStyleMap['__counter__'][$cntAttrKey] = $cntAttrVal;
				}
			}
		}
		$listType = isset($xStyleMap['__counter__']['list-type']) ? $xStyleMap['__counter__']['list-type'] : '';

		// The insertion point (xCursor) should be at story (div) or at list (div->ol->li).
		// Here we pick-up the list (ol) e.g. in case there was a <br> under list item or a style change.
		$xCurListTop = null;
		if( $this->xCursor->nodeName == 'li' ) {
			$xCurListTop = $this->xCursor->parentNode; // ol/ul
		} else {
			// When there was a para-style change for the next list item, the insertion point (xCursor)
			// could be at story (div) while underneath, the last added element was a list. In that
			// case we need to give into the list to make that the parental insertion point instead.
			if( $this->xCursor->nodeName != 'ol' && $this->xCursor->nodeName != 'ul' && $this->xCursor->lastChild && 
					($this->xCursor->lastChild->nodeName == 'ol' || $this->xCursor->lastChild->nodeName == 'ul') ) {
				$xCurListTop = $this->xCursor->lastChild; // ol/ul
			}
		}
		
		// Process bullet lists
		if( $listType == 'ul' ) {
			if( $xCurListTop ) {
				// Bullet list 'inside' numbered list; go one step up to make list items (li) at same level.
				if( $xCurListTop->nodeName == 'ol' ) {
					$xCurListTop = $xCurListTop->parentNode;
				}
				$this->xCursor = $xCurListTop;
			}
			if( !$xCurListTop || $xCurListTop->nodeName != 'ul' ) {
				// Create new bullet list (ul) at XHTML
				$xList = $this->xDoc->createElement( 'ul' );
				$this->xCursor->appendChild( $xList );
				$this->xCursor = $xList;
			}
			// Create new list item (li) at XHTML
			$xItem = $this->xDoc->createElement( 'li' );
			$this->xCursor->appendChild( $xItem );
			$this->xCursor = $xItem;
		}
		
		// Add list structure to XHTML when needed
		if( $listType == 'ol' ) {

			// Climb up to highest parent of the list system
			if( $xCurListTop ) { 
				while( $xCurListTop->parentNode->nodeName == 'ol' || $xCurListTop->parentNode->nodeName == 'ul' ) {
					$xCurListTop = $xCurListTop->parentNode;
				}
				// Do not allow numbered lists inside bullet lists (only allow vice versa)
				if( $xCurListTop->nodeName == 'ul' ) {
					$xCurListTop = null;
				} /*else {
					LogHandler::Log( 'WCML2XHTML', 'DEBUG', 'Found (top of) list: '.$this->getNodePath($xCurListTop) );
				}*/
			}

			// To ease data access, repair missing attributes
			if( !isset($xStyleMap['__counter__']['num-type']) ) {
				$xStyleMap['__counter__']['num-type'] = 'd';
			}
			if( !isset($xStyleMap['__counter__']['level']) || !($xStyleMap['__counter__']['level']) ) {
				$xStyleMap['__counter__']['level'] = 1;
			}
			/*LogHandler::Log( 'WCML2XHTML', 'DEBUG', 'icPara:'.$this->getNodePath($icPara) );
			LogHandler::Log( 'WCML2XHTML', 'DEBUG', 'xCursor:'.$this->getNodePath($this->xCursor) );
			LogHandler::Log( 'WCML2XHTML', 'DEBUG', 'counter:'.print_r($xStyleMap['__counter__'],true) );*/
			
			// Build list tree at XHTML as deep as requested by WCML.
			$xCurListLeaf = $xCurListTop;
			for( $i = 1; $i <= $xStyleMap['__counter__']['level']; $i++ ) {
				if( $xCurListLeaf && $xCurListLeaf->lastChild && $xCurListLeaf->lastChild->nodeName == 'ol' ) {
					$xCurListLeaf = $xCurListLeaf->lastChild;
				}
				
				// Determine wether we should create a new list a XHTML
				$a = !$xCurListLeaf; // we did not have a list previous time? => create new one
				$b = $xCurListLeaf && ($xStyleMap['__counter__']['num-type'] != $xCurListLeaf->getAttribute('type'));
				$c = isset( $xStyleMap['__counter__']['start'] );
				$d = $xCurListTop && $xCurListTop->nodeName == 'ul';
				$newList = $a || $b || $c || $d;
				//LogHandler::Log( 'WCML2XHTML', 'DEBUG', "New list: $newList ($a,$b,$c,$d) {$this->xCursor->nodeName}" );

				// Create new list (ol) at XHTML when needed
				if( $newList ) {
					$xList = $this->xDoc->createElement( $listType );
					//$xList->setAttribute( 'id', 'a' ); // debug
					if( isset($xStyleMap['__counter__']['start']) ) {
						$xList->setAttribute( 'start', $xStyleMap['__counter__']['start'] );
					}
					if( !empty($xStyleMap['__counter__']['num-type']) ) {
						$xList->setAttribute( 'type', $xStyleMap['__counter__']['num-type'] );
					}
					if( $xCurListLeaf ) {
						$xCurListLeaf->appendChild( $xList );
					} else {
						$this->xCursor->appendChild( $xList );
					}
					$xCurListLeaf = $xList;
					if( !$xCurListTop ) {
						$xCurListTop = $xList;
					}
				}
			}
			/*if( $xCurListLeaf ) {
				LogHandler::Log( 'WCML2XHTML', 'DEBUG', 'Picked list leaf:'.$this->getNodePath($xCurListLeaf) );
			}*/
			
			// Create new list item (li) at XHTML
			$xItem = $this->xDoc->createElement( 'li' );
			$xCurListLeaf->appendChild( $xItem );
			$this->xCursor = $xItem;
		} 
		
		// When no more list wanted at WCML, escape from XHTML list
		if( $listType != 'ol' && $listType != 'ul') {
			if( $xCurListTop ) {
				$this->xCursor = $xCurListTop->parentNode;
			}
		}
		
		// Add paragraph to XHTML
		$xPara = $this->xDoc->createElement( $this->paragraphElementName );
		$this->xCursor->appendChild( $xPara );

		// Let subclass (if any) customize the styles before adding to XHTML
		$xStyles = '';
		$xStyleMap = $this->postProcessStyles( $xStyleMap, $icPara, $xPara, true, true, $styleName );
		foreach( $xStyleMap as $key => $val ) {
			if( $key != '__specials__' && $key != '__counter__' ) {
				$xStyles .= $key . ': ' . $val . '; ';
			}
		}
		if( $xStyles ) {
			$xPara->setAttribute( 'style', $xStyles );
		}
		// BZ#26720 - Add simple "NormalParagraphStyle" to the class, when para style = [Basic Paragraph]
		$normalParaStyle = @substr_compare( $styleName, 'NormalParagraphStyle', -19, 20) ? '':' NormalParagraphStyle';
		$xPara->setAttribute( 'class', 'para '.$this->cssEncode( $styleName, false ).$normalParaStyle );
		$this->xCursor = $xPara; // insertion point

		// Continue parsing char style...
		if( $icOneChar ) {
			if( !$skipCharacterStyleRange ) {
				$this->convertCharacterStyleRange( $icOneChar, false, $includeStyles );
				$this->prevCharNode = $icOneChar;  // Set the previous CharacterStyleRange node
			}
		} else {
			// Read CharacterStyleRange from InCopy
			$icNodes = $this->icXPath->query( '*', $icPara ); // BZ#33975 - Get all child nodes, including those under <Change> node
			foreach( $icNodes as $icNode ) {
				// BZ#31733 - First check if there is change node, if yes, then query CharacterStyleRange under change node
				if( $icNode->nodeName == 'Change' && $icNode->getAttribute( 'ChangeType' ) != 'DeletedText' ) { // Continue process when it is not DeletedText
					$icChangeChars =  $this->icXPath->query( 'CharacterStyleRange', $icNode );
					foreach( $icChangeChars as $icChangChar ) {
						$icChars[] = $icChangChar;
					}
				} elseif( $icNode->nodeName == 'CharacterStyleRange' ) {
					$icChars[] = $icNode;
				}
			}
			$prevBreak = false;
			foreach( $icChars as $icChar ) {

				// BZ#25298/BZ#25469 Always take the nearest ancestor paragraph element, since the func call
				// convertCharacterStyleRange() at the end of this loop could move the $this->xCursor to 
				// deeper paragraph elements. This happens for tables, which have cells wherein another 
				// paragraph could get created (p->span->table->tbody->tr->td->p->span) which must be pickup.
				$xPath = new DOMXPath( $this->xDoc );
				$xParas = $xPath->query( 'ancestor-or-self::' . $this->paragraphElementName, $this->xCursor );
				if( $xParas->length > 0 ) {
					$xPara = $xParas->item( $xParas->length-1 ); // choose nearest ancestor (0=highest, n-1=deepest)
				} else {
					// Should never happen... but let's continue since we have an xPara element so we can at least 
					// output all text so nothing gets lost, except that the text might be hang up under the wrong
					// parental element, and so could occur somewhere else in the document (very bad but not fatal).
					LogHandler::Log( 'textconv', 'ERROR', __METHOD__.': Could not find ancestor paragraph in path: '.$this->getNodePath($this->xCursor) );
				}

				// Deal with no-breaks that avoids text wrapping.
				// This can not be applied onto styles since there can be many
				// char style elems on which no-break must be applied as a whole,
				// or else, the UA will break between the spans! For example "sept 12th 2010" for which
				// no-break is set to the entire fragment, but because "th" is in superscript, there is
				// a span around it and so the UA will break after "sept 12", which is wrong.
				// Therefor the below creates a nobr elem and collects char spans as long as the
				// no-break option is enabled and not changed over the spans.
				$noBreak = $icChar->getAttribute('NoBreak');
				if( $noBreak && $noBreak == 'true' ) {
					if( !$prevBreak ) {
						$xNoBreak = $this->xDoc->createElement( 'nobr' );
						$xPara->appendChild( $xNoBreak );
					}
					$this->xCursor = $xNoBreak; // insertion point
					$prevBreak = true;
				} else {
					// Because a Br element can cause a new paragraph element, we need to search for this
					// paragraph element so we always put the content in the correct node. If the paragraph
					// element isn't found, we use the created xPara node. 
					$this->xCursor = $xPara;
					$prevBreak = false;
				}
				$this->convertCharacterStyleRange( $icChar );
			}
		}
	}
	
	/**
	 * Converts a WCML styled text selection to XHTML. See convert() for more details.
	 * InCopy path: ../Document/Story/ParagraphStyleRange/CharacterStyleRange
	 */
	protected function convertCharacterStyleRange( DOMNode $icChar, $includeContent = true, $includeStyles = true )
	{
		// Check the FontStyle, if there is bold or italic there, we may have to change the tag.
		$hasBoldFont = false;
		$hasItalicFont = false;
		$hasObliqueFont = false;
		$fontStyle = $icChar->getAttribute('FontStyle');
		if ( '' != $fontStyle ) {
			// Upper case the character Style.
			$fontStyle = strtoupper( $fontStyle );
			$hasBoldFont = (strstr( $fontStyle, 'BOLD') !== FALSE);
			$hasItalicFont = (strstr( $fontStyle, 'ITALIC') !== FALSE);
			$hasObliqueFont = (strstr( $fontStyle, 'OBLIQUE') !== FALSE);
		}

		// Add span to XHTML
		$xChar = $this->xDoc->createElement( 'span' );
		$styleName = $icChar->getAttribute('AppliedCharacterStyle');
		$styleName = str_replace( 'CharacterStyle/', 'char_', $styleName );
		// BZ#26720 - Add simple "No_character_style" to the class, when character style = [None]
		$noCharStyle = @substr_compare( $styleName, '[No_character_style]', -19, 20) ? '':' No_character_style';
		$xChar->setAttribute( 'class', 'char '.$this->cssEncode( $styleName, false ).$noCharStyle );
		$this->xCursor->appendChild( $xChar );

		// Parse inline char styles and add them to XHTML 'style' attribute
		$xStyles = '';
		$xStyleMap = $this->parseStyles( $icChar, true, false ); // override char
		$xStyleMap = $this->postProcessStyles( $xStyleMap, $icChar, $xChar, true, false, $styleName );
		foreach( $xStyleMap as $key => $val ) {
			if( $key != '__specials__' && $key != '__counter__' ) {
				$xStyles .= $key . ': ' . $val . '; ';
			}
		}
		if( $xStyles ) {
			$xChar->setAttribute( 'style', $xStyles );
		}

		$this->xCursor = $xChar; // insertion point

		if( $includeStyles ) {
			// Prior to adding the content, make sure the strong / em tags are included if present.
			if ( $hasBoldFont ) {
				$xChar = $this->xDoc->createElement( 'strong' );
				$this->xCursor->appendChild ( $xChar );
				$this->xCursor = $xChar;
			}

			if ( $hasObliqueFont || $hasItalicFont ) {
				$xChar = $this->xDoc->createElement( 'em' );
				$this->xCursor->appendChild ( $xChar );
				$this->xCursor = $xChar;
			}
		}

		if( $includeContent ) {
			$this->convertContent( $icChar ); // Read Content from InCopy
		}
	}
	
	/**
	 * Converts a WCML table to XHTML. See convert() for more details.
	 * InCopy path: ../ParagraphStyleRange/CharacterStyleRange/Table
	 * @todo support table borders
	 */
	private function convertTable( DOMNode $icTable )
	{
		$xCursor = $this->xCursor; // Get the original cursor insertion point before insert table element

		// Add table to XHTML
		$xTable = $this->xDoc->createElement( 'table' );
		$this->xCursor->appendChild( $xTable );
		$xTable->setAttribute( 'cellpadding', 0 );
		$xTable->setAttribute( 'cellspacing', 0 );

		// Take over stylename to allow CSS to style tables differently
		$tableStyleName = $icTable->getAttribute('AppliedTableStyle');
		$tableStyleName = str_replace( 'TableStyle/', 'table_', $tableStyleName );
		$xTable->setAttribute( 'class', $this->cssEncode( $tableStyleName, false ) );

		// Read table from InCopy
		$icRows = $this->icXPath->query( 'Row', $icTable );
		$icCols = $this->icXPath->query( 'Column', $icTable );
		$icCells = $this->icXPath->query( 'Cell', $icTable );
		$icColumnCnt = $icTable->getAttribute('ColumnCount');
		$icHdrRowCnt = $icTable->getAttribute('HeaderRowCount');
		$icBdyRowCnt = $icTable->getAttribute('BodyRowCount');
		$icFtrRowCnt = $icTable->getAttribute('FooterRowCount');
		$icTotRowCnt = $icHdrRowCnt + $icBdyRowCnt + $icFtrRowCnt;
		$cell = 0;
		for( $row = 0; $row < $icTotRowCnt; $row++ ) {
		
			// Add table body to XHTML table
			$tRow = null;
			if( $row == 0 && $row < $icHdrRowCnt ) {
				$tRow = 'thead';
			} elseif( $row == $icHdrRowCnt && ($row < ($icHdrRowCnt + $icBdyRowCnt)) ) {
				$tRow = 'tbody';
			} elseif( $row == ($icHdrRowCnt + $icBdyRowCnt) ) {
				$tRow = 'tfoot';
			}
			if( $tRow ) {
				$xTbody = $this->xDoc->createElement( $tRow );
				$xTable->appendChild( $xTbody );
			}

			// Add table row to XHTML table body
			$xRow = $this->xDoc->createElement( 'tr' );
			$xTbody->appendChild( $xRow );

			for( $col = 0; $col < $icColumnCnt; $col++ ) {

				if( $cell < $icCells->length ) {
					// Get the cell Name attributes, which in "column:row" format that represent cell column and row value.
					// when found $colRows[1] > $row, means the cell should be on next row, 
					// therefore break the loop to process next row with the cell.
					// This trick is use to fix the cell RowSpan problem.
					$cellName = $icCells->item($cell)->getAttribute( 'Name' );
					$colRows = explode( ':', $cellName );
					if( $colRows[1] > $row ) {
						break;
					}
					
					// Add table cell to XHTML table row
					$xCell = $this->xDoc->createElement( 'td' );
					$xRow->appendChild( $xCell );
					if( $col < $icCols->length ) {
						$width = $icCols->item($col)->getAttribute( 'SingleColumnWidth' );
						if( $width ) {
							$xCell->setAttribute( 'width', intval($width).'px' );
						}
					}
					if( $row < $icRows->length ) {
						$height = $icRows->item($row)->getAttribute( 'SingleRowHeight' );
						if( $height ) {
							$xCell->setAttribute( 'height', intval($height).'px' );
						}
					}

					// Get the cell ColumnSpan and set HTML colSpan value
					$columnSpan =  $icCells->item($cell)->getAttribute( 'ColumnSpan' );
					if( $columnSpan > 1 ) {
						$xCell->setAttribute( 'colSpan', intval($columnSpan) );
					}
					// Get the cell RowSpan and set HTML rowSpan value
					$rowSpan = $icCells->item($cell)->getAttribute( 'RowSpan' );
					if( $rowSpan > 1 ) {
						$xCell->setAttribute( 'rowSpan', intval($rowSpan) );
					}

					// Take over stylename to allow CSS to style table cell differently
					$cellStyleName = $icCells->item($cell)->getAttribute('AppliedCellStyle');
					$cellStyleName = str_replace( 'CellStyle/', 'cell_', $cellStyleName );
					$xCell->setAttribute( 'class', $this->cssEncode( $cellStyleName, false ) );

					// Read ParagraphStyleRange from InCopy
					$icParas = $this->icXPath->query( 'ParagraphStyleRange', $icCells->item($cell) );
					foreach( $icParas as $icPara ) {
						$this->xCursor = $xCell; // insertion point
						$this->convertParagraphStyleRange( $icPara );
					}
				}
				$cell++;
			}
		}
		$this->xCursor = $xCursor; // Restore back the original cursor insertion point after finished inserting table
	}
	
	/**
	 * Converts a WCML text fragment to XHTML. See convert() for more details.
	 * InCopy path: ../ParagraphStyleRange/CharacterStyleRange/*
	 */
	protected function convertContent( DOMNode $icChar )
	{
		$icNodes = $this->icXPath->query( '*', $icChar ); // all child nodes (but no text)
		$xThisCursor = $this->xCursor; // insertion point (mostly a char-span)

		foreach( $icNodes as $key => $icNode ) {
			switch( $icNode->nodeName ) {
				case 'ParagraphStyleRange':
					// Read (styled) paragraph from InCopy
					// BZ#30990 - If there is ParagraphStyleRange node under <Change> node, compare first ParagraphStyleRange
					// node with the previous ParagraphStyleRange node before <Change> node.
					// If both of them having the same attribute, then converter won't create a new paragraph <p> node in XHTML.
					if( $this->icChangeParentNode ) { // Deep node under the <Change> node
						if( is_null($this->firstChangeParaNode) ) {
							$this->firstChangeParaNode = true; // Set first ParagraphStyleRange node to true
						}
						if( $this->firstChangeParaNode && $this->prevParaNode && $this->isSameNodeAttributes( $this->prevParaNode, $icNode ) ) {
							$this->convertContent( $icNode );
							$xThisCursor = $this->prevChangeCursor; // Restore the cursor, the cursor will create <p> under <div> node
							$this->firstChangeParaNode = false; // Set to false, don't process same logic for other ParagraphStyleRange node
						} else {
							$this->xCursor = $xThisCursor; // insertion point
							$this->convertParagraphStyleRange( $icNode );
						}
					} else {
						// 1st - Check if there is any preciding "Change" node of the current Node
						if( $this->isPrecedingChange( $icNode) ) {
							// 2nd - If Change node found, check if current node and previous node having the same ParagraphStyleRange attribute
							if( $this->prevParaNode && $this->isSameNodeAttributes( $this->prevParaNode, $icNode ) ) {
								// 3rd - If same ParagraphStyleRange found, restore the previous cursor, and continue to convert content
								$xThisCursor = $this->prevChangeCursor;
								$this->convertContent( $icNode );
							} else {
								// 3rd - If other ParagraphStyleRange found, don't restore to previous cursor,
								//       but continue to convert a new ParagraphStyleRange
								$this->xCursor = $xThisCursor; // insertion point
								$this->convertParagraphStyleRange( $icNode );
							}
						} else {
							$this->xCursor = $xThisCursor; // insertion point
							$this->convertParagraphStyleRange( $icNode );
						}
					}
					$this->prevParaNode = $icNode; // Set the previous ParagraphStyleRange node
				break;
				case 'CharacterStyleRange':
					// Read (styled) text fragment from InCopy
					if( $this->icChangeParentNode ) {
						if( $this->firstChangeParaNode && $this->prevCharNode && $this->isSameNodeAttributes( $this->prevCharNode, $icNode ) ) {
							$this->convertContent( $icNode );
						} else {
							$this->xCursor = $xThisCursor; // insertion point
							$this->convertCharacterStyleRange( $icNode );
						}
					} else {
						$this->xCursor = $xThisCursor; // insertion point
						$this->convertCharacterStyleRange( $icNode );
					}
					$xThisCursor = $this->xCursor;
					$this->prevCharNode = $icNode; // Set the previous CharacterStyleRange node
				break;
				case 'Content':
					// Read text from InCopy
					$icTexts = $this->icXPath->query( 'text()', $icNode );
					foreach( $icTexts as $icText ) {
						// Split the text when text contains soft line endings,
						// which need to convert to br element
						$icTexts2 = mb_split( IC_SOFT_EOL, $icText->nodeValue );
						foreach( $icTexts2 as $key => $icText2 ) {
							// Add text to XHTML
							$this->xCursor = $xThisCursor; // insertion point
							$xText = $this->xDoc->createTextNode( $icText2 );
							$this->xCursor->appendChild( $xText );
							if( $key < count($icTexts2) - 1 ) {
								$xBr = $this->xDoc->createElement( 'br' );
								$this->xCursor->appendChild( $xBr );
							}
						}
					}
				break;
				case 'Br':
					/* BZ#27115 - Do not create br element for the Hard line ending, as it is already belong to paragragh element, <p>
					// Add line break (br) to XHTML
					$this->xCursor = $xThisCursor; // insertion point
					$xBr = $this->xDoc->createElement( 'br' );
					$this->xCursor->appendChild( $xBr );
					*/

					// Create new para <p> to XHTML to make sure styles are re-applied to the next
					// line. E.g. this is needed for list bullets to show disc at start of each line.
					// We also check if the following sibbling of the current CharacterStyleRange
					// also is a CharacterStyleRange. If so we also create a new para item.
					if( $key < $icNodes->length -1 || $this->isNextCharacterStyleRange( $icChar ) ) { // don't start new para when br is last node
						$xPath = new DOMXPath( $this->xDoc );
						$xParas = $xPath->query( 'ancestor-or-self::' . $this->paragraphElementName, $this->xCursor );
						if( $xParas->length > 0 ) {
							$this->xCursor = $xParas->item( $xParas->length-1 )->parentNode; // insertion point, mostly a story (div) or list item (li)
							// BZ#25298/BZ#25469 $xParas->length-1 takes nearest ancestor (0=highest, n-1=deepest). See also above for related fixes.
						} else {
							// Should never happen... but let's continue since we have an xPara element so we can at least 
							// output all text so nothing gets lost, except that the text might be hang up under the wrong
							// parental element, and so could occur somewhere else in the document (very bad but not fatal).
							LogHandler::Log( 'textconv', 'ERROR', __METHOD__.': Could not find ancestor paragraph in path: '.$this->getNodePath($this->xCursor) );
						}
						// When current node/parent node=change, set the correct para node,
						// check whether parent node = CharacterStyleRange, if yes, set current node to parent node
						// then start converting from the para node, to get the para and char style,
						// else we lose out para and char style if further converting with "change" node
						$icPara = $icChar->parentNode;
						if( $icChar->nodeName == 'Change' || $icChar->parentNode->nodeName == 'Change' ) {
							$icPara = $icChar->parentNode->parentNode;
							if( $icChar->parentNode->nodeName == 'CharacterStyleRange' ) {
								$icChar = $icChar->parentNode;
							}
						}
						// EN-87342 - Do not continue to create character <span> element,
						// i) when it is a last <Br/> node with next node is CharacterStyleRange node,
						//    this is to ensure the next CharacterStyleRange node will create the its own character <span> element with FontStyle under <p> element.
						// ii)when the next node is <Br/>, this is to prevent create <span><strong><em> element on empty content, that messed up the cursor position.
						//
						//    When next node is not a <Br/> node, continue converting CharacterStyleRange, to ensure that character style with FontStyle will
						//    carried over to the next <Content> element.
						$nextNode = ($key < $icNodes->length ) ? $icNodes->item($key + 1) : null;
						if( ($nextNode && $nextNode->nodeName == 'Br') ) {
							$this->convertParagraphStyleRange( $icPara, $icChar, false, false );
						} elseif( $key == $icNodes->length -1 && !$this->icChangeParentNode ) {
							$this->convertParagraphStyleRange( $icPara, $icChar, true );
						} else {
							$this->convertParagraphStyleRange( $icPara, $icChar );
						}
						$xThisCursor = $this->xCursor; // insertion point
					} else {
						// For single Br element, create new para when there is following sibling of "Next" or "CharacterStyleRange" node
						if( $key == 0 && $this->icChangeParentNode && ($this->isNextChange( $icNode ) || $this->isNextCharacterStyleRange( $icChar) ) ) {
							$xPath = new DOMXPath( $this->xDoc );
							$xParas = $xPath->query( 'ancestor-or-self::' . $this->paragraphElementName, $this->xCursor );
							if( $xParas->length > 0 ) {
								$this->xCursor = $xParas->item( $xParas->length-1 )->parentNode;
							} else {
								LogHandler::Log( 'textconv', 'ERROR', __METHOD__.': Could not find ancestor paragraph in path: '.$this->getNodePath($this->xCursor) );
							}
							$icPara = $icChar->parentNode->parentNode;
							if( $icChar->parentNode->nodeName == 'CharacterStyleRange' ) {
								$icChar = $icChar->parentNode;
							}
							$this->convertParagraphStyleRange( $icPara, $icChar );
							$xThisCursor = $this->xCursor; // insertion point	
						}
					}
				break;
				case 'Table':
					$this->xCursor = $xThisCursor; // insertion point
					$this->convertTable( $icNode );
				break;
				case 'HyperlinkTextSource':
					// Search hyperlink definition at WCML
					$icURL = $this->lookupHyperlink( $icNode );
					if( $icURL ) {
						// Create anchor <a> and add it to XHTML
						$this->xCursor = $xThisCursor; // insertion point
						$xAnchor = $this->xDoc->createElement( 'a' );
						$xAnchor->setAttribute( 'href', $icURL );
						$openHyperlinkInSameWindow = $this->getOpenHyperlinkInSameWindow();
						if( $openHyperlinkInSameWindow ) {
							$xAnchor->setAttribute( 'target', '_self' );
						} else {
							$xAnchor->setAttribute( 'target', '_blank' ); // Old and Default behavior.
						}
						$this->xCursor->appendChild( $xAnchor );
						$this->xCursor = $xAnchor;
					} 
					// No matter the kind of hyperlink is known or not, continue processing 
					// to get out at least Content nodes that might be underneath.
					$this->convertContent( $icNode );
				break;
				case 'Footnote':
					// Remember footnote to process later (after story is completed)
					$icStories = $this->icXPath->query( 'ancestor::Story', $icNode );
					if( $icStories->length > 0 ) {
						$icStoryGuid = $icStories->item(0)->getAttribute('Guid');
						$footnodeId = count($this->icFootnotes) + 1;
						$this->icFootnotes[$icStoryGuid][$footnodeId] = $icNode;
						
						// Add footnote class to parent (span) to allow styling on that level,
						// which gives much more control than at child anchor (a); e.g. decreasing
						// the font size of the anchor would still have the parent span taking too 
						// much space and so there is no effect to the line spacing with lines above anchor.
						$this->xCursor = $xThisCursor; // insertion point
						$orgClass = $this->xCursor->getAttribute( 'class' );
						$this->xCursor->setAttribute( 'class', $orgClass.' footnote_ref' );

						// Create XHTML anchor to footnote (for which content will be added later)
						$xAnchor = $this->xDoc->createElement( 'a' );
						$xAnchor->setAttribute( 'href', '#footnote_'.$footnodeId );
						$this->xCursor->appendChild( $xAnchor );
						$xText = $this->xDoc->createTextNode( $footnodeId );
						$xAnchor->appendChild( $xText );
					}
				break;
				case 'Change': // tracked changes
					// Convert inserted and moved text from WCML to XHTML, but ignore deleted text.
					// In fact, the below does implicitly 'apply tracked changes' to the exported XHTML.
					$icChangeType = $icNode->getAttribute( 'ChangeType' );
					if( $icChangeType != 'DeletedText' ) { // 'InsertedText', 'MovedText'
						// Get the insertion point of change node, the insertion point might restore and used later
						$this->prevChangeCursor = $xThisCursor;
						$this->icChangeParentNode = true; // Set icChangeParentNode=true, indicating starting process node under <Change> node
						$this->convertContent( $icNode );
					} // else: DeletedText => leave out (do not convert to XHTML)
					$this->icChangeParentNode = null; // Set to null after finish processed nodes under <Change> node
					$this->firstChangeParaNode = null;
					$xThisCursor = $this->xCursor; // Set latest insertion point after finish prfocessed nodes under <Change> node
				break;
				case 'Rectangle':
					// If there is an (inline) image in the node, then determine the width and the height for this
					// image, this is done in the same way as CS handles the inline image dimensions.
					$this->setImageWidthAndHeight($icNode);

					// Process even unknown nodes to get out at least Content nodes that might be underneath.
					// Deep down in there we'll find the Link element.
					$this->xCursor = $xThisCursor; // insertion point
					$this->convertContent( $icNode );

					// Unset the image's dimensions since this image should be in the Rectangle element
					// and should only be used if the inline image is in the Link element (which is within the rectangle element)
					// which should have been processed in the above convertContent() call.
					unset( $this->imageWidth );
					unset( $this->imageHeight );

					break;
				case 'Link':
					if( $this->exportInlineImages && $icNode->parentNode->nodeName == 'Image' ) { // To make sure it is under the Image element
						// We can only export inline images when their LinkResourceURI points to a known image in the
						// database, therefore we need to doublecheck that we can use the LikResourceURI prior to continuing
						// to create the link.
						$icLinkID = $icNode->getAttribute( 'LinkResourceURI' );
						$icLinkPrefix = mb_substr($icLinkID, 0, 3);

						if ($icLinkPrefix == 'sc:') {
							$icLinkID = mb_substr( $icLinkID, 3 ); // skip 'sc:' prefix
							$xImgLink = $this->xDoc->createElement( 'img' );
							// Set the id and src attribute to the following pattern,
							// the value being set will be use by ww_enterprise Drupal module to replace the image src value,
							// after image has been uploaded into Drupal.
							// id = 'ent_xxx' where 'ent_' is prefix follow by 'xxx' is a object Id
							// src = 'ww_enterprise'
							$xImgLink->setAttribute( 'id', 'ent_'. $icLinkID ); // Id must be with prefix 'ent_'
							$xImgLink->setAttribute( 'src', 'ww_enterprise' ); // Temporary set to 'ww_enterprise' that will replace later

							// Set the dimension of the image retrieved from the rectangle element.
							if( isset( $this->imageWidth ) && isset( $this->imageHeight )) {
								$xImgLink->setAttribute( 'width', $this->imageWidth );
								$xImgLink->setAttribute( 'height', $this->imageHeight );
							}

							// Collect the inline images per frame.
							// Not unique collection, duplicates are saved as it is.
							$xFrame = $this->xFrames[$this->currentGuid];
							$xFrame->InlineImageIds[] = $icLinkID;
							// Collect the inline images for the whole story.
							// It's a unique collection, duplicates are saved one time.
							$this->uniqueInlineImageIds[$icLinkID] = true;

							$this->xCursor->appendChild( $xImgLink );
						} else {
							LogHandler::Log( 'textconv', 'WARN', 'Wcml2Xhtml->convertContent: Could not convert an  '
								. 'image link. The Link ID does not appear to be a valid Enterprise Object ID: '
								. $icLinkID);
						}
					}
				break;
				case 'Note': // user notes => leave out (do not convert to XHTML)
				break;
				default: // XMLElement or unknown
					// Process even unknown nodes to get out at least Content nodes that might be underneath
					$this->xCursor = $xThisCursor; // insertion point
					$this->convertContent( $icNode );
				break;
			}
		}
	}

	/**
	 * Determines the image Width and Height for an image inside a rectangle element.
	 *
	 * Sets the attributes $imageWidth and $imageHeight based on the data in the xml node.
	 *
	 * @todo Handle the blue / brown rectangle boxes correctly to match ID / IC behaviour.
	 *
	 * @param $icNode The Rectangle node to determine the image height / width for.
	 * @return void.
	 */
	private function setImageWidthAndHeight($icNode)
	{
		$query = 'Properties/PathGeometry/GeometryPathType/PathPointArray/PathPointType';
		$icPathPointTypes = $this->icXPath->query( $query, $icNode );
		unset($xMin); unset($xMax); unset($yMin); unset($yMax);
		foreach( $icPathPointTypes as $icPathPointType ) {
			$icLeftDirection = $icPathPointType->getAttribute( 'LeftDirection' );
			list( $x, $y ) = explode( ' ', $icLeftDirection );
			if( !isset( $xMin ) || $x < $xMin ) {
				$xMin = $x;
			}
			if( !isset( $xMax ) || $x > $xMax ) {
				$xMax = $x;
			}
			if( !isset( $yMin ) || $y < $yMin ) {
				$yMin = $y;
			}
			if( !isset( $yMax ) || $y > $yMax ) {
				$yMax = $y;
			}

			if( isset( $xMin ) && isset( $xMax ) ) {
				$width = $xMin - $xMax;

				// Make absolute
				if( $width < 0 ) {
					$width = floor( abs( $width ) );
				}
			}
			if( isset( $yMin ) && isset( $yMax ) ) {
				$height = $yMin - $yMax;

				// Make absolute
				if( $height < 0 ) {
					$height = floor( abs( $height ) );
				}
			}
		}

		// Set the width if available.
		if (isset($width)) {
			$this->imageWidth = $width;
		}

		// Set the height if available.
		if (isset($height)) {
			$this->imageHeight = $height;
		}
	}
	
	/**
	 * Returns a boolean whether or not the following sibling is also a CharacterStyleRange node.
	 * 
	 * @param DOMNode $icNode CharacterStyleRange node
	 * @return boolean
	 */
	private function isNextCharacterStyleRange( DOMNode $icNode )
	{
		// 1st - Get the self or ancestor CharacterStyleRange node, to prevent passing the wrong node
		// 2nd - Check if there is following sibling of CharacterStyleRange node
		$charNodes = $this->icXPath->query( 'ancestor-or-self::CharacterStyleRange', $icNode );
		if( $charNodes->length > 0 ) {
			$charNode = $charNodes->item(0);
			$nextCharNodes = $this->icXPath->query( 'following-sibling::CharacterStyleRange', $charNode );	

			return ( $nextCharNodes->length > 0 );
		}
		return false;
	}

	/**
	 * Returns a boolean whether or not the preceding sibling is a Change node with ChangeType != DeletedText.
	 * 
	 * @param DOMNode $icNode ParagraphStyleRange node
	 * @return boolean
	 */
	private function isPrecedingChange( DOMNode $icNode )
	{
		$precedingNodes = $this->icXPath->query( 'preceding-sibling::*[1]', $icNode ); // Get the last preciding sibling
		
		if( $precedingNodes->length > 0 ) {
			$precedingNode = $precedingNodes->item(0);
			if( $precedingNode->nodeName == 'Change' && $precedingNode->getAttribute( 'ChangeType' ) != 'DeletedText' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns a boolean whether or not the following sibling is a Change node.
	 * 
	 * @param DOMNode $icNode Change node
	 * @return boolean
	 */
	private function isNextChange( DOMNode $icNode )
	{
		// 1st - Get the ancestor Change node
		// 2nd - Check if there is following sibling of Change node
		$changeNodes = $this->icXPath->query( 'ancestor::Change[1]', $icNode );
		if( $changeNodes->length > 0 ) {
			$changeNode = $changeNodes->item(0);
			$nextChangeNodes = $this->icXPath->query( 'following-sibling::Change', $changeNode );	

			return ( $nextChangeNodes->length > 0 );
		}
		return false;
	}

	/**
	 * Returns the URL for a given HyperlinkTextSource node, by searching through WCML definitions.
	 *
	 * @param DOMNode $icNode HyperlinkTextSource node
	 * @return string URL when found, else NULL.
	 */
	private function lookupHyperlink( DOMNode $icNode )
	{
		$icURL = null;
		$icSelf = $icNode->getAttribute( 'Self' );
		$icLinks = $this->icXPath->query( '/ea:Stories/ea:Story/Document/Hyperlink[@Source="'.$icSelf.'"]' );
		if( $icLinks->length > 0 ) {
			$icLink = $icLinks->item(0);
			$icDests = $this->icXPath->query( 'Properties/Destination/text()', $icLink );
			if( $icDests->length > 0 ) {
				$icDest = $icDests->item(0);
				$icURLDests = $this->icXPath->query( '/ea:Stories/ea:Story/Document/HyperlinkURLDestination[@Self="'.$icDest->nodeValue.'"]' );
				if( $icURLDests->length > 0 ) {
					$icURLDest = $icURLDests->item(0);
					$icURL = $icURLDest->getAttribute( 'DestinationURL' );
				}
			}
		}
		return $icURL;
	}

	/**
	 * Parses WCML color definitions and converts them to XHTML ($this->xColors).
	 * It supports RGB, Labs and CMYK color system which are all converted to RGB.
	 */
	private function buildColors()
	{
		// Iterate through the color defintions at WCML and transform colors to RGB.
		// All found colors are collected at $this->xColors.
		$icColors = $this->icXPath->query( '/ea:Stories/ea:Story/Document/Color' );
		foreach( $icColors as $icColor ) {
			$self = $icColor->getAttribute('Self');
			$space = $icColor->getAttribute('Space');
			$colVals = explode( ' ', $icColor->getAttribute('ColorValue') );
			switch( $space ) {
				case 'LAB':
					$rgb = InCopyUtils::lab2rgb( $colVals );
					break;
				case 'CMYK':
					$colVals[0] = $colVals[0] / 100;
					$colVals[1] = $colVals[1] / 100;
					$colVals[2] = $colVals[2] / 100;
					$colVals[3] = $colVals[3] / 100;
					$rgb = InCopyUtils::cmyk2rgb( $colVals );
					break;
				case 'RGB':
					$rgb = $colVals;
					break;
				default:
					$rgb = array(0,0,0); // black
					break;
			}
			$this->xColors[$self] = '#'.InCopyUtils::rgb2hex( $rgb );
		}
		if( !isset($this->xColors['Color/Black']) ) {
			$this->xColors['Color/Black'] = '#000000';
		}
		
		// Iterate through all swatches and copy colors from definitions found above.
		// For each copy, the swatch id is used so that swatch color lookups will work.
		$icSwatches = $this->icXPath->query( '/ea:Stories/ea:Story/Document/Swatch' );
		foreach( $icSwatches as $icSwatch ) {
			$icCreatorId = $icSwatch->getAttribute('SwatchCreatorID');
			$icColors = $this->icXPath->query( '/ea:Stories/ea:Story/Document/Color[@SwatchCreatorID="'.$icCreatorId.'"]' );
			if( $icColors->length > 0 ) {
				$icColor = $icColors->item(0);
				$icColorId = $icColor->getAttribute('Self');
				$icSwatchId = $icSwatch->getAttribute('Self');
				$this->xColors[$icSwatchId] = $this->xColors[$icColorId]; // copy color
			}
		}
	}

	/**
	 * Reads para/char/override style information from given InCopy style node
	 * and builds a CSS style from that. 
	 *
	 * @param DOMNode $icStyle InCopy node being processed
	 * @param bool $isOverride TRUE for override style (becomes 'style' attribute) or FALSE for para/char style definition (referred through 'class' attribute).
	 * @param bool $isPara TRUE for para style or FALSE for char style.
	 * @param array $css Style map for internal use.
	 * @return string CSS definition
	 */
	protected function parseStyles( DOMNode $icStyle, $isOverride, $isPara, $css = array() )
	{
		// Use single quotes (') or double quotes (") around string values. 
		// Single for class attributes and double for CSS defines.
		$quote = $isOverride ? "'" : '"';
		
		// Inherit styles (using recursion)
		$icBasedOns = $this->icXPath->query( 'Properties/BasedOn/text()', $icStyle );
		foreach( $icBasedOns as $icBasedOn ) { // typically one only
			$icStyleGroup = $icStyle->parentNode; // climb up to RootParagraphStyleGroup or RootCharacterStyleGroup
			if( $isPara ) {
				$icStyles = $this->icXPath->query( 'ParagraphStyle[@Self="'.$icBasedOn->nodeValue.'"]', $icStyleGroup );
				if( $icStyles && $icStyles->length > 0 ) {
					$css = $this->parseStyles( $icStyles->item(0), $isOverride, $isPara, $css );
				}
			} else { // char style
				$icStyles = $this->icXPath->query( 'CharacterStyle[@Self="'.$icBasedOn->nodeValue.'"]', $icStyleGroup );
				if( $icStyles && $icStyles->length > 0 ) {
					$css = $this->parseStyles( $icStyles->item(0), $isOverride, $isPara, $css );
				}
			}
		}

		// Parse styles at this level (overruling inherited)

		$val = $icStyle->getAttribute('CharacterDirection'); // enum
		if( !$val ) {
			$val = $icStyle->getAttribute('ParagraphDirection'); // enum
		}
		if( $val ) {
			switch( $val ) {
				case 'LeftToRightDirection':
					$css['direction'] = 'ltr';
				break;
				case 'RightToLeftDirection':
					$css['direction'] = 'rtl';
				break;
			}
		}

		$val = $icStyle->getAttribute('Position'); // enum
		if( $val ) {
			switch( $val ) {
				case 'Normal':
					$css['vertical-align'] = 'baseline';
				break;
				case 'Superscript':
				case 'OTSuperscript': // OpenType fonts
					$css['vertical-align'] = 'super';
				break;
				case 'Subscript':
				case 'OTSubscript': // OpenType fonts
					$css['vertical-align'] = 'sub';
				break;
				case 'OTNumerator': // OpenType fonts
					$css['vertical-align'] = 'text-top';
				break;
				case 'OTDenominator': // OpenType fonts
					$css['vertical-align'] = 'text-bottom';
				break;
			}
		}
		
		$val = $icStyle->getAttribute('FontStyle'); // string
		if( $val ) {
			$vals = explode( ' ', $val ); // example: "Bold Italic"
			if( in_array( 'Italic', $vals ) ) {
				$css['font-style'] = 'italic';
			}
			if( in_array( 'Bold', $vals ) ) {
				$css['font-weight'] = 'bold';
			}
			if( in_array( 'Semibold', $vals ) ) {
				$css['font-weight'] = 'semibold';
			}
		}
		$val = $icStyle->getAttribute('PointSize'); // double
		if( $val ) {
			$css['font-size'] = $val.'pt';
		}
		$val = $this->icXPath->query( 'Properties/AppliedFont/text()', $icStyle ); // string
		if( $val && $val->length > 0 ) {
			$val = $val->item(0)->nodeValue;
			if( $val ) {
				$css['font-family'] = $quote.$val.$quote;
			}
		}

		/* // TBD: Whether or not to support text indents
		$val = $icStyle->getAttribute('FirstLineIndent'); // double
		if( $val ) {
			$css['text-indent'] = $val.'em';
		}
		$val = $icStyle->getAttribute('LeftIndent'); // double
		if( $val ) {
			$css['padding-left'] = $val.'em';
		}
		$val = $icStyle->getAttribute('RightIndent'); // double
		if( $val ) {
			$css['padding-right'] = $val.'em';
		}
		$val = $icStyle->getAttribute('SpaceBefore'); // double
		if( $val ) {
			$css['padding-top'] = $val.'em';
		}
		$val = $icStyle->getAttribute('SpaceAfter'); // double
		if( $val ) {
			$css['padding-bottom'] = $val.'em';
		}*/

		$val = $icStyle->getAttribute('Capitalization'); // enum
		if( $val ) {
			switch( $val ) {
				case 'Normal': // no change
					$css['text-transform'] = 'none';
				break;
				case 'CapToSmallCap': // lowercase (OpenType)
				case 'SmallCaps': // lowercase
					$css['text-transform'] = 'lowercase';
				break;
				case 'AllCaps': // uppercase
					$css['text-transform'] = 'uppercase';
				break;
			}
		}
		
		$val = $icStyle->getAttribute('Underline'); // boolean
		if( $val && $val == 'true' ) {
			$css['text-decoration'] = 'underline';
		}
		$val = $icStyle->getAttribute('StrikeThru'); // boolean
		if( $val && $val == 'true' ) {
			$css['text-decoration'] = 'line-through';
		}
		
		/* Commented out since the No-Break style can not be done here since that does not give 
		   the required behavior.  Instead, this is done with nobr elements by the 
		   convertParagraphStyleRange() function. See overthere for more information.
		$val = $icStyle->getAttribute('NoBreak'); // boolean
		if( $val ) {
			if( $val == 'true' ) {
				$css['white-space'] = 'no-wrap';
			} else { // false
				$css['white-space'] = 'normal';
			}
		}*/
	
		$val = $icStyle->getAttribute('FillColor'); // string
		if( $val ) {
			if( isset($this->xColors[$val]) ) {
				$css['color'] = $this->xColors[$val];
			}
		}
		$val = $icStyle->getAttribute('StrokeColor'); // string
		if( $val && $val != 'Swatch/None' ) {
			if( isset($this->xColors[$val]) ) {
				$css['background-color'] = $this->xColors[$val];
			}
		}

		$dcChars = $icStyle->getAttribute('DropCapCharacters'); // int
		$dcLines = $icStyle->getAttribute('DropCapLines'); // int
		if( $dcChars || $dcLines ) { // limitation: max 1 char supported
			$val = max( $dcChars, $dcLines ) * 125; // rougly: 100% plus some line height (+/-25%) or else 3 could fit into 2 lines
			$css['__specials__']['first-letter']['font-size'] = $val.'%'; 
			$css['__specials__']['first-letter']['float'] = 'left';
		}

		$val = $icStyle->getAttribute('NumberingLevel'); // int
		if( $val ) {
			$css['__counter__']['level'] = $val;
		}
		$val = $icStyle->getAttribute('BulletsAndNumberingListType'); // enum
		if( $val ) {
			switch( $val ) {
				case 'NoList':
					$css['__counter__']['list-type'] = '';
				break;
				case 'NumberedList':
					$css['__counter__']['list-type'] = 'ol';
				break;
				default: // default bullets (for future systems)
				case 'BulletList':
					$css['__counter__']['list-type'] = 'ul';
				break;
			}
		}
		/*$val = $icStyle->getAttribute('BulletsTextAfter'); // string
		if( $val ) {
			$val = str_replace( '^t', '   ', $val ); // TODO: how to deal with control chars?
			$css['__specials__']['after']['content'] = $quote.$val.$quote;
		}*/
		$val = $this->icXPath->query( 'Properties/NumberingFormat/text()', $icStyle ); // enum
		if( $val && $val->length > 0 ) {
			$val = $val->item(0)->nodeValue;
			if( $val ) {
				switch( $val ) {
					// unsupported: 'Kanjicase' 'KatakanaModerncase' 'KatakanaTraditionalcase' 'ArabicAlifBaTahcase' 'ArabicAbjadcase' 'HebrewBiblicalcase' 'HebrewNonStandardcase' 
					case 'A, B, C, D...'     : /*$xNumStyle = 'upper-alpha';*/ $xNumType = 'A'; break;
					case 'a, b, c, d...'     : /*$xNumStyle = 'lower-alpha';*/ $xNumType = 'a'; break;
					case 'I, II, III, IV...' : /*$xNumStyle = 'upper-roman';*/ $xNumType = 'I'; break;
					case 'i, ii, iii, iv...' : /*$xNumStyle = 'lower-roman';*/ $xNumType = 'i'; break;
					case '1, 2, 3, 4...'     : // fall through ...
					default                  : /*$xNumStyle = 'decimal';    */ $xNumType = 'd'; break;
				}
				$css['__counter__']['num-type'] = $xNumType;
			}
		}
		$val = $icStyle->getAttribute('NumberingContinue'); // boolean
		if( $val && $val == 'false' ) {
			$css['__counter__']['start'] = 1;
		}
		$val = $icStyle->getAttribute('NumberingStartAt'); // int
		if( $val ) {
			$css['__counter__']['start'] = $val;
		}

		return $css;
	}

	/**
	 * Parses WCML para/char style definitions and builds $this->xStyles (for XHTML).
	 *
	 * @return string
	 */
	private function buildStyles()
	{
		$this->xStyles = array();

		// Paragraph style
		// BZ#27109: Need to retrieve styles from "ea:wwsd_document/ea:wwsd_styles/Document/RootPara..." and "ea:Story/Document/RootPara..."
		$icParaStyles = $this->icXPath->query( '/ea:Stories/ea:wwsd_document/ea:wwsd_styles/Document/RootParagraphStyleGroup/ParagraphStyle' ); 
		foreach( $icParaStyles as $icParaStyle ) {
			$self = $icParaStyle->getAttribute('Self');
			$self = str_replace( 'ParagraphStyle/', 'para_', $self );
			$xStyleMap = $this->parseStyles( $icParaStyle, false, true ); // style def, para
			$this->xStyles[$self] = $this->postProcessStyles( $xStyleMap, $icParaStyle, null, false, true, $self );
		}
		
		
		$icParaStyles = $this->icXPath->query( '/ea:Stories/ea:Story/Document/RootParagraphStyleGroup/ParagraphStyle' );
		foreach( $icParaStyles as $icParaStyle ) {
			$self = $icParaStyle->getAttribute('Self');
			$self = str_replace( 'ParagraphStyle/', 'para_', $self );
			$xStyleMap = $this->parseStyles( $icParaStyle, false, true ); // style def, para
			$this->xStyles[$self] = $this->postProcessStyles( $xStyleMap, $icParaStyle, null, false, true, $self );
		}
				
		// Character style
		// BZ#27109: Need to retrieve styles from "ea:wwsd_document/ea:wwsd_styles/Document/RootPara..." and "ea:Story/Document/RootPara..."
		$icCharStyles = $this->icXPath->query( '/ea:Stories/ea:wwsd_document/ea:wwsd_styles/Document/RootCharacterStyleGroup/CharacterStyle' );
		foreach( $icCharStyles as $icCharStyle ) {
			$self = $icCharStyle->getAttribute('Self');
			$self = str_replace( 'CharacterStyle/', 'char_', $self );
			$xStyleMap = $this->parseStyles( $icCharStyle, false, false ); // style def, char
			$this->xStyles[$self] = $this->postProcessStyles( $xStyleMap, $icCharStyle, null, false, false, $self );
		}
		
		$icCharStyles = $this->icXPath->query( '/ea:Stories/ea:Story/Document/RootCharacterStyleGroup/CharacterStyle' );
		foreach( $icCharStyles as $icCharStyle ) {
			$self = $icCharStyle->getAttribute('Self');
			$self = str_replace( 'CharacterStyle/', 'char_', $self );
			$xStyleMap = $this->parseStyles( $icCharStyle, false, false ); // style def, char
			$this->xStyles[$self] = $this->postProcessStyles( $xStyleMap, $icCharStyle, null, false, false, $self );
		}
	}

	/**
	 * Parses WCML para/char style definitions and builds a full CSS definition for XHTML.
	 *
	 * @return string
	 */
	private function buildCSS()
	{
		//$counters = array();
		$css = PHP_EOL;

		foreach( $this->xStyles as $name => $xStyleMap ) {
			if( count($xStyleMap) > 0 ) {
				$css .= '.' . $this->cssEncode( $name, true ) . ' {' . PHP_EOL;
				foreach( $xStyleMap as $key => $val ) {
					if( $key != '__specials__' && $key != '__counter__' ) {
						$css .= "\t" . $key . ': ' . $val . ';' . PHP_EOL;
					}
				}
				$css .= '}' . PHP_EOL;
			}

			/*if( isset($xStyleMap['__counter__']) ) {
				$counter = $xStyleMap['__counter__'];
				$cntLevel = isset($counter['level']) ? $counter['level'] : 1;
				$numStyle = isset($counter['num-style']) ? $counter['num-style'] : 'decimal';
				$numType  = isset($counter['num-type']) ? $counter['num-type'] : 'd';
				$cntName = 'ww_para_counter_lev'.$cntLevel.'_'.$numType;
				$xStyleMap['__specials__']['before']['content'] = "counter($cntName, $numStyle) \" \""; // space after counter
				$xStyleMap['__specials__']['before']['counter-increment'] = $cntName;
				$counters[$cntName] = true;
			}*/

			if( isset($xStyleMap['__specials__']) ) {
				foreach( array_keys($xStyleMap['__specials__']) as $special ) { // before, after, first-letter, etc
					if( count($xStyleMap['__specials__'][$special]) > 0 ) {
						$css .= '.' . $this->cssEncode( $name, true ) . ':'.$special.' {' . PHP_EOL;
						foreach( $xStyleMap['__specials__'][$special] as $key => $val ) {
							$css .= "\t" . $key . ': ' . $val . ';' . PHP_EOL;
						}
						$css .= '}' . PHP_EOL;
					}
				}
			}
		}

		// Reset counters for para numbering / numbered lists (at body level)
		/*$css .= "\t.body { ";
		foreach( array_keys($counters) as $cntName ) {
			$css .= 'counter-reset: ' . $cntName . '; ';
		}
		$css .= '}' . PHP_EOL;*/
		return $css;
	}

	/**
	 * In CSS, identifiers (including element names, classes, and IDs in selectors) can contain only 
	 * the characters [a-z0-9] and ISO 10646 characters U+00A1 and higher, plus the hyphen (-) and 
	 * the underscore (_); they cannot start with a digit, or a hyphen followed by a digit. 
	 * Identifiers can also contain escaped characters and any ISO 10646 character as a numeric code. 
	 * For instance, the identifier "B&W?" may be written as "B\&W\?" or "B\26 W\3F".
	 *
	 * For HTML attributes (id/name/class), there is no escaping applied, but for CSS defs it is.
	 * For example, this HTML class:
	 *    <p class="yes\no?"> 
	 * refers to this CSS definition:
	 *    .yes\\no\? { color:red; }
	 *
	 * CSS2 grammar:
	 * 		ident       {nmstart}{nmchar}*
	 * 		nmstart     [a-zA-Z]|{nonascii}|{escape}
	 * 		nmchar      [a-z0-9-]|{nonascii}|{escape}
	 * 		nonascii    [^\0-\177]
	 * 		unicode     \\[0-9a-f]{1,6}[ \n\r\t\f]?
	 *	 
	 * Note that spaces at the "class" attribute of HTML elements are used to separate classes.
	 * Therefor, this function replaces spaces with underscores.
	 * 
	 * @param string $name Identifier or classname in UTF-8 format to encode for CSS
	 * @param bool $escape TRUE for CSS definitions, or FALSE for HTML id/name/class attributes
	 * @return string XHTML/CSS compatible indentifier
	 */
	public function cssEncode( $name, $escape )
	{
		// Return previously encoded names from cache
		static $map = array();
		if( isset($map[$name][$escape]) ) {
			return $map[$name][$escape];
		}
		
		// Iterate through chars and escape/copy one-by-one
		$css = ''; // CSS safe name in UTF-8 format to return caller
		$buf = iconv( 'UTF-8', 'UTF-16BE', $name ); // temp convert to UTF-16 to be able to check index ranges below
		$len = mb_strlen( $buf, 'UTF-16BE'); // determine lenght outside loop for speed
		$checkPrefix = true; // as long as it starts with hyphens (-), keep checking prefix
		$needPrefix = false;
		for( $i = 0; $i < $len; $i++ ) {
			
			// Take 2-byte hex value from char in UTF-16 (Big Endian) format
			$char = mb_substr( $buf, $i, 1, 'UTF-16BE' );
			$char = (ord($char{0}) << 8) + ord($char{1});
			
			// Check if we need repair (when start with a digit, or a hyphen followed by a digit)
			if( $checkPrefix ) {
				if( $char != 0x002D ) { // hyphen (-)
					$checkPrefix = false;
				}
				if( $char >= 0x0030 && $char <= 0x0039 ) { // [0-9]
					$needPrefix = true;
				}
			}
			
			// Copy or escape the char
			if( $char == 0x002D || // hyphen (-)
				$char == 0x005F || // underscore (_)
				($char >= 0x0041 && $char <= 0x005A) || // [A-Z]
				($char >= 0x0061 && $char <= 0x007A) || // [a-z]
				($char >= 0x0030 && $char <= 0x0039) || // [0-9]
				$char >= 0x00A1 ) { // ISO 10646 characters U+00A1 and higher
				$css .= mb_substr( $name, $i, 1, 'UTF-8' ); // just copy the char
			} else if( $char == 0x0020 ) { // space
				$css .= '_'; // replace spaces with underscores
			} else if( $char > 0x001F) { // paranoid: exclude all before the 'space' char (tabs, eols, etc)
				if( $escape ) {
					$css .= '\\'.mb_substr( $name, $i, 1, 'UTF-8' ); // escape the char
				} else {
					$css .= mb_substr( $name, $i, 1, 'UTF-8' ); // just copy the char
				}
			}
		}
		
		// Repair by prefix (when starts with digit, or hypen followed by a digit)
		if( $needPrefix ) {
			$css = '_'.$css;
		}
		
		// Cache and return escaped string to caller
		$map[$name][$escape] = $css;
		return $css;
	}

	/**
	 * Returns the full XML/DOM path for a given XML node. This used for heavy debuging only.
	 * When anything goes wrong during document conversion, you can pass in any XML node (WCML input
	 * or XHTML output) to see which node is processed at a certain point in the source code.
	 *
	 * @param DOMNode $node
	 * @return string Full DOM/XML path
	 */
	private function getNodePath( DOMNode $node )
	{
		$names = array();
		while( $node ) {
			$names[] = $node->nodeName;
			$node = $node->parentNode;
		}
		return implode( '/', array_reverse( $names ) );
	}

	/**
	 * Set the $exportInlineImages to true
	 * See TextImport class for more info.
	 */
	public function enableInlineImageProcessing()
	{
		$this->exportInlineImages = true;
		return true;
	}

	/**
	 * See TextImport class for more info.
	 * @return array|null
	 */
	public function getInlineImages()
	{
		return array_keys( $this->uniqueInlineImageIds );
	}

	/**
	 * Check if both of the Dom node having the same attributes
	 *
	 * @param DOMNode $firstNode
	 * @param DOMNode $secondNode
	 * @return True|False Return true when attribute found to be the same between 2 nodes
	 */
	private function isSameNodeAttributes( DOMNode $firstNode, DOMNode $secondNode )
	{
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		
		$phpCompare->initCompare( array() );			
		
		if( !$phpCompare->compareTwoObjects( $firstNode, $secondNode ) ){
			return false;
		}
		return true;
	}
}
