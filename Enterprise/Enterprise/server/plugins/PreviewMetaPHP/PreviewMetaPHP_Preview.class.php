<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
**/
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/Preview_EnterpriseConnector.class.php';

class PreviewMetaPHP_Preview extends Preview_EnterpriseConnector
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
		// Just to be sure we ask GD what formats are supported:
		$gdInfo = gd_info( );
		$useGD = false;
		
		switch( $format ) {
			case 'image/jpeg':
			case 'image/pjpeg':
			case 'image/jpg':
				if( array_key_exists('JPG Support', $gdInfo ) ) {  	// PHP 5.2 and lower
					if( $gdInfo['JPG Support'] ) {
						$useGD = true;
					}						
				}
				else if( $gdInfo['JPEG Support'] ) {								// PHP 5.3 and higher
					$useGD = true;
				}
				break;
			case 'image/gif':
				if( $gdInfo['GIF Read Support'] ) {
					$useGD = true;
				}
				break;
			case 'image/png':
			case 'image/x-png':
				if( $gdInfo['PNG Support'] ) {
					$useGD = true;
				}
				break;
		}
		
		if ( $useGD ) {
			return 9; // GD does good and fast job, but allow for another to be better
		} else {
			return 0;
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
		$format = $attachment->Type;
		LogHandler::Log('PreviewMetaPHP', 'DEBUG', 'Creating preview for '.$format );
		require_once BASEDIR.'/server/utils/ImageUtils.class.php';
		
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
		$preview = '';
		$ret = false;
		
		switch( $format ) {
			case 'image/gif':
			case 'image/png':
			case 'image/x-png':
				$ret = ImageUtils::ResizePNG( $max, 			// max for both width/height
											  $file, $filePathUsage,
											  null, null, 		// height, width max
											  $preview 			// output 
										   );
				if( $ret ) {
					$previewFormat = 'image/png';
				}
				break;
			case 'image/jpeg':
			case 'image/pjpeg':
			case 'image/jpg':
			default:
				$ret = ImageUtils::ResizeJPEG( $max, 			// max for both width/height
											   $file, $filePathUsage,
											   80, 				// quality
											   null, null, 		// height, width max
											   $preview 		// output 
										   );
				if( $ret ) {
					$previewFormat = 'image/jpeg';
				}
				break;
		}
		
		if( $ret ) {
			return $preview;
		} else {
			return null;
		}
	}
}
