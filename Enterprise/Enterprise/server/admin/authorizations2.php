<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBAuthorizations.class.php';

checkSecure('publadmin');

// determine incoming mode
$publ    = isset($_REQUEST['publ'])   ? intval($_REQUEST['publ'])  : 0; // Publication id. Zero not allowed.
$issue   = isset($_REQUEST['issue'])  ? intval($_REQUEST['issue']) : 0; // Issue id. Zero for all.
$grp     = isset($_REQUEST['grp'])    ? intval($_REQUEST['grp'])   : 0; // User group id. Zero for all, in special mode in which user should pick one first.

// check publication rights
checkPublAdmin($publ);

$authApp = new Ww_Admin_Authorizations_App();

// print report
if( isset($_REQUEST['report']) && $_REQUEST['report'] ) {
	$txt = $authApp->composeAuthorizationsReport( $publ, $issue );
	print HtmlDocument::buildDocument( $txt, false );
	exit;
}

$command = isset($_REQUEST['command']) ? $_REQUEST['command'] : 'view';
$authIds = isset($_REQUEST['authids']) ? $authApp->safeCastCsvToDbIds( $_REQUEST['authids'] ) : array();

$profiles = getProfiles();
$objTypeMap = getObjectTypeMap();

// generate upper part (info or select fields)
$txt = HtmlDocument::loadTemplate( 'authorizations2.htm' );

// Show user group.
require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
if( $command == 'view' ) {
	$grptxt = '<select name="grp" onChange="this.form.submit()">';
	foreach( DBUser::listUserGroupNames() as $userGroupId => $userGroupName ) {
		$selected = $userGroupId == $grp ? ' selected="selected"' : '';
		$grptxt .= '<option value="'.$userGroupId.'"'.$selected.'>'.formvar($userGroupName).'</option>';
	}
	$grptxt .= '</select>';
} else {
	$userGroupName = DBUser::getUserGroupName( $grp );
	$grptxt = formvar($userGroupName).inputvar( 'grp', $grp, 'hidden' );
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
require_once BASEDIR.'/server/dbclasses/DBStates.class.php';
$statusDomainTree = DBStates::getStatusNamesForBrandIssue( $publ, $issue );
$statedomain = array();
foreach( $statusDomainTree as $statusType => $statusIdName) {
	foreach( $statusIdName as $statusId => $statusName ) {
		$statedomain[ $statusId ] = $objTypeMap[ $statusType ]." / ".$statusName;
	}
}

// Handle the Delete operation.
if( $authIds && $command == 'delete' ) {
	DBAuthorizations::deleteAuthorizationsByIds( $authIds );
}

// Handle the Edit/Add operations.
if( ($authIds && $command == 'edit') || $command == 'add' ) {

	// Get the authorization from DB the user wants to edit.
	$authRows = array();
	if( $authIds && $command == 'edit' ) {
		$authRows = DBAuthorizations::getAuthorizationRowsByIds( $authIds );
	} else if( $command == 'add' ) {
		$authRows = array( 'section' => 0, 'state' => 0, 'profile' => 0 );
	}

	// Compose (and pre-select) the Categories user wants to edit in the jsTree widget.
	$categoryIds = array_map( function( $row ) { return $row['section']; }, $authRows );
	$jsTree = $authApp->composeCategoryTree( $sectiondomain, $categoryIds );
	$txt = str_replace( '[/*VAR:CATEGORIES*/]', json_encode( $jsTree ), $txt );

	// Compose (and pre-select) the Statuses user wants to edit in the jsTree widget.
	$statusIds = array_map( function( $row ) { return $row['state']; }, $authRows );
	$jsTree = $authApp->composeStatusTree( $statusDomainTree, $objTypeMap, $statusIds );
	$txt = str_replace( '[/*VAR:STATUSES*/]', json_encode( $jsTree ), $txt );

	// Compose (and pre-select) the Profiles user wants to edit in the jsTree widget.
	$profileIds  = array_map( function( $row ) { return $row['profile']; }, $authRows );
	$jsTree = $authApp->composeProfileTree( $profiles, $profileIds );
	$txt = str_replace( '[/*VAR:PROFILES*/]', json_encode( $jsTree ), $txt );
}

// Handle the Save operation.
if( $command == 'save' ) {

	// Retrieve user selected Categories/Statuses/Profiles from the jsTree widgets.
	$selectedCategoryIds = isset( $_REQUEST['selcategoryids'] ) ? $authApp->safeCastCsvToDbIds( $_REQUEST['selcategoryids'], 'cat_' ) : array();
	$selectedStatusIds = isset( $_REQUEST['selstatusids'] ) ? $authApp->safeCastCsvToDbIds( $_REQUEST['selstatusids'], 'stt_' ) : array();
	$selectedProfileIds = isset( $_REQUEST['selprofileids'] ) ? $authApp->safeCastCsvToDbIds( $_REQUEST['selprofileids'], 'prf_' ) : array();

	// When all Categories or Statuses are selected, that is indicated in DB as zero (0).
	if( count( $sectiondomain ) == count( $selectedCategoryIds ) ) {
		$selectedCategoryIds = array( 0 );
	}
	if( count( $statedomain ) == count( $selectedStatusIds ) ) {
		$selectedStatusIds = array( 0 );
	}

	// Store the authorizations in DB.
	$authApp->saveAuthorizations( $selectedCategoryIds, $selectedStatusIds, $selectedProfileIds,
		$authIds, $publ, $issue, $grp );
}

// Retrieve all authorizations from DB that are configured for the brand/issue/group.
$rows = DBAuthorizations::getAuthorizationRowsByBrandIssueUserGroup( $publ, $issue, $grp );

// Combine authorizations DB records that can be shown in one HTML row.
$authMap = new Ww_Admin_Authorizations_Map();
foreach( $rows as $row ) {
	$authMap->add( $row['id'], $row['section'], $row['state'], $row['profile'], $row['bundle'] );
}
$authMap->combine( DBAuthorizations::getMaxBundleId( $publ, $issue, $grp ) );
$newBundleIds = $authMap->getNewBundleIds();
if( $newBundleIds ) foreach( $newBundleIds as $bundleId => $authIds ) {
	DBAuthorizations::updateBundleIds( $bundleId, $authIds );
}

// Compose a HTML table to display the (combined) authorizations.
$detailtxt = $authApp->composeAuthorizationsHtmlTable( $authMap, $sectiondomain, $statedomain, $profiles, $command, $authIds );
$txt = str_replace( '<!--ROWS-->', $detailtxt, $txt );

// If not in edit mode, show the Add Authorization button.
$button = '';
if( $command != 'edit' && $command != 'add' ) {
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

class Ww_Admin_Authorizations_Map
{
	/**
	 * @var array List of authorization configurations to be smartly combined by algorithm.
	 * Could be either the ones that were not bundled by the admin user before, or
	 * existing ones requested by admin user to be automatically optimized.
	 */
	private $authCombis = array();

	/**
	 * @var array List of authorization configurations that were bundled before.
	 * Those are bundled in the way the admin user wants.
	 */
	private $authBundles = array();

	/** @var array $authMap The two arrays above merged. Used to iterate over all.  */
	private $authMap = array();

	/**
	 * Defines one authorization configuration (to be combined later, see combine() function).
	 *
	 * @param integer $authId
	 * @param integer $categoryId
	 * @param integer $statusId
	 * @param integer $profileId
	 * @param integer $bundleId
	 */
	public function add( $authId, $categoryId, $statusId, $profileId, $bundleId )
	{
		if( $bundleId ) {
			if( !isset($this->authBundles[$bundleId]) ) {
				$this->authBundles[$bundleId] = array(
					'categories' => $categoryId,
					'statuses' => $statusId,
					'profiles' => $profileId,
					'ids' => $authId
				);
			} else {
				$bundle = &$this->authBundles[$bundleId]; // by reference, so authBundles gets updated below
				$bundle['categories'] = $this->combineFields( $bundle['categories'], $categoryId );
				$bundle['statuses'] = $this->combineFields( $bundle['statuses'], $statusId );
				$bundle['profiles'] = $this->combineFields( $bundle['profiles'], $profileId );
				$bundle['ids'] = $this->combineFields( $bundle['ids'], $authId );
			}
		} else {
			$this->authCombis[] = array(
				'categories' => $categoryId,
				'statuses' => $statusId,
				'profiles' => $profileId,
				'ids' => $authId
			);
		}
	}

	/**
	 * Functions to iterate over the combined authorizations.
	 *
	 * Usage: $authMap->reset(); $authMap->current(); $authMap->next()
	 *
	 * Note that these functions do NOT return the data itself to hide internal data structure.
	 * Use the getCurrent...Ids() functions to retrieve the properties instead.
	 *
	 * @return bool Whether or not there is a first, next or current item.
	 */
	public function reset()	  { return (bool)reset( $this->authMap ); }
	public function next()    { return (bool)next( $this->authMap ); }
	public function current() { return (bool)current( $this->authMap ); }

	/**
	 * Returns a property of the current combined authorization record.
	 *
	 * @return integer[] List of ids.
	 */
	public function getCurrentAuthIds()     { return $this->getCurrentProperty( 'ids' ); }
	public function getCurrentCategoryIds() { return $this->getCurrentProperty( 'categories' ); }
	public function getCurrentProfileIds()  { return $this->getCurrentProperty( 'profiles' ); }
	public function getCurrentStatusIds()   { return $this->getCurrentProperty( 'statuses' ); }

	/**
	 * Returns a certain property of the current combined authorization record.
	 *
	 * @param string $property Name of the property
	 * @return integer[] List of ids.
	 */
	private function getCurrentProperty( $property )
	{
		$auth = current( $this->authMap );
		return explode( ',', $auth[ $property ] );
	}

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
	 * @param integer $maxBundleId The highest bundle id that already exists in DB.
	 */
	public function combine( $maxBundleId )
	{
		// About the algorithm below...

		// By sorting on Category ids and Status ids, two authorizations that have the same values
		// for both properties will be grouped together. That makes it easier to combine, which is done
		// when two successive rows have the same Category ids and Status ids. The combineRows() function
		// merges the other properties together, in this case, Profile ids and Auth ids.

		// Merging is done in the combineRows() function by using a CSV format. So ids are comma separated.
		// When merging, the ids are always sorted, so before-, but also after the merge.
		// This step is repeated for all combinations of Category ids, Status ids and Profile ids
		// (= cartesian product) to make sure every authorization gets combined that can be combined.

		// After one run, it checks if the original count of combined authorizations differs from
		// the current one. If so, we could make one or more combinations. By making combinations,
		// it is important to realise that ids are merged and so the data has changed and so there
		// a new chance of matches that can be made that were not made before. So as long as we
		// could combine, we continue combining.

		do {
			$originalCount = count( $this->authCombis );

			list( $category, $status, $profile ) = $this->prepareMultiSort( $this->authCombis );
			array_multisort( $category, SORT_ASC, SORT_STRING, $status, SORT_ASC, SORT_STRING, $this->authCombis );
			$this->authCombis = $this->combineRows( $this->authCombis, 'categories', 'statuses' );
			list( $category, $status, $profile ) = $this->prepareMultiSort( $this->authCombis );
			array_multisort( $status, SORT_ASC, SORT_STRING, $category, SORT_ASC, SORT_STRING, $this->authCombis );
			$this->authCombis = $this->combineRows( $this->authCombis, 'statuses', 'categories' );

			list( $category, $status, $profile ) = $this->prepareMultiSort( $this->authCombis );
			array_multisort( $status, SORT_ASC, SORT_STRING, $profile, SORT_ASC, SORT_STRING, $this->authCombis );
			$this->authCombis = $this->combineRows( $this->authCombis, 'statuses', 'profiles' );
			list( $category, $status, $profile ) = $this->prepareMultiSort( $this->authCombis );
			array_multisort( $profile, SORT_ASC, SORT_STRING, $status, SORT_ASC, SORT_STRING, $this->authCombis );
			$this->authCombis = $this->combineRows( $this->authCombis, 'profiles', 'statuses' );

			list( $category, $status, $profile ) = $this->prepareMultiSort( $this->authCombis );
			array_multisort( $category, SORT_ASC, SORT_STRING, $profile, SORT_ASC, SORT_STRING, $this->authCombis );
			$this->authCombis = $this->combineRows( $this->authCombis, 'categories', 'profiles' );
			list( $category, $status, $profile ) = $this->prepareMultiSort( $this->authCombis );
			array_multisort( $profile, SORT_ASC, SORT_STRING, $category, SORT_ASC, SORT_STRING, $this->authCombis );
			$this->authCombis = $this->combineRows( $this->authCombis, 'profiles', 'categories' );

		} while( $originalCount != count( $this->authCombis ) );

		// Replace the ids of the authCombis array with [max+1...n] in preparation of a merge with authBundles.
		if( $this->authCombis ) {
			$bundleIds = range( $maxBundleId + 1, $maxBundleId + count( $this->authCombis ) );
			$this->authCombis = array_combine( $bundleIds, $this->authCombis );
		}

		// Combine the results of the combine algorithm (authCombies) with the manually edit configs (authBundles)
		// so that the caller can iterate of all entries after this.
		$this->authMap = array_merge( $this->authBundles, $this->authCombis );
	}

	/**
	 * Composes a list of new bundle ids that are allocated by the compose algorithm (in memory).
	 *
	 * @return array List of bundle ids (keys). Each entry contains a list of authorization ids.
	 */
	public function getNewBundleIds()
	{
		$retVal = array();
		foreach( $this->authCombis as $bundleId => $authConfig ) {
			foreach( explode( ',', $authConfig['ids'] ) as $authId ) {
				$retVal[ $bundleId ][] = $authId;
			}
		}
		return $retVal;
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
	private function prepareMultiSort( $authMap )
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
	private function combineRows( $authMap, $col1, $col2 )
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
							$authMap[ $prevKey ][ $column ] = $this->combineFields( $authMap[ $prevKey ][ $column ], $auth[ $column ] );
						}
					}
					unset( $authMap[ $key ] );
					$key = null;
				}
				if( !is_null( $key ) ) {
					$prevKey = $key;
				}
			}
		} while( $orgCount != count( $authMap ) );
		return $authMap;
	}

	/**
	 * Combines two CSV lists into one CSV list in a sorted manner.
	 *
	 * @param string $lhs CSV list to compare (Left Hand Side)
	 * @param string $rhs CSV list to compare (Right Hand Side)
	 * @return string Combined CSV list.
	 */
	private function combineFields( $lhs, $rhs )
	{
		$arr = array_merge( explode( ',', $lhs ), explode( ',', $rhs ) );
		sort( $arr );
		return implode( ',', $arr );
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class Ww_Admin_Authorizations_App
{
	/**
	 * Composes a jsTree compatible data object structure of Categories.
	 *
	 * The Categories passed in are pre-selected in the tree.
	 *
	 * @param array $sectiondomain
	 * @param array $preselectIds Category ids to pre-select.
	 * @return stdClass[] List of jsTree nodes (data objects).
	 */
	public function composeCategoryTree( $sectiondomain, $preselectIds )
	{
		$categoryTree = array();
		$categoryTreeRefs = array();
		foreach( $sectiondomain as $categoryId => $categoryName ) {
			$categoryItem = new stdClass();
			$categoryItem->id = 'cat_'.$categoryId;
			$categoryItem->text = formvar( $categoryName );
			$categoryTree[] = $categoryItem;
			$categoryTreeRefs[ $categoryId ] = $categoryItem;
		}
		foreach( $preselectIds as $preselectId ) {
			if( $preselectId ) {
				$categoryItem = $categoryTreeRefs[ $preselectId ];
				$categoryItem->state = new stdClass();
				$categoryItem->state->selected = true;
			} else { // select all
				foreach( $categoryTree as $categoryItem ) {
					$categoryItem->state = new stdClass();
					$categoryItem->state->selected = true;
				}
			}
		}
		return $categoryTree;
	}

	/**
	 * Composes a jsTree compatible data object structure of Statuses.
	 *
	 * The Categories passed in are pre-selected in the tree.
	 *
	 * @param array $statusDomainTree
	 * @param array $objTypeMap
	 * @param array $preselectIds Status ids to pre-select.
	 * @return stdClass[] List of jsTree nodes (data objects).
	 */
	public function composeStatusTree( $statusDomainTree, $objTypeMap, $preselectIds )
	{
		$statusTree = array();
		$statusTreeItemsRefs = array();
		$statusTreeGroupsRefs = array();
		foreach( $statusDomainTree as $statusType => $statusPerType ) {
			$statusGroup = new stdClass();
			$statusGroup->id = 'stt_'.$statusType;
			$statusGroup->text = formvar( $objTypeMap[ $statusType ] );
			$statusGroup->children = array();
			foreach( $statusPerType as $statusId => $statusName ) {
				$statusItem = new stdClass();
				$statusItem->id = 'stt_'.$statusId;
				$statusItem->text = formvar( $statusName );
				$statusGroup->children[] = $statusItem;
				$statusTreeItemsRefs[ $statusId ] = $statusItem;
			}
			$statusTree[] = $statusGroup;
			$statusTreeGroupsRefs[ $statusType ] = $statusGroup;
		}
		foreach( $preselectIds as $preselectId ) {
			if( $preselectId ) {
				$statusItem = $statusTreeItemsRefs[ $preselectId ];
				$statusItem->state = new stdClass();
				$statusItem->state->selected = true;
			} else {
				foreach( $statusTree as $statusGroup ) {
					$statusGroup->state = new stdClass();
					$statusGroup->state->selected = true;
				}
			}
		}
		return $statusTree;
	}

	/**
	 * Composes a jsTree compatible data object structure of Profiles.
	 *
	 * The Categories passed in are pre-selected in the tree.
	 *
	 * @param array $profiles
	 * @param array $preselectIds Profile ids to pre-select.
	 * @return stdClass[] List of jsTree nodes (data objects).
	 */
	public function composeProfileTree( $profiles, $preselectIds )
	{
		$profileTree = array();
		$profileTreeRefs = array();
		foreach( $profiles as $profileId => $profileName ) {
			$profileItem = new stdClass();
			$profileItem->id = 'prf_'.$profileId;
			$profileItem->text = formvar($profileName);
			$profileTree[] = $profileItem;
			$profileTreeRefs[$profileId] = $profileItem;
		}
		foreach( $preselectIds as $preselectId ) {
			if( $preselectId ) {
				$profileItem = $profileTreeRefs[ $preselectId ];
				$profileItem->state = new stdClass();
				$profileItem->state->selected = true;
			}
		}
		return $profileTree;
	}

	/**
	 * Save one bundle of configured authorizations in DB.
	 *
	 * It deletes existing authorizations from DB that are no longer in the bundle.
	 * When saving a new bundle, a new bundle id gets assigned to all authorizations.
	 *
	 * IMPORTANT: When caller has cleared the bundle id before calling this function
	 * it won't add authorizations that can be found in other bundles and it will
	 * delete authorizations that are 'more specific'. In other terms, it jumps into
	 * intelligence mode whereby redundant authorizations are not stored or cleaned.
	 *
	 * A 'more specific' authorization has the category and/or status set, while
	 * a 'more generic' authorization has one/both set to zero (all). So in that way
	 * they could be 'matching', since a zero (all) supersedes a specific value.
	 * For those cases, technically, the specific authorizations can be removed (optimized).
	 * But that would be very confusing for the admin user; e.g. when adding a all-all
	 * authorization would delete all other configurations made before, which would
	 * be simply too dangerous and may lead to frustrations. So cleaning should be done
	 * manually, or auto cleaning should be an explicit request (by clearing the bundle ids).
	 *
	 * @param integer[] $selectedCategoryIds New user selection of category ids to be saved.
	 * @param integer[] $selectedStatusIds New user selection of status ids to be saved.
	 * @param integer[] $selectedProfileIds New user selection of profile ids to be saved.
	 * @param integer[] $authIds Authorizations that were opened for editing by user.
	 * @param integer $brandId
	 * @param integer $issueId
	 * @param integer $userGroupId
	 */
	function saveAuthorizations( $selectedCategoryIds, $selectedStatusIds, $selectedProfileIds,
	                             $authIds, $brandId, $issueId, $userGroupId )
	{
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
		if( $authIds ) {
			$rows = DBAuthorizations::getAuthorizationRowsByIds( $authIds );
		} else { // Happens when handling a Save command after an Add/Copy command.
			$rows = array();
		}
		foreach( $rows as $row ) {
			if( !isset( $selectedAuths[ $row['section'] ][ $row['state'] ][ $row['profile'] ] ) ) {
				$deleteAuthIds[] = $row['id'];
			}
		}
		if( $deleteAuthIds ) {
			DBAuthorizations::deleteAuthorizationsByIds( $deleteAuthIds );
		}

		// Determine the bundle id. When user creates new authorizations take a new bundle id.
		if( $rows ) {
			$firstRow = reset( $rows );
			$bundleId = $firstRow['bundle'];
		} else {
			$bundleId = DBAuthorizations::getMaxBundleId( $brandId, $issueId, $userGroupId ) + 1;
		}

		// Create all user configured authorizations in DB that do not exist yet.
		$dbAuths = array();
		foreach( $rows as $row ) {
			$dbAuths[ $row['section'] ][ $row['state'] ][ $row['profile'] ] = true;
		}
		foreach( $selectedCategoryIds as $categoryId ) {
			foreach( $selectedStatusIds as $statusId ) {
				foreach( $selectedProfileIds as $profileId ) {
					if( !isset( $dbAuths[ $categoryId ][ $statusId ][ $profileId ] ) ) {

						// For logic within this if-part, see function header for full explanation.
						// The bundle id is zero when caller has requested for auto clean,
						// or when the first time editing authorizations after DB migration from < 10.1.

						// When bundle id is set, check for existence within the bundle only.
						// When bundle is zero, check for existence within the whole brand/issue/group.
						if( !DBAuthorizations::doesAuthorizationExists( $brandId, $issueId, $userGroupId,
							$categoryId, $statusId, $profileId, $bundleId ) ) {

							// Only create new authorization when it does not exist (see check above).
							DBAuthorizations::insertAuthorizationRow( $brandId, $issueId, $userGroupId,
								$categoryId, $statusId, $profileId, $bundleId );

							// Only delete 'more specific' authorizations in auto clean mode.
							if( !$bundleId ) {
								DBAuthorizations::deleteMoreSpecificAuthorizations( $brandId, $issueId, $userGroupId,
									$categoryId, $statusId, $profileId );
							}
						}
					}
				}
			}
		}
	}

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

	/**
	 * Composes a HTML table which shows all given authorizations.
	 *
	 * In edit mode, there is one record being edit at the same time, while the others
	 * are readonly. The composed HTML table visualizes the record being edit.
	 *
	 * When the page is in readonly mode, all buttons are shown, except Save and Cancel.
	 * When the page is in edit mode, all buttons are hidden, except Save and Cancel.
	 *
	 * @param Ww_Admin_Authorizations_Map $authMap Combined authorization records.
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
		for( $authMap->reset(); $authMap->current(); $authMap->next() ) {
			$html .= '<tr class="separator"><td colspan="4"/></tr>';

			$authMapIdsCsv = implode( ',', $authMap->getCurrentAuthIds() );
			if( $command == 'edit' && $authIdsCsv && $authIdsCsv == $authMapIdsCsv ) {
				$html .= $this->composeAuthorizationsHtmlRowEditable( $authMapIdsCsv );
			} else {
				$html .= $this->composeAuthorizationsHtmlRowReadonly( $authMap, $sectiondomain, $statedomain, $profiles, $command, $authIds );
			}
		}
		if( $command == 'add' ) {
			$html .= $this->composeAuthorizationsHtmlRowEditable( '' );
		}
		return $html;
	}

	/**
	 * Composes an HTML row representing a given combined authorization record as readonly.
	 *
	 * @param Ww_Admin_Authorizations_Map $authMap Combined authorization records.
	 * @param array $sectiondomain Id-Name lookup map of categories.
	 * @param array $statedomain Id-Name lookup  map of statuses.
	 * @param array $profiles Id-Name lookup map of profiles.
	 * @param string $command The operation requested by user.
	 * @param array $authIds The authorization ids involved with the operation.
	 * @return string HTML table row.
	 */
	function composeAuthorizationsHtmlRowReadonly( $authMap, $sectiondomain, $statedomain, $profiles, $command, $authIds )
	{
		$all = BizResources::localize( "LIS_ALL" );
		$html = '<tr class="readonly">';

		// Compose list of Categories in HTML.
		$html .= '<td><div class="chkbox"><ul>';
		$authCategoryIds = array_flip( $authMap->getCurrentCategoryIds() );
		foreach( array_keys( $sectiondomain + array( 0 ) ) as $categoryId ) { // respect DB order
			if( array_key_exists( $categoryId, $authCategoryIds ) ) {
				$html .= '<li>';
				$html .= $categoryId ? formvar( $sectiondomain[ $categoryId ] ) : formvar( $all );
				$html .= '</li>';
			}
		}
		$html .= '</ul></div></td>';

		// Compose list of Statuses in HTML.
		$html .= '<td><div class="chkbox"><ul>';
		$authStatusIds = array_flip( $authMap->getCurrentStatusIds() );
		foreach( array_keys( $statedomain + array( 0 ) ) as $statusId ) { // respect DB order
			if( array_key_exists( $statusId, $authStatusIds ) ) {
				$html .= '<li>';
				$html .= $statusId ? formvar( $statedomain[ $statusId ] ) : formvar( $all );
				$html .= '</li>';
			}
		}
		$html .= '</ul></div></td>';

		// Compose list of Profiles in HTML.
		$html .= '<td><div class="chkbox"><ul>';
		$authProfileIds = array_flip( $authMap->getCurrentProfileIds() );
		foreach( array_keys( $profiles ) as $profileId ) { // respect DB order
			if( array_key_exists( $profileId, $authProfileIds ) ) {
				$html .= '<li>';
				$html .= formvar( $profiles[ $profileId ] );
				$html .= '</li>';
			}
		}
		$html .= '</ul></div></td>';

		// Show Edit, Copy and Delete buttons (only when not in Edit/Add mode).
		if( $command != 'edit' && $command != 'add' ) {
			$authMapIdsCsv = implode( ',', $authMap->getCurrentAuthIds() );
			$html .=
				'<td>'.
				'<div class="btnbar"">'.
				'<a class="ui-button ui-button-text-icons" href="javascript:editAuth(\''.$authMapIdsCsv.'\');">'.
				'<span class="ui-button-icon ui-icon ui-icon-pencil"></span>'.
				'<span class="ui-button-text">'.BizResources::localize( 'ACT_EDIT' ).'</span>'.
				'</a>'.
				'<a class="ui-button ui-button-text-icons" href="javascript:copyAuth(\''.$authMapIdsCsv.'\');">'.
				'<span class="ui-button-icon ui-icon ui-icon-copy"></span>'.
				'<span class="ui-button-text">'.BizResources::localize( 'ACT_COPY' ).'</span>'.
				'</a>'.
				'<a class="ui-button ui-button-text-icons" href="javascript:deleteAuth(\''.$authMapIdsCsv.'\');">'.
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
	 * @param string $nextAuthIdsCsv The authorization ids (in CSV notation) to involve for next operation.
	 * @return string One HTML table row with the record and one row with Select/Deselect All buttons.
	 */
	function composeAuthorizationsHtmlRowEditable( $nextAuthIdsCsv )
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
			'<button onclick="javascript:saveAuth(\''.$nextAuthIdsCsv.'\');">'.
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
		$dbp = $dbh->tablename( 'publications' );
		$dbi = $dbh->tablename( 'issues' );
		$dbg = $dbh->tablename( 'groups' );
		$dbs = $dbh->tablename( 'publsections' );
		$dbst = $dbh->tablename( 'states' );
		$dba = $dbh->tablename( 'authorizations' );

		$txt = '<html><head><title>WoodWing InDesign and InCopy Solutions</title>';
		$txt .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
		$txt .= '<link rel="stylesheet" href="../../config/templates/woodwingmain.css" type="text/css" />';
		$txt .= '<link rel="icon" href="../../config/images/favicon.ico" type="image/x-icon" />';
		$txt .= '<link rel="shortcut icon" href="../../config/images/favicon.ico" type="image/x-icon" />';
		$txt .= '</head>';
		$txt .= '<body style="background-color: #FFFFFF">';

		// header
		$sql = "select * from $dbp where `id` = $publ";
		$sth = $dbh->query( $sql );
		$row = $dbh->fetch( $sth );
		$tmp = formvar( $row['publication'] );

		if( $issue > 0 ) {
			$sql = "select * from $dbi where `id` = $issue";
			$sth = $dbh->query( $sql );
			$rowi = $dbh->fetch( $sth );
			$tmp .= ' / '.formvar( $rowi['name'] );
		}
		$txt .= '<h1><img src="../../config/images/woodwing95.gif"/> <img src="../../config/images/lock.gif"/> '
			.BizResources::localize( 'OBJ_AUTHORIZATIONS_FOR' ).' '.$tmp.'</h1>';
		$txt .= '<table class="text" width="700">';

		$sql = "SELECT g.`name` as `grp`, s.`section` as `section`, a.`state` as `state`, st.`type` as `type`, ".
			"st.`state` as `statename`, a.`profile` as `profile` ".
			"FROM $dbg g, $dba a ".
			"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
			"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
			"WHERE a.`grpid` = g.`id` and a.`publication` = $publ and a.`issue` = $issue ".
			"ORDER BY g.`name`, s.`section`, st.`type`, st.`state`";
		$sth = $dbh->query( $sql );
		$color = array( " bgcolor='#eeeeee'", '' );
		$flip = 0;
		$grpName = '';
		while( ( $row = $dbh->fetch( $sth ) ) ) {
			// break group
			if( $row['grp'] != $grpName ) {
				$grpName = $row['grp'];
				$txt .= '<tr><td>&nbsp;</td></tr>';
				$txt .= '<tr><td><h3><img src="../../config/images/groups_small.gif"> '
					.BizResources::localize( 'GRP_GROUP' ).': '.formvar( $grpName ).'</h3></td></tr>';
				$txt .= '<tr>'
					.'<th>'.BizResources::localize( 'SECTION' ).'</th>'
					.'<th>'.BizResources::localize( 'STATE' ).'</th>'
					.'<th>'.BizResources::localize( 'ACT_PROFILE' ).'</th>'
					.'</tr>';
				$flip = 0;
			}

			$clr = $color[ $flip ];
			$flip = 1 - $flip;
			if( $row['section'] ) {
				$txt .= "<tr$clr><td>".formvar( $row['section'] ).'</td>';
			} else {
				$txt .= "<tr$clr><td>&lt;".BizResources::localize( 'LIS_ALL' ).'&gt;</td>';
			}
			if( $row['state'] ) {
				$txt .= '<td>'.formvar( $row['type'] ).'/'.formvar( $row['statename'] ).'</td>';
			} else {
				$txt .= '<td>&lt;'.BizResources::localize( 'LIS_ALL' ).'&gt;</td>';
			}
			$txt .= '<td>'.formvar( $profiles[ $row['profile'] ] ).'</td>';
			$txt .= '</tr>';
		}
		$txt .= '</table><br/><br/>';
		$txt .= '<form><input type="button" value="'.BizResources::localize( 'ACT_PRINT_THIS_PAGE' ).'" onclick="window.print()"></form>';
		$txt .= '<a href="javascript:history.back(-1)"><img src="../../config/images/back_32.gif" border="0"></a>';
		$txt .= '</body></html>';
		return $txt;
	}
}