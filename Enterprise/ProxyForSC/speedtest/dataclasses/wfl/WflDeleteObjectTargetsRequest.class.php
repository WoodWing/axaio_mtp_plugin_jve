<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflDeleteObjectTargetsRequest
{
	public $Ticket;
	public $IDs;
	public $Targets;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $IDs                       
	 * @param Target[]             $Targets                   
	 */
	public function __construct( $Ticket=null, $IDs=null, $Targets=null )
	{
		$this->Ticket               = $Ticket;
		$this->IDs                  = $IDs;
		$this->Targets              = $Targets;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteObjectTargetsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IDs' ) ) {
			$validator->enterPath( 'IDs' );
			$validator->checkNull( $datObj->IDs );
			if( !is_null( $datObj->IDs ) ) {
				$validator->checkType( $datObj->IDs, 'array' );
				if( !empty($datObj->IDs) ) foreach( $datObj->IDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Targets' ) ) {
			$validator->enterPath( 'Targets' );
			$validator->checkNull( $datObj->Targets );
			if( !is_null( $datObj->Targets ) ) {
				$validator->checkType( $datObj->Targets, 'array' );
				if( !empty($datObj->Targets) ) foreach( $datObj->Targets as $listItem ) {
					$validator->enterPath( 'Target' );
					$validator->checkType( $listItem, 'Target' );
					WflTargetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflDeleteObjectTargetsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->IDs)){
			if (is_object($this->IDs[0])){
				foreach ($this->IDs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Targets)){
			if (is_object($this->Targets[0])){
				foreach ($this->Targets as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

