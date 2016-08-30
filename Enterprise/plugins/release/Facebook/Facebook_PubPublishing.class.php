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
	 * {@inheritdoc}
	 */
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		require_once dirname( __FILE__ ).'/simple_html_dom.php'; // Html to text converter
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		$facebookPublisher = new FacebookPublisher( $publishTarget->PubChannelID );
		$pageId = $facebookPublisher->pageId;

		// Bail out if there is no publish form in the dossier (should never happen).
		$publishForm = BizPublishForm::findPublishFormInObjects( $objectsInDossier );
		if( !$publishForm ) {
			return array();
		}

		// Take the objects that are placed on the publish form.
		$publishFormObjects = BizPublishForm::getFormFields( $publishForm );

		// Process the publish Form
		$e = null;
		try {
			switch( BizPublishForm::getDocumentId( $publishForm ) ) {

				// Facebook post
				case $this->getDocumentIdPrefix().'0' :
					// Extract the message
					$messageText = null;
					$element = BizPublishForm::extractFormFieldDataFromFieldValue( 'C_FACEBOOK_PF_MESSAGE_SEL', $publishFormObjects['C_FACEBOOK_PF_MESSAGE_SEL'], $publishTarget->PubChannelID );
					if( isset( $element[0]['elements'][0] ) ) {
						$messageText = str_get_html( $element[0]['elements'][0]->Content )->plaintext;
					}

					// Get the URL
					$url = null;
					$urlValue = BizPublishForm::extractFormFieldDataByName( $publishForm, 'C_FACEBOOK_PF_HYPERLINK_URL', $publishTarget->PubChannelID, false );
					if( $urlValue && $urlValue['C_FACEBOOK_PF_HYPERLINK_URL'] && $urlValue['C_FACEBOOK_PF_HYPERLINK_URL'][0] ) {
						$url = $urlValue['C_FACEBOOK_PF_HYPERLINK_URL'][0];
					}

					// Check if we have a message or hyperlink
					if( !trim( $messageText ) && !$url ) {
						throw new BizException( 'FACEBOOK_ERROR_ADD_MESSAGE', 'Server', '' );
					}

					// Needed for Facebook when you just want to post a URL
					if( !$messageText ) {
						$messageText = ' ';
					}

					// Publish to Facebook
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

				// Facebook photo
				case $this->getDocumentIdPrefix().'1' :
					if( isset( $publishFormObjects['C_FACEBOOK_PF_MEDIA_SEL'] ) ) {
						$imageObjects = $publishFormObjects['C_FACEBOOK_PF_MEDIA_SEL'];
						if( is_object( $imageObjects ) ) {
							// The getFormField returns an object when there is only 1 object else it returns an array.
							$imageObjects = array( $imageObjects );
						}
						if( $imageObjects ) foreach( $imageObjects as $imageObject ) {
							$imageId = $imageObject->MetaData->BasicMetaData->ID;
							$imageAttachment = $this->getCroppedImage( $publishForm, $imageId, 'C_FACEBOOK_PF_MEDIA_SEL' );
							if( !$imageAttachment ) {
								$imageAttachment = $imageObject->Files[0]; // fallback to native image
							}
							if( $imageAttachment ) {
								$imagePath = $imageAttachment->FilePath;
								$imageDescription = $imageDescription = $this->getImageDescription( $imageObject );
								try {
									$dossier->ExternalId = $facebookPublisher->uploadPictureToPage( $pageId, $imagePath, $imageDescription );
								} catch( Exception $e ) {
									$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
								}
							}
						}
					}
					break;

				// Facebook photo album
				case $this->getDocumentIdPrefix().'2' :
					$imageCheck = false;
					if( isset( $publishFormObjects['C_FACEBOOK_MULTI_IMAGES'] ) ) {
						$imageObjects = $publishFormObjects['C_FACEBOOK_MULTI_IMAGES'];
						if( is_object( $imageObjects ) ) {
							// The getFormField returns an object when there is only 1 object else it returns an array.
							$imageObjects = array( $imageObjects );
						}
						if( $imageObjects ) foreach( $imageObjects as $imageObject ) {
							$imageId = $imageObject->MetaData->BasicMetaData->ID;
							$imageAttachment = $this->getCroppedImage( $publishForm, $imageId, 'C_FACEBOOK_MULTI_IMAGES' );
							if( !$imageAttachment ) {
								$imageAttachment = $imageObject->Files[0]; // fallback to native image
							}
							if( $imageAttachment ) {
								$imagePath = $imageAttachment->FilePath;
								$this->uploadImageToAlbum( $publishForm, $dossier, $publishTarget, $imageObject, $imagePath, $pageId );
								$imageCheck = true;
							}
						}
					}
					if( !$imageCheck ) {
						throw new BizException( 'FACEBOOK_ERROR_ADD_IMAGE', 'Server', '' );
					}
					break;
			}
		} catch( BizException $e ) {
		}

		// Remove temp files from transfer server folder as prepared by getFormFields().
		BizPublishForm::cleanupFilesReturnedByGetFormFields( $publishFormObjects );

		// Re-throw publish error caught before.
		if( $e ) {
			throw $e;
		}

		return $this->requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
	}

	/**
	 * Retrieves an image crop made on a given Publish Form.
	 *
	 * The end user may have cropped the image placed on the Publish Form.
	 * When a crop is found, the caller should prefer the cropped image (over the native image).
	 *
	 * The cropped image is dynamically set by the core server during the publish operation at Placement->ImageCropAttachment.
	 * Note that this property is not defined in the WSDL. When the property is missing, there is no crop.
	 *
	 * @since 10.1.0
	 * @param Object $publishForm The form object being published.
	 * @param string $childId Id of placed image object to get the attachment for.
	 * @param integer $formWidgetId The field of the publish form the child object could be placed on.
	 * @return Attachment|null The cropped image. NULL when no crop found.
	 */
	private function getCroppedImage( $publishForm, $childId, $formWidgetId )
	{
		$cropppedImage = null;
		foreach( $publishForm->Relations as $relation ) {
			if( $relation->Type == 'Placed' &&
				$relation->Child == $childId &&
				$relation->Parent == $publishForm->MetaData->BasicMetaData->ID
			) {
				foreach( $relation->Placements as $placement ) {
					if( $placement->FormWidgetId &&
						$placement->FormWidgetId == $formWidgetId &&
						isset( $placement->ImageCropAttachment )
					) {
						$cropppedImage = $placement->ImageCropAttachment;
					}
				}
			}
		}
		return $cropppedImage;
	}

	/**
	 * Upload to Facebook album
	 *
	 * @param Object $publishForm
	 * @param Object $dossier
	 * @param PubPublishTarget $publishTarget
	 * @param Object $imageObject
	 * @param string $imagePath
	 * @param int $pageId
	 */
	private function uploadImageToAlbum( $publishForm, $dossier, $publishTarget, $imageObject, $imagePath, $pageId )
	{
		$pubChannelId = $publishTarget->PubChannelID;
		$facebookPublisher = new FacebookPublisher( $pubChannelId );
		$albumNameField = BizPublishForm::extractFormFieldDataByName( $publishForm, 'C_FACEBOOK_ALBUM_NAME', false, $pubChannelId );
		$albumDescriptionField = BizPublishForm::extractFormFieldDataByName( $publishForm, 'C_FACEBOOK_ALBUM', false, $pubChannelId );
		$albumName = $albumNameField['C_FACEBOOK_ALBUM_NAME'][0];
		$albumDescription = $albumDescriptionField['C_FACEBOOK_ALBUM'][0];
		$albumId = $dossier->ExternalId ? $dossier->ExternalId : null;
		$imageDescription = $this->getImageDescription( $imageObject );

		// Post the slideshow.
		try {
			$imageObject->ExternalId = $facebookPublisher->uploadPictureToPage( $pageId, $imagePath, $imageDescription,
				$albumName, $albumDescription, $albumId );
			if( $albumId ) {
				$dossier->ExternalId = $albumId;
			}
		} catch( Exception $e ) {
			$this->reThrowDetailedError( $publishForm, $e, 'PublishDossier' );
		}
	}

	/**
	 * Looks up the image description in the custom metadata property C_FACEBOOK_IMAGE_DESCRIPTION.
	 *
	 * @param Object $imageObject
	 * @return string
	 */
	private function getImageDescription( $imageObject )
	{
		$imageDescription = '';
		if( $imageObject->MetaData->ExtraMetaData ) {
			foreach( $imageObject->MetaData->ExtraMetaData as $extraData ) {
				if( $extraData->Property == 'C_FACEBOOK_IMAGE_DESCRIPTION' ) {
					$imageDescription = $extraData->Values[0];
				}
			}
		}
		return $imageDescription;
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

		if( $action == 'PublishDossier' ) {
			$msg = BizResources::localize( 'FACEBOOK_ERROR_PUBLISH', true, $reasonParams );
			$actionMessage = 'Publishing';
		} else {
			$msg = BizResources::localize( 'FACEBOOK_ERROR_UNPUBLISH', true, $reasonParams );
			$actionMessage = 'Unpublishing';
		}

		$detail = $actionMessage.' '.$publishForm->MetaData->BasicMetaData->Name.
			' with id '.$publishForm->MetaData->BasicMetaData->ID.
			' produced an error: '.$e->getMessage().$errorCode;

		throw new BizException( null, 'Server', $detail, $msg );
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

		// Bail out if there is no publish form in the dossier (should never happen).
		$publishForm = BizPublishForm::findPublishFormInObjects( $objectsInDossier );
		if( !$publishForm ) {
			return array();
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
	 * @param Object[] $objectsInDossier The objects in the Dossier.
	 * @param PubPublishTarget $publishTarget The target.
	 * @return array List of PubField with its values.
	 */
	public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';

		$url = null;
		$pubField = array();

		// Bail out if there is no publish form in the dossier (should never happen).
		$publishForm = BizPublishForm::findPublishFormInObjects( $objectsInDossier );
		if( !$publishForm ) {
			return array();
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