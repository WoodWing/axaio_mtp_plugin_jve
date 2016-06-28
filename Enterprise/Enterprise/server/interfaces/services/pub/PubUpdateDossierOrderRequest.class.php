<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class PubUpdateDossierOrderRequest
{
	public $Ticket;
	public $Target;
	public $NewOrder;
	public $OriginalOrder;

	/**
	 * @param string               $Ticket                    
	 * @param PubPublishTarget     $Target                    
	 * @param string[]             $NewOrder                  
	 * @param string[]             $OriginalOrder             
	 */
	public function __construct( $Ticket=null, $Target=null, $NewOrder=null, $OriginalOrder=null )
	{
		$this->Ticket               = $Ticket;
		$this->Target               = $Target;
		$this->NewOrder             = $NewOrder;
		$this->OriginalOrder        = $OriginalOrder;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pub/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'UpdateDossierOrderRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Target' ) ) {
			$validator->enterPath( 'Target' );
			$validator->checkNull( $datObj->Target );
			if( !is_null( $datObj->Target ) ) {
				$validator->checkType( $datObj->Target, 'PubPublishTarget' );
				PubPublishTargetValidator::validate( $validator, $datObj->Target );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'NewOrder' ) ) {
			$validator->enterPath( 'NewOrder' );
			$validator->checkNull( $datObj->NewOrder );
			if( !is_null( $datObj->NewOrder ) ) {
				$validator->checkType( $datObj->NewOrder, 'array' );
				if( !empty($datObj->NewOrder) ) foreach( $datObj->NewOrder as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'OriginalOrder' ) ) {
			$validator->enterPath( 'OriginalOrder' );
			$validator->checkNull( $datObj->OriginalOrder );
			if( !is_null( $datObj->OriginalOrder ) ) {
				$validator->checkType( $datObj->OriginalOrder, 'array' );
				if( !empty($datObj->OriginalOrder) ) foreach( $datObj->OriginalOrder as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubUpdateDossierOrderRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->NewOrder)){
			if (is_object($this->NewOrder[0])){
				foreach ($this->NewOrder as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->OriginalOrder)){
			if (is_object($this->OriginalOrder[0])){
				foreach ($this->OriginalOrder as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

