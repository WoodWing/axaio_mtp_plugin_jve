<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v8.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Gives a server plug-in (connector) the ability to manage seats on its integrated system
 * (at the same time this is done by the core server for the Enterprise system).
 * 
 * Integration tips:
 * - Implement the LogOn and LogOff connectors and use the runAfter() method to
 *   see who is getting or releasing Enterprise tickets.
 * - Use BizSession class to retrieve all kind of session dependent information (such
 *   as client application name and service name) in case your checks must be more specific.
 * - Store the ticket of the integrated system in the session variables of Enterprise
 *   by using getSessionVariables / setSessionVariables functions of the BizSession class.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class Session_EnterpriseConnector extends DefaultConnector
{
	/**
	 * When a client application talks to the server, a ticket is sent along with each
	 * web service request. When this ticket is (still) valid, the core server does reset 
	 * the expiration date. The client has then the full expiration time again, as defined 
	 * for the EXPIREDEFAULT or EXPIREDWEB options in the configserver.php file.
	 *
	 * On this event, the core server calls this function, which can be useful for a connector
	 * (e.g.ContentSource or PubPublishing) that integrates with an integrated system.
	 * For example, it could be needed to obtain a seat for the same user at the integrated
	 * system as well. In other terms, one user takes one seat at Enterprise and one seat
	 * at the integrated system.
	 *
	 * This function is only called when the ticket is valid and its expiration is succesfully
	 * reset. Then it is up to the connector to do the same for the integrated system.
	 * On success, the function should return TRUE so the core server continues normally.
	 * But on failure, the function should return FALSE, in which case the core server
	 * removes the ticket and raise the ticket expiration error. Clients detect this
	 * error and raise the (re)logon dialog to let the same user login again.
	 *
	 * This function can also be used to make Enterprise tickets invalid, using custom rules.
	 * For example it could check if the user has worked for 8 hours and make the ticket invalid.
	 *
	 * @param string $ticket     Enterprise ticket that was sucessfully reset.
	 * @param string $userShort  Short name of the user taking the seat.
	 * @return boolean TRUE to continue normally, or FALSE to raise ticket expiration error.
	 */
	public function ticketExpirationReset( $ticket, $userShort )
	{
		$ticket = $ticket; $userShort = $userShort; // keep analyzer happy
		return true;
	}

	// ===================================================================================

	// Generic methods that can be overruled by a connector:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that cannot be overruled by a connector:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
