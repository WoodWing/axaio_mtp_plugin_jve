<?php
/**
 * Does web service logging at database level.
 *
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBLog extends DBBase
{
	const TABLENAME = 'log';

	/**
	 * Leaves footprint for a service call related to an object.
	 *
	 * The footprint is written in the smart_log table with some info taken from the given object.
	 *
	 * @param string|null $user User executing the service
	 * @param string $service Name of service being logged
	 * @param string $id Object id involving the service
	 * @param string $publ Brand id of object
	 * @param string $issue Issue id of object
	 * @param string $section Category of object
	 * @param string $state Status of object
	 * @param string $parent
	 * @param string $lock
	 * @param string $rendition
	 * @param string $type
	 * @param string $routeto
	 * @param string $edition
	 * @param string $version
	 */
	static public function logService( $user = null, $service = '', $id = '', 
		$publ = '', $issue = '', $section = '', $state = '', $parent = '', $lock = '',
		$rendition = '', $type = '', $routeto = '', $edition = '', $version = '' )
	{
		$channel=0; // TODO v6.0

		switch ($service) {
			case 'LogOn':
			case 'LogOff':
				$loglevel = 1;
				break;
			default:
				$loglevel = 2;
				break;
		}

		if ($loglevel <= LOGLEVEL) {

			if( is_null( $user ) ) {
				require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
				$user = BizSession::getUserInfo('user');
			}

			require_once BASEDIR.'/server/utils/UrlUtils.php';

			$dbDriver = DBDriverFactory::gen();
			$user = $dbDriver->toDBString($user);
			$routeto = $dbDriver->toDBString($routeto);
			$db = $dbDriver->tablename(self::TABLENAME);
			$date = date('Y-m-d\TH:i:s', time());
			$ip = WW_Utils_UrlUtils::getClientIP();
			$fields = '`user`, `service`, `date`, `ip`, `objectid`, `publication`, `issue`, `section`, `state`, `parent`, `lock`, `rendition`, `type`, `routeto`, `edition`, `channelid`';
			$values = "'$user', '$service', '$date', '$ip', '$id', '$publ', '$issue', '$section', '$state', '$parent', '$lock', '$rendition', '$type', '$routeto', '$edition', '$channel'";
			if( !empty($version) ) {
				$verRow = array();
				require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
				DBVersion::splitMajorMinorVersion( $version, $verRow ); // update verRow
				$fields .= ', `minorversion`, `majorversion`';
				$values .= ", '".$verRow['minorversion']."', '".$verRow['majorversion']."'";
			}
			$sql = 'INSERT INTO '.$db.' ('.$fields.') VALUES ('.$values.')';
			$sql = $dbDriver->autoincrement($sql);
			$dbDriver->query($sql);
		}
	}

	/**
	 * Leaves footprint for a service call related to an object.
	 *
	 * The footprint is written in the smart_log table with some info taken from the given object.
	 *
	 * @param string|null $user User executing the service
	 * @param string $service Name of service being logged.
	 * @param array $objProps Object properties
	 * @param array|null $extraInfo Additional info/properties
	 */
	static public function logServiceEx( $user, $service, $objProps, $extraInfo = null )
	{
		self::logService( $user, $service,
			array_key_exists( 'ID',        $objProps ) ? $objProps['ID'] : '',
			array_key_exists( 'PublicationId', $objProps ) ? $objProps['PublicationId'] : '',
			array_key_exists( 'IssueId',   $objProps ) ? $objProps['IssueId'] : '',
			array_key_exists( 'SectionId', $objProps ) ? $objProps['SectionId'] : '',
			array_key_exists( 'StateId',   $objProps ) ? $objProps['StateId'] : '',
			($extraInfo && array_key_exists( 'parent',    $extraInfo )) ? $extraInfo['parent'] : '',
			($extraInfo && array_key_exists( 'lock',      $extraInfo )) ? $extraInfo['lock'] : '',
			($extraInfo && array_key_exists( 'rendition', $extraInfo )) ? $extraInfo['rendition'] : '',
			array_key_exists( 'Type',      $objProps ) ? $objProps['Type'] : '',
			array_key_exists( 'RouteTo',   $objProps ) ? $objProps['RouteTo'] : '',
			array_key_exists( 'Edition',   $objProps ) ? $objProps['Edition'] : '',
			array_key_exists( 'Version',   $objProps ) ? $objProps['Version'] : '' );
	}

	/**
	 * Leaves footprint for a service call related to an object.
	 *
	 * The footprint is written in the smart_log table with some info taken from the given object.
	 *
	 * @param string $service Name of the web service being called/handled.
	 * @param Object $object The related object
	 * @param array|null $extraInfo
	 */
	static public function logServiceForObject( $service, $object, $extraInfo = null )
	{
		self::logService( null, $service,
			$object->MetaData->BasicMetaData->ID,
			$object->MetaData->BasicMetaData->Publication->Id,
			'', // Issue
			$object->MetaData->BasicMetaData->Category->Id,
			$object->MetaData->WorkflowMetaData->State->Id,
			($extraInfo && array_key_exists( 'parent',    $extraInfo )) ? $extraInfo['parent'] : '',
			($extraInfo && array_key_exists( 'lock',      $extraInfo )) ? $extraInfo['lock'] : '',
			($extraInfo && array_key_exists( 'rendition', $extraInfo )) ? $extraInfo['rendition'] : '',
			$object->MetaData->BasicMetaData->Type,
			$object->MetaData->WorkflowMetaData->RouteTo,
			'', // Edition
			$object->MetaData->WorkflowMetaData->Version
		);
	}

	/**
	 * Leaves footprints for a service call related to multiple objects.
	 *
	 * The footprints are written in the smart_log table with some info taken from the given objects.
	 *
	 * @param string|null $user User executing the service
	 * @param string $service Context for log call
	 * @param array $ids List of object ids
	 * @param string $type Object type
	 * @param string $publ Brand id (same for all object ids passed)
	 * @param array $sections Array of sections for each id
	 * @param array $states Array with states for each id
	 * @param array $versions Array with versions for each id
	 * @param array $routeTos Array with RouteTo information.
	 */
	static public function logMultiService( $user = null, $service = '', $ids = array(), $type = '',
									   $publ = '', $sections = array(), $states = array(), $versions = array(), $routeTos = array() )
	{
		switch( $service ) {
			case 'LogOn':
			case 'LogOff':
				$loglevel = 1;
				break;
			default:
				$loglevel = 2;
				break;
		}

		if( $loglevel <= LOGLEVEL ) {
			if( is_null( $user ) ) {
				require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
				$user = BizSession::getUserInfo('user');
			}

			require_once BASEDIR.'/server/utils/UrlUtils.php';

			$date = date('Y-m-d\TH:i:s', time());
			$ip = WW_Utils_UrlUtils::getClientIP();

			$columnNames = array(
				'user',
				'service',
				'date',
				'ip',
				'objectid',
				'publication',
				'section',
				'state',
				'type',
				'minorversion',
				'majorversion',
				'routeto',
			);

			$values = array();
			foreach( $ids as $key => $id ) {
				$verRow = array();
				DBVersion::splitMajorMinorVersion( $versions[$key], $verRow );

				$values[] = array(
					strval( $user ),
					strval( $service ),
					strval( $date ),
					strval( $ip ),
					intval( $id ),
					intval( $publ ),
					intval( $sections[$key] ),
					intval( $states[$key] ),
					strval( $type ),
					intval( $verRow['minorversion'] ),
					intval( $verRow['majorversion'] ),
					strval( $routeTos[$key] ),
				);
			}

			self::insertRows( self::TABLENAME, $columnNames, $values );
		}
	}
}
