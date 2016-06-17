<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.

 MultipleInbox, contentSource to be able to search and read articles from SCE Inbox.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once dirname(__FILE__).'/MultipleInbox.class.php';
require_once dirname(__FILE__).'/PluginInfo.php';
require_once dirname(__FILE__).'/Config.php';

class MultipleInbox_ContentSource extends ContentSource_EnterpriseConnector
{
	public function getContentSourceId( )
	{
		return MultipleInbox_CONTENTSOURCEID;
	}


	/**
	 * getQueries
	 *
	 * Returns available queries for the content source. These will be shown as named queries.
	 * It's Ok to return an empty array, which means the content source is not visible in the
	 * Enterprise (content) query user-interface.
	 *
	 * @return array of NamedQuery
	 */
	// Return empty query user-interface
	public function getQueries()
	{
		$queries[] = new NamedQueryType( 'MultipleInbox', array() );
		return $queries;
	}

	/**
	 * doNamedQuery
	 *
	 * Execute query on content source.
	 *
	 * @param string 				$query		Query name as obtained from getQueries
	 * @param array of Property 	$params		Query parameters as filled in by user
	 * @param unsigned int			$firstEntry	Index of first requested object of total count (TotalEntries)
	 * @param unsigned int			$maxEntries Max count of requested objects (zero for all, nil for default)
	 * @param array of QueryOrder	$order
	 *
	 * @return WflNamedQueryResponse
	 */
	public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		$query=$query; $firstEntry=$firstEntry; $maxEntries=$maxEntries; $order=$order;

		$namedQueryParam = MultipleInbox::QueryObjects( $params );
		$cols = $this->getColumns();

		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';

		$namedQueryResp = new WflNamedQueryResponse( $cols, $namedQueryParam, null, null, null, null, 0, 0, 0, null );
		return $namedQueryResp;
	}

	public function isInstalled()
	{
		return true;
	}

	public function runInstallation()
	{
		if( !$this->isInstalled() ) {
			throw new BizException( '', 'Client', 'Unable to connect to the MultipleInbox-server', 'Installation failure.' );
		}
	}

	// -------------------------
	// - getAlienObject-
	// -------------------------
	public function getAlienObject( $alienID, $rendition, $lock )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );
	
	
		list($trueid,$serversetting) = explode('##',$alienID );
		list($serverurl,$serverversion) = explode('#@',$serversetting );
		$trueid=substr($trueid,strlen(MultipleInbox_CONTENTSOURCEPREFIX)+3);

		$arrobjects = MultipleInbox::Getobjects( $alienID,$trueid, $serverurl, $serverversion, $rendition, $lock );
		return $arrobjects;
	}

	public function deleteAlienObject( $alienId )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );

		$alienId=$alienId;
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', "ContentSource doesn't implement deleteAlienObject" );
	}

	public function listAlienObjectVersions( $alienId, $rendition )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );
		// No versioning on file system
		// return an empty array, which will show empty version dialog.
		return array();
	}

	public function getAlienObjectVersion( $alienId, $version, $rendition )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );
		$alienId=$alienId; $version=$version; $rendition=$rendition;
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', "ContentSource doesn't implement getAlienObjectVersion" );
	}

	public function restoreAlienObjectVersion( $alienId, $version )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );
		$alienId=$alienId; $version=$version;
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', "ContentSource doesn't implement restoreAlienObjectVersion" );
	}

	/**
	 * createShadowObject
	 *
	 * Create object record in Enterprise with thumb and preview, native to stay in file-system.
	 * For simplicity of this example, we assume that we only deal with jpg images
	 *
	 * @param string	$id 		Alien object id, so include the _<ContentSourceID>_ prefix
	 *
	 * @return Object
	 */
	public function createShadowObject( $alienID, $destObject )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );
		list($trueid,$serversetting) = explode('##',$alienID );
		list($serverurl,$serverversion) = explode('#@',$serversetting );
		$trueid=substr($trueid,strlen(MultipleInbox_CONTENTSOURCEPREFIX)+3);

		$arrobjects = MultipleInbox::Getobjects( $alienID,$trueid, $serverurl, $serverversion, $rendition, $lock );
		return $arrobjects;
	}

	/**
	 * getShadowObject
	 *
	 * Get shadow object. Meta data is all set already, access rights have been set etc.
	 * All that is required is filling in the files for the requested object.
	 * Furthermore the meta data can be adjusted if needed.
	 * If Files is null, Enterprise will fill in the files
	 *
	 * Default implementation does nothing, leaving it all up to Enterpruse
	 *
	 * @param string	$alienId 	Alien object id
	 * @param string	$object 	Shadow object from Enterprise
	 * @param array		$objprops 	Array of all properties, both the public (also in Object) as well as internals
	 * @param boolean	$lock		Whether object should be locked
	 * @param string	$rendition	Rendition to get
	 *
	 * @return Object
	 */
	 
/*	 
	public function getShadowObject( $alienID, &$object, $objprops, $lock, $rendition )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );
		list($trueid,$serversetting) = explode('##',$alienID );
		list($serverurl,$serverversion) = explode('#@',$serversetting );
		$trueid=substr($trueid,strlen(MultipleInbox_CONTENTSOURCEPREFIX)+3);

		$arrobjects = MultipleInbox::Getobjects( $alienID,$trueid, $serverurl, $serverversion, $rendition, $lock );

		unset($object->Files);
		$object->Files=$arrobjects->Files;
		return $object;
	}
*/


	/**
	 * saveShadowObject
	 *
	 * Saves shadow object. This is called after update of DB records is done in Enterprise, but
	 * before any files are stored. This allows content source to save the files externally in
	 * which case Files can be cleared. If Files not cleared, Enterprise will save the files
	 *
	 * Default implementation does nothing, leaving it all up to Enterpruse
	 *
	 * @param string	$alienId		Alien id of shadow object
	 * @param string	$object
	 *
	 * @return Object
	 */
	 
	 
	public function saveShadowObject( $alienId, &$object )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ . '#' . __LINE__ );

		list($trueid,$serversetting) = explode('##',$alienId );
		list($serverurl,$serverversion) = explode('#@',$serversetting );
		$trueid=substr($trueid,strlen(MultipleInbox_CONTENTSOURCEPREFIX)+3);

		$myobject = clone $object;
		MultipleInbox::Saveobjects( $alienId,$trueid, $serverurl, $serverversion, $myobject );
	}


/*
 	public function deleteShadowObject( $alienId, $shadowId, $permanent, $restore )
	{
		$alienId=$alienId; $shadowId=$shadowId; $permanent=$permanent; $restore=$restore;
		LogHandler::Log('MultipleInbox', 'DEBUG', 'ContentSource::deleteShadowObject called for '.$shadowId );
	}

	public function listShadowObjectVersions( $alienId, $shadowId, $rendition )
	{
		$alienId=$alienId; $shadowId=$shadowId; $rendition=$rendition;
		LogHandler::Log('MultipleInbox', 'DEBUG', 'ContentSource::listShadowObjectVersions called for '.$shadowId );
		return null;
	}

	public function getShadowObjectVersion( $alienId, $shadowId, $version, $rendition )
	{
		$alienId=$alienId; $shadowId=$shadowId; $version=$version; $rendition=$rendition;
		LogHandler::Log('MultipleInbox', 'DEBUG', 'ContentSource::getShadowObjectVersion called for '.$shadowId );
		return null;
	}

	public function restoreShadowObjectVersion( $alienId, $shadowId, $version )
	{
		$alienId=$alienId; $shadowId=$shadowId; $version=$version;
		LogHandler::Log('MultipleInbox', 'DEBUG', 'ContentSource::restoreShadowObjectVersion called for '.$shadowId );
		return null;
	}

	public function copyShadowObject( $alienId, $srcObject, $destObject )
	{
		$srcObject = $srcObject;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::copyShadowObject called for '.$alienId );
		$shadowObject = $this->createShadowObject($alienId, $destObject);
		return $shadowObject;
	}
*/

	// - - - - - - - - - - - - - - - - - - - - - -
	// Below private implementation methods:
	// - - - - - - - - - - - - - - - - - - - - - -
	public function getColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 		'ID', 		'string'	); // Required as 1st
		$cols[] = new Property( 'Type', 	'Type', 	'string' 	); // Required as 2nd
		$cols[] = new Property( 'Name', 	'Name', 	'string' 	); // Required as 3rd
		$cols[] = new Property( 'State', 	'Status', 	'list' 		);
		$cols[] = new Property( 'LockedBy', 	'In Use By', 	'string' 	);
		$cols[] = new Property( 'PlacedOn', 'PlacedOn', 'string'	);
		$cols[] = new Property( 'FileSize', 	'Size', 	'int'	);
		$cols[] = new Property( 'Modifier', 	'Modified By', 	'string'	);
		$cols[] = new Property( 'Modified', 	'Modified On', 	'datetime'	);
		$cols[] = new Property( 'Publication', 'Brand', 'list'	);
		$cols[] = new Property( 'Section', 'Category', 'list'	);
		$cols[] = new Property( 'Comment', 'Comment', 'multiline'	);
		$cols[] = new Property( 'RouteTo', 'Route To', 'list'	);
		$cols[] = new Property( 'Creator', 'Created By', 'string'	);
		$cols[] = new Property( 'Format', 'Format', 'string'	);
		$cols[] = new Property( 'PublicationId', 'Brand ID', 'string'	);
		$cols[] = new Property( 'SectionId', 	'Category ID', 	'string'	);
		$cols[] = new Property( 'StateId', 	'Status ID', 	'string' 	);
		$cols[] = new Property( 'StateColor', 	'Color', 	'string'	);
		$cols[] = new Property( 'LockForOffline', 	'LockForOffline', 	'bool'	);
		$cols[] = new Property( 'IssueId', 	'Issue ID', 	'string' 	);
		return $cols;
	}

	public function getRows($queryResponse,$serverurl)
	{
		$rows = array();
		foreach($queryResponse as $key => $value){
			$each_row = array();
			//array here is modified in plugins of SCE Inbox

			$each_row[] = MultipleInbox_CONTENTSOURCEPREFIX .'!@!'. $value[0] . "##" . $serverurl; // ID
			$each_row[] = $value[1]; //Type
			$each_row[] = $value[2]; //Name
			$each_row[] = $value[3]; //status
			$each_row[] = $value[4]; //In Used By
			$each_row[] = $value[5]; //PlacedOn
			$each_row[] = $value[6]; //Size
			$each_row[] = $value[7]; //Modified By
			$each_row[] = $value[8]; //Modified On
			$each_row[] = $value[9]; //Brand
			$each_row[] = $value[10]; //Category
			$each_row[] = $value[11]; //Comment
			$each_row[] = $value[12]; //Route To
			$each_row[] = $value[13]; //Created By
			$each_row[] = $value[14]; //Format
			$each_row[] = $value[15]; //Brand ID
			$each_row[] = $value[16]; //Category ID
			$each_row[] = $value[17]; //Status ID
			$each_row[] = $value[18]; //Color
			$each_row[] = $value[19]; //LockForOffline
			$each_row[] = $value[20]; //Issue ID
			$rows[] = $each_row;
		}
		return $rows;
	}

}


