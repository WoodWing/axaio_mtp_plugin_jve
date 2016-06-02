<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdsGetSettingsResponse
{
	public $Settings;

	/**
	 * @param AdsSetting[]         $Settings                  
	 */
	public function __construct( $Settings=null )
	{
		$this->Settings             = $Settings;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetSettingsResponse' );
		if( $validator->checkExist( $datObj, 'Settings' ) ) {
			$validator->enterPath( 'Settings' );
			$validator->checkNull( $datObj->Settings );
			if( !is_null( $datObj->Settings ) ) {
				$validator->checkType( $datObj->Settings, 'array' );
				if( !empty($datObj->Settings) ) foreach( $datObj->Settings as $listItem ) {
					$validator->enterPath( 'Setting' );
					$validator->checkType( $listItem, 'AdsSetting' );
					AdsSettingValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetSettingsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

