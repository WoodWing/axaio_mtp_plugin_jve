<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v9.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once dirname(__FILE__).'/WordPressXmlRpcClient.class.php';
require_once dirname(__FILE__).'/WordPress_Utils.class.php';

class WordPress_PubPublishing extends PubPublishing_EnterpriseConnector
{
	const DOCUMENT_PREFIX = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
	const SITE_ID = '0';

	/**
	 * {@inheritdoc}
	 * @throws BizException
	 */
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget ) 
	{
		// Bail out if there is no publish form in the dossier (should never happen).
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		$publishForm = BizPublishForm::findPublishFormInObjects( $objectsInDossier );
		if( !$publishForm ) {
			return array();
		}

		// Prepare post content.
		$pubFields = array();
		$publishFormObjects = BizPublishForm::getFormFields( $publishForm );

		$e = null;
		try {
			$postContents = $this->preparePost( $publishForm, $publishFormObjects, $dossier, $objectsInDossier, $publishTarget, 'publish' );
			$objXMLRPClientWordPress = new WordPressXmlRpcClient( $publishTarget );
			$postId = $objXMLRPClientWordPress->uploadPost( $postContents );
			if( $postId ) {
				$dossier->ExternalId = $postId;
				$url = $this->getPostUrl( $postId, $publishTarget );
				$pubFields[] = new PubField( 'URL', 'string', array( $url ) );
			}
		} catch( BizException $e ) {
		}

		// Remove temp files from transfer server folder as prepared by getFormFields().
		BizPublishForm::cleanupFilesReturnedByGetFormFields( $publishFormObjects );

		if( $e ) {
			$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
		}
		return $pubFields;
	}

	/**
	 * {@inheritdoc}
	 * @throws BizException
	 */
	public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		// Bail out if there is no publish form in the dossier (should never happen).
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		$publishForm = BizPublishForm::findPublishFormInObjects( $objectsInDossier );
		if( !$publishForm ) {
			return array();
		}

		// Prepare post content.
		$pubFields = array();
		$publishFormObjects = BizPublishForm::getFormFields( $publishForm );

		$e = null;
		try {
			$postContents = $this->preparePost( $publishForm, $publishFormObjects, $dossier, $objectsInDossier, $publishTarget, 'update' );
			$objXMLRPClientWordPress = new WordPressXmlRpcClient( $publishTarget );
			$objXMLRPClientWordPress->uploadPost( $postContents, $dossier->ExternalId );
			$url = $this->getPostUrl( $dossier->ExternalId, $publishTarget );
			$pubFields[] = new PubField( 'URL', 'string', array( $url ) );
		} catch( BizException $e ) {
		}

		// Remove temp files from transfer server folder as prepared by getFormFields().
		BizPublishForm::cleanupFilesReturnedByGetFormFields( $publishFormObjects );

		if( $e ) {
			$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
		}
		return $pubFields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget )
	{
		$objXMLRPClientWordPress = new WordPressXmlRpcClient( $publishTarget );

		try {
			$objXMLRPClientWordPress->deleteOldPublishedPreviews(); // delete all old previews
			try {
				$objXMLRPClientWordPress->deletePostAndContent( $dossier->ExternalId );
				$dossier->ExternalId = '';
			} catch( BizException $e) {
				$this->reThrowDetailedError( $dossier, $e, 'UnpublishDossier' );
			}
		} catch( BizException $e ) { // we want to continue when we could not remove the previews
			LogHandler::Log( 'SERVER', 'WARN', 'Could not remove old published reviews', $e );
		}
		return array();
	}

	/**
	 * {@inheritdoc}
	 * @throws BizException
	 */
	public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		// Bail out if there is no publish form in the dossier (should never happen).
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		$publishForm = BizPublishForm::findPublishFormInObjects( $objectsInDossier );
		if( !$publishForm ) {
			return array();
		}

		// Prepare post content.
		$pubFields = array();
		$publishFormObjects = BizPublishForm::getFormFields( $publishForm );

		$e = null;
		try {
			$postContents = $this->preparePost( $publishForm, $publishFormObjects, $dossier, $objectsInDossier, $publishTarget, 'preview' );
			$objXMLRPClientWordPress = new WordPressXmlRpcClient( $publishTarget );
			$result = $objXMLRPClientWordPress->uploadPost( $postContents, null, true );
			if( $result ) {
				$pubFields[] = new PubField( 'URL', 'string', array( $result ) );
			}
		} catch( BizException $e ) {
		}

		// Remove temp files from transfer server folder as prepared by getFormFields().
		BizPublishForm::cleanupFilesReturnedByGetFormFields( $publishFormObjects );

		if( $e ) {
			throw new BizException( 'WORDPRESS_ERROR_PREVIEW', 'SERVER', 'ERROR', null, array( $publishForm->MetaData->BasicMetaData->Name ) );
		}

		return $pubFields;
	}

	/**
	 * Publish or Update post to WordPress.
	 *
	 * This function is used to update or publish a post from WordPress.
	 * With the action parameter 'publish' or 'update' can be given to determine which action should be done.
	 *
	 * @param Object $publishForm PublishForm object
	 * @param Object[] $publishFormOBJS Objects placed on the Publish Form
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier Objects contained by dossier
	 * @param PubPublishTarget $publishTarget
	 * @param $action
	 * @return array $postContent
	 * @throws BizException
	 */
	private function preparePost( $publishForm, $publishFormOBJS, $dossier, $objectsInDossier, $publishTarget, $action )
	{
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		$postContent = array();
		$article = $this->getFirstObjectWithType( $objectsInDossier, 'Article' );

		$wpClient = new WordPressXmlRpcClient( $publishTarget );
		try {
			$wpClient->deleteOldPublishedPreviews();
		} catch( BizException $e ) {
			// Failing to remove the preview should not stop the operation.
			LogHandler::Log( 'SERVER', 'WARN', 'Could not remove old published previews.', $e );
		}

		$wpUtils = new WordPress_Utils();
		$siteName = $wpUtils->getSiteName( $publishTarget );
		$publishFormDocId = BizPublishForm::getDocumentId( $publishForm );
		if( $publishFormDocId == $this->getDocumentIdPrefix().$siteName ) {
			$messageText = null;
			$attachments = null;
			$inlineImagesCustomField = null;
			$featuredImagesCustomField = null;
			$galleriesCustomField = null;
			$galleryId = null;

			$title = isset( $publishFormOBJS['C_WORDPRESS_POST_TITLE'] ) ? $publishFormOBJS['C_WORDPRESS_POST_TITLE'][0] : null;
			if( !$title ) {
				throw new BizException( 'WORDPRESS_ERROR_NO_TITLE', 'Server', 'A title is needed but there is no title found.' );
			}

			// This is needed because the getFormFields does not return a array when it has only 1 object
			// in that case it will return a object instead of a array. We need a array so here we force it into an array.
			if( isset($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']) && !is_array($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']) ) {
				$publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] = array( $publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] );
			}

			$articleFields = BizPublishForm::extractFormFieldDataFromFieldValue( 'C_WORDPRESS_PF_MESSAGE_SEL', $article, $publishTarget->PubChannelID, true );
			if( $articleFields && isset( $articleFields[0] ) ) {
				$messageText = ( isset( $articleFields[0]['elements'] ) && isset( $articleFields[0]['elements'][0] ) ) ?
										$articleFields[0]['elements'][0]->Content : null;
				$attachments = isset( $articleFields[0]['attachments'] ) ? $articleFields[0]['attachments'] : null;
			}
			$this->validateInlineImageFormat( $attachments );

			// Deleting images from WordPress is only needed when updating
			if( strtolower( $action ) == 'update' ) {
				$customFieldIds = $wpClient->deleteInlineAndFeaturedImages( $dossier->ExternalId );
				$inlineImagesCustomField = isset( $customFieldIds['inline-custom-field'] ) ? $customFieldIds['inline-custom-field'] : null;
				$featuredImagesCustomField = isset( $customFieldIds['featured-custom-field'] ) ? $customFieldIds['featured-custom-field'] : null;
				$gallery = $this->getGalleryByWidgetName( $publishTarget, $dossier->ExternalId, 'C_WORDPRESS_MULTI_IMAGES' );

				if( isset( $gallery['customFieldId'] ) ) {
					$galleriesCustomField = $gallery['customFieldId'];
				}
				if( isset( $gallery['galleryId'] ) ) {
					$galleryId = $gallery['galleryId'];
				}

				if( $galleryId ) {
					$dossiersPublished = DBPublishHistory::getPublishHistoryDossier( $dossier->MetaData->BasicMetaData->ID, $publishTarget->PubChannelID, $publishTarget->IssueID, null, true );
					$dossierPublished = reset( $dossiersPublished ); // Get the first dossier.

					/** @var $imagesToDelete array List of the external ids of the images that should be deleted. */
					$imagesToDelete = array();

					/** @var $nativeExternalsToRemove array List of object ids of which the external id should be unset.
					   Since this id is stored on object level, it needs to be unset once the (native) is removed from the publish form. */
					$nativeExternalsToRemove = array();

					/** @var $nativeImageIds array List of the image ids of the natives to be published in the gallery. value=ImageId */
					$nativeImageIds = array();
					/** @var $convertedPlacements array List of converted placements to be published. Key=ImageId, value=Placement */
					$convertedPlacements = array();

					// Collect all images that are about to be published and save them in two arrays: One for natives and one for converted images.
					if( isset( $publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] ) ) foreach( $publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] as $frameOrder => $image ) {

						// If an image has been converted, we should be on the safe side and always use the newest one.
						$convertedPlacement = $this->getConvertedPlacement( $publishForm, $image->MetaData->BasicMetaData->ID, 'C_WORDPRESS_MULTI_IMAGES', $frameOrder );
						if( $convertedPlacement ) {
							$convertedPlacements[$image->MetaData->BasicMetaData->ID] = $convertedPlacement;
						} else {
							$nativeImageIds[] = $image->MetaData->BasicMetaData->ID;
						}
					}

					// Look through the published native images.
					$publishedObjects = DBPublishedObjectsHist::getPublishedObjectsHist( $dossierPublished['id'] );
					if( $publishedObjects ) foreach( $publishedObjects as $publishedObject ) {

						// Only look at images that have actually been published, i.e. that have an external id.
						if( $publishedObject['type'] == 'Image' && $publishedObject['externalid'] ) {

							// Delete in case that the published image has been removed from the publish form
							if( !in_array( $publishedObject['objectid'], $nativeImageIds ) ) {
								$imagesToDelete[] = $publishedObject['externalid'];
								$nativeExternalsToRemove[] = $publishedObject['objectid'];
							}
						}
					}

					// Look through the published converted images.
					$publishedPlacements = DBPubPublishedPlacementsHistory::listPublishedPlacements( $dossierPublished['id'] );
					if( $publishedPlacements ) foreach( $publishedPlacements as $publishedPlacement ) {
						// Only look at images that have actually been published, i.e. that have an external id.
						if( $publishedPlacement->ExternalId ) {

							// Delete if the crop has been removed from the image.
							if( !isset( $convertedPlacements[$publishedPlacement->ObjectId] ) ) {
								$imagesToDelete[] = $publishedPlacement->ExternalId;
							}
							// Delete if the placement hash of the to-be-published image does not match with the published image's.
							elseif( $convertedPlacements[$publishedPlacement->ObjectId]->ConvertedImageToPublish->PlacementHash !== $publishedPlacement->PlacementHash) {
								$imagesToDelete[] = $publishedPlacement->ExternalId;
							}
						}
					}

					if( $imagesToDelete ) foreach( $imagesToDelete as $id ) {
						$wpClient->deleteImage( $id );
					}

					if( $nativeExternalsToRemove ) foreach( $nativeExternalsToRemove as $objectId ) {
						$this->updateExternalId( null, $objectsInDossier, $objectId, null );
					}
				}
			}

			$doPreview = false;
			$usedGalleryId = $galleryId;
			if( strtolower($action) == 'preview' ) {
				$doPreview = true;
				// In case a preview is done, we want to use a new gallery as to not corrupt a potentially existing one.
				$usedGalleryId = null;
			}

			$uploadImagesResult = $this->uploadImages( $publishForm, $publishFormOBJS, $objectsInDossier, $publishTarget,
				$usedGalleryId, $dossier->MetaData->BasicMetaData->Name, $attachments, $doPreview );

			if( isset( $uploadImagesResult['galleryId'] ) ) {
				$galleryId = $uploadImagesResult['galleryId'];
			}

			// Cleanup the temporary created images files.
			if( $attachments ) {
				require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				foreach( $attachments as $attachment ) {
					$composedFilePath = $transferServer->composeTransferPath( basename( $attachment->FilePath ) );
					if( $composedFilePath == $attachment->FilePath ) { // native files are stored in the transfer folder.
						$transferServer->deleteFile( $attachment->FilePath );
					} else { // cropped images are stored in the system temp folder.
						unlink( $attachment->FilePath );
					}
				}
			}

			if( isset( $uploadImagesResult['attachments'] ) ) {
				$attachments = $uploadImagesResult['attachments'];
				$pattern = '/<img[^>]*id=\"ent_([^\"]*)[^>]*src=\"([^\"]*)[^>]*>/i';
				$patternResult = array();
				preg_match_all( $pattern, $messageText, $patternResult );

				foreach( $attachments as $attachment ) {
					for( $i = 0; $i < count( $patternResult[0] ); $i++ ) {
						if( $patternResult[1][ $i ] == $attachment['ent_id'] ) {
							$imageUrl = str_replace( $patternResult[2][ $i ], $attachment['url'], $patternResult[0][ $i ] );
							$messageText = str_replace( $patternResult[0][ $i ], $imageUrl, $messageText );
						}
					}
				}
			}

			$images = array();
			if( isset( $publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] ) ) {
				foreach( $publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] as $image ) {
					foreach( $objectsInDossier as $dossierObject ) {
						if( $dossierObject->MetaData->BasicMetaData->Type == 'Image' ) {
							if( $dossierObject->MetaData->BasicMetaData->ID == $image->MetaData->BasicMetaData->ID ) {
								$images[] = $dossierObject->ExternalId;
								break;
							}
						}
					}
				}
				if( $images ) {
					$size = count($images);
					if( $size > 1 ) {
						$messageText .= '[ngg_images gallery_ids="'.$galleryId.'" image_ids="'.implode(',', $images).'" display_type="photocrati-nextgen_basic_slideshow"]';
					} else if( $size == 1 ) {
						$messageText .= '[ngg_images gallery_ids="'.$galleryId.'" image_ids="'.implode(',', $images).'" display_type="photocrati-nextgen_basic_singlepic"]';
					}
				}
			}

			$postContent['title'] = $title;
			$postContent['body'] = $messageText; // Always exists because there should be at least 1 image or some text
			$postContent['authorId'] = $this->getWordPressUserIdForCurrentUser( $siteName );
			if( isset( $uploadImagesResult['galleryId'] ) ) {
				$postContent['gallProps']['galleries']['C_WORDPRESS_MULTI_IMAGES'] = $uploadImagesResult['galleryId'];
				$postContent['gallProps']['customFieldId'] = $galleriesCustomField;
			}

			if( isset( $publishFormOBJS[ 'C_WORDPRESS_CAT_'.strtoupper( $siteName ) ] ) ) {
				$categories = $publishFormOBJS[ 'C_WORDPRESS_CAT_'.strtoupper( $siteName ) ];
				$convertedCategories = array();
				foreach( $categories as $category ) { // delete the hierarchical prefixes.
					$convertedCategory = preg_replace( '/^-+\ /', '', $category );
					$convertedCategories[] = $convertedCategory;
				}
				$postContent['categories'] = $convertedCategories;
			}

			if( isset( $uploadImagesResult['inline-ids-for-saving'] ) ) {
				$postContent['inlineImages']['inline-ids'] = $uploadImagesResult['inline-ids-for-saving'];
				if( $inlineImagesCustomField ) {
					$postContent['inlineImages']['customFieldId'] = $inlineImagesCustomField;
				}
			}

			if( isset( $uploadImagesResult['featured-image'] ) ) {
				$postContent['featuredImage']['featured-image'] = $uploadImagesResult['featured-image'];
				if( $featuredImagesCustomField ) {
					$postContent['featuredImage']['customFieldId'] = $featuredImagesCustomField;
				}
			}

			if( isset( $publishFormOBJS[ 'C_WORDPRESS_FORMAT_'.strtoupper( $siteName ) ] ) ) {
				$format = $publishFormOBJS[ 'C_WORDPRESS_FORMAT_'.strtoupper( $siteName ) ][0];
				$savedFormats = $wpUtils->getSavedFormats( $siteName );
				$savedFormat = $format;

				if( isset( $savedFormats[ $format ] ) ) {
					$savedFormat = $savedFormats[ $format ];
				}
				$postContent['format'] = $savedFormat;
			}

			if( isset( $publishFormOBJS['C_WORDPRESS_VISIBILITY'] ) ) {
				$postContent['visibility'] = $publishFormOBJS['C_WORDPRESS_VISIBILITY'][0];
			}
			if( isset( $publishFormOBJS['C_WORDPRESS_STICKY'] ) ) {
				$postContent['sticky'] = $publishFormOBJS['C_WORDPRESS_STICKY'][0];
			}
			if( isset( $publishFormOBJS['C_WORDPRESS_ALLOW_COMMENTS'] ) ) {
				$postContent['allowComments'] = $publishFormOBJS['C_WORDPRESS_ALLOW_COMMENTS'][0];
			}
			if( isset( $publishFormOBJS['C_WORDPRESS_PUBLISH_DATE'] ) ) {
				$postContent['publishDate'] = $publishFormOBJS['C_WORDPRESS_PUBLISH_DATE'][0];
			}
			if( isset( $publishFormOBJS['C_WORDPRESS_EXCERPT'] ) ) {
				$postContent['excerpt'] = $publishFormOBJS['C_WORDPRESS_EXCERPT'][0];
			}
			if( isset( $publishFormOBJS['C_WORDPRESS_SLUG'] ) ) {
				$postContent['slug'] = $publishFormOBJS['C_WORDPRESS_SLUG'][0];
			}
			if( isset( $publishFormOBJS['C_WORDPRESS_TAGS_'.strtoupper( $siteName )] ) ) {
				$tags = is_array($publishFormOBJS['C_WORDPRESS_TAGS_'.strtoupper( $siteName )]) ?
					$publishFormOBJS['C_WORDPRESS_TAGS_'.strtoupper( $siteName )] :
					array( $publishFormOBJS['C_WORDPRESS_TAGS_'.strtoupper( $siteName )] );
				$postContent['tags'] = implode( ', ', $tags );
			}
		}
		return $postContent;
	}

	/**
	 * Returns the supported WordPress image formats (MIME).
	 *
	 * @return array
	 */
	private function getSupportedImageFormats()
	{
		$retVal = array( 'image/jpg', 'image/jpeg', 'image/png', 'image/gif' );
		return $retVal;
	}

	/**
	 * Get gallery by widget name.
	 * Get the gallery id if the gallery for the publish form already exists in WordPress.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param int $externalId
	 * @param string $widgetName
	 * @return array|null Array with gallery id and custom field id if found, NULL otherwise.
	 */
	private function getGalleryByWidgetName( $publishTarget, $externalId, $widgetName )
	{
		require_once 'Zend/Json.php';
		static $galleryField;
		$clientWordPress = new WordPressXmlRpcClient( $publishTarget );

		if( !$galleryField ) {
			$galleryField = $clientWordPress->getGalleriesFromCustomField( $externalId );
		}

		if( $galleryField ) {
			$galleries = $galleryField['galleries'];
			$customFieldId = $galleryField['customFieldId'];
			$galleryId = null;
			if( is_array( $galleries ) ) {
				$galleries = reset( $galleries );
				$galleries = Zend_Json::decode( $galleries );

				// Get the gallery id if the gallery already exists.
				$galleryId = null;
				if( isset( $galleries[$widgetName] ) ) {
					$galleryId = $galleries[$widgetName];
				}
			}
			return array( 'galleryId' => $galleryId, 'customFieldId' => $customFieldId );
		} else {
			return null;
		}
	}

	/**
	 * Get the WordPress user id for the current user
	 *
	 * This function gets the WordPress user id,
	 * only if the username or full name of the logged in Enterprise user matches the WordPress username.
	 *
	 * @param string $siteName
	 * @return int $savedUserId
	 */
	public function getWordPressUserIdForCurrentUser( $siteName )
	{
		$savedUserId = null;
		$wordpressUtils = new WordPress_Utils();
		$wordpressUsers = $wordpressUtils->getSavedUsers( $siteName );
		$currentUser = BizSession::getUser();

		if( $wordpressUsers ) {
			if( isset( $wordpressUsers[strtolower( $currentUser->UserID )] ) ) {
				$savedUserId = $wordpressUsers[strtolower( $currentUser->UserID )];
			} else if( isset( $wordpressUsers[strtolower( $currentUser->FullName )] ) ) {
				$savedUserId = $wordpressUsers[strtolower( $currentUser->FullName )];
			}
		}

		return $savedUserId;
	}

	/**
	 * Re-throw error resulted from Publishing/Unpublishing the PublishForm to WordPress.
	 *
	 * Error collected while publishing/unpublishing to WordPress is refined with further details,
	 * this detailed error is re-thrown.
	 *
	 * @param Object $publishForm
	 * @param BizException $e
	 * @param string $action Whether the error is from Publishing or Unpublishing. Possible values: 'PublishDossier', 'UnpublishDossier'
	 * @throws BizException
	 */
	public function reThrowDetailedError( $publishForm, $e, $action )
	{
		$errorCode = ($e->getCode()) ? ' (code: ' . $e->getCode() . ')' : '';
		$actionMessage = $action == 'PublishDossier' ? 'Posting' : 'Unpublishing';

		$reasonParams = array( $publishForm->MetaData->BasicMetaData->Name );
		if( $action == 'PublishDossier' || $action == 'Posting' ) {
			$msg = BizResources::localize( 'WORDPRESS_ERROR_PUBLISH', true, $reasonParams );
		} else {
			$msg = BizResources::localize( 'WORDPRESS_ERROR_UNPUBLISH', true, $reasonParams );
		}
		$detail = $actionMessage . $publishForm->MetaData->BasicMetaData->Name . ' with id: ' . $publishForm->MetaData->BasicMetaData->ID
			. ' produced an error: ' . $e->getMessage() . $errorCode;

		throw new BizException( null, 'Server', $detail, $msg );
	}

    /**
     * Get the Url for a certain post.
     *
     * @param int $externalId The id of the post, used to get the Url.
     * @param $publishTarget PubPublishTarget
     * @return string
     */
    public function getPostUrl( $externalId, $publishTarget )
    {
        $clientWordPress = new WordPressXmlRpcClient( $publishTarget );
        $postInfo = $clientWordPress->getPost( $externalId );
        return $postInfo['link'];
    }

	/**
	 * {@inheritdoc}
	 */
	public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget )
	{
		$url = null;
		if( $dossier->ExternalId ) {
			$url = $this->getPostUrl( $dossier->ExternalId, $publishTarget );
		}
		$pubField = new PubField('URL', 'string', array($url));

		return array( $pubField );
	}

	/**
	 * Upload all images in the dossier to WordPress.
	 *
	 * This function is used to upload images, it processes the images and checks if they need to be uploaded or only updated.
	 * It calls the upload image or update image for every image that is in the dossier.
	 *
	 * @param Object $publishForm PublishForm object
	 * @param array $publishFormObjects Array of PublishForm field values
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @param integer|null $galleryId
	 * @param string|null $galleryName
	 * @param Attachment[]|null $attachments
	 * @param boolean $preview TRUE if the current action is Preview, FALSE if it is not.
	 * @return array
	 * @throws BizException
	 */
	public function uploadImages( $publishForm, $publishFormObjects, $objectsInDossier, $publishTarget,
	                              $galleryId, $galleryName, $attachments, $preview = false )
	{
		$postId = null;
		$result = null;
		$wpClient = new WordPressXmlRpcClient( $publishTarget );

		if( isset( $publishFormObjects['C_WORDPRESS_MULTI_IMAGES'] ) ) {
			$this->uploadGalleryImages( $wpClient, $publishFormObjects['C_WORDPRESS_MULTI_IMAGES'], $publishForm, $objectsInDossier,
				$publishTarget, $galleryId, $galleryName, $preview );
			$result['galleryId'] = $galleryId;
		}

		if( isset( $publishFormObjects['C_WORDPRESS_FEATURED_IMAGE'] ) ) {
			$result['featured-image'] = $this->uploadFeaturedImage( $wpClient, $publishForm, $objectsInDossier,
				$publishFormObjects['C_WORDPRESS_FEATURED_IMAGE'] );
		}

		if( $attachments ) {
			$inlineImages = array_keys($attachments);
			require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
			foreach( $inlineImages as $imageId ) {
				$format = $attachments[$imageId]->Type;
				$extension = MimeTypeHandler::mimeType2FileExt($format);
				$inlineImageName = DBObject::getObjectName( $imageId );

				if( !$inlineImageName ) { // objectName not found in Workflow area
					$inlineImageName = DBDeletedObject::getObjectName( $imageId ); // so find in Trash area
				}
				try {
					$retVal = $wpClient->uploadMediaLibraryImage( $inlineImageName.$extension,
						$attachments[$imageId]->FilePath, $attachments[$imageId]->Type );
				} catch( BizException $e ) {
					throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'SERVER', $e->getDetail());
				}

				$result['attachments'][$imageId] = $retVal;
				$result['attachments'][$imageId]['ent_id'] = $imageId;
				$result['inline-ids-for-saving'][$imageId] = $retVal['id'];
			}
		}

		return $result;
	}

	/**
	 * Handles the uploading of all images for the gallery.
	 *
	 * @param WordPressXmlRpcClient $wpClient Client to communicate to WordPress with.
	 * @param Object[] $images List of images to be uploaded to the gallery
	 * @param Object $publishForm
	 * @param Object[] $objectsInDossier List of all objects in the dossier. Used for updating object metadata.
	 * @param PubPublishTarget $publishTarget Publishing target for this publish form.
	 * @param string|null &$galleryId Gallery id. If not set, it will be filled in by reference.
	 * @param string $galleryName
	 * @param boolean $preview TRUE if the current action is Preview, FALSE if it is not.
	 * @throws BizException
	 */
	private function uploadGalleryImages( $wpClient, $images, $publishForm, $objectsInDossier, $publishTarget, &$galleryId, $galleryName, $preview )
	{
		if( !$galleryName && !$galleryId ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', 'Please contact your system administrator');
		}

		if( !$galleryId ) {
			$galleryId = $wpClient->createGallery( $galleryName );
		}

		foreach( $images as $frameOrder => $imageObj ) {
			$convertedPlacement = $this->getConvertedPlacement( $publishForm, $imageObj->MetaData->BasicMetaData->ID, 'C_WORDPRESS_MULTI_IMAGES', $frameOrder );

			$externalId = $this->uploadGalleryImage( $wpClient, $publishForm, $publishTarget, $imageObj,
				$galleryId, $preview, $convertedPlacement );

			$this->updateExternalId( $externalId, $objectsInDossier, $imageObj->MetaData->BasicMetaData->ID, $convertedPlacement );
		}
	}

	/**
	 * Updates the external id on all resources relevant to updating Enterprise objects in the database.
	 *
	 * For native images, only the $objectsInDossier list is used to save publish history, so the external
	 * id needs to be set correctly in order to be able to query it later.
	 *
	 * @since 10.1.0
	 * @param string|null $externalId The external id to be updated. If null, the externalid value will be unset.
	 * @param $objectsInDossier List of objects contained in the dossier. Used for updating metadata.
	 * @param int $objectId The id of the object to be updated.
	 * @param Placement|null $convertedPlacement If provided, the externalid will be updated on this resource (for converted images).
	 */
	private function updateExternalId( $externalId, $objectsInDossier, $objectId, $convertedPlacement )
	{
		if( $convertedPlacement ) {
			$convertedPlacement->ConvertedImageToPublish->ExternalId = $externalId;
		} else {
			foreach( $objectsInDossier as $object ) {
				if( $object->MetaData->BasicMetaData->ID == $objectId ) {
					$object->ExternalId = $externalId;
					break;
				}
			}
		}
	}

	/**
	 * Retrieves a placement of an image crop made on a given Publish Form.
	 *
	 * The end user may have cropped the image placed on the Publish Form.
	 * When a crop is found, the caller should prefer the cropped image (over the native image).
	 *
	 * The cropped image is dynamically set by the core server during the publish operation at Placement->ConvertedImageToPublish->Attachment.
	 * Note that this property is not defined in the WSDL. When the property is missing, there is no crop.
	 *
	 * @since 10.1.0
	 * @param Object $publishForm The form object being published.
	 * @param string $childId Id of placed image object to get the attachment for.
	 * @param integer $formWidgetId The publish form template object id used to create the publish form.
	 * @param integer $frameOrder The position of the placement within the publishform field.
	 * @return Placement|null The cropped image. NULL when no crop found.
	 */
	private function getConvertedPlacement( $publishForm, $childId, $formWidgetId, $frameOrder )
	{
		$convertedPlacement = null;
		foreach( $publishForm->Relations as $relation ) {
			if( $relation->Type == 'Placed' &&
				$relation->Child == $childId &&
				$relation->Parent == $publishForm->MetaData->BasicMetaData->ID
			) {
				foreach( $relation->Placements as $placement ) {
					if( $placement->FormWidgetId &&
						$placement->FormWidgetId == $formWidgetId &&
						$placement->FrameOrder == $frameOrder &&
						isset( $placement->ConvertedImageToPublish )
					) {
						$convertedPlacement = $placement;
					}
				}
			}
		}
		return $convertedPlacement;
	}


	/**
	 * Handles the processing and uploading of a featured image.
	 *
	 * @param WordPressXmlRpcClient $wpClient The client used to communicate with WordPress.
	 * @param Object $publishForm PublishForm object.
	 * @param Object[] $objectsInDossier List of objects in the dossier to be published.
	 * @param Object $featuredImage Original image object to be uploaded. Contains all metadata.
	 * @return string External id of the uploaded image.
	 * @throws BizException when communicating with WordPress returns an error.
	 */
	private function uploadFeaturedImage( $wpClient, $publishForm, $objectsInDossier, $featuredImage )
	{
		$convertedPlacement = $this->getConvertedPlacement( $publishForm, $featuredImage->MetaData->BasicMetaData->ID, 'C_WORDPRESS_FEATURED_IMAGE', 0 );

		try {
			if( $convertedPlacement ) {
				$filePath = $convertedPlacement->ConvertedImageToPublish->Attachment->FilePath;
			} else {
				$filePath = $this->saveLocal( $featuredImage->MetaData->BasicMetaData->ID );
			}
			$extension = pathinfo($filePath, PATHINFO_EXTENSION);
			$imageName = $featuredImage->MetaData->BasicMetaData->Name . '.' . $extension ;

			$retVal = $wpClient->uploadMediaLibraryImage( $imageName, $filePath, $featuredImage->MetaData->ContentMetaData->Format );
			$externalId = $retVal['id'];
			if( $externalId ) {
				$imageDescription = null;
				foreach( $featuredImage->MetaData->ExtraMetaData as $extraData ) {
					if( $extraData->Property == 'C_WORDPRESS_IMAGE_DESCRIPTION' ) {
						$imageDescription = $extraData->Values[0];
						break;
					}
				}
				$wpClient->updateMediaLibraryImageMetaData( $externalId, $featuredImage->MetaData->BasicMetaData->Name, $imageDescription, '' );
			}
		} catch( BizException $e ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'SERVER', $e->getDetail() );
		}
		$this->updateExternalId( $externalId, $objectsInDossier, $featuredImage->MetaData->BasicMetaData->ID, $convertedPlacement );
		return $externalId;
	}

	/**
	 * Uploads or updates an image to/in a specific gallery.
	 *
	 * @param WordPressXmlRpcClient $wpClient The client used to communicate with WordPress.
	 * @param Object $publishForm PublishForm object.
	 * @param PubPublishTarget $publishTarget
	 * @param Object $imageObj Original image object to be uploaded. Contains all relevant metadata.
	 * @param integer $galleryId The gallery to which we need to upload images.
	 * @param boolean $preview TRUE if the current action is Preview, FALSE if it is not.
	 * @param Placement|null $convertedPlacement The converted image, if any conversion has been done for it. If not this property is null.
	 * @return string The external id of the gallery image.
	 * @throws BizException when communicating with WordPress returns an error.
	 */
	private function uploadGalleryImage( $wpClient, $publishForm, $publishTarget, $imageObj, $galleryId, $preview, $convertedPlacement )
	{
		$imageDescription = null;
		$externalId = null;
		foreach( $imageObj->MetaData->ExtraMetaData as $extraData ) {
			if( $extraData->Property == 'C_WORDPRESS_IMAGE_DESCRIPTION' ) {
				$imageDescription = $extraData->Values[0];
				break;
			}
		}

		if( $preview ) { // Don't get the externalId when previewing because we want to upload a new set of images.
			$uploadNeeded = true;
		} else {
			if( $convertedPlacement ) {
				$uploadNeeded = $this->isConvertedImageUploadNeeded( $convertedPlacement );
				$externalId = $convertedPlacement->ConvertedImageToPublish->ExternalId;
			} else {
				// External id is set by reference.
				$uploadNeeded = $this->isUploadChildNeeded( $publishForm, $imageObj, $publishTarget, $externalId );
			}
		}

		if( $uploadNeeded ) {
			$filePath = null;
			$extension = $imageObj->Relations[0]->ChildInfo->Format;
			if( $convertedPlacement ) {
				if( $convertedPlacement->ConvertedImageToPublish->Attachment ) {
					$filePath = $convertedPlacement->ConvertedImageToPublish->Attachment->FilePath;
				}
			} else {
				$filePath = $this->saveLocal( $imageObj->MetaData->BasicMetaData->ID );
			}

			if( $filePath ) {
				$imageName = $imageObj->MetaData->BasicMetaData->Name.'.'.pathinfo( $filePath, PATHINFO_EXTENSION );
				if( $externalId ) { // update existing?
					try {
						$response = $wpClient->updateImage( $imageName, $filePath, $extension, $galleryId, $externalId );
					} catch( BizException $e ) {
						throw new BizException( 'WORDPRESS_ERROR_UPDATE_IMAGE', 'SERVER', $e->getDetail() );
					}
				} else { // upload new?
					try {
						$response = $wpClient->uploadImage( $imageName, $filePath, $extension, $galleryId );
					} catch( BizException $e ) {
						throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'SERVER', $e->getDetail() );
					}
				}
				$externalId = $response['pid']; // WordPress file id
			}
		}
		if( isset($externalId) ) {
			$wpClient->updateImageMetaData( $externalId, $imageObj->MetaData->BasicMetaData->Name, $imageDescription );
		}

		return $externalId;
	}

	/**
	 * Checks if the child object has been changed since it was last published.
	 *
	 * Checks if the child object has been changed since the last time it was published.
	 * If the published version of the object is older (older version number) then the
	 * object needs to be re-uploaded to Drupal. When the child has never been uploaded
	 * (publishing for the first time) the object needs to be uploaded as well.
	 *
	 * @param Object $publishForm
	 * @param Object $childObj
	 * @param PubPublishTarget $publishTarget
	 * @param string $externalId
	 * @return bool True when child object upload is needed; False otherwise.
	 */
	private function isUploadChildNeeded( $publishForm, $childObj, $publishTarget, &$externalId )
	{
		$publishedChildVersion = null;
		$publishedChildPublishDate = null;
		$formRelations = $publishForm->Relations;
		if( $formRelations ) foreach( $formRelations as $relation ) {
			if( $relation->Parent == $publishForm->MetaData->BasicMetaData->ID &&
				$relation->Child == $childObj->MetaData->BasicMetaData->ID &&
				$relation->Type == 'Placed'
			) {
				if( $relation->Targets ) foreach( $relation->Targets as $target ) {
					$isSameIssue = ( $target->Issue->Id == $publishTarget->IssueID );
					if( $isSameIssue ) {
						$publishedChildVersion = $target->PublishedVersion;
						$publishedChildPublishDate = $target->PublishedDate;
						$externalId = $target->ExternalId;
						break 2;
					}
				}
			}
		}
		$uploadNeeded = false;
		if( !$publishedChildVersion || !$publishedChildPublishDate || !$externalId ) {
			$uploadNeeded = true; // Has never been uploaded or is unpublished before (PublishDate set empty).
		} elseif( $publishedChildVersion ) {
			require_once BASEDIR.'/server/utils/VersionUtils.class.php';
			if( VersionUtils::versionCompare( $publishedChildVersion, $childObj->MetaData->WorkflowMetaData->Version, '<' ) ) {
				$uploadNeeded = true; // Modification has been done since published, so re-upload is needed.
			}
		}
		return $uploadNeeded;
	}

	/**
	 * Determines whether or not a cropped image should be uploaded to Drupal again.
	 *
	 * @since 10.1.0
	 * @param Placement $convertedPlacement
	 * @return bool
	 */
	private function isConvertedImageUploadNeeded( Placement $convertedPlacement )
	{
		return !$convertedPlacement->ConvertedImageToPublish->ExternalId &&
			$convertedPlacement->ConvertedImageToPublish->Attachment;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDossierURL( $dossier, $objectsInDossier, $publishTarget )
	{}

	/**
	 * {@inheritdoc}
	 */
	public function doesSupportPublishForms() 
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function doesSupportCropping()
	{
		return true;
	}

	/**
	 * Returns the publishing templates given the pub channel id.
	 *
	 * @since 9.0
	 * @param integer $pubChannelId Publication Channel Id.
	 * @return array|Null Array of templates|The default connector returns null which indicates it doesn't support publishing forms.
	 */
	public function getPublishFormTemplates( $pubChannelId )
	{
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		// Create the templates.
		$templatesObj = array();
		$documentIdPrefix = $this->getDocumentIdPrefix();

		$wordpressUtils = new WordPress_Utils();
		$publishTarget = new PubPublishTarget( $pubChannelId );
		$siteName = $wordpressUtils->getSiteName( $publishTarget );

		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId, 'WordPress post', 'Publish a text article to WordPress', $documentIdPrefix.$siteName );

		return $templatesObj;
	}

	/**
	 * Returns the prefix used in the document id
	 *
	 * @return String The Document Id prefix: "WordPress<SITE_ID>_"
	 */
	private function getDocumentIdPrefix()
	{
		return self::DOCUMENT_PREFIX . '_' . self::SITE_ID . '_';
	}

	/**
	 * This function can return a dialog that is shown in Content Station. 
	 * This is used for the Multi Channel Publishing Feature.
	 *
	 * @since 9.0
	 * @param Object $publishFormTemplate
	 * @return Dialog|null Dialog definition|The default connector returns null which indicates it doesn't support the getDialog call.
	 */
	public function getDialogForSetPublishPropertiesAction( $publishFormTemplate ) 
	{
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		require_once dirname(__FILE__).'/WordPress_CustomObjectMetaData.class.php';

		$dialog = WW_Utils_PublishingUtils::getDefaultPublishingDialog($publishFormTemplate->MetaData->BasicMetaData->Name);
		$wordpressUtils = new WordPress_Utils();
		$target = reset($publishFormTemplate->Targets);
		$publishTarget = new PubPublishTarget( $target->PubChannel->Id );
		$siteName = $wordpressUtils->getSiteName( $publishTarget );
		$clientWordPress = new WordPressXmlRpcClient( $publishTarget );
		$documentIdPrefix = $this->getDocumentIdPrefix();
		$customObjectMetaData = new WordPress_CustomObjectMetaData();

		// Create / Add widgets.
		switch( $publishFormTemplate->MetaData->BasicMetaData->DocumentID ) {
			case $documentIdPrefix . $siteName : // WordPress post
				//  **Post Tab**
				$tabPost = WW_Utils_PublishingUtils::getPublishingTab( 'Publish Settings' );

				// Title widget
				$urlWidget = new DialogWidget();
				$urlWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_POST_TITLE');
				$urlWidgetUsage = new PropertyUsage();
				$urlWidgetUsage->Name = $urlWidget->PropertyInfo->Name;
				$urlWidgetUsage->Editable = true;
				$urlWidgetUsage->Mandatory = true;
				$urlWidgetUsage->Restricted = false;
				$urlWidgetUsage->RefreshOnChange = false;
				$urlWidget->PropertyUsage = $urlWidgetUsage;
				$tabPost->Widgets[] = $urlWidget;

				// Date widget
				$publishDateWidget = new DialogWidget();
				$publishDateWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_PUBLISH_DATE');
				$publishDateWidgetUsage = new PropertyUsage();
				$publishDateWidgetUsage->Name = $publishDateWidget->PropertyInfo->Name;
				$publishDateWidgetUsage->Editable = true;
				$publishDateWidgetUsage->Mandatory = false;
				$publishDateWidgetUsage->Restricted = false;
				$publishDateWidgetUsage->RefreshOnChange = false;
				$publishDateWidget->PropertyUsage = $publishDateWidgetUsage;
				$tabPost->Widgets[] = $publishDateWidget;

				// Visibility widget
				$visibilityWidget = new DialogWidget();
				$visibilityWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_VISIBILITY');
				$visibilityWidgetUsage = new PropertyUsage();
				$visibilityWidgetUsage->Name = $visibilityWidget->PropertyInfo->Name;
				$visibilityWidgetUsage->Editable = true;
				$visibilityWidgetUsage->Mandatory = false;
				$visibilityWidgetUsage->Restricted = false;
				$visibilityWidgetUsage->RefreshOnChange = false;
				$visibilityWidget->PropertyUsage = $visibilityWidgetUsage;
				$tabPost->Widgets[] = $visibilityWidget;

				$dialog->Tabs[] = $tabPost;

				// Message widget
				$articleWidget = new DialogWidget();
				$articleWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_PF_MESSAGE');
				$articleWidgetUsage = new PropertyUsage();
				$articleWidgetUsage->Name = $articleWidget->PropertyInfo->Name;
				$articleWidgetUsage->Editable = true;
				$articleWidgetUsage->Mandatory = false;
				$articleWidgetUsage->Restricted = false;
				$articleWidgetUsage->RefreshOnChange = false;
				$articleWidget->PropertyUsage = $articleWidgetUsage;

				$widget = new DialogWidget();
				$widget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_PF_MESSAGE_SEL');
				$widget->PropertyInfo->Widgets = array($articleWidget);
				$widgetUsage = new PropertyUsage();
				$widgetUsage->Name = $widget->PropertyInfo->Name;
				$widgetUsage->Editable = true;
				$widgetUsage->Mandatory = false;
				$widgetUsage->Restricted = false;
				$widgetUsage->RefreshOnChange = false;
				$widgetUsage->InitialHeight = 300;
				$widget->PropertyUsage = $widgetUsage;

				$tabPost->Widgets[] = $widget;

				$tabFeaturedImage = WW_Utils_PublishingUtils::getPublishingTab( 'Featured image' );

				// Media widget
				$imageFileWidget = new DialogWidget();
				$imageFileWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_FEATURED_IMAGE_FILE');
				$imageFileWidget->PropertyInfo->Widgets = $this->getFileWidgets();
				$imageFileWidgetUsage = new PropertyUsage();
				$imageFileWidgetUsage->Name = $imageFileWidget->PropertyInfo->Name;
				$imageFileWidgetUsage->Editable = true;
				$imageFileWidgetUsage->Mandatory = false;
				$imageFileWidgetUsage->Restricted = false;
				$imageFileWidgetUsage->RefreshOnChange = false;
				$imageFileWidget->PropertyUsage = $imageFileWidgetUsage;

				$imageWidget = new DialogWidget();
				$imageWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_FEATURED_IMAGE');
				$imageWidget->PropertyInfo->Widgets = array($imageFileWidget);
				$imageWidgetUsage = new PropertyUsage();
				$imageWidgetUsage->Name = $imageWidget->PropertyInfo->Name;
				$imageWidgetUsage->Editable = true;
				$imageWidgetUsage->Mandatory = false;
				$imageWidgetUsage->Restricted = false;
				$imageWidgetUsage->RefreshOnChange = false;
				$imageWidget->PropertyUsage = $imageWidgetUsage;
				$tabFeaturedImage->Widgets[] = $imageWidget;

				$dialog->Tabs[] = $tabFeaturedImage;

				if( $clientWordPress->checkNextGenEnabled() ) { // check if the NextGen plug-in is installed else don't show the gallery tab
					// **Gallery tab**
					$tabGallery = WW_Utils_PublishingUtils::getPublishingTab( 'Gallery' );

					// Media widget
					$imagesFileWidget = new DialogWidget();
					$imagesFileWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_MULTI_IMAGES_FILE');
					$imagesFileWidget->PropertyInfo->Widgets = $this->getFileWidgets();
					$imagesFileWidgetUsage = new PropertyUsage();
					$imagesFileWidgetUsage->Name = $imagesFileWidget->PropertyInfo->Name;
					$imagesFileWidgetUsage->Editable = true;
					$imagesFileWidgetUsage->Mandatory = false;
					$imagesFileWidgetUsage->Restricted = false;
					$imagesFileWidgetUsage->RefreshOnChange = false;
					$imagesFileWidget->PropertyUsage = $imagesFileWidgetUsage;

					$imagesWidget = new DialogWidget();
					$imagesWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_MULTI_IMAGES');
					$imagesWidget->PropertyInfo->Widgets = array($imagesFileWidget);
					$imagesWidgetUsage = new PropertyUsage();
					$imagesWidgetUsage->Name = $imagesWidget->PropertyInfo->Name;
					$imagesWidgetUsage->Editable = true;
					$imagesWidgetUsage->Mandatory = false;
					$imagesWidgetUsage->Restricted = false;
					$imagesWidgetUsage->RefreshOnChange = false;
					$imagesWidget->PropertyUsage = $imagesWidgetUsage;
					$tabGallery->Widgets[] = $imagesWidget;

					$dialog->Tabs[] = $tabGallery;
				}

				// **Tagging tab**
				$tabTagging = WW_Utils_PublishingUtils::getPublishingTab( 'Tags' );

				// Categories widget
				$categoriesWidget = new DialogWidget();
				$categoriesWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_CAT_' . strtoupper($siteName) );
				$categoriesWidgetUsage = new PropertyUsage();
				$categoriesWidgetUsage->Name = $categoriesWidget->PropertyInfo->Name;
				$categoriesWidgetUsage->Editable = true;
				$categoriesWidgetUsage->Mandatory = false;
				$categoriesWidgetUsage->Restricted = false;
				$categoriesWidgetUsage->RefreshOnChange = false;
				$categoriesWidget->PropertyUsage = $categoriesWidgetUsage;
				$tabTagging->Widgets[] = $categoriesWidget;

				// Tags widget
				$tagsWidget = new DialogWidget();
				$tagsWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_TAGS_' . strtoupper($siteName) );
				$tagsWidgetUsage = new PropertyUsage();
				$tagsWidgetUsage->Name = $tagsWidget->PropertyInfo->Name;
				$tagsWidgetUsage->Editable = true;
				$tagsWidgetUsage->Mandatory = false;
				$tagsWidgetUsage->Restricted = false;
				$tagsWidgetUsage->RefreshOnChange = false;
				$tagsWidget->PropertyUsage = $tagsWidgetUsage;
				$tabTagging->Widgets[] = $tagsWidget;

				$dialog->Tabs[] = $tabTagging;

				// **Other tab**
				$tabOther = WW_Utils_PublishingUtils::getPublishingTab( 'General Settings' );

				// Slug widget
				$slugWidget = new DialogWidget();
				$slugWidget->PropertyInfo = $customObjectMetaData->getProperty('C_WORDPRESS_SLUG');
				$slugWidgetUsage = new PropertyUsage();
				$slugWidgetUsage->Name = $slugWidget->PropertyInfo->Name;
				$slugWidgetUsage->Editable = true;
				$slugWidgetUsage->Mandatory = false;
				$slugWidgetUsage->Restricted = false;
				$slugWidgetUsage->RefreshOnChange = false;
				$slugWidget->PropertyUsage = $slugWidgetUsage;
				$tabOther->Widgets[] = $slugWidget;

				// Format widget
				$formatWidget = new DialogWidget();
				$formatWidget->PropertyInfo = $customObjectMetaData->getProperty( 'C_WORDPRESS_FORMAT_' . strtoupper($siteName) );
				$formatWidgetUsage = new PropertyUsage();
				$formatWidgetUsage->Name = $formatWidget->PropertyInfo->Name;
				$formatWidgetUsage->Editable = true;
				$formatWidgetUsage->Mandatory = false;
				$formatWidgetUsage->Restricted = false;
				$formatWidgetUsage->RefreshOnChange = false;
				$formatWidget->PropertyUsage = $formatWidgetUsage;
				$tabOther->Widgets[] = $formatWidget;

				// Allow comments widget
				$allowCommentsWidget = new DialogWidget();
				$allowCommentsWidget->PropertyInfo = $customObjectMetaData->getProperty ( 'C_WORDPRESS_ALLOW_COMMENTS' );
				$allowCommentsWidgetUsage = new PropertyUsage();
				$allowCommentsWidgetUsage->Name = $allowCommentsWidget->PropertyInfo->Name;
				$allowCommentsWidgetUsage->Editable = true;
				$allowCommentsWidgetUsage->Mandatory = false;
				$allowCommentsWidgetUsage->Restricted = false;
				$allowCommentsWidgetUsage->RefreshOnChange = false;
				$allowCommentsWidget->PropertyUsage = $allowCommentsWidgetUsage;
				$tabOther->Widgets[] = $allowCommentsWidget;

				// Sticky widget
				$stickyWidget = new DialogWidget();
				$stickyWidget->PropertyInfo = $customObjectMetaData->getProperty ( 'C_WORDPRESS_STICKY' );
				$stickyWidgetUsage = new PropertyUsage();
				$stickyWidgetUsage->Name = $stickyWidget->PropertyInfo->Name;
				$stickyWidgetUsage->Editable = true;
				$stickyWidgetUsage->Mandatory = false;
				$stickyWidgetUsage->Restricted = false;
				$stickyWidgetUsage->RefreshOnChange = false;
				$stickyWidget->PropertyUsage = $stickyWidgetUsage;
				$tabOther->Widgets[] = $stickyWidget;

				// Excerpt widget
				$excerptWidget = new DialogWidget();
				$excerptWidget->PropertyInfo = $customObjectMetaData->getProperty ( 'C_WORDPRESS_EXCERPT' );
				$excerptWidgetUsage = new PropertyUsage();
				$excerptWidgetUsage->Name = $excerptWidget->PropertyInfo->Name;
				$excerptWidgetUsage->Editable = true;
				$excerptWidgetUsage->Mandatory = false;
				$excerptWidgetUsage->Restricted = false;
				$excerptWidgetUsage->RefreshOnChange = false;
				$excerptWidget->PropertyUsage = $excerptWidgetUsage;
				$tabOther->Widgets[] = $excerptWidget;

				$dialog->Tabs[] = $tabOther;
				break;
		}
		return $dialog;
	}

	/**
	 * Get file info widgets
	 *
	 * These widgets are used to show the info of a picture in content station.
	 *
	 * @return array
	 */
	private function getFileWidgets()
	{
		require_once dirname(__FILE__).'/WordPress_CustomObjectMetaData.class.php';
		$customObjectMetaData = new WordPress_CustomObjectMetaData();
		$standardProperties = BizProperty::getPropertyInfos();
		$fileWidgets = array();

		// Description
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = $customObjectMetaData->getPropertyFromOthers( 'C_WORDPRESS_IMAGE_DESCRIPTION' );
		$fileWidget->PropertyUsage = new PropertyUsage();
		$fileWidget->PropertyUsage->Name = $fileWidget->PropertyInfo->Name;
		$fileWidget->PropertyUsage->Editable = true;
		$fileWidget->PropertyUsage->Mandatory = false;
		$fileWidget->PropertyUsage->Restricted = false;
		$fileWidget->PropertyUsage->RefreshOnChange = false;
		$fileWidget->PropertyUsage->InitialHeight = 0;
		$fileWidgets[] = $fileWidget;

		// Image height
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = $standardProperties['Height'];
		$fileWidget->PropertyUsage = new PropertyUsage();
		$fileWidget->PropertyUsage->Name = $fileWidget->PropertyInfo->Name;
		$fileWidget->PropertyUsage->Editable = false;
		$fileWidget->PropertyUsage->Mandatory = true;
		$fileWidget->PropertyUsage->Restricted = false;
		$fileWidget->PropertyUsage->RefreshOnChange = false;
		$fileWidget->PropertyUsage->InitialHeight = 0;
		$fileWidgets[] = $fileWidget;

		// Image width
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = $standardProperties['Width'];
		$fileWidget->PropertyUsage = new PropertyUsage();
		$fileWidget->PropertyUsage->Name = $fileWidget->PropertyInfo->Name;
		$fileWidget->PropertyUsage->Editable = false;
		$fileWidget->PropertyUsage->Mandatory = true;
		$fileWidget->PropertyUsage->Restricted = false;
		$fileWidget->PropertyUsage->RefreshOnChange = false;
		$fileWidget->PropertyUsage->InitialHeight = 0;
		$fileWidgets[] = $fileWidget;

		// Image format
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = $standardProperties['Format'];
		$fileWidget->PropertyUsage = new PropertyUsage();
		$fileWidget->PropertyUsage->Name = $fileWidget->PropertyInfo->Name;
		$fileWidget->PropertyUsage->Editable = false;
		$fileWidget->PropertyUsage->Mandatory = true;
		$fileWidget->PropertyUsage->Restricted = false;
		$fileWidget->PropertyUsage->RefreshOnChange = false;
		$fileWidget->PropertyUsage->InitialHeight = 0;
		$fileWidgets[] = $fileWidget;

		// Image name
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = $standardProperties['Name'];
		$fileWidget->PropertyUsage = new PropertyUsage();
		$fileWidget->PropertyUsage->Name = $fileWidget->PropertyInfo->Name;
		$fileWidget->PropertyUsage->Editable = true;
		$fileWidget->PropertyUsage->Mandatory = true;
		$fileWidget->PropertyUsage->Restricted = false;
		$fileWidget->PropertyUsage->RefreshOnChange = false;
		$fileWidget->PropertyUsage->InitialHeight = 0;
		$fileWidgets[] = $fileWidget;

		return $fileWidgets; // Widgets in widgets (in widgets)
	}


	/**
	 * Composes a Dialog->MetaData list from dialog widgets and custom properties.
	 *
	 * @param $extraMetaDatas array of ExtraMetaData elements
	 * @param $widgets array of DialogWidget elements
	 * @return array List of MetaDataValue elements
	 */
	public function extractMetaDataFromWidgets( $extraMetaDatas, $widgets )
	{
		$metaDataValues = array();
		if( $widgets ) {
			foreach( $widgets as $widget ) {
				if( $extraMetaDatas ) foreach( $extraMetaDatas as $extraMetaData ) {
					if( $widget->PropertyInfo->Name == $extraMetaData->Property ) {
						$metaDataValue = new MetaDataValue();
						$metaDataValue->Property = $extraMetaData->Property;
						$metaDataValue->Values = $extraMetaData->Values; // array of string
						$metaDataValues[] = $metaDataValue;
						break; // found
					}
				}
			}
		}
		return $metaDataValues;
	}

	/**
	 * The server plug-in can provide its own icons to show in UI for its channels.
	 * When the connector return TRUE, it should provide icons its plug-ins folder as follows:
	 *    plugins/<yourplugin>/pubchannelicons
	 * In that folder there can be the following files:
	 *    16x16.png
	 *    24x24.png
	 *    32x32.png
	 *    48x48.png
	 *
	 * @since 9.0
	 * @return boolean
	 */
	public function hasPubChannelIcons() 
	{
		return true;
	}

	public function getPrio() { return self::PRIO_DEFAULT; }

	/**
	 * Retrieves an object from the ObjectsService and stores a local copy to be uploaded.
	 *
	 * File structure from the object can differ.
	 *
	 * @param int $id The id for the object to be stored.
	 *
	 * @return string $exportName The name of the exported object.
	 *
	 * @throws BizException Throws an exception if something goes wrong.
	 */
	public function saveLocal( $id )
	{
		// Check and create the directory as needed.
		if( !is_dir(TEMPDIRECTORY) ) {
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir(TEMPDIRECTORY);
			// Check if the directory was created.
			if( !is_dir(TEMPDIRECTORY) ) {
				$msg = 'The directory: "' . TEMPDIRECTORY . '" does not exist, or could not be created.';
				$detail = 'The directory "' . TEMPDIRECTORY . '" could not be created. Either create it manually and '
					. 'ensure the web server can write to it, or check that the webserver has rights to write to the sub-'
					. 'directories in the path.';

				throw new BizException(null, 'Server', $detail, $msg);
			}
		}

		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$object = $this->getObject( $id );
		$type = $object->MetaData->BasicMetaData->Type;
		// We need to get a unique name for the object, in case there are multiple objects with the same name.
		$name = $id . $type;
		// Get the format, and the proper extension for the format.
		$format = $object->MetaData->ContentMetaData->Format;
		$extension = MimeTypeHandler::mimeType2FileExt( $format );
		$exportName = TEMPDIRECTORY . '/' . $name . $extension;

		// Retrieve file data.
		$filePath = null;
		if( isset($object->Files[0]->FilePath) ) {
			$filePath = $object->Files[0]->FilePath;
		}
		$content = file_get_contents($filePath);

		if( is_null($content) ) {
			$msg = 'Could not retrieve file contents';
			$detail = (is_string($filePath)) ? $filePath : 'Attachment (' . $id . ')';
			$detail .= ' did not yield proper content.';
			throw new BizException(null, 'Server', $detail, $msg);
		}
		file_put_contents( $exportName, $content );

		// Check if the file was created.
		if( !file_exists($exportName) ) {
			$detail = 'File: ' . $exportName . ' for ' . $type . ', object (ID: ' . $id . ') Does not seem to be a valid file.';
			$msg = 'Unable to save ' . $type . ' data locally.';
			throw new BizException(null, 'Server', $detail, $msg);
		}

		return $exportName;
	}

	/**
	 * Uses the GetObjectsService to return a business object by its id.
	 *
	 * Retrieves the business object by id, uses the WflGetObjectsService to retrieve
	 * the object.
	 *
	 * @param string $id The id of the object to be retrieved.
	 * @param bool $lock Whether to lock the object or not.
	 * @param string $rendition Rendition option.
	 * @param array $requestInfo Request specific information by changing the array.
	 *
	 * @return null|Object $object The retrieved object.
	 *
	 * @see /Enterprise/server/bizclasses/BizObject.class.php
	 */
	private function getObject( $id, $lock = false, $rendition = 'native', $requestInfo = array() )
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$service = new WflGetObjectsService();
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = array( $id );
		$request->Lock = $lock;
		$request->Rendition = $rendition;
		$request->RequestInfo = $requestInfo;
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;

		/* @var WflGetObjectsResponse $resp */
		$resp = $service->execute($request);

		if( $resp->Objects ) {
			return $resp->Objects[0];
		}
		return null;
	}

	/**
	 * Get the first object in a dossier which match with object type
	 *
	 * @param $objectsInDossier
	 * @param $objectType
	 * @return Object|null Return object if found else return null
	 */
	private function getFirstObjectWithType( $objectsInDossier, $objectType )
	{
		$objectFound = null;
		foreach( $objectsInDossier as $objectDossier ) {
			if( $objectDossier->MetaData->BasicMetaData->Type == $objectType ) {
				$objectFound = $objectDossier;
				break;
			}
		}
		return $objectFound;
	}

	/**
	 * Check and validate if the inline image format is wordpress supported image format or not
	 *
	 * @param array $attachments array of attachments
	 * @throws BizException When unsupported image found, throw bizexception
	 */
	private function validateInlineImageFormat( $attachments )
	{
		$unsupportedInlineImages = array();
		$supportedImageFormats = $this->getSupportedImageFormats();
		if( $attachments ) {
			$inlineImages = array_keys($attachments);
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
			foreach( $inlineImages as $imageId ) {
				$format = $attachments[$imageId]->Type;
				if( !in_array( $format, $supportedImageFormats ) ) {
					$inlineImageName = DBObject::getObjectName( $imageId );
					$unsupportedInlineImages[] = $inlineImageName.MimeTypeHandler::mimeType2FileExt($format);
				}
			}
		}
		if( $unsupportedInlineImages ) {
			throw new BizException( 'WORDPRESS_ERROR_UNSUPPORTED_IMAGE', 'Server', 'Unsupported Image', null, array( implode(', ', $unsupportedInlineImages) ) );
		}
	}
}