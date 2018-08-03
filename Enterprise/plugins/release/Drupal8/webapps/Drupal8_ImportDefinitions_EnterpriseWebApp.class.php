<?php
/**
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';

class Drupal8_ImportDefinitions_EnterpriseWebApp extends EnterpriseWebApp 
{
	/** 
	 * List of pub channels for which this plugin can publish (with PublishSystem set to
	 * Drupal 8) and where the admin user has access to.
	 *
	 * @var array $pubChannelInfos List of PubChannelInfo data objects
	 */
	private $pubChannelInfos;
	static private $selectedPubChannelInfos = null;
	
	public function getTitle()      { return 'Import Content Types from Drupal 8'; }
	public function isEmbedded()    { return true; }
	public function getAccessType() { return 'admin'; }
	
	/**
	 * Called by the core server. Builds the HTML body of the web application.
	 *
	 * @return string HTML
	 */
	public function getHtmlBody() 
	{
		require_once dirname(__FILE__).'/../Utils.class.php';
		$pluginName = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
		// Intercept user input.
		$importBtnPressed = isset($_REQUEST['import']);
        $pubChannelToImport = isset($_REQUEST['pubchanneldropdown']) ? $_REQUEST['pubchanneldropdown'] : 'all';
        $importContentTypes = ( isset($_REQUEST['content-types']) ? $_REQUEST['content-types'] : false);
        $importTaxonomies = ( isset($_REQUEST['taxonomies']) ? $_REQUEST['taxonomies'] : false);
		$importStatus = '';
		// Build the HTML form.
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$htmlTemplateFile = dirname(__FILE__).'/importdefs.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );
        require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
        $pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( $pluginName );

		if( $importBtnPressed && ( $importContentTypes || $importTaxonomies )) {
			try {
				// Raise the max execution time to ensure that the plugin has enough time to get and save all the data.
				set_time_limit(3600);

                if( $pubChannelToImport == 'all' ){
				    $this->pubChannelInfos = $pubChannelInfos;
                } else {
                    foreach( $pubChannelInfos as $pubChannelKey => $selectedPubChannel ){
                        if( $selectedPubChannel->Id == $pubChannelToImport ){
                            $this->pubChannelInfos = array( $pubChannelInfos[$pubChannelKey] );
                            break;
                        }
                    }
                }

				$this->setContext();

                if( $importContentTypes ){
                    $this->importPublishFormTemplates();
                }

                if( $importTaxonomies ){
                    $this->importTermEntitiesAndTerms(); // Make sure to import TermEntities&Terms first, as the DB id is needed to populate the field in the custom props below.
                }

                $this->importCustomObjectProperties();

				$this->importPublishFormDialogs();
				$htmlBody = $this->printImportResults( $importStatus, $htmlBody );
			} catch ( BizException $e ) {
				$importStatus = '<p style="color:red">Import failed: ' . $e->getMessage() .'<br /> Detail: '.
					( $e->getDetail() ? $e->getDetail() : 'No details' ) .'</p>';
			}
		} else if( $importBtnPressed && ( !$importContentTypes && !$importTaxonomies )){
            $importStatus = '<font color=red>Import failed: None of the checkboxes are checked </font>';
        }

        $pubChannelDropDown = '<option selected value="all">All</option>';

        if( $pubChannelInfos ){
            foreach( $pubChannelInfos as $pubChannel ){
                $pubChannelDropDown .= '<option value=\'' . $pubChannel->Id . '\'>' . $pubChannel->Name . '</option>';
            }
        }

		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$unusedPublishFormProperties = BizProperty::getUnusedPublishFormProperties( $pluginName );
		if( count($unusedPublishFormProperties) > 0 ) {
			$url = '<a href="' . SERVERURL_ROOT.INETROOT.'/server/admin/removeunusedproperties.php?serverplugin='. $pluginName . '">'.
				BizResources::localize( 'ACT_CLEAN_UP' ) .'</a>';
			$unusedMessage = BizResources::localize( 'INFO_MESS_LINK_UNUSED_PROP', true, array( $pluginName ) ) . '<br/>';
			$unusedMessage.= BizResources::localize( 'INFO_CLEANUP_PROPERTY', true,	array( $url ) );
			$htmlBody = str_replace ( '<!--UNUSEDMESSAGE-->', $unusedMessage, $htmlBody );
		}

		$htmlBody = $this->printErrorsWarnings( $htmlBody, false, false );
		$htmlBody = str_replace ( '<!--PUB_CHANNEL_DROP_DOWN-->', $pubChannelDropDown, $htmlBody );
        $htmlBody = str_replace ( '<!--IMPORT_STATUS-->', $importStatus, $htmlBody );
		return $htmlBody;
	}

	/**
	 * Sets the context in which the Web Application runs. For now the selected PubChannelInfos are set.
	 */
	private function setContext()
	{
		self::$selectedPubChannelInfos = $this->pubChannelInfos;
	}

	/**
	 * Returns the context in which the Web Application runs (for now only the selected PubChannelInfos).
	 *
	 * @return the context in which the Web Application runs.
	 */
	static public function getContext()
	{
		return self::$selectedPubChannelInfos;
	}

	/**
	 * Resets the context. This method must be called after the context is read by getContext(). The reason is to
	 * limit the context to the processing of the Web Application.
	 */
	static public function resetContext()
	{
		self::$selectedPubChannelInfos = null;
	}

	/**
	 * Prints the import results
	 * Fills in the $htmlBody (The import page) with the Drupal import results.
	 *
	 * @param string $importStatus Empty string to be filled in with the import result status
	 * @param string $htmlBody The body page to be filled in with the import results
	 * @return string Body page with the import result.
	 */
	private function printImportResults( &$importStatus, $htmlBody )
	{
		$errorMessages = $this->getErrorMessages();
		$errorsMsgTxt = '';
		$warningsMsgTxt = '';
		$hasError = false;
		$hasWarning = false;
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';
		$errorSectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'IMPORTERROR_RECORD' );
		$warningSectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'IMPORTWARNING_RECORD' );

		require_once BASEDIR . '/server/utils/PublishingUtils.class.php';
		require_once dirname(__FILE__) . '/../Utils.class.php';
		$channelInfos = array();

		foreach( array_values( $errorMessages ) as $errorMessage ) {
			if( $errorMessage ) {
				foreach( $errorMessage as $eachErrorMsg ) {

					// Get the channel related information.
					$siteUrl = '';
					if (isset($channelInfos[$eachErrorMsg['pubchannelid']])) {
						// Use stored information.
						$channel = $channelInfos[$eachErrorMsg['pubchannelid']]['channel'];
						$channelUrl = $channelInfos[$eachErrorMsg['pubchannelid']]['channelUrl'];
						$siteUrl = $channelInfos[$eachErrorMsg['pubchannelid']]['siteUrl'];
					} else {
						// Retrieve the channel.
						$channel = WW_Utils_PublishingUtils::getAdmChannelById($eachErrorMsg['pubchannelid']);

						if (!is_null($channel)) {
							// get the publication.
							$publication = WW_Utils_PublishingUtils::getAdmPublicationByChannelId($channel->Id);

							// Compose the Channel URL.
							$channelUrl = SERVERURL_ROOT.INETROOT.'/server/admin/editChannel.php?publid='
								. $publication->Id . '&channelid=' . $channel->Id;

							// Compose the Site URL.
							$selectedSite = WW_Utils_PublishingUtils::getAdmPropertyValue($channel, WW_Plugins_Drupal8_Utils::CHANNEL_SITE_URL);
							$configuration = WW_Plugins_Drupal8_Utils::resolveConfigurationSettings( $selectedSite );
							if( $configuration ) {
								$siteUrl = $configuration['url'];
							}

							// Store the data for reuse.
							$channelInfos[$eachErrorMsg['pubchannelid']] = array();
							$channelInfos[$eachErrorMsg['pubchannelid']]['channel'] = $channel;
							$channelInfos[$eachErrorMsg['pubchannelid']]['channelUrl'] = $channelUrl;
							$channelInfos[$eachErrorMsg['pubchannelid']]['siteUrl'] = $siteUrl;
						}
					}

					if( $eachErrorMsg['severity'] == 'error' ) {
						$sectionTxt = $errorSectionObj->getSection( $htmlBody );
						$sectionTxt = str_replace('<!--PAR:E_CONTENTTYPE-->', $eachErrorMsg['content_type'], $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:E_SITE-->', $siteUrl, $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:SITEURL-->', $siteUrl, $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:E_PUBCHANNELID-->', $channel->Name, $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:PUBCHANNELURL-->', $channelUrl, $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:E_FIELDNAME-->', $eachErrorMsg['field_name'], $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:E_MESSAGE-->', $eachErrorMsg['message'], $sectionTxt);
						$errorsMsgTxt .= $sectionTxt;

						$hasError = true;
						$severity = 'ERROR';
					} elseif ( $eachErrorMsg['severity'] == 'warn' ) {
						$sectionTxt = $warningSectionObj->getSection( $htmlBody );
						$sectionTxt = str_replace('<!--PAR:W_CONTENTTYPE-->', $eachErrorMsg['content_type'], $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:W_SITE-->', $siteUrl, $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:SITEURL-->', $siteUrl, $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:W_PUBCHANNELID-->', $channel->Name, $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:PUBCHANNELURL-->', $channelUrl, $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:W_FIELDNAME-->', $eachErrorMsg['field_name'], $sectionTxt);
						$sectionTxt = str_replace('<!--PAR:W_MESSAGE-->', $eachErrorMsg['message'], $sectionTxt);
						$warningsMsgTxt .= $sectionTxt;

						$hasWarning = true;
						$severity = 'WARN';
					}
					LogHandler::Log($channel->Name.'::' . $eachErrorMsg['content_type'], $severity,
						$eachErrorMsg['field_name'] . ': ' . $eachErrorMsg['message'] );
				}
			}
		}
		$prefix = 'Import status: ';
		if( !$hasError && !$hasWarning ) {
			$importStatus = $prefix . 'import successful';
		} else {
			$importStatus = $prefix . 'a warning or error occurred. Please check the error messages or warning messages below.';
		}
		$htmlBody = $errorSectionObj->replaceSection( $htmlBody, $errorsMsgTxt );
		$htmlBody = $warningSectionObj->replaceSection( $htmlBody, $warningsMsgTxt );

		$htmlBody = $this->printErrorsWarnings( $htmlBody, $hasError, $hasWarning );

		return $htmlBody;
	}

	/**
	 * Determines whether or not to display the error or warning if there's any during the import.
	 *
	 * @param string $htmlBody
	 * @param bool $hasError To determine if the Error should be shown in the import page. True to show.
	 * @param bool $hasWarning To determine if the Warning should be shown in the import page. True to show.
	 * @return string The import page filled with errors or warnings if there's any.
	 */
	private function printErrorsWarnings( $htmlBody, $hasError=false, $hasWarning=false )
	{
		$displayErrorMessages = $hasError ? '' : 'display:none';
		$htmlBody = str_replace('<!--PAR:DISPLAY_ERRORMSG-->', $displayErrorMessages, $htmlBody );
		$displayWarningMessages = $hasWarning ? '' : 'display:none';
		$htmlBody = str_replace('<!--PAR:DISPLAY_WARNINGMSG-->', $displayWarningMessages, $htmlBody );
		return $htmlBody;
	}
	
	/**
	 * Let the core validate and install the custom properties introduced by our plugin.
	 */
	private function importCustomObjectProperties()
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$pluginErrs = null;
		BizProperty::validateAndInstallCustomProperties( 'Drupal8', $pluginErrs, false );
	}
	
	/**
	 * Imports the Publish Form Dialogs.
	 */
	private function importPublishFormDialogs()
	{
		// Retrieve the Templates from the database, we only need to get this set once 
		// since we do not need to store this per channel, but rather based on the unique 
		// document id provided by the template.
		if ($this->pubChannelInfos) {
			require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
            foreach ( $this->pubChannelInfos  as $pubChannelInfo ) {
			    $resp = $this->queryTemplatesFromDb( $pubChannelInfo->Id );
			    BizPublishing::createPublishingDialogsWhenMissing($pubChannelInfo->Id, $resp);
            }
		}
	}
	
	/**
	 * Retrieves the PublishFormTemplate objects provided (hardcoded) by the plugin
	 * and inserts them into the Enterprise DB in case they do not exist yet.
	 */
	private function importPublishFormTemplates()
	{
		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		require_once dirname(__FILE__).'/../Drupal8_PubPublishing.class.php';
		if( $this->pubChannelInfos ) foreach( $this->pubChannelInfos as $pubChannelInfo ) {
			// Repair PublishFormTemplates if needed because of a change from
			$success = self::repairPublishFormTemplates( $pubChannelInfo->Id );

			if (!$success) {
				LogHandler::Log(__CLASS__ . '::' . __FUNCTION__, 'ERROR', 'Repairing templates failed, proceeding with '
					. 'Import procedure.');
			}

			$resp = $this->queryTemplatesFromDb( $pubChannelInfo->Id );
			BizPublishing::createPublishingTemplatesWhenMissing( $pubChannelInfo->Id, $resp, true );
		}
	}

	/**
	 * Imports the Term Entities and Terms from Drupal8 and insert into Enterprise database.
	 */
	private function importTermEntitiesAndTerms()
	{
		require_once dirname(__FILE__).'/../Utils.class.php';
		require_once dirname(__FILE__).'/../XmlRpcClient.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermEntitiesService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermsService.class.php';

		$imported = array();
		if( $this->pubChannelInfos ) foreach( $this->pubChannelInfos as $pubChannelInfo ) {
			$publishSystemId = BizAdmPublication::getPublishSystemIdForChannel( $pubChannelInfo->Id );
			if( !isset( $imported[$publishSystemId] )) {
				$this->clearTermEntitiesAndTerms( $publishSystemId );

				// Of course we should foreach instead to get all the channels.
				$publishTarget = new PubPublishTarget( $pubChannelInfo->Id );
				$drupalXmlRpcClient = new WW_Plugins_Drupal8_XmlRpcClient( $publishTarget );

				// Fetching the Vocabulary names (Term Entities) from Drupal8.
				$drupalVocabNames = $drupalXmlRpcClient->getVocabularyNames();
				foreach( $drupalVocabNames as $drupalVocabUuid => $info ) {
					$termEntity = new AdmTermEntity();
					$termEntity->Name = $info['name'];
					$termEntity->AutocompleteProvider = 'Drupal8';
					$termEntity->PublishSystemId = $publishSystemId;

					$service = new AdmCreateAutocompleteTermEntitiesService();
					$request = new AdmCreateAutocompleteTermEntitiesRequest();
					$request->Ticket = BizSession::getTicket();
					$request->TermEntities = array( $termEntity );
					$response = $service->execute( $request );

					// Fetching the Vocabularies (Terms) from Drupal8 given the vocabulary name id from Drupal8.
					$drupalVocabs = $drupalXmlRpcClient->getVocabulary( $drupalVocabUuid );

					if( $drupalVocabs ) {
						$service = new AdmCreateAutocompleteTermsService();
						$request = new AdmCreateAutocompleteTermsRequest();
						$request->Ticket = BizSession::getTicket();
						$request->TermEntity = $response->TermEntities[0];
						$request->Terms = $drupalVocabs;
						$service->execute( $request );
					}
				}
				$imported[$publishSystemId] = true;
			}
 		}
	}

	/**
	 * To delete a list of Term Entities and Terms belong to Drupal8 provider.
	 *
	 * @param string $publishSystemId Unique id of the publishing system. Use to bind the publishing storage.
	 */
	private function clearTermEntitiesAndTerms( $publishSystemId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';
		require_once dirname(__FILE__).'/../Utils.class.php'; // WW_Plugins_Drupal8_Utils.

		// Delete the Term Entities and all belonging Terms.
        $termEntities = DBAdmAutocompleteTermEntity::getTermEntityByProviderAndPublishSystemId(
            WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME, $publishSystemId );
		if( $termEntities ) {
			$service = new AdmDeleteAutocompleteTermEntitiesService();
			$request = new AdmDeleteAutocompleteTermEntitiesRequest();
			$request->Ticket = BizSession::getTicket();
			$request->TermEntities = $termEntities;
			$service->execute( $request );
		}
	}

	/**
	 * Repairs PublishFormTemplates for the Channels if needed.
	 *
	 * The way of handling templateNames has changed for Drupal from using the system_name as the unique name part
	 * of a DocumentId to using the original known by Drupal, therefore it might be necessary to update the old
	 * templates. This function takes care of that.
	 *
	 * @param int $pubChannelId The id of the current PubChannel being repaired.
	 * @return bool Whether or not the updates were succesful.
	 */
	public function repairPublishFormTemplates( $pubChannelId )
	{
		require_once dirname(__FILE__).'/../Utils.class.php'; // WW_Plugins_Drupal8_Utils.
		require_once dirname(__FILE__).'/../XmlRpcClient.class.php';
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php'; // PubPublishTarget.
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';

		$success = false;
		// The DocumentId of a PublishFormTemplate was formerly based on the system_name of the Drupal ContentType, however
		// since administrators in Drupal have the freedom to change the system_name, we needed to update the identifier
		// used in the DocumentId of the PublishFormTemplate, we use the original field from the ContentType for this.
		// This means that we must possibly update any older imported templates before passing them to the core.
		// Update rules:
		// If the smart_config flag is set for the channel, we do not run the updates.
		// If a PublishFormTemplate is known by its old 'type' DocumentId it needs to be updated to one using the 'original'.
		// If a PublishFormTemplate is known by the new type we don't need to do anything.
		// If a PublishFormTemplate is not known, we assume it is a new PublishFormTemplate.
		$channelUpdated = 'Drupal8_' . $pubChannelId . '_documentids_updated';
		$channelIsUpdated = DBConfig::getValue( $channelUpdated );

		if ( !is_null( $channelIsUpdated ) ) {
			$success = true;
		} else {
			$publishTarget = new PubPublishTarget( $pubChannelId );
			$drupalXmlRpcClient = new WW_Plugins_Drupal8_XmlRpcClient( $publishTarget );

			$contentTypes = $drupalXmlRpcClient->getContentTypes();

			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
			require_once BASEDIR . '/server/bizclasses/BizAdmActionProperty.class.php';
			$userName = BizSession::getShortUserName();
			if ( $contentTypes ) foreach ( $contentTypes as $contentType ) {
				// Check if we already have a ContentType matching the new format.
				$newTypedDocumentId = WW_Plugins_Drupal8_Utils::convertContentType2DocumentId( $pubChannelId, $contentType['original'] );
				$area = null;
				$objectId = BizObject::getObjectIdByDocumentId( $newTypedDocumentId, $area );

				if (is_null( $objectId ) ) {
					// Newly typed object does not yet exist, check if we already know the Object under the old DocumentId.
					$oldTypedDocumentId = WW_Plugins_Drupal8_Utils::convertContentType2DocumentId( $pubChannelId, $contentType['type'] );
					$objectId = BizObject::getObjectIdByDocumentId( $oldTypedDocumentId, $area );

					if ( !is_null( $objectId ) ) {

						// Update the Object with the new DocumentId if it is a workflow object.
						if ($area == 'Workflow') {
							$publishFormTemplateObject = BizObject::getObject($objectId, $userName, true, 'none', array());

							// Update the Original PublishFormTemplateObject with the newly aqcuired DocumentId.
							$publishFormTemplateObject->MetaData->BasicMetaData->DocumentID = $newTypedDocumentId;
							$success = BizObject::saveObject($publishFormTemplateObject, $userName, true, true);
						} else {
							// Area is trash, so we need a workaround, first save the new value.
							$success = DBObject::updateRowValues( $objectId, array( 'documentid' => $newTypedDocumentId ), 'Trash' );

							if ($success) {
								// Get the Object and update the search indices.
								$publishFormTemplateObject = BizObject::getObject( $objectId, $userName, false, 'none',
									array(), null, false, array('Trash'));

								// Update search index for the Template in the Trash:
								if ( $publishFormTemplateObject ) {
									BizSearch::updateObjects( array( $publishFormTemplateObject ), true, array('Trash') );
								}
							}
						}

						// Log a message for informative purposes.
						if (!$success) {
							LogHandler::Log(__CLASS__ . '::' . __FUNCTION__, 'INFO', 'PublishFormTemplate: ' . $objectId . ' '
								. 'could not be updated with the new DocumentId.');
						} else {
							// Remove the Dialog for the old typed value.
							$action = 'SetPublishProperties';
							$usages = BizAdmActionProperty::getAdmPropertyUsages($action, $oldTypedDocumentId);

							// If there are usages for the old documentId, remove it as well and let the process later recreate them.
							if ( count( $usages ) > 0 ) {
								if (!BizAdmActionProperty::deleteAdmPropertyUsageByActionAndDocumentId($action, $oldTypedDocumentId)) {
									LogHandler::Log('BizPublishing', 'ERROR', 'Removing existing dialog failed for documentID: '
										. $oldTypedDocumentId . '. Please remove it manually.');
								}
							}

							LogHandler::Log(__CLASS__ . '::' . __FUNCTION__, 'INFO', 'PublishFormTemplate: ' . $objectId . ' '
								. 'updated successfully.');
						}
					}
				}
			}

			// Update the smart_config table to mark the Channel as been completely searched.
			DBConfig::storeValue($channelUpdated, '1');
			$success = true;
		}
		return $success;
	}

	/**
	 * Queries the Enterprise DB for PublishTemplate objects. For that it uses 
	 * the built-in "PublishFormTemplates" Named Query.
	 *
	 * @param int $pubChannelId
	 * @return WflNamedQueryResponse
	 */
	private function queryTemplatesFromDb( $pubChannelId )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
		
		$service = new WflNamedQueryService();
		$req = new WflNamedQueryRequest();
		$req->Ticket = BizSession::getTicket();
		$req->User   = BizSession::getShortUserName();
		$req->Query  = 'PublishFormTemplates';
		
		$queryParam = new QueryParam();
		$queryParam->Property = 'PubChannelId';
		$queryParam->Operation = '=';
		$queryParam->Value = $pubChannelId;
		$req->Params = array( $queryParam );
		
		$resp = $service->execute( $req );
		
		return $resp;
	}

	/**
	 * Collect the error messages accumulated during the definitions import.
	 *
	 * @return array
	 */
	private function getErrorMessages()
	{
		$customObjPropErrors = array();
		BizServerPlugin::runDefaultConnector( 'Drupal8', 'CustomObjectMetaData', null,
			'getErrors', array(), $customObjPropErrors );

		$dialogAndTemplateErrors = array();
		BizServerPlugin::runDefaultConnector( 'Drupal8', 'PubPublishing', null,
			'getErrors', array(), $dialogAndTemplateErrors );

		$errorMessages = array_merge( $customObjPropErrors, $dialogAndTemplateErrors );

		return $errorMessages;
	}

	/**
	 * List of stylesheet files (urls) to include in the HTML page.
	 *
	 * @return array of strings (css include urls)
	 */
	public function getStyleSheetIncludes()
	{
		return array( 'webapps/Drupal8.css' );
	}

}