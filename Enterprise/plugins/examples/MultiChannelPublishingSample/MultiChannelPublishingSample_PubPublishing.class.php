<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Sample that shows a server plug-in returning various templates.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';

class MultiChannelPublishingSample_PubPublishing extends PubPublishing_EnterpriseConnector
{
	// A plugin should provide a unique site ID.
	const DOCUMENT_PREFIX = 'sample';
	const SITE_ID = '0';

	/**
	 * Returns the plugins priority.
	 *
	 * @return mixed The priority.
	 */
	final public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function doesSupportPublishForms()
	{
		return true; // Supports Publish Forms feature.
	}

	/**
	 * Refer to PubPublishing_EnterpriseConnector::getPublishFormTemplates() header.
	 */
	public function getPublishFormTemplates( $pubChannelId )
	{
		// Create the templates.
		$templatesObj = array();
		$documentIdPrefix = self::DOCUMENT_PREFIX . '_' . self::SITE_ID . '_';
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';

		// Recipe Sample Template.
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId,'Recipe Sample Template',
			'Sample publishing template for recipe form.',
			$documentIdPrefix . '0'
		);

		// News Sample Template.
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId,'News Sample Template',
			'Sample commenting template for news.',
			$documentIdPrefix . '1'
		);

		// News Sample Template.
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId,'All Widgets Sample Template',
			'All widgets template. This template returns all the widgets that are available.',
			$documentIdPrefix . '2'
		);

		// News Sample Template.
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId,'File Selector Template',
			'Sample File Selector Template.',
			$documentIdPrefix . '3'
		);

		// Fileselector template.
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId,'Article Component Selector Template',
			'Sample Article Component Selector Template.',
			$documentIdPrefix . '4'
		);

		// No Preview button template
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId,'No preview and update button',
			'The preview and update buttons are not available when using this template.',
			$documentIdPrefix . '5'
		);

		// Disabled preview and update button
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId,'Disabled preview and update button',
			'The update and preview buttons are disabled when using this template.',
			$documentIdPrefix . '6'
		);

		// Widget in widget sample Template.
		$templatesObj[] = WW_Utils_PublishingUtils::getPublishFormTemplateObj(
			$pubChannelId,'Widget in widget Template',
			'Widget in widget Template.',
			$documentIdPrefix . '7'
		);


		return $templatesObj;
	}
	
	/**
	 * This function can return a dialog that is shown in Content Station. This is used for the Multi Channel Publishing Feature.
	 *
	 * @since 9.0
	 * @param Object $publishForm
	 * @param Object $publishFormTemplate
	 * @return Dialog|null Dialog definition|The default connector returns null which indicates it doesn't support the getDialog call.
	 */
	public function getDialogForSetPublishPropertiesAction( $publishFormTemplate )
	{
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';

		$dialog = WW_Utils_PublishingUtils::getDefaultPublishingDialog( $publishFormTemplate->MetaData->BasicMetaData->DocumentID, 'GeneralFields' );
		$tab = reset($dialog->Tabs);

		// Create / Add widgets.
		switch ( $publishFormTemplate->MetaData->BasicMetaData->DocumentID) {
			case 'sample_0_0' :
				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_RECTITLE';
				$widget->PropertyInfo->DisplayName          = 'Title';
				$widget->PropertyInfo->Type                 = 'string';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_TEASERTXT';
				$widget->PropertyInfo->DisplayName          = 'TeaserText';
				$widget->PropertyInfo->Type                 = 'multiline';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_SERVINGS';
				$widget->PropertyInfo->DisplayName          = 'Servings';
				$widget->PropertyInfo->Type                 = 'int';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_PREPTIME';
				$widget->PropertyInfo->DisplayName          = 'PrepTime';
				$widget->PropertyInfo->Type                 = 'int';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_TOTALTIME';
				$widget->PropertyInfo->DisplayName          = 'TotalTime';
				$widget->PropertyInfo->Type                 = 'int';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_CUISIN';
				$widget->PropertyInfo->DisplayName          = 'Cuisin';
				$widget->PropertyInfo->Type                 = 'list';
				$widget->PropertyInfo->DefaultValue         = '3';
				$widget->PropertyInfo->ValueList            = array('2'=>'Indian','3'=>'Japanese','5'=>'Western');
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_MAININGREDIENTS';
				$widget->PropertyInfo->DisplayName          = 'Main Ingredients';
				$widget->PropertyInfo->Type                 = 'multilist';
				$widget->PropertyInfo->DefaultValue         = '3';
				$widget->PropertyInfo->ValueList            = array('2'=>'Apple','3'=>'Sour Cream','5'=>'Tomato');
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_INGREDIENTS';
				$widget->PropertyInfo->DisplayName          = 'Ingredients';
				$widget->PropertyInfo->Type                 = 'multiline';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_PREPARATION';
				$widget->PropertyInfo->DisplayName          = 'Preparation';
				$widget->PropertyInfo->Type                 = 'multiline';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_NUTRIINFO';
				$widget->PropertyInfo->DisplayName          = 'Nutritional Information';
				$widget->PropertyInfo->Type                 = 'multiline';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				break;
			case 'sample_0_1' :
				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_NEWSTITLE';
				$widget->PropertyInfo->DisplayName          = 'Title';
				$widget->PropertyInfo->Type                 = 'string';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_HEADER';
				$widget->PropertyInfo->DisplayName          = 'Header';
				$widget->PropertyInfo->Type                 = 'multiline';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_BODY';
				$widget->PropertyInfo->DisplayName          = 'Body';
				$widget->PropertyInfo->Type                 = 'multiline';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				break;
			case 'sample_0_2' :
				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_STRING';
				$widget->PropertyInfo->DisplayName          = 'String';
				$widget->PropertyInfo->Type                 = 'string';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_MULTISTRING';
				$widget->PropertyInfo->DisplayName          = 'Multistring';
				$widget->PropertyInfo->Type                 = 'multistring';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_TOURISMCITIES';
				$widget->PropertyInfo->DisplayName          = 'Tourism Cities';
				$widget->PropertyInfo->Type                 = 'multistring';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyInfo->MaxLength            = 140;
				$widget->PropertyInfo->TermEntity           = 'City';
				$widget->PropertyInfo->SuggestionEntity     = 'City';
				$widget->PropertyInfo->AutocompleteProvider = 'MultiChannelPublishingSample';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_COUNTRIES';
				$widget->PropertyInfo->DisplayName          = 'Countries';
				$widget->PropertyInfo->Type                 = 'multistring';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyInfo->MaxLength            = 140;
				$widget->PropertyInfo->TermEntity           = 'Country';
				$widget->PropertyInfo->SuggestionEntity     = 'Country';
				//$widget->PropertyInfo->AutocompleteProvider = 'MultiChannelPublishingSample'; Don't set this on purpose, let the server to resolve this.
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_MULTILINE';
				$widget->PropertyInfo->DisplayName          = 'Multiline';
				$widget->PropertyInfo->Type                 = 'multiline';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyInfo->MaxLength            = 140;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_BOOL';
				$widget->PropertyInfo->DisplayName          = 'Bool';
				$widget->PropertyInfo->Type                 = 'bool';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_INT';
				$widget->PropertyInfo->DisplayName          = 'Int';
				$widget->PropertyInfo->Type                 = 'int';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_DOUBLE';
				$widget->PropertyInfo->DisplayName          = 'Double';
				$widget->PropertyInfo->Type                 = 'double';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_DATE';
				$widget->PropertyInfo->DisplayName          = 'Date';
				$widget->PropertyInfo->Type                 = 'date';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_DATETIME';
				$widget->PropertyInfo->DisplayName          = 'Datetime';
				$widget->PropertyInfo->Type                 = 'datetime';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_LIST';
				$widget->PropertyInfo->DisplayName          = 'List';
				$widget->PropertyInfo->Type                 = 'list';
				$widget->PropertyInfo->DefaultValue         = 'Option 1';
				$widget->PropertyInfo->ValueList            = array('Option 1','Option 2','Option 3');
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_MULTILIST';
				$widget->PropertyInfo->DisplayName          = 'Multilist';
				$widget->PropertyInfo->Type                 = 'multilist';
				$widget->PropertyInfo->DefaultValue         = 'Option a';
				$widget->PropertyInfo->ValueList            = array('Option a','Option b','Option c');
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				break;
			case 'sample_0_3' :

				// PropertyValues setting.
				$fileImagePropertyInfoPropValue1 = new PropertyValue('image/png', '.png', 'Format');
				$fileImagePropertyInfoPropValue2 = new PropertyValue('image/jpeg', '.jpg', 'Format');

				// Add FileSelector
				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_MAIN_IMAGE_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
				$fileWidget->PropertyInfo->DisplayName = 'Selected Image';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 1000000; // (2MB max upload)
				$fileWidget->PropertyInfo->MinResolution = '200x200'; // (w x h)
				$fileWidget->PropertyInfo->MaxResolution = '640x480'; // (w x h)
				$fileWidget->PropertyInfo->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
				$fileWidget->PropertyInfo->Widgets = null;
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_MAIN_IMAGE';
				$widget->PropertyInfo->DisplayName = 'Main Image - Select a File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 1;
				$widget->PropertyInfo->MaxValue = 1;
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				// Add FileSelector Head Image 1
				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_HEAD_IMAGE_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
				$fileWidget->PropertyInfo->DisplayName = 'Selected Image';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 1500000; // (2MB max upload)
				$fileWidget->PropertyInfo->MinResolution = '300x300'; // (w x h)
				$fileWidget->PropertyInfo->MaxResolution = '1024x768'; // (w x h)
				$fileWidget->PropertyInfo->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
				$fileWidget->PropertyInfo->Widgets = null;
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_HEAD_IMAGE_A';
				$widget->PropertyInfo->DisplayName = 'Heading Image #1 - Select a File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 1;
				$widget->PropertyInfo->MaxValue = 1;
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				// Add FileSelector Head Image 1
				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_HEAD_IMAGEA_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
				$fileWidget->PropertyInfo->DisplayName = 'Selected Image';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 2000000; // (2MB max upload)
				$fileWidget->PropertyInfo->MinResolution = '50x50'; // (w x h)
				$fileWidget->PropertyInfo->MaxResolution = '800x600'; // (w x h)
				$fileWidget->PropertyInfo->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
				$fileWidget->PropertyInfo->Widgets = null;
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_HEAD_IMAGE_B';
				$widget->PropertyInfo->DisplayName = 'Heading Image #2 - Select a File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 1;
				$widget->PropertyInfo->MaxValue = 1;
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				// Add FileSelector Head Image 1
				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_HEAD_IMAGEB_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
				$fileWidget->PropertyInfo->DisplayName = 'Selected Image';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 2000000; // (2MB max upload)
				$fileWidget->PropertyInfo->MinResolution = '50x50'; // (w x h)
				$fileWidget->PropertyInfo->MaxResolution = '800x800'; // (w x h)
				$fileWidget->PropertyInfo->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
				$fileWidget->PropertyInfo->Widgets = null;
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_HEAD_IMAGE_C';
				$widget->PropertyInfo->DisplayName = 'Heading Image #3 - Select a File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 1;
				$widget->PropertyInfo->MaxValue = 1;
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_MAIN_IMAGEB_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
				$fileWidget->PropertyInfo->DisplayName = 'Selected Image';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 1500000; // (2MB max upload)
				$fileWidget->PropertyInfo->MinResolution = '50x50'; // (w x h)
				$fileWidget->PropertyInfo->MaxResolution = '600x600'; // (w x h)
				$fileWidget->PropertyInfo->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
				$fileWidget->PropertyInfo->Widgets = null;
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				// FileSelector 2, first and only option.
				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_MAIN_IMAGE_B';
				$widget->PropertyInfo->DisplayName = 'Main Image #2 - Select a File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 1;
				$widget->PropertyInfo->MaxValue = 5;
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;

				$tab->Widgets[] = $widget;
				break;
			case 'sample_0_4' :
				// Article Component Selector.
				$articleComponentPropValue1 = new PropertyValue('application/incopyicml', '.wcml', 'Format');

				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_ART_COMPONENTA'; // Name for a File / ArticleComponent should be generated. (d_ ? )
				$fileWidget->PropertyInfo->DisplayName = 'Selected Text Component';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'articlecomponent';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = null; // (2MB max upload)
				$fileWidget->PropertyInfo->MinResolution = null; // (w x h)
				$fileWidget->PropertyInfo->MaxResolution = null; // (w x h)
				$fileWidget->PropertyInfo->PropertyValues = array($articleComponentPropValue1);
				$fileWidget->PropertyInfo->Widgets = null;
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0; // Set the initialHeight of the subwidget to not set.

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_BODYTEXT';
				$widget->PropertyInfo->DisplayName = 'Body Text';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'articlecomponentselector';
				$widget->PropertyInfo->MinValue = 1; // One component.
				$widget->PropertyInfo->MaxValue = 1; // One component.
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 150; // 10 lines at 15 pixels height each;
				$tab->Widgets[] = $widget;
				break;
			case 'sample_0_5':
			case 'sample_0_6':
				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name                 = 'C_MCPSAMPLE_STRING';
				$widget->PropertyInfo->DisplayName          = 'String';
				$widget->PropertyInfo->Type                 = 'string';
				$widget->PropertyInfo->DefaultValue         = '';
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;
				break;
			case 'sample_0_7':
				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_1FILE_FILE';
				$fileWidget->PropertyInfo->DisplayName = 'Selected Image';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file'; // Native but show thumb
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 2000000; // (2MB max upload)
				$fileWidget->PropertyInfo->PropertyValues = null;
				$fileWidget->PropertyInfo->Widgets = $this->getFileWidgets();
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_1FILE';
				$widget->PropertyInfo->DisplayName = 'Single file - Select a File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 1;
				$widget->PropertyInfo->MaxValue = 1;
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				// *** MultipleImages ***
				$fileImagePropertyInfoPropValue1 = new PropertyValue('image/png', '.png', 'Format');
				$fileImagePropertyInfoPropValue2 = new PropertyValue('image/jpeg', '.jpg', 'Format');

				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_MULTI_IMAGES_FILE';
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
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_MULTI_IMAGES';
				$widget->PropertyInfo->DisplayName = 'MultiImages - Select File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 0; // Empty allowed.
 				$widget->PropertyInfo->MaxValue = null; // Many images allowed.
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				// *** Multiple zip files ***
				$fileZipPropertyInfoPropValue1 = new PropertyValue('application/zip', '.zip', 'Format');
				$fileZipPropertyInfoPropValue2 = new PropertyValue('application/x-gzip', '.gz', 'Format');

				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_MULTI_ZIP_FILE';
				$fileWidget->PropertyInfo->DisplayName = 'Selected Zip';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 2000000; // (2MB max upload)
				$fileWidget->PropertyInfo->PropertyValues = array( $fileZipPropertyInfoPropValue1, $fileZipPropertyInfoPropValue2 );
				$fileWidget->PropertyInfo->Widgets = $this->getFileWidgets();
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_MULTI_ZIP';
				$widget->PropertyInfo->DisplayName = 'MultiZip - Select File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 0; // Empty allowed.
				$widget->PropertyInfo->MaxValue = null; // Many files allowed.
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;

				// *** 10 Images ***
				$fileImagePropertyInfoPropValue1 = new PropertyValue('image/png', '.png', 'Format');
				$fileImagePropertyInfoPropValue2 = new PropertyValue('image/jpeg', '.jpg', 'Format');

				$fileWidget = new DialogWidget();
				$fileWidget->PropertyInfo = new PropertyInfo();
				$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_10IMAGES_FILE';
				$fileWidget->PropertyInfo->DisplayName = 'Selected Image';
				$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
				$fileWidget->PropertyInfo->Type = 'file';
				$fileWidget->PropertyInfo->DefaultValue = null;
				$fileWidget->PropertyInfo->MaxLength = 2000000; // (2MB max upload)
				$fileWidget->PropertyInfo->MinResolution = '200x200'; // (w x h)
				$fileWidget->PropertyInfo->MaxResolution = '640x480'; // (w x h)
				$fileWidget->PropertyInfo->PropertyValues = array( $fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2 );
				$fileWidget->PropertyInfo->Widgets = $this->getFileWidgets();
				$fileWidget->PropertyUsage = new PropertyUsage();
				$fileWidget->PropertyUsage->Name                 = $fileWidget->PropertyInfo->Name;
				$fileWidget->PropertyUsage->Editable             = false;
				$fileWidget->PropertyUsage->Mandatory            = false;
				$fileWidget->PropertyUsage->Restricted           = false;
				$fileWidget->PropertyUsage->RefreshOnChange      = false;
				$fileWidget->PropertyUsage->InitialHeight        = 0;

				$widget = new DialogWidget();
				$widget->PropertyInfo = new PropertyInfo();
				$widget->PropertyInfo->Name = 'C_MCPSAMPLE_10IMAGES';
				$widget->PropertyInfo->DisplayName = '10Images - Select File';
				$widget->PropertyInfo->Category = null;
				$widget->PropertyInfo->Type = 'fileselector';
				$widget->PropertyInfo->MinValue = 1;
				$widget->PropertyInfo->MaxValue = 10;
				$widget->PropertyInfo->ValueList = null;
				$widget->PropertyInfo->Widgets = array($fileWidget);
				$widget->PropertyInfo->PropertyValues = null;
				$widget->PropertyUsage = new PropertyUsage();
				$widget->PropertyUsage->Name                 = $widget->PropertyInfo->Name;
				$widget->PropertyUsage->Editable             = true;
				$widget->PropertyUsage->Mandatory            = false;
				$widget->PropertyUsage->Restricted           = false;
				$widget->PropertyUsage->RefreshOnChange      = false;
				$widget->PropertyUsage->InitialHeight        = 0;
				$tab->Widgets[] = $widget;
				break;
		}

		$dialog->Tabs = array( $tab );

		$extraMetaData = null;
		$dialog->MetaData = $this->extractMetaDataFromWidgets( $extraMetaData, $tab->Widgets );

		return $dialog;
	}
	
	/**
	 * Composes a Dialog->MetaData list from dialog widgets and custom properties.
	 *
	 * @oaram array $extraMetaDatas List of ExtraMetaData elements
	 * @param array $widgets List of DialogWidget elements
	 * @return array List of MetaDataValue elements
	 */
	public function extractMetaDataFromWidgets( $extraMetaDatas, $widgets )
	{
		$metaDataValues = array();
		if( $widgets ) foreach( $widgets as $widget ) {
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
		return $metaDataValues;
	}

	/**
	 * @see PubPublishing_EnterpriseConnector::getButtonBarForSetPublishPropertiesAction
	 */
	public function getButtonBarForSetPublishPropertiesAction( $defaultButtonBar, $publishFormTemplate, $publishForm )
	{
		$publishForm = $publishForm; // Make analyzer happy.
		switch ( $publishFormTemplate->MetaData->BasicMetaData->DocumentID) {
			case 'sample_0_5':
				foreach ( $defaultButtonBar as $index => $button ) {
					if ( in_array($button->PropertyInfo->Name, array('Update', 'Preview') ) ) {
						unset($defaultButtonBar[$index]);
					}
				}
				break;
			case 'sample_0_6':
				foreach ( $defaultButtonBar as &$button ) {
					if ( in_array($button->PropertyInfo->Name, array('Update', 'Preview') ) ) {
						$button->PropertyUsage->Editable = false;
					}
				}
				break;
			default:
				break;
		}

		return $defaultButtonBar;
	}

	/**
	 * Compose a list of Widgets in widgets in widgets for File widget.
	 *
	 * @return array List of Widgets in widgets in widgets for File widget.
	 */
	private function getFileWidgets()
	{
		// Caption
		$fileWidgets = array();
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = new PropertyInfo();
		$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_DESCRIPTION';
		$fileWidget->PropertyInfo->DisplayName = 'Caption';
		$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileWidget->PropertyInfo->Type = 'string';
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

		// Alt text
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = new PropertyInfo();
		$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_DESCRIPTIONAUTHOR';
		$fileWidget->PropertyInfo->DisplayName = 'Alt text';
		$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileWidget->PropertyInfo->Type = 'string';
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

		// Filesize
		$fileWidget = new DialogWidget();
		$fileWidget->PropertyInfo = new PropertyInfo();
		$fileWidget->PropertyInfo->Name = 'C_MCPSAMPLE_FILESIZE';
		$fileWidget->PropertyInfo->DisplayName = 'Filesize';
		$fileWidget->PropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileWidget->PropertyInfo->Type = 'int';
		$fileWidget->PropertyInfo->DefaultValue = null;
		$fileWidget->PropertyInfo->MaxLength = null;
		$fileWidget->PropertyInfo->PropertyValues = null;
		$fileWidget->PropertyInfo->Widgets = null;
		$fileWidget->PropertyUsage = new PropertyUsage();
		$fileWidget->PropertyUsage->Name = $fileWidget->PropertyInfo->Name;
		$fileWidget->PropertyUsage->Editable = false;
		$fileWidget->PropertyUsage->Mandatory = false;
		$fileWidget->PropertyUsage->Restricted = false;
		$fileWidget->PropertyUsage->RefreshOnChange = false;
		$fileWidget->PropertyUsage->InitialHeight = 0;
		$fileWidgets[] = $fileWidget;

		return $fileWidgets; // Widgets in widgets (in widgets)
	}
	
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		// To make analyzer happy.
		$dossier = $dossier; $objectsInDossier = $objectsInDossier; $publishTarget = $publishTarget;
		return array();
	}

	public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		return $this->publishDossier( $dossier, $objectsInDossier, $publishTarget );
	}

	public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget )
	{
		// To make analyzer happy.
		$dossier = $dossier; $objectsInDossier = $objectsInDossier; $publishTarget = $publishTarget;
		return array();
	}

	public function requestPublishFields($dossier, $objectsInDossier, $publishTarget)
	{
		// To make analyzer happy.
		$dossier = $dossier; $objectsInDossier = $objectsInDossier; $publishTarget = $publishTarget;
		return array();
	}

	public function getDossierURL($dossier, $objectsInDossier, $publishTarget)
	{
		// To make analyzer happy.
		$dossier = $dossier; $objectsInDossier = $objectsInDossier; $publishTarget = $publishTarget;

		return "";
	}

	public function previewDossier(&$dossier, &$objectsInDossier, $publishTarget)
	{
		// To make analyzer happy.
		$dossier = $dossier; $objectsInDossier = $objectsInDossier; $publishTarget = $publishTarget;
		return array();
	}
}
