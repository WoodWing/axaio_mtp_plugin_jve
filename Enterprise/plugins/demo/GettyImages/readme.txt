Getty Images Content Source Server plug-in for Enterprise 8

1. Introduction
This document describes how we can apply the Getty Image Server plug-in in Enterprise. This
includes the following topics: overview, installation, configuration, troubleshooting, limitations.
The current search functionality that is developed allows searching (with facets) the Getty Image feed.
Getty Image searches can be performed through API as well as in InCopy, InDesign and Content Station.

2. Functional Overview
After installing the plug-in, a new search option named "Getty Images" is available.
It contains the faceted search feature where the search result list can be further filtered by choosing 
one or more facets (a facet is a group of images with one common attribute).
Searches are based on the entered search phrase.

Initial search
==============
You can perform a Getty Images search by specifying a phrase to search for and clicking the button to start searching.

The search will be passed to the Getty Image API which will return the list of results.

Redefine Search with facets
===========================
The user can further filter the results by using the facets functionality.
Next, the user can select a photo and import it into the WoodWing Enterprise system.

Importing Getty Images to Enterprise
====================================
When a user imports the image from the search result list, initially a free low res comp resolution image from Getty Images will be imported into Enterprise.
When the user eventually decides to use the Getty Image, a high-res version of the image can be downloaded from the Getty Images website.
Alternatively, the user can choose to import the high-res image directly from the search result list.

Downloading high-res Getty Images
================================
To download the high-res Getty image from the search result list, right-click the selected image and from the context menu choose "Download Getty Image".
The Getty Images site will get open in the browser. You can continue with the purchase process and download the high-res image.
Next, you need to upload the downloaded high-res image back to Enterprise to replace the low-res image.

3. Installation
By default, the plug-in will be installed in the Enterprise/config/plug-ins folder.
This is done by unzipping the plug-in and placing the files into this folder.
Next, the plug-in needs to be enabled in Enterprise:
Step 1. Log-in to Enterprise server.
Step 2. In the menu bar, click Server Plug-ins.
Step 3. Check that the "Getty Image Content Source" plug-in is listed.
Step 4. Click the plug-in icon to enable it.

4. Configuration
Open the config.php of the plug-in folder.

Define a meaningful named query that will appear as an option in the Search drop-down list on the client side (e.g. Content Station).
define ('GETTYSEARCH_NAMEDQUERY', 'Getty Images');
By default, it is named "Getty Images" but you can change the name by modifying the defined value.


Define the Getty Image API credential.
The API credentials are use to connect to Getty Image.
define ('GETTYIMAGE_USER_NAME', '' );	// User name
define ('GETTYIMAGE_USER_PWD', '' );	// User Password

Define the refinements that will exclude in the facets list
define ('EXCLUDE_REFINEMENTS' , serialize( array( ) ) );

Define the ObjectContextMenuActions that allow user to download the high-res image from Getty Image in Content Station.
Open the WWSettings.xml, add the following ObjectContextMenuActions,
<ObjectContextMenuActions>
	<ObjectContextMenuAction label="Download Getty" url="{SERVER_URL}config/plugins/GettyImages/high-resGettyImage.php?ticket={SESSION_ID}" objtypes="Image" external="true"/>
</ObjectContextMenuActions>

Troubleshooting

Known limitations
(i) Download Limits
Most product offerings have enforced periodic download limits such as monthly,
weekly, and daily. When this operation executes, the count of allowed downloads is
decremented by one for the product offering identified by the DownloadToken. Once
the download limit is reached for a given product offering, no further downloads
may be requested for that product offering until the next download period.
The download limit for a given download period is covered in your product
agreement established with Getty Images.

(ii) Product Offerings
Authorization to download images is controlled by the product offerings associated
with a customer. Product offerings control two aspects of download: whether the
customer can download the image, and what sizes of the image can be
downloaded. Each image authorization returned by this operation indicates the
product offering that is authorizing the download of the image, as well as the
authorized download size. Multiple different product offering authorizations may be
returned for the same image depending on the product offerings associated with
the customer. In these cases, the client may give the customer the option of which
product offering to authorize against.

(iii) Image Sizes
The user is free to import the low-res comp image from Getty Images to Enterprise.
Sizes available for downloading the high-res image are governed by your product agreement.

(iv) Multiple download
Downloading multiple high-res images from Getty Image is not supported.
The user can only open one image from the Getty Image website, proceed with the purchase and download.

Integration details.
We are applying ZendFramework to perform API search from Getty Image.
