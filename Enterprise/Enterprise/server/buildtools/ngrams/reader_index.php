<?php
require_once dirname(__FILE__).'/../../../config/config.php';

function new_seed() {
	return (float) microtime() * 10000000;
}

// Read "books" subfolder and list all books inside
$masterDir =  opendir( BASEDIR.'/server/wwtest/ngrams/books' );
$tables = array(); 
while($bookFile = readdir($masterDir)) {	
	$fullName = BASEDIR.'/server/wwtest/ngrams/books/'.$bookFile;
	if (is_file($fullName) && strrpos($fullName, '.php') == strlen($fullName)-strlen('.php') ) {
		$baseName = basename( $fullName, '.php' );
		$parts = explode( '-', $baseName );
		$grams = array( '2gram' => 'bigram', '3gram' => 'trigram', '4gram' => 'quadgram');
		$tables[$baseName] = '['.strtoupper($parts[0]).'] '.ucfirst($parts[1]).' - '.($parts[2]=='char'?'character':'word').' '.$grams[$parts[3]];
	}
}
closedir($masterDir);

if(!array_key_exists('table', $_REQUEST) || !array_key_exists($_REQUEST['table'], $tables)) {
	$_REQUEST['table'] = array_rand($tables);
}

if(!array_key_exists('length', $_REQUEST) || !is_numeric($_REQUEST['length']) || ($_REQUEST['length'] > 2000) || ($_REQUEST['length'] < 1)) {
	$_REQUEST['length'] = 100;
}

if(!array_key_exists('paragraphs', $_REQUEST) || !is_numeric($_REQUEST['paragraphs']) || ($_REQUEST['paragraphs'] > 100) || ($_REQUEST['paragraphs'] < 1)) {
	$_REQUEST['paragraphs'] = 5;
}

if(!array_key_exists('html-mode', $_REQUEST)) {
	$_REQUEST['html-mode'] = false;
} else {
	$_REQUEST['html-mode'] = true;
}

if(!array_key_exists('suppress-quotes', $_REQUEST)) {
	$_REQUEST['suppress-quotes'] = false;
} else {
	$_REQUEST['suppress-quotes'] = true;
}

if(!array_key_exists('suppress-marks', $_REQUEST)) {
	$_REQUEST['suppress-marks'] = false;
} else {
	$_REQUEST['suppress-marks'] = true;
}

if(!array_key_exists('suppress-digits', $_REQUEST)) {
	$_REQUEST['suppress-digits'] = false;
} else {
	$_REQUEST['suppress-digits'] = true;
}

if(!array_key_exists('ucase-first', $_REQUEST)) {
	$_REQUEST['ucase-first'] = false;
} else {
	$_REQUEST['ucase-first'] = true;
}

if(!array_key_exists('seed', $_REQUEST) || !is_numeric($_REQUEST['seed'])) {
	$_REQUEST['seed'] = new_seed();
}

$length = $_REQUEST['length'];
$currTable = $_REQUEST['table'];
$paragraphs = $_REQUEST['paragraphs'];
$htmlMode = $_REQUEST['html-mode'];
$suppressQuotes = $_REQUEST['suppress-quotes'];
$suppressMarks = $_REQUEST['suppress-marks'];
$suppressDigits = $_REQUEST['suppress-digits'];
$ucaseFirst = $_REQUEST['ucase-first'];
$seed = $_REQUEST['seed'];
srand($seed);

$permalink = "?table=$currTable&amp;length=$length&amp;paragraphs=$paragraphs&amp;seed=$seed";
if($htmlMode) $permalink .= "&amp;html-mode";
if($suppressQuotes) $permalink .= "&amp;suppress-quotes";

// Import book from disk
require_once BASEDIR.'/server/wwtest/ngrams/books/'.$currTable.'.php';
$book = new NGramsBook();

// Parse book
require_once BASEDIR.'/server/wwtest/ngrams/NGramsBookReader.class.php';
$generator = new NGramsBookReader( $book, $suppressQuotes, $suppressMarks, $suppressDigits, $ucaseFirst );

// Read book
$texts = array();
for($i = 0; $i < $paragraphs; $i++) {
	$text = $generator->readBook($length);
	if($htmlMode) {
		$text = "&lt;p&gt;$text&lt;/p&gt;";
	}
	$texts[] = $text;
}

// Show user input form (HTML)
require_once('Template.php');
$template = new Template('reader_frontend.php');
$template->assign('length', $length);
$template->assign('table', $currTable);
$template->assign('texts', $texts);
$template->assign('paragraphs', $paragraphs);
$template->assign('tables', $tables);
$template->assign('htmlMode', $htmlMode);
$template->assign('suppressQuotes', $suppressQuotes);
$template->assign('suppressMarks', $suppressMarks);
$template->assign('suppressDigits', $suppressDigits);
$template->assign('ucaseFirst', $ucaseFirst);
$template->assign('permalink', $permalink);

header("Content-Type: text/html; charset=utf-8");
echo $template->render();
