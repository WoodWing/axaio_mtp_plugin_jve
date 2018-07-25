<?php
/**
 * @since v9.2.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_ContentSource_Setup_TestCase extends TestCase
{
	// Brand setup entities to work on, taken from LogOn response:
	private $publication = null;
	private $pubChannels = null;
	private $printPubChannel = null;
	private $printIssue = null;
	private $testOptions = null;

	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Checks if the basic environment can be setup properly.'; }
	public function getTestMethods() { return
		'Perform multiple services to setup the test environment.
		 <ol>
		 	<li>Make sure SimpleFileSystem plugin is installed and enabled.</li>
		 	<li>Logon user configured at TESTSUITE option in configserver.php.(LogOn)</li>
		 	<li>Retrieve all the necessary settings and set in the session variables.</li>
		 </ol> '; }
    public function getPrio()        { return 1; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$imageStatus = null;
		$articleStatus = null;
		$dossierStatus = null;
		$articleTemplateStatus = null;
		$activatedSFSPlugin = null;

		do {
			// Validate the configured TESTSUITE option.
			if( !$this->getTestSuiteData() ) {
				break;
			}

			// Make sure that the SimpleFileSystem(SFS) is enabled.
			$activatedSFSPlugin = $this->utils->activatePluginByName( $this, 'SimpleFileSystem' );
			if( is_null( $activatedSFSPlugin ) ) {
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

			// Pick a status for Images, Articles and Dossiers.
			$imageStatus   = $this->pickObjectTypeStatus( $this->publication, 'Image' );
			if( is_null( $imageStatus )) {
				break;
			}

			$articleStatus = $this->pickObjectTypeStatus( $this->publication, 'Article' );
			if( is_null( $articleStatus )) {
				break;
			}

			$dossierStatus = $this->pickObjectTypeStatus( $this->publication, 'Dossier' );
			if( is_null( $dossierStatus )) {
				break;
			}

			$articleTemplateStatus = $this->pickObjectTypeStatus( $this->publication, 'ArticleTemplate' );
			if( is_null( $articleTemplateStatus )) {
				break;
			}
		} while( false );

		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this ContentSource TestSuite.
		$vars = array();
		$vars['BuildTest_SFS']['ticket']                = $this->ticket;
		$vars['BuildTest_SFS']['publication']           = $this->publication;
		$vars['BuildTest_SFS']['pubChannels']           = $this->pubChannels; // All Pub Channels of the BuildTest's Brand.
		$vars['BuildTest_SFS']['printPubChannel']       = $this->printPubChannel;
		$vars['BuildTest_SFS']['printIssue']            = $this->printIssue;
		$vars['BuildTest_SFS']['testOptions']           = $this->testOptions;
		$vars['BuildTest_SFS']['imageStatus']           = $imageStatus;
		$vars['BuildTest_SFS']['articleStatus']         = $articleStatus;
		$vars['BuildTest_SFS']['dossierStatus']         = $dossierStatus;
		$vars['BuildTest_SFS']['articleTemplateStatus'] = $articleTemplateStatus;
		$vars['BuildTest_SFS']['activatedSFSPlugin']    = $activatedSFSPlugin;
		$this->setSessionVariables( $vars );
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
	 * Picks a status for a given object type that is configured for a given brand ($pubInfo).
	 * It prefers picking a non-personal status, but when none found and the Personal Status
	 * feature is enabled, that status is used as fall back. When none found an error is logged.
	 *
	 * @param PublicationInfo $pubInfo
	 * @param string $objType
	 * @return State|null Picked status, or NULL when none found.
	 */
	private function pickObjectTypeStatus( PublicationInfo $pubInfo, $objType )
	{
		$objStatus = null;
		if( $pubInfo->States ) foreach( $pubInfo->States as $status ) {
			if( $status->Type == $objType ) {
				$objStatus = $status;
				if( $status->Id != -1 ) { // Prefer non-personal status
					break;
				}
			}
		}
		if( !$objStatus ) {
			$this->setResult( 'ERROR',
				'Brand "'.$pubInfo->Name.'" has no '.$objType.' Status to work with.',
				'Please check the Brand Maintenance page and configure one.' );
		}
		return $objStatus;
	}
}