<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetTemplateObjectsRequest
{
	public $Ticket;
	public $RequestModes;
	public $PublicationId;
	public $IssueId;
	public $TemplateObjectId;
	public $UserGroupId;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $RequestModes              
	 * @param integer              $PublicationId             Nullable.
	 * @param integer              $IssueId                   Nullable.
	 * @param integer              $TemplateObjectId          Nullable.
	 * @param integer              $UserGroupId               Nullable.
	 */
	public function __construct( $Ticket=null, $RequestModes=null, $PublicationId=null, $IssueId=null, $TemplateObjectId=null, $UserGroupId=null )
	{
		$this->Ticket               = $Ticket;
		$this->RequestModes         = $RequestModes;
		$this->PublicationId        = $PublicationId;
		$this->IssueId              = $IssueId;
		$this->TemplateObjectId     = $TemplateObjectId;
		$this->UserGroupId          = $UserGroupId;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetTemplateObjectsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestModes' ) ) {
			$validator->enterPath( 'RequestModes' );
			$validator->checkNull( $datObj->RequestModes );
			if( !is_null( $datObj->RequestModes ) ) {
				$validator->checkType( $datObj->RequestModes, 'array' );
				if( !empty($datObj->RequestModes) ) foreach( $datObj->RequestModes as $listItem ) {
					$validator->enterPath( 'Mode' );
					$validator->checkType( $listItem, 'string' );
					AdmModeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
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
		if( $validator->checkExist( $datObj, 'TemplateObjectId' ) ) {
			$validator->enterPath( 'TemplateObjectId' );
			if( !is_null( $datObj->TemplateObjectId ) ) {
				$validator->checkType( $datObj->TemplateObjectId, 'integer' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetTemplateObjectsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (is_nan($this->TemplateObjectId)){ $this->TemplateObjectId = null; }
		if (is_nan($this->UserGroupId)){ $this->UserGroupId = null; }
		if (0 < count($this->RequestModes)){
			if (is_object($this->RequestModes[0])){
				foreach ($this->RequestModes as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

