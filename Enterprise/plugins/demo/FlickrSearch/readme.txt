Flickr Search Server Plugin for Enterprise 6.1.x and 7

1. Introduction
This document describes how we can apply the Flickr Search server plugin in Enterprise, this 
include topics of overview, installation, configuration, troubleshooting, limitations.
The current search functionality being developed was search Flickr by tag, and by user.
You can expect to perform Flickr search through API, in the InCopy, InDeisng and Content Station.

2. Functional Overview
After installing the plugin, there will have a new search, by default it is named as "Flickr 
Search".
The search will contains basically 3 fields, search type, search phrase and search button.
You can perform Flickr search by specifying,
(i)  Search Type [By Tag or By User],
(ii) Phrase to search.
and click button to start searching.

The searches will be search through all Flickr member, to return all the made public photos.

When search result list are returned, you can select the photo and import it into WoodWing 
Enterprise System.

3. Installation
By default, the plug-in will need to be installed in the Enterprise/config/plug-ins folder. 
Do this by unzipping the files into this folder.
Next, the plug-in needs to be enabled in Enterprise:
Step 1.Log-in to Enterprise server.
Step 2.In the menu bar, click Server Plug-ins.
Step 3.Check that the "Search from Flickr" plug-in is listed.
Step 4.Click the plug in icon to enable it.

4. Configuration
There are some mandatory configuration setting must be done in this plugin, for Flickr and WoodWing 
Enterprise, in the config.php.
For Enterprise,
Define a meaningful name query that will appear in Enteprise.
define ('FLICKRSEARCH_NAMEDQUERY', 'Flickr Search');
By default, it is named as, "Flickr Search", you can change the name by setting the define value.


Define the the name of search type, currently it support 2 types, which are search By Tag or By 
User.
define ('FLICKRSEARCH_SEARCH_BY_TAG',	'By Tag');
By default, it is name as "By Tag" for search type by tag.
define ('FLICKRSEARCH_SEARCH_BY_USER',	'By User');
By default, it is named as "By User" for search type by user.


For Flickr,
Few configuration needs to be done before we able to search from Flickr.
Define the number of search return result rows.
define ('FLICKRSEARCH_ITEMS_PER_PAGE', 100);
By default, it has been set to 100 rows return result.

Define the Flickr user Account.
define ('FLICKRSEARCH_USR_ACC', 	'woodwing software' );	// User Account Id
The value will be use as default value, when there is no user accunt info being input during search 
by user type.
This will allow Enterprise to perform search for the define user.

Step 1. Create a Flickr account
(Note: when it is a free account created, you aren't allow to download the original files, it is 
best recommend to upgrade to Pro account type, when you need the original files from Flickr).
Step 2. Go to Flickr Services page, http://www.flickr.com/services/.
Step 3. Apply a new API key, follow the instruction to create a new key.
Step 4. New API key and secret generated.
Step 5. Open the configuration file, config.php, define FLICKRSEARCH_API_KEY and 
FLICKRSEARCH_API_SECRET with the key and secret which obtained in Step 4.
Step 6. Define the value for FLICKRSEARCH_ITEMS_PER_PAGE, the maximum will be 500.
Step 7. Go to page, FlickrSearch/getToken.php, follow the instruction in the page, to acquire a 
read access token.
[Note: Access token step must be done, in order to search the user private photos]


Problem Solving
Q1 ) Search result didn't contains full records as in Flickr.com
Answer: This might due to, no read access token being acquire, and return results will only 
contains user public photos, and not private photos.
        Please following Step 7 in topic Configuration, to acquire a read access token.

Q2 ) Search by tag, the results return is not accurate.
Answer: This might due to, the upload and tagged photo haven't reviewed by the team of Flickr.com, 
who will approve and made it available to the public.

Known limitations
1) The search from flcikr.com has been set to limit 500 results per search.
2) Original photo file will not available if the photo belongs to "Free" Flickr user account, 
therefore only file size large will return.
[Note: Definition for Large, 1024 on longest side (only exists for very large original images)]

Integration details.
We are applying ZendFramework, Zend_Service_Flickr services to perform API search from Flickr.com.
