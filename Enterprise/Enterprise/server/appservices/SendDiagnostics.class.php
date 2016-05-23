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
	 * 
	 * @param type $ticket
	 * @param type $category
	 * @param type $synopsis
	 * @param type $description
	 * @param type $attachment
	 * @return type
	 * @throws type
	 */
	public static function execute( $ticket, $category, $synopsis, $description, $attachment )
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		
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

