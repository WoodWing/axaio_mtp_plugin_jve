<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=US-ASCII">
	<title>Migrate Kicker Content</title>
</head>
<body>
In order to migrate the content of the 'old' kicker field to the new one please select 'Yes'. In case you do not want to
make any changes select 'No'. If you select 'Yes' the next changes will be made:
<ul>
	<li>A custom property 'KICKER' of type list will be created.</li>
	<li>The current content of the old 'kicker' field ('slugline') will be copied to the new 'kicker' field.</li>
	<li>The current field used as kicker ('slugline') will be cleaned.</li>
	<li>Migrating of the kicker content is done for all dossiers used in an Adobe DPS channel.</li>
</ul>
After these steps you have do to the next steps manually:
<ul>
	<li>In the Dialogs Setup page remove the old 'kicker' field ('Slugline (Kicker)') for the object type Dossier.</li>
	<li>In the Dialogs Setup page add the new 'kicker' field (* Kicker) for the object type Dossier.</li>
	<li>In the Metadata page clear the display name of the Dynamic Property Slugline.</li>
	<li>In the Metadata page update the Value List of the Kicker custom property. Add the values previously used in the 'Slugline'.</li>
</ul>

If you select 'No' the next change will be made:
<ul>
	<li>A custom field 'KICKER' of type list will be created.</li>
</ul>
<form action="MigrateKickerContent.php" method="post">
Migrate the Kicker settings: <input type="submit" name="yes" value="Yes"> <input type="submit" name="no" value="No">
</form>
<?php
require_once '../../../../../config/config.php';
require_once dirname(__FILE__).'/MigrateKickerContent.class.php';
$action = isset ( $_POST['yes'] ) ? 'migrate' : ( isset( $_POST['no'] ) ? 'no_migrate' : '' );
$errorMessage = '';
$succesMessage = '';
if ( $action == 'migrate' ) {
	$result = MigrateKickerContent::doMigrateKickerContent();
	if ( !$result ) {
		$errorMessage  = 'The migration of the old \'kicker\' property to the custom property \'KICKER\' has failed. ' .
			'Please check the Enterprise log files for more information.';
	} else {
		$succesMessage = 'Property \'Slugline\' has been migrated to \'KICKER\'.';
	}
} elseif ( $action == 'no_migrate' ) {
	if ( MigrateKickerContent::addKickerToModel() ) {
		$result = MigrateKickerContent::setMigrateKickerPropFlag( 1 );
	} else {
		$errorMessage = 'Adding the custom propery \'KICKER\' to the model has failed.';
	}
}

if ( $succesMessage ) {
	print '<p style="color:green">'.$succesMessage.'</p>';
} elseif ( $errorMessage ) {
	print '<p style="color:red">'.$errorMessage.'</p>';
}

?>
</body>
</html>