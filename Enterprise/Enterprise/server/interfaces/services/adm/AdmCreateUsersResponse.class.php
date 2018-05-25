<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmCreateUsersResponse
{
	public $Users;

	/**
	 * @param AdmUser[]            $Users                     
	 */
	public function __construct( $Users=null )
	{
		$this->Users                = $Users;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreateUsersResponse' );
		if( $validator->checkExist( $datObj, 'Users' ) ) {
			$validator->enterPath( 'Users' );
			$validator->checkNull( $datObj->Users );
			if( !is_null( $datObj->Users ) ) {
				$validator->checkType( $datObj->Users, 'array' );
				if( !empty($datObj->Users) ) foreach( $datObj->Users as $listItem ) {
					$validator->enterPath( 'User' );
					$validator->checkType( $listItem, 'AdmUser' );
					AdmUserValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCreateUsersResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

