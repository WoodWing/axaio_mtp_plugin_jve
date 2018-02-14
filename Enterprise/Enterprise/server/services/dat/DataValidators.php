<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class DatOperationTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( '<', '>', '<=', '>=', '=', '!=', 'contains', 'starts', 'ends', 'within' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class DatUpdateTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'none', 'new', 'deleted', 'changed' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class DatResponseTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'none', 'accepted', 'rejected', 'ignored' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class DatPropertyTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'string', 'multistring', 'multiline', 'bool', 'int', 'double', 'date', 'datetime', 'list', 'multilist', 'fileselector', 'file', 'articlecomponentselector', 'articlecomponent' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class DatQueryParamValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Property' ) ) {
			$validator->enterPath( 'Property' );
			$validator->checkNull( $datObj->Property );
			if( !is_null( $datObj->Property ) ) {
				$validator->checkType( $datObj->Property, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Operation' ) ) {
			$validator->enterPath( 'Operation' );
			$validator->checkNull( $datObj->Operation );
			if( !is_null( $datObj->Operation ) ) {
				$validator->checkType( $datObj->Operation, 'string' );
				DatOperationTypeValidator::validate( $validator, $datObj->Operation );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Value' ) ) {
			$validator->enterPath( 'Value' );
			$validator->checkNull( $datObj->Value );
			if( !is_null( $datObj->Value ) ) {
				$validator->checkType( $datObj->Value, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class DatAttributeValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			$validator->checkNull( $datObj->Name );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Value' ) ) {
			$validator->enterPath( 'Value' );
			$validator->checkNull( $datObj->Value );
			if( !is_null( $datObj->Value ) ) {
				$validator->checkType( $datObj->Value, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class DatListValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			$validator->checkNull( $datObj->Name );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Value' ) ) {
			$validator->enterPath( 'Value' );
			$validator->checkNull( $datObj->Value );
			if( !is_null( $datObj->Value ) ) {
				$validator->checkType( $datObj->Value, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Attributes' ) ) {
			$validator->enterPath( 'Attributes' );
			$validator->checkNull( $datObj->Attributes );
			if( !is_null( $datObj->Attributes ) ) {
				$validator->checkType( $datObj->Attributes, 'array' );
				if( !empty($datObj->Attributes) ) foreach( $datObj->Attributes as $listItem ) {
					$validator->enterPath( 'Attribute' );
					$validator->checkType( $listItem, 'DatAttribute' );
					DatAttributeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class DatRecordFieldValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'UpdateType' ) ) {
			$validator->enterPath( 'UpdateType' );
			$validator->checkNull( $datObj->UpdateType );
			if( !is_null( $datObj->UpdateType ) ) {
				$validator->checkType( $datObj->UpdateType, 'string' );
				DatUpdateTypeValidator::validate( $validator, $datObj->UpdateType );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UpdateResponse' ) ) {
			$validator->enterPath( 'UpdateResponse' );
			$validator->checkNull( $datObj->UpdateResponse );
			if( !is_null( $datObj->UpdateResponse ) ) {
				$validator->checkType( $datObj->UpdateResponse, 'string' );
				DatResponseTypeValidator::validate( $validator, $datObj->UpdateResponse );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReadOnly' ) ) {
			$validator->enterPath( 'ReadOnly' );
			$validator->checkNull( $datObj->ReadOnly );
			if( !is_null( $datObj->ReadOnly ) ) {
				$validator->checkType( $datObj->ReadOnly, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Priority' ) ) {
			$validator->enterPath( 'Priority' );
			$validator->checkNull( $datObj->Priority );
			if( !is_null( $datObj->Priority ) ) {
				$validator->checkType( $datObj->Priority, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			$validator->checkNull( $datObj->Name );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Attributes' ) ) {
			$validator->enterPath( 'Attributes' );
			$validator->checkNull( $datObj->Attributes );
			if( !is_null( $datObj->Attributes ) ) {
				$validator->checkType( $datObj->Attributes, 'array' );
				if( !empty($datObj->Attributes) ) foreach( $datObj->Attributes as $listItem ) {
					$validator->enterPath( 'Attribute' );
					$validator->checkType( $listItem, 'DatAttribute' );
					DatAttributeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class DatRecordValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			$validator->checkNull( $datObj->ID );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UpdateType' ) ) {
			$validator->enterPath( 'UpdateType' );
			$validator->checkNull( $datObj->UpdateType );
			if( !is_null( $datObj->UpdateType ) ) {
				$validator->checkType( $datObj->UpdateType, 'string' );
				DatUpdateTypeValidator::validate( $validator, $datObj->UpdateType );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UpdateResponse' ) ) {
			$validator->enterPath( 'UpdateResponse' );
			$validator->checkNull( $datObj->UpdateResponse );
			if( !is_null( $datObj->UpdateResponse ) ) {
				$validator->checkType( $datObj->UpdateResponse, 'string' );
				DatResponseTypeValidator::validate( $validator, $datObj->UpdateResponse );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Hidden' ) ) {
			$validator->enterPath( 'Hidden' );
			$validator->checkNull( $datObj->Hidden );
			if( !is_null( $datObj->Hidden ) ) {
				$validator->checkType( $datObj->Hidden, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Fields' ) ) {
			$validator->enterPath( 'Fields' );
			$validator->checkNull( $datObj->Fields );
			if( !is_null( $datObj->Fields ) ) {
				$validator->checkType( $datObj->Fields, 'array' );
				if( !empty($datObj->Fields) ) foreach( $datObj->Fields as $listItem ) {
					$validator->enterPath( 'RecordField' );
					$validator->checkType( $listItem, 'DatRecordField' );
					DatRecordFieldValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class DatPlacedQueryValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'QueryID' ) ) {
			$validator->enterPath( 'QueryID' );
			$validator->checkNull( $datObj->QueryID );
			if( !is_null( $datObj->QueryID ) ) {
				$validator->checkType( $datObj->QueryID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FamilyValues' ) ) {
			$validator->enterPath( 'FamilyValues' );
			$validator->checkNull( $datObj->FamilyValues );
			if( !is_null( $datObj->FamilyValues ) ) {
				$validator->checkType( $datObj->FamilyValues, 'array' );
				if( !empty($datObj->FamilyValues) ) foreach( $datObj->FamilyValues as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class DatPlacementValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ObjectID' ) ) {
			$validator->enterPath( 'ObjectID' );
			$validator->checkNull( $datObj->ObjectID );
			if( !is_null( $datObj->ObjectID ) ) {
				$validator->checkType( $datObj->ObjectID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PlacedQueries' ) ) {
			$validator->enterPath( 'PlacedQueries' );
			$validator->checkNull( $datObj->PlacedQueries );
			if( !is_null( $datObj->PlacedQueries ) ) {
				$validator->checkType( $datObj->PlacedQueries, 'array' );
				if( !empty($datObj->PlacedQueries) ) foreach( $datObj->PlacedQueries as $listItem ) {
					$validator->enterPath( 'PlacedQuery' );
					$validator->checkType( $listItem, 'DatPlacedQuery' );
					DatPlacedQueryValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class DatDatasourceInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			$validator->checkNull( $datObj->ID );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			$validator->checkNull( $datObj->Name );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class DatQueryValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			$validator->checkNull( $datObj->ID );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			$validator->checkNull( $datObj->Name );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Params' ) ) {
			$validator->enterPath( 'Params' );
			$validator->checkNull( $datObj->Params );
			if( !is_null( $datObj->Params ) ) {
				$validator->checkType( $datObj->Params, 'array' );
				if( !empty($datObj->Params) ) foreach( $datObj->Params as $listItem ) {
					$validator->enterPath( 'Property' );
					$validator->checkType( $listItem, 'DatProperty' );
					DatPropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RecordFamily' ) ) {
			$validator->enterPath( 'RecordFamily' );
			$validator->checkNull( $datObj->RecordFamily );
			if( !is_null( $datObj->RecordFamily ) ) {
				$validator->checkType( $datObj->RecordFamily, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class DatPropertyValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			$validator->checkNull( $datObj->Name );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DisplayName' ) ) {
			$validator->enterPath( 'DisplayName' );
			$validator->checkNull( $datObj->DisplayName );
			if( !is_null( $datObj->DisplayName ) ) {
				$validator->checkType( $datObj->DisplayName, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				DatPropertyTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DefaultValue' ) ) {
			$validator->enterPath( 'DefaultValue' );
			if( !is_null( $datObj->DefaultValue ) ) {
				$validator->checkType( $datObj->DefaultValue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ValueList' ) ) {
			$validator->enterPath( 'ValueList' );
			if( !is_null( $datObj->ValueList ) ) {
				$validator->checkType( $datObj->ValueList, 'array' );
				if( !empty($datObj->ValueList) ) foreach( $datObj->ValueList as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MinValue' ) ) {
			$validator->enterPath( 'MinValue' );
			if( !is_null( $datObj->MinValue ) ) {
				$validator->checkType( $datObj->MinValue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MaxValue' ) ) {
			$validator->enterPath( 'MaxValue' );
			if( !is_null( $datObj->MaxValue ) ) {
				$validator->checkType( $datObj->MaxValue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MaxLength' ) ) {
			$validator->enterPath( 'MaxLength' );
			if( !is_null( $datObj->MaxLength ) ) {
				$validator->checkType( $datObj->MaxLength, 'integer' );
			}
			$validator->leavePath();
		}
	}
}

