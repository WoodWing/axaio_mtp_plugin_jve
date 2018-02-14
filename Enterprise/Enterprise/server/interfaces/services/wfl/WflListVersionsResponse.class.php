<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflListVersionsResponse
{
	public $Versions;

	/**
	 * @param VersionInfo[]        $Versions                  
	 */
	public function __construct( $Versions=null )
	{
		$this->Versions             = $Versions;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'ListVersionsResponse' );
		if( $validator->checkExist( $datObj, 'Versions' ) ) {
			$validator->enterPath( 'Versions' );
			$validator->checkNull( $datObj->Versions );
			if( !is_null( $datObj->Versions ) ) {
				$validator->checkType( $datObj->Versions, 'array' );
				if( !empty($datObj->Versions) ) foreach( $datObj->Versions as $listItem ) {
					$validator->enterPath( 'VersionInfo' );
					$validator->checkType( $listItem, 'VersionInfo' );
					WflVersionInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflListVersionsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return true; }
}

