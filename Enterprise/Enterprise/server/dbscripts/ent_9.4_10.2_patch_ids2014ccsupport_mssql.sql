ALTER TABLE [smart_indesignserverjobs] ADD 
  [maxservermajorversion] int NOT NULL  default '0',
  [maxserverminorversion] int NOT NULL  default '0';
CREATE PROCEDURE [dbo].[SCE_GetConstraintName] ( @tablename sysname, @columnName sysname, @constraintName sysname OUTPUT ) AS
SELECT @constraintName = o1.name FROM sysobjects o1
INNER JOIN sysobjects o2 ON o1.parent_obj = o2.id
INNER JOIN syscolumns c ON (o1.id = c.cdefault) OR (c.id = o2.id and c.cdefault = 0 and o1.xtype = 'PK')
WHERE (o2.name = @tablename) AND (c.name = @columnName);
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignserverjobs', @columnName = 'servermajorversion', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignserverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
EXECUTE sp_rename 'smart_indesignserverjobs.servermajorversion', 'minservermajorversion', 'COLUMN';
ALTER TABLE [smart_indesignserverjobs] ADD DEFAULT ('0') FOR [minservermajorversion];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignserverjobs', @columnName = 'serverminorversion', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignserverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
EXECUTE sp_rename 'smart_indesignserverjobs.serverminorversion', 'minserverminorversion', 'COLUMN';
ALTER TABLE [smart_indesignserverjobs] ADD DEFAULT ('0') FOR [minserverminorversion];
DROP PROCEDURE [dbo].[SCE_GetConstraintName];
INSERT INTO [smart_config] ([name], [value]) VALUES ('ids2014ccsupport', 'yes');
