<?php

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

abstract class WW_TestSuite_BuildTest_Search_Base extends TestCase
{
	protected $vars;
	protected $ticket;
	protected $utils;

	public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the Ticket that has been determined by Setup TestCase.
		$this->vars = $this->getSessionVariables();

		$this->ticket = @$this->vars['BuildTest_Search']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR', 'There is no ticket while expected one',
				'Please enable the "Setup test data" entry and try again.' );
			return;
		}

		// Make sure the save operations are directly reflected at Solr indexes,
		// or else we risk race-conditions, whereby saved data is not queryable the next
		// split second, and so tests would fail randomly.
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		BizSession::setDirectCommit( true );

		return;
	}

	public function __construct()
	{
	}

	/**
	 * Test searching on a query and validate the expected result.
	 *
	 * @param string $objectID ID of object being tested
	 * @param string $query Query term on which is being searched
	 * @param string $stepInfo Info added to any error
	 * @param bool $expected Whether or not it is expected to find the tested Object
	 * @return bool True on success.
	 */
	public function testSearch( $objectID, $query, $stepInfo, $expected = true, $queryParams = null, $areas = array("Workflow") )
	{
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';

		$found = false;

		$params = array(
			new QueryParam( "Search", "=", $query )
		);
		if( !is_null( $queryParams ) ) {
			$params = array_merge( $params, $queryParams );
		}

		$request = new WflQueryObjectsRequest( $this->ticket, $params );
		$request->Query	= '_QueryObjects_';
		$request->Areas = $areas;
		$request->Hierarchical = true;
		$request->GetObjectMode = false;

		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( is_null($response) ) {
			return false;
		}

		// Get the index number where 'ID' is stored in returned column of queryObjects resp.
		$columnIndex = -1;
		if( $response->Columns ) {
			foreach( $response->Columns as $column ) {
				$columnIndex++;
				if( $column->Name == 'ID' ) {
					break; // Found the index number of Name.
				}
			}
		}

		if( $columnIndex == -1 ) {
			$this->setResult( 'ERROR', 'Could not find column index of ID',
				'Check your column settings' );
			return false;
		}

		// Check row for the (expected) object
		foreach( $response->Rows as $row ) {
			if( $row[$columnIndex] == $objectID ) {
				$found = true;
				break;
			}
		}

		if( $expected != $found ) {
			if( $expected ) {
				$this->setResult( 'ERROR', $stepInfo . ': did not find object for query "' . $query . '" while expected',
					'Check the Object in Solr/database' );
			} else {
				$this->setResult( 'ERROR', $stepInfo . ': found object for query "' . $query . '" while not expected',
					'Check the Object in Solr/database' );
			}
			return false;
		}
		return true;
	}
}
