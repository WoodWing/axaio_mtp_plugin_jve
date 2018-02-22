<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflSendMessagesRequest
{
	public $Ticket;
	public $Messages;
	public $MessageList;

	/**
	 * @param string               $Ticket                    
	 * @param Message[]            $Messages                  Nullable.
	 * @param MessageList          $MessageList               Nullable.
	 */
	public function __construct( $Ticket=null, $Messages=null, $MessageList=null )
	{
		$this->Ticket               = $Ticket;
		$this->Messages             = $Messages;
		$this->MessageList          = $MessageList;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'SendMessagesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSendMessagesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Messages)){
			if (is_object($this->Messages[0])){
				foreach ($this->Messages as $complexField){
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

