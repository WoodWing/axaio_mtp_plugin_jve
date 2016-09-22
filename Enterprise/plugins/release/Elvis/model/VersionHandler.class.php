<?php

require_once dirname(__FILE__) . '/../util/ElvisUtils.class.php';
require_once dirname(__FILE__) . '/../logic/ElvisContentSourceService.php';

class VersionHandler
{
	/**
	 * Returns array of filled VersionInfo instances for corresponding Elvis versions for provided elvis id
	 *
	 * @param $alienId  string - alien id of shadow object in enterprise
	 * @param $rendition Rendition to include in the version info
	 * @return array of VersionInfo objects
	 */
	public function listVersions($alienId, $rendition)
	{
		//restore elvis id
		$elvisId = ElvisUtils::getElvisId($alienId);

		$service = new ElvisContentSourceService();
		// get asset versions
		$hits = $service->listVersions($elvisId);
		$versions = array();
		foreach ($hits as $hit) {
			$vi = $this->fillVersionInfo($hit, $rendition);
			array_unshift($versions, $vi);
		}
		return $versions;

	}

	/**
	 * Retrieves specific version from Elvis server, by provided alien id and enterprise version number
	 * @param string $alienId - alien id of shadow object in enterprise
	 * @param string $version string - enterprise version
	 * @param string $rendition - rendition to be filled in VersionInfo
	 * @return VersionInfo
	 */
	public function retrieveVersion($alienId, $version, $rendition)
	{
		$elvisId = ElvisUtils::getElvisId($alienId);
		$versionNumber = ElvisUtils::getElvisVersionNumber($version);

		$service = new ElvisContentSourceService();
		$hit = $service->retrieveVersion($elvisId, $versionNumber);

		return $this->fillVersionInfo($hit, $rendition);
	}

	/**
	 * Promotes version on Elvis server, by provided alien id and enterprise version number
	 *
	 * @param $alienId - alien id of shadow object in enterprise
	 * @param $version
	 */
	public function promoteVersion($alienId, $version)
	{
		$service = new ElvisContentSourceService();
		$elvisId = ElvisUtils::getElvisId($alienId);
		$elvisVersion = ElvisUtils::getElvisVersionNumber($version);

		//TODO check if we need to handle cases when promotion failed on Elvis side
		$service->promoteVersion($elvisId, $elvisVersion);
	}

	/**
	 * Utility.
	 * Fills in VersionInfo object base on provided hit and rendition
	 *
	 * @param $hit
	 * @param $rendition
	 * @return VersionInfo
	 */
	private function fillVersionInfo( $hit, $rendition )
	{
		require_once dirname(__FILE__).'/MetadataHandler.class.php';
		$metadataHandler = new MetadataHandler();
		$object = new Object();
		$metadataHandler->read($object, $hit->metadata);

		$vi = new VersionInfo();
		$vi->Version = $object->MetaData->WorkflowMetaData->Version;
		$vi->User = $object->MetaData->WorkflowMetaData->Modifier;
		$vi->Created = $object->MetaData->WorkflowMetaData->Modified;
		$vi->Comment = $object->MetaData->WorkflowMetaData->Comment;
		$vi->Object = $object->MetaData->BasicMetaData->Name;
		$vi->File = ElvisUtils::getAttachment( $hit, $rendition, false );

		return $vi;
	}

}