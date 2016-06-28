<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=US-ASCII">
        <title>Convert custom property Protect into Article_access.</title>
    </head>
    <body>
		<?php
			require_once '../../../../../config/config.php';
			require_once dirname(__FILE__).'/AddArticleAccessProp.class.php';
			$result = AddArticleAccessProp::doAddArticleAccessProp();
			if ( !$result ) {
				$message  = 'The change of custom property from \'PROTECT\' to \'ARTICLE_ACCESS\' has failed. ' .
							'Please check the Enterprise log files for more information.';
				print '<p style="color:red">'.$message.'</p>';
			} else {
				$message = 'Custom property \'PROTECT\' has been changed into \'ARTICLE_ACCESS\'.';
				print '<p style="color:green">'.$message.'</p>';
			}
		?>
    </body>
</html>
