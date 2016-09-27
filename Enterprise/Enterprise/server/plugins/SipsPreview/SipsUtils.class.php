<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Facade to Sips for preview/thumb generation.
 *
**/
require_once BASEDIR.'/config/config_sips.php';

class SipsUtils
{
	/**
	 *	Converts the given image data to the given size in JPG.
	 *  Image can be passed as a path to a file or as stored in memory.
	 *  If passed in memory, first write the image data to disk using a unique temporary name.
	 *  If the image is of the type postscript the image is converted to pdf first before
	 *  it is converted to jpg. Sips can handle pdg but not postscript (eps files).
	 *	Then call convertFile: use Sips to convert the image.
	 *	Finally, read the outputfile from convertFile, and return it.
	 *	Clean up the temporary files.
	 *
	 * @param string 	$data			Image data to convert to JPG
	 * @param int			$size			Maximum width/height for the resulting image
	 * @param MetaData		$meta			Meta data of the image object
	 * @param boolean		$filePathUsage 	The given file is assumed to be a file path (else it is passed in memory)
	 * @return imagedata	Returns JPG image of requested size.
	*/
	public static function convertData( $data, $size, MetaData $meta, $filePathUsage )
	{
		if( $filePathUsage && strlen($data) === 0 ) {
			LogHandler::Log('SipsPreview', 'ERROR', 'Conversion failed. No image data given to convert.' );
			return null;
		}
		
		$outputFilename = tempnam(sys_get_temp_dir(), 'sips-out-');
		
		if ( $filePathUsage ) { // Image is passed as file.
			$inputFilename = $data;	
		} else { // Image is stored in memory, write data to a temporary file.
			$inputFilename = str_replace('sips-out-', 'sips-in-', $outputFilename);
			$tmpin = fopen( $inputFilename, 'wb' );
			if ( !$tmpin ) {
				LogHandler::Log('SipsPreview', 'ERROR', "Can't write to input file $inputFilename." );
				return null;
			}
			fwrite( $tmpin, $data );
			fclose( $tmpin );
		}
		
		// We don't want to delete the input file when it was a file given by caller.
		// However, we DO want to delete it when the caller passed us file data (no path)
		// for which we have created a temp file ourself.
		$cleanupInputFile = !$filePathUsage;
	
		// Convert postscript files to pdf first.
		if( $meta->ContentMetaData->Format == 'application/postscript' && !isset($meta->sips_hack_preview)) {
			$outFilenamePsToPdf = str_replace('sips-out-', 'sips-in-pdf-', $outputFilename);
			$outFilenamePsToPdf = self::convertPS2PDF( $inputFilename, $outFilenamePsToPdf );
			if ( $cleanupInputFile ) {
				unlink( $inputFilename );
			}
			if( is_null($outFilenamePsToPdf) ) { // Convert to pdf failed.
				return null;
			}
			$inputFilename = $outFilenamePsToPdf; // Pdf file becomes the input file. 
			
			// Regardless of the caller passed us file data or a file path, the $inputFilename 
			// now points to a temp file created by us. So remember to delete after usage.
			$cleanupInputFile = true;
		}

		// Call Sips to generate image preview
		$orientation = isset($meta->ContentMetaData->Orientation) ? $meta->ContentMetaData->Orientation : 1;
		self::convertFile( $inputFilename, $outputFilename, $size, $orientation );
		if ( $cleanupInputFile ) {
			unlink( $inputFilename );
		}

		if ( !file_exists( $outputFilename)) {
			LogHandler::Log('SipsPreview', 'ERROR', "Conversion failed. No output found in $outputFilename." );
			return null;
		}
	
		$tmpout = fopen( $outputFilename, 'rb' );
		if ( !$tmpout ) {
			LogHandler::Log('SipsPreview', 'ERROR', "Output file $outputFilename can't be opened." );
			return null;
		}

		$fileContent = fread( $tmpout, filesize( $outputFilename ));
		fclose( $tmpout );
		unlink( $outputFilename );
		
		if( strlen( $fileContent ) === 0 ) {
			LogHandler::Log('SipsPreview', 'ERROR', "Conversion failed. Output file $outputFilename is empty." );
			return null;
		}

		return $fileContent;
	}

	/**
	 * Converts the given input image to the given size and writes it to the given output file.
	 * Build a command line to call Sips.
	 *
	 * @param string 	$inputfilename	Full path of image to convert
	 * @param string 	$outputfilename	Full path of destination image
	 * @param int 		$size			Maximum width/height for the resulting image
	 * @param int     $orientation EXIF orientation flag
	 * @return int		0 on success, error code on failure
	*/
	private static function convertFile( $inputfilename, $outputfilename, $size, $orientation )
	{
		// delete any previous export files so that we can detect if export was succesful
		if (file_exists($outputfilename)) {
			unlink($outputfilename);
		}

		$newWidth = null;
		$newHeight = null;
		$width = self::getImageSize( $inputfilename, 'Width' );
		$height = self::getImageSize( $inputfilename, 'Height' );
		self::ReDimensionJPEG( $width, $height, $size, $newWidth, $newHeight );
		
		$cmd = SIPS_COMMAND . ' -s format jpeg -s dpiHeight 72 -s dpiWidth 72' . " -z $newHeight $newWidth" .
			' -m ' . escapeshellarg(SIPS_RGB_PROFILE) . ' ' . self::composeOrientationCmdParams( $orientation ) .
			escapeshellarg($inputfilename) . ' --out ' . escapeshellarg($outputfilename) . ' 2>&1';
		
		LogHandler::Log('SipsPreview', 'DEBUG', "Sips command line: $cmd" );

		$returnVar = 0;
		$output = array();
		PerformanceProfiler::startProfile( "Sips", 3 );
		/*$result =*/ exec( $cmd, $output, $returnVar );
		PerformanceProfiler::stopProfile( "Sips", 3 );
	}

	/**
	 * Get the image size from sips command line.
	 *
	 * @param string 	$inputfilename	Full path of image to convert
	 * @param string    $type			Type of size, width or height
	 * @return int		$size		    Return size
	*/
	private static function getImageSize( $inputfilename, $type )
	{
		$cmd = SIPS_COMMAND . ' -g pixel' . $type . ' ' . escapeshellarg($inputfilename) . ' 2>&1';
		$returnVar = 0;
		$output = array();
		exec( $cmd, $output, $returnVar );
		$pos = strpos($output[1], ':');
		return intval(trim(substr( $output[1], $pos+1 )));		
	}

	// Determine new width and height for JPEG image
	private static function ReDimensionJPEG( $width, $height, $max, &$newWidth, &$newHeight )
	{
		if( $width <= $max && $height <= $max ) {
			$newWidth = $width;
			$newHeight = $height;
		} else {
			if( $width > $height ) {
				if( $width > $max ) {
					$formule = $width / $max;
					$newWidth = $width / $formule;
					$newHeight = $height / $formule;
				}
			} else {
				if( $height > $max ) {
					$formule = $height / $max;
					$newWidth = $width / $formule;
					$newHeight = $height / $formule;
				}
			}
		}
	}

	/**
	 * Returns html string with Sips version information.
	 * In case of error null is returned.
	*/
	public static function getVersions() 
	{
		$cmd = SIPS_COMMAND;
		$output = array();
		$returnVar = 0;
		exec( $cmd, $output, $returnVar );
		if( !empty($output) ) {
			$versionInfo = PHP_EOL . PHP_EOL . $output[0];
		} else {
			$versionInfo = null;
		}
		return $versionInfo;
	}

	/**
	 * Convert a postscript (EPS) file to PDF file. Should be called before calling a sips command
	 * since sips is not able to process postscript files. This EPS-PDF conversion trick solves the problem.
	 * 
	 * @param string $inputfilename Full path of the EPS image to convert.
	 * @param string $outFilenamePsToPdf Full path of file used to store the converted postscript file.
	 * @return string|null Full path of the converted DPF image. NULL on error.
	*/
	private static function convertPS2PDF( $inputfilename, $outFilenamePsToPdf )
	{
		$cmd = PS2PDF_COMMAND . ' ' . escapeshellarg($inputfilename) . ' -o ' . escapeshellarg($outFilenamePsToPdf) . ' 2>&1';
		LogHandler::Log( 'SipsPreview', 'DEBUG', "Sips command line: $cmd" );
		$output = null;
		$returnVar = 0;
		exec( $cmd, $output, $returnVar );
		if( $returnVar !== 0 ) {
			LogHandler::Log('Sips', 'ERROR', 'The pstopdf command has failed: ' . implode( PHP_EOL, $output ) );
			$result = null;
		} else {
			$result = $outFilenamePsToPdf;
		}
		return $result;
	}

	/**
	 * Composes command line parameters to pass on to Sips to rotate/mirror an image.
	 *
	 * @param int $orientation How to rotate/mirror the image; EXIF/IFD0 standard with values 1...8
	 * @return string Parameters for the command line. Empty for none.
	 */
	private static function composeOrientationCmdParams( $orientation )
	{
		$cmdParams = '';
		switch( $orientation ) {
			case 1: // Horizontal (normal)
				break;
			case 2: // Mirror horizontal
				$cmdParams = ' -f horizontal ';
				break;
			case 3: // Rotate 180 CW
				$cmdParams = ' -r 180 ';
				break;
			case 4: // Flip vertical
				$cmdParams = ' -f vertical ';
				break;
			case 5: // First flip vertical, then rotate 90 CW
				$cmdParams = ' -r 90 -f vertical '; // these two operations are executed in opposite order!
				break;
			case 6: // Rotate 90 CW
				$cmdParams = ' -r 90 ';
				break;
			case 7: // First mirror horizontal, then rotate 90 CW
				$cmdParams = ' -r 90 -f horizontal '; // these two operations are executed in opposite order!
				break;
			case 8: // Rotate 270 CW
				$cmdParams = ' -r 270 ';
				break;
		}
		return $cmdParams;
	}
}
