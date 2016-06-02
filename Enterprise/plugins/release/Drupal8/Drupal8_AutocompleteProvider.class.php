<?php
/****************************************************************************
   Copyright 2013 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR . '/server/interfaces/plugins/connectors/AutocompleteProvider_EnterpriseConnector.class.php';

class Drupal8_AutocompleteProvider extends AutocompleteProvider_EnterpriseConnector
{
	/**
	 * Refer to AutocompleteProvider_EnterpriseConnector::getSupportedEntities() header for more information.
	 *
	 * @return string[] List of supported Term Entities.
	 */
	public function getSupportedEntities()
	{
		require_once BASEDIR .'/server/bizclasses/BizAdmAutocomplete.class.php';
		require_once dirname(__FILE__).'/Utils.class.php';
		$provider = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
		$termEntitiesObj = BizAdmAutocomplete::getAutocompleteTermEntities( $provider );
		static $cachedSupportedEntities;
		if( !isset( $cachedSupportedEntities[$provider] )) {
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