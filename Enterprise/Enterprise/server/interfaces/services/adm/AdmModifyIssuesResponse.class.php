<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmModifyIssuesResponse
{
	public $PublicationId;
	public $PubChannelId;
	public $Issues;

	/**
	 * @param integer              $PublicationId             
	 * @param integer              $PubChannelId              
	 * @param AdmIssue[]           $Issues                    
	 */
	public function __construct( $PublicationId=null, $PubChannelId=null, $Issues=null )
	{
		$this->PublicationId        = $PublicationId;
		$this->PubChannelId         = $PubChannelId;
		$this->Issues               = $Issues;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'ModifyIssuesResponse' );
		if( $validator->checkExist( $datObj, 'PublicationId' ) ) {
			$validator->enterPath( 'PublicationId' );
			$validator->checkNull( $datObj->PublicationId );
			if( !is_null( $datObj->PublicationId ) ) {
				$validator->checkType( $datObj->PublicationId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PubChannelId' ) ) {
			$validator->enterPath( 'PubChannelId' );
			$validator->checkNull( $datObj->PubChannelId );
			if( !is_null( $datObj->PubChannelId ) ) {
				$validator->checkType( $datObj->PubChannelId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Issues' ) ) {
			$validator->enterPath( 'Issues' );
			$validator->checkNull( $datObj->Issues );
			if( !is_null( $datObj->Issues ) ) {
				$validator->checkType( $datObj->Issues, 'array' );
				if( !empty($datObj->Issues) ) foreach( $datObj->Issues as $listItem ) {
					$validator->enterPath( 'Issue' );
					$validator->checkType( $listItem, 'AdmIssue' );
					AdmIssueValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmModifyIssuesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

