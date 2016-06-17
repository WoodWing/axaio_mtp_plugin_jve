<?php
/**
 * Database Class: Application Session storage. <br>
 * 
 * Maintains application sessions in database. These sessions are started by users
 * but never properly closed (by checking in locked objects). To re-launch such sessions, 
 * they can be found in the database with some essential details. This is especially used by
 * Web Editor to re-open articles that are currently edit by end-users.
 *
 * For all methods, use hasError() to find out if method has failed.
 * When failed, use getError() to find out error details.
 *
 * @package     SCEnterprise
 * @subpackage  DBClasses
 * @since       v5.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAppSession extends DBBase
{

	/**
	 * Lists all running Application Sessions for an user (as recorded in db).
	 * 
	 * @param string $userId Short name (unique) of user.
	 * @param string $appName Reserved name of application to filter for.
	 * @return array List of found workspace session ids or null on db error. Empty array when none found.
	 */
	public static function findSessionIds( $userId, $appName )
	{
		// Init db access
		self::clearError();
		
		// Search in db
		$where = '`userid` = ? AND `appname` = ?';
		$params = array( $userId, $appName );
		$fields = array( 'sessionid' );
		$groupBy = array( 'sessionid' );
		$rows = self::listRows( 'appsessions', null, null, $where, $fields, 
								$params, null, null, $groupBy );
		
		// Error on failure
		if( self::hasError() ) {
			return null; // failed
		}
		
		// Return list of found results
		$ids = array();
		foreach( $rows as $row ) {
			$ids[] = $row['sessionid'];
		}
		return $ids;
	}

	/**
	 * Find an uniquely identified application session (as recorded in db).
	 * 
	 * @param string $sessionID Application Session id (GUID).
	 * @return object Found Application Session biz object or null when not found.
	 */
	public static function getSession( $sessionID )
	{
		// Init db access
		self::clearError();

		// Search in db
		$where = '`sessionid` = ?';
		$params = array( $sessionID );
		$rows = self::listRows( 'appsessions', null, null, $where, '*', $params );

		// Error on failure
		if( self::hasError() ) {
			return null; // failed
		}
		
		// Return result (or null when not found)
		return $rows ? self::rowsToObj( $rows ) : null;
	}
	
	/**
	 * Creates new Application Session record at db.
	 * 
	 * @param stdClass $session Application Session with all details, including specified session id (GUID).
	 */
	public static function createSession( $session )
	{
		// Init db access
		self::clearError();

		// Create record in db
		$rows = self::objToRows( $session );
		foreach( $rows as $row ) {
			if( !isset( $row['articlemajorversion'] ) || !isset( $row['articleminorversion'] ) ) {
				$row['articlemajorversion'] = -1; // -1 indicates version is unknown! which is typically done for new articles
				$row['articleminorversion'] = -1;
			}
			DBBase::insertRow( 'appsessions', $row );
		}
	}

	/**
	 * Updates existing Application Session (as recorded at db).
	 * 
	 * @param object $session Application Session biz object with all details.
	 */
	public static function updateSession( $session )
	{
		// Init db access
		self::clearError();

		// Update record in db
		$rows = self::objToRows( $session );
		foreach( $rows as $row ) {
			$where = '`sessionid`= ? AND (`articleid` = ? OR `articleid` = ?) '; // *
			$params = array( $row['sessionid'], $row['articleid'], 0 );
			self::updateRow( 'appsessions', $row, $where, $params );
		}
		// * Note that the condition says (articleid == id OR articleid == 0)
		//   because the article could have been saved by the user in the meantime,
		//   it suddenly got an id. In that case, the 0 needs to be updated with the id
		//   in the smart_appsessions table too. This is safe to do since there can not
		//   be multiple new articles. Only when the articles are placed, there can be
		//   multiple in the workspace, but to place an article it must have an id.
	}
	
	/**
	 * Removes existing Application Session (as recorded at db).
	 * 
	 * @param string $sessionID Application Session id (GUID).
	 */
	public static function deleteSession( $sessionID )
	{
		// Init db access
		self::clearError();

		// Remove record from db
		self::deleteRows( 'appsessions', "`sessionid` = '$sessionID'");
	}

	/**
     *  Converts db record/row to biz object both representing an Application Session.
     * 
	 * @param array $rows Application Session db record/row
	 * @return object Application Session biz object (stdClass)
	 */
	static private function rowsToObj( $rows )
	{
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		
		// Take first row, assuming all data is the same, except article data.
		$row = $rows[0];
		
		$obj = new stdClass();
		$obj->ID					= $row['sessionid'];
		$obj->UserID				= $row['userid'];
		$obj->AppName  				= $row['appname'];
		$obj->LastSaved  			= $row['lastsaved']; // timestamp for display (and cleanup?) func
		$obj->ReadOnly  			= $row['readonly'] == 'on' ? true : false; // article opened for readonly
		$obj->DOMVersion 			= DBVersion::joinMajorMinorVersion( $row, 'dom' );
		
		foreach( $rows as $row ) {
			$name = trim($row['articlename']);
			if( $row['articleid'] || !empty($name) ) {
				$article = new stdClass();
				$article->ID		= intval($row['articleid']);
				$article->Name		= $row['articlename'];
				$article->Format	= $row['articleformat'];
				$article->Version 	= DBVersion::joinMajorMinorVersion( $row, 'article' );
				$article->ID		= $article->ID != 0 ? $article->ID : null; // null for new (uncreated) articles
				$article->Version	= strpos( $article->Version, '-1' ) === false ? $article->Version : null; // " "
			} else {
				$article = null;
			}
			$obj->Articles[] = $article;
		}
		
		$name = trim($row['templatename']);
		if( $row['templateid'] || !empty($name) ) {
			$obj->Template = new stdClass();
			$obj->Template->ID		= intval($row['templateid']);
			$obj->Template->Name	= $row['templatename'];
			$obj->Template->Format	= $row['templateformat'];
			$obj->Template->Version	= null;
			$obj->Template->ID 		= $obj->Template->ID != 0 ? $obj->Template->ID : null; // null for global templates
		} else {
			$obj->Template = null;
		}

		if( $row['layoutid'] ) {
			$obj->Layout = new stdClass();
			$obj->Layout->ID 		= intval($row['layoutid']);
			$obj->Layout->Name 		= null;
			$obj->Layout->Format 	= null;
			$obj->Layout->Version 	= DBVersion::joinMajorMinorVersion( $row, 'layout' );
		} else {
			$obj->Layout = null;
		}

		return $obj;
	}
	
	/**
     *  Converts biz object to db record/row both representing an Application Session.
     * 
     *  @param object $obj Application Session biz object (stdClass)
     *  @return array Application Session db record/row
    **/
	static private function objToRows( $obj )
	{	
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';

		$row = array();
		$row['sessionid'] 	= $obj->ID;
		$row['userid']    	= $obj->UserID;
		$row['appname']   	= $obj->AppName;
		$row['lastsaved'] 	= $obj->LastSaved;
		$row['readonly']  	= $obj->ReadOnly ? 'on' : '';
		DBVersion::splitMajorMinorVersion( $obj->DOMVersion, $row, 'dom' );
		
		if( isset($obj->Template) && !is_null($obj->Template) ) {
			$row['templateid']   	= intval($obj->Template->ID);
			$row['templatename'] 	= strval($obj->Template->Name);
			$row['templateformat']	= strval($obj->Template->Format);
		}
		if( isset($obj->Layout) && !is_null($obj->Layout) ) {
			$row['layoutid']   		= intval($obj->Layout->ID);
			if( !is_null($obj->Layout->Version) ) {
				DBVersion::splitMajorMinorVersion( $obj->Layout->Version, $row, 'layout' );
			}
		}
		
		// For each article, copy the first row and update the copy with article info.
		$rows = array();
		if( $obj->Articles ) {
			foreach( $obj->Articles as $article ) {
				$row2 = $row; // copy first row
				$row2['articleid']     = intval($article->ID);
				$row2['articlename']   = strval($article->Name);
				$row2['articleformat'] = strval($article->Format);
				if( !is_null($article->Version) ) {
					DBVersion::splitMajorMinorVersion( $article->Version, $row2, 'article' );
				}
				$rows[] = $row2;
			}
		} else {
			$rows[] = $row;
		}
		return $rows;
	}
}
