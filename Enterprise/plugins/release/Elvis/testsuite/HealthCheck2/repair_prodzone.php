<?php
/**
 * @since      10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Repairs the Production Zone properties for all given Publications.
 *
 * It fills in the default value configured in VALIDATE_DEFINE_MANDATORY for all given Publications
 * that do not have the Production Zone property set (empty).
 *
 * This is typically useful after DB migration from 10.1.0 (or older) to 10.1.1 (or newer) which introduces this feature.
 * The Health Check will detect this and provide a URL to this module and passes on the Publication ids to make sure
 * no accidental changes are made.
 */
if( file_exists('../../../../../config/config.php') ) {
	require_once '../../../../../config/config.php';
} else { // fall back at symbolic link (repository location of server plug-in)
	require_once '../../../../../Enterprise/config/config.php';
}

// Obtain ticket of admin user from cookie. Redirect if no valid ticket.
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
$ticket = checkSecure();

// Get the Publication ids from the URL.
$pubIds = isset($_GET['ids']) ? $_GET['ids'] : array();
if( $pubIds ) {
	$pubIds = explode( ',', $pubIds );
	$pubIds = array_map( 'trim', $pubIds );
	$pubIds = array_map( 'intval', $pubIds ); // cast all ids to integers to block SQL injection
	$pubIds = array_diff( $pubIds, array( 0 ) ); // paranoid filter; remove zeros
}

// Repair the Production Zone property for the Publications.
try {
	if( $pubIds ) {
		$app = new Elvis_RepairProductionZoneApp();
		$app->repairPublication( $ticket, $pubIds );
		echo 'Update complete.';
	} else {
		echo 'ERROR: No Brands found to update.';
	}
} catch( BizException $e ) {
	echo 'ERROR: '.$e->getMessage();
}

class Elvis_RepairProductionZoneApp
{
	/**
	 * Repairs the Production Zone property of given Publications in the DB. See module header.
	 *
	 * @param string $ticket
	 * @param integer[] $pubIds
	 * @throws BizException
	 */
	public function repairPublication( $ticket, $pubIds )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';
		require_once __DIR__.'/../../config.php'; // DEFAULT_ELVIS_PRODUCTION_ZONE

		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'Publication' );
		$pubsToFix = $this->getPublications( $ticket, $pubIds );
		if( $pubsToFix ) foreach( $pubsToFix as $pubToFix ) {
			$productionZone = Elvis_BizClasses_BrandAdminConfig::getProductionZone( $pubToFix );
			if( is_null( $productionZone ) ) {
				Elvis_BizClasses_BrandAdminConfig::addProductionZone( $pubToFix, DEFAULT_ELVIS_PRODUCTION_ZONE );
			} elseif( !$productionZone ) {
				Elvis_BizClasses_BrandAdminConfig::setProductionZone( $pubToFix, DEFAULT_ELVIS_PRODUCTION_ZONE );
			}
		}
		$this->savePublications( $ticket, $pubsToFix );
	}

	/**
	 * Retrieves requested Publications from the DB.
	 *
	 * @param string $ticket
	 * @param integer[] $pubIds
	 * @return AdmPublication[]
	 * @throws BizException
	 */
	private function getPublications( $ticket, $pubIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->PublicationIds = $pubIds;

		$service = new AdmGetPublicationsService();
		$response = $service->execute( $request );
		return $response->Publications;
	}

	/**
	 * Updates given Publications in the DB.
	 *
	 * @param string $ticket
	 * @param AdmPublication[] $publications
	 * @throws BizException
	 */
	private function savePublications( $ticket, $publications )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyPublicationsService.class.php';
		$request = new AdmModifyPublicationsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->Publications = $publications;

		$service = new AdmModifyPublicationsService();
		$service->execute( $request );
	}
}