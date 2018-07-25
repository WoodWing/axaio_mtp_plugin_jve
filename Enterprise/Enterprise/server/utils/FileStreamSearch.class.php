<?php
/**
 * @since       v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Helper class that searches through a file for certain byte data.
 * This is done -without- reading the entire file into memory.
 *
 * The maximum amount of used memory can be controlled by parameters.
 * A block size is used to read the file chunckwise. Larger block size
 * makes reading faster, but consumes more memory. The following params are accepted:
 * - searchHorizon:  The search can be ended before reaching the end of file. 
 *                   This parametertells when to give up the search. Default = 100MB.
 * - dataBlockSize:  When searching for data blocks, the block itself is recorded in
 *                   memory. Default = 10MB.
 * - maxMemoryUsage: During search operations, the file is read chunck-wise. 
 *                   This parameter controls the chunck size. Default = 1MB.
 *
 * IMPORTANT: This class can NOT search for multi-byte Unicode characters.
 * Nevertheles, it -can- be used to search for ASCII chars (e.g. such as XML tag names)
 * while the file contains multi-byte Unicode characters.
 */
class WW_Utils_FileStreamSearch
{
	private $fileHandle;
	private $fileOffset;
	
	private $dataBlockSize;
	private $searchHorizon;
	private $maxMemoryUsage;

	/**
	 * Creates a new data search.
	 *
	 * The memory usage during search operations can be controlled.
	 * See module header for details of which parameters are accepted.
	 *
	 * @param array|null $args NULL to use the default arguments.
	 */
	public function __construct( $args = null )
	{
		if( $args ) {
			$this->setSearchParams( $args );
		}
		$this->fileHandle = null;
		$this->fileOffset = 0;
	}
	
	/**
	 * Auto closes the file being searched.
	 */
	public function __destruct()
	{
		if( $this->fileHandle ) {
			$this->closeFile();
		}
	}
	
	/**
	 * The memory usage during search operations can be controlled.
	 * See module header for details of which parameters are accepted.
	 *
	 * @param array $args
	 */
	public function setSearchParams( array $args )
	{
		if( array_key_exists( 'searchHorizon', $args ) ) {
			$this->searchHorizon = $args['searchHorizon'];
		} else {
			$this->searchHorizon = 104857600; // 100 MB
		}
		if( array_key_exists( 'dataBlockSize', $args ) ) {
			$this->dataBlockSize = $args['dataBlockSize'];
		} else {
			$this->dataBlockSize = 10485760; // 10 MB
		}
		if( array_key_exists( 'maxMemoryUsage', $args ) ) {
			$this->maxMemoryUsage = $args['maxMemoryUsage'];
		} else {
			$this->maxMemoryUsage = 1048576; // 1 MB
		}
	}

	/**
	 * Searches for a piece of data. When found, the internal file pointer is moved foreward.
	 * This could be useful when searchNextDataBlock() needs to work from a certain start point.
	 *
	 * @param string $searchString Byte data to seach for.
	 * @return bool Whether or not the data was found.
	 */
	public function searchNextData( $searchString )
	{
		if( $this->fileHandle ) {
			$recordData = false;
			$found = $this->doSearchNextData( $searchString, $recordData );
		} else {
			$found = false;
		}
		return $found;
	}
	
	/**
	 * Same as searchNextData() but now the ability to 'record' byte data while searching.
	 * The data is recorded from the current internal file pointer. The first time calling
	 * the record param should be set to false (or searchNextData() should be called).
	 * The second time calling, setting the record param to true results into 
	 *
	 * @param string $searchString Byte data to seach for.
	 * @param string|bool &$recordData Collects and returns all passed data while searching. Pass in FALSE if data recording is unwanted.
	 * @return bool Whether or not the data was found.
	 */
	private function doSearchNextData( $searchString, &$recordData )
	{
		$found = false;
		$prevEndPiece = '';
		$searchStringLen = strlen( $searchString );
		fseek( $this->fileHandle, $this->fileOffset );

		while( !feof( $this->fileHandle ) && 
			!$found && 
			$this->fileOffset < $this->searchHorizon ) {

			$dataBlock = fread( $this->fileHandle, $this->maxMemoryUsage );
			if( $dataBlock !== false ) {
				// Search in more than just the block size, since the search string could coincidentally
				// overlap the end of one block and the begin of the next block.
				// This is where this example fails: http://nl3.php.net/manual/en/function.fseek.php#106594
				$strPos = strpos( $prevEndPiece.$dataBlock, $searchString );
				if( $strPos !== false ) {
					$strPos -= strlen( $prevEndPiece ); // compensate
					$found = true;
					$this->fileOffset += $strPos;
				} else {
					$this->fileOffset += $this->maxMemoryUsage;
				}
			} else {
				$strPos = false;
			}
			
			// Only when requested to record, collect the data.
			if( $recordData !== false ) {
				if( strlen($recordData) < $this->dataBlockSize ) {
					if( $strPos !== false ) {
						$recordData .= substr( $dataBlock, 0, $strPos + $searchStringLen );
					} else {
						$recordData .= $dataBlock;
					}
				} // else stop recording; about to exceed max allowed block size usage.
			}
			
			// Grab last piece of the data block to include in next search iteration.
			if( $dataBlock !== false ) {
				$prevEndPiece = substr( $dataBlock, -$searchStringLen );
			} else {
				$prevEndPiece = '';
			}
		}
		return $found;
	}

	/**
	 * Find a next chunck of byte data with start- and end markers.
	 * Assumed is that the searched data block is not so large.
	 * And so the data block is recorded in memory. 
	 *
	 * @param string $startString Begin data block marker.
	 * @param string $stopString  End data block marker.
	 * @return bool|string Content of found data block. NULL when nothing found.
	 */
	public function searchNextDataBlock( $startString, $stopString )
	{
		$found = false;
		$recordData = false;
		if( $this->fileHandle ) {
			if( $this->doSearchNextData( $startString, $recordData ) ) {
				$recordData = '';
				if( $this->doSearchNextData( $stopString, $recordData ) ) {
					$found = true;
				}
			}
		}
		return $found ? $recordData : false;
	}
	
	/**
	 * Reads a byte data block, starting from the current internal file pointer.
	 *
	 * @return string
	 */
	public function grabDataBlock()
	{
		if( $this->fileHandle ) {
			fseek( $this->fileHandle, $this->fileOffset );
			$dataBlock = fread( $this->fileHandle, $this->maxMemoryUsage );
		} else {
			$dataBlock = false;
		}
		return $dataBlock;
	}
	
	/**
	 * Resets the internal file pointer to a given byte offset. After this, calling 
	 * searchNextDataBlock() or searchNextData() will start searching from the given offset.
	 *
	 * @param integer $fileOffset
	 * @return bool Whether or not the start point could be set.
	 */
	public function setSearchStartingPoint( $fileOffset )
	{
		if( $this->fileHandle ) {
			$this->fileOffset = $fileOffset;
			$couldSet = (bool)(fseek( $this->fileHandle, $this->fileOffset ) == 0);
		} else {
			$couldSet = false;
		}
		return $couldSet;
	}

	/**
	 * Tells the internal file pointer, from where the next search will be operating.
	 * Note that this position is right -before- the found data block, as found by calling
	 * the searchNextDataBlock() or searchNextData() functions.
	 *
	 * @return integer
	 */
	public function getSearchStartingPoint()
	{
		return $this->fileOffset;
	}
	
	/**
	 * Open a file for searching (reading).
	 *
	 * @param string $file
	 * @return boolean Whether or not the file could be opened.
	 */
	public function openFile( $file )
	{
		if( !$this->fileHandle ) { // void opening another file before current is closed.
			$this->fileHandle = fopen( $file, 'r' );
			$couldOpen = (bool)$this->fileHandle;
		} else {
			$couldOpen = false;
		}
		return $couldOpen;
	}
	
	/**
	 * Close the file.
	 *
	 * @param void
	 * @return boolean Whether or not the file could be closed.
	 */
	public function closeFile()
	{
		if( $this->fileHandle ) {
			fclose( $this->fileHandle );
			$this->fileHandle = null;
			$couldClose = true;
		} else {
			$couldClose = false;
		}
		return $couldClose;
	}
}
