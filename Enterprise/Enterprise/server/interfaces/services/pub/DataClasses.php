<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR.'/server/protocols/soap/SOAP_Attachment.class.php';

class PubObjectInfo
{
	public $ID;
	public $Name;
	public $Type;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 
	 * @param string               $Type                 
	 */
	public function __construct( $ID=null, $Name=null, $Type=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->Type                 = $Type;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubObjectInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PubUserMessage
{
	public $Severity;
	public $MessageID;
	public $Message;
	public $Reason;

	/**
	 * @param string               $Severity             
	 * @param int                  $MessageID            
	 * @param string               $Message              
	 * @param string               $Reason               
	 */
	public function __construct( $Severity=null, $MessageID=null, $Message=null, $Reason=null)
	{
		$this->Severity             = $Severity;
		$this->MessageID            = $MessageID;
		$this->Message              = $Message;
		$this->Reason               = $Reason;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubUserMessage'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->MessageID)){ $this->MessageID = null; }
	}
}
class PubPageInfo
{
	public $PageNumber;
	public $PageSequence;
	public $PageOrder;

	/**
	 * @param string               $PageNumber           
	 * @param string               $PageSequence         
	 * @param string               $PageOrder            
	 */
	public function __construct( $PageNumber=null, $PageSequence=null, $PageOrder=null)
	{
		$this->PageNumber           = $PageNumber;
		$this->PageSequence         = $PageSequence;
		$this->PageOrder            = $PageOrder;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubPageInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PubMessageContext
{
	public $Objects;
	public $Page;

	/**
	 * @param PubObjectInfo[]      $Objects              
	 * @param PubPageInfo          $Page                 Nullable.
	 */
	public function __construct( $Objects=null, $Page=null)
	{
		$this->Objects              = $Objects;
		$this->Page                 = $Page;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubMessageContext'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Objects)){
			if (is_object($this->Objects[0])){
				foreach ($this->Objects as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if( is_object( $this->Page ) ) {
			$this->Page->sanitizeProperties4Php();
		}
	}
}
class PubReportMessage
{
	public $UserMessage;
	public $Context;

	/**
	 * @param PubUserMessage       $UserMessage          
	 * @param PubMessageContext    $Context              
	 */
	public function __construct( $UserMessage=null, $Context=null)
	{
		$this->UserMessage          = $UserMessage;
		$this->Context              = $Context;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubReportMessage'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if( is_object( $this->UserMessage ) ) {
			$this->UserMessage->sanitizeProperties4Php();
		}
		if( is_object( $this->Context ) ) {
			$this->Context->sanitizeProperties4Php();
		}
	}
}
class PubPublishedDossier
{
	public $DossierID;
	public $Target;
	public $PublishedDate;
	public $PublishMessage;
	public $Online;
	public $URL;
	public $Fields;
	public $History;

	/**
	 * @param string               $DossierID            
	 * @param PubPublishTarget     $Target               
	 * @param string               $PublishedDate        Nullable.
	 * @param PubUserMessage       $PublishMessage       Nullable.
	 * @param boolean              $Online               Nullable.
	 * @param string               $URL                  Nullable.
	 * @param PubField[]           $Fields               Nullable.
	 * @param PubPublishHistory[]  $History              Nullable.
	 */
	public function __construct( $DossierID=null, $Target=null, $PublishedDate=null, $PublishMessage=null, $Online=null, $URL=null, $Fields=null, $History=null)
	{
		$this->DossierID            = $DossierID;
		$this->Target               = $Target;
		$this->PublishedDate        = $PublishedDate;
		$this->PublishMessage       = $PublishMessage;
		$this->Online               = $Online;
		$this->URL                  = $URL;
		$this->Fields               = $Fields;
		$this->History              = $History;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubPublishedDossier'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Online)){ $this->Online = ('true' == $this->Online) ? true : false; }
		if (0 < count($this->Fields)){
			if (is_object($this->Fields[0])){
				foreach ($this->Fields as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->History)){
			if (is_object($this->History[0])){
				foreach ($this->History as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if( is_object( $this->Target ) ) {
			$this->Target->sanitizeProperties4Php();
		}
		if( is_object( $this->PublishMessage ) ) {
			$this->PublishMessage->sanitizeProperties4Php();
		}
	}
}
class PubPublishedIssue
{
	public $Target;
	public $Version;
	public $Fields;
	public $Report;
	public $PublishedDate;
	public $DossierOrder;

	/**
	 * @param PubPublishTarget     $Target               
	 * @param string               $Version              
	 * @param PubField[]           $Fields               Nullable.
	 * @param PubReportMessage[]   $Report               Nullable.
	 * @param string               $PublishedDate        Nullable.
	 * @param string[]             $DossierOrder         Nullable.
	 */
	public function __construct( $Target=null, $Version=null, $Fields=null, $Report=null, $PublishedDate=null, $DossierOrder=null)
	{
		$this->Target               = $Target;
		$this->Version              = $Version;
		$this->Fields               = $Fields;
		$this->Report               = $Report;
		$this->PublishedDate        = $PublishedDate;
		$this->DossierOrder         = $DossierOrder;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubPublishedIssue'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Fields)){
			if (is_object($this->Fields[0])){
				foreach ($this->Fields as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Report)){
			if (is_object($this->Report[0])){
				foreach ($this->Report as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->DossierOrder)){
			if (is_object($this->DossierOrder[0])){
				foreach ($this->DossierOrder as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if( is_object( $this->Target ) ) {
			$this->Target->sanitizeProperties4Php();
		}
	}
}
class PubField
{
	public $Key;
	public $Type;
	public $Values;

	/**
	 * @param string               $Key                  
	 * @param string               $Type                 
	 * @param string[]             $Values               
	 */
	public function __construct( $Key=null, $Type=null, $Values=null)
	{
		$this->Key                  = $Key;
		$this->Type                 = $Type;
		$this->Values               = $Values;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubField'; } // AMF object type mapping

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
class PubPublishTarget
{
	public $PubChannelID;
	public $IssueID;
	public $EditionID;
	public $PublishedDate;

	/**
	 * @param string               $PubChannelID         Nullable.
	 * @param string               $IssueID              Nullable.
	 * @param string               $EditionID            Nullable.
	 * @param string               $PublishedDate        Nullable.
	 */
	public function __construct( $PubChannelID=null, $IssueID=null, $EditionID=null, $PublishedDate=null)
	{
		$this->PubChannelID         = $PubChannelID;
		$this->IssueID              = $IssueID;
		$this->EditionID            = $EditionID;
		$this->PublishedDate        = $PublishedDate;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubPublishTarget'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PubPublishHistory
{
	public $PublishedDate;
	public $SendDate;
	public $PublishedBy;
	public $PublishedObjects;

	/**
	 * @param string               $PublishedDate        Nullable.
	 * @param string               $SendDate             
	 * @param string               $PublishedBy          
	 * @param PubPublishedObject[] $PublishedObjects     
	 */
	public function __construct( $PublishedDate=null, $SendDate=null, $PublishedBy=null, $PublishedObjects=null)
	{
		$this->PublishedDate        = $PublishedDate;
		$this->SendDate             = $SendDate;
		$this->PublishedBy          = $PublishedBy;
		$this->PublishedObjects     = $PublishedObjects;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubPublishHistory'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->PublishedObjects)){
			if (is_object($this->PublishedObjects[0])){
				foreach ($this->PublishedObjects as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class PubPublishedObject
{
	public $ObjectId;
	public $Version;
	public $Name;
	public $Type;
	public $Format;

	/**
	 * @param string               $ObjectId             
	 * @param string               $Version              
	 * @param string               $Name                 
	 * @param string               $Type                 
	 * @param string               $Format               
	 */
	public function __construct( $ObjectId=null, $Version=null, $Name=null, $Type=null, $Format=null)
	{
		$this->ObjectId             = $ObjectId;
		$this->Version              = $Version;
		$this->Name                 = $Name;
		$this->Type                 = $Type;
		$this->Format               = $Format;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubPublishedObject'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class PubProgressPhase
{
	public $ID;
	public $Label;
	public $Maximum;
	public $Progress;

	/**
	 * @param string               $ID                   
	 * @param string               $Label                
	 * @param integer              $Maximum              
	 * @param integer              $Progress             
	 */
	public function __construct( $ID=null, $Label=null, $Maximum=null, $Progress=null)
	{
		$this->ID                   = $ID;
		$this->Label                = $Label;
		$this->Maximum              = $Maximum;
		$this->Progress             = $Progress;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.dataclasses.PubProgressPhase'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Maximum)){ $this->Maximum = null; }
		if (is_nan($this->Progress)){ $this->Progress = null; }
	}
}
