#!/bin/bash

#
# This script creates symbolics at config/plugins folder for ALL(!) plug-ins found under
# the script's folder, such as all under the demo and samples folders (but skips
# the external plug-ins.
#

# get the full folder path where this bash script resides (=source)
SCRIPTPATH=$( cd $(dirname $0) ; pwd -P )

# go to plugins folder where to create the symbolic links (=target)
cd ../Enterprise/config/plugins

# iterate through all subfolders, which are the plug-in categories: demo, example, etc
for pluginCategoryFolder in $SCRIPTPATH/* ; do

	# only deal with subfolders (under script's folder)
	if [ -d $pluginCategoryFolder ] ; then

		# get folder base (name without path)
		pluginCategory=${pluginCategoryFolder##*/}
		
		# skip the "external" plug-ins folder
		if [ $pluginCategory == 'external' ] ; then
			continue;
		fi

		for pluginFolder in $pluginCategoryFolder/* ; do
			if [ -d $pluginFolder ] ; then

				# get folder base (name without path)
				pluginName=${pluginFolder##*/}

				# skip the "QuickPlugin" plug-in
				if [ $pluginName == 'QuickPlugin' ] ; then
					continue;
				fi
				
				# only create symbolic link when not already present
				if [ ! -d ./$pluginName ] ; then
				
					# create the symbolic link
					ln -s $pluginFolder
					echo "Linked $pluginCategory plug-in: Enterprise/config/plugins/$pluginName"
				fi
			fi
		done
	fi
done