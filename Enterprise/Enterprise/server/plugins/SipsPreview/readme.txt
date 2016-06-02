Sips Preview server plug-in for Enterprise

-------------------------
1. Introduction
-------------------------
Since Enterprise v8, the "Sips Preview" server plug-in is shipped with Enterprise Server. The plug-in uses the SIPS command (Scriptable Image Processing System) in Mac OSX to generate image previews and thumbnails. The server plug-in will be picked by Enterprise Server (to generate image previews and thumbnails) when it is the best/fastest among other installed preview server plug-ins. This is based on the image file format being uploaded (or saved) by end users.

-------------------------
2. Installation
-------------------------
Follow instructions at Admin Guide how to setup Enterprise Server. For the server plug-in, no additional installation steps are required. The Sips command is usually bundled and installed with the Mac OS.

To check installation: In Enterprise Server, access the Server Plug-ins Maintenance page.
-> The "Sips Preview" plug-in should be listed.
-> It should show the green connector icon. In case the yellow lightning icon is shown, click it to check installation and follow the instructions shown to solve any raised configuration issues.

-------------------------
3. Configuration
-------------------------
See instructions at the config/config_sips.php file.

-------------------------
4. Troubleshooting
-------------------------
Problem: Image previews and thumbnails are generated, but do not trigger Sips.
=> It might be another preview connector which does a better job than Sips generates the image previews and thumbnails. You could try other image formats or disable other installed preview plug-ins.
=> You might use a client application that generates (and uploads) the preview/thumb by itself.

-------------------------
5. Known Issues and Limitations
-------------------------
Can only be used in combination with Mac OS.  
