<?php
/**
 * Factory class that creates text convertors used by the Web Editor.<br>
 *
 * Based on a given file format, it creates a TextImport or TextExport that deals with the format. <br>
 * Supported formats are plain text and InCopy. <br>
 *
 * @package SCEnterprise
 * @subpackage WebEditor
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class TextConverter
{
	/**
	 * Factory that creates a TextImport object. <br>
	 *
	 * @param string $format  File format (mime type) e.g. 'text/plain' or 'application/incopy'
	 * @return object         TextImport object, or null when format is not supported
	 */
	static public function createTextImporter( $format )
	{
		$fc = null;
		switch( $format ) {
			case 'application/incopy':
			case 'application/incopyinx':
				require_once BASEDIR.'/server/appservices/textconverters/InCopyTextImport.class.php';
				$fc = new InCopyTextImport();
				break;
			case 'application/incopyicml':
				require_once BASEDIR.'/server/appservices/textconverters/Wcml2Xhtml.php';
				$fc = new WW_TextConverters_Wcml2Xhtml();
				break;
			case 'text/plain':
			case 'application/text':
				require_once BASEDIR.'/server/appservices/textconverters/PlainTextImport.class.php';
				$fc = new PlainTextImport();
				break;
			default:
				LogHandler::Log( 'textconv', 'WARN', 'TextConverterFactory->createTextImporter(): No import text converter found for ['.$format.']');
		}
		return $fc;
	}

	/**
	 * Factory that creates a TextExport object. <br>
	 *
	 * @param string $format  File format (mime type) e.g. 'text/plain' or 'application/incopy'
	 * @return object         TextExport object, or null when format is not supported
	 */
	static public function createTextExporter( $format )
	{
		$fc = null;
		switch( $format ) {
			case 'application/incopy':
			case 'application/incopyinx':
				require_once BASEDIR.'/server/appservices/textconverters/InCopyTextExport.class.php';
				$fc = new InCopyTextExport();
				break;
			case 'text/plain':
			case 'application/text':
				require_once BASEDIR.'/server/appservices/textconverters/PlainTextExport.class.php';
				$fc = new PlainTextExport();
				break;
			default:
				LogHandler::Log( 'textconv', 'WARN', 'TextConverterFactory->createTextExporter(): No export text converter found for ['.$format.']');
		}
		return $fc;
	}
	
	/**
	 * Converts a collection of files from native format to XHTML. <br>
	 *
	 * Each file is represented by an array with following properties: <br>
	 * - FilePath:   Specify the full path of native file to be imported. <br>
	 * - Format:     Specify the mime-type of the file, e.g. 'text/plain' or 'application/incopy' <br>
	 * - HtmlFrames: Returns the collection of XHTML frames (=text frames) that have been imported. <br>
	 *
	 * @param array $files    Collection of files: array( array( 'FilePath', 'Format', 'HtmlFrames', 'StylesCSS', 'StylesMap', 'DOMVersion' ), ... )
	 */
	static public function import( &$files )
	{
		foreach( $files as &$file ) { // & -> return HtmlFrames property
			$fc = self::createTextImporter( $file['Format'] );
			if( $fc != null ) {
				$xFrames = array();
				$stylesCSS = '';
				$stylesMap = '';
				$domVersion = '0';
				$fc->importFile( $file['FilePath'], $xFrames, $stylesCSS, $stylesMap, $domVersion );
				$file['HtmlFrames'] = $xFrames;
				$file['StylesCSS'] = $stylesCSS;
				$file['StylesMap'] = $stylesMap;
				$file['DOMVersion'] = $domVersion;
			}
		}
	}

	/**
	 * Converts a collection of files XHTML to their native format. <br>
	 *
	 * Each file is represented by an array with following properties: <br>
	 * - FilePath:   Specify the full path of native file to be imported. <br>
	 * - Format:     Specify the mime-type of the file, e.g. 'text/plain' or 'application/incopy' <br>
	 * - HtmlFrames: Specify the collection of XHTML frames (=text frames) to export. <br>
	 *
	 * @param array $files    Collection of files: array( array( 'FilePath', 'Format', 'HtmlFrames' ), ... )
	 */
	static public function export( $files )
	{
		foreach( $files as $file ) {
			$fc = self::createTextExporter( $file['Format'] );
			if( $fc != null ) {
				$fc->exportFile( $file['HtmlFrames'], $file['FilePath'] );
			}
		}
	}
}
