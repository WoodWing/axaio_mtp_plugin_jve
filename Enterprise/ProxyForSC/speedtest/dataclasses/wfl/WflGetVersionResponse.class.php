<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflGetVersionResponse
{
	public $VersionInfo;

	/**
	 * @param VersionInfo          $VersionInfo               
	 */
	public function __construct( $VersionInfo=null )
	{
		$this->VersionInfo          = $VersionInfo;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetVersionResponse' );
		if( $validator->checkExist( $datObj, 'VersionInfo' ) ) {
			$validator->enterPath( 'VersionInfo' );
			$validator->checkNull( $datObj->VersionInfo );
			if( !is_null( $datObj->VersionInfo ) ) {
				$validator->checkType( $datObj->VersionInfo, 'VersionInfo' );
				WflVersionInfoValidator::validate( $validator, $datObj->VersionInfo );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetVersionResponse'; } // AMF object type mapping

	public function mightHaveContent() { return true; }
}

