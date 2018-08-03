<?php
/**
 * GetDialog workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialogRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialogResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetDialogService extends EnterpriseService
{
	private $req1; // WflGetDialogRequest (v1)
	private $resp1; // WflGetDialogResponse (v1)
	
	public function execute( WflGetDialogRequest $req )
	{
		// Here, call the runBefore of the server plug-ins, followed by the core (which is 'this' class,
		// called back through runCallback), followed by the runAfter of the server plug-ins.
		// Expected is to have v2 response returned.
		$resp = $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetDialog2', // let core call the WflGetDialog2 connectors (since there is no WflGetDialog connector interface)
			true,  		// check ticket
			false   	// no transaction, it's a get function
			);
		return $resp;
	}

	/**
	 * @param mixed $req Changes from WflGetDialogRequest (v1) to WflGetDialog2Request (v2)
	 */
	protected function restructureRequest( &$req )
	{
		// At this point, client has called us with v1 structure, which needs to be transformed to v2.
		// Reason is that server plug-ins, like the core itself, only want to know about v2 (since v1 is obsoleted).
		$this->req1 = $req;
		$req = self::buildIncomingDialog( $req );
	}

	/**
	 * @param mixed $req Changes from WflGetDialog2Response (v2) to WflGetDialogResponse (v1)
	 * {@inheritdoc}
	 */
	protected function restructureResponse( $req, &$resp )
	{
		// At this point, make waiting clients happy, by returning v1 response.
		self::buildOutgoingDialog( $this->resp1 );
		$resp = $this->resp1;
	}
	
	private static function buildIncomingDialog( WflGetDialogRequest $req1 )
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialog2Request.class.php';
		$req2 = new WflGetDialog2Request();
		
		require_once BASEDIR.'/server/utils/PHPClass.class.php';
		WW_Utils_PHPClass::copyObjProps( $req1, $req2 );
		
		// Map to the new getDialog2 metadata structure
		$metaData = array();
		                                           // Property,    Values, PropertyValues
		$metaData['ID']          = new MetaDataValue('ID',          null, array( new PropertyValue( $req1->ID ))); 
		$metaData['Publication'] = new MetaDataValue('Publication', null, array( new PropertyValue( $req1->Publication )));
		$metaData['Issue']       = new MetaDataValue('Issue',       null, array( new PropertyValue( $req1->Issue )));
		$metaData['Category']    = new MetaDataValue('Category',    null, array( new PropertyValue( $req1->Section )));
		$metaData['State']       = new MetaDataValue('State',       null, array( new PropertyValue( $req1->State )));
		$metaData['Type']        = new MetaDataValue('Type',        null, array( new PropertyValue( $req1->Type )));

		$req2->MetaData = $metaData;
		return $req2;
	}

	private static function buildOutgoingDialog( WflGetDialogResponse $resp1 )
	{
		// Set PropertyValues to null, so that the GetDialogResponse won't contain the v8 structure.
		if( $resp1->Dialog ) {
			// v7 ContentStation(CS) takes 'Category' instead of 'Section', 'Section' will cause properties not shown in CS Properties Pane, so unset it.
			unset( $resp1->Dialog->MetaData['Section']);
			
			// v7 CS cannot have ID in the MetaData list, it will cause error in the 'Create' dialog as 
			// CS tries to send empty object ID to server(server will throw error if client sends object ID during Create action).
			unset( $resp1->Dialog->MetaData['ID']);
			
			foreach( $resp1->Dialog->MetaData as $metaDataValue ) {
				if( !is_null($metaDataValue->PropertyValues) ) {
					if( $metaDataValue->Property == 'RouteTo' ){
						foreach( $metaDataValue->PropertyValues as $propertyValue ) {
							$metaDataValue->Values[] = $propertyValue->Display;
						}
					}else{
						foreach( $metaDataValue->PropertyValues as $propertyValue ) {
							if( strlen( $propertyValue->Value ) > 0 ) {
								$metaDataValue->Values[] = $propertyValue->Value;
							}
						}
					}	
				}
				$metaDataValue->PropertyValues = null;
			}
			
			// Note that dossier are returned through the 'Dossier' dialog widget to serve GetDialog2.
			// But for GetDialog(1), the dossiers are already returned through $resp1->Dossiers.
			// To avoid returning duplicate data, here we remove the dossiers from the widget. 
			if( $resp1->Dialog->Tabs ) foreach( $resp1->Dialog->Tabs as $tab ) {
				if( $tab->Widgets ) foreach( $tab->Widgets as $widget ) {
					if( $widget->PropertyInfo->Name == 'Dossier' ) {
						$widget->PropertyInfo->PropertyValues = null;
						break 2; // exit both foreach loops at once
					}
				}
			}
		}
	}
	
	public function runCallback( WflGetDialog2Request $req2 )
	{
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		$retVal = BizWorkflow::getDialog( 
			$this->User,
			$req2->Action,
			$req2->MetaData,
			$req2->Targets,
			$this->req1->RequestDialog,
			$this->req1->RequestPublication,
			$this->req1->RequestMetaData,
			$this->req1->RequestStates,
			$this->req1->RequestTargets,
			$req2->DefaultDossier,
			$req2->Parent,
			$req2->Template,
			$req2->Areas,
			false ); // MultipleObjects
		
		$this->resp1 = new WflGetDialogResponse( 
			$retVal['Dialog'],
			$retVal['Publications'],
			$retVal['PublicationInfo'],
			$retVal['MetaData'],
			$retVal['GetStatesResponse'],
			$retVal['Targets'],
			$retVal['RelatedTargets'],
			$retVal['Dossiers']
			);

		// The runAfter of the server plug-ins is called with the returned value from here, 
		// so there is a need to return v2 structure.
		// Note that the buildOutgoingDialog calls copyObjProps, which does -not- do a deep copy,
		// and so all attributes are referred from v1 to v2, which allows server plug-ins to
		// adjust the v2 response, if they need, which gets reflected into v1 response through references.
		// As the 'Dossiers' attribute is an array it can not just be referenced. By letting $this->resp1->Dossiers and
		// $resp2->Dossiers point to the same content changes made in the runAfter of the server plug-ins to the
		// $resp2->Dossiers get reflected in $this->resp1->Dossiers.

		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialog2Response.class.php';
		$resp2 = new WflGetDialog2Response();

		require_once BASEDIR.'/server/utils/PHPClass.class.php';
		WW_Utils_PHPClass::copyObjProps( $this->resp1, $resp2 );

		// Enrich GetDialog2Response with additional GetDialogResponse field
		$resp2->Publications = $retVal['Publications'];
		$resp2->PublicationInfo = $retVal['PublicationInfo'];
		$resp2->oldClient = true; // Indicator it is from old client
		// EN-84968 - Allow GetDialog2 connector to manipulate the GetStateResponse value for GetDialog
		$resp2->GetStatesResponse = $retVal['GetStatesResponse'];
		$resp2->Dossiers = $retVal['Dossiers'];
		$this->resp1->Dossiers = &$resp2->Dossiers;

		return $resp2;
	}
}
