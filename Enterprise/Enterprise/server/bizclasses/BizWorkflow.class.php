<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizWorkflow
{
	private static $routeToCache = null;
	private static $avoidOverrulePreSelection = false;
	static public function setAvoidOverrulePreSelection() { self::$avoidOverrulePreSelection = true; }

	/**
	 * Returns states plus routeto users and groups for specified object or Pub/Iss/Sec
	 *
	 * @param string $user
	 * @param string $id	- object id, should be used when object is created (leave empty when about to create new object)
	 * @param string $publication  publication id, full target (not id) of new object to create (leave empty for existing objects)
	 * @param string $issue - issue id, see $publicatio
	 * @param string $section - section id, see $publication
	 * @param string $type - object type (Article,Layout,Image,etc) of object to create (leave empty for existing objects)
	 * @throws BizException
	 * @return WflGetStatesResponse Response with 3 elemenents: array of states, array of users and array of usergroups
	 */
	public static function getStatesExtended( $user, $id,  $publication,  $issue,  $section, $type )
	{
		// if id: get object and look for states, otherwise use given parameters
		$dbDriver = DBDriverFactory::gen();
		if ($id) {

			// is it an alien, get object from content source to determine type, publication and category:
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			$alien = BizContentSource::isAlienObject( $id );
			if( $alien ) {
				$shadowID = BizContentSource::getShadowObjectID($id);
				if( !empty( $shadowID ) ) {
					// we have shadow, use that and set alien to false, so we get object from DB
					$id 	= $shadowID;
					$alien 	= false;
				}
			}

			if( $alien ) {
				// Check if we already have a shadow object for this alien. If so, change the id
				$alienObject = BizContentSource::getAlienObject( $id, 'none', false );
				$pubId = $alienObject->MetaData->BasicMetaData->Publication->Id;
				$issId = 0;
				$secId = $alienObject->MetaData->BasicMetaData->Category->Id;
				$type = $alienObject->MetaData->BasicMetaData->Type;
			} else {
				// native Enterprise object, get it from DB:
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$sth = DBObject::getObject( $id );
				if (!$sth) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
				$row = $dbDriver->fetch($sth);
				$pubId = $row['publication'];

				require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
				$targets = BizTarget::getTargets($user, $id);
				$issId = BizTarget::getDefaultIssueId($pubId, $targets);

				$secId = $row['section'];
				$type = $row['type'];
			}
		} else {
			if( !isset($publication->Id) || empty($publication->Id) ) { // publication is mandatory
				throw new BizException( 'ERR_NOTFOUND', 'Client', BizResources::localize('PUBLICATION') );
			}
			$pubId = $publication->Id;
			$issId = isset($issue->Id)   ? $issue->Id   : null; // null means all
			$secId = isset($section->Id) ? $section->Id : null; //    "    "
		}

		// For overrule issues, we also need to pass issue to get users and groups:
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$overruleIssue = DBIssue::isOverruleIssue( $issId );

		// return info
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesResponse.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		return new WflGetStatesResponse(
			self::getStates( $user, $pubId, $overruleIssue ? $issId : null, $secId, $type ),
			BizUser::getUsers( $pubId, $overruleIssue ? $issId : null, 'fullname', true ),
			BizUser::getUserGroups( $pubId, $overruleIssue ? $issId : null, true )); // full define, routable only
	}

	/**
	 * Returns statuses for the given Pub/Issue/Section/object type. Caller does not have to worry about overrule issues.
	 *
	 * About the Personal Status feature...
	 * As you know, the Personal status is designed to keep the document for your eyes only. Since introduction of the
	 * Personal status feature, it was hidden for InDesign users creating articles (from a layout). The main reason to
	 * hide the Personal status from workflow dialogs in this specific situation is that Personal status is always the
	 * first entry in the list of statuses. As a result, articles would be too easily created in the Personal status,
	 * which is unwanted in most cases; Typically layouters would create an article from layout to send out to someone
	 * else (to write-to-fit). By default (accidentally overlooking the status combo), the article would be created at
	 * the layouter's Personal status and so unaccessible for the user to whom the article is sent to. Note that the
	 * server does not accept creating articles (or any objects) for someone else in his/her Personal status, and so
	 * choosing Personal status and Route To someone else, will silently Route To the user performing the action (in
	 * this case the layouter). However, since the introduction of Content Station, the status lists at the workflow
	 * dialogs became very inconsistent with InDesign since men would *always* see the Personal status, no matter the
	 * object type or application.
	 * Since v6.1, to make both applications consistent at this point, and to avoid the initial problem (layouters
	 * accidentally creating articles in Personal status), we have changed the system's behavior a bit. The server *always*
	 * return the Personal status to client applications to show in their workflow dialogs. The client applications
	 * are now responsible to avoid this problem; InDesign should pre-select the 2nd status in the list when the first
	 * one is the  personal status and the user is about to create a new article.
	 * Since v6.5, the Personal status has been moved to the end of the status list! (See BZ#5458) This is to allow
	 * configuring a 'default' status, which is the first one. Having the Personal always on top would block this feature.
	 *
	 * @param string	$user
	 * @param string	$publication	publication id
	 * @param string	$issue		issue id
	 * @param string	$section		section id
	 * @param string	$type		object type
	 * @param boolean $checkForOverruleIssue if you know that the issue you pass in is overrule, pass in false to save a DB call.
	 * @param boolean $logon
	 * @throws BizException
	 * @return array of State
	 */
	public static function getStates($user, $publication, $issue = null, $section = null, $type = null, $checkForOverruleIssue=true, $logon = false)
	{
		$ret = array();

		// If issue is specified we need to see if this is an overrule issue
		// if not we should get the publication's states
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		if( $checkForOverruleIssue && !is_null($issue) && !DBIssue::isOverruleIssue( $issue ) ) {
			$issue = null;
		}

		// Now get regular states from DB and add them if the user has some sort of access to them
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		$sts = DBWorkflow::listStatesCached( $publication, $issue, $section, $type );
		if ($sts === false) {
			$dbDriver = DBDriverFactory::gen();
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		// TO DO: Remove use of globals
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
		if ($user) {
			$globAuth->getRights($user, $publication, $issue, $section);
		}
		foreach ($sts as $row){
			// check for any right on this state
			if (!$user || $globAuth->checkright('', $publication, $issue, $section, $type, $row['id']))
			{
				// if issue is set: only get these specific records (performance)
				$getit = true;
				if ($publication && $issue){
					$getit = (($row['issue'] == $issue) /*||  $row['issue'] == 0*/); // BZ#4189
				}

				if ($getit)
				{
					$color = $row['color'];
					if( strlen( $color ) > 0 ){
						$color = substr( $color, 1 ); // remove leading #
					}
					$route = '';
					if ($logon !== true) {
						$route = self::doGetDefaultRouting( $publication, $issue, $section, $row['id'] );
					}
					if( empty( $route ) ) { $route = null; }
					$ret[] =new State( $row['id'], $row['state'], $row['type'], trim($row['produce']) != '',
						$color, $route );
				}
			}

		}

		// When the Personal Status server feature is enabled and,
		// a) no object type is given, the personal statuses for all object types are included.
		// - OR -
		// b) a specific object type is given, only the personal status for that type is included*.
		// * However, this is not done for: Advert, Plan, Library, Task, DossierTemplate, Hyperlink, and all templates
		$types = array_keys( getObjectTypeMap() );
		if(strtolower(PERSONAL_STATE) == strtolower('ON')){
			if( !is_null($type) && !empty($type) ) { // type given?
				$exceptionTypes = array(
					// Showing personal status does not make much sense for:
					'Advert', 'Plan', 'Library',
					'Task', // => men typically creates tasks for someone else
					'DossierTemplate',
					'Hyperlink', // => has no workflow, so no personal status
					'ArticleTemplate',
					'LayoutTemplate',
					'PublishFormTemplate',
					'AdvertTemplate',
					'LayoutModuleTemplate' // => templates are typically for global usage
				);
				if( in_array( $type, $types ) && !in_array( $type, $exceptionTypes ) ) {
					$ret[] = new State( '-1', BizResources::localize('PERSONAL_STATE'), $type, false,
						substr(PERSONAL_STATE_COLOR, 1), $user);
				}
			} else {
				foreach( $types as $objtype ){
					$ret[]= new State( '-1', BizResources::localize('PERSONAL_STATE'), $objtype, false,
						substr(PERSONAL_STATE_COLOR, 1), $user);
				}
			}
		}

		return $ret;
	}

	/**
	 * Flushes the DBWorkflow state cache.
	 *
	 * Flushes the cache so the states cache needs to be retrieved on the next
	 * listStatesCached call to DBWorkflow.
	 *
	 * @static
	 * @return void
	 */
	public static function flushStatesCache()
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		DBWorkflow::flushStatesCache();
	}


	public static function validateWorkflowData( MetaData &$metaData, $targets, $user, $curState = null )
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';

		if( !$metaData->WorkflowMetaData ) {
			return; // nothing to do when nothing is given
		}

		// Is a valid status passed?
		$state = $metaData->WorkflowMetaData->State ? $metaData->WorkflowMetaData->State->Id : null;

		// If state is empty and we have a current state, this means it should move to next:
		if (!$state && $curState ) {
			// no state: try nextstate
			$state = DBWorkflow::nextState( $curState );
			// still no state: just keep current state
			if (!$state) {
				$state = $curState;
			}

			$metaData->WorkflowMetaData->State = new State( $state );

			// RouteTo is set to automatic routing for the next status or empty if no automatic
			// routing defined. See Routing v6.1 spec for details
			$metaData->WorkflowMetaData->RouteTo = self::setDefaultRouting( $metaData, $targets );
		}

		if( is_null( $state )) {
			$publicationId = $metaData->BasicMetaData->Publication->Id;
			// Object can reside in one workflow definition at any one time.
			// In case of an overrule issue, that issue defines the workflow.
			// Therefore an object can never be assigned to [two overrule issues] OR [overrule issue and a normal issue],
			// so it's safe to get the first issue from the object targets and let the callee find out whether or not that's an overrule issue.
			$issueId = isset( $targets[0]->Issue->Id ) ? $targets[0]->Issue->Id : null;
			$categoryId	 = $metaData->BasicMetaData->Category->Id;
			$objectType = $metaData->BasicMetaData->Type;

			$states = self::getStates( $user, $publicationId, $issueId, $categoryId, $objectType );
			if( $states ) {
				$state = $states[0]->Id;
			}
			$metaData->WorkflowMetaData->State = new State( $state );

			// RouteTo is set to automatic routing for the next status or empty if no automatic
			// routing defined. See Routing v6.1 spec for details
			$metaData->WorkflowMetaData->RouteTo = self::setDefaultRouting( $metaData, $targets );

		}

		if( !DBWorkflow::checkState( $state ) ) {
			$stateName = $metaData->WorkflowMetaData->State ? $metaData->WorkflowMetaData->State->Name : '';
			throw new BizException( 'ERR_INVALIDSTATE', 'Client', $state, null, array( $stateName ) );
		}

		// If personal status (id -1) we force routeto to the user
		if ($state == -1) {
			require_once BASEDIR . '/server/bizclasses/BizUser.class.php';
			$metaData->WorkflowMetaData->RouteTo = BizUser::resolveFullUserName($user);
		}

		// Note: before v6.1 we would now check for empty routeto and apply default routeto in that
		// case, but all clients now handle default routeo in UI. So when nothing is selected in UI
		// it should be applied like that, so nothing to do here.

	}

	private static function setDefaultRouting( MetaData $meta, $targets )
	{
		$publ 	= $meta->BasicMetaData->Publication->Id;
		$sect	= $meta->BasicMetaData->Category->Id;
		$state	= $meta->WorkflowMetaData->State->Id;

		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
		$issue = BizTarget::getDefaultIssueId($publ, $targets);

		return self::doGetDefaultRouting( $publ, $issue, $sect, $state );
	}

	/**
	 * Clears internally cached routing information.
	 *
	 * Can be useful for testing purposes. For example after changing the routing
	 * definitions in the database, a request for the same brand id / issue id would
	 * depend on cached information, which is then unwanted. By clearing the cache first,
	 * later the changed routing will be read from database into cache again.
	 */
	public static function clearRouteToCache()
	{
		self::$routeToCache = null;
	}

	/*
	* @since v7.4.1: Changed this method from private to public. (BZ#4729)
	*
	* Based on these parameters: publication Id( $publ ) , Issue Id( $issue ),
	* section Id( $sect ) and status Id ( $state ), the routeTo is returned.
	* When both publication and issue id are sent as -1, empty string '' is returned.
	*
	* @param int $publ Publication Id.
	* @param int $issue Issue Id.
	* @param int $sect Category(section) Id. 0 for all categories (sections).
	* @param int $state Status Id.
	* @return string routeTo user fullname. When no routeTo is defined or found, empty string '' is returned.
	*/

	public static function doGetDefaultRouting($publ, $issue, $sect, $state )
	{
		$result = self::getDefaultRoutingUsername($publ, $issue, $sect, $state );
		require_once BASEDIR . '/server/bizclasses/BizUser.class.php';
		return !empty($result) ? BizUser::resolveFullUserName($result) : '';
	}

	/**
	 * Resolves the routeto for the entered input values.
	 *
	 * @param int $publ Publication Id.
	 * @param int $issue Issue Id.
	 * @param int $sect Category(section) Id. 0 for all categories (sections).
	 * @param int $state Status Id.
	 * @return string The RouteTo as defined in the routing table for the entered values.
	 */
	public static function getDefaultRoutingUsername( $publ, $issue, $sect, $state )
	{
		$choice1 = $choice2 = $choice3 = $choice4 = '';
		$rows = array();
		$result = '';

		if( !self::$routeToCache ) {
			self::$routeToCache = array();
			self::$routeToCache['pubId'] = -1;
			self::$routeToCache['issueId'] = -1;
			self::$routeToCache['saveRows'] = array();
		}

		if( $publ == self::$routeToCache['pubId'] && $issue == self::$routeToCache['issueId'] ) {
			$rows = self::$routeToCache['saveRows'];
		} else {
			require_once BASEDIR.'/server/dbclasses/DBRouting.class.php';
			$sth = DBRouting::getRouting($publ, $issue);
			$dbDriver = DBDriverFactory::gen();

			while (($row = $dbDriver->fetch($sth))) {
				$rows[] = $row;
			}

			// Update cache with request data and found DB rows.
			self::$routeToCache['pubId'] = $publ;
			self::$routeToCache['issueId'] = $issue;
			self::$routeToCache['saveRows'] = $rows;
		}

		foreach($rows as $row) {
			if ($row['section'] == $sect && $row['state'] == $state) {
				$choice1 = $row['routeto'];
			} elseif ($row['section'] == $sect && !$row['state']) {
				$choice2 = $row['routeto'];
			} elseif (!$row['section'] && $row['state'] == $state) {
				$choice3 = $row['routeto'];
			} elseif (!$row['section'] && !$row['state']) {
				$choice4 = $row['routeto'];
			}
		}

		if ($choice1) {
			$result =  $choice1;
		}
		elseif ($choice2) {
			$result =  $choice2;
		}
		elseif ($choice3) {
			$result =  $choice3;
		}
		elseif ($choice4) {
			$result =  $choice4;
		}

		// Force result to a string.
		return !empty($result) ? $result : '';
	}

	/**
	 * Validates parameters sent in for GetDialog request and throws BizException when there's invalid parameters.
	 *
	 * @param array $metaData List of MetaDataValue object.
	 * @param bool $multipleObjects Indicate whether the dialog is for single object or multiple objects.
	 * @param string $action Workflow ActionType.
	 * @param array $targets List of targets, should null set when $multipleObjects is true.
	 * @param int $defaultDossier Dossier ID. Tells if client can draw the Dossier prop, should be null when $multipleObjects is true.
	 * @param string $parentId Used for create dialog, should be null when $multipleObjects is true.
	 * @param string $templateId Used for Create dialog only, should be null when $multipleObjects is true.
	 * @param array $areas A list of 'Workflow' OR 'Trash. (Can only set either one).
	 * @param int $objId Object db id retrieved from $metaData[ID]. This should only be set when $objIds is empty.
	 * @param array $objIds List of Object id. This should only be set when $objId is empty.
	 * @throws BizException
	 */
	private static function getDialogBasicParamsValidation( $metaData, $multipleObjects, $action, $targets,
	                                                        $defaultDossier, $parentId, $templateId, $areas, $objId, $objIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR .'/server/dbclasses/DBIssue.class.php';
		// Do basic parameter validation
		$idExists = array_key_exists( 'ID', $metaData );
		$idsExists = array_key_exists( 'IDs', $metaData );

		// Validate request data for multiple objects.
		if( $multipleObjects ) {
			$prefix = 'GetDialog service: When the MultipleObjects parameter is set to \'true\', ';
			if( $idExists || !$idsExists ) { // Contains 'ID' or no 'IDs' in the request.
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . 'the MetaData parameter should contain the \'IDs\' item but not the \'ID\' item.' );
			}
			if( count($objIds) <= 1 ) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . 'object IDs ('.implode(',', $objIds ).') provided should be more than 1, '.
					'or use the \'ID\' parameter instead.' );
			}
			if( !in_array( $action, self::getMultiObjectsAllowedActions() )){
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . 'the Action parameter should be set to \'SetProperties\' or \'SendTo\'.' );
			}
			if( $targets || $defaultDossier || $parentId || $templateId ) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . 'the Targets, DefaultDossier, Parent and Template parameters should be set to \'null\'.' );
			}
			if( !is_null($areas) && in_array('Trash',$areas) ) { // Must be workflow.
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . 'the Areas parameter should be set to \'null\' or should contain \'Workflow\'.' );
			}
			if( !DBObject::isSameObjectTypeAndPublication( $objIds )) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . 'object IDs ('.implode(',', $objIds ).') provided must be of the same object '.
					'type and from the same publication.');
			}
			$overruleIssueObjIds = DBIssue::getOverruleIssueIdsFromObjectIds( $objIds );
			$nonOverruleIssueIds = array_diff( $objIds, array_keys( $overruleIssueObjIds ) );
			if( $overruleIssueObjIds && $nonOverruleIssueIds ) { // Cannot have mixture of workflow and overrule objects.
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . 'object IDs ('.implode(',', $objIds ).') provided must be from the same Publication '.
					' or the same overuleissues.');
			}
			if( $overruleIssueObjIds ) {
				$sameOverruleissue = DBIssue::isSameOverruleIssue( $objIds );
				if( !is_null( $sameOverruleissue ) && // Are all objects overruleissue objects?
					$sameOverruleissue === false ) { // Are they the same overruleissue?
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
						$prefix . 'object IDs ('.implode(',', $objIds ).') provided must be of the same overrule issue.' );
				}
			}
			if( !array_key_exists( 'IDs', $metaData ) ||
				!array_key_exists( 'Type', $metaData ) ||
				!array_key_exists( 'Publication', $metaData ) ) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . ' the MetaData parameter should at least contain \'IDs\', \'Type\' and \'Publication\'. ' );
			}
		} else { // For single object.
			$prefix = 'GetDialog service: When the MultipleObjects parameter is set to \'false\', ';
			if( !$idExists || $idsExists ) { // Contains 'IDs' or no 'ID' in the request.
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					$prefix . 'the MetaData parameter should contain the \'ID\' item but not the \'IDs\' item.' );
			}

			$actionWithoutObjId = ($action == 'Create' || $action == 'Query');
			if( ($actionWithoutObjId && $objId) || (!$actionWithoutObjId && !$objId) ) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'GetDialog service: '.
					'When the Action parameter is "Create" or "Query", the object ID parameter should be nil! '.
					'When the Action parameter is other than "Create" and "Query", the object ID parameter should be valid!' );
			}

			// ** Note that the Parent param is also used for data inheritance in Create mode, but also to
			// determine the parent object the user is currently facing, such as CheckIn operation for
			// an Article object that is placed on a Layout Module. This is needed to know to disable properties
			// on the workflow dialogs and to force setting Brand/Issue/Edition properties.
			if( $action != 'Create' && $templateId ) { // **
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'GetDialog service: '.
					'When the Action parameter is other than "Create", Template should be nil!' );
			}
		}
	}

	/**
	 * Get workflow dialog definition for a given publication, object type and action.
	 * Optionally request for all object meta data, publication hierarchy and route options.
	 *
	 * @param string $user          User ID. Mandatory.
	 * @param string $action        Workflow ActionType.
	 * @param array  $metaData      Array of MetaDataValue object.
	 * @param array  $targetsFromReq Array of Targets
	 * @param boolean $reqDialog    Asks to populate Dialog element at response.
	 * @param boolean $reqPub       Asks to populate Publications element at response.
	 * @param boolean $reqMetaData  Asks to populate MetaData element at response. (Do not confuse with Dialog->MetaData.)
	 * @param boolean $reqStates    Asks to populate GetStatesResponse element at response.
	 * @param boolean $reqTargets   [v6.0] Indicates if client supports the Targets complex widget (that hold multiple issues/editions).
	 * @param int $defaultDossier   [v7.0] Dossier ID.  Tells if client can draw the Dossier prop. Nil when not. (BZ#10526)
	 * @param string $parentId      [v7.0] Parent ID.   Used for Create dialog only. (BZ#15357). See WSDL for more details.
	 * @param string $templateId    [v7.0] Template ID. Used for Create dialog only. (BZ#14103). See WSDL for more details.
	 * @param array $areas 		    [v8.0] 'Workflow' or 'Trash'. Can only set either one in the array.
	 * @param bool $multipleObjects [v9.2] Indicate whether the dialog is for single object or multiple objects properties. Default false.
	 * @throws BizException
	 * @return array of Dialog      Dialog definition to show user. See BizDataClasses {@link: Dialog}
	 */
	public static function getDialog( $user, $action, $metaData, &$targetsFromReq,
	                                  $reqDialog, $reqPub, $reqMetaData, $reqStates, $reqTargets,
	                                  $defaultDossier=null, $parentId=null, $templateId=null, $areas=null,
	                                  $multipleObjects=false )
	{
		$objId = self::getPropertyFromMetaData( 'ID', $metaData, 0 );
		if( isset($metaData['IDs']->PropertyValues[0]->Value) ) {
			$objIds = array();
			$propValues = $metaData['IDs']->PropertyValues;
			foreach( $propValues as $propValue ) {
				$objIds[] = $propValue->Value;
			}
		} else {
			$objIds = array();
		}
		$pub = self::getPropertyFromMetaData( 'Publication', $metaData, 0 );
		$iss = self::getPropertyFromMetaData( 'Issue', $metaData, 0 );
		$catId = self::getPropertyFromMetaData( 'Category', $metaData, 0 );
		$statusId = self::getPropertyFromMetaData( 'State', $metaData, 0 );
		$objType = self::getPropertyFromMetaData( 'Type', $metaData, 0 );
		$routeTo = self::getPropertyFromMetaData( 'RouteTo', $metaData, null );

		$retVal = self::getDefaultPublishDialog(); // collect response data

		if( ($action != 'Preview' && $action != 'SetProperties' ) &&  !is_null($areas) && in_array('Trash',$areas) ){
			throw new BizException( 'ERR_AUTHORIZATION', 'Client', 'GetDialog service: When the object is from Trash area, only Preview and Set Properties are allowed.');
		}
		// If the action is for publishing of a dossier go a different route. These are implemented by plugins directly
		// If not implemented by any plugin return a default dialog
		if($action == 'PublishDossier' || $action == "UnPublishDossier" || $action == "UpdateDossier") {
			return self::getPublishDialog($objId, $iss, $action);
		}

		// The SetPublishProperties is handled differently since it is returned
		// by the publish connectors
		if ( $action == 'SetPublishProperties' ) {
			$retVal = self::getSetPublishPropertiesDialog($objId, $iss, $reqMetaData);
			return $retVal;
		}

		self::getDialogBasicParamsValidation( $metaData, $multipleObjects, $action, $targetsFromReq, $defaultDossier,
			$parentId, $templateId, $areas, $objId, $objIds );

		// Tricky assumptions; When $objId is given it is must be for a non-Create dialog.
		// But, when $pub is given too, assumed is that the dialog is about to get re-drawn! Else initial draw!
		// In other cases, it must be a Create dialog for which we can not tell if we are
		// in initial- or redraw mode! This is because clients can pass the active* brand on object creations.
		// * That is the brand user is working in, like currently selected in Search results.
		$redrawNonCreate = ($objId && $pub) ||
			// Note that in multisetprops mode the $objId is not set and so we determine a redraw as follows:
			($multipleObjects && count( $metaData ) > 3 ) ;
		// L> getDialogBasicParamsValidation checks for presence of IDs, Type and Publication

		// Get the object, the parent object (typically a Layout or Dossier) and the template object.
		// (If $multipleObjects == true, none of the three objects below will be retrieved.)
		$obj = self::getObjectOrAlien( $user, $objId, $areas );
		$parentObj = self::getObjectOrAlien( $user, $parentId, $areas );
		$templateObj = self::getObjectOrAlien( $user, $templateId, $areas );

		// Check whether there is brand change on dialog. (BZ#31415)
		$redrawOnPub = false;
		if( $obj && $pub && isset($obj->MetaData->BasicMetaData->Publication->Id) && $obj->MetaData->BasicMetaData->Publication->Id != $pub ) {
			$redrawOnPub = true;
		}

		// Repair CS hack: CS requests for 'NotSupported' type when it does not know the object type. (Tested with CSv8.0.1)
		if( $objType == 'NotSupported' && $obj && isset($obj->MetaData->BasicMetaData->Type)) {
			$objType = $obj->MetaData->BasicMetaData->Type;
		}

		// Check whether template type corresponds with object type. (BZ#21002)
		$objectType = ($obj && isset($obj->MetaData->BasicMetaData->Type)) ? $obj->MetaData->BasicMetaData->Type : $objType;
		if( $templateObj ) {
			$templateType = $templateObj->MetaData->BasicMetaData->Type;
			if( !self::isTemplateTypeCompatibleWithObjectType( $templateType, $objectType ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client',
					'Template type "'.$templateType.'" does not correspond with object type "'.$objectType.'".' );
			}
		}

		// Check if the given Publication param (user selecting a Brand or Overrule Issue at the dialog)
		// does match with the object targets. If not, we have to 'forget' the object targets.
		// Note that SOAP clients cache the user's object target selections because the GetDialog service
		// does not offer a way to roundtrip all that. But, whenever another Brand (or overrule Issue) is
		// selected, the SOAP clients have the clear that cache and respect the object targets. So, here
		// the server has to make sure that those object targets get cleared. Later on, we pick the
		// current issue of the default channel to let SOAP clients pre-select that. (BZ#16713)

		// @since v8.0, the above statement about "GetDialog service does not offer a way to roundtrip user's
		// object target selection" is no longer valid. v8 enables to roundtrip user's selection of object
		// target via getDialog2 service request. The user selected target is sent via getDialog2 request ($targetsFromReq).
		// So here, server will first detect whether there's any Targets given in the request ($targetsFromReq),
		// if there's none, only server will fall back to object->Targets (this is for v7 compability).

		// $v8Client is client that supports getDialog2 service without the need to map from getDialog to getDialog2 service.
		// This is differentiate by the Targets sent in the request: Only getDialog2 has 'Targets' in the request
		// (getDialog2->Targets) which is an array, and only v8 client can support getDialog2 service.
		$v8Client = is_array($targetsFromReq);
		$targets = $targetsFromReq ? $targetsFromReq : ( $obj && !empty($obj->Targets) ? $obj->Targets : null );
		$workflowMatchesPubParam = true;
		$objPubId='';
		if( $pub && $targets ){
			require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
			$firstIssueId = $targets[0]->Issue->Id;
			$firstIssueRow = DBIssue::getIssue( $firstIssueId ); // just pick first target (assuming integrity)
			$userIssueRow = ($iss > 0) ? DBIssue::getIssue( $iss ) : null;
			if( $firstIssueRow['overrulepub'] || ($userIssueRow && $userIssueRow['overrulepub']) ) {
				$workflowMatchesPubParam = ($firstIssueRow['publication'] == $pub && $firstIssueId == $iss );
			} else {
				$workflowMatchesPubParam = ($firstIssueRow['publication'] == $pub);
			}

			// Since v8, by just checking whether the issue's pub is equivalent with $pub sent in via the
			// getDialog request is not enough for server to indicate whether $obj->Targets needs to be adjusted later on.
			// This is because v8 server allows round-tripping user's selection. At one point,
			// the getDialog2 request might be sending in a new brand (user changed the Brand in dialog box)
			// and a round-tripped targets (which is valid within the changed Brand).
			// For this case, the $targets[x]->Issue 's Brand and the sent in Brand will be equal,
			// and server will think that the Brand is not changed not will not adjust $obj->Targets which is wrong(!).
			// So here, server still need to check the DB object's Brand(original Brand) with the sent in Brand($pub)
			// to make sure which Brand's issue to go into $obj->Targets later on.
			if( $obj && isset($obj->MetaData->BasicMetaData->Publication->Id)){
				$objPubId = $obj->MetaData->BasicMetaData->Publication->Id;
				if( $v8Client && $objPubId != $pub ){ // indicates user has changed brand
					$workflowMatchesPubParam = false; // indicates $obj->Targets need to be adjusted
				}
			}
		}

		// Resolve Brand/Category params (when not given by user) from object, parent or template.
		// On same way, determine default Issue to pre-select for new objects (taken from parent object).
		if( $multipleObjects ) {
			require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
			// User cannot change Brand for multi-setproperties, so it is always(whether it is a initial draw or a redraw)
			// safe to get the Brand from database.
			// All objects hold the same Publication for multi-setproperties, so safe to get from the first object's Brand.
			$pub = DBObject::getObjectPublicationId( $objIds[0] );
		} else {
			self::fixAndResolvePubIssSecFromObjects( $obj, $pub, $iss, $catId, $action, $parentObj, $templateObj, $defaultDossier, $user );
		}

		// For Create action, create new (temporary) object and derive MetaData from template object.
		self::createNewObjectWithInheritedMetaData( $obj, $objType, $action, $templateObj );

		// Collect all targets issue ids; They need to be resolved, even when non-Active!
		$extraIssueIds = array();
		if( $obj && !empty($targets) ) {
			foreach( $targets as $target ) {
				$extraIssueIds[$target->Issue->Id] = true;
			}
		}

		$pubInfo = null;
		$prunedPubInfo = null;
		$issName = '';
		$catName = '';
		require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
		if( !$multipleObjects ) {
			// Get publication info
			$pubInfos = BizPublication::getPublications( $user, 'browse', $pub, $extraIssueIds );
			$pubInfo = (count($pubInfos) == 1) ? $pubInfos[0] : null;
			// When issue and/or section are not selected by user (first time drawing the dialog)
			// or when they are not resolved yet (when creating new objects without template use),
			// we pick a default here (and/or repair badly chosen values).
			if( $pubInfo ) { // Typically NOT for 'Query' action (BZ#16988)
				self::fixAndResolveIssueSectionFromPubInfo( $pubInfo, $iss, $issName, $catId, $catName, $action, $redrawNonCreate, $objectType, $parentObj, $objId );
				$prunedPubInfo = self::prunePublicationInfo( $pubInfo, $iss );
			}
		}

		// At this point, the $iss belongs to the $pub, since it is resolved at fixAndResolveIssueSectionFromPubInfo (above).
		// This is needed to determine the 'initial' Issue in case user is changing Brand (or Overrule Issue)
		// or when he/she is about to Create (or Copy) an object.
		if( self::objectIsCreated( $action) || // BZ#16971
			!$workflowMatchesPubParam ) { // BZ#16937, BZ#16713
			require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';

			$obj->Targets = ($iss > 0) ? array(BizTarget::buildTargetFromIssueId( $iss )) : null;
		}

		// Get PropertyInfos, PropertyUsages
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		// PropertyInfos
		$props = BizProperty::getProperties( $pub, $objType );
		// PropertyUsages
		$wiwiwUsages = null;
		$usages = BizProperty::getPropertyUsages( $pub, $objType, $action, true, false, null, $wiwiwUsages, $multipleObjects );

		// Get Objects' properties if handling multi-set properties dialog.
		$objectsProps = array();
		$isOverruleIssue = false;
		if( $multipleObjects ) {
			require_once BASEDIR .'/server/bizclasses/BizObject.class.php';
			$requestProps = array_keys( $usages );
			$objectsProps = BizObject::getMultipleObjectsPropertiesByObjectIds( $user, $objIds, $requestProps );

			// In multi-set properties, all issue related data (issueId, targets, relational targets) are ignored
			// to avoid complexity (since it involves many objects that might targeted to different issue(s)).
			// However, the issue is needed when the dialog is dealing with multi-set properties for overrule issue.
			// Below, check if multi-set properties is dealing with overrule issue and set the flag accordingly.
			// and enrich the issueId and categoryId(that should be from overrule issueId and categoryId) when it is not
			// yet filled (Typically happens for initial draw).
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			if( !$iss && !$catId ) { // Initial draw.
				$firstObjProp = reset( $objectsProps );
				$issues = explode( ',', $firstObjProp['IssueIds'] );
				if( DBIssue::isOverruleIssue( $issues[0] )) { // When it is Overruleissue, it should contain only 1 issue.
					$iss = $issues[0];
					$catId = $firstObjProp['CategoryId'];
					$isOverruleIssue = true;
				}
			} else { // Re-draw dialog where the issue is given by the client.
				// Checks if the dialog is dealing with overrule issue.
				$isOverruleIssue = DBIssue::isOverruleIssue( $iss ) ? true : false;
			}
		}

		// Get statuses and resolve status id
		if( $pub ) {
			$states = self::getStatesExtended( $user, null, // don't use objId!
				new Publication($pub), new Issue($iss), new Category($catId), $objType );
		} else if( $objId ) {
			$states = self::getStatesExtended( $user, $objId, null, null, null, $objType );
		} else { // Typically happens for 'Query' action (BZ#16988)
			$states = null;
		}

		// Retrieve statusId and RouteTo.
		if( $multipleObjects ) { // When handling multi-setproperties
			$mixedStatusIds = false;
			$uniqueStatusId = array();
			$mixedRouteTo = false;
			$uniqueRouteTo = array();
			foreach( $objectsProps as $objectProp /* => $objectValue */ ) {

				if( isset( $objectProp['StateId'] )) {
					// Searching for StateId.
					if( count( $uniqueStatusId ) == 0 ) {
						$uniqueStatusId[] = $objectProp['StateId'];
					}

					if( !in_array( $objectProp['StateId'], $uniqueStatusId )) { // Detected not all objects hold the same status.
						$mixedStatusIds = true;
					}
				}

				if( isset( $objectProp['RouteTo'] )) {
					// Searching for RouteTo.
					if( count( $uniqueRouteTo ) == 0 ) {
						$uniqueRouteTo[] = $objectProp['RouteTo'];
					}

					if( !in_array( $objectProp['RouteTo'], $uniqueRouteTo )) { // Detected not all objects hold the same RouteTo user.
						$mixedRouteTo = true;
					}
				}

				if( ( $mixedStatusIds || !isset( $objectProp['StateId'] ) ) &&
					( $mixedRouteTo || !isset( $objectProp['RouteTo'] )) ) {
					break;
				}
			}

			// StatusId
			if( !$statusId ) { // Only get the statusId from database when user did not select status.
				$statusId = $mixedStatusIds ? 0 : $uniqueStatusId[0];
			}
			$oriStateId = $statusId;

			// RouteTo
			if( is_null( $routeTo )) { // Only get the RouteTo from database when user did not select RouteTo.
				// Can only get RouteTo when all the objects share the same RouteTo user/group, otherwise it is mixed values.
				if( !$mixedRouteTo ) {
					$routeTo = isset( $uniqueRouteTo[0] ) ? $uniqueRouteTo[0] : '';
				}
			}

			// Access Rights
			// For normal workflow issue objects, the multiple objects (in the multi-set properties) might share
			// different issues, and therefore in the access rights checking, we don't pass in Issue($iss=null).
			// However, for overruleIssue, $iss has to be passed in as the overruleIssue is the 'acting Brand',
			// and when it is an overruleissue, all objects will share the same issue.
			$issAccess = $isOverruleIssue ? $iss : null;
			$rights = self::determineAccessRightsForMultipleObjects( $user, $pub, $issAccess, $catId, $objType, $statusId, $objIds );
		} else { // when handling with one object's properties.
			// StatusId
			$oriStateId = isset($obj->MetaData->WorkflowMetaData->State->Id) ? $obj->MetaData->WorkflowMetaData->State->Id : 0;
			$statusId = $states ? self::fixAndResolveStatusId( $obj, $statusId, $states->States ) : 0;

			// RouteTo
			$routeTo = ($obj && isset($obj->MetaData->WorkflowMetaData->RouteTo)) ? $obj->MetaData->WorkflowMetaData->RouteTo : '';
			$contentSource = ($obj && isset($obj->MetaData->BasicMetaData->ContentSource)) ? $obj->MetaData->BasicMetaData->ContentSource : '';
			$documentId = ($obj && isset($obj->MetaData->BasicMetaData->DocumentID)) ? $obj->MetaData->BasicMetaData->DocumentID : '';
            $accessObjId = ( self::objectIsCreated( $action ) ) ? null : $objId ; // Don't use object id for access checking (e.g. needed to copy aliens; EN-85894)
			$routeToForRights = $accessObjId ? $routeTo : $user;
			// This is quite a hack. The routeTo user is determined later on based on for example the state. To
			// determine the state the rights are needed. If a new object is created in personal state then the user has full
			// access. Personal state means that the $statusId = -1 and the routeTo user of an object is the acting user.
			// At this moment the $routeTo user is not set for new objects. In that case we pass the acting user as the
			// routeTo user. If the state = -1 then the user will get full access.

			// Access Rights.
			$rights = self::determineAccessRightsForObject( $user,
				$pub, $iss, $catId, $objType, $statusId,
				$accessObjId, $contentSource, $documentId, $routeToForRights );
		}

		// Check access right "Change Status" and "Change Status Forward"
		// When access right, "Change Status" disabled and "Change Status Forward" enabled,
		// remove state other than current state and next state.
        if( $statusId && !self::objectIsCreated( $action ) ) { // Only filter status for existing object, new object will include all statuses.
			$changeStatusRights = ($rights['hasRights']['Change_Status']) ? true : false;
			$changeStatusForwardRights = ($rights['hasRights']['Change_Status_Forward']) ? true : false;
			if( !$changeStatusRights && $changeStatusForwardRights ) {
				$nextStateId = DBWorkflow::nextState($oriStateId);
				foreach( $states->States as $key => $state ) {
					// Remove state when it is not current and next state.
					if( $state->Id != $nextStateId && $state->Id != $statusId && $state->Id != $oriStateId ) {
						unset($states->States[$key]);
					}
				}
			}
		}

		// Set default values for issue and section/category, only for ID/IC v7.0 clients
		$props['Issue']->DefaultValue = $issName;
		$props['Category']->DefaultValue = $catName;

		// Automatic Routing feature; determine if/how the Route To property must be set
		$routeTo = self::determineRouteTo( $redrawNonCreate, $routeTo, $action, $statusId, $states );

		$isPlaced = false;
		$relatedTargets = array();
		if( !$multipleObjects ) {
			$parentObjType = $parentObj ? $parentObj->MetaData->BasicMetaData->Type : '';

			$isContained = false;
			$relatedTargets = self::getRelatedTargetsInfos( $user, $objId, $parentId, $parentObjType, $action, $isPlaced, $isContained );
			// BZ#19537 - For task type object, the target will inherit the parent target
			if( $action == 'Create' && ( $objType == 'Task' || $objType == 'Layout' || $objType == 'PublishForm' ) && $parentObjType == 'Dossier' && $isContained ) {
				if( $objType == 'Task' ) {
					$obj->Targets = $parentObj->Targets;
					$resetRelatedTargets = array();
					foreach ($relatedTargets as $relatedTarget) { // Task don't get relational targets
						$relatedTarget->Targets = array();
						$resetRelatedTargets[] = $relatedTarget;
					}
					$relatedTargets = $resetRelatedTargets;
				} elseif( $objType == 'Layout' || $objType == 'PublishForm' ) {
					// BZ#30404 - Set the default object target as the first target of parent object
					if( $parentObj->Targets ) {
						$obj->Targets[] = $parentObj->Targets[0]; // Get the first target of parent object
					}
				}
			}
			if( count($relatedTargets) > 0 ) {
				self::addRelatedTargetsPropertyUsage( $props, $usages );
			}

			self::fixTargetPropertyUsage( $reqTargets == 'true', $usages, $isPlaced,
				empty($obj->Targets) ? array() : $obj->Targets, $objType ); // Targets are typically NOT set for 'Query' action (BZ#16988)
		}

		self::fixDossierPropertyUsage( $action, $objType, $defaultDossier, $usages );

		self::handleDossiersForDialog( $pub, $iss, $catId, $rights, $defaultDossier, $user, $usages, $retVal, $props );

		if( $multipleObjects ) {
			// Eliminate all the unwanted properties for multi-set properties.
			self::fixForMultipleObjectsPropertyUsage( $usages, $isOverruleIssue );
		}

		// Build dialog
		$pubs = BizPublication::getPublications( $user, 'flat' );

		self::addDisabledRouteTo( $routeTo, $states );

		$props = self::fillValueListIntoProps( $user, $props, $pubs, $pub, $states, $prunedPubInfo );

		if( $reqDialog == 'true' ) { // requested for dialog definition?
			$retVal['Dialog'] = self::buildDialog( $obj, $objType, $pub, $iss, $catId,
				$statusId, $action, $routeTo, $parentObj,
				$props, $usages, $rights, $isPlaced, $reqTargets == 'true', $metaData,
				$redrawOnPub, $targets, $objectsProps, $multipleObjects );
		}

		if( $action != 'Query' && // The below makes no sense for 'Query' actions (BZ#16988)
			!$multipleObjects ) {
			if( $reqPub == 'true' ) { // requested for publication browse hierarchy?
				$retVal['Publications'] = $pubs;
				if( $pubInfo ) {
					$retVal['PublicationInfo'] = $prunedPubInfo;
					$retVal['PubChannels'] = $retVal['PublicationInfo']->PubChannels;
				}
			}

			if( $reqMetaData == 'true' ) { // requested for actual object meta data?
				$retVal['MetaData'] = $obj->MetaData;
			}

			$retVal['Targets'] = $obj->Targets; // always use to obj->Targets
			// but in v8, server has to re-decide whether to use obj->Target or Request->Targets($targetsFromReq) in getDialogResp->Targets
			if( $v8Client && $objPubId ){
				$overruleIssue = false;
				if ( $iss ) {
					require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
					$issueRow = DBIssue::getIssue( $iss );
					if ( $issueRow && $issueRow['overrulepub'] ) {
						$overruleIssue = true;
					}
				}

				if( $objPubId == $pub &&
					$targetsFromReq != $obj->Targets && // indicates that the user has added new target(s) within same brand
					!$overruleIssue ) { // The exception is overruled issues (to solve BZ#30003)
					$retVal['Targets'] = $targetsFromReq; // so respect the targets from request
				}

				// user has changed Brand, so respect obj->Targets which has been adjusted by the server based on the new Brand selected
				if( $objPubId != $pub ){
					$retVal['Targets'] = $obj->Targets;
				}
			}
			$retVal['RelatedTargets'] = $relatedTargets;

			if( $reqStates == 'true' && $states ) { // requested for possible statusses and route-to info?
				// For v7 client compability, we still need to have ['GetStatesResponse'] in getDialog response
				$retVal['GetStatesResponse'] = $states;
			}
		}

		//Only disable 'Set Properties' dialog when the requested area is in 'Trash' area.
		if( $areas && in_array('Trash',$areas) ){
			if( $retVal['Dialog'] ) foreach( $retVal['Dialog']->Tabs as $tab ) {
				foreach( $tab->Widgets as $widget ) {
					$widget->PropertyUsage->Editable = false;
				}
			}
		}

		// Re-construct for getDialog2 structure.
		if( $objId ) {
			if( !isset( $metaData['ID']->PropertyValues[0] )) {
				$metaData['ID']->PropertyValues[0] = new PropertyValue();
			}
			$metaData['ID']->PropertyValues[0]->Value = $objId;
		}

		if( $pub ) {
			if( !isset( $metaData['Publication']->PropertyValues[0] )) {
				$metaData['Publication']->PropertyValues[0] = new PropertyValue();
			}
			$metaData['Publication']->PropertyValues[0]->Value = $pub;
		}

		if( $iss ) {
			if( !isset( $metaData['Issue']->PropertyValues[0] )) {
				$metaData['Issue']->PropertyValues[0] = new PropertyValue();
			}
			$metaData['Issue']->PropertyValues[0]->Value = $iss;
		}

		if( $catId ) {
			if( !isset( $metaData['Category']->PropertyValues[0] ) ) {
				$metaData['Category']->PropertyValues[0] = new PropertyValue();
			}
			$metaData['Category']->PropertyValues[0]->Value = $catId;
		}

		if( $statusId ) {
			if( !isset( $metaData['State']->PropertyValues[0] ) ) {
				$metaData['State']->PropertyValues[0] = new PropertyValue();
			}
			$metaData['State']->PropertyValues[0]->Value = $statusId;
		}

		if( $objType ) {
			if( !isset( $metaData['Type']->PropertyValues[0] ) ) {
				$metaData['Type']->PropertyValues[0] = new PropertyValue();
			}
			$metaData['Type']->PropertyValues[0]->Value = $objType;
		}

		return $retVal;
	}

	/**
	 * Retrieves a property value from a metadata structure. If no value is set the property value is initialized.
	 *
	 * @param string $property
	 * @param MetaDataValue[] $metaData
	 * @param mixed $initValue Initialize value
	 * @return mixed
	 */
	static private function getPropertyFromMetaData( $property, $metaData, $initValue )
	{
		$value = $initValue;
		if( isset( $metaData[ $property ]->PropertyValues[0]->Value ) ) {
			$value = $metaData[ $property ]->PropertyValues[0]->Value;
		}

		return $value;
	}

	/**
	 * Enrich the widget's SuggestionProvider when the TermEntity of the widget is supported by the Suggestion provider.
	 *
	 * @since v9.1
	 * @param string $objId The ID of the Object containing the TermEntity.
	 * @param int $channelId The publication channel Id to retrieve SuggestionProvider set at channel level.
	 * @param Dialog $dialog The dialog to be enriched.
	 * @return bool Whether or not the SuggestionProvider is set in any of the widget that has TermEntity.
	 */
	protected static function enrichDialogWidgetsSuggestionProviders(
		/** @noinspection PhpUnusedParameterInspection */ $objId,
		                                                  $channelId, $dialog )
	{
		require_once BASEDIR . '/server/bizclasses/BizAutoSuggest.class.php';
		$suggestionProvider = DBChannel::getSuggestionProviderByChannelId( $channelId );
		$suggestionConnector = BizServerPlugin::searchConnectorByClassName( $suggestionProvider.'_SuggestionProvider' );

		$foundProvider = false;
		if( $suggestionProvider ) {
			if( isset( $dialog->Tabs ) ) foreach( $dialog->Tabs as $tab ) {
				if( $tab->Widgets ) foreach( $tab->Widgets as $widget ) {
					$suggestionEntity = $widget->PropertyInfo->SuggestionEntity;
					if( $suggestionEntity ) {
						$canHandleEntity = BizServerPlugin::runConnector( $suggestionConnector, 'canHandleEntity',
							array( $suggestionEntity ) );
						if( $canHandleEntity ) {
							$widget->PropertyInfo->SuggestionProvider = $suggestionProvider;
							$foundProvider = true;
						}
					}
				}
			}
		}
		return $foundProvider;
	}

	/**
	 * @since v8.0, server is introduced with new structure of value list
	 * which is called list of propertyValues.
	 * This functions will populate the propertyValues for several properties
	 * like 'Publication', 'Category','State' and 'RouteTo'.
	 * This is for the client to show the lists of properties with their
	 * values in Id<->Name pair.
	 *
	 * @param string $user user shortname.
	 * @param array $props PropertyInfo objects.
	 * @param array $pubs List of Publication objects.
	 * @param int $pubId DB Id of publication. Used to getSections()
	 * @param WflGetStatesResponse $statusesResp. Statuses for $objType
	 * @param PublicationInfo $prunedPubInfo PublicationInfo that has been adjusted for drawing the dialog.
	 * @return array $props Adjusted PropertyInfo objects.
	 */
	protected static function fillValueListIntoProps( $user, $props, $pubs, $pubId, $statusesResp, $prunedPubInfo )
	{
		require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
		$props['Publication']->PropertyValues = array();
		if($pubs) foreach( $pubs as $pub ){
			$props['Publication']->PropertyValues[]			= new PropertyValue( $pub->Id, $pub->Name);
		}

		$props['Category']->PropertyValues = array();
		if( $pubId ) {
			$sections = BizPublication::getSections( $user, $pubId );
			if( $sections ) foreach( $sections as $section ){
				$props['Category']->PropertyValues[]			= new PropertyValue( $section->Id, $section->Name );
			}
		}

		$props['State']->PropertyValues = array();
		if( $statusesResp ) foreach( $statusesResp->States as $state ){
			$props['State']->PropertyValues[]				= new PropertyValue( $state->Id, $state->Name );
		}

		$props['RouteTo']->PropertyValues = array();
		if( $statusesResp ) {
			foreach( $statusesResp->RouteToGroups as $routeToGroup ){
				$props['RouteTo']->PropertyValues[]			= new PropertyValue( $routeToGroup->Name, $routeToGroup->Name, 'UserGroup' );
			}
			foreach( $statusesResp->RouteToUsers as $routeToUser ){
				$props['RouteTo']->PropertyValues[]			= new PropertyValue( $routeToUser->UserID, $routeToUser->FullName, 'User' );
			}
		}
		$props['PubChannels']->PropertyValues = array();
		if( $prunedPubInfo ) {
			if( $prunedPubInfo->PubChannels ) foreach( $prunedPubInfo->PubChannels as $pubChannel ) {
				$props['PubChannels']->PropertyValues[]     = new PropertyValue( $pubChannel->Id, $pubChannel->Name );
			}
		}
		return $props;
	}

	/**
	 * Retrieves an object from DB or from Content Source (alien object).
	 *
	 * @param string $user
	 * @param string $objId
	 * @param array $areas 'Workflow' or 'Trash.
	 * @return Object. Returns null when object was not found or objId was not specified.
	 */
	protected static function getObjectOrAlien( $user, $objId, $areas=null )
	{
		if( $objId ) {
			// If alien, pass to content source (and clear objId to get dialog based on type), otherwise get from our DB
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			if( BizContentSource::isAlienObject( $objId ) ) {
				$obj = BizContentSource::getAlienObject( $objId, 'none', false /*lock*/ );
				$objId = null; // don't use alient object id below (so do things based on type)
			} else {
				// Get object meta data (tree structure)
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				$obj = BizObject::getObject( $objId, $user, false /*lock*/, 'none', array('MetaData','Targets','Relations'), null/* haveVersion */, true/*checkRights*/, $areas );
			}
		} else {
			$obj = null;
		}
		return $obj;
	}

	/**
	 * For new objects that about to get created, this function inherits MetaData from
	 * given template.
	 * Nothing is done to existing objects or in context of other actions than Create.
	 * If obj is null a new Object is created.
	 *
	 * @param Object $obj         Object to enrich with inherited data. Should be nil to trigger inheritance.
	 * @param string $action      Dialog action. Should be Create to trigger inheritance.
	 * @param string $objType
	 * @param Object $templateObj Template object to inherit from
	 */
	protected static function createNewObjectWithInheritedMetaData( &$obj, $objType, $action, $templateObj )
	{
		if( $action == 'Create' && is_null($obj) ) {
			$obj = new Object();

			if( is_object($templateObj) ) {
				// Take over some fields when instantiating object from template. (BZ#14103)
				// Here we transform (a copy of) the template meta data to get ready for the new object.
				$obj->MetaData = clone $templateObj->MetaData;
				$inherit = true;
				$preserveBasicMD = array_flip( array( 'Publication', 'Category' ) );
			} else {
				$inherit = false;
				$preserveBasicMD = array();
			}
		} else if( $action == 'CopyTo' ) { // BZ#16971
			$inherit = true;
			$preserveBasicMD = array_flip( array( 'Publication', 'Category', 'Name' ) );
		} else {
			$inherit = false;
			$preserveBasicMD = array();
		}

		if( $inherit ) {
			// Clear the BasicMetaData and WorkflowMetaData, which are copied too much
			$basMD = $obj->MetaData->BasicMetaData;
			foreach( array_keys(get_class_vars('BasicMetaData')) as $prop ) {
				if( !isset($preserveBasicMD[$prop]) ) { // preserve Brand and Category! (and Name too for CopyTo only)
					$basMD->$prop = (is_object($basMD->$prop) || is_null($basMD->$prop)) ? null : ''; // clear!
				}
			}
			$basMD->Type = $objType;
			$wflMD = $obj->MetaData->WorkflowMetaData;
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			foreach( array_keys(get_class_vars('WorkflowMetaData')) as $prop ) {
				$propType = BizProperty::getStandardPropertyType( $prop );
				if( is_object($wflMD->$prop) || is_null($wflMD->$prop) || $propType == 'int' || $propType == 'datetime' ) {
					$wflMD->$prop = null; // Clear, set property to null to avoid service validation error
				} else {
					$wflMD->$prop = ''; // Clear, set string type property to empty string
				}
			}
		}
	}

	/**
	 * Checks and fixes the Brand/Issue/Category parameters.
	 * Assumed is that the params are given (=> Create mode) or the object is given (=> Save mode),
	 * or both (=> Save with changed location).
	 * Empty params are zerofied. No Brand means no Issue and no Category.
	 * When zero/empty params, Brand/Issue/Category are derived from the object ($obj), parent or template.
	 *
	 * Note that the Issue is resolved for clients that are not multi-target-aware, such as ID/IC.
	 * Even when there is no issue assigned to an existing object, there is still a need to have one
	 * set to make those clients happy. For those cases, the default issue is derived from the Brand,
	 * which is done later, at the fixAndResolveIssueSectionFromPubInfo() function.
	 *
	 * The Brand/Issue/Category are derived from the first best found item, at the following order:
	 * 0. parent module object
	 * 1. object context / user selection ( => respecting $pub and $sec )
	 * 2. existing object
	 * 3. parent object in Create mode
	 * 4. template object in Create mode (=> only done for Brand/Category, NOT Issue)
	 * 5. initiate object from template object from within a dossier, Create mode.
	 *
	 * @param Object $obj Pass null when creating new object.
	 * @param mixed $pub Brand ID, currently selected by user at workflow dialog.
	 * @param mixed $iss Issue ID,         " "
	 * @param mixed $sec Category ID,      " "
	 * @param string $action Workflow ActionType.
	 * @param Object $parentObj Parent object. Typically Layout or Dossier. Null when not provided.
	 * @param Object $templateObj Template object. Null when not provided.
	 * @param int $defaultDossier objectId of the dossier currently working in.
	 * @param string $user short username
	 */
	protected static function fixAndResolvePubIssSecFromObjects( $obj, &$pub, &$iss, &$sec, $action, $parentObj, $templateObj, $defaultDossier, $user)
	{
		// Fix parameters
		$pub = intval($pub);
		$iss = intval($iss);
		$sec = intval($sec);
		if( !$pub ) { $iss = 0; $sec = 0; } // robustness; clear other when pub not given to avoid mixture

		// Determine if is parent is a module
		$parentObjType = $parentObj ? $parentObj->MetaData->BasicMetaData->Type : '';
		$parentIsModule = ($parentObjType == 'LayoutModule' || $parentObjType == 'LayoutModuleTemplate');

		// Resolve Brand*
		if( $parentIsModule ) { // [0]
			$pub = $parentObj->MetaData->BasicMetaData->Publication->Id;
		} else if( $pub ) { // [1]
			// nothing to do
		} else if( $obj && $action != 'Create' ) { // [2]
			$pub = $obj->MetaData->BasicMetaData->Publication->Id;
		} else if( $parentObj && $action == 'Create' ) { // [3]
			$pub = $parentObj->MetaData->BasicMetaData->Publication->Id;
		} else if( $templateObj && $action == 'Create' ) { // [4]
			$pub = $templateObj->MetaData->BasicMetaData->Publication->Id;
		}

		// Resolve Category*
		if( $parentIsModule ) { // [0]
			$sec = $parentObj->MetaData->BasicMetaData->Category->Id;
		} else if( $sec ) { // [1]
			// nothing to do
		} else if( $obj && $action != 'Create' ) { // [2]
			$sec = $obj->MetaData->BasicMetaData->Category->Id;
		} else if( $parentObj && $action == 'Create' ) { // [3]
			$sec = $parentObj->MetaData->BasicMetaData->Category->Id;
		} else if( $templateObj && $action == 'Create' ) { // [4]
			$sec = $templateObj->MetaData->BasicMetaData->Category->Id;
		}

		// * Note: Brand and Category are resolved separately; For example, there can be a Create action
		//   for which a parent is given (e.g. create article from layout), but also the 'current' Brand
		//   the user is working in (e.g. taken from Search filter). The Brand must then be respected,
		//   but, the Category should be taken from the parent! We do not worry here if the two do not 'match'.
		//   That is, a Category that is defined for other Brand; That is fixed in fixAndResolveIssueSectionFromPubInfo
		//   function, which respects the Brand and fixes the Category.

		// Determine default Issue
		if( $parentIsModule ) { // [0]
			if( count($parentObj->Targets) > 0 ) {
				$iss = intval($parentObj->Targets[0]->Issue->Id);
			} else {
				if( $parentObj->Relations ) foreach( $parentObj->Relations as $relation ) { // BZ#32619 - Get the parent module first relation target issue
					if( $relation->Type == 'Contained' && $relation->Child == $parentObj->MetaData->BasicMetaData->ID ) {
						if( count($relation->Targets) > 0 ) {
							$iss = intval($relation->Targets[0]->Issue->Id);
						}
						break;
					}
				}
			}
		} else if( $iss ) { // [1]
			// nothing to do
		} else if( $obj && $action != 'Create'
			// BZ#20589
			// For 'CopyTo', assigning $iss from Target when $iss is empty might violate either of the following:
			// - Current Issue not being selected
			// - User's selection of Issue is not respected
			&& $action != 'CopyTo' // [2]
			// BZ#24178: (Only for CS) When user changes from overrule iss to normal pub within the same Brand, the obj iss (which is overrule iss) should not be respected anymore.
			&& !self::$avoidOverrulePreSelection ) {
			require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
			if( $obj->Targets ) {
				// Prefer that object issue, which is default for the brand
				$iss = BizTarget::getDefaultIssueId( $pub, $obj->Targets );
				// When issue does not match default, fall back at first object issue
				if( !$iss && count( $obj->Targets ) > 0 ) {
					$iss = intval($obj->Targets[0]->Issue->Id);
				}
			} else { // BZ#24883/28647 - Get default issueid from related targets, where object relation is contained/placed
				if( self::canBeIssuelessObject($obj->MetaData->BasicMetaData->Type) ||
					( $obj->MetaData->BasicMetaData->Type == 'PublishForm' ) ){ // Form only has Relational Target as it is always contained inside a dossier.
					foreach( $obj->Relations as $relation ) {
						if( $relation->Child == $obj->MetaData->BasicMetaData->ID && ($relation->Type == 'Contained' || $relation->Type == 'Placed')) {
							$iss = BizTarget::getDefaultIssueId( $pub, $relation->Targets );
							if( !$iss && count( $relation->Targets ) > 0 ) {
								$iss = intval($relation->Targets[0]->Issue->Id);
							}
							// Ignore overrule issues of parents/relations, because they might not apply to the
							// child objects.
							if( DBIssue::isOverruleIssue( $iss ) ) {
								$iss = 0;
								continue;
							}
							break;
						}
					}
				}
			}
		} else if( $parentObj && $action == 'Create' ) { // [3]
			if( $parentObjType != 'Dossier' ) { // BZ#18415 - Don't get default issue when parentobj type is Dossier
				require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
				$iss = BizTarget::getDefaultIssueId( $pub, $parentObj->Targets );
				// Fall back at first issue when none of the parent target issues is defined under default channel
				if( !$iss && count( $parentObj->Targets ) > 0 ) {
					$iss = intval($parentObj->Targets[0]->Issue->Id);
				}
			}
		} else if( $templateObj && $action == 'Create' && $defaultDossier === null  ) { // [4]
			// Take current issue (or else the first issue) of the same channel of the template BZ#17864
			// Do NOT take the default channel here; The template is intended for a certain channel in which
			// newly created objects should flow by default.
			if( count( $templateObj->Targets ) > 0 ) {
				require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
				$defaultChanRow = DBChannel::getChannel( $templateObj->Targets[0]->PubChannel->Id );
				$iss = !empty($defaultChanRow['currentissueid']) ? $defaultChanRow['currentissueid'] : null;
				if( !$iss ) {
					require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
					$issObj = DBAdmIssue::getFirstActiveChannelIssueObj( $templateObj->Targets[0]->PubChannel->Id );
					if( $issObj ) {
						$iss = $issObj->Id;
					}
				}
			}
		} else if ( $templateObj && $action == 'Create' && $defaultDossier != null  ){ // [5]
			foreach( $templateObj->Relations as $relation ) { // This can have a serious performance drawback. If a template is used in many dossiers it must
				// investigated if looping through all relations is really needed or if we can assume that the template and
				// default dossier have always a relation.
				if( $relation->Type == 'Contained' && $relation->Child == $templateObj->MetaData->BasicMetaData->ID && $relation->Parent == $defaultDossier ) {
					$templateParentObj = self::getObjectOrAlien( $user, $relation->Parent );
					if( count($templateParentObj->Targets) > 0 ) {
						$iss = $templateParentObj->Targets[0]->Issue->Id;	// Get the first target issue
						break;
					}
				}
			}
		}
	}

	/**
	 * Validates the $statusId (must be numeric) and checks if it exists in the possible options user can
	 * choose from ($states). If not, or $statusId is not given, it does a fallback; It takes the status of
	 * the object, or, when object is a template used to create new object, it takes the first of $states.
	 * When there is none, it clears the $statusId (set to zero). That means bad config.
	 *
	 * @param Object $obj
	 * @param int $statusId
	 * @param array $states List of State objects
	 * @return int status id
	 */
	protected static function fixAndResolveStatusId( $obj, $statusId, $states )
	{
		$statusId = intval($statusId);
		if( !$statusId && $obj && isset($obj->MetaData->WorkflowMetaData->State->Id) ) {
			// Fix: Moved this check from the far below to here, or else changing to another Brand
			//      for existing object (e.g. Dossier at Set Properties dialog) causes falling back
			//      to the objects's status, which is not valid for the new Brand, and so it does
			//      not get pre-selected at the dialog. (BZ#16713)
			$statusId = $obj->MetaData->WorkflowMetaData->State->Id;
		}
		if( $statusId ) {
			$sttFound = false;
			foreach( $states as $sttObj ) {
				if( $sttObj->Id == $statusId ) {
					$sttFound = true;
					break;
				}
			}
			if( !$sttFound ) {
				$statusId = 0; // given status does no longer match; clear to resolve below
			}
		}
		if( !$statusId ) {
			$statusId = isset($states[0]) ? $states[0]->Id : 0;
		}
		return $statusId;
	}

	/**
	 * Build a Dialog object with DialogTab(s) and DialogWidget(s) objects for the given object/location and action.
	 * The Dialog object specifies what widgets are configured; How the workflow dialogs should be drawn by clients.
	 *
	 * @param Object $obj
	 * @param string $objType Object type
	 * @param int $pub        Brand id
	 * @param int $iss        Issue id
	 * @param int $sec        Category id
	 * @param int $statusId   Status id
	 * @param string $action  Dialog action type
	 * @param string $routeTo Route To property to pre-select at dialog
	 * @param string $parentObj The parent object. Null when object has no parent.
	 * @param array $props    List of PropertyInfo objects
	 * @param array $usages   List of PropertyUsage objects
	 * @param array $rights Key-value array where key consists of 'hasRights','noRights', actions and its Object ids in the values.
	 * @param boolean $isPlaced
	 * @param boolean $reqTargets
	 * @param array $flatMD Array of MetaDataValue object.
	 * @param bool $redrawOnPub Tells if the dialog is about to get drawn after changing brand
	 * @param array $objTargets List of object targets.
	 * @param array $objectsProps List of ObjId(Key) and its properties values(Value). Needed when $multipleObjects is true.
	 * @param bool $multipleObjects Indicates whether the dialog is built for single object or multiple objects properties.
	 * @return Dialog
	 */
	protected static function buildDialog( $obj, $objType, $pub, $iss, $sec,
	                                       $statusId, $action, $routeTo, $parentObj,
	                                       array $props, array $usages, array $rights,
	                                       $isPlaced, $reqTargets, $flatMD, $redrawOnPub = false,
	                                       $objTargets, $objectsProps, $multipleObjects )
	{
		// Checks if there's 'MixedValues' for each property to be displayed on the dialog.
		$mixedValueProps = array();
		if( $multipleObjects ) {
			$mixedValueProps = self::getMixedValuesProperties( $usages, $objectsProps, $routeTo, $statusId, $sec  );
		}

		$readOnlyProperties = array();
		if( $multipleObjects ) {
			$readOnlyProperties = self::autoRoutingInMultipleObjects( $mixedValueProps, $objectsProps, $pub, $objType );
		}

		// Make fields read-only for which user has no edit rights
		$statusEditable = false;
		self::handlePropertiesEditableField( $usages, $flatMD, $parentObj, $action, $obj, $objType, $objTargets, $rights,
			$statusId, $statusEditable, $reqTargets, $isPlaced, $redrawOnPub, $multipleObjects,
			$readOnlyProperties );

		// Build flat list of all props shown in dialog, except for Dossier
		$widgets = array();
		$widgetGroup = array();
		foreach( $usages as $key => $usage ) {
			if(!in_array($props[$key]->Category, $widgetGroup)) {
				$widgetGroup[] = $props[$key]->Category;
				$widgets[$props[$key]->Category] = array();
			}
            $props[$key]->Notifications = null;
            if ( !self::objectIsCreated( $action ) ) {
                $props[$key]->Notifications = self::getNotificationsForMultiSetProperties( $key, $rights, $objectsProps, $readOnlyProperties ); // v9.2
            }
			$props[$key]->MixedValues = isset( $mixedValueProps[$key] ) ? $mixedValueProps[$key] : false; // v9.2
			$widgets[$props[$key]->Category][] = new DialogWidget( $props[$key], $usage );
		}

		// Prepares Dialog->MetaData.
		$flatMD = self::enrichAndRepairFlatMetaData( $flatMD, $usages, $props, $pub, $iss, $sec, $action, $obj, $objType,
			$statusId, $routeTo, $multipleObjects, $objectsProps, $mixedValueProps );

		// Build the dialog with tabs
		$tabs = self::buildDialogTabs( $widgetGroup, $multipleObjects, $widgets, $action, $statusEditable );

		$actmap = getObjectActionTypeMap( $objType );
		return new Dialog( $actmap[$action], $tabs, $flatMD );
	}

	/**
	 * Set Editable field in Usages based on workflow rules.
	 *
	 * Function iterates through all the properties in $usages and set the Usage->Editable based on the workflow rules.
	 *
	 * @param array $usages List of property name and its PropertyUsage, see function header for its purpose.
	 * @param array $flatMD List of property name and its MetaDataValue.
	 * @param string $parentObj Parent Object to determine its object type.
	 * @param string $action  Dialog action type
	 * @param Object $obj Workflow object to get its metadata info.
	 * @param string $objType Object type.
	 * @param array $objTargets Object Targets of the Dossier to be checked.
	 * @param array $rights Key-value array where key consists of 'hasRights','noRights', actions and its Object ids in the values.
	 * @param int $statusId Status id
	 * @param bool $statusEditable Function will set this boolean based on State property set in $usages. (To be used by the caller in DefaultFocus).
	 * @param bool $reqTargets Whether the client has requested for Targets. False when $multipleObjects is set to true.
	 * @param bool $isPlaced Tells if the object is a placed object.
	 * @param bool $redrawOnPub Tells if the dialog is about to get drawn after changing brand
	 * @param bool $multipleObjects True when the dialog is for multi set properties, false otherwise.
	 * @param array $readOnlyProperties List of properties that need to be disabled.
	 */
	private static function handlePropertiesEditableField( &$usages, $flatMD, $parentObj, $action, $obj, $objType,
	                                                       $objTargets, $rights, $statusId, &$statusEditable, $reqTargets, $isPlaced,
	                                                       $redrawOnPub, $multipleObjects, $readOnlyProperties )
	{
		// Object Id: Typically needed when dealing with single-setproperties.
		$objId = isset( $obj->MetaData->BasicMetaData->ID ) ? $obj->MetaData->BasicMetaData->ID : 0;

		if( $multipleObjects ) {
			$parentIsModule = false;
			$isCommonPlacement = false; // Not applicable but just set to false.
		} else {
			$parentObjType = $parentObj ? $parentObj->MetaData->BasicMetaData->Type : '';
			$parentIsModule = ($parentObjType == 'LayoutModule' || $parentObjType == 'LayoutModuleTemplate');
			$isCommonPlacement = ($action == 'Create' &&
				($objType == 'Image' || $objType == 'Article' || $objType == 'Spreadsheet') &&
				($parentObjType == 'Layout' || $parentObjType == 'PublishForm'));
		}

		$disableDossierProperty = false;
		if( $flatMD['Type']->PropertyValues ) foreach( $flatMD['Type']->PropertyValues as $propValue ) {
			if( $propValue->Value == 'Dossier' ) {
				$disableDossierProperty = self::isDossierPropertyDisabled( $objTargets );
				break; // Break here as the subject (The Dossier) has been found and checked.
			}
		}

		foreach( $usages as $usage ) {
			switch( $usage->Name ) {
				case 'Editions':
					if( $usage->Editable ) {
						// Biz rules to enable/disable editions; Editions can be assigned to:
						// - containers (=> Dossier, Layout, PublishForm etc)
						// - placable objects, which implies 'intended' editions (=> Article, Image, etc)
						// Exception: Hyperlink is placable, but is has no 'intended' editions.
						$disableEditionObjTypes = array( 'Library', 'Task', 'Other', 'Plan', 'Hyperlink',
							'Presentation', 'Archive', 'PublishForm', 'PublishFormTemplate' );
						if( $disableDossierProperty ) {
							$disableEditionObjTypes[] = 'Dossier';
						}
						if( in_array( $objType, $disableEditionObjTypes )  ) {
							$usage->Editable = false; // Always disable editions (BZ#16675)
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because object is of type '.$objType.'.' );
						} else if( $parentIsModule ) {
							$usage->Editable = false; // Always disable editions (BZ#16254)
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because because parent object is a Layout Module.' );
						} else if( $isCommonPlacement ) {
							$usage->Editable = false; // Always disable editions (BZ#15357)
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because user is about to create Article or Image onto a Layout.' );
						} else if( $action == 'SendTo' ) { // Always disable (BZ#18185)
							$usage->Editable = false;
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because dialog type is SendTo.' );
						}
                        else if( !self::objectIsCreated( $action ) ) {  // Avoid GUI deadlocks at Create dialogs (BZ#16504 / BZ#16971)
							$usage->Editable = in_array( $objId, $rights['hasRights']['ChangeEdition'] );
							if( !$usage->Editable ) {
								LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because user has no Change Edition rights.' );
							}
						} else {
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to editable (no matter Change Edition rights) because dialog type is Create or Copy To.' );
						}
					}
					break;
				case 'State':
					if( $usage->Editable ) {
                        if( !self::objectIsCreated( $action ) ) {  // Avoid GUI deadlocks at Create dialogs (BZ#16504 / BZ#16971)
							if( array_key_exists( 'State', $readOnlyProperties) ) { // Only applicable for multiple objects.
								$usage->Editable = false;
							} else { // Check access rights
								if( $objId ) {
									$changeStatusRights = in_array( $objId, $rights['hasRights']['Change_Status'] );
									$changeStatusForwardRights = in_array( $objId, $rights['hasRights']['Change_Status_Forward'] );
								} else {
									// As long as there's one object has the right, the status field can be editable.
									// Objects that have no access rights will be recorded in Notifications.
									$changeStatusRights = ( $rights['hasRights']['Change_Status'] ) ? true : false;
									$changeStatusForwardRights = ( $rights['hasRights']['Change_Status_Forward'] ) ? true : false;
								}
								$usage->Editable = ($changeStatusRights || $changeStatusForwardRights);
							}
							if( !$usage->Editable ) {
								LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because user has no Change Status rights.' );
							}
						}
					}
					if( !$usage->Editable && $statusId == -1 ) { // Whenever status became disabled, allow change status in Personal status (BZ#16064)
						$usage->Editable = true;
						LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to editable because object is in Personal Status.' );
					}
					$statusEditable = $usage->Editable; // remember for setting DefaultFocus
					break;
				case 'Publication':
				case 'PubChannels':
				case 'RelatedTargets':
				case 'Targets':
				case 'Issues':
				case 'Issue':
					if( $usage->Editable ) {

						if( ($objType == 'PublishForm' || $objType == 'PublishFormTemplate' || $disableDossierProperty ) &&
								( !self::objectIsCreated( $action ) ) ) {
							$usage->Editable = false;
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only ' .
								'because dialog type is '.$action.' and object type is '.$objType );
						} else if( $action == 'SendTo' ) { // Always disable (BZ#17657)
							$usage->Editable = false;
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because dialog type is SendTo.' );
						} else if( $parentIsModule ) {
							$usage->Editable = false; // Since editions will be disabled, Brand/Issue needs to be disabled too (BZ#16254)
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because editions are disabled (because parent object is a Layout Module).' );
						} else if( $isCommonPlacement ) {
							$usage->Editable = false; // Always disable (BZ#15357)
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because user is about to create Article or Image onto a Layout.' );
						} else if( $obj && self::hideTargetsAtDialog( $reqTargets, $isPlaced, $obj->Targets ) ) {
							// When target related properties are made hidden, make sure to disable 'Publication'
							// or else user can change it, but not set its issue/editions after that.
							// Happens for single target dialogs only, such as ID/IC. (BZ#17069, BZ#14916, BZ#16686)
							$usage->Editable = false;
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because targets are hidden from dialog.' );
						} else if( !self::objectIsCreated( $action ) ) {  // Avoid GUI deadlocks at Create dialogs (BZ#16504 / BZ#16971)
							if( $redrawOnPub ) {
								$usage->Editable = true; // BZ#31415 - Set field to editable, to avoid GUI deadlocks
							} else {
								$usage->Editable = in_array( $objId, $rights['hasRights']['ChangePIS'] ); // Check access rights (Change Publication/Issue/Section)
							}
							if( !$usage->Editable ) {
								LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because user has no Change Brand/Issue/Section rights.' );
							}
						} else {
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to editable (no matter Change Brand/Issue/Section rights) because dialog type is Create or Copy To.' );
						}
					}
					break;
				case 'Category':
					if( $usage->Editable ) {
						if( $isCommonPlacement ) {
							$usage->Editable = false; // Always disable (BZ#15357)
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because user is about to create Article or Image onto a Layout.' );
                        } else if( !self::objectIsCreated( $action ) ) {  // Avoid GUI deadlocks at Create dialogs (BZ#16504 / BZ#16971)
							if( $redrawOnPub ) {
								$usage->Editable = true; // BZ#31415 - Set field to editable, to avoid GUI deadlocks
							} else {
								// Check access rights
								if( $objId ) {
									$usage->Editable = in_array( $objId, $rights['hasRights']['ChangePIS'] );
								} else { // When dealing with multiple objects.
									if( array_key_exists( 'Category', $readOnlyProperties ) ) {
										$usage->Editable = false;
									} else {
										// As long as there's one object that has rights on Brand/Issue/Section, editable is set to true.
										// Objects that have no access rights will be recorded in Notifications.
										$usage->Editable = ($rights['hasRights']['ChangePIS']) ? true : false;
									}
								}
							}
							if( !$usage->Editable ) {
								LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because user has no Brand/Issue/Section access.' );
							}
						} else {
							LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to editable (no matter Change Brand/Issue/Section rights) because dialog type is Create or Copy To.' );
						}
					}
					break;
			}
			// When restricted property is editable, but user has no "Change Restrictred Properties"
			// rights, change it to read-only here
			$noRestrictedPropertiesRights = in_array( $objId, $rights['noRights']['RestrictedProperties'] );
			if( $usage->Editable && $usage->Restricted && $noRestrictedPropertiesRights ) {
				$usage->Editable = false;
				LogHandler::Log( 'GetDialog', 'INFO', 'Set the '.$usage->Name.' property to read-only because it is Restricted and user has no Change Restricted Properties rights.' );
			}
		}
	}

	/**
	 * Iterates through the objects and detects if there's any object that has auto-routing.
	 *
	 * This function is to take care of edge cases where all several properties have mixed values and when the user starts
	 * changing Category or State, RouteTo might contains mixedValues which will not be understood by setObjectProperties
	 * or multiSetObjectProperties service.
	 *
	 * When the properties shown in the dialog have mixed values in the following combinations:
	 *  - Category, State and RouteTo
	 *  - Category and RouteTo
	 *  - State and RouteTo
	 * function checks if there's any object that has auto-routing configured, as long as there's one object has
	 * auto-routing, certain properties will be disabled in the dialog (depending on the combinations above).
	 *
	 * What are the fields that will be disabled?
	 * When mixed values are found in:
	 * - Category, State and RouteTo
	 *      L> Category and State will be disabled.
	 * - Category and RouteTo
	 *      L> State will be disabled.
	 * - State and RouteTo
	 *      L> Category will be disabled.
	 *
	 *
	 * -- Refer to the scenario below (case where Category, State and RouteTo are mixed values): --
	 * Configuration:
	 * 	Category News/Sport
	 * 	Image Status Draft/Process/Finished
	 * 	Routing
	 * 	News, Image/Draft, user 1
	 * 	News, Image/Process, user 2
	 * 	Sport, Image/Draft, user 3
	 * 	Sport, Image/Process, user 4
	 *
	 * Scenario:
	 * Image 1 in News and Draft routed to user 1
	 * Image 2 in Sport and Process routed to user 3
	 * Dialog
	 * 	Category: Multiple values
	 * 	Status: Multiple values
	 * 	Route to: Multiple values
	 * 	Change category to: "News", Undo button is shown
	 * 	Route to changes to another "Multiple values"
	 * The user expects that in this case image 1 is routed to user 1 and that image 2 is routed to user 2 because this
	 * was configured in the autorouting. Technically there is a problem that auto routing is done by the
	 * GetDialog service and not in the SetProperties services.
	 *
	 * @param string[] $mixedValueProps List of properties where the values mixed among the objects, used to determine the editable field for category and status.
	 * @param array $objectsProps List of ObjId(Key) and its properties values(Value). Needed when $multipleObjects is true.
	 * @param int $pubId Publication Id.
	 * @param string $objType Object Type.
	 * @return array Key-value list where key is the property name and value the message/reason property is disabled.
	 */
	private static function autoRoutingInMultipleObjects( $mixedValueProps, $objectsProps, $pubId, $objType )
	{
		$autoRouting = false;
		$readOnlyProperties = array();
		if( $mixedValueProps ) {
			// Edge case where it will hardly happen, but we still need to handle them to avoid mixed values in RouteTo
			// which can't be resolved by setObjectProperties and MultiSetObjectProperties service.
			$categoryStateRouteToMixed = isset( $mixedValueProps['Category'] ) && isset( $mixedValueProps['State'] ) &&
				isset( $mixedValueProps['RouteTo'] );
			$categoryRouteToMixed = isset( $mixedValueProps['Category'] ) && isset( $mixedValueProps['RouteTo'] );
			$stateRouteToMixed = isset( $mixedValueProps['State'] ) && isset( $mixedValueProps['RouteTo'] );

			// Only check if there's auto-routing configured when any of the edge cases above happen.
			if( $categoryStateRouteToMixed || $categoryRouteToMixed || $stateRouteToMixed ) {
				$user = BizSession::getShortUserName();
				foreach( $objectsProps as /*$objId => */$objProps ) {
					$pub = new Publication();
					$pub->Id = $pubId;

					$issue = new Issue();
					$issue->Id = $objProps['IssueIds'];

					$cat = new Category();
					$cat->Id = $objProps['CategoryId'];

					$states = self::getStatesExtended( $user, null, $pub, $issue, $cat, $objType );
					if( isset($states->States) ) foreach( $states->States as $status ) {
						if( $status->Id == $objProps['StateId'] ) {
							if( $status->DefaultRouteTo ) {
								// As long as one object has auto-routing,
								// certain properties(depending on the scenario) need to be disabled.
								// so can quit here once one auto-routing is found.
								$autoRouting = true;
								break 2; // Quit two foreach loop.
							}
						}
					}
				}
			}

			if( $autoRouting ) { // At least one object has auto-routing configured.
				if( $categoryStateRouteToMixed ) { // This combination has to be the most top.
					$message = BizResources::localize( 'MULTI_SET_PROPERTIES_AUTO_ROUTING_WARNING' );
					$readOnlyProperties['Category'] = $message;
					$readOnlyProperties['State'] = $message;
				} else if( $categoryRouteToMixed ) {
					$readOnlyProperties['State'] = BizResources::localize( 'MULTI_SET_PROPERTIES_READONLY_WARNING' );
				} else if( $stateRouteToMixed ) {
					$readOnlyProperties['Category'] = BizResources::localize( 'MULTI_SET_PROPERTIES_READONLY_WARNING' );
				}
			}
		}
		return $readOnlyProperties;
	}

	/**
	 * To check for all the properties if they have mixed values in their values.
	 *
	 * The function iterates through a list of properties in $objectsProps, each
	 * @param array $usages List of key-value pairs where key is the property name and value is PropertyUsage.
	 * @param array $objectsProps List of key-value pairs where key is the objectId and value is array of propertyName and its value.
	 * @param string $routeTo User fullname, the value is to check if property 'RouteTo' needs to be checked for MultipleValues. Refer to header.
	 * @param int $statusId Object status Id, value is needed to check if property 'State' needs to be checked for MultipleValues. Refer to header.
	 * @param int $sec Category Id, value is needed to check if property 'Category' needs to be checked for MultipleValues. Refer to header.
	 * @return array List of properties where the multiple objects don't share the same value.
	 */
	private static function getMixedValuesProperties( $usages, $objectsProps, $routeTo, $statusId, $sec )
	{
		$mixedValueProps = array();
		$benchMarkObj = reset( $objectsProps );
		if( $usages ) foreach( array_keys( $usages ) as $propUsageName ) {
			// For certain properties, we don't have to check for its MixedValues when the user has selected the value.
			// The value selected will be applied to all the objects, and therefore skip here when the value is given(selected).
			if( $propUsageName == 'RouteTo' && $routeTo ) {
				continue; // skip
			}
			if( $propUsageName == 'State' && $statusId ) {
				continue; // skip
			}
			if( $propUsageName == 'Category' && $sec ) {
				continue; // skip
			}

			// When reaches here, meaning no value was selected or it is a initial draw, therefore have to find out
			// the MixedValues by going through the objects.
			foreach( $objectsProps as /*$objId => */$objProps ) {
				if( $benchMarkObj[$propUsageName] != $objProps[$propUsageName] ){ // Detected object using another value for the same property.
					$mixedValueProps[$propUsageName] = true; // Mark down the property where the objects have different values.
					break; // Found that this property($propUsageName) has mixed value used by the objects, break and search for the next property.
				}
			}
		}

		return $mixedValueProps;
	}

	/**
	 * Enrich the flat metadata structure which will be used in Dialog->MetaData.
	 *
	 * The function first goes through the usages list and adds properties into the flat metadata($flatMD) when the
	 * properties don't exist yet in flat metadata. Then it populates the flat metadata with the values taken from a
	 * object.
	 * When there are multiple objects involved, first object will be taken to populate the properties' data, however
	 * when the objects don't share the same value among all for that particular property, the property's value will be
	 * set to null (meaning not shown to end-user).
	 *
	 * @param array $flatMD List of property name and its MetaDataValue, this flatMD will be enriched by the function.
	 * @param array $usages List of property name and its PropertyUsage, see function header for its purpose.
	 * @param array $props List of property name and its PropertyInfo.
	 * @param int $pub Publication id
	 * @param int $iss Issue id
	 * @param int $sec Category id
	 * @param string $action  Dialog action type
	 * @param Object $obj To be used to populate the metadata structure data.
	 * @param string $objType Object type.
	 * @param int $statusId Status id
	 * @param string $routeTo Route To property to pre-select at dialog
	 * @param bool $multipleObjects True when the dialog is for multi set properties, false otherwise.
	 * @param array $objectsProps List of objectId and its properties with its values.
	 * @param array $mixedValueProps List of properties where the multiple objects don't share the same value.
	 * @return array The enriched flat metadata structure which is ready to be used in Dialog->MetaData.
	 */
	private static function enrichAndRepairFlatMetaData( $flatMD, $usages, $props, $pub, $iss, $sec, $action, $obj,
	                                                     $objType, $statusId, $routeTo, $multipleObjects, $objectsProps,
	                                                     $mixedValueProps )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		foreach( array_keys( $usages ) as $propName ) {
			if( !array_key_exists( $propName, $flatMD ) ) { // only enrich when missing
				$md = new MetaDataValue();
				$md->Property = $props[$propName]->Name;
				if( isset( $props[$propName]->DefaultValue ) // Assign a default value when it is configured.
					&& !is_null( $props[$propName]->DefaultValue )) {
					$defaultValue = $props[$propName]->DefaultValue;
					if( $props[$propName]->Type == 'bool' ) {
						$defaultValue = ( strtolower( $defaultValue ) == 'true' || $defaultValue == 1 ) ? 1 : 0;
					}
					$propValue = new PropertyValue( $defaultValue );
					$md->PropertyValues = array( $propValue );
				}

				$flatMD[$md->Property] = $md;
			}
		}
		if( $multipleObjects ) {
			// In order to enrich the $flatMD, we need an object, for multi-setproperties dialog,
			// many objects are involved, and therefore there's no $objId (only available when dealing with single object).
			// Here we just get one object (from the many objects involved) and enrich the flat metadata
			// structure( $flatMD ).
			// Those properties where the objects don't share the same value, it will be
			// 'scrapped off'(by setting the properties to null) later on.
			$user = BizSession::getShortUserName();
			reset( $objectsProps ); // Just get one of the objects.
			$obj = self::getObjectOrAlien( $user, key($objectsProps), array('Workflow') );
		}

		// Fill the flat list with meta data from the tree
		if( !is_null( $obj ) && isset( $obj->MetaData ) ) {
			$IDs = null;
			if( isset( $flatMD['IDs'] )) {
				// As IDs is not an official built-in property, nor it is a custom property, so here
				// exclude from the $flatMD so that BizProperty:updateMetaDataFlatWithTree can update the tree
				// without raising warning stating that 'IDs' is not supported.
				$IDs = $flatMD['IDs'];
				unset( $flatMD['IDs'] );
			}
			BizProperty::updateMetaDataFlatWithTree( $flatMD, $obj->MetaData );
			if( !is_null( $IDs )) {
				$flatMD['IDs'] = $IDs; // Add it back here.
			}

			// $flatMD: Flat list to let caller show initial/current data in dialog (to allow user change it)
			$objId = $obj->MetaData->BasicMetaData->ID;
			BizProperty::updateMetaDataFlatWithSpecialProperties( $objId, $flatMD );
			// BZ#14681 Clear comment value if action is check in
			if ($action == 'CheckIn'){
				// we don't need to use reference md here because md is an object
				foreach ($flatMD as $md){
					if ($md->Property == 'Comment'){
						$md->Values = array('');
						$md->PropertyValues = array(); // BZ#29243 - remove the comment the v8 way!!
						break;
					}
				}
				LogHandler::Log( 'GetDialog', 'INFO', 'Cleared the Comment property because dialog type is Check In.' );
			}

			if( $multipleObjects ) {
				// For multiple objects, $obj is not applicable since it involves multiple objects.
				// The above was just to enrich the flat metadata structure values by using one of the multiple objects,
				// once finish enriching, set the $obj to be null.
				$obj = null;
			}
		}

		// No matter object metadata stored at DB, we fill-in the requested Brand and Category
		// here to let SOAP client pre-select them at workflow dialog!

		if( $objType == 'PublishFormTemplate' ) { // Issues are not shown for PublishFormTemplate but for PublishForm.
			unset( $flatMD['Issue'] );
			unset( $flatMD['Issues'] );
		}

		foreach( $flatMD as &$fmd ) {
			switch( $fmd->Property ) {
				case 'Publication':
					if( !empty( $pub ) ) {
						require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
						$pubRow = DBPublication::getPublication( $pub );
						$pubName = $pubRow ? $pubRow['publication'] : '';
						$fmd->PropertyValues = array( new PropertyValue($pub, $pubName) );
					}
					break;
				case 'Issue':
				case 'Issues':
				case 'PubChannels':
					if( !empty( $iss ) ) {
						require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
						require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
						$issRow = DBIssue::getIssue( $iss );
						$issName = !is_null( $issRow ) ? $issRow['name'] : '';

						if( $fmd->Property == 'Issue' || $fmd->Property == 'Issues' ) {
							$fmd->PropertyValues = array( new PropertyValue($iss, $issName ) );
						} else if( $fmd->Property == 'PubChannels' ) {
							$pubChannel = BizPublication::getChannelForIssue( $iss );
							$fmd->PropertyValues = array( new PropertyValue( $pubChannel->Id, $pubChannel->Name ) );
						}
					}
					break;
				case 'Section':
				case 'Category':
				case 'CategoryId':
					if( !empty( $sec ) ) {
						require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
						$secName = DBSection::getSectionName( $sec );
						$fmd->PropertyValues = array( new PropertyValue( $sec, $secName) );
					}
					break;
				case 'State':
				case 'StateId':
					if( !empty( $statusId ) ) {
						require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
						$fmd->PropertyValues = array( new PropertyValue( $statusId, DBWorkflow::getStatusName( $statusId ) ) );
					}
					break;
				case 'RouteTo':
					if( !empty( $routeTo ) ) {
						// The RouteTo PropertyValue must match with the DialogWidget->PropertyInfo->PropertyValues
						// to let the client pre-select one of the listed for the route to field.
						// Therefore the user indication is the user short name, and not the user db id !
						// And obviously, the display value is the user full name.
						$routeToPropValue = BizProperty::getRouteToPropertyValue( $routeTo );
						$fmd->PropertyValues = array( $routeToPropValue );
					}
					break;
			}
		}

		// Do not return the properties in Dialog->MetaData if they have mixed values.
		if( $mixedValueProps ) foreach( array_keys( $mixedValueProps ) as $mixedValueProp ) {
			$flatMD[$mixedValueProp]->Values = null;
			$flatMD[$mixedValueProp]->PropertyValues = null;
		}

		return $flatMD;
	}

	/**
	 * To create a list of DialogTab.
	 *
	 * @param array $widgetGroup List of dialog tabs.
	 * @param bool $multipleObjects Whether the dialog is for multi setproperties that involves multiple objects.
	 * @param array $widgets List of dialog tabs with its list of DialogWidget.
	 * @param string $action Workflow action type, this value is used to determin the DialogTab->DefaultFocus.
	 * @param bool $statusEditable Whether the status field is editable, this it to determine the DialogTab->DefaultFocus.
	 * @return array List of DialogTab.
	 */
	private static function buildDialogTabs( $widgetGroup, $multipleObjects, $widgets, $action, $statusEditable )
	{
		$tabs = array();
		foreach( $widgetGroup as $key => $category ) {
			$tab = new DialogTab();
			if( !$category ) {
				$tab->Title = BizResources::localize('TAB_GENERAL');
				if( $multipleObjects ) {
					// Search for the first editable widget on the first tab.
					$tab->DefaultFocus = '';
					$tabWidgets = $widgets[$widgetGroup[$key]];
					foreach( $tabWidgets as $tabWidget ) {
						if( $tabWidget->PropertyUsage->Editable ) {
							$tab->DefaultFocus = $tabWidget->PropertyInfo->Name;
							break;
						}
					}
				} else {
					// BZ#13312, BZ#16689: DefaultFocus feature
                    $tab->DefaultFocus = ( self::objectIsCreated( $action ) || !$statusEditable) ? 'Name' : 'State';
				}

			} else {
				$tab->Title = $category;
				$tab->DefaultFocus = '';
			}
			$tab->Widgets = $widgets[$widgetGroup[$key]];
			$tabs[] = $tab;
		}
		return $tabs;
	}

	/**
	 * Compose a list of PropertyNotification to be added into PropertyInfo->Notifications.
	 *
	 * When a user attempts to change a property value of an object but he/she has no access rights
	 * to do so for a particular object, this function will collect the error and return in PropertyNotification.
	 * The objects names of which user has no rights to will be returned.
     * In case of a 'Create' or 'Copy' action the object id is unknown.
	 *
	 * $readOnlyProperties contains a list of read only properties and each of the property is enclosed with a notification
	 * that tells why the property is disabled. Refer to {@link:autoRoutingInMultipleObjects()} for more info.
	 *
	 * @param string $propName Property of an object of which user has no access to will be marked down in the notifications.
	 * @param array $rights Key-value array where key consists of 'hasRights','noRights', actions and its Object ids in the values.
	 * @param array $objectsProps List of ObjId(Key) and its properties values(Value). Needed when $multipleObjects is true.
	 * @param array $readOnlyProperties List of properties that are disabled.
	 * @return array|null List of PropertyNotification (typically one PropertyNotification) when user has no rights for certain action on certain objects.
	 */
	private static function getNotificationsForMultiSetProperties( $propName, $rights, $objectsProps, $readOnlyProperties )
	{
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';
		$notifications = array();
		$noRightsObjIds = null;
		switch( $propName ) {
			case 'State':
				$changeStatusRightsIds = $rights['noRights']['Change_Status'];
				$changeStatusForwardRightsIds = $rights['noRights']['Change_Status_Forward'];
				if( $changeStatusRightsIds && $changeStatusForwardRightsIds ) {
					$noRightsObjIds = array_unique ( array_merge( $changeStatusRightsIds, $changeStatusForwardRightsIds ));
				}
				if( array_key_exists( 'State', $readOnlyProperties ) ) {
					$propNotification = new PropertyNotification();
					$propNotification->Type = 'Info';
					$propNotification->Message = $readOnlyProperties['State'];
					$notifications[] = $propNotification;
				}
				break;
			case 'Publication':
				if( $rights['noRights']['ChangePIS'] ) {
					$noRightsObjIds = $rights['noRights']['ChangePIS']; // Object Ids of which user doesn't have access rights
				}
				break;
			case 'Category':
				if( $rights['noRights']['ChangePIS'] ) {
					$noRightsObjIds = $rights['noRights']['ChangePIS']; // Object Ids of which user doesn't have access rights
				}
				if( array_key_exists( 'Category', $readOnlyProperties ) ) {
					$propNotification = new PropertyNotification();
					$propNotification->Type = 'Info';
					$propNotification->Message = $readOnlyProperties['Category'];
					$notifications[] = $propNotification;
				}
				break;
		}
		if( !is_null( $noRightsObjIds )) { // Objects where user has no rights.
			$noRightsObjects = array();
			require_once BASEDIR .'/server/dbclasses/DBObject.class.php';
			foreach( $noRightsObjIds as $noRightObjId ) {
				if( array_key_exists( $noRightObjId, $objectsProps ) ) {
					$noRightsObjects[] = $objectsProps[$noRightObjId]['Name'];
				} else {
					$noRightsObjects[] = DBObject::getObjectName( $noRightObjId );
				}
			}


			$propNotification = new PropertyNotification();
			$propNotification->Type = 'Info';
			$bulletSymbol = chr( 0xE2 ) . chr( 0x80 ) . chr( 0xA2 );
			$propNotification->Message = BizResources::localize( 'MULTI_SET_PROPERTIES_ACCESRIGHTS_WARNING' ) . "\n" .
				$bulletSymbol. implode( "\n" . $bulletSymbol , $noRightsObjects );

			$notifications[] = $propNotification;
		}

		return $notifications ? $notifications : null;
	}

	/**
	 * To tell whether the dialog properties should be enabled or disabled.
	 *
	 * When clients (such as Content Station below v9.0.0 and SmartConnection) are not
	 * PublishForm aware, dialog properties(such as PublicationChannel) that are
	 * PublishForm related are all disabled.
	 *
	 * @param array $objTargets Object Targets of the Dossier to be checked.
	 * @return bool.
	 */
	protected static function isDossierPropertyDisabled( $objTargets )
	{
		$clientVersion = BizSession::getClientVersion( null, null, 3 );
		$appName = BizSession::getClientName();

		$dossierPropDisabled = false; // By default, don't disable anything.
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		if( $objTargets ) foreach( $objTargets as $objTarget ) {
			$canDossierHavePublishForm = BizServerPlugin::runChannelConnector( $objTarget->PubChannel->Id,
				'doesSupportPublishForms', array(), false );
			if( $canDossierHavePublishForm ) { // Channel that supports PublishForm, but can the client handle PublishForm? Check further.
				$isInDesign = ( stripos( $appName, 'InDesign' ) !== false ) ? true: false;
				$isInCopy = ( stripos( $appName, 'InCopy' ) !== false ) ? true: false;
				if( ( $appName == 'Content Station' && version_compare( $clientVersion, '9.0.0', '<' ) ) ||
					$isInDesign || $isInCopy ) {
					$dossierPropDisabled = true; // Client that are not PublishForm aware, disable it.
				} else {
					$dossierPropDisabled = false; // Client is PublishForm aware.
				}
				break; // When one of the Target supports PublishForm, this Dossier can has PublishForm; Thus quit here, not needed to search further.
			}
		}
		return $dossierPropDisabled;
	}

	/**
	 * For initially drawn dialogs, we do not (yet) pre-select the Route To, except for Create and CopyTo dialogs.
	 * As soon at the user selects a different Status or Category, the GetDialog service is called
	 * again for a redraw, in which case the Route To should get pre-selected. The pre-selections
	 * are implemented by returning the MetaDataValue for the Route To field through GetDialog response.
	 * Note that the personal status is all-overruling, for which the Route To is -always- set to current user.
	 *
	 * @param bool $redrawNonCreate
	 * @param string $routeTo Route To value of object, as stored at DB (not as selected by user!)
	 * @param string $action Dialog action
	 * @param int $statusId Status of object (initial dialog draw), or selected by user at dialog (redraw).
	 * @param WflGetStatesResponse $states Used to pick default status from
	 * @return string
	 */
	static private function determineRouteTo( $redrawNonCreate, $routeTo, $action, $statusId, $states )
	{
		if( $statusId == -1 ) {
			$newRouteTo = true; // Personal status; Always Route To current user!
			LogHandler::Log( 'GetDialog', 'INFO', 'Decided to determine new Route To because object is in Personal Status.' );
		} else if( $redrawNonCreate ) {
			$newRouteTo = true; // Redraw dialog always triggers automatic routing!
			LogHandler::Log( 'GetDialog', 'INFO', 'Decided to determine new Route To because non-Create dialog is about to get redrawn.' );
		} else if( self::objectIsCreated( $action ) ) { // BZ#16971
			$newRouteTo = true; // Create/CopyTo dialogs always have Route To preselected; even when dialog is initially drawn
			LogHandler::Log( 'GetDialog', 'INFO', 'Decided to determine new Route To because dialog type is Create, Copy To or Check In.' );
		} else {
			$newRouteTo = false; // NO automatic routing; Keep the Route To as-is
			LogHandler::Log( 'GetDialog', 'INFO', 'No automatic routing triggered, so Route To is unchanged.' );
		}
		if( $newRouteTo ) {
			if( isset($states->States) ) foreach( $states->States as $status ) {
				if( $status->Id == $statusId ) {
					if( $status->DefaultRouteTo ) {
						require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
						$routeTo = BizUser::resolveFullUserName( $status->DefaultRouteTo );
						LogHandler::Log( 'GetDialog', 'INFO', 'Automatic routing applied. Route To set to '.$routeTo );
					} else {
						LogHandler::Log( 'GetDialog', 'INFO', 'No automatic routing because there was no default routing configured for status (id='.$status->Id.').' );
					}
				}
			}
		}
		return $routeTo;
	}

	/**
	 * Initialize $rights array to store the access rights for affected objects.
	 *
	 * Function returns a list of rights array with the following structure:
	 * $rights['hasRights'][ACTION] = array();
	 * $rights['noRights'][ACTION] = array();
	 * Where ACTION is ChangeEdition, Change_Status, Change_Status_Forward, CreateDossier,
	 * RestrictedProperties, ChangePIS.
	 *
	 * @return array
	 */
	private static function initRights()
	{
		$rights = array();
		$rights['hasRights']['ChangeEdition']         = array();
		$rights['hasRights']['Change_Status']         = array();
		$rights['hasRights']['Change_Status_Forward'] = array();
		$rights['hasRights']['CreateDossier']         = array();
		$rights['hasRights']['RestrictedProperties']  = array();
		$rights['hasRights']['ChangePIS']             = array();

		$rights['noRights']['ChangeEdition']         = array();
		$rights['noRights']['Change_Status']         = array();
		$rights['noRights']['Change_Status_Forward'] = array();
		$rights['noRights']['CreateDossier']         = array();
		$rights['noRights']['RestrictedProperties']  = array();
		$rights['noRights']['ChangePIS']             = array();
		return $rights;
	}

	/**
	 * Determines user access rights that are relevant for drawing workflow dialogs.
     * In case of an object is created or an existing object is copied the object id is unknown (null).
	 *
	 * @param string $user
	 * @param int $pub Publication Id
	 * @param int|null $iss Issue id
	 * @param int|null $cat Category id
	 * @param string $objType Object type
	 * @param int $statusId Status id
	 * @param mixed $objId Object id of which their access rights collected, null in case of a 'Create' or 'Copy' action.
	 * @param string $contentSource
	 * @param string $documentId
	 * @param string $routeTo
	 * @return array List of access rights with the object ids assigned to the corresponding access.
	 */
	private static function determineAccessRightsForObject(
		$user, $pub, $iss, $cat, $objType, $statusId,
		$objId, $contentSource, $documentId, $routeTo )
	{
		// Determine access rights
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}

		$rights = self::initRights();
		$globAuth->getRights( $user, $pub, $iss, $cat, $objType, $statusId );
		$rights = self::collectAccessRights( $globAuth,
			$pub, $iss, $cat, $objType, $statusId,
			$objId, $contentSource, $documentId, $rights, $routeTo );
		LogHandler::Log( 'GetDialog', 'INFO', 'Access rights: '.print_r($rights,true) );
		return $rights;
	}

	/**
	 * Determines user access rights that are relevant for drawing workflow dialogs.
	 *
	 * @param string $user
	 * @param int $pub Publication Id to filter/determine the access right.
	 * @param int|null $iss Issue id to filter/determine the access right
	 * @param int|null $cat Category id to filter/determine the access right, null to retrieve Category from $objIds.
	 * @param string $objType Object type to filter/determine the access right.
	 * @param int $statusId Status id to filter/determine the access right, 0 to retrieve statusId from $objIds.
	 * @param array $objIds List of object ids of which their access rights collected.
	 * @return array List of access rights with the object ids assigned to the corresponding access.
	 */
	private static function determineAccessRightsForMultipleObjects(
		$user, $pub, $iss, $cat, $objType, $statusId, $objIds )
	{
		// Determine access rights
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
		$globAuth->getRights( $user, $pub, $iss );

		$rights = self::initRights();
		$metaDatas = DBObject::getMultipleObjectsProperties( $objIds );

		foreach( $metaDatas as $objId => $metaData ) {
			$objCatId = $cat ? $cat : $metaData->BasicMetaData->Category->Id; // When $cat is given, meaning it is a re-draw.
			$objStatusId = $statusId ? $statusId : $metaData->WorkflowMetaData->State->Id;
			$contentSource = $metaData->BasicMetaData->ContentSource;
			$documentId = $metaData->BasicMetaData->DocumentID;
			$routeTo = $metaData->WorkflowMetaData->RouteTo;
			$rights = self::collectAccessRights( $globAuth,
				$pub, $iss, $objCatId, $objType, $objStatusId,
				$objId, $contentSource, $documentId, $rights, $routeTo );
		}
		return $rights;
	}

	/**
	 * The access right is collected and being sorted into hasRights and noRights for the given object.
     * In case of an object is created or an existing object is copied the object id is unknown (null).
	 *
	 * @param authorizationmodule $globAuth
	 * @param int $pub Publication Id to filter/determine the access right.
	 * @param int $iss Issue id to filter/determine the access right.
	 * @param int $cat Category id to filter/determine the access right.
	 * @param string $objType Object type to filter/determine the access right.
	 * @param int $statusId Status id to filter/determine the access right.
     * @param mixed $objId Object id of which their access rights collected, null in case of a 'Create' or 'Copy' action.
	 * @param string $contentSource
	 * @param string $documentId
	 * @param array $rights List of access rights of which the object id will be assign in to the corresponding access.
	 * @param string $routeTo User to which an object is routed or will be routed to.
	 * @return array List of access rights with the object id assigned to the corresponding access.
	 */
	private static function collectAccessRights(
		$globAuth, $pub, $iss, $cat, $objType, $statusId,
		$objId, $contentSource, $documentId, $rights, $routeTo )
	{
		if( $globAuth->checkright( 'e',
			$pub, $iss, $cat, $objType, $statusId,
			$objId, $contentSource, $documentId, $routeTo ) ) {
			$rights['hasRights']['ChangeEdition'][] = $objId;
		} else {
			$rights['noRights']['ChangeEdition'][] = $objId;
		}

		if( $globAuth->checkright( 'C',
			$pub, $iss, $cat, $objType, $statusId,
			$objId, $contentSource, $documentId, $routeTo ) ) {
			$rights['hasRights']['Change_Status'][] = $objId;
		} else {
			$rights['noRights']['Change_Status'][] = $objId;
		}

		if( $globAuth->checkright( 'F',
			$pub, $iss, $cat, $objType, $statusId,
			$objId, $contentSource, $documentId, $routeTo ) ) {
			$rights['hasRights']['Change_Status_Forward'][] = $objId;
		} else {
			$rights['noRights']['Change_Status_Forward'][] = $objId;
		}

		if( $globAuth->checkright( 'd',
			$pub, $iss, $cat, $objType, $statusId,
			$objId, $contentSource, $documentId, $routeTo ) ) {
			$rights['hasRights']['CreateDossier'][] = $objId;
		} else {
			$rights['noRights']['CreateDossier'][] = $objId;
		}

		if( $globAuth->checkright( 'r',
			$pub, $iss, $cat, $objType, $statusId,
			$objId, $contentSource, $documentId, $routeTo ) ) {
			$rights['hasRights']['RestrictedProperties'][] = $objId;
		} else {
			$rights['noRights']['RestrictedProperties'][] = $objId;
		}

		if( $globAuth->checkright( 'P',
			$pub, $iss, $cat, $objType, $statusId,
			$objId, $contentSource, $documentId, $routeTo ) ) {
			$rights['hasRights']['ChangePIS'][] = $objId;
		} else {
			$rights['noRights']['ChangePIS'][] = $objId;
		}
		return $rights;
	}

	/**
	 * Removes the unwanted PropertyUsage objects for property Dossier.
	 *
	 * @since 10.x.x
	 * @param string $action
	 * @param string $objType
	 * @param string $defaultDossier
	 * @param string[] $usages [IN/OUT]
	 */
	public static function fixDossierPropertyUsage( $action, $objType, $defaultDossier, &$usages )
	{
		// Add Dossier property list to dialog and fill it with dossiers (to let user pick one).
		// But, disable Dossier property for non-Create dialogs, even when client does support it.
		// The reason is that only the CreateObject service does support implicit Dossier creation !
		// Only for Create dialogs we want the Dossier property (BZ#10526)
		// Besides 'Create', 'CopyTo' needs Dossier property as well. (BZ #18311)
		if( !self::objectIsCreated( $action ) ||
			$objType == 'Dossier' || $objType == 'DossierTemplate'  ) { // Dossier in Dossier not supported! (BZ#16909)
			unset($usages['Dossier']);
		}

		if ( is_null($defaultDossier) ) {
			// remove Dossier from usages, it's not supported by the client
			if (isset($usages['Dossier'])){
				unset($usages['Dossier']);
				LogHandler::Log( 'GetDialog', 'INFO', 'Hiding Dossier property because it is not supported by client.' );
			}
		}
	}

	/**
	 * Adds/removes the given PropertyInfo and PropertyUsage objects with 'Dossier' property details.
	 * This depends if supported by client, which is indicated by the $defaultDossier param.
	 *
	 * @since 10.x.x Renamed the function from fixDossierPropertyUsage to handleDossiersForDialog.
	 * @param int $pub Publication Id.
	 * @param int $iss Issue Id.
	 * @param int $catId Category Id.
	 * @param string[] $rights User access rights (key=right, value=boolean)
	 * @param int $defaultDossier Dossier ID. When given, 'Dossier' prop details are added.
	 * @param string $user Acting user.
	 * @param string[] $usages List of PropertyUsage objects.
	 * @param string[] $retVal List of Dialog properties.
	 * @param string[] $props List of properties.
	 */
	protected static function handleDossiersForDialog( $pub, $iss, $catId, $rights, $defaultDossier, $user, $usages, array &$retVal, array &$props )
	{
		if( !is_null($defaultDossier) && isset($usages['Dossier']) ) {
			$retVal['Dossiers'] = self::getDossiersForDialog( $pub, $iss, $catId, $rights, $defaultDossier, $user );
		}

		if ( !is_null($defaultDossier) ) {
			// set default dossier
			if( isset($props['Dossier']) ) {
				$props['Dossier']->PropertyValues = array();
				$dossiers = $retVal['Dossiers'];
				if( $dossiers ) foreach( $dossiers as $dossier ) {
					$props['Dossier']->PropertyValues[] = new PropertyValue( $dossier->ID, $dossier->Name );
					// Check whether default dossier exists and set default value
					if( $dossier->ID == $defaultDossier ) {
						// use name as other ValueList properties do
						$props['Dossier']->DefaultValue = $dossier->Name;
					}
				}
				// value list is not valid because it will be filled $retVal['Dossiers']
				$props['Dossier']->ValueList = null;
				// BZ17831 Don't reset Mandatory for Dossiers here, the default is reset in BizProperty::getPropertyUsages
				// and it can be customized in the dialog set-up
			}
		}
	}

	/**
	 * Removes the unwanted PropertyUsage objects that are not applicable for multi set properties dialog.
	 * The properties that should not be n multi set properties dialog are:
	 * - Name
	 * - Publication
	 * - PubChannels
	 * - Targets
	 * - Issues
	 * - **Issue
	 * - Editions
	 *
	 * **When the dialog is dealing with overruleIssue ($isOverruleIssue = true),
	 * $usages['Issue'] is retained to keep the 'acting publication' (reflected in OverruleIssue).
	 *
	 * @param array $usages List of PropertyUsage objects.
	 * @param bool $isOverruleIssue True when dealing with overrule issue and $usages['Issue'] will be retained.
	 */
	protected static function fixForMultipleObjectsPropertyUsage( array &$usages, $isOverruleIssue )
	{
		unset( $usages['Name'] );
		unset( $usages['Publication'] );
		unset( $usages['PubChannels'] );
		unset( $usages['Targets'] );
		unset( $usages['Issues'] );
		if( !$isOverruleIssue ) {
			unset( $usages['Issue'] );
		}
		unset( $usages['Editions'] );
	}

	/**
	 * Inserts the RelatedTargets (fake property) after the first best target related property found.
	 *
	 * @param array $properties List of PropertyInfo objects
	 * @param array $usages List of PropertyUsage objects
	 */
	protected static function addRelatedTargetsPropertyUsage( array &$properties, array &$usages )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$targetProps = BizProperty::getTargetRelatedPropIds();
		$offset = 0;
		// check if targets are requested and dertermine offset
		foreach( array_keys($usages) as $key ) {
			if (in_array($key, $targetProps)){
				break;
			}
			$offset++;
		}
		$tmpArray = array_splice($usages, $offset);

		$usages['RelatedTargets'] = new PropertyUsage('RelatedTargets', true, false, false, false);
		$properties['RelatedTargets'] = new PropertyInfo('RelatedTargets',
			BizResources::localize('OBJ_USEDIN'), null, 'list');

		$usages = array_merge($usages, $tmpArray);
	}

	/**
	 * Alters target properties and usages depending if client supports complex target widget.
	 *
	 * @param bool  $reqTargets Client supports complex targets widget
	 * @param array $usages List of PropertyUsage objects
	 * @param boolean $isPlaced Tells if the object is placed
	 * @param array $objTargets
	 * @param string $objType
	 */
	protected static function fixTargetPropertyUsage( $reqTargets, array &$usages, $isPlaced, $objTargets, $objType )
	{

		// Leave out the widgets when not required for certain object types.
		switch( $objType ) {
			case 'PublishFormTemplate':
				unset( $usages['Targets'] );
				unset( $usages['Editions'] );
				unset( $usages['Issues'] );
				unset( $usages['Issue'] );
				break;
			case 'PublishForm':
				unset( $usages['Targets'] );
				unset( $usages['Editions'] );
				break;
		}

		// Remove target related properties that should not be shown
		if( $objType != 'PublishFormTemplate' && $objType != 'PublishForm' ) {
			unset( $usages['PubChannels'] );
		}
		if( $reqTargets ) {
			// For ID/IC v7 clients: keep Targets, Issues and Editions
			unset( $usages['Issue'] );
		} else {
			// For CS/WE v7 clients: keep Issue and Editions
			unset( $usages['Target'] );
		}

		// Hide object targets when showing is confusing for end user or when editing/roundtrip could lead into troubles. (BZ#17069, BZ#14916, BZ#16686)
		if( self::hideTargetsAtDialog( $reqTargets, $isPlaced, $objTargets ) ) {
			unset($usages['Issue']);
			unset($usages['Issues']);
			unset($usages['Targets']);
			unset($usages['Editions']);
			if( $isPlaced ) {
				LogHandler::Log( 'GetDialog', 'INFO', 'Hiding Issue/Editions properties because object is placed and client has single target support only.' );
			} else {
				LogHandler::Log( 'GetDialog', 'INFO', 'Hiding Issue/Editions properties because object has multiple targets and client has single target support only.' );
			}
		}
	}

	/**
	 * Tells when it is needed to hide target related properties at workflow dialog.
	 *
	 * @param bool $reqTargets Client supports complex targets widget
	 * @param boolean $isPlaced Tells if the object is placed
	 * @param array $objTargets
	 * @return bool
	 */
	protected static function hideTargetsAtDialog( $reqTargets, $isPlaced, $objTargets )
	{
		return !$reqTargets && // InDesign or InCopy which can not handle multiple targets at the dialog. Showing only 1 issue selection at the time.
		($isPlaced ||      // Typically a placed article/image for which we don't know which parent to pick to derive the (single!) issue
			// and for which the server does not know the last selected edition for the placed article.
			count($objTargets) != 1); // When object has multiple targets, server can not pick one (as done for v6.1).
		// This is because there can be multiple print channels (or PDF created for Web channel). Better show nothing.
	}

	/**
	 * Prune a given PublicationInfo tree; avoid returning a whole bunch of definitions that are not
	 * used to draw dialog. For example, -many- sections/editions would be returned for
	 * all overrule issues if we won't prune those. But, we do not prune for the requested issue !
	 * But, we never return states; To populate combo, clients should use GetStatesResponse!
	 *
	 * @param PublicationInfo $pubInfo Tree to prune
	 * @param int $issueId The contextual issue of our interest to preserve (not to be pruned!)
	 * @return PublicationInfo The pruned tree
	 */
	protected static function prunePublicationInfo( PublicationInfo &$pubInfo, $issueId )
	{
		$overrule = false;
		foreach( $pubInfo->PubChannels as $chaInfo ) {
			foreach( $chaInfo->Issues as $issInfo ) {
				if( $issInfo->Id == $issueId ) {
					$overrule = $issInfo->OverrulePublication;
				} else {
					// Clear editions/categories at (overrule!) issue level when normal- or other overrule issue is selected.
					// Those are specified at brand- or overrule issue level and so useless to send to clients.
					$issInfo->Editions = null;
					$issInfo->Sections = null;
				}
			}
		}
		// Clear editions/categories at brand level when overrule issue is selected.
		// Those are specified at issue level and so useless to send to clients.
		if( $overrule ) {
			foreach( $pubInfo->PubChannels as $chaInfo ) {
				$chaInfo->Editions = null;
			}
			$pubInfo->Categories = null;
		}

		return $pubInfo;
	}

	/**
	 * Returns dossiers for use in a Dialog.
	 *
	 * @param int $publication
	 * @param int $issue
	 * @param int $section
	 * @param array $rights User access rights (key=right, value=boolean)
	 * @param int $defaultDossier DB Id of the dossier. The dossier that to be added into the list of dossiers for Dialog.
	 * @param string $user User ID. To retreieve the default dossier object.
	 * @return array with ObjectInfo
	 */
	protected static function getDossiersForDialog( $publication = 0, $issue = 0, $section = 0, array $rights, $defaultDossier, $user )
	{
		$dossiers = array();
		// no dossier = 0
		$dossiers[] = new ObjectInfo('0', '', 'Dossier');
		// new dossier = -1
		if( $rights['hasRights']['CreateDossier'] ) {
			$dossiers[] = new ObjectInfo('-1', BizResources::localize('ACT_NEW_DOSSIER'), 'Dossier');
		} else {
			LogHandler::Log( 'GetDialog', 'INFO', 'Hiding New Dossier item at Dossier property because user has no rights to create dossiers.' );
		}
		// only add dossiers when publication, issue and section are given
		$defaultDossierAdded = false;
		if ($publication > 0 && $issue > 0 && $section > 0){
			// query for dossiers
			require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
			$shortUserName = BizSession::getShortUserName();
			$queryParams = array();
			$queryParams[] = new QueryParam('PublicationId', '=', $publication);
			$queryParams[] = new QueryParam('IssueId', '=', $issue);
			//$queryParams[] = new QueryParam('SectionId', '=', $section); // commented out; this gives too few results!
			$queryParams[] = new QueryParam('Type', '=', 'Dossier');
			// BZ#22871 - Shouldn't set the result limit, set maxEntries = '' to return all dossiers
			require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
			$request = new WflQueryObjectsRequest();
			$request->Ticket = '';
			$request->Params = $queryParams;
			$request->MaxEntries = '';
			$request->Hierarchical = false;
			$request->Order = array( new QueryOrder( 'Name', true ) );
			$request->RequestProps = array( 'ID', 'Name', 'Type' );
			$result = BizQuery::queryObjects2( $request, $shortUserName );
			if (is_array($result->Rows)){
				foreach ($result->Rows as $row) {
					if( $row[0] == $defaultDossier ){ // BZ#24829: version#9, scenario 2 & 4:
						LogHandler::Log( 'GetDialog', 'INFO', 'Adding default dossier into Dossier property.' );
						$defaultDossierAdded = true; // Default dossier already added, need not to be added below again.
					}

					$newDossier = new ObjectInfo();
					$newDossier->ID = $row[0];
					$newDossier->Name = $row[1];
					$newDossier->Type = $row[2];
					$dossiers[] = $newDossier;
				}
			}
		} else {
			LogHandler::Log( 'GetDialog', 'INFO', 'Hiding dossier items at Dossier property because no brand/issue/section is given.' );
		}

		// BZ#24829: version#9, scenario 2 & 4:
		if( !$defaultDossierAdded && $defaultDossier > 0 ){ // If only not yet added above and it is a valid dossier ID ( $defaultDossier could be 0 )
			LogHandler::Log( 'GetDialog', 'INFO', 'Adding default dossier into Dossier property.' );
			$defaultDossierObj = BizObject::getObject( $defaultDossier, $user, false, 'none', array() );
			// When defaultDossier is given, Server should adds into the Dossier property.
			$newDossier = new ObjectInfo();
			$newDossier->ID = $defaultDossier;
			$newDossier->Name = $defaultDossierObj->MetaData->BasicMetaData->Name;
			$newDossier->Type = $defaultDossierObj->MetaData->BasicMetaData->Type;
			$dossiers[] = $newDossier;
		}
		return $dossiers;
	}

	/**
	 * Checks issue and category of a publication and set them to a default value
	 * if there're not set yet. Thereby the default channel is taken (or the first
	 * channel when not configured). Of that channel, the current issue is taken (or
	 * the first issue when not configured). When the given issue can not be found at
	 * publication info, a default issue is determined. When the given category can
	 * not be found at the publication or (resolved) issue, the default category is
	 * determined by taking the first one.
	 *
	 * @param PublicationInfo $pubInfo
	 * @param int $iss      [IN/OUT] Issue ID. Pass zero to determine default (see above).
	 * @param string $issName [OUT]    Issue name. Corresponds with returned $issId.
	 * @param int $catId      [IN/OUT] Category ID. Pass zero to determine default (see above).
	 * @param string $catName [OUT]    Category name. Corresponds with returned $catId.
	 * @param string $action  [IN]     Workflow ActionType.
	 * @param string $redrawNonCreate [IN]   Tells if the dialog is about to get drawn for the 2nd+ time. So not initial draw and/or not a Create dialog.
	 * @param string $objType [IN] Object type
	 * @param Object $parentObj Parent object. Typically Layout or Dossier. Null when not provided.
	 * @param int $objectId [IN] The id of the object for which the dialog is called.
	 * @return bool Whether or not issue/category could be resolved. False could happen for bad config only.
	 */
	protected static function fixAndResolveIssueSectionFromPubInfo( $pubInfo, &$iss, &$issName, &$catId, &$catName, $action, $redrawNonCreate, $objType, $parentObj, $objectId )
	{
		// ------------ RESOLVE CHANNEL ------------
		// Prefer using the issue's channel
		$channelId = 0;
		if( $iss ) {
			foreach( $pubInfo->PubChannels as $chaInfo ) {
				foreach( $chaInfo->Issues as $issInfo ) {
					if( $issInfo->Id == $iss ) {
						$channelId = $chaInfo->Id;
						break 2; // Found; quit both loops
					}
				}
			}
		}
		// Fall back at default channel; Typically happens for creating new objects
		if( !$channelId ) { // No issue given (or issue does not belong to publication)
			require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
			$pubRow = DBPublication::getPublication( $pubInfo->Id );
			if( !$pubRow ) { return false; } // should never happen
			$channelId = $pubRow['defaultchannelid'];
			$iss = 0; // Clear to repair later; Requested issue does not belong to publication!
			// Fall back to first channel when no default channel specified
			if( $channelId ) {
				LogHandler::Log( 'GetDialog', 'INFO', 'Picked default channel (id='.$channelId.') because no issue was is given.' );
			} else {
				if( isset($pubInfo->PubChannels[0]) ) {
					$channelId = $pubInfo->PubChannels[0]->Id;
					LogHandler::Log( 'GetDialog', 'INFO', 'Picked first channel (id='.$channelId.') because no issue was is given and no default channel was configured.' );
				}
			}
		}

		// ------------ RESOLVE ISSUE ------------
		// Lookup resolved channel first
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
		$issInfo = null;
		$issPreSelection = false;
		$parentObjType = $parentObj ? $parentObj->MetaData->BasicMetaData->Type : '';
		if( $channelId && // theoretically, the Brand could have no channels
			( $iss ||  ( // issue can be resolved before, typically an overrule issue, which we need to resolve Category below! (BZ#16965)
					($action == 'Create' && $parentObjType != 'Dossier') || $action == 'CopyTo' ||  // avoid adding current issue for Preview dialog, Save Version, etc etc (BZ#16482 / BZ#16971)
					($action == 'Create' && ($objType == 'Layout' || $objType == 'PublishForm') && $parentObjType == 'Dossier' && !$parentObj->Targets ) || // Resolve the default issue when parent object is not target (BZ#30404)
					($redrawNonCreate && !self::canBeIssuelessObject($objType) ) || /* A */
					($objectId && !BizContentSource::isAlienObject( $objectId )&& self::canBeIssuelessObject($objType) && DBTarget::hasObjectTargetIssue( $objectId )) /* B */))) {
			// avoid empty issue combo when selecting another Brand (for Save Version, Check In, etc)
			// Except if a dialog is redrawn and the object can be issueless:
			// - If the object is issueless (no object-target) no default is returned and the issue combo-box will not be shown in ID/IC. BZ#18740. Marked with A.
			// - A default will be returned if the object has its own issue already (object-target) BZ#22087. Marked with B.
			foreach( $pubInfo->PubChannels as $chaInfo ) {
				if( $channelId == $chaInfo->Id ) {
					if( !$iss ) { // Take current issue when none requested (or cleared above)
						$iss = $chaInfo->CurrentIssue;
						$issPreSelection = (bool)$iss;
						if( !$iss ) { // Fall back at first issue when no current configured
							if( isset($chaInfo->Issues[0]) ) {
								$iss = $chaInfo->Issues[0]->Id;
								$issPreSelection = (bool)$iss;
							}
							if( !$iss ) { // No issues configured (should not happen), but let's continue to resolve the Category!
								$issInfo = null;
								break;
							}
						}
					}
					if( $iss ) {
						foreach( $chaInfo->Issues as $issInfo1 ) {
							if( $issInfo1->Id == $iss ) {
								// It could be the case that the user has selected a normal* brand at a CS workflow dialog.
								//   * Which has no combined id with an Overrule Issue.
								// By simply passing that brand id to the GetDialog service without specifying an issue,
								// it picks the current/first issue of the default/first channel.
								// But, that issue could turn out tobe an Overrule Issue...! By pre-selecting such issue, the
								// user would not be able to not select a normal issue anymore... so he/she got stuck with the dialog.
								// So, we need to avoid this situation by telling the GetDialog service to take other issue
								// that is not overrrule. This trick is only needed for CS (BZ#17312).
								if( $issInfo1->OverrulePublication && self::$avoidOverrulePreSelection && $issPreSelection ) {
									// First, search within this channel
									$normalAlternativeFound = false;
									foreach( $chaInfo->Issues as $issInfo2 ) {
										if( !$issInfo2->OverrulePublication ) {
											$normalAlternativeFound = true;
											$issInfo = $issInfo2;
											$iss = $issInfo->Id;
											break; // normal issue found
										}
									}
									// Second, search within other channels
									if( !$normalAlternativeFound ) {
										foreach( $pubInfo->PubChannels as $chaInfo3 ) {
											foreach( $chaInfo3->Issues as $issInfo3 ) {
												if( !$issInfo3->OverrulePublication ) {
													$normalAlternativeFound = true;
													$issInfo = $issInfo3;
													$iss = $issInfo->Id;
													break 2; // normal issue found
												}
											}
										}
									}
									// Clear the issue id and info when no normal alternative is found.
									// This can happen if the Brand only contains overrule issues on all channels.
									if( !$normalAlternativeFound ) {
										$iss = 0;
										$issInfo = null;
										break;
									}
								}
								$issInfo = $issInfo1;
								$issName = $issInfo->Name;
								break; // Requested issue found
							}
							$issInfo = null;
						}
						break; // Requested channel found
					}
				}
			}
		}

		// ------------ RESOLVE CATEGORY ------------
		// Determine where to look for categories; at publication or issue?
		$catName = '';
		if( !is_null($issInfo) && $issInfo->OverrulePublication ) {
			$categories = $issInfo->Sections;
		} else {
			$categories = $pubInfo->Categories;
		}
		if( $catId ) { // Lookup requested category
			foreach( $categories as $catInfo ) {
				if( $catInfo->Id == $catId ) {
					$catName = $catInfo->Name;
					break; // Requested category found
				}
			}
		}
		// If no category requested or found, take first one
		if( empty($catName) ) {
			$catId = 0; // Clear to repair
			if( isset($categories[0]) ) {
				$catId = $categories[0]->Id;
				$catName = $categories[0]->Name;
			}
			if( !$catId ) { return false; } // Quit when no categories configured (should not happen)
		}
		return true;
	}

	/**
	 * Returns all targets of all parent objects onto which the given object ($objId) is placed
	 * and all targets of -relations- with dossier objects.
	 *
	 * @param string $user
	 * @param string $objId    Child object ID
	 * @param string $parentId Parent object ID (about to get Placed relation)
	 * @param string $parentType Parent object Type
	 * @param string $action   Workflow ActionType.
	 * @param bool	 $isPlaced
	 * @param bool 	 $isContained
	 * @return array of ObjectTargetsInfos objects
	 */
	protected static function getRelatedTargetsInfos( $user, $objId, $parentId, $parentType, $action, &$isPlaced, &$isContained )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		$ret = array(); // list of ObjectTargetsInfo

		// == Determine wether the parent object is a dossier or layout ==
		$dossierType = $layoutType = false;

		$dossierTypes = array('Dossier', 'DossierTemplate');
		$layoutTypes = array('Layout', 'LayoutTemplate', 'LayoutModule', 'LayoutModuleTemplate', 'PublishForm', 'PublishFormTemplate');

		if(in_array($parentType, $dossierTypes)) {
			$dossierType = true;
		} elseif(in_array($parentType, $layoutTypes)) {
			$layoutType = true;
		}
		// =====

		// Alien objects cannot have relations, so we can bail out:
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		// Exclude alien objects because if you send the id to the database you get sql errors
		if( ( !empty( $objId ) && BizContentSource::isAlienObject( $objId ) ) || $action == 'CopyTo' ) { // CopyTo: BZ#16971
			$isPlaced = ($parentId > 0 && $layoutType);
			$isContained = ($parentId > 0 && $dossierType);
			if( $action == 'CopyTo' ) {
				LogHandler::Log( 'GetDialog', 'INFO', 'Skipped resolving relational targets because it is a Copy To dialog.' );
			} else {
				LogHandler::Log( 'GetDialog', 'INFO', 'Skipped resolving relational targets because object is an alien.' );
			}
			return $ret;
		}

		// Create a parents object
		$parents = array();

		// Get relational target with Dossiers
		$curRelated = 0;
		$maxRelated = 5; // get max 5 objects (to avoid huge performance hit)
		$rows = !empty($objId) ? DBObjectRelation::getObjectRelations( $objId, 'parents', 'Contained' ) : array();
		$isContained = (count($rows) > 0) || ($parentId > 0 && $dossierType);

		if( !empty($rows) ) {
			foreach( $rows as $row ) {
				$parents[$row['parent']] = true;
				$relTargets = DBTarget::getTargetsbyObjectrelationId( $row['id'] );
				if( count($relTargets) > 0 ) {
					$curRelated++;
					if( $curRelated > $maxRelated ) { // Reached limit?
						break; // stop here!
					}
					try {
						$relObj = BizObject::getObject( $row['parent'], $user, false, 'none', array() ); // do NOT get object targets!
						$ret[] = new ObjectTargetsInfo( $relObj->MetaData->BasicMetaData, $relTargets );
					} catch( BizException $e ) { // Let's be silent. User could have no File Access to the parent (BZ#16772)
						$ret[] = new ObjectTargetsInfo( new BasicMetaData( null, null, $e->getMessage() ), null );
					}
				}
			}
		}

		// If the parentId is set and the curRelated is smaller than maxRelated, get the parentId targets
		if( $parentId > 0 && $dossierType && $curRelated < $maxRelated ) {
			// Check if the parentId not in parents array. If it is the targets are already fetched.
			if( !isset($parents[$parentId]) ) {
				$curRelated++;
				try {
					$relObj = BizObject::getObject( $parentId, $user, false, 'none', array('Targets') ); // No relation yet, so get the targets by getObject
					$ret[] = new ObjectTargetsInfo( $relObj->MetaData->BasicMetaData, $relObj->Targets );
				} catch( BizException $e ) { // Let's be silent. User could have no File Access to the parent (BZ#16772)
					$ret[] = new ObjectTargetsInfo( new BasicMetaData( null, null, $e->getMessage() ), null );
				}
			}
		}

		// When limit exceeded, add a dummy placeholder to let user know there are more parents
		if( $curRelated > $maxRelated ) {
			$ret[] = new ObjectTargetsInfo( new BasicMetaData( null, null, '...' ), null );
		}

		// Clear the parents array, use it now for the placed objects
		$parents = array();

		// Get targets of layouts on with the object is placed
		$rows = !empty($objId) ? DBObjectRelation::getObjectRelations( $objId, 'parents', 'Placed' ) : array();
		$isPlaced = (count($rows) > 0) || ($parentId > 0 && $layoutType);
		if( !empty($rows) ) {
			foreach( $rows as $row ) {
				$parents[$row['parent']] = true;
			}
		}
		if( $parentId > 0 && $layoutType ) {
			$parents[$parentId] = true;
		}
		$curPlaced = 0;
		$maxPlaced = 5; // get max 5 objects (avoid huge performance hit)
		foreach( array_keys($parents) as $parId ) {
			$curPlaced++;
			if( $curPlaced > $maxPlaced ) { // Reached limit?
				break; // stop here!
			}
			try {
				$parObj = BizObject::getObject( $parId, $user, false /*lock*/, 'none', array('Targets') );
				$ret[] = new ObjectTargetsInfo( $parObj->MetaData->BasicMetaData, $parObj->Targets );
			} catch( BizException $e ) { // Let's be silent. User could have no File Access to the parent (BZ#16772)
				$ret[] = new ObjectTargetsInfo( new BasicMetaData( null, null, $e->getMessage() ), null );
			}
		}
		// When limit exceeded, add a dummy placeholder to let user know there are more parents
		if( $curPlaced > $maxPlaced ) {
			$ret[] = new ObjectTargetsInfo( new BasicMetaData( null, null, '...' ), null );
		}

		return $ret;
	}

	/**
	 * Validates wether a object can have multiple targets.
	 * Layout, LayoutTemplate, LayoutModule and LayoutModuleTemplate can only have one target
	 * assigned.
	 *
	 * Throws BizException in case of not allowed number of targets
	 *
	 * @param MetaData $meta
	 * @param array $targets
	 * @throws BizException
	 */
	public static function validateMultipleTargets( $meta, $targets )
	{
		$types = array('Layout', 'LayoutTemplate', 'LayoutModule', 'LayoutModuleTemplate', 'PublishForm', 'PublishFormTemplate');
		if( in_array( $meta->BasicMetaData->Type, $types ) ) {
			if( count($targets) > 1 ) {
				throw new BizException('ERR_ONE_ISSUE_ALLOWED', 'Server', '');
			}
		}
	}

	/**
	 * Get the dialog for publishing. This is used for the actions: PublishDossier, UpdateDossier and UnPublishDossier.
	 *
	 * @param string $objId
	 * @param string $iss
	 * @param string $action
	 * @throws BizException
	 * @return array with the dialog
	 */
	private static function getPublishDialog($objId, $iss, $action)
	{
		if(empty($objId)) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'GetDialog service: '.
				'When the Action parameter is "PublishDossier", "UnPublishDossier" or "UpdateDossier", the object ID parameter should not be nil! ');
		}

		if(empty($iss)) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'GetDialog service: '.
				'When the Action parameter is "PublishDossier", "UnPublishDossier" or "UpdateDossier", the issue ID parameter should not be nil! ');
		}

		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$channel = BizPublication::getChannelForIssue($iss);
		$errorWidgets = $warningAndInfoWidgets = array();
		self::validateObjectForPublishing($action, $objId, $channel->Id, $iss, $errorWidgets, $warningAndInfoWidgets);

		$default = self::getDefaultPublishDialog();

		$title = '';
		switch ($action) {
			case "PublishDossier":
				$title = BizResources::localize('ACT_PUBLISH');
				break;
			case "UnPublishDossier":
				$title = BizResources::localize('ACT_UNPUBLISH');
				break;
			case "UpdateDossier":
				$title = BizResources::localize('ACT_UPDATE');
				break;
		}

		$dialog = new Dialog($title);
		$dialog->Tabs = array(new DialogTab(BizResources::localize('TAB_GENERAL'), (isset($warningAndInfoWidgets['widgets']) ? $warningAndInfoWidgets['widgets'] : array()), '' ));
		// If there are errors create a new special tab with the title ERRORS and set the errorWidgets.
		if(!empty($errorWidgets)) {
			// Set the ERRORS tab as the first one in the tabs array
			array_unshift($dialog->Tabs, new DialogTab('ERRORS', $errorWidgets['widgets'], ''));
		}

		$errorMessages = (isset($errorWidgets['messages'])) ? $errorWidgets['messages'] : array();
		$warningAndInfoMessages = (isset($warningAndInfoWidgets['messages'])) ? $warningAndInfoWidgets['messages'] : array();
		// Create empty metadata values for the 'errors' and 'warnings'
		$metadata = array();
		$allMessages = array_merge( $errorMessages, $warningAndInfoMessages );
		if(isset($allMessages)) {
			foreach($allMessages as $key => $messages) {
				// BZ#30280 Create an PropertyValue object per message
				$values = array();
				foreach ( $messages as $message ) {
					$values[] = new PropertyValue( $message );
				}
				$metadata[] = new MetaDataValue($key, null, $values);
			}
		}
		$dialog->MetaData = $metadata;

		$default['Dialog'] = $dialog;

		return $default;
	}

	/**
	 * Returns the dialog for the SetPublishProperties. To get this dialog,
	 * the Publish Connector is called for the given issue/publication channel.
	 *
	 * @since 9.0.0
	 * @param int $objId Object Id (PublishForm)
	 * @param int $iss Issue Id
	 * @param bool $reqMetaData
	 * @throws BizException
	 * @return array
	 */
	private static function getSetPublishPropertiesDialog($objId, $iss, $reqMetaData)
	{
		if(empty($objId)) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'GetDialog service: '.
				'When the Action parameter is "SetPublishProperties", the object ID parameter should not be nil! ');
		}

		if(empty($iss)) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'GetDialog service: '.
				'When the Action parameter is "SetPublishProperties", the issue ID parameter should not be nil! ');
		}

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		$object = BizObject::getObject( $objId, BizSession::getShortUserName(), false, 'none');
		if ( !BizPublishForm::isPublishForm($object) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'GetDialog service: '.
				'When the Action parameter is "SetPublishProperties", the object ID parameter should be a PublishForm object! ');
		}

		$parentId = BizObject::getInstanceOfRelationalParentId( $object );
		$parent = BizObject::getObject( $parentId, BizSession::getShortUserName(), false, 'none');

		list( $publishSystem, $templateId ) = BizObject::getPublishSystemAndTemplateId(null, $object->Relations);

		$properties = BizProperty::getFullPropertyInfos(null, null, null, $publishSystem, $templateId);

		// StandardProperties are needed when we have standard object properties in our widgets in widgets in widgets.
		// These properties will be resolved when handling the widgets in widgets in widgets (If needed).
		$standardProperties = null;

		// Retrieve a dialog data from the database.
		$dialogAction = 'SetPublishProperties';
		$objectType = 'PublishFormTemplate';

		$wiwiwUsages = array();
		$customActionProperties = BizProperty::getPropertyUsages(0, $objectType, $dialogAction, false, false,
			$parent->MetaData->BasicMetaData->DocumentID, $wiwiwUsages, false );

		// Create a dialog.
		require_once BASEDIR.'/server/utils/PublishingUtils.class.php';

		$dialog = WW_Utils_PublishingUtils::getDefaultPublishingDialog( $parent->MetaData->BasicMetaData->Name );
		$metaDataValues = $dialog->MetaData;

		// Add any default created tabs to our temp array;
		$tabs = array();

		require_once BASEDIR.'/server/dbclasses/DBActionproperty.class.php';
		if (!empty($customActionProperties) && !empty($properties)){
			$widgetsInWidgets = array();
			$widgetsInWidgetsInWidgets = array();
			$mainWidgets = array();
			foreach ($customActionProperties as $key => $propertyUsage) {
				foreach ($properties as $propertyInfo) {
					if ($key == $propertyInfo->Name) {
						$widget = self::getDialogWidget( $propertyInfo, $propertyUsage );

						if (!isset($tabs[$propertyInfo->Category])) {
							$tabs[$propertyInfo->Category] = WW_Utils_PublishingUtils::getPublishingTab($propertyInfo->Category);
						}

						if (!isset($mainWidgets[$propertyInfo->Category])) {
							$mainWidgets[$propertyInfo->Category] = array();
						}

						if (!isset($widgetsInWidgets[$propertyInfo->Category])) {
							$widgetsInWidgets[$propertyInfo->Category] = array();
						}

						// Determine the 'position' of the widget:
						// Either the most top level of Widget, Widget in a widget or Widget in a widget in a widget.
						if (empty($propertyUsage->ParentFieldId)) {
							// Most top level widget (mainWidget)
							$widget->PropertyInfo->Widgets = null;
							$mainWidgets[$propertyInfo->Category][$propertyUsage->Id] = $widget;
						} else {
							// Widget in widget (wiw)
							$widget->PropertyInfo->Widgets = array();

							if (!isset($mainWidgets[$propertyInfo->Category][$propertyUsage->ParentFieldId])) {
								$mainWidgets[$propertyInfo->Category][$propertyUsage->ParentFieldId] = array();
							}

							$widgetsInWidgets[$propertyInfo->Category][$propertyUsage->ParentFieldId][] = $widget;

							// Widget in widget in widget. (wiwiw)
							$parentProp = '';
							$parentPropId = $propertyUsage->ParentFieldId;
							foreach( $customActionProperties as $propUsage ) {
								if( $propUsage->Id == $parentPropId ) {
									$parentProp = $propUsage->Name;
									break;
								}
							}
							$oneWiwiwUsages = isset( $wiwiwUsages[$parentProp][$propertyUsage->Name] )
								? $wiwiwUsages[$parentProp][$propertyUsage->Name] // The list of wiwiw usage for one widget in wiw.
								: null;

							if( !is_null( $oneWiwiwUsages )) {
								$oneWiwiwWidget = null;
								foreach ($oneWiwiwUsages as $oneWiwiwKey => $oneWiwiwPropertyUsage ) {
									// Check if we are handling a standard or custom property.
									if (BizProperty::isCustomPropertyName($oneWiwiwKey)) {
										// Go throught the custom properties to find a match for our $wiwiwUsage.
										foreach ($properties as $wiwiwPropertyInfo) {
											if ($oneWiwiwKey == $wiwiwPropertyInfo->Name) {
												$oneWiwiwWidget = self::getDialogWidget( $wiwiwPropertyInfo, $oneWiwiwPropertyUsage );
											}
										}
									} else {
										// Check if we have standard object properties at hand, otherwise fetch them.
										if (is_null($standardProperties)) {
											$standardProperties = BizProperty::getPropertyInfos();
										}

										// Go through our standard properties to find a match for the wiwiwUsage.
										if ( $standardProperties ) foreach ( $standardProperties as $standardPropertyInfoKey => $standardPropertyInfo ) {
											if ($oneWiwiwKey == $standardPropertyInfoKey) {
												$oneWiwiwWidget = self::getDialogWidget( $standardPropertyInfo, $oneWiwiwPropertyUsage );
											}
										}
									}
									if ($oneWiwiwWidget) {
										$widgetsInWidgetsInWidgets[$propertyInfo->Category][$parentPropId][] = $oneWiwiwWidget;
									}
								}
							}
						}

						// Extract the MetaData.
						if( isset($object->MetaData->ExtraMetaData) ) foreach( $object->MetaData->ExtraMetaData as $extraMetaData ) {
							if( $widget->PropertyInfo->Name == $extraMetaData->Property ) {
								$metaDataValue = new MetaDataValue();
								$metaDataValue->Property = $extraMetaData->Property;
								$metaDataValue->Values = $extraMetaData->Values; // array of string
								$metaDataValues[] = $metaDataValue;
								break; // found
							}
						}
					}
				}
			}

			// Resolve WidgetsInWidgetsInWidgets to the WidgetsInWidgets.
			foreach( $widgetsInWidgetsInWidgets as $tab => $subWidgetEntry ) {
				foreach( $subWidgetEntry as $parentId => $widgets ) { // $widgets ==> Widgets in the widgets in the widgets.
					foreach( $widgetsInWidgets[$tab][$parentId] as $widgetInWidget ) {
						$widgetInWidget->PropertyInfo->Widgets = $widgets;
					}
				}
			}

			// Resolve WidgetsInWidgets to the main Widgets.
			foreach ($widgetsInWidgets as $tab => $subWidgetEntry) {
				foreach ($subWidgetEntry as $parentId => $widgets) { // $widgets ==> Widgets in the widget.
					foreach ($widgets as $widget) {
						$mainWidgets[$tab][$parentId]->PropertyInfo->Widgets[] = $widget;
					}
				}
			}

			// Add the main widgets to the tabs.
			foreach ($mainWidgets as $category => $widgetArray) {
				// Adding subwidgets probably will mess up the order, so ksort to retain the order of action property creation.
				ksort($widgetArray);
				foreach ($widgetArray as $widget) {
					$tabs[$category]->Widgets[] = $widget;
				}
			}
		}
		// Add the tabs to the dialog.
		$dialog->Tabs = array();
		foreach ($tabs as $tab) {
			// Set the focus on the first Widget in a tab.
			if (isset($tab->Widgets) && count($tab->Widgets) > 0 ) {
				$focus = $tab->Widgets[0]->PropertyInfo->Name;
				$tab->DefaultFocus = $focus;
			}

			$dialog->Tabs[] = $tab;
		}

		$dialog->MetaData = $metaDataValues;

		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$channel = BizPublication::getChannelForIssue($iss);

		// To fill in the Suggestion provider.
		// Before creating the button, enrich the SuggestionProvider in the widget first if applicable.
		// Whether to show SuggestionRefresh button depends on whether the SuggestionProvider is set or not.
		$suggestionProviderFound = self::enrichDialogWidgetsSuggestionProviders( $objId, $channel->Id, $dialog );

		$defaultButtonBar = self::getDefaultButtonBar( $suggestionProviderFound );
		$buttonBar = BizServerPlugin::runChannelConnector( $channel->Id, 'getButtonBarForSetPublishPropertiesAction', array( $defaultButtonBar, $parent, $object ) );
		$dialog->ButtonBar = $buttonBar;

		$default = self::getDefaultPublishDialog();
		$default['Dialog'] = $dialog;
		$default['Relations'] = $object->Relations;

		// When there is a Dialog and the requestMetaData property is set to true,
		// return all the metadata from the object.
		if ( $dialog && $reqMetaData ) {
			$default['MetaData'] = $object->MetaData;
		}
		return $default;
	}

	/**
	 * Pass in the PropertyInfo and the PropertyUsage to compose the DialogWidget.
	 *
	 * @param PropertyInfo $propertyInfo
	 * @param PropertyUsage $propertyUsage
	 * @return DialogWidget
	 */
	private static function getDialogWidget( $propertyInfo, $propertyUsage )
	{
		$widget = new DialogWidget();
		$widget->PropertyInfo = $propertyInfo;
		$widget->PropertyUsage = $propertyUsage;

		return $widget;
	}

	/**
	 * Returns a list of default button for publishing operations.
	 *
	 * The default button includes button for SetPublishProperties action and
	 * RefreshSuggestion button when there's a suggestion provider on at least one of
	 * the dialog widget.
	 *
	 * @param bool $suggestionProviderFound To indicate whether to add the RefreshSuggestion button into the default button bar.
	 * @return array List of default button bar.
	 */
	private static function getDefaultButtonBar( $suggestionProviderFound )
	{
		$defaultButtonBar = self::getDefaultButtonBarForSetPublishPropertiesAction();
		if( $suggestionProviderFound ) {
			$defaultButtonBar[] = self::getDefaultButtonBarForRefreshSuggestion();
		}
		return $defaultButtonBar;
	}

	/**
	 * Returns a default array with button definitions for 'Publish', 'Update', 'UnPublish' and 'Preview' actions.
	 *
	 * @return array
	 */
	private static function getDefaultButtonBarForSetPublishPropertiesAction()
	{
		$buttonBar = array();

		$actions = array(
			'Publish'   => 'ACT_PUBLISH',
			'Update'    => 'ACT_UPDATE',
			'UnPublish' => 'ACT_UNPUBLISH',
			'Preview'   => 'ACT_PREVIEW'
		);
		foreach ( $actions as $action => $localizationKey ) {
			$propInfo = new PropertyInfo();
			$propInfo->Name = $action;
			$propInfo->DisplayName = BizResources::localize($localizationKey);
			$propInfo->Type = 'button';

			$propUsage = new PropertyUsage();
			$propUsage->Name = $action;
			$propUsage->Editable = true;
			$propUsage->Mandatory = false; // Not used in this context but should be set for the object type
			$propUsage->Restricted = false; // Not used in this context but should be set for the object type
			$propUsage->RefreshOnChange = false; // Not used in this context but should be set for the object type

			$buttonBar[] = new DialogButton( $propInfo, $propUsage );
		}

		return $buttonBar;
	}

	/**
	 * Returns a Refresh Suggestion button bar.
	 *
	 * @return DialogButton Suggestion button bar
	 */
	private static function getDefaultButtonBarForRefreshSuggestion()
	{
		$propInfo = new PropertyInfo();
		$propInfo->Name = 'RefreshSuggestions';
		$propInfo->DisplayName = BizResources::localize( 'SUGGESTIONS_BUTTON_LABEL' );
		$propInfo->Type = 'button';
		$propNotification = new PropertyNotification();
		$propNotification->Type = 'Info';
		$propNotification->Message = BizResources::localize( 'SUGGESTIONS_BUTTON_TOOLTIP' );
		$propInfo->Notifications = array( $propNotification );

		$propUsage = new PropertyUsage();
		$propUsage->Name = 'RefreshSuggestions';
		$propUsage->Editable = true;
		$propUsage->Mandatory = false; // Not used in this context but should be set for the object type
		$propUsage->Restricted = false; // Not used in this context but should be set for the object type
		$propUsage->RefreshOnChange = false; // Not used in this context but should be set for the object type

		$buttonBar = new DialogButton( $propInfo, $propUsage );

		return $buttonBar;
	}

	/**
	 * Get a default dialog for publishing. This is send back when the get Dialog for Publishing
	 * (publish, update and unpublish) isn't overruled.
	 *
	 * @return array with a default dialog
	 */
	private static function getDefaultPublishDialog()
	{
		$ret = array();

		// GetDialogResponse attributes that are not in GetDialog2Response:
		$ret['Publications'] = null;
		$ret['PublicationInfo'] = null;
		$ret['GetStatesResponse'] = null;
		$ret['Dossiers'] = null;

		// GetDialog2Response attributes:
		$ret['Dialog'] = null;
		$ret['PubChannels'] = null;
		$ret['MetaData'] = null;
		$ret['Targets'] = null;
		$ret['RelatedTargets'] = null;
		$ret['Relations'] = null;

		return $ret;
	}

	/**
	 * Validates a object via the channel plugin.
	 *
	 * @param string $type
	 * @param int $dossierId
	 * @param int $channelId
	 * @param int $issueId
	 * @param array $errorWidgets for the errorWidgets (referenced)
	 * @param array $warningAndInfoWidgets for the warningAndInfoWidgets (referenced)
	 */
	private static function validateObjectForPublishing($type, $dossierId, $channelId, $issueId, &$errorWidgets, &$warningAndInfoWidgets)
	{
		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		$errors = BizPublishing::validateDossierForPublishing( $channelId, array($type, $dossierId, $issueId));

		$errorTypes = array('errors' => BizResources::localize('ERR_GENERAL_ERROR'), 'warnings' => BizResources::localize('WARNING'), 'infos' => BizResources::localize('INFORMATION'));

		foreach($errorTypes as $errorType => $translation) {
			if(isset($errors[$errorType]) && !empty($errors[$errorType])) {
				$name = strtoupper($errorType);
				$widget = new DialogWidget(
					new PropertyInfo($name, $translation, null, 'string', '' ),
					new PropertyUsage($name, false, false, false ));

				$messages = array();
				foreach($errors[$errorType] as $message) {
					$messages[] = $message;
				}

				if(!empty($messages)) {
					if($errorType == "errors") {
						$errorWidgets['widgets'][$name] = $widget;
						$errorWidgets['messages'][$name] = $messages;
					} else {
						$warningAndInfoWidgets['widgets'][$name] = $widget;
						$warningAndInfoWidgets['messages'][$name] = $messages;
					}
				}
			}
		}
	}

	/**
	 * Checks if an objecttype can issueless. This means that an object of this type
	 * can have no object target. E.g an article can be created without assigning
	 * it to an issue.
	 * @param string $objectType Object type to be checked.
	 * @return boolean Can be issueless (true/false)
	 */
	public static function canBeIssuelessObject($objectType)
	{
		static $canBeIssuelessTypes = array('Article' => true, 'Spreadsheet' => true, 'Image' => true, 'Advert' => true, 'Audio' => true, 'Video' => true);

		$result = isset($canBeIssuelessTypes[$objectType]) ? $canBeIssuelessTypes[$objectType] : false;

		return $result;
	}

	/**
	 * Checks whether the given template object type is the is compatible with the given object type.
	 * In other terms, it tells if the object can be derived/created from the template.
	 *
	 * @param string $templateType
	 * @param string $objectType
	 * @return boolean
	 */
	private static function isTemplateTypeCompatibleWithObjectType( $templateType, $objectType )
	{
		return
			( $objectType == 'Article'       		&& $templateType == 'ArticleTemplate' )      ||
			( $objectType == 'ArticleTemplate'		&& $templateType == 'ArticleTemplate' )      ||
			( $objectType == 'Layout'        		&& $templateType == 'LayoutTemplate' )       ||
			( $objectType == 'LayoutTemplate'		&& $templateType == 'LayoutTemplate' )       ||
			( $objectType == 'LayoutModule'  		&& $templateType == 'LayoutTemplate' )       ||
			( $objectType == 'Layout'        		&& $templateType == 'LayoutModuleTemplate' ) ||
			( $objectType == 'LayoutModule'  		&& $templateType == 'LayoutModuleTemplate' ) ||
			( $objectType == 'LayoutModuleTemplate'	&& $templateType == 'LayoutModuleTemplate' ) ||
			( $objectType == 'Dossier'       		&& $templateType == 'DossierTemplate' )      ||
			( $objectType == 'PublishForm'   		&& $templateType == 'PublishFormTemplate')   ||
			( $objectType == 'Spreadsheet'   		&& $templateType == 'Spreadsheet')           || // There's no template object type but there's template file format.
			( $objectType == 'Advert'        		&& $templateType == 'AdvertTemplate');
	}

	/**
	 * Get the MetaData Value of a property from metadata
	 *
	 * @param array $metaData Array of MetaDataValue object
	 * @param string $prop Property name
	 * @return string $value
	 * @since v8.0
	 */
	public static function getMetaDataValue( $metaData, $prop )
	{
		$value = null;
		if( $metaData ) {
			foreach( $metaData as $metaDataValue ) {
				if( $metaDataValue->Property == $prop ) {
					$value = $metaDataValue->PropertyValues[0]->Value;
					break;
				}
			}
		}
		return $value;
	}

	/**
	 * Set the MetaData value for a property
	 *
	 * @param array $metaData Array of MetaDataValue object
	 * @param string $prop Property name
	 * @param string $value
	 * @since v8.0
	 */
	public static function setMetaDataValue( $metaData, $prop, $value )
	{
		foreach( $metaData as $metaDataValue ) {
			if( $metaDataValue->Property == $prop ) {
				$metaDataValue->PropertyValues[0]->Value = $value;
				break;
			}
		}
	}

	/**
	 * Adds a disabled routeTo user to the states.
	 *
	 * The states only contain active users. But if an object is routed to a deactivated user we add this user so
	 * it will be available in the drop-down list.
	 * @param string $routeTo short user name of the RouteTo user.
	 * @param  WflGetStatesResponse|null $states
	 */
	private static function addDisabledRouteTo( $routeTo, $states )
	{
		if ( $routeTo && $states ) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$userRow = DBUser::getUser( $routeTo );
			if ( $userRow['disable'] == 'on' ) {
				$user = new User();
				$user->UserID = $userRow['id'];
				$user->FullName = $userRow['fullname'];
				$trackChangesColor = $userRow['trackchangescolor'];
				if ( strlen( $trackChangesColor ) > 0 ) {
					$trackChangesColor = substr( $trackChangesColor, 1 );
				} else {
					$trackChangesColor = substr( DEFAULT_USER_COLOR, 1 );
				}
				$user->TrackChangesColor = $trackChangesColor;
				$states->RouteToUsers[] = $user;
			}
		}
	}

	private static function objectIsCreated( $action )
    {
        $createActions = array( 'Create', 'CopyTo' );
        return in_array( $action, $createActions) ?  true : false;
    }

	/**
	 * Returns a list of actions that support Multiple objects from the Workflow world.
	 *
	 * @since 10.x.x
	 * @return string[]
	 */
	public static function getMultiObjectsAllowedActions()
	{
		return array( 'SetProperties', 'SendTo' );
	}
}
