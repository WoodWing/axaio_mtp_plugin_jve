<?php
/**
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';

class MultiChannelPublishingSample_ImportDefinitions_EnterpriseWebApp extends EnterpriseWebApp 
{
	/** 
	 * List of pub channels for which this plugin can publish (with PublishSystem set to
	 * MultiChannelPublishingSample) and where the admin user has access to.
	 *
	 * @var array $pubChannelInfos List of PubChannelInfo data objects
	 */
	private $pubChannelInfos;
	
	public function getTitle()      { return 'Import Definitions'; }
	public function isEmbedded()    { return true; }
	public function getAccessType() { return 'admin'; }
	
	/**
	 * Called by the core server. Builds the HTML body of the web application.
	 *
	 * @return string HTML
	 */
	public function getHtmlBody() 
	{
		// Intercept user input.
		$importBtnPressed = isset($_REQUEST['import']);
		$message = '';		
		if( $importBtnPressed ) {
			try {
				// Raise the max execution time to ensure that the plugin has enough time to get and save all the data.
				set_time_limit(3600);
				$this->importDefinitions();
				$message = 'Import completed!';
			} catch ( BizException $e ) {
				$message = '<font color=red>Import failed:' . $e->getMessage() . '</font>';
			}
		}
		
		// Build the HTML form.
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$htmlTemplateFile = dirname(__FILE__).'/importdefs.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );
		$htmlBody = str_replace ( '<!--CONTENT-->', $message, $htmlBody );
		return $htmlBody;
	}

	/**
	 * Imports the custom properties, templates and dialog of this plugin.
	 */
	public function importDefinitions()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$this->pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( 'MultiChannelPublishingSample' );
		$this->importTermEntitiesAndTerms(); // Make sure to import TermEntities&Terms first, as the DB id is needed to populate the field in the custom props below.
		$this->importCustomObjectProperties();
		$this->importPublishFormTemplates();
		$this->importPublishFormDialogs();
	}

	/**
	 * Imports the Term Entities and Terms from MultiChannelPublishingSample and insert into Enterprise database.
	 */
	private function importTermEntitiesAndTerms()
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermEntitiesService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermsService.class.php';

		$this->clearTermEntitiesAndTerms();

		// Add Term Entities and its Terms file below.
		$cityFile = dirname(__FILE__).'/worldcities.txt';
		$countryFile = dirname(__FILE__).'/worldcountries.txt';
		$termEntitiesAndTerms = array(
		//		                <Term Entity Name> => <Location of the Terms File>
								'City' => $cityFile,
								'Country' => $countryFile,
								// Add the following Term Entity and its Terms here ..
								);

		if( $termEntitiesAndTerms ) foreach( $termEntitiesAndTerms as $termEntityName => $termsFile ) {
			// Creating Term Entity
			$termEntity = new AdmTermEntity();
			$termEntity->Name = $termEntityName;
			$termEntity->AutocompleteProvider = 'MultiChannelPublishingSample';
			$termEntity->PublishSystemId = '';

			// Creating Terms.
			$readText = file_get_contents( $termsFile );
			$terms = explode( chr(0x0A), $readText );

			$service = new AdmCreateAutocompleteTermEntitiesService();
			$request = new AdmCreateAutocompleteTermEntitiesRequest();
			$request->Ticket = BizSession::getTicket();
			$request->TermEntities = array( $termEntity );
			$response = $service->execute( $request );

			if( $terms ) {
				$service = new AdmCreateAutocompleteTermsService();
				$request = new AdmCreateAutocompleteTermsRequest();
				$request->Ticket = BizSession::getTicket();
				$request->TermEntity = $response->TermEntities[0];
				$request->Terms = $terms;
				$service->execute( $request );
			}
			unset($readText);
		}
	}

	/**
	 * To delete a list of Term Entities and Terms belong to MultiChannelPublishingSample provider.
	 */
	private function clearTermEntitiesAndTerms()
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermsService.class.php';

		// Delete the Terms.
		$service = new AdmDeleteAutocompleteTermsService();
		$request = new AdmDeleteAutocompleteTermsRequest();
		$request->Ticket = BizSession::getTicket();

		$termEntities = DBAdmAutocompleteTermEntity::getTermEntityByProvider( 'MultiChannelPublishingSample' );
		if( $termEntities ) foreach( $termEntities as $termEntity ) {
			$terms = array();
			$admTerms = DBAdmAutocompleteTerm::getTermsByTermEntityId( $termEntity->Id );
			if( $admTerms ) foreach( $admTerms as $admTerm ) {
				$terms[] = $admTerm->DisplayName;
			}
			$request->TermEntity = $termEntity;
			$request->Terms = $terms;
			$service->execute( $request );
		}

		// Delete the Term Entities.
		if( $termEntities ) {
			$service = new AdmDeleteAutocompleteTermEntitiesService();
			$request = new AdmDeleteAutocompleteTermEntitiesRequest();
			$request->Ticket = BizSession::getTicket();
			$request->TermEntities = $termEntities;
			$service->execute( $request );
		}
	}
	
	/**
	 * Let the core validate and install the custom properties introduced by our plugin.
	 */
	private function importCustomObjectProperties()
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$pluginErrs = null;
		BizProperty::validateAndInstallCustomProperties( 'MultiChannelPublishingSample', $pluginErrs, false );
	}
	
	/**
	 * Imports the Publish Form Dialogs.
	 */
	private function importPublishFormDialogs()
	{
		// Retrieve the Templates from the database, we only need to get this set once since we do not need
		// to store this per channel, but rather based on the unique document id provided by the template.
		if ($this->pubChannelInfos) {
			require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
			$resp = $this->queryTemplatesFromDb( $this->pubChannelInfos[0]->Id );
			BizPublishing::createPublishingDialogsWhenMissing($this->pubChannelInfos[0]->Id, $resp);
		}
	}
	
	/**
	 * Retrieves the PublishFormTemplate objects provided (hardcoded) by the plugin
	 * and inserts them into the Enterprise DB in case they do not exist yet.
	 */
	private function importPublishFormTemplates()
	{
		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		if( $this->pubChannelInfos ) foreach( $this->pubChannelInfos as $pubChannelInfo ) {
			$resp = $this->queryTemplatesFromDb( $pubChannelInfo->Id );
			BizPublishing::createPublishingTemplatesWhenMissing( $pubChannelInfo->Id, $resp );
		}
	}

	/**
	 * Queries the Enterprise DB for PublishTemplate objects. For that it uses 
	 * the built-in "PublishFormTemplates" Named Query.
	 */
	private function queryTemplatesFromDb( $pubChannelId )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
		
		$service = new WflNamedQueryService();
		$req = new WflNamedQueryRequest();
		$req->Ticket = BizSession::getTicket();
		$req->User   = BizSession::getShortUserName();
		$req->Query  = 'PublishFormTemplates';
		
		$queryParam = new QueryParam();
		$queryParam->Property = 'PubChannelId';
		$queryParam->Operation = '=';
		$queryParam->Value = $pubChannelId;
		$req->Params = array( $queryParam );
		
		$resp = $service->execute( $req );
		
		return $resp;
	}
}
