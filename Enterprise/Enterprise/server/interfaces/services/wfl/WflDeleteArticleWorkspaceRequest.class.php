<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflDeleteArticleWorkspaceRequest
{
	public $Ticket;
	public $WorkspaceId;

	/**
	 * @param string               $Ticket                    
	 * @param string               $WorkspaceId               
	 */
	public function __construct( $Ticket=null, $WorkspaceId=null )
	{
		$this->Ticket               = $Ticket;
		$this->WorkspaceId          = $WorkspaceId;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeleteArticleWorkspaceRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflDeleteArticleWorkspaceRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}
