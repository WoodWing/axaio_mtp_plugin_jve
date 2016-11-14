<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v10.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Abstract image converter that can be implemented by an Enterprise connector
 * that integrates with an image processor.
 *
 * It allows callers to crop/scale/rotate/mirror/resize images in a generic way
 * without knowledge of a specific image processor.
 *
 * It defines the supported operations and does some geometrical calculations.
 * The connector should tell which image formats are supported and should
 * implement the real image conversion.
 */
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class ImageConverter_EnterpriseConnector extends DefaultConnector
{
	/** @var string $inputFilePath Full file path of the image to read. */
	protected $inputFilePath;

	/** @var string $outputFilePath Full file path of the image to write. */
	protected $outputFilePath;

	/** @var integer $inputWidth Width in pixels of the input image. */
	protected $inputWidth;

	/** @var integer $inputHeight Height in pixels of the input image. */
	protected $inputHeight;

	/** @var float $inputDpi Pixel density (DPI) of the input image.  */
	protected $inputDpi;

	/** @var integer $inputOrientation How to rotate/mirror the input image; EXIF/IFD0 standard with values 1...8  */
	protected $inputOrientation;

	/** @var float $outputDpi Pixel density (DPI) of the output image.  */
	protected $outputDpi;

	/** @var float $pt2pxFactor Calculated factor used to convert points to pixels.  */
	private $pt2pxFactor;

	// - - - - - - - - - - - - - - - - - - - - - - - - -
	/** @var integer $cropLeft Left most point in pixels of cropping frame on x-axis of input image.  */
	protected $cropLeft;

	/** @var integer $cropTop Top most point in pixels of cropping frame on y-axis of input image.  */
	protected $cropTop;

	/** @var integer $cropWidth Width in pixels of cropping frame on x-axis of input image.  */
	protected $cropWidth;

	/** @var integer $cropHeight Height in pixels of cropping frame on x-axis of input image.  */
	protected $cropHeight;

	/** @var boolean $applyCrop Whether or not to apply the crop operation.  */
	protected $applyCrop;

	// - - - - - - - - - - - - - - - - - - - - - - - - -
	/** @var float $scaleFactorX Factor to scale down the input image (or its crop frame) over the x-axis. */
	protected $scaleFactorX;

	/** @var float $scaleFactorY Factor to scale down the input image (or its crop frame) over the y-axis. */
	protected $scaleFactorY;

	/** @var boolean $applyScale Whether or not to apply the scale operation.  */
	protected $applyScale;

	// - - - - - - - - - - - - - - - - - - - - - - - - -
	/** @var float $rotateDegrees Angle in degrees to rotate input image (or its crop frame) CCW. */
	protected $rotateDegrees;

	/** @var boolean $applyRotate Whether or not to apply the rotate operation.  */
	protected $applyRotate;

	// - - - - - - - - - - - - - - - - - - - - - - - - -
	/** @var boolean $mirrorHorizontal Whether or not to flip the input image (or its crop frame) horizontally.  */
	protected $mirrorHorizontal;

	/** @var boolean $mirrorVertical Whether or not to flip the input image (or its crop frame) vertically.  */
	protected $mirrorVertical;

	/** @var boolean $applyMirror Whether or not to apply the mirror operation.  */
	protected $applyMirror;

	// - - - - - - - - - - - - - - - - - - - - - - - - -
	/** @var integer $resizeWidth Width in pixels to scale down the the input image (or its crop frame). */
	protected $resizeWidth;

	/** @var integer $resizeHeight Height in pixels to scale down the the input image (or its crop frame). */
	protected $resizeHeight;

	/** @var boolean $applyResize Whether or not to apply the resize operation.  */
	protected $applyResize;

	// - - - - - - - - - - - - - - - - - - - - - - - - -
	/**
	 * Defines the location of the input image file to be read.
	 *
	 * @param string $fullPath Full file path of the image.
	 */
	public function setInputFilePath( $fullPath )
	{
		$this->inputFilePath = $fullPath;
	}

	/**
	 * Defines the location of the output image file to be written.
	 *
	 * @param string $fullPath Full file path of the image.
	 */
	public function setOutputFilePath( $fullPath )
	{
		$this->outputFilePath = $fullPath;
	}

	/**
	 * Defines the dimensions of the input image.
	 *
	 * @param integer $width Number of horizontal pixels.
	 * @param integer $height Number of vertical pixels.
	 * @param float $dpi Number of pixels per inch.
	 * @param integer $orientation How to rotate/mirror the image; EXIF/IFD0 standard with values 1...8
	 */
	public function setInputDimension( $width, $height, $dpi, $orientation )
	{
		$this->inputWidth = $width;
		$this->inputHeight = $height;
		$this->inputDpi = $dpi;
		$this->inputOrientation = $orientation;
		$this->recalcOutputDpi();
		$this->pt2pxFactor = $dpi / 72; // assumption: 72 points per inch
		$this->resetOperations();
	}

	/**
	 * Defines the DPI of the output image.
	 *
	 * @param float $dpi Number of pixels per inch.
	 */
	public function setOutputDpi( $dpi )
	{
		$this->outputDpi = $dpi;
		$this->recalcOutputDpi();
	}

	/**
	 * Clear previously defined operations.
	 *
	 * Can be used to operate on the same input image again.
	 * Implicitly called by setInputDimension().
	 */
	public function resetOperations()
	{
		// Default no scale.
		$this->scaleFactorX = 1; // 100%
		$this->scaleFactorY = 1; // 100%
		$this->applyScale = false;

		// Default no crop.
		$this->cropLeft = 0;
		$this->cropTop = 0;
		if( $this->inputOrientation < 5 ) {
			$this->cropWidth = $this->inputWidth;
			$this->cropHeight = $this->inputHeight;
		} else { // rotated 90 degrees CW or CCW
			$this->cropWidth = $this->inputHeight;
			$this->cropHeight = $this->inputWidth;
		}
		$this->applyCrop = false;

		// Default no rotate.
		$this->rotateDegrees = 0;
		$this->applyRotate = false;

		// Default no mirror.
		$this->mirrorHorizontal = false;
		$this->mirrorVertical = false;
		$this->applyMirror = false;

		// Default no resize.
		if( $this->inputOrientation < 5 ) {
			$this->resizeWidth = $this->inputWidth;
			$this->resizeHeight = $this->inputHeight;
		} else { // rotated 90 degrees CW or CCW
			$this->resizeWidth = $this->inputHeight;
			$this->resizeHeight = $this->inputWidth;
		}
		$this->applyResize = false;

		$this->recalcOutputDimentions();
	}

	/**
	 * Converts a given size in a given unit to a number of pixels.
	 *
	 * @param float $size
	 * @param string $unit The unit of $size. Supported values are 'pixels' and 'points'.
	 * @return int|null Number of pixels. NULL when unsupported unit was given.
	 */
	private function toPixels( $size, $unit )
	{
		$pixels = null;
		switch( $unit ) {
			case 'pixels':
				$pixels = $size;
				break;
			case 'points':
				$pixels = intval( $this->pt2pxFactor * $size );
				break;
			default:
				LogHandler::Log( 'ImageProcessor', 'ERROR', 'Unsupported crop unit: '.$unit );
		}
		return $pixels;
	}

	/**
	 * Defines the crop operation to apply on the image.
	 *
	 * @param float $left
	 * @param float $top
	 * @param float $width
	 * @param float $height
	 * @param string $unit The unit of $left, $top, $width and $height. Supported values are 'pixels' and 'points'.
	 */
	public function crop( $left, $top, $width, $height, $unit = 'pixels' )
	{
		$this->cropLeft = $this->toPixels( $left, $unit );
		$this->cropTop = $this->toPixels( $top, $unit );
		$this->cropWidth = $this->toPixels( $width, $unit );
		$this->cropHeight = $this->toPixels( $height, $unit );
		$this->applyCrop = true;
		$this->recalcOutputDimentions();
	}

	/**
	 * Converts a given size in a given unit to a factor [0.0..1.0].
	 *
	 * @param float $size The size to be converted.
	 * @param string $unit The unit of $size. Supported values are 'factor' and 'percentage'.
	 * @return int|null The factor. NULL when unsupported unit was given.
	 */
	private function toFactor( $size, $unit )
	{
		$factor = null;
		switch( $unit ) {
			case 'factor':
				$factor = $size;
				break;
			case 'percentage':
				$factor = $size / 100;
				break;
			default:
				LogHandler::Log( 'ImageProcessor', 'ERROR', 'Unsupported scale unit: '.$unit );
		}
		return $factor;
	}

	/**
	 * Defines the scale operation to apply on the image.
	 *
	 * @param float $scaleX
	 * @param float $scaleY
	 * @param string $unit
	 */
	public function scale( $scaleX, $scaleY, $unit = 'factor' )
	{
		$this->scaleFactorX = $this->toFactor( $scaleX, $unit );
		$this->scaleFactorY = $this->toFactor( $scaleY, $unit );
		if( !$this->scaleFactorX ) {
			$this->scaleFactorX = 1;
		}
		if( !$this->scaleFactorY ) {
			$this->scaleFactorY = 1;
		}
		if( $this->scaleFactorX != 1 || $this->scaleFactorY != 1 ) {
			$this->applyScale = true;
			$this->applyResize = false; // can not do both; resize and scale
			$this->recalcOutputDimentions();
		}
	}

	/**
	 * Defines the rotation operation to apply on the image.
	 *
	 * The center of rotation is the center of the image, and the rotated image
	 * may have different dimensions than the original image.
	 *
	 * @param float $angle Rotation angle, in degrees (CCW), to rotate the image.
	 */
	public function rotate( $angle )
	{
		$this->rotateDegrees = $angle;
		if( $this->rotateDegrees ) {
			$this->applyRotate = true;
		}
	}

	/**
	 * Defines the horizontal/vertical mirror/flip operation to apply on the image.
	 *
	 * @param boolean $horizontal TRUE to flip horizontally, FALSE not to flip.
	 * @param boolean $vertical TRUE to flip vertically, FALSE not to flip.
	 */
	public function mirror( $horizontal, $vertical )
	{
		$this->mirrorHorizontal = $horizontal;
		$this->mirrorVertical = $vertical;
		if( $this->mirrorHorizontal || $this->mirrorVertical ) {
			$this->applyMirror = true;
		}
	}

	/**
	 * Defines the resize operation to apply on the image.
	 *
	 * @param integer $width
	 * @param integer $height
	 * @param string $unit The unit of $width and $height. Supported values are 'pixels' and 'points'.
	 */
	public function resize( $width, $height, $unit = 'pixels' )
	{
		if( $width && $height ) {
			$this->resizeWidth = $this->toPixels( $width, $unit );
			$this->resizeHeight = $this->toPixels( $height, $unit );
			$this->applyResize = true;
			$this->applyScale = false; // can not do both; resize and scale
			$this->recalcOutputDimentions();
		}
	}

	/**
	 * Recalculates the width and height of the output image based on scale/crop/resize operations.
	 */
	private function recalcOutputDimentions()
	{
		if( $this->applyResize ) {
			$this->outputWidth = $this->resizeWidth;
			$this->outputHeight = $this->resizeHeight;
		} else {
			$this->outputWidth = $this->cropWidth * $this->scaleFactorX;
			$this->outputHeight = $this->cropHeight * $this->scaleFactorY;
		}
	}

	/**
	 * Adjusts the outputDpi based on inputDpi avoiding getting more pixels out than available.
	 *
	 * Should be called whenever inputDpi or outputDpi is changed.
	 */
	private function recalcOutputDpi()
	{
		if( $this->inputDpi && $this->outputDpi ) {
			$this->outputDpi = min( $this->outputDpi, $this->inputDpi );
		} elseif( $this->inputDpi ) {
			$this->outputDpi = $this->inputDpi;
		} else {
			$this->outputDpi = null;
		}
	}

	/**
	 * Executes all image conversion operations (as defined before) at once.
	 *
	 * @return bool Whether or not conversion was successful.
	 */
	abstract public function convertImage();

	/**
	 * canHandleFormat
	 *
	 * @param string 	$inputFormat   mime format
	 * @param string 	$outputFormat  mime format
	 * @return int		Return if and how well the formats are supported.
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
	abstract public function canHandleFormat( $inputFormat, $outputFormat );

	/**
	 * Returns a list of image formats that are supported as output by the image converter implementation.
	 * The image formats are notated in MIME format.
	 *
	 * @return array
	 */
	abstract public function getSupportedOutputFormats();

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
}