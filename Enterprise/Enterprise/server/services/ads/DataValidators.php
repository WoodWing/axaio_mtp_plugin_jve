<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsPublicationValidator
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
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			$validator->checkNull( $datObj->Description );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class AdsSettingValidator
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

class AdsQueryValidator
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
		if( $validator->checkExist( $datObj, 'Query' ) ) {
			$validator->enterPath( 'Query' );
			$validator->checkNull( $datObj->Query );
			if( !is_null( $datObj->Query ) ) {
				$validator->checkType( $datObj->Query, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Interface' ) ) {
			$validator->enterPath( 'Interface' );
			$validator->checkNull( $datObj->Interface );
			if( !is_null( $datObj->Interface ) ) {
				$validator->checkType( $datObj->Interface, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Comment' ) ) {
			$validator->enterPath( 'Comment' );
			$validator->checkNull( $datObj->Comment );
			if( !is_null( $datObj->Comment ) ) {
				$validator->checkType( $datObj->Comment, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RecordID' ) ) {
			$validator->enterPath( 'RecordID' );
			$validator->checkNull( $datObj->RecordID );
			if( !is_null( $datObj->RecordID ) ) {
				$validator->checkType( $datObj->RecordID, 'string' );
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

class AdsQueryFieldValidator
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
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			$validator->checkNull( $datObj->ID );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Priority' ) ) {
			$validator->enterPath( 'Priority' );
			$validator->checkNull( $datObj->Priority );
			if( !is_null( $datObj->Priority ) ) {
				$validator->checkType( $datObj->Priority, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReadOnly' ) ) {
			$validator->enterPath( 'ReadOnly' );
			$validator->checkNull( $datObj->ReadOnly );
			if( !is_null( $datObj->ReadOnly ) ) {
				$validator->checkType( $datObj->ReadOnly, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class AdsDatasourceTypeValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class AdsSettingsDetailValidator
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
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			$validator->checkNull( $datObj->Description );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'List' ) ) {
			$validator->enterPath( 'List' );
			if( !is_null( $datObj->List ) ) {
				$validator->checkType( $datObj->List, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class AdsDatasourceInfoValidator
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
		if( $validator->checkExist( $datObj, 'Bidirectional' ) ) {
			$validator->enterPath( 'Bidirectional' );
			$validator->checkNull( $datObj->Bidirectional );
			if( !is_null( $datObj->Bidirectional ) ) {
				$validator->checkType( $datObj->Bidirectional, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
			}
			$validator->leavePath();
		}
	}
}

