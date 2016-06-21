<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 **/

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class Drupal7_AdminProperties extends AdminProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * Build a list of custom admin properties to show at the admin Maintenance pages.
	 * This is called to (1) extend the DB model, (2) add widgets to store/travel with
	 * the given entity, (3) draw widgets on the admin Maintenance pages.
	 *
	 * @param string $entity Admin object type: Publication, PubChannel, Issue, Edition, Section
	 * @param string $mode update_dbmodel, extend_entity or draw_dialog
	 *
	 * @todo Translate the the dialog labels through TMS.
	 *
	 * @return DialogWidget[]
	 */
	private function doCollectDialogWidgets( $entity, $mode )
	{
		require_once dirname(__FILE__) . '/Utils.class.php';

		$widgets = array();
		switch( $entity ) {
			case 'Publication':
				break;
			case 'PubChannel':
				// Draw a separator.
				if( $mode == 'draw_dialog' ) { // Show separator on dialogs, but do not add it to the DB model.
					$widgets[WW_Plugins_Drupal7_Utils::CHANNEL_SEPERATOR] = new DialogWidget(
						new PropertyInfo( WW_Plugins_Drupal7_Utils::CHANNEL_SEPERATOR, 'Drupal Account', null, 'separator' ),
						new PropertyUsage( WW_Plugins_Drupal7_Utils::CHANNEL_SEPERATOR, true, false, false, false ) );
				}

				// Draw the URL field.
				$widgets[WW_Plugins_Drupal7_Utils::CHANNEL_SITE_URL] = new DialogWidget(
					new PropertyInfo( WW_Plugins_Drupal7_Utils::CHANNEL_SITE_URL, 'Web Site URL', null, 'string', '' ),
					new PropertyUsage( WW_Plugins_Drupal7_Utils::CHANNEL_SITE_URL, true, true, false ));

				// Draw the Consumer Key Field.
				$widgets[WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_KEY] = new DialogWidget(
					new PropertyInfo( WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_KEY, 'Consumer Key', null, 'string', '' ),
					new PropertyUsage( WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_KEY, true, true, false ));

				// Draw the Consumer Secret Field.
				$widgets[WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_SECRET] = new DialogWidget(
					new PropertyInfo( WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_SECRET, 'Consumer Secret', null, 'string', '' ),
					new PropertyUsage( WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_SECRET, true, true, false ));

				// Draw the Certificate field.
				$widgets[WW_Plugins_Drupal7_Utils::CHANNEL_CERTIFICATE] = new DialogWidget(
					new PropertyInfo( WW_Plugins_Drupal7_Utils::CHANNEL_CERTIFICATE, 'Certificate', null, 'string', '' ),
					new PropertyUsage( WW_Plugins_Drupal7_Utils::CHANNEL_CERTIFICATE, true, false, false ));

				break;
			case 'Issue':
				break;
		}
		return $widgets;
	}

	/**
	 * Checks if our PublishSystem and Entity match what we require for the Admin Properties.
	 *
	 * This is used when determining whether or not to roundtrip the custom properties and
	 * to determine whether or not to display the widgets.
	 *
	 * Checks the following:
	 *
	 * - PublishSystem of the PubChannel should match our plugin's
	 * - Entity should match 'PubChannel' as we only want the data for the channel.
	 * - PubChannel Type should match 'web'.
	 *
	 * On a create action we cannot initially add the fields already since there was no action type submitted, so if
	 * a create action is attempted, do not draw the fields, only at an update should the fields be drawn, and only if
	 * the old type was a match for this system.
	 *
	 * @param $context The context to check.
	 * @param $entity The entity to check.
	 * @param $action The action being performed.
	 * @return bool Whether or not the context/entity match the requirements.
	 */
	private function isCorrectPublishSystem($context, $entity, $action)
	{
		$match = false;
		$isSystemChanged = false;
		require_once dirname(__FILE__) . '/Utils.class.php';

		if( $entity == 'PubChannel' && $action != 'Create') {

			// If the action is not Create, then it is Update, in which case we need to see what our previous channel
			// types were, and not return widgets if they do not match.
			if ($action == 'Update') {
				// Determine the PubChannelId.
				$contextPubChannelObj = $context->getPubChannel();
				$pubChannelId = $contextPubChannelObj->Id;

				// Retrieve the previous channel.
				require_once BASEDIR . '/server/utils/PublishingUtils.class.php';
				require_once dirname(__FILE__) . '/Utils.class.php';
				$publicationChannel = WW_Utils_PublishingUtils::getAdmChannelById($pubChannelId);

				if ($publicationChannel->Type != 'web' || $publicationChannel->PublishSystem != WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME) {
					$isSystemChanged = true;
				}
			}

			$pubChannel = $context->getPubChannel();
			$chanType = $pubChannel->Type;
			$publishSystem = $pubChannel->PublishSystem;

			if( $chanType == 'web' && $publishSystem == WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME && !$isSystemChanged) {
				$match = true;
			}
		}
		return $match;
	}

	/**
	 * Collect all possible custom properties for the given entity to extend the DB model.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 */
	final public function collectDialogWidgets( $entity )
	{
		return $this->doCollectDialogWidgets( $entity, 'update_dbmodel' );
	}

	/**
	 * Collect custom properties for the given context to travel along with the entity instance.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 */
	public function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action )
	{
		$action = $action; // keep analyzer happy

		return ($this->isCorrectPublishSystem($context, $entity, $action))
			? $this->doCollectDialogWidgets( $entity, 'extend_entity')
			: array();
	}

	/**
	 * Add (or adjust) given dialog widgets ($showWidgets) to show admin user for given entity+action.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 */
	final public function buildDialogWidgets( AdminProperties_Context $context, $entity, $action, $allWidgets, &$showWidgets )
	{
		$action = $action; $allWidgets = $allWidgets; // keep code analyzer happy

		// This way you can grab contextual data:
		//$pubObj = $context->getPublication();
		//$channelObj = $context->getPubChannel();
		//$issueObj = $context->getIssue();

		// Add our custom props depending on the given admin entity.
		// Let's simply add our custom props at the end of all properties.
		if ($this->isCorrectPublishSystem($context, $entity, $action)) {
			$showWidgets += $this->doCollectDialogWidgets( $entity, 'draw_dialog' );
		}
	}
}
