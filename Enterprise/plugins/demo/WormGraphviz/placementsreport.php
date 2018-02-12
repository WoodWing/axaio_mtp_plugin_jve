<?php
require_once dirname(__FILE__).'/WormGraphvizComposer.class.php';

class WormGraphvizPlacementsReport extends WormGraphvizComposer
{
	/**
	 * Composes a report that shows a layout and its placements.
	 * The report is sent to PHP output and HTTP headers are set.
	 *
	 * @param string $ticket
	 * @param string $objectId
	 * @param string $format Output file format of graphic image to generate, 'svg' or 'pdf'
	 */
	public function compose( $ticket, $objectId, $format )
	{
		self::$format = $format;

		// Get the object from DB.
		$errMsg = '';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';	
		$service = new WflGetObjectsService();
		try {
			$request = new WflGetObjectsRequest( $ticket );
			$request->Ticket = $ticket;
			$request->IDs = array( $objectId );
			$request->Lock = false;
			$request->Rendition = 'none';
			$request->RequestInfo = array( 'MetaData', 'PagesInfo', 'Relations' );
			$response = $service->execute( $request );
			$objects = $response->Objects;
		} catch( BizException $e ) {
			$errMsg .= $e->getMessage().' ('.$e->getDetail().')';				
			// ignore errors, let InDesign Server plugins fetch layout
		}
		$object = $objects[0];

		// Create a placements Digraph.
		require_once dirname(__FILE__).'/graphviz/vendor/autoload.php';
		$graph = new Alom\Graphviz\Digraph( '"Placements report"' );
		$graph->beginNode( 'title' )
			->attribute( 'shape', 'plaintext' )
			->attribute( 'fontsize', '18' )
			->attribute( 'fontname', 'bold' )
			->attribute( 'label', 'Placements report' )
		->end();
		$this->renderPlacements( $graph, $object );
		$render = $graph->render();

        // Run the Digraph command.
		$this->composeImage( $render, $format );
	}

	/**
	 * Get all the object relations including its child objects' relations.
	 *
	 * @param int $objId
	 * @param string $objType
	 * @return Relation[] List of Relation of which the Relation is added with an internal property 'ChildRelations'.
	 */
	private function getObjectRelations( $objId, $objType )
	{
		$relationType = null;
		switch( $objType ) {
			case 'Dossier':
			case 'DossierTemplate':
				$relationType = 'Contained';
				break;
			case 'PublishForm':
			case 'PublishFormTemplate':
			case 'Layout':
			case 'LayoutTemplate':
			case 'LayoutModule':
			case 'LayoutModuleTemplate':
				$relationType = 'Placed';
				break;
		}

		require_once BASEDIR .'/server/bizclasses/BizRelation.class.php';
		$relations = array();
		if( !is_null( $relationType )) {
			$relations = BizRelation::getObjectRelations( $objId, null, false, 'childs', false, false, $relationType );
			if( $relations ) foreach( $relations as $relation ) {
				$relation->ChildRelations = $this->getObjectRelations( $relation->Child, $relation->ChildInfo->Type );
			}
		}
		return $relations;
	}

	/**
	 * To render all the relations of the objects.
	 *
	 * @param string $parentObjectName
	 * @param Relation[] $relations
	 * @param Alom\Graphviz\Digraph $graph
	 */
	private function renderObjectRelations( $parentObjectId, $relations, $graph )
	{
		if( $relations ) foreach( $relations as $relation ) {
			/** @var Alom\Graphviz\Digraph $graph */
			
			// Draw the child object.
			$clickable = false;
			$node = $graph->beginNode( $relation->ChildInfo->ID );
			switch( $relation->ChildInfo->Type ) {
				case 'Image' :
					// Make images clickable, which navigates to the image preview.
					$node
						->attribute( 'URL', self::composeLinkToImage( $relation->ChildInfo->ID ) )
						->attribute( 'fontcolor', 'blue' )
						->attribute( 'tooltip', 'Click to show image preview' )
					;
					$clickable = true;
					break;
				case 'PublishForm':
				case 'PublishFormTemplate':
				case 'Layout' :
				case 'LayoutTemplate' :
				case 'LayoutModule' :
				case 'LayoutModuleTemplate' :
					// Make these clickable, which 'zooms' into the clicked child.
					$node
						->attribute( 'URL', self::composeLinkToReport( $relation->ChildInfo->ID, 'placementsreport' ) )
						->attribute( 'fontcolor', 'blue' )
						->attribute( 'tooltip', 'Click to zoom into this object' )
					;
					$clickable = true;
					break;
			}
			$node
				->attribute( 'label', self::composeObjectLabel( $relation->ChildInfo->ID, $relation->ChildInfo->Name, $relation->ChildInfo->Type, $clickable ) )
			;
			
			// Draw an arrow between the parent and child objects.
			$graph->edge(
				array( $parentObjectId, $relation->ChildInfo->ID ), 
				array( 'label' => $relation->Type, 'color' => 'blue' )
			);

			if ($relation->Type == 'Placed') {
				$keys = array();
				$pages = array();
				if ( isset ( $relation->Placements ) ) foreach ($relation->Placements as $placement) {
				    if ( !empty( $placement->Element ) ) {
						if ( !array_key_exists( $placement->ElementID, $keys ) ){
						
							// Draw the placed text/graphic element.
					        $keys[$placement->ElementID] = array();
					        $placeNodeId = $placement->FrameID.'-'.$relation->ChildInfo->ID;
							$graph->beginNode( $placeNodeId, array('label' => $placement->Element))
							    ->end()
								->attr( 'edge', array('label' => 'Placement', 'color' => 'blue'))
								->edge( array( $relation->ChildInfo->ID, $placeNodeId) );
							
							// The the page item. Make it clickable, which navigates to the page preview.
							if ( !empty($placement->PageNumber) ) {
								$pageNodeId = $parentObjectId . '_E_' . $placement->Edition. '_P_' . $placement->PageNumber;
								if (!array_key_exists( $placement->PageNumber, $pages) ) {
									$pages[$placement->PageNumber] = 1;
									$graph->beginNode( $pageNodeId )
										->attribute( 'label', '<<u>page ' . $placement->PageNumber.'</u>>' ) // node the outer < > markers for HTML markup
										->attribute( 'URL', self::composeLinkToPage( $parentObjectId, $placement->PageNumber ) )
										->attribute( 'fontcolor', 'blue' )
										->attribute( 'tooltip', 'Click to show page preview' )
										->end();
								}
								$graph->edge(
									array( $placeNodeId, $pageNodeId ), 
									array('label' => 'Placed', 'color' => 'blue')
								);
							}
						}
					}
				}
			}

			if( $relation->ChildRelations ) {
				$this->renderObjectRelations( $relation->ChildInfo->ID, $relation->ChildRelations, $graph );
			}
		}
	}

	/**
	 * To render the Layout object and its child objects if there's any.
	 *
	 * @param Alom\Graphviz\Digraph $graph
	 * @param Object $object Dossier object.
	 */
	private function renderPlacements( $graph, $object )
	{
		$objectId = $object->MetaData->BasicMetaData->ID;
		$objType = $object->MetaData->BasicMetaData->Type;
		$objName = $object->MetaData->BasicMetaData->Name;
		$relations = $this->getObjectRelations( $objectId, $objType );
		$graph->beginNode( $objectId )
			->attribute( 'label', self::composeObjectLabel( $objectId, $objName, $objType, true ) )
			->attribute( 'URL', self::composeLinkToReport( $objectId, 'objectprogressreport' ) )
			->attribute( 'fontcolor', 'blue' )
			->attribute( 'tooltip', 'Click to navigate to progress report' )
			->end();

		$this->renderObjectRelations( $objectId, $relations, $graph );
	}
}
