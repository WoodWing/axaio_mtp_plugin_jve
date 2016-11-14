<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v9.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class WordPress_AdminProperties extends AdminProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * Build a list of custom admin properties to show at the admin Maintenance pages.
	 * This is called to (1) extend the DB model, (2) add widgets to store/travel with
	 * the given entity, (3) draw widgets on the admin Maintenance pages.
	 *
	 * @param AdminProperties_Context $context The context to check only needs to be set when the action is 'draw_dialog'
	 * @param string $entity Admin object type: Publication, PubChannel, Issue, Edition, Section
	 * @param string $mode update_dbmodel, extend_entity or draw_dialog
	 *
	 * @todo Translate the the dialog labels through TMS.
	 *
	 * @return DialogWidget[]
	 */
	private function doCollectDialogWidgets( $context, $entity, $mode )
	{
		$widgets = array();
		switch( $entity ) {
			case 'Publication':
			case 'Issue':
				break;
			case 'PubChannel':
				// Draw a separator.
				if( $mode == 'draw_dialog' ) { // Show separator on dialogs, but do not add it to the DB model.
					$separatorName = BizResources::localize( 'WORDPRESS_SITE_LABEL' );
					$widgets['C_WP_CHANNEL_SEPARATOR'] = new DialogWidget(
						new PropertyInfo( 'C_WP_CHANNEL_SEPARATOR', $separatorName, null, 'separator' ),
						new PropertyUsage( 'C_WP_CHANNEL_SEPARATOR', true, false, false, false ) );
				}

				require_once dirname(__FILE__).'/config.php';
				$wordPressSites = unserialize( WORDPRESS_SITES );
				$siteKeys = array_keys( $wordPressSites );
				$preparedSiteKeys = array();
				foreach( $siteKeys as $siteKey ){
					$preparedSiteKeys[strval($siteKey)] = $siteKey;
				}
				if( !$preparedSiteKeys ){
					$preparedSiteKeys[] = BizResources::localize( 'WORDPRESS_NO_SITES_CONFIGURED' );
				}

				if( $mode == 'draw_dialog' ) {
					require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
					require_once BASEDIR.'/server/utils/PublishingUtils.class.php';

					$channel = $context->getPubChannel();
					$channelObj = WW_Utils_PublishingUtils::getAdmChannelById( $channel->Id );
					$siteKey = BizAdmProperty::getCustomPropVal( $channelObj->ExtraMetaData, 'C_WP_CHANNEL_SITE' );
					if ( !$siteKey && $siteKeys ) {
						// If there is no site key set yet default to an empty option.
						$preparedSiteKeys = array('' => '') + $preparedSiteKeys; // use the union operator because the keys can be numeric
					}
				}

				// Draw the URL field.
				$dropDownName = BizResources::localize( 'WORDPRESS_WEB_SITE_LABEL' );
				$widgets['C_WP_CHANNEL_SITE'] = new DialogWidget(
					new PropertyInfo( 'C_WP_CHANNEL_SITE', $dropDownName, null, 'list', null, $preparedSiteKeys ),
					new PropertyUsage( 'C_WP_CHANNEL_SITE', true, true, true ));

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
	 * @param string $entity Admin object type: Publication, PubChannel, Issue, Edition, Section
	 * @param string $action The action to check
	 * @return bool Whether or not the context/entity match the requirements.
	 */
	private function isCorrectPublishSystem( $context, $entity, $action )
	{
		$match = false;
		$isSystemChanged = false;
		if( $entity == 'PubChannel'  && $action != 'Create' ) {
			if( $action == 'Update' ) {
				// Determine the PubChannelId.
				$contextPubChannelObj = $context->getPubChannel();
				$pubChannelId = $contextPubChannelObj->Id;

				// Retrieve the previous channel.
				require_once BASEDIR . '/server/utils/PublishingUtils.class.php';
				$publicationChannel = WW_Utils_PublishingUtils::getAdmChannelById( $pubChannelId );
				if( $publicationChannel->Type != 'web' || $publicationChannel->PublishSystem != 'WordPress' ) {
					$isSystemChanged = true;
				}
			}
			$pubChannel = $context->getPubChannel();
			$publishSystem = $pubChannel->PublishSystem;
			$chanType = $pubChannel->Type;
			if( $chanType == 'web' && $publishSystem == 'WordPress' && !$isSystemChanged ) {
				$match = true;
			}
		}
		return $match;
	}

	/**
	 * Collect all possible custom properties for the given entity to extend the DB model.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 *
	 * @param string $entity Admin object type: Publication, PubChannel, Issue, Edition, Section
	 * @return array Array of DialogWidget
	 */
	final public function collectDialogWidgets( $entity )
	{
		return $this->doCollectDialogWidgets( null, $entity, 'update_dbmodel' );
	}

	/**
	 * Collect custom properties for the given context to travel along with the entity instance.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 *
	 * @param AdminProperties_Context $context
	 * @param string $entity
	 * @param string $action
	 * @return array True to return array of DialogWidget, else empty array
	 */
	public function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action )
	{
		return ($this->isCorrectPublishSystem($context, $entity, $action))
			? $this->doCollectDialogWidgets( $context, $entity, 'extend_entity') : array();
	}

	/**
	 * Add (or adjust) given dialog widgets ($showWidgets) to show admin user for given entity+action.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 *
	 * @param AdminProperties_Context $context
	 * @param string $entity
	 * @param string $action
	 * @param array $allWidgets
	 * @param $showWidgets
	 * @return array of Dialogwidgets
	 */
	final public function buildDialogWidgets( AdminProperties_Context $context, $entity, $action, $allWidgets, &$showWidgets )
	{
		// Add our custom props depending on the given admin entity.
		// Let's simply add our custom props at the end of all properties.
		if ($this->isCorrectPublishSystem($context, $entity, $action)) {
			$showWidgets += $this->doCollectDialogWidgets( $context, $entity, 'draw_dialog' );
		}
	}
}
