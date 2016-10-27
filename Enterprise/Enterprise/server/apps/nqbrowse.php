<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR."/server/admin/global_inc.php";
require_once BASEDIR."/server/secure.php";
require_once BASEDIR."/server/apps/functions.php";
require_once BASEDIR."/server/apps/browse_inc.php";
require_once BASEDIR."/server/bizclasses/BizNamedQuery.class.php";
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure();

// Get form params
$nQuery   = isset($_REQUEST['nq']) ? $_REQUEST['nq'] : '';
$Thumbnail_view = isset($_REQUEST['Thumbnail']) ? $_REQUEST['Thumbnail'] : '';
// $delID and $UnlockID can have multiple values (e.g. "1,3,6")
$delID    = isset($_POST['del']) ? $_POST['del'] : '';
$UnlockID = isset($_POST['unlock']) ? $_POST['unlock'] : '';

if($nQuery == "Browse") {
	header('Location: browse.php?Thumbnail='.$Thumbnail_view);
	exit();
}

$nqTemplate = HtmlDocument::loadTemplate( 'nqbrowse.htm' );

//
/////////////////////// *** "Unlock Selected" command *** ///////////////////////
//

$succeed = true;
$message = "";
if(!empty($delID)){
	$dds=explode(",",$delID);
	if(count($dds)>1)
	{
		array_pop($dds);
	}
	for($counter=0;$counter<count($dds);$counter++)
	{
		$ids=array($dds[$counter]); // can be alien object id  (=string!)
		$permanent = false;
		if(!empty($ids)) {
			try {
				require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
				$service = new WflDeleteObjectsService();
				$deleteObjectsReq = new WflDeleteObjectsRequest();
				$deleteObjectsReq->Ticket = $ticket;
				$deleteObjectsReq->IDs = $ids;
				$deleteObjectsReq->Permanent = $permanent;
				$resp = $service->execute( $deleteObjectsReq );
				
				if( $resp->Reports ) { // Introduced since v8.0
					$message = '';
					$succeed = false;
					foreach( $resp->Reports as $report ) {
						foreach( $report->Entries as $reportEntry ) {
							$message .= $reportEntry->Message . PHP_EOL;
						}
					}
				}
			} catch( BizException $e ) {	
				$message = $e->getMessage();
				$succeed = false;
			}
		}
	}
	$delID="";
}

//
/////////////////////// *** "Deleted Selected" command *** ///////////////////////
//

if(!empty($UnlockID)){
	$UID=explode(",",$UnlockID);
	if(count($UID)>1)
	{
		array_pop($UID);
	}
	$succeed = true;
	for($counter=0;$counter<count($UID);$counter++)
	{
		$uids=array($UID[$counter]); // can be alien object id  (=string!)
		if(!empty($uids)) {
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			try {
				$unlockReq = new WflUnlockObjectsRequest( $ticket, $uids, null );
				$unlockService = new WflUnlockObjectsService();
				$unlockService->execute( $unlockReq );
			} catch( BizException $e ) {
				$message = $e->getMessage();
				$succeed = false;
			}
		}
	}
	$UnlockID="";
}

//
/////////////////////// *** Show query params *** ///////////////////////
//

$namedQueries = array();
$interface ="";
$namedQuery="";

class Props
{
	public $Property;
	public $Value;

	public function __construct($value1, $value2)
	{
		$this->Property = $value1;
		$this->Value = $value2;
	}
};

$comboBoxNamesQueries = '<select name=nq style="width:150px" onchange="submit();">';
$comboBoxNamesQueries .= '<option value="">'.BizResources::localize('LIS_NONE').'...</option>';
$comboBoxNamesQueries .= '<option value="Browse">'.BizResources::localize('ACT_BROWSE').'</option>';

$props = array();
try{
	$nqs = BizNamedQuery::getNamedQueries();
} catch( BizException $e ) {
}
$found = false;

foreach ($nqs as $nq) {
	$parameters = '';

	$namedQuery = $nq->Name; //names of the named queries

	if($namedQuery == $nQuery){
		$comboBoxNamesQueries .= '<option value="'.formvar($namedQuery).'" selected="selected">'.formvar($namedQuery).'</option>';
		$found = true;
	} else {
		$comboBoxNamesQueries .= '<option value="'.formvar($namedQuery).'">'.formvar($namedQuery).'</option>';
	}
	if($found)
	{
		$arrayParams = $nq->Params;
		$nProps = 0;

		for($i = 0 ; $i < sizeof($arrayParams) ; $i++)
		{
			unset($string);
			unset($list);
			unset($bool);
			unset($int);
			$property = $arrayParams[$i];
			$ifName = $property->Name;			//if = interface
			$ifType = $property->Type;
			$ifDefaultValue = $property->DefaultValue;

			// Build unique name for html inputs
			$uniqueName = $namedQuery."_".$i;
			$uniqueName = str_replace( ' ', '_', $uniqueName ); // Remove spaces BZ#3212

			// Input text, checkbox and select must be made dynamic, otherwise there will be problems with cache if the names are the same

			$propvalue = null;
			if ($ifType == 'string')
			{
				if(!empty($_POST[$uniqueName])) $string = $_POST[$uniqueName];
				if(!isset($string)) $string = $ifDefaultValue;
				$parameters .= '<tr><td/><td align="right" width="15%">'.formvar($ifName).' &nbsp;</td>'.
									'<td align="left"><input type="text" name="'.formvar($uniqueName).'" value="'.formvar($string).'"/></td></tr>';
				$propvalue = $string;
			}
			elseif($ifType == 'list')
			{
				if(!empty($_POST[$uniqueName])) $list = $_POST[$uniqueName];
				if(!isset($list)) $list = $ifDefaultValue; // set default value

				$parameters .= '<tr><td/><td align="right" width="15%">'.formvar($ifName).' &nbsp;</td>'.
									'<td align="left"><select name="'.formvar($uniqueName).'" style="width:150px">';
				$ifValueList = $property->ValueList;

				foreach ($ifValueList as $value)
				{
					if($list == $value) {
						$parameters .= '<option value="'.formvar($value).'" selected="selected">'.formvar($value).'</option>';
					} else {
						$parameters .= '<option value="'.formvar($value).'">'.formvar($value).'</option>';
					}
				}
				$parameters .= '</select></td></tr>';
				$propvalue = $list;
			}
			elseif($ifType == 'int')
			{
				if(!empty($_POST[$uniqueName])) $int = $_POST[$uniqueName];
				if(!isset($int)) $int = $ifDefaultValue;

				$parameters .= '<tr><td width="15%">'.formvar($ifName).': </td>'.
								'<td align="left"><input type="text" name="'.formvar($uniqueName).'" value="'.formvar($int).'"/></td></tr>';
				$propvalue = $int;
			}
			elseif($ifType == 'bool'){
				if(!empty($_POST[$uniqueName])) $checkBox = 'checked="checked"';
				if(!isset($checkBox)) $checkBox = ''; // Bug fix: default was not set

				$parameters .= '<tr><td width="15%">'.formvar($ifName).': </td><td align="left">'.
								'<input type="checkbox" name="'.formvar($uniqueName).'" '.$checkBox.'/></td></tr>';
				$propvalue = $checkBox;
			}

			$props[$nProps] = array('PropertyName' => $ifName, 'PropertyType' => $ifType, 'PropertyValue' => $propvalue);
			$nProps++;
		}
		$found = false;
	}
	$namedQueries[] = array('NamedQuery' => $namedQuery, 'Properties' => $props,'HTML' => $parameters);

}
$comboBoxNamesQueries .= '</select>';
$nqTemplate = str_replace ('<!--NAMEDQUERIES-->',$comboBoxNamesQueries, $nqTemplate);

$ThumbnailCheck = ($Thumbnail_view != '') ? 'checked="checked"' : '';
$Object_Thumnail = '<input type="checkbox" name="Thumbnail" '.$ThumbnailCheck.'/>';
$nqTemplate = str_replace ('<!--THUMB-->',$Object_Thumnail, $nqTemplate );

//
/////////////////////// *** Result selection combo *** ///////////////////////
//
$ObjectRes = "";

// Show the fields to fill in the properties
foreach ($namedQueries as $namedQuery){
	if ($namedQuery['NamedQuery'] == $nQuery){
		$nqTemplate = str_replace ("<!--PARAMETERS-->",$namedQuery['HTML'], $nqTemplate);
	}
}

$ObjectTitels = "";
$properties = array();
$listButtonBar = 'hidden';
if(!empty($nQuery))
{
	foreach ($namedQueries as $namedQuery){
		if ($namedQuery['NamedQuery'] == $nQuery){

			$arrayOfProperties = $namedQuery['Properties'];
			$aantalProps = 0;
			if($arrayOfProperties) foreach($arrayOfProperties as $prop)
			{
				$propvalue = $prop['PropertyValue'];
				switch( $prop['PropertyType'] )
				{
					case "string" : $property = new Props($prop['PropertyName'], "$propvalue"); break;
					case "list"   : $property = new Props($prop['PropertyName'], "$propvalue");   break;
					case "int"    : $property = new Props($prop['PropertyName'], "$propvalue");    break;
					case "bool"   : $property = new Props($prop['PropertyName'], ($propvalue == "CHECKED") ? "true" : "false" ); break;
				}
				$properties[$aantalProps] = $property;
				$aantalProps++;
			}

			global $globUser;
			$queryResp = BizNamedQuery::namedQuery( $ticket, $globUser, $namedQuery['NamedQuery'],$properties );

			$num_results = 0;
			$ObjectRes = ShowQueryResults( $queryResp, "", "", $Thumbnail_view, $ObjectTitels, $num_results );

			$listButtonBar = $num_results > 0 ? 'none' : 'hidden';
		}
	}
}

$sHiddenFormParams = inputvar( 'unlock', '', 'hidden' );
$sHiddenFormParams .= inputvar( 'del', '', 'hidden' );

$nqTemplate = str_replace ("<!--LISTBUTTONBAR-->",$listButtonBar, $nqTemplate);
$nqTemplate  = str_replace ("<!--HIDDENFORMPARAMS-->",$sHiddenFormParams, $nqTemplate );
$nqTemplate  = str_replace ("<!--RESULTTITELS-->",$ObjectTitels, $nqTemplate );
$nqTemplate  = str_replace ("<!--RESULTOBJECTS-->",$ObjectRes, $nqTemplate);

print HtmlDocument::buildDocument($nqTemplate, true, $succeed ? '' : "onLoad=\"javaScript:alert('$message !')\"", true);
?>
