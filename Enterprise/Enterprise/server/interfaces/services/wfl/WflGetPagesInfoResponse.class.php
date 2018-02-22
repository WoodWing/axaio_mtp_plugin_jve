<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetPagesInfoResponse
{
	public $ReversedReadingOrder;
	public $ExpectedPages;
	public $PageOrderMethod;
	public $EditionsPages;
	public $LayoutObjects;
	public $PlacedObjects;

	/**
	 * @param boolean              $ReversedReadingOrder      
	 * @param integer              $ExpectedPages             Nullable.
	 * @param string               $PageOrderMethod           
	 * @param EditionPages[]       $EditionsPages             
	 * @param LayoutObject[]       $LayoutObjects             
	 * @param PlacedObject[]       $PlacedObjects             
	 */
	public function __construct( $ReversedReadingOrder=null, $ExpectedPages=null, $PageOrderMethod=null, $EditionsPages=null, $LayoutObjects=null, $PlacedObjects=null )
	{
		$this->ReversedReadingOrder = $ReversedReadingOrder;
		$this->ExpectedPages        = $ExpectedPages;
		$this->PageOrderMethod      = $PageOrderMethod;
		$this->EditionsPages        = $EditionsPages;
		$this->LayoutObjects        = $LayoutObjects;
		$this->PlacedObjects        = $PlacedObjects;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetPagesInfoResponse' );
		if( $validator->checkExist( $datObj, 'ReversedReadingOrder' ) ) {
			$validator->enterPath( 'ReversedReadingOrder' );
			$validator->checkNull( $datObj->ReversedReadingOrder );
			if( !is_null( $datObj->ReversedReadingOrder ) ) {
				$validator->checkType( $datObj->ReversedReadingOrder, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ExpectedPages' ) ) {
			$validator->enterPath( 'ExpectedPages' );
			if( !is_null( $datObj->ExpectedPages ) ) {
				$validator->checkType( $datObj->ExpectedPages, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageOrderMethod' ) ) {
			$validator->enterPath( 'PageOrderMethod' );
			$validator->checkNull( $datObj->PageOrderMethod );
			if( !is_null( $datObj->PageOrderMethod ) ) {
				$validator->checkType( $datObj->PageOrderMethod, 'string' );
			}
			$validator->leavePath();
		}
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
		if( $validator->checkExist( $datObj, 'PlacedObjects' ) ) {
			$validator->enterPath( 'PlacedObjects' );
			$validator->checkNull( $datObj->PlacedObjects );
			if( !is_null( $datObj->PlacedObjects ) ) {
				$validator->checkType( $datObj->PlacedObjects, 'array' );
				if( !empty($datObj->PlacedObjects) ) foreach( $datObj->PlacedObjects as $listItem ) {
					$validator->enterPath( 'PlacedObject' );
					$validator->checkType( $listItem, 'PlacedObject' );
					WflPlacedObjectValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetPagesInfoResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

