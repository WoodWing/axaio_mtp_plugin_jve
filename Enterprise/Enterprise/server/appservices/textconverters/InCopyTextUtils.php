<?php
/**
 * Helper classes for InCopyTextImport/InCopyTextExport
 *
 * @package Enterprise
 * @subpackage WebEditor
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
define( 'IC_HARD_EOL', chr(0xE2).chr(0x80).chr(0xA9) ); // InCopy Hard enter. (UTF-8: &hE280A9  UTF-16: &h2920)
define( 'IC_SOFT_EOL', chr(0xE2).chr(0x80).chr(0xA8) ); // InCopy Soft enter. (UTF-8: &hE280A8  UTF-16: &h2820)

define( 'LAB_BLACK', 20 );
define( 'LAB_YELLOW', 70 );

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
class InCopyUtils
{
	static public function replaceGUIDs( &$doc, &$replacedGuids, $format = null )
	{
		if( $format == 'text/wwea' ) {
			self::replaceGUIDsForWWEA( $doc, $replacedGuids );
		} else {
			self::replaceGUIDsForInCopy( $doc, $replacedGuids );
		}
	}

	/**
	 * Replaces the contentID(GUIDs) in a wwea DOMDocument. Typically used to instantiate a new document.
	 *
	 * @param DOMDocument &$wweaDoc Reference to the DOMDocument to replace the contentId(GUIDs) in
	 * @param array &$replacedGuids Reference to an array with old-new contentId(GUIDs) mapping (old=key, new=value)
	**/
	static private function replaceGUIDsForWWEA( &$wweaDoc, &$replacedGuids )
	{
		$xpath = new DOMXPath($wweaDoc);
		$xpath->registerNamespace('ea', "urn:EnterpriseArticle");
				
		foreach($xpath->query('/ea:article/ea:component') as $component) {
			
			$oldContentId = $component->getAttribute('contentId');
			if( $oldContentId ) {
				$replacedGuids[$oldContentId] = strtoupper(InCopyUtils::createGUID());
				$component->setAttribute('contentId',$replacedGuids[$oldContentId]);
			}
		}
	}
		
	/**
	 * Replaces the GUIDs in a InCopy DOMDocument. Typically used to instantiate a new document.
	 *
	 * @param DOMDocument &icDoc Reference to the DOMDocument to replace the GUIDs in
	 * @param array &$replacedGuids Reference to an array with old-new GUIDs mapping (old=key, new=value)
	**/	
	static private function replaceGUIDsForInCopy( &$icDoc, &$replacedGuids)
	{
		// Determine GUIDs map and replace GUIDs for all Story elements
		$xpath = new DOMXPath( $icDoc );
		$xpath->registerNamespace( 'ea', 'urn:SmartConnection_v3' );
		
		$query = '/ea:Stories';
		$entries = $xpath->query($query);
		
		if($entries->length >0){ //version maybe found.
			$version = $entries->item(0)->getAttribute('ea:WWVersion');
			if( version_compare( $version,  '2.0', '>=' ) ){ //version = '2.0' is found, it is an IC CS5 article
				self::replaceGUIDsForInCopyCS5($icDoc, $replacedGuids);
			}
		}else{ //if no version specified,meaning it is an IC CS4 article.
			//For CS4
			self::replaceGUIDsForInCopyCS4($icDoc, $replacedGuids);
		}
	}

	/**
	 * Replaces the GUIDs in a InCopy CS4 DOMDocument. Typically used to instantiate a new document.
	 * '//Stories/Story/SnippetRoot/cflo' xpath query for copying 'normal' articles.
	 * '//Stories/wwst_template/wwst_stories/SnippetRoot/cflo' xpath query for copying article created from a template.
	 * '//Stories/Story/SnippetRoot/crec' ); // BZ#14792 xpath query for copying an article created from a image (graphical article) 
	 * 
	 * @param DOMDocument &icDoc Reference to the DOMDocument to replace the GUIDs in
	 * @param array &$replacedGuids Reference to an array with old-new GUIDs mapping (old=key, new=value)
	 */
	static private function replaceGUIDsForInCopyCS4( &$icDoc, &$replacedGuids)
	{	
		$xpath = new DOMXPath( $icDoc );
		$query = '//Stories/Story';
		$entries = $xpath->query($query);
		foreach( $entries as $entry ) {
			$oldGUID = $entry->getAttribute( 'GUID' );
			if( $oldGUID ) {
				$replacedGuids[$oldGUID] = strtoupper(InCopyUtils::createGUID());
				$entry->setAttribute( 'GUID', $replacedGuids[$oldGUID] );
			}
		}

		// Replace other GUIDs that did refer to the old (replaced) Story GUIDs
		$queries = array( 
			'//Stories/Story/SnippetRoot/cflo', 
			'//Stories/wwst_template/wwst_stories/SnippetRoot/cflo',
			'//Stories/Story/SnippetRoot/crec' ); // BZ#14792
		foreach( $queries as $query ) {
			$entries = $xpath->query($query);
			foreach( $entries as $entry ) {
				$oldGUID = $entry->getAttribute( 'GUID' ); // GUID has k_ or c_ prefixes
				if( $oldGUID ) { 
					$newGUID = new ICDBToken( $oldGUID );
					$newGUID->value = $replacedGuids[$newGUID->value];
					$entry->setAttribute( 'GUID', $newGUID->toString() ); 
				}
			}
		}	
	}
	
	/**
	 * Replaces the GUIDs in a InCopy CS5 DOMDocument. Typically used to instantiate a new document.
	 * More info see: replaceGUIDsForInCopyCS4()
	 * @param DOMDocument &icDoc Reference to the DOMDocument to replace the GUIDs in
	 * @param array &$replacedGuids Reference to an array with old-new GUIDs mapping (old=key, new=value)
	**/	
	static private function replaceGUIDsForInCopyCS5( &$icDoc, &$replacedGuids)
	{
		// Determine GUIDs map and replace GUIDs for all Story elements
		$xpath = new DOMXPath( $icDoc );
		$query = '/ea:Stories/ea:Story';
		$entries = $xpath->query($query);

		foreach( $entries as $entry ) {
			$oldGUID = $entry->getAttribute('ea:GUID');
			if( $oldGUID ) {
				$replacedGuids[$oldGUID] = strtoupper(InCopyUtils::createGUID());
				$entry->setAttribute('ea:GUID', $replacedGuids[$oldGUID] );
			}
		}
				
		$queries = array(
				'//ea:Stories/ea:Story/Document/Story', //equivalent to CS4: //Stories/Story/SnippetRoot/cflo'
				'//ea:Stories/ea:wwst_template/ea:wwst_stories/Document/Story', //equivalent to CS4: //Stories/wwst_template/wwst_stories/SnippetRoot/cflo
				'//ea:Stories/ea:Story/Document/Spread/Rectangle'); // equivalent to CS4: //Stories/Story/SnippetRoot/crec
																	
		foreach( $queries as $query ) {
			$entries = $xpath->query($query);
			foreach( $entries as $entry ) {
				$oldGUID = $entry->getAttribute( 'Guid' );
				if( $oldGUID ) { 
					$entry->setAttribute( 'Guid', $replacedGuids[$oldGUID] );
				}
			}
		}
	}
	
	
	/**
	 * Generate unique GUID (in 8-4-4-4-12 format) that can be used for Adobe InCopy stories.
	 *
	 * @return string   GUID
	 */
	static public function createGUID()
	{
        // Create a md5 sum of a random number - this is a 32 character hex string
        $raw_GUID = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );

        // Format the string into 8-4-4-4-12 (numbers are the number of characters in each block)
        return  substr($raw_GUID,0,8) . "-" . substr($raw_GUID,8,4) . "-" . substr($raw_GUID,12,4) . "-" . substr($raw_GUID,16,4) . "-" . substr($raw_GUID,20,12);
	}

	/*
	 * Generate unique resource id that can be used for Adobe InCopy stories.
	 * Should be used at attributes like this: Self="rc_uNNN", 
	 * where NNN represents the id returned by this function.
	 * All XML nodes and Process Instructions are parsed, looking for the Self attribute.
	 * From those, the maximum resource id is determined. The returned value is that one, plus 1.
	 * Results are cached so you can safely call this function many times without performance worries.
	 *
	 * @return integer Unique resource id, to be converted like this: 'rc_u'.dechex($recId)
	 */
	static public function createNewResourceId( DOMDocument $icDoc )
	{
		static $maxRcId;
		if( !isset($maxRcId) ) {
			$xpath = new DOMXPath($icDoc);
			/* Commented out since registerPHPFunctions() requires PHP 5.3
			$xpath->registerNamespace("php", "http://php.net/xpath");
			$xpath->registerPHPFunctions();
			$query = '//SnippetRoot/*[php:functionString("substr", @Self, 0, 4) = "rc_u"]';
			$rcNodes = $xpath->query( $query );*/

			// Get the MAX resource id for nodes underneath any "SnippetRoot" node (at any level
			// between the document root and the found node) that have a "Self" attribute which 
			// represents an InCopy resource definition. Those are prefixed with "rc_u".
			$nodesMaxRcId = 0;
			$query = '//SnippetRoot//*[substring(@Self,1,4)="rc_u"]';
			$rcNodes = $xpath->query( $query );
			LogHandler::Log( 'textconv', 'DEBUG', 'Found '.$rcNodes->length.' XML nodes representing resource definitions.' );
			if( $rcNodes->length > 0 ) foreach( $rcNodes as $rcNode ) {
				$rcId = hexdec( substr( $rcNode->getAttribute('Self'), 4 ) ); // skip rc_u
				$nodesMaxRcId = max( $nodesMaxRcId, $rcId );
			}
			LogHandler::Log( 'textconv', 'DEBUG', 'Determined max resource id from nodes : rc_u'.dechex($nodesMaxRcId) );

			// Now do the same, but for Processing instructions, which look like the following:
			//    < ? aid Char="0" Self="rc_uc8cins10" ? >
			// Those are used for hyperlink markers and such. Also here, we are looking for the ones
			// that have the "Self" attribute with a "rc_u" prefix.
			$PIsMaxRcId = 0;
			$query = '//SnippetRoot//processing-instruction()[contains(.,\'Self="rc_u\')]';
			$rcNodes = $xpath->query( $query );
			LogHandler::Log( 'textconv', 'DEBUG', 'Found '.$rcNodes->length.' process instructions representing resource definitions.' );
			if( $rcNodes->length > 0 ) foreach( $rcNodes as $rcNode ) {
				// Unlike above, PIs have no attributes, and so we parse its data element
				// which contains the full content of the PI. See example above.
				$rcStart = strpos( $rcNode->data, 'Self="rc_u' ) + strlen( 'Self="rc_u' );
				$rcEnd = strpos( $rcNode->data, '"', $rcStart );
				$rcId = hexdec( substr( $rcNode->data, $rcStart, $rcEnd - $rcStart ) );
				$PIsMaxRcId = max( $PIsMaxRcId, $rcId );
			}
			LogHandler::Log( 'textconv', 'DEBUG', 'Determined max resource id from process instructions : rc_u'.dechex($PIsMaxRcId) );
			$maxRcId = max( $PIsMaxRcId, $nodesMaxRcId );
		}
		$maxRcId++;
		LogHandler::Log( 'textconv', 'DEBUG', 'Determined unique resource id : rc_u'.dechex($maxRcId) );
		return $maxRcId;
	}
	
	/**
	 * Determines the default paragraph style for the given InCopy story.
	 * This style is typically used when no style is explicitly set by user.
	 *
	 * @param DOMDocument $icDoc
	 * @param DOMNode $icStory
	 * @return string Name of the style (without "rc_" prefix). Empty on error.
	 */
	static public function getDefaultParaStyle( $icDoc, $icStory )
	{
		try {
			$xpath = new DOMXPath( $icDoc );
			$queries = array( 'SnippetRoot/psty[@pnam="k_[No paragraph style]"]' => $icStory, 
				'wwst_template/wwst_styles/SnippetRoot/psty[@pnam="k_[No paragraph style]"]' => $icStory->parentNode,
				'wwsd_document/wwsd_styles/SnippetRoot/psty[@pnam="k_[No paragraph style]"]' => $icStory->parentNode ); 
			foreach( $queries as $query => $icParent ) { // Walk through all paragraph styles
				$entries = $xpath->query( $query, $icParent );
				foreach( $entries as $icPsty ) {
					//$icPsty = $icPsty->parentNode; // climb up to psty element
					//$pnam = $icPsty->getAttribute( 'pnam' ); // name
					//if($pnam == 'k_NormalParagraphStyle'){
					$basd = $icPsty->getAttribute( 'basd' );
					if( empty( $basd ) ) { // id inherit from
						$self = $icPsty->getAttribute( 'Self' ); // id
						return substr( $self, 3 ); // skip "rc_" prefix
					}
				}
			}
		} catch( DOMException $e ) {
		}
		return ''; // should never happen?
	}
	
	/**
	 * Determines the default character style for the given InCopy story.
	 * This style is typically used when no style is explicitly set by user.
	 *
	 * @param DOMDocument $icDoc
	 * @param DOMNode $icStory
	 * @return string Name of the style (without "rc_" prefix). Empty on error.
	 */
	static public function getDefaultCharStyle( $icDoc, $icStory )
	{
		try {
			$xpath = new DOMXPath( $icDoc );
			$queries = array( 'SnippetRoot/csty[@pnam="k_[No character style]"]' => $icStory, 
				'wwst_template/wwst_styles/SnippetRoot/csty[@pnam="k_[No character style]"]' => $icStory->parentNode,
				'wwsd_document/wwsd_styles/SnippetRoot/csty[@pnam="k_[No character style]"]' => $icStory->parentNode ); 
			foreach( $queries as $query => $icParent ) { // Walk through all character styles
				$entries = $xpath->query( $query, $icParent );
				foreach( $entries as $icCsty ) {
					//$icCsty = $icCsty->parentNode; // climb up to csty element
					$basd = $icCsty->getAttribute( 'basd' );
					if( empty( $basd ) ) { // id inherit from
						$self = $icCsty->getAttribute( 'Self' ); // id
						return substr( $self, 3 ); // skip "rc_" prefix
					}
				}
			}
		} catch( DOMException $e ) {
		}
		return ''; // should never happen?
	}

	/**
	 * Converts IC font size into HTML font size
	 *
	 * Font sizes are specified in two ways; in styles and in free formats.
	 * For HTML, the free format sizes are defined in font elements: <font size="..."></font>
	 * This size is an indexed and only values 1...7 are allowed !
	 * Therefor font sizes needs to be converted between IC and HTML.
	 * For styles, there is no convertion problem since px can be used in HTML.
	 *
	 * @param integer $icSize InCopy font size
	 * @return integer HTML font size (index) 1...7
	 */
	static public function freeFontSizeIC2HTML( $icSize )
	{
		$sizeMap = array( 1=>8, 2=>10, 3=>12, 4=>14, 5=>18, 6=>24, 7=>36 );
		$sizeKeys = array_keys( $sizeMap );
		$maxSize = $sizeMap[count($sizeMap)];
		if( $icSize <= $sizeMap[1] ) return $sizeKeys[0]; // handle 1 (or smaller, which is invallid)
		if( $icSize >= $maxSize ) return $sizeKeys[count($sizeKeys)-1]; // treat index 7 (or higher, which is invallid)
		for( $i = 1; $i < count($sizeMap); $i++ ) { // walk through 1...6 (not 7)
			if( $icSize >= $sizeMap[$i] && $icSize < $sizeMap[$i+1] ) {
				return $sizeKeys[$i-1];
			}
		}
		return $sizeKeys[2]; // take average, should never happen -> all handled above!
	}
	
	/**
	 * Converts HTML font size into IC font size
	 * See {@link freeFontSizeIC2HTML()} for more details.
	 *
	 * @param integer HTML font size (index) 1...7
	 * @return integer $icSize InCopy font size
	 */
	static public function freeFontSizeHTML2IC( $htmlSize )
	{
		$sizeMap = array( 1=>8, 2=>10, 3=>12, 4=>14, 5=>18, 6=>24, 7=>36 );
		$sizeKeys = array_keys( $sizeMap );
		$maxKey = $sizeKeys[count($sizeKeys)-1];
		if( $htmlSize < $sizeKeys[0] ) return $sizeMap[0];
		if( $htmlSize > $maxKey ) return $sizeMap[count($sizeMap)-1];
		return $sizeMap[$htmlSize];
	}
	
	/**
	  * Converts an InCopy color definition into RGB value (that can be used for XHTML).
	  *
	  * @param object $icColr InCopy <colr> element
	  * @return string RBG color in 6 digit hex notation with # prefix: #RRGGBB
	  */
	static public function getColorRGB( $icColr )
	{
		$rgb = '000000'; // black
		if( is_object($icColr) ) { // paranoid check
			$clvl = $icColr->getAttribute( 'clvl' );
			$colorarr = explode('_', $clvl );
			switch( $icColr->getAttribute( 'clsp' ) ) {
				case 'e_CMYK':
					$cmyk = array();
					$cmyk[] = $colorarr[3]/100;
					$cmyk[] = $colorarr[5]/100;
					$cmyk[] = $colorarr[7]/100;
					$cmyk[] = $colorarr[9]/100;
					$rgb = self::cmyk2rgb( $cmyk );
					$rgb = self::rgb2hex( $rgb );
					break;
				case 'e_cLAB':
					$lab = array();
					$lab[] = $colorarr[3];
					$lab[] = $colorarr[5];
					$lab[] = $colorarr[7];
					$rgb = self::lab2rgb( $lab );
					$rgb = self::rgb2hex( $rgb );
					break;
				case 'e_cRGB':
					$rgb = array();
					$rgb[] = $colorarr[3];
					$rgb[] = $colorarr[5];
					$rgb[] = $colorarr[7];
					$rgb = self::rgb2hex( $rgb );
					break;
			}
		}
		return '#'.$rgb; // Fix: added "#" to show CSS colors under Safari v3
	}
	
	/**
	* Converts a Lab color to a RGB color
	*
	* @param  array $lab Lab color
	* @return array RGB color
	*/
	static public function lab2rgb( $lab )
	{
		$fY = pow(($lab[0] + 16.0) / 116.0, 3.0);
		if( $fY < 0.008856 ) {
			$fY = $lab[0] / 903.3;
		}
		$Y = $fY;
		
		if( $fY > 0.008856 ) {
			$fY = pow($fY, 1.0/3.0);
		} else {
			$fY = (7.787 * $fY) + (16.0/116.0);
		}
		
		$fX = ($lab[1] / 500.0) + $fY;     
		if( $fX > 0.206893 ) {
			$X = pow($fX, 3.0);
		} else {
			$X = ($fX - (16.0/116.0)) / 7.787;
		}
		
		$fZ = $fY - ($lab[2] /200.0);
		if( $fZ > 0.206893 ) {
			$Z = pow($fZ, 3.0);
		} else {
			$Z = ($fZ - (16.0/116.0)) / 7.787;
		}
		
		$X *= (0.950456 * 255);
		$Y *=             255;
		$Z *= (1.088754 * 255);
		
		$r = ceil( (3.240479*$X) - (1.537150*$Y) - (0.498535*$Z));
		$g = ceil((-0.969256*$X) + (1.875992*$Y) + (0.041556*$Z));
		$b = ceil( (0.055648*$X) - (0.204043*$Y) + (1.057311*$Z));
		
		$rgb = array();
		$rgb[] = $r < 0 ? 0 : ($r > 255 ? 255 : $r);
		$rgb[] = $g < 0 ? 0 : ($g > 255 ? 255 : $g);
		$rgb[] = $b < 0 ? 0 : ($b > 255 ? 255 : $b);
		return $rgb;
	}

	/**
	* Converts a RGB color to a Lab color
	*
	* @param  array $rgb RGB color
	* @return array Lab color
	*/
	/*static public function rgb2lab( $rgb )
	{
		$X = (0.412453*$rgb[0]) + (0.357580*$rgb[1]) + (0.180423*$rgb[2]);
		$Y = (0.212671*$rgb[0]) + (0.715160*$rgb[1]) + (0.072169*$rgb[2]);
		$Z = (0.019334*$rgb[0]) + (0.119193*$rgb[1]) + (0.950227*$rgb[2]);
		
		$X /= (255 * 0.950456);
		$Y /=  255;
		$Z /= (255 * 1.088754);
		
		$lab = array();
		if( $Y > 0.008856 ) {
			$fY = pow($Y, 1.0/3.0);
			$lab[0] = (116.0*$fY) - 16.0;
		} else {
			$fY = (7.787*$Y) + (16.0/116.0);
			$lab[0] = (903.3*$Y);
		}
		
		if( $X > 0.008856 ) {
			$fX = pow($X, 1.0/3.0);
		} else {
			$fX = (7.787*$X) + (16.0/116.0);
		}
		
		if( $Z > 0.008856 ) {
			$fZ = pow($Z, 1.0/3.0);
		} else {
			$fZ = (7.787*$Z) + (16.0/116.0);
		}
		
		$lab[1] = 500.0*($fX - $fY);
		$lab[2] = 200.0*($fY - $fZ);
		
		if( $lab[0] < LAB_BLACK ) {
			$lab[1] *= exp((L - LAB_BLACK) / (LAB_BLACK / 4));
			$lab[2] *= exp((L - LAB_BLACK) / (LAB_BLACK / 4));
			$lab[0] = LAB_BLACK;
		}
		if( $lab[2] > LAB_YELLOW ) {
			$lab[2] = LAB_YELLOW;
		}
		$lab[0] = ceil($lab[0]);
		$lab[1] = ceil($lab[1]);
		$lab[2] = ceil($lab[2]);
		return $lab;
	}*/
	
	/**
	* Converts a CMYK color to a RGB color
	*
	* @param  array $cmyk CMYK color
	* @return array RGB color
	*/
	static public function cmyk2rgb( $cmyk )
	{
		$rgb = array();
		$k = $cmyk[3];
		$c = min(1, ($cmyk[0]*(1-$k))+$k);
		$m = min(1, ($cmyk[1]*(1-$k))+$k);
		$y = min(1, ($cmyk[2]*(1-$k))+$k);
		$rgb[] = round((1-$c)*255);
		$rgb[] = round((1-$m)*255);
		$rgb[] = round((1-$y)*255);
		return $rgb;
	}

	/**
	* Converts a RGB color to a CMYK color
	*
	* @param  array $rgb RGB color
	* @return array CMYK color
	*/
	/*static public function rgb2cmyk( $rgb )
	{
		$r = ( $rgb[0] / 255 ) * 100;
		$g = ( $rgb[1] / 255 ) * 100;
		$b = ( $rgb[2] / 255 ) * 100;
		
		$div = 100 - ( ( min( $rgb ) / 255 ) * 100 );
		
		$c = ($div != 0) ? round(100 - (100 * $r) / $div) : 0;
		$c = ($c < 0) ? 0 : $c;
		$m = ($div != 0) ? round(100 - (100 * $g) / $div) : 0;
		$m = ($m < 0) ? 0 : $m;
		$y = ($div != 0) ? round(100 - (100 * $b) / $div) : 0;
		$y = ($y < 0) ? 0 : $y;
		
		$k = round( max( $rgb ) / 255 ) * 100;
		$k = 100 - $k;
		return array($c, $m, $y, $k);
	}*/

	/**
	* Converts hexadecimal string into array of RGB values.
	*
	* @param  string $hex Hex RGB value
	* @return array  Dec RGB values
	*/
	static public function hex2rgb( $hex )
	{
		$c = array();
		$c[] = hexdec(substr($hex,0,2));
		$c[] = hexdec(substr($hex,2,2));
		$c[] = hexdec(substr($hex,4,2));
		return $c;
	}

	static public function rgb2hex( $arrColors = null ) 
	{
	    array_splice($arrColors, 3);
	    for ($x = 0; $x < count($arrColors); $x++) {
			$arrColors[$x] = dechex($arrColors[$x]);
	    }
	    foreach($arrColors as $index => $hex){
	    	if(mb_strlen($hex) == 1){
	    		settype($hex, 'string');
	    		$hex = '0'.$hex;
	    		$arrColors[$index] = $hex;
	    	}
	    }
		return implode("", $arrColors);
	}

	/**
	 * Removes duplicate style definitions from a given InCopy WCML file.
	 *
	 * For each text element (also called 'Story') style definitions can be made.
	 * A WCML article may contain multiple elements. When adding a style to one
	 * element, to make it available for the other elements in SC (IC/ID), the style
	 * definitions are replicated. However, SC/CS will accumulate all style definitions
	 * when opening the WCML article, and so, they don't need the duplicates. 
	 * By stripping the duplicates, file transfer becomes more efficient for remote users.
	 *
	 * Existing style definitions under the WW Document (wwsd_styles/Document or wwst_styles/Document)
	 * remain untouched. Only the ones under Story/Document are removed. When removing 
	 * definitions that are not present under the WW Document, those are added under there,
	 * to make sure that the definitions under the WW Document are complete.
	 *
	 * @param DOMDocument $icDoc InCopy WCML DOM wherein duplicates should be removed.
	 */
	/* COMMENTED OUT: still in experimental phase
	static public function stripDuplicateDefinitions( $icDoc ) 
	{
		$isDebug = LogHandler::debugMode();
		$xpath = new DOMXPath( $icDoc );
		$xpath->registerNamespace( 'ea', 'urn:SmartConnection_v3' );
		
		// Style definitions for which duplicates should be removed.
		// These definitions can be found directly under the Document element 
		// and have a Self attribute used for identification.
		$styles = array(
			'FontFamily' => true, // 'Font',
			'RootCharacterStyleGroup' => true, // 'CharacterStyle',
			'RootParagraphStyleGroup' => true, // 'ParagraphStyle',
			'TableStyleGroup' => true, // 'TableStyle',
			'CellStyleGroup' => true, // 'CellStyle',
			'ObjectStyleGroup' => true, // 'ObjectStyle',
			'TOCStyle' => true, // 'TOCStyleEntry',
			'TrapPreset' => true,
			'CompositeFont' => true,
			'CrossReferenceFormat' => true,
			'Swatch' => true,
			'Color' => true,
			'Gradient' => true,
			'GradientStop' => true,
			'MixedInk' => true,
			'MixedInkGroup' => true,
			'Ink' => true,
			'StrokeStyle' => true,
			'MojikumiTable' => true,
			'KinsokuTable' => true,
			'NumberingList' => true,
			'NamedGrid' => true,
			'MotionPreset' => true,
			'Condition' => true,
			'ConditionSet' => true,
			'TextVariable' => true,
			'Layer' => true,
			'Section' => true,
			'DocumentUser' => true,
			'Hyperlink' => true,
			'Bookmark' => true,
			'PreflightProfile' => true,
			'DataMergeImagePlaceholder' => true,
			'HyphenationException' => true,
			'IndexingSortOption' => true,
			'ABullet' => true,
			'Assignment' => true,
		);
		
		// Find the WW parent Document under where all styles should be defined.
		// Note that the WCML document could be an article or an article template.
		$wwDocs = array();
		$queries = array(
			'/ea:Stories/ea:wwsd_document/ea:wwsd_styles/Document',
			'/ea:wwst_template/ea:wwst_styles/Document'
		);
		foreach( $queries as $query ) {
			$nodes = $xpath->query( $query );
			if( $nodes->length > 0 ) {
				// There should be 1 doc only, but respect all (future aware).
				for( $i = 0; $i < $nodes->length; $i++ ) {
					$wwDocs[] = $nodes->item($i);
				}
			}
		}
		
		// Under the found WW parent Document, collect all definitions made.
		$defs = array();
		foreach( $wwDocs as $wwDoc ) {
			$nodes = $xpath->query( '*', $wwDoc );
			foreach( $nodes as $node ) {
			
				// Collect known style definitions.
				if( array_key_exists( $node->nodeName, $styles ) ) {
					$styleId = $node->getAttribute('Self');
					$defs[$node->nodeName][$styleId] = true;
				}
			}
		}

		// Delete duplicate style definitions; Search through the WCML text components, 
		// and remove embedded style definitions that are already present under the WW Document.
		$nodes = $xpath->query( '/ea:Stories/ea:Story/Document/*' );
		foreach( $nodes as $node ) {
			if( array_key_exists( $node->nodeName, $styles ) ) {
				
				// Remove definition from text component.
				$parent = $node->parentNode;
				if( $isDebug ) { self::removeMarkupBeforeAfter( $node ); }
				$parent->removeChild( $node );
				
				// Add definition to WW Document.
				if( !array_key_exists( $node->nodeName, $defs ) ) {
					$defs[$node->nodeName] = array();
				}
				$styleId = $node->getAttribute('Self');
				if( !array_key_exists( $styleId, $defs[$node->nodeName] ) ) {
					foreach( $wwDocs as $wwDoc ) {
						$wwDoc->appendChild( $node );
						if( $isDebug ) { // add line ending in debug mode only (to ease diff)
							$wwDoc->appendChild( $icDoc->createTextNode( PHP_EOL ) );
						}
					}
					$defs[$node->nodeName][$styleId] = true;
				}
			}
		}
	}*/
	
	/**
	 * Removes all sibling text nodes before- and after a give node.
	 *
	 * For debugging, a WCML article could be beautified. Then it is made readable
	 * by oulining the XML elements (by adding tab indents and line endings). However
	 * those can be disturbing when calling parentNode->hasChildNodes() since markup/text
	 * nodes are counted as well. This function strips those markups so that beautified
	 * can be used as well.
	 *
	 * @param DOMNode $node XML node for which its sibling text nodes should be deleted.
	 */
	static private function removeMarkupBeforeAfter( $node )
	{
		$iter = $node->nextSibling;
		while( $iter ) {
			if( $iter->nodeType != XML_TEXT_NODE ) {
				break;
			}
			$text = $iter;
			$iter = $iter->nextSibling;
			$text->parentNode->removeChild( $text );
		}
		$iter = $node->previousSibling;
		while( $iter ) {
			if( $iter->nodeType != XML_TEXT_NODE ) {
				break;
			}
			$text = $iter;
			$iter = $iter->previousSibling;
			$text->parentNode->removeChild( $text );
		}
	}	
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
/**
 * Holds user definitions that are embedded in InCopy text. <br/>
 * Those users have been editing the document. <br/>
 * This is used to determine the user's track changes colors. <br/>
 *
 * @since v4.1.9
 */
class InCopyUser
{
	/**
    * @var string
    */
	public $ID;
	/**
    * @var string
    */
	public $Name;
	/**
    * @var string
    */
	public $Color;

	/**
	 * Constuctor.
	 * @param $id    string User resource identifier used by IC (without rc_ or ro_ prefix) <br/>
	 * @param $name  string User name, which is Enterprise's user id <br/>
	 * @param $color string User track changes color (in #RGB notation) <br/>
	 */
	public function __construct( $id, $name, $color )
	{
		$this->ID = $id;
		$this->Name = $name;
		$this->Color = $color;
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
/**
 * Keeps track of resource objects that are embedded in InCopy text. <br/>
 * Examples are: Foot notes, inline notes, inline images, tables, etc <br/>
 */
class StoryResourceDef
{
	/**
    * @var string
    */
	public $ID;
	/**
    * @var string
    */
	public $EndID;
	/**
    * @var string
    */
	public $Type;
	/**
    * @var string
    */
	public $Description;
	/**
    * @var string
    */
	public $IconPath;
	/**
    * @var string
    */
	public $BackColor;

	/**
	 * Init object data. <br/>
	 *
	 * @param $id    string Resource identifier (without rc_ or ro_ prefix) <br/>
	 * @param $endid string Resource identifier of text end marker (in case of text insert using tracked changes) <br/>
	 * @param $type  string Element name of embedded objects ('ctbl' for table, etc) <br/>
	 * @param $descr string Description to show user in popup help to identify object in HTML <br/>
	 * @param $icon  string Path to icon file to illustrate type of embedded object <br/>
	 * @param $color string Background color to use for icon <br/>
	 */
	public function setData( $id, $endid, $type, $descr, $icon, $color )
	{
		$this->ID = $id;
		$this->EndID = $endid;
		$this->Type = $type;
		$this->Description = $descr;
		$this->IconPath = $icon;
		$this->BackColor = $color;
	}

	/**
	 * Copy or construct object <br/>
	 *
	 * @param $obj object When object given, it performs copy constructor. When nothing given it does normal construction. <br/>
	 */
	public function __construct( $obj = null ) 
	{
		if ( !is_null($obj) && get_class($obj) == get_class($this) ) {
			$arMyVars = get_object_vars($obj);
			foreach ($arMyVars as $name => $value) {
				$this->$name = $value;
			}
		}
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
/**
 * Manages hyperlinks parsed from InCopy files.
 */
class InCopyHyperlinkStack
{
	private $xLinks = array();

	/**
	 * Returns the InCopy (XML) hyperlink that was last added (on top of the stack).
	 * When stack is empty, null is returned.
	 *
	 * @return DOMNode The hyperlink.
	 */
	public function getCurrentLink()
	{
		if( count($this->xLinks) > 0 ) {
			$struct = current( $this->xLinks );
			return $struct[2];
		}
		return null;
	}
	
	/**
	 * Adds an InCopy hyperlink on top of the stack.
	 *
	 * @param string $sttResId Resource ID of the start marker of the hyperlink.
	 * @param string $endResId Resource ID of the end marker of the hyperlink.
	 * @param DOMNode $xLink The InCopy hyperlink XML object.
	 */
	public function addLink( $sttResId, $endResId, $xLink )
	{
		array_push( $this->xLinks, array( $sttResId, $endResId, $xLink ) ); // stack add at the end
	}
	
	/**
	 * Removes an InCopy hyperlink from top of the stack.
	 * Logs errors when the given start/end markers do not match with the top most link of the stack.
	 *
	 * @param string $sttResId Resource ID of the start marker of the hyperlink.
	 * @param string $endResId Resource ID of the end marker of the hyperlink.
	 */
	public function removeLink( $sttResId, $endResId )
	{
		$struct = array_pop( $this->xLinks ); // pop off the end
		if( $struct[0] !== $sttResId ) {
			LogHandler::Log( 'textconv', 'ERROR', 'Given start marker of hyperlink ['.$sttResId.'] was not found on top of stack ['.$struct[0].']');
		}
		if( $struct[1] !== $endResId ) {
			LogHandler::Log( 'textconv', 'ERROR', 'Given end marker of hyperlink ['.$endResId.'] was not found on top of stack ['.$struct[1].']');
		}
	}
}

/**
 * InCopy hyperlinks are marked in the text content, but its properties are stored in the InCopy database.
 * This class deals with references made between content and InCopy database.
 */
class ICDBHyperlink
{
	private $sttRC;
	private $endRC;
	private $icHLTsSelf;
	
	// values returned by findHyperlink4ResId() function
	const MARKER_NOT_FOUND = 0;
	const MARKER_START = 1;
	const MARKER_END = 2;
	
	public function __construct()
	{
		$this->sttRC = '';
		$this->endRC = '';
		$this->icHLTsSelf = '';
	}
	
	/**
	 * Lookup hyperlink in InCopy database ($icDoc) for given story ($icStory).
	 * Properties are taken from given XHTML hyperlink ($xLink).
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMNode     $xLink (in)     XHTML hyperlink (anchor) element.
	 * @return boolean Wether or not the hyperlink could be found at InCopy database.
	 */
	public function findHyperlink4Href( $icDoc, $icStory, $xLink )
	{
		$resId = $xLink->getAttribute( 'id' );
		$href  = $xLink->getAttribute( 'href' );
		$title = $xLink->getAttribute( 'title' );
		$xpath = new DOMXPath( $icDoc );
		
		if( $resId ){
			$entries = $xpath->query( 'SnippetRoot/HLOB[@Self="'.$resId.'"]', $icStory );
			if( $entries->length > 0 && ($icHLOB = $entries->item(0)) ) {
				$resId = 'rc_'.mb_substr( $icHLOB->getAttribute( 'hlds' ), 2 ); // replace 'o_' prefix with 'rc_'
				$entries = $xpath->query( 'SnippetRoot/HLUd[@Self="'.$resId.'"]', $icStory ); 
				if( $entries->length > 0 && ($icHLUd = $entries->item(0)) ) {
					$icHLUd->setAttribute( 'hURL', 'c_'.$href );
					$icHLUd->setAttribute( 'pname', 'c_'.$title );
				}
				$resId = 'rc_'.mb_substr( $icHLOB->getAttribute( 'hlsc' ), 2 ); // replace 'o_' prefix with 'rc_'
				$entries = $xpath->query( 'SnippetRoot/HLTs[@Self="'.$resId.'"]', $icStory ); 
				if( $entries->length > 0 && ($icHLTs = $entries->item(0)) ) {
					$icHLTs->setAttribute( 'pname', 'c_'.$title );
					$icHsTx = $icHLTs->getAttribute( 'hsTx' );
					$resIds = explode( ':', $icHsTx ); // get out start+stop resource ids, such as: hsTx="o_ubdcins4:ubdcins8"
					if( count( $resIds ) == 2 ) {
						$resIds[0] = mb_substr( $resIds[0], 2 ); // skip 'o_' prefix
						$this->sttRC = 'rc_'.$resIds[0];
						$this->endRC = 'rc_'.$resIds[1];
						LogHandler::Log( 'textconv', 'DEBUG', 'findHyperlink4Href: Found URL ['.$href.'] for hyperlink id ['.$resId.']');
						return true;
					}
				}
			}
		}
		LogHandler::Log( 'textconv', 'DEBUG', 'findHyperlink4Href: No hyperlink id found for URL ['.$href.']. New InCopy resource needs to be created.');
		return false;
	}
	
	/**
	 * When lookup for hyperlink in IC database failed in findHyperlink4Href(),
	 * we need to create a hyperlink structure into IC Database with the ResIds ($hyperlinkResIdSet) generated.
	 * A hyperlink in IC Database consists of the following:
	 * <HLUd Self="rc_u92bb2" hURL="c_" hddn="b_f" hduk="l_1" pnam="c_"/>
	 * <HLTs Self="rc_u92bb3" crst="o_n" hddn="b_f" hsTx="o_uc8cins0:uc8cins11" pnam="c_Hyperlink 1"/>
	 * <HLOB Self="rc_u92bb4" clr="e_iBlk" hHlt="e_none" hSty="e_sold" hddn="b_f" hduk="l_1" hlds="rc_u92bb2" hlsc="rc_u92bb3" pnam="c_" pvis="b_t" wdwh="e_thin"/>
	 * 
	 * $hyperlinkResIdSet consists of the following:
	 * array(	"HLUd" => 'rc_u92bb2',
	 * 		"HLTs" => 'rc_u92bb3',
	 * 		"HLOB" => 'rc_u92bb4');
	 * 
	 * Hyperlink in IC DB is cross reference with XHTML (xElem) id attribute(<a 'id'>)
	 * 
	 * 
	 *
	 * @param DOMDocument $icDoc (in/out) InCopy document
	 * @param DOMNode $icStory (in) InCopy Story element
	 * @param DOMNode $xElem (in/out) XHTML hyperlink (anchor) element
	 * @param int $totalHyperlink Keep track of the total hyperlink for the whole article (multiple stories)
	 */
	public function createHyperlink4Href( &$icDoc, $icStory, &$xElem, &$totalHyperlink )
	{
		LogHandler::Log( 'textconv', 'DEBUG', __METHOD__ .' Setting id and title in <a> tag of XHTML and ' .
			'enriching IC doc with hyperlink structure');
		
		LogHandler::Log('textconv','DEBUG',__METHOD__.': Generating resource Id for hyperlink structure in IC DB.');
		$hyperlinkResIdSet = array(
			'HLUd' => 'rc_u' . dechex(InCopyUtils::createNewResourceId( $icDoc )),
			'HLTs' => 'rc_u' . dechex(InCopyUtils::createNewResourceId( $icDoc )),
			'HLOB' => 'rc_u' . dechex(InCopyUtils::createNewResourceId( $icDoc )) );
				
		// Setting 'id' and 'title' in XHTML (it is later used to reference the corresponding hyperlink node in IC Doc)
		if( !$xElem->getAttribute('id') ){
			$xElem->setAttribute('id',$hyperlinkResIdSet['HLOB']);
		}
						
		if( !$xElem->getAttribute('title') ){
			$xElem->setAttribute('title','');
		}
		
		/**
		 * Introduce hyperlink node in IC Doc and tag the following element tag with its corresponding ResId.
		 * HLUd, HLTs, HLOB.
		 * $icDoc needs a set of the following:
		 *	<HLUd Self="rc_u92bb2" hURL="c_" hddn="b_f" hduk="l_1" pnam="c_"/>
	 	 *  	<HLTs Self="rc_u92bb3" crst="o_n" hddn="b_f" hsTx="o_uc8cins0:uc8cins11" pnam="c_Hyperlink 1"/>
	 	 * 	<HLOB Self="rc_u92bb4" clr="e_iBlk" hHlt="e_none" hSty="e_sold" hddn="b_f" hduk="l_1" hlds="rc_u92bb2" hlsc="rc_u92bb3" pnam="c_" pvis="b_t" wdwh="e_thin"/>
		 * for ONE hyperlink(href).
		 */
		
		$xpath = new DOMXPath( $icDoc );
		// Extract the label(intro,body,head...) from Story element. (we need to know which part hyperlink is needed)
		$storyLabels = $xpath->query( 'StoryInfo/SI_EL', $icStory );
		$storyLabel = $storyLabels->item(0)->nodeValue;
		
		$stories = $xpath->query( '/Stories/Story');
		foreach( $stories as $story ){
			
			$siEl = $story->getElementsByTagName('StoryInfo')->item(0)->getElementsByTagName('SI_EL')->item(0)->nodeValue;
			if( $siEl == $storyLabel ){ // found the label( extracted from Story) in IC Doc, so insert the hyperlink structure.
				// HLUd
				$node = $icDoc->createElement('HLUd');
				$element = $story->getElementsByTagName('SnippetRoot')->item(0)->appendChild($node);
				$element->setAttribute('Self', $hyperlinkResIdSet['HLUd']);
				$element->setAttribute('hURL', 'c_');
				$element->setAttribute('hddn', 'b_f');
				$element->setAttribute('hduk', 'l_' . $totalHyperlink );
				$element->setAttribute('pnam', 'c_' . $xElem->getAttribute('href'));
				//<HLUd Self="rc_ufb" hURL="c_" hddn="b_f" hduk="l_1" pnam="c_"/>
				
				// HLTs
				LogHandler::Log('textconv','DEBUG',__METHOD__.': Generating resource Id for start and end marker for Process Instruction.');
				$sttRC = 'u' . dechex(InCopyUtils::createNewResourceId( $icDoc ));
				$endRc = 'u' . dechex(InCopyUtils::createNewResourceId( $icDoc ));

				$node = $icDoc->createElement('HLTs');
				$element = $story->getElementsByTagName('SnippetRoot')->item(0)->appendChild($node);
				$element->setAttribute('Self', $hyperlinkResIdSet['HLTs']);
				$element->setAttribute('crst', 'o_n');
				$element->setAttribute('hddn', 'b_f');
				$element->setAttribute('hsTx', 'o_' . $sttRC . ':' . $endRc); // to mark the hyperlink with starter and the end.
				$element->setAttribute('pnam', 'c_Hyperlink '. $totalHyperlink);
				//<HLTs Self="rc_uf7" crst="o_n" hddn="b_f" hsTx="o_uc8cins0:uc8cins11" pnam="c_Hyperlink 1"/>
				
				// HLOB
				$hlds = 'o_'.mb_substr( $hyperlinkResIdSet['HLUd'], 3 ); // replace rc_ with o_
				$hlsc = 'o_'.mb_substr( $hyperlinkResIdSet['HLTs'], 3 ); // replace rc_ with o_
				$node = $icDoc->createElement('HLOB');
				$element = $story->getElementsByTagName('SnippetRoot')->item(0)->appendChild($node);
				$element->setAttribute('Self', $hyperlinkResIdSet['HLOB']);
				$element->setAttribute('clr', 'e_iBlk');
				$element->setAttribute('hHlt', 'e_none');
				$element->setAttribute('hSty', 'e_sold');
				$element->setAttribute('hddn', 'b_f');
				$element->setAttribute('hduk', 'l_'. $totalHyperlink);
				$element->setAttribute('hlds', $hlds);
				$element->setAttribute('hlsc', $hlsc);
				$element->setAttribute('pnam', 'c_');
				$element->setAttribute('pvis', 'b_t');
				$element->setAttribute('wdwh', 'e_thin');
				//<HLOB Self="rc_ufa" clr="e_iBlk" hHlt="e_none" hSty="e_sold" hddn="b_f" hduk="l_1" hlds="o_ufb" hlsc="o_uf7" pnam="c_" pvis="b_t" wdwh="e_thin"/>
				
				$totalHyperlink += 1;
			}
		}
	}
	
	/**
	 * Creates processing instruction which represents the start marker for InCopy content pointing 
	 * to the InCopy database that holds the hyperlink definition found with findHyperlink() function.
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @return DOMProcessingInstruction The new DOMProcessingInstruction or false if an error occured.
	 */
	public function createStartPI( $icDoc )
	{
		return $icDoc->createProcessingInstruction( 'aid', 'Char="0" Self="'.$this->sttRC.'"' );
	}

	/**
	 * Creates processing instruction which represents the end marker for InCopy content pointing 
	 * to the InCopy database that holds the hyperlink definition found with findHyperlink() function.
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @return DOMProcessingInstruction The new DOMProcessingInstruction or false if an error occured.
	 */
	public function createEndPI( $icDoc )
	{
		return $icDoc->createProcessingInstruction( 'aid', 'Char="0" Self="'.$this->endRC.'"' );
	}

	/**
	 * Searches for the hyperlink at InCopy database ($icDoc) for given story ($icStory) specified by resource id ($resId).
	 * When found, it tells if the given resource id ($resId) was a start marker or end marker.
	 * Markers are processing instructions that wrap the hyperlink at content and refer to the InCopy 
	 * database where the hyperlink definition (properties) can be found.
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param string      $resId (in)     The resource id of the hyperlink.
	 * @return integer MARKER_START or MARKER_END when $resId is start- or end marker. Return MARKER_NOT_FOUND when not found.
	 */
	public function findHyperlink4ResId( $icDoc, $icStory, $resId )
	{
		$xpath = new DOMXPath( $icDoc );
		$icHLTsEntries = $xpath->query( 'SnippetRoot/HLTs', $icStory );
		$resId = mb_substr( $resId, 3 ); // skip 'rc_' prefix
		foreach( $icHLTsEntries as $icHLTsElem ) {
			$icHsTx = $icHLTsElem->getAttribute('hsTx');
			if( strlen($icHsTx) > 0 ) {
				$resIds = explode( ':', $icHsTx ); // get out start+stop resource ids, such as: hsTx="o_ubdcins4:ubdcins8"
				if( count( $resIds ) == 2 ) {
					$resIds[0] = mb_substr( $resIds[0], 2 ); // skip 'o_' prefix
					$this->icHLTsSelf = $icHLTsElem->getAttribute( 'Self');
					$this->sttRC = $resIds[0];
					$this->endRC = $resIds[1];
					if( $resIds[0] == $resId ) { // start marked found?
						LogHandler::Log( 'textconv', 'DEBUG', 'findHyperlink4ResId: Found start marker resource ['.$resId.'] of hyperlink ['.$this->icHLTsSelf.']');
						return self::MARKER_START;
					} else if( $resIds[1] == $resId ) { // end marker found?
						LogHandler::Log( 'textconv', 'DEBUG', 'findHyperlink4ResId: Found end marker resource ['.$resId.'] of hyperlink ['.$this->icHLTsSelf.']');
						return self::MARKER_END;
					}
				}
			}
		}
		return self::MARKER_NOT_FOUND;
	}
	
	public function getStartRC() { return $this->sttRC; }
	public function getEndRC() { return $this->endRC; }

	/**
	 * Creates XHTML hyperlink based on found InCopy hyperlink definition.
	 * Call the findHyperlink4ResId() function before calling this function.
	 * Returns null when properties could not be determined (should never happen).
	 *
	 * @param DOMDocument $icDoc (in)     InCopy document.
	 * @param DOMNode     $icStory (in)   InCopy Story element.
	 * @param DOMDocument $xDoc (in)      XHTML document.
	 * @return DOMNode The XHTML hyperlink object.
	 */
	public function createHyperlinkXHTML( $icDoc, $icStory, $xDoc )
	{
		$xpath = new DOMXPath( $icDoc );
		$icHLTsSelf = new ICDBToken( $this->icHLTsSelf );
		$icHLOBEntries = $xpath->query( 'SnippetRoot/HLOB[@hlsc="o_'.$icHLTsSelf->value.'"]', $icStory );
		if( $icHLOBEntries->length > 0 && ($icHLOBElem = $icHLOBEntries->item(0)) ) {
			$icHLOBSelf = $icHLOBElem->getAttribute('Self');
			$icHlds = new ICDBToken( $icHLOBElem->getAttribute('hlds') );
			if( strlen($icHlds->value) > 0 ) {
				$icHLUdEntries = $xpath->query( 'SnippetRoot/HLUd[@Self="rc_'.$icHlds->value.'"]', $icStory ); 
				if( $icHLUdEntries->length > 0 && ($icHLUdElem = $icHLUdEntries->item(0)) ) {
					$icURL = new ICDBToken( $icHLUdElem->getAttribute('hURL') );
					LogHandler::Log( 'textconv', 'DEBUG', 'createHyperlinkXHTML: Found URL ['.$icURL->value.'] for hyperlink object ['.$icHLOBSelf.']');
					$title = new ICDBToken( $icHLOBElem->getAttribute('pnam') );
					$xLink = $xDoc->createElement( 'a' );
					$xLink->setAttribute( 'href', $icURL->value );
					$xLink->setAttribute( 'id', $icHLOBSelf );
					$xLink->setAttribute( 'title', $title->value );
					return $xLink;
				}
			}
		}
		return null;
	}
}

/**
 * InCopy databases (files) are using tokens to uniquely identify objects and to make references between them.
 * A token consists of a type indicator and a value and are stored in an XML attribute, such as: GUID="k_abc".
 * The "k" is the type indicator, the "_" is a seperator, and "abc" is the value.
 * This class parses and streams such tokens.
 */
class ICDBToken
{
	public $type = '';
	public $value = '';
	
	/**
	 * Parse a token, by splitting up given token string into type and value.
	 * @param string $tokenStr Type and value separated by "_"
	 */
	public function __construct( $tokenStr )
	{
		if( strlen($tokenStr) ) {
			$buf = explode( '_', $tokenStr, 2 );
			$this->type = $buf[0];
			$this->value = $buf[1];
		}
	}
	
	/**
	 * Streams a token's type and value back into a string.
	 * @return string Type and value separated by "_"
	 */
	public function toString()
	{
		return $this->type.'_'.$this->value;
	}
}
