<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

class PlnRenditionTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'none', 'preview', 'output' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class PlnPublishPrioTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'MustHave', 'ShouldHave', 'NiceToHave' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class PlnAttachmentValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Rendition' ) ) {
			$validator->enterPath( 'Rendition' );
			$validator->checkNull( $datObj->Rendition );
			if( !is_null( $datObj->Rendition ) ) {
				$validator->checkType( $datObj->Rendition, 'string' );
				PlnRenditionTypeValidator::validate( $validator, $datObj->Rendition );
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
		if( $validator->checkExist( $datObj, 'Content' ) ) {
			$validator->enterPath( 'Content' );
			if( !is_null( $datObj->Content ) ) {
				$validator->checkType( $datObj->Content, 'SOAP_Attachment' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FilePath' ) ) {
			$validator->enterPath( 'FilePath' );
			if( !is_null( $datObj->FilePath ) ) {
				$validator->checkType( $datObj->FilePath, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FileUrl' ) ) {
			$validator->enterPath( 'FileUrl' );
			if( !is_null( $datObj->FileUrl ) ) {
				$validator->checkType( $datObj->FileUrl, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class PlnEditionValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
	}
}

class PlnPageValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'PageOrder' ) ) {
			$validator->enterPath( 'PageOrder' );
			$validator->checkNull( $datObj->PageOrder );
			if( !is_null( $datObj->PageOrder ) ) {
				$validator->checkType( $datObj->PageOrder, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Width' ) ) {
			$validator->enterPath( 'Width' );
			if( !is_null( $datObj->Width ) ) {
				$validator->checkType( $datObj->Width, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Height' ) ) {
			$validator->enterPath( 'Height' );
			if( !is_null( $datObj->Height ) ) {
				$validator->checkType( $datObj->Height, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Files' ) ) {
			$validator->enterPath( 'Files' );
			if( !is_null( $datObj->Files ) ) {
				$validator->checkType( $datObj->Files, 'array' );
				if( !empty($datObj->Files) ) foreach( $datObj->Files as $listItem ) {
					$validator->enterPath( 'Attachment' );
					$validator->checkType( $listItem, 'Attachment' );
					PlnAttachmentValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Edition' ) ) {
			$validator->enterPath( 'Edition' );
			if( !is_null( $datObj->Edition ) ) {
				$validator->checkType( $datObj->Edition, 'Edition' );
				PlnEditionValidator::validate( $validator, $datObj->Edition );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Master' ) ) {
			$validator->enterPath( 'Master' );
			if( !is_null( $datObj->Master ) ) {
				$validator->checkType( $datObj->Master, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageSequence' ) ) {
			$validator->enterPath( 'PageSequence' );
			if( !is_null( $datObj->PageSequence ) ) {
				$validator->checkType( $datObj->PageSequence, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageNumber' ) ) {
			$validator->enterPath( 'PageNumber' );
			if( !is_null( $datObj->PageNumber ) ) {
				$validator->checkType( $datObj->PageNumber, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class PlnLayoutValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
		if( $validator->checkExist( $datObj, 'Publication' ) ) {
			$validator->enterPath( 'Publication' );
			if( !is_null( $datObj->Publication ) ) {
				$validator->checkType( $datObj->Publication, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Issue' ) ) {
			$validator->enterPath( 'Issue' );
			if( !is_null( $datObj->Issue ) ) {
				$validator->checkType( $datObj->Issue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PubChannel' ) ) {
			$validator->enterPath( 'PubChannel' );
			if( !is_null( $datObj->PubChannel ) ) {
				$validator->checkType( $datObj->PubChannel, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Section' ) ) {
			$validator->enterPath( 'Section' );
			if( !is_null( $datObj->Section ) ) {
				$validator->checkType( $datObj->Section, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Status' ) ) {
			$validator->enterPath( 'Status' );
			if( !is_null( $datObj->Status ) ) {
				$validator->checkType( $datObj->Status, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Pages' ) ) {
			$validator->enterPath( 'Pages' );
			if( !is_null( $datObj->Pages ) ) {
				$validator->checkType( $datObj->Pages, 'array' );
				if( !empty($datObj->Pages) ) foreach( $datObj->Pages as $listItem ) {
					$validator->enterPath( 'Page' );
					$validator->checkType( $listItem, 'PlnPage' );
					PlnPageValidator::validate( $validator, $listItem );
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
					$validator->enterPath( 'Edition' );
					$validator->checkType( $listItem, 'Edition' );
					PlnEditionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Deadline' ) ) {
			$validator->enterPath( 'Deadline' );
			if( !is_null( $datObj->Deadline ) ) {
				$validator->checkType( $datObj->Deadline, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Version' ) ) {
			$validator->enterPath( 'Version' );
			if( !is_null( $datObj->Version ) ) {
				$validator->checkType( $datObj->Version, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class PlnLayoutFromTemplateValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'NewLayout' ) ) {
			$validator->enterPath( 'NewLayout' );
			$validator->checkNull( $datObj->NewLayout );
			if( !is_null( $datObj->NewLayout ) ) {
				$validator->checkType( $datObj->NewLayout, 'PlnLayout' );
				PlnLayoutValidator::validate( $validator, $datObj->NewLayout );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Template' ) ) {
			$validator->enterPath( 'Template' );
			$validator->checkNull( $datObj->Template );
			if( !is_null( $datObj->Template ) ) {
				$validator->checkType( $datObj->Template, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class PlnPlacementValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Left' ) ) {
			$validator->enterPath( 'Left' );
			$validator->checkNull( $datObj->Left );
			if( !is_null( $datObj->Left ) ) {
				$validator->checkType( $datObj->Left, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Top' ) ) {
			$validator->enterPath( 'Top' );
			$validator->checkNull( $datObj->Top );
			if( !is_null( $datObj->Top ) ) {
				$validator->checkType( $datObj->Top, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Columns' ) ) {
			$validator->enterPath( 'Columns' );
			if( !is_null( $datObj->Columns ) ) {
				$validator->checkType( $datObj->Columns, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Width' ) ) {
			$validator->enterPath( 'Width' );
			if( !is_null( $datObj->Width ) ) {
				$validator->checkType( $datObj->Width, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Height' ) ) {
			$validator->enterPath( 'Height' );
			if( !is_null( $datObj->Height ) ) {
				$validator->checkType( $datObj->Height, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Fixed' ) ) {
			$validator->enterPath( 'Fixed' );
			if( !is_null( $datObj->Fixed ) ) {
				$validator->checkType( $datObj->Fixed, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Layer' ) ) {
			$validator->enterPath( 'Layer' );
			if( !is_null( $datObj->Layer ) ) {
				$validator->checkType( $datObj->Layer, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ContentDx' ) ) {
			$validator->enterPath( 'ContentDx' );
			if( !is_null( $datObj->ContentDx ) ) {
				$validator->checkType( $datObj->ContentDx, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ContentDy' ) ) {
			$validator->enterPath( 'ContentDy' );
			if( !is_null( $datObj->ContentDy ) ) {
				$validator->checkType( $datObj->ContentDy, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ScaleX' ) ) {
			$validator->enterPath( 'ScaleX' );
			if( !is_null( $datObj->ScaleX ) ) {
				$validator->checkType( $datObj->ScaleX, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ScaleY' ) ) {
			$validator->enterPath( 'ScaleY' );
			if( !is_null( $datObj->ScaleY ) ) {
				$validator->checkType( $datObj->ScaleY, 'float' );
			}
			$validator->leavePath();
		}
	}
}

class PlnAdvertValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AlienId' ) ) {
			$validator->enterPath( 'AlienId' );
			if( !is_null( $datObj->AlienId ) ) {
				$validator->checkType( $datObj->AlienId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Publication' ) ) {
			$validator->enterPath( 'Publication' );
			if( !is_null( $datObj->Publication ) ) {
				$validator->checkType( $datObj->Publication, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Issue' ) ) {
			$validator->enterPath( 'Issue' );
			if( !is_null( $datObj->Issue ) ) {
				$validator->checkType( $datObj->Issue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PubChannel' ) ) {
			$validator->enterPath( 'PubChannel' );
			if( !is_null( $datObj->PubChannel ) ) {
				$validator->checkType( $datObj->PubChannel, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Section' ) ) {
			$validator->enterPath( 'Section' );
			if( !is_null( $datObj->Section ) ) {
				$validator->checkType( $datObj->Section, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Status' ) ) {
			$validator->enterPath( 'Status' );
			if( !is_null( $datObj->Status ) ) {
				$validator->checkType( $datObj->Status, 'string' );
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
		if( $validator->checkExist( $datObj, 'AdType' ) ) {
			$validator->enterPath( 'AdType' );
			if( !is_null( $datObj->AdType ) ) {
				$validator->checkType( $datObj->AdType, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Comment' ) ) {
			$validator->enterPath( 'Comment' );
			if( !is_null( $datObj->Comment ) ) {
				$validator->checkType( $datObj->Comment, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Source' ) ) {
			$validator->enterPath( 'Source' );
			if( !is_null( $datObj->Source ) ) {
				$validator->checkType( $datObj->Source, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ColorSpace' ) ) {
			$validator->enterPath( 'ColorSpace' );
			if( !is_null( $datObj->ColorSpace ) ) {
				$validator->checkType( $datObj->ColorSpace, 'string' );
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
		if( $validator->checkExist( $datObj, 'PlainContent' ) ) {
			$validator->enterPath( 'PlainContent' );
			if( !is_null( $datObj->PlainContent ) ) {
				$validator->checkType( $datObj->PlainContent, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'File' ) ) {
			$validator->enterPath( 'File' );
			if( !is_null( $datObj->File ) ) {
				$validator->checkType( $datObj->File, 'Attachment' );
				PlnAttachmentValidator::validate( $validator, $datObj->File );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'HighResFile' ) ) {
			$validator->enterPath( 'HighResFile' );
			if( !is_null( $datObj->HighResFile ) ) {
				$validator->checkType( $datObj->HighResFile, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageOrder' ) ) {
			$validator->enterPath( 'PageOrder' );
			if( !is_null( $datObj->PageOrder ) ) {
				$validator->checkType( $datObj->PageOrder, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Page' ) ) {
			$validator->enterPath( 'Page' );
			if( !is_null( $datObj->Page ) ) {
				$validator->checkType( $datObj->Page, 'PlnPage' );
				PlnPageValidator::validate( $validator, $datObj->Page );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Placement' ) ) {
			$validator->enterPath( 'Placement' );
			if( !is_null( $datObj->Placement ) ) {
				$validator->checkType( $datObj->Placement, 'PlnPlacement' );
				PlnPlacementValidator::validate( $validator, $datObj->Placement );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PreferredPlacement' ) ) {
			$validator->enterPath( 'PreferredPlacement' );
			if( !is_null( $datObj->PreferredPlacement ) ) {
				$validator->checkType( $datObj->PreferredPlacement, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishPrio' ) ) {
			$validator->enterPath( 'PublishPrio' );
			if( !is_null( $datObj->PublishPrio ) ) {
				$validator->checkType( $datObj->PublishPrio, 'string' );
				PlnPublishPrioTypeValidator::validate( $validator, $datObj->PublishPrio );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Rate' ) ) {
			$validator->enterPath( 'Rate' );
			if( !is_null( $datObj->Rate ) ) {
				$validator->checkType( $datObj->Rate, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Editions' ) ) {
			$validator->enterPath( 'Editions' );
			if( !is_null( $datObj->Editions ) ) {
				$validator->checkType( $datObj->Editions, 'array' );
				if( !empty($datObj->Editions) ) foreach( $datObj->Editions as $listItem ) {
					$validator->enterPath( 'Edition' );
					$validator->checkType( $listItem, 'Edition' );
					PlnEditionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Deadline' ) ) {
			$validator->enterPath( 'Deadline' );
			if( !is_null( $datObj->Deadline ) ) {
				$validator->checkType( $datObj->Deadline, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageSequence' ) ) {
			$validator->enterPath( 'PageSequence' );
			if( !is_null( $datObj->PageSequence ) ) {
				$validator->checkType( $datObj->PageSequence, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Version' ) ) {
			$validator->enterPath( 'Version' );
			if( !is_null( $datObj->Version ) ) {
				$validator->checkType( $datObj->Version, 'string' );
			}
			$validator->leavePath();
		}
	}
}

