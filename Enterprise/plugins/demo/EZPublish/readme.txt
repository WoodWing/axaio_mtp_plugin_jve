Installation guide eZ Publish Demo for Enterprise 6.1 and 7:

1. Be sure to have a backup of everything!

2. Copy the 'ezpublishdemo' map from the demo folder to your webserver folder.

3. Execute the sql file in the demo map on your mysql database

4. If you have an user in your mysql database named root using password root, go to the next step, otherwise, there are three files to edit:
 - ezpublishdemo/settings/siteaccess/admin_site/site.ini.append.php
 - ezpublishdemo/settings/siteaccess/eng/site.ini.append.php
 - ezpublishdemo/settings/siteaccess/user_site/site.ini.append.php Edit the [DatabaseSettings] part of these files to correspond to a user in your MySQL database.

5. Install the Enterprise eZ Publish plugin into your Enterprise installation.

6. Edit the EZPUBLISH_URL and EZPUBLISH_EXTERNAL_URL in the config file to your own settings.

7. Enable the plugin in enterprise (see admin guide)

8. Add a publication channel for eZ Publish (see admin guide)

9. Start publishing