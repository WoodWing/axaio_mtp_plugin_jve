<?php
/**
 * LDAP Server data class.<br>
 *
 * This is a data class which tells how to connect to a LDAP server.<br>
 * A list of LDAP servers is configured in the configserver.php file.<br>
 * That way, client applications can let end-users validate their password through LDAP.<br>
 * 
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 class LDAPServer
{
	public $ServerIP;
	public $PortNr;
	public $SuffixDNS;
    public $Options;

	/**
	 * Construct the LDAP Server.<br>
	 *
     * @param string $serverIP       IP network number of LDAP server machine
     * @param int    $portNr         Port number of LDAP server. When null given, default 389 is used.
     * @param string $suffixDNS      Primary DNS Suffix
     * @param array  $options        Key Value Array with options for binding, searching and conversion of users and groups:
     *                               AUTH_USER          User to bind to LDAP server, %username% will be replaced by entered username
     *                               AUTH_PASSWORD      Password to bind to LDAP server, %password% will be replaced by entered username
	 *                               BASE_DN            Search Base e.g. 'dc=example,dc=com'
	 *                               USERNAME_ATTRIB    LDAP attribute that will be matched against entered username (e.g. sAMAccountName, uid)
	 *                               GROUPMEMBER_ATTRIB LDAP attribute that will be used to find usergroups
	 *                               ATTRIB_MAP         array with the mapping of Enterprise attributes to LDAP attributes
	 *                               GROUP_CLASS        LDAP objectClass for groups (e.g. 'group', 'posixGroup')
     */	
	public function __construct( $serverIP, $portNr, $suffixDNS, $options = array())
	{
		$this->ServerIP = $serverIP;
		$this->PortNr = $portNr;
		$this->SuffixDNS = $suffixDNS;
        $this->Options = $options;
	}
}