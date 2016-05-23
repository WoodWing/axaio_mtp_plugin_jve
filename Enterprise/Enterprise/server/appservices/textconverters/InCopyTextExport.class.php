<?php
/**
 * TextExport class for InCopy (XML) text format used by the Web Editor.
 *
 * Converts from collection of XHTML frames that have been edit in TinMCE frames back into InCopy (XML).
 * See {@link TextExport} interface for more details. 
 *
 * @package Enterprise
 * @subpackage WebEditor
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/TextImportExport.intf.php'; // TextExport
require_once dirname(__FILE__).'/InCopyTextUtils.php';

class InCopyTextExport implements TextExport
{
	private $defaultpara = null;
	private $defaultchar = null;
	private $colors = null;
	private $totalHyperlink = 1;	// Needed in IC Database Hyperlink structure.
	public  $ihcPlugin = false; 	 // InCopyHTMLConversion plugin: if this flag is set to true, it triggers alternate behavior
	public 	$ihcPluginReplaceVersionGuid = true; // If elements have a proper GUID then the GUID must be re-used.

	/**
	 * Convert a collection of XHTML frames into a file in InCopy (XML) text format. <br>
	 *
	 * @param array  $xFrames    Collection of XHTML DOMDocument each representing one text frame. <br>
	 * @param string $icFile     Full path of file to be written with InCopy (XML) text. <br>
	 * @param boolean $draft     Perform draft save (false for permantent save). Keeps embedded object definitions to allow undo operations. <br>
	 */ 
	public function exportFile( $xFrames, $icFile, $draft )
	{
		LogHandler::Log( 'textconv', 'INFO', 'InCopyFileExport->export(): Reading InCopy Text from ['.$icFile.']');
		$icDoc = new DOMDocument();
		$icDoc->loadXML( file_get_contents( $icFile ) ); // URL encoded paths fail: $icDoc->load( $icFile ); (BZ#6561)

		LogHandler::Log( 'textconv', 'INFO', 'InCopyFileExport->export(): Converting InCopy to HTML for ['.$icFile.']');
		$this->exportBuf( $xFrames, $icDoc, $draft );

		// >>> BZ#13734 
		/*// Update version GUID to trigger text updates opening layouts with placed articles.
		LogHandler::Log( 'textconv', 'INFO', 'InCopyFileExport->export(): Updating version GUIDs for file ['.$icFile.']');
		$xpath = new DOMXPath($icDoc);
		$entries = $xpath->query( '//Stories/Story/SnippetRoot/cflo' );
		foreach ($entries as $icCflo) {
			$icCfloGUID = new ICDBToken( $icCflo->getAttribute('GUID') );
			if( strlen($icCfloGUID->value) > 0 ) { // Skip inline text! Inline text frames have attribute GUID="k_" (type, but no value)
				// Update Story->SnipptRoot->cflo->VeGU with new version GUID (for Adobe)
				$icCfloVegu = new ICDBToken( $icCflo->getAttribute('VeGU') );
				$newVerGUID = InCopyUtils::createGUID();
				LogHandler::Log( 'textconv', 'DEBUG', 'InCopyFileExport->export(): Updating cflo->VeGU version GUID ['
								.$icCfloGUID->value.'] with new GUID ['.$newVerGUID.'] for story.');
				$icCfloVegu->value = $newVerGUID;
				$icCflo->setAttribute( 'VeGU', $icCfloVegu->toString() );
				// Update the Story->StoryInfo->SI_Version with new version GUID (for WoodWing)
				$icStory = $icCflo->parentNode->parentNode;
				$icVerTxt = $xpath->query( 'StoryInfo/SI_Version/text()', $icStory );
				if( $icVerTxt->length > 0 ) {
					LogHandler::Log( 'textconv', 'DEBUG', 'InCopyFileExport->export(): Updating StoryInfo->SI_Version version GUID ['
									.$icVerTxt->item(0)->textContent.'] with new GUID ['.$newVerGUID.'] for story.');
					$icNewText = $icDoc->createTextNode( $newVerGUID );
					$icVerTxt->item(0)->parentNode->replaceChild( $icNewText, $icVerTxt->item(0) );
				}
			}
		}*/ // <<<

//		$icFile .= '.out.wwcx'; // TODO: Remove debug extension
		LogHandler::Log( 'textconv', 'INFO', 'InCopyFileExport->export(): Saving InCopy Text to ['.$icFile.']');
		// please note, we urlencode the article file for special character support
		// when InDesign Server WW plugins search for the updated Article file

		$icDoc->save( urlencode($icFile) );
		
		// Format txsr and pcnt elements to make them comparable for debugging
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/utils/FileHandler.class.php';
			$fh = new FileHandler( $icFile );
			$fh->readFile();
			$contents = $fh->getFileContent();
			$contents = str_replace( '<txsr', "\r\n\t<txsr", $contents );
			$contents = str_replace( '</txsr>', "\r\n\t</txsr>", $contents );
			$contents = str_replace( '<pcnt', "\r\n\t\t<pcnt", $contents );
			$fh->writeFile( $icFile, $contents );
		}
	}
	
	/**
	 * Convert a collection of XHTML frames to a memory buffer in InCopy (XML) text format. <br>
	 *
	 * @param array  $xFrames     Collection of XHTML DOMDocument each representing one text frame. <br>
	 * @param DOMDocument $icDoc  Returned InCopy (XML) document. <br>
	 * @param boolean $draft      Perform draft save (false for permantent save). Keeps embedded object definitions to allow undo operations. <br>
	 */ 
	public function exportBuf( $xFrames, &$icDoc, $draft )
	{
		// Collect cflo elements used as anchor points to replace IC- with HTML text elements 
		$xpath = new DOMXPath($icDoc);
		$cfloQuery = '//Stories/Story/SnippetRoot/cflo';
		$icCflos = $xpath->query( $cfloQuery );
		if( $icCflos->length == 0 ) {
			$cfloQuery = '//wwst_template/wwst_stories/SnippetRoot/cflo'; // Try ww template format
			$icCflos = $xpath->query( $cfloQuery );
		}

		// Remove all txsr elements from all stories
		//$icInsPoint = null; // insertion point; successor of last removed txsr elem
		// $this->removeAllTxsrs( $icDoc, $icCflos ); // BZ#13734 Performance improvement: 15 seconds faster if we only update changed frames

		// Translate HTML elements into IC elements and add them to the IC doc
		$icDBRefIds = array();
		$this->colors = array(); // Keep outside loop to share colors with other stories avoiding adding new colors that are the same but might get different rc ids!
		foreach( $xFrames as $xFrameID => $xFrame ) {

			// Determine the IC frame (cflo) that matches the XHTML frame
			if( is_numeric($xFrameID) ) { // old dangerous way, only for backwards compat.
				$icCflo = $icCflos->item( $xFrameID );
			} else { // assumed is that $xFrameID is the frame GUID
				// Not all text frames can be synced. There could be inline frames (BZ#11869) that should be ignored.
				// (And, there could be new frames from Web Editor in future.)
				if( !$xFrameID || strlen($xFrameID) == 0 ) {
					LogHandler::Log( 'textconv', 'ERROR', 'Found frame without ID. Its text could NOT be saved!' );
					continue; 
				}
				$xpath = new DOMXPath($icDoc);
				$icCflos = $xpath->query( $cfloQuery.'[@GUID="c_'.$xFrameID.'"]' );
				if( $icCflos->length == 0 ) { // when c_ prefix failed, try k_
					$icCflos = $xpath->query( $cfloQuery.'[@GUID="k_'.$xFrameID.'"]' );
				}
				if( $icCflos->length == 0 ) {
					LogHandler::Log( 'textconv', 'ERROR', 'Frame ['.$xFrameID.'] could not be found. Its text could NOT be saved!' );
					continue; // Frame not found in IC. ( => Here we could build feature that allows adding frames from Web Editor!)
				}
				$icCflo = $icCflos->item(0);
			}

			// >>> BZ#13734 
			$icCfloGUID = new ICDBToken( $icCflo->getAttribute('GUID') );
			if( strlen($icCfloGUID->value) > 0 ) { // Skip inline text! Inline text frames have attribute GUID="k_" (type, but no value)
				// Only remove IC text (txrs elements) from matching frames (XHTML) that are actually changed by user
				$xpath = new DOMXPath($icDoc);
				$icTxsrs = $xpath->query( 'txsr', $icCflo );
				foreach( $icTxsrs as $icTxsr ) {
					$icCflo->removeChild( $icTxsr );
				}
				// Update Story->SnipptRoot->cflo->VeGU with new version GUID (for Adobe)
				$icCfloVegu = new ICDBToken( $icCflo->getAttribute('VeGU') );
				if (! $this->ihcPlugin || ($this->ihcPlugin && $this->ihcPluginReplaceVersionGuid)) { // No CS article or CS article without version GUID
					$newVerGUID = InCopyUtils::createGUID();
					LogHandler::Log('textconv', 'DEBUG', 'InCopyFileExport->export(): Updating cflo->VeGU version GUID [' . $icCfloGUID->value . '] with new GUID [' . $newVerGUID . '] for story.');
					$icCfloVegu->value = $newVerGUID;
				}
				else { // CS article with version GUID
					$icOrgVegu = $xpath->query('StoryInfo/SI_Version/text()', $icCflo->parentNode->parentNode);	
					$icCfloVegu->value = $icOrgVegu->item(0)->wholeText; // Use version of CS article element
				}
				$icCflo->setAttribute( 'VeGU', $icCfloVegu->toString() );
				// Update the Story->StoryInfo->SI_Version with new version GUID (for WoodWing)
				$icStory = $icCflo->parentNode->parentNode;
				$icVerTxt = $xpath->query( 'StoryInfo/SI_Version/text()', $icStory );
				if( $icVerTxt->length > 0 ) {
					if (! $this->ihcPlugin || ($this->ihcPlugin && $this->ihcPluginReplaceVersionGuid)) { // No CS article or CS article without version GUID
						LogHandler::Log('textconv', 'DEBUG', 'InCopyFileExport->export(): Updating StoryInfo->SI_Version version GUID [' . $icVerTxt->item(0)->textContent . '] with new GUID [' . $newVerGUID . '] for story.');
						$icNewText = $icDoc->createTextNode($newVerGUID);
						$icVerTxt->item(0)->parentNode->replaceChild($icNewText, $icVerTxt->item(0));
					}
				}
			} // <<<

			// Determine default styles
			$icInsPoint = $icCflo->lastChild;
			$icStory = $icCflo->parentNode->parentNode;
			$this->defaultpara = InCopyUtils::getDefaultParaStyle( $icDoc, $icStory );
			$this->defaultchar = InCopyUtils::getDefaultCharStyle( $icDoc, $icStory );

			// Color styles
			$xpath = new DOMXPath( $icDoc );
			$query = 'SnippetRoot/colr'; // Walk through color styles (swatches?)
			$entries = $xpath->query( $query, $icStory );
			foreach( $entries as $icColr ) {
				$self = $icColr->getAttribute( 'Self' ); // id
				$self = mb_substr( $self, 3 ); // skip "rc_" prefix
				$this->colors[$self] = InCopyUtils::getColorRGB( $icColr );
			}

			// Count total XHTML paras for story
			$xpath = new DOMXPath( $xFrame );
			$query = '//p'; 
			$xParas = $xpath->query( $query );
			
			// Count total XHTML OL/UL paras for story
			$query = '//ol'; 
			$xOLs = $xpath->query( $query );
			$query = '//ul'; 
			$xULs = $xpath->query( $query );
			
			// Prepare structure to keep track of EOLs
			$trackEOL = new stdClass();
			$trackEOL->totalParas = $xParas->length + $xOLs->length + $xULs->length;
			$trackEOL->parsedParas = 0;
			$trackEOL->lastWasEOL = false;
			$iterTxsrAttr = array();

			// Merge typed story content (XHTML) into InCopy DB file
			$icTxsr = null; $icPcnt = null;
			$xpath = new DOMXPath( $xFrame );
			$query = '//body';
			$xBodies = $xpath->query( $query );
			foreach( $xBodies as $xBody ) {
				$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xBody, $trackEOL, $icDBRefIds );
			}
		}
		// Keep embedded object definitions for Save Draft*, but remove unreferenced onces in Save Version or Check In.
		// * Removing defintions for Save Draft would make it impossible to undo table/note/etc deletions!
		if( !$draft ) { 
			$this->deleteUnreferencedObjects( $icDoc, $icDBRefIds );
		}
	}
	
	/**
	 * Remove embedded IC DB objects, which are no longer unreferenced (-> user has removed icon/anchor from text)
	 *
	 * @param DOMDocument $icDoc (in)  InCopy document. <br/>
	 * @param array  $icDBRefIds (in)  List of used references (ids) from text (icons/anchors) to embedded IC DB objects. <br/>
	 */
	private function deleteUnreferencedObjects( $icDoc, $icDBRefIds )
	{
		$elemNames = array( 'ctbl', 'crec', 'Note', 'FNcl', 'TVc3', 'txtf', 'glin', 'Push', 'cpgn', 'covl', 'hitx' );
		$xpath = new DOMXPath( $icDoc );
		$query = '//Stories/Story/SnippetRoot/cflo/*/@STof'; // all <cflo> childs that have a "STof" attribute
		$entries = $xpath->query( $query );
		foreach( $entries as $icNode ) {
			$icNode = $icNode->parentNode;
			if( in_array( $icNode->nodeName, $elemNames ) ) { // supported node?
				$icSTof = $icNode->getAttribute( 'STof' );
				$elemId = mb_substr( $icSTof, 3 ); // skip 'ro_' prefix
				$multiKey = mb_split( ':', $elemId ); 
				if( count( $multiKey ) > 1  ) { // double key? (startkey:endkey) -> typically used for tracked changes
					if( !array_key_exists( $multiKey[0], $icDBRefIds ) && !array_key_exists( $multiKey[1], $icDBRefIds ) ) { // unreferenced node?
						$icNode->parentNode->removeChild( $icNode );
					}
				} else {
					if( !array_key_exists( $elemId, $icDBRefIds ) ) { // unreferenced node?
						if( $icNode->nodeName == 'txtf' ) { // BZ#18836 - additional unreference node need to be remove if inline text element
							$icStrp = $icNode->getAttribute( 'strp' );
							$strpId = mb_substr( $icStrp, 3 );
							$query = '//Stories/Story/SnippetRoot/cflo[@Self="rc_'.$strpId.'"]';
							$entry = $xpath->query( $query );
							if( $entry->length > 0 ) {
								$cfloNode = $entry->item(0);
								$cfloNode->parentNode->removeChild( $cfloNode );
							}
						}
						$icNode->parentNode->removeChild( $icNode );						
					}
				}
			}
		}
	}
	
	/**
	 * Creates an XHTML elements inside a paragraph into IC (XML) text format. <br>
	 *
	 * @param DOMDocument $icDoc (in)    InCopy document. <br>
	 * @param DOMNode $icCflo (in)       InCopy cflo element (=> root of story contents). <br>
	 * @param DOMNode $icInsPoint (in)   Insertion point to add InCopy txsr elements <br>
	 * @param array   $iterTxsrAttr (in) List of collected attributes that needs to be applied to the new icTxsr <br>
	 * @param DOMNode $icTxsr (in/out)   Current InCopy txsr element to reuse. Pass null to create new one. <br>
	 * @param DOMNode $icPcnt (in/out)   Current InCopy pcnt element to reuse. Pass null to create new one. <br>
	 * @param DOMNode $xFrame (in)       XHTML document (text frame) being parsed. <br>
	 * @param DOMNode $xParent (in)      XHTML parent element being parsed. <br>
	 * @param DOMNode $trackEOL (in/out) Tells if the last parsed element was a hard enter (EOL). <br>
	 * @param array  $icDBRefIds (out)   List of used references (ids) from text (icons/anchors) to embedded IC DB objects. <br/>
	 */
	private function handleStory( &$icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, &$icTxsr, &$icPcnt, $xFrame, $xParent, &$trackEOL, &$icDBRefIds )
	{
		// Walk through para/char-styled text fragments, fonts, colors, lists, soft breaks, symbols, bold/italic/underline/strike, super-/sub-scripts and text chunks
		$xpath = new DOMXPath( $xFrame );
		//$query = 'p|span|font|ol|ul|li|br|img|strong|b|em|i|strike|u|sup|sub|text()'; 
		$query = '*|text()'; // all child elements including text nodes
		$xElements = $xpath->query( $query, $xParent );
		// Hardcoded the ol/ul indent attributes, else the indent size will be larger/bigger
		$xIndentAttribs = serialize(array('inbl' => 'U_18', 'infl' => 'U_-18'));
		foreach( $xElements as $key => $xElem ) {
			$trackEOL->lastWasEOL = false;
			switch( $xElem->nodeName ) {
				case '#text': // text element
					$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, false );
					$txtPrefix = !$icPcnt->hasChildNodes() ? 'c_' : ''; // new pcnt must start with c_ for any text inside
					// Don't create textnode if the text fall under 'BODY'/'UL'/'OL', else it will generate extra line
					if($xElem->parentNode && ($xElem->parentNode->nodeName == 'ol' || $xElem->parentNode->nodeName == 'ul')) {
					}
					elseif($xElem->parentNode && ($xElem->parentNode->nodeName == 'ol' || $xElem->parentNode->nodeName == 'ul') && $xElem->nextSibling && $xElem->parentNode->nextSibling &&
					   	  ($xElem->parentNode->nextSibling->nodeName != 'ol' && $xElem->parentNode->nextSibling->nodeName != 'ul' && $xElem->parentNode->nextSibling->nodeName != 'p') ) {
					}
					elseif($xElem->parentNode && $xElem->parentNode->nodeName == 'body' && $xElem->nextSibling) {
					}
					elseif(($xElem->nextSibling && $xElem->nextSibling->nodeName == 'div') ) {
					}
					else {
					//$txtContent = str_replace( chr(0xC2).chr(0xA0), IC_HARD_EOL, $xElem->textContent ); // &nbsp; = C2A0 = &#160;
					//$icText = $icDoc->createTextNode( $txtPrefix.$txtContent );
					$icText = $icDoc->createTextNode( $txtPrefix.$xElem->textContent );
					$icPcnt->appendChild( $icText );
					//if( ($lastBytes = substr( $txtContent, -3 )) ) { // remember when last char was EOL
					//	$trackEOL->lastWasEOL = ($lastBytes == IC_HARD_EOL);
					//}
					}
					break;
				case 'p': // paragraph element
					//$icTxsr = null; $icPcnt = null; // force create new txsr+pcnt
					$trackEOL->lastWasEOL = false;
					$class = substr( $xElem->getAttribute( 'class' ), 4 ); // skip "para" prefix
					$class = $this->resolveStyleDef( $icDoc, $icCflo->parentNode->parentNode, 'prst', $class ) ? $class : null;
					if( $class ) $orgProp1 = $this->pushTxsrAttr( $iterTxsrAttr, 'prst', $class ); 
					$style = $xElem->getAttribute('style');
					if( $style ) $xStyleProps = $this->pushTsxrStyleAttrs( $icDoc, $icCflo, $style, $iterTxsrAttr );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					
					// BZ#8034 - After handlestory, if the pcnt still null, then create new pcnt that hold the paragraph
					if(!$icPcnt) {
						$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, false );
					}
					
					// Empty icPcnt elements are not valid; There should be c_ or else ID/IC/IDServer crashses.
					if( !$icPcnt->hasChildNodes() ) { 
						$icText = $icDoc->createTextNode( 'c_' );
						$icPcnt->appendChild( $icText );
					}
					
					// Paras are separated with hard enters, except:
					// 1) after last para -> or else the document 'grows' each save (enter at end)
					// 2) when the last elem was EOL -> e.g. "...{hard}</span></p>" has already a break to next para
					$trackEOL->parsedParas += 1;
					if( $trackEOL->parsedParas < $trackEOL->totalParas && !$trackEOL->lastWasEOL) {
						$icText = $icDoc->createTextNode( IC_HARD_EOL );
						$icPcnt->appendChild( $icText );
					}
					
					// InCopyHTMLConversion plugin: fix IC paragraphs to look like HTML paragraphs
					// except for the last paragraph
					/* BZ#17826 - This actually create extra line, for each paragraph
					if( $trackEOL->parsedParas < $trackEOL->totalParas && $this->ihcPlugin ) {
						$icText = $icDoc->createTextNode( IC_HARD_EOL );
						$icPcnt->appendChild( $icText );
					}
					*/

					// BZ#17827 - Do not pop the last paragraph for Content Station article
					if( !$this->ihcPlugin || ($this->ihcPlugin && $trackEOL->parsedParas < $trackEOL->totalParas) ) {
						if( $style ) $this->popTxsrAttrs( $iterTxsrAttr, $xStyleProps );
						if( $class ) $this->popTxsrAttr( $iterTxsrAttr, $orgProp1 );
					}
					break;
				case 'span': // span element, used for character styles
					//if( $icPcnt && !$icPcnt->hasChildNodes() ) $icPcnt->parentNode->removeChild( $icPcnt ); // avoid empty <pcnt/> elements
					//$icPcnt = null; // force create new pcnt (but keep same txsr)
					//$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );
					$id = $xElem->getAttribute( 'id' ); // check "txsr_" prefix
					$id = substr( $id, 0, 5 ) == 'txsr_' ? substr( $id, 5 ) : null;
					if( $id ) $unhandTxsrAttrs = $this->pushTsxrAttrs( unserialize( $id ), $iterTxsrAttr );
					$class = substr( $xElem->getAttribute( 'class' ), 4 ); // skip "char" prefix
					$class = $this->resolveStyleDef( $icDoc, $icCflo->parentNode->parentNode, 'crst', $class ) ? $class : null;
					if( $class ) $orgProp1 = $this->pushTxsrAttr( $iterTxsrAttr, 'crst', $class ); 
					$style = $xElem->getAttribute('style');
					if( $style ) $xStyleProps = $this->pushTsxrStyleAttrs( $icDoc, $icCflo, $style, $iterTxsrAttr );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					if( $style ) $this->popTxsrAttrs( $iterTxsrAttr, $xStyleProps );
					if( $class ) $this->popTxsrAttr( $iterTxsrAttr, $orgProp1 );
					if( $id ) $this->popTxsrAttrs( $iterTxsrAttr, $unhandTxsrAttrs );
					break;
				case 'ol': // numbered list
					$id = $xElem->getAttribute( 'id' ); // check "txsr_" prefix
					$id = substr( $id, 0, 5 ) == 'txsr_' ? substr( $id, 5 ) : $xIndentAttribs;
					$unhandTxsrAttrs = $this->pushTsxrAttrs( unserialize( $id ), $iterTxsrAttr );
					$orgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'bnlt', 'e_LTnm' );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$this->popTxsrAttr( $iterTxsrAttr, $orgProp );
					$this->popTxsrAttrs( $iterTxsrAttr, $unhandTxsrAttrs );
					$trackEOL->parsedParas += 1;
					break;
				case 'ul': // bullet list
					$id = $xElem->getAttribute( 'id' ); // check "txsr_" prefix
					$id = substr( $id, 0, 5 ) == 'txsr_' ? substr( $id, 5 ) : $xIndentAttribs;
					$unhandTxsrAttrs = $this->pushTsxrAttrs( unserialize( $id ), $iterTxsrAttr );
					$xOrgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'bnlt', 'e_LTbt' );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$this->popTxsrAttr( $iterTxsrAttr, $xOrgProp );
					$this->popTxsrAttrs( $iterTxsrAttr, $unhandTxsrAttrs );
					$trackEOL->parsedParas += 1;
					break;
				case 'li': // list item (bullet or number)
					$firstNumChild = $xElem->parentNode && $xElem->parentNode->nodeName == 'ol' && !$xElem->previousSibling;
					$orgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'bncp', $firstNumChild ? 'b_f' : 'b_t' ); // it's easier to use 'b_t' than to remove 'b_f' (like IC does)
					$class = substr( $xElem->getAttribute( 'class' ), 4 ); // skip "para" prefix
					$class = $this->resolveStyleDef( $icDoc, $icCflo->parentNode->parentNode, 'prst', $class ) ? $class : null;
					if( !$class ) $class = 'o_' . $this->defaultpara;
					$orgProp1 = $this->pushTxsrAttr( $iterTxsrAttr, 'prst', $class ); 
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					if( ($xElem->nextSibling && $xElem->nextSibling->nodeName == 'li') ||  // If it is last LI, don't create EOL, else extra line generated
						($xElem->nextSibling && $xElem->nextSibling->nodeName == '#text') ||
					    ($xElem->parentNode->nextSibling && ( $xElem->parentNode->nextSibling->nodeName == 'ol' || $xElem->parentNode->nextSibling->nodeName == 'ul' || $xElem->parentNode->nextSibling->nodeName == 'p' ) ) ) {
					    $txtPrefix = !$icPcnt->hasChildNodes() ? 'c_' : '';
						$icText = $icDoc->createTextNode( $txtPrefix . IC_HARD_EOL );
						$icPcnt->appendChild( $icText );
					}
					$this->popTxsrAttr( $iterTxsrAttr, $orgProp );
					break;
				case 'strong': // bold
				case 'b': // might happen for copy&paste texts?
					$biVal = array_key_exists( 'ptfs', $iterTxsrAttr ) && $iterTxsrAttr['ptfs'] == 'c_Italic' ? 'c_Bold Italic' : 'c_Bold';
					$xOrgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'ptfs', $biVal );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$this->popTxsrAttr( $iterTxsrAttr, $xOrgProp );
					break;
				case 'em': // italic
				case 'i': // might happen for copy&paste texts?
					$biVal = array_key_exists( 'ptfs', $iterTxsrAttr ) && $iterTxsrAttr['ptfs'] == 'c_Bold' ? 'c_Bold Italic' : 'c_Italic';
					$xOrgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'ptfs', 'c_Italic' );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$this->popTxsrAttr( $iterTxsrAttr, $xOrgProp );
					break;
				case 'strike': // strike-through
					$xOrgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'strk', 'b_t' );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$this->popTxsrAttr( $iterTxsrAttr, $xOrgProp );
					break;
				case 'u': // underline
					$xOrgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'undr', 'b_t' );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$this->popTxsrAttr( $iterTxsrAttr, $xOrgProp );
					break;
				case 'font': // font (face, size and colors)
					$face = $xElem->getAttribute('face');
					$color = $xElem->getAttribute('color');
					if( $color ) $color = $this->findColorRef( $icDoc, $icCflo->parentNode, $color );
					$style = $xElem->getAttribute('style');
					if( $face ) $xOrgProp1 = $this->pushTxsrAttr( $iterTxsrAttr, 'font', 'c_'.$face );
					if( $color ) $xOrgProp2 = $this->pushTxsrAttr( $iterTxsrAttr, 'flcl', $color );
					if( $style ) $xStyleProps = $this->pushTsxrStyleAttrs( $icDoc, $icCflo, $style, $iterTxsrAttr );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					if( $style ) $this->popTxsrAttrs( $iterTxsrAttr, $xStyleProps );
					if( $color ) $this->popTxsrAttr( $iterTxsrAttr, $xOrgProp2 );
					if( $face  ) $this->popTxsrAttr( $iterTxsrAttr, $xOrgProp1 );
					break;
				case 'sup': // superscript
					$xOrgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'posm', 'e_spsc' );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$this->popTxsrAttr( $iterTxsrAttr, $xOrgProp );
					break;
				case 'sub': // subscript
					$xOrgProp = $this->pushTxsrAttr( $iterTxsrAttr, 'posm', 'e_sbsc' );
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$this->popTxsrAttr( $iterTxsrAttr, $xOrgProp );
					break;
				case 'br': // break element, used for soft EOL
					$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, false );
					$txtPrefix = !$icPcnt->hasChildNodes() ? 'c_' : ''; // new pcnt must start with c_ for any text inside
					$txtContent = ($key < ($xElements->length-1)) ? IC_SOFT_EOL : ''; // ignore soft break at end of para -> </br></span></p>
					$icText = $icDoc->createTextNode( $txtPrefix.$txtContent );
					$icPcnt->appendChild( $icText );
					break;
				case 'img': // image element, used for synbols and embedded objects
					switch( $xElem->getAttribute( 'class' ) ) {
						case 'aid':
							// force create new txsr and pcnt (or else IC won't recognize e.g. inline notes)
							$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );

							// Text Variables consist of e_SPtv (followed by two aid instructions)
							if( strstr( $xElem->getAttribute( 'src' ), '/images/webeditor/textvar_16.gif' ) !== false ) {
								$icText = $icDoc->createTextNode( 'e_SPtv' );
								$icPcnt->appendChild( $icText );
							}
							
							// New pcnt must start with c_ for any text inside
							if( !$icPcnt->hasChildNodes() ) { 
								$icText = $icDoc->createTextNode( 'c_' );
								$icPcnt->appendChild( $icText );
							}
							
							// Return used referenced IC DB embedded object ids
							$idInfo = explode( ':', $xElem->getAttribute( 'id' ) );
							$resId = $idInfo[0]; // includes 'rc_' prefix
							$char = count($idInfo) > 1 ? $idInfo[1] : '';
							$icDBRefIds[ substr($resId,3) ] = true; // skip 'rc_'

							// Create the aid instruction
							$icAidPI = $icDoc->createProcessingInstruction( 'aid', 'Char="'.$char.'" Self="'.$resId.'"' );
							$icPcnt->appendChild( $icAidPI );
							
							// Footnotes consist of aid followed by ACE instructions
							if( strstr( $xElem->getAttribute( 'src' ), '/images/webeditor/footnote_16.gif' ) !== false ) {
								$icAcePI = $icDoc->createProcessingInstruction( 'ACE', '4' );
								$icPcnt->appendChild( $icAcePI );
							}

							// Text Variables consist of two aid instructions (preceeded by e_SPtv)
							if( strstr( $xElem->getAttribute( 'src' ), '/images/webeditor/textvar_16.gif' ) !== false ) {
								$rcId2 = substr( $resId, -2 ); // take last two hex chars
								$rcId2 = dechex( hexdec( $rcId2 ) + 1 ); // add 1 in hex
								$rcId2 = substr( $resId, 0, -2 ).$rcId2; // replace last two hex chars
								$icAidPI = $icDoc->createProcessingInstruction( 'aid', 'Self="'.$rcId2.'"' );
								$icPcnt->appendChild( $icAidPI );
							}
							// If it is under li node, we don't create a new txsr, else c_ won't append, and lead to crash
							$liParent = null;
							if( $xElem->parentNode->parentNode->nodeName == 'li' ) {
								$liParent = true;
							}
							if( !$liParent ) {
								// close txsr to force create new one for any siblings (or else e.g. a hard enter disturbs IC recognizing a Text Variable)
								$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );
							}
							break;
						case 'mceAdobeChar': // InCopy symbol and ace objects
							$icSymb = $xElem->getAttribute( 'id' );
							$icACEs = array( 'AC_OTHER_ENDNESTEDSTYLE' => '3', 'AC_MARKER_FOOTNOTE' => '4', 'AC_OTHER_INDENTTOHERE' => '7', 'AC_OTHER_RIGHTINDENTTAB' => '8', 'AC_MARKER_SECTION' => '19' );
							$aceKey = array_key_exists( $icSymb, $icACEs );
							$icSymbols = array( 'AC_BREAK_COLUMN' => 'SClB', 'AC_BREAK_FRAME' => 'SFrB', 'AC_BREAK_PAGE' => 'SPgB', 'AC_MARKER_CURRENTPAGE' => 'SApn', 'AC_MARKER_NEXTPAGE' => 'SNpn', 'AC_MARKER_PREVIOUSPAGE' => 'SPpn', 'AC_BREAK_ODDPAGE' => 'SOpB', 'AC_BREAK_EVENPAGE' => 'SEpB' );								
							$symbKey = array_key_exists( $icSymb, $icSymbols );
							$icTabs = array( 'AC_OTHER_TAB' => chr(0x09) );
							$tabKey = array_key_exists( $icSymb, $icTabs );
							// ACE
							if( $aceKey ) {
								// force create new txsr and pcnt (or else IC won't recognize e.g. inline notes)
								$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );
								if( !$icPcnt->hasChildNodes() ) { // new pcnt must start with c_ for any text inside
									$icText = $icDoc->createTextNode( 'c_' );
									$icPcnt->appendChild( $icText );
								}
								$icAcePI = $icDoc->createProcessingInstruction( 'ACE', $icACEs[$icSymb] );
								$icPcnt->appendChild( $icAcePI );
							}
							// Symbols	
							elseif( $symbKey ) {
								$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );								
								$icText = $icDoc->createTextNode( 'e_'.$icSymbols[$icSymb] );
								$icPcnt->appendChild( $icText );

								// Its not allowed to put EOL after symbol, so we do at begin of next text chunk
								$icBreaks = array( 'AC_BREAK_COLUMN' => 'SClB', 'AC_BREAK_FRAME' => 'SFrB', 'AC_BREAK_PAGE' => 'SPgB', 'AC_BREAK_ODDPAGE' => 'SOpB', 'AC_BREAK_EVENPAGE' => 'SEpB' );
								$isBreakSymb = array_key_exists( $icSymb, $icBreaks );
								$trackEOL->lastWasEOL = true; // BZ#18837 - avoid caller adding EOL since we do here

								$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );
								if( !$isBreakSymb ) { // BZ#6300
									$icText = $icDoc->createTextNode( 'c_'.IC_HARD_EOL );
									$icPcnt->appendChild( $icText );
								}
							}
							// Tab and Paragraph Return
							elseif( $tabKey ) {
								$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );								
								$icText = $icDoc->createTextNode( 'c_'.$icTabs[$icSymb] );
								$icPcnt->appendChild( $icText );
							}
							break;							
						case 'WWtg': // WW tag, typically used for Smart Catalog fields
							// force create new txsr and pcnt
							$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );
							$xWWtg = $xElem->getAttribute( 'id' );
							$icTxsr->setAttribute( 'WWtg', $xWWtg  );
							$xAlt = $xElem->getAttribute( 'alt' );
							$icText = $icDoc->createTextNode( 'c_'. $xAlt );
							$icPcnt->appendChild( $icText );
							// close txsr to force create new one for any siblings (or else e.g. a hard enter disturbs IC recognizing a Text Variable)
							$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );
							break;
						default: // TODO: handle placed image?
							break;
					}
					break;
				case 'td': // tables are not supported, but let's do primitive formatting
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$txtPrefix = !$icPcnt->hasChildNodes() ? 'c_' : ''; // new pcnt must start with c_ for any text inside
					$icText = $icDoc->createTextNode( $txtPrefix.chr(0x09) ); // tab
					$icPcnt->appendChild( $icText );
					break;
				case 'tr': // tables are not supported, but let's do primitive formatting
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					$txtPrefix = !$icPcnt->hasChildNodes() ? 'c_' : ''; // new pcnt must start with c_ for any text inside
					$icText = $icDoc->createTextNode( $txtPrefix.IC_HARD_EOL );
					$icPcnt->appendChild( $icText );
					break;
				case 'a': // handle hyperlinks
					$dbLink = new ICDBHyperlink();
					if( !$dbLink->findHyperlink4Href( $icDoc, $icCflo->parentNode->parentNode, $xElem ) &&
						$xElem->getAttribute('href') ){
						// need to create hyperlink structure into InCopy DB based on the ResId generated earlier.
						$dbLink->createHyperlink4Href( $icDoc, $icCflo->parentNode->parentNode, $xElem, $this->totalHyperlink );
					}
					if( $dbLink->findHyperlink4Href( $icDoc, $icCflo->parentNode->parentNode, $xElem ) ) {
						// create new txsr and add c_ text followed by process instruction with start marker for hyperlink
						$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );
						$icPcnt->appendChild( $icDoc->createTextNode( 'c_' ) );
						$icPcnt->appendChild( $dbLink->createStartPI( $icDoc ) );
						// go into recursion to handle hyperlink content, which can also be marked up...!
						$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
						// create process instruction with end marker for hyperlink and start new txsr for any other following content
						$icPcnt->appendChild( $dbLink->createEndPI( $icDoc ) );
						$this->addTxsrPcnt( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xElem, true );
					} else {
						// Hyperlink pasted from internet, or auto-formed with IE; Continue parse process to keep its textual content (BZ#15669)
						$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					}
					break;
				default: // unhandled element, let's treat its children to see if we know them
					$this->handleStory( $icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, $icTxsr, $icPcnt, $xFrame, $xElem, $trackEOL, $icDBRefIds );
					break;
			}
		}
	}

	/**
	 * Pushes all "unknown" txsr attributes onto stack of the given InCopy txsr element ($iterTxsrAttr). <br>
	 * These are all attributes except "prst" and "crst" that are handled in p->class and span->class at HTML. <br>
	 * This is all done to maintain free formating to text selections (not to be confused with para/char styles). <br>
	 * The unknown attributes are serialized in the span->id attribute in HTML (outside this function). <br>
	 *
	 * @param string  $txsrAttrs (in)    Unknown txsr attributes (as carried through in span->id at HTML) <br>
	 * @param array   $iterTxsrAttr (in/out) List of collected attributes that needs to be applied to the new icTxsr <br>
	 * @return array of pushed txsr attributes onto stack
	 */
	function pushTsxrAttrs( $txsrAttrs, &$iterTxsrAttr )
	{
		$pushBag = array(); // pushed txsr attributes (that needs to pop later)
		foreach( $txsrAttrs as $key => $value ) {
			$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, $key, $value );
		}
		return $pushBag;
	}
	
	/**
	 * Pushes all properties of the given HTML style attribute onto stack of the given InCopy txsr element ($iterTxsrAttr). <br> 
	 * Styles are typically set to HTML paragraphs, spans and colors. <br>
	 * 
	 * Typically Safari uses <span style="font-weight: bold" class="Apple-style-span">...</span> to mark text bold,
	 * while other browers use <b>...</b>. This function handles those Safari styles as well.
	 *
	 * @param DOMDocument $icDoc (in)    InCopy document. <br>
	 * @param DOMNode $icCflo (in)       InCopy cflo element (=> root of story contents). <br>
	 * @param string  $styleAttr (in)    HTML style attribute <br>
	 * @param array   $iterTxsrAttr (in/out) List of collected attributes that needs to be applied to the new icTxsr <br>
	 * @return array of pushed style attributes to track
	 */
	private function pushTsxrStyleAttrs( $icDoc, $icCflo, $styleAttr, &$iterTxsrAttr )
	{
		$pushBag = array(); // pushed txsr attributes (that needs to pop later)
		$styleVals = $this->getStyleValues( $styleAttr );
		foreach( $styleVals as $styleKey => $styleVal ) {
			switch( $styleKey ) {
				case 'font-size':
					$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'ptsz', 'U_'.substr( $styleVal, 0, -2 ) ); // skip "px" postfix
					break;
				case 'text-decoration':
					switch( $styleVal ) {
						case 'line-through':
							$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'strk', 'b_t' );
							break;
						case 'underline':
							$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'undr', 'b_t' );
							break;
					}
					break;
				case 'font-weight':
					switch( $styleVal ) {
						case 'bold':
							$biVal = array_key_exists( 'ptfs', $iterTxsrAttr ) && $iterTxsrAttr['ptfs'] == 'c_Italic' ? 'c_Bold Italic' : 'c_Bold';
							$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'ptfs', $biVal );
							break;
					}
					break;
				case 'font-style':
					switch( $styleVal ) {
						case 'italic':
							$biVal = array_key_exists( 'ptfs', $iterTxsrAttr ) && $iterTxsrAttr['ptfs'] == 'c_Bold' ? 'c_Bold Italic' : 'c_Italic';
							$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'ptfs', $biVal );
							break;
					}
					break;
				case 'color':
					$color = $this->findColorRef( $icDoc, $icCflo->parentNode, $styleVal );
					$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'flcl', $color );
					break;
				case 'background-color':
					$backCol = $this->findColorRef( $icDoc, $icCflo->parentNode, $styleVal );
					$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'lncl', $backCol );
					break;
				case 'vertical-align':
					switch( $styleVal ) {
						case 'super':
							$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'posm', 'e_spsc' );
							break;
						case 'sub':
							$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'posm', 'e_sbsc' );
							break;
					}
					break;
				case 'padding-left':
					// InCopyHTMLConversion plugin: add left indentation
					if($this->ihcPlugin)
						$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'inbl', 'U_'.substr( $styleVal, 0, -2 ) ); // skip "px" postfix
					break;
				case 'padding-right':
					// InCopyHTMLConversion plugin: add right indentation
					if($this->ihcPlugin)
						$pushBag[] = $this->pushTxsrAttr( $iterTxsrAttr, 'inbr', 'U_'.substr( $styleVal, 0, -2 ) ); // skip "px" postfix
					break;
			}
		}
		return $pushBag;
	}
	
	/**
	 * Parses the given HTML style attributes and returns properties inside as array of key-value pair. <br> 
	 * 
	 * @param string  $styleAttr  HTML style attribute <br>
	 * @return array of style properties
	 */
	private function getStyleValues( $styleAttr )
	{
		$styleVals = array();
		$props = explode( ';', $styleAttr );
		foreach( $props as $prop ) {
			$keyVal = explode( ':', $prop );
			if( count( $keyVal ) > 1 ) {
				$styleVals[ trim($keyVal[0]) ] = trim($keyVal[1]);
			}
		}
		return $styleVals;
	}

	
	/*private function getStyleValue( $style, $propName )
	{
		$props = explode( ';', $style );
		foreach( $props as $prop ) {
			$keyVal = explode( ':', $prop );
			if( count( $keyVal ) > 1 ) {
				$key = trim( $keyVal[0] );
				if( $key == $propName ) {
					$val = trim( $keyVal[1] );
					return $val;
				}
			}
		}
		return '';
	}*/
	
	private function findColorRef( $icDoc, $icSnippetRoot, $rgb )
	{
		// Determine search key
		$rgb = trim( $rgb );
		
		/* // commented out: we don't want two-way conversions or else we risk mismatches, e.g. due to round/trunc!
		$icStory = $icSnippetRoot->parentNode;
		if( $rgb{0} == '#' ) $rgb = substr( $rgb, 1 ); // skip "#" prefix
		$rgbArr = $this->hex2rgb( $rgb );

		// Search for any color definition having the CMYK color
		$cmyk = self::rgb2cmyk( $rgbArr );
		$cmyk = 'x_4_D_'.$cmyk[0].'_D_'.$cmyk[1].'_D_'.$cmyk[2].'_D_'.$cmyk[3];
		$xpath = new DOMXPath( $icDoc );
		$query = 'SnippetRoot/colr[@clvl="'.$cmyk.'"]';
		$entries = $xpath->query( $query, $icStory );
		foreach( $entries as $entry ) {
			$clsp = $entry->getAttribute('clsp');
			if( $clsp == 'e_CMYK' ) {
				$self = $entry->getAttribute('Self');
				return 'o_'.substr( $self, 3 ); // replace "rc_" prefix with "o_" to make it a reference
			}
		}

		// Search for any color definition having the RGB color
		$rgb = 'x_3_D_'.$rgbArr[0].'_D_'.$rgbArr[1].'_D_'.$rgbArr[2];
		$xpath = new DOMXPath( $icDoc );
		$query = 'SnippetRoot/colr[@clvl="'.$rgb.'"]';
		$entries = $xpath->query( $query, $icStory );
		foreach( $entries as $entry ) {
			$clsp = $entry->getAttribute('clsp');
			if( $clsp == 'e_cRGB' ) {
				$self = $entry->getAttribute('Self');
				return 'o_'.substr( $self, 3 ); // replace "rc_" prefix with "o_" to make it a reference
			}
		}

		// Search for any color definition having the Lab color
		$lab = $this->rgb2lab( $rgbArr );
		$lab = 'x_3_D_'.$lab[0].'_D_'.$lab[1].'_D_'.$lab[2];
		$xpath = new DOMXPath( $icDoc );
		$query = 'SnippetRoot/colr[@clvl="'.$lab.'"]';
		$entries = $xpath->query( $query, $icStory );
		foreach( $entries as $entry ) {
			$clsp = $entry->getAttribute('clsp');
			if( $clsp == 'e_cLAB' ) {
				$self = $entry->getAttribute('Self');
				return 'o_'.substr( $self, 3 ); // replace "rc_" prefix with "o_" to make it a reference
			}
		}*/
		
		// Find the color in our cache (derived from InCopy DB).
		// We do one-way color conversion to avoid mismatches caused by round/trunc !
		if( ($rcId = array_search( $rgb, $this->colors )) !== false ) {
			return 'o_'.$rcId;
		}
		
		// Create new color object, like this:		
		//<colr Self="rc_ud8" atcs="e_nasp" atvl="x_0" clbs="o_n" clmd="e_prss" clsp="e_cRGB" clvl="x_3_D_115_D_255_D_119" edbl="b_t" 
		//  ovrd="e_norm" pnam="c_R=115 G=255 B=119" pvis="b_t" rmbl="b_t" swID="l_1f01"/>
		$rgbArr = $rgb;
		if( $rgbArr{0} == '#' ) $rgbArr = substr( $rgb, 1 ); // skip "#" prefix
		$rgbArr = InCopyUtils::hex2rgb( $rgbArr );
		LogHandler::Log('textconv','DEBUG',__METHOD__.': Generating resource Id for color structure in IC DB.');
		$rcId = 'u' . dechex(InCopyUtils::createNewResourceId( $icDoc ));
		$icColr = $icDoc->createElement( 'colr' );
		$icColr->setAttribute( 'Self', 'rc_'.$rcId );
		$icColr->setAttribute( 'atcs', 'e_nasp' );
		$icColr->setAttribute( 'atvl', 'x_0' );
		$icColr->setAttribute( 'clbs', 'o_n' );
		$icColr->setAttribute( 'clmd', 'e_prss' );
		$icColr->setAttribute( 'clsp', 'e_cRGB' );
		$icColr->setAttribute( 'clvl', 'x_3_D_'.$rgbArr[0].'_D_'.$rgbArr[1].'_D_'.$rgbArr[2] );
		$icColr->setAttribute( 'edbl', 'b_t' );
		$icColr->setAttribute( 'ovrd', 'e_norm' );
		$icColr->setAttribute( 'pnam', 'c_R='.$rgbArr[0].' G='.$rgbArr[1].' B='.$rgbArr[2] );
		$icColr->setAttribute( 'pvis', 'b_t' );
		$icColr->setAttribute( 'rmbl', 'b_t' );
		$icColr->setAttribute( 'swID', 'l_1f01' );
		$icSnippetRoot->insertBefore( $icColr, $icSnippetRoot->firstChild ); // do not append, or InCopy won't find it...brrr
		$this->colors[$rcId] = $rgb;
		return 'o_'.$rcId;
		
		/*// When not found, let's fall back at black
		$xpath = new DOMXPath( $icDoc );
		$query = 'SnippetRoot/colr[@pnam="c_Black"]';
		$entries = $xpath->query( $query, $icStory );
		foreach( $entries as $entry ) {
			$self = $entry->getAttribute('Self');
			return 'o_'.substr( $self, 3 ); // replace "rc_" prefix with "o_" to make it a reference
		}
		return ''; // should not happen
		*/
	}
	
	/**
	 * Pushes a txsr attribute (name, value) into a given collection (iterTxsrAttr).
	 * When the attribute already exists, the original attribute object is returned, else null.
	 * This object can be given at {@link:popTxsrAttr} function to perform reverse operation while backtracking.
	 * This is used to track text formattings while stepping down parsing the XHTML element tree.
	 * When there is actually something to output in InCopy format, the attributes are used to build a txsr.
	 * @return object Attribute with name and value.
	 */
	private function pushTxsrAttr( &$iterTxsrAttr, $attrName, $attrVal )
	{
		$orgAttr = new stdClass();
		$orgAttr->Name = $attrName;
		if( array_key_exists( $attrName, $iterTxsrAttr ) ) {
			$orgAttr->Value = $iterTxsrAttr[$attrName];
		} else {
			$orgAttr->Value = null;
		}
		$iterTxsrAttr[$attrName] = $attrVal;
		return $orgAttr;
	}
	
	/**
	 * Pops a txsr attribute object ($orgAttr) from a given collection (iterTxsrAttr).
	 * See also {@link:pushTxsrAttr} function for more details.
	 *
	 * @param array $iterTxsrAttr Collection of attributes to track
	 * @param object $orgAttr Attribute object that was overwritten by {@link:pushTxsrAttr} function.
	 */
	private function popTxsrAttr( &$iterTxsrAttr, $orgAttr )
	{
		if( is_null( $orgAttr->Value ) ) {
			unset( $iterTxsrAttr[$orgAttr->Name] ); // remove
		} else {
			$iterTxsrAttr[$orgAttr->Name] = $orgAttr->Value; // restore overwritten
		}
	}

	/**
	 * Pops a collection of txsr attributes object ($orgAttrs) from a given collection ($iterTxsrAttr).
	 * See also {@link:popTxsrAttr} function for more details.
	 *
	 * @param array $iterTxsrAttr Collection of attributes to track
	 * @param array $orgAttrs Collection of attributes that was overwritten by {@link:pushTxsrAttr} function.
	 */
	private function popTxsrAttrs( &$iterTxsrAttr, $orgAttrs )
	{
		foreach( $orgAttrs as $orgAttr ) {
			$this->popTxsrAttr( $iterTxsrAttr, $orgAttr );
		}
	}
	
	/**
	 * Removes all txsr elements from all stories. <br>
	 * After that, the IC document is ready to receive new typed content from XHTML. <br>
	 *
	 * @param DOMDocument $icDoc (in)   InCopy document. <br>
	 * @param DOMNodeList $icCflos (in) List of InCopy cflo elements (=> roots of story contents). <br>
	 * @param DOMNode $icInsPoint (out) Returns next sibling of last removed InCopy txsr element. <br>
	 */
	/*private function removeAllTxsrs( $icDoc, $icCflos )
	{
		foreach( $icCflos as $cflo ) {
			$icCfloGUID = $cflo->getAttribute('GUID');
			if( $icCfloGUID && strlen($icCfloGUID) > 2 ) { // Skip inline text! Inline text frames have attribute GUID="k_"
				$xpath = new DOMXPath($icDoc);
				$query = 'txsr';
				$txsrs = $xpath->query( $query, $cflo );
				foreach( $txsrs as $txsr ) {
					$cflo->removeChild( $txsr );
				}
			}
		}
	}*/

	/**
	 * Creates txsr and pcnt elements. <br>
	 * It also updates the txsr element with prst/crst attributes (para/char styles). <br>
	 * This is needed to insert any text elements (that are typed in XHTML). <br>
	 *
	 * @param DOMDocument $icDoc (in/out) InCopy document. <br>
	 * @param DOMNode $icCflo (in)     InCopy cflo element (=> root of story contents). <br>
	 * @param DOMNode $icInsPoint (in) Insertion point to add InCopy txsr elements <br>
	 * @param DOMNode $icTxsr (in/out) Current InCopy txsr element to reuse. Pass null to create new one. <br>
	 * @param DOMNode $icPcnt (in/out) Current InCopy pcnt element to reuse. Pass null to create new one. <br>
	 * @param DOMNode $xElem (in)      XHTML element being parsed. Can be any of supported: p, span, text, img, pi, text, etc.<br>
	 */
	private function addTxsrPcnt( &$icDoc, $icCflo, $icInsPoint, $iterTxsrAttr, &$icTxsr, &$icPcnt, $xElem, $forceNewTxsr )
	{
		$xElem = $xElem; // keep analyzer happy
		
		// Check we have different attributes collected (iterTxsrAttr) than defined for the last one created (icTxsr)
		$icTxsrAttr = array();
		if( $icTxsr ) foreach( $icTxsr->attributes as $attr ) {
			$icTxsrAttr[$attr->name] = $attr->value;
		}

		// Add default styles when missing
		if( !array_key_exists( 'prst', $iterTxsrAttr ) ) {
			$iterTxsrAttr['prst'] = 'o_'.$this->defaultpara;
		}
		if( !array_key_exists( 'crst', $iterTxsrAttr ) ) {
			$iterTxsrAttr['crst'] = 'o_'.$this->defaultchar;
		}
		
		// Create new txsr+pcnt elements
		//$icTxsrDiff = array_diff_key( $icTxsrAttr, $iterTxsrAttr );
		$icTxsrDiff = $icTxsrAttr != $iterTxsrAttr; //count( $icTxsrDiff ) > 0;
		if( $icTxsrDiff ||  // is there any different attribute?
			$forceNewTxsr || // requested by caller?
			is_null($icTxsr) || is_null($icPcnt) ) { // first time called?
			// Avoid empty txsr or pcnt elements
			if( $icPcnt && !$icPcnt->hasChildNodes() ) $icPcnt->parentNode->removeChild( $icPcnt ); 
			if( $icTxsr && !$icTxsr->hasChildNodes() ) $icTxsr->parentNode->removeChild( $icTxsr );

			// Create new txsr and pcnt elements
			$icTxsr = $icDoc->createElement( 'txsr' );
			$icCflo->insertBefore( $icTxsr, $icInsPoint );
			$icPcnt = $icDoc->createElement( 'pcnt' );
			$icTxsr->appendChild( $icPcnt );
			
			// Set all collected attributes to the new created txsr element
			foreach( $iterTxsrAttr as $key => $val ) {
				$icTxsr->setAttribute( $key, $val );
				
				// Add para/char style to the story when missing (resolved from global wwst_stories element)
				if( $key == 'prst' || $key == 'crst' || $key == 'flcl' || $key == 'lncl' ) {
					$this->resolveStyleDef( $icDoc, $icCflo->parentNode->parentNode, $key, $val );
				}
			}			
		}
	}

	/**
	 * Resolves paragraph-, character- and color styles from WW template definition. <br>
	 * It checks if the style is present the given story. If not, it looks up the style definition <br>
	 * in the WW template element and copies it into the story. Also para/char style colors are copied. <br>
	 *
	 * @param DOMDocument $icDoc (in/out) InCopy document. <br>
	 * @param DOMNode $icStory (in)       InCopy story element. (xpath: //Stories/Story). <br>
	 * @param DOMNode $icStyleType (in)   Style type: 'psty', 'csty' or 'colr'.<br>
	 * @param DOMNode $icStyleRef (in)    Style id reference (from txsr element) inlcuding "o_" prefix. <br>
	 */
	private function resolveStyleDef( &$icDoc, $icStory, $icStyleType, $icStyleRef )
	{
		// Preparation and validation
		switch( $icStyleType ) {
			case 'prst': $icStyleElem = 'psty'; break;
			case 'crst': $icStyleElem = 'csty'; break;
			case 'flcl': $icStyleElem = 'colr'; break;
			case 'lncl': $icStyleElem = 'colr'; break;
			default: return false; // bad param, should never happen
		}
		$xpath = new DOMXPath( $icDoc );
		if( !$icStyleRef ) return false; // paranoid check, should not happen
		
		$query = 'SnippetRoot';
		$entries = $xpath->query( $query, $icStory );
		if( $entries->length == 0 ) return false; // not found, should never happen
		$icSnipRoot = $entries->item(0);

		$query = 'SnippetRoot/'.$icStyleElem;
		$entries = $xpath->query( $query, $icStory );
		$icLastStyle = $entries->length > 0 ? $entries->item($entries->length-1) : null;

		// Get style definition from story
		if( $icStyleRef == 'k_[No paragraph style]' ) {
			$query = 'SnippetRoot/'.$icStyleElem.'[@pnam="k_[No paragraph style]"]';
		} else {
			$icStyleRc = 'rc_'.substr( $icStyleRef, 2 ); // skip "o_"
			$query = 'SnippetRoot/'.$icStyleElem.'[@Self="'.$icStyleRc.'"]';
		}
		$entries = $xpath->query( $query, $icStory );
		if( $entries->length == 0 ) { // not found, let's copy

			// Get style definition from WW template element
			$query2 = '//Stories/wwst_template/wwst_styles/'.$query;
			$entries = $xpath->query( $query2 );
			if( $entries->length == 0 ) { // not found, let's try WW document
				$query2 = '//Stories/wwsd_document/wwsd_styles/'.$query;
				$entries = $xpath->query( $query2 );
				if( $entries->length == 0 ) return false; // not found, rarely happens, typically for pasted html content or bold/italic styles in Safari
			}
			$icWWStyle = $entries->item(0);

			// Style is missing, so here we add it to the story
			$icStyle = $icWWStyle->cloneNode( true );
			if( $icSnipRoot->firstChild ) {
				$icSnipRoot->insertBefore( $icStyle, $icLastStyle ? $icLastStyle : $icSnipRoot->firstChild );
			} else {
				$icSnipRoot->appendChild( $icStyle );
			}
		} else {
			$icStyle = $entries->item(0);
		}
		
		if( $icStyleType == 'prst' || $icStyleType == 'crst' ) { // just paranoid avoid recursion
			// Also copy referred font color definitions
			$icFlcl = $icStyle->getAttribute( 'flcl' );
			if( $icFlcl ) { 
				$this->resolveStyleDef( $icDoc, $icStory, 'flcl', $icFlcl ); 
			}

			// Also copy inherited para/char styles
			$icBasd = $icStyle->getAttribute( 'basd' );
			if( $icBasd ) { 
				$this->resolveStyleDef( $icDoc, $icStory, $icStyleType, $icBasd ); 
			}
		}
		return true;
	}
}
?>
