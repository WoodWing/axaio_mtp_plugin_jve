<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Connector that adds admininstration properties to the Publication Channel Maintanence page
 * of 'dps2' channels. It enables the system admin to configure a Adobe DPS account for the channel.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class AdobeDps2_AdminProperties extends AdminProperties_EnterpriseConnector
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
			case 'Issue':
				break;
			case 'PubChannel':
				// Draw a separator.
				require_once dirname(__FILE__).'/config.php'; // DPS2_PLUGIN_DISPLAYNAME
				if( $mode == 'draw_dialog' ) { // Show separator on dialogs, but do not add it to the DB model.
					$widgets['C_DPS2_CHANNEL_SEPERATOR'] = new DialogWidget(
						new PropertyInfo( 'C_DPS2_CHANNEL_SEPERATOR', DPS2_PLUGIN_DISPLAYNAME, null, 'separator' ),
						new PropertyUsage( 'C_DPS2_CHANNEL_SEPERATOR', true, false, false, false ) );
				}

				// Project (Link Reference)
				$widgets['C_DPS2_CHANNEL_PROJECT'] = new DialogWidget(
					new PropertyInfo( 'C_DPS2_CHANNEL_PROJECT', BizResources::localize('AdobeDps2.PROJECT'), null, 'string', '' ),
					new PropertyUsage( 'C_DPS2_CHANNEL_PROJECT', false, false, false )); // read-only!
				
				// Default value to set accessibility for all articles being uploaded.
				require_once dirname(__FILE__).'/utils/Folio.class.php';
				$articleAccessOpts = AdobeDps2_Utils_Folio::getArticleAccessOptions();
				$widgets['C_DPS2_CHANNEL_ART_ACCESS'] = new DialogWidget(
					new PropertyInfo( 'C_DPS2_CHANNEL_ART_ACCESS', BizResources::localize('AdobeDps2.DEFAULT_ARTICLE_ACCESS'), null, 'list', 
										$articleAccessOpts[0], $articleAccessOpts ),
					new PropertyUsage( 'C_DPS2_CHANNEL_ART_ACCESS', true, false, false ));
				
				// Create Collections
				$widgets['C_DPS2_CHANNEL_CREATE_COLLS'] = new DialogWidget(
					new PropertyInfo( 'C_DPS2_CHANNEL_CREATE_COLLS', BizResources::localize('AdobeDps2.CREATE_COLLECTIONS'), null, 'bool', true ),
					new PropertyUsage( 'C_DPS2_CHANNEL_CREATE_COLLS', true, false, false ));
				
				// Project Id (hidden property)
				if( $mode != 'draw_dialog' ) {
					$widgets['C_DPS2_CHANNEL_PROJECT_ID'] = new DialogWidget(
						new PropertyInfo( 'C_DPS2_CHANNEL_PROJECT_ID', 'Project Id', null, 'string', '' ),
						new PropertyUsage( 'C_DPS2_CHANNEL_PROJECT_ID', true, false, false ));
					$widgets['C_DPS2_CHANNEL_PROJECT_ID']->PropertyInfo->AdminUI = false;
				}
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
	 * @return bool Whether or not the context/entity match the requirements.
	 */
	private function isCorrectPublishSystem( $context, $entity )
	{
		$isCorrect = false;
		if( $entity == 'PubChannel' ) {
			require_once dirname(__FILE__).'/utils/Folio.class.php';
			$pubChannel = $context->getPubChannel();
			if( $pubChannel->Type == AdobeDps2_Utils_Folio::CHANNELTYPE ) {
				$isCorrect = true;
			}
		}
		return $isCorrect;
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
	 *
	 * @param AdminProperties_Context $context
	 * @param string $entity
	 * @param string $action
	 * @return array|DialogWidget[]|null
	 */
	public function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action )
	{
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
		// This way you can grab contextual data:
		//$pubObj = $context->getPublication();
		//$channelObj = $context->getPubChannel();
		//$issueObj = $context->getIssue();

		// Add our custom props depending on the given admin entity.
		// Let's simply add our custom props at the end of all properties.
		if( $this->isCorrectPublishSystem( $context, $entity ) ) {
			$showWidgets += $this->doCollectDialogWidgets( $entity, 'draw_dialog' );
		}
	}
}
