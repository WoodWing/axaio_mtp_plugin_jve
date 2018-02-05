ALTER TABLE [smart_objectlocks] ADD 
  [appname] varchar(200) NOT NULL  default '',
  [appversion] varchar(200) NOT NULL  default '';
UPDATE [smart_config] set [value] = '10.3' where [name] = 'version';
