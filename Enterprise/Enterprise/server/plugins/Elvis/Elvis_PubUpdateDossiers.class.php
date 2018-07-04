<?php
/**
 * Updates the Published Date property for image assets in Elvis when user has republished a Publish Form.
 * This is done for all the shadow images placed on the form.
 *
 * @since      10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/pub/PubUpdateDossiers_EnterpriseConnector.class.php';

class Elvis_PubUpdateDossiers extends PubUpdateDossiers_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called.
	final public function runBefore( PubUpdateDossiersRequest &$req )
	{
	}

	/**
	 * @inheritdoc
	 */
	final public function runAfter( PubUpdateDossiersRequest $req, PubUpdateDossiersResponse &$resp )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading
		try {
			Elvis_BizClasses_Object::updatePublisFormPlacementsForPublishDossierOperation( $resp->PublishedDossiers );
		} catch( BizException $e ) {
			// ignore errors on Elvis updates
		}
	}

	/**
	 * @inheritdoc
	 */
	final public function onError( PubUpdateDossiersRequest $req, BizException $e )
	{
	}
	
	// Not called.
	final public function runOverruled( PubUpdateDossiersRequest $req )
	{
	}
}
