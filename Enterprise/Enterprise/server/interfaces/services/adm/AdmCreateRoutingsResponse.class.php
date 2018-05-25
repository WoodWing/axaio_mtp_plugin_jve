<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmCreateRoutingsResponse
{
	public $Routings;

	/**
	 * @param AdmRouting[]         $Routings                  
	 */
	public function __construct( $Routings=null )
	{
		$this->Routings             = $Routings;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreateRoutingsResponse' );
		if( $validator->checkExist( $datObj, 'Routings' ) ) {
			$validator->enterPath( 'Routings' );
			$validator->checkNull( $datObj->Routings );
			if( !is_null( $datObj->Routings ) ) {
				$validator->checkType( $datObj->Routings, 'array' );
				if( !empty($datObj->Routings) ) foreach( $datObj->Routings as $listItem ) {
					$validator->enterPath( 'Routing' );
					$validator->checkType( $listItem, 'AdmRouting' );
					AdmRoutingValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCreateRoutingsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

