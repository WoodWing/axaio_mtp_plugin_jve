<?php

/**
 * Utility class to convert data classes between Drupal 7 and Enterprise.
 *
 * @package 	Enterprise
 * @subpackage 	Utils
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class WW_Plugins_Drupal7_Utils
{
	/** Constant for the Enterprise Promote property. */
	const C_DIALOG_DRUPAL7_PROMOTE = 'C_DIALOG_DRUPAL7_PROMOTE';
	/** Constant for the Enterprise Sticky property. */
	const C_DIALOG_DRUPAL7_STICKY = 'C_DIALOG_DRUPAL7_STICKY';
	/** Constant for the Enterprise Comments property. */
	const C_DIALOG_DRUPAL7_COMMENTS = 'C_DIALOG_DRUPAL7_COMMENTS';
	/** Constant for the Content Type Title Field. */
	const C_DIALOG_DRUPAL7_TITLE = 'C_DIALOG_DRUPAL7_TITLE';
	/** Constant for the Content Type Publish Field. */
	const C_DIALOG_DRUPAL7_PUBLISH = 'C_DIALOG_DRUPAL7_PUBLISH';
	/** Content Type. */
	const DRUPAL7_CONTENT_TYPE = 'DRUPAL7_CONTENT_TYPE';
	/** Constant for the Plugin type. */
	const DRUPAL7_PLUGIN_NAME = 'Drupal7';

	/** Constant for the custom ADM properties dialog, used in Admin pages. */
	const CHANNEL_SEPERATOR = 'C_DPF_CHANNEL_SEPERATOR';
	/** Constant used for the Drupal site URL, used in the communication with Drupal. */
	const CHANNEL_SITE_URL = 'C_DPF_CHANNEL_SITE_URL';
	/** Constant used for the Oauth Consumer Key, used in the communication with Drupal. */
	const CHANNEL_CONSUMER_KEY = 'C_DPF_CHANNEL_CONSUMER_KEY';
	/** Constant used for the Oauth Consumer Secret, used in the communication with Drupal. */
	const CHANNEL_CONSUMER_SECRET = 'C_DPF_CHANNEL_CONSUMER_SECRET';
	/** Constant used for the Certificate, used when communication is done over SSL. */
	const CHANNEL_CERTIFICATE = 'C_DPF_CHANNEL_CERTIFICATE';

	/**
	 * Converts a given DocumentID (of Enterprise) into a content type (of Drupal).
	 *
	 * @param string $documentId Enterprise's DocumentID.
	 * @return string Drupal's content type.
	 */
	static public function convertDocumentId2ContentType( $documentId )
	{
		// The code below deals with the fact that the content type has underscores
		// as well as the separator we use for some prefixes in the DocumentID prop.
		$parts = explode( '_', $documentId );
		array_shift( $parts ); // remove "drupal7_" prefix
		array_shift( $parts ); // remove site id prefix
		return implode( '_', $parts ); // glue remaining pieces back together
	}

	/**
	 * Converts a given content type (of Drupal) into a DocumentID (of Enterprise).
	 *
     * @param int $siteId
	 * @param string $contentType Drupal's content type.
	 * @return string Enterprise's DocumentID.
	 */
	static public function convertContentType2DocumentId( $siteId, $contentType )
	{
		return self::DRUPAL7_PLUGIN_NAME . '_' . $siteId . '_' . $contentType;
	}

	/**
	 * Converts the internally stored value to the Drupal specific value.
	 *
	 * If not found then NULL is returned.
	 *
	 * @static
	 * @param string $key The key to search for.
	 * @return null|int The value for the comments toggle as used by Drupal.
	 */
	static public function convertEnterpriseCommentsToDrupal ( $key )
	{
		$values = array(
			'Disable' => 0, // Hidden.
			'Read' => 1, // Read only.
			'Read/Write' => 2, // Open.
		);

		if (array_key_exists($key, $values)) {
			return $values[$key];
		}
		return null;
	}

	/**
	 * Prepares the form fields to be sent to Drupal.
	 *
	 * @param array $propertyUsages The PropertyUsages to use.
	 * @param array $wiwiwUsages A three dimensional list of PropertyUsages. Keys are used as follows: $wiwiwUsages[mainProp][wiwProp][wiwiwProp]
	 * @param array $fields List of PublishForm fields.
	 * @return array An array of values indexed to Drupal Field ids.
	 */
	static public function prepareFormFields( $propertyUsages, $wiwiwUsages, $fields )
	{
		$indexes = array();

		if (isset($fields[self::DRUPAL7_CONTENT_TYPE])) {
			$indexes[self::DRUPAL7_CONTENT_TYPE] = $fields[self::DRUPAL7_CONTENT_TYPE];
		}

		// Prepare the array indexes.
		if( $propertyUsages ) foreach ( $propertyUsages as $propertyUsage ) {
			$fieldName = $propertyUsage->Name;
			$parts = explode( '_', $fieldName );
			$drupalFieldId = $parts[3];
			// Add _SUM to the drupal id so we can merge it later!
			if ( isset($parts[4]) && $parts[4] == 'SUM' ) {
				$drupalFieldId .= '_SUM';
			}
			$indexes = self::getFormFieldValue( $drupalFieldId, $fields, $fieldName, $indexes );
		}
		if( $wiwiwUsages ) foreach( $wiwiwUsages as /*$mainPropName => */$wiwUsages ) {
			foreach( $wiwUsages as /*$wiwPropName => */$wiwiwUsageArray ) {
				foreach( $wiwiwUsageArray as /*$wiwiwPropName => */$wiwiwUsage ) {
					$fieldName = $wiwiwUsage->Name;
					$parts = explode( '_', $fieldName );
					$drupalFieldId = $parts[3];
					// Add _SUM to the drupal id so we can merge it later!
					if ( $parts[4] == 'SUM' ) {
						$drupalFieldId .= '_SUM';
					}
					$indexes = self::getFormFieldValue( $drupalFieldId, $fields, $fieldName, $indexes );
				}
			}
		}
		return $indexes;
	}

	/**
	 * To retrieve the field value and fill it into $indexes.
	 * $indexes is a key-value pair where key is the field name and the value is the value of the field.
	 * The $indexes is returned at the end of the function.
	 *
	 * @param string $drupalFieldId
	 * @param array $fields List of PublishForm fields.
	 * @param string $fieldName The name of the property/field.
	 * @param array $indexes Refer to header above.
	 * @param array List of key-value pair that contains the FieldName and its value.
	 */
	private static function getFormFieldValue( $drupalFieldId, $fields, $fieldName, $indexes )
	{
		if ($drupalFieldId == 'PROMOTE') {
			$indexes[self::C_DIALOG_DRUPAL7_PROMOTE] = (isset($fields[$fieldName]))
				? BizPublishForm::extractFormFieldDataFromFieldValue ( $fieldName, $fields[$fieldName] )
				: null;
		} elseif ($drupalFieldId == 'STICKY') {
			$indexes[self::C_DIALOG_DRUPAL7_STICKY] = (isset($fields[$fieldName]))
				? BizPublishForm::extractFormFieldDataFromFieldValue ( $fieldName, $fields[$fieldName] )
				: null;
		} elseif ($drupalFieldId == 'COMMENTS') {
			$indexes[self::C_DIALOG_DRUPAL7_COMMENTS] = (isset($fields[$fieldName]))
				? BizPublishForm::extractFormFieldDataFromFieldValue ( $fieldName, $fields[$fieldName] )
				: null;
		}elseif ($drupalFieldId == 'TITLE') {
			$indexes[self::C_DIALOG_DRUPAL7_TITLE] = (isset($fields[$fieldName]))
				? BizPublishForm::extractFormFieldDataFromFieldValue ( $fieldName, $fields[$fieldName] )
				: null;
		}elseif ($drupalFieldId == 'PUBLISH') {
			require_once dirname(__FILE__) . '/DrupalField.class.php';
			// Retrieve whether we should publish the node or not, and set an int on the field.
			$value = (isset($fields[$fieldName]))
				? BizPublishForm::extractFormFieldDataFromFieldValue ( $fieldName, $fields[$fieldName] )
				: DrupalField::DRUPAL_VALUE_PUBLISH_PUBLIC;
			$indexes[self::C_DIALOG_DRUPAL7_PUBLISH] = (DrupalField::DRUPAL_VALUE_PUBLISH_PRIVATE == $value[0] )
				? array(0)
				: array(1);
		} else {

			$indexes[$drupalFieldId] = null;

			if (isset( $fields[$fieldName])) {
				if (is_array($fields[$fieldName])) {
					$content = array();
					foreach ($fields[$fieldName] as $object) {
						if (is_object( $object ) ) {
							$content[] = self::extractContent( $object, $fieldName );
						} else {
							$content = self::extractContent( $fields[$fieldName], $fieldName );
						}
					}
				} else {
					$content = self::extractContent( $fields[$fieldName], $fieldName );
				}
				$indexes[$drupalFieldId] = $content;
			}
		}

		return $indexes;
	}

	/**
	 * Extracts the data from a file Object if needed.
	 *
	 * @static
	 * @param Object $object
	 * @param string $fieldName
	 * @return array|null
	 */
	private static function extractContent( $object, $fieldName )
	{
		$extractContent = true;

		// For layouts, get the object as is.
		if ( isset( $object->MetaData->BasicMetaData->Type ) &&
			$object->MetaData->BasicMetaData->Type == 'Layout') {
			$extractContent = false;
		}

		// For Articles only get the content if we are dealing with a non-fileselector.
		if ( isset( $object->MetaData->BasicMetaData->Type ) &&
			$object->MetaData->BasicMetaData->Type == 'Article') {
			// Determine what kind of field the property represents, if it is an articlecomponentSelector extract
			// the content, otherwise get the file attachments instead as we are dealing with a file selector and
			// should not extract the data.
			$propertyInfos = BizProperty::getFullPropertyInfos(self::DRUPAL7_PLUGIN_NAME, $fieldName );
			$propertyInfo = $propertyInfos[0];
			if ($propertyInfo->Type == 'fileselector') {
				$extractContent = false;
			}
		}

		return BizPublishForm::extractFormFieldDataFromFieldValue ( $fieldName, $object, $extractContent );
	}

	/**
	 * Retrieves the form fields from the PublishForm.
	 *
	 * @static
	 * @param object $publishForm The PublishForm from which to get the form fields.
	 * @param $pattern Optional pattern which Property names should match for to be included in the result.
	 * @return array An array of key / value pairs with values for the form fields.
	 */
	public static function getFormFields( $publishForm, $pattern )
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';
		require_once dirname( __FILE__ ) . '/DrupalField.class.php';

		$fields = BizPublishForm::getFormFields( $publishForm, $pattern );

		// Restructure values.
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		$properties = BizProperty::getFullPropertyInfos(self::DRUPAL7_PLUGIN_NAME);

		// Fix fields.
		foreach ($properties as $propertyInfo ) {
			if ( preg_match($pattern, $propertyInfo->Name)
				&& array_key_exists($propertyInfo->Name, $fields)
				&& is_array($fields[$propertyInfo->Name])
			) {
				// N/A / None values need to be translated back to an empty string.
				if ( $propertyInfo->Type == DrupalField::ENTERPRISE_PROPERTY_TYPE_MULTILIST
					|| $propertyInfo->Type == DrupalField::ENTERPRISE_PROPERTY_TYPE_LIST
				) {
					foreach ($fields[$propertyInfo->Name] as $key => $value) {
						if ($value == DrupalField::DRUPAL_VALUE_NA || $value == DrupalField::DRUPAL_VALUE_NONE) {
							if (count($fields[$propertyInfo->Name]) > 1) {
								unset($fields[$propertyInfo->Name][$key]);
							} else {
								$fields[$propertyInfo->Name][0] = '';
							}
						}
					}
				}

				// If we are dealing with a multilist or multistring, transform selected values back into an array.
				if ( count($fields[$propertyInfo->Name]) == 1
					&& ($propertyInfo->Type == DrupalField::ENTERPRISE_PROPERTY_TYPE_MULTILIST 
                        || $propertyInfo->Type == DrupalField::ENTERPRISE_PROPERTY_TYPE_MULTISTRING)
				) {
					$fields[$propertyInfo->Name] = explode(',', $fields[$propertyInfo->Name][0]);
				}

				// If we are dealing with a dates calculate it back to UTC so the Drupal side can calculate it back to it's own timezone
				if ( ($propertyInfo->Type == DrupalField::ENTERPRISE_PROPERTY_TYPE_DATE
						|| $propertyInfo->Type == DrupalField::ENTERPRISE_PROPERTY_TYPE_DATETIME)
				) {
					$value = $fields[$propertyInfo->Name][0];
					if ( !empty($value) ) {
						$fields[$propertyInfo->Name] = array( strtotime($value) );
					}
				}
			}
		}

		// Set the content type of the node we are attempting to create.
		$documentId = BizPublishForm::getDocumentId( $publishForm );
		$contentType = self::convertDocumentId2ContentType( $documentId );
		$fields[self::DRUPAL7_CONTENT_TYPE] = array($contentType);

		return $fields;
	}
}