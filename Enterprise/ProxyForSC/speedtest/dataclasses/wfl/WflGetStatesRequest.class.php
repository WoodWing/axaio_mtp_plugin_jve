<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflGetStatesRequest
{
	public $Ticket;
	public $ID;
	public $Publication;
	public $Issue;
	public $Section;
	public $Type;

	/**
	 * @param string               $Ticket                    
	 * @param string               $ID                        Nullable.
	 * @param Publication          $Publication               Nullable.
	 * @param Issue                $Issue                     Nullable.
	 * @param Category             $Section                   Nullable.
	 * @param string               $Type                      Nullable.
	 */
	public function __construct( $Ticket=null, $ID=null, $Publication=null, $Issue=null, $Section=null, $Type=null )
	{
		$this->Ticket               = $Ticket;
		$this->ID                   = $ID;
		$this->Publication          = $Publication;
		$this->Issue                = $Issue;
		$this->Section              = $Section;
		$this->Type                 = $Type;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetStatesRequest' );
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
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Publication' ) ) {
			$validator->enterPath( 'Publication' );
			if( !is_null( $datObj->Publication ) ) {
				$validator->checkType( $datObj->Publication, 'Publication' );
				WflPublicationValidator::validate( $validator, $datObj->Publication );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Issue' ) ) {
			$validator->enterPath( 'Issue' );
			if( !is_null( $datObj->Issue ) ) {
				$validator->checkType( $datObj->Issue, 'Issue' );
				WflIssueValidator::validate( $validator, $datObj->Issue );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Section' ) ) {
			$validator->enterPath( 'Section' );
			if( !is_null( $datObj->Section ) ) {
				$validator->checkType( $datObj->Section, 'Category' );
				WflCategoryValidator::validate( $validator, $datObj->Section );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				WflObjectTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetStatesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

