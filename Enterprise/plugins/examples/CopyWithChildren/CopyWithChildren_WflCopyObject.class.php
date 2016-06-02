<?php

/****************************************************************************
   Copyright 2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR . '/server/interfaces/services/wfl/WflCopyObject_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';


class CopyWithChildren_WflCopyObject extends WflCopyObject_EnterpriseConnector
{
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_AFTER; }

	final public function runBefore (WflCopyObjectRequest &$req)
	{
	}

	final public function runAfter (WflCopyObjectRequest $req, WflCopyObjectResponse &$resp)
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';

// prepare a database query engine
		$dbdriver = DBDriverFactory::gen();

// get meta data for original layout
		$srcid=$req->SourceID;
		$myorilayout = BizObject::getObject( $srcid, null, false, 'none' );
		$meta=$myorilayout->MetaData;

// and possibly missing meta data for destination layout
		$mydestlayout = BizObject::getObject( $resp->MetaData->BasicMetaData->ID, null, false, 'none' );
		$result=$mydestlayout->MetaData;

// only accept Layout as a source for a 'deep copy'
		if ($meta->BasicMetaData->Type != 'Layout')
			return;

// check if custom meta data C_DEEPCOPY is set to true, or if it is missing
// in these cases, we will deepcopy the layout
		$deepcopy = true;
		foreach($req->MetaData->ExtraMetaData as $extradata) 
		{
			if ($extradata->Property == 'C_DEEPCOPY')
			{
				$deepcopy = ($extradata->Values[0] == '1');
				break;
			}
		}

// so only continue the deepcopy if the user set the variable to true
		if ($deepcopy)
		{
			LogHandler::Log('CopyWithChildren', 'DEBUG', "Recursive copy of layout/dossier" );

// Get relation data for layout source
			$relations = BizRelation::getObjectRelations( $srcid );

//	$newobjectrelation will contain all relation to set on the new layout
			$newobjrelation=array();

//	$oldobjectrelation will contain all relation to remove from the new layout
			$oldobjrelation=array();


			$childobjects = array();
			
			file_put_contents("/Logs/relations.txt", print_r($relations, true));

// now process all existing relations in the source layout
 
			foreach( $relations as $relation)
			{
				$childid = $relation->Child;
				$obj = BizObject::getObject( $childid, null, false, 'none' );

// only copy Article objects, leave other objects by default				
				if ($obj->MetaData->BasicMetaData->Type == 'Article')
				{
		
// put article relation in the $oldobjrelation set (to be reset)
					$parentid = $relation->Parent;
					$childtype=$relation->Type;
					$childplacements=$relation->Placements;
					$childparenetversion=$relation->ParentVersion;
					$childversion=$relation->ChildVersion;
					$childgeometry=$relation->Geometry;
					$oldobjrelation[]=new Relation($resp->MetaData->BasicMetaData->ID,$childid,$childtype,$childplacements,$childparenetversion,$childversion,$childgeometry);
			
// now create a copy of the article
					//set child id
					$objid = $obj->MetaData->BasicMetaData->ID;
					//set child meta
					$childmeta = $obj->MetaData;
					$childtarget = $mydestlayout->Objects[0]->Targets;
					//change child name
					$newname = $obj->MetaData->BasicMetaData->Name;
	
					$childmeta->BasicMetaData->Name = $newname;
	
					$obj->MetaData->WorkflowMetaData->Deadline = "";
					//copy child
	
					LogHandler::Log('CopyWithChildren', 'DEBUG', "Copy placed object before" );
	
					require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
					$myOriLayoutIssIds = BizTarget::getIssueIds( $myorilayout->Targets );
					require_once BASEDIR . '/server/dbclasses/DBObject.class.php';					
					$newname = DBObject::getUniqueObjectName( $myOriLayoutIssIds, $childmeta->BasicMetaData->Type, $childmeta->BasicMetaData->Name);
	
					LogHandler::Log('CopyWithChildren', 'DEBUG', "Copy placed object after" );
	
					$childmeta->BasicMetaData->Name = $newname;
					unset($childmeta->BasicMetaData->ID);
					
					$childmeta->BasicMetaData->Category = $req->MetaData->BasicMetaData->Category;
	
					// Copy article into new article
					require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
					$childmeta->BasicMetaData->Name = "Xopy of ".$newname;
					$copychild = BizObject::copyObject( $objid, $childmeta, null, $childtarget );
	
					$newchildid = $copychild->MetaData->BasicMetaData->ID;

// if the copy succeeded, at it as a new relation to the layout			
					if ($newchildid)
					{
						foreach( $relations as $relation )
						{
							$childid  = $relation->Child;
	
							//
							// Only copy the current child relation !!!
							//
							if ($childid == $objid)
							{
								$myguid=array();
	
// unfortunately we need a bit of sql here, to set the guids right	
								$sql1 = "select `guid` from `smart_elements` where `objid`=".$newchildid;
								$sth1 = $dbdriver->query($sql1);
								while (	$row1 = $dbdriver->fetch($sth1) ){
									$myguid[]=$row1['guid'];
								}
	
								$parentid = $relation->Parent;
								$childtype= $relation->Type;
	
								$childplacements=$relation->Placements;
								for($x=0;$x<count($myguid);$x++){
									$childplacements[$x]->ElementID=$myguid[$x];
								}
								//$childparenetversion=$relation->ParentVersion;
								$childparenetversion=1;
								$childversion=$relation->ChildVersion;
								$childgeometry=$relation->Geometry;
	
								$newobjrelation[]=new Relation($resp->MetaData->BasicMetaData->ID,$newchildid,$childtype,$childplacements,$childparenetversion,$childversion,$childgeometry);
							}
						}
					}	
				}
			}

// now confirm the new relations
			$newrelation= BizRelation::createObjectRelations($newobjrelation, null, null, false, false);
// and reset the old relations
			BizRelation::deleteObjectRelations( null, $oldobjrelation );

			//$newrelation= BizRelation::updateObjectRelations(null, $newobjrelation);

		}

// finished
		return $resp;

	}

	final public function runOverruled (WflCopyObjectRequest $req) {} // Not called because we're just doing run before and after
	}
