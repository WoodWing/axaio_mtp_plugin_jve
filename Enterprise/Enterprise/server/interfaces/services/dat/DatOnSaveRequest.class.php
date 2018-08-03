<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class DatOnSaveRequest
{
	public $Ticket;
	public $DatasourceID;
	public $Placements;

	/**
	 * @param string               $Ticket                    
	 * @param string               $DatasourceID              
	 * @param DatPlacement[]       $Placements                
	 */
	public function __construct( $Ticket=null, $DatasourceID=null, $Placements=null )
	{
		$this->Ticket               = $Ticket;
		$this->DatasourceID         = $DatasourceID;
		$this->Placements           = $Placements;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/dat/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'OnSaveRequest' );
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
		if( $validator->checkExist( $datObj, 'Placements' ) ) {
			$validator->enterPath( 'Placements' );
			$validator->checkNull( $datObj->Placements );
			if( !is_null( $datObj->Placements ) ) {
				$validator->checkType( $datObj->Placements, 'array' );
				if( !empty($datObj->Placements) ) foreach( $datObj->Placements as $listItem ) {
					$validator->enterPath( 'Placement' );
					$validator->checkType( $listItem, 'DatPlacement' );
					DatPlacementValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.DatOnSaveRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Placements)){
			if (is_object($this->Placements[0])){
				foreach ($this->Placements as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

