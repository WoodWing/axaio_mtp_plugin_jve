<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetRoutingsResponse
{
	public $Routings;
	public $Sections;
	public $Statuses;

	/**
	 * @param AdmRouting[]         $Routings                  
	 * @param AdmSection[]         $Sections                  Nullable.
	 * @param AdmStatus[]          $Statuses                  Nullable.
	 */
	public function __construct( $Routings=null, $Sections=null, $Statuses=null )
	{
		$this->Routings             = $Routings;
		$this->Sections             = $Sections;
		$this->Statuses             = $Statuses;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetRoutingsResponse' );
		if( $validator->checkExist( $datObj, 'Routings' ) ) {
			$validator->enterPath( 'Routings' );
			$validator->checkNull( $datObj->Routings );
			if( !is_null( $datObj->Routings ) ) {
				$validator->checkType( $datObj->Routings, 'array' );
				if( !empty($datObj->Routings) ) foreach( $datObj->Routings as $listItem ) {
					$validator->enterPath( 'Routing' );
					$validator->checkType( $listItem, 'AdmRouting' );
					AdmRoutingValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Sections' ) ) {
			$validator->enterPath( 'Sections' );
			if( !is_null( $datObj->Sections ) ) {
				$validator->checkType( $datObj->Sections, 'array' );
				if( !empty($datObj->Sections) ) foreach( $datObj->Sections as $listItem ) {
					$validator->enterPath( 'Section' );
					$validator->checkType( $listItem, 'AdmSection' );
					AdmSectionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Statuses' ) ) {
			$validator->enterPath( 'Statuses' );
			if( !is_null( $datObj->Statuses ) ) {
				$validator->checkType( $datObj->Statuses, 'array' );
				if( !empty($datObj->Statuses) ) foreach( $datObj->Statuses as $listItem ) {
					$validator->enterPath( 'Status' );
					$validator->checkType( $listItem, 'AdmStatus' );
					AdmStatusValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetRoutingsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

