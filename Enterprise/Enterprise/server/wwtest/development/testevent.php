<?php

require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/smartevent.php';

$e = new smartevent_logon('id', 'name', 'server');
$e->fire();

?>