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
$actMap = getQueryActionTypeMap();

// determine incoming mode
$publ = 0; // Cannot set/configure at brand level but for DB saving, publication needs a value.
$objType = ''; // Cannot set/configure at brand level but for DB saving, object type needs a value.
$action  = isset($_REQUEST['action'])  ? $_REQUEST['action']  : 'Query'; 	// Internal action string, such as 'Query', 'QueryOut'.

// Validate data retrieved from form (XSS attacks)
if( !array_key_exists($action, $actMap) ) { $action = '' ; }
if( !array_key_exists($objType, $objMap) ) { $objType = ''; }

$dum = '';
cookie('actionpropertiesquery', !(isset($_REQUEST['isqueryform']) && $_REQUEST['isqueryform']), $action, $dum, $dum, $dum, $dum, $dum, $dum );

// Re-validate data retrieved from cookie! (XSS attacks)
//if( !array_key_exists($action, $actMap) ) { $action = ''; }
//if( !array_key_exists($objType, $objMap) ) { $objType = ''; }

$app = new ActionPropertiesQueryAdminApp( $publ, $objType, $action, $sysProps );

$app->processRequestData();

$txt = $app->loadHtmlTemplate();

// Upper part - build selection of combo box for Brand, Object Type and Action
$txt = $app->buildSelectionComboBoxes( $actMap, $txt );

// Middle part - build Brand-Object Type-Action link list
$txt = $app->createBrandObjectTypeActionLinks( $objMap, $actMap, $txt );

// Lower part, build current action properties list
$txt = $app->buildCurrentActionProperties( $txt );

// generate total page
print HtmlDocument::buildDocument( $txt );

/**
 * Helper class for the admin application: Dialog Setup Admin Page
 */
class ActionPropertiesQueryAdminApp
{
	private $publ = null;
	private $objType = null;
	private $action = null;
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
	public function buildSelectionComboBoxes( $actMap, $txt )
	{
		$combo = $this->buildActionComboBox( $actMap );
		$txt = str_replace('<!--COMBO:ACTION-->', $combo, $txt );
		return $txt;
	}

	/**
	 * Build action combo box
	 *
	 * @param array $actMap Array of action type
	 * @return string $combo Action HTML combo element text
	 */
	private function buildActionComboBox( $actMap )
	{
		$combo = inputvar( 'isqueryform', '1', 'hidden' );
		$combo .= '<select name="action" onchange="submit();">';
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
		// Validate data retrieved from form (XSS attacks)
		if( in_array($prop, $this->sysProps) ) {
			$edit = '';
		}
		$edit = $edit ? 'on' : '';
		$mandatory = $mandatory ? 'on' : '';
		$restricted = $restricted ? 'on' : '';
		if( $prop ) {
			$values = array('publication' => $this->publ, 'action' => $this->action, 'type' => $this->objType, 'orderid' => $order, 'property' => $prop,
				'edit' => $edit, 'mandatory' => $mandatory, 'restricted' => $restricted );
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
	 * Composes and creates Query default property usages and returns the list of property usages created.
	 *
	 * @since 10.5.0
	 * @return array List of usages or empty list if the insertion of the property usages into database fails.
	 */
	private function composeAndInsertActionsProperty():array
	{
		$addDefaultDynamicFields = ( isset( $_REQUEST['addDefaultDynamic'] ) && strval( $_REQUEST['addDefaultDynamic'] ) == 'true' ) ? true : false;
		if( $addDefaultDynamicFields == 'true' ) {
			$usages = BizProperty::defaultPropertyUsageWhenNoUsagesAvailable( $this->action, false );
		} else {
			$usages = BizProperty::defaultPropertyUsageWhenNoUsagesAvailable( $this->action, true );
		}
		$order = 5;
		$listOfValues = array();
		if( $usages ) foreach( $usages as $usage ) {
			$values = array();
			$values = array(
				$this->publ,
				$this->action,
				$this->objType,
				$order,
				$usage->Name,
				$usage->Editable ? 'on' : '',
				$usage->Mandatory ? 'on' : '',
				$usage->Restricted ? 'on' : '',
				$usage->MultipleObjects ? 'on' : ''
			);
			$listOfValues[] = $values;
			$order += 5;
		}
		$fields = array( 'publication', 'action', 'type', 'orderid', 'property', 'edit', 'mandatory', 'restricted', 'multipleobjects' );
		require_once BASEDIR . '/server/dbclasses/DBActionproperty.class.php';
		$result = DBActionproperty::insertActionsProperty( $fields, $listOfValues );
		return $result ? $usages : array();
	}

	/**
	 * Loop through all the current action properties, and perform update action.
	 *
	 * @param integer $numberOfRecords Number of records count
	 */
	private function updateActionProperties( $numberOfRecords )
	{
		for( $i=0; $i < $numberOfRecords; $i++ ) {
			$id = intval($_REQUEST["id$i"]);        // Record id. Used in POST and GET requests.
			$order = intval($_REQUEST["order$i"]);  // Sorting order field. Zero when not filled.
			$prop = $_REQUEST["prop$i"];            // Name of action property. Always set.
			$edit = isset($_REQUEST["edit$i"]) ? $_REQUEST["edit$i"] : '';
			$mandatory = isset($_REQUEST["mandatory$i"]) ? $_REQUEST["mandatory$i"] : '';
			$restricted = isset($_REQUEST["restricted$i"]) ? $_REQUEST["restricted$i"] : '';
			// Validate data retrieved from form (XSS attacks)
			if( in_array($prop, $this->sysProps) ) {
				$edit = '';
			}
			$edit = $edit ? 'on' : '';
			$mandatory = $mandatory ? 'on' : '';
			$restricted = $restricted ? 'on' : '';
			$values = array('publication' => $this->publ, 'orderid' => $order, 'property' => $prop, 'edit' => $edit,
				'mandatory' => $mandatory,	'restricted' => $restricted );
			DBActionproperty::updateActionproperty( $id, $values );
		}
	}

	/**
	 * Delete action property(ies) selected on the Form.
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
		$rows = DBActionproperty::listQueryActionPropertyGroups();
		// Show results in a list of hyperlinks to select the Brand/Type/Act combos when user clicks on them...
		$brandTypeActionlist = "";

		if( $rows ) foreach( $rows as $row ) {
			// Skip SetPublishProperties action for PublishFormTemplates, they should never be editable from the action properties page.
			if (isset($row['action']) && trim($row['action']) == 'SetPublishProperties' && trim($row['type']) == 'PublishFormTemplate') {
				continue;
			}
			$disp_act = trim($row['action']);
			if( $disp_act ) {
				$disp_act = $actMap[$disp_act];
			} else {
				$disp_act = '<'.$this->sAll.'>'; // set to "<all>" when not defined
			}
			$brandTypeActionlist .= '<a href="javascript:SetCombos(\''.formvar(trim($row['action'])).'\');">';
			$brandTypeActionlist .= formvar($disp_act).'</a><br/>';
		}
		$txt = str_replace("<!--PUB_TYPE_ACTION_LIST-->", $brandTypeActionlist, $txt );
		return $txt;
	}

	/**
	 * Generate HTML text for the current action properties table list
	 * If exact brand is found from 1st left join query result, only get the custom property display name from the 2nd left join.
	 * If exact brand is not found, then continue to get custom displayname from the 2nd left join query result.
	 *
	 * @param array $locals Array of property infos
	 * @param array $rows Array of action properties database records
	 * @param string $detailTxt HTML strings
	 * @param int $numberOfRecords [In/Out] Total number of action properties listed.
	 * @return string $detailTxt HTML strings of the table list
	 */
	private function listCurrentActionProperties( $locals, $rows, $detailTxt, &$numberOfRecords )
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
			$deleteCheckbox = '';
			if( $isConfigurable ) {
				$deleteCheckbox = inputvar( "multiDelete$i", '', 'checkbox', null, true, null, !$isConfigurable );
//				$deleteCheckbox = $this->placeCheckboxInAToolTipWrapper( $deleteCheckbox, BizResources::localize("MNU_DIALOG_SELECT_DELETE") );
				$deleteCheckbox = $this->placeCheckboxInAToolTipWrapper( $deleteCheckbox, 'Select to permanently remove this row' );
			}
			$detailTxt .= "<tr$clr>";
			$detailTxt .= '<td align="center" width="5">' . $deleteCheckbox . '</td>';
			$detailTxt .= "<td>".inputvar("order$i", $row['orderid'], 'small').'</td>';
			$detailTxt .= '<td>'.formvar($prop).inputvar("prop$i",$row['property'],'hidden').'</td>';
			$detailTxt .= '</tr>';
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
	 * @param array $props Array of properties
	 * @param array $rows Array of action properties database records
	 * @param string $detailTxt HTML strings
	 * @return string $detailTxt HTML strings of the table list
	 */
	private function listNewAndCurrentActionProperties( $props, $rows, $detailTxt )
	{
		$highestOrderId = $rows ? max( array_column( $rows, 'orderid' )) + 5 : 5;
		$detailTxt .= '<tr>';
		$detailTxt .= '<td>'.inputvar( 'order', $highestOrderId, 'small', null, true, null, false, 'order' ).'</td>';
		$detailTxt .= '<td>'.inputvar('prop', '', 'combo', $props, false).'</td>';
		$detailTxt .= '</tr>';
		$detailTxt .= inputvar( 'insert', '1', 'hidden' );
		// show other states as info
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		$exactBrandFound = $this->isExactBrandFound( $rows );
		if ( $rows ) foreach( $rows as $row ) {
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
			$detailTxt .= "<tr$clr><td>".$row['orderid'].'</td><td>'.formvar($prop).'</td>';
		}
		return $detailTxt;
	}

	/**
	 * Draw the Form to either show or hide Edit, Update and Delete buttons depending on the action ( $this->mode ).
	 *
	 * @since 10.5.0
	 * @param string $txt
	 * @param int $numberOfRecords
	 * @param null|bool $onlyConfigurableProperties
	 * @return string
	 */
	private function showOrHideButtons( string $txt, int $numberOfRecords, ?bool $onlyConfigurableProperties ): string
	{
		switch( $this->mode ) {
			case "view":
			case "delete":
			case "reset":
				$txt = str_replace("<!--ADD_BUTTON-->",  '', $txt );
				$txt = str_replace("<!--UPDATE_BUTTON-->",( $numberOfRecords == 0 ) ? 'display:none' : '', $txt );
				$txt = str_replace("<!--DELETE_BUTTON-->",( $numberOfRecords == 0 || $onlyConfigurableProperties ) ? 'display:none' : '', $txt );
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
				$txt = str_replace("<!--RESET_BUTTON-->",( $numberOfRecords == 0 ) ? 'display:none' : '', $txt );
				break;
		}
		return $txt;
	}

	/**
	 * Load different Html Template
	 *
	 * @return string $txt HTML strings
	 */
	public function loadHtmlTemplate()
	{
		return HtmlDocument::loadTemplate( 'actionpropertiesquery.htm' );
	}

	/**
	 * Build the lower part of the admin page, current action properties
	 *
	 * @param string $txt HTML strings
	 * @return string $txt HTML Strings
	 */
	public function buildCurrentActionProperties( $txt )
	{
		require_once BASEDIR .'/server/bizclasses/BizQueryBase.class.php';
		$staticProps   = BizProperty::getStaticPropIds();
		$dynamicProps  = BizProperty::getDynamicPropIds();
		$xmpProps      = BizProperty::getXmpPropIds();
		$wfProps       = BizProperty::getWorkflowPropIds();
		$readonlyProps = BizProperty::getSpecialQueryPropIds();

		$already = array();
		$usages = $this->preparePropertyUsages();
		if( $usages ) foreach( $usages as $usage ) {
			$already[] = $usage->Name;
		}

		switch( $this->action ) {
			case 'Query': // 'Query Parameters'
				$allProps = array_merge( $dynamicProps, $xmpProps, $readonlyProps);
				break;
			case 'QueryOut': // 'Query Result Columns'
			case 'QueryOutInDesign':
			case 'QueryOutInCopy':
			case 'QueryOutContentStation':
			case 'QueryOutPlanning':
				$allProps = array_merge($staticProps, $dynamicProps, $xmpProps, $readonlyProps);
				$already = array_merge( $already, BizQueryBase::getMandatoryQueryResultColumnFields() );
				$already[] = 'Issue'; // BZ#27830 In the query result only 'Issues' are of interest.
				break;
			default:
				$allProps = array_merge($dynamicProps, $xmpProps);
				$already = array_merge( $already, BizQueryBase::getMandatoryQueryResultColumnFields() );
				break;
		}

		// get customfields
		$cust = array();
		$trans = array();
		$propObjs = BizProperty::getProperties( $this->publ, $this->objType, null, null, false, false, $this->isPropertySupportedOnlyAtAllObjectTypeLevel() );

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
		$onlyConfigurableProperties = null;
		$rows = DBActionproperty::listActionPropertyWithNames( $this->publ, $this->objType, $this->action, $this->isPropertySupportedOnlyAtAllObjectTypeLevel() );
		switch( $this->mode ) {
			case 'view':
			case 'update':
			case 'delete':
			case 'reset':
				$numberOfRecords = 0;
				$detailTxt = $this->listCurrentActionProperties( $locals, $rows, $detailTxt, $numberOfRecords );
			$onlyConfigurableProperties = $this->isListOfConfigurablePropertiesOnly( $rows );
				break;
			case 'add':
				$numberOfRecords = count( $rows );
				$detailTxt = $this->listNewAndCurrentActionProperties( $props, $rows, $detailTxt );
				break;
		}

		$txt = $this->showOrHideButtons( $txt, $numberOfRecords, $onlyConfigurableProperties );
		$txt = str_replace("<!--DELETE_COLUMN-->", ( $this->mode == 'add' ) ? 'display:none' : (( $numberOfRecords > 0 ) ? '' : 'display:none'), $txt );
		$txt = str_replace("<!--ROWS-->", $detailTxt, $txt);
		$txt = str_replace("<!--DIALOG_CONFIRM_MESSAGE-->", BizResources::localize( 'QUERY_SETUP_CONFIRM_MESSAGE', true, array( "<br/>" )), $txt);
		return $txt;
	}

	/**
	 * Function tells whether the provided list of properties consist of configurable properties only.
	 *
	 * @since 10.5.0
	 * @param string $propertyRows
	 * @return bool
	 */
	private function isListOfConfigurablePropertiesOnly( array $propertyRows ): bool
	{
		$isOnlyConfigurableProperties = true;
		if( $propertyRows ) foreach( $propertyRows as $row ) {
			if( $this->isConfigurableField( $row['property'] )) {
				$isOnlyConfigurableProperties = false;
				break;
			}
		}
		return $isOnlyConfigurableProperties;
	}

	/**
	 * Process request data, follow by insert, update or delete action on the action property.
	 */
	public function processRequestData()
	{
		if( isset( $_REQUEST['updateProperties'] ) && $_REQUEST['updateProperties'] == 'update' ) {
			$this->mode = 'update';
			$numberOfRecords = isset( $_REQUEST['recs'] ) ? intval( $_REQUEST['recs'] ) : 0;
			$insert = isset( $_REQUEST['insert'] ) ? (bool)$_REQUEST['insert'] : false;
		} else if( isset( $_REQUEST['deleteProperties'] ) && $_REQUEST['deleteProperties'] == 'delete' ) {
			$this->mode = 'delete';
			$numberOfRecords = isset( $_REQUEST['recs'] ) ? intval( $_REQUEST['recs'] ) : 0;
			$insert = false;
		} else if( isset( $_REQUEST['resetProperties'] ) && $_REQUEST['resetProperties'] == 'reset' ) {
			$this->mode = 'reset';
			$numberOfRecords = isset( $_REQUEST['recs'] ) ? intval( $_REQUEST['recs'] ) : 0;
			$insert = false;
		} else if( isset( $_REQUEST['addProperties'] ) && $_REQUEST['addProperties'] == 'add' ) {
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
	 * To determine if the property is supported only at object type level 'ALL' or also at '<specific>' object type level.
	 *
	 * The properties ( whether they are static, dynamic or custom ), can be adjusted or defined at 'ALL' object type level
	 * or '<specific>' object type level in the MetaData page.
	 * Depending on the Query action ( 'Query Parameters', 'Query Result Columns' and etc ), some action(s) can only use
	 * the properties defined at 'ALL' object type level and '<specific>' object type level; while some can only use the
	 * properties that are defined at 'ALL' object type level.
	 *
	 * The list of which actions can use which type of properties are listed below.
	 * 1) Actions that can use propertes defined at 'ALL' and '<specific> object type level:
	 * 'Query Result Columns'
	 * 'Query Result Columns for Content Station'
	 * 'Query Result Columns for InCopy'
	 * 'Query Result Columns for InDesign'
	 * 'Query Result Columns for Planning'
	 * Possible example properties(<Brand>_<Type>_<Property>): All_All_RouteTo, All_Image_RouteTo, All_Article_CustomProp and etc.
	 *
	 * 2) Actions that can use properties that are defined only at 'ALL' object type level:
	 * 'Query Parameters'
	 * Possible example properties(<Brand>_<Type>_<Property>): All_All_RouteTo, All_All_CustomProp and etc.
	 *
	 * @since 10.5.0
	 * @return bool
	 */
	private function isPropertySupportedOnlyAtAllObjectTypeLevel()
	{
		switch( $this->action ) {
			case 'QueryOut': // 'Query Result Columns'
			case 'QueryOutInDesign':
			case 'QueryOutInCopy':
			case 'QueryOutContentStation':
			case 'QueryOutPlanning':
				$propertyObjectTypeApplicableToAction = false;
			break;
			case 'Query': // 'Query Parameters'
				$propertyObjectTypeApplicableToAction = true;
				break;
		}
		return $propertyObjectTypeApplicableToAction;
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
	 * @since 10.5.0
	 * @param string $prop
	 * @return bool Returns true when the property can be configured from the UI, false otherwise.
	 */
	private function isConfigurableField( $prop )
	{
		require_once BASEDIR .'/server/bizclasses/BizQueryBase.class.php';
		$editable = true;
		switch( $this->action ) {
			case 'Query':
				switch( $prop ) {
					case 'Name':
						$editable = false;
						break;
				}
				break;
			case 'QueryOut':
			case 'QueryOutInDesign':
			case 'QueryOutInCopy':
			case 'QueryOutContentStation':
			case 'QueryOutPlanning':
				$nonEditableFields = array_merge( BizQueryBase::getMandatoryQueryResultColumnFields(), array( 'Issue' ) );
				if( in_array( $prop, $nonEditableFields )) {
					$editable = false;
				}
				break;
			default:
				$nonEditableFields = BizQueryBase::getMandatoryQueryResultColumnFields();
					$editable = false;
				break;
		}
		return $editable;
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
	 * @since 10.5.0
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
	 * Returns list of property usages retrieved from the database.
	 *
	 * When there's no property usages found in the database and if it is first time
	 * configuration, user will be prompted if default dynamic properties should be
	 * added in advance. If user chooses 'Yes', function will pre-insert all the default
	 * dynamic properties into database and returns this set of default dynamic properties usages.
	 *
	 * @since 10.5.0
	 * @return array List of usages or list can be empty if insertion properties into database has taken place but failed.
	 */
	private function preparePropertyUsages():array
	{
		$wiwiwUsages = null;
		$usages = BizProperty::getPropertyUsages( $this->publ, $this->objType, $this->action,
			false,  // BZ#6516: Do not return default/static properties here
			true,  // BZ#14553: Request NOT to fall back at global definition levels. Specified level only.
			null,
			$wiwiwUsages, // $wiwiwUsages = null when it is not for Template and PublishForm.
			false );

		if( !$usages ) {
			if( $this->mode == 'add' ) {
				$usages = $this->composeAndInsertActionsProperty();
			}
		}
		return $usages;
	}
}