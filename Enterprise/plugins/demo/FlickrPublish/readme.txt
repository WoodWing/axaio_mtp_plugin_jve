Flickr Publish Server Plugin for Enterprise 6.1.x and 7

1. Introduction

This document describes how we can apply the Flickr publish server plugin in Enterprise, this include topics of overview, installation, configuration, troubleshooting, limitations.
The current publish functionality being developed was publish image from Enterprise to Flickr.
You will able to publish and unpublish image in a dossier. Update is implemented partially.


2. Functional Overview

After installing the plugin, you will able to publish images in a dossiers to Flickr,
update images in a dossier in Flickr, and unpublish images from Flickr.


3. Installation

The plug-in will need to be installed in the Enterprise/config/plug-ins folder and subsequently activated via the Server plugin page.


4. Configuration

Create a new Publication Channel in Enterprise, set publish system to Flickr.

Some definitions needs to be done before we able to publish image to Flickr.

Step 1. Create a Flickr account.
(Note: when it is a free account created, you aren't allow to perform update dossier, it is best recommend to upgrade to Pro account type, when you need the update image in Flickr).
Step 2. In your account, under 'Personal Information', set the alias for you account. Use your account name as extention (ie. www.flickr.com/photos/woodwingdemo)
Step 3. Go to Flickr Services page, http://www.flickr.com/services/.
Step 4. Apply a new API key, follow the instruction to create a new key.
Step 5. New API key and secret generated.
Step 6. Open the configuration file, FlickrPublish/config.php, fill in your Flickr account name and the key and secret which have been obtained in Step 4.
Step 7. Open 'Edit key details' on the API key page and configure Application Title, Description and Logo.
Step 8. Go to web-page, http://*yourserver*/Enterprise/config/plugins/FlickrPublish/getToken.php, follow the instruction in the page, to acquire a read access token. [Note: Access token step must be done, in order to update the image]


5. Usage Notes:

- The Enterprise image name is used as the title for the image in Flickr.
- The Enterprise image description and keywords are also set in Flickr, these may be empty.
- Update dossier does not support adding/removing images. Work-around: unpublish first.


Problem Solving

Q1 ) Not able to publish images to Flickr, when publish a dossier.
Answer: 
i)  This might due to there is no publication channel being setup with Flickr as its publishing system
ii) This might due to invalid token, or network problem.

Q1 ) Not able to replace the images in Flickr, when update a dossier.
Answer: This might due to, no write access token being acquire.
        Please following Step 6 in topic Configuration, to acquire a write access token.

Q2 ) Not able to unpublish images from Flcikr, when unpublish a dossier.
Answer: This might due to, no write access token being acquire.
        Please following Step 6 in topic Configuration, to acquire a write access token.


Known limitations

1) You are not able to perform update or replace a image in Flickr, if the user are "Free" Flickr user account.
2) Update dossier does not support adding/removing images. Work-around: unpublish first.
3) No support to deal with collections and/or sets