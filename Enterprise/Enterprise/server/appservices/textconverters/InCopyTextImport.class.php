<?php
/**
 * TextImport class for InCopy (XML) text format used by the Web Editor.
 *
 * Converts from InCopy (XML) text format to collection of XHTML frames that can be edit in TinMCE frames.
 * See {@link TextImport} interface for more details.
 *
 * @package Enterprise
 * @subpackage WebEditor
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/TextImportExport.intf.php'; // TextImport
require_once dirname(__FILE__).'/InCopyTextUtils.php';

class InCopyTextImport implements TextImport
{
	private $hyperLinks = null;
	
	private $docDomVersion = null; // BZ#15879 - Store the InCopy document version

	private $icUsers = array(); // list of InCopy users that have edit trail in IC (XML) article
	// Inventory of all handled InCopy attributes and their values used for free format editing.
	// Those are converted to HTML elements/attributes and so they can be converted back.
	// The InCopy features that can NOT be converted to HTML are collected at the "id" attribute
	// to be able to convert them back once the article gets converted back (e.g. saved from Web Editor).
	// See collectTxsrAttrs function for more details.
	private static $handledTxsrAttributes = array( 
		// bold/italic elements:
		'ptfs' => array( 'c_Bold', 'c_Bold Italic', 'c_Italic', 'c_Bold Italic' ), 
		// strike-through element:
		'strk' => array(), // ALL values handled
		// underline element:
		'undr' => array(), // ALL values handled
		// subscript/superscript element:
		'posm' => array( 'e_sbsc', 'e_spsc' ),
		// bullets and numbering:
		'bnlt' => array( 'e_LTnm', 'e_LTbt' ),
		// font face/size:
		'font' => array(), // ALL values handled
		'ptsz' => array(), // ALL values handled
		// font text/back color:
		'flcl' => array(), // ALL values handled
		'lncl' => array(), // ALL values handled
		// para/char styles:
		'prst' => array(), // ALL values handled
		'crst' => array()  // ALL values handled
	);
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->hyperLinks = new InCopyHyperlinkStack();
	}

	/**
	 * Convert a file from InCopy (XML) text format to collection of XHTML frames.
	 *
	 * @param string $icFile  Full path of file to be read.
	 * @param array  $xFrames   (out) Returned collection of XHTML DOMDocument each representing one text frame.
	 * @param string $stylesCSS (out) Cascade para+char style definition.
	 * @param string $stylesMap (out) Para+char styles; GUI display name mapped onto CSS name.
	 * @param integer $domVersion (out) Major version number of the document. Zero when undetermined. CS1=3, CS2=4, CS3=5, CS4=6, etc
	 */
	public function importFile( $icFile, &$xFrames, &$stylesCSS, &$stylesMap, &$domVersion )
	{
		LogHandler::Log( 'textconv', 'INFO', 'InCopyFileExport->import(): Reading InCopy Text from ['.$icFile.']');
		$icDoc = new DOMDocument();
		$icDoc->loadXML( file_get_contents( $icFile ) ); // URL encoded paths fail: $icDoc->load( $icFile ); (BZ#6561)

		// When IC article template given, convert it into normal IC article
		if( $icDoc->documentElement->nodeName == 'wwst_template' ) {
			$icDoc = $this->importTemplate( $icDoc );
			if( !$icDoc ) { // conversion failed
				LogHandler::Log( 'textconv', 'ERROR', 'InCopyFileExport->import(): Failed to import IC template ['.$icFile.']');
				return;
			}
			$icDoc->save( urlencode($icFile) ); // write as normal doc
		}

		LogHandler::Log( 'textconv', 'INFO', 'InCopyFileExport->import(): Convert InCopy to HTML for ['.$icFile.']');
		$this->importBuf( $icDoc, $xFrames, $stylesCSS, $stylesMap, $domVersion );
	}

	/**
	 * Convert an InCopy template into InCopy document. It creates a new IC document based on the given IC template.
	 * The created document contains all typed content, styles, tags, stories and color definitions (swatches).
	 * The GUIDs of of the stories are updated to make them unique.
	 *
	 * @param DOMDocument $icTempl  InCopy template document to be converted.
	 * @return DOMDocument  The newly created InCopy document.
	 */
	public function importTemplate( $icTempl )
	{
		$xpath = new DOMXPath( $icTempl );
		$query = '//wwst_template/wwst_story_contents';
		$entries = $xpath->query( $query );
		if( $entries->length == 0 ) { return null; }
		$icTemplStories = $entries->item(0);

		// Templates have story contents embedded, especially done for the Web Editor to create new articles.
		// In the following steps we create a new article using the given template.

		// Step 1: Copy Template->wwst_template/wwst_story_contents to Document->Stories
		//         Note that element names can NOT be set in PHP5 'cauz setName and setNodeName are not implemented!
		//         So we can NOT rename "wwst_story_contents" into "Stories" at target document.
		//         To work around this, we set the name by creating new node, and so we need to copy all its attributes.
		$icDoc = new DOMDocument();
		$icDocStories = $icDoc->createElement( 'Stories' );
		$icDoc->appendChild( $icDocStories ); // set root element
		if( $icTemplStories->hasAttributes() ) for( $a = 0; $a < $icTemplStories->attributes->length; $a++ ) {
			$icTemplStoriesAtt = $icTemplStories->attributes->item( $a );
			$icDocStories->setAttribute( $icTemplStoriesAtt->name, $icTemplStoriesAtt->value );
		}

		// Step 2: Deep-Copy each Template->wwst_template/wwst_story_contents/wwst_story to Document->Stories/Story.
		//         Note that element names can NOT be set in PHP5 'cauz setName and setNodeName are not implemented!
		//         So we can NOT rename "wwst_story" into "Story" at target document.
		//         To work around this, we set the name by creating new node, and so we need to copy all its attributes,
		//         and we deep-copy all its children (to take over all typed content).
		if( $icTemplStories->hasChildNodes() ) for( $i = 0; $i < $icTemplStories->childNodes->length; $i++ ) {
			$icTemplStory = $icTemplStories->childNodes->item( $i );
			if( $icTemplStory->nodeName == 'wwst_story' ) {
				$icDocStory = $icDoc->createElement( 'Story' );
				$icDocStories->appendChild( $icDocStory );
				if( $icTemplStory->hasAttributes() ) for( $j = 0; $j < $icTemplStory->attributes->length; $j++ ) {
					$icTemplStoryAtt = $icTemplStory->attributes->item( $j );
					$icDocStory->setAttribute( $icTemplStoryAtt->name, $icTemplStoryAtt->value );
				}
				if( $icTemplStory->hasChildNodes() ) for( $k = 0; $k < $icTemplStory->childNodes->length; $k++ ) {
					$icTemplStoryChild = $icTemplStory->childNodes->item( $k );
					$icDocStoryChild = $icDoc->importNode( $icTemplStoryChild, true ); // deep copy
					$icDocStory->appendChild( $icDocStoryChild );
				}
			}
		}

		// Step 3: Deep-Copy each Template->wwst_template (root) to Document->Stories/wwst_template.
		//         except wwst_story_contents which is already treated above. So we remove it after the copy.
		$icDocTemplate = $icDoc->importNode( $icTempl->documentElement, true );
		$icDocStories->appendChild( $icDocTemplate );
		$xpath = new DOMXPath( $icDoc );
		$query = '//Stories/wwst_template/wwst_story_contents';
		$entries = $xpath->query( $query );
		if( $entries->length > 0 ) {
			$entries->item(0)->parentNode->removeChild( $entries->item(0) ); // remove wwst_story_contents (copied too much)
		}

		// Step 4: Update the story GUIDs to make the new story unique:
		//         - Stories/Story->GUID
		//         - Stories/Story/SnippetRoot/cflo->GUID
		//         - Stories/wwst_template/wwst_stories/SnippetRoot/cflo->GUID
		// Note: Do not confuse story GUIDs with version GUIDs (which are updated during export/save !)
		$newGuids = array();
		$xpath = new DOMXPath( $icDoc );
		$query = '//Stories/Story';
		$entries = $xpath->query($query);
		foreach( $entries as $entry ) {
			$oldGUID = $entry->getAttribute( 'GUID' );
			if( $oldGUID ) {
				$newGuids[$oldGUID] = InCopyUtils::createGUID();
				$entry->setAttribute( 'GUID', $newGuids[$oldGUID] );
			}
		}
		$xpath = new DOMXPath( $icDoc );
		$query = '//Stories/Story/SnippetRoot/cflo';
		$entries = $xpath->query($query);
		foreach( $entries as $entry ) {
			$oldGUID = $entry->getAttribute( 'GUID' );
			if( $oldGUID ) {
				$oldGUID = substr( $oldGUID, 2 ); // skip "k_"
				$entry->setAttribute( 'GUID', 'k_'.$newGuids[$oldGUID] );
			}
		}
		$xpath = new DOMXPath( $icDoc );
		$query = '//Stories/wwst_template/wwst_stories/SnippetRoot/cflo';
		$entries = $xpath->query($query);
		foreach( $entries as $entry ) {
			$oldGUID = $entry->getAttribute( 'GUID' );
			if( $oldGUID ) {
				$oldGUID = substr( $oldGUID, 2 ); // skip "k_"
				$entry->setAttribute( 'GUID', 'k_'.$newGuids[$oldGUID] );
			}
		}

		return $icDoc; // the newly create IC document
	}

	/**
	 * Convert a memory buffer from InCopy (XML) text format to collection of XHTML frames.
	 *
	 * @param DOMDocument $icDoc  InCopy document to be parsed.
	 * @param array $xFrames    (out) Returned collection of XHTML DOMDocument each representing one text frame.
	 * @param string $stylesCSS (out) Cascade para+char style definition.
	 * @param string $stylesMap (out) Para+char styles; GUI display name mapped onto CSS name.
	 * @param integer $domVersion (out) Major version number of the document. Zero when undetermined. CS1=3, CS2=4, CS3=5, CS4=6, etc
	 */
	public function importBuf( $icDoc, &$xFrames, &$stylesCSS, &$stylesMap, &$domVersion )
	{
		// Handle stories
		$xpath = new DOMXPath( $icDoc );
		$query = '//Stories/Story'; // Walk through document's stories
		$entries = $xpath->query( $query );
		$domVersion = '0';
		foreach( $entries as $icStory ) {

			// Detect the internal document model version
			$pi0 = $icStory->getAttribute('pi0');
			$regs = array();
			preg_match('/DOMVersion="([0-9]+\.[0-9]+)"/i', $pi0, $regs ); 
			if( count($regs) > 0 ) {
				$domVersion = ($regs[1]);
				$this->docDomVersion = $domVersion; // BZ#15879 - Set the domversion for later usage
			}

			$storykind = $icStory->getAttribute('pi1'); // SnippetType="PageItem"
			if ( substr_count($storykind,"PageItem") ) {
				$xFrame = $this->handleGraphicElem( $icStory );
				if( $xFrame ) {
					$xFrames[] = $xFrame;
				}
			} else { // SnippetType="InCopyInterchange"
				// Cache defined users
				$xpath = new DOMXPath( $icDoc );
				$query = 'SnippetRoot/DcUs'; // Walk through defined InCopy users
				$entries = $xpath->query( $query, $icStory );
				foreach( $entries as $icUser ) {
					$userId = $icUser->getAttribute('Self');
					$userId = mb_substr( $userId, 3 ); // skip 'rc_' prefix
					$userName = $icUser->getAttribute('UsrN');
					$userName = mb_substr( $userName, 2 ); // skip 'c_' prefix
					$userColor = $icUser->getAttribute('UsrC');
					$userColor = mb_substr( $userColor, 2 ); // skip 'e_' prefix
					$this->icUsers[$userId] = new InCopyUser( $userId, $userName, $userColor );
				}

				// Create HTML document with empty body
				$xDoc = new DOMDocument('1.0');
				$xRoot = $xDoc->createElement( 'html' );
				$xDoc->appendChild( $xRoot );
				$xBody = $xDoc->createElement( 'body' );
				$xRoot->appendChild( $xBody );

				// Handle para/char styles
				$this->handleStyleElems( $icDoc, $icStory, $xDoc, $xBody, $stylesCSS, $stylesMap );

				// Handle story and build Frame object that holds ID, label and content (XHTML)
				$xFrame = $this->handleStoryElem( $icDoc, $icStory, $xDoc, $xBody );
				if( $xFrame ) {
					$xFrames[] = $xFrame;
				}
			}
		}
	}

	/**
	 * Parse the InCopy Story element and returns an xframe with GUID
	 * InCopy path: Stories/Story/GUID
	 *
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @return string     GUID of graphic
	 */
	private function handleGraphicElem( $icStory )
	{
		// return story's GUID, without label / content
		$xFrame = new stdClass();
		$guid = $icStory->getAttribute('GUID');
		$xFrame->ID = $guid;
		$xFrame->Label = null;
		$xFrame->Document = null;
		$xFrame->Content = null;
		return $xFrame;
	}

	/**
	 * Parse the InCopy Story element, convert it into a XHTML and add it to the body element.
	 * InCopy path: Stories/Story/SnippetRoot/cflo/txsr
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xBody (in/out) XHTML body element.
	 * @return string     GUID of story
	 */
	private function handleStoryElem( $icDoc, $icStory, &$xDoc, &$xBody )
	{
		$xpath = new DOMXPath( $icDoc );
		$query = 'SnippetRoot/cflo/txsr'; // Walk through story's paragraphs
		$entries = $xpath->query( $query, $icStory );
		$xPara = null; $xSpan = null; $xList = null;
		$prevPrst = null;
		$prevBnlt = null;
		$icRestart = null;
		$sameRestart = null;
		$prevRestart = null;
		$emptyList = true;
		$prevEndsWithEOL = false; // Tells if previous txsr element's text has hard enter as last char
		if( $entries->length == 0 ) {

			// Avoid empty bodies... make sure the default styles are set or else
			// user starts typing in undefined styles (and also issue BZ#5515)
			/*
			// Default styles is not needed as it create extra line, and also we can't locate the cursor correctly inside <p><span>x</span></p> in FF and Safari.
			// It is pointless.
			$xPara = $xDoc->createElement( 'p' );
			$xPara->setAttribute( 'class', 'para'.$this->defaultpara );
			$xBody->appendChild( $xPara );

			$xSpan = $xDoc->createElement( 'span' );
			$xSpan->setAttribute( 'class', 'char'.$this->defaultchar );
			$xPara->appendChild( $xSpan );
			*/
		} else {
			foreach( $entries as $key => $icTxsr ) {
				$icInlineText = $icTxsr->parentNode->getAttribute( 'GUID');
				if( $icInlineText && strlen($icInlineText) > 2 ) {	// Check whether it is Inline Text, Inline Text is having GUID="k_"
					// Join paragraphs of same style
					$icPrst = $icTxsr->getAttribute( 'prst' );
					$icBnlt = $icTxsr->getAttribute( 'bnlt' );
					$same = (!is_null( $icPrst ) && !is_null( $prevPrst ) && $icPrst == $prevPrst);
					$sameBnlt = (!is_null( $icBnlt ) && !is_null( $prevBnlt ) && $icBnlt == $prevBnlt);
					if( $icBnlt == 'e_LTnm' ) { // BZ#17267 - Check whether there is repeat restart in the order list
						$icRestart = ($icTxsr->getAttribute( 'bncp' ) == 'b_f') ? true : false;
						$sameRestart = (!is_null( $icRestart) && !is_null( $prevRestart ) && $icRestart == $prevRestart);
					}
					if( !$same || $prevEndsWithEOL || !$sameBnlt ) {
						$xPara = $xDoc->createElement( 'p' );
						$xPara->setAttribute( 'class', 'para'.$icPrst );
						$xBody->appendChild( $xPara );
					}

					// Handle txsr element
					$this->handleTxsrElem( $icDoc, $icStory, $icTxsr, $xDoc, $xBody, $xPara, $xSpan, $xList, $key, $emptyList, $sameRestart );

					// Add hard enter separate joined paragraphs, except after the last para
					/*if( $same && isset($xSpan) && $i < ($entries->length-1) ) {
						$xEOL = $xDoc->createEntityReference( '#160' );
						$xSpan->appendChild( $xEOL );
					}*/
					$prevPrst = $icPrst;
					$prevBnlt = $icBnlt;
					$prevRestart = $icRestart;

					// Get the last text element of txsr to see if it has a hard enter at end (BZ#6602).
					// The $icTxsr->textContent does include markup tabs and enters (between txsr and pcnt)
					// so we can't use it.  Therefore we use XPath instead to get last text element.
					$query2 = 'pcnt/text()[position()=last()]'; // Get txsr's last text element
					$entries2 = $xpath->query( $query2, $icTxsr );
					$lastChars = ($entries2->length > 0 && $entries2->item(0)) ? substr( $entries2->item(0)->nodeValue, -strlen(IC_HARD_EOL) ) : false; // Don't use mb_substr since we do bytes here!
					$prevEndsWithEOL = $lastChars && ($lastChars == IC_HARD_EOL);
				}
			}
		}
		// return story's GUID, label and content
		$xFrame = new stdClass();
		$xpath = new DOMXPath( $icDoc );
		$query = 'SnippetRoot/cflo';
		$entries = $xpath->query( $query, $icStory );
		if( $entries->length == 0 ) { return null; } // happens e.g. for graphic articles!
		$guid = $entries->item(0)->getAttribute('GUID');
		$xFrame->ID = mb_substr( $guid, 2 ); // skip "k_" prefix
		// BZ#15879 - CS3 doc having "sLbl" attrib as label, while CS4 doc having "sTtl" as label
		if( $this->docDomVersion == 5 ) {
			$sLbl = $entries->item(0)->getAttribute('sLbl'); // CS3
		}
		else {
			$sLbl = $entries->item(0)->getAttribute('sTtl'); // CS4
		}
		$sLbl = mb_substr( $sLbl, 2 ); // skip "c_" prefix
		$sLbl = mb_eregi_replace( '~sep~', '_', $sLbl ); // IC stores ~sep~ for underscores
		$xFrame->Label = $sLbl;
		$xFrame->Document = $xDoc;
		$xFrame->Content = $xDoc->saveXML();

		// Determine page width and height (in preparation on next step)
		$icPageWidth = 0;
		$icPageHeight = 0;
		$xpath = new DOMXPath( $icDoc );
		$queries = array(
			'wwst_template/wwst_docinfo' => $icStory->parentNode,
			'wwsd_document/wwsd_docinfo' => $icStory->parentNode );
		foreach( $queries as $query => $icParent ) { // Walk through text frames having current story label
			$entries = $xpath->query( $query, $icParent );
			foreach( $entries as $entry ) {
				$icBound = $entry->getAttribute( 'bnd' );
				if( $icBound ) {
					$icBound = explode( ';', $icBound );
					$icPageWidth = $icBound[2];
					$icPageHeight = $icBound[3];
					break 2;
				}
			}
		}

		// Get geometry coordinates from article created on template with geo info
		$xpath = new DOMXPath( $icDoc );
		$queries = array(
			'wwst_template/wwst_stories/SnippetRoot/cflo[@GUID="'.$guid.'"]' => $icStory->parentNode,
			'wwsd_document/wwsd_stories/SnippetRoot/cflo[@GUID="'.$guid.'"]' => $icStory->parentNode );
		foreach( $queries as $query => $icParent ) { // Walk through text frames having current story label
			$cflos = $xpath->query( $query, $icParent );
			foreach( $cflos as $cflo ) {
				// Determine (first) reference id from cflo to txsf
				$txtfId = $cflo->getAttribute( 'Self' );
				if( $txtfId ) $txtfId = mb_substr( $txtfId, 3 ); // skip "rc_" prefix
				if( $txtfId ) { // ref found?
					$txtfs = $xpath->query( 'txtf[@strp="ro_'.$txtfId.'"]', $cflo->parentNode ); // Ref to strp att for both CS3 and CS4
					if(!$txtfs || $txtfs->length == 0) $txtfs = $xpath->query( 'SLSL/txtf[@strp="ro_'.$txtfId.'"]', $cflo->parentNode ); // also try Smart Layout
					foreach( $txtfs as $txtf ) {
						// Get geometry info from frame and translate it from IC to HTML coordinates
						// Note: IC base point is in middle of spread, while HTML base point is left top of page.
						$icGeo = $txtf->getAttribute( 'IGeo' );
						if( $icGeo ) {
							$icGeo = explode( '_', $icGeo ); // syntax: type_value_  -> left, top, right, bottom, transformation (6 digits)
							if( $icGeo && count($icGeo) > 51 ) {
								$xTrans = $icGeo[49];
								$yTrans = $icGeo[51];
								$xFrame->Left = $icGeo[33] + $xTrans;
								if( $xFrame->Left < 0 ) $xFrame->Left = $icPageWidth + $xFrame->Left; // assume when left side is negative, frame is on left page (else right page)
								$xFrame->Top    = $icGeo[35] + $yTrans + (0.5 * $icPageHeight);
								$xFrame->Width  = $icGeo[37] - $icGeo[33]; // r-l
								$xFrame->Height = $icGeo[39] - $icGeo[35]; // b-t
								$xFrame->PageSequence = 0;
								break 2; // found! (we only support 1 frame per story for now)
							}
						}
					}
				}
			}
		}
		return $xFrame;
	}

	/**
	 * Parse the InCopy txsr element, convert it into a XHTML and add it to the paragraph(p) element.
	 * InCopy path: Stories/Story/SnippetRoot/cflo/txsr/pcnt
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMNode     $icTxsr (in)    InCopy txsr element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xBody (in/out) XHTML body element.
	 * @param DOMNode     $xPara (in/out) XHTML paragraph element.
	 * @param DOMNode     $xSpan (in/out) XHTML span (char style) element.
	 * @param DOMNode     $xList (in/out) XHTML ul or ol element.
	 * @param String      $key			  Number of processed txsr in InCopy story
	 * @param Boolean	  $emptyList	  Previous empty LI
	 * @param Boolean	  $sameRestart	  Same restart value for continuous list item
	 */
	private function handleTxsrElem( $icDoc, $icStory, $icTxsr, &$xDoc, &$xBody, &$xPara, &$xSpan, &$xList, $key, &$emptyList, $sameRestart )
	{
		// Walk through paragraph's content chuncks
		$xpath = new DOMXPath( $icDoc );
		$query = 'pcnt';
		$entries = $xpath->query( $query, $icTxsr );
		foreach( $entries as $icPcnt ) { // loop, although there should be just one pcnt per txsr

			// Convert <txsr crst="[style]"  into  <span class="[style]"
			$icCrst = $icTxsr->getAttribute( 'crst' );
			$xSpan = $xDoc->createElement( 'span' );
			$xSpan->setAttribute( 'class', 'char'.$icCrst );
			$xPara->appendChild( $xSpan );

			// Make sure we embed all attributes that allows us to set back unhandled attributes on the way back!
			$attrBag = $this->collectTxsrAttrs( $icTxsr );
			if( count($attrBag) > 0 ) $xSpan->setAttribute( 'id', 'txsr_'.serialize($attrBag) );

			// Optionally handle WW tag, typically used for Smart Catalog fields
			// which is generated text, so we make those parts read-only to end-user.
			// This is done by putting it into popup help of icons, which are placeholders.
			$icWWtg = $icTxsr->getAttribute( 'WWtg' );
			if( $icWWtg && strlen($icWWtg) > 2 ) { // assume there is text only inside ( >2 implies emptyness checksum; skip leading '0_')
				$icText = mb_substr( $icPcnt->textContent, 2 ); // skip 'c_'
				$this->createImageForWWtg( $xDoc, $xSpan, $icWWtg, $icText );
			} else {
				// Walk through all pcnt childs
				for( $i = 0; $i < $icPcnt->childNodes->length; $i++ ) {
					$icNode = $icPcnt->childNodes->item( $i );
					// BZ#8760 - Get the next node type
					if( ($i + 1) < $icPcnt->childNodes->length ) {
						$icNextNode = $icPcnt->childNodes->item( $i + 1);
						$xNextNodeType = $icNextNode->nodeType;
					} else {
						$xNextNodeType = null;
					}
					if( $i > 0 ) {
						$icPrevNode = $icPcnt->childNodes->item( $i - 1);
						$xPrevNodeType = $icPrevNode->nodeType;
					} else {
						$xPrevNodeType = null;
					}
					switch( $icNode->nodeType ) {
						case XML_TEXT_NODE: // Node is a DOMText
							$icText = $icNode->nodeValue;
							$prefix = '';
							if( $i == 0 ) {
								$prefix = substr( $icText, 0, 2 );
								$icText = mb_substr( $icText, 2 ); // skip 'c_'
							}
							if( $prefix == 'e_' ) {
								$symbol = substr( $icText, 0, 4 ); // first four chars is the symbol
								// Text Symbols - Convert it back to hexadecimal character
								$textsymbols = array('SEmD' => chr(0xE2).chr(0x80).chr(0x94),	// Em Dash
								                     'SEnD' => chr(0xE2).chr(0x80).chr(0x93),	// En Dash
								                     'SDHp' => chr(0xC2).chr(0xAD),				// Discretionary Hyphen
								                     'SNbh' => chr(0xE2).chr(0x80).chr(0x91),	// Nonbreaking hyphen
								                     'SEmS' => chr(0xE2).chr(0x80).chr(0x83),	// Em Space
								                     'SEnS' => chr(0xE2).chr(0x80).chr(0x82),	// En Space
								                     'Snnb' => chr(0xE2).chr(0x80).chr(0xAF),   // Nonbreaking Space (Fixed Width)
								                     'SHrS' => chr(0xE2).chr(0x80).chr(0x8A),	// Hair Space 
								                     'SSiS' => chr(0xE2).chr(0x80).chr(0x86),	// Sixth Space
								                     'STnS' => chr(0xE2).chr(0x80).chr(0x89),	// Thin Space
								                     'SQuS' => chr(0xE2).chr(0x80).chr(0x85),	// Quarter Space
								                     'SThS' => chr(0xE2).chr(0x80).chr(0x84),	// Third Space
								                     'SPnS' => chr(0xE2).chr(0x80).chr(0x88),	// Punctuation Space
								                     'SFgS' => chr(0xE2).chr(0x80).chr(0x87),	// Figure Space
								                     'SFlS' => chr(0xE2).chr(0x80).chr(0x81),	// Flush Space
								                     'SBlt' => chr(0xE2).chr(0x80).chr(0xA2),	// Bullet Character
								                     'SCrt' => chr(0xC2).chr(0xA9),				// Copyright Symbol
								                     'SLps' => chr(0xE2).chr(0x80).chr(0xA6),	// Ellipsis
								                     'SPar' => chr(0xC2).chr(0xB6),				// Paragraph Symbol
								                     'SRTm' => chr(0xC2).chr(0xAE),				// Registered Trademark Symbol
								                     'SsnS' => chr(0xC2).chr(0xA7),				// Section Symbol
								                     'STmk' => chr(0xE2).chr(0x84).chr(0xA2),	// Trademark Symbol
								                     'SDLq' => chr(0xE2).chr(0x80).chr(0x9C),	// Double Left Quotation Marks
								                     'SDRq' => chr(0xE2).chr(0x80).chr(0x9D),	// Double Right Quotation Marks
								                     'SSLq' => chr(0xE2).chr(0x80).chr(0x98),	// Single Left Quotation Mark
								                     'SSRq' => chr(0xE2).chr(0x80).chr(0x99),	// Single Right Quotation Mark
								                     'SDSq' => chr(0x22),						// Straight Double Quotation Marks
								                     'SSSq' => chr(0x27),						// Straight Single Quotation Mark (Apostrophe)
								                     'SPnj' => chr(0xE2).chr(0x80).chr(0x8C),	// Non-joiner
								                     'SFlb' => chr(0xE2).chr(0x80).chr(0xA8)	// Forced Line Break
								                   ); 
								$textsymbol = array_key_exists( $symbol, $textsymbols ) ? $textsymbols[$symbol] : null;
								if( !$textsymbol ) {
									// BZ#17267 - When symbol reside in the ordered/unordered, call createListElement and createListItem,
									//            to create OL/UL and LI element so that symbol element allowed to be append inside.
									if( !$sameRestart ) { // BZ#17267 - when restart is not repeat, then create list element
										$this->createListElement( $icTxsr, $xDoc, $xBody, $xPara, $xList );
									}
									if( $xList ) { // BZ#17267 - When xList created, then only create list item
										$this->createListItem( $icTxsr, $xDoc, $xSpan, $xList, $emptyList, 0 );
									}
									$this->handleSymbol( $icDoc, $icStory, $symbol, $xDoc, $xSpan );
									$icText = mb_substr( $icText, 4 ); // there should no more text, but let's do robust
								}
								else {
									$icText = $textsymbol;
								}
							}
							if( strlen($icText) > 0 ) $this->handleText( $icDoc, $icStory, $icTxsr, $icText, $xDoc, $xBody, $xPara, $xSpan, $xList, $xPrevNodeType, $xNextNodeType, $key, $emptyList, $sameRestart );
							break;
						case XML_PI_NODE: // Node is a DOMProcessingInstruction
							if( !$sameRestart ) { // check whether needs to create a new list element if node fal below list. 
								$this->createListElement( $icTxsr, $xDoc, $xBody, $xPara, $xList );
							}
							if( $xList ) { // BZ#18076 - If list exists, append the node inside.
								$this->createListItem( $icTxsr, $xDoc, $xSpan, $xList, $emptyList, 0 );
							}
							$this->handlePcntPI( $icDoc, $icStory, $icNode, $xDoc, $xSpan );
							break;
					}
				}
			}
		}
	}

	/**
	 * Parse the InCopy text() element, convert it into a XHTML and add it to the span element.
	 * Also, hard EOL are converted to p+span elements, and soft EOL to br elements.
	 * InCopy path: Stories/Story/SnippetRoot/cflo/txsr/pcnt/text()
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMNode     $icTxsr (in)    InCopy txsr element.
	 * @param DOMTextNode $icText (in)    InCopy text element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xBody (in/out) XHTML body element.
	 * @param DOMNode     $xPara (in/out) XHTML paragraph element.
	 * @param DOMNode     $xSpan (in/out) XHTML span element.
	 * @param DOMNode     $xList (in/out) XHTML ul or ol element.
	 * @param DOMNodeType $xPrevNodeType  XHTML Previous Node Type.
	 * @param DOMNodeType $xNextNodeType  XHTML Next Node Type.
	 * @param string 	  $key  		  Number of processed txsr in InCopy story
	 * @param boolean	  $xEmptyList	  Previous empty LI
	 * @param boolean	  $sameRestart	  Same restart value for continuous list item
	 */
	private function handleText( $icDoc, $icStory, $icTxsr, $icText, &$xDoc, &$xBody, &$xPara, &$xSpan, &$xList, $xPrevNodeType, $xNextNodeType, $key, &$xEmptyList, $sameRestart )
	{
		$xLI = null; // list item
		// BZ#6473 replace IC's nbsp (fixed width) with normal nbsp, or else it won't be shown in HTML
		$icText = mb_eregi_replace( chr(0xE2).chr(0x80).chr(0xAF), chr(0xC2).chr(0xA0), $icText );

		$texts1 = mb_split( IC_HARD_EOL, $icText ); // Hard enter means: start new paragraph or list item
		$xTextParent = null;
		foreach( $texts1 as $key1 => $text1 ) {
			// BZ#8760 - Last line ending before next node type of XML_PI_NODE, should create the line
			$addPara = false;
			if( !(strlen( $text1 )) && $key1 == (count($texts1)-1) && $xNextNodeType == XML_PI_NODE ) {
				$addPara = true;
			}
			// Handle bullets and numering (defined in para styles)
			if( !(strlen( $text1 )) ) {
				$icPrst = $icTxsr->getAttribute( 'prst' );
				if( $icPrst ) {
					$icPrst = substr( $icPrst, 2 ); // skip "o_"
					$icPsty = $this->stylearray['para'][$icPrst];
					if( $icPsty ) {
						$icBnlt = $this->getInheritedAttribute( $icPsty, 'bnlt', 'para' );
						// BZ#14045 - Check on whether xList created, if no, then create new line
						if( $xList && ($icBnlt == 'e_LTnm' || $icBnlt == 'e_LTbt') ) {
							continue; // skip line ending (that have no following text) to avoid creating new empty list items, or else you get two bullets in HTML (per list item)
						}
					}
				}
			}

			// Handle bullets and numbering (free format)
			if( strlen( $text1 ) > 0 ) { // avoid new list creations (or restarts) for hard enters
				if( !$sameRestart ) {
					$this->createListElement( $icTxsr, $xDoc, $xBody, $xPara, $xList );
				}
			}

			// Insert container element (to hold new text chunk), which is a p+span (style change) or ol/ul (list)
			if( $xList ) {
				if( strlen( $text1 ) > 0 || $addPara ) { // avoid empty list bullets/numbers at end of list except PI_NODE
					if( $text1 == chr(10)) {
						continue;
					}
					$this->createListItem( $icTxsr, $xDoc, $xSpan, $xList, $xEmptyList, $key1 );
				}
				else {
					$xEmptyList = true;
				}
				$xHyperLink = $this->hyperLinks->getCurrentLink();
				$xTextParent = is_null($xHyperLink) ? $xSpan : $xHyperLink;
			} else {
				$xLI = null;

				if( $key1 < (count($texts1)-1) || strlen($text1) > 0 || $addPara ) { // skip last line ending when there is no text after it! (BZ#6429)

					// Add  <p class="[para style]"
					if( $key1 > 0 || is_null( $xPara ) ) { // normally, paras are pre-created by caller, so skip first time
						$icPrst = $icTxsr->getAttribute( 'prst' );
						$xPara = $xDoc->createElement( 'p' );
						$xPara->setAttribute( 'class', 'para'.$icPrst );
						$xBody->appendChild( $xPara );
						$xSpan = null; // new para requires new span (as done below)
					}

					// Add  <span class="[char style]"
					if( $key1 > 0 || is_null( $xSpan ) ) { // normally, spans are pre-created by caller, so skip first time
						$icCrst = $icTxsr->getAttribute( 'crst' );
						$xSpan = $xDoc->createElement( 'span' );
						$xSpan->setAttribute( 'class', 'char'.$icCrst );
						$xPara->appendChild( $xSpan );

						// Make sure we embed all attributes that allows us to set back unhandled attributes on the way back!
						$attrBag = $this->collectTxsrAttrs( $icTxsr );
						if( count($attrBag) > 0 ) $xSpan->setAttribute( 'id', 'txsr_'.serialize($attrBag) );
					}
					$xHyperLink = $this->hyperLinks->getCurrentLink();
					$xTextParent = is_null($xHyperLink) ? $xSpan : $xHyperLink;
				}
			}

			// Insert font face/size
			$font = $icTxsr->getAttribute( 'font' ); // face
			$ptsz = $icTxsr->getAttribute( 'ptsz' ); // size
			$flcl = $icTxsr->getAttribute( 'flcl' ); // text color
			$lncl = $icTxsr->getAttribute( 'lncl' ); // back color   // style="background-color: rgb(153, 153, 153);"
			if( $ptsz || $font || $flcl || $lncl ) {
				$xFont = $xDoc->createElement( 'font' );
				$xTextParent->appendChild( $xFont );
				if( $font ) $xFont->setAttribute( 'face', substr($font,2) ); // skip "c_" prefix
				if( $flcl ) $xFont->setAttribute( 'color', $this->getColor( substr($flcl,2) ) ); // skip "o_" prefix
				$style = $lncl ? 'background-color:'.$this->getColor( substr($lncl,2) ).';' : ''; // skip "o_" prefix
				$style .= $ptsz ? 'font-size:'.substr($ptsz,2).';' : ''; // skip "U_" prefix
				if( !empty($style) ) $xFont->setAttribute( 'style',  $style );
				$xTextParent = $xFont;
			}

			// Insert bold/italic elements
			$ptfs = $icTxsr->getAttribute( 'ptfs' );
			if( $ptfs == 'c_Bold' || $ptfs == 'c_Bold Italic' ) {
				$xBold = $xDoc->createElement( 'strong' );
				$xTextParent->appendChild( $xBold );
				$xTextParent = $xBold;
			}
			if( $ptfs == 'c_Italic' || $ptfs == 'c_Bold Italic' ) {
				$xItalic = $xDoc->createElement( 'em' );
				$xTextParent->appendChild( $xItalic );
				$xTextParent = $xItalic;
			}

			// Insert strike-through element
			if( $icTxsr->getAttribute( 'strk' ) == 'b_t' ) {
				$xStrike = $xDoc->createElement( 'strike' );
				$xTextParent->appendChild( $xStrike );
				$xTextParent = $xStrike;
			}

			// Insert underline element
			if( $icTxsr->getAttribute( 'undr' ) == 'b_t' ) {
				$xUnder = $xDoc->createElement( 'u' );
				$xTextParent->appendChild( $xUnder );
				$xTextParent = $xUnder;
			}

			// Insert subscript/superscript element
			$posm = $icTxsr->getAttribute( 'posm' );
			if( $posm == 'e_sbsc' ) {
				$xSub = $xDoc->createElement( 'sub' );
				$xTextParent->appendChild( $xSub );
				$xTextParent = $xSub;
			} elseif( $posm == 'e_spsc' ) {
				$xSup = $xDoc->createElement( 'sup' );
				$xTextParent->appendChild( $xSup );
				$xTextParent = $xSup;
			}

			// Insert the text chunks (respecting soft line breaks)
			$texts2 = mb_split( IC_SOFT_EOL, $text1 );
			foreach( $texts2 as $key2 => $text2 ) {

				// Tabs are make-up chars for HTML elements.
				// Here we convert them to tab icons to visualize them.
				$texts3 = mb_split( chr(0x09), $text2 );
				foreach( $texts3 as $key3 => $text3 ) {
					$xText = $xDoc->createTextNode( $text3 );
					$xTextParent->appendChild( $xText );

					// Add tab icon for tab chars, except last text
					if( $key3 < (count($texts3) - 1) ) {
						$xImg = $xDoc->createElement( 'img' );
						$xImg->setAttribute( 'src', '' );
						$xImg->setAttribute( 'id', 'AC_OTHER_TAB' );
						$xImg->setAttribute( 'class', 'mceAdobeChar' );
						$xImg->setAttribute( 'title', '' );
						$xTextParent->appendChild( $xImg );
					}
				}

				// Add <br> for soft enters, except last text (unless very last char is soft enter)
				if( $key2 < (count($texts2) - 1) ||
					(substr( $text1, -3 ) == IC_SOFT_EOL) ) {
				    //mb_substr( $text1, mb_strlen( $text1 )-1, 1 ) == IC_SOFT_EOL ) {
					$xBreak = $xDoc->createElement( 'br' );
					$xTextParent->appendChild( $xBreak );
				}
			}

			// Add non-breaking space to empty paragraphs, or else they won't show in HTML!  (BZ#6429)
			// This is the same trick TinyMCE does when users hit enter at end of line (->adding new empty paragraph).
			if( is_null( $xLI ) && !( strlen( $text1 ) ) && $key1 < (count($texts1)-1) &&
			($key1 != 0 || ($key1 == 0 && $key == 0 && $xPrevNodeType != XML_PI_NODE) )) { 	// There should be no space after XML_PI_NODE or symbol
					$xNBS = $xDoc->createTextNode( chr(0xC2).chr(0xA0) ); // &nbsp; entity written in UTF-8 (&#x00A0; in UTF-16 or &#xC2A0; in UTF-8)
					$xTextParent->appendChild( $xNBS );
			}
		}
	}

	/**
	 * Collects all "unknown" txsr attributes of the given InCopy txsr element ($icTxsr).
	 * This is all done to maintain free formating to text selections (not to be confused with para/char styles).
	 * The unknown attributes are serialized in the span->id attribute in HTML (outside this function).
	 *
	 * @param DOMNode  $icTxsr (in)  InCopy txsr element.
	 * @return array of txsr attributes (exept prst and crst)
	 */
	private function collectTxsrAttrs( $icTxsr )
	{
		$attrBag = array();
		for( $j = 0; $j < $icTxsr->attributes->length; $j++ ) {
			$elemAttr = $icTxsr->attributes->item( $j );
			if( array_key_exists( $elemAttr->name, self::$handledTxsrAttributes ) ) {
				$handlesValues = self::$handledTxsrAttributes[$elemAttr->name];
				if( count($handlesValues) > 0 && !in_array( $elemAttr->value, $handlesValues) ) { // check for unknown attribute
					$attrBag[ $elemAttr->name ] = $elemAttr->value;
				}
			}
			else {
				$attrBag[ $elemAttr->name ] = $elemAttr->value;  // unknown attribute 
			}
		}
		return $attrBag;
	}

	/**
	 * Gets the attribute for the given style element or from its parent.
	 *
	 * @param DOMNode     $icElem       InCopy paragraph-, character- or color style element.
	 * @param string      $icAttr       InCopy attribute name.
	 * @param string      $icStyleType  'para', 'char' or 'colr'.
	 * @return value of the attribute. Empty when not found.
	 */
	private function getInheritedAttribute( $icElem, $icAttr, $icStyleType )
	{
		if( $icElem && $icAttr ) {
			// Try this element
			$icAttrVal = $icElem->getAttribute( $icAttr );
			if( $icAttrVal ) {
				return $icAttrVal;
			}
			// Try parent/inherit element
			$icBasd = $icElem->getAttribute( 'basd' );
			if( $icBasd ) {
				if( $icBasd == 'k_[No paragraph style]' ) { $icBasd = $this->defaultpara; } // resolve default para
				$icBasd = substr( $icBasd, 2 ); // skip "o_"
				return $this->getInheritedAttribute( $this->stylearray[$icStyleType][$icBasd], $icAttr, $icStyleType );
			}
		}
		return '';
	}

	/**
	 * Parse InCopy symbols, convert it into a XHTML image (place holder) and add it to the span element.
	 * InCopy path: Stories/Story/SnippetRoot/cflo/txsr/pcnt/text()
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param string      $icSymbol (in)  InCopy symbol.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xSpan (in/out) XHTML span element.
	 */
	private function handleSymbol( $icDoc, $icStory, $icSymbol, &$xDoc, &$xSpan )
	{
		// TODO: Localizations
		$symbols = array( 'SClB' => 'AC_BREAK_COLUMN', 'SFrB' => 'AC_BREAK_FRAME', 'SPgB' => 'AC_BREAK_PAGE', 'SApn' => 'AC_MARKER_CURRENTPAGE', 'SNpn' => 'AC_MARKER_NEXTPAGE', 'SPpn' => 'AC_MARKER_PREVIOUSPAGE', 'SsnM' => 'AC_MARKER_SECTION', 'SOpB' => 'AC_BREAK_ODDPAGE', 'SEpB' => 'AC_BREAK_EVENPAGE', 'SPtv' => 'Text variable', 'SNbS' => 'AC_WHITESPACE_NONBREAKING' );
		if( $icSymbol != 'SPtv' ) { // Skip the Text Variables, which are identified with "< ?aid ..." instructions; these are handled in handlePcntPI function.
			$acKey = array_key_exists( $icSymbol, $symbols ) ? $symbols[$icSymbol] : BizResources::localize( 'ART_SPECIAL_CHAR' );

			$xImg = $xDoc->createElement( 'img' );
			$xImg->setAttribute( 'src', '' );
			$xImg->setAttribute( 'id', $acKey );
			$xImg->setAttribute( 'class', 'mceAdobeChar' );
			$xImg->setAttribute( 'title', '' );
			$xSpan->appendChild( $xImg );
		}
	}

	/**
	 * Parse the InCopy processing instructions (PI), convert it into a XHTML image (place holder) and add it to the span element.
	 * Only known instructions (aid and ACE) are converted. Others are passed through as-is.
	 * InCopy path: Stories/Story/SnippetRoot/cflo/txsr/pcnt/text()
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMProcessingInstruction $icPI (in) InCopy PI element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xSpan (in/out) XHTML span element.
	 */
	private function handlePcntPI( $icDoc, $icStory, $icPI, &$xDoc, &$xSpan )
	{
		switch( $icPI->target ) {
			case 'aid':
				// Parse PI, that looks like: < ?aid Char="0" Self="rc_ua9cins3f"? >
				$selfs = array();
				preg_match('/Self=\"([0-9A-Z_]+)\"/i', $icPI->data, $selfs );
				if( count($selfs) > 0 ) {
					$chars = array();
					preg_match('/Char=\"([0-9A-Z_]+)\"/i', $icPI->data, $chars ); 
					$char = count($chars) > 0 ? $chars[1] : '';
					$this->handleAidPI( $icDoc, $icStory, $xDoc, $xSpan, $selfs[1], $char );
				}
				break;
			case 'ACE':
				$this->handleAcePI( $icDoc, $icPI->data, $xDoc, $xSpan );
				break;
			default: // copy unknown PI
				$pi = $xDoc->createProcessingInstruction( $icPI->target, $icPI->data );
				$xSpan->appendChild( $pi );
		}
	}

	/**
	 * Parse the InCopy ACE processing instructions (PI), convert it into a XHTML image (place holder) and add it to the span element.
	 * The ACE elements represent all kind of instructions, such as footnote number, right indent tab, auto page number, etc
	 * InCopy path: Stories/Story/SnippetRoot/cflo/txsr/pcnt/text()
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param string      $icAceId (in)   InCopy ACE id. Taken from: < ?ACE [id]? >
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xSpan (in/out) XHTML span element.
	 */
	private function handleAcePI( $icDoc, $icAceId, &$xDoc, &$xSpan )
	{
		// TODO: Localizations
		$symbols = array( '3' => 'AC_OTHER_ENDNESTEDSTYLE', '4' => 'AC_MARKER_FOOTNOTE', '7' => 'AC_OTHER_INDENTTOHERE', '8' => 'AC_OTHER_RIGHTINDENTTAB', '18' => 'AC_MARKER_CURRENTPAGE', '19' => 'AC_MARKER_SECTION' );
		$acKey = array_key_exists( $icAceId, $symbols ) ? $symbols[$icAceId] : BizResources::localize( 'ART_SPECIAL_CHAR' );

		if( $icAceId != '4' ) { // Skip the Text Variables, which are identified with "< ?aid ..." instructions; these are handled in handlePcntPI function.
			$xImg = $xDoc->createElement( 'img' );
			$xImg->setAttribute( 'src', '' );
			$xImg->setAttribute( 'id', $acKey );
			$xImg->setAttribute( 'class', 'mceAdobeChar' );
			$xImg->setAttribute( 'title', '' );
			$xSpan->appendChild( $xImg );
		}
	}

	/**
	 * Factory that creates StoryResourceDef object out of given unconvertable InCopy XML element ($icNode). <br/>
	 *
	 * @param DOMDocument $icDoc
	 * @param DOMNode $icNode
	 * @param string $elemId
	 * @param string $elemEndId
	 * @param boolean $startMarker
	 * @return StoryResourceDef
	 */
	private function createStoryResourceDef( $icDoc, $icNode, $elemId, $elemEndId, $startMarker )
	{
		$elemDescr = '';
		$backColor = '';
		$elemIcon = '../../config/images/webeditor/space_16.gif'; // default (should not be used)

		switch( $icNode->nodeName ) {
			case 'TVc3': // text variable
				$tvName = $icNode->getAttribute('pnam');
				$tvName = mb_substr( $tvName, 3 ); // skip 'rc_' prefix
				$tvVal = $icNode->getAttribute('TVeq');
				$tvVal = mb_substr( $tvVal, 3 ); // skip 'rc_' prefix
				$elemDescr = $tvName.': '.$tvVal;
				$elemIcon = '../../config/images/webeditor/textvar_16.gif';
				break;
			case 'Note': // inline note
				$elemDescr = BizResources::localize( 'ART_INLINE_NOTE' ).': ';
				$backColor = 'Yellow'; // default (should be overridden with user color)
				$elemIcon = '../../config/images/webeditor/usernote_16.gif';
				break;
			case 'FNcl': // footnote
				$elemDescr = BizResources::localize( 'ART_FOOT_NOTE' ).': ';
				$elemIcon = '../../config/images/webeditor/footnote_16.gif';
				break;
			case 'ctbl': // table
				$elemDescr = BizResources::localize('ART_TABLE').': ';
				$elemIcon = '../../config/images/webeditor/table_16.gif';
				break;
			case 'crec': // inline image
				$elemDescr = BizResources::localize('ART_EMBEDDED_IMAGE');
				$elemIcon = '../../config/images/webeditor/image_16.gif';
				break;
			case 'covl': // Inline Circle/Polygon
				$elemDescr = BizResources::localize('ART_EMBEDDED_IMAGE');
				$elemIcon = '../../config/images/webeditor/image_16.gif';
				break;
			case 'txtf': // inline text
				$elemDescr = BizResources::localize('ART_INLINE_TEXT');
				$elemIcon = '../../config/images/webeditor/text_16.gif';
				break;
			case 'glin': // inline line
				$elemDescr = BizResources::localize('ART_EMBEDDED_IMAGE');
				$elemIcon = '../../config/images/webeditor/image_16.gif';
				break;
			case 'Push': // inline button
				$elemDescr = BizResources::localize('ART_EMBEDDED_IMAGE');
				$elemIcon = '../../config/images/webeditor/image_16.gif';
				break;
			case 'cpgn': // Inline Pencil
				$elemDescr = BizResources::localize('ART_EMBEDDED_IMAGE');
				$elemIcon = '../../config/images/webeditor/image_16.gif';
				break;
			case 'cXML': // XML tag
				$elemDescr = BizResources::localize('ART_TAG');
				if( $startMarker ) {
					$elemIcon = '../../config/images/webeditor/ins_stt_16.gif'; // Insert (start marker)
				} else {
					$elemIcon = '../../config/images/webeditor/ins_end_16.gif'; // Insert (end marker)
				}
				break;
			case 'hitx': // invisible conditional text
				$elemDescr = BizResources::localize('ART_CONDITIONAL_TEXT');
				$elemIcon = '../../config/images/webeditor/image_16.gif';
				break;
		}
		switch( $icNode->nodeName ) {
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'Note': // inline note
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'FNcl': // footnote
				if( ($dcuz = $icNode->getAttribute('dcuz') ) ) { // user id
					$userId = mb_substr( $dcuz, 2 ); // skip 'o_' prefix
					if( array_key_exists( $userId, $this->icUsers ) === true ) {
						$userObj = $this->icUsers[$userId];
						require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
						$backColor = BizAdmUser::getTrackChangesColor( $userObj->Color );
						$elemDescr = $userObj->Name.': '.$elemDescr;
					}
				}
				// continue ... !   (no break)
			case 'ctbl': // table
				// Get table/note/change text content to show in popup help
				$xpath = new DOMXPath( $icDoc );
				$query = './/pcnt'; // Walk through inline note-, footnote-, tracked change- or table content
				$entries = $xpath->query( $query, $icNode );
				foreach( $entries as $icTabCont ) {
					$elemDescr .= mb_substr( $icTabCont->textContent, 2 ); // skip 'c_' prefix
					$elemDescr .= ' '; // separate multi lines with spaces
				}
				break;
			case 'TVc3': // text variable
			case 'crec': // inline image
			case 'glin': // inline line
			case 'Push': // inline button
			case 'cpgn': // inline pencil
			case 'covl': // inline circle/polygon
				break;
			case 'txtf': // inline text
				$roId = '';
				if( ($strp = $icNode->getAttribute('strp')) ) {
					$roId = mb_substr( $strp, 3 ); // skip 'ro_' prefix
				}
				// Get inline text content to show in popup help
				$xpath = new DOMXPath( $icDoc );
				$query = '//Stories/Story/SnippetRoot/cflo[@Self="rc_'.$roId.'"]';
				$cflos = $xpath->query( $query );

				foreach( $cflos as $cflo ) {
					$query = './txsr/pcnt';
					$entries = $xpath->query( $query, $cflo );
					foreach( $entries as $icText) {
						$elemDescr .= mb_substr( $icText->textContent, 2 ); // skip 'c_' prefix
						$elemDescr .= ' '; // separate multi lines with spaces
					}
				}
				break;
			case 'cXML': // XML tag
				if( ($tagResId = $icNode->getAttribute('XMLt') ) ) { // reference to XML tag
					$tagResId = 'rc_'.mb_substr( $tagResId, 2 ); // replace 'o_' with 'rc_' prefix
					$xpath = new DOMXPath( $icDoc );
					$query = '//Stories/Story/SnippetRoot/tagX[@Self="'.$tagResId.'"]';
					$tags = $xpath->query( $query );
					if( $tags->length > 0 && ($tag = $tags->item(0)) ) {
						$tagName = $tag->getAttribute('pnam');
						if( strlen($tagName) >= 2 ) {
							$tagName = mb_substr( $tagName, 2 ); // skip 'c_' prefix
						}
						if( strlen($tagName) ) {
							$elemDescr .= ': '.$tagName;
						}
						$tagClr = $tag->getAttribute('XTCr'); // tag color
						if( strlen($tagClr) >= 2 ) {
							$tagClr = mb_substr( $tagClr, 2 ); // skip 'e_' prefix
						}
						if( strlen($tagClr) ) {
							require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
							$backColor = BizAdmUser::getTrackChangesColor( $tagClr );
						}
					}
				}
				break;
			case 'hitx': // invisible conditional text
				// Get invisible text content to show in popup help
				$xpath = new DOMXPath( $icDoc );
				$query = './/pcnt'; // Walk through the text content
				$entries = $xpath->query( $query, $icNode );
				$elemDescr .= ': ';
				foreach( $entries as $icTabCont ) {
					$elemDescr .= mb_substr( $icTabCont->textContent, 2 ); // skip 'c_' prefix
					$elemDescr .= ' '; // separate multi lines with spaces
				}
				break;
		}
		$obj = new StoryResourceDef();
		$obj->setData( $elemId, $elemEndId, $icNode->nodeName, $elemDescr, $elemIcon, $backColor );
		return $obj;
	}

	/**
	 * Searches resource definitions at InCopy database for embedded elements referenced from 
	 * text content by given resource id ($rcId) as held in Processing Instructions.
	 *
	 * Note that Tracked Changes (Chng) is left out on purpose(!) since we can not round-trip Tracked Changes 
	 * with InCopy due to technical difficulties at Web Editor. This function skips all changes so they'll  get 
	 * applied/removed implicitly by opening the article. TODO: Would it be nice to inform user about the applied changes...?
	 *
	 * @param DOMDocument $icDoc (in)   InCopy document.
	 * @param DOMNode     $icStory (in) InCopy Story element.
	 * @param string      $rcId (in)    Resource id of the embedded element to search for.
	 * @param string     $char (in)    Special marker (The Char attribute of the aid PI).
	 * @return StoryResourceDef|null
	 */
	private function searchStoryResourceDefs( $icDoc, $icStory, $rcId, $char )
	{
		$rcId = mb_substr( $rcId, 3 ); // skip 'rc_' prefix
		
		// 1. Search for known elements directly under cflo.
		$elemNames = array( 
			'ctbl', // table
			'crec', // inline image
			'Note', // inline note
			'FNcl', // inline footnote
			'TVc3', // text variable
			'txtf', // inline text frame
			'glin', // inline line
			'Push', // inline button
			'cpgn', // inline pencil
			'covl', // inline circle/polygon
			'hitx'	// invisible conditional text
		);
		$xpath = new DOMXPath( $icDoc );
		$query = 'SnippetRoot/cflo/*[@STof="ro_'.$rcId.'"]';
		$entries = $xpath->query( $query, $icStory );
		foreach( $entries as $icNode ) {
			if( in_array( $icNode->nodeName, $elemNames ) ) {
				return $this->createStoryResourceDef( $icDoc, $icNode, $rcId, '', true );
			}
		}

		// 2. Search for XML tags.
		if( $char == 'feff' ) { // XML tag indicator
			$entries = $xpath->query( 'SnippetRoot//cXML', $icStory );
			foreach( $entries as $icNode ) {
				if( ($Xcnt = $icNode->getAttribute('Xcnt') ) ) {
					$elemId = mb_substr( $Xcnt, 2 ); // skip 'o_' prefix
					$multiKey = mb_split( ':', $elemId ); // parse double resource ids (start:end)
					if( count( $multiKey ) > 1  && ($multiKey[0] == $rcId || $multiKey[1] == $rcId) ) { 
						return $this->createStoryResourceDef( $icDoc, $icNode, $multiKey[0], $multiKey[1], $multiKey[0] == $rcId );
					}
				}
			}
		}

		// 3. Search Cross References
		if( $char == '0' ) {
			$entries = $xpath->query( 'SnippetRoot/XRSr', $icStory );
			foreach( $entries as $icNode ) {
				if( ($hsTX = $icNode->getAttribute('hsTx') ) ) {
					$elemId = mb_substr( $hsTX, 2 ); // skip 'o_' prefix
					$multiKey = mb_split( ':', $elemId ); 
					if( count( $multiKey ) > 1  && ($multiKey[0] == $rcId || $multiKey[1] == $rcId) ) { // double key? (startkey:endkey)
						$refObj = $this->createStoryResourceDef( $icDoc, $icNode, $multiKey[0], $multiKey[1].'_end', $multiKey[0] == $rcId );
						return $refObj;
					}
				}
			}
		}

		// 4. Search Tracked Changes
		/* // COMMENTED OUT: We lose track changes here because Web Editor does not support it.
		$entries = $xpath->query( 'SnippetRoot/cflo/Chng', $icStory );
		foreach( $entries as $icNode ) {
			if( ($STof = $icNode->getAttribute('STof') ) ) {
				$elemId = mb_substr( $STof, 3 ); // skip 'ro_' prefix
				$multiKey = mb_split( ':', $elemId ); 
				if( count( $multiKey ) > 1  && ($multiKey[0] == $rcId || $multiKey[1] == $rcId) ) { // double key? (startkey:endkey)
					$refObj = $this->createStoryResourceDef( $icDoc, $icNode, $multiKey[0], $multiKey[1].'_end', $multiKey[0] == $rcId );
					return $refObj;
				}
			}
		}*/
		return null;
	}

	/**
	 * Parse the InCopy aid processing instructions (PI), convert it into a XHTML image (place holder) and add it to the span element.
	 * The aid elements represent all kind of embedded objects, such as images, tables, user comments, sticky notes, etc
	 * InCopy path: Stories/Story/SnippetRoot/cflo/txsr/pcnt/text()
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xSpan (in/out) XHTML span element.
	 * @param string     $resId (in)     Resource id (The Self attribute of the aid PI).
	 * @param string     $char (in)      Special marker (The Char attribute of the aid PI).
	 */
	private function handleAidPI( $icDoc, $icStory, &$xDoc, &$xSpan, $resId, $char )
	{
		$resDef = $this->searchStoryResourceDefs( $icDoc, $icStory, $resId, $char );
		if( $resDef ) {
			$this->createImageForPI( $xDoc, $xSpan, $resDef, $resId.':'.$char, 'aid' );
		} else { // BZ#10578: Preserve hyperlinks
			// Nothing found; Let's see if we are dealing with hyperlinks...
			$this->handleHyperlink( $icDoc, $icStory, $xDoc, $xSpan, $resId );
		}
	}
	
	/**
	 * Parse the InCopy aid processing instructions (PI), convert it into a XHTML hyperlink (anchor/<a>) and add it to the span element.
	 * References made from PI to IC DB are very unlike other embedded objects, such as images, tables, user comments, sticky notes, etc
	 * When you have a link "http://www.myworld.com" defined behind some text "hello world" and named it "myworld", the IC doc looks like this:
	 *	...
	 *	<cflo ...>
	 *		...
	 *		<txsr crst="o_u68" prst="o_u6b">
	 *			<pcnt>c_<?aid Char="0" Self="rc_ubdcins4"?>hello worl</pcnt>
	 *		</txsr>
	 *		<txsr crst="o_u68" prst="o_u6b">
	 *			<pcnt>c_<?aid Char="0" Self="rc_ubdcins7"?>d<?aid Char="0" Self="rc_ubdcins8"?></pcnt>
	 *		</txsr>
	 *	</cflo>
	 *	<HLUd Self="rc_udb" hURL="c_http://www.myworld.com" hddn="b_t" pnam="c_.219"/>
	 *	<HLTs Self="rc_ude" hddn="b_f" hsTx="o_ubdcins4:ubdcins8" pnam="c_myworld"/>
	 *	<HLOB Self="rc_udf" clr="e_iBlk" hHlt="e_none" hSty="e_sold" hddn="b_f" hlds="o_udb" hlsc="o_ude" pnam="c_myworld" pvis="b_t" wdwh="e_thin"/>
	 *	...
	 * There is a start marker (ubdcins4) and an end marker (ubdcins8) that enclose the text "hello world" and refer to the IC DB definition HLTs.
	 * The HLTs element points to the hyperlink object HLOB (HLTs->Self points to HLOB->hlsc).
	 * The HLOB element points to the hyperlink url HLUd element (HLOB->hlds points to HLUd->Self).
	 *
	 * This function creates an XHTML hyperlink such as <a id="rc_udf" href="http://www.myworld.com" title="myworld"/>
	 * But the challenge is, that the text that needs to go INSIDE the XHTML anchor <a> element, can not be handled here!
	 * Instead, the handleText() function deals with that. And so we add the link to an internal stack, which is requested by the function.
	 * When it finds the end marker, the hyperlink object is removed from stack, and so handleText() will continue handling texts normally again.
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xSpan (in/out) XHTML span element.
	 * @param string     $resId (in)     Resource id of the aid element.
	 */
	private function handleHyperlink( $icDoc, $icStory, &$xDoc, &$xSpan, $resId )
	{
		$dbLink = new ICDBHyperlink();
		switch( $dbLink->findHyperlink4ResId( $icDoc, $icStory, $resId ) ) {
			case ICDBHyperlink::MARKER_START: // start marked found?
				if( ($xLink = $dbLink->createHyperlinkXHTML( $icDoc, $icStory, $xDoc ))) {
					$xSpan->appendChild( $xLink );
					$this->hyperLinks->addLink( $dbLink->getStartRC(), $dbLink->getEndRC(), $xLink );
				}
				break;
			case ICDBHyperlink::MARKER_END: // end marker found?
				$this->hyperLinks->removeLink( $dbLink->getStartRC(), $dbLink->getEndRC() );
				break;
		}
	}
	
	/**
	 * Creates an XHTML image, which is used as place holder in XHTML for InCopy Processing Instructions.
	 *
	 * @param DOMDocument      $xDoc (in/out)  XHTML document.
	 * @param DOMNode          $xSpan (in/out) XHTML span element.
	 * @param StoryResourceDef $refObj (in)    Resource object specifying how to create the XHTML image.
	 * @param string           $rcId (in)      Resource id.
	 * @param string           $type (in)      XHTML document.
	 * @return DOMNode XHTML image representing the PI.
	 */
	private function createImageForPI( &$xDoc, &$xSpan, $refObj, $rcId, $type )
	{
		$xImg = $xDoc->createElement( 'img' );
		$xImg->setAttribute( 'src', $refObj->IconPath );
		$xImg->setAttribute( 'id', $rcId );
		$xImg->setAttribute( 'class', $type );
		$xImg->setAttribute( 'width', '16' );
		$xImg->setAttribute( 'title', $refObj->Description );
		$xImg->setAttribute( 'style', 'background: '.$refObj->BackColor );
		$xSpan->appendChild( $xImg );
		return $xImg;
	}

	/**
	 * Creates an XHTML image, which is used as place holder in XHTML for WW Smart Catalog fields.
	 *
	 * @param DOMDocument      $xDoc (in/out)  XHTML document.
	 * @param DOMNode          $xSpan (in/out) XHTML span element.
	 * @param string           $icWWtg (in)    WWtg attribute, such as: WWtg="x_8_c_SCat_c__c_Products_c__c_ _l_ffffffff_l_ffffffff_c_"
	 * @param string           $icText (in)    Generated (non-editable) field text.
	 * @return DOMNode XHTML image representing the SCat field.
	 */
	private function createImageForWWtg( &$xDoc, &$xSpan, $icWWtg, $icText )
	{
		$parts = mb_split( '_', $icWWtg );
		$title = '';
		if( count( $parts ) > 7 ) {
			$title = ( $parts[3] == 'SCat' ) ? 'Smart Catalog: ' : '';
			$title .= $parts[7] . ': ' . $icText;
		}

		$xImg = $xDoc->createElement( 'img' );
		$xImg->setAttribute( 'src', '../../config/images/webeditor/textvar_16.gif' );
		$xImg->setAttribute( 'id', $icWWtg );
		$xImg->setAttribute( 'class', 'WWtg' );
		$xImg->setAttribute( 'width', '16' );
		$xImg->setAttribute( 'alt', $icText );
		$xImg->setAttribute( 'title', $title );
		$xSpan->appendChild( $xImg );
		return $xImg;
	}

	// ---------------------- STYLES ----------------------

	private $defaultpara = null;
	private $defaultchar = null;
	private $stylearray = array();
	private $cssnamesarray = array();

	/**
	 * Parse the InCopy Story element, convert it into a XHTML and add it to the body element.
	 * InCopy path: Stories/Story/SnippetRoot/psty
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xBody (in/out) XHTML body element.
	 * @param string      $stylesCSS (out) Cascade para+char style definition.
	 * @param string      $stylesMap (out) Para+char styles; GUI display name mapped onto CSS name.
	 */
	private function handleStyleElems( $icDoc, $icStory, $xDoc, $xBody, &$stylesCSS, &$stylesMap )
	{
		// Color styles
		$xpath = new DOMXPath( $icDoc );
		$queries = array( 'SnippetRoot/colr' => $icStory,
			'wwst_template/wwst_styles/SnippetRoot/colr' => $icStory->parentNode,
			'wwsd_document/wwsd_styles/SnippetRoot/colr' => $icStory->parentNode );
		foreach( $queries as $query => $icParent ) { // Walk through all color styles
			$entries = $xpath->query( $query, $icParent );
			foreach( $entries as $icColr ) {
				$self = $icColr->getAttribute( 'Self' ); // id
				$self = mb_substr( $self, 3 ); // skip "rc_" prefix
				$this->stylearray['colr'][$self] = $icColr;
			}
		}

		// Paragraph styles
		$xpath = new DOMXPath( $icDoc );
		$queries = array( 'SnippetRoot/psty' => $icStory,
			'wwst_template/wwst_styles/SnippetRoot/psty' => $icStory->parentNode,
			'wwsd_document/wwsd_styles/SnippetRoot/psty' => $icStory->parentNode );
		$storedParaStyles = array();
		foreach( $queries as $query => $icParent ) { // Walk through all paragraph styles
			$entries = $xpath->query( $query, $icParent );
			foreach( $entries as $icPsty ) {
				$name = $icPsty->getAttribute( 'pnam' ); // name
				if( !in_array($name, $storedParaStyles) ) { // Don't add para style when name found on wwst_template/wwsd_document
					$storedParaStyles[] = $name;
					$self = $icPsty->getAttribute( 'Self' ); // id
					$self = mb_substr( $self, 3 ); // skip "rc_" prefix
					$this->stylearray['para'][$self] = $icPsty;
				}
			}
		}
		$this->defaultpara = 'o_'.InCopyUtils::getDefaultParaStyle( $icDoc, $icStory );

		// Character styles
		$xpath = new DOMXPath( $icDoc );
		$queries = array( 'SnippetRoot/csty' => $icStory,
			'wwst_template/wwst_styles/SnippetRoot/csty' => $icStory->parentNode,
			'wwsd_document/wwsd_styles/SnippetRoot/csty' => $icStory->parentNode );
		$storedCharStyles = array();
		foreach( $queries as $query => $icParent ) { // Walk through all character styles
			$entries = $xpath->query( $query, $icParent );
			foreach( $entries as $icCsty ) {
				$name = $icCsty->getAttribute( 'pnam' ); // name
				if( !in_array($name, $storedCharStyles) ) { // Don't add char style when same name found on wwst_templates/wwsd_document
					$storedCharStyles[] = $name;
					$self = $icCsty->getAttribute( 'Self' ); // id
					$self = mb_substr( $self, 3 ); // skip "rc_" prefix
					$this->stylearray['char'][$self] = $icCsty;
				}
			}
		}
		$this->defaultchar = 'o_'.InCopyUtils::getDefaultCharStyle( $icDoc, $icStory );

		// Return styles
		$stylesMap = '';
		$stylesCSS = '';
		$bodyCSS   = '';

		// Paragraph styles
		$stortedMap = array();
		$paras = &$this->getStyles( 'para' );
		foreach( $paras as $name => $style){
			$displayName = $this->cssnamesarray[$name];
			if( $displayName == '[No paragraph style]' ) {
				$displayName = '['.BizResources::localize( 'NO_PARA_STYLE' ).']';
				$stylesMap .= $displayName.'=parao_'.$name.';'; // keep outside stortedMap to let it appear on top of list
			} else {
				if( !array_key_exists($displayName, $stortedMap) ) {
					$stortedMap[$displayName] = $displayName.'=parao_'.$name.';';
				}
			}
			$stylesCSS .= $style;
			if( substr($this->defaultpara, 2) == $name ) {
				$start = strpos($style, '{');
				$end = strpos($style, '}');
				$bodyCSS .= substr($style, $start+1, $end - $start - 1);
				$stylesCSS .= 'p { '. substr($style, $start+1, $end - $start - 1) . ' }'."\n";
			}
		}
		ksort($stortedMap);
		foreach( $stortedMap as $styleMap ) {
			$stylesMap .= $styleMap;
		}

		// Character styles
		$stortedMap = array();
		$chars = &$this->getStyles( 'char' );
		foreach( $chars as $name => $style){
			$displayName = $this->cssnamesarray[$name];
			if( $displayName == '[No character style]' ) {
				$displayName = '['.BizResources::localize( 'NO_CHAR_STYLE' ).']';
				$stylesMap .= $displayName.'=charo_'.$name.';'; // keep outside stortedMap to let it appear on top of list
			} else {
				if( !array_key_exists($displayName, $stortedMap) ) {
					$stortedMap[$displayName] = $displayName.'=charo_'.$name.';';
				}
			}
			$stylesCSS .= $style;
		}
		ksort($stortedMap);
		foreach( $stortedMap as $styleMap ) {
			$stylesMap .= $styleMap;
		}
		$stylesMap = mb_substr($stylesMap, 0, mb_strlen($stylesMap)-1); // remove last ;

		// Overrule default font size for entire tinyMCE text widgets when it outbounds the configured [min...max] size.  (See BZ#6492 and BZ#5515)
		$fontSize = stripos($bodyCSS, 'font-size');
		if( $fontSize !== false ) {
   			$stylesCSS .= 'body.mceContentBody {'.$bodyCSS.' }';
		}
		else {
			$minSize = defined( 'WEB_MIN_FONT_SIZE' ) ? WEB_MIN_FONT_SIZE : 8;  // let's use size index 1 by default
			$maxSize = defined( 'WEB_MAX_FONT_SIZE' ) ? WEB_MAX_FONT_SIZE : 36; // let's use size index 7 by default
			if( $minSize <= $maxSize ) { // valid config?
				$fontSize = 10; // browser default
				if( $minSize > $fontSize ) $fontSize = $minSize;
				if( $maxSize < $fontSize ) $fontSize = $maxSize;
				$bodyCSS .=  'font-size: '.$fontSize.'px';
				$stylesCSS .= 'body.mceContentBody {'.$bodyCSS.' }';
			}
		}
	}

	private function &getStyles( $styleType )
	{
		$styles = array();
		$minSize = defined( 'WEB_MIN_FONT_SIZE' ) ? WEB_MIN_FONT_SIZE : 8;  // let's use size index 1 by default
		$maxSize = defined( 'WEB_MAX_FONT_SIZE' ) ? WEB_MAX_FONT_SIZE : 36; // let's use size index 7 by default
		$maxWhite = 150;
		if( is_array( $this->stylearray[$styleType] )) {
			foreach( $this->stylearray[$styleType] as $styleData ) { // paras, chars, colors
				$css = $this->generateCSS( $styleData, $styleType );
				if( count($css) > 0 ) {
					foreach( $css as $name => $cssvals ) {
						$name = mb_substr( $name, 3 );
						$csstext = '';
						// Handle before-style, typically used for numbered lists in paragraph styles
						if( isset( $cssvals['before'] ) ) {
							$csstext .= '.'.$styleType.'o_'.$name.':before { ';
							foreach( $cssvals['before'] as $type => $value ) {
								$csstext .= $type.': '.$value.'; ';
							}
							$csstext .= '}'."\n";
						}
						// Handle style
						$csstext .= '.'.$styleType.'o_'.$name.' { ';
						foreach( $cssvals as $type => $value ) {
							switch($type){
								case 'before': break; // skip, treated above
								case 'color':
									// Fix font color when too white/bright to show on white background (of TinyMCE text widget)
									$rgb = array( hexdec(substr($value,1,2)), hexdec(substr($value,3,2)), hexdec(substr($value,5,2)) );
									if( $rgb[0] > $maxWhite && $rgb[1] > $maxWhite && $rgb[2] > $maxWhite ) {
										$maxHex = dechex( $maxWhite );
										$value = '#'.$maxHex.$maxHex.$maxHex;
									}
									$csstext .= $type.': '.$value.'; ';
									break;
								case 'text-decoration':
									$csstext .= $type.': ';
									foreach($value as $deco){
										$csstext .= ' '.$deco;
									}
									$csstext .= '; ';
									break;
								case 'font-size':
									if( $minSize <= $maxSize ) { // valid config?
										if( $value < $minSize ) {
											$value = $minSize; // Avoid unreadable characters (when too small)
										} else if( $value > $maxSize ) {
											$value = $maxSize; // Avoid unreadable characters (when too large)
										}
									}
									$csstext .= $type.': '.$value.'px; ';
									break;
								default:
									$csstext .= $type.': '.$value.'; ';
									break;
							}
						}
						$csstext .= '}'."\n";
						$styles[$name] = $csstext;
					}
				}
			}
		}
		return $styles;
	}

	/**
	 * @param DOMNode $icStyle     InCopy para/char style element (psty/csty)
	 * @param string $styleType    Style to generate: 'para' or 'char'
	 * @param array $css
	 * @param string|null $self
	 * @return array
	 */
	private function generateCSS( $icStyle, $styleType, $css = array(), $self = null )
	{
		if( !is_object($icStyle) ) {
			return $css;
		}
		$pnam = $icStyle->getAttribute( 'pnam' ); // name

		if( is_null($self) ) {
			$self = $icStyle->getAttribute( 'Self' ); // id
			if( !empty($self) ) {
				if( !empty($pnam) ) {
					$selfVal = mb_substr( $self, 3 ); // skip "rc_" prefix
					$pnamVal = mb_substr( $pnam, 2 ); // skip "k_" prefix
					$pnamVal = mb_eregi_replace( '~sep~', '_', $pnamVal ); // IC stores ~sep~ for underscores
					$this->cssnamesarray[$selfVal] = $pnamVal;
				}
			}
		}

		// On inheritance, resolve by recursion
		$basd = $icStyle->getAttribute( 'basd' ); // id inherit from
		if( !empty($basd) ) {
			if( $basd == 'k_[No character style]' ) { $basd = $this->defaultchar; } // resolve char id
			if( $basd == 'k_[No paragraph style]' ) { $basd = $this->defaultpara; } // resolve para id
			if( mb_substr($basd, 0, 2) == 'o_' ) {
				$basd = mb_substr( $basd, 2 ); // skip "o_" prefix
				$css = $this->generateCSS( $this->stylearray[$styleType][$basd], $styleType, $css, $self );
			}
		}

		if( !isset($css[$self]) ) { $css[$self] = array(); } // Embody base styles

		/* Commented out: This trick does not work for Safari because cursor can NOT be set into empty paragraphs.
		// Make sure paragraphs have content, or else empty ones don't show up in editor!
		// The CSS2's "before" trick is used to define content, which does NOT take part of the story's content!
		// We insert a non-breaking space in a small font before any paragraph, which does the trick.
		if( $styleType == 'para' ) {
			if( !isset($css[$self]['before']['content']) ) {
				if( !isset($css[$self]['before']) ) $css[$self]['before'] = array();
				$css[$self]['before']['content'] = '"-'.chr(0xC2).chr(0xA0).'"'; // non-breaking space
				$css[$self]['before']['font-size'] = '8px';
			}
		}*/

		// Handle bold/italic
		$value = $icStyle->getAttribute( 'ptfs' );
		if( !empty($value) ) {
			$value = mb_substr( $value, 2 ); // skip "c_" prefix
			if( mb_strpos( $value, ' ') !== false ){
				$css[$self]['font-weight'] = 'bold';
				$css[$self]['font-style'] = 'italic';
			} else if($value == 'Bold') {
				$css[$self]['font-weight'] = 'bold';
			} else if($value == 'Italic') {
				$css[$self]['font-style'] = 'italic';
			}
		}

		// Handle fonts
		$value = $icStyle->getAttribute( 'font' );
		if( !empty($value) ) {
			$value = mb_substr( $value, 2 ); // skip "c_" prefix
			$css[$self]['font-family'] = $value;
		}

		// Handle underline
		$value = $icStyle->getAttribute( 'undr' );
		if( !empty($value) ) {
			if( $value == 'b_t' ) { // set? (b_t => boolean_true)
				$css[$self]['text-decoration'][] = 'underline';
			}
		}

		// Handle striketrough
		$value = $icStyle->getAttribute( 'strk' );
		if( !empty($value) ) {
			if( $value == 'b_t' ) { // set? (b_t => boolean_true)
				$css[$self]['text-decoration'][] = 'line-through';
			}
		}

		// Handle point size
		$value = $icStyle->getAttribute( 'ptsz' );
		if( !empty($value) ) {
			$value = mb_substr( $value, 2 ); // skip "U_" prefix
			$css[$self]['font-size'] = $value;
		}

		// Handle fill-color
		$value = $icStyle->getAttribute( 'flcl' );
		if( !empty($value) ) {
			$value = mb_substr( $value, 2 ); // skip "o_" prefix
			$css[$self]['color'] = $this->getColor( $value );
		}

		//$value = $icStyle->getAttribute( 'stco' ); // striketrough color
		//$value = $icStyle->getAttribute( 'ulco' ); // underline color

		// Handle position (subscr, superscr)
		$value = $icStyle->getAttribute( 'posm' );
		if( !empty($value) ) {
			if($value == 'e_spsc'){
				$css[$self]['vertical-align'] = 'super';
			}
			if($value == 'e_sbsc'){
				$css[$self]['vertical-align'] = 'sub';
			}
		}

		// Handle number- and bullet lists
		$value = $icStyle->getAttribute( 'bnlt' );
		if( !empty($value) ) {
			switch( $value ) {
				case 'e_LTbt': // bullets
					$icNumStyle = $icStyle->getAttribute( 'bnbc' );
					$parts = mb_split( '_', $icNumStyle );
					$icNumStyle = count( $parts ) > 5 ? $parts[5] : 'bb'; // paranoid: default bullets
					// When the character is bound to font familyy[x_2_e_BCgf_l_1f], we change to standard bullet character
					$icNumStyle = ($icNumStyle == 'bb') || ($parts[3] == 'BCgf') ? 'b7' : $icNumStyle; // When it is default bullets, we change it to code 'b7', which is a bullet.
					// The bnbc attribute, usually in this patern, x_2_e_BCuf_l_275a
					// Test against the part[3], if it is BCuf, then it is HTML unicode, we don't utf8 encode it,
					// else the encoding will convert it become device control character, and make the loading failed.
					if($parts[3] == 'BCuf'){
						$icNumStyle = "\\" . $icNumStyle;	// For CSS Style, must put it as \code, eg, \2013, cause &#x2013; will not display as single character
					}
					else {
						$icNumStyle = utf8_encode( chr( hexdec($icNumStyle) ) ); // we can not us entities, text is taken literal
					}
					unset($css[$self]['before']['counter-increment']);
					if( !isset($css[$self]['before']) ) $css[$self]['before'] = array();
					$css[$self]['before']['content'] = '"'.$icNumStyle.' "'; // space after bullet
					break;
				case'e_LTnm': // numbers
					$icNumStyle = $icStyle->getAttribute( 'bnns' );
					switch( $icNumStyle ) {
						case 'k_A, B, C, D...'     : $xNumStyle = 'upper-alpha'; break;
						case 'k_a, b, c, d...'     : $xNumStyle = 'lower-alpha'; break;
						case 'k_I, II, III, IV...' : $xNumStyle = 'upper-roman'; break;
						case 'k_i, ii, iii, iv...' : $xNumStyle = 'lower-roman'; break;
						case 'k_01, 02, 03, 04...' : $xNumStyle = 'decimal-leading-zero'; break;
						case 'k_1, 2, 3, 4...'     :
						default:
							$xNumStyle = 'decimal';
							break;
					}
					if( !isset($css[$self]['before']) ) $css[$self]['before'] = array();
					$css[$self]['before']['counter-increment'] = 'sce_para_cnt';
					$css[$self]['before']['content'] = 'counter(sce_para_cnt, '.$xNumStyle.') " "'; // space after counter
					break;
				case 'e_LTno': // no numbering (could be base, could be overrule!)
					unset($css[$self]['before']['counter-increment']);
					unset($css[$self]['before']['content']);
					break;
			}
		}

		if( $pnam != 'k_[No paragraph style]' ) { // avoid inherited numbering restart for all paras
			// Handle (re)start numbered lists
			$icBncp = $icStyle->getAttribute( 'bncp' ); // continue numbering at this level (boolean)
			if( $icBncp && $icBncp == 'b_f' ) {
				//if( !isset($css[$self]['before']) ) $css[$self]['before'] = array();
				$css[$self]['counter-reset'] = 'sce_para_cnt 0'; // restart (and compensate counter-increment)
			}
			$icBnst = $icStyle->getAttribute( 'bnst' ); // start number at (number)
			if( $icBnst ) {
				$icBnst = substr( $icBnst, 2 ); // skip "l_"
				//if( !isset($css[$self]['before']) ) $css[$self]['before'] = array();
				$icBnst = (int)($icBnst)-1; // restart (and compensate counter-increment)
				$css[$self]['counter-reset'] = 'sce_para_cnt '.$icBnst;
			}
		}
		return $css;
	}

	private function getColor( $colorIdx )
	{
		$icColr = array_key_exists( $colorIdx, $this->stylearray['colr'] ) ? $this->stylearray['colr'][$colorIdx] : null;
		if( is_null($icColr) ){
			LogHandler::Log( 'textconv', 'WARN', 'The color definition (' . $colorIdx . ') is used but not defined in InCopy file.');
		}
		return InCopyUtils::getColorRGB( $icColr );
	}

	/**
	 * Create ordered/unordered list UL/OL element.
	 *
	 * @param DOMNode     $icTxsr (in)    InCopy txsr element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xBody (in/out) XHTML body element.
	 * @param DOMNode     $xPara (in/out) XHTML paragraph element.
	 * @param DOMNode     $xList (in/out) XHTML ul or ol element.
	 */
	private function createListElement( $icTxsr, &$xDoc, &$xBody, &$xPara, &$xList )
	{
		$restart = ($icTxsr->getAttribute( 'bncp' ) == 'b_f') ? true : false; // each numbered list item needs restart numbering?

		$icBnlt = $icTxsr->getAttribute( 'bnlt' );
		if( $icBnlt == 'e_LTnm' ) { // numbered list?
			if( $restart || // restart numbering?
				!$xList || $xList->nodeName != 'ol' ) { // avoid creating new list on para/char style change
				if( $xPara->parentNode ) {	// BZ#17267 - when xPara belong to a node, then only remove it
					$xBody->removeChild($xPara); // Remove Para node, if it is OL/UL node
				}
				$xList = $xDoc->createElement( 'ol' );
				$xBody->appendChild( $xList );
				if( $restart ) $xList->setAttribute( 'start', '1' );
			}
		} else if( $icBnlt == 'e_LTbt' ) { // bullet list
			if( !$xList || $xList->nodeName != 'ul' ) { // avoid creating new list on para/char style change
				$xBody->removeChild($xPara); // Remove Para node, if it is OL/UL node
				$xList = $xDoc->createElement( 'ul' );
				$xBody->appendChild( $xList );
			}
		} else {
			$xList = null; // end list
		}
		if( $xList ) {
			// Make sure we embed all attributes that allows us to set back unhandled attributes on the way back!
			$attrBag = $this->collectTxsrAttrs( $icTxsr );
			if( count($attrBag) > 0 ) $xList->setAttribute( 'id', 'txsr_'.serialize($attrBag) );
		}
	}

	/**
	 * Create ordered/unordered list item LI element.
	 *
	 * @param DOMNode     $icTxsr (in)    InCopy txsr element.
	 * @param DOMDocument $xDoc (in/out)  XHTML document.
	 * @param DOMNode     $xSpan (in/out) XHTML span element.
	 * @param DOMNode     $xList (in/out) XHTML ul or ol element.
	 * @param Boolean	  $xEmptyList	  Previous empty LI.
	 * @param String 	  $key1  		  Number of hard return in one processed text item.
	 */
	private function createListItem( $icTxsr, &$xDoc, &$xSpan, &$xList, &$xEmptyList, $key1 )
	{
		$icPrst = $icTxsr->getAttribute( 'prst' );
		$icCrst = $icTxsr->getAttribute( 'crst' );
		if( $xEmptyList || $key1 > 0 ) { // If previous list is a empty list or in a pcnt got EOL in between, we will create a new li
			$xLI = $xDoc->createElement( 'li' );
			$xLI->setAttribute( 'class', 'para'.$icPrst ); // Set para class on li
			$xSpan = $xDoc->createElement( 'span' );
			$xSpan->setAttribute( 'class', 'char'.$icCrst ); // Set character class on span
			$xLI->appendChild( $xSpan );
			$xList->appendChild( $xLI );
		}
		else {
			// If previous list is not empty, then we need to compare the prst of previous list.
			// If it is having same prst, then we don't create a new li, instead of appending the text to the previous li
			// This is to avoid create multiple li in WE.
			if( $xList->lastChild ) {
				$liChild = $xList->lastChild;
				$liClass = $liChild->getAttribute( 'class' );
				$liClass = substr($liClass, 4);
				if( $liChild->lastChild ) {
					$spanChild = $liChild->lastChild;
					$spanClass = $spanChild->getAttribute( 'class' );
					$spanClass = substr($spanClass, 4);
				}
				if( $icPrst == $liClass ) {
					$xLI 	= $liChild;
					/** @noinspection PhpUndefinedVariableInspection $spanClass
					  * Too complex to make changes here, as setting a default value might already change behaviour. */
					if( $icCrst == $spanClass ) {
						/** @noinspection PhpUndefinedVariableInspection $spanChild
						 * Too complex to make changes here, as setting a default value might already change behaviour. */
						$xSpan = $spanChild;
					}
					else {
						$xSpan = $xDoc->createElement( 'span' );
						$xSpan->setAttribute( 'class', 'char'.$icCrst );
						$xLI->appendChild( $xSpan );
					}
				}
			}
		}
		$xEmptyList = false; // Set to false, so it won't create a new LI
	}

	/**
	 * It doesn't support inline image, so always return false.
	 * See TextImport class for more info.
	 */
	public function enableInlineImageProcessing()
	{
		return false;
	}

	/**
	 * It doesn't support inline image, so always return null.
	 * See TextImport class for more info.
	 */
	public function getInlineImages()
	{
		return null;
	}
}
