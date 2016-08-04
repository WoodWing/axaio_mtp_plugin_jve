<?php

/**
 * @package 	Enterprise
 * @subpackage 	Utils
 * @since 		v7.0.4
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * The GD library does NOT support the Orientation tag. As a result, previews generated from 
 * uploaded native files with EXIF Orientation Tag defined, will be badly orientated.
 * This class can read the Orientation Tag and rotate/flip the image into the correct position.
 *
 * The Exif specification defines an Orientation fag to indicate the orientation of the camera 
 * relative to the captured scene. This can be used by the camera either to indicate the orientation 
 * automatically by an orientation sensor, or to allow the user to indicate the orientation manually 
 * by a menu switch, without actually transforming the image data itself. 
 *
 * For convenience, here is what the letter F would look like if it were tagged correctly and displayed 
 * by a program that ignores the orientation tag (thus showing the stored image):
 *
 *   1        2       3      4         5            6           7          8
 * 
 *  888888  888888      88  88      8888888888  88                  88  8888888888
 *  88          88      88  88      88  88      88  88          88  88      88  88
 *  8888      8888    8888  8888    88          8888888888  8888888888          88
 *  88          88      88  88
 *  88          88  888888  888888
 * 
 */
class ExifImageOrientatation
{
	private $content = null;
	
	/**
	 * Determines the Orientation from the given image file path having embedded EXIF metadata.
	 * It uses the exif_read_data() function to take out the Orientation Tag from image on disk.
	 *
	 * This function supersedes readExifOrientationFromBuf() since leaving large image files on
	 * disk is much less memory consumptive (and so avoids memory swapping in case of many users).
	 * Leaving image files on disk is implemented since v8.0 by using the File Transfer Server.
	 *
	 * @see readExifOrientationFromBuf()
	 * @param string $filePath Full file path of the image
	 * @return integer The value of Orientation Tag [1...8]. Zero when not found.
	 */
	public function readExifOrientationFromFile( $filePath )
	{
		$exifData = @exif_read_data( $filePath ); // file format might not be supported, so added @ to suppress PHP warnings
		if( isset( $exifData['IFD0']['Orientation'] ) ) { 
			$orientation = $exifData['IFD0']['Orientation'];
		} else if( isset( $exifData['IFD1']['Orientation'] ) ) { 
			$orientation = $exifData['IFD1']['Orientation'];
		} else if( isset( $exifData['Orientation'] ) ) { 
			$orientation = $exifData['Orientation'];
		} else {
			$orientation = 0;
		}
		if( $orientation < 1 || $orientation > 8 ) {
			LogHandler::Log( __CLASS__, 'DEBUG', 'Ignoring the EXIF Orientation Tag. It is out of range: ['.$orientation.'].' );
			$orientation = 0;
		}
		return $orientation;
	}
	
	/**
	 * Determines the Orientation from the given image content having embedded EXIF metadata.
	 * In case the image is already loaded in memory (for some reason), the readExifOrientationFromFile()
	 * function could be used to take out the Orientation Tag, but that would require to store the 
	 * content (in memory) on disk first, which is expensive. For that reason, readExifOrientationFromBuf()
	 * could be called, which parses the EXIF metadata by itself. Nevertheless, leaving the file on disk
	 * in the first place, is much more advisable, for which the File Transfer Server can be used since v8.0.
	 *
	 * @see readExifOrientationFromFile()
	 * @param string $content Image file contents
	 * @return integer The value of Orientation Tag [1...8]. Zero when not found.
	 */
	public function readExifOrientationFromBuf( $content )
	{
		$ignore = 'Ignoring the EXIF Orientation Tag. ';
		$this->memPos = 0;
		$this->content = $content;

		$exifData = array();
		
		// Read File head, check for JPEG SOI + Exif APP1
		for( $i = 0; $i < 4; $i++ ) {
			$exifData[$i] = $this->readOneByte();
		}
		if ($exifData[0] != 0xFF ||
			$exifData[1] != 0xD8 ||
			$exifData[2] != 0xFF ||
			$exifData[3] != 0xE1 ) {
			LogHandler::Log( __CLASS__, 'INFO', $ignore.'No file header found [' . implode( '][', $exifData ). '].' );
			return 0;
		}
		
		// Get the marker parameter length count
		$length = $this->readTwoBytes();
		// Length includes itself, so must be at least 2
		// Following Exif data length must be at least 6
		if( $length < 8 ) {
			LogHandler::Log( __CLASS__, 'INFO', $ignore.'No marker parameter found.' );
			return 0;
		}
		$length -= 8;
		
		// Read Exif head, check for "Exif"
		for( $i = 0; $i < 6; $i++ ) {
			$exifData[$i] = $this->readOneByte();
		}
		if ($exifData[0] != 0x45 ||
			$exifData[1] != 0x78 ||
			$exifData[2] != 0x69 ||
			$exifData[3] != 0x66 ||
			$exifData[4] != 0 ||
			$exifData[5] != 0 ) {
			LogHandler::Log( __CLASS__, 'INFO', $ignore.'No EXIF header found.' );
			return 0;
		}
		
		// Read Exif body
		for( $i = 0; $i < $length; $i++ ) {
			$exifData[$i] = $this->readOneByte();
		}
		if( $length < 12) {
			LogHandler::Log( __CLASS__, 'INFO', $ignore.'No EXIF body found.' );
			return 0; // Length of an IFD entry
		}
		
		// Discover byte order
		if( $exifData[0] == 0x49 && $exifData[1] == 0x49 ) {
			$isMotorola = false;
		} else if ($exifData[0] == 0x4D && $exifData[1] == 0x4D ) {
			$isMotorola = true;
		} else {
			LogHandler::Log( __CLASS__, 'WARN', $ignore.'Could not determine byte order.' );
			return 0;
		}
		
		// Check Tag Mark
		if( $isMotorola ) {
			if( $exifData[2] != 0 || $exifData[3] != 0x2A ) {
				LogHandler::Log( __CLASS__, 'INFO', $ignore.'Could not find tag mark.' );
				return 0;
			}
		} else {
			if( $exifData[3] != 0 || $exifData[2] != 0x2A ) {
				LogHandler::Log( __CLASS__, 'INFO', $ignore.'Could not find tag mark.' );
				return 0;
			}
		}
		
		// Get first IFD $offset ($offset to IFD0)
		if( $isMotorola ) {
			if( $exifData[4] != 0 || $exifData[5] != 0 ) {
				LogHandler::Log( __CLASS__, 'INFO', $ignore.'Bad offset for IFD0 section.' );
				return 0;
			}
			$offset = $exifData[6];
			$offset <<= 8;
			$offset += $exifData[7];
		} else {
			if( $exifData[7] != 0 || $exifData[6] != 0 ) {
				LogHandler::Log( __CLASS__, 'INFO', $ignore.'Bad offset for IFD0 section.' );
				return 0;
			}
			$offset = $exifData[5];
			$offset <<= 8;
			$offset += $exifData[4];
		}
		if( $offset > $length - 2 ) {
			LogHandler::Log( __CLASS__, 'INFO', $ignore.'Could not find IFD0 section.' );
			return 0; // check end of data segment
		}
		
		// Get the number of directory entries contained in this IFD
		if( $isMotorola ) {
			$number_of_tags = $exifData[$offset];
			$number_of_tags <<= 8;
			$number_of_tags += $exifData[$offset+1];
		} else {
			$number_of_tags = $exifData[$offset+1];
			$number_of_tags <<= 8;
			$number_of_tags += $exifData[$offset];
		}
		if ($number_of_tags == 0 ) {
			LogHandler::Log( __CLASS__, 'INFO', $ignore.'Could not find entries in IFD section.' );
			return 0;
		}
		$offset += 2;
		
		// heavy debug only
		//print 'number_of_tags: '.$number_of_tags.'<br/>';
		
		// Search for Orientation Tag in IFD0
		for (;;) {
			if ($offset > $length - 12) {
				LogHandler::Log( __CLASS__, 'INFO', $ignore.'Could not find Orientation Tag.' );
				return 0; // check end of data segment
			}
			// Get Tag number 
			if( $isMotorola ) {
				$tagnum = $exifData[$offset];
				$tagnum <<= 8;
				$tagnum += $exifData[$offset+1];
			} else {
				$tagnum = $exifData[$offset+1];
				$tagnum <<= 8;
				$tagnum += $exifData[$offset];
			}
			// heavy debug only
			//print 'tagnum: '.$tagnum.'<br/>';
			if( $tagnum == 0x0112 ) break; // found Orientation Tag
			if( --$number_of_tags == 0 ) {
				LogHandler::Log( __CLASS__, 'INFO', $ignore.'Could not find Orientation Tag.' );
				return 0;
			}
			$offset += 12;
		}
		
		// Get the Orientation value
		if( $isMotorola ) {
			if( $exifData[$offset+8] != 0 ) {
				LogHandler::Log( __CLASS__, 'WARN', $ignore.'Bad Orientation value.' );
				return 0;
			}
			$orientation = $exifData[$offset+9];
		} else {
			if( $exifData[$offset+9] != 0 ) {
				LogHandler::Log( __CLASS__, 'WARN', $ignore.'Bad Orientation value.' );
				return 0;
			}
			$orientation = $exifData[$offset+8];
		}

		if( $orientation < 1 || $orientation > 8 ) {
			LogHandler::Log( __CLASS__, 'WARN', $ignore.'It is out of range: ['.$orientation.'].' );
			return 0;
		}

		return $orientation;
	}

	/**
	 * Read next byte of the $this->content image file content buffer.
	 * Helper function of readExifOrientationFromBuf()
	 *
	 * @return byte
	 */
	private function readOneByte()
	{
		$c = ord($this->content[$this->memPos]);
		$this->memPos += 1;
		return $c;
	}
	
	/**
	 * Read the next two bytes of the $this->content image file content buffer.
	 * Helper function of readExifOrientationFromBuf()
	 *
	 * @return two bytes
	 */
	private function readTwoBytes()
	{
		$c1 = $this->readOneByte();
		$c2 = $this->readOneByte();
		return (($c1) << 8) + ($c2);
	}

	/**
	 * Rotates and/or Flips the given image regarding the specified Orientation.
	 *
	 * @param resource $imageRes The image resource handler
	 * @param integer $orientation [1...8]
	 */
	public function applyOrientation( &$imageRes, $orientation )
	{
		$tmpImage = null;
		switch( $orientation ) {
			case 1: // nothing
				break;
			case 2: // horizontal flip
				$tmpImage = $this->flipImage( $imageRes, 1 );
				break;
			case 3: // 180 rotate left
				$tmpImage = imagerotate( $imageRes, 180, 0 );
				break;
			case 4: // vertical flip
				$tmpImage = $this->flipImage( $imageRes, 2 );
				break;
			case 5: // vertical flip + 90 rotate right
				$tmpImage = $this->flipImage( $imageRes, 2 );
				if( $tmpImage ) {
					$tmpImage = imagerotate( $tmpImage, 270, 0 );
				}
				break;
			case 6: // 90 rotate right
				$tmpImage = imagerotate( $imageRes, 270, 0 );
				break;
			case 7: // horizontal flip + 90 rotate right
				$tmpImage = $this->flipImage( $imageRes, 1 );
				if( $tmpImage ) {
					$tmpImage = imagerotate( $tmpImage, 270, 0 );
				}
				break;
			case 8:    // 90 rotate left
				$tmpImage = imagerotate( $imageRes, 90,0 );
				break;
		}
		if( $tmpImage ) {
			imagedestroy( $imageRes );
			$imageRes = $tmpImage;
		}
	}
	
	/**
	 * Does flip the given image horizontally, vertically or both.
	 *
	 * @param resource $imageRes The image resource handler
	 * @param integer $mode 1 = horizontal, 2 = vertical, 3 = both
	 * @return resource of the flipped image
	 */
	private function flipImage( $imageRes, $mode )
	{
		$width       = imagesx( $imageRes );
		$height      = imagesy( $imageRes );
	
		$srcX       = 0;
		$srcY       = 0;
		$srcWidth   = $width;
		$srcHeight  = $height;
	
		switch ( $mode ) {
			case 1: // horizontal
				$srcX      = $width -1;
				$srcWidth  = -$width;
				break;
			case 2: // vertical
				$srcY      = $height -1;
				$srcHeight = -$height;
				break;
			case 3: // both
				$srcX      = $width -1;
				$srcY      = $height -1;
				$srcWidth  = -$width;
				$srcHeight = -$height;
				break;
			default:
				return false;
		}
	
		$flipImage = imagecreatetruecolor( $width, $height );
		if( $flipImage ) {
			if( imagecopyresampled( $flipImage, $imageRes, 0, 0, $srcX, $srcY, 
									$width, $height, $srcWidth, $srcHeight ) ) {
				return $flipImage;
			}
			imagedestroy( $flipImage );
		}
		return false;
	}
}
