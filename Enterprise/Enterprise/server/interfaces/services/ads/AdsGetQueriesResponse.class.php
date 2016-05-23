<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdsGetQueriesResponse
{
	public $Queries;

	/**
	 * @param AdsQuery[]           $Queries                   
	 */
	public function __construct( $Queries=null )
	{
		$this->Queries              = $Queries;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetQueriesResponse' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsGetQueriesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

