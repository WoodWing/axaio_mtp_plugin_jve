<?php
/**
 * @since 		v9.4
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class with functions called to publish.
 * This is a fake connector used only for BuildTest purposes.
 * The real publishing doesn't take place but instead dummy data are returned.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';

class PublishingTest_PubPublishing extends PubPublishing_EnterpriseConnector
{
	private $errors = array();
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * Publishes a dossier with contained objects (articles. images, etc.) to Drupal.
	 * The plugin is supposed to publish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @throws BizException Throws a BizException if the node cannot be published.
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @return array of PubFields containing information from Drupal
	 */
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		$pubFields = array();
		$pubFields[] = new PubField('URL','string', array('http://dummy.net'));
		return $pubFields;
	}

	/**
	 * Updates a published Dossier to Drupal.
	 *
	 * Updates/republishes a published dossier with contained objects (articles. images, etc.) to Drupal using the
	 * $dossier->ExternalId to identify the dosier to Drupal. The plugin is supposed to update/republish the dossier
	 * and it's articles and fill in some fields for reference.
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @return PubField[] Array containing information from Drupal.
	 */
	public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		$pubFields = array();
		$pubFields[] = new PubField('URL','string', array('http://dummy.net'));
		return $pubFields;
	}

	/**
	 * Unpublishes and removes a published dossier from Drupal.
	 *
	 * The $dossier->ExternalId is used to identify the dosier in Drupal.
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @return array of PubFields containing information from Drupal
	 */
	public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget )
	{
		return array(); // Return an empty array so the Dossier is saved.
	}

	/**
	 * Previews a Dossier.
	 *
	 * Previews a Dossier with contained objects (articles. images, etc.) to an external publishing system.
	 * The plugin is supposed to send the dossier and it's articles to the publishing system and fill in the URL field
	 * for reference.
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @return PubField[] containing information from Publishing system.
	 */
	public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		$pubFields = array();
		$pubFields[] = new PubField('URL','string', array('http://dummy.net'));
		return $pubFields;
	}

	/**
	 * Requests Publish Fields from Drupal.
	 *
	 * Uses the dossier->ExternalId to identify the dossier in Drupal. Called by the core (BizPublishing.class.php).
	 *
	 * @param Object $dossier
	 * @param Object[] $objectsindossier
	 * @param PubPublishTarget $publishTarget
	 * @return PubField[] Array containing information gathered from Drupal.
	 */
	public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget )
	{
		$result = array();
		$result[] = new PubField( 'key', 'type', 'value' );

		return $result;
	}

	/**
	 * Requests the Dossier URL from Drupal.
	 *
	 * Uses the $dossier->ExternalId to identify the dosier to Drupal. (Called by the core, BizPublishing.class.php)
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PubPublishTarget $publishTarget
	 * @return string The url to the content.
	 */
	public function getDossierURL( $dossier, $objectsInDossier, $publishTarget )
	{
		$url = 'http://dummy.net?node=xx';
		return $url;
	}

}
