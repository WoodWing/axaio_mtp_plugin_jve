<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=US-ASCII">
        <title>Synchronize the Free/Paid setting per device.</title>
    </head>
    <body>
		<?php
			require_once '../../../../../config/config.php';
			require_once dirname(__FILE__).'/SyncFreePaidPerDevice.class.php';
			$result = SyncFreePaidPerDevice::doSync();
			if ( !$result ) {
				$message  = 'The update of the free/paid setting per edition failed.<br>';
				$message .= 'Please have a look at the Enterprise log files for more information.';
				print '<p style="color:red">'.$message.'</p>';
			} else {
				$message = 'Synchronization of the Free/Paid setting completed successfully.';
				print '<p style="color:green">'.$message.'</p>';
			}
		?>
    </body>
</html>
