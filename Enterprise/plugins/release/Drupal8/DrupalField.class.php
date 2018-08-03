<?php

/**
 * @since 		v9.0.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * @todo        Implement the Collection Widget handling for Image / File Drupal Fields.
 * @todo        Implement the Term Reference field, it is unclear how to retrieve the terms from within the same caller.
 * @todo        Expand with functionality to return Widgets? (to be used when creating dialogs?)
 *
 * Class which can be used to transform a raw Drupal Field definition into a usable object, provide validation and
 * allow the object to be transformed into a PropertyInfo object for Enterprise.
 */

require_once dirname(__FILE__).'/Utils.class.php';

class DrupalField {
	public $fieldCategory = null; //Contains the category (tab) to which the Field will belong.
	public $pubChannelId = null; //The id of the publication channel used for determining term reference field values.
	public $contentType = null; //The ContentType this field belongs to (In Enterprise, it is transformed into template.)
	public $rawField = null; //The Raw field definition as returned by Drupal.
	public $widgetType = null; //The Drupal field Widget Type.
	public $fileExtensions = null; //The file extensions used for the field by Drupal.
	public $hasTextFilter = false; //Internal property used to determine the presence of a text filter for field creation.
	public $propertyValues = null; //Transformed Drupal data to be stored as Enterprise PropertyValues.
	public $name = null; //The Name of the Drupal field.
	public $type = null; //The Type of the Drupal field.
	public $autocompleteTermEntity = null; //Used to determine the Term Entity for an Autocomplete.
	public $suggestionEntity = null; //Used to determine the Term Entity for a Suggestion service.
	public $required = false; //The Drupal field Required flag.
	public $displayName = null; //The Label of the Drupal field.
	public $id = null; //The Id of the Drupal field.
	public $cardinality = null; // The Cardinality of the Drupal field.
	public $templateId = null; //The Enterprise templateId for the ContentType this field belongs to.
	public $minValue = null; //The Minimum value as defined for some Drupal fields.
	public $maxValue = null; //The Maximum value as defined for some Drupal fields.
	public $maxLength = null; //The Maximum length in bytes / characters as defined for some Drupal fields.
	public $hasDescriptionField = false; //Used for File Drupal fields to specify whether it should have a description.
	public $hasAltTextField = false; //Used for Image Drupal fields to specify whether it should have an alt text field.
	public $hasTitleField = false; //Used for Image Drupal fields to specify whether it should have a title field.
	public $minResolution = null; //The minimum resolution as defined by Image Drupal fields.
	public $maxResolution = null; //The maximum resolution as defined by Image Drupal fields.
	public $hasDisplayField = false; //Used for File Drupal fields to determine if the file should be shown on the node.
	public $hasDisplaySummary = false; //Whether or not the display summary field is checked.
	public $initialHeight = null; //The InitialHeight of the widget, only applicable for certain field types.
	public $sumInitialHeight = null; //The InitialHeight of the summary widget, only applicable for formatted, long summary text field.
	public $displayDefault = false; // Whether or not to display a file by default.
	public $propertyInfoType = null; //Contains the determined PropertyInfo type for the DrupalField.
	public $errors = null; //Contains errors that occurred during the creation of a PropertyInfo object.
	public $hasError = false; //Whether or not any of the $errors has severity = 'error'.
	public $machineName = null; //The machine name of the field.
	public $values = null; // The options for Drupal fields such as lists or RadioButtons.
	public $defaultValues = null; // The default value(s) for the Drupal field.

	/** Constant used for special property PROMOTE. */
	const ENTERPRISE_PROPERTY_PROMOTE = 'C_DIALOG_DRUPAL8_PROMOTE';
	/** Constant used for special property STICKY. */
	const ENTERPRISE_PROPERTY_STICKY = 'C_DIALOG_DRUPAL8_STICKY';
	/** Constant used for special property COMMENTS. */
	const ENTERPRISE_PROPERTY_COMMENTS = 'C_DIALOG_DRUPAL8_COMMENTS';

	const DRUPAL_VALUE_NONE = '- None -'; //Constant used for select values as the first value (if the field is not required). Float/Text/Term Reference.
	const DRUPAL_VALUE_NA = '- Not available -'; //Constant used for radio button values as the first value (if the field is not required). Float/Text/Term Reference.

	const DRUPAL_VALUE_PUBLISH_PRIVATE = 'private'; //Constant used for the publish select box, option private.
	const DRUPAL_VALUE_PUBLISH_PUBLIC = 'public'; //Constant used for the publish select box, public.
	const DRUPAL_REQUIRED_FIELD_ERROR = '(This is a required field. However, it could not be imported.)';

	const DRUPAL_IMG_ALT_TEXT = 'C_DPF_F_IMG_ALT_TEXT'; //Constant used for Alt text for Image file selector images.
	const DRUPAL_IMG_TITLE = 'C_DPF_F_IMG_TITLE'; //Constant used for the Title for Image file selector images.

	/**
	 * Constructs a new DrupalField object.
	 *
	 * @param string|null $templateId The id of the Enterprise Template object corresponding to the content type of this field.
	 * @param int|null $pubChannelId
	 * @param string|null $contentType
	 */
	public function __construct($templateId=null, $pubChannelId=null, $contentType=null )
	{
		$this->pubChannelId = $pubChannelId;
		$this->contentType = $contentType;
		$this->templateId = $templateId;
		$this->clearErrors();
	}

	/**
	 * Sets errors for this object.
	 */
	public function clearErrors()
	{
		$this->hasError = false;
		$this->errors = array();
	}

	/**
	 * Determines the PropertyValues for the DrupalField.
	 *
	 * Widgets of type File / Image use the PropertyValues to describe the filter on the file input.
	 * Text widgets implemented as articlecomponent / articlecomponentselector use it to filter on article.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField for which to determine the PropertyValues.
	 * @return array|null The determined PropertyValues.
	 */
	private static function determinePropertyValues( DrupalField $drupalField )
	{
		$propertyValues = null;
		if ( $drupalField->widgetType == 'file_generic' || $drupalField->widgetType == 'image_image') {
			$file_extensions = $drupalField->fileExtensions;

			if ( !empty( $file_extensions )) {
				$propertyValues = array();
				$rawFilterArray = explode(' ', $file_extensions);
				require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
				if (count($rawFilterArray) > 0) foreach ( $rawFilterArray as $rawValue ) {
					$fileExtension = '.'.$rawValue;
					$mimeType = MimeTypeHandler::fileExt2MimeType($fileExtension);
					$propertyValue = new PropertyValue($mimeType, $fileExtension, 'Format');
					$propertyValues[] = $propertyValue;
				}
			}
		}

		if ( $drupalField->widgetType == 'text_textarea' || $drupalField->widgetType == 'text_textarea_with_summary'
			|| $drupalField->widgetType == 'text_textfield') {

			if ( $drupalField->hasTextFilter) {
				$propertyValues = array();
				$propertyValue = new PropertyValue('application/incopyicml', '.wcml', 'Format');
				$propertyValues[] = $propertyValue;
			}
		}
		return $propertyValues;
	}

	/**
	 * Uses the supplied field definition and template id to create a new DrupalField object.
	 *
	 * Takes the Field definition $field and parses this information into a new DrupalField object. Depending on the
	 * type of object certain attributes may or may not be set for the new object.
	 *
	 * @static
	 * @param array $field An array detailing the drupal field, as returned by the ww_enterprise module.
	 * @param string $templateId The Enterprise object Id for the template that matches the objects content type.
	 * @param int $pubChannelId
	 * @param string $contentType
	 * @return DrupalField The generated DrupalField object.
	 */
	public static function generateFromDrupalFieldDefinition( $field, $templateId, $pubChannelId, $contentType )
	{
		// Implement changes required based on the data from Drupal 8.
		$drupalField = new DrupalField($templateId, $pubChannelId, $contentType);
		$drupalField->id = $field['id'];

		static $cache;
		if( isset( $cache[$drupalField->id][$templateId][$pubChannelId][$contentType] )) {
			// Reset the cached errors here as this errors are from previous validation and
			// the validation did not take place this round, so there should not be any errors logged.
			$cache[$drupalField->id][$templateId][$pubChannelId][$contentType]->clearErrors();
			return $cache[$drupalField->id][$templateId][$pubChannelId][$contentType];
		}

		$drupalField->fieldCategory = 'GeneralFields';
		$drupalField->rawField = $field;
		$drupalField->widgetType = $field['widget_type'];
		$drupalField->hasTextFilter = $field['has_text_filter'];
		$drupalField->name = $field['name'];
		$drupalField->type = $field['type'];
		$drupalField->required = $field['required'];
		$drupalField->displayName = $field['display_name'];
		$drupalField->cardinality = intval($field['cardinality']);
		$drupalField->minValue = $field['min_value'];
		$drupalField->maxValue = $field['max_value'];
		$drupalField->maxLength = $field['max_length'];
		$drupalField->hasDescriptionField = $field['has_description_field'];
		$drupalField->hasAltTextField = $field['has_alt_text'];
		$drupalField->hasTitleField = $field['has_title_field'];
		$drupalField->minResolution = $field['min_resolution'];
		$drupalField->maxResolution = $field['max_resolution'];
		$drupalField->hasDisplayField = $field['has_display_field'];
		$drupalField->hasDisplaySummary = $field['has_display_summary'];
		$drupalField->initialHeight = $field['initial_height'];
		$drupalField->sumInitialHeight = $field['summary_initial_height'];
		$drupalField->displayDefault = $field['display_default'];
		$drupalField->propertyInfoType = $field['property_info_type'];
		$drupalField->fileExtensions = $field['file_extensions'];
		$drupalField->machineName = $field['machine_name'];
		$drupalField->propertyValues = self::determinePropertyValues( $drupalField );

		// Determine and set the Autocomplete Term Entity for the field.
		if ( isset($field['vocabulary_name']) ) { // Autocomplete Entity field.
			$drupalField->autocompleteTermEntity = $field['vocabulary_name'];
		}

		// Determine and set the Suggestion Term Entity for the field.
		if ( isset($field['ww_term_entity']) ) { // Suggestion Entity field.
			$drupalField->suggestionEntity = $field['ww_term_entity'];
		}


		// TODO: Default values.
		// TODO: Selectable values.

		// Enterprise can only store one default value. If an array is passed just take the first entry.
		if ( is_array( $field['default_value']) ) {
			$field['default_value'] = isset($field['default_value'][0]['value']) ? strval($field['default_value'][0]['value']) : null;
		}
		$drupalField->defaultValues = isset( $field['default_value']) ? $field['default_value'] : null;

		// Add or restructure additional information based on the type / widget type of the object.
		$drupalField->values = self::determineValues( $drupalField );

		// Cache the results.
		$cache[$field['id']][$templateId][$pubChannelId][$contentType] = $drupalField;
		return $drupalField;
	}

	/**
	 * Determines the values for fields that have multiple options.
	 *
	 * For fields like select boxes, radio buttons or checkboxes the available values need to be parsed.
	 * For widget types where the values are of no significance return null.
	 *
	 * @param DrupalField $drupalField The DrupalField object being analyzed.
	 * @return array|null A list of values, or null if not found / determined.
	 */
	private static function determineValues( DrupalField $drupalField )
	{
		$values = null; // Default to empty
		$rawField = $drupalField->rawField;

		switch ($drupalField->getWidgetType()) {
			case 'options_buttons':
			case 'options_select':
			case 'comment_default':
				$values = $rawField['list_options'];
				break;
			default :
				break;
		}
		return $values;
	}

	/**
	 * Generates an array of PropertyInfo(s) for this DrupalField object.
	 *
	 * Some fields return in multiple PropertyInfos being generated ( Article Component Selector, File selector, and
	 * Collection), Others may result in a single PropertyInfo.
	 *
	 * If there were errors during validation, they are set on the DrupalField, and an empty array is returned. The
	 * errors can be checked by using the getErrors function.
	 *
	 * @see getErrors();
	 * @param boolean $flattened
	 * @param array $errors
	 * @return array An array of PropertyInfo object(s), or an empty array in case of errors.
	 */
	public function generatePropertyInfo( $flattened = false, $errors )
	{
		static $cache;
		$drupalFieldName = $this->name;
		$flattenedList = $flattened ? 'flattened' : 'tree';
		if( isset( $cache[$this->id][$this->templateId][$this->pubChannelId][$drupalFieldName][$flattenedList] )) {
			return $cache[$this->id][$this->templateId][$this->pubChannelId][$drupalFieldName][$flattenedList];
		}

		$propertyInfo = null;
		$propertyInfos = array();
		$this->clearErrors(); // wipe any previously set errors.

		require_once BASEDIR .'/server/bizclasses/BizAdmPublication.class.php';

		$valid = true;
		if ( $errors ) foreach ( $errors as $key => $errorList) {
			if ($key == $this->machineName) {
					$valid = false;
			}
		}

		if ( $valid ) {
			$propertyInfo = new PropertyInfo();

			// Generate the Custom Property Name. If it is not valid return null. Validation occurs inside the
			// generateCustomPropertyName function.
			$propertyInfo->Name = $this->generateCustomPropertyName();
			if (is_null($propertyInfo->Name)) {
				return $propertyInfos;
			}

			$propertyInfo->DisplayName = $this->displayName;
			$propertyInfo->Category = $this->fieldCategory;
			$propertyInfo->Type = $this->propertyInfoType;
			$propertyInfo->DefaultValue = $this->defaultValues;
			$propertyInfo->ValueList = $this->values;
			$propertyInfo->MinValue = $this->minValue;
			$propertyInfo->MaxValue = $this->maxValue;
			$propertyInfo->MaxLength = $this->maxLength;
			$propertyInfo->PropertyValues = $this->getPropertyValues();
			$propertyInfo->MinResolution = $this->minResolution;
			$propertyInfo->MaxResolution = $this->maxResolution;
			$propertyInfo->AdminUI = false;
			$propertyInfo->PublishSystem = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
			$propertyInfo->TemplateId = $this->templateId;
			$propertyInfo->Required = $this->required; // Needed for Dialogs.

			if( $this->autocompleteTermEntity ) {
				$propertyInfo->TermEntity = $this->autocompleteTermEntity;
				$propertyInfo->AutocompleteProvider = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
				$propertyInfo->PublishSystemId = BizAdmPublication::getPublishSystemIdForChannel( $this->pubChannelId );
			}

			// If the Suggestion Term Entity is set for the field, also set it on the PropertyInfo.
			if (!empty( $this->suggestionEntity ) ) {
				$propertyInfo->SuggestionEntity = $this->suggestionEntity;

				// Set a warning if the TermEntity cannot be handled by the SuggestionProvider.
				if (!$this->validateSuggestionEntity()) {
					$error = $this->createError( $this->name, 'The TermEntity: `' . $this->suggestionEntity
						. '` is not supported by the suggestion provider configured for the channel.'
						, $this->pubChannelId, $this->contentType, 'warn' );
					$this->addError( $error );
				}
			}

			// If the Type is FileSelector, we need to create both a File and a FileSelector PropertyInfo.
			if ($propertyInfo->Type == 'fileselector') {
				$propertyInfoFile = $this->getFileProperty( $propertyInfo );
				if ( !$propertyInfoFile ) {
					return $propertyInfos;
				}

				$subPropertyInfos = $this->determineFileSubPropertyInfos( $flattened );
				if ( !$flattened ) {
					// Add any found subPropertyInfos to the File Widget.
					if ( $subPropertyInfos ) {
						$subWidgets = array();

						foreach ( $subPropertyInfos as $subPropInfo ) {
							$subWidgets[] = new DialogWidget( $subPropInfo );
						}

						$propertyInfoFile->Widgets = $subWidgets;
					}

					$propertyInfo->Widgets = array( new DialogWidget( $propertyInfoFile ) );
				}

				// Remove fields from the FileSelector which should not be recorded there, but in the File.
				$propertyInfo->DefaultValue = null;
				$propertyInfo->ValueList = null;
				$propertyInfo->MaxLength = null;
				$propertyInfo->PropertyValues = null;
				$propertyInfo->MinResolution = null;
				$propertyInfo->MaxResolution = null;

				// FileSelectors have a min value of 1. The max value is determined by means of the cardinality. If the
				// cardinality is not unlimited we set the actual number of selectable files, otherwise we leave it empty.
				$propertyInfo->MinValue = 1;
				$propertyInfo->MaxValue = ($this->cardinality != -1) ? $this->cardinality : '';
			}

			// If the Type is ArticleComponentSelector, we need to create both an ArticleComponent and a
			// ArticleComponentSelector PropertyInfo.
			if ($propertyInfo->Type == 'articlecomponentselector' ) {
				$propertyInfoFile = $this->getArticleComponentProperty( $propertyInfo );
				if ( !$propertyInfoFile ) {
					return $propertyInfos;
				}

				if ( !$flattened ) {
					$propertyInfo->Widgets = array( new DialogWidget( $propertyInfoFile ) );
				}
			}

			if ( $this->hasDisplaySummary ) {
				$propertyInfoSummary = unserialize(serialize($propertyInfo));

				// Update the name
				$propertyInfoSummary->Name = $this->generateCustomPropertyName(null, '_SUM');
				$propertyInfoSummary->DisplayName = 'Summary ('.$propertyInfoSummary->DisplayName.')';

				if (is_null($propertyInfoSummary->Name)) {
					return $propertyInfos;
				}

				if ( $propertyInfoSummary->Type == 'multiline' ) {
					// Make sure this one is added before the 'normal' widget
					$propertyInfos[] = $propertyInfoSummary;
				} else {
					$name = $this->generateCustomPropertyName('articlecomponent', '_SUM');
					if ( $flattened ) {
						$propertyInfoSummaryFile = unserialize(serialize($propertyInfoFile));
						$propertyInfoSummaryFile->Name = $name;
						$propertyInfoSummaryFile->DisplayName = 'Summary ('.$propertyInfoSummaryFile->DisplayName.')';

						$propertyInfos[] = $propertyInfoSummaryFile;
					} else {
						foreach ( $propertyInfoSummary->Widgets as &$widget ) {
							$widget->PropertyInfo->Name = $name;
						}
					}
					$propertyInfos[] = $propertyInfoSummary;
				}
			}
		}

		if( !is_null( $propertyInfo ) ) {
			// Add the PropertyInfo(s).
			$propertyInfos[] = $propertyInfo;
		}

		// If flattened (creating the properties in the database, add the props to a subindex for the PublishForm.
		if ( $flattened ) {
			$flattenedProps = array();
			$flattenedProps['PublishForm'] = $propertyInfos; // Properties for ObjectType 'PublishForm'.
			$flattenedProps['Image'] = array(); // Properties for ObjectType 'Image'.
			$flattenedProps[0] = array(); // Properties for any ObjectType.

			// If Type is a FileSelector, also add the File.
			if ( !is_null($propertyInfo) && isset($propertyInfoFile) && ($propertyInfo->Type == 'fileselector'
				|| $propertyInfo->Type == 'articlecomponentselector')) {

				$flattenedProps['PublishForm'][] = $propertyInfoFile;

				if ($propertyInfo->Type == 'fileselector' && !empty($subPropertyInfos) ) {
					$drupalSubWidgetType = ($this->getWidgetType() == 'image_image')
						? 'Image'
						: 0;

					//$propertyInfos = array_merge( $propertyInfos, $subPropertyInfos );

					foreach ( $subPropertyInfos as $subPropertyInfo ) {
						$flattenedProps[$drupalSubWidgetType][] = $subPropertyInfo;
					}
				}
			}

			$propertyInfos = $flattenedProps;
		}

		$cache[$this->id][$this->templateId][$this->pubChannelId][$drupalFieldName][$flattenedList] = $propertyInfos;
		return $propertyInfos;
	}

	/**
	 * Creates a File widget as subwidget.
	 *
	 * This function returns a File widget. It also sets the properties correctly for the parent FileSelector widget.
	 *
	 * @param PropertyInfo $propertyInfo
	 * @return PropertyInfo
	 */
	private function getFileProperty( $propertyInfo )
	{
		// Generate a File PropertyInfo object to go along with the FileSelector.
		$propertyInfoFile = new PropertyInfo();

		// Attempt to generate the name for the File Property, if it fails return an empty array.
		// A file will have the 'C_DPF_F' prefix instead of 'C_DPF'.
		$propertyInfoFile->Name = $this->generateCustomPropertyName('file');
		if (is_null($propertyInfoFile->Name)) {
			return null;
		}

		$propertyInfoFile->DisplayName = $propertyInfo->DisplayName;
		$propertyInfoFile->Category = $propertyInfo->Category;
		$propertyInfoFile->Type = 'file';
		$propertyInfoFile->MaxLength = $propertyInfo->MaxLength;
		$propertyInfoFile->PropertyValues = $propertyInfo->PropertyValues;
		$propertyInfoFile->AdminUI = false;
		$propertyInfoFile->PublishSystem = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
		$propertyInfoFile->TemplateId = $this->templateId;
		$propertyInfoFile->Required = $propertyInfo->Required; // Needed in Dialogs.

		if ($this->type == 'image') {
			// If the widget for which we create a file is an image,  we need the min and max resolution.
			$propertyInfoFile->MinResolution = $propertyInfo->MinResolution;
			$propertyInfoFile->MaxResolution = $propertyInfo->MaxResolution;
		}

		return $propertyInfoFile;
	}

	/**
	 * Creates a PropertyInfo for the Description sub-widget of a Drupal File widget.
	 *
	 * @return null|PropertyInfo
	 */
	public function createPropertyInfoForDescriptionSubWidget()
	{
		$propertyInfo = null;
		$propertyName = self::generateCustomPropertyName( 'file', '_DES');

		if ( !is_null( $propertyName ) ) {
			$propertyInfo = new PropertyInfo();
			$propertyInfo->DisplayName = 'Description';
			$propertyInfo->Name = $propertyName;

			$propertyInfo->Category = $this->fieldCategory;
			$propertyInfo->Type = 'string';
			$propertyInfo->DefaultValue = '';
			$propertyInfo->MaxLength = 128; // Description field can only contain 128 characters.
			$propertyInfo->AdminUI = false;
			$propertyInfo->PublishSystem = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
			$propertyInfo->TemplateId = $this->templateId;
			$propertyInfo->Required = false; // Needed for Dialogs.
		}

		return $propertyInfo;
	}

	/**
	 * Determines the PropertyInfos of a File (Image or File) subwidget.
	 *
	 * @param bool $flattened Whether to get the PropertyInfo as single values or as a tree.
	 * @return PropertyInfo[] An array of PropertyInfos belonging to a subwidget.
	 */
	private function determineFileSubPropertyInfos( $flattened )
	{
		$propertyInfos = array();
		// Resolve subwidgets and properties for specific types of widgets.
		if ( $this->type == 'image' ) {

				// Check the Alt text field.
				if ( $this->hasAltTextField ) {
					$propertyInfos[] = $this->createPropertyInfoForAlternateTextSubWidget();
				}

				// Check the Title field.
				if ( $this->hasTitleField ) {
					$propertyInfos[] = $this->createPropertyInfoForTitleSubWidget();
				}
		} elseif ( $this->type == 'file' ) {

				// Check the Display field.
				if ( $this->hasDisplayField ) {
					$propertyInfos[] = $this->createPropertyInfoForDisplaySubWidget( $this->displayDefault );
				}

				// Check the Description Field.
				if ( $this->hasDescriptionField ) {
					$propertyInfos[] = $this->createPropertyInfoForDescriptionSubWidget();
			}
		}

		// Add standard Properties for the Name and the Format,but only for the Dialog creation.
		if ( ($this->type == 'image' || $this->type == 'file') && !$flattened ) {
			$standardProperties = BizProperty::getPropertyInfos();

			// For images add the height and width of the image so ContentStation can compose the resolution.
			if ($this->type == 'image') {
				$propertyInfos[] = $standardProperties['Height'];
				$propertyInfos[] = $standardProperties['Width'];
			}

			$propertyInfos[] = $standardProperties['Format'];
			$propertyInfos[] = $standardProperties['Name'];
		}

		return $propertyInfos;
	}

	/**
	 * Creates a ArticleComponent widget as subwidget.
	 *
	 * This function returns a ArticleComponent widget. It also sets the properties correctly for the parent ArticleComponentSelector widget.
	 *
	 * @param PropertyInfo $propertyInfo
	 * @return PropertyInfo
	 */
	private function getArticleComponentProperty( $propertyInfo )
	{
		// Generate an ArticleComponent PropertyInfo object to go along with the FileSelector.
		$propertyInfoArticleComponent = new PropertyInfo();

		// Attempt to generate the name for the ArticleComponent Property, if it fails return an empty array.
		// An ArticleComponent will have the 'C_DPFF_' prefix instead of 'C_DPF'.
		$propertyInfoArticleComponent->Name = $this->generateCustomPropertyName( 'articlecomponent' );
		if (is_null($propertyInfoArticleComponent->Name)) {
			return null;
		}

		$propertyInfoArticleComponent->DisplayName = $propertyInfo->DisplayName;
		$propertyInfoArticleComponent->Category = $propertyInfo->Category;
		$propertyInfoArticleComponent->Type = 'articlecomponent';
		$propertyInfoArticleComponent->PropertyValues = $propertyInfo->PropertyValues;
		$propertyInfoArticleComponent->AdminUI = false;
		$propertyInfoArticleComponent->PublishSystem = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
		$propertyInfoArticleComponent->TemplateId = $this->templateId;
		$propertyInfoArticleComponent->Required = $propertyInfo->Required; // Needed in Dialogs.

		// Remove fields from the ArticleComponentSelector which should not be recorded there, but in the component.
		$propertyInfo->DefaultValue = null;
		$propertyInfo->ValueList = null;
		$propertyInfo->MaxLength = null;
		$propertyInfo->PropertyValues = null;
		$propertyInfo->MinResolution = null;
		$propertyInfo->MaxResolution = null;

		// ArticleComponentSelectors have a max and min value of 1 for Drupal, so force them.
		$propertyInfo->MaxValue = 1;
		$propertyInfo->MinValue = 1;

		return $propertyInfoArticleComponent;
	}

	/**
	 * Checks if the Suggestion  Entity can be handled by the Suggestion provider as defined on the Channel.
	 *
	 * If the ww_term_entity is set for the field data then the provider is retrieved from the channel, and a check
	 * is performed to see if the listed term entity is supported by the provider. If the term does not need to be
	 * handled, true is returned.
	 *
	 * @return bool Whether or not the term is supported by the provider.
	 */
	private function validateSuggestionEntity()
	{
		$valid = true;

		if (!empty( $this->suggestionEntity )) {
			// Get the channel for the template.
			require_once BASEDIR . '/server/dbclasses/DBAdmPubChannel.class.php';
			$channel = DBAdmPubChannel::getPubChannelObj( $this->pubChannelId );

			if ( $channel ) {
				// Check if the TermEntity can be handled by the provider.
				require_once BASEDIR . '/server/bizclasses/BizAutoSuggest.class.php';
				$channelProvider = $channel->SuggestionProvider;
				if ( !empty( $channelProvider ) ) {
					$valid = BizAutoSuggest::canSuggestionProviderHandleEntity($channelProvider, $this->suggestionEntity );
				}
			}
		}
		return $valid;
	}

	/**
	 * Generates the Custom Property name based on the provided parameters.
	 *
	 * The generated name is validated prior to being returned.
	 *
	 * @see validateCustomPropertyName()
	 * @param null|string $type A specific WidgetType for which to generate the name.
	 * @param string $subWidgetPrefix
	 * @return null|string The CustomProperty Name, or null if the generated name was not valid.
	 */
	private function generateCustomPropertyName( $type = null, $subWidgetPrefix = '' )
	{
		// Determine a name prefix.
		switch ($type) {
			case 'articlecomponent' :
			case 'file' :
				$stringPrefix = 'C_DPF_F_';
				break;
			default :
				$stringPrefix = 'C_DPF_';
				break;
		}

		// Sanitize the Drupal field name.
		$prefix = 'field_';
		$drupalFieldName = $this->name;
		if( substr( $drupalFieldName, 0, strlen( $prefix ) ) == $prefix ) {
			$drupalFieldName = substr( $drupalFieldName, strlen( $prefix ) ); // Strip off 'field_'
		}

		$drupalFieldName = strtoupper($drupalFieldName); // Ensure the name will be in uppercase.

		// Determine the new name.
		$name = $stringPrefix . $this->templateId . '_' . $this->id . $subWidgetPrefix . '_' . $drupalFieldName;
			$name = substr($name, 0, 30);

		// validate the Name.
		if (!$this->validateCustomPropertyName( $name, $stringPrefix )) {
			$severity = $this->required ? 'error' : 'warn';
			$requiredFieldMsg = $this->required ? self::DRUPAL_REQUIRED_FIELD_ERROR : '';
			$error = self::createError( $this->name,
				'The generated Property name did not pass validation. Entered name: \'' . $name . '\'. ' . $requiredFieldMsg,
				$this->pubChannelId, $this->contentType, $severity );
			$this->addError( $error );
			return null;
		}
		return $name;
	}

	/**
	 * Determines special properties tied to the template id.
	 *
	 * For certain content type specific properties PropertyInfos need to be created:
	 *
	 * - The content type's sticky setting.
	 * - The content type's promote setting.
	 * - The content type's title setting. ( Can be hidden in the Drupal 8 Content Type )
	 * - The content type's status setting.
	 *
	 * @static
	 * @param int|string $templateId The id of the template for which to create a PropertyInfo.
	 * @param array $rawSpecialFieldValues The raw field definition for the content type properties.
	 * @param int $pubChannelId
	 * @param string $contentType
	 * @return PropertyInfo[] An array of PropertyInfo objects.
	 */
	public function getSpecialPropertyInfos($templateId, $rawSpecialFieldValues, $pubChannelId, $contentType )
	{
		static $cache;
		if( isset( $cache[$templateId]['rawSpecialFieldValues'][$pubChannelId][$contentType] )) {
			return $cache[$templateId]['rawSpecialFieldValues'][$pubChannelId][$contentType];
		}

		$properties = array();
		$namePrefix = 'C_DPF_' . $templateId;
		$pattern = "/^C_DPF_[0-9]+_[A-Z0-9_]{0,}$/";

		$basicProperties = array();
		// Omit the title field if it was not sent along from Drupal 8 (Set to disabled on the Content Type).
		if (isset( $rawSpecialFieldValues['title'])) {
			$basicProperties['title'] = substr($namePrefix . '_TITLE', 0, 30);
		}
		$basicProperties['promote'] = substr($namePrefix . '_PROMOTE', 0, 30);
		$basicProperties['sticky'] = substr($namePrefix . '_STICKY', 0, 30);
		$basicProperties['status'] = substr($namePrefix . '_PUBLISH', 0, 30);

		foreach ($basicProperties as $prop => $propertyName) {
			$requiredFieldMsg = ( $rawSpecialFieldValues[$prop]['required'] ) ? self::DRUPAL_REQUIRED_FIELD_ERROR : '';
			$severity = ( $rawSpecialFieldValues[$prop]['required'] ) ? 'error' : 'warn';

			if (preg_match($pattern, $propertyName)) {
				$propertyKey = null;
				$propertyInfo = new PropertyInfo();
				$propertyInfo->Name = $propertyName;
				$propertyInfo->Type = $rawSpecialFieldValues[$prop]['property_info_type'];
				$propertyInfo->DefaultValue = $rawSpecialFieldValues[$prop]['default_value'];
				$propertyInfo->DisplayName = $rawSpecialFieldValues[$prop]['display_name'];
				$propertyInfo->Category = '';
				$propertyInfo->Required = $rawSpecialFieldValues[$prop]['required'];
				$propertyInfo->AdminUI = false; // Do not show the property in the admin ui.
				$propertyInfo->PublishSystem = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
				$propertyInfo->TemplateId = $templateId;
				if ( $prop == 'title' ) {
					$propertyInfo->MaxLength = $rawSpecialFieldValues[$prop]['max_length'];
					$propertyKey = '_TITLE';
				} elseif ( $prop == 'status') {
					$propertyInfo->ValueList = $rawSpecialFieldValues[$prop]['list_options'];
					$propertyKey = '_PUBLISH';
				} else {
					$propertyKey = $prop;
				}
				$properties[$propertyKey] = $propertyInfo;
			} else {
				$error = self::createError( $prop, 'Invalid property name: \''
					. $propertyName	. '\'.'. $requiredFieldMsg, $pubChannelId, $contentType, $severity );
				$this->addError( $error );
			}
		}
		$cache[$templateId]['rawSpecialFieldValues'][$pubChannelId][$contentType] = $properties;
		return $properties;
	}


	/**
	 * Validates a Custom Property name for Drupal
	 *
	 * Validates the Property name against the following business rules:
	 *
	 * Naming pattern: C_{prefix}_{template_id}_{drupal_field_id}_{drupal_field_name}
	 *  - May not be longer than 30 characters in total due to database restrictions on smart_(deleted)objects.
	 *  - Should start with the prefix `C_DPF_` for standard properties, can be changed by means of the prefix param.
	 *    ArticleComponents / Files are generated automatically and need a prefix, for these use 'C_DPFF_'.
	 *  - {template_id}: The Enterprise template id, may only be an integer, (positive).
	 *  - {drupal_field_id}: The id of the field as used in Drupal, may only be an integer, (positive).
	 *  - {drupal_field_name}: Uppercase field name as used in drupal, with spaces stripped and possibly shortened to
	 *    the remaining available length as to not surpass the maximum 30 character length of the property name. Under-
	 *    scores and numbers are allowed here.
	 *
	 * @param string $name The name to be validated.
	 * @param string $prefix The Prefix to test against.
	 * @return bool Whether the name is valid or not.
	 */
	private function validateCustomPropertyName( $name, $prefix )
	{
		// Name should not surpass 30 characters in total length.
		if (strlen($name) > 30) {
			return false;
		}

		// Name should match business rules, see function block.
		$pattern = "/^" . $prefix . "[0-9]+_[0-9]+_[A-Z0-9_]{0,}$/";
		return preg_match($pattern, $name);
	}

	/**
	 * Returns the DrupalField Type.
	 *
	 * The type matches the field setting in the fields display setting.
	 *
	 * @return null|string The Drupal Field type.
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Sets the Drupal Widget type.
	 *
	 * Matches the field setting as used for the fields widget part.
	 *
	 * @param string|null $widgetType The type of the field widget.
	 * @return void.
	 */
	public function setWidgetType($widgetType)
	{
		$this->widgetType = $widgetType;
	}

	/**
	 * Returns the Drupal Widget type.
	 *
	 * Matches the field setting as used for the field widget part.
	 *
	 * @return null|string The Widget Type.
	 */
	public function getWidgetType()
	{
		return $this->widgetType;
	}

	/**
	 * Sets the PropertyValues for this field.
	 *
	 * The PropertyValues are used for example with files / articlecomponents to specify the filter for the file
	 * inputs.
	 *
	 * @param null|PropertyValue[] $propertyValues The PropertyValues.
	 * @return void.
	 */
	public function setPropertyValues($propertyValues)
	{
		$this->propertyValues = $propertyValues;
	}

	/**
	 * Return the PropertyValues as defined for this field.
	 *
	 * The PropertyValues are used for example with files / articlecomponents to specify the filter for the file
	 * inputs.
	 *
     * @return null|PropertyValue[] The PropertyValues for this field.
     */
	public function getPropertyValues()
	{
		return $this->propertyValues;
	}

	/**
	 * Creates and returns a new error (array) that can be added to the report for this DrupalField by caller.
	 *
	 * An error message consists of:
	 *      array(
	 *              'field_name' => $fieldName,
	 *              'message' => $errorMessage,
	 *              'severity' => $severity
	 *           )
	 *
	 * @param string $fieldName The name of the field (DrupalField) for which to track an error.
	 * @param string $errorMessage Localized and human readable error or warning.
	 * @param int $pubChannelId
	 * @param string $contentType
	 * @param string $severity 'warn' or 'error'
	 * @return array
	 */
	private static function createError( $fieldName, $errorMessage, $pubChannelId, $contentType, $severity = 'warn' )
	{
		return array(
			'pubchannelid' => $pubChannelId,
			'content_type' => $contentType,
			'field_name' => $fieldName,
			'message' => $errorMessage,
			'severity' => $severity,
		);
	}

	/**
	 * Add one error to the error report of this DrupalField.
	 *
	 * @see createError()
	 * @param array $error
	 */
	public function addError( $error )
	{
		if( $error['severity'] == 'error' ) {
			$this->hasError = true;
		}

		$this->errors[] = $error;
	}

	/**
	 * Returns the full error report for this DrupalField.
	 *
	 * Errors are structured in an array of errors.
	 *
	 * @see createError()
	 * @return array|null An array of errors (if set).
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Whether or not any of the $this->errors has severity = 'error'.
	 *
	 * @see createError()
	 * @return bool
	 */
	public function hasError()
	{
		return $this->hasError;
	}

	/**
	 * Creates a PropertyInfo for the Alternate Text sub-widget of a Drupal Image widget.
	 *
	 * @return null|PropertyInfo
	 */
	public function createPropertyInfoForAlternateTextSubWidget()
	{
		$propertyInfo = null;

		// Unlike normal properties which will get a DB column per property for a specific Drupal 8 field, the Alt Text
		// on an image file selector is supposed to use a single field for the whole Drupal 8 integration. This means
		// that a single DB column will be responsible for storing the Alt text for an image and that this exact same
		// alt text will be used for the image across all used templates. This is by design.
		$propertyName = self::DRUPAL_IMG_ALT_TEXT;

		$propertyInfo = new PropertyInfo();
		$propertyInfo->DisplayName = 'Alternate text';
		$propertyInfo->Name = $propertyName;

		$propertyInfo->Category = $this->fieldCategory;
		$propertyInfo->Type = 'string';
		$propertyInfo->DefaultValue = '';
		$propertyInfo->MaxLength = 255;
		$propertyInfo->AdminUI = false;
		$propertyInfo->PublishSystem = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
		$propertyInfo->Required = false; // Needed for Dialogs.

		return $propertyInfo;
	}

	/**
	 * Creates a PropertyInfo for the Title sub-widget of a Drupal Image widget.
	 *
	 * @return null|PropertyInfo
	 */
	public function createPropertyInfoForTitleSubWidget()
	{
		$propertyInfo = null;

		// Unlike normal properties which will get a DB column per property for a specific Drupal 8 field, the Title
		// on an image file selector is supposed to use a single field for the whole Drupal 8 integration. This means
		// that a single DB column will be responsible for storing the Title for an image and that this exact same
		// title will be used for the image across all used templates. This is by design.
		$propertyName = self::DRUPAL_IMG_TITLE;

		$propertyInfo = new PropertyInfo();
		$propertyInfo->DisplayName = 'Title';
		$propertyInfo->Name = $propertyName;

		$propertyInfo->Category = $this->fieldCategory;
		$propertyInfo->Type = 'string';
		$propertyInfo->DefaultValue = '';
		$propertyInfo->MaxLength = 255;
		$propertyInfo->AdminUI = false;
		$propertyInfo->PublishSystem = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
		$propertyInfo->Required = false; // Needed for Dialogs.

		return $propertyInfo;
	}

	/**
	 * Creates a PropertyInfo for the Display sub-widget of a Drupal File widget.
	 *
	 * @param bool $displayDefault
	 * @return null|PropertyInfo
	 */
	public function createPropertyInfoForDisplaySubWidget( $displayDefault )
	{
		$propertyInfo = null;
		$propertyName = self::generateCustomPropertyName( 'file', '_DIS');

		if ( !is_null( $propertyName ) ) {
			$propertyInfo = new PropertyInfo();
			$propertyInfo->DisplayName = 'Include file in display';
			$propertyInfo->Name = $propertyName;
			$propertyInfo->Category = $this->fieldCategory;
			$propertyInfo->Type = 'bool';
			// Determine the Default value for the Display toggle.
			$propertyInfo->DefaultValue = ($displayDefault) ? 'true' : '';
			$propertyInfo->AdminUI = false;
			$propertyInfo->PublishSystem = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
			$propertyInfo->TemplateId = $this->templateId;
			$propertyInfo->Required = false; // Needed for Dialogs.
		}
		return $propertyInfo;
	}
}