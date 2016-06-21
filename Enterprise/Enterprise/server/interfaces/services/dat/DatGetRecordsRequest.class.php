<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class DatGetRecordsRequest
{
	public $Ticket;
	public $ObjectID;
	public $QueryID;
	public $DatasourceID;
	public $Params;

	/**
	 * @param string               $Ticket                    
	 * @param string               $ObjectID                  
	 * @param string               $QueryID                   
	 * @param string               $DatasourceID              
	 * @param DatQueryParam[]      $Params                    
	 */
	public function __construct( $Ticket=null, $ObjectID=null, $QueryID=null, $DatasourceID=null, $Params=null )
	{
		$this->Ticket               = $Ticket;
		$this->ObjectID             = $ObjectID;
		$this->QueryID              = $QueryID;
		$this->DatasourceID         = $DatasourceID;
		$this->Params               = $Params;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/dat/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetRecordsRequest' );
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
		if( $validator->checkExist( $datObj, 'QueryID' ) ) {
			$validator->enterPath( 'QueryID' );
			$validator->checkNull( $datObj->QueryID );
			if( !is_null( $datObj->QueryID ) ) {
				$validator->checkType( $datObj->QueryID, 'string' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.DatGetRecordsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Params)){
			if (is_object($this->Params[0])){
				foreach ($this->Params as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

