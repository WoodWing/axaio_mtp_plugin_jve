<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Implements business logics of messages sent to users or objects. Doing so, the DBMessage class
 * is called to store, update or remove the message's properties. And, the BizMessage is responsible
 * to n-cast messages, to directly update client applications.
 *
 * It uses the DBSemaphore to make all message operations atomic, to avoid all kind of race conditions
 * whereby data could get corrupted. For example: two users updating sticky notes (and their replies) 
 * for the same layout. One user could do through CS Pub Overview (SendMessages) and the other user 
 * could do through SC for InDesign (SaveObjects) at the very same time.
 *
 * IMPORATANT: The DBMessage class should  NOT be called directly by other PHP classes; Always call this
 *             BizMessage class instead. Or else, the n-casts are bypassed and client apps won't get updated.
 *             And, there would be danger for race conditions / data corruption, as mentioned above.
 *
 * The BizMessage class is restructured since v8.0 to embrace the Annotations feature.
 */

class BizMessage
{
	/**
	 * Get all messages sent to a specific object.
	 *
	 * @param string $objectId
	 * @throws BizException Throws BizException when fails.
	 * @return MessageList|Null
	 */
	public static function getMessagesForObject( $objectId )
	{
		$report = BizErrorReport::startReport();
		// Use semaphore to avoid race conditions / data corruption.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSema = new BizSemaphore();
		$semaName = 'BizMessage_obj_'.$objectId;
		$semaId = $bizSema->createSemaphore( $semaName );
		if( !$semaId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$details = 'Operation "'.__METHOD__ .'" could not be completed because user "'. $otherUser . 
						'" is performing the same operation on the same objects or users.';
			throw new BizException( 'ERR_GET_MESSAGES', 'Server', $details );
		}

		// Get messages from DB.
		try {
			$report->Type = 'Object';
			$report->ID = $objectId;
			require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';		
			$messages = DBMessage::getMessagesForObject( $objectId );
		} catch( BizException $e ) {
			if( $semaId ) {
				$bizSema->releaseSemaphore( $semaId );
			}
			BizErrorReport::reportException( $e );
			BizErrorReport::stopReport();
			return null;
		}
		
		// DB operations are complete, so time to release the semaphore.
		if( $semaId ) {
			$bizSema->releaseSemaphore( $semaId );
		}
		
		$readMessageIDs = array();
		if( $messages ) foreach( $messages as $message ) {
			// Collect read messages.
			if( $message->IsRead ) {
				$readMessageIDs[] = $message->MessageID;
			}

			// Leave out the # infront of the color code.			
			if( isset( $message->StickyInfo->Color ) && $message->StickyInfo->Color ) {
				$message->StickyInfo->Color = substr( $message->StickyInfo->Color, 1 );
			}
		}
		
		// Return MessageList to caller.
		$messageList = new MessageList();
		$messageList->Messages = $messages;
		$messageList->ReadMessageIDs = $readMessageIDs;
		
		BizErrorReport::stopReport();		
		return $messageList;
	}
	
	/**
	 * Get all messages sent to a specific user.
	 *
	 * @param integer $userId User DB id.
	 * @param string $userShortName
	 * @throws BizException Throws BizException when fails.
	 * @return MessageList
	 */
	public static function getMessagesForUser( $userId, $userShortName )
	{
		// Use semaphore to avoid race conditions / data corruption.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSema = new BizSemaphore();
		$semaName = 'BizMessage_usr_'.$userId;
		$semaId = $bizSema->createSemaphore( $semaName );
		if( !$semaId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$details = 'Operation "'.__METHOD__ .'" could not be completed because user "'. $otherUser . 
						'" is performing the same operation on the same objects or users.';
			throw new BizException( 'ERR_GET_MESSAGES', 'Server', $details );
		}

		// Get messages from DB.
		try {
			require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
			$messages = DBMessage::getMessagesForUser( $userShortName );
		} catch( BizException $e ) {
			if( $semaId ) {
				$bizSema->releaseSemaphore( $semaId );
			}
			throw $e;
		}

		// DB operations are complete, so time to release the semaphore.
		if( $semaId ) {
			$bizSema->releaseSemaphore( $semaId );
		}

		$readMessageIDs = array();
		if( $messages ) foreach( $messages as $message ) {
			// Collect read messages.
			if( $message->IsRead ) {
				$readMessageIDs[] = $message->MessageID;
			}
		
			// Leave out the # infront of the color code.
			if( isset( $message->StickyInfo->Color ) && $message->StickyInfo->Color ) {
				$message->StickyInfo->Color = substr( $message->StickyInfo->Color, 1 );
			}
		}
		
		// Return MessageList to caller.
		$messageList = new MessageList();
		$messageList->Messages = $messages;
		$messageList->ReadMessageIDs = $readMessageIDs;
		return $messageList;
	}

	/**
	 * @param array $objectIds Array of layout id
	 * @return array $allMessageList
	 */
	public static function getMessagesForObjects( $objectIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
		$allMessages = DBMessage::getMessagesForObjects( $objectIds );
		$allMessageList = array();
		if( $allMessages ) foreach( $allMessages as $key => $messages ) {
			if( $messages ) {
				$readMessageIDs = array();
				foreach( $messages as $message ) {
					// Collect read messages.
					if( $message->IsRead ) {
						$readMessageIDs[] = $message->MessageID;
					}
					// Leave out the # infront of the color code.
					if( isset($message->StickyInfo->Color) && $message->StickyInfo->Color ) {
						$message->StickyInfo->Color = substr($message->StickyInfo->Color, 1);
					}
				}
				$messageList = new MessageList();
				$messageList->Messages = $messages;
				$messageList->ReadMessageIDs = $readMessageIDs;
				$allMessageList[$key] = $messageList;
			}
		}
		return $allMessageList;
	}

	/**
	 * Remove given messages from the database.
	 * When one of the given messages has started a message thread (such as a sticky note), 
	 * all replies of that thread are cascade deleted from database as well.
	 *
	 * @param array $messageIds Message objects to delete
	 */	
	private static function removeMessage( $messageIds )
	{	
		if( $messageIds ) {
			require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
			require_once BASEDIR.'/server/smartevent.php';
			$ticket = BizSession::getTicket();
			foreach( $messageIds as $messageId ) {
				$type = DBMessage::getMessageType( $messageId );
				if( $type == 'reply' ) {
					// Avoid deleting intermediate replies. That are replies for which another reply
					// was given. This could happen in race conditions, whereby user A is replying
					// while user B is removing his/her last reply.
					$replyIds = DBMessage::getReplyToMsgIdsForMessageId( $messageId );
					if( $replyIds && count( $replyIds ) > 0 ) {
						$details = 'Message reply '.$messageId.' was not '.
									'deleted because another reply on it was found in the database '.reset($replyIds).
									' (reply on reply). Only the last reply can be deleted.';
						$errorReportEntry = new ErrorReportEntry();
						$errorReportEntry->Message		= 'Message cannot be deleted.';
						$errorReportEntry->Details 		= $details;
						$errorReportEntry->MessageLevel = 'Warning';
						
						BizErrorReport::reportError( $errorReportEntry );						
						continue; // SKIP deletion of this message
					}
				} else {
					// Cascade delete the whole thread first (before deleting thread holder).
					// For example, deletion of a sticky note, implies deletion of all its replies.
					$dependentMessageIds = DBMessage::getMsgIdsForThreadMessageId( $messageId );
					$objectIds = DBMessage::getObjectIdsForMsgIds( $dependentMessageIds );
					if( $dependentMessageIds ) foreach( $dependentMessageIds as $dependentMessageId ) {
						if( DBMessage::deleteMessage( $dependentMessageId ) ) {
							$objectId = isset($objectIds[$dependentMessageId]) ? $objectIds[$dependentMessageId] : null; // not set for a user message
							new smartevent_deletemessage( $ticket, $dependentMessageId, $objectId );
						}
					}
				}
				$objectIds = DBMessage::getObjectIdsForMsgIds( array($messageId) );
				if( DBMessage::deleteMessage( $messageId ) ) {
					$objectId = isset($objectIds[$messageId]) ? $objectIds[$messageId] : null; // not set for a user message
					new smartevent_deletemessage( $ticket, $messageId, $objectId );
				}
			}
		}
	}
	
	/**
	 * Deletes messages sent to a given object.
	 *
	 * @param string $objectId
	 * @param string $messageType       Optional. Only delete messages with this type.
	 * @param string $messageTypeDetail Optional. Only delete messages with this type detail.
	 * @throws BizException Throws BizException when fails.
	 */
	public static function deleteMessagesForObject( $objectId, $messageType = null, $messageTypeDetail = null )
	{
		// Use semaphore to avoid race conditions / data corruption.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSema = new BizSemaphore();
		$semaName = 'BizMessage_obj_'.$objectId;
		$semaId = $bizSema->createSemaphore( $semaName );
		if( !$semaId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$details = 'Operation "'.__METHOD__ .'" could not be completed because user "'. $otherUser . 
						'" is performing the same operation on the same objects or users.';
			throw new BizException( 'ERR_DELETE_MESSAGES', 'Server', $details );
		}

		// Delete messages from DB.
		try {
			require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
			$messageIds = DBMessage::getMessageIdsByObjId( $objectId, $messageType, $messageTypeDetail );
			self::removeMessage( $messageIds );
		} catch( BizException $e ) {
			if( $semaId ) {
				$bizSema->releaseSemaphore( $semaId );
			}
			throw $e;
		}

		// DB operations are complete, so time to release the semaphore.
		if( $semaId ) {
			$bizSema->releaseSemaphore( $semaId );
		}
	}
	
	/**
	 * Deletes messages sent to a given user.
	 *
	 * @param string $userId User DB id.
	 * @param string $messageType       Optional. Only delete messages with this type.
	 * @param string $messageTypeDetail Optional. Only delete messages with this type detail.
	 * @throws BizException Throws BizException when fails.
	 */
	public static function deleteMessagesForUser( $userId, $messageType = null, $messageTypeDetail = null )
	{
		// Use semaphore to avoid race conditions / data corruption.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSema = new BizSemaphore();
		$semaName = 'BizMessage_usr_'.$userId;
		$semaId = $bizSema->createSemaphore( $semaName );
		if( !$semaId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$details = 'Operation "'.__METHOD__ .'" could not be completed because user "'. $otherUser . 
						'" is performing the same operation on the same objects or users.';
			throw new BizException( 'ERR_DELETE_MESSAGES', 'Server', $details );
		}

		// Delete messages from DB.
		try {
			require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
			$messageIds = DBMessage::getMessageIdsByUserId( $userId, $messageType, $messageTypeDetail );
			self::removeMessage( $messageIds );
		} catch( BizException $e ) {
			if( $semaId ) {
				$bizSema->releaseSemaphore( $semaId );
			}
			throw $e;
		}

		// DB operations are complete, so time to release the semaphore.
		if( $semaId ) {
			$bizSema->releaseSemaphore( $semaId );
		}
	}
	
	/**
	 * Sends messages, for which the following is done:
	 * - Saves messages to the DB (for later use, when objects are opened or users are logged in)
	 * - Send messages over network (n-cast: broadcast / multicast) to directly notify user (working with object)
	 * - Saves a footprint of SendMessage service at DB for administrative purposes
	 *
	 * Messages can be *deleted* by passing MessageList->DeleteMessageIDs.
	 * Messages can be marked *read* by passing MessageList->ReadMessageIDs.
	 *
	 * @param MessageList $messageList Messages to send to users or objects.
	 * @param bool $notify Wether or not to send network notification (n-cast) per message.
	 */
	public static function sendMessages( $messageList, $notify = true )
	{
		if( $messageList ) {
			$report = BizErrorReport::startReport();
			$report->Type = 'Message';

			if( !is_a( $messageList, 'MessageList' ) ) { // detect 7.x customizations that are not ported to 8.0 (used to be $user)
				$details = 'Since 8.0, the BizMessage::sendMessages() function no longer accepts an user and a list of messages. '.
							  'Make sure you pass in a MessageList data object at the 1st parameter. ';
				$errorReportEntry = new ErrorReportEntry();
				$errorReportEntry->Message		= 'Message';
				$errorReportEntry->Details 		= $details;

				BizErrorReport::reportError( $errorReportEntry );
				BizErrorReport::stopReport();
				return;
			}
		
			// Make sure incoming messages are not a bad mixture (=> client programming error).
			$objectId = null; $shortUserName = null;
			try {
				$destination = self::validateMessageList( $messageList, $objectId, $shortUserName );
			} catch( BizException $e ) {
				BizErrorReport::reportException( $e );
				BizErrorReport::stopReport();
				return;
			}

			try {			
				if( $destination == 'object' ) {
					// Send object messages, per object.
					require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
					$shortUserName = BizSession::getShortUserName();
					$object = BizObject::getObject( $objectId, $shortUserName, false, 'none', array( 'MetaData', 'Targets' ) );
					$object->MessageList = $messageList;
					self::doSendMessagesForObject( $object, $objectId, $notify );
				} else if( $destination == 'user' ) {
					require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
					$userId = DBUser::getUserDbIdByShortName( $shortUserName );
					self::doSendMessagesForUser( $userId, $messageList, $notify );
				}
			} catch( BizException $e ) {
				BizErrorReport::reportException( $e );
			}
			BizErrorReport::stopReport();
		}
	}

	/**
	 * Save messages that were sent to objects. New messages are created in database. Existing messages
	 * are updated in the database. Doing this, the MessageID is assumed to be unique. The caller can
	 * make up its own MessageID by generating a GUID, in case it is needed to know the ID on forehand
	 * which is the case for SaveObjects requests, saving heavy layout files.
	 * The passed $object->MessageList gets updated with properties as stored in database.
	 *
	 * @param Object $object Object being saved. Its MessageList should contain message to send.
	 * @param bool $notify Wether or not to send network notification (n-cast) per message.
	 */
	public static function sendMessagesForObject( $object, $notify = true )
	{
		$report = BizErrorReport::startReport();
		$report->Type = 'Object';
		$report->ID = $object->MetaData->BasicMetaData->ID;
		// Make sure incoming messages are not a bad mixture (=> client programming error).
		$objectId = null; 
		$userId = null;
		try {
			$destination = self::validateMessageList( $object->MessageList, $objectId, $userId );
		} catch( BizException $e ) {
			BizErrorReport::reportException( $e );
			BizErrorReport::stopReport();
			return;
		}

		try {
			if( $destination == 'object' ) {
				self::doSendMessagesForObject( $object, $objectId, $notify );
			} else if( $destination == 'user' ) {
				$details = 'Not allowed to store a messages sent to users, while the context is about objects. '.
							'Make sure that for all Message objects, the ObjectID is set.';
				$errorReportEntry = new ErrorReportEntry();
				$errorReportEntry->Message	= BizResources::localize( 'ERR_INVALID_OPERATION' );
				$errorReportEntry->Details 	= $details;
				BizErrorReport::reportError( $errorReportEntry );
			} // else: Emtpy destination, which happens when there no messages at all, which is ok.		
		} catch ( BizException $e ) {
			BizErrorReport::reportException( $e );
		}
		BizErrorReport::stopReport();
	}
	
	/**
	 * Same as sendMessagesForObject function, but without message validation.
	 *
	 * @param Object $object
	 * @param string $objIdOfMessage Postfix used in semaphore
	 * @param bool $notify Whether or not to send network notification (n-cast) per message.
	 * @throws BizException
	 */
	private static function doSendMessagesForObject( $object, $objIdOfMessage, $notify )
	{
		// Use semaphore to avoid race conditions / data corruption.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSema = new BizSemaphore();
		$semaName = 'BizMessage_obj_'.$objIdOfMessage;
		$semaId = $bizSema->createSemaphore( $semaName );
		if( !$semaId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$details = 'Operation "'.__METHOD__ .'" could not be completed because user "'. $otherUser . 
						'" is performing the same operation on the same objects or users.';
			throw new BizException( 'ERR_SEND_MESSAGES', 'Server', $details );
		}

		try {

			// Build high accessibility lists for read- and deleted messages.
			$readMsgIds = array();
			$alreadyHandledReadMsgIds = array();
			if( isset($object->MessageList->ReadMessageIDs) ) {
				$readMsgIds = array_flip( $object->MessageList->ReadMessageIDs );
			}
			$deleteMsgIds = array();
			if( isset($object->MessageList->DeleteMessageIDs) ) {
				$deleteMsgIds = array_flip( $object->MessageList->DeleteMessageIDs );
			}
			
			$updatedMessages = array();
			// Send messages to the object; Store them in database and n-cast.
			if( isset($object->MessageList->Messages) ) {
				$messages = $object->MessageList->Messages;
				foreach( $messages as $message ) {

					// Update message with new object version.
					if( !$message->ObjectVersion ) {
						$message->ObjectVersion = $object->MetaData->WorkflowMetaData->Version;
					}
	
					// Mark message as read, when indicated by client/user.
					if( array_key_exists( $message->MessageID, $readMsgIds ) ) {
						$message->IsRead = true;
						$alreadyHandledReadMsgIds[$message->MessageID] = true;
					}
					
					// Send the message (except when marked for deletion).
					if( !array_key_exists( $message->MessageID, $deleteMsgIds ) ) {
						try {
							$updatedMessages[] = self::sendMessage( null, $message, $notify );
						} catch( BizException $e ) {
							BizErrorReport::reportException( $e );
						}
					}
				}
			}
			
			// In case only ReadMessageIds is sent in (without MessageList->Messages),
			// the ReadMessageIds need to be handled here (mark as read).
			if( $readMsgIds ) {
				foreach( array_keys( $readMsgIds ) as $readMsgId ) {
					if( !array_key_exists( $readMsgId, $alreadyHandledReadMsgIds ) ) {
						$updatedMessages[] = self::markMessageAsRead( $readMsgId );						
					}
				}
			}
			
			if( $updatedMessages ) {
				$object->MessageList->Messages = $updatedMessages;
			}
			
			if( isset($object->MessageList->Messages) ) {
				// Leave footprint of SendMessage service at database.
				require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
				DBlog::logServiceForObject( 'SendMessages', $object );
			}
			
			// Delete messages from database that are marked to delete.
			if( isset($object->MessageList->DeleteMessageIDs) ) {
				self::removeMessage( $object->MessageList->DeleteMessageIDs );
			}

			// Update search index, when there are updated messages or deleted messages in the object
			if( $updatedMessages || isset($object->MessageList->DeleteMessageIDs) ) {
				require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
				BizSearch::updateObjects( array( $object ), true/*$suppressExceptions*/, array('Workflow')/*$areas*/  );
			}
		} catch( BizException $e ) {
			if( $semaId ) {
				$bizSema->releaseSemaphore( $semaId );
			}
			BizErrorReport::reportException( $e );
		}

		// DB operations are complete, so time to release the semaphore.
		if( $semaId ) {
			$bizSema->releaseSemaphore( $semaId );
		}
	}

	/**
	 * Save messages that were sent to an user. New messages are created in database. Existing messages
	 * are updated in the database. Doing this, the MessageID is assumed to be unique. The caller can
	 * make up its own MessageID by generating a GUID, in case it is needed to know the ID on forehand
	 * which is the case for LogOff requests.
	 * The passed $messageList gets updated with properties as stored in database.
	 *
	 * @param string $shortUserName Short user name.
	 * @param MessageList $messageList Messages to send.
	 * @param bool $notify Whether or not to send network notification (n-cast) per message.
	 * @throws BizException Throws BizException when fails.
	 */
	public static function sendMessagesForUser( $shortUserName, $messageList, $notify = true )
	{
		// Make sure incoming messages are not a bad mixture (=> client programming error).
		$objectId = null;
		$destination = self::validateMessageList( $messageList, $objectId, $shortUserName );
		
		if( $destination == 'user' ) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$userId = DBUser::getUserDbIdByShortName( $shortUserName );
			self::doSendMessagesForUser( $userId, $messageList, $notify );
		} else if( $destination == 'object' ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
				'Not allowed to store a messages sent to objects, while the context is about users. '.
				'Make sure that for all Message objects, the UserID is set.' );
		} // else: Emtpy destination, which happens when there no messages at all, which is ok.
	}
	
	/**
	 * Same as sendMessagesForUser function, but without message validation.
	 *
	 * @param string $userId User DB id.
	 * @param MessageList|null $messageList Messages to validate.
	 * @param bool $notify Whether or not to send network notification (n-cast) per message.
	 * @throws BizException
	 */
	private static function doSendMessagesForUser( $userId, $messageList, $notify )
	{
		// Use semaphore to avoid race conditions / data corruption.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSema = new BizSemaphore();
		$semaName = 'BizMessage_usr_'.$userId;
		$semaId = $bizSema->createSemaphore( $semaName );
		if( !$semaId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$details = 'Operation "'.__METHOD__ .'" could not be completed because user "'. $otherUser . 
						'" is performing the same operation on the same objects or users.';
			throw new BizException( 'ERR_SEND_MESSAGES', 'Server', $details );
		}

		try {
			// Build high accessibility lists for read- and deleted messages.
			$readMsgIds = array();
			$alreadyHandledReadMsgIds = array();
			if( isset($messageList->ReadMessageIDs) ) {
				$readMsgIds = array_flip( $messageList->ReadMessageIDs );
			}
			$deleteMsgIds = array();
			if( isset($messageList->DeleteMessageIDs) ) {
				$deleteMsgIds = array_flip( $messageList->DeleteMessageIDs );
			}
			
			$updatedMessages = array();
			// Send messages to the user; Store them in database and n-cast.
			if( isset($messageList->Messages) ) {
				$messages = $messageList->Messages;				
				foreach( $messages as $message ) {
				
					// Messages sent to an user, can not be sent to an object as well.
					$message->ObjectID = null;
					$message->ObjectVersion = null;
	
					// Mark message as read, when indicated by client/user.
					if( array_key_exists( $message->MessageID, $readMsgIds ) ) {
						$message->IsRead = true;
						$alreadyHandledReadMsgIds[$message->MessageID] = true;
					}
					
					// Send the message (except when marked for deletion).
					if( !array_key_exists( $message->MessageID, $deleteMsgIds ) ) {
						try {
							$updatedMessages[] = self::sendMessage( null, $message, $notify );
						} catch( BizException $e ) {
							BizErrorReport::reportException( $e );
						}
					}
				}
			}
			
			// In case only ReadMessageIds is sent in (without MessageList->Messages),
			// the ReadMessageIds need to be handled here (mark as read).
			if( $readMsgIds ) {
				foreach( array_keys( $readMsgIds ) as $readMsgId ) {
					if( !array_key_exists( $readMsgId, $alreadyHandledReadMsgIds ) ) {
						$updatedMessages[] = self::markMessageAsRead( $readMsgId );
					}
				}
			}
			
			if( $updatedMessages ) {
				$messageList->Messages = $updatedMessages;
			}	

			if( isset($messageList->Messages) ) {
				// Leave footprint of SendMessage service at database.
				require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
				DBlog::logService( null, 'SendMessages' );
			}
			
			// Delete messages from database that are marked to delete.
			if( isset($messageList->DeleteMessageIDs) ) {
				self::removeMessage( $messageList->DeleteMessageIDs );
			}
		} catch( BizException $e ) {
			if( $semaId ) {
				$bizSema->releaseSemaphore( $semaId );
			}
			BizErrorReport::reportException( $e );
		}

		// DB operations are complete, so time to release the semaphore.
		if( $semaId ) {
			$bizSema->releaseSemaphore( $semaId );
		}
	}
	
	/**
	 * Send a single message, notification depends on the $notify flag.
	 *
	 * @param string $user     Short name of the user performing the request. NULL to auto resolve current user.
	 * @param Message $message Message to send (the message ids will be updated with the created ones at DB!).
	 * @param bool $notify     Whether or not to send network notification (n-cast) per message.
	 * @throws BizException Throws BizException when the operation fails.
	 * @return Message The updated message, freshly read from database (after storage).
	 */
	private static function sendMessage( $user, &$message, $notify )
	{
		require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
		
		// Validate referential message ids; They should exist in the database.
		if( $message->MessageType == 'reply' ) {
			if( !DBMessage::doesMessageExist( $message->ReplyToMessageID ) ) {
				$entity1 = new ErrorReportEntity();
				if( $message->ObjectID ) {
					$entity1->Type = 'Object';
					$entity1->ID = $message->ObjectID;
				} elseif( $message->UserID ) {
					$entity1->Type = 'User';
					$entity1->ID = $message->UserID;
				}
				$entity1->Role = 'Receiving';
				$entity2 = new ErrorReportEntity();
				$entity2->Type = 'Message';
				$entity2->ID = $message->ReplyToMessageID;
				$entity2->Role = 'DoesNotExist';
				$entity3 = new ErrorReportEntity();
				$entity3->Type = 'Message';
				$entity3->ID = $message->MessageID;
				$entity3->Role = 'Sending';
				$entry = new ErrorReportEntry();
				$entry->Entities = array( $entity1, $entity2, $entity3 );
				$entry->Message = BizResources::localize( 'ERR_NOTFOUND' );
				BizErrorReport::reportError( $entry );
				return null;
			}
			if( !DBMessage::doesMessageExist( $message->ThreadMessageID ) ) {
				$entity1 = new ErrorReportEntity();
				if( $message->ObjectID ) {
					$entity1->Type = 'Object';
					$entity1->ID = $message->ObjectID;
				} elseif( $message->UserID ) {
					$entity1->Type = 'User';
					$entity1->ID = $message->UserID;
				}
				$entity1->Role = 'Receiving';
				$entity2 = new ErrorReportEntity();
				$entity2->Type = 'Message';
				$entity2->ID = $message->ReplyToMessageID;
				$entity2->Role = 'DoesNotExist';
				$entity3 = new ErrorReportEntity();
				$entity3->Type = 'Message';
				$entity3->ID = $message->MessageID;
				$entity3->Role = 'Sending';
				$entry = new ErrorReportEntry();
				$entry->Entities = array( $entity1, $entity2, $entity3 );
				$entry->Message = BizResources::localize( 'ERR_NOTFOUND' );
				BizErrorReport::reportError( $entry );
				return null;
			}
		}
		
		$orgMessage = null;
		if( $message->MessageID ) {
			// Before storing the message in DB, get the original message from DB.
			$orgMessage = DBMessage::getMessageByMsgId( $message->MessageID );
		}

		if( $orgMessage ) { // Ori msg exists, meaning it is an update of a existing sticky
			$message->FromUser = $orgMessage->FromUser; // Always take the first creator of the sticky.
		}
		// If from user is still not filled in after the 'repair' above
		// (happens only when it is a new sticky note), do so now:
		if( empty($message->FromUser) ) {
			if( is_null( $user ) ) {
				$user = BizSession::getUserInfo('user');
			}
			$message->FromUser = $user;
		}
		
		$stickyAlreadyExists = $orgMessage ? true : false;
		if( isset( $message->StickyInfo->Color )) {
			// When it is a new sticky, color can be determined using the current login user's color.
			// When it is an update of a sticky, and no color is sent, server assumes no changes needed, so won't do anything.		
			if( empty( $message->StickyInfo->Color ) && !$stickyAlreadyExists ) {
				$message->StickyInfo->Color = BizSession::getUserInfo( 'trackchangescolor' );
			} elseif( !is_null($message->StickyInfo->Color) ) { // if color given, add the hash (#)
				$message->StickyInfo->Color = '#' . $message->StickyInfo->Color;
			}
		}
		if (( !isset( $message->TimeStamp ) || empty( $message->TimeStamp )) && !is_null( $message->TimeStamp )) {
			$message->TimeStamp = date( 'Y-m-d\TH:i:s' ); // Set default timestamp.
		}

		// Store message in DB.
		$id = DBMessage::saveMessage( $message );
		if( $id === false ) {
			$dbDriver = DBDriverFactory::gen();
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		// Get the saved message from DB, which is especially important to return the id
		// in case a new message was created. But also to be able to do a good compare between
		// the message update applied to DB and the original message that was stored at DB before.
		$message = DBMessage::getMessageByMsgId( $id );

		// When the message has not been changed, do NOT n-cast.
		// This is to avoid n-casting many of the same messages over and over again,
		// for example during SaveObjects of layout object with many Stick Notes.
		if( $orgMessage && $notify ) {
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$compUtil = new WW_Utils_PhpCompare();
			$compUtil->initCompare( array( 'Message->ObjectVersion' => true ) ) ; // TODO: remove when fixed
			$notify = !$compUtil->compareTwoObjects( $orgMessage, $message );
		}
		
		// n-cast the properties of created/update message to client apps.
		if( $notify === true ) {
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_sendmessage( BizSession::getTicket(), $message );
		}
		if( isset( $message->StickyInfo->Color ) && $message->StickyInfo->Color ) {
			$message->StickyInfo->Color = substr( $message->StickyInfo->Color, 1 );
		}	
		return $message;
	}
	
		
	/** 
	 * Flag the message as already read.
	 * @param string $readMsgId Unique id(GUID) of message id where message needs to be marked as read.
	 * @return Message The updated message that is retrieved from DB after updating the isRead property.
	 */
	private static function markMessageAsRead( $readMsgId )
	{
		require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
		$message = new Message();
		$message->MessageID = $readMsgId;
		$message->IsRead = true;
		$updatedMessage = null;
		if( DBMessage::markMessageAsRead( $message ) ) {
			$updatedMessage = DBMessage::getMessageByMsgId( $readMsgId );
			if( isset( $updatedMessage->StickyInfo->Color ) && $updatedMessage->StickyInfo->Color ) {
				$updatedMessage->StickyInfo->Color = substr( $updatedMessage->StickyInfo->Color, 1 );
			}

		}
		return $updatedMessage;
	}
	
	/**
	 * Validates if given messages belong either to one object or to some users.
	 *
	 * @param MessageList|null $messageList Messages to validate.
	 * @param string $objectId [in/out] Filled only when the message belongs to object.
	 * @param string $shortUserName User short name.
	 * @return string Where messages are sent to: 'object' or 'user'
	 * @throws BizException When any of the given messages is not valid.
	 */
	private static function validateMessageList( $messageList, &$objectId, &$shortUserName )
	{
		// Bail out when no there messages at all.
		if( !$messageList || (
				count( $messageList->Messages ) == 0 &&
				count( $messageList->ReadMessageIDs ) == 0 &&
				count( $messageList->DeleteMessageIDs ) == 0 ) ) {
			return '';
		}
		
		// Make sure the messages are sent either to objects or to users.
		$otherMessageIDs = array();
		$destination = null;
		if( $messageList->Messages ) foreach( $messageList->Messages as $message ) {
			if( $objectId ) { // next message, while first message was an -object- message
				if( $message->UserID ) {
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store a mixture of object- and user messages at once. '.
						'Make sure that all Message objects, either the UserID or ObjectID is set. '.
						'Problem found for message id: '.$message->MessageID );
				}
				if( $message->ObjectID != $objectId ) {
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store a messages sent to miscellaneous objects at once. '.
						'Make sure that for all Message objects, the ObjectID is the same. '.
						'Problem found for message id: '.$message->MessageID );
				}
				$destination = 'object';
			} else if( $shortUserName ) { // next message, while first message was an -user- message
				if( $message->ObjectID ) {
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store a mixture of object- and user messages at once. '.
						'Make sure that all Message objects, either the UserID or ObjectID is set. '.
						'Problem found for message id: '.$message->MessageID );
				}
				if( $message->UserID != $shortUserName ) {
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store a messages sent to miscellaneous users at once. '.
						'Make sure that for all Message objects, the UserID is the same. '.
						'Problem found for message id: '.$message->MessageID );
				}
				$destination = 'user';
			} else { // first message?
				if( $message->ObjectID ) {
					$objectId = $message->ObjectID;
					$destination = 'object';
				} else if( $message->UserID ) {
					$shortUserName = $message->UserID;
					$destination = 'user';
				} else {
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store messages without destination. '.
						'Make sure that for all Message objects, either the ObjectID or UserID is set.' );
				}
			}
			
			// Make sure replies have extra message ids set, but other messages have not.
			if( $message->MessageType == 'reply' ) {
				if( !$message->ThreadMessageID || !$message->ReplyToMessageID ) {
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store message replies without thread or reply-to message. '.
						'Make sure that for all Message objects, the ThreadMessageID and '.
						'ReplyToMessageID are both set, or the MessageType is not set to "reply". '.
						'Problem found for message id: '.$message->MessageID );
				}
			} else { // other type
				if( $message->ThreadMessageID || $message->ReplyToMessageID ) {
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store messages as replies, while they are not of reply type. '.
						'Make sure that for all Message objects, the ThreadMessageID and '.
						'ReplyToMessageID are not set, or the MessageType is set to "reply". '.
						'Problem found for message id: '.$message->MessageID );
				}
			}
			
			// Collect references to other messages.
			if( $message->ThreadMessageID ) {
				$otherMessageIDs[] = $message->ThreadMessageID;
			}
			if( $message->ReplyToMessageID ) {
				$otherMessageIDs[] = $message->ReplyToMessageID;
			}
		}
		
		// Colllect references to other messages.
		if( $messageList->ReadMessageIDs ) {
			$otherMessageIDs = array_merge( $otherMessageIDs, $messageList->ReadMessageIDs );
		}
		if( $messageList->DeleteMessageIDs ) {
			$otherMessageIDs = array_merge( $otherMessageIDs, $messageList->DeleteMessageIDs );
		}
		
		// Make sure that references to other messages all have the same destination.
		if( count( $otherMessageIDs ) > 0 ) {
			require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
			$userIds = DBMessage::getUserIdsForMsgIds( $otherMessageIDs );
			$objectIds = DBMessage::getObjectIdsForMsgIds( $otherMessageIDs );
			if( !$destination ) {
				if( count( $userIds ) > 0 ) {
					$shortUserName = reset( $userIds );
					$destination = 'user';
				} else if( count( $objectIds ) > 0 ) {
					$objectId = reset( $objectIds );
					$destination = 'object';
				} // else, the message is no longer found at DB?
			}
			if( $destination == 'user' ) {
				if( $userIds ) foreach( $userIds as $msgId => $iterUserId ) {
					if( !$iterUserId ) {
						throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
							'Not allowed to store a mixture of object- and user messages at once. '.
							'Make sure that ReadMessageIDs, DeleteMessageIDs, ThreadMessageID and ReplyToMessageID are sent to the same user. '.
							'Problem found for message id: '.$msgId );
					}
					if( $iterUserId != $shortUserName ) {
						throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
							'Not allowed to store a mixture of user messages at once. '.
							'Make sure that ReadMessageIDs, DeleteMessageIDs, ThreadMessageID and ReplyToMessageID are sent to the same user. '.
							'Problem found for message id: '.$msgId );
					}
				}
				if( count( $objectIds ) > 0 ) {
					reset( $objectIds ); // take first item, for the call below: key()
					$msgId = key( $objectIds ); // take first key
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store a mixture of object- and user messages at once. '.
						'Make sure that ReadMessageIDs, DeleteMessageIDs, ThreadMessageID and ReplyToMessageID are sent to the same user. '.
						'Problem found for message id: '.$msgId );
				}
			} else if( $destination == 'object' ) {
				if( $objectIds ) foreach( $objectIds as $msgId => $iterObjectId ) {
					if( !$iterObjectId ) {
						throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
							'Not allowed to store a mixture of object- and user messages at once. '.
							'Make sure that ReadMessageIDs, DeleteMessageIDs, ThreadMessageID and ReplyToMessageID are sent to the same object. '.
							'Problem found for message id: '.$msgId );
					}
					if( $iterObjectId != $objectId ) {
						throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
							'Not allowed to store a mixture of object messages at once. '.
							'Make sure that ReadMessageIDs, DeleteMessageIDs, ThreadMessageID and ReplyToMessageID are sent to the same object. '.
							'Problem found for message id: '.$msgId );
					}
				}
				if( count( $userIds ) > 0 ) {
					reset( $userIds ); // take first item, for the call below: key()
					$msgId = key( $userIds ); // take first key
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 
						'Not allowed to store a mixture of object- and user messages at once. '.
						'Make sure that ReadMessageIDs, DeleteMessageIDs, ThreadMessageID and ReplyToMessageID are sent to the same object. '.
						'Problem found for message id: '.$msgId );
				}
			}
		}
		return $destination;
	}
}