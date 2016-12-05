<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflQueryObjects_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflQueryObjects extends WflQueryObjects_EnterpriseConnector
{
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * When called by Content Station check if we need to convert PubId query params and require
	 * IssueId in return columns (for convert in after)
	 *
	 * @param WflQueryObjectsRequest $req
	 */
	final public function runBefore (WflQueryObjectsRequest &$req) 
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		if( OverruleCompatibility::isContentStation( $req->Ticket ) || BizSession::isAppInTicket( null, 'mover-' ) ) { // allow SM to run CS user queries (BZ#22065)
			// First check if we do have any overrule issues in the system. If not we don't
			// have to require IssueId as extra minimal prop (which makes query more complicated/slower)
			require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
			$overruleIssues = DBIssue::listAllOverruleIssuesWithPub();
			if( !empty($overruleIssues) ) {
				// if query contains a overrule publication, fix it:
				if (isset($req->Params)) {
				foreach( $req->Params as $param ) {
					if( $param->Property == 'PublicationId' && OverruleCompatibility::isOverrulePub($param->Value) ) {
						$overruleIssueId = OverruleCompatibility::getIssue($param->Value);
						$param->Value =OverruleCompatibility::getPublication($param->Value);
						
						$req->Params[] = new QueryParam( 'IssueId', '=', $overruleIssueId );
						break;
					}
				}
				}
				// Add IssueId as minimal column:
				$req->MinimalProps[] = 'IssueId';
			}
		}
	}

	/**
	 * Converts server overrule issues to Content Station.
	 *
	 * @param WflQueryObjectsRequest $req
	 * @param WflQueryObjectsResponse $resp
	 */
	final public function runAfter (WflQueryObjectsRequest $req, WflQueryObjectsResponse &$resp)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		OverruleCompatibility::convertQueryResults( $req->Ticket, $resp );
	}

	/**
	 * @param WflQueryObjectsRequest $req
	 */
	final public function runOverruled (WflQueryObjectsRequest $req) {}
}
