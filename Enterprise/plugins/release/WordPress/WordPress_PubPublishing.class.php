<?php
/****************************************************************************
   Copyright 2013 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/WordPressXmlRpcClient.class.php';
require_once dirname(__FILE__) . '/WordPress_Utils.class.php';

class WordPress_PubPublishing extends PubPublishing_EnterpriseConnector
{
	const DOCUMENT_PREFIX = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
	const SITE_ID = '0';

	/**
	 * Publishes a dossier with contained objects (articles. images, etc.) to an external publishing system.
	 * The plugin is supposed to publish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 * 
	 * @return array of PubField containing information from publishing system
	 *
	 * @throws BizException
	 */
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget ) 
	{
		$pubField = $this->preparePost($objectsInDossier, $dossier, $publishTarget, 'publish');
		$pubFields[] = $pubField;

		return $pubFields;
	}

	/**
	 * Publish or Update post to WordPress.
	 *
	 * This function is used to update or publish a post from WordPress.
	 * With the action parameter 'publish' or 'update' can be given to determine which action should be done.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 * @param $action
	 * @return PubField
	 * @throws BizException
	 */
	function preparePost(&$objectsInDossier, &$dossier, $publishTarget, $action)
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';

		$publishForm = null;
		$article = null;
		$messageText = null;
		$pubField = null;

		foreach($objectsInDossier as $objectDossier){
			if($objectDossier->MetaData->BasicMetaData->Type == 'Article'){
				$article = $objectDossier;
				break;
			}
		}

		foreach ($objectsInDossier as $objectInDossier) {
			if ($objectInDossier->MetaData->BasicMetaData->Type == 'PublishForm') {
				$publishForm = $objectInDossier;
				break;
			}
		}

		$objXMLRPClientWordPress = new WordPressXmlRpcClient( $publishTarget );

		try{
			$objXMLRPClientWordPress->deleteOldPublishedPreviews(); // delete all old previews
		} catch( Exception $e ){ // we want to continue when we could not remove the previews
			LogHandler::Log( 'SERVER', 'WARN', 'Could not remove old published reviews', $e );
		}

		$wordpressUtils = new WordPress_Utils();
		$siteName = $wordpressUtils->getSiteName( $publishTarget );

		if (!is_null($publishForm)) {
			switch ( BizPublishForm::getDocumentId($publishForm) ) {
				//WordPress post
				case $this->getDocumentIdPrefix() . $siteName :
					$images = null;
					$size = null;
					$count = 0;
					$attachments = null;
					$inlineImagesCustomField = null;
                    $featuredImagesCustomField = null;
					$galleriesCustomField = null;
					$galleryId = null;
					$title = null;

					$publishFormOBJS = BizPublishForm::getFormFields($publishForm);
					$articleFields = BizPublishForm::extractFormFieldDataFromFieldValue ( 'C_WORDPRESS_PF_MESSAGE_SEL', $article, true );

					if ($articleFields && isset($articleFields[0]) && isset($articleFields[0]['elements']) && isset($articleFields[0]['elements'][0])){
						$messageText = $articleFields[0]['elements'][0]->Content;
					}

					// This is used for the Inline images so is not used right now
					if ($articleFields && isset($articleFields[0]) && isset($articleFields[0]['attachments'])) {
						$attachments = $articleFields[0]['attachments'];
					}

					$unsupportedInlineImages = array();
					$supportedImageFormats = $this->getSupportedImageFormats();

					if( $attachments ){
						$inlineImages = array_keys($attachments);

						foreach($inlineImages as $imageId){
							require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
							$extension = $attachments[$imageId]->Type;

							if( !in_array( $extension, $supportedImageFormats )){
								$inlineImageName = DBObject::getObjectName( $imageId );
								$extension = explode('/', $extension);
								$extension = '.' . $extension[1];
								$unsupportedInlineImages[] = $inlineImageName . $extension;
							}
						}
					}

					if( $unsupportedInlineImages ){
						$inlineImageCount = count( $unsupportedInlineImages );
						$unsupported = null;
						$count = 0;

						foreach( $unsupportedInlineImages as $unsupportedInlineImage ){
							$unsupported .= $unsupportedInlineImage;
							$count += 1;

							if( $inlineImageCount < $count ){
								$unsupported .= ', ';
							}
						}

						$reasonParams = array( $unsupported );
						throw new BizException( 'WORDPRESS_ERROR_UNSUPPORTED_IMAGE', 'Server', 'Unsupported Image', null, $reasonParams);
					}

					//Get the title
					if( isset($publishFormOBJS['C_WORDPRESS_POST_TITLE']) ){
						$title = $publishFormOBJS['C_WORDPRESS_POST_TITLE'][0];
					}

					if ( !$title) {
						throw new BizException( 'WORDPRESS_ERROR_NO_TITLE', 'Server', 'A title is needed but there is no title found.');
					}

					if( strtolower($action) == 'update' ){ // deleting images from WordPress is only needed when updating
						$customFieldIds = $objXMLRPClientWordPress->deleteInlineAndFeaturedImages( $dossier->ExternalId );
                        $inlineImagesCustomField = ( isset($customFieldIds['inline-custom-field']) ? $customFieldIds['inline-custom-field'] : null );
                        $featuredImagesCustomField = ( isset($customFieldIds['featured-custom-field']) ? $customFieldIds['featured-custom-field'] : null );
						$gallery = $this->getGalleryByWidgetName( $publishTarget, $dossier->ExternalId, 'C_WORDPRESS_MULTI_IMAGES' );

						if( isset($gallery['customFieldId']) ){
							$galleriesCustomField = $gallery['customFieldId'];
						}

						if( isset($gallery['galleryId']) ){
							$galleryId = $gallery['galleryId'];
						}

						if( $galleryId ){
							$pubChannelId = $publishTarget->PubChannelID;
							$dossiersPublished = DBPublishHistory::getPublishHistoryDossier($dossier->MetaData->BasicMetaData->ID, $pubChannelId, $publishTarget->IssueID, null, true);
							$dossierPublished = reset( $dossiersPublished ); // Get the first dossier.
							$publishedObjects = DBPublishedObjectsHist::getPublishedObjectsHist($dossierPublished['id']);

							foreach ($publishedObjects as $relation) {
								if ($relation['type'] == 'Image') {
									$deleted = true;
									foreach($objectsInDossier as $object){
										if($object->MetaData->BasicMetaData->Type == 'Image'){
											if($object->MetaData->BasicMetaData->ID == $relation['objectid']){
												$deleted = false;
												break;
											}
										}
									}

									$externalId = null;
									if($deleted){
										$externalId = DBPublishedObjectsHist::getObjectExternalId( $dossier->MetaData->BasicMetaData->ID, $relation['objectid'], $pubChannelId, $publishTarget->IssueID, null, $dossierPublished['id'] );
									}

									if( $externalId ){
										$objXMLRPClientWordPress->deleteImage( $externalId );
									}
								}
							}
						}
					}

					$uploadImagesResult = null;
					if( strtolower( $action ) == 'preview' ){ // if preview we don't want to save anything or use the existing gallery because that will corrupt the dossier
						$uploadImagesResult = $this->uploadImages( $objectsInDossier,  $publishTarget, null, $dossier->MetaData->BasicMetaData->Name, $attachments, isset($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']), true);
					} else if( $galleryId ){ // use already existing gallery
						$uploadImagesResult = $this->uploadImages( $objectsInDossier,  $publishTarget, $galleryId, null, $attachments, isset($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']));
					} else{ // create a new gallery
						$uploadImagesResult = $this->uploadImages( $objectsInDossier,  $publishTarget, null, $dossier->MetaData->BasicMetaData->Name, $attachments, isset($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']));
					}

					if( isset( $uploadImagesResult['attachments'] )){
						$attachments = $uploadImagesResult['attachments'];
						$pattern = '/<img[^>]*id=\"ent_([^\"]*)[^>]*src=\"([^\"]*)[^>]*>/i';
						$patternResult = array();
						preg_match_all( $pattern, $messageText, $patternResult );

						foreach( $attachments as $attachment ){
							for( $i=0; $i<count($patternResult[0]); $i++ ) {
								if( $patternResult[1][$i] == $attachment['ent_id'] ) {
									$imageUrl = str_replace( $patternResult[2][$i], $attachment['url'], $patternResult[0][$i] );
									$messageText = str_replace( $patternResult[0][$i], $imageUrl, $messageText );
								}
							}
						}
					}

					if(isset($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'])){
						$size = count($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']);
					}

					if( $size > 1 ){
						foreach($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] as $image){
							foreach($objectsInDossier as $dossierObject){
								if($dossierObject->MetaData->BasicMetaData->Type == 'Image'){
									if($dossierObject->MetaData->BasicMetaData->ID == $image->MetaData->BasicMetaData->ID){
										$images .= $dossierObject->ExternalId;
										$count += 1;

										if($count != $size){
											$images .=  ',';
										}

										break;
									}
								}
							}
						}
					} else if($size == 1){
						foreach($objectsInDossier as $dossierObject){
							if($dossierObject->MetaData->BasicMetaData->Type == 'Image'){
								if($dossierObject->MetaData->BasicMetaData->ID == $publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']->MetaData->BasicMetaData->ID){
									$images .= $dossierObject->ExternalId;
								}
							}
						}
					}

					if($images && $size == 1){
						$messageText .= '[ngg_images gallery_ids="' . $galleryId . '" image_ids="'  . $images . '" display_type="photocrati-nextgen_basic_singlepic"]';
					}

					if($images && $size > 1){
						$messageText .= '[ngg_images gallery_ids="' . $galleryId . '" image_ids="'  . $images . '" display_type="photocrati-nextgen_basic_slideshow"]';
					}

					$postContent = null;
					$postContent['title'] = $title;
					$postContent['body'] = $messageText; // Always exists because there should be at least 1 image or some text
					$postContent['authorId'] = $this->getWordPressUserIdForCurrentUser( $siteName );

					if( isset( $uploadImagesResult['galleryId'] )){
						$postContent['gallProps']['galleries']['C_WORDPRESS_MULTI_IMAGES'] = $uploadImagesResult['galleryId'];
						$postContent['gallProps']['customFieldId'] = $galleriesCustomField;
					}

					if( isset( $publishFormOBJS['C_WORDPRESS_CAT_' . strtoupper($siteName)] )){
                        $categories = $publishFormOBJS['C_WORDPRESS_CAT_' . strtoupper($siteName)];
                        $convertedCategories = array();

                        foreach( $categories as $category ){ // delete the hierarchical prefixes.
                            $convertedCategory = preg_replace('/^-+\ /', '', $category);
                            $convertedCategories[] = $convertedCategory;
                        }

                        $postContent['categories'] = $convertedCategories;
                    }

					if( isset( $uploadImagesResult['inline-ids-for-saving'] ) ){
						$postContent['inlineImages']['inline-ids'] = $uploadImagesResult['inline-ids-for-saving'];
						if( $inlineImagesCustomField ){
							$postContent['inlineImages']['customFieldId'] = $inlineImagesCustomField;
						}
					}

                    if( isset( $uploadImagesResult['featured-image'] ) ){
                        $postContent['featuredImage']['featured-image'] = $uploadImagesResult['featured-image'];
                        if( $featuredImagesCustomField ){
                            $postContent['featuredImage']['customFieldId'] = $featuredImagesCustomField;
                        }
                    }

					if( isset( $publishFormOBJS['C_WORDPRESS_FORMAT_' . strtoupper($siteName)] )){
						$format = $publishFormOBJS['C_WORDPRESS_FORMAT_' . strtoupper($siteName)][0];
                        $savedFormats = $wordpressUtils->getSavedFormats( $siteName );
                        $savedFormat = $format;

                        if( isset( $savedFormats[$format] ) ){
                            $savedFormat = $savedFormats[$format];
                        }

                        $postContent['format'] = $savedFormat;
					}

					if( isset( $publishFormOBJS['C_WORDPRESS_VISIBILITY'] )){
						$postContent['visibility'] = $publishFormOBJS['C_WORDPRESS_VISIBILITY'][0];
					}

					if( isset( $publishFormOBJS['C_WORDPRESS_STICKY'] )){
						$postContent['sticky'] = $publishFormOBJS['C_WORDPRESS_STICKY'][0];
					}

					if( isset( $publishFormOBJS['C_WORDPRESS_ALLOW_COMMENTS'] )){
						$postContent['allowComments'] = $publishFormOBJS['C_WORDPRESS_ALLOW_COMMENTS'][0];
					}

					if( isset( $publishFormOBJS['C_WORDPRESS_PUBLISH_DATE'] )){
						$postContent['publishDate'] = $publishFormOBJS['C_WORDPRESS_PUBLISH_DATE'][0];
					}

					if( isset( $publishFormOBJS['C_WORDPRESS_EXCERPT'] )){
						$postContent['excerpt'] = $publishFormOBJS['C_WORDPRESS_EXCERPT'][0];
					}

					if( isset( $publishFormOBJS['C_WORDPRESS_SLUG'] )){
						$postContent['slug'] = $publishFormOBJS['C_WORDPRESS_SLUG'][0];
					}

					if( isset( $publishFormOBJS['C_WORDPRESS_TAGS_' . strtoupper($siteName)] )){
						$tags = $publishFormOBJS['C_WORDPRESS_TAGS_' . strtoupper($siteName)];
						$preparedTags = null;
						$count = 0;

						foreach( $tags as $tag ){
							$count++;
							$preparedTags .= $tag;
							if( $count < count($tags) ){
								$preparedTags .= ', ';
							}
						}

						$postContent['tags'] = $preparedTags;
					}

					$result = null;
					$standardUrl = null;

					switch( strtolower( $action )){
						case 'publish':
							try{
								$postId = $objXMLRPClientWordPress->uploadPost( $postContent );
								$dossier->ExternalId = $postId;
							} catch ( Exception $e ) {
								$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
							}
							break;

						case 'update':
							try{
								$objXMLRPClientWordPress->uploadPost( $postContent, $dossier->ExternalId );
							} catch ( Exception $e ) {
								$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
							}
							break;

						case 'preview':
							try{
								$result = $objXMLRPClientWordPress->uploadPost( $postContent, null, true );
							} catch ( Exception $e ) {
								throw new BizException( 'WORDPRESS_ERROR_PREVIEW', 'SERVER', 'ERROR', null, array( $publishForm->MetaData->BasicMetaData->Name ));
							}
							break;
					}

					if( strtolower( $action ) == 'preview' ){
						if( $result ) {
							$standardUrl = $result;
						}
					}else{
						$standardUrl = $this->getPostUrl( $dossier->ExternalId, $publishTarget );
					}

					if( $standardUrl ){
						$pubField = new PubField( 'URL', 'string', array( $standardUrl ));
					}
					break;
			}
		}
		return $pubField;
	}

	/**
	 * Get supported Image Formats
	 *
	 * This function returns the supported WordPress Image Formats.
	 *
	 * @return array
	 */
	private function getSupportedImageFormats()
	{
		$retVal = array();

		$retVal[] = 'image/jpg';
		$retVal[] = 'image/jpeg';
		$retVal[] = 'image/png';
		$retVal[] = 'image/gif';

		return $retVal;
	}

	/**
	 * Get gallery by widget name.
	 * Get the gallery id if the gallery for the publish form already exists in WordPress.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param int $externalId
	 * @param string $widgetName
	 * @return int
	 */
	private function getGalleryByWidgetName( $publishTarget, $externalId, $widgetName )
	{
		require_once 'Zend/Json.php';
		static $galleryField;
		$clientWordPress = new WordPressXmlRpcClient( $publishTarget );

		if( !$galleryField ){
			$galleryField = $clientWordPress->getGalleriesFromCustomField( $externalId );
		}

		if( $galleryField ){
			$galleries = $galleryField['galleries'];
			$customFieldId = $galleryField['customFieldId'];
			$galleryId = null;

			if( is_array( $galleries ) ){
				$galleries = reset( $galleries );
				$galleries = Zend_Json::decode( $galleries );

				// Get the gallery id if the gallery already exists.
				$galleryId = null;
				if( isset( $galleries[$widgetName] )){
					$galleryId = $galleries[$widgetName];
				}
			}
			return array( 'galleryId' => $galleryId, 'customFieldId' => $customFieldId );
		}else{
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
	 *
	 * @return int $savedUserId
	 */
	public function getWordPressUserIdForCurrentUser( $siteName )
	{
		$savedUserId = null;
		$wordpressUtils = new WordPress_Utils();
		$wordpressUsers = $wordpressUtils->getSavedUsers( $siteName );
		$currentUser = BizSession::getUser();

		if( $wordpressUsers ) {
			if( isset( $wordpressUsers[strtolower( $currentUser->UserID )])) {
				$savedUserId = $wordpressUsers[strtolower( $currentUser->UserID )];
			}else if( isset ($wordpressUsers[strtolower( $currentUser->FullName )])) {
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
	public function reThrowDetailedError($publishForm, $e, $action )
	{
		$errorCode = ($e->getCode()) ? ' (code: ' . $e->getCode() . ')' : '';
		$actionMessage = $action == 'PublishDossier' ? 'Posting' : 'Unpublishing';

		$reasonParams = array( $publishForm->MetaData->BasicMetaData->Name );
		if($action == 'PublishDossier' || $action == 'Posting'){
			$msg = BizResources::localize( 'WORDPRESS_ERROR_PUBLISH', true, $reasonParams );
		} else{ $msg = BizResources::localize( 'WORDPRESS_ERROR_UNPUBLISH', true, $reasonParams );}

		$detail = $actionMessage . $publishForm->MetaData->BasicMetaData->Name . ' with id: ' . $publishForm->MetaData->BasicMetaData->ID
			. ' produced an error: ' . $e->getMessage() . $errorCode;

		throw new BizException(null, 'Server', $detail, $msg);
	}

	/**
	 * Updates/republishes a published dossier with contained objects (articles. images, etc.) to an 
	 * external publishing system, using the $publishForm->ExternalId to identify the dosier to the
	 * publishing system. The plugin is supposed to update/republish the dossier and it's articles 
	 * and fill in some fields for reference.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return array|void of PubField containing information from publishing system
	 *
	 * @throws BizException
	 */
	public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		$pubField = $this->preparePost($objectsInDossier, $dossier, $publishTarget, 'update');
		return array($pubField);
	}

	/**
	 * Removes/unpublishes a published dossier from an external publishing system
	 * using the $publishForm->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return array of PubField containing information from publishing system
	 */
	public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget ) 
	{
		$objectsInDossier = $objectsInDossier; //Keep analyzer happy
		$objXMLRPClientWordPress = new WordPressXmlRpcClient( $publishTarget );

		try{
			$objXMLRPClientWordPress->deleteOldPublishedPreviews(); // delete all old previews
		} catch( Exception $e ){ // we want to continue when we could not remove the previews
			LogHandler::Log( 'SERVER', 'WARN', 'Could not remove old published reviews', $e );
		}

		try {
			$objXMLRPClientWordPress->deletePostAndContent( $dossier->ExternalId );
			$dossier->ExternalId = '';
		} catch (Exception $e) {
			$this->reThrowDetailedError($dossier, $e, 'UnpublishDossier' );
		}
		return array();
	}

    /**
     * Get the Url for a certain post.
     *
     * @param $externalId int The id of the post, used to get the Url.
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
	 * Requests field values from an external publishing system
	 * using the $dossier->ExternalId to identify the Dossier to the publishing system.
	 *
	 * @param Object $dossier The Dossier to request field values from.
	 * @param array $objectsInDossier The objects in the Dossier.
	 * @param PubPublishTarget $publishTarget The target.
	 * @return array List of PubField with its values.
	 */
	public function requestPublishFields($dossier, $objectsInDossier, $publishTarget)
	{
		$objectsInDossier = $objectsInDossier; //Keep analyzer happy
		$url = null;

		if( $dossier->ExternalId ){
            $url = $this->getPostUrl( $dossier->ExternalId, $publishTarget );
		}

		$pubField = new PubField('URL', 'string', array($url));
		return array($pubField);
	}

	/**
	 * Upload al images in the dossier to WordPress.
	 *
	 * This function is used to upload images, it processes the images and checks if they need to be uploaded or only updated.
	 * It calls the upload image or update image for every image that is in the dossier.
	 *
	 * @param $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @param $galleryId
	 * @param $galleryName
	 * @param null $attachments
	 * @param bool $images
	 * @param bool $preview
	 *
	 * @return array
	 *
	 * @throws BizException
	 */
	function uploadImages( &$objectsInDossier, $publishTarget, $galleryId = null, $galleryName = null, $attachments = null, $images = false, $preview = false )
	{
		$postId = null;
		$publishForm = null;
		$result = null;
		$clientWordPress = new WordPressXmlRpcClient( $publishTarget );

		foreach ($objectsInDossier as $objectInDossier) {
			if ($objectInDossier->MetaData->BasicMetaData->Type == 'PublishForm') {
				$publishForm = $objectInDossier;
				break;
			}
		}

		$publishFormOBJS = BizPublishForm::getFormFields($publishForm);

		// Check if we have a galleryName or an galleryId when there are images to upload,
		// this exception is given when the function is called with the wrong parameters
		if( $images && !$galleryName && !$galleryId ){
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', 'Please contact your system administrator');
		}

		if( $images ){
			//If no GalleryId then create a new Gallery.
			if( !$galleryId ){
				$galleryId = $clientWordPress->createGallery( $galleryName );
			}

			// This is needed because the getFormFields does not return a array when it has only 1 object
			// in that case it will return a object instead of a array. We need a array so here we force it into an array.
			if( isset($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']) ){
				if( !is_array($publishFormOBJS['C_WORDPRESS_MULTI_IMAGES']) ){
					$replaceObj[] = $publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'];
					$publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] = $replaceObj;
				}
			}

			if( $galleryId ){
				foreach($objectsInDossier as $object){
					foreach( $publishFormOBJS['C_WORDPRESS_MULTI_IMAGES'] as $publishedImage ){
						if( $publishedImage->MetaData->BasicMetaData->ID == $object->MetaData->BasicMetaData->ID ){
							if($object->MetaData->BasicMetaData->Type == 'Image'){
								$imageDescription = null;
								$externalId = null;

								foreach ($object->MetaData->ExtraMetaData as $extraData) {
									if( $extraData->Property == 'C_WORDPRESS_IMAGE_DESCRIPTION' ){
										$imageDescription = $extraData->Values[0];
										break;
									}
								}

								$uploadNeeded = false;
								if( !$preview ){ // Don't get the externalId when previewing because we want to upload a new set of images.
									// This part gets the externalId if exists and compares the version of the published version and the workflow version.
									foreach($object->Relations as $relation){
										if($relation->Type == 'Placed' && $relation->Parent == $publishForm->MetaData->BasicMetaData->ID){
											if( isset($relation->Targets) ){
												foreach($relation->Targets as $target){
													if( $target->Issue->Id == $publishTarget->IssueID ){
														require_once BASEDIR.'/server/utils/VersionUtils.class.php';
														$externalId = $relation->Targets[0]->ExternalId;

														if( $externalId ){ // if there is no externalId the version does not have to compared
															if( VersionUtils::versionCompare( $target->PublishedVersion, $object->MetaData->WorkflowMetaData->Version, '<' )) {
																$uploadNeeded = true; // Modification has been done since published, so re-upload is needed.
															}
														}
														break 2; // Stop this foreach and the relation foreach.
													}
												}
											}
										}
									}
								}

								if( !$externalId || ( $externalId && $uploadNeeded ) ){ // check if any change is made else do nothing
									$mediaId = $object->MetaData->BasicMetaData->ID;
									$filePath = $this->saveLocal($mediaId);
									$extension = $object->Relations[0]->ChildInfo->Format;
									$imageName = $object->MetaData->BasicMetaData->Name . '.' . pathinfo($filePath, PATHINFO_EXTENSION);

									// If there is a externalId the image needs to be updated else it's a new image.
									if( $externalId ){
										try{
											$response = $clientWordPress->updateImage( $imageName, $filePath, $extension, $galleryId, $externalId );
										}catch( BizException $e ){
											throw new BizException( 'WORDPRESS_ERROR_UPDATE_IMAGE', 'SERVER', $e->getDetail());
										}
									} else{
										try{
											$response = $clientWordPress->uploadImage( $imageName, $filePath, $extension, $galleryId );
										}catch( BizException $e ){
											throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'SERVER', $e->getDetail());
										}
									}

									$postId = $response['pid'];
									$object->ExternalId = $postId;
								}

								if( $object->ExternalId ){
									$clientWordPress->updateImageMetaData( $object->ExternalId, $object->MetaData->BasicMetaData->Name, $imageDescription );
								}

								break;
							}
						}
					}
				}
				$result['galleryId'] = $galleryId;
			}
		}

		if( $attachments ){
			$inlineImages = array_keys($attachments);

			foreach($inlineImages as $imageId){
				require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
				$extension = $attachments[$imageId]->Type;
				$extension = explode('/', $extension);
				$extension = '.' . $extension[1];
				$inlineImageName = null;
				$inlineImageName = DBObject::getObjectName( $imageId );

				if( !$inlineImageName ){ // objectName not found in Workflow area
					$inlineImageName = DBDeletedObject::getObjectName( $imageId ); // so find in Trash area
				}
				try{
					$retVal = $clientWordPress->uploadMediaLibraryImage( $inlineImageName . $extension, $attachments[$imageId]->FilePath, $attachments[$imageId]->Type );
				}catch( BizException $e ){
					throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'SERVER', $e->getDetail());
				}

				$result['attachments'][$imageId] = $retVal;
				$result['attachments'][$imageId]['ent_id'] = $imageId;
				$result['inline-ids-for-saving'][$imageId] = $retVal['id'];
			}
		}

        if( isset($publishFormOBJS['C_WORDPRESS_FEATURED_IMAGE']) ){
            $featuredImage = $publishFormOBJS['C_WORDPRESS_FEATURED_IMAGE'];

            try{
                $mediaId = $featuredImage->MetaData->BasicMetaData->ID;
                $filePath = $this->saveLocal($mediaId);
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $imageName = $featuredImage->MetaData->BasicMetaData->Name . '.' . $extension ;

                $retVal = $clientWordPress->uploadMediaLibraryImage( $imageName, $filePath, $featuredImage->MetaData->ContentMetaData->Format );
				if( $retVal['id'] ) {
					foreach( $featuredImage->MetaData->ExtraMetaData as $extraData) {
						if( $extraData->Property == 'C_WORDPRESS_IMAGE_DESCRIPTION' ){
							$imageDescription = $extraData->Values[0];
							break;
						}
					}
					$clientWordPress->updateMediaLibraryImageMetaData( $retVal['id'], $featuredImage->MetaData->BasicMetaData->Name, $imageDescription, '' );
				}
            }catch( BizException $e ){
                throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'SERVER', $e->getDetail());
            }

            $result['featured-image'] = $retVal['id'];
        }

		return $result;
	}

	/**
	 * Requests dossier URL from an external publishing system
	 * using the $publishForm->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array $objectsInDossier Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return string URL to published item
	 */
	public function getDossierURL( $dossier, $objectsInDossier, $publishTarget )
	{
		$objectsInDossier = $objectsInDossier; //Keep analyzer happy
		$publishTarget = $publishTarget;
		$dossier = $dossier;
	}

	/**
	 * Previews a dossier with contained objects (articles. images, etc.) to an external publishing 
	 * system. The plugin is supposed to send the dossier and it's articles to the publishing system 
	 * and fill in the URL field for reference.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 * 
	 * @return array of Fields containing information from Publishing system
	 */
	public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		$pubField = $this->preparePost( $objectsInDossier, $dossier, $publishTarget, 'preview' );
		return array( $pubField );
	}

	/**
	 * The function tells whether the plugin ( Channel specific ) supports Publish Form feature.
	 *
	 * @since 9.0
	 * @return boolean True when the channel plugin supports PublishForm feature; False(Default) otherwise.
	 */
	public function doesSupportPublishForms() 
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
	public function getPublishFormTemplates($pubChannelId)
	{
		// Create the templates.
		$templatesObj = array();
		$documentIdPrefix = $this->getDocumentIdPrefix();
		require_once BASEDIR . '/server/utils/PublishingUtils.class.php';

		$wordpressUtils = new WordPress_Utils();
		$publishTarget = new PubPublishTarget( $pubChannelId );
		$siteName = $wordpressUtils->getSiteName( $publishTarget );

		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId, 'WordPress post', 'Publish a text article to WordPress', $documentIdPrefix . $siteName
		);

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
		require_once BASEDIR . '/server/utils/PublishingUtils.class.php';
		require_once dirname(__FILE__). '/WordPress_CustomObjectMetaData.class.php';

		$dialog = WW_Utils_PublishingUtils::getDefaultPublishingDialog($publishFormTemplate->MetaData->BasicMetaData->Name);
		$documentIdPrefix = $this->getDocumentIdPrefix();

		$customObjectMetaData = new WordPress_CustomObjectMetaData();
		$wordpressUtils = new WordPress_Utils();
		$target = reset($publishFormTemplate->Targets);
		$publishTarget = new PubPublishTarget( $target->PubChannel->Id );
		$siteName = $wordpressUtils->getSiteName( $publishTarget );
		$clientWordPress = new WordPressXmlRpcClient( $publishTarget );

		// Create / Add widgets.
		switch ($publishFormTemplate->MetaData->BasicMetaData->DocumentID) {
			case $documentIdPrefix . $siteName : //WordPress post

				//  **Post Tab**
				$tabPost = WW_Utils_PublishingUtils::getPublishingTab( 'Publish Settings' );

				//Title widget
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

				//Date widget
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

				//Visibility widget
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

				//Message widget
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

                //Media widget
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

				if( $clientWordPress->checkNextGenEnabled() ){ // check if the NextGen plug-in is installed else don't show the gallery tab
					// **Gallery tab**
					$tabGallery = WW_Utils_PublishingUtils::getPublishingTab( 'Gallery' );

					//Media widget
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

				//Slug widget
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

				//Allow comments widget
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

				//Sticky widget
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

				//Sticky widget
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
	 * These widgets are used to show the info of a picture in content station.
	 *
	 * @return array
	 */
	private function getFileWidgets()
	{
		require_once dirname(__FILE__). '/WordPress_CustomObjectMetaData.class.php';
		$customObjectMetaData = new WordPress_CustomObjectMetaData();
		$standardProperties = BizProperty::getPropertyInfos();
		$fileWidgets = array();

		//Description
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
	 * @param $extraMetaDatas List of ExtraMetaData elements
	 * @param $widgets List of DialogWidget elements
	 * @return array List of MetaDataValue elements
	 */
	public function extractMetaDataFromWidgets($extraMetaDatas, $widgets)
	{
		$metaDataValues = array();
		if ($widgets) {
			foreach ($widgets as $widget) {
				if ($extraMetaDatas)
					foreach ($extraMetaDatas as $extraMetaData) {
						if ($widget->PropertyInfo->Name == $extraMetaData->Property) {
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
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

		$object = $this->getObject($id);
		$type = $object->MetaData->BasicMetaData->Type;

		// We need to get a unique name for the object, in case there are multiple objects with the same name.
		$name = $id . $type;

		// Get the format, and the proper extension for the format.
		$format = $object->MetaData->ContentMetaData->Format;
		$extension = MimeTypeHandler::mimeType2FileExt($format);

		// Retrieve file data.
		$filePath = null;
		if (isset($object->Files[0]->FilePath)) {
			$filePath = $object->Files[0]->FilePath;
		}

		$exportName = TEMPDIRECTORY . '/' . $name . $extension;

		// Check and create the directory as needed.
		if (!is_dir(TEMPDIRECTORY)) {
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir(TEMPDIRECTORY);
		}

		// Check if the directory was created.
		if (!is_dir(TEMPDIRECTORY)) {
			$msg = 'The directory: "' . TEMPDIRECTORY . '" does not exist, or could not be created.';
			$detail = 'The directory "' . TEMPDIRECTORY . '" could not be created. Either create it manually and '
				. 'ensure the web server can write to it, or check that the webserver has rights to write to the sub-'
				. 'directories in the path.';

			throw new BizException(null, 'Server', $detail, $msg);
		}

		$content = file_get_contents($filePath);

		if (is_null($content)) {
			$msg = 'Could not retrieve file contents';
			$detail = (is_string($filePath)) ? $filePath : 'Attachment (' . $id . ')';
			$detail .= ' did not yield proper content.';
			throw new BizException(null, 'Server', $detail, $msg);
		}

		file_put_contents( $exportName, $content );

		// Check if the file was created.
		if (!file_exists($exportName)) {
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
	private function getObject($id, $lock = false, $rendition = 'native', $requestInfo = array())
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$service = new WflGetObjectsService();

		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
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

		if ( $resp->Objects ) {
			return $resp->Objects[0];
		}
		return null;
	}
}