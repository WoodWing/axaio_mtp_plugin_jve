<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetRelatedPagesRequest
{
	public $Ticket;
	public $LayoutId;
	public $PageSequences;
	public $Rendition;

	/**
	 * @param string               $Ticket                    
	 * @param string               $LayoutId                  
	 * @param integer[]            $PageSequences             
	 * @param string               $Rendition                 
	 */
	public function __construct( $Ticket=null, $LayoutId=null, $PageSequences=null, $Rendition=null )
	{
		$this->Ticket               = $Ticket;
		$this->LayoutId             = $LayoutId;
		$this->PageSequences        = $PageSequences;
		$this->Rendition            = $Rendition;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetRelatedPagesRequest' );
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
			$validator->checkNull( $datObj->LayoutId );
			if( !is_null( $datObj->LayoutId ) ) {
				$validator->checkType( $datObj->LayoutId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageSequences' ) ) {
			$validator->enterPath( 'PageSequences' );
			$validator->checkNull( $datObj->PageSequences );
			if( !is_null( $datObj->PageSequences ) ) {
				$validator->checkType( $datObj->PageSequences, 'array' );
				if( !empty($datObj->PageSequences) ) foreach( $datObj->PageSequences as $listItem ) {
					$validator->enterPath( 'unsignedInt' );
					$validator->checkType( $listItem, 'unsignedInt' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Rendition' ) ) {
			$validator->enterPath( 'Rendition' );
			$validator->checkNull( $datObj->Rendition );
			if( !is_null( $datObj->Rendition ) ) {
				$validator->checkType( $datObj->Rendition, 'string' );
				WflRenditionTypeValidator::validate( $validator, $datObj->Rendition );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetRelatedPagesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->PageSequences)){
			if (is_object($this->PageSequences[0])){
				foreach ($this->PageSequences as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

