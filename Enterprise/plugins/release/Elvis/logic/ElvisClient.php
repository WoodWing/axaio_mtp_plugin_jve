<?php

class ElvisClient
{
	/**
	 * Throws BizException for low level communication errors with Elvis Server.
	 *
	 * For ES 10.0 or later it throws a S1144 error else it throws S1069.
	 *
	 * @since 10.0.5 / 10.1.1
	 * @param string $detail
	 * @throws BizException
	 */
	public static function throwExceptionForElvisCommunicationFailure( $detail )
	{
		require_once BASEDIR . '/server/utils/VersionUtils.class.php';
		$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
		if( VersionUtils::versionCompare( $serverVer[0], '10.0.0', '>=' ) ) {
			throw new BizException( 'ERR_CONNECT', 'Server', $detail, null, array( 'Elvis' ) );
		} else {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', $detail );
		}
	}
}