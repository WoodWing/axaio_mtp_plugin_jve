<?php

/**
 * @package Enterprise
 * @subpackage Planning Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR.'/server/protocols/soap/SOAP_Attachment.class.php';

class PlnPage
{
	public $PageOrder;
	public $Width;
	public $Height;
	public $Files;
	public $Edition;
	public $Master;
	public $PageSequence;
	public $PageNumber;

	/**
	 * @param integer              $PageOrder            
	 * @param float                $Width                Nullable.
	 * @param float                $Height               Nullable.
	 * @param Attachment[]         $Files                Nullable.
	 * @param Edition              $Edition              Nullable.
	 * @param string               $Master               Nullable.
	 * @param integer              $PageSequence         Nullable.
	 * @param string               $PageNumber           Nullable.
	 */
	public function __construct( $PageOrder=null, $Width=null, $Height=null, $Files=null, $Edition=null, $Master=null, $PageSequence=null, $PageNumber=null)
	{
		$this->PageOrder            = $PageOrder;
		$this->Width                = $Width;
		$this->Height               = $Height;
		$this->Files                = $Files;
		$this->Edition              = $Edition;
		$this->Master               = $Master;
		$this->PageSequence         = $PageSequence;
		$this->PageNumber           = $PageNumber;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.dataclasses.PlnPage'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PageOrder)){ $this->PageOrder = null; }
		if (is_nan($this->Width)){ $this->Width = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
		if (is_nan($this->PageSequence)){ $this->PageSequence = null; }
		if (0 < count($this->Files)){
			if (is_object($this->Files[0])){
				foreach ($this->Files as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class PlnLayout
{
	public $Id;
	public $Name;
	public $Publication;
	public $Issue;
	public $Section;
	public $Status;
	public $Pages;
	public $Editions;
	public $Deadline;
	public $Version;

	/**
	 * @param string               $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Publication          Nullable.
	 * @param string               $Issue                Nullable.
	 * @param string               $Section              Nullable.
	 * @param string               $Status               Nullable.
	 * @param PlnPage[]            $Pages                Nullable.
	 * @param Edition[]            $Editions             Nullable.
	 * @param string               $Deadline             Nullable.
	 * @param string               $Version              Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Publication=null, $Issue=null, $Section=null, $Status=null, $Pages=null, $Editions=null, $Deadline=null, $Version=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Publication          = $Publication;
		$this->Issue                = $Issue;
		$this->Section              = $Section;
		$this->Status               = $Status;
		$this->Pages                = $Pages;
		$this->Editions             = $Editions;
		$this->Deadline             = $Deadline;
		$this->Version              = $Version;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.dataclasses.PlnLayout'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Pages)){
			if (is_object($this->Pages[0])){
				foreach ($this->Pages as $complexField){
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
class PlnLayoutFromTemplate
{
	public $NewLayout;
	public $Template;

	/**
	 * @param PlnLayout            $NewLayout            
	 * @param string               $Template             
	 */
	public function __construct( $NewLayout=null, $Template=null)
	{
		$this->NewLayout            = $NewLayout;
		$this->Template             = $Template;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.dataclasses.PlnLayoutFromTemplate'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PlnPlacement
{
	public $Left;
	public $Top;
	public $Columns;
	public $Width;
	public $Height;
	public $Fixed;
	public $Layer;
	public $ContentDx;
	public $ContentDy;
	public $ScaleX;
	public $ScaleY;

	/**
	 * @param float                $Left                 
	 * @param float                $Top                  
	 * @param int                  $Columns              Nullable.
	 * @param float                $Width                Nullable.
	 * @param float                $Height               Nullable.
	 * @param boolean              $Fixed                Nullable.
	 * @param string               $Layer                Nullable.
	 * @param float                $ContentDx            Nullable.
	 * @param float                $ContentDy            Nullable.
	 * @param float                $ScaleX               Nullable.
	 * @param float                $ScaleY               Nullable.
	 */
	public function __construct( $Left=null, $Top=null, $Columns=null, $Width=null, $Height=null, $Fixed=null, $Layer=null, $ContentDx=null, $ContentDy=null, $ScaleX=null, $ScaleY=null)
	{
		$this->Left                 = $Left;
		$this->Top                  = $Top;
		$this->Columns              = $Columns;
		$this->Width                = $Width;
		$this->Height               = $Height;
		$this->Fixed                = $Fixed;
		$this->Layer                = $Layer;
		$this->ContentDx            = $ContentDx;
		$this->ContentDy            = $ContentDy;
		$this->ScaleX               = $ScaleX;
		$this->ScaleY               = $ScaleY;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.dataclasses.PlnPlacement'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Left)){ $this->Left = null; }
		if (is_nan($this->Top)){ $this->Top = null; }
		if (is_nan($this->Columns)){ $this->Columns = null; }
		if (is_nan($this->Width)){ $this->Width = null; }
		if (is_nan($this->Height)){ $this->Height = null; }
		if (is_nan($this->ContentDx)){ $this->ContentDx = null; }
		if (is_nan($this->ContentDy)){ $this->ContentDy = null; }
		if (is_nan($this->ScaleX)){ $this->ScaleX = null; }
		if (is_nan($this->ScaleY)){ $this->ScaleY = null; }
		if (!is_null($this->Fixed)){ $this->Fixed = ('true' == $this->Fixed) ? true : false; }
	}
}
class PlnAdvert
{
	public $Id;
	public $AlienId;
	public $Publication;
	public $Issue;
	public $Section;
	public $Status;
	public $Name;
	public $AdType;
	public $Comment;
	public $Source;
	public $ColorSpace;
	public $Description;
	public $PlainContent;
	public $File;
	public $HighResFile;
	public $PageOrder;
	public $Page;
	public $Placement;
	public $PreferredPlacement;
	public $PublishPrio;
	public $Rate;
	public $Editions;
	public $Deadline;
	public $PageSequence;
	public $Version;

	/**
	 * @param string               $Id                   Nullable.
	 * @param string               $AlienId              Nullable.
	 * @param string               $Publication          Nullable.
	 * @param string               $Issue                Nullable.
	 * @param string               $Section              Nullable.
	 * @param string               $Status               Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $AdType               Nullable.
	 * @param string               $Comment              Nullable.
	 * @param string               $Source               Nullable.
	 * @param string               $ColorSpace           Nullable.
	 * @param string               $Description          Nullable.
	 * @param string               $PlainContent         Nullable.
	 * @param Attachment           $File                 Nullable.
	 * @param string               $HighResFile          Nullable.
	 * @param integer              $PageOrder            Nullable.
	 * @param PlnPage              $Page                 Nullable.
	 * @param PlnPlacement         $Placement            Nullable.
	 * @param string               $PreferredPlacement   Nullable.
	 * @param string               $PublishPrio          Nullable.
	 * @param float                $Rate                 Nullable.
	 * @param Edition[]            $Editions             Nullable.
	 * @param string               $Deadline             Nullable.
	 * @param integer              $PageSequence         Nullable.
	 * @param string               $Version              Nullable.
	 */
	public function __construct( $Id=null, $AlienId=null, $Publication=null, $Issue=null, $Section=null, $Status=null, $Name=null, $AdType=null, $Comment=null, $Source=null, $ColorSpace=null, $Description=null, $PlainContent=null, $File=null, $HighResFile=null, $PageOrder=null, $Page=null, $Placement=null, $PreferredPlacement=null, $PublishPrio=null, $Rate=null, $Editions=null, $Deadline=null, $PageSequence=null, $Version=null)
	{
		$this->Id                   = $Id;
		$this->AlienId              = $AlienId;
		$this->Publication          = $Publication;
		$this->Issue                = $Issue;
		$this->Section              = $Section;
		$this->Status               = $Status;
		$this->Name                 = $Name;
		$this->AdType               = $AdType;
		$this->Comment              = $Comment;
		$this->Source               = $Source;
		$this->ColorSpace           = $ColorSpace;
		$this->Description          = $Description;
		$this->PlainContent         = $PlainContent;
		$this->File                 = $File;
		$this->HighResFile          = $HighResFile;
		$this->PageOrder            = $PageOrder;
		$this->Page                 = $Page;
		$this->Placement            = $Placement;
		$this->PreferredPlacement   = $PreferredPlacement;
		$this->PublishPrio          = $PublishPrio;
		$this->Rate                 = $Rate;
		$this->Editions             = $Editions;
		$this->Deadline             = $Deadline;
		$this->PageSequence         = $PageSequence;
		$this->Version              = $Version;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.dataclasses.PlnAdvert'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PageOrder)){ $this->PageOrder = null; }
		if (is_nan($this->Rate)){ $this->Rate = null; }
		if (is_nan($this->PageSequence)){ $this->PageSequence = null; }
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
