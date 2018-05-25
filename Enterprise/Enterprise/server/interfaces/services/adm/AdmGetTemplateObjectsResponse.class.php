<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetTemplateObjectsResponse
{
	public $TemplateObjects;
	public $UserGroups;
	public $ObjectInfos;

	/**
	 * @param AdmTemplateObjectAccess[] $TemplateObjects           
	 * @param AdmUserGroup[]       $UserGroups                Nullable.
	 * @param AdmObjectInfo[]      $ObjectInfos               Nullable.
	 */
	public function __construct( $TemplateObjects=null, $UserGroups=null, $ObjectInfos=null )
	{
		$this->TemplateObjects      = $TemplateObjects;
		$this->UserGroups           = $UserGroups;
		$this->ObjectInfos          = $ObjectInfos;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetTemplateObjectsResponse' );
		if( $validator->checkExist( $datObj, 'TemplateObjects' ) ) {
			$validator->enterPath( 'TemplateObjects' );
			$validator->checkNull( $datObj->TemplateObjects );
			if( !is_null( $datObj->TemplateObjects ) ) {
				$validator->checkType( $datObj->TemplateObjects, 'array' );
				if( !empty($datObj->TemplateObjects) ) foreach( $datObj->TemplateObjects as $listItem ) {
					$validator->enterPath( 'TemplateObjectAccess' );
					$validator->checkType( $listItem, 'AdmTemplateObjectAccess' );
					AdmTemplateObjectAccessValidator::validate( $validator, $listItem );
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
		if( $validator->checkExist( $datObj, 'ObjectInfos' ) ) {
			$validator->enterPath( 'ObjectInfos' );
			if( !is_null( $datObj->ObjectInfos ) ) {
				$validator->checkType( $datObj->ObjectInfos, 'array' );
				if( !empty($datObj->ObjectInfos) ) foreach( $datObj->ObjectInfos as $listItem ) {
					$validator->enterPath( 'ObjectInfo' );
					$validator->checkType( $listItem, 'AdmObjectInfo' );
					AdmObjectInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetTemplateObjectsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

