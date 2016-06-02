<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class with static functions to integrated external content sources like an image database.
 * 
 * Enterprise will use it's standard named query mechanism to query the external content source,
 * which will return so-called 'alien objects' which don't have a record inside the Enterprise
 * database. As soon as the object is going to be used inside Enterprise (getting the native or 
 * creating a relation) the alien object gets a record inside the Enterprise database which we 
 * call a shadow object. 
 * 
 * It's up to the content source implementation if any renditions (like thumb/preview) for a 
 * shadow object are stored inside Enterprise. If any rendition is stored in Enterprise it's
 * responsibility of the content source implementation to keep these up to date, for example by 
 * checking for updates any time an object is retrieved.
 * 
 * An ALIEN objects has an object id that must start with '_<content source id>_', for example
 * '_MyCS_1234' with 1234 being the foreign id. A SHADOW object has an Enterprise ID, its 
 * DocumentID contains the foreign id and it's ContentSource field contains the content source 
 * identifier. Let's say this alien object is about to become a shadow object getting Enterprise 
 * object id 56789 assigned. To show the example values for terminology used, it looks like this:
 * - content source id = MyCS
 * - content source prefix = _MyCS_
 * - external id / foreign id = 1234
 * - enterprise id / object id = alien id or shadow id
 * - alien id = _MyCS_1234
 * - shadow id = 56789
 * In terms of metadata, the ALIEN object has the following values:
 * - Object->MetaData->BasicMetaData->ID = _MyCS_1234
 * - Object->MetaData->BasicMetaData->ContentSource = MyCS
 * - Object->MetaData->BasicMetaData->DocumentID = 1234
 * and when it becomes a SHADOW object, it has these values:
 * - Object->MetaData->BasicMetaData->ID = 56789
 * - Object->MetaData->BasicMetaData->ContentSource = MyCS
 * - Object->MetaData->BasicMetaData->DocumentID = 1234
 *  
 * The total length of this id may not exceed 63 characters and it may not contain any 
 * symbols that are invalid for folder names on Win or Mac. The name of the alien object 
 * may not be longer than 27 characters.
 *
 * In case of any error a BizException should be thrown.
 *
 * => Possible other connectors a content source wants/needs to implement:
 * If a content source wants to be notified when targets are set for a shadow object, when properties
 * are set for a shadow or when a relation with a shadow object is created, the content source plug-in
 * can implement the specific workflow connectors for this.
 * 
 * When the content source needs to influence the Property dialog, the GetDialog service
 * connector can be implemented.
 *
 * @since v9.2.0, the so called "multi set object properties" feature is introduced.
 * Instead of setting properties of an object one at a time, now it can be done for multiple objects in one go.
 * For third party integrators that has implemented setShadowObjectProperties() should implement the following function:
 * - multiSetShadowObjectProperties
 *
 * In the Server Plugin HealthCheck, plugins that use Content Source connector will be checked if
 * setShadowObjectProperties() is implemented, if single setShadowObjectProperties is implemented,
 * multiSetShadowObjectProperties() that handle multiple objects are expected to be implemented too. When this
 * function does not exist, HealthCheck will show Warning.
 * It is not mandatory to implement this function as the server will fallback to 'single object' operation when this function
 * does not exists. That is to set properties of the shadow object one by one instead of in
 * one call to the server plugin.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class ContentSource_EnterpriseConnector extends DefaultConnector
{
    /**
     * Determines whether or not a content source request should return file links or the actual files.
     *
     * This property is set to false by default for backwards compatability with existing content source
     * plugins and older Enterprise Server versions.
     * @var bool
     */
    public $isFileLinksRequested = false;

	/**
	 * getContentSourceId
	 * 
	 * Return unique identifier for this content source implementation. Each alien object id needs
	 * to start with _<this id>_
	 * 
	 * @return string	unique identifier for this content source, without underscores.
	 */
	abstract public function getContentSourceId( );

	/**
	 * getQueries
	 * 
	 * Returns available queries for the content source. These will be shown as named queries.
	 * It's Ok to return an empty array, which means the content source is not visible in the 
	 * Enterprise (content) query user-interface.
	 *
	 * @return array of NamedQuery
	 */
	abstract public function getQueries( );

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
	abstract public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order );
	
	/**
	 * getAlienObject
	 * 
	 * Gets alien object. In case of rendition 'none' the lock param can be set to true, this is the 
	 * situation that Properties dialog is shown. If content source allows this, return the object
	 * on failure the dialog will be read-only. If Property dialog is ok-ed, a shadow object will 
	 * be created. The object is assumed NOT be locked, hence there is no unlock sent to content source.
	 *
	 * @param string	$alienId	Alien object id, so include the _<ContentSourceId>_ prefix
	 * @param string	$rendition	'none' (to get properties only), 'thumb', 'preview' or 'native'
	 * @param boolean	$lock		See method comment.
	 * 
	 * @return Object
	 */
	abstract public function getAlienObject( $alienId, $rendition, $lock );

	/**
	 * deleteAlienObject
	 * 
	 * Deletion of alien object. 
	 *
	 * Default implementation throws an invalid operation exception
	 *
	 * @param string	$alienId		Alien id
	 * 
	 * @return <nothing>
	 */
	public function deleteAlienObject( $alienId )
	{
		// keep code analyzer happy for unused params:
		$alienId=$alienId; 
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', "ContentSource doesn't implement deleteAlienObject" );
	}
	
	/**
	 * listAlienObjectVersions
	 * 
	 * Returns versions of alien object
	 *
	 * Default implementation returns an empty array, which makes client show an empty dialog
	 * and also prevents that get/restoreAlienObjectVerison will be called
	 *
	 * @param string	$alienId	Alien id
	 * @param string 	$rendition	Rendition to include in the version info
	 * 
	 * @return array of VersionInfo
	 */
	public function listAlienObjectVersions( $alienId, $rendition )
	{
		// No versioning on file system
		// return an empty array, which will show empty version dialog.
		
		// keep code analyzer happy for unused params:
		$alienId=$alienId; $rendition=$rendition;
		return array();
	}

	/**
	 * getAlienObjectVersion
	 * 
	 * Returns versions of alien object
	 *
	 * Default implementation throws invalid operation exception, but this should never be called
	 * if listAlientObjectVersions returns an empty array.
	 *
	 * @param string	$alienId	Alien id
	 * @param string 	$version	Version to get as returned by listAlienVersons
	 * @param string 	$rendition	Rendition to get
	 * 
	 * @return VersionInfo
	 */
	public function getAlienObjectVersion( $alienId, $version, $rendition )
	{
		// keep code analyzer happy for unused params:
		$alienId=$alienId; $version=$version; $rendition=$rendition;
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', "ContentSource doesn't implement getAlienObjectVersion" );
	}

	/**
	 * restoreAlienObjectVersion
	 * 
	 * Restores versions of alien object
	 * 
	 * Default implementation throws invalid operation exception, but this should never be called
	 * if listAlientObjectVersions returns an empty array.
	 *
	 * @param string	$alienId	Alien id
	 * @param string 	$version	Version to get as returned by listAlienVersons
	 */
	public function restoreAlienObjectVersion( $alienId, $version )
	{
		// keep code analyzer happy for unused params:
		$alienId=$alienId; $version=$version;
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', "ContentSource doesn't implement restoreAlienObjectVersion" );
	}	
	/**
	 * createShadowObject
	 * 
	 * Create shadow object for specified alien object. The actual creation is done by Enterprise,
	 * the Content Sources needs to instantiate and fill in an object of class Object.
	 * When an empty name is filled in, autonaming will be used.
	 * It's up to the content source implementation if any renditions (like thumb/preview) are stored
	 * inside Enterprise. If any rendition is stored in Enterprise it's the content source implementation's
	 * responsibility to keep these up to date. This could for example be checked whenever the object 
	 * is requested via getShadowObject
	 *
	 * @param string		$alienId 		Alien object id, so include the _<ContentSourceId>_ prefix
	 * @param Object|null	$destObject		In specific cases (e.g. CopyObject, SendToNext, CreateObjectRelations) 
	 										a workflow object is provided that can be partly filled in by user.
	 * 										However, in other cases this parameters is set to null, so be aware.
	 * 
	 * @return Object	filled in with all fields, the actual creation of the Enterprise object is done by Enterprise.
	 */
	abstract public function createShadowObject( $alienId, $destObject );

	/**
	 * getShadowObject
	 * 
	 * Get shadow object. Meta data is all set already, access rights have been set etc.
	 * All that is required is filling in the files for the requested object.
	 * Furthermore the meta data can be adjusted if needed.
	 * If Files is null, Enterprise will fill in the files
	 * 
	 * Default implementation does nothing, leaving it all up to Enterprise
	 *
	 * @param string	$alienId 	Alien object id
	 * @param string	$object 	Shadow object from Enterprise
	 * @param array		$objprops 	Array of all properties, both the public (also in Object) as well as internals
	 * @param boolean	$lock		Whether object should be locked
	 * @param string	$rendition	Rendition to get
	 */
	public function getShadowObject( $alienId, &$object, $objprops, $lock, $rendition )
	{
		// keep code analyzer happy:
		$alienId=$alienId; $object=$object; $objprops=$objprops; $lock=$lock; $rendition=$rendition;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::getShadowObject called for '.
			$object->MetaData->BasicMetaData->ID.'('.$object->MetaData->BasicMetaData->DocumentID.')' );
	}

	/**
	 * getShadowObject2
	 * 
	 * This is an extension of the getShadowObject call. It adds support for the haveVersion parameter,
	 * which will enable content sources to verify whether or not they need to get object files.
	 * 
	 * Files should generally only be retrieved when the haveVersion differs from the content source version.
	 * Every content source implementation could potentially decide for themselves how (and if) they would
	 * implement the haveVersion.
	 * 
	 * Performance could vastly improve by not retrieving files for every request.
	 * 
	 * @param string	$alienId 		Alien object id
	 * @param string	$object 		Shadow object from Enterprise
	 * @param array		$objprops 		Array of all properties, both the public (also in Object) as well as internals
	 * @param boolean	$lock			Whether object should be locked
	 * @param string	$rendition		Rendition to get
	 * @param string	$haveVersion 	Current version of the requestor
	 */
	public function getShadowObject2( $alienId, &$object, $objprops, $lock, $rendition, $haveVersion )
	{
		$haveVersion=$haveVersion;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::getShadowObject2 called for '.
			$object->MetaData->BasicMetaData->ID.'('.$object->MetaData->BasicMetaData->DocumentID.')' );
		$this->getShadowObject( $alienId, $object, $objprops, $lock, $rendition );
	}
	
	/**
	 * saveShadowObject
	 * 
	 * Saves shadow object. This is called after update of DB records is done in Enterprise, but
	 * before any files are stored. This allows content source to save the files externally in 
	 * which case Files can be cleared. If Files not cleared, Enterprise will save the files
	 * 
	 * Default implementation does nothing, leaving it all up to Enterprise
	 *
	 * @param string	$alienId		Alien id of shadow object
	 * @param Object	$object
	 */
	public function saveShadowObject( $alienId, &$object )
	{
		// keep code analyzer happy:
		$alienId=$alienId; $object=$object;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::saveShadowObject called for '.
			$object->MetaData->BasicMetaData->ID.' ('.$object->MetaData->BasicMetaData->DocumentID.')' );
	}

	/**
	 * setShadowObjectProperties
	 * 
	 * Updates the metadata of a shadow object. This is called after updating DB records 
	 * in Enterprise. This allows the content source to synchronize metadata changes with
	 * its external/integrated DB (if any). However, this is an edge case, because the 
	 * content source is more about content and less about metadata. Therefor, normally 
	 * there would be no need to implement this function. Nevertheless, it can be used in
	 * case a tight integration with the external content source is needed.
	 * 
	 * @since v8.2.0
	 * @param string	$alienId		Alien id of shadow object
	 * @param Object	$object
	 */
	public function setShadowObjectProperties( $alienId, &$object )
	{
		// keep code analyzer happy:
		$alienId=$alienId; $object=$object;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::setShadowObjectProperties called for '.
			$object->MetaData->BasicMetaData->ID.' ('.$object->MetaData->BasicMetaData->DocumentID.')' );
	}

	/**
	 * Setting multiple shadow objects' properties.
	 *
	 * This function is similar to {@link: setShadowObjectProperties}, but instead of setting for one object,
	 * function sets for multiple objects in one call to content source integrator. Another difference is that,
	 * instead of getting a list of modified shadow objects, function gets a list of modified shadow object ids
	 * and its modified properties, thus only the modified properties will be sent to the content source for updates.
	 *
	 * All Content Source integrations that implement the setShadowObjectProperties function should implement this.
	 * However, if the content source integrator did not implement this function, as a fallback, integrator can use
	 * {@link: setShadowObjectProperties()} to set properties for multiple objects one by one, which might result in
	 * performance hit as there can be few hundred alien objects being selected.
	 *
	 * With single SetObjectProperties the user can set targets (issues and editions).
	 * For multiple objects this is not supported. The $objects passed in will not have their targets assigned.
	 *
	 * All Content Source integrations that implement the setShadowObjectProperties function should implement this.
	 *
	 * Function expects a list of shadow object ids ( $shadowObjectIds ) of which the modified properties ( $modifiedProperties )
	 * will be sent to the external application for updates.
	 * $shadowObjectIds looks like this: $shadowObjectIds['MyCS'] = array( '789', '999' );
	 *
	 * @since v9.2.0
	 * @param array[] $shadowObjectIds List of array where key is the content source id and value its list of shadow ids.
	 * @param MetaDataValues[] $metaDataValues The modified values that needs to be updated at the content source side.
	 */
	public function multiSetShadowObjectProperties( $shadowObjectIds, $metaDataValues )
	{
		$shadowObjectIds = $shadowObjectIds; $metaDataValues = $metaDataValues; // Keep code analyzer happy.
	}

	/**
	 * deleteShadowObject
	 * 
	 * Deletion of shadow object, called just before the shadow object record is deleted 
	 * or after the object is restored from trash.
	 * 
	 * Default implementation does nothing
	 *
	 * @param string	$alienId		Alien id of shadow object
	 * @param string	$shadowId		Enterprise id of shadow object
	 * @param boolean	$permanent		Whether object will be permanently deleted
	 * @param boolean	$restore		if object is restored from trash
	 */
 	public function deleteShadowObject( $alienId, $shadowId, $permanent, $restore )
	{
		// keep code analyzer happy:
		$alienId=$alienId; $shadowId=$shadowId; $permanent=$permanent; $restore=$restore;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::deleteShadowObject called for '.$shadowId );
	}
	
	/**
	 * listShadowObjectVersions
	 * 
	 * Returns versions of show object or null if Enterprise should handle this
	 * 
	 * Default implementation returns null to have Enterprise handle this.
	 *
	 * @param string	$alienId	Alien id of shadow object
	 * @param string	$shadowId	Enterprise id of shadow object
	 * @param string 	$rendition	Rendition to include in the version info
	 * 
	 * @return VersionInfo[]|null Return NULL if Enterprise should handle this.
	 */
	public function listShadowObjectVersions( $alienId, $shadowId, $rendition )
	{
		// keep code analyzer happy:
		$alienId=$alienId; $shadowId=$shadowId; $rendition=$rendition;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::listShadowObjectVersions called for '.$shadowId );
		return null;
	}

	/**
	 * getShadowObjectVersion
	 * 
	 * Returns versions of shadow object or null if Enterprise should handle this
	 * 
	 * Default implementation returns null to have Enterprise handle this.
	 *
	 * @param string	$alienId	Alien id of shadow object
	 * @param string	$shadowId	Enterprise id of shadow object
	 * @param string 	$version	Version to get as returned by listShadowVersons
	 * @param string 	$rendition	Rendition to get
	 * 
	 * @return VersionInfo or null if Enterprise should handle this
	 */
	public function getShadowObjectVersion( $alienId, $shadowId, $version, $rendition )
	{
		// keep code analyzer happy:
		$alienId=$alienId; $shadowId=$shadowId; $version=$version; $rendition=$rendition;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::getShadowObjectVersion called for '.$shadowId );
		return null;
	}

	/**
	 * restoreShadowObjectVersion
	 * 
	 * Restores versions of alien object, true when handled or null if Enterprise should handle this
	 * 
	 * Default implementation returns null to have Enterprise handle this.
	 *
	 * @param string	$alienId	Alien id of shadow object
	 * @param string	$shadowId	Enterprise id of shadow object
	 * @param string 	$version	Version to get as returned by listAlienVersons
	 * 
	 * @return boolean|null TRUE when handled or NULL if Enterprise should handle this
	 */
	public function restoreShadowObjectVersion( $alienId, $shadowId, $version )
	{
		// keep code analyzer happy:
		$alienId=$alienId; $shadowId=$shadowId; $version=$version;
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::restoreShadowObjectVersion called for '.$shadowId );
		return null;
	}
	
	/**
	 * copyShadowObject
	 * 
	 * Copies a shadow object.
	 * All that is required is filling in the files for the copied object.
	 * Furthermore the meta data can be adjusted if needed.
	 * If Files is null, Enterprise will fill in the files
	 * 
	 * Default implementation creates a new shadow object.
	 *
	 * @param string	$alienId	Alien id of shadow object
	 * @param Object	$srcObject	Source Enterprise object (only metadata filled)
	 * @param Object	$destObject	Destination Enterprise object
	 * 
	 * @return Object	filled in with all fields, the actual creation of the Enterprise object is done by Enterprise.
	 */
	public function copyShadowObject( $alienId, $srcObject, $destObject )
	{
		// keep analyzer happy
		$srcObject = $srcObject;
		
		LogHandler::Log('ContentSource', 'DEBUG', 'ContentSource::copyShadowObject called for '.$alienId );
		
		$shadowObject = $this->createShadowObject($alienId, $destObject);
		
		return $shadowObject;
	}

	/**
	 * Called by the core server to ask the Content Source connector whether or not the  
	 * given user has certain access ($right) to the given alien object.
	 *
	 * Access rights are setup per brand or overrule issue. Underneath, rights can be configured 
	 * more specific; per object type, status or category. Note that the given parameters 
	 * represent the values to be assigned, so the object stored in database might have different
	 * values assigned at the time calling this function.
	 *
	 * By default NULL is returned, which means that the core server does access rights
	 * checking as configured for Enterprise. However, when the connector wants to e.g. let 
	 * the integrated Content Source system do the checking, it should implement this function
	 * and return TRUE or FALSE instead.
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
	public static function checkAccessForAlien( $user, $right, 
		$brandId, $overruleIssueId, $categoryId, $objectType, $statusId, 
		$alienId, $contentSource, $documentId )
	{
		// keep analyzer happy:
		$user=$user; $right=$right; $alienId=$alienId; $contentSource=$contentSource; $documentId=$documentId; 
		$brandId=$brandId; $overruleIssueId=$overruleIssueId; $categoryId=$categoryId; $objectType=$objectType; $statusId=$statusId;

		return null; // let core server check access rights
	}

	/**
	 * Same as {@link:checkAccessForAlien()} but then for shadow objects.
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
	public static function checkAccessForShadow( $user, $right, 
		$brandId, $overruleIssueId, $categoryId, $objectType, $statusId, 
		$shadowId, $contentSource, $documentId )
	{
		// keep analyzer happy:
		$user=$user; $right=$right; $shadowId=$shadowId; $contentSource=$contentSource; $documentId=$documentId; 
		$brandId=$brandId; $overruleIssueId=$overruleIssueId; $categoryId=$categoryId; $objectType=$objectType; $statusId=$statusId;

		return null; // let core server check access rights
	}

    /**
     * Called by the core server to ask the Content Source connector whether or not the connector can provide more
     * information about the given user abstracted from MetaData of the alien- or shadow object.
     *
     * When a content source creates an alien- or a shadow object it is possible that the MetaData contains user names
     * that are not known in Enterprise Server (yet). e.g: The fields Modifier, Creator, Deletor, RouteTo and LockedBy
     * can have such user names as values.
     *
     * If LDAP is enabled and if external systems can put objects into Enterprise Server it is possible that users are
     * only known in LDAP and not in Enterprise Server. Therefore users get abstracted from the MetaData information.
     * And if they are not known in Enterprise Server they are created on the fly with just the bare minimum information
     * available.
     *
     * Such users get a flag "ImportOnLogon" which is set to true. As soon as such user logs in into Enterprise Server,
     * the user information is further enriched from the information provided by LDAP. Like groups, external ID,
     * password and e-mail data etc.
     *
     * On the Users admin page, partially imported users are displayed with "Import Groups" set to 'Yes'. As soon as the
     * user logs in, this will be set to 'No'.
     *
     * This method allows the connector to enrich user information before the user gets created. e.g.:
     * The AdmUser->FullName is used to show the name of the user in the UI. While the MetaData contains the short
     * username, which not always describes the user properly.
     *
     * @since 9.4
     * @param AdmUser $user Object to enrich with more user information
     * @return AdmUser $user Enriched object
     */
    public static function completeUser( AdmUser $user )
    {
        return $user;
    }

    /**
     * This function is called by the core server in order to determine whether the plugin 
     * supports requests for file links.
     *
     * By default any content source connector does not support requests for file links.
     * This can be overruled by the connector by implementing this function.
     *
     * @since 9.7
     * @return bool
     */
    public function isContentSourceFileLinksSupported()
    {
        return false;
    }

    /**
     * For services that support content source file links, it is checked on every call 
     * whether file links are requested. 
     * 
     * If they are, this method is called from the core server in order to communicate to 
     * the content source connector that file links are requested instead of content files.
     *
     * @since 9.7
     */
    public function requestedContentSourceFileLinks()
    {
        $this->isFileLinksRequested = true;
    }

    /**
     * Returns whether or not content source file links are asked for in the current request.
     *
     * @since 9.7
     * @return bool
     */
    public function isContentSourceFileLinksRequested()
    {
        return $this->isFileLinksRequested;
    }

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Helper methods which don't have to be implemented by concrete content sources:
	public function implementsQuery( $query ) 
	{
		$queries = $this->getQueries();
		foreach( $queries as $q ) {
			if( $q->Name == $query ) return true;
		}
		return false;
	}
	
	// Helper methods which don't have to be implemented by concrete content sources:
	// Returns true if the specified content source id is from this content source
	public function isContentSourceId( $contentSourceId ) 
	{
		return $this->getContentSourceId() == $contentSourceId;
	}

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
