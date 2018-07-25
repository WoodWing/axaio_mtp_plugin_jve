<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflSuggestionsResponse
{
	public $SuggestedTags;

	/**
	 * @param EntityTags[]         $SuggestedTags             
	 */
	public function __construct( $SuggestedTags=null )
	{
		$this->SuggestedTags        = $SuggestedTags;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'SuggestionsResponse' );
		if( $validator->checkExist( $datObj, 'SuggestedTags' ) ) {
			$validator->enterPath( 'SuggestedTags' );
			$validator->checkNull( $datObj->SuggestedTags );
			if( !is_null( $datObj->SuggestedTags ) ) {
				$validator->checkType( $datObj->SuggestedTags, 'array' );
				if( !empty($datObj->SuggestedTags) ) foreach( $datObj->SuggestedTags as $listItem ) {
					$validator->enterPath( 'EntityTags' );
					$validator->checkType( $listItem, 'EntityTags' );
					WflEntityTagsValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSuggestionsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

