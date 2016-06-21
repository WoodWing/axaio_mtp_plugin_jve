EASY WORKFLOW PLUG-IN GENERATOR


Operation:

1. Create a folder in config/plugins, the folder name will be the name of the plug-in
2. Unzip the QuickPlugin archive into this folder
3. Make sure the webserver user has write access to the config/plugins folder (which usually is 
	the case in a development environment)
4. Run the webadmin serverplugins page - the plug-in definition file 'PluginInfo.class.php' is generated
5. Edit the generated 'PluginInfo.class.php', uncomment the connectors you need to 
	implement
6. Run the webadmin serverplugins page again - the connector implementation files will
	be generated


Adding connectors:

If at any time you want to add a connector to the plug-in, uncomment the corresponding
line in PluginInfo.class.php and run the webadmin serverplugins again. An empty
implementation for the connector will be generated.


How it works:

The PluginInfo.php file, which normally contains the plugin definitions, now contains
a bit of code which generates the PluginInfo.class.php file from the template file 
PluginInfo.template.php. From the defined connector list, it generates empty implementation
files for each connector which has no implementation yet, based on the Connector.template.php
files.



