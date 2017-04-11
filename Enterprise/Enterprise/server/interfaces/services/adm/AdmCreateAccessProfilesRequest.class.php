<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmCreateAccessProfilesRequest
{
	public $Ticket;
	public $RequestModes;
	public $AccessProfiles;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $RequestModes              Nullable.
	 * @param AdmAccessProfile[]   $AccessProfiles            
	 */
	public function __construct( $Ticket=null, $RequestModes=null, $AccessProfiles=null )
	{
		$this->Ticket               = $Ticket;
		$this->RequestModes         = $RequestModes;
		$this->AccessProfiles       = $AccessProfiles;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CreateAccessProfilesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestModes' ) ) {
			$validator->enterPath( 'RequestModes' );
			if( !is_null( $datObj->RequestModes ) ) {
				$validator->checkType( $datObj->RequestModes, 'array' );
				if( !empty($datObj->RequestModes) ) foreach( $datObj->RequestModes as $listItem ) {
					$validator->enterPath( 'Mode' );
					$validator->checkType( $listItem, 'string' );
					AdmModeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AccessProfiles' ) ) {
			$validator->enterPath( 'AccessProfiles' );
			$validator->checkNull( $datObj->AccessProfiles );
			if( !is_null( $datObj->AccessProfiles ) ) {
				$validator->checkType( $datObj->AccessProfiles, 'array' );
				if( !empty($datObj->AccessProfiles) ) foreach( $datObj->AccessProfiles as $listItem ) {
					$validator->enterPath( 'AccessProfile' );
					$validator->checkType( $listItem, 'AdmAccessProfile' );
					AdmAccessProfileValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCreateAccessProfilesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->RequestModes)){
			if (is_object($this->RequestModes[0])){
				foreach ($this->RequestModes as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->AccessProfiles)){
			if (is_object($this->AccessProfiles[0])){
				foreach ($this->AccessProfiles as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

