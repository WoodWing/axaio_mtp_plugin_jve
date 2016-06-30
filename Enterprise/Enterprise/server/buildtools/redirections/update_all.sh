#!/bin/sh
# @package      Enterprise
# @subpackage   Build
# @since        10.0
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# Creates redirection URLs for all documentation URLs used in Enterprise Server.
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
	echo "${0} <personal-api-key> <enterprise-major-version> "
}

#
# Creates or updates a redirection URL.
#
# @param string $1 Personal API key
# @param string $2 Source path
# @param string $3 Target URL (redirect to)
#
function saveRedirection {
	echo "Saving redirection URL ${2}"
	curl -X POST \
		-H "X-Api-Key: ${1}" \
		-H "Content-Type: application/json" \
		-d '{"path":"'${2}'","redirect":"'${3}'"}' \
		"${REDIRECTION_SERVICE}" | json_pretty_print
	echo
}


#
# Validates an existing redirection URL.
#
# It calls the give source URL and checks if the micro service returns the redirection URL.
#
# @param string $1 Personal API key
# @param string $2 Source path
# @param string $3 Target URL (redirect to)
#
function testRedirection {
	echo "Testing redirection URL ${2}"
	redirTest=`curl "${REDIRECTION_SERVICE}?path=${2}"`
	echo "Response:"
	echo ${redirTest} | json_pretty_print
	sourceUrl=`php -r "\\$json = json_decode( \\$argv[1] ); print \\$json->path;" "${redirTest}"`
	if [ "${sourceUrl}" != "${2}" ]; then
		echo "Error: The returned source URL is not as expected: ${sourceUrl}"
		exit 1
	fi
	targetUrl=`php -r "\\$json = json_decode( \\$argv[1] ); print \\$json->redirect;" "${redirTest}"`
	if [ "${targetUrl}" != "${3}" ]; then
		echo "Error: The target URL is not as expected: ${targetUrl}"
		exit 1
	fi
	echo "Redirection OK"
}

#
# Creates or updates a redirection URL and validates it afterwards.
#
# @param string $1 Personal API key
# @param string $2 Source path
# @param string $3 Target URL (redirect to)
#
function saveAndTestRedirection {
	echo "-------------------------"
	saveRedirection ${1} ${2} ${3} 
	testRedirection ${1} ${2} ${3} 
}

#
# Saves all known redirections for the PHP manual pages as referred from our Health Check pages.
#
# Example:
#    Source URL: https://redirect.woodwing.com/v1/?path=enterprise-server/10/help/zend-opcache
#    Target URL: https://helpcenter.woodwing.com/hc/en-us/articles/205501875
#
# @param string $1 Personal API key
# @param string $1 Enterprise Server major version
#
function saveAndTestHelpCenterRedirections {
	# https://redirect.woodwing.com/v1/?path=enterprise-server/10/help/zend-opcache
	saveAndTestRedirection "${1}" "enterprise-server/${2}/help/zend-opcache" "https://helpcenter.woodwing.com/hc/en-us/articles/209990166" 

	# Note that this Analytics help page is removed already: https://helpcenter.woodwing.com/hc/en-us/articles/204805639
}

#
# Saves all known redirections for the Help Center articles as referred from our Health Check pages.
#
# @param string $1 Personal API key
#
function saveAndTestPhpManualRedirections {
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/image-installation" "http://php.net/manual/en/image.installation.php"
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/exif-installation" "http://php.net/manual/en/exif.installation.php"
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/sockets-installation" "http://php.net/manual/en/sockets.installation.php"
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/mbstring-installation" "http://php.net/manual/en/mbstring.installation.php"
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/soap-installation" "http://php.net/manual/en/soap.installation.php"
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/iconv-installation" "http://php.net/manual/en/iconv.installation.php"
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/curl-installation" "http://php.net/manual/en/curl.installation.php"
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/zlib-installation" "http://php.net/manual/en/zlib.installation.php"
	saveAndTestRedirection "${1}" "enterprise-server/php-manual/xsl-installation" "http://php.net/manual/en/xsl.installation.php"
}

# Validate input parameters
if [ ! -n "${1}" ]; then 
	echo "Personal API key not specified. Please request SysOps for a personal API key and provide this parameter."
	showUsage
	exit 1;
fi
if [ ! -n "${2}" ]; then 
	echo "Enterprise Server major version not specified. Please provide this parameter."
	showUsage
	exit 1;
fi

# Save and test all redirections as referred from our Health Check pages.
saveAndTestHelpCenterRedirections ${1} ${2}
saveAndTestPhpManualRedirections ${1}
