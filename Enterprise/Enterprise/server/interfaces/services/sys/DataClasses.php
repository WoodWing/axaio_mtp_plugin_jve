<?php

/**
 * @package Enterprise
 * @subpackage SysAdmin Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR.'/server/protocols/soap/SOAP_Attachment.class.php';

class SysSubApplication
{
	public $ID;
	public $Version;
	public $PackageUrl;
	public $DisplayName;
	public $ClientAppName;

	/**
	 * @param string               $ID                   
	 * @param string               $Version              
	 * @param string               $PackageUrl           
	 * @param string               $DisplayName          
	 * @param string               $ClientAppName        
	 */
	public function __construct( $ID=null, $Version=null, $PackageUrl=null, $DisplayName=null, $ClientAppName=null)
	{
		$this->ID                   = $ID;
		$this->Version              = $Version;
		$this->PackageUrl           = $PackageUrl;
		$this->DisplayName          = $DisplayName;
		$this->ClientAppName        = $ClientAppName;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.sys.dataclasses.SysSubApplication'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}
}
