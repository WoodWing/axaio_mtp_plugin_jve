Flickr Bulk Import Server Plugin for Enterprise v6.1.x and v7.

Below are brief documentation about Flickr Bulk Import script.
Note: This module is still not yet complete, but it is demoable.
The area which are not complete as:
i)   Not to import, when there is a new version exist in Enterprise.
ii)  Not to import but update, when there is a new image version in Flickr that needs to be update in Enterprise as well.

1. Introduction
This document describes how we can apply the Flickr Bulk Import script, to automatically importimages from Flickr.com, in some time interval basis.
It will based on the uploaded time, so when there is a new uploaded photos in Flickr, it will avaialble in Enterprise, after the import script run.
You can use Flickr as your image storage, and import to Enterprise for production usage.

2. Functional Overview
Photos uploaded in Flickr, will also get uploaded to the Enterprise system once the import script run.

3. Installation
By default, the script is come with the FlickrSearch and FlcikrPublish server plugin.
There are 5 PHP script files in total, as listed below:
i)   configImport.php
ii)  FlickrBulkImport.php
iii) FlickrImport.class.php
iv)  GetImportToken.php
v)   win_curl.php

We need to create a scehedule task in windows environment, and cron job in Mac environment.
For windows,
Create a schedule task, and put the command as below:
C:\PHP\php.exe "C:\Inetpub\wwwroot\Enterprise\config\plugins\FlickrImport\win_curl.php" -url "http://localhost/enterprise/config/plugins/FlickrImport/FlickrBulkImport.php"

For Mac,
Create a cron job, and put the command as below:
curl http://localhost/enterprise/config/plugins/FlickrImport/FlickrBulkImport.php

The time interval is depends on the internet speed, you can set to run it every 30 minutes, to import 50 images or more.

4. Configuration
There are some mandatory configuration setting must be done in this plugin, for Flickr and WoodWing Enterprise, in the configImport.php.

Define the Enterprise user name,
define ('FLICKR_WW_USERNAME',	'woodwing');

Define the Enterprise user password,
define ('FLICKR_WW_USERPWD',	'ww');

Define the Brand where import photos belongs to,
define ('FLICKR_WW_BRAND',		'WW News');

Define the Category where import photos belongs to,
define ('FLICKR_WW_CATEGORY',	'News');

Define the Status where import photos belongs to,
define ('FLICKR_WW_STATUS',		'Images');

Define the SourceID to indicate the photos is from Flickr,
define ('FLICKR_SOURCEID', 		'FS');
Define the minimum upload date, this is use on first time import, to import all the images which upload date is greater than this value,
define ('FLICKR_UPLOAD_DATE',	'2009-04-04');

Define maximum number of photo to be import, the maximum value is 500,
define ('FLICKR_MAX_IMPORT', 	'2');

Define the Flickr User Account Name, the import will get the latest photos from this Flickr account,
define ('FLICKRIMPORT_USR_ACC', 	'woodwing software' );

define ('FLICKRIMPORT_API_KEY', 	'910efd7a231bf1398348b44a7e5ad39f' );	// API Key
define ('FLICKRIMPORT_API_SECRET',	'3b06ea3cfc8f8599' );					// API Secret
define ('FLICKRIMPORT_TOKEN',		'72157616985886721-f286f0ba173c3e17' ); // Token

Flickr Configuration,
Few definition needs to be done before we able to import image from Flickr.

Step 1. Create a Flickr account
(Note: when it is a free account created, you aren't allow to perform update dossier, it is best recommend to upgrade to Pro account type, when you need the update image in Flickr).
Step 2. Go to Flickr Services page, http://www.flickr.com/services/.
Step 3. Apply a new API key, follow the instruction to create a new key.
Step 4. New API key and secret generated.
Step 5. Open the configuration file, config.php, define FLICKRIMPORT_API_KEY and FLICKRIMPORT_API_SECRET with the key and secret which obtained in Step 4.
Step 6. Go to page, /getImportToken.php, follow the instruction in the page, to acquire a read access token.
[Note: Access token step must be done, in order to get the private photo]


Problem Solving
Q1 ) Not able to import photos from Flickr
Answer: You can check on the logfile, this might due to,
        (i)   invalid Enterprise user account or password,
        (ii)  invalid Enterprise Brand, Categopry, Status, token. 
        (iii) invalid Flickr User Account,
        (iv)  invalid token, please following Step 6 in topic Configuration, to acquire a read access token.

Q2 ) Not able to import total number of photos from Flickr, as defined in maximum photos to be import,'FLICKR_MAX_IMPORT'.
Answer: This might due to, 
	(i)  the internet speed was not fast enough.
        (ii) please check on the php.ini, the configuration for the variable, max_execution_time, please set it to sufficient value.

Q3 ) Not able to import the original photo files from Flickr.
Answer: Thisis due to, the user Flickr Account is a "Free" account type, which not allow to download original files.

Known limitations
1) You are not able to import image original files from Flickr, if the user are "Free" Flickr user account.
2) The maximum photos can be import in one time is 500, you can set the script to run in more frequent.

Integration details.
We are applying ZendFramework, Zend_Rest_Client services to perform API from Flickr.com.
