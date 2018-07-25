<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCheckSpellingResponse
{
	public $MisspelledWords;

	/**
	 * @param string[]             $MisspelledWords           
	 */
	public function __construct( $MisspelledWords=null )
	{
		$this->MisspelledWords      = $MisspelledWords;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CheckSpellingResponse' );
		if( $validator->checkExist( $datObj, 'MisspelledWords' ) ) {
			$validator->enterPath( 'MisspelledWords' );
			$validator->checkNull( $datObj->MisspelledWords );
			if( !is_null( $datObj->MisspelledWords ) ) {
				$validator->checkType( $datObj->MisspelledWords, 'array' );
				if( !empty($datObj->MisspelledWords) ) foreach( $datObj->MisspelledWords as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCheckSpellingResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

