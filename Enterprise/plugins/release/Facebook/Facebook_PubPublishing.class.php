<?php
/**
 * Class for publishing a Dossier to Facebook.
 *
 * @package 	Enterprise
 * @subpackage	 ServerPlugins
 * @since		 v7.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This plug-in is provided "as is" without warranty of WoodWing Software.
 */
require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/FacebookPublisher.class.php';

class Facebook_PubPublishing extends PubPublishing_EnterpriseConnector
{
	// A plugin should provide a unique site ID.
	const DOCUMENT_PREFIX = 'Facebook';
	const SITE_ID = '0';

	/**
	 * Returns the priority for this EnterpriseConnector.
	 *
	 * @return mixed The priority.
	 */
	final public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}

	/**
	 * Publish dossier to Facebook
	 *
	 * Here the images and/or text ar posted to Facebook, there i looked at which Publish Form is
	 * used in content station and after that processed an uploaded to Facebook.
	 *
	 * @param Object $dossier [writable] The Dossier to be published. ExternalId to be filled in by this function.
	 * @param array $objectsInDossier [writable] The objects in the Dossier to be published.
	 * @param PublishTarget $publishTarget The target for publishing to.
	 * @return array A list with PubField|Empty array when no PublishForm found in the Dossier.
	 * @throws BizException
	 */
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		require_once dirname( __FILE__ ).'/simple_html_dom.php'; // Html to text converter
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		$pubChannelId = $publishTarget->PubChannelID;
		$facebookPublisher = new FacebookPublisher( $pubChannelId );

		$pageId = $facebookPublisher->pageId;

		$publishForm = null;
		$messageText = null;

		//Get the Publish Form
		foreach( $objectsInDossier as $objectInDossier ) {
			if( $objectInDossier->MetaData->BasicMetaData->Type == 'PublishForm' ) {
				$publishForm = $objectInDossier;
				break;
			}
		}

		// Process the publish Form
		if( !is_null( $publishForm ) ) {

			//Extract the message
			//Publish to Facebook
			switch( BizPublishForm::getDocumentId( $publishForm ) ) {
				//Facebook post
				case $this->getDocumentIdPrefix().'0' :
					$fields = BizPublishForm::getFormFields( $publishForm );
					$element = BizPublishForm::extractFormFieldDataFromFieldValue( 'C_FACEBOOK_PF_MESSAGE_SEL', $fields['C_FACEBOOK_PF_MESSAGE_SEL'] );
					if( $element && isset( $element[0] ) && isset( $element[0]['elements'] ) && isset( $element[0]['elements'][0] ) ) {
						$messageText = str_get_html( $element[0]['elements'][0]->Content )->plaintext;
					}

					//Get the URL
					$url = null;
					$urlValue = BizPublishForm::extractFormFieldDataByName( $publishForm, 'C_FACEBOOK_PF_HYPERLINK_URL', false );
					if( $urlValue && $urlValue['C_FACEBOOK_PF_HYPERLINK_URL'] && $urlValue['C_FACEBOOK_PF_HYPERLINK_URL'][0] ) {
						$url = $urlValue['C_FACEBOOK_PF_HYPERLINK_URL'][0];
					}

					//Check if we have a message or hyperlink
					if( !trim( $messageText ) && !$url ) {
						throw new BizException( 'FACEBOOK_ERROR_ADD_MESSAGE', 'Server', '' );
					}

					// Needed for Facebook when you just want to post a URL
					if( !$messageText ) {
						$messageText = ' ';
					}

					try {
						$postId = $facebookPublisher->postMessageToPageFeed( $pageId, $messageText, $url );
						$dossier->ExternalId = $postId;
					} catch( Exception $e ) {
						if( strpos( $e->getMessage(), '#100' ) || strpos( $e->getMessage(), '#1500' ) ) { // Facebook errors when they think the url is not proper formatted.
							throw new BizException( 'FACEBOOK_ERROR_INVALID_URL', 'Server', 'Hyperlink error' );
						}
						$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
					}
					break;

				//Facebook photo
				case $this->getDocumentIdPrefix().'1' :
					$objectType = null;

					//Get the id and type of the placed image
					$mediaId = $this->getPlacedObjectId( $publishForm, 'C_FACEBOOK_PF_MEDIA_SEL' );

					if( !is_null( $mediaId ) ) {
						$objectType = BizObject::getObjectType( $mediaId, 'Workflow' );
					}

					if( $objectType != 'Image' ) {
						throw new BizException( 'FACEBOOK_ERROR_ADD_IMAGE', 'Server', '' );
					}

					//Save the image to a file
					$filePath = $this->saveLocal( $mediaId );

					// Post the Image.
					try {
						if( $objectType == 'Image' ) {
							$imageDescription = null;
							foreach( $objectsInDossier as $objectInDossier ) {
								if( $objectInDossier->MetaData->BasicMetaData->Type == 'Image' ) {
									$imageObj = $objectInDossier;

									if( is_array( $imageObj->MetaData->ExtraMetaData ) ) {
										foreach( $imageObj->MetaData->ExtraMetaData as $extraData ) {
											if( $extraData->Property == 'C_FACEBOOK_IMAGE_DESCRIPTION' ) {
												$imageDescription = $extraData->Values[0];
											}
										}
									}

									$result = $facebookPublisher->uploadPictureToPage( $pageId, $filePath, $imageDescription );
									$postId = $result['id'];
									$dossier->ExternalId = $postId;
								}
							}
						}
					} catch( Exception $e ) {
						unlink( $filePath );
						$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
					}
					//Remove the temp image
					unlink( $filePath );
					break;

				case $this->getDocumentIdPrefix().'2' :
					$imageCheck = false;
					$publishFormOBJS = BizPublishForm::getFormFields( $publishForm );

					// This is needed because the getFormField returns an object when there is only 1 object else it returns an array
					if( isset( $publishFormOBJS['C_FACEBOOK_MULTI_IMAGES'] ) ) {
						if( count( $publishFormOBJS['C_FACEBOOK_MULTI_IMAGES'] ) > 1 ) {
							foreach( $publishFormOBJS['C_FACEBOOK_MULTI_IMAGES'] as $image ) {
								$this->uploadImageToAlbum( $objectsInDossier, $publishForm, $dossier, $publishTarget, $image, $pageId );
								if( !$imageCheck ) {
									$imageCheck = true;
								}
							}
						} else if( count( $publishFormOBJS['C_FACEBOOK_MULTI_IMAGES'] ) == 1 ) {
							$this->uploadImageToAlbum( $objectsInDossier, $publishForm, $dossier, $publishTarget, $publishFormOBJS['C_FACEBOOK_MULTI_IMAGES'], $pageId );
							$imageCheck = true;
						}
					}

					if( !$imageCheck ) {
						throw new BizException( 'FACEBOOK_ERROR_ADD_IMAGE', 'Server', '' );
					}
			}
		}
		return $this->requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
	}

	/**
	 * Upload to Facebook album
	 *
	 * This function is needed because of the getFromFields function that returns a array of multiple objects or returns just 1 object.
	 *
	 * @param objectsInDossier
	 * @param $publishForm
	 * @param $publishTarget
	 * @param $dossier
	 * @param object $image
	 * @param int $pageId
	 */
	public function uploadImageToAlbum( &$objectsInDossier, &$publishForm, &$dossier, $publishTarget, $image, $pageId )
	{
		$pubChannelId = $publishTarget->PubChannelID;
		$facebookPublisher = new FacebookPublisher( $pubChannelId );
		$albumNameField = BizPublishForm::extractFormFieldDataByName( $publishForm, 'C_FACEBOOK_ALBUM_NAME', false );
		$albumDescriptionField = BizPublishForm::extractFormFieldDataByName( $publishForm, 'C_FACEBOOK_ALBUM', false );
		$albumName = $albumNameField['C_FACEBOOK_ALBUM_NAME'][0];
		$albumDescription = $albumDescriptionField['C_FACEBOOK_ALBUM'][0];
		$albumId = null;

		foreach( $objectsInDossier as $imageObj ) {
			if( $imageObj->MetaData->BasicMetaData->Type == 'Image' ) {
				if( $imageObj->MetaData->BasicMetaData->ID == $image->MetaData->BasicMetaData->ID ) {
					$imageDescription = '';

					if( is_array( $imageObj->MetaData->ExtraMetaData ) ) {
						foreach( $imageObj->MetaData->ExtraMetaData as $extraData ) {
							if( $extraData->Property == 'C_FACEBOOK_IMAGE_DESCRIPTION' ) {
								$imageDescription = $extraData->Values[0];
							}
						}
					}

					if( $dossier->ExternalId ) {
						$albumId = $dossier->ExternalId;
					}

					//Save the image to a file
					$filePath = $this->saveLocal( $imageObj->MetaData->BasicMetaData->ID );

					// Post the slideshow.
					try {
						$result = $facebookPublisher->uploadPictureToPage( $pageId, $filePath, $imageDescription, $albumName, $albumDescription, $albumId );
						$postId = $result['id'];
						$imageObj->ExternalId = $postId;

						if( isset( $result['albumId'] ) ) {
							$dossier->ExternalId = $result['albumId'];
						}

					} catch( Exception $e ) {
						unlink( $filePath );
						$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
					}
					//Remove the temp image
					unlink( $filePath );
				}
			}
		}
	}

	/**
	 * Re-throw error resulted from Publishing/Unpublishing the PublishForm to Facebook.
	 *
	 * Error collected while publishing/unpublishing to Facebook is refined with further details,
	 * this detailed error is re-thrown.
	 *
	 * @param Object $publishForm
	 * @param Exception $e
	 * @param string $action Whether the error is from Publishing or Unpublishing. Possible values: 'PublishDossier', 'UnpublishDossier'
	 * @throws BizException
	 */
	public function reThrowDetailedError( $publishForm, $e, $action )
	{
		$reasonParams = array( $publishForm->MetaData->BasicMetaData->Name );
		$errorCode = ( $e->getCode() ) ? ' (code: '.$e->getCode().')' : '';
		$actionMessage = $action == 'PublishDossier' ? 'Posting' : 'Unpublishing';

		if( $actionMessage == 'PublishDossier' || $actionMessage == 'Posting' ) {
			$msg = BizResources::localize( 'FACEBOOK_ERROR_PUBLISH', true, $reasonParams );
		} else {
			$msg = BizResources::localize( 'FACEBOOK_ERROR_UNPUBLISH', true, $reasonParams );
		}

		$detail = $actionMessage.$publishForm->MetaData->BasicMetaData->Name.' with id: '.$publishForm->MetaData->BasicMetaData->ID
			.' produced an error: '.$e->getMessage().$errorCode;

		throw new BizException( null, 'Server', $detail, $msg );
	}

	/**
	 * Returns the object id of the object placed in the specified field.
	 *
	 * @param Object $publishForm Publish form object
	 * @param string $fieldName Name of the fileselector field
	 * @return null|int The Id of the placed object on $fieldName; Null when not found.
	 */
	public function getPlacedObjectId( $publishForm, $fieldName )
	{
		$placedObjId = null;
		if( is_array( $publishForm->Relations ) ) {
			foreach( $publishForm->Relations as $relation ) {
				if( $relation->Type == 'Placed' ) {
					if( is_array( $relation->Placements ) ) {
						foreach( $relation->Placements as $placement ) {
							$property = $placement->FormWidgetId;
							if( $property == $fieldName ) {
								$placedObjId = $relation->Child;
								break; // Found
							}
						}
					}
				}
			}
		}
		return $placedObjId;
	}

	/**
	 * Updates/republishes a published Dossier.
	 *
	 * Note: Updating a Dossier on Facebook is NOT possible at the moment of this writing.
	 *
	 * @param Object $dossier [writable] The Dossier to be updated on Facebook.
	 * @param array $objectsInDossier [writable] The objects in the Dossier to be updated on Facebook.
	 * @param PublishTarget $publishTarget
	 * @return void
	 * @throws BizException
	 */
	public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		// Updating content on Facebook is not possible, the API does not support updating posts or images. They
		// also do not support a full set of DELETE calls, therefore deleting the old content and uploading it again is
		// not a possibility from within the plug-in. Deleting content mannually is an option, but is outside of the scope
		// of the plug-in.

		$msg = 'Updating a Dossier on Facebook is not possible.';
		$detail = 'Updating is not possible due to restrictions in the Facebook Graph API.';
		throw new BizException( null, 'Server', $detail, $msg );
	}

	/**
	 * Removes/unPublishes a published Dossier.
	 *
	 * Note: UnPublishing a Dossier with an album does not delete the album itself, it only deletes the pictures
	 * in the dossier from Facebook. Facebook API does not support deleting albums.
	 *
	 * @param Object $dossier The Dossier to be unpublished.
	 * @param array $objectsInDossier $objectsInDossier The objects in the Dossier to be unpublished on Facebook.
	 * @param publishTarget $publishTarget The target.
	 * @return array Empty array.
	 */
	public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublishedObjectsHist.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';

		$pubChannelId = $publishTarget->PubChannelID;
		$facebookPublisher = new FacebookPublisher( $pubChannelId );
		$pageId = $facebookPublisher->pageId;
		$publishForm = null;

		//Get the Publish Form
		foreach( $objectsInDossier as $objectInDossier ) {
			if( $objectInDossier->MetaData->BasicMetaData->Type == 'PublishForm' ) {
				$publishForm = $objectInDossier;
				break;
			}
		}

		switch( BizPublishForm::getDocumentId( $publishForm ) ) {
			//Facebook post or Facebook single image
			case $this->getDocumentIdPrefix().'0' :
			/** @noinspection PhpMissingBreakStatementInspection */
			case $this->getDocumentIdPrefix().'1' :
				if( $dossier->ExternalId != 'publishedFacebook' ) { // needed for older publish forms with the externalId on the object instead of on the dossier, then the code of case '2' is needed
					try {
						$facebookPublisher->deleteMessageFromFeed( $pageId, $dossier->ExternalId );
					} catch( Exception $e ) {
						$this->reThrowDetailedError( $publishForm, $e, 'UnpublishDossier' );
					}
					$dossier->ExternalId = '';
					break;
				}

			//Facebook image gallery
			case $this->getDocumentIdPrefix().'2' :
				$dossiersPublished = DBPublishHistory::getPublishHistoryDossier( $dossier->MetaData->BasicMetaData->ID, $pubChannelId, $publishTarget->IssueID, null, true );
				$dossierPublished = reset( $dossiersPublished ); // Get the first dossier.
				$publishedObjects = DBPublishedObjectsHist::getPublishedObjectsHist( $dossierPublished['id'] );
				foreach( $publishedObjects as $relation ) {
					if( $relation['type'] == 'Image' ) {
						$externalId = DBPublishedObjectsHist::getObjectExternalId( $dossier->MetaData->BasicMetaData->ID, $relation['objectid'], $pubChannelId, $publishTarget->IssueID, null, $dossierPublished['id'] );
						if( !empty( $externalId ) ) {
							try {
								$facebookPublisher->deleteMessageFromFeed( $pageId, $externalId );
							} catch( Exception $e ) {
								$this->reThrowDetailedError( $publishForm, $e, 'UnpublishDossier' );
							}
						}
					}
				}
				$dossier->ExternalId = '';
				break;
		}

		// Un-publishing content from Facebook is not possible because the Graph API does not support a full set of
		// DELETE calls in their API, meaning we are unable to delete the full set of published content from Facebook.
		// Some of these calls have been left out of the API by design, others have a very low priority on their bug lists
		// or never got past user wishlists, the likelihood that they will be implemented therefore is doubtful.
		//
		// Delete Video: Not possible through the Facebook Graph API by design, the call can be made, but will return true in
		// either case, and DOES NOT delete the video.
		//
		// Delete Album: Not possible through the Facebook Graph API by design.
		//
		// Delete Photo: Can Be done, there is a function for this in the FacebookPublisher class.
		// Delete Wall post: Can be done, there is a function for this in the FacebookPublisher class.

		return array();
	}

	/**
	 * {@inheritdoc}
	 *
	 * Note: Not possible for Facebook content.
	 */
	public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		// Previewing content is not possible for the Facebook plug-in.
		$msg = 'Previewing a Dossier is not possible on Facebook.';
		$detail = 'Facebook does not support previewing of Dossiers.';
		throw new BizException( null, 'Server', $detail, $msg );
	}

	/**
	 * Requests field values from an external publishing system
	 * using the $dossier->ExternalId to identify the Dossier to the publishing system.
	 *
	 * @param Object $dossier The Dossier to request field values from.
	 * @param array $objectsInDossier The objects in the Dossier.
	 * @param publishTarget $publishTarget The target.
	 * @return array List of PubField with its values.
	 */
	public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';

		$url = null;
		$publishForm = null;
		$pubField = array();

		foreach( $objectsInDossier as $objectInDossier ) {
			if( $objectInDossier->MetaData->BasicMetaData->Type == 'PublishForm' ) {
				$publishForm = $objectInDossier;
				break;
			}
		}

		switch( BizPublishForm::getDocumentId( $publishForm ) ) {
			//Facebook post or Facebook single image
			case $this->getDocumentIdPrefix().'0':
				if( $dossier->ExternalId ) {
					$pieces = explode( '_', $dossier->ExternalId );
					$part1 = $pieces[0];
					$part2 = $pieces[1];
					$url = 'http://www.facebook.com/'.$part1.'/posts/'.$part2;
				}
				break;

			case $this->getDocumentIdPrefix().'1':
				if( $dossier->ExternalId != 'publishedFacebook' ) {
					if( $dossier->ExternalId ) {
						$url = 'https://www.facebook.com/photo.php?fbid='.$dossier->ExternalId;
					}
				} else {
					foreach( $objectsInDossier as $objectInDossier ) {
						if( $objectInDossier->MetaData->BasicMetaData->Type == 'Image' ) {
							if( $objectInDossier->ExternalId ) {
								$url = 'https://www.facebook.com/photo.php?fbid='.$objectInDossier->ExternalId;
								break;
							}
						}
					}
				}
				break;

			case $this->getDocumentIdPrefix().'2':
				if( $dossier->ExternalId ) {
					if( $dossier->ExternalId != 'publishedFacebook' ) {
						$url = 'https://www.facebook.com/media/set/?set=a.'.$dossier->ExternalId;
					} else {
						$url = 'https://www.facebook.com/media/set/?set=a.'.$publishForm->ExternalId;
					}
				}
				break;
		}
		$pubField[] = new PubField( 'URL', 'string', array( $url ) );
		return $pubField;
	}

	/**
	 * Requests Dossier URL from an external publishing system.
	 *
	 * @param Object $dossier The Dossier.
	 * @param array $objectsInDossier The objects in the Dossier.
	 * @param publishTarget $publishTarget The target.
	 *
	 * @return string The Dossier URL.
	 */
	public function getDossierURL( $dossier, $objectsInDossier, $publishTarget )
	{
		$pubChannelId = $publishTarget->PubChannelID;
		$facebookPublisher = new FacebookPublisher( $pubChannelId );

		$facebookPage = 'http://www.facebook.com/pages/-/'.$facebookPublisher->pageId.'?sk=app_'.$facebookPublisher->appId;

		// Show the page where is being published to
		return $facebookPage;
	}

	/**
	 * Retrieves an object from the ObjectsService and stores a local copy to be uploaded.
	 *
	 * File structure from the object can differ.
	 *
	 * @param int $id The id for the object to be stored.
	 * @return string $exportName The name of the exported object.
	 * @throws BizException Throws an exception if something goes wrong.
	 */
	public function saveLocal( $id )
	{
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';

		$object = $this->getObject( $id );
		$type = $object->MetaData->BasicMetaData->Type;

		// We need to get a unique name for the object, in case there are multiple objects with the same name.
		$name = $id.$type;

		// Get the format, and the proper extension for the format.
		$format = $object->MetaData->ContentMetaData->Format;
		$extension = MimeTypeHandler::mimeType2FileExt( $format );

		// Retrieve file data.
		$filePath = null;
		if( isset( $object->Files[0]->FilePath ) ) {
			$filePath = $object->Files[0]->FilePath;
		}

		$exportName = TEMPDIRECTORY.'/'.$name.$extension;

		// Check and create the FACEBOOK_DIRECTORY as needed.
		if( !is_dir( TEMPDIRECTORY ) ) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir( TEMPDIRECTORY );
		}

		// Check if the directory was created.
		if( !is_dir( TEMPDIRECTORY ) ) {
			$msg = 'The Facebook directory: "'.TEMPDIRECTORY.'" does not exist, or could not be created.';
			$detail = 'The directory "'.TEMPDIRECTORY.'" could not be created. Either create it manually and '
				.'ensure the web server can write to it, or check that the webserver has rights to write to the sub-'
				.'directories in the path.';

			throw new BizException( null, 'Server', $detail, $msg );
		}

		if( is_string( $filePath ) ) {
			$content = file_get_contents( $filePath );
		} else {
			$content = $object->Files[0]->Content->options['attachment']['body'];
		}

		if( is_null( $content ) ) {
			$msg = 'Could not retrieve file contents';
			$detail = ( is_string( $filePath ) ) ? $filePath : 'Attachment ('.$id.')';
			$detail .= ' did not yield proper content.';
			throw new BizException( null, 'Server', $detail, $msg );
		}

		$fp = fopen( $exportName, 'w+' );

		if( !$fp ) {
			$msg = 'Saving local file failed.';
			$detail = 'Cannot store file: '.$exportName;
			throw new BizException( null, 'Server', $detail, $msg );
		}

		fputs( $fp, $content );
		fclose( $fp );

		// Check if the file was created.
		if( !file_exists( $exportName ) ) {
			$detail = 'File: '.$exportName.' for '.$type.', object (ID: '.$id.') Does not seem to be a valid file.';
			$msg = 'Unable to save '.$type.' data locally.';
			throw new BizException( null, 'Server', $detail, $msg );
		}

		return $exportName;
	}

	/**
	 * We provide our own icons to show in UI for our Facebook channels.
	 * Icons are provided in our plug-ins folder as read by the core server:
	 *   plugins/Facebook/pubchannelicons
	 *
	 * @since 8.2
	 * @return boolean
	 */
	public function hasPubChannelIcons()
	{
		return true;
	}

	/**
	 * Uses the GetObjectsService to return a business object by its id.
	 *
	 * Retrieves the business object by id, uses the WflGetObjectsService to retrieve
	 * the object.
	 *
	 * @param string $id The id of the object to be retrieved.
	 * @param null $userName The username to use when retrieving the object.
	 * @param bool $lock Whether to lock the object or not.
	 * @param string $rendition Rendition option.
	 * @param array $requestInfo Request specific information by changing the array.
	 * @return null|Object $object The retrieved object.
	 * @see /Enterprise/Enterprise/server/bizclasses/BizObject.class.php
	 */
	private function getObject( $id, $userName = null, $lock = false, $rendition = 'native', $requestInfo = array() )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';

		// Attempt to get the username from the session.
		if( is_null( $userName ) ) {
			$userName = BizSession::getShortUserName();
		}

		// Retrieve the object and return it if present.
		$object = BizObject::getObject( $id, $userName, $lock, $rendition, $requestInfo );
		return ( $object instanceof Object ) ? $object : null;
	}

	/**
	 * Refer to PubPublishing_EnterpriseConnector::doesSupportPublishForms() header.
	 */
	public function doesSupportPublishForms()
	{
		return true; // Supports Publish Forms feature.
	}

	/**
	 * Returns the prefix used in the document id
	 *
	 * @return String The Document Id prefix: "Facebook_<SITE_ID>_"
	 */
	private function getDocumentIdPrefix()
	{
		return self::DOCUMENT_PREFIX.'_'.self::SITE_ID.'_';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPublishFormTemplates( $pubChannelId )
	{
		// Create the templates.
		$templatesObj = array();
		$documentIdPrefix = $this->getDocumentIdPrefix();
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';


		// Facebook message
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId, 'Facebook post', 'Create a Facebook post with an optional hyperlink', $documentIdPrefix.'0'
		);

		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId, 'Facebook photo', 'Publish a photo with a description to Facebook', $documentIdPrefix.'1'
		);

		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId, 'Facebook photo album', 'Publish a photo album to Facebook', $documentIdPrefix.'2'
		);

		return $templatesObj;
	}

	/**
	 * This function can return a dialog that is shown in Content Station. This is used for the Multi Channel Publishing Feature.
	 *
	 * @since 8.2
	 * @param Object $publishFormTemplate
	 * @return Dialog|null Dialog definition|The default connector returns null which indicates it doesn't support the getDialog call.
	 */
	public function getDialogForSetPublishPropertiesAction( $publishFormTemplate )
	{
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';

		$dialog = WW_Utils_PublishingUtils::getDefaultPublishingDialog( $publishFormTemplate->MetaData->BasicMetaData->Name );
		$tab = $dialog->Tabs[0];
		$documentIdPrefix = $this->getDocumentIdPrefix();
		$customObjectMetaData = new Facebook_CustomObjectMetaData();

		// Create / Add widgets.
		switch( $publishFormTemplate->MetaData->BasicMetaData->DocumentID ) {
			case $documentIdPrefix.'0' : //Facebook post
				// Article Component Selector.
				$articleWidget = new DialogWidget();
				$articleWidget->PropertyInfo = $customObjectMetaData->getProperty( 'C_FACEBOOK_PF_MESSAGE' );
				$articleWidget->PropertyUsage = new PropertyUsage( $articleWidget->PropertyInfo->Name, true, false, false, false );
				$widget = new DialogWidget();
				$widget->PropertyInfo = $customObjectMetaData->getProperty( 'C_FACEBOOK_PF_MESSAGE_SEL' );
				$widget->PropertyInfo->Widgets = array( $articleWidget );
				$widget->PropertyUsage = new PropertyUsage( $widget->PropertyInfo->Name, true, false, false, false, 150 );
				$tab->Widgets[] = $widget;

				//Hyperlink widget
				$urlWidget = new DialogWidget();
				$urlWidget->PropertyInfo = $customObjectMetaData->getProperty( 'C_FACEBOOK_PF_HYPERLINK_URL' );
				$urlWidget->PropertyUsage = new PropertyUsage( $urlWidget->PropertyInfo->Name, true, false, false, false );
				$tab->Widgets[] = $urlWidget;
				break;

			case $documentIdPrefix.'1' : //Facebook photo
				//Media widget
				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = $customObjectMetaData->getProperty( 'C_FACEBOOK_PF_MEDIA' );
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->Name = 'C_FACEBOOK_PF_MEDIA';
				$fileWidget->PropertyInfo->Category = null;
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 2000000;
				$fileWidget->PropertyInfo->Widgets = $this->getFileWidgets();
				$fileWidget->PropertyUsage = new PropertyUsage( $fileWidget->PropertyInfo->Name, false, false, false, false );

				$widget = new DialogWidget();
				$widget->PropertyInfo = $customObjectMetaData->getProperty( 'C_FACEBOOK_PF_MEDIA_SEL' );
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->Name = 'C_FACEBOOK_PF_MEDIA_SEL';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->DefaultValue = null;
				$widget->PropertyInfo->Widgets = array( $fileWidget );
				$widget->PropertyUsage = new PropertyUsage( $widget->PropertyInfo->Name, true, true, false, false );
				$tab->Widgets[] = $widget;
				break;

			case $documentIdPrefix.'2' : //Facebook slide show to album
				$descriptionWidget = new DialogWidget();
				$descriptionWidget->PropertyInfo = $customObjectMetaData->getProperty( 'C_FACEBOOK_ALBUM_NAME' );
				$descriptionWidget->PropertyInfo->Type = 'string';
				$descriptionWidget->PropertyInfo->Name = 'C_FACEBOOK_ALBUM_NAME';
				$descriptionWidget->PropertyInfo->Category = null;
				$descriptionWidget->PropertyInfo->DefaultValue = null;
				$descriptionWidget->PropertyUsage = new PropertyUsage();
				$descriptionWidget->PropertyUsage->Name = $descriptionWidget->PropertyInfo->Name;
				$descriptionWidget->PropertyUsage->Editable = true;
				$descriptionWidget->PropertyUsage->Mandatory = true;
				$descriptionWidget->PropertyUsage->Restricted = false;
				$descriptionWidget->PropertyUsage->RefreshOnChange = false;
				$descriptionWidget->PropertyUsage->InitialHeight = 0;
				$tab->Widgets[] = $descriptionWidget;

				$nameWidget = new DialogWidget();
				$nameWidget->PropertyInfo = $customObjectMetaData->getProperty( 'C_FACEBOOK_ALBUM' );
				$nameWidget->PropertyInfo->Type = 'multiline';
				$nameWidget->PropertyInfo->Name = 'C_FACEBOOK_ALBUM';
				$nameWidget->PropertyInfo->Category = null;
				$nameWidget->PropertyInfo->DefaultValue = null;
				$nameWidget->PropertyUsage = new PropertyUsage();
				$nameWidget->PropertyUsage->Name = $nameWidget->PropertyInfo->Name;
				$nameWidget->PropertyUsage->Editable = true;
				$nameWidget->PropertyUsage->Mandatory = false;
				$nameWidget->PropertyUsage->Restricted = false;
				$nameWidget->PropertyUsage->RefreshOnChange = false;
				$nameWidget->PropertyUsage->InitialHeight = 0;
				$tab->Widgets[] = $nameWidget;

				//Media widget
				$fileImagePropertyInfoPropValue1 = new PropertyValue( 'image/png', '.png', 'Format' );
				$fileImagePropertyInfoPropValue2 = new PropertyValue( 'image/jpeg', '.jpg', 'Format' );

				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo( 'C_FACEBOOK_MULTI_IMAGES_FILE' );
				$fileWidget->PropertyInfo->Name = 'C_FACEBOOK_MULTI_IMAGES_FILE';
				$fileWidget->PropertyInfo->DisplayName = 'Selected Image';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 2000000; // (2MB max upload)
				$fileWidget->PropertyInfo->PropertyValues = array( $fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2 );
				$fileWidget->PropertyInfo->MinResolution = '200x200'; // (w x h)
				$fileWidget->PropertyInfo->MaxResolution = '640x480'; // (w x h)
				$fileWidget->PropertyInfo->Widgets = $this->getFileWidgets();
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable = false;
				$fileWidget->PropertyUsage->Mandatory = false;
				$fileWidget->PropertyUsage->Restricted = false;
				$fileWidget->PropertyUsage->RefreshOnChange = false;
				$fileWidget->PropertyUsage->InitialHeight = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo( 'C_FACEBOOK_MULTI_IMAGES' );
				$widget->PropertyInfo->Name = 'C_FACEBOOK_MULTI_IMAGES';
				$widget->PropertyInfo->DisplayName = 'Images';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 0; // Empty allowed.
				$widget->PropertyInfo->MaxValue = null; // Many images allowed.
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array( $fileWidget );
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable = true;
				$widget->PropertyUsage->Mandatory = true;
				$widget->PropertyUsage->Restricted = false;
				$widget->PropertyUsage->RefreshOnChange = false;
				$widget->PropertyUsage->InitialHeight = 0;
				$tab->Widgets[] = $widget;
				break;
		}
		$extraMetaData = null;
		$dialog->MetaData = $this->extractMetaDataFromWidgets( $extraMetaData, $tab->Widgets );

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
		$standardProperties = BizProperty::getPropertyInfos();
		$fileWidgets = array();
		//Description
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = new PropertyInfo();
		$fileWidget->PropertyInfo->Name = 'C_FACEBOOK_IMAGE_DESCRIPTION';
		$fileWidget->PropertyInfo->DisplayName = 'Description';
		$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileWidget->PropertyInfo->Type = 'multiline';
		$fileWidget->PropertyInfo->DefaultValue = null;
		$fileWidget->PropertyInfo->MaxLength = null;
		$fileWidget->PropertyInfo->PropertyValues = null;
		$fileWidget->PropertyInfo->Widgets = null;
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
	 * @param ExtraMetaData[] $extraMetaDatas
	 * @param DialogWidget[] $widgets
	 * @return MetaDataValue[]
	 */
	public function extractMetaDataFromWidgets( $extraMetaDatas, $widgets )
	{
		$metaDataValues = array();
		if( $widgets ) {
			foreach( $widgets as $widget ) {
				if( $extraMetaDatas )
					foreach( $extraMetaDatas as $extraMetaData ) {
						if( $widget->PropertyInfo->Name == $extraMetaData->Property ) {
							$metaDataValue = new MetaDataValue();
							$metaDataValue->Property = $extraMetaData->Property;
							$metaDataValue->Values = $extraMetaData->Values; // Array of string
							$metaDataValues[] = $metaDataValue;
							break; // Found
						}
					}
			}
		}
		return $metaDataValues;
	}

	/**
	 * This function allows the server plug-ins to customize the button bar of the PublishForm dialog.
	 *
	 * A default button bar is given in the $defaultButtonBar parameter. This array consists of 4 dialog
	 * buttons by default. A Publish, UnPublish, Update and Preview button are given. When a button is removed
	 * from the array, the button won't be shown in the client. Alternatively you could set the
	 * Button->PropertyUsage->Editable option to false to disable the button in the UI.
	 *
	 * It's up to UI to decide when to show what buttons. For example: when a dossier is unpublish only
	 * the 'Publish' button is shown and not the 'Update' button.
	 *
	 * @since 9.0
	 * @param array $defaultButtonBar
	 * @param Object $publishFormTemplate
	 * @param Object $publishForm
	 * @return array
	 */
	public function getButtonBarForSetPublishPropertiesAction( $defaultButtonBar, $publishFormTemplate, $publishForm )
	{
		//Remove the update and preview button
		foreach( $defaultButtonBar as $index => $button ) {
			if( in_array( $button->PropertyInfo->Name, array( 'Update', 'Preview' ) ) ) {
				unset( $defaultButtonBar[ $index ] );
			}
		}


		return $defaultButtonBar;
	}

	/**
	 * Ensure that the necessary PubFields are stored as part of the PublishHistory.
	 *
	 * @return string[] An array of string keys of the PubFields to be stored in the PublishHistory.
	 */
	public function getPublishDossierFieldsForDB()
	{
		// Ensure that the URL is stored in the PublishHistory.
		return array( 'URL' );
	}
}