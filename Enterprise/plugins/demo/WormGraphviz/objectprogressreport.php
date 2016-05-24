<?php
require_once dirname(__FILE__).'/WormGraphvizComposer.class.php';

class WormGraphvizObjectProgressReport extends WormGraphvizComposer
{	
	/** @var integer $statusesDone The count of statuses, objects went through. Use for progress measurement.  */
	private $statusesDone;

	/** @var integer $statusesTodo The count of statuses, objects still have to go through. Use for progress measurement.  */
	private $statusesTodo;
	
	/** @var Alom\Graphviz\Digraph $graph Representation of entire drawing. */
	private $graph;

	/** @var Alom\Graphviz\Digraph $statusGraph Holds the status items of the drawing. */
	private $statusGraph;

	/** @var Alom\Graphviz\Digraph $relationGraph Holds the (related) object items of the drawing. */
	private $relationGraph;
	
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
		$this->statusesDone = 0;
		$this->statusesTodo = 0;
		
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
			$request->RequestInfo = array('MetaData');
			$response = $service->execute( $request );
			$objects = $response->Objects;
		} catch( BizException $e ) {
			$errMsg .= $e->getMessage().' ('.$e->getDetail().')';				
			// ignore errors, let InDesign Server plugins fetch layout
		}
		$object = $objects[0];

		// Compose the Digraph command.
		require_once dirname(__FILE__).'/graphviz/vendor/autoload.php';
		$this->graph = new Alom\Graphviz\Digraph('"Progress report"');
		//$this->graph->attr( 'graph', array( 'bgcolor' => '#A0A0A0' ));
		$this->graph->beginNode( 'title' )
			->attribute( 'shape', 'plaintext' )
			->attribute( 'fontsize', '18' )
			->attribute( 'fontname', 'bold' )
			->attribute( 'label', 'Progress report' )
		->end();
		$this->edgesTowardsInitialStatuses = array();
		$this->renderObject( $object );
		$render = $this->graph->render();

		// Run the Digraph command.
		$this->composeImage( $render, $format );
	}
	
	private function getObjectStatusId( $objectId )
	{
		$fields = array( 'state' );
		$where = '`id` = ?';
		$params = array( $objectId );
		$row = DBBase::getRow( 'objects', $where, $fields, $params );
		return $row['state'];
	}
	
	private function getStatusRows( $publId, $issueId, $objType )
	{
		switch( $objType ) {
			case 'Dossier':
			case 'DossierTemplate':
			case 'PublishForm':
			case 'PublishFormTemplate':
			case 'Layout':
			case 'LayoutTemplate':
			case 'LayoutModule':
			case 'LayoutModuleTemplate':
				return array(); // workflow not so interesting for non-placable objects
		}
				
		$publId = intval($publId); // Convert to integer
		$issueId = intval($issueId); // Convert to integer

		$fields = array( 'id', 'type', 'state', 'color' );
		$where = '`publication` = ? AND `issue` = ? AND `type` = ?';
		$params = array( $publId, $issueId, $objType );
		$orderBy = array( 'code' => true, 'id' => true );
		
		$rows = DBBase::listRows( 'states', null, null, $where, $fields, $params, $orderBy );
		$objs = array();
		if( PERSONAL_STATE == 'ON' ) { // insert personal status definition when this feature is enabled
			$objs[-1] = array(
				'id' => -1,
				'type' => $objType,
				'state' => BizResources::localize('PERSONAL_STATE'),
				'color' => PERSONAL_STATE_COLOR
			);
		}
		if( $rows ) foreach( $rows as $row ) {
			$objs[$row['id']] = $row;
		}
		return $objs;
	}
	
	private function renderStatusRows( $objectId, $objectType, $objectStatusId, $statusRows )
	{
		$initialStatusId = 0;
		$edges = array();
		$passedCurrent = false;
		$passedInitial = false;
		if( $statusRows ) foreach( $statusRows as $row ) {
		
			// Skip the Personal status, when not used by object.
			if( $objectStatusId != -1 && $row['id'] == -1 ) {
				continue;
			}
			
			// Count todo/done statuses for the object.
			if( $passedInitial ) { // initial status does not count for progress
				if( $passedCurrent ) {
					$this->statusesTodo += 1;
				} else {
					$this->statusesDone += 1;
				}
			} else {
				$initialStatusId = $row['id']; // either personal, or first defined in workflow.
			}
			
			// Draw status. On the left, draw a colored box, on the right, draw the status name.
			$nodeId = $objectId.'-'.$row['id'];
			$statusColor = $row['color'] ? $row['color'] : '#A0A0A0';
			if( $objectStatusId == $row['id'] ) { // Object's current workflow status.
				$passedCurrent = true;
				$node = $this->statusGraph->beginNode( $nodeId );
				$node
					->end();
			} else {
				$node = $this->graph->beginNode( $nodeId );
				$node
					->end();
			}
			$node
				->attribute( 'color', 'grey' )
				->attribute( 'fillcolor', 'white' )
				->attribute( 'shape', 'box' )
				->attribute( 'style', 'filled,rounded' )
				->attribute( 'label', 
					'<<table border="0"><tr>'.
						'<td><table border="0"><tr>'.
							'<td border="1" bgColor="'.$statusColor.'">&nbsp;&nbsp;&nbsp;</td>'.
						'</tr></table></td>'.
						'<td>'.$row['state'].'</td>'.
					'</tr></table>>' 
				)
				->end();
			$edges[] = $nodeId;
			$passedInitial = true;
		}
		if( count($edges) > 1 ) {
			$this->graph->edge( $edges, array( 'color' => 'black' ) );
		}
		return $initialStatusId;
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
			$relations = BizRelation::getObjectRelations( $objId, true, false, 'childs', false, false, $relationType );
			if( $relations ) foreach( $relations as $relation ) {
				$relation->ChildRelations = $this->getObjectRelations( $relation->Child, $relation->ChildInfo->Type );
			}
		}
		return $relations;
	}

	/**
	 * To render the given object and its child objects if there's any.
	 *
	 * @param Object $object
	 */
	private function renderObject( $object )
	{
		$objectId = $object->MetaData->BasicMetaData->ID;
		$objectType = $object->MetaData->BasicMetaData->Type;
		$objectName = $object->MetaData->BasicMetaData->Name;
		$objectStatusId = $object->MetaData->WorkflowMetaData->State->Id;
		$relations = $this->getObjectRelations( $objectId, $objectType );

		$pubId = $object->MetaData->BasicMetaData->Publication->Id;
		$issId = 0; // TODO: overrule issues
		$this->graph
			->beginNode( $objectId )
				->attribute( 'label', self::composeObjectLabel( $objectId, $objectName, $objectType, true ) )
				->attribute( 'URL', self::composeLinkToReport( $objectId, 'placementsreport' ) )
				->attribute( 'fontcolor', 'blue' )
				->attribute( 'tooltip', 'Click to navigate to the placements report' )
			->end();

		$this->statusGraph = $this->graph->subgraph( 'cluster_statuses' ) // 'cluster' is a preserved keyword!
			->set( 'color', '#E0E0E0' )
			->set( 'style', 'filled,dashed' )
			->set( 'pencolor', 'black' ) // box around cluster
		;

		// Cluster the relations, to keep them together a bit. (Avoid mixing with initial statuses.)
		$this->relationGraph = $this->graph->subgraph( 'cluster_relations' ) // 'cluster' is a preserved keyword!
			->set( 'pencolor', 'white' ) // no box around cluster (hack)
		;

		$this->renderObjectRelations( $pubId, $issId, $objectId, $relations );
		
		$progress = round( ( $this->statusesDone / ($this->statusesTodo + $this->statusesDone)) * 100 ).'%';
		$this->statusGraph->node( 'pt-1', array( // pt = progress title
				'shape' => 'plaintext',
				'label' => '<<b>Progress: '.$progress.'</b>>' // note the outer < > markers; those indicate HTML markup
			));

		$statusRows = $this->getStatusRows( $pubId, 0, $objectType );
	}

	/**
	 * To render all the relations of the objects.
	 *
	 * @param integer $parentObjectId
	 * @param Relation[] $relations
	 */
	private function renderObjectRelations( $pubId, $issId, $parentObjectId, $relations )
	{
		if( $relations ) foreach( $relations as $relation ) {
			$clickable = false;
			$node = $this->relationGraph->beginNode( $relation->ChildInfo->ID );
			switch( $relation->ChildInfo->Type ) {
				case 'PublishForm':
				case 'PublishFormTemplate':
				case 'Layout':
				case 'LayoutTemplate':
				case 'LayoutModule':
				case 'LayoutModuleTemplate':
					$node
						->attribute( 'URL', self::composeLinkToReport( $relation->ChildInfo->ID, 'objectprogressreport' ) )
						->attribute( 'fontcolor', 'blue' )
						->attribute( 'tooltip', 'Click to show placements report' )
					;
					$clickable = true;
					break;
			}
			$node
				->attribute( 'label', self::composeObjectLabel( $relation->ChildInfo->ID, $relation->ChildInfo->Name, $relation->ChildInfo->Type, $clickable ) )
			;
			$this->graph->edge(
					array( $parentObjectId, $relation->ChildInfo->ID ), 
					array( 'color' => 'blue', 'label' => $relation->Type )
				);
			
			// Draw child objects and relations with this parent.
			if( $relation->ChildRelations ) {
				$this->renderObjectRelations( $pubId, $issId, $relation->ChildInfo->ID, $relation->ChildRelations );
			}
			
			// Draw status flow diagram for object type.
			$childObjId = $relation->ChildInfo->ID;
			$childObjType = $relation->ChildInfo->Type;
			$statusRows = $this->getStatusRows( $pubId, 0, $childObjType );
			if( $statusRows ) {
				if( !isset($this->edgesTowardsInitialStatuses[$childObjId]) ) { // avoid duplicate edges
					$this->edgesTowardsInitialStatuses[$childObjId] = true;
					
					$objectStatusId = $this->getObjectStatusId( $childObjId );
					$initialStatusId = $this->renderStatusRows( $childObjId, $childObjType, $objectStatusId, $statusRows );

					// Draw arrow from object to first status in flow.
					if( $initialStatusId != 0 ) { // should always be
						$row = $statusRows[$initialStatusId];
						$initialObjStatusId = $childObjId.'-'.$row['id'];
						$this->graph->edge( 
							array( $relation->ChildInfo->ID, $initialObjStatusId ),
							array( 'style' => 'dotted', 'label' => $childObjType )
						);
					}
				}
			}
		}
	}
}
