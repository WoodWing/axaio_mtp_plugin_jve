<?php
/**
 * @since 		v9.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Autocomplete provider connector interface to help end-user fill in dialog property values.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateAutocompleteTermsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAutocompleteTermsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAutocompleteTermsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAutocompleteTermsRequest.class.php';

abstract class AutocompleteProvider_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Returns the complete list of supported term entities regardless of the context used.
	 *
	 * This function is called by the core when Admin user is configuring properties for
	 * workflow dialogs whereby he/she has to choose the entity attribute for a property.
	 * Thereby the full list of entities that are supported by the system needs to be shown.
	 *
	 * @return string[] An array of supported entities.
	 */
	abstract public function getSupportedEntities();

	/**
	 * Whether or not this provider can handle the given term entity.
	 *
	 * This function is called by the core while composing a workflow dialog (GetDialog2 service).
	 * When TRUE is returned, the provider will be requested later again (through the {@link: autocomplete()} function)
	 * to help the end-user with filling in a property for which the term entity is defined.
	 *
	 * @param string $termEntity The requested entity, whether or not supported by provider.
	 * @return bool Whether or not the Entity is supported.
	 */
	abstract public function canHandleEntity( $termEntity );

	/**
	 * Provides autocomplete suggestions.
	 *
	 * While the end-user is filling in a property value, this function is called. The Autocomplete provider
	 * should come up with suggestions to complete the property value. Note that this service
	 * must be lightning fast (max 50ms).
	 *
	 * @param string $provider The Name of the Plugin that should handle the autocomplete suggestions.
	 * @param string $objectId ID of the Object for which to provide autocomplete suggestions.
	 * @param string $propertyName Name of the Property for which autocomplete is requested.
	 * @param string $termEntityName The Name of the TermEntity for which autocomplete suggestions are requested.
	 * @param string $publishSystemId Unique id of the publishing system. Use to bind the channel to the publishing storage.
	 * @param string[] $ignoreValues An array of string values to be ignored when requesting autocomplete suggestions.
	 * @param string $typedValue The typed in value for which to retrieve autocomplete suggestions.
	 * @return AutoSuggestTag[] An array of AutoSugestTag objects, which represent autocomplete suggestions.
	 */
	public function autocomplete( $provider, $objectId, $propertyName, $termEntityName, $publishSystemId, $ignoreValues, $typedValue )
	{
		$entities = $this->getSupportedEntities();
		if( in_array( $termEntityName, $entities ) ) {
			require_once BASEDIR . '/server/bizclasses/BizAutoSuggest.class.php';
			$tags = BizAutoSuggest::searchAutocompleteTerms(
				$termEntityName, $provider, $publishSystemId,
				$typedValue, $ignoreValues );
		} else {
			$tags = array();
		}
		return $tags;

	}

	/**
	 * Whether or not Term Entities can be maintained by (admin) user.
	 *
	 * Before the user creates/modifies/deletes Term Entities, this function is called. By default, false is returned.
	 * If the connector allows editing the Term Entities, it should overrule this function and returns true instead.
	 *
	 * @return bool
	 */
	public function areTermEntitiesEditable()
	{
		return false;
	}

	/**
	 * Whether or not Terms can be maintained by (admin) user.
	 *
	 * Before the user creates/modifies/deletes Terms, this function is called. By default, false is returned.
	 * If the connector allows editing the Terms, it should overrule this function and returns true instead.
	 * Only then {@link:createAutocompleteTerms()}, {@link:modifyAutocompleteTerms()} and
	 * {@link:deleteAutocompleteTerms()} are called respectively.
	 *
	 * @return bool
	 */
	public function areTermsEditable()
	{
		return false;
	}

	/**
	 * Creates / inserts new list of terms that belong to a TermEntity into database.
	 *
	 * @param AdmCreateAutocompleteTermsRequest $request
	 * @return null
	 */
	public function createAutocompleteTerms( AdmCreateAutocompleteTermsRequest $request )
	{
		return null;
	}

	/**
	 * Modifies / updates a list of Autocomplete TermEntity terms in the database.
	 *
	 * @param AdmModifyAutocompleteTermsRequest $request
	 * @return null
	 */
	public function modifyAutocompleteTerms( AdmModifyAutocompleteTermsRequest $request )
	{
		return null;
	}

	/**
	 * Gets a list of Terms given the term entity.
	 *
	 * @param AdmGetAutocompleteTermsRequest $request
	 * @return null
	 */
	public function getAutocompleteTerms( AdmGetAutocompleteTermsRequest $request )
	{
		return null;
	}

	/**
	 * Deletes a list of Autocomplete TermEntity terms in the database.
	 *
	 * @param AdmDeleteAutocompleteTermsRequest $request
	 * @return null
	 */
	public function deleteAutocompleteTerms( AdmDeleteAutocompleteTermsRequest $request )
	{
		return null;
	}

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); }
	final public function getInterfaceVersion() { return 1; }

}