<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCopyObjectResponse
{
	public $MetaData;
	public $Relations;
	public $Targets;

	/**
	 * @param MetaData             $MetaData                  
	 * @param Relation[]           $Relations                 
	 * @param Target[]             $Targets                   
	 */
	public function __construct( $MetaData=null, $Relations=null, $Targets=null )
	{
		$this->MetaData             = $MetaData;
		$this->Relations            = $Relations;
		$this->Targets              = $Targets;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CopyObjectResponse' );
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
			$validator->checkNull( $datObj->Relations );
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
			$validator->checkNull( $datObj->Targets );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCopyObjectResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

