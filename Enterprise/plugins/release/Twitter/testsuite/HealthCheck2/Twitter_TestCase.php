<?php
/**
 * Twitter TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v7.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Twitter_TestCase extends TestCase
{

	public function getDisplayName()
	{
		return 'Publish to Twitter';
	}

	/**
	 * Returns the test goals for this TestCase.
	 *
	 * @return string The goals of this TestCase.
	 */
	public function getTestGoals()
	{
		return 'Checks if the Twitter plugin is installed and configured correctly.';
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
	 * Runs the test for the Twitter plugin.
	 *
	 * @return mixed
	 */
	final public function runTest()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';

		// When plug-in is not installed or disabled, skip test and refer to the
		// Server Plug-ins page to install/enable.
		$help = 'Check <a href="../../server/admin/serverplugins.php' . '">Server Plug-ins</a>';
		$pluginObj = BizServerPlugin::getPluginForConnector('Twitter_PubPublishing');
		if ( $pluginObj && $pluginObj->IsInstalled ) {
			if ( !$pluginObj->IsActive ) {
				$this->setResult('NOTINSTALLED', 'The Twitter server plugin is disabled.', $help);
				return;
			}
		} else {
			$this->setResult('NOTINSTALLED', 'The Twitter server plugin is not installed.', $help);
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Checked server plugin Twitter installation.');

		// Check Twitter API dependencies.
		if ( !$this->checkTwitterDependencies() ) {
			return;
		}

		// Check the Twitter plugin configuration.
		if ( !$this->checkTwitterConfiguration() ) {
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Validated the Twitter plugin.');
	}

	/**
	 * Checks the Twitter API dependencies.
	 *
	 * @return bool Whether or not the API dependencies are met.
	 */
	private function checkTwitterDependencies()
	{
		$hasCurlExtension = extension_loaded('curl');
		$hasJsonExtension = function_exists('json_decode');
		$curl = '<b>lib_curl</b>';
		$json = '<b>json</b>';
		$missing = '';

		if (!$hasCurlExtension && !$hasJsonExtension){
			$missing = $curl . ' and ' . $json;
		}
		elseif (!$hasCurlExtension){
			$missing = $curl;
		}
		elseif (!$hasJsonExtension){
			$missing = $json;
		}

		if ($missing != ''){
			$help = 'The following required extensions are missing: ' . $missing . '. Please install them.';
			$this->setResult('ERROR', 'Required PHP extension(s) for the Twitter API are missing.', $help);
			return false;
		}
		return true;
	}

	/**
	 * Configuration test
	 *
	 * Checks the Twitter configuration settings, checks if the configuration fields are not empty and
	 * checks if the issues are registered.
	 *
	 * @return bool Whether or not the checks were successful.
	 */
	public function checkTwitterConfiguration()
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR . '/server/utils/PublishingUtils.class.php';
		require_once dirname(__FILE__) . '/../../EnterpriseTwitterConnector.class.php';
		require_once BASEDIR . '/server/utils/TestSuite.php';
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';

		$pubChannels = DBChannel::getChannelsByPublishSystem( 'Twitter' );

		//Check if there is at least 1 Twitter channel because when this is false you want to give an error.
		$twitConn = new EnterpriseTwitterConnector();
		if( $pubChannels ) foreach ($pubChannels as $channel){
			foreach( DBAdmIssue::listChannelIssuesObj($channel->Id) as $issueObj){
				$reasonParams = array($issueObj->Name,'<a href="' . SERVERURL_ROOT . INETROOT . '/server/admin/webappindex.php?webappid=TwitterConfig&plugintype=config&pluginname=Twitter" target=_blank>');
				//Check if the user access token is valid and you can connect to Twitter with it.
				$response = $twitConn->getTwitter($issueObj->Id)->getHttpClient()->getLastResponse()->getMessage();
				if($response != 'OK'){
					$help = BizResources::localize( 'TWITTER_ERROR_HELP_NOT_REGISTERED', true, $reasonParams ) . '</a>';
					$this->setResult('ERROR', BizResources::localize( 'TWITTER_ERROR_MESSAGE_NOT_REGISTERED', true, $reasonParams ), $help);
				}

			}
			$result = true;
		} else {
			$help =  BizResources::localize('TWITTER_ERROR_HELP_NO_CHANNELS');
			$this->setResult('ERROR', BizResources::localize('TWITTER_ERROR_MESSAGE_NO_CHANNELS'), $help);
			$result = false;
		}
		return $result;
	}
}