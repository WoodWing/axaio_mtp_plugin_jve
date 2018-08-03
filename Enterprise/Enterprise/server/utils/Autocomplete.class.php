<?php
/**
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Offers functionality to install and maintain list of entity terms.
 * Terms can be added, removed and searched. Typically useful for fast lookup when implementing an
 * Autocomplete provider. Terms are persistent and shared across
 * all application servers that belong to the same Enterprise Server installation.
 * Terms should be normalized before storing them in the Terms file. That means they should
 * be lower-cased and that accents, white space and dashes should be stripped from the term.
 */

// TODO: The errors strings should be reviewed.

class WW_Utils_Autocomplete
{
	private $termsFile;
	private $terms;
	private $semaphoreName;

	/**
	 * @param string $semaphoreName
	 */
	public function __construct( $semaphoreName )
	{
		$this->semaphoreName = $semaphoreName;
	}

	/**
	 * Opens a Terms file. It will be created if it does not exist.
	 *
	 * @param string $bookshelf The main directory location where the Terms file resides.
	 * @param string $termsFileName The name of the Terms file file to open, excluding the extension.
	 * @throws BizException Throws an Exception when the dictionary file cannot be opened / created.
	 */
	public function openTermsFile( $bookshelf, $termsFileName )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'Autocomplete' . $this->semaphoreName;
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );

		if( !$semaphoreId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaphoreName );
			$details = 'Failed opening/creating "'.$this->termsFile.'" file because user "'. $otherUser .
				'" is performing the same operation.';
			throw new BizException( 'ERR_ERROR', 'Server', $details );
		}

		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$retVal = false;
		if( $this->termsFile ) {
			$this->closeTermsFile();
		}
		$this->termsFile = AUTOCOMPLETEDIRECTORY;
		if( $bookshelf ) {
			$this->termsFile .= '/'.$bookshelf;
		}
		if( FolderUtils::mkFullDir( $this->termsFile ) ) {
			$this->termsFile .= '/'.$termsFileName.'.txt';
			$this->terms = array();
			$retVal = true;
			if( !file_exists( $this->termsFile ) ) {
				if( file_put_contents( $this->termsFile, '' ) === false ) {
					$retVal = false;
				}
			}
		}
		if( $semaphoreId ) {
			$bizSemaphore->releaseSemaphore( $semaphoreId );
		}
		if( !$retVal ) {
			throw new BizException( 'ERR_ERROR', 'Server', 'Failed opening/creating "'.$this->termsFile.'" file.' );
		}
	}
	
	/**
	 * Imports a Terms file.
	 *
	 * Raw import of an external Terms data file. Could be used to install
	 * a Terms file for the first time. If already installed before, nothing
	 * will happen and FALSE will be returned.
	 *
	 * @param string $termsFile Full path to the remote Terms file to import.
	 * @throws BizException Throws an Exception when the import was not successful.
	 * @return bool Whether or not the import was successful.
	 */
	public function importTermsFile( $termsFile )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'Autocomplete' . $this->semaphoreName;
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );

		if( !$semaphoreId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaphoreName );
			$details = 'Failed importing Terms file into "'.$this->termsFile.'" because user "'.$otherUser.'" ' .
				'is performing the same operation.';
			throw new BizException( 'ERR_ERROR', 'Server', $details );
		}

		$retVal = false;
		if( $this->termsFile ) {
			if( !file_exists($this->termsFile) ) {
				$retVal = copy( $termsFile, $this->termsFile );
			} else {
				$retVal = true; // TermEntity file already exists, don't import (copy).
			}
		}
		if( $semaphoreId ) {
			$bizSemaphore->releaseSemaphore( $semaphoreId );
		}
		if( !$retVal ) {
			throw new BizException( '', 'Server', 'Failed copying "'.$termsFile.'" into "'.$this->termsFile.'".',
																BizResources::localize( 'IMPORT_TERMENTITY_FAILED' ) );
		}
		return $retVal;
	}
	
	/**
	 * Closes a Terms file.
	 *
	 * Closes the Terms file after usage. It clears internal memory usage, which can be extensive.
	 *
	 * @return bool Whether or not the Terms file was closed successfully.
	 */
	public function closeTermsFile()
	{
		$this->termsFile = null;
		$this->terms = array();
		return true;
	}

	/**
	 * Rename a Terms file.
	 *
	 * {@link: openTermsFile()} needs to be called first before calling this function.
	 *
	 * @param string $bookshelf The main directory location where the Terms file resides.
	 * @param string $newTermsFileName The new name of the Terms file, excluding the extension.
	 * @throws BizException Throws an Exception when the modify operation fails.
	 */
	public function modifyTermsFile( $bookshelf, $newTermsFileName )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'Autocomplete' . $this->semaphoreName;
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );

		if( !$semaphoreId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaphoreName );
			$details = 'Failed modifying Terms file because user "'.$otherUser.'" is performing the same operation.';
			throw new BizException( 'ERR_ERROR', 'Server', $details );
		}

		require_once BASEDIR .'/server/utils/FolderUtils.class.php';
		// New Terms file
		$newTermsFile = AUTOCOMPLETEDIRECTORY;
		if( $bookshelf ) {
			$newTermsFile .= '/'.$bookshelf;
		}
		if( !FolderUtils::mkFullDir( $newTermsFile ) ) {
			throw new BizException( 'ERR_ERROR', 'Server', 'Failed opening/creating the file: "'.$newTermsFile.'"' );
		}
		$newTermsFile .= '/'. $newTermsFileName .'.txt';

		// Do the rename operation.
		if( file_exists( $this->termsFile ) ) {
			if( !rename( $this->termsFile, $newTermsFile ) ) {
				if( $semaphoreId ) {
					$bizSemaphore->releaseSemaphore( $semaphoreId );
				}
				throw new BizException( 'ERR_ERROR', 'Server', 'Failed renaming file "'.$this->termsFile.
						'" into new name ".$newTermsFile.".');
			}

		} else {
			if( $semaphoreId ) {
				$bizSemaphore->releaseSemaphore( $semaphoreId );
			}
			throw new BizException( 'ERR_ERROR', 'Server', 'Failed to modify Terms file because the original file '.
				'"'.$this->termsFile.'" was not found.' );
		}
		if( $semaphoreId ) {
			$bizSemaphore->releaseSemaphore( $semaphoreId );
		}
	}

	/**
	 * Deletes a Terms file.
	 *
	 * Removes the whole Terms file with all its terms. This can be used to uninstall a Terms file.
	 *
	 * IMPORTANT: This operation cannot be undone.
	 *
	 * @throws BizException
	 * @return bool Whether or not the Terms file was deleted successfully.
	 */
	public function deleteTermsFile()
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'Autocomplete' . $this->semaphoreName;
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );

		if( !$semaphoreId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaphoreName );
			$details = 'Failed deleting Terms file because user "'.$otherUser.'" is performing the same operation.';
			throw new BizException( 'ERR_ERROR', 'Server', $details );
		}

		$retVal = false;
		if( $this->termsFile && file_exists($this->termsFile) ) {
			$retVal = unlink( $this->termsFile );
			if( !$retVal ) {
				// Not fatal, so just log it instead of throwing error.
				LogHandler::Log( 'WW_Utils_Autocomplete', 'ERROR', 'Failed deleting file "'.$this->termsFile.'"' );
			}
			$this->termsFile = null;
			$this->terms = array();
		}
		if( $semaphoreId ) {
			$bizSemaphore->releaseSemaphore( $semaphoreId );
		}
		return $retVal;
	}
	
	/**
	 * Adds terms to the opened Terms file.
	 *
	 * @param string[] $terms List of terms to add. Should be in UTF-8 format.
	 * @throws BizException Throws an exception when an error occurs when adding terms in the Terms file.
	 */
	public function addTerms( array $terms )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'Autocomplete' . $this->semaphoreName;
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );

		if( !$semaphoreId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaphoreName );
			$details = 'Failed adding Terms from the Terms file because user "'.$otherUser.'" is performing the same operation.';
			throw new BizException( 'ERR_ERROR', 'Server', $details );
		}

		if( $this->termsFile ) {
			$this->readTermsFile();
			if( $terms ) foreach( $terms as $term ) {
				$term = trim($term);
				if( !empty($term) && !array_key_exists( $term, $this->terms ) ) {
					$this->terms[$term] = true;
				}
			}
			ksort($this->terms);
			if( !$this->writeTermsFile() ) {
				if( $semaphoreId ) {
					$bizSemaphore->releaseSemaphore( $semaphoreId );
				}
				throw new BizException( 'ERR_ERROR', 'Server', 'Error writing file "'.$this->termsFile.
						'": A list of terms cannot be added into the file.' );
			}
		} else {
			if( $semaphoreId ) {
				$bizSemaphore->releaseSemaphore( $semaphoreId );
			}
			throw new BizException( 'ERR_ERROR', 'Server', '"'.$this->termsFile.'" file does not exists, ' .
					'no Terms were written into the file.' );
		}
		if( $semaphoreId ) {
			$bizSemaphore->releaseSemaphore( $semaphoreId );
		}
	}

	/**
	 * Removes terms from the Terms file.
	 *
	 * @param string[] $terms List of terms to remove. Should be in UTF-8 format.
	 * @throws BizException Throws an exception when deleting the terms from the Terms file fails.
	 */
	public function removeTerms( array $terms )
	{
		$semaphoreId = null;
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'Autocomplete' . $this->semaphoreName;
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );

		if( !$semaphoreId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaphoreName );
			$details = 'Failed removing Terms from the Terms file because user "'.$otherUser.'" is performing the same operation.';
			throw new BizException( 'ERR_ERROR', 'Server', $details );
		}

		if( $this->termsFile ) {
			$this->readTermsFile();
			if( $terms ) foreach( $terms as $term ) {
				if( array_key_exists( $term, $this->terms ) ) {
					unset($this->terms[$term]);
				}
			}
			if( !$this->writeTermsFile() ) {
				if( $semaphoreId ) {
					$bizSemaphore->releaseSemaphore( $semaphoreId );
				}
				throw new BizException( 'ERR_ERROR', 'Server', 'Error writing file "'.$this->termsFile.
								'": A list of terms cannot be deleted from the file.' );
			}
		} else {
			if( $semaphoreId ) {
				$bizSemaphore->releaseSemaphore( $semaphoreId );
			}
			throw new BizException( 'ERR_ERROR', 'Server', '"'.$this->termsFile.'" file does not exists, ' .
				'Terms cannot be deleted.' );
		}
		if( $semaphoreId ) {
			$bizSemaphore->releaseSemaphore( $semaphoreId );
		}
	}
	
	/**
	 * Performs a full text search on the Terms file.
	 *
	 * Returns all terms that match the given search phrase: "*foo*". By default it only returns the top 6
	 * best matching terms. This can be changed with the optional $maxResults parameter.
	 * For each term a score is given which indicates how well it matches the search phrase.
	 * Note that wildcards (e.g. * and ?) are NOT supported.
	 *
	 * @param string $searchPhrase The phrase to search in the Terms file.
	 * @param integer $hits Returns the total number of matching terms (which might be > $maxResults).
	 * @param string[] $ignoreValues List of values that should be ignored while searching for $searchPhrase.
	 * @param integer $firstEntry The starting entry of the term in the terms list that should be returned to the caller.
	 * @param integer $maxResults Limits the max number of matching terms to be returned.
	 * @return AutoSuggestTag[]
	 *
	 * @TODO: To implement the paging. Currently $firstEntry, $maxEntries are not taken into account.
	 */
	public function searchTerms( $searchPhrase, &$hits, $ignoreValues=array(), $firstEntry, $maxResults )
	{
		$searchResult = null;
		$searchPhraseLen = 0;
		$eol = chr(0x0A);
		$hits = 0;
		$numberOfIgnoreValues = count( $ignoreValues );
		if( $this->termsFile && file_exists($this->termsFile) ) {
			$text = file_get_contents( $this->termsFile );
			$textParts = array();
			$startWithOnly = false;
			if( strlen($searchPhrase) <= 3 ) { // We expect a lot of hits, first try search on start-with.
				$searchPhraseLen = strlen( $eol.$searchPhrase );
				$textParts = explode( $eol.$searchPhrase, $text, $maxResults+1+$numberOfIgnoreValues );
				$startWithOnly = true;
			}
			if( count($textParts) < $maxResults ) { // Not enough hits on start-with? => full search.
				$searchPhraseLen = strlen( $searchPhrase );
				$textParts = explode( $searchPhrase, $text );
				$startWithOnly = false;
			}

			$foundItems = array();
			$hits = array();
			$textOffset = 0;
			$ignoreValues = array_flip( $ignoreValues );
			foreach( $textParts as $partIndex => $textPart ) {
				if( $partIndex > 0 ) { // Skip the first part.

					// Note: strrpos() does not work as expected due to the offset!
					//       This is what we want, but searches forward instead of backward:
					//          $leftPos = strrpos( $text, chr(0x0A), $textOffset );
					//       Therefore we work around with a sandbox below.
					if( $startWithOnly ) {
						$leftPart = $searchPhrase;
						$leftPos = 0;
					} else {
						$leftIndex = 1;
						$leftSandbox = $textParts[$partIndex-$leftIndex].$searchPhrase;
						// Expand the sandbox until it contains a term separator (or start of the file reached).
						while( ( $leftPos = strrpos( $leftSandbox, $eol ) ) === false ) { // No begin of Term found yet?
							$leftIndex += 1;
							if( $leftIndex > $partIndex ) { // paranoid: avoid endless loop at all times
								$leftPos = 0;
								break;
							}
							$leftSandbox = $textParts[$partIndex-$leftIndex].$searchPhrase.$leftSandbox;
						}
						$leftPos = strlen($leftSandbox) - $leftPos - 1;
						$leftPart = substr( $text, $textOffset - $leftPos, $leftPos );
					}

					$rightPos = strpos( $text, $eol, $textOffset );
					$rightPart = substr( $text, $textOffset, $rightPos - $textOffset );

					$foundItem = $leftPart.$rightPart; //." ($leftPos:$rightPos:$textOffset)";
					if( !array_key_exists( $foundItem, $ignoreValues ) && // skip when user has already selected term before
						!array_key_exists( $textOffset - $leftPos, $hits )) { // skip when term was already found (e.g. two hits in same term)
						$hits[$textOffset - $leftPos] = true;
						$foundItems[strlen($leftPart)-$searchPhraseLen+1][] = $foundItem;
					}
				}
				$textOffset += strlen($textPart) + $searchPhraseLen;
			}

			ksort($foundItems); // Sort on the position where the search string is found.
			$searchResult = array();
			$resultCount = 0;

			foreach( $foundItems as $leftPos => $posItems ) {
				sort($posItems);
				foreach( $posItems as $foundItem ) {
					$startPos = mb_strpos( $foundItem, $searchPhrase );

					$score = round(1.0 - ($leftPos/35), 2);
					$tag = new AutoSuggestTag();
					$tag->Value = $foundItem;
					$tag->Score = $score;
					$tag->StartPos = $startPos;
					$tag->Length = mb_strlen( $searchPhrase );
					$searchResult[] = $tag;
					$resultCount += 1;
					if( $resultCount >= $maxResults ) {
						break 2; // Exit both loops.
					}
				}
			}
			$hits = count($textParts)-1;
		}
		return $searchResult;
	}

	/**
	 * Reads a whole Terms file from disk into memory (e.g. to prepare a search).
	 *
	 * @return void.
	 */
	private function readTermsFile()
	{
		$this->terms = array();
		if( $this->termsFile && file_exists($this->termsFile) ) {
			$readText = file_get_contents( $this->termsFile );
			if( $readText ) {
				$readTextArray = explode( chr(0x0A), $readText );
				// Due to the eol at begin/end of the file, we have one empty element at the begin/end of the array
				// that we need to remove.
				if( reset( $readTextArray ) == '' ) {
					array_shift( $readTextArray ); // remove empty term from begin
				}
				if( end( $readTextArray ) == '' ) {
					array_pop( $readTextArray ); // remove empty term from end
				}
				// Only flip after removing the empty terms from the begin/end of the read terms
				// because array_flip only keeps the repeated values as only one key, as a result, we loose one element
				// from the array after flipping. i.e If there are two empty terms(at begin/end of the file), array_flip
				// will result into one empty term which is unwanted (we do want to remove the both begin/end empty term).
				$this->terms = array_flip( $readTextArray ); // After removing the empty term, flip them.

			} else {
				$this->terms = array();
			}
			unset($readText);
		}
	}

	/**
	 * Writes the whole Terms file from memory to disk
	 *
	 * Writes the Terms file to disk (e.g. after a search).
	 *
	 * @return bool Whether or not writing the Terms file to disk was successful.
	 */
	private function writeTermsFile()
	{
		$bytes = 0;
		if( $this->termsFile ) {
			if( $this->terms ) {
				$writeText = chr(0x0A) . implode( chr(0x0A), array_keys( $this->terms ) ).chr(0x0A);
				// Note that we add one extra eol at begin/end of the file to ease the algorithm (in searchTerms())
				// that searches for begin/end markers (eol) before taking out a whole term found in the file.
			} else {
				$writeText = '';
			}
			$bytes = file_put_contents( $this->termsFile, $writeText );
			unset($writeText);
		}
		return $bytes !== false;
	}

}