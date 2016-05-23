<?php

require_once BASEDIR . '/server/interfaces/plugins/connectors/Session_EnterpriseConnector.class.php';

class Elvis_Session extends Session_EnterpriseConnector {
	
	private static $LAST_KEEP_ALIVE_CALLED_TIME = 'last_keep_alive_called_time';
	
	public function ticketExpirationReset($ticket, $userShort)
	{
		require_once dirname(__FILE__) . '/util/ElvisSessionUtil.php';

		$sessionIDAvailable = ElvisSessionUtil::isSessionIdAvailable();
		
		LogHandler::Log(__CLASS__, 'DEBUG', "ticket=$ticket, userShort=$userShort, available=$sessionIDAvailable");
		
		if (!$sessionIDAvailable) {
			return true;
		}
		
		$sessionID = ElvisSessionUtil::getSessionId();
		$cred = ElvisSessionUtil::getCredentials();
		$clientID = ElvisSessionUtil::getClientId();
		
		LogHandler::Log(__CLASS__, 'DEBUG', "sessionID=$sessionID, cred=$cred, clientID=$clientID");
		
		$lastCalledTime = ElvisSessionUtil::getSessionVar(self::$LAST_KEEP_ALIVE_CALLED_TIME);
		//LogHandler::logPhpObject($lastCalledTime);
		if (!$lastCalledTime) {
			ElvisSessionUtil::setSessionVar(self::$LAST_KEEP_ALIVE_CALLED_TIME, time());
			return true;
		}
		
		$timeDelta = time() - $lastCalledTime;
		LogHandler::Log(__CLASS__, 'DEBUG', "sessionID=$sessionID, timeDelta=$timeDelta");
		
		if ($timeDelta > 120 /* 2 minutes */) {
			$url = ELVIS_URL . '/alive.txt?_=' . time() . ';jsessionid=' . $sessionID;
			$ch = curl_init($url);
			LogHandler::log(__CLASS__, 'DEBUG', 'URL called for keep alive: '.$url);
			if (!$ch) {
				$message = 'Failed to create a curl handle with url: ' . $url;
				throw new BizException(null, 'Server', $message, $message);
			}
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$success = curl_exec($ch);
			if (!$success) {
				$errno = curl_errno($ch);
				
				//Throw understandable error to user, but log real error on server
				$message = 'curl_exec failed with error code: ' . $errno;
				LogHandler::Log('ELVIS', 'ERROR', $message);
				
				$message = 'The Elvis server is not available at: ' . ELVIS_URL . '. Please contact your system administrator to check if the Elvis server is running and properly configured for Enterprise.';
				throw new BizException(null, 'Server', $message, $message);
			}
			curl_close($ch);
			
			$time = time();
			LogHandler::Log(__CLASS__, 'DEBUG', 'Updating session variable with new time: ' . $time);
			ElvisSessionUtil::setSessionVar(self::$LAST_KEEP_ALIVE_CALLED_TIME, $time);
		}
		
		return true;
	}
}

?>
