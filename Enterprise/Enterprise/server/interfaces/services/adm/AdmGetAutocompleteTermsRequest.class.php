<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmGetAutocompleteTermsRequest
{
	public $Ticket;
	public $TermEntity;
	public $TypedValue;
	public $FirstEntry;
	public $MaxEntries;

	/**
	 * @param string               $Ticket                    
	 * @param AdmTermEntity        $TermEntity                
	 * @param string               $TypedValue                
	 * @param integer              $FirstEntry                
	 * @param integer              $MaxEntries                Nullable.
	 */
	public function __construct( $Ticket=null, $TermEntity=null, $TypedValue=null, $FirstEntry=null, $MaxEntries=null )
	{
		$this->Ticket               = $Ticket;
		$this->TermEntity           = $TermEntity;
		$this->TypedValue           = $TypedValue;
		$this->FirstEntry           = $FirstEntry;
		$this->MaxEntries           = $MaxEntries;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetAutocompleteTermsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TermEntity' ) ) {
			$validator->enterPath( 'TermEntity' );
			$validator->checkNull( $datObj->TermEntity );
			if( !is_null( $datObj->TermEntity ) ) {
				$validator->checkType( $datObj->TermEntity, 'AdmTermEntity' );
				AdmTermEntityValidator::validate( $validator, $datObj->TermEntity );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TypedValue' ) ) {
			$validator->enterPath( 'TypedValue' );
			$validator->checkNull( $datObj->TypedValue );
			if( !is_null( $datObj->TypedValue ) ) {
				$validator->checkType( $datObj->TypedValue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FirstEntry' ) ) {
			$validator->enterPath( 'FirstEntry' );
			$validator->checkNull( $datObj->FirstEntry );
			if( !is_null( $datObj->FirstEntry ) ) {
				$validator->checkType( $datObj->FirstEntry, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MaxEntries' ) ) {
			$validator->enterPath( 'MaxEntries' );
			if( !is_null( $datObj->MaxEntries ) ) {
				$validator->checkType( $datObj->MaxEntries, 'integer' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetAutocompleteTermsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->FirstEntry)){ $this->FirstEntry = null; }
		if (is_nan($this->MaxEntries)){ $this->MaxEntries = null; }
		if( is_object( $this->TermEntity ) ) {
			$this->TermEntity->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

