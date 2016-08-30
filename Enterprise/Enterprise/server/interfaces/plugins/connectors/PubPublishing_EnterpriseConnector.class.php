<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Interface definition class for server plug-in connectors that implement publishing to an 
 * external publishing system. All "abstract public function" needs to be implemented by the connector.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class PubPublishing_EnterpriseConnector extends DefaultConnector
{
	private $phaseId = null;
	private $operation = null;
	private $operationId = null;
	
	// - - - - - - - - - - - - - - - - - - PUBLISH OPERATIONS - - - - - - - - - - - - - - - - - - -

	/**
	 * Publishes a dossier with contained objects (articles. images, etc.) to an external publishing system.
	 * The plugin is supposed to publish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param Object $dossier         [writable]
	 * @param Object[] $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 * @return PubField[] containing information from publishing system
	 */	
	abstract public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget );
		
	/*	Example algorithm
		1. Publish the dossier and it's objects to the publishing system.
			a. Read $objectindossier->Files->Attachment[0]->Content to read the contents of the file
			b. Optional: convert the files to a format recognized by the publishing system
			c. Publish the files
			d. throw a BizException if the publishing fails.
		2. Fill in $dossier->ExternalId with a string uniquely identifying the dossier in the 
		   publishing system;
		3. Optional: For each $object in $objectsindossier: fill in $objectindossier->ExternalId 
		   with an unique id identifying the object.
		4. Return array of Field's about the dossier in the publishing system, for example:
			numviews, rating, numraters, numcomments, url
	*/

	/**
	 * BEFORE and AFTER the publishDossier() operation, the publish connector is called
	 * to enable initializing and cleanup its data.
	 * When the connector implements multiple phases, this function is called for each phase.
	 * In case the user aborts the operation, publishAbort() is called to let connector cleanup.
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 */
	public function publishBefore( $publishTarget ) {}
	public function publishAfter( $publishTarget )  {}
	public function publishAbort( $publishTarget )  {}

	/**
	 * Updates/republishes a published dossier with contained objects (articles. images, etc.) to an 
	 * external publishing system, using the $dossier->ExternalId to identify the dosier to the 
	 * publishing system. The plugin is supposed to update/republish the dossier and it's articles 
	 * and fill in some fields for reference.
	 *
	 * @param Object $dossier            [writable]
	 * @param Object[] $objectsInDossier [writable]
	 * @param PubPublishTarget $publishTarget
	 * @return PubField[] containing information from publishing system
	 */	
	abstract public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget );
	/*	Example algorithm
		1. Update/repblish the dossier and it's objects to the publishing system, identifying the 
		   data to be updated with $dossier->ExternalId.
			a. Read $objectindossier->Files->Attachment[0]->Content to read the contents of the file
			b. Optional: convert the files to a format recognized by the publishing system
			c. Publish the files
			d. throw a BizException if the updating/republishing fails.
		2. Optional: For each $object in $objectsindossier: use $objectindossier->ExternalId.
		3. If changed: Fill in $dossier->ExternalId with a string uniquely identifying the dossier 
		   in the publishing system, this is only needed if this id has changed;
		4. Optional: For each $object in $objectsindossier: fill in $objectindossier->ExternalId with 
		   an unique id identifying the object, this is only needed if these id's have changed.
		5. Return array of Fields with data about the dossier in the publishing system, for example:
			numviews, rating, numraters, numcomments, url
	*/
	
	/**
	 * BEFORE and AFTER the updateDossier() operation, the publish connector is called
	 * to enable initializing and cleanup its data.
	 * When the connector implements multiple phases, this function is called for each phase.
	 * In case the user aborts the operation, updateAbort() is called to let connector cleanup.
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 */
	public function updateBefore( $publishTarget ) {}
	public function updateAfter( $publishTarget )  {}
	public function updateAbort( $publishTarget )  {}
	
	/**
	 * Removes/unpublishes a published dossier from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier            [writable]
	 * @param Object[] $objectsInDossier [writable]
	 * @param PubPublishTarget $publishTarget
	 * @return PubField[] containing information from publishing system
	 */	
	abstract public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget );
	/*	Example algorithm
		1. Remove the dossier and it's objects from the publishing system, identifying the data to 
		   be updated with $dossier->ExternalId.
			a. Unpublish the dossier
			b. throw a BizException if the updating/republishing fails.
		2. Optional: For each $object in $objectsindossier: use $objectindossier->ExternalId.
		3. Return array of Fields with data about the dossier in the publishing system, for example:
			numviews, rating, numraters, numcomments, url
	*/

	/**
	 * BEFORE and AFTER the unpublishDossier() operation, the publish connector is called
	 * to enable initializing and cleanup its data.
	 * When the connector implements multiple phases, this function is called for each phase.
	 * In case the user aborts the operation, unpublishAbort() is called to let connector cleanup.
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 */
	public function unpublishBefore( $publishTarget ) {}
	public function unpublishAfter( $publishTarget )  {}
	public function unpublishAbort( $publishTarget )  {}

	/**
	 * Requests fieldvalues from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * 
	 * @return array of PubField containing information from publishing system
	 */	
	abstract public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
	/*	Example algorithm
		1. Query fields from the publishing system, identifying the data with $dossier->ExternalId.
			a. query fieldvalues by looping through the fieldnames.
			b. throw a BizException if the request fails.
		2. Optional: For each $object in $objectsindossier: use $objectindossier->ExternalId.
		3. Return array of Fields.
	*/

	/**
	 * Requests dossier URL from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * 
	 * @return string URL to published item
	 */	
	abstract public function getDossierURL( $dossier, $objectsInDossier, $publishTarget );

	/**
	 * Previews a dossier with contained objects (articles. images, etc.) to an external publishing 
	 * system. The plugin is supposed to send the dossier and it's articles to the publishing system 
	 * and fill in the URL field for reference.
	 *
	 * @param Object $dossier            [writable]
	 * @param Object[] $objectsInDossier [writable]
	 * @param PubPublishTarget $publishTarget
	 * 
	 * @return array of Fields containing information from Publishing system
	 */	
	abstract public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget );
	/*	Example algorithm same as publish dossier
	*/
	
	/**
	 * BEFORE and AFTER the previewDossier() operation, the publish connector is called
	 * to enable initializing and cleanup its data.
	 * When the connector implements multiple phases, this function is called for each phase.
	 * In case the user aborts the operation, abortPreview() is called to let connector cleanup.
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 */
	public function previewBefore( $publishTarget ) {}
	public function previewAfter( $publishTarget )  {}
	public function previewAbort( $publishTarget )  {}

	// - - - - - - - - - - - - - - - - - - PUBLISH ISSUE - - - - - - - - - - - - - - - - - - - -

	/**
	 * Allows connector to provide published issue properties, such as  Fields, Report, DossierOrder, etc 
	 * of the processed magazine. The core will store the returned data into the DB.
	 * Affective for publishDossiers(), updateDossiers(), unpublishDossiers() and previewDossiers().
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 * @return PubPublishedIssue|null When NULL returned, the core skips the DB update.
	 */
	public function getPublishInfoForIssue( $publishTarget )
	{
		return null;
	}

	/**
	 * Allows connector to act on changes to published issue properties. To get full control
	 * of the issue being changed, the original published issue ($orgIssue) is provided.
	 * The passed $newIssue contains only thonse properties that actually are different from $orgIssue.
	 * The connector may adjust $newIssue Fields property when needed. The core server merges both and updates the DB after.
	 *
	 * @since 7.5
	 * @param AdmIssue $admIssue The configured admin issue properties.
	 * @param PubPublishedIssue $orgIssue The latest properties, just read from DB.
	 * @param PubPublishedIssue $newIssue The properties that are about to get changed.
     * @return string|null A report can be returned. The report is not saved in the database when returned but only send back in the PubSetPublishInfoResponse
	 */
	public function setPublishInfoForIssue( $admIssue, $orgIssue, $newIssue )
	{
		return null;
	}

	// - - - - - - - - - - - - - - - - - - PUBLISH DETAILS - - - - - - - - - - - - - - - - - - - -
	
	/**
	 * This function is called when a dossier is about to be removed that is published.
	 * Default this function returns false so a published dossier to the plugin cannot be removed. 
	 * This function can be overridden by a implementation of a plugin so a dossier can be removed 
	 * when published. (e.g. SMS channels)
	 *
	 * @return boolean false if not allowed, true if allowed
	 */
	public function allowRemoveDossierWhenPublished()
	{
		return false;
	}
	
	/**
	 * This function is called when the GetDossier is called for PublishDossier, UnPublishDossier 
	 * or UpdateDossier. In this function an array of arrays can be created as:
	 * array( 'errors' => array(), 'warnings' => array(), 'info' => array());
	 * Errors stop the publishing of the object. Warnings are shown to the user, but a user is still 
	 * allowed to publish. Infos are just informational strings.
	 *
	 * @param string $type the type of the validation. PublishDossier, UnPublishDossier or UpdateDossier
	 * @param int $dossierId The id of the dossier to publish
	 * @param int $issueId The id of the issue to publish in
	 * @return array
	 */
	public function validateDossierForPublishing( $type, $dossierId, $issueId )
	{
		return array('errors' => array(), 'warnings' => array(), 'infos' => array());
	}
	
	/**
	 * Allows connector to provide other Publish System name than the display name of the server plug-in
	 * which is taken by default. This is name is shown at admin pages, such as the Channel Maintenance 
	 * page. This function is NOT abstract since it is introduced later. When function is not implemented 
	 * by the connector or when an empty string is returned, the server plug-in name is taken instead.
	 *
	 * @return string
	 */
	public function getPublishSystemDisplayName() 
	{
		return '';
	}

	/**
	 * Get the correct rendition for a to publish object.
	 *
	 * @param Object $object
	 * @return string The file rendition type.
	 */
	protected function askRenditionType( $object )
	{
		$rendition = null;
		switch ($object->MetaData->BasicMetaData->Type) {
			case 'Image':
				$rendition = 'preview';
				break;
			default:
				$rendition = 'native';
				break;
		}
		return $rendition;
	}

	/**
	 * Retrieves the file contents from the Object.
	 *
	 * @param Object $object The object to get the File Contents from.
	 * @param string $rendition The rendition.
	 * @return null|string The retrieved file or null if not found.
	 */
	protected function getFileContent( $object, $rendition )
	{
		if ($rendition) {
			$objectid = $object->MetaData->BasicMetaData->ID;
			$user = BizSession::getShortUserName();
			$tempobject = BizObject::getObject($objectid, $user, false, $rendition);
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$content = $transferServer->getContent($tempobject->Files[0]);
			return $content;
		} else {
			return null;
		}
	}

	// - - - - - - - - - - - - - - - - - - PUBLISH FIELDS FILTERING - - - - - - - - - - - - - - - - - - - -

	/**
	 * Allows connector to filter out fields that needs to be stored in the database.
	 * By default, no fields are stored. The function should simply return the key names of the
	 * fields. The core checks if those keys are available and includes those into the database.
	 *
	 * @return array|null List of PubField keys. NULL to include all fields.
	 */
	public function getPublishIssueFieldsForDB()   { return array(); }
	public function getPublishDossierFieldsForDB() { return array(); }

	/**
	 * Allows connector to filter out fields that needs to be returned through web services.
	 * By default, all fields are returned. The function should simply return the key names of the
	 * fields. The core checks if those keys are available and includes those in the responses.
	 *
	 * @return array|null List of PubField keys. NULL to include all fields.
	 */
	public function getPublishIssueFieldsForWebServices()   { return null; }
	public function getPublishDossierFieldsForWebServices() { return null; }
	
	/**
	 * Allows connector to filter out fields that needs to be Ncasted (broadcasted/multicasted).
	 * By default, no fields are Ncasted. The function should simply return the key names of the
	 * fields. The core checks if those keys are available and includes those in the Ncasting.
	 *
	 * @return array|null List of PubField keys. NULL to include all fields.
	 */
	public function getPublishIssueFieldsForNcasting()   { return array(); }
	public function getPublishDossierFieldsForNcasting() { return array(); }

	// - - - - - - - - - - - - - - - - - - DOSSIER ORDERING - - - - - - - - - - - - - - - - - - - -

	/**
	 * Allows connector to have its own implementation to retrieve the dossier order of a magazine.
	 * When it does, TRUE should be returned to inform the core server that the default
	 * implementation is no longer needed.
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget Identification of the magazine.
	 * @return boolean FALSE for default/core behavior, or TRUE for custom/plugin behavior.
	 */
	public function getDossierOrder( $publishTarget ) 
	{
		return null;
	}

	/**
	 * Allows connector to have its own implementation to update the dossier order of a magazine.
	 * When it does, TRUE should be returned to inform the core server that the default
	 * implementation is no longer needed.
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget Identification of the magazine.
	 * @param array $newOrder
	 * @param array $originalOrder
	 * @return boolean FALSE for default/core behavior, or TRUE for custom/plugin behavior.
	 */
	public function updateDossierOrder( $publishTarget, $newOrder, $originalOrder ) 
	{
		return null;
	}

	// - - - - - - - - - - - - - - - - - - OPERATION PHASES - - - - - - - - - - - - - - - - - - - -

	/**
	 * getPreviewPhases / getPublishPhases / getUpdatePhases / getUnpublishPhases
	 *
	 * Operations are the logical actions done by end users. Phases are the steps needed
	 * by the connector to implement those operations. Depending on the publication channel
	 * and algorithm of the connector, the phases can differ for each operation.
	 *
	 * These 4 functions allow a connector to overrule and have multiple phases for an operation,
	 * or to have a different (or more explicit) name for an operation. There could be a need
	 * to have multiple phases; For exanple, the 'preview' operation could do an 'export' to have
	 * all content collected before it can compress all into on archive file (zip) an do an 'upload' 
	 * of that to the publishing server. Both actions are time consuming since there are many bytes 
	 * to transfer, and therefore the 'preview' operation can be split-up into two phases 'export' 
	 * and 'upload'. Doing so, the connector's previewDossier() function gets called twice by the core 
	 * server. The connector can use getPhase() to find out the current operation phase (as set by 
	 * the core). The first time calling, it returns 'export', and the second time 'upload'.
	 *
	 * Built-in operation ids are 'preview', 'publish', 'update' and 'unpublish'.
	 * Built-in phase ids are 'extract', 'export', 'compress', 'upload' and 'cleanup'.
	 * Operation ids can be used for phase ids as well. By using one of the built-in ids, the
	 * core server provides localization. However, connectors are free to introduce their own ids
	 * but then there is a need to provide localizations too.
	 *
	 * When 'preview' needs one phase it can be just 'preview'. In that case nothing is needed
	 * since this is the default implementation of this connector interface.
	 * When it actually does a server side 'export', and it is wanted this phase name shown in the UI
	 * aside to the progress bar, the connector can implement its own function which does:
	 *    return array( 'export' => null );
	 * When the 'preview' operation needs two phases, it can implement as follows:
	 *    return array( 'export' => null, 'upload' => null );
	 * When there is need for a custom phase/operation name, it can implement as follows:
	 *    return array( 'preview' => 'Please wait while generating a wonderful preview for you...' );
	 * 
	 * @since 7.5
	 * @return array Keys are phase ids (such as 'export'). Values are localized names.
	 */
	public function getPreviewPhases()
	{
		return array( 'preview' => null );
	}
	public function getPublishPhases()
	{
		return array( 'publish' => null );
	}
	public function getUpdatePhases()
	{
		return array( 'update' => null );
	}
	public function getUnpublishPhases()
	{
		return array( 'unpublish' => null );
	}
	
	/**
	 * Called by core server before calling previewDossier(), publishDossier(), updateDossier()
	 * or unpublishDossier(). See above for more details.
	 *
	 * @since 7.5
	 * @param string $phaseId
	 */
	public function setPhase( $phaseId )
	{
		$this->phaseId = $phaseId;
	}
	
	/**
	 * Can be called by connectors to find out the actualy operation phase when its previewDossier(), 
	 * publishDossier(), updateDossier() or unpublishDossier() functions are called. See above for more details.
	 *
	 * @since 7.5
	 * @return string Phase id
	 */
	public function getPhase()
	{
		return $this->phaseId;
	}

	/**
	 * Called by core server before calling previewDossier(), publishDossier(), updateDossier()
	 * or unpublishDossier(). See above for more details.
	 *
	 * @since 7.5
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 */
	public function setOperation( $operation, $operationId )
	{
		$this->operation = $operation;
		$this->operationId = $operationId;
	}
	
	/**
	 * Can be called by connectors to find out the actualy operation id when its previewDossier(), 
	 * publishDossier(), updateDossier() or unpublishDossier() functions are called. See above for more details.
	 *
	 * @since 7.5
	 * @return string Operation id
	 */
	public function getOperationId()
	{
		return $this->operationId;
	}

	/**
	 * Can be called by connectors to find out the actualy operation name when its previewDossier(), 
	 * publishDossier(), updateDossier() or unpublishDossier() functions are called. See above for more details.
	 *
	 * @since 7.5
	 * @return string Operation name: Preview, Publish, Update or UnPublish
	 */
	public function getOperation()
	{
		return $this->operation;
	}

	/**
	 * Called by core server before/after calling previewDossier(), publishDossier(), updateDossier()
	 * or unpublishDossier(). This is called only ones (no matter when there are multiple phases).
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Publish, Update, UnPublish or Preview
	 */
	public function beforeOperation( $publishTarget, $operation ) {}
	public function afterOperation ( $publishTarget, $operation ) {}

	// - - - - - - - - - - - - - - - - - - PARALLEL UPLOAD - - - - - - - - - - - - - - - - - - - -

	/**
	 * Tells whether the publishing connector supports the parallel upload feature. This 
	 * means it can upload multiple dossiers at the same time to the remote publishing system.
	 * The connector might implement this feature with help of the WW_UtilsHttpClientMultiCurl 
	 * class which is based on the multi-curl technology. See publishDossiersParallel function
	 * for more details about the parallel upload feature.
	 *
	 * @since 7.6.7
	 * @param string $phase The phase of the publishing that will determine if it supports parallel upload.
	 * @return bool TRUE when the connector can handle parallel publishing. FALSE when only handle one publishing at a time.
	 */
	public function canHandleParallelUpload( $phase )
	{
		return false;
	}
	
	/**
	 * Called by the core server during the upload phase when the connector has indicated
	 * that it can handle parallel uploads (through the canHandleParallelUpload function).
	 * Unlike the serial solution, the core server does not loop through the dossiers
	 * by itself when it comes to the parallel solution. The connector should iterate
	 * and callback the core server when to fire the next request (calling publishDossier).
	 * Then the connector should call processNextDossierCB which accepts a connection id
	 * as parameter. This callback returns TRUE as long as there are more dossiers to
	 * get published. When any (other) response from the remote publishing system arrives
	 * the connector should callback the core server through processedDossierCB. This function
	 * accepts a dossier id and publishFields parameter of the dossier being published.
	 *
	 * @since 7.6.7
	 * @param array $processNextDossierCB Callback function to fire the next request.
	 * @param array $processedDossierCB Callback function to store an arrived response.
	 */
	public function publishDossiersParallel( $processNextDossierCB, $processedDossierCB )
	{
	}

	/**
	 * The function tells whether the plugin ( Channel specific ) supports Publish Form feature.
	 *
	 * @since 9.0
	 * @return boolean True when the channel plugin supports PublishForm feature; False(Default) otherwise.
	 */
	public function doesSupportPublishForms()
	{
		return false;
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
		return null;
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
		return null;
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
		return $defaultButtonBar;
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
		return false;
	}

	/**
	 * Returns a list of the supported output image file formats (in their MIME format).
	 *
	 * @since 10.1
	 * @return array
	 */
	public function getFileFormatsForOutputImage()
	{
		return array( 'image/jpeg' );
	}

	/**
	 * Returns the DPI for the output image based on the image metadata.
	 *
	 * @since 10.1
	 * @return double
	 */
	public function getDpiForOutputImage()
	{
		return 72.0;
	}
	
	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
