<?php
/**
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';
require_once dirname(__FILE__) . '/../WordPress_Utils.class.php';

class WordPress_WordPressConfig_EnterpriseWebApp extends EnterpriseWebApp
{
	/**
	 * List of pub channels for which this plugin can publish (with PublishSystem set to
	 * WordPress and where the admin user has access to.
	 *
	 * @var array $pubChannelInfos List of PubChannelInfo data objects
	 */
	private $pubChannelInfos;

	public function getTitle()      { return WordPress_Utils::WORDPRESS_PLUGIN_NAME; }
	public function isEmbedded()    { return true; }
	public function getAccessType() { return 'admin'; }
	/**
	 * Called by the core server. Builds the HTML body of the web application.
	 *
	 * @return string HTML
	 */
	public function getHtmlBody()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

		$this->pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( 'WordPress' );
		$wordpressUtils = new WordPress_Utils();
		$importMessage = '';
		$saveMessage = '';

		// Intercept user input.
		if( isset( $_REQUEST['submitImport'] ) ) {
			$canContinue = true;
			try {
				$wordpressUtils->checkAllSiteKeyLengths();
			} catch( BizException $e ) {
				$importMessage = '<p style="color:red">Import failed: ' . $e->getMessage() . '</p>';
				$canContinue = false;
			}
			if( $canContinue ) { // import should only continue if sitekey length is valid
				if( $_REQUEST['import'] == 'importAll' ) {
					$importMessage = $this->importAll();
				} else if( $_REQUEST['import'] == 'importUsers' ) {
					$importMessage = $this->importUsersOnly();
				} else if( $_REQUEST['import'] == 'importTags' ) {
					$importMessage = $this->importTagsOnly();
				}
			}
		} else if( isset($_REQUEST['saveSettings']) ) {
			$saveMessage = $this->saveSettings();
		}

		$suggestionEntity = $wordpressUtils->getEnterpriseSuggestionEntity();

		// Build the HTML form.
		$htmlTemplateFile = dirname(__FILE__).'/importdefs.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );
		$htmlBody = HtmlDocument::replaceConfigKeys( $htmlBody );
		$htmlBody = str_replace ( '<!--IMPORTMESSAGE-->', $importMessage, $htmlBody );
		$htmlBody = str_replace ( '<!--SAVEMESSAGE-->', $saveMessage, $htmlBody );
		$htmlBody = str_replace ( '<!--SUGGESTIONENTITY-->', $suggestionEntity, $htmlBody );

		return $htmlBody;
	}

	/**
	 * Save WordPress settings
	 *
	 * Save the settings for WordPress, at the time of writing this only saves the suggestion entity.
	 *
	 * @return string
	 */
	private function saveSettings()
	{
		$wordpressUtils = new WordPress_Utils();
		try {
			$suggestionEntity = $_REQUEST['suggestionEntity'];
			$wordpressUtils->storeEnterpriseSuggestionEntity( $suggestionEntity );
			$saveMessage = BizResources::localize( 'WORDPRESS_SAVE_COMPLETED' );
		} catch ( BizException $e ) {
			$saveMessage = '<p style="color:red">Save failed: ' . $e->getMessage() . '</p>'; // WORDPRESS_SAVE_COMPLETED
		}
		return $saveMessage;
	}

	/**
	 * Import everything
	 *
	 * Import the WordPress users, tags, custom-metadata and publish forms.
	 *
	 * @return string
	 */
	private function importAll()
	{
		$wordpressUtils = new WordPress_Utils();
		try {
			// Raise the max execution time to ensure that the plugin has enough time to get and save all the data.
			set_time_limit(3600);
			$wordpressUtils->importTags();
			$this->importCustomObjectProperties();
			$this->importPublishFormTemplates();
			$this->importPublishFormDialogs();
			$wordpressUtils->importAllUsers();
			$importMessage = BizResources::localize( 'WORDPRESS_IMPORT_COMPLETED' );
		} catch ( BizException $e ) {
			$importMessage = '<p style="color:red">Import failed: ' . $e->getMessage() . '</p>';
		}
		return $importMessage;
	}

	/**
	 * Import WordPress tags only
	 *
	 * Import the WordPress tags which are used in the publish form, also updates the tags widget.
	 *
	 * @return string
	 */
	private function importTagsOnly()
	{
		$wordpressUtils = new WordPress_Utils();
		try {
			// Raise the max execution time to ensure that the plugin has enough time to get and save all the data.
			set_time_limit(600);
			$wordpressUtils->importTags();
			$wordpressUtils->updateTagsWidget();
			$importMessage = BizResources::localize( 'WORDPRESS_IMPORT_COMPLETED' );
		} catch ( BizException $e ) {
			$importMessage = '<p style="color:red">Import failed: ' . $e->getMessage() . '</p>';
		}
		return $importMessage;
	}

	/**
	 * Import WordPress users only
	 *
	 * Import the WordPress users which are used when publishing to select the author.
	 *
	 * @return string
	 */
	private function importUsersOnly()
	{
		$wordpressUtils = new WordPress_Utils();
		try {
			// Raise the max execution time to ensure that the plugin has enough time to get and save all the data.
			set_time_limit(600);
			$wordpressUtils->importAllUsers();
			$importMessage = BizResources::localize( 'WORDPRESS_IMPORT_COMPLETED' );
		} catch ( BizException $e ) {
			$importMessage = '<p style="color:red">Import failed: ' . $e->getMessage() . '</p>';
		}
		return $importMessage;
	}

	/**
	 * Let the core validate and install the custom properties introduced by our plugin.
	 */
	private function importCustomObjectProperties()
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$errors = array();
		BizProperty::validateAndInstallCustomProperties( WordPress_Utils::WORDPRESS_PLUGIN_NAME, $errors, false );
	}

	/**
	 * Imports the Publish Form Dialogs.
	 */
	private function importPublishFormDialogs()
	{
		// Retrieve the Templates from the database, we only need to get this set once since we do not need
		// to store this per channel, but rather based on the unique document id provided by the template.
		if( $this->pubChannelInfos ) foreach( $this->pubChannelInfos as $pubChannelInfo ) {
			require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
			$resp = $this->queryTemplatesFromDb( $pubChannelInfo->Id );
			BizPublishing::createPublishingDialogsWhenMissing($pubChannelInfo->Id, $resp);
		}
	}

	/**
	 * Import provided objects into the Database.
	 *
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
	 * Query the templates from the Database.
	 *
	 * Queries the Enterprise DB for PublishTemplate objects. For that it uses
	 * the built-in "PublishFormTemplates" Named Query.
	 *
	 * @param $pubChannelId
	 * @return WflNamedQueryResponse
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
