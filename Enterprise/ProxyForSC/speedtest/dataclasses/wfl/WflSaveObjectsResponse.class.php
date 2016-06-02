<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflSaveObjectsResponse
{
	public $Objects;
	public $Reports;

	/**
	 * @param Object[]             $Objects                   
	 * @param ErrorReport[]        $Reports                   Nullable.
	 */
	public function __construct( $Objects=null, $Reports=null )
	{
		$this->Objects              = $Objects;
		$this->Reports              = $Reports;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'SaveObjectsResponse' );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSaveObjectsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

