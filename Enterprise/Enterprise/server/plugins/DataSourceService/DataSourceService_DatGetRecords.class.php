<?php

/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatGetRecords_EnterpriseConnector.class.php';

class DataSourceService_DatGetRecords extends DatGetRecords_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( DatGetRecordsRequest &$req ) {}
	
	final public function runAfter( DatGetRecordsRequest $req, DatGetRecordsResponse &$resp )
	{
		LogHandler::Log("DataSourceService_DatGetRecords","DEBUG","Checking record structure consistency..");
		
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
			throw new BizException("ERR_DATASOURCE","Server","The structure of the query result, was not consistent.","The structure of the query result, was not consistent. The number of fields per record was not the same for every record.");
		
		if( $consistent["fieldorder"] == false )
			throw new BizException("ERR_DATASOURCE","Server","The structure of the query result, was not consistent.","The structure of the query result, was not consistent. The order of fields per record was not the same for every record.");
		
		// if no errors were thrown, close the plugin
		LogHandler::Log("DataSourceService_DatGetRecords","DEBUG","Structure consistency: OK!");
	}
	
	final public function runOverruled( DatGetRecordsRequest $req ) {}
}
