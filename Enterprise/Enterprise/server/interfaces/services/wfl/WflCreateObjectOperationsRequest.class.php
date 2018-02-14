<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCreateObjectOperationsRequest
{
	public $Ticket;
	public $HaveVersion;
	public $Operations;

	/**
	 * @param string               $Ticket                    
	 * @param ObjectVersion        $HaveVersion               
	 * @param ObjectOperation[]    $Operations                
	 */
	public function __construct( $Ticket=null, $HaveVersion=null, $Operations=null )
	{
		$this->Ticket               = $Ticket;
		$this->HaveVersion          = $HaveVersion;
		$this->Operations           = $Operations;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CreateObjectOperationsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'HaveVersion' ) ) {
			$validator->enterPath( 'HaveVersion' );
			$validator->checkNull( $datObj->HaveVersion );
			if( !is_null( $datObj->HaveVersion ) ) {
				$validator->checkType( $datObj->HaveVersion, 'ObjectVersion' );
				WflObjectVersionValidator::validate( $validator, $datObj->HaveVersion );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Operations' ) ) {
			$validator->enterPath( 'Operations' );
			$validator->checkNull( $datObj->Operations );
			if( !is_null( $datObj->Operations ) ) {
				$validator->checkType( $datObj->Operations, 'array' );
				if( !empty($datObj->Operations) ) foreach( $datObj->Operations as $listItem ) {
					$validator->enterPath( 'ObjectOperation' );
					$validator->checkType( $listItem, 'ObjectOperation' );
					WflObjectOperationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCreateObjectOperationsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Operations)){
			if (is_object($this->Operations[0])){
				foreach ($this->Operations as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if( is_object( $this->HaveVersion ) ) {
			$this->HaveVersion->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

