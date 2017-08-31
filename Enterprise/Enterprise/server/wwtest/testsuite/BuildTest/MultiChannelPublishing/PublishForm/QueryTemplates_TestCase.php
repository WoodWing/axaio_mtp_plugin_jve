<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_QueryTemplates_TestCase extends TestCase
{
	private $ticket = null;
	private $utils = null; // WW_Utils_TestSuite
	private $mcpUtils = null; // MultiChannelPublishingUtils

	private $publicationObj = null;
	private $pubChannelObj = null;
	private $pubChannels = null;
	private $template = null;

	const PUBLISH_TEMPLATE = 'PublishFormTemplates';
	
	public function getDisplayName() { return 'Query Templates'; }
	public function getTestGoals()   { return 'Checks if the built-in named query \''.self::PUBLISH_TEMPLATE.'\' works.'; }
	public function getTestMethods() { return 'Perform named query and check whether it returns the correct PublishFormTemplate and throws error when no channel id is provided.'; }
    public function getPrio()        { return 10; }
	
	final public function runTest()
	{
		// Use the publishing Utils.
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/MultiChannelPublishing/MultiChannelPublishingUtils.class.php';
		$this->mcpUtils = new MultiChannelPublishingUtils();
		if( !$this->mcpUtils->initTest( $this ) ) {
			return;
		}

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the data that has been determined by "Setup test data" TestCase.
   		$this->vars = $this->getSessionVariables();
  		$this->ticket         = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$this->publicationObj = $this->vars['BuildTest_MultiChannelPublishing']['publication'];
		$this->pubChannelObj  = $this->vars['BuildTest_MultiChannelPublishing']['webPubChannel'];
		$nonWebPublishingPubChannel = $this->vars['BuildTest_MultiChannelPublishing']['printPubChannel'];
		
		do {
			// Create a Publish From Template.
			if( !$this->setupTestData() ) {
				break;
			}
			
			// A valid NamedQuery test. This should not fail.
			// Templates are created when installing the plugin, this test assumes such a plugin is already installed.
			$resp = $this->namedQuery( $this->pubChannelObj->Id, null ); // null = don't expect error.
			$this->validateNamedQueryResp( $resp, true );
	
			// Hunt for error: No Channel Id given should result into error.
			/*$resp = */$this->namedQuery( null, '(S1000)' ); // expect error.
	
			// Testing with a non-webPublishing connector
			// When requesting for NamedQuery templates, it should return no rows.
			$resp = $this->namedQuery( $nonWebPublishingPubChannel->Id, null ); // null = don't expect error.
			$this->validateNamedQueryResp( $resp, null ); // null = Don't expect rows
		} while( false );
					
		// Remove the Publish From Template.
		$this->tearDownTestData();
	}

	/**
	 * Creates test data: a Publish Form Template.
	 *
	 * @return bool Whether or not the test data could be created.
	 */
	private function setupTestData()
	{
		$retVal = true;

		$stepInfo = 'Create the Publish Form Template.';
		$this->template = $this->mcpUtils->createPublishFormTemplateObject( $stepInfo );
		if( is_null($this->template) ) {
			$this->setResult( 'ERROR',  'Could not create the PublishFormTemplate.');
			$retVal = false;
		}
		
		return $retVal;
	}
	
	/**
	 * Removes the test data that was created with the {@link: setupTestData()} function.
	 */
	private function tearDownTestData()
	{
		if( $this->template ) {
			$errorReport = null;
			$id = $this->template->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down Publish Form Template object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Failed to tear down Publish Form Template. '. $errorReport );
			}
			$this->template = null;
		}
	}
	
	/**
	 * Calls the workflow interface NamedQuery service.
	 *
	 * @param string $pubChannelId
	 * @param string $expectedError Set to true when the service call is expected to fail; False otherwise.
	 * @return WflNamedQueryResponse on succes. NULL on error.
	 */
	private function namedQuery( $pubChannelId, $expectedError )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';

		$request = new WflNamedQueryRequest();
		$request->Ticket	= $this->ticket;
		$request->Query		= self::PUBLISH_TEMPLATE;

		$queryParam = new QueryParam();
		$queryParam->Property = 'PubChannelId';
		$queryParam->Operation = '=';
		if( !is_null( $pubChannelId ) ) {			
			$queryParam->Value = $pubChannelId;
		} else {
			$queryParam->Value = '';
		}
		$request->Params = array( $queryParam );
		
		$stepInfo = 'Call NamedQuery service';
		return $this->utils->callService( $this, $request, $stepInfo, $expectedError );
	}

	/**
	 * Validate the WflNamedQueryResponse result
	 *
	 * @param bool $expectedTemplates
	 * @param object $response WflNamedQueryResponse object
	 * @param integer $numberTemplatesExpected
	 * @return boolean 
	 */
	private function validateNamedQueryResp( $response, $expectedTemplates = true, $numberTemplatesExpected = null )
	{
		$return = true;

		// Determine column indexes to work with
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
		$minProps = BizNamedQuery::getMinimalPropsForPublishTemplates();
		$indexes = array_combine( array_values( $minProps ), array_fill( 1, count( $minProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}
		
		// Check if all expected columns are returned.
		foreach( $minProps as $minProp ) {
			if( $indexes[$minProp] == -1 ) {
				$this->setResult( 'ERROR', 'Expected NamedQuery to return column "'.$minProp.'" '.
											'but was not found.' );
				$return = false;
			}
		}
		
		$templatesCount = count( $response->Rows ); // Number of templates returned.
		if( !is_null( $numberTemplatesExpected ) ) { // Only test this when the BuildTest know for sure how many templates it should return.
			if( $templatesCount != $numberTemplatesExpected ) {
				$this->setResult( 'ERROR', 'Expected NamedQuery to return "'.$numberTemplatesExpected.'" '.
											'Form Templates, but "'.$templatesCount.'" returned.' );
				$return = false;
			}
		}
		if( $expectedTemplates ) { // Expected templates to be returned.
			if( $templatesCount == 0 ) {
				$this->setResult( 'ERROR', 'Expected NamedQuery to return Form Templates, but none returned.' );
				$return = false;
			}
		} else { // No rows are expected when a non-WebPublish connector is requesting for WebPublishTemplate.
			if( $templatesCount > 0 ) {
				LogHandler::logPhpObject( $response, 'print_r', 'Invalid_Response'  );
				$this->setResult( 'ERROR', 'NamedQuery returned "'.$templatesCount.'" Form Templates, which is not expected.' .
											'When a non-WebPublishing connector do a NamedQuery to request for templates, ' .
											'zero template expected.' );
				$return = false;
			} else {
				return true; // Nothing more to validate, bail out here.
			}				
		}	
		foreach( $response->Rows as $row ) {
			foreach( $minProps as $minProp ) {
				$propValue = $row[$indexes[$minProp]];
				switch( $minProp ){
	            	case 'Type':
						if( $propValue != 'PublishFormTemplate' ) {
							$this->setResult( 'ERROR', 'NamedQuery returned wrong object type: '
											. $propValue . '. Expected all to be Form Templates.');
							$return = false;
						}
						break;
				}
			}
		}
		LogHandler::Log( 'MultiChannelPublishing_NamedQuery', 'INFO', 'NamedQuery service call validated.' );
		return $return;
	}
}