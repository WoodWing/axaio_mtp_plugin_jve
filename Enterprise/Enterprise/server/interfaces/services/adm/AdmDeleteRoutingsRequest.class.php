<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmDeleteRoutingsRequest
{
	public $Ticket;
	public $PublicationId;
	public $IssueId;
	public $SectionId;
	public $RoutingIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             Nullable.
	 * @param integer              $IssueId                   Nullable.
	 * @param integer              $SectionId                 Nullable.
	 * @param integer[]            $RoutingIds                Nullable.
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $IssueId=null, $SectionId=null, $RoutingIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationId        = $PublicationId;
		$this->IssueId              = $IssueId;
		$this->SectionId            = $SectionId;
		$this->RoutingIds           = $RoutingIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteRoutingsRequest' );
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
		if( $validator->checkExist( $datObj, 'SectionId' ) ) {
			$validator->enterPath( 'SectionId' );
			if( !is_null( $datObj->SectionId ) ) {
				$validator->checkType( $datObj->SectionId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RoutingIds' ) ) {
			$validator->enterPath( 'RoutingIds' );
			if( !is_null( $datObj->RoutingIds ) ) {
				$validator->checkType( $datObj->RoutingIds, 'array' );
				if( !empty($datObj->RoutingIds) ) foreach( $datObj->RoutingIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteRoutingsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (is_nan($this->SectionId)){ $this->SectionId = null; }
		if (0 < count($this->RoutingIds)){
			if (is_object($this->RoutingIds[0])){
				foreach ($this->RoutingIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

