ALTER TABLE [smart_actionproperties] ADD 
  [parentfieldid] int NOT NULL  default '0',
  [documentid] varchar(512) NOT NULL  default '',
  [initialheight] int NOT NULL  default '0',
  [multipleobjects] char(2) NOT NULL  default '';
CREATE PROCEDURE [dbo].[SCE_GetConstraintName] ( @tablename sysname, @columnName sysname, @constraintName sysname OUTPUT ) AS
SELECT @constraintName = o1.name FROM sysobjects o1
INNER JOIN sysobjects o2 ON o1.parent_obj = o2.id
INNER JOIN syscolumns c ON (o1.id = c.cdefault) OR (c.id = o2.id and c.cdefault = 0 and o1.xtype = 'PK')
WHERE (o2.name = @tablename) AND (c.name = @columnName);
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_actionproperties', @columnName = 'orderid', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_actionproperties DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_actionproperties ALTER COLUMN   [orderid] int NOT NULL ;
ALTER TABLE [smart_actionproperties] ADD DEFAULT ('0') FOR [orderid];
ALTER TABLE [smart_authorizations] ADD 
  [bundle] int NOT NULL  default '0';
ALTER TABLE [smart_deletedobjects] ADD 
  [orientation] tinyint NOT NULL  default '0';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_deletedobjects', @columnName = 'dpi', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_deletedobjects DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_deletedobjects ALTER COLUMN   [dpi] real NOT NULL ;
ALTER TABLE [smart_deletedobjects] ADD DEFAULT ('0') FOR [dpi];
ALTER TABLE [smart_objectrelations] ADD 
  [parenttype] varchar(20) NOT NULL  default '';
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
ALTER TABLE [smart_placements] ADD 
  [formwidgetid] varchar(200) NOT NULL  default '',
  [frametype] varchar(20) NOT NULL  default '',
  [splineid] varchar(200) NOT NULL  default '';

CREATE TABLE [smart_indesignarticles] (
  [objid] int NOT NULL  default 0,
  [artuid] varchar(40) NOT NULL  default '',
  [name] varchar(200) NOT NULL  default '',
  [code] int NOT NULL  default '0',
  PRIMARY KEY ([objid], [artuid])
);

CREATE TABLE [smart_idarticlesplacements] (
  [objid] int NOT NULL  default 0,
  [artuid] varchar(40) NOT NULL  default '',
  [plcid] int NOT NULL  default 0,
  PRIMARY KEY ([objid], [artuid], [plcid])
);

CREATE TABLE [smart_objectoperations] (
  [id] int NOT NULL  IDENTITY(1,1),
  [objid] int NOT NULL  default 0,
  [guid] varchar(40) NOT NULL  default '',
  [type] varchar(200) NOT NULL  default '',
  [name] varchar(200) NOT NULL  default '',
  [params] text NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [objid_objectoperations] ON [smart_objectoperations]([objid]) ;
ALTER TABLE [smart_properties] ADD 
  [adminui] varchar(2) NOT NULL  default 'on',
  [propertyvalues] text NOT NULL  default '',
  [minresolution] varchar(200) NOT NULL  default '',
  [maxresolution] varchar(200) NOT NULL  default '',
  [publishsystem] varchar(64) NOT NULL  default '',
  [templateid] int NOT NULL  default 0,
  [termentityid] int NOT NULL  default '0',
  [suggestionentity] varchar(200) NOT NULL  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_properties', @columnName = 'maxlen', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_properties DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_properties ALTER COLUMN   [maxlen] bigint NOT NULL ;
ALTER TABLE [smart_properties] ADD DEFAULT ('0') FOR [maxlen];
ALTER TABLE [smart_publications] ADD 
  [calculatedeadlines] char(2) NOT NULL  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_routing', @columnName = 'routeto', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_routing DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_routing ALTER COLUMN   [routeto] varchar(255) NOT NULL ;
ALTER TABLE [smart_routing] ADD DEFAULT ('') FOR [routeto];
ALTER TABLE [smart_states] ADD 
  [readyforpublishing] char(2) NOT NULL  default '',
  [phase] varchar(40) NOT NULL  default 'Production',
  [skipidsa] char(2) NOT NULL  default '';
ALTER TABLE [smart_tickets] ADD 
  [masterticketid] varchar(40) NOT NULL  default '';
CREATE  INDEX [mtid_tickets] ON [smart_tickets]([masterticketid]) ;

CREATE TABLE [smart_termentities] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(255) NOT NULL  default '',
  [provider] varchar(40) NOT NULL  default '',
  [publishsystemid] varchar(40) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [te_name] ON [smart_termentities]([name]) ;
CREATE  INDEX [te_provider] ON [smart_termentities]([provider]) ;
CREATE  INDEX [te_termentity] ON [smart_termentities]([name], [provider]) ;

CREATE TABLE [smart_terms] (
  [entityid] int NOT NULL  default '0',
  [displayname] varchar(255) NOT NULL  default '',
  [normalizedname] varchar(255) NOT NULL  default '',
  [ligatures] varchar(255) NOT NULL  default '',
  PRIMARY KEY ([entityid], [displayname])
);
CREATE  INDEX [tm_entityid] ON [smart_terms]([entityid]) ;
CREATE  INDEX [tm_normalizedname] ON [smart_terms]([entityid], [normalizedname]) ;
ALTER TABLE [smart_users] ADD 
  [importonlogon] char(2) NOT NULL  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_users', @columnName = 'pass', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_users DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_users ALTER COLUMN   [pass] varchar(128) NOT NULL ;
ALTER TABLE [smart_users] ADD DEFAULT ('') FOR [pass];
ALTER TABLE [smart_channels] ADD 
  [suggestionprovider] varchar(64) NOT NULL  default '',
  [publishsystemid] varchar(40) NOT NULL  default '';
ALTER TABLE [smart_issues] ADD 
  [calculatedeadlines] char(2) NOT NULL  default '';
ALTER TABLE [smart_publishhistory] ADD 
  [user] varchar(255) NOT NULL  default '';
ALTER TABLE [smart_publishedobjectshist] ADD 
  [objectname] varchar(255) NOT NULL  default '',
  [objecttype] varchar(40) NOT NULL  default '',
  [objectformat] varchar(128) NOT NULL  default '';

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
ALTER TABLE [smart_indesignservers] ADD 
  [prio1] char(2) NOT NULL  default 'on',
  [prio2] char(2) NOT NULL  default 'on',
  [prio3] char(2) NOT NULL  default 'on',
  [prio4] char(2) NOT NULL  default 'on',
  [prio5] char(2) NOT NULL  default 'on',
  [locktoken] varchar(40) NOT NULL  default '';
ALTER TABLE [smart_indesignserverjobs] ADD 
  [jobid] varchar(40) NOT NULL  default '',
  [objectmajorversion] int NOT NULL  default '0',
  [objectminorversion] int NOT NULL  default '0',
  [locktoken] varchar(40) NOT NULL  default '',
  [jobstatus] int NOT NULL  default 0,
  [jobcondition] int NOT NULL  default 0,
  [jobprogress] int NOT NULL  default 0,
  [attempts] int NOT NULL  default 0,
  [pickuptime] varchar(30) NOT NULL  default '',
  [maxservermajorversion] int NOT NULL  default '0',
  [maxserverminorversion] int NOT NULL  default '0',
  [prio] int NOT NULL  default '3',
  [ticketseal] varchar(40) NOT NULL  default '',
  [ticket] varchar(40) NOT NULL  default '',
  [actinguser] varchar(40) NOT NULL  default '',
  [initiator] varchar(40) NOT NULL  default '',
  [servicename] varchar(32) NOT NULL  default '',
  [context] varchar(64) NOT NULL  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_indesignserverjobs', @columnName = 'errormessage', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_indesignserverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_indesignserverjobs ALTER COLUMN   [errormessage] varchar(1024) NOT NULL ;
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
CREATE  INDEX [objid_indesignserverjobs] ON [smart_indesignserverjobs]([objid]) ;
CREATE  INDEX [prid_indesignserverjobs] ON [smart_indesignserverjobs]([prio], [jobid]) ;
CREATE  INDEX [ts_indesignserverjobs] ON [smart_indesignserverjobs]([ticketseal]) ;
CREATE  INDEX [ttjtstrt_indesignserverjobs] ON [smart_indesignserverjobs]([ticket], [jobtype], [starttime], [readytime]) ;
CREATE  INDEX [jp_indesignserverjobs] ON [smart_indesignserverjobs]([jobprogress]) ;
CREATE  INDEX [jspr_indesignserverjobs] ON [smart_indesignserverjobs]([jobstatus], [prio], [queuetime]) ;
CREATE  INDEX [lt_indesignserverjobs] ON [smart_indesignserverjobs]([locktoken]) ;
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
  [jobid] varchar(40) NOT NULL  default '',
  [attempts] int NOT NULL  default 0,
  [errormessage] varchar(1024) NOT NULL  default '',
  [jobdata] text NOT NULL  default '',
  [dataentity] varchar(20) NOT NULL  default '';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_serverjobs', @columnName = 'queuetime', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_serverjobs DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_serverjobs ALTER COLUMN   [queuetime] varchar(30) NOT NULL ;
ALTER TABLE [smart_serverjobs] ADD DEFAULT ('') FOR [queuetime];
CREATE  INDEX [jobinfo] ON [smart_serverjobs]([locktoken], [jobstatus], [jobprogress]) ;
CREATE  INDEX [aslt_serverjobs] ON [smart_serverjobs]([assignedserverid], [locktoken]) ;
CREATE  INDEX [paged_results] ON [smart_serverjobs]([queuetime], [servertype], [jobtype], [jobstatus], [actinguser]) ;
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
  [guid] varchar(40) NOT NULL  default '',
  [jobtype] varchar(32) NOT NULL  default '',
  [retrytimestamp] varchar(20) NOT NULL  default '',
  PRIMARY KEY ([guid])
);
CREATE  INDEX [jobtype] ON [smart_serverjobtypesonhold]([jobtype]) ;
CREATE  INDEX [retrytime] ON [smart_serverjobtypesonhold]([retrytimestamp]) ;
ALTER TABLE [smart_serverjobconfigs] ADD 
  [userconfigneeded] char(1) NOT NULL  default 'Y',
  [selfdestructive] char(1) NOT NULL  default 'N';
ALTER TABLE [smart_serverplugins] ADD 
  [dbprefix] varchar(10) NOT NULL  default '',
  [dbversion] varchar(10) NOT NULL  default '';
ALTER TABLE [smart_semaphores] ADD 
  [lifetime] int NOT NULL  default '0';

CREATE TABLE [smart_objectlabels] (
  [id] int NOT NULL  IDENTITY(1,1),
  [objid] int NOT NULL  default '0',
  [name] varchar(250) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [objlabels_objid] ON [smart_objectlabels]([objid]) ;

CREATE TABLE [smart_objectrelationlabels] (
  [labelid] int NOT NULL  default '0',
  [childobjid] int NOT NULL  default '0',
  PRIMARY KEY ([labelid], [childobjid])
);
CREATE  INDEX [objrellabels_childobjid] ON [smart_objectrelationlabels]([childobjid]) ;
ALTER TABLE [smart_channeldata] ADD 
  [publication] int NOT NULL  default '0',
  [pubchannel] int NOT NULL  default '0';
DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = 'ALTER TABLE smart_channeldata DROP CONSTRAINT |ConstraintName|'
SET @SQL1 = REPLACE(@SQL1, '|ConstraintName|', ( SELECT name FROM sysobjects WHERE xtype = 'PK' AND parent_obj = OBJECT_ID('smart_channeldata')))
EXEC (@SQL1);
ALTER TABLE [smart_channeldata] ADD PRIMARY KEY ([publication], [pubchannel], [issue], [section], [name]);
DROP PROCEDURE [dbo].[SCE_GetConstraintName];
UPDATE [smart_config] set [value] = '10.2' where [name] = 'version';
