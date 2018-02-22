<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflChangePasswordRequest
{
	public $Ticket;
	public $Old;
	public $New;
	public $Name;

	/**
	 * @param string               $Ticket                    Nullable.
	 * @param string               $Old                       
	 * @param string               $New                       
	 * @param string               $Name                      Nullable.
	 */
	public function __construct( $Ticket=null, $Old=null, $New=null, $Name=null )
	{
		$this->Ticket               = $Ticket;
		$this->Old                  = $Old;
		$this->New                  = $New;
		$this->Name                 = $Name;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'ChangePasswordRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Old' ) ) {
			$validator->enterPath( 'Old' );
			$validator->checkNull( $datObj->Old );
			if( !is_null( $datObj->Old ) ) {
				$validator->checkType( $datObj->Old, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'New' ) ) {
			$validator->enterPath( 'New' );
			$validator->checkNull( $datObj->New );
			if( !is_null( $datObj->New ) ) {
				$validator->checkType( $datObj->New, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Name' ) ) {
			$validator->enterPath( 'Name' );
			if( !is_null( $datObj->Name ) ) {
				$validator->checkType( $datObj->Name, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflChangePasswordRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

