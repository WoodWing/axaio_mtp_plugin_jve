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

echo ------------------------------------------------
echo "All done!"
