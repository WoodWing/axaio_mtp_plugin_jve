YouTube Publish Server Plugin for Enterprise v6.1 and v7

1. Introduction
This document describes how we can apply the YouTube publish server plugin in Enterprise, this include topics of overview, installation, configuration, troubleshooting, limitations.
The current publish functionality being developed was publish video from Enterprise to YouTube.
You will able to publish, update and unpublish video in a dossier.

2. Functional Overview
After installing the plugin, you will able to publish videos in a dossiers to YouTube,
update videos in a dossier in YouTube, and unpublish videos from YouTube.

3. Installation
By default, the plug-in will need to be installed in the Enterprise/config/plug-ins folder. 
Do this by unzipping the files into this folder.
Next, the plug-in needs to be enabled in Enterprise:
Step 1.Log-in to Enterprise server.
Step 2.In the menu bar, click Server Plug-ins.
Step 3.Check that the "Publish to YouTube" plug-in is listed.
Step 4.Click the plug in icon to enable it.

4. Configuration
Create a new YouTube Publication Channel, this publication channel will be use to publish videos to YouTube from Content Station.

Create list of issue for the YouTube publication channel, the issue name will be map as a category in YouTube.
This field is mandatory, else the upload API wil fail, if category not match or not found.
Below are the list of category in YouTube:
Activism
Animals
Animation
Autos
Blogs
Comedy
Education
Entertainment
Events
Film
Gaming
Howto 
Music
News
Nonprofits
People
Pets
Politics
Science
Sports
Style
Technology
Travel
Vehicles

YouTube might change the category list from time to time, you can always check the latest category from YouTube.

There are some mandatory configuration setting must be done in this plugin, for YouTube, in the config.php.
Basic Configuration,
Define the temporary directory, this directory will use as temporary storage for publish video to YouTube.
define ('YOUTUBE_DIRECTORY', ATTACHMENTDIRECTORY . '/_YOUTUBE_');
By default, it is named as "_YOUTUBE_".

Google and YouTube user Configuration,
Step 1. Create a new Google Gmail account.
Step 2. Create a new YouTube account and linked with the Google account created at Step 1.
Step 3. Login to Gmail account, you will receive email from YouTube asking to confirm your email address, click confirm.

Step 4. Configure user account information in config.php.
Define YouTube user name,
define ('YOUTUBEPUBLISH_USERNAME',	'username' );

Define YouTube user password,
define ('YOUTUBEPUBLISH_USERPWD',	'password' );

Configure Google Client ID and Developer key.
A developer key identifies the YouTube developer that is submitting an API request. A client ID identifies your application for logging and debugging purposes. Please visit http://code.google.com/apis/youtube/dashboard/ to obtain a developer key and client ID.

Define your own Client ID and developer key, after you have created.
define ('YOUTUBEPUBLISH_CLIENT_ID',	'' );
define ('YOUTUBEPUBLISH_DEV_KEY', 	'' );


Problem Solving
Q1 ) Not able to publish videos to YouTube, when publish a dossier.
Answer: 
i) This might due to publication channel issue name didn't match with the category in YouTube(please check the latest category in YouTube), or,
ii)This might due to invalid configuration, please refer to the topic configuration.
   When the configuration is set correctly, it might be the user account haven't confirm the email address to YouTube, please check email and confirm.

Q2 ) Not able to replace the videos in YouTube, when update a dossier.
Answer: This might due to the video no longer exist, or the video didn't have update version to update.

Q3 ) Not able to unpublish videos from YouTube, when unpublish a dossier.
Answer: This might due to the video has been removed from YouTube.

Known limitations
1) It might take sometimes to upload a big size video, depends on the network speed.

Integration details.
We are applying ZendFramework YouTube Data API in the form of Google Data API feeds to perform API to retrieve and update YouTube content.