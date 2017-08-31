<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflQueryObjectsResponse
{
	public $Columns;
	public $Rows;
	public $ChildColumns;
	public $ChildRows;
	public $ComponentColumns;
	public $ComponentRows;
	public $FirstEntry;
	public $ListedEntries;
	public $TotalEntries;
	public $UpdateID;
	public $Facets;
	public $SearchFeatures;

	/**
	 * @param Property[]           $Columns                   
	 * @param Row[]                $Rows                      
	 * @param Property[]           $ChildColumns              Nullable.
	 * @param ChildRow[]           $ChildRows                 Nullable.
	 * @param Property[]           $ComponentColumns          Nullable.
	 * @param ChildRow[]           $ComponentRows             Nullable.
	 * @param integer              $FirstEntry                Nullable.
	 * @param integer              $ListedEntries             Nullable.
	 * @param integer              $TotalEntries              Nullable.
	 * @param string               $UpdateID                  Nullable.
	 * @param Facet[]              $Facets                    Nullable.
	 * @param Feature[]            $SearchFeatures            Nullable.
	 */
	public function __construct( $Columns=null, $Rows=null, $ChildColumns=null, $ChildRows=null, $ComponentColumns=null, $ComponentRows=null, $FirstEntry=null, $ListedEntries=null, $TotalEntries=null, $UpdateID=null, $Facets=null, $SearchFeatures=null )
	{
		$this->Columns              = $Columns;
		$this->Rows                 = $Rows;
		$this->ChildColumns         = $ChildColumns;
		$this->ChildRows            = $ChildRows;
		$this->ComponentColumns     = $ComponentColumns;
		$this->ComponentRows        = $ComponentRows;
		$this->FirstEntry           = $FirstEntry;
		$this->ListedEntries        = $ListedEntries;
		$this->TotalEntries         = $TotalEntries;
		$this->UpdateID             = $UpdateID;
		$this->Facets               = $Facets;
		$this->SearchFeatures       = $SearchFeatures;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'QueryObjectsResponse' );
		if( $validator->checkExist( $datObj, 'Columns' ) ) {
			$validator->enterPath( 'Columns' );
			$validator->checkNull( $datObj->Columns );
			if( !is_null( $datObj->Columns ) ) {
				$validator->checkType( $datObj->Columns, 'array' );
				if( !empty($datObj->Columns) ) foreach( $datObj->Columns as $listItem ) {
					$validator->enterPath( 'Property' );
					$validator->checkType( $listItem, 'Property' );
					WflPropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Rows' ) ) {
			$validator->enterPath( 'Rows' );
			$validator->checkNull( $datObj->Rows );
			if( !is_null( $datObj->Rows ) ) {
				$validator->checkType( $datObj->Rows, 'array' );
				if( !empty($datObj->Rows) ) foreach( $datObj->Rows as $listItem ) {
					$validator->enterPath( 'array' );
					$validator->checkType( $listItem, 'array' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ChildColumns' ) ) {
			$validator->enterPath( 'ChildColumns' );
			if( !is_null( $datObj->ChildColumns ) ) {
				$validator->checkType( $datObj->ChildColumns, 'array' );
				if( !empty($datObj->ChildColumns) ) foreach( $datObj->ChildColumns as $listItem ) {
					$validator->enterPath( 'Property' );
					$validator->checkType( $listItem, 'Property' );
					WflPropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ChildRows' ) ) {
			$validator->enterPath( 'ChildRows' );
			if( !is_null( $datObj->ChildRows ) ) {
				$validator->checkType( $datObj->ChildRows, 'array' );
				if( !empty($datObj->ChildRows) ) foreach( $datObj->ChildRows as $listItem ) {
					$validator->enterPath( 'ChildRow' );
					$validator->checkType( $listItem, 'ChildRow' );
					WflChildRowValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ComponentColumns' ) ) {
			$validator->enterPath( 'ComponentColumns' );
			if( !is_null( $datObj->ComponentColumns ) ) {
				$validator->checkType( $datObj->ComponentColumns, 'array' );
				if( !empty($datObj->ComponentColumns) ) foreach( $datObj->ComponentColumns as $listItem ) {
					$validator->enterPath( 'Property' );
					$validator->checkType( $listItem, 'Property' );
					WflPropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ComponentRows' ) ) {
			$validator->enterPath( 'ComponentRows' );
			if( !is_null( $datObj->ComponentRows ) ) {
				$validator->checkType( $datObj->ComponentRows, 'array' );
				if( !empty($datObj->ComponentRows) ) foreach( $datObj->ComponentRows as $listItem ) {
					$validator->enterPath( 'ChildRow' );
					$validator->checkType( $listItem, 'ChildRow' );
					WflChildRowValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FirstEntry' ) ) {
			$validator->enterPath( 'FirstEntry' );
			if( !is_null( $datObj->FirstEntry ) ) {
				$validator->checkType( $datObj->FirstEntry, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ListedEntries' ) ) {
			$validator->enterPath( 'ListedEntries' );
			if( !is_null( $datObj->ListedEntries ) ) {
				$validator->checkType( $datObj->ListedEntries, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TotalEntries' ) ) {
			$validator->enterPath( 'TotalEntries' );
			if( !is_null( $datObj->TotalEntries ) ) {
				$validator->checkType( $datObj->TotalEntries, 'unsignedInt' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UpdateID' ) ) {
			$validator->enterPath( 'UpdateID' );
			if( !is_null( $datObj->UpdateID ) ) {
				$validator->checkType( $datObj->UpdateID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Facets' ) ) {
			$validator->enterPath( 'Facets' );
			if( !is_null( $datObj->Facets ) ) {
				$validator->checkType( $datObj->Facets, 'array' );
				if( !empty($datObj->Facets) ) foreach( $datObj->Facets as $listItem ) {
					$validator->enterPath( 'Facet' );
					$validator->checkType( $listItem, 'Facet' );
					WflFacetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SearchFeatures' ) ) {
			$validator->enterPath( 'SearchFeatures' );
			if( !is_null( $datObj->SearchFeatures ) ) {
				$validator->checkType( $datObj->SearchFeatures, 'array' );
				if( !empty($datObj->SearchFeatures) ) foreach( $datObj->SearchFeatures as $listItem ) {
					$validator->enterPath( 'Feature' );
					$validator->checkType( $listItem, 'Feature' );
					WflFeatureValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflQueryObjectsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

