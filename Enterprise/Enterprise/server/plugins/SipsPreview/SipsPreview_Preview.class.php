<?php
/**
 * @since 		v7.0.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
**/
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/Preview_EnterpriseConnector.class.php';

class SipsPreview_Preview extends Preview_EnterpriseConnector
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
			case 'image/jpeg':
			case 'image/pjpeg':
			case 'image/jpg':
			case 'image/gif':
			case 'image/png':
			case 'image/x-png':
				return 10;
				break;
			case 'image/tiff':
			case 'image/x-photoshop':
			case 'image/x-raw':
			case 'application/postscript':
			case 'application/illustrator':
			case 'application/pdf':
				return 8;
				break;
			default:
				return 0;
				break;
		}
	}

	/**
	 * Creates a preview from an attachment.
	 * 
	 * @param Attachment 			$attachment			Contains either the path to image to be converted or the image is passed in memory.
	 * @param int					$max				Max width/height for preview
	 * @param string				$previewFormat		Output parameter to return the format of generated preview.
	 * @param MetaData				$meta				Output parameter, allows to modify meta data, typically for width/height/format/colorspace/dpi
	 * @param BizMetaDataPreview	$bizMetaDataPreview	Instance of BizMetaDataPreview for file caching
	 * 
	 * @return string Buffer with generated preview. Returns null in case of an error.
	 */
	public function generatePreview( Attachment $attachment, $max, &$previewFormat, &$meta, $bizMetaDataPreview )
	{
		LogHandler::Log('SipsPreview', 'DEBUG', 'Creating preview for '.$attachment->Type );
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		// Set the DYLD_LIBRARY to empty. This fixes a library confilct issue that caused SIPS to fail
		$oriDyldLibPath = getenv( "DYLD_LIBRARY_PATH" ); // Get original DYLD_LIBRARY_PATH varibale value
		putenv( "DYLD_LIBRARY_PATH=" );

		// Work from disk or from memory.
		if( $attachment->FilePath ) { // happens for native file, to create preview for
			$file = $attachment->FilePath;
			$filePathUsage = true;
		} else { // happens for preview, to create thumnail for
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$file = $transferServer->getContent($attachment);
			$filePathUsage = false;
		}
		
		require_once dirname(__FILE__) . '/SipsUtils.class.php';
		$preview = SipsUtils::convertData( $file, $max, $meta, $filePathUsage );
		putenv( "DYLD_LIBRARY_PATH=$oriDyldLibPath" ); // Restore back the original DYLD_LIBRARY_PATH value
		if( $preview ) {
			// A trick to know that whether it is the first time preview genaration or not
			if( isset($meta->sips_hack_preview) ) {
				unset( $meta->sips_hack_preview ); // unset the temporary variable after second preview generated
			} else {
				$meta->sips_hack_preview = 1; // Set the variable value, to identify it is the first time preview
			}
			$previewFormat = 'image/jpeg';
			return $preview;
		}
		return null;
	}
}
