This directory (server/dbscripts) contains SQL scripts for:
- Clean install of a new DB (current version).
- DB conversion from previous installed version to current version.
- DB updates that were introduced with patch releases of Enterprise Server.

SQL files are generated with the server/buildtools/dbgen.php tool and written in this folder.
These scripts are picked-up by the server/admin/dbadmin.php tool to run automatically.
Alternatively, DB admin users could run the SQL scripts directly from a database tool.

The server/dbscripts/dbupgrades folder contains PHP scripts that migrate some data in installed/upgraded DB model.
Those are listed by the dbadmin.php tool which executes them once only. (After a successful run, a flag is saved
in the smart_config table to avoid running another time.)

The server/dbmodel/dbmodelview.php tool can be run at your web browser to inspect the DB model.
For example: <web root>/Enterprise/server/dbmodel/dbmodelview.php

The server/dbmodel/dbmodeldiff.php tool can be run at your web browser to validate a DB installation.
It compares the installed DB model with the defined DB model and reports any differences. (MySQL only.)

Server plugins may provider their own DB model which will be automatically picked up by the tools mentioned.
They can extend the Enterprise DB model with additional tables for their own need.
