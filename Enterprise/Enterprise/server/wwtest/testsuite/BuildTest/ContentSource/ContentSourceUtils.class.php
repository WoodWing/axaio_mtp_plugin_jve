<?php

/**
 * Contains helper functions for the ContentSource tests.
 *
 * @package 	Enterprise
 * @subpackage 	Testsuite
 * @since 		v9.2.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class ContentSourceUtils
{
	private $testCase = null;
	private $vars = null;
	private $ticket = null;
	private $utils = null;

	/**
	 * Initializes the utils to let it work for a TestCase.
	 *
	 * @param TestCase $testCase
	 * @return bool Whether or not all session variables are complete.
	 */
	public function initTest( TestCase $testCase )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$valid = false;
		$this->vars = $testCase->getSessionVariables();
		$this->testCase = $testCase;
		$this->expectedError = null;

		$tip = 'Please enable the "Setup Test Data" entry and try again.';
		do {
			// Check LogOn ticket.
			$this->ticket = @$this->vars['BuildTest_SFS']['ticket'];
			if( !$this->ticket ) {
				$testCase->setResult( 'ERROR',  'Could not find ticket to test with.', $tip );
				break;
			}

			// Check presence of test data.
			if( !isset($this->vars['BuildTest_SFS']['publication'] ) ||
				!isset($this->vars['BuildTest_SFS']['printPubChannel'] ) ||
				!isset($this->vars['BuildTest_SFS']['printIssue'] ) ||
				!isset($this->vars['BuildTest_SFS']['testOptions'] ) ||
				!isset($this->vars['BuildTest_SFS']['imageStatus'] ) ||
				!isset($this->vars['BuildTest_SFS']['articleStatus'] ) ||
				!isset($this->vars['BuildTest_SFS']['dossierStatus'] ) ||
				!isset($this->vars['BuildTest_SFS']['articleTemplateStatus'] ) ||
				!isset($this->vars['BuildTest_SFS']['activatedSFSPlugin'] )
			) {
				$testCase->setResult( 'ERROR',  'Could not find data to test with.', $tip );
				break;
			}

			$valid = true;
		} while( false );

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		return $valid;
	}
	
	/**
	 * Defines the error message or server error (S-code) for the next function call
	 * that executes a service request. This settings get automatically cleared after
	 * the call.
	 *
	 * @param string $expectedError
	 */
	public function setExpectedError( $expectedError ) 
	{
		$this->expectedError = $expectedError;
	}

	/**
	 * Retrieves the files via SimpleFileSystem content source plugin by doing a NamedQuery service call.
	 *
	 * @param QueryParam[] $params List of query param object.
	 * @return array List of alien object ids.
	 */
	public function queryObjectsFromSimpleFileSystem( array $params )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';

		$request = new WflNamedQueryRequest();
		$request->Ticket       = $this->ticket;
		$request->Query        = 'Image Library';
		$request->Params       = $params;
		$request->FirstEntry   = 1;
		$request->MaxEntries   = null;
		$request->Hierarchical = false;

		$stepInfo = 'Querying images from simple file system.';
		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );

		$requestProps = array( 'ID', 'Type', 'Name' );
		// Determine column indexes to work with
		$indexes = array_combine( array_values( $requestProps ), array_fill( 1, count( $requestProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}

		$alienObjectIds = array();
		foreach( $response->Rows as $row ) {
			$multiObjsProps = array();
			foreach( $requestProps as $requestProp ) {
				$propValue = $row[$indexes[$requestProp]];
				$multiObjsProps[$requestProp] = $propValue;
			}
			$objectId = $row[$indexes['ID']];
			$alienObjectIds[] = $objectId;
		}

		return $alienObjectIds;
	}

	/**
	 * Deletes the object.
	 *
	 * @param int $objId The id of the object to be removed.
	 * @param string $stepInfo Extra logging info.
	 * @param string &$errorReport To fill in the error message if there's any during the delete operation.
	 * @param bool $permanent Whether or not to delete the object permanently.
	 * @param string[] $areas The areas to test against.
	 * @return bool Whether the deletion was successful.
	 */
	public function deleteObject( $objId, $stepInfo, &$errorReport, $permanent=true, $areas=array('Workflow'))
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array($objId);
		$request->Permanent = $permanent;
		$request->Areas = $areas;
		$response = $this->utils->callService( $this->testCase, $request, $stepInfo, $this->expectedError );
		$this->expectedError = null; // reset (has to be set per function call)
		if( is_null( $response ) ) {
			return false;
		}

		$deleteSuccessful = true;
		if( $response->Reports && count( $response->Reports ) > 0 ) {
			foreach( $response->Reports as $report ) {
				$errorReport .= 'Failed deleted ObjectID:"' . $report->BelongsTo->ID . '" </br>';
				$errorReport .= 'Reason:';
				if( $report->Entries ) foreach( $report->Entries as $reportEntry ) {
					$errorReport .= $reportEntry->Message . '</br>';
				}
				$errorReport .= '</br>';
			}
			$deleteSuccessful = false;
		}
		return $deleteSuccessful;
	}
}
