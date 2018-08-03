<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetWorkflowUserGroupAuthorizationsResponse
{
	public $WorkflowUserGroupAuthorizations;
	public $UserGroups;
	public $Statuses;
	public $Sections;

	/**
	 * @param AdmWorkflowUserGroupAuthorization[] $WorkflowUserGroupAuthorizations 
	 * @param AdmUserGroup[]       $UserGroups                Nullable.
	 * @param AdmStatus[]          $Statuses                  Nullable.
	 * @param AdmSection[]         $Sections                  Nullable.
	 */
	public function __construct( $WorkflowUserGroupAuthorizations=null, $UserGroups=null, $Statuses=null, $Sections=null )
	{
		$this->WorkflowUserGroupAuthorizations = $WorkflowUserGroupAuthorizations;
		$this->UserGroups           = $UserGroups;
		$this->Statuses             = $Statuses;
		$this->Sections             = $Sections;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetWorkflowUserGroupAuthorizationsResponse' );
		if( $validator->checkExist( $datObj, 'WorkflowUserGroupAuthorizations' ) ) {
			$validator->enterPath( 'WorkflowUserGroupAuthorizations' );
			$validator->checkNull( $datObj->WorkflowUserGroupAuthorizations );
			if( !is_null( $datObj->WorkflowUserGroupAuthorizations ) ) {
				$validator->checkType( $datObj->WorkflowUserGroupAuthorizations, 'array' );
				if( !empty($datObj->WorkflowUserGroupAuthorizations) ) foreach( $datObj->WorkflowUserGroupAuthorizations as $listItem ) {
					$validator->enterPath( 'WorkflowUserGroupAuthorization' );
					$validator->checkType( $listItem, 'AdmWorkflowUserGroupAuthorization' );
					AdmWorkflowUserGroupAuthorizationValidator::validate( $validator, $listItem );
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
		if( $validator->checkExist( $datObj, 'Statuses' ) ) {
			$validator->enterPath( 'Statuses' );
			if( !is_null( $datObj->Statuses ) ) {
				$validator->checkType( $datObj->Statuses, 'array' );
				if( !empty($datObj->Statuses) ) foreach( $datObj->Statuses as $listItem ) {
					$validator->enterPath( 'Status' );
					$validator->checkType( $listItem, 'AdmStatus' );
					AdmStatusValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Sections' ) ) {
			$validator->enterPath( 'Sections' );
			if( !is_null( $datObj->Sections ) ) {
				$validator->checkType( $datObj->Sections, 'array' );
				if( !empty($datObj->Sections) ) foreach( $datObj->Sections as $listItem ) {
					$validator->enterPath( 'Section' );
					$validator->checkType( $listItem, 'AdmSection' );
					AdmSectionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetWorkflowUserGroupAuthorizationsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

