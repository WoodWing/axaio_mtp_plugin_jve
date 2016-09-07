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
ALTER TABLE [smart_states] ADD 
  [skipidsa] char(2) not null  default '';
ALTER TABLE [smart_tickets] ADD 
  [masterticketid] varchar(40) not null  default '';
CREATE  INDEX [mtid_tickets] on [smart_tickets]([masterticketid]) ;

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
  [errormessage] varchar(1024) not null  default '';
ALTER TABLE [smart_semaphores] ADD 
  [lifetime] int not null  default '0';
DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)
EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = 'smart_objectlabels', @columnName = 'name', @constraintName = @constraintName OUTPUT
SET @sql = 'ALTER TABLE smart_objectlabels DROP CONSTRAINT ' + @constraintName
EXEC (@sql);
ALTER TABLE smart_objectlabels ALTER COLUMN   [name] varchar(250) not null ;
ALTER TABLE [smart_objectlabels] ADD DEFAULT ('') FOR [name];
DROP PROCEDURE [dbo].[SCE_GetConstraintName];
UPDATE [smart_config] set [value] = '10.1' where [name] = 'version';
