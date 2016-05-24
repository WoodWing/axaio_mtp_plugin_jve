<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';

$ticket = checkSecure('admin');
$sessionUser = DBTicket::checkTicket( $ticket );

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$err = null;
$jobConfig = null;

$bizJobConfig = new BizServerJobConfig();

// handle request
try {
	switch( $action ) {
		case 'update': // add or edit
			$jobConfig = $bizJobConfig->newJobConfig();     // all props are null
			WW_Admin_ServerJobConfigs::updateJobConfigWithHttpParams( $jobConfig );    // update some props with user type data
			$bizJobConfig->completeJobConfig( $jobConfig ); // update missing props with DB data
			$bizJobConfig->updateJobConfig( $jobConfig );   // save data into DB
			$action = ''; // after add/edit, go back to overview
		break;
		case 'delete':
			$bizJobConfig->deleteJobConfig( $id );
			$action = ''; // after delete, go back to overview
		break;
	}	
} catch( BizException $e ) {
	if( $action == 'update' || $action == 'delete' ) {
		// on error, we stick to the current action (we do not go back to overview)
		//$action = ( $id === 0 ) ? 'add' : 'edit';
		$action = 'edit';
	}
	$err = $e->getMessage();
}

// show results
try {
	switch( $action ) {
		case '' : // Overview: List all servers job configs
			$txt = HtmlDocument::loadTemplate( 'serverjobconfigs.htm' );
		
			// Get markers for old/new configs to refer to helper text
			$oldConfigObj = new WW_Utils_HtmlClasses_TemplateSection( 'MARK_OLDCONFIG' );
			$oldConfigTxt = $oldConfigObj->getSection( $txt );
			$txt = $oldConfigObj->replaceSection( $txt, '' );
			$newConfigObj = new WW_Utils_HtmlClasses_TemplateSection( 'MARK_NEWCONFIG' );
			$newConfigTxt = $newConfigObj->getSection( $txt );
			$txt = $newConfigObj->replaceSection( $txt, '' );

			// Determine what to how; new ones, obsoleted ones, or all.
			$dbConfigs  = $bizJobConfig->listJobConfigs();
			$envConfigs = $bizJobConfig->getJobConfigsFromEnvironment();
			$newConfigs = $bizJobConfig->getIntroducedJobConfigs( $dbConfigs, $envConfigs );
			$obsConfigs = $bizJobConfig->getObsoletedJobConfigs( $dbConfigs, $envConfigs );

			// Show helper text when there are old/new configs
			$msg = count($newConfigs) ?  $newConfigTxt.' '. BizResources::localize( 'SVR_MSG_NEW_JOBCONFIGS' ).'<br/>' : '';
			$txt = str_replace( '<!--PAR:MSG_NEW_JOBCONFIGS-->', $msg, $txt );
			$msg = count($obsConfigs) ? $oldConfigTxt .' '. BizResources::localize( 'SVR_MSG_OLD_JOBCONFIGS' ).'<br/>' : '';
			$txt = str_replace( '<!--PAR:MSG_OLD_JOBCONFIGS-->', $msg, $txt );

			// Build the view
			$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'JOBCONFIG_RECORD' );
			$sectionTxt = $sectionObj->getSection( $txt );
			$jobConfigTxt = '';
			foreach( $dbConfigs as $serverType => $jobTypes ) {
				foreach( $jobTypes as $jobType => $jobConfig ) {
					$mark = isset($obsConfigs[$serverType][$jobType]) ? $oldConfigTxt : '';
					$recordTxt = $sectionObj->fillInRecordFields( $sectionTxt, $jobConfig, false ); // false = readonly mode
					$recordTxt = str_replace( '<!--PAR:MARK_CONFIG-->', $mark, $recordTxt );
						$tempSectionTxt = $recordTxt;
						
						if( $jobConfig->Recurring == 1 ) {
							$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_RECURRING-->', '', $tempSectionTxt);
							$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_USERINITIATED-->', 'display:none', $tempSectionTxt);
						} else {
							$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_RECURRING-->', 'display:none', $tempSectionTxt);
							$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_USERINITIATED-->', '', $tempSectionTxt);
						}
						$jobConfigTxt .= $sectionObj->fillInRecordFields( $tempSectionTxt, $jobConfig, false ); // false = readonly mode
										
				}
			}
			foreach( $envConfigs as $serverType => $jobTypes ) {
				foreach( $jobTypes as $jobType => $jobConfig ) {
					if( !isset($dbConfigs[$serverType][$jobType]) ) {

						// Automatically create the new config that was missing at database
						$bizJobConfig->updateJobConfig( $jobConfig );

						$mark = isset($newConfigs[$serverType][$jobType]) ? $newConfigTxt : '';
						$recordTxt = $sectionObj->fillInRecordFields( $sectionTxt, $jobConfig, false ); // false = readonly mode
						$recordTxt = str_replace( '<!--PAR:MARK_CONFIG-->', $mark, $recordTxt );						
						$tempSectionTxt = $recordTxt;						

						if( $jobConfig->Recurring == 1 ) {
							$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_RECURRING-->', '', $tempSectionTxt);
							$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_USERINITIATED-->', 'display:none', $tempSectionTxt);											 
						} else {
							$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_RECURRING-->', 'display:none', $tempSectionTxt);
							$tempSectionTxt = str_replace('<!--PAR:RECURRENCE_USERINITIATED-->', '', $tempSectionTxt);
						}
						$jobConfigTxt .= $sectionObj->fillInRecordFields( $tempSectionTxt, $jobConfig, false ); // false = readonly mode
										
					}
				}
			}
			$txt = $sectionObj->replaceSection( $txt, $jobConfigTxt );
		break;
		case 'edit':
			$txt = HtmlDocument::loadTemplate( 'serverjobconfig_edit.htm' );
			if( is_null($jobConfig) ) {
				$jobConfig = $bizJobConfig->getJobConfig( $id );
			}
			$txt = str_replace('<!--DISABLE_DEL_BTN-->', '', $txt); 			
		break;
	}
} catch( BizException $e ) {
	$err = $e->getMessage();
}

$txt = str_replace('<!--PAR:ERROR-->', $err, $txt);
if ( $action == 'edit' /*|| $action == 'add'*/ ) {

	// Fill in the server job config details
	$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'JOBCONFIG_RECORD' );
	$sectionTxt = $sectionObj->getSection( $txt );
	$jobConfigTxt = $sectionObj->fillInRecordFields( $sectionTxt, $jobConfig, true ); // true = edit mode
	$jobConfigTxt = WW_Admin_ServerJobConfigs::buildUsersCombo( $jobConfig, $jobConfigTxt, $sessionUser );
	$txt = $sectionObj->replaceSection( $txt, $jobConfigTxt );
}

print HtmlDocument::buildDocument($txt);

class WW_Admin_ServerJobConfigs
{
	/**
	 * Updates a given server job config (object) with user data posted through HTTP params.
	 *
	 * @param ServerJobConfig $jobConfig Server Job config to update.
	 */
	public static function updateJobConfigWithHttpParams( ServerJobConfig $jobConfig )
	{
		if( isset($_REQUEST['id']) )             $jobConfig->Id               = intval($_REQUEST['id']);
		if( isset($_REQUEST['jobtype']) )        $jobConfig->JobType          = $_REQUEST['jobtype'];
		if( isset($_REQUEST['servertype']) )     $jobConfig->ServerType       = $_REQUEST['servertype'];
		if( isset($_REQUEST['attempts']) )       $jobConfig->NumberOfAttempts = intval($_REQUEST['attempts']);
		$jobConfig->Active = isset($_REQUEST['active']) && $_REQUEST['active'] == 'on'; // for checkboxes, we can not distingish between 'missing' and 'untagged'
		if( isset($_REQUEST['userid']) )         $jobConfig->UserId           = intval($_REQUEST['userid']);
		/* - Remarked for now, since recurring job configure on the scheduler/crontab
		if( isset($_REQUEST['dailystarttime']) ) $jobConfig->DailyStartTime   = $_REQUEST['dailystarttime'];
		if( isset($_REQUEST['dailystoptime']) )  $jobConfig->DailyStopTime    = $_REQUEST['dailystoptime'];
		if( isset($_REQUEST['timeinterval']) )   $jobConfig->TimeInterval     = intval($_REQUEST['timeinterval']);
		*/
	}
	
	/**
	 * Builds combo box filled with users to allow admin user to select one to be applied as acting 
	 * user for the given server job.
	 *
	 * @param ServerJobConfig $jobConfig
	 * @param string $jobConfigTxt Template fragment that contains the JOBCONFIG_USERCOMBO template section to be replaced with the combo
	 * @return string Template fragment with users combo filled in.
	 */
	public static function buildUsersCombo( ServerJobConfig $jobConfig, $jobConfigTxt, $sessionUser )
	{
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'JOBCONFIG_USERCOMBO' );
		$sectionTxtCol = '';
		$firstEntry = '';
		if( is_null($jobConfig->SysAdmin) ) { // null => acting user only
			$firstEntry = '<'.BizResources::localize('CURRENT').'>';
		} else {
			// Get users from DB
			require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
			$users = BizAdmUser::listUsersObj( $sessionUser, null, null, null, $jobConfig->SysAdmin );
			
			// Check if user configured (before) is still a valid user at the DB
			$found = false;
			foreach( $users as $user ) {
				if( $user->Id == $jobConfig->UserId ) {
					$found = true;
				}
			}
			
			// When user not found, add special entry to user combo box to let admin user pick one
			if( !$found ) {
				$firstEntry = '<'.BizResources::localize('PICK').'>';
			}
		}
		
		// Add special entry to user combo box
		if( $firstEntry ) {
			$sectionTxt = $sectionObj->getSection( $jobConfigTxt );
			$sectionTxt = str_replace('<!--PAR:USERID-->', 0, $sectionTxt);
			$sectionTxt = str_replace('<!--PAR:USER_SELECTED-->', ( !$jobConfig->UserId )  ? 'selected="selected"' : '' , $sectionTxt);
			$sectionTxt = str_replace('<!--PAR:USERNAME-->', formvar($firstEntry), $sectionTxt);
			$sectionTxtCol .= $sectionTxt;
		}

		// Add user ids+names to user combo box
		if( !is_null($jobConfig->SysAdmin) ) { // true/false => pick user
			foreach( $users as $user ) {
				$sectionTxt = $sectionObj->getSection( $jobConfigTxt );
				$sectionTxt = str_replace('<!--PAR:USERID-->', $user->Id, $sectionTxt);
				$sectionTxt = str_replace('<!--PAR:USER_SELECTED-->', ( $user->Id == $jobConfig->UserId )  ? 'selected="selected"' : '' , $sectionTxt);
				$sectionTxt = str_replace('<!--PAR:USERNAME-->', formvar($user->FullName), $sectionTxt);
				$sectionTxtCol .= $sectionTxt;
			}
		}
		return $sectionObj->replaceSection( $jobConfigTxt, $sectionTxtCol );
	}

}