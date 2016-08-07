<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v10.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * @todo:
 * - handle configuration for output file format (now jpeg hard coded)
 * - the ContentDx/ContentDy seems not to be telly among the PHP modules
 * - the MetaData->ContentMetaData->Dpi (300) returned for native image may not be correct?
 * - clean the transfer server folder and temp folder after image processing
 * - error handling and error logging
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
		$this->outputImageAttachment = null;
		try {
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$user = BizSession::getShortUserName();
			$imageObject = BizObject::getObject( $imageId, $user, false/*lock*/,
				'native'/*rendition*/, array( 'MetaData' ) );

			$outputFilePath = $this->createOutputFile(
				$imageObject->MetaData->ContentMetaData->Format,
				$imageObject->MetaData->BasicMetaData->Type ); // could be Image or Advert

			if( $imageObject && $imageObject->Files[0] && $outputFilePath ) {
				$this->inputImageAttachment = $imageObject->Files[0];
				$this->inputImageProps['Dpi'] = $imageObject->MetaData->ContentMetaData->Dpi;
				$this->inputImageProps['Width'] = $imageObject->MetaData->ContentMetaData->Width;
				$this->inputImageProps['Height'] = $imageObject->MetaData->ContentMetaData->Height;

				$attachment = new Attachment();
				$attachment->Type = 'image/jpeg';
				$attachment->Rendition = 'preview';
				$attachment->FilePath = $outputFilePath;
				$this->outputImageAttachment = $attachment;
			}
		} catch( BizException $e ) {
		}
		return (bool)$this->inputImageAttachment;
	}

	/**
	 * Invokes the best image converter connector and requests for crop and scale operaions.
	 *
	 * @param Placement $placement Definition of the image crop frame, scale and dimensions.
	 * @return bool Whether or not the operation was successful.
	 */
	public function cropAndScaleImageByPlacement( Placement $placement )
	{
		if( !$this->inputImageAttachment || !$this->outputImageAttachment ) {
			return false;
		}
		$connector = $this->getBestConnector( $this->inputImageAttachment->Type );
		if( !$connector ) {
			return false;
		}

		BizServerPlugin::runConnector( $connector, 'resetOperations', array() );
		BizServerPlugin::runConnector( $connector, 'setInputFilePath', array(
			$this->inputImageAttachment->FilePath ) );
		BizServerPlugin::runConnector( $connector, 'setOutputFilePath', array(
			$this->outputImageAttachment->FilePath ) );
		BizServerPlugin::runConnector( $connector, 'setInputDimension', array(
			$this->inputImageProps['Width'], $this->inputImageProps['Height'], $this->inputImageProps['Dpi'] ) );
		if( $placement->ContentDx || $placement->ContentDy ) {
			BizServerPlugin::runConnector( $connector, 'crop', array(
				-$placement->ContentDx, -$placement->ContentDy,
				$placement->Width, $placement->Height, 'points' ) );
		}
		if( $placement->ScaleX || $placement->ScaleY ) {
			BizServerPlugin::runConnector( $connector, 'scale', array( $placement->ScaleX, $placement->ScaleY ) );
		}
		BizServerPlugin::runConnector( $connector, 'convertImage', array() );
		return true;
	}

	/**
	 * Iterates through all image converter connectors to find the best one.
	 *
	 * @param string $imageFormat
	 * @return ImageConverter_EnterpriseConnector|null
	 */
	private function getBestConnector( $imageFormat )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connectors = BizServerPlugin::searchConnectors( 'ImageConverter', null );
		$highestQuality = 0;
		$bestConnector = null;
		foreach( $connectors as $connector ) {
			$quality = BizServerPlugin::runConnector( $connector, 'canHandleFormat', array( $imageFormat ) );
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
				'for image format "'.$imageFormat.'".' );
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
		$fileNameOut = sys_get_temp_dir().'/imgcnv_'.NumberUtils::createGUID().$fileExt;
		// Note that tempnam() does not give file extensions.
		$fileResOut = fopen( $fileNameOut, 'wb' );
		if( !$fileResOut ) {
			return false;
		}
		fclose( $fileResOut );
		return $fileNameOut;
	}
}