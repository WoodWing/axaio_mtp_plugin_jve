<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('publadmin');

// database stuff
$dbh = DBDriverFactory::gen();
$dbg = $dbh->tablename('groups');
$dbs = $dbh->tablename('publsections');
$dbst = $dbh->tablename('states');
$dba = $dbh->tablename('authorizations');

// determine incoming mode
$publ    = isset($_REQUEST['publ'])   ? intval($_REQUEST['publ'])  : 0; // Publication id. Zero not allowed.
$issue   = isset($_REQUEST['issue'])  ? intval($_REQUEST['issue']) : 0; // Issue id. Zero for all.
$grp     = isset($_REQUEST['grp'])    ? intval($_REQUEST['grp'])   : 0; // User group id. Zero for all, in special mode in which user should pick one first.
$records = isset($_REQUEST['recs'])   ? intval($_REQUEST['recs'])  : 0; // Number of records posted.
$insert  = isset($_REQUEST['insert']) ? (bool)$_REQUEST['insert']  : false; // Whether or not in insertion mode.
$command = isset($_REQUEST['command']) ? $_REQUEST['command'] : 'view';

// Validate auth record ids. Those are passed in for Edit, Delete and Save commands.
// To block SQL injection, cast all ids to integers. To block other attacks, remove negatives and duplicates.
$ids = isset($_REQUEST['authids']) ? explode(',', $_REQUEST['authids'] ) : array();
$ids = array_unique( array_map( 'intval', $ids ) );
$ids = array_filter( $ids, function( $id ) { return $id > 0; } );

// check publication rights
checkPublAdmin($publ);

//////////////////////////////////////////
// print report
//////////////////////////////////////////
if (isset($_REQUEST['report']) && $_REQUEST['report']) {

	$txt = composeAuthorizationsReport( $publ, $issue );
	print HtmlDocument::buildDocument($txt, false);
	exit;
}

$profiles = getProfiles();
$objTypeMap = getObjectTypeMap();

//////////////////////////////////////////
// normal operations
//////////////////////////////////////////
if (!$grp) {
	$mode = 'select';
} else if (isset($_REQUEST['update']) && $_REQUEST['update']) {
	$mode = 'update';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['add']) && $_REQUEST['add']) {
	$mode = 'add';
} else {
	$mode = 'view';
}

// handle request
if ($records > 0) {
	for ($i=0; $i < $records; $i++) {
		$id      = isset($_REQUEST["id$i"])      ? intval($_REQUEST["id$i"])      : 0; // Record id
		$section = isset($_REQUEST["section$i"]) ? intval($_REQUEST["section$i"]) : 0; // Category id
		$state   = isset($_REQUEST["state$i"])   ? intval($_REQUEST["state$i"])   : 0; // Status id
		$profile = isset($_REQUEST["profile$i"]) ? intval($_REQUEST["profile$i"]) : 0; // Profile id
		//echo 'DEBUG: id=['. $id .'] section=['. $section .'] state=['. $state .'] profile=['. $profile .']</br>';
		if ($profile > 0) {
			$sql = "UPDATE $dba SET `publication`=$publ, `issue` = $issue, `grpid`=$grp, ".
						"`section`=$section, `state`=$state, `profile`=$profile ".
					"WHERE `id` = $id";
			$sth = $dbh->query($sql);
		}
	}
}
if ($insert === true) {
	$section = isset($_REQUEST['section']) ? intval($_REQUEST['section']) : 0; // Category id
	$state   = isset($_REQUEST['state'])   ? intval($_REQUEST['state'])   : 0; // Status id
	$profile = isset($_REQUEST['profile']) ? intval($_REQUEST['profile']) : 0; // Profile id
	//echo 'DEBUG: section=['. $section .'] state=['. $state .'] profile=['. $profile .']</br>';
	if ($profile > 0) {
		// handle autoincrement for non-mysql
		$sql = "INSERT INTO $dba (`publication`, `issue`, `grpid`, `section`, `state`, `profile`) ".
				"VALUES ($publ, $issue, $grp, $section, $state, $profile)";
		$sql = $dbh->autoincrement($sql);
		$sth = $dbh->query($sql);
		$id = $dbh->newid($dba, true);
	}
}

// Perform delete operation on authorizations.
if( $command == 'delete' && $ids ) {
	$sql = "DELETE FROM $dba WHERE `id` IN ( ".implode( ',', $ids )." )";
	$dbh->query( $sql );
}

// generate upper part (info or select fields)
$txt = HtmlDocument::loadTemplate( 'authorizations2.htm' );

// Show user group.
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

// generate lower part
$detailtxt = '';

// Collect categories (of the brand/issue).
$sql = "select `id`, `section` from $dbs where `publication` = $publ and `issue` = $issue order by `section`";
$sth = $dbh->query($sql);
$sectiondomain = array();
$sAll = BizResources::localize("LIS_ALL");
while (($row = $dbh->fetch($sth))) {
	$sectiondomain[$row['id']] = $row['section'];
}

// Collect statuses (of the brand/issue).
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

if( $ids && $command == 'edit' ) {

	// Compose JSON data structure of categories for jsTree widget.
	$categoryTree = array();
	$categoryTreeRefs = array();
	foreach( $sectiondomain as $categoryId => $categoryName ) {
		$categoryItem = new stdClass();
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
		$statusGroup->text = formvar($objTypeMap[$statusType]);
		$statusGroup->children = array();
		foreach( $statusPerType as $statusId => $statusName ) {
			$statusItem = new stdClass();
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
		$profileItem->text = formvar($profileName);
		$profileTree[] = $profileItem;
		$profileTreeRefs[$profileId] = $profileItem;
	}

	// Enable the statuses in the jsTree for the authorization the user wants to edit.
	$sql =
		"SELECT a.`id`, a.`section`, a.`state`, a.`profile` ".
		"FROM $dba a ".
		'WHERE a.`id` IN( '.implode( ',', $ids ).' ) ';
	$sth = $dbh->query( $sql );
	while (($row = $dbh->fetch($sth))) {
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

switch( $command ) {
	case 'view':
	case 'edit':
	case 'update':
	case 'delete':
		// Changed order by state to order by code
		$sql = "SELECT a.`id`, a.`section`, a.`state`, a.`profile` ".
				"FROM $dba a ".
				"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
				"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
				"WHERE a.`publication` = $publ and a.`issue` = $issue and a.`grpid` = $grp ".
				"ORDER BY s.`section`, st.`type`, st.`code`";
		$sth = $dbh->query($sql);

		$authMap = array();
		while (($row = $dbh->fetch($sth))) {
			$authMap[] = array(
				'categories' => $row['section'],
				'statuses' => $row['state'],
				'profiles' => $row['profile'],
				'ids' => $row['id']
			);
		}
		$authMap = combineAuthorizations( $authMap );
		$detailtxt .= composeAuthorizationsHtmlTable( $authMap, $sectiondomain, $statedomain, $profiles, $command, $ids );

		$i = 0;
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		while (($row = $dbh->fetch($sth))) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			//$deltxt = "<a href='authorizations2.php?publ=$publ&issue=$issue&grp=$grp&delete=1&id=".$row["id"]."' onClick='return myconfirm(\"delauthor\")'>".BizResources::localize("ACT_DEL")."</a>";
			$detailtxt .= "<tr$clr><td>".inputvar("section$i", $row['section'], 'combo', $sectiondomain, $sAll).'</td>';
			$detailtxt .= '<td>'.inputvar("state$i", $row['state'], 'combo', $statedomain, $sAll).'</td>';
			$detailtxt .= "<td>".inputvar("profile$i", $row["profile"], 'combo', $profiles)."</td>";
			//$detailtxt .= "<td>$deltxt</td>";
			$detailtxt .= '</tr>';
			$detailtxt .= inputvar( "id$i", $row['id'], 'hidden' );
			$i++;
		}
		$detailtxt .= inputvar( 'recs', $i, 'hidden' );
		break;
	case 'add':
		// 1 row to enter new record
		$detailtxt .= '<tr><td>'.inputvar('section', '', 'combo', $sectiondomain, $sAll).'</td>';
		$detailtxt .= '<td>'.inputvar('state','', 'combo', $statedomain, $sAll).'</td>';
		$detailtxt .= '<td>'.inputvar('profile', '', 'combo', $profiles);
		$detailtxt .= '<td></td></tr>';
		$detailtxt .= inputvar( 'insert', '1', 'hidden' );

		// show other authorizations as info
		// Changed order by state to order by code
		$sql = "SELECT a.`id`, s.`section`, a.`state`, st.`type`, ".
					"st.`state` as `statename`, a.`profile` FROM $dba a ".
				"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
				"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
				"WHERE a.`publication` = $publ and a.`issue` = $issue and a.`grpid` = $grp ".
				"ORDER BY s.`section`, st.`type`, st.`code`";
		$sth = $dbh->query($sql);
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		while (($row = $dbh->fetch($sth))) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			if ($row['section']) {
				$detailtxt .= "<tr$clr><td>". formvar($row['section']) .'</td><td>';
			} else {
				$detailtxt .= "<tr$clr><td>". $sAll .'</td><td>';
			}
			if ($row['state']) {
				$detailtxt .= formvar($row['type']).'/'.formvar($row['statename']);
			} else {	
				$detailtxt .= $sAll;
			}	
			$detailtxt .= '</td>';
			$detailtxt .= '<td>'.formvar($profiles[$row['profile']]).'</td>';
			$detailtxt .= '<td></td></tr>';
		}
		break;
}

$button = '';
if( $command != 'edit' ) {
	$button = '<input type="button" value="<!--RES:ACT_ADD_AUTHORIZATION-->" onclick="javascript:addAuth();" />';
}
$txt = str_replace( '<!--VAR:ADD_AUTH_BTN-->', $button , $txt );

// generate total page
$txt = str_replace("<!--ROWS-->", $detailtxt, $txt);
if ($issue > 0) {
	$back = "hppublissues.php?id=$issue";
} else {
	$back = "hppublications.php?id=$publ";
}
$txt = str_replace("<!--BACK-->", $back, $txt);
print HtmlDocument::buildDocument($txt);

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
 * @param array $ids The authorization ids involved with the operation.
 * @return string HTML fragment that shows the authorizations.
 */
function composeAuthorizationsHtmlTable( $authMap, $sectiondomain, $statedomain, $profiles, $command, $ids )
{
	$idsCsv = implode( ',', $ids );
	$html = '';
	foreach( $authMap as $key => $auth ) {
		$html .= '<tr class="separator"><td colspan="4"/></tr>';

		if( $command == 'edit' && $idsCsv && $idsCsv == $auth['ids'] ) {
			$html .= composeAuthorizationsHtmlRowEditable( $auth, $sectiondomain, $statedomain, $profiles, $command, $ids );
		} else {
			$html .= composeAuthorizationsHtmlRowReadonly( $auth, $sectiondomain, $statedomain, $profiles, $command, $ids );
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
 * @param array $ids The authorization ids involved with the operation.
 * @return string HTML table row.
 */
function composeAuthorizationsHtmlRowReadonly( $auth, $sectiondomain, $statedomain, $profiles, $command, $ids )
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
 * @param array $ids The authorization ids involved with the operation.
 * @return string One HTML table row with the record and one row with Select/Deselect All buttons.
 */
function composeAuthorizationsHtmlRowEditable( $auth, $sectiondomain, $statedomain, $profiles, $command, $ids )
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
					'<button style="display: block;" href="javascript:saveAuth(\''.$auth['ids'].'\');">'.
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