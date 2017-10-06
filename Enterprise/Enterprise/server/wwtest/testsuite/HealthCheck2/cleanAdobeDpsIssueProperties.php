<?php
require_once '../../../../config/config.php';
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class CleanAdobeDpsIssueProperties extends DBBase {

	/**
	 * Cleans the Adobe DPS (1) issue properties as there is now way to do this in the UI.
	 */
	public static function cleanDpsProperties()
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmProperty.class.php';

		$props = DBAdmProperty::getPropertyInfos(null, 'AdobeDps', null);
		$propNames = array_map(function($prop) { return $prop->Name; }, $props);
		foreach($props as $prop) {
			DBAdmProperty::deleteAdmPropertyInfo($prop);
		}

		$dbDriver = DBDriverFactory::gen();
		foreach( $propNames as $key => $val ) {
			$propNames[$key] = "'".$dbDriver->toDBString($val)."'";
		}
		$includePropNames = implode( ',', $propNames );
		$where = "`name` IN ($includePropNames) ";
		self::deleteRows('channeldata', $where);
	}
}

print '<html><body>';
try {
	CleanAdobeDpsIssueProperties::cleanDpsProperties();
	print '<font color="green">The DPS properties are cleaned.</font>';
} catch( Throwable $e ) {
	print '<font color="red">DPS properties could not be cleaned. The following error occurred: '.$e->getMessage().'</font>';
}
print '</body></html>';