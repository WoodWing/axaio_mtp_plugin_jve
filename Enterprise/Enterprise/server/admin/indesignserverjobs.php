<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';

$ticket = checkSecure('admin');


// - - - - - - - - - - - - - - - - - - - - - - - - 
// Handle user operations.
// - - - - - - - - - - - - - - - - - - - - - - - - 

// Show the log page of the IDS job script when user has clicked the Show Log button.
$showLogForJobId = isset($_REQUEST['showlog']) ? $_REQUEST['showlog'] : ''; 
if( $showLogForJobId ) {
	// fetch blob and show text as html...
	$row = DBInDesignServerJob::getJobLog( $showLogForJobId );
	$errMsg = trim($row['errormessage']);
	$srcRes = trim($row['scriptresult']);
	if( !empty($errMsg) || !empty($srcRes) ) {
		$scriptresult = $srcRes . "\n" . $errMsg;
		$scriptresult = str_replace("\r\n","<br/>", $scriptresult); // windows
		$scriptresult = str_replace("\r","<br/>", $scriptresult);	// mac
		$scriptresult = str_replace("\n","<br/>", $scriptresult);	// linux / unix 	
	} else {
		$scriptresult = 'No log available for job ['.$showLogForJobId.']';
	}

	print($scriptresult);
	return;
}

// Restart the job when user has clicked the Restart button.
$restartJobId = isset($_REQUEST['restart']) ? $_REQUEST['restart'] : '';
if( !empty($restartJobId) ) {
	BizInDesignServerJobs::restartJob( $restartJobId );
}

// Remove the job when user has clicked the Remove button.
$remove = isset($_REQUEST['remove']) ? $_REQUEST['remove'] : '';
if( !empty($remove) ) {
	BizInDesignServerJobs::removeJob( $remove );
}

// - - - - - - - - - - - - - - - - - - - - - - - - 
// List the InDesign Servers on top of page.
// - - - - - - - - - - - - - - - - - - - - - - - - 
$rows = null;
$servers = BizInDesignServer::listInDesignServers();
foreach( $servers as $server ) {
	$busyJob = DBInDesignServerJob::getCurrentJobInfoForIds( $server->Id );
	$totalJobsForIds = DBInDesignServerJob::getTotalJobsForIds( $server->Id );
	if( $busyJob ) {
		$job = $busyJob->JobId.' ('.$busyJob->JobType.') ';
	} else {
		$job = BizResources::localize('IDS_IDLE'); // idle
	}
	$idsActive = empty($server->Active) ? '' : '<img src="../../config/images/opts_16.gif" />';
	if( $server->Active && !BizInDesignServer::isResponsive( $server ) ) {
		$idsIsAlive = '<img src="../../config/images/wwtest/warning.png" title="InDesign Server is not responding."/>';
	} else {
		$idsIsAlive = '';
	}
	$displayVer = ( $server->DisplayVersion == '???' ) ? '<font color="red"><b>'.formvar($server->DisplayVersion).'</b></font>' : formvar($server->DisplayVersion);
	$busyJobStartTime = $busyJob ? DateTimeFunctions::iso2date( $busyJob->StartTime ) : '';
	$rows .= '<tr bgcolor="#eeeeee">'.
				'<td>'.formvar($server->Name).'</td>'.
				'<td>'.$displayVer.'</td>'.
				'<td width="20">'.formvar($totalJobsForIds).'</td>'.
				'<td>'.formvar($job).'</td>'.
				'<td>'.formvar($busyJobStartTime).'</td>'.
				'<td width="20">'.$idsActive.$idsIsAlive.'</td>'.
			'</tr>'."\r\n";
}
	
$txt = HtmlDocument::loadTemplate( 'indesignserverjobs.htm' );
$txt = str_replace( '<!--SERVERS-->', $rows, $txt );


// - - - - - - - - - - - - - - - - - - - - - - - - 
// Compose the search/filter widgets (for IDS jobs).
// - - - - - - - - - - - - - - - - - - - - - - - - 
$app = new InDesignServerJobsApp();

// Add the IDS Server filter.
$searchServer = $app->getFormParamValue( 'idsjobs_searchserver', '' );
$idsServerOptions = $app->composeIdsServerComboOptions( $servers, $searchServer );
$txt = str_replace( '<!--PAR:IDS_SERVER_OPTIONS-->', $idsServerOptions, $txt );

// Add the Job Status filter.
$searchStatus = $app->getFormParamValue( 'idsjobs_searchstatus', '' );
$jobStatusOptions = $app->composeJobStatusComboOptions( $searchStatus );
$txt = str_replace( '<!--PAR:JOB_STATUS_OPTIONS-->', $jobStatusOptions, $txt );

// Add the Job Prio filter.
$searchPrio = $app->getFormParamValue( 'idsjobs_searchprio', '' );
$jobPrioOptions = $app->composeJobPrioComboOptions( $searchPrio );
$txt = str_replace( '<!--PAR:JOB_PRIO_OPTIONS-->', $jobPrioOptions, $txt );

// Add the Queued Since filter.
$searchSince = $app->getFormParamValue( 'idsjobs_searchsince', '' );
$searchSinceOptions = $app->composeQueueSinceComboOptions( $searchSince );
$txt = str_replace( '<!--PAR:QUEUED_SINCE_OPTIONS-->', $searchSinceOptions, $txt );


// - - - - - - - - - - - - - - - - - - - - - - - - 
// List the InDesign Server jobs.
// - - - - - - - - - - - - - - - - - - - - - - - - 
$searchFilters = array();
if( $searchServer ) {
	$searchFilters[] = new QueryParam( 'AssignedServerId', '=', $searchServer );
}
if( $searchStatus ) {
	if( $searchStatus == '-1' ) {
		$searchFilters[] = new QueryParam( 'JobStatus', '!=', InDesignServerJobStatus::COMPLETED );
	} else {
		$searchFilters[] = new QueryParam( 'JobStatus', '=', $searchStatus );
	}
}
if( $searchPrio ) {
	$searchFilters[] = new QueryParam( 'JobPrio', '=', $searchPrio );
}
if ( $searchSince ) {
	$searchFilters[] = new QueryParam( 'QueueTime', '=', $searchSince );
}
$limit = ( !$searchSince && DBMAXQUERY > 0 ) ? DBMAXQUERY : 0;
$jobRows = DBInDesignServerJob::searchJobs( $searchFilters, $limit );
$htmlRows = '';
if ( $jobRows ) foreach ( $jobRows as $jobIndex => $jobRow ) {
	$assignedServerId = $jobRow['assignedserverid'];
	$server = array_key_exists( $assignedServerId, $servers ) ? $servers[$assignedServerId]->Name : '';

	$jobStatus = new InDesignServerJobStatus();
	$jobStatus->setStatus( $jobRow['jobstatus'] );

	$htmlRows .= '<tr bgcolor="#eeeeee">'.
		$app->composeJobInfoCell( $jobIndex, $jobRow ).
		$app->composeObjInfoCell( $jobIndex, $jobRow ).
		'<td>'.formvar($server).'</td>'.
		'<td>'.formvar(DateTimeFunctions::iso2date( $jobRow['queuetime'] )).'</td>'.
		'<td>'.formvar(DateTimeFunctions::iso2date( $jobRow['pickuptime'] )).'</td>'.
		'<td>'.formvar(DateTimeFunctions::iso2date( $jobRow['starttime'] )).'</td>'.
		'<td>'.formvar(DateTimeFunctions::iso2date( $jobRow['readytime'] )).'</td>'.
		'<td>'.formvar($jobRow['attempts']).'</td>'.
		'<td>'.$app->composeStatus( $jobStatus, $jobRow['errormessage'] ).'</td>'.
		'<td>'.$app->composeStatusCondition( $jobStatus ).'</td>'.
		'<td>'.$app->composeStatusProgress( $jobStatus ).'</td>'.
		'<td>'.formvar(BizInDesignServerJobs::localizeJobPrioValue( $jobRow['prio'] )).'</td>';

	$scriptresult = trim($jobRow['scriptresult']);
	if( !empty($scriptresult) ) {
		$htmlRows .= "<td><a target=_blank href=\"indesignserverjobs.php?showlog=".$jobRow['jobid']."\">".
					"<img src=\"../../config/images/normalview_16.gif\" title=\"". BizResources::localize('SHOW_LOG') ."\">".
				"</a></td>\r\n";  
	} else {
		$htmlRows .= "<td>&nbsp;</td>\r\n";  		
	}
	
	if ( $jobRow["foreground"] == "" ) {
		$htmlRows .= "<td><a href=\"indesignserverjobs.php?restart=".$jobRow['jobid']."\">".
					"<img src=\"../../config/images/admin_16.gif\" title=\"". BizResources::localize('IDS_RESTARTJOB') ."\">".
				"</a></td>\r\n";  
	} else {
		$htmlRows .= "<td>&nbsp;</td>\r\n";  		
	}
	$htmlRows .= "<td><a href=\"indesignserverjobs.php?remove=".$jobRow['jobid']."\">".
				"<img src=\"../../config/images/trash_16.gif\" title=\"". BizResources::localize('ACT_REMOVE') ."\">".
			"</a></td>\r\n";
	$htmlRows .= "</tr>\r\n";
}
$txt = str_replace( '<!--JOBS-->', $htmlRows, $txt );

print HtmlDocument::buildDocument($txt);


class InDesignServerJobsApp
{
	/**
	 * Composes a HTML fragment that contains a job info icon.
	 * When hovering over the icon, a property sheet pops up with job context properties.
	 *
	 * @param integer $jobIndex
	 * @param array $jobRow
	 * @return string HTML fragment
	 */
	public function composeJobInfoCell( $jobIndex, array $jobRow )
	{
		$sheetTable = $this->composeJobPropSheet( $jobRow );
		$sheetId = 'hidden-jobsheet-'.$jobIndex;
		$jobContextInfoDiv = '<div id="'.$sheetId.'" style="display:none;">'.$sheetTable.'</div>';
		$popupOnHover = "nhpup.popup($('#{$sheetId}').html(), {'width': 400});";
		$infoIcon = '<img src="../../config/images/sinfo_16.gif" border="0" onmouseover="'.$popupOnHover.'"/>';
		return '<td>'.$infoIcon.'&nbsp;'.formvar($jobRow['jobtype']).$jobContextInfoDiv.'</td>'."\r\n";
	}
	
	/**
	 * Composes a HTML fragment that contains a an object name and an info icon.
	 * When hovering over the icon, a property sheet pops up with some object properties.
	 *
	 * @param integer $jobIndex
	 * @param array $jobRow
	 * @return string HTML fragment
	 */
	public function composeObjInfoCell( $jobIndex, array $jobRow )
	{
		if( $jobRow['objid'] ) {
			$sheetTable = $this->composeObjPropSheet( $jobRow );
			$sheetId = 'hidden-objsheet-'.$jobIndex;
			$sheetDiv = '<div id="'.$sheetId.'" style="display:none;">'.$sheetTable.'</div>';
			$popupOnHover = "nhpup.popup($('#{$sheetId}').html(), {'width': 400});";
			$infoIcon = '<img src="../../config/images/sinfo_16.gif" border="0" onmouseover="'.$popupOnHover.'"/>';
			$retVal = '<td>'.$infoIcon.'&nbsp;'.formvar($jobRow['name']).$sheetDiv.'</td>'."\r\n";
		} else {
			$retVal = '<td/>';
		}
		return $retVal;
	}
	
	/**
	 * Composes a HTML table that contains a property sheet with contextual job property values.
	 *
	 * @param array $jobRow
	 * @return string HTML table
	 */
	private function composeJobPropSheet( array $jobRow )
	{
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';

		$requiredIdsVersion = BizInDesignServer::composeRequiredVersionInfo( 
			DBVersion::joinMajorMinorVersion( $jobRow, 'minserver' ),
			DBVersion::joinMajorMinorVersion( $jobRow, 'maxserver' ) 
		);
		$runMode = $jobRow['foreground'] == 'on' ? 
			BizResources::localize('PLN_RUNMODE_SYNCHRON') : 
			BizResources::localize('PLN_RUNMODE_BACKGROUND');
		return $this->composePropSheetTable( array(
			'IDS_JOB' => $jobRow['jobid'],
			'PLN_RUNMODE' => $runMode,
			'ACT_APPLICATION_VERSION' => $requiredIdsVersion,
			'IDS_ACTING_USER' => $jobRow['actinguser'],
			'IDS_INITIATOR_USER' => $jobRow['initiator'],
			'ACT_SERVICE' => $jobRow['servicename'],
			'IDS_CONTEXT_INFO' => $jobRow['context'],
		));
	}
	
	/**
	 * Composes a HTML table that contains a property sheet with contextual object property values.
	 *
	 * @param array $jobRow
	 * @return string HTML table
	 */
	private function composeObjPropSheet( array $jobRow )
	{
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		$objVersion = DBVersion::joinMajorMinorVersion( $jobRow, 'object' );
		$objVersion = $objVersion == '0.0' ? '' : $objVersion;
		
		$runMode = $jobRow['foreground'] == 'on' ? 
			BizResources::localize('PLN_RUNMODE_SYNCHRON') : 
			BizResources::localize('PLN_RUNMODE_BACKGROUND');
		return $this->composePropSheetTable( array(
			'OBJ_ID' => $jobRow['objid'],
			'OBJ_NAME' => $jobRow['name'],
			'OBJ_LOCKED_BY' => $jobRow['usr'],
			'OBJ_VERSION' => $objVersion,
		));
	}
	
	/**
	 * Composes a HTML table that contains a property sheet with localized keys and property values.
	 *
	 * @param array $props
	 * @return string HTML table
	 */
	private function composePropSheetTable( array $props )
	{
		$propSheet = '<table>'."\r\n";
		foreach( $props as $resKey => $value ) {
			$propSheet .= $this->composePropSheetEntry( BizResources::localize( $resKey ), $value );
		}
		$propSheet .= '</table>'."\r\n";
		return $propSheet;
	}
	
	/**
	 * Composes a HTML table row that contains two fields: a property name and property value.
	 *
	 * @param string $key
	 * @param string $value
	 * @return string HTML table row
	 */
	private function composePropSheetEntry( $key, $value )
	{
		return '<tr><td align="right"><b>'.formvar($key).':</b></td><td>'.formvar($value).'</td></tr>'."\r\n";
	}
	
	/**
	 * Composes a HTML fragment with the status condition value. 
	 * On error the text is red, on warning is it orange, else black.
	 *
	 * @param InDesignServerJobStatus $jobStatus
	 * @return string HTML fragment
	 */
	public function composeStatusCondition( $jobStatus )
	{
		if( $jobStatus->isError() ) {
			$condColor = 'red';
		} elseif( $jobStatus->isWarn() ) {
			$condColor = 'orange';
		} else { // info (or unknown)
			$condColor = 'black';
		}
		return '<font color="'.$condColor.'">'.formvar($jobStatus->getConditionLocalized()).'</font>';
	}

	/**
	 * Composes a HTML fragment with the status progress value. 
	 * When job still todo/busy the text is blue, else black.
	 *
	 * @param InDesignServerJobStatus $jobStatus
	 * @return string HTML fragment
	 */
	public function composeStatusProgress( $jobStatus )
	{
		if( $jobStatus->isToDo() ) {
			$progColor = 'blue';
		} elseif( $jobStatus->isBusy() ) {
			$progColor = 'blue';
		} else { // done (or unknown)
			$progColor = 'black';
		}
		return '<font color="'.$progColor.'">'.formvar($jobStatus->getProgressLocalized()).'</font>';
	}

	/**
	 * Composes a HTML fragment with the status value and an info icon with error message in popup.
	 * When job still todo/busy the text is blue, on error it is red, on warning it is orange, else green.
	 *
	 * @param InDesignServerJobStatus $jobStatus
	 * @param string $errorMsg
	 * @return string HTML fragment
	 */
	public function composeStatus( $jobStatus, $errorMsg )
	{
		if( $jobStatus->isToDo() ) {
			$statColor = 'blue';
		} elseif( $jobStatus->isBusy() ) {
			$statColor = 'blue';
		} else { // done (or unknown)
			if( $jobStatus->isError() ) {
				$statColor = 'red';
			} elseif( $jobStatus->isWarn() ) {
				$statColor = 'orange';
			} else { // info (or unknown)
				$statColor = 'green';
			}
		}
		$statusInfoText = $jobStatus->getStatusInfoLocalized();
		if( $errorMsg ) {
			$statusInfoText .= ' '.$errorMsg;
		}
		$statusInfoHtml = '<img src="../../config/images/sinfo_16.gif" border="0" onmouseover="nhpup.popup(\''.formvar($statusInfoText).'\');"/>';
		return '<font color="'.$statColor.'">'.formvar($jobStatus->getStatusLocalized()).'</font>&nbsp;'.$statusInfoHtml;
	}

	/*
	 * Composes HTML options for a combobox.
	 *
	 * @param string[] $list Key-value pairs of options to compose.
	 * @param string $preSelect The option to pre-select
	 * @return string List of HTML option elements (to be injected into the HTML input element).
	 */
	private function composeComboOptions( array $list, $preSelect )
	{
		$options = '';
		foreach( $list as $key => $value ) {
			$selected = ($preSelect == $key)  ? 'selected = "selected" ' : '';
			$options .= '<option value="'.formvar($key).'"'.$selected.'>'.formvar($value).'</option>';
		}
		return $options;
	}

	/*
	 * Composes HTML options for a combobox listing all possible IDS servers.
	 *
	 * @param string $searchServer The IDS server to pre-select. Empty to select the 'All' item.
	 * @return string List of HTML option elements (to be injected into the HTML input element).
	 */
	public function composeIdsServerComboOptions( $idsServers, $searchServer )
	{
		$list = array( '' => BizResources::localize( 'ACT_ALL' ) );
		if( $idsServers ) foreach( $idsServers as $idsServer ) {
			$list[$idsServer->Id] = $idsServer->Name;
		}
		return $this->composeComboOptions( $list, $searchServer );
	}

	/*
	 * Composes HTML options for a combobox listing all possible IDS job statuses.
	 *
	 * @param string $searchStatus The status to pre-select. Empty to select the 'All' item.
	 * @return string List of HTML option elements (to be injected into the HTML input element).
	 */
	public function composeJobStatusComboOptions( $searchStatus )
	{
		$jobStatusIds = InDesignServerJobStatus::listAllStatuses();
		$list = array(
			'' => BizResources::localize( 'ACT_ALL' ),
			'-1' => BizResources::localize( 'SVR_JOBSTATUS_NOT_COMPLETED' )
		);
		if( $jobStatusIds ) foreach( $jobStatusIds as $jobStatusId ) {
			$jobStatus = new InDesignServerJobStatus();
			$jobStatus->setStatus( $jobStatusId );
			$list[$jobStatusId] = $jobStatus->getStatusLocalized();
		}
		return $this->composeComboOptions( $list, $searchStatus );
	}

	/*
	 * Composes HTML options for a combobox listing all possible IDS job priorities.
	 *
	 * @param string $searchPrio The priority to pre-select. Empty to select the 'All' item.
	 * @return string List of HTML option elements (to be injected into the HTML input element).
	 */
	public function composeJobPrioComboOptions( $searchPrio )
	{
		$jobPrios = BizInDesignServerJobs::getSupportedJobPrioValues();
		$list = array( '' => BizResources::localize( 'ACT_ALL' ) );
		if( $jobPrios ) foreach( $jobPrios as $jobPrio ) {
			$list[$jobPrio] = BizInDesignServerJobs::localizeJobPrioValue( $jobPrio );
		}
		return $this->composeComboOptions( $list, $searchPrio );
	}

	/*
	 * Composes HTML options for a combobox listing all days on which IDS jobs are queued.
	 *
	 * @param string $searchSince The day to pre-select. Empty to select the 'All' item.
	 * @return string List of HTML option elements (to be injected into the HTML input element).
	 */
	public function composeQueueSinceComboOptions( $searchSince )
	{
		$found = false;
		$firstDate = '';
		$days = DBInDesignServerJob::listQueueTimeOfJobsAsDays();
		$list = array( '' => BizResources::localize( 'ACT_ALL' ) );
		if( $days ) foreach( $days as $day ) {
			$queueDate = substr( $day['queuedate'], 0, 10 );
			if( $firstDate == '' ) {
				$firstDate = $queueDate;
			}
			if( $searchSince == $queueDate ) {
				$found = true;
			}
			$list[$queueDate] = substr( DateTimeFunctions::iso2date( $queueDate.'T00:00:00' ), 0, 10 );
		}
		// When searchSince is no longer valid, reset to first available date.
		if( $searchSince && !$found ) {
			$searchSince = $firstDate;
		}
		return $this->composeComboOptions( $list, $searchSince );
	}
	
	/**
	 * Retrieves an HTML form parameter value.
	 *
	 * When the value is filled in by end user, it is read from the form.
	 * In case the form was loaded the first time for this session, the
	 * value is read from the cookie. When the form was loaded the very first
	 * time, or when the cookie is expired, the default value is returned.
	 * In all cases, the returned value is stored in the cookie for next time use.
	 *
	 * @param string $key
	 * @param string $default
	 * @return string Value
	 */
	public function getFormParamValue( $key, $default )
	{
		if( array_key_exists( $key, $_GET ) ) {
			$value = $_GET[ $key ];
		} elseif( array_key_exists( $key, $_COOKIE ) ) {
			$value = $_COOKIE[ $key ];
		} else {
			$value = $default;
		}
		setcookie( $key, $value, 0, INETROOT );
		return $value;
	}
}