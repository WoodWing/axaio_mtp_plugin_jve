<?php
/**
 * Manages messages (sent to objects and users) at database.
 *
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * This class stores, updates or removes properties of messages. While doing so, it transforms 
 * the Message data structure into database rows (for smart_messagelog table) and back again.
 *
 * This class prevents hack attacks related to the datatabase, such as SQL injection.
 *
 * This class is responsible for data sorting. For messages, this is quite complicated since 
 * messages are bundled by thread wherein replies on replies can be given. This should be seen
 * as a data tree with the thread owner on top, which could be a sticky note. Then, all the replies
 * on the sticky are listed, but in between there can be replies on replies. Then, the second
 * sticky is given, etc etc. In other terms, the tree is flattened in such way that when parsing the 
 * flat list, you'll never bump into message ids (threadid, reply-to id) that are unknown; They always
 * refer back to other messages that are higher up in the list.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
 
class DBMessage extends DBBase
{
	const TABLENAME = 'messagelog';	

	/**
	 * Tells whether or not there is a Message stored in the database for a given message id.
	 *
	 * @param string $messageId Message ID (not record id)
	 * @return bool Whether or not message exists.
	 */
	static public function doesMessageExist( $messageId )
	{
		if( $messageId ) {
			$where = '`msgid`= ?';
			$params = array( $messageId );
			$retVal = (bool)self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
		} else {
			LogHandler::Log( 'DBMessage', 'WARN', 'No message id specified.' );
			$retVal = false;
		}
		return $retVal;
	}
	
	/**
	 * Returns the message type for a given message id.
	 *
	 * @param string $messageId Message ID (not record id)
	 * @return string|null Message type. Null when message not found.
	 */
	static public function getMessageType( $messageId )
	{
		if( $messageId ) {
			$where = '`msgid`= ?';
			$params = array( $messageId );
			$fields = array( 'id', 'messagetype' );
			$row = self::getRow( self::TABLENAME, $where, $fields, $params );
			if( !$row ) {
				LogHandler::Log( 'DBMessage', 'WARN', 'The message id could not be found: '.$messageId );
			}
		} else {
			LogHandler::Log( 'DBMessage', 'WARN', 'No message id specified.' );
			$row = null;
		}
		return $row ? $row['messagetype'] : null;
	}	
	
	/**
	 * Creates or updates a given Message in the database.
	 *
	 * @param Message $message
	 * @return boolean|string Message Id on success or false on failure.
	 */
	static public function saveMessage( Message $message ) 
	{
		if( $message->MessageType == 'system' ) {
			// Avoid duplicate system messages. Especially usefull for planning systems generating
			// same message again and again.
			$where = '`messagetype` = ? AND `messagetypedetail` = ? AND `message` LIKE #BLOB# ';
			$params = array( $message->MessageType, $message->MessageTypeDetail );
			if( $message->UserID ) {
				require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
				$userDbId = intval( DBUser::getUserDbIdByShortName( $message->UserID ) );
				$where .= 'AND `userid` = ? ';
				$params[] = $userDbId;
			}
			if( $message->ObjectID ) {
				$where .= 'AND `objid` = ? ';
				$params[] = $message->ObjectID;
			}
			$orderBy = null;
			$fields = array( 'id', 'msgid' );
			$row = self::getRow( self::TABLENAME, $where, $fields, $params, $orderBy, $message->Message );
			if( isset($row['msgid']) ) {
				return $row['msgid']; // return existing msgid and skip insertion
			} // else, the insert is done below.
		} else if( $message->MessageID ) {
			// Check if message exists, if so we need to do an update instead of insert:
			$where = '`msgid`= ?';
			$params = array( $message->MessageID );
			$row = self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
			if( $row ) {
				// We need to do an update instead of insert:
				$row = self::objToRow( $message );
				$where = '`msgid`= ?';
				$params = array( $message->MessageID );
				if( array_key_exists( 'message', $row ) ) {
					$row['message'] = '#BLOB#';
				}
				self::updateRow( self::TABLENAME, $row, $where, $params, $message->Message );
				return $message->MessageID;
			} // else, the insert is done below.
		}

		// msgid can be left empty by client in which case we put db ID into msgid.
		// We duplicate id this way which prevents the rest from the code to deal with both id and msgid
		if( !$message->MessageID ) {
			require_once BASEDIR.'/server/utils/NumberUtils.class.php';
			$message->MessageID = NumberUtils::createGUID();
		}
		
		$row = self::objToRow( $message );
		if( array_key_exists( 'message', $row ) ) {
			$row['message'] = '#BLOB#';
		}
		$insertId = self::insertRow( self::TABLENAME, $row, true, $message->Message );
		if( $insertId === false ) {
			return false; // insert failed
		}
		$message->Id = $insertId;
		return $message->MessageID;
	}

	/** 
	 * Flag the message as already read.
	 * @param Message $message Message to be marked as read.
	 * @return Boolean True when successfully marked as read, False otherwise.
	 */
	static public function markMessageAsRead( $message )
	{
		$where = '`msgid`= ?';
		$params = array( $message->MessageID );
		$row = self::objToRow( $message );
		return self::updateRow( self::TABLENAME, $row, $where, $params );
	}
	
	/**
	 * Returns messages for a given object.
	 * Messages are sorted in human reading order. See module header for details.
	 *
	 * @param string $objectId
	 * @return array of Message
	 */
	static public function getMessagesForObject( $objectId )
	{
		$idxMessages = array();
		$where = '`objid` = ?';
		$params = array( $objectId );
		$orderBy = array( 'id' => true );
		$rows = self::listRows( self::TABLENAME, 'id', null, $where, '*', $params, $orderBy );
		if( $rows ) foreach( $rows as $row ) {
			$idxMessages[ $row['msgid'] ] = self::rowToObj( $row );
		}
		$messages = self::sortMessages( $idxMessages );
		return $messages;
	}
	
	/**
	 * Returns the number of unread messages for the specified object.
	 *
	 * @param string $objectId
	 * @return mixed Number of unread messages or null in case of error. 
	 */
	static public function getUnReadMessageCountForObject( $objectId )
	{
		$dbDriver = DBDriverFactory::gen();
		$messagelog = $dbDriver->tablename(self::TABLENAME);
		
		$sql  = "SELECT COUNT(1) as total ";
		$sql .= "FROM $messagelog ";
		$sql .= "WHERE `objid` = ? AND `isread` != ? ";
		$params = array( $objectId, 'on' );
		
		$sth = $dbDriver->query( $sql, $params );
		if( is_null($sth) ) {
			return null;	
		}		

		$row = $dbDriver->fetch($sth);
		return $row ? intval( $row['total'] ) : 0;
	}

	/**
	 * Returns the number of unread messages for the specified objects.
	 * Same as getUnReadMessageCountForObject() but then for multiple objects.
	 * 
	 * @param integer[] $objectIds
	 * @return array key/value pairs of object id and unread messages.    
	 */
	static public function getUnReadMessageCountForObjects( $objectIds )
	{
		$dbDriver = DBDriverFactory::gen();
		$messagelog = $dbDriver->tablename( self::TABLENAME );
		
		$sql  = 'SELECT `objid`, COUNT(1) as `total` ';
		$sql .= "FROM $messagelog ";
		$sql .= 'WHERE ' . self::addIntArrayToWhereClause( 'objid', $objectIds, false ) .' AND `isread` != ? ';
		$sql .= 'GROUP BY `objid`';
		$params = array( 'on' );
		
		$sth = $dbDriver->query( $sql, $params );

		$rows = self::fetchResults( $sth, 'objid' );

		return $rows;
	}
	
	/**
	 * Returns the number of unread messages for objects contained by the temporary
	 * table $view.
	 *
	 * @param string $view Temporary view to join on.
	 * @return array Number of unread messages for each object in the view. 
	 */
	static public function getUnReadMessageCountForView( $view )
	{
		$dbDriver = DBDriverFactory::gen();
		$messagelog = $dbDriver->tablename(self::TABLENAME);
		$tempids = self::getTempIds($view);
		
		$sql  = "SELECT ml.`objid` as id, COUNT(1) as total ";
		$sql .= "FROM $messagelog ml ";
		$sql .= "INNER JOIN $tempids ov ON (ov.`id` = ml.`objid`) ";
		$sql .= "WHERE ml.`isread` != ? ";
		$sql .= "GROUP BY ml.`objid` ";
		$params = array( 'on' );
		
		$sth = $dbDriver->query( $sql, $params );
		if( is_null($sth) ) {
			return null;	
		}		

		$rows = self::fetchResults($sth, 'id' );
		return $rows;
	}
	
	/**
	 * Returns messages sent to a given user.
	 * Messages are sorted in human reading order. See module header for details.
	 *
	 * @param string $userShortName Short user name.
	 * @return array of Message
	 */
	static public function getMessagesForUser( $userShortName )
	{
		$idxMessages = array();
		$tables = array( 'm' => self::TABLENAME, 'u' => 'users' );
		$where = 'u.`user` = ? AND ( m.`userid` = u.`id` ) ';
		$params = array( $userShortName );
		$fieldNames = array( 'm' => array( '*' ) );
		$orderBy = array( 'm.`id`' => true );
		$rows = self::listRows( $tables, null, null, $where, $fieldNames, $params, $orderBy );
		if( $rows ) foreach( $rows as $row ) {
			$idxMessages[ $row['msgid'] ] = self::rowToObj( $row );
		}
		$messages = self::sortMessages( $idxMessages );
		return $messages;
	}

	/**
	 * Return message for an array of objects.
	 *
	 * @param array $objectIds Array of object Ids
	 * @return array $messages Array of messages
	 */
	static public function getMessagesForObjects( $objectIds )
	{
		$messages = array();
		$objectIds = "'".implode( "', '", $objectIds )."'";
		$where = '`objid` IN ( '.$objectIds.' ) ';
		$rows = self::listRows( self::TABLENAME, 'id', null, $where, '*' );
		if( $rows ) foreach( $rows as $row ) {
			$messages[$row['objid']][] = self::rowToObj( $row );
		}
		return $messages;
	}

	/**
	 * Retrieves a message for a given message id.
	 *
	 * @param string $messageId
	 * @return Message|null The Message (when found) or NULL when not found.
	 */
	static public function getMessageByMsgId( $messageId )
	{
		if( $messageId ) {
			$where = '`msgid` = ?';
			$params = array( $messageId );
			$row = self::getRow( self::TABLENAME, $where, '*', $params );
			$message = $row ? self::rowToObj( $row ) : null;
		} else {
			LogHandler::Log( 'DBMessage', 'WARN', 'No message id specified.' );
			$message = null;
		}
		return $message;
	}

	/**
	 * Determines to which objects (ids) a given list of messages (ids) are sent.
	 *
	 * @param array List of message ids.
	 * @return array Map of ids: Key = message id, Value = object id
	 */
	static public function getObjectIdsForMsgIds( array $messageIds )
	{
		$idMap = array();
		if( $messageIds ) {
			$fieldNames = array( 'id', 'msgid', 'objid' );
			$messageIds = "'".implode( "', '", $messageIds )."'";
			$where = '`objid` <> 0 AND `msgid` IN ( '.$messageIds.' ) ';
			$rows = self::listRows( self::TABLENAME, 'id', null, $where, $fieldNames );
			if( $rows ) foreach( $rows as $row ) {
				$idMap[ $row['msgid'] ] = $row['objid'];
			}
		}
		return $idMap;
	}

	/**
	 * Determines to which users (ids) a given list of messages (ids) are sent.
	 *
	 * @param array List of message ids.
	 * @return array Map of ids: Key = message id, Value = user id
	 */
	static public function getUserIdsForMsgIds( array $messageIds )
	{
		$idMap = array();
		if( $messageIds ) {
			$messageIds = "'".implode( "', '", $messageIds )."'";
 			$tables = array( 'm' => self::TABLENAME, 'u' => 'users' );
			$fieldNames = array( 
				'm' => array( 'id', 'msgid', 'userid' ), 
				'u' => array( 'user' ) );
			$where = 'm.`userid` = u.`id` AND m.`msgid` IN ( '.$messageIds.' ) ';
			$rows = self::listRows( $tables, null, null, $where, $fieldNames );
			if( $rows ) foreach( $rows as $row ) {
				$idMap[ $row['msgid'] ] = $row['user'];
			}
		}
		return $idMap;
	}
	
	/**
	 * Removes a message from the database, based on given message id.
	 *
	 * @param string $messageId
	 * @return bool Whether or not the message could be deleted.
	 */
	static public function deleteMessage( $messageId )
	{
		if( $messageId ) {
			$where = '`msgid` = ?';
			$params = array( $messageId );
			$retVal = (bool)self::deleteRows( self::TABLENAME, $where, $params );
		} else {
			LogHandler::Log( 'DBMessage', 'WARN', 'No message id specified.' );
			$retVal = false;
		}
		return $retVal;
	}
	
	/**
	 * Retrieves message ids of all messages sent to a given object.
	 *
	 * @param string $objectId
	 * @param string $messageType       Optional. Only get messages with this type.
	 * @param string $messageTypeDetail Optional. Only get messages with this type detail.
	 * @return array Message ids.
	 */
	static public function getMessageIdsByObjId( $objectId, $messageType = null, $messageTypeDetail = null )
	{
		$messageIds = array();
		if( $objectId ) {
			$fieldNames = array( 'id', 'msgid' );
			$where = '( `objid` = ? ) ';
			$params = array( $objectId );
			if( $messageType ) {
				$where .= 'AND ( `messagetype` = ? ) ';
				$params[] = $messageType;
			}
			if( $messageTypeDetail ) {
				$where .= 'AND ( `messagetypedetail` = ? ) ';
				$params[] = $messageTypeDetail;
			}
			$rows = self::listRows( self::TABLENAME, null, null, $where, $fieldNames, $params );
			if( $rows ) foreach( $rows as $row ) {
				$messageIds[ $row['id'] ] = $row['msgid'];
			}
		} else {
			LogHandler::Log( 'DBMessage', 'WARN', 'No object id specified.' );
		}
		return $messageIds;
	}

	/**
	 * Retrieves message ids of all messages sent to a given user.
	 *
	 * @param string $userId
	 * @param string $messageType       Optional. Only get messages with this type.
	 * @param string $messageTypeDetail Optional. Only get messages with this type detail.
	 * @return array Message ids.
	 */
	static public function getMessageIdsByUserId( $userId, $messageType = null, $messageTypeDetail = null )
	{
		$messageIds = array();
		if( $userId ) {
			$tables = array( 'm' => self::TABLENAME, 'u' => 'users' );
			$fieldNames = array( 
				'm' => array( 'id', 'msgid', 'userid' ), 
				'u' => array( 'user' ) );
			$where = '( m.`userid` = u.`id` ) AND ( u.`id` = ? ) ';
			$params = array( $userId );
			if( $messageType ) {
				$where .= 'AND ( m.`messagetype` = ? ) ';
				$params[] = $messageType;
			}
			if( $messageTypeDetail ) {
				$where .= 'AND ( m.`messagetypedetail` = ? ) ';
				$params[] = $messageTypeDetail;
			}
			$rows = self::listRows( $tables, null, null, $where, $fieldNames, $params );
			if( $rows ) foreach( $rows as $row ) {
				$messageIds[ $row['id'] ] = $row['msgid'];
			}
		} else {
			LogHandler::Log( 'DBMessage', 'WARN', 'No user id specified.' );
		}
		return $messageIds;
	}
	
	/**
	 * Retrieves message ids of all replies (messages) that were given (by users) on a specific message.
	 *
	 * @param string $messageId Message ID to collect replies for.
	 * @return array Replied message ids (array with record ids at keys and message ids at values).
	 */
	static public function getReplyToMsgIdsForMessageId( $messageId )
	{
		$messageIds = array();
		if( $messageId ) {
			$fieldNames = array( 'id', 'msgid' );
			$where = '( `replytomessageid` = ? ) ';
			$params = array( $messageId );
			$rows = self::listRows( self::TABLENAME, null, null, $where, $fieldNames, $params );
			if( $rows ) foreach( $rows as $row ) {
				$messageIds[ $row['id'] ] = $row['msgid'];
			}
		} else {
			LogHandler::Log( 'DBMessage', 'WARN', 'No message id specified.' );
		}
		return $messageIds;
	}

	/**
	 * Retrieves message ids of all replies (messages) that were given (by users) on a specific 
	 * message thread owner. For example, a Sticky Note for which replies are given.
	 *
	 * @param string $threadMessageId Message ID to collect replies for.
	 * @return array Replied message ids (array with record ids at keys and message ids at values).
	 */
	static public function getMsgIdsForThreadMessageId( $threadMessageId )
	{
		$messageIds = array();
		if( $threadMessageId ) {
			$fieldNames = array( 'id', 'msgid' );
			$where = '( `threadmessageid` = ? ) ';
			$params = array( $threadMessageId );
			$rows = self::listRows( self::TABLENAME, null, null, $where, $fieldNames, $params );
			if( $rows ) foreach( $rows as $row ) {
				$messageIds[ $row['id'] ] = $row['msgid'];
			}
		} else {
			LogHandler::Log( 'DBMessage', 'WARN', 'No message id specified.' );
		}
		return $messageIds;
	}

	/**
	 * Sorts messages by thread id, reply id and message id. See module header for details.
	 * Infact, messages get sorted in human reading order. This is to ease client apps drawing
	 * a tree of messages. And, clients may assume all message ids being parsed are defined before.
	 * So when parsing the tree, the thread ids and reply-to ids refer to message ids parsed earlier.
	 *
	 * When this is the given order:
	 *   msg id - reply id
	 *        1 - 0
	 *        2 - 1
	 *        3 - 2
	 *        4 - 2
	 *        5 - 1
	 * It will get sorted as follows: 
	 *   msg id - reply id
	 *        1 - 0
	 *        2 - 1
	 *        5 - 1
	 *        3 - 2
	 *        4 - 2
	 * And, the messages are grouped by thread id.
	 *
	 * @param array $idxMessages List of Message objects, sorted by record id.
	 * @return array List of Message objects, sorted in reading order (prepared tree view).
	 */
	private static function sortMessages( $idxMessages )
	{
		$treeMessages = array();
		$messageThreadIds = array();
		foreach( $idxMessages as $idxMessage ) {
			$treeMessage = new stdClass();
			$treeMessage->MessageID = $idxMessage->MessageID;
			$treeMessage->Replies = array();
			if( $idxMessage->ReplyToMessageID ) {
				if( array_key_exists( $idxMessage->ReplyToMessageID, $treeMessages ) ) {
					$treeMessages[ $idxMessage->ReplyToMessageID ]->Replies[] = $treeMessage;
				} else {
					// TODO: error?
				}
			} else {
				$messageThreadIds[] = $idxMessage->MessageID;
			}
			$treeMessages[ $idxMessage->MessageID ] = $treeMessage;
		}
		$sortedMessages = array();
		foreach( $messageThreadIds as $messageThreadId ) {
			self::sortReplies( $idxMessages, $sortedMessages, $treeMessages[ $messageThreadId ] );
		}
		return $sortedMessages;
	}
	
	/**
	 * A message can be a reply on another message. Even a reply on reply. This function sorts
	 * those messages, as described in the sortMessages function.
	 *
	 * @param array $idxMessages List of message replies to sort. List is indexed by message id.
	 * @param array $sortedMessages List of sorted message replies. Read/write.
	 * @param Message $message The message for which replies needs to be sorted.
	 * @param integer $recursion Current recursion level (n-th ply). Max allowed is 25 ply deep.
	 */
	private static function sortReplies( $idxMessages, &$sortedMessages, $message, $recursion = 1 )
	{
		// Avoid endless recursion. More than 25 replies on replies is very unlikely to happen.
		if( $recursion > 25 ) {
			LogHandler::Log( 'DBMessage', 'ERROR', 'Exceeding maximum message reply depth of 25. '.
						'Message ('.$message->MessageID.') and all its replies are ignored.' );
			return;
		}
		
		// Add current message to sorted list, followed by its replies.
		// Use recursion to resolve replies on replies in the correct sorting order.
		$sortedMessages[] = $idxMessages[ $message->MessageID ];
		foreach( $message->Replies as $reply ) {
			self::sortReplies( $idxMessages, $sortedMessages, $reply, $recursion + 1 );
		}
	}
	
	/**
	 * Converts an a given Message object into a DB record.
	 *
	 * @param object $obj Message object
	 * @return array Message DB record
	 */
	static public function objToRow( $obj )
	{
		$row = array();

		if( isset( $obj->Id ) ) { // internal prop
			$row['id'] = intval( $obj->Id );
		}
		if( !is_null( $obj->ObjectID ) ) {
			$row['objid'] = intval( $obj->ObjectID );
		}
		if( !is_null( $obj->UserID ) ) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$userDbId = DBUser::getUserDbIdByShortName( $obj->UserID );
			if( $userDbId ) {
				$row['userid'] = $userDbId;
			}
		}
		if( !is_null( $obj->MessageID ) ) {
			$row['msgid'] = $obj->MessageID; // string
		}
		if( !is_null( $obj->MessageType ) ) {
			$row['messagetype'] = $obj->MessageType;
		}
		if( !is_null( $obj->MessageTypeDetail ) ) {
			$row['messagetypedetail'] = $obj->MessageTypeDetail;
		}
		if( !is_null( $obj->Message ) ) {
			$row['message'] = $obj->Message;
		}
		if( !is_null( $obj->TimeStamp ) ) {
			$row['date'] = $obj->TimeStamp;
		}
		if( !is_null( $obj->Expiration ) ) {
			$row['expirationdate'] = $obj->Expiration;
		}
		if( !is_null( $obj->MessageLevel ) ) {
			$row['messagelevel'] = $obj->MessageLevel;
		}
		if( !is_null( $obj->FromUser ) ) {
			$row['fromuser'] = $obj->FromUser;
		}

		// Introduced since 8.0...
		if( !is_null( $obj->ThreadMessageID ) ) {
			$row['threadmessageid'] = $obj->ThreadMessageID;
		}
		if( !is_null( $obj->ReplyToMessageID ) ) {
			$row['replytomessageid'] = $obj->ReplyToMessageID;
		}
		if( !is_null( $obj->MessageStatus ) ) {
			$row['messagestatus'] = $obj->MessageStatus;
		}
		self::splitMajorMinorVer( $obj->ObjectVersion, $row, '' );
		$row['isread'] = isset( $obj->IsRead ) ? ( $obj->IsRead === true ? 'on' : '' ) : '';

		// Specific info for Sticky Notes...
		if( isset( $obj->StickyInfo ) ) { // isset also check for null values
			if( !is_null( $obj->StickyInfo->AnchorX ) ) {
				$row['anchorx'] = floatval( $obj->StickyInfo->AnchorX );
			}
			if( !is_null( $obj->StickyInfo->AnchorY ) ) {
				$row['anchory'] = floatval( $obj->StickyInfo->AnchorY );
			}
			if( !is_null( $obj->StickyInfo->Left ) ) {
				$row['left'] = floatval( $obj->StickyInfo->Left );
			}
			if( !is_null( $obj->StickyInfo->Top ) ) {
				$row['top'] = floatval( $obj->StickyInfo->Top );
			}
			if( !is_null( $obj->StickyInfo->Width ) ) {
				$row['width'] = floatval( $obj->StickyInfo->Width );
			}
			if( !is_null( $obj->StickyInfo->Height ) ) {
				$row['height'] = floatval( $obj->StickyInfo->Height );
			}
			if( !is_null( $obj->StickyInfo->Page ) ) {
				$row['page'] = intval( $obj->StickyInfo->Page );
			}
			if( !is_null( $obj->StickyInfo->Version ) ) {
				$row['version'] = $obj->StickyInfo->Version;
			}
			if( !is_null( $obj->StickyInfo->Color ) ) {
				$row['color'] = $obj->StickyInfo->Color;
			}
			if( !is_null( $obj->StickyInfo->PageSequence ) ) {
				$row['pagesequence'] = intval( $obj->StickyInfo->PageSequence );
			}
		}
		return $row;
	}

	/**
	 * Converts a message DB record into a Message object.
	 * 
	 * @param array $row   Database record with key-values.
	 * @return Message
	 */
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		$obj = new Message();
		if( array_key_exists( 'id', $row ) ) { // internal prop
			$obj->Id = $row['id'];
		}
		if( array_key_exists( 'objid', $row ) ) {
			$obj->ObjectID = $row['objid'];
		}
		if( !$obj->ObjectID ) {
			$obj->ObjectID = null;
		}
		if( array_key_exists( 'userid', $row ) ) {
			if( $row['userid'] ) {
				require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
				$obj->UserID = DBUser::getShortNameByUserDbId( $row['userid'] );
			}
		}
		if( !$obj->UserID ) {
			$obj->UserID = null;
		}
		if( array_key_exists( 'msgid', $row ) ) {
			$obj->MessageID = $row['msgid'];
		}
		if( array_key_exists( 'messagetype', $row ) ) {
			$obj->MessageType = $row['messagetype'];
		}
		if( array_key_exists( 'messagetypedetail', $row ) ) {
			$obj->MessageTypeDetail = $row['messagetypedetail'];
		}
		if( array_key_exists( 'message', $row ) ) {
			$obj->Message = $row['message'];
		}
		if( array_key_exists( 'date', $row ) ) {
			if( empty( $row['date'] ) ) { // TimeStamp must not be an empty string but null is allowed.
				$obj->TimeStamp = null;
			} else {
				$obj->TimeStamp = $row['date'];
			}
		}
		if( array_key_exists( 'expirationdate', $row ) && $row['expirationdate']  ) {
			$obj->Expiration = $row['expirationdate'];
		}		
		if( array_key_exists( 'messagelevel', $row ) ) {
			$obj->MessageLevel = $row['messagelevel'];
		}
		if( array_key_exists( 'fromuser', $row ) ) {
			$obj->FromUser = $row['fromuser'];
		}
		
		// Introduced since 8.0...
		if( array_key_exists( 'threadmessageid', $row ) ) {
			$obj->ThreadMessageID = $row['threadmessageid'];
		}
		if( array_key_exists( 'replytomessageid', $row ) ) {
			$obj->ReplyToMessageID = $row['replytomessageid'];
		}
		if( array_key_exists( 'messagestatus', $row ) ) {
			$obj->MessageStatus = $row['messagestatus'];
		}
		self::joinMajorMinorVer( $obj->ObjectVersion, $row, '' );
		if( array_key_exists( 'isread', $row ) ) { // internal prop
			$obj->IsRead = ($row['isread'] == 'on') ? true : false;
		}
		
		// Specific info for Sticky Notes...
		$obj->StickyInfo = null;
		if( array_key_exists( 'anchorx', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->AnchorX = $row['anchorx'];
		}
		if( array_key_exists( 'anchory', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->AnchorY = $row['anchory'];
		}
		if( array_key_exists( 'left', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->Left = $row['left'];
		}
		if( array_key_exists( 'top', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->Top = $row['top'];
		}
		if( array_key_exists( 'width', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->Width = $row['width'];
		}
		if( array_key_exists( 'height', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->Height = $row['height'];
		}
		if( array_key_exists( 'page', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->Page = $row['page'];
		}
		if( array_key_exists( 'version', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->Version = $row['version'];
		}
		if( array_key_exists( 'color', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->Color = $row['color'];
		}
		if( array_key_exists( 'pagesequence', $row ) ) {
			if( !$obj->StickyInfo ) $obj->StickyInfo = new StickyInfo();
			$obj->StickyInfo->PageSequence = $row['pagesequence'];
		}
		if( !$row['left'] && !$row['top'] && !$row['pagesequence'] ) {
			$obj->StickyInfo = null; // When three elements above are not available, stickyInfo is considered to be not exists.
		}
		return $obj;
	}	
}
