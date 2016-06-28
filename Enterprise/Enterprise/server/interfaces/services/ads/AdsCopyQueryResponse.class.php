<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdsCopyQueryResponse
{
	public $NewQueryID;

	/**
	 * @param string               $NewQueryID                
	 */
	public function __construct( $NewQueryID=null )
	{
		$this->NewQueryID           = $NewQueryID;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CopyQueryResponse' );
		if( $validator->checkExist( $datObj, 'NewQueryID' ) ) {
			$validator->enterPath( 'NewQueryID' );
			$validator->checkNull( $datObj->NewQueryID );
			if( !is_null( $datObj->NewQueryID ) ) {
				$validator->checkType( $datObj->NewQueryID, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsCopyQueryResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

