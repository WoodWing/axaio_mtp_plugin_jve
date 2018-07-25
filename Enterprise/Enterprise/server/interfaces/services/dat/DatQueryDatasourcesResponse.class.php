<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class DatQueryDatasourcesResponse
{
	public $Datasources;

	/**
	 * @param DatDatasourceInfo[]  $Datasources               
	 */
	public function __construct( $Datasources=null )
	{
		$this->Datasources          = $Datasources;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/dat/DataValidators.php';
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
					$validator->checkType( $listItem, 'DatDatasourceInfo' );
					DatDatasourceInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.DatQueryDatasourcesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

