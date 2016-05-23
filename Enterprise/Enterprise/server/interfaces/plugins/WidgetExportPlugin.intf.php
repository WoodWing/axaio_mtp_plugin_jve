<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/dataclasses/OutputDevice.php';

interface WidgetExportPlugin
{
	/**
	 * Process the widget to customize the exported widget.
	 * 
	 * @param Object $entDossier - READ ONLY - Current Enterprise dossier object
	 * @param Object $entObject - READ ONLY - Current Enterprise Widget object
	 * @param OutputDevice $outputDevice - READ ONLY - Current export device object 
	 * @param string $widgetRootPath - READ ONLY - Path to the root of the widget
	 * @param array $widgetPlacement - READ ONLY - Info about the placement within the magazine
	 * @param DOMDocument $manifestDocument - The manifest DOM document. Can be changed
	 */
	public function processWidget( Object $entDossier, Object $entWidget, OutputDevice $outputDevice, $widgetRootPath, array $widgetPlacement, DOMDocument $manifestDocument);
}
