<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR.'/server/dbclasses/DBCascadePub.class.php';

class BizCascadePub
{
	/**
	 * Performs cascade copy of a publication.
	 * It copies all definions that are made for the publication.
	 * This includes channels, issues, editions, sections, statuses, deadlines, authorizations, workflow and routing.
	 *
	 * @param int   $srcPubId     Id of source publication to be copied.
	 * @param string  $copyPubName  New name to apply to copied publication.
	 * @param boolean $copyIssues   Whether or not to copy issues too.
	 * @param string  $namePrefix   Debug feature; name prefix to apply to all copied items inside publication. Just to ease recognizion.
	 * @return int Id of the copied publication
	 * @throws BizException when source pub not found or new name is invalid or already exists
	 */
	static public function copyPublication( $srcPubId, $copyPubName, $copyIssues, $namePrefix )
	{
		// Validate id and name
		if( !$srcPubId ) {
			throw new BizException( 'ERR_ARGUMENT', 'client', null );
		}
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$copyPubName = trim( $copyPubName ); //BZ#12402
		BizAdmPublication::validateNewName( $copyPubName );

		// Get source publication
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		$dbDriver = DBDriverFactory::gen();
		$srcPubRow = DBPublication::getPublication( $srcPubId );
		if( !$srcPubRow ) {
			throw new BizException( 'ERR_NOTFOUND', 'client', null, $srcPubId );
		}
		// Check if new pub name does not already exist
		$dupRow = DBPublication::getRow( 'publications', "`publication` = '".$dbDriver->toDBString($copyPubName)."' " );
		if( DBPublication::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'server', null, DBPublication::getError() );
		}
		if( $dupRow ) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'client', $copyPubName );
		}
		// Perform cascade copy
		return DBCascadePub::copyPublication( $srcPubRow, $copyPubName, $copyIssues, $namePrefix );
	}

	/**
	 * Performs cascade copy of an issue.
	 * It copies all definions that are made for the issue.
	 * This includes editions, sections, deadlines, authorizations, workflow and routing.
	 *
	 * @param string    $srcIssueId     Id of source publication to be copied.
	 * @param AdmIssue $copyIssueObj   Issue object
	 * @param string   $namePrefix     Debug feature; name prefix to apply to all copied items inside publication. Just to ease recognizion.
	 * @return string Id of the copied issue
	 * @throws BizException when source issue not found or new name is invalid or already exists
	 */
	static public function copyIssue( $srcIssueId, $copyIssueObj, $namePrefix )
	{
		// Validate id and name
		if( !$srcIssueId ) {
			throw new BizException( 'ERR_ARGUMENT', 'client', null );
		}
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$copyIssueName = trim( $copyIssueObj->Name ); //BZ#12402
		BizAdmPublication::validateNewName( $copyIssueName );

		// Get source issue
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$where = "`id` = ?";
		$params = array( $srcIssueId );
		$srcIssueRow = DBIssue::getRow( DBIssue::TABLENAME, $where, '*', $params );
		if( !$srcIssueRow ) {
			throw new BizException( 'ERR_NOTFOUND', 'client', null, $srcIssueId );
		}

		// Check if new issue name does not already exist in the same channel.
		$where = "`name` = ? AND `channelid` = ? ";
		$params = array( $copyIssueObj->Name, intval( $srcIssueRow['channelid'] ));
		$dupRow = DBIssue::getRow( DBIssue::TABLENAME, $where, '*', $params );
		if( DBIssue::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'server', null, DBIssue::getError() );
		}
		if( $dupRow ) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'client', $copyIssueObj->Name );
		}

		// Resolve issue's publication id
		require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
		$setup = new WW_Utils_ResolveBrandSetup();
		$setup->resolveIssuePubChannelBrand( $srcIssueId );
		$srcPubId = $setup->getPublication()->Id;

		// Perform cascade copy
		return DBCascadePub::copyIssue( $srcPubId, 0, $srcIssueRow, $copyIssueObj, $namePrefix );
	}
	
	/**
	 * Cascade deletes a publication configuration.
	 *
	 * @param int $pubId Id of issue definition to delete.
	 * @throws BizException when it can not be deleted since it is in use by any (deleted) objects.
	 */    
	public static function deletePublication( $pubId )
	{
		if( !$pubId ) { // paranoid check; empty/zero is catastrophal in cascase deletions!
			throw new BizException( 'ERR_ARGUMENT', 'client', null );
		}
		// We can't delete when definition is in use by object or deleted object
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( DBObject::inUseByObjects( $pubId, 'PublicationId', false ) ) {
			throw new BizException( 'ERR_IN_USE_BY_OBJECTS', 'client', null, null, array('{PUBLICATION}') );
		}
		if( DBObject::inUseByObjects( $pubId, 'PublicationId', true ) ) {
			throw new BizException( 'ERR_IN_USE_BY_DELETEDOBJECTS', 'client', null, null, array('{PUBLICATION}') );
		}

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createIssueEventsForPub( $pubId, 'delete' );

		// Perform cascade delete operation of the definition
		DBCascadePub::deletePublication( $pubId );
	}
	
	/**
	 * Cascade deletes a channel configuration.
	 *
	 * @param int $channelId Id of channel definition to delete.
	 * @throws BizException when it can not be deleted since it is in use by any (deleted) objects.
	 */    
	public static function deleteChannel( $channelId )
	{
		if( !$channelId ) { // paranoid check; empty/zero is catastrophal in cascase deletions!
			throw new BizException( 'ERR_ARGUMENT', 'client', null );
		}
		// We can't delete when definition is in use by object or deleted object
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( DBObject::inUseByObjects( $channelId, 'PubChannelId', false ) ) {
			throw new BizException( 'ERR_IN_USE_BY_OBJECTS', 'client', null, null, array('{CHANNEL}') );
		}
		if( DBObject::inUseByObjects( $channelId, 'PubChannelId', true ) ) {
			throw new BizException( 'ERR_IN_USE_BY_DELETEDOBJECTS', 'client', null, null, array('{CHANNEL}') );
		}

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createIssueEventsForChannel( $channelId, 'delete' );

		// Perform cascade delete operation of the definition
		DBCascadePub::deleteChannels( array($channelId) );
		
		// Reset default pub channel Id of the publication to 0
		DBCascadePub::updatePubDefaultChannelId( $channelId ); // BZ#20601
	}
	
	/**
	 * Cascade deletes an issue configuration.
	 *
	 * @param int $issueId Id of issue definition to delete.
	 * @throws BizException when it can not be deleted since it is in use by any (deleted) objects.
	 */    
	public static function deleteIssue( $issueId )
	{
		if( !$issueId ) { // paranoid check; empty/zero is catastrophal in cascase deletions!
			throw new BizException( 'ERR_ARGUMENT', 'client', null );
		}
		// We can't delete when definition is in use by object or deleted object
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( DBObject::inUseByObjects( $issueId, 'IssueId', false ) ) {
			throw new BizException( 'ERR_IN_USE_BY_OBJECTS', 'client', null, null, array('{ISSUE}') );
		}
		if( DBObject::inUseByObjects( $issueId, 'IssueId', true ) ) {
			throw new BizException( 'ERR_IN_USE_BY_DELETEDOBJECTS', 'client', null, null, array('{ISSUE}') );
		}
		// Notify event plugin (note: before delete so we can still get the data)
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createIssueEvent( $issueId, 'delete', true /* convert immediately */ );

		// Perform cascade delete operation of the definition
		DBCascadePub::deleteIssues( array($issueId) );
	}
	
	/**
	 * Cascade deletes an edition configuration.
	 *
	 * @param int $editionId Id of edition definition to delete.
	 * @throws BizException when it can not be deleted since it is in use by any (deleted) objects.
	 */    
	public static function deleteEdition( $editionId )
	{
		if( !$editionId ) { // paranoid check; empty/zero is catastrophal in cascase deletions!
			throw new BizException( 'ERR_ARGUMENT', 'client', null );
		}
		// We can't delete when definition is in use by object or deleted object
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( DBObject::inUseByObjects( $editionId, 'EditionId', false ) ) {
			throw new BizException( 'ERR_IN_USE_BY_OBJECTS', 'client', null, null, array('{EDITION}') );
		}
		if( DBObject::inUseByObjects( $editionId, 'EditionId', true ) ) {
			throw new BizException( 'ERR_IN_USE_BY_DELETEDOBJECTS', 'client', null, null, array('{EDITION}') );
		}
		// Perform cascade delete operation of the definition
		DBCascadePub::deleteEditions( array($editionId) );
	}

	/**
	 * Cascade deletes an section configuration.
	 *
	 * @param int $sectionId Id of section definition to delete.
	 * @throws BizException when it can not be deleted since it is in use by any (deleted) objects.
	 */    
	public static function deleteSection( $sectionId )
	{
		if( !$sectionId ) { // paranoid check; empty/zero is catastrophal in cascase deletions!
			throw new BizException( 'ERR_ARGUMENT', 'client', null  );
		}
		// We can't delete when definition is in use by object or deleted object
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( DBObject::inUseByObjects( $sectionId, 'SectionId', false ) ) {
			throw new BizException( 'ERR_IN_USE_BY_OBJECTS', 'client', null, null, array('{SECTION}') );
		}
		if( DBObject::inUseByObjects( $sectionId, 'SectionId', true ) ) {
			throw new BizException( 'ERR_IN_USE_BY_DELETEDOBJECTS', 'client', null, null, array('{SECTION}') );
		}
		// Perform cascade delete operation of the definition
		DBCascadePub::deleteSections( array($sectionId) );
	}

	/**
	 * Cascade deletes a status configuration.
	 *
	 * @param int $statusId Id of status definition to delete.
	 * @throws BizException when it can not be deleted since it is in use by any (deleted) objects.
	 */    
	public static function deleteStatus( $statusId )
	{
		if( !$statusId ) { // paranoid check; empty/zero is catastrophal in cascase deletions!
			throw new BizException( 'ERR_ARGUMENT', 'client', null );
		}
		// We can't delete when definition is in use by object or deleted object
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( DBObject::inUseByObjects( $statusId, 'StateId', false ) ) {
			throw new BizException( 'ERR_IN_USE_BY_OBJECTS', 'client', null, null, array('{STATE}') );
		}
		if( DBObject::inUseByObjects( $statusId, 'StateId', true ) ) {
			throw new BizException( 'ERR_IN_USE_BY_DELETEDOBJECTS', 'client', null, null, array('{STATE}') );
		}
		// Perform cascade delete operation of the definition
		DBCascadePub::deleteStatuses( array( $statusId ) );
	}
	
}