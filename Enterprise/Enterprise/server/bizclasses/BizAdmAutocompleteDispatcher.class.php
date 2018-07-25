<?php
/**
 * @since 		v9.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 */

require_once BASEDIR.'/server/bizclasses/BizAutocompleteDispatcher.class.php';
class BizAdmAutocompleteDispatcher extends BizAutocompleteDispatcher
{
	/**************************** Autocomplete TermEntity **********************************/
	/**
	 * Validates the TermEntities and stores them into the database.
	 *
	 * @param AdmCreateAutocompleteTermEntitiesRequest $request The request to be handled.
	 * @return AdmCreateAutocompleteTermEntitiesResponse The composed response.
	 */
	public static function createAutocompleteTermEntities( AdmCreateAutocompleteTermEntitiesRequest $request )
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		$provider = self::getAutocompleteProviderAndValidateEntities( $request->TermEntities, 'createTermEntity' );
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
		$termEntities = BizAdmAutocomplete::createAutocompleteTermEntities( $provider, $request->TermEntities );
		$response = new AdmCreateAutocompleteTermEntitiesResponse();
		$response->TermEntities = $termEntities;
		return $response;
	}

	/**
	 * Validates the TermEntities and modifies the existing TermEntity with new values.
	 *
	 * @param AdmModifyAutocompleteTermEntitiesRequest $request The request.
	 * @return AdmModifyAutocompleteTermEntitiesResponse The modified AdmTermEntities.
	 */
	public static function modifyAutocompleteTermEntities( AdmModifyAutocompleteTermEntitiesRequest $request )
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		$provider = self::getAutocompleteProviderAndValidateEntities( $request->TermEntities, 'modifyTermEntity' );
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
		$termEntities = BizAdmAutocomplete::modifyAutocompleteTermEntities( $provider, $request->TermEntities );
		$response = new AdmModifyAutocompleteTermEntitiesResponse();
		$response->TermEntities = $termEntities;
		return $response;
	}

	/**
	 * Gets list of TermEntity given the autocomplete provider.
	 *
	 * @param AdmGetAutocompleteTermEntitiesRequest $request The request
	 * @return AdmGetAutocompleteTermEntitiesResponse The retrieved AdmTermEntities.
	 */
	public static function getAutocompleteTermEntities( AdmGetAutocompleteTermEntitiesRequest $request )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
		$termEntities = BizAdmAutocomplete::getAutocompleteTermEntities( $request->AutocompleteProvider );
		$response = new AdmGetAutocompleteTermEntitiesResponse();
		$response->TermEntities = $termEntities;
		return $response;
	}

	/**
	 * Deletes list of TermEntity.
	 *
	 * @param AdmDeleteAutocompleteTermEntitiesRequest $request The delete request.
	 * @return AdmDeleteAutocompleteTermEntitiesResponse The response.
	 */
	public static function deleteAutocompleteTermEntities( AdmDeleteAutocompleteTermEntitiesRequest $request )
	{
		$provider = self::getAutocompleteProviderAndValidateEntities( $request->TermEntities, 'deleteTermEntity' );
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
		BizAdmAutocomplete::deleteAutocompleteTermEntities( $provider, $request->TermEntities );
		$response = new AdmDeleteAutocompleteTermEntitiesResponse();
		return $response;
	}

	/**************************** Autocomplete Terms **********************************/
	/**
	 * Store a list of Terms that belong to a TermEntity into the database.
	 *
	 * @param AdmCreateAutocompleteTermsRequest $request The create request.
	 * @return AdmCreateAutocompleteTermsResponse The response.
	 */
	public static function createAutocompleteTerms( AdmCreateAutocompleteTermsRequest $request )
	{
		$response = null;
		$termEntity = $request->TermEntity;
		$provider = self::getAutocompleteProviderAndValidateEntities( array( $termEntity ), 'createTerm' );
		if( $provider ) {
			$connector = parent::findAutocompleteProviderConnector( $provider, $termEntity, false );
			if( $connector ) {
				$canEditTerms = BizServerPlugin::runConnector( $connector, 'areTermsEditable', array() );
				if( $canEditTerms ) {
					$response = BizServerPlugin::runConnector( $connector, 'createAutocompleteTerms', array( $request ) );
				}
			}
		}
		if( is_null($response) ) { // Happens when there's no provider or the provider requests the core server to handle the Terms
			require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
			BizAdmAutocomplete::createAutocompleteTerms( $provider, $termEntity, $request->Terms );
		}

		return new AdmCreateAutocompleteTermsResponse();

	}

	/**
	 * Get a list of Autocomplete terms for a specific term entity.
	 *
	 * @param AdmGetAutocompleteTermsRequest $request The request.
	 * @return AdmGetAutocompleteTermsResponse The response.
	 */
	public static function getAutocompleteTerms( AdmGetAutocompleteTermsRequest $request )
	{
		$response = null;
		$termEntity = $request->TermEntity;
		$provider = self::getAutocompleteProviderAndValidateEntities( array( $termEntity ), 'getTerm' );
		if( $provider ) {
			$connector = parent::findAutocompleteProviderConnector( $provider, $termEntity, false );
			if( $connector ) {
				$response = BizServerPlugin::runConnector( $connector, 'getAutocompleteTerms', array( $request ) );
			}
		}
		if( is_null($response) ) { // Happens when there's no provider or the provider requests the core server to handle the Terms
			require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
			$firstEntry = $request->FirstEntry;
			$terms = BizAdmAutocomplete::getAutocompleteTerms( $termEntity, $request->TypedValue, $firstEntry, $request->MaxEntries );
			$response = new AdmGetAutocompleteTermsResponse();
			$response->Terms                = $terms;
			$response->FirstEntry           = $firstEntry;
			$response->ListedEntries        = count( $terms ); // TODO: To fill in the correct value once paging is supported.
			$response->TotalEntries         = count( $terms ); // TODO: To fill in the correct value once paging is supported.
		}

		return $response;
	}

	/**
	 * Updates a list of old terms that belong to one TermEntity with the new ones.
	 *
	 * @param AdmModifyAutocompleteTermsRequest $request The modify request
	 * @return AdmModifyAutocompleteTermsResponse The response.
	 */
	public static function modifyAutocompleteTerms( AdmModifyAutocompleteTermsRequest $request )
	{
		$response = null;
		$termEntity = $request->TermEntity;
		$provider = self::getAutocompleteProviderAndValidateEntities( array( $termEntity ), 'modifyTerm' );
		if( $provider ) {
			$connector = parent::findAutocompleteProviderConnector( $provider, $termEntity, false );
			if( $connector ) {
				$canEditTerms = BizServerPlugin::runConnector( $connector, 'areTermsEditable', array() );
				if( $canEditTerms ) {
					$response = BizServerPlugin::runConnector( $connector, 'modifyAutocompleteTerms', array( $request ) );
				}
			}
		}
		if( is_null($response) ) { // Happens when there's no provider or the provider requests the core server to handle the Terms
			require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
			BizAdmAutocomplete::modifyAutocompleteTerms( $provider, $termEntity, $request->OldTerms, $request->NewTerms );
		}

		return new AdmModifyAutocompleteTermsResponse();
	}

	/**
	 * Deletes a list of Autocomplete terms for a specific term entity.
	 *
	 * @param AdmDeleteAutocompleteTermsRequest $request The delete request.
	 * @return AdmDeleteAutocompleteTermsResponse The response.
	 */
	public static function deleteAutocompleteTerms( AdmDeleteAutocompleteTermsRequest $request )
	{
		$response = null;
		$termEntity = $request->TermEntity;
		$provider = self::getAutocompleteProviderAndValidateEntities( array( $termEntity ), 'deleteTerm' );
		$termEntity = $request->TermEntity;
		if( $provider ) {
			$connector = parent::findAutocompleteProviderConnector( $provider, $termEntity, false );
			if( $connector ) {
				$canEditTerms = BizServerPlugin::runConnector( $connector, 'areTermsEditable', array() );
				if( $canEditTerms ) {
					$response = BizServerPlugin::runConnector( $connector, 'deleteAutocompleteTerms', array( $request ) );
				}
			}
		}
		if( is_null($response) ) { // Happens when there's no provider or the provider requests the core server to handle the Terms
			require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
			BizAdmAutocomplete::deleteAutocompleteTerms( $provider, $termEntity, $request->Terms );
		}
		return new AdmDeleteAutocompleteTermsResponse();
	}

	/**
	 * Validates if $termEntities are set.
	 *
	 * Validates if the given $termEntities is set, and has at least one TermEntity and if all entities have
	 * the same AutocompleteProvider value. A mixture of providers is not supported / allowed.
	 * In general, the AdmTermEntity->Id must be set -OR- the Name + AutocompleteProvider must be set.
	 * When the AutocompleteProvider is empty, it means that the core server itself should act as the provider.
	 * For create operations, the Id must be null.
	 *
	 * @param AdmTermEntity[] $termEntities The TermEntities to be validated.
	 * @param string $context 'createTermEntity', 'modifyTermEntity', 'deleteTermEntity', 'createTerm', 'modifyTerm', 'getTerm', 'deleteTerm'.
	 * @throws BizException Throws an exception when any of the criteria's are not met.
	 * @return string The resolved Autocomplete provider's name.
	 */
	private static function getAutocompleteProviderAndValidateEntities( $termEntities, $context )
	{
		$autocompleteProvider = null;
		if( $termEntities )  {
			// Take first provider as the bench mark to check if the rest are all the same.
			$provider = $termEntities[0]->AutocompleteProvider;
			foreach( $termEntities as $termEntity ) {
				switch( $context ) {
					case 'createTermEntity':
						if( $termEntity->Id ) {
							throw new BizException( 'ERR_ARGUMENT', 'Client', 'A Term Entity ID was received while no '.
								'Term Entity ID should be set yet.' );
						}
						// Mandatory: AutocompleteProvider, Name.
						if( !$termEntity->AutocompleteProvider || !$termEntity->Name ){
							throw new BizException( 'ERR_ARGUMENT', 'Client', 'The Term Entity \'AutocompleteProvider\' '.
								'or the Term Entity \'Name\' was not received while both should be sent.' );
						}
						break;
					case 'modifyTermEntity':
						// Mandatory: Id, Name.
						if( !$termEntity->Id || !$termEntity->Name ){
							throw new BizException( 'ERR_ARGUMENT', 'Client', 'The Term Entity \'ID\' or the '.
								'Term Entity \'Name\' was not received while both should be sent.' );
						}
						break;
					case 'deleteTermEntity':
					case 'createTerm':
					case 'modifyTerm':
					case 'getTerm':
					case 'deleteTerm':
						// Mandatory: Id
						if( !$termEntity->Id ) {
							throw new BizException( 'ERR_ARGUMENT', 'Client', 'The Term Entity ID is not set.' );
						}
						break;
				}

				if( $termEntity->AutocompleteProvider ) {
					$autocompleteProvider = $termEntity->AutocompleteProvider;
					if( $provider != $autocompleteProvider ) {
						throw new BizException( 'ERR_ARGUMENT', 'Client', 'Different Autocomplete Providers have been '.
							'found in the list of Term Entities. The use of only one provider is supported.' );
					}
				}
			}

			if( is_null( $provider )) {
				$termEntityId = $termEntities[0]->Id;
				if( $termEntityId ) {
					require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
					$termEntityDb = DBAdmAutocompleteTermEntity::getTermEntityById( $termEntityId );
					$autocompleteProvider = $termEntityDb->AutocompleteProvider;
					foreach( $termEntities as $termEntity ) {
						switch( $context ) {
							case 'modifyTermEntity':
								// Resolve: AutocompleteProvider.
								$termEntity->AutocompleteProvider = $autocompleteProvider;
								break;
							case 'createTerm':
							case 'modifyTerm':
							case 'getTerm':
							case 'deleteTerm':
								// Resolve: Name + AutocompleteProvider.
								$termEntity->AutocompleteProvider = $autocompleteProvider;
								$termEntity->Name = $termEntityDb->Name;
								break;
						}
					}
				}
			}
		} else {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No Term Entities were provided.' );
		}
		return $autocompleteProvider;
	}
}