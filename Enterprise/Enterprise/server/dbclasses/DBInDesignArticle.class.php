<?php
/**
 * Implements DB querying of InDesignArticle data objects.
 * 
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v9.7
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBInDesignArticle extends DBBase
{
 	const TABLENAME = 'indesignarticles';
 	
	/**
	 * Creates the given InDesign Articles in DB for a certain layout object.
	 *
	 * The records are inserted by one statement. In that case it is important that for each record all columns are set.
	 * To ensure this a row template is used in which all values are merged.
	 *
	 * @since 9.7.0
	 * @param integer $layoutId
	 * @param InDesignArticle[] $articles The InDesign Articles to be created.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function createInDesignArticles( $layoutId, $articles )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$layoutId = intval( $layoutId );
		if( !$layoutId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		$order = 0;
		require_once BASEDIR.'/server/dbclasses/DBCustomField.class.php';
		$rowTemplate = DBCustomField::getFieldsAtModel( self::TABLENAME );
		$rowTemplate = array_fill_keys( array_keys( $rowTemplate ), '');
		$values = array();
		if( $articles ) foreach( $articles as $article ) {
			$row = self::objToRow( $article );
			$row['objid'] = $layoutId;
			$row['code'] = $order;
			$order += 1;
			$rowValues = array_merge( $rowTemplate, $row );
			$values[] = array_values( $rowValues );
		}
		if( $values ) {
			$result = self::insertRows( self::TABLENAME, array_keys( $rowTemplate ), $values, false );
			if( !$result ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}
	}

	/**
	 * Retrieves the InDesign Articles from DB for a given layout object.
	 *
	 * @since 9.7.0
	 * @param integer $layoutId
	 * @param boolean $resolveSortedSplineIds
	 * @return InDesignArticle[] The InDesign Articles. Empty when none found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
 	public static function getInDesignArticles( $layoutId, $resolveSortedSplineIds = false )
 	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$layoutId = intval( $layoutId );
		if( !$layoutId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
 		$where = '`objid` = ?';
 		$params = array( $layoutId );
 		$orderBy = array( 'code' => true ); // ORDER BY `code` ASC (to get the creation order)
		$rows = self::listRows( self::TABLENAME, '', '', $where, '*', $params, $orderBy );
		if( self::hasError() || is_null($rows) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Convert rows to data objects.
	   /** @var InDesignArticle[] $articles */
		$articles = array();
		if( $rows ) foreach( $rows as $row ) {
			$articles[] = self::rowToObj( $row );
		}

		// Enrich the InDesign Articles with the sorted spline ids.
		if( $resolveSortedSplineIds && $articles ) {
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
			$sortedSplineIds = DBInDesignArticlePlacement::getSortedSplineIdsForInDesignArticlesOnLayout( $layoutId );
			foreach( $articles as $article ) {
				if( array_key_exists( $article->Id, $sortedSplineIds ) && count( $sortedSplineIds[ $article->Id ] ) > 0 ) {
					$article->SplineIDs = $sortedSplineIds[ $article->Id ];
				}
			}
		}

		return $articles;

 	}

	/**
	 * Deletes the InDesign Articles from DB for a given layout object.
	 *
	 * @since 9.7.0
	 * @param integer $layoutId
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
 	public static function deleteInDesignArticles( $layoutId )
 	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$layoutId = intval( $layoutId );
		if( !$layoutId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

 		$where = '`objid` = ?';
 		$params = array( $layoutId );
 		$result = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		// Cascade delete the InDesignArticle placements.
		require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
		DBInDesignArticlePlacement::unlinkInDesignArticleToPlacementByObjectId( $layoutId );
 	}
 	
    /**
     * Converts an InDesignArticle object into a DB row.
     * Both represent an InDesign Article.
     *
	 * @since 9.7.0
     * @param InDesignArticle $obj
     * @return array DB row
     */
	static public function objToRow( InDesignArticle $obj )
	{	
		$row = array();
		
		if( !is_null($obj->Id) ) {
			$row['artuid'] = $obj->Id;
		}
		if( !is_null($obj->Name) ) {
			require_once BASEDIR.'/server/utils/UtfString.class.php';
			$row['name'] = UtfString::removeIllegalUnicodeCharacters( $obj->Name );
			$row['name'] = UtfString::truncateMultiByteValue( $row['name'], 200 );
		}
		
		return $row;
	}

	/**
     * Converts a DB row into an InDesignArticle object.
     * Both represent an InDesign Article.
     *
	 * @since 9.7.0
     * @param array $row
     * @return InDesignArticle
     */
	static private function rowToObj( $row )
	{
		$obj = new InDesignArticle();
		
		if( array_key_exists( 'artuid', $row ) ) {
			$obj->Id = $row['artuid'];
		}
		if( array_key_exists( 'name', $row ) ) {
			$obj->Name = $row['name'];
		}
		
		return $obj;
	}
}