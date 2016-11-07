<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v9.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/CustomObjectMetaData_EnterpriseConnector.class.php';

class WordPress_CustomObjectMetaData extends CustomObjectMetaData_EnterpriseConnector
{

	/**
	 * Returns list of PropertyInfo for the publish forms.
	 *
	 * @return array contains widgets
	 */
	private function getPublishFormPropertyDefinition()
	{
		require_once dirname(__FILE__) . '/WordPress_Utils.class.php';
		$props = array();

		// Article Component Selector.
		$articleComponent1 = new PropertyInfo();
		$articleComponent1->Name = 'C_WORDPRESS_PF_MESSAGE'; // Name for a File / ArticleComponent should be generated. (d_ ? )
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
		$articleComponent1->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $articleComponent1;

		$articleComponentSelector1 = new PropertyInfo();
		$articleComponentSelector1->Name = 'C_WORDPRESS_PF_MESSAGE_SEL';
		$articleComponentSelector1->DisplayName = 'Post';
		$articleComponentSelector1->Category = null;
		$articleComponentSelector1->Type = 'articlecomponentselector';
		$articleComponentSelector1->MinValue = 1; // One component.
		$articleComponentSelector1->MaxValue = 1; // One component.
		$articleComponentSelector1->ValueList = null;
		$articleComponentSelector1->AdminUI = false;
		$articleComponentSelector1->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$articleComponentSelector1->PropertyValues = array();
		$articleComponentSelector1->Widgets = array($articleComponent1);
		$props[] = $articleComponentSelector1;

		$albumDescriptionComponent = new PropertyInfo();
		$albumDescriptionComponent->Name = 'C_WORDPRESS_POST_TITLE';
		$albumDescriptionComponent->DisplayName = 'Title';
		$albumDescriptionComponent->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$albumDescriptionComponent->Type = 'string';
		$albumDescriptionComponent->DefaultValue = null;
		$albumDescriptionComponent->PropertyValues = array(new PropertyValue('appl./incopyicml', '.wcml', 'Format'));
		$albumDescriptionComponent->Widgets = null;
		$albumDescriptionComponent->AdminUI = false;
		$albumDescriptionComponent->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $albumDescriptionComponent;

		// *** single image ***
		// File Selector Template Properties.

		// File: C_WORDPRESS_MULTI_IMAGES_FILE
		$fileFeaturedImagePropertyInfo = new PropertyInfo();
		$fileFeaturedImagePropertyInfo->Name = 'C_WORDPRESS_FEATURED_IMAGE_FILE';
		$fileFeaturedImagePropertyInfo->DisplayName = 'Selected image';
		$fileFeaturedImagePropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileFeaturedImagePropertyInfo->Type = 'file';
		$fileFeaturedImagePropertyInfo->DefaultValue = null;
		$fileFeaturedImagePropertyInfo->PropertyValues = self::getMediaPropertyValues();
		$fileFeaturedImagePropertyInfo->Widgets = null;
		$fileFeaturedImagePropertyInfo->AdminUI = false;
		$fileFeaturedImagePropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $fileFeaturedImagePropertyInfo;

		// FileSelector: C_WORDPRESS_MULTI_IMAGES
		$selectorFeaturedImagePropertyInfo = new PropertyInfo();
		$selectorFeaturedImagePropertyInfo->Name = 'C_WORDPRESS_FEATURED_IMAGE';
		$selectorFeaturedImagePropertyInfo->DisplayName = 'Featured image';
		$selectorFeaturedImagePropertyInfo->Category = null;
		$selectorFeaturedImagePropertyInfo->Type = 'fileselector';
		$selectorFeaturedImagePropertyInfo->MinValue = 0; // Empty allowed.
		$selectorFeaturedImagePropertyInfo->MaxValue = 1; // One images allowed.
		$selectorFeaturedImagePropertyInfo->ValueList = null;
		$selectorFeaturedImagePropertyInfo->AdminUI = false;
		$selectorFeaturedImagePropertyInfo->Widgets = array( $fileFeaturedImagePropertyInfo );
		$selectorFeaturedImagePropertyInfo->PropertyValues = array();
		$selectorFeaturedImagePropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $selectorFeaturedImagePropertyInfo;

		// *** MultipleImages ***
		// File Selector Template Properties.

		// File: C_WORDPRESS_MULTI_IMAGES_FILE
		$fileImagePropertyInfo = new PropertyInfo();
		$fileImagePropertyInfo->Name = 'C_WORDPRESS_MULTI_IMAGES_FILE';
		$fileImagePropertyInfo->DisplayName = 'Selected Image';
		$fileImagePropertyInfo->Category = null; // Make sure the Category is always empty, we should let Client resolve it.
		$fileImagePropertyInfo->Type = 'file';
		$fileImagePropertyInfo->DefaultValue = null;
		$fileImagePropertyInfo->PropertyValues = self::getMediaPropertyValues();
		$fileImagePropertyInfo->Widgets = null;
		$fileImagePropertyInfo->AdminUI = false;
		$fileImagePropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $fileImagePropertyInfo;

		// FileSelector: C_WORDPRESS_MULTI_IMAGES
		$selectorImagePropertyInfo = new PropertyInfo();
		$selectorImagePropertyInfo->Name = 'C_WORDPRESS_MULTI_IMAGES';
		$selectorImagePropertyInfo->DisplayName = 'Gallery';
		$selectorImagePropertyInfo->Category = null;
		$selectorImagePropertyInfo->Type = 'fileselector';
		$selectorImagePropertyInfo->MinValue = 0; // Empty allowed.
		$selectorImagePropertyInfo->MaxValue = null; // Many images allowed.
		$selectorImagePropertyInfo->ValueList = null;
		$selectorImagePropertyInfo->AdminUI = false;
		$selectorImagePropertyInfo->Widgets = null;
		$selectorImagePropertyInfo->PropertyValues = array();
		$selectorImagePropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $selectorImagePropertyInfo;

		//  ** Field properties **
		// Date/Time: C_WORDPRESS_PUBLISH_DATE
		$datePropertyInfo = new PropertyInfo();
		$datePropertyInfo->Name = 'C_WORDPRESS_PUBLISH_DATE';
		$datePropertyInfo->DisplayName = 'Publish Date';
		$datePropertyInfo->Category = null;
		$datePropertyInfo->Type = 'datetime';
		$datePropertyInfo->AdminUI = false;
		$datePropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $datePropertyInfo;

		// Bool: C_WORDPRESS_STICKY
		$stickyPropertyInfo = new PropertyInfo();
		$stickyPropertyInfo->Name = 'C_WORDPRESS_STICKY';
		$stickyPropertyInfo->DisplayName = 'Stick to Front Page';
		$stickyPropertyInfo->Category = null;
		$stickyPropertyInfo->Type = 'bool';
		$stickyPropertyInfo->AdminUI = false;
		$stickyPropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $stickyPropertyInfo;

		// Bool: C_WORDPRESS_ALLOW_COMMENTS
		$commentPropertyInfo = new PropertyInfo();
		$commentPropertyInfo->Name = 'C_WORDPRESS_ALLOW_COMMENTS';
		$commentPropertyInfo->DisplayName = 'Allow Comments';
		$commentPropertyInfo->Category = null;
		$commentPropertyInfo->Type = 'bool';
		$commentPropertyInfo->AdminUI = false;
		$commentPropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $commentPropertyInfo;

		// Multiline: C_WORDPRESS_EXCERPT
		$excerptPropertyInfo = new PropertyInfo();
		$excerptPropertyInfo->Name = 'C_WORDPRESS_EXCERPT';
		$excerptPropertyInfo->DisplayName = 'Excerpt';
		$excerptPropertyInfo->Category = null;
		$excerptPropertyInfo->Type = 'multiline';
		$excerptPropertyInfo->AdminUI = false;
		$excerptPropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $excerptPropertyInfo;

		// String: C_WORDPRESS_SLUG
		$slugPropertyInfo = new PropertyInfo();
		$slugPropertyInfo->Name = 'C_WORDPRESS_SLUG';
		$slugPropertyInfo->DisplayName = 'Slug';
		$slugPropertyInfo->Category = null;
		$slugPropertyInfo->MaxLength = 200;
		$slugPropertyInfo->Type = 'string';
		$slugPropertyInfo->AdminUI = false;
		$slugPropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $slugPropertyInfo;

		// List: C_WORDPRESS_VISIBILITY
		$visibilityPropertyInfo = new PropertyInfo();
		$visibilityPropertyInfo->Name = 'C_WORDPRESS_VISIBILITY';
		$visibilityPropertyInfo->DisplayName = 'Visibility';
		$visibilityPropertyInfo->Category = null;
		$visibilityPropertyInfo->Type = 'list';
		$visibilityPropertyInfo->ValueList = array('Public', 'Private');
		$visibilityPropertyInfo->DefaultValue = 'Public';
		$visibilityPropertyInfo->AdminUI = false;
		$visibilityPropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$props[] = $visibilityPropertyInfo;

		return $props;
	}

	/**
	 * Get properties and fill them with values from WordPress.
	 *
	 * This function can get just 1 property or all properties.
	 * This is needed because otherwise the properties will connect to WordPress every time you do the getProperty().
	 *
	 * @param $props
	 * @return array
	 *
	 * @throws BizException
	 */
	private function getPropertyFromWordPress( $props )
	{
		require_once dirname(__FILE__) . '/WordPress_Utils.class.php';
		$wordpressUtils = new WordPress_Utils();
		// getAllCategoriesAndFormats() is needed here because else the getAllCategoriesAndFormats() wil be called every time we do a getProperty()
		$sites = $wordpressUtils->getAllCategoriesAndFormats();

		if( $sites ) foreach( $sites as $normalizedSiteName => $categoriesAndFormats ){
			// ** Categories widget **

			// Categories widget
			$categoriesPropertyInfo = new PropertyInfo();
			$categoriesPropertyInfo->Name = 'C_WORDPRESS_CAT_' . strtoupper($normalizedSiteName);
			$categoriesPropertyInfo->DisplayName = 'Categories';
			$categoriesPropertyInfo->Category = null;
			$categoriesPropertyInfo->Type = 'multilist';
			$categoriesPropertyInfo->ValueList = $categoriesAndFormats['categories'];
			$categoriesPropertyInfo->AdminUI = false;
			$categoriesPropertyInfo->PublishSystem = 'WordPress';
			$props[] = $categoriesPropertyInfo;

			// ** Format widget **
			// Format widget
			$formatPropertyInfo = new PropertyInfo();
			$formatPropertyInfo->Name = 'C_WORDPRESS_FORMAT_' . strtoupper($normalizedSiteName);
			$formatPropertyInfo->DisplayName = 'Format';
			$formatPropertyInfo->Category = null;
			$formatPropertyInfo->Type = 'list';
			$formatPropertyInfo->ValueList = $categoriesAndFormats['formats'];;
			$formatPropertyInfo->DefaultValue = 'Standard';
			$formatPropertyInfo->AdminUI = false;
			$formatPropertyInfo->PublishSystem = 'WordPress';
			$props[] = $formatPropertyInfo;

			// ** Tags widget**
			$suggestionEntity = $wordpressUtils->getEnterpriseSuggestionEntity();

			// Multiline: C_WORDPRESS_TAGS
			$tagsPropertyInfo = new PropertyInfo();
			$tagsPropertyInfo->Name = 'C_WORDPRESS_TAGS_' . strtoupper($normalizedSiteName);
			$tagsPropertyInfo->DisplayName = 'Tags';
			$tagsPropertyInfo->Category = null;
			$tagsPropertyInfo->Type = 'multistring';
			$tagsPropertyInfo->TermEntity = 'wordpress_tags_' . $normalizedSiteName;
			$tagsPropertyInfo->AutocompleteProvider = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
			$tagsPropertyInfo->AdminUI = false;
			$tagsPropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;

			if( $suggestionEntity ) {
				$tagsPropertyInfo->SuggestionEntity = $suggestionEntity;
			}
			$props[] = $tagsPropertyInfo;
		}
		return $props;
	}

	/**
	 * Return the other Properties
	 *
	 * @return array
	 */
	private function getOtherPropertyDefinitions()
	{
		require_once dirname(__FILE__) . '/WordPress_Utils.class.php';
		
		// The following properties are applicable for ANY ObjectType.

		// FileSelector: C_WORDPRESS_IMAGE_DESCRIPTION
		$descriptionImagePropertyInfo = new PropertyInfo();
		$descriptionImagePropertyInfo->Name = 'C_WORDPRESS_IMAGE_DESCRIPTION';
		$descriptionImagePropertyInfo->DisplayName = 'Description';
		$descriptionImagePropertyInfo->Category = null;
		$descriptionImagePropertyInfo->Type = 'multiline';
		$descriptionImagePropertyInfo->MinValue = 0; // Empty allowed.
		$descriptionImagePropertyInfo->MaxValue = null;
		$descriptionImagePropertyInfo->ValueList = null;
		$descriptionImagePropertyInfo->AdminUI = false;
		$descriptionImagePropertyInfo->Widgets = null;
		$descriptionImagePropertyInfo->PropertyValues = array();
		$descriptionImagePropertyInfo->PublishSystem = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
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
		// Image
		$properties[] = new PropertyValue('image/png', '.png', 'Format');
		$properties[] = new PropertyValue('image/jpeg', '.jpg', 'Format');
		return $properties;
	}

	/**
	 * See CustomObjectMetaData_EnterpriseConnector::collectCustomProperties function header.
	 *
	 * @param bool $coreInstallation
	 * @return array
	 */
	public function collectCustomProperties( $coreInstallation )
	{
		$props = array();
		// Because we provide an admin page that imports custom object properties definition,
		// we bail out when the core server is gathering and installing all custom properties
		// during generic installation procedure such as running the Server Plug-ins page.
		if( $coreInstallation ) {
			return $props;
		}

		$props[0]['PublishForm'] = $this->getCachedProps();
		$props[0][0] = self::getOtherPropertyDefinitions();
		return $props;
	}

	/**
	 * Get the cached props
	 *
	 * The props are being cached because else the import calls the same functions multiple times.
	 *
	 * @return array
	 */
	public function getCachedProps()
	{
		static $cachedProps;
		if( !$cachedProps ) {
			$cachedProps = self::getPropertyFromWordPress( self::getPublishFormPropertyDefinition() );
		}
		return $cachedProps;
	}

	/**
	 * Returns PropertyInfo of the given $name from the other properties.
	 *
	 * @param string $name The property to search.
	 * @return null|PropertyInfo
	 */
	public function getPropertyFromOthers( $name )
	{
		$props = $this->getOtherPropertyDefinitions();
		foreach( $props as $prop ) {
			if( $prop->Name == $name ) {
				return $prop;
			}
		}

		LogHandler::Log( 'WordPress_Publisher', 'ERROR', ' property ' . $name . ' not found' );
		return null;
	}

	/**
	 * Returns PropertyInfo of the given $name from the cached properties.
	 *
	 * @param string $name The property to search.
	 * @return null|PropertyInfo
	 */
	public function getProperty( $name )
	{
		$props = $this->getCachedProps();
		foreach( $props as $prop ) {
			if( $prop->Name == $name ) {
				return $prop;
			}
		}

		LogHandler::Log( 'WordPress_Publisher', 'ERROR', ' property ' . $name . ' not found' );
		return null;
	}

	public function getPrio() { return self::PRIO_DEFAULT; }
}
