Elpical Claro Enterprise Server Plugin for Enterprise v6.1.x and v7

1. Introduction
This document describes how we can apply the Claro server plugin in Enterprise, this include topics of overview, installation, configuration, troubleshooting, limitations.
The current functionality being developed was send the image from Enterprise to Claro for enhancement. 
You will able to send the image from Enterprise to Claro for enhancement and imported back to Enterprise through background job.

2. Functional Overview
The general workflow for this server plugin is, when user saves a Layout in InDesign, 
all the images that placed in that layout will be inserted in database table with the placed crop information.
User will able to select which image to be send to Claro for optimization by setting the image properties and set the status.
Image will get processed by Claro, and a background task will imported the processed image back to Enterprise as a new updated version.

3. Installation
For this Claro  v6.0.1 needs to be installed.
You can create a channel in Claro, with configuration for the image processing.

For WoodWing Enterprise Server Plugin,
By default, the plug-in will need to be installed in the Enterprise/config/plug-ins folder. 
Do this by unzipping the files into this folder.
There are 3 main installation involve, which are server plugin, InDesign Client script, and bulkimport script.

For server Plugin, the plug-in needs to be installed in Enterprise:
Step 1.Log-in to Enterprise server.
Step 2.In the menu bar, click Server Plug-ins.
Step 3.Check that the "Claro" plug-in is listed.
Step 4.Click the plug in icon to installed it, a database table named smart_claro will get created after installation.

For InDesign client script,
CS3
===
Copy the following files below,
(i) afterOpenLayout.jsx
(ii) beforeSaveLayout.jsx
(iii) claro.inc.jsx
to the default application's script location.
The default location for scripts is:
Windows: C:\Documents and Settings\<username>\Application Data\Adobe\InDesign\Version 5.0\Scripts\Scripts Panel
Macintosh: /Applications/Adobe InDesign CS3/Scripts/Scripts Panel

CS4
===
Copy the following files below,
(i) afterOpenLayout_CS4.jsx
(ii) beforeSaveLayout_CS4.jsx
(iii) claro.inc.jsx
to the default application's script location.
The default location for scripts is:
Windows: C:\Documents and Settings\<username>\Application Data\Adobe\InDesign\Version 6.0\en_GB\Scripts\Scripts Panel
Macintosh: /Applications/Adobe InDesign CS4/Scripts/Scripts Panel

After file being copied, rename the script file,
(i) afterOpenLayout_CS4.jsx to afterOpenLayout.jsx
(ii) beforeSaveLayout_CS4.jsx to beforeSaveLayout.jsx

Add the following text to your WWSettings.xml to call the scripts automatically.

<SCEnt:ScriptingEvents>
        <SCEnt:ScriptsFolderPath>*scripts location*</SCEnt:ScriptsFolderPath>
        <SCEnt:Script event="beforeSaveLayout" debug="false">beforeSaveLayout.jsx</SCEnt:Script>
        <SCEnt:Script event="afterOpenLayout" debug="false">afterOpenLayout.jsx</SCEnt:Script>
</SCEnt:ScriptingEvents>

*scripts location* can be the default location of the scripts, but can also be a custom location where the scripts are located.

For BulkImport script,
We need to create a scehedule task in windows environment, and cron job in Mac environment.
For windows,
Create a schedule task, and put the command as below:
C:\PHP\php.exe "C:\Inetpub\wwwroot\Enterprise\config\plugins\Claro\win_curl.php" -url "http://localhost/enterprise/config/plugins/Claro/ClaroBulkImport.php"

For Mac,
Create a cron job, and put the command as below:
curl http://localhost/enterprise/config/plugins/Claro/ClaroBulkImport.php

You can set it to run in one minute time interval, to import images back to Enterprise.

4. Configuration
For WoodWing Enterprise,
There are some mandatory configuration setting to be set in the configuration file of the Claro plugin, config.php.

Define the WoodWing Enterprise user name and password:
define ('CLARO_WW_USERNAME', 	'woodwing');
define ('CLARO_WW_USERPWD', 	'ww');

Define the status, which indicate the different stage of image that process in Claro.
Status which indicate the image to be process in Claro Image.
Normal Image.
define ('CLARO_PRE_STATUS', 	'Image Editing');
Black & White image conversion.
define ('CLARO_PRE_BW_STATUS', 	'BW Image Editing');

Status which indicate the image successfully processed by Claro Image.
define ('CLARO_POST_STATUS', 	'Image Finished');
Status which indicate the image is in processing stage by Claro Image.
define ('CLARO_PROCESS_STATUS', 'Image Processing');

Define the image type to be send for Claro Image Optimization. Only defined image type will get process, for Gif and PNG, it is not advisable cause results are unpredictable.
define ('CLARO_IMAGE_TYPE', serialize(array(".jpg",".jpeg", ".tif", ".tiff", ".psd")) );

Define the processing queue per brand, you can define different setting for different brands,
configuraiton can be set for,
(i)   DOCROP, allow for image cropping.
(ii)  DOROTATE, allow for image rotating,
(iii) EXPORT_PATH, location path where Claro Image Job XML file will located at.
(iv)  EXPORT_IMAGE_PATH, location where Enterprise Image will copy to for processing.
(v)   SERVER_EXPORT_IMG_PATH, location where the file system is on other server, you can set UNC path.
(vi)  IMPORT_PATH, location path where the processed images will be copy to and waiting to be import back to Enterprise.
define ('CLARO_CONFIG', serialize(array(
	'WW News' => array (		// for each brand name (case sensitve):
		'MIME' =>'image/jpg',		// MIME to CLaro, 'image/jpg' or psd 'image/vnd.adobe.photoshop'
		'EXT' => 'jpg',				// ext to Claro 'jpg' or 'psd'
		'DOCROP' => true,
		'DOROTATE' => true,
		'EXPORT_PATH' => ATTACHMENTDIRECTORY . '/_CLARO_/input/',
		'EXPORT_IMAGE_PATH' => ATTACHMENTDIRECTORY . '/_CLARO_/input/images/',
	//	'SERVER_EXPORT_IMG_PATH' => '/CLARO/to/image/',	// delete this line if not needed
		'IMPORT_PATH' => ATTACHMENTDIRECTORY . '/_CLARO_/output/'
		)
)

For Claro,
Create channel in Claro to handle the image processing, different channels need to be created if there is more that one publication configuration set in CLARO_CONFIG.

In the Input configuration,
Please set the Source folder = EXPORT_PATH in CLARO_CONFIG.
Please do not tick or mark the option to Scan subfolders, as the Claro service will depends on the job XML file to process the images.
In the Output configuration,
Please set the Destination folder = IMPORT_PATH in CLARO_CONFIG.
Please set the File format = File format of the original.

Problem Solving
Q1) Not able to send images to Claro.
Answer: This might due to invalid status being configure, you can check in the config.php, and make sure when you set the image properties, do select the correct status.

Q2) Not able to import latest updated images back to Enterprise.
Answer: This might due to the wrong IMPORT_PATH being set, please check whether path exist.

Q3) The return latest version of image doesn't cropped, when cropping did in InDesign Layout for the image.
Answer: This might due to the configuration of DOCROP, has been set to false.

Q4) The return latest version of image doen't rotate, when rotate did in InDesign Layout for the image.
Answer: This might due to the configuration of DOROTATE, has been set to false.

Q5) Some image that placed in the InDesign Layout, didn't get processed by Claro.
Answer: This might due to the image type, where it doesn't include in the image type definition.

Known limitations
1) The latest version of the processed image by Claro will be get import to Enterprise immediately, it depends on the schedule task or cron job.

Integration details.
We are integrating WoodWing Enterprise v6.1 and v7 with Claro Image v6.0.1 and v6.0.3.
