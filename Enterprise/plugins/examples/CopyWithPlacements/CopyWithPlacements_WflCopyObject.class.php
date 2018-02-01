<?php
/****************************************************************************
 * Copyright 2015 WoodWing Software BV
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 ****************************************************************************/

require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObject_EnterpriseConnector.class.php';

class CopyWithPlacements_WflCopyObject extends WflCopyObject_EnterpriseConnector
{
	final public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}

	final public function getRunMode()
	{
		return self::RUNMODE_AFTER;
	}

	final public function runAfter( WflCopyObjectRequest $req, WflCopyObjectResponse &$resp )
	{
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'CopyWithPlacements', 'DEBUG', 'Called: CopyWithPlacements_WflCopyObject->runAfter()' );
		}
		require_once dirname( __FILE__ ).'/config.php';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';

		$dbDriver = DBDriverFactory::gen();

		$originalId = $req->SourceID;
		$originalLayout = BizObject::getObject( $originalId, null, false, 'none' );
		$originalMetadata = $originalLayout->MetaData;

		if( $originalMetadata->BasicMetaData->Type != 'Layout' ) {
			return;
		}

		$deepCopy = null;
		foreach( $req->MetaData->ExtraMetaData as $extraMetaData ) {
			if( $extraMetaData->Property == 'C_CWP_DEEPCOPY' ) {
				$deepCopy = ( $extraMetaData->Values[0] == '1' );
				break;
			}
		}

		if( !$deepCopy ) {
			return;
		}

		$newLayout = BizObject::getObject( $resp->MetaData->BasicMetaData->ID, null, false, 'none' );
		$newMetaData = $newLayout->MetaData;
		$newLayoutId = $resp->MetaData->BasicMetaData->ID;

		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$user = BizSession::getShortUserName();
		$pubs = BizPublication::getPublications( $user, 'full', $req->MetaData->BasicMetaData->Publication->Id );
		$articleToStatus = Array();
		if( $pubs ) foreach( $pubs as $pub ) {
			if( $pub->States ) foreach( $pub->States as $state ) {
				if( $state->Type == 'Article' ) {
					$articleToStatus[ $state->Name ] = $state;
				}
			}
		}

		LogHandler::Log( 'CopyWithPlacements', 'INFO', "CopyWithPlacements of layout/dossier $originalId to $newLayoutId" );
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'CopyWithPlacements', 'DEBUG', print_r( $newLayout, true ) );
		}

		$relations = BizRelation::getObjectRelations( $originalId );
		$newObjRelation = array();
		$oldObjRelation = array();

		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "Original relations: ".print_r( $relations, true ) );
		}

		// now process all existing relations in the source layout
		if( $relations ) foreach( $relations as $relation ) {
			$childId = $relation->Child;
			$obj = BizObject::getObject( $childId, null, false, 'none' );

			// only copy Article objects, leave other objects by default
			if( $obj->MetaData->BasicMetaData->Type == 'Article' ) {
				LogHandler::Log( 'CopyWithPlacements', 'INFO', "Copy article ".$obj->MetaData->BasicMetaData->Name );

				// put article relation in the $oldObjRelation set (to be reset)
				$parentId = $relation->Parent;
				$childType = $relation->Type;
				$childPlacements = $relation->Placements;
				$childParentVersion = $relation->ParentVersion;
				$childVersion = $relation->ChildVersion;
				$childGeometry = $relation->Geometry;
				$oldObjRelation[] = new Relation( $resp->MetaData->BasicMetaData->ID, $childId, $childType, $childPlacements, $childParentVersion, $childVersion, $childGeometry );

				// now create a copy of the article
				// id of object to be copied
				$objId = $obj->MetaData->BasicMetaData->ID;

				//prepare meta data for article copy
				$childMeta = $obj->MetaData;

				$childMeta->WorkflowMetaData->State = $articleToStatus[ $childMeta->WorkflowMetaData->State->Name ];

				// set Brand and Category of article to the target of the layout
				$childMeta->BasicMetaData->Publication = $newMetaData->BasicMetaData->Publication;
				$childMeta->BasicMetaData->Category = $newMetaData->BasicMetaData->Category;

				//change child name
				$newName = $obj->MetaData->BasicMetaData->Name;
				$childMeta->BasicMetaData->Name = BizResources::localize( 'ACT_COPY_OF' ).' '.$newName;

				// Do not copy target meta data, this will make it possible
				// to copy layout + articles to a different brand

				// clear deadline
				$obj->MetaData->WorkflowMetaData->Deadline = "";

				// clear target ID
				unset( $childMeta->BasicMetaData->ID );

				// Copy article into new article
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				$copyChild = BizObject::copyObject( $objId, $childMeta, $user, null, null );

				$newChildId = $copyChild->MetaData->BasicMetaData->ID;

				// if the copy succeeded, add it as a new relation to the layout
				if( $newChildId ) {
					foreach( $relations as $childRelation ) {
						$childId = $childRelation->Child;
						if( $childId == $objId ) { // Only copy the current child relation !!!
							$guidMapping = array();

							// unfortunately we need a bit of sql here, to set the guids right
							// we have to map from original guid to new guid
							// have to do this because order of components is not guaranteed by copyto service
							// (this should be improved in 8.2, but nevertheless...

							$sql1 = "select `guid` from `smart_elements` where `objid`=".$newChildId.' order by `version` asc';
							$sth1 = $dbDriver->query( $sql1 );

							$sql2 = "select `guid` from `smart_elements` where `objid`=".$objId.' order by `version` asc';
							$sth2 = $dbDriver->query( $sql2 );

							while( $row1 = $dbDriver->fetch( $sth1 ) ) {
								$row2 = $dbDriver->fetch( $sth2 );
								$guidMapping[ $row2['guid'] ] = $row1['guid'];
							}

							if( LogHandler::debugMode() ) {
								LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "GUID mapping (old->new): ".print_r( $guidMapping, true ) );
							}

							$parentId = $childRelation->Parent;
							$childType = $childRelation->Type;

							if( LogHandler::debugMode() ) {
								LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "Placements before reordering: ".print_r( $childPlacements, true ) );
							}

							// now copy guids from smart_elements table (created by the artice CopyObjects) into the
							// smart_placements table (initialized by this customization)
							$childPlacements = $childRelation->Placements;
							for( $x = 0; $x < count( $childPlacements ); $x++ ) {
								$childPlacements[ $x ]->ElementID = $guidMapping[ $childPlacements[ $x ]->ElementID ];
							}

							if( LogHandler::debugMode() ) {
								LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "Placements after reordering: ".print_r( $childPlacements, true ) );
							}

							$childParentVersion = 1;
							$childVersion = $childRelation->ChildVersion;
							$childGeometry = $childRelation->Geometry;

							$newObjRelation[] = new Relation( $resp->MetaData->BasicMetaData->ID, $newChildId, $childType, $childPlacements, $childParentVersion, $childVersion, $childGeometry );
						}
					}
				}
			}
		}

		// now confirm the new relations
		$newRelation = BizRelation::createObjectRelations( $newObjRelation, $user, null, false, false );
		// and reset the old relations
		BizRelation::deleteObjectRelations( $user, $oldObjRelation );

		// finished
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'CopyWithPlacements', 'DEBUG', 'Returns: CopyWithPlacements_WflCopyObject->runAfter()' );
		}
	}

	final public function runOverruled( WflCopyObjectRequest $req )
	{
	}

	final public function runBefore( WflCopyObjectRequest &$req )
	{
	}

}
