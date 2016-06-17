<?php

require_once BASEDIR . '/server/utils/htmlclasses/HtmlAnyForm.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlTree.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlCombo.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlDateTimeField.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlDiffTimeField.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlAction.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlIconField.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlColorField.class.php';
require_once BASEDIR . '/server/bizclasses/BizDeadlines.class.php';

class IssueDeadlinesForm extends HtmlAnyForm
{
	private $IssueId;
	private $Issue;
	private $PublId;

	private $IssueFields;
	private $SectionsTree;
	private $StatesTree;
	
	private $DeleteDeadlinesAction;
	private $UpdateIssueAction;
	private $UpdateSectionsAction;
	private $UpdateStatesAction;
	
	private $now;
	
	public function __construct($owner, $name)
	{
		HtmlAnyForm::__construct($owner, $name);
		$this->IssueId = intval($_REQUEST['issueid']);
		if ($this->IssueId)
		{
			$this->Issue = BizDeadlines::getIssue($this->IssueId);
		}
		if ($this->Issue)
		{
			$this->PublId = intval($this->Issue['publication']);
			$this->Publication = BizDeadlines::getPublication($this->PublId);
		}
		else
		{
			$this->PublId = intval($_REQUEST['publid']);
			$this->Publication = BizDeadlines::getPublication($this->PublId);
			$this->IssueId = intval($this->Publication['currentissue']);
			$this->Issue = BizDeadlines::getIssue($this->IssueId);
		}
		$this->now = date('Y-m-d\TH:i:s');
	}

	/**
	 * Enriches the HTML document with form data showing issue/category/status deadline widgets.
	 */
	public function createFields()
	{
		$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
		$colorfield = new HtmlColorField($this, '');
		$colorfield->setBoxSize( $boxSize );
		$typeiconfield = new HtmlIconField($this, '');

		// Add issue properties
		$this->IssueFields = new HtmlTree($this, BizResources::localize('ISSUE'), true);
		$this->IssueFields->addCol(175, '', 'pubcol', 'publication', BizResources::localize('PUBLICATION') );
		$this->IssueFields->addNameCol(175, BizResources::localize('ISSUE'));
		$issuePublDateField = new HtmlDateTimeField($this, 'issuepubldate', false, false);
		//$this->IssueFields->addCol(20, $colorfield, 'pd_colorcol', 'pd_color', '&nbsp', false );
		//$this->IssueFields->addCol(20, $typeiconfield, 'pd_iconcol', 'pd_icon', '', false);
		$this->IssueFields->addCol(175, $issuePublDateField, 'publdatecol', 'publdate', BizResources::localize('PUBLICATION_DATE'),true);
		$issueDeadlineField = new HtmlDateTimeField($this, 'issuedeadline', false, false);
		$this->IssueFields->addCol(20, $colorfield, 'dl_colorcol', 'dl_color', '&nbsp', false );
		$this->IssueFields->addCol(20, $typeiconfield, 'dl_iconcol', 'dl_icon', '', false);
		$this->IssueFields->addCol(175, $issueDeadlineField, 'deadlinecol', 'deadline', BizResources::localize('DEADLINE'));

		// Add category deadlines tree
		$this->SectionsTree = new HtmlTree($this, 'Sections', false);
		$deadlinefield = new HtmlDateTimeField($this, 'sectiondeadline');
		$deadlinerelativefield = new HtmlDiffTimeField($this, 'sectiondeadlinerelative');
		$this->SectionsTree->addNameCol(150, BizResources::localize('SECTION'));
		$this->SectionsTree->addCol(20, $colorfield, 'dl_colorcol', 'dl_color', '&nbsp', false );
		$this->SectionsTree->addCol(20, $typeiconfield, 'dl_iconcol', 'dl_icon', '', false);
		$this->SectionsTree->addCol(200, $deadlinefield, 'deadlinecol', 'deadline', BizResources::localize('DEADLINE'));
		//$this->SectionsTree->addCol(80, $calcdirfield, 'calcdircol','calcdir','&nbsp;<==>');
		$this->SectionsTree->addCol(120, $deadlinerelativefield, 'deadlinerelativecol', 'deadlinerelative', BizResources::localize('TIME'), true);
		$this->SectionsTree->addCol(200, null, 'beforecol', 'before', '');

		// Add status deadlines tree
		$this->StatesTree = new HtmlTree($this, 'States', false);
		$statedeadlinefield = new HtmlDateTimeField($this, 'statedeadline');
		$statedeadlinerelativefield = new HtmlDiffTimeField($this, 'statedeadlinerelative');
		$this->StatesTree->addExpandCol(50, '');
		$this->StatesTree->addNameCol(150, BizResources::localize('SECTION') . ' / ' . BizResources::localize('STATE'));
		$this->StatesTree->addCol(20, $colorfield, 'dl_colorcol', 'dl_color', '&nbsp', false, 100 );
		$this->StatesTree->addCol(20, $typeiconfield, 'dl_iconcol', 'dl_icon', '', false);
		$this->StatesTree->addCol(200, $statedeadlinefield, 'deadlinecol', 'deadline', BizResources::localize('DEADLINE'));
		//$this->StatesTree->addCol(80, $calcdirfield, 'calcdircol','calcdir','&nbsp;<==>');
		$this->StatesTree->addCol(120, $statedeadlinerelativefield, 'deadlinerelativecol', 'deadlinerelative', BizResources::localize('TIME'), true);
		$this->StatesTree->addCol(200, null, 'beforecol', 'before', '');

		// Add buttons
		$this->DeleteDeadlinesAction = new HtmlAction($this, 'deletedeadlinesaction', BizResources::localize('ACT_DELETE') . ' ' . BizResources::localize('ISSUE_DEADLINE'), null, true);
		$this->UpdateIssueAction = new HtmlAction($this, 'updateissueaction', BizResources::localize('ACT_UPDATE') . ' ' . BizResources::localize('ISSUE_DEADLINE'), null, true);
		$this->UpdateSectionsAction = new HtmlAction($this, 'updatesectionsaction', BizResources::localize('ACT_UPDATE') . ' ' . BizResources::localize('SECTION') . ' ' . BizResources::localize('DEADLINES'), null, true);
		$this->UpdateStatesAction = new HtmlAction($this, 'updatestatesaction', BizResources::localize('ACT_UPDATE') . ' ' . BizResources::localize('STATE') . ' ' . BizResources::localize('DEADLINES'), null, true);
	}

	/**
	 * Executes operations fired by admin user pressing one of the buttons at the HTML form.
	 */
	public function execAction()
	{
		// Check if user is brand admin
		require_once BASEDIR.'/server/secure.php';
		checkSecure('publadmin');
		checkPublAdmin( $this->PublId );

		// Dispatch operation
		if( isset($_REQUEST['updateissueaction']) && $_REQUEST['updateissueaction'] ) {
			$this->saveIssueDeadline( false, false );
		}
		if( isset($_REQUEST['updatesectionsaction']) && $_REQUEST['updatesectionsaction'] ) {
			$this->saveIssueDeadline( true, false );
		}
		if( isset($_REQUEST['updatestatesaction']) && $_REQUEST['updatestatesaction'] ) {
			$this->saveIssueDeadline( true, true );
		}
		if( isset($_REQUEST['deletedeadlinesaction']) && $_REQUEST['deletedeadlinesaction'] ) {
			BizDeadlines::updateIssue( $this->IssueId, array( 'deadline' => '' ) );
			BizDeadlines::deleteDeadlines( $this->IssueId );
		}
	}

	/**
	 * Updates the issue deadline with the one filled in by admin user at the form.
	 * It recalculates new absolute deadlines for the issue's categories and statuses.
	 * Optionally, it can preserve admin user's justifications made to the deadlines of
	 * the categories or statuses (that is shown/typed at the HTML form).
	 * Everything is reflected/stored at the DB.
	 *
	 * @param boolean $saveCategoriesDeadlines Preserves category deadlines with ones at form.
	 * @param boolean $saveStatusesDeadlines Preserves status deadlines with ones at form.
	 */
	private function saveIssueDeadline( $saveCategoriesDeadlines, $saveStatusesDeadlines )
	{
		$updates = $this->IssueFields->requestValues();

		// Update the user typed issue deadline directly into DB.
		$update = reset($updates); // there is only one issue at form
		$update['deadline'] = trim($update['deadline']);
		//$update['publdate'] = trim($update['publdate']);
		$issueDeadline = $update['deadline'];
		BizDeadlines::updateIssue( $this->IssueId, $update );

		if( empty($issueDeadline) ) { // delete
			BizDeadlines::deleteDeadlines( $this->IssueId );
		} else { // update
		
			// Recalculate all category deadlines and update the outcome directly into DB.
			BizDeadlines::updateRecalcSectionDefs( $this->PublId, $this->IssueId, $issueDeadline );

			// Update category deadlines with user's justifications made at the form.
			if( $saveCategoriesDeadlines ) {
				$this->saveCategoriesDeadlines( $issueDeadline );
			}

			// Recalculate all status deadlines and update the outcome directly into DB. 
			// This is a full 'matrix', which is more than actually shown; 
			// It also includes records for which no deadlines are set (=empty).
			BizDeadlines::updateRecalcIssueSectionStates( $this->PublId, $this->IssueId, $issueDeadline );

			// Update status deadlines with user's justifications made at the form.
			if( $saveStatusesDeadlines ) {
				$this->saveStatusesDeadlines( $issueDeadline );
			}

			// Recalculate all object deadlines and update the outcome directly into DB. 
			BizDeadlines::updateObjectDeadlines( $this->IssueId );
		}
	}
	
	/**
	 * Update the issue's absolute category deadlines with user's justifications made at the form.
	 * When no deadline filled in yet, the issue's deadlines is taken ('inherited').
	 * This does -not- recalculate any deadline information, and simply stores deadlines as-is into DB.
	 *
	 * @param dateTime $issueDeadline
	 */
	private function saveCategoriesDeadlines( $issueDeadline )
	{
		$updates = $this->SectionsTree->requestValues();
		foreach( $updates as $updateid => $update ) {
			$updateidarray = explode('~', $updateid);
			$type = $updateidarray[0];
			if( $type == 'SectionDef' ) {
				$sectionDefId = $updateidarray[1];
				$deadline = trim( $update['deadline'] );
				if( empty($deadline) ) {
					$deadline = $issueDeadline;
				}
				$fields = array();
				$fields['deadline'] = $deadline;
				BizDeadlines::insertIssueSection( $this->IssueId, $sectionDefId, $fields, true );
			}
		}
	}

	/**
	 * Update the issue's absolute status deadlines with user's justifications made at the form.
	 * When no deadline filled in yet, the issue's deadlines is taken ('inherited').
	 * This does -not- recalculate any deadline information, and simply stores deadlines as-is into DB.
	 *
	 * @param dateTime $issueDeadline
	 */
	private function saveStatusesDeadlines( $issueDeadline )
	{
		$updates = $this->StatesTree->requestValues();
		foreach( $updates as $updateid => $update ) {
			$updateidarray = explode('~', $updateid);
			$type = $updateidarray[0];
			if( $type == 'StateDef' ) {
				$sectionDefId = $updateidarray[1];
				$stateDefId = $updateidarray[2];
				$deadline = trim( $update['deadline'] );
				if( empty($deadline) ) {
					$deadline = $issueDeadline;
				}
				$fields = array();
				$fields['deadline'] = $deadline;
				BizDeadlines::insertIssueSectionState( $this->IssueId, $sectionDefId, $stateDefId, $fields, true );
			}
		}
	}

	/**
	 * Loads all issue's absolute category deadlines from DB. When data is missing, it is calcuted
	 * to come up with proper defaults. All deadlines are shown into the HTML doc and nothing is 
	 * updated at DB yet. (Call saveCategoriesDeadlines() for that.)
	 */
	private function loadCategoriesDeadlines()
	{
		// Build an HTML tree of categories (creating a branch per category definition)
		$sectiondefs = BizDeadlines::recalcSectionDefs( $this->PublId, $this->IssueId, $this->Issue['deadline'], false );
		foreach( $sectiondefs as $sectionDefId => $sectiondef )	{
			$sectiondef['dl_color'] = BizDeadlines::deadlineColor( $this->now, $sectiondef['deadline'] );
			$sectiondef['before'] = BizResources::localize('BEFORE') . ' ' .  BizResources::localize('ISSUE_DEADLINE');
			$sectiondef['dl_icon'] = $this->getDeadlineIcon( $sectiondef, 'section' );
			$tag = 'SectionDef' . '~' . $sectionDefId;
			$sectiondef['deadline'] = array( 'value' => $sectiondef['deadline'], 'error' => $sectiondef['dl_icon']['error'] ); // trick: pack two values into one
			$this->SectionsTree->beginNode('SectionDef', $sectiondef['section'], $sectiondef, $tag);
			$this->SectionsTree->endNode();
		}
	}

	/**
	 * Loads all issue's absolute statuses deadlines from DB. When data is missing, it is calcuted
	 * to come up with proper defaults. All deadlines are shown into the HTML doc and nothing is 
	 * updated at DB yet. (Call saveStatusesDeadlines() for that.)
	 */
	private function loadStatusesDeadlines()
	{
		//fetch sectiondefinitions for this publication (or overruling issue)
		$sectiondefs = BizDeadlines::recalcSectionDefs( $this->PublId, $this->IssueId, $this->Issue['deadline'], false ); // reset=false
		
		//fetch workflowdefinitions for this publication (or overruling issue)
		$workflowdefs = BizDeadlines::listWorkflowDefs($this->PublId, $this->IssueId);
		
		//fetch statedefinitions for this publication (or overruling issue)
		$statedefs = BizDeadlines::listStateDefs($this->PublId, $this->IssueId, 'DESC');
		
		// Build a HTML tree of absulte category-status deadlines. A branch per category is created.
		foreach( $sectiondefs as $sectionDefId => $sectiondef ) {
		
			// Fetch -relative- deadlines for all category-statuses (as configured at brand's Relative Deadlines page).
			// These -must- be set.
			$sectionStateDefs = BizDeadlines::listSectionStateDefs( $sectionDefId );
			
			// Fetch -absolute- deadlines for this category-statuses (as configured at Issues Deadline page).
			// These may not be set (in case we fall back on relative definitions taken above.).
			$issueSectionStates = BizDeadlines::listIssueSectionStates( $this->IssueId, $sectionDefId, true );
			
			//all section-relative deadlines are relative to the issue.
			$sectiondef['before'] = BizResources::localize('BEFORE') . ' ' .  BizResources::localize('ISSUE_DEADLINE');
			
			//create the sectionbranch
			$tag = 'SectionDef' . '~' . $sectionDefId;
			$sectionnode = $this->StatesTree->beginNode('SectionDef', $sectiondef['section'], $sectiondef, $tag);
			$sectionnode->ReadOnly = true;

			// For each category, loop through workflow types
			foreach( $workflowdefs as $workflowdef ) {
			
				// Filter the category definitions for this workflow type	
				$filteredstatedefs = BizDeadlines::filterStateDefsByType( $statedefs, $workflowdef['name'] );

				// Check if there is any status with a relative deadline for this workflow type
				$deadlinedstatedeffound = false;
				foreach( $filteredstatedefs as $statedef ) {
					if( $statedef['type'] == $workflowdef['name'] ) {			 	
						if( $statedef['deadlinerelative'] > 0 ) {
							$deadlinedstatedeffound = true;
							break;
						}
					}
				}
				if( !$deadlinedstatedeffound ) {
					continue; // hide deadline for this workflow type
				}

				//create a branch
				$workflowName = BizDeadlines::getTranslatedWorkflowName( $workflowdef['name'] );
				$workflownode = $this->StatesTree->beginNode( 'Workflow', $workflowName, $workflowdef );
				
				//can't edit these nodes themselves (can edit children though!)
				$workflownode->ReadOnly = true;
				$before = BizResources::localize('BEFORE') . ' ' . BizResources::localize('SECTION_DEADLINE');

				$curStatusDeadline = $sectiondef['deadline'];
				/*if( empty($curStatusDeadline) ) {
					$curStatusDeadline = $this->Issue['deadline'];
				}*/
				foreach( $filteredstatedefs as $stateDefId => $statedef ) {
				
					// Recalculate the status deadline
					$curissuesectionstate = BizDeadlines::recalcIssueSectionState( $sectiondef['deadline'], $curStatusDeadline, 
												$issueSectionStates, $sectionStateDefs, $stateDefId, false ); // reset=false
					$statedef['deadline'] = $curissuesectionstate['deadline'];
					$statedef['deadlinerelative'] = $curissuesectionstate['deadlinerelative'];
					
					// Hide this status deadline when no relative deadline is configured at brand ('template') level
					if( $statedef['deadlinerelative'] <= 0 && $statedef['deadline'] == $sectiondef['deadline'] ) {
						continue;   
					}
					
					// Add status deadline to the HTML page
					$statedef['dl_color'] = BizDeadlines::deadlineColor( $this->now, $statedef['deadline'] );
					$statedef['dl_icon'] = $this->getDeadlineIcon( $curissuesectionstate, 'status' );
					$statedef['before'] = $before;
					$tag = 'StateDef' . '~' . $sectionDefId . '~' . $statedef['id'];
					$statedef['deadline'] = array( 'value' => $statedef['deadline'], 'error' => $statedef['dl_icon']['error'] ); // trick: pack two values into one
					$this->StatesTree->beginNode(BizResources::localize('STATE'), $statedef['state'], $statedef, $tag);
					$this->StatesTree->endNode();

					// Prepare for next iteration					
					$before = BizResources::localize('BEFORE') . ' ' . $statedef['state'];
					$curStatusDeadline = $curissuesectionstate['deadline'];
				}
				$this->StatesTree->endNode();
			}
			$this->StatesTree->endNode();
		}
	}

	/**
	 * Loads all issue deadline information from DB and shows it at the HTML form.
	 * See also loadCategoriesDeadlines() and loadStatusesDeadlines().
	 */
	public function loadIssueDeadline()
	{
		$this->Issue = BizDeadlines::getIssue($this->IssueId);
		$this->Publication = BizDeadlines::getPublication($this->PublId);

		$this->Issue['publication'] = $this->Publication['publication'];
		$this->Issue['dl_color'] = BizDeadlines::deadlineColor( $this->now, $this->Issue['deadline'] );
		$this->Issue['dl_icon'] = $this->getDeadlineIcon( $this->Issue, 'issue' );

		$pdProps = array( 'deadline_edit' => true, 'deadline_toolate' => false );
		$pdProps['deadline_nonworking'] = DateTimeFunctions::nonWorkDay( DateTimeFunctions::iso2time($this->Issue['publdate']) );
		$this->Issue['pd_color'] = BizDeadlines::deadlineColor( $this->now, $this->Issue['publdate'] );
		$this->Issue['pd_icon'] = $this->getDeadlineIcon( $pdProps, 'publdate' );

		$tag = 'IssueDef' . '~' . $this->Issue['id'];
		$this->Issue['deadline'] = array( 'value' => $this->Issue['deadline'], 'error' => $this->Issue['dl_icon']['error'] ); // trick: pack two values into one
		$this->IssueFields->beginNode( 'IssueDef', $this->Issue['name'], $this->Issue, $tag );
		$this->IssueFields->endNode();
		$this->Issue['deadline'] = $this->Issue['deadline']['value']; // restore trick

		$this->loadCategoriesDeadlines();
		$this->loadStatusesDeadlines();
	}
	
	/**
	 * Called by HtmlAnyForm class when it is time to grab user typed data from the HTML form.
	 * See loadIssueDeadline() for what is done.
	 */
	public function fetchData()
	{
		$this->loadIssueDeadline();
	}
	
	public function drawHeader()
	{
		return '';   
	}
	
	/**
	 * Called by HtmlAnyForm class when it is time to draw the HTML document body.
	 * It returns the HTML form with all widgets and buttons on it to show admin user.
	 *
	 * @return string HTML fragment
	 */
	public function drawBody()
	{
		require_once BASEDIR.'/server/admin/global_inc.php'; // inputvar(), formvar()

		// HTML page header
		$result = '<script language="javascript" src="../../server/utils/javascript/HtmlTree.js"></script>';
		$jsInc = file_get_contents( BASEDIR.'/server/utils/javascript/DatePicker.js' );
		$result .= '<script language="javascript">'. HtmlDocument::buildDocument( $jsInc, false ) .'</script>';
		$result .= '<body onload="javascript:initTree(\''.$this->SectionsTree->Name.'\',0); javascript:initTree(\''.$this->StatesTree->Name.'\',3);">';
		$result .= '<form id="'.$this->Name.'" type="submit" method="post">';

		// Issue properties		
		$title = BizResources::localize('ISSUE') . ' ' . BizResources::localize('DEADLINES');
		$result .= '<h2><img src="../../config/images/issue.gif">&nbsp;'.formvar($title).'</h2>';
		$result .= $this->IssueFields->drawBody();	
		$result .= $this->UpdateIssueAction->drawBody();
		$result .= $this->DeleteDeadlinesAction->drawBody();

		// Categories deadlines
		$title = BizResources::localize('SECTION') . ' ' . BizResources::localize('DEADLINES');
		$result .= '<h3><img src="../../config/images/deadline_24.gif">&nbsp;'.formvar($title).'</h3>';
		$result .= $this->SectionsTree->drawBody();
		$result .= $this->UpdateSectionsAction->drawBody();

		// Statuses deadlines
		$title = BizResources::localize('STATE') . ' ' . BizResources::localize('DEADLINES');
		$result .= '<h3><img src="../../config/images/deadline_24.gif">&nbsp;'.formvar($title).'</h3>';
		$result .= $this->StatesTree->drawBody();
		$result .= $this->UpdateStatesAction->drawBody();

		// HTML page footer
		$result .= "</form>";
		$result .= '<a href="hppublissues.php?id=' . $this->IssueId . '">'.
						'<img src="../../config/images/back_32.gif" border ="0" title="'.BizResources::localize('ACT_BACK').'" width="32" height="32">'.
					'</a>'.
					'<script language="javascript">document.forms[\''.$this->Name.'\'][0].focus();</script>'. // Bug fix: document.forms[0].pname does not exist!
					'</td></tr>';

		$result .= "</body>";
		return $result;
	}
	
	/**
	 * Determines an icon to show if deadline has been changed manually.
	 * When deadline is set to the weekend, a holiday or too late in time, a warning is determined too.
	 *
	 * Too late in time could be anything of the following:
	 * - an issue deadline after production
	 * - a category deadline after issue deadline
	 * - a status deadline after category deadline or predecessor status
	 *
	 * A list of properties is required, with following boolean members:
	 * - 'deadline_edit' => true when deadline is manually changed.
	 * - 'deadline_toolate' => true when deadline is 'too late' (see above).
	 * - 'deadline_nonworking' => true when deadline is in a weekend/holiday.
	 *
	 * @param array $props List of properties. See above.
	 * @param string $context 'issue', 'section' or 'status'. Used to determine warning message.
	 * @return array with two keys: 'title' => warning message, 'icon' => relative path to icon file
	 */
	private function getDeadlineIcon( $props /*, $context */)
	{
		$retVal = array();
		if( $props['deadline_edit'] ) {
			if( $props['deadline_nonworking'] ) {
				$retVal['title'] = BizResources::localize('INVALID_DATE'); // TODO: be more specific, such as 'Non-working day (holiday or weekend).'
				$retVal['icon'] =  '../../config/images/plugin_error_16.gif';
				$retVal['error'] = $retVal['title'];
			} else if( $props['deadline_toolate'] ) {
			 	$retVal['title'] = BizResources::localize('BEYOND_DATE');
			 	/*switch( $context ) {
			 		case 'issue':  $msg = BizResources::localize('PUBLICATION_DATE'); break;
			 		case 'section':$msg = BizResources::localize('ISSUE_DEADLINE'); break;
			 		case 'status': $msg = BizResources::localize('SECTION_DEADLINE'); break;
			 	}
			 	$retVal['title'] .= '. ('.BizResources::localize('DEADLINE_TILL').': '.$msg.')';
			 	*/
				$retVal['icon'] = '../../config/images/plugin_error_16.gif';
				$retVal['error'] = $retVal['title'];
			} else {
				$retVal['title'] = BizResources::localize('ACT_OK');
				$retVal['icon'] = '../../config/images/lockedit_16.gif';
				$retVal['error'] = '';
			}
		} else {
			$retVal['title'] = '';
			$retVal['icon'] = '';
			$retVal['error'] = '';
		}
		return $retVal;
	}
}
