<?php

class MimeTypeHandler
{
	/**
	 * Returns the file extension of $filename
	 *
	 * @param string $filename
	 * @return string
	 */
	private static function getFileExt($filename)
	{
		//need to check for existence of '.' first, return '' if no '.' found
		if (!strrpos($filename,'.')) { 
			return '';
		}
		$explodedFile = explode('.', $filename);
		$fileext = array_pop($explodedFile);
		return strtolower('.' . $fileext);
	}
	
	/**
	 * Takes care that the EXTENSIONMAP is only serialized once
	 *
	 * @return array
	 */
	public static function getExtensionMap()
	{
		static $ExtensionMap;
		if (!isset($ExtensionMap)) {
			$ExtensionMap = unserialize(EXTENSIONMAP);
		}
		return $ExtensionMap;
	}

	/**
	 * Derives file extension from given mime type (using EXTENSIONMAP, new in v5.0)
	 * If there are more extensions for a given mimetype, the first entry in EXTENSIONMAP with that mimetype is returned.
	 *
	 * @param string $mimetype
	 * @param string|null $objtype
	 * @return int|string
	 */
	public static function mimeType2FileExt( $mimetype, $objtype=null )
	{
		$map = self::getExtensionMap();
		foreach ($map as $ext => $mimeandobjtype) {
			if( is_null($objtype) ) {
				if ($mimetype == $mimeandobjtype[0]) {
					return $ext;
				}
			} else {
				if ($mimetype == $mimeandobjtype[0] && $objtype == $mimeandobjtype[1]) {
					return $ext;
				}
			}
		}
		return '';
	}

	/**
	 * Derives mime type from given file extension (using EXTENSIONMAP, new in v5.0)
	 *
	 * @param string $fileext
	 * @return string
	 */
	public static function fileExt2MimeType( $fileext )
	{
		$map = self::getExtensionMap();
		if (array_key_exists($fileext, $map)) {
			return $map[$fileext][0];
		}
		return '';
	}

	/**
	 * Derives mime type from given file path (using file extension and EXTENSIONMAP)
	 *
	 * @param string $filePath
	 * @return string
	 */
	public static function filePath2MimeType( $filePath )
	{
		$fileExt = self::getFileExt( $filePath );
		return MimeTypeHandler::fileExt2MimeType( $fileExt );
	}

	/**
	 * Determine the object type via filename.
	 *
	 * File-extension is leading for finding an objecttype, not mimetype.
	 *
	 * @param string $mimetype
	 * @param string $filename
	 * @param bool $repairmimetype
	 * @return string
	 */
	public static function filename2ObjType(&$mimetype, $filename, $repairmimetype = true)
	{
		$fileext = self::getFileExt($filename);
		$objtype = '';
		$map = self::getExtensionMap();
		
		if (array_key_exists($fileext, $map)) {
			$objtype = $map[$fileext][1];
			if ($repairmimetype) {
				$mimetype = $map[$fileext][0];
			}
		} else {
			//search on mimetype if no file ext found/available
			foreach ($map as $mimeandobjtype) {
				if ($mimeandobjtype[0] == $mimetype) {
					$objtype = $mimeandobjtype[1];
					break;					
				}
			}
		}
		return $objtype;
	}


	/**
	 * Maps the given mime type to object type.
	 *
	 * If mime type is not supported for upload, empty string is returned.
	 * Note that the mime can be 'repaired' based on file extention for tiff and InCopy files.
	 *
	 * @param string $mime
	 * @param string $filename
	 * @param bool $repairmimetype
	 * @return string
	 */
	public static function mime2ObjType( &$mime, $filename, $repairmimetype = true)
	{
		return self::filename2ObjType($mime, $filename, $repairmimetype);
	}

	/**
	 * Determines if the given mime indicates JPEG
	 *
	 * @param string $MimeType
	 * @return bool
	 */
	public static function isJPEG($MimeType)
	{
		return ($MimeType == "image/pjpeg" || $MimeType == "image/jpeg" || $MimeType == "image/jpg") ? true : false;
	}
}