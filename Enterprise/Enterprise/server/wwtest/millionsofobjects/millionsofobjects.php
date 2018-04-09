<?php
/**
 * @package 	Enterprise
 * @subpackage 	MillionofObjects
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This is a test tool that allows you to create millions of objects in the Enterprise system.
 * The goal is to load the system with plenty of data after which the performance can be tested in
 * several ways using other test tools, such as the speedtest.php.
 *
 * The test tool shows a list of processes that can be freely configured in the config.xml file.
 * For each process, a progress bar is shown, indicating how many objects it is about to create in
 * the Enterprise database.
 *
 * The test tool returns a HTML page that is shown in the web browser. The loaded HTML file runs
 * as a stand-alone client; It talks through Ajax to this PHP server module running at the back end.
 * The service works like an application facade and returns home brewed XML packages to the HTML client
 * application, which then updates the progress bar with arrived progress info.
 */
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
require_once BASEDIR.'/server/secure.php';
set_time_limit(3600);

// Start session.
session_start();

$testApp = new TestMillionObjects();
$testApp->handle();

class TestMillionObjects
{
	private $publicationId = null;
	private $issueIds = array();
	private $categoryIds = array();
	private $templates = null;
	private $templateIds = null;
	private $startDate = null;
	private $endDate = null;
	private $number = null;
	private $lastObjCount = 0;
	private $currentObjectDate = null;

	/**
	 * @todo: Write function header.
	 */
	public function createOrGetTemplates(  DOMDocument &$xmlDoc )
	{
		$templateIds = array();
		//$xmlDoc = $this->getXMLDoc( false, 'CreateDossiersWithPublishForms' );
		$xPath = new DOMXPath( $xmlDoc );
		$query = '/MillionsOfObjects/Template/Id';
		$entries = $xPath->query( $query );
		if( $entries->length > 0 ) {
			foreach ( $entries as $templateId ) {
				$tid = $templateId->nodeValue;
				$templateIds[] = $tid;
			}
			$this->templateIds = $templateIds;
		}

		if (!is_null($this->templateIds) && is_null($this->templates)) {
			// Retrieve the Publish Form Template from DB.
			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$service = new WflGetObjectsService();

			$request = new WflGetObjectsRequest();
			$request->Ticket = $_SESSION['ticket'];
			$request->IDs = $this->templateIds;
			$request->Lock = false;
			$request->Rendition = 'none';
			$request->RequestInfo = null;
			$request->HaveVersions = null;
			$request->Areas = null;
			$request->EditionId = null;

			$resp = $service->execute($request);

			if ($resp->Objects) foreach ($resp->Objects as $object) {
				if (!is_array($this->templates)) { $this->templates = array(); }
				$this->templates[] = $object;
			}
		}

		if (is_null($this->templates)) {
			$templateCount = count($this->issueIds);
			$this->templates = array();

			if ($templateCount == 0) {
				// no templates to create.
				return;
			}

			$publication = $this->publicationId;
			$ticket = $_SESSION['ticket'];

			// Get All available Targets, and set their editions.
			$templateTargets = $this->buildObjectTargets('all');
			$editions = $this->buildObjectTargetEditions( 'all' );
			foreach( $templateTargets as $target ) {
				$target->Editions = $editions;
			}

			// Go through each of the Targets and for each create a PublishFormTemplate.
			foreach( $templateTargets as $target ) {
				// Check if a template already exists.
				$name = uniqid( 'PublishFormTemplate_' );

				// Flush the states cache to ensure we retrieve the latest from the Database.
				require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
				BizWorkflow::flushStatesCache();

				$state = new State();
				$statesIds = $this->getStateIds( 'PublishFormTemplate' );
				$state->Id = $statesIds[0];
				$state->Name = DBWorkflow::getStatusName( $statesIds[0] );

				$categoryId = array_rand( $this->categoryIds ); // pick random category (from creations made before)
				$category = new Category( $this->categoryIds[$categoryId], '' );

				// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
				$objectPublication = new Publication();
				$objectPublication->Id = $publication;
				$objectPublication->Name = '';

				// MetaData
				$metaData = new MetaData();
				$metaData->BasicMetaData = new BasicMetaData(null,null, $name,'PublishFormTemplate',$objectPublication,$category,null);
				$metaData->RightsMetaData = new RightsMetaData();
				$metaData->SourceMetaData = new SourceMetaData();
				$metaData->ContentMetaData = new ContentMetaData('Created by MillionsOfObjects.');
				$metaData->WorkflowMetaData = new WorkflowMetaData(null,null,null,null,null,null,null,$state);
				$metaData->ExtraMetaData = array();

				// Get The Target.
				$templateObj = new Object();
				$templateObj->MetaData = $metaData;
				$templateObj->Targets = array($target);

				// Test creating an object.
				require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
				$service = new WflCreateObjectsService();
				$req = new WflCreateObjectsRequest();
				$req->Ticket = $ticket;
				$req->Lock = false;
				$req->Objects = array($templateObj);
				$response = $service->execute( $req );

				$object = $response->Objects[0];

				$this->updateObjectDate($object);

				$this->templates[] = $object;

				$xmlMilObj = $xmlDoc->documentElement;
				$xmlTemplate = $xmlDoc->createElement( 'Template' );
				$xmlMilObj->appendChild( $xmlTemplate );
				$this->createTextElem( $xmlDoc, $xmlTemplate, 'Id', $object->MetaData->BasicMetaData->ID );
			}
		}
	}

	/**
	 * @todo: Write function header.
	 */
	public static function createPlacementRelationsForForm( $formId, $placementObjId )
	{
		$relation = new Relation();
		$relation->Parent = $formId;
		$relation->Child = $placementObjId;
		$relation->Type = 'Placed';
		$placement = new Placement();
		$placement->Page = null;
		$placement->Element = null;
		$placement->ElementID = '';
		$placement->FrameOrder = 0;
		$placement->FrameID = '209';
		$placement->Left = 0;
		$placement->Top = 0;
		$placement->Width = 4;
		$placement->Height = 384;
		$placement->Overset = null;
		$placement->OversetChars = null;
		$placement->OversetLines = null;
		$placement->Layer = null;
		$placement->Content = '';
		$placement->Edition = null;
		$placement->ContentDx = 0;
		$placement->ContentDy = 0;
		$placement->ScaleX = null;
		$placement->ScaleY = null;
		$placement->PageSequence = null;
		$placement->PageNumber = null;
		$placement->Tiles = array();
		$placement->FormWidgetId = 'C_DUMMY_WIDGETID_' . $placementObjId;
		$relation->Placements = array( $placement );
		$relation->ParentVersion = null;
		$relation->ChildVersion = null;
		$relation->Rating = null;
		$relation->Targets = null;
		$relations = array( $relation );

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$service = new WflCreateObjectRelationsService();
		$req = new WflCreateObjectRelationsRequest();
		$req->Ticket = $_SESSION['ticket'];
		$req->Relations = $relations;
		$response = $service->execute( $req );
		$relation = $response->Relations[0];

		return $relation;
	}

	/**
	 * @todo: Write function header.
	 */
	private function createPublishFormDossier()
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';

		$publication = $this->publicationId;

		// Retrieve the State.
		$ticket = $_SESSION['ticket'];

		$state = new State();
		$statesIds = $this->getStateIds( 'Dossier' );
		$state->Id = $statesIds[0];
		$state->Name = DBWorkflow::getStatusName( $statesIds[0] );

		$categoryId = array_rand( $this->categoryIds ); // pick random category (from creations made before)
		$category = new Category( $this->categoryIds[$categoryId], '' );

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$objectPublication = new Publication();
		$objectPublication->Id = $publication;
		$objectPublication->Name = '';

		$dossierName = uniqid('Dossier_');
		// MetaData
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData(null,null,$dossierName, 'Dossier',$objectPublication, $category, null);
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData('Temporary dossier to contain a publishForm.');
		$metaData->WorkflowMetaData = new WorkflowMetaData(null,null,null,null,null,null,null,$state);
		$metaData->ExtraMetaData = array();

		$targets = $this->buildObjectTargets('all');
		$editions = $this->buildObjectTargetEditions( 'all' );
		foreach( $targets as $target ) {
			$target->Editions = $editions;
		}

		$dosObject = new Object();
		$dosObject->MetaData = $metaData;
		$dosObject->Targets = $targets;

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$service = new WflCreateObjectsService();
		$req = new WflCreateObjectsRequest();
		$req->Ticket = $ticket;
		$req->Lock = false;
		$req->Objects = array($dosObject);
		$response = $service->execute( $req );

		$object = $response->Objects[0];

		// determine the Date to use for the Object and all inside the Object.
		$this->currentObjectDate = $this->determinePublishFormDate();

		$this->updateObjectDate($object);

		return $object;
	}

	/**
	 * @todo: Write function header.
	 */
	private function createPublishForm( /** @noinspection PhpLanguageLevelInspection */ Object $template, Object $dossier)
	{
		$ticket = $_SESSION['ticket'];
		$object = new Object();

		// Create the Object MetaData.
		$object->Targets = null;

		$namePrefix = $dossier->MetaData->BasicMetaData->Name . '-'
			. $dossier->Targets[0]->PubChannel->Name . '-';

		// Construct the MetaData for this object, make sure we overrule the name with a unique one.
		$object->MetaData 	= new MetaData();
		$object->MetaData->BasicMetaData = new BasicMetaData();
		$object->MetaData->BasicMetaData->Name =  uniqid($namePrefix);

		$object->Files     	= array();

		$relations = array();
		$relations[] = new Relation($template->MetaData->BasicMetaData->ID, null, 'InstanceOf');
		$relations[] = new Relation($dossier->MetaData->BasicMetaData->ID, null, 'Contained', null, null, null, null,
			null, array($template->Targets[0]));
		$object->Relations = $relations;

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$service = new WflCreateObjectsService();
		$req = new WflCreateObjectsRequest();
		$req->Ticket = $ticket;
		$req->Lock = false;
		$req->Objects = array($object);
		$response = $service->execute( $req );

		$object = $response->Objects[0];

		$this->updateObjectDate($object);

		return $object;
	}

	/**
	 * @todo: Write function header.
	 */
	private function createPublishFormObjects( DOMDocument &$xmlDoc )
	{
		// Retrieve all relevant data for the creation process.
		$configXmlDoc = $this->getConfigXmlDoc();
		$xpath = new DOMXPath( $configXmlDoc );
		$query = '/MillionsofObjectsConfig/Steps/Step[@name="CreateDossiersWithPublishForms"]/ObjectConfig';
		$entries = $xpath->query( $query );
		$objConfig = $entries->item(0);

		//$assignToIssue = strtolower($objConfig->getAttribute('assignToIssue')); // Possible values: all, none, random
		//$assignToEdition = strtolower($objConfig->getAttribute('assignToEdition')); // Possible values: all, none, random
		//$formsPerDossier = strtolower($objConfig->getAttribute('formsPerDossier'));
		$articleCount = strtolower($objConfig->getAttribute('articlesPerForm'));
		$imageCount = strtolower($objConfig->getAttribute('imagesPerForm'));
		$this->startDate = strtolower($objConfig->getAttribute('from'));
		$this->endDate = strtolower($objConfig->getAttribute('to'));
		$this->number = strtolower($objConfig->getAttribute('count'));
		//$templatePrefix = strtolower($objConfig->getAttribute('templatePrefix'));

		// Determine the number of Templates to create, which will be equal to the number of issues created in a previous
		// step in the creation process.

		$this->currentObjectDate = $this->startDate . 'T00:00:00';
		$this->createOrGetTemplates( $xmlDoc );

		$ticket = $_SESSION['ticket'];

		// Create a Dossier.
		$dossier = $this->createPublishFormDossier();

		// For each Template create a PublishForm and add it to the Dossier.
		$publishForms = array();
		foreach( $this->templates as $template ) {
			$publishForms[] = $this->createPublishForm( $template, $dossier );
		}

		// Create the Articles in the Dossier and place them on the Form
		if ($articleCount > 0) {
			for ($i = 0; $i < $articleCount; $i++) {
				// Create the Article.
				$metadata = $this->buildObjectMetaData( 'Article' );
				$files = $this->buildObjectFiles($metadata);

				// Contained in Dossier, and place on all the forms.
				$relations = array();
				$relations[] = new Relation($dossier->MetaData->BasicMetaData->ID, null, 'Contained' );

				$targets = $this->buildObjectTargets( 'all' );

				$editions = $this->buildObjectTargetEditions( 'all' );
				foreach( $targets as $target ) {
					$target->Editions = $editions;
				}

				$article = new Object();
				$article->MetaData = $metadata;
				$article->Targets = $targets;
				$article->Files = $files;

				// Create the Articles.
				require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
				$service = new WflCreateObjectsService();
				$req = new WflCreateObjectsRequest();
				$req->Ticket = $ticket;
				$req->Lock = false;
				$req->Objects = array($article);
				$response = $service->execute( $req );

				$article = $response->Objects[0];

				$this->updateObjectDate($article);

				// Place the Article.
				foreach ($publishForms as $form) {
					$this->createPlacementRelationsForForm( $form->MetaData->BasicMetaData->ID, $article->MetaData->BasicMetaData->ID );
				}

			}
		}

		// Create images.
		if ($imageCount > 0) {
			for ($i = 0; $i < $articleCount; $i++) {
				// Create the Article.
				$metadata = $this->buildObjectMetaData( 'Image' );

				$files = $this->buildObjectFiles($metadata);

				// Contained in Dossier, and place on all the forms.
				$relations = array();
				$relations[] = new Relation($dossier->MetaData->BasicMetaData->ID, null, 'Contained' );

				$targets = $this->buildObjectTargets( 'all' );

				$editions = $this->buildObjectTargetEditions( 'all' );
				foreach( $targets as $target ) {
					$target->Editions = $editions;
				}

				$object = new Object();
				$object->MetaData = $metadata;
				$object->Targets = $targets;
				$object->Files = $files;

				// Create the image.
				require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
				$service = new WflCreateObjectsService();
				$req = new WflCreateObjectsRequest();
				$req->Ticket = $ticket;
				$req->Lock = false;
				$req->Objects = array($object);
				$response = $service->execute( $req );

				$object = $response->Objects[0];

				$this->updateObjectDate($object);

				// Place the Article.
				foreach ($publishForms as $form) {
					$this->createPlacementRelationsForForm( $form->MetaData->BasicMetaData->ID, $object->MetaData->BasicMetaData->ID );
				}
			}
		}

		// Update the XML for the object.
		$xmlMilObj = $xmlDoc->documentElement;
		$xmlObject = $xmlDoc->createElement( $dossier->MetaData->BasicMetaData->Type );
		$xmlMilObj->appendChild( $xmlObject );
		$this->createTextElem( $xmlDoc, $xmlObject, 'Id', $dossier->MetaData->BasicMetaData->ID );
	}

	/**
	 * Updates an Object's created and modified date to the given date to the current object date.
	 *
	 * @param Object $object
	 * @return Object
	 */
	private function updateObjectDate( $object )
	{
		// Update the date on the Object to be as the begin date for the Objects.
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		$date = $this->currentObjectDate;

		// Update Object Creation date.
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename("objects");
		$sql = 'UPDATE ' . $db . ' SET `created`=\'' . $date . '\', `modified`=\'' . $date . '\' WHERE `id`='
			. $object->MetaData->BasicMetaData->ID;
		$dbDriver->query($sql);

		$object->MetaData->WorkflowMetaData->Created = $date;
		return $object;
	}

	/**
	 * Determines a date by means of the input parameters.
	 *
	 * @param $currentCount The Current Object count.
	 * @param $startDate The start date in Y-m-dTH:i:s format.
	 * @param $endDate The end date in Y-m-dTH:i:s format.
	 * @param $numberOfDossiers The number of Dossiers to be created.
	 *
	 * @return string The Date in Y-m-dTH:i:s evenly spaced.
	 */
	public function determinePublishFormDate()
	{
		$secondsEndDate = intval(strtotime($this->endDate));
		$secondsStartDate = intval(strtotime($this->startDate));
		$number = intval($this->lastObjCount);

		if ($this->lastObjCount == 0) {
			$seconds = $secondsStartDate;
		} else {
			$interval = ceil(($secondsEndDate - $secondsStartDate) / $number);
			$seconds = $secondsStartDate + ($interval * $this->lastObjCount);
		}

		$date = date('Y-m-d\TH:i:s', $seconds);

		return $date;
	}

	/**
	 * Handles the incoming request.
	 */
	public function handle()
	{
		// Dispatch command.
		$command = isset($_REQUEST['command']) ? $_REQUEST['command'] : 'LoadPage';
		switch( $command ) {
			case 'LoadPage': // Request to return the html page (which then will fire InitPage command).
				$debug	= LogHandler::getDebugLevel() != 'NONE';
				$profile= (defined('PROFILELEVEL') && PROFILELEVEL != 0);
				$logSql = (defined('LOGSQL') && LOGSQL != false);
				$serviceLog = (defined('LOG_INTERNAL_SERVICES') && LOG_INTERNAL_SERVICES != false);
				$dpsLog = (defined('LOG_DPS_SERVICES') && LOG_DPS_SERVICES != false);
				$serviceValidation = (defined('SERVICE_VALIDATION') && SERVICE_VALIDATION != false);
				$linkFiles = (defined('HTMLLINKFILES') && HTMLLINKFILES != false);
				$err	= null;
				$errMsg = '';
				if( $debug || $profile || $logSql || $serviceLog || $dpsLog || $serviceValidation || $linkFiles ) {
					$errMsg .=
						'<p>Warning: Currently, one or more logging, profiling or validation '.
						'features of Enterprise Server are enabled, which has a significant '.
						'negative impact to the performance of this tool. '.
						'Please set DEBUGLEVELS to \'NONE\', PROFILELEVEL to 0, LOG_INTERNAL_SERVICES to false, '.
						'LOG_DPS_SERVICES to false, SERVICE_VALIDATION to false, HTMLLINKFILES to false '.
						'and LOGSQL to false in the configserver.php file.</p>';
				}
				
				require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
				$plugins = array( 'PreviewMetaPHP', 'SipsPreview', 'SolrSearch', 'Tika' );
				foreach( $plugins as $plugin ) {
					if( BizServerPlugin::isPluginActivated( $plugin ) ) {
						$errMsg .=
							'<p>Warning: Currently the Server Plug-in "'.$plugin.'" is enabled, '.
							'which has a significant negative impact to the performance of this tool. '.
							'Please disable the plug-in.</p>';
					}
				}
				
				try {
					$this->logOn();
				} catch( BizException $e ) {
					$err = 1;
					$errMsg .= '<p>'.$this->getErrorMessage($e).'</p>';
					$errMsg .= '<p>Please check the option TESTSUITE in configserver.php.</p>';
				}
				
				try {
					$this->validateConfigXml();
				} catch( BizException $e ) {
					$err = 1;
					$errMsg .= '<p>Bad settings found in config.xml.</p>';
					$errMsg .= '<p>'.$this->getErrorMessage($e) . '</p>';
					$errMsg .= '<p>Please check your config.xml.</p>';
				}				
		
				require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
				$tpl = HtmlDocument::loadTemplate( dirname(__FILE__). '/millionsofobjects.htm' );
				$tpl = str_replace("<!--VAR:NAME-->", $this->buildHTMLFromXML('title') ,$tpl); // Title
				$tpl = str_replace("<!--VAR:WARNING-->", $errMsg ,$tpl); 				// Warning
				if( !$err ) {
					$tpl = str_replace("<!--VAR:STEPS-->", $this->buildHTMLFromXML('steps') ,$tpl);// Steps
					$tpl = str_replace("<!--VAR:STARTSTOP-->", $this->buildStartStopBtn('steps') ,$tpl);// Steps
				}
				print HtmlDocument::buildDocument( $tpl, true, null, false, true );
				break;
		
			case 'InitPage': // Request config.xml
				header( 'Content-Type: text/xml' );
				print $this->buildHTMLFromXML('all');
				break;
		
			case 'processStep':
				$name 	= $_REQUEST['stepName'];
				$iter 	= $_REQUEST['stepSize'];
				$max 	= $_REQUEST['maxObjCount'];
				$lastObjCount	= $_REQUEST['lastObjCount'];
		
				$this->getPublicationId();
				$this->getIssueIds();
				$this->getEditionIds();
				$this->getCategoryIds();

				$this->lastObjCount = $lastObjCount;
		
				$newDoc = ($lastObjCount) == 0 ? true : false;
				$xmlDoc = $this->getXMLDoc( $newDoc, $name );

				$errMsg = '';
				for( $i = 0; $i<$iter && $i<($max-$lastObjCount); $i++ ) {
					try {
						$method_name = 'test_'.$name;
						if( method_exists( $this, $method_name ) ) {
							$this->$method_name( $xmlDoc );
						}

						// Up the count.
						$this->lastObjCount = $lastObjCount + 1;
					} catch( BizException $e ) {
						$errMsg = $this->getErrorMessage($e);
						break;
					}
				}

				$this->writeResultInXML( $xmlDoc, $name );
				$lastObjCount += $i;
		
				$done = (($max == $i) || $lastObjCount == $max );
				$response = $this->getProgressAsXml( $name, $max, $lastObjCount, $done, $errMsg );
				header( 'Content-Type: text/xml' );
				print $response;
				break;
		}
	}

	/**
	 * Builds a start/stop button for the page.
	 *
	 * @return string
	 */
	function buildStartStopBtn()
	{
		$txt =	'<tr>'.
					'<td colspan="4">'.
						'<input type="button" name="startBtn" id="startBtn" value="<!--RES:ACT_START-->" onclick="confirmMsg();" />&nbsp;'.
						'<input type="button" name="stopBtn" id="stopBtn" value="<!--RES:ACT_STOP-->" onclick="stopProcessing();" />&nbsp;'.
					'</td>'.
				'</tr>';
		return $txt;
	}

	/**
	 * Returns an error message based on the thrown BizException.
	 *
	 * @param BizException $e
	 * @return string
	 */
	function getErrorMessage( BizException $e )
	{
		$errMsg = 'FATAL ERROR: ';
		if( $e->getType() == 'MillionObjects' ) {
			$errMsg .= $e->getDetail();
		}
		else {
			$errMsg .= $e->getMessage();
		}
		return $errMsg;
	}

	/**
	 * Loads the config.xml file into a DOM document.
	 *
	 * @return DOMDocument
	 */
	function getConfigXmlDoc()
	{
		$xmlFile = dirname(__FILE__).'/config.xml';
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML( file_get_contents( $xmlFile ) );
		return $xmlDoc;
	}

	/**
	 * Builds a web page based on the settings in config.xml.
	 *
	 * @param null $HTMLNode
	 * @return string
	 */
	function buildHTMLFromXML( $HTMLNode = null)
	{
		$suiteOpts = unserialize( TESTSUITE );
		$xmlDoc = $this->getConfigXmlDoc();
		$xpath = new DOMXPath( $xmlDoc );
	
		switch ( $HTMLNode ) {
			case 'title':
				$query = '/MillionsofObjectsConfig/Title';
				$entries = $xpath->query( $query );
				if( $entries->length > 0 ) {
					$txt = $entries->item(0)->nodeValue;
				}
				break;
			case 'steps':
				$txt = '';
				$count = 1;
				$query = '/MillionsofObjectsConfig/Steps/Step';
				$entries = $xpath->query( $query );
				foreach( $entries as $step ) {
					$name 	= $step->getAttribute( 'name' );
					$display= $step->getAttribute( 'display' );
					$total 	= $step->getAttribute( 'count' );
					//$size 	= $step->getAttribute( 'stepsize' );
					$info 	= $xpath->query('Info', $step);
					$stepInfo= $info->item(0)->nodeValue;
					$stepInfo= str_replace('%count%', $total, $stepInfo);
					$stepInfo= str_replace('%UseBrand%', $suiteOpts['Brand'], $stepInfo);
					$txt .= '<tr>'.
							'	<td colspan="4" align="left"><span class="apptitletext">Step '.$count.': '.$display . '</span></td>'.
							'</tr>'.
							'<tr>'.
							'	<td><input type="checkbox" id="checkbox_'.$name.'" checked=""></td>'.
							'	<td><img onclick="javascript:toggleInfo(\''.$name.'\');" src="../../../config/images/wwtest/details.png" title="Details"/>&nbsp;&nbsp;&nbsp;&nbsp;</td>'.
							'	<td><span id="' . $name . '_created">0</span></td>'.
							'	<td><div style="width:400px" id="ProgressBar_'.$name.'"></div></td>'.
							'   <td><span id="ProgressLabel_'. $name.'"></span></td>'.							
							'	<td><span id="' . $name . '_count">' . $total .'</span></td>'.
							'	<td><span id="' . $name . '_spinningBall"></span></td>'.
							'</tr>'.
							'<tr id="stepinfo_' . $name . '" style="display:none;" valign="top">'.
							'	<td/>'.
							'	<td bgcolor="#EEEEEE" colspan="4"><table width="100%">'.
							'    <tr valign="top"><td><b>Info: </b></td><td>' . $stepInfo . '</td></tr></table>'.
							'</tr>'.
							'<tr>'.
							'	<td colspan="4" align="left"><span id="' . $name . '_errorReport"></span></td>'.
							'</tr>';
					$count++;
				}
				break;
			case 'all':
				$txt = $xmlDoc->saveXML();
				break;
		}
		return $txt;
	}

	/**
	 * Tests the LogOn procedure.
	 *
	 * @throws BizException
	 */
	private function logOn()
	{
		$pubId = null;
		$suiteOpts = unserialize( TESTSUITE );

		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$service= new WflLogonService();
		$req 	= new WflLogOnRequest( $suiteOpts['User'], $suiteOpts['Password'], '', '', '', '', 'Millions of Objects', SERVERVERSION, '', '', false );
		$resp 	= $service->execute( $req );
		$ticket = $resp->Ticket;
   		$_SESSION['ticket'] = $ticket;
		session_write_close();
		if( count($resp->Publications) > 0 ) {
			foreach( $resp->Publications as $pub ) {
				if( $pub->Name == $suiteOpts['Brand'] ) {
					$pubId = $pub->Id;
					$this->publicationId = $pubId;
					$xmlDoc = $this->getXMLDoc( true );
					$xmlMilObj = $xmlDoc->documentElement;
					$xmlPub = $xmlDoc->createElement( 'Publication' );
					$xmlMilObj->appendChild( $xmlPub );
					$this->createTextElem( $xmlDoc, $xmlPub, 'Id', $pubId );
					foreach( $pub->States as $state ) {
						$xmlState = $xmlDoc->createElement( 'State' );
						$xmlMilObj->appendChild( $xmlState );
						$this->createTextElem( $xmlDoc, $xmlState, 'Id', 	$state->Id );
						$this->createTextElem( $xmlDoc, $xmlState, 'Type', 	$state->Type );
					}
					$this->writeResultInXML( $xmlDoc, 'Publication' );
					break;
				}
			}
			if( !$pubId ) {
				throw new BizException( 'ERR_NOTFOUND', 'MillionObjects',  'Could not find the configured Brand: "'.$suiteOpts['Brand'].'"' );
			}
		} else {
			throw new BizException( 'ERR_NOTFOUND', 'MillionObjects', 'Could not find any publication at LogOn response.' );
		}
	}

	/**
	 * Validates  the configuration settings in config.xml.
	 *
	 * A BizException is thrown when a bad configuration is found.
	 *
	 * @return void.
	 */
	private function validateConfigXml()
	{
		// Collect the configurations from Config.xml
		$configXmlDoc = $this->getConfigXmlDoc();
		$xpath = new DOMXPath( $configXmlDoc );
		//$query = '/MillionsofObjectsConfig/Steps/Step[@name="'.$testStepName.'"]/ObjectConfig';
		$query = '/MillionsofObjectsConfig/Steps/Step';
		$entries = $xpath->query( $query );
		$configs = array();

		for( $counter=0; $counter<$entries->length; $counter++) {
			$currNode = $entries->item( $counter );
			$actionName = $currNode->getAttribute( 'name' );
			$configs[ $actionName ]['count'] = $currNode->getAttribute( 'count' );
			if( $actionName == 'CreateDossiers' || $actionName == 'CreateArticles' || $actionName == 'CreateDossiersWithPublishForms' ) {
				$objConfigQuery = '/MillionsofObjectsConfig/Steps/Step[@name="'.$actionName.'"]/ObjectConfig';
				$objConfigs = $xpath->query( $objConfigQuery );
				$configs[ $actionName ]['assignToIssue'] = 
								strtolower( $objConfigs->item(0)->getAttribute('assignToIssue') );
				$configs[ $actionName ]['assignToEdition'] = 
								strtolower( $objConfigs->item(0)->getAttribute('assignToEdition') );
				
				if( $actionName == 'CreateArticles' ) {
					$configs[ $actionName ]['containInDossier'] = 
								strtolower( $objConfigs->item(0)->getAttribute('containInDossier') );
				}

				if ($actionName == 'CreateDossiersWithPublishForms') {
					//formsPerDossier="1" imagesPerForm="1" articlesPerForm="1" from="2012-01-01" to="2013-12-12"/>
					$configs[ $actionName ]['formsPerDossier'] = $objConfigs->item(0)->getAttribute('formsPerDossier');
					$configs[ $actionName ]['imagesPerForm'] = $objConfigs->item(0)->getAttribute('imagesPerForm');
					$configs[ $actionName ]['articlesPerForm'] = $objConfigs->item(0)->getAttribute('articlesPerForm');
					$configs[ $actionName ]['from'] = $objConfigs->item(0)->getAttribute('from');
					$configs[ $actionName ]['to'] = $objConfigs->item(0)->getAttribute('to');
				}
			}
		}
		
		// Validate the configurations
		// When creatingDossier or Articles, all Issues,Editions & Categories have to be enabled.
		$createObjects = ( $configs['CreateDossiers']['count'] > 0 )||( $configs['CreateArticles']['count'] > 0);
		$createIssCatEdition = ( $configs['CreateIssues']['count'] > 0 && 
							   $configs['CreateEditions']['count'] > 0 &&
							   $configs['CreateCategories']['count'] > 0 );
		if( $createObjects && !$createIssCatEdition ) {
			throw new BizException( null, 'Client', 'MillionObjects',  
			'CreateIssues, CreateEditions and CreateCategories must have "count" set to more than 0.' );
		}
		$possibleValues = array( 'all', 'random', 'none' );
		if( !in_array( $configs['CreateDossiers']['assignToIssue'], $possibleValues ) ||
		    !in_array( $configs['CreateDossiers']['assignToEdition'], $possibleValues ) ||
			!in_array( $configs['CreateDossiersWithPublishForms']['assignToIssue'], $possibleValues ) ||
			!in_array( $configs['CreateDossiersWithPublishForms']['assignToEdition'], $possibleValues ) ||
			!in_array( $configs['CreateArticles']['assignToIssue'], $possibleValues ) ||
			!in_array( $configs['CreateArticles']['assignToEdition'], $possibleValues ) ||
			!in_array( $configs['CreateArticles']['containInDossier'], $possibleValues ) ) {
			throw new BizException( null, 'Client', 'MillionObjects',  
				'assignToIssue, assignToEdition and containInDossier each can only have one of the ' .
				'value from "'. implode( ',', $possibleValues ).'".' );
		}
		$containInDossier = $configs['CreateArticles']['containInDossier'] == 'all' ||
						 $configs['CreateArticles']['containInDossier'] == 'random';
		if( $containInDossier && 
			 ( $configs['CreateArticles']['assignToIssue'] != 'none' || 
			   $configs['CreateArticles']['assignToEdition'] != 'none'  ) ) {
			throw new BizException( null, 'Client', 'MillionObjects',  
				'In CreateArticles, when "containInDossier" is set to "all" or "random", ' .
				'"assignToIssue" and "assignToEdition" should be set to "none".' );
		}
	}	

	/**
	 * Tests creating issues.
	 *
	 * @param DOMDocument $xmlDoc
	 */
	private function test_CreateIssues( DOMDocument &$xmlDoc )
	{
		$newiss 			= new AdmIssue();
		$newiss->Name 		= uniqid('Issue_'); // Use uniqid, else duplicate issue error raise
		$newiss->Description= 'Created by Millions Objects Tools';
		$newiss->Activated	= true;

		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$service = new AdmCreateIssuesService();
		$req	 = new AdmCreateIssuesRequest( $_SESSION['ticket'], array(), $this->publicationId, null, array($newiss) );
		$resp	 = $service->execute($req);
		$issue 	 = $resp->Issues[0];

		$xmlMilObj = $xmlDoc->documentElement;
		$xmlIssue = $xmlDoc->createElement( 'Issue' );
		$xmlMilObj->appendChild( $xmlIssue );
		$this->createTextElem( $xmlDoc, $xmlIssue, 'Id', $issue->Id );
	}

	/**
	 * Tests creating editions.
	 *
	 * @param DOMDocument $xmlDoc
	 */
	private function test_CreateEditions( DOMDocument &$xmlDoc )
	{
		$newEdition = new AdmEdition();
		$newEdition->Name = uniqid('Edition_');
		$newEdition->Description = 'Created by Millions Objects Tools';

		require_once BASEDIR.'/server/services/adm/AdmCreateEditionsService.class.php';
		$service = new AdmCreateEditionsService();
		$req = new AdmCreateEditionsRequest();
		$req->Ticket = $_SESSION['ticket'];
		$req->PublicationId = $this->publicationId;
		$req->PubChannelId = $this->getPubChannelId();
		$req->IssueId = 0;
		$req->Editions = array( $newEdition );
		$resp = $service->execute($req);
		$edition = $resp->Editions[0];

		$xmlMilObj = $xmlDoc->documentElement;
		$xmlEdition = $xmlDoc->createElement( 'Edition' );
		$xmlMilObj->appendChild( $xmlEdition );
		$this->createTextElem( $xmlDoc, $xmlEdition, 'Id', $edition->Id );
	}

	/**
	 * Tests creating dossiers.
	 *
	 * @param DOMDocument $xmlDoc\
	 */
	private function test_CreateCategories( DOMDocument &$xmlDoc )
	{
		$newsec = new AdmSection();
		$newsec->Name 		= uniqid('Category_');
		$newsec->Description= 'Created by Millions Objects Tools';

		require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';
		$service	= new AdmCreateSectionsService();
		$req	 	= new AdmCreateSectionsRequest( $_SESSION['ticket'], array(), $this->publicationId, 0, array($newsec) );
		$resp	 	= $service->execute($req);
		$categories	= $resp->Sections;
		$category 	= $categories[0];

		$xmlMilObj = $xmlDoc->documentElement;
		$xmlCategory= $xmlDoc->createElement( 'Category' );
		$xmlMilObj->appendChild( $xmlCategory );
		$this->createTextElem( $xmlDoc, $xmlCategory, 'Id', $category->Id );
	}

	/**
	 * Tests creating dossiers.
	 *
	 * @param DOMDocument $xmlDoc
	 */
	private function test_CreateDossiers( DOMDocument &$xmlDoc )
	{
		$this->createObjects( $xmlDoc, 'CreateDossiers', 'Dossier' );
	}

	/**
	 * Tests creating Articles.
	 *
	 * @param DOMDocument $xmlDoc
	 */
	private function test_CreateArticles( DOMDocument &$xmlDoc )
	{
		$this->createObjects( $xmlDoc, 'CreateArticles', 'Article' );
	}

	/**
	 * Tests creating Dossiers with PublishForms.
	 *
	 * @param DOMDocument $xmlDoc
	 */
	private function test_CreateDossiersWithPublishForms( DOMDocument &$xmlDoc )
	{
		$this->createPublishFormObjects($xmlDoc);
	}

	/**
	 * Creates an Object.
	 *
	 * @param DOMDocument $xmlDoc
	 * @param $testStepName
	 * @param $objectType
	 * @throws BizException
	 */
	private function createObjects( DOMDocument &$xmlDoc, $testStepName, $objectType )
	{
		// Read object configuration to determine what/how to create objects
		$configXmlDoc = $this->getConfigXmlDoc();
		$xpath = new DOMXPath( $configXmlDoc );
		$query = '/MillionsofObjectsConfig/Steps/Step[@name="'.$testStepName.'"]/ObjectConfig';
		$entries = $xpath->query( $query );
		$objConfig = $entries->item(0);

		$assignToIssue = strtolower($objConfig->getAttribute('assignToIssue')); // Possible values: all, none, random
		$assignToEdition = strtolower($objConfig->getAttribute('assignToEdition')); // Possible values: all, none, random
		$containInDossier = strtolower($objConfig->getAttribute('containInDossier')); // Possible values: all, none, random
		
		// Check required results of previous steps		
		if( $assignToIssue == 'all' || $assignToIssue == 'random' ) {
			if( empty($this->issueIds) ) {
				throw new BizException( null, 'Client', 'MillionObjects',  'No Issue being created on previous step' );
			}
		}
		if( $assignToEdition == 'all' || $assignToEdition == 'random' ) {
			if( $assignToIssue == 'none' ) {
				throw new BizException( null, 'Client', 'MillionObjects',  'No Issue assigned to ' .
										'object "'.$objectType.'". Please check on "assignToIssue" '.
										'option under "'.$testStepName.'" in config.xml.' );
			}
			if( empty( $this->editionIds ) )  {
				throw new BizException( null, 'Client', 'MillionObjects',  'No Edition being created on previous step' );
			}
		}
		if( $containInDossier == 'all' || $containInDossier == 'random' ) {
			if( $assignToIssue != 'none' ) {
				// When article needs to be created in the dossier, no new issue should be targeted
				// to the article. (Dossier is already targeted to issue(s))
				throw new BizException( null, 'Client', 'MillionObjects',  'Bad configurations: '.
									'When "containInDossier" is set to "'.$containInDossier.'", '.
									'"assignToIssue" and "assignToEdition" should set to "none" '. 
									'for "'. $testStepName . '"');
			}
			$this->getDossierIds();
			if( empty($this->dossierIds) ) {
				throw new BizException( null, 'Client', 'MillionObjects',  'No Dossier being created on previous step' );
			}
		}
		if( empty($this->categoryIds) ) {
			throw new BizException( null, 'Client', 'MillionObjects',  'No Category being created on previous step' );
		}

		// Build the Object in memory
		$object = new Object();
		$object->MetaData = $this->buildObjectMetaData( $objectType );
		if( $containInDossier == 'all' || $containInDossier == 'random' ) {
			$object->Relations = $this->builObjectRelations( $containInDossier );
		}
		if( $assignToIssue == 'all' || $assignToIssue == 'random' ) {
			$object->Targets = $this->buildObjectTargets( $assignToIssue );			
		}
		if( $assignToEdition == 'all' || $assignToEdition == 'random' ) {
			$editions = $this->buildObjectTargetEditions( $assignToEdition );
			foreach( $object->Targets as $target ) {
				$target->Editions = $editions;		
			}
		}
		$object->Files = $this->buildObjectFiles( $object->MetaData );

		// Create the Object at DB
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$service= new WflCreateObjectsService();
		$req 	= new WflCreateObjectsRequest( $_SESSION['ticket'], false, array($object) );
		$resp 	= $service->execute( $req );
		$objects= $resp->Objects;
		$object = $objects[0];

		// Store the results at XML cache
		$xmlMilObj = $xmlDoc->documentElement;
		$xmlObject = $xmlDoc->createElement( $objectType );
		$xmlMilObj->appendChild( $xmlObject );
		$this->createTextElem( $xmlDoc, $xmlObject, 'Id', $object->MetaData->BasicMetaData->ID );
	}

	/**
	 * Builds the MetaData element for a workflow Object.
	 *
	 * @param string $objectType
	 */
	private function buildObjectMetaData( $objectType )
	{
		$metaData = new MetaData();
		$categoryId = array_rand( $this->categoryIds ); // pick random category (from creations made before)
		
		$basMD = new BasicMetaData();
		$basMD->Name = uniqid( $objectType.'_' );
		$basMD->Type = $objectType;
		$basMD->Publication = new Publication( $this->publicationId, '' );
		$basMD->Category = new Category( $this->categoryIds[$categoryId], '' );
		$metaData->BasicMetaData = $basMD;


		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		$wflState = new State();
		$statesIds = $this->getStateIds( $objectType );
		$wflState->Id = $statesIds[0];
		$wflState->Name = DBWorkflow::getStatusName( $statesIds[0] );
		$wflMD = new WorkflowMetaData();
		$wflMD->State = $wflState;
		$metaData->WorkflowMetaData = $wflMD;

		if( $objectType == 'Article' ) {
			$content = 'To temos aut explabo. Ipsunte plat. Em accae eatur? Ihiliqui oditatem. Ro ipicid '.
				'quiam ex et quis consequae occae nihictur? Giantia sim alic te volum harum, audionseque '.
				'rem vite nobitas perrum faccuptias sunt fugit eliquatint velit a aut milicia consecum '.
				'veribus auda ides ut quia commosa quam et moles iscil mo conseque magnim quis ex ex eaquamet '.
				'ut adi dolor mo odis magnihi ligendit ut lam reperibusam quatumquam labor renis pe con eos '.
				'magnima gnatiur sitaepeles quatia namus ni aut adit at ad quundem laudia qui ut ratempe '.
				'rnatestorro te por alis acidunt volore nobit harciminum re eatus repudiatem ame prati bere '.
				'cus minveliquis serum, ute velecus cipiciur, occum nulpario quat fugitatur, nihillu ptatqui '.
				'ventibus doluptatur? Dus alique nonectoribus inciend elenim di sunt que mollis autempo ribus. '.
				'Totatent peliam aut facipsuntur aut pra quam es rem abo.';

			$cntMD = new ContentMetaData();
			$cntMD->PlainContent = $content;
			$cntMD->Slugline = substr( $content, 0, 255 );
			$cntMD->Format = 'text/plain';
			$cntMD->LengthWords = count( explode( ' ', $content ) );
			$cntMD->LengthChars = $cntMD->FileSize;
			$cntMD->LengthParas = count( explode( '\n', $content ) );;
			$cntMD->LengthLines = count( explode( '.', $content ) );
			$metaData->ContentMetaData = $cntMD;
		}

		if ($objectType == 'Image' ) {
			$cntMD = new ContentMetaData();
			$cntMD->Description = null;
			$cntMD->DescriptionAuthor = null;
			$cntMD->Keywords = array();
			$cntMD->Slugline = null;
			$cntMD->Format = 'image/jpeg';
			$cntMD->Columns = null;
			$cntMD->Width = null;
			$cntMD->Height = null;
			$cntMD->Dpi = null;
			$cntMD->LengthWords = null;
			$cntMD->LengthChars = null;
			$cntMD->LengthParas = null;
			$cntMD->LengthLines = null;
			$cntMD->PlainContent = null;
			$cntMD->FileSize = 179508;
			$cntMD->ColorSpace = null;
			$cntMD->HighResFile = null;
			$cntMD->Encoding = null;
			$cntMD->Compression = null;
			$cntMD->KeyFrameEveryFrames = null;
			$cntMD->Channels = null;
			$cntMD->AspectRatio = null;
			$metaData->ContentMetaData = $cntMD;

		}
		return $metaData;
	}

	/**
	 * Builds the object Relation for workflow object.
	 * The relation is created with Dossier as the parent and 'Contained'
	 * as the type of the Relation.
	 * When $containInDossier = 'all': 
	 *   L> Create Relation for each and every dossier in $this->dossierIds.
	 * When $containInDossier = 'random':
	 *   L> Only ONE relation will be created. The dossier is randomly picked
	 *      from $this->dossierIds.
	 * 
	 * @param string $containInDossier Possible values: 'all','random'.
	 * @return array of Relation
	 */
	private function builObjectRelations( $containInDossier )
	{
		if( $containInDossier == 'all' ) {
			$relations = array();
			foreach( $this->dossierIds as $dossierId ) {
				$relation = new Relation();
				$relation->Parent = $dossierId;
				$relation->Type = 'Contained';
				$relations[] = $relation;
			}
		} else if( $containInDossier == 'random' ) {			
			$dossierIdIndex = array_rand( $this->dossierIds ); // pick random dossier (as created before)
			$relation = new Relation();
			$relation->Parent = $this->dossierIds[$dossierIdIndex];
			$relation->Type = 'Contained';

			$relations = array( $relation );
		}
		return $relations;
	}
	
	/**
	 * Builds the Files element for a workflow Object.
	 * Also updates the FileSize property.
	 *
	 * @param MetaData $metaData
	 * @return File[]|null An array of files or null if unsuccesful.
	 */
	private function buildObjectFiles( MetaData $metaData )
	{
		if( $metaData->ContentMetaData ) {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();

			$format = $metaData->ContentMetaData->Format;
			switch( $format ) {
				case 'text/plain': // typically the case for articles
					$contentUTF8 = $metaData->ContentMetaData->PlainContent;
					if( $contentUTF8 ) {
						// Create a plain text file (native rendition)
						$newBom = chr(0xFE) . chr(0xFF); // Insert UTF-16BE BOM marker, which eases recognizion for any editor
						$contentUTF16 = $newBom . mb_convert_encoding( $contentUTF8, 'UTF-16BE', 'UTF-8' ); // UTF-16BE can be opened with ID/IC! 

						$attachment = new Attachment();
						$attachment->Rendition = 'native';
						$attachment->Type = $format;
						if( !$transferServer->writeContentToFileTransferServer( $contentUTF16, $attachment ) ) {
							throw new BizException( 'ERROR', 'MillionObjects',  'Failed uploading native file for article "'.$metaData->BasicMetaData->Name.'".' );
						}
						$files = array( $attachment );
		
						// Now we have content, determine file size									
						$metaData->ContentMetaData->FileSize = strlen( $contentUTF16 );
					}
					break;
				case 'image/jpeg' :
					$attachment = new Attachment();
					$attachment->Rendition = 'native';
					$attachment->Type = 'image/jpeg';
					$attachment->Content = null;
					$attachment->FilePath = '';
					$attachment->FileUrl = null;
					$attachment->EditionId = null;
					$inputPath = dirname(__FILE__).'/millionsofobjects.jpg';
					$transferServer->copyToFileTransferServer( $inputPath, $attachment );

    				$files = array( $attachment );
					break;
				default:
					// Format not supported
					break;
			}
		}
		return isset( $files ) ? $files : null;
	}
	
	/**
	 * Builds the Targets element for a workflow Object.
	 * When $issToApply = 'all': 
	 *   L> Create Target with each and every issue in $this->issueIds.
	 * When $issToApply = 'random':
	 *   L> Only ONE Target will be created. The issue is randomly picked
	 *      from $this->issueIds.
	 * @param string $issToApply Possible values: 'all', 'random'. 
	 * @return array Target
	 */
	private function buildObjectTargets( $issToApply )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		if( $issToApply == 'all' ) { // pick all issue to build Targets
			$targets = array();
			foreach( $this->issueIds as $issId ) {
				$issue = DBIssue::getIssue( $issId );
				$targets[] = $this->buildTargetObj( $issue );
			}
		} else if( $issToApply == 'random' ) { // only one issue is picked to build Targets
			$issueIdIndex = array_rand( $this->issueIds ); // pick random issue (as created before)
			$issue = DBIssue::getIssue( $this->issueIds[$issueIdIndex] );
			$target = $this->buildTargetObj( $issue );
			$targets = array( $target );
		}

		return $targets;
	}
	
	/**
	 * Build Edition Object for Target.
	 * When $editionToApply = 'all': 
	 *   L> Create Target with each and every edition in $this->editionIds.
	 * When $editionToApply = 'random':
	 *   L> Only ONE Target will be created. The edition is randomly picked
	 *      from $this->editionIds.
	 * @param string $editionToApply Possible values 'all', 'random'
	 * @return array of Edition
	 */
	private function buildObjectTargetEditions( $editionToApply )
	{
		if( $editionToApply == 'all' ) {
			$editions = array();
			foreach( $this->editionIds as $editionId ) {
				$editions[] = $this->buildEditionObj( $editionId );
			}
		} else if( $editionToApply == 'random' ) {
			$editionIdIndex = array_rand( $this->editionIds );
			$edition = $this->buildEditionObj( $this->editionIds[$editionIdIndex] );			
			$editions = array( $edition );
		}
		return $editions;
	}

	/**
	 * Given the edition Id, edition Object is built.
	 * @param int $editionId Edition id to build the Edition.
	 * @return Edition
	 */
	private function buildEditionObj( $editionId )
	{
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		$editionObj = DBEdition::getEditionObj( $editionId );
		$edition = new Edition();
		$edition->Id = $editionObj->Id;
		$edition->Name = $editionObj->Name;
		$edition->Description = $editionObj->Description;
		
		return $edition;
	}

	/**
	 * Build Target object given the issue object.
	 * @param Issue $issue To build Target object.
	 * @return Target
	 */
	private function buildTargetObj( $issue )
	{
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		$pubChannelObj = DBChannel::getPubChannelObj( $issue['channelid'] );
		$pubChannel = new PubChannel();
		$pubChannel->Id = $pubChannelObj->Id;
		$pubChannel->Name = $pubChannelObj->Name;
		
		$newIssue = new Issue();
		$newIssue->Id = $issue['id'];	
		$newIssue->Name = $issue['name'];

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue = $newIssue;		
		
		return $target;
	}	
	/**
	 * Get the XML document from xml file or created from new
	 * @param boolean $new Indicator to load from existing or create new DOMDocument
	 * @param string $name Name value
	 * @return DOMDocument $xmlDoc
	 */
	private function getXMLDoc( $new, $name = null )
	{
		$xmlDoc = new DOMDocument();
		if( !$new ) {
			$xmlFile = TEMPDIRECTORY.'/millionsofobjects_'.$name.'.xml';
			if( file_exists( $xmlFile) ) {
				$xmlDoc->loadXML( file_get_contents( $xmlFile ) );
			}
		}
		else {
			$xmlMilObj = $xmlDoc->createElement( 'MillionsOfObjects' );
			$xmlDoc->appendChild( $xmlMilObj );
		}
		return $xmlDoc;
	}

	/**
	 * Get all the created Issue Id from xml file
	 */
	private function writeResultInXML( $xmlDoc, $name )
	{
		$xmlFile = TEMPDIRECTORY.'/millionsofobjects_'.$name.'.xml';
		$this->mkFullDir( TEMPDIRECTORY );
		file_put_contents( $xmlFile, $xmlDoc->saveXML() );
	}

	/**
	 * Get all created Publication Id from xml file
	 */
	private function getPublicationId()
	{
		$pubId = null;
		$xmlDoc = $this->getXMLDoc( false, 'Publication' );
		$xPath = new DOMXPath( $xmlDoc );
		$query = '/MillionsOfObjects/Publication/Id';
		$entries = $xPath->query( $query );
		if( $entries->length > 0 ) {
			$pubId = $entries->item(0)->nodeValue;
		}
		$this->publicationId = $pubId;
	}

	/**
	 * Get all the created Issue Id from xml file
	 */
	private function getIssueIds()
	{
		$issueIds = array();
		$xmlDoc = $this->getXMLDoc( false, 'CreateIssues' );
		$xPath = new DOMXPath( $xmlDoc );
		$query = '/MillionsOfObjects/Issue/Id';
		$entries = $xPath->query( $query );
		if( $entries->length > 0 ) {
			foreach ( $entries as $issueId ) {
				$issueIds[] = $issueId->nodeValue;
			}
		}
		$this->issueIds = $issueIds;
	}

	/**
	 * Get all the created Edition Id from xml file
	 */
	private function getEditionIds()
	{
		$editionIds = array();
		$xmlDoc = $this->getXMLDoc( false, 'CreateEditions' );
		$xPath = new DOMXPath( $xmlDoc );
		$query = '/MillionsOfObjects/Edition/Id';
		$entries = $xPath->query( $query );
		if( $entries->length > 0 ) {
			foreach( $entries as $editionId ) {
				$editionIds[] = $editionId->nodeValue;
			}
		}
		$this->editionIds = $editionIds;
	}

	/**
	 * Get all the created Category Id from xml file
	 */
	private function getCategoryIds()
	{
		$categoryIds = array();
		
		$xmlDoc = $this->getXMLDoc( false, 'CreateCategories' );
		$xPath = new DOMXPath( $xmlDoc );
		$query = '/MillionsOfObjects/Category/Id';
		$entries = $xPath->query( $query );
		if( $entries->length > 0 ) {
			foreach ( $entries as $categoryId ) {
				$categoryIds[] = $categoryId->nodeValue;
			}
		}
		$this->categoryIds = $categoryIds;
	}

	/**
	 * Get all the State Id based on type
	 * @param string $type State type
	 * @return array of Status Ids of the object type ($type).
	 */
	private function getStateIds( $type )
	{
		$stateIds = array();
		
		$xmlDoc = $this->getXMLDoc( false, 'Publication' );
		$xPath = new DOMXPath( $xmlDoc );
		$query = "/MillionsOfObjects/State[Type='$type']/Id";
		$entries = $xPath->query( $query );
		if( $entries->length > 0 ) {
			foreach ( $entries as $stateId ) {
				$stateIds[] = $stateId->nodeValue;
			}
		}
		return $stateIds;
	}
	
	/**
	 * Get the dossier ids created which were recorded in xml file.
	 */
	private function getDossierIds()
	{
		$dossierIds = array();
		
		$xmlDoc = $this->getXMLDoc( false, 'CreateDossiers' );
		$xPath = new DOMXPATH( $xmlDoc );
		$query = '/MillionsOfObjects/Dossier/Id';
		$entries = $xPath->query( $query );
		if( $entries->length > 0 ) {
			foreach( $entries as $dossierId ) {
				$dossierIds[] = $dossierId->nodeValue;
			}
		}
		$this->dossierIds = $dossierIds;
	}
	
	/**
	 * Get publication channel id.
	 * It first retrieves one issue id created in the test. ($this->issueIds), 
	 * and then it gets the publication channel id using the issue id retrieved.
	 * 
	 * @return Publication channel id.
	 */
	private function getPubChannelId()
	{
		if( empty( $this->issueIds ) ) {
			throw new BizException( null, 'Client', 'MillionObjects',  'No Issue being created in the previous step.' );
		}
		require_once BASEDIR.'/server/utils/ResolveBrandSetup.class.php';
		$resolveBrandSetup = new WW_Utils_ResolveBrandSetup();
		$resolveBrandSetup->resolveIssuePubChannelBrand( $this->issueIds[0] );
		
		$pubChannel = $resolveBrandSetup->getPubChannel();
		return $pubChannel->Id;
	}
	
	/**
	 * Get publication channel info.
	 * It first retrieves one issue id created in the test. ($this->issueIds), 
	 * and then it gets the publication channel info using the issue id retrieved.
	 *
	 * @return PubChannelInfo
	 */
	private function getPubChannelInfo()
	{
		if( empty( $this->issueIds ) ) {
			throw new BizException( null, 'Client', 'MillionObjects',  'No Issue being created in the previous step.' );
		}
		require_once BASEDIR.'/server/utils/ResolveBrandSetup.class.php';
		$resolveBrandSetup = new WW_Utils_ResolveBrandSetup();
		$resolveBrandSetup->resolveIssuePubChannelBrand( $this->issueIds[0] );
		
		$pubChannelInfo = $resolveBrandSetup->getPubChannelInfo();
		return $pubChannelInfo;
	}

	/**
	 * Returns an XML document (as string) that contains progress data to be sent to client app.
	 *
	 * @param string  $name Name of the step.
	 * @param integer $max Maximum; Total number of creation.
	 * @param integer $ipro Progress; Number of created objects.
	 * @param boolean $completed; Tells if all iteration steps are taken.
	 * @param string $errMsg; Error message (if any).
	 * @return string XML response
	 */
	private function getProgressAsXml( $name, $max, $pro, $completed, $errMsg )
	{
		// Create XML output stream to return caller
		$xmlDoc = new DOMDocument();
		$xmlReport = $xmlDoc->createElement( 'ProgressResponse' );
		$xmlDoc->appendChild( $xmlReport );

		$xmlBar = $xmlDoc->createElement( 'ProgressBar' );
		$xmlReport->appendChild( $xmlBar );
		$this->createTextElem( $xmlDoc, $xmlBar, 'Name', $name );
		$this->createTextElem( $xmlDoc, $xmlBar, 'Maximum', $max );
		$this->createTextElem( $xmlDoc, $xmlBar, 'Progress', $pro );
		$this->createTextElem( $xmlDoc, $xmlBar, 'RunCompleted', $completed ? 'true' : 'false' );

		if( $errMsg ) {
			$xmlError = $xmlDoc->createElement( 'Error' );
			$xmlReport->appendChild( $xmlError );
			$this->createTextElem( $xmlDoc, $xmlError, 'Message', $errMsg );
		}
		
		$ret = $xmlDoc->saveXML(); // return XML stream to caller
		return $ret;
	}

	/**
	 * Creates new wrapper element node with a new text element inside that contains given text string.
	 *
	 * @param DOMDocument $xmlDoc Document to be mutated.
	 * @param DOMNode $xmlParent Node under which the new node must be created.
	 * @param string $nodeName Name of XML node that gets created between parent and text node.
	 * @param string $nodeText The text data to add to text node.
	 * @return DOMNode The wrapper node that contains the text node.
	 */
	private function createTextElem( $xmlDoc, $xmlParent, $nodeName, $nodeText )
	{
		$xmlNode = $xmlDoc->createElement( $nodeName );
		$xmlParent->appendChild( $xmlNode );
		$xmlText = $xmlDoc->createTextNode( $nodeText );
		$xmlNode->appendChild( $xmlText );
		return $xmlNode;
	}

	/**
	 * Creates a full directory path
	 *
	 * Attempts to create all directories in the given dirName.
	 *
	 * @param string $dirName The full directory path to be created.
	 * @return bool Whether or not creating the directory was succesful.
	 */
	private function mkFullDir( $dirName )
	{
		$newDir = '';
		foreach( explode('/',$dirName) as $dirPart) {
			$newDir .= $dirPart.'/';
			if( !is_dir( $newDir ) ) {
				if( !mkdir( $newDir ) ) {
					return false;
				}
			}
		}
		return true;
	}
}