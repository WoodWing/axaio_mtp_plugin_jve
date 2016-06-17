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

class WflObjectTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( '', 'Article', 'ArticleTemplate', 'Layout', 'LayoutTemplate', 'Image', 'Advert', 'AdvertTemplate', 'Plan', 'Audio', 'Video', 'Library', 'Dossier', 'DossierTemplate', 'LayoutModule', 'LayoutModuleTemplate', 'Task', 'Hyperlink', 'Presentation', 'Archive', 'Spreadsheet', 'Other', 'PublishForm', 'PublishFormTemplate' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflPropertyTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'string', 'multistring', 'multiline', 'bool', 'int', 'double', 'date', 'datetime', 'list', 'multilist', 'fileselector', 'file', 'articlecomponentselector', 'articlecomponent', 'button' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflOperationTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( '<', '>', '<=', '>=', '=', '!=', 'contains', 'starts', 'ends', 'within', 'between' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflPubChannelTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'print', 'web', 'sms', 'dps', 'dps2', 'other' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflActionValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( '', 'Create', 'CheckIn', 'SendTo', 'CopyTo', 'SetProperties', 'Query', 'Preview', 'PublishDossier', 'UpdateDossier', 'UnPublishDossier', 'SetPublishProperties' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflMessageTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'system', 'client', 'user', 'sticky', 'reply' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflMessageLevelValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( '', 'Info', 'Warning', 'Error' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflMessageStatusValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'None', 'Accepted', 'Cancelled', 'Completed', 'Rejected' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflRenditionTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'none', 'thumb', 'preview', 'placement', 'native', 'output', 'trailer' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflRelationTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'Placed', 'Planned', 'Candidate', 'Contained', 'Related', 'InstanceOf' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflOnlineStatusTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'TakeOffline', 'TakeOnline' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflInstanceTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'Production', 'Planning' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflPreviewTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'page', 'spread' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflAreaTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( 'Workflow', 'Trash' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflFrameTypeValidator
{
	static public function validate( $validator, $enumVal )
	{
		static $enums = array( '', 'unassigned', 'text', 'graphic' );
		$validator->checkEnum( $enums, $enumVal );
	}
}

class WflFeatureValidator
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
		if( $validator->checkExist( $datObj, 'Value' ) ) {
			$validator->enterPath( 'Value' );
			if( !is_null( $datObj->Value ) ) {
				$validator->checkType( $datObj->Value, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflBasicMetaDataValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DocumentID' ) ) {
			$validator->enterPath( 'DocumentID' );
			if( !is_null( $datObj->DocumentID ) ) {
				$validator->checkType( $datObj->DocumentID, 'string' );
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
				WflObjectTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Publication' ) ) {
			$validator->enterPath( 'Publication' );
			if( !is_null( $datObj->Publication ) ) {
				$validator->checkType( $datObj->Publication, 'Publication' );
				WflPublicationValidator::validate( $validator, $datObj->Publication );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Category' ) ) {
			$validator->enterPath( 'Category' );
			if( !is_null( $datObj->Category ) ) {
				$validator->checkType( $datObj->Category, 'Category' );
				WflCategoryValidator::validate( $validator, $datObj->Category );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ContentSource' ) ) {
			$validator->enterPath( 'ContentSource' );
			if( !is_null( $datObj->ContentSource ) ) {
				$validator->checkType( $datObj->ContentSource, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflRightsMetaDataValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'CopyrightMarked' ) ) {
			$validator->enterPath( 'CopyrightMarked' );
			if( !is_null( $datObj->CopyrightMarked ) ) {
				$validator->checkType( $datObj->CopyrightMarked, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Copyright' ) ) {
			$validator->enterPath( 'Copyright' );
			if( !is_null( $datObj->Copyright ) ) {
				$validator->checkType( $datObj->Copyright, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CopyrightURL' ) ) {
			$validator->enterPath( 'CopyrightURL' );
			if( !is_null( $datObj->CopyrightURL ) ) {
				$validator->checkType( $datObj->CopyrightURL, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflSourceMetaDataValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Credit' ) ) {
			$validator->enterPath( 'Credit' );
			if( !is_null( $datObj->Credit ) ) {
				$validator->checkType( $datObj->Credit, 'string' );
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
		if( $validator->checkExist( $datObj, 'Author' ) ) {
			$validator->enterPath( 'Author' );
			if( !is_null( $datObj->Author ) ) {
				$validator->checkType( $datObj->Author, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflContentMetaDataValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Description' ) ) {
			$validator->enterPath( 'Description' );
			if( !is_null( $datObj->Description ) ) {
				$validator->checkType( $datObj->Description, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DescriptionAuthor' ) ) {
			$validator->enterPath( 'DescriptionAuthor' );
			if( !is_null( $datObj->DescriptionAuthor ) ) {
				$validator->checkType( $datObj->DescriptionAuthor, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Keywords' ) ) {
			$validator->enterPath( 'Keywords' );
			if( !is_null( $datObj->Keywords ) ) {
				$validator->checkType( $datObj->Keywords, 'array' );
				if( !empty($datObj->Keywords) ) foreach( $datObj->Keywords as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Slugline' ) ) {
			$validator->enterPath( 'Slugline' );
			if( !is_null( $datObj->Slugline ) ) {
				$validator->checkType( $datObj->Slugline, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Format' ) ) {
			$validator->enterPath( 'Format' );
			if( !is_null( $datObj->Format ) ) {
				$validator->checkType( $datObj->Format, 'string' );
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
		if( $validator->checkExist( $datObj, 'Dpi' ) ) {
			$validator->enterPath( 'Dpi' );
			if( !is_null( $datObj->Dpi ) ) {
				$validator->checkType( $datObj->Dpi, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LengthWords' ) ) {
			$validator->enterPath( 'LengthWords' );
			if( !is_null( $datObj->LengthWords ) ) {
				$validator->checkType( $datObj->LengthWords, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LengthChars' ) ) {
			$validator->enterPath( 'LengthChars' );
			if( !is_null( $datObj->LengthChars ) ) {
				$validator->checkType( $datObj->LengthChars, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LengthParas' ) ) {
			$validator->enterPath( 'LengthParas' );
			if( !is_null( $datObj->LengthParas ) ) {
				$validator->checkType( $datObj->LengthParas, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LengthLines' ) ) {
			$validator->enterPath( 'LengthLines' );
			if( !is_null( $datObj->LengthLines ) ) {
				$validator->checkType( $datObj->LengthLines, 'unsignedInt' );
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
		if( $validator->checkExist( $datObj, 'FileSize' ) ) {
			$validator->enterPath( 'FileSize' );
			if( !is_null( $datObj->FileSize ) ) {
				$validator->checkType( $datObj->FileSize, 'unsignedInt' );
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
		if( $validator->checkExist( $datObj, 'HighResFile' ) ) {
			$validator->enterPath( 'HighResFile' );
			if( !is_null( $datObj->HighResFile ) ) {
				$validator->checkType( $datObj->HighResFile, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Encoding' ) ) {
			$validator->enterPath( 'Encoding' );
			if( !is_null( $datObj->Encoding ) ) {
				$validator->checkType( $datObj->Encoding, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Compression' ) ) {
			$validator->enterPath( 'Compression' );
			if( !is_null( $datObj->Compression ) ) {
				$validator->checkType( $datObj->Compression, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'KeyFrameEveryFrames' ) ) {
			$validator->enterPath( 'KeyFrameEveryFrames' );
			if( !is_null( $datObj->KeyFrameEveryFrames ) ) {
				$validator->checkType( $datObj->KeyFrameEveryFrames, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Channels' ) ) {
			$validator->enterPath( 'Channels' );
			if( !is_null( $datObj->Channels ) ) {
				$validator->checkType( $datObj->Channels, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AspectRatio' ) ) {
			$validator->enterPath( 'AspectRatio' );
			if( !is_null( $datObj->AspectRatio ) ) {
				$validator->checkType( $datObj->AspectRatio, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflWorkflowMetaDataValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Deadline' ) ) {
			$validator->enterPath( 'Deadline' );
			if( !is_null( $datObj->Deadline ) ) {
				$validator->checkType( $datObj->Deadline, 'dateTimeOrEmpty' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Urgency' ) ) {
			$validator->enterPath( 'Urgency' );
			if( !is_null( $datObj->Urgency ) ) {
				$validator->checkType( $datObj->Urgency, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Modifier' ) ) {
			$validator->enterPath( 'Modifier' );
			if( !is_null( $datObj->Modifier ) ) {
				$validator->checkType( $datObj->Modifier, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Modified' ) ) {
			$validator->enterPath( 'Modified' );
			if( !is_null( $datObj->Modified ) ) {
				$validator->checkType( $datObj->Modified, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Creator' ) ) {
			$validator->enterPath( 'Creator' );
			if( !is_null( $datObj->Creator ) ) {
				$validator->checkType( $datObj->Creator, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Created' ) ) {
			$validator->enterPath( 'Created' );
			if( !is_null( $datObj->Created ) ) {
				$validator->checkType( $datObj->Created, 'dateTime' );
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
		if( $validator->checkExist( $datObj, 'State' ) ) {
			$validator->enterPath( 'State' );
			if( !is_null( $datObj->State ) ) {
				$validator->checkType( $datObj->State, 'State' );
				WflStateValidator::validate( $validator, $datObj->State );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RouteTo' ) ) {
			$validator->enterPath( 'RouteTo' );
			if( !is_null( $datObj->RouteTo ) ) {
				$validator->checkType( $datObj->RouteTo, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LockedBy' ) ) {
			$validator->enterPath( 'LockedBy' );
			if( !is_null( $datObj->LockedBy ) ) {
				$validator->checkType( $datObj->LockedBy, 'string' );
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
		if( $validator->checkExist( $datObj, 'DeadlineSoft' ) ) {
			$validator->enterPath( 'DeadlineSoft' );
			if( !is_null( $datObj->DeadlineSoft ) ) {
				$validator->checkType( $datObj->DeadlineSoft, 'dateTimeOrEmpty' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Rating' ) ) {
			$validator->enterPath( 'Rating' );
			if( !is_null( $datObj->Rating ) ) {
				$validator->checkType( $datObj->Rating, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Deletor' ) ) {
			$validator->enterPath( 'Deletor' );
			if( !is_null( $datObj->Deletor ) ) {
				$validator->checkType( $datObj->Deletor, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Deleted' ) ) {
			$validator->enterPath( 'Deleted' );
			if( !is_null( $datObj->Deleted ) ) {
				$validator->checkType( $datObj->Deleted, 'dateTime' );
			}
			$validator->leavePath();
		}
	}
}

class WflExtraMetaDataValidator
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

class WflPlacementTileValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'PageSequence' ) ) {
			$validator->enterPath( 'PageSequence' );
			$validator->checkNull( $datObj->PageSequence );
			if( !is_null( $datObj->PageSequence ) ) {
				$validator->checkType( $datObj->PageSequence, 'unsignedInt' );
			}
			$validator->leavePath();
		}
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
		if( $validator->checkExist( $datObj, 'Width' ) ) {
			$validator->enterPath( 'Width' );
			$validator->checkNull( $datObj->Width );
			if( !is_null( $datObj->Width ) ) {
				$validator->checkType( $datObj->Width, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Height' ) ) {
			$validator->enterPath( 'Height' );
			$validator->checkNull( $datObj->Height );
			if( !is_null( $datObj->Height ) ) {
				$validator->checkType( $datObj->Height, 'float' );
			}
			$validator->leavePath();
		}
	}
}

class WflPlacementValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Page' ) ) {
			$validator->enterPath( 'Page' );
			if( !is_null( $datObj->Page ) ) {
				$validator->checkType( $datObj->Page, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Element' ) ) {
			$validator->enterPath( 'Element' );
			if( !is_null( $datObj->Element ) ) {
				$validator->checkType( $datObj->Element, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ElementID' ) ) {
			$validator->enterPath( 'ElementID' );
			$validator->checkNull( $datObj->ElementID );
			if( !is_null( $datObj->ElementID ) ) {
				$validator->checkType( $datObj->ElementID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FrameOrder' ) ) {
			$validator->enterPath( 'FrameOrder' );
			$validator->checkNull( $datObj->FrameOrder );
			if( !is_null( $datObj->FrameOrder ) ) {
				$validator->checkType( $datObj->FrameOrder, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FrameID' ) ) {
			$validator->enterPath( 'FrameID' );
			if( !is_null( $datObj->FrameID ) ) {
				$validator->checkType( $datObj->FrameID, 'string' );
			}
			$validator->leavePath();
		}
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
		if( $validator->checkExist( $datObj, 'Width' ) ) {
			$validator->enterPath( 'Width' );
			$validator->checkNull( $datObj->Width );
			if( !is_null( $datObj->Width ) ) {
				$validator->checkType( $datObj->Width, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Height' ) ) {
			$validator->enterPath( 'Height' );
			$validator->checkNull( $datObj->Height );
			if( !is_null( $datObj->Height ) ) {
				$validator->checkType( $datObj->Height, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Overset' ) ) {
			$validator->enterPath( 'Overset' );
			if( !is_null( $datObj->Overset ) ) {
				$validator->checkType( $datObj->Overset, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'OversetChars' ) ) {
			$validator->enterPath( 'OversetChars' );
			if( !is_null( $datObj->OversetChars ) ) {
				$validator->checkType( $datObj->OversetChars, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'OversetLines' ) ) {
			$validator->enterPath( 'OversetLines' );
			if( !is_null( $datObj->OversetLines ) ) {
				$validator->checkType( $datObj->OversetLines, 'integer' );
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
		if( $validator->checkExist( $datObj, 'Content' ) ) {
			$validator->enterPath( 'Content' );
			if( !is_null( $datObj->Content ) ) {
				$validator->checkType( $datObj->Content, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Edition' ) ) {
			$validator->enterPath( 'Edition' );
			if( !is_null( $datObj->Edition ) ) {
				$validator->checkType( $datObj->Edition, 'Edition' );
				WflEditionValidator::validate( $validator, $datObj->Edition );
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
		if( $validator->checkExist( $datObj, 'Tiles' ) ) {
			$validator->enterPath( 'Tiles' );
			if( !is_null( $datObj->Tiles ) ) {
				$validator->checkType( $datObj->Tiles, 'array' );
				if( !empty($datObj->Tiles) ) foreach( $datObj->Tiles as $listItem ) {
					$validator->enterPath( 'PlacementTile' );
					$validator->checkType( $listItem, 'PlacementTile' );
					WflPlacementTileValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FormWidgetId' ) ) {
			$validator->enterPath( 'FormWidgetId' );
			if( !is_null( $datObj->FormWidgetId ) ) {
				$validator->checkType( $datObj->FormWidgetId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'InDesignArticleIds' ) ) {
			$validator->enterPath( 'InDesignArticleIds' );
			if( !is_null( $datObj->InDesignArticleIds ) ) {
				$validator->checkType( $datObj->InDesignArticleIds, 'array' );
				if( !empty($datObj->InDesignArticleIds) ) foreach( $datObj->InDesignArticleIds as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FrameType' ) ) {
			$validator->enterPath( 'FrameType' );
			if( !is_null( $datObj->FrameType ) ) {
				$validator->checkType( $datObj->FrameType, 'string' );
				WflFrameTypeValidator::validate( $validator, $datObj->FrameType );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SplineID' ) ) {
			$validator->enterPath( 'SplineID' );
			if( !is_null( $datObj->SplineID ) ) {
				$validator->checkType( $datObj->SplineID, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflElementValidator
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
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LengthWords' ) ) {
			$validator->enterPath( 'LengthWords' );
			if( !is_null( $datObj->LengthWords ) ) {
				$validator->checkType( $datObj->LengthWords, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LengthChars' ) ) {
			$validator->enterPath( 'LengthChars' );
			if( !is_null( $datObj->LengthChars ) ) {
				$validator->checkType( $datObj->LengthChars, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LengthParas' ) ) {
			$validator->enterPath( 'LengthParas' );
			if( !is_null( $datObj->LengthParas ) ) {
				$validator->checkType( $datObj->LengthParas, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LengthLines' ) ) {
			$validator->enterPath( 'LengthLines' );
			if( !is_null( $datObj->LengthLines ) ) {
				$validator->checkType( $datObj->LengthLines, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Snippet' ) ) {
			$validator->enterPath( 'Snippet' );
			if( !is_null( $datObj->Snippet ) ) {
				$validator->checkType( $datObj->Snippet, 'string' );
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
		if( $validator->checkExist( $datObj, 'Content' ) ) {
			$validator->enterPath( 'Content' );
			if( !is_null( $datObj->Content ) ) {
				$validator->checkType( $datObj->Content, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflInDesignArticleValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
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

class WflRelationValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Parent' ) ) {
			$validator->enterPath( 'Parent' );
			$validator->checkNull( $datObj->Parent );
			if( !is_null( $datObj->Parent ) ) {
				$validator->checkType( $datObj->Parent, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Child' ) ) {
			$validator->enterPath( 'Child' );
			if( !is_null( $datObj->Child ) ) {
				$validator->checkType( $datObj->Child, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				WflRelationTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Placements' ) ) {
			$validator->enterPath( 'Placements' );
			if( !is_null( $datObj->Placements ) ) {
				$validator->checkType( $datObj->Placements, 'array' );
				if( !empty($datObj->Placements) ) foreach( $datObj->Placements as $listItem ) {
					$validator->enterPath( 'Placement' );
					$validator->checkType( $listItem, 'Placement' );
					WflPlacementValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ParentVersion' ) ) {
			$validator->enterPath( 'ParentVersion' );
			if( !is_null( $datObj->ParentVersion ) ) {
				$validator->checkType( $datObj->ParentVersion, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ChildVersion' ) ) {
			$validator->enterPath( 'ChildVersion' );
			if( !is_null( $datObj->ChildVersion ) ) {
				$validator->checkType( $datObj->ChildVersion, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Geometry' ) ) {
			$validator->enterPath( 'Geometry' );
			if( !is_null( $datObj->Geometry ) ) {
				$validator->checkType( $datObj->Geometry, 'Attachment' );
				WflAttachmentValidator::validate( $validator, $datObj->Geometry );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Rating' ) ) {
			$validator->enterPath( 'Rating' );
			if( !is_null( $datObj->Rating ) ) {
				$validator->checkType( $datObj->Rating, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Targets' ) ) {
			$validator->enterPath( 'Targets' );
			if( !is_null( $datObj->Targets ) ) {
				$validator->checkType( $datObj->Targets, 'array' );
				if( !empty($datObj->Targets) ) foreach( $datObj->Targets as $listItem ) {
					$validator->enterPath( 'Target' );
					$validator->checkType( $listItem, 'Target' );
					WflTargetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ParentInfo' ) ) {
			$validator->enterPath( 'ParentInfo' );
			if( !is_null( $datObj->ParentInfo ) ) {
				$validator->checkType( $datObj->ParentInfo, 'ObjectInfo' );
				WflObjectInfoValidator::validate( $validator, $datObj->ParentInfo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ChildInfo' ) ) {
			$validator->enterPath( 'ChildInfo' );
			if( !is_null( $datObj->ChildInfo ) ) {
				$validator->checkType( $datObj->ChildInfo, 'ObjectInfo' );
				WflObjectInfoValidator::validate( $validator, $datObj->ChildInfo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectLabels' ) ) {
			$validator->enterPath( 'ObjectLabels' );
			if( !is_null( $datObj->ObjectLabels ) ) {
				$validator->checkType( $datObj->ObjectLabels, 'array' );
				if( !empty($datObj->ObjectLabels) ) foreach( $datObj->ObjectLabels as $listItem ) {
					$validator->enterPath( 'ObjectLabel' );
					$validator->checkType( $listItem, 'ObjectLabel' );
					WflObjectLabelValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflStickyInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'AnchorX' ) ) {
			$validator->enterPath( 'AnchorX' );
			if( !is_null( $datObj->AnchorX ) ) {
				$validator->checkType( $datObj->AnchorX, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AnchorY' ) ) {
			$validator->enterPath( 'AnchorY' );
			if( !is_null( $datObj->AnchorY ) ) {
				$validator->checkType( $datObj->AnchorY, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Left' ) ) {
			$validator->enterPath( 'Left' );
			if( !is_null( $datObj->Left ) ) {
				$validator->checkType( $datObj->Left, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Top' ) ) {
			$validator->enterPath( 'Top' );
			if( !is_null( $datObj->Top ) ) {
				$validator->checkType( $datObj->Top, 'float' );
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
		if( $validator->checkExist( $datObj, 'Page' ) ) {
			$validator->enterPath( 'Page' );
			if( !is_null( $datObj->Page ) ) {
				$validator->checkType( $datObj->Page, 'unsignedInt' );
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
		if( $validator->checkExist( $datObj, 'Color' ) ) {
			$validator->enterPath( 'Color' );
			if( !is_null( $datObj->Color ) ) {
				$validator->checkType( $datObj->Color, 'Color' );
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
	}
}

class WflSettingValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Setting' ) ) {
			$validator->enterPath( 'Setting' );
			$validator->checkNull( $datObj->Setting );
			if( !is_null( $datObj->Setting ) ) {
				$validator->checkType( $datObj->Setting, 'string' );
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

class WflMessageValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ObjectID' ) ) {
			$validator->enterPath( 'ObjectID' );
			if( !is_null( $datObj->ObjectID ) ) {
				$validator->checkType( $datObj->ObjectID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserID' ) ) {
			$validator->enterPath( 'UserID' );
			if( !is_null( $datObj->UserID ) ) {
				$validator->checkType( $datObj->UserID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageID' ) ) {
			$validator->enterPath( 'MessageID' );
			if( !is_null( $datObj->MessageID ) ) {
				$validator->checkType( $datObj->MessageID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageType' ) ) {
			$validator->enterPath( 'MessageType' );
			$validator->checkNull( $datObj->MessageType );
			if( !is_null( $datObj->MessageType ) ) {
				$validator->checkType( $datObj->MessageType, 'string' );
				WflMessageTypeValidator::validate( $validator, $datObj->MessageType );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageTypeDetail' ) ) {
			$validator->enterPath( 'MessageTypeDetail' );
			$validator->checkNull( $datObj->MessageTypeDetail );
			if( !is_null( $datObj->MessageTypeDetail ) ) {
				$validator->checkType( $datObj->MessageTypeDetail, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Message' ) ) {
			$validator->enterPath( 'Message' );
			if( !is_null( $datObj->Message ) ) {
				$validator->checkType( $datObj->Message, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TimeStamp' ) ) {
			$validator->enterPath( 'TimeStamp' );
			if( !is_null( $datObj->TimeStamp ) ) {
				$validator->checkType( $datObj->TimeStamp, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Expiration' ) ) {
			$validator->enterPath( 'Expiration' );
			if( !is_null( $datObj->Expiration ) ) {
				$validator->checkType( $datObj->Expiration, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageLevel' ) ) {
			$validator->enterPath( 'MessageLevel' );
			if( !is_null( $datObj->MessageLevel ) ) {
				$validator->checkType( $datObj->MessageLevel, 'string' );
				WflMessageLevelValidator::validate( $validator, $datObj->MessageLevel );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FromUser' ) ) {
			$validator->enterPath( 'FromUser' );
			if( !is_null( $datObj->FromUser ) ) {
				$validator->checkType( $datObj->FromUser, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'StickyInfo' ) ) {
			$validator->enterPath( 'StickyInfo' );
			if( !is_null( $datObj->StickyInfo ) ) {
				$validator->checkType( $datObj->StickyInfo, 'StickyInfo' );
				WflStickyInfoValidator::validate( $validator, $datObj->StickyInfo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ThreadMessageID' ) ) {
			$validator->enterPath( 'ThreadMessageID' );
			if( !is_null( $datObj->ThreadMessageID ) ) {
				$validator->checkType( $datObj->ThreadMessageID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReplyToMessageID' ) ) {
			$validator->enterPath( 'ReplyToMessageID' );
			if( !is_null( $datObj->ReplyToMessageID ) ) {
				$validator->checkType( $datObj->ReplyToMessageID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageStatus' ) ) {
			$validator->enterPath( 'MessageStatus' );
			if( !is_null( $datObj->MessageStatus ) ) {
				$validator->checkType( $datObj->MessageStatus, 'string' );
				WflMessageStatusValidator::validate( $validator, $datObj->MessageStatus );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectVersion' ) ) {
			$validator->enterPath( 'ObjectVersion' );
			if( !is_null( $datObj->ObjectVersion ) ) {
				$validator->checkType( $datObj->ObjectVersion, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflMessageListValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Messages' ) ) {
			$validator->enterPath( 'Messages' );
			if( !is_null( $datObj->Messages ) ) {
				$validator->checkType( $datObj->Messages, 'array' );
				if( !empty($datObj->Messages) ) foreach( $datObj->Messages as $listItem ) {
					$validator->enterPath( 'Message' );
					$validator->checkType( $listItem, 'Message' );
					WflMessageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReadMessageIDs' ) ) {
			$validator->enterPath( 'ReadMessageIDs' );
			if( !is_null( $datObj->ReadMessageIDs ) ) {
				$validator->checkType( $datObj->ReadMessageIDs, 'array' );
				if( !empty($datObj->ReadMessageIDs) ) foreach( $datObj->ReadMessageIDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DeleteMessageIDs' ) ) {
			$validator->enterPath( 'DeleteMessageIDs' );
			if( !is_null( $datObj->DeleteMessageIDs ) ) {
				$validator->checkType( $datObj->DeleteMessageIDs, 'array' );
				if( !empty($datObj->DeleteMessageIDs) ) foreach( $datObj->DeleteMessageIDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflTermValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Term' ) ) {
			$validator->enterPath( 'Term' );
			$validator->checkNull( $datObj->Term );
			if( !is_null( $datObj->Term ) ) {
				$validator->checkType( $datObj->Term, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Translation' ) ) {
			$validator->enterPath( 'Translation' );
			$validator->checkNull( $datObj->Translation );
			if( !is_null( $datObj->Translation ) ) {
				$validator->checkType( $datObj->Translation, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflUserValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'UserID' ) ) {
			$validator->enterPath( 'UserID' );
			$validator->checkNull( $datObj->UserID );
			if( !is_null( $datObj->UserID ) ) {
				$validator->checkType( $datObj->UserID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FullName' ) ) {
			$validator->enterPath( 'FullName' );
			$validator->checkNull( $datObj->FullName );
			if( !is_null( $datObj->FullName ) ) {
				$validator->checkType( $datObj->FullName, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TrackChangesColor' ) ) {
			$validator->enterPath( 'TrackChangesColor' );
			$validator->checkNull( $datObj->TrackChangesColor );
			if( !is_null( $datObj->TrackChangesColor ) ) {
				$validator->checkType( $datObj->TrackChangesColor, 'Color' );
			}
			$validator->leavePath();
		}
	}
}

class WflUserGroupValidator
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
	}
}

class WflServerInfoValidator
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
		if( $validator->checkExist( $datObj, 'URL' ) ) {
			$validator->enterPath( 'URL' );
			$validator->checkNull( $datObj->URL );
			if( !is_null( $datObj->URL ) ) {
				$validator->checkType( $datObj->URL, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Developer' ) ) {
			$validator->enterPath( 'Developer' );
			$validator->checkNull( $datObj->Developer );
			if( !is_null( $datObj->Developer ) ) {
				$validator->checkType( $datObj->Developer, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Implementation' ) ) {
			$validator->enterPath( 'Implementation' );
			$validator->checkNull( $datObj->Implementation );
			if( !is_null( $datObj->Implementation ) ) {
				$validator->checkType( $datObj->Implementation, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Technology' ) ) {
			$validator->enterPath( 'Technology' );
			$validator->checkNull( $datObj->Technology );
			if( !is_null( $datObj->Technology ) ) {
				$validator->checkType( $datObj->Technology, 'string' );
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
		if( $validator->checkExist( $datObj, 'FeatureSet' ) ) {
			$validator->enterPath( 'FeatureSet' );
			$validator->checkNull( $datObj->FeatureSet );
			if( !is_null( $datObj->FeatureSet ) ) {
				$validator->checkType( $datObj->FeatureSet, 'array' );
				if( !empty($datObj->FeatureSet) ) foreach( $datObj->FeatureSet as $listItem ) {
					$validator->enterPath( 'Feature' );
					$validator->checkType( $listItem, 'Feature' );
					WflFeatureValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CryptKey' ) ) {
			$validator->enterPath( 'CryptKey' );
			if( !is_null( $datObj->CryptKey ) ) {
				$validator->checkType( $datObj->CryptKey, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EnterpriseSystemId' ) ) {
			$validator->enterPath( 'EnterpriseSystemId' );
			if( !is_null( $datObj->EnterpriseSystemId ) ) {
				$validator->checkType( $datObj->EnterpriseSystemId, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflMetaDataValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'BasicMetaData' ) ) {
			$validator->enterPath( 'BasicMetaData' );
			if( !is_null( $datObj->BasicMetaData ) ) {
				$validator->checkType( $datObj->BasicMetaData, 'BasicMetaData' );
				WflBasicMetaDataValidator::validate( $validator, $datObj->BasicMetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RightsMetaData' ) ) {
			$validator->enterPath( 'RightsMetaData' );
			if( !is_null( $datObj->RightsMetaData ) ) {
				$validator->checkType( $datObj->RightsMetaData, 'RightsMetaData' );
				WflRightsMetaDataValidator::validate( $validator, $datObj->RightsMetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SourceMetaData' ) ) {
			$validator->enterPath( 'SourceMetaData' );
			if( !is_null( $datObj->SourceMetaData ) ) {
				$validator->checkType( $datObj->SourceMetaData, 'SourceMetaData' );
				WflSourceMetaDataValidator::validate( $validator, $datObj->SourceMetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ContentMetaData' ) ) {
			$validator->enterPath( 'ContentMetaData' );
			if( !is_null( $datObj->ContentMetaData ) ) {
				$validator->checkType( $datObj->ContentMetaData, 'ContentMetaData' );
				WflContentMetaDataValidator::validate( $validator, $datObj->ContentMetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'WorkflowMetaData' ) ) {
			$validator->enterPath( 'WorkflowMetaData' );
			if( !is_null( $datObj->WorkflowMetaData ) ) {
				$validator->checkType( $datObj->WorkflowMetaData, 'WorkflowMetaData' );
				WflWorkflowMetaDataValidator::validate( $validator, $datObj->WorkflowMetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ExtraMetaData' ) ) {
			$validator->enterPath( 'ExtraMetaData' );
			if( !is_null( $datObj->ExtraMetaData ) ) {
				$validator->checkType( $datObj->ExtraMetaData, 'array' );
				if( !empty($datObj->ExtraMetaData) ) foreach( $datObj->ExtraMetaData as $listItem ) {
					$validator->enterPath( 'ExtraMetaData' );
					$validator->checkType( $listItem, 'ExtraMetaData' );
					WflExtraMetaDataValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflPageValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Width' ) ) {
			$validator->enterPath( 'Width' );
			$validator->checkNull( $datObj->Width );
			if( !is_null( $datObj->Width ) ) {
				$validator->checkType( $datObj->Width, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Height' ) ) {
			$validator->enterPath( 'Height' );
			$validator->checkNull( $datObj->Height );
			if( !is_null( $datObj->Height ) ) {
				$validator->checkType( $datObj->Height, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageNumber' ) ) {
			$validator->enterPath( 'PageNumber' );
			$validator->checkNull( $datObj->PageNumber );
			if( !is_null( $datObj->PageNumber ) ) {
				$validator->checkType( $datObj->PageNumber, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageOrder' ) ) {
			$validator->enterPath( 'PageOrder' );
			$validator->checkNull( $datObj->PageOrder );
			if( !is_null( $datObj->PageOrder ) ) {
				$validator->checkType( $datObj->PageOrder, 'unsignedInt' );
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
					WflAttachmentValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Edition' ) ) {
			$validator->enterPath( 'Edition' );
			if( !is_null( $datObj->Edition ) ) {
				$validator->checkType( $datObj->Edition, 'Edition' );
				WflEditionValidator::validate( $validator, $datObj->Edition );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Master' ) ) {
			$validator->enterPath( 'Master' );
			$validator->checkNull( $datObj->Master );
			if( !is_null( $datObj->Master ) ) {
				$validator->checkType( $datObj->Master, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Instance' ) ) {
			$validator->enterPath( 'Instance' );
			$validator->checkNull( $datObj->Instance );
			if( !is_null( $datObj->Instance ) ) {
				$validator->checkType( $datObj->Instance, 'string' );
				WflInstanceTypeValidator::validate( $validator, $datObj->Instance );
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
		if( $validator->checkExist( $datObj, 'Renditions' ) ) {
			$validator->enterPath( 'Renditions' );
			if( !is_null( $datObj->Renditions ) ) {
				$validator->checkType( $datObj->Renditions, 'array' );
				if( !empty($datObj->Renditions) ) foreach( $datObj->Renditions as $listItem ) {
					$validator->enterPath( 'RenditionType' );
					$validator->checkType( $listItem, 'string' );
					WflRenditionTypeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Orientation' ) ) {
			$validator->enterPath( 'Orientation' );
			if( !is_null( $datObj->Orientation ) ) {
				$validator->checkType( $datObj->Orientation, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflObjectValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'MetaData' ) ) {
			$validator->enterPath( 'MetaData' );
			if( !is_null( $datObj->MetaData ) ) {
				$validator->checkType( $datObj->MetaData, 'MetaData' );
				WflMetaDataValidator::validate( $validator, $datObj->MetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Relations' ) ) {
			$validator->enterPath( 'Relations' );
			if( !is_null( $datObj->Relations ) ) {
				$validator->checkType( $datObj->Relations, 'array' );
				if( !empty($datObj->Relations) ) foreach( $datObj->Relations as $listItem ) {
					$validator->enterPath( 'Relation' );
					$validator->checkType( $listItem, 'Relation' );
					WflRelationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Pages' ) ) {
			$validator->enterPath( 'Pages' );
			if( !is_null( $datObj->Pages ) ) {
				$validator->checkType( $datObj->Pages, 'array' );
				if( !empty($datObj->Pages) ) foreach( $datObj->Pages as $listItem ) {
					$validator->enterPath( 'Page' );
					$validator->checkType( $listItem, 'Page' );
					WflPageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
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
					WflAttachmentValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Messages' ) ) {
			$validator->enterPath( 'Messages' );
			if( !is_null( $datObj->Messages ) ) {
				$validator->checkType( $datObj->Messages, 'array' );
				if( !empty($datObj->Messages) ) foreach( $datObj->Messages as $listItem ) {
					$validator->enterPath( 'Message' );
					$validator->checkType( $listItem, 'Message' );
					WflMessageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Elements' ) ) {
			$validator->enterPath( 'Elements' );
			if( !is_null( $datObj->Elements ) ) {
				$validator->checkType( $datObj->Elements, 'array' );
				if( !empty($datObj->Elements) ) foreach( $datObj->Elements as $listItem ) {
					$validator->enterPath( 'Element' );
					$validator->checkType( $listItem, 'Element' );
					WflElementValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Targets' ) ) {
			$validator->enterPath( 'Targets' );
			if( !is_null( $datObj->Targets ) ) {
				$validator->checkType( $datObj->Targets, 'array' );
				if( !empty($datObj->Targets) ) foreach( $datObj->Targets as $listItem ) {
					$validator->enterPath( 'Target' );
					$validator->checkType( $listItem, 'Target' );
					WflTargetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Renditions' ) ) {
			$validator->enterPath( 'Renditions' );
			if( !is_null( $datObj->Renditions ) ) {
				$validator->checkType( $datObj->Renditions, 'array' );
				if( !empty($datObj->Renditions) ) foreach( $datObj->Renditions as $listItem ) {
					$validator->enterPath( 'EditionRenditionsInfo' );
					$validator->checkType( $listItem, 'EditionRenditionsInfo' );
					WflEditionRenditionsInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageList' ) ) {
			$validator->enterPath( 'MessageList' );
			if( !is_null( $datObj->MessageList ) ) {
				$validator->checkType( $datObj->MessageList, 'MessageList' );
				WflMessageListValidator::validate( $validator, $datObj->MessageList );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectLabels' ) ) {
			$validator->enterPath( 'ObjectLabels' );
			if( !is_null( $datObj->ObjectLabels ) ) {
				$validator->checkType( $datObj->ObjectLabels, 'array' );
				if( !empty($datObj->ObjectLabels) ) foreach( $datObj->ObjectLabels as $listItem ) {
					$validator->enterPath( 'ObjectLabel' );
					$validator->checkType( $listItem, 'ObjectLabel' );
					WflObjectLabelValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'InDesignArticles' ) ) {
			$validator->enterPath( 'InDesignArticles' );
			if( !is_null( $datObj->InDesignArticles ) ) {
				$validator->checkType( $datObj->InDesignArticles, 'array' );
				if( !empty($datObj->InDesignArticles) ) foreach( $datObj->InDesignArticles as $listItem ) {
					$validator->enterPath( 'InDesignArticle' );
					$validator->checkType( $listItem, 'InDesignArticle' );
					WflInDesignArticleValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Placements' ) ) {
			$validator->enterPath( 'Placements' );
			if( !is_null( $datObj->Placements ) ) {
				$validator->checkType( $datObj->Placements, 'array' );
				if( !empty($datObj->Placements) ) foreach( $datObj->Placements as $listItem ) {
					$validator->enterPath( 'Placement' );
					$validator->checkType( $listItem, 'Placement' );
					WflPlacementValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Operations' ) ) {
			$validator->enterPath( 'Operations' );
			if( !is_null( $datObj->Operations ) ) {
				$validator->checkType( $datObj->Operations, 'array' );
				if( !empty($datObj->Operations) ) foreach( $datObj->Operations as $listItem ) {
					$validator->enterPath( 'ObjectOperation' );
					$validator->checkType( $listItem, 'ObjectOperation' );
					WflObjectOperationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflNamedQueryTypeValidator
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
		if( $validator->checkExist( $datObj, 'Params' ) ) {
			$validator->enterPath( 'Params' );
			$validator->checkNull( $datObj->Params );
			if( !is_null( $datObj->Params ) ) {
				$validator->checkType( $datObj->Params, 'array' );
				if( !empty($datObj->Params) ) foreach( $datObj->Params as $listItem ) {
					$validator->enterPath( 'PropertyInfo' );
					$validator->checkType( $listItem, 'PropertyInfo' );
					WflPropertyInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflPropertyValidator
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
				WflPropertyTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
	}
}

class WflFacetValidator
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
		if( $validator->checkExist( $datObj, 'FacetItems' ) ) {
			$validator->enterPath( 'FacetItems' );
			$validator->checkNull( $datObj->FacetItems );
			if( !is_null( $datObj->FacetItems ) ) {
				$validator->checkType( $datObj->FacetItems, 'array' );
				if( !empty($datObj->FacetItems) ) foreach( $datObj->FacetItems as $listItem ) {
					$validator->enterPath( 'FacetItem' );
					$validator->checkType( $listItem, 'FacetItem' );
					WflFacetItemValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflFacetItemValidator
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
		if( $validator->checkExist( $datObj, 'Number' ) ) {
			$validator->enterPath( 'Number' );
			$validator->checkNull( $datObj->Number );
			if( !is_null( $datObj->Number ) ) {
				$validator->checkType( $datObj->Number, 'unsignedInt' );
			}
			$validator->leavePath();
		}
	}
}

class WflPropertyInfoValidator
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
		if( $validator->checkExist( $datObj, 'Category' ) ) {
			$validator->enterPath( 'Category' );
			if( !is_null( $datObj->Category ) ) {
				$validator->checkType( $datObj->Category, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				WflPropertyTypeValidator::validate( $validator, $datObj->Type );
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
		if( $validator->checkExist( $datObj, 'PropertyValues' ) ) {
			$validator->enterPath( 'PropertyValues' );
			if( !is_null( $datObj->PropertyValues ) ) {
				$validator->checkType( $datObj->PropertyValues, 'array' );
				if( !empty($datObj->PropertyValues) ) foreach( $datObj->PropertyValues as $listItem ) {
					$validator->enterPath( 'PropertyValue' );
					$validator->checkType( $listItem, 'PropertyValue' );
					WflPropertyValueValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ParentValue' ) ) {
			$validator->enterPath( 'ParentValue' );
			if( !is_null( $datObj->ParentValue ) ) {
				$validator->checkType( $datObj->ParentValue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DependentProperties' ) ) {
			$validator->enterPath( 'DependentProperties' );
			if( !is_null( $datObj->DependentProperties ) ) {
				$validator->checkType( $datObj->DependentProperties, 'array' );
				if( !empty($datObj->DependentProperties) ) foreach( $datObj->DependentProperties as $listItem ) {
					$validator->enterPath( 'Property' );
					$validator->checkType( $listItem, 'Property' );
					WflPropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MinResolution' ) ) {
			$validator->enterPath( 'MinResolution' );
			if( !is_null( $datObj->MinResolution ) ) {
				$validator->checkType( $datObj->MinResolution, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MaxResolution' ) ) {
			$validator->enterPath( 'MaxResolution' );
			if( !is_null( $datObj->MaxResolution ) ) {
				$validator->checkType( $datObj->MaxResolution, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Widgets' ) ) {
			$validator->enterPath( 'Widgets' );
			if( !is_null( $datObj->Widgets ) ) {
				$validator->checkType( $datObj->Widgets, 'array' );
				if( !empty($datObj->Widgets) ) foreach( $datObj->Widgets as $listItem ) {
					$validator->enterPath( 'DialogWidget' );
					$validator->checkType( $listItem, 'DialogWidget' );
					WflDialogWidgetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TermEntity' ) ) {
			$validator->enterPath( 'TermEntity' );
			if( !is_null( $datObj->TermEntity ) ) {
				$validator->checkType( $datObj->TermEntity, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SuggestionEntity' ) ) {
			$validator->enterPath( 'SuggestionEntity' );
			if( !is_null( $datObj->SuggestionEntity ) ) {
				$validator->checkType( $datObj->SuggestionEntity, 'string' );
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
		if( $validator->checkExist( $datObj, 'SuggestionProvider' ) ) {
			$validator->enterPath( 'SuggestionProvider' );
			if( !is_null( $datObj->SuggestionProvider ) ) {
				$validator->checkType( $datObj->SuggestionProvider, 'string' );
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
		if( $validator->checkExist( $datObj, 'Notifications' ) ) {
			$validator->enterPath( 'Notifications' );
			if( !is_null( $datObj->Notifications ) ) {
				$validator->checkType( $datObj->Notifications, 'array' );
				if( !empty($datObj->Notifications) ) foreach( $datObj->Notifications as $listItem ) {
					$validator->enterPath( 'PropertyNotification' );
					$validator->checkType( $listItem, 'PropertyNotification' );
					WflPropertyNotificationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MixedValues' ) ) {
			$validator->enterPath( 'MixedValues' );
			if( !is_null( $datObj->MixedValues ) ) {
				$validator->checkType( $datObj->MixedValues, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class WflPropertyUsageValidator
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
		if( $validator->checkExist( $datObj, 'Editable' ) ) {
			$validator->enterPath( 'Editable' );
			$validator->checkNull( $datObj->Editable );
			if( !is_null( $datObj->Editable ) ) {
				$validator->checkType( $datObj->Editable, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Mandatory' ) ) {
			$validator->enterPath( 'Mandatory' );
			$validator->checkNull( $datObj->Mandatory );
			if( !is_null( $datObj->Mandatory ) ) {
				$validator->checkType( $datObj->Mandatory, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Restricted' ) ) {
			$validator->enterPath( 'Restricted' );
			$validator->checkNull( $datObj->Restricted );
			if( !is_null( $datObj->Restricted ) ) {
				$validator->checkType( $datObj->Restricted, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RefreshOnChange' ) ) {
			$validator->enterPath( 'RefreshOnChange' );
			$validator->checkNull( $datObj->RefreshOnChange );
			if( !is_null( $datObj->RefreshOnChange ) ) {
				$validator->checkType( $datObj->RefreshOnChange, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'InitialHeight' ) ) {
			$validator->enterPath( 'InitialHeight' );
			if( !is_null( $datObj->InitialHeight ) ) {
				$validator->checkType( $datObj->InitialHeight, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MultipleObjects' ) ) {
			$validator->enterPath( 'MultipleObjects' );
			if( !is_null( $datObj->MultipleObjects ) ) {
				$validator->checkType( $datObj->MultipleObjects, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class WflActionPropertyValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Action' ) ) {
			$validator->enterPath( 'Action' );
			$validator->checkNull( $datObj->Action );
			if( !is_null( $datObj->Action ) ) {
				$validator->checkType( $datObj->Action, 'string' );
				WflActionValidator::validate( $validator, $datObj->Action );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectType' ) ) {
			$validator->enterPath( 'ObjectType' );
			if( !is_null( $datObj->ObjectType ) ) {
				$validator->checkType( $datObj->ObjectType, 'string' );
				WflObjectTypeValidator::validate( $validator, $datObj->ObjectType );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Properties' ) ) {
			$validator->enterPath( 'Properties' );
			$validator->checkNull( $datObj->Properties );
			if( !is_null( $datObj->Properties ) ) {
				$validator->checkType( $datObj->Properties, 'array' );
				if( !empty($datObj->Properties) ) foreach( $datObj->Properties as $listItem ) {
					$validator->enterPath( 'PropertyUsage' );
					$validator->checkType( $listItem, 'PropertyUsage' );
					WflPropertyUsageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflObjectTypePropertyValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				WflObjectTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Properties' ) ) {
			$validator->enterPath( 'Properties' );
			$validator->checkNull( $datObj->Properties );
			if( !is_null( $datObj->Properties ) ) {
				$validator->checkType( $datObj->Properties, 'array' );
				if( !empty($datObj->Properties) ) foreach( $datObj->Properties as $listItem ) {
					$validator->enterPath( 'PropertyInfo' );
					$validator->checkType( $listItem, 'PropertyInfo' );
					WflPropertyInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflQueryOrderValidator
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
		if( $validator->checkExist( $datObj, 'Direction' ) ) {
			$validator->enterPath( 'Direction' );
			$validator->checkNull( $datObj->Direction );
			if( !is_null( $datObj->Direction ) ) {
				$validator->checkType( $datObj->Direction, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class WflQueryParamValidator
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
				WflOperationTypeValidator::validate( $validator, $datObj->Operation );
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
		if( $validator->checkExist( $datObj, 'Special' ) ) {
			$validator->enterPath( 'Special' );
			if( !is_null( $datObj->Special ) ) {
				$validator->checkType( $datObj->Special, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Value2' ) ) {
			$validator->enterPath( 'Value2' );
			if( !is_null( $datObj->Value2 ) ) {
				$validator->checkType( $datObj->Value2, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflVersionInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Version' ) ) {
			$validator->enterPath( 'Version' );
			$validator->checkNull( $datObj->Version );
			if( !is_null( $datObj->Version ) ) {
				$validator->checkType( $datObj->Version, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'User' ) ) {
			$validator->enterPath( 'User' );
			$validator->checkNull( $datObj->User );
			if( !is_null( $datObj->User ) ) {
				$validator->checkType( $datObj->User, 'string' );
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
		if( $validator->checkExist( $datObj, 'Slugline' ) ) {
			$validator->enterPath( 'Slugline' );
			$validator->checkNull( $datObj->Slugline );
			if( !is_null( $datObj->Slugline ) ) {
				$validator->checkType( $datObj->Slugline, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Created' ) ) {
			$validator->enterPath( 'Created' );
			$validator->checkNull( $datObj->Created );
			if( !is_null( $datObj->Created ) ) {
				$validator->checkType( $datObj->Created, 'dateTime' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Object' ) ) {
			$validator->enterPath( 'Object' );
			$validator->checkNull( $datObj->Object );
			if( !is_null( $datObj->Object ) ) {
				$validator->checkType( $datObj->Object, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'State' ) ) {
			$validator->enterPath( 'State' );
			$validator->checkNull( $datObj->State );
			if( !is_null( $datObj->State ) ) {
				$validator->checkType( $datObj->State, 'State' );
				WflStateValidator::validate( $validator, $datObj->State );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'File' ) ) {
			$validator->enterPath( 'File' );
			if( !is_null( $datObj->File ) ) {
				$validator->checkType( $datObj->File, 'Attachment' );
				WflAttachmentValidator::validate( $validator, $datObj->File );
			}
			$validator->leavePath();
		}
	}
}

class WflAttachmentValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Rendition' ) ) {
			$validator->enterPath( 'Rendition' );
			$validator->checkNull( $datObj->Rendition );
			if( !is_null( $datObj->Rendition ) ) {
				$validator->checkType( $datObj->Rendition, 'string' );
				WflRenditionTypeValidator::validate( $validator, $datObj->Rendition );
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
		if( $validator->checkExist( $datObj, 'EditionId' ) ) {
			$validator->enterPath( 'EditionId' );
			if( !is_null( $datObj->EditionId ) ) {
				$validator->checkType( $datObj->EditionId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ContentSourceFileLink' ) ) {
			$validator->enterPath( 'ContentSourceFileLink' );
			if( !is_null( $datObj->ContentSourceFileLink ) ) {
				$validator->checkType( $datObj->ContentSourceFileLink, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflRenditionTypeInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Rendition' ) ) {
			$validator->enterPath( 'Rendition' );
			$validator->checkNull( $datObj->Rendition );
			if( !is_null( $datObj->Rendition ) ) {
				$validator->checkType( $datObj->Rendition, 'string' );
				WflRenditionTypeValidator::validate( $validator, $datObj->Rendition );
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

class WflEditionRenditionsInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Edition' ) ) {
			$validator->enterPath( 'Edition' );
			if( !is_null( $datObj->Edition ) ) {
				$validator->checkType( $datObj->Edition, 'Edition' );
				WflEditionValidator::validate( $validator, $datObj->Edition );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Renditions' ) ) {
			$validator->enterPath( 'Renditions' );
			$validator->checkNull( $datObj->Renditions );
			if( !is_null( $datObj->Renditions ) ) {
				$validator->checkType( $datObj->Renditions, 'array' );
				if( !empty($datObj->Renditions) ) foreach( $datObj->Renditions as $listItem ) {
					$validator->enterPath( 'RenditionTypeInfo' );
					$validator->checkType( $listItem, 'RenditionTypeInfo' );
					WflRenditionTypeInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflPublicationValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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

class WflPublicationInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
		if( $validator->checkExist( $datObj, 'Issues' ) ) {
			$validator->enterPath( 'Issues' );
			if( !is_null( $datObj->Issues ) ) {
				$validator->checkType( $datObj->Issues, 'array' );
				if( !empty($datObj->Issues) ) foreach( $datObj->Issues as $listItem ) {
					$validator->enterPath( 'IssueInfo' );
					$validator->checkType( $listItem, 'IssueInfo' );
					WflIssueInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'States' ) ) {
			$validator->enterPath( 'States' );
			if( !is_null( $datObj->States ) ) {
				$validator->checkType( $datObj->States, 'array' );
				if( !empty($datObj->States) ) foreach( $datObj->States as $listItem ) {
					$validator->enterPath( 'State' );
					$validator->checkType( $listItem, 'State' );
					WflStateValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectTypeProperties' ) ) {
			$validator->enterPath( 'ObjectTypeProperties' );
			if( !is_null( $datObj->ObjectTypeProperties ) ) {
				$validator->checkType( $datObj->ObjectTypeProperties, 'array' );
				if( !empty($datObj->ObjectTypeProperties) ) foreach( $datObj->ObjectTypeProperties as $listItem ) {
					$validator->enterPath( 'ObjectTypeProperty' );
					$validator->checkType( $listItem, 'ObjectTypeProperty' );
					WflObjectTypePropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ActionProperties' ) ) {
			$validator->enterPath( 'ActionProperties' );
			if( !is_null( $datObj->ActionProperties ) ) {
				$validator->checkType( $datObj->ActionProperties, 'array' );
				if( !empty($datObj->ActionProperties) ) foreach( $datObj->ActionProperties as $listItem ) {
					$validator->enterPath( 'ActionProperty' );
					$validator->checkType( $listItem, 'ActionProperty' );
					WflActionPropertyValidator::validate( $validator, $listItem );
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
					WflEditionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FeatureAccessList' ) ) {
			$validator->enterPath( 'FeatureAccessList' );
			if( !is_null( $datObj->FeatureAccessList ) ) {
				$validator->checkType( $datObj->FeatureAccessList, 'array' );
				if( !empty($datObj->FeatureAccessList) ) foreach( $datObj->FeatureAccessList as $listItem ) {
					$validator->enterPath( 'FeatureAccess' );
					$validator->checkType( $listItem, 'FeatureAccess' );
					WflFeatureAccessValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CurrentIssue' ) ) {
			$validator->enterPath( 'CurrentIssue' );
			if( !is_null( $datObj->CurrentIssue ) ) {
				$validator->checkType( $datObj->CurrentIssue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PubChannels' ) ) {
			$validator->enterPath( 'PubChannels' );
			if( !is_null( $datObj->PubChannels ) ) {
				$validator->checkType( $datObj->PubChannels, 'array' );
				if( !empty($datObj->PubChannels) ) foreach( $datObj->PubChannels as $listItem ) {
					$validator->enterPath( 'PubChannelInfo' );
					$validator->checkType( $listItem, 'PubChannelInfo' );
					WflPubChannelInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Categories' ) ) {
			$validator->enterPath( 'Categories' );
			if( !is_null( $datObj->Categories ) ) {
				$validator->checkType( $datObj->Categories, 'array' );
				if( !empty($datObj->Categories) ) foreach( $datObj->Categories as $listItem ) {
					$validator->enterPath( 'CategoryInfo' );
					$validator->checkType( $listItem, 'CategoryInfo' );
					WflCategoryInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Dictionaries' ) ) {
			$validator->enterPath( 'Dictionaries' );
			if( !is_null( $datObj->Dictionaries ) ) {
				$validator->checkType( $datObj->Dictionaries, 'array' );
				if( !empty($datObj->Dictionaries) ) foreach( $datObj->Dictionaries as $listItem ) {
					$validator->enterPath( 'Dictionary' );
					$validator->checkType( $listItem, 'Dictionary' );
					WflDictionaryValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReversedRead' ) ) {
			$validator->enterPath( 'ReversedRead' );
			$validator->checkNull( $datObj->ReversedRead );
			if( !is_null( $datObj->ReversedRead ) ) {
				$validator->checkType( $datObj->ReversedRead, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class WflCategoryValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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

class WflCategoryInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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

class WflPubChannelValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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

class WflPubChannelInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
		if( $validator->checkExist( $datObj, 'Issues' ) ) {
			$validator->enterPath( 'Issues' );
			if( !is_null( $datObj->Issues ) ) {
				$validator->checkType( $datObj->Issues, 'array' );
				if( !empty($datObj->Issues) ) foreach( $datObj->Issues as $listItem ) {
					$validator->enterPath( 'IssueInfo' );
					$validator->checkType( $listItem, 'IssueInfo' );
					WflIssueInfoValidator::validate( $validator, $listItem );
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
					WflEditionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CurrentIssue' ) ) {
			$validator->enterPath( 'CurrentIssue' );
			if( !is_null( $datObj->CurrentIssue ) ) {
				$validator->checkType( $datObj->CurrentIssue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				WflPubChannelTypeValidator::validate( $validator, $datObj->Type );
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
	}
}

class WflEditionValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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

class WflIssueValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
		if( $validator->checkExist( $datObj, 'OverrulePublication' ) ) {
			$validator->enterPath( 'OverrulePublication' );
			if( !is_null( $datObj->OverrulePublication ) ) {
				$validator->checkType( $datObj->OverrulePublication, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class WflIssueInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
		if( $validator->checkExist( $datObj, 'OverrulePublication' ) ) {
			$validator->enterPath( 'OverrulePublication' );
			if( !is_null( $datObj->OverrulePublication ) ) {
				$validator->checkType( $datObj->OverrulePublication, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Sections' ) ) {
			$validator->enterPath( 'Sections' );
			if( !is_null( $datObj->Sections ) ) {
				$validator->checkType( $datObj->Sections, 'array' );
				if( !empty($datObj->Sections) ) foreach( $datObj->Sections as $listItem ) {
					$validator->enterPath( 'CategoryInfo' );
					$validator->checkType( $listItem, 'CategoryInfo' );
					WflCategoryInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'States' ) ) {
			$validator->enterPath( 'States' );
			if( !is_null( $datObj->States ) ) {
				$validator->checkType( $datObj->States, 'array' );
				if( !empty($datObj->States) ) foreach( $datObj->States as $listItem ) {
					$validator->enterPath( 'State' );
					$validator->checkType( $listItem, 'State' );
					WflStateValidator::validate( $validator, $listItem );
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
					WflEditionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
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
		if( $validator->checkExist( $datObj, 'Subject' ) ) {
			$validator->enterPath( 'Subject' );
			if( !is_null( $datObj->Subject ) ) {
				$validator->checkType( $datObj->Subject, 'string' );
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
		if( $validator->checkExist( $datObj, 'ReversedRead' ) ) {
			$validator->enterPath( 'ReversedRead' );
			if( !is_null( $datObj->ReversedRead ) ) {
				$validator->checkType( $datObj->ReversedRead, 'boolean' );
			}
			$validator->leavePath();
		}
	}
}

class WflStateValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
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
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				WflObjectTypeValidator::validate( $validator, $datObj->Type );
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
				$validator->checkType( $datObj->DefaultRouteTo, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflTargetValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'PubChannel' ) ) {
			$validator->enterPath( 'PubChannel' );
			$validator->checkNull( $datObj->PubChannel );
			if( !is_null( $datObj->PubChannel ) ) {
				$validator->checkType( $datObj->PubChannel, 'PubChannel' );
				WflPubChannelValidator::validate( $validator, $datObj->PubChannel );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Issue' ) ) {
			$validator->enterPath( 'Issue' );
			if( !is_null( $datObj->Issue ) ) {
				$validator->checkType( $datObj->Issue, 'Issue' );
				WflIssueValidator::validate( $validator, $datObj->Issue );
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
					WflEditionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedDate' ) ) {
			$validator->enterPath( 'PublishedDate' );
			if( !is_null( $datObj->PublishedDate ) ) {
				$validator->checkType( $datObj->PublishedDate, 'dateTimeOrEmpty' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedVersion' ) ) {
			$validator->enterPath( 'PublishedVersion' );
			if( !is_null( $datObj->PublishedVersion ) ) {
				$validator->checkType( $datObj->PublishedVersion, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflObjectTargetsInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'BasicMetaData' ) ) {
			$validator->enterPath( 'BasicMetaData' );
			$validator->checkNull( $datObj->BasicMetaData );
			if( !is_null( $datObj->BasicMetaData ) ) {
				$validator->checkType( $datObj->BasicMetaData, 'BasicMetaData' );
				WflBasicMetaDataValidator::validate( $validator, $datObj->BasicMetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Targets' ) ) {
			$validator->enterPath( 'Targets' );
			$validator->checkNull( $datObj->Targets );
			if( !is_null( $datObj->Targets ) ) {
				$validator->checkType( $datObj->Targets, 'array' );
				if( !empty($datObj->Targets) ) foreach( $datObj->Targets as $listItem ) {
					$validator->enterPath( 'Target' );
					$validator->checkType( $listItem, 'Target' );
					WflTargetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflRowValidator
{
	static public function validate( $validator, $datObj )
	{
		$validator = $validator; $datObj = $datObj; // keep validator happy
	}
}

class WflChildRowValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Parents' ) ) {
			$validator->enterPath( 'Parents' );
			$validator->checkNull( $datObj->Parents );
			if( !is_null( $datObj->Parents ) ) {
				$validator->checkType( $datObj->Parents, 'array' );
				if( !empty($datObj->Parents) ) foreach( $datObj->Parents as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Row' ) ) {
			$validator->enterPath( 'Row' );
			if( !is_null( $datObj->Row ) ) {
				$validator->checkType( $datObj->Row, 'array' );
				if( !empty($datObj->Row) ) foreach( $datObj->Row as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflAppFeatureValidator
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

class WflFeatureProfileValidator
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
		if( $validator->checkExist( $datObj, 'Features' ) ) {
			$validator->enterPath( 'Features' );
			if( !is_null( $datObj->Features ) ) {
				$validator->checkType( $datObj->Features, 'array' );
				if( !empty($datObj->Features) ) foreach( $datObj->Features as $listItem ) {
					$validator->enterPath( 'AppFeature' );
					$validator->checkType( $listItem, 'AppFeature' );
					WflAppFeatureValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflFeatureAccessValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Profile' ) ) {
			$validator->enterPath( 'Profile' );
			$validator->checkNull( $datObj->Profile );
			if( !is_null( $datObj->Profile ) ) {
				$validator->checkType( $datObj->Profile, 'string' );
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
		if( $validator->checkExist( $datObj, 'Section' ) ) {
			$validator->enterPath( 'Section' );
			if( !is_null( $datObj->Section ) ) {
				$validator->checkType( $datObj->Section, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'State' ) ) {
			$validator->enterPath( 'State' );
			if( !is_null( $datObj->State ) ) {
				$validator->checkType( $datObj->State, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflDialogWidgetValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'PropertyInfo' ) ) {
			$validator->enterPath( 'PropertyInfo' );
			$validator->checkNull( $datObj->PropertyInfo );
			if( !is_null( $datObj->PropertyInfo ) ) {
				$validator->checkType( $datObj->PropertyInfo, 'PropertyInfo' );
				WflPropertyInfoValidator::validate( $validator, $datObj->PropertyInfo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PropertyUsage' ) ) {
			$validator->enterPath( 'PropertyUsage' );
			$validator->checkNull( $datObj->PropertyUsage );
			if( !is_null( $datObj->PropertyUsage ) ) {
				$validator->checkType( $datObj->PropertyUsage, 'PropertyUsage' );
				WflPropertyUsageValidator::validate( $validator, $datObj->PropertyUsage );
			}
			$validator->leavePath();
		}
	}
}

class WflDialogTabValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Title' ) ) {
			$validator->enterPath( 'Title' );
			$validator->checkNull( $datObj->Title );
			if( !is_null( $datObj->Title ) ) {
				$validator->checkType( $datObj->Title, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Widgets' ) ) {
			$validator->enterPath( 'Widgets' );
			$validator->checkNull( $datObj->Widgets );
			if( !is_null( $datObj->Widgets ) ) {
				$validator->checkType( $datObj->Widgets, 'array' );
				if( !empty($datObj->Widgets) ) foreach( $datObj->Widgets as $listItem ) {
					$validator->enterPath( 'DialogWidget' );
					$validator->checkType( $listItem, 'DialogWidget' );
					WflDialogWidgetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DefaultFocus' ) ) {
			$validator->enterPath( 'DefaultFocus' );
			$validator->checkNull( $datObj->DefaultFocus );
			if( !is_null( $datObj->DefaultFocus ) ) {
				$validator->checkType( $datObj->DefaultFocus, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflMetaDataValueValidator
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
		if( $validator->checkExist( $datObj, 'PropertyValues' ) ) {
			$validator->enterPath( 'PropertyValues' );
			if( !is_null( $datObj->PropertyValues ) ) {
				$validator->checkType( $datObj->PropertyValues, 'array' );
				if( !empty($datObj->PropertyValues) ) foreach( $datObj->PropertyValues as $listItem ) {
					$validator->enterPath( 'PropertyValue' );
					$validator->checkType( $listItem, 'PropertyValue' );
					WflPropertyValueValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflDialogValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Title' ) ) {
			$validator->enterPath( 'Title' );
			$validator->checkNull( $datObj->Title );
			if( !is_null( $datObj->Title ) ) {
				$validator->checkType( $datObj->Title, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Tabs' ) ) {
			$validator->enterPath( 'Tabs' );
			$validator->checkNull( $datObj->Tabs );
			if( !is_null( $datObj->Tabs ) ) {
				$validator->checkType( $datObj->Tabs, 'array' );
				if( !empty($datObj->Tabs) ) foreach( $datObj->Tabs as $listItem ) {
					$validator->enterPath( 'DialogTab' );
					$validator->checkType( $listItem, 'DialogTab' );
					WflDialogTabValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MetaData' ) ) {
			$validator->enterPath( 'MetaData' );
			$validator->checkNull( $datObj->MetaData );
			if( !is_null( $datObj->MetaData ) ) {
				$validator->checkType( $datObj->MetaData, 'array' );
				if( !empty($datObj->MetaData) ) foreach( $datObj->MetaData as $listItem ) {
					$validator->enterPath( 'MetaDataValue' );
					$validator->checkType( $listItem, 'MetaDataValue' );
					WflMetaDataValueValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ButtonBar' ) ) {
			$validator->enterPath( 'ButtonBar' );
			if( !is_null( $datObj->ButtonBar ) ) {
				$validator->checkType( $datObj->ButtonBar, 'array' );
				if( !empty($datObj->ButtonBar) ) foreach( $datObj->ButtonBar as $listItem ) {
					$validator->enterPath( 'DialogButton' );
					$validator->checkType( $listItem, 'DialogButton' );
					WflDialogButtonValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflDialogButtonValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'PropertyInfo' ) ) {
			$validator->enterPath( 'PropertyInfo' );
			$validator->checkNull( $datObj->PropertyInfo );
			if( !is_null( $datObj->PropertyInfo ) ) {
				$validator->checkType( $datObj->PropertyInfo, 'PropertyInfo' );
				WflPropertyInfoValidator::validate( $validator, $datObj->PropertyInfo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PropertyUsage' ) ) {
			$validator->enterPath( 'PropertyUsage' );
			$validator->checkNull( $datObj->PropertyUsage );
			if( !is_null( $datObj->PropertyUsage ) ) {
				$validator->checkType( $datObj->PropertyUsage, 'PropertyUsage' );
				WflPropertyUsageValidator::validate( $validator, $datObj->PropertyUsage );
			}
			$validator->leavePath();
		}
	}
}

class WflPropertyValueValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Value' ) ) {
			$validator->enterPath( 'Value' );
			$validator->checkNull( $datObj->Value );
			if( !is_null( $datObj->Value ) ) {
				$validator->checkType( $datObj->Value, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Display' ) ) {
			$validator->enterPath( 'Display' );
			if( !is_null( $datObj->Display ) ) {
				$validator->checkType( $datObj->Display, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Entity' ) ) {
			$validator->enterPath( 'Entity' );
			if( !is_null( $datObj->Entity ) ) {
				$validator->checkType( $datObj->Entity, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflObjectPageInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'MetaData' ) ) {
			$validator->enterPath( 'MetaData' );
			if( !is_null( $datObj->MetaData ) ) {
				$validator->checkType( $datObj->MetaData, 'MetaData' );
				WflMetaDataValidator::validate( $validator, $datObj->MetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Pages' ) ) {
			$validator->enterPath( 'Pages' );
			$validator->checkNull( $datObj->Pages );
			if( !is_null( $datObj->Pages ) ) {
				$validator->checkType( $datObj->Pages, 'array' );
				if( !empty($datObj->Pages) ) foreach( $datObj->Pages as $listItem ) {
					$validator->enterPath( 'Page' );
					$validator->checkType( $listItem, 'Page' );
					WflPageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Messages' ) ) {
			$validator->enterPath( 'Messages' );
			if( !is_null( $datObj->Messages ) ) {
				$validator->checkType( $datObj->Messages, 'array' );
				if( !empty($datObj->Messages) ) foreach( $datObj->Messages as $listItem ) {
					$validator->enterPath( 'Message' );
					$validator->checkType( $listItem, 'Message' );
					WflMessageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageList' ) ) {
			$validator->enterPath( 'MessageList' );
			if( !is_null( $datObj->MessageList ) ) {
				$validator->checkType( $datObj->MessageList, 'MessageList' );
				WflMessageListValidator::validate( $validator, $datObj->MessageList );
			}
			$validator->leavePath();
		}
	}
}

class WflObjectInfoValidator
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
				WflObjectTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Format' ) ) {
			$validator->enterPath( 'Format' );
			if( !is_null( $datObj->Format ) ) {
				$validator->checkType( $datObj->Format, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflObjectVersionValidator
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
		if( $validator->checkExist( $datObj, 'Version' ) ) {
			$validator->enterPath( 'Version' );
			$validator->checkNull( $datObj->Version );
			if( !is_null( $datObj->Version ) ) {
				$validator->checkType( $datObj->Version, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflSuggestionValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'MisspelledWord' ) ) {
			$validator->enterPath( 'MisspelledWord' );
			$validator->checkNull( $datObj->MisspelledWord );
			if( !is_null( $datObj->MisspelledWord ) ) {
				$validator->checkType( $datObj->MisspelledWord, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Suggestions' ) ) {
			$validator->enterPath( 'Suggestions' );
			$validator->checkNull( $datObj->Suggestions );
			if( !is_null( $datObj->Suggestions ) ) {
				$validator->checkType( $datObj->Suggestions, 'array' );
				if( !empty($datObj->Suggestions) ) foreach( $datObj->Suggestions as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflDictionaryValidator
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
		if( $validator->checkExist( $datObj, 'Language' ) ) {
			$validator->enterPath( 'Language' );
			$validator->checkNull( $datObj->Language );
			if( !is_null( $datObj->Language ) ) {
				$validator->checkType( $datObj->Language, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DocLanguage' ) ) {
			$validator->enterPath( 'DocLanguage' );
			$validator->checkNull( $datObj->DocLanguage );
			if( !is_null( $datObj->DocLanguage ) ) {
				$validator->checkType( $datObj->DocLanguage, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'WordChars' ) ) {
			$validator->enterPath( 'WordChars' );
			$validator->checkNull( $datObj->WordChars );
			if( !is_null( $datObj->WordChars ) ) {
				$validator->checkType( $datObj->WordChars, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflErrorReportEntityValidator
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
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
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
		if( $validator->checkExist( $datObj, 'Role' ) ) {
			$validator->enterPath( 'Role' );
			if( !is_null( $datObj->Role ) ) {
				$validator->checkType( $datObj->Role, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflErrorReportEntryValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Entities' ) ) {
			$validator->enterPath( 'Entities' );
			$validator->checkNull( $datObj->Entities );
			if( !is_null( $datObj->Entities ) ) {
				$validator->checkType( $datObj->Entities, 'array' );
				if( !empty($datObj->Entities) ) foreach( $datObj->Entities as $listItem ) {
					$validator->enterPath( 'ErrorReportEntity' );
					$validator->checkType( $listItem, 'ErrorReportEntity' );
					WflErrorReportEntityValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
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
		if( $validator->checkExist( $datObj, 'Details' ) ) {
			$validator->enterPath( 'Details' );
			$validator->checkNull( $datObj->Details );
			if( !is_null( $datObj->Details ) ) {
				$validator->checkType( $datObj->Details, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ErrorCode' ) ) {
			$validator->enterPath( 'ErrorCode' );
			$validator->checkNull( $datObj->ErrorCode );
			if( !is_null( $datObj->ErrorCode ) ) {
				$validator->checkType( $datObj->ErrorCode, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageLevel' ) ) {
			$validator->enterPath( 'MessageLevel' );
			$validator->checkNull( $datObj->MessageLevel );
			if( !is_null( $datObj->MessageLevel ) ) {
				$validator->checkType( $datObj->MessageLevel, 'string' );
				WflMessageLevelValidator::validate( $validator, $datObj->MessageLevel );
			}
			$validator->leavePath();
		}
	}
}

class WflErrorReportValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'BelongsTo' ) ) {
			$validator->enterPath( 'BelongsTo' );
			$validator->checkNull( $datObj->BelongsTo );
			if( !is_null( $datObj->BelongsTo ) ) {
				$validator->checkType( $datObj->BelongsTo, 'ErrorReportEntity' );
				WflErrorReportEntityValidator::validate( $validator, $datObj->BelongsTo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Entries' ) ) {
			$validator->enterPath( 'Entries' );
			$validator->checkNull( $datObj->Entries );
			if( !is_null( $datObj->Entries ) ) {
				$validator->checkType( $datObj->Entries, 'array' );
				if( !empty($datObj->Entries) ) foreach( $datObj->Entries as $listItem ) {
					$validator->enterPath( 'ErrorReportEntry' );
					$validator->checkType( $listItem, 'ErrorReportEntry' );
					WflErrorReportEntryValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflAutoSuggestTagValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Value' ) ) {
			$validator->enterPath( 'Value' );
			$validator->checkNull( $datObj->Value );
			if( !is_null( $datObj->Value ) ) {
				$validator->checkType( $datObj->Value, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Score' ) ) {
			$validator->enterPath( 'Score' );
			$validator->checkNull( $datObj->Score );
			if( !is_null( $datObj->Score ) ) {
				$validator->checkType( $datObj->Score, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'StartPos' ) ) {
			$validator->enterPath( 'StartPos' );
			if( !is_null( $datObj->StartPos ) ) {
				$validator->checkType( $datObj->StartPos, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Length' ) ) {
			$validator->enterPath( 'Length' );
			if( !is_null( $datObj->Length ) ) {
				$validator->checkType( $datObj->Length, 'integer' );
			}
			$validator->leavePath();
		}
	}
}

class WflEntityTagsValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Entity' ) ) {
			$validator->enterPath( 'Entity' );
			$validator->checkNull( $datObj->Entity );
			if( !is_null( $datObj->Entity ) ) {
				$validator->checkType( $datObj->Entity, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Tags' ) ) {
			$validator->enterPath( 'Tags' );
			$validator->checkNull( $datObj->Tags );
			if( !is_null( $datObj->Tags ) ) {
				$validator->checkType( $datObj->Tags, 'array' );
				if( !empty($datObj->Tags) ) foreach( $datObj->Tags as $listItem ) {
					$validator->enterPath( 'AutoSuggestTag' );
					$validator->checkType( $listItem, 'AutoSuggestTag' );
					WflAutoSuggestTagValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflAutoSuggestPropertyValidator
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
		if( $validator->checkExist( $datObj, 'Entity' ) ) {
			$validator->enterPath( 'Entity' );
			$validator->checkNull( $datObj->Entity );
			if( !is_null( $datObj->Entity ) ) {
				$validator->checkType( $datObj->Entity, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IgnoreValues' ) ) {
			$validator->enterPath( 'IgnoreValues' );
			if( !is_null( $datObj->IgnoreValues ) ) {
				$validator->checkType( $datObj->IgnoreValues, 'array' );
				if( !empty($datObj->IgnoreValues) ) foreach( $datObj->IgnoreValues as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflPropertyNotificationValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			$validator->checkNull( $datObj->Type );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				WflMessageLevelValidator::validate( $validator, $datObj->Type );
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
	}
}

class WflObjectLabelValidator
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
	}
}

class WflRoutingMetaDataValidator
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
		if( $validator->checkExist( $datObj, 'State' ) ) {
			$validator->enterPath( 'State' );
			$validator->checkNull( $datObj->State );
			if( !is_null( $datObj->State ) ) {
				$validator->checkType( $datObj->State, 'State' );
				WflStateValidator::validate( $validator, $datObj->State );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RouteTo' ) ) {
			$validator->enterPath( 'RouteTo' );
			$validator->checkNull( $datObj->RouteTo );
			if( !is_null( $datObj->RouteTo ) ) {
				$validator->checkType( $datObj->RouteTo, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflArticleAtWorkspaceValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
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
		if( $validator->checkExist( $datObj, 'Content' ) ) {
			$validator->enterPath( 'Content' );
			if( !is_null( $datObj->Content ) ) {
				$validator->checkType( $datObj->Content, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Elements' ) ) {
			$validator->enterPath( 'Elements' );
			if( !is_null( $datObj->Elements ) ) {
				$validator->checkType( $datObj->Elements, 'array' );
				if( !empty($datObj->Elements) ) foreach( $datObj->Elements as $listItem ) {
					$validator->enterPath( 'Element' );
					$validator->checkType( $listItem, 'Element' );
					WflElementValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflParamValidator
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

class WflObjectOperationValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
		if( $validator->checkExist( $datObj, 'Params' ) ) {
			$validator->enterPath( 'Params' );
			$validator->checkNull( $datObj->Params );
			if( !is_null( $datObj->Params ) ) {
				$validator->checkType( $datObj->Params, 'array' );
				if( !empty($datObj->Params) ) foreach( $datObj->Params as $listItem ) {
					$validator->enterPath( 'Param' );
					$validator->checkType( $listItem, 'Param' );
					WflParamValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflMessageQueueConnectionValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Instance' ) ) {
			$validator->enterPath( 'Instance' );
			$validator->checkNull( $datObj->Instance );
			if( !is_null( $datObj->Instance ) ) {
				$validator->checkType( $datObj->Instance, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Protocol' ) ) {
			$validator->enterPath( 'Protocol' );
			$validator->checkNull( $datObj->Protocol );
			if( !is_null( $datObj->Protocol ) ) {
				$validator->checkType( $datObj->Protocol, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Url' ) ) {
			$validator->enterPath( 'Url' );
			$validator->checkNull( $datObj->Url );
			if( !is_null( $datObj->Url ) ) {
				$validator->checkType( $datObj->Url, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'User' ) ) {
			$validator->enterPath( 'User' );
			$validator->checkNull( $datObj->User );
			if( !is_null( $datObj->User ) ) {
				$validator->checkType( $datObj->User, 'string' );
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
		if( $validator->checkExist( $datObj, 'VirtualHost' ) ) {
			$validator->enterPath( 'VirtualHost' );
			if( !is_null( $datObj->VirtualHost ) ) {
				$validator->checkType( $datObj->VirtualHost, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflGetStatesResponseValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'States' ) ) {
			$validator->enterPath( 'States' );
			$validator->checkNull( $datObj->States );
			if( !is_null( $datObj->States ) ) {
				$validator->checkType( $datObj->States, 'array' );
				if( !empty($datObj->States) ) foreach( $datObj->States as $listItem ) {
					$validator->enterPath( 'State' );
					$validator->checkType( $listItem, 'State' );
					WflStateValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RouteToUsers' ) ) {
			$validator->enterPath( 'RouteToUsers' );
			$validator->checkNull( $datObj->RouteToUsers );
			if( !is_null( $datObj->RouteToUsers ) ) {
				$validator->checkType( $datObj->RouteToUsers, 'array' );
				if( !empty($datObj->RouteToUsers) ) foreach( $datObj->RouteToUsers as $listItem ) {
					$validator->enterPath( 'User' );
					$validator->checkType( $listItem, 'User' );
					WflUserValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RouteToGroups' ) ) {
			$validator->enterPath( 'RouteToGroups' );
			$validator->checkNull( $datObj->RouteToGroups );
			if( !is_null( $datObj->RouteToGroups ) ) {
				$validator->checkType( $datObj->RouteToGroups, 'array' );
				if( !empty($datObj->RouteToGroups) ) foreach( $datObj->RouteToGroups as $listItem ) {
					$validator->enterPath( 'UserGroup' );
					$validator->checkType( $listItem, 'UserGroup' );
					WflUserGroupValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflEditionPagesValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Edition' ) ) {
			$validator->enterPath( 'Edition' );
			if( !is_null( $datObj->Edition ) ) {
				$validator->checkType( $datObj->Edition, 'Edition' );
				WflEditionValidator::validate( $validator, $datObj->Edition );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageObjects' ) ) {
			$validator->enterPath( 'PageObjects' );
			$validator->checkNull( $datObj->PageObjects );
			if( !is_null( $datObj->PageObjects ) ) {
				$validator->checkType( $datObj->PageObjects, 'array' );
				if( !empty($datObj->PageObjects) ) foreach( $datObj->PageObjects as $listItem ) {
					$validator->enterPath( 'PageObject' );
					$validator->checkType( $listItem, 'PageObject' );
					WflPageObjectValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflPageObjectValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'IssuePagePosition' ) ) {
			$validator->enterPath( 'IssuePagePosition' );
			$validator->checkNull( $datObj->IssuePagePosition );
			if( !is_null( $datObj->IssuePagePosition ) ) {
				$validator->checkType( $datObj->IssuePagePosition, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageOrder' ) ) {
			$validator->enterPath( 'PageOrder' );
			$validator->checkNull( $datObj->PageOrder );
			if( !is_null( $datObj->PageOrder ) ) {
				$validator->checkType( $datObj->PageOrder, 'unsignedInt' );
			}
			$validator->leavePath();
		}
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
				$validator->checkType( $datObj->PageSequence, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Height' ) ) {
			$validator->enterPath( 'Height' );
			$validator->checkNull( $datObj->Height );
			if( !is_null( $datObj->Height ) ) {
				$validator->checkType( $datObj->Height, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Width' ) ) {
			$validator->enterPath( 'Width' );
			$validator->checkNull( $datObj->Width );
			if( !is_null( $datObj->Width ) ) {
				$validator->checkType( $datObj->Width, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ParentLayoutId' ) ) {
			$validator->enterPath( 'ParentLayoutId' );
			$validator->checkNull( $datObj->ParentLayoutId );
			if( !is_null( $datObj->ParentLayoutId ) ) {
				$validator->checkType( $datObj->ParentLayoutId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'OutputRenditionAvailable' ) ) {
			$validator->enterPath( 'OutputRenditionAvailable' );
			$validator->checkNull( $datObj->OutputRenditionAvailable );
			if( !is_null( $datObj->OutputRenditionAvailable ) ) {
				$validator->checkType( $datObj->OutputRenditionAvailable, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PlacementInfos' ) ) {
			$validator->enterPath( 'PlacementInfos' );
			$validator->checkNull( $datObj->PlacementInfos );
			if( !is_null( $datObj->PlacementInfos ) ) {
				$validator->checkType( $datObj->PlacementInfos, 'array' );
				if( !empty($datObj->PlacementInfos) ) foreach( $datObj->PlacementInfos as $listItem ) {
					$validator->enterPath( 'PlacementInfo' );
					$validator->checkType( $listItem, 'PlacementInfo' );
					WflPlacementInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
	}
}

class WflPlacementInfoValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
			}
			$validator->leavePath();
		}
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
		if( $validator->checkExist( $datObj, 'Width' ) ) {
			$validator->enterPath( 'Width' );
			$validator->checkNull( $datObj->Width );
			if( !is_null( $datObj->Width ) ) {
				$validator->checkType( $datObj->Width, 'float' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Height' ) ) {
			$validator->enterPath( 'Height' );
			$validator->checkNull( $datObj->Height );
			if( !is_null( $datObj->Height ) ) {
				$validator->checkType( $datObj->Height, 'float' );
			}
			$validator->leavePath();
		}
	}
}

class WflLayoutObjectValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
		if( $validator->checkExist( $datObj, 'Category' ) ) {
			$validator->enterPath( 'Category' );
			$validator->checkNull( $datObj->Category );
			if( !is_null( $datObj->Category ) ) {
				$validator->checkType( $datObj->Category, 'Category' );
				WflCategoryValidator::validate( $validator, $datObj->Category );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'State' ) ) {
			$validator->enterPath( 'State' );
			$validator->checkNull( $datObj->State );
			if( !is_null( $datObj->State ) ) {
				$validator->checkType( $datObj->State, 'State' );
				WflStateValidator::validate( $validator, $datObj->State );
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
		if( $validator->checkExist( $datObj, 'LockedBy' ) ) {
			$validator->enterPath( 'LockedBy' );
			$validator->checkNull( $datObj->LockedBy );
			if( !is_null( $datObj->LockedBy ) ) {
				$validator->checkType( $datObj->LockedBy, 'string' );
			}
			$validator->leavePath();
		}
	}
}

class WflPlacedObjectValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'Id' ) ) {
			$validator->enterPath( 'Id' );
			$validator->checkNull( $datObj->Id );
			if( !is_null( $datObj->Id ) ) {
				$validator->checkType( $datObj->Id, 'string' );
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
		if( $validator->checkExist( $datObj, 'State' ) ) {
			$validator->enterPath( 'State' );
			$validator->checkNull( $datObj->State );
			if( !is_null( $datObj->State ) ) {
				$validator->checkType( $datObj->State, 'State' );
				WflStateValidator::validate( $validator, $datObj->State );
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
		if( $validator->checkExist( $datObj, 'LockedBy' ) ) {
			$validator->enterPath( 'LockedBy' );
			$validator->checkNull( $datObj->LockedBy );
			if( !is_null( $datObj->LockedBy ) ) {
				$validator->checkType( $datObj->LockedBy, 'string' );
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

