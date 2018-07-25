<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetArticleFromWorkspaceResponse
{
	public $ID;
	public $Format;
	public $Content;

	/**
	 * @param string               $ID                        Nullable.
	 * @param string               $Format                    
	 * @param string               $Content                   
	 */
	public function __construct( $ID=null, $Format=null, $Content=null )
	{
		$this->ID                   = $ID;
		$this->Format               = $Format;
		$this->Content              = $Content;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetArticleFromWorkspaceResponse' );
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
			$validator->checkNull( $datObj->Content );
			if( !is_null( $datObj->Content ) ) {
				$validator->checkType( $datObj->Content, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetArticleFromWorkspaceResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

