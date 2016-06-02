<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflGetDialog2Response
{
	public $Dialog;
	public $PubChannels;
	public $MetaData;
	public $Targets;
	public $RelatedTargets;
	public $Relations;

	/**
	 * @param Dialog               $Dialog                    Nullable.
	 * @param PubChannelInfo[]     $PubChannels               Nullable.
	 * @param MetaData             $MetaData                  Nullable.
	 * @param Target[]             $Targets                   Nullable.
	 * @param ObjectTargetsInfo[]  $RelatedTargets            Nullable.
	 * @param Relation[]           $Relations                 Nullable.
	 */
	public function __construct( $Dialog=null, $PubChannels=null, $MetaData=null, $Targets=null, $RelatedTargets=null, $Relations=null )
	{
		$this->Dialog               = $Dialog;
		$this->PubChannels          = $PubChannels;
		$this->MetaData             = $MetaData;
		$this->Targets              = $Targets;
		$this->RelatedTargets       = $RelatedTargets;
		$this->Relations            = $Relations;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetDialog2Response' );
		if( $validator->checkExist( $datObj, 'Dialog' ) ) {
			$validator->enterPath( 'Dialog' );
			if( !is_null( $datObj->Dialog ) ) {
				$validator->checkType( $datObj->Dialog, 'Dialog' );
				WflDialogValidator::validate( $validator, $datObj->Dialog );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PubChannels' ) ) {
			$validator->enterPath( 'PubChannels' );
			if( !is_null( $datObj->PubChannels ) ) {
				$validator->checkType( $datObj->PubChannels, 'array' );
				if( !empty($datObj->PubChannels) ) foreach( $datObj->PubChannels as $listItem ) {
					$validator->enterPath( 'PubChannelInfo' );
					$validator->checkType( $listItem, 'PubChannelInfo' );
					WflPubChannelInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetDialog2Response'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

