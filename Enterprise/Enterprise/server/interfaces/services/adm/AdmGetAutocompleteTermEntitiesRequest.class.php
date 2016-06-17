<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmGetAutocompleteTermEntitiesRequest
{
	public $Ticket;
	public $AutocompleteProvider;

	/**
	 * @param string               $Ticket                    
	 * @param string               $AutocompleteProvider      Nullable.
	 */
	public function __construct( $Ticket=null, $AutocompleteProvider=null )
	{
		$this->Ticket               = $Ticket;
		$this->AutocompleteProvider = $AutocompleteProvider;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetAutocompleteTermEntitiesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AutocompleteProvider' ) ) {
			$validator->enterPath( 'AutocompleteProvider' );
			if( !is_null( $datObj->AutocompleteProvider ) ) {
				$validator->checkType( $datObj->AutocompleteProvider, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetAutocompleteTermEntitiesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

