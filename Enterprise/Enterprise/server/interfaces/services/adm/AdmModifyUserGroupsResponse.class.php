<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmModifyUserGroupsResponse
{
	public $UserGroups;

	/**
	 * @param AdmUserGroup[]       $UserGroups                
	 */
	public function __construct( $UserGroups=null )
	{
		$this->UserGroups           = $UserGroups;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'ModifyUserGroupsResponse' );
		if( $validator->checkExist( $datObj, 'UserGroups' ) ) {
			$validator->enterPath( 'UserGroups' );
			$validator->checkNull( $datObj->UserGroups );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmModifyUserGroupsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

