<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflGetDialog2Request
{
	public $Ticket;
	public $Action;
	public $MetaData;
	public $Targets;
	public $DefaultDossier;
	public $Parent;
	public $Template;
	public $Areas;
	public $MultipleObjects;

	/**
	 * @param string               $Ticket                    
	 * @param string               $Action                    Nullable.
	 * @param MetaDataValue[]      $MetaData                  Nullable.
	 * @param Target[]             $Targets                   Nullable.
	 * @param string               $DefaultDossier            Nullable.
	 * @param string               $Parent                    Nullable.
	 * @param string               $Template                  Nullable.
	 * @param string[]             $Areas                     Nullable.
	 * @param boolean              $MultipleObjects           Nullable.
	 */
	public function __construct( $Ticket=null, $Action=null, $MetaData=null, $Targets=null, $DefaultDossier=null, $Parent=null, $Template=null, $Areas=null, $MultipleObjects=null )
	{
		$this->Ticket               = $Ticket;
		$this->Action               = $Action;
		$this->MetaData             = $MetaData;
		$this->Targets              = $Targets;
		$this->DefaultDossier       = $DefaultDossier;
		$this->Parent               = $Parent;
		$this->Template             = $Template;
		$this->Areas                = $Areas;
		$this->MultipleObjects      = $MultipleObjects;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetDialog2Request' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Action' ) ) {
			$validator->enterPath( 'Action' );
			if( !is_null( $datObj->Action ) ) {
				$validator->checkType( $datObj->Action, 'string' );
				WflActionValidator::validate( $validator, $datObj->Action );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MetaData' ) ) {
			$validator->enterPath( 'MetaData' );
			if( !is_null( $datObj->MetaData ) ) {
				$validator->checkType( $datObj->MetaData, 'array' );
				if( !empty($datObj->MetaData) ) foreach( $datObj->MetaData as $listItem ) {
					$validator->enterPath( 'MetaDataValue' );
					$validator->checkType( $listItem, 'MetaDataValue' );
					WflMetaDataValueValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Targets' ) ) {
			$validator->enterPath( 'Targets' );
			if( !is_null( $datObj->Targets ) ) {
				$validator->checkType( $datObj->Targets, 'array' );
				if( !empty($datObj->Targets) ) foreach( $datObj->Targets as $listItem ) {
					$validator->enterPath( 'Target' );
					$validator->checkType( $listItem, 'Target' );
					WflTargetValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DefaultDossier' ) ) {
			$validator->enterPath( 'DefaultDossier' );
			if( !is_null( $datObj->DefaultDossier ) ) {
				$validator->checkType( $datObj->DefaultDossier, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Parent' ) ) {
			$validator->enterPath( 'Parent' );
			if( !is_null( $datObj->Parent ) ) {
				$validator->checkType( $datObj->Parent, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Template' ) ) {
			$validator->enterPath( 'Template' );
			if( !is_null( $datObj->Template ) ) {
				$validator->checkType( $datObj->Template, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Areas' ) ) {
			$validator->enterPath( 'Areas' );
			if( !is_null( $datObj->Areas ) ) {
				$validator->checkType( $datObj->Areas, 'array' );
				if( !empty($datObj->Areas) ) foreach( $datObj->Areas as $listItem ) {
					$validator->enterPath( 'AreaType' );
					$validator->checkType( $listItem, 'string' );
					WflAreaTypeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MultipleObjects' ) ) {
			$validator->enterPath( 'MultipleObjects' );
			if( !is_null( $datObj->MultipleObjects ) ) {
				$validator->checkType( $datObj->MultipleObjects, 'boolean' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetDialog2Request'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->MultipleObjects)){ $this->MultipleObjects = ('true' == $this->MultipleObjects) ? true : false; }
		if (0 < count($this->MetaData)){
			if (is_object($this->MetaData[0])){
				foreach ($this->MetaData as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Targets)){
			if (is_object($this->Targets[0])){
				foreach ($this->Targets as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Areas)){
			if (is_object($this->Areas[0])){
				foreach ($this->Areas as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

