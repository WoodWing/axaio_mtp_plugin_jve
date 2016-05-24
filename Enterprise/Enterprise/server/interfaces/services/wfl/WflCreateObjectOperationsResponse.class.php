<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflCreateObjectOperationsResponse
{
	public $Operations;
	public $Reports;

	/**
	 * @param ObjectOperation[]    $Operations                
	 * @param ErrorReport[]        $Reports                   Nullable.
	 */
	public function __construct( $Operations=null, $Reports=null )
	{
		$this->Operations           = $Operations;
		$this->Reports              = $Reports;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreateObjectOperationsResponse' );
		if( $validator->checkExist( $datObj, 'Operations' ) ) {
			$validator->enterPath( 'Operations' );
			$validator->checkNull( $datObj->Operations );
			if( !is_null( $datObj->Operations ) ) {
				$validator->checkType( $datObj->Operations, 'array' );
				if( !empty($datObj->Operations) ) foreach( $datObj->Operations as $listItem ) {
					$validator->enterPath( 'ObjectOperation' );
					$validator->checkType( $listItem, 'ObjectOperation' );
					WflObjectOperationValidator::validate( $validator, $listItem );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCreateObjectOperationsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

