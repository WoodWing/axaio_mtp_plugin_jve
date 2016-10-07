<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class PubGetPublishInfoRequest
{
	public $Ticket;
	public $DossierIDs;
	public $Targets;
	public $PublishedDossiers;
	public $PublishedIssue;
	public $RequestInfo;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $DossierIDs                Nullable.
	 * @param PubPublishTarget[]   $Targets                   Nullable.
	 * @param PubPublishedDossier[] $PublishedDossiers         Nullable.
	 * @param PubPublishedIssue    $PublishedIssue            Nullable.
	 * @param string[]             $RequestInfo               Nullable.
	 */
	public function __construct( $Ticket=null, $DossierIDs=null, $Targets=null, $PublishedDossiers=null, $PublishedIssue=null, $RequestInfo=null )
	{
		$this->Ticket               = $Ticket;
		$this->DossierIDs           = $DossierIDs;
		$this->Targets              = $Targets;
		$this->PublishedDossiers    = $PublishedDossiers;
		$this->PublishedIssue       = $PublishedIssue;
		$this->RequestInfo          = $RequestInfo;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pub/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetPublishInfoRequest' );
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
		if( $validator->checkExist( $datObj, 'PublishedDossiers' ) ) {
			$validator->enterPath( 'PublishedDossiers' );
			if( !is_null( $datObj->PublishedDossiers ) ) {
				$validator->checkType( $datObj->PublishedDossiers, 'array' );
				if( !empty($datObj->PublishedDossiers) ) foreach( $datObj->PublishedDossiers as $listItem ) {
					$validator->enterPath( 'PublishedDossier' );
					$validator->checkType( $listItem, 'PubPublishedDossier' );
					PubPublishedDossierValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedIssue' ) ) {
			$validator->enterPath( 'PublishedIssue' );
			if( !is_null( $datObj->PublishedIssue ) ) {
				$validator->checkType( $datObj->PublishedIssue, 'PubPublishedIssue' );
				PubPublishedIssueValidator::validate( $validator, $datObj->PublishedIssue );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubGetPublishInfoRequest'; } // AMF object type mapping

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
		if (0 < count($this->PublishedDossiers)){
			if (is_object($this->PublishedDossiers[0])){
				foreach ($this->PublishedDossiers as $complexField){
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
		if( is_object( $this->PublishedIssue ) ) {
			$this->PublishedIssue->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

