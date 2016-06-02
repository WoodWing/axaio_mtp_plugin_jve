<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflGetDialog_TestCase extends TestCase
{
	// Session related stuff	
	private $ticket = null;
		
	public function getDisplayName() { return 'GetDialog'; }
	public function getTestGoals()   { return 'Checks if the workflow dialog service works well.'; }
	public function getTestMethods() { return 'Call GetDialog service and see whether it returns a good data structure that can be used to draw a dialog.'; }
	public function getPrio()        { return 100; }
	
	final public function runTest()
	{
		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}

		require_once BASEDIR . '/server/services/wfl/WflGetDialogService.class.php';
		//require_once BASEDIR . '/server/interfaces/services/wfl/WflGetDialogRequest.class.php';
		$service = new WflGetDialogService();
		$request = new WflGetDialogRequest();
		$request->Ticket	= $this->ticket;
		//$request->ID
		//$request->Publication
		//$request->Issue
		//$request->Section
		//$request->State
		$request->Type      = 'Article';
		$request->Action    = 'Create';
		$request->RequestDialog      = true;
		$request->RequestPublication = true;
		$request->RequestMetaData    = true;
		$request->RequestStates      = true;
		$request->RequestTargets     = true;
		$pubInfo				= $vars['BuildTest_WebServices_WflServices']['publication'];
		$request->Publication	= $pubInfo->Id;
		$request->Section		= (isset($pubInfo->Categories) && !empty($pubInfo->Categories) ) ? $pubInfo->Categories[0]->Id : null;
		//$request->DefaultDosier
		//$request->Parent
		//$request->Template
		//$request->Areas
		$service->execute( $request );
		//LogHandler::Log( 'WflGetDialog', 'DEBUG', print_r($response,true) );
	}
}