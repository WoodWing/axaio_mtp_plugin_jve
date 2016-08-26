<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v10.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class BizImageConverter
{
	/** @var Attachment $inputImageAttachment */
	private $inputImageAttachment;

	/** @var array $inputImageProps */
	private $inputImageProps = array();

	/** @var Attachment $outputImageAttachment */
	private $outputImageAttachment;

	/**
	 * Returns the image that was used as input for image conversion.
	 *
	 * @return Attachment Image file definition with Type, FilePath and Rendition set.
	 */
	public function getInputImageAttachment()
	{
		return $this->inputImageAttachment;
	}

	/**
	 * Returns the image that was outputted by image conversion.
	 *
	 * @return Attachment Image file definition with Type, FilePath and Rendition set.
	 */
	public function getOutputImageAttachment()
	{
		return $this->outputImageAttachment;
	}

	/**
	 * Retrieves a native image file from the file-store along with some metadata.
	 *
	 * @param integer $imageId
	 * @return boolean Whether or not the image could be retrieved.
	 */
	public function loadNativeFileForInputImage( $imageId )
	{
		$this->inputImageAttachment = null;
		$this->inputImageProps = array();
		$this->inputImageObject = null;
		try {
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$user = BizSession::getShortUserName();
			$this->inputImageObject = BizObject::getObject( $imageId, $user, false/*lock*/,
				'native'/*rendition*/, array( 'MetaData' ) );

			if( $this->inputImageObject && $this->inputImageObject->Files[0] ) {
				$this->inputImageAttachment = $this->inputImageObject->Files[0];
				$this->inputImageProps['Dpi'] = $this->inputImageObject->MetaData->ContentMetaData->Dpi;
				$this->inputImageProps['Width'] = $this->inputImageObject->MetaData->ContentMetaData->Width;
				$this->inputImageProps['Height'] = $this->inputImageObject->MetaData->ContentMetaData->Height;
			}
		} catch( BizException $e ) {
		}
		return (bool)$this->inputImageAttachment;
	}

	/**
	 * Invokes the best image converter connector and requests for crop and scale operaions.
	 *
	 * @param Placement $placement Definition of the image crop frame, scale and dimensions.
	 * @param integer $channelId The ID of the Publication Channel.
	 * @return bool Whether or not the operation was successful.
	 */
	public function cropAndScaleImageByPlacement( Placement $placement, $channelId )
	{
		if( !$this->inputImageAttachment ) {
			return false;
		}

		$supportedOutputFormats = $this->getSupportedOutputFormats( $channelId );

		$connector = null;
		foreach( $supportedOutputFormats as $outputFormat ) {
			$connector = $this->getBestConnector( $this->inputImageAttachment->Type, $outputFormat );

			// Stop looking the moment we found a connector for the wanted output format.
			if( $connector != null ) {
				break;
			}
		}
		if( !$connector ) {
			return false;
		}

		$outputFilePath = $this->createOutputFile(
			$this->inputImageObject->MetaData->ContentMetaData->Format,
			$this->inputImageObject->MetaData->BasicMetaData->Type ); // could be Image or Advert

		$attachment = new Attachment();
		$attachment->Type = 'image/jpeg';
		$attachment->Rendition = 'output';
		$attachment->FilePath = $outputFilePath;
		$this->outputImageAttachment = $attachment;

		BizServerPlugin::runConnector( $connector, 'resetOperations', array() );
		BizServerPlugin::runConnector( $connector, 'setInputFilePath', array(
			$this->inputImageAttachment->FilePath ) );
		BizServerPlugin::runConnector( $connector, 'setOutputFilePath', array(
			$this->outputImageAttachment->FilePath ) );
		BizServerPlugin::runConnector( $connector, 'setInputDimension', array(
			$this->inputImageProps['Width'], $this->inputImageProps['Height'], $this->inputImageProps['Dpi'] ) );
		if( $placement->Width && $placement->Height ) {
			if( $placement->ContentDx || $placement->ContentDy ||
				$this->inputImageProps['Width'] != $placement->Width ||
				$this->inputImageProps['Height'] != $placement->Height
			) {
				$left = $placement->ScaleX ? -$placement->ContentDx / $placement->ScaleX : -$placement->ContentDx;
				$top = $placement->ScaleY ? -$placement->ContentDy / $placement->ScaleY : -$placement->ContentDy;
				$width = $placement->ScaleX ? $placement->Width / $placement->ScaleX : $placement->Width;
				$height = $placement->ScaleY ? $placement->Height / $placement->ScaleY : $placement->Height;
				BizServerPlugin::runConnector( $connector, 'crop', array( $left, $top, $width, $height, 'points' ) );
			}
		}
		if( $placement->ScaleX || $placement->ScaleY ) {
			BizServerPlugin::runConnector( $connector, 'scale', array( $placement->ScaleX, $placement->ScaleY ) );
		}
		return BizServerPlugin::runConnector( $connector, 'convertImage', array() );
	}

	/**
	 * Requests the Publishing connector for a list of all output formats that are supported by the publication channel.
	 *
	 * @param integer $channelId The ID of the Publication channel.
	 * @return array|null A list of MIME output formats supported by the current publication channel.
	 */
	private function getSupportedOutputFormats( $channelId )
	{
		$outputFileFormats = BizServerPlugin::runChannelConnector( $channelId, 'getFileFormatsForOutputImage', array() );
		if( is_array($outputFileFormats) ) {
			return $outputFileFormats;
		} else {
			return null;
		}
	}

	/**
	 * Iterates through all image converter connectors to find the best one.
	 *
	 * @param string $inputImageFormat
	 * @param string $outputImageFormat
	 * @return ImageConverter_EnterpriseConnector|null
	 */
	private function getBestConnector( $inputImageFormat, $outputImageFormat )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connectors = BizServerPlugin::searchConnectors( 'ImageConverter', null );
		$highestQuality = 0;
		$bestConnector = null;
		foreach( $connectors as $connector ) {
			$quality = BizServerPlugin::runConnector( $connector, 'canHandleFormat', array( $inputImageFormat, $outputImageFormat ) );
			if( $quality > $highestQuality ) {
				$highestQuality = $quality;
				$bestConnector = $connector;
			}
		}

		if( $bestConnector ) {
			LogHandler::Log( 'BizImageConverter', 'INFO', 'Determined the best ImageConverter connector: "'.
				get_class( $bestConnector ).'" with quality "'.$highestQuality.'".' );
		} else {
			LogHandler::Log( 'BizImageConverter', 'INFO', 'Could not find an ImageConverter connector '.
				'for image input format "'.$inputImageFormat.'" and output format "'.$outputImageFormat.'".' );
			// Note that when no connector found, there is no need for a warning; Biz logic should take care.
		}
		return $bestConnector;
	}

	/**
	 * Creates an empty output file in the temp folder with random name and proper extension.
	 *
	 * @param string $format File format (mime type), used to determine the file extension.
	 * @param string $objectType Object type, used to determine the file extension.
	 * @return bool|string File path when created, or FALSE when failed.
	 */
	private function createOutputFile( $format, $objectType )
	{
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$fileExt = MimeTypeHandler::mimeType2FileExt( $format, $objectType );
		$systemTempDir = rtrim( sys_get_temp_dir(), '/\\' );
		$fileNameOut = $systemTempDir.'/imgcnv_'.NumberUtils::createGUID().$fileExt;
		// Note that tempnam() does not give file extensions.
		$fileResOut = fopen( $fileNameOut, 'wb' );
		if( !$fileResOut ) {
			return false;
		}
		fclose( $fileResOut );
		return $fileNameOut;
	}
}