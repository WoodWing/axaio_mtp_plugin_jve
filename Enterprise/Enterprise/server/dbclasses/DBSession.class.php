<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       10.5.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * For the current user session, this class requests the DB to resolve ticket info, user info the Enterprise System ID.
 * Since the network access time to the DB is relatively expensive, all info is requested in just one SQL statement.
 * The results are cached in memory so it can be requested again within the same service without need for DB access.
 */
class WW_DbClasses_Session extends DBBase
{
	/** @var array */
	private $sessionUserRow;

	/** @var array */
	private $sessionTicketRow;

	/** @var string  */
	private $enterpriseSystemId;

	/**
	 * Setup the memory cache with session info. See module header for details.
	 *
	 * @param string $ticket
	 * @return bool Whether or not initialized.
	 */
	private function init( $ticket )
	{
		// Get values from multiple tables with the following SQL statement (in MySQL notation):
		//    SELECT t.`usr`, t.`appname`, t.`appversion`, t.`expire`, t.`masterticketid`,
		//       c.`value` AS 'enterprise_system_id', u.*
		//    FROM `smart_tickets` t, `smart_config` c, `smart_users` u
		//    WHERE t.`ticketid` = '...' AND c.`name` = 'enterprise_system_id' AND t.`usr` = u.`user`

		$tables = array( 't' => 'tickets', 'c' => 'config', 'u' => 'users' );
		$fields = array(
			't' => array( 'usr', 'appname', 'appversion', 'expire', 'masterticketid' ),
			'c' => array( 'enterprise_system_id' => 'value' ),
			'u' => array( '*' )
		);
		$where = 't.`ticketid` = ? AND c.`name` = ? AND t.`usr` = u.`user`';
		$params = array( strval( $ticket ), 'enterprise_system_id' );
		$row = self::getRow( $tables, $where, $fields, $params );
		if( $row ) {
			$excludeFieldsFromUserRow = array_merge( $fields['t'], array( 'enterprise_system_id' ) );
			$this->sessionUserRow = array_diff_key( $row, array_flip( $excludeFieldsFromUserRow ) );
			$this->sessionTicketRow = array_intersect_key( $row, array_flip( $fields['t'] ) );
			$this->enterpriseSystemId = $row['enterprise_system_id'];
		}
		return (bool)$row;
	}

	/**
	 * @return array DB record from smart_users table containing all fields.
	 */
	public function getSessionUserRow()
	{
		return $this->sessionUserRow;
	}

	/**
	 * @return array DB record from the smart_tickets table containing fields: 'usr', 'appname', 'appversion', 'expire', 'masterticketid'
	 */
	public function getSessionTicketRow()
	{
		return $this->sessionTicketRow;
	}

	/**
	 * Provide the Enterprise System ID
	 *
	 * This ID is needed to access the local cache of this application server.
	 * See BizLocalCache class for more information.
	 *
	 * @return string Enterprise System ID (GUID)
	 */
	public function getEnterpriseSystemId()
	{
		return $this->enterpriseSystemId;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// The below enforce this class can only be used as a singleton.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/** @var WW_DbClasses_Session The instance of the singleton. */
	private static $instance;

	/** @var string The user session ticket this singleton is created for. */
	private static $ticket;

	/**
	 * Return the singleton instance of this class.
	 *
	 * @param string $ticket
	 * @return WW_DbClasses_Session
	 * @throws BizException when a different $ticket is provided than a session is already started for.
	 */
	public static function getInstance( $ticket )
	{
		if( null === self::$instance ) {
			self::$instance = new self();
			if( self::$instance->init( $ticket ) ) {
				self::$ticket = $ticket;
			}
		} elseif( self::$ticket !== $ticket ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server',
				'Invalid params provided for '.__METHOD__.'(). '.
				'Please provide the same value for $ticket once a session is started.' );
		} 
		return self::$instance;
	}

	// Block callers of this class from creating instances through new, clone or unserialize operations.
	protected function __construct() {}
	private function __clone() {}
	private function __wakeup() {}
}