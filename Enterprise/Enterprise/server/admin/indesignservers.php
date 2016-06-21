<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('admin');
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$err = null;
$idsObj = null;
$errors = array();

// handle request
try {
	switch( $action ) {
		case 'update': // add or edit
			$idsObj = getInDesignServerFromHTTP();
			$idsObj = BizInDesignServer::updateInDesignServer( $idsObj ); // insert or update
			$action = ''; // after add/edit, go back to overview
		break;
		case 'delete':
			$idsObj = getInDesignServerFromHTTP();
			BizInDesignServer::deleteInDesignServer( $idsObj->Id );
			$action = ''; // after delete, go back to overview
		break;
		case 'autodetectversion':
			if( $id !== 0 ) {
				$idsObj = getInDesignServerFromHTTP();
				BizInDesignServer::autoDetectServerVersion( $idsObj );
			}
			$action = ( $id === 0 ) ? 'add' : 'edit';
		break;
	}	
} catch( BizException $e ) {
	if( $action == 'update' || $action == 'delete' || $action == 'autodetectversion' ) {
		// on error, we stick to the current action (we do not go back to overview)
		$action = ( $id === 0 ) ? 'add' : 'edit';
	}
	$err = $e->getMessage();
}

// show results
$autoVer = '';
switch ($action) {
	case '' : // overview; list all IDSs
		$rows = '';
		$txt = HtmlDocument::loadTemplate( 'indesignservers.htm' );
		$idsObjs = BizInDesignServer::listInDesignServers();
		foreach( $idsObjs as $idsObj ) {
			$selectedPrio = array();
			$idsObj->Prio1 ? $selectedPrio[] = '<!--RES:IDS_PRIO_1-->' : null;
			$idsObj->Prio2 ? $selectedPrio[] = '<!--RES:IDS_PRIO_2-->' : null;
			$idsObj->Prio3 ? $selectedPrio[] = '<!--RES:IDS_PRIO_3-->' : null;
			$idsObj->Prio4 ? $selectedPrio[] = '<!--RES:IDS_PRIO_4-->' : null;
			$idsObj->Prio5 ? $selectedPrio[] = '<!--RES:IDS_PRIO_5-->' : null;
			$prioString = implode(', ', $selectedPrio);

			$activeDisplay = $idsObj->Active ? '<img src="../../config/images/opts_16.gif" />' : '';
			$rows .= "<tr><td><a href='indesignservers.php?action=edit&id={$idsObj->Id}'>".formvar($idsObj->ServerURL)."</a></td>";
			$displayVer = ( $idsObj->DisplayVersion == '???' ) ? '<font color="red"><b>'.formvar($idsObj->DisplayVersion).'</b></font>' : formvar($idsObj->DisplayVersion);
			$rows .= '<td nowrap="nowrap">'.$displayVer.'</td><td>'.formvar($idsObj->Description).'</td><td nowrap="nowrap">'.$prioString.'</td><td>'.$activeDisplay.'</td></tr>'."\r\n";
		}
		$txt = str_replace( '<!--ROWS-->', $rows, $txt );

		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
		$retVal = BizInDesignServer::checkCoveredPriorities();

		if( $retVal ){
			$retVal = '<div><br/>' . BizResources::localize('WARNING') .': '. $retVal . '</div>';
		}

		$txt = str_replace( '<!--WARNING-->', $retVal, $txt );

	break;		
	case 'add':	
		$txt = HtmlDocument::loadTemplate( 'indesignservers_add.htm' );		
		$idsObj = BizInDesignServer::newInDesignServer();
		$txt = str_replace('<!--DISABLE_DEL_BTN-->', 'disabled="disabled"', $txt);
	break;
	case 'edit':
		$txt = HtmlDocument::loadTemplate( 'indesignservers_add.htm' );
		if( is_null($idsObj) ) {
			$idsObj = BizInDesignServer::getInDesignServer( $id );
		}
		$txt = str_replace('<!--DISABLE_DEL_BTN-->', '', $txt); 			
	break;
}

if ( $action == 'edit' || $action == 'add' ) {
	$txt = str_replace('<!--ERROR-->', $err, $txt);
	if ( ($err || $id == 0) && is_null($idsObj) ) { 
		$idsObj = getInDesignServerFromHTTP();
	}
	
	// Build combo of Adobe CS versions and pre-select current IDS version taken from DB 
	// (for existing IDS configs) or pre-select max CS version (for new IDS configs).
	$idsVersions = BizInDesignServer::supportedServerVersions( $idsObj );
	
	// fields
	$txt = str_replace("<!--VAR:HOSTNAME-->", '<input maxlength="63" name="hostname" value="'.formvar($idsObj->HostName).'"/>', $txt );
	$txt = str_replace("<!--VAR:PORTNUMBER-->", '<input maxlength="27" name="portnumber" value="'.formvar($idsObj->PortNumber).'"/>', $txt );	
	$txt = str_replace("<!--VAR:IDSVERSION-->", inputvar('serverversion', $idsObj->ServerVersion, 'combo', $idsVersions, false).'&nbsp;'.$autoVer, $txt );
	$txt = str_replace("<!--VAR:DESCRIPTION-->", '<input name="description" value="'.formvar($idsObj->Description).'"/>', $txt );
	$txt = str_replace("<!--VAR:ACTIVE-->", '<input type="checkbox" name="active" '.($idsObj->Active?'checked="checked"':'').'/>', $txt );
	$txt = str_replace("<!--VAR:PRIO1-->", '<input type="checkbox" name="prio1"'.($idsObj->Prio1?'checked="checked"':'').'/><label><!--RES:IDS_PRIO_1--></label>', $txt );
	$txt = str_replace("<!--VAR:PRIO2-->", '<input type="checkbox" name="prio2"'.($idsObj->Prio2?'checked="checked"':'').'/><label><!--RES:IDS_PRIO_2--></label>', $txt );
	$txt = str_replace("<!--VAR:PRIO3-->", '<input type="checkbox" name="prio3"'.($idsObj->Prio3?'checked="checked"':'').'/><label><!--RES:IDS_PRIO_3--></label>', $txt );
	$txt = str_replace("<!--VAR:PRIO4-->", '<input type="checkbox" name="prio4"'.($idsObj->Prio4?'checked="checked"':'').'/><label><!--RES:IDS_PRIO_4--></label>', $txt );
	$txt = str_replace("<!--VAR:PRIO5-->", '<input type="checkbox" name="prio5"'.($idsObj->Prio5?'checked="checked"':'').'/><label><!--RES:IDS_PRIO_5--></label>', $txt );

	$txt = str_replace("<!--VAR:HIDDEN-->", '<input type="hidden" name="id" value="'.formvar($idsObj->Id).'"/>', $txt );
}

print HtmlDocument::buildDocument($txt);

/**
 * Retrieves one InDesign Server configuration (object) from HTTP params.
 *
 * @return object
 */
function getInDesignServerFromHTTP()
{
	require_once BASEDIR.'/server/dataclasses/InDesignServer.class.php';
	$idsObj = new InDesignServer();
	$idsObj->Id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$idsObj->HostName = $_REQUEST['hostname'];
	$idsObj->PortNumber = intval($_REQUEST['portnumber']);
	$idsObj->Description = $_REQUEST['description'];   
	$idsObj->ServerVersion = $_REQUEST['serverversion'];
	$idsObj->Active = isset($_REQUEST['active']) && $_REQUEST['active'] == 'on';
	$idsObj->Prio1 = isset($_REQUEST['prio1']) && $_REQUEST['prio1'] == 'on';
	$idsObj->Prio2 = isset($_REQUEST['prio2']) && $_REQUEST['prio2'] == 'on';
	$idsObj->Prio3 = isset($_REQUEST['prio3']) && $_REQUEST['prio3'] == 'on';
	$idsObj->Prio4 = isset($_REQUEST['prio4']) && $_REQUEST['prio4'] == 'on';
	$idsObj->Prio5 = isset($_REQUEST['prio5']) && $_REQUEST['prio5'] == 'on';
	BizInDesignServer::enrichServerObject( $idsObj );
	return $idsObj;
}
