<?php

class ImageUtils
{
	// Determine new width and height for JPEG image
	private static function ReDimensionJPEG( $width, $height, $max, &$newWidth, &$newHeight )
	{
		if ($width <= $max && $height <= $max){
			$newWidth = $width;
			$newHeight = $height;
		}
		else
		{
			if ($width > $height)
			{
				If ($width > $max)
				{
					$formule = $width / $max;
					$newWidth = $width / $formule;
					$newHeight = $height / $formule;
				}
			}
			else
			{
				If ($height > $max)
				{
					$formule = $height / $max;
					$newWidth = $width / $formule;
					$newHeight = $height / $formule;
				}
			}
		}
	}
	
	// Resize a JPEG, PNG or GIF with the GD libary to JPG
	// If uploaddir param is set, the given file is assumed to be a file path
	// If null given, the given file is assumed to be a memory buffer
	// The buffer parameter determines if output is written to that buffer or if null it will overwrite the file if input is file
	// Returns true on success or false on failure. Only on success, the resized file may assumed to be present.
	public static function ResizeJPEG( $max, $file, $uploaddir, $quality, $newWidth = null, $newHeight = null, &$buffer = null )
	{
		return self::ResizeImage( $max, $file, $uploaddir, $quality, $newWidth, $newHeight, $buffer, false );
	}
	
	// Resize a JPEG, PNG or GIF with the GD libary to PNG, see ResizeJPEG for comments
	public static function ResizePNG( $max, $file, $uploaddir, $newWidth = null, $newHeight = null, &$buffer = null )
	{
		return self::ResizeImage( $max, $file, $uploaddir, 0, $newWidth, $newHeight, $buffer, true );
	}

	public static function ResizeImage( $max, $file, $uploaddir, $quality, $newWidth = null, $newHeight = null, &$buffer = null, $png = false )
	{
		require_once BASEDIR.'/server/utils/MemoryTracker.class.php';
		if( $uploaddir ) { // path...
			$imgType = exif_imagetype( $file );
			if( $imgType == IMAGETYPE_JPEG ) {
				MemoryTracker::Log( 'ResizeJPEG imagecreatefromjpeg' );
				$oldImage = imagecreatefromjpeg( $file );
			} elseif( $imgType == IMAGETYPE_PNG ) {
				MemoryTracker::Log( 'ResizeJPEG imagecreatefrompng' );
				$oldImage = imagecreatefrompng( $file );
			} elseif( $imgType == IMAGETYPE_GIF ) {
				MemoryTracker::Log( 'ResizeJPEG imagecreatefromgif' );
				$oldImage = imagecreatefromgif( $file );
			} else {
				$oldImage = false;
			}
		} else { // memory...
			MemoryTracker::Log( 'ResizeJPEG imagecreatefromstring' );
			$oldImage = imagecreatefromstring( $file );
		}
		MemoryTracker::Log( 'ResizeJPEG image loaded' );
	
		if( !$oldImage ) return false;
	
		if( $uploaddir ) { // path...
			$size = getimagesize( $file );
			$width = $size[0];
			$height = $size[1];
		} else { // memory...
			$width = imagesx( $oldImage );
			$height = imagesy( $oldImage );
		}
	
		if( isset( $max ) && (!isset( $newWidth ) || !isset( $newHeight ) )) {
			$newWidth = null;
			$newHeight = null;
			self::ReDimensionJPEG( $width, $height, $max, $newWidth, $newHeight );
		}

		// >>> Get the Orientation Tag from native image file (BZ#18975)
		require_once BASEDIR.'/server/utils/ExifImageOrientatation.class.php';
		$orientHelper = new ExifImageOrientatation();
		if( $uploaddir ) { // path...
			$orientation = $orientHelper->readExifOrientationFromFile( $file );
		} else {
		$orientation = $orientHelper->readExifOrientationFromBuf( $file );
		}
		// <<<
		
		$newImage = imagecreatetruecolor( $newWidth, $newHeight );
		MemoryTracker::Log( 'ResizeJPEG new empty image created' );
		
		if( $png ) {
			// For png preserve transparency, for this make new image transparent
			$background = imagecolorallocate($newImage, 0, 0, 0);
			ImageColorTransparent($newImage, $background); // make the new temp image all transparent
			imagealphablending($newImage, false); // turn off the alpha blending to keep the alpha channel
		}

		//imagecopyresized( $newImage, $oldImage, 0,0,0,0, $newWidth, $newHeight, $width, $height );
		imagecopyresampled( $newImage, $oldImage, 0,0,0,0, $newWidth, $newHeight, $width, $height );
		
		MemoryTracker::Log( 'ResizeJPEG image copy resized' );

		// >>> Apply the Orientation Tag (from native image file) to the preview image (BZ#18975)
		if( $orientation ) {
			$orientHelper->applyOrientation( $newImage, $orientation );
		} // <<<
	
		if( $buffer === null && $uploaddir ) { // path...
			if( $png ) {
				$retval = imagepng( $newImage, $uploaddir ); // png has no quality setting, but compression, we don't set so use default
			} else {
				$retval = imagejpeg( $newImage, $uploaddir, $quality );
			}
		} else { // memory...
			ob_start();
			if( $png ) {
				$retval = imagepng( $newImage, null ); // png has no quality setting, but compression, we don't set so use default
			} else {
				$retval = imagejpeg( $newImage, null, $quality );
			}
			if( $retval ) $buffer = ob_get_contents();
			ob_end_clean();
		}

		imagedestroy( $oldImage );
		imagedestroy( $newImage );
		MemoryTracker::Log( 'ResizeJPEG completed' );
		return $retval;
	}

	/**
	 * This function checks if an image exceeds the passed dimensions.
	 *
	 * This can be used to determine if an image must be rescaled. Exceeds means that both the width and height
	 * of the image are larger than the passed values. The image can be passed either in memory or as the file path
	 * to the image.
	 *
	 * @param integer $widthRh Width to compare.
	 * @param integer $heightRh Height to compare.
	 * @param string $path Path to the image file.
	 * @param string $image Image as string (memory).
	 * @return boolean Image is larger than passed dimension (true/false).
	 */
	public static function imageExceedsDimensions( $widthRh, $heightRh, $path, $image )
	{
		$widthLh = 0.0;
		$heightLh = 0.0;
		$exceeds = false;

		if( $path ) { // path...
			$size = getimagesize( $path );
			$widthLh = $size[0];
			$heightLh = $size[1];
		}

		if( $image ) { // memory...
			require_once BASEDIR.'/server/utils/MemoryTracker.class.php';
			MemoryTracker::Log( 'Calculate dimensions ImageCreateFromString:' );
			$imageResc = imagecreatefromstring( $image );
			MemoryTracker::Log( 'Calculate dimensions image loaded' );

			$widthLh = imagesx( $imageResc );
			$heightLh = imagesy( $imageResc );
		}

		if( $widthLh > $widthRh && $heightLh > $heightRh ) {
			$exceeds = true;
		}

		return $exceeds;
	}
	
	/**
	 * This method creates from a supplied image a toc image. The toc image
	 * has the dimensions 70 x 70. Depending on the passed image (portrait
	 * or landscape) the image will get transparent strokes on the left and right
	 * (portrait) or at the top and bottom (landscape).
	 *
	 * @param string 	$inputPath Path to input image.
	 * @param string 	$tocPatch Path to the location to write the toc image.
	 * @return boolean 	$result Toc images is written to the passed location.	
	 */
	public static function generateTocImage( $inputPath, $tocPatch )
	{
		// src means source, dst means destination.
		// resize and fit to 70x70 pixels
		$image = imagecreatefromstring(file_get_contents($inputPath));
		$srcW = imagesx($image);
		$srcH = imagesy($image);
		$dstW = 70;
		$dstH = 70;

		if ($srcW > $srcH){
			// Landscape
			$dstH = $dstW / $srcW * $srcH;
		} else if ($srcH > $srcW){
			// Portrait
			$dstW = $dstH / $srcH * $srcW;
		}

		// place in the middle
		$dstX = (70 - $dstW) / 2;
		$dstY = (70 - $dstH) / 2;

		// create transparent image
		$newImage = imagecreatetruecolor(70, 70);
		$transparentColor = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
		imagealphablending($newImage, false);
		imagefilledrectangle($newImage, 0, 0, 70, 70, $transparentColor );

		// Copy original to fit in transparent image.
		imagecopyresampled($newImage, $image, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
		imagedestroy($image);

		// Save image to (StackResources) directory.
		imagesavealpha($newImage, true);
		$result = imagepng($newImage, $tocPatch);
		
		return $result;
	}	
}
