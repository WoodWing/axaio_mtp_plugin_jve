CREATE PROCEDURE [dbo].[SCE_GetConstraintName] ( @tablename sysname, @columnName sysname, @constraintName sysname OUTPUT ) AS
SELECT @constraintName = o1.name FROM sysobjects o1
INNER JOIN sysobjects o2 ON o1.parent_obj = o2.id
INNER JOIN syscolumns c ON (o1.id = c.cdefault) OR (c.id = o2.id and c.cdefault = 0 and o1.xtype = 'PK')
WHERE (o2.name = @tablename) AND (c.name = @columnName);
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_deletedobjects', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_deletedobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_deletedobjects ALTER COLUMN   [dpi] real not null ;
ALTER TABLE [smart_deletedobjects] ADD DEFAULT ('0') FOR [dpi];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objects', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objects ALTER COLUMN   [dpi] real not null ;
ALTER TABLE [smart_objects] ADD DEFAULT ('0') FOR [dpi];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectversions', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectversions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectversions ALTER COLUMN   [dpi] real not null ;
ALTER TABLE [smart_objectversions] ADD DEFAULT ('0') FOR [dpi];
ALTER TABLE [smart_states] ADD 
  [skipidsa] char(2) not null  default '';
CREATE  INDEX [lt_indesignserverjobs] on [smart_indesignserverjobs]([locktoken]) ;
DROP PROCEDURE [dbo].[SCE_GetConstraintName];
UPDATE [smart_config] set [value] = '10.1' where [name] = 'version';
