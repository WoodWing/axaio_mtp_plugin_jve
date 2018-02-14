<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmDeleteUserGroupsRequest
{
	public $Ticket;
	public $GroupIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer[]            $GroupIds                  
	 */
	public function __construct( $Ticket=null, $GroupIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->GroupIds             = $GroupIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteUserGroupsRequest' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteUserGroupsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
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

