<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflSuggestionsRequest
{
	public $Ticket;
	public $SuggestionProvider;
	public $ObjectId;
	public $MetaData;
	public $SuggestForProperties;

	/**
	 * @param string               $Ticket                    
	 * @param string               $SuggestionProvider        
	 * @param string               $ObjectId                  
	 * @param MetaDataValue[]      $MetaData                  
	 * @param AutoSuggestProperty[] $SuggestForProperties      
	 */
	public function __construct( $Ticket=null, $SuggestionProvider=null, $ObjectId=null, $MetaData=null, $SuggestForProperties=null )
	{
		$this->Ticket               = $Ticket;
		$this->SuggestionProvider   = $SuggestionProvider;
		$this->ObjectId             = $ObjectId;
		$this->MetaData             = $MetaData;
		$this->SuggestForProperties = $SuggestForProperties;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'SuggestionsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SuggestionProvider' ) ) {
			$validator->enterPath( 'SuggestionProvider' );
			$validator->checkNull( $datObj->SuggestionProvider );
			if( !is_null( $datObj->SuggestionProvider ) ) {
				$validator->checkType( $datObj->SuggestionProvider, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectId' ) ) {
			$validator->enterPath( 'ObjectId' );
			$validator->checkNull( $datObj->ObjectId );
			if( !is_null( $datObj->ObjectId ) ) {
				$validator->checkType( $datObj->ObjectId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MetaData' ) ) {
			$validator->enterPath( 'MetaData' );
			$validator->checkNull( $datObj->MetaData );
			if( !is_null( $datObj->MetaData ) ) {
				$validator->checkType( $datObj->MetaData, 'array' );
				if( !empty($datObj->MetaData) ) foreach( $datObj->MetaData as $listItem ) {
					$validator->enterPath( 'MetaDataValue' );
					$validator->checkType( $listItem, 'MetaDataValue' );
					WflMetaDataValueValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SuggestForProperties' ) ) {
			$validator->enterPath( 'SuggestForProperties' );
			$validator->checkNull( $datObj->SuggestForProperties );
			if( !is_null( $datObj->SuggestForProperties ) ) {
				$validator->checkType( $datObj->SuggestForProperties, 'array' );
				if( !empty($datObj->SuggestForProperties) ) foreach( $datObj->SuggestForProperties as $listItem ) {
					$validator->enterPath( 'AutoSuggestProperty' );
					$validator->checkType( $listItem, 'AutoSuggestProperty' );
					WflAutoSuggestPropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSuggestionsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->MetaData)){
			if (is_object($this->MetaData[0])){
				foreach ($this->MetaData as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->SuggestForProperties)){
			if (is_object($this->SuggestForProperties[0])){
				foreach ($this->SuggestForProperties as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

