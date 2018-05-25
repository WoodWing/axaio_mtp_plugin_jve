#!/bin/sh
# @since        9.6
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# This bash shell script does the following:
# - Check installed executables used by this script.
# - Perforce: Check out dgrid components for editing.
# - Update dgrid components locally.
# - Perforce: Revert unchanged dgrid files and submit updated files.
#
# This script is designed to run on a MacOSX development machine.
# Please run only when it is needed to upgrade the dgrid module.

ENT_DIR=../../..

# NPM is required to install bower
npmbin=`which npm`
if [ ! -f "${npmbin}" ]; then
	echo '[ERROR] npm executable not found. Please download Node.js from https://nodejs.org/ and install it.'
	exit 1
fi
echo "[OK] npm executable found at: ${npmbin}"

# Node.js is required to determine the dojo version
nodebin=`which node`
if [ ! -f "${nodebin}" ]; then
	echo '[ERROR] node executable not found. Please download Node.js from https://nodejs.org/ and install it.'
	exit 1
fi
echo "[OK] node executable found at: ${nodebin}"

# Bower is required to install for dgrid
bowerbin=`which bower`
if [ ! -f "${bowerbin}" ]; then
	sudo npm install -g bower
fi
if [ ! -f "${bowerbin}" ]; then
	echo '[ERROR] Failed to install bower.'
	exit 1
fi 
echo "[OK] bower executable found at: ${bowerbin}"

# Install/update dgrid (which also installs dojo through its dependencies).
bower install dgrid
echo "[OK] Updated dgrid"

# Determine the (just) installed dojo version.
dojover=`node -pe 'JSON.parse(process.argv[1]).version' "$(cat ../../../dgrid/dojo/package.json)"`
if [ ! -n "${dojover}" ]; then
	echo '[ERROR] Could not determine installed dojo version.'
	exit 1
fi
echo "[OK] Found dojo version: ${dojover}"

# Install/update additional packages for dgrid.
bower install dojox#${dojover}
bower install dijit#${dojover}
echo "[OK] Updated dgrid packages at: ${ENT_DIR}/Libraries/dgrid"

# Copy dgrid library from the full version folder to the shipping folder.
# Note that we need a few components from dojox only, and we don't need test folders.
cp -R "${ENT_DIR}/Libraries/dgrid" "${ENT_DIR}/Enterprise/server"
rm -Rfd "${ENT_DIR}/Enterprise/server/dgrid/dojox"
mkdir "${ENT_DIR}/Enterprise/server/dgrid/dojox"
cp -R "${ENT_DIR}/Libraries/dgrid/dojox/grid" "${ENT_DIR}/Enterprise/server/dgrid/dojox/grid"
cp -R "${ENT_DIR}/Libraries/dgrid/dojox/html" "${ENT_DIR}/Enterprise/server/dgrid/dojox/html"
cp "${ENT_DIR}/Libraries/dgrid/dojox/main.js" "${ENT_DIR}/Enterprise/server/dgrid/dojox"
rm -Rfd "${ENT_DIR}/Enterprise/server/dgrid/dojox/grid/tests"
rm -Rfd "${ENT_DIR}/Enterprise/server/dgrid/dojox/html/tests"
rm -Rfd "${ENT_DIR}/Enterprise/server/dgrid/dijit/tests"
rm -Rfd "${ENT_DIR}/Enterprise/server/dgrid/dojo/tests"
rm -Rfd "${ENT_DIR}/Enterprise/server/dgrid/dstore/tests"
rm -Rfd "${ENT_DIR}/Enterprise/server/dgrid/put-selector/test"
rm -Rfd "${ENT_DIR}/Enterprise/server/dgrid/xstyle/test"
echo "[OK] Copied dgrid packages to: ${ENT_DIR}/Enterprise/server/dgrid"

echo "[OK] Update completed. Please commit and push unstaged files in the Git repo."
