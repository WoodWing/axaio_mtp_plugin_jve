<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCopyObjectRequest
{
	public $Ticket;
	public $SourceID;
	public $MetaData;
	public $Relations;
	public $Targets;

	/**
	 * @param string               $Ticket                    
	 * @param string               $SourceID                  
	 * @param MetaData             $MetaData                  
	 * @param Relation[]           $Relations                 Nullable.
	 * @param Target[]             $Targets                   Nullable.
	 */
	public function __construct( $Ticket=null, $SourceID=null, $MetaData=null, $Relations=null, $Targets=null )
	{
		$this->Ticket               = $Ticket;
		$this->SourceID             = $SourceID;
		$this->MetaData             = $MetaData;
		$this->Relations            = $Relations;
		$this->Targets              = $Targets;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CopyObjectRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SourceID' ) ) {
			$validator->enterPath( 'SourceID' );
			$validator->checkNull( $datObj->SourceID );
			if( !is_null( $datObj->SourceID ) ) {
				$validator->checkType( $datObj->SourceID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MetaData' ) ) {
			$validator->enterPath( 'MetaData' );
			$validator->checkNull( $datObj->MetaData );
			if( !is_null( $datObj->MetaData ) ) {
				$validator->checkType( $datObj->MetaData, 'MetaData' );
				WflMetaDataValidator::validate( $validator, $datObj->MetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Relations' ) ) {
			$validator->enterPath( 'Relations' );
			if( !is_null( $datObj->Relations ) ) {
				$validator->checkType( $datObj->Relations, 'array' );
				if( !empty($datObj->Relations) ) foreach( $datObj->Relations as $listItem ) {
					$validator->enterPath( 'Relation' );
					$validator->checkType( $listItem, 'Relation' );
					WflRelationValidator::validate( $validator, $listItem );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCopyObjectRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Relations)){
			if (is_object($this->Relations[0])){
				foreach ($this->Relations as $complexField){
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
		if( is_object( $this->MetaData ) ) {
			$this->MetaData->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

