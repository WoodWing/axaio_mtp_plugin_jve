<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';

// Get form params
$inPub = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0; // Publication id
$inNewName = isset($_REQUEST['newName']) ? trim($_REQUEST['newName']) : ''; // Issue name for the copy destination
$inIssue = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0; // Issue id of the copy source
$issueName = isset($_REQUEST['issueName']) ? $_REQUEST['issueName'] : '';
$inNewDpsProdId = isset($_REQUEST['newDpsProdId']) ? trim($_REQUEST['newDpsProdId']) : ''; // New Dps Product ID for the copy destination
// See if the issue belongs to a Dps channel
$pubChannelId = DBIssue::getChannelId($inIssue);
$pubChannel = DBAdmPubChannel::getPubChannelObj($pubChannelId);
$isDpsIssue = $pubChannel->Type === 'dps' ? true : false;

// Hidden multicopy feature: get params to copy multiple issues at once
$startIdx = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 1; // postfix number to append to destination user name
$countIdx = isset($_REQUEST['count']) ? intval($_REQUEST['count']) : 1; // number of users to copy (based on source user)
if( $countIdx > 1 ) { set_time_limit(3600); }
$prefix = isset($_REQUEST['prefix']) ? $_REQUEST['prefix'] : ''; // name prefix to use for all copied items inside issue, such as editions, sections, etc

// Check publication rights
$ticket = checkSecure( 'publadmin' );
checkPublAdmin( $inPub );

// Duplicate issue and its entire workflow definition
$err = '';
if( $inIssue > 0 && isset($_REQUEST['newName']) ) {
	try {
		for( $currIdx = $startIdx; $currIdx < ($startIdx + $countIdx); $currIdx++ ) { 
		
			// Hidden multicopy feature: add postfixes to given name
			$prefixPlusIdx = $prefix;
			$inNewNamePlusIdx = $inNewName;
			$inNewDpsProdIdPlusIdx = $inNewDpsProdId;
			if( $countIdx > 1 ) {
				$prefixPlusIdx = $prefixPlusIdx.sprintf('%03d',$currIdx).' ';
				$inNewNamePlusIdx = $prefixPlusIdx.$inNewNamePlusIdx; // optionally add postfix number
				$inNewDpsProdIdPlusIdx = $prefixPlusIdx.$inNewDpsProdIdPlusIdx; // Dps Product ID must be unique. 
			}

			$issueObj = new AdmIssue();
			$issueObj->Name = $inNewNamePlusIdx;
			if ( $isDpsIssue ) {
				$issueObj->ExtraMetaData[] = new AdmExtraMetaData( "C_DPS_PRODUCTID", array($inNewDpsProdIdPlusIdx) );
			}
			require_once BASEDIR.'/server/services/adm/AdmCopyIssuesService.class.php';
			$service = new AdmCopyIssuesService();
			$request = new AdmCopyIssuesRequest();
			$request->Ticket 	= $ticket;
			$request->RequestModes = array();
			$request->IssueId 	= $inIssue;
			$request->Issues 	= array($issueObj);
			$request->NamePrefix= $prefixPlusIdx;	// Temp hack
			$response = $service->execute($request);

			$issueObj = $response->Issues[0];
			$copyIssueId = $issueObj->Id;

			// For last iteration, do redirection
			if( $currIdx == ($startIdx + $countIdx - 1)) {
				if( $countIdx > 1 ) { // multi-copy; go to owner / publication
					header("Location: hppublications.php?id=$inPub");
					exit();
				} else { // single copy; go to maintenance page of copied issue
					header("Location: hppublissues.php?id=$copyIssueId");
					exit();
				}
			}
		}
	} catch( BizException $e ) {
		$err .= '<br/>'.$e->getMessage().'<br/>'.$e->getDetail();
	}
}

// Build hidden html form variables
$vars  = inputvar( 'newName', $inNewName, null );
$vars .= inputvar( 'publ', $inPub, 'hidden' );
$vars .= inputvar( 'issue', $inIssue, 'hidden' );
$vars .= inputvar( 'issueName', $issueName, 'hidden' );
$varError = '<font color="#ff0000">'.$err.'</font>';
$resDpsId = '';
$varDps = '';
if ( $isDpsIssue ) {
	$resDpsId = '<!--RES:DPS_PRODUCTID-->';
	$varDps = inputvar( 'newDpsProdId', $inNewDpsProdId, null );
}
// Build html document
$tpl = HtmlDocument::loadTemplate( 'duplicate_issue.htm' );
$tpl = str_replace( '<!--NAME-->',$issueName, $tpl );
$tpl = str_replace( '<!--VARS-->', $vars, $tpl );
$tpl = str_replace( '<!--RESDPS-->', $resDpsId, $tpl );
$tpl = str_replace( '<!--VARDPS-->', $varDps, $tpl );
$tpl = str_replace( '<!--VARDPS-->', $varDps, $tpl );
$tpl = str_replace( '<!--ERROR-->', $varError, $tpl );
print HtmlDocument::buildDocument( $tpl );
