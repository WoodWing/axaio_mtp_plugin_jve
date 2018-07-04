<?php
/**
 * Hooks into the Create Object Relations workflow web service.
 * Called when an end-user places a file into a dossier or layout (typically using SC or CS).
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjectRelations_EnterpriseConnector.class.php';

class Elvis_WflCreateObjectRelations extends WflCreateObjectRelations_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflCreateObjectRelationsRequest &$req )
	{
		require_once __DIR__.'/config.php'; // auto-loading
		if( ELVIS_CREATE_COPY === 'Hard_Copy_To_Enterprise' ) {
			require_once __DIR__.'/Elvis_ContentSource.class.php';
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$user = BizSession::getShortUserName();
			$ticket = BizSession::getTicket();

			foreach( $req->Relations as $relation ) {
				$parent = $relation->Parent;
				$child = $relation->Child;

				if( Elvis_BizClasses_AssetId::isElvisAssetId( $child ) ) {
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
		require_once __DIR__.'/config.php'; // auto-loading
		if( ELVIS_CREATE_COPY !== 'Hard_Copy_To_Enterprise' ) {

			// Collect Elvis shadow ids
			$shadowIds = array();
			foreach( $resp->Relations as $relation ) {
				$shadowIds[] = $relation->Child;
			}
			$shadowIds = Elvis_BizClasses_Object::filterElvisShadowObjects( $shadowIds );

			if( $shadowIds ) {
				// Collect layout ids for which we are creating shadow object relations
				$layoutIds = array();
				foreach( $resp->Relations as $relation ) {
					if( in_array( $relation->Child, $shadowIds) ) {
						$layoutIds[] = $relation->Parent;
					}
				}
				$layoutIds = Elvis_BizClasses_Object::filterRelevantIdsFromObjectIds( $layoutIds );

				if( $layoutIds ) {
					// Collect shadow relations if any are found and send the updated placements to Elvis
					$newShadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjectIds( $layoutIds );

					if( $newShadowRelations ) {
						Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelationsByObjectIds( $layoutIds, $newShadowRelations );
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
