<?php
/**
 * Implements DB side of feature profile and feature access
 *
 * @package Enterprise
 * @subpackage DBClasses
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBFeature extends DBBase
{
	const TABLENAME = 'profiles';

	public static function getFeatureProfiles()
	{
		require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
		$ret = array();

		$dbdriver = DBDriverFactory::gen();
		$dbp = $dbdriver->tablename(self::TABLENAME);
		$dbpv = $dbdriver->tablename('profilefeatures');

		$features = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();

		// get all profiles
		$sql = "SELECT * FROM $dbp ORDER BY `code`, `profile`";
		$sth = $dbdriver->query($sql);
		while( ($row = $dbdriver->fetch($sth)) ) {
			$ft = array();

			//@todo Move to profilefeatures
			// get all profilefeatures
			$sql = "SELECT * FROM $dbpv WHERE `profile` = ".$row['id'];
			$sthdet = $dbdriver->query($sql);
			$db = array();
			while( ($rowdet = $dbdriver->fetch($sthdet)) ) {
				$db[$rowdet['feature']] = $rowdet;
			}

			// determine value of features, only return those values other than "Yes"
			foreach ($features as $fid => $feature) {
				unset($value);
				if (isset($db[$fid])) {
					$value = @$db[$fid]['value'];
				}
				if (!isset($value)) {
					$value = 'No';
				}
				if ($value != 'Yes') {
					$ft[] = new AppFeature( $feature->Name, $value);
				}
			}

			// add profile
			$ret[] = new FeatureProfile( $row['profile'],$ft);
		}

		return $ret;
	}

	/**
	 * Retrieve the feature accesses defined for a given user and brand.
	 *
	 * Information can be retrieved per brand or system wide (all brands).
	 * When $pubId is an array, the results are two-dimensional and grouped by the brand id (key).
	 * When $pubId is an integer, the result set is one-dimensional.
	 *
	 * @param string $userShort
	 * @param integer|array $pubIds Brand id(s). Since 9.7 an array is allowed to retrieve for many brands at once.
	 * @return FeatureAccess[]
	 */
	public static function getFeatureAccess( $userShort, $pubIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$dbDriver = DBDriverFactory::gen();
		$rows = DBUser::getRights( $userShort, $pubIds );

		$featureAccessList = array();
		if( $rows ) foreach( $rows as $row ) {
			$featureAccess = new FeatureAccess(
				$row['profilename'],
				$row['issue']	!= 0 ? $row['issue']   : null,
				$row['section'] != 0 ? $row['section'] : null,
				$row['state']	!= 0 ? $row['state']   : null );
			// L> When issue, section, state is 0, it should be null.
			if( is_array( $pubIds ) ) {
				$featureAccessList[$row['publication']][] = $featureAccess;
			} else {
				$featureAccessList[] = $featureAccess;
			}
		}
		return $featureAccessList;
	}

	/** Returns the features of profiles.
	 *
	 * @param array $profileIds Profile Ids of which the features are read.
	 * @return array with profile/feature combinations.
	 * @throws BizException
	 */
	public static function getFeaturesByProfiles( array $profileIds )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbpv = $dbDriver->tablename( 'profilefeatures' );
		$sql =  'SELECT `profile`, `feature` '.
			'FROM '.$dbpv.' '.
			'WHERE `profile` IN (' . implode(',', $profileIds ) . ') '.
			'AND `feature` < 100 '; // [1..99]
		$sth = $dbDriver->query($sql);
		$result = self::fetchResults($sth);

		return $result;
	}
}
