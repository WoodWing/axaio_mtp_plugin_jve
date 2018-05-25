<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetRelatedPagesInfoRequest
{
	public $Ticket;
	public $LayoutId;
	public $PageSequences;

	/**
	 * @param string               $Ticket                    
	 * @param string               $LayoutId                  
	 * @param integer[]            $PageSequences             
	 */
	public function __construct( $Ticket=null, $LayoutId=null, $PageSequences=null )
	{
		$this->Ticket               = $Ticket;
		$this->LayoutId             = $LayoutId;
		$this->PageSequences        = $PageSequences;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetRelatedPagesInfoRequest' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetRelatedPagesInfoRequest'; } // AMF object type mapping

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

