<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmDeleteUsersRequest
{
	public $Ticket;
	public $UserIds;

	/**
	 * @param string               $Ticket                    
	 * @param Id[]                 $UserIds                   
	 */
	public function __construct( $Ticket=null, $UserIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->UserIds              = $UserIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteUsersRequest' );
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
					$validator->enterPath( 'integer' );
					$validator->checkType( $listItem, 'integer' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteUsersRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
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

