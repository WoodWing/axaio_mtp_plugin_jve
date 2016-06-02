<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class PubPreviewDossiersRequest
{
	public $Ticket;
	public $DossierIDs;
	public $Targets;
	public $RequestInfo;
	public $OperationId;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $DossierIDs                
	 * @param PubPublishTarget[]   $Targets                   
	 * @param string[]             $RequestInfo               Nullable.
	 * @param string               $OperationId               
	 */
	public function __construct( $Ticket=null, $DossierIDs=null, $Targets=null, $RequestInfo=null, $OperationId=null )
	{
		$this->Ticket               = $Ticket;
		$this->DossierIDs           = $DossierIDs;
		$this->Targets              = $Targets;
		$this->RequestInfo          = $RequestInfo;
		$this->OperationId          = $OperationId;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pub/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'PreviewDossiersRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DossierIDs' ) ) {
			$validator->enterPath( 'DossierIDs' );
			$validator->checkNull( $datObj->DossierIDs );
			if( !is_null( $datObj->DossierIDs ) ) {
				$validator->checkType( $datObj->DossierIDs, 'array' );
				if( !empty($datObj->DossierIDs) ) foreach( $datObj->DossierIDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Targets' ) ) {
			$validator->enterPath( 'Targets' );
			$validator->checkNull( $datObj->Targets );
			if( !is_null( $datObj->Targets ) ) {
				$validator->checkType( $datObj->Targets, 'array' );
				if( !empty($datObj->Targets) ) foreach( $datObj->Targets as $listItem ) {
					$validator->enterPath( 'PublishTarget' );
					$validator->checkType( $listItem, 'PubPublishTarget' );
					PubPublishTargetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestInfo' ) ) {
			$validator->enterPath( 'RequestInfo' );
			if( !is_null( $datObj->RequestInfo ) ) {
				$validator->checkType( $datObj->RequestInfo, 'array' );
				if( !empty($datObj->RequestInfo) ) foreach( $datObj->RequestInfo as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'OperationId' ) ) {
			$validator->enterPath( 'OperationId' );
			$validator->checkNull( $datObj->OperationId );
			if( !is_null( $datObj->OperationId ) ) {
				$validator->checkType( $datObj->OperationId, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubPreviewDossiersRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->DossierIDs)){
			if (is_object($this->DossierIDs[0])){
				foreach ($this->DossierIDs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Targets)){
			if (is_object($this->Targets[0])){
				foreach ($this->Targets as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->RequestInfo)){
			if (is_object($this->RequestInfo[0])){
				foreach ($this->RequestInfo as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

