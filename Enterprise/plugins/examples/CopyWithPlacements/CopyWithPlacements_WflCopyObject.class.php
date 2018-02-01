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

		$dbdriver = DBDriverFactory::gen();

		$originalId = $req->SourceID;
		$originalLayout = BizObject::getObject( $originalId, null, false, 'none' );
		$originalMetadata = $originalLayout->MetaData;

		if( $originalMetadata->BasicMetaData->Type != 'Layout' ) {
			return;
		}

		$deepcopy = null;
		foreach( $req->MetaData->ExtraMetaData as $extradata ) {
			if( $extradata->Property == 'C_CWP_DEEPCOPY' ) {
				$deepcopy = ( $extradata->Values[0] == '1' );
				break;
			}
		}

		if( !$deepcopy ) {
			return;
		}

		$newLayout = BizObject::getObject( $resp->MetaData->BasicMetaData->ID, null, false, 'none' );
		$newMetaData = $newLayout->MetaData;
		$newLayoutId = $resp->MetaData->BasicMetaData->ID;

		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$user = BizSession::getShortUserName();
		$pubs = BizPublication::getPublications( $user, 'full', $req->MetaData->BasicMetaData->Publication->Id );
		$article2status = Array();
		if( $pubs ) foreach( $pubs as $pub ) {
			if( $pub->States ) foreach( $pub->States as $state ) {
				if( $state->Type == 'Article' ) {
					$article2status[ $state->Name ] = $state;
				}
			}
		}

		LogHandler::Log( 'CopyWithPlacements', 'INFO', "CopyWithPlacements of layout/dossier $originalId to $newLayoutId" );
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'CopyWithPlacements', 'DEBUG', print_r( $newLayout, true ) );
		}

		$relations = BizRelation::getObjectRelations( $originalId );
		$newobjrelation = array();
		$oldobjrelation = array();

		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "Original relations: ".print_r( $relations, true ) );
		}

		// now process all existing relations in the source layout
		if( $relations ) foreach( $relations as $relation ) {
			$childid = $relation->Child;
			$obj = BizObject::getObject( $childid, null, false, 'none' );

			// only copy Article objects, leave other objects by default
			if( $obj->MetaData->BasicMetaData->Type == 'Article' ) {
				LogHandler::Log( 'CopyWithPlacements', 'INFO', "Copy article ".$obj->MetaData->BasicMetaData->Name );

				// put article relation in the $oldobjrelation set (to be reset)
				$parentid = $relation->Parent;
				$childtype = $relation->Type;
				$childplacements = $relation->Placements;
				$childparenetversion = $relation->ParentVersion;
				$childversion = $relation->ChildVersion;
				$childgeometry = $relation->Geometry;
				$oldobjrelation[] = new Relation( $resp->MetaData->BasicMetaData->ID, $childid, $childtype, $childplacements, $childparenetversion, $childversion, $childgeometry );

				// now create a copy of the article
				// id of object to be copied
				$objid = $obj->MetaData->BasicMetaData->ID;

				//prepare meta data for article copy
				$childmeta = $obj->MetaData;

				$childmeta->WorkflowMetaData->State = $article2status[ $childmeta->WorkflowMetaData->State->Name ];

				// set Brand and Category of article to the target of the layout
				$childmeta->BasicMetaData->Publication = $newMetaData->BasicMetaData->Publication;
				$childmeta->BasicMetaData->Category = $newMetaData->BasicMetaData->Category;

				//change child name
				$newname = $obj->MetaData->BasicMetaData->Name;
				$childmeta->BasicMetaData->Name = BizResources::localize( 'ACT_COPY_OF' ).' '.$newname;

				// Do not copy target meta data, this will make it possible
				// to copy layout + articles to a different brand

				//	$childmeta->TargetMetaData = $newMetaData->TargetMetaData;

				// clear deadline
				$obj->MetaData->WorkflowMetaData->Deadline = "";

				// clear target ID
				unset( $childmeta->BasicMetaData->ID );

				// Copy article into new article
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				$copychild = BizObject::copyObject( $objid, $childmeta, $user, null, null );

				$newchildid = $copychild->MetaData->BasicMetaData->ID;

				// if the copy succeeded, add it as a new relation to the layout
				if( $newchildid ) {
					foreach( $relations as $childRelation ) {
						$childid = $childRelation->Child;
						if( $childid == $objid ) { // Only copy the current child relation !!!
							$guidmapping = array();

							// unfortunately we need a bit of sql here, to set the guids right
							// we have to map from original guid to new guid
							// have to do this because order of components is not guaranteed by copyto service
							// (this should be improved in 8.2, but nevertheless...

							$sql1 = "select `guid` from `smart_elements` where `objid`=".$newchildid.' order by `version` asc';
							$sth1 = $dbdriver->query( $sql1 );

							$sql2 = "select `guid` from `smart_elements` where `objid`=".$objid.' order by `version` asc';
							$sth2 = $dbdriver->query( $sql2 );

							while( $row1 = $dbdriver->fetch( $sth1 ) ) {
								$row2 = $dbdriver->fetch( $sth2 );
								$guidmapping[ $row2['guid'] ] = $row1['guid'];
							}

							if( LogHandler::debugMode() ) {
								LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "GUID mapping (old->new): ".print_r( $guidmapping, true ) );
							}

							$parentid = $childRelation->Parent;
							$childtype = $childRelation->Type;

							if( LogHandler::debugMode() ) {
								LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "Placements before reordering: ".print_r( $childplacements, true ) );
							}

							// now copy guids from smart_elements table (created by the artice CopyObjects) into the
							// smart_placements table (initialized by this customization)
							$childplacements = $childRelation->Placements;
							for( $x = 0; $x < count( $childplacements ); $x++ ) {
								$childplacements[ $x ]->ElementID = $guidmapping[ $childplacements[ $x ]->ElementID ];
							}

							if( LogHandler::debugMode() ) {
								LogHandler::Log( 'CopyWithPlacements', 'DEBUG', "Placements after reordering: ".print_r( $childplacements, true ) );
							}

							// $childparenetversion=$childRelation->ParentVersion;
							$childparenetversion = 1;
							$childversion = $childRelation->ChildVersion;
							$childgeometry = $childRelation->Geometry;

							$newobjrelation[] = new Relation( $resp->MetaData->BasicMetaData->ID, $newchildid, $childtype, $childplacements, $childparenetversion, $childversion, $childgeometry );
						}
					}
				}
			}
		}

		// now confirm the new relations
		$newrelation = BizRelation::createObjectRelations( $newobjrelation, $user, null, false, false );
		// and reset the old relations
		BizRelation::deleteObjectRelations( $user, $oldobjrelation );

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
