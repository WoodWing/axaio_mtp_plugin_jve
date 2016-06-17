<?php

/**
 * LDAP authorization business rules class.<br>
 *
 * Uses LDAP server to determine if user has access to Enterprise system.<br>
 * 
 * @package Enterprise
 * @subpackage BizClasses
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */ 

class BizLDAP
{
	const ERR_INVALID_CREDENTIALS = 0x31;  // = 49
	const UF_ACCOUNTDISABLED = 0x2;        // User disabled flag
	
	protected $host;
	protected $port;
	protected $connection;
	protected $bindDN;
	protected $bindPassword;
	protected $searchDN;
	protected $searchAttName;
	protected $groupAttName;
	protected $uidAttName;
	protected $attMap;
	protected $groupClass;
	protected $excludeUserNames;
	protected $emailNotifications;
	protected $fullNameSeparator;

	/**
	 * Returns whether or not LDAP can be used
	 *
	 * @return boolean true when LDAP can be used otherwise false
	 */
	public static function isInstalled()
	{
		return defined('LDAP_SERVERS') ? true : false;
	}
	
	/**
	 * Set LDAP server to use
	 *
	 * @param LDAPServer $server
	 * @param string $name
	 * @param string $password
	 */
	public function setServer( LDAPServer $server, $name, $password )
	{
		LogHandler::Log(__CLASS__, 'DEBUG', 'Set LDAP server to ' . $server->ServerIP . ':' . $server->PortNr);
		$this->host = $server->ServerIP;
		$this->port = $server->PortNr;
		$this->bindDN = null;
		$this->bindPassword = null;
		$this->searchDN = null;
		$this->searchAttName = null;
		$this->groupAttName = 'memberof';
		$this->uidAttName = 'dn';
		$this->attMap = array();
		$this->groupClass = 'group';
		$this->excludeUserNames = array();
		$this->emailNotifications = null;
		$this->fullNameSeparator = '';
		
		if( is_array($server->Options) ) {
			if( array_key_exists('AUTH_USER', $server->Options) ) {
				$this->bindDN = str_replace('%username%', $name, $server->Options['AUTH_USER']);
			}
			if( array_key_exists('AUTH_PASSWORD', $server->Options) ) {
				$this->bindPassword = str_replace('%password%', $password, $server->Options['AUTH_PASSWORD']);
			}
			if( array_key_exists('BASE_DN', $server->Options) ) {
				$this->searchDN = $server->Options['BASE_DN'];
			}
			if( array_key_exists('USERNAME_ATTRIB', $server->Options) ) {
				$this->searchAttName = $server->Options['USERNAME_ATTRIB'];
			}
			if( array_key_exists('GROUPMEMBER_ATTRIB', $server->Options) ) {
				$this->groupAttName = $server->Options['GROUPMEMBER_ATTRIB'];
			}
			/* disabled UNIQUEID_ATTRIB so that we always use DN
			  if (array_key_exists('UNIQUEID_ATTRIB', $server->Options)){
				$this->uidAttName = $server->Options['UNIQUEID_ATTRIB'];
			}*/
			if( array_key_exists('ATTRIB_MAP', $server->Options) ) {
				$this->attMap = $server->Options['ATTRIB_MAP'];
			}
			if ( array_key_exists( 'FULLNAME_SEPARATOR', $server->Options )) {
				$this->fullNameSeparator = $server->Options['FULLNAME_SEPARATOR']; 
			}
			if( array_key_exists('GROUP_CLASS', $server->Options) ) {
				$this->groupClass = $server->Options['GROUP_CLASS'];
			}
			if( array_key_exists('EXCLUDE_USERNAMES', $server->Options) ) {
				$this->excludeUserNames = $server->Options['EXCLUDE_USERNAMES'];
			}
			if( array_key_exists('EMAIL_NOTIFICATIONS', $server->Options) ) {
				$this->emailNotifications = $server->Options['EMAIL_NOTIFICATIONS'];
			}
		}
	}
	
	/**
	 * Authenticate username and password against configured LDAP servers in configserver.php
	 *
	 * @param string $name
	 * @param string $password unencrypted password
	 * @throws BizException
	 * @return AdmUser
	 */
	public function authenticate($name, $password)
	{
		$user = null;
		
		$servers = BizSettings::getLdapServers();
		$ldapErr = array();
		foreach ($servers as $server){
			$this->setServer($server, $name, $password);
			$user = $this->excludeUser($name);
			if (! is_null($user)){
				// BZ#14329 Skip user for current server, ID Server users should excuded for every LDAP server
				continue;
			}
			$connectRes = $this->connect(null, null, $ldapErr);
			if ($connectRes){
				$filter = $this->searchAttName . '=' . $name;
				$searchRes = ldap_search($this->connection, $this->searchDN, $filter);
				if ($searchRes !== FALSE){
					$entries = ldap_get_entries($this->connection, $searchRes);
					if ($entries !== FALSE && $entries['count'] == 1){
						// ok, found user now check if we can connect with the given username and password, for Windows AD this might
						// already be done for searching the user (when %username% has been used), but it cannot hurt to do it a second time
						$dn = $this->getAttributeValue($entries[0], 'dn');
						$this->disconnect();
						if (!$this->connect($dn, $password)) {
							continue; // Connection with password failed. Try next ldap server
						}
						$user = $this->getEnterpriseUser($entries[0], $name, $password);
						$groupsToRemove = array();
						$groupsToAdd = array();
						$groupsCurrent = array();
						if (! is_null($this->groupAttName) ){
							// manage groups in LDAP
							LogHandler::Log(__CLASS__, 'DEBUG', 'Groups are managed in LDAP, synchronize LDAP memberships with Enterprise memberships');
							$this->getUpdateEnterpriseGroupMembership($entries[0], $user, $groupsToRemove, $groupsToAdd, $groupsCurrent);
							$groupRights = DBUser::getRightsByUserGroups(array_merge($groupsCurrent, $groupsToAdd));
							if (empty($groupRights)){
								throw new BizException('ERR_AUTHORIZATION', 'Client', 'User LDAP groups don\'t have any rights in Enterprise');
							}
							
						} else {
							LogHandler::Log(__CLASS__, 'DEBUG', 'Groups are managed in Enterprise');
							if (! $user->Id){
								// BZ#13452 User cannot have Enterprise groups if user doesn't exist yet
								// No groups, no rights so don't create unwanted user
								// If groups are managed in Enterprise it would be useful to import LDAP users, see BZ#7054
								throw new BizException('ERR_AUTHORIZATION', 'Client', 'User doesn\'t have any rights in Enterprise');
							}
						}
						// save user
						if ($user->Id){
							// update user
							DBUser::modifyUsersObj(array($user));
						} else {
							// add new user to Enterprise
							$user = DBUser::createUserObj($user);
						}
						// update groups
						if (!empty($groupsToAdd) || !empty($groupsToRemove)){
							require_once BASEDIR . '/server/bizclasses/BizUser.class.php';
							BizUser::resetMemberships( $user->Id, $groupsToRemove, $groupsToAdd );
						}
						// stop searching
						break;
					} else {
						LogHandler::Log(__CLASS__, 'DEBUG', 'Not one or invalid results for  ' . $name .' with base DN ' . $this->searchDN . ' and filter ' . $filter);
					}
				} else {
					LogHandler::Log(__CLASS__, 'DEBUG', 'Couldn\'t find  ' . $name .' with base DN ' . $this->searchDN . ' and filter ' . $filter);
				}
				$this->disconnect();
			} else {
				LogHandler::Log(__CLASS__, 'DEBUG', 'Couldn\'t connect to ' . $server->ServerIP .' with user ' . $this->bindDN);
			}
		}
		if (is_null($user)){
			if (isset($ldapErr[self::ERR_INVALID_CREDENTIALS])) {
				throw new BizException( 'ERR_WRONGPASS', 'Client',  $ldapErr[self::ERR_INVALID_CREDENTIALS]);
			} else {
				throw new BizException( 'ERR_AUTHSERVER_NONEAVAIL', 'Server', '' );
			}
		}
		
		return $user;
	}
	
	/**
	 * Connect to LDAP server and bind.
	 *
	 * @param string $bindDN override configured bind DN
	 * @param string $bindPassword override configured bind password
	 * @param array $ldapErr Stack that consists ldap errors.
	 * @return boolean true when successful otherwise false
	 */
	protected function connect($bindDN = null, $bindPassword = null, &$ldapErr = array())
	{
		if (is_null($bindDN)){
			$bindDN = $this->bindDN;
		}
		if (is_null($bindPassword)){
			$bindPassword = $this->bindPassword;
		}
		$result = false;
		$this->connection = ldap_connect($this->host, $this->port);
		if ($this->connection){
			// Set LDAP version number
			//Needed for more reliable integration
			ldap_set_option( $this->connection, LDAP_OPT_PROTOCOL_VERSION, 3 );
			//Must set Referrals due to bug in Active Directory searching
			//We are not currently searching the AD scheme for attributes related to the user...but we could.
			ldap_set_option( $this->connection, LDAP_OPT_REFERRALS, 0 );
			
			$result = @ldap_bind( $this->connection, $bindDN, $bindPassword );
			if (! $result){
				$errno = ldap_errno($this->connection);
				$error = ldap_error($this->connection);
				if ($errno == self::ERR_INVALID_CREDENTIALS){
					$this->disconnect();
					$ldapErr[$errno] = $error;
				}
				// other error => continue trying other servers
				LogHandler::Log(__CLASS__, 'DEBUG', $error);
			}
		} else {
			LogHandler::Log(__CLASS__, 'WARN', 'Failed to connect to ' . $this->host . ' on  port ' . $this->port);
		}
		
		return $result;
	}
	
	/**
	 * Disconnect form currently connected server
	 *
	 */
	protected function disconnect()
	{
		ldap_close($this->connection);
	}
	
	/**
	 * Update AdmUser to reflect parameters found in LDAP.
	 * By default the passwords are managed by LDAP. From Enterprise perspective the password is fixed.
	 *
	 * @param AdmUser $admUser
	 * @param array $attributes
	 * @param string $password
	 * @return AdmUser
	 */
	protected function updateAdminUser(AdmUser $admUser, $attributes, $password)
	{
		$map = $this->attMap;
		// Name map is fixed to searchAttName
		$map['Name'] = $this->searchAttName;
		
		foreach ($map as $key => $value){
			$attValue = $this->getAttributeValue($attributes, $value);
			if (! is_null($attValue) && property_exists($admUser, $key)){
				$admUser->$key = $attValue;
			}
		}
		// Always set the password
		$admUser->Password = $password;
		// The password is managed by Ldap/AD. 
		$admUser->FixedPassword = true;

        // After logon user is no longer partial in the system
        if ( $admUser->ImportOnLogon ) {
            $admUser->ImportOnLogon = false;
        }
		
		//TODO find solution for special properties like booleans
		// user disabled is special (and only for Windows AD?)
		/* it hasn't been use in previous version, so it's now disabled
		$admUser->Deactivated = false;
		$attValue = $this->getAttributeValue($attributes, 'useraccountcontrol');
		if (! is_null($attValue) && ($attValue & self::UF_ACCOUNTDISABLED)){
			$admUser->Deactivated = true;
		}
		*/
		
		return $admUser;
	}
	
	/**
	 * Return LDAP attribute value or default value when $name is not found.
	 *
	 * @param array $attributes LDAP attributes
	 * @param array|string $mapping Mapping between LDAP and Enterprise
	 * @param string $default optional
	 * @return null|string Null if no mapping is found else the (concatenated) value(s)  
	 */
	protected function getAttributeValue($attributes, $mapping, $default = null)
	{
		$result = $default;
		if ( is_array( $mapping )) {
			$separator = '';
			foreach ( $mapping as $map ) {
				$value = '';
				if ( $this->getLdapAttributeValue($attributes, $map, $value )) {
					$result .= $separator.$value;
					$separator = $this->fullNameSeparator; 
				}
			}
		} else {
			$value = '';
			if ( $this->getLdapAttributeValue($attributes, $mapping, $value )) {
				$result = $value;
			}
		}

		return $result;
	}

	/**
	 * Checks if their is a LDAP attribute that can be mapped. If so the value of
	 * that attribute is passed to $value. 
	 * 
	 * @param array $attributes  LDAP attributes
	 * @param string $map Specific mapping
	 * @param string $value Value of the LDAP attribute
	 * @return boolean True if an attribute is found else false.
	 */
	private function getLdapAttributeValue( $attributes, $map, &$value )
	{
		$result = false;
		$map = strtolower( $map );
		if ( isset( $attributes[$map] ) ) {
			$attrValue = $attributes[$map];
			$result = true;
			if ( is_array( $attrValue ) ) {
				$value = $attributes[$map][0];
			} else {
				$value = $attributes[$map];
			}
		}

		return $result;
	}

	/**
	 * Returns an external id from LDAP attributes
	 *
	 * @param array $attributes
	 * @return string
	 */
	protected function getExternalId($attributes)
	{
		$externalId = $this->getAttributeValue($attributes, $this->uidAttName);
		// if external id is not a text string encode it (database can only store utf8 text)
		if (preg_match('/[\x00-\x1F\x7F-\xFF]/', $externalId) === 1){
			$externalId = base64_encode($externalId);
		}
		
		return $externalId;
	}
	
	/**
	 * Get an Enterprise user from LDAP attributes but do not update Enterprise DB yet
	 *
	 * @param array $attributes
	 * @param string $name
	 * @param string $password
	 * @throws BizException
	 * @return AdmUser
	 */
	protected function getEnterpriseUser($attributes, $name, $password)
	{
		require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';

		$externalId = $this->getExternalId($attributes);
		$users = DBUser::getUsersByWhere('`externalid` = ?', array($externalId));
		$count = count($users);
		if ($count == 0){
			// try to find user by name (backwards compatible and for migrations)
			$users = DBUser::getUsersByWhere('`user` = ?', array($name));
			$count = count($users);
		}
		if ($count == 0){
			// add user to enterprise
			$admUser = new AdmUser();
			// ExternalId doesn't exist in WSDL and thus in class
			$admUser->ExternalId = $externalId;
			// BZ#30110 - When option is true, enable user Email Notification for user and usergroup
			if( $this->emailNotifications === true ) {
				$admUser->EmailUser = true;
				$admUser->EmailGroup= true;
			}
			$this->updateAdminUser($admUser, $attributes, $password);
		} else if ($count == 1){
			// update user
			$admUser = $users[0];
			$admUser->ExternalId = $externalId;
			$this->updateAdminUser($admUser, $attributes, $password);
		} else {
			throw new BizException('ERR_DATABASE', 'Server', 'Found ' . $count
				. ' matching LDAP users in Enterprise (externalid: ' . $externalId . ', name: ' . $name . ')');
		}
		
		return $admUser;
	}
	
	/**
	 * Get usergroups to remove from or add to Enterprise.
	 *
	 * @param array $attributes
	 * @param AdmUser $user
	 * @param array $toRemove
	 * @param array $toAdd
	 * @param array $current
	 */
	protected function getUpdateEnterpriseGroupMembership($attributes, AdmUser $user, array &$toRemove, array &$toAdd, array &$current)
	{
		$userLDAPGroups = $this->getUserGroups($attributes);
		// all enterprise groups
		$allEntGroups = DBUser::getUserGroups();
		// put all ent groups in external id => id and name => id map
		$allEntGroupsMapExtId = array();
		$allEntGroupsMapName = array();
		foreach ($allEntGroups as $entGroup){
			if ( ! empty($entGroup['externalid']) ){
				$allEntGroupsMapExtId[$entGroup['externalid']] = $entGroup['id'];
			}
			$allEntGroupsMapName[strtolower($entGroup['name'])] = $entGroup['id'];
		}
		// user memberships
		$userEntGroups = array();
		if ($user->Id){
			$userEntGroups = DBUser::getMemberships($user->Id);
		}
		// put user memberships in external id => id and name => id map
		$userEntGroupsMapExtId = array();
		$userEntGroupsMapName = array();
		foreach ($userEntGroups as $entGroup){
			$userEntGroupsMapExtId[$entGroup['externalid']] = $entGroup['id'];
			$userEntGroupsMapName[strtolower($entGroup['name'])] = $entGroup['id'];
			// select all memberships for removal
			$toRemove[$entGroup['id']] = true;
		}
		// determine which enterprise memberships to add
		$toAdd = array();
		foreach ($userLDAPGroups as $userLDAPGroup){
			// read LDAP usergroup information
			$readRes = ldap_read($this->connection, $userLDAPGroup, 'objectClass=*', array($this->uidAttName));
			$entries = ldap_get_entries($this->connection, $readRes);
			if ($entries['count'] == 1){
				$externalId = $this->getExternalId($entries[0]);
				$cn = strtolower($this->getCommonName($userLDAPGroup));
				// first try to match on external id
				if (array_key_exists($externalId, $allEntGroupsMapExtId)){
					// ldap group exists in enterprise groups, check user memberships
					if (! array_key_exists($externalId, $userEntGroupsMapExtId)){
						// ldap group name doesn't exist in user memberships, so add it
						$toAdd[] = $allEntGroupsMapExtId[$externalId];
					} else {
						// remove from delete list, see below
						unset($toRemove[$allEntGroupsMapExtId[$externalId]]);
						// add to current
						$current[] = $allEntGroupsMapExtId[$externalId];
					}
				} else if (array_key_exists($cn, $allEntGroupsMapName)){
					// ldap group exists in enterprise groups, check user memberships
					if (! array_key_exists($cn, $userEntGroupsMapName)){
						// ldap group name doesn't exist in user memberships, so add it
						$toAdd[] = $allEntGroupsMapName[$cn];
					} else {
						// remove from delete list, see below
						unset($toRemove[$allEntGroupsMapName[$cn]]);
						// add to current
						$current[] = $allEntGroupsMapName[$cn];
					}
					//TODO should we update group with the external id?
				}
			}
		}
		// keys are the group ids, make them values
		$toRemove = array_keys($toRemove);
	}
	
	/**
	 * Return usergroups found in LDAP attributes
	 *
	 * @param array $attributes
	 * @return array with group DNs
	 */
	protected function getUserGroups($attributes)
	{
		$groups = array();
		// search on GROUPMEMBER_ATTRIB
		if (isset($attributes[$this->groupAttName])) {
			$memberOf = $attributes[$this->groupAttName];
			$count = intval($memberOf['count']);
			if ($count > 0){
				for ($i = 0; $i < $count; $i++){
					$groups[] = $memberOf[$i];
				}
			}
		}
		// search on member DN (Windows AD)
		$dn = $this->getAttributeValue($attributes, 'dn');
		$searchRes = ldap_search($this->connection, $this->searchDN, '(member=' . $dn .')');
		if ($searchRes !== FALSE){
			$entries = ldap_get_entries($this->connection, $searchRes);
			if ($entries !== FALSE){
				$count = $entries['count'];
				for ($i = 0; $i < $count; $i++){
					$dnGroup = $this->getAttributeValue($entries[$i], 'dn');
					if (! is_null($dnGroup)){
						$groups[] = $dnGroup;
					}
				}
			}
		}
		// search on memberUid (OpenLDAP)
		$uid = $this->getAttributeValue($attributes, 'uid');
		$searchRes = ldap_search($this->connection, $this->searchDN, '(memberUid=' . $uid .')');
		if ($searchRes !== FALSE){
			$entries = ldap_get_entries($this->connection, $searchRes);
			if ($entries !== FALSE){
				$count = $entries['count'];
				for ($i = 0; $i < $count; $i++){
					$dnGroup = $this->getAttributeValue($entries[$i], 'dn');
					if (! is_null($dnGroup)){
						$groups[] = $dnGroup;
					}
				}
			}
		}
		$groups = array_unique($groups);

		return $groups;
	}
	
	/**
	 * Return the common name (CN) from a distinguished name (DN)
	 *
	 * @param string $dn distinguished name 
	 * @return string CN or empty string when not found
	 */
	protected function getCommonName($dn)
	{
		$commaSplit = explode(',', $dn);
		foreach ($commaSplit as $part){
			$isSplit = explode('=', $part);
			if (count($isSplit) == 2 && strtolower($isSplit[0]) == 'cn'){
				return $isSplit[1];
			}
		}
		
		return '';
	}
	
	/**
	 * Returns groups found matching the $search parameter in the first LDAP server that
	 * successfully connects 
	 *
	 * @param string $name
	 * @param string $password
	 * @param string $domain
	 * @param string $search
	 * @return array of AdmUserGroup
	 */
	public function findGroups($name, $password, $domain, $search)
	{
		require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';
		
		$groups = array();
		
		$servers = BizSettings::getLdapServers();
		$search = '*' . $search;
		if (strlen($search) > 1){
			// search with ** doesn't work, so do this only when search has been entered
			$search .= '*';
		}
		foreach ( $servers as $server ) {
			if ( $server->SuffixDNS == $domain ) {
				$this->setServer( $server, $name, $password );
				$connectRes = $this->connect();
				if ( $connectRes ) {
					$filter = '(&(objectclass=' . $this->groupClass . ')(cn=' . $search . '))';
					$searchRes = ldap_search( $this->connection, $this->searchDN, $filter );
					if ( $searchRes !== FALSE ) {
						$entries = ldap_get_entries( $this->connection, $searchRes );
						if ( $entries !== FALSE ) {
							$count = $entries['count'];
							for ( $i = 0; $i < $count; $i ++ ) {
								$entry = $entries[$i];
								$group = new AdmUserGroup();
								$group->Name = $this->getAttributeValue( $entry, 'cn', '' );
								$group->Description = $this->getAttributeValue( $entry, 'description', '' );
								// ExternalId doesn't exist in WSDL and thus in class
								$group->ExternalId = $this->getExternalId( $entry );
								$groups[] = $group;
							}
						} else {
							LogHandler::Log( __CLASS__, 'DEBUG', 'Not one or invalid results for  ' . $name . ' with base DN ' . $this->searchDN . ' and filter ' . $filter );
						}
					} else {
						LogHandler::Log( __CLASS__, 'DEBUG', 'Couldn\'t find  ' . $name . ' with base DN ' . $this->searchDN . ' and filter ' . $filter );
					}
					$this->disconnect();
				} else {
					LogHandler::Log( __CLASS__, 'DEBUG', 'Couldn\'t connect to ' . $server->ServerIP . ' with user ' . $this->bindDN );
				}
			}
		}
		
		return $groups;
	}
	
	/**
	 * Checks whether or not the giver user should not be authenticated agains LDAP.
	 * E.g. woodwing user or BrandStation ID server user (BZ#14329)
	 *
	 * @param string $userName
	 * @return AdmUser excluded user or null when no user found
	 */
	protected function excludeUser($userName)
	{
		$excludedUser = null;
		$matchedUserName = '';
		foreach ($this->excludeUserNames as $excludedUserName){
			if (function_exists('fnmatch')) { //BZ#19157 fnmatch is only supported on Windows from PHP 5.3 onwards.
				if (fnmatch($excludedUserName, $userName)){ 
				// match with fnmatch because it's more convenient for non-programming users
					$matchedUserName = $userName;
					LogHandler::Log(__CLASS__, 'DEBUG', 'Found excluded LDAP auth user "' . $matchedUserName . '" matched against "' . $excludedUserName . '"');
					break;
				}
			}	 
			else {
				if (preg_match("/$excludedUserName/", $userName)){	
					$matchedUserName = $userName;
					LogHandler::Log(__CLASS__, 'DEBUG', 'Found excluded LDAP auth user "' . $matchedUserName . '" matched against "' . $excludedUserName . '"');
					break;
				}
			}
		}	
		if ($matchedUserName != ''){
			// with LDAP, only check on short user name
			$users = DBUser::getUsersByWhere('`user` = ?', array($matchedUserName));
			if (count($users) == 1){
				$excludedUser = $users[0];
			}
		}
		
		return $excludedUser;
	}
}
