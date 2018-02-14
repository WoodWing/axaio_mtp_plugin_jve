<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsSaveQueryFieldRequest
{
	public $Ticket;
	public $QueryID;
	public $Name;
	public $Priority;
	public $ReadOnly;

	/**
	 * @param string               $Ticket                    
	 * @param string               $QueryID                   
	 * @param string               $Name                      
	 * @param string               $Priority                  
	 * @param string               $ReadOnly                  
	 */
	public function __construct( $Ticket=null, $QueryID=null, $Name=null, $Priority=null, $ReadOnly=null )
	{
		$this->Ticket               = $Ticket;
		$this->QueryID              = $QueryID;
		$this->Name                 = $Name;
		$this->Priority             = $Priority;
		$this->ReadOnly             = $ReadOnly;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'SaveQueryFieldRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'QueryID' ) ) {
			$validator->enterPath( 'QueryID' );
			$validator->checkNull( $datObj->QueryID );
			if( !is_null( $datObj->QueryID ) ) {
				$validator->checkType( $datObj->QueryID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			$validator->checkNull( $datObj->Name );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Priority' ) ) {
			$validator->enterPath( 'Priority' );
			$validator->checkNull( $datObj->Priority );
			if( !is_null( $datObj->Priority ) ) {
				$validator->checkType( $datObj->Priority, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ReadOnly' ) ) {
			$validator->enterPath( 'ReadOnly' );
			$validator->checkNull( $datObj->ReadOnly );
			if( !is_null( $datObj->ReadOnly ) ) {
				$validator->checkType( $datObj->ReadOnly, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsSaveQueryFieldRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

