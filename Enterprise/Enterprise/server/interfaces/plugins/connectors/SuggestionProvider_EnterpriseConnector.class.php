<?php
/**
 * @since 		v9.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * SuggestionProvider connector interface. The connector implementing this interface can
 * provide suggestions for properties that are shown on Publish Forms.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class SuggestionProvider_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Returns a list of TermEntities that can be handled by this connector.
	 *
	 * When a Publish Form dialog is requested by a client, the core server calls this function
	 * to find out for which property entities suggestions can be provided. When returning
	 * TRUE, the {@link:suggest()} function could be called later.
	 *
	 * @param string $termEntity The requested entity, whether or not supported by provider.
	 * @return bool Whether or not suggestions can be provided.
	 */
	abstract public function canHandleEntity( $termEntity );

	/**
	 * Requests suggestions from the Plugin.
	 *
	 * When the user is filling in a Publish Form dialog he/she could request suggestions.
	 * All plain text is collected (from the placed articles on the form, and text fields) and sent to  the server
	 * together with dialog properties for which to provide suggestions. This function is only called
	 * when the connector has returned TRUE through the {@link:canHandleEntity()} function before.
	 *
	 * @param string $objectId ID of the Object for which the dialog is drawn.
	 * @param MetaDataValue[] $metadata Properties that contain plain text to base suggestions on.
	 * @param AutoSuggestProperty[] $suggestForProps Properties to provide suggestions for.
	 * @return EntityTags[] The found EntitityTags.
	 */
	abstract public function suggest( $objectId, $metadata, $suggestForProps );

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); }
	final public function getInterfaceVersion() { return 1; }
}