<?php
/**
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBDatasource.class.php';
require_once BASEDIR.'/server/bizclasses/BizDatasourceUtils.php';

class BizDatasource
{	
	
	/**
	 * Create a Datasource Object
	 *
	 * @param int $datasourceid Datasource ID
	 * @throws BizException Throws BizException when the creation fails.
	 * @return EnterpriseConnector
	 */
	private static function createDatasourceConnector( $datasourceid )
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

				// get the settings. our custom datasource might get errors if there are no settings.
				// this is just a precaution.
				$settings = DBDatasource::getSettings( $datasourceid );
				if( DBDatasource::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
				}
				
				$connector->setSettings( $settings );
				return $connector;
			} else {
				throw new BizException( 'ERR_DATABASE', 'Server', 'Could not create datasource object: datasource type not found for ID "'.$datasourceid.'".' ); // BZ#636
			}
			
		} else {
			throw new BizException( 'ERR_DATABASE', 'Server', 'Could not create datasource object: no datasource ID' ); // BZ#636
		}
	}
	
	
	/**
	 * Retrieves a list of Datasources.
	 * 
	 * Retrieves a list of available Datasources (by publication id)
	 *
	 * @param int $publicationid Publication id
	 * @throws BizException Throws BizException when the query fails.
	 * @return DatDatasourceInfo[]
	 */
	public static function queryDatasources( $publicationid = 0 )
	{
		// get datasources by publication id
		$datasources = DBDatasource::queryDatasourcesByPub( $publicationid );
		
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
		
		// fetch into array
		$return = array();
		foreach( $datasources as $datasource ) {
			$return[] = BizDatasourceUtils::datasourceToObj( $datasource );
		}
		
		return $return;
	}

	/**
	 * Retrieve a Datasource.
	 * 
	 * Retrieve information about a Datasource
	 * such as a list of Queries, etc.
	 * by Datasource id
	 *
	 * @param int $datasourceId
	 * @throws BizException Throws BizException when there's error getting the Datasource.
	 * @return array List of Settings & Queries & Publications
	 */
	public static function getDatasource( $datasourceId = 0 )
	{
		$datasource = array();
		$returnquery = array();
		
		// get queries of this datasource
		$queries = DBDatasource::getQueries( $datasourceId );
		if( DBDatasource::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
		}
	
		// Requested (January 16th 2008):
		// if the var 'queries' is empty, we found no queries in the database.
		// try to get queries from the datasource itself.
		// we expanded the DatasourceInterface to do so.
		if( count($queries) < 1 )
		{
			$returnquery = self::getQueriesFromDatasource($datasourceId);
		}else{
			foreach( $queries as $query ) {
				$interface = BizDatasourceUtils::queryInterface($query['interface']);
				$returnquery[] = BizDatasourceUtils::queryRecToObj( $query, $interface );
			}
		}
		$datasource["queries"] = &$returnquery;
		$datasource["bidirectional"] = BizDatasourceUtils::intToBoolean(DBDatasource::getBidirectional( $datasourceId ),true);
				
		return $datasource;
	}
	
	/**
	 * getQueriesFromDatasource.
	 * 
	 * Will get queries from the datasource file.
	 * Used only if they are not found in the database.
	 *
	 * @param int $datasourceId Datasource ID
	 * @return Array of Queries
	 */
	private static function getQueriesFromDatasource( $datasourceId )
	{
		$returnquery = array();
		$myDatasource = self::createDatasourceConnector( $datasourceId );
		if($myDatasource) {
			$queries = $myDatasource->getQueries();
			
			if( $queries ) {
				foreach( $queries as $query ) {
					$interface = BizDatasourceUtils::queryInterface($query->Interface);
					$returnquery[] = BizDatasourceUtils::queryObjToObj($query, $interface);
				}
			}
		}
		return $returnquery;
	}

	
	/**
	 * Retrieve records from a datasource.
	 * 
	 * The code beneath will retrieve a datasource out of the database (by query id),
	 * get the query (sql, interface etc) from the database (by query id),
	 * parse the sql (merge the sql with the user input),
	 * include the datasource (by datasource type),
	 * get additional settings (by datasource id),
	 * create an instance of the datasource object and pass the settings on to it,
	 * pass the query to the datasource.
	 * 
	 * Execution is handled on the datasourceplugin side.
	 * 
	 * @param string $user
	 * @param string $objectId
	 * @param int $queryId
	 * @param int $datasourceId
	 * @param array $params
	 * @throws BizException
	 * @return array
	 */
	public static function getRecords( $user = '', $objectId = '', $queryId = 0, $datasourceId = 0, $params = array() )
	{	
		// create datasource object
		$myDatasource = self::createDatasourceConnector( $datasourceId );
		$records = array();		
		$specialfields = null;
		
		if( $myDatasource ) {
			// get query by query id
			$query = DBDatasource::getQuery( $queryId );

			$queryInterface = null;
			$queryString = null;
			$queryRecordID = null;
			if( !$query ) {
				$query = $myDatasource->getQuery( $queryId );
				if( $query ) {
					$queryString = $query->Query;
					$queryInterface = $query->Interface;
					$queryRecordID = $query->RecordID;
				}
			} else{
				// get 'special' fields from the database (readonly/priority)
				$specialfields = DBDatasource::getQueryFields( $queryId );
				if( DBDatasource::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
				}
				$queryString = $query['query'];
				$queryInterface = $query['interface'];
				$queryRecordID = $query['recordid'];
			}
			
			// BZ#682, objectid should be allowed empty (without an error message)
			if( $objectId ) {
				$metadata = self::getMetaData( $objectId, $user );
			} else {
				$metadata = array();
			}
			
			$sql = BizDatasourceUtils::parseSQL($user, $queryString, $queryInterface, $params, $metadata);
			$parameters = BizDatasourceUtils::parseParametersToArray($user, $queryString, $queryInterface, $params, $metadata);
			
			LogHandler::Log( 'Datasource', 'INFO', "Datasource->getRecords: Parsed Query\r\n".$sql ); // BZ#636

			$records = $myDatasource->getRecords( $sql, $queryRecordID, $parameters );
			if( $specialfields ) {
				$records = self::addSpecialFieldsToRecords($records, $specialfields);
			}			
		}
		return $records;
	}
	
	/** 
	 * Requested feature: Meta-data support in Queries
	 *
	 * @param string $objectid
	 * @param string $user
	 * @return array List of flat meta data properties (key-values)
	 */
	private static function getMetaData( $objectid, $user )
	{	
		// Get an object
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$object = BizObject::getObject($objectid,$user,false,'none', array('Targets'));
		
		// create lists of structured meta-data
		$metadata = array();
		
		$metadata["BasicMetaData"] 		= &$object->MetaData->BasicMetaData;
		$metadata["ContentMetaData"] 	= &$object->MetaData->ContentMetaData;
		$metadata["WorkflowMetaData"] 	= &$object->MetaData->WorkflowMetaData;
		$metadata["ExtraMetaData"] 		= &$object->MetaData->ExtraMetaData;
		
		// create a 'flat' list of meta-data
		$metadata["FlatMetaData"]		= array();
		
		foreach( $metadata["BasicMetaData"] as $key=>&$value )
		{
			if( $key != "Publication" && $key != "Category" )
			{
				$var = "\$BasicMetaData";
				$var.= "[".$key."]";
				$metadata["FlatMetaData"][$var] = $value;
			}
		}
		
		$objAr = array();
		$objAr["BasicMetaData"] = array("Publication","Category");
		for($i=0;$i<count($objAr["BasicMetaData"]);$i++)
		{
			foreach( $metadata["BasicMetaData"]->$objAr["BasicMetaData"][$i] as $key=>&$value )
			{
				if( $value )
				{
					$var = "\$BasicMetaData[".$objAr["BasicMetaData"][$i]."]";
					$var.= "[".$key."]";
					$metadata["FlatMetaData"][$var] = $value;
				}
			}
		}
				
		$counter = 0;
		foreach( $object->Targets as &$target )
		{	
			// extract the PubChannel and Issue objects
			$objAr = array();
			$objAr["TargetMetaData"] = array("PubChannel","Issue");
			for($i=0;$i<count($objAr["TargetMetaData"]);$i++)
			{
				foreach( $target->$objAr["TargetMetaData"][$i] as $key=>&$value )
				{
					$var = "\$TargetMetaData[".$counter."]";
					$var .= "[".$objAr["TargetMetaData"][$i]."]";
					$var.= "[".$key."]";
					$metadata["FlatMetaData"][$var] = $value;
				}
			}
			
			// extract the Editions objects
			foreach( $target->Editions as $key=>&$value )
			{
				if( $value )
				{				
					foreach( $value as $editionkey=>&$editionValue)
					{
						$var = "\$TargetMetaData[".$counter."]";
						$metadata["FlatMetaData"][$var."[Editions][".$key."][".$editionkey."]"] = $editionValue;
					}
				}
			}
			
			$counter++;
		}
			
		foreach( $metadata["ContentMetaData"] as $key=>&$value )
		{
			if( $value )
			{
				$var = "\$ContentMetaData";
				if($key != "Keywords")
				{
					$var.= "[".$key."]";
					$metadata["FlatMetaData"][$var] = $value;
				}else{
					foreach( $metadata["ContentMetaData"]->Keywords as $key1=>&$value1 )
					{
							$metadata["FlatMetaData"][$var."[Keywords][".$key1."]"] = $value1;
					}
				}
			}
		}
		
		foreach( $metadata["WorkflowMetaData"] as $key=>&$value )
		{
			$var = "\$WorkflowMetaData";
			if($key != "State")
			{
				$var.= "[".$key."]";
				$metadata["FlatMetaData"][$var] = $value;
			}else{
				foreach( $metadata["WorkflowMetaData"]->State as $key1=>&$value1 )
				{
					$metadata["FlatMetaData"][$var."[State][".$key1."]"] = $value1;
				}
			}
		}
		
		foreach( $metadata["ExtraMetaData"] as $mdObj )
		{
			foreach( $mdObj as $key => $value )
			{
				$var = "\$ExtraMetaData[".substr($mdObj->Property,2)."]";
				if( count($mdObj->Values) > 1 )
				{
					$var.= "[".$key."]";
				}
				$metadata["FlatMetaData"][$var] = $value;
			}
		}
						
		return $metadata["FlatMetaData"];
	}
	
	/**
	 * SetRecords
	 *
	 * Pass changed records, from the client, back to the datasource.
	 * 
	 * @param string $user
	 * @param string $objectId
	 * @param int $datasourceId
	 * @param int $queryId
	 * @param array $params
	 * @param array $records
	 */
	public static function setRecords( $user = '', $objectId = '', $datasourceId = 0, $queryId = 0, $params = array(),
	                                   $records = array() )
	{		
		// the $records variable does not have the right objects,
		// parse them manually (DOES THIS GIVE A OVERHEAD ISSUE??)
		// This function needs revision!
		$arrayofrecords = array();
		foreach( $records as $record ) {
			$arrayoffields = array();
			$fields = &$record->Fields;			
			foreach( $fields as $field ) {
				$fieldValue = "";
				$fieldType = "StrValue";	// default type
				
				foreach( $field as $key=>&$value ) {
					if( $key == "StrValue" || $key == "IntValue" ) {
						$fieldType = $key;
						$fieldValue = $value;
					} elseif( $key == "ListValue" || $key == "ImageListValue" ) {
						$fieldType = $key;							
						$listitems = array();
						foreach( $value->List as &$list ) {
							$listitemattributes = array();
							foreach( $list->Attributes as $attribute ) {
								$listitemattributes[] = BizDatasourceUtils::attributeToObj( $attribute->Name, $attribute->Value );
							}
							// modified below to fix: BZ#354
							if( $key == "ImageListValue" ) {
								$listitems[] = BizDatasourceUtils::imageListItemToObj( $list->Name, $list->Value, $listitemattributes );
							} else {
								$listitems[] = BizDatasourceUtils::listItemToObj( $list->Name, $list->Value, $listitemattributes );
							}
						}
						
						$fieldValue = &$listitems;
					}
				}
				
				$arrayofattributes = array();
				foreach( $field->Attributes as $attribute ) {
					$arrayofattributes[] = BizDatasourceUtils::attributeToObj($attribute->Name,$attribute->Value);
				}
				$arrayoffields[] = BizDatasourceUtils::fieldToObj($field->Name, $fieldType, $fieldValue, $arrayofattributes, $field->UpdateType, $field->UpdateResponse, $field->ReadOnly, $field->Priority);
			}
			$arrayofrecords[] = BizDatasourceUtils::recordToObj( $record->ID, $arrayoffields, $record->UpdateType, $record->UpdateResponse, $record->Hidden );
		}
		
		// create datasource object
		$myDatasource = self::createDatasourceConnector( $datasourceId );
		if($myDatasource) {
			// get query by query id
			$query = DBDatasource::getQuery( $queryId );

			$queryString = null;
			$queryInterface = '';
			$queryRecordID = null;
			if( !$query ) {
				$query = $myDatasource->getQuery( $queryId );
				if( $query ) {
					$queryString = $query->Query;
					$queryInterface = $query->Interface;
					$queryRecordID = $query->RecordID;
				}
			} else {
				$queryString = $query['query'];
				$queryInterface = $query['interface'];
				$queryRecordID = $query['recordid'];
			}
			
			// BZ#682. support empty object id
			if( $objectId ) {
				$metadata = self::getMetaData( $objectId, $user );
			} else {
				$metadata = array();
			}
			
			/*$sql =*/ BizDatasourceUtils::parseSQL($user, $queryString, $queryInterface, $params, $metadata);
			$parameters = BizDatasourceUtils::parseParametersToArray($user, $queryString, $queryInterface, $params, $metadata);

			$myDatasource->setRecords($arrayofrecords, $queryRecordID, $parameters);
		}
	}
	
	/**
	 * addSpecialFieldsToRecords
	 * 
	 * This method adds priority and readonly fields to an array of records
	 *
	 * @param Array of Records $records
	 * @param Array of Fields $specialfields
	 * @return Array of Records
	 */
	public static function addSpecialFieldsToRecords( $records, $specialfields )
	{
		foreach( $records as &$record )
		{
			foreach( $record->Fields as &$field )
			{
				foreach( $specialfields as &$specialfield )
				{
					if( $specialfield["name"] == $field->Name )
					{
						$field->Priority = BizDatasourceUtils::intToBoolean($specialfield["priority"]);
						$field->ReadOnly = BizDatasourceUtils::intToBoolean($specialfield["readonly"]);
					}
				}
			}
		}
		
		return $records;
	}
	
	
	/**
	 * Handle an external call that a source of data
	 * has updated records available.
	 *
	 * @param int $datasourceId
	 * @param string $user
	 * @param string $familyvalue
	 * @throws BizException
	 */
	public static function hasUpdates( $datasourceId, /** @noinspection PhpUnusedParameterInspection */ $user,
	                                   $familyvalue )
	{
		$placements = 0;
		if( $familyvalue ) {
			// find on which documents this family value is placed
			$placements = DBDatasource::getDirtyDocuments( $familyvalue );
			if( DBDatasource::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
			}
		}
		
		if( count($placements) > 0 ) {
		
			// create datasource object
			//$newRecordSet = null;
			// WoodWing patch 24-09-2014: Do not retrieve the updates from the datasource
			//$myDatasource = self::createDatasourceConnector( $datasourceid );
			//if($myDatasource)
			//{
				// get the new record set
				//$newRecordSet = $myDatasource->getUpdates( $familyvalue );
			//}	
				
			// store the new set of records into the database
			// WoodWing patch 24-09-2014: Do not store the updates in the database
			//$serializedSet = serialize($newRecordSet);
			//$updateID = DBDatasource::storeUpdate($serializedSet,$familyvalue);
			//if( DBDatasource::hasError() ) {
				//throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
			//}
			
			// Create and Send a broadcast
			require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
			//$sendBefore = array(); // store send object id's, to avoid duplicate messages
			foreach( $placements as $placement ) {
				// Create a link in the database (UpdateID to every ObjectID)
				// WoodWing patch 24-09-2014: Do not store the updateRelations in the database
				//DBDatasource::storeUpdateObjectRelation($placement["objectid"],$updateID);
				//if( DBDatasource::hasError() ) {
					//throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
				//}
				
				// Flag the objects dirty
				DBDatasource::flagDirtyDocuments( $placement["id"] );
				if( DBDatasource::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBDatasource::getError() );
				}
				
				// Send a 'knuffelvlag'
				require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
				//define('FLAG_PLACEMENT_UPDATED',5);
				DBObjectFlag::setObjectFlag( $placement["objectid"],'DataSource Plugin','5', 1, 'The DataSource has been updated.'); // BZ#636
				
				// Send message to object
				/* // COMMENTED OUT: The WSDL does not support 'plutus' type. (When this message is still needed, it should respect the WSDL.)
				if( !array_key_exists( $placement['objectid'], $sendBefore ) ) {
					$message = new Message();
					$message->ObjectID = $placement['objectid'];
					$message->MessageType = 'plutus';
					$message->Message = $updateID.','.$familyvalue;
					$message->FromUser = $user['user'];
					$messageList = new MessageList();
					$messageList->Messages = array( $message );
					BizMessage::sendMessages( $messageList );
					$sendBefore[ $placement['objectid'] ] = true; // remember to avoid sending duplicate messages
				}*/
			}
		}else{
			LogHandler::Log("SERVER","INFO","Non-placed update received from external source. Update ignored.");
		}
	}

	/**
	 * Returns a list of records.
	 *
	 * @param string $objectId
	 * @param int $updateId
	 * @param string $user
	 * @return array|mixed
	 */
	public static function getUpdates( $objectId, $updateId, /** @noinspection PhpUnusedParameterInspection */ $user )
	{	
		// check if there is a relation
		$relation = DBDatasource::getUpdateObjectRelation( $objectId, $updateId );
		if( !is_array($relation) ) {
			$relation = array();
		}
		
		// initialize the un-serialized record set
		$unserializedSet = array();
		
		if( count($relation) > 0) {
			// get the update from the database
			$update = DBDatasource::getUpdate( $updateId );
			
			// get the serialized record set from the update
			$serializedSet = &$update["recordset"];
			// un-serialize
			$unserializedSet = unserialize($serializedSet);
			
			/* // COMMENTED OUT: The WSDL does not support 'plutus' type. (When this message is still needed, it should respect the WSDL.)
			// get the family value from the update, we need this to set a message
			$familyvalue = $update["familyvalue"];
			
			// Create and Send a message (without broadcast)
			require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
			$message = new Message();
			$message->ObjectID = $objectId;
			$message->MessageType = 'plutus';
			$message->Message = $updateId.",".$familyvalue;
			$message->FromUser = $user['user'];
			
			$messageList = new MessageList();
			$messageList->Messages = array( $message );
			BizMessage::sendMessages( $messageList, false ); // send the message
			*/
			
			// BZ#1058, update cache and knuffelvlag are no longer emptied here! The code is transfered to 'onSave'.
			/* -code was here- */
		}
		
		// return the record set via SOAP
		return $unserializedSet;
	}
	
	/**
	 * Save QueryPlacement - Object relations
	 * These relations are necessary to determine updates within a datasource
	 *
	 * @param string $datasourceid
	 * @param DatPlacement[] $placements
	 * @param string $user
	 */
	public static function onSave( $datasourceid, $placements, $user )
	{
		foreach( $placements as $placement ) {
			// BZ#1058 - update cache and knuffelvlag are emptied here! This code is transfered from 'GetUpdates'
			// get all update relations for this object
			$relations = DBDatasource::getUpdateRelation('',$placement->ObjectID);
			
			// remove all update relations from cache for this object
			DBDatasource::deleteUpdateObjectRelation($placement->ObjectID);
			
			// BZ#1400 start
			// Get the object's relations
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$object = BizObject::getObject($placement->ObjectID, $user, false, 'none', array('Relations'));
			
			// get the parents (layouts) this object is placed on
			$parents = array();
			$objectrelations = $object->Relations;
			foreach( $objectrelations as $objectrelation ) {
				if( $objectrelation->Child == $placement->ObjectID ) {
					$parents[] = $objectrelation->Parent;
				}
			}
			// BZ#1400 end (part 1/3)
			
			// delete the knuffelvlag
			require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
			DBObjectFlag::deleteObjectFlagsByObjId($placement->ObjectID);
		
			// if, by removing the relations (above), there is no more relation
			// to the update, remove the update
			for($i=0; $i<count($relations); $i++) {
				$updateid = $relations[$i]["updateid"];
				
				// BZ#1400 start
				// check if any of the parents has a relation to the same updateid as the child
				// if so, delete that relation because the child is updated
				foreach( $parents as $parent ) {
					DBDatasource::deleteUpdateObjectRelation($parent, $updateid);
				}
				// BZ#1400 end (part 2/3)
				
				$updaterelation = DBDatasource::getUpdateRelation( $updateid );
				if( count($updaterelation) < 1 ) {
					DBDatasource::deleteUpdate( $updateid );
				}
			}
			
			/* // COMMENTED OUT: The WSDL does not support 'plutus' type. (When this message is still needed, it should respect the WSDL.)
			// Delete all plutus messages that were sent before to this object.
			require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
			BizMessage::deleteMessagesForObject( $placement->ObjectID, 'plutus' );
			*/
			
			// BZ#1400 start
			// if the parent has no more relations to an update, delete the 'plutus'-message
			// also delete the object flags
			foreach( $parents as $parent ) {
				$parentrelations = DBDatasource::getUpdateRelation('', $parent);
				if( !is_array($parentrelations) ) {
					$parentrelations = array();
				}
					
				if( count($parentrelations) < 1 ) {

					/* // COMMENTED OUT: The WSDL does not support 'plutus' type. (When this message is still needed, it should respect the WSDL.)
					// Delete all plutus messages that were sent before to this parent object.
					BizMessage::deleteMessagesForObject( $parent, 'plutus' );
					*/
					
					// delete flags
					DBObjectFlag::deleteObjectFlagsByObjId($parent);
				}
			}
			// BZ#1400 end (part 3/3)
			
			// check if this is a new placement, or an existing one
			$queryplacement = DBDatasource::getQueryPlacement($placement->ObjectID);
			if( $queryplacement ) {
				$queryplacementid = $queryplacement["id"];			
				
				// check if there are updates for this placement,
				// if so; do NOT set the dirty flag to 0
				// if not; set the dirty flag to 0
				$relation = DBDatasource::getUpdateRelation('',$placement->ObjectID);
				if( count($relation) == 0 ) {
					DBDatasource::updateQueryPlacement($placement->ObjectID,$datasourceid);
				}
				// throw away all the old family values
				DBDatasource::deleteFamilyValues($queryplacement["id"]);
			} else {
				// we insert a new query placement
				$queryplacementid = DBDatasource::newQueryPlacement($placement->ObjectID,$datasourceid);
			}
			
			foreach( $placement->PlacedQueries as $placedQuery ) {
				foreach( $placedQuery->FamilyValues as $familyvalue ) {
					$queryid = $placedQuery->QueryID;
					
					// get the family field of this query, if any
					$query = DBDatasource::getQuery($queryid);
					if( !$query ) {
						$myDatasource = self::createDatasourceConnector($datasourceid);
						$query = $myDatasource->getQuery( $queryid );
						$qrecordfamily = $query->RecordFamily;
					} else {
						$qrecordfamily = $query["recordfamily"];
					}
					
					if($qrecordfamily && $familyvalue) {
						DBDatasource::newFamilyValue($queryplacementid,$qrecordfamily,$familyvalue);
					}
				}
			}
		}
	}
}
