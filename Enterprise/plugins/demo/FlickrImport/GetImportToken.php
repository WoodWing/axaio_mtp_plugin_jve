<html>
	<head>
		<title>Request Flickr Authorization Token</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<body>
<?php
require_once '../../../config/config.php';
require_once dirname(__FILE__) . '/FlickrImport.class.php';

require_once BASEDIR.'/server/interfaces/services/BizException.class.php';
require_once BASEDIR.'/server/ZendFramework/library/Zend/Rest/Client.php';

$Flickr = new FlickrImport();

if(!empty($_REQUEST['frob']) && !empty($_REQUEST['logon'])) {
	$frob = $_REQUEST['frob'];
	$token = $Flickr->getToken($frob);
	require_once dirname(__FILE__) . '/configImport.php';
	if( defined('FLICKRIMPORT_TOKEN') ){
		echo "You already have an authorized token defined, please copy the new authorized token below to <BR>";
		echo "the FLICKRIMPORT_TOKEN value in " . dirname(__FILE__) . "/config.php";
		echo "New Authorized Token: " . $token;
	}
	else {
		$define = "\ndefine ('FLICKRIMPORT_TOKEN',		'" . $token . "' ); // Token";
		$configFile = "configImport.php";
		if (is_writable($configFile)) {
			$handle = fopen($configFile, 'a');
			if(!$handle) {
         		echo "Error: Cannot open file ($configFile).";
         		exit;
    		}
    		else {
    			if (fwrite($handle, $define) === FALSE) {
        			echo "Error: Cannot write to file ($configFile).";
        			exit;
   	 			}
    		}
    		fclose($handle);
		}
		else {
			echo 'Error: File is not writeable, please assign write permission to file ($configFile).';
		}
	}
?>
			<div>Flickr Authorization Token Installation Success.<br/></div>
<?php
}
elseif($_REQUEST['logon']) {
	$frob 	= $Flickr->getFrob();
	$url 	= $Flickr->getAuthUrl('read', $frob);
?>
			<div><b>Flickr Authorization Token Installation</b></div>
			<div>Step 3: Please authorize the access to Flickr by following the instructions below.</div>
			<div>Step 4: Please click on the Acquire Token at the bottom left screen.</div>
			
			<form action="getimporttoken.php" method="post" name="frm" onSubmit="return true;">
			<div id='FlickrAuth' width='100%'><iframe width="100%" height="80%" src="<?php echo $url ?>"></iframe></div>
				<input type="hidden" name="frob" value="<?php echo $frob ?>">
				<input type="hidden" name="logon" value="logon">
				<input type="submit" name="update" value="Acquire Token"/>
			</form>
<?php
}
else {
?>
			<div><b>Flickr Authorization Token Installation</b></div>
			<div>Step 1: If you have not already signed in, please sign in to <a href="http://www.flickr.com" target="_blank">www.flickr.com</a></div>
			<div>Step 2: After successfully logon, please click Authorize button below to authorize Flickr access.</div>
			<form action="getimporttoken.php" method="post" name="frm" onSubmit="return true;">
				<div>
					<input type="hidden" name="logon" value="logon">
					<input type="submit" name="authorize" value="Authorize"/>
				</div>
			</form>
<?php
}
?>
		</body>
</html>