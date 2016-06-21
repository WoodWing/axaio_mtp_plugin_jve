Elvis Search Server Plugin for Enterprise 7

1. Introduction
This document describes how we can apply the Elvis Search server plugin in Enterprise, this 
include topics of overview, installation, configuration, troubleshooting, limitations.
You can expect to perform Elvis search through API, in the InCopy, InDesign and Content Station.

2. Functional Overview
After installing the plugin, there will have a new search, by default it is named as "Elvis 
Search".
The search will contains basically 2 fields, search phrase and search button.
You can perform Elvis search by specifying the phrase to search and click button to start searching.

When search result list are returned, you can select the photo and import it into WoodWing 
Enterprise System.

3. Installation
By default, the plug-in will need to be installed in the Enterprise/config/plug-ins folder. 
Do this by unzipping the files into this folder.
Next, the plug-in needs to be enabled in Enterprise:
Step 1.Log-in to Enterprise server.
Step 2.In the menu bar, click Server Plug-ins.
Step 3.Check that the "Elvis Content Source" plug-in is listed.
Step 4.Click the plug in icon to enable it.

4. Configuration
There are some mandatory configuration setting must be done in this plugin, for Elvis and WoodWing 
Enterprise, in the config.php.
For Enterprise,
Define a meaningful name query that will appear in Enteprise.
define ('ELVISSEARCH_NAMEDQUERY', 'Elvis Search');
By default, it is named as, "Elvis Search", you can change the name by setting the define value.


For Elvis,
Few configuration needs to be done before we able to search from Flickr.

Define the URL to the WSDL on the Elvis server
define ('ELVIS_URL', 'http://elvis.dutchsoftware.com/elvis/webservice/elvis.wsdl');

By default it's set to http://elvis.dutchsoftware.com/elvis/webservice/elvis.wsdl. This is
a demo server. You can sign up for a free demo account on http://www.elviscloud.com/

Define username and password.
define ('ELVIS_USERNAME', '');
define ('ELVIS_PASSWORD', '');

You can enter the username and password you've received when you signed up for the free demo account.

Define the number of search return result rows.
define ('ELVISSEARCH_ITEMS_PER_PAGE', 50);
By default, it has been set to 50 rows return result.
