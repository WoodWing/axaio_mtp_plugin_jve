<?php

// This test tool creates some statuses for a given publication (or overrule issue).
// The publication id must be passed through URL, by adding param like this: multistatuses.php?pubId=123
// Status names and colors can be configured below. Note that they are listed in opposite order!
$statusNames = array( 'archive' => '#AAAAAA', 'ready' => '#22FF22', 'draft' => '#FF9900', 'plan' => '#FF0000' );

// NOTE: You can also delete all statuses for a publication at once: multistatuses.php?pubId=123&delete
// This needs to be used with care! Nevertheless, it will error/skip statuses that are in use by objects.


require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';

// check admin access
checkSecure('admin');

// check URL params
$pubId = intval($_REQUEST['pubId']);
$issueId = isset($_REQUEST['issueId']) ? intval($_REQUEST['issueId']) : 0;
if( !$pubId ) {
	die( 'Please specify pubId param at URL!' );
}

// delete or create statuses...
$objTypes = getObjectTypeMap();
if( isset($_REQUEST['delete']) ) { // delete mode
	foreach( $objTypes as $objType => $objTypeDisplay ) {
		$statusObjs = BizAdmStatus::getStatuses( $pubId, $issueId, $objType );
		foreach( $statusObjs as $statusObj ) {
			try {
				print 'Deleting status "'.$statusObj->Name.'" ... ';
				BizCascadePub::deleteStatus( $statusObj->Id ); // only for deletions, status id is provided
				print '<font color="green">OK!</font><br/>';
			} catch( BizException $e ) {
				print '<font color="red">ERROR: '. $e->getMessage() . '</font><br/>';
			}
		}
	}
} else { // insert mode
	foreach( $objTypes as $objType => $objTypeDisplay ) {
		$nextId = 0;
		$order = count($statusNames) * 10; // opposite order, so determine last one (to start with)
		foreach( $statusNames as $statusName => $color ) {
			try {
				// determine prefix to apply to new status name, by taking some initials of display name
				$parts = explode( ' ', $objTypeDisplay );
				$len = ($parts[0] == 'Audio' || $parts[0] == 'Advert' || $parts[0] == 'Library' ) ? 2 : 1; // take 1 or 2 prefix chars
				$postfix = '';
				foreach( $parts as $part ) {
					$postfix .= substr( $part, 0, $len );
					$len = 1;
				}
				$prefix = ($issueId > 0) ? '['.$issueId.'] ' : '';

				// build new status object in memory
				$statusObj = newStatusObj( $objType, $prefix.$statusName.' ('.$postfix.')', $color, $nextId, $order );

				// create the status object at DB
				print 'Creating status "'.$statusObj->Name.'" ... ';
				$statusObj = BizAdmStatus::createStatuses( $pubId, $issueId, array($statusObj) );
				print '<font color="green">OK!</font><br/>';

				// prepare for next status object
				$nextId = $statusObj->Id;
				$order -= 10; // take previous order
			} catch( BizException $e ) {
				print '<font color="red">ERROR: '. $e->getMessage() . '</font><br/>';
			}
		}
	}
}
print '<br/><br/>Done!<br/>';

/**
 * Helper function that composes a status object in memory.
 *
 * @param string $objType
 * @param string $statusName
 * @param string $color
 * @param integer $nextId
 * @param integer $order
 * @return AdmStatus
 */
function newStatusObj( $objType, $statusName, $color, $nextId, $order )
{
	require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php'; // AdmStatus, AdmIdName
	$status = new AdmStatus();
	$status->Id               = 0;
	$status->Name             = $statusName;
	$status->SortOrder        = $order;
	$status->Type             = $objType;
	$status->Produce          = false;
	$status->Color            = $color;
	$status->DeadlineRelative = '';
	$status->NextStatus       = new AdmIdName( $nextId );
	$status->CreatePermanentVersion     = false;
	$status->RemoveIntermediateVersions = false;
	$status->AutomaticallySendToNext    = false;
	$status->ReadyForPublishing         = false;
	$status->SkipIdsa                   = false;
	$status->Phase            = 'Production';
	return $status;
}
