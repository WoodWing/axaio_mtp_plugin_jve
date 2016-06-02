<?php
require_once dirname(__FILE__).'/../../../config/config.php';

$script = $_GET['script'];
$filePath = WOODWINGSYSTEMDIRECTORY . '/EnterpriseScripts/'. $script;

header( 'Content-Type: application/zip' );
header( "Content-Disposition: attachment; filename=$script" );
header( 'Content-length: ' . filesize($filePath) );

print_r (file_get_contents( $filePath ) );


