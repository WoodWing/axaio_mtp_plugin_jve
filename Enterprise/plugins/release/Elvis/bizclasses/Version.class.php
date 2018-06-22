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
		require_once __DIR__.'/../util/ElvisUtils.class.php';
		require_once __DIR__.'/../model/ElvisEntHit.php';

		$assetId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHits = $client->listVersions( $assetId );
		$hits = array_map( array( 'ElvisEntHit', 'fromStdClass' ), $stdClassHits );

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
		require_once __DIR__.'/../util/ElvisUtils.class.php';
		require_once __DIR__.'/../model/ElvisEntHit.php';

		$assetId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		$assetVersion = ElvisUtils::getElvisVersionNumber( $version );
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHit = $client->retrieveVersion( $assetId, $assetVersion );
		$hit = ElvisEntHit::fromStdClass( $stdClassHit );
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
		require_once __DIR__.'/../util/ElvisUtils.class.php';

		$assetId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		$assetVersion = ElvisUtils::getElvisVersionNumber( $version );
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$client->promoteVersion( $assetId, $assetVersion );
	}

	/**
	 * Compose a VersionInfo object based on provided hit and rendition.
	 *
	 * @param ElvisEntHit $hit
	 * @param string $rendition
	 * @return VersionInfo
	 */
	private function fillVersionInfo( ElvisEntHit $hit, string $rendition ) : VersionInfo
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
		$vi->File = ElvisUtils::getAttachment( $hit, $rendition, 'FileUrl' );
		$vi->Slugline = '';

		return $vi;
	}
}