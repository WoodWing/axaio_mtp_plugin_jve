<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdsGetQueryFieldsResponse
{
	public $QueryFields;

	/**
	 * @param AdsQueryField[]      $QueryFields               
	 */
	public function __construct( $QueryFields=null )
	{
		$this->QueryFields          = $QueryFields;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetQueryFieldsResponse' );
		if( $validator->checkExist( $datObj, 'QueryFields' ) ) {
			$validator->enterPath( 'QueryFields' );
			$validator->checkNull( $datObj->QueryFields );
			if( !is_null( $datObj->QueryFields ) ) {
				$validator->checkType( $datObj->QueryFields, 'array' );
				if( !empty($datObj->QueryFields) ) foreach( $datObj->QueryFields as $listItem ) {
					$validator->enterPath( 'QueryField' );
					$validator->checkType( $listItem, 'AdsQueryField' );
					AdsQueryFieldValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetQueryFieldsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

