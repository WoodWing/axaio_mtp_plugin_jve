<?php
require_once BASEDIR.'/server/interfaces/plugins/connectors/Session_EnterpriseConnector.class.php';

class Elvis_Session extends Session_EnterpriseConnector
{
	private static $LAST_KEEP_ALIVE_CALLED_TIME = 'last_keep_alive_called_time';

	public function ticketExpirationReset( $ticket, $userShort )
	{
		require_once dirname(__FILE__).'/util/ElvisSessionUtil.php';
		$sessionVariables = ElvisSessionUtil::getSessionVariables();
		$sessionIDAvailable = ElvisSessionUtil::isSessionIdAvailable( $sessionVariables );
		LogHandler::Log(  __CLASS__, 'DEBUG', "ticket=$ticket, userShort=$userShort, available=$sessionIDAvailable" );
		if( !$sessionIDAvailable ) {
			return true;
		}

		$time = time();
		$lastCalledTime = ElvisSessionUtil::getSessionVar( self::$LAST_KEEP_ALIVE_CALLED_TIME, $sessionVariables );
		if( !$lastCalledTime ) {
			ElvisSessionUtil::setSessionVar( self::$LAST_KEEP_ALIVE_CALLED_TIME, $time );
			return true;
		}

		$timeDelta = $time - $lastCalledTime;
		LogHandler::Log( __CLASS__, 'DEBUG', "timeDelta=$timeDelta" );
		if( $timeDelta > 120 /* 2 minutes */ ) {

			require_once dirname(__FILE__).'/logic/ElvisRESTClient.php';
			ElvisRESTClient::keepAlive( $time );

			LogHandler::Log( __CLASS__, 'DEBUG', 'Updating session variable with new time: ' . $time );
			ElvisSessionUtil::setSessionVar( self::$LAST_KEEP_ALIVE_CALLED_TIME, $time );
		}

		return true;
	}
}
