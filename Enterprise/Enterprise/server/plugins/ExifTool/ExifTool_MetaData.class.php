<?php
/**
 * @package     Enterprise
 * @subpackage  ExifTool
 * @since       v10.1
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Integrates the ExifTool to read metadata from files.
 *
 * ExifTool documentation: http://www.sno.phy.queensu.ca/~phil/exiftool/exiftool_pod.html
 *
 * Interpreted standards by this connector:
 * - http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/XMP.html
 * - http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/EXIF.html
 * - http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/JFIF.html
 * - http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/IPTC.html
 * - http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/Photoshop.html
 *
 * For most properties, it uses the following fallback path: File => EXIF => IPTC => Photoshop => XMP
 *
 * The reason to put XMP at the end of the fallback is that:
 * - when Adobe ID/PS does update XMP, it updates EXIF/IPTC too
 * - when 3rd party image tool (e.g. Claro) does not support XMP, it updates EXIF/IPTC only
 * So EXIF/IPTC is more 'reliable' than XMP.
 *
 * Mapping to Enterprise properties: https://helpcenter.woodwing.com/hc/en-us/articles/209991626
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/MetaData_EnterpriseConnector.class.php';

class ExifTool_MetaData extends MetaData_EnterpriseConnector
{
	/**
	 * @var array $toolMetaData List of extracted metadata properties read by ExifTool.
	 * Properties are grouped with keys: File, XMP, EXIF, IPTC, Photoshop, Computed, etc
	 * The names and values are taken as-is from ExifTool, without any conversion whatsoever.
	 * See top of this PHP module for references to the supported metadata standards.
	 */
	private $toolMetaData = array();

	/**
	 * @var array $entMetaData List of Enterprise properties the read properties $toolMetaData
	 * could be mapped onto. So the names and the values are converted from the ExifTool world
	 * to the Enterprise world. Properties that could not be mapped are not present. Only mapped
	 * properties are returned to the core server so it can enrich the object being saved with those.
	 */
	private $entMetaData = array();

	/**
	 * {@inheritdoc}
	 */
	final public function canHandleFormat( $format )
	{
		switch( $format ) {
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
			case 'image/tiff':
			case 'image/x-photoshop':
			case 'application/postscript':
			case 'application/illustrator':
			case 'application/pdf':
				$returnVal = 9;
				break;
			default:
				$returnVal = 0;
				break;
		}
		LogHandler::Log( 'ExifTool', 'DEBUG', 'canHandleFormat: '.$returnVal.' , format: '.$format );
		return $returnVal;
	}

	/**
	 * {@inheritdoc}
	 */
	public function readMetaData( Attachment $attachment, $bizMetaDataPreview )
	{
		// Determine the file extension.
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$objectType = MimeTypeHandler::filename2ObjType( $attachment->Type, '' );
		$ext = MimeTypeHandler::mimeType2FileExt( $attachment->Type, $objectType );

		// Temporary add the extension to the file in the Transfer Server folder.
		$extPos = strripos( $attachment->FilePath, $ext );
		$hasExt = $extPos !== false && $extPos + strlen( $ext ) == strlen( $attachment->FilePath );
		if( !$hasExt && rename( $attachment->FilePath, $attachment->FilePath.$ext ) ) {
			$inputFileName = $attachment->FilePath.$ext;
		} else {
			$inputFileName = $attachment->FilePath;
		}

		// Extract embedded metadata properties from the file.
		$metaData = $this->readMetaDataFromFile( $inputFileName );

		// If we did rename the file above, rename it back to the original name.
		if( strcasecmp( $inputFileName, $attachment->FilePath ) !== 0 ) {
			if( !rename( $inputFileName, $attachment->FilePath ) ) {
				$attachment->FilePath = $inputFileName;
				LogHandler::Log( 'ExifTool', 'WARN', 'Could not rename back attachment to original name.' );
			}
		}
		return $metaData;
	}

	/**
	 * Extracts embedded metadata from a given file.
	 *
	 * @param string $inputFileName
	 * @return string[] Key-value list of Enterprise metadata properties.
	 */
	private function readMetaDataFromFile( $inputFileName )
	{
		$arguments = '-q -s -ee -n -g -php -charset UTF8';
		// L> Note that without -ee option we got no JFIF info.
		// L> We do NOT add -b to avoid thumbnail extraction, which is not used and expensive.
		$returnStatus = 0;
		$outputExifTool = self::callExifTool( $arguments.' '.escapeshellarg( $inputFileName ), 'extract metadata', $returnStatus );

		$toolMetaData = array();
		$this->toolMetaData = array();
		eval( '$toolMetaData = '.$outputExifTool );
		if( isset( $toolMetaData[0] ) && is_array( $toolMetaData[0] ) ) {
			$this->toolMetaData = $toolMetaData[0];
			if( LogHandler::debugMode() ) {
				LogHandler::logService( 'ExifTool', print_r( $this->toolMetaData, true ), false, 'CmdLine' );
			}
		} else {
			LogHandler::Log( 'ExifTool', 'ERROR', 'Could not extract metadata.' );
		}

		$this->entMetaData = array();
		$this->mapToEnterpriseMetaData();
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'ExifTool', 'DEBUG', 'Extracted metadata for Enterprise: '.print_r( $this->entMetaData, true ) );
		}
		return $this->entMetaData;
	}

	/**
	 * Retrieve all properties that could be extracted from a file after calling readMetaData().
	 *
	 * The returned properties are raw; they are NOT mapped onto Enterprise metadata property names.
	 *
	 * @return array|null Two-dimensional list of properties.
	 */
	public function getRawMetaData()
	{
		return $this->toolMetaData;
	}

	/**
	 * Composes the full file path of the ExifTool executable.
	 *
	 * @return string ExifTool file path.
	 */
	public static function composeExifToolExecutableFilePath()
	{
		$path = EXIFTOOL_APP_PATH;
		if( $path ) { // could be empty; then rely on global PATH setting
			$path .= '/';
		}
		$path .= (OS == 'WIN') ? 'exiftool.exe' : 'exiftool';
		return $path;
	}

	/**
	 * Runs the ExifTool on the command line with a given set of arguments.
	 *
	 * @param string $arguments Commandline arguments to pass on to the ExifTool. Use escapeshellarg() to compose this.
	 * @param string $cmdInfo Human readable ExifTool operation name, used for logging only.
	 * @param integer $returnStatus The return status of the command.
	 * @return string|null The output returned by the ExifTool. NULL on error.
	 */
	public static function callExifTool( $arguments, $cmdInfo, &$returnStatus )
	{
		// Compose command line.
		$cmdLine = '"'.self::composeExifToolExecutableFilePath().'" '.$arguments;
		LogHandler::Log( 'ExifTool', 'INFO', 'Running ExifTool command: '.$cmdLine );

		// Call ExifTool on command line.
		$output = null;
		PerformanceProfiler::startProfile( "ExifTool - $cmdInfo", 3 );
		ob_start();
		passthru( $cmdLine, $returnStatus );
		$output = ob_get_contents();
		ob_end_clean();
		PerformanceProfiler::stopProfile( "ExifTool - $cmdInfo", 3 );

		// Log errors returned by command.
		if( $returnStatus ) {
			LogHandler::Log( 'ExifTool', 'ERROR', "ExifTool command failed. ".
				"Command: $cmdLine. Error code: $returnStatus. Output:".$output."." );
			$output = null;
		}
		return $output;
	}

	/**
	 * Maps extracted metadata onto Enterprise properties.
	 *
	 * Before calling, all extract properties are expected in $this->toolMetaData.
	 * After calling, the mapped properties can be found in $this->entMetaData.
	 */
	private function mapToEnterpriseMetaData()
	{
		$this->determineDimensionAndResolutionProperties();

		$this->mapFieldValue( 'EXIF', 'Artist', 'Author', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'IPTC', 'By-line', 'Author', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Creator', 'Author', array( $this, 'castToUtf8String' ) );

		// v10.1: The format of the two date fields below differ from xsd:datetime and are not converted properly.
		//        The core server overwrites the creation time stamp anyway, so let's skip them here. (EN-87917)
		//$this->mapFieldValue( 'IPTC', 'DateCreated', 'Created', array( $this, 'castToUtf8String' ) );
		//$this->mapFieldValue( 'XMP', 'CreateDate', 'Created', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'IPTC', 'Credit', 'Credit', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Credit', 'Credit', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'EXIF', 'ColorSpace', 'ColorSpace', array( $this, 'castToColorSpace' ) );
												// L> v10.1: improved (used to be: IsColor == 1 ? 'RGB' : 'CMYK')
		$this->mapFieldValue( 'XMP', 'ColorMode', 'ColorSpace', array( $this, 'castColorModeToColorSpace' ) );

		$this->mapFieldValue( 'XMP', 'Instructions', 'Comment', array( $this, 'castToUtf8String' ) );

		// Do not use the EXIF->UserComment as it may be filled with rubbish by some camera's. (BZ#7059)
		//$this->mapFieldValue( 'EXIF', 'UserComment', 'Comment', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'IPTC', 'SpecialInstructions', 'Comment', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'IPTC', 'UniqueObjectName', 'DocumentID', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'DocumentID', 'DocumentID', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'EXIF', 'Description', 'Description', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'IPTC', 'Caption-Abstract', 'Description', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Description', 'Description', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'IPTC', 'Writer-Editor', 'DescriptionAuthor', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'CaptionWriter', 'DescriptionAuthor', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'EXIF', 'Copyright', 'Copyright', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'IPTC', 'CopyrightNotice', 'Copyright', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Copyright', 'Copyright', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Rights', 'Copyright', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'XMP', 'WebStatement', 'CopyrightURL', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'XMP', 'Marked', 'CopyrightMarked', array( $this, 'castToBooleanWhenValid' ) );

		$this->mapFieldValue( 'File', 'FileSize', 'FileSize', array( $this, 'castToIntegerWhenPositive' ) );

		$this->mapFieldValue( 'File', 'MIMEType', 'Format', array( $this, 'castToFileFormatWhenKnown' ) );
		$this->mapFieldValue( 'XMP', 'Format', 'Format', array( $this, 'castToFileFormatWhenKnown' ) );

		$this->mapFieldValue( 'IPTC', 'Keywords', 'Keywords', array( $this, 'castToListOfUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Keywords', 'Keywords', array( $this, 'castToListOfUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Keyword', 'Keywords', array( $this, 'castToListOfUtf8String' ) ); // v10.1
		$this->mapFieldValue( 'XMP', 'Subject', 'Keywords', array( $this, 'castToListOfUtf8String' ) ); // v10.1

		// Make sure that Rating is an integer (BZ#35029).
		// XMP->Rating supports [0-5] and -1 for rejected. Enterprise supports unsignedInt only (see WSDL).
		// Since v10.1 the -1 value won't be accepted (becomes null).
		$this->mapFieldValue( 'XMP', 'Rating', 'Rating', array( $this, 'castToIntegerWhenZeroOrPositive' ) );

		$this->mapFieldValue( 'IPTC', 'Headline', 'Slugline', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Headline', 'Slugline', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'IPTC', 'Source', 'Source', array( $this, 'castToUtf8String' ) );
		$this->mapFieldValue( 'XMP', 'Source', 'Source', array( $this, 'castToUtf8String' ) );

		$this->mapFieldValue( 'IPTC', 'Urgency', 'Urgency', array( $this, 'castToUtf8String' ) ); // number [0-9] to string

		$this->mapFieldValue( 'EXIF', 'Orientation', 'Orientation', array( $this, 'castToIntegerWhenPositive' ) );
	}

	/**
	 * Resolves the Height, Width and DPI properties for Image objects.
	 */
	private function determineDimensionAndResolutionProperties()
	{
		// Determine width and height. Preference: File => XMP => EXIF => Photoshop
		list( $fileHeight, $fileWidth ) = $this->mapFieldPairValues( 'File', 'ImageHeight', 'ImageWidth',
			'Height', 'Width', array( $this, 'castToFloatWhenPositive' ) );
		list( $exifImageHeight, $exifImageWidth ) = $this->mapFieldPairValues( 'EXIF', 'ExifImageHeight', 'ExifImageWidth',
			'Height', 'Width', array( $this, 'castToFloatWhenPositive' ) );
		// L> Note that ExifTool returns ExifImageWidth/ExifImageHeight which are specified as PixelXDimension/PixelYDimension.
		list( $psImageHeight, $psImageWidth ) = $this->mapFieldPairValues( 'Photoshop', 'ImageHeight', 'ImageWidth',
			'Height', 'Width', array( $this, 'castToFloatWhenPositive' ) );
		list( $pngImageHeight, $pngImageWidth ) = $this->mapFieldPairValues( 'PNG', 'ImageHeight', 'ImageWidth',
			'Height', 'Width', array( $this, 'castToFloatWhenPositive' ) );
		list( $xmpImageHeight, $xmpImageWidth ) = $this->mapFieldPairValues( 'XMP', 'ImageHeight', 'ImageWidth',
			'Height', 'Width', array( $this, 'castToFloatWhenPositive' ) );

		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'ExifTool', 'DEBUG', "Extracted image (height={$this->entMetaData['Height']}, width={$this->entMetaData['Width']}) from tags: ".
				"File($fileHeight, $fileWidth) XMP($xmpImageHeight, $xmpImageWidth) ".
				"EXIF($exifImageHeight, $exifImageWidth) PNG($pngImageHeight, $pngImageWidth) Photoshop($psImageHeight, $psImageWidth)" );
		}

		// To determine the DPI, we need the width and height, so bail out when not found.
		if( !$this->entMetaData['Height'] || !$this->entMetaData['Width'] ) {
			return;
		}

		// Determine the DPI:
		// Let's assume the user has downloaded a photo from a web site. The photo was taken by someone else with high res camera.
		// When the photographer uploaded the photo to that web site the photo was scaled down by a tool that is not aware of EXIF.
		// In this example, the tool has updated the XResolution/YResolution for JFIF, but not for EXIF. It is a challenge to
		// find out which information is most reliable. For that we take the width and height. So in the example, when the
		// ExifImageHeight and ExifImageWidth are not matching with the width and height resolved from the image format ('File' tag)
		// then we assume EXIF is -not- updated and so we also do -not- use its XResolution to determine the DPI value.

		// EXIF
		if( !isset($this->entMetaData[ 'Dpi' ]) ) {
			if( $exifImageHeight == $this->entMetaData['Height'] && $exifImageWidth == $this->entMetaData['Width'] ) { // EXIF reliable?
				$exifXResolution = $this->mapFieldValue( 'EXIF', 'XResolution', null, array( $this, 'castToFloatWhenPositive' ) );
				if( $exifXResolution ) {
					// EXIF: Unit of XResolution/YResolution: '1' = no-unit, '2' = inches, '3' = centimeters.
					$exifResolutionUnit = $this->mapFieldValue( 'EXIF', 'ResolutionUnit', null, array( $this, 'castToIntegerWhenPositive' ) );
					$exifXResolution = $this->convertResolutionToInches( $exifXResolution, $exifResolutionUnit );
					if( $exifXResolution ) {
						$this->entMetaData['Dpi'] = $exifXResolution;
						LogHandler::Log( 'ExifTool', 'DEBUG', 'Derived DPI from EXIF: '.$this->entMetaData['Dpi'] );
					}
				}
			}
		}

		// Photoshop
		if( !isset( $this->entMetaData[ 'Dpi' ] ) ) {
			if( $psImageHeight == $this->entMetaData['Height'] && $psImageWidth == $this->entMetaData['Width'] ) { // Photoshop reliable?
				$psXResolution = $this->mapFieldValue( 'Photoshop', 'XResolution', null, array( $this, 'castToFloatWhenPositive' ) );
				if( $psXResolution ) {
					// Photoshop: Unit of DisplayedUnitsX: '0' = no-unit, '1' = inches, '2' = centimeters.
					$psDisplayedUnitsX = $this->mapFieldValue( 'Photoshop', 'DisplayedUnitsX', null, array( $this, 'castToIntegerWhenZeroOrPositive' ) );
					$psXResolution = $this->convertResolutionToInches( $psXResolution, $psDisplayedUnitsX, true );
					if( $psXResolution ) {
						$this->entMetaData['Dpi'] = $psXResolution;
						LogHandler::Log( 'ExifTool', 'DEBUG', 'Derived DPI from Photoshop: '.$this->entMetaData['Dpi'] );
					}
				}
			}
		}

		// XMP
		if( !isset($this->entMetaData[ 'Dpi' ]) ) {
			if( $xmpImageHeight == $this->entMetaData['Height'] && $xmpImageWidth == $this->entMetaData['Width'] ) { // XMP reliable?
				$xmpXResolution = $this->mapFieldValue( 'XMP', 'XResolution', null, array( $this, 'castToFloatWhenPositive' ) );
				if( $xmpXResolution ) {
					// XMP: Unit of XResolution/YResolution: '1' = no-unit, '2' = inches, '3' = centimeters.
					$xmpResolutionUnit = $this->mapFieldValue( 'XMP', 'ResolutionUnit', null, array( $this, 'castToIntegerWhenPositive' ) );
					$xmpXResolution = $this->convertResolutionToInches( $xmpXResolution, $xmpResolutionUnit );
					if( $xmpXResolution ) {
						$this->entMetaData['Dpi'] = $xmpXResolution;
						LogHandler::Log( 'ExifTool', 'DEBUG', 'Derived DPI from XMP: '.$this->entMetaData['Dpi'] );
					}
				}
			}
		}

		// JFIF: This standard has no width/height specified, so we take this as last fallback to determine the DPI.
		if( !isset($this->entMetaData[ 'Dpi' ]) ) {
			$jfifXResolution = $this->mapFieldValue( 'JFIF', 'XResolution', null, array( $this, 'castToFloatWhenPositive' ) );
			if( $jfifXResolution ) {
				// JFIF: Unit of XResolution/YResolution: '0' = no-unit, '1' = inches, '2' = centimeters.
				$jfifResolutionUnit = $this->mapFieldValue( 'JFIF', 'ResolutionUnit', null, array( $this, 'castToIntegerWhenZeroOrPositive' ) );
				$jfifXResolution = $this->convertResolutionToInches( $jfifXResolution, $jfifResolutionUnit, true );
				if( $jfifXResolution ) {
					$this->entMetaData['Dpi'] = $jfifXResolution;
					LogHandler::Log( 'ExifTool', 'DEBUG', 'Derived DPI from JFIF: '.$this->entMetaData['Dpi'] );
				}
			}
		}
	}

	/**
	 * Maps ExifTool properties ($this->toolMetaData) to Enterprise properties ($this->entMetaData).
	 *
	 * When the property already exists in the target ($this->entMetaData) it won't be overwritten.
	 * This way you can simply try to map the most preferred property first, then second most, etc.
	 * And so, when multiple properties are found, only the most preferred one is respected.
	 *
	 * @param string $group Source: In what group to access the property (File, EXIF, XMP, IPTC, Photoshop, etc) as organised by ExifTool.
	 * @param string $key Source: The name of the extracted metadata property provided by ExifTool.
	 * @param string|null $property Target: The Enterprise metadata property name to be mapped onto.
	 *                    NULL to skip mapping onto $this->entMetaData but just use the return value only.
	 * @param callable $cbCastValue The function called to convert the property value from ExifTool to Enterprise format.
	 * @return mixed|null The Enterprise property value. NULL when not found in $this->toolMetaData or when value could not be converted to Enterprise format.
	 */
	private function mapFieldValue( $group, $key, $property, $cbCastValue )
	{
		$castedValue = null;
		if( isset( $this->toolMetaData[ $group ][ $key ] ) ) {
			$castedValue = call_user_func( $cbCastValue, $this->toolMetaData[ $group ][ $key ] );
			if( !isset( $this->entMetaData[ $property ] ) ) { // prefer earlier found props
				if( !is_null( $castedValue ) && is_string( $property ) ) {
					$this->entMetaData[ $property ] = $castedValue;
				}
			}
		}
		return $castedValue;
	}

	/**
	 * Does the very same as mapFieldValue() but deals with a pair of properties.
	 *
	 * Only when both properties are found, they are mapped. This is convenient for properties
	 * that are strongly related to each other, such as Width and Height. This way, the caller
	 * avoids taking the ImageHeight of EXIF and the ImageWidth of XMP, which could cause troubles
	 * in case both standards (EXIF and XMP) are not entirely complete not updated.
	 *
	 * @param string $group Source: In what group to access both properties (File, EXIF, XMP, IPTC, Photoshop, etc) as organised by ExifTool.
	 * @param string $key1 Source: The name of the 1st extracted metadata property provided by ExifTool.
	 * @param string $key2 Source: The name of the 2nd extracted metadata property provided by ExifTool.
	 * @param string|null $property1 Target: The 1st Enterprise metadata property name to be mapped onto.
	 *                    NULL to skip mapping onto $this->entMetaData but just use the return value only.
	 * @param string|null $property2 Target: The 2nd Enterprise metadata property name to be mapped onto.
	 *                    NULL to skip mapping onto $this->entMetaData but just use the return value only.
	 * @param callable $cbCastValue The function called to convert the property value from ExifTool to Enterprise format.
	 * @return array Two Enterprise properties indexed with 0 and 1. Values could be NULL when not found
	 *               in $this->toolMetaData or when value could not be converted to Enterprise format.
	 */
	private function mapFieldPairValues( $group, $key1, $key2, $property1, $property2, $cbCastValue )
	{
		$castedValues = array( null, null );
		if( isset( $this->toolMetaData[ $group ][ $key1 ] ) && isset( $this->toolMetaData[ $group ][ $key2 ] ) ) {
			$castedValues[0] = call_user_func( $cbCastValue, $this->toolMetaData[ $group ][ $key1 ] );
			$castedValues[1] = call_user_func( $cbCastValue, $this->toolMetaData[ $group ][ $key2 ] );
			if( !isset( $this->entMetaData[ $property1 ] ) && !isset( $this->entMetaData[ $property2 ] ) ) { // prefer earlier found props
				if( !is_null( $castedValues[0] ) && is_string( $property1 ) &&
					 !is_null( $castedValues[1] ) && is_string( $property2 ) ) {
					$this->entMetaData[ $property1 ] = $castedValues[0];
					$this->entMetaData[ $property2 ] = $castedValues[1];
				}
			}
		}
		return $castedValues;
	}

	/**
	 * Converts the given resolution to inches, given a resolution unit.
	 *
	 * @param float $resolution The resolution to convert.
	 * @param integer $unit 1=none, 2=inches, 3=centimeters
	 * @param bool $zeroBased FALSE for 1-based $unit as specified. TRUE for 0-based: 0=none, 1=inches, 2=centimeters
	 * @return float|null Converted resolution. NULL when unsupported unit provided (to indicate should not be mapped to Enterprise).
	 */
	private function convertResolutionToInches( $resolution, $unit, $zeroBased = false )
	{
		if( !is_null($unit) ) {
			if( $zeroBased ) {
				$unit += 1;
			}
			switch( $unit ) {
				case 1: // none (e.g. for JFIF this is used to specify the pixel aspect ratio)
					$resolution = null; // do not use to fall back at default (EN-87972)
					break;
				case 2: // inches
					break;
				case 3: // centimeters
					$resolution = $resolution * 25.4; // convert cm to inches
					break;
				default: // unsupported
					$resolution = null;
			}
		}
		return $resolution;
	}

	/**
	 * Converts a given file format (mime type) to a supported format by Enterprise.
	 *
	 * @param string $value
	 * @return null|string The file format. NULL when it could not be mapped.
	 */
	private function castToFileFormatWhenKnown( $value )
	{
		if( !is_null( $value ) ) {
			// Let's be robust and repair some known formats.
			switch( $value ) {
				case 'image/pjpeg':
				case 'image/jpg':
					$value = 'image/jpeg';
					break;
				case 'image/x-png':
					$value = 'image/png';
					break;
			}

			// Map file format to Enterprise.
			require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
			$fileExt = MimeTypeHandler::mimeType2FileExt( $value );
			if( !$fileExt ) {
				$value = null;
			}
		}
		return $value;
	}

	/**
	 * Converts a given EXIF ColorSpace index value to an Enterprise human readable string.
	 *
	 * @param integer|null $value EXIF ColorSpace index value.
	 * @return string|null Enterprise human readable string. NULL when not mapped.
	 */
	private function castToColorSpace( $value )
	{
		if( !is_null( $value ) ) {
			switch( $value ) {
				case 0x1:    $value = 'sRGB';          break;
				case 0x2:    $value = 'Adobe RGB';     break;
				case 0xfffd: $value = 'Wide Gamut RGB';break;
				case 0xfffe: $value = 'ICC Profile';   break;
				case 0xffff: $value = 'Uncalibrated';  break;
				default: $value = null;                break;
			}
		}
		return $value;
	}

	/**
	 * Converts a given XMP ColorMode index value to an Enterprise human readable string.
	 *
	 * @param integer|null $value XMP ColorMode index value.
	 * @return string|null Enterprise human readable string. NULL when not mapped.
	 */
	private function castColorModeToColorSpace( $value )
	{
		if( !is_null( $value ) ) {
			switch( $value ) {
				case 0: $value = 'Bitmap';    break;
				case 1: $value = 'Grayscale'; break;
				case 2: $value = 'Indexed';   break;
				case 3: $value = 'RGB';       break;
				case 4: $value = 'CMYK';      break;
				case 7: $value = 'Multichannel'; break;
				case 8: $value = 'Duotone';   break;
				case 9: $value = 'Lab';       break;
				default: $value = null;       break;
			}
		}
		return $value;
	}

	/**
	 * Converts a given numeric value to a positive integer.
	 *
	 * @param mixed $value Value to convert.
	 * @return integer|null Positive integer. NULL when not numeric, not positive or when zero.
	 */
	private function castToIntegerWhenPositive( $value )
	{
		$value = intval( $value );
		return $value > 0 ? $value : null;
	}

	/**
	 * Converts a given numeric value to zero or a positive integer.
	 *
	 * @param mixed $value Value to convert.
	 * @return integer|null Positive integer or zero. NULL when not numeric or not positive.
	 */
	private function castToIntegerWhenZeroOrPositive( $value )
	{
		$value = intval( $value );
		return $value >= 0 ? $value : null;
	}

	/**
	 * Converts a given numeric value to a positive float.
	 *
	 * @param mixed $value Value to convert.
	 * @return float|null Positive float. NULL when not numeric, not positive or when zero.
	 */
	private function castToFloatWhenPositive( $value )
	{
		$value = floatval( $value );
		return $value > 0.0 ? $value : null;
	}

	/**
	 * Checks a given boolean value to be true or false.
	 *
	 * @param mixed $value Value to check.
	 * @return boolean|null TRUE or FALSE. NULL when not a boolean.
	 */
	private function castToBooleanWhenValid( $value )
	{
		return is_bool( $value ) ? $value : null;
	}

	/**
	 * Converts a string into a UTF-8 string.
	 *
	 * It respects the MB_ENCODINGS setting and removes illegal Unicode chars.
	 *
	 * @param string $value Value to convert.
	 * @return string Value converted to an UTF-8 string.
	 */
	private function castToUtf8String( $value )
	{
		require_once BASEDIR.'/server/utils/UtfString.class.php';
		return UtfString::smart_utf8_encode( $value );
	}

	/**
	 * Converts a list of strings into a list of UTF-8 strings.
	 *
	 * It respects the MB_ENCODINGS setting and removes illegal Unicode chars.
	 *
	 * @param string[] $values Values to convert.
	 * @return string[]|null Value converted to an UTF-8 string. NULL when no array given.
	 */
	private function castToListOfUtf8String( $values )
	{
		require_once BASEDIR.'/server/utils/UtfString.class.php';
		if( !is_array( $values ) ) {
			$values = null;
		}
		if( $values ) foreach( $values as $key => $value ) {
			$values[$key] = UtfString::smart_utf8_encode( $value );
		}
		return $values;
	}
}