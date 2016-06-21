<?php

class WormGraphvizComposer
{
	/** @var string $format Image file format to compose, 'svg' or 'pdf' */
	static protected $format;

	/**
	 * Generates an image, based on a Graphviz image definition, and saves it into a temp file.
	 * Caller is responsible to remove the temp image file after usage.
	 *
	 * @param string $render The input definition of the image to generate.
	 */
	protected function composeImage( $render )
	{
		// In case ps2pdf installed, generate ps2 to make hyperlinks clickable, then convert ps2pdf.
		if( self::$format == 'pdf' && GRAPHVIZ_PS2PDF_APPLICATION_PATH ) {
			self::$format = 'ps2';
		}

		// Compose the Digraph command for the command line.
		$errorFile = tempnam( sys_get_temp_dir(), 'orm_err_' );
		$inputFile = tempnam( sys_get_temp_dir(), 'orm_inp_' );
		$imageFile = tempnam( sys_get_temp_dir(), 'orm_out_' );
		file_put_contents( $inputFile, $render );
		
		$cmd = GRAPHVIZ_APPLICATION_PATH;
		// Commented out: With this option we have UTF-8 support, but then hyperlinks are no longer clickable.
		/*if( self::$format == 'pdf' || self::$format == 'ps2' ) {
			$cmd .= ' -Tps:cairo';
		}*/
		$cmd .= ' -T'.self::$format.
				' -o'.escapeshellarg($imageFile).' '.
				' '.escapeshellarg($inputFile).' '.
				' 2>'.escapeshellarg($errorFile);

		// Run the Digraph command on the command line.
		$returnVar = 0;
		$output = array();
		PerformanceProfiler::startProfile( 'Graphviz', 3 );
		/*$result =*/ exec( $cmd, $output, $returnVar );
		LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Command:'.$cmd );
		LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Command input:'.$render );
		LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Command output:'.print_r($output,true) );
		LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Command returnVar:'.print_r($returnVar,true) );
		PerformanceProfiler::stopProfile( 'Graphviz', 3 );

		// Log error in case the Digraph command gave error on command line.
		$error = file_get_contents( $errorFile );
		if( $error ) {
			LogHandler::Log( 'WormGraphviz', 'ERROR', 'Digraph error: '.$error );
			// ... just continue; it might be just a warning
		}

		// Remove the temp files.
		LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Removing temp error file: '.$errorFile );
		unlink( $errorFile );
		LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Removing temp input file: '.$inputFile );
		unlink( $inputFile );
		
		if( self::$format == 'ps2' && GRAPHVIZ_PS2PDF_APPLICATION_PATH ) {
			$ps2File = $imageFile; // input file
			$imageFile = basename($ps2File).'pdf'; // output file
			$cmd = GRAPHVIZ_PS2PDF_APPLICATION_PATH.' '.escapeshellarg($ps2File).' '.escapeshellarg($imageFile);
			
			$returnVar = 0;
			$output = array();
			PerformanceProfiler::startProfile( 'Graphviz', 3 );
			/*$result =*/ exec( $cmd, $output, $returnVar );
			LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Command:'.$cmd );
			LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Command output:'.print_r($output,true) );
			LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Command returnVar:'.print_r($returnVar,true) );
			PerformanceProfiler::stopProfile( 'Graphviz', 3 );
			
			self::$format = 'pdf'; // we have just converted ps2 to pdf on-the-fly
		}
		
		// Output image to client app.
		$contentType = '';
		switch( self::$format ) {
			case 'svg':
				$contentType = 'image/svg+xml';
				break;
			case 'pdf':
				$contentType = 'application/pdf';
				break;
			default:
				LogHandler::Log( 'WormGraphviz', 'ERROR', 'Unknown format:'.self::$format );
				break;
		}
		
		if( $contentType ) {
			header( 'Content-type: '.$contentType );
			readfile( $imageFile );
			// Attempt to force CS opening a clicked SVG in its own tab, but did not work:
			/*header( 'Content-type: '.$contentType );
			if( self::$format == 'svg' ) {
				$output = file_get_contents( $imageFile );
				$output = str_replace( 'xlink:href=', 'target="_parent" xlink:href=', $output );
				print $output;
			} else {
				readfile( $imageFile );
			}*/
		}

		// Remove the temp files.
		LogHandler::Log( 'WormGraphviz', 'DEBUG', 'Removing temp input file: '.$imageFile );
		unlink( $imageFile );
	}
	
	/**
	 * Composes a display label for objects.
	 *
	 * @param integer $objectId 
	 * @param string $objectName Name to display.
	 * @param string $objectType Used to pick icon.
	 * @param boolean $clickable Whether or not to underline the text to indicate the label is clickable.
	 */
	static protected function composeObjectLabel( $objectId, $objectName, $objectType, $clickable )
	{
		$title = $clickable ? '<u>'.$objectName.'</u>' : $objectName;
		return
			'<<table border="0"><tr>'.
				'<td><img src="objecticons/'.$objectType.'_16x16.png" /></td><td>'.$title.'</td>'.
			'</tr></table>>';
	}

	/**
	 * Composes an URL that can be used to link to the object progress report.
	 *
	 * @param integer $objectId 
	 * @param string $report 'objectprogressreport' or 'placementsreport'
	 * @return string URL
	 */
	static protected function composeLinkToReport( $objectId, $report )
	{
		$pluginFolder = basename(dirname(__FILE__));
		$ticket = BizSession::getTicket();
		$url = SERVERURL_ROOT.INETROOT.'/config/plugins/'.$pluginFolder.
			'/index.php?ticket='.$ticket.'&command='.$report.'&id='.intval($objectId).'&format='.self::$format;
		return self::escapeUrl( $url );
	}

	/**
	 * Composes an URL that can be used to link to the preview of an image.
	 *
	 * @param integer $objectId 
	 * @return string URL
	 */
	static protected function composeLinkToImage( $objectId )
	{
		$ticket = BizSession::getTicket();
		$url = SERVERURL_ROOT . INETROOT . '/server/apps/image.php?ticket='.$ticket.
				'&id='.$objectId.'&rendition=eMagPreview';
		return self::escapeUrl( $url );
	}

	/**
	 * Composes an URL that can be used to link to the preview of a page.
	 *
	 * @param integer $objectId 
	 * @param string $pageNr 
	 * @return string URL
	 */
	static protected function composeLinkToPage( $objectId, $pageNr )
	{
		$ticket = BizSession::getTicket();
		$url = SERVERURL_ROOT . INETROOT . '/server/apps/image.php?ticket='.$ticket.
			'&id='.$objectId.'&rendition=eMagPreview&page='.$pageNr;
		return self::escapeUrl( $url );
	}

	/**
	 * Escapes a given URL when format is SVG. For PDF, no action is taken.
	 *
	 * @param string $url To escape. 
	 * @return string Escaped url.
	 */
	static protected function escapeUrl( $url )
	{
		if( self::$format == 'svg' ) {
			$url = str_replace( '&', '&amp;', $url );
		}
		return $url;
	}
}