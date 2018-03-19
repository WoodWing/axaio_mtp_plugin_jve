<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * This class makes it easy to setup a brand with categories, statuses, channels, issues and editions.
 * Al it requires is a home brewed data structure (to be provided) to specify the brand setup.
 * Functions are available to retrieve the DB ids of those created admin entities.
 * It offers a tear down function to delete the entire brand setup including all its admin entities.
 */

require_once BASEDIR.'/server/wwtest/testsuite/Setup/AbstractConfig.class.php';

class WW_TestSuite_Setup_PublicationConfig extends WW_TestSuite_Setup_AbstractConfig
{
	/** @var array */
	private $publicationNameIdMap;
	/** @var array */
	private $pubChannelNameIdMap;
	/** @var array */
	private $issueNameIdMap;
	/** @var array */
	private $editionNameIdMap;
	/** @var array */
	private $categoryNameIdMap;
	/** @var array */
	private $statusNameIdMap;

	/**
	 * @inheritdoc
	 */
	public function setupTestData()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

		if( isset( $this->config->Publications ) ) {
			foreach( $this->config->Publications as $publicationConfig ) {
				$publication = new AdmPublication();
				$this->copyConfigPropertiesToAdminClass( $publicationConfig, $publication );
				$publication->PubChannels = null; // handled separately below
				$pubId = $this->testSuiteUtils->createNewPublication( $this->testCase, $this->ticket, $publication );
				$this->publicationNameIdMap[ $publicationConfig->Name ] = $pubId;

				if( isset( $publicationConfig->PubChannels ) ) {
					foreach( $publicationConfig->PubChannels as $pubChannelConfig ) {
						$pubChannel = new AdmPubChannel();
						$this->copyConfigPropertiesToAdminClass( $pubChannelConfig, $pubChannel );
						$pubChannel->Issues = null; // handled separately below
						$pubChannel->Editions = null; // handled separately below
						$response = $this->testSuiteUtils->createNewPubChannel( $this->testCase, $this->ticket, $pubId, $pubChannel );
						$pubChannelId = $response->PubChannels[0]->Id;
						$this->pubChannelNameIdMap[ $publicationConfig->Name ][ $pubChannelConfig->Name ] = $pubChannelId;

						if( isset( $pubChannelConfig->Issues ) ) {
							foreach( $pubChannelConfig->Issues as $issueConfig ) {
								$issue = new AdmIssue();
								$this->copyConfigPropertiesToAdminClass( $issueConfig, $issue );
								$response = $this->testSuiteUtils->createNewIssue( $this->testCase, $this->ticket, $pubId, $pubChannelId, $issue );
								$issueId = $response->Issues[0]->Id;
								$this->issueNameIdMap[ $publicationConfig->Name ][ $pubChannelConfig->Name ][ $issueConfig->Name ] = $issueId;
							}
						}

						if( isset( $pubChannelConfig->Editions ) ) {
							foreach( $pubChannelConfig->Editions as $editionConfig ) {
								$edition = new AdmEdition();
								$this->copyConfigPropertiesToAdminClass( $editionConfig, $edition );
								$newEdition = $this->testSuiteUtils->createNewEdition( $this->testCase, $this->ticket, $pubId, $pubChannelId, 0, $edition );
								$editionId = $newEdition->Id;
								$this->editionNameIdMap[ $publicationConfig->Name ][ $pubChannelConfig->Name ][ $editionConfig->Name ] = $editionId;
							}
						}
					}
				}

				if( isset( $publicationConfig->States ) ) {
					foreach( $publicationConfig->States as $statusConfig ) {
						$status = new AdmStatus();
						$this->copyConfigPropertiesToAdminClass( $statusConfig, $status );
						$statusId = $this->testSuiteUtils->createNewStatus( $this->testCase, $this->ticket, $pubId, 0, $status );
						$this->statusNameIdMap[ $publicationConfig->Name ][ $statusConfig->Name ] = $statusId;
					}
				}

				if( isset( $publicationConfig->Categories ) ) {
					foreach( $publicationConfig->Categories as $categoryConfig ) {
						$category = new AdmSection();
						$this->copyConfigPropertiesToAdminClass( $categoryConfig, $category );
						$categoryId = $this->testSuiteUtils->createNewSection( $this->testCase, $this->ticket, $pubId, 0, $category );
						$this->categoryNameIdMap[ $publicationConfig->Name ][ $categoryConfig->Name ] = $categoryId;
					}
				}
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function teardownTestData()
	{
		foreach( $this->publicationNameIdMap as $publicationConfigName => $pubId ) {
			foreach( $this->categoryNameIdMap[ $publicationConfigName ] as $categoryId ) {
				$this->testSuiteUtils->deleteSections( $this->testCase, $this->ticket, $pubId, 0, array( $categoryId ) );
			}
			foreach( $this->statusNameIdMap[ $publicationConfigName ] as $statusId ) {
				$this->testSuiteUtils->deleteStatuses( $this->testCase, $this->ticket, array( $statusId ) );
			}
			foreach( $this->pubChannelNameIdMap[ $publicationConfigName ] as $pubChannelConfigName => $pubChannelId ) {
				if( isset( $this->editionNameIdMap[ $publicationConfigName ][ $pubChannelConfigName ] ) ) {
					foreach( $this->editionNameIdMap[ $publicationConfigName ][ $pubChannelConfigName ] as $editionId ) {
						$this->testSuiteUtils->removeEdition( $this->testCase, $this->ticket, $pubId, $pubChannelId, 0, $editionId );
					}
				}
				foreach( $this->issueNameIdMap[ $publicationConfigName ][ $pubChannelConfigName ] as $issueId ) {
					$this->testSuiteUtils->removeIssue( $this->testCase, $this->ticket, $pubId, $issueId );
				}
				$this->testSuiteUtils->removePubChannel( $this->testCase, $this->ticket, $pubId, $pubChannelId );
			}
			$this->testSuiteUtils->deletePublication( $this->testCase, $this->ticket, $pubId );
		}
	}

	/**
	 * @param string $publicationConfigName
	 * @return integer
	 */
	public function getPublicationId( $publicationConfigName )
	{
		return $this->publicationNameIdMap[ $publicationConfigName ];
	}

	/**
	 * @param string $publicationConfigName
	 * @return string
	 */
	public function getPublicationName( $publicationConfigName )
	{
		return $this->replaceTimeStampPlaceholder( $publicationConfigName );
	}

	/**
	 * @param string $publicationConfigName
	 * @param string $pubChannelConfigName
	 * @return integer
	 */
	public function getPubChannelId( $publicationConfigName, $pubChannelConfigName )
	{
		return $this->pubChannelNameIdMap[ $publicationConfigName ][ $pubChannelConfigName ];
	}

	/**
	 * @param string $pubChannelConfigName
	 * @return string
	 */
	public function getPubChannelName( $pubChannelConfigName )
	{
		return $this->replaceTimeStampPlaceholder( $pubChannelConfigName );
	}

	/**
	 * @param string $publicationConfigName
	 * @param string $categoryConfigName
	 * @return integer
	 */
	public function getCategoryId( $publicationConfigName, $categoryConfigName )
	{
		return $this->categoryNameIdMap[ $publicationConfigName ][ $categoryConfigName ];
	}

	/**
	 * @param string $categoryConfigName
	 * @return string
	 */
	public function getCategoryName( $categoryConfigName )
	{
		return $this->replaceTimeStampPlaceholder( $categoryConfigName );
	}

	/**
	 * @param string $publicationConfigName
	 * @param string $statusConfigName
	 * @return integer
	 */
	public function getStatusId( $publicationConfigName, $statusConfigName )
	{
		return $this->statusNameIdMap[ $publicationConfigName ][ $statusConfigName ];
	}

	/**
	 * @param string $statusConfigName
	 * @return string
	 */
	public function getStatusName( $statusConfigName )
	{
		return $this->replaceTimeStampPlaceholder( $statusConfigName );
	}

	/**
	 * @param string $publicationConfigName
	 * @param string $pubChannelConfigName
	 * @param string $issueConfigName
	 * @return integer
	 */
	public function getIssueId( $publicationConfigName, $pubChannelConfigName, $issueConfigName )
	{
		return $this->issueNameIdMap[ $publicationConfigName ][ $pubChannelConfigName ][ $issueConfigName ];
	}

	/**
	 * @param string $issueConfigName
	 * @return string
	 */
	public function getIssueName( $issueConfigName )
	{
		return $this->replaceTimeStampPlaceholder( $issueConfigName );
	}

	/**
	 * @param string $publicationConfigName
	 * @param string $pubChannelConfigName
	 * @param string $editionConfigName
	 * @return integer
	 */
	public function getEditionId( $publicationConfigName, $pubChannelConfigName, $editionConfigName )
	{
		return $this->editionNameIdMap[ $publicationConfigName ][ $pubChannelConfigName ][ $editionConfigName ];
	}

	/**
	 * @param string $editionConfigName
	 * @return string
	 */
	public function getEditionName( $editionConfigName )
	{
		return $this->replaceTimeStampPlaceholder( $editionConfigName );
	}
}
