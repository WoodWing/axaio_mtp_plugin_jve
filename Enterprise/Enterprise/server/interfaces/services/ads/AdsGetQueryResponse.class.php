<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsGetQueryResponse
{
	public $Query;

	/**
	 * @param AdsQuery             $Query                     
	 */
	public function __construct( $Query=null )
	{
		$this->Query                = $Query;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetQueryResponse' );
		if( $validator->checkExist( $datObj, 'Query' ) ) {
			$validator->enterPath( 'Query' );
			$validator->checkNull( $datObj->Query );
			if( !is_null( $datObj->Query ) ) {
				$validator->checkType( $datObj->Query, 'AdsQuery' );
				AdsQueryValidator::validate( $validator, $datObj->Query );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetQueryResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

