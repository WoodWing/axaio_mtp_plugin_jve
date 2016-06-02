<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflCopyObject_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflCopyObject extends WflCopyObject_EnterpriseConnector
{
	private $overruleIssues;
	
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * Converts Content Station overruled brands to Server.
	 *
	 * @param WflCopyObjectRequest $req
	 */
	final public function runBefore (WflCopyObjectRequest &$req)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		$this->overruleIssues = Array();
		OverruleCompatibility::convertObjectBefore( $req->Ticket, $req, $this->overruleIssues );

	}

	/**
	 * Converts server overrule issues to Content Station.
	 *
	 * @param WflCopyObjectRequest $req
	 * @param WflCopyObjectResponse $resp
	 */
	final public function runAfter (WflCopyObjectRequest $req, WflCopyObjectResponse &$resp)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		OverruleCompatibility::convertObjectAfter( $req->Ticket, $resp, $this->overruleIssues );
	}

	/**
	 * @param WflCopyObjectRequest $req
	 */
	final public function runOverruled (WflCopyObjectRequest $req) // Not called because we're just doing run before and after
	{
		$req = $req; // keep analyzer happy
	}
}
