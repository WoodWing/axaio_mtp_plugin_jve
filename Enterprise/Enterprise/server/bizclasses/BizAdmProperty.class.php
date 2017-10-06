<?php

/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v7.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Server Plug-ins can extend the data model for Publication, PubChannel and Issue admin 
 * entities by providing a list of custom properties through their connector that implements
 * the AdminProperties_EnterpriseConnector connector interface. The DialogWidget data objects
 * returned by this interface contain AdmPropertyInfo data objects which are maintained by
 * this PHP module. During instalation time (running the Server Plugins page) it stores the
 * custom property definitions in the smart_properties table with help of the DBAdmProperty 
 * class. Having that in place, the custom properties are no longer read from the connector,
 * but from the database. Nevertheless, the connectors are still asked whether or not to draw the
 * custom properties on the maintenance pages.
 *
 * When the connector is unplugged, the custom properties are still stored in the database.
 * That is true for the definitions and for the user typed values. Nevertheless, the widgets
 * are no longer shown on the maintenance page. When the connector is plugged in again, the
 * custom properties appear at the maintenance pages again without loss of information.
 */

class BizAdmProperty
{
	/**
	 * Transforms an admin object into a list of AdmExtraMetaData objects.
	 * All properties are taken, which are actually carried by the object,
	 * which can be more than defined by the class.
	 *
	 * @param AdmPublication|AdmPubChannel|AdmIssue $obj
	 * @return array of AdmExtraMetaData
	 */
	static public function getMetaDataValues( $obj )
	{
		$values = array(); // we do not use named keys to be prepared for SOAP!
		foreach( array_keys( get_object_vars($obj) ) as $propName ) {
			if( $propName == 'ExtraMetaData' ) { // custom admin props
				$values = array_merge( $values, $obj->ExtraMetaData );
			} elseif($propName == 'SectionMapping') {
				$values = array_merge( $values, $obj->SectionMapping );
			} else { // built-in admin props
				$value = $obj->$propName;
				if( !is_array($value) ) {
					$value = array($value);
				}
				// We do NOT use prop names in array keys; SOAP does not!
				$values[] = new AdmExtraMetaData( $propName, $value );
			}
		}
		return $values;
	}

	/**
	 * Transforms a comma separated string into an array of strings.
	 * Typically used to parse user typed input string. Extra spaces
	 * between commas and string values are trimmed.
	 *
	 * @param string $strValue Comma separated string to parse.
	 * @return array of strings
	 */
	static private function commaSepStringToArray( $strValue )
	{
		$strValue = trim($strValue);
		$values = strlen($strValue) > 0 ? explode( ',', $strValue ) : array();
		foreach( $values as $key => $value ) {
			$values[$key] = trim($value);
		}
		return $values;
	}

	/**
	 * Populates all properties of a given admin object with default values. When there 
	 * is no default configured (at PropertyInfo->DefaultValue) it will fall back at a 
	 * proper value depending on its type. Typically called when building an admin entity 
	 * from scratch. Custom admin props are maintained at the $admObj->ExtraMetaData property.
	 *
	 * @param object $admObj The admin object to set properties for.
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param string $prefix An prefix that is used for all property names at the form.
	 * @param AdminProperties_Context $context Publication, Issue, etc for which the properties are maintained
	 */
	static public function buildDefaultAdmObj( &$admObj, $entity,
		/** @noinspection PhpUnusedParameterInspection */ $prefix,
		AdminProperties_Context $context )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$admObj->ExtraMetaData = array();
		
		// Create if entity id equals 0 else Update - For the collectDialogWidgetsForContext function
		$action = ($admObj->Id == 0) ? 'Create' : 'Update';

		// Collect all possible properties definitions (DialogWidget objects), no matter to be shown or not.
		// This includes our own standard/builtin props and custom props collected from Server Plug-ins.
		$builtinWidgets = self::collectDialogWidgets( $entity, false );
		$customWidgets = self::collectDialogWidgetsForContext( $context, $entity, $action );
		$allWidgets = array_merge( $builtinWidgets, $customWidgets );
		
		// Iterate through all properties and determine default values.
		foreach( $allWidgets as $widget ) {
			$propInfo = $widget->PropertyInfo;
			$values = self::getDefaultValue( $propInfo->Type, $propInfo->DefaultValue );
			if( BizProperty::isCustomPropertyName( $propInfo->Name ) ) {
				$extraMetaData = new AdmExtraMetaData();
				$extraMetaData->Property = $propInfo->Name;
				$extraMetaData->Values = $values;
				$admObj->ExtraMetaData[] = $extraMetaData;
			} else {
				$propName = $propInfo->Name;
				$admObj->$propName = $values[0];
			}
		}
	}

	/**
	 * Returns the default value for a widget in the correct format.
	 *
	 * @param string $type the type of the widget
	 * @param mixed $default the default value for the widget
	 * @return array containing the value
	 */
	static public function getDefaultValue($type, $default)
	{
		switch( $type )
		{
			case 'string':      $values = array(is_null($default) ? '' : $default); break;
			case 'multistring': $values = is_null($default) ? array() : self::commaSepStringToArray($default); break;
			case 'multiline':   $values = array(is_null($default) ? '' : $default); break;
			case 'bool':        $values = array((bool)$default ? '1' : '0'); break;
			case 'int':         $values = array(is_null($default) ? 0 : intval($default)); break;
			case 'double':      $values = array(is_null($default) ? 0.0 : floatval($default)); break;
			case 'date':        $values = array(is_null($default) ? '' : $default); break;
			case 'datetime':    $values = array(is_null($default) ? '' : $default); break;
			case 'list':        $values = array(is_null($default) ? '' : $default); break;
			case 'multilist':   $values = array(is_null($default) ? '' : $default); break;
			// TODO: Add new options below to the WSDL once $admObj->ExtraMetaData is made public!!!
			case 'password':    $values = array(is_null($default) ? '' : $default); break;
			//case 'language':    $values = array(is_null($default) ? '' : $default); break; // FUTURE
			//case 'color':       $values = array(is_null($default) ? '' : $default); break; // FUTURE
			case 'separator':   $values = array(); break; // dummy placeholder (no values)
			default:
				$values = array('');
				LogHandler::Log( 'BizAdmProperty', 'ERROR', 'Bad case "'.$type.'" in '.__METHOD__ );
				break;
		}

		return $values;
	}

	/**
	 * Populates all properties of a given admin object using HTTP request data ($_REQUEST).
	 * Typically called when a web form is submit (by admin user) and an admin entity
	 * needs to be built from from scratch based on the form fields (HTTP form submit).
	 * Custom admin props are maintained at the $admObj->ExtraMetaData property.
	 *
	 * @param AdmPublication|AdmPubChannel|AdmIssue $admObj The admin object to set properties for.
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param string $prefix An prefix that is used for all property names at the form.
	 * @param AdminProperties_Context $context The context. Is used to build the dialogs
	 * @throws BizException When mandatory field is empty.
	 */
	static public function buildAdmObjFromHttp( &$admObj, $entity, $prefix, AdminProperties_Context $context )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$throwMandatory = false;
		$admObj->ExtraMetaData = array();
		
		// Create if entity id equals 0 else Update - For the collectDialogWidgetsForContext function
		$action = ($admObj->Id == 0) ? 'Create' : 'Update';
		
		// Collect all possible properties definitions (DialogWidget objects), no matter to be shown or not.
		// This includes our own standard/builtin props and custom props collected from Server Plug-ins.
		// Note that we first collect the builtin props since those are needed to update the context.
		// Based on that context, we let the connector decide what custom props to add. For example this
		// is needed when the admin user changes the channel type and the connector adds channel specific props.
		$builtinWidgets = self::collectDialogWidgets( $entity, false );
		self::doBuildAdmObjFromHttp( $builtinWidgets, $admObj, $prefix, $throwMandatory );

		switch( $entity ) {
			case 'Publication':
				$orgAdmObj = $context->getPublication();
				self::copyBuiltinProps( $admObj, $orgAdmObj );
				$context->setPublication( $orgAdmObj );
			break;
			case 'PubChannel':
				$orgAdmObj = $context->getPubChannel();
				self::copyBuiltinProps( $admObj, $orgAdmObj );
				$context->setPubChannel( $orgAdmObj );
			break;
			case 'Issue':
				$orgAdmObj = $context->getIssue();
				self::copyBuiltinProps( $admObj, $orgAdmObj );
				$context->setIssue( $orgAdmObj );
			break;
		}
		
		$customWidgets = self::collectDialogWidgetsForContext( $context, $entity, $action );
		self::doBuildAdmObjFromHttp( $customWidgets, $admObj, $prefix, $throwMandatory );
		
		if( $throwMandatory ) {
			throw new BizException( 'ERR_MANDATORYFIELDS', 'Client', '' );
		}
	}
	
	/**
	 * Retrieves the values from HTTP request data ($_REQUEST) for the given set of
	 * widgets. When not present at HTTP, it takes the default value. The widgets could
	 * be either the builtin properties or the custom properties (or both). The given
	 * admin entity instance ($admObj) is updated with the determined values.
	 *
	 * @since 9.0.0 Split from the buildAdmObjFromHttp() function.
	 * @param DialogWidget[] $widgets
	 * @param object $admObj The admin object to set properties for.
	 * @param string $prefix An prefix that is used for all property names at the form.
	 * @param bool &$throwMandatory Whether or not the caller should throw exception.
	 * @throws BizException When mandatory field is empty.
	 */
	static public function doBuildAdmObjFromHttp( $widgets, &$admObj, $prefix, &$throwMandatory )
	{
		foreach( $widgets as $widget ) {
			$multi = false;
			$propName = $widget->PropertyInfo->Name;
			$propType = $widget->PropertyInfo->Type;
// BZ#31206 - For "datetime" type property, amend "_date" as suffix to the HTML field name and check from the $_REQUEST
// ???
			$isCustomProp = BizProperty::isCustomPropertyName( $propName );
			if ( $widget->PropertyUsage->Editable ) {// BZ#34387 prevent setting default value for non-editable properties.
				if( isset($_REQUEST[$prefix.$propName]) ) {
					$propValue = $_REQUEST[$prefix.$propName];
				} else {
					$propValue = $widget->PropertyInfo->DefaultValue;
				}
			} else {
				if( isset($_REQUEST[$prefix.$propName]) ) { // EN-85822 - The readonly field can be safely reassign back the value.
					$propValue = $_REQUEST[$prefix.$propName];
				} else {
					continue; // Do nothing where there is no value assign in the REQUEST field.
				}
			}
			
			switch( $propType ) {
				case 'string':      $values = array( trim($propValue) ); break;
				case 'multistring': $values = self::commaSepStringToArray($propValue); $multi = true; break;
				case 'multiline':   $values = array( trim($propValue) ); break;
				case 'bool':        $values = array( isset($_REQUEST[$prefix.$propName]) ); break; // untagged booleans are simply not posted!
				case 'int':         $values = array( intval($propValue) ); break;
				case 'double':      $values = array( floatval($propValue) ); break;
				case 'date':
					require_once BASEDIR.'/server/utils/htmlclasses/HtmlDateField.class.php';
					$dateField = new HtmlDateField( null, $prefix.$propName );
					$value = $dateField->requestValue();
					$values = array( !is_null($value) ? $value : '' );
					break;
				case 'datetime':
					require_once BASEDIR.'/server/utils/htmlclasses/HtmlDateTimeField.class.php';
					$dateField = new HtmlDateTimeField( null, $prefix.$propName );
					$value = $dateField->requestValue();
					$values = array( !is_null($value) ? $value : '' );
					break;
				case 'list':        $values = array( $propValue ); break;
				case 'multilist':   $values = isset($_REQUEST[$prefix.$propName]) ? $propValue : array(); $multi = true; break;
				// TODO: Add new options below to the WSDL once $admObj->ExtraMetaData is made public!!!
				case 'password':    $values = array( trim($propValue) ); break;
				//case 'language':    $values = array( trim($propValue) ); break; // FUTURE
				//case 'color':       $values = array( trim($propValue) ); break; // FUTURE
				case 'separator':   $values = array(); break; // dummy placeholder (no values)
				default:
					$values = array('');
					LogHandler::Log( 'BizAdmProperty', 'ERROR', 'Bad case "'.$propType.'" in '.__METHOD__ );
					break;
			}

			// BZ#32560 - There is an exception for the Publication property ReversedRead. This must be shown as a list in the UI,
			// but is a boolean in the wsdl and database model. So in this case we need to convert it to a boolean!
			if ( $propName == 'ReversedRead' && $propType == 'list' ) {
				$values = array( ($propValue == 'on') );
			}

			if( $isCustomProp ) {
				self::getCustomPropertyMetaDataValue( $admObj, $propName, $values );
			} else {
				$admObj->$propName = $multi ? $values : $values[0];
			}
			
			if( $widget->PropertyUsage->Mandatory && empty($values[0]) ) {
				if( !BizProperty::isHiddenCustomProperty( $widget->PropertyInfo ) ) {
					$throwMandatory = true; // Throw at the end to complete building the object first !
				}
			}
		}
	}
	
	/**
	 * Copy all builtin properties of a given admin entity instance. The custom
	 * properties are NOT copied.
	 *
	 * @since 9.0.0
	 * @param AdmPublication|AdmPubChannel|AdmIssue $srcObj   Source to copy from.
	 * @param AdmPublication|AdmPubChannel|AdmIssue &$destObj Destination to copy to.
	 */
	static private function copyBuiltinProps( $srcObj, &$destObj )
	{
		if( $srcObj ) foreach( get_object_vars( $srcObj ) as $propName => $propValue ) {
			if( $propName != 'ExtraMetaData' && $propName != 'SectionMapping' ) {
				$destObj->$propName = $propValue;
			}
		}
	}
	
	/**
	 * Get the custom property meta data values for a adm object. 
	 *
	 * @param object $admObj
	 * @param string $propName
	 * @param array $values
	 */
	static public function getCustomPropertyMetaDataValue(&$admObj, $propName, $values)
	{
		// TODO: Create a better solution for this hack. Needed for the sectionmapping
		if(isset($_REQUEST['currentsectionmapping']) && !empty($_REQUEST['currentsectionmapping']) &&
			( self::getPropertyInfos( 'Issue', 'Drupal', $propName ) || 
			  self::getPropertyInfos( 'Issue', 'Drupal7', $propName ) ) ) {
			$metadata = new AdmExtraMetaData( $propName, $values );
			$metadata->SectionId = intval($_REQUEST['currentsectionmapping']);
			$admObj->SectionMapping[] = $metadata;
		} else {
			$admObj->ExtraMetaData[] = new AdmExtraMetaData( $propName, $values );
		}
	}

	/**
	 * Determines the value(s) of a custom property.
	 *
	 * @param ExtraMetaData[]||AdmExtraMetaData[] $extraMetaData List of custom props to search in.
	 * @param string $propName Name of custom property to lookup.
	 * @param bool $multiVal Whether or not to return multi value (array).
	 * @return mixed The value(s) of the property. NULL when property was not found.
	 */
	static public function getCustomPropVal( $extraMetaData, $propName, $multiVal=false )
	{
		if( $extraMetaData ) foreach( $extraMetaData as $custProp ) {
			if( $custProp->Property == $propName ) {
				if( $multiVal ) {
					return $custProp->Values ? $custProp->Values : array(); // found, quit search
				} else {
					return $custProp->Values[0]; // found, quit search
				}
			}
		}
		return null; // not found
	}

	/**
	 * Set the value(s) of a custom property.
	 *
	 * @param AdmExtraMetaData[] $extraMetaData List of custom props to search in.
	 * @param string $propName Name of custom property to lookup.
	 * @param string $value The value of the custom property
	 */
	static public function setCustomPropVal( $extraMetaData, $propName, $value )
	{
		if( $extraMetaData ) foreach( $extraMetaData as $custProp ) {
			if( $custProp->Property == $propName ) {
				if( is_array( $value ) ) {
					$custProp->Values = $value;
				} else {
					$custProp->Values[0] = $value;
				}
			}
		}
	}

	/**
	 * Sorts a list of custom properties based on their (internal) name.
	 * This is useful for test scripts that want to check if custom propery values
	 * are round-tripped property correctly through the web serives. Having a sorted
	 * list, test scripts can do an exact compare (using WW_Utils_PhpCompare class).
	 * Note that the custom properties can be found at the ExtraMetaData attribute of
	 * objects and admin entities, which both are supported by this function.
	 * For the caller's convenience, when null is passed in, null is returned.
	 * 
	 * @param AdmExtraMetaData[]|null $extraMetaData
	 * @return array|null
	 */
	static public function sortCustomProperties( $extraMetaData )
	{
		$sorted = null;
		if( $extraMetaData ) {
			$sorted = array();
			foreach( $extraMetaData as $metaData ) {
				$sorted[$metaData->Property] = $metaData;
			}
			ksort( $sorted );
			$newKeys = range( 0, count( $sorted )-1 );
			$sorted = array_combine( $newKeys, array_values( $sorted ) );
		}
		return $sorted;
	}
	
	/**
	 * Returns a list of the custom property types of a given admin entity.
	 * Typically used to lookup the storage type / primitive data type of a prop.
	 *
	 * @since 9.0.0
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @return array with property names as keys and property types as values
	 */
	static public function getCustomPropertyTypes( $entity )
	{
		static $typeMap = array();
		if( isset($typeMap[$entity]) ) {
			return $typeMap[$entity];
		}
		$typeMap[$entity] = array();
		$propInfos = self::getPropertyInfos( $entity );
		foreach( $propInfos as $propInfo ) {
			$typeMap[$entity][$propInfo->Name] = $propInfo->Type;
		}
		return $typeMap[$entity];
	}

	/**
	 * Builds a list of AdmExtraMetaData data objects (custom props) for a given admin entity.
	 * For all properties (as configured for that entity by admin user) it takes the default 
	 * value and copies over the property value ($mdValues) if provided by the caller. Note that
	 * properties provided by caller that are not configured will be ignored (no part of result set).
	 * This combined set of custom property values is typically used to create/copy an entity.
	 *
	 * @since 9.0.0
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param array $mdValues List of AdmExtraMetaData objects
	 * @return array of AdmExtraMetaData
	 */
	static public function buildCustomMetaDataFromValues( $entity, $mdValues )
	{
		$customProps = array();
		$widgets = self::collectCustomDialogWidgets( $entity );
		foreach( $widgets as /*$propName => */ $widget ) {
			$propName = $widget->PropertyInfo->Name;
			$found = false;
			foreach( $mdValues as $index => $md ) {
				if( $md->Property == $propName ) {
					$found = $index;
					break;
				}
			}
			if( $found === false ) { // not found: fall back to default value
				$propValues = self::getDefaultValue(
										$widget->PropertyInfo->Type,
										$widget->PropertyInfo->DefaultValue );
			} else { // found: take over given value
				$propValues = $mdValues[$found]->Values;
				$propValues = is_array( $propValues ) ? $propValues : array( $propValues );
			}
			$extraMetaData = new AdmExtraMetaData();
			$extraMetaData->Property = $propName;
			$extraMetaData->Values = $propValues;
			$customProps[] = $extraMetaData;
		}
		return $customProps;
	}

	/**
	 * Uses a DB row of a given admin entity to build an array of AdmExtraMetaData objects.
	 * The result can be applied to the ExtraMetaData property of an admin object, which holds custom props.
	 * Does the opposite of enrichDBRowWithCustomMetaData function.
	 * Important! Since it constructs an Admin interface (AdmExtraMetaData), it should not be called by
	 * any workflow interface functions.
	 * 
	 * @deprecated Since 9.0.0. Use buildCustomMetaDataFromValues instead.
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param array $row
	 * @return array of AdmExtraMetaData objects
	 */
	static public function buildCustomMetaDataFromDBRow( $entity, $row )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$widgets = self::collectCustomDialogWidgets( $entity );
		$customProps = array();
		$row = array_change_key_case( $row, CASE_UPPER );

		foreach( $widgets as /*$propName => */$widget ) {
			$propName = $widget->PropertyInfo->Name;
			if( isset( $row[$propName] ) ) {
				$propVals = DBChanneldata::unpackPropValues( $widget->PropertyInfo->Type, $row[$propName] );
				$extraMetaData = new AdmExtraMetaData();
				$extraMetaData->Property = $propName;
				$extraMetaData->Values = $propVals;
				$customProps[] = $extraMetaData;
			}
		}
		return $customProps;
	}

	/**
	 * Uses a DB row of a issue entity to build an array of SectionMapping objects.
	 * The result can be applied to the SectionMapping property of an admin object, which holds the section mapping custom props.
	 * Does the same as buildCustomMetaDataFromDBRow only for section mapping
	 *
	 * @param array $row
	 * @return array of section mapping objects
	 */
	static public function buildSectionMappingFromDBRow( $row )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$widgets = self::collectCustomDialogWidgets( 'Issue' );
		$mdValues = array();
		foreach( $row as $sectionId => $data ) {
			$data = array_change_key_case( $data, CASE_UPPER );
			foreach( $widgets as /*$propName => */$widget ) {
				$propName = $widget->PropertyInfo->Name;
				if( isset( $data[$propName] ) ) {
					$propVals = DBChanneldata::unpackPropValues( $widget->PropertyInfo->Type, $data[$propName] );
					$mdValue = new AdmExtraMetaData( $propName, $propVals );
					$mdValue->SectionId = $sectionId;
					$mdValues[] = $mdValue;
				}
			}
		}
		return $mdValues;
	}
	
	/**
	 * Enriches a DB row of a given admin entity with data values of MetaDataValue objects.
	 * Does the opposite of buildCustomMetaDataFromDBRow function.
	 *
	 * @deprecated Since 9.0.0. Use buildCustomMetaDataFromValues instead.
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param array $mdValues List of MetaDataValue objects
	 * @param array $row DB row to update
	 */
	static public function enrichDBRowWithCustomMetaData( $entity, $mdValues, &$row )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$widgets = self::collectCustomDialogWidgets( $entity );
		foreach( $widgets as /*$propName => */$widget ) {
			$propName = $widget->PropertyInfo->Name;
			$found = -1;
			foreach( $mdValues as $index => $md ) {
				if( $md->Property == $propName ) {
					$found = $index;
					break;
				}
			}
			if( $found != -1 ) {
				$propVals = $mdValues[$found]->Values;
				if( !is_null($propVals) ) {
					$row['ExtraMetaData'][$propName] =
						DBChanneldata::packPropValues( $widget->PropertyInfo->Type, $propVals );
				}
			}
		}
	}
	
	/**
	 * Enriches a DB row of a given array with section mapping with data values of MetaDataValue objects.
	 * Does the same as enrichDBRowWithCustomMetaData exept this can handle the section ids
	 * This is only needed for the entity issue
	 *
	 * @param array $mdValues List of MetaDataValue objects
	 * @param array $row DB row to update
	 */
	static public function enrichDBRowWithSectionMapping( $mdValues, &$row )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$widgets = self::collectCustomDialogWidgets( 'Issue' );
		foreach( $widgets as /*$propName => */$widget ) {
			$propName = $widget->PropertyInfo->Name;
			$found = array();
			foreach( $mdValues as $index => $md ) {
				if( $md->Property == $propName ) {
					$found[] = $index;
				}
			}
			if( !empty($found) ) {
				foreach($found as $index) {
					$propVals = $mdValues[$index]->Values;
					$propSectionId = $mdValues[$index]->SectionId;
					if( !is_null($propVals) ) {
						$row['SectionMapping'][$propSectionId][$propName] = 
							DBChanneldata::packPropValues( $widget->PropertyInfo->Type, $propVals );
					}
				}
			}
		}
	}

	/**
	 * Creates new Utils_XHtmlField based on given property definition (DialogWidget).
	 *
	 * @param Utils_XHtmlDocument $doc
	 * @param Utils_XHtmlForm $form
	 * @param string $prefix Prefix used for form field names.
	 * @param DialogWidget $widget
	 * @return Utils_XHtmlField
	 */
	static public function newHtmlField( /** @noinspection PhpUnusedParameterInspection */ Utils_XHtmlDocument $doc,
		Utils_XHtmlForm $form, $prefix, DialogWidget $widget )
	{
		$inf = $widget->PropertyInfo;
		$use = $widget->PropertyUsage;
		$field = $form->addField( $inf->Type, $prefix.$inf->Name, $prefix.$inf->Name );
		if( $inf->Type == 'list' || $inf->Type == 'multilist' ) {
			if( $inf->ValueList ) {
				$field->setOptions( $inf->ValueList );
			} elseif( $inf->PropertyValues ) {
				$options = array();
				foreach( $inf->PropertyValues as $propValue ) {
					$options[ $propValue->Value ] = $propValue->Display;
				}
				$field->setOptions( $options );
			}
		}
		$field->setMaxLength( $inf->MaxLength );
		$field->setReadOnly( !$use->Editable );
		return $field;
	}
	
	/**
	 * Tells which admin entities can have custom admin properties.
	 *
	 * @since 9.0.0
	 * @return string[] Internal names of admin entities: 'Publication', 'PubChannel' and 'Issue'
	 */
	static public function getSupportedEnties()
	{
		return array( 'Publication', 'PubChannel', 'Issue' );
	}

	/**
	 * Check if the collectDialogWidgets functions (implemented by server plug-ins) return correct structure.
	 * If so, the provided custom properties (through that function) are automatically created at DB.
	 * This is done by expanding the columns of DB tables.
	 *
	 * @param array|null $pluginErrs Empty array to collect errors. NULL to let function throw BizException on errors.
	 * @throws BizException When any collectDialogWidgets returns bad structure
	 */
	static public function validateAndInstallCustomProperties( &$pluginErrs )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Iterate through supported entities
		foreach( self::getSupportedEnties() as $entity ) {

			// Retrieve custom property definitions from server plug-ins
			$connRetVals = array(); // return values of custom server plug-in connectors
			BizServerPlugin::runDefaultConnectors( 'AdminProperties', null, 
									'collectDialogWidgets', array($entity), $connRetVals );
			// Validate property definitions retrieved from plugins
			self::validateCustomProperties( $entity, $connRetVals, $pluginErrs );

			// Store custom property defintions at DB and extend the DB model
			self::installCustomProperties( $entity, $connRetVals, $pluginErrs );
		}
	}

	/**
	 * Validates property definitions retrieved from server plug-ins
	 *
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param array $connRetVals Custom property definition, as returned by plugin connectors (key = connector name, value = array of DialogWidget objects)
	 * @param array|null $pluginErrs Empty array to collect errors. NULL to let function throw BizException on errors.
	 * @throws BizException When any property definition breaks the rules
	 */
	static private function validateCustomProperties( $entity, array $connRetVals, &$pluginErrs )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';

		$allWidgets = self::collectDialogWidgets( $entity, false ); // only built-in props (exclude custom props!)

		// Very strict validation on return values
		foreach( $connRetVals as $connName => $connWidgets ) { // key = connector name, value = array of DialogWidget objects
			try {
				if( !is_array( $connWidgets ) ) {
					throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectDialogWidgets function '.
						'should return an array of DialogWidget objects. Returned value is not of type array.' );
				}
				foreach( $connWidgets as $propName => $connWidget ) { // key = internal custom property name, value = DialogWidget object
					if( !is_object($connWidget) || get_class($connWidget) !== 'DialogWidget' ) {
						throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectDialogWidgets function '.
							'should return an array of DialogWidget objects. No DialogWidget object was found at index "'.$propName.'".' );
					}
					if( $propName !== $connWidget->PropertyInfo->Name ||
						$propName !== $connWidget->PropertyUsage->Name ) {
						throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectDialogWidgets function '.
							'returns an array of DialogWidget objects. The index of the array should represent the internal '.
							'property name and should match PropertyInfo->Name and PropertyUsage->Name. For index "'.$propName.'" this is not the case.' );
					}
					if( !BizProperty::isCustomPropertyName( $propName ) ) {
						throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectDialogWidgets function '.
							'returns an array of DialogWidget objects. The internal property names should have "C_" prefix. '.
							'For property "'.$propName.'" this is not the case.' );
					}
					if( preg_match("/^C_[A-Z0-9_]{2,28}$/", $propName) == 0 ) {
						throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectDialogWidgets function '.
							'returns an array of DialogWidget objects. The internal property names should contain A-Z (uppercase) or 0-9 characters only. '.
							'Underscores (_) are allowed. And, due to DB limitations, names should contain no more than 30 characters. '.
							'For property "'.$propName.'" this is not the case.' );
					}
					if( isset( $allWidgets[$propName] ) ) {
						throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectDialogWidgets function '.
							'returns an array of DialogWidget objects. The internal property names should be unique for the entire system. '.
							'For property "'.$propName.'" this is not the case.' );
					}
				}
			} catch( BizException $e ) {
				if( is_array($pluginErrs) ) { // the caller wants us to collect errors?
					$pluginKey = BizServerPlugin::getPluginUniqueNameForConnector( $connName );
					$pluginErrs[$pluginKey] = $e->getMessage()."\n".$e->getDetail();
				} else { // the caller does collect errors, so we (re)throw
					throw $e;
				}
			}
			$allWidgets = array_merge( $allWidgets, $connWidgets );
		}
	}

	/**
	 * Removes unused properties from DB and checks if new custom props are unique at DB
	 *
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param string $table DB table name
	 * @param array $connRetVals Custom property definition, as returned by plugin connectors (key = connector name, value = array of DialogWidget objects)
	 * @throw BizException When any property not unique in DB
	 */
	/*static private function cleanDBAndCheckNewCustomProperties( $entity, $connRetVals )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmProperty.class.php';
		
		foreach( $connRetVals as $connName => $connWidgets ) { // key = connector name, value = array of DialogWidget objects
			$plugin = BizServerPlugin::getPluginForConnector( $connName );

			// Remove PropInfo and PropUsage from DB that are no longer used by this plugin
			$excludePropNames = array_keys( $connWidgets ); // exlcude props we are about to insert/update
			DBAdmProperty::deleteUnusedPluginPropInfos( $entity, $plugin->UniqueName, $excludePropNames );

			// Check if all new PropInfo and PropUsage are unique at DB
			foreach( array_keys($connWidgets) as $propName ) { // key = internal custom property name, value = DialogWidget object
				$admPIs = DBAdmProperty::getPropertyInfos( $entity, null, $propName );
				if( count($admPIs) > 0 ) {
					$admPI = $admPIs[0];
					if( count($admPIs) > 1 || $plugin->UniqueName != $admPI->PluginName ) {
						throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectDialogWidgets function '.
							'returns an array of DialogWidget objects. The internal property names should be unique for the entire system. '.
							'For property "'.$propName.'" of "'.$plugin->UniqueName.'" plug-in this is not the case; it is already used by the "'.$admPI->PluginName.'" plug-in.' );
					}
				}
			}
		}
	}*/

	/**
	 * Stores custom property defintions at DB and extends the DB model
	 *
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param array $connRetVals Custom property definition, as returned by plugin connectors (key = connector name, value = array of DialogWidget objects)
	 * @param array|null $pluginErrs Empty array to collect errors. NULL to let function throw BizException on errors.
	 * @throws BizException When any property not unique in DB
	 */
	static private function installCustomProperties( $entity, $connRetVals, &$pluginErrs )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmProperty.class.php';

		// Get all custom admin property definitions that are recorded in DB for the given
		// admin entity, which were installed by our and other server plugins.
		$allPropInfos = self::getPropertyInfos( $entity );
		
		// Replace the numneric key [0...n] with the property names for quick lookup later.
		// Note that those names are unique per entity, so that's a safe thing to do.
		if( $allPropInfos ) foreach( $allPropInfos as $numKey => $propInfo ) {
			unset( $allPropInfos[$numKey] );
			$allPropInfos[$propInfo->Name] = $propInfo;
		}
		
		// Before installing anything, let's check if some of the plugins gives us
		// a definition that is already in use by another plugin. This could only happen
		// when the prefixes of the custom props happen to be the same, which would be
		// very unlucky coincidence, but something we want to avoid at this point.
		foreach( $connRetVals as $connName => $connWidgets ) { // key = connector name, value = array of DialogWidget objects
			$plugin = BizServerPlugin::getPluginForConnector( $connName );
			try {
				foreach( $connWidgets as $propName => $connWidget ) { // key = internal custom property name, value = DialogWidget object
					if( isset( $allPropInfos[$propName] ) ) {
						$admPropInfo = $allPropInfos[$propName];
						if( $admPropInfo->PluginName != $plugin->UniqueName ) {
							throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', 'The '.$connName.'->collectDialogWidgets function '.
								'returns an array of DialogWidget data objects. Those represent custom admin property definitions '.
								'which are registered in the database (for fast look-ups during production). '.
								'A known limitation is that two plugins cannot define the same property (name) for the same admin entity. '.
								'However, the "'.$plugin->UniqueName.'" plug-in attempted to insert a property named "'.$propName.'", '.
								'which was already registered by the "'.$admPropInfo->PluginName.'" plug-in for admin entity "'.$entity.'". '.
								'To resolve this conflict, please refer to the provider of these plug-ins for further assistence. ' );
						}
						if( $admPropInfo->Type != $connWidget->PropertyInfo->Type ) {
							throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', 'The '.$connName.'->collectDialogWidgets function '.
								'returns an array of DialogWidget data objects. Those represent custom admin property definitions '.
								'which are registered in the database (for fast look-ups during production). '.
								'A known limitation is that once a property has been registered, its storage type cannot be changed. '.
								'However, property "'.$propName.'" is currently registered as "'.$admPropInfo->Type.'", '.
								'while the "'.$admPropInfo->PluginName.'" plug-in attempted to change it into "'.$connWidget->PropertyInfo->Type.'". '.
								'To resolve this conflict, please refer to the provider of this plug-in for further assistence. ' );
						}
					}
				}
			} catch( BizException $e ) {
				if( is_array($pluginErrs) ) { // the caller wants us to collect errors?
					$pluginErrs[$plugin->UniqueName] = $e->getMessage()."\n".$e->getDetail();
				} else { // the caller does collect errors, so we (re)throw
					throw $e;
				}
			}
		}

		// Now all checking is done, it is safe to register the property definitions at DB.
		// Existing registrations are updated, else new registrations are created.
		foreach( $connRetVals as $connName => $connWidgets ) { // key = connector name, value = array of DialogWidget objects
			$plugin = BizServerPlugin::getPluginForConnector( $connName );
			foreach( $connWidgets as $propName => $connWidget ) { // key = internal custom property name, value = DialogWidget object
				if( isset( $allPropInfos[$propName] ) ) { // update?
					$admPropInfo = $allPropInfos[$propName];
					self::copyToAdmPropertyInfo( $connWidget->PropertyInfo, $admPropInfo ); // updates $admPropInfo object
					DBAdmProperty::updateAdmPropertyInfo( $admPropInfo ); // store $admPropInfo at DB
				} else { // insert?
					// Make sure there is an AdmPropertyInfo recorded representing the DB model (column)
					$admPropInfo = self::castToAdmPropertyInfo( $connWidget->PropertyInfo );
					$admPropInfo->PluginName = $plugin->UniqueName;
					$admPropInfo->Entity = $entity;
					$admPropInfo->Id = 0;
					$admPropInfo->PublicationId = 0;
					$admPropInfo->ObjectType = '';
					$admPropInfo->DBUpdated = true;
					DBAdmProperty::insertAdmPropertyInfo( $admPropInfo ); // store $admPropInfo at DB
				}
			}
		}
		// NOTE: Unlike PropertyInfo objects, we do NOT need to store PropertyUsage objects from plugins.
		// This is because the plugins determine how props must be used depending on many contextual parameters.
	}

	/**
	 * Lists all built-in property defintions for an entity.
	 * Collects all possible properties definitions (DialogWidget objects), no matter to be shown or not.
	 * This includes our own standard/builtin props and custom props collected from Server Plug-ins.
	 *
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param bool $includeCustom
	 * @return DialogWidget[]
	 */
	private static function collectDialogWidgets( $entity, $includeCustom=true )
	{
		switch( $entity ) {
			case 'Publication':
				$allWidgets = self::collectPublicationWidgets();
				break;
			case 'PubChannel':
				$allWidgets = self::collectPubChannelWidgets();
				break;
			case 'Issue':
				$allWidgets = self::collectIssueWidgets();
				break;
			default:
				$allWidgets = null;
				break;
		}
		if( is_null($allWidgets) ) {
			return null; // something bad that should not happen
		}
		if( $includeCustom ) {
			$allWidgets = array_merge( $allWidgets, self::collectCustomDialogWidgets( $entity ) );
		}
		return $allWidgets;
	}

	/**
	 * Same as collectDialogWidgets but then custom properties only.
	 *
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @return DialogWidget[]
	 */
	public static function collectCustomDialogWidgets( $entity )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connRetVals = array();
		$widgets = array();
		BizServerPlugin::runDefaultConnectors( 'AdminProperties', null, 
							'collectDialogWidgets', array($entity), $connRetVals );
		foreach( $connRetVals as $connRetVal ) {
			if( is_array( $connRetVal ) ) {
				$widgets = array_merge( $widgets, $connRetVal );
			}
		}
		return $widgets;
	}

	/**
	 * Calls the connectors implementing the AdminProperties_EnterpriseConnector interface 
	 * to collect properties that should travel along with the admin entity. See more info:
	 *    Enterprise/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php
	 *
	 * @param AdminProperties_Context $context Publication, Issue, etc for which the properties are maintained
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param string $action User operation: Create or Update.
	 * @return DialogWidget[]
	 */
	private static function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$widgets = array();
		$adminConnectors = BizServerPlugin::searchConnectors( 'AdminProperties', null );
		foreach( $adminConnectors as $adminConnector ) {
		
			// First try calling the new collectDialogWidgetsForContext() function that was
			// added since 9.0.0. It gives only those properties that are relevant for the
			// given context, which is much more efficient in terms of storing properties 
			// per admin entity instance in our DB.
			$params = array( $context, $entity, $action );
			$connWidgets = BizServerPlugin::runConnector( $adminConnector, 
								'collectDialogWidgetsForContext', $params );
			
			// When the connector did not implement the above, we fall back to the old
			// collectDialogWidgets() function the connector must have implemented.
			// This is less efficient storage, but better too many than too little info.
			if( is_null( $connWidgets ) ) {
				$connWidgets = BizServerPlugin::runConnector( $adminConnector, 
									'collectDialogWidgets', array($entity) );
			}
			
			// Collect all widgets from all connectors (for the given context).
			if( is_array( $connWidgets ) ) {
				$widgets = array_merge( $widgets, $connWidgets );
			}
		}
		return $widgets;
	}

	/**
	 * Same as collectDialogWidgets, but then for the built-in properties of the Publication entity.
	 *
	 * @return DialogWidget[]
	 */
	private static function collectPublicationWidgets()
	{
		static $widgets = null;
		if( $widgets ) {
			return $widgets;
		}
		$widgets = array();
		$widgets['Name'] = new DialogWidget(
			new PropertyInfo( 'Name', BizResources::localize( 'OBJ_NAME' ), null, 'string', '', null, null, null, 255 ),
			new PropertyUsage( 'Name', true, true, false, false ) ); // mandatory
		$widgets['EmailNotify'] = new DialogWidget(
			new PropertyInfo( 'EmailNotify', BizResources::localize( 'WFL_EMAIL_NOTIFICATIONS' ), null, 'bool', false ),
			new PropertyUsage( 'EmailNotify', true, false, false, false ) );
		$widgets['Description'] = new DialogWidget(
			new PropertyInfo( 'Description', BizResources::localize( 'OBJ_DESCRIPTION' ), null, 'multiline', '' ),
			new PropertyUsage( 'Description', true, false, false, false ) );
		$readingDirection = array(
			'' => BizResources::localize('OBJ_TABLE_LANGUAGE_LTR'),
			'on' => BizResources::localize('OBJ_TABLE_LANGUAGE_RTL') );
		$widgets['ReversedRead'] = new DialogWidget(
			new PropertyInfo( 'ReversedRead', BizResources::localize( 'READING_ORDER_REV' ), null, 'list', '', $readingDirection ),
			new PropertyUsage( 'ReversedRead', true, false, false, false ) );
		$widgets['AutoPurge'] = new DialogWidget(
			new PropertyInfo( 'AutoPurge', BizResources::localize( 'AUTO_PURGE' ), null, 'int', 0 ),
			new PropertyUsage( 'AutoPurge', true, false, false, false ) );
		$widgets['DefaultChannelId'] = new DialogWidget(
			new PropertyInfo( 'DefaultChannelId', BizResources::localize( 'DEFAULT_CHANNEL' ), null, 'list' ),
			new PropertyUsage( 'DefaultChannelId', true, false, false, false ) );
		$widgets['CalculateDeadlines'] = new DialogWidget(
			new PropertyInfo( 'CalculateDeadlines', BizResources::localize( 'RELATIVE_DEADLINE_MODE' ), null, 'bool', false ),
			new PropertyUsage( 'CalculateDeadlines', true, false, false, false ) );
		return $widgets;
	}

	/**
	 * Same as collectDialogWidgets, but then for the built-in properties of the PubChannel entity.
	 *
	 * @return DialogWidget[]
	 */
	private static function collectPubChannelWidgets()
	{
		static $widgets = null;
		if( $widgets ) {
			return $widgets;
		}
		$widgets = array();
			// PropertyInfo( Name, DisplayName, Category, Type, ValueList, 
			//               MinValue, MaxValue, MaxLength, PropertyValues )
			// PropertyUsage( Name, Editable, Mandatory, Restricted, RefreshOnChange )
		$widgets['Name'] = new DialogWidget(
			new PropertyInfo( 'Name', BizResources::localize( 'OBJ_NAME' ), null, 'string', '', null, null, null, 255 ),
			new PropertyUsage( 'Name', true, true, false, false )); // mandatory
		$widgets['Description'] = new DialogWidget(
			new PropertyInfo( 'Description', BizResources::localize( 'OBJ_DESCRIPTION' ), null, 'multiline', '', null, null, null, 255 ),
			new PropertyUsage( 'Description', true, false, false, false ));
		$widgets['Type'] = new DialogWidget(
			new PropertyInfo( 'Type', BizResources::localize( 'CHANNEL_TYPE' ), null, 'list', 'print' ),
			new PropertyUsage( 'Type', true, true, false, false )); // mandatory
		$widgets['CurrentIssueId'] = new DialogWidget(
			new PropertyInfo( 'CurrentIssueId', BizResources::localize( 'CURRENT_ISSUE' ), null, 'list' ),
			new PropertyUsage( 'CurrentIssueId', true, false, false, false ));
		$widgets['PublishSystem'] = new DialogWidget(
			new PropertyInfo( 'PublishSystem', BizResources::localize( 'PUBLISH_SYSTEM' ), null, 'list', '' ),
			new PropertyUsage( 'PublishSystem', true, false, false, false ));
		$widgets['SuggestionProvider'] = new DialogWidget(
			new PropertyInfo( 'SuggestionProvider', BizResources::localize( 'SUGGESTION_PROVIDER' ), null, 'list', '' ),
			new PropertyUsage( 'SuggestionProvider', true, false, false, false ));
		return $widgets;
	}

	/**
	 * Same as collectDialogWidgets, but then for the built-in properties of the Issue entity.
	 *
	 * @return DialogWidget[]
	 */
	private static function collectIssueWidgets()
	{
		static $widgets = null;
		if( $widgets ) {
			return $widgets;
		}
		$widgets = array();
		$widgets['Name'] = new DialogWidget(
			new PropertyInfo( 'Name', BizResources::localize( 'OBJ_NAME' ), null, 'string', '', null, null, null, 255 ),
			new PropertyUsage( 'Name', true, true, false, false )); // mandatory
		$widgets['PublicationDate'] = new DialogWidget(
			new PropertyInfo( 'PublicationDate', BizResources::localize( 'PUBLICATION_DATE' ), null, 'datetime', '' ),
			new PropertyUsage( 'PublicationDate', true, false, false, false ));
		$widgets['Deadline'] = new DialogWidget(
			new PropertyInfo( 'Deadline', BizResources::localize( 'ISS_DEADLINE' ), null, 'datetime', '' ),
			new PropertyUsage( 'Deadline', true, false, false, false ));
		$widgets['ExpectedPages'] = new DialogWidget(
			new PropertyInfo( 'ExpectedPages', BizResources::localize( 'ISS_EXPECTED_PAGES' ), null, 'int', 0 ),
			new PropertyUsage( 'ExpectedPages', true, false, false, false ));
		$widgets['Subject'] = new DialogWidget(
			new PropertyInfo( 'Subject', BizResources::localize( 'ISS_SUBJECT' ), null, 'multiline', '' ),
			new PropertyUsage( 'Subject', true, false, false, false ));
		$widgets['Description'] = new DialogWidget(
			new PropertyInfo( 'Description', BizResources::localize( 'OBJ_DESCRIPTION' ), null, 'multiline', '' ),
			new PropertyUsage( 'Description', true, false, false, false ));
		$widgets['Activated'] = new DialogWidget(
			new PropertyInfo( 'Activated', BizResources::localize( 'ISS_ACTIVE' ), null, 'bool', true ),
			new PropertyUsage( 'Activated', true, false, false, false ));
		$widgets['OverrulePublication'] = new DialogWidget(
			new PropertyInfo( 'OverrulePublication', BizResources::localize( 'PUB_OVERRULE_PUBLICATION' ), null, 'bool', false ),
			new PropertyUsage( 'OverrulePublication', true, false, false, false ));
		$widgets['ReversedRead'] = new DialogWidget(
			new PropertyInfo( 'ReversedRead', BizResources::localize( 'READINGDIRECTION' ), null, 'bool', false ),
			new PropertyUsage( 'ReversedRead', true, false, false, false ));
		$widgets['CalculateDeadlines'] = new DialogWidget(
			new PropertyInfo( 'CalculateDeadlines', BizResources::localize( 'RELATIVE_DEADLINE_MODE' ), null, 'bool', false ),
			new PropertyUsage( 'CalculateDeadlines', true, false, false, false ) );
		return $widgets;
	}
	
	/**
	 * Determines which properties to show for a given admin data object entity. It builds a
	 * list of properties (DialogWidget data objects) and puts them in the order to show to 
	 * admin user. The Server Plug-ins that carry a AdminProperties connector are requested 
	 * to add their custom properties. They may want to reorganize the properties on-the-fly
	 * which includes the standard properties. Also the properties to be shown might vary
	 * depending on the given action (Create or Update), as to be determined by the connectors.
	 *
	 * @param AdminProperties_Context $context Publication, Issue, etc for which the properties are maintained
	 * @param string $entity Configuration type: Publication, PubChannel or Issue
	 * @param string $action User operation: Create or Update.
	 * @param DialogWidget[] $hideWidgets Properties to store with entity, but to hide from dialog.
	 * @return DialogWidget[] Properties to show in given situation.
	 */
	public static function buildDialogWidgets( AdminProperties_Context $context, $entity, $action, array &$hideWidgets )
	{
		// Collect all possible properties definitions (DialogWidget objects), no matter to be shown or not.
		// This includes our own standard/builtin props and custom props collected from Server Plug-ins.
		$builtinWidgets = self::collectDialogWidgets( $entity, false );
		$customWidgets = self::collectDialogWidgetsForContext( $context, $entity, $action );
		$allWidgets = array_merge( $builtinWidgets, $customWidgets );

		// Build standard dialog
		switch( $entity )
		{
			case 'Publication':
				require_once BASEDIR.'/server/bizclasses/BizAutoPurge.class.php';
				$purgeEnabled = BizAutoPurge::isAutoPurgeEnabled();
				$showWidgets = $builtinWidgets;
				if( !$purgeEnabled ) {
					unset( $showWidgets['AutoPurge'] );
				}
				break;
			case 'PubChannel':
				$showWidgets = $builtinWidgets;
				if( $action == 'Create' ) {
					unset( $showWidgets['CurrentIssueId'] );
				}
				break;
			case 'Issue':
				$showWidgets = self::buildIssueWidgets( $context, $builtinWidgets );
				break;
			default: // unsupported entity
				$showWidgets = null;
				break;
		}
		if( is_null($showWidgets) ) {
			return null;
		}
		$showWidgets = array_values($showWidgets); // remove the named keys (to prepare for SOAP in future!)

		// Allow server plug-ins to customize the standard dialog
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connRetVals = array();
		$params = array( $context, $entity, $action, $allWidgets, &$showWidgets ); // Allow adjusting $showWidgets!
		BizServerPlugin::runDefaultConnectors( 'AdminProperties', null, 'buildDialogWidgets', $params, $connRetVals );

		// Determine hidden widgets (the ones that are not shown); allWidgets - showWidgets
		$hideWidgets = array();
		foreach( $allWidgets as $allWidget ) {
			if( self::hasWidget( $showWidgets, $allWidget->PropertyInfo->Name ) === false ) {
				$hideWidgets[] = $allWidget;
			}
		}
		return $showWidgets;
	}

	public static function hasWidget( array $widgets, $propName )
	{
		foreach( $widgets as $key => $widget ) {
			if( $widget->PropertyInfo->Name == $propName ) {
				return $key;
			}
		}
		return false;
	}

	/**
	 * Same as buildDialogWidgets, but then for the built-in properties of the Issue entity.
	 *
	 * @static
	 * @param AdminProperties_Context $context
	 * @param DialogWidget[] $builtinWidgets
	 * @return DialogWidget[]
	 */
	private static function buildIssueWidgets( AdminProperties_Context $context, array $builtinWidgets )
	{
		$channelObj = $context->getPubChannel();
		$issueObj = $context->getIssue();
		$isPrintChannel    = strtolower($channelObj->Type) == 'print';
		$isApChannel       = strtolower($channelObj->Type) == 'dps2';
		$showWidgets = $builtinWidgets;
		if( !$isPrintChannel && !$isApChannel ) {
			unset( $showWidgets['PublicationDate'] );
			unset( $showWidgets['Deadline'] );
			unset( $showWidgets['ExpectedPages'] );
			unset( $showWidgets['OverrulePublication'] );
		}
		if( !(($isPrintChannel || $isApChannel) && $issueObj->OverrulePublication) ) {
			unset( $showWidgets['ReversedRead'] );
		}
		if( !$issueObj->OverrulePublication ) {
			// For normal issue, the CalculateDeadlines flag is configured at Brand level.
			unset( $showWidgets['CalculateDeadlines'] );
		}
		return $showWidgets;
	}

	/**
	 * Casts PropertyInfo to AdmPropertyInfo class.
	 *
	 * @param PropertyInfo $info
	 * @return AdmPropertyInfo
	 */
	static public function castToAdmPropertyInfo( PropertyInfo $info )
	{
		require_once BASEDIR.'/server/utils/PHPClass.class.php';
		return WW_Utils_PHPClass::typeCast( $info, 'AdmPropertyInfo' );
	}

	/**
	 * Copies all properties of PropertyInfo to AdmPropertyInfo class.
	 *
	 * @param PropertyInfo $infoFrom
	 * @param AdmPropertyInfo $admInfoTo
	 * @return void
	 */
	static private function copyToAdmPropertyInfo( PropertyInfo $infoFrom, AdmPropertyInfo $admInfoTo )
	{
		require_once BASEDIR.'/server/utils/PHPClass.class.php';
		WW_Utils_PHPClass::copyObjPropsIntersect( $infoFrom, $admInfoTo );
	}

	/**
	 * Inserts a new AdmPropertyInfo object in the database.
	 *
	 * @static
	 * @param AdmPropertyInfo $propertyInfo The AdmPropertyInfo to be inserted.
	 * @return AdmPropertyInfo|null Null when the update fails, the updated object otherwise.
	 */
	static public function insertAdmPropertyInfo( AdmPropertyInfo $propertyInfo )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmProperty.class.php';
		try {
			DBAdmProperty::insertAdmPropertyInfo( $propertyInfo );
		} catch ( BizException $e ) {
			$propertyInfo = null;
		}
		return $propertyInfo;
	}

	/**
	 * Updates an AdmPropertyInfo object in the database.
	 *
	 * @static
	 * @param AdmPropertyInfo $propertyInfo The AdmPropertyInfo to be updated.
	 * @return AdmPropertyInfo|null Null when the update fails, the updated object otherwise.
	 */
	static public function updateAdmPropertyInfo( AdmPropertyInfo $propertyInfo )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmProperty.class.php';
		try {
			DBAdmProperty::updateAdmPropertyInfo( $propertyInfo );
		} catch ( BizException $e ) {
			$propertyInfo = null;
		}
		return $propertyInfo;
	}

	/**
	 * Deletes an AdmPropertyInfo object from the database.
	 *
	 * @static
	 * @param AdmPropertyInfo $propertyInfo The object to be deleted.
	 * @return bool Whether or not the object could be deleted.
	 */
	static public function deleteAdmPropertyInfo( AdmPropertyInfo $propertyInfo )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmProperty.class.php';
		try {
			$deleted = DBAdmProperty::deleteAdmPropertyInfo( $propertyInfo );
		} catch ( BizException $e ) {
			$deleted = false;
		}
		return $deleted;
	}

	/**
	 * Retrieves an AdmPropertyInfo definition for a given admin entity from DB.
	 * This info was specified by a server plug-in before and stored in DB.
	 *
	 * @param string|null $entity Configuration type: Publication, PubChannel or Issue. NULL for all.
	 * @param string|null $pluginName Name of the server plug-in. NULL for all.
	 * @param string|null $propName Name of the custom admin property. NULL for all.
	 * @return AdmPropertyInfo[]
	 */
	static public function getPropertyInfos( $entity = null, $pluginName = null, $propName = null )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmProperty.class.php';
		return DBAdmProperty::getPropertyInfos( $entity, $pluginName, $propName );
	}
}
