<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Send To Next workflow web service.
 * Called when an end-user send a file to the next workflow status (typically using CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflUnlockObjects_EnterpriseConnector.class.php';

class Elvis_WflUnlockObjects extends WflUnlockObjects_EnterpriseConnector {

    final public function getPrio() { return self::PRIO_DEFAULT; }
    final public function getRunMode() { return self::RUNMODE_BEFORE; }

    // Not called.
    final public function runBefore( WflUnlockObjectsRequest &$req ) {
        foreach($req->IDs as $id) {
            $elvisId = self::getDocumentIdForObjectId($id);
            if (!is_null($elvisId)) {
                require_once dirname(__FILE__) . '/logic/ElvisContentSourceService.php';
                $service = new ElvisContentSourceService();
                $service->undoCheckout($elvisId);
            }
        }
    }

    final public function runAfter( WflUnlockObjectsRequest $req, WflUnlockObjectsResponse &$resp ) {
    	$req=$req; //keep analyzer happy
    	$resp=$resp;
    }

    // Not called.
    final public function runOverruled( WflUnlockObjectsRequest $req ) {
        $req = $req; // keep analyzer happy
    }

    static public function getDocumentIdForObjectId( $id ) {
        require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
        $params = array( $id, 'ELVIS' );
        $where = '`id` = ? and `contentsource` = ?';
        $fields = array( 'documentid' );
        $row = DBObject::getRow( DBObject::TABLENAME, $where, $fields, $params );
        return $row ? $row['documentid'] : null;
    }

}