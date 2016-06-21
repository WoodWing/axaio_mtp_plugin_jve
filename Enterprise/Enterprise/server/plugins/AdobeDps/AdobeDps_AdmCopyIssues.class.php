<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/services/adm/AdmCopyIssues_EnterpriseConnector.class.php';

class AdobeDps_AdmCopyIssues extends AdmCopyIssues_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( AdmCopyIssuesRequest &$req )
	{
		require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
		$setup = new WW_Utils_ResolveBrandSetup();
		$setup->resolveIssuePubChannelBrand( $req->IssueId );
		$pubChannelObj = $setup->getPubChannelInfo();

		if( $pubChannelObj && $pubChannelObj->Type == 'dps' ) {
			if( $req->Issues ) {
				require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
				require_once dirname(__FILE__).'/Utils/AdobeDpsAdminUtils.class.php';
				foreach( $req->Issues as $issue ) {
					$value = BizAdmProperty::getCustomPropVal($issue->ExtraMetaData, 'C_HIDDEN_DPS_DOSSIER_ORDER');
					if( !is_null( $value ) ) {
						BizAdmProperty::setCustomPropVal($issue->ExtraMetaData, 'C_HIDDEN_DPS_DOSSIER_ORDER', $value );
					} else {
						$issue->ExtraMetaData[] = new MetaDataValue( 'C_HIDDEN_DPS_DOSSIER_ORDER', array('') );
					}

					// Only set / overwrite the DPS product id if it is set in the issue ExtraMetaData, otherwise the value
					// is taken from the original issue by the duplicate issues functionality.
					$value = BizAdmProperty::getCustomPropVal($issue->ExtraMetaData, 'C_DPS_PRODUCTID');
					if( !is_null( $value ) ) {
						BizAdmProperty::setCustomPropVal($issue->ExtraMetaData, 'C_DPS_PRODUCTID', $value );
					}

					$value = BizAdmProperty::getCustomPropVal($issue->ExtraMetaData, 'C_DPS_PUBLICATION_TITLE');
					if( !is_null( $value ) ) {
						BizAdmProperty::setCustomPropVal($issue->ExtraMetaData, 'C_DPS_PUBLICATION_TITLE', $value );
					} else {
						$issue->ExtraMetaData[] = new MetaDataValue( 'C_DPS_PUBLICATION_TITLE', array('') );
					}

					$value = BizAdmProperty::getCustomPropVal($issue->ExtraMetaData, 'C_DPS_VOLUMENUMBER');
					if( !is_null( $value ) ) {
						BizAdmProperty::setCustomPropVal($issue->ExtraMetaData, 'C_DPS_VOLUMENUMBER', $value );
					} else {
						$issue->ExtraMetaData[] = new MetaDataValue( 'C_DPS_VOLUMENUMBER', array('') );
					}
					$adminUtils = new AdobeDpsAdminUtils();
					$adminUtils->validateProperties( $issue, $pubChannelObj->Id );
				}
			}
		}
	}
	
	final public function runAfter( AdmCopyIssuesRequest $req, AdmCopyIssuesResponse &$resp )
	{
		$req = $req;	// keep analyzer happy
		$resp = $resp;	// keep analyzer happy
	}
	
	final public function runOverruled( AdmCopyIssuesRequest $req )
	{
		$req = $req; // keep analyzer happy
	}
}
