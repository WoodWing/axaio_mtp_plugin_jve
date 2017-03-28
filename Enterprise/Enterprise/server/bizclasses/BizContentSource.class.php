<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class that acts as facade to third party content sources implemented by server plug-ins.
 * 
 */

class BizContentSource
{
	/**
	 * Checks if specified object is an alien object.
	 *
	 * In case a shadow object id is passed, false is returned.
	 *
	 * This function does the same as {@link: filterAlienIdsFromObjectIds()} but instead of checking for multiple
	 * objects, it checks for only one object. If the caller needs to find out a list of objects whether there are
	 * any alien objects in the list, it's better to use filterAlienIdsFromObjectIds() instead of calling isAlienObject()
	 * many times.
	 *
	 * @param string $id Object id, alien or Enterprise (including shadow object)
	 * @return bool
	 */
	public static function isAlienObject( $id )
	{
		return !empty($id) && $id[0] == '_';
	}

	/**
	 * Tells if the specified object is a shadow object. 
	 *
	 * @param Object $object
	 * @return bool
	 */
	public static function isShadowObject( $object )
	{
		$bmd = $object->MetaData->BasicMetaData;
		return self::isShadowObjectBasedOnProps( $bmd->ContentSource, $bmd->DocumentID );
	}

	/**
	 * Tells if the specified object properties indicate that the object is a shadow object. 
	 *
	 * @since 9.4
	 * @param string $contentSource
	 * @param string $documentId
	 * @return bool
	 */
	public static function isShadowObjectBasedOnProps( $contentSource, $documentId )
	{
		return !empty($contentSource) && !empty($documentId);
	}

	/**
	 * Pass in any object id. If it's a native Enterprise object the same id is returned.
	 * In case of an alien its shadow id is returned or, in case there is no shadow yet,
	 * a shadow object will be created.
	 * If creation of a shadow object fails, a BizException will be thrown
	 *
	 * @param string $id Object id which can be alien or Enterprise object (Including shadow object).
	 * @param Object|null $destObject
	 * @throws BizException Throws BizException when the operation fails.
	 * @return string Object id.
	 */
	public static function ifAlienGetOrCreateShadowObject( $id, $destObject=null )
	{
		if( !BizContentSource::isAlienObject( $id ) ) return $id;

		// Check if we already have a shadow object for this alien. If so, return the id.
		$shadowId = self::getShadowObjectID($id);
		if( $shadowId ) {
			return $shadowId;
		} else {
			LogHandler::Log('bizcontentsource','DEBUG','No shadow found for alien object '.$id);

			require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			$shadowObject = BizContentSource::createShadowObject( $id, $destObject );

            // Check if all users in the metadata are known within the system. If not, create them and import remaining
            // information when such a user logs in through LDAP when enabled.
            BizObject::getOrCreateResolvedUsers($id, $shadowObject->MetaData);

			$shadowObject = BizObject::createObject( $shadowObject, BizSession::getShortUserName(), false /*lock*/, empty($shadowObject->MetaData->BasicMetaData->Name) /*$autonaming*/ );
			// Change alien id into new shadow id
			return $shadowObject->MetaData->BasicMetaData->ID;
		}
	}
	
	/**
	 * Returns shadow object id for the alien object. When the object is not an alien or when
	 * it doesn't have a shadow, null is returned.
	 *
	 * @param string $id
	 * @return string
	 */
	public static function getShadowObjectID( $id ) 
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$contentSource ='';
		$documentId = self::getContentSourceAndDocumentId( $id, $contentSource );

		$shadowId = DBObject::getObjectForAlien( $contentSource, $documentId );

		if( $shadowId ) {
			LogHandler::Log('bizcontentsource','DEBUG','Shadow '.$shadowId.' found for alien object '.$id);
			return $shadowId;
		}
		return null;
	}

	/**
	 * Retrieves shadow object ids from the given list of alien ids.
	 *
	 * Function goes through the list of alien ids and for all the alien id
	 * that has its shadow object, function returns the alien id and its shadow id.
	 *
	 * @param string[] $alienIds List of alien id to retrieve its shadow ids if there's any.
	 * @return array List of key-value pair where key is the alien id and value its shadow id.
	 */
	public static function getShadowObjectIds( $alienIds )
	{
		$alienBag = array();
		$documentIds = array(); // External Id of the alien.
		if( $alienIds ) foreach( $alienIds as $alienId ) {
			$contentSource ='';
			$documentId = self::getContentSourceAndDocumentId( $alienId, $contentSource );
			$alienBag[$contentSource][$documentId] = $alienId;
			$documentIds[$contentSource][] = $documentId;
		}

		$shadowIds = array();
		if( $documentIds && $alienBag ){
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$rows = DBObject::getObjectsForAliens( $documentIds );

			if( $rows ) foreach( $rows as $row ) {
				$alienId = $alienBag[$row['contentsource']][$row['documentid']];
				$shadowIds[$alienId] = $row['id']; // alienId - shadowId
			}

		}
		return $shadowIds;
	}

	/**
	 * Converts a given alien ID into the ContentSource and DocumentID.
	 *
	 * @param string $alienId
	 * @param string $contentSource (return value!)
	 * @return string DocumentID
	 */
	private static function getContentSourceAndDocumentId( $alienId, &$contentSource )
	{
		// Search for second _, to know the end of the prefix
		$endPrefix = strpos( $alienId, '_', 1 );
		$contentSource = substr( $alienId, 1, $endPrefix-1 );
		return substr( $alienId, $endPrefix+1 );
	}

	// ===================================================================
	// Below facade functions for the ContentSource Connector methods
	// these facades take care of getting the right plug-in etc.
	// ===================================================================
		
	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in(s)
	 *
	 * @return array
	 */
	public static function getQueries( ) 
	{
		$namedQueries = array();
		
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connRetVals = array();
		BizServerPlugin::runDefaultConnectors( 'ContentSource', null, 'getQueries', array(), $connRetVals );
		foreach( $connRetVals as $connRetVal ) {
			$namedQueries = array_merge( $namedQueries, $connRetVal );
		}
		return $namedQueries;
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $query
	 * @param array $params List of QueryParam objects
	 * @param int $firstEntry
	 * @param int $maxEntries
	 * @param array $order List of QueryOrder objects
	 * @throws BizException
	 * @return mixed Results from the connector's function call.
	 */
	public static function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// Search connector that implements specified query and execute it:
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connector = BizServerPlugin::searchConnector( 'ContentSource', null, 'implementsQuery', array($query) );
		if( $connector ) {
			return BizServerPlugin::runConnector( $connector, 'doNamedQuery', array($query, $params, $firstEntry, $maxEntries, $order));
		}
		
		// if we arrive here, we didn't find the requested query, so raise an exception
		throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Named query not found: ' . $query);
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $alienId
	 * @param string $rendition
	 * @param bool $lock
	 * @return mixed Results from the connector's function call.
	 */
	public static function getAlienObject( $alienId, $rendition, $lock ) 
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Get content source connector for this alien object		
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'getAlienObject', array($alienId, $rendition, $lock) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $alienId
	 * @param string $rendition
	 * @return mixed Results from the connector's function call.
	 */
	public static function listAlienObjectVersions( $alienId, $rendition )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Get content source connector for this alien object		
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'listAlienObjectVersions', array($alienId, $rendition) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $alienId
	 * @param string $version
	 * @param string $rendition
	 * @return mixed Results from the connector's function call.
	 */
	public static function getAlienObjectVersion( $alienId, $version, $rendition )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		// Get content source connector for this alien object		
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'getAlienObjectVersion', array($alienId, $version, $rendition) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $alienId
	 * @param string $version
	 * @return mixed Results from the connector's function call.
	 */
	public static function restoreAlienObjectVersion( $alienId, $version )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Get content source connector for this alien object		
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'restoreAlienObjectVersion', array($alienId, $version) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $alienId
	 * @param Object $destObject
	 * @return Object
	 */
	public static function createShadowObject( $alienId, $destObject )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		// Get content source connector for this alien object		
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'createShadowObject', array($alienId, $destObject) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string  $contentSource
	 * @param string  $documentId
	 * @param Object  $object
	 * @param array   $objProps
	 * @param boolean $lock
	 * @param string 	$rendition
	 * @param array   $requestInfo
	 * @param array   $supportedContentSources
	 * @param string  $haveVersion
	 */
	public static function getShadowObject( $contentSource, $documentId, &$object, $objProps, 
		$lock, $rendition, $requestInfo = null, $supportedContentSources = null, $haveVersion = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Get content source connector for this alien object
		$alienId = self::getAlienId( $contentSource, $documentId );
		$connector = self::getContentSourceForAlienObject( $alienId );

        // When ContentSourceFileLinks are requested, they need to be enabled for the Content Source plugin.
        if( in_array( 'ContentSourceFileLinks', $requestInfo ) 
			&& is_array( $supportedContentSources ) && in_array( $contentSource, $supportedContentSources ) ) {
            require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
            BizServerPlugin::runConnector( $connector, 'requestedContentSourceFileLinks', array() );
        }

		// EN-86558 Use the new getShadowObject api call that supports the haveVersion parameter
		BizServerPlugin::runConnector( $connector, 'getShadowObject2', 
			array( $alienId, &$object, $objProps, $lock, $rendition, $haveVersion ) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $alienId
	 * @return mixed Results from the connector's function call.
	 */
	public static function deleteAlienObject( $alienId )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		// Get content source connector for this alien object		
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'deleteAlienObject', array($alienId) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $contentSource
	 * @param string $documentId
	 * @param Object $object
	 * @return mixed Results from the connector's function call.
	 */
	public static function saveShadowObject( $contentSource, $documentId, &$object )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Get content source connector for this alien object
		$alienId = self::getAlienId( $contentSource, $documentId );
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'saveShadowObject', array($alienId, &$object) );
	}
	
	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @since v8.2.0
	 * @param string $contentSource
	 * @param string $documentId
	 * @param Object $object
	 * @return mixed Results from the connector's function call.
	 */
	public static function setShadowObjectProperties( $contentSource, $documentId, &$object )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Get content source connector for this alien object
		$alienId = self::getAlienId( $contentSource, $documentId );
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'setShadowObjectProperties', array($alienId, &$object) );
	}

	/**
	 * Set multiple shadow objects' properties.
	 *
	 * Refer to ContentSource_EnterpriseConnector::multiSetShadowObjectProperties() for more details.
	 *
	 * @param array[] $shadowObjectIds List of array where key is the content source and value its list of shadow ids.
	 * @param MetaDataValues[] $metaDataValues The modified value that needs to be updated at the content source side.
	 */
	public static function multiSetShadowObjectProperties( $shadowObjectIds, $metaDataValues )
	{
		$connectorsPerCS = array(); // CS = ContentSource
		$shadowIdsPerCS = array(); // CS = ContentSource
		if( $shadowObjectIds ) {
			foreach( $shadowObjectIds as $contentSource => $shadowIds ) {
				$connector = self::getContentSourceConnectorForContentSource( $contentSource );
				$connectorsPerCS[$contentSource] = $connector;
				$shadowIdsPerCS[$contentSource][] = $shadowIds;
			}
		}

		require_once BASEDIR . '/server/utils/PHPClass.class.php';
		if( $connectorsPerCS ) foreach( $connectorsPerCS as $contentSource => $connector ) {
			$shadowIds = $shadowIdsPerCS[$contentSource];
			if( self::doesContentSourceSupportsMultiSet( $contentSource ) ) {
				BizServerPlugin::runConnector( $connector, 'multiSetShadowObjectProperties', array( $shadowIds, $metaDataValues ) );
			} else {
				LogHandler::Log('BizContentSource','ERROR','Function "multiSetShadowObjectProperties" is not implemented by ' .
					'content source "'.$contentSource.'". No action taken.');
			}
		}
	}

	/**
	 * Function checks if the connector supports the multi-set properties function named multiSetShadowObjectProperties.
	 *
	 * @param string $contentSource Content Source unique name.
	 * @return bool
	 */
	public static function doesContentSourceSupportsMultiSet( $contentSource )
	{
		require_once BASEDIR . '/server/utils/PHPClass.class.php';
		$connector = self::getContentSourceConnectorForContentSource( $contentSource );
		return WW_Utils_PHPClass::methodExistsInDeclaringClass( get_class( $connector ), 'multiSetShadowObjectProperties' );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $contentSource
	 * @param string $documentId
	 * @param string $shadowId
	 * @param boolean $permanent
	 * @param boolean $restore
	 * @return mixed Results from the connector's function call.
	 */
	public static function deleteShadowObject( $contentSource, $documentId, $shadowId, $permanent, $restore )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		// Get content source connector for this alien object		
		$alienId = self::getAlienId( $contentSource, $documentId );
		$connector = self::getContentSourceForAlienObject( $alienId ); 
		return BizServerPlugin::runConnector( $connector, 'deleteShadowObject', array($alienId, $shadowId, $permanent,$restore) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $contentSource
	 * @param string $documentId
	 * @param string $shadowId
	 * @param string $rendition
	 * @return VersionInfo[]
	 */
	public static function listShadowObjectVersions( $contentSource, $documentId, $shadowId, $rendition )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		// Get content source connector for this alien object		
		$alienId = self::getAlienId( $contentSource, $documentId );
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'listShadowObjectVersions', array($alienId, $shadowId, $rendition) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $contentSource
	 * @param string $documentId
	 * @param string $shadowId
	 * @param string $version
	 * @param string $rendition
	 * @return VersionInfo
	 */
	public static function getShadowObjectVersion( $contentSource, $documentId, $shadowId, $version, $rendition )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Get content source connector for this alien object		
		$alienId = self::getAlienId( $contentSource, $documentId );
		$connector = self::getContentSourceForAlienObject( $alienId ); 
		return BizServerPlugin::runConnector( $connector, 'getShadowObjectVersion', array($alienId, $shadowId, $version, $rendition) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string $contentSource
	 * @param string $documentId
	 * @param string $shadowId
	 * @param string $version
	 * @return boolean|null TRUE when handled or NULL if Enterprise should handle this
	 */
	public static function restoreShadowObjectVersion( $contentSource, $documentId, $shadowId, $version )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		// Get content source connector for this alien object		
		$alienId = self::getAlienId( $contentSource, $documentId );
		$connector = self::getContentSourceForAlienObject( $alienId );
		return BizServerPlugin::runConnector( $connector, 'restoreShadowObjectVersion', array($alienId, $shadowId, $version) );
	}

	/**
	 * See ContentSource_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in
	 *
	 * @param string	$contentSource
	 * @param string	$documentId
	 * @param Object 	$srcObject
	 * @param Object 	$destObject
	 * @return Object
	 */
	public static function copyShadowObject( $contentSource, $documentId, $srcObject, $destObject )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		// Get content source connector for this alien object		
		$alienId = self::getAlienId( $contentSource, $documentId );
		$connector = self::getContentSourceForAlienObject( $alienId );
		$shadowObject =  BizServerPlugin::runConnector( $connector, 'copyShadowObject', array($alienId, $srcObject, $destObject) );
				
		return $shadowObject;
	}

	/**
	 * Gets content source for specified alien object
	 *
	 * @param string 	$alienId	alien object id
	 * @return ContentSource_EnterpriseConnector that can be passed to BizServerPlugin
	 * @throws BizException when no content source found for alien
	 */
	private static function getContentSourceForAlienObject( $alienId )
	{
		// Get content source id out of alien id
		$contentSource = '';
		self::getContentSourceAndDocumentId( $alienId, $contentSource );
		return self::getContentSourceConnectorForContentSource( $contentSource );
	}

	/**
	 * Returns the connector used by the given Content Source.
	 *
	 * @param string $contentSource Content Source name.
	 * @throws BizException Throws error when no connector found for the Content Source requested.
	 * @return EnterpriseConnector
	 */
	private static function getContentSourceConnectorForContentSource( $contentSource )
	{
		// Now find the corresponding content source:
		// Results is cached, because for multi-object gets this is called multiple times and this turns to be expensive
		static $registry = array();
		if( array_key_exists( 'ContentSource'.$contentSource, $registry ) ) {
			$connector = $registry['ContentSource'.$contentSource];
		} else {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connector = BizServerPlugin::searchConnector( 'ContentSource', null, 'isContentSourceId', array($contentSource) );
			$registry['ContentSource'.$contentSource] = $connector;
		}
		if( $connector ) {
			return $connector;
		}
		
		// if we arrive here, we didn't find the requested query, so raise an exception
		throw new BizException( 'ERR_NO_CONTENTSOURCE', 'Client', $contentSource );

		// Note: This could occur for example when a Content Source was previously installed which has introduced 
		// shadow objects at the Enterprise database. When unplugging that Content Source and getting (previewing/placing/opening) 
		// such objects, this message could raise.
	}
	
	/**
	 * Composes an alien object id based on given ContentSource and DocumentID.
	 *
	 * @param string $contentSource Unique ID of content source connector
	 * @param string $documentId Id for the foreign object in the content source
	 * @return string Alien object id
	 */
	private static function getAlienId( $contentSource, $documentId )
	{
		return '_' . $contentSource . '_' . $documentId;
	}

	/**
	 * Given the object, function returns the object's alien id.
	 *
	 * @param Object $object
	 * @return string|null Alien object id, null when the object has no ContentSource and DocumentID set.
	 */
	public static function getAlienIdFromObject( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		$contentSource = $object->MetaData->BasicMetaData->ContentSource;
		$documentId = $object->MetaData->BasicMetaData->DocumentID;

		$alienId = null;
		if( $contentSource && $documentId ) {
			$alienId = self::getAlienId( $contentSource, $documentId );
		}
		return $alienId;
	}

	/**
	 * Filters out all the alien ids found in the list of given object ids.
	 *
	 * This function does the same as {@link: isAlienObject()} but instead of just checking for one
	 * object, it checks for multiple objects in one go. If the caller needs to find out a list of
	 * objects whether there are any alien objects in the list, it's better to use filterAlienIdsFromObjectIds()
	 * as it will be faster than isAlienObject().
	 *
	 * @param string[] $objectIds List of object ids, alien or Enterprise (including shadow objects).
	 * @return string[] List of alien ids found in the given object ids, empty array when no alien id found.
	 */
	public static function filterAlienIdsFromObjectIds( $objectIds )
	{
		// Because alien object ids have a '_' prefix, casting them to an integer results into zero(0).
		$castedObjectIds = array_map( 'intval', $objectIds );

		// The difference between the original objectIds and the casted objectIds are the alien ids that became zero(0).
		$alienIds = array_diff_assoc( $objectIds, $castedObjectIds );

		// To return a clean array of alien ids, renumber the keys [0...n-1]
		$alienIds = array_values( $alienIds );

		return $alienIds;
	}

	/**
	 * During a save opertion, the DocumentID could be read from embedded XMP data
	 * by the server plug-in "PHP Preview and Meta Data". This represents Adobe's document id
	 * but the DocumentID is already preserved (used) by the Content Source integration.
	 * By doing nothing, Adobe's document id would overwrite the link to the external content
	 * source store. For example when an InDesign user does Edit Original of a placed PS 
	 * image that originates from Elvis. To avoid this from happening, this function checks 
	 * the DB to see if the originally stored object is controlled by a content source. 
	 * In that case, the arrived metadata of the SaveObjects request (as given to this function) 
	 * needs to be repaired with the DocumentID and ContentSource as stored in DB.
	 *
	 * @param MetaData $metaData Arrived with SaveObjects request, about to get stored in DB, to be protected.
	 * @param string $contentSource ContentSource property value as currently stored in DB.
	 * @param string $documentId DocumentID property value as currently stored in DB.
	 */
	public static function protectShadowFromBreakingLink( &$metaData, $contentSource, $documentId )
	{
		if( !empty($contentSource) ) {
			$metaData->BasicMetaData->ContentSource = $contentSource;
			$metaData->BasicMetaData->DocumentID = $documentId;
		}
	}
	
	/**
	 * Requests the Content Source connector whether or not the given user has certain 
	 * access ($right) to the given alien object. See also checkAccessForAlien() function
	 * in server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php.
	 *
	 * @since 9.4
	 * @param string $user Short user name.
	 * @param string $right Access right to be checked. See BizAccessFeatureProfiles.class.php for possible flags.
	 * @param integer $brandId
	 * @param integer $overruleIssueId Id of issue that overrules the brand. Zero when none- or normal issue(s) assigned.
	 * @param integer $categoryId
	 * @param string $objectType
	 * @param integer $statusId Valid status id, or -1 for Personal Status.
	 * @param string $alienId
	 * @param string $contentSource
	 * @param string $documentId
	 * @return boolean|null NULL to let core server decide (default). TRUE when allowed. FALSE when not allowed (experimental).
	 */
	public static function checkAccessForAlien( 
		$user, $right, $brandId, $overruleIssueId, $categoryId, $objectType, $statusId,
		$alienId, $contentSource, $documentId )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connector = self::getContentSourceConnectorForContentSource( $contentSource );
		$params = array( $user, $right, 
					 	$brandId, $overruleIssueId, $categoryId, $objectType, $statusId,
					 	$alienId, $contentSource, $documentId );
		return BizServerPlugin::runConnector( $connector, 'checkAccessForAlien', $params );
	}

	/**
	 * Requests the Content Source connector whether or not the given user has certain 
	 * access ($right) to the given shadow object. See also checkAccessForShadow() function
	 * in server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php.
	 *
	 * @since 9.4
	 * @param string $user Short user name.
	 * @param string $right Access right to be checked. See BizAccessFeatureProfiles.class.php for possible flags.
	 * @param integer $brandId
	 * @param integer $overruleIssueId Id of issue that overrules the brand. Zero when none- or normal issue(s) assigned.
	 * @param integer $categoryId
	 * @param string $objectType
	 * @param integer $statusId Valid status id, or -1 for Personal Status.
	 * @param string $shadowId
	 * @param string $contentSource
	 * @param string $documentId
	 * @return boolean|null NULL to let core server decide (default). TRUE when allowed. FALSE when not allowed (experimental).
	 */
	 public static function checkAccessForShadow( 
		$user, $right, $brandId, $overruleIssueId, $categoryId, $objectType, $statusId,
		$shadowId, $contentSource, $documentId )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connector = self::getContentSourceConnectorForContentSource( $contentSource );
		$params = array( $user, $right, 
					 	$brandId, $overruleIssueId, $categoryId, $objectType, $statusId,
					 	$shadowId, $contentSource, $documentId );
		return BizServerPlugin::runConnector( $connector, 'checkAccessForShadow', $params );
	}

    /**
     * Ask the Content Source connector whether or not the connector can provide more information about the given user
     * abstracted from MetaData of the alien- or shadow object.
     *
     * When a content source creates an alien- or a shadow object it is possible that the MetaData contains user names
     * that are not known in Enterprise Server (yet). e.g: The fields Modifier, Creator, Deletor, RouteTo and LockedBy
     * can have such user names as values.
     *
     * This method allows the connector to enrich user information before the user gets created. e.g.:
     * The AdmUser->FullName is used to show the name of the user in the UI. While the MetaData contains the short
     * username, which not always describes the user properly.
     *
     * See also completeUser() function in server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php
     *
     * @param integer $alienId Id of the alien object to resolve the content source connector
     * @param AdmUser $user
     * @return AdmUser Return the user object with enriched user information
     */
    public static function completeUser( $alienId, AdmUser $user )
    {
        require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

        // Get content source connector for this alien object
        $connector = self::getContentSourceForAlienObject( $alienId );
        return BizServerPlugin::runConnector( $connector, 'completeUser', array($user) );
    }
}
