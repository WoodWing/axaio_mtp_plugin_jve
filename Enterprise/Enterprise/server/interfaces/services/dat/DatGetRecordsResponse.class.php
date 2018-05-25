<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class DatGetRecordsResponse
{
	public $Records;

	/**
	 * @param DatRecord[]          $Records                   
	 */
	public function __construct( $Records=null )
	{
		$this->Records              = $Records;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/dat/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetRecordsResponse' );
		if( $validator->checkExist( $datObj, 'Records' ) ) {
			$validator->enterPath( 'Records' );
			$validator->checkNull( $datObj->Records );
			if( !is_null( $datObj->Records ) ) {
				$validator->checkType( $datObj->Records, 'array' );
				if( !empty($datObj->Records) ) foreach( $datObj->Records as $listItem ) {
					$validator->enterPath( 'Record' );
					$validator->checkType( $listItem, 'DatRecord' );
					DatRecordValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.DatGetRecordsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

