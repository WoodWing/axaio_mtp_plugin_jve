<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmAddUsersToGroupRequest
{
	public $Ticket;
	public $UserIds;
	public $GroupId;

	/**
	 * @param string               $Ticket                    
	 * @param integer[]            $UserIds                   
	 * @param integer              $GroupId                   
	 */
	public function __construct( $Ticket=null, $UserIds=null, $GroupId=null )
	{
		$this->Ticket               = $Ticket;
		$this->UserIds              = $UserIds;
		$this->GroupId              = $GroupId;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'AddUsersToGroupRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserIds' ) ) {
			$validator->enterPath( 'UserIds' );
			$validator->checkNull( $datObj->UserIds );
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
		if( $validator->checkExist( $datObj, 'GroupId' ) ) {
			$validator->enterPath( 'GroupId' );
			$validator->checkNull( $datObj->GroupId );
			if( !is_null( $datObj->GroupId ) ) {
				$validator->checkType( $datObj->GroupId, 'integer' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmAddUsersToGroupRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->GroupId)){ $this->GroupId = null; }
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
