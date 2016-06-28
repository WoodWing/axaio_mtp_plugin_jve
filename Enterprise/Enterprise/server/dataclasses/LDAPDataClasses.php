<?php
class LdapUserGroup
{
	/**
	 * @var string
	 */
	public $Name;
	/**
	 * @var string
	 */
	public $Description;
	/**
	 * @var string
	 */
	public $ExternalId;

	public function __construct( $name, $descr = null, $externalId  = null)
	{
		$this->Name   = $name;
		$this->Description = $descr;
		$this->ExternalId = $externalId;
	}
}

class LdapUser
{
	/**
	 * @var string
	 */
	public $UserID;
	/**
	 * @var string
	 */
	public $FullName;
	/**
	 * @var email
	 */
	public $Email;
	/**
	 * @var string
	 */
	 public $Disabled;

	public function __construct( $userid, $fullname = null, $email = null, $disabled = null )
	{
		$this->UserID   = $userid;
		$this->FullName = $fullname;
		$this->Email    = $email;
		$this->Disabled  = $disabled;
	}
}

class LdapUserAccount
{
	/**
	 * @var LdapUser; Connected LdapUser object
	 */	
	public $User;
	/**
	 * @var array of LdapUserGroup; Connected array of user memberships
	 */	
	public $Groups;
	/**
	 * @var resource; Connected LDAP resource
	 */	
	public $Resource;
	/**
	 * @var LDAPServer; Connected LDAPServer object
	 */	
	public $Server;
	/**
	 * @var NetworkDomain; Connected NetworkDomain object
	 */	
	public $Domain;


	public function __construct( $user, $groups = null, $resource = null, $server = null, $domain = null )
	{
		$this->User     = $user;
		$this->Groups   = $groups;
		$this->Resource = $resource;
		$this->Server   = $server;
		$this->Domain   = $domain;
	}	
}