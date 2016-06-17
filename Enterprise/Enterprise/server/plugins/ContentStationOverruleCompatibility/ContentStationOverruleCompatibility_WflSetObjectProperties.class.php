<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflSetObjectProperties_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflSetObjectProperties extends WflSetObjectProperties_EnterpriseConnector
{
	private $overruleIssues;
	
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * Converts Content Station overruled brands to Server.
	 *
	 * @param WflSetObjectPropertiesRequest $req
	 */
	final public function runBefore (WflSetObjectPropertiesRequest &$req)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		$this->overruleIssues = array();
		OverruleCompatibility::convertObjectBefore( $req->Ticket, $req, $this->overruleIssues );

	}

	/**
	 * Converts server overrule issues to Content Station.
	 *
	 * @param WflSetObjectPropertiesRequest $req
	 * @param WflSetObjectPropertiesResponse $resp
	 */
	final public function runAfter (WflSetObjectPropertiesRequest $req, WflSetObjectPropertiesResponse &$resp)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		OverruleCompatibility::convertObjectAfter( $req->Ticket, $resp, $this->overruleIssues );
	}

	/**
	 * @param WflSetObjectPropertiesRequest $req
	 */
	final public function runOverruled (WflSetObjectPropertiesRequest $req) // Not called because we're just doing run before and after
	{
		$req = $req; // keep analyzer happy
	}
}
