#!/bin/sh
#
# This script simulates the phpStorm Code Inspection as executed on the Zetes build machine,
# but then on your local MacOSX development machine. Assumed is that you have phpStorm installed.
#
# Usage:
# 1. Exit your phpStorm client.
# 2. Open a Terminal and enter the following commands:
#    $ cd ~/git/enterprise-server/master      (or any other branch)
#    $ sh Enterprise/Build/phpstorm_code_inspect.sh
#
# Results are filtered by usage of the Enterprise/Build/phpstorm2junit.xml config file.
# Reports are written in the Enterprise/reports folder (which is cleared before it starts).
# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

# Runs the phpStorm Code Inspector within the current Enterprise Server project folder.
function phpStormCodeInspection {
 	echo "Clearing folder: ${WORKSPACE}/reports"
 	rm -rf "${WORKSPACE}/reports"
 	mkdir "${WORKSPACE}/reports"
 	
	echo "Temporary move 3rd party libraries aside (to exclude them from phpStorm code inspection)."
 	mkdir -p "${WORKSPACE}/code_analyser"
	mv "${WORKSPACE}/Enterprise/Enterprise/server/dgrid" "${WORKSPACE}/code_analyser"
	mv "${WORKSPACE}/Enterprise/Enterprise/server/javachart" "${WORKSPACE}/code_analyser"
	mv "${WORKSPACE}/Enterprise/Enterprise/server/jquery" "${WORKSPACE}/code_analyser"
	mv "${WORKSPACE}/Enterprise/Enterprise/server/vendor" "${WORKSPACE}/code_analyser"
	mv "${WORKSPACE}/Enterprise/Enterprise/server/ZendFramework" "${WORKSPACE}/code_analyser"
	
	echo "Running phpStorm Code Inspector..."
	# inspect.sh params: <project file path> <inspection profile path> <output path> -d <directory to be inspected>
	# see more info: http://www.jetbrains.com/phpstorm/webhelp/running-inspections-offline.html
	sh /Applications/PhpStorm.app/Contents/bin/inspect.sh "${WORKSPACE}/Enterprise" "${WORKSPACE}/Enterprise/.idea/inspectionProfiles/EnterpriseCodeInspection.xml" "${WORKSPACE}/reports" -d "${WORKSPACE}/Enterprise/Enterprise"

	echo "Move back the 3rd party libraries (that were temporary moved aside) to their original location."
	mv "${WORKSPACE}/code_analyser/dgrid" "${WORKSPACE}/Enterprise/Enterprise/server"
	mv "${WORKSPACE}/code_analyser/javachart" "${WORKSPACE}/Enterprise/Enterprise/server"
	mv "${WORKSPACE}/code_analyser/jquery" "${WORKSPACE}/Enterprise/Enterprise/server"
	mv "${WORKSPACE}/code_analyser/vendor" "${WORKSPACE}/Enterprise/Enterprise/server"
	mv "${WORKSPACE}/code_analyser/ZendFramework" "${WORKSPACE}/Enterprise/Enterprise/server"

	echo "Filtering reports generated by phpStorm Code Inspector..."
	# phpstorm2junit params: <folder path with code inspector output> <output path for jUnit>
	php "${WORKSPACE}/Enterprise/Build/phpstorm2junit.php" "${WORKSPACE}/reports" "${WORKSPACE}/reports/TEST-PhpStormCodeInspection.xml"
	echo "Results are written into: ${WORKSPACE}/reports/TEST-PhpStormCodeInspection.xml"
	date
}

# Determine the root for the current branch folder (which is two levels up compared to the script's folder).
WORKSPACE=$(cd "$(dirname "$0")"; cd ../..; pwd)
# Let phpStorm inspect the sourcecode in current branch folder of Enterprise Server.
phpStormCodeInspection