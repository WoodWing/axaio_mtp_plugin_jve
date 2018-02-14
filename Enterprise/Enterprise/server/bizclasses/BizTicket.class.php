<?php
/**
 * @package   Enterprise
 * @subpackage   BizClasses
 * @since      v10.2.1
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class BizTicket
{
	/**
	 * Checks if there are expired tickets and deletes them.
	 *
	 * Tickets are also used to create workspaces and message queues. When the ticket is deleted these structures are
	 * also deleted.
	 *
	 * @throws  BizException In case of database connection error.
	 */
	public function deleteExpiredTicketsAndAffiliatedStructures(): void
	{
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$expiredTicketsById = DBTicket::getExpiredTicketsIndexedById();
		if( $expiredTicketsById ) {
			$this->cleanUpTicketBasedStructures( $expiredTicketsById );
			DBTicket::deleteTicketsById( array_keys( $expiredTicketsById ) );
		}
	}

	/**
	 * Cleans up structures related to tickets.
	 *
	 * Typically called when tickets are deleted.
	 *
	 * @param string[] $tickets
	 */
	private function cleanUpTicketBasedStructures( array $tickets ): void
	{
		BizSession::purgeSessionWorkspaces( $tickets );
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		BizMessageQueue::removeOrphanQueuesByTickets( $tickets );
	}

	/**
	 * Deletes a ticket and all related structures.
	 *
	 * @param string $ticket
	 * @throws BizException In case of database connection error.
	 */
	public function deleteTicketAndAffiliatedStructures( string $ticket ): void
	{
		$this->cleanUpTicketBasedStructures( array( $ticket ) );
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		DBTicket::DBendticket( $ticket );
	}

	/**
	 * Deletes all tickets and related structures linked to a user.
	 *
	 * @param string $user Short user name.
	 * @throws BizException In case of database connection error.
	 */
	public function deleteTicketsAndAffiliatedStructuresByUser( string $user ): void
	{
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$tickets = DBTicket::getTicketsByUser( $user );
		if( $tickets ) {
			$this->cleanUpTicketBasedStructures( $tickets );
			DBTicket::DbPurgeTicketsByUser( $user );
		}
	}
}
