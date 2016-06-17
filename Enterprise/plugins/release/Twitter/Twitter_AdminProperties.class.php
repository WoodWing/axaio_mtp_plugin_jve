<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 **/

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class Twitter_AdminProperties extends AdminProperties_EnterpriseConnector
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
	 * @return DialogWidget[]
	 */
	private function doCollectDialogWidgets( $entity, $mode )
	{
		$widgets = array();
		switch( $entity ) {
			case 'Publication':
			case 'PubChannel':
				break;
			case 'Issue':
				// Draw a separator.
				if( $mode == 'draw_dialog' ) { // Show separator on dialogs, but do not add it to the DB model.
					$widgets['C_TPF_CHANNEL_SEPERATOR'] = new DialogWidget(
						new PropertyInfo( 'C_TPF_CHANNEL_SEPERATOR', 'Twitter Account', null, 'separator' ),
						new PropertyUsage( 'C_TPF_CHANNEL_SEPERATOR', true, false, false, false ) );
				}

				// Draw the Consumer Key Field.
				$widgets['C_TPF_CHANNEL_CONSUMER_KEY'] = new DialogWidget(
					new PropertyInfo( 'C_TPF_CHANNEL_CONSUMER_KEY', 'Consumer Key', null, 'string', '' ),
					new PropertyUsage( 'C_TPF_CHANNEL_CONSUMER_KEY', true, true, false ));

				// Draw the Consumer Secret Field.
				$widgets['C_TPF_CHANNEL_CONSUMER_SECRET'] = new DialogWidget(
					new PropertyInfo( 'C_TPF_CHANNEL_CONSUMER_SECRET', 'Consumer Secret', null, 'string', '' ),
					new PropertyUsage( 'C_TPF_CHANNEL_CONSUMER_SECRET', true, true, false ));
				break;
		}
		return $widgets;
	}

	/**
	 * Checks if our PublishSystem match what we require for the Admin Properties.
	 *
	 * This is used when determining whether or not to roundtrip the custom properties and
	 * to determine whether or not to display the widgets.
	 *
	 * Checks the following:
	 *
	 * - PublishSystem of the PubChannel should match our plugin's
	 * - Entity should match 'PubChannel' as we only want the data for the channel.
	 *
	 * @param $context The context to check.
	 * @param $entity The entity to check.
	 * @return bool Whether or not the context/entity match the requirements.
	 */
	private function isCorrectPublishSystem($context, $entity)
	{
		if( $entity == 'Issue' ) {
			$pubChannel = $context->getPubChannel();
			$publishSystem = $pubChannel->PublishSystem;
			if( $publishSystem == 'Twitter' ) {
				return true;
			}
		}
		return false;
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

		return ($this->isCorrectPublishSystem($context, $entity))
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
		if ($this->isCorrectPublishSystem($context, $entity)) {
			$showWidgets += $this->doCollectDialogWidgets( $entity, 'draw_dialog' );
		}
	}
}
