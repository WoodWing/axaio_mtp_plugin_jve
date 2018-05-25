<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetUserSettingsResponse
{
	public $Settings;

	/**
	 * @param Setting[]            $Settings                  
	 */
	public function __construct( $Settings=null )
	{
		$this->Settings             = $Settings;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetUserSettingsResponse' );
		if( $validator->checkExist( $datObj, 'Settings' ) ) {
			$validator->enterPath( 'Settings' );
			$validator->checkNull( $datObj->Settings );
			if( !is_null( $datObj->Settings ) ) {
				$validator->checkType( $datObj->Settings, 'array' );
				if( !empty($datObj->Settings) ) foreach( $datObj->Settings as $listItem ) {
					$validator->enterPath( 'Setting' );
					$validator->checkType( $listItem, 'Setting' );
					WflSettingValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetUserSettingsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

