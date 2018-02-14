<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCheckSpellingAndSuggestResponse
{
	public $Suggestions;

	/**
	 * @param Suggestion[]         $Suggestions               
	 */
	public function __construct( $Suggestions=null )
	{
		$this->Suggestions          = $Suggestions;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CheckSpellingAndSuggestResponse' );
		if( $validator->checkExist( $datObj, 'Suggestions' ) ) {
			$validator->enterPath( 'Suggestions' );
			$validator->checkNull( $datObj->Suggestions );
			if( !is_null( $datObj->Suggestions ) ) {
				$validator->checkType( $datObj->Suggestions, 'array' );
				if( !empty($datObj->Suggestions) ) foreach( $datObj->Suggestions as $listItem ) {
					$validator->enterPath( 'Suggestion' );
					$validator->checkType( $listItem, 'Suggestion' );
					WflSuggestionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCheckSpellingAndSuggestResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

