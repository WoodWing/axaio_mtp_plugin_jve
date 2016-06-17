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

class MultiChannelPublishingSample_AutocompleteProvider extends AutocompleteProvider_EnterpriseConnector
{

	/**
	 * Refer to AutocompleteProvider_EnterpriseConnector::getSupportedEntities() header for more information.
	 *
	 * @return string[]
	 */
	public function getSupportedEntities()
	{
		return array( 'City', 'Country' );
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

	/**
	 * Allows (admin) user to manage the Term Entities.
	 *
	 * Refer to AutocompleteProvider_EnterpriseConnector::areTermEntitiesEditable() header for more information.
	 *
	 * @return bool
	 */
	public function	areTermEntitiesEditable()
	{
		return true;
	}

	/**
	 * Allows (admin) user to manage the Terms.
	 *
	 * Refer to AutocompleteProvider_EnterpriseConnector::areTermsEditable() header for more information.
	 *
	 * @return bool
	 */
	public function areTermsEditable()
	{
		return true;
	}

}