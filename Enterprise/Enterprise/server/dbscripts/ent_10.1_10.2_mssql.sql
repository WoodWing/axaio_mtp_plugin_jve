ALTER TABLE [smart_serverplugins] ADD 
  [dbprefix] varchar(10) NOT NULL  default '',
  [dbversion] varchar(10) NOT NULL  default '';
UPDATE [smart_config] set [value] = '10.2' where [name] = 'version';
