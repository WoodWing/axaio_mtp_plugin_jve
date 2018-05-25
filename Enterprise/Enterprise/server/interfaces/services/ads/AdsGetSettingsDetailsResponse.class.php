<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsGetSettingsDetailsResponse
{
	public $SettingsDetails;

	/**
	 * @param AdsSettingsDetail[]  $SettingsDetails           
	 */
	public function __construct( $SettingsDetails=null )
	{
		$this->SettingsDetails      = $SettingsDetails;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetSettingsDetailsResponse' );
		if( $validator->checkExist( $datObj, 'SettingsDetails' ) ) {
			$validator->enterPath( 'SettingsDetails' );
			$validator->checkNull( $datObj->SettingsDetails );
			if( !is_null( $datObj->SettingsDetails ) ) {
				$validator->checkType( $datObj->SettingsDetails, 'array' );
				if( !empty($datObj->SettingsDetails) ) foreach( $datObj->SettingsDetails as $listItem ) {
					$validator->enterPath( 'SettingsDetail' );
					$validator->checkType( $listItem, 'AdsSettingsDetail' );
					AdsSettingsDetailValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetSettingsDetailsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

