
CREATE TABLE [smart_lvs_tokens] (
  [entuser] varchar(40) NOT NULL  default '',
  [elvisuser] varchar(40) NOT NULL  default '',
  [token] varchar(1024) NOT NULL  default '',
  PRIMARY KEY ([entuser])
);
UPDATE [smart_serverplugins] SET [dbversion] = '1.0' WHERE [uniquename] = 'Elvis';
UPDATE [smart_serverplugins] SET [dbprefix] = 'smart_lvs_' WHERE [uniquename] = 'Elvis';
