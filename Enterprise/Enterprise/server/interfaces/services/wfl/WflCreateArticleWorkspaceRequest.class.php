<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflCreateArticleWorkspaceRequest
{
	public $Ticket;
	public $ID;
	public $Format;
	public $Content;

	/**
	 * @param string               $Ticket                    
	 * @param string               $ID                        Nullable.
	 * @param string               $Format                    
	 * @param string               $Content                   Nullable.
	 */
	public function __construct( $Ticket=null, $ID=null, $Format=null, $Content=null )
	{
		$this->Ticket               = $Ticket;
		$this->ID                   = $ID;
		$this->Format               = $Format;
		$this->Content              = $Content;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CreateArticleWorkspaceRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
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
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflCreateArticleWorkspaceRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

