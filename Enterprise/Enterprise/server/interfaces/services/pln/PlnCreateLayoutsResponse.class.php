<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class PlnCreateLayoutsResponse
{
	public $Layouts;

	/**
	 * @param PlnLayout[]          $Layouts                   
	 */
	public function __construct( $Layouts=null )
	{
		$this->Layouts              = $Layouts;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pln/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreateLayoutsResponse' );
		if( $validator->checkExist( $datObj, 'Layouts' ) ) {
			$validator->enterPath( 'Layouts' );
			$validator->checkNull( $datObj->Layouts );
			if( !is_null( $datObj->Layouts ) ) {
				$validator->checkType( $datObj->Layouts, 'array' );
				if( !empty($datObj->Layouts) ) foreach( $datObj->Layouts as $listItem ) {
					$validator->enterPath( 'Layout' );
					$validator->checkType( $listItem, 'PlnLayout' );
					PlnLayoutValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.PlnCreateLayoutsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

