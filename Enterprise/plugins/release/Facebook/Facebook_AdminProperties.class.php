<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 **/

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class Facebook_AdminProperties extends AdminProperties_EnterpriseConnector
{
	final public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}

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
				// Draw a separator.
				if( $mode == 'draw_dialog' ) { // Show separator on dialogs, but do not add it to the DB model.
					$widgets['C_FPF_CHANNEL_SEPERATOR'] = new DialogWidget(
						new PropertyInfo( 'C_FPF_CHANNEL_SEPERATOR', 'Facebook Account', null, 'separator' ),
						new PropertyUsage( 'C_FPF_CHANNEL_SEPERATOR', true, false, false, false ) );
				}

				// Draw the Channel ID Field.
				$widgets['C_FPF_CHANNEL_APPLICATION_ID'] = new DialogWidget(
					new PropertyInfo( 'C_FPF_CHANNEL_APPLICATION_ID', 'Application ID', null, 'string', '' ),
					new PropertyUsage( 'C_FPF_CHANNEL_APPLICATION_ID', true, true, false ) );

				// Draw the Channel Secret Field.
				$widgets['C_FPF_CHANNEL_APP_SECRET'] = new DialogWidget(
					new PropertyInfo( 'C_FPF_CHANNEL_APP_SECRET', 'Application Secret', null, 'string', '' ),
					new PropertyUsage( 'C_FPF_CHANNEL_APP_SECRET', true, true, false ) );

				// Draw the Channel Page ID.
				$widgets['C_FPF_CHANNEL_PAGE_ID'] = new DialogWidget(
					new PropertyInfo( 'C_FPF_CHANNEL_PAGE_ID', 'Page ID', null, 'string', '' ),
					new PropertyUsage( 'C_FPF_CHANNEL_PAGE_ID', true, true, false ) );
				break;
			case 'Issue':
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
	 * @param AdminProperties_Context $context The context to check.
	 * @param string $entity The entity to check.
	 * @param string $action The action to check
	 * @return bool Whether or not the context/entity match the requirements.
	 */
	private function isCorrectPublishSystem( $context, $entity, $action )
	{
		$match = false;
		$isSystemChanged = false;

		if( $entity == 'PubChannel' && $action != 'Create' ) {

			if( $action == 'Update' ) {
				$pubChannel = $context->getPubChannel();
				$publishSystem = $pubChannel->PublishSystem;
				$chanType = $pubChannel->Type;
				if( $chanType != 'web' || $publishSystem != 'Facebook' ) {
					$isSystemChanged = true;
				}
			}
			$pubChannel = $context->getPubChannel();
			$publishSystem = $pubChannel->PublishSystem;
			$chanType = $pubChannel->Type;
			if( $chanType == 'web' && $publishSystem == 'Facebook' && !$isSystemChanged ) {
				$match = true;
			}
		}
		return $match;
	}

	/**
	 * Collect all possible custom properties for the given entity to extend the DB model.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 *
	 * @param string $entity
	 * @return DialogWidget[]
	 */
	final public function collectDialogWidgets( $entity )
	{
		return $this->doCollectDialogWidgets( $entity, 'update_dbmodel' );
	}

	/**
	 * Collect custom properties for the given context to travel along with the entity instance.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 *
	 * @param AdminProperties_Context $context
	 * @param string $entity
	 * @param string $action
	 * @return DialogWidget[]
	 */
	public function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action )
	{
		return ( $this->isCorrectPublishSystem( $context, $entity, $action ) )
			? $this->doCollectDialogWidgets( $entity, 'extend_entity' )
			: array();
	}

	/**
	 * Add (or adjust) given dialog widgets ($showWidgets) to show admin user for given entity+action.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 *
	 * @param AdminProperties_Context $context
	 * @param string $entity
	 * @param string $action
	 * @param DialogWidget[] $allWidgets
	 * @param DialogWidget[] $showWidgets
	 */
	final public function buildDialogWidgets( AdminProperties_Context $context, $entity, $action, $allWidgets, &$showWidgets )
	{
		// This way you can grab contextual data:
		//$pubObj = $context->getPublication();
		//$channelObj = $context->getPubChannel();
		//$issueObj = $context->getIssue();

		// Add our custom props depending on the given admin entity.
		// Let's simply add our custom props at the end of all properties.
		if( $this->isCorrectPublishSystem( $context, $entity, $action ) ) {
			$showWidgets += $this->doCollectDialogWidgets( $entity, 'draw_dialog' );
		}
	}
}
