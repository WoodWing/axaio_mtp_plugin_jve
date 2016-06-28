<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class with functions called to publish to the Drupal publishing system.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';

class Drupal8_PubPublishing extends PubPublishing_EnterpriseConnector
{
	private $errors = array();
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * Publishes a dossier with contained objects (articles. images, etc.) to Drupal.
	 * The plugin is supposed to publish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @throws BizException Throws a BizException if the node cannot be published.
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @return array of PubFields containing information from Drupal
	 */
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		$pubFields = array();

		require_once dirname(__FILE__).'/Utils.class.php';
		require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';

		foreach ( $objectsInDossier as $objectInDossier ) {
			if( $objectInDossier->MetaData->BasicMetaData->Type == 'PublishForm' ) {
				// Prepare content.
				$values = $this->prepareNodeValues( $objectInDossier, $objectsInDossier, $publishTarget );
				// Publish the node.
				require_once dirname(__FILE__) . '/DrupalXmlRpcClient.class.php';
				$drupalXmlRpcClient = new DrupalXmlRpcClient($publishTarget);
				$result = $drupalXmlRpcClient->saveNode($dossier, $values, array());

				// Handle errors.
				if (isset($result['errors'])) {
					LogHandler::Log(__CLASS__ . '::' . __FUNCTION__ , 'ERROR', $result['errors']);
					throw new BizException( null, 'Server', null, $result['errors'] );
				}

				if (is_array($result) && isset($result['nid']) && isset($result['url'])) {
					$dossier->ExternalId = $result['nid'];
					$pubFields[] = new PubField('URL','string', array($result['url']));
				}
			}
		}
		return $pubFields;
	}

	/**
	 * Updates a published Dossier to Drupal.
	 *
	 * Updates/republishes a published dossier with contained objects (articles. images, etc.) to Drupal using the
	 * $dossier->ExternalId to identify the dosier to Drupal. The plugin is supposed to update/republish the dossier
	 * and it's articles and fill in some fields for reference.
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @throws BizException
	 * @return PubField[] Array containing information from Drupal.
	 */
	public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		$values = array(); // Keep analyzer happy.
		$pubFields = array();

		require_once dirname(__FILE__).'/Utils.class.php';
		require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';

		foreach ( $objectsInDossier as $objectInDossier ) {
			if( $objectInDossier->MetaData->BasicMetaData->Type == 'PublishForm' ) {
				// Prepare content.
				$values = $this->prepareNodeValues( $objectInDossier, $objectsInDossier, $publishTarget );
				// Publish the node.
				require_once dirname(__FILE__) . '/DrupalXmlRpcClient.class.php';
				$drupalXmlRpcClient = new DrupalXmlRpcClient($publishTarget);
				$result = $drupalXmlRpcClient->updateNode($dossier, $values, array());
				// Handle errors.
				if (isset($result['errors'])) {
					LogHandler::Log(__CLASS__ . '::' . __FUNCTION__ , 'ERROR', $result['errors']);
					throw new BizException( null, 'Server', null, $result['errors'] );
				}

				if (is_array($result) && isset($result['nid']) && isset($result['url'])) {
					$dossier->ExternalId = $result['nid'];
					$pubFields[] = new PubField('URL','string', array($result['url']));
				}
			}
		}
		return $pubFields;
	}

	/**
	 * Unpublishes and removes a published dossier from Drupal.
	 *
	 * The $dossier->ExternalId is used to identify the dosier in Drupal.
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @throws BizException
	 * @return array of PubFields containing information from Drupal
	 */
	public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget )
	{
		// Unpublish the node.
		require_once dirname(__FILE__) . '/DrupalXmlRpcClient.class.php';
		$drupalXmlRpcClient = new DrupalXmlRpcClient($publishTarget);
		$result = $drupalXmlRpcClient->removeNode( $dossier );

		// Handle errors.
		if (isset($result['errors'])) {
			LogHandler::Log(__CLASS__ . '::' . __FUNCTION__ , 'ERROR', $result['errors']);
			throw new BizException( null, 'Server', null, $result['errors'] );
		}

		$dossier->ExternalId = ""; // Empty the ExternalId on the dossier as the node is removed.
		return array(); // Return an empty array so the Dossier is saved.
	}

	/**
	 * Previews a Dossier.
	 *
	 * Previews a Dossier with contained objects (articles. images, etc.) to an external publishing system.
	 * The plugin is supposed to send the dossier and it's articles to the publishing system and fill in the URL field
	 * for reference.
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @throws BizException
	 * @return PubField[] containing information from Publishing system.
	 */
	public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		$values = array(); // Keep analyzer happy.
		$pubFields = array();

		require_once dirname(__FILE__).'/Utils.class.php';
		require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';

		foreach ( $objectsInDossier as $objectInDossier ) {
			if( $objectInDossier->MetaData->BasicMetaData->Type == 'PublishForm' ) {
				// Prepare content.
				$values = $this->prepareNodeValues( $objectInDossier, $objectsInDossier, $publishTarget );
				// Publish the node.
				require_once dirname(__FILE__) . '/DrupalXmlRpcClient.class.php';
				$drupalXmlRpcClient = new DrupalXmlRpcClient($publishTarget);
				$result = $drupalXmlRpcClient->previewNode($dossier, $values, array());

				// Handle errors.
				if (isset($result['errors'])) {
					LogHandler::Log(__CLASS__ . '::' . __FUNCTION__ , 'ERROR', $result['errors']);
					throw new BizException( null, 'Server', null, $result['errors'] );
				}

				if (is_array($result) && isset($result['nid']) && isset($result['url'])) {
					$dossier->ExternalId = $result['nid'];
					$pubFields[] = new PubField('URL','string', array($result['url']));
				}
			}
		}
		return $pubFields;
	}

	/**
	 * Prepares node data to be sent to Drupal.
	 *
	 * @param Object $publishForm The PublishForm to be published/updated/previewed.
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget The PublishForm Target
	 * @throws BizException Throws an Exception if the validation fails.
	 * @return array
	 */
	private function prepareNodeValues( $publishForm, $objectsInDossier, $publishTarget )
	{
		require_once dirname(__FILE__).'/Utils.class.php';
		require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';
		require_once dirname(__FILE__) . '/DrupalXmlRpcClient.class.php';
		require_once dirname(__FILE__).'/DrupalField.class.php';
		$drupalXmlRpcClient = new DrupalXmlRpcClient($publishTarget);

		$fields = null;
		$pattern = null;
		$values = null;

		$templateId = BizPublishForm::getTemplateId( $publishForm );
		$attachmentUploaded = array(); // To store EnterpriseId => DrupalId if there's any file uploaded.
		if (!is_null($templateId)) {
			$pattern_prefix = '/^C_DPF_' . $templateId;
			$pattern = $pattern_prefix . '_[A-Z0-9_]{0,}$/';
			$fields = WW_Plugins_Drupal8_Utils::getFormFields( $publishForm, $pattern );

			// Validate mandatory fields.
			if (BizPublishForm::validateFormFields($fields, $publishForm, $pattern )) {
				$wiwiwUsages = array();
				$propertyUsages = BizPublishForm::getPropertyUsagesForForm( $publishForm, $pattern, false, false, $wiwiwUsages );
				$values = WW_Plugins_Drupal8_Utils::prepareFormFields( $propertyUsages, $wiwiwUsages, $fields );
			} else {
				$message = 'The Dossier could not be published.';
				LogHandler::Log(__CLASS__ . '::' . __FUNCTION__ , 'ERROR', $message);
				throw new BizException( null, 'Server', null, $message );
			}

			// Array entry structure:
			//
			// ArticleComponent:
			//   $values[$field][elements] = 'elementTextContent';
			//   $values[$field][attachments] = array( 'EnterpriseObjectId' => 'DrupalFileId' );
			//
			// FileSelector: The returned value for the FileSelector can be a single file, or can be an array of files.
			//   $values[$field][] = array( 'fid' => 1, 'description' => 'desc', 'display' => '0'. // File
			// or
			//   $values[$field][] = array( 'fid' => 1, 'alt' => 'alt text', 'title' => 'An image'. // Image
			//
			// Other input fields.
			//  Values are taken as is, they already contain the right structure to be handled by Drupal.

			// Handle attachments to be uploaded to Drupal.
			//Todo: stream attachments instead of loading them in memory.

			foreach ($values as $field => $value) {
				// Handle normal file attachments, for example for a file selector or layout.
				$contentType = $values[WW_Plugins_Drupal8_Utils::DRUPAL8_CONTENT_TYPE][0];

				if( is_null( $value )) {
					$values[$field] = array( null ); // To avoid rpc call truncating null value: In RPC call
				} elseif (is_array($value) && is_array($value[0]) && isset($value[0]['elements'])) { //Handle file attachments(such as InlineImages) on ArticleComponents.
					// Get the element contents.
					$value[0]['elements'] = $value[0]['elements'][0]->Content;

					// Upload the inline images and make sure they are mapped to Enterprise object IDs.
					$fileIds = array();
					if (isset($value[0]['attachments'])) foreach ($value[0]['attachments'] as $key => $attachment ) {
						$fileId = $drupalXmlRpcClient->uploadAttachment( $attachment, $key, $field,	$contentType);
						$fileIds[$key] =  $fileId;
					}
					// Set the fileIds (EnterpriseObjectId => DrupalFileId) as attachments on the value.
					$value[0]['attachments'] = $fileIds;

					// Overwrite the value in the data to be sent to Drupal.
					$values[$field] = $value;

				// Handle File Selectors.
				} elseif ( is_array( $value ) && is_array( $value[0]) && !isset($value[0]['elements'])
					|| is_array($value) && is_object($value[0]) ) {

					// Detect if we are handling a multi-file upload or a single file upload.
					// If we are handling a single upload, restructure the file as needed.
					if ( (is_array( $value ) && is_array( $value[0]) && !isset($value[0]['elements'])) == false ) {
						$newValue = array($value);
						$value = $newValue;
						$values[$field] = $value;
					}

					// Now loop through our field values to upload the files.
					if ($value) foreach ( $value as $key => $attachmentAndMetaData ) {
						$fileId = null;
						$childId = $attachmentAndMetaData['metadata']->BasicMetaData->ID;
						$uploadNeeded = $this->doesUploadChildNeeded( $publishForm, $objectsInDossier[$childId], $publishTarget );

						if( $uploadNeeded ) {
							// Upload the attachment to Drupal.
							$fileId = $drupalXmlRpcClient->uploadAttachment(
								$attachmentAndMetaData[0],
								$attachmentAndMetaData['metadata']->BasicMetaData->Name,
								$field,
								$contentType
							);
						} else { // No changes since uploaded, so used back the existing ExternalId.
							$fileId = $objectsInDossier[$childId]->ExternalId;
						}

						// Set the additional MetaData values needed by Drupal.
						$newMetaData = array();
						$newMetaData['fid'] = $fileId;
						$filePatternPrefix = '/^C_DPF_F_' . $templateId . '_' . $field . '_';
						$filePatternPostfix = '_[A-Z0-9_]{0,}$/';
						foreach ($attachmentAndMetaData['metadata']->ExtraMetaData as $extra) {
							// If the type is image, we need to add fields that are normally only for other Files because
							// we do not know what the purpose of the Image is. The Image can be used as part of a Drupal
							// file selector of a drupal image selector, if it is used as a File then Description / Display
							// are needed, in other cases Title and Alt are needed. Therefore in case of an Image set
							// both the sets. So set the File properties for any files being uploaded.

							// Check the File's Display setting.
							$filePattern = $filePatternPrefix . 'DIS' . $filePatternPostfix;
							if (preg_match($filePattern, $extra->Property)) {
								$newMetaData['display']  = $extra->Values[0];
							}

							// Check the File's Description setting.
							$filePattern = $filePatternPrefix . 'DES' . $filePatternPostfix;
							if (preg_match($filePattern, $extra->Property)) {
								$newMetaData['description']  = $extra->Values[0];
							}

							// Set fields only needed for Images.
							if ($attachmentAndMetaData['metadata']->BasicMetaData->Type == 'Image') {
								// Check the Image's Alternate Text setting.
								if ($extra->Property === DrupalField::DRUPAL_IMG_ALT_TEXT) {
									$newMetaData['alt']  = $extra->Values[0];
								}

								// Check the Image's Title setting.
								if ($extra->Property === DrupalField::DRUPAL_IMG_TITLE) {
									$newMetaData['title']  = $extra->Values[0];
								}
							}
						}

						// Set our MetaData values to be sent to Drupal.
						$values[$field][$key] = $newMetaData;

						// For Child ExternalId.
						$attachmentUploaded[$childId] = $fileId;

					}
				}
				// Remove Metadata from the values if present.
				if (isset($values[$field]['metadata'])) {
					unset($values[$field]['metadata']);
				}

				// Use array_key_exists here.. The value can be null!!!
				if ( array_key_exists( $field.'_SUM', $values ) ) {
					$summary = $values[$field.'_SUM'];
					if (isset($summary['metadata'])) {
						unset($summary['metadata']);
					}

					// Save this value as value-summary, it will be handled as such on the Drupal side
					$values[$field] = array( 'value' => $values[$field], 'summary' => $summary );
					unset($values[$field.'_SUM']);
				}
			}
		}
		$this->updateExternalId( $objectsInDossier, $attachmentUploaded );
		return $values;
	}

	/**
	 * Update the external ID for an object published to Drupal.
	 *
	 * Places the Drupal ID (fid) in the object's ExternalId field.
	 *
	 * @param array $objectsInDossier All child in the dossier / placed on Form to be updated with ExternalId(DrupalId).
	 * @param array $attachmentUploaded Key-Value pair list where Key is the Enteprise DB Id and Value is DrupalId.
	 */
	private function updateExternalId( $objectsInDossier, $attachmentUploaded )
	{
		if( $attachmentUploaded ) foreach( $attachmentUploaded as $enterpriseId => $drupalId ) {
			if( isset( $objectsInDossier[$enterpriseId]->ExternalId ) ) {
				$objectsInDossier[$enterpriseId]->ExternalId = $drupalId;
			}
		}
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
	 * @return bool True when child object upload is needed; False otherwise.
	 */
	private function doesUploadChildNeeded( $publishForm, $childObj, $publishTarget )
	{
		$publishedChildVersion = null;
		$publishedChildPublishDate = null;
		$formRelations = $publishForm->Relations;
		if( $formRelations ) foreach( $formRelations as $relation ) {
			if( $relation->Parent == $publishForm->MetaData->BasicMetaData->ID &&
				$relation->Child == $childObj->MetaData->BasicMetaData->ID &&
				$relation->Type == 'Placed' ) {
				if( $relation->Targets ) foreach( $relation->Targets as $target ) {
					$isSameIssue = ( $target->Issue->Id == $publishTarget->IssueID );
					if( $isSameIssue ) {
						$publishedChildVersion = $target->PublishedVersion;
						$publishedChildPublishDate = $target->PublishedDate;
						break 2;
					}
				}
			}
		}
		$uploadNeeded = false;
		if( is_null( $publishedChildVersion ) || !$publishedChildPublishDate ) {
			$uploadNeeded = true; // Has never been uploaded or is unpublished before (PublishDate set empty).
		}else {
			require_once BASEDIR.'/server/utils/VersionUtils.class.php';
			if( VersionUtils::versionCompare( $publishedChildVersion,$childObj->MetaData->WorkflowMetaData->Version, '<' )) {
				$uploadNeeded = true; // Modification has been done since published, so re-upload is needed.
			}
		}
		return $uploadNeeded;
	}

	/**
	 * Requests Publish Fields from Drupal.
	 *
	 * Uses the dossier->ExternalId to identify the dossier in Drupal. Called by the core (BizPublishing.class.php).
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @return PubField[] Array containing information gathered from Drupal.
	 */
	public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget )
	{
		$result = array();
		$map = array(
			'Views'    => 'int',
			'Rating'   => 'double',
			'Raters'   => 'int',
			'CommentsCount' => 'int',
			'Comments' => 'multistring',
			'URL'      => 'string'
		);

		// Get the info from Drupal.
		require_once dirname(__FILE__).'/DrupalXmlRpcClient.class.php';
		$drupalXmlRpcClient = new DrupalXmlRpcClient( $publishTarget );
		$response = $drupalXmlRpcClient->nodeGetInfo( $dossier );
		if( $response ) foreach( $response as $fieldKey => $fieldVal ) {
			if( $fieldVal == 'N/A' ) {
				$type = 'string';
				$fieldVal = BizResources::localize('NOT_AVAILABLE');
			} else {
				$type = isset( $map[$fieldKey] ) ? $map[$fieldKey] : 'string';
			}
			$result[] = DrupalXmlRpcClient::getField( $fieldKey, $type, $fieldVal );
		}
		return $result;
	}

	/**
	 * Requests the Dossier URL from Drupal.
	 *
	 * Uses the $dossier->ExternalId to identify the dosier to Drupal. (Called by the core, BizPublishing.class.php)
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @return string The url to the content.
	 */
	public function getDossierURL( $dossier, $objectsInDossier, $publishTarget )
	{
		require_once dirname(__FILE__).'/DrupalXmlRpcClient.class.php';
		$drupalXmlRpcClient = new DrupalXmlRpcClient($publishTarget);
		$url = $drupalXmlRpcClient->getUrl($dossier);
		return $url;
	}

	/**
	 * Validates the Dossier for publishing.
	 *
	 * This function is called when the GetDossier is called for PublishDossier, UnPublishDossier or UpdateDossier.
	 * In this function an array of arrays can be created as:
	 *
	 * array( 'errors' => array(), 'warnings' => array(), 'infos' => array());
	 *
	 * Errors stop the publishing of the object. Warnings are shown to the user, but a user is still allowed to publish.
	 * Infos are just informational strings.
	 *
	 * @param string $type the type of the validation. PublishDossier, UnPublishDossier or UpdateDossier
	 * @param int $dossierId The id of the dossier to publish
	 * @param int $issueId The id of the issue to publish in
	 * @return array An array containing Error information, if any.
	 */
	public function validateDossierForPublishing( $type, $dossierId, $issueId )
	{
		// If Content Station 7.1.x is used you can use this to validate the input before publishing or updaing
		return array('errors' => array(), 'warnings' => array(), 'infos' => array());
	}

	/**
	 * Get the correct rendition for a to publish object.
	 *
	 * Defaults to the native rendition if not specified.
	 *
	 * @param PubPublishedObject $object
	 * @return string The Rendition type.
	 */
	protected function askRenditionType( $object )
	{
		$rendition = 'native';
		switch( $object->MetaData->BasicMetaData->Type ) {
			case 'Layout':
				$rendition = 'output';
				break;
		}
		return $rendition;
	}

	/**
	 * Refer to PubPublishing_EnterpriseConnector::doesSupportPublishForms() header.
	 *
	 * @see PubPublishing_EnterpriseConnector::doesSupportPublishForms().
	 */
	public function doesSupportPublishForms()
	{
		return true; // Supports Publish Forms feature.
	}

	/**
	 * Retrieves content types from Drupal and transforms them into Enterprise Objects.
	 *
	 * The Drupal content types are retrieved and transformed into Objects (Publish Form Templates) in memory and
	 * returned to the caller.
	 *
	 * Refer to PubPublishing_EnterpriseConnector::getPublishFormTemplates() header.
	 *
	 * @param int $pubChannelId The publicationId for which to retrieve the templates, default null.
	 * @return array
	 */
	public function getPublishFormTemplates( $pubChannelId )
	{
		require_once dirname(__FILE__).'/Utils.class.php'; // WW_Plugins_Drupal8_Utils.
		require_once dirname(__FILE__).'/DrupalXmlRpcClient.class.php';
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php'; // PubPublishTarget.
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';

		$publishTarget = new PubPublishTarget( $pubChannelId );
		$drupalXmlRpcClient = new DrupalXmlRpcClient( $publishTarget );

		$templatesObj = array();
		$contentTypes = $drupalXmlRpcClient->getContentTypes();
			if ( $contentTypes ) foreach ( $contentTypes as $contentType ) {
			$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
				$pubChannelId, $contentType['name'],
				$contentType['description'],
				WW_Plugins_Drupal8_Utils::convertContentType2DocumentId( $pubChannelId, $contentType['original'] )
			);
		}
		return $templatesObj;
	}

	/**
	 * This function can return a dialog that is shown in Content Station. This is used for the Multi Channel Publishing Feature.
	 *
	 * @since 9.0
	 * @param Object $publishFormTemplate
	 * @return Dialog|null Dialog definition|The default connector returns null which indicates it doesn't support the getDialog call.
	 */
	public function getDialogForSetPublishPropertiesAction( $publishFormTemplate )
	{
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		require_once dirname(__FILE__).'/DrupalField.class.php';
		require_once dirname(__FILE__).'/Utils.class.php'; // WW_Plugins_Drupal8_Utils

		$basicMD = $publishFormTemplate->MetaData->BasicMetaData;
		$contentType = WW_Plugins_Drupal8_Utils::convertDocumentId2ContentType( $basicMD->DocumentID );
		$pubChannelId = $publishFormTemplate->Targets[0]->PubChannel->Id;

		$dialog = WW_Utils_PublishingUtils::getDefaultPublishingDialog( $basicMD->Name );

		// Set the Default focus to the title field.
		$dialog->Tabs[0]->DefaultFocus = 'C_DPF_' . $basicMD->ID . '_TITLE';

		// Add The data for the GeneralFields tab.
		$fields = $this->getFields( $pubChannelId, $contentType );
		$tab = WW_Utils_PublishingUtils::getPublishingTab( 'GeneralFields' );

		if( is_array( $fields ) && isset($fields['custom_fields']) ) {
			foreach( $fields['custom_fields'] as $field ) {
				$errors = array();

				// Create a new DrupalField and get any errors from the field generation.
				$drupalField = DrupalField::generateFromDrupalFieldDefinition($field, $basicMD->ID, $pubChannelId, $contentType );
				$errors = array_merge($errors, $drupalField->getErrors());

				// Attempt to create a propertyInfo, and get any errors.
				$propertyInfos = $drupalField->generatePropertyInfo(false, $errors);
				$errors = array_merge($errors, $drupalField->getErrors());

				// No errors, add the property to the list.
				if ( count($propertyInfos) > 0 && !$drupalField->hasError() ) { // No errors or just warnings
					foreach( $propertyInfos as $propertyInfo ) {
						$summaryWidget = ( preg_match('/^C_DPF_[0-9]*_[0-9]*_SUM_*/', $propertyInfo->Name ) === 1 );

						$widget = new DialogWidget();
						$widget->PropertyInfo = $propertyInfo;
						$widget->PropertyUsage = new PropertyUsage();
						$widget->PropertyUsage->Name            = $widget->PropertyInfo->Name;
						$widget->PropertyUsage->Editable        = true;
						$widget->PropertyUsage->Mandatory       = $summaryWidget ? false : $drupalField->required;
						$widget->PropertyUsage->Restricted      = false;
						$widget->PropertyUsage->RefreshOnChange = false;
						$widget->PropertyUsage->InitialHeight   = $summaryWidget ? $drupalField->sumInitialHeight : $drupalField->initialHeight;

						if ( $widget->PropertyInfo->Widgets ) foreach ( $widget->PropertyInfo->Widgets as &$subWidget ) {
							$summaryWidget = ( preg_match('/^C_DPF_[0-9]*_[0-9]*_SUM_*/', $subWidget->PropertyInfo->Name ) === 1 );

							$subWidget->PropertyUsage = new PropertyUsage();
							$subWidget->PropertyUsage->Name            = $subWidget->PropertyInfo->Name;
							$subWidget->PropertyUsage->Editable        = true;
							$subWidget->PropertyUsage->Mandatory       = $summaryWidget ? false : $drupalField->required;
							$subWidget->PropertyUsage->Restricted      = false;
							$subWidget->PropertyUsage->RefreshOnChange = false;
							$subWidget->PropertyUsage->InitialHeight   = $summaryWidget ? $drupalField->sumInitialHeight : $drupalField->initialHeight;

							if ($subWidget->PropertyInfo->Widgets) foreach ( $subWidget->PropertyInfo->Widgets as &$subSubWidget ) {
								$subSubWidget->PropertyUsage = new PropertyUsage();
								$subSubWidget->PropertyUsage->Name            = $subSubWidget->PropertyInfo->Name;

								// Some fields are not editable.
								$readOnlyProperties = array( 'Format', 'Width', 'Height');
								// Some fields are mandatory.
								$mandatoryProperties = array( 'Name', 'Width', 'Height', 'Format');

								$subSubWidget->PropertyUsage->Editable = (!in_array($subSubWidget->PropertyInfo->Name , $readOnlyProperties));
								$subSubWidget->PropertyUsage->Mandatory       = (in_array($subSubWidget->PropertyInfo->Name , $mandatoryProperties));
								$subSubWidget->PropertyUsage->Restricted      = false;
								$subSubWidget->PropertyUsage->RefreshOnChange = false;
								$subSubWidget->PropertyUsage->InitialHeight   = $drupalField->initialHeight;
							}
						}

						$tab->Widgets[] = $widget;
					}
				} else {
					$this->errors = array_merge( $this->errors, $errors );
				}
			}
		}

		$dialog->Tabs[] = $tab;
		$extraMetaData = null;
		$widgets = (count($tab->Widgets) > 0 ) ? $tab->Widgets : array();
		$dialog->MetaData = $this->extractMetaDataFromWidgets( $extraMetaData, $widgets );

		// Get the publish properties and set them on the dialog.
		if (is_array($fields) && isset($fields['basic_fields'])) {
			$drupalField = new DrupalField();
			$propertyInfos = $drupalField->getSpecialPropertyInfos($basicMD->ID, $fields['basic_fields'],
				$pubChannelId, $contentType );

			// Collect anyway regardless of have errors and warnings or not
			// except that the property that has error will not be in the collection(will not be collected).
			$widgets = array();
			if( $propertyInfos ) foreach ($propertyInfos as $propertyInfo ) {
				$widget = new DialogWidget();
				$widget->PropertyInfo = $propertyInfo;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name            = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable        = true;
				$widget->PropertyUsage->Mandatory       = $propertyInfo->Required;
				$widget->PropertyUsage->Restricted      = false;
				$widget->PropertyUsage->RefreshOnChange = false;
				$widget->PropertyUsage->InitialHeight   = null;
				$widgets[] = $widget;
			}
			$dialog->Tabs[0]->Widgets = $widgets;

			if( $drupalField->hasError() ) {
				$this->errors = array_merge( $this->errors, $drupalField->getErrors() );
			}
		}
		return $dialog;
	}

	/**
	 * Composes a Dialog->MetaData list from dialog widgets and custom properties.
	 *
	 * @param array $extraMetaDatas List of ExtraMetaData elements.
	 * @param array $widgets List of DialogWidget elements.
	 * @return array List of MetaDataValue elements.
	 */
	public function extractMetaDataFromWidgets( $extraMetaDatas, $widgets )
	{
		$metaDataValues = array();
		if( $widgets ) foreach( $widgets as $widget ) {
			if( $extraMetaDatas ) foreach( $extraMetaDatas as $extraMetaData ) {
				if( $widget->PropertyInfo->Name == $extraMetaData->Property ) {
					$metaDataValue = new MetaDataValue();
					$metaDataValue->Property = $extraMetaData->Property;
					$metaDataValue->Values = $extraMetaData->Values; // Array of strings.
					$metaDataValues[] = $metaDataValue;
					break; // Found.
				}
			}
		}
		return $metaDataValues;
	}

	/**
	 * Get the field definitions setup for a given content type (or for all content types).
	 *
	 * @param integer $pubChannelId Indicates which Drupal site to retrieve from.
	 * @param string $contentType Filter fields per content type. Pass NULL to get all.
	 * @return array The field definitions.
	 */
	public function getFields( $pubChannelId, $contentType )
	{
		require_once dirname(__FILE__) . '/DrupalXmlRpcClient.class.php';
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php'; // PubPublishTarget

		// Set the actual publish target object so we know which site to contact.
		$publishTarget = new PubPublishTarget( $pubChannelId );
		// Get the fields.
		$drupalXmlRpcClient = new DrupalXmlRpcClient($publishTarget);
		$fields = $drupalXmlRpcClient->getFields( $contentType );
		return $fields;
	}

	/**
	 * We provide our own icons to show in UI for our Drupal channels.
	 * Icons are provided in our plug-ins folder as read by the core server:
	 *    /server/plugins/Drupal8/pubchannelicons
	 *
	 * @since 9.0
	 * @return boolean
	 */
	public function hasPubChannelIcons()
	{
		return true;
	}

	/**
	 * Returns errors collected during calling of getDialogForSetPublishPropertiesAction().
	 *
	 * @return array Errors collected during dialog import.
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Ensure that the necessary PubFields are stored as part of the PublishHistory.
	 *
	 * @return string[] An array of string keys of the PubFields to be stored in the PublishHistory.
	 */
	public function getPublishDossierFieldsForDB()
	{
		// Ensure that the URL for the Drupal Node is stored in the PublishHistory.
		return array('URL');
	}

}
