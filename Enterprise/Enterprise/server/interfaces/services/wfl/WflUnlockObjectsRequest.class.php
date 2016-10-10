<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflUnlockObjectsRequest
{
	public $Ticket;
	public $IDs;
	public $ReadMessageIDs;
	public $MessageList;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $IDs                       
	 * @param string[]             $ReadMessageIDs            Nullable.
	 * @param MessageList          $MessageList               Nullable.
	 */
	public function __construct( $Ticket=null, $IDs=null, $ReadMessageIDs=null, $MessageList=null )
	{
		$this->Ticket               = $Ticket;
		$this->IDs                  = $IDs;
		$this->ReadMessageIDs       = $ReadMessageIDs;
		$this->MessageList          = $MessageList;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'UnlockObjectsRequest' );
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
		if( $validator->checkExist( $datObj, 'MessageList' ) ) {
			$validator->enterPath( 'MessageList' );
			if( !is_null( $datObj->MessageList ) ) {
				$validator->checkType( $datObj->MessageList, 'MessageList' );
				WflMessageListValidator::validate( $validator, $datObj->MessageList );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflUnlockObjectsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->IDs)){
			if (is_object($this->IDs[0])){
				foreach ($this->IDs as $complexField){
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
		if( is_object( $this->MessageList ) ) {
			$this->MessageList->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

