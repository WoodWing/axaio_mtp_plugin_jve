<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsNewQueryResponse
{
	public $QueryID;

	/**
	 * @param string               $QueryID                   
	 */
	public function __construct( $QueryID=null )
	{
		$this->QueryID              = $QueryID;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'NewQueryResponse' );
		if( $validator->checkExist( $datObj, 'QueryID' ) ) {
			$validator->enterPath( 'QueryID' );
			$validator->checkNull( $datObj->QueryID );
			if( !is_null( $datObj->QueryID ) ) {
				$validator->checkType( $datObj->QueryID, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsNewQueryResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

