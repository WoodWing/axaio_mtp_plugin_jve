<?php
/**
 * Instantiate Widget - Business service.<br>
 *
 * When an error occurs a BizException will be thrown.<br>
 * 
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class InstantiateWidget
{
	public static function execute( $ticket, $dossierId, $widgetId, $layoutId, $editionId, $artboard, $location, $manifest, $pageSequence )
	{
		// Start business session, create DB instance etc.
		BizSession::startSession( $ticket );
		BizSession::startTransaction();
		try {
			// Read the resource table from disk
			require_once dirname(__FILE__).'/widgets/Widgets.class.php';
			$widget = new Widget( $dossierId, $widgetId, $layoutId, $editionId, $artboard, $location, $manifest, $pageSequence );
			$attachment = $widget->instantiate();
			$ret =  $attachment ? $attachment :  null;
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
