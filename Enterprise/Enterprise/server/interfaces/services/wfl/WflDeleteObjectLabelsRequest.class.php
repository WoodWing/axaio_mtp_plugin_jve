<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflDeleteObjectLabelsRequest
{
	public $Ticket;
	public $ObjectLabels;

	/**
	 * @param string               $Ticket                    
	 * @param ObjectLabel[]        $ObjectLabels              
	 */
	public function __construct( $Ticket=null, $ObjectLabels=null )
	{
		$this->Ticket               = $Ticket;
		$this->ObjectLabels         = $ObjectLabels;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteObjectLabelsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflDeleteObjectLabelsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
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

