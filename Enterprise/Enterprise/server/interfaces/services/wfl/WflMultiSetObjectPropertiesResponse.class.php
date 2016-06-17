<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflMultiSetObjectPropertiesResponse
{
	public $MetaData;
	public $Reports;

	/**
	 * @param MetaDataValue[]      $MetaData                  
	 * @param ErrorReport[]        $Reports                   
	 */
	public function __construct( $MetaData=null, $Reports=null )
	{
		$this->MetaData             = $MetaData;
		$this->Reports              = $Reports;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'MultiSetObjectPropertiesResponse' );
		if( $validator->checkExist( $datObj, 'MetaData' ) ) {
			$validator->enterPath( 'MetaData' );
			$validator->checkNull( $datObj->MetaData );
			if( !is_null( $datObj->MetaData ) ) {
				$validator->checkType( $datObj->MetaData, 'array' );
				if( !empty($datObj->MetaData) ) foreach( $datObj->MetaData as $listItem ) {
					$validator->enterPath( 'MetaDataValue' );
					$validator->checkType( $listItem, 'MetaDataValue' );
					WflMetaDataValueValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Reports' ) ) {
			$validator->enterPath( 'Reports' );
			$validator->checkNull( $datObj->Reports );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflMultiSetObjectPropertiesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

