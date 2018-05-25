<?php
/**
 * @since 		v9.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Generic logic for the Autocomplete feature that can be inheritted by service specific
 * implementations, such as the Workflow and Administration interfaces.
 */
class BizAutocompleteBase
{
	/**
	 * Returns the path name where the Term Entity file should reside.
	 *
	 * Adds a prefix to the Autocomplete provider($provider) to indicate if the provider
	 * is from third party or Enterprise itself.
	 * When the publishsystemid is not empty, it is also added to the path name.
	 *
	 * @param string $provider The name of the provider to be used as the folder path.
	 * @param string $publishSystemId Unique id of the publish system which will be included in the folder path if not empty.
	 * @return string The prefixed provider with $publishSystemId when not empty.
	 */
	protected static function composeBookshelf( $provider, $publishSystemId )
	{
		$bookshelf = $provider ? $provider.'_plugin' : 'Enterprise';
		if( $publishSystemId ) {
			$bookshelf .= '/' .$publishSystemId;
		}
		return $bookshelf;
	}

	/**
	 * To set the PublishSystemId to be empty when it is set to null.
	 *
	 * PublishSystemId when:
	 * 1. Set to valid GUID: The provider connector deals with publishing system that has more than one instance.
	 * 2. Empty: The provider connector integrates with max one publish system instance.
	 * 3. Null: The field is not applicable, so nothing to set.
	 *
	 * In this case, the context is always about Term Entity, thus PublishSystemId
	 * should be set. When not set(null), this function assign it to empty which implies
	 * the autocomplete provider connector integrates with max one publish system instance.
	 *
	 * @param string $publishSystemId
	 */
	protected static function enrichNullPublishSystemId( &$publishSystemId )
	{
		if( is_null( $publishSystemId ) ) {
			$publishSystemId = '';
		}
	}

	/**
	 * Get a list of Autocomplete terms that belong to the TermEntity $termEntity.
	 *
	 * @param string $provider The Autocomplete provider name.
	 * @param string $publishSystemId Unique id of the publishing system. Use to bind the publishing storage.
	 * @param string $termEntityName Name of the Term Entity that contain its own terms.
	 * @param string $typedValue The value the user has typed so far, will be used  to match the terms to be returned.
	 * @param string[] $ignoreValues List of values that should be ignored while searching for $searchPhrase.
	 * @param int|null $firstEntry The starting entry of the term in the terms list that should be returned to the caller.
	 * @param int|null $maxEntries The max entries of the terms that should be returned starting from the $firstEntry.
	 * @return AutoSuggestTag[] Returns a list of autocomplete suggestions for the requested TermEntity.
	 */
	public static function searchAutocompleteTerms( $termEntityName, $provider, $publishSystemId,
	                                                $typedValue, $ignoreValues, $firstEntry = null, $maxEntries = null )
	{
		require_once BASEDIR . '/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		require_once BASEDIR . '/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR . '/server/utils/Autocomplete.class.php';

		if( !$firstEntry ) {
			$firstEntry = 1;
		}
		if( !$maxEntries ) {
			$maxEntries = 6;
		}

		// Fast lookup the normalized terms into the local dictionary (file).
		self::enrichNullPublishSystemId( $publishSystemId );
		$normalizedTypedValue = self::normalizeTerm( $typedValue );
		$entityId = DBAdmAutocompleteTermEntity::getTermEntityId( $provider, $publishSystemId, $termEntityName );
		$autocompleteUtils = new WW_Utils_Autocomplete( $entityId );
		$bookshelf = self::composeBookshelf( $provider, $publishSystemId );
		$autocompleteUtils->openTermsFile( $bookshelf, $termEntityName );
		$hits = 0;

		$normalizedIgnoreValues = array();
		foreach($ignoreValues as $ignoreValue){
			$normalizedIgnoreValues[] = self::normalizeTerm( $ignoreValue );
		}

		$foundTermTags = $autocompleteUtils->searchTerms( $normalizedTypedValue, $hits, $normalizedIgnoreValues, $firstEntry, $maxEntries  );
		$autocompleteUtils->closeTermsFile();

		// 'Translate' the found normalized terms into their display names.
		if( $entityId ) {
			if( $foundTermTags ) foreach( $foundTermTags as $foundTermTag ) {
				// TODO: see if we can find all terms at once (optimization) or else we might do many SQL queries here
				$displayNames = DBAdmAutocompleteTerm::getTermsByNormalizedName( $entityId, $foundTermTag->Value );
				if( isset($displayNames[0]) ) {
					$ligatures = $displayNames[0]->Ligatures;
					if( strpos( $ligatures, '2' ) !== false ) {

						// Transform the StartPos of the normalized name into the StartPos of the display name.
						$sumStartPos = 0; // Sum of ligatures
						$startPos = 0; // Position in display name.
						$i = 0;
						while( $sumStartPos < $foundTermTag->StartPos ) {
							$sumStartPos += intval($ligatures[$i]);
							$startPos += 1;
							$i += 1;
						}
						$foundTermTag->StartPos = $startPos;

						// Transform the Length of the normalized name into the Length of the display name.
						$sumLength = 0; // Sum of ligatures
						$length = 0; // Position in display name.
						$i = $startPos;
						while( $sumLength < $foundTermTag->Length ) {
							$sumLength += intval($ligatures[$i]);
							$length += 1;
							$i+=1;
						}
						$foundTermTag->Length = $length;
					}
					$foundTermTag->Value = $displayNames[0]->DisplayName;
				}
			}
		}

		return $foundTermTags;
	}

	/**
	 * Normalizes a given term.
	 *
	 * All terms have a display version and a normalized version.
	 * Search phrases should be normalized before they are looked up in the normalized dictionary
	 * of terms. This is all done to ease finding matching terms based on the search phrase.
	 * This function removes accents, removes reading symbols and lower-cases the given string.
	 *
	 * @param string $term The Term to normalize.
	 * @return string The normalized term.
	 */
	protected static function normalizeTerm( $term )
	{
		$term = self::removeAccents( $term );
		$term = self::removeSymbols( $term );
		$term = mb_convert_case( $term, MB_CASE_LOWER, 'UTF-8' );
		return $term;
	}

	/**
	 * Removes reading symbols from a given term.
	 *
	 * The following symbols are removed:  - _ / \ ( ) , . ' ` ´ & : ;
	 * This function is called by {@link: normalizeTerm()} to normalize terms.
	 *
	 * @param string $term The Term for which to remove symbols.
	 * @return string The Term without symbols.
	 */
	private static function removeSymbols( $term )
	{
		static $chars;
		if( !$chars ) {
			$chars = array(
				'-' => ' ',
				'_' => ' ',
				'/' => ' ',
				'\\' => ' ',
				'(' => ' ',
				')' => ' ',
				'\'' => ' ',
				'`' => ' ',
				'´' => ' ',
				',' => ' ',
				'.' => ' ',
				'&' => ' ',
				':' => ' ',
				';' => ' ',
			);
		}
		$term = strtr( $term, $chars );

		// The operation above could lead to double spaces, for example "St. Augustine" could become
		// "st  augustine" with two spaces but should become "st  augustine" with one space.
		// This is repaired below.
		$term = str_replace( '  ', ' ', $term ); // Remove double spaces.
		return trim( $term, ' ' ); // Remove spaces from begin and end.
	}

	/**
	 * Removes accents from a given term.
	 *
	 * For example "e" with accent grave becomes a normal "e" without accent. Most Latin-1 accents are removed.
	 * This function is called by {@link: normalizeTerm()} to normalize terms.
	 *
	 * @param string $term Term that may have accents.
	 * @return string Term without accents.
	 */
	private static function removeAccents( $term )
	{
		if( !preg_match( '/[\x80-\xff]/', $term ) ) {
			return $term;
		}

		$chars = self::getCharMap();
		return strtr( $term, $chars );
	}

	/**
	 * Returns a character map of a UTF-8 bytes notation with its normalized characters.
	 *
	 * @return array List of key-value pair where the key is the UTF-8 notation and value is normalized characters.
	 */
	private static function getCharMap()
	{
		static $chars;
		if( !$chars ) {
			// Mapping table with the following format:
			//  "UTF-8 bytes" => 'normalized char(s)' // UTF-16 hex code.
			// Note that the UTF-16 hex code can be looked up as follows:
			//   http://www.fileformat.info/info/unicode/char/<code>/index.htm
			// but you can also use the Wiki pages as shown between the lines below.
			$chars = array(

				// http://en.wikipedia.org/wiki/Latin-1_Supplement_(Unicode_block)
				"\xC2\xAA" => 'a', // 00AA
				"\xC2\xBA" => 'o', // 00BA
				"\xC3\x80" => 'A', // 00C0
				"\xC3\x81" => 'A', // 00C1
				"\xC3\x82" => 'A', // 00C2
				"\xC3\x83" => 'A', // 00C3
				"\xC3\x84" => 'A', // 00C4
				"\xC3\x85" => 'A', // 00C5
				"\xC3\x86" => 'AE', // 00C6
				"\xC3\x87" => 'C', // 00C7
				"\xC3\x88" => 'E', // 00C8
				"\xC3\x89" => 'E', // 00C9
				"\xC3\x8A" => 'E', // 00CA
				"\xC3\x8B" => 'E', // 00CB
				"\xC3\x8C" => 'I', // 00CC
				"\xC3\x8D" => 'I', // 00CD
				"\xC3\x8E" => 'I', // 00CE
				"\xC3\x8F" => 'I', // 00CF
				"\xC3\x90" => 'D', // 00D0
				"\xC3\x91" => 'N', // 00D1
				"\xC3\x92" => 'O', // 00D2
				"\xC3\x93" => 'O', // 00D3
				"\xC3\x94" => 'O', // 00D4
				"\xC3\x95" => 'O', // 00D5
				"\xC3\x96" => 'O', // 00D6
				"\xC3\x99" => 'U', // 00D9
				"\xC3\x9A" => 'U', // 00DA
				"\xC3\x9B" => 'U', // 00DB
				"\xC3\x9C" => 'U', // 00DC
				"\xC3\x9D" => 'Y', // 00DD
				"\xC3\x9E" => 'TH', // 00DE
				"\xC3\x9F" => 's', // 00DF
				"\xC3\xA0" => 'a', // 00E0
				"\xC3\xA1" => 'a', // 00E1
				"\xC3\xA2" => 'a', // 00E2
				"\xC3\xA3" => 'a', // 00E3
				"\xC3\xA4" => 'a', // 00E4
				"\xC3\xA5" => 'a', // 00E5
				"\xC3\xA6" => 'ae', // 00E6
				"\xC3\xA7" => 'c', // 00E7
				"\xC3\xA8" => 'e', // 00E8
				"\xC3\xA9" => 'e', // 00E9
				"\xC3\xAA" => 'e', // 00EA
				"\xC3\xAB" => 'e', // 00EB
				"\xC3\xAC" => 'i', // 00EC
				"\xC3\xAD" => 'i', // 00ED
				"\xC3\xAE" => 'i', // 00EE
				"\xC3\xAF" => 'i', // 00EF
				"\xC3\xB0" => 'd', // 00F0
				"\xC3\xB1" => 'n', // 00F1
				"\xC3\xB2" => 'o', // 00F2
				"\xC3\xB3" => 'o', // 00F3
				"\xC3\xB4" => 'o', // 00F4
				"\xC3\xB5" => 'o', // 00F5
				"\xC3\xB6" => 'o', // 00F6
				"\xC3\xB8" => 'o', // 00F8
				"\xC3\xB9" => 'u', // 00F9
				"\xC3\xBA" => 'u', // 00FA
				"\xC3\xBB" => 'u', // 00FB
				"\xC3\xBC" => 'u', // 00FC
				"\xC3\xBD" => 'y', // 00FD
				"\xC3\xBE" => 'th', // 00FE
				"\xC3\xBF" => 'y', // 00FF
				"\xC3\x98" => 'O', // 00D8

				// http://en.wikipedia.org/wiki/Latin_Extended-A
				"\xC4\x80" => 'A', // 01001
				"\xC4\x81" => 'a', // 01001
				"\xC4\x82" => 'A', // 01001
				"\xC4\x83" => 'a', // 01001
				"\xC4\x84" => 'A', // 01001
				"\xC4\x85" => 'a', // 01001
				"\xC4\x86" => 'C', // 01001
				"\xC4\x87" => 'c', // 01001
				"\xC4\x88" => 'C', // 01001
				"\xC4\x89" => 'c', // 01001
				"\xC4\x8A" => 'C', // 01001
				"\xC4\x8B" => 'c', // 01001
				"\xC4\x8C" => 'C', // 01001
				"\xC4\x8D" => 'c', // 01001
				"\xC4\x8E" => 'D', // 01001
				"\xC4\x8F" => 'd', // 01001
				"\xC4\x90" => 'D', // 0110
				"\xC4\x91" => 'd', // 0111
				"\xC4\x92" => 'E', // 0112
				"\xC4\x93" => 'e', // 0113
				"\xC4\x94" => 'E', // 0114
				"\xC4\x95" => 'e', // 0115
				"\xC4\x96" => 'E', // 0116
				"\xC4\x97" => 'e', // 0117
				"\xC4\x98" => 'E', // 0118
				"\xC4\x99" => 'e', // 0119
				"\xC4\x9A" => 'E', // 011A
				"\xC4\x9B" => 'e', // 011B
				"\xC4\x9C" => 'G', // 011C
				"\xC4\x9D" => 'g', // 011D
				"\xC4\x9E" => 'G', // 011E
				"\xC4\x9F" => 'g', // 011F
				"\xC4\xA0" => 'G', // 0120
				"\xC4\xA1" => 'g', // 0121
				"\xC4\xA2" => 'G', // 0122
				"\xC4\xA3" => 'g', // 0123
				"\xC4\xA4" => 'H', // 0124
				"\xC4\xA5" => 'h', // 0125
				"\xC4\xA6" => 'H', // 0126
				"\xC4\xA7" => 'h', // 0127
				"\xC4\xA8" => 'I', // 0128
				"\xC4\xA9" => 'i', // 0129
				"\xC4\xAA" => 'I', // 012A
				"\xC4\xAB" => 'i', // 012B
				"\xC4\xAC" => 'I', // 012C
				"\xC4\xAD" => 'i', // 012D
				"\xC4\xAE" => 'I', // 012E
				"\xC4\xAF" => 'i', // 012F
				"\xC4\xB0" => 'I', // 0130
				"\xC4\xB1" => 'i', // 0131
				"\xC4\xB2" => 'IJ', // 0132
				"\xC4\xB3" => 'ij', // 0133
				"\xC4\xB4" => 'J', // 0134
				"\xC4\xB5" => 'j', // 0135
				"\xC4\xB6" => 'K', // 0136
				"\xC4\xB7" => 'k', // 0137
				"\xC4\xB8" => 'k', // 0138
				"\xC4\xB9" => 'L', // 0139
				"\xC4\xBA" => 'l', // 013A
				"\xC4\xBB" => 'L', // 013B
				"\xC4\xBC" => 'l', // 013C
				"\xC4\xBD" => 'L', // 013D
				"\xC4\xBE" => 'l', // 013E
				"\xC4\xBF" => 'L', // 013F
				"\xC5\x80" => 'l', // 0140
				"\xC5\x81" => 'L', // 0141
				"\xC5\x82" => 'l', // 0142
				"\xC5\x83" => 'N', // 0143
				"\xC5\x84" => 'n', // 0144
				"\xC5\x85" => 'N', // 0145
				"\xC5\x86" => 'n', // 0146
				"\xC5\x87" => 'N', // 0147
				"\xC5\x88" => 'n', // 0148
				"\xC5\x89" => 'N', // 0149
				"\xC5\x8A" => 'n', // 014A
				"\xC5\x8B" => 'N', // 014B
				"\xC5\x8C" => 'O', // 014C
				"\xC5\x8D" => 'o', // 014D
				"\xC5\x8E" => 'O', // 014E
				"\xC5\x8F" => 'o', // 014F
				"\xC5\x90" => 'O', // 0150
				"\xC5\x91" => 'o', // 0151
				"\xC5\x92" => 'OE', // 0152
				"\xC5\x93" => 'oe', // 0153
				"\xC5\x94" => 'R', // 0154
				"\xC5\x95" => 'r', // 0155
				"\xC5\x96" => 'R', // 0156
				"\xC5\x97" => 'r', // 0157
				"\xC5\x98" => 'R', // 0158
				"\xC5\x99" => 'r', // 0159
				"\xC5\x9A" => 'S', // 015A
				"\xC5\x9B" => 's', // 015B
				"\xC5\x9C" => 'S', // 015C
				"\xC5\x9D" => 's', // 015D
				"\xC5\x9E" => 'S', // 015E
				"\xC5\x9F" => 's', // 015F
				"\xC5\xA0" => 'S', // 0160
				"\xC5\xA1" => 's', // 0161
				"\xC5\xA2" => 'T', // 0162
				"\xC5\xA3" => 't', // 0163
				"\xC5\xA4" => 'T', // 0164
				"\xC5\xA5" => 't', // 0165
				"\xC5\xA6" => 'T', // 0166
				"\xC5\xA7" => 't', // 0167
				"\xC5\xA8" => 'U', // 0168
				"\xC5\xA9" => 'u', // 0169
				"\xC5\xAA" => 'U', // 016A
				"\xC5\xAB" => 'u', // 016B
				"\xC5\xAC" => 'U', // 016C
				"\xC5\xAD" => 'u', // 016D
				"\xC5\xAE" => 'U', // 016E
				"\xC5\xAF" => 'u', // 016F
				"\xC5\xB0" => 'U', // 0170
				"\xC5\xB1" => 'u', // 0171
				"\xC5\xB2" => 'U', // 0172
				"\xC5\xB3" => 'u', // 0173
				"\xC5\xB4" => 'W', // 0174
				"\xC5\xB5" => 'w', // 0175
				"\xC5\xB6" => 'Y', // 0176
				"\xC5\xB7" => 'y', // 0177
				"\xC5\xB8" => 'Y', // 0178
				"\xC5\xB9" => 'Z', // 0179
				"\xC5\xBA" => 'z', // 017A
				"\xC5\xBB" => 'Z', // 017B
				"\xC5\xBC" => 'z', // 017C
				"\xC5\xBD" => 'Z', // 017D
				"\xC5\xBE" => 'z', // 017E
				"\xC5\xBF" => 's', // 017F

				// http://en.wikipedia.org/wiki/Latin_Extended-B
				"\xC6\xA0" => 'O', // 01A0
				"\xC6\xA1" => 'o', // 01A1
				"\xC6\xAF" => 'U', // 01AF
				"\xC6\xB0" => 'u', // 01B0
				"\xC7\x95" => 'U', // 01D5
				"\xC7\x96" => 'u', // 01D6
				"\xC7\x97" => 'U', // 01D7
				"\xC7\x98" => 'u', // 01D8
				"\xC7\x8D" => 'A', // 01CD
				"\xC7\x8E" => 'a', // 01CE
				"\xC7\x8F" => 'I', // 01CF
				"\xC7\x90" => 'i', // 01D0
				"\xC7\x91" => 'O', // 01D1
				"\xC7\x92" => 'o', // 01D2
				"\xC7\x93" => 'U', // 01D3
				"\xC7\x94" => 'u', // 01D4
				"\xC7\x99" => 'U', // 01D9
				"\xC7\x9A" => 'u', // 01DA
				"\xC7\x9B" => 'U', // 01DB
				"\xC7\x9C" => 'u', // 01DC
				"\xC8\x98" => 'S', // 0218
				"\xC8\x99" => 's', // 0219
				"\xC8\x9A" => 'T', // 021A
				"\xC8\x9B" => 't', // 021B

				// http://en.wikipedia.org/wiki/Latin_Extended_Additional
				"\xE1\xBA\xA6" => 'A', // 1EA6
				"\xE1\xBA\xA7" => 'a', // 1EA7
				"\xE1\xBA\xB0" => 'A', // 1EB0
				"\xE1\xBA\xB1" => 'a', // 1EB1
				"\xE1\xBB\x80" => 'E', // 1EC0
				"\xE1\xBB\x81" => 'e', // 1EC1
				"\xE1\xBB\x92" => 'O', // 1ED2
				"\xE1\xBB\x93" => 'o', // 1ED3
				"\xE1\xBB\x9C" => 'O', // 1EDC
				"\xE1\xBB\x9D" => 'o', // 1EDD
				"\xE1\xBB\xAA" => 'U', // 1EEA
				"\xE1\xBB\xAB" => 'u', // 1EEB
				"\xE1\xBB\xB2" => 'Y', // 1EF2
				"\xE1\xBB\xB3" => 'y', // 1EF3
				"\xE1\xBA\xA2" => 'A', // 1EA2
				"\xE1\xBA\xA3" => 'a', // 1EA3
				"\xE1\xBA\xA8" => 'A', // 1EA8
				"\xE1\xBA\xA9" => 'a', // 1EA9
				"\xE1\xBA\xB2" => 'A', // 1EB2
				"\xE1\xBA\xB3" => 'a', // 1EB3
				"\xE1\xBA\xBA" => 'E', // 1EBA
				"\xE1\xBA\xBB" => 'e', // 1EBB
				"\xE1\xBB\x82" => 'E', // 1EC2
				"\xE1\xBB\x83" => 'e', // 1EC3
				"\xE1\xBB\x88" => 'I', // 1EC8
				"\xE1\xBB\x89" => 'i', // 1EC9
				"\xE1\xBB\x8E" => 'O', // 1ECE
				"\xE1\xBB\x8F" => 'o', // 1ECF
				"\xE1\xBB\x94" => 'O', // 1ED4
				"\xE1\xBB\x95" => 'o', // 1ED5
				"\xE1\xBB\x9E" => 'O', // 1EDE
				"\xE1\xBB\x9F" => 'o', // 1EDF
				"\xE1\xBB\xA6" => 'U', // 1EE6
				"\xE1\xBB\xA7" => 'u', // 1EE7
				"\xE1\xBB\xAC" => 'U', // 1EEC
				"\xE1\xBB\xAD" => 'u', // 1EED
				"\xE1\xBB\xB6" => 'Y', // 1EF6
				"\xE1\xBB\xB7" => 'y', // 1EF7
				"\xE1\xBA\xAA" => 'A', // 1EAA
				"\xE1\xBA\xAB" => 'a', // 1EAB
				"\xE1\xBA\xB4" => 'A', // 1EB4
				"\xE1\xBA\xB5" => 'a', // 1EB5
				"\xE1\xBA\xBC" => 'E', // 1EBC
				"\xE1\xBA\xBD" => 'e', // 1EBD
				"\xE1\xBB\x84" => 'E', // 1EC4
				"\xE1\xBB\x85" => 'e', // 1EC5
				"\xE1\xBB\x96" => 'O', // 1ED6
				"\xE1\xBB\x97" => 'o', // 1ED7
				"\xE1\xBB\xA0" => 'O', // 1EE0
				"\xE1\xBB\xA1" => 'o', // 1EE1
				"\xE1\xBB\xAE" => 'U', // 1EEE
				"\xE1\xBB\xAF" => 'u', // 1EEF
				"\xE1\xBB\xB8" => 'Y', // 1EF8
				"\xE1\xBB\xB9" => 'y', // 1EF9
				"\xE1\xBA\xA4" => 'A', // 1EA4
				"\xE1\xBA\xA5" => 'a', // 1EA5
				"\xE1\xBA\xAE" => 'A', // 1EAE
				"\xE1\xBA\xAF" => 'a', // 1EAF
				"\xE1\xBA\xBE" => 'E', // 1EBE
				"\xE1\xBA\xBF" => 'e', // 1EBF
				"\xE1\xBB\x90" => 'O', // 1ED0
				"\xE1\xBB\x91" => 'o', // 1ED1
				"\xE1\xBB\x9A" => 'O', // 1EDA
				"\xE1\xBB\x9B" => 'o', // 1EDB
				"\xE1\xBB\xA8" => 'U', // 1EE8
				"\xE1\xBB\xA9" => 'u', // 1EE9
				"\xE1\xBA\xA0" => 'A', // 1EA0
				"\xE1\xBA\xA1" => 'a', // 1EA1
				"\xE1\xBA\xAC" => 'A', // 1EAC
				"\xE1\xBA\xAD" => 'a', // 1EAD
				"\xE1\xBA\xB6" => 'A', // 1EB6
				"\xE1\xBA\xB7" => 'a', // 1EB7
				"\xE1\xBA\xB8" => 'E', // 1EB8
				"\xE1\xBA\xB9" => 'e', // 1EB9
				"\xE1\xBB\x86" => 'E', // 1EC6
				"\xE1\xBB\x87" => 'e', // 1EC7
				"\xE1\xBB\x8A" => 'I', // 1ECA
				"\xE1\xBB\x8B" => 'i', // 1ECB
				"\xE1\xBB\x8C" => 'O', // 1ECC
				"\xE1\xBB\x8D" => 'o', // 1ECD
				"\xE1\xBB\x98" => 'O', // 1ED8
				"\xE1\xBB\x99" => 'o', // 1ED9
				"\xE1\xBB\xA2" => 'O', // 1EE2
				"\xE1\xBB\xA3" => 'o', // 1EE3
				"\xE1\xBB\xA4" => 'U', // 1EE4
				"\xE1\xBB\xA5" => 'u', // 1EE5
				"\xE1\xBB\xB0" => 'U', // 1EF0
				"\xE1\xBB\xB1" => 'u', // 1EF1
				"\xE1\xBB\xB4" => 'Y', // 1EF4
				"\xE1\xBB\xB5" => 'y', // 1EF5

				// http://en.wikipedia.org/wiki/IPA_Extensions
				"\xC9\x91" => 'a', // 0251

			);
		}

		return $chars;
	}

	/**
	 * Composes a list of integers to indicate each and every character's ligature.
	 *
	 * @param string $term The display(non-normalized) term to get its ligature for each character.
	 * @return int[] List of ligature for each character in $term.
	 */
	protected static function calculateLigaturesForTerm( $term )
	{
		$ligatures = array();
		$charMap = self::getCharMap();
		$len = mb_strlen( $term );
		for( $i = 0; $i < $len; $i++ ) {
			$char = mb_substr( $term, $i, 1 ); // one unicode char
			if( array_key_exists( $char, $charMap )) {
				$ligatures[] = mb_strlen( $charMap[$char] ); // mostly 1, sometimes 2, in theory > 2
			} else {
				// The whole map is indexed by single chars (although those could be multi byte)
				// and so it is safe to assume that the ligature is also one char.
				$ligatures[] = 1;
			}
		}
		return $ligatures;
	}
}