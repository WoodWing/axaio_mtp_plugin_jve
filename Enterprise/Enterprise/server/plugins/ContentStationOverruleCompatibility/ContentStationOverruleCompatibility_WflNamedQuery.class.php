<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflNamedQuery_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflNamedQuery extends WflNamedQuery_EnterpriseConnector
{
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_AFTER; }

	/**
	 * See convertQueryResults for comments.
	 *
	 * @param WflNamedQueryRequest $req
	 * @param WflNamedQueryResponse $resp
	 */
	final public function runAfter (WflNamedQueryRequest $req, WflNamedQueryResponse &$resp)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		OverruleCompatibility::convertQueryResults( $req->Ticket, $resp );
	}

	/**
	 * @param WflNamedQueryRequest $req
	 */
	final public function runBefore( WflNamedQueryRequest &$req ) // Not called because we're just doing run after
	{
		$req = $req; // keep analyzer happy
	}

	/**
	 * @param WflNamedQueryRequest $req
	 */
	final public function runOverruled (WflNamedQueryRequest $req) // Not called because we're just doing run after
	{
		$req = $req; // keep analyzer happy
	}
}
