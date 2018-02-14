<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmModifyWorkflowUserGroupAuthorizationsResponse
{
	public $WorkflowUserGroupAuthorizations;

	/**
	 * @param AdmWorkflowUserGroupAuthorization[] $WorkflowUserGroupAuthorizations 
	 */
	public function __construct( $WorkflowUserGroupAuthorizations=null )
	{
		$this->WorkflowUserGroupAuthorizations = $WorkflowUserGroupAuthorizations;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'ModifyWorkflowUserGroupAuthorizationsResponse' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmModifyWorkflowUserGroupAuthorizationsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

