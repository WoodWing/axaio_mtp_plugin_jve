
CREATE TABLE [smart_actionproperties] (
  [id] int NOT NULL  IDENTITY(1,1),
  [publication] int NOT NULL  default '0',
  [orderid] int NOT NULL  default '0',
  [property] varchar(200) NOT NULL  default '',
  [edit] char(2) NOT NULL  default '',
  [mandatory] char(2) NOT NULL  default '',
  [action] varchar(40) NOT NULL  default '',
  [type] varchar(40) NOT NULL  default '',
  [restricted] char(2) NOT NULL  default '',
  [refreshonchange] char(2) NOT NULL  default '',
  [parentfieldid] int NOT NULL  default '0',
  [documentid] varchar(512) NOT NULL  default '',
  [initialheight] int NOT NULL  default '0',
  [multipleobjects] char(2) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pbac_actionproperties] ON [smart_actionproperties]([publication], [action]) ;

CREATE TABLE [smart_authorizations] (
  [id] int NOT NULL  IDENTITY(1,1),
  [grpid] int NOT NULL  default '0',
  [publication] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [state] int NOT NULL  default '0',
  [rights] varchar(1024) NOT NULL  default '',
  [issue] int NOT NULL  default '0',
  [profile] int NOT NULL  default '0',
  [bundle] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [gipu_authorizations] ON [smart_authorizations]([grpid], [publication]) ;
CREATE  INDEX [gipr_authorizations] ON [smart_authorizations]([grpid], [profile]) ;
SET IDENTITY_INSERT [smart_authorizations] ON
INSERT INTO [smart_authorizations] ([id], [grpid], [publication], [section], [state], [rights], [issue], [profile], [bundle]) VALUES (1, 2, 1, 0, 0, 'VRWDCKSF', 0, 1, 0);
SET IDENTITY_INSERT [smart_authorizations] OFF

CREATE TABLE [smart_config] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(200) NOT NULL  default '',
  [value] text NOT NULL  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_config] ON
INSERT INTO [smart_config] ([id], [name], [value]) VALUES (1, 'version', '00');
SET IDENTITY_INSERT [smart_config] OFF

CREATE TABLE [smart_deletedobjects] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [documentid] varchar(512) NOT NULL  default '',
  [masterid] bigint NOT NULL  default '0',
  [type] varchar(20) NOT NULL  default '',
  [name] varchar(255) NOT NULL  default '',
  [publication] int NOT NULL  default '0',
  [issue] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [state] int NOT NULL  default '0',
  [routeto] varchar(255) NOT NULL  default '',
  [copyright] varchar(255) NOT NULL  default '',
  [slugline] varchar(255) NOT NULL  default '',
  [comment] varchar(255) NOT NULL  default '',
  [author] varchar(255) NOT NULL  default '',
  [deadline] varchar(30) NOT NULL  default '',
  [urgency] varchar(40) NOT NULL  default '',
  [format] varchar(128) NOT NULL  default '',
  [width] real NOT NULL  default '0',
  [depth] real NOT NULL  default '0',
  [dpi] real NOT NULL  default '0',
  [lengthwords] int NOT NULL  default '0',
  [lengthchars] int NOT NULL  default '0',
  [lengthparas] int NOT NULL  default '0',
  [lengthlines] int NOT NULL  default '0',
  [keywords] text NOT NULL  default '',
  [modifier] varchar(40) NOT NULL  default '',
  [modified] varchar(30) NOT NULL  default '',
  [creator] varchar(40) NOT NULL  default '',
  [created] varchar(30) NOT NULL  default '',
  [deletor] varchar(40) NOT NULL  default '',
  [deleted] varchar(30) NOT NULL  default '',
  [copyrightmarked] varchar(255) NOT NULL  default '',
  [copyrighturl] varchar(255) NOT NULL  default '',
  [credit] varchar(255) NOT NULL  default '',
  [source] varchar(255) NOT NULL  default '',
  [description] text NOT NULL  default '',
  [descriptionauthor] varchar(255) NOT NULL  default '',
  [_columns] int NOT NULL  default '0',
  [plaincontent] text NOT NULL  default '',
  [filesize] int NOT NULL  default '0',
  [colorspace] varchar(20) NOT NULL  default '',
  [pagenumber] int NOT NULL  default '0',
  [types] text NOT NULL  default '',
  [storename] text NOT NULL  default '',
  [pagerange] varchar(50) NOT NULL  default '',
  [highresfile] varchar(255) NOT NULL  default '',
  [deadlinesoft] varchar(30) NOT NULL  default '',
  [deadlinechanged] char(1) NOT NULL  default '',
  [plannedpagerange] varchar(50) NOT NULL  default '',
  [majorversion] int NOT NULL  default '-1',
  [minorversion] int NOT NULL  default '0',
  [encoding] varchar(100) NOT NULL  default '',
  [compression] varchar(100) NOT NULL  default '',
  [keyframeeveryframes] int NOT NULL  default '0',
  [channels] varchar(100) NOT NULL  default '',
  [aspectratio] varchar(100) NOT NULL  default '',
  [contentsource] varchar(100) NOT NULL  default '',
  [rating] tinyint NOT NULL  default 0,
  [indexed] char(2) NOT NULL  default '',
  [closed] char(2) NOT NULL  default '',
  [orientation] tinyint NOT NULL  default '0',
  [routetouserid] int NOT NULL  default '0',
  [routetogroupid] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_groups] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(100) NOT NULL  default '',
  [descr] varchar(255) NOT NULL  default '',
  [admin] char(2) NOT NULL  default '',
  [routing] char(2) NOT NULL  default '',
  [externalid] varchar(200) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [idnaro_groups] ON [smart_groups]([id], [name], [routing]) ;
CREATE  INDEX [na_groups] ON [smart_groups]([name]) ;
SET IDENTITY_INSERT [smart_groups] ON
INSERT INTO [smart_groups] ([id], [name], [descr], [admin], [routing], [externalid]) VALUES (2, 'admin', 'System Admins', 'on', '', '');
SET IDENTITY_INSERT [smart_groups] OFF

CREATE TABLE [smart_log] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [user] varchar(50) NOT NULL  default '',
  [service] varchar(50) NOT NULL  default '',
  [ip] varchar(30) NOT NULL  default '',
  [date] varchar(30) NOT NULL  default '',
  [objectid] bigint NOT NULL  default '0',
  [publication] int NOT NULL  default '0',
  [issue] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [state] int NOT NULL  default '0',
  [parent] bigint NOT NULL  default '0',
  [lock] varchar(1) NOT NULL  default '',
  [rendition] varchar(10) NOT NULL  default '',
  [type] varchar(20) NOT NULL  default '',
  [routeto] varchar(255) NOT NULL  default '',
  [edition] varchar(255) NOT NULL  default '',
  [minorversion] int NOT NULL  default '0',
  [channelid] int NOT NULL  default '0',
  [majorversion] int NOT NULL  default '-1',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_namedqueries] (
  [id] int NOT NULL  IDENTITY(1,1),
  [query] varchar(200) NOT NULL  default '',
  [interface] text NOT NULL  default '',
  [sql] text NOT NULL  default '',
  [comment] text NOT NULL  default '',
  [checkaccess] varchar(2) NOT NULL  default 'on',
  PRIMARY KEY ([id])
);
CREATE  INDEX [qe_namedqueries] ON [smart_namedqueries]([query]) ;
SET IDENTITY_INSERT [smart_namedqueries] ON
INSERT INTO [smart_namedqueries] ([id], [query], [interface], [sql], [comment], [checkaccess]) VALUES (2, 'Templates', '', '/*SELECT*/
select o.`id` as `ID`, o.`type` as `Type`, o.`name` as `Name`, st.`state` as `State`, ul.`fullname` as `LockedBy`, p.`publication` as `Publication`, s.`section` as `Section`, o.`comment` as `Comment`, o.`routeto` as `RouteTo`, un.`fullname` as `Creator`, o.`format` as `Format`, um.`fullname` as `Modifier`, p.`id` as `PublicationId`, s.`id` as `SectionId`, st.`id` as `StateId`, st.`color` as `StateColor`, l.`lockoffline` as `LockForOffline`
/*FROM*/
from `smart_objects` o
/*JOINS*/
left join `smart_publications` p on (o.`publication` = p.`id`)
left join `smart_publsections` s on (o.`section` = s.`id`)
left join `smart_states` st on (o.`state` = st.`id`)
left join `smart_objectlocks` l on (o.`id` = l.`object`)
left join `smart_users` ul on (l.`usr` = ul.`user`)
left join `smart_users` um on (o.`modifier` = um.`user`)
left join `smart_users` un on (o.`creator` = un.`user`)
/*WHERE*/
where (o.`type` = ''LayoutTemplate'' or o.`type` = ''ArticleTemplate'' or o.`type` = ''LayoutModuleTemplate'')', 'Shows all layout templates.', 'on');
INSERT INTO [smart_namedqueries] ([id], [query], [interface], [sql], [comment], [checkaccess]) VALUES (5, 'Libraries', '', '/*SELECT*/
select o.`id` as `ID`, o.`type` as `Type`, o.`name` as `Name`, st.`state` as `State`, ul.`fullname` as `LockedBy`, p.`publication` as `Publication`, s.`section` as `Section`, o.`comment` as `Comment`, o.`routeto` as `RouteTo`, un.`fullname` as `Creator`, o.`format` as `Format`, um.`fullname` as `Modifier`, p.`id` as `PublicationId`, s.`id` as `SectionId`, st.`id` as `StateId`, st.`color` as `StateColor`, l.`lockoffline` as `LockForOffline`
/*FROM*/
from `smart_objects` o
/*JOINS*/
left join `smart_publications` p on (o.`publication` = p.`id`)
left join `smart_publsections` s on (o.`section` = s.`id`)
left join `smart_states` st on (o.`state` = st.`id`)
left join `smart_objectlocks` l on (o.`id` = l.`object`)
left join `smart_users` ul on (l.`usr` = ul.`user`)
left join `smart_users` um on (o.`modifier` = um.`user`)
left join `smart_users` un on (o.`creator` = un.`user`)
/*WHERE*/
where (o.`type` = ''Library'')', 'Shows all libraries.', 'on');
SET IDENTITY_INSERT [smart_namedqueries] OFF

CREATE TABLE [smart_objectlocks] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [object] bigint NOT NULL  default '0',
  [usr] varchar(40) NOT NULL  default '',
  [timestamp] timestamp NOT NULL ,
  [ip] varchar(30) NOT NULL  default '',
  [lockoffline] varchar(2) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [ob_objectlocks] ON [smart_objectlocks]([object]) ;
CREATE  INDEX [obusr_objectlocks] ON [smart_objectlocks]([object], [usr]) ;

CREATE TABLE [smart_objectrelations] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [parent] bigint NOT NULL  default '0',
  [child] bigint NOT NULL  default '0',
  [type] varchar(40) NOT NULL  default '',
  [subid] varchar(20) NOT NULL  default '',
  [pagerange] varchar(50) NOT NULL  default '',
  [rating] tinyint NOT NULL  default 0,
  [parenttype] varchar(20) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [ch_objectrelations] ON [smart_objectrelations]([parent], [child], [subid], [type]) ;
CREATE  INDEX [pachty_objectrelations] ON [smart_objectrelations]([parent], [child], [type]) ;
CREATE  INDEX [child_type_id] ON [smart_objectrelations]([child], [type], [id]) ;

CREATE TABLE [smart_objects] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [documentid] varchar(512) NOT NULL  default '',
  [masterid] bigint NOT NULL  default '0',
  [type] varchar(20) NOT NULL  default '',
  [name] varchar(255) NOT NULL  default '',
  [publication] int NOT NULL  default '0',
  [issue] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [state] int NOT NULL  default '0',
  [routeto] varchar(255) NOT NULL  default '',
  [copyright] varchar(255) NOT NULL  default '',
  [slugline] varchar(255) NOT NULL  default '',
  [comment] varchar(255) NOT NULL  default '',
  [author] varchar(255) NOT NULL  default '',
  [deadline] varchar(30) NOT NULL  default '',
  [urgency] varchar(40) NOT NULL  default '',
  [format] varchar(128) NOT NULL  default '',
  [width] real NOT NULL  default '0',
  [depth] real NOT NULL  default '0',
  [dpi] real NOT NULL  default '0',
  [lengthwords] int NOT NULL  default '0',
  [lengthchars] int NOT NULL  default '0',
  [lengthparas] int NOT NULL  default '0',
  [lengthlines] int NOT NULL  default '0',
  [keywords] text NOT NULL  default '',
  [modifier] varchar(40) NOT NULL  default '',
  [modified] varchar(30) NOT NULL  default '',
  [creator] varchar(40) NOT NULL  default '',
  [created] varchar(30) NOT NULL  default '',
  [deletor] varchar(40) NOT NULL  default '',
  [deleted] varchar(30) NOT NULL  default '',
  [copyrightmarked] varchar(255) NOT NULL  default '',
  [copyrighturl] varchar(255) NOT NULL  default '',
  [credit] varchar(255) NOT NULL  default '',
  [source] varchar(255) NOT NULL  default '',
  [description] text NOT NULL  default '',
  [descriptionauthor] varchar(255) NOT NULL  default '',
  [_columns] int NOT NULL  default '0',
  [plaincontent] text NOT NULL  default '',
  [filesize] int NOT NULL  default '0',
  [colorspace] varchar(20) NOT NULL  default '',
  [types] text NOT NULL  default '',
  [pagenumber] int NOT NULL  default '0',
  [storename] text NOT NULL  default '',
  [pagerange] varchar(50) NOT NULL  default '',
  [highresfile] varchar(255) NOT NULL  default '',
  [deadlinesoft] varchar(30) NOT NULL  default '',
  [deadlinechanged] char(1) NOT NULL  default '',
  [plannedpagerange] varchar(50) NOT NULL  default '',
  [majorversion] int NOT NULL  default '-1',
  [minorversion] int NOT NULL  default '0',
  [encoding] varchar(100) NOT NULL  default '',
  [compression] varchar(100) NOT NULL  default '',
  [keyframeeveryframes] int NOT NULL  default '0',
  [channels] varchar(100) NOT NULL  default '',
  [aspectratio] varchar(100) NOT NULL  default '',
  [contentsource] varchar(100) NOT NULL  default '',
  [rating] tinyint NOT NULL  default 0,
  [indexed] char(2) NOT NULL  default '',
  [closed] char(2) NOT NULL  default '',
  [routetouserid] int NOT NULL  default '0',
  [routetogroupid] int NOT NULL  default '0',
  [orientation] tinyint NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [nm_objects] ON [smart_objects]([name]) ;
CREATE  INDEX [pbsectstate_objects] ON [smart_objects]([publication], [section], [state], [closed]) ;
CREATE  INDEX [pubid_objects] ON [smart_objects]([publication], [id], [closed]) ;
CREATE  INDEX [mo_objects] ON [smart_objects]([modifier]) ;
CREATE  INDEX [roid_objects] ON [smart_objects]([routeto], [id], [closed]) ;
CREATE  INDEX [codo_objects] ON [smart_objects]([contentsource], [documentid]) ;

CREATE TABLE [smart_objectversions] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objid] bigint NOT NULL  default '0',
  [minorversion] int NOT NULL  default '0',
  [modifier] varchar(40) NOT NULL  default '',
  [comment] varchar(255) NOT NULL  default '',
  [slugline] varchar(255) NOT NULL  default '',
  [created] varchar(30) NOT NULL  default '',
  [types] text NOT NULL  default '',
  [format] varchar(128) NOT NULL  default '',
  [width] real NOT NULL  default '0',
  [depth] real NOT NULL  default '0',
  [dpi] real NOT NULL  default '0',
  [lengthwords] int NOT NULL  default '0',
  [lengthchars] int NOT NULL  default '0',
  [lengthparas] int NOT NULL  default '0',
  [lengthlines] int NOT NULL  default '0',
  [keywords] text NOT NULL  default '',
  [description] text NOT NULL  default '',
  [descriptionauthor] varchar(255) NOT NULL  default '',
  [_columns] int NOT NULL  default '0',
  [plaincontent] text NOT NULL  default '',
  [filesize] int NOT NULL  default '0',
  [colorspace] varchar(20) NOT NULL  default '',
  [orientation] tinyint NOT NULL  default '0',
  [state] int NOT NULL  default '0',
  [majorversion] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [oive_objectversions] ON [smart_objectversions]([objid], [majorversion], [minorversion]) ;

CREATE TABLE [smart_objectrenditions] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objid] bigint NOT NULL  default '0',
  [editionid] int NOT NULL  default '0',
  [rendition] varchar(10) NOT NULL  default '',
  [format] varchar(128) NOT NULL  default '',
  [majorversion] int NOT NULL  default '0',
  [minorversion] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [obed_objectrenditions] ON [smart_objectrenditions]([objid], [editionid], [rendition]) ;

CREATE TABLE [smart_pages] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objid] bigint NOT NULL  default '0',
  [width] real NOT NULL  default '0',
  [height] real NOT NULL  default '0',
  [pagenumber] varchar(20) NOT NULL  default '',
  [pageorder] int NOT NULL  default '0',
  [nr] int NOT NULL  default '0',
  [types] text NOT NULL  default '',
  [edition] int NOT NULL  default '0',
  [master] varchar(255) NOT NULL  default '',
  [instance] varchar(40) NOT NULL  default 'Production',
  [pagesequence] int NOT NULL  default '0',
  [orientation] varchar(9) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [obpaed_pages] ON [smart_pages]([objid], [pageorder], [edition]) ;

CREATE TABLE [smart_placements] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [parent] bigint NOT NULL  default '0',
  [child] bigint NOT NULL  default '0',
  [page] int NOT NULL  default '0',
  [element] varchar(200) NOT NULL  default '',
  [elementid] varchar(200) NOT NULL  default '',
  [frameorder] int NOT NULL  default '0',
  [frameid] varchar(200) NOT NULL  default '',
  [_left] real NOT NULL  default '0',
  [top] real NOT NULL  default '0',
  [width] real NOT NULL  default '0',
  [height] real NOT NULL  default '0',
  [overset] real NOT NULL  default '0',
  [oversetchars] int NOT NULL  default '0',
  [oversetlines] int NOT NULL  default '0',
  [layer] varchar(200) NOT NULL  default '',
  [content] text NOT NULL  default '',
  [type] varchar(40) NOT NULL ,
  [edition] int NOT NULL  default '0',
  [contentdx] real NOT NULL  default 0,
  [contentdy] real NOT NULL  default 0,
  [scalex] real NOT NULL  default 1,
  [scaley] real NOT NULL  default 1,
  [pagesequence] int NOT NULL  default '0',
  [pagenumber] varchar(20) NOT NULL  default '',
  [formwidgetid] varchar(200) NOT NULL  default '',
  [frametype] varchar(20) NOT NULL  default '',
  [splineid] varchar(200) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pachty_placements] ON [smart_placements]([parent], [child], [type]) ;
CREATE  INDEX [ei_placements] ON [smart_placements]([elementid]) ;
CREATE  INDEX [chty_placements] ON [smart_placements]([child], [type]) ;

CREATE TABLE [smart_elements] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [guid] varchar(200) NOT NULL  default '',
  [name] varchar(200) NOT NULL  default '',
  [objid] bigint NOT NULL  default 0,
  [lengthwords] int NOT NULL  default '0',
  [lengthchars] int NOT NULL  default '0',
  [lengthparas] int NOT NULL  default '0',
  [lengthlines] int NOT NULL  default '0',
  [snippet] varchar(255) NOT NULL  default '',
  [version] varchar(50) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [oigu_elements] ON [smart_elements]([objid], [guid]) ;

CREATE TABLE [smart_indesignarticles] (
  [objid] bigint NOT NULL  default 0,
  [artuid] varchar(40) NOT NULL  default '',
  [name] varchar(200) NOT NULL  default '',
  [code] int NOT NULL  default '0',
  PRIMARY KEY ([objid], [artuid])
);

CREATE TABLE [smart_idarticlesplacements] (
  [objid] bigint NOT NULL  default 0,
  [artuid] varchar(40) NOT NULL  default '',
  [plcid] bigint NOT NULL  default 0,
  [code] int NOT NULL  default '0',
  PRIMARY KEY ([objid], [artuid], [plcid])
);
CREATE  INDEX [plcid_idarticlesplacements] ON [smart_idarticlesplacements]([plcid]) ;

CREATE TABLE [smart_objectoperations] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objid] bigint NOT NULL  default 0,
  [guid] varchar(40) NOT NULL  default '',
  [type] varchar(200) NOT NULL  default '',
  [name] varchar(200) NOT NULL  default '',
  [params] text NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [objid_objectoperations] ON [smart_objectoperations]([objid]) ;

CREATE TABLE [smart_properties] (
  [id] int NOT NULL  IDENTITY(1,1),
  [publication] int NOT NULL  default '0',
  [objtype] varchar(40) NOT NULL  default '',
  [name] varchar(200) NOT NULL  default '',
  [dispname] varchar(200) NOT NULL  default '',
  [category] varchar(200) NOT NULL  default '',
  [type] varchar(40) NOT NULL  default '',
  [defaultvalue] varchar(200) NOT NULL  default '',
  [valuelist] text NOT NULL  default '',
  [minvalue] varchar(200) NOT NULL  default '',
  [maxvalue] varchar(200) NOT NULL  default '',
  [maxlen] bigint NOT NULL  default '0',
  [dbupdated] tinyint NOT NULL  default '0',
  [entity] varchar(20) NOT NULL  default 'Object',
  [serverplugin] varchar(64) NOT NULL  default '',
  [adminui] varchar(2) NOT NULL  default 'on',
  [propertyvalues] text NOT NULL  default '',
  [minresolution] varchar(200) NOT NULL  default '',
  [maxresolution] varchar(200) NOT NULL  default '',
  [publishsystem] varchar(64) NOT NULL  default '',
  [templateid] bigint NOT NULL  default 0,
  [termentityid] int NOT NULL  default '0',
  [suggestionentity] varchar(200) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [puob_properties] ON [smart_properties]([publication], [objtype]) ;
CREATE  INDEX [pudb_properties] ON [smart_properties]([publication], [dbupdated]) ;

CREATE TABLE [smart_publadmin] (
  [id] int NOT NULL  IDENTITY(1,1),
  [publication] int NOT NULL  default '0',
  [grpid] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pugi_publadmin] ON [smart_publadmin]([publication], [grpid]) ;

CREATE TABLE [smart_publications] (
  [id] int NOT NULL  IDENTITY(1,1),
  [publication] varchar(255) NOT NULL  default '',
  [code] int NOT NULL  default '0',
  [email] char(2) NOT NULL  default '',
  [description] text NOT NULL  default '',
  [readingorderrev] varchar(2) NOT NULL  default '',
  [autopurge] int NOT NULL  default 0,
  [defaultchannelid] int NOT NULL  default '0',
  [calculatedeadlines] char(2) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pb_publications] ON [smart_publications]([publication]) ;
CREATE  INDEX [idpb_publications] ON [smart_publications]([id], [publication]) ;
SET IDENTITY_INSERT [smart_publications] ON
INSERT INTO [smart_publications] ([id], [publication], [code], [email], [description], [readingorderrev], [autopurge], [defaultchannelid], [calculatedeadlines]) VALUES (1, 'WW News', 0, '', '', '', 0, 1, '');
SET IDENTITY_INSERT [smart_publications] OFF

CREATE TABLE [smart_publsections] (
  [id] int NOT NULL  IDENTITY(1,1),
  [publication] int NOT NULL  default '0',
  [section] varchar(255) NOT NULL  default '',
  [issue] int NOT NULL  default '0',
  [code] int NOT NULL  default '0',
  [description] text NOT NULL  default '',
  [pages] int NOT NULL  default '0',
  [deadline] varchar(30) NOT NULL  default '',
  [deadlinerelative] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pbis_publsections] ON [smart_publsections]([publication], [issue]) ;
CREATE  INDEX [se_publsections] ON [smart_publsections]([section]) ;
SET IDENTITY_INSERT [smart_publsections] ON
INSERT INTO [smart_publsections] ([id], [publication], [section], [issue], [code], [description], [pages], [deadline], [deadlinerelative]) VALUES (1, 1, 'News', 0, 10, '', 0, '', 0 );
INSERT INTO [smart_publsections] ([id], [publication], [section], [issue], [code], [description], [pages], [deadline], [deadlinerelative]) VALUES (2, 1, 'Sport', 0, 20, '', 0, '', 0 );
SET IDENTITY_INSERT [smart_publsections] OFF

CREATE TABLE [smart_publobjects] (
  [id] int NOT NULL  IDENTITY(1,1),
  [publicationid] int NOT NULL  default '0',
  [issueid] int NOT NULL  default '0',
  [objectid] bigint NOT NULL  default '0',
  [grpid] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [puisobgr_publobjects] ON [smart_publobjects]([publicationid], [issueid], [objectid], [grpid]) ;

CREATE TABLE [smart_issueeditions] (
  [id] int NOT NULL  IDENTITY(1,1),
  [issue] int NOT NULL  default '0',
  [edition] int NOT NULL  default '0',
  [deadline] varchar(30) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [is_issueeditions] ON [smart_issueeditions]([issue]) ;
CREATE  INDEX [ed_issueeditions] ON [smart_issueeditions]([edition]) ;

CREATE TABLE [smart_routing] (
  [id] int NOT NULL  IDENTITY(1,1),
  [publication] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [state] int NOT NULL  default '0',
  [routeto] varchar(255) NOT NULL  default '',
  [issue] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pbisse_routing] ON [smart_routing]([publication], [issue], [section]) ;
CREATE  INDEX [st_routing] ON [smart_routing]([state]) ;

CREATE TABLE [smart_settings] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [user] varchar(200) NOT NULL  default '',
  [setting] varchar(200) NOT NULL  default '',
  [value] text NOT NULL  default '',
  [appname] varchar(200) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [us_settings] ON [smart_settings]([user]) ;
CREATE  INDEX [se_settings] ON [smart_settings]([setting]) ;

CREATE TABLE [smart_states] (
  [id] int NOT NULL  IDENTITY(1,1),
  [publication] int NOT NULL  default '0',
  [type] varchar(40) NOT NULL  default '',
  [state] varchar(40) NOT NULL  default '',
  [produce] char(2) NOT NULL  default '',
  [color] varchar(11) NOT NULL  default '',
  [nextstate] int NOT NULL  default '0',
  [code] int NOT NULL  default '0',
  [issue] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [deadlinestate] int NOT NULL  default '0',
  [deadlinerelative] int NOT NULL  default '0',
  [createpermanentversion] char(2) NOT NULL  default '',
  [removeintermediateversions] char(2) NOT NULL  default '',
  [readyforpublishing] char(2) NOT NULL  default '',
  [automaticallysendtonext] char(2) NOT NULL  default '',
  [phase] varchar(40) NOT NULL  default 'Production',
  [skipidsa] char(2) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [st_states] ON [smart_states]([state]) ;
CREATE  INDEX [pbistyse_states] ON [smart_states]([publication], [issue], [type], [section]) ;
CREATE  INDEX [istyse_states] ON [smart_states]([issue], [type], [section]) ;
CREATE  INDEX [cost_states] ON [smart_states]([code], [state]) ;
SET IDENTITY_INSERT [smart_states] ON
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (1, 1, 'Article', 'Draft text', '', '#FF0000', 2, 10, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (2, 1, 'Article', 'Ready', '', '#00FF00', 0, 20, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (3, 1, 'Layout', 'Layouts', '', '#0000FF', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (4, 1, 'LayoutTemplate', 'Layout Templates', '', '#FFFF99', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (5, 1, 'ArticleTemplate', 'Article Templates', '', '#FFFF99', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (6, 1, 'Image', 'Images', '', '#FFFF00', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (7, 1, 'Advert', 'Adverts', '', '#99CCFF', 0, 0, 0, 0, 0, 0, '', '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (8, 1, 'Video', 'Videos', '', '#FFFF00', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (9, 1, 'Audio', 'Audios', '', '#FFFF00', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (10, 1, 'Library', 'Libraries', '', '#888888', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (11, 1, 'Dossier', 'Dossiers', '', '#BBBBBB', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (12, 1, 'DossierTemplate', 'Dossier Templates', '', '#BBBBBB', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (13, 1, 'LayoutModule', 'Layout Modules', '', '#D7C101', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (14, 1, 'LayoutModuleTemplate', 'Layout Module Templates', '', '#FFE553', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (15, 1, 'Task', 'Assigned', '', '#AAAAAA', 15, 10, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (16, 1, 'Task', 'In progress', '', '#AAAAAA', 16, 20, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (17, 1, 'Task', 'Completed', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (18, 1, 'Hyperlink', 'Hyperlinks', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (19, 1, 'Other', 'Others', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (20, 1, 'Archive', 'Archives', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (21, 1, 'Presentation', 'Presentations', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (22, 1, 'Spreadsheet', 'Draft', '', '#FF0000', 23, 10, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (23, 1, 'Spreadsheet', 'Ready', '', '#00FF00', 0, 20, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (24, 1, 'PublishForm', 'Publish Forms', '', '#AAAAAA', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO [smart_states] ([id], [publication], [type], [state], [produce], [color], [nextstate], [code], [issue], [section], [deadlinestate], [deadlinerelative], [createpermanentversion], [removeintermediateversions], [readyforpublishing], [automaticallysendtonext], [phase], [skipidsa]) VALUES (25, 1, 'PublishFormTemplate', 'Publish Form Templates', '', '#AAAAAA', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
SET IDENTITY_INSERT [smart_states] OFF

CREATE TABLE [smart_tickets] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [ticketid] varchar(40) NOT NULL  default '',
  [usr] varchar(40) NOT NULL  default '',
  [db] varchar(255) NOT NULL  default '',
  [clientname] varchar(255) NOT NULL  default '',
  [clientip] varchar(40) NOT NULL  default '',
  [appname] varchar(200) NOT NULL  default '',
  [appversion] varchar(200) NOT NULL  default '',
  [appserial] varchar(200) NOT NULL  default '',
  [logon] varchar(20) NOT NULL  default '',
  [expire] varchar(30) NOT NULL  default '',
  [appproductcode] varchar(40) NOT NULL  default '',
  [masterticketid] varchar(40) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [ti_tickets] ON [smart_tickets]([ticketid]) ;
CREATE  INDEX [us_tickets] ON [smart_tickets]([usr]) ;
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
  [entityid] bigint NOT NULL  default '0',
  [displayname] varchar(255) NOT NULL  default '',
  [normalizedname] varchar(255) NOT NULL  default '',
  [ligatures] varchar(255) NOT NULL  default '',
  PRIMARY KEY ([entityid], [displayname])
);
CREATE  INDEX [tm_entityid] ON [smart_terms]([entityid]) ;
CREATE  INDEX [tm_normalizedname] ON [smart_terms]([entityid], [normalizedname]) ;

CREATE TABLE [smart_users] (
  [id] int NOT NULL  IDENTITY(1,1),
  [user] varchar(40) NOT NULL  default '',
  [fullname] varchar(255) NOT NULL  default '',
  [pass] varchar(128) NOT NULL  default '',
  [disable] char(2) NOT NULL  default '',
  [fixedpass] char(2) NOT NULL  default '',
  [email] varchar(100) NOT NULL  default '',
  [emailgrp] char(2) NOT NULL  default '',
  [emailusr] char(2) NOT NULL  default '',
  [language] varchar(4) NOT NULL  default '',
  [startdate] varchar(30) NOT NULL  default '',
  [enddate] varchar(30) NOT NULL  default '',
  [expirepassdate] varchar(30) NOT NULL  default '',
  [expiredays] int NOT NULL  default '0',
  [trackchangescolor] varchar(11) NOT NULL  default '',
  [lastlogondate] varchar(30) NOT NULL  default '',
  [organization] varchar(255) NOT NULL  default '',
  [location] varchar(255) NOT NULL  default '',
  [externalid] varchar(200) NOT NULL  default '',
  [importonlogon] char(2) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [us_users] ON [smart_users]([user]) ;
CREATE  INDEX [fu_users] ON [smart_users]([fullname]) ;
SET IDENTITY_INSERT [smart_users] ON
INSERT INTO [smart_users] ([id], [user], [fullname], [pass], [disable], [fixedpass], [email], [emailgrp], [emailusr], [language], [startdate], [enddate], [expirepassdate], [expiredays], [trackchangescolor], [lastlogondate], [organization], [location], [externalid], [importonlogon]) VALUES (1, 'woodwing', 'WoodWing Software', '', '', '', '', '', '', 'enUS', '', '', '', 0, '#FF0000', '', '', '', '', '' );
SET IDENTITY_INSERT [smart_users] OFF

CREATE TABLE [smart_usrgrp] (
  [id] int NOT NULL  IDENTITY(1,1),
  [usrid] int NOT NULL  default '0',
  [grpid] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [usgi_usrgrp] ON [smart_usrgrp]([usrid], [grpid]) ;
CREATE  INDEX [gi_usrgrp] ON [smart_usrgrp]([grpid]) ;
SET IDENTITY_INSERT [smart_usrgrp] ON
INSERT INTO [smart_usrgrp] ([id], [usrid], [grpid]) VALUES (2, 1, 2);
SET IDENTITY_INSERT [smart_usrgrp] OFF

CREATE TABLE [smart_mtp] (
  [publid] int NOT NULL ,
  [issueid] int NOT NULL  default '0',
  [laytriggerstate] int NOT NULL ,
  [arttriggerstate] int NOT NULL  default 0,
  [imgtriggerstate] int NOT NULL  default 0,
  [layprogstate] int NOT NULL  default 0,
  [artprogstate] int NOT NULL  default 0,
  [imgprogstate] int NOT NULL  default 0,
  [mtptext] text NOT NULL  default '',
  PRIMARY KEY ([publid], [issueid], [laytriggerstate])
);
CREATE  INDEX [ii_mtp] ON [smart_mtp]([issueid]) ;

CREATE TABLE [smart_mtpsentobjects] (
  [objid] bigint NOT NULL  default '0',
  [publid] int NOT NULL ,
  [issueid] int NOT NULL  default '0',
  [laytriggerstate] int NOT NULL ,
  [printstate] int NOT NULL ,
  PRIMARY KEY ([objid], [publid], [issueid], [laytriggerstate], [printstate])
);
CREATE  INDEX [ii_mtpsentobjects] ON [smart_mtpsentobjects]([issueid]) ;
CREATE  INDEX [ls_mtpsentobjects] ON [smart_mtpsentobjects]([laytriggerstate]) ;

CREATE TABLE [smart_messagelog] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objid] bigint NOT NULL  default 0,
  [userid] int NOT NULL  default 0,
  [messagetype] varchar(255) NOT NULL ,
  [messagetypedetail] varchar(255) NOT NULL ,
  [message] text NOT NULL  default '',
  [date] varchar(30) NOT NULL  default '',
  [expirationdate] varchar(30) NOT NULL  default '',
  [messagelevel] varchar(255) NOT NULL  default '',
  [fromuser] varchar(255) NOT NULL  default '',
  [msgid] varchar(200) NOT NULL  default '',
  [anchorx] real NOT NULL  default '0',
  [anchory] real NOT NULL  default '0',
  [left] real NOT NULL  default '0',
  [top] real NOT NULL  default '0',
  [width] real NOT NULL  default '0',
  [height] real NOT NULL  default '0',
  [page] int NOT NULL  default '0',
  [version] varchar(200) NOT NULL  default '',
  [color] varchar(11) NOT NULL  default '',
  [pagesequence] int NOT NULL  default '0',
  [threadmessageid] varchar(200) NOT NULL  default '',
  [replytomessageid] varchar(200) NOT NULL  default '',
  [messagestatus] varchar(15) NOT NULL  default 'None',
  [majorversion] int NOT NULL  default '0',
  [minorversion] int NOT NULL  default '0',
  [isread] varchar(2) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [oimtpa_messagelog] ON [smart_messagelog]([objid], [messagetype], [page]) ;
CREATE  INDEX [oimtd_messagelog] ON [smart_messagelog]([objid], [messagetypedetail]) ;
CREATE  INDEX [mi_messagelog] ON [smart_messagelog]([msgid]) ;
CREATE  INDEX [uid_messagelog] ON [smart_messagelog]([userid]) ;

CREATE TABLE [smart_objectflags] (
  [objid] bigint NOT NULL  default '0',
  [flagorigin] varchar(255) NOT NULL ,
  [flag] int NOT NULL ,
  [severity] int NOT NULL ,
  [message] text NOT NULL  default '',
  [locked] int NOT NULL  default 0,
  PRIMARY KEY ([objid], [flagorigin], [flag])
);

CREATE TABLE [smart_issuesection] (
  [id] int NOT NULL  IDENTITY(1,1),
  [issue] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [deadline] varchar(30) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [isse_issuesection] ON [smart_issuesection]([issue], [section]) ;

CREATE TABLE [smart_issuesectionstate] (
  [id] int NOT NULL  IDENTITY(1,1),
  [issue] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [state] int NOT NULL  default '0',
  [deadline] varchar(30) NOT NULL  default '',
  [deadlinerelative] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [issest_issuesectionstate] ON [smart_issuesectionstate]([issue], [section], [state]) ;

CREATE TABLE [smart_sectionstate] (
  [id] int NOT NULL  IDENTITY(1,1),
  [section] int NOT NULL  default '0',
  [state] int NOT NULL  default '0',
  [deadlinerelative] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [sest_sectionstate] ON [smart_sectionstate]([section], [state]) ;

CREATE TABLE [smart_profiles] (
  [id] int NOT NULL  IDENTITY(1,1),
  [profile] varchar(255) NOT NULL  default '',
  [code] int NOT NULL  default '0',
  [description] text NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pr_profiles] ON [smart_profiles]([profile]) ;
SET IDENTITY_INSERT [smart_profiles] ON
INSERT INTO [smart_profiles] ([id], [profile], [code], [description]) VALUES (1, 'Full Control', 0, 'All features enabled');
SET IDENTITY_INSERT [smart_profiles] OFF

CREATE TABLE [smart_profilefeatures] (
  [id] int NOT NULL  IDENTITY(1,1),
  [profile] int NOT NULL  default '0',
  [feature] int NOT NULL  default '0',
  [value] varchar(20) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [prfe_profiles] ON [smart_profilefeatures]([profile], [feature]) ;
SET IDENTITY_INSERT [smart_profilefeatures] ON
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (1, 1, 1, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (2, 1, 2, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (3, 1, 3, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (4, 1, 4, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (5, 1, 5, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (6, 1, 6, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (7, 1, 7, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (8, 1, 8, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (9, 1, 9, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (10, 1, 10, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (11, 1, 99, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (12, 1, 101, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (13, 1, 102, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (14, 1, 103, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (15, 1, 104, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (16, 1, 105, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (17, 1, 106, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (18, 1, 107, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (19, 1, 108, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (20, 1, 109, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (21, 1, 110, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (22, 1, 111, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (23, 1, 112, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (24, 1, 113, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (25, 1, 114, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (26, 1, 115, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (27, 1, 116, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (28, 1, 117, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (29, 1, 118, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (30, 1, 119, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (31, 1, 120, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (32, 1, 121, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (33, 1, 122, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (34, 1, 124, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (35, 1, 125, 'No');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (36, 1, 126, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (37, 1, 127, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (38, 1, 128, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (39, 1, 129, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (40, 1, 130, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (41, 1, 131, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (42, 1, 132, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (43, 1, 133, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (44, 1, 134, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (45, 1, 135, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (46, 1, 1001, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (47, 1, 1002, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (48, 1, 1003, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (49, 1, 1004, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (52, 1, 1007, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (53, 1, 1008, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (54, 1, 91, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (55, 1, 92, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (56, 1, 93, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (57, 1, 90, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (58, 1, 98, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (59, 1, 88, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (61, 1, 87, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (62, 1, 86, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (63, 1, 85, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (64, 1, 1009, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (65, 1, 11, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (66, 1, 12, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (67, 1, 13, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (68, 1, 136, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (69, 1, 70, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (70, 1, 71, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (71, 1, 72, 'Yes');
INSERT INTO [smart_profilefeatures] ([id], [profile], [feature], [value]) VALUES (72, 1, 84, 'Yes');
SET IDENTITY_INSERT [smart_profilefeatures] OFF

CREATE TABLE [smart_featureaccess] (
  [featurename] varchar(255) NOT NULL  default '',
  [featureid] int NOT NULL  default '0',
  [accessflag] int NOT NULL  default '0',
  PRIMARY KEY ([featurename])
);
CREATE UNIQUE INDEX [faid_profiles] ON [smart_featureaccess]([featureid]) ;
CREATE  INDEX [fafl_profiles] ON [smart_featureaccess]([accessflag]) ;

CREATE TABLE [smart_appsessions] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [sessionid] varchar(40) NOT NULL  default '',
  [userid] varchar(40) NOT NULL  default '',
  [appname] varchar(40) NOT NULL  default '',
  [lastsaved] varchar(20) NOT NULL  default '',
  [readonly] char(2) NOT NULL  default '',
  [articleid] bigint NOT NULL  default 0,
  [articlename] varchar(255) NOT NULL  default '',
  [articleformat] varchar(128) NOT NULL  default '',
  [articleminorversion] int NOT NULL  default 0,
  [templateid] bigint NOT NULL  default 0,
  [templatename] varchar(255) NOT NULL  default '',
  [templateformat] varchar(128) NOT NULL  default '',
  [layoutid] bigint NOT NULL  default 0,
  [layoutminorversion] int NOT NULL  default 0,
  [articlemajorversion] int NOT NULL  default 0,
  [layoutmajorversion] int NOT NULL  default 0,
  [dommajorversion] int NOT NULL  default '5',
  [domminorversion] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_datasources] (
  [id] int NOT NULL  IDENTITY(1,1),
  [type] varchar(255) NOT NULL  default '',
  [name] varchar(255) NOT NULL  default '',
  [bidirectional] char(2) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [na_datasources] ON [smart_datasources]([name]) ;

CREATE TABLE [smart_dspublications] (
  [id] int NOT NULL  IDENTITY(1,1),
  [datasourceid] int NOT NULL  default '0',
  [publicationid] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [dsid_dspublications] ON [smart_dspublications]([datasourceid]) ;
CREATE  INDEX [pubid_dspublications] ON [smart_dspublications]([publicationid]) ;

CREATE TABLE [smart_dsqueries] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(255) NOT NULL  default '',
  [query] text NOT NULL  default '',
  [comment] text NOT NULL  default '',
  [interface] text NOT NULL  default '',
  [datasourceid] int NOT NULL  default '0',
  [recordid] varchar(255) NOT NULL  default '',
  [recordfamily] varchar(255) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [dsid_dsqueries] ON [smart_dsqueries]([datasourceid]) ;

CREATE TABLE [smart_dsqueryfields] (
  [id] int NOT NULL  IDENTITY(1,1),
  [queryid] int NOT NULL  default '0',
  [priority] tinyint NOT NULL  default '0',
  [name] varchar(255) NOT NULL  default '',
  [readonly] tinyint NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [queryid_dsqueryfields] ON [smart_dsqueryfields]([queryid]) ;

CREATE TABLE [smart_dssettings] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(255) NOT NULL  default '',
  [value] text NOT NULL  default '',
  [datasourceid] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [dsid_dssettings] ON [smart_dssettings]([datasourceid]) ;

CREATE TABLE [smart_dsqueryplacements] (
  [id] int NOT NULL  IDENTITY(1,1),
  [objectid] bigint NOT NULL  default '0',
  [datasourceid] int NOT NULL  default '0',
  [dirty] char(2) NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [objid_dsqueryplacements] ON [smart_dsqueryplacements]([objectid]) ;
CREATE  INDEX [dsid_dsqueryplacements] ON [smart_dsqueryplacements]([datasourceid]) ;

CREATE TABLE [smart_dsqueryfamilies] (
  [id] int NOT NULL  IDENTITY(1,1),
  [queryplacementid] int NOT NULL  default '0',
  [familyfield] varchar(255) NOT NULL  default '',
  [familyvalue] text NOT NULL  default '',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_dsupdates] (
  [id] int NOT NULL  IDENTITY(1,1),
  [recordset] image NOT NULL  default '',
  [familyvalue] text NOT NULL  default '',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_dsobjupdates] (
  [id] int NOT NULL  IDENTITY(1,1),
  [updateid] int NOT NULL  default '0',
  [objectid] bigint NOT NULL  default '0',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_channels] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(255) NOT NULL  default '',
  [publicationid] int NOT NULL  default '0',
  [type] varchar(32) NOT NULL  default 'print',
  [description] varchar(255) NOT NULL  default '',
  [code] int NOT NULL  default '0',
  [deadlinerelative] int NOT NULL  default '0',
  [currentissueid] int NOT NULL  default '0',
  [publishsystem] varchar(64) NOT NULL  default '',
  [suggestionprovider] varchar(64) NOT NULL  default '',
  [publishsystemid] varchar(40) NOT NULL  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_channels] ON
INSERT INTO [smart_channels] ([id], [name], [publicationid], [type], [description], [code], [deadlinerelative], [currentissueid], [publishsystem], [suggestionprovider], [publishsystemid]) VALUES (1, 'Print', 1, 'print', 'Print Channel', 10, 0, 1, '', '', '' );
INSERT INTO [smart_channels] ([id], [name], [publicationid], [type], [description], [code], [deadlinerelative], [currentissueid], [publishsystem], [suggestionprovider], [publishsystemid]) VALUES (2, 'Web', 1, 'web', 'Web Channel', 20, 0, 0, '', '', '' );
SET IDENTITY_INSERT [smart_channels] OFF

CREATE TABLE [smart_editions] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(255) NOT NULL  default '',
  [channelid] int NOT NULL  default '0',
  [issueid] int NOT NULL  default '0',
  [code] int NOT NULL  default '0',
  [deadlinerelative] int NOT NULL  default '0',
  [description] text NOT NULL  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_editions] ON
INSERT INTO [smart_editions] ([id], [name], [channelid], [issueid], [code], [deadlinerelative], [description]) VALUES (1, 'North', 1, 0, '10', '', '' );
INSERT INTO [smart_editions] ([id], [name], [channelid], [issueid], [code], [deadlinerelative], [description]) VALUES (2, 'South', 1, 0, '20', '', '' );
SET IDENTITY_INSERT [smart_editions] OFF

CREATE TABLE [smart_issues] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(255) NOT NULL  default '',
  [channelid] int NOT NULL  default '0',
  [overrulepub] char(2) NOT NULL  default '',
  [code] int NOT NULL  default '0',
  [publdate] varchar(30) NOT NULL  default '',
  [deadline] varchar(30) NOT NULL  default '',
  [pages] int NOT NULL  default '0',
  [subject] text NOT NULL  default '',
  [description] text NOT NULL  default '',
  [active] char(2) NOT NULL  default '',
  [readingorderrev] varchar(2) NOT NULL  default '',
  [calculatedeadlines] char(2) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [ch_issues] ON [smart_issues]([channelid]) ;
CREATE  INDEX [na_issues] ON [smart_issues]([name]) ;
SET IDENTITY_INSERT [smart_issues] ON
INSERT INTO [smart_issues] ([id], [name], [channelid], [overrulepub], [code], [publdate], [deadline], [pages], [subject], [description], [active], [readingorderrev], [calculatedeadlines]) VALUES (1, '1st Issue', 1, '', '10', '', '', 16, '', '', 'on', '', '' );
INSERT INTO [smart_issues] ([id], [name], [channelid], [overrulepub], [code], [publdate], [deadline], [pages], [subject], [description], [active], [readingorderrev], [calculatedeadlines]) VALUES (2, '2nd Issue', 1, '', '20', '', '', 16, '', '', 'on', 'on', '' );
INSERT INTO [smart_issues] ([id], [name], [channelid], [overrulepub], [code], [publdate], [deadline], [pages], [subject], [description], [active], [readingorderrev], [calculatedeadlines]) VALUES (3, 'webissue', 2, '', '10', '', '', 16, '', '', 'on', 'on', '' );
SET IDENTITY_INSERT [smart_issues] OFF

CREATE TABLE [smart_targets] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objectid] bigint NOT NULL  default '0',
  [channelid] int NOT NULL  default '0',
  [issueid] int NOT NULL  default '0',
  [externalid] varchar(200) NOT NULL  default '',
  [objectrelationid] bigint NOT NULL  default '0',
  [publisheddate] varchar(30) NOT NULL  default '',
  [publishedmajorversion] int NOT NULL  default '0',
  [publishedminorversion] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [obchisobr_targets] ON [smart_targets]([objectid], [channelid], [issueid], [objectrelationid]) ;
CREATE UNIQUE INDEX [obrobid_targets] ON [smart_targets]([objectrelationid], [objectid], [id]) ;
CREATE  INDEX [issueid_targets] ON [smart_targets]([issueid]) ;

CREATE TABLE [smart_publishhistory] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [externalid] varchar(200) NOT NULL  default '',
  [objectid] bigint NOT NULL  default '0',
  [channelid] int NOT NULL  default '0',
  [issueid] int NOT NULL  default '0',
  [editionid] int NOT NULL  default '0',
  [publisheddate] varchar(30) NOT NULL  default '',
  [fields] text NOT NULL  default '',
  [fieldsmajorversion] int NOT NULL  default '0',
  [fieldsminorversion] int NOT NULL  default '0',
  [actiondate] varchar(30) NOT NULL  default '',
  [action] varchar(20) NOT NULL  default '',
  [user] varchar(255) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [obchis_publhist] ON [smart_publishhistory]([objectid], [channelid], [issueid]) ;
CREATE  INDEX [chis_publhist] ON [smart_publishhistory]([channelid], [issueid]) ;

CREATE TABLE [smart_pubpublishedissues] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [externalid] varchar(200) NOT NULL  default '',
  [channelid] int NOT NULL  default '0',
  [issueid] int NOT NULL  default '0',
  [editionid] int NOT NULL  default '0',
  [report] text NOT NULL  default '',
  [dossierorder] text NOT NULL  default '',
  [publishdate] varchar(30) NOT NULL  default '',
  [issuemajorversion] int NOT NULL  default '0',
  [issueminorversion] int NOT NULL  default '0',
  [fields] text NOT NULL  default '',
  [fieldsmajorversion] int NOT NULL  default '0',
  [fieldsminorversion] int NOT NULL  default '0',
  [actiondate] varchar(30) NOT NULL  default '',
  [action] varchar(20) NOT NULL  default '',
  [userid] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [chised_publhist] ON [smart_pubpublishedissues]([channelid], [issueid], [editionid]) ;

CREATE TABLE [smart_publishedobjectshist] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objectid] bigint NOT NULL  default '0',
  [publishid] bigint NOT NULL  default '0',
  [majorversion] int NOT NULL  default '0',
  [minorversion] int NOT NULL  default '0',
  [externalid] varchar(200) NOT NULL  default '',
  [objectname] varchar(255) NOT NULL  default '',
  [objecttype] varchar(40) NOT NULL  default '',
  [objectformat] varchar(128) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [obpu_publobjhist] ON [smart_publishedobjectshist]([objectid], [publishid]) ;
CREATE  INDEX [puob_publobjhist] ON [smart_publishedobjectshist]([publishid], [objectid]) ;

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

CREATE TABLE [smart_targeteditions] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [targetid] bigint NOT NULL  default '0',
  [editionid] int NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [taed_targeteditions] ON [smart_targeteditions]([targetid], [editionid]) ;
CREATE UNIQUE INDEX [edta_targeteditions] ON [smart_targeteditions]([editionid], [targetid]) ;

CREATE TABLE [smart_indesignservers] (
  [id] int NOT NULL  IDENTITY(1,1),
  [hostname] varchar(64) NOT NULL  default '',
  [portnumber] int NOT NULL  default '0',
  [description] varchar(255) NOT NULL  default '',
  [active] char(2) NOT NULL  default '',
  [servermajorversion] int NOT NULL  default '5',
  [serverminorversion] int NOT NULL  default '0',
  [prio1] char(2) NOT NULL  default 'on',
  [prio2] char(2) NOT NULL  default 'on',
  [prio3] char(2) NOT NULL  default 'on',
  [prio4] char(2) NOT NULL  default 'on',
  [prio5] char(2) NOT NULL  default 'on',
  [locktoken] varchar(40) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [hopo_indesignservers] ON [smart_indesignservers]([hostname], [portnumber]) ;

CREATE TABLE [smart_indesignserverjobs] (
  [jobid] varchar(40) NOT NULL  default '',
  [foreground] char(2) NOT NULL  default '',
  [objid] bigint NOT NULL  default 0,
  [objectmajorversion] int NOT NULL  default '0',
  [objectminorversion] int NOT NULL  default '0',
  [jobtype] varchar(32) NOT NULL ,
  [jobscript] text NOT NULL  default '',
  [jobparams] text NOT NULL  default '',
  [locktoken] varchar(40) NOT NULL  default '',
  [queuetime] varchar(20) NOT NULL  default '',
  [starttime] varchar(30) NOT NULL  default '',
  [readytime] varchar(20) NOT NULL  default '',
  [errorcode] varchar(32) NOT NULL  default '',
  [errormessage] varchar(1024) NOT NULL  default '',
  [scriptresult] text NOT NULL  default '',
  [jobstatus] int NOT NULL  default 0,
  [jobcondition] int NOT NULL  default 0,
  [jobprogress] int NOT NULL  default 0,
  [attempts] int NOT NULL  default 0,
  [pickuptime] varchar(30) NOT NULL  default '',
  [assignedserverid] int NOT NULL  default 0,
  [minservermajorversion] int NOT NULL  default '0',
  [minserverminorversion] int NOT NULL  default '0',
  [maxservermajorversion] int NOT NULL  default '0',
  [maxserverminorversion] int NOT NULL  default '0',
  [prio] int NOT NULL  default '3',
  [ticketseal] varchar(40) NOT NULL  default '',
  [ticket] varchar(40) NOT NULL  default '',
  [actinguser] varchar(40) NOT NULL  default '',
  [initiator] varchar(40) NOT NULL  default '',
  [servicename] varchar(32) NOT NULL  default '',
  [context] varchar(64) NOT NULL  default '',
  PRIMARY KEY ([jobid])
);
CREATE  INDEX [asre_indesignserverjobs] ON [smart_indesignserverjobs]([assignedserverid], [readytime]) ;
CREATE  INDEX [qt_indesignserverjobs] ON [smart_indesignserverjobs]([queuetime]) ;
CREATE  INDEX [objid_indesignserverjobs] ON [smart_indesignserverjobs]([objid]) ;
CREATE  INDEX [prid_indesignserverjobs] ON [smart_indesignserverjobs]([prio], [jobid]) ;
CREATE  INDEX [ts_indesignserverjobs] ON [smart_indesignserverjobs]([ticketseal]) ;
CREATE  INDEX [ttjtstrt_indesignserverjobs] ON [smart_indesignserverjobs]([ticket], [jobtype], [starttime], [readytime]) ;
CREATE  INDEX [jp_indesignserverjobs] ON [smart_indesignserverjobs]([jobprogress]) ;
CREATE  INDEX [jspr_indesignserverjobs] ON [smart_indesignserverjobs]([jobstatus], [prio], [queuetime]) ;
CREATE  INDEX [lt_indesignserverjobs] ON [smart_indesignserverjobs]([locktoken]) ;

CREATE TABLE [smart_servers] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(64) NOT NULL  default '',
  [type] varchar(32) NOT NULL  default '',
  [url] varchar(1024) NOT NULL  default '',
  [description] varchar(255) NOT NULL  default '',
  [jobsupport] char(1) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [hopo_servers] ON [smart_servers]([name]) ;

CREATE TABLE [smart_serverjobs] (
  [jobid] varchar(40) NOT NULL  default '',
  [attempts] int NOT NULL  default 0,
  [queuetime] varchar(30) NOT NULL  default '',
  [servicename] varchar(32) NOT NULL  default '',
  [context] varchar(32) NOT NULL  default '',
  [servertype] varchar(32) NOT NULL  default '',
  [jobtype] varchar(32) NOT NULL  default '',
  [assignedserverid] int NOT NULL  default 0,
  [starttime] varchar(30) NOT NULL  default '0000-00-00T00:00:00',
  [readytime] varchar(30) NOT NULL  default '0000-00-00T00:00:00',
  [errormessage] varchar(1024) NOT NULL  default '',
  [locktoken] varchar(40) NOT NULL  default '',
  [ticketseal] varchar(40) NOT NULL  default '',
  [actinguser] varchar(40) NOT NULL  default '',
  [jobstatus] int NOT NULL  default 0,
  [jobcondition] int NOT NULL  default 0,
  [jobprogress] int NOT NULL  default 0,
  [jobdata] text NOT NULL  default '',
  [dataentity] varchar(20) NOT NULL  default '',
  PRIMARY KEY ([jobid])
);
CREATE  INDEX [qt_serverjobs] ON [smart_serverjobs]([queuetime]) ;
CREATE  INDEX [jobinfo] ON [smart_serverjobs]([locktoken], [jobstatus], [jobprogress]) ;
CREATE  INDEX [aslt_serverjobs] ON [smart_serverjobs]([assignedserverid], [locktoken]) ;
CREATE  INDEX [paged_results] ON [smart_serverjobs]([queuetime], [servertype], [jobtype], [jobstatus], [actinguser]) ;

CREATE TABLE [smart_serverjobtypesonhold] (
  [guid] varchar(40) NOT NULL  default '',
  [jobtype] varchar(32) NOT NULL  default '',
  [retrytimestamp] varchar(20) NOT NULL  default '',
  PRIMARY KEY ([guid])
);
CREATE  INDEX [jobtype] ON [smart_serverjobtypesonhold]([jobtype]) ;
CREATE  INDEX [retrytime] ON [smart_serverjobtypesonhold]([retrytimestamp]) ;

CREATE TABLE [smart_serverjobconfigs] (
  [id] int NOT NULL  IDENTITY(1,1),
  [jobtype] varchar(32) NOT NULL  default '',
  [servertype] varchar(32) NOT NULL  default '',
  [attempts] int NOT NULL  default 0,
  [active] char(1) NOT NULL  default 'N',
  [sysadmin] char(1) NOT NULL  default '-',
  [userid] int NOT NULL  default 0,
  [userconfigneeded] char(1) NOT NULL  default 'Y',
  [recurring] char(1) NOT NULL  default 'N',
  [selfdestructive] char(1) NOT NULL  default 'N',
  [workingdays] char(1) NOT NULL  default 'N',
  [dailystarttime] varchar(30) NOT NULL  default '00-00-00T00:00:00',
  [dailystoptime] varchar(30) NOT NULL  default '00-00-00T00:00:00',
  [timeinterval] int NOT NULL  default 0,
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_serverjobsupports] (
  [id] int NOT NULL  IDENTITY(1,1),
  [serverid] int NOT NULL  default 0,
  [jobconfigid] int NOT NULL  default 0,
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [sjs_serverconfigs] ON [smart_serverjobsupports]([serverid], [jobconfigid]) ;

CREATE TABLE [smart_serverplugins] (
  [id] int NOT NULL  IDENTITY(1,1),
  [uniquename] varchar(64) NOT NULL  default '',
  [displayname] varchar(128) NOT NULL  default '',
  [version] varchar(64) NOT NULL  default '',
  [description] varchar(255) NOT NULL  default '',
  [copyright] varchar(128) NOT NULL  default '',
  [active] char(2) NOT NULL  default '',
  [system] char(2) NOT NULL  default '',
  [installed] char(2) NOT NULL  default '',
  [modified] varchar(30) NOT NULL  default '',
  [dbprefix] varchar(10) NOT NULL  default '',
  [dbversion] varchar(10) NOT NULL  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_serverplugins] ON
INSERT INTO [smart_serverplugins] ([id], [uniquename], [displayname], [version], [description], [copyright], [active], [system], [installed], [modified], [dbprefix], [dbversion]) VALUES (1, 'PreviewMetaPHP', 'PHP Preview and Meta Data', 'v6.1', 'Using internal PHP libraries (such as GD) to generate previews and read metadata', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', 'on', 'on', 'on', '2008-10-02T09:00:00', '', '');
INSERT INTO [smart_serverplugins] ([id], [uniquename], [displayname], [version], [description], [copyright], [active], [system], [installed], [modified], [dbprefix], [dbversion]) VALUES (2, 'ImageMagick', 'ImageMagick', 'v6.1', 'Use ImageMagick to support extra formats for preview generation', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', '', 'on', '', '2008-10-02T09:00:00', '', '');
INSERT INTO [smart_serverplugins] ([id], [uniquename], [displayname], [version], [description], [copyright], [active], [system], [installed], [modified], [dbprefix], [dbversion]) VALUES (3, 'InCopyHTMLConversion', 'InCopy HTML Conversion', 'v6.1', 'Have InCopy and InDesign edit HTML articles by converting the article to text', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', 'on', 'on', 'on', '2008-11-30T09:00:00', '', '');
SET IDENTITY_INSERT [smart_serverplugins] OFF

CREATE TABLE [smart_serverconnectors] (
  [id] int NOT NULL  IDENTITY(1,1),
  [pluginid] int NOT NULL  default '0',
  [classname] varchar(128) NOT NULL  default '',
  [interface] varchar(128) NOT NULL  default '',
  [type] varchar(32) NOT NULL  default '',
  [prio] int NOT NULL  default '0',
  [runmode] varchar(16) NOT NULL  default '',
  [classfile] varchar(255) NOT NULL  default '',
  [modified] varchar(30) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [seco_pluginid] ON [smart_serverconnectors]([pluginid]) ;
CREATE  INDEX [seco_typeinterface] ON [smart_serverconnectors]([type], [interface]) ;
SET IDENTITY_INSERT [smart_serverconnectors] ON
INSERT INTO [smart_serverconnectors] ([id], [pluginid], [classname], [interface], [type], [prio], [runmode], [classfile], [modified]) VALUES (1, 1, 'PreviewMetaPHP_Preview', 'Preview', '', 500, 'Synchron', '/server/plugins/PreviewMetaPHP/PreviewMetaPHP_Preview.class.php', '2008-10-02T09:00:00');
INSERT INTO [smart_serverconnectors] ([id], [pluginid], [classname], [interface], [type], [prio], [runmode], [classfile], [modified]) VALUES (2, 1, 'PreviewMetaPHP_MetaData', 'MetaData', '', 500, 'Synchron', '/server/plugins/PreviewMetaPHP/PreviewMetaPHP_MetaData.class.php', '2008-10-02T09:00:00');
INSERT INTO [smart_serverconnectors] ([id], [pluginid], [classname], [interface], [type], [prio], [runmode], [classfile], [modified]) VALUES (3, 3, 'InCopyHTMLConversion_WflGetObjects', 'WflGetObjects', 'WorkflowService', 500, 'After', '/server/plugins/InCopyHTMLConversion/InCopyHTMLConversion_WflGetObjects.class.php', '2008-11-30T09:00:00');
SET IDENTITY_INSERT [smart_serverconnectors] OFF

CREATE TABLE [smart_semaphores] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [entityid] varchar(40) NOT NULL  default '0',
  [lastupdate] int NOT NULL  default '0',
  [lifetime] int NOT NULL  default '0',
  [user] varchar(40) NOT NULL  default '',
  [ip] varchar(30) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [idx_entity] ON [smart_semaphores]([entityid]) ;
CREATE  INDEX [idx_entityuser] ON [smart_semaphores]([entityid], [user]) ;

CREATE TABLE [smart_outputdevices] (
  [id] int NOT NULL  IDENTITY(1,1),
  [name] varchar(255) NOT NULL  default '',
  [code] int NOT NULL  default '0',
  [description] text NOT NULL  default '',
  [landscapewidth] int NOT NULL  default '0',
  [landscapeheight] int NOT NULL  default '0',
  [portraitwidth] int NOT NULL  default '0',
  [portraitheight] int NOT NULL  default '0',
  [previewquality] int NOT NULL  default '0',
  [landscapelayoutwidth] real NOT NULL  default '0',
  [pixeldensity] int NOT NULL  default '0',
  [pngcompression] int NOT NULL  default '0',
  [textviewpadding] varchar(50) NOT NULL  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_outputdevices] ON
INSERT INTO [smart_outputdevices] ([id], [name], [code], [description], [landscapewidth], [landscapeheight], [portraitwidth], [portraitheight], [previewquality], [landscapelayoutwidth], [pixeldensity], [pngcompression], [textviewpadding]) VALUES (1, 'iPad - DM', 0, '', 1024, 748, 768, 1004, 4, 558.5, 132, 9, '');
INSERT INTO [smart_outputdevices] ([id], [name], [code], [description], [landscapewidth], [landscapeheight], [portraitwidth], [portraitheight], [previewquality], [landscapelayoutwidth], [pixeldensity], [pngcompression], [textviewpadding]) VALUES (2, 'iPad', 10, '', 1024, 768, 768, 1024, 4, 1024, 132, 9, '');
INSERT INTO [smart_outputdevices] ([id], [name], [code], [description], [landscapewidth], [landscapeheight], [portraitwidth], [portraitheight], [previewquality], [landscapelayoutwidth], [pixeldensity], [pngcompression], [textviewpadding]) VALUES (3, 'Kindle Fire', 20, '', 1024, 600, 600, 1024, 4, 1024, 169, 9, '');
INSERT INTO [smart_outputdevices] ([id], [name], [code], [description], [landscapewidth], [landscapeheight], [portraitwidth], [portraitheight], [previewquality], [landscapelayoutwidth], [pixeldensity], [pngcompression], [textviewpadding]) VALUES (4, 'Xoom', 30, '', 1280, 800, 800, 1280, 4, 1280, 160, 9, '');
SET IDENTITY_INSERT [smart_outputdevices] OFF

CREATE TABLE [smart_placementtiles] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [placementid] bigint NOT NULL  default '0',
  [pagesequence] int NOT NULL  default '0',
  [left] real NOT NULL  default '0',
  [top] real NOT NULL  default '0',
  [width] real NOT NULL  default '0',
  [height] real NOT NULL  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pi_placementtiles] ON [smart_placementtiles]([placementid]) ;

CREATE TABLE [smart_objectlabels] (
  [id] bigint NOT NULL  IDENTITY(1,1),
  [objid] bigint NOT NULL  default '0',
  [name] varchar(250) NOT NULL  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [objlabels_objid] ON [smart_objectlabels]([objid]) ;

CREATE TABLE [smart_objectrelationlabels] (
  [labelid] bigint NOT NULL  default '0',
  [childobjid] bigint NOT NULL  default '0',
  PRIMARY KEY ([labelid], [childobjid])
);
CREATE  INDEX [objrellabels_childobjid] ON [smart_objectrelationlabels]([childobjid]) ;

CREATE TABLE [smart_channeldata] (
  [publication] int NOT NULL  default '0',
  [pubchannel] int NOT NULL  default '0',
  [issue] int NOT NULL  default '0',
  [section] int NOT NULL  default '0',
  [name] varchar(200) NOT NULL  default '',
  [value] text NOT NULL  default '',
  PRIMARY KEY ([publication], [pubchannel], [issue], [section], [name])
);
UPDATE [smart_config] set [value] = '10.4' where [name] = 'version';
