<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflGetPagesRequest
{
	public $Ticket;
	public $Params;
	public $IDs;
	public $PageOrders;
	public $PageSequences;
	public $Edition;
	public $Renditions;
	public $RequestMetaData;
	public $RequestFiles;

	/**
	 * @param string               $Ticket                    
	 * @param QueryParam[]         $Params                    Nullable.
	 * @param string[]             $IDs                       Nullable.
	 * @param string[]             $PageOrders                Nullable.
	 * @param string[]             $PageSequences             Nullable.
	 * @param Edition              $Edition                   Nullable.
	 * @param string[]             $Renditions                Nullable.
	 * @param boolean              $RequestMetaData           Nullable.
	 * @param boolean              $RequestFiles              Nullable.
	 */
	public function __construct( $Ticket=null, $Params=null, $IDs=null, $PageOrders=null, $PageSequences=null, $Edition=null, $Renditions=null, $RequestMetaData=null, $RequestFiles=null )
	{
		$this->Ticket               = $Ticket;
		$this->Params               = $Params;
		$this->IDs                  = $IDs;
		$this->PageOrders           = $PageOrders;
		$this->PageSequences        = $PageSequences;
		$this->Edition              = $Edition;
		$this->Renditions           = $Renditions;
		$this->RequestMetaData      = $RequestMetaData;
		$this->RequestFiles         = $RequestFiles;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetPagesRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Params' ) ) {
			$validator->enterPath( 'Params' );
			if( !is_null( $datObj->Params ) ) {
				$validator->checkType( $datObj->Params, 'array' );
				if( !empty($datObj->Params) ) foreach( $datObj->Params as $listItem ) {
					$validator->enterPath( 'QueryParam' );
					$validator->checkType( $listItem, 'QueryParam' );
					WflQueryParamValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IDs' ) ) {
			$validator->enterPath( 'IDs' );
			if( !is_null( $datObj->IDs ) ) {
				$validator->checkType( $datObj->IDs, 'array' );
				if( !empty($datObj->IDs) ) foreach( $datObj->IDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageOrders' ) ) {
			$validator->enterPath( 'PageOrders' );
			if( !is_null( $datObj->PageOrders ) ) {
				$validator->checkType( $datObj->PageOrders, 'array' );
				if( !empty($datObj->PageOrders) ) foreach( $datObj->PageOrders as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PageSequences' ) ) {
			$validator->enterPath( 'PageSequences' );
			if( !is_null( $datObj->PageSequences ) ) {
				$validator->checkType( $datObj->PageSequences, 'array' );
				if( !empty($datObj->PageSequences) ) foreach( $datObj->PageSequences as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Edition' ) ) {
			$validator->enterPath( 'Edition' );
			if( !is_null( $datObj->Edition ) ) {
				$validator->checkType( $datObj->Edition, 'Edition' );
				WflEditionValidator::validate( $validator, $datObj->Edition );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Renditions' ) ) {
			$validator->enterPath( 'Renditions' );
			if( !is_null( $datObj->Renditions ) ) {
				$validator->checkType( $datObj->Renditions, 'array' );
				if( !empty($datObj->Renditions) ) foreach( $datObj->Renditions as $listItem ) {
					$validator->enterPath( 'RenditionType' );
					$validator->checkType( $listItem, 'string' );
					WflRenditionTypeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestMetaData' ) ) {
			$validator->enterPath( 'RequestMetaData' );
			if( !is_null( $datObj->RequestMetaData ) ) {
				$validator->checkType( $datObj->RequestMetaData, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestFiles' ) ) {
			$validator->enterPath( 'RequestFiles' );
			if( !is_null( $datObj->RequestFiles ) ) {
				$validator->checkType( $datObj->RequestFiles, 'boolean' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetPagesRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->RequestMetaData)){ $this->RequestMetaData = ('true' == $this->RequestMetaData) ? true : false; }
		if (!is_null($this->RequestFiles)){ $this->RequestFiles = ('true' == $this->RequestFiles) ? true : false; }
		if (0 < count($this->Params)){
			if (is_object($this->Params[0])){
				foreach ($this->Params as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->IDs)){
			if (is_object($this->IDs[0])){
				foreach ($this->IDs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->PageOrders)){
			if (is_object($this->PageOrders[0])){
				foreach ($this->PageOrders as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->PageSequences)){
			if (is_object($this->PageSequences[0])){
				foreach ($this->PageSequences as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Renditions)){
			if (is_object($this->Renditions[0])){
				foreach ($this->Renditions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if( is_object( $this->Edition ) ) {
			$this->Edition->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

