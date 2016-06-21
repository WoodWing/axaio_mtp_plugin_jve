<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflPreviewArticleAtWorkspaceResponse
{
	public $Placements;
	public $Elements;
	public $Pages;
	public $LayoutVersion;

	/**
	 * @param Placement[]          $Placements                
	 * @param Element[]            $Elements                  
	 * @param Page[]               $Pages                     Nullable.
	 * @param string               $LayoutVersion             
	 */
	public function __construct( $Placements=null, $Elements=null, $Pages=null, $LayoutVersion=null )
	{
		$this->Placements           = $Placements;
		$this->Elements             = $Elements;
		$this->Pages                = $Pages;
		$this->LayoutVersion        = $LayoutVersion;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'PreviewArticleAtWorkspaceResponse' );
		if( $validator->checkExist( $datObj, 'Placements' ) ) {
			$validator->enterPath( 'Placements' );
			$validator->checkNull( $datObj->Placements );
			if( !is_null( $datObj->Placements ) ) {
				$validator->checkType( $datObj->Placements, 'array' );
				if( !empty($datObj->Placements) ) foreach( $datObj->Placements as $listItem ) {
					$validator->enterPath( 'Placement' );
					$validator->checkType( $listItem, 'Placement' );
					WflPlacementValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Elements' ) ) {
			$validator->enterPath( 'Elements' );
			$validator->checkNull( $datObj->Elements );
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
		if( $validator->checkExist( $datObj, 'Pages' ) ) {
			$validator->enterPath( 'Pages' );
			if( !is_null( $datObj->Pages ) ) {
				$validator->checkType( $datObj->Pages, 'array' );
				if( !empty($datObj->Pages) ) foreach( $datObj->Pages as $listItem ) {
					$validator->enterPath( 'Page' );
					$validator->checkType( $listItem, 'Page' );
					WflPageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LayoutVersion' ) ) {
			$validator->enterPath( 'LayoutVersion' );
			$validator->checkNull( $datObj->LayoutVersion );
			if( !is_null( $datObj->LayoutVersion ) ) {
				$validator->checkType( $datObj->LayoutVersion, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflPreviewArticleAtWorkspaceResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

