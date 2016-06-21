New York Times plugin for Enterprise v6.1.x and v7

The NY Times plugin implements a Content Source plugin using the NY Times API.

Two queries are added:
* NY Times Archive - allows to search in all articles published since 1981
* Newswire - shows the latest stories published

Each of these use a separate API.

The NY Times content is represented as hyperlink as the API doesn't support to get the content itself.

In order to use this plugin you need to obtain 2 API keys (for the Article Search API and for the Newswire API) 
from http://developer.nytimes.com/


Configuring the Plugin

1. Open config.php and set NYT_API_ARTICLESEARCH_KEY and NYT_API_NEWSWIRE_KEY to your API keys

2. In config.php set NYT_BRAND, NYT_CATEGORY, NYT_STATUS to names of your system. These are the values
use when an hyperlink is imported into the system.

4. Enabled the server plugin


Using the NYTimes Plugin

Open Content Station and use one of the NY Times searches

Note: Within Smart Connection this plugin isn't really helpful as it doesn't show hyperlink previews.


DISCLAIMER

WoodWing provides this integration as a reference implementation how integration to these kind of 
systems can be implemented. This integration is provided AS IS without support from WoodWing. 
The current status of this integration is not ready for production, it would require further 
development and proper QA.

Usage is at your own risk.

Copyright 2008-2009 WoodWing Software BV. Licensed under the Apache License, Version 2.0