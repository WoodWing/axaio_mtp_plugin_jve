<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflNamedQueryRequest
{
	public $Ticket;
	public $Query;
	public $Params;
	public $FirstEntry;
	public $MaxEntries;
	public $Hierarchical;
	public $Order;

	/**
	 * @param string               $Ticket                    
	 * @param string               $Query                     
	 * @param QueryParam[]         $Params                    
	 * @param integer              $FirstEntry                Nullable.
	 * @param integer              $MaxEntries                Nullable.
	 * @param boolean              $Hierarchical              Nullable.
	 * @param QueryOrder[]         $Order                     Nullable.
	 */
	public function __construct( $Ticket=null, $Query=null, $Params=null, $FirstEntry=null, $MaxEntries=null, $Hierarchical=null, $Order=null )
	{
		$this->Ticket               = $Ticket;
		$this->Query                = $Query;
		$this->Params               = $Params;
		$this->FirstEntry           = $FirstEntry;
		$this->MaxEntries           = $MaxEntries;
		$this->Hierarchical         = $Hierarchical;
		$this->Order                = $Order;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'NamedQueryRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Query' ) ) {
			$validator->enterPath( 'Query' );
			$validator->checkNull( $datObj->Query );
			if( !is_null( $datObj->Query ) ) {
				$validator->checkType( $datObj->Query, 'string' );
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
					$validator->checkType( $listItem, 'QueryParam' );
					WflQueryParamValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FirstEntry' ) ) {
			$validator->enterPath( 'FirstEntry' );
			if( !is_null( $datObj->FirstEntry ) ) {
				$validator->checkType( $datObj->FirstEntry, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MaxEntries' ) ) {
			$validator->enterPath( 'MaxEntries' );
			if( !is_null( $datObj->MaxEntries ) ) {
				$validator->checkType( $datObj->MaxEntries, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Hierarchical' ) ) {
			$validator->enterPath( 'Hierarchical' );
			if( !is_null( $datObj->Hierarchical ) ) {
				$validator->checkType( $datObj->Hierarchical, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Order' ) ) {
			$validator->enterPath( 'Order' );
			if( !is_null( $datObj->Order ) ) {
				$validator->checkType( $datObj->Order, 'array' );
				if( !empty($datObj->Order) ) foreach( $datObj->Order as $listItem ) {
					$validator->enterPath( 'QueryOrder' );
					$validator->checkType( $listItem, 'QueryOrder' );
					WflQueryOrderValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflNamedQueryRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->FirstEntry)){ $this->FirstEntry = null; }
		if (is_nan($this->MaxEntries)){ $this->MaxEntries = null; }
		if (!is_null($this->Hierarchical)){ $this->Hierarchical = ('true' == $this->Hierarchical) ? true : false; }
		if (0 < count($this->Params)){
			if (is_object($this->Params[0])){
				foreach ($this->Params as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Order)){
			if (is_object($this->Order[0])){
				foreach ($this->Order as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

