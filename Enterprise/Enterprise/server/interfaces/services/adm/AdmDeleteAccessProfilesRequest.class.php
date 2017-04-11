<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmDeleteAccessProfilesRequest
{
	public $Ticket;
	public $AccessProfileIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer[]            $AccessProfileIds          
	 */
	public function __construct( $Ticket=null, $AccessProfileIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->AccessProfileIds     = $AccessProfileIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteAccessProfilesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AccessProfileIds' ) ) {
			$validator->enterPath( 'AccessProfileIds' );
			$validator->checkNull( $datObj->AccessProfileIds );
			if( !is_null( $datObj->AccessProfileIds ) ) {
				$validator->checkType( $datObj->AccessProfileIds, 'array' );
				if( !empty($datObj->AccessProfileIds) ) foreach( $datObj->AccessProfileIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteAccessProfilesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->AccessProfileIds)){
			if (is_object($this->AccessProfileIds[0])){
				foreach ($this->AccessProfileIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

