<?php
/**
 * Hooks into the Send To Next workflow web service.
 * Called when an end-user send a file to the next workflow status (typically using CS).
 *
 * @since      4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflUnlockObjects_EnterpriseConnector.class.php';

class Elvis_WflUnlockObjects extends WflUnlockObjects_EnterpriseConnector {

    final public function getPrio() { return self::PRIO_DEFAULT; }
    final public function getRunMode() { return self::RUNMODE_BEFORE; }

	/**
	 * @inheritdoc
	 */
    final public function runBefore( WflUnlockObjectsRequest &$req )
    {
	    require_once BASEDIR.'/config/config_elvis.php'; // auto-loading
	    if( $req->IDs ) foreach( $req->IDs as $id ) {
		    $elvisId = self::getDocumentIdForObjectId( $id );
		    if( !is_null( $elvisId ) ) {
			    $service = new Elvis_BizClasses_AssetService();
			    $service->undoCheckout( $elvisId );
		    }
	    }
    }

    // Not called.
    final public function runAfter( WflUnlockObjectsRequest $req, WflUnlockObjectsResponse &$resp )
    {
    }

	 // Not called.
    final public function runOverruled( WflUnlockObjectsRequest $req )
    {
    }

    static public function getDocumentIdForObjectId( $id )
    {
        require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

        $params = array( $id, 'ELVIS' );
        $where = '`id` = ? and `contentsource` = ?';
        $fields = array( 'documentid' );
        $row = DBObject::getRow( DBObject::TABLENAME, $where, $fields, $params );

        return $row ? $row['documentid'] : null;
    }
}