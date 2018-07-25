<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflSaveArticleInWorkspaceRequest
{
	public $Ticket;
	public $WorkspaceId;
	public $ID;
	public $Format;
	public $Content;
	public $Elements;

	/**
	 * @param string               $Ticket                    
	 * @param string               $WorkspaceId               
	 * @param string               $ID                        Nullable.
	 * @param string               $Format                    
	 * @param string               $Content                   Nullable.
	 * @param Element[]            $Elements                  Nullable.
	 */
	public function __construct( $Ticket=null, $WorkspaceId=null, $ID=null, $Format=null, $Content=null, $Elements=null )
	{
		$this->Ticket               = $Ticket;
		$this->WorkspaceId          = $WorkspaceId;
		$this->ID                   = $ID;
		$this->Format               = $Format;
		$this->Content              = $Content;
		$this->Elements             = $Elements;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'SaveArticleInWorkspaceRequest' );
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
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Format' ) ) {
			$validator->enterPath( 'Format' );
			$validator->checkNull( $datObj->Format );
			if( !is_null( $datObj->Format ) ) {
				$validator->checkType( $datObj->Format, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Content' ) ) {
			$validator->enterPath( 'Content' );
			if( !is_null( $datObj->Content ) ) {
				$validator->checkType( $datObj->Content, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Elements' ) ) {
			$validator->enterPath( 'Elements' );
			if( !is_null( $datObj->Elements ) ) {
				$validator->checkType( $datObj->Elements, 'array' );
				if( !empty($datObj->Elements) ) foreach( $datObj->Elements as $listItem ) {
					$validator->enterPath( 'Element' );
					$validator->checkType( $listItem, 'Element' );
					WflElementValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSaveArticleInWorkspaceRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Elements)){
			if (is_object($this->Elements[0])){
				foreach ($this->Elements as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

