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
	public function deleteExpiredTickets(): void
	{
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$expiredTicketsById = DBTicket::getExpiredTicketsById();
		if( $expiredTicketsById ) {
			$expiredTickets = array_map( function( $expiredTicketById ) { return $expiredTicketById['ticketid']; },
												$expiredTicketsById);
			$this->cleanUpTicketBasedStructures( $expiredTickets );
			DBTicket::deleteTicketsById( $expiredTicketsById );
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
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
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
	public function deleteTicket( string $ticket ): void
	{
		$this->cleanUpTicketBasedStructures( array( $ticket ) );
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		DBTicket::DBendticket( $ticket );
	}

	/**
	 * Deletes all tickets and related structures linked to a user.
	 *
	 * @param string $user
	 * @throws BizException In case of database connection error.
	 */
	public function deleteTicketsByUser( string $user ): void
	{
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$tickets = DBTicket::getTicketsByUser( $user );
		if( $tickets ) {
			$this->cleanUpTicketBasedStructures( $tickets );
			DBTicket::DbPurgeTicketsByUser( $user );
		}
	}
}
