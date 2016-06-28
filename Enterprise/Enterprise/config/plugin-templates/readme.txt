SERVER PLUG-IN GENERATOR

The Server Plug-in Generator built into Enterprise Server provides a starting point for 
developing your own Server plug-ins. When run for the first time, PHP file are generated 
that contain class definitions and functions which are required for any plug-in. In subsequent 
runs it can generate any Service and/or Business connectors that you would like to add to 
your plug-in. Note that since Enterprise Server 9.0, the functionality of the QuickPlugin 
(as available on WoodWing Labs) is integrated in the core and is therefore no longer needed.

Operation:

1. Create a new folder under the config/plugins folder.
	=> Use camel case without spaces, for example: HelloWorld
2. Make sure that the webserver user has write access to the new plugin folder.
	=> This is only needed during development, not for the production server.
3. Make sure DEBUG mode is enabled. See the DEBUGLEVELS option in the configserver.php file.
4. Run the Server Plug-ins web admin page.
	=> The following files are generated and saved in the plug-in folder:
		- PluginInfo.php
		- config.php
		- NOTICE
		- LICENSE
5. Edit the generated 'PluginInfo.php':
	- Uncomment the connectors you need to implement.
	- Fill in a hard-coded description at $info->Description.
6. Run the Server Plug-ins page in Enterprise Server again.
	=> The connector implementation files will be generated.
7. Implement the runBefore() or runAfter() methods of the connector. 
	=> Those functions are called by Enterprise Server when it handles a workflow service request.


Adding connectors:

If at any time you want to add a connector to the plug-in, uncomment the corresponding 
line in PluginInfo.php and run the Server Plug-ins page again. An empty implementation 
for the connector will be generated.


How it works:

The generated files are based on template files that can be found in the following folder:
	config/plugin-templates/<filename>.template.php
For each defined connector (listed in the PluginInfo.php file) a connector file is generated
that has all required function definitions in place. The function bodies are left empty 
for you to do the actual implementation. The generated connector files are based on one 
of the following template files:
	BusinessConnector.template.php
	ServiceConnector.template.php
