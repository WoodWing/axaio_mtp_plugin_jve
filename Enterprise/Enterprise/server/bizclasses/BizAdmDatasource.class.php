<?php
/**
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBDatasource.class.php';
require_once BASEDIR.'/server/bizclasses/BizDatasourceUtils.php';
require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

class BizAdminDatasource
{	
	/**
	 * Get a list of (all) publications from SCE
	 *
	 * @param string $dsid
	 * @return AdsPublication[]
	 * @throws BizException
	 */
	public static function getPublications( $dsid='' )
	{
		$return = array();

		if( $dsid ) {
			$publications = DBDatasource::getPublications( $dsid );
		} else {
			$publications = DBDatasource::getAllPublications();
		}
		
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
		
		foreach( $publications as &$publication ) {
			$return[] = BizDatasourceUtils::publicationToObj( $publication );
		}
		
		return $return;
	}
	
	public static function savePublication( $datasourceid, $publicationid )
	{
		// check if entry already exists
		$ePubs = self::getPublications( $datasourceid );
		$exists = false;
		foreach( $ePubs as $ePub ) {
			if( $ePub->ID == $publicationid ) {
				$exists = true;
			}
		}
		
		if( $exists == true ) {
			throw new BizException( 'ERR_DATABASE', 'Server', 'The DataSource is already linked to this Publication.' );
		} else {
			DBDatasource::newPublication( $publicationid, $datasourceid );
		}
	}
	
	public static function saveQueryField( $queryid, $name, $priority, $readonly, $updatemode=false )
	{
		// check if field name is not empty
		if( trim($name) == "" ) {
			throw new BizException( 'ERR_ERROR', 'Server', 'The field name you entered was empty.'); // BZ#636
		}
		
		// check if entry already exists
		$fields = self::getQueryFields( $queryid );
		$exists = false;
		$exist_id = 0;
		
		if( count( $fields ) > 0 ) {
			foreach( $fields as $field ) {
				if( $field->Name == $name ) {
					$exists = true;
					$exist_id = $field->ID;
				}
			}
		}
		
		if( $exists == true && $updatemode == false ) {
			throw new BizException( 'ERR_ERROR', 'Server', 'The field you entered already exist.' ); // BZ#636
		} else {
			// BZ#12649 use of ctype_digit is very dirty and doesn't work if variable is a real int
			// for now we convert it to string first.
			// In the future we should onyl pass 0 or 1 and convert variable before we call this function
			if( !ctype_digit( strval($priority) ) ) {
				$priority = ($priority == 'on') ? 1 : 0;
			}
			
			if( !ctype_digit( strval($readonly) ) ) {
				$readonly = ($readonly == 'on') ? 1 : 0;
			}
			
			if( $updatemode == false ) {
				DBDatasource::newField( $queryid, $name, $priority, $readonly );
			} else {
				DBDatasource::updateField( $exist_id, $name, $priority, $readonly);
			}
		}
	}
	
	public static function deletePublication( $datasourceid, $publicationid )
	{
		DBDatasource::deletePublication( $publicationid, $datasourceid );
	}
	
	public static function deleteQueryField( $fieldid )
	{
		DBDatasource::deleteQueryField( $fieldid );
	}
	
	/**
	 * Retrieve a list of Datasources.
	 * Retrieve a list of available Datasources (by publication id)
	 *
	 * @param string $type
	 * @throws BizException
	 * @return AdsDatasourceInfo[]
	 */
	public static function queryDatasources( $type='' )
	{
		//get all datasources
		$datasources = DBDatasource::queryDatasources();		
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
		
		$return = array();
		foreach( $datasources as &$datasource ) {
			if( $type && $datasource["type"] == $type ) {
				$return[] = BizDatasourceUtils::adminDatasourceToObj( $datasource );
			} else {
				$return[] = BizDatasourceUtils::adminDatasourceToObj( $datasource );
			}
		}
		
		return $return;
	}
	
	/**
	 * Get the basic information of a data source
	 *
	 * @param DataSource ID $datasourceid
	 * @return AdsDataSourceInfo
	 */
	public static function getDatasourceInfo( $datasourceid )
	{
		$datasourcerow = DBDatasource::getDatasourceInfo( $datasourceid );
		$datasource = BizDatasourceUtils::adminDatasourceToObj($datasourcerow);
		return $datasource;
	}
	
	/**
	 * Retrieve a Datasource.
	 * Retrieve information about a Datasource such as a list of Queries, etc by Datasource id.
	 *
	 * @param string $datasourceid
	 * @throws BizException
	 * @return AdsGetDatasourceResponse
	 */
	public static function getDatasource( $datasourceid = '' )
	{
		//
		// get queries of this datasource (if any; hard coded queries will not be displayed!)
		$queries = DBDatasource::getQueries( $datasourceid );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
		
		$returnquery = array();
		foreach( $queries as &$query ) {
			$returnquery[] = BizDatasourceUtils::adminQueryToObj($query);
		}
		
		//
		// get settings of this datasource
		$settings = DBDatasource::getSettings( $datasourceid, true );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );	
		}
		
		$returnsetting = array();
		foreach( $settings as &$setting ) {
			$returnsetting[] = BizDatasourceUtils::settingToObj( $setting );
		}
		
		//
		// get publications of this datasource
		$publications = DBDatasource::getPublications( $datasourceid );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );	
		}
		
		$returnpublication = array();
		foreach( $publications as &$publication ) {
			$returnpublication[] = BizDatasourceUtils::publicationToObj( $publication );
		}
		
		require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceResponse.class.php';
		return new AdsGetDatasourceResponse( $returnquery, $returnsetting, $returnpublication );
	}
	
	
	/**
	 * Get a Query (by Query ID) from the database.
	 *
	 * @param string $queryid
	 * @throws BizException
	 * @return AdsQuery
	 */
	public static function getQuery( $queryid = '' )
	{
		//
		// get query from the database
		$query = DBDatasource::getQuery( $queryid );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
		$returnquery = BizDatasourceUtils::adminQueryToObj($query);
		
		return $returnquery;
	}
	
	public static function getQueries( $datasourceid )
	{
	//
		// get queries of this datasource (if any; hard coded queries will not be displayed!)
		$queries = DBDatasource::getQueries( $datasourceid );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
		
		$returnquery = array();
		foreach( $queries as &$query ) {
			$returnquery[] = BizDatasourceUtils::adminQueryToObj($query);
		}
		
		return $returnquery;
	}
	
	public static function getQueryFields( $queryid = '' )
	{
		//
		// get fields from the database
		$fields = DBDatasource::getQueryFields( $queryid );
		
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_ERROR', 'Server', DBDatasource::getError() );
		}
		
		$returnfields = array();
		foreach( $fields as &$field ) {
			$returnfields[] = BizDatasourceUtils::queryfieldToObj( $field );
		}
		
		return $returnfields;
	}
	
	
	/**
	 * Get the available data source types from DB.
	 *
	 * @return	AdsDatasourceType[]
	 */
	public static function getDatasourceTypes()
	{
		$datTypes = DBDatasource::getDatasourceTypes();
		$return = array();
		foreach( $datTypes as $datType ) {
			$return[] = BizDatasourceUtils::datasourceTypeToObj( $datType['type'] );
		}
		return $return;
	}
	
	public static function getDatasourceType( $datasourceid )
	{
		$typerow = DBDatasource::getDatasourceType( $datasourceid );
		return $typerow["type"];
	}
	
	public static function newQuery( $datasourceid, $name, $query, $interface, $comment, $recordid, $recordfamily )
	{
		if( trim($name) == "" ) {
			throw new BizException( 'ERR_DATABASE', 'Server', 'The filter name is empty.' ); // BZ#636
		}
		
		return DBDatasource::newQuery( $datasourceid, $name, $query, $interface, $comment, $recordid, $recordfamily );
	}
	
	/**
	 * Saves a Query.
	 * This method will save (= create/modify) a Query into the database.
	 * A Query exists of an actual query object and an array of field objects.
	 *
	 * @param int $queryid
	 * @param string $name
	 * @param string $sqlquery
	 * @param string $interface
	 * @param string $comment
	 * @param string $recordId
	 * @param string $recordfamily
	 * @throws BizException
	 */
	public static function saveQuery( $queryid, $name, $sqlquery, $interface, $comment, $recordId, $recordfamily )
	{
		if( trim($name) == "" ) {
			throw new BizException( 'ERR_DATABASE', 'Server', 'The filter name was empty.' );
		} else {
			DBDatasource::updateQuery( $queryid, $name, $sqlquery, $interface, $comment, $recordId, $recordfamily );
		}
	}
	
	/**
	 * Delete a query and its related fields
	 *
	 * @param int $queryid
	 * @throws BizException
	 */
	public static function deleteQuery( $queryid )
	{
		DBDatasource::deleteQuery( $queryid );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
	}

	public static function newDatasource( $name, $type, $bidirectional )
	{
		// check if data source name is not empty
		if( trim($name) == "" ) {
			throw new BizException( 'ERR_ERROR', 'Server', 'The data source name you entered was empty.'); // BZ#636
		}
		
		// check if the connector exists
		$connectors = BizServerPlugin::searchConnectors( 'DataSource', $type );
		if( count($connectors) == 0 ) {
			throw new BizException( 'ERR_DATABASE', 'Server', 'The DataSource type: '.$type.' does not (or no longer) exist.' ); // BZ#636
		}
		
		// check if the datasource already exists
		$datasources = self::queryDatasources();
		foreach( $datasources as $datasource ) {
			if( $datasource->Name == $name ) {
				throw new BizException( 'ERR_DATABASE', 'Server', 'A DataSource with that name already exists.' ); // BZ#636
			}
		}
		
		// save the datasource
		return DBDatasource::newDatasource($name, $type, $bidirectional);
	}
	
	public static function saveDatasource( $id, $name, $bidirectional )
	{
		// check if the name is not empty
		if( trim($name) == "" ) {
			throw new BizException( 'ERR_ERROR', 'Server', 'The data source name you entered was empty.'); // BZ#636
		}
		
		// check if the datasource already exists
		$datasources = self::queryDatasources();
		foreach( $datasources as $datasource ) {
			if( $datasource->Name == $name && $datasource->ID != $id ) {
				throw new BizException( 'ERR_DATABASE', 'Server', 'A DataSource with that name already exists.' ); // BZ#636
			}
		}
		
		// save the datasource
		DBDatasource::updateDatasource( $id, $name, $bidirectional );
	}
	
	public static function getSettingsDetails( $datasourceid )
	{
		$datasource = self::createPlainDatasourceConnector( $datasourceid );
		$set = DBDatasource::getSettings( $datasourceid );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
		$datasource->setSettings( $set );
		$settings = $datasource->getSettings();
		$settingsdetails = array();
		
		// convert all setting array's to objects
		foreach( $settings as $setting ) {
			$settingsdetails[] = BizDatasourceUtils::settingUIToObj( 
				$setting['name'], $setting['desc'], $setting['type'], // mandatory
				isset($setting['list']) ? $setting['list'] : null, 
				isset($setting['size']) ? $setting['size'] : null );
		}
		
		return $settingsdetails;
	}
	
	public static function getSettings( $datasourceid )
	{
		//
		// get settings of this datasource
		$settings = DBDatasource::getSettings( $datasourceid, true );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );	
		}
		
		$returnsetting = array();
		foreach( $settings as &$setting ) {
			$returnsetting[] = BizDatasourceUtils::settingToObj( $setting );
		}
		
		return $returnsetting;
	}
	
	public static function saveSetting( $datasourceid, $name, $value )
	{	
		// check if setting exists; if so update
		$datasource = self::getDatasource( $datasourceid );
		$settings = $datasource->Settings;
		
		$update = false;
		foreach( $settings as $setting ) {
			if( $setting->Name == $name ) {
				$update = true;
			}
		}
		
		if( $update == true ) {
			DBDatasource::updateSetting( $datasourceid, $name, $value );
		} else {
			DBDatasource::newSetting( $datasourceid, $name, $value );
		}
	}
	
	/**
	 * Create a Plain Datasource Object (without settings)
	 *
	 * @param int $datasourceid
	 * @throws BizException
	 * @return EnterpriseConnector
	 */
	private static function createPlainDatasourceConnector( $datasourceid )
	{
		if( $datasourceid ) {
			// get datasource type by datasource id
			$datasource = DBDatasource::getDatasourceType( $datasourceid );
			if( DBDatasource::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
			}
			
			if( $datasource ) {
				require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
				$connectors = BizServerPlugin::searchConnectors( 'DataSource', $datasource['type'] );
				if( count($connectors) == 0 ) {
					throw new BizException( 'ERR_DATABASE', 'Server', 'Could not create datasource object: No connector found that implements the specified datasource type "'.$datasource['type'].'".' ); // BZ#636
				}
				$connector = current($connectors); // let's take the first one (there should be only one?)
				// removed the settings, to use without actual instance (for example; if you want to use methods and there are no settings yet)
				return $connector;
			} else {
				throw new BizException( 'ERR_DATABASE', 'Server', 'Could not create datasource object: datasource type not found for ID "'.$datasourceid.'".' ); // BZ#636
			}
			
		} else {
			throw new BizException( 'ERR_DATABASE', 'Server', 'Could not create datasource object: no datasource ID' ); // BZ#636
		}
	}
	
	/**
	 * Delete a datasource (and its relations)
	 *
	 * @param int $datasourceid
	 * @throws BizException
	 */
	public static function deleteDatasource( $datasourceid )
	{
		// do a delete action
		DBDatasource::deleteDatasource( $datasourceid );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
	}
	
	
	public static function copyDatasource( $datasourceid, $newname, $copyqueries )
	{
		// check if the new name is not empty
		if( trim($newname) == "" ) {
			throw new BizException('ERR_ERROR','Server','The new data source name you entered was empty.'); // BZ#636
		}
		
		// get the data source we'd like to copy
		$datasources = self::queryDatasources();
		$new_datasource = '';
		foreach( $datasources as $datasource ) {
			if( $datasource->ID == $datasourceid ) {
				$new_datasource = $datasource;
				break;
			}
		}
		
		// create a new data source with values of the data source we'd like to copy
		$new_datasource_id = self::newDatasource( $newname, $new_datasource->Type, $new_datasource->Bidirectional );
		
		// copy settings
		$settings = self::getSettings( $datasourceid );
		foreach( $settings as $setting ) {
			self::saveSetting( $new_datasource_id, $setting->Name, $setting->Value );
		}
		
		// copy publications
		$publications = self::getPublications( $datasourceid );
		foreach( $publications as $publication ) {
			self::savePublication( $new_datasource_id, $publication->ID );
		}
		
		// if $copyqueries is true, also copy all the queries
		if( $copyqueries ) {
			$queries = self::getQueries( $datasourceid );
			foreach( $queries as $query ) {
				// create a new query with the values of the old query
				$new_query_id = self::newQuery( $new_datasource_id, $query->Name, $query->Query, $query->Interface, $query->Comment, $query->RecordID, $query->RecordFamily );
				// also copy the fields of every query
				$fields = self::getQueryFields( $query->ID );
				foreach( $fields as $field ) {
					// create a new field with the values of the old field
					self::saveQueryField( $new_query_id, $field->Name, $field->Priority, $field->ReadOnly );
				}
			}
		}
		
		return $new_datasource_id;
	}
	
	public static function copyQuery( $queryid, $targetid, $newname, $copyfields )
	{
		// check if the new query name is not empty
		if( trim($newname) == "" ) {
			throw new BizException('ERR_ERROR','Server','The new query name you entered was empty.'); // BZ#636
		}
		
		// get the query we'd like to copy
		$query = self::getQuery( $queryid );
		
		// copy the query by creating a new one
		$new_query_id = self::newQuery( $targetid, $newname, $query->Query, $query->Interface, $query->Comment, $query->RecordID, $query->RecordFamily );
		
		// also copy the fields of the query (if $copyfields is true)
		if( $copyfields ) {
			$fields = self::getQueryFields( $queryid );
			foreach( $fields as $field ) {
				// create a new field with the values of the old field
				self::saveQueryField( $new_query_id, $field->Name, $field->Priority, $field->ReadOnly );
			}
		}
		
		return $new_query_id;
	}
}
