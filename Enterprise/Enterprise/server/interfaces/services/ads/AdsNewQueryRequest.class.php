<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdsNewQueryRequest
{
	public $Ticket;
	public $DatasourceID;
	public $Name;
	public $Query;
	public $Interface;
	public $Comment;
	public $RecordID;
	public $RecordFamily;

	/**
	 * @param string               $Ticket                    
	 * @param string               $DatasourceID              
	 * @param string               $Name                      
	 * @param string               $Query                     
	 * @param string               $Interface                 
	 * @param string               $Comment                   
	 * @param string               $RecordID                  
	 * @param string               $RecordFamily              
	 */
	public function __construct( $Ticket=null, $DatasourceID=null, $Name=null, $Query=null, $Interface=null, $Comment=null, $RecordID=null, $RecordFamily=null )
	{
		$this->Ticket               = $Ticket;
		$this->DatasourceID         = $DatasourceID;
		$this->Name                 = $Name;
		$this->Query                = $Query;
		$this->Interface            = $Interface;
		$this->Comment              = $Comment;
		$this->RecordID             = $RecordID;
		$this->RecordFamily         = $RecordFamily;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'NewQueryRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DatasourceID' ) ) {
			$validator->enterPath( 'DatasourceID' );
			$validator->checkNull( $datObj->DatasourceID );
			if( !is_null( $datObj->DatasourceID ) ) {
				$validator->checkType( $datObj->DatasourceID, 'string' );
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
		if( $validator->checkExist( $datObj, 'Query' ) ) {
			$validator->enterPath( 'Query' );
			$validator->checkNull( $datObj->Query );
			if( !is_null( $datObj->Query ) ) {
				$validator->checkType( $datObj->Query, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Interface' ) ) {
			$validator->enterPath( 'Interface' );
			$validator->checkNull( $datObj->Interface );
			if( !is_null( $datObj->Interface ) ) {
				$validator->checkType( $datObj->Interface, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Comment' ) ) {
			$validator->enterPath( 'Comment' );
			$validator->checkNull( $datObj->Comment );
			if( !is_null( $datObj->Comment ) ) {
				$validator->checkType( $datObj->Comment, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RecordID' ) ) {
			$validator->enterPath( 'RecordID' );
			$validator->checkNull( $datObj->RecordID );
			if( !is_null( $datObj->RecordID ) ) {
				$validator->checkType( $datObj->RecordID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RecordFamily' ) ) {
			$validator->enterPath( 'RecordFamily' );
			$validator->checkNull( $datObj->RecordFamily );
			if( !is_null( $datObj->RecordFamily ) ) {
				$validator->checkType( $datObj->RecordFamily, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsNewQueryRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

