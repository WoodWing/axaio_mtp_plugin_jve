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

class StandaloneAutocompleteSample_AutocompleteProvider extends AutocompleteProvider_EnterpriseConnector
{
	/**
	 * Refer to AutocompleteProvider_EnterpriseConnector::getSupportedEntities() header for more information.
	 *
	 * @return string[]
	 */
	public function getSupportedEntities()
	{
		return array( 'City' );
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
		$tags = array();
		$entities = $this->getSupportedEntities();
		if( in_array( $termEntityName, $entities ) ) {
			require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';

			if ( preg_match( '/^a/i', $typedValue ) ) {
				$tag = new AutoSuggestTag();
				$tag->Value = 'Amsterdam';
				$tag->Score = round(1.0, 2);;
				$tag->StartPos = 0;
				$tag->Length = mb_strlen( $typedValue );
				$tags[] = $tag;

				$tag = new AutoSuggestTag();
				$tag->Value = 'Amstelveen';
				$tag->Score = round(1.0, 2);;
				$tag->StartPos = 0;
				$tag->Length = mb_strlen( $typedValue );
				$tags[] = $tag;

				$tag = new AutoSuggestTag();
				$tag->Value = 'Abcoude';
				$tag->Score = round(1.0, 2);;
				$tag->StartPos = 0;
				$tag->Length = mb_strlen( $typedValue );
				$tags[] = $tag;
			} elseif ( preg_match( '/^b/i', $typedValue ) ) {
				$tag = new AutoSuggestTag();
				$tag->Value = 'Breukelen';
				$tag->Score = round(1.0, 2);;
				$tag->StartPos = 0;
				$tag->Length = mb_strlen( $typedValue );
				$tags[] = $tag;

				$tag = new AutoSuggestTag();
				$tag->Value = 'Baarn';
				$tag->Score = round(1.0, 2);;
				$tag->StartPos = 0;
				$tag->Length = mb_strlen( $typedValue );
				$tags[] = $tag;

				$tag = new AutoSuggestTag();
				$tag->Value = 'Badhoevedorp';
				$tag->Score = round(1.0, 2);;
				$tag->StartPos = 0;
				$tag->Length = mb_strlen( $typedValue );
				$tags[] = $tag;
			}
		}

		// Filter out ignore values.
		if ($tags) foreach ($tags as $key => $tag ) {
			if (in_array( $tag->Value, $ignoreValues) ) {
				unset($tags[$key]);
			}
		}

		return $tags;
	}
}