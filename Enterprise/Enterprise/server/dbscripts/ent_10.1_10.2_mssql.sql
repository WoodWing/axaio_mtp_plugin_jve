ALTER TABLE [smart_serverplugins] ADD 
  [dbprefix] varchar(10) not null  default '',
  [dbversion] varchar(10) not null  default '';
UPDATE [smart_config] set [value] = '10.2' where [name] = 'version';
