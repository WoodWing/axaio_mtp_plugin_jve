<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR.'/server/protocols/soap/SOAP_Attachment.class.php';

class AdsPublication
{
	public $ID;
	public $Name;
	public $Description;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 
	 * @param string               $Description          
	 */
	public function __construct( $ID=null, $Name=null, $Description=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->Description          = $Description;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.dataclasses.AdsPublication'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class AdsSetting
{
	public $ID;
	public $Name;
	public $Value;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 
	 * @param string               $Value                
	 */
	public function __construct( $ID=null, $Name=null, $Value=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->Value                = $Value;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.dataclasses.AdsSetting'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class AdsQuery
{
	public $ID;
	public $Name;
	public $Query;
	public $Interface;
	public $Comment;
	public $RecordID;
	public $RecordFamily;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 
	 * @param string               $Query                
	 * @param string               $Interface            
	 * @param string               $Comment              
	 * @param string               $RecordID             
	 * @param string               $RecordFamily         
	 */
	public function __construct( $ID=null, $Name=null, $Query=null, $Interface=null, $Comment=null, $RecordID=null, $RecordFamily=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->Query                = $Query;
		$this->Interface            = $Interface;
		$this->Comment              = $Comment;
		$this->RecordID             = $RecordID;
		$this->RecordFamily         = $RecordFamily;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.dataclasses.AdsQuery'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class AdsQueryField
{
	public $Name;
	public $ID;
	public $Priority;
	public $ReadOnly;

	/**
	 * @param string               $Name                 
	 * @param string               $ID                   
	 * @param string               $Priority             
	 * @param string               $ReadOnly             
	 */
	public function __construct( $Name=null, $ID=null, $Priority=null, $ReadOnly=null)
	{
		$this->Name                 = $Name;
		$this->ID                   = $ID;
		$this->Priority             = $Priority;
		$this->ReadOnly             = $ReadOnly;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.dataclasses.AdsQueryField'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class AdsDatasourceType
{
	public $Type;

	/**
	 * @param string               $Type                 
	 */
	public function __construct( $Type=null)
	{
		$this->Type                 = $Type;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.dataclasses.AdsDatasourceType'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class AdsSettingsDetail
{
	public $Name;
	public $Description;
	public $Type;
	public $List;

	/**
	 * @param string               $Name                 
	 * @param string               $Description          
	 * @param string               $Type                 
	 * @param string               $List                 Nullable.
	 */
	public function __construct( $Name=null, $Description=null, $Type=null, $List=null)
	{
		$this->Name                 = $Name;
		$this->Description          = $Description;
		$this->Type                 = $Type;
		$this->List                 = $List;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.dataclasses.AdsSettingsDetail'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
class AdsDatasourceInfo
{
	public $ID;
	public $Name;
	public $Bidirectional;
	public $Type;

	/**
	 * @param string               $ID                   
	 * @param string               $Name                 
	 * @param string               $Bidirectional        
	 * @param string               $Type                 
	 */
	public function __construct( $ID=null, $Name=null, $Bidirectional=null, $Type=null)
	{
		$this->ID                   = $ID;
		$this->Name                 = $Name;
		$this->Bidirectional        = $Bidirectional;
		$this->Type                 = $Type;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.dataclasses.AdsDatasourceInfo'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
