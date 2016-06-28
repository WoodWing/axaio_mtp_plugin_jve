<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Conjunction of Autocomplete and Suggestion service business logic.
 */
require_once BASEDIR.'/server/bizclasses/BizAutocompleteBase.class.php';

class BizAutoSuggest extends BizAutocompleteBase
{
	/**
	 * Requests the first capable Autocomplete plugin to provide suggestions.
	 *
	 * Searches for the first best Autocomplete plug-in connector that can
	 * handle a term entity (defined for a given property) that the user is currently editing.
	 * Once a term entity is found, that connector is requested to provide autocomplete information
	 * to help the user fill in the property value.
	 *
	 * @param string $provider The Name of the Plugin that should handle the autocomplete suggestions.
	 * @param string $publishSystemId Unique id of the publishing system. Use to bind the channel to the publishing storage.
	 * @param string $objectId The ID of the Object for which to get autocomplete suggestions.
	 * @param AutoSuggestProperty $autoSuggestProperty The AutoSuggestProperty for which to get Entities.
	 * @param string $typedValue The currently typed in value for which to get autocomplete suggestions.
	 * @return AutoSuggestTag[] Returns a list of autocomplete suggestions for the requested TermEntity.
	 */
	public static function autocomplete( $provider, $publishSystemId, $objectId, $autoSuggestProperty, $typedValue )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAutocompleteDispatcher.class.php';

		$foundConnector = BizAutocompleteDispatcher::findAutocompleteProviderConnector( $provider,
									$autoSuggestProperty->Entity, false );

		if( $foundConnector ) {
			$connRetVals = BizServerPlugin::runConnector( $foundConnector, 'autocomplete',
				array( $provider, $objectId, $autoSuggestProperty->Name, $autoSuggestProperty->Entity, $publishSystemId,
					$autoSuggestProperty->IgnoreValues, $typedValue ) );
		} else {
			$connRetVals = array();
		}
		return $connRetVals;
	}

	/**
	 * Resolves the name of the Autocomplete provider (server plug-in name) that can handle a given TermEntity.
	 *
	 * The core server will enrich the PropertyUsage elements for the workflow dialog
	 * widgets with this by setting its AutocompleteProvider attribute. Then clients can round-trip that
	 * provider name ($autocompleteProvider) for fast lookup in {@link:autocomplete()}.
	 * When $preferredProvider is given, this function will checks if the preferred provider can handle the term entity,
	 * if can, the function returns the preferred provider as the autocomplete provider, else
	 * the function will return the first best found autocomplete provider that can handles the term entity.
	 *
	 * @param string $objectId The ID of the Object for which to get an autocomplete provider.
	 * @param string $propertyName The PropertyName for which to resolve the provider.
	 * @param string $termEntity The TermEntity for which to resolve the provider.
	 * @param string $preferredProvider The preferred Autocomplete provider.
	 * @return null|string Name of provider. NULL when none are found.
	 */
	public static function getAutocompleteProviderForTermEntity(
		/** @noinspection PhpUnusedParameterInspection */  $objectId,
		/** @noinspection PhpUnusedParameterInspection */ $propertyName,
		                                                   $termEntity, $preferredProvider )
	{
		$provider = null;

		// If the caller prefers a provider (typically a autocomplete connector shipped within a publish plugin)
		// we first check if that provider can handle the entity. In that case, we return that provider.
		if( $preferredProvider ) {
			require_once BASEDIR.'/server/bizclasses/BizAutocompleteDispatcher.class.php';
			if( BizAutocompleteDispatcher::findAutocompleteProviderConnector( $preferredProvider, $termEntity, true )) {
				$provider = $preferredProvider;
			}
		}

		// If no preferred provider given or preferred provider cannot handle the term entity, then we
		// search for the best first found provider that can handle the term entity.
		if( !$provider ) {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			static $connectors;
			$connector = null;
			if( !isset( $connectors )) {
				// TODO: The solution below is a temporary hack.
				// Currently the server doesn't find the best connector but just hard-coded to always use
				// 'AutocompleteSample' Autocomplete connector plugin .
				$connectorClass = 'AutocompleteSample_AutocompleteProvider';
				$connector = BizServerPlugin::searchConnectorByClassName( $connectorClass );
			}
			if( $connector ) {
				$canHandle = BizServerPlugin::runConnector( $connector, 'canHandleEntity',
					array( $termEntity ) );
				if( $canHandle ) {
					/** @noinspection PhpUndefinedVariableInspection */
					// Can suppress this checking, may assume that $connectorClass is defined at this stage.
					$provider = BizServerPlugin::getPluginUniqueNameForConnector( $connectorClass );
				}
			}
//			foreach( $connectors as $connectorClass => $connector ) {
//				$canHandle = BizServerPlugin::runConnector( $connector, 'canHandleEntity',
//					array( $termEntity ) );
//				if( $canHandle ) {
//					$provider = BizServerPlugin::getPluginUniqueNameForConnector( $connectorClass );
//					break; // Found.
//				}
//			}
		}
		return $provider;
	}

	/**
	 * Gets the Suggestions plug-in connector to return the tags per Suggestion Entity requested.
	 *
	 * The connector only returns the tags for Suggestion Entity it supports.
	 *
	 * @param string $suggestionProvider The suggestion provider to query for Suggestion Entity objects.
	 * @param string $objectId The ObjectId for which to retrieve Suggestion Entity objects.
	 * @param MetaDataValue[] $metadataValues A list of text metadata values for which to retrieve suggestions.
	 * @param AutoSuggestProperty[] $suggestForProperties The autoSuggestProperties for which to resolve suggestions.
	 * @return array The used connectors' return values.
	 */
	public static function suggestions( $suggestionProvider, $objectId, $metadataValues, $suggestForProperties )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$foundConnector = BizServerPlugin::searchConnectorByClassName( $suggestionProvider.'_SuggestionProvider' );
		if( $foundConnector ) {
			$connRetVals = BizServerPlugin::runConnector( $foundConnector, 'suggest',
				array( $objectId, $metadataValues, $suggestForProperties ) );

		} else {
			$connRetVals = array();
		}
		return $connRetVals;
	}

	/**
	 * Checks if the Provider by the given name will be able to handle the Suggestion Entity for Suggestions.
	 *
	 * @param string $suggestionProvider The Name of the SuggestionProvider to check if it can handle the Suggestion Entity.
	 * @param string $suggestionEntity The Suggestion Entity for which to check if the suggestionProvider can handle it.
	 * @return bool Whether or not the $suggestionProvider can handle the $suggestionEntity.
	 */
	public static function canSuggestionProviderHandleEntity( $suggestionProvider, $suggestionEntity )
	{
		$canHandleSuggestionEntity = false;
		static $cachedSuggestionConnectors = array();
		if( isset( $cachedSuggestionConnectors[$suggestionProvider][$suggestionEntity] )){
			$foundConnector = $cachedSuggestionConnectors[$suggestionProvider][$suggestionEntity];
		} else {
			$foundConnector = BizServerPlugin::searchConnectorByClassName( $suggestionProvider.'_SuggestionProvider' );
			$cachedSuggestionConnectors[$suggestionProvider][$suggestionEntity] = $foundConnector;
		}
		if( $foundConnector ) {
			$success = BizServerPlugin::runConnector( $foundConnector, 'canHandleEntity', array( $suggestionEntity ) );
			if ($success) {
				$canHandleSuggestionEntity = true;
			}
		}
		return $canHandleSuggestionEntity;
	}

	/**
	 * Resolves the name of the Suggestion provider (server plug-in name) that can handle a given suggestion entity.
	 *
	 * The core server will enrich the PropertyUsage elements for workflow dialog
	 * widgets with this by setting its SuggestionProvider attribute. Then clients can round-trip that
	 * provider name ($suggestionProvider) for fast lookups in {@link:suggest()}.
	 *
	 * @param string $objectId The ID of the Object for which to get an autocomplete provider.
	 * @param string $propertyName The PropertyName for which to resolve the provider.
	 * @param string $suggestionEntity The SuggestionEntity for which to resolve the suggestions.
	 * @return null|string Name of provider. NULL when none were found.
	 */
	public static function getSuggestionProviderForEntity(
		/** @noinspection PhpUnusedParameterInspection */ $objectId,
		/** @noinspection PhpUnusedParameterInspection */ $propertyName,
		                                                  $suggestionEntity )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		static $connectors;
		if( !isset( $connectors )) {
			$connectors = BizServerPlugin::searchConnectors( 'SuggestionProvider', null );
		}

		$provider = null;
		foreach( $connectors as $connectorClass => $connector ) {
			$canHandle = BizServerPlugin::runConnector( $connector, 'canHandleEntity',
				array( $suggestionEntity ) );
			if( $canHandle ) {
				$provider = BizServerPlugin::getPluginUniqueNameForConnector( $connectorClass );
				break; // Found.
			}
		}
		return $provider;
	}

	/**
	 * Consolidates the metadatavalues input into a single value.
	 *
	 * Concatenates all the input fields into a single string value that should be analyzed by
	 * a Suggestions service.
	 *
	 * @param MetaDataValue[] $metadataValues The MetaData values sent with the request.
	 * @return string The consolidated content of the metadata values.
	 */
	public static function consolidateMetaDataValues( $metadataValues )
	{
		$content = '';

		if ($metadataValues) foreach ( $metadataValues as $metadata ) {
			// $metadata should already be a valid MetaDataValue, validated by the service, therefore just loop through
			// the values and gather the content.
			if ($metadata->Values) foreach ($metadata->Values as $value) {
				if (!is_null($value)) {
					$content .= $value . "\r\n";
				}
			}
		}

		return $content;
	}

	/**
	 * Consolidates the requested AutoSuggestProperties into an array of ignore- and requested values.
	 *
	 * Returns an array as follows:
	 *
	 * $requestedTerms['ignored'] => string[] // The Entities to be ignored from the suggestions service.
	 * $requestedTerms['suggestionentities'] => string[] // The Entities that are wanted from the suggestions service.
	 *
	 * @param AutoSuggestProperty[] $suggestedForProps Properties for which to retrieve/ignore Entities
	 * @return array An array with Suggestion Entities to be ignored / retrieved.
	 */
	public static function consolidateRequestedSuggestionEntities( $suggestedForProps )
	{
		$requestedTerms = array();
		$requestedTerms['ignored'] = array();
		$requestedTerms['suggestionentities'] = array();

		if ( $suggestedForProps ) foreach ( $suggestedForProps as $autoSuggestProperty ) {
			// Consolidate the ignored terms.
			if ($autoSuggestProperty->IgnoreValues) foreach ($autoSuggestProperty->IgnoreValues as $ignoreValue ) {
				$requestedTerms['ignored'][$ignoreValue] = strtolower($ignoreValue);
			}

			// Consolidate the requested termentities.
			$requestedTerms['suggestionentities'][$autoSuggestProperty->Entity] = strtolower($autoSuggestProperty->Entity);
		}

		return $requestedTerms;
	}
}
