<?php
/**
 * @since 		v8.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Sample that shows how to let a server plug-in automatically install custom object
 * properties into the database (instead of manual installation in the Metadata admin page).
**/

require_once BASEDIR . '/server/interfaces/plugins/connectors/CustomObjectMetaData_EnterpriseConnector.class.php';

class MultiChannelPublishingSample_CustomObjectMetaData extends CustomObjectMetaData_EnterpriseConnector
{
	/**
	 * See CustomObjectMetaData_EnterpriseConnector::collectCustomProperties function header.
	 */
	final public function collectCustomProperties( $coreInstallation )
	{
		$props = array();
		// Because we provide an admin page that imports custom object properties definition,
		// we bail out when the core server is gathering and installing all custom properties
		// during generic installation procedure such as running the Server Plug-ins page.
		if( $coreInstallation ) {
			return $props;
		}

		// At this point, the admin user has pressed the import button of the MultiChannelPublishingSample import page.
		$this->composeRecipeCustomProps( $props );
		$this->composeNewsCustomProps( $props );
		$this->composeAllWidgetsCustomProps( $props );
		$this->composeFileSelectorCustomProps( $props );
		$this->composeArticleComponentSelectorCustomProps( $props );
		$this->composeCustomPropsForWidgetInWidget( $props );
		$this->composePropertiesForBuildTest( $props );

		return $props;
	}

	/**
	 * Fills in custom properties into $props which will be used in 'Recipe' template.
	 *
	 * @param array &$props (Writable) See header above.
	 */
	private function composeRecipeCustomProps( &$props )
	{
		// For the Recipe template
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_RECTITLE','Title',null,'string','' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_TEASERTXT','TeaserText',null,'multiline','' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_SERVINGS','Servings',null,'int' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_PREPTIME','PrepTime',null,'int' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_TOTALTIME','TotalTime',null,'int' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_CUISIN','Cuisin',null,'list', 'Japanese', array('Indian','Japanese','Western') );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_MAININGREDIENTS','Main Ingredients',null,'multilist', 'Sour Cream', array('Apple','Sour Cream','Tomato') );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_INGREDIENTS','Ingredients',null,'multiline', '' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_PREPARATION','Preparation',null,'multiline', '' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_NUTRIINFO','Nutritional Information',null,'multiline', '' );

	}

	/**
	 * Fills in custom properties into $props which will be used in 'News' template.
	 *
	 * @param array &$props (Writable) See header above.
	 */
	private function composeNewsCustomProps( &$props)
	{
		// For the news template
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_NEWSTITLE','Title',null,'string','' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_HEADER','Header',null,'multiline','' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_BODY','Body',null,'multiline','' );
	}

	/**
	 * Fills in custom properties into $props which will be used in 'All Widgets' template.
	 *
	 * @param array &$props (Writable) See header above.
	 */
	private function composeAllWidgetsCustomProps( &$props )
	{
		// For the all widgets template
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_STRING','String',null,'string','' );
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_MULTISTRING','Multistring',null,'multistring','' );

		$prop = new PropertyInfo( 'C_MCPSAMPLE_TOURISMCITIES','Tourism Cities',null,'multistring','', null,null,null,140 );
		$prop->TermEntity = 'City';
		$prop->SuggestionEntity = 'City';
		$prop->AutocompleteProvider = 'MultiChannelPublishingSample';
		$props[0]['PublishForm'][] = $prop;

		$prop = new PropertyInfo( 'C_MCPSAMPLE_COUNTRIES','Countries',null,'multistring','', null,null,null,140 );
		$prop->TermEntity = 'Country';
		$prop->SuggestionEntity = 'Country';
		// Don't set this AutocompleteProvider on purpose, let the server resolve this.
		// $prop->AutocompleteProvider = 'MultiChannelPublishingSample';
		$props[0]['PublishForm'][] = $prop;

		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_MULTILINE','Multiline',null,'multiline','',null,null,null,140 );
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_BOOL','Bool',null,'bool' );
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_INT','Int',null,'int' );
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_DOUBLE','Double',null,'double' );
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_DATE','Date',null,'date' );
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_DATETIME','Datetime',null,'datetime' );
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_LIST','List',null,'list','Option 1', array('Option 1','Option 2','Option 3') );
		$props[0]['PublishForm'][] = new PropertyInfo( 'C_MCPSAMPLE_MULTILIST','Multilist',null,'multilist','Option a', array('Option a','Option b','Option c') );
	}

	/**
	 * Fills in custom properties into $props which will be used in 'File Selector' template.
	 *
	 * @param array &$props (Writable) See header above.
	 */
	private function composeFileSelectorCustomProps( &$props)
	{
		// File Selector Template Properties.
		$fileImagePropertyInfoPropValue1 = new PropertyValue('image/png', '.png', 'Format');
		$fileImagePropertyInfoPropValue2 = new PropertyValue('image/jpeg', '.jpg', 'Format');

		$fileImagePropertyInfo = new PropertyInfo();
		$fileImagePropertyInfo->Name = 'C_MCPSAMPLE_MAIN_IMAGE_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
		$fileImagePropertyInfo->DisplayName = 'Selected Image';
		$fileImagePropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo->Type = 'file';
		$fileImagePropertyInfo->DefaultValue = null;
		$fileImagePropertyInfo->MaxLength = 1000000; // (2MB max upload)
		$fileImagePropertyInfo->MinResolution = '200x200'; // (w x h)
		$fileImagePropertyInfo->MaxResolution = '640x480'; // (w x h)
		$fileImagePropertyInfo->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
		$fileImagePropertyInfo->Widgets = null;
		$fileImagePropertyInfo->AdminUI = false;

		// Add a file to the first option.
		$fileImagePropertyInfo1 = new PropertyInfo();
		$fileImagePropertyInfo1->Name = 'C_MCPSAMPLE_HEAD_IMAGE_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
		$fileImagePropertyInfo1->DisplayName = 'Selected Image';
		$fileImagePropertyInfo1->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo1->Type = 'file';
		$fileImagePropertyInfo1->DefaultValue = null;
		$fileImagePropertyInfo1->MaxLength = 1500000; // (2MB max upload)
		$fileImagePropertyInfo1->MinResolution = '300x300'; // (w x h)
		$fileImagePropertyInfo1->MaxResolution = '1024x768'; // (w x h)
		$fileImagePropertyInfo1->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
		$fileImagePropertyInfo1->Widgets = null;
		$fileImagePropertyInfo1->AdminUI = false;

		// Add a file to the first option.
		$fileImagePropertyInfo2 = new PropertyInfo();
		$fileImagePropertyInfo2->Name = 'C_MCPSAMPLE_HEAD_IMAGEA_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
		$fileImagePropertyInfo2->DisplayName = 'Selected Image';
		$fileImagePropertyInfo2->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo2->Type = 'file';
		$fileImagePropertyInfo2->DefaultValue = null;
		$fileImagePropertyInfo2->MaxLength = 2000000; // (2MB max upload)
		$fileImagePropertyInfo2->MinResolution = '50x50'; // (w x h)
		$fileImagePropertyInfo2->MaxResolution = '800x600'; // (w x h)
		$fileImagePropertyInfo2->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
		$fileImagePropertyInfo2->Widgets = null;
		$fileImagePropertyInfo2->AdminUI = false;

		// Add a file to the first option.
		$fileImagePropertyInfo3 = new PropertyInfo();
		$fileImagePropertyInfo3->Name = 'C_MCPSAMPLE_HEAD_IMAGEB_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
		$fileImagePropertyInfo3->DisplayName = 'Selected Image';
		$fileImagePropertyInfo3->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo3->Type = 'file';
		$fileImagePropertyInfo3->DefaultValue = null;
		$fileImagePropertyInfo3->MaxLength = 2000000; // (2MB max upload)
		$fileImagePropertyInfo3->MinResolution = '50x50'; // (w x h)
		$fileImagePropertyInfo3->MaxResolution = '800x800'; // (w x h)
		$fileImagePropertyInfo3->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
		$fileImagePropertyInfo3->Widgets = null;
		$fileImagePropertyInfo3->AdminUI = false;

		// Add a file to the first option.
		$fileImagePropertyInfo4 = new PropertyInfo();
		$fileImagePropertyInfo4->Name = 'C_MCPSAMPLE_MAIN_IMAGEB_FILE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
		$fileImagePropertyInfo4->DisplayName = 'Selected Image';
		$fileImagePropertyInfo4->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo4->Type = 'file';
		$fileImagePropertyInfo4->DefaultValue = null;
		$fileImagePropertyInfo4->MaxLength = 1500000; // (2MB max upload)
		$fileImagePropertyInfo4->MinResolution = '50x50'; // (w x h)
		$fileImagePropertyInfo4->MaxResolution = '600x600'; // (w x h)
		$fileImagePropertyInfo4->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
		$fileImagePropertyInfo4->Widgets = null;
		$fileImagePropertyInfo4->AdminUI = false;

		// FileSelector 1, first option
		$mainImagePropertyInfo = new PropertyInfo();
		$mainImagePropertyInfo->Name = 'C_MCPSAMPLE_MAIN_IMAGE';
		$mainImagePropertyInfo->DisplayName = 'Main Image - Select a File';
		$mainImagePropertyInfo->Category = null;
		$mainImagePropertyInfo->Type = 'fileselector';
		$mainImagePropertyInfo->MinValue = 1;
		$mainImagePropertyInfo->MaxValue = 1;
		$mainImagePropertyInfo->ValueList = null;
		$mainImagePropertyInfo->AdminUI = false;
		$mainImagePropertyInfo->Widgets = null;
		$mainImagePropertyInfo->PropertyValues = array();

		// FileSelector 1, second option
		$headImagePropertyInfo1 = new PropertyInfo();
		$headImagePropertyInfo1->Name = 'C_MCPSAMPLE_HEAD_IMAGE_A';
		$headImagePropertyInfo1->DisplayName = 'Heading Image #1 - Select a File';
		$headImagePropertyInfo1->Category = null;
		$headImagePropertyInfo1->Type = 'fileselector';
		$headImagePropertyInfo1->MinValue = 1;
		$headImagePropertyInfo1->MaxValue = 1;
		$headImagePropertyInfo1->ValueList = null;
		$headImagePropertyInfo1->AdminUI = false;
		$headImagePropertyInfo1->PropertyValues = array();
		$headImagePropertyInfo1->Widgets = null;

		// FileSelector 1, third option
		$headImagePropertyInfo2 = new PropertyInfo();
		$headImagePropertyInfo2->Name = 'C_MCPSAMPLE_HEAD_IMAGE_B';
		$headImagePropertyInfo2->DisplayName = 'Heading Image #2 - Select a File';
		$headImagePropertyInfo2->Category = null;
		$headImagePropertyInfo2->Type = 'fileselector';
		$headImagePropertyInfo2->MinValue = 1;
		$headImagePropertyInfo2->MaxValue = 1;
		$headImagePropertyInfo2->AdminUI = false;
		$headImagePropertyInfo2->PropertyValues = array();
		$headImagePropertyInfo2->Widgets = null;

		// FileSelector 1, fourth option
		$headImagePropertyInfo3 = new PropertyInfo();
		$headImagePropertyInfo3->Name = 'C_MCPSAMPLE_HEAD_IMAGE_C';
		$headImagePropertyInfo3->DisplayName = 'Heading Image #3 - Select a File';
		$headImagePropertyInfo3->Category = null;
		$headImagePropertyInfo3->Type = 'fileselector';
		$headImagePropertyInfo3->MinValue = 1;
		$headImagePropertyInfo3->MaxValue = 1;
		$headImagePropertyInfo3->ValueList = null;
		$headImagePropertyInfo3->AdminUI = false;
		$headImagePropertyInfo3->PropertyValues = array();
		$headImagePropertyInfo3->Widgets = null;

		// FileSelector 2, first and only option.
		$mainImagePropertyInfo2 = new PropertyInfo();
		$mainImagePropertyInfo2->Name = 'C_MCPSAMPLE_MAIN_IMAGE_B';
		$mainImagePropertyInfo2->DisplayName = 'Main Image #2 - Select a File';
		$mainImagePropertyInfo2->Category = null;
		$mainImagePropertyInfo2->Type = 'fileselector';
		$mainImagePropertyInfo2->MinValue = 1;
		$mainImagePropertyInfo2->MaxValue = 5;
		$mainImagePropertyInfo2->ValueList = null;
		$mainImagePropertyInfo2->AdminUI = false;
		$mainImagePropertyInfo2->PropertyValues = array();
		$mainImagePropertyInfo2->Widgets = null;

		// Set all the PropertyInfos
		$props[0][0][] = $fileImagePropertyInfo;
		$props[0][0][] = $fileImagePropertyInfo1;
		$props[0][0][] = $fileImagePropertyInfo2;
		$props[0][0][] = $fileImagePropertyInfo3;
		$props[0][0][] = $fileImagePropertyInfo4;
		$props[0][0][] = $mainImagePropertyInfo;
		$props[0][0][] = $headImagePropertyInfo1;
		$props[0][0][] = $headImagePropertyInfo2;
		$props[0][0][] = $headImagePropertyInfo3;
		$props[0][0][] = $mainImagePropertyInfo2;
	}

	/**
	 * Fills in custom properties into $props which will be used in 'Article Component Selector' template.
	 *
	 * @param array &$props (Writable) See header above.
	 */
	private function composeArticleComponentSelectorCustomProps( &$props )
	{

		// Article Component Selector.
		$articleComponentPropValue1 = new PropertyValue('application/incopyicml', '.wcml', 'Format');

		$articleComponentSelector1 = new PropertyInfo();
		$articleComponentSelector1->Name = 'C_MCPSAMPLE_BODYTEXT';
		$articleComponentSelector1->DisplayName = 'Body Text';
		$articleComponentSelector1->Category = null;
		$articleComponentSelector1->Type = 'articlecomponentselector';
		$articleComponentSelector1->MinValue = 1; // One component.
		$articleComponentSelector1->MaxValue = 1; // One component.
		$articleComponentSelector1->ValueList = null;
		$articleComponentSelector1->AdminUI = false;
		$articleComponentSelector1->PropertyValues = array();
		$articleComponentSelector1->Widgets = null;

		$articleComponent1 = new PropertyInfo();
		$articleComponent1->Name = 'C_MCPSAMPLE_ART_COMPONENTA'; // Name for a File / ArticleComponent should be generated. (d_ ? )
		$articleComponent1->DisplayName = 'Selected Text Component';
		$articleComponent1->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$articleComponent1->Type = 'articlecomponent';
		$articleComponent1->DefaultValue = null;
		$articleComponent1->MaxLength = null; // (2MB max upload)
		$articleComponent1->MinResolution = null; // (w x h)
		$articleComponent1->MaxResolution = null; // (w x h)
		$articleComponent1->PropertyValues = array($articleComponentPropValue1);
		$articleComponent1->Widgets = null;
		$articleComponent1->AdminUI = false;

		$props[0][0][] = $articleComponentSelector1;
		$props[0][0][] = $articleComponent1;
	}

	/**
	 * Fills in custom properties into $props which will be used in 'Widget in Widget' template.
	 *
	 * @param array &$props (Writable) See header above.
	 */
	private function composeCustomPropsForWidgetInWidget( &$props )
	{
		// The following properties are applicable for ANY ObjectType, create with empty objType field. (0)
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_DESCRIPTION','Caption',null,'string','' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_DESCRIPTIONAUTHOR','Alt Text',null,'multiline','' );
		$props[0][0][] = new PropertyInfo( 'C_MCPSAMPLE_FILESIZE','Filesize',null,'int' );

		// *** Single File ***
		// File: C_MCPSAMPLE_1FILE_FILE
		$filePropertyInfo = new PropertyInfo();
		$filePropertyInfo->Name = 'C_MCPSAMPLE_1FILE_FILE';
		$filePropertyInfo->DisplayName = 'Selected File';
		$filePropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$filePropertyInfo->Type = 'file';
		$filePropertyInfo->DefaultValue = null;
		$filePropertyInfo->MaxLength = 2000000; // (2MB max upload)
		$filePropertyInfo->PropertyValues = null;
		$filePropertyInfo->Widgets = null;
		$filePropertyInfo->AdminUI = false;
		$filePropertyInfo->PublishSystem = 'MultiChannelPublishingSample';

		// FileSelector: C_MCPSAMPLE_1FILE
		$fileSelectorPropertyInfo = new PropertyInfo();
		$fileSelectorPropertyInfo->Name = 'C_MCPSAMPLE_1FILE';
		$fileSelectorPropertyInfo->DisplayName = 'Single File - Select a File';
		$fileSelectorPropertyInfo->Category = null;
		$fileSelectorPropertyInfo->Type = 'fileselector';
		$fileSelectorPropertyInfo->MinValue = 1;
		$fileSelectorPropertyInfo->MaxValue = 1;
		$fileSelectorPropertyInfo->ValueList = null;
		$fileSelectorPropertyInfo->AdminUI = false;
		$fileSelectorPropertyInfo->Widgets = null;
		$fileSelectorPropertyInfo->PropertyValues = array();
		$fileSelectorPropertyInfo->PublishSystem = 'MultiChannelPublishingSample';

		$props[0]['PublishForm'][] = $filePropertyInfo;
		$props[0]['PublishForm'][] = $fileSelectorPropertyInfo;

		// *** MultipleImages ***
		// File Selector Template Properties.
		$fileImagePropertyInfoPropValue1 = new PropertyValue('image/png', '.png', 'Format');
		$fileImagePropertyInfoPropValue2 = new PropertyValue('image/jpeg', '.jpg', 'Format');

		// File: C_MCPSAMPLE_MULTI_IMAGES_FILE
		$fileImagePropertyInfo = new PropertyInfo();
		$fileImagePropertyInfo->Name = 'C_MCPSAMPLE_MULTI_IMAGES_FILE';
		$fileImagePropertyInfo->DisplayName = 'Selected Image';
		$fileImagePropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo->Type = 'file';
		$fileImagePropertyInfo->DefaultValue = null;
		$fileImagePropertyInfo->MaxLength = 2000000; // (2MB max upload)
		$fileImagePropertyInfo->MinResolution = '200x200'; // (w x h)
		$fileImagePropertyInfo->MaxResolution = '640x480'; // (w x h)
		$fileImagePropertyInfo->PropertyValues = array($fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2);
		$fileImagePropertyInfo->Widgets = null;
		$fileImagePropertyInfo->AdminUI = false;
		$fileImagePropertyInfo->PublishSystem = 'MultiChannelPublishingSample';

		// FileSelector: C_MCPSAMPLE_MULTI_IMAGES
		$selectorImagePropertyInfo = new PropertyInfo();
		$selectorImagePropertyInfo->Name = 'C_MCPSAMPLE_MULTI_IMAGES';
		$selectorImagePropertyInfo->DisplayName = 'MultiImages - Select File';
		$selectorImagePropertyInfo->Category = null;
		$selectorImagePropertyInfo->Type = 'fileselector';
		$selectorImagePropertyInfo->MinValue = 0; // Empty allowed.
		$selectorImagePropertyInfo->MaxValue = null; // Many images allowed.
		$selectorImagePropertyInfo->ValueList = null;
		$selectorImagePropertyInfo->AdminUI = false;
		$selectorImagePropertyInfo->Widgets = null;
		$selectorImagePropertyInfo->PropertyValues = array();
		$selectorImagePropertyInfo->PublishSystem = 'MultiChannelPublishingSample';

		$props[0]['PublishForm'][] = $fileImagePropertyInfo;
		$props[0]['PublishForm'][] = $selectorImagePropertyInfo; // ObjType = '' because the object type for normal file selector can be any Enterprise ObjType.

		// *** Multiple zip file ***
		// File Selector Template Properties.
		$fileZipPropertyInfoPropValue1 = new PropertyValue('application/zip', '.zip', 'Format');
		$fileZipPropertyInfoPropValue2 = new PropertyValue('application/x-gzip', '.gz', 'Format');

		// File: C_MCPSAMPLE_MULTI_ZIP_FILE
		$fileZipPropertyInfo = new PropertyInfo();
		$fileZipPropertyInfo->Name = 'C_MCPSAMPLE_MULTI_ZIP_FILE';
		$fileZipPropertyInfo->DisplayName = 'Selected Zip File';
		$fileZipPropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileZipPropertyInfo->Type = 'file';
		$fileZipPropertyInfo->DefaultValue = null;
		$fileZipPropertyInfo->MaxLength = 2000000; // (2MB max upload)
		$fileZipPropertyInfo->MinResolution = '200x200'; // (w x h)
		$fileZipPropertyInfo->MaxResolution = '640x480'; // (w x h)
		$fileZipPropertyInfo->PropertyValues = array( $fileZipPropertyInfoPropValue1, $fileZipPropertyInfoPropValue2);
		$fileZipPropertyInfo->Widgets = null;
		$fileZipPropertyInfo->AdminUI = false;
		$fileZipPropertyInfo->PublishSystem = 'MultiChannelPublishingSample';

		// FileSelector: C_MCPSAMPLE_MULTI_ZIP
		$selectorZipPropertyInfo = new PropertyInfo();
		$selectorZipPropertyInfo->Name = 'C_MCPSAMPLE_MULTI_ZIP';
		$selectorZipPropertyInfo->DisplayName = 'MultiZip - Select File';
		$selectorZipPropertyInfo->Category = null;
		$selectorZipPropertyInfo->Type = 'fileselector';
		$selectorZipPropertyInfo->MinValue = 0; // Empty allowed.
		$selectorZipPropertyInfo->MaxValue = null; // Many files allowed.
		$selectorZipPropertyInfo->ValueList = null;
		$selectorZipPropertyInfo->AdminUI = false;
		$selectorZipPropertyInfo->Widgets = null;
		$selectorZipPropertyInfo->PropertyValues = array();
		$selectorZipPropertyInfo->PublishSystem = 'MultiChannelPublishingSample';

		$props[0]['PublishForm'][] = $fileZipPropertyInfo;
		$props[0]['PublishForm'][] = $selectorZipPropertyInfo;

		// *** 10 Images ***
		$fileImagePropertyInfoPropValue1 = new PropertyValue('image/png', '.png', 'Format');
		$fileImagePropertyInfoPropValue2 = new PropertyValue('image/jpeg', '.jpg', 'Format');

		// File: C_MCPSAMPLE_10IMAGES_FILE
		$fileImagePropertyInfo = new PropertyInfo();
		$fileImagePropertyInfo->Name = 'C_MCPSAMPLE_10IMAGES_FILE';
		$fileImagePropertyInfo->DisplayName = 'Selected Image';
		$fileImagePropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo->Type = 'file';
		$fileImagePropertyInfo->DefaultValue = null;
		$fileImagePropertyInfo->MaxLength = 2000000; // (2MB max upload)
		$fileImagePropertyInfo->MinResolution = '200x200'; // (w x h)
		$fileImagePropertyInfo->MaxResolution = '640x480'; // (w x h)
		$fileImagePropertyInfo->PropertyValues = array( $fileImagePropertyInfoPropValue1, $fileImagePropertyInfoPropValue2 );
		$fileImagePropertyInfo->Widgets = null;
		$fileImagePropertyInfo->AdminUI = false;
		$fileImagePropertyInfo->PublishSystem = 'MultiChannelPublishingSample';

		// FileSelector: C_MCPSAMPLE_10IMAGES
		$selectorImagePropertyInfo = new PropertyInfo();
		$selectorImagePropertyInfo->Name = 'C_MCPSAMPLE_10IMAGES';
		$selectorImagePropertyInfo->DisplayName = '10Images - Select File';
		$selectorImagePropertyInfo->Category = null;
		$selectorImagePropertyInfo->Type = 'fileselector';
		$selectorImagePropertyInfo->MinValue = 1;
		$selectorImagePropertyInfo->MaxValue = 10;
		$selectorImagePropertyInfo->ValueList = null;
		$selectorImagePropertyInfo->AdminUI = false;
		$selectorImagePropertyInfo->Widgets = null;
		$selectorImagePropertyInfo->PropertyValues = array();
		$selectorImagePropertyInfo->PublishSystem = 'MultiChannelPublishingSample';

		$props[0]['PublishForm'][] = $fileImagePropertyInfo;
		$props[0]['PublishForm'][] = $selectorImagePropertyInfo;

	}

	/**
	 * Fills in custom properties into $props which are needed by the BuildTest.
	 *
	 * @param array &$props (Writable) See header above.
	 */
	private function composePropertiesForBuildTest( &$props )
	{
		// The following properties are needed for the build test.
		$bool = new PropertyInfo();
		$bool->Name = 'C_MCPSAMPLE_PUBLISHFORM_BOOL';
		$bool->DisplayName = 'Publish Form Only Boolean Property';
		$bool->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$bool->Type = 'bool';
		$bool->DefaultValue = 'true';
		$bool->AdminUI = true;

		$string = new PropertyInfo();
		$string->Name = 'C_MCPSAMPLE_PUBLISHFORM_STRING';
		$string->DisplayName = 'Publish Form Only String Property';
		$string->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$string->Type = 'string';
		$string->DefaultValue = 'Default string value.';
		$string->AdminUI = true;
		$string->PublishSystem = 'MultiChannelPublishingSample';

		$int = new PropertyInfo();
		$int->Name = 'C_MCPSAMPLE_PUBLISHFORM_INT';
		$int->DisplayName = 'Publish Form Only Integer Property';
		$int->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$int->Type = 'int';
		$int->DefaultValue = 2013;
		$int->AdminUI = false;
		$int->TemplateId = 1234;

		$double = new PropertyInfo();
		$double->Name = 'C_MCPSAMPLE_PUBLISHFORM_DOUBLE';
		$double->DisplayName = 'Publish Form Only Double Property';
		$double->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$double->Type = 'double';
		$double->DefaultValue = 2012.4;
		$double->AdminUI = false;
		$double->PublishSystem = 'MultiChannelPublishingSample';
		$double->TemplateId = 1234;

		// If we do not have an index for the PublishForm in the props, create one.
		if (!isset($props[0]['PublishForm'])) {
			$props[0]['PublishForm'] = array();
 		}

		// Add the additional properties for the 'PublishForm' object.
		$props[0]['PublishForm'][] = $bool;
		$props[0]['PublishForm'][] = $string;
		$props[0]['PublishForm'][] = $int;
		$props[0]['PublishForm'][] = $double;
	}
}
