<?php

class UtfConverter
{
	/**
	 *  Converts a plain-text-file to UTF-16BE with BOM.
	 *  The infile must have a BOM, otherwise no conversion.
	 *  This is the required format to be able to be read correctly by InCopy/InDesign
	 *
	 *  @param $infile  string Filename of the plain-text-file to be converted
	 *  @param $outfile string Filename of the destination-file
	 *  @param $overwriteifexists boolean If the destination-file exists, overwrite it
	 *  @return boolean true if conversion succeeded, false if some error
	**/  
	static public function convert2UTFAdobe( $infile, $outfile, $overwriteifexists = false )
	{
		// Validate parameters
		if( !file_exists( $infile ) ) return false;
		if( !$overwriteifexists && file_exists( $outfile ) ) return false;
		$retVal = false;

		// Read file and convert to UTF-16BE in memory
		require_once BASEDIR.'/server/utils/FileHandler.class.php';
		$fh = new FileHandler();
		if( $fh->openFile( $infile, 'r' ) ) { // determine path and access mode

			$fh->readFile(); // determine content + encoding
			if( $fh->getFileEncoding() != 'UTF-16BE' ) {
				$fh->convertEncoding( 'UTF-16BE' ); // make UTF-16BE
			}
			$newBom = chr(0xFE) . chr(0xFF); // UTF-16BE BOM
			$outstring =  $newBom . $fh->getFileContent();
			$fh->closeFile();

			// Write converted file
			if( file_exists( $outfile ) && $overwriteifexists ) {
				unlink( $outfile );
			}
			$fpout = fopen( $outfile, 'w' );
			if( $fpout ) {
				fwrite( $fpout, $outstring );
				fclose( $fpout );
				$retVal = true;
			}
		}
		return $retVal;
	}
}
?>