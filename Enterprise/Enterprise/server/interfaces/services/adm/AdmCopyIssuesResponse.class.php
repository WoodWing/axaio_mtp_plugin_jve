<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmCopyIssuesResponse
{
	public $Issues;

	/**
	 * @param AdmIssue[]           $Issues                    
	 */
	public function __construct( $Issues=null )
	{
		$this->Issues               = $Issues;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CopyIssuesResponse' );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCopyIssuesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

