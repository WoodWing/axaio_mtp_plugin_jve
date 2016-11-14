<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * This content source implements a hidden named query for content station
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';

class ContentStationListDossiers_ContentSource extends ContentSource_EnterpriseConnector
{
	const LIST_DOSSIERS_QUERY_NAME = 'LIST_DOSSIERS';
	
	const LIST_DOSSIER_TEMPLATE_QUERY_NAME = 'DOSSIER_TEMPLATES_IN_APPLICATIONS';
	
	const CONTENTSOURCEID		= 'WWCSLD';	
	
	final public function getConnectorType()  { return 'ContentSourceService'; }

	/**
	 * getContentSourceId
	 * 
	 * Return unique identifier for this content source implementation. Each alien object id needs
	 * to start with _<this id>_
	 * 
	 * @return string	unique identifier for this content source, without underscores.
	 */
 	final public function getContentSourceId( )
	{
		return self::CONTENTSOURCEID;
	}
	
	/**
	 * Implements hidden query LIST_DOSSIERS_QUERY_NAME
	 *
	 * @param string $query
	 * @return bool
	 */
	final public function implementsQuery( $query )
	{
		// support LIST_DOSSIERS or LIST_DOSSIER_TEMPLATE_QUERY_NAME
		if ($query == self::LIST_DOSSIERS_QUERY_NAME || $query == self::LIST_DOSSIER_TEMPLATE_QUERY_NAME){
			return true;
		}
		
		return false;
	}
	
	/**
	 * Doesn't return queries because you don't want to see this one in the user interface.
	 *
	 * @return array always empty
	 */
	final public function getQueries( )
	{
		$queries = array();
		
		// remove comment signs to show it in the user interface
		/*$queryParam = new PropertyInfo( 
			'ObjectId', 'Object ID', // Name, Display Name
			null,				// Category, not used
			'int',				// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			0,		// Default value
			null,		// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
		);
		
		$queries[] = new NamedQueryType( self::LIST_DOSSIERS_QUERY_NAME, array($queryParam) );
		*/
		
		return $queries;
	}
	
	/**
	 * doNamedQuery
	 * 
	 * Execute query on content source.
	 *
	 * @param string $query Query name as obtained from getQueries
	 * @param Property[] $params Query parameters as filled in by user
	 * @param int (unsigned) $firstEntry Index of first requested object of total count (TotalEntries)
	 * @param int (unsigned) $maxEntries Max count of requested objects (zero for all, nil for default)
	 * @param QueryOrder[]	$order
	 * @return WflNamedQueryResponse
	 */
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		LogHandler::Log('ContentStationListDossiers', 'DEBUG', 'ContentStationListDossiers::queryObjects called' );
        
		$shortusername = BizSession::getShortUserName();
		
		$dbdriver = DBDriverFactory::gen();
		$sql = '';
		if( $query == self::LIST_DOSSIERS_QUERY_NAME ) {
			//create a view containing all object-id's for which $user is authorized and has view access for
			DBQuery::createAuthorizedObjectsView($shortusername,  false, null, false, false);

			$tempaov = DBQuery::getTempIds('aov');

			$sql = 'SELECT DISTINCT o.`id` as `ID`, o.`type` as `Type`, o.`name` as `Name`, o.`format` as `Format`, o.`publication` as `PublicationId`, o.`issue` as `IssueId` '
				. 'FROM smart_objects o '
				. 'INNER JOIN ' . $tempaov . ' aov ON (aov.`id` = o.`id`) '
				. 'INNER JOIN `smart_objectrelations` orel ON (orel.`parent` = o.`id`) '
				. 'WHERE o.`type` = \'Dossier\' '
				. 'AND orel.`child` = ' . intval($params[0]->Value) . ' ';
		}
		elseif ( $query == self::LIST_DOSSIER_TEMPLATE_QUERY_NAME ) {
			//create a view containing all object-id's for which $user is authorized
			DBQuery::createAuthorizedObjectsView($shortusername,  false, null, false, false, '', 0 /* Skip access right */);

			$tempaov = DBQuery::getTempIds('aov');

			$sql = "SELECT DISTINCT o.`id` as `ID`, o.`type` as `Type`, o.`name` as `Name`, o.`format` as `Format`, o.`publication` as `PublicationId`, o.`issue` as `IssueId` "
				. "FROM smart_users u, smart_usrgrp ug, smart_objects o "
				. "INNER JOIN " . $tempaov . " aov ON (aov.`id` = o.`id`) "
				. "INNER JOIN `smart_publobjects` pobj ON (pobj.`objectid` = o.`id`) "
				. "WHERE o.`type` = 'DossierTemplate' "
				. "AND u.`user` = '" . $shortusername . "' AND u.`id` = ug.`usrid` AND (ug.`grpid` = pobj.`grpid` OR pobj.`grpid` = 0)";
		}

		$sth  = $dbdriver->query($sql);
		$dbRows = DBBase::fetchResults($sth);
        
		//Drop the created views (essential to not get a lot of views in the database!)
		DBQuery::dropRegisteredViews();

		// Create array with column definitions
		$cols = $this->getColumns();

		// Transform db rows to names query response rows
		$rows = array();
		foreach($dbRows as $dbRow)
		{
			$rows[] = array($dbRow['ID'], $dbRow['Type'], $dbRow['Name'], $dbRow['Format'], $dbRow['PublicationId'], $dbRow['IssueId']);
		}
		
		
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, 1, count($rows), count($rows), null );
	}
	
	/**
	 * @inheritdoc
	 * @throws BizException when called since this function is not supported.
	 */
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		// not supported
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', "ContentSource doesn't implement getAlienObject" );
	}
	
	/**
	 * createShadowObject
	 * 
	 * Create object record in Enterprise with thumb and preview, native to stay in file-system.
	 * For simplicity of this example, we assume that we only deal with jpg images
	 *
	 * @param string $alienID Alien object id, so include the _<ContentSourceID>_ prefix
	 * @param Object $destObject
	 * @return Object
	 * @throws BizException when called since this function is not supported.
	 */
	final public function createShadowObject( $alienID, $destObject )
	{
		// not supported
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', "ContentSource doesn't implement createShadowObject" );
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - 
	// Below private implementation methods:
	// - - - - - - - - - - - - - - - - - - - - - - 
	
	private function getColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 			'ID', 				'string' ); // Required as 1st
		$cols[] = new Property( 'Type', 		'Type', 			'string' ); // Required as 2nd
		$cols[] = new Property( 'Name', 		'Name', 			'string' ); // Required as 3rd
		$cols[] = new Property( 'Format', 		'Format', 			'string' );
		$cols[] = new Property( 'PublicationId', 'PublicationId', 	'string' ); // Required by Content Station
		$cols[] = new Property( 'IssueId',       'IssueId', 	    'string' ); // Required by Content Station OverruleCompatibility plug-in
		return $cols;
	}
}
