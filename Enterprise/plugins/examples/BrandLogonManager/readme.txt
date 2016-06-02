Brand Logon Manager

1. Introduction
This plug-in implements functionality to limit the number of logons per Brand. This can be essential for Agencies using a single Enterprise system for multiple brands. This way you can prevent one brand from 'eating up' all Enterprise connections preventing other brands from logging on.

2. Functional Overview
After installing the plugin, you are able to limit the number of users to logon from one single brand.
The user's brand is determined by the Organization field of the user. So strictly speaking you could argue that this is actually an Organization Logon Manager.
In a configuration file you can specify a limit per brand and optionally a default 'fallback' for brands without configure limit.

3. Installation
By default, the plug-in will need to be installed in the Enterprise/config/plug-ins folder. 
Do this by unzipping the files into this folder.
Next, follow the configuration instructions shown in below. 
After configuration the plug-in needs to be enabled in Enterprise:
Step 1.Log-in to Enterprise server.
Step 2.In the menu bar, click Server Plug-ins.
Step 3.Check that the "Brand Logon Manager" plug-in is listed.
Step 4.Click the plug in icon to enable it.

4. Configuration
4.1 Setting User Organization
Make sure that each user has its Organization filled in.

4.2 Configure limits
Open the plug-ins config.php and add limits per brand that you want to limit. Make sure to end each entry with a comma except the last entry, for example:

$brandLimits = array( 
	'WoodWing' => 1,
	'*' => 0
);

You can use '*' for the default limit that will be used for all brands (organizations) not listed.

Problem Solving
Q1 ) The server plug-in page opens with a blank page
Answer: You can a PHP error in config.php. Turn on debug logging and check php.log

Known limitations
1) Logons are limited per unique user independent of client application.
