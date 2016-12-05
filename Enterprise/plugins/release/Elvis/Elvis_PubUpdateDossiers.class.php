<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Updates the Published Date property for image assets in Elvis when user has republished a Publish Form.
 * This is done for all the shadow images placed on the form.
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
		try {
			require_once __DIR__.'/util/ElvisObjectUtils.class.php';
			ElvisObjectUtils::updatePublisFormPlacementsForPublishDossierOperation( $resp->PublishedDossiers );
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
