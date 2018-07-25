<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetObjectsRequest
{
	public $Ticket;
	public $IDs;
	public $Lock;
	public $Rendition;
	public $RequestInfo;
	public $HaveVersions;
	public $Areas;
	public $EditionId;
	public $SupportedContentSources;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $IDs                       
	 * @param boolean              $Lock                      
	 * @param string               $Rendition                 
	 * @param string[]             $RequestInfo               Nullable.
	 * @param ObjectVersion[]      $HaveVersions              Nullable.
	 * @param string[]             $Areas                     Nullable.
	 * @param string               $EditionId                 Nullable.
	 * @param string[]             $SupportedContentSources   Nullable.
	 */
	public function __construct( $Ticket=null, $IDs=null, $Lock=null, $Rendition=null, $RequestInfo=null, $HaveVersions=null, $Areas=null, $EditionId=null, $SupportedContentSources=null )
	{
		$this->Ticket               = $Ticket;
		$this->IDs                  = $IDs;
		$this->Lock                 = $Lock;
		$this->Rendition            = $Rendition;
		$this->RequestInfo          = $RequestInfo;
		$this->HaveVersions         = $HaveVersions;
		$this->Areas                = $Areas;
		$this->EditionId            = $EditionId;
		$this->SupportedContentSources = $SupportedContentSources;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetObjectsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IDs' ) ) {
			$validator->enterPath( 'IDs' );
			$validator->checkNull( $datObj->IDs );
			if( !is_null( $datObj->IDs ) ) {
				$validator->checkType( $datObj->IDs, 'array' );
				if( !empty($datObj->IDs) ) foreach( $datObj->IDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Lock' ) ) {
			$validator->enterPath( 'Lock' );
			$validator->checkNull( $datObj->Lock );
			if( !is_null( $datObj->Lock ) ) {
				$validator->checkType( $datObj->Lock, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Rendition' ) ) {
			$validator->enterPath( 'Rendition' );
			$validator->checkNull( $datObj->Rendition );
			if( !is_null( $datObj->Rendition ) ) {
				$validator->checkType( $datObj->Rendition, 'string' );
				WflRenditionTypeValidator::validate( $validator, $datObj->Rendition );
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
		if( $validator->checkExist( $datObj, 'HaveVersions' ) ) {
			$validator->enterPath( 'HaveVersions' );
			if( !is_null( $datObj->HaveVersions ) ) {
				$validator->checkType( $datObj->HaveVersions, 'array' );
				if( !empty($datObj->HaveVersions) ) foreach( $datObj->HaveVersions as $listItem ) {
					$validator->enterPath( 'ObjectVersion' );
					$validator->checkType( $listItem, 'ObjectVersion' );
					WflObjectVersionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Areas' ) ) {
			$validator->enterPath( 'Areas' );
			if( !is_null( $datObj->Areas ) ) {
				$validator->checkType( $datObj->Areas, 'array' );
				if( !empty($datObj->Areas) ) foreach( $datObj->Areas as $listItem ) {
					$validator->enterPath( 'AreaType' );
					$validator->checkType( $listItem, 'string' );
					WflAreaTypeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EditionId' ) ) {
			$validator->enterPath( 'EditionId' );
			if( !is_null( $datObj->EditionId ) ) {
				$validator->checkType( $datObj->EditionId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SupportedContentSources' ) ) {
			$validator->enterPath( 'SupportedContentSources' );
			if( !is_null( $datObj->SupportedContentSources ) ) {
				$validator->checkType( $datObj->SupportedContentSources, 'array' );
				if( !empty($datObj->SupportedContentSources) ) foreach( $datObj->SupportedContentSources as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetObjectsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Lock)){ $this->Lock = ('true' == $this->Lock) ? true : false; }
		if (0 < count($this->IDs)){
			if (is_object($this->IDs[0])){
				foreach ($this->IDs as $complexField){
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
		if (0 < count($this->HaveVersions)){
			if (is_object($this->HaveVersions[0])){
				foreach ($this->HaveVersions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Areas)){
			if (is_object($this->Areas[0])){
				foreach ($this->Areas as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->SupportedContentSources)){
			if (is_object($this->SupportedContentSources[0])){
				foreach ($this->SupportedContentSources as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

