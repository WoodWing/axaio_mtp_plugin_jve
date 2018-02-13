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

		$originalId = $req->SourceID;
		$originalObjectType = BizObject::getObjectType( $originalId, "Workflow" );
		if( $originalObjectType != 'Layout' ) {
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

		LogHandler::Log( 'CopyWithPlacements', 'INFO', "CopyWithPlacements of layout $originalId to $newLayoutId" );
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
				$childType = $relation->Type;
				$childPlacements = $relation->Placements;
				$childParentVersion = $relation->ParentVersion;
				$childVersion = $relation->ChildVersion;
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
				$obj->MetaData->WorkflowMetaData->Deadline = ""; // clear deadline
				unset( $childMeta->BasicMetaData->ID ); // clear target ID

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

							// Remap the GUID from the parent object to the child.
							$where = "`objid` = ? ";
							$fieldNames = array( 'guid' );
							$orderBy = array( 'version' => true );

							// Child
							$params = array( $newChildId );
							$newChildElements = DBBase::listRows( 'elements', '', '', $where, $fieldNames, $params, $orderBy );

							// Parent
							$params = array( $objId );
							$parentElements = DBBase::listRows( 'elements', '', '', $where, $fieldNames, $params, $orderBy );

							$index = 0;
							if( $newChildElements ) foreach( $newChildElements as $newChildGuidRow ) {
								if( $newChildGuidRow ) foreach ( $newChildGuidRow as $newChildGuid )
								$guidMapping[ $parentElements[$index]['guid'] ] = $newChildGuid;
								$index++;
							}

							if( LogHandler::debugMode() ) {
								LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "GUID mapping (old->new): ".print_r( $guidMapping, true ) );
							}

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

							$newObjRelation[] = new Relation( $resp->MetaData->BasicMetaData->ID, $newChildId, $childType, $childPlacements, $childParentVersion, $childVersion, $childGeometry );
						}
					}
				}
			}
		}

		BizRelation::createObjectRelations( $newObjRelation, $user, null, false, false );
		BizRelation::deleteObjectRelations( $user, $oldObjRelation ); // and reset the old relations

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
