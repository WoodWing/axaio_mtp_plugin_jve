<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class DatGetDatasourceResponse
{
	public $Queries;
	public $Bidirectional;

	/**
	 * @param DatQuery[]           $Queries                   
	 * @param string               $Bidirectional             
	 */
	public function __construct( $Queries=null, $Bidirectional=null )
	{
		$this->Queries              = $Queries;
		$this->Bidirectional        = $Bidirectional;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/dat/DataValidators.php';
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
					$validator->checkType( $listItem, 'DatQuery' );
					DatQueryValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Bidirectional' ) ) {
			$validator->enterPath( 'Bidirectional' );
			$validator->checkNull( $datObj->Bidirectional );
			if( !is_null( $datObj->Bidirectional ) ) {
				$validator->checkType( $datObj->Bidirectional, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.DatGetDatasourceResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

