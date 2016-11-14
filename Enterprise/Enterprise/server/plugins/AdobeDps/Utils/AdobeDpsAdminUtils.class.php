<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * 
 * Covers the utilities for Admin Services.
 * Define default settings of Adobe Dps custom properties - which is needed
 * for AdmServices when those properties are not being set yet.
 *
 */
require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
require_once BASEDIR.'/config/config_dps.php';
class AdobeDpsAdminUtils
{
	// All AdobeDps custom properties
	private $allCustomProps = array( 'C_DPS_PUBLICATION_TITLE',	// magazine title
									 'C_DPS_PRODUCTID',			// unique identifier of folio
									 'C_DPS_PAGE_ORIENTATION',	// page orientation
									 'C_DPS_NAVIGATION',		// article navigation
									 'C_DPS_READINGDIRECTION',	// reading direction
									 'C_DPS_VOLUMENUMBER',		// unique publisher defined identifier for folio
									);
	private static $dossierOrderPropName = 'C_HIDDEN_DPS_DOSSIER_ORDER';
	/**
	 * This function returns the Adobe Dps custom properties $customProps with its respective values:
	 * 
	 * The first three, it sets empty array by default.
	 * For custom properties in $lastIssueFields, the function will first attempt to get the settings(values)
	 * from last created Adobe Dps issue given the pub channel Id($channelId), however,
	 * when there's no last created Adobe Dps issue found or the last created Adobe Dps issue
	 * doesn't have these settings set, this function will then just set the default
	 * as below:
	 * C_DPS_PRODUCTID			= ''
	 * C_DPS_PAGE_ORIENTATION	= horizontalAndVertical
	 * C_DPS_NAVIGATION			= both
	 * C_DPS_PUBLICATION_TITLE	= ''
	 * C_DPS_VOLUMENUMBER		= ''
	 * C_DPS_READINGDIRECTION' 	= left
	 * 
	 * For other properties than in $lastIssueFields, set it to empty string.
	 * 
	 * @param int $channelId Publication channel DB Id
	 * @return array $customProps Custom properties of Digital Magazine
	 */
	
	public function getCustomProps( $channelId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
		//require_once dirname(__FILE__) . '/../Services/ExportMagazineService.class.php';
		$customProps = array();
		/**
		 * For the following three DM prop(C_HIDDEN_DM_DOSSIER_ORDER,
		 * C_HIDDEN_DM_ADVERT_RELATIONS,C_HIDDEN_ISSUE_ACTIVATION_DATE),
		 * set to default empty array.
		 */
		/*
		$customProps['C_HIDDEN_DM_DOSSIER_ORDER'] = array('');
		$customProps['C_HIDDEN_DM_ADVERT_RELATIONS'] = array('');
		$customProps['C_HIDDEN_ISSUE_ACTIVATION_DATE'] = array('');
		*/
		/**
		 * For the following Adobe Dps Props in $lastIssueFields array,
		 * try getting the settings from last created DPS issue
		 */
		$channelIss = DBAdmIssue::listChannelIssuesObj( $channelId );
		$lastCreatedIssue = ( $channelIss ) ? end( $channelIss ) : null;
		$lastCreatedIssId = ( $lastCreatedIssue ) ? $lastCreatedIssue->Id : null;
		$lastIssueFields = array('C_DPS_PUBLICATION_TITLE'	=> null,	// magazine title
								 'C_DPS_PRODUCTID'			=> null,	// unique identifier of folio
								 'C_DPS_PAGE_ORIENTATION' 	=> null,	// page orientation
								 'C_DPS_NAVIGATION' 		=> null,	// article navigation
								 'C_DPS_VOLUMENUMBER' 		=> null,	// unique publisher defined identifier for folio
								 'C_DPS_READINGDIRECTION' 	=> null,	// reading direction
								);
		if( $lastCreatedIssId ){
			// Try to get ExtraMetaData from issue obj first.
			$issueObj = DBAdmIssue::getIssueObj( $lastCreatedIssId );

			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			// Get the custom properties setting from the last created DPS issue
			foreach( array_keys($lastIssueFields) as $key ) {
				$lastIssueFields[$key] = BizAdmProperty::getCustomPropVal( $issueObj->ExtraMetaData, $key );
			}
		}
		// Set to default when there's no last created issue found or when last created issue doesn't have DPS Prop defined.
		$customProps['C_DPS_PUBLICATION_TITLE']	= is_null( $lastIssueFields['C_DPS_PUBLICATION_TITLE'] )? array('') 	: array( $lastIssueFields['C_DPS_PUBLICATION_TITLE'] );
		$customProps['C_DPS_PRODUCTID']			= is_null( $lastIssueFields['C_DPS_PRODUCTID'] )		? array('') 	: array( $lastIssueFields['C_DPS_PRODUCTID'] );
		$customProps['C_DPS_PAGE_ORIENTATION']	= is_null( $lastIssueFields['C_DPS_PAGE_ORIENTATION'] )	? array('always') : array( $lastIssueFields['C_DPS_PAGE_ORIENTATION'] );
		$customProps['C_DPS_NAVIGATION']		= is_null( $lastIssueFields['C_DPS_NAVIGATION'] )		? array('horizontalAndVertical') : array( $lastIssueFields['C_DPS_NAVIGATION'] );
		$customProps['C_DPS_VOLUMENUMBER']		= is_null( $lastIssueFields['C_DPS_VOLUMENUMBER'] )		? array('')		: array( $lastIssueFields['C_DPS_VOLUMENUMBER'] );
		$customProps['C_DPS_READINGDIRECTION']	= is_null( $lastIssueFields['C_DPS_READINGDIRECTION'] )	? array('left')	: array( $lastIssueFields['C_DPS_READINGDIRECTION'] );

		// Set other property default value to empty string
		foreach( $this->allCustomProps as $customProp ) {
			if( !array_key_exists( $customProp, $customProps) ) {
				$customProps[$customProp] = array('');
			}
		}
		return $customProps;
	}
	
	/**
	 * During create issue (e.g from Planning App CS), ExtraMetaData is not being sent along with the
	 * AdmIssue Object. This is because the WSDL does not support this new feature, which doesn't have the
	 * ExtraMetaData defined.
	 * Therefore this function will 'repair' by adding back the custom properties
	 * defined for DM (These properties are retrieved from $this->getCustomProps())
	 * 
	 * For modify issue, when the request is coming from Planning App in CS, the ExtraMD is not
	 * sent along as well, for this case, we need to check whether there are any existing ExtraMD
	 * on this issue before we 'repair'.
	 * 'Repairing' for this case referes to adding the newly introduced custom properties
	 * which are introduced @since v7.5.
	 * 
	 *
	 * @param AdmIssue $reqIssue
	 * @param int $pubChannelId Publication channel DB id
	 */
	public function addPropertiesToIssue( $reqIssue, $pubChannelId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
		
		$existCustomProp=array();
		$reqIssue->ExtraMetaData = isset($reqIssue->ExtraMetaData) ? $reqIssue->ExtraMetaData : array();
		
		if( $reqIssue->Id > 0 ){ // indicate this issue already exists in DB (action is update, not create)
			// We need to ensure the already existing extraMD is being attached to this reqIssue.
			$oriIssObj = DBAdmIssue::getIssueObj( $reqIssue->Id ); // need to see check whether there's already any ExtraMD defined.
			if( isset( $oriIssObj->ExtraMetaData) ){
				foreach( $oriIssObj->ExtraMetaData as $extraMD ){
					// Set to true when customer property defined
					if( in_array($extraMD->Property, $this->allCustomProps) ) {
						$existCustomProp[$extraMD->Property] = true;
					}
				}
				$reqIssue->ExtraMetaData = $oriIssObj->ExtraMetaData;
			}
		}
		$nonExistsExtraMD = array_diff_key( $this->getCustomProps( $pubChannelId ), $existCustomProp );
		/* If AdobeDps customProps don't exists in the existing, then we 
		* need to 'repair' / build those AdobeDps customProps that don't exists in original ExtraMD. */
		if( $nonExistsExtraMD ){
			foreach( $nonExistsExtraMD as $propKey => $propVal ){
				$reqIssue->ExtraMetaData[] = new AdmExtraMetaData($propKey, $propVal);
			}
		}
	}

	/**
	 * Validates the values of the properties.
	 * @param AdmIssue $reqIssue The new/modified issue with its properties.
	 * @param int $pubChannelId The id of the publication channel of the issue.
	 */
	public function validateProperties(  AdmIssue $reqIssue, $pubChannelId )
	{
		foreach( $reqIssue->ExtraMetaData as $extraMetaData ) {
			if ( $extraMetaData->Property === 'C_DPS_PRODUCTID' ) {
				$this->validateDPSProductId( $reqIssue, $extraMetaData, $pubChannelId );
			}
			if ( $extraMetaData->Property === 'C_DPS_IS_FREE' ) {
				throw new BizException( 'ERR_VERSION_CLIENT', 'Client', '', null );
			}
		}	
	}

	/**
	 * Checks if the DPS Product ID is unique and meets the conditions as set by Adobe (length, valid characters and prefix, etc).
	 * @param AdmIssue $reqIssue The new/modified issue with its properties.
	 * @param AdmExtraMetaData $extraMetaData Metadata to be validated .
	 * @param int $pubChannelId The id of the publication channel of the issue.
	 * @throws BizException
	 */
	private function validateDPSProductId( AdmIssue $reqIssue, AdmExtraMetaData $extraMetaData, $pubChannelId )
	{
		$issueId = ( isset( $reqIssue->Id ) && $reqIssue->Id > 0 ) ? $reqIssue->Id : 0; 
		$allowDupl = ( defined( 'AllowDuplicateProductID' ) && AllowDuplicateProductID === true ) ? true : false; 
		$unique = true;
		
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$prodId = is_array( $extraMetaData->Values ) ? $extraMetaData->Values[0] : $extraMetaData->Values;
		if( is_null($prodId) || trim($prodId) == '' ) {
			throw new BizException( 'DPS_ERROR_PRODUCTID_EMPTYNULL', 'Client', null );
		} elseif ( $allowDupl ) { 
		// Check ofProduct ID is unique within the channel. 	
			$unique = DBChanneldata::isUniqueInChannel( $pubChannelId, $issueId, $extraMetaData->Property, $prodId );
		} else { 
		// Check ofProduct ID is unique in all channels (issues). 	
			$unique =  DBChanneldata::isUnique( $issueId, $extraMetaData->Property, $prodId ) ;
		}
		if ( !$unique ) {
			require_once BASEDIR.'/server/dbclasses/DBAdmProperty.class.php';
			$propertyInfo = DBAdmProperty::getPropertyInfos('Issue', 'AdobeDps', $extraMetaData->Property);
			$propertyName = $propertyInfo[0]->DisplayName;
			throw new BizException( 'ERR_SUBJECT_EXISTS', 'Client', '', null, array( $propertyName, $prodId ));
		}
		if( strlen($prodId) > 100 ) {
			throw new BizException( 'DPS_ERROR_PRODUCTID_MAXLENGTH', 'Client', null );
		}
		if( substr($prodId, 0, 12) == 'android.test' ) {
			throw new BizException( 'DPS_ERROR_PRODUCTID_ANDROID_PREFIX', 'Client', null );
		}
		if( substr($prodId, 0, 1) == '.' || substr($prodId, 0, 1) == '_' ) {
			throw new BizException( 'DPS_ERROR_PRODUCTID_PREFIX', 'Client', null );
		}
		if( preg_match("/[^a-zA-Z0-9._]/", $prodId) > 0 ) {
			throw new BizException( 'DPS_ERROR_PRODUCTID_INVALID_CHARACTERS', 'Client', null );
		}
	}	
	
}
