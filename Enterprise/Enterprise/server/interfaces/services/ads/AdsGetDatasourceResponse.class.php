<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsGetDatasourceResponse
{
	public $Queries;
	public $Settings;
	public $Publications;

	/**
	 * @param AdsQuery[]           $Queries                   
	 * @param AdsSetting[]         $Settings                  
	 * @param AdsPublication[]     $Publications              
	 */
	public function __construct( $Queries=null, $Settings=null, $Publications=null )
	{
		$this->Queries              = $Queries;
		$this->Settings             = $Settings;
		$this->Publications         = $Publications;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetDatasourceResponse' );
		if( $validator->checkExist( $datObj, 'Queries' ) ) {
			$validator->enterPath( 'Queries' );
			$validator->checkNull( $datObj->Queries );
			if( !is_null( $datObj->Queries ) ) {
				$validator->checkType( $datObj->Queries, 'array' );
				if( !empty($datObj->Queries) ) foreach( $datObj->Queries as $listItem ) {
					$validator->enterPath( 'Query' );
					$validator->checkType( $listItem, 'AdsQuery' );
					AdsQueryValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Settings' ) ) {
			$validator->enterPath( 'Settings' );
			$validator->checkNull( $datObj->Settings );
			if( !is_null( $datObj->Settings ) ) {
				$validator->checkType( $datObj->Settings, 'array' );
				if( !empty($datObj->Settings) ) foreach( $datObj->Settings as $listItem ) {
					$validator->enterPath( 'Setting' );
					$validator->checkType( $listItem, 'AdsSetting' );
					AdsSettingValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Publications' ) ) {
			$validator->enterPath( 'Publications' );
			$validator->checkNull( $datObj->Publications );
			if( !is_null( $datObj->Publications ) ) {
				$validator->checkType( $datObj->Publications, 'array' );
				if( !empty($datObj->Publications) ) foreach( $datObj->Publications as $listItem ) {
					$validator->enterPath( 'Publication' );
					$validator->checkType( $listItem, 'AdsPublication' );
					AdsPublicationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetDatasourceResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

