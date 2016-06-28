<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflGetDialogResponse
{
	public $Dialog;
	public $Publications;
	public $PublicationInfo;
	public $MetaData;
	public $GetStatesResponse;
	public $Targets;
	public $RelatedTargets;
	public $Dossiers;

	/**
	 * @param Dialog               $Dialog                    Nullable.
	 * @param Publication[]        $Publications              Nullable.
	 * @param PublicationInfo      $PublicationInfo           Nullable.
	 * @param MetaData             $MetaData                  Nullable.
	 * @param GetStatesResponse    $GetStatesResponse         Nullable.
	 * @param Target[]             $Targets                   Nullable.
	 * @param ObjectTargetsInfo[]  $RelatedTargets            Nullable.
	 * @param ObjectInfo[]         $Dossiers                  Nullable.
	 */
	public function __construct( $Dialog=null, $Publications=null, $PublicationInfo=null, $MetaData=null, $GetStatesResponse=null, $Targets=null, $RelatedTargets=null, $Dossiers=null )
	{
		$this->Dialog               = $Dialog;
		$this->Publications         = $Publications;
		$this->PublicationInfo      = $PublicationInfo;
		$this->MetaData             = $MetaData;
		$this->GetStatesResponse    = $GetStatesResponse;
		$this->Targets              = $Targets;
		$this->RelatedTargets       = $RelatedTargets;
		$this->Dossiers             = $Dossiers;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetDialogResponse' );
		if( $validator->checkExist( $datObj, 'Dialog' ) ) {
			$validator->enterPath( 'Dialog' );
			if( !is_null( $datObj->Dialog ) ) {
				$validator->checkType( $datObj->Dialog, 'Dialog' );
				WflDialogValidator::validate( $validator, $datObj->Dialog );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Publications' ) ) {
			$validator->enterPath( 'Publications' );
			if( !is_null( $datObj->Publications ) ) {
				$validator->checkType( $datObj->Publications, 'array' );
				if( !empty($datObj->Publications) ) foreach( $datObj->Publications as $listItem ) {
					$validator->enterPath( 'Publication' );
					$validator->checkType( $listItem, 'Publication' );
					WflPublicationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublicationInfo' ) ) {
			$validator->enterPath( 'PublicationInfo' );
			if( !is_null( $datObj->PublicationInfo ) ) {
				$validator->checkType( $datObj->PublicationInfo, 'PublicationInfo' );
				WflPublicationInfoValidator::validate( $validator, $datObj->PublicationInfo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MetaData' ) ) {
			$validator->enterPath( 'MetaData' );
			if( !is_null( $datObj->MetaData ) ) {
				$validator->checkType( $datObj->MetaData, 'MetaData' );
				WflMetaDataValidator::validate( $validator, $datObj->MetaData );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'GetStatesResponse' ) ) {
			$validator->enterPath( 'GetStatesResponse' );
			if( !is_null( $datObj->GetStatesResponse ) ) {
				$validator->checkType( $datObj->GetStatesResponse, 'WflGetStatesResponse' );
				WflGetStatesResponseValidator::validate( $validator, $datObj->GetStatesResponse );
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
		if( $validator->checkExist( $datObj, 'RelatedTargets' ) ) {
			$validator->enterPath( 'RelatedTargets' );
			if( !is_null( $datObj->RelatedTargets ) ) {
				$validator->checkType( $datObj->RelatedTargets, 'array' );
				if( !empty($datObj->RelatedTargets) ) foreach( $datObj->RelatedTargets as $listItem ) {
					$validator->enterPath( 'ObjectTargetsInfo' );
					$validator->checkType( $listItem, 'ObjectTargetsInfo' );
					WflObjectTargetsInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Dossiers' ) ) {
			$validator->enterPath( 'Dossiers' );
			if( !is_null( $datObj->Dossiers ) ) {
				$validator->checkType( $datObj->Dossiers, 'array' );
				if( !empty($datObj->Dossiers) ) foreach( $datObj->Dossiers as $listItem ) {
					$validator->enterPath( 'ObjectInfo' );
					$validator->checkType( $listItem, 'ObjectInfo' );
					WflObjectInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetDialogResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

