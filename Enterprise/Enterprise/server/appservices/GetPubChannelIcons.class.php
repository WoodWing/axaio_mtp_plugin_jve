<?php
/**
 * Get Publiation Channel Icons - Business service.<br>
 *
 * When an error occurs a BizException will be thrown.<br>
 * 
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class GetPubChannelIcons
{
	public static function execute( $ticket, $iconMetrics )
	{
		// Start business session, create DB instance etc.
		BizSession::startSession( $ticket );
		BizSession::startTransaction();
		try {
			// Read the resource table from disk
			$ret = null;
			$iconInfos = BizResources::getPubChannelIcons( $iconMetrics );
			// flatten the structure (remove keys)
			foreach( $iconInfos as $iconInfo ) {
				$ret[] = $iconInfo;
			}
		} catch ( BizException $e ) {
			// Cancel session and re-throw exception to stop the service:
			BizSession::cancelTransaction();
			BizSession::endSession();
			throw( $e );
		}
		BizSession::endTransaction();
		BizSession::endSession();
		return $ret;
	}
}
