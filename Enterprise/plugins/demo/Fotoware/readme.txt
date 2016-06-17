Fotoware Plugin

This plugin connects to the remote Content Source of Fotoware. 

The Content Source Concept

When quering the external content source the files are not imported into Enterprise. As soon as a 
file is going to be used (placed, put in a dossier, opened for editing) it will be imported into 
Enterprise. That's how this implementation has been done, it's also possible to keep the actual files
outside of Enterprise, instead just an object record is created for the workflow/metadata, but the 
actual files would stull reside in the external system. It would also be possible to have just thumbs
and/or previews in Enterprise and the high-res in the external system


Configuring Fotoware Plugin

No configuration needs to be done. Copy the plugin into the plugins folder and enable the plugin.


Using the Fotoware Plugin

Open Content Station or Smart Connection for ID/IC. Select the query named 'Fotoware', the
first combo allows to select a sub-folder.


DISCLAIMER

WoodWing provides this integration as a reference implementation how integration to these kind of 
systems can be implemented. This integration is provided ‘as is’ without support from WoodWing. 
The current status of this integration is not ready for production, it would require further 
development and proper QA. 

Usage is at your own risk.

Copyright 2008-2010 WoodWing Software BV. Licensed under the Apache License, Version 2.0