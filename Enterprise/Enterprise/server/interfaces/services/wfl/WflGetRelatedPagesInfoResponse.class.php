<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetRelatedPagesInfoResponse
{
	public $EditionsPages;
	public $LayoutObjects;

	/**
	 * @param EditionPages[]       $EditionsPages             
	 * @param LayoutObject[]       $LayoutObjects             
	 */
	public function __construct( $EditionsPages=null, $LayoutObjects=null )
	{
		$this->EditionsPages        = $EditionsPages;
		$this->LayoutObjects        = $LayoutObjects;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetRelatedPagesInfoResponse' );
		if( $validator->checkExist( $datObj, 'EditionsPages' ) ) {
			$validator->enterPath( 'EditionsPages' );
			$validator->checkNull( $datObj->EditionsPages );
			if( !is_null( $datObj->EditionsPages ) ) {
				$validator->checkType( $datObj->EditionsPages, 'array' );
				if( !empty($datObj->EditionsPages) ) foreach( $datObj->EditionsPages as $listItem ) {
					$validator->enterPath( 'EditionPages' );
					$validator->checkType( $listItem, 'EditionPages' );
					WflEditionPagesValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LayoutObjects' ) ) {
			$validator->enterPath( 'LayoutObjects' );
			$validator->checkNull( $datObj->LayoutObjects );
			if( !is_null( $datObj->LayoutObjects ) ) {
				$validator->checkType( $datObj->LayoutObjects, 'array' );
				if( !empty($datObj->LayoutObjects) ) foreach( $datObj->LayoutObjects as $listItem ) {
					$validator->enterPath( 'LayoutObject' );
					$validator->checkType( $listItem, 'LayoutObject' );
					WflLayoutObjectValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetRelatedPagesInfoResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

