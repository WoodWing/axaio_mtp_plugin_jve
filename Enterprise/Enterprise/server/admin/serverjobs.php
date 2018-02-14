<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';

$ticket = checkSecure('admin');

$action             = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$jobId              = isset($_REQUEST['jobid']) ? $_REQUEST['jobid'] : '';
$isSetJobConfigId   = isset($_REQUEST['jobConfigId']) ? true : false;
$isSetJobStatus     = isset($_REQUEST['jobStatus']) ? true : false;
$isSetServerId      = isset($_REQUEST['serverId']) ? true : false;
$isSetUserId        = isset($_REQUEST['actingUserId']) ? true : false;
$isSetStartPos      = isset($_REQUEST['startPos']) ? true : false;
$serverJobConfigId  = $isSetJobConfigId ? $_REQUEST['jobConfigId'] : 'ALL'; //filter
$serverJobStatus    = $isSetJobStatus ? $_REQUEST['jobStatus'] : 'ALL'; //filter
$serverId           = $isSetServerId  ? $_REQUEST['serverId'] : 'ALL'; //filter
$actingUserId       = $isSetUserId ? $_REQUEST['actingUserId'] : 'ALL'; //filter
$newStartPos        = $isSetStartPos ? intval( $_REQUEST['startPos'] ) : 1;
$delId              = isset($_REQUEST['deleteIds']) ? $_REQUEST['deleteIds'] : '';

$dum = '';
cookie('serverjobs', ( !$isSetJobConfigId && !$isSetJobStatus && !$isSetServerId && !$isSetUserId ),
						$serverJobConfigId, $serverJobStatus, $serverId, $actingUserId, $dum, $dum, $dum);

$err = '';
$serverJob = null;
$errors = array();

$bizServerJob = new BizServerJob();
$bizJobConfig = new BizServerJobConfig();

// handle request
try {
	switch( $action ) {
		case 'deleteMultiple':
			$delIds = preg_split("/,/",$delId,0,PREG_SPLIT_NO_EMPTY);
			if($delIds) foreach( $delIds as $delId ){
				$bizServerJob->deleteJob( $delId );
			}
			$action = '';
			break;
		case 'deleteSingle':
			$bizServerJob->deleteJob( $jobId );
			$action = ''; // after delete, go back to overview
			break;
		case 'restart':
			$bizServerJob->restartJob( $jobId );
			$action = ''; // after retart, go back to overview
			break;
	}	
} catch( BizException $e ) {
	$err = $e->getMessage();
}

// show results
try {
	switch( $action ) {
		case '' : // overview; list all servers
			$rows = '';
			$serverjobType='';
			if($serverJobConfigId && $serverJobConfigId != 'ALL' ){
				$serverJobConfig = $bizJobConfig->getJobConfig($serverJobConfigId);
				$serverjobType = $serverJobConfig->JobType;
			}
			$serverJobsAdminApp = new ServerJobsAdminApp();
			
			$serverJobsAdminApp->buildJobTypeComboBox( $serverJobConfigId, $bizJobConfig, $serverjobType );
			
			$serverJobsAdminApp->buildJobStatusComboBox( $serverJobStatus );
			
			$serverJobsAdminApp->buildMachineNameComboBox( $serverId );
			
			$serverJobsAdminApp->buildUsersComboBox( $actingUserId, $bizServerJob );
			
			// List Jobs when requested.
			$totalJobs = $serverJobsAdminApp->listJobs( $serverJobConfigId, $serverJobStatus, $bizServerJob, 
										   $serverjobType, $serverId, $actingUserId, $newStartPos );

			if( $totalJobs > 0 ) {
				$serverJobsAdminApp->buildPageNavigationButtons( $newStartPos );
			}
			else {
				$serverJobsAdminApp->buildStartPosField( $newStartPos );
			}
			
			$txt = $serverJobsAdminApp->getHtmlPage();
		break;		
		case 'inspect':
			$txt = HtmlDocument::loadTemplate( 'serverjob_propsheet.htm' );
			if( is_null($serverJob) ) {
				$serverJob = $bizServerJob->getJob( $jobId );
			}
			$txt = str_replace('<!--VAR:SHOW_RESTART_BUTTON-->', $serverJob->JobStatus->isError() ? '' : 'display:none', $txt);
		break;
	}
} catch( BizException $e ) {
	$err = $e->getMessage();
}

try {
	if ( $action == 'inspect' ) {

		// Fill in the Server Job details
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'SERVERJOB_RECORD' );
		$sectionTxt = $sectionObj->getSection( $txt );
		$sectionTxt = str_replace('<!--PAR:JOBSTATUS-->', $serverJob->JobStatus->getStatusLocalized(), $sectionTxt);
		if( $serverJob->ErrorMessage ) {
			$sectionTxt = str_replace('<!--PAR:JOBSTATUSINFO-->', $serverJob->JobStatus->getStatusInfoLocalized().' '.$serverJob->ErrorMessage, $sectionTxt);
		} else {
			$sectionTxt = str_replace('<!--PAR:JOBSTATUSINFO-->', $serverJob->JobStatus->getStatusInfoLocalized(), $sectionTxt);
		}
		$sectionTxt = str_replace('<!--PAR:JOBPROGRESS-->', $serverJob->JobStatus->getProgressLocalized(), $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:JOBCONDITION-->', $serverJob->JobStatus->getConditionLocalized(), $sectionTxt);
		$sectionTxt = $sectionObj->fillInRecordFields( $sectionTxt, $serverJob, true ); // true = edit mode
		$txt = $sectionObj->replaceSection( $txt, $sectionTxt );
	}
} catch( BizException $e ) {
	$err = $e->getMessage();
}

$txt = str_replace('<!--PAR:ERROR-->', $err, $txt); // show errors, if any
if ( $action == 'inspect' ) {
	print HtmlDocument::buildDocument($txt,false);
} else {
	print HtmlDocument::buildDocument($txt);
}


class ServerJobsAdminApp
{
	private $txt = ''; //  the admin page (html) to show at web browser
	private $totalJobs = null; // For page navigation
	private $totalResultsDisplayed = 25; // For page navigation
	private $numResults = null; // Number rows returned by current page request.

	public function __construct()
	{
		$this->txt = HtmlDocument::loadTemplate( 'serverjobs.htm' );
	}
	
	/**
	 * Returns the HTML page loaded from template and
	 * 're-constructed' based on several methods such as
	 * buildJobTypeComboBox(), buildJobStatusComboBox(),
	 * buildMachineNameComboBox(), buildUsersComboBox(),
	 * buildPageNavigationButtons()
	 * and listJobs().
	 *
	 * @return string HTML page.
	 */
	public function getHtmlPage()
	{
		return $this->txt;
	}
	
	/**
	 * Build combo box for Server Job Types.
	 *
	 * @param int $serverJobConfigId. DB id of the server job config.
	 * @param BizServerJobConfig $bizJobConfig. serverJobConfig object to call serverJobConfig class methods.
	 * @param string $serverjobType. The serverJobType to be selected on the combo box.
	 */
	public function buildJobTypeComboBox( $serverJobConfigId, $bizJobConfig, $serverjobType )
	{
		$dbConfigs  = $bizJobConfig->listJobConfigs();
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'SERVERJOB_TYPES' );
		$jobTypeTxt = '';

		// Add 'ALL' item to combo
		$sectionTxt = $sectionObj->getSection( $this->txt );
		$sectionTxt = str_replace('<!--PAR:JOBID-->', 'ALL', $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:JOBTYPE_SELECTED-->', ( $serverJobConfigId == 'ALL' )  ? 'selected="selected"' : '' , $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:JOBTYPE-->', formvar(BizResources::localize('ACT_ALL')), $sectionTxt);
		$jobTypeTxt .= $sectionTxt;
		
		// Add Job Types to combo
		foreach( $dbConfigs /*as $serverType =>*/ as $jobTypes ){
			foreach( $jobTypes as $jobType => $jobConfig ){
				$sectionTxt = $sectionObj->getSection( $this->txt );
				$sectionTxt = str_replace('<!--PAR:JOBID-->', $jobConfig->Id, $sectionTxt);
				$sectionTxt = str_replace('<!--PAR:JOBTYPE_SELECTED-->', ( $jobConfig->JobType == $serverjobType ) ? 'selected="selected"' : '' , $sectionTxt);
				$sectionTxt = str_replace('<!--PAR:JOBTYPE-->', formvar($jobType), $sectionTxt);
				$jobTypeTxt .= $sectionTxt;
			}
		}
		$this->txt = $sectionObj->replaceSection( $this->txt, $jobTypeTxt );
	}
	
	/**
	 * Build combo box for Server Job Statuses.
	 *
	 * @param string $serverJobStatus. The serverJobStatus to be selected on the combo box.
	 */
	public function buildJobStatusComboBox( $serverJobStatus )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'SERVERJOB_STATUS' );
		$jobTypeTxt = '';

		// Add 'ALL' item to combo
		$sectionTxt = $sectionObj->getSection( $this->txt );
		$sectionTxt = str_replace('<!--PAR:JOBSTATE-->', 'ALL', $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:JOBSTATUS_SELECTED-->', ( $serverJobStatus == 'ALL' ) ? 'selected="selected"' : '' , $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:JOBSTATE_LOCAL-->', formvar(BizResources::localize('ACT_ALL')), $sectionTxt);
		$jobTypeTxt .= $sectionTxt;

		// Add Job Statuses to combo
		$jobStatuses = ServerJobStatus::listAllStatuses();
		$serverJobObj = new ServerJob();
		$serverJobObj->JobStatus = new ServerJobStatus();
		foreach( $jobStatuses as $jobStatus ){
			$serverJobObj->JobStatus->setStatus( $jobStatus ); 
			$jobStatusLocalized = $serverJobObj->JobStatus->getStatusLocalized();
			$sectionTxt = $sectionObj->getSection( $this->txt );
			$sectionTxt = str_replace('<!--PAR:JOBSTATE-->', $jobStatus, $sectionTxt);
			$sectionTxt = str_replace('<!--PAR:JOBSTATUS_SELECTED-->', ( $serverJobStatus == $jobStatus ) ? 'selected=\'selected\'' : '', $sectionTxt);
			$sectionTxt = str_replace('<!--PAR:JOBSTATE_LOCAL-->', formvar($jobStatusLocalized), $sectionTxt);
			$jobTypeTxt .= $sectionTxt;
		}
		$this->txt = $sectionObj->replaceSection( $this->txt, $jobTypeTxt );
	}
	
	/**
	 * Build combo box for Server's name (Machine names) that executes the server jobs.
	 *
	 * @param int $serverId. DB id of the server.
	 */
	public function buildMachineNameComboBox( $serverId )
	{
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'MACHINENAMES' );
		$jobTypeTxt = '';
		
		// Add 'ALL' item to combo
		$sectionTxt = $sectionObj->getSection( $this->txt );
		$sectionTxt = str_replace('<!--PAR:SERVERID-->', 'ALL', $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:MACHINENAME_SELECTED-->', ( $serverId == 'ALL' )  ? 'selected="selected"' : '' , $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:MACHINENAME-->', formvar(BizResources::localize('ACT_ALL')), $sectionTxt);
		$jobTypeTxt .= $sectionTxt;

		// Add '-'(To show no server has pick up the job yet ) item to combo
		$sectionTxt = $sectionObj->getSection( $this->txt );
		$sectionTxt = str_replace('<!--PAR:SERVERID-->', 0, $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:MACHINENAME_SELECTED-->', ( $serverId === '0' )  ? 'selected="selected"' : '' , $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:MACHINENAME-->', '-- (Job not picked up by any server yet)', $sectionTxt);
		$jobTypeTxt .= $sectionTxt;

		// Add Machine Names to combo
		require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
		$bizServer = new BizServer();
		$listOfServers = $bizServer->listServers();

		foreach( $listOfServers as /* serverId => */ $serverObj ) {
			$sectionTxt = $sectionObj->getSection( $this->txt );
			$sectionTxt = str_replace('<!--PAR:SERVERID-->', $serverObj->Id, $sectionTxt );
			$sectionTxt = str_replace('<!--PAR:MACHINENAME_SELECTED-->', ( $serverObj->Id == $serverId ) ? 'selected="selected"' : '' , $sectionTxt);
			$sectionTxt = str_replace('<!--PAR:MACHINENAME-->', formvar($serverObj->Name), $sectionTxt);
			$jobTypeTxt .= $sectionTxt;
		}
		$this->txt = $sectionObj->replaceSection( $this->txt, $jobTypeTxt );
	}
	
	/**
	 * Build combo box for users that executes the server jobs.
	 *
	 * @param int $actingUserId. DB id of the user.
	 * @param BizServerJobConfig $bizJobConfig. serverJobConfig object to call serverJobConfig class methods.
	 * 
	 */
	public function buildUsersComboBox( $actingUserId, $bizServerJob )
	{
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'ACTINGUSERS' );
		$jobTypeTxt = '';
		
		// Add 'ALL' item to combo
		$sectionTxt = $sectionObj->getSection( $this->txt );
		$sectionTxt = str_replace('<!--PAR:ACTINGUSERID-->', 'ALL', $sectionTxt );
		$sectionTxt = str_replace('<!--PAR:ACTINGUSER_SELECTED-->', ( $actingUserId == 'ALL' )  ? 'selected="selected"' : '' , $sectionTxt);
		$sectionTxt = str_replace('<!--PAR:ACTINGUSER_SHORTNAME-->', formvar(BizResources::localize('ACT_ALL')), $sectionTxt);
		$jobTypeTxt .= $sectionTxt;
		
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$jobActingUsers = $bizServerJob->getAllJobActingUsers();
		if( !is_null( $jobActingUsers )) foreach( $jobActingUsers as $jobActingUser ) {
			if( $jobActingUser ) {
				$row = DBUser::getUser( $jobActingUser );
				$userId = !is_null( $row ) ? $row['id'] : null;
				$sectionTxt = $sectionObj->getSection( $this->txt );
				$sectionTxt = str_replace('<!--PAR:ACTINGUSERID-->', $userId, $sectionTxt );
				$sectionTxt = str_replace('<!--PAR:ACTINGUSER_SELECTED-->', ( $userId == $actingUserId ) ? 'selected="selected"' : '' , $sectionTxt);
				$sectionTxt = str_replace('<!--PAR:ACTINGUSER_SHORTNAME-->', formvar($jobActingUser), $sectionTxt);
				$jobTypeTxt .= $sectionTxt;
			}	
		}
		$this->txt = $sectionObj->replaceSection( $this->txt, $jobTypeTxt );
	}
	
	/**
	 * List the server jobs given the $serverjobType and $serverJobStatus.
	 *
	 * @param int $serverJobConfigId. Server job Db Id.
	 * @param string $serverJobStatus. Server job status.
	 * @param BizServerJob $bizServerJob. Object of  bizServerJob to call serverJob class's methods.
	 * @param string $serverjobType. Server job JobType.
	 * @param int $serverId Db server id where the job was run/executed at.
	 * @param int $actingUserId Db user id of the user that execute the job.
	 * @param int $newStartPos The starting number Nth record to be listed in the page.
	 * @return int $this->totalJobs The total jobs found by user search criterias(Not the total jobs that will be displayed in a page).
	 * 								Returns 0 when no records found.
	 */
	public function listJobs( $serverJobConfigId, $serverJobStatus, $bizServerJob, $serverjobType, $serverId, $actingUserId, 
								$newStartPos )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$recordsToDisplay = true;
		$params = array();
		if( $serverJobConfigId != 'ALL' || $serverJobStatus != 'ALL' ||
			$serverId != 'ALL' || $actingUserId != 'ALL' ) {
			if( $serverJobConfigId && $serverJobStatus ) {
				if( !empty($serverjobType) ) {
					$params['jobtype'] = $serverjobType;
				}
				if( $serverJobStatus && $serverJobStatus != 'ALL' ) {
					$params['jobstatus'] = $serverJobStatus;
				}
				if( ( $serverId && $serverId != 'ALL' ) ||
						$serverId == '0'/*job not executed yet, no server assigned to job */ ) {
					$params['assignedserverid'] = $serverId;
				}
				if( $actingUserId && $actingUserId != 'ALL' ) {
					$userAttributes = DBUser::getUserById( $actingUserId );
					$params['actinguser'] = $userAttributes['user'];
				}
			}
		}
		// For page navgigations.
		// Retrieve only certain amount of jobs requested by user ( Depending on how many records displayed on a page )
		$serverJobs = $bizServerJob->listPagedJobs( $params, array( 'queuetime' => TRUE), $newStartPos-1, $this->totalResultsDisplayed );

		$this->numResults = count( $serverJobs ); // For page navigation

		// Count all jobs.
		$this->totalJobs = $bizServerJob->countServerJobsByParameters( $params ); // For page navigation

		if( $serverJobs ){
			$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'SERVERJOB_RECORD' );
			$jobsTxt = '';

			$bizJobConfig = new BizServerJobConfig();

			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
			$bizServer = new BizServer();
			$listOfServers = $bizServer->listServers();

			foreach( $serverJobs as $serverJob ) {
				$sectionTxt = $sectionObj->getSection( $this->txt );
				$sectionTxt = str_replace('<!--PAR:JOBSTATUS-->', $serverJob->JobStatus->getStatusLocalized(), $sectionTxt);
				$sectionTxt = str_replace('<!--PAR:JOBPROGRESS-->', $serverJob->JobStatus->getProgressLocalized(), $sectionTxt);
				$sectionTxt = str_replace('<!--PAR:JOBCONDITION-->', $serverJob->JobStatus->getConditionLocalized(), $sectionTxt);

				// ServerName
				$assignedServerId = $serverJob->AssignedServerId;
				$assignedServerName = $assignedServerId != 0 ? $listOfServers[$assignedServerId]->Name :
										'-'; // When job is not completed yet, there will be no assigned server yet.
				$sectionTxt = str_replace('<!--PAR:ASSIGNED_SERVER-->', $assignedServerName, $sectionTxt);
				$tempSectionTxt = $sectionObj->fillInRecordFields( $sectionTxt, $serverJob, false ); // false = readonly mode

				$jobConfig = $bizJobConfig->findJobConfig( $serverJob->JobType , $serverJob->ServerType );
				if( $jobConfig->Recurring == 1 ) {
					$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_RECURRING-->', '', $tempSectionTxt);
					$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_USERINITIATED-->', 'display:none', $tempSectionTxt);
				} else {
					$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_RECURRING-->', 'display:none', $tempSectionTxt);
					$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_USERINITIATED-->', '', $tempSectionTxt);
				}
				$jobsTxt .= $sectionObj->fillInRecordFields( $tempSectionTxt, $jobConfig, false ); // false = readonly mode
			}
			$this->txt = $sectionObj->replaceSection( $this->txt, $jobsTxt );
			$this->txt = str_replace('<!--VAR:SHOW_RECORD-->', '', $this->txt);
			$this->txt = str_replace('<!--VAR:NO_RECORD-->','display:none;', $this->txt );
			$this->txt = str_replace('<!--VAR:NO_LEGEND_AND_BUTTONS-->','', $this->txt );
		}else{
			$recordsToDisplay = false;
		}

		if( !$recordsToDisplay ) { // No records to display, so hide all the records row in template.
			$this->txt = str_replace('<!--VAR:SHOW_RECORD-->','display:none;', $this->txt );
			$this->txt = str_replace('<!--VAR:NO_RECORD-->','', $this->txt );
			$this->txt = str_replace('<!--VAR:NO_LEGEND_AND_BUTTONS-->','display:none;', $this->txt );

			$this->totalJobs = 0; // For page navigation buttons
		}
		return $this->totalJobs;
	}

	/**
	 * Build buttons for page navigations.
	 * 
	 * @param int $newStartPos Indicator to the page navigation buttons on which record should be first shown.
	 * 
	 */
	public function buildPageNavigationButtons( $newStartPos )
	{
		$pageCount      = ceil( $this->totalJobs / $this->totalResultsDisplayed );
		$curPage        = ceil( $newStartPos / $this->totalResultsDisplayed  );
		$backPos        = $newStartPos -  $this->totalResultsDisplayed;
		$nextPos        = $newStartPos +  $this->totalResultsDisplayed;
		$thisEndPos     = $newStartPos + $this->numResults -1;
		
		// To calculate the record index to be displayed on the very last page.
		$totalCleanPage = floor( $this->totalJobs / $this->totalResultsDisplayed );
		if( ( $this->totalJobs % $this->totalResultsDisplayed ) == 0 ) { // All pages have SAME total of records.
			$lastPos = ( $totalCleanPage * $this->totalResultsDisplayed ) - $this->totalResultsDisplayed + 1;
		} else { // One page will have lesser records than other pages.
			$lastPos = ( $totalCleanPage * $this->totalResultsDisplayed ) + 1;
		}

		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'PAGE_NAVIGATION' );
		$pageNavTxt = '';		

		// For "Back" page navigation button
		if( $curPage > 1 ) {
			$sectionTxt = $sectionObj->getSection( $this->txt );
			$pageNavItems = '';
			$pageNavItems .= '<a href="javascript:ShowLimitedResults( \'1\' );">';
			$pageNavItems .= '<img src="../../config/images/rewnd_32.gif" border=0 title="'.BizResources::localize("LIS_BEGIN").'"></a>&nbsp;&nbsp;&nbsp;';
			$pageNavItems .= '<a href="javascript:ShowLimitedResults( \''.$backPos.'\' );">';
			$pageNavItems .= '<img src="../../config/images/back_32.gif" border=0 title="'.BizResources::localize("ACT_BACK").'"></a>';
			$sectionTxt = str_replace('<!--PAR:PAGE_NAVIGATION_ITEMS-->', $pageNavItems, $sectionTxt );
			$pageNavTxt .= $sectionTxt;
		}
	
		// For "Display" page navigation button ( 10-20 / 30 ) => ( record 10 to record 20 out of 30)
		$sectionTxt = $sectionObj->getSection( $this->txt );
		$pageNavItems = '';
		$pageNavItems .= $newStartPos .'-'. $thisEndPos .'/'. $this->totalJobs;
		$pageNavItems .= '<input type="hidden" name="startPos" value="'.$newStartPos.'">';
		$sectionTxt = str_replace('<!--PAR:PAGE_NAVIGATION_ITEMS-->', $pageNavItems, $sectionTxt );
		$pageNavTxt .= $sectionTxt;

		// For "Forward" page navigation button
		if( $curPage < $pageCount ) {
			$sectionTxt = $sectionObj->getSection( $this->txt );
			$pageNavItems = '';
			$pageNavItems .= '<a href="javascript:ShowLimitedResults( \''.$nextPos.'\' );">';
			$pageNavItems .= '<img src="../../config/images/forwd_32.gif" border=0 title="'.BizResources::localize("LIS_NEXT").'"></a>&nbsp;&nbsp;&nbsp;';
			$pageNavItems .= '<a href="javascript:ShowLimitedResults( \''.$lastPos.'\' );">';
			$pageNavItems .= '<img src="../../config/images/fastf_32.gif" border=0 title="'.BizResources::localize("LIS_LAST").'"></a></td>';
			$sectionTxt = str_replace('<!--PAR:PAGE_NAVIGATION_ITEMS-->', $pageNavItems, $sectionTxt );
			$pageNavTxt .= $sectionTxt;
		}
		
		$this->txt = $sectionObj->replaceSection( $this->txt, $pageNavTxt );
	}

	/**
	 * Build hidden starting position field in case "total pages" are zero. This is needed in case the user
	 * wants to use the selectboxes to make a new selection.
	 *
	 * @param int $newStartPos Indicator to the page navigation buttons on which record should be first shown.
	 *
	 */
	public function buildStartPosField( $newStartPos )
	{
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'PAGE_NAVIGATION' );
		// For "Display" page navigation button ( 10-20 / 30 ) => ( record 10 to record 20 out of 30)
		$sectionTxt = $sectionObj->getSection( $this->txt );
		$pageNavItems = '';
		$pageNavItems .= '<input type="hidden" name="startPos" value="'.$newStartPos.'">';
		$pageNavTxt = str_replace('<!--PAR:PAGE_NAVIGATION_ITEMS-->', $pageNavItems, $sectionTxt );

		$this->txt = $sectionObj->replaceSection( $this->txt, $pageNavTxt );
	}

}