<?php
/**
 * TextExport class for plain text format used by the Web Editor.
 *
 * Converts from collection of XHTML frames that have been edit in TinMCE frames back into plain text format.
 * See {@link TextExport} interface for more details.
 *
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/appservices/textconverters/TextImportExport.intf.php'; // TextExport
require_once BASEDIR.'/server/utils/FileHandler.class.php';

class PlainTextExport implements TextExport
{
	/**
	 * Convert a collection of XHTML frames into a file in plain text format.
	 * The encoding is concidered to be UTF-8 in Web Editor (XHTML) and stored as UTF-16BE in the DB.
	 * This function takes care about conversion between both encodings as well.
	 *
	 * @param array  $xFrames    Collection of XHTML DOMDocument each representing one text frame.
	 * @param string $plainFile  Full path of file to be written with plain text.
	 * @param boolean $draft     Perform draft save (false for permantent save). Keeps embedded object definitions to allow undo operations.
	 */ 
	public function exportFile( $xFrames, $plainFile, $draft )
	{
		LogHandler::Log( 'textconv', 'INFO', 'PlainFileExport->export(): Converting HTML to Plain Text from ['.$plainFile.']');

		$plainContent = '';	
		$this->exportBuf( $xFrames, $plainContent, $draft );

		// Let's go (back?) to InCopy compatible encoding (UTF-16BE) -> which can be opened in InCopy
		LogHandler::Log( 'textconv', 'INFO', 'PlainFileExport->export(): Saving Plain Text to ['.$plainFile.']');
		$fh = new FileHandler();
		$fh->writeFile( $plainFile, $plainContent, 'w' );
	}

	/**
	 * Convert a collection of XHTML frames to a memory buffer in plain text format.
	 * BZ#10705: It makes sure text sequence is respected, by jumpng into recursion at (@link: exportNode}
	 *
	 * @param array  $xFrames       Collection of XHTML DOMDocument each representing one text frame.
	 * @param string $plainContent  Returned memory buffer with plain text.
	 * @param boolean $draft        Perform draft save (false for permantent save). Keeps embedded object definitions to allow undo operations.
	 */ 
	public function exportBuf( $xFrames, &$plainContent, $draft )
	{
		// Walk through all XHTML frames; each should have one body
		foreach( $xFrames as $xFrame ) {
			$xpath = new DOMXPath( $xFrame );
			$query = '//body'; // Take out the body from the frame
			$xBodies = $xpath->query( $query );
			foreach( $xBodies as $xBody ) { // should be one body, but let's walk through all
				$this->exportNode( $xFrame, $xBody, $plainContent );
			}
		}
		// Remove the last EOL, only when it is on the far end
		$textEndsWithEOL = substr( $plainContent, -2, 2 ) == "\r\n";
		if( $textEndsWithEOL ) {
			$plainContent = substr( $plainContent, 0, -2 );
		}

		// Convert UTF-8 encoding to UTF-16 Big Endian
		$newBom = chr(0xFE) . chr(0xFF); // Insert UTF-16BE BOM marker, which eases recognizion for any editor
		$plainContent = $newBom . mb_convert_encoding( $plainContent, 'UTF-16BE', 'UTF-8' ); 
	}

	/**
	 * Convert a XHTML element to a memory buffer in plain text format.
	 *
	 * @param DOMDocument $xFrame (in)  XHTML document (text frame) being parsed.
	 * @param DOMNode $xParent
	 * @param string $plainContent  Returned memory buffer with plain text.
	 * @param integer $indent       The text indent (typically for lists)
	 * @param string $numSystem     Numbering system; 'ol' for numbered list or 'ul' bullet list.
	 * @return boolean TRUE if child element did an enter, FALSE otherwise.
	 */ 
	private function exportNode( $xFrame, $xParent, &$plainContent, $indent = 0, $numSystem = 'ul' )
	{
		$childHasEnter = false;
		$listNumber = 0;
		$xpath = new DOMXPath( $xFrame );
		$query = '*|text()'; // Walk through all elements including texts
		$xElements = $xpath->query( $query, $xParent );
		foreach( $xElements as $xElem ) {
			switch( $xElem->nodeName ) {
				case '#text': 
					// Strip internal line endings (XHTML make-up) which is no part of the content (BZ#11286)
					$plainContent .= str_replace( "\n", '', $xElem->textContent );
					break;
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
				case 'pre':
				case 'address':
				case 'blockquote':
				case 'p':
					$orgText = $plainContent;
					$this->exportNode( $xFrame, $xElem, $plainContent, $indent, $numSystem );
					$childHasText = strlen($orgText) != strlen($plainContent);
					$childEndsWithEOL = substr( $plainContent, -2, 2 ) == "\r\n";
					if( $childHasText && !$childEndsWithEOL ) {
						// HTML rendering issue: paragraph elements <p> needs to 'enter' when 
						// one of the childs contain any text!
						$childHasEnter = true; // tell daddy about his child did enter
						$plainContent .= "\r\n";
					}
					break;
				case 'li':
					$listNumber++;
					$numPrefix = ($numSystem == 'ul') ? '* ' : $listNumber . '. ';
					$plainContent .= str_repeat( "\t", $indent ) . $numPrefix;
					if( !$this->exportNode( $xFrame, $xElem, $plainContent, $indent, $numSystem ) ) { 
						// HTML rendering issue: list elements <li> needs to 'enter' when *none* of the childs did!
						$childHasEnter = true; // tell daddy about his child did enter
						$plainContent .= "\r\n";
					}
					break;
				case 'ol':
					$numSystem = 'ol';
					$indent++;
					if( $this->exportNode( $xFrame, $xElem, $plainContent, $indent, $numSystem ) ) { 
						$childHasEnter = true; // tell daddy some grantchild did enter
					}
					$indent--;
					break;
				case 'ul':
					$numSystem = 'ul';
					$indent++;
					if( $this->exportNode( $xFrame, $xElem, $plainContent, $indent, $numSystem ) ) { 
						$childHasEnter = true; // tell daddy some grantchild did enter
					}
					$indent--;
					break;
				case 'br':
				case 'tr':
					$this->exportNode( $xFrame, $xElem, $plainContent, $indent, $numSystem );
					$childHasEnter = true; // tell daddy about this child did enter
					$plainContent .= "\r\n";
					break;
				case 'td':
					if( $this->exportNode( $xFrame, $xElem, $plainContent, $indent, $numSystem ) ) { 
						$childHasEnter = true; // tell daddy some grantchild did enter
					}
					$plainContent .= "\t";
					break;
				case 'a':
					$orgText = $plainContent;
					if( $this->exportNode( $xFrame, $xElem, $plainContent, $indent, $numSystem ) ) { 
						$childHasEnter = true; // tell daddy some grantchild did enter
					}
					// Show the internal URL (between bracket) only when differs from display URL
					$displayURL = substr( $plainContent, strlen($orgText) );
					$href = $xElem->getAttribute( 'href');
					if( !empty($href) && $href != $displayURL ) {
						$plainContent .= ' ('.$href.')';
					}
					break;
				default: // other XHTML elements!
					if( $this->exportNode( $xFrame, $xElem, $plainContent, $indent, $numSystem ) ) { 
						$childHasEnter = true; // tell daddy some grantchild did enter
					}
					break;
			}
		}
		return $childHasEnter;
	}

}
