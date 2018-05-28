<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class VersionHandler
{
	/**
	 * Retrieve version info of an asset (from Elvis server) for a provided asset id.
	 *
	 * @param string $alienId Alien id of shadow object in Enterprise.
	 * @param string $rendition Rendition to include in the version info
	 * @return VersionInfo[]
	 */
	public function listVersions( $alienId, $rendition )
	{
		require_once __DIR__.'/../util/ElvisUtils.class.php';
		require_once __DIR__.'/../logic/ElvisContentSourceService.php';

		$elvisId = ElvisUtils::getElvisId( $alienId );
		$service = new ElvisContentSourceService();
		$hits = $service->listVersions( $elvisId ); // get asset versions
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
	public function retrieveVersion( $alienId, $version, $rendition )
	{
		require_once __DIR__.'/../util/ElvisUtils.class.php';
		require_once __DIR__.'/../logic/ElvisContentSourceService.php';

		$elvisId = ElvisUtils::getElvisId( $alienId );
		$versionNumber = ElvisUtils::getElvisVersionNumber( $version );
		$service = new ElvisContentSourceService();
		$hit = $service->retrieveVersion( $elvisId, $versionNumber );
		return $this->fillVersionInfo( $hit, $rendition );
	}

	/**
	 * Promote a version on Elvis server, by provided alien id and Enterprise object version number.
	 *
	 * @param string $alienId - alien id of shadow object in enterprise
	 * @param string $version
	 */
	public function promoteVersion( $alienId, $version )
	{
		require_once __DIR__.'/../util/ElvisUtils.class.php';
		require_once __DIR__.'/../logic/ElvisContentSourceService.php';

		$service = new ElvisContentSourceService();
		$elvisId = ElvisUtils::getElvisId( $alienId );
		$elvisVersion = ElvisUtils::getElvisVersionNumber( $version );
		$service->promoteVersion( $elvisId, $elvisVersion );
	}

	/**
	 * Compose a VersionInfo object based on provided hit and rendition.
	 *
	 * @param ElvisEntHit $hit
	 * @param string $rendition
	 * @return VersionInfo
	 */
	private function fillVersionInfo( $hit, $rendition )
	{
		require_once __DIR__.'/MetadataHandler.class.php';
		$metadataHandler = new MetadataHandler();
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