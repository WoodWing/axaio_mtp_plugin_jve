<?php
/**
 * @since      v9.7
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @deprecated v10.1.0 This metadata connector is superseded by the Exiftool @see ExifTool_MetaData and should be removed with 11.0.
 *
 * ImageMagick implementation of meta data reader.
 **/

require_once BASEDIR . '/server/interfaces/plugins/connectors/MetaData_EnterpriseConnector.class.php';

class ImageMagick_MetaData extends MetaData_EnterpriseConnector
{
	/**
	 * canHandleFormat
	 *
	 * @param string 	$format		mime format
	 * @return int		Return if and how well the format is supported.
	 * 				 	 0 - Not supported
	 * 					 1 - Could give it a try
	 * 					 2 - Reasonable
	 * 					 3 - Pretty Good, but slow
	 * 					 4 - Pretty Good and fast
	 * 					 5 - Good, but slow
	 * 					 6 - Good and fast
	 * 					 8 - Very good, but slow
	 * 					 9 - Very good and fast
	 * 					10 - perfect and lightening fast
	 * 					11 - over the top to overrule it all
	 */
	final public function canHandleFormat( $format )
	{
		switch( $format ) {
			case 'image/jpeg':				// Formats that typically have XMP
			case 'image/pjpeg':
			case 'image/jpg':
			case 'image/jepg':
			case 'image/tiff':
			case 'image/x-photoshop':
				$returnVal = 8;
				break;
			case 'application/postscript':
			case 'application/pdf':
				$returnVal = 7;	// Allow some space to have other plug-ins do it better
				break;
			case 'image/png':
			case 'image/x-png':
			case 'image/gif':
				$returnVal = 6;
				break;
			default:
				$returnVal = 0;
				break;
		}

		// Some objects we don't check for metadata properties.
		// This plugin can't handle archives (zip, tar) so we don't
		// want to check those. Especially when they are 12Gb or so.
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$objectType = MimeTypeHandler::filename2ObjType( $format, '' );
		$excludeTypes = array( 'Archive' );
		if( in_array( $objectType, $excludeTypes ) ) {
			$returnVal = 0;
		}
		LogHandler::Log( 'ImageMagick_MetaData', 'DEBUG', 'canHandleFormat: '.$returnVal.' , format: '.$format );

		return $returnVal;
	}

	/**
	 * Read metadata from the content of the attachment.
	 *
	 * @param Attachment 			$attachment
	 * @param BizMetaDataPreview	$bizMetaDataPreview	Instance of BizMetaDataPreview for file caching
	 *
	 * @return array key values of meta data
	 */
	public function readMetaData( Attachment $attachment, $bizMetaDataPreview )
	{
		$format = $attachment->Type;
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$objectType = MimeTypeHandler::filename2ObjType( $format, '' );
		$ext = MimeTypeHandler::mimeType2FileExt( $format, $objectType );
		// Add the extension to the file to circumvent an ImageMagick bug. See BZ#28047.
		require_once dirname( __FILE__ ).'/ImageMagick.class.php';
		$extPos = strripos( $attachment->FilePath, $ext );
		if( $extPos !== false && $extPos + strlen( $ext ) == strlen( $attachment->FilePath ) ) {
			$inputFilename = $attachment->FilePath;
		} else {
			$inputFilename = ImageMagick::addExtension( $attachment->FilePath, $ext ); // does rename!
		}

		$identifyMetaData = array();
		/** @noinspection PhpDeprecationInspection */
		ImageMagick::getBasicMetaData( $inputFilename, $identifyMetaData );
		$xmpMetaData = array();
		/** @noinspection PhpDeprecationInspection */
		ImageMagick::getXMPMetaData( $inputFilename, $xmpMetaData );
		$iptcMetaData = array();
		/** @noinspection PhpDeprecationInspection */
		ImageMagick::getIPTCMetaData( $inputFilename, $iptcMetaData );

		if( strcasecmp( $inputFilename, $attachment->FilePath ) !== 0 ) {
			rename( $inputFilename, $attachment->FilePath );
		}
		$metaData = array_merge( $iptcMetaData, $xmpMetaData, $identifyMetaData );

		return $metaData;
	}
}