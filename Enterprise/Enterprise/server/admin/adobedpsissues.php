<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// Check the ticket and Determine the user.
$ticket = checkSecure('admin');
$user = DBTicket::checkTicket( $ticket );
$app = new AdobeDpsIssuesAdminApp();

$channelEditionId = isset($_POST['channelEditionId']) ? $_POST['channelEditionId'] : '';
$dpsTicket  = isset($_POST['dpsTicket'])   ? $_POST['dpsTicket'] : '';
$accountId  = isset($_POST['accountId'])   ? $_POST['accountId'] : '';

$isUpdateRequest = isset($_POST['ud_issue_id']);

// Ajax update call from the page to update an issue.
if ($isUpdateRequest) {
	// Product ID can be empty as it has not always been enforced in the past.
	$productId = isset($_POST['ud_product_id']) ? $_POST['ud_product_id'] : '';
	$issueId = isset($_POST['ud_issue_id']) ? $_POST['ud_issue_id'] : '';
	$statusId = isset($_POST['ud_issue_status']) ? $_POST['ud_issue_status'] : '';
	$freePaid = isset($_POST['ud_free_paid']) ? $_POST['ud_free_paid'] : '';

	// Validate issue id, if empty we cannot proceed to update the issue.
	if ($issueId === '') {
		$message = BizResources::localize('DPS_ADM_FORM_ERROR_ISSUE_ID');
		header('HTTP/1.1 400 '.$message, true, 400);
		header('400 '.$message );
		exit;
	}

	// Validate the statusId. (test, production or disabled).
	if ($statusId != 'disabled' && $statusId != 'test' && $statusId != 'production') {
		$message = BizResources::localize( 'DPS_ADM_FORM_ERROR_ISSUE_STATUS', true, array( $statusId ) );
		header('HTTP/1.1 400 '.$message, true, 400);
		header('400 '.$message );
		exit;
	}

	// Validate the freePaid field (noChargeStore or appleStore);
	if ($freePaid != 'noChargeStore' && $freePaid != 'appleStore') {
		$message = BizResources::localize( 'DPS_ADM_FORM_ERROR_FREE_PAID', true, array( $freePaid ) );
		header('HTTP/1.1 400 '.$message, true, 400);
		header('400 '.$message );
		exit;
	}

	// attempt to update the issue.
	try {
		// Read user account for the Adobe DPS server from settings.
		$configEditionId =  ($channelEditionId !== '' )
			? $channelEditionId
			: '0_0';
		$dpsConfig = $app->getDpsConfig( $configEditionId );

		// Create client proxy that connects to Adobe DPS server.
		require_once BASEDIR.'/server/utils/DigitalPublishingSuiteClient.class.php';
		$dpsService = new WW_Utils_DigitalPublishingSuiteClient( $dpsConfig['serverurl'], $dpsConfig['username'] );

		if( $dpsTicket && $accountId) {
			$dpsService->setSignInInfo( $dpsTicket, $accountId );
		} else {
			$dpsService->signIn( $dpsConfig['username'], $dpsConfig['password'] );
			$dpsService->getSignInInfo( $dpsTicket, $accountId );
		}

		$data = array(
			'issueid' => $issueId,
			'issuestatus' => $statusId,
			'storestatus' => $freePaid,
			'productid' => $productId
		);

		// Update the issue at DPS.
		$app->updateDpsIssue($dpsService, $data);

	} catch (Exception $e) {
		$message = $e->getMessage();
		header('HTTP/1.1 500 Internal Server Error');
		header('HTTP/1.1 500 Internal Server Error - ' . $message);
		exit;
	}
	$message = 'Updated issue succesfully.';
	header('HTTP/1.1 200 OK');
	header('Status: 200 OK - '.$message );
	exit;
} else {
	// Normal procedure, read POST parameters, read from HTML form.

	$delIssueId = isset($_POST['delIssueId'])  ? $_POST['delIssueId'] : ''; // Adobe issue ids are strings (not numeric).
	$editIssueId = isset($_POST['editIssueId']) ? $_POST['editIssueId'] : '';
	$pubChanged = isset($_POST['pubChanged'])  ? (bool)$_POST['pubChanged'] : false;

	// Read HTML template form.
	$tpl = HtmlDocument::loadTemplate( 'adobedpsissues.htm');
	$issueInfos = '';
	$error = '';

	try {
		// Read user account for the Adobe DPS server from settings.
		$configEditionId =  ($channelEditionId !== '' ) ? $channelEditionId : '0_0';
		$dpsConfig = $app->getDpsConfig( $configEditionId );

		// Create client proxy that connects to Adobe DPS server.
		require_once BASEDIR.'/server/utils/DigitalPublishingSuiteClient.class.php';
		$dpsService = new WW_Utils_DigitalPublishingSuiteClient( $dpsConfig['serverurl'], $dpsConfig['username'] );

		// The first time, $dpsTicket and $accountId are not set, so we login (and store this info in the form).
		// Next time, when $dpsTicket and $accountId are set (read from form), try using that account info.
		// However, when user changes the brand/config, re-login instead.
		if( $dpsTicket && $accountId && // next time calling? (e.g. user presses Refresh button).
			!$pubChanged ) { // user did not change the brand?
			$dpsService->setSignInInfo( $dpsTicket, $accountId );
		} else { // re-login.
			$dpsService->signIn( $dpsConfig['username'], $dpsConfig['password'] );
			$dpsService->getSignInInfo( $dpsTicket, $accountId );
		}

		try {
			// Delete issue (when admin user has pressed the delete button).
			if( $delIssueId ) {
				$dpsService->deleteIssue( $delIssueId );
				$delIssueId = '';
			}
		} catch( BizException $e ) {
			$error .= formvar($e->getMessage()).'<br/>'.formvar($e->getDetail()).'<br/>';
			$delIssueId = '';
		}

		$dpsIssueInfos = array();
		if ($channelEditionId !== '') {
			// Execute a dpsIssueInfos call at DPS.
			$dpsIssueInfos = $app->getIssueInfos( $dpsService );

			// Gather published issues to be used for enriching the records
			$editionData = explode('_', $configEditionId);
			$channelIssueData = $app->getPublishedIssueData($editionData[0], $editionData[1]);
			// enrich the DPS Issue info.
			$dpsIssueInfos = $app->enrichDpsIssueData($dpsIssueInfos, $channelIssueData);
		}


		// Display the retrieved issue info.
		$sttKeyStr = '<!--PAR:ISSUEINFO>-->';
		$endKeyStr = '<!--<PAR:ISSUEINFO-->';
		if( ($sttKeyPos = strpos( $tpl, $sttKeyStr )) !== false &&
			($endKeyPos = strpos( $tpl, $endKeyStr, $sttKeyPos )) !== false ) {
			$endKeyPos += strlen($endKeyStr);
			$tplRec = substr( $tpl, $sttKeyPos, $endKeyPos-$sttKeyPos );
			$records = $app->issueInfosToHtml( $tplRec, $dpsIssueInfos );
			$tpl = substr( $tpl, 0, $sttKeyPos ) . $records . substr( $tpl, $endKeyPos );
		}

	} catch( BizException $e ) {
		$error .= formvar($e->getMessage()).'<br/>'.formvar($e->getDetail()).'<br/>';
	}

	// Fill-in HTML form parameters.
	$tpl = str_replace( '<!--PAR:BRAND_COMBOBOX-->', $app->buildPublicationHtmlComboBox( $user, $channelEditionId ), $tpl );
	$tpl = str_replace( '<!--PAR:ERROR-->', $error, $tpl );
	$tpl = str_replace( '<!--PAR:DPS_TICKET-->', inputvar('dpsTicket', $dpsTicket, 'hidden'), $tpl );
	$tpl = str_replace( '<!--PAR:ACCOUNT_ID-->', inputvar('accountId', $accountId, 'hidden'), $tpl );
	$tpl = str_replace( '<!--PAR:DEL_ISSUE_ID-->', inputvar('delIssueId', $delIssueId, 'hidden'), $tpl );
	$tpl = str_replace( '<!--PAR:PUBLICATION_CHANGED-->', inputvar('pubChanged', false, 'hidden'), $tpl );
	$tpl = str_replace( '<!--PAR:EDIT_ISSUE_ID-->', inputvar('editIssueId', $editIssueId, 'hidden'), $tpl );
	$tpl = str_replace ('<!--PAR:URL-->',  htmlspecialchars( $_SERVER['PHP_SELF'] ), $tpl);
	$tpl = str_replace ('<!--PAR:LOGIN-->',LOGINPHP, $tpl);

	// Display a big warning that the Removal tool is used at the users own risk.
	$warnMessage1 = BizResources::localize("DPS_ADM_REM_HEAD");
	$warnMessage2 = BizResources::localize("DPS_ADM_REM_BODY_CS");
	$warnMessage3 = BizResources::localize("DPS_ADM_REM_BODY_SYNC");
	$warnMessage4 = BizResources::localize("DPS_ADM_REM_FOOT");
	$warnMessageDel = BizResources::localize("DPS_ADM_REM_WARN");

	$tpl = str_replace ('<!--PAR:WARN_MESG1-->',            formvar($warnMessage1), $tpl );
	$tpl = str_replace ('<!--PAR:WARN_MESG2-->',            formvar($warnMessage2), $tpl );
	$tpl = str_replace ('<!--PAR:WARN_MESG3-->',            formvar($warnMessage3), $tpl );
	$tpl = str_replace ('<!--PAR:WARN_MESG4-->',            formvar($warnMessage4), $tpl );
	$tpl = str_replace ('<!--PAR:WARN_MESG_DEL-->',         formvar($warnMessageDel), $tpl );

	// Replace DPS Edit form fields.
	$tpl = str_replace ('<!--PAR:WARN_MESG1-->',            formvar($warnMessage1), $tpl );

	// Show HTML form.
	print HtmlDocument::buildDocument( $tpl );

}

/**
 * Helper class for the admin application: Adobe DPS online issues.
 */
class AdobeDpsIssuesAdminApp
{
	/**
	 * Retrieves the published issue data based on the channel id / edition id.
	 *
	 * If the channel id is '0' the property is retrieved for all issues, if the channel id is specified
	 * the issue custom properties are only retrieved for that channel, the same goes for the edition id.
	 *
	 * @param String $channelId The channel id to search published issues for.
	 * @param String $editionId The edition id to search published issues for.
	 * @return array $publishedIssueData an array of published issue data.
	 */
	public function getPublishedIssueData( $channelId='0', $editionId='0') {
		$channelId = strval($channelId);
		$editionId = strval($editionId);

		//require_once BASEDIR . '/server/dbclasses/DBChanneldata.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';

		$pubIssues = DBPubPublishedIssues::getByChannelAndEdition($channelId, $editionId, true);

		$channelIssueData = array();
		if ($pubIssues) foreach ($pubIssues as $issue) {
			if ('' != $issue['externalid']) {
				$channelIssueData[$issue['externalid']] = $issue['issueid'];
			}
		}

		return $channelIssueData;
	}

	/**
	 * Enriches the DPS issue data with custom properties.
	 *
	 * Adds the DPS product ID to the array data.
	 *
	 * @param $dpsIssueData
	 * @param $channelIssueData
	 */
	public function enrichDpsIssueData($dpsIssueData, $channelIssueData) {

		if ($dpsIssueData) foreach ($dpsIssueData as $key => $dpsIssue){
			$dpsIssue['inEnterprise'] = false;

			if ($channelIssueData) {
				if ( isset($channelIssueData[$key]) ){
					$dpsIssue['inEnterprise'] = true;
				}
			}
			$dpsIssueData[$key] = $dpsIssue;
		}
		return $dpsIssueData;
	}

	/**
	 * Updates the issue information on DPS.
	 *
	 * Please note that only the information on Adobe DPS is adjusted, not the vlaues
	 *
	 * @param $dpsService
	 * @param $data
	 */
	public function updateDpsIssue($dpsService, $data) {
		// Validate input.
		if (!array($data) || !isset($data['issuestatus']) || !isset($data['storestatus'])
			|| !isset($data['issueid']) || !isset($data['productid'])) {
			// throw error, actually handle the individual values, because it should be able to update these
			// things.
			LogHandler::Log('AdobeDps', 'ERROR', 'The input data is incorrect for the updateDpsIssue function.');
		}

		$issueId = $data['issueid'];
		$issueStatus = $data['issuestatus'];
		$brokers = $data['storestatus'];
		$productId = $data['productid'];
		// Filter is optional, passing null will not update the value if it was set. Since we might not know the current
		// value of the filter do not update if it is passed as empty.
		$filter = (isset($data['filter']) && !is_null($data['filter'])) ? $data['filter'] : null;

		$result = $dpsService->updateIssue($issueId, array($brokers), $issueStatus, $productId, $filter);

		// Do something with the result.
		return $result;
	}

	/**
	 * Read user account for Adobe DPS server from settings.
	 *
	 * @param integer $channelEditionId Selected DPS account / configuration, indicated by channelId_editionId.
	 * @return array $dpsConfig Array of configuration settings
	 */
	public function getDpsConfig( $channelEditionId )
	{
		require_once BASEDIR.'/config/config_dps.php';
		$dpsConfigs = unserialize( ADOBEDPS_ACCOUNTS );
	
		list( $selectedChannel, $selectedEdition ) = explode('_', $channelEditionId );
		$dpsConfig = $dpsConfigs[$selectedChannel][$selectedEdition];
	
		if( (!$dpsConfig || !$dpsConfig['serverurl'])  && $channelEditionId !== '') {
			
			if( $selectedChannel == 0  && $selectedEdition == 0 ) { // ALL Channels and Editions
				$channelName = BizResources::localize('ACT_ALL');
				$editionName = BizResources::localize('ACT_ALL');
			} else if( $selectedChannel ) {
				require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
				require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
				
				// Retrieve Channel name
				$dpsChannel = DBChannel::getChannel( $selectedChannel );
				$channelName = $dpsChannel['name'];
				
				// Retrieve Edition name
				if( $selectedEdition != 0 ){
					require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
					$editionObj = DBEdition::getEdition( $selectedEdition );
					$editionName = $editionObj->Name;
				} else {
					$editionName = BizResources::localize('ACT_ALL');
				}
			}
			$message = BizResources::localize( 'DPS_CONNECTION_CONFIGURED_INCORRECT', true,
						array( $channelName, $selectedChannel, $editionName, $selectedEdition ) );
			$reason = BizResources::localize( 'DPS_CONNECTION_CONFIGURED_INCORRECT_REASON' );
			$reason .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			throw new BizException( null, 'ERROR', $reason, $message ); // fatal error, request to bail out
		}
	
		return $dpsConfig;
	}
	
	/**
	 * Build a HTML combobox filled with DPS accounts / configurations.
	 * Those are indicated with channel and edition ids, which names are resolved for displaying.
	 *
	 * @param string $user Admin user
	 * @param integer $selectedChannelEditionId Selected DPS account / configuration, indicated by channel_edition id.
	 * @return string HTML fragment representing the combobox.
	 */
	public function buildPublicationHtmlComboBox( $user, $selectedChannelEditionId )
	{
		require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';

		$dpsConfigs = unserialize( ADOBEDPS_ACCOUNTS );
		
		$pubs = BizPublication::getPublications( $user ); // brands that are accessible for this user only		
		$combo = '<select id="channelEditionId" name="channelEditionId" style="width:450px">';
		
		$listAll = '<'.BizResources::localize('ACT_ALL').'>';
		$combo .= '<option value=""></option>';
		if( $dpsConfigs ) foreach( $dpsConfigs as $channelId => $dpsAccs ) {
			foreach( $dpsAccs as $editionId => $dpsAccSettings ) {
				if( $channelId == 0 && $editionId == 0 ) { // All channel and All edition
					$option = $listAll . ' - ' . $listAll . ' - ' . $listAll .
							' - '.$dpsAccSettings['username'].' - '.$dpsAccSettings['serverurl'];
					if( $selectedChannelEditionId == '0_0' ) {		
						$combo .= '<option value="0_0" selected>'.formvar($option).'</option>';
					} else {
						$combo .= '<option value="0_0">'.formvar($option).'</option>';
					}
				} else if ( $channelId != 0 && $editionId != 0 ) { // specific channel and specific edition				
					if( $pubs ) {
						require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
						$pubChannelBrandSetup = new WW_Utils_ResolveBrandSetup();
						$pubChannelBrandSetup->resolveEditionPubChannelBrand( $editionId );
						
						$resolvedPub = $pubChannelBrandSetup->getPublication(); // Resolved Publication using edition id.
						foreach( $pubs as $pub ) {
							if( $pub->Id == $resolvedPub->Id ){ // User has access to the resolved pub given the edition
								$resolvedEdition = $pubChannelBrandSetup->getEdition(); // Resolved Edition using edition id.
								$resolvedPubChannel = $pubChannelBrandSetup->getPubChannelInfo(); // Resolved PubChannel using edition id.
								$option = $pub->Name . ' - ' . $resolvedPubChannel->Name . ' - ' . $resolvedEdition->Name .
												' - '.$dpsAccSettings['username'].' - '.$dpsAccSettings['serverurl'];
								$selectedChannel = '';
								$selectedEdition = '';
								if ($selectedChannelEditionId) list( $selectedChannel, $selectedEdition ) = explode( '_', $selectedChannelEditionId );
								$channelEditionId = $channelId . '_'. $editionId;
								if( $selectedChannel == $channelId && $selectedEdition == $editionId ) {								
									$combo .= '<option value="'.$channelEditionId . '" selected>'.formvar($option).'</option>';
								} else {
									$combo .= '<option value="'.$channelEditionId . '">'.formvar($option).'</option>';
								}
								break; // Since only one specific channel and one specific edition, it should only has one, so quit once found.
							}
						}
					}
				} else if( $channelId != 0 && $editionId == 0 ) { // Specific channel and all editions under this channel
					if( $pubs ) {
						$dpsChannel = DBChannel::getChannel( $channelId );
						foreach( $pubs as $pub ) {
							if( $pub->Id == $dpsChannel['publicationid'] ) { // user has access to this pub channel
								$option = $pub->Name . ' - ' . $dpsChannel['name'] . ' - ' . $listAll .
										' - '.$dpsAccSettings['username'].' - '.$dpsAccSettings['serverurl'];
								list( $selectedChannel, $selectedEdition ) = explode('_', $selectedChannelEditionId );
								$channelEditionId = $channelId . '_'. $editionId;
								if( $selectedChannel == $channelId && $selectedEdition == $editionId ) {
									$combo .= '<option value="'.$channelEditionId . '" selected>'.formvar($option).'</option>';
								} else {
									$combo .= '<option value="'.$channelEditionId . '">'.formvar($option).'</option>';
								}
								break; // quit since found the channel's publication.
							}
						}
					}
				}
			}
		}
		$combo .= '</select>';
		return $combo;
	}
	
	/**
	 * Request Adobe DPS server for information of all issues that are currently online.
	 *
	 * @param WW_Utils_DigitalPublishingSuiteClient $dpsService
	 * @return array of DPS issue infos. Index = DPS issue id, values = issue info (array).
	 */
	public function getIssueInfos( $dpsService )
	{
		// Collect list of available issues at Adobe DPS.
		$allIssues = true;        // TRUE to get test and production issues. FALSE to get production issues only.
		$title = null;            // Magazine title. If specified the list is restricted to issues matching that publication.
		$includeDisabled = true;  // TRUE to include disabled issues also. Only relevant when $allIssues is TRUE.
		$includeTest = true;      // TRUE to include 'test' issues also. Only relevant when $allIssues is FALSE.
		// If a dimension value is provided, then include only issues with the specified target dimension. 
		// If "all", then include all dimensions. If parameter is not provided, then only issues with 
		// default iPad dimension ("1024x768") are included.
		$targetDimension = 'all';

		return $dpsService->getIssueInfos( $allIssues, $title, $includeDisabled, $includeTest, $targetDimension );
	}
	
	/**
	 * Fill-in all DPS issue info properties into a template record (fragment read from HTML template).
	 *
	 * @param string $tplRec Template record.
	 * @param array $dpsIssueInfos List of DPS issue infos. Index = DPS issue id, values = issue info (array).
	 * @return string HTML fragment that contains filled-in DPS issue infos.
	 */
	public function issueInfosToHtml( $tplRec, $dpsIssueInfos )
	{
		$records = '';
		foreach( $dpsIssueInfos as $dpsIssueId => $dpsIssueInfo ) {
			$rec = $tplRec;
			$editImageLink = ($dpsIssueInfo['inEnterprise']) ? '' : $this->getEditImageLink();
			$rec = str_replace ('<!--PAR:IMG_EDIT-->',			  $editImageLink, $rec );
			$rec = str_replace ('<!--PAR:DPS_ISSUE_ID-->',        formvar($dpsIssueId), $rec );
			$rec = str_replace ('<!--PAR:PRODUCT_ID-->',          formvar($dpsIssueInfo['productId']), $rec );
			$rec = str_replace ('<!--PAR:MAGAZINE_TILE-->',       formvar($dpsIssueInfo['magazineTitle']), $rec );
			$rec = str_replace ('<!--PAR:ISSUE_NUMBER-->',        formvar($dpsIssueInfo['issueNumber']), $rec );
			$rec = str_replace ('<!--PAR:DESCRIPTION-->',         formvar($dpsIssueInfo['description']), $rec );
			$rec = str_replace ('<!--PAR:STATE-->',               formvar($dpsIssueInfo['state']), $rec );
			$publicationDate = str_replace('T', ' ', $dpsIssueInfo['publicationDate']);
			$publicationDate = substr($publicationDate, 0, -1);
			$rec = str_replace ('<!--PAR:PUBLICATION_DATE-->',    formvar($publicationDate), $rec );
			$rec = str_replace ('<!--PAR:ISSUE_ID-->',            formvar($dpsIssueId), $rec );

			// Determine if the content if free or not.
			$isFreeLabel = ($dpsIssueInfo['broker'] == 'noChargeStore')	? BizResources::localize("DPS_ADM_FREE") : BizResources::localize("DPS_ADM_PAID");
			$rec = str_replace ('<!--PAR:FREE_LABEL-->',          formvar($isFreeLabel), $rec );
			$rec = str_replace ('<!--PAR:DIMENSIONS-->',          formvar(implode(', ',$dpsIssueInfo['targetDimensions'])), $rec );
			$inEnterprise = ($dpsIssueInfo['inEnterprise']) ? BizResources::localize("FEATURE_YES") : BizResources::localize("FEATURE_NO");
			$rec = str_replace ('<!--PAR:IN_ENTERPRISE-->',       formvar($inEnterprise), $rec );

			/*
			$rec = str_replace ('<!--PAR:MANIFEST_XREF-->',       formvar($dpsIssueInfo['manifestXRef']), $rec );
			$rec = str_replace ('<!--PAR:LIBRARY_PREVIEW_URL-->', formvar($dpsIssueInfo['libraryPreviewUrl']), $rec );
			$rec = str_replace ('<!--PAR:LANDSCAPE_VERSION-->',   formvar($dpsIssueInfo['landscapeVersion']), $rec );
			$rec = str_replace ('<!--PAR:PORTRAIT_VERSION-->',    formvar($dpsIssueInfo['portraitVersion']), $rec );
			*/
			$records .= $rec;
		}
		return $records;
	}

	/**
	 * Get the edit image link
	 *
	 * @return string HTML string
	 */
	private function getEditImageLink()
	{
		$editImageLink = "<a id=\"editDpsIssue\" href=\"#\" onclick=\"buildForm('<!--PAR:DPS_ISSUE_ID-->')\">" .
						 	"<img id=\"<!--PAR:DPS_ISSUE_ID-->\" border=\"0\" title=\"<!--RES:ACT_EDIT-->\" src=\"../../config/images/edit_16.gif\" alt=\"<!--RES:ACT_EDIT-->\">" .
        	             "</a>";
		return $editImageLink;		
	}
}
