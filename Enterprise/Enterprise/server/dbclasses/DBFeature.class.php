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

	/**
	 * Function returns a list of FeatureProfile object.
	 *
	 * For every Profile saved from the Enterprise Admin page, its list of feature(s)
	 * that is/are not checked ( no access to that particular feature ) will be
	 * returned.
	 *
	 * When the Profile has all features checked (full access for every feature), then
	 * only the Profile is returned with zero feature (which means full access).
	 *
	 * @return FeatureProfile[] Returns list of Profiles. See more in the function header.
	 */
	public static function getFeatureProfiles()
	{
		require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
		$featureProfilesToReturn = array();

		// Retrieved the full set of available features
		$fullAvailableFeatures = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();

		// Get all saved Profiles.
		$profilesFromDb = self::listRows( self::TABLENAME, 'id', '', '',
													array( 'id', 'profile' ), array(), array( 'code' => true, 'profile' => true ) );
		if( $profilesFromDb ) {
			$profilesFromDb = array_column( $profilesFromDb, 'profile', 'id' );
			$profileIdsFromDb = array_keys( $profilesFromDb );

			// From each saved Profile, collect its features that are checked (allow access).
			$where = DBBase::addIntArrayToWhereClause( 'profile', $profileIdsFromDb, false );
			$featuresFromDb = self::listRows( 'profilefeatures', 'id', '', $where, array( 'id', 'profile', 'feature' ) );
			$featuresMapFromDb = array(); // To collect the features by Profile.
			if( $featuresFromDb ) foreach( $featuresFromDb as $featureProfileId => $featureProfileInfo ) {
				$featuresMapFromDb[$featureProfileInfo['profile']][$featureProfileInfo['feature']] = true;
			}

			// Compiling the to be returned FeatureProfiles. Only features that are unchecked ( no access ) will be returned.
			foreach( $profilesFromDb as $profileId => $profileName ) {
				$unCheckedFeaturesSet = array(); // To collect all the features that are unchecked.
				foreach( $fullAvailableFeatures as $featureId => $featureProfileObject ) {
					if( !isset( $featuresMapFromDb[$profileId][$featureId] ) ) { // Feature is not found in DB = It is unchecked in the admin page.
						$unCheckedFeaturesSet[] = new AppFeature( $featureProfileObject->Name, 'No' ); // Only return the feature which is not checked.
					}
				}
				// Add features to its Profile. When Profile has full access, feature list is empty.
				$featureProfilesToReturn[] = new FeatureProfile( $profileName, $unCheckedFeaturesSet );
			}
		}

		return $featureProfilesToReturn;
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
			'AND ( `feature` < 100 OR `feature` >= 5000 ) '; // core basics: [1..99], plug-ins: [5000..5999]
		$sth = $dbDriver->query($sql);
		$result = self::fetchResults($sth);

		return $result;
	}
}
