<?php
/**
 * @package Enterprise
 * @subpackage BizServices
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/../DataClasses.php';
class Widget
{
	private $widget = null;
	private $device = null;
	private $xDoc 	= null;

	/**
	 * Constructor.
	 *
	 * @param string $dossierId
	 * @param string $widgetId
	 * @param string $layoutId
	 * @param string $editionId
	 * @param object $artboard Object of coordinate
	 * @param object $location Object of coordinate
	 * @param String $manifest The manifest file with the output content
	 * @param string $pageSequence Page sequence number
	 */
	public function __construct( $dossierId, $widgetId, $layoutId, $editionId, $artboard, $location, $manifest, $pageSequence )
	{
		$widget = new stdClass();
		$widget->DossierId		= $dossierId;
		$widget->WidgetId		= $widgetId;
		$widget->LayoutId		= $layoutId;
		$widget->EditionId		= $editionId;
		$widget->Artboard		= $artboard;
		$widget->Location		= $location;
		$widget->Manifest		= $manifest;
		$widget->PageSequence	= $pageSequence;
		$this->widget = $widget;
	}

	/**
	 * Instantiate widget
	 *
	 * @return Attachment $attachment Attachment of the processes widget file
	 */
	public function instantiate()
	{
		$dossier	= $this->getObject( $this->widget->DossierId );
		$widget		= $this->getObject( $this->widget->WidgetId );
		$layout		= $this->getObject( $this->widget->LayoutId );
		$page 		= $this->getPage( $layout, $this->widget->PageSequence );
		$offsets 	= $this->widget->Artboard;
		$this->getDevice( $this->widget->EditionId );

		$widgetFilePath = $this->exportWidget( $dossier, $widget, $offsets, $layout, $page );
		$attachment = null;
		if( file_exists( $widgetFilePath ) ) {
			$objType	= $widget->MetaData->ContentMetaData->Format;
			$attachment = new Attachment( 'native', $objType );

			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$bizTransfer = new BizTransferServer();
			$bizTransfer->copyToFileTransferServer( $widgetFilePath, $attachment );
			$bizTransfer->filePathToURL( $attachment );

			// Clean all subfolders and files, including the top folder itself
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			FolderUtils::cleanDirRecursive( dirname( $widgetFilePath ) , true );
		}
		return $attachment;
	}

	/**
	 * Exports the widget properties into given magazine XML document.
	 *
	 * @param Object $dossier
	 * @param object $widget
	 * @param Object $offsets
	 * @param object $layout
	 * @param Page $page
	 * @return string $archivePath The archive file of the processed widget file
	 */
	private function exportWidget( $dossier, $widget, $offsets, $layout, $page )
	{
		LogHandler::Log( 'Dps','INFO', 'Export widget' );
		$objectId	= $this->widget->WidgetId;
		$manifest	= trim( $this->widget->Manifest );

		$xPlacement = $this->exportPlacedObjectProps( $objectId, $this->widget->Location, 'widget', $offsets, $layout, $page );
		$tempFolder	= $this->downloadWidgetFile( $dossier, $widget, $manifest, $xPlacement, $layout, $page );
		$archivePath = $this->createWidgetArchive( $tempFolder );
		LogHandler::Log( 'Dps','INFO', 'Exported widget to archive ' . $archivePath );
		return $archivePath;
	}

	/**
	 * Exports basic object properties (id, type, x, y, height, width) into given XML document.
	 *
	 * @param string $objectId
	 * @param object $coordinates
	 * @param string $type
	 * @param object $offsets
	 * @param object $layout
	 * @param Page $page
	 * @return DOMNode The exported object XML node
	 */
	private function exportPlacedObjectProps( $objectId, $coordinates, $type, $offsets, $layout, $page )
	{
		$this->xDoc = new DOMDocument();
		$xObject = $this->xDoc->createElement( 'object' );
		$this->createTextElem( $xObject, 'id', 		$objectId );
		$this->createTextElem( $xObject, 'type',	$type );
		$this->createTextElem( $xObject, 'x', 		$this->pointsToPixelsWithOffset( $coordinates->Left, $offsets->Left, 'x', $layout, $page ) );
		$this->createTextElem( $xObject, 'y', 		$this->pointsToPixelsWithOffset( $coordinates->Top, $offsets->Top, 'y', $layout, $page ) );
		$this->createTextElem( $xObject, 'width', 	$this->pointsToPixels( $coordinates->Width, 'width', $layout, $page ) );
		$this->createTextElem( $xObject, 'height', 	$this->pointsToPixels( $coordinates->Height, 'height', $layout, $page ) );
		return $xObject;
	}
	
	/**
	 * Retrieves a widget from storage (FILE or DB) and writes it to the magazine export folder.
	 *
	 * @param Object $dossier
	 * @param Object $object
	 * @param String $manifest The manifest file with the output content
	 * @param DOMNode $widgetPlacement
	 * @param Object $layout (layout widget is placed on).
	 * @param Page $page, first page of layout, needed to calculate the dimensions in pixels.		 
	 * @return string The file path of the temporary export folder.
	 */
	private function downloadWidgetFile( $dossier, $object, $manifest, $widgetPlacement, $layout, $page )
	{
		require_once BASEDIR.'/server/utils/WidgetUtils.class.php';
		try {
			$widgetUtils = new WW_Utils_WidgetUtils();
			$widgetUtils->schema 		= BASEDIR.'/server/schemas/ww_dm_manifest_v1.xsd';
			$widgetUtils->tempFolder 	= TEMPDIRECTORY .'/widget_'.uniqid();
			$widgetUtils->widgetPluginFolder = BASEDIR.'/config/WidgetExportPlugins/';
			$frameWidth = floatval($this->widget->Location->Width);
			$frameHeight = floatval($this->widget->Location->Height);
			$frameDimensions['width'] = $this->pointsToPixels($frameWidth, 'width', $layout, $page); 
			$frameDimensions['heigth'] = $this->pointsToPixels($frameHeight, 'height', $layout, $page); 
			$widgetUtils->downloadWidgetFile( $dossier, $object, $manifest, $widgetPlacement, $frameDimensions );
		} catch( BizException $e ) {
			// When BizException, remove the extracted folder and its contents
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			FolderUtils::cleanDirRecursive( $widgetUtils->tempFolder, true );

			throw $e;
		}
		return $widgetUtils->tempFolder;
	}

	/**
	 * Create an archive file, add files from directory to the archive
	 *
	 * @param string $tempFolder Temporary folder that contains the extracted and processed widget files 
	 * @return string $widgetArchivePath The widget archive file path
	 */
	private function createWidgetArchive( $tempFolder )
	{
		$fileExt = '.zip';
		$widgetArchivePath = $tempFolder . '/widget_'.$this->widget->WidgetId.'_'.uniqid().$fileExt;

		require_once BASEDIR.'/server/utils/ZipUtility.class.php';
		$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility();
		$zipUtility->createZipArchive( $widgetArchivePath );
		$zipUtility->addDirectoryToArchive( $tempFolder . '/' );
		return $widgetArchivePath;
	}

	/**
	 * Get object
	 *
	 * @param string $objId
	 * @return object $object
	 */
	private function getObject( $objId )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest( BizSession::getTicket(), array($objId), false, 'none', array() );
		$service = new WflGetObjectsService();
		try {
		$response = $service->execute( $request );
		} catch ( BizException $e ) {
			$e = $e;
			$response = null;
		}	
		$object = ( $response && $response->Objects ) ? $response->Objects[0] : null;

		return $object;
	}

	/**
	 * Helper function to create an XML node with a text node inside.
	 *
	 * @param DOMNode $xmlParent
	 * @param string $nodeName
	 * @param string $nodeText
	 * @return DOMNode
	 */
	private function createTextElem( DOMNode $xmlParent, $nodeName, $nodeText )
	{
		$xmlNode = $this->xDoc->createElement( $nodeName );
		$xmlParent->appendChild( $xmlNode );
		$xmlText = $this->xDoc->createTextNode( $nodeText );
		$xmlNode->appendChild( $xmlText );
		return $xmlNode;
	}

	/**
	 * Get the device by editionid from the list of devices
	 *
	 * @param string $editionId
	 */
	private function getDevice( $editionId )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmOutputDevice.class.php';
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		$edition = DBEdition::getEdition($editionId);
		$bizDevice = new BizAdmOutputDevice();
		$allDevices = $bizDevice->getDevices();
		$deviceFound = null;
		
					foreach( $allDevices as $device ) {
						if( $device->Name == $edition->Name ) {
							$deviceFound = $device;
							break;
						}
					}
		
		if( $deviceFound ) {
			$this->device = $deviceFound;
		} else {
			$message = DMEXPORT_COULD_NOT_EXPORT_NO_DEVICES;
			throw new BizException( '', 'Server', '', $message );
		}
	}

	/**
	 * Get the page by page sequence from the layout pages
	 *
	 * @param object $layout
	 * @param string $pageSequence
	 * @return Page $page;
	 */
	private function getPage( $layout, $pageSequence )
	{
		$pageFound = null;
		if( $layout->Pages ) foreach( $layout->Pages as $page ) {
			if( $page->PageSequence == $pageSequence ) {
				$pageFound = $page;
			}
		}
		if( is_null( $pageFound) ) {
			$pageFound = $layout->Pages[0]; // Get the first page
		}
		return $pageFound;
	}

	/**
	 * Converts placement coordiates from points to pixels.
	 * This is to 'reposition' a placed image onto a layout page preview.
	 *
	 * @param double $points Coordinate in points
	 * @param string $type (possible values: x, y, width and height)
	 * @param object $layout
	 * @param Page $page
	 * @return integer Coordinate in pixels
	 */
	private function pointsToPixels( $points, $type, $layout, $page )
	{
		$pointToDevide = 0;
		$pixels = 0;
		switch ( $type ) {
			case 'x':
			case 'width':
				$pixels = $this->device->getScreenWidth( !$this->isHorizontalPage($page, $layout) );
				$pointToDevide = $this->widget->Artboard->Width;
				break;
			case 'y':
			case 'height':
				$pixels 		= $this->device->getScreenHeight( !$this->isHorizontalPage($page, $layout) );
				$layoutWidth 	= $this->widget->Artboard->Width;
				$screenWidth 	= $this->device->getScreenWidth( !$this->isHorizontalPage($page, $layout) );
				$screenHeight 	= $this->device->getScreenHeight( !$this->isHorizontalPage($page, $layout) );
				$pointToDevide 	= ( ( $layoutWidth / $screenWidth ) * $screenHeight );
				break;
			default:
				// do nothing
		}

		if ( $pointToDevide <= 0 ) { // return 0 because we can't devide by 0
			return 0;
		}

		return intval ( round ( ( ( $points / $pointToDevide ) * $pixels ) ) );
	}

	/**
	 * Converts placement coordiates from points to pixels for given offset.
	 *
	 * @param double $points Coordinate in points
	 * @param double $offset Offset in points
	 * @param string $type (possible values: x, y, width and height)
	 * @param object $layout
	 * @param Page $page
	 * @return integer Coordinate in pixels
	 */
	private function pointsToPixelsWithOffset( $points, $offset, $type, $layout, $page )
	{
		$points -= $offset;
		return $this->pointsToPixels( $points, $type, $layout, $page );
	}

	/**
	 * Check whether it is horizontal page
	 *
	 * @param Page $page
	 * @return boolean
	 */
	private function isHorizontalPage( $page )
	{
		$pageWidth = $page->Width;
		$landscapeLayoutWidth = $this->device->LandscapeLayoutWidth;

		// Calculate the number of decimals of the defined width in the server features
		// This is to round the pageWidth that comes back from ID
		$countDecimals = 0;
		$pos = stripos($landscapeLayoutWidth, '.');
		if ( $pos !== false ) { // If the dot is found
			$decimals = substr($landscapeLayoutWidth, $pos + 1 );
			$countDecimals = strlen($decimals);
		}

		if ( $landscapeLayoutWidth == round( $pageWidth, $countDecimals ) ) {
			return true;
		}
		return false;
	}
}
?>
