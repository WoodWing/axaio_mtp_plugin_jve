CREATE PROCEDURE [dbo].[SCE_GetConstraintName] ( @tablename sysname, @columnName sysname, @constraintName sysname OUTPUT ) AS
SELECT @constraintName = o1.name FROM sysobjects o1
INNER JOIN sysobjects o2 ON o1.parent_obj = o2.id
INNER JOIN syscolumns c ON (o1.id = c.cdefault) OR (c.id = o2.id and c.cdefault = 0 and o1.xtype = 'PK')
WHERE (o2.name = @tablename) AND (c.name = @columnName);
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_actionproperties', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_actionproperties DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_actionproperties ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_authorizations', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_authorizations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_authorizations ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_authorizations', @columnName = 'rights', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_authorizations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_authorizations ALTER COLUMN   [rights] varchar(1024) NOT NULL ;
ALTER TABLE [smart_authorizations] ADD DEFAULT ('') FOR [rights];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_config', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_config DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_config ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_deletedobjects', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_deletedobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_deletedobjects ALTER COLUMN   [id] bigint NOT NULL ;
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
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectlocks', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectlocks DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectlocks ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectlocks', @columnName = 'object', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectlocks DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectlocks ALTER COLUMN   [object] bigint NOT NULL ;
ALTER TABLE [smart_objectlocks] ADD DEFAULT ('0') FOR [object];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelations', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectrelations ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelations', @columnName = 'parent', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectrelations ALTER COLUMN   [parent] bigint NOT NULL ;
ALTER TABLE [smart_objectrelations] ADD DEFAULT ('0') FOR [parent];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelations', @columnName = 'child', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectrelations ALTER COLUMN   [child] bigint NOT NULL ;
ALTER TABLE [smart_objectrelations] ADD DEFAULT ('0') FOR [child];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objects', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objects ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectversions', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectversions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectversions ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectversions', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectversions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectversions ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_objectversions] ADD DEFAULT ('0') FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrenditions', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrenditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectrenditions ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrenditions', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrenditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectrenditions ALTER COLUMN   [objid] bigint NOT NULL ;
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
ALTER TABLE smart_pages ALTER COLUMN   [objid] bigint NOT NULL ;
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
ALTER TABLE smart_placements ALTER COLUMN   [parent] bigint NOT NULL ;
ALTER TABLE [smart_placements] ADD DEFAULT ('0') FOR [parent];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_placements', @columnName = 'child', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_placements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_placements ALTER COLUMN   [child] bigint NOT NULL ;
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
ALTER TABLE smart_elements ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_elements] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignarticles', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignarticles DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_indesignarticles ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_indesignarticles] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_idarticlesplacements', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_idarticlesplacements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_idarticlesplacements ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_idarticlesplacements] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_idarticlesplacements', @columnName = 'plcid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_idarticlesplacements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_idarticlesplacements ALTER COLUMN   [plcid] bigint NOT NULL ;
ALTER TABLE [smart_idarticlesplacements] ADD DEFAULT (0) FOR [plcid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectoperations', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectoperations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectoperations ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectoperations', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectoperations DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectoperations ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_objectoperations] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publobjects', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_publobjects ALTER COLUMN   [objectid] bigint NOT NULL ;
ALTER TABLE [smart_publobjects] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_issueeditions', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_issueeditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_issueeditions ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_settings', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_settings DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_settings ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_tickets', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_tickets DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_tickets ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_termentities', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_termentities DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_termentities ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_terms', @columnName = 'entityid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_terms DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_terms ALTER COLUMN   [entityid] bigint NOT NULL ;
ALTER TABLE [smart_terms] ADD DEFAULT ('0') FOR [entityid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_mtpsentobjects', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_mtpsentobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_mtpsentobjects ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_mtpsentobjects] ADD DEFAULT ('0') FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_messagelog', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_messagelog DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_messagelog ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_messagelog] ADD DEFAULT (0) FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectflags', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectflags DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectflags ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_objectflags] ADD DEFAULT () FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_profilefeatures', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_profilefeatures DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_profilefeatures ALTER COLUMN   [id] bigint NOT NULL ;

CREATE TABLE [smart_featureaccess] (
  [featurename] varchar(255) NOT NULL  default '',
  [featureid] int NOT NULL  default '0',
  [accessflag] varchar(4) NOT NULL  default '',
  PRIMARY KEY ([featurename])
);
CREATE UNIQUE INDEX [faid_profiles] ON [smart_featureaccess]([featureid]) ;
CREATE UNIQUE INDEX [faaf_profiles] ON [smart_featureaccess]([accessflag]) ;
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
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_appsessions', @columnName = 'layoutid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_appsessions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_appsessions ALTER COLUMN   [layoutid] bigint NOT NULL ;
ALTER TABLE [smart_appsessions] ADD DEFAULT (0) FOR [layoutid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_dsqueryplacements', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_dsqueryplacements DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_dsqueryplacements ALTER COLUMN   [objectid] bigint NOT NULL ;
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
ALTER TABLE smart_targets ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targets', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targets DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_targets ALTER COLUMN   [objectid] bigint NOT NULL ;
ALTER TABLE [smart_targets] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targets', @columnName = 'objectrelationid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targets DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_targets ALTER COLUMN   [objectrelationid] bigint NOT NULL ;
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
ALTER TABLE smart_publishhistory ALTER COLUMN   [objectid] bigint NOT NULL ;
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
ALTER TABLE smart_publishedobjectshist ALTER COLUMN   [objectid] bigint NOT NULL ;
ALTER TABLE [smart_publishedobjectshist] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishedobjectshist', @columnName = 'publishid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishedobjectshist DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_publishedobjectshist ALTER COLUMN   [publishid] bigint NOT NULL ;
ALTER TABLE [smart_publishedobjectshist] ADD DEFAULT ('0') FOR [publishid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishedplcmtshist', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishedplcmtshist DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_publishedplcmtshist ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishedplcmtshist', @columnName = 'objectid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishedplcmtshist DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_publishedplcmtshist ALTER COLUMN   [objectid] bigint NOT NULL ;
ALTER TABLE [smart_publishedplcmtshist] ADD DEFAULT ('0') FOR [objectid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_publishedplcmtshist', @columnName = 'publishid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_publishedplcmtshist DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_publishedplcmtshist ALTER COLUMN   [publishid] bigint NOT NULL ;
ALTER TABLE [smart_publishedplcmtshist] ADD DEFAULT ('0') FOR [publishid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targeteditions', @columnName = 'id', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targeteditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_targeteditions ALTER COLUMN   [id] bigint NOT NULL ;
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_targeteditions', @columnName = 'targetid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_targeteditions DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_targeteditions ALTER COLUMN   [targetid] bigint NOT NULL ;
ALTER TABLE [smart_targeteditions] ADD DEFAULT ('0') FOR [targetid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignserverjobs', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignserverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_indesignserverjobs ALTER COLUMN   [objid] bigint NOT NULL ;
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
ALTER TABLE smart_placementtiles ALTER COLUMN   [placementid] bigint NOT NULL ;
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
ALTER TABLE smart_objectlabels ALTER COLUMN   [objid] bigint NOT NULL ;
ALTER TABLE [smart_objectlabels] ADD DEFAULT ('0') FOR [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelationlabels', @columnName = 'labelid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelationlabels DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectrelationlabels ALTER COLUMN   [labelid] bigint NOT NULL ;
ALTER TABLE [smart_objectrelationlabels] ADD DEFAULT ('0') FOR [labelid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectrelationlabels', @columnName = 'childobjid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectrelationlabels DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectrelationlabels ALTER COLUMN   [childobjid] bigint NOT NULL ;
ALTER TABLE [smart_objectrelationlabels] ADD DEFAULT ('0') FOR [childobjid];
DROP PROCEDURE [dbo].[SCE_GetConstraintName];
UPDATE [smart_config] set [value] = '10.2' where [name] = 'version';
