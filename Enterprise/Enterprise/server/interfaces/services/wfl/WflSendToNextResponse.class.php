<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflSendToNextResponse
{
	public $RoutingMetaDatas;
	public $Reports;

	/**
	 * @param RoutingMetaData[]    $RoutingMetaDatas          
	 * @param ErrorReport[]        $Reports                   
	 */
	public function __construct( $RoutingMetaDatas=null, $Reports=null )
	{
		$this->RoutingMetaDatas     = $RoutingMetaDatas;
		$this->Reports              = $Reports;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'SendToNextResponse' );
		if( $validator->checkExist( $datObj, 'RoutingMetaDatas' ) ) {
			$validator->enterPath( 'RoutingMetaDatas' );
			$validator->checkNull( $datObj->RoutingMetaDatas );
			if( !is_null( $datObj->RoutingMetaDatas ) ) {
				$validator->checkType( $datObj->RoutingMetaDatas, 'array' );
				if( !empty($datObj->RoutingMetaDatas) ) foreach( $datObj->RoutingMetaDatas as $listItem ) {
					$validator->enterPath( 'RoutingMetaData' );
					$validator->checkType( $listItem, 'RoutingMetaData' );
					WflRoutingMetaDataValidator::validate( $validator, $listItem );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSendToNextResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

