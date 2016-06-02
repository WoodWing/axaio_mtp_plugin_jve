#!/bin/sh
# @package      Enterprise
# @subpackage   Composer
# @since        9.2
# @copyright    WoodWing Software bv. All Rights Reserved.
#
# This bash shell script does the following:
# 1. Copies composer.lock and composer.json to Enterprise/server and update composer.lock permissions
# 2. Run the Composer from the Enterprise/server folder with the --no-dev option and working
#    directory set to the Enterprise server folder. The vendor folder must be checked out.
# 3. Copy lock file back if changed (requires the file to be checked out)
# 4. Remove composer.json and composer.lock from Enterprise/server
#
# This script creates a folder "vendor" containing the requested library and dependencies.
# The required classes are auto loaded through the included script in config_server.php (server/vendor/autoload.php).
#
# To update composer.phar:
# 1. Checkout composer.phar
# 2. composer.phar selfupdate
# 3. Check-in composer.phar
#
# For more information see https://getcomposer.org/
#

ENT_DIR=../../..

# Perforce: make sure vendor- and composer folders in workspace is in sync with latest version:
p4 sync "${ENT_DIR}/server/vendor/...#head"
p4 sync "${ENT_DIR}/server/buildtools/composer/...#head"

# Perforce: checkout the entire vendor- and composer folders:
p4 edit "${ENT_DIR}/server/vendor/..."
p4 edit "${ENT_DIR}/server/buildtools/composer/..."

# Auto update to latest composer
php composer.phar --version | grep -q self-update
if [ $? -eq 0 ]; then
	php composer.phar self-update
fi

# Copy composer.lock and composer.json to Enterprise/server
if [[ -f "$ENT_DIR/server/composer.json" ]]; then
    echo '"$ENT_DIR/server/composer.json" still exists.'
    exit
fi
if [[ -f "$ENT_DIR/server/composer.lock" ]]; then
    echo '"$ENT_DIR/server/composer.lock" still exists.'
    exit
fi

cp composer.json "$ENT_DIR/server/composer.json"
cp composer.lock "$ENT_DIR/server/composer.lock"

# Update composer.lock permissions
chmod 755 "$ENT_DIR/server/composer.lock"

# Update libraries and dependencies
php composer.phar update --no-dev --working-dir "$ENT_DIR/server"

# Cleanup
rm -f "$ENT_DIR/server/composer.json"

# Update composer.lock if needed
if diff "$ENT_DIR/server/composer.lock" composer.lock >/dev/null ; then
    # Lock file did not update, just remove original
    rm -f "$ENT_DIR/server/composer.lock"
else
    # Lock file was updated
    echo 'Updating composer.lock file (changed)'
    mv "$ENT_DIR/server/composer.lock" composer.lock
    if [ $? -ne 0 ] ; then
        echo 'Failed to update composer.lock file (make sure it is checked out).'
        exit
    fi
fi

# Perforce: revert unchanged files in vendor- and composer folders:
p4 revert -a "${ENT_DIR}/server/vendor/..."
p4 revert -a "${ENT_DIR}/server/buildtools/composer/..."

# Perforce: add newly created files (if any) to vendor folder:
find "${ENT_DIR}/server/vendor" -type f -not -path '*/\.*' -print | p4 -x - add
# L> "p4 add" command does not allow us to add files recursively (unline the GUI client)
# L> note that the "find <folder> -type f -print" command lists all files recursively
# L> note that the "\( ! -iname ".*" \)" expression excludes the hidden files (starting with dot)

############################################## !!! CAUTION !!! ##################################################
#
# NOTE: We still have to test this from within this script. Just added this as reference.
#
# Use this only in case you know what you are doing! This is an instructional comment, don't run this 'as is'.
#
# To make the changes in a local folder 'leading' against Perforce, it's possible to 'reconcile' the local folder.
# We can do this 'possibly' as follows (${ID_OF_CHANGELIST} = the last changelist created by the reconciliation):
#
# p4 reconcile -n -f "${ENT_DIR}/server/vendor/..."
# p4 change -i
# ID_OF_CHANGELIST=`p4 counter change`
# if [[ -f "${ENT_DIR}/server/vendor/composer/autoload_namespaces.php" ]]; then
#   p4 reconcile -f -c ${ID_OF_CHANGELIST} "${ENT_DIR}/server/vendor/composer/autoload_namespaces.php"
# fi
#
# We can do this 'possibly', because we have not tested this yet.
#
# Check in the Perforce client if the changes are as desired to submit. Then submit the changelist.
#
############################################## !!! CAUTION !!! ##################################################

echo ------------------------------------------------
echo "All done!"
echo "At Perforce, refresh your default Pending Changelist and review re-generated files before submit."
