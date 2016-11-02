<?php
/**
 * @package    MaintenanceMode
 * @subpackage ServerPlugins
 * @since      v10.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the LogOn workflow web service.
 * Called when an end-user does logon to Enterprise (typically using SC or CS).
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class MaintenanceMode_WflLogOn extends WflLogOn_EnterpriseConnector {
	const PLUGIN_UNIQUE_NAME = 'MaintenanceMode';
	/** @var boolean The disable log in is activated. */
	private $disableLogIn = false;
	/** @var string Message to show user during log in. */
	private $disableLogInMessage = '';
	/** @var string Date/Time (ISO format) from which the log in is disabled. */
	private $disableFromDateTimeISO = '';

	final public function getPrio() { return self::PRIO_DEFAULT; }
	final public function getRunMode() { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflLogOnRequest &$req )
	{
	}

	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp )
	{
		if ( $this->readConfiguration() ) {
			$ticket = BizSession::getTicket();
			if( $this->isNonSystemUser( $ticket ) ) {
				if( $this->logInIsDisabled() ) {
					require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
					DBBase::deleteRows( 'tickets', '`ticketid` = ? ', array( $ticket ) );
					throw new BizException( null, 'Client', '', $this->disableLogInMessage );
				}
			}
		}
	}

	final public function runOverruled( WflLogOnRequest $req )
	{
	}

	private function readConfiguration()
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

	private function isNonSystemUser( $ticket )
	{
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';

		if( BizSession::isAppInTicket( null, 'Web' ) && DBUser::isAdminUser( BizSession::getShortUserName() ) ) {
			return false;
		}
		if ( BizInDesignServerJobs::calledByIDSAutomation( $ticket ) ) {
			return false;
		}
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		if ( BizSession::getRunMode() == BizSession::RUNMODE_BACKGROUND  ) {
			return false;
		}
		if( BizSession::isAppInTicket( null, 'mover-' ) ) { // Smart Mover
			return false;
		}

		return true;
	}

	private function logInIsDisabled(  )
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
