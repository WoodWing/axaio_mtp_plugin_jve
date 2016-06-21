<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdsQueryDatasourcesResponse
{
	public $Datasources;

	/**
	 * @param AdsDatasourceInfo[]  $Datasources               
	 */
	public function __construct( $Datasources=null )
	{
		$this->Datasources          = $Datasources;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'QueryDatasourcesResponse' );
		if( $validator->checkExist( $datObj, 'Datasources' ) ) {
			$validator->enterPath( 'Datasources' );
			$validator->checkNull( $datObj->Datasources );
			if( !is_null( $datObj->Datasources ) ) {
				$validator->checkType( $datObj->Datasources, 'array' );
				if( !empty($datObj->Datasources) ) foreach( $datObj->Datasources as $listItem ) {
					$validator->enterPath( 'DatasourceInfo' );
					$validator->checkType( $listItem, 'AdsDatasourceInfo' );
					AdsDatasourceInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsQueryDatasourcesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

