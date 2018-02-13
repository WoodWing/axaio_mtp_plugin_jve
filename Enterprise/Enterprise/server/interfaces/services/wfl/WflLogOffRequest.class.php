<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflLogOffRequest
{
	public $Ticket;
	public $SaveSettings;
	public $Settings;
	public $ReadMessageIDs;
	public $MessageList;

	/**
	 * @param string               $Ticket                    
	 * @param boolean              $SaveSettings              Nullable.
	 * @param Setting[]            $Settings                  Nullable.
	 * @param string[]             $ReadMessageIDs            Nullable.
	 * @param MessageList          $MessageList               Nullable.
	 */
	public function __construct( $Ticket=null, $SaveSettings=null, $Settings=null, $ReadMessageIDs=null, $MessageList=null )
	{
		$this->Ticket               = $Ticket;
		$this->SaveSettings         = $SaveSettings;
		$this->Settings             = $Settings;
		$this->ReadMessageIDs       = $ReadMessageIDs;
		$this->MessageList          = $MessageList;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'LogOffRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SaveSettings' ) ) {
			$validator->enterPath( 'SaveSettings' );
			if( !is_null( $datObj->SaveSettings ) ) {
				$validator->checkType( $datObj->SaveSettings, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Settings' ) ) {
			$validator->enterPath( 'Settings' );
			if( !is_null( $datObj->Settings ) ) {
				$validator->checkType( $datObj->Settings, 'array' );
				if( !empty($datObj->Settings) ) foreach( $datObj->Settings as $listItem ) {
					$validator->enterPath( 'Setting' );
					$validator->checkType( $listItem, 'Setting' );
					WflSettingValidator::validate( $validator, $listItem );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflLogOffRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->SaveSettings)){ $this->SaveSettings = ('true' == $this->SaveSettings) ? true : false; }
		if (0 < count($this->Settings)){
			if (is_object($this->Settings[0])){
				foreach ($this->Settings as $complexField){
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

