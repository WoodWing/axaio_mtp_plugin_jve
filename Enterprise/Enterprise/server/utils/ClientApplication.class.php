<?php
/**
 * Determine which client application is calling.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_ClientApplication
{
	/**
	 * Tell whether the client that calls the current service is Content Station.
	 *
	 * @returns bool
	 */
	static public function isContentStation(): bool
	{
		return self::isClient( 'Content Station' );
	}

	/**
	 * Tell whether the client that calls the current service is Smart Connection.
	 *
	 * @returns bool
	 */
	static public function isSmartConnection(): bool
	{
		return self::isClient( 'InDesign' ) || self::isClient( 'InCopy' );
	}

	/**
	 * Tell whether the client that calls the current service is Smart Connection for InDesign Server.
	 *
	 * @returns bool
	 */
	static public function isInDesignServer(): bool
	{
		return self::isClient( 'InDesign Server' );
	}

	/**
	 * Tell whether the client that calls the current service has the given name.
	 *
	 * @param string $clientName
	 * @return bool
	 */
	static private function isClient( string $clientName ): bool
	{
		$activeClient = BizSession::isStarted() ? BizSession::getClientName() : '';
		LogHandler::Log( 'ClientApplication', 'DEBUG', 'Client for ticket '.BizSession::getTicket().': '.$activeClient );
		return (bool)stristr( $activeClient, $clientName );
	}
}