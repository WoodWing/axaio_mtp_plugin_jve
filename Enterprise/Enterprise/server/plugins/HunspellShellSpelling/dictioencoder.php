<?php
ini_set('display_errors', 1);
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar
require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';

/*$ticket =*/ checkSecure('publadmin');
?>
<html>
<head>
	<title>Enterprise Spelling - Encoding Convertor</title>
	<meta http-equiv="Content-Type" content="text/plain; charset=UTF-8" />
	<style type="text/css">
		table { border: 1px; border-spacing: 0; empty-cells: show; }
	</style>
</head>
<body style="font-family: Arial;">
	<h1>Enterprise Spelling - Dictionary Encoding Convertor</h1>
	<p>Converts installed Hunspell/MySpell dictionaries to UTF-8 encoding, to make it suitable for Enterprise.</p>
	<form action="" method="post">
	<input type="hidden" name="generateNew" value="1"/>
<?php
$generate = isset( $_POST['generateNew'] ) ? $_POST['generateNew'] : '';
$commands = array();
if( !$generate ){
	echo '<input type="submit" value="Generate UTF-8 encoded dictionaries"/><br/><br/>';
}else {
	require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
	require_once BASEDIR . '/server/bizclasses/BizSpelling.class.php';
	
	$pluginObj = BizServerPlugin::getPluginForConnector( 'HunspellShellSpelling_Spelling' );
	$error = false;
	$help = 'Please run the Health Check to ensure the Hunspell setup is correct.<br/>';
	if( $pluginObj && $pluginObj->IsInstalled ) {
		if( !$pluginObj->IsActive ) {
			echo 'The HunspellShellSpelling plug-in is disabled.' . $help;
			$error=true;
		}
	} else {
			echo 'The HunspellShellSpelling plug-in is not installed.<br/>' . $help;
			$error=true;
	}
	
	if( !$error ){
		$bizSpelling = new BizSpelling();
		$configs = $bizSpelling->getConfiguredSpelling( 0 /*ALL pubId*/, null/*language*/, $pluginObj->UniqueName, false);
				
		// Get full path of ALL the INSTALLED dictionaries
		$language = null;
		$connector = $bizSpelling->getInstalledSpellingConnector( null, $language, $pluginObj->UniqueName );
		$dictionariesPath = $connector->getInstalledDictionariesAndPath( $pluginObj->UniqueName );
		
		// Retrieves full path of the CONFIGURED Hunspell dictionaries
		$affixFileNames = array();
		foreach( $configs as $publicationId => $pubConfig ){
			foreach( $pubConfig as $language => $langConfig ) {
				foreach( $langConfig['dictionaries'] as $configuredDictionary ){
					foreach( $dictionariesPath as $dictionaryPath ){
						if( basename( $dictionaryPath, '.aff' ) == $configuredDictionary ){
							$affixFileNames[] = $dictionaryPath;
							break;
						}
					}
				}
			}
		}
		
		$notUtf8Encoded = array();
		// Now, do the dictionary encoding check.
		$notReadableAffixFile = array();					
		if( $affixFileNames ) foreach( $affixFileNames as $affixFileName ){
			$affixFileName = $affixFileName . '.aff';
			if( is_readable( $affixFileName ) ){
				$affixFile = file_get_contents( $affixFileName );
				$affixFileContents = mb_split( '\n', $affixFile );
				foreach( $affixFileContents as $affixFileContent ){
					if( mb_ereg_match( '^SET ', $affixFileContent ) ){
						list( , $encoding ) = mb_split( '\s', $affixFileContent );
						if( !mb_ereg_match( 'UTF-8', $encoding ) ){ // Encoding not in UTF-8, mark it
							$dictionary = dirname( $affixFileName ) . '/' .baseName( $affixFileName, '.aff' ) ;
							$notUtf8Encoded[ $dictionary ] = $encoding;
						}
						break;
					}
				}
			}else{
				$notReadableAffixFile[ baseName( $affixFileName, '.aff' ) ] = dirname( $affixFileName );
			}
		}
		
		if( count( $notReadableAffixFile ) > 0 ) {
			$error=true;
			$detail = 'The following language of Hunspell dictionary(s) are not readable [' .
			implode( ',', array_keys( $notReadableAffixFile ) ) . '].<br/>';
			echo $detail . $help;		
		}
		
		if( !$error && count( $notUtf8Encoded ) > 0 ){
			// check / create TEMP directory
			if ( !file_exists(TEMPDIRECTORY) ) {
				$old_umask = umask(0); // Needed for mkdir, see http://www.php.net/umask
				if( mkdir( TEMPDIRECTORY, 0777, true ) ) {
					chmod( TEMPDIRECTORY, 0777 );  
					umask($old_umask);
				}else{
					$error = true;
					echo 'No temporary directory available, please ensure [' . TEMPDIRECTORY .
					'] is created and www/inet user has read access to it.<br>';
				}
			}
			
			if( !$error ){
				echo '<p>Generating UTF-8 encoded dictionary(s)::<br/>';
				// Generate UTF-8 dictionaries
				foreach( $notUtf8Encoded as $dictionary => $encoding ){
					// Handling .dic file
					// Converting UTF-8
					$convertedDictionary = iconv( $encoding, 'UTF-8', file_get_contents( $dictionary . '.dic' ) );
					$utf8EncodedDictionary = TEMPDIRECTORY. '/' . basename( $dictionary ) . '.dic';
					if ( !file_put_contents( $utf8EncodedDictionary, $convertedDictionary ) ){
						$error=true;
						echo '<font color="red">--Couldn\'t generate new dictionary file in ' .$utf8EncodedDictionary . '<br>' . 
							'Please ensure inet/www user has access to folder [' . TEMPDIRECTORY . ']</font><br/><br/>';
					}else{
						// Preparing the commands for copy action
						if( OS == 'WIN' ) {
							$utf8EncodedDictionary = str_replace( '/', DIRECTORY_SEPARATOR, $utf8EncodedDictionary );
							$dictionary = str_replace( '/', DIRECTORY_SEPARATOR, $dictionary );
							$commands[] = 'copy "' . $utf8EncodedDictionary . '" "' . $dictionary . '.dic"';
						}else{
							$commands[] = 'cp "' . $utf8EncodedDictionary . '" "' . $dictionary . '.dic"';
						}
					}
					if( !$error ){
						echo 'Succesfully generated dictionary ['. basename( $dictionary ).'] .dic file<br/>';
					}
					$error = false; // reset to false for next checking
					
					// Handling .aff file
					// Converting into UTF-8
					// First change the content from 'SET KOI8-R', 'SET ISO8859-1' and etc to 'SET UTF-8'  
					$oriAffixContent = file_get_contents( $dictionary . '.aff' );
					$affixContents = mb_split( '\n', $oriAffixContent );
					$newAffixContent = '';
					foreach( $affixContents as $affixFileContent ){
						$affixFileContent = trim( $affixFileContent );
						if( mb_ereg_match( '^SET ', $affixFileContent ) ){
							$newAffixContent .= "SET UTF-8\nFLAG UTF-8\n";
						}else{
							$newAffixContent .= $affixFileContent . "\n";
						}
					}
					$convertedAffix = iconv( $encoding, 'UTF-8', $newAffixContent );
					$utf8EncodedDictionary = TEMPDIRECTORY. '/' . basename( $dictionary ) . '.aff';
					if( !file_put_contents( $utf8EncodedDictionary, $convertedAffix ) ){
						$error=true;
						echo '<font color="red">--Couldn\'t generate new dictionary file in ' .$utf8EncodedDictionary . '<br>' .
							'Please ensure inet/www user has access to folder [' . TEMPDIRECTORY . ']</font><br/><br/>';
					}else{
						// Preparing the commands for copy action
						if( OS == 'WIN' ) {
							$utf8EncodedDictionary = str_replace( '/', DIRECTORY_SEPARATOR, $utf8EncodedDictionary );
							$dictionary = str_replace( '/', DIRECTORY_SEPARATOR, $dictionary );
							$commands[] = 'copy "' . $utf8EncodedDictionary . '" "' . $dictionary . '.aff"';
						}else{
							$commands[] = 'cp "' . $utf8EncodedDictionary . '" "' . $dictionary . '.aff"';
						}
					}
					
					if( !$error ){
						echo 'Succesfully generated dictionary ['. basename( $dictionary ).'] .aff file<br/>';
					}
					$error = false; // reset to false for next checking
				}
				echo '</p>';
			}
		}else if( !$error && count( $notUtf8Encoded ) == 0 ){
			echo 'Nothing to do, all dictionary(s) in UTF-8 encoding, no conversion needed.<br/>';
		}
	} // conversion ends
}

?>

<?php
	if( $commands ){
?>
	<h3>Instructions</h3>
	<p>Please run the following command (one line at a time) on your command prompt/terminal to copy the new dictionaries into the existing dictionaries:</br>
		<font color="red">*Please be reminded that Enterprise will not keep the original dictionaries, please keep a backup if you 
		still want to keep the original dictionaries.</font>
	</p>
	<p>Commands:<br/>
	
<?php
		foreach( $commands as $command ){
			echo $command . "<br/>";
		}
	echo '</p>';	
	}
?>	
	</form>
</body>
</html>
