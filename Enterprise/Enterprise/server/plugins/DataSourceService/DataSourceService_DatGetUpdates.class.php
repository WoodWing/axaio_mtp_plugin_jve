<?php

/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatGetUpdates_EnterpriseConnector.class.php';

class DataSourceService_DatGetUpdates extends DatGetUpdates_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( DatGetUpdatesRequest &$req ) {}
	
	final public function runAfter( DatGetUpdatesRequest $req, DatGetUpdatesResponse &$resp )
	{
		LogHandler::Log("DataSourceService_DatGetUpdates","DEBUG","Checking record structure consistency..");
		
		$recordSet = &$resp; // the record set
		
		$maxNumFields = 0;
		$fieldOrder = array();
		
		$consistent = array("fieldnumber"=>true,"fieldorder"=>true);
		
		foreach( $recordSet->Records as &$record )
		{
			$numFields = count($record->Fields);
			if( $maxNumFields == 0 )	$maxNumFields = $numFields;
			
			if( $numFields == $maxNumFields )
			{
				// iterate through the fieldnames, check if the order of the fields are all the same. if not, set consistent to false
				$fieldCounter = 0;
				foreach( $record->Fields as &$field )
				{
					if( count($fieldOrder) != count($record->Fields) )
					{
						$fieldOrder[] = $field->Name;
					}else{
						if($fieldOrder[$fieldCounter] != $field->Name)
						{
							$consistent["fieldorder"] = false;
							break;
						}
					}
					$fieldCounter++;
				}
			}else{
				$consistent["fieldnumber"] = false;
				break;
			}
		}
		
		if( $consistent["fieldnumber"] == false )
		{
			// remove update from database, otherwise we will get this update every time the document opens!
			self::deleteUpdateMessages( $req->ObjectID, $req->UpdateID );

			// we don't want to throw an exception if we are getting live updates! updates are no user actions, so the user should be confronted by them.
			// return an empty record array instead. And log the action.
			LogHandler::log("DataSourceService_DatGetUpdates","ERROR","The structure of the query result, was not consistent. The number of fields per record was not the same for every record.");
			$resp->Records = array();
		}elseif( $consistent["fieldorder"] == false )
		{
			// remove update from database, otherwise we will get this update every time the document opens!
			self::deleteUpdateMessages( $req->ObjectID, $req->UpdateID );
			
			// we don't want to throw an exception if we are getting live updates! updates are no user actions, so the user should be confronted by them.
			// return an empty record array instead. And log the action.
			LogHandler::log("DataSourceService_DatGetUpdates","ERROR","The structure of the query result, was not consistent. The order of fields per record was not the same for every record.");
			$resp->Records = array();
		}else{
			// if no errors were thrown, close the plugin
			LogHandler::Log("DataSourceService_DatGetUpdates","DEBUG","Structure consistency: OK!");
		}
	}
	
	private function deleteUpdateMessages( $objectid, $updateid )
	{
		require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
		$messageList = BizMessage::getMessagesForObject( $objectid );
		if( $messageList->Messages ) foreach( $messageList->Messages as $message ) {
			$messageupdateid = explode( ',', $message->Message );
			if( $messageupdateid == $updateid ) {
				$messageList->DeletedMessageIDs[] = $message->MessageID;
			}
		}
		$messageList->ReadMessageIDs = null;
		$messageList->Messages = null;
		BizMessage::sendMessages( $messageList );
	}
	
	final public function runOverruled( DatGetUpdatesRequest $req ) {}
}
