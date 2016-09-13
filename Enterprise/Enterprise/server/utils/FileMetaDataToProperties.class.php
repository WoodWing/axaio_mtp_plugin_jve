<?php
/**
 * @package 	Enterprise
 * @subpackage 	utils
 * @since 		v9.7
 * @deprecated v10.1.0 This file is deprecated and should be removed with v11.
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Includes two wrapper classes; one for the convert XMP metadata class and one for the convert IPTC metadata class.
 * Also includes a factory class to create the one of those wrapper classes.
 */

/**
 * @deprecated v10.1.0 This class is deprecated and should be removed with v11.
 */
class WW_Utils_FileMetaDataToProperties_Factory
{
	/**
	 * Creates a utility to convert file (image) metadata to Enterprise properties. The source of the metadata can
	 * be, for example, an IPTC block or the XMP data of a file.
	 *
	 * @param string $source Type of (image) metadata.
	 * @return null|WW_Utils_FileIPTCDataToProperties|WW_Utils_FileXMPDataToProperties
	 */
	static public function createConverter( $source )
	{
		if ( $source == 'iptc' ) {
			return new WW_Utils_FileIPTCDataToProperties();
		} elseif ( $source == 'xmp' ) {
			return new WW_Utils_FileXMPDataToProperties();
		}

		return null;
	}
}

/**
 * @deprecated v10.1.0 This class is deprecated and should be removed with v11.
 */
abstract class WW_Utils_FileMetaDataToProperties
{

	/**
	 * Converts metadata extracted from a file to Enterprise properties.
	 *
	 * @param $imageMetaData Extracted metadata.
	 * @param array $metaData Array with key/value pairs of Enterprise properties and their values.
	 */
	abstract public function convert( $imageMetaData, &$metaData );
}

/**
 * @deprecated v10.1.0 This class is deprecated and should be removed with v11.
 */
class WW_Utils_FileIPTCDataToProperties extends  WW_Utils_FileMetaDataToProperties
{
	/**
	 * Converts metadata extracted from a file to Enterprise properties.
	 *
	 * @param array $iptcData Extracted IPTC metadata.
	 * @param array $metaData Array with key/value pairs of Enterprise properties and their values.
	 */
	public function convert( $iptcData, &$metaData )
	{
		require_once BASEDIR.'/server/utils/UtfString.class.php';
		if( !array_key_exists('Description', $metaData ) && array_key_exists("2#120",$iptcData) ) {	// IPTC Caption
			$metaData['Description'] = UtfString::smart_utf8_encode($iptcData["2#120"][0]);
		}
		if( !array_key_exists('DescriptionAuthor', $metaData ) && array_key_exists("2#122",$iptcData) ) {	// IPTC Caption
			$metaData['DescriptionAuthor'] = UtfString::smart_utf8_encode($iptcData["2#122"][0]);
		}
		if( !array_key_exists('Author', $metaData ) && array_key_exists("2#080",$iptcData) ) {	// IPTC Byline
			$metaData['Author'] = UtfString::smart_utf8_encode($iptcData["2#080"][0]);
		}
		if( !array_key_exists('Copyright', $metaData ) && array_key_exists("2#116",$iptcData) ) {
			$metaData['Copyright'] = UtfString::smart_utf8_encode($iptcData["2#116"][0]);
		}
		if( !array_key_exists('Created', $metaData ) && array_key_exists("2#055",$iptcData) ) {
			$metaData['Created'] = UtfString::smart_utf8_encode($iptcData["2#055"][0]);
		}
		if( !array_key_exists('Keywords', $metaData ) && array_key_exists("2#025",$iptcData) ) {
			$iptcKeywords = $iptcData["2#025"];
			if( !empty($iptcKeywords) && is_array($iptcKeywords) ) {
				$metaData['Keywords'] = array();
				foreach( $iptcKeywords as $kw ) {
					$metaData['Keywords'][] = UtfString::smart_utf8_encode( $kw );
				}
			}
		}
		if( !array_key_exists('Credit', $metaData ) && array_key_exists("2#110",$iptcData) ) {
			$metaData['Credit'] = UtfString::smart_utf8_encode($iptcData["2#110"][0]);
		}
		if( !array_key_exists('Source', $metaData ) && array_key_exists("2#115",$iptcData) ) {
			$metaData['Source'] = UtfString::smart_utf8_encode($iptcData["2#115"][0]);
		}
		if( !array_key_exists('Slugline', $metaData ) && array_key_exists("2#105",$iptcData) ) {  // IPTC Headline
			$metaData['Slugline'] = UtfString::smart_utf8_encode($iptcData["2#105"][0]);
		}
		if( !array_key_exists('Urgency', $metaData ) && array_key_exists("2#010",$iptcData) ) {
			$metaData['Urgency'] = UtfString::smart_utf8_encode($iptcData["2#010"][0]);
		}
		if( !array_key_exists('Comment', $metaData ) && array_key_exists("2#040",$iptcData) ) {   // IPTC Special instructions
			// Seen sample image with '0' as comment, so filter these out
			if( !is_numeric($iptcData["2#040"][0]) ) {
				$metaData['Comment'] = UtfString::smart_utf8_encode($iptcData["2#040"][0]);
			}
		}
		if( !array_key_exists('DocumentID', $metaData ) && array_key_exists("1#100",$iptcData) ) {   // IPTC UNO
			$metaData['DocumentID'] = UtfString::smart_utf8_encode($iptcData["1#100"][0]);
		}
	}
}

/**
 * @deprecated v10.1.0 This class is deprecated and should be removed with v11.
 */
class WW_Utils_FileXMPDataToProperties extends  WW_Utils_FileMetaDataToProperties
{
	/**
	 * Converts metadata extracted from a file to Enterprise properties.
	 *
	 * @param SimpleXMLElement $xmpData Extracted XMP metadata.
	 * @param array $metaData Array with key/value pairs of Enterprise properties and their values.
	 */
	public function convert( $xmpData, &$metaData )
	{
			// Register namespaces that we need with the XML parser:
			$xmpData->registerXPathNamespace( 'xap', 'http://ns.adobe.com/xap/1.0/' );
//			$xmp->registerXPathNamespace('Iptc4xmpCore','http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/' );
			$xmpData->registerXPathNamespace( 'exif', 'http://ns.adobe.com/exif/1.0/' );
			$xmpData->registerXPathNamespace( 'tiff', 'http://ns.adobe.com/tiff/1.0/' );
			$xmpData->registerXPathNamespace( 'photoshop', 'http://ns.adobe.com/photoshop/1.0/' );
			$xmpData->registerXPathNamespace( 'xapRights', 'http://ns.adobe.com/xap/1.0/rights/' );
			$xmpData->registerXPathNamespace( 'xapMM', 'http://ns.adobe.com/xap/1.0/mm/' );
			$xmpData->registerXPathNamespace( 'dc', 'http://purl.org/dc/elements/1.1/' );
			$xmpData->registerXPathNamespace( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );

			$this->getXMPValue( $metaData, 'Author',	'string', $xmpData, '//dc:creator//rdf:li' );
			$this->getXMPValue( $metaData, 'Description', 'string', $xmpData, '//dc:description//*[@xml:lang="x-default"]' ); // Take default language.
			$this->getXMPValue( $metaData, 'Copyright', 'string', $xmpData, '//dc:rights//*[@xml:lang="x-default"]' ); // Take default language.
			$this->getXMPValue( $metaData, 'Format', 'string', $xmpData, '//dc:format' );
			if( !isset($metaData['Format'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Format',	'attrstring', 	$xmpData, '//rdf:Description', 'http://purl.org/dc/elements/1.1/', 'format' );
			}
			$this->getXMPValue( $metaData, 'Keywords', 'list', $xmpData, '//dc:subject//rdf:li' );
			$this->getXMPValue( $metaData, 'Rating', 'string', $xmpData, '//xap:Rating' );
			if( !isset($metaData['Rating'] ) ) {
				$this->getXMPValue( $metaData, 'Rating',	'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/xap/1.0/', 'Rating' );
			}
			if( isset($metaData['Rating'] ) ) {
				$metaData['Rating'] = intval( $metaData['Rating'] ); // BZ#35029 Make sure that Rating is an integer.
			}
			$this->getXMPValue( $metaData, 'Created', 'string', $xmpData, '//xap:CreateDate' );
			if( !isset($metaData['Created'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Created','attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/xap/1.0/', 'CreateDate' );
			}
			$this->getXMPValue( $metaData, 'DocumentID', 'string', $xmpData, '//xapMM:DocumentID' );
			if( !isset($metaData['DocumentID'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'DocumentID',	'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/xap/1.0/mm/', 'DocumentID' );
			}
			$this->getXMPValue( $metaData, 'CopyrightURL', 'string', $xmpData, '//xapRights:WebStatement' );
			if( !isset($metaData['CopyrightURL'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'CopyrightURL', 'attrstring',	$xmpData, '//rdf:Description', 'http://ns.adobe.com/xap/1.0/rights/', 'WebStatement' );
			}
			$this->getXMPValue( $metaData, 'CopyrightMarked', 'string', $xmpData, '//xapRights:Marked' );
			if( !isset($metaData['CopyrightMarked'] ) ) {// Try to find alternative way.
				$this->getXMPValue( $metaData, 'CopyrightMarked', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/xap/1.0/rights/', 'Marked' );
			}
			$this->getXMPValue( $metaData, 'ColorSpace', 'string', $xmpData, '//photoshop:ColorMode' );
			if( !isset($metaData['ColorSpace'] ) ) {// Try to find alternative way.
				$this->getXMPValue( $metaData, 'ColorSpace', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/photoshop/1.0/', 'ColorMode' );
			}
			if( isset( $metaData['ColorSpace'] ) ) {  // Be aware: empty won't work here because string with contents '0' would give true
				// Map PS colormode to Enterprise ColorSpace
				switch( $metaData['ColorSpace'] ) {
					case '0': $metaData['ColorSpace'] = 'Bitmap';		break;
					case '1': $metaData['ColorSpace'] = 'Grayscale';	break;
					case '2': $metaData['ColorSpace'] = 'Indexed';		break;
					case '3': $metaData['ColorSpace'] = 'RGB';			break;
					case '4': $metaData['ColorSpace'] = 'CMYK';			break;
					case '7': $metaData['ColorSpace'] = 'Multichannel';	break;
					case '8': $metaData['ColorSpace'] = 'Duotone';		break;
					case '9': $metaData['ColorSpace'] = 'Lab';			break;
				}
			}
			$this->getXMPValue( $metaData, 'DescriptionAuthor', 'string', $xmpData, '//photoshop:CaptionWriter' );
			if( empty($metaData['DescriptionAuthor'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'DescriptionAuthor', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/photoshop/1.0/', 'CaptionWriter' );
			}
			$this->getXMPValue( $metaData, 'Slugline', 'string', $xmpData, '//photoshop:Headline' );
			if( empty($metaData['Slugline'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Slugline', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/photoshop/1.0/', 'Headline' );
			}
			$this->getXMPValue( $metaData, 'Credit', 'string', $xmpData, '//photoshop:Credit' );
			if( empty($metaData['Credit'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Credit', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/photoshop/1.0/', 'Credit' );
			}
			$this->getXMPValue( $metaData, 'Source', 'string', $xmpData, '//photoshop:Source' );
			if( empty($metaData['Source'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Source', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/photoshop/1.0/', 'Source' );
			}
			$this->getXMPValue( $metaData, 'Comment', 'string', $xmpData, '//photoshop:Instructions' );
			if( empty($metaData['Comment'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Comment', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/photoshop/1.0/', 'Instructions' );
			}
			$this->getXMPValue( $metaData, 'Dpi', 'eval', $xmpData, '//tiff:XResolution' );
			if( empty($metaData['Dpi'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Dpi', 'attreval', $xmpData, '//rdf:Description', 'http://ns.adobe.com/tiff/1.0/', 'XResolution' );
			}
			$this->getXMPValue( $metaData, 'Width', 'string', $xmpData, '//tiff:ImageWidth' );
			if( empty($metaData['Width'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Width', 'string', $xmpData, '//exif:PixelXDimension' );
			}
			if( empty($metaData['Width'] ) ) { // Try to find alternative way. See test image XMPVariation.jpg
				$this->getXMPValue( $metaData, 'Width', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/exif/1.0/', 'PixelXDimension' );
			}
			$this->getXMPValue( $metaData, 'Height', 'string', $xmpData, '//tiff:ImageLength' );
			if( empty($metaData['Height'] ) ) { // Try to find alternative way.
				$this->getXMPValue( $metaData, 'Height', 'string', $xmpData, '//exif:PixelYDimension');
			}
			if( empty($metaData['Height'] ) ) { // Try to find alternative way. See test image XMPVariation.jpg
				$this->getXMPValue( $metaData, 'Height', 'attrstring', $xmpData, '//rdf:Description', 'http://ns.adobe.com/exif/1.0/', 'PixelYDimension' );
			}

//			$this->getXMPValue( $metaData, 'Title', 'string', $xmpData, '//dc:title//*[@xml:lang="x-default"]');
//          Take default language.
//			$this->getXMPValue( $metaData, 'ColorSpace',	'string', $xmpData, '//exif:ColorSpace');
//          NOTE: this is different from Enterprise's ColorSpace, see colormode above
//			$this->getXMPValue( $metaData, 'datetimeoriginal', 'string', $xmpData, '//exif:DateTimeOriginal');
//			$this->getXMPValue( $metaData, 'yresolution', 'eval', $xmpData, '//tiff:YResolution');
//			$this->getXMPValue( $metaData, 'resolutionunit', 'string', $xmpData, '//tiff:ResolutionUnit');
//			$this->getXMPValue( $metaData, 'country', 'string', $xmpData, '//photoshop:Country');
//			$this->getXMPValue( $metaData, 'category', 'string', $xmpData, '//photoshop:Category//rdf:li');
//			$this->getXMPValue( $metaData, 'suppcategories',	'list', $xmpData, '//photoshop:SupplementalCategories//rdf:li');
//			$this->getXMPValue( $metaData, 'ps-created',	'string', $xmpData, '//photoshop:DateCreated');
//			$this->getXMPValue( $metaData, 'transmissionref', 'string', $xmpData, '//photoshop:TransmissionReference');
//			$this->getXMPValue( $metaData, 'orientation', 'string', $xmpData, '//tiff:Orientation');
//			$this->getXMPValue( $metaData, 'ColorProfile', 'string', $xmpData, '//photoshop:ICCProfile');
//			$this->getXMPValue( $metaData, 'captionwriterole', 'string', $xmpData, '//photoshop:AuthorsPosition');

//          There are many more, camera oriented exif fields that we don't care about
//			$this->getXMPValue( $metaData, 'genre', 'string', $xmpData, '//Iptc4xmpCore:IntellectualGenre');
//			$this->getXMPValue( $metaData, 'location', 'string', $xmpData, '//Iptc4xmpCore:Location');
//			$this->getXMPValue( $metaData, 'creatoraddress', 'string', $xmpData, '//Iptc4xmpCore:CiAdrExtadr');
//			$this->getXMPValue( $metaData, 'creatorcity', 'string', $xmpData, '//Iptc4xmpCore:CiAdrCity');
//			$this->getXMPValue( $metaData, 'creatorregion', 'string', $xmpData, '//Iptc4xmpCore:CiAdrRegion');
//			$this->getXMPValue( $metaData, 'creatorzipcode', 'string', $xmpData, '//Iptc4xmpCore:CiAdrPcode');
//			$this->getXMPValue( $metaData, 'creatorcity', 'string', $xmpData, '//Iptc4xmpCore:CiAdrCtry');
//			$this->getXMPValue( $metaData, 'creatortel', 'string', $xmpData, '//Iptc4xmpCore:CiTelWork');
//			$this->getXMPValue( $metaData, 'creatoremail', 'string', $xmpData, '//Iptc4xmpCore:CiEmailWork');
//			$this->getXMPValue( $metaData, 'creatorurl', 'string', $xmpData, '//Iptc4xmpCore:CiUrlWork');
//			$this->getXMPValue( $metaData, 'city', 'string', $xmpData, '//photoshop:City');
//			$this->getXMPValue( $metaData, 'state', 'string', $xmpData, '//photoshop:State');
//			$this->getXMPValue( $metaData, 'scene', 'list', $xmpData, '//Iptc4xmpCore:Scene//rdf:li');
//			$this->getXMPValue( $metaData, 'creatortool', 'string', $xmpData, '//xap:CreatorTool');
	}

	/**
	 * Return the basic metadata of a preview embedded in an image.
	 *
	 * @param SimpleXMLElement $xmpData Extracted XMP metadata.
	 * @return array  Array with key/value pairs of preview properties and their values.
	 */
	public function readPreviewMetaDataFromXMP( $xmpData )
	{
		$xmpData->registerXPathNamespace('xapGImg',		"http://ns.adobe.com/xap/1.0/g/img/");

		$this->getXMPValue( $metaData, 'previewwidth',		'string', $xmpData, '//xapGImg:width');
		$this->getXMPValue( $metaData, 'previewheight', 		'string', $xmpData, '//xapGImg:height');
		$this->getXMPValue( $metaData, 'previewformat', 		'string', $xmpData, '//xapGImg:format');
		$this->getXMPValue( $metaData, 'previewimage', 		'string', $xmpData, '//xapGImg:image');

		return $metaData;
	}

	/**
	 * Helper for getXMPFields to get the right values from XMP XML.
	 *
	 * @param array	$metadata	Array to store the metadata key/value.
	 * @param string $metakey	Meta data key.
	 * @param string $type		Type of the field: string, list or eval (like 30000/100)
	 * @param SimpleXMLElement	$simpleXML	The XMP-XML element to read from.
	 * @param string $xpath
	 * @param string $attrNS    Namespace of the xmp element.
	 * @param string $attrName  The XMP attribute name.
	 */
	private function getXMPValue( &$metadata, $metakey, $type, $simpleXML, $xpath, $attrNS = '', $attrName = '' )
	{
		$value = @$simpleXML->xpath( $xpath ); // Use @ to prevent warnings when xml element is not found.
		if( !$value ) {
			return;
		}

		switch( $type ) {
			case 'string':
				if( is_array( $value ) ) { $value = $value[0]; }
				$metadata[$metakey] = (string)$value;
				break;
			case 'attrstring':
				// The registerXPathNamespace() function works for xpath() but does not work for
				// the attributes() function. For example when 'xapMM' namespace is used in PHP but
				// 'xmpMM' namespace is used in XML document, it does NOT match! Mind the second char.
				// Therefore, in the code below, we ask for all namespaces, and resolve 'xmpMM' from
				// 'xapMM' by mapping them via the full namespace 'http://ns.adobe.com/xap/1.0/mm/'.
				if( is_array( $value ) ) { $value = $value[0]; }
				$nameSpaces = array_flip( $value->getNamespaces(true) );
				if( array_key_exists( $attrNS, $nameSpaces ) ) {
					$attr = $value->attributes( $nameSpaces[$attrNS], true );
					$metadata[$metakey] = (string) $attr[$attrName];
				}
				break;
			case 'eval':
				if( is_array( $value ) ) $value = $value[0];
				$val = explode( '/', (string)$value );
				if( count($val) == 1 ) {
					$metadata[$metakey] = $val[0];
				} else {
					$metadata[$metakey] = ( $val[0]/$val[1] );
				}
				break;
			case 'attreval':
				// The registerXPathNamespace() function works for xpath() but does not work for
				// the attributes() function. For example when 'xapMM' namespace is used in PHP but
				// 'xmpMM' namespace is used in XML document, it does NOT match! Mind the second char.
				// Therefore, in the code below, we ask for all namespaces, and resolve 'xmpMM' from
				// 'xapMM' by mapping them via the full namespace 'http://ns.adobe.com/xap/1.0/mm/'.
				if( is_array( $value ) ) { $value = $value[0] ; }
				$nameSpaces = array_flip( $value->getNamespaces( true ) );
				if( array_key_exists( $attrNS, $nameSpaces ) ) {
					$attr = $value->attributes( $nameSpaces[$attrNS], true );
					$value = (string) $attr[$attrName];
					$val = explode( '/', (string)$value );
					if( count($val) == 1 ) {
						$metadata[$metakey] = $val[0];
					} else {
						$metadata[$metakey] = ( $val[0]/$val[1] );
					}
				}
				break;
			case 'list':
				$metadata[$metakey] = array();
				if ( $value ) foreach( $value as $val ) {
					$metadata[$metakey][] = (string)$val;
				}
				break;
		}
	}
}