<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Class to hold one DIME record of a DIME message.
 *
 */
class BizDimeRecord
{
	const VERSION = 1;

	const FLAG_TYPE_UNCHANGED = 0x00;
	const FLAG_TYPE_MEDIA = 0x10;
	const FLAG_TYPE_URI = 0x20;
	const FLAG_TYPE_UNKNOWN = 0x30;
	const FLAG_TYPE_NONE = 0x40;

	const FLAG_CHUNK = 0x0100;
	const FLAG_END = 0x0200;
	const FLAG_BEGIN = 0x0400;

	const HEADER_LEN = 12;

	const COPY_CHUNK = 102400;

	protected $Flags = 0;
	protected $OptsLen = 0;
	protected $IdLen = 0;
	protected $TypeLen = 0;
	protected $DataLen = 0;
	public $RecordLen = 0;
	protected $Options = '';
	public $Id = '';
	protected $Type = '';
	protected $Data = null;
	protected $DataFilePath = null;
	protected $DataOffset = 0;

	/**
	 * DIME length fields must be padded so that the length of the field is a multiple of a 4-byte interval
	 *
	 * @param int $length
	 * @return int
	 */
	public static function padLength4Bytes( $length )
	{
		$pad = $length % 4;
		if ($pad > 0) {
			$pad = 4 - $pad;
		}
		return $length + $pad;
	}

	/**
	 * Return the ID of this record.
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->Id;
	}

	/**
	 * Reads 'this' record from the provided file handle.
	 *
	 * @param resource $handle
	 * @throws Exception
	 */
	public function read( $handle )
	{
		// first 12 bytes contains the dime header
		$data = fread( $handle, self::HEADER_LEN );
		if ($data === FALSE || strlen( $data ) < self::HEADER_LEN) {
			throw new Exception( 'ERR_ERROR', 'Client', 'Could not read or invalid DIME record' );
		}
		$this->Flags = (hexdec( bin2hex( $data[0] ) ) << 8) + hexdec( bin2hex( $data[1] ) );
		$this->OptsLen = (hexdec( bin2hex( $data[2] ) ) << 8) + hexdec( bin2hex( $data[3] ) );
		$this->IdLen = (hexdec( bin2hex( $data[4] ) ) << 8) + hexdec( bin2hex( $data[5] ) );
		$this->TypeLen = (hexdec( bin2hex( $data[6] ) ) << 8) + hexdec( bin2hex( $data[7] ) );
		$this->DataLen = (hexdec( bin2hex( $data[8] ) ) << 24) + (hexdec( bin2hex( $data[9] ) ) << 16) + (hexdec(
					bin2hex( $data[10] ) ) << 8) + hexdec( bin2hex( $data[11] ) );
		$padOptsLen = self::padLength4Bytes( $this->OptsLen );
		$padIdLen = self::padLength4Bytes( $this->IdLen );
		$padTypeLen = self::padLength4Bytes( $this->TypeLen );
		$padOptsIdTypeLen = $padOptsLen + $padIdLen + $padTypeLen;
		$this->RecordLen = self::HEADER_LEN + $padOptsIdTypeLen + self::padLength4Bytes( $this->DataLen );
		if ($padOptsIdTypeLen > 0) {
			// read options, id and type
			$data = fread( $handle, $padOptsIdTypeLen );
			if ($data === FALSE) {
				throw new Exception( 'ERR_ERROR', 'Client', 'Could not read options from DIME record' );
			}
			$p = 0;
			$this->Options = substr( $data, $p, $this->OptsLen );
			$p += $padOptsLen;
			$this->Id = substr( $data, $p, $this->IdLen );
			$p += $padIdLen;
			$this->Type = substr( $data, $p, $this->TypeLen );
		}
		// with chunks, id, type and options are not set
		$this->DataOffset = ftell( $handle );
	}

	/**
	 * Reads the data from the current offset for this record.
	 *
	 * @param resource $handle
	 */
	public function readData ( $handle )
	{
		if (is_null( $this->Data ) && $this->DataLen > 0) {
			fseek( $handle, $this->DataOffset );
			$this->Data = fread( $handle, $this->DataLen );
		}
	}

	/**
	 * Writes 'this' record to the given file handle.
	 *
	 * @param resource $handle
	 */
	public function write( $handle )
	{
		// set version
		$this->Flags |= self::VERSION << 11;
		$this->OptsLen = strlen( $this->Options );
		$this->IdLen = strlen( $this->Id );
		$header = pack( 'nnnnN', $this->Flags, $this->OptsLen, $this->IdLen, $this->TypeLen, $this->DataLen );
		fwrite( $handle, $header, self::HEADER_LEN );
		$pad = "\0\0\0\0";
		if ($this->OptsLen > 0) {
			$padLen = self::padLength4Bytes( $this->OptsLen );
			fwrite( $handle, $this->Options, $this->OptsLen );
			fwrite( $handle, $pad, ($padLen - $this->OptsLen) );
		}
		if ($this->IdLen > 0) {
			$padLen = self::padLength4Bytes( $this->IdLen );
			fwrite( $handle, $this->Id, $this->IdLen );
			fwrite( $handle, $pad, ($padLen - $this->IdLen) );
		}
		if ($this->TypeLen > 0) {
			$padLen = self::padLength4Bytes( $this->TypeLen );
			fwrite( $handle, $this->Type, $this->TypeLen );
			fwrite( $handle, $pad, ($padLen - $this->TypeLen) );
		}
		if ($this->DataLen > 0) {
			$padLen = self::padLength4Bytes( $this->DataLen );
			// use data or a filepath
			if (is_null( $this->Data ) && ! is_null( $this->DataFilePath )) {
				// filepath
				if (($fp = fopen( $this->DataFilePath, 'rb' ))) {
					while (! feof( $fp )) {
						$data = fread( $fp, self::COPY_CHUNK );
						if ($data != FALSE) {
							fwrite( $handle, $data );
						} else {
							break;
						}
					}
					fwrite( $handle, $pad, ($padLen - $this->DataLen) );
					fclose( $fp );
				}
			} else {
				fwrite( $handle, $this->Data, $this->DataLen );
				fwrite( $handle, $pad, ($padLen - $this->DataLen) );
			}
		}
	}

	/**
	 * Determines the length of the DIME record.
	 *
	 * @return int Byte count.
	 */
	public function getRecordLength()
	{
		$len = self::HEADER_LEN + self::padLength4Bytes( strlen( $this->Options ) ) + self::padLength4Bytes(
				strlen( $this->Id ) ) + self::padLength4Bytes( $this->TypeLen ) + self::padLength4Bytes( $this->DataLen );
		return $len;
	}

	/**
	 * Sets the pointer to the chunk that contains the message.
	 */
	public function setMsgChunk()
	{
		$this->Flags |= self::FLAG_CHUNK;
	}

	/**
	 * Checks whether or not the pointer is at the chunk that contains the message.
	 *
	 * @return bool
	 */
	public function isMsgChunk()
	{
		return (bool)($this->Flags & self::FLAG_CHUNK);
	}

	/**
	 * Flags the beginning of the message.
	 */
	public function setMsgBegin ()
	{
		$this->Flags |= self::FLAG_BEGIN;
	}

	/**
	 * Flag the end of the message.
	 */
	public function setMsgEnd ()
	{
		$this->Flags |= self::FLAG_END;
	}

	/**
	 * Sets the type of the chunk.
	 *
	 * @param string $typeStr
	 * @param int $typeFlag
	 */
	public function setType( $typeStr, $typeFlag = self::FLAG_TYPE_UNKNOWN )
	{
		$this->Type = $typeStr;
		$this->TypeLen = strlen( $typeStr );
		$this->Flags |= $typeFlag;
	}

	/**
	 * Sets the data of the chunk.
	 *
	 * @param string $data
	 * @param int $length
	 */
	public function setData( $data, $length = -1 )
	{
		$this->Data = $data;
		$this->DataFilePath = null;
		if( $length == - 1 ) {
			$this->DataLen = strlen( $data );
		} else {
			$this->DataLen = $length;
		}
	}

	/**
	 * Defines the file path where the data will be stored.
	 *
	 * @param string $filePath
	 */
	public function setDataFilePath( $filePath )
	{
		$this->DataFilePath = $filePath;
		$this->Data = null;
		$this->DataLen = filesize( $this->DataFilePath );
	}

	/**
	 * Returns the data that was in a chunk.
	 *
	 * @return string The data.
	 */
	public function getData()
	{
		return $this->Data;
	}

	/**
	 * Returns the file path where the data of the chunks are stored.
	 *
	 * @return string File path.
	 */
	public function getDataFilePath()
	{
		return $this->DataFilePath;
	}
}