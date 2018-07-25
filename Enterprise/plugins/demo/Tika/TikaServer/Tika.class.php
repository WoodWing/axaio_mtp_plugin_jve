<?php
/****************************************************************************
   Copyright 2008-2010 WoodWing Software BV

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

/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Facade to Tika for plain content extraction.
 *
**/

class Tika
{
	public static $version = '1.0'; // Tika Server plugin interface version
	
	/**
	 *	Run the tika command line utility to return file plaintext or metadata
	 *
	 * @param string	$inputPath 	Input file path
	 * @param string	$outputPath	Output file path
	 * @param string	$option 	Option type either plaintext/metadata
	 * @return array/string	Returns result in array or error in string
	*/
	public static function runTikaCmd( $inputPath, $outputPath, $option )
	{
		require_once dirname(__FILE__) . '/config.php';
		$dir = sys_get_temp_dir();
		$javaHeapSize = '';
		
		if( JAVA_INI_HEAP_SIZE > 0 ) {
			$javaHeapSize = "-Xms" . JAVA_INI_HEAP_SIZE . "m ";
		}
		if( JAVA_MAX_HEAP_SIZE > 0 ) {
			$javaHeapSize .= "-Xmx" . JAVA_MAX_HEAP_SIZE . "m ";
		}

		$cmd = CMDSETENV . "java -Djava.awt.headless=true " . $javaHeapSize . "-jar " . escapeshellarg(TIKA_APP) . ' -e"UTF-8"'. " -$option " . escapeshellarg($inputPath);
			// Fix: Added flag "-Djava.awt.headless=true" to avoid error extracing from PDF on MacOSX:
			// 'Exception in thread "main" java.lang.InternalError: Can't connect to window server - not enough permissions.'
			// See also: http://ernstdehaan.blogspot.com/2008/10/mac-os-x-fonts-and-headless-java.html
		if( $option == 't') {
			$cmd .= ' > ' . escapeshellarg($outputPath) . ' 2>&1';
		}
		else {
			$cmd .= ' 2>&1';
		}

		$error = 0;
		exec($cmd, $output, $error);
		
		if( $error ) {
			if( $option == 'm') {
				$result = implode("\n",$output);
				$result = "$cmd\n$result";
			}
			else {
				$result = "$cmd\n" . file_get_contents( $outputPath );
				unlink( $outputPath );
			}
		}
		else {
			$result = $output;
		}

		return $result;
	}

	/**
	 * Get all the Tika supported file format from the test directory.
	 * 
	 * @return string/array $result Return array of format or return error message when format not found
	*/
	public static function getSupportedFormats()
	{
		require_once dirname(__FILE__) . '/config.php';
		//$testDocDir = TIKA_APP_DIRECTORY . '/tika-parsers/target/test-classes/test-documents/';
		$testDocDir = TIKA_APP_DIRECTORY . '/test-documents/';
		$result = array();
		
		$files = glob( $testDocDir . 'test*', GLOB_MARK ); // Get only file with "test" word prefix in the filename
		foreach( $files as $file ) {
			if(is_file($file)){
				$metaData = Tika::runTikaCmd($file, '', 'm');
				foreach( $metaData as $meta ) {
					$metaNameValue = explode( ":", $meta );
					if( $metaNameValue[0] == 'Content-Type' ) {
						$result[] = trim($metaNameValue[1]);
						break;
					}
				}
			}
		}
		$result = array_unique( $result );

		if( empty($result) ) {
			$result = "No supported formats found in Tika";
		}
		return $result;
	}

	/**
	 * Get the installed Java version on the Tika Server
	 * 
	 * @return string/array $result Return Java version or return error message when format not found
	*/
	public static function getJavaVersion()
	{
		$cmd = CMDSETENV . 'java -version 2>&1';
		
		$error = 0;
		$output= null;
		exec($cmd, $output, $error);
		if( $error ) {
			$result = implode("\n",$output);
			$result = "$cmd\n$result";
		}
		else {
			$output[0] = substr( $output[0], strpos($output[0], '"') + 1, 3 );
			$result = $output;
		}
		return $result;
	}

	/**
	 * Get the installed Tika version on the Tika Server
	 * 
	 * @return string/array $result Return Tika version or return error message when format not found
	*/
	public static function getTikaVersion()
	{
		// Check configured options
		require_once dirname(__FILE__) . '/config.php';
		if( !is_dir(TIKA_APP_DIRECTORY) ) {
			return 'The Tika directory "'.TIKA_APP_DIRECTORY.'" was not found. Please check the TIKA_APP_DIRECTORY option in "'.dirname(__FILE__).'/config.php" .';
		}
		if( !is_file(TIKA_APP) ) {
			return 'The Tika application "'.TIKA_APP.'" was not found. Please check the TIKA_APP option in "'.dirname(__FILE__).'/config.php" .';
		}
		$tempDir = sys_get_temp_dir();
		if( !is_dir($tempDir) || !is_writable($tempDir) ) {
			return 'The temporary directory of PHP "'.$tempDir.'" was not found or is not writable.';
		}

		// Execute Tika app, if error exist, don't retrieve the xml file
		$tikaVersion = array();
		$cmd = CMDSETENV . "java -jar " . escapeshellarg(TIKA_APP) . " -? 2>&1";
		$error = 0;
		$output= null;
		exec($cmd, $output, $error);
		if( $error ) {
			$result = implode("\n",$output);
			$result = "$cmd\n$result";
			return $result;
		}

		// Read Tika version from its pom.xml file.
		$xmlContents = file_get_contents( TIKA_APP_DIRECTORY.'/pom.xml' );
		if( $xmlContents === false ) {
			return 'Can not read XML file, '.TIKA_APP_DIRECTORY.'/pom.xml';
		}
		$xmlDoc = new DOMDocument();
		$xmlParser = new Tika_XmlParser();
		if( !$xmlParser->loadXML( $xmlDoc, $xmlContents ) ) {
			return 'Can not parse XML file, '.TIKA_APP_DIRECTORY.'/pom.xml';
		}
		// Don't not validate the schema for now
		/*if( !$xmlParser->schemaValidate( $xmlDoc, 'http://maven.apache.org/maven-v4_0_0.xsd' ) ) {
			exit( 'Can not validate XML file' );
		}*/

		$xpath = new DOMXPath( $xmlDoc );
		$xpath->registerNamespace( 'pom', 'http://maven.apache.org/POM/4.0.0' );
		$query = '/pom:project/pom:parent/pom:version';
		$entries = $xpath->query( $query );
		if( $entries->length == 0 ) { 
			return 'Can not determine the Tika version';
		}
		$tikaVersion[] = $entries->item(0)->nodeValue;
		return $tikaVersion;
	}

	/**
	 * Check the Tika Server system folder access right of web server user
	 * 
	 * @return bool Return true when access found, false when no access
	*/
	public static function checkSystemFolderAccess()
	{
		if( strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ) {
			return self::isReadableAfterTikaProcess(tempnam( sys_get_temp_dir(), 'tika-out-' ));
		}
		else {
			return is_writable(sys_get_temp_dir());
		}
	}

	/**
	 * Check the Tika Server system temp folder is readable after java process
	 * 
	 * The reason why run this tika process is due to,
	 * for windows temp folder permission, it always writable and readable, although didn't have the permission.
	 * But when the tika java process take place, and create the file in the system temp folder,
	 * the Internet user account will no longer able to read the file, therefore return empty contents.
	 * The test here is simulate the exact workflow in production, so if it failed, it will fail during installtion of plugin,
	 * and not during production.
	 * 
	 * @return bool Return true when access found, false when no access
	*/
	private function isReadableAfterTikaProcess($outputPath) 
	{
		$inputPath = dirname(__FILE__) . '/config.php';
		self::runTikaCmd($inputPath, $outputPath, "t");

		if (!file_exists($outputPath)) {
        	return false;
		}
		unlink($outputPath);
		return true;
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
/**
 * Tika_XmlParser class
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * Helper class reading schema files, validating them against XML doc, and logging any errors.
 */
class Tika_XmlParser
{
	private $module = null;
	
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
	 * Wrapper function for the DOMDocument::loadXML() function catching and logging errors.
	 *
	 * @param DOMDocument $xDoc The XML document to get loaded with given XML string.
	 * @param string $xmlString The string containing the XML
	 * @param integer $options Bitwise OR of the libxml option constants
	 * @return bool Wether the given XML document is well formed or not.
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
	 * @return bool Wether the given XML document is valid or not, regarding the given XSD schema.
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