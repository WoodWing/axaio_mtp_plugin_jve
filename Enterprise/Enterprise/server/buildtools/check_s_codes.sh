#!/bin/sh
# @package      Enterprise Server
# @subpackage   Build
# @since        9.7
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# Retrieves highest used S-code from the enUS.xml file. Tested for MacOSX.

ENT_DIR=../..

#
# Checks whether or not the enUS.xml file has duplicate S-codes.
#
function checkForDuplicateSCodes {
	resFile="${ENT_DIR}/config/resources/enUS.xml"
	duplicateCodes=`egrep -o '\(S[0-9]*\)' ${resFile} | sort | uniq -d`
	if [ -n "${duplicateCodes}" ]; then 
		echo "ERROR: The resource file ${resFile} has duplicate S-codes!"
		echo "${duplicateCodes}"
		exit 1
	fi
}

#
# Shows a list of the highest S-codes that are in use.
#
function showHighestSCodes {
	resFile="${ENT_DIR}/config/resources/enUS.xml"
	highestCodes=`egrep -o '\(S[0-9]*\)' ${resFile} | sort | tail`
	if [ ! -n "${highestCodes}" ]; then 
		echo "ERROR: The resource file ${resFile} no S-codes!"
		exit 1
	fi
	
	echo "All checks are OK."
	echo "These are the highest S-codes in use: "
	echo "${highestCodes}"
}

checkForDuplicateSCodes
showHighestSCodes