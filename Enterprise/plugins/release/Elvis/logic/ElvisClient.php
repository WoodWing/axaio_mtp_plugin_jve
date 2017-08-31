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
			$localizedMessage = self::replaceLineEndingIfAny( BizResources::localize( 'Elvis.ERR_CONNECT' ) );
			throw new BizException( null, 'Server', $detail, $localizedMessage );
		} else {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', $detail );
		}
	}

	/**
	 * Adds in the correct line ending supported by the clients in the localized string.
	 *
	 * Replaces all the string "#EOL#" in the localized string ( if there's any )
	 * with the correct line ending supported by the client.
	 *
	 * @param string $localized The localized string to be replaced with the correct line ending.
	 * @return string The localized string with the correct line ending supported by the client.
	 */
	private static function replaceLineEndingIfAny( $localized )
	{
		$lineEndingOccurance = strpos( $localized, '#EOL#' );
		if( $lineEndingOccurance !== false ) {
			$lineEnding = self::getCurrentClientSupportedLineEnding();
			$localized = str_replace( '#EOL#', $lineEnding, $localized );
		}
		return $localized;
	}

	/**
	 * Returns the line ending supported by the current client communicating with Enterprise.
	 *
	 * The line ending would be used in the error message details that are
	 * shown (thrown) to the end-user.
	 *
	 * @return string
	 */
	private static function getCurrentClientSupportedLineEnding()
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/utils/VersionUtils.class.php';
		$clientName = BizSession::getClientName();
		if( $clientName == 'Content Station' ) {
			$csVersion = BizSession::getClientVersion();
			if( VersionUtils::versionCompare( $csVersion, '10.0.0', '>=' ) ) {
				$lineEnding = "<br/>";
			} else {
				$lineEnding = "\n";
			}
		} else {
			$lineEnding = "\r\n";
		}
		return $lineEnding;
	}

	/**
	 * Returns the base URL of Elvis Server.
	 *
	 * @return string
	 * @since 10.1.4
	 */
	protected static function getElvisBaseUrl()
	{
		require_once __DIR__.'/../config.php'; // For ELVIS_URL constant.
		return ELVIS_URL;
	}
}