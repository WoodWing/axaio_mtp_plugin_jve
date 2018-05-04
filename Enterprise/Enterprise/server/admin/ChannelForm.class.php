<?php

require_once BASEDIR . '/server/utils/htmlclasses/HtmlAnyForm.class.php';

class ChannelForm extends HtmlAnyForm
{
	private $publicationId = 0;
	private $publicationObj = null; // Brand of the PubChannel.
	private $pubChannelId = 0;
	private $pubChannelObj = null; // PubChannel being edit by admin user.

	private $IssuesTree = null;
	private $EditionsTree = null;
	
	private $CreateChannelAction = null;
	private $UpdateChannelAction = null;

	private $AddIssueAction = null;
	private $EditIssueAction = null;
	private $DeleteIssueAction = null;
	private $ReorderIssuesAction = null;

	private $AddEditionAction = null;
	private $EditEditionAction = null;
	private $DeleteEditionAction = null;
	private $ReorderEditionsAction = null;

	public function __construct($owner, $name)
	{
		HtmlAnyForm::__construct($owner, $name);
		$this->pubChannelId = isset($_REQUEST['channelid']) ? intval($_REQUEST['channelid']) : 0; // not set for new channels
		$this->publicationId = intval($_REQUEST['publid']);
	}
	
	public function createFields()
	{
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlTree.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlCombo.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlDateTimeField.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlDiffTimeField.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlAction.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlIconField.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlStringField.class.php';
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';

		// Resolve brand and channel.
		$this->publicationObj = $this->getPublicationObj();
		$this->pubChannelObj = $this->buildPubChannelObj( $this->publicationObj, $this->pubChannelId );

		$this->IssuesTree = new HtmlTree($this, 'Issues', false);
		$issuecodefield = new HtmlStringField($this, 'issuecodefield', false, false, false, 0, null, false, 40);
		$this->IssuesTree->addCol(40, $issuecodefield, 'code', 'code', BizResources::localize('LIS_ORDER'));
		$this->IssuesTree->addNameCol(200, BizResources::localize('ISSUE'));
		
		$publicationdatefield = new HtmlDateTimeField($this, 'publicationdate');
		$this->IssuesTree->addCol(200, $publicationdatefield, 'publicationdate', 'publdate', BizResources::localize('PUBLICATION_DATE'), true);
		
		$deadlinefield = new HtmlDateTimeField($this, 'issuedeadline');
		$this->IssuesTree->addCol(200, $deadlinefield, 'issuedeadline', 'deadline', BizResources::localize('ISS_DEADLINE'), true);

		$issuedescriptionfield = new HtmlStringField($this, 'issuedescriptionfield');
		$this->IssuesTree->addCol(200, $issuedescriptionfield, 'editiondescription', 'description', BizResources::localize('OBJ_DESCRIPTION'), true);
		$this->EditIssueAction = new HtmlAction($this, 'editissueaction', BizResources::localize('ACT_EDIT'), null, true);
		$col = $this->IssuesTree->addActionCol(200, '', $this->EditIssueAction);
		$this->DeleteIssueAction = new HtmlAction($this, 'deleteissueaction', BizResources::localize('ACT_DELETE'), null, true, 
										'return confirm(\''.BizResources::localize('ACT_SURE_DELETE_ISSUE').'\');');
		$col->Actions[] = $this->DeleteIssueAction;

		$this->EditionsTree = new HtmlTree($this, 'Editions', false);
		$editioncodefield = new HtmlStringField($this, 'editioncodefield', false, false, false, 0, null, false, 40);
		$this->EditionsTree->addCol(40, $editioncodefield, 'code', 'code', BizResources::localize('LIS_ORDER'));
		$this->EditionsTree->addNameCol(200, BizResources::localize('EDITION'));
		$editiondescriptionfield = new HtmlStringField($this, 'editiondescriptionfield');
		$this->EditionsTree->addCol(200, $editiondescriptionfield, 'editiondescription', 'description', BizResources::localize('OBJ_DESCRIPTION'), true);
		$this->EditEditionAction = new HtmlAction($this, 'editeditionaction', BizResources::localize('ACT_EDIT'), null, true);
		$col = $this->EditionsTree->addActionCol(200, '', $this->EditEditionAction);
		$this->DeleteEditionAction = new HtmlAction($this, 'deleteeditionaction', BizResources::localize('ACT_DELETE'), null, true,
										'return confirm(\''.BizResources::localize('ACT_SURE_DELETE_EDITION').'\');');
		$col->Actions[] = $this->DeleteEditionAction;

		$this->CreateChannelAction = new HtmlAction($this, 'createchannelaction', BizResources::localize('ACT_CREATE'), null, true);
		$this->UpdateChannelAction = new HtmlAction($this, 'updatechannelaction', BizResources::localize('ACT_UPDATE'), null, true);
		$this->AddIssueAction = new HtmlAction($this, 'addissueaction', BizResources::localize('ACT_ADD') . ' ' . BizResources::localize('ISSUE'), null, true);
		$this->ReorderIssuesAction = new HtmlAction($this, 'reorderissuesaction', BizResources::localize('ACT_REORDER'), null, true);
		$this->AddEditionAction = new HtmlAction($this, 'addeditionaction', BizResources::localize('ACT_ADD') . ' ' . BizResources::localize('EDITION'), null, true);
		$this->ReorderEditionsAction = new HtmlAction($this, 'reordereditionsaction', BizResources::localize('ACT_REORDER'), null, true);

	}

	private function buildPubChannelForm( $validateErrors )
	{
		// Determine the user action.
		$action = ($this->pubChannelId) ? 'Update' : 'Create';
		
		// Build a list of properties (DialogWidget objects) and put them in the order how to show to end-user.
		// Server Plug-ins are requested to add their custom properties as well and are able to reorganize props.
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php'; // AdminProperties_Context
		$entity = 'PubChannel';
		$context = new AdminProperties_Context();
		$context->setPublicationContext( $this->publicationObj, $this->pubChannelObj, null, null, null );
		$hideWidgets = array();
		$showWidgets = BizAdmProperty::buildDialogWidgets( $context, $entity, $action, $hideWidgets );
		
		// Collect the property values to fill in at the publication form fields.
		$mdValues = BizAdmProperty::getMetaDataValues( $this->pubChannelObj );
		
		// Draw all form fields (in memory as HTML string) which includes the form values, representing the publication form.
		$prefix = 'PubChannel_'; // prefix for form property names
		$propsHtml = '';
		require_once BASEDIR.'/server/utils/htmlclasses/XHtmlDocument.class.php';
		$doc = new Utils_XHtmlDocument();
		$form = $doc->addForm( 'myform', 'myform' );

		foreach( $showWidgets as $widget ) {
			$this->fillCombos( $action, $widget );
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
			$propsHtml .= $this->drawHtmlField( $this->pubChannelObj, $widget, $prop, $validateError );
		}

		// Collect hidden fields too, to add to form at hidden section. This way data can round-trip
		// which is typically needed for booleans; Those do not get posted when untagged. So, men can not
		// tell difference between unpresent checkbox or untagged checkbox.
		$hidePropsHtml = '';
		foreach( $hideWidgets as $widget ) {
			$this->fillCombos( $action, $widget );
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
			$hidePropsHtml .= $this->drawHtmlField( $this->pubChannelObj, $widget, $prop, '' );
		}

		$txt =
			'<table class="appframe"><tr class="text"><td>'.
				'<table class="formbody">'.$propsHtml.'</table>'.
				'<i>* <!--RES:OBJ_MANDATORY--></i>'.
				'<div style="position:absolute; left:550px; top:150px; visibility:hidden;">'.
					'<table class="formbody">'.$hidePropsHtml.'</table>'.
				'</div>'.
			'</td></tr></table>';

		// NOTE: For Brand/Issue Maintenance pages we work with HTML templates, but for 
		// historical reasons, not for PubChannels. In case we want to support HTML template 
		// for PubChannels, we should replace the code fragment above with the below.
		
		// Load HTML template and insert the publication form.
		//$txt = HtmlDocument::loadTemplate( 'hppubchannel.htm' );
		//$txt = str_replace( '<!--VAR:PUBCHANNEL_PROPERTIES-->', $propsHtml, $txt );
		//$txt = str_replace( '<!--VAR:PUBCHANNEL_HIDDEN_PROPERTIES-->', $hidePropsHtml, $txt );
		//$txt = str_replace( '<!--VAR:HIDDEN-->', inputvar( 'id', $this->pubChannelId, 'hidden' ), $txt );
		
		return $txt;
	}
	
	/**
	 * Lists options for the comboboxes drawn for the pub channel.
	 * This is done for CurrentIssueId, PublishSystem and Type.
	 *
	 * @param string $action
	 * $param DialogWidget[] $widgets The $widget->PropertyInfo->PropertyValues to update.
	 */
	private function fillCombos( $action, $widget )
	{
		switch( $widget->PropertyInfo->Name ) {
			case 'CurrentIssueId':
				$widget->PropertyInfo->PropertyValues = array();
				$propValue = new PropertyValue();
				$propValue->Value = 0;
				$propValue->Display = BizResources::localize('LBL_NONE');
				$widget->PropertyInfo->PropertyValues[] = $propValue;
				if( $this->pubChannelId ) { // Update only
					require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
					$issues = DBIssue::listChannelIssues( $this->pubChannelId );
					foreach( $issues as $issueId => $issue ) {
						$propValue = new PropertyValue();
						$propValue->Value = $issueId;
						$propValue->Display = $issue['name'];
						$widget->PropertyInfo->PropertyValues[] = $propValue;
					}
				}
				break;
			case 'PublishSystem':
				$widget->PropertyInfo->PropertyValues = array();
				$publishSystems = $this->getPublishSystems();
				if( $publishSystems ) foreach( $publishSystems as $systemKey => $systemDisplay ) {
					$propValue = new PropertyValue();
					$propValue->Value = $systemKey;
					$propValue->Display = $systemDisplay;
					$widget->PropertyInfo->PropertyValues[] = $propValue;
				}
				break;
			case 'SuggestionProvider':
				$widget->PropertyInfo->PropertyValues = array();
				$suggestionProviders = $this->getSuggestionProvider();
				if( $suggestionProviders ) foreach( $suggestionProviders as $systemKey => $systemDisplay ) {
					$propValue = new PropertyValue();
					$propValue->Value = $systemKey;
					$propValue->Display = $systemDisplay;
					$widget->PropertyInfo->PropertyValues[] = $propValue;
				}
				break;
			case 'Type':
				$widget->PropertyInfo->PropertyValues = array();
				require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
				$channelTypes = DBChannel::listChannelTypes();
				if( $channelTypes ) foreach( $channelTypes as $typeDisplay ) {
					$propValue = new PropertyValue();
					$propValue->Value = $typeDisplay;
					$propValue->Display = $typeDisplay;
					$widget->PropertyInfo->PropertyValues[] = $propValue;
				}
				break;
		}
	}

	/**
	 * Creates new AdmPubChannel object and sets all its properties.
	 * When initially loading the form for a new admin object, default properties are taken.
	 * When initially loading the form for an existing admin object, properties are retrieved from DB.
	 * When user posts typed/changed data, properties are retrieved from HTTP params ($_REQUEST).
	 *
	 * @param AdmPublication $pubObj
	 * @param integer $channelId PubChannel ID
	 * @throws BizException On DB error.
	 * @return AdmPubChannel
	 */
	private function buildPubChannelObj( AdmPublication $pubObj, $channelId )
	{
		$channelObj = new AdmPubChannel();
		$channelObj->Id = $channelId;
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$prefix = 'PubChannel_'; // prefix for form property names
		$entity = 'PubChannel';
		$newObject = ($channelId == 0); // TRUE: creating new admin object, FALSE: updating existing admin object
		
		$firstCall = $this->hasError ?
			// When there's error occurred during the creation/update, we re-load the page and ignore all the changes user has made.
			true :
			// TRUE: first time building the form or changed the selection for section mapping, FALSE: user does submit typed changes
			!isset($_REQUEST['PubChannel_Name']);
		
		require_once BASEDIR.'/server/interfaces/plugins/connectors/AdminProperties_EnterpriseConnector.class.php'; // AdminProperties_Context
		$context = new AdminProperties_Context();
		$context->setPublicationContext( $pubObj, $channelObj, null, null, null );

		if( $newObject ) { // user is creating new admin object
			$channelObj->Type = 'print';
			if( $firstCall ) { // loading initial form
				BizAdmProperty::buildDefaultAdmObj( $channelObj, $entity, $prefix, $context );
			} else { // user submit typed/changed data
				BizAdmProperty::buildAdmObjFromHttp( $channelObj, $entity, $prefix, $context );
			}
		} else { // user is updating existing admin object
			if( $firstCall ) { // loading initial form
				$channelObj = $this->getPubChannelObj();
			} else { // user submits typed/changed data
				BizAdmProperty::buildAdmObjFromHttp( $channelObj, $entity, $prefix, $context );
			}
		}
		return $channelObj;
	}

	/**
	 * Streams given Utils_XHtmlField into an HTML string that suites the pub channel form.
	 * It shows the property name and the input widget in two (hidden) table columns.
	 * In case of validation error, the error message is shown at second column.
	 *
	 * IMPORTANT: Please keep this function in sync with hppublications.php and hppublissues.php.
	 *
	 * @param AdmPubChannel $channelObj
	 * @param DialogWidget $widget
	 * @param Utils_XHtmlField $htmlPropObj
	 * @param string $validateError Error message to show when validation for this property has failed.
	 * @param int $width Sets the element width
	 * @return string
	 */
	private function drawHtmlField( AdmPubChannel $channelObj, DialogWidget $widget, Utils_XHtmlField $htmlPropObj, $validateError, $width = 200 )
	{
		$displayName = $widget->PropertyInfo->DisplayName;
		if( $widget->PropertyInfo->Type == 'separator' ) {
			$htmlWidget = '<tr><td colspan="2">&nbsp;</td></tr>'."\r\n";
			$htmlWidget .= '<tr><th colspan="2"><br/>'.formvar($displayName).'</th></tr>'."\r\n";
		} else {
			$htmlPropObj->setWidth( $width );
			$mandatory = $widget->PropertyUsage->Mandatory ? '*' : '';
			$htmlWidget = '<tr><td>'.formvar($displayName).$mandatory.'</td><td>'.$htmlPropObj->toString().
				'<font color="#ff0000"><i>'.$validateError.'</i></font></td></tr>'."\r\n";
		}
		return $htmlWidget;
	}
	
	/**
	 * Retrieves a Publication from the database.
	 *
	 * @throws BizException On DB error.
	 * @return AdmPublication
	 */
	private function getPublicationObj()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$service = new AdmGetPublicationsService();
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $this->Owner->Ticket;
		$request->RequestModes = array();
		$request->PublicationIds = array( $this->publicationId );
		$response = $service->execute( $request );
		return $response->Publications[0];
	}	
	
	/**
	 * Retrieves a Publication Channel from the database.
	 *
	 * @throws BizException On DB error.
	 * @return AdmPubChannel
	 */
	private function getPubChannelObj()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';
		$service = new AdmGetPubChannelsService();
		$request = new AdmGetPubChannelsRequest();
		$request->Ticket = $this->Owner->Ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannelIds = array( $this->pubChannelId );
		$response = $service->execute( $request );
		return $response->PubChannels[0];
	}	

	/**
	 * Creates a Publication Channel in the database.
	 *
	 * @throws BizException On DB error.
	 */
	private function createPubChannelObj()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';
		$service = new AdmCreatePubChannelsService();
		$request = new AdmCreatePubChannelsRequest();
		$request->Ticket = $this->Owner->Ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannels = array( $this->pubChannelObj );
		$response = $service->execute( $request );
		
		$this->pubChannelObj = $response->PubChannels[0];
		$this->pubChannelId = $this->pubChannelObj->Id;
	}	

	/**
	 * Updates a Publication Channel in the database.
	 *
	 * @param array $pubChannels List of AdmPubChannel data objects
	 * @throws BizException On DB error.
	 */
	private function modifyPubChannelObj()
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyPubChannelsService.class.php';
		$service = new AdmModifyPubChannelsService();
		$request = new AdmModifyPubChannelsRequest();
		$request->Ticket = $this->Owner->Ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannels = array( $this->pubChannelObj );
		$response = $service->execute( $request );

		$this->pubChannelObj = $response->PubChannels[0];
	}
	
	/**
	 * Build a list of publish systems the admin user can choose from.
	 *
	 * @return array of publish systems (key = internal name, value = display name)
	 */
	private function getPublishSystems()
	{
		// Build list of Publish System options
		$publishSystems[''] = BizResources::localize('ENTERPRISE');
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connObjs = BizServerPlugin::searchConnectors( 'PubPublishing', '', false, false );
		foreach( $connObjs as $connObj ) {
			$pluginObj = BizServerPlugin::getPluginForConnector( get_class($connObj) );
			if( $pluginObj ) {
				$pubSys = BizServerPlugin::runConnector( $connObj, 'getPublishSystemDisplayName', array() );
				if( empty($pubSys) ) { // only when provided by connector (optional!)
					$pubSys = $pluginObj->DisplayName; // fall back at plug-ins display name
				}
				if( !empty($pubSys) ) {
					$publishSystems[$pluginObj->UniqueName] = $pubSys;
				}
			}
		}
		asort($publishSystems);
		return $publishSystems;
	}

	/**
	 * Returns a list of Suggestion providers where the admin user can choose from.
	 *
	 * @return array List of Suggestion providers. (key = internal name, value = display name)
	 */
	private function getSuggestionProvider()
	{
		// Build list of Suggestion providers options
		$suggestionProviders[''] = BizResources::localize('LBL_NONE');
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connObjs = BizServerPlugin::searchConnectors( 'SuggestionProvider', null, false, true );
		foreach( $connObjs as $connObj ) {
			$pluginObj = BizServerPlugin::getPluginForConnector( get_class($connObj) );
			if( $pluginObj ) {
				$displayName = $pluginObj->DisplayName;
				if( !$pluginObj->IsActive ) {
					$displayName .= ' ('.BizResources::localize( 'PLUGGED_OUT').  ')';
				}
				$suggestionProviders[$pluginObj->UniqueName] = $displayName; // Use plug-ins display name
			}
		}
		asort($suggestionProviders);
		return $suggestionProviders;
	}
	
	public function execAction()
	{
		if (isset($_REQUEST['createchannelaction'])) {
			return $this->execCreateChannel();
		}
		if (isset($_REQUEST['updatechannelaction'])) {
			return $this->execUpdateChannel();
		}
		if (isset($_REQUEST['addissueaction'])) {
			return $this->execAddIssue();
		}
		if (isset($_REQUEST['addeditionaction'])) {
			return $this->execAddEdition();
		}
		if (isset($_REQUEST['reorderissuesaction'])) {
			return $this->execReorderIssues();
		}
		if (isset($_REQUEST['reordereditionsaction'])) {
			return $this->execReorderEditions();
		}
		foreach (array_keys($_REQUEST) as $key) {
			if (strpos($key, 'editissueaction') === 0) {
				return $this->execEditIssue($key);
			}
			if (strpos($key, 'editeditionaction') === 0) {
				return $this->execEditEdition($key);
			}
			if (strpos($key, 'deleteissueaction') === 0) {
				return $this->execDeleteIssue($key);
			}
			if (strpos($key, 'deleteeditionaction') === 0) {
				return $this->execDeleteEdition($key);
			}
		}
		return null;
	}

	private function execUpdateChannel()
	{
		try {
			$this->modifyPubChannelObj();
		} catch( BizException $e ) {
			return $e->getMessage();
		}
		return null;
	}

	private function execCreateChannel()
	{
		try {
			$this->createPubChannelObj();
		} catch( BizException $e ) {
			return $e->getMessage();
		}

		header("Location: editChannel.php?publid=$this->publicationId&channelid={$this->pubChannelId}");
		exit;
	}
			
	private function execAddIssue()
	{
		header("Location: hppublissues.php?publ=$this->publicationId&channelid={$this->pubChannelId}");
		exit;
	}
	
	private function execEditIssue($requestkey)
	{
		$requestarray = explode('~', $requestkey);
		$tag = $requestarray[2];
		header("Location: hppublissues.php?id=$tag");
		exit;                
	}

	private function execAddEdition()
	{
		header("Location: hpeditions.php?publ=$this->publicationId&channelid={$this->pubChannelId}");
		exit;
	}
	
	private function execEditEdition($requestkey)
	{
		$requestarray = explode('~', $requestkey);
		$tag = $requestarray[2];
		header("Location: hpeditions.php?id=$tag&publ=$this->publicationId&channelid={$this->pubChannelId}");
		exit;
	}
	
	private function execDeleteIssue($requestkey)
	{
		$requestarray = explode('~', $requestkey);
		$tag = $requestarray[2];
		header("Location: hppublissues.php?delete=1&del=$tag&id=$tag");
		exit;                
		
	}
	
	private function execDeleteEdition($requestkey)
	{
		$requestarray = explode('~', $requestkey);
		$tag = $requestarray[2];
		header("Location: hpeditions.php?delete=1&id=$tag&publ=$this->publicationId&channelid={$this->pubChannelId}");
		exit;                
		
	}
	
	private function execReorderIssues()
	{
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		$updates = $this->IssuesTree->requestValues();
		if( $updates ) {
			foreach( $updates as $updateid => $update ) {
				$updateidarray = explode('~', $updateid);
				$type = $updateidarray[0];
				if( $type == 'Issue' ) {
					$issueId = $updateidarray[1];
					$fields = array( 'code' => $update['code'] );
					DBIssue::updateIssue( $issueId, $fields );
				}
			}
			$this->sendEventForReorderedIssues( $_REQUEST['publid'], $_REQUEST['channelid'] );
		}
	}

	private function execReorderEditions()
	{
		require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
		$updates = $this->EditionsTree->requestValues();
		foreach( $updates as $updateid => $update ) {
			$updateidarray = explode('~', $updateid);
			$type = $updateidarray[0];
			if( $type == 'Edition' ) {
				$editionid = $updateidarray[1];
				$fields = array( 'code' => $update['code'] );
				DBEdition::updateEditionDef( $editionid, $fields );
			}
		}
	}

	public function fetchData()
	{
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
		if( $this->pubChannelObj->Id > 0 ) {
			$issues = DBIssue::listChannelIssues( $this->pubChannelObj->Id );
			// Sort the issues by code, then by name in natural order.
			if( $issues ) {
				uasort( $issues, function( array $issueA, array $issueB ) {
					if( $issueA['code'] == $issueB['code'] ) {
						return strnatcmp( $issueA['name'], $issueB['name'] );
					}
					return $issueA['code'] < $issueB['code'] ? -1 : 1;
				} );
			}
			$this->fetchIssuesTree( $issues );

			if( $this->pubChannelObj->Type == 'print' ||
				$this->pubChannelObj->Type == 'dps2' ) {
				$editions = DBEdition::listChannelEditions( $this->pubChannelObj->Id );
				// Sort the editions by code, then by name in natural order.
				if( $editions ) {
					uasort( $editions, function( array $editionA, array $editionB ) {
						if( $editionA['code'] == $editionB['code'] ) {
							return strnatcmp( $editionA['name'], $editionB['name'] );
						}
						return $editionA['code'] < $editionB['code'] ? -1 : 1;
					} );
				}
				$this->fetchEditionsTree( $editions );
			}
		}
	}
	
	private function fetchIssuesTree($issues)
	{
		foreach ($issues as $issueid => $issue)
		{
			$this->IssuesTree->beginNode('Issue', $issue['name'], $issue, 'Issue' . '~' . $issueid);
			$this->IssuesTree->endNode();
		}
	}

	private function fetchEditionsTree($editions)
	{
		foreach ($editions as $editionid => $edition)
		{
			$this->EditionsTree->beginNode('Edition', $edition['name'], $edition, 'Edition' . '~' . $editionid);
			$this->EditionsTree->endNode();
		}            
	}

	/**
	 * ﻿Send out a list of reordered issue ids via n-casting and RabbitMQ.
	 *
	 * @since 10.4.1
	 * @param integer $pubId
	 * @param integer $pubChannelId
	 */
	private function sendEventForReorderedIssues( $pubId, $pubChannelId )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR.'/server/smartevent.php';
		$reorderedIssues = DBIssue::listChannelIssues( $pubChannelId );
		$reorderedIssues = array_keys( $reorderedIssues ); // Only ids
		new smartevent_updateissuesorderEx( BizSession::getTicket(), $pubId, $pubChannelId, $reorderedIssues );
	}
	
	public function drawHeader()
	{
		return '';   
	}
	
	public function drawBody()
	{
		require_once BASEDIR.'/server/admin/global_inc.php'; // inputvar(), formvar()
		
		$result = '';
		if (empty($this->pubChannelId) && empty($this->publicationId)) {
			$result = BizResources::localize('ERR_NOTFOUND') . ": " . BizResources::localize('CHANNEL') . ' ' . $this->pubChannelId;
			return $result;
		}

		$result .= '
			<script language="javascript" src="../../server/utils/javascript/HtmlTree.js"></script>
			<script language="javascript"><!--INC:DatePicker.js--></script>
			<form id="'.$this->Name.'" type="submit" method="post">
			<table class="apptitlebar">
				<tr>
					<td><img class="apptitleicon" src="../../config/images/channel_32.gif"/></td>
					<td>&nbsp;<span class="apptitletext"><!--RES:CHANNEL_MAINTENANCE--></span></td>
				</tr>
			</table>';
					

		$validateErrors = array();
		$result .= $this->buildPubChannelForm( $validateErrors );
		
		if (empty($this->pubChannelId)) {
			$result .= $this->CreateChannelAction->drawBody();
			$result .= '</form>';
			$result .= '</body>';
			return $result;                
		}

		//channel exists

		$result .= $this->UpdateChannelAction->drawBody();

		if (!empty($this->pubChannelId) && 
				($this->pubChannelObj->Type == 'print' ||
				$this->pubChannelObj->Type == 'dps2' )) {
			$result .= '
				<table class="subtitlebar">
					<tr>
						<td><img class="subtitleicon" src="../../config/images/edition_small.gif"/></td>
						<td>&nbsp;<span class="subtitletext"><!--RES:EDITIONS--></span></td>
					</tr>
				</table>';
			$result .= $this->EditionsTree->drawBody();
			$result .= $this->AddEditionAction->drawBody();
			$result .= $this->ReorderEditionsAction->drawBody();
		}
		
		$result .= '
			<table class="subtitlebar">
				<tr>
					<td><img class="subtitleicon" src="../../config/images/issue_small.gif"/></td>
					<td>&nbsp;<span class="subtitletext"><!--RES:ISSUES--></span></td>
				</tr>
			</table>';
		$result .= $this->IssuesTree->drawBody();
		$result .= $this->AddIssueAction->drawBody();
		$result .= $this->ReorderIssuesAction->drawBody();
		$result .= '</form>';

		$actionname = BizResources::localize('ACT_BACK');
		$result .= '<p><a href="hppublications.php?id=' . $this->publicationId. '">'.
					'<img src="../../config/images/back_32.gif" border="0" title="'.$actionname.'" width="32" height="32"/></a></p>';

		$result .= "</body>";
		return $result;
	}
}
