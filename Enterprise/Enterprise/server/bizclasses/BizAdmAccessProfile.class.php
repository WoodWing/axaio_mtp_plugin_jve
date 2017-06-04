<?php

/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v10.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Business logics and rules that can handle admin access profile objects operations.
 * This is all about the configuration of access profiles in the workflow definition.
 * Note that workflow access profile objects are NOT the same and so are handled elsewhere.
 */

/**
 * Class BizAdmAccessProfile
 *
 * Contains business logic for operations on access profiles.
 *
 * This class provides function for validation and validates the user access and user
 * input that is sent in a request. Only if everything is valid an operation will be
 * performed on the data.
 *
 */
class BizAdmAccessProfile
{
	/**
	 * Checks if an user has admin access to the entire system.
	 *
	 * @throws BizException When user has no access.
	 */
	static private function checkSysAdminAccess()
	{
		$user = BizSession::getShortUserName();
		if( !hasRights( DBDriverFactory::gen(), $user ) ) { // not a system admin?
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', null );
		}
	}

	/**
	 * Validates an access profile and throws an error if something is incorrect.
	 *
	 * @param boolean $isCreate If true the context is a create, else it is a modify.
	 * @param AdmAccessProfile $accessProfile
	 * @throws BizException when the access profile is invalid.
	 */
	static private function validateAccessProfile( $isCreate, $accessProfile )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmAccessProfile.class.php';

		//access profile id validation
		if( $isCreate && $accessProfile->Id ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'An access profile can not have an id while it is being created.' );
		}
		if( !$isCreate && $accessProfile->Id < 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Id can not be negative.' );
		}
		if( !$isCreate && !DBAdmAccessProfile::getAccessProfiles( array( $accessProfile->Id ) ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', '', null, array( '{MNU_PROFILES}', $accessProfile->Id ) );
		}

		//access profile name validation
		if( isset( $accessProfile->Name ) && DBAdmAccessProfile::accessProfileNameExists( $accessProfile ) ) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', 'Name "'.$accessProfile->Name.'" is duplicate.' );
		}
		if( $accessProfile->Name === '' || ( $isCreate && !isset( $accessProfile->Name ) ) ) { //null is accepted for modify
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Access profile name can not be empty.' );
		}

	}

	/**
	 * Creates access profile objects at the database.
	 *
	 * Calls the db layer to perform an insert on the database. It
	 * returns the new ids of the inserted access profiles if successful.
	 *
	 * @param AdmAccessProfile[] $accessProfiles List of access profile objects to be inserted.
	 * @return integer[] List of ids of the inserted access profiles.
	 */
	public static function createAccessProfiles( array $accessProfiles )
	{
		self::checkSysAdminAccess();
		require_once BASEDIR.'/server/dbclasses/DBAdmAccessProfile.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmProfileFeature.class.php';

		$newAccessProfileIds = array();
		if( $accessProfiles ) foreach( $accessProfiles as $accessProfile ) {
			self::validateAccessProfile( true, $accessProfile );
			$newAccessProfileId = DBAdmAccessProfile::createAccessProfile( $accessProfile );
			if( $accessProfile->ProfileFeatures && $newAccessProfileId ) {
				$accessProfile->ProfileFeatures = self::resolveProfileFeatureIdsByName( $accessProfile->ProfileFeatures );
				DBAdmProfileFeature::updateProfileFeatures( $newAccessProfileId, $accessProfile->ProfileFeatures );
			}
			if( $newAccessProfileId ) {
				$newAccessProfileIds[] = $newAccessProfileId;
			}
		}
		return $newAccessProfileIds;
	}

	/**
	 * Updates existing access profile objects at the database.
	 *
	 * Calls the db layer to perform an update on the database. If
	 * successful it resolves the profile features of the modified
	 * access profiles and returns them all.
	 *
	 * @param array|null $requestModes Input 'GetProfileFeatures' is accepted or null.
	 * @param AdmAccessProfile[] $accessProfiles List of access profiles to be modified.
	 * @return AdmAccessProfile[] The list of modified access profile objects.
	 */
	public static function modifyAccessProfiles( $requestModes, array $accessProfiles )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmAccessProfile.class.php';

		self::checkSysAdminAccess();
		if( $accessProfiles ) foreach( $accessProfiles as $accessProfile ) {
			self::validateAccessProfile( false, $accessProfile );
			if( $accessProfile->ProfileFeatures ) {
				$accessProfile->ProfileFeatures = self::resolveProfileFeatureIdsByName( $accessProfile->ProfileFeatures );
			}
			DBAdmAccessProfile::modifyAccessProfile( $accessProfile ) ? true : false;
			if( $accessProfile->ProfileFeatures ) {
				require_once BASEDIR.'/server/dbclasses/DBAdmProfileFeature.class.php';
				DBAdmProfileFeature::updateProfileFeatures( $accessProfile->Id, $accessProfile->ProfileFeatures );

				//update authorization rights (flags)
				require_once BASEDIR.'/server/bizclasses/BizAdmWorkflowUserGroupAuthorization.class.php';
				BizAdmWorkflowUserGroupAuthorization::updateWorkflowUserGroupAuthorizationRights( $accessProfile->Id );
				if( is_null( $requestModes ) || in_array( 'GetProfileFeatures', $requestModes ) ) {
					$accessProfile->ProfileFeatures = self::resolveProfileFeatures( $accessProfile->ProfileFeatures );
				}
			}
		}
		return $accessProfiles;
	}

	/**
	 * Retrieves a list of access profiles from the database by id.
	 *
	 * Calls the db layer to perform a select on the database. The
	 * profile features of gotten access profiles are resolved before
	 * returning them.
	 *
	 * @param array $requestModes Input 'GetProfileFeatures' is accepted or null.
	 * @param integer[]|null $accessProfileIds List of access profile ids or null for all access profiles
	 * @throws BizException when an access profile id is negative.
	 * @return AdmAccessProfile[]
	 */
	public static function getAccessProfiles( $requestModes, $accessProfileIds )
	{
		self::checkSysAdminAccess();
		require_once BASEDIR.'/server/dbclasses/DBAdmProfileFeature.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAccessProfile.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

		//if the array contains only a 0, a template access profile with all profile features should be returned.
		if( count( $accessProfileIds ) == 1 && $accessProfileIds[0] === 0 ) {
			$accessProfile = new AdmAccessProfile();
			$accessProfile->ProfileFeatures = self::resolveProfileFeatures( null );
			return array( $accessProfile );
		}

		if( $accessProfileIds ) foreach( $accessProfileIds as $accessProfileId ) {
			if( $accessProfileId <= 0 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Id must be positive.' );
			}
		}
		$accessProfiles = DBAdmAccessProfile::getAccessProfiles( $accessProfileIds );

		if( is_null( $requestModes ) || in_array( 'GetProfileFeatures', $requestModes ) ) {
			foreach( $accessProfiles as &$accessProfile ) {
				$profileFeatures = DBAdmProfileFeature::getProfileFeatures( $accessProfile->Id );
				$accessProfile->ProfileFeatures = self::resolveProfileFeatures( $profileFeatures );
			}
		}
		return $accessProfiles;
	}

	/**
	 * Deletes access profile objects from the database based on id.
	 *
	 * @param integer[] $accessProfileIds
	 */
	public static function deleteAccessProfiles( array $accessProfileIds )
	{
		self::checkSysAdminAccess();
		require_once BASEDIR.'/server/dbclasses/DBAdmAccessProfile.class.php';
		DBAdmAccessProfile::deleteAccessProfiles( $accessProfileIds );
	}

	/**
	 * Resolves the ids for a given list of profile features (by their names).
	 *
	 * Enriches a profile feature array with ids from the sys profile feature object.
	 *
	 * @param AdmProfileFeature[] $profileFeatures A list of profile feature objects.
	 * @return AdmProfileFeature[] List of profile feature objects with an id as key.
	 */
	public static function resolveProfileFeatureIdsByName( array $profileFeatures )
	{
		static $nameIdMap = null;
		if( !$nameIdMap ) { // do only once per service
			require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
			$sysFeatureProfiles = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
			foreach( $sysFeatureProfiles as $key => $sysFeatureProfile ) {
				$nameIdMap[$sysFeatureProfile->Name] = $key;
			}
		}
		$profileFeaturesWithKey = array();
		if( $profileFeatures ) foreach( $profileFeatures as $profileFeature ) {
			if( isset( $nameIdMap[$profileFeature->Name] ) ) {
				$profileFeaturesWithKey[ $nameIdMap[$profileFeature->Name] ] = $profileFeature;
			}
		}
		return $profileFeaturesWithKey;
	}

	/**
	 * Resolves information of AdmProfileFeatures.
	 *
	 * Enriches a profile feature object from the database with information from
	 * the workflow sysFeatureProfile object.
	 *
	 * @param AdmProfileFeature[]|null $profileFeatures List to be enriched, or NULL to get new default list (template).
	 * @return AdmProfileFeature[] Enriched list of profile features.
	 */
	public static function resolveProfileFeatures( $profileFeatures )
	{
		$allProfileFeatures = array();

		require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
		$sysFeatureProfiles = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();

		if( !is_null($profileFeatures) ) {
			foreach( $sysFeatureProfiles as $sysFeatureProfileId => $sysFeatureProfile ) {
				if( array_key_exists( $sysFeatureProfileId, $profileFeatures ) ) {
					$profileFeature = $profileFeatures[$sysFeatureProfileId];
					$profileFeature->Name = $sysFeatureProfile->Name;
					$profileFeature->DisplayName = $sysFeatureProfile->Display;
					$allProfileFeatures[$sysFeatureProfile->Name] = $profileFeature;
				}
			}
		} else {
			foreach( $sysFeatureProfiles as $sysFeatureProfile ) {
				$profileFeature = new AdmProfileFeature();
				$profileFeature->Name = $sysFeatureProfile->Name;
				$profileFeature->DisplayName = $sysFeatureProfile->Display;
				$profileFeature->Value = ( $sysFeatureProfile->Default ) ? 'No' : 'Yes'; //Default value is either empty or 'No'.
				$allProfileFeatures[$sysFeatureProfile->Name] = $profileFeature;
			}
		}
		return $allProfileFeatures;
	}
}