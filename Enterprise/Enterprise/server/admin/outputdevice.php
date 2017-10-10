<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizAdmOutputDevice.class.php';
require_once BASEDIR.'/server/dataclasses/OutputDevice.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// Start the session to save the ticket in BizSession
$ticket = checkSecure('admin'); // system admin
BizSession::startSession( $ticket );

// Init
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'init';
$bizDevice = new BizAdmOutputDevice();
$appDevice = new AdmOutputDeviceApp();

// Handle request
$device = null;
$errors = array();
try {
	switch( $mode )
	{
		case 'init':
			$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
			if( $id ) {
				$device = $bizDevice->getDevice( $id );
			} else {
				require_once BASEDIR.'/server/dataclasses/OutputDevice.php';
				$device = new OutputDevice();
			}
		break;
		case 'update':
			$device = $appDevice->buildOutputDeviceFromHttpParams();
			$devices = $bizDevice->modifyDevices( array( $device ) );
			$device = $devices[0];
		break;
		case 'insert':
			$device = $appDevice->buildOutputDeviceFromHttpParams();
			$devices = $bizDevice->createDevices( array( $device ) );
			$device = $devices[0];
		break;
	}
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
}

// Build input form and HTML page
$txt = HtmlDocument::loadTemplate( 'outputdevice.htm' );
if( $device ) {
	$txt = $appDevice->fillFormWithOutputDevice( $txt, $device );
}
if( $mode == 'init' && count($errors) == 0 ) {
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$mode = $id ? 'update' : 'insert'; // next step
} else if( $mode == 'insert' && count($errors) == 0 ) {
	$mode = 'update';
}
if( $mode == 'insert' ) {
	$txt = str_replace( '<!--PAR:SUBMIT_BUTTON_TEXT-->', '<!--RES:ACT_CREATE-->', $txt );
} else { // update
	$txt = str_replace( '<!--PAR:SUBMIT_BUTTON_TEXT-->', '<!--RES:ACT_UPDATE-->', $txt );
}
$txt = str_replace( '<!--PAR:MODE-->', inputvar( 'mode', $mode, 'hidden' ), $txt );
$err = count($errors) > 0 ? "onLoad='javascript:alert(\"".implode('\n',$errors)."\")'" : ''; // \n is literal for JavaScript
print HtmlDocument::buildDocument( $txt, true, $err );

class AdmOutputDeviceApp
{
	public function buildOutputDeviceFromHttpParams()
	{
		require_once BASEDIR.'/server/dataclasses/OutputDevice.php';
		$obj = new OutputDevice();
		
		$obj->Id              = @intval($_REQUEST['id']);
		$obj->Name            = @trim($_REQUEST['name']);
		$obj->Description     = @trim($_REQUEST['description']);
		
		$obj->LandscapeWidth  = @intval($_REQUEST['landscapewidth']);
		$obj->LandscapeHeight = @intval($_REQUEST['landscapeheight']);
		$obj->PortraitWidth   = @intval($_REQUEST['portraitwidth']);
		$obj->PortraitHeight  = @intval($_REQUEST['portraitheight']);
		
		$obj->PreviewQuality  = @intval($_REQUEST['previewquality']);
		$obj->LandscapeLayoutWidth = @floatval($_REQUEST['landscapelayoutwidth']);
		$obj->PixelDensity    = @intval($_REQUEST['pixeldensity']);
		$obj->PngCompression  = @intval($_REQUEST['pngcompression']);

		$obj->TextViewPadding = @trim($_REQUEST['textviewpadding']);

		return $obj;
	}

	public function fillFormWithOutputDevice( $txt, $device )
	{
		$txt = str_replace( '<!--PAR:ID-->',              inputvar( 'id',              $device->Id ), $txt );
		$txt = str_replace( '<!--PAR:NAME-->',            inputvar( 'name',            $device->Name ), $txt );
		$txt = str_replace( '<!--PAR:DESCRIPTION-->',     inputvar( 'description',     $device->Description, 'area'), $txt );
		
		$txt = str_replace( '<!--PAR:LANDSCAPEWIDTH-->',  inputvar( 'landscapewidth',  $device->LandscapeWidth, 'small' ), $txt );
		$txt = str_replace( '<!--PAR:LANDSCAPEHEIGHT-->', inputvar( 'landscapeheight', $device->LandscapeHeight, 'small' ), $txt );
		$txt = str_replace( '<!--PAR:PORTRAITWIDTH-->',   inputvar( 'portraitwidth',   $device->PortraitWidth, 'small' ), $txt );
		$txt = str_replace( '<!--PAR:PORTRAITHEIGHT-->',  inputvar( 'portraitheight',  $device->PortraitHeight, 'small' ), $txt );

		$txt = str_replace( '<!--PAR:PREVIEWQUALITY-->',  inputvar( 'previewquality',  $device->PreviewQuality, 'small' ), $txt );
		$txt = str_replace( '<!--PAR:LANDSCAPELAYOUTWIDTH-->', inputvar( 'landscapelayoutwidth', $device->LandscapeLayoutWidth, 'small' ), $txt );
		$txt = str_replace( '<!--PAR:PIXELDENSITY-->',    inputvar( 'pixeldensity',    $device->PixelDensity, 'small' ), $txt );
		$txt = str_replace( '<!--PAR:PNGCOMPRESSION-->',  inputvar( 'pngcompression',  $device->PngCompression, 'small' ), $txt );
		$txt = str_replace( '<!--PAR:TEXTVIEWPADDING-->', inputvar( 'textviewpadding', $device->TextViewPadding ), $txt );

		return $txt;
	}
}