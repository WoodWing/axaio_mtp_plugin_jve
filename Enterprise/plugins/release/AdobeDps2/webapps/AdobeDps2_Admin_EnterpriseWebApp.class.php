<?php
/**
 * @since       v9.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 */

require_once dirname(__FILE__).'/../config.php'; // DPS2_PLUGIN_DISPLAYNAME
require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';

class AdobeDps2_Admin_EnterpriseWebApp extends EnterpriseWebApp
{
	/** @var string[] Messages to show end-user. */
	private $infos;
	
	/** @var errors[] Errors to show end-user. */
	private $errors;

	public function getTitle()      { return DPS2_PLUGIN_DISPLAYNAME; }
	public function isEmbedded()    { return true; }
	public function getAccessType() { return 'admin'; }

	/**
	 * Called by the core server. Builds the HTML body of the web application. 
	 * Handles user operations.
	 *
	 * @return string HTML
	 */
	public function getHtmlBody()
	{
		require_once BASEDIR . '/server/utils/htmlclasses/TemplateSection.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
		require_once dirname(__FILE__).'/../config.php'; // DPS2_PLUGIN_DISPLAYNAME
		
		// Init member variables.
		$this->infos = array();
		$this->errors = array();
		$htmlBody = null;
		
		// Detect and dispatch the user operation.
		$command = $this->getUserCommand();
		switch( $command->application ) {
			case 'overview':
				switch( $command->operation ) {
					case 'load_overview':
						$htmlBody = $this->loadOverviewApp();
					break;
					case 'load_register':
						$htmlBody = $this->loadRegistrationApp( true );
					break;
					case 'load_unregister':
						$htmlBody = $this->loadRegistrationApp( false );
					break;
					case 'load_setproject':
						$htmlBody = $this->loadSetProjectApp( $command->params['channelid'], true );
					break;
					case 'load_unsetproject':
						$htmlBody = $this->loadSetProjectApp( $command->params['channelid'], false );
					break;
				}
				break;
			case 'registration':
				switch( $command->operation ) {
					case 'submit_register':
						$htmlBody = $this->registerAtRegistrationApp( 
							$command->params['consumerkey'], $command->params['consumersecret'], 
							$command->params['devicetoken'], $command->params['deviceid'] );
					break;
					case 'submit_unregister':
						$htmlBody = $this->unregisterAtRegistrationApp();
					break;
					case 'submit_cancel':
						$htmlBody = $this->loadOverviewApp();
					break;
				}
			break;
			case 'setproject':
				switch( $command->operation ) {
					case 'submit_setproject':
						$htmlBody = $this->setProjectAtSetProjectApp(
							$command->params['channelid'], $command->params['projectrefid'] );
					break;
					case 'submit_unsetproject':
						$htmlBody = $this->unsetProjectAtSetProjectApp( $command->params['channelid'] );
					break;
					case 'submit_cancel':
						$htmlBody = $this->loadOverviewApp();
					break;
				}
			break;
		}
		if( is_null($htmlBody) ) { // should never happen
			$htmlBody = $this->loadOverviewApp();
			$this->errors[] = 'Unrecognized operation '.$command->operation.' for application '.$command->application.'.';
		}
		
		// Show the notices and errors.
		$messages = '';
		if( $this->errors ) foreach( $this->errors as $error ) {
			$messages .= $this->markupMsg( $error, true );
		}
		if( $this->infos ) foreach( $this->infos as $info ) {
			$messages .= $this->markupMsg( $info, false );
		}
		$htmlBody = str_replace( '<!--VAR:MESSAGES-->', $messages, $htmlBody );
		$uploadTitle = BizResources::localize( 'AdobeDps2.UPLOADING_AP_ARTICLES', true, array( DPS2_PLUGIN_DISPLAYNAME ) );
		$htmlBody = str_replace( '<!--VAR:UPLOADING_AP_ARTICLES-->', $uploadTitle, $htmlBody );
		return $htmlBody;
	}
	
	/**
	 * Detects on which application the user is working and detects the user operation 
	 * performed on the application.
	 *
	 * @return object The command.
	 */
	private function getUserCommand()
	{
		// If not operation detected, fall back to loading the overview app.
		$command = new stdClass();
		$command->application = 'overview';
		$command->operation = 'load_overview';
		$command->params = array();
		
		if( isset($_POST) ) {
			$cancel = BizResources::localize('ACT_CANCEL');
			foreach( array_keys($_POST) as $postParam ) {
				switch( $postParam ) {
					case 'submit_register':
						$command->application = 'registration';
						$command->operation = ($_POST[$postParam] == $cancel) ? 'submit_cancel' : 'submit_register';
						$command->params['consumerkey'] = $_POST['consumerkey'];
						$command->params['consumersecret'] = $_POST['consumersecret'];
						$command->params['devicetoken'] = $_POST['devicetoken'];
						$command->params['deviceid'] = $_POST['deviceid'];
						break;
					case 'submit_unregister':
						$command->application = 'registration';
						$command->operation = ($_POST[$postParam] == $cancel) ? 'submit_cancel' : 'submit_unregister';
						break;
					case 'submit_setproject':
						$command->application = 'setproject';
						$command->operation = ($_POST[$postParam] == $cancel) ? 'submit_cancel' : 'submit_setproject';
						$command->params['channelid'] = $_POST['channelid'];
						$command->params['projectrefid'] = $_POST['projectrefid'];
						break;
					case 'submit_unsetproject':
						$command->application = 'setproject';
						$command->operation = ($_POST[$postParam] == $cancel) ? 'submit_cancel' : 'submit_unsetproject';
						$command->params['channelid'] = $_POST['channelid'];
						break;
					case 'load_register':
						$command->application = 'overview';
						$command->operation = 'load_register';
						break;
					case 'load_unregister':
						$command->application = 'overview';
						$command->operation = 'load_unregister';
						break;
					default: // 'load_setproject_<id>' or 'load_unsetproject_<id>'
						// Did user click on the setproject/unsetproject buttons on the overview app?
						$parts = explode( '_', $postParam );
						if( count($parts) == 3 && 
							$parts[0] == 'load' && 
							($parts[1] == 'setproject' || $parts[1] == 'unsetproject') &&
							intval($parts[2]) == $parts[2] ) {
							$command->operation = $parts[0].'_'.$parts[1]; // 'load_setproject' or 'load_unsetproject'
							$command->params['channelid'] = $parts[2];
						}
						break;
				}
			}
		}
		return $command;
	}
	
	/**
	 * Composes the Registration or Unregistration application.
	 *
	 * Registration app allows the user to enter (or change) the device token/id.
	 * and to continue the registration procedure.
	 * Unregistration app allows user to clear the device token/id.
	 *
	 * @param boolean $register TRUE to register, FALSE to unregister.
	 * @return string HTML body
	 */
	private function loadRegistrationApp( $register )
	{
		// Load HTML template.
		if( $register ) {
			$htmlTemplateFile = dirname(__FILE__).'/register_app.htm';
		} else {
			$htmlTemplateFile = dirname(__FILE__).'/unregister_app.htm';
		}
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );
		
		// Fill in the Device Token/Id in case user has registered before.
		require_once dirname(dirname(__FILE__)).'/bizclasses/Authorization.class.php';
		$bizAuth = new AdobeDps2_BizClasses_Authorization();
		$deviceToken = $bizAuth->getDeviceToken();
		$deviceId = $bizAuth->getDeviceId();
		$htmlBody = str_replace( '<!--VAR:DEVICE_TOKEN-->', $deviceToken, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:DEVICE_ID-->', $deviceId, $htmlBody );

		require_once dirname(dirname(__FILE__)).'/bizclasses/Config.class.php';
		$consumerKey = AdobeDps2_BizClasses_Config::getConsumerKey();
		$consumerSecret = AdobeDps2_BizClasses_Config::getConsumerSecret();
		$htmlBody = str_replace( '<!--VAR:CONSUMER_KEY-->', $consumerKey, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:CONSUMER_SECRET-->', $consumerSecret, $htmlBody );
		
		// Fill in hidden form parameters (to be round-tripped).
		$hiddenParams = '';
		$htmlBody = str_replace( '<!--VAR:REGISTERAPP_HIDDENPARAMS-->', $hiddenParams, $htmlBody );

		return $htmlBody;
	}

	/**
	 * Takes the Consumer Key, Consumer Secret, Device Token and Device Id that was filled in 
	 * by admin user and attempts to obtain a Access Token from Adobe DPS.
	 * The given parameters are saved in DB / FileStore and the Overview application is loaded.
	 *
	 * @param string $consumerKey
	 * @param string $consumerSecret
	 * @param string $deviceToken
	 * @param string $deviceId
	 * @return string HTML body
	 */
	private function registerAtRegistrationApp( $consumerKey, $consumerSecret, $deviceToken, $deviceId )
	{
		try {
			// Remove spaces from begin/end of user typed fields.
			$consumerKey = trim($consumerKey);
			$consumerSecret = trim($consumerSecret);
			$deviceToken = trim($deviceToken);
			$deviceId = trim($deviceId);

			// Save the Consumer Key and Secret into the smart_config table.
			require_once dirname(dirname(__FILE__)).'/bizclasses/Config.class.php';
			$savedKey = AdobeDps2_BizClasses_Config::saveConsumerKey( $consumerKey );
			$savedSecret = AdobeDps2_BizClasses_Config::saveConsumerSecret( $consumerSecret );

			// Save the Device Token and Device Id in the filestore.
			require_once dirname(dirname(__FILE__)).'/bizclasses/Authorization.class.php';
			$bizAuth = new AdobeDps2_BizClasses_Authorization();
			$savedToken = $bizAuth->saveDeviceToken( $deviceToken );
			$savedId = $bizAuth->saveDeviceId( $deviceId );
			
			// Validate the save operations. (Should never happen, so error is in English only.)
			if( !$savedKey ) {
				throw new Exception( 'Failed to save the Consumer Key.' );  
			}
			if( !$savedSecret ) {
				throw new Exception( 'Failed to save the Consumer Secret.' );
			}
			if( !$savedToken ) {
				throw new Exception( 'Failed to save the Device Token.' );
			}
			if( !$savedId ) {
				throw new Exception( 'Failed to save the Device Id.' );
			}
			
			// Check if user has filled in all mandatory fields.
			if( empty($consumerKey) || empty($consumerSecret) || empty($deviceToken) || empty($deviceId) ) {
				throw new Exception( BizResources::localize( 'ERR_MANDATORYFIELDS' ) );
			}
			
			// Compose a HTTP client.
			require_once dirname(dirname(__FILE__)).'/utils/HttpClient.class.php';
			$httpClient = new AdobeDps2_Utils_HttpClient( 
				AdobeDps2_BizClasses_Config::getAuthenticationUrl(),
				AdobeDps2_BizClasses_Config::getAuthorizationUrl(),
				AdobeDps2_BizClasses_Config::getProducerUrl(),
				AdobeDps2_BizClasses_Config::getIngestionUrl(),
				$consumerKey, $consumerSecret
			);
			
			// Request Adobe DPS authentication server for a new Access Token 
			// and an update of our Device Token and Device Id.
			$httpClient->getToken( $deviceToken, $deviceId );

			// Navigate back to the overview app.
			$htmlBody = $this->loadOverviewApp();
			
		} catch( Exception $e ) {
			$this->errors[] = $e->getMessage();
			$htmlBody = $this->loadRegistrationApp( true );
		}
		return $htmlBody;
	}
			
	/**
	 * Handles the unregistration operation, then composes the Overview application.
	 * When unregistration fails, the Unregistration application is composed instead.
	 *
	 * @return string HTML body
	 */
	private function unregisterAtRegistrationApp()
	{
		require_once dirname(dirname(__FILE__)).'/bizclasses/Authorization.class.php';
		$bizAuth = new AdobeDps2_BizClasses_Authorization();
		if( $bizAuth->saveDeviceToken( '' ) && $bizAuth->saveDeviceId( '' ) ) {
			$this->infos[] = BizResources::localize('AdobeDps2.SUCCESSFULLY_UNREGISTERED');
			$htmlBody = $this->loadOverviewApp();
		} else { // Should never happen. (So English only.)
			$this->errors[] = 'Failed to unregister.';
			$htmlBody = $this->loadRegistrationApp( false );
		}
		return $htmlBody;
	}
	
	/**
	 * ...
	 *
	 * @param integer $pubChannelId
	 * @param boolean $setProject
	 * @return string HTML body
	 */
	private function loadSetProjectApp( $pubChannelId, $setProject )
	{
		// Load HTML template.
		if( $setProject ) {
			$htmlTemplateFile = dirname(__FILE__).'/setproject_app.htm';
		} else {
			$htmlTemplateFile = dirname(__FILE__).'/unsetproject_app.htm';
		}
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );

		try {
			// Resolve the AdmPubChannel from id.
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
			$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );
			$pubChannel = DBAdmPubChannel::getPubChannelObj( $pubChannelId, $typeMap );

			// Compose a HTTP client.
			require_once dirname(dirname(__FILE__)).'/utils/HttpClient.class.php';
			require_once dirname(dirname(__FILE__)).'/bizclasses/Config.class.php';
			$httpClient = new AdobeDps2_Utils_HttpClient( 
				AdobeDps2_BizClasses_Config::getAuthenticationUrl(),
				AdobeDps2_BizClasses_Config::getAuthorizationUrl(),
				AdobeDps2_BizClasses_Config::getProducerUrl(),
				AdobeDps2_BizClasses_Config::getIngestionUrl(),
				AdobeDps2_BizClasses_Config::getConsumerKey(),
				AdobeDps2_BizClasses_Config::getConsumerSecret()
			);

			// Request Adobe DPS authentication server for a new Access Token 
			// and an update of our Device Token and Device Id.
			require_once dirname(dirname(__FILE__)).'/bizclasses/Authorization.class.php';
			$bizAuth = new AdobeDps2_BizClasses_Authorization();
			$deviceToken = $bizAuth->getDeviceToken();
			$deviceId = $bizAuth->getDeviceId();
			$httpClient->getToken( $deviceToken, $deviceId );
			
			// Get the Projects from AP for which the user has access.
			$projects = $httpClient->getPermissions();
			if( $projects ) {

				// Compose a list of all projects that can be transformed into a combo.
				// We compose a combined key, which holds the project reference and id.
				// That way, we can later decompose this since we need to store both.
				// For the values, we take the project title, which is known to users.
				$projectsMap = array();
				foreach( $projects as $project ) {
					$projectsMap[$project->name.'|'.$project->id] = $project->title;
				}

				// Sort by AP Project Title.
				asort( $projectsMap );
				
				// Check if the user has add/edit content access rights for the Adobe DPS publication (project).
				require_once dirname(dirname(__FILE__)).'/bizclasses/Config.class.php';
				$projectRef = AdobeDps2_BizClasses_Config::getProjectRef( $pubChannel );
				$projectId = AdobeDps2_BizClasses_Config::getProjectId( $pubChannel );
				
				// Compose a combo of AP publications, pre-select $projectRef.
				$combo = inputvar( 'projectrefid', $projectRef.'|'.$projectId, 'combo', $projectsMap );
				$htmlBody = str_replace ( '<!--VAR:AP_PUBLICATIONS_COMBOBOX-->', $combo, $htmlBody );

				// Resolve the Publication Channel name.
				$htmlBody = str_replace( '<!--VAR:CHANNEL_NAME-->', $pubChannel->Name, $htmlBody );
		
				// Fill in hidden form parameters (to be round-tripped).
				$hiddenParams = inputvar( 'channelid', $pubChannelId, 'hidden' );
				$htmlBody = str_replace( '<!--VAR:SETPROJECTAPP_HIDDENPARAMS-->', $hiddenParams, $htmlBody );
				
			} else {
				throw new Exception( BizResources::localize('AdobeDps2.ERROR_NO_EDIT_CONTENT_RIGHTS') );
			}
		} catch( Exception $e ) {
			$this->errors[] = $e->getMessage();
			$htmlBody = $this->loadRegistrationApp( true );
		}
		
		return $htmlBody;
	}
	
	/**
	 * Takes the AP publication (step2) and stores it in the custom props of the PubChannel.
	 * Then it shows the Overview application.
	 *
	 * @param integer $pubChannelId
	 * @param string $projectRefId Combined field with separator: Project Ref | Project Id
	 * @return string HTML body
	 */
	private function setProjectAtSetProjectApp( $pubChannelId, $projectRefId )
	{
		// Error when no project selected and show step1 again.
		if( !$projectRefId ) {
			$this->errors[] = BizResources::localize( 'ERR_MANDATORYFIELDS' );
			return $this->loadSetProjectApp( $pubChannelId, true );
		}
		
		// Parse the combined field.
		list( $projectRef, $projectId ) = explode( '|', $projectRefId );
		
		// Save the AP Project reference in the custom props of the PubChannel.
		require_once dirname(dirname(__FILE__)).'/bizclasses/Config.class.php';
		if( !AdobeDps2_BizClasses_Config::setProject( $pubChannelId, $projectRef, $projectId ) ) {
			// Should never happen. (So error is in English only.)
			$this->errors[] = 'Failed to save the Project reference for Publication Channel (id='.$pubChannelId.').'; 
		}
	
		// Show the Overview application.
		return $this->loadOverviewApp();
	}
	
	/**
	 * Clears the Project property of a given Publication Channel.
	 *
	 * @param integer $pubChannelId
	 * @return string HTML body
	 */
	private function unsetProjectAtSetProjectApp( $pubChannelId )
	{
		// Clear the AP Project reference in the custom props of the PubChannel.
		require_once dirname(dirname(__FILE__)).'/bizclasses/Config.class.php';
		if( !AdobeDps2_BizClasses_Config::setProject( $pubChannelId, '', '' ) ) {
			// Should never happen. (So error is in English only.)
			$this->errors[] = 'Failed to save the Project reference for Publication Channel (id='.$pubChannelId.').'; 
		}
	
		// Show the Overview application.
		return $this->loadOverviewApp();
	}
	
	/**
	 * Composes the Overview application.
	 *
	 * @return string HTML body
	 */
	private function loadOverviewApp()
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmPublication.class.php';
		
		// Collect all 'dps2' channels and error when none found.
		$this->pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( 'AdobeDps2' );
		if( count($this->pubChannelInfos) == 0 ) {
			$this->errors[] = BizResources::localize('AdobeDps2.ERROR_NO_CHANNELS').
				' '.BizResources::localize('AdobeDps2.ERROR_NO_CHANNELS_HELP');
		}

		// Build the HTML form.
		$htmlTemplateFile = dirname(__FILE__).'/admin_app.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );
		$htmlBody = str_replace ( '<!--VAR:TICKET-->', BizSession::getTicket(), $htmlBody );
		$channelRowsObj = new WW_Utils_HtmlClasses_TemplateSection( 'CHANNEL_ROWS' );
		$channelRowsTxt = '';

		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		require_once dirname(dirname(__FILE__)).'/bizclasses/Config.class.php';
		require_once dirname(dirname(__FILE__)).'/bizclasses/Authorization.class.php';

		$bizAuth = new AdobeDps2_BizClasses_Authorization();
		$hasDeviceTokenAndId = $bizAuth->hasDeviceToken() && $bizAuth->hasDeviceId();

		if( $this->pubChannelInfos ) foreach ( $this->pubChannelInfos as $channelInfo ) {
			
			// Resolve the Project.
			$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );
			$pubChannel = DBAdmPubChannel::getPubChannelObj( $channelInfo->Id, $typeMap );
			$projectRef = AdobeDps2_BizClasses_Config::getProjectRef( $pubChannel );
			
			// Resolve brand id/name.
			require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';
			require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
			$publId = DBChannel::getPublicationId( $channelInfo->Id );
			$publicationName = DBPublication::getPublicationName( $publId );
			
			// Compose pub channel row for HTML table.
			$channelTxt = $channelRowsObj->getSection( $htmlBody );
			$channelTxt = str_replace ( '<!--PAR:BRANDNAME-->', $publicationName, $channelTxt );
			$channelTxt = str_replace ( '<!--PAR:PUBCHANNELNAME-->', $channelInfo->Name, $channelTxt );
			$channelUrl = SERVERURL_ROOT.INETROOT.'/server/admin/editChannel.php?publid='.$publId.'&channelid=' . $channelInfo->Id;
			$channelTxt = str_replace ( '<!--PAR:PUBCHANNELURL-->', $channelUrl, $channelTxt );

			if( $hasDeviceTokenAndId ) {
				if( $projectRef ) {
					$btnTitle = BizResources::localize('AdobeDps2.UNSETPROJECT');
					$btnId = 'load_unsetproject_'.($channelInfo->Id);
				} else {
					$btnTitle = BizResources::localize('AdobeDps2.SETPROJECT');
					$btnId = 'load_setproject_'.($channelInfo->Id);
				}
				$button = '<input type="submit" value="'.formvar($btnTitle).'" id="'.$btnId.'" name="'.$btnId.'" />';
			} else {
				$button = ''; // hide button (need to register first)
			}
			
			$channelTxt = str_replace ( '<!--PAR:PUBCHANNEL_PROJECT-->', $projectRef, $channelTxt );
			$channelTxt = str_replace ( '<!--PAR:PUBCHANNEL_SETPROJECT_BUTTON-->', $button, $channelTxt );
			$channelRowsTxt .= $channelTxt;
		}
		$htmlBody = $channelRowsObj->replaceSection( $htmlBody, $channelRowsTxt );
		
		// Show the Register button.
		if( $hasDeviceTokenAndId ) {
			$btnTitle = BizResources::localize('ACT_UNREGISTER');
			$btnId = 'load_unregister';
		} else {
			$btnTitle = BizResources::localize('ACT_REGISTER');
			$btnId = 'load_register';
		}
		$button = '<input type="submit" value="'.formvar($btnTitle).'" name="'.$btnId.'" />';
		$htmlBody = str_replace ( '<!--PAR:PUBCHANNEL_REGISTER_BUTTON-->', $button, $htmlBody );

		return $htmlBody;
	}

	/**
	 * Composes a HTML fragment to display an info text or error message.
	 *
	 * @param string $msg Message to display
	 * @param bool $isError TRUE when error (red), FALSE when info text (blue)
	 * @return string HTML fragment that contains the text (marked up)
	 */
	private function markupMsg( $msg, $isError )
	{
		if( $msg ) {
			if( $isError ) {
				 $msg = '<p style="color:#ff0000">'.$msg.'</p>';
			} else {
				 $msg = '<p style="color:#01a701">'.$msg.'</p>';
			}
		}
		return $msg;
	 }
}
