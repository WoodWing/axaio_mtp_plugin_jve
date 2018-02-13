<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetObjectsResponse
{
	public $Objects;

	/**
	 * @param Object[]             $Objects                   
	 */
	public function __construct( $Objects=null )
	{
		$this->Objects              = $Objects;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetObjectsResponse' );
		if( $validator->checkExist( $datObj, 'Objects' ) ) {
			$validator->enterPath( 'Objects' );
			$validator->checkNull( $datObj->Objects );
			if( !is_null( $datObj->Objects ) ) {
				$validator->checkType( $datObj->Objects, 'array' );
				if( !empty($datObj->Objects) ) foreach( $datObj->Objects as $listItem ) {
					$validator->enterPath( 'Object' );
					$validator->checkType( $listItem, 'Object' );
					WflObjectValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetObjectsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return true; }
}

