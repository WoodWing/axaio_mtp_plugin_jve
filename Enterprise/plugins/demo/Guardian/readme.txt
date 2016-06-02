Guardian server plugin for Enterprise v6.1.x and v7

The Guardian plugin implements a Content Source plugin using the Guardian Open Platform.

In order to use this plugin you need to obtain an API key http://guardian.mashery.com/
For more information about the Guardian Open Platform, see http://www.guardian.co.uk/open-platform


Configuring the Plugin

1. Open the plugin's config.php and set GNL_API_KEY to your API key

2. In the plugin's config.php set GNL_BRAND, GNL_CATEGORY, GNL_STATUS to names of your system.

4. Enable the server plugin


Using the Guardian Plugin

Open Content Station or Smart Connection and use the Guardian search


Technical Notes

The API only allows 1 request per second which means it's a bit limited. This implementation
packs some basic meta data into the alienID to prevent calling again for 'none' rendition getObject
calls. Could be better resolved by caching data.
	
Other possible improvements:
- Support other content types than just Article
- Get keywords from response
- Implement date parameters: after, before


DISCLAIMER

WoodWing provides this integration as a reference implementation how integration to these kind of 
systems can be implemented. This integration is provided AS IS without support from WoodWing. 
The current status of this integration is not ready for production, it would require further 
development and proper QA.

Usage is at your own risk.

Copyright 2008-2009 WoodWing Software BV. Licensed under the Apache License, Version 2.0