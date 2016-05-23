<?php

/**
 * @package Enterprise
 * @subpackage DataSource Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR.'/server/protocols/soap/SOAP_Attachment.class.php';

class DatQueryParam
{
	public $Property;
	public $Operation;
	public $Value;

	/**
	 * @param string               $Property             
	 * @param string               $Operation            
	 * @param string               $Value                
	 */
	public function __construct( $Property=null, $Operation=null, $Value=null)
	{
		$this->Property             = $Property;
		$this->Operation            = $Operation;
		$this->Value                = $Value;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatQueryParam'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class DatAttribute
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatAttribute'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class DatList
{
	public $Name;
	public $Value;
	public $Attributes;

	/**
	 * @param string               $Name                 
	 * @param string               $Value                
	 * @param DatAttribute[]       $Attributes           
	 */
	public function __construct( $Name=null, $Value=null, $Attributes=null)
	{
		$this->Name                 = $Name;
		$this->Value                = $Value;
		$this->Attributes           = $Attributes;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatList'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Attributes)){
			if (is_object($this->Attributes[0])){
				foreach ($this->Attributes as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class DatRecordField
{
	public $UpdateType;
	public $UpdateResponse;
	public $ReadOnly;
	public $Priority;
	public $Name;
	public $Attributes;

	/**
	 * @param string               $UpdateType           
	 * @param string               $UpdateResponse       
	 * @param boolean              $ReadOnly             
	 * @param boolean              $Priority             
	 * @param string               $Name                 
	 * @param DatAttribute[]       $Attributes           
	 */
	public function __construct( $UpdateType=null, $UpdateResponse=null, $ReadOnly=null, $Priority=null, $Name=null, $Attributes=null)
	{
		$this->UpdateType           = $UpdateType;
		$this->UpdateResponse       = $UpdateResponse;
		$this->ReadOnly             = $ReadOnly;
		$this->Priority             = $Priority;
		$this->Name                 = $Name;
		$this->Attributes           = $Attributes;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatRecordField'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->ReadOnly)){ $this->ReadOnly = ('true' == $this->ReadOnly) ? true : false; }
		if (!is_null($this->Priority)){ $this->Priority = ('true' == $this->Priority) ? true : false; }
		if (0 < count($this->Attributes)){
			if (is_object($this->Attributes[0])){
				foreach ($this->Attributes as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class DatRecord
{
	public $ID;
	public $UpdateType;
	public $UpdateResponse;
	public $Hidden;
	public $Fields;

	/**
	 * @param string               $ID                   
	 * @param string               $UpdateType           
	 * @param string               $UpdateResponse       
	 * @param boolean              $Hidden               
	 * @param DatRecordField[]     $Fields               
	 */
	public function __construct( $ID=null, $UpdateType=null, $UpdateResponse=null, $Hidden=null, $Fields=null)
	{
		$this->ID                   = $ID;
		$this->UpdateType           = $UpdateType;
		$this->UpdateResponse       = $UpdateResponse;
		$this->Hidden               = $Hidden;
		$this->Fields               = $Fields;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatRecord'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Hidden)){ $this->Hidden = ('true' == $this->Hidden) ? true : false; }
		if (0 < count($this->Fields)){
			if (is_object($this->Fields[0])){
				foreach ($this->Fields as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class DatPlacedQuery
{
	public $QueryID;
	public $FamilyValues;

	/**
	 * @param string               $QueryID              
	 * @param FamilyValue[]        $FamilyValues         
	 */
	public function __construct( $QueryID=null, $FamilyValues=null)
	{
		$this->QueryID              = $QueryID;
		$this->FamilyValues         = $FamilyValues;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatPlacedQuery'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->FamilyValues)){
			if (is_object($this->FamilyValues[0])){
				foreach ($this->FamilyValues as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class DatPlacement
{
	public $ObjectID;
	public $PlacedQueries;

	/**
	 * @param string               $ObjectID             
	 * @param DatPlacedQuery[]     $PlacedQueries        
	 */
	public function __construct( $ObjectID=null, $PlacedQueries=null)
	{
		$this->ObjectID             = $ObjectID;
		$this->PlacedQueries        = $PlacedQueries;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatPlacement'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->PlacedQueries)){
			if (is_object($this->PlacedQueries[0])){
				foreach ($this->PlacedQueries as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class DatDatasourceInfo
{
	public $ID;
	public $Name;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 
	 */
	public function __construct( $ID=null, $Name=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatDatasourceInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class DatQuery
{
	public $ID;
	public $Name;
	public $Params;
	public $RecordFamily;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 
	 * @param DatProperty[]        $Params               
	 * @param string               $RecordFamily         
	 */
	public function __construct( $ID=null, $Name=null, $Params=null, $RecordFamily=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->Params               = $Params;
		$this->RecordFamily         = $RecordFamily;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatQuery'; } // AMF object type mapping

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
class DatProperty
{
	public $Name;
	public $DisplayName;
	public $Type;
	public $DefaultValue;
	public $ValueList;
	public $MinValue;
	public $MaxValue;
	public $MaxLength;

	/**
	 * @param string               $Name                 
	 * @param string               $DisplayName          
	 * @param string               $Type                 
	 * @param string               $DefaultValue         Nullable.
	 * @param string[]             $ValueList            Nullable.
	 * @param string               $MinValue             Nullable.
	 * @param string               $MaxValue             Nullable.
	 * @param int                  $MaxLength            Nullable.
	 */
	public function __construct( $Name=null, $DisplayName=null, $Type=null, $DefaultValue=null, $ValueList=null, $MinValue=null, $MaxValue=null, $MaxLength=null)
	{
		$this->Name                 = $Name;
		$this->DisplayName          = $DisplayName;
		$this->Type                 = $Type;
		$this->DefaultValue         = $DefaultValue;
		$this->ValueList            = $ValueList;
		$this->MinValue             = $MinValue;
		$this->MaxValue             = $MaxValue;
		$this->MaxLength            = $MaxLength;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.dataclasses.DatProperty'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->MaxLength)){ $this->MaxLength = null; }
		if (0 < count($this->ValueList)){
			if (is_object($this->ValueList[0])){
				foreach ($this->ValueList as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
