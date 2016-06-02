Temporary Solution (plugin) for TrashCan Feature. v8.0


================================================================================================================================================================
Using this plugin:
1. Place this folder(TrashCan) into /Enterprise/config/plugins/ folder.
2. Enable the plugin in the web admin page.



================================================================================================================================================================
In order to do delete trashCan and restoration in Content Station, 
you need to setup the following for Content Station:


1. Make sure you quit Content Station application.
2. Open WWSettings.xml and add the below inside <SCEnt:ContentStation>.

<ObjectContextMenuActions>

	<ObjectContextMenuAction label="Restore" url="{SERVER_URL}config/plugins/TrashCan/restore.php?ticket={SESSION_ID}" silent="true" objtypes="Article,ArticleTemplate,Image,Layout,LayoutTemplate,LayoutModule,LayoutModuleTemplate,Dossier,DossierTemplate,Task,Advert,AdvertTemplate,Library,Plan,Audio,Video,Hyperlink,Presentation,Other"/>

	<ObjectContextMenuAction label="Delete Permanent" url="{SERVER_URL}config/plugins/TrashCan/deletePermanent.php?ticket={SESSION_ID}&all=0" silent="true" objtypes="Article,ArticleTemplate,Image,Layout,LayoutTemplate,LayoutModule,LayoutModuleTemplate,Dossier,DossierTemplate,Task,Advert,AdvertTemplate,Library,Plan,Audio,Video,Hyperlink,Presentation,Other"/>

	<ObjectContextMenuAction label="Delete All" url="{SERVER_URL}config/plugins/TrashCan/deletePermanent.php?ticket={SESSION_ID}&all=1" silent="true" objtypes="Article,ArticleTemplate,Image,Layout,LayoutTemplate,LayoutModule,LayoutModuleTemplate,Dossier,DossierTemplate,Task,Advert,AdvertTemplate,Library,Plan,Audio,Video,Hyperlink,Presentation,Other"/>

</ObjectContextMenuActions>



================================================================================================================================================================
