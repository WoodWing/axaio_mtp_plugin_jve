<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmModifyStatusesRequest
{
	public $Ticket;
	public $Statuses;

	/**
	 * @param string               $Ticket                    
	 * @param AdmStatus[]          $Statuses                  
	 */
	public function __construct( $Ticket=null, $Statuses=null )
	{
		$this->Ticket               = $Ticket;
		$this->Statuses             = $Statuses;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'ModifyStatusesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Statuses' ) ) {
			$validator->enterPath( 'Statuses' );
			$validator->checkNull( $datObj->Statuses );
			if( !is_null( $datObj->Statuses ) ) {
				$validator->checkType( $datObj->Statuses, 'array' );
				if( !empty($datObj->Statuses) ) foreach( $datObj->Statuses as $listItem ) {
					$validator->enterPath( 'Status' );
					$validator->checkType( $listItem, 'AdmStatus' );
					AdmStatusValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmModifyStatusesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Statuses)){
			if (is_object($this->Statuses[0])){
				foreach ($this->Statuses as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

