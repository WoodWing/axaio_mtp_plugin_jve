<?php
/**
 * @package     Enterprise
 * @subpackage  Analytics
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 */

require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';
require_once dirname(__FILE__).'/../BizAnaIssue.class.php';
require_once dirname( dirname(__FILE__) ) . '/Analytics_Utils.class.php';

class Analytics_IssueExportDefinitions_EnterpriseWebApp extends EnterpriseWebApp
{
	public function getTitle()      { return 'Enterprise Analytics'; }
	public function isEmbedded()    { return true; }
	public function getAccessType() { return 'admin'; }

	/**
	 * Called by the core server. Builds the HTML body of the web application.
	 *
	 * @return string HTML
	 */
	public function getHtmlBody()
	{
		$errors = array( 'register' => '', 'settings' => '', 'export' => '' );
		$infos = array( 'register' => '', 'settings' => '', 'export' => '' );
		$exportStatus = '';

		// Interpret user input.
		$registerBtnPressed = isset( $_REQUEST['register'] );
		$unregisterBtnPressed = isset( $_REQUEST['unregister'] );
		$saveSettingsBtnPressed = isset( $_REQUEST['saveSettings'] );
		$exportBtnPressed = isset( $_REQUEST['export'] );

		$serverUrl = isset( $_REQUEST['serverUrl'] ) ? trim( $_REQUEST['serverUrl'] ) : null;
		$analyticsSendUserNames = isset( $_REQUEST['sendUserNames'] ) ? true : false;

		// Build the HTML form.
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$htmlTemplateFile = dirname(__FILE__).'/issueexportdefs.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );

		//Get all publications (with their active issues) that are accessible to the user.
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$user = BizSession::getUser();
		$publications = BizPublication::getPublications( $user->UserID, 'browse' );

		//Handle the user input.
		if( $registerBtnPressed || $unregisterBtnPressed ) {
			try {
				if( $registerBtnPressed ) {
					$consumerSecret = isset( $_REQUEST['consumerSecret'] ) ? trim( $_REQUEST['consumerSecret'] ) : null;
					if( $serverUrl && $consumerSecret ) {
						Analytics_Utils::storeServerUrl( $serverUrl );
						Analytics_Utils::storeConsumerSecret( $consumerSecret );
						$this->registerAccessToken( true );
					} else {
						$errors['register'] = BizResources::localize( 'ERR_MANDATORYFIELDS' );
					}
				}
				elseif( $unregisterBtnPressed ) {
					$this->registerAccessToken( false );
					Analytics_Utils::storeIsRegistered( false );
				}
			} catch( BizException $e ) {
				$errors['register'] = $e->getMessage();
			}
		}		
		elseif( $saveSettingsBtnPressed ) {
			try {
				Analytics_Utils::storeRevealUsernames( $analyticsSendUserNames );
			} catch( BizException $e ) {
				$errors['settings'] = $e->getMessage();
			}
		}
		elseif( $exportBtnPressed ) {
			try {
				// Raise the max execution time to ensure that the plugin has enough time to get and save all the data.
				set_time_limit(3600);

				if( isset( $_REQUEST['exportissues'] ) ) {
					$this->exportIssuesInformation( $_REQUEST['exportissues'], $publications );
				} else {
					$errors['export'] = 'An issue has to be selected prior to exporting.'; // TODO: localize
				}

				$htmlBody = $this->printExportResults( $exportStatus, $htmlBody );
			} catch ( BizException $e ) {
				$errors['export'] = $e->getMessage();
			}
		} elseif( isset( $_REQUEST['register_errormsg'] )) {
			$errors['register'] = $_REQUEST['register_errormsg']; // *
		} elseif( isset( $_REQUEST['register_infomsg'] )) {
			$infos['register'] = $_REQUEST['register_infomsg']; // *
		} 
		// * After the access token registrations, the register_errormsg / register_infomsg messages 
		//   are put on the URL and should be shown once only. However, they stay on the URL
		//   as long the page is loaded in web browser and buttons are clicked. To avoid
		//   showing the messages over and over again, we detect if any button is clicked, 
		//   in which case we ignore the messages.
		$isRegistered = Analytics_Utils::getIsRegistered();
		try {
			require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
			$serverUrl = !$serverUrl ? Analytics_Utils::getServerUrl() : $serverUrl;
			$consumerKey = Analytics_Utils::getConsumerKey(); // readonly
			$consumerSecret = $isRegistered ? '***' : '';
			$analyticsSendUserNames = !$analyticsSendUserNames ? Analytics_Utils::getRevealUsernames() : $analyticsSendUserNames;
		} catch( BizException $e ) {
			$errors['register'] = $e->getMessage();
		}

		//Set "Connection" related variables.
		$readonly = '';
		if( $isRegistered ) {
			$registerBtnTxt = '<input type="submit" name="unregister" value="<!--RES:ACT_UNREGISTER-->"/>';
			$exportIssuesBtnTxt = '<input type="submit" name="export" value="<!--RES:ACT_EXPORT-->"/>';
			$readonly = 'readonly';
		} else {
			$registerBtnTxt = '<input type="submit" name="register" value="<!--RES:ACT_REGISTER-->"/>';
			$exportIssuesBtnTxt = '<input type="submit" name="export" value="<!--RES:ACT_EXPORT-->" disabled/>';
		}

		$htmlURL = '<input name="serverUrl" size="70" value="'.formvar($serverUrl).'" '.$readonly.'/>';
		$htmlKey = '<input name="consumerKey" size="70" value="'.formvar($consumerKey).'" readonly/>';
		$htmlSecret = '<input name="consumerSecret" size="70" value="'.formvar($consumerSecret).'" '.$readonly.' autocomplete="off"/>';

		//Set "Settings" related variables.
		$sendUserNamesMsgTxt = BizResources::localize( 'ANA_SEND_USERNAMES_MSG', true, 
			array('<a target="_blank" href="https://helpcenter.woodwing.com/hc/en-us/articles/204805639">', '</a>') );
		$sendUserNamesMsgTxt = str_replace( "\\n", '<br />', $sendUserNamesMsgTxt );

		$checked = $analyticsSendUserNames ? 'checked' : '';
		$sendUserNamesCheckboxTxt = '<input type="checkbox" name="sendUserNames" value="1" '.$checked.'/>';

		//Set "Export" related variables.
		$exportIssuesDropdownTxt = $this->printExportIssuesDropdown( $publications );

		//Handle infos and errors.
		if( !$errors['settings'] && $saveSettingsBtnPressed ) {
			$infos['settings'] = BizResources::localize( 'WORDPRESS_SAVE_COMPLETED' );
		}

		if( !$errors['export'] && $exportBtnPressed ) {
			//TODO: add appropriate key once string has been approved and added to TMS.
			$url = '<a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/serverjobs.php">';
			$infos['export'] = BizResources::localize( 'ANA_ISSUES_SCHEDULED', true, array( $url, '</a>') );
		}
		
		$msgRegister = $errors['register'] ? $this->markupMsg( $errors['register'], true ) : $this->markupMsg( $infos['register'], false );
		$msgSettings = $errors['settings'] ? $this->markupMsg( $errors['settings'], true ) : $this->markupMsg( $infos['settings'], false );
		$msgExport   = $errors['export']   ? $this->markupMsg( $errors['export']  , true ) : $this->markupMsg( $infos['export']  , false );

		// TODO: Add translatable string here. For now it is hardcoded otherwise people will have to update the server core before they see the strings
		$testMonitoringUrl = SERVERURL_ROOT.INETROOT.'/config/plugins/Analytics/monitor.php?test=1';
		$testMonitoringBtnTxt = '<input type="button" value="Test Notification" onclick="window.open(\''.$testMonitoringUrl.'\');"/>';
		$htmlBody = str_replace( '<!--VAR:TEST_MONITORING_BTN-->', $testMonitoringBtnTxt, $htmlBody );

		$htmlBody = str_replace( '<!--VAR:ANALYTICS_URL-->', $htmlURL, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:ANALYTICS_KEY-->', $htmlKey, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:ANALYTICS_SECRET-->', $htmlSecret, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:REGISTERBTN-->', $registerBtnTxt, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:SENDUSERNAMES-->', $sendUserNamesCheckboxTxt, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:ANA_SEND_USERNAMES_MSG-->', $sendUserNamesMsgTxt, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:EXPORTISSUES_DROPDOWN-->', $exportIssuesDropdownTxt, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:EXPORTISSUES_BTN-->', $exportIssuesBtnTxt, $htmlBody );
		$htmlBody = str_replace ( '<!--EXPORT_STATUS-->', $exportStatus, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:REGISTER_RESULT-->', $msgRegister, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:SETTINGS_RESULT-->', $msgSettings, $htmlBody );
		$htmlBody = str_replace( '<!--VAR:EXPORT_RESULT-->',   $msgExport, $htmlBody );
		
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
				$msg = '<span style="color:#ff0000">'.$msg.'</span>';
			} else {
				$msg = '<span style="color:#01a701">'.$msg.'</span>';
			}
			$msg = '<p>'.$msg.'</p><br/>';
		}
		return $msg;
	}
	
	/**
	 * Obtains the oAuth access token from Analytics Server and registers it in filestore.
	 *
	 * @param bool $register TRUE to register, FALSE to unregister
	 * @throws BizException
	 */
	private function registerAccessToken( $register )
	{
		require_once dirname( dirname(__FILE__) ) . '/AnalyticsRestClient.class.php';
		if( $register ) { // Register: Get the Analytics access token.
			if( !AnalyticsRestClient::getAccessToken() ) {
				AnalyticsRestClient::loginToAnalytics();
			}
		} else { // Unregister: Release the Analytics access token.
			AnalyticsRestClient::releaseAccessToken();
		}
	}

	/**
	 * Prints the export results
	 * Fills in the $htmlBody (The export page) with the Analytics export results.
	 *
	 * @param string $exportStatus Empty string to be filled in with the export result status
	 * @param string $htmlBody The body page to be filled in with the export results
	 * @return string Body page with the export result.
	 */
	private function printExportResults( &$exportStatus, $htmlBody )
	{
		$errorMessages = $this->getErrorMessages();
		$errorsMsgTxt = '';
		$warningsMsgTxt = '';
		$hasError = false;
		$hasWarning = false;
		require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';
		$errorSectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'EXPORTERROR_RECORD' );
		$warningSectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'EXPORTWARNING_RECORD' );

		// @TODO: Error messages are not handled right now. But if they exists error messages are only logged for now.
		//        Should they also be fed back to the result page?
		if ($errorMessages) foreach( $errorMessages as $errorMessage ) {
			if( $errorMessage ) {
				foreach( $errorMessage as $eachErrorMsg ) {
					$severity = 'INFO'; // Default severity
					if( $eachErrorMsg['severity'] == 'error' ) {
						$hasError = true;
						$severity = 'ERROR';
					} elseif ( $eachErrorMsg['severity'] == 'warn' ) {
						$hasWarning = true;
						$severity = 'WARN';
					}
					LogHandler::Log('Analytics_IssueExportDefinitions', $severity,
						$eachErrorMsg['field_name'] . ': ' . $eachErrorMsg['message'] );
				}
			}
		}

		$prefix = 'Export status: ';
		if( !$hasError && !$hasWarning ) {
			$exportStatus = $prefix . 'creation of Server Jobs successful. Please observe the Server Job Queue
				to see if the jobs are processed.';
		} else {
			$exportStatus = $prefix . 'a warning or error occurred. Please check the error messages
				or warning messages below.';
		}
		$htmlBody = $errorSectionObj->replaceSection( $htmlBody, $errorsMsgTxt );
		$htmlBody = $warningSectionObj->replaceSection( $htmlBody, $warningsMsgTxt );
		$htmlBody = $this->printErrorsWarnings( $htmlBody, $hasError, $hasWarning );

		return $htmlBody;
	}

	/**
	 * Assembles the HTML of a dropdown given an array of publications.
	 *
	 * @param array $publications An array of publications with their channels and issues.
	 * @return string The HTML for the export issue dropdown field.
	 */
	private function printExportIssuesDropdown( array $publications )
	{
		$txt = '<select name="exportissues">'.
			'<option value="0">'.BizResources::localize( 'LBL_ALL_ISSUES' ).'</option>';

		if( $publications ) foreach( $publications as $publication ) {
			if( $publication->PubChannels && $publication->PubChannels[0]->Issues ) { //Need to look ahead to categorize the dropdown well.
				$txt .= '<optgroup label="'.$publication->Name.'">';
				foreach( $publication->PubChannels as $pubChannel ) {
					foreach( $pubChannel->Issues as $issue ) {
							$txt .= '<option value="'.$issue->Id.'">'.$issue->Name.'</option>';
					}
				}
				$txt .= '</optgroup>';
			}
		}
		$txt .= '</select>';

		return $txt;
	}

	/**
	 * Determines whether or not to display the error or warning if there's any during the export.
	 *
	 * @param string $htmlBody
	 * @param bool $hasError To determine if the Error should be shown in the export page. True to show.
	 * @param bool $hasWarning To determine if the Warning should be shown in the export page. True to show.
	 * @return string The export page filled with errors or warnings if there's any.
	 */
	private function printErrorsWarnings( $htmlBody, $hasError=false, $hasWarning=false )
	{
		$displayErrorMessages = $hasError ? '' : 'display:none';
		$htmlBody = str_replace('<!--PAR:DISPLAY_ERRORMSG-->', $displayErrorMessages, $htmlBody );
		$displayWarningMessages = $hasWarning ? '' : 'display:none';
		$htmlBody = str_replace('<!--PAR:DISPLAY_WARNINGMSG-->', $displayWarningMessages, $htmlBody );
		return $htmlBody;
	}

	/**
	 * Exports and creates EnterpriseEvent Server Jobs for one or more issues.
	 *
	 * When the issue id is 0 (for the option "All issues"), $publications is not allowed to be empty.
	 *
	 * @param integer $issueId Id of the issue that is to be exported. Id is 0 for all issues.
	 * @param array $publications A list of publications with their issues.
	 * @throws BizException on error.
	 */
	private function exportIssuesInformation( $issueId, $publications = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		if( $issueId > 0 ) {
			BizEnterpriseEvent::createIssueEvent( $issueId, 'create' );
		} elseif( $issueId == 0 ) {
			if( $publications ) {
				foreach( $publications as $publication ) {
					if( $publication->PubChannels ) foreach( $publication->PubChannels as $pubChannel ) {
						if( $pubChannel->Issues ) foreach( $pubChannel->Issues as $issue ) {
							BizEnterpriseEvent::createIssueEvent( $issue->Id, 'create' );
						}
					}
				}
			} else {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Publications should be given when exporting all issues.' );
			}
		}
	}

	/**
	 * Collect the error messages accumulated during the definitions export.
	 *
	 * @return array
	 */
	private function getErrorMessages()
	{
		// @TODO: No error messages are being handled right now.
		return array();
	}

	/**
	 * List of stylesheet files (urls) to include in the HTML page.
	 *
	 * @return array of strings (css include urls)
	 */
	public function getStyleSheetIncludes()
	{
		return array( 'webapps/analytics.css' );
	}

}
