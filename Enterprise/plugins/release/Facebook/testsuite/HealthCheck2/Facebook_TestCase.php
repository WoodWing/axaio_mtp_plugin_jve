<?php
/**
 * Facebook TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Facebook_TestCase extends TestCase
{
	/**
	 * Returns the display name for this TestCase.
	 *
	 * @return string The display name.
	 */
	public function getDisplayName()
	{
		return 'Publish to Facebook';
	}

	/**
	 * Returns the test goals for this TestCase.
	 *
	 * @return string The goals of this TestCase.
	 */
	public function getTestGoals()
	{
		return 'Checks if the Facebook plugin is installed and configured correctly.';
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
	 * Runs the test for the Facebook plugin.
	 *
	 * @return mixed
	 */
	final public function runTest()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// When plug-in is not installed or disabled, skip test and refer to the
		// Server Plug-ins page to install/enable.
		$help = 'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>';
		$pluginObj = BizServerPlugin::getPluginForConnector( 'Facebook_PubPublishing' );
		if( $pluginObj && $pluginObj->IsInstalled ) {
			if( !$pluginObj->IsActive ) {
				$this->setResult( 'NOTINSTALLED', 'The Facebook server plugin is disabled.', $help );
				return;
			}
		} else {
			$this->setResult( 'NOTINSTALLED', 'The Facebook server plugin is not installed.', $help );
			return;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'Checked server plugin Facebook installation.' );

		// Check Facebook API dependencies.
		if( !$this->checkFacebookDependencies() ) {
			return;
		}

		// Check the Facebook plugin configuration.
		if( !$this->checkFacebookConfiguration() ) {
			return;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'Validated the Facebook plugin.' );
	}

	/**
	 * Checks the Facebook API dependencies.
	 *
	 * @return bool Whether or not the API dependencies are met.
	 */
	private function checkFacebookDependencies()
	{
		$hasCurlExtension = extension_loaded( 'curl' );
		$hasJsonExtension = function_exists( 'json_decode' );
		$curl = '<b>lib_curl</b>';
		$json = '<b>json</b>';
		$missing = '';

		if( !$hasCurlExtension && !$hasJsonExtension ) {
			$missing = $curl.' and '.$json;
		} elseif( !$hasCurlExtension ) {
			$missing = $curl;
		} elseif( !$hasJsonExtension ) {
			$missing = $json;
		}

		if( $missing != '' ) {
			$help = 'The following required extensions are missing: '.$missing.'. Please install them.';
			$this->setResult( 'ERROR', 'Required PHP extension(s) for the Facebook API are missing.', $help );
			return false;
		}
		return true;
	}

	/**
	 * Configuration test
	 *
	 * Checks the Facebook configuration settings, checks if the configuration fields are not empty and
	 * checks if the channels are registered.
	 *
	 * @return bool Whether or not the checks were successful.
	 */
	public function checkFacebookConfiguration()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once dirname( __FILE__ ).'/../../FacebookPublisher.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

		$pubChannels = DBChannel::getChannelsByPublishSystem( 'Facebook' );

		//Check if there is at least 1 Facebook channel because when this is false you want to give an error.
		if( $pubChannels[0] != null ) {

			foreach( $pubChannels as $channel ) {
				$channelObj = WW_Utils_PublishingUtils::getAdmChannelById( $channel->Id );
				$appId = BizAdmProperty::getCustomPropVal( $channelObj->ExtraMetaData, 'C_FPF_CHANNEL_APPLICATION_ID' );
				$appSecret = BizAdmProperty::getCustomPropVal( $channelObj->ExtraMetaData, 'C_FPF_CHANNEL_APP_SECRET' );
				$pageId = BizAdmProperty::getCustomPropVal( $channelObj->ExtraMetaData, 'C_FPF_CHANNEL_PAGE_ID' );

				$reasonParams = array( $channel->Name, '<a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/webappindex.php?webappid=ImportDefinitions&plugintype=config&pluginname=Facebook" target=_blank>' );

				//Check if the configuration fields are filled in that are needed for the register process.
				if( $appId != null && $appSecret != null && $pageId != null ) {
					//Check if the user access token is valid and you can connect to Facebook with it.
					$faceConn = new FacebookPublisher();
					$connectString = 'https://graph.facebook.com/me/accounts?access_token='.$faceConn->getAccessToken( $channel->Id );
					$user = json_decode( file_get_contents( $connectString ) );

					if( $user == '' || $user == null ) {
						$help = BizResources::localize( 'FACEBOOK_ERROR_HELP_NOT_REGISTERED', true, $reasonParams ).'</a>';
						$this->setResult( 'ERROR', BizResources::localize( 'FACEBOOK_ERROR_MESSAGE_NOT_REGISTERED' ), $help );
					}
				} //Error because the config fields are not filled in
				else {
					$help = BizResources::localize( 'FACEBOOK_ERROR_MESSAGE_CONFIG_FIELD', true, $reasonParams );
					$this->setResult( 'ERROR', BizResources::localize( 'FACEBOOK_ERROR_HELP_CONFIG_FIELD' ), $help );
				}
			}
			$result = true;
		} else {
			$help = BizResources::localize( 'FACEBOOK_ERROR_HELP_NO_CHANNELS' );
			$this->setResult( 'ERROR', BizResources::localize( 'FACEBOOK_ERROR_MESSAGE_NO_CHANNELS' ), $help );
			$result = false;
		}
		return $result;
	}
}