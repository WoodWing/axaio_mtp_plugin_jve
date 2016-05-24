<?php
/**
 * @package 	Enterprise
 * @subpackage 	ImageMagick
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
**/
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/Preview_EnterpriseConnector.class.php';

class ImageMagick_Preview extends Preview_EnterpriseConnector
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
				return 8;
				break;
			case 'image/tiff':
			case 'image/x-photoshop':
			case 'application/postscript':
			case 'application/illustrator':
			case 'application/pdf':
			case 'application/photoshop':
        	case 'application/eps':
				return 9;
				break;
			default:
				return 0;
				break;
		}
	}

	/**
	 * Creates a preview from an attachment.
	 * 
	 * @param Attachment 			$attachment		    Attachment containing image data to convert to JPG.
	 *                                                  Either in memory or a file path.
	 * @param int					$max				Max width/height for preview
	 * @param string				$previewFormat		Output parameter to return the format of generated preview.
	 * @param MetaData				$meta				Output parameter, allows to modify meta data, typically for width/height/format/colorspace/dpi
	 * @param BizMetaDataPreview	$bizMetaDataPreview	Instance of BizMetaDataPreview for file caching
	 * 
	 * @return string Buffer with generated preview. Returns null in case of an error.
	 */
	public function generatePreview(Attachment $attachment, $max, &$previewFormat, &$meta, $bizMetaDataPreview )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$bizMetaDataPreview = $bizMetaDataPreview;

		LogHandler::Log('ImageMagick', 'DEBUG', 'Creating preview for '.$attachment->Type );
		require_once dirname(__FILE__) . '/ImageMagick.class.php';
		// Work from disk or from memory.
		if( $attachment->FilePath ) { // happens for native file, to create preview for
			$file = $attachment->FilePath;
			$filePathUsage = true;
		} else { // Happens for preview, to create thumbnail.
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$file = $transferServer->getContent($attachment);
			$filePathUsage = false;
		}
		$preview = ImageMagick::convertData($file, $max, $meta, $filePathUsage );
		
		if( $preview ) {
			$previewFormat = 'image/jpeg';
			return $preview;
		}
		return null;
	}
}
