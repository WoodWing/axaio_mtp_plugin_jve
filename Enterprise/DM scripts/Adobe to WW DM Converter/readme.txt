Adobe .folio to WoodWing Digital Magazine converter

This script can be used to convert .folio files to a WoodWing Digital Magazine. This script is just made for a few customers.
The script doesn't follow the usual Enterprise coding guidelines.

Installation notes:

- Put this folder in the root of the webserver
- Put an extracted .folio folder in the IN folder (the Issue.xml file should be in the root of the IN directory)
- Make sure the IN and LIBRARY directories are readable by the PHP script
- Make sure the OUT and ZIP directory is writable by the PHP script
- Run the script by starting it via the browser
-- The URL will look something like: http://<server address>/<name of the folder>/convertissue.php
- After a while when the page is fully loaded the magazine is converted

- The exported magazine can be found in the OUT folder.