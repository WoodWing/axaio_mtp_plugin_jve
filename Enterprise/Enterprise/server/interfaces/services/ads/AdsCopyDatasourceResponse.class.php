<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsCopyDatasourceResponse
{
	public $NewDatasourceID;

	/**
	 * @param string               $NewDatasourceID           
	 */
	public function __construct( $NewDatasourceID=null )
	{
		$this->NewDatasourceID      = $NewDatasourceID;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CopyDatasourceResponse' );
		if( $validator->checkExist( $datObj, 'NewDatasourceID' ) ) {
			$validator->enterPath( 'NewDatasourceID' );
			$validator->checkNull( $datObj->NewDatasourceID );
			if( !is_null( $datObj->NewDatasourceID ) ) {
				$validator->checkType( $datObj->NewDatasourceID, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsCopyDatasourceResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

