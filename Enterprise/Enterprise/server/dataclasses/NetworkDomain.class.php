<?php
/**
 * Network Domain data class.<br>
 *
 * This is a data class used to specify network domains.<br>
 * A list of domains is configured in the configserver.php file.<br>
 * That way, client applications can let end-users type DNS suffixes which are matched with the domains.<br>
 * 
 * @package SCEnterprise
 * @subpackage DataClasses
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 class NetworkDomain
{
	public $SuffixDNS;
	public $SearchList;
	
	public function __construct( $suffixDNS, $searchList )
	{
		$this->SuffixDNS = $suffixDNS;
		$this->SearchList = $searchList;
	}
}