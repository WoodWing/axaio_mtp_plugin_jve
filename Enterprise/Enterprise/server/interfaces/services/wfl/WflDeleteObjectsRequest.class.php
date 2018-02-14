<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflDeleteObjectsRequest
{
	public $Ticket;
	public $IDs;
	public $Permanent;
	public $Params;
	public $Areas;
	public $Context;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $IDs                       Nullable.
	 * @param boolean              $Permanent                 
	 * @param QueryParam[]         $Params                    Nullable.
	 * @param string[]             $Areas                     Nullable.
	 * @param string               $Context                   Nullable.
	 */
	public function __construct( $Ticket=null, $IDs=null, $Permanent=null, $Params=null, $Areas=null, $Context=null )
	{
		$this->Ticket               = $Ticket;
		$this->IDs                  = $IDs;
		$this->Permanent            = $Permanent;
		$this->Params               = $Params;
		$this->Areas                = $Areas;
		$this->Context              = $Context;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteObjectsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IDs' ) ) {
			$validator->enterPath( 'IDs' );
			if( !is_null( $datObj->IDs ) ) {
				$validator->checkType( $datObj->IDs, 'array' );
				if( !empty($datObj->IDs) ) foreach( $datObj->IDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Permanent' ) ) {
			$validator->enterPath( 'Permanent' );
			$validator->checkNull( $datObj->Permanent );
			if( !is_null( $datObj->Permanent ) ) {
				$validator->checkType( $datObj->Permanent, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Params' ) ) {
			$validator->enterPath( 'Params' );
			if( !is_null( $datObj->Params ) ) {
				$validator->checkType( $datObj->Params, 'array' );
				if( !empty($datObj->Params) ) foreach( $datObj->Params as $listItem ) {
					$validator->enterPath( 'QueryParam' );
					$validator->checkType( $listItem, 'QueryParam' );
					WflQueryParamValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Areas' ) ) {
			$validator->enterPath( 'Areas' );
			if( !is_null( $datObj->Areas ) ) {
				$validator->checkType( $datObj->Areas, 'array' );
				if( !empty($datObj->Areas) ) foreach( $datObj->Areas as $listItem ) {
					$validator->enterPath( 'AreaType' );
					$validator->checkType( $listItem, 'string' );
					WflAreaTypeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Context' ) ) {
			$validator->enterPath( 'Context' );
			if( !is_null( $datObj->Context ) ) {
				$validator->checkType( $datObj->Context, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflDeleteObjectsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Permanent)){ $this->Permanent = ('true' == $this->Permanent) ? true : false; }
		if (0 < count($this->IDs)){
			if (is_object($this->IDs[0])){
				foreach ($this->IDs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Params)){
			if (is_object($this->Params[0])){
				foreach ($this->Params as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Areas)){
			if (is_object($this->Areas[0])){
				foreach ($this->Areas as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

