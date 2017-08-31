<?php
/**
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v9.0.0.
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/utils/htmlclasses/EnterpriseWebApp.class.php';

class Facebook_ImportDefinitions_EnterpriseWebApp extends EnterpriseWebApp 
{
	/** 
	 * List of pub channels for which this plugin can publish (with PublishSystem set to
	 * Facebook) and where the admin user has access to.
	 *
	 * @var array $pubChannelInfos List of PubChannelInfo data objects
	 */
	private $pubChannelInfos;
	
	public function getTitle()
		{ return 'Facebook Maintenance'; }

	public function isEmbedded()
		{ return true; }

	public function getAccessType()
		{ return 'admin'; }
	
	/**
	 * Called by the core server. Builds the HTML body of the web application.
	 *
	 * @return string HTML
	 */
	public function getHtmlBody() 
	{
        require_once BASEDIR . '/server/bizclasses/BizAdmPublication.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once dirname(__FILE__) . '/../FacebookPublisher.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/TemplateSection.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';

        $this->pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( 'Facebook' );
        $faceConn = null;

		// Intercept user input.
		$importBtnPressed = isset($_REQUEST['import']);
		$message = '';		
		if( $importBtnPressed ) {
            try {
				$this->importCustomObjectProperties();
				$this->importPublishFormTemplates();
				$this->importPublishFormDialogs();
				$message = 'Import completed!';
			} catch ( BizException $e ) {
				$message = '<font color=red>Import failed:' . $e->getMessage() . '</font>';
			}
		}

		$register = null;
		$channelId = 0;
        if (isset($_REQUEST)) {
            foreach( array_keys($_REQUEST) as $postParam ) {
                $parts = explode( '_', $postParam );
                if( count($parts) == 2 && intval($parts[1]) == $parts[1] && ($parts[0] == 'register' || $parts[0] == 'unregister') ) {
                    $register = ($parts[0] == 'register');
                    $channelId = $parts[1];
                }
            }
		}

		if( $channelId ) {
			if( $register ) {
				$faceConn = new FacebookPublisher($channelId);
				try {
					// Get Facebook access token
					if( !$faceConn->getAccessToken( $channelId ) ) {
                    	// Go to the login page of Facebook to log in and get an access-code
                    	$faceConn->loginToFacebook($channelId);
					}
				} catch( Exception $e ) {
					LogHandler::Log('Facebook','ERROR', ':Error retrieving access token:'. $e->getMessage() );
				}
			} else {
				$faceConn = new FacebookPublisher();
				// Unregister, by releasing the access token.
				$faceConn->releaseAccessToken( $channelId );
			}
		}

		// Build the HTML form.
		$htmlTemplateFile = dirname(__FILE__).'/importdefs.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );
		$htmlBody = str_replace ( '<!--CONTENT-->', $message, $htmlBody );
		$channelRowsObj = new WW_Utils_HtmlClasses_TemplateSection( 'CHANNEL_ROWS' );
		$channelRowsTxt = '';

		if ( $this->pubChannelInfos ) foreach ( $this->pubChannelInfos  as $channelInfo ) {
			require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';
			require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';

			$publId = DBChannel::getPublicationId( $channelInfo->Id );
			$publicationName = DBPublication::getPublicationName( $publId );

			$channelTxt = $channelRowsObj->getSection( $htmlBody );
			// Compose the Channel URL.
			$channelTxt = str_replace ( '<!--PAR:BRANDNAME-->', $publicationName, $channelTxt );
			$channelTxt = str_replace ( '<!--PAR:PUBCHANNELNAME-->', $channelInfo->Name, $channelTxt );
			$channelUrl = SERVERURL_ROOT.INETROOT.'/server/admin/editChannel.php?publid='.$publId.'&channelid=' . $channelInfo->Id;
			$channelTxt = str_replace ( '<!--PAR:PUBCHANNELURL-->', $channelUrl, $channelTxt );

			$registered = null;
			try{
				$faceConn = new FacebookPublisher( $channelInfo->Id );
				$registered = $faceConn->getAccessToken( $channelInfo->Id ) ? true : false;
			} catch( Exception $e ) {
				$channelTxt = str_replace ( '<!--PAR:ERROR_MESSAGE-->', $e->getMessage(), $channelTxt );
			}

			//Check if the user is a valid user and if he has access to the application, if you don't do this you'll get an error by Facebook.
			//If the user has no access the access error is triggered and the user will be unregistered.
			if($registered){
				if($faceConn->checkUser() == null){
					$faceConn->releaseAccessToken($channelInfo->Id);
					$registered = false;

					// Access error
					$error = BizResources::localize('FACEBOOK_ERROR_MESSAGE_NO_ACCESS');
					$channelTxt = str_replace ( '<!--PAR:ERROR_MESSAGE-->', $error, $channelTxt );
				}
			}

			if( $registered ) {
				$btnTitle = 'Unregister';
				$btnId = 'unregister_'.($channelInfo->Id);
				$status = 'Yes';
				$userName = $faceConn->getUserName( $channelInfo->Id );
			} else {
				$btnTitle = 'Register';
				$btnId = 'register_'.($channelInfo->Id);
				$status = 'No';
				$userName = 'not registered';
			}

			$channelTxt = str_replace ( '<!--PAR:REGISTERED-->', $status, $channelTxt );
			$channelTxt = str_replace ( '<!--PAR:FACEBOOKACCOUNT-->', $userName ? formvar($userName) : '&nbsp;', $channelTxt );
			$channelTxt = str_replace ( '<!--PAR:BUTTON-->', '<input type="submit" value="'.$btnTitle.'" id="'.$btnId.'" name="'.$btnId.'" />', $channelTxt );
			$channelRowsTxt .= $channelTxt;
		}

		$htmlBody = $channelRowsObj->replaceSection( $htmlBody, $channelRowsTxt );
		return $htmlBody;
	}
	
	/**
	 * Let the core validate and install the custom properties introduced by our plugin.
	 */
	private function importCustomObjectProperties()
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		$pluginErrs = null;
		BizProperty::validateAndInstallCustomProperties( 'Facebook', $pluginErrs, false );
	}
	
	/**
	 * Imports the Publish Form Dialogs.
	 */
	private function importPublishFormDialogs()
	{
		// Retrieve the Templates from the database, we only need to get this set once since we do not need
		// to store this per channel, but rather based on the unique document id provided by the template.
		if ($this->pubChannelInfos) {
			require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
			foreach ( $this->pubChannelInfos  as $pubChannelInfo ) {
				$resp = $this->queryTemplatesFromDb( $pubChannelInfo->Id );
				BizPublishing::createPublishingDialogsWhenMissing($pubChannelInfo->Id, $resp);
			}
		}
	}
	
	/**
	 * Retrieves the PublishFormTemplate objects provided (hardcoded) by the plugin
	 * and inserts them into the Enterprise DB in case they do not exist yet.
	 */
	private function importPublishFormTemplates()
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		if( $this->pubChannelInfos ){
			foreach( $this->pubChannelInfos as $pubChannelInfo ) {
				$resp = $this->queryTemplatesFromDb( $pubChannelInfo->Id );
				BizPublishing::createPublishingTemplatesWhenMissing( $pubChannelInfo->Id, $resp );
			}
		}
	}

	/**
	 * Queries the Enterprise DB for PublishTemplate objects. For that it uses 
	 * the built-in "PublishFormTemplates" Named Query.
	 */
	private function queryTemplatesFromDb( $pubChannelId )
	{
		require_once BASEDIR . '/server/services/wfl/WflNamedQueryService.class.php';
		
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
