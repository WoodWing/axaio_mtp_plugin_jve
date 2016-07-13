<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('publadmin');

// determine incoming mode
$publ    = isset($_REQUEST['publ'])   ? intval($_REQUEST['publ'])  : 0; // Publication id. Zero not allowed.
$issue   = isset($_REQUEST['issue'])  ? intval($_REQUEST['issue']) : 0; // Issue id. Zero for all.
$grp     = isset($_REQUEST['grp'])    ? intval($_REQUEST['grp'])   : 0; // User group id. Zero for all, in special mode in which user should pick one first.

// check publication rights
checkPublAdmin($publ);

// print report
if( isset($_REQUEST['report']) && $_REQUEST['report'] ) {
	$txt = composeAuthorizationsReport( $publ, $issue );
	print HtmlDocument::buildDocument( $txt, false );
	exit;
}

$command = isset($_REQUEST['command']) ? $_REQUEST['command'] : 'view';
$authIds = isset($_REQUEST['authids']) ? safeCastCsvToDbIds( $_REQUEST['authids'] ) : array();

$profiles = getProfiles();
$objTypeMap = getObjectTypeMap();

// generate upper part (info or select fields)
$txt = HtmlDocument::loadTemplate( 'authorizations2.htm' );

// Show user group.
$dbh = DBDriverFactory::gen();
$dbg = $dbh->tablename('groups');
if( $command == 'view' ) {
	$sql = "select `id`, `name` from $dbg order by `name`";
	$sth = $dbh->query($sql);
	$grptxt = '<select name="grp" onChange="this.form.submit()">';
	while (($row = $dbh->fetch($sth))) {
		$selected = $row['id'] == $grp ? ' selected="selected"' : '';
		$grptxt .= '<option value="'.$row['id'].'"'.$selected.'>'.formvar($row['name']).'</option>';
	}
	$grptxt .= '</select>';
} else {
	$sql = "select `id`, `name` from $dbg where `id` = $grp";
	$sth = $dbh->query($sql);
	$row = $dbh->fetch($sth);
	$grptxt = formvar($row['name']).inputvar( 'grp', $grp, 'hidden' );
}
$txt = str_replace('<!--VAR:GROUP-->', $grptxt, $txt);

// Show brand and overrule issue.
require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
$pubName = DBPublication::getPublicationName( $publ );
$txt = str_replace( '<!--VAR:PUBL-->', formvar($pubName).inputvar( 'publ', $publ, 'hidden' ), $txt );
if( $issue > 0 ) {
	require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
	$issueName = DBIssue::getIssueName( $issue );
	$txt = str_replace( '<!--VAR:ISSUE-->', formvar($issueName).inputvar( 'issue', $issue, 'hidden' ), $txt );
} else {
	$txt = preg_replace('/<!--IF:STATE-->.*<!--ENDIF-->/is', '', $txt);
}

// Collect categories (of the brand/issue).
$sectiondomain = getCategoryIdNameByBrandIssue( $publ, $issue );

// Collect statuses (of the brand/issue).
$dbh = DBDriverFactory::gen();
$dbst = $dbh->tablename('states');
$sql = "SELECT `id`, `state`, `type`, `code` FROM $dbst ".
		"WHERE `publication` = $publ and `issue` = $issue ".
		"ORDER BY `type`, `code`, `id`";
$sth = $dbh->query($sql);
$statedomain = array();
$statusDomainTree = array();
while (($row = $dbh->fetch($sth))) {
	$statedomain[$row['id']] = $objTypeMap[$row['type']]." / ".$row['state'];
	$statusDomainTree[$row['type']][$row['id']] = $row['state'];
}

// Handle the Delete operation.
if( $authIds && $command == 'delete' ) {
	deleteAuthorizationsByIds( $authIds );
}

// Handle the Edit operation.
if( $authIds && $command == 'edit' ) {

	// Compose JSON data structure of categories for jsTree widget.
	$categoryTree = array();
	$categoryTreeRefs = array();
	foreach( $sectiondomain as $categoryId => $categoryName ) {
		$categoryItem = new stdClass();
		$categoryItem->id = 'cat_'.$categoryId;
		$categoryItem->text = formvar($categoryName);
		$categoryTree[] = $categoryItem;
		$categoryTreeRefs[$categoryId] = $categoryItem;
	}

	// Compose JSON data structure of statuses for jsTree widget.
	$statusTree = array();
	$statusTreeItemsRefs = array();
	$statusTreeGroupsRefs = array();
	foreach( $statusDomainTree as $statusType => $statusPerType ) {
		$statusGroup = new stdClass();
		$statusGroup->id = 'stt_'.$statusType;
		$statusGroup->text = formvar($objTypeMap[$statusType]);
		$statusGroup->children = array();
		foreach( $statusPerType as $statusId => $statusName ) {
			$statusItem = new stdClass();
			$statusItem->id = 'stt_'.$statusId;
			$statusItem->text = formvar($statusName);
			$statusGroup->children[] = $statusItem;
			$statusTreeItemsRefs[$statusId] = $statusItem;
		}
		$statusTree[] = $statusGroup;
		$statusTreeGroupsRefs[$statusType] = $statusGroup;
	}

	// Compose JSON data structure of profiles for jsTree widget.
	$profileTree = array();
	$profileTreeRefs = array();
	foreach( $profiles as $profileId => $profileName ) {
		$profileItem = new stdClass();
		$profileItem->id = 'prf_'.$profileId;
		$profileItem->text = formvar($profileName);
		$profileTree[] = $profileItem;
		$profileTreeRefs[$profileId] = $profileItem;
	}

	// Enable the statuses in the jsTree for the authorization the user wants to edit.
	$rows = getAuthorizationRowsByIds( $authIds );
	foreach( $rows as $row ) {
		if( $row['section'] ) {
			$categoryItem = $categoryTreeRefs[ $row['section'] ];
			$categoryItem->state = new stdClass();
			$categoryItem->state->selected = true;
		} else { // select all
			foreach( $categoryTree as $categoryItem ) {
				$categoryItem->state = new stdClass();
				$categoryItem->state->selected = true;
			}
		}
		if( $row['state'] ) {
			$statusItem = $statusTreeItemsRefs[ $row['state'] ];
			$statusItem->state = new stdClass();
			$statusItem->state->selected = true;
		} else {
			foreach( $statusTree as $statusGroup ) {
				$statusGroup->state = new stdClass();
				$statusGroup->state->selected = true;
			}
		}
		if( $row['profile'] ) {
			$profileItem = $profileTreeRefs[ $row['profile'] ];
			$profileItem->state = new stdClass();
			$profileItem->state->selected = true;
		}
	}
	$txt = str_replace( '[/*VAR:CATEGORIES*/]', json_encode( $categoryTree ), $txt );
	$txt = str_replace( '[/*VAR:STATUSES*/]', json_encode( $statusTree ), $txt );
	$txt = str_replace( '[/*VAR:PROFILES*/]', json_encode( $profileTree ), $txt );
}

// Handle the Save operation.
if( $authIds && $command == 'save' ) {

	// Retrieve user selected Categories/Statuses/Profiles from the jsTree widgets.
	$selectedCategoryIds = isset( $_REQUEST['selcategoryids'] ) ? safeCastCsvToDbIds( $_REQUEST['selcategoryids'], 'cat_' ) : array();
	$selectedStatusIds = isset( $_REQUEST['selstatusids'] ) ? safeCastCsvToDbIds( $_REQUEST['selstatusids'], 'stt_' ) : array();
	$selectedProfileIds = isset( $_REQUEST['selprofileids'] ) ? safeCastCsvToDbIds( $_REQUEST['selprofileids'], 'prf_' ) : array();

	// When all Categories or Statuses are selected, that is indicated in DB as zero (0).
	if( count( $sectiondomain ) == count( $selectedCategoryIds ) ) {
		$selectedCategoryIds = array( 0 );
	}
	if( count( $statedomain ) == count( $selectedStatusIds ) ) {
		$selectedStatusIds = array( 0 );
	}

	// Compose cartesian product of the user selected ids.
	$selectedAuths = array();
	foreach( $selectedCategoryIds as $categoryId ) {
		foreach( $selectedStatusIds as $statusId ) {
			foreach( $selectedProfileIds as $profileId ) {
				$selectedAuths[ $categoryId ][ $statusId ][ $profileId ] = true;
			}
		}
	}

	// Delete all authorizations that have changed in the user's selection.
	$deleteAuthIds = array();
	$rows = getAuthorizationRowsByIds( $authIds );
	foreach( $rows as $row ) {
		if( !isset( $selectedAuths[ $row['section'] ][ $row['state'] ][ $row['profile'] ] ) ) {
			$deleteAuthIds[] = $row['id'];
		}
	}
	if( $deleteAuthIds ) {
		deleteAuthorizationsByIds( $deleteAuthIds );
	}

	// Create all user configured authorizations in DB that do not exist yet.
	$dbAuths =array();
	foreach( $rows as $row ) {
		$dbAuths[ $row['section'] ][ $row['state'] ][ $row['profile'] ] = true;
	}
	foreach( $selectedCategoryIds as $categoryId ) {
		foreach( $selectedStatusIds as $statusId ) {
			foreach( $selectedProfileIds as $profileId ) {
				if( !isset( $dbAuths[ $categoryId ][ $statusId ][ $profileId ] ) ) {
					insertAuthorizationRow( $publ, $issue, $grp, $categoryId, $statusId, $profileId );
				}
			}
		}
	}
}

// Retrieve all authorizations from DB and compose.
$rows = getAuthorizationRowsByBrandIssueUserGroup( $publ, $issue, $grp );

// Combine authorizations DB records that can be shown in one HTML row.
$authMap = array();
foreach( $rows as $row ) {
	$authMap[] = array(
		'categories' => $row['section'],
		'statuses' => $row['state'],
		'profiles' => $row['profile'],
		'ids' => $row['id']
	);
}
$authMap = combineAuthorizations( $authMap );

// Compose a HTML table to display the (combined) authorizations.
$detailtxt = composeAuthorizationsHtmlTable( $authMap, $sectiondomain, $statedomain, $profiles, $command, $authIds );
$txt = str_replace( '<!--ROWS-->', $detailtxt, $txt );

// If not in edit mode, show the Add Authorization button.
$button = '';
if( $command != 'edit' ) {
	$button = '<input type="button" value="<!--RES:ACT_ADD_AUTHORIZATION-->" onclick="javascript:addAuth();" />';
}
$txt = str_replace( '<!--VAR:ADD_AUTH_BTN-->', $button, $txt );

// Show the Back button to let user navigate back to brand/issue page.
if( $issue > 0 ) {
	$back = "hppublissues.php?id=$issue";
} else {
	$back = "hppublications.php?id=$publ";
}
$txt = str_replace( '<!--BACK-->', $back, $txt );

// Compose the HTML admin page, including header (top) and menu (left).
print HtmlDocument::buildDocument( $txt );

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * Retrieves all configured profiles from DB.
 *
 * @return array Profile names indexed by profile record ids.
 * @throws BizException
 */
function getProfiles()
{
	$dbh = DBDriverFactory::gen();
	$dbp = $dbh->tablename('profiles');
	
	$sql = 'SELECT `id`, `profile` FROM '.$dbp.' ORDER BY `code`, `profile`';
	$sth = $dbh->query($sql);
	$arr = array();
	while (($row = $dbh->fetch($sth)) ) {
		$arr[$row['id']] = $row['profile'];
	}
	return $arr;
}

/**
 * Retrieves all configured categories from DB for a given brand (and overrule issue).
 *
 * @param int $brandId
 * @param int $issueId Optional, overrule issue id.
 * @return array List of category names, indexed by record id, sorted by name.
 */
function getCategoryIdNameByBrandIssue( $brandId, $issueId = 0 )
{
	$where = '`publication` = ? and `issue` = ?';
	$params = array( $brandId, $issueId );
	$orderBy = array( 'section' => true );
	$rows = DBBase::listRows( 'publsections', 'id', 'section', $where, null, $params, $orderBy );
	return array_map( function( $row ) { return $row['section']; }, $rows );
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * Retrieves configured authorizations from DB, given their record ids.
 *
 * @param integer[] $authIds Authorization record ids.
 * @return array smart_authorization table records indexed by record ids.
 * @throws BizException
 */
function getAuthorizationRowsByIds( $authIds )
{
	$dbh = DBDriverFactory::gen();
	$dba = $dbh->tablename('authorizations');
	$sql =
		"SELECT a.`id`, a.`section`, a.`state`, a.`profile` ".
		"FROM $dba a ".
		'WHERE a.`id` IN( '.implode( ',', $authIds ).' ) ';
	$sth = $dbh->query( $sql );

	$rows = array();
	while( ( $row = $dbh->fetch( $sth ) ) ) {
		$rows[ $row['id'] ] = $row;
	}
	return $rows;
}

/**
 * Retrieves configured authorization records from DB.
 *
 * @param integer $brandId
 * @param integer$issueId
 * @param integer $userGroupId
 * @return array Authorization records sorted by Category, Object Type and Status (code) and indexed by record id.
 * @throws BizException
 */
function getAuthorizationRowsByBrandIssueUserGroup( $brandId, $issueId, $userGroupId )
{
	$dbh = DBDriverFactory::gen();
	$dbs = $dbh->tablename('publsections');
	$dbst = $dbh->tablename('states');
	$dba = $dbh->tablename('authorizations');

	$sql = "SELECT a.`id`, a.`section`, a.`state`, a.`profile` ".
		"FROM $dba a ".
		"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
		"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
		"WHERE a.`publication` = ? and a.`issue` = ? and a.`grpid` = ? ".
		"ORDER BY s.`section`, st.`type`, st.`code`";
	$params = array( $brandId, $issueId, $userGroupId );
	$sth = $dbh->query( $sql, $params );

	$rows = array();
	while( ( $row = $dbh->fetch( $sth ) ) ) {
		$rows[ $row['id'] ] = $row;
	}
	return $rows;
}

/**
 * Deletes configured authorizations from DB, given their record ids.
 *
 * @param integer[] $authIds Authorization record ids.
 * @throws BizException
 */
function deleteAuthorizationsByIds( $authIds )
{
	$dbh = DBDriverFactory::gen();
	$dba = $dbh->tablename('authorizations');
	$sql = "DELETE FROM $dba WHERE `id` IN ( ".implode( ',', $authIds )." )";
	$dbh->query( $sql );
}

/**
 * Creates a new authorization configuration record in DB.
 *
 * @param integer $brandId
 * @param integer $issueId
 * @param integer $userGroupId
 * @param integer $categoryId
 * @param integer $statusId
 * @param integer $profileId
 * @return bool|integer Record id, or false when creation failed.
 * @throws BizException
 */
function insertAuthorizationRow( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId )
{
	$dbh = DBDriverFactory::gen();
	$dba = $dbh->tablename('authorizations');
	$sql = "INSERT INTO $dba (`publication`, `issue`, `grpid`, `section`, `state`, `profile`) ".
		"VALUES ( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId )";
	$sql = $dbh->autoincrement( $sql );
	$sth = $dbh->query( $sql );
	return (bool)$dbh->newid( $dba, true );
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * Converts a Comma Separated Value in a list of DB record ids (positive integers).
 *
 * Bad values are filtered out. Blocks SQL injections.
 *
 * @param string $csv
 * @param string $prefix Optionally, prefix used for each DB id.
 * @return integer[]
 */
function safeCastCsvToDbIds( $csv, $prefix = '' )
{
	$ids = explode(',', $csv );
	if( $prefix ) {
		$ids = array_map( function( $id ) use ( $prefix ) {
			$id = substr( $id, strlen($prefix) );
			return $id ? $id : 0; // false becomes zero
		} , $ids );
	}
	$ids = array_map( 'intval', $ids ); // cast all to integers
	$ids = array_unique( $ids ); // remove duplicates
	return array_filter( $ids, function( $id ) { return $id > 0; } ); // keep positives only
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * Combines authorization records that share the same categories, statuses and/or profiles.
 *
 * When it can find two records with the same category and status, it combines both records
 * by merging the profiles (which differ). It does the same for other combinations it can find;
 * When it finds two records with the same status and profile, it combines the categories.
 * When it finds two records with the same category and profile, it combines the statuses.
 *
 * Combinations are made by merging ids in a comma separated value in a sorted manner.
 * Having that in place, it tries to combine the previously combinations as well.
 * So when the combined list of categories and the combined list of statuses is the same between
 * two combined records, it combines all their profiles. This repeats until no more combinations
 * can be made.
 *
 * The result is a set of combined records for which you can say that all combinations
 * between the individual categories, statuses and profiles are configured. For all combine
 * operations, the authorization ids are merged as well, which identifies the records involved.
 *
 * Note that it keeps on sorting in a multi-dimensional matter between all the combination
 * operations. This is to put similar records after each other. When one record is the same
 * as the next record, those two get merged.
 *
 * Note that this algorithm is kept simple and does not strive for the most optimal combinations.
 *
 * @param array $authMap Authorization records to be combined.
 * @return array Combined authorization records.
 */
function combineAuthorizations( $authMap )
{
	do {
		$orgCount = count($authMap);

		list( $category, $status, $profile ) = prepareMultiSort( $authMap );
		array_multisort( $category, SORT_ASC, SORT_STRING, $status, SORT_ASC, SORT_STRING, $authMap );
		$authMap = combineRows( $authMap, 'categories', 'statuses' );

		list( $category, $status, $profile ) = prepareMultiSort( $authMap );
		array_multisort( $category, SORT_ASC, SORT_STRING, $status, SORT_ASC, SORT_STRING, $authMap );
		$authMap = combineRows( $authMap, 'statuses', 'profiles' );

		list( $category, $status, $profile ) = prepareMultiSort( $authMap );
		array_multisort( $category, SORT_ASC, SORT_STRING, $status, SORT_ASC, SORT_STRING, $authMap );
		$authMap = combineRows( $authMap, 'categories', 'profiles' );

	} while( $orgCount != count($authMap) );
	return $authMap;
}

/**
 * Composes three arrays for a given authorization record set, in preparation of array_multisort().
 *
 * It returns one array for categories, one for statuses and one for profiles all sharing the
 * same keys as the given authorization record set. Those arrays can be passed into array_multisort()
 * along with the authorizations to perform a multi-dimensional sort.
 *
 * @param array $authMap Combined authorization records.
 * @return array List of three arrays.
 */
function prepareMultiSort( $authMap )
{
	$category = array();
	$status = array();
	$profile = array();
	foreach( $authMap as $key => $auth ) {
		$category[ $key ] = $auth['categories'];
		$status[ $key ] = $auth['statuses'];
		$profile[ $key ] = $auth['profiles'];
	}
	return array( $category, $status, $profile );
}

/**
 * Combines two similar authorizations, as explained for the combineAuthorizations() function.
 *
 * @param array $authMap Authorization records that may need to be combined.
 * @param string $col1 Property to compare with $col2 for each item in $authMap.
 * @param string $col2 Property to compare with $col1 for each item in $authMap.
 * @return array Combined authorization records.
 */
function combineRows( $authMap, $col1, $col2 )
{
	do {
		$prevKey = null;
		$orgCount = count( $authMap );
		foreach( $authMap as $key => $auth ) {
			if( !is_null( $prevKey ) &&
				$authMap[ $prevKey ][ $col1 ] == $auth[ $col1 ] &&
				$authMap[ $prevKey ][ $col2 ] == $auth[ $col2 ]
			) {
				foreach( array_keys( $authMap[ $key ] ) as $column ) {
					if( $column != $col1 && $column != $col2 ) {
						$authMap[ $prevKey ][ $column ] = combineFields( $authMap[ $prevKey ][ $column ], $auth[ $column ] );
					}
				}
				unset( $authMap[ $key ] );
				$key = null;
			}
			if( !is_null( $key ) ) {
				$prevKey = $key;
			}
		}
	} while( $orgCount != count($authMap) );
	return $authMap;
}

/**
 * Combines two CSV lists into one CSV list in a sorted manner.
 *
 * @param string $lhs CSV list to compare (Left Hand Side)
 * @param string $rhs CSV list to compare (Right Hand Side)
 * @return string Combined CSV list.
 */
function combineFields( $lhs, $rhs )
{
	$arr = array_merge( explode( ',', $lhs ), explode( ',', $rhs ) );
	sort( $arr );
	return implode( ',', $arr );
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * Composes a HTML table which shows all given authorizations.
 *
 * In edit mode, there is one record being edit at the same time, while the others
 * are readonly. The composed HTML table visualizes the record being edit.
 *
 * When the page is in readonly mode, all buttons are shown, except Save and Cancel.
 * When the page is in edit mode, all buttons are hidden, except Save and Cancel.
 *
 * @param array $authMap Combined authorization records.
 * @param array $sectiondomain Id-Name lookup map of categories.
 * @param array $statedomain Id-Name lookup  map of statuses.
 * @param array $profiles Id-Name lookup map of profiles.
 * @param string $command The operation requested by user.
 * @param array $authIds The authorization ids involved with the operation.
 * @return string HTML fragment that shows the authorizations.
 */
function composeAuthorizationsHtmlTable( $authMap, $sectiondomain, $statedomain, $profiles, $command, $authIds )
{
	$authIdsCsv = implode( ',', $authIds );
	$html = '';
	foreach( $authMap as $key => $auth ) {
		$html .= '<tr class="separator"><td colspan="4"/></tr>';

		if( $command == 'edit' && $authIdsCsv && $authIdsCsv == $auth['ids'] ) {
			$html .= composeAuthorizationsHtmlRowEditable( $auth, $sectiondomain, $statedomain, $profiles, $command, $authIds );
		} else {
			$html .= composeAuthorizationsHtmlRowReadonly( $auth, $sectiondomain, $statedomain, $profiles, $command, $authIds );
		}
	}
	return $html;
}

/**
 * Composes an HTML row representing a given combined authorization record as readonly.
 *
 * @param array $auth Combined authorization record.
 * @param array $sectiondomain Id-Name lookup map of categories.
 * @param array $statedomain Id-Name lookup  map of statuses.
 * @param array $profiles Id-Name lookup map of profiles.
 * @param string $command The operation requested by user.
 * @param array $authIds The authorization ids involved with the operation.
 * @return string HTML table row.
 */
function composeAuthorizationsHtmlRowReadonly( $auth, $sectiondomain, $statedomain, $profiles, $command, $authIds )
{
	$all = BizResources::localize("LIS_ALL");
	$html = '<tr class="readonly">';

	$html .= '<td><div class="chkbox"><ul>';
	foreach( explode( ',', $auth['categories'] ) as $categoryId ) {
		$html .= '<li>';
		$html .=    $categoryId ? formvar( $sectiondomain[ $categoryId ] ) : formvar( $all );
		$html .= '</li>';
	}
	$html .= '</ul></div></td>';

	$html .= '<td><div class="chkbox"><ul>';
	foreach( explode( ',', $auth['statuses'] ) as $statusId ) {
		$html .= '<li>';
		$html .=    $statusId ? formvar( $statedomain[ $statusId ] ) : formvar( $all );
		$html .= '</li>';
	}
	$html .= '</ul></div></td>';

	$html .= '<td><div class="chkbox"><ul>';
	foreach( explode( ',', $auth['profiles'] ) as $profileId ) {
		$html .= '<li>';
		$html .=    formvar( $profiles[ $profileId ] );
		$html .= '</li>';
	}
	$html .= '</ul></div></td>';

	// Show Edit, Copy and Delete buttons (only when not in Edit mode).
	if( $command != 'edit' ) {
		$html .=
			'<td>'.
				'<div class="btnbar"">'.
					'<a class="ui-button ui-button-text-icons" href="javascript:editAuth(\''.$auth['ids'].'\');">'.
						'<span class="ui-button-icon ui-icon ui-icon-pencil"></span>'.
						'<span class="ui-button-text">'.BizResources::localize( 'ACT_EDIT' ).'</span>'.
					'</a>'.
					'<a class="ui-button ui-button-text-icons" href="javascript:copyAuth(\''.$auth['ids'].'\');">'.
						'<span class="ui-button-icon ui-icon ui-icon-copy"></span>'.
						'<span class="ui-button-text">'.BizResources::localize( 'ACT_COPY' ).'</span>'.
					'</a>'.
					'<a class="ui-button ui-button-text-icons" href="javascript:deleteAuth(\''.$auth['ids'].'\');">'.
						'<span class="ui-button-icon ui-icon ui-icon-trash"></span>'.
						'<span class="ui-button-text">'.BizResources::localize( 'ACT_DELETE' ).'</span>'.
					'</a>'.
				'</div>'.
			'</td>';
	} else {
		$html .= '<td></td>';
	}

	$html .= '</tr>';
	return $html;
}

/**
 * Composes two HTML rows representing a given combined authorization record as editable.
 *
 * @param array $auth Combined authorization record.
 * @param array $sectiondomain Id-Name lookup map of categories.
 * @param array $statedomain Id-Name lookup  map of statuses.
 * @param array $profiles Id-Name lookup map of profiles.
 * @param string $command The operation requested by user.
 * @param array $authIds The authorization ids involved with the operation.
 * @return string One HTML table row with the record and one row with Select/Deselect All buttons.
 */
function composeAuthorizationsHtmlRowEditable( $auth, $sectiondomain, $statedomain, $profiles, $command, $authIds )
{
	// Add placeholders for the jsTree widgets to show Categories, Statuses and Profiles
	// and show the Cancel and Save buttons (at right side of jsTree).
	$html =
		'<tr class="editable">'.
			'<td><div id="jstree_categories" class="chkbox"></div></td>'.
			'<td><div id="jstree_statuses" class="chkbox"></div></td>'.
			'<td><div id="jstree_profiles" class="chkbox"></div></td>'.
			'<td>'.
				'<div class="btnbar"">'.
					'<a href="javascript:viewAuth();">'.
				      BizResources::localize( 'ACT_CANCEL' ).
					'</a>'.
					'<button onclick="javascript:saveAuth(\''.$auth['ids'].'\');">'.
					   BizResources::localize( 'ACT_SAVE' ).
					'</button>'.
				'</div>'.
			'</td>'.
		'</tr>';

	// Show the Select All and Deselect All buttons (below the jsTree).
	$select = BizResources::localize( 'ACT_SELECT_ALL' );
	$deselect = BizResources::localize( 'ACT_UNSELECT_ALL' );
	$html .=
		'<tr class="editbtnbar">'.
			'<td><div class="btnbar"">'.
				'<a href="javascript:$(\'#jstree_categories\').jstree(\'check_all\');">'.$select.'</a>'.
				'<div class="separator"></div>'.
				'<a href="javascript:$(\'#jstree_categories\').jstree(\'uncheck_all\');">'.$deselect.'</a>'.
			'</div></td>'.
			'<td><div class="btnbar"">'.
				'<a href="javascript:$(\'#jstree_statuses\').jstree(\'check_all\');">'.$select.'</a>'.
				'<div class="separator"></div>'.
				'<a href="javascript:$(\'#jstree_statuses\').jstree(\'uncheck_all\');">'.$deselect.'</a>'.
			'</div></td>'.
			'<td><div class="btnbar"">'.
				'<a href="javascript:$(\'#jstree_profiles\').jstree(\'check_all\');">'.$select.'</a>'.
				'<div class="separator"></div>'.
				'<a href="javascript:$(\'#jstree_profiles\').jstree(\'uncheck_all\');">'.$deselect.'</a>'.
			'</div></td>'.
			'<td></td>'.
		'</tr>';

	return $html;
}

/**
 * Composes a HTML page that shows a printable version of all authorizations of a given brand/issue.
 *
 * @param integer $publ Brand id.
 * @param integer $issue Overrule issue id.
 * @return string HTML report page.
 * @throws BizException
 */
function composeAuthorizationsReport( $publ, $issue )
{
	$profiles = getProfiles();

	$dbh = DBDriverFactory::gen();
	$dbp = $dbh->tablename('publications');
	$dbi = $dbh->tablename('issues');
	$dbg = $dbh->tablename('groups');
	$dbs = $dbh->tablename('publsections');
	$dbst = $dbh->tablename('states');
	$dba = $dbh->tablename('authorizations');

	$txt = '<html><head><title>WoodWing InDesign and InCopy Solutions</title>';
	$txt .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
	$txt .= '<link rel="stylesheet" href="../../config/templates/woodwingmain.css" type="text/css" />';
	$txt .= '<link rel="icon" href="../../config/images/favicon.ico" type="image/x-icon" />';
	$txt .= '<link rel="shortcut icon" href="../../config/images/favicon.ico" type="image/x-icon" />';
	$txt .= '</head>';
	$txt .= '<body style="background-color: #FFFFFF">';

	// header
	$sql = "select * from $dbp where `id` = $publ";
	$sth = $dbh->query($sql);
	$row = $dbh->fetch($sth);
	$tmp = formvar($row['publication']);

	if ($issue > 0) {
		$sql = "select * from $dbi where `id` = $issue";
		$sth = $dbh->query($sql);
		$rowi = $dbh->fetch($sth);
		$tmp .= ' / '.formvar($rowi['name']);
	}
	$txt .= '<h1><img src="../../config/images/woodwing95.gif"/> <img src="../../config/images/lock.gif"/> '
		.BizResources::localize('OBJ_AUTHORIZATIONS_FOR').' '.$tmp.'</h1>';
	$txt .= '<table class="text" width="700">';

	$sql = "SELECT g.`name` as `grp`, s.`section` as `section`, a.`state` as `state`, st.`type` as `type`, ".
		"st.`state` as `statename`, a.`profile` as `profile` ".
		"FROM $dbg g, $dba a ".
		"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
		"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
		"WHERE a.`grpid` = g.`id` and a.`publication` = $publ and a.`issue` = $issue ".
		"ORDER BY g.`name`, s.`section`, st.`type`, st.`state`";
	$sth = $dbh->query($sql);
	$color = array (" bgcolor='#eeeeee'", '');
	$flip = 0;
	$grpName = '';
	while (($row = $dbh->fetch($sth))) {
		// break group
		if ($row['grp'] != $grpName) {
			$grpName = $row['grp'];
			$txt .= '<tr><td>&nbsp;</td></tr>';
			$txt .= '<tr><td><h3><img src="../../config/images/groups_small.gif"> '
				.BizResources::localize('GRP_GROUP').': '.formvar($grpName).'</h3></td></tr>';
			$txt .= '<tr>'
				.'<th>'.BizResources::localize('SECTION').'</th>'
				.'<th>'.BizResources::localize('STATE').'</th>'
				.'<th>'.BizResources::localize('ACT_PROFILE').'</th>'
				.'</tr>';
			$flip = 0;
		}

		$clr = $color[$flip];
		$flip = 1- $flip;
		if( $row['section'] ) {
			$txt .= "<tr$clr><td>".formvar($row['section']).'</td>';
		} else {
			$txt .= "<tr$clr><td>&lt;".BizResources::localize('LIS_ALL').'&gt;</td>';
		}
		if( $row['state'] ) {
			$txt .= '<td>'.formvar($row['type']).'/'.formvar($row['statename']).'</td>';
		} else {
			$txt .= '<td>&lt;'.BizResources::localize('LIS_ALL').'&gt;</td>';
		}
		$txt .= '<td>'.formvar($profiles[$row['profile']]).'</td>';
		$txt .= '</tr>';
	}
	$txt .= '</table><br/><br/>';
	$txt .= '<form><input type="button" value="'.BizResources::localize('ACT_PRINT_THIS_PAGE').'" onclick="window.print()"></form>';
	$txt .= '<a href="javascript:history.back(-1)"><img src="../../config/images/back_32.gif" border="0"></a>';
	$txt .= '</body></html>';
	return $txt;
}