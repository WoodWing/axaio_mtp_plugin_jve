<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Controls the storage of the Device Token used for Adobe DPS authorization.
 */

class AdobeDps2_BizClasses_Authorization
{
	/**
	 * Retrieves the Device Token.
	 *
	 * Returns the Device Token that gives access to Adobe DPS. This was retrieved 
	 * from Adobe DPS before during interactive the authorization session. 
	 *
	 * The Device Token is stored in the the filestore with the following path:
	 *    <filestore>/_SYSTEM_/adobedps2_devicetoken.bin
	 *
	 * @return string The Device Token.
	 */
	public function getDeviceToken()
	{
		$tokenFile = $this->composeDeviceTokenFilePath();
		$deviceToken = '';
		if( file_exists( $tokenFile ) ) {
			$deviceToken = file_get_contents( $tokenFile );
			if( !$deviceToken ) {
				$deviceToken = ''; // change false into empty
			}
		}
		return $deviceToken;
	}

	/**
	 * Retrieves the Device Id.
	 *
	 * Returns the Device Id that gives access to Adobe DPS. This was retrieved 
	 * from Adobe DPS before during interactive the authorization session. 
	 *
	 * The Device Id is stored in the the filestore with the following path:
	 *    <filestore>/_SYSTEM_/adobedps2_deviceid.bin
	 *
	 * @return string The Device Id.
	 */
	public function getDeviceId()
	{
		$idFile = $this->composeDeviceIdFilePath();
		$deviceId = '';
		if( file_exists( $idFile ) ) {
			$deviceId = file_get_contents( $idFile );
			if( !$deviceId ) {
				$deviceId = ''; // change false into empty
			}
		}
		return $deviceId;
	}

	/**
	 * Saves the Device Token.
	 *
	 * Saves the Device Token that gives access to Adobe DPS. This was retrieved from
	 * Adobe DPS before during interactive the authorization session.
	 *
	 * The Device Token is stored in the the filestore with the following path:
	 *    <filestore>/_SYSTEM_/adobedps2_devicetoken.bin
	 *
	 * @param string $deviceToken The Device Token.
	 * @return boolean Whether or not saved successfully.
	 */
	public function saveDeviceToken( $deviceToken )
	{
		$tokenFile = $this->composeDeviceTokenFilePath();
		$savedToken = file_put_contents( $tokenFile, trim($deviceToken) ) !== false;
		if( !$savedToken ) {
			LogHandler::Log( 'AdobeDps2', 'ERROR', 'Could not write Device Token in file: '.$tokenFile );
		}
		return $savedToken;
	}

	/**
	 * Saves the Device Id.
	 *
	 * Saves the Device Id that gives access to Adobe DPS. This was retrieved from
	 * Adobe DPS before during interactive the authorization session.
	 *
	 * The Device Id is stored in the the filestore with the following path:
	 *    <filestore>/_SYSTEM_/adobedps2_deviceid.bin
	 *
	 * @param string $deviceId The Device Id.
	 * @return boolean Whether or not saved successfully.
	 */
	public function saveDeviceId( $deviceId )
	{
		$idFile = $this->composeDeviceIdFilePath();
		$savedId = file_put_contents( $idFile, trim($deviceId) ) !== false;
		if( !$savedId ) {
			LogHandler::Log( 'AdobeDps2', 'ERROR', 'Could not write Device Id in file: '.$idFile );
		}
		return $savedId;
	}

	/**
	 * Tells whether or not a Device Token is registered.
	 *
	 * @return boolean Whether or not registered.
	 */
	public function hasDeviceToken()
	{
		// Has Device Token?
		$tokenFile = $this->composeDeviceTokenFilePath();
		return file_exists( $tokenFile ) && filesize( $tokenFile ) > 0;
	}
	
	/**
	 * Tells whether or not a Device Id is registered.
	 *
	 * @return boolean Whether or not registered.
	 */
	public function hasDeviceId()
	{
		$idFile = $this->composeDeviceIdFilePath();
		return file_exists( $idFile ) && filesize( $idFile ) > 0;
	}
	
	/**
	 * Retrieves the file path of the Device Token.
	 *
	 * @return string The file path of the Device Token.
	 */
	private function composeDeviceTokenFilePath()
	{
		return WOODWINGSYSTEMDIRECTORY . '/adobedps2_devicetoken.bin';
	}

	/**
	 * Retrieves the file path of the Device Id.
	 *
	 * @return string The file path of the Device Id.
	 */
	private function composeDeviceIdFilePath()
	{
		return WOODWINGSYSTEMDIRECTORY . '/adobedps2_deviceid.bin';
	}
}