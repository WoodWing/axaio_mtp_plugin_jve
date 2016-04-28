<?php
/**
 * returns the username for logging in with MadeToPrint.
 * Enables the use of MadeToPrint with different servers
 * 
 * @package SCEnterprise
 * @subpackage MadeToPrint
 * @since v6.x
 * @copyright axaio software. All Rights Reserved.
 */

require_once dirname(__FILE__) . '/config.php';

ini_set('display_errors', '0');
set_time_limit(3600);

function getFilledUserArray()
{
	$users = array( 0 => AXAIO_MTP_USER);
	if( defined("AXAIO_MTP_MULTI_USERS"))
	{
		foreach( unserialize( AXAIO_MTP_MULTI_USERS) as $cur_ref => $cur_user)
		{	
			$users[ $cur_ref ] = $cur_user;
		}
	}
	
	return $users;
}

function getMTPUserName()
{
	$users = getFilledUserArray();


    $addr = $_SERVER['REMOTE_ADDR'];
	$host = gethostbyaddr( $addr );
	
	if( isset( $users[$addr] ) )
	{
		return $users[$addr];
  	}
  	
  	if( isset( $users[$host] ) )
	{
		return $users[$host];
  	}

	return $users[0];
}

print getMTPUserName();
exit;
