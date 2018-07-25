<?php
/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class MultiMtpLogon_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_BEFORE; }

	final public function runBefore (WflLogOnRequest &$req)
	{
		require_once BASEDIR.'/server/admin/global_inc.php';
		$dbdriver = DBDriverFactory::gen();

		$user=$req->User;
		if ($user == '_mtp_')
		{
			// check tickets for user mtp
			// if ticket exists for user mtp
			// add seq nr to mtp and retry
			// _mtp_1_ _mtp_2_ etc.

			for ($i=0; $i<10; $i++)
			{
				$user = "_mtp_".$i."_";
				$sql = "SELECT * FROM `smart_tickets` where `usr` = '$user'";
				$sth = $dbdriver->query($sql);
				$row = $dbdriver->fetch($sth);
				LogHandler::Log('mtp', 'DEBUG', print_r($row,1) );
				if (!is_array($row))
				{
					break;
				}
            }
            if (!is_array($row))
            {
            	// if no free user is available
            	// reuse the oldest one
            	// might have been crashed
            	$sql = "SELECT `usr` FROM `smart_tickets` where `usr` like '_mtp%' order by `expire` desc limit 1";
				$sth = $dbdriver->query($sql);
				$row = $dbdriver->fetch($sth);
				$user = $row['usr'];
            }
        }
        $req->User=$user;
		LogHandler::Log('UserName', 'DEBUG', print_r($user,1) );
		return $req;
	}

	final public function runAfter (WflLogOnRequest $req, WflLogOnResponse &$resp)
	{
	}

	final public function runOverruled (WflLogOnRequest $req) {

	}
}
