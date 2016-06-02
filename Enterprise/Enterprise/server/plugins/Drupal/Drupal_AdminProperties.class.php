<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
**/

require_once BASEDIR . '/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php';

class Drupal_AdminProperties extends AdminProperties_EnterpriseConnector
{
	private static $ticket;
	
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * Build a list of custom admin properties to show at the admin Maintenance pages.
	 * This is called to (1) extend the DB model, (2) add widgets to store/travel with
	 * the given entity, (3) draw widgets on the admin Maintenance pages.
	 *
	 * @param string $entity Admin object type: Publication, PubChannel, Issue, Edition, Section
	 * @param AdminProperties_Context|null $context
	 * @param string $mode update_dbmodel, extend_entity or draw_dialog
	 * @return DialogWidget[]
	 */
	private function doCollectDialogWidgets( $entity, $context, $mode )
	{
		$widgets = array();
		if( $entity == 'Issue' ) {			
			require_once BASEDIR . '/config/config_drupal.php';
			require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
			
			// Setting the ticket here because this function is called when the session is still active
			// buildDialogWidgets is not
			$ticket = BizSession::getTicket();
			if(!empty($ticket)) {
				self::$ticket = $ticket;
			}
	
			$contentTypes = array();
			$vocabularies = array();
			$terms = array();
			
			// If the components mapping isn't available in config_drupal.php create a default one
			$requiredComponents = array(
									'title' => 'title', 
									'teaser' => 'teaser', 
									'body' => 'body' 
									);
	
			// If the context is not given don't fill the contentType and vocabularies array
			if( $mode != 'update_dbmodel' ) {
				$this->getDrupalData($contentTypes, $vocabularies, $terms, $requiredComponents, $context);
			}

			$widgets['C_DRUPAL_CONTENT_TYPE'] = new DialogWidget(
				new PropertyInfo('C_DRUPAL_CONTENT_TYPE', BizResources::localize('DRUPAL_CONTENT_TYPE'), null, 'list', '', $contentTypes ),
				new PropertyUsage('C_DRUPAL_CONTENT_TYPE', true, true, false ));
			$widgets['C_DRUPAL_VOCABULARY'] = new DialogWidget(
				new PropertyInfo('C_DRUPAL_VOCABULARY', BizResources::localize('DRUPAL_VOCABULARY'), null, 'multilist', '', $vocabularies ),
				new PropertyUsage('C_DRUPAL_VOCABULARY', true, false, false ));
			$widgets['C_DRUPAL_TERMS'] = new DialogWidget(
				new PropertyInfo('C_DRUPAL_TERMS', BizResources::localize('DRUPAL_TERMS'), null, 'multilist', '', $terms ),
				new PropertyUsage('C_DRUPAL_TERMS', true, false, false ));
			$widgets['C_DRUPAL_REQUIRED_COMPONENTS'] = new DialogWidget(
				new PropertyInfo('C_DRUPAL_REQUIRED_COMPONENTS', BizResources::localize('DRUPAL_REQUIRED_TEXT_COMPONENTS'), null, 'multilist', 'body', $requiredComponents ),
				new PropertyUsage('C_DRUPAL_REQUIRED_COMPONENTS', true, false, false ));
			$widgets['C_DRUPAL_IMG_REQUIRED'] = new DialogWidget(
				new PropertyInfo('C_DRUPAL_IMG_REQUIRED', BizResources::localize('DRUPAL_IMG_REQUIRED'), null, 'bool', '' ),
				new PropertyUsage('C_DRUPAL_IMG_REQUIRED', true, false, false ));
		}

		return $widgets;
	}

	/** 
	 * Collect all possible custom properties for the given entity to extend the DB model. 
	 * See AdminProperties_EnterpriseConnector interface for details.
	 */
	final public function collectDialogWidgets( $entity )
	{
		return $this->doCollectDialogWidgets( $entity, null, 'update_dbmodel' );
	}

	/** 
	 * Collect custom properties for the given context to travel along with the entity instance. 
	 * See AdminProperties_EnterpriseConnector interface for details.
	 */
	public function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action )
	{
		$action = $action; // keep analyzer happy
		$widgets = array();
		if( $entity == 'Issue' ) {
			// Only for Drupal channels, add/show our properties.
			$pubChannel = $context->getPubChannel();
			if( $pubChannel->PublishSystem == 'Drupal' ) {
				$widgets = $this->doCollectDialogWidgets( $entity, $context, 'extend_entity' );
			}
		}
		return $widgets;
	}

	/** 
	 * Add (or adjust) given dialog widgets ($showWidgets) to show admin user for given entity+action.
	 * See AdminProperties_EnterpriseConnector interface for details.
	 */
	final public function buildDialogWidgets( AdminProperties_Context $context, $entity, $action, $allWidgets, &$showWidgets )
	{
		$action = $action; $allWidgets = $allWidgets; // keep analyzer happy
		if( $entity == 'Issue' ) {
			// Only for Drupal channels, add/show our properties.
			$channelObj = $context->getPubChannel();
			if( $channelObj->PublishSystem == 'Drupal' ) {
				// Insert all our custom props after the Description property
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$key = BizAdmProperty::hasWidget( $showWidgets, 'Description' );
				if( $key !== false ) {
					$showWidgets = array_merge(
						array_slice( $showWidgets, 0, $key + 1 ),
						$this->doCollectDialogWidgets( $entity , $context, 'draw_dialog' ),
						array_slice( $showWidgets, $key + 1 ) );
				} else { // no Description prop found; Let's add our custom props at the end
					$showWidgets += $this->doCollectDialogWidgets( $entity, $context, 'draw_dialog' );
				}
			}
		}
	}

	/**
	 * Gets the drupal data for content types, vocabularies, terms and required components from the Drupal_PubPublishing class. 
	 * This data is arranged in a way the widget class for custom properties can show them in the user interface.
	 *
	 * @param array $contentTypes an array for the content types is used as reference
	 * @param array $vocabularies an array for the vocabularies is used as reference
	 * @param array $terms an array for the terms is used as reference
	 * @param array $requiredComponents an array for the required components is used as reference
	 * @param AdminProperties_Context $context
	 */
	public function getDrupalData(&$contentTypes, &$vocabularies, &$terms, &$requiredComponents, $context)
	{
		require_once BASEDIR . '/server/interfaces/services/pub/DataClasses.php';
		require_once dirname(__FILE__) . '/Drupal_PubPublishing.class.php';

		// Create a new publish target with the id's from the given context
		$channelId = $context->getPubChannel()->Id;
		$issueId = $context->getIssue()->Id;
		$target = new PubPublishTarget($channelId, $issueId);

		// Create a new instance of the drupal publishing plugin to get the information
		$drupalPublish = new Drupal_PubPublishing();
		// Set the actual publish target object so the drupal plugin knows which site to contact
		$drupalPublish->setPublishTarget($target);

		// Boolean wether selected terms or vocabularies are changed.
		$changed = false;
		
		// The vocabulary ids are necessary to check if the terms are still valid
		$vids = array();
		
		// Get content type data
		$this->getContentTypesFromDrupal($drupalPublish, $contentTypes);
		$contentTypeSelected = $this->getSelectedData($context, 'contenttype');
		
		// If the selected content type is empty add an empty first one to display
		if(empty($contentTypeSelected)) {
			if(!isset($contentTypes[''])) {
				$contentTypes[''] = '';
			}
		}
		
		// Sort the contenttypes on the key names in alphabetic order
		ksort($contentTypes);
		
		// Get vocabularies data
		$this->getVocabulariesFromDrupal($drupalPublish, $vocabularies, $vids, $contentTypeSelected);
		$vocabulariesSelected = $this->getSelectedData($context, 'vocabularies');
		if(is_null($vocabulariesSelected)) { $vocabulariesSelected = array(); }
		$this->changeVocabulariesSelected($vocabulariesSelected, $vids, $changed, $vocabularies);
		
		// Get terms data
		$this->getTermsFromDrupal($drupalPublish, $terms, $vocabulariesSelected);
		$termsSelected = $this->getSelectedData($context, 'terms');
		if(is_null($termsSelected)) { $termsSelected = array(); }
		$this->changeTermsSelected($termsSelected, $changed, $vids);
		
		// If the information is changed, then save the Issue with the new information.
		// Don't save information if the selected contenttype is empty.
		// ContentType is required.
		if($changed && !empty($contentTypeSelected)) {
			$this->changeIssue($vocabulariesSelected, $termsSelected, $context);
		}
		
		$this->getRequiredComponents($requiredComponents, $drupalPublish);
	}
	
	/**
	 * Get the content types from Drupal and fill the array to display
	 *
	 * @param Drupal_PubPublishing $drupalPublish
	 * @param array $contentTypes
	 */
	private function getContentTypesFromDrupal($drupalPublish, &$contentTypes)
	{
		// Get all the content types from drupal
		$types = $drupalPublish->getContentTypes();
		
		// Fill the contentTypes array so these can be displayed
		foreach ($types as $type) {
			$contentTypes[$type['type']] = $type['name'];
		}
	}
	
	/**
	 * Get the vocabularies from Drupal for the selected content type and fill the array to display
	 *
	 * @param Drupal_PubPublishing $drupalPublish
	 * @param array $vocabularies
	 * @param array $vids
	 * @param string $selectedContentType
	 */
	private function getVocabulariesFromDrupal($drupalPublish, &$vocabularies, &$vids, $selectedContentType)
	{
		if(empty($selectedContentType)) {
			$vocabularies[''] = "Please select a content type and update";
		} else {
			// Get the vocabularies for the specific content type
			$vocs = $drupalPublish->getVocabularies($selectedContentType);

			// Fill the vocabularies array so these can be displayed
			foreach ($vocs as $voc) {
				$vids[$voc['vid']] = $voc['name'];
				$vocabularies[$voc['name']] = $voc['name'];
			}
		}
		
		if(empty($vocabularies)) {
			$vocabularies[''] = 'No vocabularies found for content type';
		}
	}
	
	/**
	 * Get the terms from Drupal for the selected vocabularies and fill the array terms to display
	 *
	 * @param Drupal_PubPublishing $drupalPublish
	 * @param array $terms
	 * @param array $vocabulariesSelected
	 */
	private function getTermsFromDrupal($drupalPublish, &$terms, $vocabulariesSelected)
	{
		if(count($vocabulariesSelected) == 0 || empty($vocabulariesSelected[0])) {
			$terms[''] = "Please select a vocabulary and update";
		} else {
			$drupalTerms = $drupalPublish->getTerms($vocabulariesSelected);
			foreach ($drupalTerms as $vocabulary => $data) {
				if($vocabulary == 'errorDrupal') {
					$terms[''] = $data['error'];
				} else {
					$tmp = array();
					foreach($data as $term) {
						$key = $term['vid'] . '-' . $term['tid'];
						$depth = '';
						for($i = 0; $i < $term['depth']; $i++) {
							$depth .= '-';
						}
						$tmp[$key] = $depth . $term['name'];
					}
					if( !empty($tmp) ) {
						$terms[$vocabulary] = $tmp;
					} else {
						$terms[$vocabulary] = 'No terms found for vocabulary';
					}
				}
			}
		}
	}
	
	/**
	 * Get the current selected terms for the given type
	 *
	 * @param AdminProperties_Context $context
	 * @param string $type - contenttype, vocabularies, terms
	 * @return array or string
	 */
	private function getSelectedData($context, $type)
	{
		$propertyName = null;
		switch($type) {
			case 'vocabularies':
				$propertyName = 'C_DRUPAL_VOCABULARY';
				break;
			case 'terms':
				$propertyName = 'C_DRUPAL_TERMS';
				break;
			case 'contenttype':
			default:
				$propertyName = 'C_DRUPAL_CONTENT_TYPE';
		}
		
		$selected = null;		
		if(isset($_REQUEST['sectionmapping']) && !empty($_REQUEST['sectionmapping'])) {
			$sectionId = intval($_REQUEST['sectionmapping']);
			if(isset($context->getIssue()->SectionMapping)) {
				foreach($context->getIssue()->SectionMapping as $section) {
					if(!is_null($section) && $section->Property == $propertyName) {
						if(isset($section->SectionId) && $section->SectionId == $sectionId) {
							$selected = $section->Values;
						}
					}
				}
			}
		} else {
			foreach($context->getIssue()->ExtraMetaData as $metadata) {
				if(!is_null($metadata) && $metadata->Property == $propertyName) {
					$selected = $metadata->Values;
				}
			}
		}
		
		return ($propertyName == 'C_DRUPAL_CONTENT_TYPE') ? $selected[0] : $selected;
	}
	
	/**
	 * Check the vocabulariesSelected array if the selected vocabularies are still valid.
	 * Change the vocabulariesSelected and vids array according the new data from Drupal.
	 * Set changed to true when one or both arrays change.
	 *
	 * @param array $vocabulariesSelected
	 * @param array $vids
	 * @param boolean $changed
	 * @param array $vocabularies
	 */
	private function changeVocabulariesSelected(&$vocabulariesSelected, &$vids, &$changed, $vocabularies)
	{
		foreach($vocabulariesSelected as $key => $voc) {
			if(!in_array($voc, $vocabularies)) {
				unset($vocabulariesSelected[$key]);
				$changed = true;
			}
		}
		
		// Unset the one in the vocs array to
		foreach($vids as $id => $name) {
			if(!in_array($name, $vocabulariesSelected)) {
				unset($vids[$id]);
				$changed = true;
			}
		}
	}
	
	/**
	 * Check the termsSelected array if the selected terms are still valid.
	 * Change the termsSelected array according the new data from Drupal.
	 * Set changed to true when array is changed.
	 *
	 * @param array $termsSelected
	 * @param boolean $changed
	 * @param array $vids
	 */
	private function changeTermsSelected(&$termsSelected, &$changed, $vids)
	{
		$vids = array_keys($vids);
		foreach($termsSelected as $key => $ter) {
			$data = explode('-', $ter);
			if(!in_array($data[0], $vids)) {
				unset($termsSelected[$key]);
				$changed = true;
			}
		}
	}
	
	/**
	 * Change the metadata of an issue and save this to the database 
	 *
	 * @param array $vocabulariesSelected
	 * @param array $termsSelected
	 * @param AdminProperties_Context $context
	 */
	private function changeIssue($vocabulariesSelected, $termsSelected, $context)
	{
		$issue = $context->getIssue();
		$sectionId = isset($_REQUEST['sectionmapping']) && !empty($_REQUEST['sectionmapping']) ? intval($_REQUEST['sectionmapping']) : 0;
		$property = (isset($_REQUEST['sectionmapping']) && !empty($_REQUEST['sectionmapping'])) ? 'SectionMapping' : 'ExtraMetaData';
		$toChange = $issue->$property;
		foreach($toChange as &$metadata) {
			if($property == 'ExtraMetaData' || (isset($metadata->SectionId) && $metadata->SectionId == $sectionId)) {
				if(!is_null($metadata) && $metadata->Property == "C_DRUPAL_VOCABULARY") {
					$metadata->Values = $vocabulariesSelected;
				}
				if(!is_null($metadata) && $metadata->Property == "C_DRUPAL_TERMS") {
					$metadata->Values = $termsSelected;
				}
			}
		}
		
		if($issue->Id != 0) {
			try {
				require_once BASEDIR . '/server/services/adm/AdmModifyIssuesService.class.php';
				require_once BASEDIR . '/server/interfaces/services/adm/AdmModifyIssuesRequest.class.php';
				require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
				$service = new AdmModifyIssuesService();
				$request = new AdmModifyIssuesRequest( self::$ticket, array(), $context->getPublication()->Id, $context->getPubChannel()->Id, array($issue) );
				$service->execute($request);
			} catch (Exception $e) {
				LogHandler::Log(__CLASS__, 'ERROR', 'Caught exception: ' . $e->getMessage());	
			}
		}
	}
	
	/**
	 * Function to change the required components and add the Enterprise mapping in the string
	 *
	 * @param array $requiredComponents
	 * @param Drupal_PubPublishing $drupalPublish
	 */
	private function getRequiredComponents(&$requiredComponents, $drupalPublish) 
	{
		$options = $drupalPublish->getSiteOptions();
		if(isset($options) && !empty($options['components']['title'])  && !empty($options['components']['teaser'])  && !empty($options['components']['body'])) {
			$requiredComponents['title'] = 'Drupal: title - Enterprise: ' . $options['components']['title'];
			$requiredComponents['teaser'] = 'Drupal: teaser - Enterprise: ' . $options['components']['teaser'];
			$requiredComponents['body'] = 'Drupal: body - Enterprise: ' . $options['components']['body'];
		}
	}

}
