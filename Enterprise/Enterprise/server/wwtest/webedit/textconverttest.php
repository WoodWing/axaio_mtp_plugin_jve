<?php

require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/utils/StopWatch.class.php';
require_once BASEDIR.'/server/utils/FileHandler.class.php';
require_once BASEDIR.'/server/utils/FolderUtils.class.php';
require_once BASEDIR.'/server/utils/FolderInterface.intf.php';

require_once BASEDIR.'/server/appservices/textconverters/TextConverter.class.php';

define( 'WE_TEST_DATA_IN', 'testdata' );
define( 'WE_TEST_DATA_OUT', 'testdata/out' );

set_time_limit(3600);

class WebEditorTest implements FolderIterInterface
{	
	private $InputFiles = null;
	
	public function __construct()
	{
		// Create output folder
		if( !file_exists( WE_TEST_DATA_OUT ) ) {
			if( !mkdir( WE_TEST_DATA_OUT, 0777 ) ) {
				print "<font color='#ff0000'><b>Error:</b> Failed to create sub folder in data folder.</font><br>";
				die();
			}
		}

		// Collect files to convert
		$this->InputFiles = array();
		FolderUtils::scanDirForFiles( $this, WE_TEST_DATA_IN, array( 'txt' , 'wwcx' ) );

		// Convert all files in the inout folder and write conversions in the output folder
		$watch = new StopWatch();
		$watch->Start();
		
		$fc = new TextConverter();
		$fc->import( $this->InputFiles ); 
		print 'InCopyTest: Import done: '.$watch->Fetch().'<br/>';
		$watch->Pause();
		
		$files = array();
		foreach( $this->InputFiles as $file ) {
			$path_parts = pathinfo( $file['FilePath'] );
			$icFile = $path_parts['basename'];
			print 'InCopyTest: Handling: '.$icFile.'<br/>';
			// Output InCopy files into HTML frames (one file per frame)
			$xFrames = $file['HtmlFrames'];
			foreach( $xFrames as $key => $xFrame ) {
				$contents = $xFrame->saveXML();
				$this->beaufifyXML( $contents );
				$fh = new FileHandler( $icFile );
				$fh->writeFile( WE_TEST_DATA_OUT.'/'.$icFile.'.out'.$key.'.html', $contents ); 
			}

			// Convert the files to InCopy and plain text
			copy( WE_TEST_DATA_IN.'/'.$icFile, WE_TEST_DATA_OUT.'/'.$icFile );
			$files[] = array( 'FilePath' => WE_TEST_DATA_OUT.'/'.$icFile, 'Format' => 'application/incopy', 'HtmlFrames' => $xFrames );
			$files[] = array( 'FilePath' => WE_TEST_DATA_OUT.'/'.$icFile, 'Format' => 'text/plain', 'HtmlFrames' => $xFrames );
		}

		$watch->UnPause();
		$fc->export($files ); 
		print 'InCopyTest: Export done: '.$watch->Fetch().'<br/>';
	}

	public function iterFile( $filePath, $level )
	{
		if( $level > 0 ) return; // only one ply to avoid parsing output folders that might be subfolders
		$this->InputFiles[] = array( 'FilePath' => $filePath, 'Format' => 'application/incopy', 'HtmlFrames' => null );
	}
	
	public function skipFile( $filePath, $level )
	{
		$filePath = $filePath; $level = $level; // heep analyzer happy
	}
	
	public function iterFolder( $folderPath, $level )
	{
		$folderPath = $folderPath; $level = $level; // heep analyzer happy
	}

	public function skipFolder( $folderPath, $level )
	{
		$folderPath = $folderPath; $level = $level; // heep analyzer happy
	}

	private function beaufifyXML( &$xml )
	{
		$offset = 0;
		$indent = 0;
		while( ($stt = mb_strpos( $xml, '<', $offset )) !== false ) { 
			if( ( $end = mb_strpos( $xml, '>', $stt )) !== false ) {
				$first = mb_substr( $xml, $stt+1, 1 );
				$last = mb_substr( $xml, $end-1, 1 );
				if( $first == '/') {
					$indent--;
				}
				$strIndent = str_repeat( "\t", $indent );
				$xml = 
					mb_substr( $xml, 0, $stt ).
					"\n".$strIndent.
					mb_substr( $xml, $stt, $end+1-$stt ).
					//"\n".
					mb_substr( $xml, $end+1 );
				$offset = $end + 1 + strlen($strIndent);
				if( $first == '/') {
					// indent done
				} else if( $last == '/' ) {
					// no indent
				} else if( $first != '?' ) {
					$indent++;
				}
			}
			$offset += 1;
		}
	}
}

$wet = new WebEditorTest();

?>