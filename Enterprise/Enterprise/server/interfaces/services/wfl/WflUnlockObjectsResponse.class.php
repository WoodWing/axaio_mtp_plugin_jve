<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflUnlockObjectsResponse
{
	public $Reports;

	/**
	 * @param ErrorReport[]        $Reports                   Nullable.
	 */
	public function __construct( $Reports=null )
	{
		$this->Reports              = $Reports;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'UnlockObjectsResponse' );
		if( $validator->checkExist( $datObj, 'Reports' ) ) {
			$validator->enterPath( 'Reports' );
			if( !is_null( $datObj->Reports ) ) {
				$validator->checkType( $datObj->Reports, 'array' );
				if( !empty($datObj->Reports) ) foreach( $datObj->Reports as $listItem ) {
					$validator->enterPath( 'ErrorReport' );
					$validator->checkType( $listItem, 'ErrorReport' );
					WflErrorReportValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflUnlockObjectsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

