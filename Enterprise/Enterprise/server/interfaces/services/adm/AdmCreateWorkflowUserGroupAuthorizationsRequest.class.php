<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmCreateWorkflowUserGroupAuthorizationsRequest
{
	public $Ticket;
	public $PublicationId;
	public $IssueId;
	public $WorkflowUserGroupAuthorizations;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             Nullable.
	 * @param integer              $IssueId                   Nullable.
	 * @param AdmWorkflowUserGroupAuthorization[] $WorkflowUserGroupAuthorizations 
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $IssueId=null, $WorkflowUserGroupAuthorizations=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationId        = $PublicationId;
		$this->IssueId              = $IssueId;
		$this->WorkflowUserGroupAuthorizations = $WorkflowUserGroupAuthorizations;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CreateWorkflowUserGroupAuthorizationsRequest' );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCreateWorkflowUserGroupAuthorizationsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (0 < count($this->WorkflowUserGroupAuthorizations)){
			if (is_object($this->WorkflowUserGroupAuthorizations[0])){
				foreach ($this->WorkflowUserGroupAuthorizations as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

