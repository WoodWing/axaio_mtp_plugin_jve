<?php
/**
 * Helper class reading schema files, validating them against XML doc, and logging any errors.
 *
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
class WW_Utils_XmlParser
{
	private $module = null;
	private $xmlParserErrLog = array();

	public function __construct( $module = 'XmlParser' )
	{
		$this->module = $module;
	}

	private function logError( $error )
	{
		switch( $error->level ) {
			case LIBXML_ERR_WARNING:
				$level = 'WARN';
				break;
			case LIBXML_ERR_ERROR:
				$level = 'ERROR';
				break;
			case LIBXML_ERR_FATAL:
				$level = 'FATAL';
				break;
			default:
				$level = '';
				break;
		}
		$log = '';
		if( $level ) {
			$log .= $level.': ';
		}
		$log .= trim( $error->message );
		if( $error->file ) {
			$log .= ' in file "'.$error->file.'"';
		}
		if( $error->line ) {
			$log .= ' on line '.$error->line;
		}
		if( $error->column ) {
			$log .= ' at column '.$error->column;
		}
		if( $error->code ) {
			$log .= ' (error code: '.$error->code.')';
		}
		$this->xmlParserErrLog[] = $log; // for DM Error Report
		LogHandler::Log( $this->module, 'ERROR', $log ); // no matter the level, always error!
	}

	private function logErrors()
	{
		$errors = libxml_get_errors();
		if( $errors ) foreach( $errors as $error ) {
			$this->logError( $error );
		}
	}
	
	/**
	 * It collects all error messages from libxml_get_errors()
	 *
	 * @return array of errors returned by libxml_get_errors(). Null when there's no errors.
	 */
	public function getXmlParserError()
	{
		return empty($this->xmlParserErrLog) ? null : $this->xmlParserErrLog;
	}

	/**
	 * Wrapper function for the DOMDocument::loadXML() function catching and logging errors.
	 *
	 * @param DOMDocument $xDoc The XML document to get loaded with given XML string.
	 * @param string $xmlString The string containing the XML
	 * @param integer $options Bitwise OR of the libxml option constants
	 * @return bool Whether the given XML document is well formed or not.
	 */
	public function loadXML( DOMDocument $xDoc, $xmlString, $options = 0 )
	{
		// Disable libxml errors and allow user to fetch error information as needed
		$prevVal = libxml_use_internal_errors(true);

		// Validate XML against schema
		$isWellFormed = $xDoc->loadXML( $xmlString, $options );
		if( !$isWellFormed ) {
			$this->logErrors();
		}

		// Clear libxml error buffer
		libxml_clear_errors();

		// Restore original error fetch mode of libxml
		libxml_use_internal_errors( $prevVal );

		return $isWellFormed;
	}

	/**
	 * Wrapper function for the DOMDocument::schemaValidate() function catching and logging errors.
	 *
	 * @param DOMDocument $xDoc The XML document to validate
	 * @param string $schemaFile The path to XSD schema file to be used for validation
	 * @return bool Whether the given XML document is valid or not, regarding the given XSD schema.
	 */
	public function schemaValidate( DOMDocument $xDoc, $schemaFile )
	{
		// Disable libxml errors and allow user to fetch error information as needed
		$prevVal = libxml_use_internal_errors(true);

		// Validate XML against schema
		$isValid = $xDoc->schemaValidate( $schemaFile );
		if( !$isValid ) {
			$this->logErrors();
		}

		// Clear libxml error buffer
		libxml_clear_errors();

		// Restore original error fetch mode of libxml
		libxml_use_internal_errors( $prevVal );

		return $isValid;
	}
}