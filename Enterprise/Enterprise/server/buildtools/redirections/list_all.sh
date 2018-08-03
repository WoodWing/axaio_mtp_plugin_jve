#!/bin/sh
# @since        10.0
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# List all redirections ever made before e.g. with the update_all.sh bash script.
#
# It uses the microservices of https://redirect.woodwing.com to manage redirections.
# See more info about how to manage redirections for Enterprise:
#    https://confluence.woodwing.net/display/EN/Enterprise+Server+-+Create+URLs+to+help+pages
#

# Include shared definition file.
. common_defines.sh

#
# Displays help how to use this tool.
#
function showUsage {
	echo "${0} <personal-api-key>"
}

# Validate input parameters
if [ ! -n "${1}" ]; then 
	echo "Personal API key not specified. Please request SysOps for a personal API key and provide this parameter."
	showUsage
	exit 1;
fi

# List all redirections.
list=`curl -X GET \
    -H "X-Api-Key: ${1}" \
    -H "Content-Type: application/json" \
    "${REDIRECTION_SERVICE}list"`
echo ${list} | json_pretty_print