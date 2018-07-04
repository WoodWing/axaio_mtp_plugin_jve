<?php
/**
 * Updates the Published Date property for image assets in Elvis when user has unpublished a Publish Form.
 * This is done for all the shadow images placed on the form.
 * 
 * @since      10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/pub/PubUnPublishDossiers_EnterpriseConnector.class.php';

class Elvis_PubUnPublishDossiers extends PubUnPublishDossiers_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called.
	final public function runBefore( PubUnPublishDossiersRequest &$req )
	{
	}

	/**
	 * @inheritdoc
	 */
	final public function runAfter( PubUnPublishDossiersRequest $req, PubUnPublishDossiersResponse &$resp )
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
	final public function onError( PubUnPublishDossiersRequest $req, BizException $e )
	{
	}
	
	// Not called.
	final public function runOverruled( PubUnPublishDossiersRequest $req )
	{
	}
}
