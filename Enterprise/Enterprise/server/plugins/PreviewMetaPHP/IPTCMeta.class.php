<?php
/**
 * @package 	Enterprise
 * @subpackage 	PreviewMetaPHP
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Reads IPTC IIM metadata from image files.
**/
 
class IPTCMeta
{
	/**
	 * Read IPTC metadata from memory buffer.
	 * 
	 * @param string $format   Format of the 'file'.
	 * @param string $data     Buffer with data of file to get meta data for.
	 * @param array  $metaData Key/value area to store metadata. Existing values will be preserved.
	 * @deprecated Since v8.0.0. Use readIptcFromFile() instead, to avoid huge memory consumption putting large files in memory.
	 */
	public static function readIPTC( $format, $data, array &$metaData )
	{
		// Read the IPTC from file.
		$iptc = false;
		$bimPos = strpos( $data, '8BIM' );
		if( $bimPos !== false ) {
			$bim = substr( $data, $bimPos );
			if( $bim ) {
				LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Found IPTC header.' );
				$iptc = iptcparse( $bim );
			} else {
				LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Empty IPTC header.' );
			}
		} else {
			LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Could not find IPTC header.' );
		}

		// Map the IPTC onto Enterprise properties.
		if( $iptc && is_array($iptc) ) {
			LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Found IPTC data.' );
			require_once BASEDIR.'/server/utils/FileMetaDataToProperties.class.php';
			$converterIPTC = WW_Utils_FileMetaDataToProperties_Factory::createConverter( 'iptc' );
			$converterIPTC->convert( $iptc, $metaData);
		} else {
			LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Did not find IPTC data.' );
		}
	}
	
	/**
	 * Read IPTC metadata from file. 
	 * While doing so, NOT the entire file is read into memory to avoid huge memory consuption.
	 * 
	 * @param string $filePath Full file path to read IPTC from.
	 * @param array  $metaData Key/value area to store metadata. Existing values will be preserved.
	 */
	public static function readIptcFromFile( $filePath, array &$metaData )
	{
		// Use FileStreamSearch to avoid huge memory consumption.
		require_once BASEDIR.'/server/utils/FileStreamSearch.class.php';
		$stream = new WW_Utils_FileStreamSearch( array(
			'searchHorizon'  => 536870912, // The IPTC data block will be in the first 512 MB.
			'dataBlockSize'  => 5242880,   // IPTC data block itself won't be more than 5 MB.
			'maxMemoryUsage' => 1048576,   // Do not use more than 1 MB for seach operations.
		));
		
		// Read the IPTC data from file.
		$iptc = false;
		if( $stream->openFile( $filePath ) ) {
			if( $stream->searchNextData( '8BIM' ) ) {
				LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Found IPTC header.' );
				$iptc = iptcparse( $stream->grabDataBlock() );
			} else {
				LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Could not find IPTC header.' );
			}
			$stream->closeFile();
		} else {
			LogHandler::Log( 'IPTCMeta', 'ERROR', 'Could not open file "'.$filePath.'" to read IPTC data.' );
		}
		
		// Map the IPTC onto Enterprise properties.
		if( $iptc && is_array($iptc) ) {
			LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Found IPTC data.' );
			require_once BASEDIR.'/server/utils/FileMetaDataToProperties.class.php';
			$converterIPTC = WW_Utils_FileMetaDataToProperties_Factory::createConverter( 'iptc' );
			$converterIPTC->convert( $iptc, $metaData);
		} else {
			LogHandler::Log( 'IPTCMeta', 'DEBUG', 'Did not find IPTC data.' );
		}
	}
}