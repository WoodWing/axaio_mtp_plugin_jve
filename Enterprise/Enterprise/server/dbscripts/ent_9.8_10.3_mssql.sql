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
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_deletedobjects', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_deletedobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_deletedobjects ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_deletedobjects', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_deletedobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_deletedobjects ALTER COLUMN   [dpi] real NOT NULL ;
ALTER TABLE [smart_deletedobjects] ADD DEFAULT ('0') FOR [dpi];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_log', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_log DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_log ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_log', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_log DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_log ALTER COLUMN   [objectid] bigint NOT NULL ;
ALTER TABLE [smart_log] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_log', @columnName = 'parent', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_log DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_log ALTER COLUMN   [parent] bigint NOT NULL ;
ALTER TABLE [smart_log] ADD DEFAULT ('0') FOR [parent];
ALTER TABLE [smart_objectlocks] ADD 
  [appname] varchar(200) NOT NULL  default '',
  [appversion] varchar(200) NOT NULL  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectlocks', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectlocks DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectlocks ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectlocks', @columnName = 'object', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectlocks DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_objectlocks]') AND is_unique_constraint = 1 AND name = N'ob_objectlocks' ) ALTER TABLE [smart_objectlocks] DROP CONSTRAINT [ob_objectlocks] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_objectlocks]')
										AND is_unique_constraint = 0 AND name = N'ob_objectlocks' ) DROP INDEX [smart_objectlocks].[ob_objectlocks] ;
DROP INDEX [smart_objectlocks].[obusr_objectlocks] ;
ALTER TABLE smart_objectlocks ALTER COLUMN   [object] bigint NOT NULL ;
CREATE UNIQUE INDEX [ob_objectlocks] ON [smart_objectlocks]([object]) ;
CREATE  INDEX [obusr_objectlocks] ON [smart_objectlocks]([object], [usr]) ;
ALTER TABLE [smart_objectlocks] ADD DEFAULT ('0') FOR [object];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelations', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_objectrelations].[child_type_id] ;
ALTER TABLE smart_objectrelations ALTER COLUMN   [id] bigint NOT NULL ;
CREATE  INDEX [child_type_id] ON [smart_objectrelations]([child], [type], [id]) ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelations', @columnName = 'parent', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_objectrelations]') AND is_unique_constraint = 1 AND name = N'ch_objectrelations' ) ALTER TABLE [smart_objectrelations] DROP CONSTRAINT [ch_objectrelations] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_objectrelations]')
										AND is_unique_constraint = 0 AND name = N'ch_objectrelations' ) DROP INDEX [smart_objectrelations].[ch_objectrelations] ;
DROP INDEX [smart_objectrelations].[pachty_objectrelations] ;
ALTER TABLE smart_objectrelations ALTER COLUMN   [parent] bigint NOT NULL ;
CREATE UNIQUE INDEX [ch_objectrelations] ON [smart_objectrelations]([parent], [child], [subid], [type]) ;
CREATE  INDEX [pachty_objectrelations] ON [smart_objectrelations]([parent], [child], [type]) ;
ALTER TABLE [smart_objectrelations] ADD DEFAULT ('0') FOR [parent];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelations', @columnName = 'child', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_objectrelations]') AND is_unique_constraint = 1 AND name = N'ch_objectrelations' ) ALTER TABLE [smart_objectrelations] DROP CONSTRAINT [ch_objectrelations] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_objectrelations]')
										AND is_unique_constraint = 0 AND name = N'ch_objectrelations' ) DROP INDEX [smart_objectrelations].[ch_objectrelations] ;
DROP INDEX [smart_objectrelations].[pachty_objectrelations] ;
DROP INDEX [smart_objectrelations].[child_type_id] ;
ALTER TABLE smart_objectrelations ALTER COLUMN   [child] bigint NOT NULL ;
CREATE UNIQUE INDEX [ch_objectrelations] ON [smart_objectrelations]([parent], [child], [subid], [type]) ;
CREATE  INDEX [pachty_objectrelations] ON [smart_objectrelations]([parent], [child], [type]) ;
CREATE  INDEX [child_type_id] ON [smart_objectrelations]([child], [type], [id]) ;
ALTER TABLE [smart_objectrelations] ADD DEFAULT ('0') FOR [child];
ALTER TABLE [smart_objects] ADD 
  [orientation] tinyint NOT NULL  default '0';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objects', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_objects].[pubid_objects] ;
DROP INDEX [smart_objects].[roid_objects] ;
ALTER TABLE smart_objects ALTER COLUMN   [id] bigint NOT NULL ;
CREATE  INDEX [pubid_objects] ON [smart_objects]([publication], [id], [closed]) ;
CREATE  INDEX [roid_objects] ON [smart_objects]([routeto], [id], [closed]) ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objects', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objects ALTER COLUMN   [dpi] real NOT NULL ;
ALTER TABLE [smart_objects] ADD DEFAULT ('0') FOR [dpi];
ALTER TABLE [smart_objectversions] ADD 
  [orientation] tinyint NOT NULL  default '0';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectversions', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectversions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectversions ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectversions', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectversions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_objectversions].[oive_objectversions] ;
ALTER TABLE smart_objectversions ALTER COLUMN   [objid] bigint NOT NULL ;
CREATE  INDEX [oive_objectversions] ON [smart_objectversions]([objid], [majorversion], [minorversion]) ;
ALTER TABLE [smart_objectversions] ADD DEFAULT ('0') FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectversions', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectversions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectversions ALTER COLUMN   [dpi] real NOT NULL ;
ALTER TABLE [smart_objectversions] ADD DEFAULT ('0') FOR [dpi];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrenditions', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrenditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectrenditions ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrenditions', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrenditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_objectrenditions].[obed_objectrenditions] ;
ALTER TABLE smart_objectrenditions ALTER COLUMN   [objid] bigint NOT NULL ;
CREATE  INDEX [obed_objectrenditions] ON [smart_objectrenditions]([objid], [editionid], [rendition]) ;
ALTER TABLE [smart_objectrenditions] ADD DEFAULT ('0') FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_pages', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_pages DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_pages ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_pages', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_pages DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_pages].[obpaed_pages] ;
ALTER TABLE smart_pages ALTER COLUMN   [objid] bigint NOT NULL ;
CREATE  INDEX [obpaed_pages] ON [smart_pages]([objid], [pageorder], [edition]) ;
ALTER TABLE [smart_pages] ADD DEFAULT ('0') FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_placements', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_placements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_placements ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_placements', @columnName = 'parent', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_placements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_placements].[pachty_placements] ;
ALTER TABLE smart_placements ALTER COLUMN   [parent] bigint NOT NULL ;
CREATE  INDEX [pachty_placements] ON [smart_placements]([parent], [child], [type]) ;
ALTER TABLE [smart_placements] ADD DEFAULT ('0') FOR [parent];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_placements', @columnName = 'child', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_placements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_placements].[pachty_placements] ;
DROP INDEX [smart_placements].[chty_placements] ;
ALTER TABLE smart_placements ALTER COLUMN   [child] bigint NOT NULL ;
CREATE  INDEX [pachty_placements] ON [smart_placements]([parent], [child], [type]) ;
CREATE  INDEX [chty_placements] ON [smart_placements]([child], [type]) ;
ALTER TABLE [smart_placements] ADD DEFAULT ('0') FOR [child];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_elements', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_elements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_elements ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_elements', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_elements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_elements].[oigu_elements] ;
ALTER TABLE smart_elements ALTER COLUMN   [objid] bigint NOT NULL ;
CREATE  INDEX [oigu_elements] ON [smart_elements]([objid], [guid]) ;
ALTER TABLE [smart_elements] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignarticles', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignarticles DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_indesignarticles DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_indesignarticles')))
EXEC (@SQL1);
ALTER TABLE smart_indesignarticles ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_indesignarticles] ADD PRIMARY KEY ([objid], [artuid]);
ALTER TABLE [smart_indesignarticles] ADD DEFAULT (0) FOR [objid];
ALTER TABLE [smart_idarticlesplacements] ADD 
  [code] int NOT NULL  default '0';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_idarticlesplacements', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_idarticlesplacements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_idarticlesplacements DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_idarticlesplacements')))
EXEC (@SQL1);
ALTER TABLE smart_idarticlesplacements ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_idarticlesplacements] ADD PRIMARY KEY ([objid], [artuid], [plcid]);
ALTER TABLE [smart_idarticlesplacements] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_idarticlesplacements', @columnName = 'plcid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_idarticlesplacements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_idarticlesplacements DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_idarticlesplacements')))
EXEC (@SQL1);
ALTER TABLE smart_idarticlesplacements ALTER COLUMN   [plcid] bigint NOT NULL ;
ALTER TABLE [smart_idarticlesplacements] ADD PRIMARY KEY ([objid], [artuid], [plcid]);
ALTER TABLE [smart_idarticlesplacements] ADD DEFAULT (0) FOR [plcid];
CREATE  INDEX [plcid_idarticlesplacements] ON [smart_idarticlesplacements]([plcid]) ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectoperations', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectoperations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectoperations ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectoperations', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectoperations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_objectoperations].[objid_objectoperations] ;
ALTER TABLE smart_objectoperations ALTER COLUMN   [objid] bigint NOT NULL ;
CREATE  INDEX [objid_objectoperations] ON [smart_objectoperations]([objid]) ;
ALTER TABLE [smart_objectoperations] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_properties', @columnName = 'templateid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_properties DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_properties ALTER COLUMN   [templateid] bigint NOT NULL ;
ALTER TABLE [smart_properties] ADD DEFAULT (0) FOR [templateid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publobjects', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_publobjects]') AND is_unique_constraint = 1 AND name = N'puisobgr_publobjects' ) ALTER TABLE [smart_publobjects] DROP CONSTRAINT [puisobgr_publobjects] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_publobjects]')
										AND is_unique_constraint = 0 AND name = N'puisobgr_publobjects' ) DROP INDEX [smart_publobjects].[puisobgr_publobjects] ;
ALTER TABLE smart_publobjects ALTER COLUMN   [objectid] bigint NOT NULL ;
CREATE UNIQUE INDEX [puisobgr_publobjects] ON [smart_publobjects]([publicationid], [issueid], [objectid], [grpid]) ;
ALTER TABLE [smart_publobjects] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_settings', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_settings DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_settings ALTER COLUMN   [id] bigint NOT NULL ;
CREATE  INDEX [cost_states] ON [smart_states]([code], [state]) ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_tickets', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_tickets DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_tickets ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_terms', @columnName = 'entityid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_terms DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_terms DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_terms')))
EXEC (@SQL1);
DROP INDEX [smart_terms].[tm_entityid] ;
DROP INDEX [smart_terms].[tm_normalizedname] ;
ALTER TABLE smart_terms ALTER COLUMN   [entityid] bigint NOT NULL ;
ALTER TABLE [smart_terms] ADD PRIMARY KEY ([entityid], [displayname]);
CREATE  INDEX [tm_entityid] ON [smart_terms]([entityid]) ;
CREATE  INDEX [tm_normalizedname] ON [smart_terms]([entityid], [normalizedname]) ;
ALTER TABLE [smart_terms] ADD DEFAULT ('0') FOR [entityid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_mtpsentobjects', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_mtpsentobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_mtpsentobjects DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_mtpsentobjects')))
EXEC (@SQL1);
ALTER TABLE smart_mtpsentobjects ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_mtpsentobjects] ADD PRIMARY KEY ([objid], [publid], [issueid], [laytriggerstate], [printstate]);
ALTER TABLE [smart_mtpsentobjects] ADD DEFAULT ('0') FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_messagelog', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_messagelog DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_messagelog].[oimtpa_messagelog] ;
DROP INDEX [smart_messagelog].[oimtd_messagelog] ;
ALTER TABLE smart_messagelog ALTER COLUMN   [objid] bigint NOT NULL ;
CREATE  INDEX [oimtpa_messagelog] ON [smart_messagelog]([objid], [messagetype], [page]) ;
CREATE  INDEX [oimtd_messagelog] ON [smart_messagelog]([objid], [messagetypedetail]) ;
ALTER TABLE [smart_messagelog] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectflags', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectflags DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_objectflags DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_objectflags')))
EXEC (@SQL1);
ALTER TABLE smart_objectflags ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_objectflags] ADD PRIMARY KEY ([objid], [flagorigin], [flag]);
ALTER TABLE [smart_objectflags] ADD DEFAULT ('0') FOR [objid];

CREATE TABLE [smart_featureaccess] (
  [featurename] varchar(255) NOT NULL  default '',
  [featureid] int NOT NULL  default '0',
  [accessflag] int NOT NULL  default '0',
  PRIMARY KEY ([featurename])
);
CREATE UNIQUE INDEX [faid_profiles] ON [smart_featureaccess]([featureid]) ;
CREATE  INDEX [fafl_profiles] ON [smart_featureaccess]([accessflag]) ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_appsessions', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_appsessions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_appsessions ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_appsessions', @columnName = 'articleid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_appsessions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_appsessions ALTER COLUMN   [articleid] bigint NOT NULL ;
ALTER TABLE [smart_appsessions] ADD DEFAULT (0) FOR [articleid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_appsessions', @columnName = 'templateid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_appsessions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_appsessions ALTER COLUMN   [templateid] bigint NOT NULL ;
ALTER TABLE [smart_appsessions] ADD DEFAULT (0) FOR [templateid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_appsessions', @columnName = 'layoutid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_appsessions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_appsessions ALTER COLUMN   [layoutid] bigint NOT NULL ;
ALTER TABLE [smart_appsessions] ADD DEFAULT (0) FOR [layoutid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_dsqueryplacements', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_dsqueryplacements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_dsqueryplacements].[objid_dsqueryplacements] ;
ALTER TABLE smart_dsqueryplacements ALTER COLUMN   [objectid] bigint NOT NULL ;
CREATE  INDEX [objid_dsqueryplacements] ON [smart_dsqueryplacements]([objectid]) ;
ALTER TABLE [smart_dsqueryplacements] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_dsobjupdates', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_dsobjupdates DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_dsobjupdates ALTER COLUMN   [objectid] bigint NOT NULL ;
ALTER TABLE [smart_dsobjupdates] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targets', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targets DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]') AND is_unique_constraint = 1 AND name = N'obrobid_targets' ) ALTER TABLE [smart_targets] DROP CONSTRAINT [obrobid_targets] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]')
										AND is_unique_constraint = 0 AND name = N'obrobid_targets' ) DROP INDEX [smart_targets].[obrobid_targets] ;
ALTER TABLE smart_targets ALTER COLUMN   [id] bigint NOT NULL ;
CREATE UNIQUE INDEX [obrobid_targets] ON [smart_targets]([objectrelationid], [objectid], [id]) ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targets', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targets DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]') AND is_unique_constraint = 1 AND name = N'obchisobr_targets' ) ALTER TABLE [smart_targets] DROP CONSTRAINT [obchisobr_targets] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]')
										AND is_unique_constraint = 0 AND name = N'obchisobr_targets' ) DROP INDEX [smart_targets].[obchisobr_targets] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]') AND is_unique_constraint = 1 AND name = N'obrobid_targets' ) ALTER TABLE [smart_targets] DROP CONSTRAINT [obrobid_targets] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]')
										AND is_unique_constraint = 0 AND name = N'obrobid_targets' ) DROP INDEX [smart_targets].[obrobid_targets] ;
ALTER TABLE smart_targets ALTER COLUMN   [objectid] bigint NOT NULL ;
CREATE UNIQUE INDEX [obchisobr_targets] ON [smart_targets]([objectid], [channelid], [issueid], [objectrelationid]) ;
CREATE UNIQUE INDEX [obrobid_targets] ON [smart_targets]([objectrelationid], [objectid], [id]) ;
ALTER TABLE [smart_targets] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targets', @columnName = 'objectrelationid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targets DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]') AND is_unique_constraint = 1 AND name = N'obchisobr_targets' ) ALTER TABLE [smart_targets] DROP CONSTRAINT [obchisobr_targets] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]')
										AND is_unique_constraint = 0 AND name = N'obchisobr_targets' ) DROP INDEX [smart_targets].[obchisobr_targets] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]') AND is_unique_constraint = 1 AND name = N'obrobid_targets' ) ALTER TABLE [smart_targets] DROP CONSTRAINT [obrobid_targets] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targets]')
										AND is_unique_constraint = 0 AND name = N'obrobid_targets' ) DROP INDEX [smart_targets].[obrobid_targets] ;
ALTER TABLE smart_targets ALTER COLUMN   [objectrelationid] bigint NOT NULL ;
CREATE UNIQUE INDEX [obchisobr_targets] ON [smart_targets]([objectid], [channelid], [issueid], [objectrelationid]) ;
CREATE UNIQUE INDEX [obrobid_targets] ON [smart_targets]([objectrelationid], [objectid], [id]) ;
ALTER TABLE [smart_targets] ADD DEFAULT ('0') FOR [objectrelationid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishhistory', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishhistory DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_publishhistory ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishhistory', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishhistory DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_publishhistory].[obchis_publhist] ;
ALTER TABLE smart_publishhistory ALTER COLUMN   [objectid] bigint NOT NULL ;
CREATE  INDEX [obchis_publhist] ON [smart_publishhistory]([objectid], [channelid], [issueid]) ;
ALTER TABLE [smart_publishhistory] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_pubpublishedissues', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_pubpublishedissues DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_pubpublishedissues ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishedobjectshist', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishedobjectshist DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_publishedobjectshist ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishedobjectshist', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishedobjectshist DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_publishedobjectshist].[obpu_publobjhist] ;
DROP INDEX [smart_publishedobjectshist].[puob_publobjhist] ;
ALTER TABLE smart_publishedobjectshist ALTER COLUMN   [objectid] bigint NOT NULL ;
CREATE  INDEX [obpu_publobjhist] ON [smart_publishedobjectshist]([objectid], [publishid]) ;
CREATE  INDEX [puob_publobjhist] ON [smart_publishedobjectshist]([publishid], [objectid]) ;
ALTER TABLE [smart_publishedobjectshist] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishedobjectshist', @columnName = 'publishid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishedobjectshist DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_publishedobjectshist].[obpu_publobjhist] ;
DROP INDEX [smart_publishedobjectshist].[puob_publobjhist] ;
ALTER TABLE smart_publishedobjectshist ALTER COLUMN   [publishid] bigint NOT NULL ;
CREATE  INDEX [obpu_publobjhist] ON [smart_publishedobjectshist]([objectid], [publishid]) ;
CREATE  INDEX [puob_publobjhist] ON [smart_publishedobjectshist]([publishid], [objectid]) ;
ALTER TABLE [smart_publishedobjectshist] ADD DEFAULT ('0') FOR [publishid];

CREATE TABLE [smart_publishedplcmtshist] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objectid] bigint NOT NULL  default '0',
  [publishid] bigint NOT NULL  default '0',
  [majorversion] int NOT NULL  default '0',
  [minorversion] int NOT NULL  default '0',
  [externalid] varchar(200) NOT NULL  default '',
  [placementhash] varchar(64) NOT NULL ,
  PRIMARY KEY ([id])
);
CREATE  INDEX [obpu_publplchist] ON [smart_publishedplcmtshist]([objectid], [publishid]) ;
CREATE  INDEX [puob_publplchist] ON [smart_publishedplcmtshist]([publishid], [objectid]) ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targeteditions', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targeteditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_targeteditions ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targeteditions', @columnName = 'targetid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targeteditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targeteditions]') AND is_unique_constraint = 1 AND name = N'taed_targeteditions' ) ALTER TABLE [smart_targeteditions] DROP CONSTRAINT [taed_targeteditions] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targeteditions]')
										AND is_unique_constraint = 0 AND name = N'taed_targeteditions' ) DROP INDEX [smart_targeteditions].[taed_targeteditions] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targeteditions]') AND is_unique_constraint = 1 AND name = N'edta_targeteditions' ) ALTER TABLE [smart_targeteditions] DROP CONSTRAINT [edta_targeteditions] ;
IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'[smart_targeteditions]')
										AND is_unique_constraint = 0 AND name = N'edta_targeteditions' ) DROP INDEX [smart_targeteditions].[edta_targeteditions] ;
ALTER TABLE smart_targeteditions ALTER COLUMN   [targetid] bigint NOT NULL ;
CREATE UNIQUE INDEX [taed_targeteditions] ON [smart_targeteditions]([targetid], [editionid]) ;
CREATE UNIQUE INDEX [edta_targeteditions] ON [smart_targeteditions]([editionid], [targetid]) ;
ALTER TABLE [smart_targeteditions] ADD DEFAULT ('0') FOR [targetid];
ALTER TABLE [smart_indesignserverjobs] ADD 
  [pickuptime] varchar(30) NOT NULL  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignserverjobs', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignserverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_indesignserverjobs].[objid_indesignserverjobs] ;
ALTER TABLE smart_indesignserverjobs ALTER COLUMN   [objid] bigint NOT NULL ;
CREATE  INDEX [objid_indesignserverjobs] ON [smart_indesignserverjobs]([objid]) ;
ALTER TABLE [smart_indesignserverjobs] ADD DEFAULT (0) FOR [objid];
ALTER TABLE [smart_serverplugins] ADD 
  [dbprefix] varchar(10) NOT NULL  default '',
  [dbversion] varchar(10) NOT NULL  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_semaphores', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_semaphores DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_semaphores ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_placementtiles', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_placementtiles DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_placementtiles ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_placementtiles', @columnName = 'placementid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_placementtiles DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_placementtiles].[pi_placementtiles] ;
ALTER TABLE smart_placementtiles ALTER COLUMN   [placementid] bigint NOT NULL ;
CREATE  INDEX [pi_placementtiles] ON [smart_placementtiles]([placementid]) ;
ALTER TABLE [smart_placementtiles] ADD DEFAULT ('0') FOR [placementid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectlabels', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectlabels DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectlabels ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectlabels', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectlabels DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DROP INDEX [smart_objectlabels].[objlabels_objid] ;
ALTER TABLE smart_objectlabels ALTER COLUMN   [objid] bigint NOT NULL ;
CREATE  INDEX [objlabels_objid] ON [smart_objectlabels]([objid]) ;
ALTER TABLE [smart_objectlabels] ADD DEFAULT ('0') FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelationlabels', @columnName = 'labelid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelationlabels DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_objectrelationlabels DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_objectrelationlabels')))
EXEC (@SQL1);
ALTER TABLE smart_objectrelationlabels ALTER COLUMN   [labelid] bigint NOT NULL ;
ALTER TABLE [smart_objectrelationlabels] ADD PRIMARY KEY ([labelid], [childobjid]);
ALTER TABLE [smart_objectrelationlabels] ADD DEFAULT ('0') FOR [labelid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelationlabels', @columnName = 'childobjid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelationlabels DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_objectrelationlabels DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_objectrelationlabels')))
EXEC (@SQL1);
DROP INDEX [smart_objectrelationlabels].[objrellabels_childobjid] ;
ALTER TABLE smart_objectrelationlabels ALTER COLUMN   [childobjid] bigint NOT NULL ;
ALTER TABLE [smart_objectrelationlabels] ADD PRIMARY KEY ([labelid], [childobjid]);
CREATE  INDEX [objrellabels_childobjid] ON [smart_objectrelationlabels]([childobjid]) ;
ALTER TABLE [smart_objectrelationlabels] ADD DEFAULT ('0') FOR [childobjid];
DROP PROCEDURE [dbo].[SCE_GetConstraintName];
UPDATE [smart_config] set [value] = '10.3' where [name] = 'version';
