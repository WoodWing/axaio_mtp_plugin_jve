<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdsGetDatasourceInfoResponse
{
	public $DatasourceInfo;

	/**
	 * @param AdsDatasourceInfo    $DatasourceInfo            
	 */
	public function __construct( $DatasourceInfo=null )
	{
		$this->DatasourceInfo       = $DatasourceInfo;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetDatasourceInfoResponse' );
		if( $validator->checkExist( $datObj, 'DatasourceInfo' ) ) {
			$validator->enterPath( 'DatasourceInfo' );
			$validator->checkNull( $datObj->DatasourceInfo );
			if( !is_null( $datObj->DatasourceInfo ) ) {
				$validator->checkType( $datObj->DatasourceInfo, 'AdsDatasourceInfo' );
				AdsDatasourceInfoValidator::validate( $validator, $datObj->DatasourceInfo );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetDatasourceInfoResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

