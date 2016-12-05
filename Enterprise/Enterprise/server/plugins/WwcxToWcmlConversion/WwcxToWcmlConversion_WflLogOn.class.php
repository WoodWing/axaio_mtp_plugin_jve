<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class WwcxToWcmlConversion_WflLogon extends WflLogOn_EnterpriseConnector
{

	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflLogOnRequest &$req ) {}

	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp )
	{
		if ( isset($resp->ServerInfo->FeatureSet) ) {
			require_once BASEDIR . '/server/dbclasses/DBTicket.class.php';
			$app = DBTicket::DBappticket( $resp->Ticket );
			if( stristr($app, 'content station') || stristr($app, 'buildtest_wwcxtowcml') ) {

				// Add the server Feature "ConvertWWCXToWCML" when an active InDesign Server is found
				// that can handle WWCX-WCML conversion (which requires IDS CS5 or later) to let CS know
				// it can try to open WWCX articles for editing (while it can only handle WCML articles).
				require_once dirname(__FILE__).'/WwcxToWcmlUtils.class.php';
				$wwcxToWcmlUtils = new WwcxToWcmlUtils();
				if( $wwcxToWcmlUtils->hasActiveInDesignServerForWcmlConversion() ) {
					$resp->ServerInfo->FeatureSet[] = new Feature( 'ConvertWWCXToWCML' );
					LogHandler::Log('WwcxToWcmlConversion', 'INFO', 'Found active InDesign Server CS5 (or later). '.
						'Therefor auto-enabling the Feature named "ConvertWWCXToWCML" '.
						'(by returning it on-the-fly at the LogOnResponse) to let CS know.' );
				}
			}
		}
	}

	final public function runOverruled( WflLogOnRequest $req ) {}
}
