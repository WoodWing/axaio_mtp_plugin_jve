<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmDeleteStatusesRequest
{
	public $Ticket;
	public $StatusIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer[]            $StatusIds                 
	 */
	public function __construct( $Ticket=null, $StatusIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->StatusIds            = $StatusIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteStatusesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'StatusIds' ) ) {
			$validator->enterPath( 'StatusIds' );
			$validator->checkNull( $datObj->StatusIds );
			if( !is_null( $datObj->StatusIds ) ) {
				$validator->checkType( $datObj->StatusIds, 'array' );
				if( !empty($datObj->StatusIds) ) foreach( $datObj->StatusIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteStatusesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->StatusIds)){
			if (is_object($this->StatusIds[0])){
				foreach ($this->StatusIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

