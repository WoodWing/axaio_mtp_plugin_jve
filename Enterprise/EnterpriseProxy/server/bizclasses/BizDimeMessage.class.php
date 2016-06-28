<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Class to handle and hold a DIME message.
 *
 */
class BizDimeMessage
{
	/** @var BizDimeRecord[] */
	protected $Records;
	
	/** @var BizDimeRecord[] */
	protected $RecordsIdMap;

	/**
	 * Reads the DIME message from a given file handle.
	 *
	 * @param resource $handle
	 */
	public function read( $handle )
	{
		require_once BASEDIR .'/server/bizclasses/BizDimeRecord.class.php';
		$fstat = fstat( $handle );
		$p = 0;
		$stop = false;
		$this->Records = array();
		$this->RecordsIdMap = array();
		while (! $stop) {
			$seekResult = fseek( $handle, $p );
			if ($seekResult === 0 && ! feof( $handle ) && $p < $fstat['size']) {
				$record = new BizDimeRecord( );
				$record->read( $handle );
				$this->addRecord( $record );
				$p += $record->RecordLen;
			} else {
				$stop = true;
			}
		}
	}

	/**
	 * Counts the records in the current DIME message.
	 *
	 * @return int The record count.
	 */
	public function getRecordCount()
	{
		return count($this->Records);
	}

	/**
	 * Retrieves a DIME record by a given number.
	 *
	 * @param integer $number
	 * @return BizDimeRecord
	 */
	public function getRecord( $number )
	{
		$record = null;
		if( isset( $this->Records[$number] ) ) {
			$record = $this->Records[$number];
		}
		return $record;
	}

	/**
	 * Retrieves a DIME record by a given identifier.
	 *
	 * @param string $id
	 * @return BizDimeRecord
	 */
	public function getRecordById( $id )
	{
		$record = null;
		if( isset( $this->RecordsIdMap[$id] ) ) {
			$record = $this->RecordsIdMap[$id];
		}
		return $record;
	}

	/**
	 * Returns DIME attachment data of a DIME record identified by a given id.
	 *
	 * @param resource $fileHandle
	 * @param string $id
	 * @return string binary attachment data
	 */
	public function getDataById( $fileHandle, $id )
	{
		$data = '';
		// find record in list and not with getRecordById because
		// we need to know the next records
		$count = count($this->Records);
		for ($i = 0; $i < $count; $i++){
			$record = $this->Records[$i];
			if ($record->Id == $id){
				// first record found, read it and check for chunks
				if (! $record->isMsgChunk()){
					// this attachment is not separated in chunks, don't concat because that requires more memory
					// peak memory is attachment size
					$record->readData($fileHandle);
					$data = $record->getData();
				} else {
					// for chunks, we use a more memory intensive routine
					// peak memory is attachment size + size of largest chunk
					$stop = false;
					do {
						$record->readData($fileHandle);
						$data .= $record->getData();
						// clear data in record to preserve memory
						$record->setData(null);
						$i++;
						if (! isset($this->Records[$i]) || (! $record->isMsgChunk()) ){
							$stop = true;
						} else {
							// get next record
							$record = $this->Records[$i];
						}
					} while (! $stop);
				}
				break;
			}
		}

		return $data;
	}

	/**
	 * Adds a new DIME record.
	 *
	 * @param BizDimeRecord $record
	 */
	public function addRecord( BizDimeRecord $record )
	{
		$this->Records[] = $record;
		$this->RecordsIdMap[$record->Id] = $record;
	}

	/**
	 * Write data to the records by the given handle.
	 *
	 * @param resource $handle
	 */
	public function write( $handle )
	{
		$lastRecordNb = count( $this->Records ) - 1;
		$recordNb = 0;
		foreach( $this->Records as $record ) {
			if( $recordNb == 0 ) {
				$record->setMsgBegin();
			}
			if( $recordNb == $lastRecordNb ) {
				$record->setMsgEnd();
			}
			$record->write( $handle );
			$recordNb++;
		}
	}

	/**
	 * Calculates the byte count of the DIME message.
	 *
	 * Accumulates the total length of the DIME message by adding up the size of each record.
	 *
	 * @return int Byte count.
	 */
	public function getMessageLength()
	{
		$len = 0;
		foreach( $this->Records as $record ) {
			$len += $record->getRecordLength();
		}
		return $len;
	}
}