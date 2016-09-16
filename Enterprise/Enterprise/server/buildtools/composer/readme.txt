Introduction
----------------------------------------------------------------
Since Enterprise Server 9.2, Composer is used to include 3rd party PHP packages. Composer 
takes care of downloading and installing the required packages. The packages can be found
at the Enterprise/server/vendor folder. Note that Composer is only used build-time, as 
the packages are shipped in the Enterprise package. The packages are build with the 
Enterprise/server/buildtools/composer/composer.sh batch file. Note that the buildtools
folder itself is -not- shipped with Enterprise, as it is internally used by WW staff only.
The required packages are auto loaded through the configserver.php file by including 
the Enterprise/server/vendor/autoload.php file.


How to obtain Composer?
----------------------------------------------------------------
See download and documentation: https://getcomposer.org/


How to add more packages using Composer?
----------------------------------------------------------------
1. Git:
	Make sure your change list is empty (stash all your files).
2. Text editor: Add required packages to the composer.json file.
	Path: Enterprise/server/buildtools/composer/composer.json
3. Terminal:
	cd Enterprise/server/buildtools/composer
	sh composer.sh
	L> Note that this regenerates composer files and downloads required packages.
4. Git: Add all modified and new files to the next commit.
	cmd> git add -all
5. Git:
	Commit the composer.json and the files under the vendor folder.
	cmd> git commit -m "Your Commit Message".
