SimpleFileSystem Plugin

This plugin serves as a sample implementation for a custom Content Source. A local filesystem is 
used as the content source, made available as a Named Query. Instead of a local filesystem any other
system can be exposed this way.


The Content Source Concept

When quering the external content source the files are not imported into Enterprise. As soon as a 
file is going to be used (placed, put in a dossier, opened for editing) it will be imported into 
Enterprise. That's how this implementation has been done, it's also possible to keep the actual files
outside of Enterprise, instead just an object record is created for the workflow/metadata, but the 
actual files would stull reside in the external system. It would also be possible to have just thumbs
and/or previews in Enterprise and the high-res in the external system


Configuring Simple FileSystem Plugin

1. Open config.php and set SFS_LOCALCONTENTFOLDER to a folder. This folder can contain 
sub-folders where you can place the images in these sub-folders. For example: 
SFS_LOCALCONTENTFOLDER = '/MyImages/';
Inside /MyImages/, can contiain subfolders:
/MyImages/imageFolder1/
/MyImages/imageFolder2/
...
These sub-folders doesn't need to be filled in the config file (Just the parent folder will do).

2. Create sub-folders _preview and _thumb that are writable by PHP. These will be used for caching.
The config.php setting SFS_PREVIEW_CACHE can be set to false to disable caching.

3. In config.php set SFS_BRAND, SFS_CATEGORY, SFS_STATUS to names of your system. These are the values
use when a file is imported into the system.

4. Enabled the server plugin


Using the Simple FileSystem Plugin

Open Content Station or Smart Connection for ID/IC. Select the query named 'Image Library', the
first combo allows to select a sub-folder, the editbox can contain a filter for the filename.
Currently the plugin only supports extension with '.jpg' files.


DISCLAIMER

WoodWing provides this integration as a reference implementation how integration to these kind of 
systems can be implemented. This integration is provided ‘as is’ without support from WoodWing. 
The current status of this integration is not ready for production, it would require further 
development and proper QA. 

Usage is at your own risk.

Copyright 2008-2009 WoodWing Software BV. Licensed under the Apache License, Version 2.0