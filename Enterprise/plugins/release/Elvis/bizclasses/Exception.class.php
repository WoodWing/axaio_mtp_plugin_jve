<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_Exception extends BizException
{

	/**
	 * Construct the Elvis_BizClasses_Exception.
	 *
	 * @param string $detail Error detail of code specifying the error.
	 * @param string|null $severity Severity / log level, such as 'ERROR', 'WARN', etc. See LogHandler for accepted values.
	 */
	public function __construct( $detail, $severity = null )
	{
		$localizedMessage = self::replaceLineEndingIfAny( BizResources::localize( 'Elvis.ERR_CONNECT' ) );
		parent::__construct( null, 'Server', $detail, $localizedMessage, null, $severity );
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
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/utils/VersionUtils.class.php';
		$clientName = BizSession::isStarted() ? BizSession::getClientName() : '';
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
}
