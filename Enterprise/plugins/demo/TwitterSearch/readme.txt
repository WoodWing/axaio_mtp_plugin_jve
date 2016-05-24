Twitter Search for Enterprise 6.1.x and 7
Twitter Search

The Twitter Search plugin implements a Content Source plugin using the Twitter search API.

Two queries are added:
* Twitter Search - search within Twitter allowing to filter for a specific language.
* Twitter Trends - shows the current top 10 trends within Twitter.

The Tweets are represented as hyperlinks.


Configuring the Plugin is optional:

1. Open config.php and optionally configure the languages you want to filter on

2. In config.php set TWS_BRAND, TWS_CATEGORY, TWS_STATUS to names of your system. These are the values
used when an hyperlink is imported into the system.

4. Enabled the server plugin


Using the Twitter Search plugin

Open Content Station and use one of the Twitter searches

Note: Within Smart Connection this plugin isn't really helpful as it doesn't show hyperlink previews.


DISCLAIMER

WoodWing provides this integration as a reference implementation how integration to these kind of 
systems can be implemented. This integration is provided AS IS without support from WoodWing. 
The current status of this integration is not ready for production, it would require further 
development and proper QA.

Usage is at your own risk.

Copyright 2008-2009 WoodWing Software BV. Licensed under the Apache License, Version 2.0