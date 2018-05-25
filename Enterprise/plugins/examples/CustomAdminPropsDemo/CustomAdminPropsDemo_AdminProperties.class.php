<?php
/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
**/

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class CustomAdminPropsDemo_AdminProperties extends AdminProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * Build a list of custom admin properties to show at the admin Maintenance pages.
	 * This is called to (1) extend the DB model, (2) add widgets to store/travel with
	 * the given entity, (3) draw widgets on the admin Maintenance pages.
	 *
	 * @param string $entity Admin object type: Publication, PubChannel, Issue, Edition, Section
	 * @param string $mode update_dbmodel, extend_entity or draw_dialog
	 * @return DialogWidget[]
	 */
	private function doCollectDialogWidgets( $entity, $mode )
	{
		$widgets = array();
		switch( $entity ) {
			case 'Publication':
			case 'PubChannel':
			case 'Issue':
				if( $mode == 'draw_dialog' ) { // Show separator on dialogs, but do not add it to the DB model.
					$widgets['C_CUSTADMPROPDEMO_SEP1'] = new DialogWidget( 
						new PropertyInfo( 'C_CUSTADMPROPDEMO_SEP1', 'User Account - Custom Demo', null, 'separator' ),
						new PropertyUsage( 'C_CUSTADMPROPDEMO_SEP1', true, false, false, false ) );
				}
				$widgets['C_CUSTADMPROPDEMO_USERNAME'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_USERNAME', BizResources::localize('USR_USER_NAME'), null, 'string', '' ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_USERNAME', true, false, false ));
				$widgets['C_CUSTADMPROPDEMO_PASSWORD'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_PASSWORD', BizResources::localize('USR_PASSWORD'), null, 'password', '' ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_PASSWORD', true, false, false ));

				if( $mode == 'draw_dialog' ) { // Show separator on dialogs, but do not add it to the DB model.
					$widgets['C_CUSTADMPROPDEMO_SEP2'] = new DialogWidget( 
						new PropertyInfo( 'C_CUSTADMPROPDEMO_SEP2', 'Field Types - Custom Demo', null, 'separator' ),
						new PropertyUsage( 'C_CUSTADMPROPDEMO_SEP2', true, false, false, false ) );
				}
				$widgets['C_CUSTADMPROPDEMO_TRAFFIC'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_TRAFFIC', 'Traffic', null, 'list', '5', array('3'=>'red','5'=>'orange','7'=>'green') ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_TRAFFIC', true, false, false ));
				$widgets['C_CUSTADMPROPDEMO_SHOPPING'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_SHOPPING', 'Shopping', null, 'multilist', '5', array('3'=>'vegetables','5'=>'butter','7'=>'milk') ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_SHOPPING', true, false, false ));
				$widgets['C_CUSTADMPROPDEMO_KEYWORDS'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_KEYWORDS', 'Keywords', null, 'multistring', 'web,cms,content,management' ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_KEYWORDS', true, false, false ));
				$widgets['C_CUSTADMPROPDEMO_STORY'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_STORY', 'Story', null, 'multiline', 'Once upon a time, there was a Drupal integration...' ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_STORY', true, false, false ));
				$widgets['C_CUSTADMPROPDEMO_PROFITS'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_PROFITS', 'Profits', null, 'double', 1.5 ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_PROFITS', true, false, false ));
				$widgets['C_CUSTADMPROPDEMO_HITCOUNT'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_HITCOUNT', 'Hit Count', null, 'int', 1 ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_HITCOUNT', true, false, false ));
				$widgets['C_CUSTADMPROPDEMO_SINCE'] = new DialogWidget( 
					new PropertyInfo( 'C_CUSTADMPROPDEMO_SINCE', 'Since', null, 'date', '' ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_SINCE', true, false, false ));
				$widgets['C_CUSTADMPROPDEMO_SAVE'] = new DialogWidget(
					new PropertyInfo( 'C_CUSTADMPROPDEMO_SAVE', 'Save result', null, 'bool', false ),
					new PropertyUsage( 'C_CUSTADMPROPDEMO_SAVE', true, false, false ));
				if( $mode != 'draw_dialog' ) {
					$widgets['C_CUSTADMPROPDEMO_HIDDEN'] = new DialogWidget(
						new PropertyInfo( 'C_CUSTADMPROPDEMO_HIDDEN', 'Hidden field', null, 'string', 'My secret' ),
						new PropertyUsage( 'C_CUSTADMPROPDEMO_HIDDEN', true, false, false ));
					$widgets['C_CUSTADMPROPDEMO_HIDDEN']->PropertyInfo->AdminUI = false;
				}
			break;
		}
		return $widgets;
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
		return $this->doCollectDialogWidgets( $entity, 'extend_entity' );
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
		$showWidgets += $this->doCollectDialogWidgets( $entity, 'draw_dialog' ); 
	}
}
