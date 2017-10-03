ALTER TABLE [smart_authorizations] ADD 
  [bundle] int NOT NULL  default '0';
CREATE PROCEDURE [dbo].[SCE_GetConstraintName] ( @tablename sysname, @columnName sysname, @constraintName sysname OUTPUT ) AS
SELECT @constraintName = o1.name FROM sysobjects o1
INNER JOIN sysobjects o2 ON o1.parent_obj = o2.id
INNER JOIN syscolumns c ON (o1.id = c.cdefault) OR (c.id = o2.id and c.cdefault = 0 and o1.xtype = 'PK')
WHERE (o2.name = @tablename) AND (c.name = @columnName);
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_authorizations', @columnName = 'rights', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_authorizations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_authorizations ALTER COLUMN   [rights] varchar(1024) NOT NULL ;
ALTER TABLE [smart_authorizations] ADD DEFAULT ('') FOR [rights];
ALTER TABLE [smart_deletedobjects] ADD 
  [orientation] tinyint NOT NULL  default '0';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_deletedobjects', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_deletedobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_deletedobjects ALTER COLUMN   [dpi] real NOT NULL ;
ALTER TABLE [smart_deletedobjects] ADD DEFAULT ('0') FOR [dpi];
ALTER TABLE [smart_objects] ADD 
  [orientation] tinyint NOT NULL  default '0';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objects', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objects ALTER COLUMN   [dpi] real NOT NULL ;
ALTER TABLE [smart_objects] ADD DEFAULT ('0') FOR [dpi];
ALTER TABLE [smart_objectversions] ADD 
  [orientation] tinyint NOT NULL  default '0';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectversions', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectversions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectversions ALTER COLUMN   [dpi] real NOT NULL ;
ALTER TABLE [smart_objectversions] ADD DEFAULT ('0') FOR [dpi];

CREATE TABLE [smart_featureaccess] (
  [featurename] varchar(255) NOT NULL  default '',
  [featureid] int NOT NULL  default '0',
  [accessflag] int NOT NULL  default '0',
  PRIMARY KEY ([featurename])
);
CREATE UNIQUE INDEX [faid_profiles] ON [smart_featureaccess]([featureid]) ;
CREATE UNIQUE INDEX [faaf_profiles] ON [smart_featureaccess]([accessflag]) ;

CREATE TABLE [smart_publishedplcmtshist] (
  [id] int NOT NULL  IDENTITY(1,1),
  [objectid] int NOT NULL  default '0',
  [publishid] int NOT NULL  default '0',
  [majorversion] int NOT NULL  default '0',
  [minorversion] int NOT NULL  default '0',
  [externalid] varchar(200) NOT NULL  default '',
  [placementhash] varchar(64) NOT NULL ,
  PRIMARY KEY ([id])
);
CREATE  INDEX [obpu_publplchist] ON [smart_publishedplcmtshist]([objectid], [publishid]) ;
CREATE  INDEX [puob_publplchist] ON [smart_publishedplcmtshist]([publishid], [objectid]) ;
ALTER TABLE [smart_indesignserverjobs] ADD 
  [pickuptime] varchar(30) NOT NULL  default '';
ALTER TABLE [smart_serverplugins] ADD 
  [dbprefix] varchar(10) NOT NULL  default '',
  [dbversion] varchar(10) NOT NULL  default '';
DROP PROCEDURE [dbo].[SCE_GetConstraintName];
UPDATE [smart_config] set [value] = '10.2' where [name] = 'version';
