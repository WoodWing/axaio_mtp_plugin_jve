<?php
/**
 * @package 	Enterprise
 * @subpackage 	AdobeDps2
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the workflow dialog operation. 
 *
 * For Create/CheckIn workflow dialogs SC, shows the "Create Adobe DPS Article" option.
 * The default value is normally controlled by SC, which would 'on' it for creations
 * but leaves it 'off' for intermediate saves. 
 *
 * However, those defaults should be controlled by the "Ready to be Published" status; 
 * When creating/saving layouts in such status, the option should be 'on', else 'off'.
 *
 * SC supports a special hidden property named C_DPS_FOLIO_CREATION_MODE that can be returned 
 * by the GetDialogResponse and allows to control the default value by specifying one
 * of the following values:
 * - ON:       Checkbox is always selected
 * - OFF:      Checkbox is always deselected
 * - AUTO:     Deselected for Save As and Save Version. Selected for Checkin.
 * - IDSERVER: No folio is created, the checkbox is not shown. This mode can be used when InDesign Server generates the folios.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialog2_EnterpriseConnector.class.php';

class AdobeDps2_WflGetDialog2 extends WflGetDialog2_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflGetDialog2Request &$req )
	{
	}

	final public function runAfter( WflGetDialog2Request $req, WflGetDialog2Response &$resp )
	{
		// The "Create Adobe DPS Article" option is only shown by SC.
		require_once BASEDIR . '/server/dbclasses/DBTicket.class.php';
		$app = DBTicket::DBappticket( $req->Ticket );
		if( !stristr($app, 'indesign') && !stristr($app, 'incopy') ) {
			LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Bailed out; Calling client is not SC.' );
			return; // bail out; nothing to do
		}
		
		// SC shows the "Create Adobe DPS Article" option only for Create and Save dialogs.
		if( $req->Action != 'Create' && $req->Action != 'CheckIn' ) {
			LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Bailed out; Dialog is not Create nor CheckIn.' );
			return; // bail out; nothing to do
		}
		
		// Read some essiental properties from the request data (to be round-tripped).
		$objectId = null; $objectType = null; $statusId = null; $issueId = null;
		if( $req->MetaData ) foreach( $req->MetaData as $mdValue ) {
			switch( $mdValue->Property ) {
				case 'ID':
					$objectId = isset($mdValue->PropertyValues[0]->Value) ? $mdValue->PropertyValues[0]->Value : null;
					break;
				case 'Type':
					$objectType = isset($mdValue->PropertyValues[0]->Value) ? $mdValue->PropertyValues[0]->Value : null;
					break;
				case 'State':
					$statusId = isset($mdValue->PropertyValues[0]->Value) ? $mdValue->PropertyValues[0]->Value : null;
					break;
				case 'Issue':
					$issueId = isset($mdValue->PropertyValues[0]->Value) ? $mdValue->PropertyValues[0]->Value : null;
					break;
			}
		}
		
		// SC shows the "Create Adobe DPS Article" option only on dialog for layouts.
		if( $objectType != 'Layout' ) {
			LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Bailed out; Object is not a Layout.' );
			return; // bail out; nothing to do
		}
		
		// When status can be found in response data (has round-tripped), we prefer that.
		if( $resp->Dialog->MetaData ) foreach( $resp->Dialog->MetaData as $mdValue ) {
			switch( $mdValue->Property ) {
				case 'State':
					$statusId = $mdValue->PropertyValues[0]->Value;
					break;
				case 'C_DPS_FOLIO_CREATION_MODE':
					LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Bailed out; Option already handled by other plugin.' );
					return; // bail out; nothing to do
			}
		}
		
		// When status is not configured for the dialog, read the current status from DB.
		if( !$statusId && $objectId ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$statusId = DBObject::getColumnValueByName( $objectId, 'Workflow', 'state' );
		}
		
		// Determine whether or not the layout status is "Ready to be Published".
		$isReadyStatus = false;
		if( $statusId ) {
			require_once dirname(__FILE__).'/utils/Folio.class.php';
			$isReadyStatus = AdobeDps2_Utils_Folio::isStatusReadyToPublish( $statusId );
		}
		LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Is a Ready to be Published status: '.($isReadyStatus?'yes':'no') );
		
		// Determine whether or not the layout's pub channel is of type 'dps2'.
		$isApChannel = false;
		if( $issueId ) {
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			$channelId = DBIssue::getChannelId( $issueId );
			if( $channelId ) {
				require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
				$isApChannel = DBChannel::getPublishSystemByChannelId( $channelId ) == 'AdobeDps2';
			}
		}
		LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Is a Adobe DPS pub channel: '.($isApChannel?'yes':'no') );
		
		
		// Determine the default value for the "Create Adobe DPS Article" option on dialog.
		$defaultValue = $isApChannel && $isReadyStatus ? 'ON' : 'OFF';
		LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Default value for "Create Adobe DPS Article" option: '.$defaultValue );
		
		// Add the special hidden custom object property to the response (as understood by SC)
		// to control the default value of the "Create Adobe DPS Article" option.
		$dialogTabs = $resp->Dialog->Tabs;
		if( $dialogTabs ) {
			$dialogTab = $dialogTabs[0];
			$dialogWidget = new DialogWidget();
			$dialogWidget->PropertyInfo = new PropertyInfo();
			$dialogWidget->PropertyInfo->Name = 'C_DPS_FOLIO_CREATION_MODE';
			$dialogWidget->PropertyInfo->Type = 'string';
			$dialogWidget->PropertyInfo->DisplayName = '';
			$dialogWidget->PropertyInfo->DefaultValue = '';
			$dialogWidget->PropertyUsage = new PropertyUsage();
			$dialogWidget->PropertyUsage->Name = 'C_DPS_FOLIO_CREATION_MODE';
			$dialogWidget->PropertyUsage->Editable = false;
			$dialogWidget->PropertyUsage->Mandatory = false;
			$dialogWidget->PropertyUsage->Restricted = false;
			$dialogWidget->PropertyUsage->RefreshOnChange = false;
			$dialogWidget->PropertyUsage->Editable = false;
			$dialogTab->Widgets['C_DPS_FOLIO_CREATION_MODE'] = $dialogWidget;
		}		
		$mdValue = new MetaDataValue();
		$mdValue->Property = 'C_DPS_FOLIO_CREATION_MODE';
		$mdValue->Values = array( $defaultValue );
		$resp->Dialog->MetaData[] = $mdValue;
	}

	final public function runOverruled( WflGetDialog2Request $req )
	{
	}
}