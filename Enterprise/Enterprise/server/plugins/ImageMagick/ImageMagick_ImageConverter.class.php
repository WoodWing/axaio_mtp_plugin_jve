<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v10.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 */
require_once BASEDIR . '/server/interfaces/plugins/connectors/ImageConverter_EnterpriseConnector.class.php';
 
class ImageMagick_ImageConverter extends ImageConverter_EnterpriseConnector
{
	/** @var array $cmdParams List of parameters to put on command line when calling ImageMagick. */
	private $cmdParams = array();

	/** @var string $configCmdLineParams  */
	private $configCmdLineParams = '';

	/**
	 * {@inheritDoc}
	 */
	final public function canHandleFormat( $inputFormat, $outputFormat )
	{
		$score = 0;
		if( in_array( $outputFormat, $this->getSupportedOutputFormats() ) ) {
			switch( $inputFormat ) {
				case 'image/jpeg':
				case 'image/pjpeg':
				case 'image/jpg':
				case 'image/gif':
				case 'image/png':
				case 'image/x-png':
					$score = 8;
					break;
				case 'image/tiff':
				case 'image/x-photoshop':
				case 'application/postscript':
				case 'application/illustrator':
				case 'application/pdf':
				case 'application/photoshop':
				case 'application/eps':
					$score = 9;
					break;
				default:
					$score = 0;
					break;
			}
		}

		return $score;
	}

	/**
	 * {@inheritDoc}
	 */
	public function convertImage()
	{
		$retVal = false;
		if( $this->applyCrop || $this->applyScale || $this->applyRotate || $this->applyMirror || $this->applyResize || $this->inputOrientation > 1 ) {

			$this->addCommonParams();

			// Assumed is that the crop dimensions are defined in 'human readable' manner. Therefore we first
			// 'straighten' the image according to the way (orientation) the camera was held *before* applying crop.
			if( $this->inputOrientation > 1 ) {
				$this->addOrientationCmdParams();
			}

			if( $this->applyCrop ) {
				$this->addCmdParam( 'crop', $this->cropWidth.'x'.$this->cropHeight.'+'.$this->cropLeft.'+'.$this->cropTop );
			}
			if( $this->applyScale ) {
				$scaleX = $this->scaleFactorX * 100;
				$scaleY = $this->scaleFactorY * 100;
				$this->addCmdParam( 'resize', $scaleX.'%x'.$scaleY.'%' );
			}
			if( $this->applyResize ) {
				$this->addCmdParam( 'resize', $this->outputWidth.'x'.$this->outputHeight );
			}
			if( $this->applyMirror ) {
				if( $this->mirrorHorizontal ) {
					$this->addCmdParam( 'flop' );
				}
				if( $this->mirrorVertical ) {
					$this->addCmdParam( 'flip' );
				}
			}
			if( $this->applyRotate ) {
				$this->addCmdParam( 'rotate', 360 - $this->rotateDegrees );
			}

			$cmdName = 'convert';
			$cmdLine = implode( ' ', array(
				$cmdName,
				escapeshellarg( $this->inputFilePath.'[0]' ), // [0] = first layer only
				$this->configCmdLineParams,
				$this->serializeParams(),
				escapeshellarg( $this->outputFilePath )
			));
			require_once BASEDIR . '/server/plugins/ImageMagick/ImageMagick.class.php';
			$retVal = ImageMagick::imageMagickCmd( $cmdName, $cmdLine ) == 0;
		} else {
			LogHandler::Log( 'ImageConverter', 'INFO', 'No operation defined to convert the image. No action taken.' );
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
	 * {@inheritDoc}
	 */
	public function resetOperations()
	{
		parent::resetOperations();
		$this->cmdParams = array();
		$this->configCmdLineParams = IMAGE_MAGICK_PUBLISH_OPTIONS;
	}

	/**
	 * Defines a parameter for the ImageMagick command line.
	 *
	 * @param string $name Parameter to appear as -param on the command line
	 * @param string|null $value The value to put after parameter name on command line. NULL to skip.
	 * @param bool $config
	 */
	private function addCmdParam( $name, $value=null, $config=false )
	{
		if( $config ) {
			$this->configCmdLineParams = str_replace( '%'.$name.'%', $value, $this->configCmdLineParams );
		} else {
			$this->cmdParams[ $name ] = $value;
		}
	}

	/**
	 * Defines some basic parameters for the convert operation of ImageMagick.
	 */
	private function addCommonParams()
	{
		if( LogHandler::debugMode() ) {
			$this->addCmdParam( 'verbose' );
		}
		$this->addCmdParam( 'units', 'PixelsPerInch' ); // used conjunction with 'density' param
		$this->addCmdParam( 'density', $this->outputDpi );

		// Fill in the template option IMAGE_MAGICK_PUBLISH_OPTIONS with default values.
		$this->addCmdParam( 'colorspace', 'sRGB', true );
		$this->addCmdParam( 'quality', '92', true );
		$this->addCmdParam( 'sharpen', '5', true );
		$this->addCmdParam( 'depth', '8', true );
		$this->addCmdParam( 'strip', null, true );
		$this->addCmdParam( 'background', 'none', true );
		$this->addCmdParam( 'layers', 'merge', true );
	}

	/**
	 * Adds command line parameters to pass on to Sips to rotate/mirror an image.
	 */
	private function addOrientationCmdParams()
	{
		switch( $this->inputOrientation ) {
			case 1: // Horizontal (normal)
				break;
			case 2: // Mirror horizontal
				$this->addCmdParam( 'flop' );
				break;
			case 3: // Rotate 180 CW
				$this->addCmdParam( 'rotate', 180 );
				break;
			case 4: // Flip vertical
				$this->addCmdParam( 'flip' );
				break;
			case 5: // First rotate 270 CW, then flip vertical
				$this->addCmdParam( 'rotate', 270 );
				$this->addCmdParam( 'flip' );
				break;
			case 6: // Rotate 90 CW
				$this->addCmdParam( 'rotate', 90 );
				break;
			case 7: // First rotate 270 CW, then mirror horizontal
				$this->addCmdParam( 'rotate', 270 );
				$this->addCmdParam( 'flop' );
				break;
			case 8: // Rotate 270 CW
				$this->addCmdParam( 'rotate', 270 );
				break;
		}
	}

	/**
	 * Serializes and escapes the defined parameters into a command line string.
	 *
	 * @return string Serialized parameters.
	 */
	private function serializeParams()
	{
		$serialized = '';
		foreach( $this->cmdParams as $paramKey => $paramValue ) {
			$serialized .= ' -'.$paramKey;
			if( !is_null( $paramValue ) ) {
				$serialized .= ' '.escapeshellarg( $paramValue );
			}
		}
		return $serialized;
	}
}