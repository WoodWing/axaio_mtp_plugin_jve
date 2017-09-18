This directory contains SQL scripts for:
- Clean install of a new DB (current version).
- DB conversion from previous installed version to current version.
- DB updates that were introduced with patch releases of Enterprise Server.

These scripts are picked-up by the dbadmin.php tool (admin dir) to run automatically.
Alternatively, DB admin users could run the SQL scripts directly from a database tool.

These SQL files are generated from the WW_DbModel_Definition class.
Non-standard updates (update statements and alter change/drop) are registered in .txt files,
these files are made manually for each DB.

The dbmodelview.php module can be run at your web browser to inspect the DB model.
Optionally, a param can be given to check older DB model versions. 
For example, the v6.1 model can be shown/inspected like this:
	<web root>/Enterprise/server/dbmodel/dbmodelview.php?ver=6.1
