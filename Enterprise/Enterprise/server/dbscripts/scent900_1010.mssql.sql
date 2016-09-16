ALTER TABLE [smart_actionproperties] ADD 
  [multipleobjects] char(2) not null  default '';
CREATE PROCEDURE [dbo].[SCE_GetConstraintName] ( @tablename sysname, @columnName sysname, @constraintName sysname OUTPUT ) AS
SELECT @constraintName = o1.name FROM sysobjects o1
INNER JOIN sysobjects o2 ON o1.parent_obj = o2.id
INNER JOIN syscolumns c ON (o1.id = c.cdefault) OR (c.id = o2.id and c.cdefault = 0 and o1.xtype = 'PK')
WHERE (o2.name = @tablename) AND (c.name = @columnName);
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_actionproperties', @columnName = 'orderid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_actionproperties DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_actionproperties ALTER COLUMN   [orderid] int not null ;
ALTER TABLE [smart_actionproperties] ADD DEFAULT ('0') FOR [orderid];
ALTER TABLE [smart_authorizations] ADD 
  [bundle] int not null  default '0';
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
ALTER TABLE [smart_placements] ADD 
  [frametype] varchar(20) not null  default '',
  [splineid] varchar(200) not null  default '';

CREATE TABLE [smart_indesignarticles] (
  [objid] int not null  default 0,
  [artuid] varchar(40) not null  default '',
  [name] varchar(200) not null  default '',
  [code] int not null  default '0',
  PRIMARY KEY ([objid], [artuid])
);

CREATE TABLE [smart_idarticlesplacements] (
  [objid] int not null  default 0,
  [artuid] varchar(40) not null  default '',
  [plcid] int not null  default 0,
  PRIMARY KEY ([objid], [artuid], [plcid])
);

CREATE TABLE [smart_objectoperations] (
  [id] int not null  IDENTITY(1,1),
  [objid] int not null  default 0,
  [guid] varchar(40) not null  default '',
  [type] varchar(200) not null  default '',
  [name] varchar(200) not null  default '',
  [params] text not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [objid_objectoperations] on [smart_objectoperations]([objid]) ;
ALTER TABLE [smart_properties] ADD 
  [termentityid] int not null  default '0',
  [suggestionentity] varchar(200) not null  default '';
ALTER TABLE [smart_publications] ADD 
  [calculatedeadlines] char(2) not null  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_routing', @columnName = 'routeto', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_routing DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_routing ALTER COLUMN   [routeto] varchar(255) not null ;
ALTER TABLE [smart_routing] ADD DEFAULT ('') FOR [routeto];
ALTER TABLE [smart_states] ADD 
  [phase] varchar(40) not null  default 'Production',
  [skipidsa] char(2) not null  default '';
ALTER TABLE [smart_tickets] ADD 
  [masterticketid] varchar(40) not null  default '';
CREATE  INDEX [mtid_tickets] on [smart_tickets]([masterticketid]) ;

CREATE TABLE [smart_termentities] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(255) not null  default '',
  [provider] varchar(40) not null  default '',
  [publishsystemid] varchar(40) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [te_name] on [smart_termentities]([name]) ;
CREATE  INDEX [te_provider] on [smart_termentities]([provider]) ;
CREATE  INDEX [te_termentity] on [smart_termentities]([name], [provider]) ;

CREATE TABLE [smart_terms] (
  [entityid] int not null  default '0',
  [displayname] varchar(255) not null  default '',
  [normalizedname] varchar(255) not null  default '',
  [ligatures] varchar(255) not null  default '',
  PRIMARY KEY ([entityid], [displayname])
);
CREATE  INDEX [tm_entityid] on [smart_terms]([entityid]) ;
CREATE  INDEX [tm_normalizedname] on [smart_terms]([entityid], [normalizedname]) ;
ALTER TABLE [smart_users] ADD 
  [importonlogon] char(2) not null  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_users', @columnName = 'pass', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_users DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_users ALTER COLUMN   [pass] varchar(128) not null ;
ALTER TABLE [smart_users] ADD DEFAULT ('') FOR [pass];
ALTER TABLE [smart_channels] ADD 
  [suggestionprovider] varchar(64) not null  default '',
  [publishsystemid] varchar(40) not null  default '';
ALTER TABLE [smart_issues] ADD 
  [calculatedeadlines] char(2) not null  default '';
ALTER TABLE [smart_publishhistory] ADD 
  [user] varchar(255) not null  default '';
ALTER TABLE [smart_publishedobjectshist] ADD 
  [objectname] varchar(255) not null  default '',
  [objecttype] varchar(40) not null  default '',
  [objectformat] varchar(128) not null  default '';

CREATE TABLE [smart_publishedplcmtshist] (
  [id] int not null  IDENTITY(1,1),
  [objectid] int not null  default '0',
  [publishid] int not null  default '0',
  [majorversion] int not null  default '0',
  [minorversion] int not null  default '0',
  [externalid] varchar(200) not null  default '',
  [placementhash] varchar(64) not null ,
  PRIMARY KEY ([id])
);
CREATE  INDEX [obpu_publplchist] on [smart_publishedplcmtshist]([objectid], [publishid]) ;
CREATE  INDEX [puob_publplchist] on [smart_publishedplcmtshist]([publishid], [objectid]) ;
ALTER TABLE [smart_indesignservers] ADD 
  [prio1] char(2) not null  default 'on',
  [prio2] char(2) not null  default 'on',
  [prio3] char(2) not null  default 'on',
  [prio4] char(2) not null  default 'on',
  [prio5] char(2) not null  default 'on',
  [locktoken] varchar(40) not null  default '';
ALTER TABLE [smart_indesignserverjobs] ADD 
  [jobid] varchar(40) not null  default '',
  [objectmajorversion] int not null  default '0',
  [objectminorversion] int not null  default '0',
  [locktoken] varchar(40) not null  default '',
  [jobstatus] int not null  default 0,
  [jobcondition] int not null  default 0,
  [jobprogress] int not null  default 0,
  [attempts] int not null  default 0,
  [maxservermajorversion] int not null  default '0',
  [maxserverminorversion] int not null  default '0',
  [prio] int not null  default '3',
  [ticketseal] varchar(40) not null  default '',
  [ticket] varchar(40) not null  default '',
  [actinguser] varchar(40) not null  default '',
  [initiator] varchar(40) not null  default '',
  [servicename] varchar(32) not null  default '',
  [context] varchar(64) not null  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignserverjobs', @columnName = 'errormessage', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignserverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_indesignserverjobs ALTER COLUMN   [errormessage] varchar(1024) not null ;
ALTER TABLE [smart_indesignserverjobs] ADD DEFAULT ('') FOR [errormessage];
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
CREATE  INDEX [objid_indesignserverjobs] on [smart_indesignserverjobs]([objid]) ;
CREATE  INDEX [prid_indesignserverjobs] on [smart_indesignserverjobs]([prio], [jobid]) ;
CREATE  INDEX [ts_indesignserverjobs] on [smart_indesignserverjobs]([ticketseal]) ;
CREATE  INDEX [ttjtstrt_indesignserverjobs] on [smart_indesignserverjobs]([ticket], [jobtype], [starttime], [readytime]) ;
CREATE  INDEX [jp_indesignserverjobs] on [smart_indesignserverjobs]([jobprogress]) ;
CREATE  INDEX [jspr_indesignserverjobs] on [smart_indesignserverjobs]([jobstatus], [prio], [queuetime]) ;
CREATE  INDEX [lt_indesignserverjobs] on [smart_indesignserverjobs]([locktoken]) ;
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_indesignserverjobs DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_indesignserverjobs')))
EXEC (@SQL1);
ALTER TABLE [smart_indesignserverjobs] ADD PRIMARY KEY ([jobid]);
ALTER TABLE [smart_indesignserverjobs] DROP COLUMN [id];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignserverjobs', @columnName = 'exclusivelock', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignserverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE [smart_indesignserverjobs] DROP COLUMN [exclusivelock];
ALTER TABLE [smart_serverjobs] ADD 
  [jobid] varchar(40) not null  default '',
  [attempts] int not null  default 0,
  [errormessage] varchar(1024) not null  default '',
  [jobdata] text not null  default '',
  [dataentity] varchar(20) not null  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_serverjobs', @columnName = 'queuetime', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_serverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_serverjobs ALTER COLUMN   [queuetime] varchar(30) not null ;
ALTER TABLE [smart_serverjobs] ADD DEFAULT ('') FOR [queuetime];
CREATE  INDEX [jobinfo] on [smart_serverjobs]([locktoken], [jobstatus], [jobprogress]) ;
CREATE  INDEX [aslt_serverjobs] on [smart_serverjobs]([assignedserverid], [locktoken]) ;
CREATE  INDEX [paged_results] on [smart_serverjobs]([queuetime], [servertype], [jobtype], [jobstatus], [actinguser]) ;
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_serverjobs DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_serverjobs')))
EXEC (@SQL1);
ALTER TABLE [smart_serverjobs] ADD PRIMARY KEY ([jobid]);
ALTER TABLE [smart_serverjobs] DROP COLUMN [id];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_serverjobs', @columnName = 'objid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_serverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE [smart_serverjobs] DROP COLUMN [objid];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_serverjobs', @columnName = 'minorversion', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_serverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE [smart_serverjobs] DROP COLUMN [minorversion];
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_serverjobs', @columnName = 'majorversion', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_serverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE [smart_serverjobs] DROP COLUMN [majorversion];

CREATE TABLE [smart_serverjobtypesonhold] (
  [guid] varchar(40) not null  default '',
  [jobtype] varchar(32) not null  default '',
  [retrytimestamp] varchar(20) not null  default '',
  PRIMARY KEY ([guid])
);
CREATE  INDEX [jobtype] on [smart_serverjobtypesonhold]([jobtype]) ;
CREATE  INDEX [retrytime] on [smart_serverjobtypesonhold]([retrytimestamp]) ;
ALTER TABLE [smart_serverjobconfigs] ADD 
  [userconfigneeded] char(1) not null  default 'Y',
  [selfdestructive] char(1) not null  default 'N';
ALTER TABLE [smart_semaphores] ADD 
  [lifetime] int not null  default '0';

CREATE TABLE [smart_objectlabels] (
  [id] int not null  IDENTITY(1,1),
  [objid] int not null  default '0',
  [name] varchar(250) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [objlabels_objid] on [smart_objectlabels]([objid]) ;

CREATE TABLE [smart_objectrelationlabels] (
  [labelid] int not null  default '0',
  [childobjid] int not null  default '0',
  PRIMARY KEY ([labelid], [childobjid])
);
CREATE  INDEX [objrellabels_childobjid] on [smart_objectrelationlabels]([childobjid]) ;
DROP PROCEDURE [dbo].[SCE_GetConstraintName];
UPDATE [smart_config] set [value] = '10.1' where [name] = 'version';
