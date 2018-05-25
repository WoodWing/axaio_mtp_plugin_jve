<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflAddObjectLabelsRequest
{
	public $Ticket;
	public $ParentId;
	public $ChildIds;
	public $ObjectLabels;

	/**
	 * @param string               $Ticket                    
	 * @param string               $ParentId                  
	 * @param string[]             $ChildIds                  
	 * @param ObjectLabel[]        $ObjectLabels              
	 */
	public function __construct( $Ticket=null, $ParentId=null, $ChildIds=null, $ObjectLabels=null )
	{
		$this->Ticket               = $Ticket;
		$this->ParentId             = $ParentId;
		$this->ChildIds             = $ChildIds;
		$this->ObjectLabels         = $ObjectLabels;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'AddObjectLabelsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ParentId' ) ) {
			$validator->enterPath( 'ParentId' );
			$validator->checkNull( $datObj->ParentId );
			if( !is_null( $datObj->ParentId ) ) {
				$validator->checkType( $datObj->ParentId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ChildIds' ) ) {
			$validator->enterPath( 'ChildIds' );
			$validator->checkNull( $datObj->ChildIds );
			if( !is_null( $datObj->ChildIds ) ) {
				$validator->checkType( $datObj->ChildIds, 'array' );
				if( !empty($datObj->ChildIds) ) foreach( $datObj->ChildIds as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectLabels' ) ) {
			$validator->enterPath( 'ObjectLabels' );
			$validator->checkNull( $datObj->ObjectLabels );
			if( !is_null( $datObj->ObjectLabels ) ) {
				$validator->checkType( $datObj->ObjectLabels, 'array' );
				if( !empty($datObj->ObjectLabels) ) foreach( $datObj->ObjectLabels as $listItem ) {
					$validator->enterPath( 'ObjectLabel' );
					$validator->checkType( $listItem, 'ObjectLabel' );
					WflObjectLabelValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflAddObjectLabelsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->ChildIds)){
			if (is_object($this->ChildIds[0])){
				foreach ($this->ChildIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->ObjectLabels)){
			if (is_object($this->ObjectLabels[0])){
				foreach ($this->ObjectLabels as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

