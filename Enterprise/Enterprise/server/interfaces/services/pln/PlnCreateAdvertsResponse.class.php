<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class PlnCreateAdvertsResponse
{
	public $Adverts;

	/**
	 * @param PlnAdvert[]          $Adverts                   
	 */
	public function __construct( $Adverts=null )
	{
		$this->Adverts              = $Adverts;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pln/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreateAdvertsResponse' );
		if( $validator->checkExist( $datObj, 'Adverts' ) ) {
			$validator->enterPath( 'Adverts' );
			$validator->checkNull( $datObj->Adverts );
			if( !is_null( $datObj->Adverts ) ) {
				$validator->checkType( $datObj->Adverts, 'array' );
				if( !empty($datObj->Adverts) ) foreach( $datObj->Adverts as $listItem ) {
					$validator->enterPath( 'Advert' );
					$validator->checkType( $listItem, 'PlnAdvert' );
					PlnAdvertValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.PlnCreateAdvertsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

