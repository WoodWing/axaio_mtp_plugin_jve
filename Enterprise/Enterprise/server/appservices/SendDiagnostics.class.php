<?php
/**
 * Diagnostic service. Handles diagnostics send by client applications.<br>
 * 
 * When an error occurs a BizException will be thrown.<br>
 * 
 * @package Enterprise
 * @subpackage BizServices
 * @since v7.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class SendDiagnostics
{
	/**
	 * @param string $ticket Session ticket.
	 * @param string $category Category of diagnostic (user/system generated).
	 * @param string $synopsis Summary of the diagnostics.
	 * @param string $description More info about the circumstances etc.
	 * @param array $attachment List of attachments like screen shot, log files etc.
	 * @return array List of info with the operation status (Success/Failure)
	 * @throws BizException on error.
	 */
	public static function execute( $ticket, $category, $synopsis, $description, $attachment )
	{
		// Start business session, create DB instance etc.
		BizSession::startSession( $ticket );
		BizSession::startTransaction();

		try {
			// Read the resource table from disk
			require_once BASEDIR.'/server/bizclasses/BizDiagnostics.class.php';
			$ret = BizDiagnostics::handle( $category, $synopsis, $description, $attachment );
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

