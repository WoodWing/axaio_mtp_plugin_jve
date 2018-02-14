<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class PubObjectTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( '', 'Article', 'ArticleTemplate', 'Layout', 'LayoutTemplate', 'Image', 'Advert', 'AdvertTemplate', 'Plan', 'Audio', 'Video', 'Library', 'Dossier', 'DossierTemplate', 'LayoutModule', 'LayoutModuleTemplate', 'Task', 'Hyperlink', 'Spreadsheet', 'Other', 'PublishForm', 'PublishFormTemplate' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class PubPropertyTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'string', 'multistring', 'multiline', 'bool', 'int', 'double', 'date', 'datetime', 'list', 'multilist', 'fileselector', 'file', 'articlecomponentselector', 'articlecomponent' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class PubObjectInfoValidator
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
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				PubObjectTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
	}
}

class PubUserMessageValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Severity' ) ) {
			$validator->enterPath( 'Severity' );
			$validator->checkNull( $datObj->Severity );
			if( !is_null( $datObj->Severity ) ) {
				$validator->checkType( $datObj->Severity, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageID' ) ) {
			$validator->enterPath( 'MessageID' );
			$validator->checkNull( $datObj->MessageID );
			if( !is_null( $datObj->MessageID ) ) {
				$validator->checkType( $datObj->MessageID, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Message' ) ) {
			$validator->enterPath( 'Message' );
			$validator->checkNull( $datObj->Message );
			if( !is_null( $datObj->Message ) ) {
				$validator->checkType( $datObj->Message, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Reason' ) ) {
			$validator->enterPath( 'Reason' );
			$validator->checkNull( $datObj->Reason );
			if( !is_null( $datObj->Reason ) ) {
				$validator->checkType( $datObj->Reason, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class PubPageInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'PageNumber' ) ) {
			$validator->enterPath( 'PageNumber' );
			$validator->checkNull( $datObj->PageNumber );
			if( !is_null( $datObj->PageNumber ) ) {
				$validator->checkType( $datObj->PageNumber, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageSequence' ) ) {
			$validator->enterPath( 'PageSequence' );
			$validator->checkNull( $datObj->PageSequence );
			if( !is_null( $datObj->PageSequence ) ) {
				$validator->checkType( $datObj->PageSequence, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageOrder' ) ) {
			$validator->enterPath( 'PageOrder' );
			$validator->checkNull( $datObj->PageOrder );
			if( !is_null( $datObj->PageOrder ) ) {
				$validator->checkType( $datObj->PageOrder, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class PubMessageContextValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Objects' ) ) {
			$validator->enterPath( 'Objects' );
			$validator->checkNull( $datObj->Objects );
			if( !is_null( $datObj->Objects ) ) {
				$validator->checkType( $datObj->Objects, 'array' );
				if( !empty($datObj->Objects) ) foreach( $datObj->Objects as $listItem ) {
					$validator->enterPath( 'ObjectInfo' );
					$validator->checkType( $listItem, 'PubObjectInfo' );
					PubObjectInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Page' ) ) {
			$validator->enterPath( 'Page' );
			if( !is_null( $datObj->Page ) ) {
				$validator->checkType( $datObj->Page, 'PubPageInfo' );
				PubPageInfoValidator::validate( $validator, $datObj->Page );
			}
			$validator->leavePath();
		}
	}
}

class PubReportMessageValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'UserMessage' ) ) {
			$validator->enterPath( 'UserMessage' );
			$validator->checkNull( $datObj->UserMessage );
			if( !is_null( $datObj->UserMessage ) ) {
				$validator->checkType( $datObj->UserMessage, 'PubUserMessage' );
				PubUserMessageValidator::validate( $validator, $datObj->UserMessage );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Context' ) ) {
			$validator->enterPath( 'Context' );
			$validator->checkNull( $datObj->Context );
			if( !is_null( $datObj->Context ) ) {
				$validator->checkType( $datObj->Context, 'PubMessageContext' );
				PubMessageContextValidator::validate( $validator, $datObj->Context );
			}
			$validator->leavePath();
		}
	}
}

class PubPublishedDossierValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'DossierID' ) ) {
			$validator->enterPath( 'DossierID' );
			$validator->checkNull( $datObj->DossierID );
			if( !is_null( $datObj->DossierID ) ) {
				$validator->checkType( $datObj->DossierID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Target' ) ) {
			$validator->enterPath( 'Target' );
			$validator->checkNull( $datObj->Target );
			if( !is_null( $datObj->Target ) ) {
				$validator->checkType( $datObj->Target, 'PubPublishTarget' );
				PubPublishTargetValidator::validate( $validator, $datObj->Target );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedDate' ) ) {
			$validator->enterPath( 'PublishedDate' );
			if( !is_null( $datObj->PublishedDate ) ) {
				$validator->checkType( $datObj->PublishedDate, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishMessage' ) ) {
			$validator->enterPath( 'PublishMessage' );
			if( !is_null( $datObj->PublishMessage ) ) {
				$validator->checkType( $datObj->PublishMessage, 'PubUserMessage' );
				PubUserMessageValidator::validate( $validator, $datObj->PublishMessage );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Online' ) ) {
			$validator->enterPath( 'Online' );
			if( !is_null( $datObj->Online ) ) {
				$validator->checkType( $datObj->Online, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'URL' ) ) {
			$validator->enterPath( 'URL' );
			if( !is_null( $datObj->URL ) ) {
				$validator->checkType( $datObj->URL, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Fields' ) ) {
			$validator->enterPath( 'Fields' );
			if( !is_null( $datObj->Fields ) ) {
				$validator->checkType( $datObj->Fields, 'array' );
				if( !empty($datObj->Fields) ) foreach( $datObj->Fields as $listItem ) {
					$validator->enterPath( 'Field' );
					$validator->checkType( $listItem, 'PubField' );
					PubFieldValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'History' ) ) {
			$validator->enterPath( 'History' );
			if( !is_null( $datObj->History ) ) {
				$validator->checkType( $datObj->History, 'array' );
				if( !empty($datObj->History) ) foreach( $datObj->History as $listItem ) {
					$validator->enterPath( 'PublishHistory' );
					$validator->checkType( $listItem, 'PubPublishHistory' );
					PubPublishHistoryValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class PubPublishedIssueValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Target' ) ) {
			$validator->enterPath( 'Target' );
			$validator->checkNull( $datObj->Target );
			if( !is_null( $datObj->Target ) ) {
				$validator->checkType( $datObj->Target, 'PubPublishTarget' );
				PubPublishTargetValidator::validate( $validator, $datObj->Target );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Version' ) ) {
			$validator->enterPath( 'Version' );
			$validator->checkNull( $datObj->Version );
			if( !is_null( $datObj->Version ) ) {
				$validator->checkType( $datObj->Version, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Fields' ) ) {
			$validator->enterPath( 'Fields' );
			if( !is_null( $datObj->Fields ) ) {
				$validator->checkType( $datObj->Fields, 'array' );
				if( !empty($datObj->Fields) ) foreach( $datObj->Fields as $listItem ) {
					$validator->enterPath( 'Field' );
					$validator->checkType( $listItem, 'PubField' );
					PubFieldValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Report' ) ) {
			$validator->enterPath( 'Report' );
			if( !is_null( $datObj->Report ) ) {
				$validator->checkType( $datObj->Report, 'array' );
				if( !empty($datObj->Report) ) foreach( $datObj->Report as $listItem ) {
					$validator->enterPath( 'ReportMessage' );
					$validator->checkType( $listItem, 'PubReportMessage' );
					PubReportMessageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedDate' ) ) {
			$validator->enterPath( 'PublishedDate' );
			if( !is_null( $datObj->PublishedDate ) ) {
				$validator->checkType( $datObj->PublishedDate, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DossierOrder' ) ) {
			$validator->enterPath( 'DossierOrder' );
			if( !is_null( $datObj->DossierOrder ) ) {
				$validator->checkType( $datObj->DossierOrder, 'array' );
				if( !empty($datObj->DossierOrder) ) foreach( $datObj->DossierOrder as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class PubFieldValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Key' ) ) {
			$validator->enterPath( 'Key' );
			$validator->checkNull( $datObj->Key );
			if( !is_null( $datObj->Key ) ) {
				$validator->checkType( $datObj->Key, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				PubPropertyTypeValidator::validate( $validator, $datObj->Type );
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

class PubPublishTargetValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'PubChannelID' ) ) {
			$validator->enterPath( 'PubChannelID' );
			if( !is_null( $datObj->PubChannelID ) ) {
				$validator->checkType( $datObj->PubChannelID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IssueID' ) ) {
			$validator->enterPath( 'IssueID' );
			if( !is_null( $datObj->IssueID ) ) {
				$validator->checkType( $datObj->IssueID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EditionID' ) ) {
			$validator->enterPath( 'EditionID' );
			if( !is_null( $datObj->EditionID ) ) {
				$validator->checkType( $datObj->EditionID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedDate' ) ) {
			$validator->enterPath( 'PublishedDate' );
			if( !is_null( $datObj->PublishedDate ) ) {
				$validator->checkType( $datObj->PublishedDate, 'dateTime' );
			}
			$validator->leavePath();
		}
	}
}

class PubPublishHistoryValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'PublishedDate' ) ) {
			$validator->enterPath( 'PublishedDate' );
			if( !is_null( $datObj->PublishedDate ) ) {
				$validator->checkType( $datObj->PublishedDate, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SendDate' ) ) {
			$validator->enterPath( 'SendDate' );
			$validator->checkNull( $datObj->SendDate );
			if( !is_null( $datObj->SendDate ) ) {
				$validator->checkType( $datObj->SendDate, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedBy' ) ) {
			$validator->enterPath( 'PublishedBy' );
			$validator->checkNull( $datObj->PublishedBy );
			if( !is_null( $datObj->PublishedBy ) ) {
				$validator->checkType( $datObj->PublishedBy, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedObjects' ) ) {
			$validator->enterPath( 'PublishedObjects' );
			$validator->checkNull( $datObj->PublishedObjects );
			if( !is_null( $datObj->PublishedObjects ) ) {
				$validator->checkType( $datObj->PublishedObjects, 'array' );
				if( !empty($datObj->PublishedObjects) ) foreach( $datObj->PublishedObjects as $listItem ) {
					$validator->enterPath( 'PublishedObject' );
					$validator->checkType( $listItem, 'PubPublishedObject' );
					PubPublishedObjectValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class PubPublishedObjectValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ObjectId' ) ) {
			$validator->enterPath( 'ObjectId' );
			$validator->checkNull( $datObj->ObjectId );
			if( !is_null( $datObj->ObjectId ) ) {
				$validator->checkType( $datObj->ObjectId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Version' ) ) {
			$validator->enterPath( 'Version' );
			$validator->checkNull( $datObj->Version );
			if( !is_null( $datObj->Version ) ) {
				$validator->checkType( $datObj->Version, 'string' );
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
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Format' ) ) {
			$validator->enterPath( 'Format' );
			$validator->checkNull( $datObj->Format );
			if( !is_null( $datObj->Format ) ) {
				$validator->checkType( $datObj->Format, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class PubProgressPhaseValidator
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
		if( $validator->checkExist( $datObj, 'Label' ) ) {
			$validator->enterPath( 'Label' );
			$validator->checkNull( $datObj->Label );
			if( !is_null( $datObj->Label ) ) {
				$validator->checkType( $datObj->Label, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Maximum' ) ) {
			$validator->enterPath( 'Maximum' );
			$validator->checkNull( $datObj->Maximum );
			if( !is_null( $datObj->Maximum ) ) {
				$validator->checkType( $datObj->Maximum, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Progress' ) ) {
			$validator->enterPath( 'Progress' );
			$validator->checkNull( $datObj->Progress );
			if( !is_null( $datObj->Progress ) ) {
				$validator->checkType( $datObj->Progress, 'integer' );
			}
			$validator->leavePath();
		}
	}
}

