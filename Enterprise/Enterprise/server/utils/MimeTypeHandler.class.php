<?php

class MimeTypeHandler
{
	// Returns the file extension of $filename
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
	
	//Takes care that the EXTENSIONMAP is only serialized once
	public static function getExtensionMap()
	{
		static $ExtensionMap;
		if (!isset($ExtensionMap)) {
			$ExtensionMap = unserialize(EXTENSIONMAP);
		}
		return $ExtensionMap;
	}

	// Derives file extension from given mime type (using EXTENSIONMAP, new in v5.0)
	// If there are more extensions for a given mimetype, the first entry in EXTENSIONMAP with that mimetype is returned.
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

	// Derives mime type from given file extension (using EXTENSIONMAP, new in v5.0)
	public static function fileExt2MimeType( $fileext )
	{
		$map = self::getExtensionMap();
		if (array_key_exists($fileext, $map)) {
			return $map[$fileext][0];
		}
		return '';
	}

	// Derives mime type from given file path (using file extension and EXTENSIONMAP)
	public static function filePath2MimeType( $filePath )
	{
		$fileExt = self::getFileExt( $filePath );
		return MimeTypeHandler::fileExt2MimeType( $fileExt );
	}

	/*
		-NATIVE2DBNAME-
		Input: a filename, optionally including an extension.
		Output: a filename, without the extension.
		The output filename can be used for the database,
		or as a base name for a preview or thumbnail filename (by appending .jpg)
	*/
	public static function native2DBname($filename, $mimeType )
	{
		$filename = basename($filename);
		//strip extension (if the extension is known)
		if ( $mimeType )
		{
			$ext = MimeTypeHandler::mimeType2FileExt( $mimeType );
			$l = strlen($ext);
			if ( $l )
			{
				if ( strtolower(substr( $filename, -$l, $l )) == $ext ){
					$filename = substr( $filename, 0, -$l );
				}else{
					//type changed!

				}
			}
		}
		else
		{
			//find last dot in filename
			$strpos = strrpos( $filename, '.' );
			if ( $strpos !== FALSE ){
				$filename = substr( $filename, 0, $strpos-1 );
			}
		}
		return $filename;
	}

	// File-extension is leading for finding an objecttype, not mimetype
	// Attention! in some cases different objecttypes are returned in v5 than in v4
	// But this only happens in 'idiot' situations, for example: application/illustrator with .doc extension, v4: 'Image'; v5: 'Article';
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
		}
		else {
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


	// Maps given mime type to object type.
	// If mime type is not supported for upload, empty string is returned.
	// Note that the mime can be 'repaired' based on file extention for tiff and InCopy files.
	public static function mime2ObjType( &$mime, $filename, $repairmimetype = true)
	{
		return self::filename2ObjType($mime, $filename, $repairmimetype);
	}

	// Determines if the given mime indicates JPEG
	public static function isJPEG($MimeType)
	{
		return ($MimeType == "image/pjpeg" || $MimeType == "image/jpeg" || $MimeType == "image/jpg") ? true : false;
	}
}
?>