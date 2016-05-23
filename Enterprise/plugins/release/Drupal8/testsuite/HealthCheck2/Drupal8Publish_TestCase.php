<?php
/**
 * "Publish to Drupal 8 - Publish Forms" TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Drupal8Publish_TestCase extends TestCase
{
	public function getDisplayName() { return 'Drupal 8 - Publish Forms'; }
	public function getTestGoals()   { return 'Checks if the "Publish to Drupal 8 - Publish Forms" server plug-in is correctly configured. '; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 23; }

	final public function runTest()
	{
		require_once dirname(__FILE__).'/../../Utils.class.php';
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

		$pluginName = WW_Plugins_Drupal8_Utils::DRUPAL8_PLUGIN_NAME;
		$drupalChannelInfos = DBChannel::getChannelsByPublishSystem( $pluginName );
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		$drupalChannelInfos = DBChannel::getChannelsByPublishSystem( $pluginName );

		if (!$drupalChannelInfos) {
			$pageUrl = SERVERURL_ROOT.INETROOT.'/server/admin/publications.php';
			$help = 'Click <a href="'.$pageUrl.'" target="_blank">here</a> to configure a Brand for publishing to Drupal 8.';

			$this->setResult( 'ERROR',
				'Could not find a Drupal 8 Publication Channel.', $help );
		} else {
		    foreach ( $drupalChannelInfos as $channelInfo ) {
			    $pubChannel = WW_Utils_PublishingUtils::getAdmChannelById($channelInfo->Id);

			    // Step 1: Check if the channel properties are configured correctly.
			    if( !$this->hasError() ) {
				    $this->validateSitesOption($channelInfo->PublicationId, $pubChannel );
				    LogHandler::Log( 'Drupal8Publish', 'INFO', 'Checked the channel options.' );
			    }

			    // Step 2: Check if we can login to Drupal.
			    if( !$this->hasError() ) {
				    $this->validateDrupalConnection( $channelInfo->PublicationId, $pubChannel );
				    LogHandler::Log( 'Drupal8Publish', 'INFO', 'Validated the Drupal connection.' );
			    }

			    // Step 3: Check if the ContentTypes from a former version of the Drupal8 Plugin use the correct naming scheme.
			    if ( !$this->hasError() ) {
				    $channelUpdated = $pluginName. '_' . $channelInfo->Id . '_documentids_updated';
				    $channelIsUpdated = DBConfig::getValue( $channelUpdated );

				    if ( is_null( $channelIsUpdated ) ) {
						$url = SERVERURL_ROOT.INETROOT.'/server/admin/webappindex.php?webappid=ImportDefinitions&plugintype=config&pluginname=Drupal8';
					    $help = 'Please import the Publish Form Templates on the <a href="' . $url . '">Drupal 8 Maintenance page</a>.';
					    $this->setResult( 'ERROR', 'The Publish Form Templates stored in Enterprise Server do not match '
					        . 'with the content types available on Drupal.', $help );
				    }
			    }
		    }
			// Step 4: Check if there is any unused custom property from Drupal7 server plugin, if so display a link to perform the cleanup
			$unusedProperties = BizProperty::getUnusedPublishFormProperties( $pluginName );
			if( count($unusedProperties) > 0 ) {
				$url = SERVERURL_ROOT.INETROOT.'/server/admin/removeunusedproperties.php?serverplugin='. $pluginName;
				$help = 'You can clean up these properties on the <a href="' . $url . '"> cleanup page</a>.';
				$this->setResult( 'WARN', 'The database contains custom properties that are no longer used by the '.$pluginName .' plug-in.', $help );
			}
		}
	}

	/**
	 * Validates the options set for the given pub channel
	 *
	 * @param int $publicationId
	 * @param AdmPubChannel $pubChannel
	 */
	private function validateSitesOption( $publicationId, $pubChannel )
	{
		$channelUrl = SERVERURL_ROOT.INETROOT.'/server/admin/editChannel.php?publid='.$publicationId.'&channelid='.$pubChannel->Id;
		$help = 'Click <a href="'.$channelUrl.'" target="_blank">here</a> to resolve the problem.';

		$selectedSite = WW_Utils_PublishingUtils::getAdmPropertyValue( $pubChannel, WW_Plugins_Drupal8_Utils::CHANNEL_SITE_URL );

		if( empty($selectedSite) ) {
			$this->setResult( 'ERROR',
				'The \'Site URL\' option is not set for Publication Channel \''.$pubChannel->Name.'\'.', $help );
		} else {
			// Resolve the Uri.
			$configuration = WW_Plugins_Drupal8_Utils::resolveConfigurationSettings( $selectedSite );
			$url = $configuration['url'];

			// For Drupal we use the Zend Http Client, so we use its URI factory to validate.
			try {
				require_once 'Zend/Uri.php';
				$uri = Zend_Uri::factory( $url );
			} catch( Exception $e ) {
				$e = $e;
				$uri = null;
			}

			// Check if the site could be reached.
			if( !$uri ) {
				$this->setResult( 'ERROR',
					'The \'Web Site URL\' option is not set for Publication Channel \''.$pubChannel->Name.'\'.', $help );
			} else if( substr( $uri, -1 ) != '/' ) { // Shouldn't happen but just to be safe. When you save the url in the channel admin page, it should automatically add / at the end of url.
				$this->setResult( 'ERROR',
					'The \'Web Site URL\' option for Publication Channel \''.$pubChannel->Name.'\' should end with a slash (/).', $help );
			}

			// Check that the username and password are not empty for the selected Drupal user.
			if ( empty ( $configuration['username'] ) ) {
				$this->setResult( 'ERROR',
					'The \'Username\' option for Publication Channel \''.$pubChannel->Name.'\' may not be empty.', $help );
			}

			if ( empty ( $configuration['password'] ) ) {
				$this->setResult( 'ERROR',
					'The \'Pasword\' option for Publication Channel \''.$pubChannel->Name.'\' may not be empty.', $help );
			}
		}
	}

	/**
	 * Checks if we can connect and login to Drupal. It calls the XML RPC function
	 * "enterprise.testConfig" and validates version information.
	 * If not, an ERROR is raised which can be requested by $this->hasError().
	 *
	 * @param int $publicationId
	 * @param AdmPubChannel$pubChannel
	 */
	private function validateDrupalConnection( $publicationId, $pubChannel )
	{	
		require_once dirname(__FILE__) . '/../../DrupalXmlRpcClient.class.php';

		$channelUrl = SERVERURL_ROOT.INETROOT.'/server/admin/editChannel.php?publid='.$publicationId.'&channelid='.$pubChannel->Id;
		$help = 'Check the settings in the config.php file.';

		try {
			$errorMessage = '';
			
			// Test the configuration.
			$result = DrupalXmlRpcClient::testConfig( $pubChannel );

			$header = 'Drupal Errors for Publication Channel "'.$pubChannel->Name.'":<br />'.PHP_EOL;
			// don't show output from the above request on test page
			ob_clean();
			if (count($result['Errors'])){
				$errorMessage = $header;
				foreach ($result['Errors'] as $error){
					$errorMessage .= $error . "<br />\n";
				}
				$this->setResult( 'ERROR', $errorMessage, $help);
			} else {
				if ( count ( $result['Access']) ) {
					if ( empty ( $errorMessage ) ) {
						$errorMessage .= $header;
					}

					foreach ( $result['Access'] as $access ) {
						$errorMessage .= $access . "<br />\n";
					}

					$this->setResult( 'ERROR', $errorMessage, $help);
				}
			}
		} catch (Exception $e) {
			$this->setResult('ERROR', $e->getMessage(), $help);
		}
	}
}

