<?php

class DuplicateImageWarning
{
	public static function sendmessage( $objectId, $msgText )
	{
		require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
		$message = new Message();
		$message->ObjectID = $objectId;
		$message->MessageType = 'system';
		$message->MessageTypeDetail = 'DuplicatePlacement';
		$message->Message = $msgText;
		$message->MessageLevel = 'Warning';
		
		$messageList = new MessageList();
		$messageList->Messages = array( $message );
		BizMessage::sendMessages( $messageList );
	}		
	
	public static function checkrelations($relations)
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		
		foreach ($relations as $relation)
		{
			if ($relation->Type == 'Placed')
			{
				// get other relations
				$childobject = BizObject::getObject($relation->Child, null, false, 'none');
				if ($childobject->MetaData->BasicMetaData->Type == 'Image')
				{
					// if more than 1, the object has been placed multiple times
					$placecount = 0;
					$placelist = array();
	
					foreach ($childobject->Relations as $subrelation)
					{
						if ($subrelation->Type == 'Placed')
						{
							$placecount ++;
							$thisparent = BizObject::getObject($subrelation->Parent,null,false,'none');
							$placelist[] = $thisparent->MetaData->BasicMetaData->Name;
						}
					}
									
					if ($placecount > 1)
					{
						DuplicateImageWarning::sendmessage(
							$relation->Parent, 
							"\r".MSG_DUPLICATEIMAGEWARNING.$childobject->MetaData->BasicMetaData->Name.						
							"\r  - ".join("\r  - ", $placelist)
							);
					}
				}
			}
		}
	}
}
