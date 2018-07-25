<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflInstantiateTemplateRequest
{
	public $Ticket;
	public $Lock;
	public $Rendition;
	public $RequestInfo;
	public $TemplateId;
	public $Objects;

	/**
	 * @param string               $Ticket                    
	 * @param boolean              $Lock                      
	 * @param string               $Rendition                 
	 * @param string[]             $RequestInfo               Nullable.
	 * @param string               $TemplateId                
	 * @param Object[]             $Objects                   
	 */
	public function __construct( $Ticket=null, $Lock=null, $Rendition=null, $RequestInfo=null, $TemplateId=null, $Objects=null )
	{
		$this->Ticket               = $Ticket;
		$this->Lock                 = $Lock;
		$this->Rendition            = $Rendition;
		$this->RequestInfo          = $RequestInfo;
		$this->TemplateId           = $TemplateId;
		$this->Objects              = $Objects;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'InstantiateTemplateRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
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
		if( $validator->checkExist( $datObj, 'TemplateId' ) ) {
			$validator->enterPath( 'TemplateId' );
			$validator->checkNull( $datObj->TemplateId );
			if( !is_null( $datObj->TemplateId ) ) {
				$validator->checkType( $datObj->TemplateId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Objects' ) ) {
			$validator->enterPath( 'Objects' );
			$validator->checkNull( $datObj->Objects );
			if( !is_null( $datObj->Objects ) ) {
				$validator->checkType( $datObj->Objects, 'array' );
				if( !empty($datObj->Objects) ) foreach( $datObj->Objects as $listItem ) {
					$validator->enterPath( 'Object' );
					$validator->checkType( $listItem, 'Object' );
					WflObjectValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflInstantiateTemplateRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Lock)){ $this->Lock = ('true' == $this->Lock) ? true : false; }
		if (0 < count($this->RequestInfo)){
			if (is_object($this->RequestInfo[0])){
				foreach ($this->RequestInfo as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Objects)){
			if (is_object($this->Objects[0])){
				foreach ($this->Objects as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

