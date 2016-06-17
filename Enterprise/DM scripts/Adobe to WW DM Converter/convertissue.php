<?php


/****************************************************************************
   Copyright 2009-2011 WoodWing Software BV

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

ini_set('display_errors', '1');	set_time_limit( 3600 ); /* max time 3600 seconds, 1 hour */

$start = microtime(true);

// Change these settings to match you local setup
$folder_IN	= dirname(__FILE__) . '/IN/'; // end with slash
$folder_OUT = dirname(__FILE__) . '/OUT/'; // end with slash
$folder_ZIP = dirname(__FILE__) . '/ZIP/'; // end with slash
$folder_LIBRARY = dirname(__FILE__) . '/LIBRARY/'; // end with slash

$zip_URL = $_SERVER['HTTP_REFERER'] . "ZIP/magazine.zip";

// The color of the page image when a full page web assets is used in RGB values
$webAssetPlaceholderBackgroundColor = array( 255, 255, 255 );

// Option to turn on that a three sixty view is created in a hotspot
// This way the 360 is opened when clicked on the page object
// otherwise the 360 will start immediatly
$threeSixtyOnHotSpot = true;

$pageQuality = 80; // GD page quality 0..100
$maxPages = 99999;

// The toc intent
$tocIntent = "TOC";
// Regular expression to find the item for the toc intent
$findTocIntent = "(0100_toc).*";

$integerIDs = array();
$nextID = 1;

deleteFolderTree( $folder_OUT );

$converter = new IssueConverter();
$wwXML  = $converter->Convert( $folder_IN.'Folio.xml' );

// Via DOM documnt to get it fomatted:
$dom = dom_import_simplexml($wwXML)->ownerDocument; 
$dom->formatOutput = true; 

file_put_contents( $folder_OUT.'magazine.xml', $dom->saveXML() );

@mkdir( $folder_ZIP );

if( extension_loaded('zip') ) {
		
	// Create new ZIP archive on disk
	$zip = new ZipArchive();
	$issueArchive = $folder_ZIP.'magazine.zip';
	if( $zip->open($issueArchive, ZIPARCHIVE::CREATE) !== TRUE ) {
		echo "Could not create archive file ".$issueArchive . "<BR/>";
	}
	else {
		addFolderToZip( $folder_OUT, $zip );
		// Save and close the ZIP archive
		$zip->close();
		clearstatcache();
	}
}
else {
	// Recompiling PHP (to include the ZIP library) is something to avoid.
	// For Mac and Linux, we can pretty easily fall back at the command line.
	
	$issueArchive = $folder_ZIP.'magazine.zip';
	$issueArchiveFile = basename($issueArchive);

	$issueArchivePath = dirname($issueArchive);

	chdir( $issueArchivePath );
	
	// First escape the first part of the shell command shell command and then add the directory list
	// The directory list is added later and not escaped otherwise it is not working * becomes \*
	$cmd = escapeshellcmd( "zip -pr $issueArchiveFile " ) . "*";
	exec( $cmd );
}

$end = microtime(true);
print '<h1>Conversion Done..</h1>';
//printf( '<h1>Conversion Done (took %d seconds)..</h1>', (int)($end-$start) );
print 'Download magazine.zip from: <a href='.$zip_URL.'>'.$zip_URL.'</a>';
exit;

class IssueConverter {

	public function Convert( $file ) {
		global $maxPages;
		$adobeXML = simplexml_load_file( $file );
		
		$wwXML = new SimpleXMLElement('<issue></issue>');
		$wwXML->addAttribute( "domversion", "1.7" );
		$wwXML->addAttribute( "systemname", "PHP Adobe .folio converter" );
		$wwXML->addAttribute( "systemversion", "1.0" );
		$wwXML->addAttribute( "engineversion", "1.0" );
		$wwXML->addAttribute( "id", $adobeXML['id'] );
		$wwXML->addChild( "issuedescription", $adobeXML->metadata->description );
		
		$wwXML->addChild( 'devicelandscapewidth', $adobeXML->targetDimensions->targetDimension['wideDimension']);
		$wwXML->addChild( 'devicelandscapeheight', $adobeXML->targetDimensions->targetDimension['narrowDimension']);
		$wwXML->addChild( 'deviceportraitwidth', $adobeXML->targetDimensions->targetDimension['narrowDimension']);
		$wwXML->addChild( 'deviceportraitheight', $adobeXML->targetDimensions->targetDimension['wideDimension']);
		
		$wwItems = $wwXML->addChild( 'items' );
		
		// First copy the HTMLResources (if available) folder to the images output folder otherwise the web assets don't work
		global $folder_IN, $folder_OUT;
		if ( file_exists($folder_IN . 'HTMLResources') ) {
			recurse_copy($folder_IN . 'HTMLResources', $folder_OUT . 'images/HTMLResources');
		}
		
		// The folio format prior to 1.7.0
		if ( isset( $adobeXML->sections->section ) ) {
			$pageCount = 0;
			foreach( $adobeXML->sections->section as $section ) {
				foreach( $section->contentStacks->contentStack as $contentStack ) {
					if( $pageCount < $maxPages ) {
						$wwItem = $wwItems->addChild( 'item' );
						
						$this->handleMetaData( $wwItem, $contentStack, $section );
						$wwHPages=array(); $wwVPages=array();
						$this->handlePages( $wwItem, $contentStack, $wwHPages, $wwVPages );
						$this->handleOverlays( $wwItem, $contentStack, $wwHPages, $wwVPages );
						$pageCount++;
						
						$wwItem->addChild( 'images' );
						$wwItem->addChild( 'elements' );
					$wwItem->addChild( 'stylesheet' );
					}
				}
			}
		} else { // Since folio format 1.7.0 there is a separate Folio.xml for each contentStack		
			$pageCount = 0;
			foreach( $adobeXML->contentStacks->contentStack as $mainContentStack ) {
				global $folder_IN;
				$adobeContentStackXML = simplexml_load_file( $folder_IN . $mainContentStack['id'] . DIRECTORY_SEPARATOR . 'Folio.xml' );
				
				$adobeContentStackXML = $adobeContentStackXML->contentStacks->contentStack;
				if( $pageCount < $maxPages ) {
					$wwItem = $wwItems->addChild( 'item' );
						
					$this->handleMetaData( $wwItem, $adobeContentStackXML );
					$wwHPages=array(); $wwVPages=array();
					// In the new structure the filepaths of the contents stack are relative to the contentstack
					// $mainContentStack['id'] . DIRECTORY_SEPARATOR is the folder of the contentstack
					$this->handlePages( $wwItem, $adobeContentStackXML, $wwHPages, $wwVPages, $mainContentStack['id'] . DIRECTORY_SEPARATOR );
					$this->handleOverlays( $wwItem, $adobeContentStackXML, $wwHPages, $wwVPages, $mainContentStack['id'] . DIRECTORY_SEPARATOR );
					$pageCount++;
						
					$wwItem->addChild( 'images' );
					$wwItem->addChild( 'elements' );
					$wwItem->addChild( 'stylesheet' );
				}
			}
		}
		
		return $wwXML;
	} // function Convert
		
	private function handleMetaData( $wwItem, $contentStack, $section = null )
	{
		$wwItem->addAttribute( 'id', getID( (string)$contentStack['id'] ) );
		$wwItem->addChild( 'id', getID( (string)$contentStack['id'] ) );
		$wwItem->addChild( 'category', htmlspecialchars($contentStack->content->regions->region->metadata->kicker) );
		//if( $section ) {
		//	$wwItem->addChild( 'category', $section['name'] );
		//}
		$wwItem->addChild( 'title', htmlspecialchars($contentStack->content->regions->region->metadata->kicker) );
		$wwItem->addChild( 'description', htmlspecialchars($contentStack->content->regions->region->metadata->author) );
		$wwItem->addChild( 'author', '' );
		
		global $tocIntent, $findTocIntent;
		
		$intent = "";
		if ( preg_match("/$findTocIntent/", $contentStack['id']) ) {
			$intent = $tocIntent;
		}
		
		$wwItem->addChild( 'intent', $intent );		// $contentStack->content->regions->region->metadata->tags );
		
	} // Funtion handleMetaData
		
	private function handlePages( $wwItem, $contentStack, &$wwHPages, &$wwVPages, $fileDirectory = "" )
	{
		$wwPages = $wwItem->addChild( 'pages' );
		$hPages = array(); $vPages = array();

		// Adobe lists first all portrait followed by all landscape, in WW model
		// the orientations are part of one page node, so first collect all
		// h and v pages to combine them later on.
		// Initially Adobe used <imageasset>, later they moved to <asset>
		foreach( $contentStack->content->assets->imageAsset as $imageAsset ) {
			if( $imageAsset['landscape'] == 'true' ) {
				$hPages[] = $imageAsset;
			} else {
				$vPages[] = $imageAsset;
			}
		}
		foreach( $contentStack->content->assets->asset as $imageAsset ) {
			if( $imageAsset['landscape'] == 'true' ) {
				$hPages[] = $imageAsset;
			} else {
				$vPages[] = $imageAsset;
			}
		}

		// When smooth scrolling is enabled, we combine the different pages so we get a long page that our apps understand.
		// Else we export the pages as "normal" pages.
		if ( isset($contentStack["smoothScrolling"]) && $contentStack["smoothScrolling"] == "always" ) {
			$hPreviews = array();
			$vPreviews = array();

			$hThumb = null;
			$vThumb = null;

			$pageCount = max( count($hPages), count($vPages) );
			for( $i=0; $i < $pageCount; $i++ ) {
				if( isset($hPages[$i]) ) { // number of H and V pages could be different
					$hPage = $hPages[$i];
					$assetRenditions = $hPage->xpath("assetRendition[@role='content']");
					$contentRendition = $assetRenditions[0];

					$assetRenditions = $hPage->xpath("assetRendition[@role='thumbnail']");
					$thumbRendition = $assetRenditions[0];

					$hPreviews[] = $contentRendition['source'];

					// We use the first "page" as thumb
					if ( is_null($hThumb) ) $hThumb = $thumbRendition['source'];
				}
				if( isset($vPages[$i]) ) { // number of H and V pages could be different
					$vPage = $vPages[$i];
					$assetRenditions = $vPage->xpath("assetRendition[@role='content']");
					$contentRendition = $assetRenditions[0];

					$assetRenditions = $vPage->xpath("assetRendition[@role='thumbnail']");
					$thumbRendition = $assetRenditions[0];

					$vPreviews[] = $contentRendition['source'];

					// We use the first "page" as thumb
					if ( is_null($vThumb) ) $vThumb = $thumbRendition['source'];
				}
			}

			$wwPage = $wwPages->addChild( 'page' );

			$portraitBounds = $contentStack->content->regions->region->portraitBounds->rectangle;
			$landscapeBounds = $contentStack->content->regions->region->landscapeBounds->rectangle;

			if( !empty($hPreviews) ) {
				$wwHPage = $wwPage->addChild( 'horizontalpage' );
				$wwHPages[] = $wwHPage;
				$wwHPage->addChild( 'width', $landscapeBounds['width'] );
				$wwHPage->addChild( 'height', $landscapeBounds['height'] );
				$wwHPage->addChild( 'objects' );
				$wwHPage->addChild( 'pthumb', jpgFileName($fileDirectory . $hThumb, (string)$wwItem['id']) );
				// Create one long page of the different pages
				$hPreview = merge_pages($hPreviews, $landscapeBounds['width'], $landscapeBounds['height'], (string)$wwItem['id'], ((string)$wwItem['id'] . '_preview_l.jpg'), $fileDirectory);
				$wwHPage->addChild( 'ppreview', jpgFileName($fileDirectory . $hPreview, (string)$wwItem['id']) );
			}

			if( !empty($vPreviews) ) {
				$wwVPage = $wwPage->addChild( 'verticalpage' );
				$wwVPages[] = $wwVPage;
				$wwVPage->addChild( 'width', $portraitBounds['width'] );
				$wwVPage->addChild( 'height', $portraitBounds['height'] );
				$wwVPage->addChild( 'objects' );
				$wwVPage->addChild( 'pthumb', jpgFileName($fileDirectory . $vThumb, (string)$wwItem['id']) );
				// Create one long page of the different pages
				$vPreview = merge_pages($vPreviews, $portraitBounds['width'], $portraitBounds['height'], (string)$wwItem['id'], ((string)$wwItem['id'] . '_preview_p.jpg'), $fileDirectory);
				$wwVPage->addChild( 'ppreview', jpgFileName($fileDirectory . $vPreview, (string)$wwItem['id']) );
			}
		} else {
			$pageCount = max( count($hPages), count($vPages) );
			for( $i=0; $i < $pageCount; $i++ ) {
				$wwPage = $wwPages->addChild( 'page' );
				if( isset($hPages[$i]) ) { // number of H and V pages could be different
					$hPage = $hPages[$i];
					$assetRenditions = $hPage->xpath("assetRendition[@role='content']");
					$contentRendition = $assetRenditions[0];

					$assetRenditions = $hPage->xpath("assetRendition[@role='thumbnail']");
					$thumbRendition = $assetRenditions[0];

					$wwHPage = $wwPage->addChild( 'horizontalpage' );
					$wwHPages[] = $wwHPage;
					$wwHPage->addChild( 'width', $contentRendition['width'] );
					$wwHPage->addChild( 'height', $contentRendition['height'] );
					$wwHPage->addChild( 'objects' );
					if ( $hPages[$i]['type'] == 'web' ) {
						$this->handleWebAsset( $hPage, $wwHPage, $wwItem, true );
					} else {
						$wwHPage->addChild( 'pthumb', jpgFileName($fileDirectory . $thumbRendition['source'], (string)$wwItem['id']) );
						$wwHPage->addChild( 'ppreview', jpgFileName($fileDirectory . $contentRendition['source'], (string)$wwItem['id']) );
					}
				}
				if( isset($vPages[$i]) ) {  // number of H and V pages could be different
					$vPage = $vPages[$i];
					$assetRenditions = $vPage->xpath("assetRendition[@role='content']");
					$contentRendition = $assetRenditions[0];

					$assetRenditions = $vPage->xpath("assetRendition[@role='thumbnail']");
					$thumbRendition = $assetRenditions[0];

					$wwVPage = $wwPage->addChild( 'verticalpage' );
					$wwVPages[] = $wwVPage;
					$wwVPage->addChild( 'width', $contentRendition['width'] );
					$wwVPage->addChild( 'height', $contentRendition['height'] );
					$wwVPage->addChild( 'objects' );
					if ( $vPages[$i]['type'] == 'web' ) {
						$this->handleWebAsset( $vPage, $wwVPage, $wwItem, false );
					} else {
						$wwVPage->addChild( 'pthumb', jpgFileName($fileDirectory . $thumbRendition['source'], (string)$wwItem['id']) );
						$wwVPage->addChild( 'ppreview', jpgFileName($fileDirectory . $contentRendition['source'], (string)$wwItem['id']) );
					}
				}
			}
		}
	} // Function handlePages
	
	private function handleWebAsset( $adobeAsset, $wwPage, $wwItem, $horizontal ) 
	{
		$pPreview = createWebAssetPlaceholder( (string)$wwItem['id'], $horizontal, $wwPage->width, $wwPage->height );
		$pThumb = $this->getWebAssetThumb( $wwItem, $adobeAsset, $horizontal );
		
		$wwPage->addChild( 'pthumb', $pThumb );
		$wwPage->addChild( 'ppreview', $pPreview);
		
		$webAsset = copyFileIfNeeded( $adobeAsset["source"], (string)$wwItem['id'] );
		// Copy the rest of the files also
		// Exclude the webpage itself, the OverlayResources and StackResources directories and the Issue.xml file
		global $folder_IN, $folder_OUT;
		recurse_copy( $folder_IN . dirname($adobeAsset["source"]), $folder_OUT . dirname($webAsset), array( basename($adobeAsset["source"]), "OverlayResources", "StackResources", "Issue.xml") );
		
		$overlay = array();
		$overlay['id'] = (string)$wwItem['id'];
		$object = $this->handleObjectBasics( $overlay, $wwPage, 0, 0, $wwPage->width, $wwPage->height );
		$object->type = 'webelement';
		$webElement = $object->addChild( "webelement" );
		$webElement->addChild( 'url', 'bundle://'.$webAsset );
		$webElement->addChild( 'target', 'inline' );
		$webElement->addChild( 'allowzoom', 'false' );
		$webElement->addChild( 'allowpageswipe', 'horizontal' );
		
		$this->resolveNavToLinksForWebOverlay( $webAsset );
	}
	
	private function getWebAssetThumb( $wwItem, $adobeAsset, $horizontal )
	{
		global $folder_IN;
		
		$tocThumb = dirname($adobeAsset["source"]) . "/toc.png";
		$scubberThumb = dirname($adobeAsset["source"]) . "/scrubberthumbnail_" . (($horizontal) ? "l" : "p") . ".png";
		
		$newThumb = "images/" . $wwItem['id'] . "/thumb_1_" . $wwItem['id'] . "_" . (($horizontal) ? "h" : "v") .  ".jpg";
		if ( file_exists($folder_IN . $scubberThumb) ) {
			copyFileIfNeededTo( $scubberThumb, $newThumb );
			return $newThumb;
		} else if ( file_exists( $folder_IN . $tocThumb ) ) {
			copyFileIfNeededTo( $tocThumb, $newThumb );
			return $newThumb;
		}
		
		return "";
	}
	
	private function resolveNavToLinksForWebOverlay( $webAsset )
	{
		global $folder_OUT;
		
		$webAssetContents = file_get_contents( $folder_OUT . $webAsset );
		
		$matches = array();
		preg_match_all("/navto:\/\/([0-9a-zA-Z_-]*)/", $webAssetContents, $matches);
		if ( isset($matches[1]) && is_array($matches[1]) ) {
			foreach ( $matches[1] as $match ) {
				$id = getID($match);
				$webAssetContents = preg_replace("/navto:\/\/$match/", "ww://storylink?itemid=" . $id, $webAssetContents);
			}			
		}
		
		file_put_contents( $folder_OUT . $webAsset, $webAssetContents );
	}
	
	private function handleOverlays( $wwItem, $contentStack, $wwHPages, $wwVPages, $fileDirectory = "" )
	{
		// Objects on the page are listed as overlays, the coordinates determine on 
		// which page an object belongs
		if( isset( $contentStack->content->overlays->overlay )) {
			foreach( $contentStack->content->overlays->overlay as $overlay ) {
				// First figure out where this overlay belongs: 
				// which page: index and hor. vs vert:
				// Add overlay to right page (h vs v and page index)
				// Note: slideshows are an exception, can be combined portrait&landscape, see below
				if( isset($overlay->portraitBounds) ) {
					// HACK: first issue of Wired has 'invalid' coordinates for toy store imagepan.
					// and Dec has same pronblem for Olympic coders
					// TODO: Better solution is probably to also take height in consideration to determine page
					// fix this here:
					if( ($overlay['id'] == 'pixar_imagepan_V' && $overlay->portraitBounds->rectangle['y'] == '7165' ) ||
						($overlay['id'] == 'solutions' && $overlay->portraitBounds->rectangle['y'] == '7136' ))  {
						 $overlay->portraitBounds->rectangle['y'] = '7168';
					}
					
					// Long pages normally consist of several preview images, but in some cases not
					if( count($wwVPages) > 1 ) {
						$wwPage = findPageForPosition( $wwVPages, $overlay->portraitBounds->rectangle['y'], 1024 );
					} else {
						$wwPage = $wwVPages[0];
					}
					if( !$wwPage ) {
						print( '<h1>Found overlay for non existent V page:'.$overlay['id'].'</h1>' );
						print '<pre>';
						print_r( $overlay);
						print_r( $wwVPages );
						continue;
					}
					$x = $overlay->portraitBounds->rectangle['x'];
					if( count($wwVPages) > 1 ) {
						$y = $overlay->portraitBounds->rectangle['y'] % 1024;
					} else {
						$y = $overlay->portraitBounds->rectangle['y'];
					}
					$width = $overlay->portraitBounds->rectangle['width'];
					$height = $overlay->portraitBounds->rectangle['height'];
					$this->handleOverlay( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $wwHPages, $wwVPages, true, $fileDirectory );
				}
				if( isset($overlay->landscapeBounds) ) {
					// HACK: first issue of Wired has invalid coordinates for toy store imagepan.
					// and Dec has same pronblem for Olympic coders
					// TODO: Better solution is probably to also take height in consideration to determine page
					// fix this here:
					if( ($overlay['id'] == 'pixar_imagepan_H' && $overlay->landscapeBounds->rectangle['y'] == '5374' ) ||
						($overlay['id'] == 'solutions' && $overlay->landscapeBounds->rectangle['y'] == '5358' ) ) {
						 $overlay->landscapeBounds->rectangle['y'] = '5376';
					}
					$pageHeight = $wwHPages[0]->height;
					
					if( count($wwHPages) > 1 ) {
						$wwPage = findPageForPosition( $wwHPages, $overlay->landscapeBounds->rectangle['y'], $pageHeight );
					} else {
						$wwPage = $wwHPages[0];
					}
					if( !$wwPage ) {
						print( '<h1>Found overlay for non existent H page:'.$overlay['id'].'</h1>' );
						print '<pre>';
						print_r( $overlay);
						continue;
					}
					$x = $overlay->landscapeBounds->rectangle['x'];
					$y = $overlay->landscapeBounds->rectangle['y'] % $pageHeight;
					$width = $overlay->landscapeBounds->rectangle['width'];
					$height = $overlay->landscapeBounds->rectangle['height'];
					$this->handleOverlay( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $wwHPages, $wwVPages, false, $fileDirectory );
				}
			}			
		}
	} // Function handleOverlays
	
	private function handleOverlay( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $wwHPages, $wwVPages, $portrait, $fileDirectory = "" )
	{
		// treat type of overlay:
		switch( $overlay['type'] ) {
			case 'hyperlink':
				$this->handleHyperLink( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $fileDirectory );
				break;
			case 'video':
				$this->handleVideo( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $portrait, $fileDirectory );
				break;
			case 'audio':
				$this->handleAudio( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $fileDirectory );
				break;
			case 'slideshow':  // Adobe slideshow is what WW calls hotspot
				$this->handleSlideShow( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $wwHPages, $wwVPages, $fileDirectory );
				break;
			case '360':
				$this->handle360( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $portrait, $fileDirectory );
				break;
			case 'imagepan':
				$this->handleImagePan( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $fileDirectory );
				break;
			case 'webview':
				$this->handleWebView( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $fileDirectory );
				break;
			default:
				print( "<h1>UNKNOWN Overlay: ".$overlay['type']."</h1>" );
				continue;
		}
	} // Function handleOverlay
	
	private function handleHyperLink( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $fileDirectory = "" )
	{
		if( strncmp( $overlay->data->url, 'navto://', strlen('navto://')) == 0 ){
			// WW Story link:
			$object = $this->handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height );
			$object->type = 'storylink';
			$storyLink = $object->addChild( 'storylink' );
			// Get last part of url which is the story id:
			$urlParts = explode( '/', $overlay->data->url );
			if( !empty($urlParts) ) {
				// URL could include page index, so get that out of it:
				$up = $urlParts[count($urlParts)-1];
				$up = explode( '#', $up );
				$storyLink->addChild( 'itemid', getID((string)$up[0]) );
				if( count( $up ) == 1 ) {
					// No page index in URL:
					$storyLink->addChild( 'pageindex', 1 );
				} else {
					// Page index has 1 decimal .0 and starts for Adobe at 0, our at 1:
					$storyLink->addChild( 'pageindex', intval($up[1])+1 );
				}
			}
		} else {
			// WW Weblink:
			$url = htmlspecialchars($overlay->data->url);

			// If the url doens't start with http://, https:// or mailto: create a local bundle url
			if ( !preg_match("/https?:/", $url) && !preg_match("/mailto:/", $url) ) {
				$url = 'bundle://images/' . $url;
			}
			$object = $this->handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height );
			$object->type = 'webelement';
			$webElement = $object->addChild( "webelement" );
			$webElement->addChild( 'url', $url );
			$webElement->addChild( 'target', ($overlay->data->openInApp == "true") ? "popup" : "external"  );
			$webElement->addChild( 'allowzoom', 'false' );
		}
	} // HandleHyperLink
	
	private function handleVideo( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $portrait, $fileDirectory = "" )
	{
		$object = $this->createVideoPlaceholder( $overlay, $wwPage, $x, $y, $width, $height, $portrait, (string)$wwItem['id'] );
		
		$object->type = 'movie';
		$movie = $object->addChild( 'movie' );
		// if local file, copy to out folder
		if( strncmp( $overlay->data->videoUrl, 'http://', strlen('http://')) != 0 ){
			$movie->addChild( 'url', copyFileIfNeeded( $fileDirectory . $overlay->data->videoUrl, (string)$wwItem['id'] ) );
		} else {
			$movie->addChild( 'url', $overlay->data->videoUrl );
		}
		
		if ( $overlay->data->playInContext == "false" ) {
			$x = 0;
			$y = 0;
			$width = $wwPage->width;
			$height = $wwPage->height;
		}
		
		$movie->addChild( 'x', $x );
		$movie->addChild( 'y', $y );
		$movie->addChild( 'width',  $width );
		$movie->addChild( 'height', $height );
		$movie->addChild( 'autoplay', ($overlay->data->playInContext == "false") ? "true" : $overlay->data->autoStart );
		$movie->addChild( 'autofullscreen', 'false' ); // playInContext is now used to place the movie on a hotspot (BZ# 23398)
		$movie->addChild( 'moviecontrols', ($overlay->data->playInContext == "false") ? "true" : $overlay->data->showControlsByDefault );
	}
	
	private function createVideoPlaceholder( $overlay, $wwPage, $x, $y, $width, $height, $portrait, $itemId )
	{
		$object = $this->handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height );
		if ( $overlay->data->playInContext == "false" ) {
			$object->type = 'hotspot';
			$hotspot = $object->addChild( 'hotspot' );
			
			$hotspot->addChild( 'url', createTransparantPNG((int)$width, (int)$height, $itemId, $itemId . '_hs_' . (($portrait) ? 'p' : 'l') . '.png') );
			$popup = $hotspot->addChild( 'popup' );
			$popup->addChild( 'x', 0 );
			$popup->addChild( 'y', 0 );
			$popup->addChild( 'width', $wwPage->width );
			$popup->addChild( 'height', $wwPage->height );
			$popup->addChild( 'url', createTransparantPNG((int)$wwPage->width, (int)$wwPage->height, $itemId, $itemId . '_hp_' . (($portrait) ? 'p' : 'l') . '.png') );
			
			$closeWidth = $closeHeight = 0;
			$movieCloseButton = getMovieCloseButton( $closeWidth, $closeHeight, $itemId, $itemId . '_hc_' . (($portrait) ? 'p' : 'l') . '.png');
			
			$closeButtonMargin = 10;
			
			$popup = $hotspot->addChild( 'close' );
			$popup->addChild( 'x', $wwPage->width - $closeWidth - $closeButtonMargin );
			$popup->addChild( 'y', $closeButtonMargin );
			$popup->addChild( 'width', $closeWidth );
			$popup->addChild( 'height', $closeHeight );
			$popup->addChild( 'url', $movieCloseButton );
			
			$objects = $hotspot->addChild( 'objects' );
			$movie = $this->handleObjectBasics( $overlay, $hotspot, 0, 0, $wwPage->width, $wwPage->height );
			
			return $movie;
		} else {
			return $object;
		}
	}

	private function handleAudio( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $fileDirectory = "" )
	{
		$object = $this->handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height );
		$object->type = 'audio';
		$movie = $object->addChild( 'audio' );
		if( strncmp( $overlay->data->audioUrl, 'http://', strlen('http://')) != 0 ){
			$movie->addChild( 'url', copyFileIfNeeded( $fileDirectory . $overlay->data->audioUrl, (string)$wwItem['id'] ) );
		} else {
			$movie->addChild( 'url', $overlay->data->audioUrl );
		}
		$movie->addChild( 'autoplay', 'false' );
	}
	
	private function handle360( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $portrait, $fileDirectory = "" )
	{
		// Some hacks for 'bugs' in Wired XML
		if( (string)$wwItem['id'] == '18.12ST.whatsinside' ) {
			$width	= 533;
			$height = 438;
		}
		
		$widgetId = $overlay['id'] . "_" . uniqid();
		
		$object = $this->handle360PlaceHolder( $overlay, $wwPage, $wwItem, $x, $y, $width, $height, $portrait );
		$object->type = 'webelement';
		$webElement = $object->addChild( "webelement" );
		$webElement->addChild( 'url', 'bundle://widgets/'.$widgetId.'/360/index.html' );
		$webElement->addChild( 'target', 'inline' );
		$webElement->addChild( 'allowzoom', 'false' );
		
		$object->addChild( 'link', 'bundle://widgets/'.$widgetId.'/360/index.html' );
		
		global $folder_LIBRARY, $folder_OUT, $folder_IN;
		if( !file_exists( $folder_OUT.'widgets/'.$widgetId.'/360' )) {
			recurse_copy( $folder_LIBRARY.'WW/360', $folder_OUT.'widgets/'.$widgetId.'/360' );
		}
		
		$files = array();
		$imageCount = 0;
		foreach( $overlay->data->overlayAsset as $overlayAsset ) {
			$assetName = 'assets/' . sprintf( "image_%03d.jpg", $imageCount);
			$file = $folder_OUT.'widgets/'.$widgetId.'/360/' . $assetName;
			if( $portrait && $overlayAsset['landscape'] == "false" ) {
				copyIfNotExist( $folder_IN.$fileDirectory.$overlayAsset, $file );
				$imageCount++;
				$files[] = $assetName;
			} elseif( !$portrait && $overlayAsset['landscape'] == "true" ) {
				copyIfNotExist( $folder_IN.$fileDirectory.$overlayAsset, $file );
				$imageCount++;
				$files[] = $assetName;
			}
		}
		
		$manifest = new DOMDocument();
		$manifest->load($folder_OUT.'widgets/'.$widgetId.'/360/manifest.xml');
		
		$xPath = new DOMXPath($manifest);
		$fileProperties = $xPath->query("/manifest/widget/properties/fileProperty[@id=\"360files\"]");
		foreach ( $fileProperties as $fileProperty ) {
			// Create a new fileListProperty that will replace the existing fileProperty
			$fileListProperty = $manifest->createElement('fileListProperty');
					
			// Copy all attributes to the new element
			foreach($fileProperty->attributes as $attr)
				$fileListProperty->setAttribute($attr->nodeName,$attr->value);
					
			// Deepcopy all child element (except the value (=last) element)
			if( $fileProperty->childNodes )
			{
				foreach($fileProperty->childNodes as $child) {
					if( $child->localName != 'value' ) {
						$fileListProperty->appendChild( $child->cloneNode(true) );
					}
				}
			}
			
			// Add a new values element that will contain the paths
			$newValues = $fileListProperty->appendChild( $manifest->createElement( 'values' ) );
			foreach($files as $index => $filePath) {
				$newItem = $newValues->appendChild( $manifest->createElement( 'listItem', $filePath ) );
				// The id attribute is mandatory, set to an empty string for now
				$newItem->setAttribute( 'id', '360_'.$index );
			}
			
			$fileProperty->parentNode->replaceChild( $fileListProperty, $fileProperty );
		}
		
		$manifest->save($folder_OUT.'widgets/'.$widgetId.'/360/config.xml');
		$json = xmlToJson($manifest);
		file_put_contents($folder_OUT.'widgets/'.$widgetId.'/360/config.json', $json);
		$manifest = null;
		unlink($folder_OUT.'widgets/'.$widgetId.'/360/manifest.xml');
	}
	
	private function handle360PlaceHolder( $overlay, $wwPage, $wwItem, $x, $y, $width, $height, $portrait )
	{
		global $threeSixtyOnHotSpot;
		
		if ( $threeSixtyOnHotSpot ) {
			$object = $this->handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height );
			$object->type = 'hotspot';
			$hotspot = $object->addChild( 'hotspot' );
			
			$hotspot->addChild( 'url', createTransparantPNG((int)$width, (int)$height, (string)$wwItem['id'], (string)$wwItem['id'] . '_hs_' . (($portrait) ? 'p' : 'l') . '.png') );
			$popup = $hotspot->addChild( 'popup' );
			$popup->addChild( 'x',$x );
			$popup->addChild( 'y', $y );
			$popup->addChild( 'width', $width );
			$popup->addChild( 'height', $height );
			$popup->addChild( 'url', createTransparantPNG((int)$width, (int)$height, (string)$wwItem['id'], (string)$wwItem['id'] . '_hp_' . (($portrait) ? 'p' : 'l') . '.png') );
			
			$objects = $hotspot->addChild( 'objects' );
			$object = $objects->addChild( 'object' );
			$object->addChild( 'id', getID((string)$overlay['id']) );
			$object->addChild( 'x', 0 );
			$object->addChild( 'y', 0 );
			$object->addChild( 'width',  $width);
			$object->addChild( 'height', $height );
			
			return $object;
		} else {
			return $this->handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height );
		}
	}

	private function handleWebView( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $fileDirectory = "" )
	{
		$object = $this->handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height );
		$object->type = 'webelement';
		$webElement = $object->addChild( "webelement" );
		$webElement->addChild( 'url', 'bundle://'.$fileDirectory.$overlay->data->webViewUrl );
		$webElement->addChild( 'target', 'inline' );
		$webElement->addChild( 'allowzoom', 'false' );
		
		// Copy complete folder with html file:
		global $folder_IN, $folder_OUT;
		$folder = dirname($overlay->data->webViewUrl);
		recurse_copy( $folder_IN.$folder, $folder_OUT.$folder );
		
		// Resolve the navto:// links in the file to WW storylinks
		$this->resolveNavToLinksForWebOverlay( $overlay->data->webViewUrl );
	}
	
	private function handleImagePan( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $fileDirectory = "" )
	{
// TODO: ImagePan now translated to scrollable area which means it doesn't zook, just pans
// further to be able to activate it like Adobe does we could wrap it into an hotspot.
		$object = $this->handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height );
		$object->type = 'scrollarea';
		$scrollArea = $object->addChild( 'scrollarea' );
		$scrollArea->addChild( 'url', copyFileIfNeeded( $fileDirectory.$overlay->data->overlayAsset, (string)$wwItem['id'] ) );
		$contentCoordinates = $scrollArea->addChild( 'contentcoordinates' );
		
		if( isset($overlay->data->initialViewport->portraitBounds) ) {
// TODO: initial positioning
			$contentCoordinates->addChild( 'x', 0 );
			$contentCoordinates->addChild( 'y', 0 );
//			$contentCoordinates->addChild( 'x', $overlay->data->initialViewport->portraitBounds->rectangle['x'] );
//			$contentCoordinates->addChild( 'y', $overlay->data->initialViewport->portraitBounds->rectangle['y'] );
			$contentCoordinates->addChild( 'width', $overlay->data->initialViewport->portraitBounds->rectangle['width'] );
			$contentCoordinates->addChild( 'height', $overlay->data->initialViewport->portraitBounds->rectangle['height'] );
		} else {
// TODO: initial positioning
			$contentCoordinates->addChild( 'x', 0 );
			$contentCoordinates->addChild( 'y', 0 );
//			$contentCoordinates->addChild( 'x', $overlay->data->initialViewport->landscapeBounds->rectangle['x'] );
//			$contentCoordinates->addChild( 'y', $overlay->data->initialViewport->landscapeBounds->rectangle['y'] );
			$contentCoordinates->addChild( 'width', $overlay->data->initialViewport->landscapeBounds->rectangle['width'] );
			$contentCoordinates->addChild( 'height', $overlay->data->initialViewport->landscapeBounds->rectangle['height'] );
		}
	}
	
	private function handleSlideShow( $wwItem, $overlay, $wwPage, $x, $y, $width, $height, $wwHPages, $wwVPages, $fileDirectory = "" )
	{
		// Collect the various piece of 'slideshow' components:
		
		// Assets
		$overlayAssetsPortrait = array(); $overlayAssetsLandscape = array();
		foreach( $overlay->data->overlayAsset as $overlayAsset ) {
			$id = (string)$overlayAsset['id'];
			if( $overlayAsset['landscape'] == 'true' ) {
				$overlayAssetsLandscape[(string)$overlayAsset['id']] = $overlayAsset;
			} else {
				$overlayAssetsPortrait[(string)$overlayAsset['id']] = $overlayAsset;
			}
		}

		// Portrait layout
		if( isset($overlay->data->portraitLayout) ) {
			$firstPage = $wwVPages[0]; 
			$pageHeight = $firstPage->height;
			$this->handleSlideShowDetails(  $wwItem, $overlay, $wwPage, 
											$overlay->portraitBounds->rectangle['x'], $overlay->portraitBounds->rectangle['y'], 
											$overlayAssetsPortrait, 
											$overlay->data->portraitLayout->displayBounds->rectangle, 
											$wwVPages, $overlay->data->portraitLayout->buttons, $pageHeight, $fileDirectory );
		}
		
		// Landscape layout
		if( isset($overlay->data->landscapeLayout) ) {
			$firstPage = $wwHPages[0]; 
			$pageHeight = $firstPage->height;
			$this->handleSlideShowDetails(  $wwItem, $overlay, $wwPage,
											$overlay->landscapeBounds->rectangle['x'], $overlay->landscapeBounds->rectangle['y'], 
											$overlayAssetsLandscape, 
											$overlay->data->landscapeLayout->displayBounds->rectangle, 
											$wwHPages, $overlay->data->landscapeLayout->buttons, $pageHeight, $fileDirectory );
		}
	} // handleSlideShow

	private function handleSlideShowDetails( $wwItem, $overlay, $wwPage, $x, $y, $overlayAssets, $overlayDisplayBounds, $wwOrientedPages, $buttons, $screenHeight, $fileDirectory = "" )
	{
		// The slideshow overlay does not have to be specific portrait/landscape like the other overlays, get right portrait page:
		$wwPage = findPageForPosition( $wwOrientedPages, $y, $screenHeight );
		
		$firstButton = true;
		foreach( $buttons->button as $button ) {
			$object = $this->handleObjectBasics( $overlay, $wwPage, 
												 $x + $button->bounds->rectangle['x'],
												 $y%$screenHeight + $button->bounds->rectangle['y'],
												 $button->bounds->rectangle['width'],
												 $button->bounds->rectangle['height'] );
			$object->type = 'hotspot';
			$hotspot = $object->addChild( 'hotspot' );
			
			$hotspot->addChild( 'url', copyFileIfNeeded( $fileDirectory . $overlayAssets[(string)$button['defaultID']], (string)$wwItem['id']) );
			if( $firstButton ) {
				$hotspot->addChild( 'autopopup', 'true' );
				$firstButton = false;
			} else {
				$hotspot->addChild( 'autopopup', 'false' );
			}
			$popup = $hotspot->addChild( 'popup' );
			$popup->addChild( 'x', $overlayDisplayBounds['x'] + $x );
			$popup->addChild( 'y', $overlayDisplayBounds['y'] + $y%$screenHeight );
			$popup->addChild( 'width', $overlayAssets[(string)$button['targetID']]['width'] );
			$popup->addChild( 'height', $overlayAssets[(string)$button['targetID']]['height'] );
			$popup->addChild( 'url', copyFileIfNeeded( $fileDirectory . (string)$overlayAssets[(string)$button['targetID']], (string)$wwItem['id'] ) );
			if(  (string)$button['selectedID'] != '' ) {
				// We translate selected state to close button, not the best mapping, but for now easiest:
				$closeButton = $hotspot->addChild( 'close' );
				$closeButton->addChild( 'x', $x + $button->bounds->rectangle['x'] );
				$closeButton->addChild( 'y', $y%$screenHeight + $button->bounds->rectangle['y'] );
				$closeButton->addChild( 'width',  $button->bounds->rectangle['width'] );
				$closeButton->addChild( 'height', $button->bounds->rectangle['height'] );
				$closeButton->addChild( 'url', copyFileIfNeeded( $fileDirectory . $overlayAssets[(string)$button['selectedID']], (string)$wwItem['id'] ) );
			}
			
			$hotspot->addChild( 'objects' );
		}
	}	//handleSlideShowDetails
	
	private function handleObjectBasics( $overlay, $wwPage, $x, $y, $width, $height, $type = "" )
	{	
		$object = $wwPage->objects->addChild( 'object' );
		$object->addChild( 'id', getID((string)$overlay['id']) );
		$object->addChild( 'type', $type );
		$object->addChild( 'x', $x );
		$object->addChild( 'y', $y );
		$object->addChild( 'width',  $width);
		$object->addChild( 'height', $height );
		return $object;
	}
	
} // Class IssueConverter
	

function jpgFileName( $inFile, $storyID )
{
	global $folder_IN, $folder_OUT, $pageQuality;
	
	$pathParts = pathinfo( $inFile );
	$outFile = str_replace( '.png', '.jpg', $pathParts['basename'] );
	
	// If source was already .jpg both files are same and we just need to do copy:
	if( $outFile == $pathParts['basename'] ) {
		copyFileIfNeededTo( $inFile, "images/$storyID/$outFile" );
	} else {
		$dirName = dirname($folder_OUT."images/$storyID/$outFile");
		if( !file_exists($dirName) ) {
			mkdir( $dirName, 0777, true );
		}
		if( !file_exists($folder_OUT."images/$storyID/$outFile") ) {
			print( "Converting image: ".$folder_IN.$inFile."<br/>" );			
			$fileContents = file_get_contents($folder_IN.$inFile);
			$image = imagecreatefromstring($fileContents);// BZ#23474 - A page can be a png or jpeg. By loading the image this way, it doesn't matter
			$fileContents = "";
			if( !$image ) {
				print( "ERROR loading: ".$folder_IN.$inFile."<br/>" );
			}
			imagejpeg( $image, $folder_OUT."images/$storyID/$outFile", $pageQuality );
			imagedestroy($image);
		}
	}
	return "images/$storyID/$outFile";
}

function jpgThumb( $inFile, $outFile )
{
	global $folder_IN, $folder_OUT, $pageQuality;
	
	$dirName = dirname( $folder_OUT . $outFile );
	if( !file_exists($dirName) ) {
		mkdir( $dirName, 0777, true );
	}
	if( !file_exists($folder_OUT.$outFile) ) {
		print( "Converting thumb: ".$folder_IN.$inFile."<br/>" );			
		$image = imagecreatefrompng($folder_IN.$inFile);
		if( !$image ) {
			print( "ERROR loading: ".$folder_IN.$inFile."<br/>" );
		}
		imagejpeg( $image, $folder_OUT.$outFile, $pageQuality );
		imagedestroy($image);
	}
}

function createTransparantPNG( $width, $height, $storyID, $outFile )
{
	global $folder_OUT, $pageQuality;
	
	$dirName = dirname( $folder_OUT . "images/$storyID/$outFile" );
	if( !file_exists($dirName) ) {
		mkdir( $dirName, 0777, true );
	}
	
	// create a true colour, transparent image
 	// turn blending OFF and draw a background rectangle in our transparent colour 
	$image = imagecreatetruecolor( $width, $height );
	imagealphablending( $image, false );
	$transparentColor = imagecolorallocatealpha( $image, 255, 255, 255, 127);

	imagefilledrectangle( $image, 0, 0, $width, $height, $transparentColor );

	imagesavealpha( $image, true );
	imagepng( $image, $folder_OUT . "images/$storyID/$outFile" ); 
	imagedestroy( $image );

	return "images/$storyID/$outFile";
}

function getMovieCloseButton( &$width, &$height, $storyID, $outFile )
{
	global $folder_OUT;
	
	$image = imagecreatefrompng(dirname(__FILE__) . '/movie_close_button.png');
	$width = imagesx($image);
	$height = imagesy($image);
	imagedestroy( $image );
	
	copyIfNotExist(dirname(__FILE__) . '/movie_close_button.png', $folder_OUT."images/$storyID/$outFile");
	
	return "images/$storyID/$outFile";
}

function createWebAssetPlaceholder( $storyID, $horizontal, $width, $height )
{
	global $folder_OUT, $pageQuality, $webAssetPlaceholderBackgroundColor;
	
	$outFile = "preview_1_" . $storyID . "_" . ($horizontal ? "h" : "v") . ".jpg";
	$dirName = dirname($folder_OUT."images/$storyID/$outFile");
	if( !file_exists($dirName) ) {
			mkdir( $dirName, 0777, true );
	}
	if( !file_exists($folder_OUT."images/$storyID/$outFile") ) {			
		$image = imagecreatetruecolor( intval($width), intval($height) );
		$fillColor = imagecolorallocate($image, $webAssetPlaceholderBackgroundColor[0], $webAssetPlaceholderBackgroundColor[1], $webAssetPlaceholderBackgroundColor[2]);
		imagefilledrectangle($image, 0, 0, intval($width), intval($height), $fillColor);
		imagejpeg( $image, $folder_OUT."images/$storyID/$outFile", $pageQuality );
		imagedestroy($image);
	}
	
	return "images/$storyID/$outFile";
}

function copyFileIfNeeded( $infile, $storyID )
{
	$pathParts = pathinfo( $infile );
	$outfile = "images/$storyID/".$pathParts['basename'];
	
	copyFileIfNeededTo( $infile, $outfile );
	
//	return "images/$storyID/".rawurlencode($pathParts['basename']);
	return "images/$storyID/".$pathParts['basename'];
}


function copyFileIfNeededTo( $infile, $outfile )
{
	global $folder_IN, $folder_OUT;
	copyIfNotExist( $folder_IN.$infile, $folder_OUT.$outfile );
}

function copyIfNotExist( $src, $dst )
{
	if( !file_exists( $dst) ) {
		$dirName = dirname($dst);
		if( !file_exists($dirName) ) {
			mkdir( $dirName, 0777, true );
		}
		copy( $src, $dst );
	}
}

function recurse_copy( $src , $dst, $exclude = array() )
{
    $dir = opendir($src); 
	if( !file_exists($dst) ) {
    	mkdir($dst, 0777, true ); 
    }
    while(false !== ( $file = readdir($dir)) ) { 
        if ( ( $file != '.' ) && ( $file != '..' ) && !in_array($file, $exclude) ) { 
            if ( is_dir($src . '/' . $file) ) { 
                recurse_copy($src . '/' . $file,$dst . '/' . $file, $exclude); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
			} 
		} 
	} 
	closedir($dir); 
}

function findPageForPosition( $pages, $y, $pageHeight )
{
	// HACK: Wired first issue has negative X, Y on manganews page. Assume it's on the first page in that case.	
	if( $y < 0 ) {
		$pageIndex = 0;
	} else {
		$pageIndex = floor($y/$pageHeight);
	}

	if( $pageIndex >= count($pages) && $pageIndex > 0 ) {
		return null;
	} else {
		return $pages[$pageIndex];
	}
}

/* Lookup or generate unique integer id from Adobe's string id */
function getID( $stringID )
{
	global 	$integerIDs, $nextID;
	
	if( !array_key_exists( $stringID, $integerIDs ) ) {
		$integerIDs[$stringID] = $nextID++;
	}
	return $integerIDs[$stringID];
}

function xmlToJson(DOMNode $node, $level = 0)
{
	try {
		if( $node->childNodes ) {
			$r = array();

			// Namespaces. Only do this for the document element. This is a limitation which is needed
			// to prevent the namespaces to appear on all elements, cluttering the structure.
			/*
				Example:
				
				"$xmlns": {
				  "gCal": "http:\/\/schemas.google.com\/gCal\/2005",
				  "gd": "http:\/\/schemas.google.com\/g\/2005",
				  "openSearch": "http:\/\/a9.com\/-\/spec\/opensearchrss\/1.0\/",
				  "$": "http:\/\/www.w3.org\/2005\/Atom"
				},
			*/
			$ownerDoc = $node->ownerDocument;
			if( !is_null( $ownerDoc ) && $node->isSameNode( $ownerDoc->documentElement ) )
			{
				$xpath = new DOMXPath( $node->ownerDocument );
				foreach ($xpath->query('namespace::*[name() != "xml"]', $node) as $ns) {
					if ($ns->localName == 'xmlns') {
						$r['$xmlns']['$'] = $ns->namespaceURI;
					} else {
						$r['$xmlns'][$ns->localName] = $ns->namespaceURI;
					}
				}
			}

			// Handle attributes, which are always strings
			if ($node->attributes && $node->attributes->length) {
				$nil = false;
				foreach ($node->attributes as $attr) {
					// nil attributes that are true get a special treatment. These signify the
					// element should be a null object
					if( $attr->localName == 'nil' && 'true' == strtolower( $attr->value ) ) {
						// caller will give empty array special treatment
						return array();
					} else {
						// Replace the : of namespace with a $
						$name = preg_replace("/:/", "$", $attr->nodeName);
						// Prefix the name with a $ to avoid clashes with elements with the same name
						$r['$'.$name] = $attr->value;
					}
				}
				
				
			}

			$content = '';
			foreach ($node->childNodes as $child) {
				$idx = $child->localName;
				// When namespace is present, prefix with namespace + $
				if( $child->prefix != '' )
					$idx = $child->prefix.'$'.$idx;
				// Recursively turn the child into JSON
				if( !is_null( $cr = xmlToJson($child, $level+1) ) ) {
					if( empty($cr) ) {
						// Special case that turns the value to null
						$r[$idx] = null;
					} else if( $child->nodeType == XML_TEXT_NODE || $child->nodeType == XML_CDATA_SECTION_NODE ) { 
						// Text element, concatenate text to already handled child text nodes
						$content .= $cr;	
					} else { 
						// Reduce arrays of just one '$' element to just the value
						if( is_array($cr) && count($cr) == 1 && isset($cr['$']) ) {
							$r[$idx] = $cr['$'];
						} else {
							// Default case, add the array
							$r[$idx][] = $cr;
						}
					}
				}
			}
			
			// Reduce 1-element arrays
			foreach ($r as $idx => $v) {
				if (is_array($v) && (count($v) == 1) && isset($v[0])) {
					$r[$idx] = $v[0];
				}
			}
			
			// Accumulated element text content that's not whitespace
			if( is_string($content) && strlen(trim($content))) { 
				// Convert to numbers and booleans when applicable 
				$trimmed = trim($content);
				if( is_numeric($trimmed) ) {
					// Convert strings that are float or int
					$r['$'] = ( (float)$trimmed == (integer)$trimmed ) ? (integer)$trimmed : (float)$trimmed;
				} else {
					// Convert strings that are boolean
					switch( strtolower($trimmed) ) {
					case 'true' :
						$r['$'] = true;
						break;
					case 'false' :
						$r['$'] = false;
						break;
					default:
						// Default case, add string
						$r['$'] = $content; 
						break;
					}
				}
			}
		}
		// No children -- just return text;
		else {
			if (($node->nodeType == XML_TEXT_NODE)||($node->nodeType == XML_CDATA_SECTION_NODE)) {
				return $node->textContent;
			}
		}
		if ($level == 0) {
			//file_put_contents( OUTPUTDIRECTORY.'r.txt', print_r( $r, true ) );
			return json_encode( $r );
		} else {
			return $r;
		}
	} catch( Exception $e ) {
		echo "Error while creating config.json: " . $e->getMessage() . "<br />";
	}
}

function deleteFolderTree( $dir, $topFolder = true )
{
	global $folder_OUT;
	
	if ( substr($dir, -1) != "\\" && substr($dir, -1) != "/" )
       	$dir .= '/';
	
	$directory = opendir($dir); 
	$files = array(); 
	while(false !== ( $file = readdir($directory)) ) { 
        if ( ( $file != '.' ) && ( $file != '..' ) ) {         	
        	$files[] = $dir . $file;
		} 
	}
	closedir($directory);
	
	foreach( $files as $file ) {
		if( substr( $file, 0, strlen($folder_OUT) ) == $folder_OUT ) {
			if( is_dir( $file ) ) {
				if( !deleteFolderTree( $file, false ) ) {
					return false; // Note: error logging already done in recursion
				}
			} else {
				@unlink( $file ); // Windows always returns false, so we can't use return val!
				// Note: Suppress warning, since we'll make an error of it later on.
			}
		}
	}
	clearstatcache(); // Make sure unlink calls above are reflected!
	foreach( $files as $file ) {
		if( @file_exists( $file ) ) { // Suppress warning, since older Windows do always warn (even when exists)
			if( is_dir($dir) ) {
				echo 'Could not clean-up (remove) folder "'.$file.'". <br/>';
			} else {
				echo 'Could not clean-up (remove) file "'.$file.'".<br/>';
			}
			return false;
		}
	}

	// Leave top folder in-tact, but remove nested folders
	if( !$topFolder ) {
		if( is_dir($dir) ) {
			if( !rmdir( $dir ) ) {
				echo 'Could not clean-up (remove) folder "'.$dir.'".<br/>';
				return false;
			}
		}
	}
	return true;
}

function merge_pages($previews, $pageWidth, $pageHeight, $storyID, $outFile, $fileDirectory = "")
{
	global $folder_IN, $folder_OUT, $pageQuality;

	$img = imagecreatetruecolor(intval($pageWidth), intval($pageHeight));

	$y = 0;
	foreach ($previews as $preview) {
		//$imgPreview = imagecreatefrompng($folder_IN.$preview);
		$imageContents = file_get_contents($folder_IN.$fileDirectory.$preview);
		$imgPreview = imagecreatefromstring( $imageContents );
		$imageContents = ""; // clean memory
		imagecopy( $img, $imgPreview, 0, $y, 0, 0, imagesx($imgPreview), imagesy($imgPreview) );
		$y += imagesy($imgPreview);
	}

	imagejpeg( $img, $folder_OUT . "images/$storyID/$outFile" );
	imagedestroy( $img );

	return "images/$storyID/$outFile";
}

function addFolderToZip($dir, $zipArchive, $zipdir = '')
{
	echo 'dir="'.$dir.'" zipdir="'.$zipdir.'".';
	if (is_dir($dir)) {

		// Add the directory
		if( $zipdir ) {
			$zipArchive->addEmptyDir($zipdir);
		}

		// Loop through all the files
		$files = glob( $dir . '*', GLOB_MARK );
		foreach( $files as $file ) {
			if(is_file($file)){
				//LogHandler::Log( 'DigitalMagazine','DEBUG', 'Added file "'. $file .'" to archive.' );
				$zipArchive->addFile( realpath($file), $zipdir . basename($file));
			} else {
				addFolderToZip( dirname($file).'/'.basename($file).'/', $zipArchive, $zipdir.basename($file).'/' );
			}
		}
	}
}
	
	
