DELETE FROM [smart_profilefeatures] WHERE [feature] = 1006;

UPDATE [smart_channels] SET [type] = 'other' WHERE [type] = 'newsfeed';

UPDATE [smart_objectrelations] SET [parenttype] = 'Article' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Article');

UPDATE [smart_objectrelations] SET [parenttype] = 'ArticleTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'ArticleTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Layout' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Layout');

UPDATE [smart_objectrelations] SET [parenttype] = 'LayoutTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'LayoutTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Image' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Image');

UPDATE [smart_objectrelations] SET [parenttype] = 'Advert' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Advert');

UPDATE [smart_objectrelations] SET [parenttype] = 'AdvertTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'AdvertTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Plan' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Plan');

UPDATE [smart_objectrelations] SET [parenttype] = 'Audio' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Audio');

UPDATE [smart_objectrelations] SET [parenttype] = 'Video' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Video');

UPDATE [smart_objectrelations] SET [parenttype] = 'Library' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Library');

UPDATE [smart_objectrelations] SET [parenttype] = 'Dossier' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Dossier');

UPDATE [smart_objectrelations] SET [parenttype] = 'DossierTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'DossierTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'LayoutModule' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'LayoutModule');

UPDATE [smart_objectrelations] SET [parenttype] = 'LayoutModuleTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'LayoutModuleTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Task' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Task');

UPDATE [smart_objectrelations] SET [parenttype] = 'Hyperlink' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Hyperlink');

UPDATE [smart_objectrelations] SET [parenttype] = 'Spreadsheet' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Spreadsheet');

UPDATE [smart_objectrelations] SET [parenttype] = 'Other' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'Other');

UPDATE [smart_objectrelations] SET [parenttype] = 'PublishForm' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'PublishForm');

UPDATE [smart_objectrelations] SET [parenttype] = 'PublishFormTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_objects] WHERE [type] = 'PublishFormTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Article' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Article');

UPDATE [smart_objectrelations] SET [parenttype] = 'ArticleTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'ArticleTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Layout' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Layout');

UPDATE [smart_objectrelations] SET [parenttype] = 'LayoutTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'LayoutTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Image' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Image');

UPDATE [smart_objectrelations] SET [parenttype] = 'Advert' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Advert');

UPDATE [smart_objectrelations] SET [parenttype] = 'AdvertTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'AdvertTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Plan' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Plan');

UPDATE [smart_objectrelations] SET [parenttype] = 'Audio' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Audio');

UPDATE [smart_objectrelations] SET [parenttype] = 'Video' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Video');

UPDATE [smart_objectrelations] SET [parenttype] = 'Library' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Library');

UPDATE [smart_objectrelations] SET [parenttype] = 'Dossier' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Dossier');

UPDATE [smart_objectrelations] SET [parenttype] = 'DossierTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'DossierTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'LayoutModule' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'LayoutModule');

UPDATE [smart_objectrelations] SET [parenttype] = 'LayoutModuleTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'LayoutModuleTemplate');

UPDATE [smart_objectrelations] SET [parenttype] = 'Task' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Task');

UPDATE [smart_objectrelations] SET [parenttype] = 'Hyperlink' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Hyperlink');

UPDATE [smart_objectrelations] SET [parenttype] = 'Spreadsheet' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Spreadsheet');

UPDATE [smart_objectrelations] SET [parenttype] = 'Other' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'Other');

UPDATE [smart_objectrelations] SET [parenttype] = 'PublishForm' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'PublishForm');

UPDATE [smart_objectrelations] SET [parenttype] = 'PublishFormTemplate' WHERE [parent] IN ( SELECT [id] FROM [smart_deletedobjects] WHERE [type] = 'PublishFormTemplate');

