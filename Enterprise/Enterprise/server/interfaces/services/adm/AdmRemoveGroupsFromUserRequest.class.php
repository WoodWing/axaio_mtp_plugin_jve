<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmRemoveGroupsFromUserRequest
{
	public $Ticket;
	public $GroupIds;
	public $UserId;

	/**
	 * @param string               $Ticket                    
	 * @param integer[]            $GroupIds                  
	 * @param integer              $UserId                    
	 */
	public function __construct( $Ticket=null, $GroupIds=null, $UserId=null )
	{
		$this->Ticket               = $Ticket;
		$this->GroupIds             = $GroupIds;
		$this->UserId               = $UserId;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'RemoveGroupsFromUserRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'GroupIds' ) ) {
			$validator->enterPath( 'GroupIds' );
			$validator->checkNull( $datObj->GroupIds );
			if( !is_null( $datObj->GroupIds ) ) {
				$validator->checkType( $datObj->GroupIds, 'array' );
				if( !empty($datObj->GroupIds) ) foreach( $datObj->GroupIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserId' ) ) {
			$validator->enterPath( 'UserId' );
			$validator->checkNull( $datObj->UserId );
			if( !is_null( $datObj->UserId ) ) {
				$validator->checkType( $datObj->UserId, 'integer' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmRemoveGroupsFromUserRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->UserId)){ $this->UserId = null; }
		if (0 < count($this->GroupIds)){
			if (is_object($this->GroupIds[0])){
				foreach ($this->GroupIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

