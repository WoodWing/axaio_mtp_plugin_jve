<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class DatHasUpdatesRequest
{
	public $Ticket;
	public $DatasourceID;
	public $FamilyValue;

	/**
	 * @param string               $Ticket                    
	 * @param string               $DatasourceID              
	 * @param string               $FamilyValue               Nullable.
	 */
	public function __construct( $Ticket=null, $DatasourceID=null, $FamilyValue=null )
	{
		$this->Ticket               = $Ticket;
		$this->DatasourceID         = $DatasourceID;
		$this->FamilyValue          = $FamilyValue;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/dat/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'HasUpdatesRequest' );
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
		if( $validator->checkExist( $datObj, 'FamilyValue' ) ) {
			$validator->enterPath( 'FamilyValue' );
			if( !is_null( $datObj->FamilyValue ) ) {
				$validator->checkType( $datObj->FamilyValue, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.DatHasUpdatesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

