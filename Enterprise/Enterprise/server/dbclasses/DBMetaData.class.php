<?php
/**
 * Implements DB side of custom meta data (properties) and usage (dialog setup / action properties)
 * 
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBMetaData extends DBBase
{
	/**
	 * Retrieves the object property configurations made system wide or brand specific.
	 *
	 * To retrieve system wide (brand-less) configurations only, pass in NULL (or zero) for $pubIds.
	 * For brand specific configurations, pass in list of brand ids or a single brand id.
	 *
	 * @param integer[]|integer|null $pubIds Brand id(s). Since 9.7 an array is allowed to retrieve for many brands at once.
	 * @return ObjectTypeProperty[] Two-dim array indexed per brand id when $pubIds is array, else one-dim array.
	 * @throws BizException on fatal DB errors.
	 */
	public static function getObjectProperties( $pubIds=null )
	{
		if( !$pubIds ) $pubIds = 0;
		$dbDriver = DBDriverFactory::gen();
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		$sth = DBProperty::getPropertiesSth( $pubIds );
		if( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		// fetch into array
		$ret = array();
		$tmp = null;
		$isfirst = true;
		$lastType = null;
		$lastPubId = null;
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			if( $isfirst || $lastPubId !== intval( $row['publication'] ) || $lastType !== trim( $row['objtype'] ) ) {
				if( $tmp != null ) {
					if( is_array( $pubIds ) ) {
						$ret[ $lastPubId ][] = $tmp;
					} else {
						$ret[] = $tmp;
					}
				}
				$tmp = new ObjectTypeProperty( trim( $row['objtype'] ) );
				$tmp->Properties = array();
				$lastType = trim( $row['objtype'] );
				$lastPubId = intval( $row['publication'] );
				$isfirst = false;
			}
			$list = explode( ',', $row['valuelist'] );
			if( empty( $list ) ) {
				$list = null;
			}
			$category = trim( $row['category'] );
			if( empty( $category ) ) {
				$category = null;
			}
			$defaultValue = trim( $row['defaultvalue'] );
			if( empty( $defaultValue ) ) {
				$defaultValue = null;
			}
			$minValue = trim( $row['minvalue'] );
			if( empty( $minValue ) ) {
				$minValue = null;
			}
			$maxValue = trim( $row['maxvalue'] );
			if( empty( $maxValue ) ) {
				$maxValue = null;
			}
			$property = new PropertyInfo();
			$property->Name = $row['name'];
			$property->DisplayName = trim( $row['dispname'] );
			$property->Category = $category;
			$property->Type = trim( $row['type'] );
			$property->DefaultValue = $defaultValue;
			$property->ValueList = $list;
			$property->MinValue = $minValue;
			$property->MaxValue = $maxValue;
			$property->MaxLength = intval( $row['maxlen'] );
			$propertyValueArray = unserialize( $row['propertyvalues'] );
			$property->PropertyValues = ( $propertyValueArray ) ? $propertyValueArray : array();
			$tmp->Properties[] = $property;
		}
		if( $tmp != null ) {
			if( is_array( $pubIds ) ) {
				$ret[ $lastPubId ][] = $tmp;
			} else {
				$ret[] = $tmp;
			}
		}
		return $ret;
	}
	
	/**
	 * Retrieves the dialog setup configurations made system wide or brand specific.
	 *
	 * To retrieve system wide (brand-less) configurations only, pass in NULL (or zero) for $pubIds.
	 * For brand specific configurations, pass in list of brand ids or a single brand id.
	 *
	 * @param integer[]|integer|null $pubIds Brand id(s). Since 9.7 an array is allowed to retrieve for many brands at once.
	 * @return ActionProperty[] Two-dim array indexed per brand id when $pubIds is array, else one-dim array.
	 * @throws BizException on fatal DB errors.
	 */
	public static function getActionProperties( $pubIds=null )
	{
		if( !$pubIds ) $pubIds = 0;
		$dbDriver = DBDriverFactory::gen();
		require_once BASEDIR.'/server/dbclasses/DBActionproperty.class.php'; 
		$sth = DBActionproperty::getPropertyUsagesSth( $pubIds );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		// fetch into array
		$ret = array();
		$tmp = null;
		$lastaction = null;
		$lasttype = null;
		$lastPubId = null;
		$isfirst = true;
		while( ($row = $dbDriver->fetch($sth)) ) {
			if ($isfirst || $lastPubId !== intval($row['publication']) || $lasttype !== trim($row['type']) || $lastaction !== trim($row['action'])) {
				if ($tmp != null && substr($lastaction,0,8) != "QueryOut") {
					if( is_array($pubIds) ) {
						$ret[$lastPubId][] = $tmp;
					} else {
						$ret[] = $tmp;
					}
				}
				$tmp = new ActionProperty(trim($row['action']), trim($row['type']));
				$tmp->Properties = array();
				$lasttype = trim($row['type']);
				$lastaction = trim($row['action']);
				$lastPubId = intval($row['publication']);
				$isfirst = false;
			}
			$tmp->Properties[] = new PropertyUsage( $row['property'], $row['edit']=='on', 
													$row['mandatory']=='on', $row['restricted']=='on', $row['refreshonchange']=='on' );
		}
		if ($tmp != null && substr($lastaction,0,8) != "QueryOut") {
			if( is_array($pubIds) ) {
				$ret[$lastPubId][] = $tmp;
			} else {
				$ret[] = $tmp;
			}
		}

		return $ret;
	}
}
