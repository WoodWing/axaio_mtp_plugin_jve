<?php
/**
 * Publishing Utils class
 *
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
class WW_Utils_PublishingUtils
{
	/**
	 * Generates a Dialog for the supplied PropertyInfo objects
	 *
	 * Creates a new Dialog and places the PropertyInfo objects on the dialog. if a default PropertyUsage is supplied
	 * those settings will be used for all created widgets on the Dialog. All PropertyInfo objects are used to create
	 * widgets on the proper tabs, and the first widget of a tab is set to have the DefaultFocus.
	 *
	 * @static
	 * @param PropertyInfo[] $propertyInfos An array of PropertyInfos to be added to the Dialog.
	 * @param string $title The title of the Dialog.
	 * @param null|PropertyUsage $defaultPropertyUsage The propertyUsage to be used for all widgets.
	 * @return Dialog The geneated Dialog.
	 */
	public static function generateDialogForPropertyInfos(array $propertyInfos,  $title='', $defaultPropertyUsage=null )
	{
		// Every dialog property is represented by a PropertyUsage
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';

		// Get a default dialog.
		$publishingDialog = self::getDefaultPublishingDialog( $title );
		$publishingDialog->Tabs[0]->Widgets = array();
		$tabs = array('' => $publishingDialog->Tabs[0]);

		// Add the properties.
		/** @var PropertyInfo $propertyInfo */
		if (count($propertyInfos) > 0 ) foreach ($propertyInfos as $propertyInfo) {
			$propertyInfo->AdminUI = (empty($propertyInfo->AdminUI)) ? false : $propertyInfo->AdminUI;

			// Determine the PropertyUsage.
			if (is_null($defaultPropertyUsage)) {
				$propertyUsage = new PropertyUsage();
				$propertyUsage->Name            = $propertyInfo->Name;
				$propertyUsage->Editable        = true;
				$propertyUsage->Mandatory       = false;
				$propertyUsage->Restricted      = false;
				$propertyUsage->RefreshOnChange = false;
			} else {
				$propertyUsage = $defaultPropertyUsage;
			}

			// Create a widget.
			$dialogWidget = new DialogWidget();
			$dialogWidget->PropertyInfo = $propertyInfo;
			$dialogWidget->PropertyUsage = $propertyUsage;

			// If the tab for the widget does not yet exist, create it prior to adding the widget.
			if (!empty($propertyInfo->Category) && !isset($tabs[$propertyInfo->Category])) {
				$dialogTab = new DialogTab();
				$dialogTab->Title = $propertyInfo->Category;
				// Set the first widget as getting the DefaultFocus;
				$dialogTab->DefaultFocus = $propertyInfo->Name;
				$tabs[$propertyInfo->Category] = $dialogTab;
				$tabs[$propertyInfo->Category]->Widgets = array();
			}

			// Add the Widget to the tab.
			$tabs[$propertyInfo->Category]->Widgets[] = $dialogWidget;

			// Set the DefaultFocus.
			if (count($tabs[$propertyInfo->Category]->Widgets) == 1) {
				$tabs[$propertyInfo->Category]->DefaultFocus = $propertyInfo->Name;
			}
		}

		// Add the tabs to the dialog.
		$publishingDialog->Tabs = $tabs;

		return $publishingDialog;
	}

	/**
	 * Returns a complete Dialog object with the given title. The first tab is already added.
	 * When the publish connector wants to add block to the UI in Content Station, other tabs
	 * need to be added after this tab.
	 *
	 * @param string $title
	 * @param string $firstTabTitle
	 * @return Dialog
	 */
	public static function getDefaultPublishingDialog( $title = '', $firstTabTitle = '' )
	{
		$dialog = new Dialog();
		$dialog->Title = $title;
		$dialog->Tabs = array( self::getPublishingTab( $firstTabTitle ) );
		$dialog->MetaData = array();

		return $dialog;
	}

	/**
	 * Returns a DialogTab object with the given title
	 *
	 * @param string $title
	 * @return DialogTab
	 */
	public static function getPublishingTab( $title = '' )
	{
		$dialogTab = new DialogTab();
		$dialogTab->Title = $title;
		$dialogTab->Widgets = array();
		$dialogTab->DefaultFocus = '';

		return $dialogTab;
	}

	/**
	 * Returns Publish Form Template Object.
	 * 
	 * @param integer $pubChannelId Publication Channel DB Id.
	 * @param string $formName The publishForm / publishFormTemplate name to be constructed.
	 * @param string $formDesc The publishForm / publishFormTemplate description to be constructed.
	 * @param string|null DocumentId for the publishForm.
	 * @return Object Publish Form Template object.
	 */
	public static function getPublishFormTemplateObj( $pubChannelId, $formName, $formDesc, $documentId=null )
	{
		require_once BASEDIR .'/server/dbclasses/DBSection.class.php';
		require_once BASEDIR .'/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR .'/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR .'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR .'/server/utils/ResolveBrandSetup.class.php';
		require_once BASEDIR .'/server/bizclasses/BizAdmStatus.class.php';
	
		$formObjType = 'PublishFormTemplate';
		$basicMD = new BasicMetaData();
		
		// Publication
		$brandSetup = new WW_Utils_ResolveBrandSetup();
		$brandSetup->resolveBrand( $pubChannelId );
		$publicationObj = $brandSetup->getPublication();		
		$templatePub = $publicationObj;
		
		// PubChannel
		$pubChannelInfo = $brandSetup->getPubChannelInfo();

		// Didn't check for access rights.
		$sections = DBSection::listPublSectionDefs( $publicationObj->Id, array( 'id', 'section' ) );
		$firstSection = current( $sections ); // Currently getting the first category.
		$templateCategory = new Category();
		$templateCategory->Id = $firstSection['id'];
		$templateCategory->Name = $firstSection['section'];
		
		// Status
		$statuses = BizAdmStatus::getStatuses( $publicationObj->Id, 0, $formObjType );
		$templateState = null;
		foreach( $statuses as $status ) {
			if( $status->Type == $formObjType ) {
				$templateState = new State();
				$templateState->Id = $status->Id;
				$templateState->Name = $status->Name;
				break; // Found
			}
		}
		if( !isset( $templateState ) ) {
			LogHandler::Log('MultiChannelWebPublishing','ERROR',__METHOD__.': Cannot find the status for ' .
							'"'.$formObjType.'" object type, please define one in the web admin page.');
		}

		// MetaData properties
		$basicMD->Name = $formName;
		$basicMD->Type = $formObjType;
		$basicMD->Publication = $templatePub;
		$basicMD->Category = $templateCategory;
		if (!is_null($documentId)) {
			$basicMD->DocumentID = $documentId;
		}
		$rightsMD = new RightsMetaData();
		$sourceMD = new SourceMetaData();		
		$contentMD = new ContentMetaData();
		$contentMD->Description = $formDesc;
		$workflowMD = new WorkflowMetaData();
		$workflowMD->State = $templateState;
		$extraMD = new ExtraMetaData();

		// MetaData
		$templateMD = new MetaData();
		$templateMD->BasicMetaData = $basicMD;
		$templateMD->RightsMetaData = $rightsMD;
		$templateMD->SourceMetaData = $sourceMD;
		$templateMD->ContentMetaData = $contentMD;
		$templateMD->WorkflowMetaData = $workflowMD;
		$templateMD->ExtraMetaData = $extraMD;
		
		// Target
		$pubChannel = new PubChannel();
		$pubChannel->Id = $pubChannelInfo->Id;
		$pubChannel->Name = $pubChannelInfo->Name;
		$issuesObj = DBIssue::listChannelIssues( $pubChannelId );
		$issueObj = current( $issuesObj ); // just take the first issue
		$issue = new Issue();
		$issue->Id = $issueObj['id'];
		$issue->Name = $issueObj['name'];
		
		$templateTarget = new Target();
		$templateTarget->PubChannel = $pubChannel;
		$templateTarget->Issue = $issue;
		
		// Object (templates)
		$templateObj = new Object();
		$templateObj->MetaData = $templateMD;
		$templateObj->Targets = array( $templateTarget ); // When Targets are not given here, it will be implicitly added in the BizPublish.
		
		return $templateObj;
	}

	/**
	 * Retrieves the Publication from the Database.
	 *
	 * @static
	 * @param integer $publicationId The ID of the Publication to retrieve.
	 * @return AdmPublication|null The Publication object.
	 */
	public static function getAdmPublicationById($publicationId)
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'Publication' );
		require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';
		return DBAdmPublication::getPublicationObj( $publicationId, $typeMap );
	}

	/**
	 * Retrieves the Publication from the database by Channel Id.
	 *
	 * @static
	 * @param integer $publicationChannelId The PublicationChannelId for which to retrieve the Publication.
	 * @return AdmPublication|null The Adm Publication belonging to the Channel Id.
	 */
	public static function getAdmPublicationByChannelId($publicationChannelId)
	{
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		$channel = DBChannel::getChannel( $publicationChannelId );
		return (!is_null($channel)) ? self::getAdmPublicationById($channel['publicationid']) : null;
	}

	/**
	 * Retrieves the Publication Channel from the Database.
	 *
	 * @static
	 * @param integer $publicationChannelId The ID of the Publication Channel to retrieve.
	 * @return AdmPubChannel|null The Publication Channel object.
	 */
	public static function getAdmChannelById($publicationChannelId)
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		return DBAdmPubChannel::getPubChannelObj($publicationChannelId, $typeMap);
	}

	/**
	 * Retrieves the Issue from the Database.
	 *
	 * @static
	 * @param integer $issueId The ID of the Issue to retrieve.
	 * @return AdmIssue|null The Publication Channel object.
	 */
	public static function getAdmIssueById($issueId)
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
		return DBAdmIssue::getIssueObj( $issueId );
	}

	/**
	 * Determines the value(s) of a custom Adm property.
	 *
	 * @param object $object The Adm Object to retrieve a property for.
	 * @param string $propertyName Name of custom property to retrieve the value for.
	 * @param bool $multiVal Whether or not to return multiple values (array) if available.
	 * @return mixed The value(s) of the property. NULL when property was not found.
	 */
	public static function getAdmPropertyValue($object, $propertyName, $multiVal=false)
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		return BizAdmProperty::getCustomPropVal( $object->ExtraMetaData, $propertyName, $multiVal );
	}
}
