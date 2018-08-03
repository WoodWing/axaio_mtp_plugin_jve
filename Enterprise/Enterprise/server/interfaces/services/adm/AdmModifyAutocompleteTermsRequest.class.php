<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmModifyAutocompleteTermsRequest
{
	public $Ticket;
	public $TermEntity;
	public $OldTerms;
	public $NewTerms;

	/**
	 * @param string               $Ticket                    
	 * @param AdmTermEntity        $TermEntity                
	 * @param string[]             $OldTerms                  
	 * @param string[]             $NewTerms                  
	 */
	public function __construct( $Ticket=null, $TermEntity=null, $OldTerms=null, $NewTerms=null )
	{
		$this->Ticket               = $Ticket;
		$this->TermEntity           = $TermEntity;
		$this->OldTerms             = $OldTerms;
		$this->NewTerms             = $NewTerms;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'ModifyAutocompleteTermsRequest' );
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
		if( $validator->checkExist( $datObj, 'OldTerms' ) ) {
			$validator->enterPath( 'OldTerms' );
			$validator->checkNull( $datObj->OldTerms );
			if( !is_null( $datObj->OldTerms ) ) {
				$validator->checkType( $datObj->OldTerms, 'array' );
				if( !empty($datObj->OldTerms) ) foreach( $datObj->OldTerms as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'NewTerms' ) ) {
			$validator->enterPath( 'NewTerms' );
			$validator->checkNull( $datObj->NewTerms );
			if( !is_null( $datObj->NewTerms ) ) {
				$validator->checkType( $datObj->NewTerms, 'array' );
				if( !empty($datObj->NewTerms) ) foreach( $datObj->NewTerms as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmModifyAutocompleteTermsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->OldTerms)){
			if (is_object($this->OldTerms[0])){
				foreach ($this->OldTerms as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->NewTerms)){
			if (is_object($this->NewTerms[0])){
				foreach ($this->NewTerms as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if( is_object( $this->TermEntity ) ) {
			$this->TermEntity->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

