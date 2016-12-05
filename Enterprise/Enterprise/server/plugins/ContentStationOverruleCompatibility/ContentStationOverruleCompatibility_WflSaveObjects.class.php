<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflSaveObjects_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflSaveObjects extends WflSaveObjects_EnterpriseConnector
{
	private $overruleIssues;
	
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * Converts Content Station overruled brands to Server.
	 *
	 * @param WflSaveObjectsRequest $req
	 */
	final public function runBefore (WflSaveObjectsRequest &$req)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		$this->overruleIssues =  OverruleCompatibility::convertObjectsBefore( $req->Ticket, $req->Objects );
	}

	/**
	 * Converts server overrule issues to Content Station.
	 *
	 * @param WflSaveObjectsRequest $req
	 * @param WflSaveObjectsResponse $resp
	 */
	final public function runAfter (WflSaveObjectsRequest $req, WflSaveObjectsResponse &$resp)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		OverruleCompatibility::convertObjectsAfter( $req->Ticket, $resp->Objects, $this->overruleIssues );
	}

	/**
	 * @param WflSaveObjectsRequest $req
	 */
	final public function runOverruled (WflSaveObjectsRequest $req) {}
}
