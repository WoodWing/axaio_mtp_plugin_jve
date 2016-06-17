<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

class AdmObjectTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'Article', 'ArticleTemplate', 'Layout', 'LayoutTemplate', 'Image', 'Advert', 'AdvertTemplate', 'Plan', 'Audio', 'Video', 'Library', 'Dossier', 'DossierTemplate', 'LayoutModule', 'LayoutModuleTemplate', 'Task', 'Hyperlink', 'Presentation', 'Archive', 'Spreadsheet', 'Other', 'PublishForm', 'PublishFormTemplate' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class AdmPubChannelTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'print', 'web', 'sms', 'dps', 'dps2', 'other' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class AdmModeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'GetPublications', 'GetPubChannels', 'GetIssues', 'GetEditions', 'GetSections', 'GetStatuses', 'GetUsers', 'GetUserGroups' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class AdmStatusPhaseValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'Selection', 'Production', 'Completed', 'Archived' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class AdmIdNameValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
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

class AdmUserValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FullName' ) ) {
			$validator->enterPath( 'FullName' );
			if( !is_null( $datObj->FullName ) ) {
				$validator->checkType( $datObj->FullName, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Deactivated' ) ) {
			$validator->enterPath( 'Deactivated' );
			if( !is_null( $datObj->Deactivated ) ) {
				$validator->checkType( $datObj->Deactivated, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Password' ) ) {
			$validator->enterPath( 'Password' );
			if( !is_null( $datObj->Password ) ) {
				$validator->checkType( $datObj->Password, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FixedPassword' ) ) {
			$validator->enterPath( 'FixedPassword' );
			if( !is_null( $datObj->FixedPassword ) ) {
				$validator->checkType( $datObj->FixedPassword, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EmailAddress' ) ) {
			$validator->enterPath( 'EmailAddress' );
			if( !is_null( $datObj->EmailAddress ) ) {
				$validator->checkType( $datObj->EmailAddress, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EmailUser' ) ) {
			$validator->enterPath( 'EmailUser' );
			if( !is_null( $datObj->EmailUser ) ) {
				$validator->checkType( $datObj->EmailUser, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EmailGroup' ) ) {
			$validator->enterPath( 'EmailGroup' );
			if( !is_null( $datObj->EmailGroup ) ) {
				$validator->checkType( $datObj->EmailGroup, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PasswordExpired' ) ) {
			$validator->enterPath( 'PasswordExpired' );
			if( !is_null( $datObj->PasswordExpired ) ) {
				$validator->checkType( $datObj->PasswordExpired, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ValidFrom' ) ) {
			$validator->enterPath( 'ValidFrom' );
			if( !is_null( $datObj->ValidFrom ) ) {
				$validator->checkType( $datObj->ValidFrom, 'dateTimeOrEmpty' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ValidTill' ) ) {
			$validator->enterPath( 'ValidTill' );
			if( !is_null( $datObj->ValidTill ) ) {
				$validator->checkType( $datObj->ValidTill, 'dateTimeOrEmpty' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Language' ) ) {
			$validator->enterPath( 'Language' );
			if( !is_null( $datObj->Language ) ) {
				$validator->checkType( $datObj->Language, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TrackChangesColor' ) ) {
			$validator->enterPath( 'TrackChangesColor' );
			if( !is_null( $datObj->TrackChangesColor ) ) {
				$validator->checkType( $datObj->TrackChangesColor, 'Color' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Organization' ) ) {
			$validator->enterPath( 'Organization' );
			if( !is_null( $datObj->Organization ) ) {
				$validator->checkType( $datObj->Organization, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Location' ) ) {
			$validator->enterPath( 'Location' );
			if( !is_null( $datObj->Location ) ) {
				$validator->checkType( $datObj->Location, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EncryptedPassword' ) ) {
			$validator->enterPath( 'EncryptedPassword' );
			if( !is_null( $datObj->EncryptedPassword ) ) {
				$validator->checkType( $datObj->EncryptedPassword, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserGroups' ) ) {
			$validator->enterPath( 'UserGroups' );
			if( !is_null( $datObj->UserGroups ) ) {
				$validator->checkType( $datObj->UserGroups, 'array' );
				if( !empty($datObj->UserGroups) ) foreach( $datObj->UserGroups as $listItem ) {
					$validator->enterPath( 'UserGroup' );
					$validator->checkType( $listItem, 'AdmUserGroup' );
					AdmUserGroupValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ImportOnLogon' ) ) {
			$validator->enterPath( 'ImportOnLogon' );
			if( !is_null( $datObj->ImportOnLogon ) ) {
				$validator->checkType( $datObj->ImportOnLogon, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class AdmUserGroupValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Admin' ) ) {
			$validator->enterPath( 'Admin' );
			if( !is_null( $datObj->Admin ) ) {
				$validator->checkType( $datObj->Admin, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Routing' ) ) {
			$validator->enterPath( 'Routing' );
			if( !is_null( $datObj->Routing ) ) {
				$validator->checkType( $datObj->Routing, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ExternalId' ) ) {
			$validator->enterPath( 'ExternalId' );
			if( !is_null( $datObj->ExternalId ) ) {
				$validator->checkType( $datObj->ExternalId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Users' ) ) {
			$validator->enterPath( 'Users' );
			if( !is_null( $datObj->Users ) ) {
				$validator->checkType( $datObj->Users, 'array' );
				if( !empty($datObj->Users) ) foreach( $datObj->Users as $listItem ) {
					$validator->enterPath( 'User' );
					$validator->checkType( $listItem, 'AdmUser' );
					AdmUserValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class AdmPublicationValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SortOrder' ) ) {
			$validator->enterPath( 'SortOrder' );
			if( !is_null( $datObj->SortOrder ) ) {
				$validator->checkType( $datObj->SortOrder, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EmailNotify' ) ) {
			$validator->enterPath( 'EmailNotify' );
			if( !is_null( $datObj->EmailNotify ) ) {
				$validator->checkType( $datObj->EmailNotify, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReversedRead' ) ) {
			$validator->enterPath( 'ReversedRead' );
			if( !is_null( $datObj->ReversedRead ) ) {
				$validator->checkType( $datObj->ReversedRead, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AutoPurge' ) ) {
			$validator->enterPath( 'AutoPurge' );
			if( !is_null( $datObj->AutoPurge ) ) {
				$validator->checkType( $datObj->AutoPurge, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DefaultChannelId' ) ) {
			$validator->enterPath( 'DefaultChannelId' );
			if( !is_null( $datObj->DefaultChannelId ) ) {
				$validator->checkType( $datObj->DefaultChannelId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ExtraMetaData' ) ) {
			$validator->enterPath( 'ExtraMetaData' );
			if( !is_null( $datObj->ExtraMetaData ) ) {
				$validator->checkType( $datObj->ExtraMetaData, 'array' );
				if( !empty($datObj->ExtraMetaData) ) foreach( $datObj->ExtraMetaData as $listItem ) {
					$validator->enterPath( 'ExtraMetaData' );
					$validator->checkType( $listItem, 'AdmExtraMetaData' );
					AdmExtraMetaDataValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PubChannels' ) ) {
			$validator->enterPath( 'PubChannels' );
			if( !is_null( $datObj->PubChannels ) ) {
				$validator->checkType( $datObj->PubChannels, 'array' );
				if( !empty($datObj->PubChannels) ) foreach( $datObj->PubChannels as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Issues' ) ) {
			$validator->enterPath( 'Issues' );
			if( !is_null( $datObj->Issues ) ) {
				$validator->checkType( $datObj->Issues, 'array' );
				if( !empty($datObj->Issues) ) foreach( $datObj->Issues as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Editions' ) ) {
			$validator->enterPath( 'Editions' );
			if( !is_null( $datObj->Editions ) ) {
				$validator->checkType( $datObj->Editions, 'array' );
				if( !empty($datObj->Editions) ) foreach( $datObj->Editions as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Sections' ) ) {
			$validator->enterPath( 'Sections' );
			if( !is_null( $datObj->Sections ) ) {
				$validator->checkType( $datObj->Sections, 'array' );
				if( !empty($datObj->Sections) ) foreach( $datObj->Sections as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Statuses' ) ) {
			$validator->enterPath( 'Statuses' );
			if( !is_null( $datObj->Statuses ) ) {
				$validator->checkType( $datObj->Statuses, 'array' );
				if( !empty($datObj->Statuses) ) foreach( $datObj->Statuses as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserGroups' ) ) {
			$validator->enterPath( 'UserGroups' );
			if( !is_null( $datObj->UserGroups ) ) {
				$validator->checkType( $datObj->UserGroups, 'array' );
				if( !empty($datObj->UserGroups) ) foreach( $datObj->UserGroups as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AdminGroups' ) ) {
			$validator->enterPath( 'AdminGroups' );
			if( !is_null( $datObj->AdminGroups ) ) {
				$validator->checkType( $datObj->AdminGroups, 'array' );
				if( !empty($datObj->AdminGroups) ) foreach( $datObj->AdminGroups as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Workflows' ) ) {
			$validator->enterPath( 'Workflows' );
			if( !is_null( $datObj->Workflows ) ) {
				$validator->checkType( $datObj->Workflows, 'array' );
				if( !empty($datObj->Workflows) ) foreach( $datObj->Workflows as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Routings' ) ) {
			$validator->enterPath( 'Routings' );
			if( !is_null( $datObj->Routings ) ) {
				$validator->checkType( $datObj->Routings, 'array' );
				if( !empty($datObj->Routings) ) foreach( $datObj->Routings as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CalculateDeadlines' ) ) {
			$validator->enterPath( 'CalculateDeadlines' );
			if( !is_null( $datObj->CalculateDeadlines ) ) {
				$validator->checkType( $datObj->CalculateDeadlines, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class AdmPubChannelValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				AdmPubChannelTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishSystem' ) ) {
			$validator->enterPath( 'PublishSystem' );
			if( !is_null( $datObj->PublishSystem ) ) {
				$validator->checkType( $datObj->PublishSystem, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishSystemId' ) ) {
			$validator->enterPath( 'PublishSystemId' );
			if( !is_null( $datObj->PublishSystemId ) ) {
				$validator->checkType( $datObj->PublishSystemId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CurrentIssueId' ) ) {
			$validator->enterPath( 'CurrentIssueId' );
			if( !is_null( $datObj->CurrentIssueId ) ) {
				$validator->checkType( $datObj->CurrentIssueId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SuggestionProvider' ) ) {
			$validator->enterPath( 'SuggestionProvider' );
			if( !is_null( $datObj->SuggestionProvider ) ) {
				$validator->checkType( $datObj->SuggestionProvider, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ExtraMetaData' ) ) {
			$validator->enterPath( 'ExtraMetaData' );
			if( !is_null( $datObj->ExtraMetaData ) ) {
				$validator->checkType( $datObj->ExtraMetaData, 'array' );
				if( !empty($datObj->ExtraMetaData) ) foreach( $datObj->ExtraMetaData as $listItem ) {
					$validator->enterPath( 'ExtraMetaData' );
					$validator->checkType( $listItem, 'AdmExtraMetaData' );
					AdmExtraMetaDataValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DirectPublish' ) ) {
			$validator->enterPath( 'DirectPublish' );
			if( !is_null( $datObj->DirectPublish ) ) {
				$validator->checkType( $datObj->DirectPublish, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SupportsForms' ) ) {
			$validator->enterPath( 'SupportsForms' );
			if( !is_null( $datObj->SupportsForms ) ) {
				$validator->checkType( $datObj->SupportsForms, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Issues' ) ) {
			$validator->enterPath( 'Issues' );
			if( !is_null( $datObj->Issues ) ) {
				$validator->checkType( $datObj->Issues, 'array' );
				if( !empty($datObj->Issues) ) foreach( $datObj->Issues as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Editions' ) ) {
			$validator->enterPath( 'Editions' );
			if( !is_null( $datObj->Editions ) ) {
				$validator->checkType( $datObj->Editions, 'array' );
				if( !empty($datObj->Editions) ) foreach( $datObj->Editions as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class AdmIssueValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SortOrder' ) ) {
			$validator->enterPath( 'SortOrder' );
			if( !is_null( $datObj->SortOrder ) ) {
				$validator->checkType( $datObj->SortOrder, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EmailNotify' ) ) {
			$validator->enterPath( 'EmailNotify' );
			if( !is_null( $datObj->EmailNotify ) ) {
				$validator->checkType( $datObj->EmailNotify, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReversedRead' ) ) {
			$validator->enterPath( 'ReversedRead' );
			if( !is_null( $datObj->ReversedRead ) ) {
				$validator->checkType( $datObj->ReversedRead, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'OverrulePublication' ) ) {
			$validator->enterPath( 'OverrulePublication' );
			if( !is_null( $datObj->OverrulePublication ) ) {
				$validator->checkType( $datObj->OverrulePublication, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Deadline' ) ) {
			$validator->enterPath( 'Deadline' );
			if( !is_null( $datObj->Deadline ) ) {
				$validator->checkType( $datObj->Deadline, 'dateTimeOrEmpty' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ExpectedPages' ) ) {
			$validator->enterPath( 'ExpectedPages' );
			if( !is_null( $datObj->ExpectedPages ) ) {
				$validator->checkType( $datObj->ExpectedPages, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Subject' ) ) {
			$validator->enterPath( 'Subject' );
			if( !is_null( $datObj->Subject ) ) {
				$validator->checkType( $datObj->Subject, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Activated' ) ) {
			$validator->enterPath( 'Activated' );
			if( !is_null( $datObj->Activated ) ) {
				$validator->checkType( $datObj->Activated, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublicationDate' ) ) {
			$validator->enterPath( 'PublicationDate' );
			if( !is_null( $datObj->PublicationDate ) ) {
				$validator->checkType( $datObj->PublicationDate, 'dateTimeOrEmpty' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ExtraMetaData' ) ) {
			$validator->enterPath( 'ExtraMetaData' );
			if( !is_null( $datObj->ExtraMetaData ) ) {
				$validator->checkType( $datObj->ExtraMetaData, 'array' );
				if( !empty($datObj->ExtraMetaData) ) foreach( $datObj->ExtraMetaData as $listItem ) {
					$validator->enterPath( 'ExtraMetaData' );
					$validator->checkType( $listItem, 'AdmExtraMetaData' );
					AdmExtraMetaDataValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Editions' ) ) {
			$validator->enterPath( 'Editions' );
			if( !is_null( $datObj->Editions ) ) {
				$validator->checkType( $datObj->Editions, 'array' );
				if( !empty($datObj->Editions) ) foreach( $datObj->Editions as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Sections' ) ) {
			$validator->enterPath( 'Sections' );
			if( !is_null( $datObj->Sections ) ) {
				$validator->checkType( $datObj->Sections, 'array' );
				if( !empty($datObj->Sections) ) foreach( $datObj->Sections as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Statuses' ) ) {
			$validator->enterPath( 'Statuses' );
			if( !is_null( $datObj->Statuses ) ) {
				$validator->checkType( $datObj->Statuses, 'array' );
				if( !empty($datObj->Statuses) ) foreach( $datObj->Statuses as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserGroups' ) ) {
			$validator->enterPath( 'UserGroups' );
			if( !is_null( $datObj->UserGroups ) ) {
				$validator->checkType( $datObj->UserGroups, 'array' );
				if( !empty($datObj->UserGroups) ) foreach( $datObj->UserGroups as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Workflows' ) ) {
			$validator->enterPath( 'Workflows' );
			if( !is_null( $datObj->Workflows ) ) {
				$validator->checkType( $datObj->Workflows, 'array' );
				if( !empty($datObj->Workflows) ) foreach( $datObj->Workflows as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Routings' ) ) {
			$validator->enterPath( 'Routings' );
			if( !is_null( $datObj->Routings ) ) {
				$validator->checkType( $datObj->Routings, 'array' );
				if( !empty($datObj->Routings) ) foreach( $datObj->Routings as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CalculateDeadlines' ) ) {
			$validator->enterPath( 'CalculateDeadlines' );
			if( !is_null( $datObj->CalculateDeadlines ) ) {
				$validator->checkType( $datObj->CalculateDeadlines, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class AdmExtraMetaDataValidator
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
		if( $validator->checkExist( $datObj, 'Values' ) ) {
			$validator->enterPath( 'Values' );
			$validator->checkNull( $datObj->Values );
			if( !is_null( $datObj->Values ) ) {
				$validator->checkType( $datObj->Values, 'array' );
				if( !empty($datObj->Values) ) foreach( $datObj->Values as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class AdmEditionValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SortOrder' ) ) {
			$validator->enterPath( 'SortOrder' );
			if( !is_null( $datObj->SortOrder ) ) {
				$validator->checkType( $datObj->SortOrder, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DeadlineRelative' ) ) {
			$validator->enterPath( 'DeadlineRelative' );
			if( !is_null( $datObj->DeadlineRelative ) ) {
				$validator->checkType( $datObj->DeadlineRelative, 'integer' );
			}
			$validator->leavePath();
		}
	}
}

class AdmSectionValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SortOrder' ) ) {
			$validator->enterPath( 'SortOrder' );
			if( !is_null( $datObj->SortOrder ) ) {
				$validator->checkType( $datObj->SortOrder, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Deadline' ) ) {
			$validator->enterPath( 'Deadline' );
			if( !is_null( $datObj->Deadline ) ) {
				$validator->checkType( $datObj->Deadline, 'dateTimeOrEmpty' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ExpectedPages' ) ) {
			$validator->enterPath( 'ExpectedPages' );
			if( !is_null( $datObj->ExpectedPages ) ) {
				$validator->checkType( $datObj->ExpectedPages, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Statuses' ) ) {
			$validator->enterPath( 'Statuses' );
			if( !is_null( $datObj->Statuses ) ) {
				$validator->checkType( $datObj->Statuses, 'array' );
				if( !empty($datObj->Statuses) ) foreach( $datObj->Statuses as $listItem ) {
					$validator->enterPath( 'IdName' );
					$validator->checkType( $listItem, 'AdmIdName' );
					AdmIdNameValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class AdmStatusValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SortOrder' ) ) {
			$validator->enterPath( 'SortOrder' );
			if( !is_null( $datObj->SortOrder ) ) {
				$validator->checkType( $datObj->SortOrder, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				AdmObjectTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Produce' ) ) {
			$validator->enterPath( 'Produce' );
			if( !is_null( $datObj->Produce ) ) {
				$validator->checkType( $datObj->Produce, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Color' ) ) {
			$validator->enterPath( 'Color' );
			if( !is_null( $datObj->Color ) ) {
				$validator->checkType( $datObj->Color, 'Color' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DefaultRouteTo' ) ) {
			$validator->enterPath( 'DefaultRouteTo' );
			if( !is_null( $datObj->DefaultRouteTo ) ) {
				$validator->checkType( $datObj->DefaultRouteTo, 'AdmIdName' );
				AdmIdNameValidator::validate( $validator, $datObj->DefaultRouteTo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CreatePermanentVersion' ) ) {
			$validator->enterPath( 'CreatePermanentVersion' );
			if( !is_null( $datObj->CreatePermanentVersion ) ) {
				$validator->checkType( $datObj->CreatePermanentVersion, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RemoveIntermediateVersions' ) ) {
			$validator->enterPath( 'RemoveIntermediateVersions' );
			if( !is_null( $datObj->RemoveIntermediateVersions ) ) {
				$validator->checkType( $datObj->RemoveIntermediateVersions, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AutomaticallySendToNext' ) ) {
			$validator->enterPath( 'AutomaticallySendToNext' );
			if( !is_null( $datObj->AutomaticallySendToNext ) ) {
				$validator->checkType( $datObj->AutomaticallySendToNext, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReadyForPublishing' ) ) {
			$validator->enterPath( 'ReadyForPublishing' );
			if( !is_null( $datObj->ReadyForPublishing ) ) {
				$validator->checkType( $datObj->ReadyForPublishing, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Phase' ) ) {
			$validator->enterPath( 'Phase' );
			if( !is_null( $datObj->Phase ) ) {
				$validator->checkType( $datObj->Phase, 'string' );
				AdmStatusPhaseValidator::validate( $validator, $datObj->Phase );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SkipIdsa' ) ) {
			$validator->enterPath( 'SkipIdsa' );
			if( !is_null( $datObj->SkipIdsa ) ) {
				$validator->checkType( $datObj->SkipIdsa, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class AdmTermEntityValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AutocompleteProvider' ) ) {
			$validator->enterPath( 'AutocompleteProvider' );
			if( !is_null( $datObj->AutocompleteProvider ) ) {
				$validator->checkType( $datObj->AutocompleteProvider, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishSystemId' ) ) {
			$validator->enterPath( 'PublishSystemId' );
			if( !is_null( $datObj->PublishSystemId ) ) {
				$validator->checkType( $datObj->PublishSystemId, 'string' );
			}
			$validator->leavePath();
		}
	}
}

