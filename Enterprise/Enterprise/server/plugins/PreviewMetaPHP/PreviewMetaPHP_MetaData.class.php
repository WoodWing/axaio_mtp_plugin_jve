<?php
/**
 * @since      v6.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @deprecated v10.1.0 This metadata connector is superseded by the Exiftool @see ExifTool_MetaData and should be removed with 11.0.
 *
 * PHP implementation of meta data reader
**/
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/MetaData_EnterpriseConnector.class.php';

class PreviewMetaPHP_MetaData extends MetaData_EnterpriseConnector
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
		$returnVal = 0;

		switch( $format ) {
			case 'image/jpeg':				// Formats that typically have XMP
			case 'image/pjpeg':
			case 'image/jpg':
			case 'image/jepg':
			case 'image/tiff':
				$returnVal = 8;	// Allow some space to have other plug-ins do it better
				break;
			case 'application/illustrator':
			case 'application/indesign':
			case 'application/incopy':
			case 'application/incopyinx':
			case 'application/incopyicml':
			case 'application/incopyicmt':
			case 'image/x-photoshop':
				$returnVal = 7;	// Allow some space to have other plug-ins do it better
				break;
			case 'application/postscript':
			case 'application/pdf':
				$returnVal = 5;
				break;
			case 'image/png':		// We cannot read PNG & GIF, can contain XMP, but need to be decompressed first
			case 'image/x-png':		// But we can read width/height, so it's a level 1 support :-)
			case 'image/gif':		// For unknown formats we can also give it a try...
			default:
				$returnVal = 1;
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
		LogHandler::Log( 'PreviewMetaPHP_MetaData', 'DEBUG', 'canHandleFormat: '.$returnVal.' , format: '.$format );

		return $returnVal;
	}

	/**
	 * Read metadata from the content of the attachment.
	 * The content can contain different kinds of metadata, XMP, Exif, IPTC. The next precedence is implemented:
	 * XMP, Exif, IPTC. So XMP metadata overrules Exif and IPTC, Exif overrules IPTC.
	 * For InCopy format file, we will iterate all the XMP package to get the correct XMP package. See EN-85926.
	 *
	 * @param Attachment $attachment
	 * @param BizMetaDataPreview $bizMetaDataPreview Instance of BizMetaDataPreview for file caching
	 * @return array key values of meta data
	 */
	public function readMetaData(Attachment $attachment, $bizMetaDataPreview )
	{
		$format = $attachment->Type;
		$filePath = $attachment->FilePath;
		$metaDataXMP = array();
		$metaDataEXIF = array();
		$metaDataIPTC = array();

		// For GIF/PNG we cannot read meta-data, so skip that:
		if( $format == 'image/png' || $format == 'image/x-png' || $format == 'image/gif' ) {
			LogHandler::Log('PreviewMetaPHP_MetaData', 'DEBUG', 'Cannot read metadata for '.$format );
		} else {
			require_once BASEDIR.'/server/utils/XMPParser.class.php';
			require_once dirname(__FILE__) . '/EXIFMeta.class.php';
			require_once dirname(__FILE__) . '/IPTCMeta.class.php';
			
			$isDebug = LogHandler::debugMode();

			// 1. See if we can read XMP. If so, we use that
			$xmps = XMPParser::readXmpFromFile( $filePath );
			if( $xmps ) {
				require_once BASEDIR.'/server/utils/FileMetaDataToProperties.class.php';
				/** @noinspection PhpDeprecationInspection */
				$converterXMP = WW_Utils_FileMetaDataToProperties_Factory::createConverter( 'xmp' );
				if( $this->isInCopyFormat( $format ) ) {
					foreach( $xmps as $xmp ) {
						$converterXMP->convert( $xmp, $metaDataXMP );
						if( $this->isInCopyXmp( $metaDataXMP ) ) {
							break;
						}
						$metaDataXMP = array(); // Reset metaDataXMP
					}
				} else {
					$converterXMP->convert( $xmps[0], $metaDataXMP );
				}

				if( !empty( $metaDataXMP ) ) {
					if( !array_key_exists( 'FileSize', $metaDataXMP ) ) {
						$metaDataXMP['FileSize'] = filesize($filePath);
					}
					if( $isDebug ) { // avoid expensive print_r() below
						LogHandler::Log('PreviewMetaPHP_MetaData', 'DEBUG', 'Using XMP metadata '.print_r( $metaDataXMP, 1));
					}
				}
			} else {
				LogHandler::Log('PreviewMetaPHP_MetaData', 'DEBUG', "Couldn't read XMP");
			}
			
			// 2. Continue and read EXIF
			/** @noinspection PhpDeprecationInspection */
			$metaDataEXIF = EXIFMeta::readEXIF( $format, $filePath );
			if( $isDebug ) { // avoid expensive print_r() below
				LogHandler::Log('PreviewMetaPHP_MetaData', 'DEBUG', 'Read EXIF '.print_r( $metaDataEXIF, 1 ));
			}
			
			// 3. Read IPTC to see if there are any fields we have not found in EXIF or XMP:
			//IPTCMeta::readIPTC( $format, file_get_contents($filePath), $metaData );
			/** @noinspection PhpDeprecationInspection */
			IPTCMeta::readIptcFromFile( $filePath, $metaDataIPTC );
			if( $isDebug ) { // avoid expensive print_r() below
				LogHandler::Log('PreviewMetaPHP_MetaData', 'DEBUG', 'Read IPTC '.print_r( $metaDataIPTC, 1));
			}
		}

		// If the input arrays have the same string keys, then the later value for that key will overwrite
		// the previous one. So XMP overrules EXIF and IPTC, EXIF overrules IPTC. 
		$metaData = array_merge( $metaDataIPTC, $metaDataEXIF, $metaDataXMP );

		// If size still unknown, try another way (works also for GIF/PNG		
		if( !array_key_exists( 'Width', $metaData ) || !array_key_exists( 'Height', $metaData )) {
			$imageSize = getimagesize( $filePath );
			if( $imageSize && count($imageSize) >= 2 ) {
				$metaData['Width']	= $imageSize[0];
				$metaData['Height']	= $imageSize[1];
			}
		}
		return $metaData;
	}

	/**
	 * Check if it is CS6/CC/CC2014 InCopy file or InCopy template file format
	 *
	 * @param string $format File type format
	 * @return bool True|False Return true when it is InCopy file or template file format, false when it is not
	 */
	private function isInCopyFormat( $format )
	{
		$inCopyFormat = array( 'application/incopy', 'application/incopyinx', 'application/incopyicml', 'application/incopyicmt');
		if( in_array($format, $inCopyFormat) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the XMP package is belongs to InCopy type file
	 *
	 * @param array $metaDataXMP Array of metadata read from XMP
	 * @return bool True|False Return true when it is InCopy XMP, false when it is not
	 */
	private function isInCopyXmp( $metaDataXMP )
	{
		if( $metaDataXMP['Format'] == 'application/x-incopy' ) { // InCopy file
			return true;
		}
		return false;
	}
}
