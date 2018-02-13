<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmDeleteWorkflowUserGroupAuthorizationsRequest
{
	public $Ticket;
	public $PublicationId;
	public $IssueId;
	public $UserGroupId;
	public $WorkflowUserGroupAuthorizationIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             Nullable.
	 * @param integer              $IssueId                   Nullable.
	 * @param integer              $UserGroupId               Nullable.
	 * @param integer[]            $WorkflowUserGroupAuthorizationIds Nullable.
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $IssueId=null, $UserGroupId=null, $WorkflowUserGroupAuthorizationIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationId        = $PublicationId;
		$this->IssueId              = $IssueId;
		$this->UserGroupId          = $UserGroupId;
		$this->WorkflowUserGroupAuthorizationIds = $WorkflowUserGroupAuthorizationIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteWorkflowUserGroupAuthorizationsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublicationId' ) ) {
			$validator->enterPath( 'PublicationId' );
			if( !is_null( $datObj->PublicationId ) ) {
				$validator->checkType( $datObj->PublicationId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IssueId' ) ) {
			$validator->enterPath( 'IssueId' );
			if( !is_null( $datObj->IssueId ) ) {
				$validator->checkType( $datObj->IssueId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserGroupId' ) ) {
			$validator->enterPath( 'UserGroupId' );
			if( !is_null( $datObj->UserGroupId ) ) {
				$validator->checkType( $datObj->UserGroupId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'WorkflowUserGroupAuthorizationIds' ) ) {
			$validator->enterPath( 'WorkflowUserGroupAuthorizationIds' );
			if( !is_null( $datObj->WorkflowUserGroupAuthorizationIds ) ) {
				$validator->checkType( $datObj->WorkflowUserGroupAuthorizationIds, 'array' );
				if( !empty($datObj->WorkflowUserGroupAuthorizationIds) ) foreach( $datObj->WorkflowUserGroupAuthorizationIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteWorkflowUserGroupAuthorizationsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (is_nan($this->UserGroupId)){ $this->UserGroupId = null; }
		if (0 < count($this->WorkflowUserGroupAuthorizationIds)){
			if (is_object($this->WorkflowUserGroupAuthorizationIds[0])){
				foreach ($this->WorkflowUserGroupAuthorizationIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

