<?php
/**
 * WordPress TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_WordPress_TestCase extends TestCase
{
    /**
	 * Returns the display name for this TestCase.
	 *
	 * @return string The display name.
	 */

	public function getDisplayName()
	{
		return 'Publish to WordPress';
	}

	/**
	 * Returns the test goals for this TestCase.
	 *
	 * @return string The goals of this TestCase.
	 */
	public function getTestGoals()
	{
		return 'Checks if the WordPress plugin is installed and configured correctly.';
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
	 * Runs the test for the WordPress plugin.
	 *
	 * @return mixed
	 */
	final public function runTest()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// When plug-in is not installed or disabled, skip test and refer to the
		// Server Plug-ins page to install/enable.
		$help = 'Check <a href="../../server/admin/serverplugins.php' . '">Server Plug-ins</a>';
		$pluginObj = BizServerPlugin::getPluginForConnector('WordPress_PubPublishing');
		if ( $pluginObj && $pluginObj->IsInstalled ) {
			if ( !$pluginObj->IsActive ) {
				$this->setResult('NOTINSTALLED', 'The WordPress server plugin is disabled.', $help);
				return;
			}
		} else {
			$this->setResult('NOTINSTALLED', 'The WordPress server plugin is not installed.', $help);
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Checked server plugin WordPress installation.');

		// Check WordPress API dependencies.
		if( !$this->checkWordPressDependencies() ) {
			return;
		}

		// Check the WordPress module.
		if( !$this->checkWordPressSetUp() ) {
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Validated the WordPress plugin.');
	}

	/**
	 * Checks the WordPress API dependencies.
	 *
	 * @return bool Whether or not the API dependencies are met.
	 */
	private function checkWordPressDependencies()
	{
		$hasCurlExtension = extension_loaded('curl');
		$hasJsonExtension = function_exists('json_decode');
		$curl = '<b>lib_curl</b>';
		$json = '<b>json</b>';
		$missing = '';

		if(!$hasCurlExtension && !$hasJsonExtension) {
			$missing = $curl . '\', \'' . $json;
		} elseif (!$hasCurlExtension) {
			$missing = $curl;
		} elseif (!$hasJsonExtension) {
			$missing = $json;
		}

		if( $missing ) {
			$reasonParams = array( $missing );
			$help = BizResources::localize( 'WORDPRESS_ERROR_MISSING_EXTENSION_TIP', true, $reasonParams );
			$message = BizResources::localize( 'WORDPRESS_ERROR_MISSING_EXTENSION_MESSAGE' );
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}
		return true;
	}

	/**
	 * WordPress Set-up test
	 *
	 * Checks if there are channels and issues set-up for WordPress
	 *
	 * Runs the the WordPress plug-in test, this checks if:
	 * - the required modules are installed and activated;
	 * - the installed ww_enterprise module has the required version;
	 * - checks if connected WordPress site has the correct version
	 *
	 * @return bool Whether or not the checks were successful.
	 */
	private function checkWordPressSetUp()
	{
		require_once dirname(__FILE__).'/../../WordPressXmlRpcClient.class.php';
		require_once dirname(__FILE__).'/../../WordPress_Utils.class.php';
		require_once BASEDIR.'/server/utils/VersionUtils.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';

		$pubChannels = DBChannel::getChannelsByPublishSystem( WordPress_Utils::WORDPRESS_PLUGIN_NAME );
		if( !$pubChannels ) {
			$help = BizResources::localize( 'WORDPRESS_ERROR_NO_CHANNELS_TIP' );
			$message = BizResources::localize( 'WORDPRESS_ERROR_NO_CHANNELS_MESSAGE' );
			$this->setResult('ERROR', $message, $help);
			return false;
		}

		$wordpressUtils = new WordPress_Utils();
		try {
			$allConnections = $wordpressUtils->getConnectionInfo(); // Will also check if there are any sites configured
		} catch( BizException $e ) {
			$help = BizResources::localize( 'WORDPRESS_ERROR_NO_SITES_TIP' );
			$message = BizResources::localize( 'WORDPRESS_ERROR_NO_SITES' );
			$this->setResult('ERROR', $message, $help);
			return false;
		}

		$sites = $allConnections['sites'];
		foreach( $pubChannels as $pubChannel ) {
			$issuesForChannel = DBIssue::listChannelIssues( $pubChannel->Id );
			$admPubChannel = WW_Utils_PublishingUtils::getAdmChannelById( $pubChannel->Id );
			$channelSiteKey = BizAdmProperty::getCustomPropVal( $admPubChannel->ExtraMetaData, 'C_WP_CHANNEL_SITE' );
			$reasonParams = array( $pubChannel->Name, $channelSiteKey );

			if( !$issuesForChannel ) {
				$help = BizResources::localize( 'WORDPRESS_ERROR_NO_ISSUES_TIP', true, $reasonParams );
				$message = BizResources::localize( 'WORDPRESS_ERROR_NO_ISSUES_MESSAGE', true, $reasonParams );
				$this->setResult('ERROR', $message, $help);
				continue; // skip the other tests because these are not needed
			}
			if( !$channelSiteKey ) {
				$help = BizResources::localize( 'WORDPRESS_ERROR_SITE_MISSING_TIP', true, $reasonParams );
				$message = BizResources::localize( 'WORDPRESS_ERROR_INCORRECT_CONFIG_MESSAGE' );
				$this->setResult('ERROR', $message, $help);
				continue; // skip the other tests because these are not needed
			}
			if( isset( $sites[$channelSiteKey] ) ) {
				$siteConnectionInfo = $sites[$channelSiteKey];
				if( !isset($siteConnectionInfo['url']) || !isset($siteConnectionInfo['username']) ||
					!isset($siteConnectionInfo['password']) ) {
					$help = BizResources::localize( 'WORDPRESS_ERROR_SITE_CREDENTIALS', true, array( $channelSiteKey ) );
					$message = BizResources::localize( 'WORDPRESS_ERROR_INCORRECT_CONFIG_MESSAGE' );
					$this->setResult('ERROR', $message, $help);
				}
			} else {
				$help = BizResources::localize( 'WORDPRESS_ERROR_SITE_NOT_EXISTING_TIP', true, $reasonParams );
				$message = BizResources::localize( 'WORDPRESS_ERROR_INCORRECT_CONFIG_MESSAGE' );
				$this->setResult('ERROR', $message, $help);
			}
		}

		$normalizedSites = array();
		foreach( $sites as $siteKey => $site ) {
			$siteError = false;

			try {
				$wordpressUtils->checkUrlSyntax( $site['url'] );
			} catch( BizException $e ) {
				$this->setResult( 'ERROR', $e->getDetail(), $e->getMessage() );
				$siteError = true;
			}
			$cleanSiteKey = $wordpressUtils->normalizeSiteName( $siteKey );
			if( strlen( $cleanSiteKey ) > 10 ) {
				$tip = BizResources::localize( 'WORDPRESS_ERROR_SITE_NAME_LENGTH_TIP' );
				$message = BizResources::localize( 'WORDPRESS_ERROR_SITE_NAME_LENGTH_MESSAGE', true, array( $siteKey ) );
				$this->setResult( 'ERROR', $message, $tip );
				$siteError = true;
			}
			if( isset( $normalizedSites[$cleanSiteKey] ) ) {
				$tip = BizResources::localize( 'WORDPRESS_ERROR_SITE_NAME_DUPLICATE_TIP' );
				$message = BizResources::localize( 'WORDPRESS_ERROR_SITE_NAME_DUPLICATE_MESSAGE' );
				$this->setResult( 'ERROR', $message, $tip );
				$siteError = true;
			} else {
				$normalizedSites[$cleanSiteKey] = $siteKey;
			}

			if( $siteError ) { // check if there is already a error else go to the next site
				continue;
			}

			$clientWordPress = new WordPressXmlRpcClient();
			$pluginInfo = BizServerPlugin::getInstalledPluginInfo( WordPress_Utils::WORDPRESS_PLUGIN_NAME );
			try {
				$clientWordPress->setConnectionUrl( $site['url'] . '/xmlrpc.php' );
				$clientWordPress->setConnectionPassword( $site['password'] );
				$clientWordPress->setConnectionUserName( $site['username'] );
				$clientWordPress->setCertificate( $site['certificate'] );
				$retVal = $clientWordPress->pluginTest( $pluginInfo->Version );
			} catch( BizException $e ) {
				$reasonParams = array( $siteKey );
				if( strpos($e->getDetail(), 'method woodwing.PluginTest does not exist' ) ) {
					$tip = BizResources::localize( 'WORDPRESS_ERROR_MISSING_WORDPRESS_PLUGIN_TIP' );
					$message = BizResources::localize( 'WORDPRESS_ERROR_MISSING_WORDPRESS_PLUGIN_MESSAGE', true, $reasonParams );
				} else if( strpos($e->getDetail(), 'username or password') ) {
					$tip = BizResources::localize( 'WORDPRESS_ERROR_SITE_INCORRECT_SITE_CONFIG_TIP' );
					$message = BizResources::localize( 'WORDPRESS_ERROR_SITE_INCORRECT_SITE_CONFIG_MESSAGE', true, $reasonParams );
				} else if( strpos( $e->getDetail(), 'permission' ) && strpos( $e->getDetail(), '(403)') ) {
					$tip = BizResources::localize( 'WORDPRESS_ERROR_SITE_INCORRECT_USER_ROLE_TIP', true, $reasonParams ) ;
					$message = BizResources::localize( 'WORDPRESS_ERROR_INCORRECT_ROLE' );
				} else {
					$tip = $e->getDetail() . ' For site: ' . $siteKey;
					$message = $e->getMessage();
				}
				$this->setResult( 'ERROR', $message, $tip );
			}

			if( isset($retVal['successful']) && !$retVal['successful'] ) {
				$errors = $retVal['errors'];
				if( isset($errors['correctVersion']) ) {
					$reasonParams = array( $errors['pluginVersion'], $errors['requiredVersion'], $siteKey );
					$help = BizResources::localize( 'WORDPRESS_ERROR_INCORRECT_PLUGIN_VERSION_TIP', true, $reasonParams );
					$message = BizResources::localize( 'WORDPRESS_ERROR_INCORRECT_PLUGIN_VERSION_MESSAGE', true, $reasonParams );
					$this->setResult('ERROR', $message, $help);
				}
			}

			if( $retVal ) {
				$minimumWordPressVersion = '3.7';
				if( VersionUtils::versionCompare( $retVal['wordpressVersion'], $minimumWordPressVersion, '<' ) ) {
					$reasonParams = array( $retVal['wordpressVersion'], $minimumWordPressVersion, $siteKey );
					$help = BizResources::localize( 'WORDPRESS_ERROR_INCORRECT_SITE_VERSION_TIP', true, $reasonParams );
					$message = BizResources::localize( 'WORDPRESS_ERROR_INCORRECT_SITE_VERSION_MESSAGE', true, $reasonParams );
					$this->setResult('ERROR', $message, $help);
				}
			}
		}
		return true;
	}
}