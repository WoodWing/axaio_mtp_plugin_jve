<?php
/**
 * Simulates a ticket expiration with an integrated system. There is no integrated system 
 * and so it fakes the expiration by letting 10 requests through before failing. It shows
 * how session variables can be used and how useful session data can be retrieved.
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/Session_EnterpriseConnector.class.php';

class RefreshRemoteTicket_Session extends Session_EnterpriseConnector
{
	public function ticketExpirationReset( $ticket, $userShort )
	{
		// Log function parameters how we got called by core.
		$debugLog = "ticketExpirationReset( Ticket:$ticket, User:$userShort )";
		LogHandler::Log( 'RefreshRemoteTicket', 'DEBUG', $debugLog );

		// Log useful session data we could use.
		$clientApp = BizSession::getClientName();
		LogHandler::Log( 'RefreshRemoteTicket', 'DEBUG', 'Client application name: '.$clientApp );
		$serviceName = BizSession::getServiceName();
		LogHandler::Log( 'RefreshRemoteTicket', 'DEBUG', 'Service name (context): '.$serviceName );
		
		// Retrieve session variables and keep track of our session counter and 'remote' ticket.
		$sessionVars = BizSession::getSessionVariables();
		if( !isset( $sessionVars['RefreshRemoteTicket'] ) ) {
			require_once BASEDIR.'/server/utils/NumberUtils.class.php';
			$sessionVars['RefreshRemoteTicket']['SessionCount'] = 0;
			$sessionVars['RefreshRemoteTicket']['RemoteTicket'] = NumberUtils::createGUID();
		}
		$sessionCount = $sessionVars['RefreshRemoteTicket']['SessionCount'];
		$remoteTicket = $sessionVars['RefreshRemoteTicket']['RemoteTicket'];
		LogHandler::Log( 'RefreshRemoteTicket', 'DEBUG', 'Session count: '.$sessionCount );
		LogHandler::Log( 'RefreshRemoteTicket', 'DEBUG', 'Remote ticket: '.$remoteTicket );
		
		// Increase the session counter and do not allow user to do more than 10 requests.
		$sessionCount++;
		$sessionVars['RefreshRemoteTicket']['SessionCount'] = $sessionCount;
		BizSession::setSessionVariables( $sessionVars );
		return $sessionCount <= 10;
	}
}
