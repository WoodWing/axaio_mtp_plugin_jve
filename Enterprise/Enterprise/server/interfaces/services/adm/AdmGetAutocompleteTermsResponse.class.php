<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetAutocompleteTermsResponse
{
	public $Terms;
	public $FirstEntry;
	public $ListedEntries;
	public $TotalEntries;

	/**
	 * @param string[]             $Terms                     
	 * @param integer              $FirstEntry                
	 * @param integer              $ListedEntries             
	 * @param integer              $TotalEntries              
	 */
	public function __construct( $Terms=null, $FirstEntry=null, $ListedEntries=null, $TotalEntries=null )
	{
		$this->Terms                = $Terms;
		$this->FirstEntry           = $FirstEntry;
		$this->ListedEntries        = $ListedEntries;
		$this->TotalEntries         = $TotalEntries;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetAutocompleteTermsResponse' );
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
		if( $validator->checkExist( $datObj, 'FirstEntry' ) ) {
			$validator->enterPath( 'FirstEntry' );
			$validator->checkNull( $datObj->FirstEntry );
			if( !is_null( $datObj->FirstEntry ) ) {
				$validator->checkType( $datObj->FirstEntry, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ListedEntries' ) ) {
			$validator->enterPath( 'ListedEntries' );
			$validator->checkNull( $datObj->ListedEntries );
			if( !is_null( $datObj->ListedEntries ) ) {
				$validator->checkType( $datObj->ListedEntries, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TotalEntries' ) ) {
			$validator->enterPath( 'TotalEntries' );
			$validator->checkNull( $datObj->TotalEntries );
			if( !is_null( $datObj->TotalEntries ) ) {
				$validator->checkType( $datObj->TotalEntries, 'integer' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetAutocompleteTermsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

