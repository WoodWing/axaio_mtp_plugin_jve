<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmCreateAccessProfilesResponse
{
	public $AccessProfiles;

	/**
	 * @param AdmAccessProfile[]   $AccessProfiles            
	 */
	public function __construct( $AccessProfiles=null )
	{
		$this->AccessProfiles       = $AccessProfiles;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreateAccessProfilesResponse' );
		if( $validator->checkExist( $datObj, 'AccessProfiles' ) ) {
			$validator->enterPath( 'AccessProfiles' );
			$validator->checkNull( $datObj->AccessProfiles );
			if( !is_null( $datObj->AccessProfiles ) ) {
				$validator->checkType( $datObj->AccessProfiles, 'array' );
				if( !empty($datObj->AccessProfiles) ) foreach( $datObj->AccessProfiles as $listItem ) {
					$validator->enterPath( 'AccessProfile' );
					$validator->checkType( $listItem, 'AdmAccessProfile' );
					AdmAccessProfileValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCreateAccessProfilesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

