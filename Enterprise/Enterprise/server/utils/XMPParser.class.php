<?php
/**
 * XMP Parser to read XMP block from file into XML object
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v6.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
class XMPParser
{
	/**
	 * Reads all XMP data (XML tree) from given file contents.
	 * 
	 * @param string $source Buffer with contents of file to read XMP from.
	 * @return SimpleXMLElement|null XML tree built from XMP. NULL when no XMP found.
	 * @deprecated Since v8.0.0. Use readXmpFromFile() instead, to avoid huge memory consumption putting large files in memory.
	 */
	static public function readXMP( $source )
	{
		$sxe = null;
		// Some files (like INDD) can have multiple XMP packages
		// the correct one starts with <?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"
		// To keep code simple we first look for W5M0MpCehiHzreSzNTczkc9d and next 
		// we look for the first <x:xmpmeta" until </x:xmpmeta>

		$xmpPack = strpos($source,'W5M0MpCehiHzreSzNTczkc9d');
		if( $xmpPack === false ) {
			LogHandler::Log('XMPParser', 'DEBUG', 'No XMP found' );
			return null;
		}

		$xmpdata_start	= strpos($source,'<x:xmpmeta', $xmpPack);
		$xmpdata_end 	= $xmpdata_start;
		if( $xmpdata_start !== false ) {
			$xmpdata_end=strpos($source,'</x:xmpmeta>',$xmpPack);
		} else {
			// Didn't find xmpmeta, try xapmeta, which is the old XMP tag:
			$xmpdata_start=strpos($source,'<x:xapmeta',$xmpPack);
			if( $xmpdata_start === false ) {
				LogHandler::Log('XMPParser', 'WARN', 'Could not find xmpmeta/xapmeta start-tag' );
				return null;
			}
			$xmpdata_end=strpos($source,'</x:xapmeta>',$xmpPack);
		}
		if( $xmpdata_end === false )  {
			LogHandler::Log('XMPParser', 'WARN', 'Could not find xmpmeta/xapmeta end-tag' );
			return null;
		}
		$xmplength=$xmpdata_end-$xmpdata_start;
		if( $xmplength > 0 ) {
			// Get the XMP piece and put it into XML object
			$xmpdata=substr($source,$xmpdata_start,$xmplength+12);
		
			try{
				$sxe=@new SimpleXMLElement($xmpdata);
			} catch( Exception $e) {
				LogHandler::logRaw(
					'XMPParser',
					'WARN',
					LogHandler::encodeLogMessage( "XMP invalid XML: {$e->getMessage}\r\n" ).LogHandler::composeCodeBlock( $xmpdata ) );
				return null;
			}
		}
		return $sxe;
	}
	
	/**
	 * Reads all XMP data (XML tree) from given file contents.
	 * Some files (like INDD) can have multiple XMP packages,
	 * we look for all the xmpmeta with <?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"
	 * To keep code simple we first look for W5M0MpCehiHzreSzNTczkc9d and next
	 * we look for the first <x:xmpmeta" until </x:xmpmeta>
	 * the format field of XMP will be used later to compare which XMP package to be use. See EN-85926.
	 *
	 * @since v8.0.0
	 * @param string $filePath Full path of file to read XMP from.
	 * @return array SimpleXMLElement|null XML tree built from XMP. NULL when no XMP found.
	 */
	static public function readXmpFromFile( $filePath )
	{
		// Use FileStreamSearch to avoid huge memory consumption.
		require_once BASEDIR.'/server/utils/FileStreamSearch.class.php';
		$stream = new WW_Utils_FileStreamSearch( array(
			'searchHorizon'  => 536870912, // The XMP data block will be in the first 512 MB.
			'dataBlockSize'  => 5242880,   // XMP data block itself won't be more than 5 MB.
			'maxMemoryUsage' => 1048576,   // Do not use more than 1 MB for search operations.
		));
		
		// Read the XMP text data from file.
		$xmpDataBlocks = array();
		if( $stream->openFile( $filePath ) ) {
			if( $stream->searchNextData( 'W5M0MpCehiHzreSzNTczkc9d' ) ) {
				do {
					$searchStartPos = $stream->getSearchStartingPoint();
					$xmpData = $stream->searchNextDataBlock( '<x:xmpmeta', '</x:xmpmeta>' );
					if( $xmpData === false ) {
						// When no <xmpmeta> tag found, try <xapmeta> tag. But first, reset
						// the search pointer back to the header, to start searching from.
						$stream->setSearchStartingPoint( $searchStartPos );
						$xmpData = $stream->searchNextDataBlock( '<x:xapmeta', '</x:xapmeta>' );
						if( $xmpData === false ) {
							LogHandler::Log( 'XMPParser', 'WARN', 'Could not find xmpmeta/xapmeta start-tag' );
						}
					}
					$xmpDataBlocks[] = $xmpData;
				} while ( $stream->searchNextData( 'W5M0MpCehiHzreSzNTczkc9d' ) );
			} else {
				LogHandler::Log( 'XMPParser', 'DEBUG', 'No XMP found' );
			}
			$stream->closeFile();
		} else {
			LogHandler::Log( 'XMPParser', 'ERROR', 'Could not open "'.$filePath.'". No XMP data read.' );
		}
		
		// Parse the XMP data.
		$retXml = null;
		if( $xmpDataBlocks ) {
			$retXml = array();
			foreach( $xmpDataBlocks as $xmpData ) {
				try {
					$retXml[] = @new SimpleXMLElement($xmpData);
				} catch (Exception $e) {
					LogHandler::logRaw(
						'XMPParser',
						'WARN',
						LogHandler::encodeLogMessage( "XMP invalid XML: {$e->getMessage}\r\n" ).LogHandler::composeCodeBlock( $xmpData ) );
				}
			}
		}
		return $retXml;
	}
}
