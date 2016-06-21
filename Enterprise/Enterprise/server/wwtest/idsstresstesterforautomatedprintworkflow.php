<?php
set_time_limit(3600);

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
ini_set('display_errors', '1'); 

$runs = isset( $_REQUEST['runs'] ) ? intval( $_REQUEST['runs'] ) : 10;

?>
<!DOCTYPE html>
<html>
	<body style="font-family:arial">
		<h1>IDS Automation - Stress test</h1>
		<p>This application will login to Enterprise Server and randomly pick a dossier 
			and a layout from an issue. The articles and images contained by the dossier 
			are then automatically placed onto the layout using the IDS Automation feature.
			The user, brand and issue are configured in the TESTSUITE option which can be
			found in the configserver.php file.</p>
		<p style="color:red"><b>Please don't use this on a production server.</b></p>
		<form>
			<p>The automated placements can be repeated with the following parameter:
				<input type="text" name="runs" value="<?php echo $runs ?>" size="4" />
				<br/><br/>
				<input type="submit" value="Start" />
			</p>
  		</form>
<?php
if( isset( $_REQUEST['runs'] ) ) {
	require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
	if( !$runs ) {
		?>
			<p style="color:red"><b>Error:</b> Please enter a positive integer value.</p>
		<?php
	} else if( !BizServerPlugin::hasActivePlugins( 'AutomatedPrintWorkflow' ) ) {
		?>
			<p style="color:red"><b>Error:</b> Please enable the AutomatedPrintWorkflow server plug-in, or another plug-in that has an AutomatedPrintWorkflow connector.</p>
		<?php
	} elseif( !BizServerPlugin::isPluginActivated( 'IdsAutomation' ) ) {
		?>
			<p style="color:red"><b>Error:</b> Please enable the IdsAutomation server plug-in.</p>
		<?php
	} else {
		$test = new WW_Test_IdsStressTesterForAutomatedPrintWorkflow();
		$test->setOption( 'runs', $runs );
		$test->runTest();
	}
}
?>
	</body>
</html>
<?php

class WW_Test_IdsStressTesterForAutomatedPrintWorkflow
{
	// session data
	private $options = null;
	private $suiteOpts = null;
	private $ticket = null;
	
	// workflow data
	private $issue = null;
	private $layout = null;
	private $dossier = null;
	
	public function __construct()
	{	
		$this->options['runs'] = 1;
	}
	
	public function setOption( $option, $value )
	{
		$options = array( 'runs' );
		if( !in_array( $option, $options ) ) {
			$this->log( 'ERROR', 'Unsupported option: '.$option );
			return false;
		}
		$this->options[$option] = $value;
		return true;
	}	

	public function runTest()
	{
		if( $this->setupTestData() ) {
			for( $r = 1; $r <= $this->options['runs']; $r+=1 ) {
				$this->log( 'INFO', '---------> Operation#'.$r.' ...' );
				do {
					if( !$this->pickRandomLayoutFromIssue() ) {
						break;
					}
					if( !$this->pickRandomDossierFromIssue() ) {
						break;
					}
					if( !$this->placeDossierOnLayout() ) {
						break;
					}
				} while( false );
			}
		}
		$this->tearDownTestData();
	}
	
	private function setupTestData()
	{
		// Use TESTSUITE defined test user (for wwtest)
		$this->suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
		if( !$this->suiteOpts ){
			$this->log( 'ERROR', 'No test User defined. '.
				'Please check the TESTSUITE setting in configserver.php.' );
			return false;
		}
		
		// Logon TESTSUITE user and resolve $this->issue from logon response.
		if( !$this->logOnTestSuiteUser() ) {
			return false;
		}
		
		return true;
	}
	
	private function tearDownTestData()
	{
		$this->logOffTestSuiteUser();
	}
	
	private function logOnTestSuiteUser()
	{
		// Determine client app name
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientIP = WW_Utils_UrlUtils::getClientIP();
		$clientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
		// >>> BZ#6359 Let's use ip since gethostbyaddr could be extreemly expensive!
		if( empty($clientName) ) { $clientName = $clientIP; }
		// if ( !$clientName || ($clientName == $clientIP )) { $clientName = gethostbyaddr($clientIP); }
		// <<<
		
		// Build the LogOn request.
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$request = new WflLogOnRequest();
		$request->User          = $this->suiteOpts['User'];
		$request->Password      = $this->suiteOpts['Password'];
		$request->Server        = 'Enterprise Server';
		$request->ClientName    = $clientName;
		$request->Domain        = '';
		$request->ClientAppName = 'IDS Stress Test';
		$request->ClientAppVersion = 'v'.SERVERVERSION;
		$request->ClientAppSerial = '';
		$request->ClientAppProductKey = '';

		// Logon the user at Enterprise Server through the admin interface.
		$stepInfo = 'Logon TESTSUITE user.';
		$response = $this->callService( $request, $stepInfo );
		if( !is_null($response) ) {
			$this->ticket = $response->Ticket;
			if( $response->Publications ) foreach( $response->Publications as $pub ) {
				if( $pub->Name == $this->suiteOpts['Brand'] ) {
					//$this->brandId = $pub->Id;
					if( $pub->PubChannels ) foreach( $pub->PubChannels as $pubChannel ) {
						foreach( $pubChannel->Issues as $issue ) {
							if( $issue->Name == $this->suiteOpts['Issue'] ) {
								$this->issue = $issue;
								if( $pubChannel->Editions ) {
									$this->edition = $pubChannel->Editions[0];
								}
								break 2;
							}
						}
					}
					break;
				}
			}
			if( $this->issue ) {
				$this->log( 'INFO', 
					'Found issue "'.$this->issue->Name.'" (id:'.$this->issue->Id.') in logon response.' );
				if( $this->edition ) {
					$this->log( 'INFO', 
						'Found edition "'.$this->edition->Name.'" (id:'.$this->edition->Id.') in logon response.' );
				} else {
					$this->log( 'ERROR', 
						'Could not find any edition for issue "'.$this->suiteOpts['Issue'].'" in logon response. '.
						'Please check the TESTSUITE setting in configserver.php and your brand setup.' );
				}
			} else {
				$this->log( 'ERROR', 
					'Could not find issue "'.$this->suiteOpts['Issue'].'" in logon response. '.
					'Please check the TESTSUITE setting in configserver.php and your brand setup.' );
			}
		} else {
			$this->log( 'ERROR', 
				'Failed to logon user "'.$this->suiteOpts['User'].'" '.
				'Please check the TESTSUITE setting in configserver.php and your brand setup.' );
		}
		return $this->issue && $this->edition;
	}

	public function logOffTestSuiteUser()
	{
		if( $this->ticket ) {
			require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
			$request = new WflLogOffRequest();
			$request->Ticket = $this->ticket;
			$stepInfo = 'LogOff TESTSUITE user.';
			/*$response =*/ $this->callService( $request, $stepInfo );
			$this->ticket = null;
		}
	}
	
	private function pickRandomLayoutFromIssue()
	{
		$this->layout = $this->pickRandomObjectFromIssue( 'Layout' );
		return (bool)$this->layout;
	}

	private function pickRandomDossierFromIssue()
	{
		$this->dossier = $this->pickRandomObjectFromIssue( 'Dossier' );
		return (bool)$this->dossier;
	}

	private function pickRandomObjectFromIssue( $objectType )
	{
		// Request for the first 100 layouts assigned to the given issue.
		$reqProps = array( 'ID', 'Type', 'Name', 'Version' ); // At least three properties are needed to let the
													// Oracle driver decide if Properties or DB fields are asked.

		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Params = array(
			new QueryParam( 'IssueId', '=', $this->issue->Id ),
			new QueryParam( 'Type', '=', $objectType )
		);
		$request->MaxEntries = 100;
		$request->Order = BizQuery::getQueryOrder( 'Name', 'asc' );
		$request->RequestProps = $reqProps; 
		$request->Areas = array( 'Workflow' );
		
		$stepInfo = 'Picking random '.$objectType.' from issue.';
		$response = $this->callService( $request, $stepInfo );
		
		// Pick random layout from result set.
		$object = null;
		if( !is_null($response) ) {
			$objects = array();
			$reqPropKeys = array_flip( $reqProps );
			if( isset($response->Rows) ) foreach( $response->Rows as $row ) {
				$objects[] = array(
					'ID' => $row[$reqPropKeys['ID']],
					'Name' => $row[$reqPropKeys['Name']],
					'Version' => $row[$reqPropKeys['Version']] );
			}
			$object = $objects ? $objects[ array_rand( $objects ) ] : null;
		}
		if( $object ) {
// 			$this->log( 'INFO', 
// 				'Picked '.$objectType.' "'.$object['Name'].'" (id:'.$object['ID'].').' );
		} else {
			$this->log( 'ERROR', 
				'Could not find any '.$objectType.' object in '.
				'issue "'.$this->issue->Name.'" (id:'.$this->issue->Id.').' );
		}
		return $object;
	}
	
	private function placeDossierOnLayout()
	{
		// Lock layout to enable us to create operations for the layout.
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->layout['ID'] );
		$request->Lock = true;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'InDesignArticles' );
		$stepInfo = 'Locking Layout "'.$this->layout['Name'].'" (id:'.$this->layout['ID'].').';
		$response = $this->callService( $request, $stepInfo );
		if( is_null($response) ) {
			$this->log( 'WARN', 'Could not lock Layout "'.$this->layout['Name'].'" (id:'.$this->layout['ID'].').' );
			return false;
		}
		
		// Pick random InDesign Article from layout.
		$idArticle = null;
		if( isset($response->Objects[0]->InDesignArticles[0]) ) {
			$idArticles = $response->Objects[0]->InDesignArticles;
			$idArticle = $idArticles[ array_rand( $idArticles ) ];
		}
		if( !$idArticle ) {
			$this->log( 'WARN', 'Layout "'.$this->layout['Name'].'" (id:'.$this->layout['ID'].') has no InDesign Articles.' );
			$this->unlockLayout();
			return false;
		}
		//$this->log( 'INFO', 'Picked InDesign Article "'.$idArticle->Name.'" (id:'.$idArticle->Id.')' );

		$this->log( 'INFO', 
			'Placing contents of Dossier "'.$this->dossier['Name'].'" (id:'.$this->dossier['ID'].') '.
			'onto InDesign Article "'.$idArticle->Name.'" (id:'.$idArticle->Id.') '.
			'of Layout "'.$this->layout['Name'].'" (id:'.$this->layout['ID'].') ...' );
		
		// Place dossier contained items onto the picked InDesign Article.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectOperationsService.class.php';
		$request = new WflCreateObjectOperationsRequest();
		$request->Ticket = $this->ticket;
		$request->HaveVersion = new ObjectVersion();
		$request->HaveVersion->ID = $this->layout['ID'];
		$request->HaveVersion->Version = $this->layout['Version'];
		$request->Operations = array();
		$request->Operations[0] = new ObjectOperation();
		$request->Operations[0]->Id = NumberUtils::createGUID();
		$request->Operations[0]->Type = 'AutomatedPrintWorkflow';
		$request->Operations[0]->Name = 'PlaceDossier';
		$request->Operations[0]->Params = array();
		$request->Operations[0]->Params[0] = new Param();
		$request->Operations[0]->Params[0]->Name = 'EditionId';
		$request->Operations[0]->Params[0]->Value = $this->edition->Id; 
		$request->Operations[0]->Params[1] = new Param();
		$request->Operations[0]->Params[1]->Name = 'DossierId';
		$request->Operations[0]->Params[1]->Value = $this->dossier['ID'];
		$request->Operations[0]->Params[2] = new Param();
		$request->Operations[0]->Params[2]->Name = 'InDesignArticleId';
		$request->Operations[0]->Params[2]->Value = $idArticle->Id;
		$stepInfo = 'Creating PlaceDossier operation.';
		$response = $this->callService( $request, $stepInfo );
		if( is_null($response) ) {
			$this->log( 'ERROR', 'Could not create PlaceDossier operation for '.
				'Layout "'.$this->layout['Name'].'" (id:'.$this->layout['ID'].').' );
			$this->unlockLayout();
			return false;
		}
		
		// Release the layout lock.
		$this->unlockLayout();
		return true;
	}
	
	private function unlockLayout()
	{
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->layout['ID'] );
		$stepInfo = 'Unlocking Layout "'.$this->layout['Name'].'" (id:'.$this->layout['ID'].').';
		/*$response =*/ $this->callService( $request, $stepInfo );
	}
	
	// - - - - - - - - - - - - - - - - helper functions - - - - - - - - - - - - - - - -


	private function callService( $request, $stepInfo )
	{
		$baseName = get_class( $request );
		$baseName = substr( $baseName, 0, strlen($baseName) - strlen('Request') );
		$serviceName = $baseName.'Service';
		//$responseName = $baseName.'Response';
		$service = new $serviceName();
		$response = null;
		try {
			$response = $service->execute( $request );
		} catch( BizException $e ) {
			$message =  '<b>Test: </b>'.$stepInfo.'<br/>'.
						'<b>The service response was unexpected: </b>'.$e->getMessage().
						' (Detail: '.$e->getDetail().')<br/>'.
						'<b>Expected response: </b>Success!';
			$this->log( 'ERROR', $message );
		}
		if( isset( $response->Reports ) ) {
			foreach( $response->Reports as $report ) {
				$this->logReport( $report );
			}
		}
		return $response;
	}

	private function logReport( ErrorReport $report )
	{
		foreach( $report->Entries as $entry ) {
			switch( $entry->MessageLevel ) {
				case 'Info':    $severity = 'INFO';  break;
				case 'Warning': $severity = 'WARN';  break;
				default:        $severity = 'ERROR'; break;
			}
			$this->log( $severity, $entry->Message );
		}
	}
	
	private function log( $level, $message )
	{
		if( $level == 'ERROR' ) {
			$color = 'red';
		} elseif( $level == 'WARN' ) {
			$color = 'orange';
		} else {
			$color = 'green';
		}
		print '<p><font color="'.$color.'">['.$level.']</font> '.$message.'</p>'; // log to screen
		LogHandler::Log( 'idsstresstest', $level, $message );
	}
}
