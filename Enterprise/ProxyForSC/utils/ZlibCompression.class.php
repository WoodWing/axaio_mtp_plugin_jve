<?php
/**
 * Helper class to compress/decompress files using "deflate" algorithm (RFC 1950 - zlib).
 *
 * @package    ProxyForSC
 * @subpackage Utils
 * @since      v1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_ZlibCompression
{
	/**
	 * Decompresses (inflates) a file into another file.
	 *
	 * @param string $inputFile Compressed file to read from.
	 * @param string $outputFile Uncompressed file to write into.
	 * @return boolean Whether or not decompressed successfully.
	 */
	public static function inflateFile( $inputFile, $outputFile )
	{
		// Init.
		$retVal = false; 
		$bytesCopied = 0; 
		$bytesRead = 0;
		
		// Open both files.
		$fpOutputFile = fopen( $outputFile, 'wb' );
		if( !$fpOutputFile ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Could not open file for writing: '.$outputFile );
		}
		$fpInputFile = fopen( $inputFile, 'rb');
		if( !$fpInputFile ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Could not open file for reading: '.$inputFile );
		}
		
		// Set filter to read compressed data and write uncompressed data.
		if( $fpOutputFile && $fpInputFile ) {
			$filter = stream_filter_append( $fpInputFile, 'zlib.inflate', STREAM_FILTER_READ );
			if( $filter ) {
			
				// Set stream buffer size.
				$bufSize = self::getBufferSize( filesize( $inputFile ) ); // 16MB (16x1024x1024)
				stream_set_read_buffer( $fpInputFile, $bufSize );
			
				// Stream input file into output file.
				$bytesCopied = stream_copy_to_stream( $fpInputFile, $fpOutputFile );
				$bytesRead = ftell( $fpInputFile );

				stream_filter_remove( $filter );
				$retVal = true;
			} else {
				LogHandler::Log( __CLASS__, 'ERROR', 'Could not set compression filter for: '.$inputFile );
			}
		}
		
		// Close both files.
		if( $fpInputFile ) {
			fclose( $fpInputFile );
		}
		if( $fpOutputFile ) {
			fclose( $fpOutputFile );
		}
		
		// Log statistics. Note: filesize() works only after fclose().
		if( $retVal && LogHandler::debugMode() ) {
			$message = 
				'File inflate statistics: <ul>'.
					'<li>compressed input file: '.$inputFile. '</li>'.
					'<li>compressed input file size: '.filesize($inputFile). '</li>'.
					'<li>uncompressed output file: '.$outputFile. '</li>'.
					'<li>uncompressed output file size: '.filesize($outputFile). '</li>'.
					'<li>bytes read from input file: '.$bytesRead. '</li>'.
					'<li>bytes copied through stream: '.$bytesCopied. '</li>'.
					'<li>stream buffer size used: '.$bufSize. '</li>'.
				'</ul>';
			LogHandler::Log( __CLASS__, 'DEBUG', $message  );
		}
		return $retVal;
	}
	
	/**
	 * Compresses (deflates) a file into another file.
	 *
	 * @param string $inputFile Uncompressed file to read from.
	 * @param string $outputFile Compressed file to write into.
	 * @return boolean Whether or not compressed successfully.
	 */
	public static function deflateFile( $inputFile, $outputFile )
	{
		// Init.
		$retVal = false; 
		$bytesCopied = 0; 
		$bytesRead = 0;
		
		// Open both files.
		$fpOutputFile = fopen( $outputFile, 'wb' );
		if( !$fpOutputFile ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Could not open file for writing: '.$outputFile );
		}
		$fpInputFile = fopen( $inputFile, 'rb');
		if( !$fpInputFile ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Could not open file for reading: '.$inputFile );
		}
		
		// Set filter to read uncompressed data and write compressed data.
		if( $fpOutputFile && $fpInputFile ) {
			$filter = stream_filter_append( $fpOutputFile, 'zlib.deflate', STREAM_FILTER_WRITE );
			if( $filter ) {
			
				// Set stream buffer size.
				$bufSize = self::getBufferSize( filesize( $outputFile ) ); // 16MB (16x1024x1024)
				stream_set_write_buffer( $fpOutputFile, $bufSize );
			
				// Stream input file into output file.
				$bytesCopied = stream_copy_to_stream( $fpInputFile, $fpOutputFile );
				$bytesRead = ftell( $fpInputFile );
			
				stream_filter_remove( $filter );
				$retVal = true;
			} else {
				LogHandler::Log( __CLASS__, 'ERROR', 'Could not set compression filter for: '.$outputFile );
			}
		}
		
		// Close both files.
		if( $fpInputFile ) {
			fclose( $fpInputFile );
		}
		if( $fpOutputFile ) {
			fclose( $fpOutputFile );
		}
		
		// Log statistics. Note: filesize() works only after fclose().
		if( $retVal && LogHandler::debugMode() ) {
			$message = 
				'File deflate statistics: <ul>'.
					'<li>uncompressed input file: '.$inputFile. '</li>'.
					'<li>uncompressed input file size: '.filesize($inputFile). '</li>'.
					'<li>compressed output file: '.$outputFile. '</li>'.
					'<li>compressed output file size: '.filesize($outputFile). '</li>'.
					'<li>bytes read from input file: '.$bytesRead. '</li>'.
					'<li>bytes copied through stream: '.$bytesCopied. '</li>'.
					'<li>stream buffer size used: '.$bufSize. '</li>'.
				'</ul>';
			LogHandler::Log( __CLASS__, 'DEBUG', $message  );
		}
		return $retVal;
	}

	/**
	 * Get buffer chunk size for read/write file 
	 * 
	 * To avoid looping too many times for chucked up/downloads, choose smart buffer size. 
     * Let's loop max 16 times under 256MB file size. Larger files will take significant
	 * up/download time for which looping won't be the bottleneck (the network throughput will be).
	 *
	 * @param integer $fileSize
	 * @return integer Buffer size in bytes.
	 */
	private static function getBufferSize( $fileSize )
	{
		if( $fileSize > 51200 ) {  // > 50K
			$bufSize = ( $fileSize > 16777216 ) ? 16777216 : 1048576; // 16MB or 1MB
		} else {
			$bufSize = 4096; // 4K
		}
		return $bufSize;
	}
}