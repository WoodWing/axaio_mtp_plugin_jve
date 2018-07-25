<?php
/**
 * LDAP TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v6.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_LDAP_TestCase extends TestCase
{
	public function getDisplayName() { return 'LDAP'; }
	public function getTestGoals()   { return 'Checks if LDAP has been configured correctly.'; }
	public function getTestMethods() { return 'Checks if it\'s possible to connect and bind to the LDAP servers.'; }
    public function getPrio()        { return 13; }
	
	final public function runTest()
	{
		// Check if ldap library is installed (only when used)
		if( defined('LDAP_SERVERS') ) {
			LogHandler::Log('wwtest', 'INFO', 'Using LDAP.');
			if (!extension_loaded('ldap')){
				$this->setResult( 'ERROR', "PHP library \"<b>ldap</b>\" not loaded, check php.ini.", '' );
			}
		} else {
			$this->setResult( 'NOTINSTALLED', 'Enterprise Server is not using LDAP. (The LDAP_SERVERS option at configserver.php is not configured.)' );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'LDAP_SERVERS setting has been checked');
		
		// check LDAP servers
		$ldapServers = unserialize(LDAP_SERVERS);
		foreach ($ldapServers as $ldapServer){
			$ldapConn = ldap_connect($ldapServer->ServerIP, $ldapServer->PortNr);
			if ($ldapConn !== FALSE) {

				// >>> BZ#23224
				// Set LDAP version number
				// Needed for more reliable integration
				ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );
				// Must set Referrals due to bug in Active Directory searching
				// We are not currently searching the AD scheme for attributes related to the user...but we could.
				ldap_set_option( $ldapConn, LDAP_OPT_REFERRALS, 0 );
				// <<<

				// always try an anonymous connection
				if (ldap_bind($ldapConn)){
					ldap_unbind($ldapConn);
				} else {
					//TODO don't show error or warn if an anonymous connection is not allowed
					$this->setResult( 'ERROR', 'Could not bind to LDAP server ' . $ldapServer->ServerIP . ' port ' . $ldapServer->PortNr . '. LDAP error: ' . ldap_error($ldapConn));
				}
			} else {
				$this->setResult( 'ERROR', 'Could not connect to LDAP server ' . $ldapServer->ServerIP . ' port ' . $ldapServer->PortNr . '. LDAP error: ' . ldap_error($ldapConn));
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'LDAP_SERVERS have been tested');
	}
	
	/* COMMENTED OUT: It is more consistent to other tests to use the NOTINSTALLED option at runTest function instead
	public function isTestable()
	{
		if (defined('LDAP_SERVERS') ){
			return true;
		}
		return false;
	}
	
	public function getIsTestableReason()
	{
		if ($this->isTestable()){
			return '';
		}
		return 'LDAP has not been configured. Check configserver.php to configure it.';
	}*/
}
