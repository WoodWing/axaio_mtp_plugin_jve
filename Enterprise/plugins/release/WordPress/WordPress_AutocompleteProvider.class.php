<?php
/**
 * @since      v9.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/AutocompleteProvider_EnterpriseConnector.class.php';

class WordPress_AutocompleteProvider extends AutocompleteProvider_EnterpriseConnector
{
	/**
	 * Refer to AutocompleteProvider_EnterpriseConnector::getSupportedEntities() header for more information.
	 *
	 * @return string[] List of supported Term Entities.
	 */
	public function getSupportedEntities()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
		require_once dirname(__FILE__).'/WordPress_Utils.class.php';
		$provider = WordPress_Utils::WORDPRESS_PLUGIN_NAME;
		$termEntitiesObj = BizAdmAutocomplete::getAutocompleteTermEntities( $provider );
		static $cachedSupportedEntities;
		if( !isset( $cachedSupportedEntities[$provider] ) ) {
			$supportedEntities = array();
			if( $termEntitiesObj ) foreach( $termEntitiesObj as $termEntityObj ) {
				$supportedEntities[] = $termEntityObj->Name;
			}
			$cachedSupportedEntities[$provider] = $supportedEntities;
		}

		return $cachedSupportedEntities[$provider];
	}

	/**
	 * Whether or not this provider can handle the given term entity.
	 *
	 * This function is called by the core while composing a workflow dialog (GetDialog2 service).
	 * When TRUE is returned, the provider will be requested later again (through the {@link: autocomplete()} function)
	 * to help end-users filling in a property for which the term entity is defined.
	 *
	 * @param string $termEntity The TermEntity name for which to determine if it can be handled by this plugin.
	 * @return bool Whether or not the TermEntity can be handled.
	 */
	public function canHandleEntity( $termEntity )
	{
		$entities = $this->getSupportedEntities();
		return in_array( $termEntity, $entities );
	}
}