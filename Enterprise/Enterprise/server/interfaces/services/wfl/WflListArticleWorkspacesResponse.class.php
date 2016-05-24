<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflListArticleWorkspacesResponse
{
	public $Workspaces;

	/**
	 * @param string[]             $Workspaces                
	 */
	public function __construct( $Workspaces=null )
	{
		$this->Workspaces           = $Workspaces;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'ListArticleWorkspacesResponse' );
		if( $validator->checkExist( $datObj, 'Workspaces' ) ) {
			$validator->enterPath( 'Workspaces' );
			$validator->checkNull( $datObj->Workspaces );
			if( !is_null( $datObj->Workspaces ) ) {
				$validator->checkType( $datObj->Workspaces, 'array' );
				if( !empty($datObj->Workspaces) ) foreach( $datObj->Workspaces as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflListArticleWorkspacesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

