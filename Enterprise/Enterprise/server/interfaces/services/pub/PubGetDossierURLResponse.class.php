<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class PubGetDossierURLResponse
{
	public $URL;

	/**
	 * @param string               $URL                       
	 */
	public function __construct( $URL=null )
	{
		$this->URL                  = $URL;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pub/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetDossierURLResponse' );
		if( $validator->checkExist( $datObj, 'URL' ) ) {
			$validator->enterPath( 'URL' );
			$validator->checkNull( $datObj->URL );
			if( !is_null( $datObj->URL ) ) {
				$validator->checkType( $datObj->URL, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubGetDossierURLResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

