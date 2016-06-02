<?php

/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */
    
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBOutputDevice extends DBBase
{
	const TABLENAME = 'outputdevices';
	
	/**
	 * Get all configured output devices (system wide). Keys are set to the deivice id.
	 *
	 * @return array Sorted array of OutputDevice data objects
	 */
	public static function getDevices()
	{
		// Query all devices from DB.
		$orderBy = array( 'code' => true, 'id' => true );
		$rows = self::listRows( self::TABLENAME, '', '', '', '*', null, $orderBy );
		
		// Convert device rows to objects.
		$devices = array();
		if( $rows ) foreach( $rows as $row ) {
			$devices[ $row['id'] ] = self::rowToObj( $row );
		}
		return $devices;
	}

	/**
	 * Retrieve Output Device definition from DB.
	 *
	 * @param integer $id Device id.
	 * @return OutputDevice
	 */
	public static function getDevice( $id )
	{
		$where = '`id` = ?';
		$params = array( intval($id) );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		return $row ? self::rowToObj($row) : null;
	}

	/**
	 * Stores new devices into DB (smart_outputdevices table).
	 *  
	 * @param array $devices List of new OutputDevice data objects to be stored.
	 * @return array List of stored OutputDevice data objects.
	 * @throws BizException on failure, e.g. when device name already exists.
	 */
	public static function createDevices( array $devices )
	{	
		$newDevices = array();
		foreach( $devices as $device ) {

			// Bail out when name exists.
			$where = '`name` = ?';
			$params = array( $device->Name );
			if( self::getRow( self::TABLENAME, $where, array('id'), $params ) ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', null, null );
			}
			
			// Store device into DB.
			$row = self::objToRow( $device );
			$newId = self::insertRow( self::TABLENAME, $row, true, $device->Description );
			
			// Retrieve created device from DB.			
			if( !is_null($newId) ) {
				$newDevice = self::getDevice( $newId );
				$newDevices[] = $newDevice;
			}	
		}
		return $newDevices;
	}

	/**
	 * Update existing devices into DB (smart_outputdevices table).
	 *  
	 * @param array $devices List of new OutputDevice data objects to be updated.
	 * @return array List of stored OutputDevice data objects.
	 * @throws BizException on failure, e.g. when device name already exists.
	 */
	public static function modifyDevices( array $devices )
	{	
		$modifiedDevices = array();
		foreach( $devices as $device ) {
	
			// Bail out when name exists.
			$where = '`name` = ? AND `id` != ?';
			$params = array( $device->Name, intval($device->Id) );
			if( self::getRow( self::TABLENAME, $where, array('id'), $params ) ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', null, null );
			}
			
			// Update device at DB.
			$row = self::objToRow( $device );
			$where = '`id` = ?';
			$params = array( intval( $device->Id ) );
			$result = self::updateRow( self::TABLENAME, $row, $where, $params, $device->Description );

			// Retrieve updated device from DB.			
			if( $result === true ){
				$modifiedDevices[] = self::getDevice( $device->Id );
			}	
		}
		return $modifiedDevices;
	}
	
	/**
	 * Re-orders devices stored at DB (smart_outputdevices table).
	 *  
	 * @param array $deviceCodes List of device sorting codes. Keys = device ids.
	 * @return boolean Whether the re-ordering was successful.
	 */
	public static function reorderDevices( array $deviceCodes )
	{
		$success = true;
		foreach( $deviceCodes as $deviceId => $sortOrder ) {
			$where = '`id` = ?';
			$params = array( intval( $deviceId ) );
			$row = array( 'code' => $sortOrder );
			if( !self::updateRow( self::TABLENAME, $row, $where, $params ) ) {
				$success = false;
				break;
			}
		}
		return $success;
	}
	
	/**
	 * Delete devices from DB (smart_outputdevices table).
	 *  
	 * @param array $deviceIds List of device ids to be deleted.
	 * @return boolean Whether the delete was successful.
	 */
	public static function deleteDevices( array $deviceIds )
	{
		$deviceIds = implode( ', ', $deviceIds ); // make ids comma separated to fit into SQL
		return (bool)self::deleteRows( self::TABLENAME, "`id` IN ( $deviceIds )" );
	}
	
	/**
	 * Converts an OutputDevice data object into DB row.
	 *
	 * @param OutputDevice $obj
	 * @return array DB row
	 */
	static private function objToRow( $obj )
	{
		$row = array();

		if( !is_null( $obj->Id ) ) {
			$row['id']              = $obj->Id;
		}
		if( !is_null( $obj->Name ) ) {
			$row['name']            = $obj->Name;
		}
		if( !is_null( $obj->Description ) ) {
			$row['description']     = '#BLOB#';
		}
		if( !is_null($obj->SortOrder) ) {
			$row['code']            = intval( $obj->SortOrder );
		}
		
		if(!is_null($obj->LandscapeWidth)){
			$row['landscapewidth']  = intval( $obj->LandscapeWidth );
		}
		if(!is_null($obj->LandscapeHeight)){
			$row['landscapeheight'] = intval( $obj->LandscapeHeight );
		}
		if(!is_null($obj->PortraitWidth)){
			$row['portraitwidth']   = intval( $obj->PortraitWidth );
		}
		if(!is_null($obj->PortraitHeight)){
			$row['portraitheight']  = intval( $obj->PortraitHeight );
		}
		
		if(!is_null($obj->PreviewQuality)){
			$row['previewquality']  = intval( $obj->PreviewQuality );
		}
		if(!is_null($obj->LandscapeLayoutWidth)){
			$row['landscapelayoutwidth'] = round(floatval( $obj->LandscapeLayoutWidth ), 3);
		}
		if(!is_null($obj->PixelDensity)){
			$row['pixeldensity']    = intval( $obj->PixelDensity );
		}
		if(!is_null($obj->PngCompression)){
			$row['pngcompression']  = intval( $obj->PngCompression );
		}

		if(!is_null($obj->TextViewPadding)){
			$row['textviewpadding'] = $obj->TextViewPadding;
		}
		
		return $row;
	}
	
	/**
	 * Converts a given DB row into an OutputDevice data object.
	 *
	 * @param array $row DB row
	 * @return OutputDevice
	 */
	static private function rowToObj( $row )
	{
		require_once BASEDIR.'/server/dataclasses/OutputDevice.php';
		$obj = new OutputDevice();
		
		$obj->Id              = $row['id'];
		$obj->Name            = $row['name'];
		$obj->Description     = $row['description'];
		$obj->SortOrder       = $row['code'];
		
		$obj->LandscapeWidth  = $row['landscapewidth'];
		$obj->LandscapeHeight = $row['landscapeheight'];
		$obj->PortraitWidth   = $row['portraitwidth'];
		$obj->PortraitHeight  = $row['portraitheight'];
		
		$obj->PreviewQuality  = $row['previewquality'];
		$obj->LandscapeLayoutWidth = round($row['landscapelayoutwidth'], 3);
		$obj->PixelDensity    = $row['pixeldensity'];
		$obj->PngCompression  = $row['pngcompression'];

		$obj->TextViewPadding = $row['textviewpadding'];

		return $obj;
	}
}
