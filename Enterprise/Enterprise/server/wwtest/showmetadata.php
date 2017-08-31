<?php
/**
 * @package 	Enterprise
 * @subpackage 	wwtest
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Meta data inspector that shows object properties and file embedded meta data, such as IPTC, EXIF, 
 * and XMP. It also shows file embedded thumbnails at EXIF and XMP. The PHP temp folder is used 
 * to temporary store the object file retrieved from database and allow the tools to retrieve the 
 * meta data from it. Within the same request, the temporary file is removed again (to keep things clean). 
 * Therefore, all meta data is streamed into one HTML file to web browser. In other terms, there can 
 * be no linked files since all stuff in cleaned up immediatly. And so all thumbnails are fysically 
 * embedded in the same HTML document (base64 encoded). For XMP data, an XSLT stylesheet is used to 
 * transform into HTML which does syntax highlighting like browser do. 
*/

if( !isset($_REQUEST['ID']) || empty($_REQUEST['ID']) || 
	(isset($_REQUEST['help']) && $_REQUEST['help'] == 'yes') ) { 
	// show instructions how to use this test tool
?>
<html>
	<body>
		<h1>Enterprise object meta data inspector</h1>
		This test tool shows the meta data of the native file of any Enterprise object.<br/>
		It accepts the following parameters at the URL:<br/>
		<ul>
			<li><b>ID: </b><i>[Mandatory]</i> Object identifier.<br/>
				Supported values: Any record id from smart_objects table (or any alien object id).<br/>
				Example: <code>showmetadata.php?ID=123</code><br/><br/>
			</li>
			<li>
				<b>rendtion:</b> <i>[Optional]</i> The file rendition to retrieve from DB to get meta data from. <br/>
				Supported values: <code>native, preview, thumb, output</code>.<br/>
				Default: <code>native</code>.<br/>
				Example: <code>showmetadata.php?ID=123&rendition=native</code><br/><br/>
			</li>
			<li>
				<b>view:</b> <i>[Optional]</i> View mode of the test tool.<br/>
				Supported values: <code>all, props, xmp, xmpthumb, exif, exifthumb, iptc</code>.<br/>
				Default: <code>all</code>.<br/>
				Example: <code>showmetadata.php?ID=123&view=xmpthumb</code><br/><br/>
			</li>
		</ul>
	</body>
</html>
<?php
} else { // perform request

	require_once dirname(__FILE__).'/../../config/config.php';
	require_once BASEDIR.'/server/admin/global_inc.php';
	require_once BASEDIR.'/server/secure.php';

	// before v5.2.1 the sys_get_temp_dir is not defined
	if ( !function_exists('sys_get_temp_dir')) {
		function sys_get_temp_dir() 
		{ 
			if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
			if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
			if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
			$tempfile = tempnam(uniqid(rand(),TRUE),'');
			if (file_exists($tempfile)) {
				unlink($tempfile);
				return realpath(dirname($tempfile));
			}
			return null;
		}
	}

	// check user access	
	$ticket = checkSecure();
	global $globUser;  // set by checkSecure()

	$viewMode = isset($_REQUEST['view']) ? $_REQUEST['view'] : 'all';

	// get object file (requested rendition) from database
	try {
		$rendition = isset($_REQUEST['rendition']) ? $_REQUEST['rendition'] : 'native';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
		$object = BizObject::getObject( $_REQUEST['ID'], $globUser, false, $rendition, null );
	} catch( BizException $e ) {
		print '<html><body>ERROR: Could not get object from database. ('.$e->getMessage().')</body></html>';
		exit();
	}

	require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
	$transferServer = new BizTransferServer();
	$buffer = $transferServer->getContent($object->Files[0]);
	if( empty( $buffer ) ) {
		print '<html><body>ERROR: Object has no native content file (empty file).</body></html>';
		exit();
	}

	/*require_once BASEDIR . '/server/utils/XMPParser.class.php';	
	$xmp = XMPParser::readXMP( $buffer );
	header('content-type: text/xml');
	print $xmp->asXML();
	exit();*/

	// store the object's file in PHP's temp folder
	$tmpFileName = tempnam( sys_get_temp_dir(), 'showmetadata_' );
	$tmpFileHandle = fopen( $tmpFileName, 'w' );
	if( $tmpFileHandle === false ) {
		print '<html><body>ERROR: Could not create temp file "'.$tmpFileName.'".</body></html>';
	}
	fwrite( $tmpFileHandle, $buffer );

	// get requested meta data from temp file and show it to end user
?>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<script language="javascript" type="text/javascript">
			function foldItem( itemId )
			{
<?php
	print 'var inetRoot = "'.INETROOT.'";' . "\n";
?>
				var foldArea = document.getElementById( itemId + "_foldArea" );
				var foldIcon = document.getElementById( itemId + "_foldIcon" );
				if( foldArea.style.display == "none" ) { // toggle
					foldArea.style.display = ""; // show
					foldIcon.src = inetRoot + '/config/images/next_16.gif';
				} else {
					foldArea.style.display = "none"; // hide
					foldIcon.src = inetRoot + '/config/images/pnext_16.gif';
				}
			}
		</script>
	</head>
	<body>
		<h1>Enterprise object meta data inspector</h1>
<?php
	$itemId = 0;
	// show some essential meta data
	print '<table>';
	print '<tr><td><b>ID: </b></td><td>'.$object->MetaData->BasicMetaData->ID.'</td></tr>' . "\n";
	print '<tr><td><b>Name: </b></td><td>'.$object->MetaData->BasicMetaData->Name.'</td></tr>' . "\n";
	print '<tr><td><b>Type: </b></td><td>'.$object->MetaData->BasicMetaData->Type.'</td></tr>' . "\n";
	print '<tr><td><b>Format: </b></td><td>'.$object->MetaData->ContentMetaData->Format.'</td></tr>' . "\n";
	print '</table><br/>';

	// determine the view modes to walk through
	print '<table border="1">' . "\n";
	$viewModes = ($viewMode == 'all') ? array('props','exiftool','getimagesize','xmp','xmpthumb','exif','exifthumb','iptc'/*,'irb','irbthumb'*/) : array($viewMode);
	foreach( $viewModes as $viewMode ) {
		$itemId++;
		$errMsg = '';
		$dataOut = '';
		print '<tr><td valign="top"><b>'.$viewMode.'</b></td>'  . "\n" . '<td>';
		switch( $viewMode ) {
			case 'props':
				$dataOut .= '<pre>';
				$dataOut .= print_r( $object->MetaData, true );
				$dataOut .= '</pre>';
				break;
			case 'exiftool':
				require_once  BASEDIR.'/server/plugins/ExifTool/ExifTool_MetaData.class.php';
				$exifToolConnector = new ExifTool_MetaData();
				$dataOut .= 'Properties mapped to Enterprise MetaData:<pre>';
				$dataOut .= print_r( $exifToolConnector->readMetaData( $object->Files[0], null ), true );
				$dataOut .= '</pre>';
				$dataOut .= 'Properties extracted from file:<pre>';
				$dataOut .= print_r( $exifToolConnector->getRawMetaData(), true );
				$dataOut .= '</pre>';
				break;
			case 'getimagesize':
				$imageInfo = getimagesize( $tmpFileName );
				if( $imageInfo ) {
					$dataOut .= '<pre>';
					$dataOut .= print_r( $imageInfo, true );
					$dataOut .= '</pre>';
				} else {
					$errMsg = 'getimagesize() returned nothing for image file.';
				}
				break;
			case 'xmp':
				require_once BASEDIR . '/server/utils/XMPParser.class.php';	
				$xmp = XMPParser::readXMP( $buffer );
				if( $xmp != null ) {
					// put simpledoc into domdoc
					$doc = new DOMDocument('1.0');
					$doc->preserveWhiteSpace = false;
					$doc->formatOutput = true;
					$doc->loadXML($xmp->asXML());
					// format XML to HTML using stylesheet
					$xslDoc = new DOMDocument();
					$xslDoc->loadXML( file_get_contents( BASEDIR.'/server/wwtest/highlightxml.xsl' ) );
					$xslProc = new XSLTProcessor();
					$xslProc->importStylesheet( $xslDoc );
					// output the formatted XML
					$dataOut .= $xslProc->transformToXML( $doc );
				} else {
					$errMsg = 'Could not retrieve XMP data from object.';
				}
				break;
			case 'xmpthumb':
				require_once BASEDIR . '/server/utils/XMPParser.class.php';	
				$xmp = XMPParser::readXMP( $buffer );
				if( $xmp != null ) {
					$xmp->registerXPathNamespace('xapGImg',	'http://ns.adobe.com/xap/1.0/g/img/');
					$value = $xmp->xpath('//xapGImg:image');
					if( $value ) {
						$image = $value[0]; // in base64 format
						//header('content-type: image/jpg');
						//print base64_decode($image);
						$dataOut .= '<img '. "\n" . 'src="data:image/jpg;base64,' .  $image . '"/>';
					} else {
						$errMsg = 'Could not retrieve XMP data from object.';
					}
				} else {
					$errMsg = 'Could not retrieve XMP data from object.';
				}
				break;
			case 'exif':
				$exif = exif_read_data( $tmpFileName, null, true, false );
				if( $exif === false ) {
					$errMsg = 'Could not retrieve EXIF data from object.';
				} else {
					$dataOut .= '<pre>';
					$dataOut .= print_r( $exif, true );
					$dataOut .= '</pre>';
				}
				break;
			case 'exifthumb':
				$exif = exif_thumbnail( $tmpFileName );
				if( $exif === false ) {
					$errMsg = 'Could not retrieve EXIF data from object.';
				} else {
					//header("Content-type: image/jpeg");
					//print $exif;
					$base64 = base64_encode($exif);
					$dataOut .= '<img '. "\n" . 'src="data:image/jpg;base64,' .  $base64 . '"/>';
				}
				break;
			case 'iptc':
				if( ($bimPos = strpos($buffer,'8BIM')) === false ) {
					$errMsg = 'Could not retrieve IPTC data from object.';
				} else {
					$bim = substr( $buffer, $bimPos );
					if( $bim ) {
						$iptc = iptcparse( $bim );
						if( $iptc ) {
							$dataOut .= '<pre>';
							$dataOut .= print_r( $iptc, true );
							$dataOut .= '</pre>';
						} else {
							$errMsg = 'Could not retrieve IPTC data from object (parser failed).';
						}
					} else {
						$errMsg = 'Could not retrieve IPTC data from object (no 8BIM data).';
					}
				}
				break;
			/* // Commented out; We can not use the MetadataToolkit since it is under GNU License
			case 'irb':
				require_once BASEDIR . '/server/MetadataToolkit/JPEG.php';
				require_once BASEDIR . '/server/MetadataToolkit/JFIF.php';
				require_once BASEDIR . '/server/MetadataToolkit/PictureInfo.php';
				require_once BASEDIR . '/server/MetadataToolkit/XMP.php';
				require_once BASEDIR . '/server/MetadataToolkit/Photoshop_IRB.php';
				require_once BASEDIR . '/server/MetadataToolkit/EXIF.php';
				
				$jpegHeader = get_jpeg_header_data( $tmpFileName );
				if( $jpegHeader === false ) {
					$errMsg = 'Could not retrieve IRB data from object.';
				}
				$irb = get_Photoshop_IRB( $jpegHeader );
				if( $irb === false ) {
					$errMsg = 'Could not retrieve IRB data from object.';
				} else {
					$dataOut .= Interpret_IRB_to_HTML( $irb, $tmpFileName );
				}
				break;
			case 'irbthumb':
				require_once BASEDIR . '/server/MetadataToolkit/JPEG.php';
				require_once BASEDIR . '/server/MetadataToolkit/Photoshop_IRB.php';
				$jpegHeader = get_jpeg_header_data( $tmpFileName );
				if( $jpegHeader === false ) {
					$errMsg = 'Could not retrieve IRB data from object.';
				}
				$irb = get_Photoshop_IRB( $jpegHeader );
				if( $irb === false ) {
					$errMsg = 'Could not retrieve IRB data from object.';
				} else {
					// Cycle through the resources in the Photoshop IRB
					// Until either a thumbnail resource is found or
					// there are no more resources
					$i = 0;
					while ( ( $i < count( $irb ) ) &&
							( $irb[$i]['ResID'] != 0x0409 ) &&
							( $irb[$i]['ResID'] != 0x040C ) ) {
						$i++;
					}
					// Check if a thumbnail was found
					if ( $i < count( $irb ) ) {
						//header("Content-type: image/jpeg");
						//print substr( $irb[$i]['ResData'] , 28 );
						$base64 = base64_encode( substr( $irb[$i]['ResData'] , 28 ) );
						$dataOut .= '<img '. "\n" . 'src="data:image/jpg;base64,' .  $base64 . '"/>';
					}
				}
				break;
			*/
		}
		if( empty( $errMsg ) ) {
			print '<a href="javascript:foldItem('.$itemId.')"><img id="'.$itemId.'_foldIcon" src="'.INETROOT.'/config/images/pnext_16.gif'.'" style="border:none;"/></a>' . "\n";
			print '<div id="'.$itemId.'_foldArea" style="display:none">'.$dataOut.'</div>';
		} else {
			print '<img src="'.INETROOT.'/config/images/remov_16.gif'.'" title="'.$errMsg.'"/>';
		}
		print '</td></tr>' . "\n";
	}
	print '</table></body></html>';
	// remove the temp file
	fclose( $tmpFileHandle );
	unlink( $tmpFileName );
}
