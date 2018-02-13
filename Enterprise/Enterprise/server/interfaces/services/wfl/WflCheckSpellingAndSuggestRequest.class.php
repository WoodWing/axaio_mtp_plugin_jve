<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCheckSpellingAndSuggestRequest
{
	public $Ticket;
	public $Language;
	public $PublicationId;
	public $WordsToCheck;

	/**
	 * @param string               $Ticket                    
	 * @param string               $Language                  
	 * @param string               $PublicationId             
	 * @param string[]             $WordsToCheck              
	 */
	public function __construct( $Ticket=null, $Language=null, $PublicationId=null, $WordsToCheck=null )
	{
		$this->Ticket               = $Ticket;
		$this->Language             = $Language;
		$this->PublicationId        = $PublicationId;
		$this->WordsToCheck         = $WordsToCheck;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CheckSpellingAndSuggestRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Language' ) ) {
			$validator->enterPath( 'Language' );
			$validator->checkNull( $datObj->Language );
			if( !is_null( $datObj->Language ) ) {
				$validator->checkType( $datObj->Language, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublicationId' ) ) {
			$validator->enterPath( 'PublicationId' );
			$validator->checkNull( $datObj->PublicationId );
			if( !is_null( $datObj->PublicationId ) ) {
				$validator->checkType( $datObj->PublicationId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'WordsToCheck' ) ) {
			$validator->enterPath( 'WordsToCheck' );
			$validator->checkNull( $datObj->WordsToCheck );
			if( !is_null( $datObj->WordsToCheck ) ) {
				$validator->checkType( $datObj->WordsToCheck, 'array' );
				if( !empty($datObj->WordsToCheck) ) foreach( $datObj->WordsToCheck as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCheckSpellingAndSuggestRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->WordsToCheck)){
			if (is_object($this->WordsToCheck[0])){
				foreach ($this->WordsToCheck as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

