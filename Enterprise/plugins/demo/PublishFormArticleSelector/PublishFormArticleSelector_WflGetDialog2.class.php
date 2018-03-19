<?php

/***************************************************************************
  Copyright 2013 WoodWing Software BV

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

  http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
 ***************************************************************************/

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetDialog2_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class PublishFormArticleSelector_WflGetDialog2 extends WflGetDialog2_EnterpriseConnector
{

	final public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}

	final public function getRunMode()
	{
		return self::RUNMODE_BEFOREAFTER;
	}
        
	final public function runBefore( WflGetDialog2Request &$req )
	{
		LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Called: PublishFormArticleSelector_WflGetDialog2->runBefore()' );
		require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';

		$objectId = '';
		$objectType = '';
		$issueId = '';
		$componentsFound = false;
		$placedComponents = array();

		//Get the object id and type.
		if ($req->MetaData) foreach ($req->MetaData as $metaDataValue) {
			if ( $metaDataValue->Property == 'ID' ) {
				$objectId = $metaDataValue->PropertyValues[0]->Value;
			}

			if ( $metaDataValue->Property == 'Type' ) {
				$objectType = $metaDataValue->PropertyValues[0]->Value;
			}

			if ( $metaDataValue->Property == 'Issue' ) {
				$issueId = $metaDataValue->PropertyValues[0]->Value;
			}
		}

		// Check if we are requesting a SetPublishProperties dialog for a PublishForm.
		if ( $req->Action == "SetPublishProperties" && $objectId != '' && $objectType != '' && $issueId != '' ) {
			// Get the version of the Publish Form.
			$version = $this->getObjectVersion( $objectId );

			// Only place components when the dialog is requested for the first time.
			if ( $version == '0.1' ) {
				// Get the dialog.
				$publishFormDialog = $this->getPublishFormDialog( $objectId, $issueId );
				$publication = $publishFormDialog['MetaData']->BasicMetaData->Publication;
				$category = $publishFormDialog['MetaData']->BasicMetaData->Category;

				// Abort when there are already components assigned.
				if ($this->hasArticleComponentAssigned($publishFormDialog['Relations'])) {
					return;
				}

				//Get the parent Dossier.
				require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
				$relations = BizRelation::getObjectRelations( $objectId, null, false );
				if ( $relations ) foreach ( $relations as $relation ) {
					if ( $relation->Type == 'Contained' ) {
						$dossierId = $relation->Parent;
						$dossierName = $relation->ParentInfo->Name;
						break;
					}
				}

				// Check if we have a parent Dossier (Not DossierTemplate!).
				if ( !empty( $dossierId ) && BizObject::getObjectType( $dossierId, 'Workflow' ) == 'Dossier' ) {
					// Get the first article.
					$target = BizTarget::buildTargetFromIssueId( $issueId );
					$article = $this->getArticle( $dossierId, $dossierName, $target, $publication, $category );
					if ( !$article) {
						return;
					}

					// Prepare the relation between the Article and the PublishForm.
					$articlePublishFormRelation = new Relation(
						$objectId, // Parent Object.
						$article->MetaData->BasicMetaData->ID, // Child Object
						'Placed', // Relation type.
						array()	// The placement.
					);
					$articlePublishFormRelation->Targets = array($target);

					// Search for articlecomponentselectors.
					if ( $publishFormDialog ) foreach ( $publishFormDialog['Dialog']->Tabs as $tab ) {
						foreach ( $tab->Widgets as $widget ) {
							if ( $widget->PropertyInfo->Type == 'articlecomponentselector' ) {
								LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Found articlecomponentselector ' . $widget->PropertyInfo->Name );
								$widgetName = $widget->PropertyInfo->Name;

								// Search for matching elements in the Article.
								foreach ( $article->Elements as $component ) {
									LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Element ' . $component->Name );

									// It is not allowed to place the same component multiple times on a the same PublishForm.
									if ( in_array( $component->Name, $placedComponents ) ) {
										LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Skip ' . $component->Name . ' because its already placed' );
										continue;
									}

									// Check we can assign this component to the widget.
									if ( $this->doesComponentMap( $widget, $component->Name, $target->PubChannel->Id ) ) {
										LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Place Element ' . $component->Name . ' in ' . $widgetName );

										// Place the component.
										$placement = new Placement();
										$placement->FormWidgetId = $widgetName;
										$placement->Element = $component->Name;
										$placement->ElementID = $component->ID;
										$placement->FrameOrder = 0;
										$placement->Top = 0;
										$placement->Left = 0;
										$placement->Width = 0;
										$placement->Height = 0;
										$articlePublishFormRelation->Placements[] = $placement;
										$componentsFound = true;
										$placedComponents[] = $component->Name;
													
										break; // Go to the next widget.
									}
								}
							}
						}
					}

					if ( $componentsFound ) {
						try {
							LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Components found, place the article.' );

							require_once BASEDIR . '/server/services/wfl/WflCreateObjectRelationsService.class.php';
							$request = new WflCreateObjectRelationsRequest();
							$request->Ticket = BizSession::getTicket();
							$request->Relations = array( $articlePublishFormRelation );

							$service = new WflCreateObjectRelationsService();
							$service->execute( $request );
						} catch ( BizException $e ) {
							LogHandler::Log( 'PublishFormArticleSelector', 'ERROR', $e->getMessage() );
						}
					}
				}
			}
		}

		LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Returns: PublishFormArticleSelector_WflGetDialog2->runBefore()' );
	}

	/**
	* Returns the version number of an Object.
	*
	* @param int $id The id of the Object.
	* @param string $area 'Workflow' or 'Trash' where the object resides.
	* @return string Version of the Object.
	*/
	private function getObjectVersion( $id, $area = 'Workflow' )
	{
		$result = null;

		$dbDriver = DBDriverFactory::gen();
		$dbo = ( $area == 'Workflow' ) ? $dbDriver->tablename( 'objects' ) : $dbDriver->tablename( 'deletedobjects' );
		$verFld = $dbDriver->concatFields( array('o.`majorversion`', "'.'", 'o.`minorversion`' ) ) . ' as "version"';

		$sql = "SELECT $verFld FROM $dbo o WHERE `id` = $id";
		$sth = $dbDriver->query( $sql );
		$currRow = $dbDriver->fetch( $sth );

		if ( $currRow ) {
			$result = $currRow['version'];
		}

		return $result;
	}

	/**
	* Returns the first Article of the Dossier or null if the Dossier does not contain any articles.
	*
	* @param string dossierId The id of the Dossier.
	* @param string dossierName Name of the Dossier, used when copying an Article into the Dossier.
	* @param int target The target involved 
	* @param Publication publication The publication used when copying an Article.
	* @param category The category used when copying an article
	*/
	private function getArticle( $dossierId, $dossierName, $target, $publication, $category )
	{
		try {
			$copyArticleToDossier = false;
			$suffix = '';
			$articleId = null;
			$channelId = $target->PubChannel->Id;
			$dossierRelations = null;
			
			// Base query.
			require_once BASEDIR . '/server/services/wfl/WflQueryObjectsService.class.php';
			$request = new WflQueryObjectsRequest();
			$request->Ticket = BizSession::getTicket();
			$request->FirstEntry = 0;
			$request->MaxEntries = 1;
			$request->Hierarchical = false;
			$request->Areas = array( 'Workflow' );
			$request->Order = array( new QueryOrder( 'Created', true ) );

			//Search for the mapping rule.
			$article_rules = unserialize( ARTICLE_RULES );
			if ( $article_rules ) foreach ( $article_rules as $rule) {					
				if ( $rule['channelId'] == '' || $rule['channelId'] == $channelId ) {
					if ($rule['type'] == 'upgrade') {
						//Upgrade, used for dossiers that were previously published using a none publishform aware channel
						//for example for Drupal6
						//In that case the target of the contained relation is set
						
						//Check if we have the dossier relations
						if (!isset($dossierRelations)) {
							$dossierRelations = BizRelation::getObjectRelations( $dossierId, null, false );
						}	  
							
						//Search in the dossier relations for the correct article
						if ($dossierRelations) foreach ($dossierRelations as $relation) { 
							if (
								$relation->Type  == 'Contained' && //Contained relation
								$relation->ChildInfo->Format	== 'application/incopyicml' && //Format is incopy wcml	 
								$relation->Targets && in_array($target->Issue->Id, BizTarget::getIssueIds($relation->Targets)) //Targeted
							) {									
								$copyArticleToDossier = false;
								$articleId = $relation->ChildInfo->ID;
								break;
							}
						}
					} else if ($rule['type'] == 'dossier') {		
						//We search for an article in the dossier
						if (!isset($dossierRelations)) {
							$dossierRelations = BizRelation::getObjectRelations( $dossierId, null, false );
						}	  
						
						//Search in the dossier relations for the correct article
						if ($dossierRelations) foreach ($dossierRelations as $relation) { 
							if (
								$relation->Type  == 'Contained' && //Contained relation
								$relation->ChildInfo->Format	== 'application/incopyicml' && //Format is incopy wcml	 
								preg_match( $rule['articleNameRegEx'], $relation->ChildInfo->Name ) 
							) {									
								$copyArticleToDossier = false;
								$articleId = $relation->ChildInfo->ID;
								break;
							}
						}														
					} else if ($rule['type'] == 'copy') {		
						//Search for the article within the Enterprise and copy it to the Dossier							
						$request->Params = array(
							new QueryParam( 'Type', '=', 'Article' ),
							new QueryParam( 'Name', '=', $rule['articleName'] )
						);

						// Process the brandId setting of the rule, if -1 search in all brands.
						if ( $rule['brandId'] != '-1' ) {
							if ( $rule['brandId'] == '' ) {
								// Search the Brand of the Channel .
								$request->Params[] = new QueryParam( 'PublicationId', '=', $publication->Id );
							} else {
								// Search the specified Brand.
								$request->Params[] = new QueryParam( 'PublicationId', '=', $rule['brandId'] );
							}
						}

						// Search for the Article.
						$service = new WflQueryObjectsService();
						$response = $service->execute( $request );
						if ( count( $response->Rows ) != 0 ) {
							$suffix = $rule['suffix'];
							$articleId = $this->getValueFromQueryResponseRow( $response, $response->Rows[0], 'ID' );
							$copyArticleToDossier = true;
							break;
						}								
					} else {
						continue; // Bad configuration!
					}					
				}
					
				//check if we found an article
				if ($articleId)
					break;
			}

			if ( $articleId ) {
				LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Found article ' . $articleId );
				$article = $this->getArticleObject( $articleId );

				if ( $copyArticleToDossier ) {
					// Get the Article name.
					$suffixLen = mb_strlen( $suffix, "UTF8" );
					$articleName = mb_strcut( $dossierName, 0, 63 - $suffixLen ) . $suffix;

					// Copy the Article into the Dossier.
					require_once BASEDIR . '/server/services/wfl/WflCopyObjectService.class.php';
					$service = new WflCopyObjectService();
					$request = new WflCopyObjectRequest();
					$request->Ticket = BizSession::getTicket();
					$request->SourceID = $articleId;
					$request->MetaData = $article->MetaData;
					$request->MetaData->BasicMetaData->Name = $articleName;
					$request->MetaData->BasicMetaData->Publication = $publication;
					$request->MetaData->BasicMetaData->Category = $category;
					$request->Relations = array( new Relation( $dossierId, null, 'Contained' ) );
					$request->Targets = null;
					$resp = $service->execute( $request );

					//Get the copied Article.
					$article = $this->getArticleObject( $resp->MetaData->BasicMetaData->ID );
				}
			
				return $article;
			}
		} catch (BizException $e) {
				LogHandler::Log( 'PublishFormArticleSelector', 'ERROR', $e->getMessage() );
		}

		return null;
	}

	/**
	* Get an article Object from the database.
	*
	* @param int $articleId The id of the article Object.
	* @return Object|null An article Object or null if not found.
	*/
	public function getArticleObject( $articleId )
	{
		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest( BizSession::getTicket(), array( $articleId ), false, 'none', array( 'Elements' ) );
		$service = new WflGetObjectsService();
		$response = $service->execute( $request );
		$article = ( $response->Objects ) ? reset( $response->Objects ) : null;
		return $article;
	}

	/**
	* Returns the SetPublishProperties dialog of the PublishForm.
	*
	* @param string $publishFormId The id of the PublishForm.
	* @param string $issueId The id the PublishForm is in.
	* @return The retrieved Dialog.
	*/
	private function getPublishFormDialog( $publishFormId, $issueId )
	{
		// Get the Dialog for the PublishForm.
		LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Get the dialog.' );

		try {
			require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
			$targets = array();
			$username = BizSession::getShortUserName();

			$metaData = array();

			$mdValId = new MetaDataValue();
			$mdValId->Property = "ID";
			$mdValId->Values = null;
			$mdValId->PropertyValues = array( new PropertyValue( $publishFormId ) );
			$metaData['ID'] = $mdValId;

			$mdValIssue = new MetaDataValue();
			$mdValIssue->Property = "Issue";
			$mdValIssue->Values = null;
			$mdValIssue->PropertyValues = array( new PropertyValue( $issueId ) );
			$metaData['Issue'] = $mdValIssue;

			// Use the BizWorkFlow directly because using the service layer would result in an endless loop!
			return BizWorkflow::getDialog( $username, 'SetPublishProperties', $metaData, $targets, true, true, true, true, true );
		} catch (BizException $e) {
			LogHandler::Log( 'PublishFormArticleSelector', 'ERROR', $e->getMessage() );
		}
	}

	/**
	* Check the WflNamedQueryResponse to see if we have an Object that matches the passed ID.
	*
	* @param WflNamedQueryResponse $response The Response Object.
	* @param int $publishFormId The PublishForm ID.
	* @return bool Whether or not the Object ID was found in the Response.
	*/
	private function getValueFromQueryResponseRow( WflQueryObjectsResponse $response, $row, $columnName ) {
		$indexes = array( $columnName => -1 );
		foreach ( array_keys($indexes) as $colName ) {
			foreach ( $response->Columns as $index => $column ) {
				if ( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break;
				}
			}
		}

		// Check that we have a record for our added PublishForm.
		return $row[$indexes[$columnName]];
	}

	/**
	* Check if one of the article component widgets already has a component assigned.
	*
	* @param Relation[] $relations The Relations of the getDialog.
	* @return bool True if there is a component assigned else false.
	*/
	private function hasArticleComponentAssigned( $relations )
	{
		if ( $relations ) foreach ( $relations as $relation ) {
			foreach ( $relation->Placements as $placement ) {
				if ( $placement->FormWidgetId ) {
					LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'hasArticleComponentAssigned true' );
					return true;
				}
			}
		}

		LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'hasArticleComponentAssigned false' );
		return false;
	}

	/**
	* Check if this is a component we can place in the widget.
	*
	* @param object $widget A widget object
	* @param string $componentName Name of the article component
	* @param int $channelId The id of channel
	*/
	private function doesComponentMap( $widget, $componentName, $channelId )
	{
		$mappingRules = unserialize( MAPPING_RULES );

		if ( $mappingRules ) foreach ( $mappingRules as $rule ) {
			if (
				preg_match( $rule['labelRegEx'], $widget->PropertyInfo->DisplayName )
				&& stristr( $componentName, $rule['componentName'] )
				&& ( $rule['channelId'] == '' || $rule['channelId'] == $channelId )
			) {
				return true;
			}
		}
		return false;
	}

	final public function runAfter(WflGetDialog2Request $req, WflGetDialog2Response &$resp)
	{
		LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Called: PublishFormArticleSelector_WflGetDialog2->runAfter()' );
		require_once dirname( __FILE__ ) . '/config.php';

		LogHandler::Log( 'PublishFormArticleSelector', 'DEBUG', 'Returns: PublishFormArticleSelector_WflGetDialog2->runAfter()' );
	}

	// Not called.
	final public function runOverruled( WflGetDialog2Request $req )
	{
	}
}