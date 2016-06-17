<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflLockObjectsRequest
{
	public $Ticket;
	public $HaveVersions;

	/**
	 * @param string               $Ticket                    
	 * @param ObjectVersion[]      $HaveVersions              
	 */
	public function __construct( $Ticket=null, $HaveVersions=null )
	{
		$this->Ticket               = $Ticket;
		$this->HaveVersions         = $HaveVersions;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'LockObjectsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'HaveVersions' ) ) {
			$validator->enterPath( 'HaveVersions' );
			$validator->checkNull( $datObj->HaveVersions );
			if( !is_null( $datObj->HaveVersions ) ) {
				$validator->checkType( $datObj->HaveVersions, 'array' );
				if( !empty($datObj->HaveVersions) ) foreach( $datObj->HaveVersions as $listItem ) {
					$validator->enterPath( 'ObjectVersion' );
					$validator->checkType( $listItem, 'ObjectVersion' );
					WflObjectVersionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflLockObjectsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->HaveVersions)){
			if (is_object($this->HaveVersions[0])){
				foreach ($this->HaveVersions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

