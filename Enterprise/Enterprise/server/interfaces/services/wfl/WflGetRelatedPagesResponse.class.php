<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetRelatedPagesResponse
{
	public $ObjectPageInfos;

	/**
	 * @param ObjectPageInfo[]     $ObjectPageInfos           
	 */
	public function __construct( $ObjectPageInfos=null )
	{
		$this->ObjectPageInfos      = $ObjectPageInfos;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetRelatedPagesResponse' );
		if( $validator->checkExist( $datObj, 'ObjectPageInfos' ) ) {
			$validator->enterPath( 'ObjectPageInfos' );
			$validator->checkNull( $datObj->ObjectPageInfos );
			if( !is_null( $datObj->ObjectPageInfos ) ) {
				$validator->checkType( $datObj->ObjectPageInfos, 'array' );
				if( !empty($datObj->ObjectPageInfos) ) foreach( $datObj->ObjectPageInfos as $listItem ) {
					$validator->enterPath( 'ObjectPageInfo' );
					$validator->checkType( $listItem, 'ObjectPageInfo' );
					WflObjectPageInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetRelatedPagesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return true; }
}

