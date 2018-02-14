<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdsSaveDatasourceRequest
{
	public $Ticket;
	public $DatasourceID;
	public $Name;
	public $Bidirectional;

	/**
	 * @param string               $Ticket                    
	 * @param string               $DatasourceID              
	 * @param string               $Name                      
	 * @param string               $Bidirectional             
	 */
	public function __construct( $Ticket=null, $DatasourceID=null, $Name=null, $Bidirectional=null )
	{
		$this->Ticket               = $Ticket;
		$this->DatasourceID         = $DatasourceID;
		$this->Name                 = $Name;
		$this->Bidirectional        = $Bidirectional;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/ads/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'SaveDatasourceRequest' );
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
		if( $validator->checkExist( $datObj, 'Bidirectional' ) ) {
			$validator->enterPath( 'Bidirectional' );
			$validator->checkNull( $datObj->Bidirectional );
			if( !is_null( $datObj->Bidirectional ) ) {
				$validator->checkType( $datObj->Bidirectional, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.ads.AdsSaveDatasourceRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

