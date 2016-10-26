<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class PubSetPublishInfoRequest
{
	public $Ticket;
	public $PublishedDossiers;
	public $PublishedIssue;
	public $RequestInfo;

	/**
	 * @param string               $Ticket                    
	 * @param PubPublishedDossier[] $PublishedDossiers         Nullable.
	 * @param PubPublishedIssue    $PublishedIssue            Nullable.
	 * @param string[]             $RequestInfo               Nullable.
	 */
	public function __construct( $Ticket=null, $PublishedDossiers=null, $PublishedIssue=null, $RequestInfo=null )
	{
		$this->Ticket               = $Ticket;
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

		$validator->enterPath( 'SetPublishInfoRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubSetPublishInfoRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
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

