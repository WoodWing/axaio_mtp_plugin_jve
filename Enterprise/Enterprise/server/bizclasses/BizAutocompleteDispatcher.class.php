<?php
/**
 * @since 		v9.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 *
 */

class BizAutocompleteDispatcher
{
	/**
	 * Searches for the Autocomplete provider connector.
	 *
	 * @param string $autocompleteProvider The autocomplete provider for which to retrieve the connector.
	 * @param string|null $termEntity Checks if the connector can handle this term entity. Only needed when $checkCanHandle is set to true.
	 * @param bool $checkCanHandle Whether or not to let this function check if the connector can handle the term entity. Default is false.
	 * @return EnterpriseConnector|null The Connector, if it can handle the entity, null otherwise.
	 */
	public static function findAutocompleteProviderConnector( $autocompleteProvider, $termEntity=null, $checkCanHandle=false )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$foundConnector = null;
		if( $autocompleteProvider ) {
			$foundConnector = BizServerPlugin::searchConnectorByClassName( $autocompleteProvider.'_AutocompleteProvider' );
		}
		if( $foundConnector && $checkCanHandle ) {
			if( !BizServerPlugin::runConnector( $foundConnector, 'canHandleEntity', array( $termEntity ) ) ) {
				$foundConnector = null;
			}
		}
		return $foundConnector;
	}

	/**
	 * Creates new Terms introduced in a PublishForm.
	 *
	 * When the PublishForm is published or updated, new Terms might be introduced on the PublishForm.
	 * To ensure that the Terms are in sync between the publish system and Enterprise, new Terms that are
	 * introduced will be created in Enterprise database.
	 *
	 * @param array $termEntityProperties List of properties that has Term Entity and its Terms defined.
	 */
	public static function createNewTermsForPublishForm( $termEntityProperties )
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmAutocomplete.class.php';
		require_once BASEDIR . '/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';

		if( $termEntityProperties ) foreach( $termEntityProperties as $termEntityProperty ) {
			$terms = $termEntityProperty['termentity_terms'];
			if( $terms ) {
				$termsToBeAdded = array();
				foreach( $terms as $term ) {
					if( $term ) { // Skip empty term.
						$termsToBeAdded[] = $term;
					}
				}
				if( $termsToBeAdded ) {
					$provider = $termEntityProperty['prop_info']->AutocompleteProvider;
					$admTermEntity = new AdmTermEntity();
					$admTermEntity->Name = $termEntityProperty['prop_info']->TermEntity;
					$admTermEntity->AutocompleteProvider = $termEntityProperty['prop_info']->AutocompleteProvider;
					$admTermEntity->PublishSystemId = $termEntityProperty['prop_info']->PublishSystemId;
					$admTermEntity = DBAdmAutocompleteTermEntity::getTermEntity( $admTermEntity );
					BizAdmAutocomplete::createAutocompleteTerms( $provider, $admTermEntity, $termsToBeAdded );
				}
			}
		}
	}
}