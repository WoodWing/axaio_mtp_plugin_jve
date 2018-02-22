<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmDeleteIssuesRequest
{
	public $Ticket;
	public $PublicationId;
	public $IssueIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             
	 * @param integer[]            $IssueIds                  
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $IssueIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationId        = $PublicationId;
		$this->IssueIds             = $IssueIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteIssuesRequest' );
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
		if( $validator->checkExist( $datObj, 'IssueIds' ) ) {
			$validator->enterPath( 'IssueIds' );
			$validator->checkNull( $datObj->IssueIds );
			if( !is_null( $datObj->IssueIds ) ) {
				$validator->checkType( $datObj->IssueIds, 'array' );
				if( !empty($datObj->IssueIds) ) foreach( $datObj->IssueIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteIssuesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (0 < count($this->IssueIds)){
			if (is_object($this->IssueIds[0])){
				foreach ($this->IssueIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

