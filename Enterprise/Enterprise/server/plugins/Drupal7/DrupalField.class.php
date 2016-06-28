<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
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
	/** @var null|string $widgetType The Drupal field Widget Type. */
	private $widgetType;
	/** @var bool $required The Drupal field Required flag. */
	private $required;
	/** @var null|string|string[] $defaultValues The default value(s) for the Drupal field. */
	private $defaultValues;
	/** @var null|string $type The Type of the Drupal field. */
	private $type;
	/** @var null|string $name The Name of the Drupal field. */
	private $name;
	/** @var null|string $cardinality The Cardinality of the Drupal field. */
	private $cardinality;
	/** @var null|string $displayName The Label of the Drupal field. */
	private $displayName;
	/** @var null|string $id The Id of the Drupal field. */
	private $id;
	/** @var null|string $templateId The Enterprise templateId for the ContentType this field belongs to. */
	private $templateId;
	/** @var null|array $values The options for Drupal fields such as lists or RadioButtons. */
	private $values;
	/** @var null|array $rawField The Raw field definition as returned by Drupal. */
	private $rawField;
	/** @var null|string $minValue The Minimum value as defined for some Drupal fields. */
	private $minValue;
	/** @var null|string $maxValue The Maximum value as defined for some Drupal fields. */
	private $maxValue;
	/** @var null|PropertyValue[] $propertyValues Transformed Drupal data to be stored as Enterprise PropertyValues. */
	private $propertyValues;
	/** @var null|string $maxLength The Maximum length in bytes as defined for some Drupal fields. */
	private $maxLength;
	/** @var bool $hasDescriptionField Used for File Drupal fields to specify whether it should have a description. */
	private $hasDescriptionField;
	/** @var bool $hasAltTextField Used for Image Drupal fields to specify whether it should have an alt text field. */
	private $hasAltTextField;
	/** @var bool $hasTitleField used for Image Drupal fields to specify whether it should have a title field. */
	private $hasTitleField;
	/** @var null|string $minResolution The minimum resolution as defined by Image Drupal fields. */
	private $minResolution;
	/** @var null|string $maxResolution The maximum resolution as defined by Image Drupal fields. */
	private $maxResolution;
	/** @var null|array $errors Contains errors that occurred during the creation of a PropertyInfo object. */
	private $errors;
	/** @var bool $hasError Whether or not any of the $errors has severity = 'error'. */
	private $hasError;
	/** @var null|string $propertyInfoType Contains the determined PropertyInfo type for the DrupalField. */
	private $propertyInfoType;
	/** @var null|string $fieldCategory Contains the category (tab) to which the Field will belong. */
	private $fieldCategory;
	/** @var null|string $fieldInfoType Internal property used to determine the actual field type for validation. */
	private $fieldInfoType;
	/** @var bool $hasTextFilter Internal property used to determine the presence of a text filter for field creation. */
	private $hasTextFilter;
	/** @var int|null $pubChannelId The id of the publication channel used for determining term reference field values. */
	private $pubChannelId;
	/** @var string $contentType The ContentType this field belongs to (In Enterprise, it is transformed into template.) */
	private $contentType;
	/** @var int|null $initialHeight The InitialHeight of the widget, only applicable for certain field types. */
	private $initialHeight;
	/** @var bool $hasDisplaySummary Whether or not the display summary field is checked. */
	private $hasDisplaySummary;
	/** @var bool $hasDisplayField Used for File Drupal fields to determine if the file should be shown on the node. */
	private $hasDisplayField;
	/** @var null|string $autocompleteTermEntity Used to determine the Term Entity for an Autocomplete. */
	private $autocompleteTermEntity;
	/** @var null|string $suggestionEntity Used to determine the Term Entity for a Suggestion service. */
	private $suggestionEntity;

	//TODO: Implement the member below when Collection widgets are built in.
	//private $widgets;

	/** Drupal widget type for checkboxes. */
	const DRUPAL_FIELD_WIDGET_TYPE_SINGLE_ON_OFF_CHECKBOX = 'options_onoff';
	/** Drupal widget type for radio buttons. */
	const DRUPAL_FIELD_WIDGET_TYPE_CHECKBOX_RADIOBUTTONS = 'options_buttons';
	/** Drupal widget type for decimal, float and integer numbers. */
	const DRUPAL_FIELD_WIDGET_TYPE_NUMBER = 'number';
	/** Drupal widget type for files. (File/Collection) */
	const DRUPAL_FIELD_WIDGET_TYPE_FILE = 'file_generic';
	/** Drupal widget type for images. (File/Collection) */
	const DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE = 'image_image';
	/** Drupal widget type for select boxes. */
	const DRUPAL_FIELD_WIDGET_TYPE_SELECT = 'options_select';
	/** Drupal widget type for Long Text (Plain/filtered). */
	const DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT = 'text_textarea';
	/** Drupal widget type for Text Area With Summary (plain/filtered). */
	const DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT_SUMMARY = 'text_textarea_with_summary';
	/** Drupal widget type for Text (plain/filtered). */
	const DRUPAL_FIELD_WIDGET_TYPE_TEXT = 'text_textfield';
	/** Drupal widget type for Taxonomy / autocomplete. */
	const DRUPAL_FIELD_WIDGET_TYPE_TAXONOMY_AUTOCOMPLETE = 'taxonomy_autocomplete';
	/** Drupal widget type for Taxonomy / autocomplete, Drupal active tags module. */
	const DRUPAL_FIELD_WIDGET_TYPE_ACTIVE_TAGS_TAXONOMY_AUTOCOMPLETE = 'active_tags_taxonomy_autocomplete';
	/** Drupal widget type for date, Drupal date module. */
	const DRUPAL_FIELD_WIDGET_TYPE_DATE_POPUP = 'date_popup';
	/** Drupal widget type for date (ISO format), Drupal date module. */
	const DRUPAL_FIELD_WIDGET_TYPE_DATE_SELECT = 'date_select';
	/** Drupal widget type for date. Drupal date module. */
	const DRUPAL_FIELD_WIDGET_TYPE_DATE_TEXT = 'date_text';

	/** Enterprise Server PropertyInfo type constant for type: bool. */
	const ENTERPRISE_PROPERTY_TYPE_BOOLEAN = 'bool';
	/** Enterprise Server PropertyInfo type constant for type: list. */
	const ENTERPRISE_PROPERTY_TYPE_LIST = 'list';
	/** Enterprise Server PropertyInfo type constant for type: multistring. */
	const ENTERPRISE_PROPERTY_TYPE_MULTISTRING = 'multistring';
	/** Enterprise Server PropertyInfo type constant for type: string. */
	const ENTERPRISE_PROPERTY_TYPE_STRING = 'string';
	/** Enterprise Server PropertyInfo type constant for type: multiline. */
	const ENTERPRISE_PROPERTY_TYPE_MULTILINE = 'multiline';
	/** Enterprise Server PropertyInfo type constant for type: int. */
	const ENTERPRISE_PROPERTY_TYPE_INT = 'int';
	/** Enterprise Server PropertyInfo type constant for type: double. */
	const ENTERPRISE_PROPERTY_TYPE_DOUBLE = 'double';
	/** Enterprise Server PropertyInfo type constant for type: date. */
	const ENTERPRISE_PROPERTY_TYPE_DATE = 'date';
	/** Enterprise Server PropertyInfo type constant for type: datetime. */
	const ENTERPRISE_PROPERTY_TYPE_DATETIME = 'datetime';
	/** Enterprise Server PropertyInfo type constant for type: multilist. */
	const ENTERPRISE_PROPERTY_TYPE_MULTILIST = 'multilist';
	/** Enterprise Server PropertyInfo type constant for type: fileselector. */
	const ENTERPRISE_PROPERTY_TYPE_FILESELECTOR = 'fileselector';
	/** Enterprise Server PropertyInfo type constant for type: file. */
	const ENTERPRISE_PROPERTY_TYPE_FILE = 'file';
	/** Enterprise Server PropertyInfo type constant for type: articlecomponentselector. */
	const ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENTSELECTOR = 'articlecomponentselector';
	/** Enterprise Server PropertyInfo type constant for type: articlecomponent. */
	const ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENT = 'articlecomponent';
	/** Enterprise Server PropertyInfo type constant for type: collection. */
	const ENTERPRISE_PROPERTY_TYPE_COLLECTION = 'collection';

	/** The Drupal cardinality value for unlimited cardinality. */
	const DRUPAL_CARDINALITY_UNLIMITED = -1;

	/** Constant used for naming the Category (tab) the properties will be eventually shown on. */
	const ENTERPRISE_PROPERTY_CATEGORY = 'GeneralFields';

	/** Constant used for special property PROMOTE. */
	const DRUPAL_PROPERTY_PROMOTE = 'promote';
	/** Constant used for special property STICKY. */
	const DRUPAL_PROPERTY_STICKY = 'sticky';
	/** Constant used for special property COMMENTS. */
	const DRUPAL_PROPERTY_COMMENTS = 'comments';

	/** Constant used for the content type title field. */
	const DRUPAL_PROPERTY_TITLE = '_TITLE';
	/** Constant used for the content type publish field. */
	const DRUPAL_PROPERTY_PUBLISH = '_PUBLISH';

	/** Constant used for special property PROMOTE. */
	const ENTERPRISE_PROPERTY_PROMOTE = 'C_DIALOG_DRUPAL7_PROMOTE';
	/** Constant used for special property STICKY. */
	const ENTERPRISE_PROPERTY_STICKY = 'C_DIALOG_DRUPAL7_STICKY';
	/** Constant used for special property COMMENTS. */
	const ENTERPRISE_PROPERTY_COMMENTS = 'C_DIALOG_DRUPAL7_COMMENTS';

	/** Constant used for select values as the first value (if the field is not required). Float/Text/Term Reference. */
	const DRUPAL_VALUE_NONE = '- None -';
	/** Constant used for radio button values as the first value (if the field is not required). Float/Text/Term Reference. */
	const DRUPAL_VALUE_NA = '- Not available -';

	/** Constant used for the publish select box, option private. */
	const DRUPAL_VALUE_PUBLISH_PRIVATE = 'Private';
	/** Constant used for the publish select box, public. */
	const DRUPAL_VALUE_PUBLISH_PUBLIC = 'Public';

	/** Shared required field error. */
	const DRUPAL_REQUIRED_FIELD_ERROR = '(This is a required field. However, it could not be imported.)';

	/** Constant Used for the Field Type text with summary (ArticleComponentSelector). */
	const DRUPAL_FIF_TEXT_WITH_SUMMARY = 'text_with_summary';
	/** Constant Used for the Field Type boolean (single select / on/off buttons). */
	const DRUPAL_FIF_LIST_BOOLEAN = 'list_boolean';
	/** Constant Used for the Field Type decimal (Decimal numbers). */
	const DRUPAL_FIF_NUMBER_DECIMAL = 'number_decimal';
	/** Constant Used for the Field Type float (Float numbers). */
	const DRUPAL_FIF_NUMBER_FLOAT = 'number_float';
	/** Constant Used for the Field Type file (file selector). */
	const DRUPAL_FIF_FILE = 'file';
	/** Constant Used for the Field Type image (file selector). */
	const DRUPAL_FIF_IMAGE = 'image';
	/** Constant Used for the Field Type integer (Integer numbers). */
	const DRUPAL_FIF_NUMBER_INTEGER = 'number_integer';
	/** Constant Used for the Field Type float select (float select boxes). */
	const DRUPAL_FIF_LIST_FLOAT = 'list_float';
	/** Constant Used for the Field Type integer select (integer select boxes). */
	const DRUPAL_FIF_LIST_INTEGER = 'list_integer';
	/** Constant Used for the Field Type text select (text select boxes). */
	const DRUPAL_FIF_LIST_TEXT = 'list_text';
	/** Constant Used for the Field Type long text (Text box). */
	const DRUPAL_FIF_TEXT_LONG = 'text_long';
	/** Constant Used for the Field Type taxonomy (Select box). */
	const DRUPAL_FIF_TAXONOMY_TERM_REFERENCE = 'taxonomy_term_reference';
	/** Constant Used for the Field Type text (Text box). */
	const DRUPAL_FIF_TEXT = 'text';
	/** Constant used for the Field Type Date time, Drupal date module.*/
	const DRUPAL_FIF_DATETIME  = 'datetime';
	/** Constant used for the Field Type Date iso, Drupal date module.*/
	const DRUPAL_FIF_DATE  = 'date';
	/** Constant used for the Field Type Date Stamp, Drupal date module.*/
	const DRUPAL_FIF_DATESTAMP  = 'datestamp';

	/** Constant used for Alt text for Image file selector images. */
	const DRUPAL_IMG_ALT_TEXT = 'C_DPF_F_IMG_ALT_TEXT';
	/** Constant used for the Title for Image file selector images. */
	const DRUPAL_IMG_TITLE = 'C_DPF_F_IMG_TITLE';

	/**
	 * Constructs a new DrupalField object.
	 *
	 * @param null|string $type The Drupal type for this field.
	 * @param bool $required Whether or not this field is required.
	 * @param array $defaultValues The default value(s) for this field.
	 * @param null|string $widgetType The Drupal widget type for this field.
	 * @param null|string $name The Drupal name for this field.
	 * @param null|integer $cardinality The cardinality for this Drupal field.
	 * @param null|string $displayName The label for this Drupal field.
	 * @param null|string $id The id (field_id) for this Drupal field.
	 * @param null|string $templateId The id of the Enterprise Template object corresponding to the content type of this field.
	 * @param array|string|null $values A list of values from Drupal for fields such as radio buttons or lists.
	 * @param null|array $rawField The raw field definition for this this field as returned by the ww_enterprise module.
	 * @param null|string $minValue The mimimum value for this field as defined by some Drupal fields.
	 * @param null|string $maxValue The maximum value for this field as defined by some Drupal fields.
	 * @param null|PropertyValue[] $propertyValues A list of PropertyValues for enterprise PropertyInfo objects.
	 * @param null|string $maxLength The maximum length (in bytes) that is allowed for this field, as defined by some Drupal fields.
	 * @param bool $hasDescriptionField Whether this field has a description widget or not (fileselector).
	 * @param null|string $minResolution The minimum required resolution for this field f.e. '10x10', used by images.
	 * @param null|string $maxResolution The maximum allowed resolution for this field f.e. '100x100', used by images.
	 * @param bool $hasAltTextField Whether this field has a Alt Text field or not (fileselector).
	 * @param bool $hasTitleField Whether this field has a Title field or not (fileselector).
	 * @param string|null $fieldCategory The Category (Tab) this field belongs to.
	 * @param int $pubChannelId
	 * @param string $contentType
	 * @param int|null $initialHeight The initial height for the field.
	 * @param bool $hasDisplaySummary
	 * @param bool $hasDisplayField Whether or not the field has a display toggle (fileselector).
	 * @param string $autocompleteTermEntity The term entity for autocomplete.
	 * @param string $suggestionEntity The term entity to use for suggestion services.
	 */
	public function __construct($type=null, $required=false, $defaultValues=array(), $widgetType=null, $name=null,
	                            $cardinality=null, $displayName=null, $id=null, $templateId=null, $values=null,
	                            $rawField=null, $minValue=null, $maxValue=null, $propertyValues=null, $maxLength=null,
	                            $hasDescriptionField=false, $minResolution=null, $maxResolution=null, $hasAltTextField=false,
	                            $hasTitleField=false, $fieldCategory=null, $pubChannelId=null, $contentType=null,
	                            $initialHeight=null, $hasDisplaySummary=false, $hasDisplayField=false,
	                            $autocompleteTermEntity=null, $suggestionEntity = null )
	{
		$this->setType( $type );
		$this->setRequired( $required );
		$this->setDefaultValues( $defaultValues );
		$this->setWidgetType( $widgetType );
		$this->setName( $name );
		$this->setCardinality( $cardinality );
		$this->setDisplayName( $displayName );
		$this->setId( $id );
		$this->setTemplateId ( $templateId );
		$this->setValues( $values );
		$this->setRawField ( $rawField );
		$this->setMinValue( $minValue );
		$this->setMaxValue( $maxValue );
		$this->setPropertyValues( $propertyValues );
		$this->setMaxLength( $maxLength );
		$this->setHasDescriptionField( $hasDescriptionField );
		$this->setMinResolution( $minResolution );
		$this->setMaxResolution( $maxResolution );
		$this->setHasTitleField( $hasTitleField );
		$this->setHasAltTextField( $hasAltTextField );
		$this->setHasDisplayField( $hasDisplayField );
		$this->clearErrors();
		$this->setPropertyInfoType( null );
		$this->setHasDisplaySummary( $hasDisplaySummary );
		$this->setAutocompleteTermEntity( $autocompleteTermEntity );
		$this->setSuggestionEntity( $suggestionEntity );

		// Determine the category (Tab the field is going to be shown on.)
		$fieldCategory = (is_null($fieldCategory)) ? self::ENTERPRISE_PROPERTY_CATEGORY : $fieldCategory;
		$this->setFieldCategory( $fieldCategory);

		// Used for determining terms when handling term reference fields.
		$this->pubChannelId = $pubChannelId;

		// Set internal testing properties.
		$this->hasTextFilter = false;

		// Used for Error handling.
		$this->contentType = $contentType;

		// Set the initial height.
		$this->initialHeight = $initialHeight;
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
		$drupalField = new DrupalField();
		static $cache;
		if( isset( $cache[$field['id']][$templateId][$pubChannelId][$contentType] )) {
			// Reset the cached errors here as this errors are from previous validation and
			// the validation did not take place this round, so there should not be any errors logged.
			$cache[$field['id']][$templateId][$pubChannelId][$contentType]->clearErrors();
			return $cache[$field['id']][$templateId][$pubChannelId][$contentType];
		}

		// Set the publication channel ID, needed for
		$drupalField->pubChannelId = $pubChannelId;

		// Set the standard DrupalField values, expected on all objects.
		$drupalField->setRawField($field);
		$drupalField->setName( $field['field_name'] );
		$drupalField->setType( $field['field_info_fields']['type'] );
		$drupalField->setWidgetType( $field['widget']['type'] );

		// Determine and set the Autocomplete Term Entity for the field.
		if ( isset($field['vocabulary_name']) ) { // Autocomplete Entity field.
			$drupalField->setAutocompleteTermEntity( $field['vocabulary_name'] );
		}

		// Determine and set the Suggestion Term Entity for the field.
		if ( isset($field['ww_term_entity']) ) { // Suggestion Entity field.
			$drupalField->setSuggestionEntity( $field['ww_term_entity'] );
		}

		// Determine if a field contains filtered text or not.
		$drupalField->hasTextFilter = $drupalField->determineTextFilterSetting( $drupalField );

		$drupalField->setRequired( ($field['required'] == 1) ? true : false );
		$defaultValues = isset( $field['default_value']) ? $field['default_value'] : null;
		$drupalField->setDefaultValues( $defaultValues );
		$drupalField->setDisplayName( $field['label'] );
		$drupalField->setId( $field['field_id'] );
		$drupalField->setTemplateId( $templateId );
		$drupalField->setCardinality( $field['field_info_fields']['cardinality'] );

		// Add or restructure additional information based on the type / widget type of the object.
		$drupalField->setValues(self::determineValues( $drupalField ));
		$drupalField->setDefaultValues(self::determineDefaultValues( $drupalField ));
		$drupalField->setMinValue(self::determineMinValue( $drupalField ));
		$drupalField->setMaxValue(self::determineMaxValue( $drupalField ));
		$drupalField->setMaxLength(self::determineMaxLength( $drupalField ));
		$drupalField->setHasDescriptionField(self::determineHasDescriptionField( $drupalField ));
		$drupalField->setPropertyValues(self::determinePropertyValues( $drupalField ));
		$drupalField->setMinResolution(self::determineMinResolution( $drupalField ));
		$drupalField->setMaxResolution(self::determineMaxResolution( $drupalField ));
		$drupalField->setInitialHeight(self::determineInitialHeight( $drupalField ));
		$drupalField->setHasAltTextField(self::determineHasAltTextField( $drupalField ));
		$drupalField->setHasTitleField(self::determineHasTitleField( $drupalField ));
		$drupalField->setHasDisplayField(self::determineHasDisplayField( $drupalField ));
		// Determine the internal FieldInfoField type for validations.
		$drupalField->fieldInfoType = $drupalField->determineFieldInfoFieldType( $drupalField );
		$drupalField->contentType = $contentType;
		$drupalField->setPropertyInfoType(self::determinePropertyInfoType( $drupalField ));
		$drupalField->setHasDisplaySummary(self::determineHasDisplaySummary( $drupalField ));

		$cache[$field['id']][$templateId][$pubChannelId][$contentType] = $drupalField;
		return $drupalField;
	}

	/**
	 * Determines the initial height for the widget.
	 *
	 * Only DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT and DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT_SUMMARY can have a initial height.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField Object to be analyzed.
	 * @return int|null The initial height or null.
	 */
	private static function determineInitialHeight( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$initialHeight = null;

		switch ($drupalField->getWidgetType()) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT :
			case self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT_SUMMARY :
				$numberOfRows = (isset($rawField['widget']['settings']['rows']) )
					? intval( $rawField['widget']['settings']['rows'] )
					: 0;
				if ($numberOfRows > 0) {
					$initialHeight = $numberOfRows * 15; // Number of rows times 15 pixels.
				}
				break;
			default:
				break;
		}
		return $initialHeight;
	}

	/**
	 * Determines if the field has a text filter (applies to text type fields.
	 *
	 * Fields of the Drupal Type 'text_default' can have a property called 'text_processing' which
	 * signifies if the field has a filter or not.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField object for which to determine the text filter.
	 * @return bool Whether or not the field has filtered text.
	 */
	private static function determineTextFilterSetting( DrupalField $drupalField )
	{
		$filter = false;
		$rawField = $drupalField->getRawField();

		switch ( $drupalField->getType() ) {
			case self::DRUPAL_FIF_TEXT_LONG :
			case self::DRUPAL_FIF_TEXT :
			case self::DRUPAL_FIF_TEXT_WITH_SUMMARY :
				// Any of the text fields can have a filter on it, we need the filter to determine the Property Type
				// to be generated, so suss out the setting on the rawField, and store it on the object.
				// The 'text_processing' flag determines whether or not there is a filter.
				$filter = ($rawField['settings']['text_processing'] == '1') ? true : false;
		}
		return $filter;
	}

	/**
	 * Determines the FieldInfoField type.
	 *
	 * @return null|string The DrupalField Type.
	 */
	private function determineFieldInfoFieldType( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		return $rawField['field_info_fields']['type'];
	}

	/**
	 * Determines whether or not an Alt Text Field is part of the supplied Drupal Field.
	 *
	 * An Alt Text field is only used in conjunction with Image objects. An image that has a Alt Text Field will be
	 * translated into a Collection/FileSelector widget by Enterprise server.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField Object to be analyzed.
	 * @return bool Whether or not the Drupal field has an Alt Text Field.
	 */
	private static function determineHasAltTextField( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$hasAltTextField = false;

		switch ($drupalField->getWidgetType()) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE :
				$hasAltTextField = ($rawField['settings']['alt_field'] == '1');
				break;
			default:
				break;
		}
		return $hasAltTextField;
	}

	/**
	 * Determines whether or not the display summary field is turned on for the supplied Drupal Field.
	 *
	 * This field can be set in drupal for Long Text and Summary widgets. When it is turned on,
	 * two widgets will be created in the Enterprise system. One for the summary and one for the actual
	 * text of the field.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField Object to be analyzed.
	 * @return bool Whether or not the Drupal field has the display summary field turned on.
	 */
	private static function determineHasDisplaySummary( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$hasDisplaySummary = false;

		if ( $drupalField->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT_SUMMARY
			&& isset( $rawField['settings']['display_summary'] ) ) {
			$hasDisplaySummary = ($rawField['settings']['display_summary'] == true);
		}

		return $hasDisplaySummary;
	}

	/**
	 * Determines whether or not a Title Field is part of the supplied Drupal Field.
	 *
	 * A Title field is only used in conjunction with Image objects. An image that has a Title field will be
	 * translated into a Collection/FileSelector widget by Enterprise server.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField object to be analyzed.
	 * @return bool Whether or not the Drupal field has a Title Field.
	 */
	private static function determineHasTitleField( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$hasTitleField = false;

		switch ($drupalField->getWidgetType()) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE :
				$hasTitleField = ($rawField['settings']['title_field'] == '1');
				break;
			default:
				break;
		}
		return $hasTitleField;
	}

	/**
	 * Determines whether or not a Display Field is part of the supplied Drupal Field.
	 *
	 * A Display field is only used in conjunction with File objects. A File that has a Display field will be
	 * translated into a Collection/FileSelector widget by Enterprise server.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField object to be analyzed.
	 * @return bool Whether or not the Drupal field has a Display Field.
	 */
	private static function determineHasDisplayField( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$hasDisplayField = false;

		switch ($drupalField->getWidgetType()) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_FILE :
				$hasDisplayField = ($rawField['field_info_fields']['settings']['display_field'] == '1');
				break;
			default:
				break;
		}
		return $hasDisplayField;
	}

	/**
	 * Determines the minimum resolution for the DrupalField.
	 *
	 * Only Drupal Image fields have a minimum resolution.
	 *
	 * @param DrupalField $drupalField The DrupalField object being analyzed.
	 * @return null|string The minimum resolution for the field.
	 */
	private static function determineMinResolution( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$minResolution = null;

		// BusinessRule: Images have the Min resolution field.
		if ($drupalField->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE) {
			$minResolution = $rawField['settings']['min_resolution'];
		}
		return $minResolution;
	}

	/**
	 * Determines the maximum resolution for the DrupalField.
	 *
	 * Only Drupal Image fields have a maximum resolution.
	 *
	 * @param DrupalField $drupalField The DrupalField object being analyzed.
	 * @return null|string The maximum resolution for the field.
	 */
	private static function determineMaxResolution( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$maxResolution = null;

		// BusinessRule: Images have the Min resolution field.
		if ($drupalField->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE) {
			$maxResolution = $rawField['settings']['max_resolution'];
		}
		return $maxResolution;
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
		$rawField = $drupalField->getRawField();

		switch ($drupalField->getWidgetType()) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_FILE :
			case self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE :
				// Determine the format filter.
				$rawFilter = $rawField['settings']['file_extensions'];
				if ($rawFilter != '') {
					$propertyValues = array();
					$rawFilterArray = explode(' ', $rawFilter);
					require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';

					// if we only have a single value add it to the propertyValues.
					if (count($rawFilterArray) > 0) foreach ($rawFilterArray as $rawValue) {
						// Determine extension, retrieve the MimeType, and compose the PropertyValue.
						$fileExtension = '.'.$rawValue;
						$mimeType = MimeTypeHandler::fileExt2MimeType($fileExtension);
						// Compose a PropertyValue.
						$propertyValue = new PropertyValue();
						$propertyValue->Display = $fileExtension;
						$propertyValue->Entity = 'Format';
						$propertyValue->Value = $mimeType;
						// Add the PropertyValue.
						$propertyValues[] = $propertyValue;
					}
				}

				// TODO: Collection Block (FILE) widgets need to include a Description field, this field can be triggered
				// TODO: from the field configuration for file by selecting: show description field.

				// TODO: Collection Block (IMAGE) widgets need to include a 'alt text' and 'title' field (either one or
				// TODO: both, depending on the settings. An image without either will be created as FileSelector.)

				break;
			case self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT :
			case self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT_SUMMARY :
			case self::DRUPAL_FIELD_WIDGET_TYPE_TEXT :
				// Business Rule: If this is a filtered field, we need to specify a format, in case of text fields this
				// should match a wcml article.
				if ($drupalField->getHasTextFilter()) {
					$propertyValues = array();
					$propertyValue = new PropertyValue();
					$propertyValue->Display = '.wcml';
					$propertyValue->Entity = 'Format';
					$propertyValue->Value = 'application/incopyicml';
					// Add the PropertyValue.
					$propertyValues[] = $propertyValue;

				}
				break;
			default :
				break;
		}
		return $propertyValues;
	}

	/**
	 * Determines if the field has a description field.
	 *
	 * Only File Widgets can have the Description Field, thus it is the only field we need to check.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField object being analyzed.
	 * @return bool Whether or not the field has a description field.
	 */
	private static function determineHasDescriptionField( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$hasDescriptionField = false;

		switch ($drupalField->getWidgetType()) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_FILE :
				$hasDescriptionField = ($rawField['settings']['description_field'] == '1');
				break;
			default:
				break;
		}
		return $hasDescriptionField;
	}

	/**
	 * Determines the maximum length of a field.
	 *
	 * For fields of type File / Image the maximum length in bytes is determined.
	 * For fields of type TextField, the maximum length in characters is determined.
	 * All other cases return an empty string.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField object for which to determine the max length.
	 * @return string The max length of this field, either empty, a number of bytes or a number of characters.
	 */
	private static function determineMaxLength( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$maxLength = '';

		switch ($drupalField->getWidgetType()) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_FILE :
			case self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE :
				$maxLength = $rawField['settings']['max_filesize'];

				// MaxLength can be left intentionally empty, which means we do not enforce a maximum (drupal site PHP max
				// upload size does apply however, but we do not know that here.
				if ($maxLength != '0' && $maxLength != '') {

					// MaxLength can be in bytes (no suffix), MB (suffix ' MB' or KB (suffix ' KB'), convert to bytes.
					$suffixes = array('' => 1, 'k' => 1024, 'm' => 1048576, ); // KB=*1024 MB=*1024*1024, etc.
					$match = array();
					if (preg_match('/([0-9]+)\s*(k|m)?(b?(ytes?)?)/i', $maxLength, $match)) {
						$maxLength = $match[1] * $suffixes[strtolower($match[2])];
					} else {
						// Business Rule: If the size in Byte cannot be determined, we set the limit to be unlimited.
						$maxLength = '';
					}
				}
				break;
			case self::DRUPAL_FIELD_WIDGET_TYPE_TEXT :
				// Business Rule, only determine the Max Length for non-filtered types.
				if (!$drupalField->getHasTextFilter()) {
					$maxLength = $rawField['field_info_fields']['settings']['max_length'];
				}
			default :
				break;
		}
		return $maxLength;
	}

	/**
	 * Determines the Default Values for a Drupal Field.
	 *
	 * If a field is of type Text and has a textfilter the default values are not determined.
	 * If a field is of a type Number, Select, RadioButtons or Checkbox the default is determined.
	 * If there is no default value, or a default value should not be retrieved for a field null is returned.
	 *
	 * The default values may be overwritten by the determineValues function in case of a TermReference.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField Object being analyzed.
	 * @return null|string The default value for the field.
	 */
	private static function determineDefaultValues( DrupalField $drupalField )
	{
		$rawField = $drupalField->getRawField();
		$defaultValue = null;
		$defaultValues = $drupalField->getDefaultValues();

		if (!is_null($defaultValues)) {
			// If we are dealing with Taxonomy, then the term id will be set instead of the value, handle the retrieval
			// of the id accordingly.
			$index = ($drupalField->getType() == self::DRUPAL_FIF_TAXONOMY_TERM_REFERENCE) ? 'tid' : 'value';

			switch ( $drupalField->getWidgetType() ) {
				case self::DRUPAL_FIELD_WIDGET_TYPE_SINGLE_ON_OFF_CHECKBOX :
					// Single on/off checkboxes should have an untranslated list.
					$defaultValue = (!empty($defaultValues) && $defaultValues[0][$index] == '1') ? 'true' : 'false';
					break;
				case self::DRUPAL_FIELD_WIDGET_TYPE_CHECKBOX_RADIOBUTTONS :
				case self::DRUPAL_FIELD_WIDGET_TYPE_NUMBER :
				case self::DRUPAL_FIELD_WIDGET_TYPE_SELECT :
				case self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT :
				case self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT_SUMMARY :
				case self::DRUPAL_FIELD_WIDGET_TYPE_TEXT :
				case self::DRUPAL_FIELD_WIDGET_TYPE_TAXONOMY_AUTOCOMPLETE :
			    case self::DRUPAL_FIELD_WIDGET_TYPE_ACTIVE_TAGS_TAXONOMY_AUTOCOMPLETE :
					if ( !is_array($defaultValues)) {
						$defaultValue = (string) $defaultValues;
					} else {
						// Instead of using the tid, we want to display the actual text value when handling autocomplete
						// active tags, therefore instead of returning the tid, return the name. This can also include
						// a value that is not yet known in the vocabulary on the Drupal side. This however should not
						// be a problem.
						if ( $drupalField->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_TAXONOMY_AUTOCOMPLETE
							|| $drupalField->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_ACTIVE_TAGS_TAXONOMY_AUTOCOMPLETE ) {
							$defaultValue = (string) $defaultValues[0]['name'];
						} else {
							$defaultValue = (string) $defaultValues[0][$index];
						}

						if ($drupalField->getType() != self::DRUPAL_FIF_TAXONOMY_TERM_REFERENCE) {
							// If not taxonomy related, we still want the values instead of the keys for our fields,
							// if there are allowed values.
							if (isset($rawField['field_info_fields']['settings']['allowed_values'])) {
								$values = $rawField['field_info_fields']['settings']['allowed_values'];

								$defaultValue = (is_array($values) && isset($values[$defaultValue]))
									? $values[$defaultValue] : null;
							}
						}
					}

					// Business Rule: If the Widget is a Text Type, and the Text has a filter, do not set the default.
					if ( $drupalField->getHasTextFilter()
						&& ($drupalField->getType() == self::DRUPAL_FIF_TEXT
							|| $drupalField->getType() == self::DRUPAL_FIF_TEXT_LONG
							|| $drupalField->getType() == self::DRUPAL_FIF_TEXT_WITH_SUMMARY
						)) {
						$defaultValue = null;
					}
					break;
				default :
					break;
			}
		}
		return $defaultValue;
	}

	/**
	 * Determines the values for fields that have multiple options.
	 *
	 * For fields like select boxes, radio buttons or checkboxes the available values need to be parsed.
	 * For widget types where the values are of no significance return null.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField object being analyzed.
	 * @return string A string of values, or null if not found / determined.
	 */
	private static function determineValues( DrupalField $drupalField )
	{
		$severity = $drupalField->required ? 'error' : 'warn';
		$requiredFieldMsg = $drupalField->required ? self::DRUPAL_REQUIRED_FIELD_ERROR : '';
		$value = null; // Default to empty
		$rawField = $drupalField->getRawField();

		switch ($drupalField->getWidgetType()) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_CHECKBOX_RADIOBUTTONS :
			case self::DRUPAL_FIELD_WIDGET_TYPE_SELECT:
			case self::DRUPAL_FIELD_WIDGET_TYPE_TAXONOMY_AUTOCOMPLETE :
				// If we are dealing with either a select or a checkbox and the type of the field is taxonomy or list_default
				// then the first value should be set to a value that represents none selected, these values differ for
				// radio buttons and select boxes. If the cardinality is not 1, then we are handling a multiple selectable
			    // field, in which case we do not want to include the - None - or - Not Available - options.
				$value = array();
				if (!$drupalField->getRequired() && $drupalField->getCardinality() == 1 &&
					($drupalField->getType() == self::DRUPAL_FIF_LIST_BOOLEAN
						|| $drupalField->getType() == self::DRUPAL_FIF_LIST_FLOAT
						|| $drupalField->getType() == self::DRUPAL_FIF_LIST_INTEGER
						|| $drupalField->getType() == self::DRUPAL_FIF_LIST_TEXT
						|| $drupalField->getType() == self::DRUPAL_FIF_TAXONOMY_TERM_REFERENCE )
				) {
					$value[] = ($drupalField->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_CHECKBOX_RADIOBUTTONS)
						? self::DRUPAL_VALUE_NA : self::DRUPAL_VALUE_NONE;
				}

				// Business rule: Term references need to be retrieved from the vocabulary.
				if ($drupalField->getType() == self::DRUPAL_FIF_TAXONOMY_TERM_REFERENCE ) {
					$terms = $rawField['field_info_fields']['settings']['allowed_values'][0]['vocabulary_terms'];

					// If there are errors, bail.
					if (isset($terms['errorDrupal'])) {
						$error = self::createError($drupalField->getName(), 'Failed to retrieve the terms for this field.'
							. $requiredFieldMsg, $drupalField->pubChannelId, $drupalField->contentType, $severity );
						$drupalField->addError( $error );
						return null;
					}

					// Get the name of the term instead of the ID, as our lists are quite primitive.
					if (is_array($terms) && count($terms) > 0) {                                            
						$value = array_merge($value, $terms);
					}
				} else {
					$values = $rawField['field_info_fields']['settings']['allowed_values'];

					if (is_array($values)) {
						if (count($values) > 0) {
							// Transform the array into a list value.
							//$value = "'" . array_shift($values) . "'";
							$values = array_flip($values);
							if (!$values) {
								// Todo: Report a warning: select / button field: $name does not have allowed values.
								$error = self::createError( $drupalField->getName(), 'The values for this select / button field are missing.'
									. $requiredFieldMsg,  $drupalField->pubChannelId,
									$drupalField->contentType, $severity );
								$drupalField->addError( $error );
								$value[] = '';
							} else {
								foreach ( $values as $key => $val ) {
									$val = $val; // Keep analyzer happy.
									$value[] = (string) $key;
								}
							}
						} else { // empty array.
							$value[] = '';
						}
					} elseif ( !is_null($values)) {
						$value[] = (string) $values;
					}
				}

				break;
			default :
				break;
		}
		return $value;
	}

	/**
	 * Determines the minimum or maximum value of a Drupal decimal field.
	 *
	 * Decimal fields in Drupal can be configured with precision and scale parameters to determine the number of total
	 * digits and the number of values after the decimal point, this means that the field can highly differ in the
	 * minimum and the maximum value that is allowed by the Drupal database. This function uses the precision and the
	 * scale that was defined on the drupal field to find the highest (or lowest) value we allow users to set on the
	 * field.
	 *
	 * For Example:
	 *   99999999.99
	 *   Precision: 10 (10 numbers in total)
	 *   Scale: 2 ( 2 numbers after the '.')
	 *
	 * @param DrupalField $drupalField The field for which to determine the value.
	 * @param bool $positiveBoundary If set to true, the highest value is determined, otherwise the lowest value is determined.
	 * @return string The min / max allowed value.
	 */
	private static function determineDecimalFieldBoundary(DrupalField $drupalField, $positiveBoundary=true )
	{
		$rawField = $drupalField->getRawField();

		// Get the precision and scale from the raw field.
		$precision = intval($rawField['field_info_fields']['settings']['precision']);
		$scale = intval($rawField['field_info_fields']['settings']['scale']);

		// Use precision and scale to get the max value, including the separator.
		$value = '.'; // Separator
		$value = str_pad($value, ( $precision + 1 - $scale ), '9', STR_PAD_LEFT); // Pad the value before the separator.
		$value = str_pad($value, ( $precision + 1 ), '9'); // Pad the value after the separator.

		// If the minimum field value is requested, prefix the value with a '-'.
		if ( !$positiveBoundary ) {
			$value = '-' . $value;
		}

		return $value;
	}

	/**
	 * Determines the minimum value for a field.
	 *
	 * Only numeric fields (Decimal [float/boolean] / Integer) have a min value. other fields will return null.
	 *
	 * @param DrupalField $drupalField The DrupalField object being analyzed.
	 * @return null||string The determined minimum value.
	 */
	private static function determineMinValue( DrupalField $drupalField )
	{
		$value = null;
		$rawField = $drupalField->getRawField();

		// Business Rule: Only decimal / float / integer numbers have a min value, in other cases return null.
		if ( $drupalField->getType() == self::DRUPAL_FIF_NUMBER_DECIMAL
			|| $drupalField->getType() == self::DRUPAL_FIF_NUMBER_INTEGER
			|| $drupalField->getType() == self::DRUPAL_FIF_NUMBER_FLOAT ) {
			$value = $rawField['settings']['min'];
		}

		// If the value is not set for any of the numeric fields, then we need to enforce a minimum, otherwise we might
		// get database errors when trying to store the values in Drupal as the user might be able to enter greater /
		// smaller numbers in ContentStation by default.
		if ($value == '') {
			if ($drupalField->getType() == self::DRUPAL_FIF_NUMBER_INTEGER) {
				$value = '-999999999';
			}
			if ($drupalField->getType() == self::DRUPAL_FIF_NUMBER_FLOAT) {
				$value = '-999999.99';
			}
			// This value needs to be determined based on the field settings, as it can be variable.
			if ( $drupalField->getType() == self::DRUPAL_FIF_NUMBER_DECIMAL ) {
				$value = self::determineDecimalFieldBoundary($drupalField, false);
			}
		}

		return $value;
	}

	/**
	 * Determines the maximum value for a field.
	 *
	 * Only numeric fields (Decimal [float/boolean] / Integer) have a max value. other fields will return null.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField object being analyzed.
	 * @return null||string The determined maximum value.
	 */
	private static function determineMaxValue( DrupalField $drupalField )
	{
		$value = null;
		$rawField = $drupalField->getRawField();

		// Business Rule: Only decimal / float, integer numbers have a max value, in other cases return null.
		if ( $drupalField->getType() == self::DRUPAL_FIF_NUMBER_DECIMAL
			|| $drupalField->getType() == self::DRUPAL_FIF_NUMBER_INTEGER
			|| $drupalField->getType() == self::DRUPAL_FIF_NUMBER_FLOAT ) {
			$value = $rawField['settings']['max'];
		}

		// If no maximum value is set, determine the value based on the field type / max number of digits of the field.
		// If we do not do this, then Drupal can start throwing fatal exceptions from the database layer as Drupal is
		// more restrictive than Enterprise Server.
		if ( $value == '' ) {
			if ($drupalField->getType() == self::DRUPAL_FIF_NUMBER_INTEGER ) {
				$value = '2147483647';
			}
			// ContentStation will not allow decimal points past the two digits, therefore we define the number below
			// as the maximum since it is the smallest maximum value that can be entered in ContentStation.
			if ($drupalField->getType() == self::DRUPAL_FIF_NUMBER_FLOAT ) {
				$value = '99999999.99';
			}
			// If the value is not set for any of the numeric fields, then we need to enforce a minimum, otherwise we might
			// get database errors when trying to store the values in Drupal as the user might be able to enter greater /
			// smaller numbers in ContentStation by default.
			if ($drupalField->getType() == self::DRUPAL_FIF_NUMBER_DECIMAL ) {
				$value = self::determineDecimalFieldBoundary( $drupalField );
			}
		}

		return $value;
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
	 * @return array An array of PropertyInfo object(s), or an empty array in case of errors.
	 */
	public function generatePropertyInfo( $flattened = false )
	{
		static $cache;
		$drupalFieldName = $this->getName();
		$flattenedList = $flattened ? 'flattened' : 'tree';
		if( isset( $cache[$this->id][$this->templateId][$this->pubChannelId][$drupalFieldName][$flattenedList] )) {
			return $cache[$this->id][$this->templateId][$this->pubChannelId][$drupalFieldName][$flattenedList];
		}

		$propertyInfo = null;
		$propertyInfos = array();
		$this->clearErrors(); // wipe any previously set errors.

		require_once BASEDIR .'/server/bizclasses/BizAdmPublication.class.php';
		// Validate the DrupalField for conversion into a PropertyInfo object.
		if ( $this->isValid() ) {
			$propertyInfo = new PropertyInfo();

			// Generate the Custom Property Name. If it is not valid return null. Validation occurs inside the
			// generateCustomPropertyName function.
			$propertyInfo->Name = $this->generateCustomPropertyName();
			if (is_null($propertyInfo->Name)) {
				return $propertyInfos;
			}

			$propertyInfo->DisplayName = $this->getDisplayName();
			$propertyInfo->Category = $this->getFieldCategory();
			$propertyInfo->Type = $this->getPropertyInfoType();
			$propertyInfo->DefaultValue = $this->getDefaultValues();
			$propertyInfo->ValueList = $this->getValues();
			$propertyInfo->MinValue = $this->getMinValue();
			$propertyInfo->MaxValue = $this->getMaxValue();
			$propertyInfo->MaxLength = $this->getMaxLength();
			$propertyInfo->PropertyValues = $this->getPropertyValues();
			$propertyInfo->MinResolution = $this->getMinResolution();
			$propertyInfo->MaxResolution = $this->getMaxResolution();
			$propertyInfo->AdminUI = false;
			$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
			$propertyInfo->TemplateId = $this->templateId;
			$propertyInfo->Required = $this->getRequired(); // Needed for Dialogs.

			if( $this->autocompleteTermEntity ) {
				$propertyInfo->TermEntity = $this->autocompleteTermEntity;
				$propertyInfo->AutocompleteProvider = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
				$propertyInfo->PublishSystemId = BizAdmPublication::getPublishSystemIdForChannel( $this->pubChannelId );
			}

			// If the Suggestion Term Entity is set for the field, also set it on the PropertyInfo.
			if (!empty( $this->suggestionEntity ) ) {
				$propertyInfo->SuggestionEntity = $this->suggestionEntity;

				// Set a warning if the TermEntity cannot be handled by the SuggestionProvider.
				if (!$this->validateSuggestionEntity()) {
					$error = $this->createError( $this->getName(), 'The TermEntity: `' . $this->getSuggestionEntity()
						. '` is not supported by the suggestion provider configured for the channel.'
						, $this->pubChannelId, $this->contentType, 'warn' );
					$this->addError( $error );
				}
			}

			// If the Type is FileSelector, we need to create both a File and a FileSelector PropertyInfo.
			if ($propertyInfo->Type == self::ENTERPRISE_PROPERTY_TYPE_FILESELECTOR) {
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
				$propertyInfo->MaxValue = ($this->getCardinality() != self::DRUPAL_CARDINALITY_UNLIMITED)
											? $this->getCardinality() : '';
			}

			// If the Type is ArticleComponentSelector, we need to create both an ArticleComponent and a
			// ArticleComponentSelector PropertyInfo.
			if ($propertyInfo->Type == self::ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENTSELECTOR ) {
				$propertyInfoFile = $this->getArticleComponentProperty( $propertyInfo );
				if ( !$propertyInfoFile ) {
					return $propertyInfos;
				}

				if ( !$flattened ) {
					$propertyInfo->Widgets = array( new DialogWidget( $propertyInfoFile ) );
				}
			}

			if ( $this->getHasDisplaySummary() ) {
				$propertyInfoSummary = unserialize(serialize($propertyInfo));

				// Update the name
				$propertyInfoSummary->Name = $this->generateCustomPropertyName(null, '_SUM');
				$propertyInfoSummary->DisplayName = 'Summary ('.$propertyInfoSummary->DisplayName.')';

				if (is_null($propertyInfoSummary->Name)) {
					return $propertyInfos;
				}

				if ( $propertyInfoSummary->Type == self::ENTERPRISE_PROPERTY_TYPE_MULTILINE ) {
					// Make sure this one is added before the 'normal' widget
					$propertyInfos[] = $propertyInfoSummary;
				} else {
					$name = $this->generateCustomPropertyName(self::ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENT, '_SUM');
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

			//TODO: Collection widgets will require special consideration as well, consisting of multiple widgets in one
			//TODO: type, means we will have to create multiple PropertyInfos to cover one Drupal widget.
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
			if ( !is_null($propertyInfo) && isset($propertyInfoFile) && ($propertyInfo->Type == self::ENTERPRISE_PROPERTY_TYPE_FILESELECTOR
				|| $propertyInfo->Type == self::ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENTSELECTOR)) {

				$flattenedProps['PublishForm'][] = $propertyInfoFile;

				if ($propertyInfo->Type == self::ENTERPRISE_PROPERTY_TYPE_FILESELECTOR && !empty($subPropertyInfos) ) {
					$drupalSubWidgetType = ($this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE)
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
		$propertyInfoFile->Name = $this->generateCustomPropertyName(self::ENTERPRISE_PROPERTY_TYPE_FILE);
		if (is_null($propertyInfoFile->Name)) {
			return null;
		}

		$propertyInfoFile->DisplayName = $propertyInfo->DisplayName;
		$propertyInfoFile->Category = $propertyInfo->Category;
		$propertyInfoFile->Type = self::ENTERPRISE_PROPERTY_TYPE_FILE;
		$propertyInfoFile->MaxLength = $propertyInfo->MaxLength;
		$propertyInfoFile->PropertyValues = $propertyInfo->PropertyValues;
		$propertyInfoFile->AdminUI = false;
		$propertyInfoFile->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
		$propertyInfoFile->TemplateId = $this->templateId;
		$propertyInfoFile->Required = $propertyInfo->Required; // Needed in Dialogs.

		if ($this->getType() == self::DRUPAL_FIF_IMAGE) {
			// If the widget for which we create a file is an image,  we need the min and max resolution.
			$propertyInfoFile->MinResolution = $propertyInfo->MinResolution;
			$propertyInfoFile->MaxResolution = $propertyInfo->MaxResolution;
		}

		return $propertyInfoFile;
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
		if ( $this->getType() == self::DRUPAL_FIF_IMAGE ) {

				// Check the Alt text field.
				if ( $this->hasAltTextField ) {
					$propertyInfos[] = $this->createPropertyInfoForAlternateTextSubWidget();
				}

				// Check the Title field.
				if ( $this->hasTitleField ) {
					$propertyInfos[] = $this->createPropertyInfoForTitleSubWidget();
				}
		} elseif ( $this->getType() == self::DRUPAL_FIF_FILE ) {

				// Check the Display field.
				if ( $this->hasDisplayField ) {
					$propertyInfos[] = $this->createPropertyInfoForDisplaySubWidget();
				}

				// Check the Description Field.
				if ( $this->hasDescriptionField ) {
					$propertyInfos[] = $this->createPropertyInfoForDescriptionSubWidget();
			}
		}

		// Add standard Properties for the Name and the Format,but only for the Dialog creation.
		if ( ($this->getType() == self::DRUPAL_FIF_IMAGE || $this->getType() == self::DRUPAL_FIF_FILE) && !$flattened ) {
			$standardProperties = BizProperty::getPropertyInfos();

			// For images add the height and width of the image so ContentStation can compose the resolution.
			if ($this->getType() == self::DRUPAL_FIF_IMAGE) {
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
		$propertyInfoArticleComponent->Name = $this->generateCustomPropertyName( self::ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENT );
		if (is_null($propertyInfoArticleComponent->Name)) {
			return null;
		}

		$propertyInfoArticleComponent->DisplayName = $propertyInfo->DisplayName;
		$propertyInfoArticleComponent->Category = $propertyInfo->Category;
		$propertyInfoArticleComponent->Type = self::ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENT;
		$propertyInfoArticleComponent->PropertyValues = $propertyInfo->PropertyValues;
		$propertyInfoArticleComponent->AdminUI = false;
		$propertyInfoArticleComponent->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
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
	 * Determine the PropertyInfo type for the specified DrupalField.
	 *
	 * Determines the Enterprise PropertyInfo type as used internally by Enterprise Server.
	 *
	 * @static
	 * @param DrupalField $drupalField The DrupalField object being analyzed.
	 * @return null|string The Enterprise Property Type, or null if it could not be determined.
 	 */
	private static function determinePropertyInfoType( DrupalField $drupalField )
	{
		$type = null;

		switch ( $drupalField->getWidgetType() ) {
			case self::DRUPAL_FIELD_WIDGET_TYPE_SINGLE_ON_OFF_CHECKBOX : // Checkbox.
				$type = self::ENTERPRISE_PROPERTY_TYPE_BOOLEAN;
				break;
			case self::DRUPAL_FIELD_WIDGET_TYPE_CHECKBOX_RADIOBUTTONS : // RadioButtons.
			case self::DRUPAL_FIELD_WIDGET_TYPE_SELECT : // Select (Float)
				$type = self::ENTERPRISE_PROPERTY_TYPE_LIST;

				// RadioButtons or Selects that match Float/Integer/Text list field info types from Drupal, with a
				// cardinality of more than 1 or unlimited should be created as multilist fields. The same goes for
				// Term references.
				if (($drupalField->getFieldInfoType() == self::DRUPAL_FIF_LIST_FLOAT
					|| $drupalField->getFieldInfoType() == self::DRUPAL_FIF_LIST_INTEGER
					|| $drupalField->getFieldInfoType() == self::DRUPAL_FIF_LIST_TEXT
					|| $drupalField->getType() == self::DRUPAL_FIF_TAXONOMY_TERM_REFERENCE )
					&& ($drupalField->getCardinality() != 1 )) {
					$type = self::ENTERPRISE_PROPERTY_TYPE_MULTILIST;
				}

				break;

			case self::DRUPAL_FIELD_WIDGET_TYPE_NUMBER : // Decimal (Float / Double), Integer.
				// If the Field Type matches a decimal, map the PropertyInfo as a Double.
				if ($drupalField->getType() == self::DRUPAL_FIF_NUMBER_DECIMAL
					|| $drupalField->getType() == self::DRUPAL_FIF_NUMBER_FLOAT
				) {
					$type = self::ENTERPRISE_PROPERTY_TYPE_DOUBLE;
				}

				// If the Field Type matches an integer, map the PropertyInfo as an Integer.
				if ($drupalField->getType() == self::DRUPAL_FIF_NUMBER_INTEGER) {
					$type = self::ENTERPRISE_PROPERTY_TYPE_INT;
				}
				break;
			case self::DRUPAL_FIELD_WIDGET_TYPE_FILE : // File / Collection
				// Default to FileSelector, and only change if needed.
				$type = self::ENTERPRISE_PROPERTY_TYPE_FILESELECTOR;

				// Collection widgets used to match the following business rule. If the field had a description field
				// and the cardinality is not unlimited, then we wanted to create a Collection widget.
				// $type = self::ENTERPRISE_PROPERTY_TYPE_COLLECTION; This however is no longer applicable as is since
				// a FileSelector can now have any cardinalty and may have the optional Description Field.
				// Once the functional specs are revised this should be analyzed.
				break;
			case self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE : // Image / Collection
				// Images are stored as FileSelectors in Enterprise, unless alt text / title are set.
				$type = self::ENTERPRISE_PROPERTY_TYPE_FILESELECTOR;

				if ($drupalField->getHasAltTextField() || $drupalField->getHasTitleField() || $drupalField->getCardinality() != 1 ) {
					//TODO: After implementation of the COLLECTION widget uncomment the line below.
					//$type = self::ENTERPRISE_PROPERTY_TYPE_COLLECTION;
					$type = $type; // Keep analyzer happy, remove this line after collection is implemented.
				}
				break;
			case self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT :
			case self::DRUPAL_FIELD_WIDGET_TYPE_LONG_TEXT_SUMMARY :
				$type = self::ENTERPRISE_PROPERTY_TYPE_MULTILINE;

				// If the field has a filter, then we need to switch the type to an ArticleComponentSelector.
				if ($drupalField->getHasTextFilter()) {
					$type = self::ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENTSELECTOR;
				}
				break;                            
			case self::DRUPAL_FIELD_WIDGET_TYPE_TEXT :
				$type = self::ENTERPRISE_PROPERTY_TYPE_STRING;

				if ($drupalField->getCardinality() == -1) {
					$type = self::ENTERPRISE_PROPERTY_TYPE_MULTISTRING;
				}

				// If the field has a filter, then we need to switch the type to an ArticleComponentSelector.
				if ($drupalField->getHasTextFilter()) {
					$type = self::ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENTSELECTOR;
				}
				break;
			case self::DRUPAL_FIELD_WIDGET_TYPE_ACTIVE_TAGS_TAXONOMY_AUTOCOMPLETE:
			case self::DRUPAL_FIELD_WIDGET_TYPE_TAXONOMY_AUTOCOMPLETE :
				$type = self::ENTERPRISE_PROPERTY_TYPE_MULTISTRING;
				break;
			case self::DRUPAL_FIELD_WIDGET_TYPE_DATE_POPUP:
			case self::DRUPAL_FIELD_WIDGET_TYPE_DATE_SELECT: // For unix datetime and ISO date.
			case self::DRUPAL_FIELD_WIDGET_TYPE_DATE_TEXT:
                $type = self::ENTERPRISE_PROPERTY_TYPE_DATETIME;
				break;
			default :
				$severity = $drupalField->required ? 'error' : 'warn';
				$requiredFieldMsg = $drupalField->required ? self::DRUPAL_REQUIRED_FIELD_ERROR : '';
				$error = self::createError( $drupalField->getName(),
						'Unsupported Field Type: ' . $drupalField->getWidgetType() .'. ' . $requiredFieldMsg,
						$drupalField->pubChannelId, $drupalField->contentType, $severity );
				$drupalField->addError( $error );
				break;
		}
		return $type;
	}

	/**
	 * Determines if the DrupalField definition is valid.
	 *
	 * @return bool Whether this DrupalField is valid or not.
	 */
	public function isValid()
	{
		$error = null;
		$severity = $this->required ? 'error' : 'warn';
		$requiredFieldMsg = $this->required ? self::DRUPAL_REQUIRED_FIELD_ERROR : '';
		// Business Rule: The DrupalField needs to have a name.
		if (is_null($this->getName())) {
			$error = self::createError( 'Unknown', 'Missing Field name.',
						$this->pubChannelId, $this->contentType, 'error' );
			$this->addError( $error );
		}

		// Business Rule: The DrupalField needs to have a display name (label).
		$displayName = $this->getDisplayName();
		if (empty($displayName)) {
			$error = self::createError( $this->getName(), 'The Display Name should not be null.' . $requiredFieldMsg,
						$this->pubChannelId, $this->contentType, 'error' );
			$this->addError( $error );
		}

		// Business Rule: The DrupalField needs to have a valid type.
		if (is_null($this->getType())) {
			$error = self::createError( $this->getName(), 'Unsupported Field type.'
						. $requiredFieldMsg, $this->pubChannelId, $this->contentType, 'error' );
			$this->addError( $error );
		}

		// Business Rule: The DrupalField needs to have a valid widget type.
		if (is_null($this->getWidgetType())) {
			$error = self::createError( $this->getName(), 'The Field Type should not be null.' .
						$requiredFieldMsg, $this->pubChannelId, $this->contentType, 'error' );
			$this->addError( $error );
		}

		// Business Rule: The DrupalField may not have cardinality 0.
		if ($this->getCardinality() == 0) {
			$error = self::createError( $this->getName(), 'The cardinality for this Field should not be \'0\'.'
						. $requiredFieldMsg, $this->pubChannelId, $this->contentType, 'error' );
			$this->addError( $error );
		}

		// Business Rule: The PropertyInfoType needs to be known (indicates a valid type).
		if (is_null($this->getPropertyInfoType())) {
			$error = self::createError( $this->getName(), 'The Property Info Type should not be null.'
						. $requiredFieldMsg, $this->pubChannelId, $this->contentType, $severity );
			$this->addError( $error );
		}

		// Business Rule: The Required Field needs to be a boolean value.
		if (!is_bool($this->getRequired())) {
			$error = self::createError( $this->getName(),
				'The \'Required\' flag for this field should be a boolean value.'  . $requiredFieldMsg,
						$this->pubChannelId, $this->contentType, $severity );
			$this->addError( $error );
		}

		// Check the Type / Widget and the required fields.
		switch ( $this->getType() ) {
			case self::DRUPAL_FIF_LIST_BOOLEAN :
			case self::DRUPAL_FIF_LIST_FLOAT :
			case self::DRUPAL_FIF_LIST_INTEGER :
			case self::DRUPAL_FIF_LIST_TEXT :
				// Business Rule: Cardinality has to be 1 for a Boolean field, anything else is not allowed.
				if ( $this->getCardinality() == 1 ) {
					// Business Rule: A Boolean field is valid if it is a single on/off checkbox, or if it is a Check
					// Box / Radio Buttons, also matches float_list.
					if ($this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_SINGLE_ON_OFF_CHECKBOX
						|| $this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_CHECKBOX_RADIOBUTTONS ) {
						break;
					}
				}

				// Float / Text / Integer Lists can have any cardinality, but need to match the type for
				// RadioButtons or Select widgets.
				if ( ( $this->getFieldInfoType() == self::DRUPAL_FIF_LIST_FLOAT
					   || $this->getFieldInfoType() == self::DRUPAL_FIF_LIST_INTEGER
					   || $this->getFieldInfoType() == self::DRUPAL_FIF_LIST_TEXT
					 )
					&& ( $this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_CHECKBOX_RADIOBUTTONS
						|| $this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_SELECT)
					) {
					break;
				}

				// None of the cases matched, generate an error.
				$error = self::createError( $this->getName(), 'The cardinality is invalid; ' . $this->getCardinality() .
					' is not valid for Fields of type ' . $this->getWidgetType(). '. ' .$requiredFieldMsg,
					$this->pubChannelId, $this->contentType, $severity );
				$this->addError( $error );
				break;
			case self::DRUPAL_FIF_NUMBER_DECIMAL : // Field Type Double.
			case self::DRUPAL_FIF_NUMBER_FLOAT : // Field type Float.
			case self::DRUPAL_FIF_NUMBER_INTEGER : // Field Type Integer.
				// Business Rule: Cardinality has to be 1 for a decimal number.
				if ( $this->getCardinality() == 1 ) {
					// Business Rule: A Decimal or Integer Number should be widget Type Number.
					if ($this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_NUMBER ) {
						break;
					}
				}
				$error = self::createError( $this->getName(), 'The cardinality is invalid; ' . $this->getCardinality() .
					' is not valid for Fields of type ' . $this->getWidgetType(). '. ' .$requiredFieldMsg,
					$this->pubChannelId, $this->contentType, $severity );
				$this->addError( $error );
				break;
			case self::DRUPAL_FIF_DATE: // Field Type date iso format
				// Business Rule: Cardinality has to be 1 for a date iso format
				if ( $this->getCardinality() != 1 ) {
					$error = self::createError( $this->getName(), 'The cardinality is invalid; ' . $this->getCardinality() .
						' is not valid for Fields of type ' . $this->getWidgetType(). '. ' .$requiredFieldMsg,
						$this->pubChannelId, $this->contentType, $severity );
					$this->addError( $error );
				}
				break;
			case self::DRUPAL_FIF_DATESTAMP: // Field Type date iso format
				// Business Rule: Cardinality has to be 1 for a date iso format
				if ( $this->getCardinality() != 1 ) {
					$error = self::createError( $this->getName(), 'The cardinality is invalid; ' . $this->getCardinality() .
						' is not valid for Fields of type ' . $this->getWidgetType(). '. ' .$requiredFieldMsg,
						$this->pubChannelId, $this->contentType, $severity );
					$this->addError( $error );
				}
				break;
            case self::DRUPAL_FIF_DATETIME: // Field Type date time
				// Business Rule: Cardinality has to be 1 for a date time
				if ( $this->getCardinality() != 1 ) {
                	$error = self::createError( $this->getName(), 'The cardinality is invalid; ' . $this->getCardinality() .
						' is not valid for Fields of type ' . $this->getWidgetType(). '. ' .$requiredFieldMsg,
					$this->pubChannelId, $this->contentType, $severity );
					$this->addError( $error );
				}
				break;
			case self::DRUPAL_FIF_FILE : // Field Type File / Collection Block.
				// Business Rule: File Selector may have a Description field enabled.
				// Business Rule: File Selector may have any Cardinality.
					break;
			case self::DRUPAL_FIF_IMAGE : // Image / Collection Block.
				// Business Rule: File Selector (Image) does not have the alt text or the title field, and should have
				//                a cardinality of 1.
				if ($this->getCardinality() == 1 && $this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE
					&& !$this->getHasAltTextField() && !$this->getHasTitleField()) {
					break;
				}

				// Business Rule: Collection may have any cardinality, but needs to have a alt text field or a title field.
				if (($this->getHasAltTextField() || $this->getHasTitleField() || $this->getCardinality() != 1) &&
					$this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE) {
					break;
				}

				// If the Widget Type is supported but the other validation rules neither matched a FileSelector nor
				// CollectionBlock set error messages accordingly.
				if ($this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_IMAGE_IMAGE) {
					// Validation did not pass for a FileSelector for a Drupal Image, set an error message accordingly.
					$error = self::createError( $this->getName(), 'An Image Field with a cardinality of 1 may not have '
						. ' an alt text field \'false\', or have a title field \'false\'.'
						. ' Entered values: alt text field: \''
						. (($this->getHasAltTextField()) ? 'true' : 'false') . '\' title field: \''
						. (($this->getHasTitleField()) ? 'true' : 'false') . '\' cardinality: \''
						. $this->getCardinality() . '\'. '  . $requiredFieldMsg, $this->pubChannelId, $this->contentType,
						$severity );
					$this->addError( $error );

					// Validation did not pass for a Collection Block for a Drupal Image, sen an error message accordingly.
					$error = self::createError( $this->getName(), 'An Image Field (collection) may have any cardinality but '
						. 'should have an alt text field or title field. Entered values: alt text field: \''
						. (($this->getHasAltTextField()) ? 'true' : 'false') . '\' title field: \''
						. (($this->getHasTitleField()) ? 'true' : 'false') . '\' cardinality: \''
						. $this->getCardinality() . '\'. '  . $requiredFieldMsg, $this->pubChannelId, $this->contentType,
						$severity );
					$this->addError( $error );
				}
				break;
			case self::DRUPAL_FIF_TEXT_WITH_SUMMARY :
			case self::DRUPAL_FIF_TEXT_LONG :
				// Business Rule: For Text Type fields the cardinality has to be one.
				if ( $this->getCardinality() != 1 ) {
					$error = self::createError( $this->getName() , 'Cardinality has to be 1 for fields of type Text. Encountered '
						. 'cardinality: \'' . $this->getCardinality() . '\'. ' . $requiredFieldMsg, $this->pubChannelId,
						$this->contentType, $severity );
					$this->addError( $error );
				}
				break;
            case self::DRUPAL_FIF_TEXT :
                //Allowed: Plain text and cardinality 1 or unlimited.
                if ( $this->getCardinality() > 1 && !$this->getHasTextFilter()) {
	                $error = self::createError( $this->getName() , 'Cardinality has to be unlimited or 1 for fields of type Text. Encountered '
	                    . 'cardinality: \'' . $this->getCardinality() . '\'. ' . $requiredFieldMsg, $this->pubChannelId,
	                $this->contentType, $severity );
	                $this->addError( $error );
                }
                //Allowed: Filtered text and cardinality 1.
                if ($this->getCardinality() != 1 && $this->getHasTextFilter() ) {
	                $error = self::createError( $this->getName() , 'Cardinality has to be 1 for fields of type Text. Encountered '
		                . 'cardinality: \'' . $this->getCardinality() . '\'. ' . $requiredFieldMsg, $this->pubChannelId,
		            $this->contentType, $severity );
	                $this->addError( $error );
                }

                //Not allowed: Filtered text for a Text Field with a text Widget and filtered text. (BZ#32770)
                if ( $this->getWidgetType() == self::DRUPAL_FIELD_WIDGET_TYPE_TEXT && $this->getHasTextFilter() ) {
	                $error = self::createError( $this->getName() , 'Filtered text is not allowed for Text fields with a Text widget. '
		                . $requiredFieldMsg, $this->pubChannelId, $this->contentType, $severity );
	                $this->addError( $error );
                }

                break;
			case self::DRUPAL_FIF_TAXONOMY_TERM_REFERENCE :
				// Term references do not need to have a check on Cardinality.
				break;
			default :
				// Invalid so do nothing.
				$error = self::createError( $this->getName(), 'Unsupported Field and Field type combination for: Type: \''
					. $this->getType() . '\', Field: \'' . $this->getWidgetType() . '\'. '  . $requiredFieldMsg,
					$this->pubChannelId, $this->contentType, $severity );
				$this->addError( $error );
				break;
		}

		return is_null( $error ); // No error or warning reported in the validation above?
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
			case self::ENTERPRISE_PROPERTY_TYPE_ARTICLECOMPONENT :
			case self::ENTERPRISE_PROPERTY_TYPE_FILE :
				$stringPrefix = 'C_DPF_F_';
				break;
			default :
				$stringPrefix = 'C_DPF_';
				break;
		}

		// Sanitize the Drupal field name.
		$prefix = 'field_';
		$drupalFieldName = $this->getName();
		if( substr( $drupalFieldName, 0, strlen( $prefix ) ) == $prefix ) {
			$drupalFieldName = substr( $drupalFieldName, strlen( $prefix ) ); // Strip off 'field_'
		}

		$drupalFieldName = strtoupper($drupalFieldName); // Ensure the name will be in uppercase.

		// Determine the new name.
		$name = $stringPrefix . $this->getTemplateId() . '_' . $this->getId() . $subWidgetPrefix . '_' . $drupalFieldName;
		$name = substr($name, 0, 30);

		// validate the Name.
		if (!$this->validateCustomPropertyName( $name, $stringPrefix )) {
			$severity = $this->required ? 'error' : 'warn';
			$requiredFieldMsg = $this->required ? self::DRUPAL_REQUIRED_FIELD_ERROR : '';
			$error = self::createError( $this->getName(),
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
	 * - The content type's comments setting.
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
		$pattern = "/^C_DPF_[0-9]+_[A-Z0-9_]{0,}$/";

		// Add a title widget.
		$propertyName = substr('C_DPF_' . $templateId . self::DRUPAL_PROPERTY_TITLE, 0, 30);
		if (preg_match($pattern, $propertyName)) {
			$defaultValue = '';
			$propertyInfo = new PropertyInfo($propertyName, 'Title','', 'string', $defaultValue, null, null, null, 255);
			$propertyInfo->Required = true;
			$propertyInfo->AdminUI = false; // Do not show the property in the admin ui.
			$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
			$propertyInfo->TemplateId = $templateId;
			$properties[self::DRUPAL_PROPERTY_TITLE] = $propertyInfo;

		} else {
			$requiredFieldMsg = self::DRUPAL_REQUIRED_FIELD_ERROR;
			$error = self::createError( self::DRUPAL_PROPERTY_TITLE, 'Invalid property name: \''
				. $propertyName	. '\'.'. $requiredFieldMsg, $pubChannelId, $contentType, 'error' );
			$this->addError( $error );
		}

		// Determine the PROMOTE property.
		$propertyName = substr('C_DPF_' . $templateId . '_PROMOTE', 0, 30);
		if (preg_match($pattern, $propertyName)) {
			$defaultValue = ($rawSpecialFieldValues[self::ENTERPRISE_PROPERTY_PROMOTE] == 1) ? 'true' : 'false';
			$propertyInfo = new PropertyInfo($propertyName, 'Promote', '', 'bool', $defaultValue );
			$propertyInfo->Required = false;
			$propertyInfo->AdminUI = false; // Do not show the property in the admin ui.
			$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
			$propertyInfo->TemplateId = $templateId;
			$properties[self::DRUPAL_PROPERTY_PROMOTE] = $propertyInfo;
		} else {
			$error = self::createError( self::ENTERPRISE_PROPERTY_PROMOTE, 'Invalid property name: \''
				. $propertyName	. '\'.', $pubChannelId, $contentType );
			$this->addError( $error );
		}

		// Determine the STICKY property.
		$propertyName = substr('C_DPF_' . $templateId . '_STICKY', 0, 30);
		if (preg_match($pattern, $propertyName)) {
			$defaultValue = ($rawSpecialFieldValues[self::ENTERPRISE_PROPERTY_STICKY] == 1) ? 'true' : 'false';
			$propertyInfo = new PropertyInfo($propertyName, 'Sticky', '', 'bool', $defaultValue );
			$propertyInfo->Required = false;
			$propertyInfo->AdminUI = false; // Do not show the property in the admin ui.
			$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
			$propertyInfo->TemplateId = $templateId;
			$properties[self::DRUPAL_PROPERTY_STICKY] = $propertyInfo;
		} else {
			$error = self::createError( self::ENTERPRISE_PROPERTY_STICKY, 'Invalid property name: \''
				. $propertyName	. '\'.', $pubChannelId, $contentType );
			$this->addError( $error );
		}

		// Determine the COMMENTS property.
		$propertyName = substr('C_DPF_' . $templateId . '_COMMENTS', 0, 30);
		if (preg_match($pattern, $propertyName)) {
			$defaultValue = $rawSpecialFieldValues[self::ENTERPRISE_PROPERTY_COMMENTS];
			$commentsValues = array();
			$commentsValues['Disable'] = 'Disable';
			$commentsValues['Read'] = 'Read';
			$commentsValues['Read/Write'] = 'Read/Write';
			$propertyInfo =  new PropertyInfo($propertyName, 'Comments', '', 'list', $defaultValue, $commentsValues );
			$propertyInfo->Required = false;
			$propertyInfo->AdminUI = false; // Do not show the property in the admin ui.
			$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
			$propertyInfo->TemplateId = $templateId;
			$properties[self::DRUPAL_PROPERTY_COMMENTS] = $propertyInfo;
		} else {
			$error = self::createError( self::ENTERPRISE_PROPERTY_COMMENTS, 'Invalid property name: \''
				. $propertyName	. '\'.', $pubChannelId, $contentType );
			$this->addError( $error );
		}

		// Add a publish widget.
		$propertyName = substr('C_DPF_' . $templateId . self::DRUPAL_PROPERTY_PUBLISH, 0, 30);
		if (preg_match($pattern, $propertyName)) {
			$defaultValue = self::DRUPAL_VALUE_PUBLISH_PUBLIC;
			$listOptions = array(self::DRUPAL_VALUE_PUBLISH_PUBLIC, self::DRUPAL_VALUE_PUBLISH_PRIVATE);
			$propertyInfo = new PropertyInfo($propertyName, 'Visibility','', 'list', $defaultValue, $listOptions );
			$propertyInfo->Required = true;
			$propertyInfo->AdminUI = false; // Do not show the property in the admin ui.
			$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
			$propertyInfo->TemplateId = $templateId;
			$properties[self::DRUPAL_PROPERTY_PUBLISH] = $propertyInfo;

		} else {
			$error = self::createError( self::DRUPAL_PROPERTY_PUBLISH, 'Invalid property name: \''
				. $propertyName	. '\'.', $pubChannelId, $contentType );
			$this->addError( $error );
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
	 * Sets the default value(s) for the DrupalField.
	 *
	 * The structure of the $defaultValues should match the Drupal way of determining these values, please see
	 * the determineDefaultValues() function for more details.
	 *
	 * @see determineDefaultValues();
	 * @param string[]|null|string $defaultValues The default values.
	 * @return void.
	 */
	public function setDefaultValues($defaultValues)
	{
		$this->defaultValues = $defaultValues;
	}

	/**
	 * Returns the default values for the DrupalField.
	 *
	 * Returns the default values for this DrupalField.
	 *
	 * @return null|string|string[] The Default values.
	 */
	public function getDefaultValues()
	{
		return $this->defaultValues;
	}

	/**
	 * Sets the required flag on the DrupalField.
	 *
	 * The required field will be used when determining the dialog.
	 *
	 * @param boolean $required Whether or not this field is required.
	 * @return void.
	 */
	public function setRequired($required)
	{
		$this->required = $required;
	}

	/**
	 * Returns the Required attribute.
	 *
	 * The Required attribute determines whether or not a field should be flagged as a required
	 * field during input.
	 *
	 * @return bool Whether or not the field is required.
	 */
	public function getRequired()
	{
		return $this->required;
	}

	/**
	 * Sets the DrupalField Type.
	 *
	 * This should match the setting in the raw Drupal field, please see the determine
	 *
	 * @param string|null $type The type of the DrupalField.
	 */
	public function setType($type)
	{
		$this->type = $type;
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
	 * Sets the name for the DrupalField.
	 *
	 * @param string|null $name The name of the DrupalField.
	 * @return void.
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns the name of the drupal field.
	 *
	 * @return null|string The name of the DrupalField.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Sets the Cardinality for this DrupalField.
	 *
	 * The Cardinality determines how many selectable / filled in values are allowed.
	 * The cardinality can be found in the field_info_field section of the fields definition.
	 *
	 * @param int $cardinality The Cardinality for the field.
	 * @return void.
	 */
	public function setCardinality($cardinality)
	{
		$this->cardinality = intval($cardinality);
	}

	/**
	 * Returns the Cardinality for this field.
	 *
	 * @return null|string The Cardinality for this field.
	 */
	public function getCardinality()
	{
		return $this->cardinality;
	}

	/**
	 * Sets the label for this field.
	 *
	 * @param string|null $displayName
	 * @return void.
	 */
	public function setDisplayName($displayName)
	{
		$this->displayName = $displayName;
	}

	/**
	 * Returns the label for this field.
	 *
	 * @return null|string The Label for this field.
	 */
	public function getDisplayName()
	{
		return $this->displayName;
	}

	/**
	 * Sets the field id.
	 *
	 * The field id should match the internal field_id as used by Drupal.
	 *
	 * @param null|string $id The field id.
	 * @return void.
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Retrieves the field id.
	 *
	 * @return null|string The field id.
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Sets the template id for this field.
	 *
	 * @param null|string $templateId The template object id of the field.
	 * @return void.
	 */
	public function setTemplateId($templateId)
	{
		$this->templateId = $templateId;
	}

	/**
	 * Returns the Template Id for this field.
	 *
	 * @return null|string The template object id of the field.
	 */
	public function getTemplateId()
	{
		return $this->templateId;
	}

	/**
	 * Sets the values for this field.
	 *
	 * The values determine the selectable values in fields such as a radio button list or select box.
	 *
	 * @param array|null $values The values for this field.
	 * @return void.
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}

	/**
	 * Returns the values for this field.
	 *
	 * @return array|null The values for this field.
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * Sets the raw field definition as returned through xmlRPC.
	 *
	 * The raw field is used for validation and retrieval of key data components for the DrupalField object.
	 *
	 * @param array|null $rawField The raw field definition.
	 * @return void.
	 */
	public function setRawField($rawField)
	{
		$this->rawField = $rawField;
	}

	/**
	 * Returns the raw field definition.
	 *
	 * @return array|null The raw field definition.
	 */
	public function getRawField()
	{
		return $this->rawField;
	}

	/**
	 * Sets the maximum value for the DrupalField.
	 *
	 * @param string|null $maxValue The maximum value.
	 * @return void.
	 */
	public function setMaxValue($maxValue)
	{
		$this->maxValue = $maxValue;
	}

	/**
	 * Returns the maximum value for the DrupalField.
	 *
	 * @return null|string The maximum value.
	 */
	public function getMaxValue()
	{
		return $this->maxValue;
	}

	/**
	 * Sets the minimum value for the DrupalField.
	 *
	 * @param string|null $minValue The mimimum value for the DrupalField.
	 * @return void.
	 */
	public function setMinValue($minValue)
	{
		$this->minValue = $minValue;
	}

	/**
	 * Returns the minimum value for the DrupalField.
	 *
	 * @return null|string The minimum value for the field.
	 */
	public function getMinValue()
	{
		return $this->minValue;
	}

	/**
	 * Sets whether or not the field has a description field.
	 *
	 * The Description field is only used for type: File.
	 *
	 * @param bool $hasDescriptionField Whether or not the field has a description field.
	 * @return void.
	 */
	public function setHasDescriptionField($hasDescriptionField)
	{
		$this->hasDescriptionField = $hasDescriptionField;
	}

	/**
	 * Returns whether or not the field has a description field.
	 *
	 * The Description field is only used for type: File.
	 *
	 * @return bool Whether or not the field has a description field.
	 */
	public function getHasDescriptionField()
	{
		return $this->hasDescriptionField;
	}

	/**
	 * Sets the maximum length of the field.
	 *
	 * The maximum length can be the number of bytes, maximum number of characters or null depending on the field
	 * definition.
	 *
	 * @param string|null $maxLength The maximum length for this field.
	 * @return void.
	 */
	public function setMaxLength($maxLength)
	{
		$this->maxLength = $maxLength;
	}

	/**
	 * Retrieves the maximum length of the field.
	 *
	 * The maximum length can be the number of bytes, maximum number of characters or null depending on the field
	 * definition.
	 *
	 * @return null|string The maximum length for the field.
	 */
	public function getMaxLength()
	{
		return $this->maxLength;
	}

	/**
	 * Sets the maximum resolution for the field.
	 *
	 * The resolution field is only used for field sof type: Image.
	 *
	 * @param null|string $maxResolution The maximum resolution for the field.
	 * @return void.
	 */
	public function setMaxResolution($maxResolution)
	{
		$this->maxResolution = $maxResolution;
	}

	/**
	 * Retrieves the maximum resolution for the field.
	 *
	 * The resolution field is only used for fields of type: Image.
	 *
	 * @return null|string The maximum resolution of the field.
	 */
	public function getMaxResolution()
	{
		return $this->maxResolution;
	}

	/**
	 * Sets the minimum resolution for the field.
	 *
	 * The resolution field is only used for field sof type: Image.
	 *
	 * @param null|string $minResolution The minimum resolution for the field.
	 * @return void.
	 */
	public function setMinResolution($minResolution)
	{
		$this->minResolution = $minResolution;
	}

	/**
	 * Retrieves the minimum resolution for the field.
	 *
	 * The resolution field is only used for fields of type: Image.
	 *
	 * @return null|string The minimum resolution of the field.
	 */
	public function getMinResolution()
	{
		return $this->minResolution;
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
	 * Defines whether or not the field has an alt text field.
	 *
	 * The alt text field is only used for widgets of type: Image.
	 *
	 * @param bool $hasAltTextField Whether or not the field has a alt text field.
	 * @return void.
	 */
	public function setHasAltTextField($hasAltTextField)
	{
		$this->hasAltTextField = $hasAltTextField;
	}

	/**
	 * Returns whether or not the field has an alt text field.
	 *
	 * The alt text field is only used for widgets of type: Image.
	 *
	 * @return bool Whether or not the field has an alt text field.
	 */
	public function getHasAltTextField()
	{
		return $this->hasAltTextField;
	}

	/**
	 * Sets the title field toggle for this field.
	 *
	 * Defines whether or not this field has a title field. Title fields are only used in Collection/FileSelector widgets.
	 *
	 * @param bool $hasTitleField Whether or not the DrupalField object has a title field.
	 * @return void.
	 */
	public function setHasTitleField($hasTitleField)
	{
		$this->hasTitleField = $hasTitleField;
	}

	/**
	 * Returns the Title field toggle for this DrupalField.
	 *
	 * The title field is only used for widgets of type Collection/FileSelector.
	 *
	 * @return bool Whether or not the field has a title field.
	 */
	public function getHasTitleField()
	{
		return $this->hasTitleField;
	}

	/**
	 * Sets errors for this object.
	 *
	 * @see createError()
	 */
	public function clearErrors()
	{
		$this->hasError = false;
		$this->errors = array();
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
	 * Sets the PropertyInfo Type for this field.
	 *
	 * The PropertyInfoType should match the internal name for the PropertyInfoType.
	 *
	 * @param null|string $propertyInfoType The Type for this DrupalField.
	 * @return void.
	 */
	public function setPropertyInfoType($propertyInfoType)
	{
		$this->propertyInfoType = $propertyInfoType;
	}

	/**
	 * Returns the PropertyInfo Type.
	 *
	 * Returns the internal type for a PropertyInfo that matches this Field.
	 *
	 * @return null|string The PropertyInfo type for this field.
	 */
	public function getPropertyInfoType()
	{
		return $this->propertyInfoType;
	}

	/**
	 * Sets the category (tab) to which the Field belongs.
	 *
	 * @param null|string $fieldCategory The Category (tab) of this field.
	 * @return void.
	 */
	public function setFieldCategory($fieldCategory)
	{
		$this->fieldCategory = $fieldCategory;
	}

	/**
	 * Returns the category (tab) to which a Field belongs.
	 *
	 * This is generally used when building the Dialog to resolve the tab on which the widget / field should be placed.
	 *
	 * @return null|string The Tab / Category for this property.
	 */
	public function getFieldCategory()
	{
		return $this->fieldCategory;
	}

	/**
	 * Returns the type as recorded on the FieldInfoField information from Drupal.
	 *
	 * This field is used internally for validations.
	 *
	 * @return null|string The FieldInfoField type as recorded on the widget.
	 */
	public function getFieldInfoType()
	{
		return $this->fieldInfoType;
	}

	/**
	 * Returns the internal property used to specify whether the field is filtered text or not.
	 *
	 * This setting affects the determination of the PropertyInfoType, as filtered text will result in
	 * ArticleComponentSelector widgets, while non-filtered text will result in multiline fields.
	 *
	 * @return bool Whether or not the field has a text filter.
	 */
	public function getHasTextFilter()
	{
		return $this->hasTextFilter;
	}

	/**
	 * Returns the recorded InitialHeight for the widget.
	 *
	 * @return int|null The InitialHeight.
	 */
	public function getInitialHeight()
	{
		return $this->initialHeight;
	}

	/**
	 * Sets the InitialHeight for the widget.
	 *
	 * @param $initialHeight
	 */
	public function setInitialHeight( $initialHeight )
	{
		$this->initialHeight = $initialHeight;
	}

	/**
	 * Gets the hasDisplaySummary property for the widget.
	 *
	 * @return bool
	 */
	public function getHasDisplaySummary()
	{
		return $this->hasDisplaySummary;
	}

	/**
	 * Sets the hasDisplaySummary property for the widget.
	 *
	 * @param $hasDisplaySummary
	 */
	public function setHasDisplaySummary ( $hasDisplaySummary )
	{
		$this->hasDisplaySummary = $hasDisplaySummary;
	}
	/**
	 * Sets the display field toggle for this field.
	 *
	 * Defines whether or not this field has a display field. Display fields are only used in FileSelector widgets.
	 *
	 * @param bool $hasDisplayField Whether or not the DrupalField object has a display field.
	 * @return void.
	 */
	public function setHasDisplayField($hasDisplayField)
	{
		$this->hasDisplayField = $hasDisplayField;
	}

	/**
	 * Returns the Display field toggle for this DrupalField.
	 *
	 * The Display field is only used for widgets of type FileSelector.
	 *
	 * @return bool Whether or not the field has a Display field.
	 */
	public function getHasDisplayField()
	{
		return $this->hasDisplayField;
	}

	/**
	 * Creates a PropertyInfo for the Alternate Text sub-widget of a Drupal Image widget.
	 *
	 * @return null|PropertyInfo
	 */
	public function createPropertyInfoForAlternateTextSubWidget()
	{
		$propertyInfo = null;

		// Unlike normal properties which will get a DB column per property for a specific Drupal 7 field, the Alt Text
		// on an image file selector is supposed to use a single field for the whole Drupal 7 integration. This means
		// that a single DB column will be responsible for storing the Alt text for an image and that this exact same
		// alt text will be used for the image across all used templates. This is by design.
		$propertyName = self::DRUPAL_IMG_ALT_TEXT;

		$propertyInfo = new PropertyInfo();
		$propertyInfo->DisplayName = 'Alternate text';
		$propertyInfo->Name = $propertyName;

		$propertyInfo->Category = $this->getFieldCategory();
		$propertyInfo->Type = self::ENTERPRISE_PROPERTY_TYPE_STRING;
		$propertyInfo->DefaultValue = '';
		$propertyInfo->MaxLength = 255;
		$propertyInfo->AdminUI = false;
		$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
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

		// Unlike normal properties which will get a DB column per property for a specific Drupal 7 field, the Title
		// on an image file selector is supposed to use a single field for the whole Drupal 7 integration. This means
		// that a single DB column will be responsible for storing the Title for an image and that this exact same
		// title will be used for the image across all used templates. This is by design.
		$propertyName = self::DRUPAL_IMG_TITLE;

		$propertyInfo = new PropertyInfo();
		$propertyInfo->DisplayName = 'Title';
		$propertyInfo->Name = $propertyName;

		$propertyInfo->Category = $this->getFieldCategory();
		$propertyInfo->Type = self::ENTERPRISE_PROPERTY_TYPE_STRING;
		$propertyInfo->DefaultValue = '';
		$propertyInfo->MaxLength = 255;
		$propertyInfo->AdminUI = false;
		$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
		$propertyInfo->Required = false; // Needed for Dialogs.

		return $propertyInfo;
	}

	/**
	 * Creates a PropertyInfo for the Display sub-widget of a Drupal File widget.
	 *
	 * @return null|PropertyInfo
	 */
	public function createPropertyInfoForDisplaySubWidget()
	{
		$propertyInfo = null;
		$propertyName = self::generateCustomPropertyName( self::ENTERPRISE_PROPERTY_TYPE_FILE, '_DIS');

		if ( !is_null( $propertyName ) ) {
			$propertyInfo = new PropertyInfo();
			$propertyInfo->DisplayName = 'Include file in display';
			$propertyInfo->Name = $propertyName;

			$propertyInfo->Category = $this->getFieldCategory();
			$propertyInfo->Type = self::ENTERPRISE_PROPERTY_TYPE_BOOLEAN;

			// Determine the Default value for the Display toggle.
			$rawField = $this->getRawField();

			$propertyInfo->DefaultValue = ($rawField['field_info_fields']['settings']['display_default'] == '1')
				? 'true'
				: '';
			$propertyInfo->AdminUI = false;
			$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
			$propertyInfo->TemplateId = $this->templateId;
			$propertyInfo->Required = false; // Needed for Dialogs.
		}

		return $propertyInfo;
	}

	/**
	 * Creates a PropertyInfo for the Description sub-widget of a Drupal File widget.
	 *
	 * @return null|PropertyInfo
	 */
	public function createPropertyInfoForDescriptionSubWidget()
	{
		$propertyInfo = null;
		$propertyName = self::generateCustomPropertyName( self::ENTERPRISE_PROPERTY_TYPE_FILE, '_DES');

		if ( !is_null( $propertyName ) ) {
			$propertyInfo = new PropertyInfo();
			$propertyInfo->DisplayName = 'Description';
			$propertyInfo->Name = $propertyName;

			$propertyInfo->Category = $this->getFieldCategory();
			$propertyInfo->Type = self::ENTERPRISE_PROPERTY_TYPE_STRING;
			$propertyInfo->DefaultValue = '';
			$propertyInfo->MaxLength = 128; // Description field can only contain 128 characters.
			$propertyInfo->AdminUI = false;
			$propertyInfo->PublishSystem = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
			$propertyInfo->TemplateId = $this->templateId;
			$propertyInfo->Required = false; // Needed for Dialogs.
		}

		return $propertyInfo;
	}

	/**
	 * Sets the Autocomplete Term Entity for this field.
	 *
	 * @param null|string $termEntity
	 */
	public function setAutocompleteTermEntity( $termEntity )
	{
		$this->autocompleteTermEntity = $termEntity;
	}

	/**
	 * Sets the Suggestion Entity for this field.
	 *
	 * @param null|string $suggestionEntity
	 */
	public function setSuggestionEntity( $suggestionEntity )
	{
		$this->suggestionEntity = $suggestionEntity;
	}

	/**
	 * Returns the Suggestion Entity for this field.
	 *
	 * @return null|string The set Suggestion Entity for this field.
	 */
	public function getSuggestionEntity()
	{
		return $this->suggestionEntity;
	}
}