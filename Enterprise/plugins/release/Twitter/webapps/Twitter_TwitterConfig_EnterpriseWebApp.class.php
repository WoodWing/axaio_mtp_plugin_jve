<?php
/**
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @since v9.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';

class Twitter_TwitterConfig_EnterpriseWebApp extends EnterpriseWebApp
{
	/** 
	 * List of pub channels for which this plugin can publish (with PublishSystem set to
	 * Twitter) and where the admin user has access to.
	 *
	 * @var array $pubChannelInfos List of PubChannelInfo data objects
	 */
	private $pubChannelInfos;
	
	public function getTitle()      { return 'Twitter'; }
	public function isEmbedded()    { return true; }
	public function getAccessType() { return 'admin'; }

	const CONFIG_CHECK_RESPONSIVE = 'twitter_check_responsive';
	
	/**
	 * Called by the core server. Builds the HTML body of the web application.
	 *
	 * @return string HTML
	 */
	public function getHtmlBody() 
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmPublication.class.php';
		require_once dirname(__FILE__) . '/../EnterpriseTwitterConnector.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/TemplateSection.php';

		$this->pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( 'Twitter' );
		$errorMessage = null;
		$twitConn = new EnterpriseTwitterConnector();

		// Intercept user input.
		$importBtnPressed = isset($_REQUEST['import']);
		$message = '';		
		if( $importBtnPressed ) {
			$this->importCustomObjectProperties();
			$this->importPublishFormTemplates();
			$this->importPublishFormDialogs();
			$message = 'Publish Form templates successfully imported.';
		}

		$saveBtnPressed = isset( $_REQUEST['Save'] );
		if( $saveBtnPressed ){
			$check = isset($_REQUEST['check']) ? $_REQUEST['check'] : false;
			if($check){
				$this->storeCheckResponsive( 'checked' );
			} else{
				$this->storeCheckResponsive( 'unchecked' );
			}
		}

		// Handle register/unregister requests of Enterprise issues
		$register = null;
		$issueId = 0;
		if( !empty($_POST) ) foreach( array_keys($_POST) as $postParam ) {
			$parts = explode( '_', $postParam );
			if( count($parts) == 2 && intval($parts[1]) == $parts[1] &&
				($parts[0] == 'register' || $parts[0] == 'unregister') ) {
				$register = ($parts[0] == 'register');
				$issueId = $parts[1];
			}
		}

		if( $issueId ) {
			if( $register ) {
				try {
					// Get Twitter access token
					if( !$twitConn->getAccessToken( $issueId ) ) {
						$twitConn->redirectToRetrieveAccessToken( $issueId ); // Go to login.php
					}
					echo 'Authorized at Twitter. Enterprise is ready to publish through Twitter.';
				} catch( Exception $e ) {
					$errorMessage = $e->getMessage();
					LogHandler::Log('Twitter WebApps Register', 'ERROR', 'Unable to register issue with Twitter', $e);
				}
			} else {
				// Unregister, by releasing the access token.
				$twitConn->releaseAccessToken( $issueId );
			}
		}
		
		// Build the HTML form.
		$htmlTemplateFile = dirname(__FILE__).'/importdefs.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );
		$htmlBody = str_replace ( '<!--CONTENT-->', $message, $htmlBody );

		$issueRowsObj = new WW_Utils_HtmlClasses_TemplateSection( 'ISSUE_ROWS' );
		$issueRowsTxt = '';
		if ( $this->pubChannelInfos ) foreach ( $this->pubChannelInfos  as $channelInfo ) {
			$publicationName = DBPublication::getPublicationName($channelInfo->PublicationId);

			$issuesInChannel = BizAdmPublication::listIssuesObj( '', '', $channelInfo->PublicationId, $channelInfo->Id, null );
			foreach ( $issuesInChannel as $issue ) {
				$issueTxt = $issueRowsObj->getSection( $htmlBody );
				// Compose the Channel URL.
				$issueTxt = str_replace ( '<!--PAR:BRANDNAME-->', $publicationName, $issueTxt );
				$issueTxt = str_replace ( '<!--PAR:PUBCHANNELNAME-->', $channelInfo->Name, $issueTxt );
				$issueTxt = str_replace ( '<!--PAR:ISSUENAME-->', $issue->Name, $issueTxt );
				$issueUrl = SERVERURL_ROOT.INETROOT.'/server/admin/hppublissues.php?id=' . $issue->Id;
				$issueTxt = str_replace ( '<!--PAR:ISSUEURL-->', $issueUrl, $issueTxt );

				$registered = $twitConn->getAccessToken( $issue->Id ) ? true : false;
				if( $registered ) {
					$btnTitle = 'Unregister';
					$btnId = 'unregister_'.$issue->Id;
					$status = 'Yes';
					$userName = $twitConn->getUserName( $issue->Id );
				} else {
					$btnTitle = 'Register';
					$btnId = 'register_'.$issue->Id;
					$status = 'No';
					$userName = 'not registered';
				}

				$issueTxt = str_replace ( '<!--PAR:REGISTERED-->', $status, $issueTxt );
				$issueTxt = str_replace ( '<!--PAR:TWITTERACCOUNT-->', $userName ? formvar($userName) : '&nbsp;', $issueTxt );
				$issueTxt = str_replace ( '<!--PAR:BUTTON-->', '<input type="submit" value="'.$btnTitle.'" id="'.$btnId.'" name="'.$btnId.'" />', $issueTxt );

				$issueRowsTxt .= $issueTxt;
			}
		}

		$check = $this->getCheckResponsive();

		if( !$check ){ // The check is turned on if it's the first time.
			$this->storeCheckResponsive( 'checked' );
			$check = $this->getCheckResponsive();
		}

		$checkbox = null;
		if( strtolower( $check ) == 'checked' ){
			$checkbox = '<input type="checkbox" name="check" value="check" checked="' . $check . '">';
			$htmlBody = str_replace ( '<!--RESPONSIVECHECK-->', $checkbox, $htmlBody );
		} else{
			$checkbox = '<input type="checkbox" name="check" value="check">';
			$htmlBody = str_replace ( '<!--RESPONSIVECHECK-->', $checkbox, $htmlBody );
		}

		if( $errorMessage ){
			$errorTxt = '<a style="color:#FF0000">Error: ' . $errorMessage . '<br/>';
			$errorTxt = $errorTxt . 'Please check the Consumer Key and Consumer Secret.</a>';

			$htmlBody = str_replace ( '<!--ERROR-->', $errorTxt, $htmlBody );
		}

		$htmlBody = $issueRowsObj->replaceSection( $htmlBody, $issueRowsTxt );

		return $htmlBody;
	}

	/**
	 * Stores the Twitter check in the database.
	 *
	 * @param string $check The check to be stored / updated.
	 * @return bool Whether or not the check was successfully stored.
	 */
	public static function storeCheckResponsive( $check )
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( self::CONFIG_CHECK_RESPONSIVE , $check );
	}

	/**
	 * Retrieves the Twitter check from the database
	 *
	 * @return null|string The retrieved check, or null if not set.
	 */
	public static function getCheckResponsive()
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::getValue( self::CONFIG_CHECK_RESPONSIVE );
	}
	
	/**
	 * Let the core validate and install the custom properties introduced by our plugin.
	 */
	private function importCustomObjectProperties()
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$errors = array();
		BizProperty::validateAndInstallCustomProperties( 'Twitter', $errors, true );
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
