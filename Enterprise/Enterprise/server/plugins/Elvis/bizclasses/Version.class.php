<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_Version
{
	/**
	 * Retrieve version info of an asset (from Elvis server) for a provided asset id.
	 *
	 * @param string $alienId Alien id of shadow object in Enterprise.
	 * @param string $rendition Rendition to include in the version info
	 * @return VersionInfo[]
	 */
	public function listVersions( string $alienId, string $rendition ) : array
	{
		$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHits = $client->listVersions( $assetId );
		$hits = array_map( array( 'Elvis_DataClasses_EntHit', 'fromStdClass' ), $stdClassHits );

		$versions = array();
		foreach( $hits as $hit ) {
			$vi = $this->fillVersionInfo( $hit, $rendition );
			array_unshift( $versions, $vi );
		}
		return $versions;
	}

	/**
	 * Retrieve a specific version from Elvis server, by provided alien id and Enterprise object version number.
	 *
	 * @param string $alienId Alien id of shadow object in Enterprise.
	 * @param string $version Enterprise object version.
	 * @param string $rendition File rendition to include in the version info.
	 * @return VersionInfo
	 */
	public function retrieveVersion( string $alienId, string $version, string $rendition ) : VersionInfo
	{
		$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		$assetVersion = self::getElvisAssetVersionNumber( $version );
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHit = $client->retrieveVersion( $assetId, $assetVersion );
		$hit = Elvis_DataClasses_EntHit::fromStdClass( $stdClassHit );
		return $this->fillVersionInfo( $hit, $rendition );
	}

	/**
	 * Promote a version on Elvis server, by provided alien id and Enterprise object version number.
	 *
	 * @param string $alienId Alien id of shadow object in Enterprise.
	 * @param string $version Enterprise object version.
	 */
	public function promoteVersion( string $alienId, string $version )
	{
		$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		$assetVersion = self::getElvisAssetVersionNumber( $version );
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$client->promoteVersion( $assetId, $assetVersion );
	}

	/**
	 * Compose a VersionInfo object based on provided hit and rendition.
	 *
	 * @param Elvis_DataClasses_EntHit $hit
	 * @param string $rendition
	 * @return VersionInfo
	 */
	private function fillVersionInfo( Elvis_DataClasses_EntHit $hit, string $rendition ) : VersionInfo
	{
		$metadataHandler = new Elvis_BizClasses_Metadata();
		$object = new Object();
		$metadataHandler->setHandlerName( 'VersionHandler' );
		$metadataHandler->read( $object, $hit->metadata );

		$vi = new VersionInfo();
		$vi->Version = $object->MetaData->WorkflowMetaData->Version;
		$vi->User = $object->MetaData->WorkflowMetaData->Modifier;
		$vi->Created = $object->MetaData->WorkflowMetaData->Modified;
		$vi->Comment = $object->MetaData->WorkflowMetaData->Comment;
		$vi->Object = $object->MetaData->BasicMetaData->Name;
		$vi->File = Elvis_BizClasses_Attachment::getAttachment( $hit, $rendition, 'FileUrl' );
		$vi->Slugline = '';

		return $vi;
	}

	/**
	 * Compose an Elvis asset version number, based on a given Enterprise object version number.
	 *
	 * @param string $version Enterprise object version number.
	 * @return string Elvis asset version
	 */
	public static function getElvisAssetVersionNumber( string $version ): string
	{
		require_once BASEDIR.'/config/config_elvis.php'; // ELVIS_ENTERPRISE_VERSIONPREFIX
		return substr($version, strlen(ELVIS_ENTERPRISE_VERSIONPREFIX));
	}

	/**
	 * Compose an Enterprise object version number, based on a given Elvis asset version number.
	 *
	 * @param string $version Elvis asset version number.
	 * @return string Enterprise object version number.
	 */
	public static function getEnterpriseObjectVersionNumber( string $version ): string
	{
		require_once BASEDIR.'/config/config_elvis.php'; // ELVIS_ENTERPRISE_VERSIONPREFIX
		return ELVIS_ENTERPRISE_VERSIONPREFIX.$version;
	}

	/**
	 * Validate if a given object version notation is valid (and possibly originates* from the integration).
	 *
	 * *) Only versions with leading "0." could originate from the integration. (So 0.5 does, but 1.5 does not.)
	 *
	 * @since 10.5.0
	 * @param string $version Enterprise object version number.
	 * @return bool Whether or not valid.
	 */
	public static function isValidEnterpriseObjectVersionNumber( string $version ): bool
	{
		require_once BASEDIR.'/config/config_elvis.php'; // ELVIS_ENTERPRISE_VERSIONPREFIX
		$valid = false;
		if( strpos( $version, ELVIS_ENTERPRISE_VERSIONPREFIX ) === 0 ) {
			$assetVersion = self::getElvisAssetVersionNumber( $version );
			$valid = self::isValidElvisAssetVersionNumber( $assetVersion );
		}
		return $valid;
	}

	/**
	 * Validate if a given asset version notation is valid.
	 *
	 * @since 10.5.0
	 * @param string $version Elvis asset version number.
	 * @return bool Whether or not valid.
	 */
	public static function isValidElvisAssetVersionNumber( string $version ): bool
	{
		return $version && intval( $version ) == $version;
	}
}