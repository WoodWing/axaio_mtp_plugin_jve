<?php
/**
 * Class FileHandler
 */
class FileHandler
{
	protected $filepath = null;
	protected $fileurl = null;
	protected $filecontent = '';
	protected $fileencoding = null;
	protected $filesize = 0;
	protected $filehandle = null;
	protected $extention = null;
	private   $bomLen = 0; // There could be an encoding marker at start of file

	public function __construct( $filepath = null )
	{
		if( !is_null( $filepath ) ) {
			$this->filepath = $filepath;
			if( file_exists($filepath) === true ) {
				$this->filesize = filesize($filepath);
				$this->fillExtention();
			}
		}
	}

	public function __destruct(){
	}

	private function fillExtention()
	{
		$pieces = explode('.', $this->filepath);
		$this->extention = array_pop($pieces);
	}

	/*
	 * Indicates if the file (as given in de the constructor) exists on disk.
	 *
	 * @return boolean
	 */
	public function exists()
	{
		return file_exists( $this->filepath );
	}

	public function readFile()
	{
		$this->filecontent = file_get_contents($this->filepath);
		$this->detectEncoding();
	}

	public function readFileByChunkSize()
	{
		if( ($fileInput = fopen( $this->filepath, 'rb' )) ) {
			$fileSize = filesize( $this->filepath );
			$bufSize = self::getBufferSize( $fileSize );
			while( !feof($fileInput) ) {
				$this->filecontent .= fread( $fileInput, $bufSize );
			}
			fclose( $fileInput );
		}
	}

	public function convertEncoding( $targetEncoding = 'UTF-8' )
	{
		if(mb_strlen($this->filecontent) != 0){
			if( $this->bomLen > 0 ) { // BOM detected, so exclude the BOM
				$content = substr( $this->filecontent, $this->bomLen ); // do NOT use mb_substr since we are skipping bytes here!
			} else { // No BOM
				$content = $this->filecontent;
			}
			$this->filecontent = mb_convert_encoding( $content, $targetEncoding, $this->getFileEncoding() );
		}
	}

	private function detectEncoding()
	{
		$this->bomLen = 0;
		$this->fileencoding = '';
		if( mb_strlen( $this->filecontent ) > 0 ) {
			$enc = self::detect_UTF_BOM( $this->bomLen, $this->filecontent );
			if( $this->bomLen > 0 ) { // The BOM determines the encoding
				$this->fileencoding = $enc;
			} else { // When no BOM, let's handle over to PHP's best guessing
				$this->fileencoding = mb_detect_encoding($this->filecontent, 'UTF-8, UTF-16BE, ISO-8859-1, UTF-16, UTF-16LE');
			}
		}
	}

	public function getFileEncoding()
	{
		return $this->fileencoding;
	}

	public function getFileContent()
	{
		return $this->filecontent;
	}

	public function getFileSize()
	{
		return $this->filesize;
	}

	public function getExtention()
	{
		return $this->extention;
	}

	public function getFilePath()
	{
		return $this->filepath;
	}

	public function getFileUrl()
	{
		return $this->fileurl;
	}

	public function openFile($path, $opentype = 'w+')
	{
		$opened = false;
		if(!is_resource($this->filehandle)){
			$this->filepath = $path;
			$this->fillExtention();
			$this->filesize = file_exists($path) ? filesize($path) : 0;
			$this->filehandle = fopen($path, $opentype);
			$opened = ($this->filehandle !== false);
		}
		return $opened;
	}

	public function closeFile()
	{
		fclose($this->filehandle);
	}

	public function writeFile($path, &$content, $opentype = 'w+')
	{
		$wrote = false;
		if( $this->openFile($path, $opentype) ) {
			$wrote = (fwrite( $this->filehandle, $content ) !== false);
			$this->closeFile();
		}
		return $wrote;
	}

	/**
	 * Get buffer chunk size for read/write file 
	 * 
	 * To avoid looping too many times for chucked up/downloads, choose smart buffer size. 
     * Let's loop max 16 times under 256MB file size. Larger files will take significant
	 * up/download time for which looping won't be the bottleneck (the network throughput will be).
	 *
	 */
	public static function getBufferSize( $fileSize )
	{
		if( $fileSize > 51200 ) {  // > 50K
			$bufSize = ( $fileSize > 16777216 ) ? 16777216 : 1048576; // 16MB or 1MB
		} else {
			$bufSize = 4096; // 4K
		}
		return $bufSize;
	}

	/*
	 * Determine if there an UTF8/16/32 BOM (file encoding prefix chars). <br>
	 * Detects also Little Endian or Big Endian. <br>
	 *
	 * BOMs to detect: <br>
	 * 00 00 FE FF    UTF-32, big-endian <br>
	 * FF FE 00 00    UTF-32, little-endian <br>
	 * FE FF          UTF-16, big-endian <br>
	 * FF FE          UTF-16, little-endian <br>
	 * EF BB BF       UTF-8	 <br>
	 
	 * @param string $bomLen  Returns number of chars representing the BOM. Zero when no BOM.
	 * @param string $str     (multibyte) data that might contain a BOM.
	 * @return string         Encoding derived from BOM. Can be 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-32LE' or 'UTF-32BE'. Empty when BOM not present.
	 */
	public static function detect_UTF_BOM( &$bomLen, $str )
	{
		$bomLen = 0;
		$strLen = strlen($str);
		if( $strLen < 2 ) {
			return ''; // no BOM found
		}
	   $c0 = ord($str[0]);
	   $c1 = ord($str[1]);
	   if( $strLen >= 3 ) {
		   $c2 = ord($str[2]);
		   if( $strLen >= 4 ) {
			   $c3 = ord($str[3]);
			}
		}
		
		// For paranoid debugging purposes:
		// print( 'detect_UTF_BOM: ['.dechex($c0).'-'.dechex($c1).'-'.dechex($c2).'-'.dechex($c3).']' );
		
	   if( $c0 == 0xfe && $c1 == 0xff ) {
	   	$bomLen = 2;
	   	return 'UTF-16BE'; 
	   } elseif( $c0 == 0xff && $c1 == 0xfe ) {
	   	if( isset($c2) && $c2 == 0x00 && isset($c3) && $c3 == 0x00 ) { 
	   		$bomLen = 4;
	   		return 'UTF-32LE'; 
	   	} else {
	   		$bomLen = 4;
		   	return 'UTF-16LE'; 
	   	}
	   } elseif( $c0 == 0x00 && $c1 == 0x00 && isset($c2) && $c2 == 0xfe && isset($c3) && $c3 == 0xff ) { 
	   	$bomLen = 4;
	  		return 'UTF-32BE'; 
		} elseif( $c0 == 0xef && $c1 == 0xbb && isset($c2) && $c2 == 0xbf ) {
			$bomLen = 3;
	   	return 'UTF-8';
	   }
	   return ''; // no/unknown BOM
	}
}
?>