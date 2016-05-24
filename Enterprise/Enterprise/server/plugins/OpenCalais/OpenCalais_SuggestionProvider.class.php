<?php
/**
 * Suggestions provider connector implementation for the OpenCalais server plugin.
 * More information about response formats etc. is available at:
 * http://developer.permid.org/open-calais-api/open-calais-tagging-user-guide/
 *
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/SuggestionProvider_EnterpriseConnector.class.php';

class OpenCalais_SuggestionProvider extends SuggestionProvider_EnterpriseConnector
{
	public function getPrio() { return self::PRIO_DEFAULT; }

	/**
	 * When a Publish Form dialog is requested by client, the core server calls this function
	 * to find out for which property entities suggestions can be provided. When returning
	 * TRUE, the {@link:suggest()} function could be called later.
	 *
	 * @param string $termEntity The requested entity, whether or not supported by provider.
	 * @return boolean Whether or not suggestions can be provided.
	 */
	public function canHandleEntity( $termEntity )
	{
		// Supported OpenCalais terms for this plugin.
		$supportedEntities = array(
			'anniversary',
			'city','company', 'continent', 'country', 'currency',
			'emailaddress', 'editor', 'entertainmentawardevent',
			'facility', 'faxnumber',
			'holiday',
			'industryterm',
			'marketindex', 'medicalcondition', 'medicaltreatment', 'movie', 'musicalbum', 'musicgroup',
			'naturalfeature',
			'operatingsystem', 'organization',
			'person', 'pharmaceuticaldrug','phonenumber', 'politicalevent', 'position', 'product', 'programminglanguage', 'provinceorstate', 'publishedmedium',
			'radioprogram', 'radiostation', 'region',
			'sportsevent', 'sportsgame', 'sportsleague',
			'tvshow', 'tvstation', 'technology',
			'url',
			'socialtags'
		);

		return in_array( strtolower( $termEntity ), $supportedEntities );
	}

	/**
	 * When the user is filling in a Publish Form dialog he/she could request for suggestions.
	 * Then all plain text is collected (of the placed articles on the form) and sent to server
	 * together with dialog properties to provide suggestions for. This function is only called
	 * when the connector has returned TRUE through the {@link:canHandleEntity()} function before.
	 *
	 * @param string $objectId ID of object for which to get suggestions.
	 * @param MetaDataValue[] $metadata Properties that contain plain text to base suggestions on.
	 * @param AutoSuggestProperty[] $suggestForProps Properties to provide suggestions for.
	 * @return EntityTags[]
	 */
	public function suggest( $objectId, $metadata, $suggestForProps )
	{
		$objectId = $objectId; // Keep the analyzer happy.

		require_once BASEDIR . '/server/bizclasses/BizAutoSuggest.class.php';
		require_once BASEDIR . '/server/plugins/OpenCalais/OpenCalais.class.php';

		// Sort out the content for which to get suggestions, and the TermEntities that are wanted.
		$plainContent = BizAutoSuggest::consolidateMetaDataValues( $metadata );
		$suggestionEntities = BizAutoSuggest::consolidateRequestedSuggestionEntities( $suggestForProps );
		$suggestionEntities = $suggestionEntities['suggestionentities'];

		// OpenCalais can handle input up to 100.000 characters, as a safety net we truncate on 99.999 characters.
		$plainContent = mb_substr( $plainContent, 0, 99999, 'UTF-8' );

		// Request the suggestions from OpenCalais.
		$suggestions = OpenCalais::suggest( $plainContent, $suggestionEntities );

		return $suggestions;
	}
}
