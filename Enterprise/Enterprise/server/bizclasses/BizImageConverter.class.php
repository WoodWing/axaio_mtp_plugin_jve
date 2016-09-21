<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v10.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Handles image conversions, such as cropping and scaling images placed on a publish form.
 *
 * Example use cases of this class:
 *
 * 1) In case you want to avoid retrieving the native image file when no conversion is needed:
 *
 *   require_once BASEDIR.'/server/bizclasses/BizImageConverter.class.php';
 *   $bizImageConverter = new BizImageConverter();
 *   $imageAttachment = null;
 *   if( $bizImageConverter->doesImageNeedConversion( $imageId, $imagePlacement ) ) {
 *      if( $bizImageConverter->loadNativeFileForInputImage( $imageId ) ) {
 *         if( $bizImageConverter->convertImageByPlacement( $imagePlacement, $pubChannelId ) ) {
 *            $imageAttachment = $bizImageConverter->getOutputImageAttachment();
 *         }
 *         $bizImageConverter->cleanupNativeFileForInputImage();
 *      }
 *   }
 *
 * 2) In case you want to use the native image file regardless whether or not the image needs conversion:
 *
 *   require_once BASEDIR.'/server/bizclasses/BizImageConverter.class.php';
 *   $bizImageConverter = new BizImageConverter();
 *   $imageAttachment = null;
 *   if( $bizImageConverter->loadNativeFileForInputImage( $imageId ) ) {
 *      if( $bizImageConverter->doesImageNeedConversion( $imageId, $imagePlacement ) ) {
 *         if( $bizImageConverter->convertImageByPlacement( $imagePlacement, $pubChannelId ) ) {
 *            $imageAttachment = $bizImageConverter->getOutputImageAttachment();
 *         }
 *         $bizImageConverter->cleanupNativeFileForInputImage();
 *      } else { // fallback at native rendition
 *         $imageAttachment = $bizImageConverter->getInputImageAttachment();
 *      }
 *   }
 */

class BizImageConverter
{
	/** @var Attachment $inputImageAttachment */
	private $inputImageAttachment;

	/** @var array $inputImageProps */
	private $inputImageProps;

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
	 * Initialisation. Retrieves essential image object properties from DB.
	 *
	 * @param integer $imageId
	 * @return bool Whether or not the properties could be retrieved.
	 */
	private function loadPropertiesForInputImage( $imageId )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$user = BizSession::getShortUserName();
		$reqProps = array(
			'ID', 'Type', 'Dpi', 'Height', 'Width', 'Format', // required by this class
			'Version', 'Types', 'StoreName' ); // required by BizStorage::getFile
		$propsPerObject = BizObject::getMultipleObjectsPropertiesByObjectIds( $user, array( $imageId ), $reqProps );
		$this->inputImageProps = count( $propsPerObject ) > 0 ? reset( $propsPerObject ) : null;
		$this->inputImageAttachment = null;
		return (bool)$this->inputImageProps;
	}

	/**
	 * Tells whether or not the image needs to be converted, based on a given placement.
	 *
	 * @param integer $imageId
	 * @param Placement $placement
	 * @return bool
	 */
	public function doesImageNeedConversion( $imageId, $placement )
	{
		if( !$this->inputImageProps ) {
			if( !$this->loadPropertiesForInputImage( $imageId ) ) {
				return false;
			}
		}
		return $this->doesImageNeedCrop( $imageId, $placement ) ||
			$this->doesImageNeedScale( $placement );
	}

	/**
	 * Tells whether or not the image needs to be cropped, based on a given placement.
	 *
	 * @param integer $imageId
	 * @param Placement $placement
	 * @return bool
	 */
	private function doesImageNeedCrop( $imageId, $placement )
	{
		$retVal = false;
		if( $placement->Width && $placement->Height ) {
			if( !$this->inputImageProps ) {
				$this->loadPropertiesForInputImage( $imageId );
			}
			if( $placement->ContentDx || $placement->ContentDy ||
				$this->inputImageProps['Width'] != $placement->Width ||
				$this->inputImageProps['Height'] != $placement->Height ) {
				$retVal = true;
			}
		}
		return $retVal;
	}

	/**
	 * Tells whether or not the image needs to be scaled, based on a given placement.
	 *
	 * @param Placement $placement
	 * @return bool
	 */
	private function doesImageNeedScale( $placement )
	{
		return $placement->ScaleX && $placement->ScaleY &&  // Avoid zero scale, which would lead into endlessly large images.
			( $placement->ScaleX != 1 || $placement->ScaleY != 1 ); // A scaling of 1 is 100%, which means no scaling is needed.
	}

	/**
	 * Retrieves a native image file from the file-store along with some metadata.
	 *
	 * @param integer $imageId
	 * @return boolean Whether or not the image could be retrieved.
	 */
	public function loadNativeFileForInputImage( $imageId )
	{
		if( !$this->inputImageProps ) {
			if( !$this->loadPropertiesForInputImage( $imageId ) ) {
				return false;
			}
		}
		$this->inputImageAttachment = null;
		try {
			// Note that we can not call BizStorage::getFile() to retrieve the native file from filestore because
			// the image could be a shadow object (e.g. managed by Elvis). Instead we call BizObject::getObject().
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$object = BizObject::getObject( $imageId, BizSession::getShortUserName(), false, 'native', array('') );
			if( $object->Files ) foreach( $object->Files as $attachment ) {
				if( $attachment->Rendition == 'native' ) {
					$this->inputImageAttachment = $attachment;
					break;
				}
			}
		} catch( BizException $e ) {
		}
		return (bool)$this->inputImageAttachment;
	}

	/**
	 * Removes the native image file from the transfer folder that was prepared by loadNativeFileForInputImage().
	 *
	 * Should be called after calling loadNativeFileForInputImage() and convertImageByPlacement().
	 */
	public function cleanupNativeFileForInputImage()
	{
		if( $this->inputImageAttachment ) {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->deleteFile( $this->inputImageAttachment->FilePath );
		}
	}

	/**
	 * Invokes the best image converter connector and requests for crop and scale operaions.
	 *
	 * @param Placement $placement Definition of the image crop frame, scale and dimensions.
	 * @param integer $channelId The ID of the Publication Channel.
	 * @return bool Whether or not the operation was successful.
	 * @throws BizException When not called correctly. See module header for usage.
	 */
	public function convertImageByPlacement( Placement $placement, $channelId )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		if( !$this->inputImageAttachment || !$this->inputImageProps ) {
			return false;
		}

		// Collect all operations that need to be done on the image based on the placement data.
		$conversionOperations = array();
		if( $this->doesImageNeedCrop( $this->inputImageProps['ID'], $placement ) ) {
			$conversionOperations[] = 'crop';
		}
		if( $this->doesImageNeedScale( $placement ) ) {
			$conversionOperations[] = 'scale';
		}
		if( !$conversionOperations ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No crop or scale operations defined for image. '.
				'Please do not call this function when doesImageNeedConversion() returns false.' );
		}

		// Lookup a connector that supports the most preferred output mime type (file format).
		$supportedOutputFormats = $this->getSupportedOutputFormats( $channelId );
		$connector = null;
		$outputFormat = null;
		foreach( $supportedOutputFormats as $outputFormat ) {
			$connector = $this->getBestConnector( $this->inputImageAttachment->Type, $outputFormat );
			if( $connector != null ) {
				break; // Stop looking the moment we found a connector for the wanted output format.
			}
		}
		if( !$connector ) {
			return false;
		}

		$attachment = new Attachment();
		$attachment->Type = $outputFormat;
		$attachment->Rendition = 'output';
		$attachment->FilePath = $this->createOutputFile( $outputFormat, 'Image' );
		$this->outputImageAttachment = $attachment;

		BizServerPlugin::runConnector( $connector, 'resetOperations', array() );
		BizServerPlugin::runConnector( $connector, 'setInputFilePath', array(
			$this->inputImageAttachment->FilePath ) );
		BizServerPlugin::runConnector( $connector, 'setOutputFilePath', array(
			$this->outputImageAttachment->FilePath ) );
		BizServerPlugin::runConnector( $connector, 'setInputDimension', array(
			$this->inputImageProps['Width'], $this->inputImageProps['Height'], $this->inputImageProps['Dpi'] ) );
		BizServerPlugin::runConnector( $connector, 'setOutputDpi', array(
			$this->getDpiForOutputImage( $channelId ) ) );

		foreach( $conversionOperations as $operation ) {
			switch( $operation ) {
				case 'crop':
					$left = $placement->ScaleX ? -$placement->ContentDx / $placement->ScaleX : -$placement->ContentDx;
					$top = $placement->ScaleY ? -$placement->ContentDy / $placement->ScaleY : -$placement->ContentDy;
					$width = $placement->ScaleX ? $placement->Width / $placement->ScaleX : $placement->Width;
					$height = $placement->ScaleY ? $placement->Height / $placement->ScaleY : $placement->Height;
					BizServerPlugin::runConnector( $connector, 'crop', array( $left, $top, $width, $height, 'points' ) );
					break;
				case 'scale':
					BizServerPlugin::runConnector( $connector, 'scale', array( $placement->ScaleX, $placement->ScaleY ) );
					break;
				default:
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'Unknown operation requested to convert image: '.$operation );
					break;
			}
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
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$outputFileFormats = BizServerPlugin::runChannelConnector( $channelId, 'getFileFormatsForOutputImage', array() );
		return is_array($outputFileFormats) ? $outputFileFormats : null;
	}

	/**
	 * Requests the Publishing connector for the DPI to be used for image conversions for a given publication channel.
	 *
	 * @param integer $channelId The ID of the Publication channel.
	 * @return double|null The DPI when connector found, else NULL.
	 */
	private function getDpiForOutputImage( $channelId )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		return BizServerPlugin::runChannelConnector( $channelId, 'getDpiForOutputImage', array() );
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