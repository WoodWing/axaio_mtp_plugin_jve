<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmGetPublicationAdminAuthorizationsResponse
{
	public $UserGroupIds;
	public $UserGroups;

	/**
	 * @param integer[]            $UserGroupIds              
	 * @param AdmUserGroup[]       $UserGroups                Nullable.
	 */
	public function __construct( $UserGroupIds=null, $UserGroups=null )
	{
		$this->UserGroupIds         = $UserGroupIds;
		$this->UserGroups           = $UserGroups;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetPublicationAdminAuthorizationsResponse' );
		if( $validator->checkExist( $datObj, 'UserGroupIds' ) ) {
			$validator->enterPath( 'UserGroupIds' );
			$validator->checkNull( $datObj->UserGroupIds );
			if( !is_null( $datObj->UserGroupIds ) ) {
				$validator->checkType( $datObj->UserGroupIds, 'array' );
				if( !empty($datObj->UserGroupIds) ) foreach( $datObj->UserGroupIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserGroups' ) ) {
			$validator->enterPath( 'UserGroups' );
			if( !is_null( $datObj->UserGroups ) ) {
				$validator->checkType( $datObj->UserGroups, 'array' );
				if( !empty($datObj->UserGroups) ) foreach( $datObj->UserGroups as $listItem ) {
					$validator->enterPath( 'UserGroup' );
					$validator->checkType( $listItem, 'AdmUserGroup' );
					AdmUserGroupValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetPublicationAdminAuthorizationsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

