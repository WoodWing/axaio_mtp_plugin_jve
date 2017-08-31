<?php
/**
 * TextImport class for plain text format used by the Web Editor.
 *
 * Converts from plain text format to collection of XHTML frames that can be edit in TinMCE frames.
 * See {@link TextImport} interface for more details.
 *
 * @package Enterprise
 * @subpackage WebEditor
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/appservices/textconverters/TextImportExport.intf.php'; // TextImport
require_once BASEDIR.'/server/utils/FileHandler.class.php';

class PlainTextImport implements TextImport
{
	/**
	 * Convert a file from plain text format to collection of XHTML frames.
	 * The encoding is concidered to be UTF-8 in Web Editor (XHTML) and stored as UTF-16BE in the DB.
	 * This function takes care about conversion between both encodings as well.
	 *
	 * @param string $plainFile  Full path of file to be read.
	 * @param array  &$xFrames   Returned collection of XHTML DOMDocument each representing one text frame.
	 * @param array &$stylesCSS
	 * @param array &$stylesMap
	 * @param string &$domVersion
	 */ 
	public function importFile( $plainFile, &$xFrames, &$stylesCSS, &$stylesMap, &$domVersion )
	{
		LogHandler::Log( 'textconv', 'INFO', 'PlainFileImport->import(): Reading Plain Text for ['.$plainFile.']' );
		$fh = new FileHandler();
		$fh->openFile( $plainFile, 'r' ); // determine path and access mode
		$fh->readFile(); // determine content + encoding
		if( $fh->getFileEncoding() != 'UTF-8' ) {
			$fh->convertEncoding(); // make UTF-8
		}
		$plainContent = $fh->getFileContent();

		LogHandler::Log( 'textconv', 'INFO', 'PlainFileImport->import(): Convert Plain Text to HTML for ['.$plainFile.']' );
		$this->importBuf( $plainContent, $xFrames, $stylesCSS, $stylesMap, $domVersion );
		$fh->closeFile();
	}

	/**
	 * Convert a memory buffer from plain text format to collection of XHTML frames.
	 *
	 * @param string $plainContent  Memory buffer to be read.
	 * @param array  $xFrames       Returned collection of XHTML DOMDocument each representing one text frame.
	 * @param array &$stylesCSS
	 * @param array &$stylesMap
	 * @param string &$domVersion
	 */ 
	public function importBuf( $plainContent, &$xFrames, &$stylesCSS, &$stylesMap, &$domVersion )
	{
		// Create HTML document with empty body
		$xDoc = new DOMDocument('1.0');
		$xRoot = $xDoc->createElement( 'html' );
		$xDoc->appendChild( $xRoot );
		$xBody = $xDoc->createElement( 'body' );
		$xRoot->appendChild( $xBody );
		
		// Add paragraph with text content for each chunk ended with EOL.
		$texts = mb_split( "\n", $plainContent );
		foreach( $texts as $text ) {
			$xPara = $xDoc->createElement( 'p' );
			$xBody->appendChild( $xPara );
			$xText = $xDoc->createTextNode( $text );
			$xPara->appendChild( $xText );
		}

		$xFrame = new stdClass();
		$xFrame->Label = '';
		$xFrame->Document = $xDoc;
		$xFrame->Content = $xDoc->saveXML();

		// Return XHTML DOM
		$xFrames[] = $xFrame; 
		
		// We might want to fill in some defaults here
		//$stylesCSS[] = ...
		//$stylesMap[] = ...
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
