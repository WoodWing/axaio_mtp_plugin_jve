<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflDeleteUserSettingsRequest
{
	public $Ticket;
	public $Settings;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $Settings                  
	 */
	public function __construct( $Ticket=null, $Settings=null )
	{
		$this->Ticket               = $Ticket;
		$this->Settings             = $Settings;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteUserSettingsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Settings' ) ) {
			$validator->enterPath( 'Settings' );
			$validator->checkNull( $datObj->Settings );
			if( !is_null( $datObj->Settings ) ) {
				$validator->checkType( $datObj->Settings, 'array' );
				if( !empty($datObj->Settings) ) foreach( $datObj->Settings as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflDeleteUserSettingsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Settings)){
			if (is_object($this->Settings[0])){
				foreach ($this->Settings as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

