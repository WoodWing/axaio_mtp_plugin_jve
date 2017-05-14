<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This SmartArchive ContentSource connector enables users to search and reuse articles and images from an archive.
 * Like the production server (that runs this connector), the archive is also an Enterprise Server instance.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';  
require_once BASEDIR.'/server/interfaces/services/BizException.class.php';

require_once dirname(__FILE__).'/config.php';
require_once dirname(__FILE__).'/SmartArchiveProxy.class.php';

class SmartArchive_ContentSource extends ContentSource_EnterpriseConnector
{
	/**
	 * Called by core server to get a unique ID of this Content Source.
	 */
	final public function getContentSourceId()
	{
		return SMARTARCHIVE_CONTENTSOURCEID;
	}

	/**
	 * Called by core server to get the named queries hosted by this Content Source.
	 * @return array of NamedQueryType
	 */
	final public function getQueries()
	{
		// Create SOAP client and logon when needed
		$this->initProxy();

		// Add "Type" query param
		// localised SMARTARCHIVE_SEARCH_OBJECT_TYPES
		$localizedObjTypeMap = $this->getLocalizedObjTypeMap(unserialize(SMARTARCHIVE_SEARCH_OBJECT_TYPES));
		$localizedObjTypes = array_keys($localizedObjTypeMap);
		
		$commonParams = array();
		// Add 'Object Type' query param. 
		/* Note: Content Station Problem
		* For PropertyInfo ( $name, $displayName, $category, $type...
		*  $name = 'Types' This is a temporary fix for CS. If leave it as 'Type' the CS cannot recognize this param. (Maybe due to cache issue in CS?)
		*/
		$commonParams[] = new PropertyInfo(
			'Types','Object Type', null, 'list', // Name, Display name, Category, Type,
			$localizedObjTypes[0], $localizedObjTypes ); // Default value, Value list

		// Add "PlainContent" query param
		$commonParams[] = new PropertyInfo(
			'PlainContent', 'Keyword Search', null, 'string' ); // Name, Display name, Category, Type
			
		// Add 'SMARTARCHIVE_FILTER_FIELDS" query param 	
		// SMARTARCHIVE_FILTER_FIELDS is defined by the users in config.php
		$filterFields = unserialize(SMARTARCHIVE_FILTER_FIELDS);
		if(count($filterFields) > 0){
			foreach($filterFields as $filterField){
				$commonParams[] = new PropertyInfo(
				$filterField,$filterField,null,'string');	
			}
		}
		
		// Add named query per publication	
		$piss = SmartArchiveProxy::getSessionData('SMARTARCHIVE_PISS');	
		if(isset($piss)){
			$piss = unserialize($piss);	
		}else{
			$piss = null;	
		}
		$queries = array();
		if( isset($piss)){
			foreach( $piss as $qName => $qData ) {
				$thisParams = array();
				$issues = $qData['Issues'];
				if( !is_null($issues) ) {
					$issues = array_keys($issues); // take out the issue names only (ignore the ids)
					$thisParams[] = new PropertyInfo(
						'Issue', 'Issue', null, 'list', // Name, Display name, Category, Type,
						$issues[0], $issues ); // Default value, Value list
				}
				if(isset($qData['States'])){
					$states = $qData['States'];
					if( !is_null($states) ) {
						$states = array_keys($states); // take out the State Name only (ids are not needed for this)
						$thisParams[] = new PropertyInfo(
						'States','Status',null, 'list', //Name, Display Name, Category, Type
						$states[0], $states);
					}
				}
				$queries[] = new NamedQueryType( $qName, array_merge($commonParams,$thisParams) );
			}
		}
		return $queries;
	}

	/**
	 * Run a named query against the archive server.
	 * Called by core server to get search results for the specified query.
	 *
	 * @param $query        string                    
	 * @param $params       array of QueryParam       
	 * @param $firstEntry   unsignedInt
	 * @param $maxEntries   unsignedInt
	 * @param $order        array of QueryOrder
	 * @return WflNamedQueryResponse
	 */
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// Create SOAP client and logon when needed
		$this->initProxy();

		// Add PublicationId search param on-the-fly, which is implicitly determined by query name.
		// In case of overrule issue, also add the IssueId search param. 
		// In both cases, zero means 'all', and for which NO param is added (to query all).
		$piss = unserialize(SmartArchiveProxy::getSessionData('SMARTARCHIVE_PISS'));
		$qData = $piss[$query]; // take out the query data
		if( $qData['PublicationId'] != 0 ) {
			$params[] = new QueryParam( 'PublicationId', '=', $qData['PublicationId'] );
		}
		if( $qData['IssueId'] != 0 ) { // overrule issue
			$params[] = new QueryParam( 'IssueId', '=', $qData['IssueId'] );
		}
		
		// Rebuild the parameters while replacing name-based search with id-based.
		$adjustedParams = array();
		foreach( $params as $param ) {
			// Due to a bug in ID/IC, empty parameters are sent as empty values, but should be left out entirely, like CS does.
			$val = trim( $param->Value );
			if( empty( $val ) ) {
				continue; // leave out empty params!
			}
			if( $param->Property == 'Issue' ) { // non-overrule issue
				$param->Property = 'IssueId';
				$param->Value = $qData['Issues'][$param->Value];
				// Skip param when the 'all' entry is selected
				if( $param->Value == 0 ) {
					continue;
				}
			} else if( $param->Property == 'PlainContent' ) {
				$param->Operation = 'contains'; // repair since '=' operator does not work
			}else if( $param->Property == 'States'){
				$param->Property = 'StateId';
				$param->Value = $qData['States'][$param->Value];
				// skip param if 'all' entry is selected.
				if($param->Value == 0){
					continue;
				}
			}else if( $param->Property == 'Types'){ //This is just for temporary as currently queryParam 'Type' is not working. It should be 'Type' not 'Types' when the problem is resolved. (CS issue)
				$param->Property = 'Type'; //This should be taken out when the above problem is solved.
				
				// localised SMARTARCHIVE_SEARCH_OBJECT_TYPES
				$localizedObjTypeMap = $this->getLocalizedObjTypeMap(unserialize(SMARTARCHIVE_SEARCH_OBJECT_TYPES));
				$param->Value = $localizedObjTypeMap[$param->Value];
				
			}
			$adjustedParams[] = $param;
		}
		
		// Perform the search request at archive server 
		$firstEntry++;  //increament $firstEntry if not the $firstEntry returned by core is actually the last entry of its current results. (It will get repeated as 1st entry on 'next'(following) page) 
		$qoResponse = SmartArchiveProxy::queryObjects( $adjustedParams, $firstEntry, $maxEntries, $order );

		// Prefix object IDs so later we know they are 'ours' (to catch up GetObjects services)
		foreach( $qoResponse->Rows as &$row ) {
			// We may assume that the object ID is at first column (index zero)
			$row[0] = SMARTARCHIVE_CONTENTSOURCEPREFIX . $row[0];
		}
		
		
		//Temporary Solution. - CS issue. 
		/* For columns ($resp->Columns) such as Category, State, Publication it is still looking into LogOn Response thus not recognizing the $resp->Columns being returned by SmartArchiveProxy::queryObjects. 
		* In this case, need to change the column name (not column display name) just to make CS take it as if a new column is returned and it will stop looking from LogOn Response.
		*/
		foreach ( $qoResponse->Columns as $col){
				if($col->Name == 'Category'){
					$col->Name = 'cat_temp';
				}
				if($col->Name == 'State'){
					$col->Name = 'state_temp';
				}
				if($col->Name == 'Publication'){
					$col->Name = 'brand_temp';
				}
		}

		
		// Cast WflQueryObjectsResponse to WflNamedQueryResponse (but avoiding expensive deep copy!)
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';		
		$namedQueryResp = new WflNamedQueryResponse();
		foreach( array_keys(get_class_vars('WflNamedQueryResponse')) as $var ) {
			if( property_exists( $qoResponse, $var ) ) {
				$namedQueryResp->$var = $qoResponse->$var;
			}
		}
		return $namedQueryResp;
	}

	/**
	 * Called by core server to retrieve an object from the content source to introduce as an alien
	 * at the production server. E.g. to show preview when user clicks object in search results.
	 *
	 * @param string $alienID
	 * @param string $rendition
	 * @param boolean $lock
	 * @return Object
	 */
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		LogHandler::Log('SmartArchive','DEBUG', __METHOD__.': Getting alien object with ID: '.$alienID);

		// Create SOAP client and logon when needed
		$this->initProxy();

		// The received alienID is prefixed which is not recognized by the archive's GetObject service, 
		// so we remove the prefix.
		$objID = substr( $alienID, strlen(SMARTARCHIVE_CONTENTSOURCEPREFIX) );
		$response = SmartArchiveProxy::getObjects( array($objID), $rendition );
		$object = $response->Objects[0]; // there can be only 1 object
		
		/* Alien Object needs an 'identity' in the production server
		* getEnterpriseContext() returns the publication, category and status specially dedicated for these alien objects. 
		*/
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext($publication, $category,$status, $object->MetaData->BasicMetaData->Type);
	
		// Adjust object properties to let us recognize we have introduced this alien object
		$basicMD = $object->MetaData->BasicMetaData;
		$basicMD->ID = $alienID;
		$basicMD->Publication = $publication;
		$basicMD->Category = $category;
		$basicMD->ContentSource = SMARTARCHIVE_CONTENTSOURCEID;
		
		//Need to replace the Object status defined in Production server which this status is dedicated for Alien Object Status.(=Archive Objects' status)
		$workflowMD = $object->MetaData->WorkflowMetaData;
		$workflowMD->State = $status;

		
		// Get the rid of dangerous foreign ids of relational data
		self::cleanAndRepairRelationalData( $object );

		// Pass mutated object back to core server for further treatment
		return $object;
	}

	final public function deleteAlienObject( $alienID )
	{
		throw new BizException( 'ERR_AUTHORIZATION', 'Client', '');
	}

	/**
	 * Create object record in production server with thumb, preview and native to stay in file-system.
	 *
	 * @param string	$alienId 		Alien object id, so include the _<ContentSourceId>_ prefix
	 * @param Object	$destObject		In saome cases (CopyObject, SendToNext, Create relatio) 
	 									this can be partly filled in by user, in other cases this is null.
	 * 									In some cases this is mostly empty, so be aware.
	 * 
	 * @return Object	filled in with all fields, the actual creation of the Enterprise object is done by Enterprise.
	 */
	final public function createShadowObject( $alienID, $destObject )
	{
		LogHandler::Log('SmartArchive','DEBUG', __METHOD__.': Getting shadow object with ID: '.$alienID);

		// Create SOAP client and logon when needed
		$this->initProxy();

		// Get the object with files (all renditions) and plain content
		$objID = substr( $alienID, strlen(SMARTARCHIVE_CONTENTSOURCEPREFIX) );
		$response = SmartArchiveProxy::getObjects( array($objID), 'native' );
		$object = $response->Objects[0]; // there can be only 1 object

		/* Alien Object needs an 'identity' in the production server
		* getEnterpriseContext() returns the publication, category and status specially dedicated for these alien objects. 
		*/
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext($publication, $category,$status, $object->MetaData->BasicMetaData->Type);
	
		// Adjust object properties to let us recognize we have introduced this alien object
		$basicMD = $object->MetaData->BasicMetaData;
		$basicMD->ID = $alienID;
		$basicMD->Publication = $publication;
		$basicMD->Category = $category;
		$basicMD->ContentSource = SMARTARCHIVE_CONTENTSOURCEID;
				
		//Need to replace the Object status defined in Production server which this status is dedicated for Alien Object Status.(=Archive Objects' status)
		$workflowMD = $object->MetaData->WorkflowMetaData;
		$workflowMD->State = $status;

		// Get the rid of dangerous foreign ids of relational data
		self::cleanAndRepairRelationalData( $object );

		// Pass mutated object back to core server for further treatment
		return $object;
	}

	/**
	 * Creates a new SOAP client and does LogOn when needed
	 */
	private function initProxy()
	{
		SmartArchiveProxy::createClient();
		$response = SmartArchiveProxy::logOn();
		if( !is_null($response) ) {
			// Lookup P/I/S/S entities at the logon response
			$allString = BizResources::localize('ACT_ALL');
			$piss = array();
			$piss['Smart Archive'] = array( 'PublicationId' => 0, 'IssueId' => 0, 'Issues' => null ); 
			foreach( $response->Publications as $pubInfo ) {
				$issues = array();
				$issues[$allString] = 0;
				if( $pubInfo->Issues ) foreach( $pubInfo->Issues as $issInfo ) {
					if( $issInfo->OverrulePublication == true ) {
						$states = array();
						$states[$allString] = 0;
						foreach( $issInfo->States as $stateInfo) {
							$states[$stateInfo->Name] = $stateInfo->Id;
						}
						
						$piss['-> '.$pubInfo->Name.' -> '.$issInfo->Name] = array( 'PublicationId' => $pubInfo->Id, 'IssueId' => $issInfo->Id, 'Issues' => null, 'States' => $states );
					} else {
						$issues[$issInfo->Name] = $issInfo->Id;
					}
				}
				
				$states = array();
				$states[$allString] = 0;
				if($pubInfo->States) foreach( $pubInfo->States as $stateInfo){
					$states[$stateInfo->Name] = $stateInfo->Id;
				}

				if(!preg_match("/:/",$pubInfo->Id)){ //Added this else there'll be an extra entry for overrule issue where pubInfo->Id is x:y (for an example 2:5) which is actually already catered above
					$piss['-> '.$pubInfo->Name] = array( 'PublicationId' => $pubInfo->Id, 'IssueId' => 0, 'Issues' => $issues, 'States' => $states ); 
				}
			}
			// Store P/I/S/S entities in the session for later use
			SmartArchiveProxy::setSessionData( 'SMARTARCHIVE_PISS', serialize($piss) );
		}
	}
	
	/**
	 * During the transformation process from archive object to production object (through
	 * alien and shadow objects) we could confuse server and clients by passing foreign ids
	 * of relational data, such as brands and statuses, but also parents and childs.
	 * This is because those archive ids are not known to the production environment and
	 * so lookups will fail.
	 * This function cleans out such dangerous data, but also tries to make-up good defaults
	 * for the new object that is about to get created in the production environment.
	 *
	 * @param $object Object to get updated.
	 */
	private function cleanAndRepairRelationalData( $object )
	{
		// Remove the object Relations to avoid any client confusal of relational alien objects.
		$object->Relations = array();
		
		// Remove the object Targets to avoid any client/server confusal of issue/edition ids.
		$object->Targets = array();
	}
	
	/*
	* Localized the objectType.
	* Given $objectTypes, it will map each of the objectType in the array to the correspond 
	* user language's objectType.
	*
	* @param array $objectTypes ObjectTypes to get tranformed.
	*
	* @return array $localizedObjTypeMap Returned an array with the localized objectType
	*/
	
	private function getLocalizedObjTypeMap($objectTypes){
		$objTypeMap = getObjectTypeMap();
		        
		$localizedObjTypeMap = array();
		foreach($objectTypes as $objectType){
			$localizedObjTypeMap[$objTypeMap[ $objectType ]] =  $objectType;
		}
		return $localizedObjTypeMap;
	}
	
	/* Giving 'identity' to the Alien Object by giving Publication, Category and Status from Production Server specially dedicated(have to define in config.php) for Alien Objects.
	*  
	* @param string $publication Empty string to get pulication.
	* @param string $category Empty string to get category.
	* @param string $status Empty string to get status.
	* @param string $objType Object Type. (=Article? OR =Image?) - The Object Type will determine the Status.
	*  
	*/
	private function getEnterpriseContext( &$publication,  &$category, &$status, $objType)
	{
		// Get list of publications from Enterpise. If available we use WW News
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$username = BizSession::getShortUserName();

		// Get all publication info is relatively expensive. In case a thumbnail overview is used this method is called
		// once per thumbnail, adding up to significant time. Hence we cache the results for the session:
		require_once 'Zend/Registry.php';

		if( Zend_Registry::isRegistered( 'SA-Publication' ) ) {
			$publication = Zend_Registry::get( 'SA-Publication' );
		} else {
			$pubs = BizPublication::getPublications( $username );
			// Default to first, look next if we can find one with the configured name:
			$pubFound = $pubs[0];
			foreach( $pubs as $pub ) {
				if( $pub->Name == SA_BRAND ) {
					$pubFound = $pub;
					break;
				}
			}
			$publication 	= new Publication($pubFound->Id);
			Zend_Registry::set( 'SFS-Publication', $publication );
		}
		
		if( Zend_Registry::isRegistered( 'SA-Category' ) ) {
			$category = Zend_Registry::get( 'SA-Category' );
		} else {
			$categories = BizPublication::getSections( $username, $publication->Id );
			// Default to first, look next if we can find one with the configured name:
			$catFound = $categories[0];
			foreach( $categories as $cat ) {
				if( $cat->Name == SA_CATEGORY ){
					$catFound = $cat;
					break;
				}
			}
			$category 	= new Category($catFound->Id);
			Zend_Registry::set( 'SA-Category', $category );
		}
		
		if( Zend_Registry::isRegistered( 'SA-Status' ) ) {
			$status = Zend_Registry::get( 'SA-Status' );
		} else {
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			//get the status depending on the object Type.
			$states=BizWorkflow::getStates($username, $publication->Id, null /*issue*/, $category->Id, $objType );
			// Default to first, look next if we can find one with the configured name:
			$statFound = $states[0];
			foreach( $states as $stat ) {
				if( $stat->Name == $objType) {
					$statFound = $stat;
					break;
				}
			}
			$status 	= new State($statFound->Id,$statFound->Name,$statFound->Type);
			Zend_Registry::set( 'SA-Status', $status );
		}
	}
}
