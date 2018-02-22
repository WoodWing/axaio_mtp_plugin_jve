<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflAutocompleteResponse
{
	public $Tags;

	/**
	 * @param AutoSuggestTag[]     $Tags                      
	 */
	public function __construct( $Tags=null )
	{
		$this->Tags                 = $Tags;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'AutocompleteResponse' );
		if( $validator->checkExist( $datObj, 'Tags' ) ) {
			$validator->enterPath( 'Tags' );
			$validator->checkNull( $datObj->Tags );
			if( !is_null( $datObj->Tags ) ) {
				$validator->checkType( $datObj->Tags, 'array' );
				if( !empty($datObj->Tags) ) foreach( $datObj->Tags as $listItem ) {
					$validator->enterPath( 'AutoSuggestTag' );
					$validator->checkType( $listItem, 'AutoSuggestTag' );
					WflAutoSuggestTagValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflAutocompleteResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

