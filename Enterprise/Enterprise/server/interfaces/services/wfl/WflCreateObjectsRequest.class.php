<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCreateObjectsRequest
{
	public $Ticket;
	public $Lock;
	public $Objects;
	public $Messages;
	public $AutoNaming;
	public $ReplaceGUIDs;

	/**
	 * @param string               $Ticket                    
	 * @param boolean              $Lock                      
	 * @param Object[]             $Objects                   
	 * @param Message[]            $Messages                  Nullable.
	 * @param boolean              $AutoNaming                Nullable.
	 * @param boolean              $ReplaceGUIDs              Nullable.
	 */
	public function __construct( $Ticket=null, $Lock=null, $Objects=null, $Messages=null, $AutoNaming=null, $ReplaceGUIDs=null )
	{
		$this->Ticket               = $Ticket;
		$this->Lock                 = $Lock;
		$this->Objects              = $Objects;
		$this->Messages             = $Messages;
		$this->AutoNaming           = $AutoNaming;
		$this->ReplaceGUIDs         = $ReplaceGUIDs;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CreateObjectsRequest' );
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
		if( $validator->checkExist( $datObj, 'Messages' ) ) {
			$validator->enterPath( 'Messages' );
			if( !is_null( $datObj->Messages ) ) {
				$validator->checkType( $datObj->Messages, 'array' );
				if( !empty($datObj->Messages) ) foreach( $datObj->Messages as $listItem ) {
					$validator->enterPath( 'Message' );
					$validator->checkType( $listItem, 'Message' );
					WflMessageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AutoNaming' ) ) {
			$validator->enterPath( 'AutoNaming' );
			if( !is_null( $datObj->AutoNaming ) ) {
				$validator->checkType( $datObj->AutoNaming, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReplaceGUIDs' ) ) {
			$validator->enterPath( 'ReplaceGUIDs' );
			if( !is_null( $datObj->ReplaceGUIDs ) ) {
				$validator->checkType( $datObj->ReplaceGUIDs, 'boolean' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCreateObjectsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->Lock)){ $this->Lock = ('true' == $this->Lock) ? true : false; }
		if (!is_null($this->AutoNaming)){ $this->AutoNaming = ('true' == $this->AutoNaming) ? true : false; }
		if (!is_null($this->ReplaceGUIDs)){ $this->ReplaceGUIDs = ('true' == $this->ReplaceGUIDs) ? true : false; }
		if (0 < count($this->Objects)){
			if (is_object($this->Objects[0])){
				foreach ($this->Objects as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Messages)){
			if (is_object($this->Messages[0])){
				foreach ($this->Messages as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return true; }
}

