<?php
/**
 * File descriptor that represents one of the SQL files located in the Enterprise/server/dbscripts folder.
 *
 * @package    Enterprise
 * @subpackage DbScripts
 * @since      v10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_DbScripts_FileDescriptor
{
	/** @var string $type */
	private $type;

	/** @var string $versionTo in 'major.minor' notation */
	public $versionTo;

	/** @var string $versionFrom in 'major.minor' notation */
	public $versionFrom;

	/** @var string $patchName */
	public $patchName;

	/** @var string $sqlFile */
	private $sqlFile;

	/**
	 * Constructor.
	 *
	 * @param string $sqlFile Full file path of a SQL script shipped in the Enterprise/server/dbscripts folder.
	 */
	public function __construct( $sqlFile )
	{
		$this->sqlFile = $sqlFile;
		$this->type = null;
		$this->versionFrom = null;
		$this->versionTo = null;
	}

	/**
	 * Full file path of a SQL script shipped in the Enterprise/server/dbscripts folder.
	 *
	 * @return string
	 */
	public function getSqlFilePath()
	{
		return $this->sqlFile;
	}

	/**
	 * Indicate that the provided SQL file represents a full DB installation script.
	 *
	 * @param string $version in 'major.minor' notation
	 */
	public function setFullInstallType( $version )
	{
		$this->type = 'full';
		$this->versionFrom = $version;
		$this->versionTo = $version;
	}

	/**
	 * Indicates whether the SQL file represents a full DB installation script.
	 *
	 * @return bool
	 */
	public function isFullInstallType()
	{
		return $this->type == 'full';
	}

	/**
	 * Indicate that the provided SQL file represents an upgrade script for an existing DB installation.
	 *
	 * @param string $versionFrom in 'major.minor' notation
	 * @param string $versionTo in 'major.minor' notation
	 */
	public function setUpgradeType( $versionFrom, $versionTo )
	{
		$this->type = 'upgrade';
		$this->versionFrom = $versionFrom;
		$this->versionTo = $versionTo;
	}

	/**
	 * Indicates whether the SQL file represents an upgrade script for an existing DB installation.
	 *
	 * @return bool
	 */
	public function isUpgradeType()
	{
		return $this->type == 'upgrade';
	}

	/**
	 * Indicate that the provided SQL file represents a pre-upgrade script for an existing DB installation.
	 *
	 * @param string $versionFrom in 'major.minor' notation
	 * @param string $versionTo in 'major.minor' notation
	 */
	public function setPreUpgradeType( $versionFrom, $versionTo )
	{
		$this->type = 'pre_upgrade';
		$this->versionFrom = $versionFrom;
		$this->versionTo = $versionTo;
	}

	/**
	 * Indicates whether the SQL file represents a pre-upgrade script for an existing DB installation.
	 *
	 * @return bool
	 */
	public function isPreUpgradeType()
	{
		return $this->type == 'pre_upgrade';
	}

	/**
	 * Indicate that the provided SQL file represents a post-upgrade script for an existing DB installation.
	 *
	 * @param string $versionFrom in 'major.minor' notation
	 * @param string $versionTo in 'major.minor' notation
	 */
	public function setPostUpgradeType( $versionFrom, $versionTo )
	{
		$this->type = 'post_upgrade';
		$this->versionFrom = $versionFrom;
		$this->versionTo = $versionTo;
	}

	/**
	 * Indicates whether the SQL file represents a post-upgrade script for an existing DB installation.
	 *
	 * @return bool
	 */
	public function isPostUpgradeType()
	{
		return $this->type == 'post_upgrade';
	}

	/**
	 * Indicate that the provided SQL file represents a patch script for an existing DB installation.
	 *
	 * @param string $versionFrom in 'major.minor' notation
	 * @param string $versionTo in 'major.minor' notation
	 * @param string $patchName
	 */
	public function setPatchType( $versionFrom, $versionTo, $patchName )
	{
		$this->type = 'patch';
		$this->versionFrom = $versionFrom;
		$this->versionTo = $versionTo;
		$this->patchName = $patchName;
	}

	/**
	 * Indicates whether the SQL file represents a patch script for an existing DB installation.
	 *
	 * @return bool
	 */
	public function isPatchType()
	{
		return $this->type == 'patch';
	}

	/**
	 * Callback function that does sort files on their names.
	 *
	 * For example the SQL files having a 'pre' as prefix are put on top of the list.
	 *
	 * @param WW_DbScripts_FileDescriptor $lhs
	 * @param WW_DbScripts_FileDescriptor $rhs
	 * @return int Returns -1 if LHS < RHS, or returns 1 if LHS > RHS, or returns 0 if they are equal.
	 */
	public static function compare( $lhs, $rhs )
	{
		$compareVersionFrom = version_compare( $lhs->versionFrom, $rhs->versionFrom );
		if( $compareVersionFrom == 0 ) {
			$compareVersionTo = version_compare( $lhs->versionTo, $rhs->versionTo );
			if( $compareVersionTo == 0 ) {
				$compare = strcmp( self::getSortToken( $lhs ), self::getSortToken( $rhs ) );
			} else {
				$compare = $compareVersionFrom;
			}

		} else {
			$compare = $compareVersionFrom;
		}
		return $compare;
	}

	/**
	 * Provides a sort token that can be used to compare files that have the same versionFrom and versionTo values.
	 *
	 * @param WW_DbScripts_FileDescriptor $file
	 * @return string
	 */
	private static function getSortToken( $file )
	{
		$token = '';
		if( $file->isPreUpgradeType() ) {
			$token = 'a';
		} elseif( $file->isFullInstallType() ) {
			$token = 'b';
		} elseif( $file->isUpgradeType() ) {
			$token = 'c';
		} elseif( $file->isPostUpgradeType() ) {
			$token = 'd';
		} elseif( $file->isPatchType() ) {
			$token = 'e';
		}
		return $token;
	}
}