<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR."/server/apps/functions.php";
require_once BASEDIR.'/server/bizclasses/PubMgr.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDateTimeField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDiffTimeField.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';

$ticket = checkSecure('publadmin');
$dbh = DBDriverFactory::gen();

// Start the session to save the ticket in BizSession
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
BizSession::startSession( $ticket );

// first handle re-orders (if any)
$recs = isset($_REQUEST['recs_section']) ? intval($_REQUEST['recs_section']) : 0;
if ($recs > 0) {
	for ($i = 1; $i < $recs; $i++) {
		$row = array( 'code' => intval($_REQUEST["section_code$i"]) );
		$where = '`id` = ?';
		$params = array( intval($_REQUEST["section_order$i"]) );
		DBBase::updateRow( 'publsections', $row, $where, $params );
	}
}
$recs2 = isset($_REQUEST['recs_edition']) ? intval($_REQUEST['recs_edition']) : 0;
if ($recs2 > 0) {
	for ($i = 1; $i < $recs2; $i++) {
		$row = array( 'code' => intval($_REQUEST["edition_code$i"]) );
		$where = '`id` = ?';
		$params = array( intval($_REQUEST["edition_order$i"]) );
		DBBase::updateRow( 'editions', $row, $where, $params );
	}
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0; // issue id
$del = isset($_REQUEST['del']) ? intval($_REQUEST['del']) : 0;

// determine incoming mode
if (isset($_REQUEST['vupdate']) && $_REQUEST['vupdate']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['delsection'])) {
	$mode = 'delsection';
} else if (isset($_REQUEST['delauthor'])) {
	$mode = 'delauthor';
} else if (isset($_REQUEST['delroute'])) {
	$mode = 'delroute';
} else if (isset($_REQUEST['delsectionmapping'])) {
	$mode = 'delsectionmapping';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}

// If there is a change of the select for the section mapping set the mode to 'changedselection' so nothing is updated
if($mode == 'update' || $mode == 'insert') {
	if(isset($_REQUEST['changedselection']) && !empty($_REQUEST['changedselection'])) {
		$mode = 'changedselection';
	}
}

assert( $id > 0 || $mode == 'new' || $mode == 'insert' || ($del > 0 && $mode == 'delete') || $mode == 'changedselection');
$recalc = isset($_REQUEST['butRecalc'])  ? $_REQUEST['butRecalc'] : '';
if ($recalc) $mode = 'recalc';

if( $id > 0 || ($del > 0 && $mode == 'delete') ) {
	// Derive channel and pub from issue id using DB
	// TODO: call admin services (instead of calling DB layer)
	require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
	require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
	$channelid = DBIssue::getChannelId( $id );
	$publ = DBChannel::getPublicationId( $channelid );
	$channelRow = DBChannel::getChannel( $channelid );
	$channelType = $channelRow['type'];
} else { // when no issue id is given, channel and pub are required
	$publ = intval($_REQUEST['publ']); // mandatory
	$channelid = intval($_REQUEST['channelid']); // mandatory
}

// Get the channel information and if the publishsystem equals Drupal then turn on the section_mapping;
// TODO: Ugly hack for sections mapping (e.g. hardcoded with drupal)
require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
$channel = DBChannel::getChannel($channelid);
if($channel['publishsystem'] == 'Drupal') {
	$_REQUEST['section_mapping'] = true;
}

assert( $publ > 0 );
assert( $channelid > 0 );

// check publication rights
checkPublAdmin($publ);

$errors = $validateErrors = array();
$app = new IssueMaintenanceApp();
try {
	$issueObj = new AdmIssue();
	$app->buildIssueObj( $issueObj, $ticket, $publ, $channelid, $id, $errors );
} catch( BizException $e ) {
	$errors[] = addslashes( $e->getMessage() );
	$mode = 'error';
}

// copy has it's own source
if( isset($_REQUEST["bt_copy"]) && $mode != 'error') {
	$publ = intval($_REQUEST['publ']); // mandatory
	header ("Location: duplicate_issue.php?publ=$publ&issue={$issueObj->Id}&issueName=".urlencode($issueObj->Name));
	exit;
}

// to do cleanUp for the selected Issue
if( isset($_REQUEST['bt_clean'])) {
	$publ = intval($_REQUEST['publ']);
	header ("Location: removeissue.php?Publication=$publ&Issue=$id"); 
}

// check deadlines
if ($mode == 'update' || $mode == 'insert' || $mode == 'recalc') {
	try {
		$app->validateDeadlines( $issueObj, $recalc );
	} catch( BizException $e ) {
		$validateErrors[$e->getDetail()] = $e->getMessage();
		$mode = 'error';
	}
}

// handle request
switch ($mode)
{
	case 'update':
		try {
			require_once BASEDIR.'/server/services/adm/AdmModifyIssuesService.class.php';
			$service = new AdmModifyIssuesService();
			$request = new AdmModifyIssuesRequest();
			$request->Ticket        = $ticket;
			$request->RequestModes  = array();
			$request->PublicationId = $publ;
			$request->PubChannelId = $channelid;
			$request->Issues = array( $issueObj );
			$response = $service->execute($request);
			$issueObj = $response->Issues[0]; // We only get one issue back
		} catch( BizException $e ) {
			$errors[] = addslashes( $e->getMessage() );
			$mode = 'error';
			break;
		}
		break;
	case 'insert':
		try {
			require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
			$service = new AdmCreateIssuesService();
			$request = new AdmCreateIssuesRequest();
			$request->Ticket        = $ticket;
			$request->RequestModes  = array();
			$request->PublicationId = $publ;
			$request->PubChannelId = $channelid;
			$request->Issues = array( $issueObj );
			$response = $service->execute($request);
			$issueObj = $response->Issues[0]; // We only get one issue back
		} catch( BizException $e ) {
			$errors[] = addslashes( $e->getMessage() );
			$mode = 'error';
			break;
		}
		break;
	case 'delete':
		if( $del > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
				$service = new AdmDeleteIssuesService();
				$request = new AdmDeleteIssuesRequest();
				$request->Ticket = $ticket;
				$request->PublicationId = $publ;
				$request->IssueIds = array( $del );				
				$service->execute($request);
			} catch( BizException $e ) {
				if( stripos( $e->getMessage(), '(S1058)' ) !== false || // BZ#25559
					stripos( $e->getMessage(), '(S1057)' ) !== false ) { // in use by objects?
					header("Location: removeissue.php?Publication=$publ&Issue=$del");
					exit;
				}
				$errors[] = addslashes( $e->getMessage() );
				$mode = 'error';
			}
		}
		break;
	case 'delsection':
		if( $del > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
				$service = new AdmDeleteSectionsService();
				$request = new AdmDeleteSectionsRequest();
				$request->Ticket        = $ticket;
				$request->PublicationId = $publ;
				$request->IssueId       = null;
				$request->SectionIds    = array( $del );
				$service->execute( $request );				
			} catch( BizException $e ) {
				if( stripos( $e->getMessage(), '(S1058)' ) !== false || // BZ#25559
					stripos( $e->getMessage(), '(S1057)' ) !== false ) { // in use by objects?
					header("Location: removesection.php?Publication=$publ&Section=$del");
					exit;
				}
				$errors[] = addslashes( $e->getMessage() );
				$mode = 'error';
			}
		}
		break;
	case 'delauthor':
		if( $issueObj->Id > 0 && $del > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';
				$request = new AdmDeleteWorkflowUserGroupAuthorizationsRequest();
				$request->Ticket = $ticket;
				$request->PublicationId = $publ;
				$request->IssueId = $id;
				$request->UserGroupId = $del;
				$service = new AdmDeleteWorkflowUserGroupAuthorizationsService();
				$service->execute( $request );
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
				$mode = 'error';
			}
		}
		break;
	case 'delroute':
		if( $issueObj->Id > 0 && $del > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmDeleteRoutingsService.class.php';
				$request = new AdmDeleteRoutingsRequest();
				$request->Ticket = $ticket;
				$request->IssueId = $issueObj->Id;
				$request->SectionId = $del;
				$request->RoutingIds = array( $del );
				$service = new AdmDeleteRoutingsService();
				$service->execute( $request );
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
				$mode = 'error';
			}
		}
		break;
	case 'delsectionmapping':
		if( $issueObj->Id > 0 && $del > 0 ) {
			$where = '`section` = ? AND `issue` = ?';
			$params = array( 
				intval( $del ), // section id
				intval( $issueObj->Id )
			);
			DBBase::deleteRows( 'channeldata', $where, $params );
		}
		break;
}

// delete: back to overview
if ( $mode == 'delete') {
	header("Location:editChannel.php?publid=$publ&channelid=$channelid");
	exit;
}

if( $mode != 'new' && $mode != 'error' && $mode != 'recalc' && $mode != 'update' && $mode != 'insert') {
	$issueObj = $app->getIssueObj( $ticket, $publ, $channelid, $issueObj->Id );
}

// resolve brand and channel
try {
	require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
	$request = new AdmGetPublicationsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$request->PublicationIds = array( $publ );
	$service = new AdmGetPublicationsService();
	$response = $service->execute( $request );
	$pubObj = $response->Publications[0];
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

try {
	require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';
	$request = new AdmGetPubChannelsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$request->PublicationId = $publ;
	$request->PubChannelIds = array( $channelid );
	$service = new AdmGetPubChannelsService();
	$response = $service->execute( $request );
	$channelObj = $response->PubChannels[0];
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

// build form
$action = ($mode == 'new') ? 'Create' : 'Update';
$txt = $app->buildIssueForm( $pubObj, $channelObj, $issueObj, $action, $validateErrors );

// add common + hidden fields
$txt = str_replace('<!--VAR:PUBL-->', formvar($pubObj->Name).inputvar( 'publ', $publ, 'hidden' ), $txt );
$txt = str_replace('<!--VAR:CHANNEL-->', formvar($channelObj->Name).inputvar( 'channelid', $channelid, 'hidden' ), $txt );
$txt = str_replace('<!--VAR:HIDDEN-->', inputvar( 'id', $issueObj->Id, 'hidden' ), $txt );
$txt = str_replace('<!--PUBLID-->', $publ, $txt);
$txt = str_replace('<!--CHANNELID-->', $channelid, $txt);

// build lower part forms if overrule pub is turned on (otherwise we don't allow to configure these things on issue level)
if( $mode != "new" && $issueObj->OverrulePublication ) {
	$detailtxt = $app->buildOverruleBrandForms( $ticket, $issueObj->Id, $publ, $channelid, $errors );
} else {
	$detailtxt = '';
}
$txt = str_replace("<!--DETAILS-->", $detailtxt, $txt);

// add Copy & CleanUp button
$butCopy = '';
$butClean = '';
if( $mode != 'new' && $mode != 'error' ) {
	$butCopy = '<input type="submit" name="bt_copy" value="'.BizResources::localize("ACT_COPY").'">';
	$butClean = '<input type="submit" name="bt_clean" value="'.BizResources::localize("ACT_CLEAN_UP").'">';
}
$txt = str_replace("<!--BUT:COPY-->", $butCopy, $txt);
$txt = str_replace("<!--BUT:CLEAN-->", $butClean, $txt);

// set user input focus to the issue name field
$focus = 'Issue_Name';
// If it is a change selection of the section mapping, then set the focus to the selection box sectionmapping
if(isset($_REQUEST['changedselection']) && !empty($_REQUEST['changedselection'])) {
	$focus = 'sectionmapping';
}
$txt .= "<script language='javascript'>document.forms[0].$focus.focus();</script>";

// raise errors if any
$err = count($errors) > 0 ? 'onLoad="javascript:alert(\''.implode('\n',$errors).'\')"' : ''; // \n is literal for JavaScript

// show html page
print HtmlDocument::buildDocument( $txt, true, $err );

class IssueMaintenanceApp
{
	/**
	 * Creates new AdmIssue object and sets all its properties.
	 * When initially loading the form for a new admin object, default properties are taken.
	 * When initially loading the form for an existing admin object, properties are retrieved from DB.
	 * When user posts typed/changed data, properties are retrieved from HTTP params ($_REQUEST).
	 *
	 * @param AdmIssue $issueObj The issue object to be created.
	 * @param string $ticket The user's session ticket.
	 * @param integer $publ Publication id.
	 * @param integer $channelid Channel id.
	 * @param integer $id Issue id.
	 * @param array &$errors List of errors.
	 * @throws BizException On DB error.
	 * @return AdmIssue
	 */
	public function buildIssueObj( $issueObj, $ticket, $publ, $channelid, $id, array &$errors )
	{
		$issueObj->Id = $id;
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$prefix = 'Issue_'; // prefix for form property names
		$entity = 'Issue';
		$newObject = ($id == 0); // TRUE: creating new admin object, FALSE: updating existin admin object
		
		// TRUE: first time building the form or changed the selection for section mapping, FALSE: user does submit typed changes
		$firstCall = ( !isset($_REQUEST['Issue_Name']) || ( isset($_REQUEST['changedselection']) && !empty($_REQUEST['changedselection']) ) );

		// resolve brand and channel
		try {
			require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
			$request = new AdmGetPublicationsRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->PublicationIds = array( $publ );
			$service = new AdmGetPublicationsService();
			$response = $service->execute( $request );
			$pubObj = $response->Publications[0];
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
		}

		try {
			require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';
			$request = new AdmGetPubChannelsRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->PublicationId = $publ;
			$request->PubChannelIds = array( $channelid );
			$service = new AdmGetPubChannelsService();
			$response = $service->execute( $request );
			$channelObj = $response->PubChannels[0];
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
		}
		
		require_once BASEDIR.'/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php'; // AdminProperties_Context
		$context = new AdminProperties_Context();
		$context->setPublicationContext( $pubObj, $channelObj, $issueObj, null, null );

		if( $newObject ) { // user is creating new admin object
			if( $firstCall ) { // loading initial form
				BizAdmProperty::buildDefaultAdmObj( $issueObj, $entity, $prefix, $context );
			} else { // user submit typed/changed data
				BizAdmProperty::buildAdmObjFromHttp( $issueObj, $entity, $prefix, $context );
			}
		} else { // user is updating existing admin object
			if( $firstCall ) { // loading initial form
				$issueObj = $this->getIssueObj( $ticket, $publ, $channelid, $id );
			} else { // user submits typed/changed data
				BizAdmProperty::buildAdmObjFromHttp( $issueObj, $entity, $prefix, $context );
			}
		}

		// deadline stuff
		if (empty($_REQUEST['lastchanged']) && empty($_REQUEST['Issue_Deadline_date'])) {
			$_REQUEST['lastchanged'] = 'reldeadline';
		}
		$lastchanged = isset($_REQUEST['lastchanged']) ? $_REQUEST['lastchanged'] : '';
		if ($lastchanged == 'reldeadline') {
			$reldeadline_field = new HtmlDiffTimeField(null, 'reldeadline1');
			$issueObj->Deadline = DateTimeFunctions::calcTime( $issueObj->PublicationDate, -$reldeadline_field->requestValue() );
		}
		return $issueObj;
	}

	/**
	 * Validates the Deadline (and PublicationDate) of given issue.
	 *
	 * @param AdmIssue $issueObj
	 * @param bool $recalc Whether or not user has pressed Recalculation button.
	 * @throws BizException When not valid. Use getMessage() to show error. Use getDetails() to get name of invalid property.
	 */
	public function validateDeadlines( AdmIssue $issueObj, $recalc )
	{
		// user typed bad date
		if( $issueObj->PublicationDate === false ) {
			throw new BizException( 'INVALID_DATE', 'Client', 'PublicationDate' );
		}
		if( $issueObj->Deadline === false ) {
			throw new BizException( 'INVALID_DATE', 'Client', 'Deadline' );
		}

		// recalculate absolute deadline respecting relative deadline
		if ($recalc) {
			$reldeadline_field = new HtmlDiffTimeField(null, 'reldeadline1');
			$temp = $reldeadline_field->requestValue();
			$inpdeadlinerelative = $temp ? DateTimeFunctions::relativeDate( $temp ) : null;
			$deadlinerelative = DateTimeFunctions::validRelativeTime( $inpdeadlinerelative );
			if( $deadlinerelative ) {
				$issueObj->Deadline = DateTimeFunctions::calcTime( $issueObj->PublicationDate, -$deadlinerelative );
			} else {
				throw new BizException( 'INVALID_RELATIVE_TIME', 'Client', 'Deadline' );
			}
		} else {
			$inpdeadline = $issueObj->Deadline ? DateTimeFunctions::iso2date( $issueObj->Deadline ) : '';
			if ($inpdeadline === '') {
				$issueObj->Deadline = '';
			} else {
				$issueObj->Deadline = DateTimeFunctions::validDate( $inpdeadline );
				if( !$issueObj->Deadline ) {
					throw new BizException( 'INVALID_DATE', 'Client', 'Deadline' );
				}
			}
		}

		if( $issueObj->PublicationDate === '' && $issueObj->Deadline !== '' ) {
			throw new BizException( 'INVALID_DATE', 'Client', 'Deadline' );
		}

		if( $issueObj->Deadline > $issueObj->PublicationDate ) {
			throw new BizException( 'INVALID_DATE', 'Client', 'Deadline' );
		}
	}

	/**
	 * Dynamically build the issue maintenance form (property sheet).
	 * Server Plug-ins are requested to give their custom properties as well.
	 *
	 * @param AdmPublication $pubObj
	 * @param AdmPubChannel $channelObj
	 * @param AdmIssue $issueObj
	 * @param string $action
	 * @param array $validateErrors Errors from property validations. Keys are property names. Values are error messages.
	 * @return string HTML fragment representing the issue properties. The form itself is excluded!
	 */
	public function buildIssueForm( AdmPublication $pubObj, AdmPubChannel $channelObj, AdmIssue $issueObj, $action, $validateErrors )
	{
		// Build a list of properties (DialogWidget objects) and put them in the order how to show to end-user.
		// Server Plug-ins are requested to add their custom properties as well and are able to reorganize props.
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php'; // AdminProperties_Context
		$entity = 'Issue';
		$context = new AdminProperties_Context();
		$context->setPublicationContext( $pubObj, $channelObj, $issueObj, null, null );
		$hideWidgets = array();
		$showWidgets = BizAdmProperty::buildDialogWidgets( $context, $entity, $action, $hideWidgets );
		
		// Collect the property values to fill in at the issue form fields.
		$mdValues = BizAdmProperty::getMetaDataValues( $issueObj );

		// check if the functionality for section mapping is turned on
		$sectionMapping = isset($_REQUEST['section_mapping']) ? $_REQUEST['section_mapping'] : false;
		// create an array to store the sections
		$sections = array();
		// If the functionality is turned on get the sections for the publication (brand)
		if($sectionMapping) {
			require_once BASEDIR . '/server/dbclasses/DBSection.class.php';
			// Get the sections for the current publication
			$rows = DBSection::listSections($pubObj->Id);

			// Store the section information in the array
			if( $rows ) foreach( $rows as $row ) {
				$section = array();
				$section['id'] = $row['id'];
				$section['section'] = $row['section'];

				$sections[] = $section;
			}
		}

		// Draw all form fields (in memory as HTML string) which includes the form values, representing the issue form.
		$prefix = 'Issue_'; // prefix for form property names
		$propsHtml = '';
		require_once BASEDIR.'/server/utils/htmlclasses/XHtmlDocument.class.php';
		$doc = new Utils_XHtmlDocument();
		$form = $doc->addForm( 'myform', 'myform' );

		$sectionMappingWidgets = array();
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		foreach( $showWidgets as $widget ) {
			$propName = $widget->PropertyInfo->Name;
			if( $sectionMapping && 
				DBProperty::isCustomPropertyName( $propName ) &&
				BizAdmProperty::getPropertyInfos( 'Issue', 'Drupal', $propName  ) ) {
				$sectionMappingWidgets[] = $widget;
			} else {
				$prop = BizAdmProperty::newHtmlField( $doc, $form, $prefix, $widget );
				$found = false;
				foreach( $mdValues as $mdValue ) {
					if( $mdValue->Property == $widget->PropertyInfo->Name ) {
						$found = true;
						break; // found ($mdValue)
					}
				}
				if( $found ) {
					$prop->setValues( $mdValue->Values );
				} else {
					$prop->setValues( array($widget->PropertyInfo->DefaultValue) );
				}
				$validateError = isset($validateErrors[$widget->PropertyInfo->Name]) ? $validateErrors[$widget->PropertyInfo->Name] : '';
				$propsHtml .= $this->drawHtmlField( $issueObj, $widget, $prop, $validateError );
			}

			// Display the Time Setting (the clock) and the deadline 'clock' after the Calculate Deadline checkbox.
			if( $widget->PropertyInfo->Name == 'CalculateDeadlines' ) {
				// Add links to edit publication/issue time settings
				if( $issueObj->OverrulePublication ){
					$clock = '<a href="editPublDeadlines.php?issueid='.intval($issueObj->Id).'"><img src="../../config/images/deadline_24.gif"></img></a>';
					$propsHtml .= '<tr id="timeSettingRow"><td>'.formvar(BizResources::localize('TIMESETTINGS')).'</td><td>'.$clock.'</td></tr>'."\r\n";
				}
			}
		}

		// Collect hidden fields to, to add to form at hidden section. This way data can round-trip
		// which is typically needed for booleans; Those do not get posted when untagged. So, men can not
		// tell difference between unpresent checkbox or untagged checkbox.
		$hidePropsHtml = '';
		foreach( $hideWidgets as $widget ) {
			// Only if the property is not a custom property add this to the page. Otherwise the data cannot be saved in the channeldata table.
			$prop = BizAdmProperty::newHtmlField( $doc, $form, $prefix, $widget );
			$found = false;
			foreach( $mdValues as $mdValue ) {
				if( $mdValue->Property == $widget->PropertyInfo->Name ) {
					$found = true;
					break; // found ($mdValue)
				}
			}
			if( $found ) {
				$prop->setValues( $mdValue->Values );
			} else {
				$prop->setValues( array($widget->PropertyInfo->DefaultValue) );
			}
			$hidePropsHtml .= $this->drawHtmlField( $issueObj, $widget, $prop, '' );
		}

		// BZ#35734: The icons should be shown if the normal brand deadlines are activated and it's not an overrule issue
		// OR: It's an overrule issue (and hence ignore any normal brand settings for deadlines)
		foreach ($showWidgets as $widget) {
			if( $widget->PropertyInfo->Name == 'Deadline' ) {
				if (($pubObj->CalculateDeadlines === true && $issueObj->OverrulePublication === false) ) {
					$clock = '<a href="editIssueDeadlines.php?issueid='.intval($issueObj->Id).'"><img src="../../config/images/deadline_24.gif"></img></a>';
					$propsHtml .= '<tr><td>'.formvar(BizResources::localize('DEADLINES')).'</td><td>'.$clock.'</td></tr>'."\r\n";
					break;
				}
				elseif ($issueObj->OverrulePublication === true ) {
					$clock = '<a href="editIssueDeadlines.php?issueid='.intval($issueObj->Id).'"><img src="../../config/images/deadline_24.gif"></img></a>';
					$propsHtml .= '<tr id="deadlineRow"><td>'.formvar(BizResources::localize('DEADLINES')).'</td><td>'.$clock.'</td></tr>'."\r\n";
					break;
				}
			}
		}

		
		// if there are section mapping needed and the customWidgets array contains data save this data
		if( $sectionMapping && !empty($sectionMappingWidgets) ) {
			$newIssue = ( !isset($issueObj->Id) || empty($issueObj->Id) || $issueObj->Id <= 0 ) ? true : false;

			$sAll = BizResources::localize("LIS_ALL");

			$propsHtml .= "<tr><td colspan=\"2\">&nbsp;</tr>";
			$propsHtml .= "<tr><th colspan=\"2\">" . BizResources::localize('SECTION_MAPPING') . "</strong></th></tr>";

			$channeldataTable = $dbh->tablename('channeldata');
			$sectionsTable = $dbh->tablename('publsections');
			$sql =  'SELECT COUNT(s.`id`) AS "cnt" '.
					"FROM {$channeldataTable} ch ".
					"JOIN {$sectionsTable} s ON ch.`section` = s.`id` ".
					'WHERE ch.`issue` = ? AND ch.`section` != ? GROUP BY s.`id` ';
			$params = array(
				intval( $issueObj->Id ),
				0 // section id
			);
			$sth = DBBase::query( $sql, $params );
			$row = DBBase::fetch( $sth );
			$count = $row ? $row['cnt'] : 0;

			if( !$newIssue && $count > 0 ) {
				$propsHtml .= "<tr><td colspan=\"2\">";
				$propsHtml .= "<table border=\"0\" style=\"background-color:white;\">";
				$propsHtml .= "<tr><th>";
				$propsHtml .= BizResources::localize('SECTIONS');
				$propsHtml .= "</th></tr>";
				$propsHtml .= "<tr><td>";
				$propsHtml .= "<a href=\"javascript:setSelected(0);\">";
				$propsHtml .= "&lt;$sAll&gt;";
				$propsHtml .= "</a>";
				$propsHtml .= "</td></tr>";

				$channeldataTable = $dbh->tablename('channeldata');
				$sectionsTable = $dbh->tablename('publsections');
				$sql =  'SELECT DISTINCT s.`id`, s.`section` ';
						"FROM {$channeldataTable} ch ";
						"JOIN {$sectionsTable} s ON ch.`section` = s.`id` ";
						'WHERE ch.`issue` = ? AND ch.`section` != ? ';
				$params = array(
					intval( $issueObj->Id ),
					0 // section id
				);
				$sth = DBBase::query( $sql ,$params );
				$rows = DBBase::fetchResults( $sth );

				if( $rows ) foreach( $rows as $row ) {
					$propsHtml .= "<tr>";
					$propsHtml .= "<td>";
					$propsHtml .= "<a href=\"javascript:setSelected({$row['id']});\">";
					$propsHtml .= formvar($row['section']);
					$propsHtml .= "</a> ";
					$propsHtml .= " - ";
					$propsHtml .= "<a href=\"hppublissues.php?delsectionmapping=1&id={$issueObj->Id}&del={$row['id']}\" onclick=\"return myconfirm('delsectionmapping');\">";
					$propsHtml .= BizResources::localize('ACT_DELETE');
					$propsHtml .= "</a>";
					$propsHtml .= "</td>";
					$propsHtml .= "</tr>";
				}
				$propsHtml .= "</table>";
				$propsHtml .= "</td></tr>";
			}

			// If the object is a new object disable the select for section mapping
			$disabled = "";
			if( $newIssue ) {
				$disabled = "disabled=\"disabled\"";
			}

			$propsHtml .= "<tr><td>" . BizResources::localize('SECTION') . "</td><td>";
			$propsHtml .= "<select id=\"sectionmapping\" name=\"sectionmapping\" $disabled onchange=\"document.getElementById('changedselection').value = 'changed'; submit();\">";

			$propsHtml .= "<option value=\"\">&lt;{$sAll}&gt;</option>";
			foreach($sections as $section) {
				$selected = (isset($_REQUEST['sectionmapping']) && $_REQUEST['sectionmapping'] == $section['id']) ? 'selected="selected"' : '';
				$propsHtml .= "<option value=\"{$section['id']}\" $selected>" . formvar($section['section']) . "</option>";
			}
			$propsHtml .= "</select>";
			$value = (isset($_REQUEST['sectionmapping'])) ? $_REQUEST['sectionmapping'] : '';
			$propsHtml .= "<input type=\"hidden\" name=\"currentsectionmapping\" value=\"" . formvar($value) . "\" />";
			$propsHtml .= "<input type=\"hidden\" id=\"changedselection\" name=\"changedselection\" value=\"\" />";
			$propsHtml .= "</td></tr>";

			foreach($sectionMappingWidgets as $widget) {
				$prop = BizAdmProperty::newHtmlField( $doc, $form, $prefix, $widget );
				$mdValue = null;
				$found = false;
				foreach( $mdValues as $mdValue ) {
					$propName =  $widget->PropertyInfo->Name;
					$sectionId = (isset($_REQUEST['sectionmapping'])) ? $_REQUEST['sectionmapping'] : null;

					if( $mdValue->Property == $propName ) {
						if(!is_null($sectionId)) {
							if(empty($sectionId)) {
								if(!isset($mdValue->SectionId)) {
									$found = true;
									break;
								}
							} else {
								if(isset($mdValue->SectionId) && $mdValue->SectionId == $sectionId) {
									$found = true;
									break;
								}
							}
						} else {
							$found = true;
							break; // found ($mdValue)
						}
					}
				}
				$data = null;
				if( !empty( $mdValue->Values ) && $found ) {
					$data = $mdValue->Values;
				} else {
					require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
					$data = BizAdmProperty::getDefaultValue($widget->PropertyInfo->Type, $widget->PropertyInfo->DefaultValue);
				}
				$prop->setValues( $data );
				$validateError = isset($validateErrors[$widget->PropertyInfo->Name]) ? $validateErrors[$widget->PropertyInfo->Name] : '';
				$propsHtml .= $this->drawHtmlField( $issueObj, $widget, $prop, $validateError, 300 );
			}
		}

		// Load HTML template and insert the issue form.
		$txt = HtmlDocument::loadTemplate( 'hppublissues.htm' );
		$txt = str_replace('<!--VAR:ISSUE_PROPERTIES-->', $propsHtml, $txt );
		$txt = str_replace('<!--VAR:ISSUE_HIDDEN_PROPERTIES-->', $hidePropsHtml, $txt );
		
		return $txt;
	}

	/**
	 * Streams given Utils_XHtmlField into an HTML string that suites the issue form.
	 * It shows the property name and the input widget in two (hidden) table columns.
	 * In case of validation error, the error message is shown at second column.
	 *
	 * IMPORTANT: Please keep this function in sync with hppublications.php and ChannelForm.class.php.
	 *
	 * @param AdmIssue $issueObj
	 * @param DialogWidget $widget
	 * @param Utils_XHtmlField $htmlPropObj
	 * @param string $validateError Error message to show when validation for this property has failed.
	 * @param int $width Sets the element width
	 * @return string
	 */
	private function drawHtmlField( AdmIssue $issueObj, DialogWidget $widget, Utils_XHtmlField $htmlPropObj, $validateError, $width = 200 )
	{
		$displayName = $widget->PropertyInfo->DisplayName;
		if( $widget->PropertyInfo->Type == 'separator' ) {
			$htmlWidget = '<tr><td colspan="2">&nbsp;</td></tr>'."\r\n";
			$htmlWidget .= '<tr><th colspan="2"><br/>'.formvar($displayName).'</th></tr>'."\r\n";
		} else {
			if( $widget->PropertyInfo->Name == 'Deadline' ) {
				$htmlWidget = $this->drawHtmlDeadlineField( $issueObj, $displayName, $htmlPropObj, $validateError );
			} else {
				$htmlPropObj->setWidth( $width );
				$mandatory = $widget->PropertyUsage->Mandatory ? '*' : '';
				$htmlWidget = '<tr><td>'.formvar($displayName).$mandatory.'</td><td>'.$htmlPropObj->toString()
					.'<font color="#ff0000"><i>'.$validateError.'</i></font></td></tr>'."\r\n";
			}
		}
		return $htmlWidget;
	}

	/**
	 * Same as drawHtmlField function, but now for Deadline.
	 * This is because the Deadline is an exceptional complex widget with more to it than just a datetime.
	 * It draws the Relative Deadline widget and the special help widget along the Deadline widget itself.
	 *
	 * @param AdmIssue $issueObj
	 * @param string $displayName
	 * @param Utils_XHtmlField $htmlPropObj
	 * @param string $validateError Error message to show when validation for this property has failed.
	 * @return string
	 */
	public function drawHtmlDeadlineField( AdmIssue $issueObj, $displayName, Utils_XHtmlField $htmlPropObj, $validateError )
	{
		$relDeadline = ($issueObj->Deadline) ? DateTimeFunctions::diffIsoTimes( $issueObj->PublicationDate, $issueObj->Deadline) : 0;
		$relDeadlineField = new HtmlDiffTimeField( null, 'reldeadline1' );
		$relDeadlineField->OnChange = "setlastchanged('reldeadline');";
		$relDeadlineField->setValue($relDeadline);

		$htmlPropObj->setOnChange( "setlastchanged('deadline');" );
		return '
			<tr>
				<td valign="top"><div style="height: 18px; padding-top: 2px">'.$displayName.'</div></td>
				<td>
					<div style="float: top; width: 210px; height: 22px">
						<div style="float: left; width: 175px; height: 18px; padding-top: 2px"><i><!--RES:BEFORE_PUB_DATE-->:</i></div>
						<div style="float: left; margin-left: 4px; margin-top: 2px">
							<img src="../../config/images/sinfo_16.gif" title="Info" onclick="javascript:hideShowElement(\'DeadlineHelp\')"/>
						</div>
					</div>
					<div style="float: top; width: 210px; height: 26px">'.$htmlPropObj->toString().'</div>
					<div style="float: top; width: 210px"><font color="#ff0000"><i>'.$validateError.'</i></font></div>
					<div style="float: top; width: 210px; height: 26px">
						<nobr>
							<div style="float: left; width: 175px;">
								'.$relDeadlineField->drawBody().'
							</div>
							<div style="float: left; margin-left: 4px; margin-top: 2px">
								<input src="../../config/images/calc_16.gif" type="image" name="butRecalc" title="<!--RES:RECALC-->"/>
							</div>
						</nobr>
					</div>
				</td>
			</tr>
			<tr id="DeadlineHelp" style="display: none; white-space: normal;">
				<td>&nbsp;</td>
				<td>
					<div style="float: top; width: 186px; border: 1px; border-style: solid; background: orange; padding: 6px; font-weight: bold; color: white">
						<!--RES:DEADLINE_HELP-->
					</div>
				</td>
			</tr>';
	}

	/**
	 * When OverrulePublication option is enabled, admin user can configure statuses, sections, editions,
	 * routings and authorizations at issue level. This function draws a hidden table with all those forms
	 * in it, to show all items mentioned and let user admin do configurations.
	 *
	 * @param string $ticket
	 * @param string $id Issue id
	 * @param string $pubId Publication id
	 * @param string $channelId Channel id
	 * @param array &$errors List of errors.
	 * @return string HTML stream
	 */
	public function buildOverruleBrandForms( $ticket, $id, $pubId, $channelId, array &$errors )
	{
		// build HTML forms
		$statuses = $this->buildStatusesForm( $ticket, $id, $pubId, $errors );
		$sections = $this->buildSectionsForm( $ticket, $id, $pubId, $channelId, $errors );
		$editions = $this->buildEditionsForm( $ticket, $id, $pubId, $channelId, $errors );
		$routings = $this->buildRoutingsForm( $ticket, $id, $pubId, $errors );
		$authors  = $this->buildAuthorizationsForm( $ticket, $id, $pubId, $errors );
		$dossierTemplates = $this->buildDossierTemplatesForm( $ticket, $id, $pubId, $errors );
		// combine all forms in one HTML table
		return '
			<table>
				<tr>
					<td valign="top">'.$statuses.' ' .$routings.'</td>
					<td valign="top">'.$editions.' '.$sections.'</td>
				</tr>
				<tr>
					<td valign="top">'.$dossierTemplates.'</td>
					<td valign="top">'.$authors.'</td>
				</tr>
			</table>';
	}

	/**
	 * Draws HTML pane/form that lists all issue's statuses.
	 *
	 * @param string $ticket The user's session ticket.
	 * @param string $id Issue id
	 * @param string $pubId Publication id
	 * @param array &$errors List of errors.
	 * @return string HTML stream
	 */
	private function buildStatusesForm( $ticket, $id, $pubId, array &$errors )
	{
		$typesdomain = getObjectTypeMap();
		$detail = '';
		if( $id > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetStatusesService.class.php';
				$request = new AdmGetStatusesRequest();
				$request->Ticket = $ticket;
				$request->IssueId = $id;
				$service = new AdmGetStatusesService();
				$response = $service->execute( $request );
				$statuses = $response->Statuses;

				// Sort statuses by name, in natural order.
				if( $statuses ) {
					usort( $statuses, function( AdmStatus $statusA, AdmStatus $statusB ) {
						return strcmp( $statusA->Type, $statusB->Type );
					} );
				}

				$arr = array();
				if( $statuses ) foreach( $statuses as $status ) {
					if (!isset($arr[$status->Type]))
						$arr[$status->Type] = array ($status->Name);
					else
						$arr[$status->Type][] = $status->Name;
				}
				$color = array (" bgcolor='#eeeeee'", '');
				$flip = 0;
				foreach( array_keys($arr) as $type ) {
					$clr = $color[$flip];
					$states = implode($arr[$type], ', ');
					$detail .= "<tr$clr><td><a href='states.php?publ=$pubId&issue=$id&type=$type'>{$typesdomain[$type]}</a></td><td>".formvar($states)."</td><tr>";
					$flip = 1- $flip;
				}
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}
		$detailtxt1 = HtmlDocument::loadTemplate( 'hpissuesdetstate.htm' );
		$detailtxt1 = str_replace( '<!--ROWS-->', $detail, $detailtxt1 );
		$detailtxt1 = str_replace( '<!--PUBL-->', $pubId, $detailtxt1 );
		$detailtxt1 = str_replace( '<!--ISSUE-->', $id, $detailtxt1 );
		return $detailtxt1;
	}

	/**
	 * Draws HTML pane/form that lists all issue's sections/categories.
	 *
	 * @param string $ticket The user's session ticket.
	 * @param string $id Issue id
	 * @param string $pubId Publication id
	 * @param string $channelId Pubchannel id.
	 * @param array &$errors List of errors.
	 * @return string HTML pane
	 */
	private function buildSectionsForm( $ticket, $id, $pubId, $channelId, array &$errors )
	{
		$detail = inputvar( 'id', $id, 'hidden' );
		$detail .= inputvar( 'publ', $pubId, 'hidden' );
		if( $id > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetSectionsService.class.php';
				$request = new AdmGetSectionsRequest();
				$request->Ticket = $ticket;
				$request->RequestModes = array();
				$request->PublicationId = $pubId;
				$request->IssueId = $id;
				$service = new AdmGetSectionsService();
				$response = $service->execute( $request );
				$sections = $response->Sections;

				// Sort the sections by code, then by name in natural order.
				if( $sections ) {
					usort( $sections, function( AdmSection $sectionA, AdmSection $sectionB ) {
						if( $sectionA->SortOrder == $sectionB->SortOrder ) {
							return strnatcmp( $sectionA->Name, $sectionB->Name );
						}
						return $sectionA->SortOrder < $sectionB->SortOrder ? -1 : 1;
					} );
				}

				$color = array (" bgcolor='#eeeeee'", '');
				$cnt=1;
				if( $sections ) foreach( $sections as $section ) {
					$clr = $color[$cnt%2];
					$bx = inputvar("section_code$cnt", $section->SortOrder, "small").inputvar( "section_order$cnt", $section->Id, 'hidden' );
					$detail .=
						"<tr$clr><td><a href='hppublsections.php?publ=$pubId&issue=$id&id=$section->Id'>"
						.formvar($section->Name)."</a></td><td>$bx</td><td><a href='hppublissues.php?delsection=1&id=$id&del="
						.$section->Id
						."' onClick='return myconfirm(\"delsection\")'>"
						."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
						."</a></td><tr>";
					$cnt++;
				}
				$detail .= inputvar( 'recs_section', $cnt, 'hidden' );
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}

		$detailtxt2 = HtmlDocument::loadTemplate( 'hpissuesdetsection.htm' );
		$detailtxt2 = str_replace( '<!--ROWS-->', $detail, $detailtxt2 );
		$detailtxt2 = str_replace( '<!--PUBL-->', $pubId, $detailtxt2 );
		$detailtxt2 = str_replace( '<!--CHANNELID-->', $channelId, $detailtxt2 );
		$detailtxt2 = str_replace( '<!--ISSUE-->', $id, $detailtxt2 );
		return $detailtxt2;
	}

	/**
	 * Draws HTML pane/form that lists all issue's editions.
	 *
	 * @param string $ticket The user's session ticket.
	 * @param string $id Issue id
	 * @param string $pubId Publication id
	 * @param string $channelId Channel id.
	 * @param array &$errors List of errors.
	 * @return string HTML pane
	 */
	private function buildEditionsForm( $ticket, $id, $pubId, $channelId, array &$errors )
	{
		// editions
		$detail = inputvar( 'id', $id, 'hidden' );
		$detail .= inputvar( 'publ', $pubId, 'hidden' );
		if ($id > 0) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetEditionsService.class.php';
				$request = new AdmGetEditionsRequest();
				$request->Ticket = $ticket;
				$request->PublicationId = $pubId;
				$request->PubChannelId = $channelId;
				$request->IssueId = $id;
				$service = new AdmGetEditionsService();
				$response = $service->execute( $request );
				$editions = $response->Editions;

				// Sort the editions by code, then by name in natural order.
				if( $editions ) {
					usort( $editions, function( AdmEdition $editionA, AdmEdition $editionB ) {
						if( $editionA->SortOrder == $editionB->SortOrder ) {
							return strnatcmp( $editionA->Name, $editionB->Name );
						}
						return $editionA->SortOrder < $editionB->SortOrder ? -1 : 1;
					} );
				}

				$color = array (" bgcolor='#eeeeee'", '');
				$cnt=1;
				if( $editions ) foreach( $editions as $edition ) {
					$clr = $color[$cnt%2];
					$bx = inputvar("edition_code$cnt", $edition->SortOrder, "small").inputvar( "edition_order$cnt", $edition->Id, 'hidden' );
					$detail .=
						"<tr$clr><td><a href='hpeditions.php?publ=$pubId&issue=$id&id=$edition->Id&channelid=$channelId'>"
						.formvar($edition->Name)."</a></td><td>$bx</td><td><a href='hpeditions.php?delete=1&id="
						.$edition->Id
						."&issue=$id&publ=$pubId' onClick='return myconfirm(\"deledition\")'>"
						."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
						."</a></td><tr>";
					$cnt++;
				}
				$detail .= inputvar( 'recs_edition', $cnt, 'hidden' );

			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}
		$detailtxt2a = str_replace("<!--ROWS-->", $detail, HtmlDocument::loadTemplate( 'hpissuesdetedition.htm' ) );
		$detailtxt2a = str_replace("<!--PUBL-->", $pubId, $detailtxt2a);
		$detailtxt2a = str_replace("<!--CHANNELID-->", $channelId, $detailtxt2a);
		$detailtxt2a = str_replace("<!--ISSUE-->", $id, $detailtxt2a);
		return $detailtxt2a;
	}

	/**
	 * Draws HTML pane/form that lists all issue's routings.
	 *
	 * @param string $ticket The user's session ticket.
	 * @param string $id Issue id
	 * @param string $pubId Publication id
	 * @param array &$errors List of errors.
	 * @return string HTML stream
	 */
	private function buildRoutingsForm( $ticket, $id, $pubId, array &$errors )
	{
		$detail = '';
		if ($id > 0) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetRoutingsService.class.php';
				$request = new AdmGetRoutingsRequest( 'GetSections', 'GetStatuses' );
				$request->Ticket = $ticket;
				$request->RequestModes = array( 'GetStatuses', 'GetSections' );
				$request->PublicationId = null;
				$request->IssueId = $id;
				$service = new AdmGetRoutingsService();
				$response = $service->execute( $request );
				$routings = $response->Routings;
				$sections = $response->Sections;
				$statuses = $response->Statuses;
				//order by routingsection, statetype, statecode?

				$statusDomain = array();
				if( $statuses ) foreach( $statuses as $status ) {
					$statusDomain[$status->Id] = $status->Type."/".$status->Name;
				}
				$sectionDomain = array();
				if( $sections ) foreach( $sections as $section ) {
					$sectionDomain[$section->Id] = $section->Name;
				}

				$color = array (" bgcolor='#eeeeee'", '');
				$flip = 0;
				$sAll = BizResources::localize("LIS_ALL");
				if( $routings ) foreach( $routings as $routing ) {
					$clr = $color[$flip];
					$sect = $routing->SectionId ? $sectionDomain[$routing->SectionId] : '<'.$sAll.'>';
					$detail .= "<tr$clr><td><a href='routing.php?publ=$pubId&issue=$id&selsection=$routing->SectionId'>".formvar($sect)."</a></td>";
					$routeToDetails = !empty($routing->RouteTo) ? $routing->RouteTo : '<'.$sAll.'>';
					$statusDetails = !empty($routing->StatusId) ? $statusDomain[$routing->StatusId] : '<'.$sAll.'>';
					$detail .= '<td>'.formvar($statusDetails).'</td><td>'.formvar($routeToDetails).'</td></tr>';
					$flip = 1- $flip;
				}
			} catch(BizException $e) {
				$errors[] = $e->getMessage();
			}
		}
		$detailtxt3 = HtmlDocument::loadTemplate( 'hpissuesdetroute.htm' );
		$detailtxt3 = str_replace( '<!--ROWS-->', $detail, $detailtxt3 );
		$detailtxt3 = str_replace( '<!--PUBL-->', $pubId, $detailtxt3 );
		$detailtxt3 = str_replace( '<!--ISSUE-->', $id, $detailtxt3 );
		return $detailtxt3;
	}

	/**
	 * Draws HTML pane/form that lists all issue's authorizations.
	 *
	 * @param string $ticket The user's session ticket.
	 * @param string $id Issue id
	 * @param string $pubId Publication id
	 * @param array &$errors List of errors.
	 * @return string HTML stream
	 */
	private function buildAuthorizationsForm( $ticket, $id, $pubId, array &$errors )
	{
		$detail = '';
		if( $id > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';
				$request = new AdmGetWorkflowUserGroupAuthorizationsRequest();
				$request->Ticket = $ticket;
				$request->RequestModes = array( 'GetUserGroups' );
				$request->IssueId = $id;
				$service = new AdmGetWorkflowUserGroupAuthorizationsService();
				$response = $service->execute( $request );
				$userGroups = $response->UserGroups;

				$color = array (" bgcolor='#eeeeee'", '');
				$flip = 0;
				if( $userGroups ) foreach( $userGroups as $userGroup ) {
					$clr = $color[$flip];
					$detail .= "<tr$clr><td><a href='authorizations.php?publ=$pubId&issue=$id&grp="
						.$userGroup->Id."'>".$userGroup->Name
						."</a></td><td><a href='hppublissues.php?delauthor=1&id=$id&del="
						.$userGroup->Id
						."' onClick='return myconfirm(\"delauthor\")'>"
						."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
						."</a></td><tr>";
					$flip = 1- $flip;
				}
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}
		$detailtxt4 = HtmlDocument::loadTemplate( 'hpissuesdetauthor.htm' );
		$detailtxt4 = str_replace( '<!--ROWS-->', $detail, $detailtxt4 );
		$detailtxt4 = str_replace( '<!--PUBL-->', $pubId, $detailtxt4 );
		$detailtxt4 = str_replace( '<!--ISSUE-->', $id, $detailtxt4 );
		return $detailtxt4;
	}

	/**
	 * Draws HTML pane/form that lists all issue's dossier template.
	 *
	 * @param string $ticket Ticket
	 * @param string $id Issue id
	 * @param string $publ Publication id
	 * @param array &$errors List of errors.
	 * @return string HTML stream
	 */
	private function buildDossierTemplatesForm( $ticket, $id, $publ, array &$errors )
	{
		$detail = '';
		if ($id > 0) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetTemplateObjectsService.class.php';
				$request = new AdmGetTemplateObjectsRequest();
				$request->Ticket = $ticket;
				$request->RequestModes = array( 'GetUserGroups', 'GetObjectInfos' );
				$request->PublicationId = $publ;
				$request->IssueId = $id;
				$service = new AdmGetTemplateObjectsService();
				$response = $service->execute( $request );
				$templateObjects = $response->TemplateObjects;
				$resUserGroups = $response->UserGroups;
				$resObjectInfos = $response->ObjectInfos;

				$userGroupsArr = array();
				$userGroupsArr[0] = '<'.BizResources::localize("LIS_ALL").'>';
				if( $resUserGroups ) foreach( $resUserGroups as $userGroup ) {
					$userGroupsArr[$userGroup->Id] = $userGroup->Name;
				}

				$objectInfos = array();
				if( $resObjectInfos ) foreach( $resObjectInfos as $objectInfo ) {
					$objectInfos[$objectInfo->ID] = $objectInfo;
				}

				$dosArr = array();
				$grpArr = array();
				if( $templateObjects ) foreach( $templateObjects as $templateObject ) {
					$dosArr[$templateObject->TemplateObjectId] = $objectInfos[$templateObject->TemplateObjectId]->Name;
					if( !isset($grpArr[$templateObject->TemplateObjectId] ) )
						$grpArr[$templateObject->TemplateObjectId] = array( $userGroupsArr[$templateObject->UserGroupId] );
					else
						$grpArr[$templateObject->TemplateObjectId][] = $userGroupsArr[$templateObject->UserGroupId];
				}

				$color = array (" bgcolor='#eeeeee'", '');
				$cnt=1;
				foreach (array_keys($dosArr) as $objId) {
					$clr = $color[$cnt%2];
					$groups = implode($grpArr[$objId], ', ');
					$detail .= "<tr$clr><td><a href='dossiertemplates.php?publ=$publ&issue=$id&objid=$objId'>".formvar($dosArr[$objId]).'</a></td>';
					$detail .= "<td>".formvar($groups)."</td><td><a href='dossiertemplates.php?publ=$publ&issue=$id&delete=2&objid="
						.$objId
						."' onClick='return myconfirm(\"delpublobjects\")'>"
						."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
						."</a></td><tr>";
					$cnt++;
				}
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
			}

			$detailtxt5 = HtmlDocument::loadTemplate( 'hpissuesdetdossiertemplate.htm' );
			$detailtxt5 = str_replace( '<!--ROWS-->', $detail, $detailtxt5 );
			$detailtxt5 = str_replace( '<!--PUBL-->', $publ, $detailtxt5 );
			$detailtxt5 = str_replace( '<!--ISSUE-->', $id, $detailtxt5 );
			return $detailtxt5;
		}
		return $detail;
	}

	/**
	 * Get Issue objects
	 *
	 * @param string $ticket 	Ticket
	 * @param string $publ 		Publication Id
	 * @param string $channelId Channel Id
	 * @param string $id		Issue Id
	 * @return object $issueObj Issue object if found
	 */
	public function getIssueObj( $ticket, $publ, $channelId, $id )
	{		
		try {
			require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';
			$service = new AdmGetIssuesService();
			$request = new AdmGetIssuesRequest();
			$request->Ticket        = $ticket;
			$request->RequestModes  = array();
			$request->PublicationId = $publ;
			$request->PubChannelId   = $channelId;
			$request->IssueIds       = array( $id );			
			$response = $service->execute($request);
			$issueObj = $response->Issues[0]; // We only get one issue back
		} catch( BizException $e ) {
			$e = $e; // Make analyzer happy.
		}
		return $issueObj;
	}
}
