<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/ads/DataClasses.php';
require_once BASEDIR . '/server/interfaces/services/dat/DataClasses.php';

/**
* Below are some functions to (correctly) create objects of a specific type
* These objects will all function as return values for SOAP calls
*/
class BizDatasourceUtils
{	
	/**
	 * Convert a record to a DatRecord object.
	 *
	 * Convert loose parameters to a record object.
	 *
	 * @param string $id Record id.
	 * @param RecordField[] $fields List of fields.
	 * @param string $updateType
	 * @param string $updateResponse
	 * @param bool $hidden
	 * @return DatRecord
	 */
	public static function recordToObj( $id, $fields, $updateType='none', $updateResponse = 'none', $hidden=false )
	{
		$record = new DatRecord();
		$record->ID					= $id;
		$record->UpdateType			= $updateType;
		$record->UpdateResponse		= $updateResponse;
		$record->Hidden				= $hidden;
		$record->Fields				= $fields;
		return $record;
	}
	
	
	public static function datasourceTypeToObj( $input )
	{
		$type = new AdsDatasourceType();
		$type->Type						= $input;
		return $type;
	}
	
	
	public static function settingToObj( $input )
	{
		$setting = new AdsSetting();
		$setting->ID					= $input["id"];
		$setting->Name					= $input["name"];
		$setting->Value					= $input["value"];
		return $setting;
	}
	
	
	public static function publicationToObj( $input )
	{
		$publication = new AdsPublication();
		$publication->ID				= $input["id"];
		$publication->Name				= $input["publication"];
		$publication->Description		= $input["description"];
		return $publication;
	}
	
	
	public static function datasourceToObj( $input )
	{
		$datasource = new DatDatasourceInfo();
		$datasource->ID					= $input['id'];
		$datasource->Bidirectional		= $input['bidirectional']; // This prop is not at WSDL !?
		$datasource->Name 				= $input['name'];
		return $datasource;
	}
	
	
	public static function adminDatasourceToObj( $input )
	{
		$datasource = new AdsDatasourceInfo();
		$datasource->ID					= $input['id'];
		$datasource->Name 				= $input['name'];
		$datasource->Bidirectional		= $input['bidirectional'];
		$datasource->Type				= $input['type'];
		return $datasource;
	}
	
	
	public static function settingUIToObj( $name, $description, $type, $list='', $size='')
	{
		$settingUI = new AdsSettingsDetail();
		$settingUI->Name			= $name;
		$settingUI->Description		= $description;
		$settingUI->Type			= $type;
		$settingUI->List			= $list;
		$settingUI->Size			= $size;
		return $settingUI;
	}
	
	
	
	/**
	 * queryToObj
	 * convert loose parameters to an attribute object
	 *
	 * @param string $id Query id.
	 * @param string $name
	 * @param string $query
	 * @param string $interface
	 * @param string $recordid
	 * @param string $recordfamily
	 * @return AdsQuery
	 */
	public static function queryToObj($id, $name, $query, $interface, $recordid, $recordfamily)
	{
		$ret = new AdsQuery();
		$ret->ID				= $id;
		$ret->DatasourceID		= "";				//BZ#535
		$ret->Name				= $name;
		$ret->Query				= $query;
		$ret->Interface			= $interface;
		$ret->RecordID			= $recordid;		// the id-field of the record
		$ret->RecordFamily		= $recordfamily;	// the family-field of the result
		return $ret;
	}
	

	public static function queryRecToObj( $input, $interface )
	{
		$query = new DatQuery();
		$query->ID 						= $input['id'];
		$query->Name 					= $input['name'];
		$query->Params 					= $interface;
		$query->RecordFamily			= $input["recordfamily"];
		return $query;
	}
	
	
	public static function queryObjToObj( $input, $interface )
	{
		$query = new DatQuery();
		$query->ID						= $input->ID;
		$query->Name					= $input->Name;
		$query->Params					= $interface;
		$query->RecordFamily			= $input->RecordFamily;
		return $query;
	}
	
	
	public static function adminQueryToObj( $input )
	{
		$query = new AdsQuery();
		$query->ID						= $input['id'];
		$query->Name					= $input['name'];
		$query->Query					= $input['query'];		
		$query->Interface				= $input['interface'];
		$query->Comment					= $input['comment'];
		$query->RecordID				= $input['recordid'];
		$query->RecordFamily			= $input["recordfamily"];
		return $query;
	}
	
	
	public static function interfaceToObj( $name=null, $displayname=null, $type=null, $defaultvalue=null, $valuelist=null, $minvalue=null, $maxvalue=null, $maxlenght=null )
	{
		$interface = new DatProperty();
		$interface->Name				= $name;
		$interface->DisplayName			= $displayname;
		$interface->Type				= $type;
		$interface->DefaultValue		= $defaultvalue;
		$interface->ValueList			= $valuelist;
		$interface->MinValue			= $minvalue;
		$interface->MaxValue			= $maxvalue;
		$interface->MaxLength			= $maxlenght;
		return $interface;
	}
	
	
	/**
	 * fieldToObj
	 * convert loose parameters to a field object
	 * 
	 * NOTE: ListValue is an Array of ListItems (see: listItemToObj)
	 *
	 * @param string $name Field Name
	 * @param string $type Field type (StrValue, ListValue, IntValue).
	 * @param string $value Field Value
	 * @param Attribute[] $attributes
	 * @param string $updatetype
	 * @param string $updateresponse
	 * @param bool $readonly
	 * @param bool $priority
	 * @return DatRecordField
	 */
	public static function fieldToObj( $name, $type, $value, $attributes, $updatetype='none', $updateresponse='none',
	                                   $readonly=false,$priority=false)
	{
		$field = new DatRecordField();
		$field->UpdateType			= $updatetype;
		$field->UpdateResponse		= $updateresponse;
		$field->ReadOnly			= $readonly;
		$field->Priority			= $priority;
		$field->Name				= $name;
		$field->$type				= $value;
		$field->Attributes			= $attributes;
		return $field;
	}
	

	public static function queryfieldToObj( $input )
	{		
		$field = new AdsQueryField();
		$field->Name					= $input['name'];
		$field->ID						= $input['id'];
		$field->Priority				= $input['priority'];
		$field->ReadOnly				= $input['readonly'];
		return $field;
	}
	

	/**
	 * listItemObj
	 * convert loose parameters to a listitem object
	 *
	 * @param Item Name				 $name
	 * @param Item Value			 $value
	 * @param Item Attributes		 $attributes
	 * @return DatList
	 */
	public static function listItemToObj( $name, $value, $attributes )
	{
		$listitem = new DatList();
		$listitem->Name				= $name;
		$listitem->Value			= $value;
		$listitem->Attributes		= $attributes;
		return $listitem;
	}
	
	
	/**
	 * imageListItemObj
	 * convert loose parameters to an imageListitem object
	 *
	 * @param Item Name				 $name
	 * @param Item Value			 $value
	 * @param Item Attributes		 $attributes
	 * @return DatList
	 */
	public static function imageListItemToObj( $name, $value, $attributes )
	{
		$imagelistitem = new DatList();
		$imagelistitem->Name				= $name;
		$imagelistitem->Value				= $value;
		$imagelistitem->Attributes			= $attributes;
		return $imagelistitem;
	}
	

	/**
	 * attributeToObj
	 * convert loose parameters to an attribute object
	 *
	 * @param Attribute Name 		$name
	 * @param Attribute Value		$value
	 * @return DatAttribute
	 */
	public static function attributeToObj( $name, $value )
	{
		$attribute = new DatAttribute();
		$attribute->Name			= $name;
		$attribute->Value			= $value;
		return $attribute;
	}
	
	
	/**
     * Converts an integer to a boolean. When 1 (one) given, true is returned, else false.
     *
     * @param integer $int The integer to convert to boolean.
     * @param boolean $toString Whether or not to convert the boolean to string.
     * @return string|boolean Converted value.
     */
    public static function intToBoolean( $int, $toString=false )
	{
		if($int == 1)
		{
			if( $toString == true )
			{
				return "true";
			}else{
				return true;
			}
		}else{
			if( $toString == true )
			{
				return "false";
			}else{
				return false;
			}
		}
	}
	
	/**
	 * queryInterface
	 * 
	 * Copied from the SCE code. This function generates a Property Object
	 * from database output (query interface).
	 *
	 * @param string $input
	 * @return array List of Property Objects
	 */
	public static function queryInterface( $input )
	{
		$interface = array();
		$pars = explode("\n",trim($input));
		foreach ($pars as $par) {
			if ($par) {
				$desc = explode(",", $par);
				if (!isset($desc[1])) $desc[1] = '';
				if (!isset($desc[2])) $desc[2] = '';
				$list = array();
				if (isset($desc[3])) {
					$list = explode("/", trim($desc[3]));
				}
				if (!isset($desc[4])) $desc[4] = '';
				if (!isset($desc[5])) $desc[5] = '';
				if (!isset($desc[6])) $desc[6] = '';
				
				$interface[] = self::interfaceToObj(	trim($desc[0]), 
														trim($desc[0]), 
														trim($desc[1]), 
														trim($desc[2]), 
														$list, 
														trim($desc[4]), 
														trim($desc[5]), 
														trim($desc[6]));
			}
		}
		return $interface;
	}
	
	
	/**
	 * parseSQL
	 * 
	 * Copied from the SCE code. This function inserts arguments into a query to
	 * generate a valid sql query that is executable.
	 *
	 * @param string $user
	 * @param string $query
	 * @param string $interface
	 * @param array $args
	 * @param null|array $metadata
	 * @throws BizException
	 * @return string
	 */
	public static function parseSQL( $user, $query, $interface, $args, $metadata=null )
	{
		$dbDriver = DBDriverFactory::gen();
		$user = $dbDriver->toDBString($user);
		
		$sql = $query;
		$interface = self::queryInterface($interface);
		
		$sql = self::injectMetaData($sql, $metadata);
		
		if ($interface) foreach ($interface as $par) {
			$found = false;
			if ($args) foreach ($args as $arg) {
				if ($arg->Property == $par->Name) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', $par->Name );
			}

			/** @noinspection PhpUndefinedVariableInspection */
			// Can assume $arg will always be defined here, otherwise BizException would have been thrown earlier on.
			$arg = strtr($arg->Value, ";'", "  ");		// anti-hack
			
			$sql = str_replace("%$" . 	$par->Name . "%" , "%" . $arg. "%",  $sql);
    		$sql = str_replace("%$" . 	$par->Name       , "%" . $arg,  		$sql);
    		$sql = str_replace("$"  . 	$par->Name . "%" , $arg. "%",  		$sql);			
			$sql = str_replace('$'	.	$par->Name		 , $arg, 								$sql);
		}
		$sql = str_replace('$user', $user, $sql);		// default var: current user
		
		return $sql;
	}
	
	
	public static function parseParametersToArray( $user, $query, $interface, $args, $metadata=null )
	{
		$dbDriver = DBDriverFactory::gen();
		$user = $dbDriver->toDBString($user);
		
		$parameters = array("user"=>$user);

		$interface = self::queryInterface($interface);
		
		$parameters = array_merge( $parameters,self::parseMetaDataToArray($query,$metadata) );
				
		if ($interface) foreach ($interface as $par) {
			$found = false;
			if ($args) foreach ($args as $arg) {
				if ($arg->Property == $par->Name) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', $par->Name );
			}

			/** @noinspection PhpUndefinedVariableInspection */
			// Can assume $arg will always be defined here, otherwise BizException would have been thrown earlier on.
			$arg = strtr($arg->Value, ";'", "  ");		// anti-hack
			$parameters[$par->Name] = $arg;
		}
		
		return $parameters;
	}

	/**
	 * Adds in metadata passed in ($metadata) into sql string ($sql).
	 *
	 * @param string $sql
	 * @param array $metadata
	 * @return string
	 */
	public static function injectMetaData( $sql, $metadata )
   	{
   		foreach( $metadata as $key => $value ) {
   			if( is_array( $value ) ) { // ExtraMetaData values
   				$sql = str_replace($key, $value[0], $sql); // supports single value only
   			} else {
   				$sql = str_replace($key, $value, $sql);
   			}
   		}
   		return $sql;
   	}
   	
   	
	public static function parseMetaDataToArray( $sql, $metadata )
   	{
   		$parameters = array();
   		foreach( $metadata as $key => $value )
   		{
   			if( is_array( $value ) ) { // ExtraMetaData values
	   			$sql_new = str_replace($key, $value[0], $sql); // supports single value only
   			} else {
	   			$sql_new = str_replace($key, $value, $sql);
   			}
   			if( $sql != $sql_new )
   			{
   				$parameters[$key] = $value;
   				$sql = $sql_new;
   			}
   		}
   		return $parameters;
   	}
   	
   	
	/**
	 * Determine the type of a value
	 * 
	 * A value can either be an int, a list or a string
	 * String is default
	 * 
	 * @todo write a good function to determine if an array
	 * is indeed a valid list. 
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return string
	 */
	public static function getValueType( $key, $value )
	{
		if( $key == 'images' ) {
			$return = 'ImageListValue';
		} else {
			$return = 'StrValue';
			if( is_numeric($value) ) {
				$return = 'IntValue';
			}
			if( is_array($value) ) {
				$return = 'ListValue';
			}
		}
		return $return;
	}
}
