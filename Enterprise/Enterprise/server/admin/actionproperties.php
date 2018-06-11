<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
require_once BASEDIR.'/server/dbclasses/DBActionproperty.class.php';

checkSecure('admin');

$sysProps= BizProperty::getSystemPropIds();
$objMap	= getObjectTypeMap();
$actMap = getWorkflowActionTypeMap();

// determine incoming mode
$publ    = isset($_REQUEST['publ'])    ? intval($_REQUEST['publ']) : 0; // Publication id. Zero for all.
$action  = isset($_REQUEST['action'])  ? $_REQUEST['action']  : ''; 	// Internal action string, such as 'CopyTo'. Empty for <all>.
$objType = isset($_REQUEST['objtype']) ? $_REQUEST['objtype'] : ''; 	// Internal object type string, such as ArticleTemplate. Empty for <all>.

// Validate data retrieved from form (XSS attacks)
if( !array_key_exists($action, $actMap) ) { $action = '' ; }
if( !array_key_exists($objType, $objMap) ) { $objType = ''; }

$dum = '';
cookie('actionproperties', !(isset($_REQUEST['isform']) && $_REQUEST['isform']), $publ, $action, $objType, $dum, $dum, $dum, $dum );

// Re-validate data retrieved from cookie! (XSS attacks)
$publ = intval($publ);
if( !array_key_exists($action, $actMap) ) { $action = ''; }
if( !array_key_exists($objType, $objMap) ) { $objType = ''; }
//echo 'DEBUG: publ=['. $publ .'] action=['. $action .'] objtype=['. $objType .']</br>';

$app = new ActionPropertiesAdminApp( $publ, $objType, $action, $sysProps );

$app->processRequestData();

$txt = $app->loadHtmlTemplate();

// Upper part - build selection of combo box for Brand, Object Type and Action
$txt = $app->buildSelectionComboBoxes( $objMap, $actMap, $txt );

// Middle part - build Brand-Object Type-Action link list
$txt = $app->createBrandObjectTypeActionLinks( $objMap, $actMap, $txt );

// Lower part, build current action properties list
$txt = $app->buildCurrentActionProperties( $txt );

// generate total page
print HtmlDocument::buildDocument( $txt );

/**
 * Helper class for the admin application: Dialog Setup Admin Page
 */
class ActionPropertiesAdminApp
{
	private $publ = null;
	private $objType = null;
	private $action = null;
	private $onlyQuery = null;
	private $mode = null;
	private $sAll = null;
	private $sysProps = null;

	public function __construct( $publ, $objType, $action, $sysProps )
	{
		$this->publ = $publ;
		$this->objType = $objType;
		$this->action = $action;
		$this->sysProps = $sysProps;
		$this->sAll = BizResources::localize("LIS_ALL");
	}

	/**
	 * Build upper part of the admin page, build the combo box for Brand, Object Type and Action
	 *
	 * @param array $objMap Array of object type
	 * @param array $actMap Array of action type
	 * @param string $txt   HTML strings
	 *
	 * @return string $txt HTML strings
	 */
	public function buildSelectionComboBoxes( $objMap, $actMap, $txt )
	{
		$combo = $this->buildBrandComboBox();
		$txt = str_replace('<!--COMBO:PUBL-->', $combo, $txt );

		$combo = $this->buildObjectTypeComboBox( $objMap );
		$txt = str_replace('<!--COMBO:TYPE-->', $combo, $txt );

		$combo = $this->buildActionComboBox( $actMap );
		$txt = str_replace('<!--COMBO:ACTION-->', $combo, $txt );
		return $txt;
	}

	/**
	 * Build Brand combo box
	 *
	 * @return string $combo Brand HTML combo element text
	 */
	private function buildBrandComboBox()
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		$rows = DBPublication::listPublications(array('id', 'publication'));
		$combo = inputvar( 'isform', '1', 'hidden' );
		$combo .= '<select name="publ" onchange="submit();">';
		$combo .= '<option value="">&lt;'.$this->sAll.'&gt;</option>';
		if( $rows ) foreach( $rows as $row ) {
			$selected = ($row['id'] == $this->publ) ? 'selected="selected"' : '';
			$combo .= '<option value="'.$row['id'].'" '.$selected.'>'.formvar($row['publication']).'</option>';
		}
		$combo .= '</select>';
		return $combo;
	}

	/**
	 * Build object type combo box
	 *
	 * @param array $objMap Array of object type
	 * @return string $combo Object type HTML combo element text
	 */
	private function buildObjectTypeComboBox( $objMap )
	{
		$combo = '<select name="objtype" onchange="submit();">';
		$combo .= '<option value="">&lt;'.$this->sAll.'&gt;</option>';
		foreach( $objMap as $k => $sDisplayType ) {
			$selected = ($k == $this->objType) ? 'selected="selected"' : '';
			$combo .= '<option value="'.$k.'" '.$selected.'>'.formvar($sDisplayType).'</option>';
		}
		$combo .= '</select>';
		return $combo;
	}

	/**
	 * Build action combo box
	 *
	 * @param array $actMap Array of action type
	 * @return string $combo Action HTML combo element text
	 */
	private function buildActionComboBox( $actMap )
	{
		$combo = '<select name="action" onchange="submit();">';
		$combo .= '<option value="">&lt;'.$this->sAll.'&gt;</option>';
		foreach( $actMap as $k => $sDisplayValue ) {
			$selected = ($k == $this->action) ? 'selected="selected"' : '';
			$combo .= '<option value="'.$k.'" '.$selected.'>'.formvar($sDisplayValue).'</option>';
		}
		$combo .= '</select>';
		return $combo;
	}

	/**
	 * Insert new action property retrieved from the Form value.
	 */
	private function insertActionPropertyFromTheForm()
	{
		$order = isset($_REQUEST['order']) ? intval($_REQUEST['order']) : 0; // Sorting order field. Zero when not filled.
		$prop = isset($_REQUEST['prop']) ? $_REQUEST['prop'] : '';           // Name of action property. Always set.
		$edit = isset($_REQUEST['edit']) ? $_REQUEST['edit'] : '';
		$mandatory = isset($_REQUEST['mandatory']) ? $_REQUEST['mandatory'] : '';
		$restricted = isset($_REQUEST['restricted']) ? $_REQUEST['restricted'] : '';
		$multipleObjects = isset($_REQUEST['multipleobjects']) ? $_REQUEST['multipleobjects'] : '';
		//echo 'DEBUG: order=['. $order .'] prop=['. $prop .'] edit=['. $edit .'] mandatory=['. $mandatory .'] restricted=['. $restricted .']</br>';
		// Validate data retrieved from form (XSS attacks)
		if( in_array($prop, $this->sysProps) ) {
			$edit = '';
		}
		$edit = $edit ? 'on' : '';
		$mandatory = $mandatory ? 'on' : '';
		$restricted = $restricted ? 'on' : '';
		$multipleObjects = $multipleObjects ? 'on' : '';
		if( $prop ) {
			$values = array('publication' => $this->publ, 'action' => $this->action, 'type' => $this->objType, 'orderid' => $order, 'property' => $prop,
				'edit' => $edit, 'mandatory' => $mandatory, 'restricted' => $restricted,
				'multipleobjects' => $multipleObjects );
			$this->insertActionProperty( $values );
		}
	}

	/**
	 * Insert new action property into database.
	 *
	 * @param string[] $values
	 */
	private function insertActionProperty( $values )
	{
		require_once BASEDIR . '/server/dbclasses/DBActionproperty.class.php';
		DBActionproperty::insertActionproperty( $values );
	}

	/**
	 * Loop through all the current action properties, and perform update action.
	 *
	 * @param integer $numberOfRecords Number of records count
	 */
	private function updateActionProperties( $numberOfRecords )
	{
		for( $i=0; $i < $numberOfRecords; $i++ ) {
			$prop = $_REQUEST["prop$i"];            // Name of action property. Always set.
			if( !$this->isConfigurableField( $prop )) {
				continue; // Don't bother to update fields that are not configurable by the end-user.
			}
			$id = intval($_REQUEST["id$i"]);        // Record id. Used in POST and GET requests.
			$order = intval($_REQUEST["order$i"]);  // Sorting order field. Zero when not filled.
			$edit = isset($_REQUEST["edit$i"]) ? $_REQUEST["edit$i"] : '';
			$mandatory = isset($_REQUEST["mandatory$i"]) ? $_REQUEST["mandatory$i"] : '';
			$restricted = isset($_REQUEST["restricted$i"]) ? $_REQUEST["restricted$i"] : '';
			$multipleObjects = isset($_REQUEST["multipleobjects$i"]) ? $_REQUEST["multipleobjects$i"] : '';
			// Validate data retrieved from form (XSS attacks)
			if( in_array($prop, $this->sysProps) ) {
				$edit = '';
			}
			$edit = $edit ? 'on' : '';
			$mandatory = $mandatory ? 'on' : '';
			$restricted = $restricted ? 'on' : '';
			$multipleObjects = $multipleObjects ? 'on' : '';
			//echo 'DEBUG: order=['. $order .'] prop=['. $prop .'] edit=['. $edit .'] mandatory=['. $mandatory .'] restricted=['. $restricted .']</br>';
			$values = array('publication' => $this->publ, 'orderid' => $order, 'property' => $prop, 'edit' => $edit,
				'mandatory' => $mandatory,	'restricted' => $restricted,
				'multipleobjects' => $multipleObjects );
			DBActionproperty::updateActionproperty( $id, $values );
		}
	}

	/**
	 * Delete action propert(ies) selected on the Form.
	 *
	 * @param int $numberOfRecords
	 */
	private function deleteActionProperty( $numberOfRecords )
	{
		require_once BASEDIR . '/server/dbclasses/DBActionproperty.class.php';
		$propIdsToBeDeleted = array();
		for( $i=0; $i < $numberOfRecords; $i++ ) {
			$deleteCheckboxChecked = isset( $_REQUEST["multiDelete$i"] ) ? $_REQUEST["multiDelete$i"] : '';
			if( $deleteCheckboxChecked ) {
				$propIdsToBeDeleted[] = intval($_REQUEST["id$i"]);
			}
		}
		if( $propIdsToBeDeleted ) {
			DBActionproperty::deleteActionProperties( $propIdsToBeDeleted );
		}
	}

	/**
	 * To delete all action properties setup in the page
	 *
	 * @param int[] $numberOfRecords
	 */
	private function deleteAllActionProperty( $numberOfRecords )
	{
		require_once BASEDIR . '/server/dbclasses/DBActionproperty.class.php';
		$propIdsToBeDeleted = array();
		for( $i=0; $i < $numberOfRecords; $i++ ) {
			$propIdsToBeDeleted[] = intval($_REQUEST["id$i"]);
		}
		if( $propIdsToBeDeleted ) {
			DBActionproperty::deleteActionProperties( $propIdsToBeDeleted );
		}
	}

	/**
	 * Create configured brand-object type-action link list
	 *
	 * @param array $objMap Array of object type
	 * @param array $actMap Array of action type
	 * @param string $txt HTML strings
	 * @return string $txt HTML strings
	 */
	public function createBrandObjectTypeActionLinks( $objMap, $actMap,  $txt )
	{
		$rows = DBActionproperty::listWorkflowActionPropertyGroups();
		// Show results in a list of hyperlinks to select the Brand/Type/Act combos when user clicks on them...
		$brandTypeActionlist = "";

		if( $rows ) foreach( $rows as $row ) {
			// Skip SetPublishProperties action for PublishFormTemplates, they should never be editable from the action properties page.
			if (isset($row['action']) && isset($row['type']) && trim($row['action']) == 'SetPublishProperties' && trim($row['type']) == 'PublishFormTemplate') {
				continue;
			}
			$disp_pub = $row['publication'];
			if( !$disp_pub ) $disp_pub = '<'.$this->sAll.'>'; // set to "<all>" when not defined
			$disp_type = trim($row['type']);
			if( $disp_type ) {
				$disp_type = $objMap[$disp_type];
			} else {
				$disp_type = '<'.$this->sAll.'>'; // set to "<all>" when not defined
			}
			$disp_act = trim($row['action']);
			if( $disp_act ) {
				$disp_act = $actMap[$disp_act];
			} else {
				$disp_act = '<'.$this->sAll.'>'; // set to "<all>" when not defined
			}
			$brandTypeActionlist .= '<a href="javascript:SetCombos(\''.$row['pubid'].'\',\''.formvar(trim($row['type'])).'\',\''.formvar(trim($row['action'])).'\');">';
			$brandTypeActionlist .= formvar($disp_pub).' - '.formvar($disp_type).' - '.formvar($disp_act).'</a><br/>';
		}
		$txt = str_replace("<!--PUB_TYPE_ACTION_LIST-->", $brandTypeActionlist, $txt );
		return $txt;
	}

	/**
	 * Generate HTML text for the current action properties table list
	 * If exact brand is found from 1st left join query result, only get the custom property display name from the 2nd left join.
	 * If exact brand is not found, then continue to get custom displayname from the 2nd left join query result.
	 *
	 * @param boolean $showMultiObj When True to show multiple objects|False not to show
	 * @param array $locals Array of property infos
	 * @param array $rows Array of action properties database records
	 * @param string $detailTxt HTML strings
	 * @param int $numberOfRecords [In/Out] Total number of action properties listed.
	 * @return string $detailTxt HTML strings of the table list
	 */
	private function listCurrentActionProperties( $showMultiObj, $locals, $rows, $detailTxt, &$numberOfRecords )
	{
		$i = 0;
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		$exactBrandFound = $this->isExactBrandFound( $rows );
		if( $rows ) foreach( $rows as $row ) {
			$dprop = $row['dispname'];
			$prop = $row['property'];
			$isConfigurable = $this->isConfigurableField( $prop );
			$isCustomProperty = BizProperty::isCustomPropertyName( $prop );
			if( !$dprop ) {
				if( $exactBrandFound ) {
					if( $isCustomProperty ) {
						$dprop = $row['dispname2'];
						$row['category'] = $row['category2'];
					} else {
						$dprop = null;
						$row['category'] = null;
					}
				} else {
					$dprop = $row['dispname2'];
					$row['category'] = $row['category2'];
				}
			}
			if( $isCustomProperty ) {
				$prop = "* ".($dprop?$dprop:substr($prop,2));
			} else if( $dprop ) {
				$prop = "$dprop ($prop)";
			} else {
				if( array_key_exists($prop, $locals ) ) {
					$prop = $locals[$prop]->DisplayName." ($prop)";
				}
			}
			$clr = $color[$flip];
			$flip = 1- $flip;
			$nonAdjustableDefaultFieldsMsg = 'This setting cannot be adjusted nor deleted because it is a mandatory property.'; // 10.x.x TODO: Localise the string
			if( $isConfigurable ) {
				$deleteCheckbox = inputvar( "multiDelete$i", '', 'checkbox', null, true, null, !$isConfigurable );
				$deleteCheckbox = $this->placeCheckboxInAToolTipWrapper( $deleteCheckbox, BizResources::localize("ACT_DELETE_PERMANENT_SELECTED_ROWS") );
			} else {
				$deleteCheckbox = '';
			}
			if( $this->isActionOnlyForFieldsDisplay( $this->action ) ) {
				$detailTxt .= "<tr$clr>";
				$detailTxt .= '<td align="center">' . $deleteCheckbox. '</td>';
				$detailTxt .= "<td>".inputvar("order$i", $row['orderid'], 'small').'</td>';
				$detailTxt .= '<td>'.formvar($prop).inputvar("prop$i",$row['property'],'hidden').'</td>';
				$detailTxt .= '</tr>';
			} else {
				$detailTxt .= "<tr$clr>";
				$detailTxt .= '<td align="center">' . $deleteCheckbox.'</td>';
				$detailTxt .= "<td>".$row['category'].'</td>';
				$detailTxt .= '<td>'.inputvar("order$i", $row['orderid'], 'small').'</td>';
				$detailTxt .= '<td>'.formvar($prop).inputvar("prop$i",$row['property'],'hidden').'</td>';
				if( empty( $row['edit'] ) && in_array( $row['property'], $this->sysProps ) ) {
					$detailTxt .= '<td align="center">'.LOCKIMAGE.'</td>';
				} else {
					$title = $isConfigurable ? BizResources::localize("OBJ_EDITABLE") : $nonAdjustableDefaultFieldsMsg;
					$checkbox = inputvar("edit$i", $row['edit'], 'checkbox', null, true, null, !$isConfigurable );
					$detailTxt .= '<td align="center">' . $this->placeCheckboxInAToolTipWrapper( $checkbox, $title ) . '</td>';
				}
				$title = $isConfigurable ? BizResources::localize("OBJ_MANDATORY") : $nonAdjustableDefaultFieldsMsg;
				$checkbox = inputvar("mandatory$i", $row['mandatory'], 'checkbox', null, true, null, !$isConfigurable );
				$detailTxt .= '<td align="center">' . $this->placeCheckboxInAToolTipWrapper( $checkbox, $title ) . '</td>';

				$title = $isConfigurable ? BizResources::localize("OBJ_RESTRICTED") : $nonAdjustableDefaultFieldsMsg;
				$checkbox = inputvar("restricted$i", $row['restricted'], 'checkbox', null, true, null, !$isConfigurable );
				$detailTxt .= '<td align="center">' . $this->placeCheckboxInAToolTipWrapper( $checkbox, $title ) . '</td>';

				if( $showMultiObj ) {
					$title = $isConfigurable ? BizResources::localize("OBJ_MULTIPLE_OBJECTS") : $nonAdjustableDefaultFieldsMsg;
					$checkbox = inputvar("multipleobjects$i", $row['multipleobjects'], 'checkbox', null, true, null, !$isConfigurable );
					$detailTxt .= '<td align="center">' . $this->placeCheckboxInAToolTipWrapper( $checkbox, $title ) . '</td>';
				} else { // Don't fill in the multiple objects column.
					$detailTxt .= '<td style="display:none"></td>'; // No checkbox.
				}
				$detailTxt .= "</tr>";
			}
			$detailTxt .= inputvar( "id$i", $row['id'], 'hidden' );
			$i++;
		}
		$detailTxt .= inputvar( 'recs', $i, 'hidden' );
		$numberOfRecords = $i;
		return $detailTxt;
	}

	/**
	 * Generate the new action property combo box with the action properties table list
	 * If exact brand is found from 1st left join query result, only get the custom property display name from the 2nd left join.
	 * If exact brand is not found, then continue to get custom displayname from the 2nd left join query result.
	 *
	 * @param boolean $showMultiObj When True to show multiple objects|False not to show
	 * @param array $props Array of properties
	 * @param array $rows Array of action properties database records
	 * @param string $detailTxt HTML strings
	 * @return string $detailTxt HTML strings of the table list
	 */
	private function listNewAndCurrentActionProperties( $showMultiObj, $props, $rows, $detailTxt )
	{
		if( $this->isActionOnlyForFieldsDisplay( $this->action )) {
			$detailTxt .= '<tr>';
			$detailTxt .= '<td>'.inputvar('order', '', 'small').'</td>';
			$detailTxt .= '<td>'.inputvar('prop', '', 'combo', $props, false).'</td>';
			$detailTxt .= '</tr>';
			$detailTxt .= inputvar( 'insert', '1', 'hidden' );
		} else {
			$detailTxt .= '<tr><td></td><td>'.inputvar('order', '', 'small').'</td>';
			$detailTxt .= '<td>'.inputvar('prop', '', 'combo', $props, false).'</td>';
			$detailTxt .= '<td align="center">'.inputvar('edit','', 'checkbox', null, true, BizResources::localize("OBJ_EDITABLE")).'</td>';
			$detailTxt .= '<td align="center">'.inputvar('mandatory','', 'checkbox', null, true, BizResources::localize("OBJ_MANDATORY")).'</td>';
			$detailTxt .= '<td align="center">'.inputvar('restricted','', 'checkbox', null, true, BizResources::localize("OBJ_RESTRICTED")).'</td>';
			if( $showMultiObj ) {
				$detailTxt .= '<td align="center">'.inputvar('multipleobjects','', 'checkbox', null, true, BizResources::localize("OBJ_MULTIPLE_OBJECTS")).'</td>';
			} else {
				$detailTxt .= '<td style="display:none"></td>';
			}
			$detailTxt .= '<td style="display:none"></td></tr>';
			$detailTxt .= inputvar( 'insert', '1', 'hidden' );
		}
		// show other states as info
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		$exactBrandFound = $this->isExactBrandFound( $rows );
		foreach( $rows as $row ) {
			$dprop = $row['dispname'];
			$prop = $row['property'];
			$isCustomProperty = BizProperty::isCustomPropertyName( $prop );
			if( !$dprop ) {
				if( $exactBrandFound ) {
					if( $isCustomProperty ) {
						$dprop = $row['dispname2'];
						$row['category'] = $row['category2'];
					} else {
						$dprop = null;
						$row['category'] = null;
					}
				} else {
					$dprop = $row['dispname2'];
					$row['category'] = $row['category2'];
				}
			}
			if( $isCustomProperty ) {
				$prop = "* ".($dprop?$dprop:substr($prop,2));
			} else if( $dprop ) {
				$prop = "$dprop ($prop)";
			} else {
				$prop = "$prop";
			}
			$clr = $color[$flip];
			$flip = 1- $flip;
			if( $this->isActionOnlyForFieldsDisplay( $this->action )) {
				$detailTxt .= "<tr$clr><td>".$row['orderid'].'</td><td>'.formvar($prop).'</td>';
			} else {
				$detailTxt .= "<tr$clr><td>".formvar($row['category']).'</td><td>'.$row['orderid'].'</td>';
				$detailTxt .= '<td>'.formvar($prop).'</td>';
				$isConfigurable = $this->isConfigurableField( $prop );
				if( empty( $row['edit'] ) && in_array( $row['property'], $this->sysProps )) {
					$detailTxt .= '<td align="center">'.(trim($row['edit'])?CHECKIMAGE:LOCKIMAGE).'</td>';
				} else {
					$detailTxt .= '<td align="center">'.(trim($row['edit'])?CHECKIMAGE:'').'</td>';
				}
				$detailTxt .= '<td align="center">'.(trim($row['mandatory'])?CHECKIMAGE:'').'</td>';
				$detailTxt .= '<td align="center">'.(trim($row['restricted'])?CHECKIMAGE:'').'</td>';
				if( $showMultiObj ) {
					$detailTxt .= '<td align="center">'.(trim($row['multipleobjects'])?CHECKIMAGE:'').'</td>';
				} else {
					$detailTxt .= '<td style="display:none"></td>';
				}
				$detailTxt .= '<td style="display:none"></td></tr>';
			}
		}
		return $detailTxt;
	}

	/**
	 * Draw the Form to either show or hide Edit, Update and Delete buttons depending on the action ( $this->mode ).
	 *
	 * @param string $txt
	 * @param int $numberOfRecords
	 * @return string
	 */
	private function showOrHideButtons( $txt, $numberOfRecords )
	{
		switch( $this->mode ) {
			case "view":
			case "delete":
			case "reset":
				$txt = str_replace("<!--ADD_BUTTON-->",  '', $txt );
				$txt = str_replace("<!--UPDATE_BUTTON-->",( $numberOfRecords == 0 ) ? 'display:none' : '', $txt );
				$txt = str_replace("<!--DELETE_BUTTON-->",( $numberOfRecords == 0 ) ? 'display:none' : '', $txt );
				$txt = str_replace("<!--RESET_BUTTON-->",( $numberOfRecords == 0 ) ? 'display:none' : '', $txt );
				break;
			case "add":
				$txt = str_replace("<!--ADD_BUTTON-->",  'display:none', $txt );
				$txt = str_replace("<!--UPDATE_BUTTON-->",'', $txt );
				$txt = str_replace("<!--DELETE_BUTTON-->",'display:none', $txt );
				$txt = str_replace("<!--RESET_BUTTON-->",'display:none', $txt );
				break;
			case "update";
				$txt = str_replace("<!--ADD_BUTTON-->",  '', $txt );
				$txt = str_replace("<!--UPDATE_BUTTON-->",( $numberOfRecords == 0 ) ? 'display:none' : '', $txt );
				$txt = str_replace("<!--DELETE_BUTTON-->",'', $txt );
				$txt = str_replace("<!--RESET_BUTTON---->",( $numberOfRecords == 0 ) ? 'display:none' : '', $txt );
				break;
		}
		return $txt;
	}

	/**
	 * Load different Html Template.
	 *
	 * @return string $txt HTML strings
	 */
	public function loadHtmlTemplate()
	{
		return HtmlDocument::loadTemplate( 'actionproperties.htm' );
	}

	/**
	 * Build the lower part of the admin page, current action properties
	 *
	 * @param string $txt HTML strings
	 * @return string $txt HTML Strings
	 */
	public function buildCurrentActionProperties( $txt )
	{
		require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
		$staticProps   = BizProperty::getStaticPropIds();
		$dynamicProps  = BizProperty::getDynamicPropIds();
		$xmpProps      = BizProperty::getXmpPropIds();
		$wfProps       = BizProperty::getWorkflowPropIds();
		$readonlyProps = BizProperty::getSpecialQueryPropIds();

		$already = array();
		$wiwiwUsages = null;
		$usages = BizProperty::getPropertyUsages( $this->publ, $this->objType, $this->action,
			false,  // BZ#6516: Do not return default/static properties here
			true,  // BZ#14553: Request NOT to fall back at global definition levels. Specified level only.
			null,
			$wiwiwUsages, // $wiwiwUsages = null when it is not for Template and PublishForm.
			false ); //
		if( $usages ) foreach( $usages as $usage ) { // $onlyMultiSetProperties = false: Returns all properties regardless of single/multi-set properties support.
			$already[] = $usage->Name;
		}

		$limitPub = true;
		switch( $this->action ) {
			case 'SendTo':
				$allProps = $wfProps;
				break;
			case 'Preview':
				$allProps = array_merge($staticProps, $dynamicProps, $xmpProps, $readonlyProps);
				break;
			default:
				$allProps = array_merge($dynamicProps, $xmpProps);
				$already[] = 'ID';
				$already[] = 'Type';
				$already[] = 'Name';
				break;
		}

		// get customfields
		$cust = array();
		$trans = array();
		$publication = $limitPub ? $this->publ : 0;
		$propObjs = BizProperty::getProperties( $publication, $this->objType, null, null, false, false, true );

		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		$excludedPropTypes = BizCustomField::getExcludedObjectFields();

		foreach( $propObjs as $propObj ) {
			$name = $propObj->Name;
			$isCustomProperty = BizProperty::isCustomPropertyName( $name );
			if( $isCustomProperty && $this->action == 'SendTo' ) continue;
			// Skip the custom properties where the PublishSystem and/or TemplateId property is set. This isn't supported on a Dialog.
			// Additionally exclude any prop for which the type is not supported.
			if( $isCustomProperty
				&& ( $propObj->PublishSystem || $propObj->TemplateId || in_array($propObj->Type, $excludedPropTypes)) ) {
				continue;
			}

			// Only show the properties where the AdminUI internal property is set to true.
			if( $isCustomProperty && $propObj->AdminUI ) $cust[] = $name;
			if( $propObj->DisplayName ) $trans[$name] = $propObj->DisplayName;
		}

		$hasCust = count($cust);
		if( $limitPub && $this->action != 'SendTo' ) {
			$properties = DBProperty::listPropertyDisplayNames();
			foreach( $properties as $propertyName => $property ) {
				if( BizProperty::isCustomPropertyName( $propertyName ) &&
					!isset( $propObjs[$propertyName] )) { // Only continues if it is not yet checked in the top foreach loop.
					if ( $hasCust ) {
						continue;			// if there are already specific customfields, skip generic customfields
					} else {
						$cust[] = $propertyName;
					}
				}
			}
		}
		$allProps = array_merge($allProps, $cust);

		$props = array();
		$locals = BizProperty::getPropertyInfos();
		foreach( $allProps as $prop ) {
			if( !in_array($prop, $already) ) {
				$isCustomProperty = BizProperty::isCustomPropertyName( $prop );
				$pre = '';
				if( $isCustomProperty ) {
					$pre ="* ";
				}
				if( $trans[$prop] ) {
					if( $isCustomProperty ) {
						$props[$prop] = $pre.$trans[$prop].' ('.substr($prop,2).')';
					} else {
						$props[$prop] = $pre.$trans[$prop]." ($prop)";
					}
				} else {
					$name = $prop;
					if( $isCustomProperty ) {
						$name = substr($name,2);
					} else {
						if( array_key_exists($prop, $locals ) ) {
							$name = $locals[$prop]->DisplayName;
						} // else $name = $name
					}
					$props[$prop] = $pre.$name." ($prop)";
				}
			}
		}
		asort( $props );

		$detailTxt = '';
		$multiObjAllowedActions = $multiObjAllowedActions = array_merge( array( '' ), BizWorkflow::getMultiObjectsAllowedActions() );
		$showMultiObj = in_array( $this->action, $multiObjAllowedActions );

		if( $usages ) {
 			$rows = DBActionproperty::listActionPropertyWithNames( $this->publ, $this->objType, $this->action, true );
 		} else {
		   $rows = array();
		   if( $this->mode == 'add' ) {
			   $usages = $this->preInsertDefaultProperties();
			   $rows = DBActionproperty::listActionPropertyWithNames( $this->publ, $this->objType, $this->action, true );
		   }
	   }

		switch( $this->mode ) {
			case 'view':
			case 'update':
			case 'delete':
			case 'reset':
				$numberOfRecords = 0;
				$detailTxt = $this->listCurrentActionProperties( $showMultiObj, $locals, $rows, $detailTxt, $numberOfRecords );
				break;
			case 'add':
				$numberOfRecords = count( $rows );
				$detailTxt = $this->listNewAndCurrentActionProperties( $showMultiObj, $props, $rows, $detailTxt );
				break;
		}

		$txt = $this->showOrHideButtons( $txt, $numberOfRecords );
		$txt = str_replace("<!--DELETE_COLUMN-->", ( $this->mode == 'add' ) ? 'display:none' : (( $numberOfRecords > 0 ) ? '' : 'display:none'), $txt );
		$txt = str_replace("<!--WORKFLOW_COLUMNS-->",$this->isActionOnlyForFieldsDisplay( $this->action ) ? 'display:none' : '', $txt );
		$txt = str_replace("<!--PREVIEW_COLUMNS-->",$this->isActionOnlyForFieldsDisplay( $this->action ) ? '' : 'display:none', $txt );
		$txt = str_replace("<!--MULTIPLE_OBJECTS_CELL-->", $showMultiObj ? '' : 'display:none', $txt );
		$txt = str_replace("<!--ROWS-->", $detailTxt, $txt);
		$txt = str_replace("<!--IMG_LOCKIMG-->", LOCKIMAGE, $txt);
		return $txt;
	}

	/**
	 * Process request data, follow by insert, update or delete action on the action property.
	 */
	public function processRequestData()
	{
		if( isset( $_REQUEST['update'] ) && $_REQUEST['update'] ) {
			$this->mode = 'update';
			$numberOfRecords = isset( $_REQUEST['recs'] ) ? intval( $_REQUEST['recs'] ) : 0;
			$insert = isset( $_REQUEST['insert'] ) ? (bool)$_REQUEST['insert'] : false;
		} else if( isset( $_REQUEST['delete'] ) && $_REQUEST['delete'] ) {
			$this->mode = 'delete';
			$numberOfRecords = isset( $_REQUEST['recs'] ) ? intval( $_REQUEST['recs'] ) : 0;
			$insert = false;
		} else if( isset( $_REQUEST['reset'] ) && $_REQUEST['reset'] ) {
			$this->mode = 'reset';
			$numberOfRecords = isset( $_REQUEST['recs'] ) ? intval( $_REQUEST['recs'] ) : 0;
			$insert = false;
		} else if( isset( $_REQUEST['add'] ) && $_REQUEST['add'] ) {
			$this->mode = 'add';
			$numberOfRecords = isset( $_REQUEST['recs'] ) ? intval( $_REQUEST['recs'] ) : 0;
			$insert = isset( $_REQUEST['insert'] ) ? (bool)$_REQUEST['insert'] : false;
		} else {
			$this->mode = 'view';
			$numberOfRecords = 0;
			$insert = false;
		}

		// handle request on Update/Add Action property
		if( $this->mode == 'update' && $numberOfRecords > 0 ) {
			$this->updateActionProperties( $numberOfRecords );
		}
		if( $insert === true ) {
			$this->insertActionPropertyFromTheForm();
		}
		if( $this->mode == 'delete' ) {
			$this->deleteActionProperty( $numberOfRecords );
		}
		if( $this->mode == 'reset' ) {
			$this->deleteAllActionProperty( $numberOfRecords );
		}
	}

	/**
	 * Check if the exact brand is found from the first join return value
	 *
	 * @param $rows
	 * @return bool
	 */
	private function isExactBrandFound( $rows )
	{
		$isExactBrandFound = false;
		foreach( $rows as $row ) {
			if( $row['publication'] == $this->publ ) {
				$isExactBrandFound = true;
				break;
			}
		}
		return $isExactBrandFound;
	}

	/**
	 * Based on the Action, it checks if the property can be configured from the UI.
	 *
	 * For certain properties, they are the mandatory fields and therefore user cannot edit nor remove them.
	 *
	 * @since 10.x.x
	 * @param string $prop
	 * @return bool Returns true when the property can be configured from the UI, false otherwise.
	 */
	private function isConfigurableField( $prop )
	{
		require_once BASEDIR .'/server/bizclasses/BizProperty.class.php';
		$nonConfigurableFields = BizProperty::getStaticPropIds();
		return !in_array( $prop, $nonConfigurableFields );
	}


	/**
	 * To check if the fields to be displayed for the action is only meant for displayed and not adjustable.
	 *
	 * @since 10.x.x
	 * @param string $action
	 * @return bool
	 */
	private function isActionOnlyForFieldsDisplay( $action )
	{
		return $action === 'Preview';
	}

	/**
	 * To place a checkbox in a tooltip wrapper.
	 *
	 * The tooltip wrapper is typically needed when the checkbox is set to disabled.
	 * This is due to jquery-ui tooltip doesn't work on disabled elements ( disabled
	 * elements do not trigger any DOM events ).
	 * So, the workaround is that the checkbox is drawn around the tooltip wrapper.
	 * For non-disabled checkbox, this function can still be called, CSS is catered
	 * to take care of the disabled checkbox.
	 *
	 * @since 10.x.x
	 * @param string $title
	 * @param string $checkbox
	 * @return string
	 */
	private function placeCheckboxInAToolTipWrapper( string $checkbox, string $title ):string
	{
		$detailTxt = '<div class="tooltip-wrapper" title="'.$title.'" >';
		$detailTxt .= $checkbox;
		$detailTxt .= '</div>';
		return $detailTxt;
	}

	/**
	 * Inserts a list of default properties ( with or without non-static properties ).
	 *
	 * On a fresh setup of Dialog Setup, list of default static properties will be pre-added
	 * before the user can add a new property. On top of that, user can choose if some selective
	 * non-static (dynamic) properties need to be added.
	 * If user choose to add in the non-static properties as well, both static and non-static default
	 * properties will be added into database; otherwise, only the default static properties will
	 * be added.
	 *
	 * @since 10.x.x
	 */
	private function preInsertDefaultProperties():void
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
		$addDefaultDynamicFields = isset( $_REQUEST['addDefaultDynamic'] ) ? strval( $_REQUEST['addDefaultDynamic'] ) : "false"; // Whether the default fields should be added.
		if( $addDefaultDynamicFields == 'true' ) {
			$usages = BizProperty::defaultPropertyUsageWhenNoUsagesAvailable( $this->action, false );
		} else {
			$usages = BizProperty::defaultPropertyUsageWhenNoUsagesAvailable( $this->action, true );
		}

		if( $this->action != '' ) { // When action is <All>, don't fix anything as some action needs 'Dossier' and some don't.
			BizWorkflow::fixDossierPropertyUsage( $this->action, $this->objType, '', $usages );
		}
		$order = 5;
		$listOfValues = array();
		if( $usages ) foreach( $usages as $usage ) {
			$values = array(
				$this->publ,
				$this->action,
				$this->objType,
				$order,
				$usage->Name,
				$usage->Editable ? 'on' : '',
				$usage->Mandatory ? 'on' : '',
				$usage->Restricted ? 'on' : '',
				$usage->MultipleObjects ? 'on' : '',
			);
			$listOfValues[] = $values;
			$order += 5;
		}
		$fields = array( 'publication', 'action', 'type', 'orderid', 'property', 'edit', 'mandatory', 'restricted', 'multipleobjects' );
		require_once BASEDIR . '/server/dbclasses/DBActionproperty.class.php';
		DBActionproperty::insertActionsProperty( $fields, $listOfValues );
	}
}
