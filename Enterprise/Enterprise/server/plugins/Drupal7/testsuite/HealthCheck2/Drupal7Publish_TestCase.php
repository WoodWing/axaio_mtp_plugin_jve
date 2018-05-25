<?php
/**
 * "Publish to Drupal 7 - Publish Forms" TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Drupal7Publish_TestCase extends TestCase
{
	public function getDisplayName() { return 'Drupal 7 - Publish Forms'; }
	public function getTestGoals()   { return 'Checks if the "Publish to Drupal 7 - Publish Forms" server plug-in is correctly configured. '; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 23; }

	final public function runTest()
	{
		require_once dirname(__FILE__).'/../../Utils.class.php';
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

		$pluginName = WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME;
		$drupalChannelInfos = DBChannel::getChannelsByPublishSystem( $pluginName );

		if (!$drupalChannelInfos) {
			$pageUrl = SERVERURL_ROOT.INETROOT.'/server/admin/publications.php';
			$help = 'Click <a href="'.$pageUrl.'" target="_blank">here</a> to configure a Brand for publishing to Drupal 7.';

			$this->setResult( 'ERROR', 'Could not find a Drupal 7 Publication Channel.', $help );
		} else {
		    foreach ( $drupalChannelInfos as $channelInfo ) {
			    $pubChannel = WW_Utils_PublishingUtils::getAdmChannelById($channelInfo->Id);

			    // Step 1: Check if the channel properties are configured correctly.
			    if( !$this->hasError() ) {
				    $this->validateSitesOption($channelInfo->PublicationId, $pubChannel );
				    LogHandler::Log( 'Drupal7Publish', 'INFO', 'Checked the channel options.' );
			    }

			    // Step 2: Check if we can login to Drupal and if the versions are compatible.
			    if( !$this->hasError() ) {
				    $this->validateDrupalConnection( $channelInfo->PublicationId, $pubChannel );
				    LogHandler::Log( 'Drupal7Publish', 'INFO', 'Validated the Drupal connection.' );
			    }

			    // Step 3: Check if the ContentTypes from a former version of the Drupal7 Plugin use the correct naming scheme.
			    if ( !$this->hasError() ) {
				    $channelUpdated = $pluginName . '_' . $channelInfo->Id . '_documentids_updated';
				    $channelIsUpdated = DBConfig::getValue( $channelUpdated );

				    if ( is_null( $channelIsUpdated ) ) {
						$url = SERVERURL_ROOT.INETROOT.'/server/admin/webappindex.php?webappid=ImportDefinitions&plugintype=server&pluginname=Drupal7';
					    $help = 'Please import the Publish Form Templates on the <a href="' . $url . '">Drupal 7 Maintenance page</a>.';
					    $this->setResult( 'ERROR', 'The Publish Form Templates stored in Enterprise Server do not match '
					        . 'with the content types available on Drupal.', $help );
				    }
			    }
		    }
			// Step 4: Check if there is any unused custom property from Drupal7 server plugin, if so display a link to perform the cleanup
			$unusedProperties = BizProperty::getUnusedPublishFormProperties( $pluginName );
			if( count($unusedProperties) > 0 ) {
				$url = '<a href="' . SERVERURL_ROOT.INETROOT.'/server/admin/removeunusedproperties.php?serverplugin='. $pluginName . '">'.
						BizResources::localize( 'ACT_CLEAN_UP' ) .'</a>';
				$help = BizResources::localize( 'INFO_CLEANUP_PROPERTY', true,	array( $url ) );
				$msg = BizResources::localize( 'INFO_MESS_LINK_UNUSED_PROP', true,	array( $pluginName ) );
				$this->setResult( 'WARN', $msg, $help );
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

		$url = WW_Utils_PublishingUtils::getAdmPropertyValue( $pubChannel, WW_Plugins_Drupal7_Utils::CHANNEL_SITE_URL );
		if( empty($url) ) {
			$this->setResult( 'ERROR',
				'The "Site URL" option configured for channel "'.$pubChannel->Name.'" is not set.', $help );
		} else {
			// For Drupal we use the Zend Http Client, so we use its URI factory to validate.
			try {
				require_once 'Zend/Uri.php';
				$uri = Zend_Uri::factory( $url );
			} catch( Exception $e ) {
				$uri = null;
			}
			if( !$uri ) {
				$this->setResult( 'ERROR',
					'The "Web Site URL" option for Publication Channel "'.$pubChannel->Name.'" is not set.', $help );
			} else if( substr( $uri, -1 ) != '/' ) {
				$this->setResult( 'ERROR',
					'The "Web Site URL" option for Publication Channel "'.$pubChannel->Name.'"" should end with a slash (/).', $help );
			}
		}

		$consumerKey = WW_Utils_PublishingUtils::getAdmPropertyValue( $pubChannel, WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_KEY );
		if( empty($consumerKey) ) {
			$this->setResult( 'ERROR',
				'The "Consumer Key" option for Publication Channel "'.$pubChannel->Name.'" is not set.', $help );
		}

		$consumerSecret = WW_Utils_PublishingUtils::getAdmPropertyValue( $pubChannel, WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_SECRET );
		if( empty($consumerSecret) ) {
			$this->setResult( 'ERROR',
				'The "Consumer Secret" option for Publication Channel "'.$pubChannel->Name.'" is not set.', $help );
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
		$help = 'Click <a href="'.$channelUrl.'" target="_blank">here</a> to verify the configuration.';

		// check if the config works
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
				if (count($result['Version'])){
					if(empty($errorMessage)) {
						$errorMessage .= $header;
					}
					foreach ($result['Version'] as $error){
						$errorMessage .= $error . "<br />\n";
					}
					$this->setResult( 'ERROR', $errorMessage,
						'Reinstall the ww_enterprise module in Drupal '.
						'by using the version shipped with this version of Enterprise Server. ' .
						'In Drupal, make sure that the module is re-loaded on the Modules page.');
				}
			}
		} catch (Exception $e) {
			$this->setResult('ERROR', $e->getMessage(), $help);
		}
	}
}

