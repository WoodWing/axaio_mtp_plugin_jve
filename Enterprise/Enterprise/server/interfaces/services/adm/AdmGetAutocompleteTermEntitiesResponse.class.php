<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetAutocompleteTermEntitiesResponse
{
	public $TermEntities;

	/**
	 * @param AdmTermEntity[]      $TermEntities              
	 */
	public function __construct( $TermEntities=null )
	{
		$this->TermEntities         = $TermEntities;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetAutocompleteTermEntitiesResponse' );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetAutocompleteTermEntitiesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

