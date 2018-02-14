<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetServersResponse
{
	public $Servers;
	public $CompanyLanguage;

	/**
	 * @param ServerInfo[]         $Servers                   
	 * @param string               $CompanyLanguage           Nullable.
	 */
	public function __construct( $Servers=null, $CompanyLanguage=null )
	{
		$this->Servers              = $Servers;
		$this->CompanyLanguage      = $CompanyLanguage;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetServersResponse' );
		if( $validator->checkExist( $datObj, 'Servers' ) ) {
			$validator->enterPath( 'Servers' );
			$validator->checkNull( $datObj->Servers );
			if( !is_null( $datObj->Servers ) ) {
				$validator->checkType( $datObj->Servers, 'array' );
				if( !empty($datObj->Servers) ) foreach( $datObj->Servers as $listItem ) {
					$validator->enterPath( 'ServerInfo' );
					$validator->checkType( $listItem, 'ServerInfo' );
					WflServerInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CompanyLanguage' ) ) {
			$validator->enterPath( 'CompanyLanguage' );
			if( !is_null( $datObj->CompanyLanguage ) ) {
				$validator->checkType( $datObj->CompanyLanguage, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetServersResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

