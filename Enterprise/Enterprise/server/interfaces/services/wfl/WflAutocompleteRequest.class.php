<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflAutocompleteRequest
{
	public $Ticket;
	public $AutocompleteProvider;
	public $PublishSystemId;
	public $ObjectId;
	public $Property;
	public $TypedValue;

	/**
	 * @param string               $Ticket                    
	 * @param string               $AutocompleteProvider      
	 * @param string               $PublishSystemId           
	 * @param string               $ObjectId                  Nullable.
	 * @param AutoSuggestProperty  $Property                  
	 * @param string               $TypedValue                
	 */
	public function __construct( $Ticket=null, $AutocompleteProvider=null, $PublishSystemId=null, $ObjectId=null, $Property=null, $TypedValue=null )
	{
		$this->Ticket               = $Ticket;
		$this->AutocompleteProvider = $AutocompleteProvider;
		$this->PublishSystemId      = $PublishSystemId;
		$this->ObjectId             = $ObjectId;
		$this->Property             = $Property;
		$this->TypedValue           = $TypedValue;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'AutocompleteRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'AutocompleteProvider' ) ) {
			$validator->enterPath( 'AutocompleteProvider' );
			$validator->checkNull( $datObj->AutocompleteProvider );
			if( !is_null( $datObj->AutocompleteProvider ) ) {
				$validator->checkType( $datObj->AutocompleteProvider, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishSystemId' ) ) {
			$validator->enterPath( 'PublishSystemId' );
			$validator->checkNull( $datObj->PublishSystemId );
			if( !is_null( $datObj->PublishSystemId ) ) {
				$validator->checkType( $datObj->PublishSystemId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectId' ) ) {
			$validator->enterPath( 'ObjectId' );
			if( !is_null( $datObj->ObjectId ) ) {
				$validator->checkType( $datObj->ObjectId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Property' ) ) {
			$validator->enterPath( 'Property' );
			$validator->checkNull( $datObj->Property );
			if( !is_null( $datObj->Property ) ) {
				$validator->checkType( $datObj->Property, 'AutoSuggestProperty' );
				WflAutoSuggestPropertyValidator::validate( $validator, $datObj->Property );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TypedValue' ) ) {
			$validator->enterPath( 'TypedValue' );
			$validator->checkNull( $datObj->TypedValue );
			if( !is_null( $datObj->TypedValue ) ) {
				$validator->checkType( $datObj->TypedValue, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflAutocompleteRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if( is_object( $this->Property ) ) {
			$this->Property->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

