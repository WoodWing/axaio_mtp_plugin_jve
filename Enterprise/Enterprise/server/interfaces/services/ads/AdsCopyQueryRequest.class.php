<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsCopyQueryRequest
{
	public $Ticket;
	public $QueryID;
	public $TargetID;
	public $NewName;
	public $CopyFields;

	/**
	 * @param string               $Ticket                    
	 * @param string               $QueryID                   
	 * @param string               $TargetID                  
	 * @param string               $NewName                   
	 * @param string               $CopyFields                
	 */
	public function __construct( $Ticket=null, $QueryID=null, $TargetID=null, $NewName=null, $CopyFields=null )
	{
		$this->Ticket               = $Ticket;
		$this->QueryID              = $QueryID;
		$this->TargetID             = $TargetID;
		$this->NewName              = $NewName;
		$this->CopyFields           = $CopyFields;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CopyQueryRequest' );
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
		if( $validator->checkExist( $datObj, 'TargetID' ) ) {
			$validator->enterPath( 'TargetID' );
			$validator->checkNull( $datObj->TargetID );
			if( !is_null( $datObj->TargetID ) ) {
				$validator->checkType( $datObj->TargetID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'NewName' ) ) {
			$validator->enterPath( 'NewName' );
			$validator->checkNull( $datObj->NewName );
			if( !is_null( $datObj->NewName ) ) {
				$validator->checkType( $datObj->NewName, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CopyFields' ) ) {
			$validator->enterPath( 'CopyFields' );
			$validator->checkNull( $datObj->CopyFields );
			if( !is_null( $datObj->CopyFields ) ) {
				$validator->checkType( $datObj->CopyFields, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsCopyQueryRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

