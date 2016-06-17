<?php
/**
 * Publish to DRUPAL TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_DrupalPublish_TestCase extends TestCase
{
	public function getDisplayName()  { return 'Drupal 6'; }
	public function getTestGoals()    { return 'Checks if the Publish to DRUPAL plug-in is correctly configured. '; }
	public function getTestMethods()  { return ''; }
	public function getPrio ()        { return 22; }

	final public function runTest()
	{
		// Step 1: Check if the DRUPAL_SITES option is configured correctly.
		if( !$this->hasError() ) {
			$this->validateSitesOption();
			LogHandler::Log( 'DrupalPublish', 'INFO', 'Checked the DRUPAL_SITES option.' );
		}
		
		// Step 2: Check if we can login to Drupal and if the versions are compatible.
		if( !$this->hasError() ) {
			$this->validateDrupalConnection();
			LogHandler::Log( 'DrupalPublish', 'INFO', 'Validated the Drupal connection.' );
		}
	}
	
	/**
	 * Checks if the DRUPAL_SITES option is configured correctly.
	 * If not, an ERROR is raised which can be requested by $this->hasError().
	 */	
	private function validateSitesOption()
	{
		require_once BASEDIR . '/config/config_drupal.php'; // DRUPAL_SITES
		require_once 'Zend/Uri.php';
		$help = 'Check the DRUPAL_SITES option in the config/config_drupal.php file.';
		
		// Error and bail out when the DRUPAL_SITES is not set.
		if( !defined('DRUPAL_SITES')  || !DRUPAL_SITES ) {
			$this->setResult( 'ERROR', 'No Drupal sites configured.', $help );
		} else {
			$sites = unserialize( DRUPAL_SITES );
			if( count($sites) <= 0 ) {
				$this->setResult( 'ERROR', 'No Drupal sites configured.', $help );
			}
		}
		
		// Check all the sites configured for the DRUPAL_SITES option.
		if( !$this->hasError() ) foreach( $sites as $siteName => $site ) {
			foreach( array( 'username', 'password', 'url' ) as $option ) {
				if( !array_key_exists( $option, $site ) || empty($site[$option]) ) {
					$this->setResult( 'ERROR', 
						'No "'.$option.'" option configured for the "'.$siteName.'" site.', $help );
				} else if( !is_string($site[$option]) ) {
					$this->setResult( 'ERROR', 
						'The "'.$option.'" option configured for the "'.$siteName.'" site '.
						'is not a string. Please use quotes.', $help );
				} else if( $option == 'url' ) {
					// For Drupal we use the Zend Http Client, so we use its URI factory to validate.
					try {
						$uri = Zend_Uri::factory( $site['url'] );
					} catch( Exception $e ) {
						$e = $e;
						$uri = null;
					}
					if( !$uri ) {
						$this->setResult( 'ERROR', 
							'The "'.$option.'" option configured for the "'.$siteName.'" site '.
							'is not a valid URL.', $help );
					} else if( substr( $uri, -1 ) != '/' ) {
						$this->setResult( 'ERROR', 
							'The "'.$option.'" option configured for the "'.$siteName.'" site '.
							'does not end with a slash (/). Please add a slash.', $help );
					}
				}
			}
		}
	}
	
	/**
	 * Checks if we can connect and login to Drupal. It calls the XML RPC function
	 * "enterprise.testConfig" and validates version information.
	 * If not, an ERROR is raised which can be requested by $this->hasError().
	 */
	private function validateDrupalConnection()
	{	
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';

		// Test if connector can be loaded
		$connector = BizServerPlugin::searchConnectorByClassName( 'Drupal_PubPublishing' );
		if( is_null($connector) ) {
			$this->setResult( 'ERROR', 'Could not load the Drupal PubPublishing connector.' );
			return;
		}
		
		// check if the config works
		try {
			$errmsg = '';
			
			$result = $connector->testConfig();
			// don't show output from the above request on test page
			ob_clean();
			if (count($result['Errors'])){
				$errmsg = "Errors from Drupal:<br />\n";
				foreach ($result['Errors'] as $error){
					$errmsg .= $error . "<br />\n";
				}
				$this->setResult( 'ERROR', $errmsg, 
					'Check your options in the DRUPAL_SITES setting of the config_drupal.php file, '.
					'make sure that the site configuration in Drupal is correct and that the '.
					'ww_enterprise module is enabled on the Modules admin page.');
			} else {
				if (count($result['Version'])){
					if(empty($errmsg)) {
						$errmsg .= "Errors from Drupal:<br />\n";
					}
					foreach ($result['Version'] as $error){
						$errmsg .= $error . "<br />\n";
					}
					$this->setResult( 'ERROR', $errmsg, 
						'Reinstall the ww_enterprise module in Drupal '.
						'with the version shipped with this Enterprise Server and ' .
						'make sure the ww_enterprise module is re-loaded in the Drupal admin page. '.
						'This can be done on the Modules admin page.');
				}
			}
		} catch (Exception $e) {
			$this->setResult('ERROR', $e->getMessage(), 'Check your config setting url');
		}
	}
}