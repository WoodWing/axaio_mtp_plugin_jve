<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/utils/PhpInfo.php';

print WW_Utils_PhpInfo::getAllInfo();
