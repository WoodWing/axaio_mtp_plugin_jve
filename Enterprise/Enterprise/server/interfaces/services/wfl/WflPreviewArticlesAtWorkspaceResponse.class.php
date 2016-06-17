<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflPreviewArticlesAtWorkspaceResponse
{
	public $Placements;
	public $Elements;
	public $Pages;
	public $LayoutVersion;
	public $InDesignArticles;
	public $Relations;

	/**
	 * @param Placement[]          $Placements                
	 * @param Element[]            $Elements                  
	 * @param Page[]               $Pages                     Nullable.
	 * @param string               $LayoutVersion             
	 * @param InDesignArticle[]    $InDesignArticles          Nullable.
	 * @param Relation[]           $Relations                 Nullable.
	 */
	public function __construct( $Placements=null, $Elements=null, $Pages=null, $LayoutVersion=null, $InDesignArticles=null, $Relations=null )
	{
		$this->Placements           = $Placements;
		$this->Elements             = $Elements;
		$this->Pages                = $Pages;
		$this->LayoutVersion        = $LayoutVersion;
		$this->InDesignArticles     = $InDesignArticles;
		$this->Relations            = $Relations;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'PreviewArticlesAtWorkspaceResponse' );
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
		if( $validator->checkExist( $datObj, 'InDesignArticles' ) ) {
			$validator->enterPath( 'InDesignArticles' );
			if( !is_null( $datObj->InDesignArticles ) ) {
				$validator->checkType( $datObj->InDesignArticles, 'array' );
				if( !empty($datObj->InDesignArticles) ) foreach( $datObj->InDesignArticles as $listItem ) {
					$validator->enterPath( 'InDesignArticle' );
					$validator->checkType( $listItem, 'InDesignArticle' );
					WflInDesignArticleValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Relations' ) ) {
			$validator->enterPath( 'Relations' );
			if( !is_null( $datObj->Relations ) ) {
				$validator->checkType( $datObj->Relations, 'array' );
				if( !empty($datObj->Relations) ) foreach( $datObj->Relations as $listItem ) {
					$validator->enterPath( 'Relation' );
					$validator->checkType( $listItem, 'Relation' );
					WflRelationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflPreviewArticlesAtWorkspaceResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

