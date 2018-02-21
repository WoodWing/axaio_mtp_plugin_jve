<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetUsersRequest
{
	public $Ticket;
	public $RequestModes;
	public $GroupId;
	public $UserIds;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $RequestModes              
	 * @param integer              $GroupId                   Nullable.
	 * @param integer[]            $UserIds                   Nullable.
	 */
	public function __construct( $Ticket=null, $RequestModes=null, $GroupId=null, $UserIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->RequestModes         = $RequestModes;
		$this->GroupId              = $GroupId;
		$this->UserIds              = $UserIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetUsersRequest' );
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
			$validator->checkNull( $datObj->RequestModes );
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
		if( $validator->checkExist( $datObj, 'GroupId' ) ) {
			$validator->enterPath( 'GroupId' );
			if( !is_null( $datObj->GroupId ) ) {
				$validator->checkType( $datObj->GroupId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserIds' ) ) {
			$validator->enterPath( 'UserIds' );
			if( !is_null( $datObj->UserIds ) ) {
				$validator->checkType( $datObj->UserIds, 'array' );
				if( !empty($datObj->UserIds) ) foreach( $datObj->UserIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetUsersRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->GroupId)){ $this->GroupId = null; }
		if (0 < count($this->RequestModes)){
			if (is_object($this->RequestModes[0])){
				foreach ($this->RequestModes as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->UserIds)){
			if (is_object($this->UserIds[0])){
				foreach ($this->UserIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}
