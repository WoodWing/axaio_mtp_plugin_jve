<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Create Object Relations workflow web service.
 * Called when an end-user places a file into a dossier or layout (typically using SC or CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjectRelations_EnterpriseConnector.class.php';

class Elvis_WflCreateObjectRelations extends WflCreateObjectRelations_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflCreateObjectRelationsRequest &$req )
	{
		require_once dirname( __FILE__ ).'/config.php';
		if( ELVIS_CREATE_COPY === 'Hard_Copy_To_Enterprise' ) {
			require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
			require_once dirname(__FILE__).'/Elvis_ContentSource.class.php';
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$user = BizSession::getShortUserName();
			$ticket = BizSession::getTicket();

			foreach( $req->Relations as $relation ) {
				$parent = $relation->Parent;
				$child = $relation->Child;

				if( ElvisUtils::isElvisId( $child ) ) {
					// Create copy of asset
					$object = new Object();
					$contentSource = new Elvis_ContentSource();
					$object = $contentSource->createCopyObject( $child, $object );

					// Add publication related metadata from parent
					$parentObject = BizObject::getObject( $parent, $user, false, 'none' );
					$object->MetaData->BasicMetaData->Publication = $parentObject->MetaData->BasicMetaData->Publication;
					$object->MetaData->BasicMetaData->Category = $parentObject->MetaData->BasicMetaData->Category;
					$object->MetaData->WorkflowMetaData->RouteTo = $user;

					// Create object in Enterprise
					require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
					$service = new WflCreateObjectsService();
					$request = new WflCreateObjectsRequest();
					$request->Ticket	= $ticket;
					$request->Objects	= array( $object );
					$request->Lock		= false;
					$request->AutoNaming = false;
					$response = $service->execute($request);
					$createdObject = $response->Objects[0];

					// Change Child in the relation to the newly created copy of the child
					$relation->Child = $createdObject->MetaData->BasicMetaData->ID;
					LogHandler::Log( 'ELVIS', 'DEBUG', 'Replaced child of relation from ' . $child . ' to ' . $relation->Child );
				}
			}
		}
	}

	final public function runAfter( WflCreateObjectRelationsRequest $req, WflCreateObjectRelationsResponse &$resp )
	{
		require_once dirname( __FILE__ ).'/config.php';
		if( ELVIS_CREATE_COPY !== 'Hard_Copy_To_Enterprise' ) {
			require_once dirname(__FILE__).'/logic/ElvisUpdateManager.class.php';
			require_once dirname(__FILE__).'/util/ElvisObjectUtils.class.php';
			require_once dirname(__FILE__).'/util/ElvisObjectRelationUtils.class.php';

			// Collect Elvis shadow ids
			$shadowIds = array();
			foreach( $resp->Relations as $relation ) {
				$shadowIds[] = $relation->Child;
			}
			$shadowIds = ElvisObjectUtils::filterElvisShadowObjects( $shadowIds );

			if( $shadowIds ) {
				// Collect layout ids for which we are creating shadow object relations
				$layoutIds = array();
				foreach( $resp->Relations as $relation ) {
					if( in_array( $relation->Child, $shadowIds) ) {
						$layoutIds[] = $relation->Parent;
					}
				}
				$layoutIds = ElvisObjectUtils::filterRelevantIdsFromObjectIds( $layoutIds );

				if( $layoutIds ) {
					// Collect shadow relations if any are found and send the updated placements to Elvis
					$newShadowRelations = ElvisObjectRelationUtils::getCurrentShadowRelationsFromObjectIds( $layoutIds );

					if( $newShadowRelations ) {
						ElvisUpdateManager::sendUpdateObjectsByIds( $layoutIds, $newShadowRelations );
					}
				}

				// For heavy debugging:
				//LogHandler::logPhpObject( $newShadowRelations, 'print_r', 'newShadowRelations' );
			}
		}
	}

	// Not called.
	final public function runOverruled( WflCreateObjectRelationsRequest $req )
	{
	}
}
