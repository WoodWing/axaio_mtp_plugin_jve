<?php
/**
 * @package   Enterprise
 * @subpackage   ServerPlugins
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Autocomplete provider connector interface to help end-user fill in dialog property values.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/AutocompleteProvider_EnterpriseConnector.class.php';

class Drupal8_AutocompleteProvider extends AutocompleteProvider_EnterpriseConnector
{
	/**
	 * Refer to AutocompleteProvider_EnterpriseConnector::getSupportedEntities() header for more information.
	 *
	 * @return string[] List of supported Term Entities.
	 */
	public function getSupportedEntities()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocomplete.class.php';
		require_once dirname( __FILE__ ).'/Utils.class.php';
		$provider = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
		$termEntitiesObj = BizAdmAutocomplete::getAutocompleteTermEntities( $provider );
		static $cachedSupportedEntities;
		if( !isset( $cachedSupportedEntities[ $provider ] ) ) {
			$supportedEntities = array();
			if( $termEntitiesObj ) foreach( $termEntitiesObj as $termEntityObj ) {
				$supportedEntities[] = $termEntityObj->Name;
			}
			$cachedSupportedEntities[ $provider ] = $supportedEntities;
		}

		return $cachedSupportedEntities[ $provider ];
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
	 * In case of 'entity reference' term fields the suggestions are retrieved from Drupal, otherwise the Suggestion
	 * Provider ss used.
	 *
	 * @since 10.1.2
	 * @inheritdoc
	 */
	public function autocomplete( $provider, $objectId, $propertyName, $termEntityName, $publishSystemId, $ignoreValues, $typedValue )
	{
		$tags = array();
		if( $termEntityName == 'entityreference' ) {
			if( mb_strlen( $typedValue ) >= 2 ) { // To get meaningful suggestions at least two characters are needed.
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$documentId = DBObject::getDocumentIdOfPublishFormTemplateUsedByProperty( $propertyName );
				if( $documentId && $propertyName ) {
					$drupalFieldId = $this->extractDrupalFieldIdFromPropertyName( $propertyName );
					$drupalContentTypeId = $this->extractDrupalContentTypeIdFromDocumentId( $documentId );
					$drupalXmlRpcClient = $this->createDrupalXmlRpcClient( $objectId, $publishSystemId );
					$drupalFieldValues = $drupalXmlRpcClient->getTermEntityValues( $drupalContentTypeId, $drupalFieldId, $typedValue );
					$tags = $this->createTagsFromDrupalFieldValues( $drupalFieldValues, $typedValue );
				} else {
					LogHandler::Log( 'Drupal8Publish', 'DEBUG', 'ERROR: Unable to resolve Drupal content type Id or field Id. Suggestion: Re-import the content types from Drupal.' );
				}
			}
		} else {
			$tags = parent::autocomplete( $provider, $objectId, $propertyName, $termEntityName, $publishSystemId, $ignoreValues, $typedValue );
		}

		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'Drupal8Publish', 'DEBUG', 'Retrieved tags: '.print_r( $tags, 1 ) );
		}
		
		return $tags;
	}

	/**
	 * Extracts the (term) field identifier from the property name.
	 *
	 * @param $propertyName
	 * @return string
	 */
	private function extractDrupalFieldIdFromPropertyName( $propertyName )
	{
		$fields = explode( '_', $propertyName );
		$drupalFieldId = $fields[3];

		return $drupalFieldId;
	}

	/**
	 * Extracts the content type identifier from the document id.
	 *
	 * @since 10.1.2
	 * @param string $documentId of a publish form.
	 * @return string Drupal content Type identifier
	 */
	private function extractDrupalContentTypeIdFromDocumentId( $documentId )
	{
		$fields = explode( '_', $documentId );
		$drupalContentTypeId = $fields[2];

		return $drupalContentTypeId;
	}

	/**
	 * Create tags based on suggestions.
	 *
	 * @since 10.1.2
	 * @param array $fieldValues Suggestions retrieved from provider
	 * @param string $typedValue Value used as search argument
	 * @return AutoSuggestTag[]
	 */
	private function createTagsFromDrupalFieldValues( $fieldValues, $typedValue )
	{
		$tags = array();
		if( $fieldValues ) foreach( $fieldValues as $fieldValue ) {
			$tag = new AutoSuggestTag();
			$tag->Value = $fieldValue;
			$tag->Score = 1;
			$tag->StartPos = mb_strpos( $fieldValue, $typedValue );
			$tag->Length = mb_strlen( $typedValue );
			$tags[] = $tag;
		}

		return $tags;
	}

	/**
	 * Creates XmlRpcClient.
	 *
	 * @since 10.1.2
	 * @param int $objectId Object Id
	 * @param string $publishSystemId Drupal System Id
	 * @return WW_Plugins_Drupal8_XmlRpcClient $drupalXmlRpcClient;
	 */
	private function createDrupalXmlRpcClient( $objectId, $publishSystemId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$publicationId = DBObject::getObjectPublicationId( $objectId );
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		$channelId = DBChannel::getChannelIdForPublishSystemId( $publicationId, $publishSystemId );
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		$publishTarget = new PubPublishTarget();
		$publishTarget->PubChannelID = $channelId;
		require_once dirname( __FILE__ ).'/XmlRpcClient.class.php';
		$drupalXmlRpcClient = new WW_Plugins_Drupal8_XmlRpcClient( $publishTarget );

		return $drupalXmlRpcClient;
	}
}