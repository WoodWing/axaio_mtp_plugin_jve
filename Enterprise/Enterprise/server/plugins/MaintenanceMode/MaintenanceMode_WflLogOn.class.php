<?php
/**
 * @since      v10.0.3
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the LogOn workflow web service and is called when any user is logged in.
 * When in maintenance mode, this module rejects normal users trying to logon but allows auto/system user to logon.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class MaintenanceMode_WflLogOn extends WflLogOn_EnterpriseConnector
{
	const PLUGIN_UNIQUE_NAME = 'MaintenanceMode';
	/** @var boolean The disable log in is activated. */
	private $disableLogIn = false;
	/** @var string Message to show user during log in. */
	private $disableLogInMessage = '';
	/** @var string Date/Time (ISO format) from which the log in is disabled. */
	private $disableFromDateTimeISO = '';

	final public function getPrio() { return self::PRIO_DEFAULT; }
	final public function getRunMode() { return self::RUNMODE_AFTER; }

	final public function runBefore( WflLogOnRequest &$req )
	{
	}

	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp )
	{
		if ( $this->getStoredSettings() ) {
			if( !$this->isSystemUser( $resp->Ticket ) ) {
				if( $this->logInIsDisabled() ) {
					require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
					DBBase::deleteRows( 'tickets', '`ticketid` = ? ', array( $resp->Ticket ) );
					throw new BizException( null, 'Client', '', $this->disableLogInMessage );
				}
			}
		}
	}

	final public function runOverruled( WflLogOnRequest $req )
	{
	}

	/**
	 * Gets the settings stored in the database.
	 *
	 * @return bool True if settings are found, else false.
	 */
	private function getStoredSettings()
	{
		$result = false;
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$serializedSettings = DBConfig::getValue( self::PLUGIN_UNIQUE_NAME.'_settings' );
		if ( $serializedSettings ) {
			$settings = unserialize( $serializedSettings );
			$this->disableLogIn = $settings['disableLogIn'];
			$this->disableFromDateTimeISO = $settings['disableFromDateTimeISO'];
			$this->disableLogInMessage = $settings['disableLogInMessage'];
			$result = true;
		}
		return $result;
	}

	/**
	 * Checks if the ticket belongs to a system user.
	 *
	 * InDesign Server, Smart Mover and back ground processes are regarded as system users.
	 * Enterprise Server jobs are marked as background processes.
	 * MadeToPrint InDesign Server jobs are run under the 'indesign server' application name.
	 * Furthermore, an admin user that logs in is also regarded as special.
	 *
	 * @param string $ticket
	 * @return bool True if the ticket belongs to a user that is marked as system user, else false.
	 */
	private function isSystemUser( $ticket )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';

		if( DBUser::isAdminUser( BizSession::getShortUserName() ) ) {
			return true;
		}
		if ( BizSession::getRunMode() == BizSession::RUNMODE_BACKGROUND  ) {
			return true;
		}
		if( BizSession::isAppInTicket( null, 'mover-' ) ||
			BizSession::isAppInTicket( null, 'indesign server'  ) ||
			BizSession::isAppInTicket( null, 'elvis' )) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the log in must be disabled.
	 *
	 * The log in is disabled when the 'disable log in' setting is set and the current time has passed the 'disable log
	 * in' time.
	 *
	 * @return bool
	 */
	private function logInIsDisabled()
	{
		$result = false;
		if ( $this->disableLogIn) {
			$disabledFromTimeStamp  = strtotime( $this->disableFromDateTimeISO );
			if( time() >= $disabledFromTimeStamp ) {
				$result = true;
			}
		}

		return $result;
	}

}
