---------------
Introduction
---------------
This sample plug-in shows how custom object properties can be added. It also shows
how to implement the Publish Forms concept. However, form templates are hard-coded and 
it does -not- support the Publish/Unpublish/Preview operations for the created forms.
This plug-in can be used for testing or for integrators to study the concept.

---------------
Installation
---------------
1. Extract the plug-in named MultiChannelPublishingSample in the config/plugins folder.
2. Access the Server Plug-in page and make sure that the plug-in is listed and installed (plugged in).

---------------
Configuration
---------------
1. Setup a new Publication Channel under Brand Admin page, set the ‘Publication Channel Type’ 
   to be ‘web’ and ‘Publish System’ to be ‘Custom Properties and Sample Template Demo’.
2. Create an issue under the Publication Channel created above.
3. You may visit the Dialogs Setup admin page to add the custom properties (introduced by 
   the plug-in) to your workflow dialogs. The custom properties are prefixed with 
   C_CUSTOBJPROPDEMO and were automatically installed into Enterprise DB when you did  
   visit the Server Plug-in page. Adding properties to dialogs is optional.
4. Import Publish Form Templates:
   a. In the Maintenance menu, click the Integrations entry.
   b. Open the Import Definitions page.
   c. Click the Import button.

---------------
Usage
---------------
1. Create a new dossier and assign it to the Issue (created above).
2. Open the Dossier in Content Station.
   => List of Publish Form Templates is shown.
3. Choose of of the templates (as provided by the plug-in).
   => A new Publish From is created based on the chosen template.
4. Fill-in the form and Check-In.
