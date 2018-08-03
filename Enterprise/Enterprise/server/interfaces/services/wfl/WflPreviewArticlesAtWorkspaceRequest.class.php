<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflPreviewArticlesAtWorkspaceRequest
{
	public $Ticket;
	public $WorkspaceId;
	public $Articles;
	public $Action;
	public $LayoutId;
	public $EditionId;
	public $PreviewType;
	public $RequestInfo;

	/**
	 * @param string               $Ticket                    
	 * @param string               $WorkspaceId               
	 * @param ArticleAtWorkspace[] $Articles                  
	 * @param string               $Action                    
	 * @param string               $LayoutId                  Nullable.
	 * @param string               $EditionId                 Nullable.
	 * @param string               $PreviewType               Nullable.
	 * @param string[]             $RequestInfo               Nullable.
	 */
	public function __construct( $Ticket=null, $WorkspaceId=null, $Articles=null, $Action=null, $LayoutId=null, $EditionId=null, $PreviewType=null, $RequestInfo=null )
	{
		$this->Ticket               = $Ticket;
		$this->WorkspaceId          = $WorkspaceId;
		$this->Articles             = $Articles;
		$this->Action               = $Action;
		$this->LayoutId             = $LayoutId;
		$this->EditionId            = $EditionId;
		$this->PreviewType          = $PreviewType;
		$this->RequestInfo          = $RequestInfo;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'PreviewArticlesAtWorkspaceRequest' );
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
		if( $validator->checkExist( $datObj, 'Articles' ) ) {
			$validator->enterPath( 'Articles' );
			$validator->checkNull( $datObj->Articles );
			if( !is_null( $datObj->Articles ) ) {
				$validator->checkType( $datObj->Articles, 'array' );
				if( !empty($datObj->Articles) ) foreach( $datObj->Articles as $listItem ) {
					$validator->enterPath( 'ArticleAtWorkspace' );
					$validator->checkType( $listItem, 'ArticleAtWorkspace' );
					WflArticleAtWorkspaceValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Action' ) ) {
			$validator->enterPath( 'Action' );
			$validator->checkNull( $datObj->Action );
			if( !is_null( $datObj->Action ) ) {
				$validator->checkType( $datObj->Action, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LayoutId' ) ) {
			$validator->enterPath( 'LayoutId' );
			if( !is_null( $datObj->LayoutId ) ) {
				$validator->checkType( $datObj->LayoutId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'EditionId' ) ) {
			$validator->enterPath( 'EditionId' );
			if( !is_null( $datObj->EditionId ) ) {
				$validator->checkType( $datObj->EditionId, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PreviewType' ) ) {
			$validator->enterPath( 'PreviewType' );
			if( !is_null( $datObj->PreviewType ) ) {
				$validator->checkType( $datObj->PreviewType, 'string' );
				WflPreviewTypeValidator::validate( $validator, $datObj->PreviewType );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestInfo' ) ) {
			$validator->enterPath( 'RequestInfo' );
			if( !is_null( $datObj->RequestInfo ) ) {
				$validator->checkType( $datObj->RequestInfo, 'array' );
				if( !empty($datObj->RequestInfo) ) foreach( $datObj->RequestInfo as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflPreviewArticlesAtWorkspaceRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Articles)){
			if (is_object($this->Articles[0])){
				foreach ($this->Articles as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->RequestInfo)){
			if (is_object($this->RequestInfo[0])){
				foreach ($this->RequestInfo as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

