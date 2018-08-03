<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Utility that provides the settings made for Adobe DPS by the system administrator.
 */
class AdobeDps2_BizClasses_Config
{
	/**
	 * Returns the value of a custom property, as configured for a given Publication Channel.
	 *
	 * @param AdmExtraMetaData[]|null $extraMetaData Custom props of Publication Channel.
	 * @param string $optionName Name of the configuration option.
	 * @return string|null The configuration option value. NULL when none found.
	 */
	private static function getOptionValue( $extraMetaData, $optionName )
	{
		require_once BASEDIR .'/server/bizclasses/BizAdmProperty.class.php';
		return BizAdmProperty::getCustomPropVal( $extraMetaData, $optionName );
	}

	/**
	 * Sets the value of a custom property, as configured for a given Publication Channel.
	 *
	 * @param AdmExtraMetaData[]|null $extraMetaData
	 * @param string $optionName Name of the configuration option.
	 * @param string $optionValue Value of the configuration option.
	 */
	private static function setOptionValue( &$extraMetaData, $optionName, $optionValue )
	{
		// This does not work for read-only properties, since they are not round-tripped
		// and therefore NOT added to the ExtraMetaData collection in setCustomPropVal():
		//    require_once BASEDIR .'/server/bizclasses/BizAdmProperty.class.php';
		//    return BizAdmProperty::setCustomPropVal( $extraMetaData, $optionName, array($optionValue) );
		
		// If property can be found, overwrite its value.
		$found = false;
		if( $extraMetaData ) foreach( $extraMetaData as $custProp ) {
			if( $custProp->Property == $optionName ) {
				$custProp->Values = array($optionValue);
				$found = true;
				break;
			}
		}
		
		// If property could not be found, add a new property (and set the value).
		if( !$found ) {
			$custProp = new AdmExtraMetaData();
			$custProp->Property = $optionName;
			$custProp->Values = array($optionValue);
			if( !$extraMetaData ) {
				$extraMetaData = array();
			}
			$extraMetaData[] = $custProp;
		}
	}

	/**
	 * Returns the Project reference, as configured for a given 'dps2' Publication Channel.
	 *
	 * From Adobe DPS point of view, this is the external id, and is stored in Enterprise Server.
	 * In the API this is called publication name. In the Adobe UI this is called "Link Reference"
	 * which can be found on the "Project" configuration page.
	 * Note that this field is read-only at the Publication Channel Maintenance page. It can only
	 * be set through the registration procedure.
	 *
	 * @param AdmPubChannel $pubChannel
	 * @return string|null The project reference. NULL when none found.
	 */
	public static function getProjectRef( $pubChannel )
	{
		return self::getOptionValue( $pubChannel->ExtraMetaData, 'C_DPS2_CHANNEL_PROJECT' );
	}
	
	/**
	 * Returns the Project Id, as configured for a given 'dps2' Publication Channel.
	 *
	 * From Adobe DPS point of view, this is the internal id, and is stored in Enterprise Server.
	 * In the API this is called publication id. In the Adobe UI this is not mentioned/shown.
	 * Note that this field is hidden from the Publication Channel Maintenance page. It can only
	 * be set through the registration procedure.
	 *
	 * @param AdmPubChannel $pubChannel
	 * @return string|null The project id. NULL when none found.
	 */
	public static function getProjectId( $pubChannel )
	{
		return self::getOptionValue( $pubChannel->ExtraMetaData, 'C_DPS2_CHANNEL_PROJECT_ID' );
	}
	
	/**
	 * Saves the Project reference and id. See getProjectRef() / getProjectId() function headers for more info.
	 *
	 * @param integer $pubChannelId
	 * @param string $projectRef
	 * @param string $projectId
	 * @return boolean Whether or not the save operation was successful.
	 */
	public static function setProject( $pubChannelId, $projectRef, $projectId )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );

		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$extraMetaData = DBChanneldata::getCustomProperties( 'PubChannel', $pubChannelId, $typeMap );
		self::setOptionValue( $extraMetaData, 'C_DPS2_CHANNEL_PROJECT', $projectRef );
		self::setOptionValue( $extraMetaData, 'C_DPS2_CHANNEL_PROJECT_ID', $projectId );
		return DBChanneldata::saveCustomProperties( 'PubChannel', $pubChannelId, $extraMetaData, $typeMap );
	}

	/**
	 * Returns the AP authentication server URL.
	 *
	 * @return string AP authentication server URL.
	 */
	public static function getAuthenticationUrl()
	{
		require_once dirname(dirname(__FILE__)).'/config.php';
		return DSP2_AUTHENTICATION_URL;
	}
	
	/**
	 * Returns the AP authorization server URL.
	 *
	 * @return string AP authorization server URL.
	 */
	public static function getAuthorizationUrl()
	{
		require_once dirname(dirname(__FILE__)).'/config.php';
		return DSP2_AUTHORIZATION_URL;
	}
	
	/**
	 * Returns the AP producer server URL.
	 *
	 * @return string AP producer server URL.
	 */
	public static function getProducerUrl()
	{
		require_once dirname(dirname(__FILE__)).'/config.php';
		return DSP2_PRODUCER_URL;
	}
	
	/**
	 * Returns the AP ingestion server URL.
	 *
	 * @return string AP ingestion server URL.
	 */
	public static function getIngestionUrl()
	{
		require_once dirname(dirname(__FILE__)).'/config.php';
		return DSP2_INGESTION_URL;
	}

	/**
	 * Read the Consumer Key from the smart_config table.
	 *
	 * @return string Consumer Key.
	 */
	public static function getConsumerKey()
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$consumerKey = DBConfig::getValue( 'DSP2_CONSUMER_KEY' );
		return is_null($consumerKey) ? '' : $consumerKey;
	}

	/**
	 * Stores the Consumer Key in the smart_config table.
	 *
	 * @param string Consumer Key.
	 * @return boolean Whether or not saved.
	 */
	public static function saveConsumerKey( $consumerKey )
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( 'DSP2_CONSUMER_KEY', $consumerKey );
	}
	
	/**
	 * Reads the Consumer Secret from the smart_config table.
	 *
	 * @return string Consumer Secret.
	 */
	public static function getConsumerSecret()
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$consumerSecret = DBConfig::getValue( 'DSP2_CONSUMER_SECRET' );
		return is_null($consumerSecret) ? '' : $consumerSecret;
	}
	
	/**
	 * Stores the Consumer Secret in the smart_config table.
	 *
	 * @param string Consumer Secret.
	 * @return boolean Whether or not saved.
	 */
	public static function saveConsumerSecret( $consumerSecret )
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( 'DSP2_CONSUMER_SECRET', $consumerSecret );
	}
	
	/**
	 * Returns the 'dps2' publication channels.
	 *
	 * @return array[] Multi dimensional list of AdmPubChannel, grouped/indexed per brand (id).
	 */
	public static function getPubChannels()
	{
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		$pubChannelInfos = DBChannel::getChannelsByPublishSystem( 'AdobeDps2' ); // @TODO: better would be a search by Type='dps2', but that would require core changes or SQL in plugin
		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );
		$pubChannels = array();
		if( $pubChannelInfos ) foreach( $pubChannelInfos as $pubChannelInfo ) {
			$pubId = DBChannel::getPublicationId( $pubChannelInfo->Id );
			$pubChannels[$pubId][] = DBAdmPubChannel::getPubChannelObj( $pubChannelInfo->Id, $typeMap );
		}
		return $pubChannels;
	}
}
