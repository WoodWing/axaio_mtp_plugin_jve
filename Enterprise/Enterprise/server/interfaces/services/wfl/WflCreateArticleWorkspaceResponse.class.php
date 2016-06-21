<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflCreateArticleWorkspaceResponse
{
	public $WorkspaceId;

	/**
	 * @param string               $WorkspaceId               
	 */
	public function __construct( $WorkspaceId=null )
	{
		$this->WorkspaceId          = $WorkspaceId;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreateArticleWorkspaceResponse' );
		if( $validator->checkExist( $datObj, 'WorkspaceId' ) ) {
			$validator->enterPath( 'WorkspaceId' );
			$validator->checkNull( $datObj->WorkspaceId );
			if( !is_null( $datObj->WorkspaceId ) ) {
				$validator->checkType( $datObj->WorkspaceId, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCreateArticleWorkspaceResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

