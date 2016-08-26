<?php

/* * **************************************************************************
  Copyright 2008-2013 WoodWing Software BV

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

  http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
 * ************************************************************************** */

/**
 * Demo publishing connector to post messages to Twitter.
 *
 * @package        Enterprise
 * @subpackage       ServerPlugins
 * @since          v7.6
 * @copyright      WoodWing Software bv. All Rights Reserved.
 *
 * @todo           Refactor the getDossierUrl, requestPublishFields and other functions requiring the same construction of the URL.
 *
 * This plug-in is provided "as is" without warranty from WoodWing Software.
 */
require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
include_once dirname(__FILE__) . '/EnterpriseTwitterConnector.class.php';

class Twitter_PubPublishing extends PubPublishing_EnterpriseConnector
{
    const DOCUMENT_PREFIX = 'Twitter';
    const SHORTENED_URL_LENGTH = 25;

    /**
     * Returns the plugins priority.
     *
     * @return mixed The priority.
     */
    final public function getPrio() {
        return self::PRIO_DEFAULT;
    }

    /**
     * Publishes a Dossier to Twitter.
     *
     * @param writable Object $dossier The Dossier to be Published.
     * @param writable array of Object $objectsInDossier The objects in the Dossier to be published.
     * @param PubPublishTarget $publishTarget The publishing target.
     *
     * @return PubField[] Array of PubField(s) containing information from Twitter.
     * */
    public function publishDossier(&$dossier, &$objectsInDossier, $publishTarget) {
        LogHandler::Log('Twitter', 'INFO', 'Publishing a Dossier to Twitter.');

        // Retrieve the text to be sent to Twitter.
        $text = $this->getTextToSend($objectsInDossier);

        $twitConn = new EnterpriseTwitterConnector();
        $twitter = $twitConn->getTwitter($publishTarget->IssueID);

        // Post the text to Twitter.
        try {
	        $mediaFiles = $this->getMediaToSend($objectsInDossier);
	        $mediaIds = array();

	        if ($mediaFiles) {
		        foreach( $mediaFiles as $mediaFile){
			        $mediaIds[] = $twitter->statusesUploadMedia( $mediaFile );
		        }

		        $mediaIdsString = implode( ',', $mediaIds);
            }

            $response = $twitter->statusesUpdate($text, null, $mediaIdsString);

        } catch (Exception $e) {
            $msg = 'Error sending message to Twitter';
            $detail = 'Error Details: ' . $e->getMessage();
            throw new BizException(null, 'Server', $detail, $msg);
        }
        if ($response->isSuccess()) {
            LogHandler::Log('Twitter', 'INFO', 'Message posted successfully.');

            // Set the Dossier's external id, to be stored in the response.
            $dossier->ExternalId = (string) $response->id_str;
        } else {
			if($response->error){ // this is needed because Twitter is not always giving the same errors
				$errorCode = '0';
				$errorMessage = $response->error;
			} else{
				$error = reset($response->getErrors());
				$errorCode = $error->code;
				$errorMessage = $error->message;
			}

            $detail = 'Received error: ' . $errorCode;
            $msg = "Error sending message to Twitter:\n" . $errorMessage;
            throw new BizException(null, 'Server', $detail, $msg);
        }

        // Return a new PubField containing the URL for the Twitter message.
        $url = 'http://twitter.com/' . $twitConn->getUserName($publishTarget->IssueID) . '/status/'
                . $dossier->ExternalId;

        $pubFields = array();
        $pubFields[] = self::getNewPubField('URL', 'string', array($url));
        $pubFields[] = self::getNewPubField('Id', 'string', array($dossier->ExternalId));
        $pubFields[] = self::getNewPubField('Text', 'string', array($text));

        return $pubFields;
    }

    /**
     * Update Dossier by just resending it.
     *
     * Note: If the messages are identical, then Twitter will return an error, stating that the message
     * is a duplicate. Since nothing is changed. Most likely this scenario will not occur, as Content Station
     * only allows updates on changed content. So long as Content Station does not detect a change in an Article
     * it will not be updated on Twitter.
     *
     * @param writable Object $dossier The Dossier to be updated.
     * @param writable array of Object $objectsInDossier The Objects in the Dossier.
     * @param PublishTarget $publishTarget The publish target.
     *
     * @return PubField[] array of Fields containing information from Twitter.
	 *
	 * @throws BizException
     */
	public function updateDossier(&$dossier, &$objectsInDossier, $publishTarget) {
		$dossier = $dossier;
		$objectsInDossier = $objectsInDossier;
		$publishTarget = $publishTarget;

		// Updating content on Twitter is not possible. this will just upload a new tweet
		$msg = 'Updating a Dossier on twitter is not possible.';
		$detail = 'Updating is not possible in this plugin';
		throw new BizException(null, 'Server', $detail, $msg);
    }

    /**
     * Un-publish a Dossier on Twitter.
     *
     * @param Object $dossier The Dossier to be un-published.
     * @param Object[] $objectsInDossier The Objects in the Dossier.
     * @param PubPublishTarget $publishTarget The target.
     *
     * @return null
     */
    public function unpublishDossier($dossier, $objectsInDossier, $publishTarget) {
        // Keep the analyzer happy.
        $objectsInDossier = $objectsInDossier;
		$dossier = $dossier;

        LogHandler::Log('Twitter', 'INFO', 'Unpublishing a Dossier on Twitter. (' . $dossier->ExternalId . ')');

        $twitConn = new EnterpriseTwitterConnector();
        $twitter = $twitConn->getTwitter($publishTarget->IssueID);

       ///** // Unpublish the message(s) on Twitter.
        try {
            $response = $twitter->statusesDestroy($dossier->ExternalId);
        } catch (Exception $e) {
            $msg = 'Error removing message from Twitter';
            $detail = 'Error Details: ' . $e->getMessage();
            throw new BizException(null, 'Server', $detail, $msg);
        }
        if ($response->isSuccess()) {
            $dossier->ExternalId = -1 * $dossier->ExternalId;
        } else {
            $error = $response->errors[0];
            $detail = 'Received error: ' . $error->code;
            $msg = "Error removing message from Twitter:\n • " . $error->message;
            throw new BizException(null, 'Server', $detail, $msg);
        } //**/
        return array();
    }

    /**
     * Previews the Dossier.
     *
     * @param $dossier The Dossier to be previewed.
     * @param $objectsInDossier The Objects in the Dossier.
     * @param $publishTarget The publish target.
     *
     * @throws BizException Throws an exception, because previewing Twitter cannot be done.
     */
    public function previewDossier(&$dossier, &$objectsInDossier, $publishTarget) {
        // Keep the analyzer happy.
        $dossier = $dossier;
        $objectsInDossier = $objectsInDossier;
        $publishTarget = $publishTarget;

        // Warn the user that previews cannot be displayed.
        $msg = 'Preview of Twitter message(s) is not possible';
        $detail = 'The twitter API does not allow for previews to be shown.';
        throw new BizException(null, 'Client', $detail, $msg);
    }

    /**
     * Request the publish fields.
     *
     * @param Object $dossier The Dossier to get the fields for.
     * @param Object[] array of Object $objectsInDossier The Objects in the Dossier.
     * @param PubPublishTarget $publishTarget The publish target.
     *
     * @return array of Fields containing information from Twitter.
     *
     * @todo Refactor this, could use the getDossierURL() function to get the same result for the URL.
     * */
    public function requestPublishFields($dossier, $objectsInDossier, $publishTarget) {
        // Keep the analyzer happy.
        $objectsInDossier = $objectsInDossier;

        // Set up the Twitter connection.
        $twitConn = new EnterpriseTwitterConnector();

        // Return a new PubField containing the URL.
        $url = 'http://twitter.com/' . $twitConn->getUserName($publishTarget->IssueID) . '/status/' . $dossier->ExternalId;
        $pubField = self::getNewPubField('URL', 'string', array($url));
        return array($pubField);
    }

    /**
     * Get dossier URL.
     *
     * @param Object $dossier The Dossier to get the URL for.
     * @param array of Object $objectsindossier The objects in the Dossier.
     * @param PubPublishTarget $publishtarget The publish target.
     *
     * @return string The URL to the Twitter page.
     */
    public function getDossierURL($dossier, $objectsInDossier, $publishTarget) {
        // Keep the analyzer happy.
        $objectsInDossier = $objectsInDossier;

        $twitConn = new EnterpriseTwitterConnector();
        return "http://twitter.com/" . $twitConn->getUserName($publishTarget->IssueID) . "/status/{$dossier->ExternalId}";
    }

    /**
     * We provide our own icons to show in UI for our Twitter channels.
     * Icons are provided in our plug-ins folder as read by the core server:
     *    plugins/twitter/pubchannelicons
     *
     * @since 8.2
     * @return boolean
     */
    public function hasPubChannelIcons() {
        return true;
    }

    /**
     * Refer to PubPublishing_EnterpriseConnector::doesSupportPublishForms() header.
     */
    public function doesSupportPublishForms() {
        return true; // Supports Publish Forms feature.
    }

    /**
     * Refer to PubPublishing_EnterpriseConnector::getPublishFormTemplates() header.
     */
    public function getPublishFormTemplates($pubChannelId) {
        // Create the templates.
        $templatesObj = array();
        $documentIdPrefix = self::DOCUMENT_PREFIX . '_' . $pubChannelId . '_';
        require_once BASEDIR . '/server/utils/PublishingUtils.class.php';

        // Twitter message
        $templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
                        $pubChannelId, 'Twitter Message', 'Publish a Twitter message.', $documentIdPrefix . '0'
        );

        return $templatesObj;
    }

    /**
     * This function can return a dialog that is shown in Content Station. This is used for the Multi Channel Publishing Feature.
     *
     * @since 9.0
     * @param Object $publishFormTemplate
     * @return Dialog|null Dialog definition|The default connector returns null which indicates it doesn't support the getDialog call.
     */
    public function getDialogForSetPublishPropertiesAction($publishFormTemplate) {
        require_once BASEDIR . '/server/utils/PublishingUtils.class.php';

        $dialog = WW_Utils_PublishingUtils::getDefaultPublishingDialog($publishFormTemplate->MetaData->BasicMetaData->Name, 'Twitter');
        $tab = reset($dialog->Tabs);

        $customObjectMetaData = new Twitter_CustomObjectMetaData();

        $widget = new DialogWidget();
        $widget->PropertyInfo = $customObjectMetaData->getProperty('C_TPF_TWEET');
        $widget->PropertyUsage = new PropertyUsage($widget->PropertyInfo->Name, true, false, false, false);
        $tab->Widgets[] = $widget;

        $widget = new DialogWidget();
        $widget->PropertyInfo = $customObjectMetaData->getProperty('C_TPF_URL');
        $widget->PropertyUsage = new PropertyUsage($widget->PropertyInfo->Name, true, false, false, false);
        $tab->Widgets[] = $widget;

	    //Media widget
	    $fileWidget = new DialogWidget();
	    $fileWidget->PropertyInfo = $customObjectMetaData->getProperty('C_TPF_MEDIA');
	    $fileWidget->PropertyInfo->Widgets = $this->getFileWidgets();
	    $fileWidget->PropertyUsage = new PropertyUsage($fileWidget->PropertyInfo->Name, true, false, false, false);
	    $widget = new DialogWidget();
	    $widget->PropertyInfo = $customObjectMetaData->getProperty('C_TPF_MEDIA_SELECTOR');
	    $widget->PropertyInfo->Widgets = array($fileWidget);
	    $widget->PropertyUsage = new PropertyUsage($widget->PropertyInfo->Name, true, false, false, false);
	    $tab->Widgets[] = $widget;

        $dialog->Tabs[] = $tab;

        return $dialog;
    }

    /**
     * Get text to send to twitter from a Dossier.
     *
     * @param array    $objectsInDossier The Objects in the Dossier for which to retrieve the text.
     *
     * @return string $text The text to send to twitter
     *
     * @throws BizException Throws an exception if the text becomes too long.
     */
    private function getTextToSend($objectsInDossier) {
        $text = '';
        $url = '';

        /**
         * @var Object $child
         */
        foreach ( $objectsInDossier as $child ) {
            if ( $child->MetaData->BasicMetaData->Type == 'PublishForm' ) {
                $extraMetaData = $child->MetaData->ExtraMetaData;
                foreach ( $extraMetaData as $metaData ) {
                    if ( $metaData->Property == 'C_TPF_TWEET' ) {
                        $text = $metaData->Values[0];
                    }

                    if ( $metaData->Property == 'C_TPF_URL' ) {
                        $url = $metaData->Values[0];
                    }
                }
            }
        }

		$maxLengthWithURL = 140 - self::SHORTENED_URL_LENGTH;
		$reasonParams = array( $maxLengthWithURL, strlen( $text ), substr( $text, 0, 140 ));

        // Tweet may only be a maximum of 140 characters long.
        if( strlen( $text ) > 140 ) {
			$msg = BizResources::localize( 'ERRMSG_TWEET_SIZE', true, $reasonParams );
            $detail = BizResources::localize( 'ERRDET_TWEET_SIZE', true, $reasonParams );
            throw new BizException( null, 'Client', $detail, $msg );
        } else if ( $url != '' ) {
			require_once 'Zend/Uri.php';
			require_once dirname(__FILE__) . '/webapps/Twitter_TwitterConfig_EnterpriseWebApp.class.php';

			$checkResponsive = Twitter_TwitterConfig_EnterpriseWebApp::getCheckResponsive();

			if( $checkResponsive == 'checked' ){
				$validUrl = false;

				if( !preg_match( '/^[a-zA-Z]+:\/\/.*/', $url ) && !( substr( strtolower( $url ), 0, 7 ) == 'mailto:' )){
					$url = 'http://' . $url;
				}

				//Check if we have a valid url
				try {
					if ( Zend_Uri::check( $url )){
						$validUrl = true;
					}

					if( !$validUrl ){
						$detail = '';
						throw new BizException( 'ERRMSG_URL_INVALID', 'Client', $detail );
					}
				} catch( Exception $e ) {
					throw new BizException( 'ERRMSG_URL_INVALID', 'INFO',  $e->getMessage());
				}

				$validUrl = WW_Utils_UrlUtils::isResponsiveUrl( $url );
				if( !$validUrl ){
					$detail = '';
					throw new BizException( 'ERRMSG_URL_NOTRESPONSIVE', 'Client', $detail );
				}
			}

            //A tweet with an url  may only be a maximum of 140 - the length of the shortened url long.
            if ( strlen( $text ) > $maxLengthWithURL ) {
                $msg = BizResources::localize( 'ERRMSG_TWEETANDURL_SIZE', true, $reasonParams );
				$detail = BizResources::localize( 'ERRDET_TWEETANDURL_SIZE' );
                throw new BizException( null, 'Client', $detail, $msg );
            }

            $text = $text . ' ' . $url;
        }


        return $text;
    }

	/**
	 * Resolves the images to be published from the publish form of the given dossier.
	 *
	 * @param Object[] $objectsInDossier
	 * @return string[] File paths of images to publish.
	 * @throws BizException
	 */
    private function getMediaToSend( $objectsInDossier )
    {
	    require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
	    $imagePaths = array();
	    $publishForm = null;
	    $publishFormFields = null;
	    foreach( $objectsInDossier as $child ) {
		    if( $child->MetaData->BasicMetaData->Type == 'PublishForm' ) {
			    $publishForm = $child;
			    $publishFormObjects = BizPublishForm::getFormFields( $publishForm );
			    break;
		    }
	    }

	    if( $publishForm && isset( $publishFormObjects['C_TPF_MEDIA_SELECTOR'] ) ) {
		    $imageObjects = $publishFormObjects['C_TPF_MEDIA_SELECTOR'];
		    if( is_object( $imageObjects ) ) {
			    // The getFormField returns an object when there is only 1 object else it returns an array.
			    $imageObjects = array( $imageObjects );
		    }
		    if( $imageObjects ) foreach( $imageObjects as $imageObject ) {
			    $imageId = $imageObject->MetaData->BasicMetaData->ID;
			    $imageAttachment = $this->getCroppedImage( $publishForm, $imageId, 'C_TPF_MEDIA_SELECTOR' );
			    if( !$imageAttachment ) {
				    $imageAttachment = $imageObject->Files[0]; // fallback to native image
			    }
			    if( $imageAttachment ) {
				    $imagePaths[] = $imageAttachment->FilePath;
			    }
		    }
	    }

	    return $imagePaths;
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
	 * Get file info widgets
	 * These widgets are used to show the info of a picture in content station.
	 *
	 * @return array
	 */
	private function getFileWidgets()
	{
		$standardProperties = BizProperty::getPropertyInfos();
		$fileWidgets = array();

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
     * Return new PubField or Field object depending on the server version.
     *
     * @param string $Key
     * @param PropertyType $Type
     * @param array $Values
     *
     * @return PubField|Field
     */
    private static function getNewPubField($Key = null, $Type = null, $Values = null) {
        // PubField only exists in 7.0
        if (class_exists('PubField')) {
            return new PubField($Key, $Type, $Values);
        }
        return new Field($Key, $Type, $Values);
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
    public function getButtonBarForSetPublishPropertiesAction($defaultButtonBar, $publishFormTemplate, $publishForm) {
        $publishFormTemplate = $publishFormTemplate; // keep analyzer happy
        $publishForm = $publishForm; // keep analyzer happy
        //Remove the update and preview button
        foreach ($defaultButtonBar as $index => $button) {
            if (in_array($button->PropertyInfo->Name, array('Update', 'Preview'))) {
                unset($defaultButtonBar[$index]);
            }
        }


        return $defaultButtonBar;
    }

    /**
     * Returns the list of publish properties that needs to be saved in the database
     * @return type Array of strings
     */
    public function getPublishDossierFieldsForDB() {
        return array('URL', 'Id', 'Text');
    }

}