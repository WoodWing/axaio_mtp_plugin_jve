<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmAddTemplateObjectsRequest
{
	public $Ticket;
	public $RequestModes;
	public $PublicationId;
	public $IssueId;
	public $TemplateObjects;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $RequestModes              
	 * @param integer              $PublicationId             Nullable.
	 * @param integer              $IssueId                   Nullable.
	 * @param AdmTemplateObjectAccess[] $TemplateObjects           
	 */
	public function __construct( $Ticket=null, $RequestModes=null, $PublicationId=null, $IssueId=null, $TemplateObjects=null )
	{
		$this->Ticket               = $Ticket;
		$this->RequestModes         = $RequestModes;
		$this->PublicationId        = $PublicationId;
		$this->IssueId              = $IssueId;
		$this->TemplateObjects      = $TemplateObjects;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'AddTemplateObjectsRequest' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmAddTemplateObjectsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (0 < count($this->RequestModes)){
			if (is_object($this->RequestModes[0])){
				foreach ($this->RequestModes as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->TemplateObjects)){
			if (is_object($this->TemplateObjects[0])){
				foreach ($this->TemplateObjects as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

