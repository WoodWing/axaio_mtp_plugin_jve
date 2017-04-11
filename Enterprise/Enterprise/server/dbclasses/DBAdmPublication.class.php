<?php
/**
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Manages the smart_publications DB table to support admin functionality.
 * For workflow functionality, the DBPublication class must be used instead.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBAdmPublication extends DBBase
{
	const TABLENAME = 'publications';
	
	/**
	 * Retrieves one publication data object from DB for a given id ($pubId), as configured by admin users.
	 *  
	 * @param integer $pubId Publication ID
	 * @param array $typeMap Lookup table with custom property names as keys and types as values. Pass NULL to skip resolving props (which leaves ExtraMetaData set to null). 
	 * @return AdmPublication|null Publication on success, or NULL on error.
	 * @throws BizException on SQL error.
	 */
	public static function getPublicationObj( $pubId, $typeMap = null )
	{
		$row = self::getRow( self::TABLENAME, '`id` = ?', '*', array( intval($pubId) ) );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		if( !$row ) {
			return null;
		}
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$pubObj = self::rowToObj( $row );
		if( is_null( $typeMap ) ) {
			$pubObj->ExtraMetaData = null;
		} else {
			$pubObj->ExtraMetaData = DBChanneldata::getCustomProperties( 'Publication', $pubId, $typeMap );
		}
		return $pubObj;
	}
	
	/**
	 * Returns all publication data objects from DB, as configured by admin users.
	 *  
	 * @param array|null $typeMap Lookup table with custom property names as keys and types as values. Pass NULL to skip resolving props (which leaves ExtraMetaData set to null).
	 * @return AdmPublication[]
	 */
	public static function listPublicationsObj( $typeMap = null )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php'; 
		$rows = DBPublication::listPublications();

		$pubObjs = array();
		if( $rows ) foreach( $rows as $row ) {
			$pubObj = self::rowToObj( $row );
			if( is_null( $typeMap ) ) {
				$pubObj->ExtraMetaData = null;
			} else {
				require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
				$pubObj->ExtraMetaData = DBChanneldata::getCustomProperties( 'Publication', $row['id'], $typeMap );
			}
			$pubObjs[] = $pubObj;
		}
		return $pubObjs;
	}
	
	/**
	 * Saves a created publication data object into the DB, as configured by admin user.
	 *  
	 * @param $pubs array of values to create new publication
	 * @param array $typeMap Lookup table with custom property names as keys and types as values.
	 * @throws BizException Throws BizException on failure.
	 * @return AdmPublication[] List of newly created Publication objects.
	 */
	public static function createPublicationsObj( $pubs, $typeMap )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$createdPubs = array();
		
		foreach( $pubs as $pub ) {
			
			// Error on duplicates
			$row = self::getRow( self::TABLENAME, '`publication` = ?', array('id'), array( strval($pub->Name) ) );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			// Auto set the Description and SortOrder when not provided.
			if( is_null( $pub->Description ) ) {
				$pub->Description = '';
			}
			if( is_null( $pub->SortOrder ) ) {
				$row = self::getRow( self::TABLENAME, '', array('max(`code`) as `m`') );
				$pub->SortOrder = $row['m'];
			}

			// Store standard publication properties in DB.
			$pubRow = self::objToRow( $pub );
			$pubId = self::insertRow( self::TABLENAME, $pubRow );
			if( $pubId ) {

				// Store custom publication properties in DB.
				DBChanneldata::saveCustomProperties( 'Publication', $pubId, $pub->ExtraMetaData, $typeMap );
	
				// Retrieve whole publication from DB. This is to make sure that the caller
				// gets exactly the same data after a 'create' as after a 'get' operation.
				$createdPubs[] = self::getPublicationObj( $pubId, $typeMap );
			}	
		}
		return $createdPubs;
	}
	
	/**
	 * Saves a modified publication data object into the DB, as configured by admin user.
	 *  
	 * @param $pubs array of values to modify existing publication
	 * @param array $typeMap Lookup table with custom property names as keys and types as values.
	 * @throws BizException Throws BizException on failure.
	 * @return AdmPublication[] List of modified Publication objects.
	 */
	public static function modifyPublicationsObj( $pubs, $typeMap )
	{	
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$modifiedPubs = array();
		
		foreach( $pubs as $pub ) {
	
			// Error on duplicates.
			if( !is_null($pub->Name) ) {
				$where = '`publication` = ? AND `id` != ?';
				$params = array( strval($pub->Name), intval($pub->Id) );
				$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
				if( self::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
				}
				if( $row ) {
					throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id']);
				}
			}
			
			// Store custom publication properties in DB.
			DBChanneldata::saveCustomProperties( 'Publication', $pub->Id, $pub->ExtraMetaData, $typeMap );
			
			// Store standard publication properties in DB.
			$pubRow = self::objToRow( $pub );
			$result = self::updateRow( self::TABLENAME, $pubRow, '`id` = ?', array( intval($pub->Id) ) );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}

			// Retrieve whole publication from DB. This is to make sure that the caller
			// gets exactly the same data after a 'save' as after a 'get' operation.
			if( $result === true ) {
				$modifiedPubs[] = self::getPublicationObj( $pub->Id, $typeMap );
			}	
		}
		return $modifiedPubs;
	}
	
	/**
	 *  Converts a given AdmPublication data object to a DB row (array).
	 *
	 *  @param AdmPublication $obj The publication data object to convert.
	 *  @return array DB row that holds the publication properties as key-value pairs.
	 */
	static public function objToRow( $obj )
	{	
		$row = array();
		if(!is_null($obj->Name)){
			$row['publication'] 	= strval($obj->Name);
		}
		if(!is_null($obj->Description)){
			$row['description'] 	= strval($obj->Description);
		}
		if(!is_null($obj->EmailNotify)){
			$row['email'] 	  		= ($obj->EmailNotify == true ? 'on' : '');
		}
		if(!is_null($obj->ReversedRead)){
			$row['readingorderrev'] = ($obj->ReversedRead == true ? 'on' : '');	
		}
		if(!is_null($obj->AutoPurge)){
			$row['autopurge']		= intval($obj->AutoPurge);
		}
		if(!is_null($obj->SortOrder)){
			$row['code'] 			= $obj->SortOrder ? intval($obj->SortOrder) : 0 ;
		}
		if(!is_null($obj->DefaultChannelId)){
			$row['defaultchannelid'] = intval($obj->DefaultChannelId);
		}
		if( !is_null( $obj->CalculateDeadlines ) ) {
			$row['calculatedeadlines'] = ( $obj->CalculateDeadlines == true ? 'on' : '' );
		}
		return $row;
	}
	
	/**
	 *  Converts a given DB row to a AdmPublication data object.
	 *
	 *  @param array $row DB row that contains key-value pairs to convert.
	 *  @return AdmPublication The publication data object.
	 */
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$pub = new AdmPublication();
		$pub->Id               = intval($row['id']);
		$pub->Name             = $row['publication'];
		$pub->Description      = $row['description'];
		$pub->EmailNotify      = ($row['email'] == 'on' ? true : false);
		$pub->ReversedRead     = ($row['readingorderrev'] == 'on' ? true : false);
		$pub->AutoPurge        = $row['autopurge'];
		$pub->SortOrder        = intval($row['code']);
		$pub->DefaultChannelId = intval($row['defaultchannelid']);
		$pub->CalculateDeadlines = ( $row['calculatedeadlines'] == 'on' ? true : false );

		return $pub;
	}
}
