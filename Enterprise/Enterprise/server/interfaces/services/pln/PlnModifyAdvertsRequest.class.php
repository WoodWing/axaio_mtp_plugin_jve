<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class PlnModifyAdvertsRequest
{
	public $Ticket;
	public $LayoutId;
	public $LayoutName;
	public $Adverts;

	/**
	 * @param string               $Ticket                    
	 * @param string               $LayoutId                  Nullable.
	 * @param string               $LayoutName                Nullable.
	 * @param PlnAdvert[]          $Adverts                   
	 */
	public function __construct( $Ticket=null, $LayoutId=null, $LayoutName=null, $Adverts=null )
	{
		$this->Ticket               = $Ticket;
		$this->LayoutId             = $LayoutId;
		$this->LayoutName           = $LayoutName;
		$this->Adverts              = $Adverts;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pln/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'ModifyAdvertsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LayoutId' ) ) {
			$validator->enterPath( 'LayoutId' );
			if( !is_null( $datObj->LayoutId ) ) {
				$validator->checkType( $datObj->LayoutId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LayoutName' ) ) {
			$validator->enterPath( 'LayoutName' );
			if( !is_null( $datObj->LayoutName ) ) {
				$validator->checkType( $datObj->LayoutName, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Adverts' ) ) {
			$validator->enterPath( 'Adverts' );
			$validator->checkNull( $datObj->Adverts );
			if( !is_null( $datObj->Adverts ) ) {
				$validator->checkType( $datObj->Adverts, 'array' );
				if( !empty($datObj->Adverts) ) foreach( $datObj->Adverts as $listItem ) {
					$validator->enterPath( 'Advert' );
					$validator->checkType( $listItem, 'PlnAdvert' );
					PlnAdvertValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pln.PlnModifyAdvertsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Adverts)){
			if (is_object($this->Adverts[0])){
				foreach ($this->Adverts as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return true; }
}

