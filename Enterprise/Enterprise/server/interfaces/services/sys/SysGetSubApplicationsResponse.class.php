<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class SysGetSubApplicationsResponse
{
	public $SubApplications;

	/**
	 * @param SysSubApplication[]  $SubApplications           
	 */
	public function __construct( $SubApplications=null )
	{
		$this->SubApplications      = $SubApplications;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/sys/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetSubApplicationsResponse' );
		if( $validator->checkExist( $datObj, 'SubApplications' ) ) {
			$validator->enterPath( 'SubApplications' );
			$validator->checkNull( $datObj->SubApplications );
			if( !is_null( $datObj->SubApplications ) ) {
				$validator->checkType( $datObj->SubApplications, 'array' );
				if( !empty($datObj->SubApplications) ) foreach( $datObj->SubApplications as $listItem ) {
					$validator->enterPath( 'SubApplication' );
					$validator->checkType( $listItem, 'SysSubApplication' );
					SysSubApplicationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.sys.SysGetSubApplicationsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

