<?php
/**
 * "Adobe DPS" TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/../../config.php'; // DPS2_PLUGIN_DISPLAYNAME
require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_AdobeDps2_TestCase extends TestCase
{
	/** @var AdmPubChannel[] $pubChannels List of all 'dps2' channels. */
	private $pubChannels = null;
	
	public function getDisplayName() { return DPS2_PLUGIN_DISPLAYNAME; }
	public function getTestGoals()   { return 'Checks if the \''.DPS2_PLUGIN_DISPLAYNAME.'\' server plug-in is correctly configured. '; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 0; }

	final public function runTest()
	{
		do {
			// Checks the 'dps2' type for configured pub channels.
			if( !$this->validatePubChannelsType() ) {
				break;
			}
			
			// Checks if the URLs in config/plugins/AdobeDps2/config.php are set.
			if( !$this->validateAdobeDpsUrls() ) {
				break;
			}
			
			// Check the configured URLs: Can we really connect to the Adobe DPS service?
			if( !$this->checkConnection() ) {
				break;
			}
			
			// Check if the registration procedure was executed successfully.
			if( !$this->validateRegistration() ) {
				break;
			}
			
			// Check if all pub channels are registered at Adobe DPS.
			// Note: must be called -after- checkConnection().
			if( !$this->validateProjectReferences() ) {
				break;
			}

			// Checks if there's status with ReadyToBePublished option enabled when 'dps2' channel is configured.
			if( !$this->validateReadyPublishStatus() ) {
				break;
			}
			
			// Checks if each edition has a device configured.
			if( !$this->validateDeviceEditionMapping() ) {
				break;
			}

			// Checks if the Email feature is enabled.
			if( !$this->isEmailEnabled()) {
				break;
			}
		} while( false ); // only once
	}
	
	/**
	 * Errors when there is no 'dps2' pub channel configured at all
	 * or when the type is badly configured.
	 *
	 * @return boolean TRUE when validation is OK, else FALSE.
	 */	
	private function validatePubChannelsType()
	{		
		// Error when no 'dps2' pub channels are configured.
		require_once dirname(__FILE__).'/../../bizclasses/Config.class.php';
		require_once dirname(__FILE__).'/../../utils/HttpClient.class.php';
		require_once dirname(__FILE__).'/../../utils/Folio.class.php';
		$this->pubChannels = AdobeDps2_BizClasses_Config::getPubChannels();
		if( !$this->pubChannels ) {
			$help = 'Please check the Publication Channel Maintenance pages.';
			$message = 'There are no \''.AdobeDps2_Utils_Folio::CHANNELTYPE.'\' Publication Channels configured.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}
		
		// Error in case one of the channels have no 'dps2' type.
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		if( $this->pubChannels ) foreach( $this->pubChannels as $pubId => $pubChannels ) {
			foreach( $pubChannels as $pubChannel ) {
				if( $pubChannel->Type != AdobeDps2_Utils_Folio::CHANNELTYPE ) {
					$pubId = DBChannel::getPublicationId( $pubChannel->Id );
					$help = 'Please change into \''.AdobeDps2_Utils_Folio::CHANNELTYPE.'\'.';
					$message = 'For the Publication Channel '.$this->composeChannelLink( $pubId, $pubChannel ).
						' the \''.BizResources::localize('CHANNEL_TYPE').'\' field is set to \''.$pubChannel->Type.'\'.';
					$this->setResult( 'ERROR', $message, $help );
					return false;
				}
			}
		}		
		return true;
	}

	/**
	 * Checks if the URLs in config/plugins/AdobeDps2/config.php are set.
	 *
	 * @return boolean TRUE when validation is OK, else FALSE.
	 */	
	private function validateAdobeDpsUrls()
	{
		// Error when mandatory options are missing in the config.php file.
		$help = 'Please check the config/plugins/AdobeDps2/config.php file.';

		$authenticationUrl = AdobeDps2_BizClasses_Config::getAuthenticationUrl();
		if( !is_string($authenticationUrl) || empty($authenticationUrl) ) {
			$message = 'No value for the DSP2_AUTHENTICATION_URL option configured.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}
		
		$authorizationUrl = AdobeDps2_BizClasses_Config::getAuthorizationUrl();
		if( !is_string($authorizationUrl) || empty($authorizationUrl) ) {
			$message = 'No value for the DSP2_AUTHORIZATION_URL option configured.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}
		
		$producerUrl = AdobeDps2_BizClasses_Config::getProducerUrl();
		if( !is_string($producerUrl) || empty($producerUrl) ) {
			$message = 'No value for the DSP2_PRODUCER_URL option configured.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}
		
		$ingestionUrl = AdobeDps2_BizClasses_Config::getIngestionUrl();
		if( !is_string($ingestionUrl) || empty($ingestionUrl) ) {
			$message = 'No value for the DSP2_INGESTION_URL option configured.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}
		return true;
	}
		

	/**
	 * Checks the connection to the defined Adobe DPS URLs
	 */
	private function checkConnection()
	{
		// The ENTERPRISE_CA_BUNDLE should be defined in configserver.php
		if ( !defined('ENTERPRISE_CA_BUNDLE') ) {
			$help = 'Make sure the ENTERPRISE_CA_BUNDLE define is present in configserver.php';
			$this->setResult( 'ERROR', 'The Enterprise CA bundle is not defined.', $help  );
		}

		require_once dirname(__FILE__).'/../../bizclasses/Config.class.php';
		require_once dirname(__FILE__).'/../../utils/HttpClient.class.php';

		// Create HTTP client.
		$authenticationUrl = AdobeDps2_BizClasses_Config::getAuthenticationUrl();
		$authorizationUrl = AdobeDps2_BizClasses_Config::getAuthorizationUrl();
		$producerUrl = AdobeDps2_BizClasses_Config::getProducerUrl();
		$ingestionUrl = AdobeDps2_BizClasses_Config::getIngestionUrl();
		$consumerKey = AdobeDps2_BizClasses_Config::getConsumerKey();
		$consumerSecret = AdobeDps2_BizClasses_Config::getConsumerSecret();
		$httpClient = new AdobeDps2_Utils_HttpClient( 
			$authenticationUrl, $authorizationUrl, $producerUrl, $ingestionUrl,
			$consumerKey, $consumerSecret );
	
		// Test connection with the Authentication server.
		$curlErrNr = null;
		if( !$httpClient->authenticationConnectionCheck( $curlErrNr ) ) {
			$this->handleCurlError( 'DSP2_AUTHENTICATION_URL', $authenticationUrl, $curlErrNr );
			return false;
		}

		// Test connection with the Authorization server.
		$curlErrNr = null;
		if( !$httpClient->authorizationConnectionCheck( $curlErrNr ) ) {
			$this->handleCurlError( 'DSP2_AUTHORIZATION_URL', $authorizationUrl, $curlErrNr );
			return false;
		}

		// Test connection with the Producer server.
		$curlErrNr = null;
		if( !$httpClient->producerConnectionCheck( $curlErrNr ) ) {
			$this->handleCurlError( 'DSP2_PRODUCER_URL', $producerUrl, $curlErrNr );
			return false;
		}

		// Test connection with the Ingestion server.
		$curlErrNr = null;
		if( !$httpClient->ingestionConnectionCheck( $curlErrNr ) ) {
			$this->handleCurlError( 'DSP2_INGESTION_URL', $ingestionUrl, $curlErrNr );
			return false;
		}
		return true;
	}
	
	/**
	 * Reports a cURL connection error.
	 *
	 * @param string $urlDefine Name of the URL define.
	 * @param string $url The connection URL being tested.
	 * @param integer $curlErrNr cURL error number
	 */
	private function handleCurlError( $urlDefine, $url, $curlErrNr )
	{
		$sslErrors = array(
			CURLE_SSL_CONNECT_ERROR,
			CURLE_SSL_ENGINE_NOTFOUND,
			CURLE_SSL_ENGINE_SETFAILED,
			CURLE_SSL_CERTPROBLEM,
			CURLE_SSL_CACERT,
			77 // CURLE_SSL_CACERT_BADFILE - Not in the PHP defined constants, but is returned by cURL
		);
		if ( in_array($curlErrNr, $sslErrors) ) {
			require_once BASEDIR . '/server/utils/UrlUtils.php';
			$testFile = BASEDIR.'/config/plugins/AdobeDps2/testsuite/HealthCheck2/caCertificates.php';
			$wwtestUrl = WW_Utils_UrlUtils::fileToUrl( $testFile, 'config', false );
			$help = ' Please run the <a href="'.$wwtestUrl.'" target="_blank">Get CA Certificates</a> page that will download the CA certificates.';
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. There seems to be a SSL certificate problem.', $help  );
			return;
		}

		$channelHelp = 'Please check the \''.$urlDefine.'\' option in your config/plugins/AdobeDps2/config.php file.';

		// Test of the peer certificate failed. This usually happens when you use the ip address instead of the URL.
		if ( $curlErrNr == CURLE_SSL_PEER_CERTIFICATE ) {
			$help = ' Make sure you use the correct URL, IP addresses are not allowed. '.$channelHelp;
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. There seems to be a SSL problem with the server certificate.', $help  );
			return;
		}

		if ( $curlErrNr == CURLE_UNSUPPORTED_PROTOCOL ) {
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The protocol is not supported by cURL.', $channelHelp  );
			return;
		}

		if ( $curlErrNr == CURLE_URL_MALFORMAT ) {
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The URL is malformed.', $channelHelp  );
			return;
		}

		if ( $curlErrNr == 4 ) { // cURL error CURLE_NOT_BUILT_IN constant is not defined, but is a valid error, hence using the number.
			$help = 'cURL could not find the feature that is requested. '.$channelHelp;
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'.', $help  );
			return;
		}

		if ( $curlErrNr == CURLE_COULDNT_RESOLVE_PROXY ) {
			$help = 'Please check the ENTERPRISE_PROXY setting in the configserver.php file. ';
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The proxy could not be resolved.', $help  );
			return;
		}

		if ( $curlErrNr == CURLE_COULDNT_RESOLVE_HOST ) {
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The host could not be resolved.', $channelHelp  );
			return;
		}

		if ( $curlErrNr == CURLE_COULDNT_CONNECT ) {
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'.', $channelHelp  );
			return;
		}
		
		$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. Unknown cURL error: '.$curlErrNr, $channelHelp  );
	}
	
	/**
	 * Check if the registration procedure was executed successfully.
	 *
	 * @return boolean TRUE when validation is OK, else FALSE.
	 */
	private function validateRegistration()
	{
		require_once dirname(__FILE__).'/../../config.php'; // DPS2_PLUGIN_DISPLAYNAME
		
		// Error when mandatory options are missing at the AP admin page.
		$help = 'Please click the \''.BizResources::localize('ACT_REGISTER').'\' button on the '.$this->composeRegistrationLink( DPS2_PLUGIN_DISPLAYNAME ).' admin page.';
		
		$consumerKey = AdobeDps2_BizClasses_Config::getConsumerKey();
		if( !is_string($consumerKey) || empty($consumerKey) ) {
			$message = 'No \'API Key\' configured.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}

		$consumerSecret = AdobeDps2_BizClasses_Config::getConsumerSecret();
		if( !is_string($consumerSecret) || empty($consumerSecret) ) {
			$message = 'No \'Secret\' configured.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}

		require_once dirname(__FILE__).'/../../bizclasses/Authorization.class.php';
		$bizAuth = new AdobeDps2_BizClasses_Authorization();
		if( !$bizAuth->hasDeviceToken() ) {
			$message = 'No Device Token available. ';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}

		if( !$bizAuth->hasDeviceId() ) {
			$message = 'No Device Id available. ';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}

		return true;
	}
	
	/**
	 * Checks if the Project reference (and id) fields are configured for Publication Channels.
	 * Those fields are only set when the registration procedure was executed successfully.
	 *
	 * @return boolean TRUE when validation is OK, else FALSE.
	 */
	private function validateProjectReferences()
	{
		require_once dirname(__FILE__).'/../../bizclasses/Config.class.php';
		require_once dirname(__FILE__).'/../../config.php'; // DPS2_PLUGIN_DISPLAYNAME

		$help = 'Please Register the channel at the '.$this->composeRegistrationLink( DPS2_PLUGIN_DISPLAYNAME ).' admin page.';
		$this->pubChannels = AdobeDps2_BizClasses_Config::getPubChannels();
		if( $this->pubChannels ) foreach( $this->pubChannels as $pubChannels ) {
			foreach( $pubChannels as $pubChannel ) {
				$projectRef = AdobeDps2_BizClasses_Config::getProjectRef( $pubChannel );
				$projectId = AdobeDps2_BizClasses_Config::getProjectId( $pubChannel );
				if( !is_string($projectRef) || empty($projectRef) ||
					!is_string($projectId) || empty($projectId) ) {
					$message = 'No \''.BizResources::localize('AdobeDps2.PROJECT').'\' is set for Publication Channel "'.$pubChannel->Name.'". ';
					$this->setResult( 'ERROR', $message, $help );
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * Checks if there's workflow status with "Ready to be Published" option enabled
	 * when 'dps2' channel is configured.
	 *
	 * When there's a publication channel of type 'dps2' defined, function
	 * checks if there's a workflow status for layout with 'Ready to be Published'
	 * option enabled for that specific brand.
	 *
	 * NOTE: The check in the function  doesn't take into account for 'overruleissues'.
	 * In the admin page, it is not possible to configure an 'overruleissue' when the
	 * channel type is set to 'dps2'.
	 *
	 * @return boolean TRUE when validation is OK, else FALSE.
	 */
	private function validateReadyPublishStatus()
	{
		require_once BASEDIR .'/server/dbclasses/DBWorkflow.class.php';

		if( $this->pubChannels ) foreach( array_keys($this->pubChannels) as $pubId ) {
			$statuses = DBWorkflow::listStatesCached( $pubId, 0, 0, 'Layout' );
			$foundStatus = false;
			if( $statuses ) foreach( $statuses as $status ) {
				if( $status['readyforpublishing'] == 'on' ) {
					$foundStatus = true;
					break;
				}
			}
			if( !$foundStatus ) {
				require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
				$pubName = DBPublication::getPublicationName( $pubId );
				$statusUrl = SERVERURL_ROOT.INETROOT.'/server/admin/states.php?publ='.$pubId.'&type=Layout';
				$help = 'Please check the Workflow Maintenance page.';
				$message = 'For Brand \'' .$pubName.'\', no Workflow Status for a '.
					'<a href="'.$statusUrl.'">layout</a> has been found that has \'Ready to be Published\' enabled.';
				$this->setResult( 'ERROR', $message, $help );
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks if each edition (configured for a 'dps2' channel) has a corresponding device configuration.
	 */
	private function validateDeviceEditionMapping()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmOutputDevice.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmEdition.class.php';
		require_once dirname(__FILE__).'/../../utils/Folio.class.php';

		$allOk = true;
		$help = 'Make sure that for Publication Channels of type \''.AdobeDps2_Utils_Folio::CHANNELTYPE.'\', '.
				'the name of each Edition matches the name of one of the Output Devices.';
		
		// Get all configured devices.
		$bizDevice = new BizAdmOutputDevice();
		$devices = $bizDevice->getDevices();
		
		// Iterate through the 'dps2' channels (which are grouped per brand id).
		if( $this->pubChannels ) foreach( $this->pubChannels as $pubId => $pubChannels ) {
			foreach( $pubChannels as $pubChannel ) {
				$editions = DBAdmEdition::listChannelEditionsObj( $pubChannel->Id );
				if( $editions ) {
					foreach( $editions as $edition ) {
						$foundDevice = false;
						foreach( $devices as $device ) {
							if( $edition->Name == $device->Name ) {
								$foundDevice = true;
								break;
							}
						}
						if( !$foundDevice ) { // no matching device for this edition
							$message = 'For Publication Channel '.$this->composeChannelLink( $pubId, $pubChannel ).
									' the Edition \''.$edition->Name.'\' has no matching Output Device.';
							$this->setResult( 'ERROR', $message, $help );
							$allOk = false;
						}
					}
				} else { // no editions for this channel
					$message = 'No Editions are configured for Publication Channel '.$this->composeChannelLink( $pubId, $pubChannel ).
							'. At least one Edition is required.  ';
					$this->setResult( 'ERROR', $message, $help );
					$allOk = false;
				}
			}
		}
		return $allOk;
	}

	/**
	 * Checks if the Email feature is enabled.
	 *
	 * @return bool Returns true when the email feature is enabled, false otherwise.
	 */
	private function isEmailEnabled()
	{
		require_once dirname(__FILE__).'/../../bizclasses/Email.class.php';
		if( !AdobeDps2_BizClasses_Email::isEmailEnabled() ) {
			$message = 'The e-mail feature (needed for informing a user by e-mail about a failed publish action) is disabled.';
			$help = 'Configure the \'EMAIL_SMTP\' option in the configserver.php file.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}
		return true;
	}
	
	/**
	 * Composes a HTML hyperlink to a Publication Channel Maintenance page.
	 *
	 * @param integer $pubId Brand id of the channel.
	 * @param AdmPubChannel $pubChannel Channel to compose the link for.
	 * @return string The composed HTML hyperlink.
	 */
	private function composeChannelLink( $pubId, AdmPubChannel $pubChannel )
	{
		$channelUrl = SERVERURL_ROOT.INETROOT.
			'/server/admin/editChannel.php?publid='.$pubId.'&channelid='.$pubChannel->Id;
		return '<a href="'.$channelUrl.'">\''. $pubChannel->Name.'\'</a>';
	}
	
	/**
	 * Composes a HTML hyperlink to a Adobe DPS admin page.
	 *
	 * @param string $text
	 * @return string The composed HTML hyperlink.
	 */
	private function composeRegistrationLink( $text )
	{
		$url = SERVERURL_ROOT.INETROOT.
			'/server/admin/webappindex.php?webappid=Admin&plugintype=config&pluginname=AdobeDps2';
		return '<a href="'.$url.'">\''.$text.'\'</a>';
	}
}

