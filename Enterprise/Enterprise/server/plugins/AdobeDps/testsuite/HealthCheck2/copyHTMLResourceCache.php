<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=US-ASCII">
        <title>Copy the HTML Resource folders to a persistent location.</title>
    </head>
    <body>
		<?php
			require_once dirname(__FILE__).'/../../../../../config/config.php';
			require_once BASEDIR.'/config/config_dps.php';
			require_once dirname(__FILE__).'/CopyHTMLResourceCache.class.php';
			$copyFile = new CopyHTMLResourceCache();
			$result = $copyFile->doCopyHTMLResourcesCache();
			if ( !$result ) {
				$failures = $copyFile->getFoldersFailure();
				$message  = 'The copy action of the next folders failed:<br>';
				foreach ( $failures as $failure ) {
					$message .= $failure.'<br>';
				}
				print '<p style="color:red">'.$message.'</p>';
			} else {
				$message = 'The copy of the folders completed successfully.';
				print '<p style="color:green">'.$message.'</p>';
			}
		?>
    </body>
</html>