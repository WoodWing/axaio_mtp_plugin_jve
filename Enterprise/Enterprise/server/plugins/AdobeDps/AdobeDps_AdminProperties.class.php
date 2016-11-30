<?php
/**
 * @package 	Enterprise
 * @subpackage ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
**/

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class AdobeDps_AdminProperties extends AdminProperties_EnterpriseConnector
{	
	// Fields with value inherit from the last issue created
	private $lastIssueFields = array('C_DPS_PRODUCTID'			    => null,	// unique identifier of folio
									 'C_DPS_PAGE_ORIENTATION'	    => null,	// page orientation
									 'C_DPS_NAVIGATION'			    => null,	// article navigation
									 'C_DPS_PUBLICATION_TITLE'	    => null,	// magazine Title
									 'C_DPS_READINGDIRECTION'	    => null,	// reading direction
									 'C_DPS_VOLUMENUMBER'		    => null,	// unique publisher defined identifier for folio
									 'C_DPS_FILTER'                 => null,	// allow end user to filter issue based on the filter keyword
									 'C_DPS_COVER_DATE'				=> null);	// Cover date
	private $editable = true;	// An indicator to set issue properties to editable or not
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
		require_once BASEDIR.'/server/bizclasses/BizResources.class.php';
		require_once BASEDIR.'/config/config_dps.php';

		$widgets = array();
		if( $entity == 'Issue' ) {
			if( $mode != 'draw_dialog' ) {
				$widgets['C_HIDDEN_DPS_DOSSIER_ORDER'] = new DialogWidget(
					new PropertyInfo('C_HIDDEN_DPS_DOSSIER_ORDER', 'Dossier order', null, 'multistring', '' ),
					new PropertyUsage('C_HIDDEN_DPS_DOSSIER_ORDER', $this->editable, true, false ));
				$widgets['C_HIDDEN_DPS_DOSSIER_ORDER']->PropertyInfo->AdminUI = false;
			}

			$widgets['C_DPS_PUBLICATION_TITLE'] = new DialogWidget(
				new PropertyInfo('C_DPS_PUBLICATION_TITLE', BizResources::localize('PRISM_PUBLICATIONNAME'), null, 'string'),
				new PropertyUsage('C_DPS_PUBLICATION_TITLE', $this->editable, false, false));

			$widgets['C_DPS_COVER_DATE'] = new DialogWidget(
				new PropertyInfo('C_DPS_COVER_DATE', BizResources::localize('DPS_COVER_DATE'), null, 'datetime' ),
				new PropertyUsage('C_DPS_COVER_DATE', $this->editable, false, false));

			$widgets['C_DPS_PRODUCTID'] = new DialogWidget(
				new PropertyInfo('C_DPS_PRODUCTID', BizResources::localize('DPS_PRODUCTID'), null, 'string'),
				new PropertyUsage('C_DPS_PRODUCTID', $this->editable, true, false));

			$valueList = array( 'portrait'	=> BizResources::localize('PORTRAIT'),
								'landscape'	=> BizResources::localize('LANDSCAPE'),
								'always' 	=> BizResources::localize('DM_PAGE_ORIENTATION_BOTH') );
			$widgets['C_DPS_PAGE_ORIENTATION'] = new DialogWidget(
				new PropertyInfo('C_DPS_PAGE_ORIENTATION',BizResources::localize('PAGE_ORIENTATION'), null, 'list', $this->lastIssueFields['C_DPS_PAGE_ORIENTATION'], $valueList),
				new PropertyUsage('C_DPS_PAGE_ORIENTATION', $this->editable, false, false));

			$valueList = array( 'horizontal' => BizResources::localize('OBJ_HORIZONTAL'), 
								'horizontalAndVertical' => BizResources::localize('OFIP_NAVIGATION_HORANDVERT') );
			$widgets['C_DPS_NAVIGATION'] = new DialogWidget(
				new PropertyInfo('C_DPS_NAVIGATION', BizResources::localize('OFIP_NAVIGATION'), null, 'list', $this->lastIssueFields['C_DPS_NAVIGATION'], $valueList),
				new PropertyUsage('C_DPS_NAVIGATION', $this->editable, false, false));

			$valueList = array( 'left'	=> BizResources::localize('OBJ_TABLE_LANGUAGE_LTR'), 
								'right'	=> BizResources::localize('OBJ_TABLE_LANGUAGE_RTL') );
			$widgets['C_DPS_READINGDIRECTION'] = new DialogWidget(
				new PropertyInfo('C_DPS_READINGDIRECTION', BizResources::localize('READINGDIRECTION'), null, 'list', $this->lastIssueFields['C_DPS_READINGDIRECTION'], $valueList),
				new PropertyUsage('C_DPS_READINGDIRECTION', $this->editable, false, false));

			$widgets['C_DPS_VOLUMENUMBER'] = new DialogWidget(
				new PropertyInfo('C_DPS_VOLUMENUMBER', BizResources::localize('DPS_VOLUMENUMBER'), null, 'string', ''),
				new PropertyUsage('C_DPS_VOLUMENUMBER', $this->editable, false, false));

			$widgets['C_DPS_FILTER'] = new DialogWidget(
				new PropertyInfo('C_DPS_FILTER', BizResources::localize('DPS_FILTER'), null, 'string', $this->lastIssueFields['C_DPS_FILTER'], 
									null, null, null, 60 ),
				new PropertyUsage('C_DPS_FILTER', $this->editable, false, false));

			if ( defined('ADOBEDPS_READER_VERSIONS') ) {
				// First prepend an empty option before the list
				$valueList = array_merge( array( '' ), unserialize( ADOBEDPS_READER_VERSIONS ) );
				// The keys should be the same as the values
				$valueList = array_combine($valueList, $valueList);

				// Get the last value, this will be the default.
				$lastValue = end($valueList);
				reset($valueList); // Reset the internal pointer

				$widgets['C_DPS_TARGET_VIEWER_VERSION'] = new DialogWidget(
					new PropertyInfo('C_DPS_TARGET_VIEWER_VERSION', BizResources::localize('DPS_TARGET_VIEWER_VERSION'), null, 'list', $lastValue,
						$valueList ),
					new PropertyUsage('C_DPS_TARGET_VIEWER_VERSION', $this->editable, false, false));
			}
								
		}
		return $widgets;
	}

	/** 
	 * Collect all possible custom properties for the given entity to extend the DB model. 
	 * See AdminProperties_EnterpriseConnector interface for details.
	 * @inheritdoc
	 */
	final public function collectDialogWidgets( $entity )
	{
		return $this->doCollectDialogWidgets( $entity, 'update_dbmodel' );
	}

	/** 
	 * Collect custom properties for the given context to travel along with the entity instance. 
	 * See AdminProperties_EnterpriseConnector interface for details.
	 * @inheritdoc
	 */
	public function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action )
	{
		$widgets = array();
		if( $entity == 'Issue' ) {
			$chanType = $context->getPubChannel()->Type;
			if( $chanType == 'dps' ) {
				if( $this->isPublishedIssue( $context ) ) {
					$this->editable = false;
				}
				$widgets = $this->doCollectDialogWidgets( $entity, 'extend_entity' );
			}
		}
		return $widgets;
	}

	/** 
	 * Add (or adjust) given dialog widgets ($showWidgets) to show admin user for given entity+action.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 * @inheritdoc
	 */
	final public function buildDialogWidgets( AdminProperties_Context $context, $entity, $action, $allWidgets, &$showWidgets )
	{
		if( $entity == 'Issue' ) {
			$chanType = $context->getPubChannel()->Type;
			if( $chanType == 'dps' ) {
				if( $context->getIssue()->Id <= 0){ // indicates creating new issue.
					// Get the last issue id being created for this issue's channel.
					$channelId = $context->getPubChannel()->Id;
					require_once dirname(__FILE__).'/Utils/AdobeDpsAdminUtils.class.php';
					$adminUtils = new AdobeDpsAdminUtils();
					$customProps = $adminUtils->getCustomProps( $channelId );
					foreach( $customProps as $key => $customProp ) {
						if( array_key_exists($key, $this->lastIssueFields) ) {
							$this->lastIssueFields[$key] = $customProp[0];
						}
					}
				}

				if( $this->isPublishedIssue( $context ) ) {
					$this->editable = false;
				}

				// Insert all our custom props after the Description property
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$key = BizAdmProperty::hasWidget( $showWidgets, 'Description' );
				$dialogWidgets = $this->doCollectDialogWidgets( $entity, 'draw_dialog' );
				
				if( $key !== false ) {
					$showWidgets = array_merge(
						array_slice( $showWidgets, 0, $key+1 ),
						$dialogWidgets,
						array_slice( $showWidgets, $key+1 ) );
				} else { // no Description prop found; Let's add our custom props at the end
					$showWidgets += $dialogWidgets; 
				}
			}
		}
	}

	/**
	 * Check whether an issue published before to DPS
	 *
	 * @param AdminProperties_Context $context
	 * @return bool Return true when published, else false
	 */
	private function isPublishedIssue( AdminProperties_Context $context )
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		$publishTarget = new PubPublishTarget();
		$publishTarget->PubChannelID = $context->getPubChannel()->Id;
		$publishTarget->IssueID = $context->getIssue()->Id;
		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
		$publishedIssue = DBPubPublishedIssues::getPublishedIssue( $publishTarget );

		return $publishedIssue ? true : false;
	}
}
