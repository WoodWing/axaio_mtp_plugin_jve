<?php
/**
 * Tests the GraphViz configuration.
 *
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v9.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_WormGraphviz_TestCase extends TestCase
{
    /**
	 * Returns the display name for this TestCase.
	 *
	 * @return string The display name.
	 */

	public function getDisplayName()
	{
		return 'WormGraphviz configuration';
	}

	/**
	 * Returns the test goals for this TestCase.
	 *
	 * @return string The goals of this TestCase.
	 */
	public function getTestGoals()
	{
		return 'Checks if the GraphViz configuration settings are correct, and if the plugin is activated.';
	}

	/**
	 * Returns the test methods string for this TestCase.
	 *
	 * @return string The test methods for this TestCase.
	 */
	public function getTestMethods()
	{
		return 'Configuration options in the config fields are checked.';
	}

	/**
	 * Returns the priority of this TestCase.
	 *
	 * @return int The Priority of this TestCase
	 */
	public function getPrio()
	{
		return 25;
	}

	/**
	 * Run the tests for the WormGraphviz plugin.
	 *
	 * @return mixed
	 */
	final public function runTest()
	{
		// Check the WormGraphviz plugin configuration.
		if ( !$this->checkConfiguration() ) {
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Validated the WormGraphviz plugin configuration.');
	}

	/**
	 * Checks the WormVizgraph configuration.
	 *
	 * @return bool Whether or not the configuration is correct.
	 */
	public function checkConfiguration()
	{
        require_once dirname(__FILE__) . '/../../config.php';
		$result = true;

		$help = 'Check the configuration settings in config.php.';

		// Check the GRAPHVIZ_APPLICATION_PATH, which is mandatory.
		if ( !defined( 'GRAPHVIZ_APPLICATION_PATH' ) || GRAPHVIZ_APPLICATION_PATH == '' ) {
			$this->setResult('ERROR', 'The GRAPHVIZ_APPLICATION_PATH is not configured.', $help);
			$result = false;
		} elseif ( !is_file( GRAPHVIZ_APPLICATION_PATH ) && !is_link( GRAPHVIZ_APPLICATION_PATH ) ){
			$this->setResult('ERROR', 'The GRAPHVIZ_APPLICATION_PATH could not be resolved to the dot executable.', $help);
			$result = false;
		}

		// Check the GRAPHVIZ_PS2PDF_APPLICATION_PATH, which is optional.
		if ( defined( 'GRAPHVIZ_PS2PDF_APPLICATION_PATH' ) &&
			 !is_file( GRAPHVIZ_PS2PDF_APPLICATION_PATH ) && 
			 !is_link( GRAPHVIZ_PS2PDF_APPLICATION_PATH ) ){
			$this->setResult('ERROR', 'The GRAPHVIZ_PS2PDF_APPLICATION_PATH could not be resolved to the dot executable.', $help);
			$result = false;
		}

		// Check the GRAPHVIZ_OUTPUT_FORMAT, which is mandatory.
		if ( !defined( 'GRAPHVIZ_OUTPUT_FORMAT' ) || GRAPHVIZ_OUTPUT_FORMAT == '' ) {
			$this->setResult('ERROR', 'The GRAPHVIZ_OUTPUT_FORMAT is not configured.', $help);
			$result = false;
		} elseif ( GRAPHVIZ_OUTPUT_FORMAT != 'svg' && GRAPHVIZ_OUTPUT_FORMAT != 'pdf' ) {
			$this->setResult('ERROR', 'The GRAPHVIZ_OUTPUT_FORMAT should be set to pdf or svg.', $help);
			$result = false;
		}

		return $result;
	}
}