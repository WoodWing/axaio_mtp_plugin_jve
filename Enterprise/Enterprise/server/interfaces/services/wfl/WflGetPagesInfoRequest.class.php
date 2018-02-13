<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetPagesInfoRequest
{
	public $Ticket;
	public $Issue;
	public $IDs;
	public $Edition;
	public $Category;
	public $State;

	/**
	 * @param string               $Ticket                    
	 * @param Issue                $Issue                     Nullable.
	 * @param string[]             $IDs                       Nullable.
	 * @param Edition              $Edition                   Nullable.
	 * @param Category             $Category                  Nullable.
	 * @param State                $State                     Nullable.
	 */
	public function __construct( $Ticket=null, $Issue=null, $IDs=null, $Edition=null, $Category=null, $State=null )
	{
		$this->Ticket               = $Ticket;
		$this->Issue                = $Issue;
		$this->IDs                  = $IDs;
		$this->Edition              = $Edition;
		$this->Category             = $Category;
		$this->State                = $State;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetPagesInfoRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
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
		if( $validator->checkExist( $datObj, 'IDs' ) ) {
			$validator->enterPath( 'IDs' );
			if( !is_null( $datObj->IDs ) ) {
				$validator->checkType( $datObj->IDs, 'array' );
				if( !empty($datObj->IDs) ) foreach( $datObj->IDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Edition' ) ) {
			$validator->enterPath( 'Edition' );
			if( !is_null( $datObj->Edition ) ) {
				$validator->checkType( $datObj->Edition, 'Edition' );
				WflEditionValidator::validate( $validator, $datObj->Edition );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Category' ) ) {
			$validator->enterPath( 'Category' );
			if( !is_null( $datObj->Category ) ) {
				$validator->checkType( $datObj->Category, 'Category' );
				WflCategoryValidator::validate( $validator, $datObj->Category );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'State' ) ) {
			$validator->enterPath( 'State' );
			if( !is_null( $datObj->State ) ) {
				$validator->checkType( $datObj->State, 'State' );
				WflStateValidator::validate( $validator, $datObj->State );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetPagesInfoRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->IDs)){
			if (is_object($this->IDs[0])){
				foreach ($this->IDs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if( is_object( $this->Issue ) ) {
			$this->Issue->sanitizeProperties4Php();
		}
		if( is_object( $this->Edition ) ) {
			$this->Edition->sanitizeProperties4Php();
		}
		if( is_object( $this->Category ) ) {
			$this->Category->sanitizeProperties4Php();
		}
		if( is_object( $this->State ) ) {
			$this->State->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

