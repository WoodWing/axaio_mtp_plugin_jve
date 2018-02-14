<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsCopyDatasourceRequest
{
	public $Ticket;
	public $DatasourceID;
	public $NewName;
	public $CopyQueries;

	/**
	 * @param string               $Ticket                    
	 * @param string               $DatasourceID              
	 * @param string               $NewName                   
	 * @param string               $CopyQueries               
	 */
	public function __construct( $Ticket=null, $DatasourceID=null, $NewName=null, $CopyQueries=null )
	{
		$this->Ticket               = $Ticket;
		$this->DatasourceID         = $DatasourceID;
		$this->NewName              = $NewName;
		$this->CopyQueries          = $CopyQueries;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CopyDatasourceRequest' );
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
		if( $validator->checkExist( $datObj, 'NewName' ) ) {
			$validator->enterPath( 'NewName' );
			$validator->checkNull( $datObj->NewName );
			if( !is_null( $datObj->NewName ) ) {
				$validator->checkType( $datObj->NewName, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CopyQueries' ) ) {
			$validator->enterPath( 'CopyQueries' );
			$validator->checkNull( $datObj->CopyQueries );
			if( !is_null( $datObj->CopyQueries ) ) {
				$validator->checkType( $datObj->CopyQueries, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsCopyDatasourceRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

