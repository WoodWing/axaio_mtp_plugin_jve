<?php
/**
 * @since 		v6.1
 * @deprecated v10.1.0 This class is deprecated and should be removed with v11.
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Read EXIF metadata properties from a given file.
**/

class EXIFMeta
{
	/**
	 * Read EXIF metadata properties from a given file.
	 * 
	 * @param string $format Format (mime type) of the given file.
	 * @param string $file Full file path to read EXIF metadata from.
	 * @return array Key-values of metadata. Keys are Enterprise property names. Values in UTF-8.
	 */
	public static function readEXIF( $format, $file )
	{
		$metaData = array();
		/** @noinspection PhpDeprecationInspection */
		if( !self::hasEXIFHeader( $format ) ) {
			return $metaData;
		}
		
		$exif = @exif_read_data( $file, null, true, false );
		if( !is_array($exif) ) {
			return $metaData;
		}

		require_once BASEDIR . '/server/utils/UtfString.class.php';
		LogHandler::Log('EXIFMeta', 'DEBUG', 'Found EXIF...' );
		
		foreach( $exif as $key => $section ) {
			foreach( $section as $name => $val ) {
				if( $name ) { // can be 0 for Comment block
					switch( $name ) {
						case 'FileSize': 				// Section 'FILE'
							$metaData['FileSize'] = $val;
							break; 	
						case 'MimeType': 				// Section 'FILE'
							$metaData['Format'] = $val;
							break;
						case 'Height':					// Section 'COMPUTED'
							$metaData['Height']  = $val;
							break; 	
						case 'Width':					// Section 'COMPUTED'
							$metaData['Width']   = $val; 
							break; 	// Section 'COMPUTED'
						case 'IsColor':					// Section 'COMPUTED'
							$metaData['ColorSpace'] = $val == 1 ? 'RGB' : 'CMYK';  // Note: CMYK is a guess, 0 is also used for grayscale
							break; // Section 'COMPUTED'
						case 'Copyright':				// Section 'COMPUTED'
							// From php.net:
							// When an Exif header contains a Copyright note, this itself can contain two values. 
							// As the solution is inconsistent in the Exif 2.10 standard, the COMPUTED section will
							// return both entries Copyright.Photographer and Copyright.Editor while the IFD0 sections
							// contains the byte array with the NULL character that splits both entries. Or just the 
							// first entry if the datatype was wrong (normal behaviour of Exif). The COMPUTED will also
							// contain the entry Copyright which is either the original copyright string, or a comma 
							// separated list of the photo and editor copyright.
							// We don't make this distinction, so we just use copyright which combines both.
							if( $key == 'COMPUTED' ) { // Ignore Copyright from IDF0
								$metaData['Copyright'] = UtfString::smart_utf8_encode($val);
							}
							break;

						// BZ#7059. Do not use the COMPUTED-UserComment as it may be filled with rubish by some camera's. 								
						// case 'UserComment': $fileDescription .= mb_convert_encoding( $val, "UTF-8" ) . " "; 
						//    break;

						// BZ#7059 Added the following to correctly get the encoding of IFD0-UserComment (!!!)
						// case 'UserCommentEncoding':
						//    $usercommentencoding = $val;
						//    break;
						// case 'UserComment':				// Section 'IFD0'
						//    $usercomment = $val;	
						//    break;
											
						case 'Artist':					// Section 'IFD0'
							$metaData['Author']	= UtfString::smart_utf8_encode($val);
							break;
						case 'ImageDescription':		// Section 'IFD0'
							$metaData['Description'] = UtfString::smart_utf8_encode($val);
							break;
						case 'XResolution':				// Section 'IFD0'
							if( strcasecmp( $key, 'THUMBNAIL' ) ) { // BZ#26349: Section 'THUMBNAIL' has XResolution as well which will be omitted.
								$imageXResolution = $val;
								$res = explode("/", $imageXResolution);
								$metaData['Dpi'] = $res[0] / $res[1];
								// $imageXResolution .= " dpi";
							}
							break;
							
						// case DateTimeOriginal:
						//    $metaData['Author'] = UtfString::smart_utf8_encode($val);
						//    break;
						
					} // if
				} // switch
			} // foreach
		} // foreach
		
		// BZ#7059 Implementing reading usercomment, this is al so weird... have a look at the documentation of the exif_read_data-function
		// if (!empty($usercommentencoding) && !empty($usercomment)) {
		// 		$temp_usercomment = substr($usercomment,strlen($usercommentencoding));
		// 		$utf8_usercomment = mb_convert_encoding($temp_usercomment, 'UTF-8', $usercommentencoding);
		// 	
		// 		if (!empty($utf8_usercomment)) {
		// 			appendIfNotPresent($fileDescription, $utf8_usercomment);
		//		}
		// }
		return $metaData;
	}
	
	/**
	 * Determines based on mime if file should have EXIF header info (JPEG/TIFF)
	 * 
	 * @param string $mimeType
	 * @return boolean TRUE if format has EXIF header.
	 */
	private static function hasEXIFHeader( $mimeType )
	{
		$hasExif = false;
		switch( $mimeType )
		{
			case 'image/pjpeg':
			case 'image/jpeg':
			case 'image/tiff':
				$hasExif = true;
				break;
		}
		return $hasExif;
	}


}