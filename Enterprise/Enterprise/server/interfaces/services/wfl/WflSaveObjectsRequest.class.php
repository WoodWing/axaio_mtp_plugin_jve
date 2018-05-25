<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflSaveObjectsRequest
{
	public $Ticket;
	public $CreateVersion;
	public $ForceCheckIn;
	public $Unlock;
	public $Objects;
	public $ReadMessageIDs;
	public $Messages;

	/**
	 * @param string               $Ticket                    
	 * @param boolean              $CreateVersion             
	 * @param boolean              $ForceCheckIn              
	 * @param boolean              $Unlock                    
	 * @param Object[]             $Objects                   
	 * @param string[]             $ReadMessageIDs            Nullable.
	 * @param Message[]            $Messages                  Nullable.
	 */
	public function __construct( $Ticket=null, $CreateVersion=null, $ForceCheckIn=null, $Unlock=null, $Objects=null, $ReadMessageIDs=null, $Messages=null )
	{
		$this->Ticket               = $Ticket;
		$this->CreateVersion        = $CreateVersion;
		$this->ForceCheckIn         = $ForceCheckIn;
		$this->Unlock               = $Unlock;
		$this->Objects              = $Objects;
		$this->ReadMessageIDs       = $ReadMessageIDs;
		$this->Messages             = $Messages;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'SaveObjectsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CreateVersion' ) ) {
			$validator->enterPath( 'CreateVersion' );
			$validator->checkNull( $datObj->CreateVersion );
			if( !is_null( $datObj->CreateVersion ) ) {
				$validator->checkType( $datObj->CreateVersion, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ForceCheckIn' ) ) {
			$validator->enterPath( 'ForceCheckIn' );
			$validator->checkNull( $datObj->ForceCheckIn );
			if( !is_null( $datObj->ForceCheckIn ) ) {
				$validator->checkType( $datObj->ForceCheckIn, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Unlock' ) ) {
			$validator->enterPath( 'Unlock' );
			$validator->checkNull( $datObj->Unlock );
			if( !is_null( $datObj->Unlock ) ) {
				$validator->checkType( $datObj->Unlock, 'boolean' );
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
		if( $validator->checkExist( $datObj, 'ReadMessageIDs' ) ) {
			$validator->enterPath( 'ReadMessageIDs' );
			if( !is_null( $datObj->ReadMessageIDs ) ) {
				$validator->checkType( $datObj->ReadMessageIDs, 'array' );
				if( !empty($datObj->ReadMessageIDs) ) foreach( $datObj->ReadMessageIDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSaveObjectsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->CreateVersion)){ $this->CreateVersion = ('true' == $this->CreateVersion) ? true : false; }
		if (!is_null($this->ForceCheckIn)){ $this->ForceCheckIn = ('true' == $this->ForceCheckIn) ? true : false; }
		if (!is_null($this->Unlock)){ $this->Unlock = ('true' == $this->Unlock) ? true : false; }
		if (0 < count($this->Objects)){
			if (is_object($this->Objects[0])){
				foreach ($this->Objects as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->ReadMessageIDs)){
			if (is_object($this->ReadMessageIDs[0])){
				foreach ($this->ReadMessageIDs as $complexField){
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

