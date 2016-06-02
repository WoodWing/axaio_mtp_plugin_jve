<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmModifySectionsRequest
{
	public $Ticket;
	public $PublicationId;
	public $IssueId;
	public $Sections;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             
	 * @param integer              $IssueId                   
	 * @param AdmSection[]         $Sections                  
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $IssueId=null, $Sections=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationId        = $PublicationId;
		$this->IssueId              = $IssueId;
		$this->Sections             = $Sections;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'ModifySectionsRequest' );
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
			$validator->checkNull( $datObj->PublicationId );
			if( !is_null( $datObj->PublicationId ) ) {
				$validator->checkType( $datObj->PublicationId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IssueId' ) ) {
			$validator->enterPath( 'IssueId' );
			$validator->checkNull( $datObj->IssueId );
			if( !is_null( $datObj->IssueId ) ) {
				$validator->checkType( $datObj->IssueId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Sections' ) ) {
			$validator->enterPath( 'Sections' );
			$validator->checkNull( $datObj->Sections );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmModifySectionsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (0 < count($this->Sections)){
			if (is_object($this->Sections[0])){
				foreach ($this->Sections as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

