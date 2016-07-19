<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

$ticket = checkSecure('publadmin');

// determine incoming mode
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

// check publication rights
checkPublAdmin($id);

$recs = isset($_REQUEST['recs_section']) ? intval($_REQUEST['recs_section']) : 0;
if ($recs > 0) {
	for ($i = 1; $i < $recs; $i++) {
		$tid = intval($_REQUEST["sec_order$i"]);
		$cd = intval($_REQUEST["sec_code$i"]);
		$dbh = DBDriverFactory::gen();
		$dbs = $dbh->tablename('publsections');
		$sql = "update $dbs set `code` = $cd where `id` = $tid";
		$sth = $dbh->query($sql);
	}
}

$recs = isset($_REQUEST['recs_channel']) ? intval($_REQUEST['recs_channel']) : 0;
if ($recs > 0) {
	for ($i = 1; $i < $recs; $i++) {
		$tid = intval($_REQUEST["channel_order$i"]);
		$cd = intval($_REQUEST["channel_code$i"]);
		$dbh = DBDriverFactory::gen();
		$dbch = $dbh->tablename('channels');
		$sql = "update $dbch set `code` = $cd where `id` = $tid";
		$sth = $dbh->query($sql);
	}
}

// mode handling
if (isset($_REQUEST['vdelete']) && $_REQUEST['vdelete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['vupdate']) && $_REQUEST['vupdate']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else if (isset($_REQUEST['delsection'])) {
	$mode = 'delsection';
} else if (isset($_REQUEST['delauthor'])) {
	$mode = 'delauthor';
} else if (isset($_REQUEST['delpubladmin'])) {
	$mode = 'delpubladmin';
} else if (isset($_REQUEST['delroute'])) {
	$mode = 'delroute';
} else if (isset($_REQUEST['delchannel'])) {
	$mode = 'delchannel';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}
$del = isset($_REQUEST['del']) ? intval($_REQUEST['del']) : 0;

// Build AdmPublication data object from user typed data (HTTP post params).
// In case of commands or redirections, read the props from DB using just the id.
$errors = array();
$validateErrors = array();
$app = new PublicationMaintenanceApp();
try {
	$pubObj = $app->buildPublicationObj( $ticket, $id );
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

// handle request
switch ($mode) {
	case 'update':
		try {
			require_once BASEDIR . '/server/services/adm/AdmModifyPublicationsService.class.php';
			require_once BASEDIR . '/server/interfaces/services/adm/AdmModifyPublicationsRequest.class.php';
			$service = new AdmModifyPublicationsService();
			$request = new AdmModifyPublicationsRequest( $ticket, array(), array($pubObj) );
			$response = $service->execute($request);
			$pubObj = $response->Publications[0];
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
		}
		break;
	case 'insert':
		try {
			require_once BASEDIR . '/server/services/adm/AdmCreatePublicationsService.class.php';
			require_once BASEDIR . '/server/interfaces/services/adm/AdmCreatePublicationsRequest.class.php';
			$service = new AdmCreatePublicationsService();
			$request = new AdmCreatePublicationsRequest( $ticket, array(), array($pubObj) );
			$response = $service->execute($request);
			$id = $response->Publications[0]->Id;
			$pubObj = $response->Publications[0];
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
		}
		break;
	case 'delete':
		if( $id > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';
				$service = new AdmDeletePublicationsService();
				$request = new AdmDeletePublicationsRequest( $ticket, array( $id ) );
				$service->execute( $request );
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}
		break;
	case 'delchannel':
		if( $del > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';
				$service = new AdmDeletePubChannelsService();
				$request = new AdmDeletePubChannelsRequest( $ticket, $id, array( $del ) );
				$service->execute( $request );
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}
		break;
	case 'delsection':
		if( $id > 0 && $del > 0 ) {
			try {			
				require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
				$service = new AdmDeleteSectionsService();
				$request = new AdmDeleteSectionsRequest( $ticket, $id, null, array( $del ) );
				$service->execute( $request );
			} catch( BizException $e ) {
				if( stripos( $e->getMessage(), '(S1057)' ) !== false ) { // in use by objects?
					header("Location: removesection.php?Publication=$id&Section=$del");
					exit;
				}
				$errors[] = $e->getMessage();
			}
			break;
		}
		break;
	case 'delauthor':
		if ($id > 0 && $del > 0) {
			try {
				require_once BASEDIR.'/server/dbclasses/DBAuthorizations.class.php';
				/*$ret = */ DBAuthorizations::deleteAuthorization( $id, 0, null, null, $del );
				require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
				$group = DBUser::getUserGroupObj( $del );
				require_once BASEDIR.'/server/dbclasses/DBRouting.class.php';
				/*$ret= */ DBRouting::deleteRouting($id, 0, null, null, $group->Name);
			} catch ( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}
		break;
	case 'delpubladmin':
		if ($id > 0 && $del > 0) {
			$dbh = DBDriverFactory::gen();
			$dbpa = $dbh->tablename('publadmin');
			$sql = "DELETE FROM $dbpa WHERE `grpid` = $del AND `publication`=$id";
			$sth = $dbh->query($sql);
		}
		break;
	case 'delroute':
		if ($id > 0) {
			try {
				require_once BASEDIR.'/server/dbclasses/DBRouting.class.php';
				/*$ret= */ DBRouting::deleteRouting($id, 0, $del, null, null);
			} catch ( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}
		break;
}
// delete: back to overview
if( $mode == 'delete' && count($errors) == 0 ) {
	header("Location:publications.php");
	exit();
}

// generate upper part (edit fields)
if( $mode != 'new' && $id ) {
	try {
		$pubObj = $app->getPublicationObj( $ticket, $id );
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
	}
}

$action = ($mode == 'new') ? 'Create' : 'Update';
print $app->buildPublicationForm( $pubObj, $action, $errors, $validateErrors, $ticket );

/**
 * Application class that takes care of:
 * - building HTML for the Brand Maintenance page
 * - retrieving Brand properties of HTTP form posts (from that page)
 */
class PublicationMaintenanceApp
{
	/**
	 * Creates new AdmPublication object and sets all its properties.
	 * When initially loading the form for a new admin object, default properties are taken.
	 * When initially loading the form for an existing admin object, properties are retrieved from DB.
	 * When user posts typed/changed data, properties are retrieved from HTTP params ($_REQUEST).
	 *
	 * @param string $ticket
	 * @param integer $id Publication ID
	 * @throws BizException On DB error.
	 * @return AdmPublication
	 */
	public function buildPublicationObj( $ticket, $id )
	{
		$pubObj = new AdmPublication();
		$pubObj->Id = $id;
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$prefix = 'Publication_'; // prefix for form property names
		$entity = 'Publication';
		$newObject = ($id == 0); // TRUE: creating new admin object, FALSE: updating existing admin object
		
		// TRUE: first time building the form or changed the selection for section mapping, FALSE: user does submit typed changes
		$firstCall = !isset($_REQUEST['Publication_Name']); 
		
		require_once BASEDIR.'/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php'; // AdminProperties_Context
		$context = new AdminProperties_Context();
		$context->setPublicationContext( $pubObj, null, null, null, null );

		if( $newObject ) { // user is creating new admin object
			if( $firstCall ) { // loading initial form
				BizAdmProperty::buildDefaultAdmObj( $pubObj, $entity, $prefix, $context );
			} else { // user submit typed/changed data
				BizAdmProperty::buildAdmObjFromHttp( $pubObj, $entity, $prefix, $context );
			}
		} else { // user is updating existing admin object
			if( $firstCall ) { // loading initial form
				$pubObj = $this->getPublicationObj( $ticket, $id );
			} else { // user submits typed/changed data
				BizAdmProperty::buildAdmObjFromHttp( $pubObj, $entity, $prefix, $context );
			}
		}
		return $pubObj;
	}
	
	/**
	 * Retrieves a AdmPublication data object from DB.
	 *
	 * @param string $ticket
	 * @param integer $id Publication ID
	 * @throws BizException On DB error.
	 * @return AdmPublication
	 */
	public function getPublicationObj( $ticket, $id )
	{
		require_once BASEDIR . '/server/services/adm/AdmGetPublicationsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/adm/AdmGetPublicationsRequest.class.php';
		$service = new AdmGetPublicationsService();
		$request = new AdmGetPublicationsRequest( $ticket, array(), array($id) );
		$response = $service->execute($request);
		return $response->Publications[0];
	}

	/**
	 * Dynamically build the publication maintenance form (property sheet).
	 * Server Plug-ins are requested to give their custom properties as well.
	 *
	 * @param AdmPublication $pubObj
	 * @param string $action 'Create' or 'Update'
	 * @param array $validateErrors Errors from property validations. Keys are property names. Values are error messages.
	 * @return string HTML fragment representing the publication properties. The form itself is excluded!
	 */
	public function buildPublicationForm( AdmPublication $pubObj, $action, $errors, $validateErrors, $ticket )
	{
		// Build a list of properties (DialogWidget objects) and put them in the order how to show to end-user.
		// Server Plug-ins are requested to add their custom properties as well and are able to reorganize props.
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php'; // AdminProperties_Context
		$entity = 'Publication';
		$context = new AdminProperties_Context();
		$context->setPublicationContext( $pubObj, null, null, null, null );
		$hideWidgets = array();
		$showWidgets = BizAdmProperty::buildDialogWidgets( $context, $entity, $action, $hideWidgets );
		
		// Collect the property values to fill in at the publication form fields.
		$mdValues = BizAdmProperty::getMetaDataValues( $pubObj );
		
		// Draw all form fields (in memory as HTML string) which includes the form values, representing the publication form.
		$prefix = 'Publication_'; // prefix for form property names
		$propsHtml = '';
		require_once BASEDIR.'/server/utils/htmlclasses/XHtmlDocument.class.php';
		$doc = new Utils_XHtmlDocument();
		$form = $doc->addForm( 'myform', 'myform' );

		foreach( $showWidgets as $widget ) {
			if( $widget->PropertyInfo->Name == 'DefaultChannelId' ) {
				if( $action == 'Update' ) {
					$widget->PropertyInfo->PropertyValues = array();

					// Add first empty channel with value id=0 to the list
					$propValue = new PropertyValue();
					$propValue->Value = 0;
					$propValue->Display = '';
					$widget->PropertyInfo->PropertyValues[] = $propValue;

					require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
					$pubChannels = DBAdmPubChannel::listPubChannelsObj( $pubObj->Id );
					if( $pubChannels ) foreach( $pubChannels as $pubChannel ) {
						$propValue = new PropertyValue();
						$propValue->Value = $pubChannel->Id;
						$propValue->Display = $pubChannel->Name;
						$widget->PropertyInfo->PropertyValues[] = $propValue;
					}
				}
			}
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
			$propsHtml .= $this->drawHtmlField( $pubObj, $widget, $prop, $validateError );

			// Display the Time Setting (the clock) after the Calculate Deadline checkbox.
			if( $widget->PropertyInfo->Name == 'CalculateDeadlines' ) {
				$timeSettings =
					'<a href="editPublDeadlines.php?publid='.$pubObj->Id.'}">'.
					'<img src="../../config/images/deadline_24.gif"></img>'.
					'</a>';
				$propsHtml .= '<tr id="timeSettingRow"><td>'.BizResources::localize('TIMESETTINGS').'</td><td>'.$timeSettings.'</td></tr>';
			}
		}

		// Collect hidden fields too, to add to form at hidden section. This way data can round-trip
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
			$hidePropsHtml .= $this->drawHtmlField( $pubObj, $widget, $prop, '' );
		}

		// Load HTML template and insert the publication form.
		$txt = HtmlDocument::loadTemplate( 'hppublications.htm' );
		$txt = str_replace( '<!--VAR:PUBLICATION_PROPERTIES-->', $propsHtml, $txt );
		$txt = str_replace( '<!--VAR:PUBLICATION_HIDDEN_PROPERTIES-->', $hidePropsHtml, $txt );
		$txt = str_replace( '<!--VAR:HIDDEN-->', inputvar( 'id', $pubObj->Id, 'hidden' ), $txt );
		
		if( $action == 'Create' ) {
			$txt = str_replace('<!--VAR:BUTTON-->', 
				'<input type="submit" name="bt_update" value="'.BizResources::localize('ACT_UPDATE').'" onclick="return myupdate()"/>', $txt );
		} else {
			$txt = str_replace('<!--VAR:BUTTON-->', 
				'<input type="submit" name="bt_update" value="'.BizResources::localize('ACT_UPDATE').'" onclick="return myupdate()"/>'.
				'<input type="submit" name="bt_delete" value="'.BizResources::localize('ACT_DEL').'" onclick="return mydelete()"/>', $txt );
		}

		// Generate lower part of the page listing the channels, categories, etc.
		// This is no longer needed when tabs are implemented with JQuery UI.
		if( $action == 'Create' ) {
			$detailtxt = '';
		} else {
			$detailtxt = $this->getPublicationSubPanes( $pubObj->Id, $ticket, $errors );
		}
		$txt = str_replace( '<!--DETAILS-->', $detailtxt, $txt );

		// Set user input focus to the Name field.
		$txt .= "<script language='javascript'>document.forms[0].Publication_Name.focus();</script>";

		// Raise errors if any.
		$err = count($errors) > 0 ? "onLoad='javascript:alert(\"".implode('\n',$errors)."\")'" : ''; // \n is literal for JavaScript

		// Show the HTML page.
		return HtmlDocument::buildDocument( $txt, true, $err );
	}

	/**
	 * Streams given Utils_XHtmlField into an HTML string that suites the publication form.
	 * It shows the property name and the input widget in two (hidden) table columns.
	 * In case of validation error, the error message is shown at second column.
	 *
	 * IMPORTANT: Please keep this function in sync with hppublissues.php and ChannelForm.class.php.
	 *
	 * @param AdmPublication $pubObj
	 * @param DialogWidget $widget
	 * @param Utils_XHtmlField $htmlPropObj
	 * @param string $validateError Error message to show when validation for this property has failed.
	 * @param int $width Sets the element width
	 * @return string
	 */
	private function drawHtmlField( AdmPublication $pubObj, DialogWidget $widget, Utils_XHtmlField $htmlPropObj, $validateError, $width = 200 )
	{
		$pubObj = $pubObj; // keep code analyzer happy
		if( $widget->PropertyInfo->Name == 'AutoPurge' ) {
			$postfix = ' '.BizResources::localize('TIME_DAYS');
			$htmlPropObj->setWidth( 100 );
		} else {
			$postfix = '';
			$htmlPropObj->setWidth( $width );
		}
		$displayName = $widget->PropertyInfo->DisplayName;
		if( $widget->PropertyInfo->Type == 'separator' ) {
			$htmlWidget = '<tr><td colspan="2">&nbsp;</td></tr>'."\r\n";
			$htmlWidget .= '<tr><th colspan="2"><br/>'.formvar($displayName).'</th></tr>'."\r\n";
		} else {
			$mandatory = $widget->PropertyUsage->Mandatory ? '*' : '';
			$htmlWidget = '<tr><td>'.formvar($displayName).$mandatory.'</td><td>'.$htmlPropObj->toString().
				$postfix.'<font color="#ff0000"><i>'.$validateError.'</i></font></td></tr>'."\r\n";
		}
		return $htmlWidget;
	}
	
	/**
	 * Builds HTML panes in memory. Each pane lists all the configured entities 
	 * (such as categories, statuses, etc) that belong to a given publication ($id).
	 *
	 * @param integer $id Publication ID
	 * @return HTML panes.
	 */
	private function getPublicationSubPanes( $id, $ticket, $errors )
	{
		$pubChannelsPane = $this->getPubChannelsPane( $id );
		$categoriesPane = $this->getCategoriesPane( $id );
		$statusesPane = $this->getStatusesPane( $id );
		$userAuthPane = $this->getUserAuthorizationsPane( $id );
		$adminAuthPane = $this->getAdminAuthorizationsPane( $id );
		$routingPane = $this->getRoutingsPane( $id, $ticket );
		$dossierTplPane = $this->getDossierTemplatesPane( $id, $ticket, $errors );
	
		// Remark when implement JQuery UI 
		// combine all text
		$txt = 
			"<table>
				<tr>
					<td width=55% valign=top>$pubChannelsPane</td>
					<td valign=top>$categoriesPane</td>
				</tr>
				<tr>
					<td valign=top>$statusesPane$routingPane$dossierTplPane</td>
					<td valign=top>$userAuthPane$adminAuthPane</td>
				</tr>
			</table>";
	
		// Uncomment when implement JQuery UI at later version
		// add tabs
		/*
		$txt = str_replace('<!--PUBCHANNEL_PANE-->', $pubChannelsPane, $txt);
		$txt = str_replace('<!--CATEGORY_PANE-->', $categoriesPane, $txt);
		$txt = str_replace('<!--WORKFLOW_PANE-->', $statusesPane, $txt);
		$txt = str_replace('<!--USER_AUTHORIZATION_PANE-->', $userAuthPane, $txt);
		$txt = str_replace('<!--ADMIN_AUTHORIZATION_PANE-->', $adminAuthPane, $txt);
		$txt = str_replace('<!--ROUTING_PANE-->', $routingPane, $txt);
		$txt = str_replace('<!--DOSSIER_TEMPLATES_PANE-->', $dossierTplPane, $txt);
		*/
		return $txt;
	}
	
	/**
	 * Builds a HTML pane in memory that lists the channels of a given publication ($id).
	 *
	 * @param integer $id Publication ID
	 * @return HTML pane.
	 */
	private function getPubChannelsPane( $id )
	{
		$dbh = DBDriverFactory::gen();
		$dbch = $dbh->tablename('channels');
		$detail = inputvar( 'id', $id, 'hidden' );
		if ($id > 0) {
			$sql = "select * from $dbch where `publicationid` = $id order by `code`, `name`";
			$sth = $dbh->query($sql);
			$color = array (" bgcolor='#eeeeee'", '');
			$cnt=1;
			while (($row = $dbh->fetch($sth) )) {
				$clr = $color[$cnt%2];
				$tid = $row['id'];
				$bx = inputvar("channel_code$cnt", $row['code'], "small").inputvar( "channel_order$cnt", $tid, 'hidden' );
				$detail .= "<tr$clr><td><a href='editChannel.php?publid=$id&channelid=$tid'>"
					.$row["name"]."</a></td><td>$bx</td><td><a href='hppublications.php?delchannel=1&id=$id&del="
					.$row['id']
					."' onClick='return myconfirm(\"delchannel\")'>"
					."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
					."</a></td><tr>";
				$cnt++;
			}
			$detail .= inputvar( 'recs_channel', $cnt, 'hidden' );
		}
		$txt = HtmlDocument::loadTemplate( 'hppublicationsdetchannel.htm' );
		$txt = str_replace( '<!--ROWS-->', $detail, $txt );
		$txt = str_replace( '<!--PUBL-->', $id, $txt );
		return $txt;
	}
	
	/**
	 * Builds a HTML pane in memory that lists the categories of a given publication ($id).
	 *
	 * @param integer $id Publication ID
	 * @return HTML pane.
	 */
	private function getCategoriesPane( $id )
	{
		$detail = '';
		if ($id > 0) {
			$dbh = DBDriverFactory::gen();
			$dbs = $dbh->tablename('publsections');
			$sql = "select * from $dbs where `publication` = $id and `issue` = 0 order by `code`, `section`";
			$sth = $dbh->query($sql);
			$color = array (" bgcolor='#eeeeee'", '');
			$cnt=1;
			while (($row = $dbh->fetch($sth) )) {
				$clr = $color[$cnt%2];
				$tid = $row['id'];
				$bx = inputvar("sec_code$cnt", $row['code'], "small").inputvar( "sec_order$cnt", $tid, 'hidden' );
				$detail .= "<tr$clr><td><a href='hppublsections.php?publ=$id&id=$tid'>"
					.$row["section"]."</a></td><td>$bx</td><td><a href='hppublications.php?delsection=1&id=$id&del="
					.$row['id']
					."' onClick='return myconfirm(\"delsection\")'>"
					."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
					."</a></td><tr>";
				$cnt++;
			}
			$detail .= inputvar( 'recs_section', $cnt, 'hidden' );
		}
		$txt = HtmlDocument::loadTemplate( 'hppublicationsdetsection.htm' );
		$txt = str_replace( '<!--ROWS-->', $detail, $txt );
		$txt = str_replace( '<!--PUBL-->', $id, $txt );
		return $txt;
	}
		
	/**
	 * Builds a HTML pane in memory that lists the statuses of a given publication ($id).
	 *
	 * @param integer $id Publication ID
	 * @return HTML pane.
	 */
	private function getStatusesPane( $id )
	{
		$detail = '';
		if ($id > 0) {
			$typesdomain = getObjectTypeMap();
			$dbh = DBDriverFactory::gen();
			$dbst = $dbh->tablename('states');
			$sql = "select `type`, `state`, `id` from $dbst ".
				"where `publication` = $id  and `issue` = 0 order by `type`, `code`";
			$sth = $dbh->query($sql);
			$arr = array();
			while (($row = $dbh->fetch($sth))) {
				if (!isset($arr[$row['type']]))
					$arr[$row['type']] = array ($row['state']);
				else
					$arr[$row['type']][] = $row['state'];
			}
			$color = array (" bgcolor='#eeeeee'", '');
			$flip = 0;
			foreach (array_keys($arr) as $type) {
				$clr = $color[$flip];
				$states = implode($arr[$type], ', ');
				$detail .= "<tr$clr><td><a href='states.php?publ=$id&type=$type'>".formvar($typesdomain[$type]).'</a></td>';
				$detail .= '<td>'.formvar($states).'</td></tr>';
				$flip = 1- $flip;
			}
		}
		$txt = HtmlDocument::loadTemplate( 'hppublicationsdetstate.htm' );
		$txt = str_replace( '<!--ROWS-->', $detail, $txt );
		$txt = str_replace( '<!--PUBL-->', $id, $txt );
		return $txt;
	}
	
	/**
	 * Builds a HTML pane in memory that lists the user authorizations of a given publication ($id).
	 *
	 * @param integer $id Publication ID
	 * @return HTML pane.
	 */
	private function getUserAuthorizationsPane( $id )
	{
		$detail = '';
		if ($id > 0) {
			$dbh = DBDriverFactory::gen();
			$dba = $dbh->tablename('authorizations');
			$dbg = $dbh->tablename('groups');
			$sql = "SELECT g.`id`, g.`name` FROM $dba a, $dbg g ".
					"WHERE a.`grpid` = g.`id` and a.`publication` = $id  and a.`issue` = 0 ".
					"GROUP BY g.`id`, g.`name` ".
					"ORDER BY g.`name`";
			$sth = $dbh->query($sql);
			$color = array (" bgcolor='#eeeeee'", '');
			$flip = 0;
			while (($row = $dbh->fetch($sth) )) {
				$clr = $color[$flip];
				$detail .= "<tr$clr><td><a href='authorizations2.php?publ=$id&grp="
					.$row["id"]
					."'>"
					.formvar($row["name"])
					."</a></td><td><a href='hppublications.php?delauthor=1&id=$id&del="
					.$row['id']
					."' onClick='return myconfirm(\"delauthor\")'>"
					."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
					."</a></td><tr>";
				$flip = 1- $flip;
			}
		}
		$txt = HtmlDocument::loadTemplate( 'hppublicationsdetauthor.htm' );
		$txt = str_replace( '<!--ROWS-->', $detail, $txt );
		$txt = str_replace( '<!--PUBL-->', $id, $txt );
		return $txt;
	}
	
	/**
	 * Builds a HTML pane in memory that lists the admin authorizations of a given publication ($id).
	 *
	 * @param integer $id Publication ID
	 * @return HTML pane.
	 */
	private function getAdminAuthorizationsPane( $id )
	{
		// publication authorizations
		$detail = '';
		if ($id > 0) {
			$dbh = DBDriverFactory::gen();
			$dbpa = $dbh->tablename('publadmin');
			$dbg = $dbh->tablename('groups');
			$sql = "SELECT g.`id`, g.`name` FROM $dbpa pa, $dbg g ".
					"WHERE pa.`grpid` = g.`id` and pa.`publication` = $id ".
					"ORDER BY g.`name`";
			$sth = $dbh->query($sql);
			$color = array (" bgcolor='#eeeeee'", '');
			$flip = 0;
			while (($row = $dbh->fetch($sth) )) {
				$clr = $color[$flip];
				$detail .= "<tr$clr><td>"
					.formvar($row["name"])."</td><td><a href='hppublications.php?delpubladmin=1&id=$id&del="
					.$row['id']
					."' onClick='return myconfirm(\"delpubladmin\")'>"
					."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
					."</a></td><tr>";
				$flip = 1- $flip;
			}
		}
		$tpl = HtmlDocument::loadTemplate( 'hppublicationsdetpubladmin.htm' );
		$txt = str_replace( '<!--ROWS-->', $detail, $tpl );
		$txt = str_replace( '<!--PUBL-->', $id, $txt );
		return $txt;
	}
	
	/**
	 * Builds a HTML pane in memory that lists the workflow routings of a given publication ($id).
	 *
	 * @param integer $id Publication ID
	 * @return HTML pane.
	 */
	private function getRoutingsPane( $id, $ticket )
	{
		$detail = '';
		if ($id > 0) {
			$dbh = DBDriverFactory::gen();
			$dbst = $dbh->tablename('states');
			$sql = "SELECT `id`, `state`, `type` FROM $dbst ".
				"WHERE `publication` = $id and `issue` = 0 ".
				"ORDER BY `type`, `code`";
			$sth = $dbh->query($sql);
			$statedomain = array();
			while (($row = $dbh->fetch($sth))) {
				$statedomain[$row['id']] = $row['type']."/".$row['state'];
			}
			$routedomain = array();
			$arrayOfRoute = listrouteto( $ticket, $id, null );
			if ($arrayOfRoute) foreach ($arrayOfRoute as $route) {
				$routedomain[$route] = $route;
			}
			$dbs = $dbh->tablename('publsections');
			$dbr = $dbh->tablename('routing');
			$sql = "SELECT s.`id`, s.`section`, r.`routeto`, r.`state` FROM $dbr r ".
					"LEFT JOIN $dbs s on (r.`section` = s.`id`) ".
					"WHERE r.`publication` = $id and r.`issue` = 0 ".
					"GROUP BY s.`section`, s.`id`, r.`routeto`, r.`state` ".
					"ORDER BY s.`section`, s.`id`";
			$sth = $dbh->query($sql);
			$color = array (" bgcolor='#eeeeee'", '');
			$flip = 0;
	
			$sAll = BizResources::localize("LIS_ALL");
			while (($row = $dbh->fetch($sth) )) {
				$clr = $color[$flip];
				$sid = $row['id'];
				$sect = trim($row['section']); 
				if (!$sect) $sect = '<'.$sAll.'>';
				$detail .= "<tr$clr><td><a href='routing.php?publ=$id&selsection=$sid'>".formvar($sect)."</a></td>";
				$routeTo = trim($row['routeto']); // MSSQL bug: empty fields have single space, so we trim
				$routeToDetails = !empty($routeTo) && isset($routedomain[$routeTo]) ? $routedomain[$routeTo] : '<'.$sAll.'>';
				$status = trim($row['state']); // MSSQL bug: empty fields have single space, so we trim
				$statusDetails = !empty($status) && isset($statedomain[$status]) ? $statedomain[$status] : '<'.$sAll.'>';
				$detail .= '<td>'.formvar($statusDetails).'</td><td>'.formvar($routeToDetails).'</td></tr>';
				$flip = 1- $flip;
			}
		}
		$txt = HtmlDocument::loadTemplate( 'hppublicationsdetroute.htm' );
		$txt = str_replace( '<!--ROWS-->', $detail, $txt );
		$txt = str_replace( '<!--PUBL-->', $id, $txt );
		return $txt;
	}
	
	/**
	 * Builds a HTML pane in memory that lists the dossier templates of a given publication ($id).
	 *
	 * @param integer $id Publication ID
	 * @return HTML pane.
	 */
	private function getDossierTemplatesPane( $id, $ticket, &$errors )
	{
		$detail = '';
		if ($id > 0) {
			require_once BASEDIR . '/server/bizclasses/BizAdmPubObject.class.php';
			$pubObjects = array();
			$pubObjects = BizAdmPubObject::listPubObjects( $id );
	
			$usergroups = null;
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
				require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUserGroupsRequest.class.php';
				$service = new AdmGetUserGroupsService();
				$request = new AdmGetUserGroupsRequest( $ticket, array() );
				$response = $service->execute($request);
				$usergroups = $response->UserGroups;
			} catch(BizException $e) {
				$errors[] = $e->getMessage();
			}
			
			$usergroupsArr = array();
			$usergroupsArr[0] = '<'.BizResources::localize("LIS_ALL").'>';
			foreach( $usergroups as $usergroup ) {
				$usergroupsArr[$usergroup->Id]= $usergroup->Name;
			}
	
			$dosArr = array();
			$grpArr = array();
			foreach( $pubObjects as $pubObject ) {
				$dosArr[$pubObject->ObjectId] = $pubObject->ObjectName;
				if (!isset($grpArr[$pubObject->ObjectId]))
					$grpArr[$pubObject->ObjectId] = array ($usergroupsArr[$pubObject->GroupId]);
				else
					$grpArr[$pubObject->ObjectId][] = $usergroupsArr[$pubObject->GroupId];
			}
	
			$color = array (" bgcolor='#eeeeee'", '');
			$cnt=1;
			foreach (array_keys($dosArr) as $objId) {
				$clr = $color[$cnt%2];
				$groups = implode($grpArr[$objId], ', ');
				$detail .= "<tr$clr><td><a href='dossiertemplates.php?publ=$id&objid=$objId'>".formvar($dosArr[$objId]).'</a></td>';
				$detail .= "<td>".formvar($groups)."</td><td><a href='dossiertemplates.php?publ=$id&issue=0&delete=1&objid="
						.$objId
						."' onClick='return myconfirm(\"delpublobjects\")'>"
						."<img src=\"../../config/images/remov_16.gif\" border=\"0\" title=\"".BizResources::localize("ACT_DELETE")."\"/>"
						."</a></td><tr>";
				$cnt++;
			}
		}
		$txt = HtmlDocument::loadTemplate( 'hppublicationsdetdossiertemplate.htm' );
		$txt = str_replace( '<!--ROWS-->', $detail, $txt );
		$txt = str_replace( '<!--PUBL-->', $id, $txt );
		return $txt;
	}
}