<?php

/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAdmPubChannel extends DBBase
{
	const TABLENAME = 'channels';
	
	/**
	 * Creates given PubChannel data objects in the smart_channels DB table
	 * that belong to a given brand ID ($pubId).
	 *
	 * @since 6.0
	 * @param integer $pubId Brand ID
	 * @param array $pubChannels List of AdmPubChannel data objects to create
	 * @param array $typeMap Lookup table with custom property names as keys and types as values.
	 * @throws BizException Throws BizException when error occurred during cration of pub channels object.
	 * @return AdmPubChannel[] PubChannel data objects as stored in DB (read after creation).
	 */
	public static function createPubChannelsObj( $pubId, $pubChannels, $typeMap )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$createdPubChannels = array();
	
		foreach( $pubChannels as $pubChannel ) {
			
			// Error on duplicates
			$where = '`name` = ? AND `publicationid` = ?';
			$params = array( strval($pubChannel->Name), intval($pubId) );
			$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			// Auto set the Description when not provided.
			if( is_null( $pubChannel->Description ) ) {
				$pubChannel->Description = '';
			}
			
			// Store standard pub channel properties in DB.
			$pubChannelRow = self::objToRow( $pubChannel );
			$pubChannelRow['publicationid'] = intval($pubId);
			$pubChannelId = self::insertRow( self::TABLENAME, $pubChannelRow );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}

			if( $pubChannelId ) {
				// Store custom publication properties in DB.
				DBChanneldata::saveCustomProperties( 'PubChannel', $pubChannelId, $pubChannel->ExtraMetaData, $typeMap );
	
				// Retrieve whole publication from DB. This is to make sure that the caller
				// gets exactly the same data after a 'create' as after a 'get' operation.
				$createdPubChannel = self::getPubChannelObj( $pubChannelId, $typeMap );
				$createdPubChannels[] = $createdPubChannel;
			}	
		}
		return $createdPubChannels;
	}
	
	/**
	 * Retrieves PubChannel data objects from the smart_channels DB table
	 * that belong to a given brand ($pubId).
	 *
	 * @since 6.0
	 * @since 9.0: Added $pubId and $typeMap parameters.
	 * @param integer $pubId Brand ID. NULL to get all channels (system wide)
	 * @param array|null $typeMap Lookup table with custom property names as keys and types as values. Pass NULL to skip resolving props (which leaves ExtraMetaData set to null).
	 * @return AdmPubChannel[]
	 * @throws BizException on SQL error.
	 */
	public static function listPubChannelsObj( $pubId = null, $typeMap = null )
	{
		$params = array();
		$where = '';
		if( !is_null( $pubId ) ) {
			$where .= '`publicationid` = ? ';
			$params[] = intval($pubId);
		}
		$orderBy = array( 'code' => true ); // ORDER BY `code` ASC
		$rows = self::listRows( self::TABLENAME, 'id', null, $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$pubChannels = array();
		if( $rows ) foreach( $rows as $row ) {
			$pubChannel = self::rowToObj( $row );
			if( is_null( $typeMap ) ) {
				$pubChannel->ExtraMetaData = null;
			} else {
				require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
				$pubChannel->ExtraMetaData = DBChanneldata::getCustomProperties( 'PubChannel', $row['id'], $typeMap );
			}
			$pubChannels[] = $pubChannel;
		}
		return $pubChannels;
	}
	
	/**
	 * Retrieves PubChannel data object from the smart_channels DB table
	 * specified by its ID ($pubChannelId).
	 *
	 * @since 6.0
	 * @param integer $pubChannelId Brand ID
	 * @param array|null $typeMap Lookup table with custom property names as keys and types as values. Pass NULL to skip resolving props (which leaves ExtraMetaData set to null).
	 * @return AdmPubChannel PubChannel data object as stored in DB (read after creation).
	 */
	public static function getPubChannelObj( $pubChannelId, $typeMap = null )
	{
		$where = '`id` = ?';
		$params = array( intval( $pubChannelId ) );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( !$row ) {
			return null;
		}
		$pubChannel = self::rowToObj( $row );

		if( is_null( $typeMap ) ) {
			$pubChannel->ExtraMetaData = null;
		} else {
			require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
			$pubChannel->ExtraMetaData = DBChanneldata::getCustomProperties( 'PubChannel', $pubChannelId, $typeMap );
		}
		return $pubChannel;
	}
	
	/**
	 * Updates given PubChannel data objects in the smart_channels DB table
	 * that belong to a given brand ID ($pubId).
	 *
	 * @since 6.0
	 * @param integer $pubId Brand ID
	 * @param AdmPubChannel[] $pubChannels List of PubChannel data objects to update
	 * @param array $typeMap Lookup table with custom property names as keys and types as values.
	 * @throws BizException Throws BizException when error occurred during update of pub channels object.
	 * @return array List of PubChannel data objects as stored in DB (read after update).
	 */
	public static function modifyPubChannelsObj( $pubId, $pubChannels, $typeMap )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$modifiedPubChannels = array();

		foreach( $pubChannels as $pubChannel ) {

			// Error on duplicates.
			$where = '`name` = ? AND `publicationid` = ? AND `id` != ?';
			$params = array( $pubChannel->Name, intval( $pubId ), intval( $pubChannel->Id ) );
			$row = self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			// Store custom pub channel properties in DB.
			DBChanneldata::saveCustomProperties( 'PubChannel', $pubChannel->Id, $pubChannel->ExtraMetaData, $typeMap );

			// Store standard pub channel properties in DB.
			$row = self::objToRow( $pubChannel );
			$result = self::updateRow( self::TABLENAME, $row, '`id` = ?', array( intval( $pubChannel->Id ) ) );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}

			// Retrieve whole publication from DB. This is to make sure that the caller
			// gets exactly the same data after a 'save' as after a 'get' operation.
			if( $result === true ) {
				$modifiedPubChannels[] = self::getPubChannelObj( $pubChannel->Id, $typeMap );
			}
		}
		return $modifiedPubChannels;
	}

	/**
	 * Update the 'suggestionprovider' value with the new value $newSuggestionProvider in smart_channels table.
	 *
	 * This is typically called when the server plugin is un-registered (plugin being removed from the plugin folder).
	 *
	 * @param string $suggestionProvider The 'suggestionprovider' that has this value will be replaced.
	 * @param string $newSuggestionProvider The new value to be inserted in 'suggestionprovider' field.
	 * @throws BizException on SQL error.
	 */
	public static function modifyPubChannelsSuggestionProvider( $suggestionProvider, $newSuggestionProvider='' )
	{
		$row = array( 'suggestionprovider' => strval( $newSuggestionProvider ) );
		$where = '`suggestionprovider` = ? ';
		$params = array( strval($suggestionProvider) );
		self::updateRow( self::TABLENAME, $row, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Returns the publish system id of the requested channel.
	 *
	 * @param int $channelId The publication channel id of which its publishsystemid will be retrieved.
	 * @return null|string The publish system id of the requested channel, null when no record of the requested channel is found.
	 * @throws BizException on SQL error.
	 */
	public static function getPublishSystemIdForChannel( $channelId )
	{
		$where = '`id` = ? ';
		$params = array( intval( $channelId ) );
		$row = self::getRow( self::TABLENAME, $where, array( 'publishsystemid' ), $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return isset( $row['publishsystemid'] ) ? $row['publishsystemid'] : null;
	}

	/**
	 * Update publishsystemid field in smart_channels table.
	 *
	 * @param int $pubChannelId The publication channel id of which its publishsystemid will be updated.
	 * @param string $publishSystemId The publishsystemid value to be saved.
	 * @throws BizException on SQL error.
	 */
	public static function savePublishSystemIdForChannel( $pubChannelId, $publishSystemId )
	{
		$row = array( 'publishsystemid' => strval( $publishSystemId ) );
		$where = '`id` = ? ';
		$params = array( intval( $pubChannelId ) );
		self::updateRow( self::TABLENAME, $row, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 *  Converts a pubchannel DB row into object.
	 *
	 *  @param array $row PubChannel DB row
	 *  @return AdmPubChannel
	 */
	static private function rowToObj ( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$pubChannel = new AdmPubChannel();
		$pubChannel->Id = intval( $row['id'] );
		$pubChannel->Name = $row['name'];
		$pubChannel->Type = $row['type'];
		$pubChannel->Description = $row['description'];
		$pubChannel->SortOrder = intval( $row['code'] );
		$pubChannel->PublishSystem = $row['publishsystem'];
		$pubChannel->PublishSystemId = $row['publishsystemid'];
		$pubChannel->SuggestionProvider = $row['suggestionprovider'];
		$pubChannel->CurrentIssueId = intval( $row['currentissueid'] );

		return $pubChannel;
	}

	/**
	 *  Converts a pubchannel object into DB row.
	 *
	 *  @param AdmPubChannel $obj PubChannel object
	 *  @return array PubChannel DB row
	 */
	static private function objToRow ( $obj )
	{
		$row = array();

		if( !is_null( $obj->Id ) ) {
			$row['id'] = intval( $obj->Id );
		}
		if( !is_null( $obj->Name ) ) {
			$row['name'] = strval( $obj->Name );
		}
		$row['type'] = ( !empty( $obj->Type ) ) ? strval( $obj->Type ) : 'print';
		$row['description'] = ( !empty( $obj->Description ) ) ? strval( $obj->Description ) : '';
		$row['code'] = ( !empty( $obj->SortOrder ) ) ? intval( $obj->SortOrder ) : 0;

		if( !is_null( $obj->PublishSystem ) ) {
			$row['publishsystem'] = strval( $obj->PublishSystem );
		}

		if( !is_null( $obj->PublishSystemId ) ) {
			$row['publishsystemid'] = intval( $obj->PublishSystemId );
		}

		if( !is_null( $obj->SuggestionProvider ) ) {
			$row['suggestionprovider'] = strval( $obj->SuggestionProvider );
		}

		if( !is_null( $obj->CurrentIssueId ) ) {
			$row['currentissueid'] = intval( $obj->CurrentIssueId );
		}

		return $row;
	}
}
