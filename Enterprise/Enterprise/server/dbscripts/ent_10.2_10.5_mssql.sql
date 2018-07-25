ALTER TABLE [smart_deletedobjects] ADD 
  [masterid] bigint NOT NULL  default '0';
ALTER TABLE [smart_objectlocks] ADD 
  [appname] varchar(200) NOT NULL  default '',
  [appversion] varchar(200) NOT NULL  default '';
ALTER TABLE [smart_objects] ADD 
  [masterid] bigint NOT NULL  default '0';
UPDATE [smart_config] set [value] = '10.5' where [name] = 'version';
