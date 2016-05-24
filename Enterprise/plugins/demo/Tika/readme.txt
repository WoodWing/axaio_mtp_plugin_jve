Apache Tika integration for Enterprise v9

-------------------------
1. Introduction
-------------------------
This document describes the Tika integration for Enterprise. This include topics of overview, installation, configuration, troubleshooting, limitations.

Tika's great power is the ability to read various kinds of rich format documents, such as PDF, Microsoft Word, Excel and Powerpoint; functionality which is not available in an out-of-the-box installation of Enterprise Server (as a result, uploaded  documents cannot be found by Enterprise, nor by any of its installed Search Servers such as Solr). Enterprise can be enriched with Tika's functionality through an integration - established by a server plug-in named "Tika Text and Data Extraction" - a Tika Server and the Tika application. All three components are shipped in the server plug-in package to ease installation.

Information about Tika can be found here: http://tika.apache.org/.

-------------------------
2. Functional Overview
-------------------------
When a user uploads or saves a document, the Tika integration is triggered by the system. It is requested to extract the plain content of the file being uploaded and to enrich the object being created or saved on-the-fly. When the user searches for any textual content, the uploaded document will now be found by Enterprise. 

The integration is triggered for articles, images, adverts, presentation, archive and files of type 'Other'. A set of file formats can be configured to tell the integration which documents to handle. (Other object types are not handled, regardless of the format.)

Once the Tika integration is installed, any existing documents (those stored before Tika functionality was integrated) won't be found by Enterprise because the integration was not triggered by an upload or save operation. For this purpose, the Tika Content Extraction admin tool can be used to lookup candidate objects for extraction. Via a single mouse click, all candidate objects will be processed and enriched with plain content.

Customers with a PDF/Word/Excel based workflow typically need the Tika integration the most. Therefore, it has wrapped the Tika application by a Tika Server to expose its functionality through HTTP. This way, the Tika application is not bound to the same machine on which Enterprise Server runs; there can be many Enterprise Servers sharing just one Tika Server. This allows system admins to have more control to do load balancing or to ease future Tika application upgrades. By default, the Tika Server and application run on the same machine as Enterprise Server though.

-------------------------
3. Installation
-------------------------
Please follow these steps:
1.	Make sure you have Enterprise Server v9.0.0 (or newer) installed.
2.	Extract the Tika server plug-in into the following folder:
		<Enterprise>/config/plugins
3.	In Enterprise Server, access the Server Plug-ins Maintenance page.
	-> The "Tika Text and Data Extraction" plug-in should be listed.
	-> It should show the green connector icon.
	-> In case the yellow lightning icon is shown, click it to check installation and follow the instructions shown to solve any raised configuration issues.
4.	Run the Health Check page:
		<Enterprise>/server/wwtest
	-> The Server Plug-ins check should give no errors.
5.	Check the TIKA_FORMATS option in the  <Enterprise>/config/plugins/Tika/config.php file.
	-> Add any formats you want to support (and remove those that should not be supported).
	-> Add only those formats that are supported by Tika.
	-> Make sure added formats are also configured in the EXTENSIONMAP option in the <Enterprise>/config/configserver.php file

At this point, your installation is complete.

Additionally, you can continue with the installation steps in any of the sections below. Those steps are only for special purposes and are NOT required.

- - - - - - - - - - - - - - - - - - - - - - - - - - 
3A. OPTIONAL Installation: Updating the Tika application
- - - - - - - - - - - - - - - - - - - - - - - - - - 
The Tika integration ships a pre-compiled build of the Tika application. This is a part of the Apache Tika package, available for Mac OS, Windows and Linux. Note that the current Tika version is shown on the Server Plug-ins Maintenance page. If you prefer using a newer version of Tika, follow these steps:

1.	Download a newer Apache Tika version from:
		http://tika.apache.org/
-> In the following steps, we assume you upgrade from v1.3 to a higher version (say v1.4).
2.	Follow the instructions on the Tika website to compile or build Tika.
3.	Create a new folder named "tika-1.4" under TikaApp:
		<Enterprise>/config/plugins/Tika/TikaApp/tika-1.4
4.	Copy the new Tika application files from your build environment:
		tika-app/target/tika-app-1.4.jar
		tika-app/pom.xml
	to the created folder:
		<Enterprise>/config/plugins/Tika/TikaApp/tika-1.4
5.	Open <Enterprise>/config/plugins/Tika/TikaServer/config.php in a plain-text editor.
5a.	Change the Tika application path from:
		define ('TIKA_APP_DIRECTORY', dirname(__FILE__).'/../TikaApp/tika-1.3');
	to:
		define ('TIKA_APP_DIRECTORY', dirname(__FILE__).'/../TikaApp/tika-1.4');
5b.	Change the Tika application path from:
		define ('TIKA_APP', TIKA_APP_DIRECTORY.'/tika-app-1.3.jar'); 
	to:
		define ('TIKA_APP', TIKA_APP_DIRECTORY.'/tika-app-1.4.jar'); 
6.	Run the following URL in a Web browser
		http://<Tika_IP>/TikaServer/TikaHealthTest.php
	-> It should show this message: "Tika Server installed"
7.	Choose "Server Plug-ins" from menu bar to let it check its configuration.
	-> The "Tika Text and Data Extraction" plug-in should be listed.
	-> It should show the green connector icon.
	-> In case the yellow lightning icon is shown, click it to check installation and follow the instructions shown to solve any raised configuration issues.
	
- - - - - - - - - - - - - - - - - - - - - - - - - - 
3B. OPTIONAL Installation: Sharing one Tika Server
- - - - - - - - - - - - - - - - - - - - - - - - - - 
If you have followed the above installation steps, each Enterprise Server has its own Tika Server. When you prefer to let all Enterprise Servers share a single Tika Server, follow the additional steps below. This could be done for load balancing reasons or to ease future Tika application upgrades.

1.	For just -ONE- Enterprise Server installation, do the following:
1a.	Copy the TikaApp folder from:
		<Enterprise>/config/plugins/Tika/TikaApp/tika-1.3
	to the application location of your Tika machine, for example:
		/usr/local/tika-1.3
1b.	Open the Tika Server configuration file in a plain-text editor:
		<Enterprise>/config/plugins/Tika/TikaServer/config.php
	and change the Tika application path at from:
		define ('TIKA_APP_DIRECTORY', dirname(__FILE__).'/../TikaApp/tika-1.3');
	into:
		define ('TIKA_APP_DIRECTORY', '/usr/local/tika-1.3');
1c.	Copy the TikaServer folder:
		<Enterprise>/config/plugins/Tika/TikaServer
	to the web root of the Tika machine, for example:
		/Library/WebServer/Documents/TikaServer
2.	For -ALL- Enterprise Server installations, do the following:
2a.	Open the Tika server plug-in configuration file in a plain text editor:
		<Enterprise>/config/plugins/Tika/config.php 
	and change the Tika Server URL from:
		define ('TIKA_SERVER_URL', SERVERURL_ROOT.INETROOT.'/config/plugins/Tika/TikaServer/');
	to the HTTP location of your Tika machine:
		define ('TIKA_SERVER_URL', 'http://<Tika_IP>/TikaServer/' );
2b.	Run the follwing URL in a Web browser
		http://<Tika_IP>/TikaServer/TikaHealthTest.php
	-> It should show this message: "Tika Server installed"
2c.	In Enterprise Server, access the Server Plug-ins Maintenance page.
	-> The "Tika Text and Data Extraction" plug-in should be listed.
	-> It should show the green connector icon.
	-> In case the yellow lightning icon is shown, click it to check installation and follow the instructions shown to solve any raised configuration issues.

-------------------------
4. Configuration
-------------------------
There are two configuration files involved:
1. Tika server plug-in:
	<Enterprise>/config/plugins/Tika/config.php
2. Tika Server:
	<Enterprise>/config/plugins/Tika/TikaServer/config.php
See the comments in those files for a detailed explanation of the options.

-------------------------
5. Troubleshooting
-------------------------
When you have uploaded or saved a document, but it can not be found by Enterprise, this section could help you trace the cause of the problem. Note that Solr and Tika do not block end-users from uploading or saving documents. When anything goes wrong, errors are logged by Enterprise Server for system admin users, without disturbing the end-users working on production. The reason for this is that both are seen as add-on features.

- - - - - - - - - - - - - - - - - - - - - - - - - - 
5A. Checking the Tika installation and configuration
- - - - - - - - - - - - - - - - - - - - - - - - - - 
To check if there is a problem with the Tika integration, do the following:
1.	Run the wwtest.
	-> If any problem is reported, please fix this first.
2.	In Enterprise Server, access the Server Plug-ins Maintenance page.
	-> The "Tika Text and Data Extraction" plug-in should be listed. If not, check the Installation section above.
	-> When the Tika server plug-in shows the red unplugged connector icon, click on it to enable. In case the yellow lightning icon is shown, click it to check installation and follow the instructions shown to solve any raised configuration issues.

- - - - - - - - - - - - - - - - - - - - - - - - - - 
5B. Check if content was extracted successfully
- - - - - - - - - - - - - - - - - - - - - - - - - - 
1.	It could be that the Tika Server was down at the time the document was uploaded. Or, that the document could not be processed by Tika due to an internal failure. To find out which of these scenarios took place, run the following tool:
		<Enterprise>/config/plugins/Tika/contentextraction.php
	-> When it was skipped, the progress bar is less than 100%. Click Start to resolve.
	-> When an error occurred, the document is reported. Click Retry to make sure it is a consistent processing problem. If there are still documents listed with the error status, see the next section for how to check these errors.
2.	Maybe the content extraction went OK, but the searched text can somehow not be found. The Search Server (such as Solr) could have been down at the time the document was uploaded or saved. To find out, run the following tool:
		<Enterprise>/admin/searchindexing.php
	-> When the progress bar is less than 100%, click Start and try searching the document again.
3.	Let's make sure if the extraction worked. To find out, start a DB admin tool and search for the problematic object record. Check if the "plaincontent" and "slugline" fields are contain text. 
3a.	If text is available, run the admin tool of your Search Server, for example:
		http://localhost:8080/solr
	-> Search for the content using this tool. Regardless the outcome, this point goes beyond scope of this document. See the Enterprise Admin Guide for more details.
3b.	If no text is filled, the uploaded document could simply have been skipped by the Tika process. This happens when the object type is not any of the following: 'Article', 'Image', 'Advert' or 'Other'. It may also be that it does not match any of the configured file formats as configured in the TIKA_FORMATS option.
	-> Open the CreateObject or SaveObjects file from soap subfolder in the server logging. Check the Type and Format elements. When all OK, continue with the next section.

- - - - - - - - - - - - - - - - - - - - - - - - - - 
5C. Check any reported errors in the server logging
- - - - - - - - - - - - - - - - - - - - - - - - - - 
1.	Open the configserver.php file in a plain-text editor.
1a.	Set the DEBUGLEVELS to DEBUG.
1b.	Set the OUTPUTDIRECTORY to a valid directory path.
2.	Open the sce_log_<DB>.htm file in the server log folder in a Web browser.
2a.	Starting at the bottom of the page, search for "Tika".
	-> Locate any "Tika" log marked with "ERROR". 
2b.	Check if any errors match with one of the following:
	- "Unable to read response, or response is empty"
		-> Check the time stamp of the error log and the previous one. If the difference exceeds the TIKA_CONNECTION_TIMEOUT option value (default 600 seconds), you might need to increase that option.
	- HTTP 404 error
		-> The Tika Server could have been down temporarily, is unaccessible or the TIKA_SERVER_URL option is badly configured.
	- Any other HTTP error.
		-> Lookup the code online to solve the problem:
			http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
	- Tika application error.
		-> Continue with the next section.

- - - - - - - - - - - - - - - - - - - - - - - - - - 
5D. Check if a document is problematic for Tika
- - - - - - - - - - - - - - - - - - - - - - - - - - 
1.	Start the terminal / console
2.	Go to the Tika application path, for example:
		cd /Library/WebServer/Documents
		cd Enterprise/config/plugins/Tika/TikaApp/tika-1.3
3.	Extract plain content from your document, for example:
		java -Djava.awt.headless=true -Xms512m -Xmx1024m -jar "tika-app-1.3.jar" -e"UTF-8" -t <your_document>
-> It should show the extracted plain text. If not, please check the Tika website: http://tika.apache.org/

-------------------------
6. Known Issues and Limitations
-------------------------
- Tika metadata extraction is NOT supported yet; only Tika's plain content extraction feature is used by Enterprise Server.
- On the "Tika content extraction" page, the menu items (hyperlinks) do not work. This is fixed in the core product since Enterprise Server v7.0.6.
- Tika delays upload and save operations. See next section for details. Better would be to have an asynchronous integration.

-------------------------
7. Performance
-------------------------
The Tika Server is called synchronously, which delays save and check-in times. The larger the document, the bigger the delay. To give a rough idea, following are some results measured on a Mac OSX 10.5 machine running Tika v0.7:

	Microsoft Word documents:
	- 256Kb took 2 seconds
	- 500Kb took 2 seconds
	- 1Mb   took 2 minutes
	- 2Mb   took 3 minutes

	Adobe PDF documents:
	- 232Kb took 9 seconds
	- 456Kb took 13 seconds
	- 896Kb took 33 seconds
	- 1.7M  took 1 minute

To check durations of Tika content extraction, do the following:
1.	Open the configserver.php file in a plain-text editor.
1a.	Set the PROFILELEVEL option to 2 (or higher)
1b.	Set the DEBUGLEVELS to INFO or DEBUG.
1c.	Set the OUTPUTDIRECTORY to a valid directory path.
2.	Upload or save any document format for which Tika is configured.
3.	Open the sce_profile_<DB>.htm from the Enterprise Server log folder.
3a.	Search for rows starting with "Calling Tika Server".
3b.	Lookup the "Duration" column, which tells Tika execution time in seconds.
	-> Note that memory usages shown do NOT include Tika integration, since Enterprise Server fires requests through HTTP to the Tika Server, which is a different process for which memory is not measured.
