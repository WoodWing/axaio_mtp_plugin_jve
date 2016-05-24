<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Sample that shows how to let a server plug-in automatically install custom object
 * properties into the database (instead of manual installation in the Metadata admin page).
 */
require_once BASEDIR . '/server/interfaces/plugins/connectors/CustomObjectMetaData_EnterpriseConnector.class.php';

class Facebook_CustomObjectMetaData extends CustomObjectMetaData_EnterpriseConnector
{
	/** Constant for the Plugin type. */
	const Facebook_CustomObjectMetaData = 'Facebook';

    /**
	 * Returns list of PropertyInfo for the Publish Forms.
	 *
	 * @return array contains widgets
	 */
	private function getPublishFormPropertyDefinition()
	{
		$props = array();

		// Article Component Selector.
		$articleComponent1 = new PropertyInfo();
		$articleComponent1->Name = 'C_FACEBOOK_PF_MESSAGE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
		$articleComponent1->DisplayName = 'Selected Text Component';
		$articleComponent1->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$articleComponent1->Type = 'articlecomponent';
		$articleComponent1->DefaultValue = null;
		$articleComponent1->MaxLength = null; // (2MB max upload)
		$articleComponent1->MinResolution = null; // (w x h)
		$articleComponent1->MaxResolution = null; // (w x h)
		$articleComponent1->PropertyValues = array(new PropertyValue('appl./incopyicml', '.wcml', 'Format'));
		$articleComponent1->Widgets = null;
		$articleComponent1->AdminUI = false;
		$articleComponent1->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
		$props[] = $articleComponent1;

		$articleComponentSelector1 = new PropertyInfo();
		$articleComponentSelector1->Name = 'C_FACEBOOK_PF_MESSAGE_SEL';
		$articleComponentSelector1->DisplayName = 'Message';
		$articleComponentSelector1->Category = null;
		$articleComponentSelector1->Type = 'articlecomponentselector';
		$articleComponentSelector1->MinValue = 1; // One component.
		$articleComponentSelector1->MaxValue = 1; // One component.
		$articleComponentSelector1->ValueList = null;
		$articleComponentSelector1->AdminUI = false;
		$articleComponentSelector1->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
		$articleComponentSelector1->PropertyValues = array();
		$articleComponentSelector1->Widgets = array($articleComponent1);
		$props[] = $articleComponentSelector1;

		// Media Component Selector.
		$mediaComponent1 = new PropertyInfo();
		$mediaComponent1->Name = 'C_FACEBOOK_PF_MEDIA'; // Name for a File / ArticleComponent should be generated. (d_ ? )
		$mediaComponent1->DisplayName = 'Media';
		$mediaComponent1->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$mediaComponent1->Type = 'file';
		$mediaComponent1->DefaultValue = null;
		$mediaComponent1->MaxLength = null; // (2MB max upload)
		$mediaComponent1->MinResolution = null; // (w x h)
		$mediaComponent1->MaxResolution = null; // (w x h)
		$mediaComponent1->PropertyValues = self::getMediaPropertyValues();
		$mediaComponent1->Widgets = null;
		$mediaComponent1->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
		$mediaComponent1->AdminUI = false;
		$props[] = $mediaComponent1;

		$mediaComponentSelector1 = new PropertyInfo();
		$mediaComponentSelector1->Name = 'C_FACEBOOK_PF_MEDIA_SEL';
		$mediaComponentSelector1->DisplayName = 'Image';
		$mediaComponentSelector1->Category = null;
		$mediaComponentSelector1->Type = 'fileselector';
		$mediaComponentSelector1->MinValue = 1; // One component.
		$mediaComponentSelector1->MaxValue = 1; // One component.
        $mediaComponentSelector1->DefaultValue = null;
		$mediaComponentSelector1->AdminUI = false;
        $mediaComponentSelector1->MinResolution = null; // (w x h)
        $mediaComponentSelector1->MaxResolution = null; // (w x h)
		$mediaComponentSelector1->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
		$mediaComponentSelector1->PropertyValues = array();
		$mediaComponentSelector1->Widgets = array($mediaComponent1);
		$props[] = $mediaComponentSelector1;

        $albumDescriptionComponent = new PropertyInfo();
        $albumDescriptionComponent->Name = 'C_FACEBOOK_ALBUM_NAME';
        $albumDescriptionComponent->DisplayName = 'Album Name';
        $albumDescriptionComponent->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
        $albumDescriptionComponent->Type = 'string';
        $albumDescriptionComponent->DefaultValue = null;
        $albumDescriptionComponent->MaxLength = null; // (2MB max upload)
        $albumDescriptionComponent->MinResolution = null; // (w x h)
        $albumDescriptionComponent->MaxResolution = null; // (w x h)
        $albumDescriptionComponent->PropertyValues = array(new PropertyValue('appl./incopyicml', '.wcml', 'Format'));
        $albumDescriptionComponent->Widgets = null;
        $albumDescriptionComponent->AdminUI = false;
        $albumDescriptionComponent->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
        $props[] = $albumDescriptionComponent;

        $albumNameComponent1 = new PropertyInfo();
        $albumNameComponent1->Name = 'C_FACEBOOK_ALBUM';
        $albumNameComponent1->DisplayName = 'Album Description';
        $albumNameComponent1->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
        $albumNameComponent1->Type = 'multiline';
        $albumNameComponent1->DefaultValue = null;
        $albumNameComponent1->MaxLength = null; // (2MB max upload)
        $albumNameComponent1->MinResolution = null; // (w x h)
        $albumNameComponent1->MaxResolution = null; // (w x h)
        $albumNameComponent1->PropertyValues = array(new PropertyValue('appl./incopyicml', '.wcml', 'Format'));
        $albumNameComponent1->Widgets = null;
        $albumNameComponent1->AdminUI = false;
        $albumNameComponent1->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
        $props[] = $albumNameComponent1;

		$hyperlinkComponent1 = new PropertyInfo();
		$hyperlinkComponent1->Name = 'C_FACEBOOK_PF_HYPERLINK_URL';
		$hyperlinkComponent1->DisplayName = 'Hyperlink';
		$hyperlinkComponent1->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$hyperlinkComponent1->Type = 'string';
		$hyperlinkComponent1->DefaultValue = null;
		$hyperlinkComponent1->MaxLength = null; // (2MB max upload)
		$hyperlinkComponent1->MinResolution = null; // (w x h)
		$hyperlinkComponent1->MaxResolution = null; // (w x h)
		$hyperlinkComponent1->PropertyValues = null;
		$hyperlinkComponent1->Widgets = null;
		$hyperlinkComponent1->AdminUI = false;
		$hyperlinkComponent1->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
		$props[] = $hyperlinkComponent1;

		// *** MultipleImages ***
		// File Selector Template Properties.

		// File: C_FACEBOOK_MULTI_IMAGES_FILE
		$fileImagePropertyInfo = new PropertyInfo();
		$fileImagePropertyInfo->Name = 'C_FACEBOOK_MULTI_IMAGES_FILE';
		$fileImagePropertyInfo->DisplayName = 'Selected Image';
		$fileImagePropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo->Type = 'file';
		$fileImagePropertyInfo->DefaultValue = null;
		$fileImagePropertyInfo->MaxLength = 2000000; // (2MB max upload)
		$fileImagePropertyInfo->MinResolution = '200x200'; // (w x h)
		$fileImagePropertyInfo->MaxResolution = '640x480'; // (w x h)
		$fileImagePropertyInfo->PropertyValues = self::getMediaPropertyValues();
		$fileImagePropertyInfo->Widgets = null;
		$fileImagePropertyInfo->AdminUI = false;
		$fileImagePropertyInfo->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
		$props[] = $fileImagePropertyInfo;

		// FileSelector: C_FACEBOOK_MULTI_IMAGES
		$selectorImagePropertyInfo = new PropertyInfo();
		$selectorImagePropertyInfo->Name = 'C_FACEBOOK_MULTI_IMAGES';
		$selectorImagePropertyInfo->DisplayName = 'Images';
		$selectorImagePropertyInfo->Category = null;
		$selectorImagePropertyInfo->Type = 'fileselector';
		$selectorImagePropertyInfo->MinValue = 0; // Empty allowed.
		$selectorImagePropertyInfo->MaxValue = null; // Many images allowed.
		$selectorImagePropertyInfo->ValueList = null;
		$selectorImagePropertyInfo->AdminUI = false;
		$selectorImagePropertyInfo->Widgets = null;
		$selectorImagePropertyInfo->PropertyValues = array();
		$selectorImagePropertyInfo->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
		$props[] = $selectorImagePropertyInfo;

		return $props;
	}

	private function getOtherPropertyDefinitions(){
		// The following properties are applicable for ANY ObjectType.

		// FileSelector: C_FACEBOOK_IMAGE_DESCRIPTION
		$descriptionImagePropertyInfo = new PropertyInfo();
		$descriptionImagePropertyInfo->Name = 'C_FACEBOOK_IMAGE_DESCRIPTION';
		$descriptionImagePropertyInfo->DisplayName = 'Description';
		$descriptionImagePropertyInfo->Category = null;
		$descriptionImagePropertyInfo->Type = 'multiline';
		$descriptionImagePropertyInfo->MinValue = 0; // Empty allowed.
		$descriptionImagePropertyInfo->MaxValue = null; // Many images allowed.
		$descriptionImagePropertyInfo->ValueList = null;
		$descriptionImagePropertyInfo->AdminUI = false;
		$descriptionImagePropertyInfo->Widgets = null;
		$descriptionImagePropertyInfo->PropertyValues = array();
		$descriptionImagePropertyInfo->PublishSystem = Facebook_CustomObjectMetaData::Facebook_CustomObjectMetaData;
		$props[] = $descriptionImagePropertyInfo;

		return $props;
	}


	/**
	 * Return a list of supported media types
	 *
	 * @return array
	 */
	private function getMediaPropertyValues()
	{
		$properties = array();

		//Image
		$properties[] = new PropertyValue('image/png', '.png', 'Format');
		$properties[] = new PropertyValue('image/jpeg', '.jpg', 'Format');

		return $properties;
	}

	/**
	 * See CustomObjectMetaData_EnterpriseConnector::collectCustomProperties function header.
	 */
	final public function collectCustomProperties($coreInstallation)
	{
		$coreInstallation = $coreInstallation; // keep analyzer happy

		$props = array();
		$props[0]['PublishForm'] = self::getPublishFormPropertyDefinition();
		$props[0][0] = self::getOtherPropertyDefinitions();
		return $props;
	}

	/**
	 * Returns PropertyInfo of the given $name.
	 *
	 * @param string $name The property to search.
	 * @return null|PropertyInfo
	 */
	public function getProperty($name)
	{
		$prop = null;
		$props = array_merge(self::getPublishFormPropertyDefinition(), self::getOtherPropertyDefinitions());

		foreach ($props as $prop) {
			if ($prop->Name == $name) {
				return $prop;
			}
		}
		LogHandler::Log('FacebookPublisher', 'ERROR', ' property ' . $name . ' not found');
		return null;
	}
}
