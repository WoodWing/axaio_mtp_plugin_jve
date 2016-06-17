<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdsGetDatasourceTypeResponse
{
	public $DatasourceType;

	/**
	 * @param AdsDatasourceType    $DatasourceType            
	 */
	public function __construct( $DatasourceType=null )
	{
		$this->DatasourceType       = $DatasourceType;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetDatasourceTypeResponse' );
		if( $validator->checkExist( $datObj, 'DatasourceType' ) ) {
			$validator->enterPath( 'DatasourceType' );
			$validator->checkNull( $datObj->DatasourceType );
			if( !is_null( $datObj->DatasourceType ) ) {
				$validator->checkType( $datObj->DatasourceType, 'AdsDatasourceType' );
				AdsDatasourceTypeValidator::validate( $validator, $datObj->DatasourceType );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetDatasourceTypeResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

