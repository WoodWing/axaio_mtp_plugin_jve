<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Normally, this would be the publishing class shipped with the Adobe DPS integration. 
 * However, it is NOT used. Instead, the AdobeDps2 server jobs for publishing operations. 
 * This class it there as a placeholder for the sake of the Publication Channel Maintenance page.
 */
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';

class AdobeDps2_PubPublishing extends PubPublishing_EnterpriseConnector
{
	const ERROR_DETAILS = 'Publishing operations are automatically triggered by workflow operations.';
	
	/**
	 * Publishes a dossier with contained objects (articles. images, etc.) to an external publishing system.
	 * The plugin is supposed to publish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PublishTarget $publishTarget
	 *
	 * @throws BizException
	 * @return array of PubField containing information from publishing system
	 */
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', self::ERROR_DETAILS );
	}

	/**
	 * Updates/republishes a published dossier with contained objects (articles. images, etc.) to an 
	 * external publishing system, using the $dossier->ExternalId to identify the dosier to the 
	 * publishing system. The plugin is supposed to update/republish the dossier and it's articles 
	 * and fill in some fields for reference.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PublishTarget $publishTarget
	 *
	 * @throws BizException
	 * @return array of PubField containing information from publishing system
	 */
	public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget ) 
	{
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', self::ERROR_DETAILS );
	}

	/**
	 * Removes/unpublishes a published dossier from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PublishTarget $publishTarget
	 *
	 * @throws BizException
	 * @return array of PubField containing information from publishing system
	 */
	public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget ) 
	{
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', self::ERROR_DETAILS );
	}

	/**
	 * Requests fieldvalues from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array $objectsInDossier Array of Object.
	 * @param PublishTarget $publishTarget
	 * 
	 * @return array of PubField containing information from publishing system
	 */
	public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget ) 
	{
	}

	/**
	 * Requests dossier URL from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array $objectsInDossier Array of Object.
	 * @param PublishTarget $publishTarget
	 * 
	 * @return string URL to published item
	 */
	public function getDossierURL( $dossier, $objectsInDossier, $publishTarget ) 
	{
	}

	/**
	 * Previews a dossier with contained objects (articles. images, etc.) to an external publishing 
	 * system. The plugin is supposed to send the dossier and it's articles to the publishing system 
	 * and fill in the URL field for reference.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PublishTarget $publishTarget
	 *
	 * @throws BizException
	 * @return array of Fields containing information from Publishing system
	 */
	public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{		
		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Not implemented.' );
	}

	/**
	 * Allows connector to provide other Publish System name than the display name of the server plug-in
	 * which is taken by default. This is name is shown at admin pages, such as the Channel Maintenance 
	 * page. This function is NOT abstract since it is introduced later. When function is not implemented 
	 * by the connector or when an empty string is returned, the server plug-in name is taken instead.
	 *
	 * @return string
	 */
	public function getPublishSystemDisplayName() 
	{
		require_once dirname(__FILE__).'/config.php'; // DPS2_PLUGIN_DISPLAYNAME
		return DPS2_PLUGIN_DISPLAYNAME;
	}
}
