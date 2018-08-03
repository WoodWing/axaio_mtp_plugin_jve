<?php
/**
 * @since 		v9.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Autocomplete TermEntities and Terms administration.
 */
require_once BASEDIR.'/server/bizclasses/BizAutocompleteBase.class.php';

class BizAdmAutocomplete extends BizAutocompleteBase
{
	/**************************** Autocomplete TermEntity **********************************/
	/**
	 * Creates a list of new TermEntity objects.
	 *
	 * @param string $provider The autocomplete provider that supports the list of TermEntities.
	 * @param AdmTermEntity[] $termEntities List of Autocomplete TermEntity objects to be created.
	 * @throws BizException Throws error when entitiy name to be created already exists.
	 * @return AdmTermEntity[] List of TermEntity objects that have been created.
	 */
	public static function createAutocompleteTermEntities( $provider, $termEntities )
	{
		require_once BASEDIR .'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR .'/server/utils/Autocomplete.class.php';
		$createdEntities = array(); // List of AdmTermEntity objects.
		if( $termEntities ) foreach( $termEntities as $termEntity ) {
			self::enrichNullPublishSystemId( $termEntity->PublishSystemId );
			$checkTermEntity = DBAdmAutocompleteTermEntity::getTermEntity( $termEntity );
			if( is_null( $checkTermEntity )) {
				$id = DBAdmAutocompleteTermEntity::createTermEntity( $termEntity );
				if( $id ) {
					$createdEntity = DBAdmAutocompleteTermEntity::getTermEntityById( $id );
					$createdEntities[] = $createdEntity;
					$autocompleteUtils = new WW_Utils_Autocomplete( $id );
					$bookshelf = self::composeBookshelf( $provider, $termEntity->PublishSystemId );
					$autocompleteUtils->openTermsFile( $bookshelf, $createdEntity->Name ); // Create autocomplete Term Entity.
					$autocompleteUtils->closeTermsFile();
				}
			} else {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Server', 'Unable to create the Autocomplete Entity  "'.$termEntity->Name.'": '.
					'it already exists for Autocomplete Provider "'.$termEntity->AutocompleteProvider.
					'" and publish system ID "'.$termEntity->PublishSystemId.'".' );
			}
		}
		return $createdEntities;
	}

	/**
	 * Get a list of TermEntities given the Autocomplete provider.
	 *
	 * @param string $autocompleteProvider The autocomplete provider's name.
	 * @return AdmTermEntity[] The retrieved AdmTermEntity object(s).
	 */
	public static function getAutocompleteTermEntities( $autocompleteProvider )
	{
		require_once BASEDIR .'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		$termEntities = DBAdmAutocompleteTermEntity::getTermEntityByProvider( $autocompleteProvider );
		return $termEntities;
	}

	/**
	 * Modify a list of TermEntity objects.
	 *
	 * @param string $provider The autocomplete provider that supports the list of TermEntities.
	 * @param AdmTermEntity[] $termEntities List of TermEntity objects to be modified.
	 * @throws BizException Throws an exception when the updated TermEntity already exists in database.
	 * @return AdmTermEntity[] List of successfully modified AdmTermEntity objects.
	 */
	public static function modifyAutocompleteTermEntities( $provider, $termEntities )
	{
		require_once BASEDIR .'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR .'/server/utils/Autocomplete.class.php';
		$modifiedEntities = array(); // List of AdmTermEntity objects.
		if( $termEntities ) foreach( $termEntities as $termEntity ) {
			self::enrichNullPublishSystemId( $termEntity->PublishSystemId );
			$checkTermEntity = DBAdmAutocompleteTermEntity::getTermEntityById( $termEntity->Id );
			if( is_null( $checkTermEntity )) {
				throw new BizException( 'ERR_ERROR', 'Server', 'Unable to modify the Term Entity: '.
					'the record does not exist. (ID='.$termEntity->Id.')' );
			}
			$checkTermEntity = DBAdmAutocompleteTermEntity::getTermEntity( $termEntity );
			if( $checkTermEntity && $checkTermEntity->Id != $termEntity->Id ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Server',
					'Unable to modify the Term Entity name: the name "'.$termEntity->Name.
					'" already exists. (ID='.$termEntity->Id.')' );
			}
			$originalEntity = DBAdmAutocompleteTermEntity::getTermEntityById( $termEntity->Id );
			$result = DBAdmAutocompleteTermEntity::modifyTermEntity( $termEntity );
			if( $result ) {
				$modifiedEntity = DBAdmAutocompleteTermEntity::getTermEntityById( $termEntity->Id );
				$modifiedEntities[] = $modifiedEntity;
				$autocompleteUtils = new WW_Utils_Autocomplete( $termEntity->Id );
				$bookshelf = self::composeBookshelf( $provider, $originalEntity->PublishSystemId );
				$autocompleteUtils->openTermsFile( $bookshelf, $originalEntity->Name ); // Opens the original entity file.
				$autocompleteUtils->modifyTermsFile( $bookshelf, $modifiedEntity->Name ); // Modify the Term Entity.
				$autocompleteUtils->closeTermsFile();
			}
		}
		return $modifiedEntities;
	}

	/**
	 * Deletes a list of TermEntity objects and all belonging Term objects.
	 *
	 * @param string $provider The autocomplete provider that supports the list of TermEntities.
	 * @param AdmTermEntity[] $termEntities List of Autocomplete TermEntities to be deleted.
	 * @throws BizException Throws an Exception when there are errors during the delete operation.
	 */
	public static function deleteAutocompleteTermEntities( $provider, $termEntities )
	{
        require_once BASEDIR .'/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		require_once BASEDIR .'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR .'/server/utils/Autocomplete.class.php';
		if( $termEntities ) foreach( $termEntities as $termEntity ) {
			$toDeleteEntity = DBAdmAutocompleteTermEntity::getTermEntityById( $termEntity->Id );
            /* $retVal = */DBAdmAutocompleteTerm::deleteTermsByTermEntityId( $toDeleteEntity->Id );
			/* $retVal = */DBAdmAutocompleteTermEntity::deleteTermEntityById( $toDeleteEntity->Id );
			$autocompleteUtils = new WW_Utils_Autocomplete( $toDeleteEntity->Id );
			$bookshelf = self::composeBookshelf( $provider, $toDeleteEntity->PublishSystemId );
			$autocompleteUtils->openTermsFile( $bookshelf, $toDeleteEntity->Name );
			$autocompleteUtils->deleteTermsFile();
			$autocompleteUtils->closeTermsFile();
		}
	}

	/**************************** Autocomplete Terms **********************************/

	/**
	 * Creates a list of terms that belong to the TermEntity $termEntity.
	 *
	 * @param string $provider Autocomplete provider that supports the list of terms.
	 * @param AdmTermEntity $termEntity The TermEntity to which the terms belong to.
	 * @param string[] $terms List of Autocomplete terms to be created.
	 * @throws BizException Throws an exception if creating the terms fails.
	 */
	public static function createAutocompleteTerms( $provider, $termEntity, $terms )
	{
		require_once BASEDIR . '/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		$createdTerms = array();
		if( $terms ) foreach( $terms as $term ) {
			$normalizedTerm = self::normalizeTerm( $term );
			$ligatures = self::calculateLigaturesForTerm( $term );
			$admTerm = new AdmTerm();
			$admTerm->EntityId = $termEntity->Id;
			$admTerm->DisplayName = $term;
			$admTerm->NormalizedName = $normalizedTerm;
			$admTerm->Ligatures = implode( '', $ligatures );
			$checkTerm = DBAdmAutocompleteTerm::getTerm( $admTerm );
			if( is_null( $checkTerm ) ) { // Only create when termns do not yet exist.
				DBAdmAutocompleteTerm::createTerm( $admTerm );
				$createdTermObj = DBAdmAutocompleteTerm::getTerm( $admTerm );
				$createdTerms[] = $createdTermObj->NormalizedName;
			}
		}

		if( $createdTerms ) { // Only insert the list of normalized terms when the insertion into DB is successful.
			require_once BASEDIR .'/server/utils/Autocomplete.class.php';
			$autocompleteUtils = new WW_Utils_Autocomplete( $termEntity->Id );
			$bookshelf = self::composeBookshelf( $provider, $termEntity->PublishSystemId );
			$autocompleteUtils->openTermsFile( $bookshelf, $termEntity->Name );
			$autocompleteUtils->addTerms( $createdTerms );
			$autocompleteUtils->closeTermsFile();
		}
	}

	/**
	 * Get a list of Autocomplete terms that belong to the TermEntity $termEntity.
	 *
	 * @param AdmTermEntity $termEntity The TermEntity of the terms to be retrieved.
	 * @param string $typedValue The value the user has typed so far, will be used  to match the terms to be returned.
	 * @param int|null $firstEntry The starting entry of the term in the terms list that should be returned to the caller.
	 * @param int|null $maxEntries The max entries of the terms that should be returned starting from the $firstEntry.
	 * @return string[] List of Autocomplete display name terms.
	 */
	public static function getAutocompleteTerms( AdmTermEntity $termEntity, $typedValue,
	                                             $firstEntry = null, $maxEntries = null )
	{
		if( $termEntity->Id ) {
			require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
			$termEntity = DBAdmAutocompleteTermEntity::getTermEntityById( $termEntity->Id );
		}
		$foundTermTags = self::searchAutocompleteTerms(
			$termEntity->Name, $termEntity->AutocompleteProvider, $termEntity->PublishSystemId,
			$typedValue, array(), $firstEntry, $maxEntries );

		$terms = array();
		if( $foundTermTags ) foreach( $foundTermTags as $foundTermTag ) {
			$terms[] = $foundTermTag->Value;
		}
		return $terms;
	}

	/**
	 * Modify a list of terms given the old and the new terms and which TermEntity the terms to be modified belong to.
	 *
	 * The function first deletes all the old terms from database and the Terms file, then it adds the new terms to the
	 * database and adds them to the Terms file.
	 *
	 * @param string $provider The autocomplete provider that supports the list of terms.
	 * @param AdmTermEntity $termEntity The TermEntity for which to modify terms.
	 * @param string[] $oldTerms List of original (old) terms to be modified.
	 * @param string[] $newTerms List of new terms that will replace the list of $oldTerms.
	 * @throws BizException Throws an exception when the update operation fails.
	 */
	public static function modifyAutocompleteTerms( $provider, $termEntity, $oldTerms, $newTerms )
	{
		if( count( $oldTerms ) != count( $newTerms )) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Unable to update the Term Entities: the number of old '.
				'and new Term Entities do not match.' );
		}

		require_once BASEDIR . '/server/dbclasses/DBAdmAutocompleteTerm.class.php';

		self::enrichNullPublishSystemId( $termEntity->PublishSystemId );
		// Managing the old terms.
		$deleteTermsInTermsFile = array();
		if( $oldTerms ) foreach( $oldTerms as $index => $oldTerm ) {
			$newTerm = $newTerms[$index];
			if( $newTerm == $oldTerm ) { // No changes.
				unset( $oldTerms[$index] );
				unset( $newTerms[$index] );
				continue; // No changes, so no update needed, skip the code below.
			}
			$newNormalizedTerm = self::normalizeTerm( $newTerm );
			$newAdmTerm = new AdmTerm();
			$newAdmTerm->EntityId = $termEntity->Id;
			$newAdmTerm->DisplayName = $newTerm;
			$newAdmTerm->NormalizedName = $newNormalizedTerm;
			$checkTerm = DBAdmAutocompleteTerm::getTerm( $newAdmTerm );
			if( !is_null( $checkTerm ) ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Server', 'Unable to modify the Term Entity: the name "'.
					$newTerm.'" already exists.' );
			}

			$normalizedOldTerm = self::normalizeTerm( $oldTerm );
			$admOldTerm = new AdmTerm();
			$admOldTerm->EntityId = $termEntity->Id;
			$admOldTerm->DisplayName = $oldTerm;
			$admOldTerm->NormalizedName = $normalizedOldTerm;
			/* $retVal = */DBAdmAutocompleteTerm::deleteTerm( $admOldTerm );
			$normalizedTermsInDb = DBAdmAutocompleteTerm::getTermsByNormalizedName( $termEntity->Id, $normalizedOldTerm );
			// Only remove from Terms file file when no other term is using $normalizedOldTerm.
			if( count( $normalizedTermsInDb ) == 0 ) {
				$deleteTermsInTermsFile[] = $normalizedOldTerm;
			}
		}

		// If terms have been deleted from the DB, delete from the file too (provided the same normalizedTerm is not
		// being used by others).
		if( $deleteTermsInTermsFile ) {
			// Remove from the Terms file.
			require_once BASEDIR .'/server/utils/Autocomplete.class.php';
			$autocompleteUtils = new WW_Utils_Autocomplete( $termEntity->Id );
			$bookshelf = self::composeBookshelf( $provider, $termEntity->PublishSystemId );
			$autocompleteUtils->openTermsFile( $bookshelf, $termEntity->Name );
			$autocompleteUtils->removeTerms( $deleteTermsInTermsFile );
			$autocompleteUtils->closeTermsFile();

		}

		// Managing the new terms.
		self::createAutocompleteTerms( $provider, $termEntity, $newTerms );

	}

	/**
	 * Deletes a list of Terms which belong to the TermEntity $termEntity.
	 *
	 * @param string $provider The Autocomplete provider that supports the list of terms.
	 * @param AdmTermEntity $termEntity TermEntity to which the terms belong to.
	 * @param string[] $terms List of Autocomplete terms to be deleted.
	 */
	public static function deleteAutocompleteTerms( $provider, $termEntity, $terms )
	{
		self::enrichNullPublishSystemId( $termEntity->PublishSystemId );
		require_once BASEDIR . '/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		$toDeleteTerms = array();
		if( $terms ) foreach( $terms as $term ) {
			$normalizedTerm = self::normalizeTerm( $term );
			$admTerm = new AdmTerm();
			$admTerm->EntityId = $termEntity->Id;
			$admTerm->DisplayName = $term;
			$admTerm->NormalizedName = $normalizedTerm;
			/*$retVal = */DBAdmAutocompleteTerm::deleteTerm( $admTerm );
			$termsInDB = DBAdmAutocompleteTerm::getTermsByNormalizedName( $termEntity->Id, $normalizedTerm );
			if( count( $termsInDB ) == 0 ) { // Only remove from Terms file when no other term is using $normalizedTerm.
				$toDeleteTerms[] = $normalizedTerm;
			}
		}

		if( $toDeleteTerms ) {
			require_once BASEDIR .'/server/utils/Autocomplete.class.php';
			$autocompleteUtils = new WW_Utils_Autocomplete( $termEntity->Id );
			$bookshelf = self::composeBookshelf( $provider, $termEntity->PublishSystemId );
			$autocompleteUtils->openTermsFile( $bookshelf, $termEntity->Name );
			$autocompleteUtils->removeTerms( $toDeleteTerms );
			$autocompleteUtils->closeTermsFile();
		}
	}
}
