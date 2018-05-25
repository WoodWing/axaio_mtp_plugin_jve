<?php
/**
 * @since v8.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_WflLogon_TestCase extends TestCase
{
	private $testOptions = null; // Test suite options
	private $utils = null; // WW_Utils_TestSuite
	private $ticket = null; // session ticket
	
	// Brand setup entities to work on, taken from LogOn response:
	private $publication = null;
	private $pubChannels = null;
	private $printPubChannel = null;
	private $printIssue = null;
	
	// Brand setup entities to work on, dynamically created.
	private $webPubChannel = null;
	private $webIssue = null;

	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Checks if user can logon to the application server to further the features testing.'; }
	public function getTestMethods() { return 'The service response is used to lookup the Brand, Channel, Issue, Category and some object statuses.'; }
    public function getPrio()        { return 1; }
	
	final public function runTest()
	{
		// Initialize
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$activatedMcpPlugin = null;
		$activatedOpenCalaisPlugin = null;
		$activatedPreviewPlugin = null;
		$activatedStandaloneAutocompletePlugin = null;
		
		do {
			// Validate the configured TESTSUITE option.
			if( !$this->getTestSuiteData() ) {
				break;
			}
	
			// Make sure that the sample plugin is enabled.
			$activatedMcpPlugin = $this->utils->activatePluginByName( $this, 'MultiChannelPublishingSample' );
			if( is_null( $activatedMcpPlugin ) ) {
				break;
			}

			// Make sure that the OpenCalais plugin is enabled.
			$activatedOpenCalaisPlugin = $this->utils->activatePluginByName( $this, 'OpenCalais' );
			if ( is_null( $activatedOpenCalaisPlugin ) ) {
				break;
			}

			// Make sure that the Preview plugin is enabled to have thumbnails generated.
			$activatedPreviewPlugin = $this->utils->activatePluginByName( $this, 'PreviewMetaPHP' );
			if( is_null( $activatedPreviewPlugin ) ) {
				break;
			}

			// Make sure that the Standalone Autocomplete plugin is enabled to test autocomplete against a static dictionary.
			$activatedStandaloneAutocompletePlugin = $this->utils->activatePluginByName( $this, 'StandaloneAutocompleteSample' );
			if( is_null( $activatedStandaloneAutocompletePlugin ) ) {
				break;
			}

			// LogOn test user through workflow interface.
			$response = $this->utils->wflLogOn( $this );
			$this->ticket = isset($response->Ticket) ? $response->Ticket : null;
			if( is_null($this->ticket) ) {
				break;
			}
			
			// Resolve the admin entities from brand setup (respecting the TESTSUITE option).
			if( !$this->getAdminSetupFromLogOnResponse( $response ) ) {
				break;
			}
			
			// Create a PubChannel with an Issue to let successor test cases work on it.
			if( !$this->setupPubChannelAndIssue() ) {
				break;
			}
		} while( false );
		
		// Save found test data into session for successor test cases.
		// This data is picked up by successor TestCase modules within this TestSuite.
		$vars = array();
		$vars['BuildTest_MultiChannelPublishing']['ticket'] = $this->ticket;
		$vars['BuildTest_MultiChannelPublishing']['activatedMcpPlugin'] = $activatedMcpPlugin;
		$vars['BuildTest_MultiChannelPublishing']['activatedOpenCalaisPlugin'] = $activatedOpenCalaisPlugin;
		$vars['BuildTest_MultiChannelPublishing']['activatedPreviewPlugin'] = $activatedPreviewPlugin;
		$vars['BuildTest_MultiChannelPublishing']['testOptions'] = $this->testOptions;
		$vars['BuildTest_MultiChannelPublishing']['publication'] = $this->publication;
		$vars['BuildTest_MultiChannelPublishing']['pubChannels'] = $this->pubChannels; // All Pub Channels of the BuildTest's Brand.
		$vars['BuildTest_MultiChannelPublishing']['printPubChannel'] = $this->printPubChannel;
		$vars['BuildTest_MultiChannelPublishing']['printIssue'] = $this->printIssue;
		$vars['BuildTest_MultiChannelPublishing']['webPubChannel'] = $this->webPubChannel; 
		$vars['BuildTest_MultiChannelPublishing']['webIssue'] = $this->webIssue;
		$this->setSessionVariables( $vars );
	}
	
	/**
	 * Creates a PubChannel and Issue for the publish system "MultiChannelPublishingSample".
	 *
	 * @return bool Whether or not the creations were successful.
	 */
	private function setupPubChannelAndIssue()
	{
		$retVal = true;

		// Compose postfix for issue/channel names.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;
		
		// Create a PubChannel.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$admPubChannel = new AdmPubChannel();
		$admPubChannel->Name = 'PubChannel '.$postfix;
		$admPubChannel->Description = 'Created by Build Test class: '.__CLASS__;
		$admPubChannel->Type = 'web';
		$admPubChannel->PublishSystem = 'MultiChannelPublishingSample';
		$pubChannelResp = $this->utils->createNewPubChannel( $this, $this->ticket, 
											$this->publication->Id, $admPubChannel );
		$this->webPubChannel = null;
		if( isset( $pubChannelResp->PubChannels[0] ) ) {
			$this->webPubChannel = $pubChannelResp->PubChannels[0];
		} else {
			$retVal = false;
		}
		
		// Create an Issue for the PubChannel.
		$this->webIssue = null;
		if( $this->webPubChannel ) {
			$admIssue = new AdmIssue();
			$admIssue->Name = 'Issue '.$postfix;
			$admIssue->Description = 'Created by Build Test class: '.__CLASS__;
			$issueResp = $this->utils->createNewIssue( $this, $this->ticket, 
											$this->publication->Id, $this->webPubChannel->Id, $admIssue );
			if( isset( $issueResp->Issues[0] ) ) {
				$this->webIssue = $issueResp->Issues[0];
			} else {
				$retVal = false;
			}
		}
		return $retVal;
	}
	
	/**
	 * Resolve the admin entities from brand setup to test with (as configured through TESTSUITE option).
	 *
	 * @param WflLogOnResponse $response
	 * @param bool Whether or not all test data could be found.
	 * @return bool
	 */	
	private function getAdminSetupFromLogOnResponse( WflLogOnResponse $response )
	{
		$this->publication = null;
		$this->pubChannels = null;
		$this->printIssue = null;

		// Determine the brand+channels and the print channel+issue to work with.
		if( count($response->Publications) > 0 ) {
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

			// Search for the Brand specified in the TESTSUITE['Brand'] option.
			foreach( $response->Publications as $pub ) {
				if( $pub->Name == $this->testOptions['Brand'] ) {

					// Remember the Brand and its Pub Channels.
					$this->publication = $pub;
					$this->pubChannels = $pub->PubChannels;
					if( !self::validatePublishSystemsForPubChannels( $this->pubChannels ) ) {
						return false;
					}

					// Search for the desired print Issue and Pub Channel.
					foreach( $pub->PubChannels as $pubChannel ) {
						foreach( $pubChannel->Issues as $issue ) {
							if( $issue->Name == $this->testOptions['Issue'] ) {
								$this->printIssue = $issue;
								if ( $pubChannel->Type == 'print' ) {
									$this->printPubChannel = $pubChannel;
								} else {
									$this->setResult( 'ERROR', 'The configured issue for print publishing "'.
										$this->testOptions['Issue'] . '" has a Publication Channel that has the Publication Channel Type set to "'.$pubChannel->Type.'".',
										'Please make sure the Pub Channel "'.$pubChannel->Name.'" for the '.
											'Issue "' . $this->testOptions['Issue'] .'" is set to Publication Channel Type "print".' );
									return false;
								}
							}
						}
					}
				}
			}
		}
		
		// Validate the found admin entities.
		if( !$this->publication ) {
			$this->setResult( 'ERROR', 'Could not find the test Brand "'.$this->testOptions['Brand'].'". ',
				'Please check the TESTSUITE setting in configserver.php and/or check your Brand setup.' );
			return false;
		}
		if( !$this->pubChannels ) {
			$this->setResult( 'ERROR', 'Could not find any Publish Channels '.
				'configured for test Brand "'.$this->testOptions['Brand'].'". ',
				'Please check the TESTSUITE setting in configserver.php and/or check your Brand setup.' );
			return false;
		}
		if ( !$this->printIssue ) {
			$this->setResult( 'ERROR', 'Could not find the test Issue "'.$this->testOptions['Issue'].'" '.
				'configured for test Brand "'.$this->testOptions['Brand'].'". ',
				'Please check the TESTSUITE setting in configserver.php and/or check your Brand setup.' );
			return false;
		}
		
		return true;
	}

	/**
	 * Retrieves the TestSuite options and validates them.
	 *
	 * Tests all the standard options that need to be present to test this case.
	 *
	 * User - The User to log in with and execute the case with.
	 * Password - The password for the User.
	 * Brand - The brand to use during the test.
	 * Issue - The issue to be used during the test.
	 * SoapUrlDebugParams - Debugging parameters, need to be present but may be empty.
	 *
	 * This function stores the TestSuite options for this test.
	 *
	 * @return bool Whether or not the values were read out correctly and are all present.
	 */
	private function getTestSuiteData()
	{
		// Retrieve the TestSuite options defined in configserver.php.
		$helpText = 'Please check the TESTSUITE setting in configserver.php.';
		require_once BASEDIR.'/config/configserver.php';
		$testOptions = (defined('TESTSUITE')) ? unserialize( TESTSUITE ) : array();
		$errorString = '';

		if( !$testOptions ){
			$errorString = 'TestSuite options are not defined.';
			$result = false;
		} else {
			// Proceed with retrieving and validating the values in the options.
			$empty = array();
			$required = array();
			$options = array('User' => true, 'Password' => true, 'Brand' => true, 'Issue' => true, 'SoapUrlDebugParams' => false );

			foreach( $options as $name => $isRequired ) {
				if ( $isRequired ) {
					if ( !in_array($name, array_keys($testOptions)) ) {
						 $required[] = $name;
						continue;
					}
					if ( empty($testOptions[$name]) ) {
						$empty[] = $name;
					}
				}
			}

			if (count($empty) > 0) {
				$errorString = 'There were errors reading the TESTSUITE options: ';
				$errorString .= ' Empty options: ' . implode(', ', $empty);
			}

			if (count($required) > 0) {
				$errorString = 'There were errors reading the TESTSUITE options: ';
				$errorString .= ' Required options are not set: ' . implode(', ', $required);
			}
		}

		if ($errorString == '') {
			$this->testOptions = $testOptions;
			$result = true;
		} else {
			$this->setResult( 'ERROR',$errorString, $helpText );
			$result = false;
		}

		return $result;
	}
	
	/**
	 * Checks the Publish Systems for the given Publication Channels.
	 *
	 * 1) Checks if the Publish System of the channel is known.
	 * 2) Check if the server plugin is enabled for the channel.
	 * 3) Checks if the PubChannelInfo->SupportForms for all given channels is returned correctly:
	 *      L> Twitter, Facebook, Drupal7, WordPress and MultiChannelPublishingSample (sample plugin) do support Forms, 
	 *         hence should return true.
	 *      L> AdobeDps2 and SMS do not support Forms, hence should return false.
	 *
	 * @param PubChannelInfo[] $pubChannels
	 * @return boolean True when all channels meet the listed criterea, else false.
	 */
	private function validatePublishSystemsForPubChannels( $pubChannels )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

		$isValid = true;
		$supportForms = array(
			'' => false, // Enterprise
			'Twitter' => true, 
			'Facebook' => true, 
			'Drupal7' => true,
			'Drupal8' => true,
			'WordPress' => true,
			'MultiChannelPublishingSample' => true,
			'PublishingTest' => false, // Analytics test
			'AdobeDps2' =>  false,
			'SMS' => false
		);
		$supportCropping = array(
			'' => false, // Enterprise
			'Twitter' => true,
			'Facebook' => true,
			'Drupal7' => true,
			'Drupal8' => true,
			'WordPress' => true,
			'MultiChannelPublishingSample' => false,
			'PublishingTest' => false, // Analytics test
			'AdobeDps2' =>  false,
			'SMS' => false
		);
		foreach( $pubChannels as $pubChannel ) {
			$publishSystem = DBChannel::getPublishSystemByChannelId( $pubChannel->Id );
			if( $publishSystem ) {
				if( !isset( $supportForms[$publishSystem] ) ) {
					$this->setResult( 'ERROR', 
						'The Publication Channel "'. $pubChannel->Name.'" is configured '.
						'under the TESTSUITE brand, but has an unknown Publish System. ',
						'Please remove the channel and the "'. $publishSystem.'" server plug-in '.
						'(or programmatically register the Publish System at the build test script).' );
					$isValid = false;
					continue; // avoid more errors on same channel, but continue validating other channels
				}
				if( !BizServerPlugin::isPluginActivated( $publishSystem ) ) {
					$this->setResult( 'ERROR', 
						'The Publication Channel "'. $pubChannel->Name.'" is configured '.
						'under the TESTSUITE brand, for which the Publish System "'. $publishSystem.'" '.
						'is configured. However, the server plug-in is not enabled. ', 
						'Please enable the "'. $publishSystem.'" server plug-in.' );
					$isValid = false;
					continue; // avoid more errors on same channel, but continue validating other channels
				}
				if( $supportForms[$publishSystem] != $pubChannel->SupportsForms ) {
					if( $supportForms[$publishSystem] ) {
						$errMsg =  'should have SupportsForms set to "true" but currently is set to "false", which is incorrect.';
					} else {
						$errMsg =  'should have SupportsForms set to "false" but currently is set to "true", which is incorrect.';				
					}
					$this->setResult( 'ERROR', 'The ['. $pubChannel->Name.'] channel ' . $errMsg, 
											'Please check the ['. $pubChannel->Name.'] plugin.' );
					$isValid = false;
					continue; // avoid more errors on same channel, but continue validating other channels
				}
				if( $supportCropping[$publishSystem] != $pubChannel->SupportsCropping ) {
					if( $supportCropping[$publishSystem] ) {
						$errMsg =  'should have SupportsCropping set to "true" but currently is set to "false", which is incorrect.';
					} else {
						$errMsg =  'should have SupportsCropping set to "false" but currently is set to "true", which is incorrect.';
					}
					$this->setResult( 'ERROR', 'The ['. $pubChannel->Name.'] channel ' . $errMsg,
						'Please check the ['. $pubChannel->Name.'] plugin.' );
					$isValid = false;
					continue; // avoid more errors on same channel, but continue validating other channels
				}
			}
		}	
		return $isValid;
	}
}
