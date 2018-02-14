<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class PlnModifyLayoutsRequest
{
	public $Ticket;
	public $Layouts;

	/**
	 * @param string               $Ticket                    
	 * @param PlnLayout[]          $Layouts                   
	 */
	public function __construct( $Ticket=null, $Layouts=null )
	{
		$this->Ticket               = $Ticket;
		$this->Layouts              = $Layouts;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pln/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'ModifyLayoutsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Layouts' ) ) {
			$validator->enterPath( 'Layouts' );
			$validator->checkNull( $datObj->Layouts );
			if( !is_null( $datObj->Layouts ) ) {
				$validator->checkType( $datObj->Layouts, 'array' );
				if( !empty($datObj->Layouts) ) foreach( $datObj->Layouts as $listItem ) {
					$validator->enterPath( 'Layout' );
					$validator->checkType( $listItem, 'PlnLayout' );
					PlnLayoutValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.PlnModifyLayoutsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Layouts)){
			if (is_object($this->Layouts[0])){
				foreach ($this->Layouts as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

