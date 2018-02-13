<?php

/**
 * @package Enterprise
 * @subpackage Workflow Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR.'/speedtest/soap/SOAP_Attachment.class.php';

class Feature
{
	public $Key;
	public $Value;

	/**
	 * @param string               $Key                  
	 * @param string               $Value                Nullable.
	 */
	public function __construct( $Key=null, $Value=null)
	{
		$this->Key                  = $Key;
		$this->Value                = $Value;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflFeature'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class BasicMetaData
{
	public $ID;
	public $DocumentID;
	public $Name;
	public $Type;
	public $Publication;
	public $Category;
	public $ContentSource;

	/**
	 * @param string               $ID                   Nullable.
	 * @param string               $DocumentID           Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Type                 Nullable.
	 * @param Publication          $Publication          Nullable.
	 * @param Category             $Category             Nullable.
	 * @param string               $ContentSource        Nullable.
	 */
	public function __construct( $ID=null, $DocumentID=null, $Name=null, $Type=null, $Publication=null, $Category=null, $ContentSource=null)
	{
		$this->ID                   = $ID;
		$this->DocumentID           = $DocumentID;
		$this->Name                 = $Name;
		$this->Type                 = $Type;
		$this->Publication          = $Publication;
		$this->Category             = $Category;
		$this->ContentSource        = $ContentSource;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflBasicMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class RightsMetaData
{
	public $CopyrightMarked;
	public $Copyright;
	public $CopyrightURL;

	/**
	 * @param boolean              $CopyrightMarked      Nullable.
	 * @param string               $Copyright            Nullable.
	 * @param string               $CopyrightURL         Nullable.
	 */
	public function __construct( $CopyrightMarked=null, $Copyright=null, $CopyrightURL=null)
	{
		$this->CopyrightMarked      = $CopyrightMarked;
		$this->Copyright            = $Copyright;
		$this->CopyrightURL         = $CopyrightURL;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflRightsMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->CopyrightMarked)){ $this->CopyrightMarked = ('true' == $this->CopyrightMarked) ? true : false; }
	}
}
class SourceMetaData
{
	public $Credit;
	public $Source;
	public $Author;

	/**
	 * @param string               $Credit               Nullable.
	 * @param string               $Source               Nullable.
	 * @param string               $Author               Nullable.
	 */
	public function __construct( $Credit=null, $Source=null, $Author=null)
	{
		$this->Credit               = $Credit;
		$this->Source               = $Source;
		$this->Author               = $Author;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflSourceMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class ContentMetaData
{
	public $Description;
	public $DescriptionAuthor;
	public $Keywords;
	public $Slugline;
	public $Format;
	public $Columns;
	public $Width;
	public $Height;
	public $Dpi;
	public $LengthWords;
	public $LengthChars;
	public $LengthParas;
	public $LengthLines;
	public $PlainContent;
	public $FileSize;
	public $ColorSpace;
	public $HighResFile;
	public $Encoding;
	public $Compression;
	public $KeyFrameEveryFrames;
	public $Channels;
	public $AspectRatio;

	/**
	 * @param string               $Description          Nullable.
	 * @param string               $DescriptionAuthor    Nullable.
	 * @param string[]             $Keywords             Nullable.
	 * @param string               $Slugline             Nullable.
	 * @param string               $Format               Nullable.
	 * @param int                  $Columns              Nullable.
	 * @param float                $Width                Nullable.
	 * @param float                $Height               Nullable.
	 * @param integer              $Dpi                  Nullable.
	 * @param integer              $LengthWords          Nullable.
	 * @param integer              $LengthChars          Nullable.
	 * @param integer              $LengthParas          Nullable.
	 * @param integer              $LengthLines          Nullable.
	 * @param string               $PlainContent         Nullable.
	 * @param integer              $FileSize             Nullable.
	 * @param string               $ColorSpace           Nullable.
	 * @param string               $HighResFile          Nullable.
	 * @param string               $Encoding             Nullable.
	 * @param string               $Compression          Nullable.
	 * @param integer              $KeyFrameEveryFrames  Nullable.
	 * @param string               $Channels             Nullable.
	 * @param string               $AspectRatio          Nullable.
	 */
	public function __construct( $Description=null, $DescriptionAuthor=null, $Keywords=null, $Slugline=null, $Format=null, $Columns=null, $Width=null, $Height=null, $Dpi=null, $LengthWords=null, $LengthChars=null, $LengthParas=null, $LengthLines=null, $PlainContent=null, $FileSize=null, $ColorSpace=null, $HighResFile=null, $Encoding=null, $Compression=null, $KeyFrameEveryFrames=null, $Channels=null, $AspectRatio=null)
	{
		$this->Description          = $Description;
		$this->DescriptionAuthor    = $DescriptionAuthor;
		$this->Keywords             = $Keywords;
		$this->Slugline             = $Slugline;
		$this->Format               = $Format;
		$this->Columns              = $Columns;
		$this->Width                = $Width;
		$this->Height               = $Height;
		$this->Dpi                  = $Dpi;
		$this->LengthWords          = $LengthWords;
		$this->LengthChars          = $LengthChars;
		$this->LengthParas          = $LengthParas;
		$this->LengthLines          = $LengthLines;
		$this->PlainContent         = $PlainContent;
		$this->FileSize             = $FileSize;
		$this->ColorSpace           = $ColorSpace;
		$this->HighResFile          = $HighResFile;
		$this->Encoding             = $Encoding;
		$this->Compression          = $Compression;
		$this->KeyFrameEveryFrames  = $KeyFrameEveryFrames;
		$this->Channels             = $Channels;
		$this->AspectRatio          = $AspectRatio;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflContentMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Columns)){ $this->Columns = null; }
		if (is_nan($this->Width)){ $this->Width = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
		if (is_nan($this->Dpi)){ $this->Dpi = null; }
		if (is_nan($this->LengthWords)){ $this->LengthWords = null; }
		if (is_nan($this->LengthChars)){ $this->LengthChars = null; }
		if (is_nan($this->LengthParas)){ $this->LengthParas = null; }
		if (is_nan($this->LengthLines)){ $this->LengthLines = null; }
		if (is_nan($this->FileSize)){ $this->FileSize = null; }
		if (is_nan($this->KeyFrameEveryFrames)){ $this->KeyFrameEveryFrames = null; }
		if (0 < count($this->Keywords)){
			if (is_object($this->Keywords[0])){
				foreach ($this->Keywords as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class WorkflowMetaData
{
	public $Deadline;
	public $Urgency;
	public $Modifier;
	public $Modified;
	public $Creator;
	public $Created;
	public $Comment;
	public $State;
	public $RouteTo;
	public $LockedBy;
	public $Version;
	public $DeadlineSoft;
	public $Rating;
	public $Deletor;
	public $Deleted;

	/**
	 * @param string               $Deadline             Nullable.
	 * @param string               $Urgency              Nullable.
	 * @param string               $Modifier             Nullable.
	 * @param string               $Modified             Nullable.
	 * @param string               $Creator              Nullable.
	 * @param string               $Created              Nullable.
	 * @param string               $Comment              Nullable.
	 * @param State                $State                Nullable.
	 * @param string               $RouteTo              Nullable.
	 * @param string               $LockedBy             Nullable.
	 * @param string               $Version              Nullable.
	 * @param string               $DeadlineSoft         Nullable.
	 * @param integer              $Rating               Nullable.
	 * @param string               $Deletor              Nullable.
	 * @param string               $Deleted              Nullable.
	 */
	public function __construct( $Deadline=null, $Urgency=null, $Modifier=null, $Modified=null, $Creator=null, $Created=null, $Comment=null, $State=null, $RouteTo=null, $LockedBy=null, $Version=null, $DeadlineSoft=null, $Rating=null, $Deletor=null, $Deleted=null)
	{
		$this->Deadline             = $Deadline;
		$this->Urgency              = $Urgency;
		$this->Modifier             = $Modifier;
		$this->Modified             = $Modified;
		$this->Creator              = $Creator;
		$this->Created              = $Created;
		$this->Comment              = $Comment;
		$this->State                = $State;
		$this->RouteTo              = $RouteTo;
		$this->LockedBy             = $LockedBy;
		$this->Version              = $Version;
		$this->DeadlineSoft         = $DeadlineSoft;
		$this->Rating               = $Rating;
		$this->Deletor              = $Deletor;
		$this->Deleted              = $Deleted;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflWorkflowMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Rating)){ $this->Rating = null; }
	}
}
class ExtraMetaData
{
	public $Property;
	public $Values;

	/**
	 * @param string               $Property             
	 * @param string[]             $Values               
	 */
	public function __construct( $Property=null, $Values=null)
	{
		$this->Property             = $Property;
		$this->Values               = $Values;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflExtraMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Values)){
			if (is_object($this->Values[0])){
				foreach ($this->Values as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class PlacementTile
{
	public $PageSequence;
	public $Left;
	public $Top;
	public $Width;
	public $Height;

	/**
	 * @param integer              $PageSequence         
	 * @param float                $Left                 
	 * @param float                $Top                  
	 * @param float                $Width                
	 * @param float                $Height               
	 */
	public function __construct( $PageSequence=null, $Left=null, $Top=null, $Width=null, $Height=null)
	{
		$this->PageSequence         = $PageSequence;
		$this->Left                 = $Left;
		$this->Top                  = $Top;
		$this->Width                = $Width;
		$this->Height               = $Height;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPlacementTile'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PageSequence)){ $this->PageSequence = null; }
		if (is_nan($this->Left)){ $this->Left = null; }
		if (is_nan($this->Top)){ $this->Top = null; }
		if (is_nan($this->Width)){ $this->Width = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
	}
}
class Placement
{
	public $Page;
	public $Element;
	public $ElementID;
	public $FrameOrder;
	public $FrameID;
	public $Left;
	public $Top;
	public $Width;
	public $Height;
	public $Overset;
	public $OversetChars;
	public $OversetLines;
	public $Layer;
	public $Content;
	public $Edition;
	public $ContentDx;
	public $ContentDy;
	public $ScaleX;
	public $ScaleY;
	public $PageSequence;
	public $PageNumber;
	public $Tiles;
	public $FormWidgetId;

	/**
	 * @param integer              $Page                 Nullable.
	 * @param string               $Element              Nullable.
	 * @param string               $ElementID            
	 * @param integer              $FrameOrder           
	 * @param string               $FrameID              Nullable.
	 * @param float                $Left                 
	 * @param float                $Top                  
	 * @param float                $Width                
	 * @param float                $Height               
	 * @param float                $Overset              Nullable.
	 * @param int                  $OversetChars         Nullable.
	 * @param int                  $OversetLines         Nullable.
	 * @param string               $Layer                Nullable.
	 * @param string               $Content              Nullable.
	 * @param Edition              $Edition              Nullable.
	 * @param float                $ContentDx            Nullable.
	 * @param float                $ContentDy            Nullable.
	 * @param float                $ScaleX               Nullable.
	 * @param float                $ScaleY               Nullable.
	 * @param integer              $PageSequence         Nullable.
	 * @param string               $PageNumber           Nullable.
	 * @param PlacementTile[]      $Tiles                Nullable.
	 * @param string               $FormWidgetId         Nullable.
	 */
	public function __construct( $Page=null, $Element=null, $ElementID=null, $FrameOrder=null, $FrameID=null, $Left=null, $Top=null, $Width=null, $Height=null, $Overset=null, $OversetChars=null, $OversetLines=null, $Layer=null, $Content=null, $Edition=null, $ContentDx=null, $ContentDy=null, $ScaleX=null, $ScaleY=null, $PageSequence=null, $PageNumber=null, $Tiles=null, $FormWidgetId=null)
	{
		$this->Page                 = $Page;
		$this->Element              = $Element;
		$this->ElementID            = $ElementID;
		$this->FrameOrder           = $FrameOrder;
		$this->FrameID              = $FrameID;
		$this->Left                 = $Left;
		$this->Top                  = $Top;
		$this->Width                = $Width;
		$this->Height               = $Height;
		$this->Overset              = $Overset;
		$this->OversetChars         = $OversetChars;
		$this->OversetLines         = $OversetLines;
		$this->Layer                = $Layer;
		$this->Content              = $Content;
		$this->Edition              = $Edition;
		$this->ContentDx            = $ContentDx;
		$this->ContentDy            = $ContentDy;
		$this->ScaleX               = $ScaleX;
		$this->ScaleY               = $ScaleY;
		$this->PageSequence         = $PageSequence;
		$this->PageNumber           = $PageNumber;
		$this->Tiles                = $Tiles;
		$this->FormWidgetId         = $FormWidgetId;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPlacement'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Page)){ $this->Page = null; }
		if (is_nan($this->FrameOrder)){ $this->FrameOrder = null; }
		if (is_nan($this->Left)){ $this->Left = null; }
		if (is_nan($this->Top)){ $this->Top = null; }
		if (is_nan($this->Width)){ $this->Width = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
		if (is_nan($this->Overset)){ $this->Overset = null; }
		if (is_nan($this->OversetChars)){ $this->OversetChars = null; }
		if (is_nan($this->OversetLines)){ $this->OversetLines = null; }
		if (is_nan($this->ContentDx)){ $this->ContentDx = null; }
		if (is_nan($this->ContentDy)){ $this->ContentDy = null; }
		if (is_nan($this->ScaleX)){ $this->ScaleX = null; }
		if (is_nan($this->ScaleY)){ $this->ScaleY = null; }
		if (is_nan($this->PageSequence)){ $this->PageSequence = null; }
		if (0 < count($this->Tiles)){
			if (is_object($this->Tiles[0])){
				foreach ($this->Tiles as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Element
{
	public $ID;
	public $Name;
	public $LengthWords;
	public $LengthChars;
	public $LengthParas;
	public $LengthLines;
	public $Snippet;
	public $Version;
	public $Content;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 Nullable.
	 * @param integer              $LengthWords          Nullable.
	 * @param integer              $LengthChars          Nullable.
	 * @param integer              $LengthParas          Nullable.
	 * @param integer              $LengthLines          Nullable.
	 * @param string               $Snippet              Nullable.
	 * @param string               $Version              Nullable.
	 * @param string               $Content              Nullable.
	 */
	public function __construct( $ID=null, $Name=null, $LengthWords=null, $LengthChars=null, $LengthParas=null, $LengthLines=null, $Snippet=null, $Version=null, $Content=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->LengthWords          = $LengthWords;
		$this->LengthChars          = $LengthChars;
		$this->LengthParas          = $LengthParas;
		$this->LengthLines          = $LengthLines;
		$this->Snippet              = $Snippet;
		$this->Version              = $Version;
		$this->Content              = $Content;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflElement'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->LengthWords)){ $this->LengthWords = null; }
		if (is_nan($this->LengthChars)){ $this->LengthChars = null; }
		if (is_nan($this->LengthParas)){ $this->LengthParas = null; }
		if (is_nan($this->LengthLines)){ $this->LengthLines = null; }
	}
}
class Relation
{
	public $Parent;
	public $Child;
	public $Type;
	public $Placements;
	public $ParentVersion;
	public $ChildVersion;
	public $Geometry;
	public $Rating;
	public $Targets;
	public $ParentInfo;
	public $ChildInfo;
	public $ObjectLabels;

	/**
	 * @param string               $Parent               
	 * @param string               $Child                Nullable.
	 * @param string               $Type                 
	 * @param Placement[]          $Placements           Nullable.
	 * @param string               $ParentVersion        Nullable.
	 * @param string               $ChildVersion         Nullable.
	 * @param Attachment           $Geometry             Nullable.
	 * @param integer              $Rating               Nullable.
	 * @param Target[]             $Targets              Nullable.
	 * @param ObjectInfo           $ParentInfo           Nullable.
	 * @param ObjectInfo           $ChildInfo            Nullable.
	 * @param ObjectLabel[]        $ObjectLabels         Nullable.
	 */
	public function __construct( $Parent=null, $Child=null, $Type=null, $Placements=null, $ParentVersion=null, $ChildVersion=null, $Geometry=null, $Rating=null, $Targets=null, $ParentInfo=null, $ChildInfo=null, $ObjectLabels=null)
	{
		$this->Parent               = $Parent;
		$this->Child                = $Child;
		$this->Type                 = $Type;
		$this->Placements           = $Placements;
		$this->ParentVersion        = $ParentVersion;
		$this->ChildVersion         = $ChildVersion;
		$this->Rating               = $Rating;
		$this->Targets              = $Targets;
		$this->ParentInfo           = $ParentInfo;
		$this->ChildInfo            = $ChildInfo;
		$this->ObjectLabels         = $ObjectLabels;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflRelation'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Rating)){ $this->Rating = null; }
		if (0 < count($this->Placements)){
			if (is_object($this->Placements[0])){
				foreach ($this->Placements as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Targets)){
			if (is_object($this->Targets[0])){
				foreach ($this->Targets as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->ObjectLabels)){
			if (is_object($this->ObjectLabels[0])){
				foreach ($this->ObjectLabels as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class StickyInfo
{
	public $AnchorX;
	public $AnchorY;
	public $Left;
	public $Top;
	public $Width;
	public $Height;
	public $Page;
	public $Version;
	public $Color;
	public $PageSequence;

	/**
	 * @param float                $AnchorX              Nullable.
	 * @param float                $AnchorY              Nullable.
	 * @param float                $Left                 Nullable.
	 * @param float                $Top                  Nullable.
	 * @param float                $Width                Nullable.
	 * @param float                $Height               Nullable.
	 * @param integer              $Page                 Nullable.
	 * @param string               $Version              Nullable.
	 * @param string               $Color                Nullable.
	 * @param integer              $PageSequence         Nullable.
	 */
	public function __construct( $AnchorX=null, $AnchorY=null, $Left=null, $Top=null, $Width=null, $Height=null, $Page=null, $Version=null, $Color=null, $PageSequence=null)
	{
		$this->AnchorX              = $AnchorX;
		$this->AnchorY              = $AnchorY;
		$this->Left                 = $Left;
		$this->Top                  = $Top;
		$this->Width                = $Width;
		$this->Height               = $Height;
		$this->Page                 = $Page;
		$this->Version              = $Version;
		$this->Color                = $Color;
		$this->PageSequence         = $PageSequence;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflStickyInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->AnchorX)){ $this->AnchorX = null; }
		if (is_nan($this->AnchorY)){ $this->AnchorY = null; }
		if (is_nan($this->Left)){ $this->Left = null; }
		if (is_nan($this->Top)){ $this->Top = null; }
		if (is_nan($this->Width)){ $this->Width = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
		if (is_nan($this->Page)){ $this->Page = null; }
		if (is_nan($this->PageSequence)){ $this->PageSequence = null; }
	}
}
class Setting
{
	public $Setting;
	public $Value;

	/**
	 * @param string               $Setting              
	 * @param string               $Value                
	 */
	public function __construct( $Setting=null, $Value=null)
	{
		$this->Setting              = $Setting;
		$this->Value                = $Value;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflSetting'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class Message
{
	public $ObjectID;
	public $UserID;
	public $MessageID;
	public $MessageType;
	public $MessageTypeDetail;
	public $Message;
	public $TimeStamp;
	public $Expiration;
	public $MessageLevel;
	public $FromUser;
	public $StickyInfo;
	public $ThreadMessageID;
	public $ReplyToMessageID;
	public $MessageStatus;
	public $ObjectVersion;

	/**
	 * @param string               $ObjectID             Nullable.
	 * @param string               $UserID               Nullable.
	 * @param string               $MessageID            Nullable.
	 * @param string               $MessageType          
	 * @param string               $MessageTypeDetail    
	 * @param string               $Message              Nullable.
	 * @param string               $TimeStamp            Nullable.
	 * @param string               $Expiration           Nullable.
	 * @param string               $MessageLevel         Nullable.
	 * @param string               $FromUser             Nullable.
	 * @param StickyInfo           $StickyInfo           Nullable.
	 * @param string               $ThreadMessageID      Nullable.
	 * @param string               $ReplyToMessageID     Nullable.
	 * @param string               $MessageStatus        Nullable.
	 * @param string               $ObjectVersion        Nullable.
	 */
	public function __construct( $ObjectID=null, $UserID=null, $MessageID=null, $MessageType=null, $MessageTypeDetail=null, $Message=null, $TimeStamp=null, $Expiration=null, $MessageLevel=null, $FromUser=null, $StickyInfo=null, $ThreadMessageID=null, $ReplyToMessageID=null, $MessageStatus=null, $ObjectVersion=null)
	{
		$this->ObjectID             = $ObjectID;
		$this->UserID               = $UserID;
		$this->MessageID            = $MessageID;
		$this->MessageType          = $MessageType;
		$this->MessageTypeDetail    = $MessageTypeDetail;
		$this->Message              = $Message;
		$this->TimeStamp            = $TimeStamp;
		$this->Expiration           = $Expiration;
		$this->MessageLevel         = $MessageLevel;
		$this->FromUser             = $FromUser;
		$this->StickyInfo           = $StickyInfo;
		$this->ThreadMessageID      = $ThreadMessageID;
		$this->ReplyToMessageID     = $ReplyToMessageID;
		$this->MessageStatus        = $MessageStatus;
		$this->ObjectVersion        = $ObjectVersion;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflMessage'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class MessageList
{
	public $Messages;
	public $ReadMessageIDs;
	public $DeleteMessageIDs;

	/**
	 * @param Message[]            $Messages             Nullable.
	 * @param string[]             $ReadMessageIDs       Nullable.
	 * @param string[]             $DeleteMessageIDs     Nullable.
	 */
	public function __construct( $Messages=null, $ReadMessageIDs=null, $DeleteMessageIDs=null)
	{
		$this->Messages             = $Messages;
		$this->ReadMessageIDs       = $ReadMessageIDs;
		$this->DeleteMessageIDs     = $DeleteMessageIDs;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflMessageList'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Messages)){
			if (is_object($this->Messages[0])){
				foreach ($this->Messages as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->ReadMessageIDs)){
			if (is_object($this->ReadMessageIDs[0])){
				foreach ($this->ReadMessageIDs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->DeleteMessageIDs)){
			if (is_object($this->DeleteMessageIDs[0])){
				foreach ($this->DeleteMessageIDs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Term
{
	public $Term;
	public $Translation;

	/**
	 * @param string               $Term                 
	 * @param string               $Translation          
	 */
	public function __construct( $Term=null, $Translation=null)
	{
		$this->Term                 = $Term;
		$this->Translation          = $Translation;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflTerm'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class User
{
	public $UserID;
	public $FullName;
	public $TrackChangesColor;

	/**
	 * @param string               $UserID               
	 * @param string               $FullName             
	 * @param string               $TrackChangesColor    
	 */
	public function __construct( $UserID=null, $FullName=null, $TrackChangesColor=null)
	{
		$this->UserID               = $UserID;
		$this->FullName             = $FullName;
		$this->TrackChangesColor    = $TrackChangesColor;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflUser'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class UserGroup
{
	public $Name;

	/**
	 * @param string               $Name                 
	 */
	public function __construct( $Name=null)
	{
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflUserGroup'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class ServerInfo
{
	public $Name;
	public $URL;
	public $Developer;
	public $Implementation;
	public $Technology;
	public $Version;
	public $FeatureSet;
	public $CryptKey;
	public $EnterpriseSystemId;

	/**
	 * @param string               $Name                 
	 * @param string               $URL                  
	 * @param string               $Developer            
	 * @param string               $Implementation       
	 * @param string               $Technology           
	 * @param string               $Version              
	 * @param Feature[]            $FeatureSet           
	 * @param string               $CryptKey             Nullable.
	 * @param string               $EnterpriseSystemId   Nullable.
	 */
	public function __construct( $Name=null, $URL=null, $Developer=null, $Implementation=null, $Technology=null, $Version=null, $FeatureSet=null, $CryptKey=null, $EnterpriseSystemId=null)
	{
		$this->Name                 = $Name;
		$this->URL                  = $URL;
		$this->Developer            = $Developer;
		$this->Implementation       = $Implementation;
		$this->Technology           = $Technology;
		$this->Version              = $Version;
		$this->FeatureSet           = $FeatureSet;
		$this->CryptKey             = $CryptKey;
		$this->EnterpriseSystemId   = $EnterpriseSystemId;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflServerInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->FeatureSet)){
			if (is_object($this->FeatureSet[0])){
				foreach ($this->FeatureSet as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class MetaData
{
	public $BasicMetaData;
	public $RightsMetaData;
	public $SourceMetaData;
	public $ContentMetaData;
	public $WorkflowMetaData;
	public $ExtraMetaData;

	/**
	 * @param BasicMetaData        $BasicMetaData        Nullable.
	 * @param RightsMetaData       $RightsMetaData       Nullable.
	 * @param SourceMetaData       $SourceMetaData       Nullable.
	 * @param ContentMetaData      $ContentMetaData      Nullable.
	 * @param WorkflowMetaData     $WorkflowMetaData     Nullable.
	 * @param ExtraMetaData[]      $ExtraMetaData        Nullable.
	 */
	public function __construct( $BasicMetaData=null, $RightsMetaData=null, $SourceMetaData=null, $ContentMetaData=null, $WorkflowMetaData=null, $ExtraMetaData=null)
	{
		$this->BasicMetaData        = $BasicMetaData;
		$this->RightsMetaData       = $RightsMetaData;
		$this->SourceMetaData       = $SourceMetaData;
		$this->ContentMetaData      = $ContentMetaData;
		$this->WorkflowMetaData     = $WorkflowMetaData;
		$this->ExtraMetaData        = $ExtraMetaData;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->ExtraMetaData)){
			if (is_object($this->ExtraMetaData[0])){
				foreach ($this->ExtraMetaData as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Page
{
	public $Width;
	public $Height;
	public $PageNumber;
	public $PageOrder;
	public $Files;
	public $Edition;
	public $Master;
	public $Instance;
	public $PageSequence;
	public $Renditions;
	public $Orientation;

	/**
	 * @param float                $Width                
	 * @param float                $Height               
	 * @param string               $PageNumber           
	 * @param integer              $PageOrder            
	 * @param Attachment[]         $Files                Nullable.
	 * @param Edition              $Edition              Nullable.
	 * @param string               $Master               
	 * @param string               $Instance             
	 * @param integer              $PageSequence         Nullable.
	 * @param string[]             $Renditions           Nullable.
	 * @param string               $Orientation          Nullable.
	 */
	public function __construct( $Width=null, $Height=null, $PageNumber=null, $PageOrder=null, $Files=null, $Edition=null, $Master=null, $Instance=null, $PageSequence=null, $Renditions=null, $Orientation=null)
	{
		$this->Width                = $Width;
		$this->Height               = $Height;
		$this->PageNumber           = $PageNumber;
		$this->PageOrder            = $PageOrder;
		$this->Files                = $Files;
		$this->Edition              = $Edition;
		$this->Master               = $Master;
		$this->Instance             = $Instance;
		$this->PageSequence         = $PageSequence;
		$this->Renditions           = $Renditions;
		$this->Orientation          = $Orientation;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPage'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Width)){ $this->Width = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
		if (is_nan($this->PageOrder)){ $this->PageOrder = null; }
		if (is_nan($this->PageSequence)){ $this->PageSequence = null; }
		if (0 < count($this->Files)){
			if (is_object($this->Files[0])){
				foreach ($this->Files as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Renditions)){
			if (is_object($this->Renditions[0])){
				foreach ($this->Renditions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Object
{
	public $MetaData;
	public $Relations;
	public $Pages;
	public $Files;
	public $Messages;
	public $Elements;
	public $Targets;
	public $Renditions;
	public $MessageList;
	public $ObjectLabels;

	/**
	 * @param MetaData             $MetaData             Nullable.
	 * @param Relation[]           $Relations            Nullable.
	 * @param Page[]               $Pages                Nullable.
	 * @param Attachment[]         $Files                Nullable.
	 * @param Message[]            $Messages             Nullable.
	 * @param Element[]            $Elements             Nullable.
	 * @param Target[]             $Targets              Nullable.
	 * @param EditionRenditionsInfo[] $Renditions           Nullable.
	 * @param MessageList          $MessageList          Nullable.
	 * @param ObjectLabel[]        $ObjectLabels         Nullable.
	 */
	public function __construct( $MetaData=null, $Relations=null, $Pages=null, $Files=null, $Messages=null, $Elements=null, $Targets=null, $Renditions=null, $MessageList=null, $ObjectLabels=null)
	{
		$this->MetaData             = $MetaData;
		$this->Relations            = $Relations;
		$this->Pages                = $Pages;
		$this->Files                = $Files;
		$this->Messages             = $Messages;
		$this->Elements             = $Elements;
		$this->Targets              = $Targets;
		$this->Renditions           = $Renditions;
		$this->MessageList          = $MessageList;
		$this->ObjectLabels         = $ObjectLabels;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflObject'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Relations)){
			if (is_object($this->Relations[0])){
				foreach ($this->Relations as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Pages)){
			if (is_object($this->Pages[0])){
				foreach ($this->Pages as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Files)){
			if (is_object($this->Files[0])){
				foreach ($this->Files as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Messages)){
			if (is_object($this->Messages[0])){
				foreach ($this->Messages as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Elements)){
			if (is_object($this->Elements[0])){
				foreach ($this->Elements as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Targets)){
			if (is_object($this->Targets[0])){
				foreach ($this->Targets as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Renditions)){
			if (is_object($this->Renditions[0])){
				foreach ($this->Renditions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->ObjectLabels)){
			if (is_object($this->ObjectLabels[0])){
				foreach ($this->ObjectLabels as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class NamedQueryType
{
	public $Name;
	public $Params;

	/**
	 * @param string               $Name                 
	 * @param PropertyInfo[]       $Params               
	 */
	public function __construct( $Name=null, $Params=null)
	{
		$this->Name                 = $Name;
		$this->Params               = $Params;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflNamedQueryType'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Params)){
			if (is_object($this->Params[0])){
				foreach ($this->Params as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Property
{
	public $Name;
	public $DisplayName;
	public $Type;

	/**
	 * @param string               $Name                 
	 * @param string               $DisplayName          
	 * @param string               $Type                 
	 */
	public function __construct( $Name=null, $DisplayName=null, $Type=null)
	{
		$this->Name                 = $Name;
		$this->DisplayName          = $DisplayName;
		$this->Type                 = $Type;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflProperty'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class Facet
{
	public $Name;
	public $DisplayName;
	public $FacetItems;

	/**
	 * @param string               $Name                 
	 * @param string               $DisplayName          
	 * @param FacetItem[]          $FacetItems           
	 */
	public function __construct( $Name=null, $DisplayName=null, $FacetItems=null)
	{
		$this->Name                 = $Name;
		$this->DisplayName          = $DisplayName;
		$this->FacetItems           = $FacetItems;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflFacet'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->FacetItems)){
			if (is_object($this->FacetItems[0])){
				foreach ($this->FacetItems as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class FacetItem
{
	public $Name;
	public $DisplayName;
	public $Number;

	/**
	 * @param string               $Name                 
	 * @param string               $DisplayName          
	 * @param integer              $Number               
	 */
	public function __construct( $Name=null, $DisplayName=null, $Number=null)
	{
		$this->Name                 = $Name;
		$this->DisplayName          = $DisplayName;
		$this->Number               = $Number;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflFacetItem'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Number)){ $this->Number = null; }
	}
}
class PropertyInfo
{
	public $Name;
	public $DisplayName;
	public $Category;
	public $Type;
	public $DefaultValue;
	public $ValueList;
	public $MinValue;
	public $MaxValue;
	public $MaxLength;
	public $PropertyValues;
	public $ParentValue;
	public $DependentProperties;
	public $MinResolution;
	public $MaxResolution;
	public $Widgets;
	public $TermEntity;
	public $SuggestionEntity;
	public $AutocompleteProvider;
	public $SuggestionProvider;
	public $PublishSystemId;
	public $Notifications;
	public $MixedValues;

	/**
	 * @param string               $Name                 
	 * @param string               $DisplayName          
	 * @param string               $Category             Nullable.
	 * @param string               $Type                 
	 * @param string               $DefaultValue         Nullable.
	 * @param string[]             $ValueList            Nullable.
	 * @param string               $MinValue             Nullable.
	 * @param string               $MaxValue             Nullable.
	 * @param int                  $MaxLength            Nullable.
	 * @param PropertyValue[]      $PropertyValues       Nullable.
	 * @param string               $ParentValue          Nullable.
	 * @param Property[]           $DependentProperties  Nullable.
	 * @param string               $MinResolution        Nullable.
	 * @param string               $MaxResolution        Nullable.
	 * @param DialogWidget[]       $Widgets              Nullable.
	 * @param string               $TermEntity           Nullable.
	 * @param string               $SuggestionEntity     Nullable.
	 * @param string               $AutocompleteProvider Nullable.
	 * @param string               $SuggestionProvider   Nullable.
	 * @param string               $PublishSystemId      Nullable.
	 * @param PropertyNotification[] $Notifications        Nullable.
	 * @param boolean              $MixedValues          Nullable.
	 */
	public function __construct( $Name=null, $DisplayName=null, $Category=null, $Type=null, $DefaultValue=null, $ValueList=null, $MinValue=null, $MaxValue=null, $MaxLength=null, $PropertyValues=null, $ParentValue=null, $DependentProperties=null, $MinResolution=null, $MaxResolution=null, $Widgets=null, $TermEntity=null, $SuggestionEntity=null, $AutocompleteProvider=null, $SuggestionProvider=null, $PublishSystemId=null, $Notifications=null, $MixedValues=null)
	{
		$this->Name                 = $Name;
		$this->DisplayName          = $DisplayName;
		$this->Category             = $Category;
		$this->Type                 = $Type;
		$this->DefaultValue         = $DefaultValue;
		$this->ValueList            = $ValueList;
		$this->MinValue             = $MinValue;
		$this->MaxValue             = $MaxValue;
		$this->MaxLength            = $MaxLength;
		$this->PropertyValues       = $PropertyValues;
		$this->ParentValue          = $ParentValue;
		$this->DependentProperties  = $DependentProperties;
		$this->MinResolution        = $MinResolution;
		$this->MaxResolution        = $MaxResolution;
		$this->Widgets              = $Widgets;
		$this->TermEntity           = $TermEntity;
		$this->SuggestionEntity     = $SuggestionEntity;
		$this->AutocompleteProvider = $AutocompleteProvider;
		$this->SuggestionProvider   = $SuggestionProvider;
		$this->PublishSystemId      = $PublishSystemId;
		$this->Notifications        = $Notifications;
		$this->MixedValues          = $MixedValues;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPropertyInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->MaxLength)){ $this->MaxLength = null; }
		if (!is_null($this->MixedValues)){ $this->MixedValues = ('true' == $this->MixedValues) ? true : false; }
		if (0 < count($this->ValueList)){
			if (is_object($this->ValueList[0])){
				foreach ($this->ValueList as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->PropertyValues)){
			if (is_object($this->PropertyValues[0])){
				foreach ($this->PropertyValues as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->DependentProperties)){
			if (is_object($this->DependentProperties[0])){
				foreach ($this->DependentProperties as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Widgets)){
			if (is_object($this->Widgets[0])){
				foreach ($this->Widgets as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Notifications)){
			if (is_object($this->Notifications[0])){
				foreach ($this->Notifications as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class PropertyUsage
{
	public $Name;
	public $Editable;
	public $Mandatory;
	public $Restricted;
	public $RefreshOnChange;
	public $InitialHeight;
	public $MultipleObjects;

	/**
	 * @param string               $Name                 
	 * @param boolean              $Editable             
	 * @param boolean              $Mandatory            
	 * @param boolean              $Restricted           
	 * @param boolean              $RefreshOnChange      
	 * @param integer              $InitialHeight        Nullable.
	 * @param boolean              $MultipleObjects      Nullable.
	 */
	public function __construct( $Name=null, $Editable=null, $Mandatory=null, $Restricted=null, $RefreshOnChange=null, $InitialHeight=null, $MultipleObjects=null)
	{
		$this->Name                 = $Name;
		$this->Editable             = $Editable;
		$this->Mandatory            = $Mandatory;
		$this->Restricted           = $Restricted;
		$this->RefreshOnChange      = $RefreshOnChange;
		$this->InitialHeight        = $InitialHeight;
		$this->MultipleObjects      = $MultipleObjects;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPropertyUsage'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->InitialHeight)){ $this->InitialHeight = null; }
		if (!is_null($this->Editable)){ $this->Editable = ('true' == $this->Editable) ? true : false; }
		if (!is_null($this->Mandatory)){ $this->Mandatory = ('true' == $this->Mandatory) ? true : false; }
		if (!is_null($this->Restricted)){ $this->Restricted = ('true' == $this->Restricted) ? true : false; }
		if (!is_null($this->RefreshOnChange)){ $this->RefreshOnChange = ('true' == $this->RefreshOnChange) ? true : false; }
		if (!is_null($this->MultipleObjects)){ $this->MultipleObjects = ('true' == $this->MultipleObjects) ? true : false; }
	}
}
class ActionProperty
{
	public $Action;
	public $ObjectType;
	public $Properties;

	/**
	 * @param string               $Action               
	 * @param string               $ObjectType           Nullable.
	 * @param PropertyUsage[]      $Properties           
	 */
	public function __construct( $Action=null, $ObjectType=null, $Properties=null)
	{
		$this->Action               = $Action;
		$this->ObjectType           = $ObjectType;
		$this->Properties           = $Properties;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflActionProperty'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Properties)){
			if (is_object($this->Properties[0])){
				foreach ($this->Properties as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class ObjectTypeProperty
{
	public $Type;
	public $Properties;

	/**
	 * @param string               $Type                 Nullable.
	 * @param PropertyInfo[]       $Properties           
	 */
	public function __construct( $Type=null, $Properties=null)
	{
		$this->Type                 = $Type;
		$this->Properties           = $Properties;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflObjectTypeProperty'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Properties)){
			if (is_object($this->Properties[0])){
				foreach ($this->Properties as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class QueryOrder
{
	public $Property;
	public $Direction;

	/**
	 * @param string               $Property             
	 * @param boolean              $Direction            
	 */
	public function __construct( $Property=null, $Direction=null)
	{
		$this->Property             = $Property;
		$this->Direction            = $Direction;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflQueryOrder'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Direction)){ $this->Direction = ('true' == $this->Direction) ? true : false; }
	}
}
class QueryParam
{
	public $Property;
	public $Operation;
	public $Value;
	public $Special;
	public $Value2;

	/**
	 * @param string               $Property             
	 * @param string               $Operation            
	 * @param string               $Value                
	 * @param boolean              $Special              Nullable.
	 * @param string               $Value2               Nullable.
	 */
	public function __construct( $Property=null, $Operation=null, $Value=null, $Special=null, $Value2=null)
	{
		$this->Property             = $Property;
		$this->Operation            = $Operation;
		$this->Value                = $Value;
		$this->Special              = $Special;
		$this->Value2               = $Value2;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflQueryParam'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Special)){ $this->Special = ('true' == $this->Special) ? true : false; }
	}
}
class VersionInfo
{
	public $Version;
	public $User;
	public $Comment;
	public $Slugline;
	public $Created;
	public $Object;
	public $State;
	public $File;

	/**
	 * @param string               $Version              
	 * @param string               $User                 
	 * @param string               $Comment              
	 * @param string               $Slugline             
	 * @param string               $Created              
	 * @param string               $Object               
	 * @param State                $State                
	 * @param Attachment           $File                 Nullable.
	 */
	public function __construct( $Version=null, $User=null, $Comment=null, $Slugline=null, $Created=null, $Object=null, $State=null, $File=null)
	{
		$this->Version              = $Version;
		$this->User                 = $User;
		$this->Comment              = $Comment;
		$this->Slugline             = $Slugline;
		$this->Created              = $Created;
		$this->Object               = $Object;
		$this->State                = $State;
		$this->File                 = $File;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflVersionInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class Attachment
{
	public $Rendition;
	public $Type;
	public $Content;
	public $FilePath;
	public $FileUrl;
	public $EditionId;

	/**
	 * @param string               $Rendition            
	 * @param string               $Type                 
	 * @param AttachmentContent    $Content              Nullable.
	 * @param string               $FilePath             Nullable.
	 * @param string               $FileUrl              Nullable.
	 * @param string               $EditionId            Nullable.
	 */
	public function __construct( $Rendition=null, $Type=null, $Content=null, $FilePath=null, $FileUrl=null, $EditionId=null)
	{
		$this->Rendition            = $Rendition;
		$this->Type                 = $Type;
		$this->Content              = $Content;
		$this->FilePath             = $FilePath;
		$this->FileUrl              = $FileUrl;
		$this->EditionId            = $EditionId;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflAttachment'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class RenditionTypeInfo
{
	public $Rendition;
	public $Type;

	/**
	 * @param string               $Rendition            
	 * @param string               $Type                 
	 */
	public function __construct( $Rendition=null, $Type=null)
	{
		$this->Rendition            = $Rendition;
		$this->Type                 = $Type;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflRenditionTypeInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class EditionRenditionsInfo
{
	public $Edition;
	public $Renditions;

	/**
	 * @param Edition              $Edition              Nullable.
	 * @param RenditionTypeInfo[]  $Renditions           
	 */
	public function __construct( $Edition=null, $Renditions=null)
	{
		$this->Edition              = $Edition;
		$this->Renditions           = $Renditions;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflEditionRenditionsInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Renditions)){
			if (is_object($this->Renditions[0])){
				foreach ($this->Renditions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Publication
{
	public $Id;
	public $Name;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 */
	public function __construct( $Id=null, $Name=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPublication'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PublicationInfo
{
	public $Id;
	public $Name;
	public $Issues;
	public $States;
	public $ObjectTypeProperties;
	public $ActionProperties;
	public $Editions;
	public $FeatureAccessList;
	public $CurrentIssue;
	public $PubChannels;
	public $Categories;
	public $Dictionaries;
	public $ReversedRead;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 * @param IssueInfo[]          $Issues               Nullable.
	 * @param State[]              $States               Nullable.
	 * @param ObjectTypeProperty[] $ObjectTypeProperties Nullable.
	 * @param ActionProperty[]     $ActionProperties     Nullable.
	 * @param Edition[]            $Editions             Nullable.
	 * @param FeatureAccess[]      $FeatureAccessList    Nullable.
	 * @param string               $CurrentIssue         Nullable.
	 * @param PubChannelInfo[]     $PubChannels          Nullable.
	 * @param CategoryInfo[]       $Categories           Nullable.
	 * @param Dictionary[]         $Dictionaries         Nullable.
	 * @param boolean              $ReversedRead         
	 */
	public function __construct( $Id=null, $Name=null, $Issues=null, $States=null, $ObjectTypeProperties=null, $ActionProperties=null, $Editions=null, $FeatureAccessList=null, $CurrentIssue=null, $PubChannels=null, $Categories=null, $Dictionaries=null, $ReversedRead=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Issues               = $Issues;
		$this->States               = $States;
		$this->ObjectTypeProperties = $ObjectTypeProperties;
		$this->ActionProperties     = $ActionProperties;
		$this->Editions             = $Editions;
		$this->FeatureAccessList    = $FeatureAccessList;
		$this->CurrentIssue         = $CurrentIssue;
		$this->PubChannels          = $PubChannels;
		$this->Categories           = $Categories;
		$this->Dictionaries         = $Dictionaries;
		$this->ReversedRead         = $ReversedRead;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPublicationInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->ReversedRead)){ $this->ReversedRead = ('true' == $this->ReversedRead) ? true : false; }
		if (0 < count($this->Issues)){
			if (is_object($this->Issues[0])){
				foreach ($this->Issues as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->States)){
			if (is_object($this->States[0])){
				foreach ($this->States as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->ObjectTypeProperties)){
			if (is_object($this->ObjectTypeProperties[0])){
				foreach ($this->ObjectTypeProperties as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->ActionProperties)){
			if (is_object($this->ActionProperties[0])){
				foreach ($this->ActionProperties as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->FeatureAccessList)){
			if (is_object($this->FeatureAccessList[0])){
				foreach ($this->FeatureAccessList as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->PubChannels)){
			if (is_object($this->PubChannels[0])){
				foreach ($this->PubChannels as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Categories)){
			if (is_object($this->Categories[0])){
				foreach ($this->Categories as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Dictionaries)){
			if (is_object($this->Dictionaries[0])){
				foreach ($this->Dictionaries as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Category
{
	public $Id;
	public $Name;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 */
	public function __construct( $Id=null, $Name=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflCategory'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class CategoryInfo
{
	public $Id;
	public $Name;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 */
	public function __construct( $Id=null, $Name=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflCategoryInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PubChannel
{
	public $Id;
	public $Name;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 */
	public function __construct( $Id=null, $Name=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPubChannel'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PubChannelInfo
{
	public $Id;
	public $Name;
	public $Issues;
	public $Editions;
	public $CurrentIssue;
	public $Type;
	public $DirectPublish;
	public $SupportsForms;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 * @param IssueInfo[]          $Issues               Nullable.
	 * @param Edition[]            $Editions             Nullable.
	 * @param string               $CurrentIssue         Nullable.
	 * @param string               $Type                 Nullable.
	 * @param boolean              $DirectPublish        Nullable.
	 * @param boolean              $SupportsForms        Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Issues=null, $Editions=null, $CurrentIssue=null, $Type=null, $DirectPublish=null, $SupportsForms=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Issues               = $Issues;
		$this->Editions             = $Editions;
		$this->CurrentIssue         = $CurrentIssue;
		$this->Type                 = $Type;
		$this->DirectPublish        = $DirectPublish;
		$this->SupportsForms        = $SupportsForms;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPubChannelInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->DirectPublish)){ $this->DirectPublish = ('true' == $this->DirectPublish) ? true : false; }
		if (!is_null($this->SupportsForms)){ $this->SupportsForms = ('true' == $this->SupportsForms) ? true : false; }
		if (0 < count($this->Issues)){
			if (is_object($this->Issues[0])){
				foreach ($this->Issues as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Edition
{
	public $Id;
	public $Name;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 */
	public function __construct( $Id=null, $Name=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflEdition'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class Issue
{
	public $Id;
	public $Name;
	public $OverrulePublication;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 * @param boolean              $OverrulePublication  Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $OverrulePublication=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->OverrulePublication  = $OverrulePublication;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflIssue'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->OverrulePublication)){ $this->OverrulePublication = ('true' == $this->OverrulePublication) ? true : false; }
	}
}
class IssueInfo
{
	public $Id;
	public $Name;
	public $OverrulePublication;
	public $Sections;
	public $States;
	public $Editions;
	public $Description;
	public $Subject;
	public $PublicationDate;
	public $ReversedRead;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 * @param boolean              $OverrulePublication  Nullable.
	 * @param CategoryInfo[]       $Sections             Nullable.
	 * @param State[]              $States               Nullable.
	 * @param Edition[]            $Editions             Nullable.
	 * @param string               $Description          Nullable.
	 * @param string               $Subject              Nullable.
	 * @param string               $PublicationDate      Nullable.
	 * @param boolean              $ReversedRead         Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $OverrulePublication=null, $Sections=null, $States=null, $Editions=null, $Description=null, $Subject=null, $PublicationDate=null, $ReversedRead=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->OverrulePublication  = $OverrulePublication;
		$this->Sections             = $Sections;
		$this->States               = $States;
		$this->Editions             = $Editions;
		$this->Description          = $Description;
		$this->Subject              = $Subject;
		$this->PublicationDate      = $PublicationDate;
		$this->ReversedRead         = $ReversedRead;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflIssueInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->OverrulePublication)){ $this->OverrulePublication = ('true' == $this->OverrulePublication) ? true : false; }
		if (!is_null($this->ReversedRead)){ $this->ReversedRead = ('true' == $this->ReversedRead) ? true : false; }
		if (0 < count($this->Sections)){
			if (is_object($this->Sections[0])){
				foreach ($this->Sections as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->States)){
			if (is_object($this->States[0])){
				foreach ($this->States as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class State
{
	public $Id;
	public $Name;
	public $Type;
	public $Produce;
	public $Color;
	public $DefaultRouteTo;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 Nullable.
	 * @param string               $Type                 Nullable.
	 * @param boolean              $Produce              Nullable.
	 * @param string               $Color                Nullable.
	 * @param string               $DefaultRouteTo       Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Type=null, $Produce=null, $Color=null, $DefaultRouteTo=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Type                 = $Type;
		$this->Produce              = $Produce;
		$this->Color                = $Color;
		$this->DefaultRouteTo       = $DefaultRouteTo;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflState'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Produce)){ $this->Produce = ('true' == $this->Produce) ? true : false; }
	}
}
class Target
{
	public $PubChannel;
	public $Issue;
	public $Editions;
	public $PublishedDate;
	public $PublishedVersion;

	/**
	 * @param PubChannel           $PubChannel           
	 * @param Issue                $Issue                Nullable.
	 * @param Edition[]            $Editions             Nullable.
	 * @param string               $PublishedDate        Nullable.
	 * @param string               $PublishedVersion     Nullable.
	 */
	public function __construct( $PubChannel=null, $Issue=null, $Editions=null, $PublishedDate=null, $PublishedVersion=null)
	{
		$this->PubChannel           = $PubChannel;
		$this->Issue                = $Issue;
		$this->Editions             = $Editions;
		$this->PublishedDate        = $PublishedDate;
		$this->PublishedVersion     = $PublishedVersion;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflTarget'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class ObjectTargetsInfo
{
	public $BasicMetaData;
	public $Targets;

	/**
	 * @param BasicMetaData        $BasicMetaData        
	 * @param Target[]             $Targets              
	 */
	public function __construct( $BasicMetaData=null, $Targets=null)
	{
		$this->BasicMetaData        = $BasicMetaData;
		$this->Targets              = $Targets;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflObjectTargetsInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Targets)){
			if (is_object($this->Targets[0])){
				foreach ($this->Targets as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class ChildRow
{
	public $Parents;
	public $Row;

	/**
	 * @param string[]             $Parents              
	 * @param string[]             $Row                  Nullable.
	 */
	public function __construct( $Parents=null, $Row=null)
	{
		$this->Parents              = $Parents;
		$this->Row                  = $Row;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflChildRow'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Parents)){
			if (is_object($this->Parents[0])){
				foreach ($this->Parents as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Row)){
			if (is_object($this->Row[0])){
				foreach ($this->Row as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AppFeature
{
	public $Name;
	public $Value;

	/**
	 * @param string               $Name                 
	 * @param string               $Value                
	 */
	public function __construct( $Name=null, $Value=null)
	{
		$this->Name                 = $Name;
		$this->Value                = $Value;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflAppFeature'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class FeatureProfile
{
	public $Name;
	public $Features;

	/**
	 * @param string               $Name                 
	 * @param AppFeature[]         $Features             Nullable.
	 */
	public function __construct( $Name=null, $Features=null)
	{
		$this->Name                 = $Name;
		$this->Features             = $Features;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflFeatureProfile'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Features)){
			if (is_object($this->Features[0])){
				foreach ($this->Features as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class FeatureAccess
{
	public $Profile;
	public $Issue;
	public $Section;
	public $State;

	/**
	 * @param string               $Profile              
	 * @param string               $Issue                Nullable.
	 * @param string               $Section              Nullable.
	 * @param string               $State                Nullable.
	 */
	public function __construct( $Profile=null, $Issue=null, $Section=null, $State=null)
	{
		$this->Profile              = $Profile;
		$this->Issue                = $Issue;
		$this->Section              = $Section;
		$this->State                = $State;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflFeatureAccess'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class DialogWidget
{
	public $PropertyInfo;
	public $PropertyUsage;

	/**
	 * @param PropertyInfo         $PropertyInfo         
	 * @param PropertyUsage        $PropertyUsage        
	 */
	public function __construct( $PropertyInfo=null, $PropertyUsage=null)
	{
		$this->PropertyInfo         = $PropertyInfo;
		$this->PropertyUsage        = $PropertyUsage;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflDialogWidget'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class DialogTab
{
	public $Title;
	public $Widgets;
	public $DefaultFocus;

	/**
	 * @param string               $Title                
	 * @param DialogWidget[]       $Widgets              
	 * @param string               $DefaultFocus         
	 */
	public function __construct( $Title=null, $Widgets=null, $DefaultFocus=null)
	{
		$this->Title                = $Title;
		$this->Widgets              = $Widgets;
		$this->DefaultFocus         = $DefaultFocus;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflDialogTab'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Widgets)){
			if (is_object($this->Widgets[0])){
				foreach ($this->Widgets as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class MetaDataValue
{
	public $Property;
	public $Values;
	public $PropertyValues;

	/**
	 * @param string               $Property             
	 * @param string[]             $Values               Nullable.
	 * @param PropertyValue[]      $PropertyValues       Nullable.
	 */
	public function __construct( $Property=null, $Values=null, $PropertyValues=null)
	{
		$this->Property             = $Property;
		$this->Values               = $Values;
		$this->PropertyValues       = $PropertyValues;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflMetaDataValue'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Values)){
			if (is_object($this->Values[0])){
				foreach ($this->Values as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->PropertyValues)){
			if (is_object($this->PropertyValues[0])){
				foreach ($this->PropertyValues as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Dialog
{
	public $Title;
	public $Tabs;
	public $MetaData;
	public $ButtonBar;

	/**
	 * @param string               $Title                
	 * @param DialogTab[]          $Tabs                 
	 * @param MetaDataValue[]      $MetaData             
	 * @param DialogButton[]       $ButtonBar            Nullable.
	 */
	public function __construct( $Title=null, $Tabs=null, $MetaData=null, $ButtonBar=null)
	{
		$this->Title                = $Title;
		$this->Tabs                 = $Tabs;
		$this->MetaData             = $MetaData;
		$this->ButtonBar            = $ButtonBar;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflDialog'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Tabs)){
			if (is_object($this->Tabs[0])){
				foreach ($this->Tabs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->MetaData)){
			if (is_object($this->MetaData[0])){
				foreach ($this->MetaData as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->ButtonBar)){
			if (is_object($this->ButtonBar[0])){
				foreach ($this->ButtonBar as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class DialogButton
{
	public $PropertyInfo;
	public $PropertyUsage;

	/**
	 * @param PropertyInfo         $PropertyInfo         
	 * @param PropertyUsage        $PropertyUsage        
	 */
	public function __construct( $PropertyInfo=null, $PropertyUsage=null)
	{
		$this->PropertyInfo         = $PropertyInfo;
		$this->PropertyUsage        = $PropertyUsage;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflDialogButton'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PropertyValue
{
	public $Value;
	public $Display;
	public $Entity;

	/**
	 * @param string               $Value                
	 * @param string               $Display              Nullable.
	 * @param string               $Entity               Nullable.
	 */
	public function __construct( $Value=null, $Display=null, $Entity=null)
	{
		$this->Value                = $Value;
		$this->Display              = $Display;
		$this->Entity               = $Entity;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPropertyValue'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class ObjectPageInfo
{
	public $MetaData;
	public $Pages;
	public $Messages;
	public $MessageList;

	/**
	 * @param MetaData             $MetaData             Nullable.
	 * @param Page[]               $Pages                
	 * @param Message[]            $Messages             Nullable.
	 * @param MessageList          $MessageList          Nullable.
	 */
	public function __construct( $MetaData=null, $Pages=null, $Messages=null, $MessageList=null)
	{
		$this->MetaData             = $MetaData;
		$this->Pages                = $Pages;
		$this->Messages             = $Messages;
		$this->MessageList          = $MessageList;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflObjectPageInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Pages)){
			if (is_object($this->Pages[0])){
				foreach ($this->Pages as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Messages)){
			if (is_object($this->Messages[0])){
				foreach ($this->Messages as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class ObjectInfo
{
	public $ID;
	public $Name;
	public $Type;
	public $Format;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 
	 * @param string               $Type                 
	 * @param string               $Format               Nullable.
	 */
	public function __construct( $ID=null, $Name=null, $Type=null, $Format=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->Type                 = $Type;
		$this->Format               = $Format;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflObjectInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class ObjectVersion
{
	public $ID;
	public $Version;

	/**
	 * @param string               $ID                   
	 * @param string               $Version              
	 */
	public function __construct( $ID=null, $Version=null)
	{
		$this->ID                   = $ID;
		$this->Version              = $Version;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflObjectVersion'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class Suggestion
{
	public $MisspelledWord;
	public $Suggestions;

	/**
	 * @param string               $MisspelledWord       
	 * @param string[]             $Suggestions          
	 */
	public function __construct( $MisspelledWord=null, $Suggestions=null)
	{
		$this->MisspelledWord       = $MisspelledWord;
		$this->Suggestions          = $Suggestions;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflSuggestion'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Suggestions)){
			if (is_object($this->Suggestions[0])){
				foreach ($this->Suggestions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class Dictionary
{
	public $Name;
	public $Language;
	public $DocLanguage;
	public $WordChars;

	/**
	 * @param string               $Name                 
	 * @param string               $Language             
	 * @param string               $DocLanguage          
	 * @param string               $WordChars            
	 */
	public function __construct( $Name=null, $Language=null, $DocLanguage=null, $WordChars=null)
	{
		$this->Name                 = $Name;
		$this->Language             = $Language;
		$this->DocLanguage          = $DocLanguage;
		$this->WordChars            = $WordChars;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflDictionary'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class ErrorReportEntity
{
	public $Type;
	public $ID;
	public $Name;
	public $Role;

	/**
	 * @param string               $Type                 
	 * @param string               $ID                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Role                 Nullable.
	 */
	public function __construct( $Type=null, $ID=null, $Name=null, $Role=null)
	{
		$this->Type                 = $Type;
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->Role                 = $Role;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflErrorReportEntity'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class ErrorReportEntry
{
	public $Entities;
	public $Message;
	public $Details;
	public $ErrorCode;
	public $MessageLevel;

	/**
	 * @param ErrorReportEntity[]  $Entities             
	 * @param string               $Message              
	 * @param string               $Details              
	 * @param string               $ErrorCode            
	 * @param string               $MessageLevel         
	 */
	public function __construct( $Entities=null, $Message=null, $Details=null, $ErrorCode=null, $MessageLevel=null)
	{
		$this->Entities             = $Entities;
		$this->Message              = $Message;
		$this->Details              = $Details;
		$this->ErrorCode            = $ErrorCode;
		$this->MessageLevel         = $MessageLevel;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflErrorReportEntry'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Entities)){
			if (is_object($this->Entities[0])){
				foreach ($this->Entities as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class ErrorReport
{
	public $BelongsTo;
	public $Entries;

	/**
	 * @param ErrorReportEntity    $BelongsTo            
	 * @param ErrorReportEntry[]   $Entries              
	 */
	public function __construct( $BelongsTo=null, $Entries=null)
	{
		$this->BelongsTo            = $BelongsTo;
		$this->Entries              = $Entries;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflErrorReport'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Entries)){
			if (is_object($this->Entries[0])){
				foreach ($this->Entries as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AutoSuggestTag
{
	public $Value;
	public $Score;
	public $StartPos;
	public $Length;

	/**
	 * @param string               $Value                
	 * @param float                $Score                
	 * @param integer              $StartPos             Nullable.
	 * @param integer              $Length               Nullable.
	 */
	public function __construct( $Value=null, $Score=null, $StartPos=null, $Length=null)
	{
		$this->Value                = $Value;
		$this->Score                = $Score;
		$this->StartPos             = $StartPos;
		$this->Length               = $Length;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflAutoSuggestTag'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Score)){ $this->Score = null; }
		if (is_nan($this->StartPos)){ $this->StartPos = null; }
		if (is_nan($this->Length)){ $this->Length = null; }
	}
}
class EntityTags
{
	public $Entity;
	public $Tags;

	/**
	 * @param string               $Entity               
	 * @param AutoSuggestTag[]     $Tags                 
	 */
	public function __construct( $Entity=null, $Tags=null)
	{
		$this->Entity               = $Entity;
		$this->Tags                 = $Tags;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflEntityTags'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Tags)){
			if (is_object($this->Tags[0])){
				foreach ($this->Tags as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AutoSuggestProperty
{
	public $Name;
	public $Entity;
	public $IgnoreValues;

	/**
	 * @param string               $Name                 
	 * @param string               $Entity               
	 * @param string[]             $IgnoreValues         Nullable.
	 */
	public function __construct( $Name=null, $Entity=null, $IgnoreValues=null)
	{
		$this->Name                 = $Name;
		$this->Entity               = $Entity;
		$this->IgnoreValues         = $IgnoreValues;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflAutoSuggestProperty'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->IgnoreValues)){
			if (is_object($this->IgnoreValues[0])){
				foreach ($this->IgnoreValues as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class PropertyNotification
{
	public $Type;
	public $Message;

	/**
	 * @param string               $Type                 
	 * @param string               $Message              
	 */
	public function __construct( $Type=null, $Message=null)
	{
		$this->Type                 = $Type;
		$this->Message              = $Message;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPropertyNotification'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class ObjectLabel
{
	public $Id;
	public $Name;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 */
	public function __construct( $Id=null, $Name=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflObjectLabel'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
	}
}
class RoutingMetaData
{
	public $ID;
	public $State;
	public $RouteTo;

	/**
	 * @param string               $ID                   
	 * @param State                $State                
	 * @param string               $RouteTo              
	 */
	public function __construct( $ID=null, $State=null, $RouteTo=null)
	{
		$this->ID                   = $ID;
		$this->State                = $State;
		$this->RouteTo              = $RouteTo;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflRoutingMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class ArticleAtWorkspace
{
	public $ID;
	public $Format;
	public $Content;
	public $Elements;

	/**
	 * @param string               $ID                   Nullable.
	 * @param string               $Format               
	 * @param string               $Content              Nullable.
	 * @param Element[]            $Elements             Nullable.
	 */
	public function __construct( $ID=null, $Format=null, $Content=null, $Elements=null)
	{
		$this->ID                   = $ID;
		$this->Format               = $Format;
		$this->Content              = $Content;
		$this->Elements             = $Elements;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflArticleAtWorkspace'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Elements)){
			if (is_object($this->Elements[0])){
				foreach ($this->Elements as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class EditionPages
{
	public $Edition;
	public $PageObjects;

	/**
	 * @param Edition              $Edition              Nullable.
	 * @param PageObject[]         $PageObjects          
	 */
	public function __construct( $Edition=null, $PageObjects=null)
	{
		$this->Edition              = $Edition;
		$this->PageObjects          = $PageObjects;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflEditionPages'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->PageObjects)){
			if (is_object($this->PageObjects[0])){
				foreach ($this->PageObjects as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class PageObject
{
	public $IssuePagePosition;
	public $PageOrder;
	public $PageNumber;
	public $PageSequence;
	public $Height;
	public $Width;
	public $ParentLayoutId;
	public $OutputRenditionAvailable;
	public $PlacementInfos;

	/**
	 * @param integer              $IssuePagePosition    
	 * @param integer              $PageOrder            
	 * @param string               $PageNumber           
	 * @param integer              $PageSequence         
	 * @param float                $Height               
	 * @param float                $Width                
	 * @param string               $ParentLayoutId       
	 * @param boolean              $OutputRenditionAvailable 
	 * @param PlacementInfo[]      $PlacementInfos       
	 */
	public function __construct( $IssuePagePosition=null, $PageOrder=null, $PageNumber=null, $PageSequence=null, $Height=null, $Width=null, $ParentLayoutId=null, $OutputRenditionAvailable=null, $PlacementInfos=null)
	{
		$this->IssuePagePosition    = $IssuePagePosition;
		$this->PageOrder            = $PageOrder;
		$this->PageNumber           = $PageNumber;
		$this->PageSequence         = $PageSequence;
		$this->Height               = $Height;
		$this->Width                = $Width;
		$this->ParentLayoutId       = $ParentLayoutId;
		$this->OutputRenditionAvailable = $OutputRenditionAvailable;
		$this->PlacementInfos       = $PlacementInfos;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPageObject'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->IssuePagePosition)){ $this->IssuePagePosition = null; }
		if (is_nan($this->PageOrder)){ $this->PageOrder = null; }
		if (is_nan($this->PageSequence)){ $this->PageSequence = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
		if (is_nan($this->Width)){ $this->Width = null; }
		if (!is_null($this->OutputRenditionAvailable)){ $this->OutputRenditionAvailable = ('true' == $this->OutputRenditionAvailable) ? true : false; }
		if (0 < count($this->PlacementInfos)){
			if (is_object($this->PlacementInfos[0])){
				foreach ($this->PlacementInfos as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class PlacementInfo
{
	public $Id;
	public $Left;
	public $Top;
	public $Width;
	public $Height;

	/**
	 * @param string               $Id                   
	 * @param float                $Left                 
	 * @param float                $Top                  
	 * @param float                $Width                
	 * @param float                $Height               
	 */
	public function __construct( $Id=null, $Left=null, $Top=null, $Width=null, $Height=null)
	{
		$this->Id                   = $Id;
		$this->Left                 = $Left;
		$this->Top                  = $Top;
		$this->Width                = $Width;
		$this->Height               = $Height;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPlacementInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Left)){ $this->Left = null; }
		if (is_nan($this->Top)){ $this->Top = null; }
		if (is_nan($this->Width)){ $this->Width = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
	}
}
class LayoutObject
{
	public $Id;
	public $Name;
	public $Category;
	public $State;
	public $Version;
	public $LockedBy;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 * @param Category             $Category             
	 * @param State                $State                
	 * @param string               $Version              
	 * @param string               $LockedBy             
	 */
	public function __construct( $Id=null, $Name=null, $Category=null, $State=null, $Version=null, $LockedBy=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Category             = $Category;
		$this->State                = $State;
		$this->Version              = $Version;
		$this->LockedBy             = $LockedBy;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflLayoutObject'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PlacedObject
{
	public $Id;
	public $Name;
	public $Type;
	public $State;
	public $Version;
	public $LockedBy;
	public $Format;

	/**
	 * @param string               $Id                   
	 * @param string               $Name                 
	 * @param string               $Type                 
	 * @param State                $State                
	 * @param string               $Version              
	 * @param string               $LockedBy             
	 * @param string               $Format               
	 */
	public function __construct( $Id=null, $Name=null, $Type=null, $State=null, $Version=null, $LockedBy=null, $Format=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Type                 = $Type;
		$this->State                = $State;
		$this->Version              = $Version;
		$this->LockedBy             = $LockedBy;
		$this->Format               = $Format;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.dataclasses.WflPlacedObject'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
