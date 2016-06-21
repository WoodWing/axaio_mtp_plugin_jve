<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflRestoreVersionRequest
{
	public $Ticket;
	public $ID;
	public $Version;

	/**
	 * @param string               $Ticket                    
	 * @param string               $ID                        
	 * @param string               $Version                   
	 */
	public function __construct( $Ticket=null, $ID=null, $Version=null )
	{
		$this->Ticket               = $Ticket;
		$this->ID                   = $ID;
		$this->Version              = $Version;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'RestoreVersionRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			$validator->checkNull( $datObj->ID );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Version' ) ) {
			$validator->enterPath( 'Version' );
			$validator->checkNull( $datObj->Version );
			if( !is_null( $datObj->Version ) ) {
				$validator->checkType( $datObj->Version, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflRestoreVersionRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

