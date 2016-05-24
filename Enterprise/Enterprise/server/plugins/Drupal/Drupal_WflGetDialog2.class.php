<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * Overrule the getDialog
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetDialog2_EnterpriseConnector.class.php';

class Drupal_WflGetDialog2 extends WflGetDialog2_EnterpriseConnector
{	
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_AFTER; }
	
	final public function runBefore( WflGetDialog2Request &$req ) // Not called, we do an run after
	{
		$req = $req; // keep analyzer happy
	}
	
	final public function runOverruled( WflGetDialog2Request $req ) // Not called, we do an run after
	{
		$req = $req; // keep analyzer happy
	}
	
	final public function runAfter( WflGetDialog2Request $req, WflGetDialog2Response &$resp ) 
	{
		$types = array("PublishDossier", "UnPublishDossier", "UpdateDossier");
		$action = $req->Action;
		$issueId = $req->MetaData['Issue']->PropertyValues[0]->Value;
		
		// Check if the issue has Drupal as publishsystem and is a type we can handle
		if( !empty($issueId) && in_array($action, $types) ) {
			$classChucks = explode( '_', __CLASS__ );
			$intName = array_shift( $classChucks );
			
			require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
			$channel = BizPublication::getChannelForIssue($issueId);
			$channelRow = DBChannel::getChannel($channel->Id);
			
			if($channelRow['publishsystem'] == $intName) {
				$tab = null;
				foreach($resp->Dialog->Tabs as &$tab) {
					if($tab->Title != "ERRORS") {
						break;
					}
				}
				
				$widgets = &$tab->Widgets;
				$metadata = &$resp->Dialog->MetaData;
								
				if($action == 'PublishDossier' || $action == 'UpdateDossier') {
					$this->getPublishAndUpdateWidgets($widgets, $tab->DefaultFocus);	
				} else if($action == 'UnPublishDossier') {
					$this->getUnPublishWidgets($widgets, $tab->DefaultFocus);
				}
				
				// Get the names of the widgets
				$keys = array_keys($widgets);
				
				$values = array();
				if($action == 'PublishDossier' || $action == 'UpdateDossier') {
					$id = $req->MetaData['ID']->PropertyValues[0]->Value;
					$this->getValuesFromDrupal($id, $channel->Id, $issueId, $values, $keys);
				}
				
				foreach( $keys as $name ) {
					// If the key is not an error, warning or info message create a new MetaDataValue
					if(!preg_match('/^ERRORS/', $name) && !preg_match('/^WARNINGS/', $name) && !preg_match('/^INFOS/', $name)) {					
						$value = (array_key_exists($name, $values)) ? $values[$name] : '';

						if(array_key_exists($name, $values) && is_bool($values[$name])) {
							$value = $values[$name] ? '1' : '0';
						}
						$metadata[$name] = new MetaDataValue($name, null, array( new PropertyValue( $value, '' )));
					}
				}				
			}
		}
	} 
	
	/**
	 * Get the values for the dossier (and issue) from Drupal. This means for an publish event that the default values
	 * for a drupal content type are fetched. For an update the current set values are fetched. 
	 *
	 * @param integer $dossierId
	 * @param integer $channelId
	 * @param integer $issueId
	 * @param array $values
	 * @param array $keys
	 */
	public function getValuesFromDrupal($dossierId, $channelId, $issueId, &$values, $keys)
	{
		require_once BASEDIR . '/server/interfaces/services/pub/DataClasses.php';
		require_once dirname(__FILE__) . '/Drupal_PubPublishing.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$nodeId = $this->getExternalId($dossierId, $channelId, $issueId);
		
		// Create a new publish target with the id's from the given context
		$target = new PubPublishTarget($channelId, $issueId);

		// Create a new instance of the drupal publishing plugin to get the information
		$drupalPublish = new Drupal_PubPublishing();
		// Set the actual publish target object so the drupal plugin knows which site to contact
		$drupalPublish->setPublishTarget($target);
		$dossier = BizObject::getObject($dossierId, BizSession::getShortUserName(), false, 'none', null, null, false);
		$drupalPublish->setPublishDossier($dossier);			
		$values = $drupalPublish->getCurrentDataForGetDialog($nodeId, $keys);
		
		// Get the options for the translations
		$options = self::getOptions(); 
		
		// Resolve the translations to the internal names for the Drupal plugin.
		foreach($values as $key => &$value) {
			if(array_key_exists($key, $options)) {
				$listValues = $options[$key]['values'];
				foreach($listValues as $listKey => $listValue) {
					if($listKey == $value) {
						$value = $listValue;
						break;
					}
				}
			}
		}
	}
	
	/**
	 * Get the external id for a specific issue and dossier
	 *
	 * @param integer $dossierId
	 * @param integer $channelId
	 * @param integer $issueId
	 * @return string
	 */
	public function getExternalId($dossierId, $channelId, $issueId)
	{
		require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
		return DBTarget::getDossierExternalId($dossierId, $channelId, $issueId);
	}
	
	/**
	 * Create the widgets for the Publish and Update action and set these into the widgets variable. Also set the default focus to one of the widgets. 
	 *
	 * @param array $widgets
	 * @param string $defaultFocus
	 */
	public function getPublishAndUpdateWidgets(&$widgets, &$defaultFocus)
	{		
		$widgets['C_DIALOG_DRUPAL_PROMOTE'] = new DialogWidget(
			new PropertyInfo('C_DIALOG_DRUPAL_PROMOTE', BizResources::localize('DRUPAL_PROMOTE'), null, 'bool', '' ),
			new PropertyUsage('C_DIALOG_DRUPAL_PROMOTE', true, false, false, false ));
			
		$widgets['C_DIALOG_DRUPAL_STICKY'] = new DialogWidget(
			new PropertyInfo('C_DIALOG_DRUPAL_STICKY', BizResources::localize('DRUPAL_STICKY'), null, 'bool', '' ),
			new PropertyUsage('C_DIALOG_DRUPAL_STICKY', true, false, false, false ));
			
		/* TODO: Implement this new functionlity
		$widgets['C_DIALOG_DRUPAL_PUBLISH_FROM'] = new DialogWidget(
			new PropertyInfo('C_DIALOG_DRUPAL_PUBLISH_FROM', 'Publish from', null, 'datetime', '' ),
			new PropertyUsage('C_DIALOG_DRUPAL_PUBLISH_FROM', true, false, false ));
			
		$widgets['C_DIALOG_DRUPAL_PUBLISH_TILL'] = new DialogWidget(
			new PropertyInfo('C_DIALOG_DRUPAL_PUBLISH_TILL', 'Publish till', null, 'datetime', '' ),
			new PropertyUsage('C_DIALOG_DRUPAL_PUBLISH_TILL', true, false, false ));
		*/
			
		// Get the options so these are translated into the correct language
		$options = self::getOptions();	
		
		// IMPORTANT: Use the listOptions array to handle translations
		$widgets['C_DIALOG_DRUPAL_COMMENTS'] = new DialogWidget(
			new PropertyInfo('C_DIALOG_DRUPAL_COMMENTS', BizResources::localize('DRUPAL_COMMENTS'), null, 'list', $options['C_DIALOG_DRUPAL_COMMENTS']['default'], $options['C_DIALOG_DRUPAL_COMMENTS']['values'] ),
			new PropertyUsage('C_DIALOG_DRUPAL_COMMENTS', true, false, false, false ));
					
		$defaultFocus = 'C_DIALOG_DRUPAL_PROMOTE';				
	}
	
	/**
	 * Create the widgets for the UnPublish action and set these into the widgets variable. Also set the default focus to one of the widgets. 
	 *
	 * @param array $widgets
	 * @param string $defaultFocus
	 */
	public function getUnPublishWidgets(&$widgets, &$defaultFocus)
	{
		// Get the options so these are translated into the correct language
		$options = self::getOptions();
		
		// IMPORTANT: Use the listOptions array to handle translations
		$widgets['C_DIALOG_DRUPAL_UNPUBLISH_NODE'] = new DialogWidget(
			new PropertyInfo('C_DIALOG_DRUPAL_UNPUBLISH_NODE', BizResources::localize('DRUPAL_UNPUBLISH_NODE'), null, 'list', $options['C_DIALOG_DRUPAL_UNPUBLISH_NODE']['default'], $options['C_DIALOG_DRUPAL_UNPUBLISH_NODE']['values'] ),
			new PropertyUsage('C_DIALOG_DRUPAL_UNPUBLISH_NODE', true, false, false, false ));
			
		$defaultFocus = 'C_DIALOG_DRUPAL_UNPUBLISH_COMMENTS';
	}
	
	/**
	 * Get the options for list widgets. This way options can be translated and be used in the Drupal plugin.
	 *
	 * @return array
	 */
	public static function getOptions()
	{
		/**
		 * Translations for the options.
		 * Create an array with translations as:
		 * 	$array[INTERNAL_NAME] = TRANSLATION;
		 * Where:
		 * 	INTERNAL_NAME = The internal name of the option. Used in the Drupal_PubPublishing plugin
		 * 	TRANSLATION = The actual translation (can be a string or be translation by BizResources)
		 */
		// Comments widget
		$commentsValues['disable'] = BizResources::localize('DRUPAL_COMMENTS_DISABLE');
		$commentsValues['read'] = BizResources::localize('DRUPAL_COMMENTS_READ_ONLY');
		$commentsValues['read/write'] = BizResources::localize('DRUPAL_COMMENTS_READ_WRITE');	
			
		// Unpublish widget
		$unpublishValues['unpublish'] = BizResources::localize('DRUPAL_UNPUBLISH');
		$unpublishValues['remove'] = BizResources::localize('DRUPAL_REMOVE');		
		
		/**
		 * Set the actual options as:
		 * 	$listOptions['WIDGET_NAME'] = array('values' => TRANSLATIONS, default => DEFAULT_OPTION);
		 * Where:
		 * 	WIDGET_NAME = the name of the widget (e.g. C_DIALOG_DRUPAL_UNPUBLISH_NODE)
		 * 	TRANSLATIONS = array with translations created above
		 * 	DEFAULT_OPTION = the name of the default option. Needs to be in the translations array.
		 */
		$listOptions['C_DIALOG_DRUPAL_COMMENTS'] 		= array('values' => $commentsValues, 	'default' => $commentsValues['disable']);
		$listOptions['C_DIALOG_DRUPAL_UNPUBLISH_NODE'] 	= array('values' => $unpublishValues, 	'default' => $unpublishValues['unpublish']);
		
		return $listOptions;
	}	
}