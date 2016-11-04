<?php
/**
 * @package    Enterprise
 * @subpackage Elvis
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * This connector is called by core server when the user is about to create a brand. It populates the custom
 * "Production Zone" property to the Brand Maintenance page under the "Elvis" section with the default value
 * configured for the ELVIS_PUBLISH_ZONE setting that is taken from Elvis/config.php.
 */

require_once BASEDIR . '/server/interfaces/services/adm/AdmCreatePublications_EnterpriseConnector.class.php';

class Elvis_AdmCreatePublications extends AdmCreatePublications_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	final public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}

	/**
	 * @inheritdoc
	 */
	final public function getRunMode()
	{
		return self::RUNMODE_BEFORE;
	}

	/**
	 * @inheritdoc
	 */
	final public function runBefore( AdmCreatePublicationsRequest &$req )
	{
		require_once __DIR__.'/config.php'; // ELVIS_PUBLISH_ZONE
		if( defined( 'ELVIS_PUBLISH_ZONE' ) && ELVIS_PUBLISH_ZONE ) {
			require_once __DIR__.'/util/ElvisBrandAdminConfig.class.php';
			if( $req->Publications ) foreach( $req->Publications as $publication ) {
				if( $publication->ExtraMetaData ) {
					$productionZone = ElvisBrandAdminConfig::getProductionZone( $publication );
					if( !$productionZone ) {
						ElvisBrandAdminConfig::setProductionZone( $publication, ELVIS_PUBLISH_ZONE );
					}
				}
			}
		}
	}

	// Not called.
	final public function runAfter( AdmCreatePublicationsRequest $req, AdmCreatePublicationsResponse &$resp )
	{
	}

	/**
	 * @inheritdoc
	 */
	final public function onError( AdmCreatePublicationsRequest $req, BizException $e )
	{
	}

	// Not called.
	final public function runOverruled( AdmCreatePublicationsRequest $req )
	{
	}
}
