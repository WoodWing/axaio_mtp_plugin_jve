<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class DatSetRecordsRequest
{
	public $Ticket;
	public $ObjectID;
	public $DatasourceID;
	public $QueryID;
	public $Params;
	public $Records;

	/**
	 * @param string               $Ticket                    
	 * @param string               $ObjectID                  
	 * @param string               $DatasourceID              
	 * @param string               $QueryID                   
	 * @param DatQueryParam[]      $Params                    
	 * @param DatRecord[]          $Records                   
	 */
	public function __construct( $Ticket=null, $ObjectID=null, $DatasourceID=null, $QueryID=null, $Params=null, $Records=null )
	{
		$this->Ticket               = $Ticket;
		$this->ObjectID             = $ObjectID;
		$this->DatasourceID         = $DatasourceID;
		$this->QueryID              = $QueryID;
		$this->Params               = $Params;
		$this->Records              = $Records;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/dat/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'SetRecordsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectID' ) ) {
			$validator->enterPath( 'ObjectID' );
			$validator->checkNull( $datObj->ObjectID );
			if( !is_null( $datObj->ObjectID ) ) {
				$validator->checkType( $datObj->ObjectID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DatasourceID' ) ) {
			$validator->enterPath( 'DatasourceID' );
			$validator->checkNull( $datObj->DatasourceID );
			if( !is_null( $datObj->DatasourceID ) ) {
				$validator->checkType( $datObj->DatasourceID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'QueryID' ) ) {
			$validator->enterPath( 'QueryID' );
			$validator->checkNull( $datObj->QueryID );
			if( !is_null( $datObj->QueryID ) ) {
				$validator->checkType( $datObj->QueryID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Params' ) ) {
			$validator->enterPath( 'Params' );
			$validator->checkNull( $datObj->Params );
			if( !is_null( $datObj->Params ) ) {
				$validator->checkType( $datObj->Params, 'array' );
				if( !empty($datObj->Params) ) foreach( $datObj->Params as $listItem ) {
					$validator->enterPath( 'QueryParam' );
					$validator->checkType( $listItem, 'DatQueryParam' );
					DatQueryParamValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.DatSetRecordsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Params)){
			if (is_object($this->Params[0])){
				foreach ($this->Params as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Records)){
			if (is_object($this->Records[0])){
				foreach ($this->Records as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

