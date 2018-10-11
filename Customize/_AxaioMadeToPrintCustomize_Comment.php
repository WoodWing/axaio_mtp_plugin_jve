<?php

require_once dirname(__FILE__) . '/../config.php';

class AxaioMadeToPrintCustomize_Comment {

    /**
     * customize the check in comment here
     * 
     * @param string $comment
     * @param int $layoutId
     * @param int $layStatusId
     * @param int $layEditionId
     * @param int $success
     * @param array $mtpConfig
     * @param array $commentinfo
     */
    public static function postProcess_filterComment(&$comment, $layoutId, $layStatusId, $layEditionId, $success, $mtpConfig, $commentinfo) {
        if ($success == 1) {
            # comment if processing was successful
            $comment = '[MTP ' . date('Y-m-d H:i:s', time()) . '] OK';
        } else {
            # comment if processing failed
            // unchanged
        }
    }

}
