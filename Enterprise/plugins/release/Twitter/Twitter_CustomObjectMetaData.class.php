<?php
/**
 * @since       v9.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Sample that shows how to let a server plug-in automatically install custom object
 * properties into the database (instead of manual installation in the Metadata admin page).
 **/
require_once BASEDIR . '/server/interfaces/plugins/connectors/CustomObjectMetaData_EnterpriseConnector.class.php';

class Twitter_CustomObjectMetaData extends CustomObjectMetaData_EnterpriseConnector
{
	/** Constant for the Plugin type. */
	const Twitter_CustomObjectMetaData = 'Twitter';

	private function getPropertyDefinition()
	{
		$props = array();

		$tweetPropertyInfo = new PropertyInfo();
		$tweetPropertyInfo->Name = 'C_TPF_TWEET';
		$tweetPropertyInfo->DisplayName = 'Tweet';
		$tweetPropertyInfo->Category = null;
		$tweetPropertyInfo->Type = 'multiline';
		$tweetPropertyInfo->MinValue = null; // Empty allowed.
		$tweetPropertyInfo->MaxValue = null; // Many images allowed.
		require_once __DIR__.'/EnterpriseTwitterConnector.class.php';
		$tweetPropertyInfo->MaxLength = EnterpriseTwitterConnector::getMaxMessageLength();
		$tweetPropertyInfo->ValueList = null;
		$tweetPropertyInfo->AdminUI = false;
		$tweetPropertyInfo->Widgets = null;
		$tweetPropertyInfo->PropertyValues = array();
		$tweetPropertyInfo->PublishSystem = Twitter_CustomObjectMetaData::Twitter_CustomObjectMetaData;
		$props[] = $tweetPropertyInfo;

		$urlPropertyInfo = new PropertyInfo();
		$urlPropertyInfo->Name = 'C_TPF_URL';
		$urlPropertyInfo->DisplayName = 'URL';
		$urlPropertyInfo->Category = null;
		$urlPropertyInfo->Type = 'string';
		$urlPropertyInfo->MinValue = null; // Empty allowed.
		$urlPropertyInfo->MaxValue = null; // Many images allowed.
		$urlPropertyInfo->MaxLength = 200;
		$urlPropertyInfo->ValueList = null;
		$urlPropertyInfo->AdminUI = false;
		$urlPropertyInfo->Widgets = null;
		$urlPropertyInfo->PropertyValues = array();
		$urlPropertyInfo->PublishSystem = Twitter_CustomObjectMetaData::Twitter_CustomObjectMetaData;
		$props[] = $urlPropertyInfo;

		$mediaPropertyInfo = new PropertyInfo();
		$mediaPropertyInfo->Name = 'C_TPF_MEDIA';
		$mediaPropertyInfo->DisplayName = 'Media';
		$mediaPropertyInfo->Category = null;
		$mediaPropertyInfo->Type = 'file';
		$mediaPropertyInfo->MinValue = null; // Empty allowed.
		$mediaPropertyInfo->MaxValue = null; // Many images allowed.
		$mediaPropertyInfo->MaxLength = 4000000; // Max 4mb upload allowed by Twitter
		$mediaPropertyInfo->ValueList = null;
		$mediaPropertyInfo->AdminUI = false;
		$mediaPropertyInfo->Widgets = null;
		$mediaPropertyInfo->PropertyValues = self::getMediaPropertyValues();
		$mediaPropertyInfo->PublishSystem = Twitter_CustomObjectMetaData::Twitter_CustomObjectMetaData;
		$props[] = $mediaPropertyInfo;

		$mediaSelectorPropertyInfo = new PropertyInfo();
		$mediaSelectorPropertyInfo->Name = 'C_TPF_MEDIA_SELECTOR';
		$mediaSelectorPropertyInfo->DisplayName = 'Images';
		$mediaSelectorPropertyInfo->Category = null;
		$mediaSelectorPropertyInfo->Type = 'fileselector';
		$mediaSelectorPropertyInfo->MinValue = null; // Empty allowed.
		$mediaSelectorPropertyInfo->MaxValue = null; // Many images allowed.
		$mediaSelectorPropertyInfo->MaxLength = null;
		$mediaSelectorPropertyInfo->ValueList = null;
		$mediaSelectorPropertyInfo->AdminUI = false;
		$mediaSelectorPropertyInfo->Widgets = null;
		$mediaSelectorPropertyInfo->PropertyValues = array();
		$mediaSelectorPropertyInfo->PublishSystem = Twitter_CustomObjectMetaData::Twitter_CustomObjectMetaData;
		$props[] = $mediaSelectorPropertyInfo;

		return $props;
	}

	/**
	 * Return a list of supported media types
	 *
	 * @return array
	 */
	private function getMediaPropertyValues()
	{
		$properties = array();

		//Image
		$properties[] = new PropertyValue('image/png', '.png', 'Format');
		$properties[] = new PropertyValue('image/jpeg', '.jpg', 'Format');
		$properties[] = new PropertyValue('image/gif', '.gif', 'Format');

		return $properties;
	}

	/**
	 * See CustomObjectMetaData_EnterpriseConnector::collectCustomProperties function header.
	 */
	final public function collectCustomProperties($coreInstallation)
	{
		$coreInstallation = $coreInstallation; //Keep analyser happy
		$props = array();
		$props[0]['PublishForm'] = self::getPropertyDefinition();

		return $props;
	}

	/**
	 * Returns one of the properties or null if not found
	 * @param string $name The property to search for
	 * @return null
	 */
	public function getProperty($name)
	{
		$prop = null;
		$props = self::getPropertyDefinition();

		foreach ($props as $prop) {
			if ($prop->Name == $name) {
				return $prop;
			}
		}
		LogHandler::Log('Twitter', 'ERROR', ' property ' . $name . ' not found');
		return null;
	}


}
