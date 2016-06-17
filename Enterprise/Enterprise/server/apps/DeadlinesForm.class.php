<?php
require_once BASEDIR.'/server/utils/htmlclasses/HtmlAnyForm.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlTree.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlCombo.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlButton.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlText.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDateTimeField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDiffTimeField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlColorField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlIconField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlStringField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlAction.class.php';
require_once BASEDIR.'/server/bizclasses/BizDeadlines.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';

class DeadlinesForm extends HtmlAnyForm
{
	public $PublId;
	public $IssueId;
	private $Publication;
	private $Issue;
	private $DisplayLevel;
	private $Time;
	private $Export2csvClicked;

	private $PublicationDateField;
	private $DeadlineField;
	private $TimeField;
	private $PublCombo;
	private $IssueCombo;
	private $DisplayLevelCombo;
	private $RefreshButton;
	private $DeadlineTree;
	private $SelectionTree;

	function __construct($owner, $name)
	{
		HtmlAnyForm::__construct($owner, $name);   
		$this->PublId = isset($_REQUEST['publid']) ? intval($_REQUEST['publid']) : 0;
		$this->IssueId = isset($_REQUEST['issueid']) ? intval($_REQUEST['issueid']) : 0;
		$this->Time = isset($_REQUEST['time']) ? $_REQUEST['time'] : '';
		if( trim($this->Time) == '' ) {
			$this->Time = strftime('%Y-%m-%dT%T');
		}
		$this->DisplayLevel = isset($_REQUEST['displaylevel']) ? $_REQUEST['displaylevel'] : '';
	}

	public function createFields()
	{
		$this->PublicationDateField = new HtmlDateTimeField($this, 'publicationdate', false, false);
		$this->DeadlineField = new HtmlDateTimeField($this, 'deadline', false, false);
		$this->TimeField = new HtmlDateTimeField($this, 'time', false, false);
		$this->PublCombo = new HtmlCombo($this, 'publcombo');
		$this->IssueCombo = new HtmlCombo($this, 'issuecombo');

		$this->DisplayLevelCombo = new HtmlCombo($this, 'DisplayLevelId');
		$this->RefreshButton = new HtmlButton($this, 'Refresh', BizResources::localize('ACT_REFRESH'));
		$this->DeadlineTree = new HtmlTree($this, 'DeadlinesTree');
		$this->DeadlineTree->DisplayLevel = $this->DisplayLevel;

		$this->SelectionTree = new HtmlTree($this, 'SelectionTree');
		$this->SelectionTree->addKeyCol(175, '&nbsp;');
		$this->SelectionTree->addValueCol(500, '');
		if (!self::$Application->InReport) {
			$this->SelectionTree->addField(BizResources::localize('PUBLICATION'), $this->PublCombo, true);
			$this->SelectionTree->addField(BizResources::localize('ISSUE'), $this->IssueCombo, true);
		}
		$this->SelectionTree->addField(BizResources::localize('PUBLICATION_DATE'), $this->PublicationDateField, true);
		$this->SelectionTree->addField(BizResources::localize('DEADLINE'), $this->DeadlineField, true);
		$this->SelectionTree->addField(BizResources::localize('TIME'), $this->TimeField, true);            
		$this->SelectionTree->HeaderColor = '#aaaaaa';

		$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
		$colorfield = new HtmlColorField($this->DeadlineTree, 'deadlinecolorfield');
		$colorfield->setBoxSize( $boxSize );
		$deadlinefield = new HtmlDateTimeField($this->DeadlineTree, 'deadlinefield', false, true);
		// $difftimefield = new HtmlDiffTimeField($this->DeadlineTree, 'difftimefield', false, true, false, true);
		// $typefield = new HtmlStringField($this->DeadlineTree, 'typenamefield', false, true);
		$typeiconfield = new HtmlIconField($this->DeadlineTree, 'typeiconfield');
		$statusfield = new HtmlStringField($this->DeadlineTree, 'statusfield', false, true);
		$issuesfield = new HtmlStringField($this->DeadlineTree, 'issuesfield', false, true);

		$this->DeadlineTree->addExpandCol(50, '');
		$this->DeadlineTree->addCol(20, $typeiconfield, 'objtypecolumn', 'typeicon', '', false);
		$this->DeadlineTree->addNameCol(200, '');
		$this->DeadlineTree->addCol(20, $colorfield, 'statuscolorcolumn', 'StateColor', '&nbsp;', false);
		$this->DeadlineTree->addCol(100, $statusfield, 'statusnamecolumn', 'State', BizResources::localize('STATE'), true);
		$this->DeadlineTree->addCol(20, $colorfield, 'deadlinecolorcolumn', 'deadlinecolor', '&nbsp;', false);
		$this->DeadlineTree->addCol(100, $deadlinefield, 'deadlinenamecolumn', 'Deadline', BizResources::localize('DEADLINE'), true);
		$this->DeadlineTree->addCol(150, $issuesfield, 'issuescolumn', 'Issues', BizResources::localize('ISSUES'), true);
	}
	
	public function drawHeader()
	{
		$result = $this->DeadlineField->drawheader();
		$result .= $this->DeadlineTree->drawHeader();    
		return $result;
	}
	
	public function drawBody()
	{
		if ($this->Export2csvClicked == 1)
		{
			$this->exportDeadlineTree2csv();
		}

		$result = '';            
		$formname = $this->Name;
		$treename = $this->DeadlineTree->Name;
		$result .= "<body onload=\"javascript:initTree('$treename',2)\">";
		$result .= "<form id=$formname>";

		if (!self::$Application->InReport)
		{
			$result .= '<h2><img src="../../config/images/deadline_32.gif">Issue deadline status</h2>';
		}
		$result .= $this->SelectionTree->drawBody() . "\n";
		$result .= $this->DeadlineTree->drawBody() . "\n";
		$result .= $this->drawExport2csvButton();
		$result .= "<input type=hidden id='issueid' name='issueid' value=$this->IssueId></input>";
		$result .= "</form>";
		$result .= "</body>";
		return $result;
	}

	public function drawExport2csvButton()
	{
		$formname = $this->Name;
		$result = '';
		$result .= '<script language="javascript">';
		$result .= 'function export2csv() {';
		$result .= 'var exportbutton = document.getElementById("export2csvbutton"); ';
		$result .= 'exportbutton.value = "clicked"; ';
		$result .= "document.forms.$formname.submit(); ";
		$result .= '}';
		$result .= '</script>';
		$buttontext = '&nbsp;' . BizResources::localize("CUST_CSVBUTTON");
		$result .= "<a href=\"javascript:export2csv()\" title='$buttontext'><img align='absmiddle' src='../../config/images/save_16.gif' border=0>$buttontext</a><input type=hidden id='export2csvbutton' name='export2csvbutton' value=''>";
		return $result;
	}

	public function exportDeadlineTree2csv()
	{
		$csv = '';
		// >>> Bug fix: Escape double quotes (stringToCSV) for Excel support, and localize column names
		$csv .= '"'.$this->stringToCSV(BizResources::localize('OBJ_ID')).'"' . "\t";
		$csv .= '"'.$this->stringToCSV(BizResources::localize('OBJ_TYPE')).'"' . "\t";
		$csv .= '"'.$this->stringToCSV(BizResources::localize('OBJ_NAME')).'"' . "\t";
		$csv .= '"'.$this->stringToCSV(BizResources::localize('SECTION')).'"' . "\t";
		$csv .= '"'.$this->stringToCSV(BizResources::localize('STATE')).'"' . "\t";
		$csv .= '"'.$this->stringToCSV(BizResources::localize('DEADLINE')).'"' . "\t";
		$csv .= '"'.$this->stringToCSV(BizResources::localize('ISSUES')).'"' . "\n";
		// <<<

		$csv .= $this->exportDeadlineNode2csv($this->DeadlineTree->RootNode);
		$csv =  iconv ( 'UTF-8', 'UTF-16LE//IGNORE', $csv ); // Bug fix: Convert to UTF-16 for Excel support
		$now = strftime('%D');
		$filename = "deadlines.$now.csv";
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=$filename"); 
		header("Content-Description: PHP Generated Data"); 
		print chr(255).chr(254).$csv; // Bug fix: Add prefix for Excel support
		exit;
	}

	private function stringToCSV( $str )
	{
		return str_replace( '"', '""', $str ); 
	}

	public function exportDeadlineNode2csv($curnode)
	{
		$result = '';
		switch ($curnode->Level)
		{
			case 2:
			{
				$deadlineField = new HtmlDateTimeField( $this, 'tempname', false, true );
				$deadlineField->setValue( $curnode->Values['Deadline'] );
				$deadlineDisplay = $deadlineField->getDisplayValue();
				
				// >>> Bug fix: Escape double quotes (stringToCSV) for Excel support
				$result .= '"' . $this->stringToCSV($curnode->Values['ID']) . '"' . "\t";
				$result .= '"' . $this->stringToCSV($curnode->Values['Type']) . '"' .  "\t";
				$result .= '"' . $this->stringToCSV($curnode->Values['Name']) . '"' .  "\t";
				$result .= '"' . $this->stringToCSV($curnode->ParentNode->Values['section']) . '"' .  "\t"; // Bug fix: Section name was not resolved
				$result .= '"' . $this->stringToCSV($curnode->Values['State']) . '"' .  "\t";
				$result .= '"' . $this->stringToCSV($deadlineDisplay) . '"' .  "\t";
				$result .= '"' . $this->stringToCSV($curnode->Values['Issues']) . '"' .  "\n";
				// <<<
			}
		}

		$children = $curnode->Children;
		foreach ($children as $childnode)
		{
			$result .= $this->exportDeadlineNode2csv($childnode);
		}
		return $result;
	}

	public function listDisplayLevels()
	{
		$result = array( 1 => 'Edition', 'Section', 'Workflow', 'State' );
		return $result;
	}

	public function fetchData()
	{
		if( $this->PublId === 0 && $this->IssueId === 0) return; // nothing to do
		$export2csvbuttonvalue = isset($_REQUEST['export2csvbutton']) ? $_REQUEST['export2csvbutton'] : '';
		$this->Export2csvClicked = ($export2csvbuttonvalue == 'clicked');
		if ($this->IssueId === 0)
		{
			$this->Publication = BizDeadlines::getPublication($this->PublId);
			$this->IssueId = $this->Publication[$this->PublId]['currentissue'];
			$this->Issue = BizDeadlines::getIssue($this->IssueId);
		}
		else
		{
			$this->Issue = BizDeadlines::getIssue($this->IssueId);
			$this->PublId = $this->Issue['publication'];
			$this->Publication = BizDeadlines::getPublication($this->PublId);
		}
		$this->PublicationDateField->setValue($this->Issue['publdate']);
		$this->DeadlineField->setValue($this->Issue['deadline']);
		$this->TimeField->setValue($this->Time);

		$publs = BizDeadlines::listPublications();
		$this->PublCombo->setOptions($publs);
		$issues = BizDeadlines::listPublicationIssues( $this->PublId );
		$this->IssueCombo->setOptions($issues);
		$this->IssueCombo->setValue($this->IssueId);
		$this->PublCombo->setValue($this->PublId);
		$this->fetchSectionsTree();
	}
	
	public function fetchSectionsTree()
	{    
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';

		$sections = BizDeadlines::listSectionDefs($this->PublId, $this->IssueId);
		$statedefs = BizDeadlines::listStateDefs($this->PublId, $this->IssueId);

		foreach ($sections as $sectionid => $section) {
		
			// Retrieve objects from DB that are assigned to this issue/category
			try {
				$queryParams = array();
				$queryParams[] = new QueryParam( 'IssueId', '=', $this->IssueId, false );
				$queryParams[] = new QueryParam( 'SectionId', '=', $sectionid, false );
				$minProps = array( 'ID', 'Type', 'Name', 'Deadline', 'StateId', 'State', 'StateColor', 'CategoryId', 'Category', 'IssueIds', 'Issues' );
				$queryOrder = array( new QueryOrder( 'Name', true ) ); // sort on Name property
				$service = new WflQueryObjectsService();
				$request = new WflQueryObjectsRequest( $this->Owner->Ticket, $queryParams, 1, 0, false, $queryOrder, $minProps, null );
				$response = $service->execute( $request );
			} catch( BizException $e ) {
				$e = $e;
				$response = null;
			}

			// Determine column indexes to work with
			$indexes = array_combine( array_values($minProps), array_fill(1,count($minProps), -1) );
			if( $response ) foreach( array_keys($indexes) as $colName ) {
				foreach( $response->Columns as $index => $column ) {
					if( $column->Name == $colName ) {
						$indexes[$colName] = $index;
						break; // found
					}
				}
			}

			// Build the HTML table/tree			
			$temp = BizDeadlines::getIssueSection($this->IssueId, $sectionid);
			$section['Deadline'] = $temp['deadline'];
			$section['colorindex'] = trim($section['Deadline']) == '' ? 0 : 1;
			$section['typeicon'] = '../../config/images/foldr_16.gif';
			$sectionnode = $this->DeadlineTree->beginNode('Section', $section['section'], $section);

			if( $response && $response->Rows ) foreach( $response->Rows as $row ) {
				$object = array();
				foreach( $minProps as $minProp ) {
					$object[$minProp] = $row[$indexes[$minProp]];
				}
				if( empty($object['Deadline']) ) {
					$object['Deadline'] = $section['Deadline'];
				}
				$object['typename'] = $row[$indexes['Type']];
				$object['typeicon'] = BizObject::getTypeIcon( $object['typename'] );
				$stateid = $row[$indexes['StateId']];
				if( $stateid == -1 ) { // personal status
					$object['State'] = BizResources::localize('PERSONAL_STATE');
					$object['statecode'] = 0;
					$object['StateColor'] = PERSONAL_STATE_COLOR;
				} else {
					$object['State'] = $statedefs[$stateid]['state'];
					$object['statecode'] = $statedefs[$stateid]['code'];
				}
				BizDeadlines::filterStateDefsByType($statedefs, $object['Type']);
				if( trim($object['Deadline']) == '' ) {
					$object['colorindex'] = 0; // no deadline defined for issue/category/object
				} else {
					$object['colorindex'] = $this->calcColorIndex($object['Deadline'], $this->Time);
				}
				$objectnode = $this->DeadlineTree->beginNode('Object', $object['Name'], $object);
				$objectnode = $this->DeadlineTree->endNode();
				$objectnode->Values['deadlinecolor'] = $this->toDeadlineColor($object['colorindex']);
				if ($object['colorindex'] > $section['colorindex']) {
					$section['colorindex'] = $object['colorindex'];
				}
			}
			
			$sectionnode = $this->DeadlineTree->endNode();
			$sectionnode->Values['deadlinecolor'] = $this->toDeadlineColor($section['colorindex']);
		}
	}



	public function calcColorIndex($deadline)
	{
		$timebeforedeadline = DateTimeFunctions::diffTime( 
					DateTimeFunctions::iso2time( $deadline ), 
					DateTimeFunctions::iso2time( $this->Time ) );
		if ($timebeforedeadline > DEADLINE_WARNTIME)
		{
			$result = 1;
		}
		else if ($timebeforedeadline > 0)
		{
			$result = 2;
		}
		else
		{
			$result = 3;
		}
		return $result;
	}
	
	public function toDeadlineColor($colorindex)
	{
		$retVal = '';
		switch( $colorindex ) {
			case 0: $retVal = '#AAAAAA'; break; // gray
			case 1: $retVal = '#4FFF4F'; break; // green
			case 2: $retVal = '#FFFF4F'; break; // orange
			case 3: $retVal = '#FF0000'; break; // red
		}
		return $retVal;
	}
	
	public function execAction()
	{
		return;   
	}
}
?>
