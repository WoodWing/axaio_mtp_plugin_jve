<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetStatusesRequest
{
	public $Ticket;
	public $PublicationId;
	public $IssueId;
	public $ObjectType;
	public $StatusIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             Nullable.
	 * @param integer              $IssueId                   Nullable.
	 * @param string               $ObjectType                Nullable.
	 * @param integer[]            $StatusIds                 Nullable.
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $IssueId=null, $ObjectType=null, $StatusIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationId        = $PublicationId;
		$this->IssueId              = $IssueId;
		$this->ObjectType           = $ObjectType;
		$this->StatusIds            = $StatusIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetStatusesRequest' );
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
		if( $validator->checkExist( $datObj, 'ObjectType' ) ) {
			$validator->enterPath( 'ObjectType' );
			if( !is_null( $datObj->ObjectType ) ) {
				$validator->checkType( $datObj->ObjectType, 'string' );
				AdmObjectTypeValidator::validate( $validator, $datObj->ObjectType );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'StatusIds' ) ) {
			$validator->enterPath( 'StatusIds' );
			if( !is_null( $datObj->StatusIds ) ) {
				$validator->checkType( $datObj->StatusIds, 'array' );
				if( !empty($datObj->StatusIds) ) foreach( $datObj->StatusIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetStatusesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (0 < count($this->StatusIds)){
			if (is_object($this->StatusIds[0])){
				foreach ($this->StatusIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

