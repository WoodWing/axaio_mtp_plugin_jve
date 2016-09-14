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
		if( $this->applyCrop || $this->applyScale || $this->applyRotate || $this->applyMirror || $this->applyResize ) {

			$this->addCommonParams();
			if( $this->applyCrop ) {
				$this->addCmdParam( 'crop', $this->cropWidth.'x'.$this->cropHeight.'+'.$this->cropLeft.'+'.$this->cropTop );
			}
			if( $this->applyScale ) {
				$scaleX = $this->scaleFactorX * 100;
				$scaleY = $this->scaleFactorY * 100;
				$this->addCmdParam( 'resize', $scaleX.'%x'.$scaleY.'%' );
			}
			if( $this->applyRotate ) {
				$this->addCmdParam( 'rotate', $this->rotateDegrees );
			}
			if( $this->applyMirror ) {
				if( $this->mirrorHorizontal ) {
					$this->addCmdParam( 'flop' );
				}
				if( $this->mirrorVertical ) {
					$this->addCmdParam( 'flip' );
				}
			}
			if( $this->applyResize ) {
				$this->addCmdParam( 'resize', $this->outputWidth.'x'.$this->outputHeight );
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
		$this->addCmdParam( 'layers', 'merge', true );
		$this->addCmdParam( 'depth', '8', true );
		$this->addCmdParam( 'strip', null, true );
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