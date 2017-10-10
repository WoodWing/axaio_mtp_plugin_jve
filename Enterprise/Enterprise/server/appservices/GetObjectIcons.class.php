<?php
/**
 * Get Object Icons - Business service.<br>
 *
 * When an error occurs a BizException will be thrown.<br>
 * 
 * @package Enterprise
 * @subpackage BizServices
 * @since v6.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class GetObjectIcons
{
	public static function execute( $ticket, $iconMetrics )
	{
		// Start business session, create DB instance etc.
		BizSession::startSession( $ticket );
		BizSession::startTransaction();
		try {
			// Read the resource table from disk
			$ret = null;
			$objTypes = BizResources::getObjectIcons( $iconMetrics );
			// flatten the structure
			foreach( $objTypes as $objType ) {
				foreach( $objType as $iconInfo ) {
					$ret[] = $iconInfo;
				}
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
