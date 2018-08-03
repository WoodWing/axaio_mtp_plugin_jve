<?php
/**
 * Test application for manually testing all kind of installed spelling integrations.
 * Those integrations are established through Server Plug-ins.
 *
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

ini_set('display_errors', 1);
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar
require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';

/*$ticket =*/ checkSecure('publadmin');

class EnterpriseSpellingWorkbenchApplication
{
	/**
	 * Show an error on top of HTML page when there is something wrong in spelling configuration or installation.
	 */
	public function validateSpellingInstallation()
	{
		$bizSpelling = new BizSpelling();
		$plugins = $bizSpelling->getInstalledSpellingPlugins(); 
		if( count($plugins) == 0 ) {
			echo '<font color="red">ERROR: No Spelling Server Plug-ins installed.<br/></font>';
		}
		try {
			foreach( $plugins as $plugin ) {
				$bizSpelling->validateSpellingConfiguration( $plugin->UniqueName );
			}
		} catch ( BizException $e ) {
			echo '<font color="red">ERROR: '.$e->getMessage().'<br/>Detail: '.$e->getDetail().'<br/></font>'.PHP_EOL;
		}
	}
	
	/**
	 * Shows a combobox on the HTML page, listing all dictionaries to let the user pick one.
	 *
	 * @param array $dicts List of Dictionary objects to show.
	 * @param object $selectedDict Returns the user selected Dictionary.
	 */
	public function showDictionaryCombo( array $dicts, &$selectedDict )
	{
		$dictNameParam = isset($_REQUEST['Dictionary']) ? $_REQUEST['Dictionary'] : $dicts[0]->Name;
		echo 'Dictionary: <select name="Dictionary">';
		$selectedDict = null;
		foreach( $dicts as $dict ) {
			$selected = ($dict->Name == $dictNameParam ) ? ' selected="selected"' : '';
			if( $dict->Name == $dictNameParam ) {
				$selectedDict = $dict;
			}
			echo '<option value="'.formvar($dict->Name).'"'.$selected.'>'.formvar($dict->Name).' ['.formvar($dict->Language).']</option>'."\n";
		}
		echo '</select>';
	}
	
	/**
	 * Shows an HTML table with 3 colums; The frst one shows a text editor to let user type plain text.
	 * The 2nd one is titled "Checked words" and shows how the typed words are split-up using the
	 * regular expression configured at 'wordchars' setting of the ENTERPRISE_SPELLING option.
	 * The 3rd one is titled "Misspelled words" and shows the return values of the spelling engine.
	 *
	 * @param integer $publicationId
	 * @parama object $dict Dictionary.
	 */
	public function checkSpellingAndSuggest( $publicationId, $dict )
	{
		$text = isset($_REQUEST['text']) ? $_REQUEST['text'] : '';
		echo '<table border="1" cellpadding="3"><thead><tr align="left"><th>Enter text:</th>';
		try {
			if( $text ) {
				echo '<th>Checked words:</th><th>Misspelled words:</th>';
			}
			echo '</tr></thead><tbody><tr valign="top">';
			echo '<td bgcolor="#cccccc"><textarea name="text" cols="60" rows="20">'.formvar($text).'</textarea></td>';
			if( $text ) {
				$matches = array();
				preg_match_all( $dict->WordChars, $text, $matches );
				$wordsToCheck = $matches[0];
				$wordsToCheck = array_unique( $wordsToCheck );
				$suggestionsTxt = '';
				$bizSpelling = new BizSpelling();
				$misspelledWords = $bizSpelling->checkSpelling( $publicationId, $dict->Language, $wordsToCheck );
				foreach( $misspelledWords as $word ) {
					$suggestionsTxt .= '- '.$word.':<ul>';
					$suggestions = $bizSpelling->getSuggestions( $publicationId, $dict->Language, $word );
					$suggestionsTxt .= '<li>'.implode( '</li><li>', $suggestions ).'</li></ul><br/>';
				}
				echo '<td>- '.implode( '<br/>- ', $wordsToCheck ).'</td><td>'.$suggestionsTxt.'</td>';
			}
		} catch ( BizException $e ) {
			echo '<font color="red">ERROR: '.$e->getMessage().'<br/>Detail: '.$e->getDetail().'<br/></font>'.PHP_EOL;
		}
		echo '</tr></tbody></table>';
	}
}
?>
<html>
<head>
	<title>Enterprise Spelling - Workbench</title>
	<meta http-equiv="Content-Type" content="text/plain; charset=UTF-8" />
	<style type="text/css">
		table { border: 1px; border-spacing: 0px; empty-cells: show; }
	</style>
</head>
<body style="font-family: Arial;">
	<h1>Enterprise Spelling - Workbench</h1>
	<p>Manual testing tool for installed spelling engines.</p>
	<form action="" method="post">
<?php
	$testApp = new EnterpriseSpellingWorkbenchApplication();
	$testApp->validateSpellingInstallation();
	$publicationId = 0; // system-wide (TODO: add combobox with publications to let user pick $publicationId)
	$dicts = BizSpelling::getDictionariesForPublication( $publicationId );
	$selectedDict = null;
	$testApp->showDictionaryCombo( $dicts, $selectedDict );
	echo '&nbsp;&nbsp;<input type="submit" value="Check Spelling"/><br/><br/>';
	$testApp->checkSpellingAndSuggest( $publicationId, $selectedDict );
?>
	</form>
	<h3>Instructions</h3>
	<p>The "Dictionary" pull-down menu lists all dictionaries configured in the ENTERPRISE_SPELLING option
at the configserver.php file. Select the dictionary you want to use. At the text frame under "Enter text", 
enter any text to be check for spelling. After pressing the "Check Spelling" button, another column appears
titled "Checked words". This shows how the typed words are split-up using the regular expression configured 
at the 'wordchars' setting of the ENTERPRISE_SPELLING option. Another column appears titled "Misspelled words",
shows the return values of the spelling engine.</p>
</body>
</html>