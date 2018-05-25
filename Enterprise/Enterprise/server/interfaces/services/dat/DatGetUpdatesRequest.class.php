<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class DatGetUpdatesRequest
{
	public $Ticket;
	public $UpdateID;
	public $ObjectID;

	/**
	 * @param string               $Ticket                    
	 * @param string               $UpdateID                  
	 * @param string               $ObjectID                  
	 */
	public function __construct( $Ticket=null, $UpdateID=null, $ObjectID=null )
	{
		$this->Ticket               = $Ticket;
		$this->UpdateID             = $UpdateID;
		$this->ObjectID             = $ObjectID;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/dat/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetUpdatesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UpdateID' ) ) {
			$validator->enterPath( 'UpdateID' );
			$validator->checkNull( $datObj->UpdateID );
			if( !is_null( $datObj->UpdateID ) ) {
				$validator->checkType( $datObj->UpdateID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectID' ) ) {
			$validator->enterPath( 'ObjectID' );
			$validator->checkNull( $datObj->ObjectID );
			if( !is_null( $datObj->ObjectID ) ) {
				$validator->checkType( $datObj->ObjectID, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.dat.DatGetUpdatesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

