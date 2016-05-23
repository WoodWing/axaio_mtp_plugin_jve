<?php

// Read posted variables from HTML form
$bookTitle = $_REQUEST['bookTitle'];
$rawText = $_REQUEST['rawText'];
$nGrams = $_REQUEST['nGrams'];
$langCode = $_REQUEST['langCode'];
$type = $_REQUEST['type'];
$error = '';
$message = '';

if( empty($langCode) ) $langCode = 'en'; // default English
if( empty($type) ) $type = 'word'; // default word-based
if( empty($nGrams) ) $nGrams = '2'; // default 2 grams

// Write the book
if( !empty($bookTitle) && !empty($rawText) ) {
	require_once dirname(__FILE__).'/../../../config/config.php';
	require_once BASEDIR.'/server/wwtest/ngrams/NGramsBookWriter.class.php';
	$writer = new NGramsBookWriter( $type, $nGrams );
	if( $writer->writeBook( $langCode, $bookTitle, $rawText, $message ) ) {
		$error = '';
	} else {
		$error = $message;
		$message = '';
	}
}

// Build HTML form from template
require_once('Template.php');
$template = new Template('writer_frontend.php');
$template->assign( 'bookTitle', $bookTitle );
$template->assign( 'rawText', $rawText );
$template->assign( 'nGrams', $nGrams );
$template->assign( 'langCode', $langCode );
$template->assign( 'type', $type );
$template->assign( 'error', $error );
$template->assign( 'message', $message );

// Show/output the HTML form
header("Content-Type: text/html; charset=utf-8");
echo $template->render();
