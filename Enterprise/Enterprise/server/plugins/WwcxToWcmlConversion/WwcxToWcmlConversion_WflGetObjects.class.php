<?php
/**
 * @since       v7.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjects_EnterpriseConnector.class.php';

class WwcxToWcmlConversion_WflGetObjects extends WflGetObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflGetObjectsRequest &$req ) {}

	final public function runAfter( WflGetObjectsRequest $req, WflGetObjectsResponse &$resp )
	{
		if( $req->Rendition == 'native' ) {
			require_once BASEDIR . '/server/dbclasses/DBTicket.class.php';
			$app = DBTicket::DBappticket( $req->Ticket );
			if( stristr($app, 'content station') || stristr($app, 'buildtest_wwcxtowcml') ) { // Opened from content station or build test
				$respObj = $resp->Objects[0];
				if( !is_null($respObj) && isset($respObj->MetaData->ContentMetaData->Format)
						&& $respObj->MetaData->ContentMetaData->Format == 'application/incopy' ) {
					try {
						LogHandler::Log( 'WwcxToWcmlConversion', 'DEBUG', 'Start article conversion from WWCX to WCML format.' );
						$this->convertArticle( $respObj );
						LogHandler::Log( 'WwcxToWcmlConversion', 'DEBUG', 'Article has been converted to WCML format successfully.' );
					} catch( BizException $e ) {
						// Now the core server has locked the article, but we want to fail.
						// So here we unlock the object and re-throw the exception.
						require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
						$objId = $respObj->MetaData->BasicMetaData->ID;
						$user = BizSession::getShortUserName();
						BizObject::unlockObject( $objId, $user, false );
						LogHandler::Log( 'WwcxToWcmlConversion', 'ERROR', $e->getMessage().' '.$e->getDetail() );
						throw $e;
					}
				}
			}
		}
	}

	final public function runOverruled( WflGetObjectsRequest $req ) {}

	/**
	 * Converts the article object file from WWCX to WCML.
	 *
	 * @param object $object The article object
	 * @throws BizException when conversion fails.
	 */
	private function convertArticle( $object )
	{
		// Get the article file content.
		$objId = $object->MetaData->BasicMetaData->ID;
		require_once dirname(__FILE__).'/WwcxToWcmlUtils.class.php';
		$wwcxToWcmlUtils = new WwcxToWcmlUtils();
		$fileContent = $wwcxToWcmlUtils->getContent( $object );
		if( is_null($fileContent) || empty($fileContent) ) { // no attachment, or empty attachment (should not happen)
			$objType = $object->MetaData->BasicMetaData->Type;
			$detail = 'Could not retrieve native file from DB for '.
				'object type "'.$objType.'" and object id "'.$objId.'".';
			throw new BizException( 'ERR_WWCX2WCML_FAILED', 'Server', $detail );
		}

		// For the variables below, there are two abbreviations used:
		// - Ent = workspace folder seen from Enterprise Server point of view (through WEBEDITDIR).
		// - Ids = workspace folder seen from InDesign Server point of view (through WEBEDITDIRIDSERV).
		
		// Create temporary workspace folder.
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$workspaceEnt = $wwcxToWcmlUtils->createWorkspaceFolder();
		$workspaceIds = $wwcxToWcmlUtils->getIdsWorkspaceFolder();
		if( !$workspaceEnt || !$workspaceIds ) {
			$detail = 'Could not create temporary workspace folder.';
			throw new BizException( 'ERR_WWCX2WCML_FAILED', 'Server', $detail );
		}
		
		// Determine article file paths (at workspace folder) to work with.
		$wwcxExt = MimeTypeHandler::mimeType2FileExt( 'application/incopy' );
		$wcmlExt = MimeTypeHandler::mimeType2FileExt( 'application/incopyicml' );
		$wwcxEntPath = $workspaceEnt . '/' . $objId . $wwcxExt;
		$wcmlEntPath = $workspaceEnt . '/' . $objId . $wcmlExt;
		$wwcxIdsPath = $workspaceIds . '/' . $objId . $wwcxExt;
		$wcmlIdsPath = $workspaceIds . '/' . $objId . $wcmlExt;
		LogHandler::Log( 'WwcxToWcmlConversion', 'DEBUG', 'Determined file paths at temporary workspace folder. '.
			'From Enterprise Server point of view: Using WWCX input file "'.$wwcxEntPath.'" and WCML output file "'.$wcmlEntPath.'". '.
			'From InDesign Server point of view: Using WWCX input file "'.$wwcxIdsPath.'" and WCML output file "'.$wcmlIdsPath.'". ' );

		// Save the WWCX article file at the temporary workspace.
		if( !$wwcxToWcmlUtils->writeFileToWorkspace( $fileContent, $wwcxEntPath ) ) { // should not happen
			$detail = 'Could not write native '.$object->MetaData->BasicMetaData->Type.
				' file to temporary workspace folder using path "'.$wwcxEntPath.'".';
			throw new BizException( 'ERR_WWCX2WCML_FAILED', 'Server', $detail );
		}

		// Convert WWCX article file to WCML format (at the temporary workspace).
		$this->callInDesignServerToConvertArticle( $wwcxIdsPath, $wcmlIdsPath );
		if( !file_exists($wcmlEntPath) ) {
			$detail = 'Converted WCML output file "'.$wcmlEntPath.'" not found.';
			throw new BizException( 'ERR_WWCX2WCML_FAILED', 'Server', $detail );
		}
		
		// Successfully converted to WCML format
		$wwcxToWcmlUtils->setContent( $wcmlEntPath, $object->Files[0], 'application/incopyicml' ); // WCML
		$object->MetaData->ContentMetaData->Format = 'application/incopyicml'; // WCML
		$wwcxToWcmlUtils->cleanupWorkSpace();
	}
	
	/**
	 * Requests IDS through SOAP to do the real WWCX to WCML article conversion.
	 * It passes a JavaScript file to IDS which manages the conversion process.
	 *
	 * @param string $wwcxIdsPath Input file in WWCX format (CS4-)
	 * @param string $wcmlIdsPath Output file in WCML format (CS5+)
	 * @throws BizException when article conversion failed.
	 */
	private function callInDesignServerToConvertArticle( $wwcxIdsPath, $wcmlIdsPath )
	{
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
		try {
			// Run javascript.js at InDesign Server, to convert the wwcx file to wcml
			BizInDesignServerJobs::createAndRunJob(
				file_get_contents( dirname(__FILE__).'/WwcxToWcml.js' ),
				array( 'wwcxPath' => $wwcxIdsPath, 'wcmlPath' => $wcmlIdsPath ),
				'WwcxToWcml conversion', null, null, // jobtype, object id, ids obj
				'8.0', // Hardcode doc version, this is the minimum version will use by the IDS to do conversion
				'11.0' // Max version is CC 2015. CC 2017 (v12) doesn't support the conversion anymore.
			);
		} catch( BizException $e ) {
			$detail = $e->getMessage().' ('.$e->getDetail().'). ';
			throw new BizException( 'ERR_WWCX2WCML_FAILED', 'Server', $detail );
		}
	}
}
