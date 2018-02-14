<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCreateObjectLabelsResponse
{
	public $ObjectLabels;

	/**
	 * @param ObjectLabel[]        $ObjectLabels              
	 */
	public function __construct( $ObjectLabels=null )
	{
		$this->ObjectLabels         = $ObjectLabels;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreateObjectLabelsResponse' );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCreateObjectLabelsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

