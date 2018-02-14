#!/bin/sh
# @package      Enterprise
# @subpackage   Build
# @since        7.6
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# This bash shell script calls the genservices/wsdl2phpcli.php module which does the following:
# - Validate manual changes applied to any of the supported WSDL files against 3rd party SOAP parser (Java).
# - Re-generate PHP and Java files (data classes, service classes, reponses, requests, etc) from WSDL files.
#
# After running the script, the changed files can be found in Sourcetree under "Unstaged files".
# Those changes needs to be reviewed (check for unexpected changes) and committed manually.
#
# Note that since Enterprise 10.2.0 this script is entirely rewritten to support web services provided by server plugins.

cd ./genservices
php wsdl2phpcli.php $*
cd -