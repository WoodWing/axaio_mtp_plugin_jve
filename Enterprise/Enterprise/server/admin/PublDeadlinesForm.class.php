<?php
require_once BASEDIR . '/server/utils/htmlclasses/HtmlAnyForm.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlTree.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlCombo.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlDateTimeField.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlDiffTimeField.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlAction.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlIconField.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlStringField.class.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR . '/server/bizclasses/BizDeadlines.class.php';

class PublDeadlinesForm extends HtmlAnyForm
{
	public $IssueId = 0;
	public $Issue = null;
	public $PublId = 0;
	public $Publication = null;
	public $PublCombo = null;
	public $PublFields = null;
	//BZ#7480 public $EditionsTree = null;
	public $SectionsTree = null;
	public $StatesTree = null;
	public $SectionStatesTree = null;
	//BZ#7480 public $UpdateEditionsAction = null;
	public $UpdateSectionsAction = null;
	public $UpdateStatesAction = null;
	public $UpdateSectionStatesAction = null;
	public $ResetSectionStatesAction = null;
	
	function __construct($owner, $name)
	{
		HtmlAnyForm::__construct($owner, $name);
		$this->IssueId = isset($_REQUEST['issueid']) ? intval($_REQUEST['issueid']) : 0;
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
		}
	}
	
	function createFields()
	{
		$this->PublCombo = new HtmlCombo($this, 'publcombo');
		$this->PublFields = new HtmlTree($this, BizResources::localize('PUBLICATION'), true);
		$this->PublFields->addKeyCol(200, '');
		$this->PublFields->addValueCol(500, '&nbsp;');
		$this->PublFields->addField(BizResources::localize('PUBLICATION'), $this->PublCombo, true);
		
		$difftimefield = new HtmlDiffTimeField($this, 'difftime');
		$beforefield = new HtmlStringField($this, 'before', false, true);

		//BZ#7480
		/*
		$this->EditionsTree = new HtmlTree($this, 'Editions', false);
		$this->EditionsTree->addNameCol(200, BizResources::localize('EDITION'));
		$this->EditionsTree->addCol(200, $difftimefield, 'difftimecol', 'deadlinerelative', BizResources::localize('TIME'));
		$this->EditionsTree->addCol(300, $beforefield, 'beforecol', 'before', '', true);
		*/
		
		$this->SectionsTree = new HtmlTree($this, 'Sections', false);
		$this->SectionsTree->addNameCol(200, BizResources::localize('SECTION'));
		$this->SectionsTree->addCol(200, $difftimefield, 'difftimecol', 'deadlinerelative', BizResources::localize('TIME'));
		$this->SectionsTree->addCol(300, $beforefield, 'beforecol', 'before', '', true);

		$this->StatesTree = new HtmlTree($this, 'States', false);
		$this->StatesTree->addExpandCol(40,'');
		$this->StatesTree->addNameCol(160, BizResources::localize('WFL_WORKFLOW') . ' / ' . BizResources::localize('STATE'));
		$this->StatesTree->addCol(200, $difftimefield, 'difftimecol', 'deadlinerelative', BizResources::localize('TIME'));
		$this->StatesTree->addCol(300, $beforefield, 'beforecol', 'before', '', true);

		$this->SectionStatesTree = new HtmlTree($this, 'SectionStates', false);
		$this->SectionStatesTree->addExpandCol(40,'');
		$this->SectionStatesTree->addNameCol(160, BizResources::localize('SECTION') . ' / ' . BizResources::localize('STATE'));
		$this->SectionStatesTree->addCol(200, $difftimefield, 'difftimecol', 'deadlinerelative', BizResources::localize('TIME'));
		$this->SectionStatesTree->addCol(300, $beforefield, 'beforecol', 'before', '', true);
		
		//BZ#7480 $this->UpdateEditionsAction = new HtmlAction($this, 'updateeditions', BizResources::localize('ACT_UPDATE') . ' ' . BizResources::localize('EDITIONS'), null, true);
		$this->UpdateSectionsAction = new HtmlAction($this, 'updatesections', BizResources::localize('ACT_UPDATE') . ' ' . BizResources::localize('SECTIONS'), null, true);
		$this->UpdateStatesAction = new HtmlAction($this, 'updatestates', BizResources::localize('ACT_UPDATE') . ' ' . BizResources::localize('STATES'), null, true);
		$this->UpdateSectionStatesAction = new HtmlAction($this, 'updatesectionstates', BizResources::localize('ACT_UPDATE') . ' ' . BizResources::localize('SECTIONS') . ' / ' . BizResources::localize('STATES') , null, true);
		$this->ResetSectionStatesAction = new HtmlAction($this, 'resetsectionstates', BizResources::localize('RECALC') . ' ' . BizResources::localize('SECTIONS') . ' / ' . BizResources::localize('STATES'), null, true);
	}

	function execAction()
	{
		//BZ#7480
		/*
		if (isset($_REQUEST['updateeditions']) && $_REQUEST['updateeditions'] )
		{
			return self::execUpdateEditions();
		}
		*/
		if (isset($_REQUEST['updatesections']) && $_REQUEST['updatesections'])
		{
			return self::execUpdateSections();
		}
		if (isset($_REQUEST['updatestates']) && $_REQUEST['updatestates'])
		{
			return self::execUpdateStates();
		}
		if (isset($_REQUEST['updatesectionstates']) && $_REQUEST['updatesectionstates'])
		{
			return self::execUpdateSectionStates();
		}
		if (isset($_REQUEST['resetsectionstates']) && $_REQUEST['resetsectionstates'])
		{
			return self::execResetSectionStates();
		}
		return null;
	}

	//BZ#7480
	/*
	function execUpdateEditions()
	{
		require_once BASEDIR.'/server/secure.php';
		checkSecure('publadmin');
		checkPublAdmin( $this->PublId );

		$updates = $this->EditionsTree->requestValues();
		foreach ($updates as $updateid => $update)
		{
			$updateidarray = explode('~', $updateid);
			$type = $updateidarray[0];
			
			if ($type == 'EditionDef')
			{
				$editiondefid = $updateidarray[1];
				$fields = array();
				$fields['deadlinerelative'] = $update['deadlinerelative'];
				BizDeadlines::updateEditionDef($editiondefid, $fields);
			}
		}
	}
	*/
	
	function execUpdateSections()
	{
		require_once BASEDIR.'/server/secure.php';
		checkSecure('publadmin');
		checkPublAdmin( $this->PublId );

		$updates = $this->SectionsTree->requestValues();
		foreach ($updates as $updateid => $update)
		{
			$updateidarray = explode('~', $updateid);
			$type = $updateidarray[0];
			
			if ($type == 'SectionDef')
			{
				$sectiondefid = $updateidarray[1];
				$fields = array();
				$fields['deadlinerelative'] = $update['deadlinerelative'];
				BizDeadlines::updateSectionDef($sectiondefid, $fields);
			}
		}
	}

	function execUpdateStates()
	{
		require_once BASEDIR.'/server/secure.php';
		checkSecure('publadmin');
		checkPublAdmin( $this->PublId );

		$updates = $this->StatesTree->requestValues();
		foreach ($updates as $updateid => $update)
		{
			$updateidarray = explode('~', $updateid);
			$type = $updateidarray[0];
			
			if ($type == 'StateDef')
			{
				$statedefid = $updateidarray[1];
				$fields = array();
				$fields['deadlinerelative'] = $update['deadlinerelative'];
				BizDeadlines::updateStateDef($statedefid, $fields);
			}
		}
		self::execResetSectionStates();
	}
	
	function execUpdateSectionStates()
	{
		require_once BASEDIR.'/server/secure.php';
		checkSecure('publadmin');
		checkPublAdmin( $this->PublId );

		$updates = $this->SectionStatesTree->requestValues();
		foreach ($updates as $updateid => $update)
		{
			$updateidarray = explode('~', $updateid);
			$type = $updateidarray[0];
			
			if ($type == 'SectionStateDef')
			{
				$sectiondefid = $updateidarray[1];
				$statedefid = $updateidarray[2];
				$fields = array();
				$fields['deadlinerelative'] = $update['deadlinerelative'];
				BizDeadlines::insertSectionStateDef($sectiondefid, $statedefid, $fields, true);
			}
		}
	}

	function execResetSectionStates()
	{
		require_once BASEDIR.'/server/secure.php';
		checkSecure('publadmin');
		checkPublAdmin( $this->PublId );

		$sectiondefs = BizDeadlines::listSectionDefs($this->PublId, $this->IssueId);
		$statedefs = BizDeadlines::listStateDefs($this->PublId, $this->IssueId, 'DESC');
		$values = array();
		foreach( array_keys($sectiondefs) as $sectiondefid ) {
			foreach( $statedefs as $statedefid => $statedef ) {
				$values['deadlinerelative'] = $statedef['deadlinerelative'];
				BizDeadlines::insertSectionStateDef($sectiondefid, $statedefid, $values, true);
			}
		}
	}
	
	function fetchData()
	{
		$this->PublCombo->setOptions(BizDeadlines::listPublications());
		$this->PublCombo->setValue($this->PublId);

		//BZ#7480 $editiondefs = BizDeadlines::listEditionDefs($this->PublId, $this->IssueId);
		$sectiondefs = BizDeadlines::listSectionDefs($this->PublId, $this->IssueId);
		$workflowdefs = BizDeadlines::listWorkflowDefs($this->PublId, $this->IssueId);
		$statedefs = BizDeadlines::listStateDefs($this->PublId, $this->IssueId, 'DESC');
		
		//BZ#7480 self::fetchEditionsTree($editiondefs);
		self::fetchSectionsTree($sectiondefs);
		self::fetchStatesTree($workflowdefs, $statedefs);
		self::fetchSectionStatesTree($sectiondefs, $workflowdefs, $statedefs);
	}
	
	//BZ#7480
	/*
	function fetchEditionsTree($editiondefs)
	{
		foreach ($editiondefs as $editiondefid => $editiondef)
		{
			$editiondef['before'] = BizResources::localize('BEFORE') . ' ' . BizResources::localize('ISSUE_DEADLINE');
			$this->EditionsTree->beginNode('EditionDef', $editiondef['name'], $editiondef, 'EditionDef' . '~' . $editiondefid);
			$this->EditionsTree->endNode();
		}			
	}
	*/
	
	function fetchSectionsTree($sectiondefs)
	{
		foreach ($sectiondefs as $sectiondefid => $sectiondef)
		{
			$sectiondef['before'] = BizResources::localize('BEFORE') . ' ' . BizResources::localize('ISSUE_DEADLINE');
			$this->SectionsTree->beginNode('SectionDef', $sectiondef['section'], $sectiondef, 'SectionDef' . '~' . $sectiondefid);
			$this->SectionsTree->endNode();
		}			
	}

	function fetchStatesTree($workflowdefs, $statedefs)
	{
		foreach ($workflowdefs as $workflowdef)
		{
			$workflownode = $this->StatesTree->beginNode('WorkflowDef', BizDeadlines::getTranslatedWorkflowName($workflowdef['name']), $workflowdef, '');
			$workflownode->ReadOnly = true;
			$before = BizResources::localize('BEFORE') . ' ' . BizResources::localize('SECTION_DEADLINE');
			foreach ($statedefs as $statedefid => $statedef)
			{
				if ($statedef['type'] == $workflowdef['name'])
				{
					$statedef['before'] = $before;
					$before = BizResources::localize('BEFORE') . ' ' . $statedef['state'] . ' ' . mb_strtolower(BizResources::localize('DEADLINE'), 'UTF-8');
					$this->StatesTree->beginNode('StateDef', $statedef['state']. ' ' . mb_strtolower(BizResources::localize('DEADLINE'), 'UTF-8'), $statedef, 'StateDef' . '~' . $statedefid);
					$this->StatesTree->endNode();
				}
			}
			$this->StatesTree->endNode();
		}			
	}
	
	function fetchSectionStatesTree($sectiondefs, $workflowdefs, $statedefs)
	{
		foreach ($sectiondefs as $sectiondefid => $sectiondef)
		{
			$sectionstatedefs = BizDeadlines::listSectionStateDefs($sectiondefid, true);
			$sectiondef['before'] = BizResources::localize('BEFORE') . ' ' . BizResources::localize('ISSUE_DEADLINE');
			$sectionnode = $this->SectionStatesTree->beginNode('SectionDef', $sectiondef['section'], $sectiondef, '');
			$sectionnode->ReadOnly = true;
			foreach ($workflowdefs as $workflowdef)
			{
				$workflownode = $this->SectionStatesTree->beginNode('WorkflowDef', BizDeadlines::getTranslatedWorkflowName($workflowdef['name']), $workflowdef, '');
				$workflownode->ReadOnly = true;
				$before = BizResources::localize('BEFORE') . ' ' . BizResources::localize('SECTION_DEADLINE');
				foreach ($statedefs as $statedefid => $statedef)
				{
					if ($statedef['type'] == $workflowdef['name'])
					{
						$statedef['before'] = $before;
						$before = BizResources::localize('BEFORE') . ' ' . $statedef['state']  . ' ' . mb_strtolower(BizResources::localize('DEADLINE'), 'UTF-8');
						
						foreach ($sectionstatedefs as $sectionstatedef)
						{
							if ($sectionstatedef['state'] == $statedefid)
							{
								$statedef['deadlinerelative'] = $sectionstatedef['deadlinerelative'];
								break;
							}
						}
						$this->SectionStatesTree->beginNode('SectionStateDef', $statedef['state']. ' ' . mb_strtolower(BizResources::localize('DEADLINE'), 'UTF-8'), $statedef, 'SectionStateDef' . '~' . $sectiondefid . '~' . $statedefid);
						$this->SectionStatesTree->endNode();
					}
				}
				$this->SectionStatesTree->endNode();
			}
			$this->SectionStatesTree->endNode();
		}						
	}
	
	function drawHeader()
	{
		return '';   
	}
	
	function drawBody()
	{
		require_once BASEDIR.'/server/admin/global_inc.php'; // inputvar(), formvar()
		
		$result = '';
		if (is_null($this->Publication))
		{
			$result = BizResources::localize('ERR_NOTFOUND') . ": " . BizResources::localize('PUBLICATION') . ' ' . $this->PublId;
			return $result;
		}

		$statestree = $this->StatesTree->Name;
		$sectionstatestree = $this->SectionStatesTree->Name;
		$publicationtitle = BizResources::localize('RELATIVE_DEADLINE') . " '" . $this->Publication['publication'] . "'";
		$result .= '<script language="javascript" src="../../server/utils/javascript/HtmlTree.js"></script>';
		$jsInc = file_get_contents( BASEDIR.'/server/utils/javascript/DatePicker.js' );
		$result .= '<script language="javascript">'. HtmlDocument::buildDocument( $jsInc, false ) .'</script>';
		$result .= '<body onload="javascript:initTree(\''.$statestree.'\',0);initTree(\''.$sectionstatestree.'\',0);">';
		$result .= '<form id="'.$this->Name.'" type="submit" method="post">';
		$result .= '<h2><img src="../../config/images/pub_small.gif">&nbsp;'.formvar($publicationtitle).'</h2>';
		//$result .= $this->PublFields->drawBody();

		//BZ#7480
		/*
		$editionstitle = BizResources::localize('EDITIONS');
		$result .= "<h3><img src=\"../../config/images/deadline_24.gif\">&nbsp;$editionstitle</h3>";
		$result .= $this->EditionsTree->drawBody();
		$result .= $this->UpdateEditionsAction->drawBody();
		*/
		
		$sectionstitle = BizResources::localize('SECTIONS');
		$result .= '<h3><img src="../../config/images/deadline_24.gif">&nbsp;'.formvar($sectionstitle).'</h3>';
		$result .= $this->SectionsTree->drawBody();
		$result .= $this->UpdateSectionsAction->drawBody();
		
		$statestitle = BizResources::localize('STATES');
		$result .= '<h3><img src="../../config/images/deadline_24.gif">&nbsp;'.formvar($statestitle).'</h3>';
		$result .= $this->StatesTree->drawBody();
		$result .= $this->UpdateStatesAction->drawBody();
		
		$sectionstatestitle = BizResources::localize('SECTIONS') . ' / ' . BizResources::localize('STATES');
		$result .= '<h3><img src="../../config/images/deadline_24.gif">&nbsp;'.formvar($sectionstatestitle).'</h3>';
		$result .= $this->SectionStatesTree->drawBody();
		$result .= $this->UpdateSectionStatesAction->drawBody();
		$result .= "</form>";
		
		if (empty($this->IssueId)) {
			$result .= '<a href="hppublications.php?id=' . $this->PublId . '">';
		}
		else {
			$result .= '<a href="hppublissues.php?id=' . $this->IssueId . '">';
		}
		$result .= '<img src="../../config/images/back_32.gif" border ="0" title="'.BizResources::localize('ACT_BACK').'" width="32" height="32"></a>'.
					'<script language="javascript">document.forms[\''.$this->Name.'\'][0].focus();</script>'. // Bug fix: document.forms[0].pname does not exist!
					'</td></tr>';

		$result .= "</body>";
		return $result;
	}
}
