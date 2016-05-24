<?php

/**
 * @package     SCEnterprise
 * @subpackage  DBClasses
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBChannel extends DBBase
{
	const TABLENAME = 'channels';
	
    public static function createChannel( $pubId, $values )
    {
        $dbDriver = DBDriverFactory::gen();
		$row = self::getRow(self::TABLENAME, "`publicationid` = '$pubId' AND `name` = '" . $dbDriver->toDBString($values['name']) . "' ");
		if ($row) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null);
		}

        $values['publicationid'] = $pubId;
        $channelId = self::insertRow( self::TABLENAME, $values );
        
        $pubvalues = array(  );
        $pubvalues['defaultchannelid'] = $channelId;
        self::updateRow( 'publications', $pubvalues, " `id` = $pubId AND `defaultchannelid` = 0 " );

        return $channelId;
    }

    public static function listChannels( $pubId )
    {
        $rows = self::listRows( self::TABLENAME,'id','name',"`publicationid` = $pubId ORDER BY `code` ASC " );
        return $rows;
    }

    /**
     * OBSOLETE function, please use getPubChannelObj instead
     *
     * @param integer $channelId
     * @return array PubChannel DB row
     */
	public static function getChannel( $channelId )
	{
		$row = self::getRow( self::TABLENAME, "`id` = $channelId" );
		return $row;
	}

	/**
	 * Returns list of supported publication channel type.
	 * - other
	 * - print
	 * - web
	 * - sms
	 * - dps
	 * - dps2 (Adobe DPS 2015)
	 *
	 * @return string[]
	 */
	public static function listChannelTypes()
	{
		return array( 0 => 'other', 1 => 'print', 2 => 'web', 3 => 'sms', 6 => 'dps', 7 => 'dps2' );
	}

	public static function updateChannel( $channelId, $values )
	{
        $dbDriver = DBDriverFactory::gen();
        $pubId = self::getPublicationId( $channelId ); // unique within same publication (BZ#8106/BZ#8448)
		$row = self::getRow(self::TABLENAME, "`name` = '" . $dbDriver->toDBString($values['name']) . "' AND `publicationid` = $pubId AND `id` != $channelId ");
		if ($row) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null);
		}

		return self::updateRow( self::TABLENAME,$values,"`id` = $channelId" );
	}

	public static function listPrevCurrentNextIssues( $channelId )
	{
		$channelrow = self::getChannel( $channelId );
		//$currentid = $channelrow['currentissueid'];
		
		// get previous and next from issue list:
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$issues = DBIssue::listChannelIssues( $channelId );
		$previous	= null;
		$current	= null;
		$next 		= null;
		foreach ( $issues as $issueid => $issue ) {
			if ( $issueid == $channelrow['currentissueid'] ) {
				$current = $issue;
			} else {
				// This is not the current issue
				// If we already have current issue this is the next
				// otherwise we remember what might be the previous
				if(  $current  ) {
					$next = $issue;
					break;  // stop the loop, we've got all we need
				} else {
					$previous = $issue;
				}
			}
		}

		$result = array(  );
		if( $current ) { // there are only prev/next when there is a current BZ#8061)
			$result['current'] = $current;
			if(  $previous  ) $result['prev'] = $previous;
			if(  $next  ) $result['next'] = $next;
		}

		return $result;
	}

	/**
	  * Looks up the publication id at smart_channels table.
	  *
	  * @param $channelId int
	  * @return string Publication id. Returns null on failure.
	  */
	static public function getPublicationId( $channelId )
	{
		$dbdriver = DBDriverFactory::gen();
		$channelsTable = $dbdriver->tablename(self::TABLENAME);
		$sql = "SELECT `publicationid` FROM $channelsTable WHERE `id` = $channelId";
		$sth = $dbdriver->query( $sql );
		if( !$sth ) return null;
		$row = $dbdriver->fetch( $sth );
		if( !$row ) return null;
		return $row['publicationid'];
	}

	/**
	 * Get PubChannelInfo object
	 *
	 * @param integer $pubChannelId
	 * @return object PubChannelInfo object
	 * @since v7.0.13
	 */
	public static function getPubChannelObj( $pubChannelId )
	{
		$row = self::getRow( self::TABLENAME, "`id` = $pubChannelId" );
		if (!$row) return null;
		return self::rowToObj($row);
	}

	/**
	 * Checks if a brand contains a channel for a certain publish system.
	 * @param integer $pubId	Brand (publication) id.
	 * @param string  $pubSystem Publish System (e.g. Drupal)
	 * @return boolean found (true/false).
	 * @since v7.5.0
	 */
	public static function checkPubChannelbyPublishSystem( $pubId, $pubSystem )
	{
		$where = "`publicationid` = ? and `publishsystem` = ? ";
		$params = array( $pubId, $pubSystem );
		$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
		
		return $row ? true : false;
	}	

	/**
	 * Returns all channels of a certain Publish System.
	 * @param string $pubSystem Publish System (e.g. Drupal)
	 * @return array of PubChannelInfo
	 * @since v7.5.0
	 */
	public static function getChannelsByPublishSystem( $pubSystem )
	{
		$where = "`publishsystem` = ? ";
		$params = array( $pubSystem );
		$rows = self::listRows(self::TABLENAME, NULL, NULL, $where, '*', $params);
		
		$result = array();
		if ( $rows ) foreach ( $rows as $row ) {
			$result[] = self::rowToObj( $row );
		}
			
		return $result;
	}
	
	/**
	 * Returns the Publish System given the channel Id.
	 * When there are more than one publish system found for the specific channel Id,
	 * the first record found will be returned.
	 * @param integer $channelId Publication Channel Id.
	 * @return string|Null Name of the Publish System | Null when no record found.
	 */
	public static function getPublishSystemByChannelId( $channelId )
	{
		$where = '`id` = ? ';
		$params = array( $channelId );			
		$row = self::getRow( self::TABLENAME, $where, array('publishsystem'), $params );
		return isset( $row['publishsystem'] ) ? $row['publishsystem'] : null;
	}

	/**
	 * Converts a pubchannel DB row into pubChannelInfo object.
	 *
	 * @param array $row PubChannel DB row
	 * @return object PubChannelInfo object
	 * @since v7.0.13 
	 */
	static private function rowToObj ( $row )
	{
		$pubChannelInfo                = new PubChannelInfo();
		$pubChannelInfo->Id            = $row['id'];
		$pubChannelInfo->Name          = $row['name'];
		$pubChannelInfo->Type          = $row['type'];
		$pubChannelInfo->Description   = $row['description'];
		$pubChannelInfo->PublishSystem = $row['publishsystem'];
		$pubChannelInfo->PublicationId = $row['publicationid'];
		$pubChannelInfo->CurrentIssueId= $row['currentissueid'];

		return $pubChannelInfo;
	}

	/**
	 * Returns the Suggestion provider of the PubChannel requested.
	 *
	 * @param int $channelId To retrieve the suggestion provider this channel belongs to.
	 * @return string|null The publication channel's Suggestion provider; Null when the PubChannel has no Suggestion provider set.
	 */
	public static function getSuggestionProviderByChannelId( $channelId )
	{
		$where = '`id` = ? ';
		$fieldNames = array( 'suggestionprovider' );
		$params = array( $channelId );
		$row = self::getRow( self::TABLENAME, $where, $fieldNames, $params );
		return $row['suggestionprovider'] ? $row['suggestionprovider'] : null;
	}
}