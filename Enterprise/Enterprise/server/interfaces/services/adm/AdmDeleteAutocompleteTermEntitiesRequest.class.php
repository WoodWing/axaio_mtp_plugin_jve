<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmDeleteAutocompleteTermEntitiesRequest
{
	public $Ticket;
	public $TermEntities;

	/**
	 * @param string               $Ticket                    
	 * @param AdmTermEntity[]      $TermEntities              
	 */
	public function __construct( $Ticket=null, $TermEntities=null )
	{
		$this->Ticket               = $Ticket;
		$this->TermEntities         = $TermEntities;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteAutocompleteTermEntitiesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TermEntities' ) ) {
			$validator->enterPath( 'TermEntities' );
			$validator->checkNull( $datObj->TermEntities );
			if( !is_null( $datObj->TermEntities ) ) {
				$validator->checkType( $datObj->TermEntities, 'array' );
				if( !empty($datObj->TermEntities) ) foreach( $datObj->TermEntities as $listItem ) {
					$validator->enterPath( 'TermEntity' );
					$validator->checkType( $listItem, 'AdmTermEntity' );
					AdmTermEntityValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteAutocompleteTermEntitiesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->TermEntities)){
			if (is_object($this->TermEntities[0])){
				foreach ($this->TermEntities as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

