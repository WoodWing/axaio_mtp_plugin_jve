<?php
/**
 * @package    Enterprise
 * @subpackage Elvis
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Connector that adds the "Production Zone" property to the Brand Maintenance page under the "Elvis" section.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class Elvis_AdminProperties extends AdminProperties_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	final public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}

	/**
	 * @inheritdoc
	 */
	private function doCollectDialogWidgets( $entity, $mode )
	{
		$widgets = array();
		switch( $entity ) {
			case 'Publication':
				// Draw a separator.
				if( $mode == 'draw_dialog' ) { // Show separator on dialogs, but do not add it to the DB model.
					$widgets['C_ELVIS_SEPERATOR'] = new DialogWidget(
						new PropertyInfo( 'C_ELVIS_SEPERATOR', 'Elvis', null, 'separator' ),
						new PropertyUsage( 'C_ELVIS_SEPERATOR', true, false, false, false ) );
				}
				// Elvis production zone (path setting)
				$title = BizResources::localize('Elvis.PRODUCTION_ZONE');
				$widgets['C_ELVIS_PRODUCTION_ZONE'] = new DialogWidget(
					new PropertyInfo( 'C_ELVIS_PRODUCTION_ZONE', $title, null, 'string', '' ),
					new PropertyUsage( 'C_ELVIS_PRODUCTION_ZONE', true, false, false )
				);
				break;
		}
		return $widgets;
	}

	/**
	 * @inheritdoc
	 */
	final public function collectDialogWidgets( $entity )
	{
		return $this->doCollectDialogWidgets( $entity, 'update_dbmodel' );
	}

	/**
	 * @inheritdoc
	 */
	public function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action )
	{
		return $this->doCollectDialogWidgets( $entity, 'extend_entity' );
	}

	/**
	 * @inheritdoc
	 */
	final public function buildDialogWidgets( AdminProperties_Context $context, $entity, $action, $allWidgets, &$showWidgets )
	{
		// Because we want to auto-fill-in the C_ELVIS_PRODUCTION_ZONE field value, we hide that field (widget)
		// during Brand creation ($action =='Create'). Once the brand is created by admin user, its properties are
		// shown again in the Brand Maintenance page, but now in the update mode ($action=='Update'). In this mode
		// we -do- show the field since that has been populated by our AdmCreatePublications connector in the meantime.
		if( $action == 'Update' ) {
			// Let's simply add our custom props at the end of all properties.
			$showWidgets += $this->doCollectDialogWidgets( $entity, 'draw_dialog' );
		}
	}
}