<?php
/**
 * @package Enterprise
 * @subpackage BizClasses
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Biz class to administer Output Devices (for publishing).
 */

require_once BASEDIR.'/server/dataclasses/OutputDevice.php';

class BizAdmOutputDevice
{
	/**
	 * List of configured output devices (system wide).
	 *
	 * @var array|null of OutputDevice data classes. NULL when undetermined, which indicates
	 *                 that the full list of configured devices needs to be retrieved from DB.
	 */
	private $devices = null;

	/**
	 * Adds all Output Device configirations in a structured way to a given Feature collection.
	 * For each Output Device property a Feature data object is created with a DigitalMagazineDevice_ prefix
	 * followed by the name of the property, followed by the device id.
	 * ALso a Feature named 'DigitalMagazine' is added which is a collection of all device ids added.
	 * This option is only added when the DM plugin or DPS plug-in is enabled.
	 *
	 * @param ServerInfo $serverInfo Holds a FeatureSet to be enriched.
	 */
	public function addFeatureOutputDevices( ServerInfo $serverInfo )
	{
		// The 'DigitalMagazine' option is an indication that DPS is enabled.
		// Therefore we only add it when one of the plugins are enabled.
		if( !BizServerPlugin::isPluginActivated( 'AdobeDps' ) &&
			!BizServerPlugin::isPluginActivated( 'AdobeDps2' ) ) {
			return; // Nothing to do
		}

		// Retrieve all configured devices from DB. The order is important since clients assume that 
		// the first one is the -default- device for DM.
		$devices = $this->getDevices();
		$ids = array();
		if( $devices ) foreach( $devices as $device ) {
			$id = $device->Id;
			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_Name_'.$id, $device->Name );

			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_PortraitDeviceWidth_'.$id, $device->PortraitWidth );
			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_PortraitDeviceHeight_'.$id, $device->PortraitHeight );
			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_LandscapeDeviceWidth_'.$id, $device->LandscapeWidth );
			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_LandscapeDeviceHeight_'.$id, $device->LandscapeHeight );

			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_PixelDensity_'.$id, $device->PixelDensity );
			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_PreviewQuality_'.$id, $device->PreviewQuality );
			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_LandscapeLayoutWidth_'.$id, $device->LandscapeLayoutWidth );

			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_TextViewPadding_'.$id, $device->TextViewPadding );
			$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazineDevice_PNGCompression_'.$id, $device->PngCompression );
			
			$ids[] = $id;
		}
		// Always add since the plugins are enabled and so we need to tell our clients. 
		// So, no matter if devices are found or not.
		$serverInfo->FeatureSet[] = new Feature( 'DigitalMagazine', implode( ',' , $ids ) );

		// TODO: Let the plugins enrich the Feature list instead.
	}
	
	/**
	 * Return the defined output devices
	 *
	 * @return array of OutputDevice
	 */
	public function getDevices()
	{
		if( is_null($this->devices) ) {
			require_once BASEDIR.'/server/dbclasses/DBOutputDevice.class.php';
			$this->devices = DBOutputDevice::getDevices();
			foreach( $this->devices as $device ) {
				$device->setValid( $this->isValidDevice( $device ) );
			}
		}
		return $this->devices;
	}
	
	/**
	 * Retrieve Output Device definition from DB.
	 *
	 * @param integer $id Device id.
	 * @return OutputDevice
	 */
	public function getDevice( $id )
	{
		require_once BASEDIR.'/server/dbclasses/DBOutputDevice.class.php';
		$device = DBOutputDevice::getDevice( $id );
		$device->setValid( $this->isValidDevice( $device ) );
		return $device;
	}

	/**
	 * Stores new devices into DB (smart_outputdevices table).
	 *  
	 * @param array $devices List of new OutputDevice data objects to be stored.
	 * @return array List of stored OutputDevice data objects.
	 * @throws BizException on failure, e.g. when device name already exists.
	 */
	public function createDevices( array $devices )
	{
		$this->devices = null; // reset (to trigger getting all devices again on successing calls)
		foreach( $devices as $device ) {
			$device->setValid( $this->isValidDevice( $device ) );
			if( $device->isValid() == false ) {
				throw new BizException( 'ERR_MANDATORYFIELDS', 'Client', null, null );
			}
		}
		require_once BASEDIR.'/server/dbclasses/DBOutputDevice.class.php';
		return DBOutputDevice::createDevices( $devices );
	}

	/**
	 * Update existing devices into DB (smart_outputdevices table).
	 *  
	 * @param array $devices List of new OutputDevice data objects to be updated.
	 * @return array List of stored OutputDevice data objects.
	 * @throws BizException on failure, e.g. when device name already exists.
	 */
	public function modifyDevices( array $devices )
	{
		$this->devices = null; // reset (to trigger getting all devices again on success calls)
		foreach( $devices as $device ) {
			$device->setValid( $this->isValidDevice( $device ) );
			if( $device->isValid() == false ) {
				throw new BizException( 'ERR_MANDATORYFIELDS', 'Client', null, null );
			}
		}
		require_once BASEDIR.'/server/dbclasses/DBOutputDevice.class.php';
		return DBOutputDevice::modifyDevices( $devices );
	}

	/**
	 * Re-orders devices stored at DB (smart_outputdevices table).
	 *  
	 * @param array $deviceCodes List of device sorting codes. Keys = device ids.
	 * @return boolean Whether the re-ordering was successful.
	 */
	public function reorderDevices( array $deviceCodes )
	{
		$this->devices = null; // reset (to trigger getting all devices again on successing calls)
		require_once BASEDIR.'/server/dbclasses/DBOutputDevice.class.php';
		return DBOutputDevice::reorderDevices( $deviceCodes );
	}
	
	/**
	 * Delete devices from DB (smart_outputdevices table).
	 *  
	 * @param array $deviceIds List of device ids to be deleted.
	 * @return boolean Whether the delete was successful.
	 */
	public function deleteDevices( array $deviceIds )
	{
		$this->devices = null; // reset (to trigger getting all devices again on successing calls)
		require_once BASEDIR.'/server/dbclasses/DBOutputDevice.class.php';
		return DBOutputDevice::deleteDevices( $deviceIds );
	}
	
	/**
	 * Get the device object for a device id
	 * 
	 * @param int $deviceId
	 * 
	 * @return OutputDevice or null if not found
	 */
	public function getDeviceForDeviceId( $deviceId )
	{
		$allDevices = $this->getDevices();
		return isset($allDevices[$deviceId]) ? $allDevices[$deviceId] : null;
	}

	/**
	 * Return the defined output devices for the specified issue id
	 *
	 * @param string $issueId
	 * @param integer $numberOfEditions Number of editions defined for the device.
	 *
	 * @return array of OutputDevice filtered for the issue
	 */
	public function getDevicesForIssue( $issueId, &$numberOfEditions )
	{
		// Collect used edition names of the DPS channel.
		// Those represent the enabled devices.
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
		$channelId = DBIssue::getChannelId( $issueId );
		$editions = DBEdition::listChannelEditionsObj( $channelId );
		$enabledDeviceNames = array();
		if( $editions ) foreach( $editions as $edition ) {
			$enabledDeviceNames[] = $edition->Name;
		}
		$numberOfEditions = count( $enabledDeviceNames );

		// Set $this->devices
		$this->getDevices(); 
		
		// Filter the devices on the edition name.
		$filteredDevices = array();
		if( $this->devices ) foreach( $this->devices as $device ) {
			if( in_array( $device->Name, $enabledDeviceNames ) )
				$filteredDevices[$device->Id] = $device;
		}
		return $filteredDevices;
	}

	/**
	 * Check if a created output device is configured correctly.
	 * It checks if all mandatory fields are filled in.
	 *
	 * @param OutputDevice $device
	 * @return boolean Whether or not valid.
	 */
	private function isValidDevice( OutputDevice $device )
	{
		return
			!empty( $device->Name ) &&
			!empty( $device->PortraitWidth ) &&
			!empty( $device->PortraitHeight ) &&
			!empty( $device->LandscapeWidth ) &&
			!empty( $device->LandscapeHeight ) &&
			!empty( $device->PixelDensity ) &&
			!empty( $device->LandscapeLayoutWidth );
	}
}
