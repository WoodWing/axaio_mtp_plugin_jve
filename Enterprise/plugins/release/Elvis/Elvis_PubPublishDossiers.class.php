<?php
/**
 * @since      10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Updates the Published Date property for image assets in Elvis when user has published a Publish Form.
 * This is done for all the shadow images placed on the form.
 */

require_once BASEDIR . '/server/interfaces/services/pub/PubPublishDossiers_EnterpriseConnector.class.php';

class Elvis_PubPublishDossiers extends PubPublishDossiers_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called.
	final public function runBefore( PubPublishDossiersRequest &$req )
	{
	}

	/**
	 * @inheritdoc
	 */
	final public function runAfter( PubPublishDossiersRequest $req, PubPublishDossiersResponse &$resp )
	{
		require_once __DIR__.'/config.php'; // auto-loading
		try {
			Elvis_BizClasses_Object::updatePublisFormPlacementsForPublishDossierOperation( $resp->PublishedDossiers );
		} catch( BizException $e ) {
			// ignore errors on Elvis updates
		}
	}

	/**
	 * @inheritdoc
	 */
	final public function onError( PubPublishDossiersRequest $req, BizException $e )
	{
	}
	
	// Not called.
	final public function runOverruled( PubPublishDossiersRequest $req )
	{
	}
}
