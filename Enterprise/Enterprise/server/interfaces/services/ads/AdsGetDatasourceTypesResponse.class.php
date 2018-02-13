<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsGetDatasourceTypesResponse
{
	public $DatasourceTypes;

	/**
	 * @param AdsDatasourceType[]  $DatasourceTypes           
	 */
	public function __construct( $DatasourceTypes=null )
	{
		$this->DatasourceTypes      = $DatasourceTypes;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetDatasourceTypesResponse' );
		if( $validator->checkExist( $datObj, 'DatasourceTypes' ) ) {
			$validator->enterPath( 'DatasourceTypes' );
			$validator->checkNull( $datObj->DatasourceTypes );
			if( !is_null( $datObj->DatasourceTypes ) ) {
				$validator->checkType( $datObj->DatasourceTypes, 'array' );
				if( !empty($datObj->DatasourceTypes) ) foreach( $datObj->DatasourceTypes as $listItem ) {
					$validator->enterPath( 'DatasourceType' );
					$validator->checkType( $listItem, 'AdsDatasourceType' );
					AdsDatasourceTypeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetDatasourceTypesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

