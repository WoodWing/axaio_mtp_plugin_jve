<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflLockObjectsResponse
{
	public $IDs;
	public $Reports;

	/**
	 * @param string[]             $IDs                       
	 * @param ErrorReport[]        $Reports                   Nullable.
	 */
	public function __construct( $IDs=null, $Reports=null )
	{
		$this->IDs                  = $IDs;
		$this->Reports              = $Reports;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'LockObjectsResponse' );
		if( $validator->checkExist( $datObj, 'IDs' ) ) {
			$validator->enterPath( 'IDs' );
			$validator->checkNull( $datObj->IDs );
			if( !is_null( $datObj->IDs ) ) {
				$validator->checkType( $datObj->IDs, 'array' );
				if( !empty($datObj->IDs) ) foreach( $datObj->IDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflLockObjectsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

