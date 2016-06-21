<?php
/**
 * @package 	Enterprise
 * @subpackage 	ImageMagick
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Facade to ImageMagick for preview generation and metadata extraction.
 *
**/

class ImageMagick
{
	/**
	 *	Converts the given image data to the given size in JPG.
	 *  Image can be passed as a path to a file or as as stored in memory.
	 *  If passed in memory, first write the image data to disk using a unique temporary name.
	 *	Then call convertFile: use ImageMagick to convert the image.
	 *	Finally, read the output file from converted file, and return it.
	 *	Clean up the temporary files.
	 *
	 * @param mixed 	    $data			Image data to convert to JPG. Either in memory or a file path.
	 * @param int 			$size			Maximum width/height for the resulting image
	 * @param MetaData		$meta			Meta data of the image object
	 * @param boolean		$filePathUsage 	The given file is assumed to be a file path (else it is passed in memory)
	 * @return Returns JPG image of requested size.
	*/
	public static function convertData( $data, $size, MetaData $meta, $filePathUsage  )
	{
		if( $filePathUsage && strlen($data) === 0 ) {
			LogHandler::Log('ImageMagick', 'ERROR', 'Conversion failed. No image data given to convert.' );
			return null;
		}
	
		$outputFilename = tempnam( sys_get_temp_dir(), 'output' );
		//Use extension .jpg to specify the output file format. This is mandatory otherwise ImageMagick does not know
		//that the image must be converted to jpeg and will convert to the type of the original image. 
		$outputFilename = self::addExtension($outputFilename, '.jpg');

		if ( $filePathUsage ) { // Image is passed as file.
			require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
			$ext = MimeTypeHandler::mimeType2FileExt($meta->ContentMetaData->Format, $meta->BasicMetaData->Type);
			// Add the extension to the file to circumvent an ImageMagick bug. See BZ#28047. This is only
			// needed for native files (so $filepathUsage).
			$inputFilename = self::addExtension( $data, $ext );
		} else { // Image is stored in memory, write data to a temporary file.
			$inputFilename = tempnam( sys_get_temp_dir(), 'input' );
			$tmpin = fopen( $inputFilename, 'w' );
			if ( !$tmpin ) { 
				LogHandler::Log('ImageMagick', 'ERROR', "Conversion failed. Can't write to input file $inputFilename." );
				unlink( $outputFilename );
				unlink( $inputFilename );
				return null;
			}
			fwrite( $tmpin, $data );
			fclose( $tmpin );
		}
		
		self::convertFile( $inputFilename, $outputFilename, $size );

		if ( $filePathUsage ) {
			// Set back to original name.
			rename( $inputFilename, $data ); 
		} else {
			unlink( $inputFilename );
		}	
	
		if ( !file_exists( $outputFilename)) {
			LogHandler::Log('ImageMagick', 'ERROR', "Conversion failed. No output found in $outputFilename." );
			return null;
		}
	
		$tmpout = fopen( $outputFilename, 'rb' );
		if ( !$tmpout ) {
			LogHandler::Log('ImageMagick', 'ERROR', "Conversion failed. Output file $outputFilename can't be opened." );
			unlink( $outputFilename );
			return null;
		}
	
		$fileContent = fread( $tmpout, filesize( $outputFilename ));
		fclose( $tmpout );
		unlink( $outputFilename );
	
		if( strlen( $fileContent ) === 0 ) {
			LogHandler::Log('ImageMagick', 'ERROR', "Conversion failed. Output file $outputFilename is empty." );
			return null;
		}
		
		return $fileContent;
	}

	/**
	 * Renames a file by adding an extension. If the renaming fails the old file name is returned.
	 * @param string $oldFilename	Original file name
	 * @param string $ext			Extension
	 * @return string new file name.	 
	 */
	public static function addExtension( $oldFilename, $ext )
	{
		$rename = rename( $oldFilename, $oldFilename.$ext );
		if ( !$rename ) {
			LogHandler::Log('ImageMagick', 'ERROR', "Renaming $oldFilename failed. Trying to continue." );
			$newFilename = $oldFilename;
		} else {	
			$newFilename = $oldFilename.$ext;
		}

		return $newFilename;
	}	

	/**
	 * Converts the given input image to the given size and writes it to the given output file.
	 * Build a command line to call ImageMagick.
	 *
	 * @param string 	$inputFilename	Full path of image to convert
	 * @param string 	$outputFilename	Full path of destination image
	 * @param int 		$imageSize		Maximum width/height for the resulting image
	 * @return int		0 on success else error code on failure
	*/
	protected static function convertFile( $inputFilename, $outputFilename, $imageSize )
	{
		$size = intval( $imageSize ); // robustness: just to make sure having numeric value
		$cmdline = self::makeExecutable( IMAGE_MAGICK_APP_PATH, 'convert' ) . ' ';
		if( LogHandler::debugMode() ) {
			$cmdline .= '-verbose ';
		}
		$cmdline .= escapeshellarg( $inputFilename ).'[0] '; // adding [0] to the filename to take the first layer only.
		// Ensures that limit is applied to both sides and it only scales down. Fix: Added double quotes (BZ#10971)
		$cmdline .= '-size '.$size.' -thumbnail '.escapeshellarg($size.'x'.$size.'>'). ' ';
		// BZ#31389 - Added option -layers merge to fix the converted black background image.
		if ( defined( 'IMAGE_MAGICK_OPTIONS' ) ) {
			$cmdline .= ' '.IMAGE_MAGICK_OPTIONS.' ';	
		} else { // Fall back
			$cmdline .= ' -colorspace sRGB -quality 92 -sharpen 5 -layers merge -depth 8 -strip -density 72x72 ';
		}	
		$cmdline .= escapeshellarg( $outputFilename );
		return self::imageMagickCmd( 'convert', $cmdline );
	}

	/**
	 * Returns ImageMagick version information.
	 * 
	 * @return string Version info. Empty string on error.
	*/
	public static function getImageMagicksVersionInfo()
	{
		// Bail out when ImageMagick is not even configured.
		if( !IMAGE_MAGICK_APP_PATH || !is_dir( IMAGE_MAGICK_APP_PATH ) ) {
			return ''; // error
		}
		
		self::setEnvironment();
		$executable = self::makeExecutable( IMAGE_MAGICK_APP_PATH, 'convert' );
		$imVersion = shell_exec( $executable . ' -version' );
		if( empty( $imVersion ) ) {
			return ''; // error
		}
		$imVersion = mb_convert_encoding( $imVersion, 'UTF-8' );
		
		// cut-off too much info (to deal with 255 chars limit at plug-in description field).
		$imVersion = str_replace( 'Version: ', '', $imVersion ); // remove prefix
		$tooMuchPos = strpos( $imVersion, 'http://' );
		if( $tooMuchPos ) { // remove copyright and additional info
			$imVersion = substr( $imVersion, 0, $tooMuchPos );
		}
		return $imVersion;
	}

	/**
	 * Returns Ghostscript version information.
	 * 
	 * @return string Version info. Empty string on error.
	*/
   public static function getGhostScriptVersionInfo()
	{
		// Bail out when GhostScript is not even configured.
		if ( !GHOST_SCRIPT_APP_PATH ) {
			return ''; // error
		}

		self::setEnvironment();

		if ( OS == 'WIN' ) {
			if ( is_dir( GHOST_SCRIPT_APP_PATH ) ) { 
				$executable = self::makeExecutable( GHOST_SCRIPT_APP_PATH, 'gswin32c.exe' );
			} else { //Full path to the executable
				$executable = self::makeExecutable( dirname( GHOST_SCRIPT_APP_PATH ), basename( GHOST_SCRIPT_APP_PATH ) );
			}
		} else {
			$executable = self::makeExecutable( GHOST_SCRIPT_APP_PATH, 'gs' );
		}
		$gsVersion = shell_exec( $executable.' -v' );
		if ( empty( $gsVersion ) ) {
			return ''; // error
		}
		$gsVersion = mb_convert_encoding( $gsVersion, 'UTF-8' );

		// cut-off too much info (to deal with 255 chars limit at plug-in description field).
		$tooMuchPos = strpos( $gsVersion, 'Copyright' );
		if ( $tooMuchPos ) {  // remove copyright info
			$gsVersion = substr( $gsVersion, 0, $tooMuchPos );
		}
		return $gsVersion;
	}

	/**
	 * Returns ImageMagick and Ghostscript version information.
	 * 
	 * @return string Version info. Empty string on error.
	*/
	public static function getVersions()
	{
		// Calling shell_exec is pretty expensive, so we cache here
		static $versionInfo = '';
		static $calledBefore = false;
		if( $calledBefore ) {
			return $versionInfo;
		}
		$calledBefore = true;
		
		// Get ImageMagick version
		$imVersion = self::getImageMagicksVersionInfo();
		if( empty( $imVersion ) ) {
			return ( $versionInfo = '' ); // Error.
		}

		// get Ghostscript version
		$gsVersion = self::getGhostScriptVersionInfo();
		if( empty( $gsVersion ) ) {
			return ( $versionInfo = '' ); // Error.
		}

		// Return found versions of both products.
		return ( $versionInfo = $imVersion.', '.$gsVersion );
	}
	
	/**
	 * Prepares environment variables to call ImageMagick.
	*/
	protected static function setEnvironment()
	{
		// Optimization: Avoid setting (and logging) variables twice within same session
		static $calledBefore = false;
		if( $calledBefore ) { 
			return; 
		}
		$calledBefore = true;

		// Only applicable for non-Windows environments.
		if( OS == 'WIN' ) {
			return;
		}

		// Update PATH setting for shell when execution paths are missing
		$curPath = getenv( 'PATH' );
		if ( strpos( $curPath, GHOST_SCRIPT_APP_PATH ) === FALSE ) {
			$curPath = GHOST_SCRIPT_APP_PATH.':'.$curPath;
			putenv( "PATH=$curPath" ); //Only possible when safe_mode_allowed_env_vars is empty, or contains 'PATH'.
		}
		if ( strpos( $curPath, IMAGE_MAGICK_APP_PATH ) === FALSE ) {
			$curPath = IMAGE_MAGICK_APP_PATH.':'.$curPath;
			putenv( "PATH=$curPath" ); //Only possible when safe_mode_allowed_env_vars is empty, or contains 'PATH'.
		}

		// Set the MAGICK_HOME, DYLD_LIBRARY_PATH and LD_LIBRARY_PATH environment variables
		// to make sure global variables get overruled by the IM/GS installation picked by admin
		// user as specified at IMAGE_MAGICK_APP_PATH and IMAGE_MAGICK_APP_PATH.
		putenv( "MAGICK_HOME=".IMAGE_MAGICK_APP_PATH );

		/*
		 * It looks like the DYLD_LIBRARY_PATH setting is not necessary or
		 * even causes ImageMagick not to load. If it turns out that if this setting
		 * is needed you have two possibilities. First is to set a absolute path
		 * and second to set a relative path.
		 * Remove either the comments of the absolute path or the relative path.
		 */
		// Absolute path
		/*
		$parts = explode( '/', GHOST_SCRIPT_APP_PATH );
		array_pop( $parts ); // remove the 'bin' folder at the end of path
		$libPath = implode( '/', $parts ).'/lib';
		putenv( "DYLD_LIBRARY_PATH=$libPath" );
		*/

		// Relative path (to the executable)
		/*
		putenv( "DYLD_LIBRARY_PATH=@executable_path/../lib" );
		*/

		$parts = explode( '/', IMAGE_MAGICK_APP_PATH );
		array_pop( $parts ); // remove the 'bin' folder at the end of path
		$libPath = implode( '/', $parts ).'/lib';
		putenv( "LD_LIBRARY_PATH=$libPath" );

		/*
		COMMENTED OUT, BUT PLEASE KEEP THIS IMPORTANT INFO HERE !!

		Below shows the old solution, which did NOT work for IM installations using MacPorts!

		$imPath = '/usr/local';
		$binPath = $imPath . '/bin';
		$libPath = $imPath . '/lib';

		putenv( "MAGICK_HOME=$imPath" );
		putenv( "LD_LIBRARY_PATH=$libPath" );
		putenv( "DYLD_LIBRARY_PATH=$libPath" );

		Reason why it did not work is that GS was installed at /usr/local/bin while IM was
		installed at /opt/local/bin. By making some hard-coded changes shown below made it work,
		but obviously breaking IM installations made at /usr/local/bin for other machines.

			putenv( "MAGICK_HOME=/opt/local/bin" );
			putenv( "LD_LIBRARY_PATH=/opt/local/lib" );
			putenv( "DYLD_LIBRARY_PATH=/usr/local/lib" ); // GS!

		To fix all this, the IMAGE_MAGICK_APP_PATH and GHOST_SCRIPT_APP_PATH settings are introduced
		since Enterprise v8.0. This also makes it possible to have several IM/GS installations
		on one machine of which just one is picked by any particular Enterprise AS.

		Note: When the MAGICK_HOME, LD_LIBRARY_PATH and DYLD_LIBRARY_PATH were NOT set, it
		surprisingly seemed to work well for Mac. Nevertheless, just to avoid breaking anything,
		those settings are now derived from IMAGE_MAGICK_APP_PATH and GHOST_SCRIPT_APP_PATH.
		Having those environment variables is recommended at the IM website anyway.
		*/
	}

	/**
	 * Returns related environment variables used at command shell to run ImageMagick and GhostScript.
	 * Typically used for debugging and fixing configuration problems.
	 * 
	 * @return string Overview of the environment variables.
	 */
	static public function getEnvVarsHTML()
	{
		self::setEnvironment();
		$output = 'PATH='.getenv( 'PATH' ).'<br/>';
		$output .= 'MAGICK_HOME='.getenv( 'MAGICK_HOME' ).'<br/>';
		$output .= 'DYLD_LIBRARY_PATH='.getenv( 'DYLD_LIBRARY_PATH' ).'<br/>';
		$output .= 'LD_LIBRARY_PATH='.getenv( 'LD_LIBRARY_PATH' ).'<br/>';
		$output = mb_convert_encoding( $output, 'UTF-8' );
		return $output;
	}

	/**
	 * Executes an ImageMagick command.
	 * 
	 * @param string $cmd ImageMagick command to execute, used for debug purposes.
	 * @param string $cmdline Full ImageMagick command including arguments.
	 * @param boolean $log Log errors or leave it up to the caller.
	 * @return int 0 on success else error code on failure
	*/
	protected static function imageMagickCmd( $cmd, $cmdline, $log=true )
	{
		self::setEnvironment();
		LogHandler::Log('ImageMagick', 'INFO', 'Running ImageMagick command: '.$cmdline );
		$outputArray = array();
		$value = 0;
		PerformanceProfiler::startProfile( "ImageMagick - $cmd", 3 );
		exec( $cmdline, $outputArray, $value );
		PerformanceProfiler::stopProfile( "ImageMagick - $cmd", 3 );
		if ( $value && $log ) {
			LogHandler::Log('ImageMagick', 'ERROR', "ImageMagick command failed. Error code $value. Cmd: $cmd." );
		}

		//Notes:
		//a) Note that when in 'Safe Mode' you must have the script or program
		//you are trying to execute in the 'safe_mode_exec_dir'.
		//You can find out what this directory is by using phpinfo().

		//b) if it takes longer than 30 seconds to complete,
		//exec fails with an error code of 155 and the process would be killed.
		//The solution to this problem is to set_time_limit() to a reasonable value

		if( LogHandler::debugMode() && $log ) {
			$msg = '';
			if ( $outputArray ) foreach( $outputArray as $o ) {
				$msg .="<pre>$o</pre>";
			}
			if( !empty($msg) ) {
				LogHandler::Log('ImageMagick', 'DEBUG', 'Output ImageMagick command: '.$msg );
			}
		}
		return $value;
	}

	/**
	 * Use the ImageMagick command 'identify' to retrieve basic meta data.
	 * Split the output into lines containing "keyword:value"
	 *
	 * @param string $fileName Full path to the file.
	 * @param array $metaData Array with key/value pairs of Enterprise properties and their values.
	 * @return bool Success when metadata is extracted else false.
	 */
	public static function getBasicMetaData( $fileName, &$metaData  )
	{
		$outputFilename = self::createOutputFile();
		$format = ' -units PixelsPerInch -format "Format:%m\nColorSpace:%[colorspace]\nDpi:%x\nHeight:%[height]\nWidth:%[width]" ';
		$executable = self::makeExecutable(IMAGE_MAGICK_APP_PATH, 'identify');
		$cmdline = $executable. ' -verbose -define jpeg:size=64x64 '.$format.escapeshellarg( $fileName.'[0]' ).' > '.
					escapeshellarg($outputFilename);
		$ret = self::imageMagickCmd( 'identify', $cmdline );
		if ( $ret ) {
			LogHandler::Log( 'ImageMagick_MetaData', 'INFO', 'No basic metadata extracted from the image.' );
			return false;
		}
		$output = self::readAndCloseOutPutFile( $outputFilename );
		if ( !$output ) {
			return false;
		}
		$output = mb_convert_encoding( $output, 'UTF-8' );
		LogHandler::Log('ImageMagick', 'DEBUG', 'ImageMagick identify output:<br/><pre>'.$output.'</pre>' );
	
		$lines = explode( "\n", $output );
		if ( $lines ) foreach ( $lines as $line ) {
			if ( $line ) { // Skip empty lines (especially the last one).
				$keyValue = explode( ':', $line );
				switch ( $keyValue[0] ) {
					case 'Format':
						$metaData['Format'] = $keyValue[1] ? self::translateFormatToMimeType( $keyValue[1] ) : '';
						break;
					case 'Width':
					case 'Height':
					case 'Dpi':
						// ImagickMagick previously to v6.8.7 returns 'X=300 PixelsPerInch' instead of 'X=300'. EN-86551
						$valueUnit = explode( ' ', $keyValue[1] );
						$metaData[$keyValue[0]] = $valueUnit[0];
						break;
					default:
						$metaData[$keyValue[0]] = $keyValue[1];
				}
			}
		}
	
		return true;
	}

	/**
	 * Returns the Mime Type based on the format of the file.
	 *
	 * @param string $format Format of the file.
	 * @return string Mime Type
	 */
	static private function translateFormatToMimeType( $format ) {
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		return MimeTypeHandler::fileExt2MimeType( '.'.strtolower( $format ) );
	}

	/**
	 * Tries to extract the XMP metadata from an (image) file. After extracting the XMP data is mapped to Enterprise
	 * metadata properties.
	 *
	 * @param string $fileName Full path to the file.
	 * @param array $metaData Array with key/value pairs of Enterprise properties and their values.
	 * @return bool Success when metadata is extracted else false.
	 */
	static public function getXMPMetaData( $fileName, &$metaData )
	{
		$outputFilename = self::createOutputFile();
		$executable = self::makeExecutable(IMAGE_MAGICK_APP_PATH, 'convert');
		$cmdline = $executable. ' -ping '.escapeshellarg($fileName.'[0]').' xmp:'.escapeshellarg( $outputFilename );
		$ret = self::imageMagickCmd( 'convert', $cmdline, false );
		if ( $ret ) {
			LogHandler::Log( 'ImageMagick_MetaData', 'INFO', 'No XMP metadata extracted from the image.' );
			return false;
		}
		$output = self::readAndCloseOutPutFile( $outputFilename );
		if ( !$output ) {
			return false;
		}

		$xmpData = null;
		try {
			$xmpData = @new SimpleXMLElement( $output );
		} catch( Exception $e) {
			$printData = str_replace( '<', "<br/>&lt;", $output );
			LogHandler::Log( 'ImageMagick_MetaData', 'WARN', 'XMP invalid XML: '.$e->getMessage."<code>$printData</code>" );
			return false;
		}

		if( $xmpData != null ) {
			require_once BASEDIR.'/server/utils/FileMetaDataToProperties.class.php';
			$converterXMP = WW_Utils_FileMetaDataToProperties_Factory::createConverter( 'xmp' );
			$converterXMP->convert( $xmpData, $metaData);
			if( !empty( $metaData ) ) {
				if( !array_key_exists( 'FileSize', $metaData ) ) {
					$metaData['FileSize'] = filesize( $fileName );
				}
				LogHandler::Log('ImageMagick_MetaData', 'DEBUG', 'Using XMP metadata '.print_r( $metaData, 1));
			}
		} else {
			LogHandler::Log('ImageMagick_MetaData', 'DEBUG', "Couldn't read XMP");
			return false;
		}

		return true;
	}

	/**
	 * Tries to extract the IPTC metadata from an (image) file. After extracting the IPTC data is mapped to Enterprise
	 * metadata properties.
	 *
	 * @param string $fileName Full path to the file.
	 * @param array $metaData Array with key/value pairs of Enterprise properties and their values.
	 * @return bool Success when metadata is extracted else false.
	 */
	static function getIPTCMetaData( $fileName, &$metaData )
	{
		$outputFilename = self::createOutputFile();
		$executable = self::makeExecutable(IMAGE_MAGICK_APP_PATH, 'convert');
		$cmdline = $executable. ' -ping '.escapeshellarg($fileName.'[0]').' iptc:'.escapeshellarg($outputFilename);
		$ret = self::imageMagickCmd( 'convert', $cmdline, false );
		if ( $ret ) {
			LogHandler::Log( 'ImageMagick_MetaData', 'INFO', 'No IPTC metadata extracted from the image.' );
			return false;
		}
		$output = self::readAndCloseOutPutFile( $outputFilename );
		if ( !$output ) {
			return false;
		}

		$iptcData = iptcparse( $output );

		// Map the IPTC onto Enterprise properties.
		if( $iptcData && is_array($iptcData) ) {
			require_once BASEDIR.'/server/utils/FileMetaDataToProperties.class.php';
			$converterIPTC = WW_Utils_FileMetaDataToProperties_Factory::createConverter( 'iptc' );
			$converterIPTC->convert( $iptcData, $metaData);
			LogHandler::Log( 'ImageMagick_MetaData', 'DEBUG', 'Found IPTC data.' );
		} else {
			LogHandler::Log( 'ImageMagick_MetaData', 'DEBUG', 'Did not find IPTC data.' );
			return false;
		}

		return true;
	}

	/**
	 * Based on the path (defines) and the executable name a command is returned.
	 * How it is returned depends on the OS. For MS Windows the full path (including
	 * the executable) must be double quoted.
	 * @param string $path
	 * @param string $executable
	 * @return string OS specific command
	*/
	static protected function makeExecutable($path, $executable)
	{
		if (empty($path)) {
			return $executable;
		}
		
		$fullpath = $path.'/'.$executable;
		
		if( OS == 'WIN' ) {
			$fullpath = '"'.$fullpath.'"';
		}
		
		return $fullpath;
	}

	/**
	 * Creates a file to store the image metadata extracted by ImageMagick.
	 *
	 * @return bool|string false if no file is created else the filename.
	 */
	static private function createOutputFile()
	{
		$outputFilename = tempnam( sys_get_temp_dir(), 'ent' );
		$filePointer = fopen( $outputFilename, 'w' );
		if ( !$filePointer ) {
			LogHandler::Log('ImageMagick', 'ERROR', "Can't write to output file $outputFilename." );
			return false;
		}
		fclose( $filePointer );

		return $outputFilename;
	}

	/**
	 * Reads the content of the file and after that removes the file.
	 *
	 * @param string $outputFilename The full path to the file.
	 * @return bool|string False in case the reading fails else the content of the file.
	 */
	static private function readAndCloseOutPutFile( $outputFilename )
	{
		$outputFileHandler = fopen( $outputFilename, 'r' );
		if ( !$outputFileHandler ) {
			LogHandler::Log('ImageMagick', 'ERROR', "Can't write to output file $outputFilename." );
			return false;
		}

		$output = fread( $outputFileHandler, filesize( $outputFilename ));
		fclose( $outputFileHandler );
		unlink( $outputFilename );

		return $output;
	}
}
