<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v10.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 */
require_once BASEDIR . '/server/interfaces/plugins/connectors/ImageConverter_EnterpriseConnector.class.php';

class PreviewMetaPHP_ImageConverter extends ImageConverter_EnterpriseConnector
{
	/**
	 * {@inheritDoc}
	 */
	final public function canHandleFormat( $inputFormat, $outputFormat )
	{
		// Just to be sure we ask GD what formats are supported:
		$gdInfo = gd_info( );
		$useGD = false;

		if( in_array( $outputFormat, $this->getSupportedOutputFormats() ) ) {
			switch( $inputFormat ) {
				case 'image/jpeg':
				case 'image/pjpeg':
				case 'image/jpg':
					if( array_key_exists('JPG Support', $gdInfo ) ) { // PHP 5.2 and lower
						if( $gdInfo['JPG Support'] ) {
							$useGD = true;
						}
					}
					else if( $gdInfo['JPEG Support'] ) { // PHP 5.3 and higher
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
		}

		if ( $useGD ) {
			return 9; // GD does good and fast job, but allow for another to be better
		} else {
			return 0;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function convertImage()
	{
		$retVal = false;
		if( $this->applyCrop || $this->applyScale || $this->applyRotate || $this->applyMirror || $this->applyResize ) {
			$inputImage = self::load( $this->inputFilePath );
			if( $inputImage ) {
				$outputImage = imagecreatetruecolor( intval($this->outputWidth), intval($this->outputHeight) );
				if( $outputImage ) {
					if( $this->applyCrop || $this->applyScale || $this->applyResize ) {
						// The crop window can be sticking out on any side of the input window. The imagecopyresampled() call
						// would then make those parts black in the output image (crop), which is unwanted. ImageMagick will
						// simply cut off all edges that are sticking out, and so the output image (crop) becomes smaller.
						// Here we mimic that behaviour by recalculating the crop window to let imagecopyresampled() implicitly
						// cut off the edges that are sticking out. (EN-87902)
						$shiftRight = $this->cropLeft < 0 ? -$this->cropLeft : 0;
						$shiftDown = $this->cropTop < 0 ? -$this->cropTop : 0;
						$cropLeft = $this->cropLeft + $shiftRight;
						$cropTop = $this->cropTop + $shiftDown;
						$cropWidth = $this->cropWidth + $shiftRight;
						$cropHeight = $this->cropHeight + $shiftDown;
						$cropWidth = min( $cropWidth, $this->inputWidth -$cropLeft );
						$cropHeight = min( $cropHeight, $this->inputHeight - $cropTop );
						$outputWidth = min( $this->outputWidth, $cropWidth );
						$outputHeight = min( $this->outputHeight, $cropHeight );
						imagecopyresampled(
							$outputImage, $inputImage, // dst, src
							0, 0, $cropLeft, $cropTop, // (dst_x, dst_y), (src_x, src_y),
							$outputWidth, $outputHeight, $cropWidth, $cropHeight ); // (dst_w, dst_h), (src_w, src_h)
					}
					if( $this->applyMirror ) {
						if( $this->mirrorHorizontal && $this->mirrorVertical ) {
							$mode = IMG_FLIP_BOTH;
						} else {
							$mode = $this->mirrorHorizontal ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL;
						}
						imageflip( $outputImage, $mode ); // requires custom implementation for < PHP 5.5
					}
					if( $this->applyRotate ) {
						$outputImage = imagerotate( $outputImage, $this->rotateDegrees, 0 );
					}
					$retVal = self::save( $outputImage, $this->outputFilePath );
					imagedestroy( $outputImage );
				}
				imagedestroy( $inputImage );
			}
		}
		return $retVal;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSupportedOutputFormats()
	{
		return array(
			'image/jpeg',
			'image/pjpeg',
			'image/jpg',
			'image/gif',
			'image/png',
			'image/x-png',
		);
	}

	/**
	 * Reads a given JPEG/GIF/PNG image file into memory.
	 *
	 * @param string $fileName Full file path of the image to read.
	 * @return null|resource Handle of the image.
	 */
	static private function load( $fileName )
	{
		$image = null;
		$imageInfo = getimagesize( $fileName );
		switch( $imageInfo[2] ) {
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg( $fileName );
				break;
			case IMAGETYPE_GIF:
				$image = imagecreatefromgif( $fileName );
				break;
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng( $fileName );
				break;
		}
		return $image;
	}

	/**
	 * Writes a given image from memory in a JPEG/GIF/PNG file.
	 *
	 * @param resource $image Handle of the image.
	 * @param string $fileName Full file path to write the image into.
	 * @param integer $imageType
	 * @return bool Whether or not successfully saved.
	 */
	static private function save( $image, $fileName, $imageType = IMAGETYPE_JPEG )
	{
		$retVal = false;
		switch( $imageType ) {
			case IMAGETYPE_JPEG:
				$retVal = imagejpeg( $image, $fileName, 75 ); // 75 = default compression
				break;
			case IMAGETYPE_GIF:
				$retVal = imagegif( $image, $fileName );
				break;
			case IMAGETYPE_PNG:
				$retVal = imagepng( $image, $fileName );
				break;
		}
		return $retVal;
	}
}

// The imageflip() function is introduced in PHP 5.5, but ES10 supports PHP 5.4 for Linux,
// and so we define our own implementation for installations < PHP 5.5 only.
if (!function_exists('imageflip')) {
	define( 'IMG_FLIP_HORIZONTAL', 0 );
	define( 'IMG_FLIP_VERTICAL', 1 );
	define( 'IMG_FLIP_BOTH', 2 );

	function imageflip( $image, $mode )
	{
		switch( $mode ) {
			case IMG_FLIP_HORIZONTAL: {
				$max_x = imagesx( $image ) - 1;
				$half_x = $max_x / 2;
				$sy = imagesy( $image );
				$temp_image = imageistruecolor( $image ) ? imagecreatetruecolor( 1, $sy ) : imagecreate( 1, $sy );
				for( $x = 0; $x < $half_x; ++$x ) {
					imagecopy( $temp_image, $image, 0, 0, $x, 0, 1, $sy );
					imagecopy( $image, $image, $x, 0, $max_x - $x, 0, 1, $sy );
					imagecopy( $image, $temp_image, $max_x - $x, 0, 0, 0, 1, $sy );
				}
				break;
			}
			case IMG_FLIP_VERTICAL: {
				$sx = imagesx( $image );
				$max_y = imagesy( $image ) - 1;
				$half_y = $max_y / 2;
				$temp_image = imageistruecolor( $image ) ? imagecreatetruecolor( $sx, 1 ) : imagecreate( $sx, 1 );
				for( $y = 0; $y < $half_y; ++$y ) {
					imagecopy( $temp_image, $image, 0, 0, 0, $y, $sx, 1 );
					imagecopy( $image, $image, 0, $y, 0, $max_y - $y, $sx, 1 );
					imagecopy( $image, $temp_image, 0, $max_y - $y, 0, 0, $sx, 1 );
				}
				break;
			}
			case IMG_FLIP_BOTH: {
				$sx = imagesx( $image );
				$sy = imagesy( $image );
				$temp_image = imagerotate( $image, 180, 0 );
				imagecopy( $image, $temp_image, 0, 0, 0, 0, $sx, $sy );
				break;
			}
			default: {
				return;
			}
		}
		imagedestroy( $temp_image );
	}
}