<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmDeleteAutocompleteTermsRequest
{
	public $Ticket;
	public $TermEntity;
	public $Terms;

	/**
	 * @param string               $Ticket                    
	 * @param AdmTermEntity        $TermEntity                
	 * @param string[]             $Terms                     
	 */
	public function __construct( $Ticket=null, $TermEntity=null, $Terms=null )
	{
		$this->Ticket               = $Ticket;
		$this->TermEntity           = $TermEntity;
		$this->Terms                = $Terms;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteAutocompleteTermsRequest' );
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
		if( $validator->checkExist( $datObj, 'Terms' ) ) {
			$validator->enterPath( 'Terms' );
			$validator->checkNull( $datObj->Terms );
			if( !is_null( $datObj->Terms ) ) {
				$validator->checkType( $datObj->Terms, 'array' );
				if( !empty($datObj->Terms) ) foreach( $datObj->Terms as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteAutocompleteTermsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Terms)){
			if (is_object($this->Terms[0])){
				foreach ($this->Terms as $complexField){
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

