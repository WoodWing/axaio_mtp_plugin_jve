
CREATE TABLE [smart_actionproperties] (
  [id] int not null  IDENTITY(1,1),
  [publication] int not null  default '0',
  [orderid] int not null  default '0',
  [property] varchar(200) not null  default '',
  [edit] char(2) not null  default '',
  [mandatory] char(2) not null  default '',
  [action] varchar(40) not null  default '',
  [type] varchar(40) not null  default '',
  [restricted] char(2) not null  default '',
  [refreshonchange] char(2) not null  default '',
  [parentfieldid] int not null  default '0',
  [documentid] varchar(512) not null  default '',
  [initialheight] int not null  default '0',
  [multipleobjects] char(2) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pbac_actionproperties] on [smart_actionproperties]([publication], [action]) ;

CREATE TABLE [smart_authorizations] (
  [id] int not null  IDENTITY(1,1),
  [grpid] int not null  default '0',
  [publication] int not null  default '0',
  [section] int not null  default '0',
  [state] int not null  default '0',
  [rights] varchar(40) not null  default '',
  [issue] int not null  default '0',
  [profile] int not null  default '0',
  [bundle] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [gipu_authorizations] on [smart_authorizations]([grpid], [publication]) ;
CREATE  INDEX [gipr_authorizations] on [smart_authorizations]([grpid], [profile]) ;
SET IDENTITY_INSERT [smart_authorizations] ON
INSERT INTO [smart_authorizations] ([id], [grpid], [publication], [section], [state], [rights], [issue], [profile], [bundle]) VALUES (1, 2, 1, 0, 0, 'VRWDCKSF', 0, 1, 0);
SET IDENTITY_INSERT [smart_authorizations] OFF

CREATE TABLE [smart_config] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(200) not null  default '',
  [value] text not null  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_config] ON
INSERT INTO [smart_config] ([id], [name], [value]) VALUES (1, 'version', '00');
SET IDENTITY_INSERT [smart_config] OFF

CREATE TABLE [smart_deletedobjects] (
  [id] int not null  IDENTITY(1,1),
  [documentid] varchar(512) not null  default '',
  [type] varchar(20) not null  default '',
  [name] varchar(255) not null  default '',
  [publication] int not null  default '0',
  [issue] int not null  default '0',
  [section] int not null  default '0',
  [state] int not null  default '0',
  [routeto] varchar(255) not null  default '',
  [copyright] varchar(255) not null  default '',
  [slugline] varchar(255) not null  default '',
  [comment] varchar(255) not null  default '',
  [author] varchar(255) not null  default '',
  [deadline] varchar(30) not null  default '',
  [urgency] varchar(40) not null  default '',
  [format] varchar(128) not null  default '',
  [width] real not null  default '0',
  [depth] real not null  default '0',
  [dpi] real not null  default '0',
  [lengthwords] int not null  default '0',
  [lengthchars] int not null  default '0',
  [lengthparas] int not null  default '0',
  [lengthlines] int not null  default '0',
  [keywords] text not null  default '',
  [modifier] varchar(40) not null  default '',
  [modified] varchar(30) not null  default '',
  [creator] varchar(40) not null  default '',
  [created] varchar(30) not null  default '',
  [deletor] varchar(40) not null  default '',
  [deleted] varchar(30) not null  default '',
  [copyrightmarked] varchar(255) not null  default '',
  [copyrighturl] varchar(255) not null  default '',
  [credit] varchar(255) not null  default '',
  [source] varchar(255) not null  default '',
  [description] text not null  default '',
  [descriptionauthor] varchar(255) not null  default '',
  [_columns] int not null  default '0',
  [plaincontent] text not null  default '',
  [filesize] int not null  default '0',
  [colorspace] varchar(20) not null  default '',
  [pagenumber] int not null  default '0',
  [types] text not null  default '',
  [storename] text not null  default '',
  [pagerange] varchar(50) not null  default '',
  [highresfile] varchar(255) not null  default '',
  [deadlinesoft] varchar(30) not null  default '',
  [deadlinechanged] char(1) not null  default '',
  [plannedpagerange] varchar(50) not null  default '',
  [majorversion] int not null  default '-1',
  [minorversion] int not null  default '0',
  [encoding] varchar(100) not null  default '',
  [compression] varchar(100) not null  default '',
  [keyframeeveryframes] int not null  default '0',
  [channels] varchar(100) not null  default '',
  [aspectratio] varchar(100) not null  default '',
  [contentsource] varchar(100) not null  default '',
  [rating] tinyint not null  default 0,
  [indexed] char(2) not null  default '',
  [closed] char(2) not null  default '',
  [orientation] tinyint not null  default '0',
  [routetouserid] int not null  default '0',
  [routetogroupid] int not null  default '0',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_groups] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(100) not null  default '',
  [descr] varchar(255) not null  default '',
  [admin] char(2) not null  default '',
  [routing] char(2) not null  default '',
  [externalid] varchar(200) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [idnaro_groups] on [smart_groups]([id], [name], [routing]) ;
CREATE  INDEX [na_groups] on [smart_groups]([name]) ;
SET IDENTITY_INSERT [smart_groups] ON
INSERT INTO [smart_groups] ([id], [name], [descr], [admin], [routing], [externalid]) VALUES (2, 'admin', 'System Admins', 'on', '', '');
SET IDENTITY_INSERT [smart_groups] OFF

CREATE TABLE [smart_log] (
  [id] int not null  IDENTITY(1,1),
  [user] varchar(50) not null  default '',
  [service] varchar(50) not null  default '',
  [ip] varchar(30) not null  default '',
  [date] varchar(30) not null  default '',
  [objectid] int not null  default '0',
  [publication] int not null  default '0',
  [issue] int not null  default '0',
  [section] int not null  default '0',
  [state] int not null  default '0',
  [parent] int not null  default '0',
  [lock] varchar(1) not null  default '',
  [rendition] varchar(10) not null  default '',
  [type] varchar(20) not null  default '',
  [routeto] varchar(255) not null  default '',
  [edition] varchar(255) not null  default '',
  [minorversion] int not null  default '0',
  [channelid] int not null  default '0',
  [majorversion] int not null  default '-1',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_namedqueries] (
  [id] int not null  IDENTITY(1,1),
  [query] varchar(200) not null  default '',
  [interface] text not null  default '',
  [sql] text not null  default '',
  [comment] text not null  default '',
  [checkaccess] varchar(2) not null  default 'on',
  PRIMARY KEY ([id])
);
CREATE  INDEX [qe_namedqueries] on [smart_namedqueries]([query]) ;
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
  [id] int not null  IDENTITY(1,1),
  [object] int not null  default '0',
  [usr] varchar(40) not null  default '',
  [timestamp] timestamp not null ,
  [ip] varchar(30) not null  default '',
  [lockoffline] varchar(2) not null  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [ob_objectlocks] on [smart_objectlocks]([object]) ;
CREATE  INDEX [obusr_objectlocks] on [smart_objectlocks]([object], [usr]) ;

CREATE TABLE [smart_objectrelations] (
  [id] int not null  IDENTITY(1,1),
  [parent] int not null  default '0',
  [child] int not null  default '0',
  [type] varchar(40) not null  default '',
  [subid] varchar(20) not null  default '',
  [pagerange] varchar(50) not null  default '',
  [rating] tinyint not null  default 0,
  [parenttype] varchar(20) not null  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [ch_objectrelations] on [smart_objectrelations]([parent], [child], [subid], [type]) ;
CREATE  INDEX [pachty_objectrelations] on [smart_objectrelations]([parent], [child], [type]) ;
CREATE  INDEX [child_type_id] on [smart_objectrelations]([child], [type], [id]) ;

CREATE TABLE [smart_objects] (
  [id] int not null  IDENTITY(1,1),
  [documentid] varchar(512) not null  default '',
  [type] varchar(20) not null  default '',
  [name] varchar(255) not null  default '',
  [publication] int not null  default '0',
  [issue] int not null  default '0',
  [section] int not null  default '0',
  [state] int not null  default '0',
  [routeto] varchar(255) not null  default '',
  [copyright] varchar(255) not null  default '',
  [slugline] varchar(255) not null  default '',
  [comment] varchar(255) not null  default '',
  [author] varchar(255) not null  default '',
  [deadline] varchar(30) not null  default '',
  [urgency] varchar(40) not null  default '',
  [format] varchar(128) not null  default '',
  [width] real not null  default '0',
  [depth] real not null  default '0',
  [dpi] real not null  default '0',
  [lengthwords] int not null  default '0',
  [lengthchars] int not null  default '0',
  [lengthparas] int not null  default '0',
  [lengthlines] int not null  default '0',
  [keywords] text not null  default '',
  [modifier] varchar(40) not null  default '',
  [modified] varchar(30) not null  default '',
  [creator] varchar(40) not null  default '',
  [created] varchar(30) not null  default '',
  [deletor] varchar(40) not null  default '',
  [deleted] varchar(30) not null  default '',
  [copyrightmarked] varchar(255) not null  default '',
  [copyrighturl] varchar(255) not null  default '',
  [credit] varchar(255) not null  default '',
  [source] varchar(255) not null  default '',
  [description] text not null  default '',
  [descriptionauthor] varchar(255) not null  default '',
  [_columns] int not null  default '0',
  [plaincontent] text not null  default '',
  [filesize] int not null  default '0',
  [colorspace] varchar(20) not null  default '',
  [types] text not null  default '',
  [pagenumber] int not null  default '0',
  [storename] text not null  default '',
  [pagerange] varchar(50) not null  default '',
  [highresfile] varchar(255) not null  default '',
  [deadlinesoft] varchar(30) not null  default '',
  [deadlinechanged] char(1) not null  default '',
  [plannedpagerange] varchar(50) not null  default '',
  [majorversion] int not null  default '-1',
  [minorversion] int not null  default '0',
  [encoding] varchar(100) not null  default '',
  [compression] varchar(100) not null  default '',
  [keyframeeveryframes] int not null  default '0',
  [channels] varchar(100) not null  default '',
  [aspectratio] varchar(100) not null  default '',
  [contentsource] varchar(100) not null  default '',
  [rating] tinyint not null  default 0,
  [indexed] char(2) not null  default '',
  [closed] char(2) not null  default '',
  [routetouserid] int not null  default '0',
  [routetogroupid] int not null  default '0',
  [orientation] tinyint not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [nm_objects] on [smart_objects]([name]) ;
CREATE  INDEX [pbsectstate_objects] on [smart_objects]([publication], [section], [state], [closed]) ;
CREATE  INDEX [pubid_objects] on [smart_objects]([publication], [id], [closed]) ;
CREATE  INDEX [mo_objects] on [smart_objects]([modifier]) ;
CREATE  INDEX [roid_objects] on [smart_objects]([routeto], [id], [closed]) ;
CREATE  INDEX [codo_objects] on [smart_objects]([contentsource], [documentid]) ;

CREATE TABLE [smart_objectversions] (
  [id] int not null  IDENTITY(1,1),
  [objid] int not null  default '0',
  [minorversion] int not null  default '0',
  [modifier] varchar(40) not null  default '',
  [comment] varchar(255) not null  default '',
  [slugline] varchar(255) not null  default '',
  [created] varchar(30) not null  default '',
  [types] text not null  default '',
  [format] varchar(128) not null  default '',
  [width] real not null  default '0',
  [depth] real not null  default '0',
  [dpi] real not null  default '0',
  [lengthwords] int not null  default '0',
  [lengthchars] int not null  default '0',
  [lengthparas] int not null  default '0',
  [lengthlines] int not null  default '0',
  [keywords] text not null  default '',
  [description] text not null  default '',
  [descriptionauthor] varchar(255) not null  default '',
  [_columns] int not null  default '0',
  [plaincontent] text not null  default '',
  [filesize] int not null  default '0',
  [colorspace] varchar(20) not null  default '',
  [orientation] tinyint not null  default '0',
  [state] int not null  default '0',
  [majorversion] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [oive_objectversions] on [smart_objectversions]([objid], [majorversion], [minorversion]) ;

CREATE TABLE [smart_objectrenditions] (
  [id] int not null  IDENTITY(1,1),
  [objid] int not null  default '0',
  [editionid] int not null  default '0',
  [rendition] varchar(10) not null  default '',
  [format] varchar(128) not null  default '',
  [majorversion] int not null  default '0',
  [minorversion] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [obed_objectrenditions] on [smart_objectrenditions]([objid], [editionid], [rendition]) ;

CREATE TABLE [smart_pages] (
  [id] int not null  IDENTITY(1,1),
  [objid] int not null  default '0',
  [width] real not null  default '0',
  [height] real not null  default '0',
  [pagenumber] varchar(20) not null  default '',
  [pageorder] int not null  default '0',
  [nr] int not null  default '0',
  [types] text not null  default '',
  [edition] int not null  default '0',
  [master] varchar(255) not null  default '',
  [instance] varchar(40) not null  default 'Production',
  [pagesequence] int not null  default '0',
  [orientation] varchar(9) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [obpaed_pages] on [smart_pages]([objid], [pageorder], [edition]) ;

CREATE TABLE [smart_placements] (
  [id] int not null  IDENTITY(1,1),
  [parent] int not null  default '0',
  [child] int not null  default '0',
  [page] int not null  default '0',
  [element] varchar(200) not null  default '',
  [elementid] varchar(200) not null  default '',
  [frameorder] int not null  default '0',
  [frameid] varchar(200) not null  default '',
  [_left] real not null  default '0',
  [top] real not null  default '0',
  [width] real not null  default '0',
  [height] real not null  default '0',
  [overset] real not null  default '0',
  [oversetchars] int not null  default '0',
  [oversetlines] int not null  default '0',
  [layer] varchar(200) not null  default '',
  [content] text not null  default '',
  [type] varchar(40) not null ,
  [edition] int not null  default '0',
  [contentdx] real not null  default 0,
  [contentdy] real not null  default 0,
  [scalex] real not null  default 1,
  [scaley] real not null  default 1,
  [pagesequence] int not null  default '0',
  [pagenumber] varchar(20) not null  default '',
  [formwidgetid] varchar(200) not null  default '',
  [frametype] varchar(20) not null  default '',
  [splineid] varchar(200) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pachty_placements] on [smart_placements]([parent], [child], [type]) ;
CREATE  INDEX [ei_placements] on [smart_placements]([elementid]) ;
CREATE  INDEX [chty_placements] on [smart_placements]([child], [type]) ;

CREATE TABLE [smart_elements] (
  [id] int not null  IDENTITY(1,1),
  [guid] varchar(200) not null  default '',
  [name] varchar(200) not null  default '',
  [objid] int not null  default 0,
  [lengthwords] int not null  default '0',
  [lengthchars] int not null  default '0',
  [lengthparas] int not null  default '0',
  [lengthlines] int not null  default '0',
  [snippet] varchar(255) not null  default '',
  [version] varchar(50) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [oigu_elements] on [smart_elements]([objid], [guid]) ;

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

CREATE TABLE [smart_properties] (
  [id] int not null  IDENTITY(1,1),
  [publication] int not null  default '0',
  [objtype] varchar(40) not null  default '',
  [name] varchar(200) not null  default '',
  [dispname] varchar(200) not null  default '',
  [category] varchar(200) not null  default '',
  [type] varchar(40) not null  default '',
  [defaultvalue] varchar(200) not null  default '',
  [valuelist] text not null  default '',
  [minvalue] varchar(200) not null  default '',
  [maxvalue] varchar(200) not null  default '',
  [maxlen] bigint not null  default '0',
  [dbupdated] tinyint not null  default '0',
  [entity] varchar(20) not null  default 'Object',
  [serverplugin] varchar(64) not null  default '',
  [adminui] varchar(2) not null  default 'on',
  [propertyvalues] text not null  default '',
  [minresolution] varchar(200) not null  default '',
  [maxresolution] varchar(200) not null  default '',
  [publishsystem] varchar(64) not null  default '',
  [templateid] int not null  default 0,
  [termentityid] int not null  default '0',
  [suggestionentity] varchar(200) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [puob_properties] on [smart_properties]([publication], [objtype]) ;
CREATE  INDEX [pudb_properties] on [smart_properties]([publication], [dbupdated]) ;

CREATE TABLE [smart_publadmin] (
  [id] int not null  IDENTITY(1,1),
  [publication] int not null  default '0',
  [grpid] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pugi_publadmin] on [smart_publadmin]([publication], [grpid]) ;

CREATE TABLE [smart_publications] (
  [id] int not null  IDENTITY(1,1),
  [publication] varchar(255) not null  default '',
  [code] int not null  default '0',
  [email] char(2) not null  default '',
  [description] text not null  default '',
  [readingorderrev] varchar(2) not null  default '',
  [autopurge] int not null  default 0,
  [defaultchannelid] int not null  default '0',
  [calculatedeadlines] char(2) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pb_publications] on [smart_publications]([publication]) ;
CREATE  INDEX [idpb_publications] on [smart_publications]([id], [publication]) ;
SET IDENTITY_INSERT [smart_publications] ON
INSERT INTO [smart_publications] ([id], [publication], [code], [email], [description], [readingorderrev], [autopurge], [defaultchannelid], [calculatedeadlines]) VALUES (1, 'WW News', 0, '', '', '', 0, 1, '');
SET IDENTITY_INSERT [smart_publications] OFF

CREATE TABLE [smart_publsections] (
  [id] int not null  IDENTITY(1,1),
  [publication] int not null  default '0',
  [section] varchar(255) not null  default '',
  [issue] int not null  default '0',
  [code] int not null  default '0',
  [description] text not null  default '',
  [pages] int not null  default '0',
  [deadline] varchar(30) not null  default '',
  [deadlinerelative] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pbis_publsections] on [smart_publsections]([publication], [issue]) ;
CREATE  INDEX [se_publsections] on [smart_publsections]([section]) ;
SET IDENTITY_INSERT [smart_publsections] ON
INSERT INTO [smart_publsections] ([id], [publication], [section], [issue], [code], [description], [pages], [deadline], [deadlinerelative]) VALUES (1, 1, 'News', 0, 10, '', 0, '', 0 );
INSERT INTO [smart_publsections] ([id], [publication], [section], [issue], [code], [description], [pages], [deadline], [deadlinerelative]) VALUES (2, 1, 'Sport', 0, 20, '', 0, '', 0 );
SET IDENTITY_INSERT [smart_publsections] OFF

CREATE TABLE [smart_publobjects] (
  [id] int not null  IDENTITY(1,1),
  [publicationid] int not null  default '0',
  [issueid] int not null  default '0',
  [objectid] int not null  default '0',
  [grpid] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [puisobgr_publobjects] on [smart_publobjects]([publicationid], [issueid], [objectid], [grpid]) ;

CREATE TABLE [smart_issueeditions] (
  [id] int not null  IDENTITY(1,1),
  [issue] int not null  default '0',
  [edition] int not null  default '0',
  [deadline] varchar(30) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [is_issueeditions] on [smart_issueeditions]([issue]) ;
CREATE  INDEX [ed_issueeditions] on [smart_issueeditions]([edition]) ;

CREATE TABLE [smart_routing] (
  [id] int not null  IDENTITY(1,1),
  [publication] int not null  default '0',
  [section] int not null  default '0',
  [state] int not null  default '0',
  [routeto] varchar(255) not null  default '',
  [issue] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pbisse_routing] on [smart_routing]([publication], [issue], [section]) ;
CREATE  INDEX [st_routing] on [smart_routing]([state]) ;

CREATE TABLE [smart_settings] (
  [id] int not null  IDENTITY(1,1),
  [user] varchar(200) not null  default '',
  [setting] varchar(200) not null  default '',
  [value] text not null  default '',
  [appname] varchar(200) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [us_settings] on [smart_settings]([user]) ;
CREATE  INDEX [se_settings] on [smart_settings]([setting]) ;

CREATE TABLE [smart_states] (
  [id] int not null  IDENTITY(1,1),
  [publication] int not null  default '0',
  [type] varchar(40) not null  default '',
  [state] varchar(40) not null  default '',
  [produce] char(2) not null  default '',
  [color] varchar(11) not null  default '',
  [nextstate] int not null  default '0',
  [code] int not null  default '0',
  [issue] int not null  default '0',
  [section] int not null  default '0',
  [deadlinestate] int not null  default '0',
  [deadlinerelative] int not null  default '0',
  [createpermanentversion] char(2) not null  default '',
  [removeintermediateversions] char(2) not null  default '',
  [readyforpublishing] char(2) not null  default '',
  [automaticallysendtonext] char(2) not null  default '',
  [phase] varchar(40) not null  default 'Production',
  [skipidsa] char(2) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [st_states] on [smart_states]([state]) ;
CREATE  INDEX [pbistyse_states] on [smart_states]([publication], [issue], [type], [section]) ;
CREATE  INDEX [istyse_states] on [smart_states]([issue], [type], [section]) ;
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
  [id] int not null  IDENTITY(1,1),
  [ticketid] varchar(40) not null  default '',
  [usr] varchar(40) not null  default '',
  [db] varchar(255) not null  default '',
  [clientname] varchar(255) not null  default '',
  [clientip] varchar(40) not null  default '',
  [appname] varchar(200) not null  default '',
  [appversion] varchar(200) not null  default '',
  [appserial] varchar(200) not null  default '',
  [logon] varchar(20) not null  default '',
  [expire] varchar(30) not null  default '',
  [appproductcode] varchar(40) not null  default '',
  [masterticketid] varchar(40) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [ti_tickets] on [smart_tickets]([ticketid]) ;
CREATE  INDEX [us_tickets] on [smart_tickets]([usr]) ;
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

CREATE TABLE [smart_users] (
  [id] int not null  IDENTITY(1,1),
  [user] varchar(40) not null  default '',
  [fullname] varchar(255) not null  default '',
  [pass] varchar(128) not null  default '',
  [disable] char(2) not null  default '',
  [fixedpass] char(2) not null  default '',
  [email] varchar(100) not null  default '',
  [emailgrp] char(2) not null  default '',
  [emailusr] char(2) not null  default '',
  [language] varchar(4) not null  default '',
  [startdate] varchar(30) not null  default '',
  [enddate] varchar(30) not null  default '',
  [expirepassdate] varchar(30) not null  default '',
  [expiredays] int not null  default '0',
  [trackchangescolor] varchar(11) not null  default '',
  [lastlogondate] varchar(30) not null  default '',
  [organization] varchar(255) not null  default '',
  [location] varchar(255) not null  default '',
  [externalid] varchar(200) not null  default '',
  [importonlogon] char(2) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [us_users] on [smart_users]([user]) ;
CREATE  INDEX [fu_users] on [smart_users]([fullname]) ;
SET IDENTITY_INSERT [smart_users] ON
INSERT INTO [smart_users] ([id], [user], [fullname], [pass], [disable], [fixedpass], [email], [emailgrp], [emailusr], [language], [startdate], [enddate], [expirepassdate], [expiredays], [trackchangescolor], [lastlogondate], [organization], [location], [externalid], [importonlogon]) VALUES (1, 'woodwing', 'WoodWing Software', '', '', '', '', '', '', 'enUS', '', '', '', 0, '#FF0000', '', '', '', '', '' );
SET IDENTITY_INSERT [smart_users] OFF

CREATE TABLE [smart_usrgrp] (
  [id] int not null  IDENTITY(1,1),
  [usrid] int not null  default '0',
  [grpid] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [usgi_usrgrp] on [smart_usrgrp]([usrid], [grpid]) ;
CREATE  INDEX [gi_usrgrp] on [smart_usrgrp]([grpid]) ;
SET IDENTITY_INSERT [smart_usrgrp] ON
INSERT INTO [smart_usrgrp] ([id], [usrid], [grpid]) VALUES (2, 1, 2);
SET IDENTITY_INSERT [smart_usrgrp] OFF

CREATE TABLE [smart_mtp] (
  [publid] int not null ,
  [issueid] int not null  default '0',
  [laytriggerstate] int not null ,
  [arttriggerstate] int not null  default 0,
  [imgtriggerstate] int not null  default 0,
  [layprogstate] int not null  default 0,
  [artprogstate] int not null  default 0,
  [imgprogstate] int not null  default 0,
  [mtptext] text not null  default '',
  PRIMARY KEY ([publid], [issueid], [laytriggerstate])
);
CREATE  INDEX [ii_mtp] on [smart_mtp]([issueid]) ;

CREATE TABLE [smart_mtpsentobjects] (
  [objid] int not null  default '0',
  [publid] int not null ,
  [issueid] int not null  default '0',
  [laytriggerstate] int not null ,
  [printstate] int not null ,
  PRIMARY KEY ([objid], [publid], [issueid], [laytriggerstate], [printstate])
);
CREATE  INDEX [ii_mtpsentobjects] on [smart_mtpsentobjects]([issueid]) ;
CREATE  INDEX [ls_mtpsentobjects] on [smart_mtpsentobjects]([laytriggerstate]) ;

CREATE TABLE [smart_messagelog] (
  [id] bigint not null  IDENTITY(1,1),
  [objid] int not null  default 0,
  [userid] int not null  default 0,
  [messagetype] varchar(255) not null ,
  [messagetypedetail] varchar(255) not null ,
  [message] text not null  default '',
  [date] varchar(30) not null  default '',
  [expirationdate] varchar(30) not null  default '',
  [messagelevel] varchar(255) not null  default '',
  [fromuser] varchar(255) not null  default '',
  [msgid] varchar(200) not null  default '',
  [anchorx] real not null  default '0',
  [anchory] real not null  default '0',
  [left] real not null  default '0',
  [top] real not null  default '0',
  [width] real not null  default '0',
  [height] real not null  default '0',
  [page] int not null  default '0',
  [version] varchar(200) not null  default '',
  [color] varchar(11) not null  default '',
  [pagesequence] int not null  default '0',
  [threadmessageid] varchar(200) not null  default '',
  [replytomessageid] varchar(200) not null  default '',
  [messagestatus] varchar(15) not null  default 'None',
  [majorversion] int not null  default '0',
  [minorversion] int not null  default '0',
  [isread] varchar(2) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [oimtpa_messagelog] on [smart_messagelog]([objid], [messagetype], [page]) ;
CREATE  INDEX [oimtd_messagelog] on [smart_messagelog]([objid], [messagetypedetail]) ;
CREATE  INDEX [mi_messagelog] on [smart_messagelog]([msgid]) ;
CREATE  INDEX [uid_messagelog] on [smart_messagelog]([userid]) ;

CREATE TABLE [smart_objectflags] (
  [objid] int not null ,
  [flagorigin] varchar(255) not null ,
  [flag] int not null ,
  [severity] int not null ,
  [message] text not null  default '',
  [locked] int not null  default 0,
  PRIMARY KEY ([objid], [flagorigin], [flag])
);

CREATE TABLE [smart_issuesection] (
  [id] int not null  IDENTITY(1,1),
  [issue] int not null  default '0',
  [section] int not null  default '0',
  [deadline] varchar(30) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [isse_issuesection] on [smart_issuesection]([issue], [section]) ;

CREATE TABLE [smart_issuesectionstate] (
  [id] int not null  IDENTITY(1,1),
  [issue] int not null  default '0',
  [section] int not null  default '0',
  [state] int not null  default '0',
  [deadline] varchar(30) not null  default '',
  [deadlinerelative] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [issest_issuesectionstate] on [smart_issuesectionstate]([issue], [section], [state]) ;

CREATE TABLE [smart_sectionstate] (
  [id] int not null  IDENTITY(1,1),
  [section] int not null  default '0',
  [state] int not null  default '0',
  [deadlinerelative] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [sest_sectionstate] on [smart_sectionstate]([section], [state]) ;

CREATE TABLE [smart_profiles] (
  [id] int not null  IDENTITY(1,1),
  [profile] varchar(255) not null  default '',
  [code] int not null  default '0',
  [description] text not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pr_profiles] on [smart_profiles]([profile]) ;
SET IDENTITY_INSERT [smart_profiles] ON
INSERT INTO [smart_profiles] ([id], [profile], [code], [description]) VALUES (1, 'Full Control', 0, 'All features enabled');
SET IDENTITY_INSERT [smart_profiles] OFF

CREATE TABLE [smart_profilefeatures] (
  [id] int not null  IDENTITY(1,1),
  [profile] int not null  default '0',
  [feature] int not null  default '0',
  [value] varchar(20) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [prfe_profiles] on [smart_profilefeatures]([profile], [feature]) ;
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

CREATE TABLE [smart_appsessions] (
  [id] int not null  IDENTITY(1,1),
  [sessionid] varchar(40) not null  default '',
  [userid] varchar(40) not null  default '',
  [appname] varchar(40) not null  default '',
  [lastsaved] varchar(20) not null  default '',
  [readonly] char(2) not null  default '',
  [articleid] int not null  default 0,
  [articlename] varchar(255) not null  default '',
  [articleformat] varchar(128) not null  default '',
  [articleminorversion] int not null  default 0,
  [templateid] int not null  default 0,
  [templatename] varchar(255) not null  default '',
  [templateformat] varchar(128) not null  default '',
  [layoutid] int not null  default 0,
  [layoutminorversion] int not null  default 0,
  [articlemajorversion] int not null  default 0,
  [layoutmajorversion] int not null  default 0,
  [dommajorversion] int not null  default '5',
  [domminorversion] int not null  default '0',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_datasources] (
  [id] int not null  IDENTITY(1,1),
  [type] varchar(255) not null  default '',
  [name] varchar(255) not null  default '',
  [bidirectional] char(2) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [na_datasources] on [smart_datasources]([name]) ;

CREATE TABLE [smart_dspublications] (
  [id] int not null  IDENTITY(1,1),
  [datasourceid] int not null  default '0',
  [publicationid] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [dsid_dspublications] on [smart_dspublications]([datasourceid]) ;
CREATE  INDEX [pubid_dspublications] on [smart_dspublications]([publicationid]) ;

CREATE TABLE [smart_dsqueries] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(255) not null  default '',
  [query] text not null  default '',
  [comment] text not null  default '',
  [interface] text not null  default '',
  [datasourceid] int not null  default '0',
  [recordid] varchar(255) not null  default '',
  [recordfamily] varchar(255) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [dsid_dsqueries] on [smart_dsqueries]([datasourceid]) ;

CREATE TABLE [smart_dsqueryfields] (
  [id] int not null  IDENTITY(1,1),
  [queryid] int not null  default '0',
  [priority] tinyint not null  default '0',
  [name] varchar(255) not null  default '',
  [readonly] tinyint not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [queryid_dsqueryfields] on [smart_dsqueryfields]([queryid]) ;

CREATE TABLE [smart_dssettings] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(255) not null  default '',
  [value] text not null  default '',
  [datasourceid] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [dsid_dssettings] on [smart_dssettings]([datasourceid]) ;

CREATE TABLE [smart_dsqueryplacements] (
  [id] int not null  IDENTITY(1,1),
  [objectid] int not null  default '0',
  [datasourceid] int not null  default '0',
  [dirty] char(2) not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [objid_dsqueryplacements] on [smart_dsqueryplacements]([objectid]) ;
CREATE  INDEX [dsid_dsqueryplacements] on [smart_dsqueryplacements]([datasourceid]) ;

CREATE TABLE [smart_dsqueryfamilies] (
  [id] int not null  IDENTITY(1,1),
  [queryplacementid] int not null  default '0',
  [familyfield] varchar(255) not null  default '',
  [familyvalue] text not null  default '',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_dsupdates] (
  [id] int not null  IDENTITY(1,1),
  [recordset] image not null  default '',
  [familyvalue] text not null  default '',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_dsobjupdates] (
  [id] int not null  IDENTITY(1,1),
  [updateid] int not null  default '0',
  [objectid] int not null  default '0',
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_channels] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(255) not null  default '',
  [publicationid] int not null  default '0',
  [type] varchar(32) not null  default 'print',
  [description] varchar(255) not null  default '',
  [code] int not null  default '0',
  [deadlinerelative] int not null  default '0',
  [currentissueid] int not null  default '0',
  [publishsystem] varchar(64) not null  default '',
  [suggestionprovider] varchar(64) not null  default '',
  [publishsystemid] varchar(40) not null  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_channels] ON
INSERT INTO [smart_channels] ([id], [name], [publicationid], [type], [description], [code], [deadlinerelative], [currentissueid], [publishsystem], [suggestionprovider], [publishsystemid]) VALUES (1, 'Print', 1, 'print', 'Print Channel', 10, 0, 1, '', '', '' );
INSERT INTO [smart_channels] ([id], [name], [publicationid], [type], [description], [code], [deadlinerelative], [currentissueid], [publishsystem], [suggestionprovider], [publishsystemid]) VALUES (2, 'Web', 1, 'web', 'Web Channel', 20, 0, 0, '', '', '' );
SET IDENTITY_INSERT [smart_channels] OFF

CREATE TABLE [smart_editions] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(255) not null  default '',
  [channelid] int not null  default '0',
  [issueid] int not null  default '0',
  [code] int not null  default '0',
  [deadlinerelative] int not null  default '0',
  [description] text not null  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_editions] ON
INSERT INTO [smart_editions] ([id], [name], [channelid], [issueid], [code], [deadlinerelative], [description]) VALUES (1, 'North', 1, 0, '10', '', '' );
INSERT INTO [smart_editions] ([id], [name], [channelid], [issueid], [code], [deadlinerelative], [description]) VALUES (2, 'South', 1, 0, '20', '', '' );
SET IDENTITY_INSERT [smart_editions] OFF

CREATE TABLE [smart_issues] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(255) not null  default '',
  [channelid] int not null  default '0',
  [overrulepub] char(2) not null  default '',
  [code] int not null  default '0',
  [publdate] varchar(30) not null  default '',
  [deadline] varchar(30) not null  default '',
  [pages] int not null  default '0',
  [subject] text not null  default '',
  [description] text not null  default '',
  [active] char(2) not null  default '',
  [readingorderrev] varchar(2) not null  default '',
  [calculatedeadlines] char(2) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [ch_issues] on [smart_issues]([channelid]) ;
CREATE  INDEX [na_issues] on [smart_issues]([name]) ;
SET IDENTITY_INSERT [smart_issues] ON
INSERT INTO [smart_issues] ([id], [name], [channelid], [overrulepub], [code], [publdate], [deadline], [pages], [subject], [description], [active], [readingorderrev], [calculatedeadlines]) VALUES (1, '1st Issue', 1, '', '10', '', '', 16, '', '', 'on', '', '' );
INSERT INTO [smart_issues] ([id], [name], [channelid], [overrulepub], [code], [publdate], [deadline], [pages], [subject], [description], [active], [readingorderrev], [calculatedeadlines]) VALUES (2, '2nd Issue', 1, '', '20', '', '', 16, '', '', 'on', 'on', '' );
INSERT INTO [smart_issues] ([id], [name], [channelid], [overrulepub], [code], [publdate], [deadline], [pages], [subject], [description], [active], [readingorderrev], [calculatedeadlines]) VALUES (3, 'webissue', 2, '', '10', '', '', 16, '', '', 'on', 'on', '' );
SET IDENTITY_INSERT [smart_issues] OFF

CREATE TABLE [smart_targets] (
  [id] int not null  IDENTITY(1,1),
  [objectid] int not null  default '0',
  [channelid] int not null  default '0',
  [issueid] int not null  default '0',
  [externalid] varchar(200) not null  default '',
  [objectrelationid] int not null  default '0',
  [publisheddate] varchar(30) not null  default '',
  [publishedmajorversion] int not null  default '0',
  [publishedminorversion] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [obchisobr_targets] on [smart_targets]([objectid], [channelid], [issueid], [objectrelationid]) ;
CREATE UNIQUE INDEX [obrobid_targets] on [smart_targets]([objectrelationid], [objectid], [id]) ;
CREATE  INDEX [issueid_targets] on [smart_targets]([issueid]) ;

CREATE TABLE [smart_publishhistory] (
  [id] int not null  IDENTITY(1,1),
  [externalid] varchar(200) not null  default '',
  [objectid] int not null  default '0',
  [channelid] int not null  default '0',
  [issueid] int not null  default '0',
  [editionid] int not null  default '0',
  [publisheddate] varchar(30) not null  default '',
  [fields] text not null  default '',
  [fieldsmajorversion] int not null  default '0',
  [fieldsminorversion] int not null  default '0',
  [actiondate] varchar(30) not null  default '',
  [action] varchar(20) not null  default '',
  [user] varchar(255) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [obchis_publhist] on [smart_publishhistory]([objectid], [channelid], [issueid]) ;
CREATE  INDEX [chis_publhist] on [smart_publishhistory]([channelid], [issueid]) ;

CREATE TABLE [smart_pubpublishedissues] (
  [id] int not null  IDENTITY(1,1),
  [externalid] varchar(200) not null  default '',
  [channelid] int not null  default '0',
  [issueid] int not null  default '0',
  [editionid] int not null  default '0',
  [report] text not null  default '',
  [dossierorder] text not null  default '',
  [publishdate] varchar(30) not null  default '',
  [issuemajorversion] int not null  default '0',
  [issueminorversion] int not null  default '0',
  [fields] text not null  default '',
  [fieldsmajorversion] int not null  default '0',
  [fieldsminorversion] int not null  default '0',
  [actiondate] varchar(30) not null  default '',
  [action] varchar(20) not null  default '',
  [userid] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [chised_publhist] on [smart_pubpublishedissues]([channelid], [issueid], [editionid]) ;

CREATE TABLE [smart_publishedobjectshist] (
  [id] int not null  IDENTITY(1,1),
  [objectid] int not null  default '0',
  [publishid] int not null  default '0',
  [majorversion] int not null  default '0',
  [minorversion] int not null  default '0',
  [externalid] varchar(200) not null  default '',
  [objectname] varchar(255) not null  default '',
  [objecttype] varchar(40) not null  default '',
  [objectformat] varchar(128) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [obpu_publobjhist] on [smart_publishedobjectshist]([objectid], [publishid]) ;
CREATE  INDEX [puob_publobjhist] on [smart_publishedobjectshist]([publishid], [objectid]) ;

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

CREATE TABLE [smart_targeteditions] (
  [id] int not null  IDENTITY(1,1),
  [targetid] int not null  default '0',
  [editionid] int not null  default '0',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [taed_targeteditions] on [smart_targeteditions]([targetid], [editionid]) ;
CREATE UNIQUE INDEX [edta_targeteditions] on [smart_targeteditions]([editionid], [targetid]) ;

CREATE TABLE [smart_indesignservers] (
  [id] int not null  IDENTITY(1,1),
  [hostname] varchar(64) not null  default '',
  [portnumber] int not null  default '0',
  [description] varchar(255) not null  default '',
  [active] char(2) not null  default '',
  [servermajorversion] int not null  default '5',
  [serverminorversion] int not null  default '0',
  [prio1] char(2) not null  default 'on',
  [prio2] char(2) not null  default 'on',
  [prio3] char(2) not null  default 'on',
  [prio4] char(2) not null  default 'on',
  [prio5] char(2) not null  default 'on',
  [locktoken] varchar(40) not null  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [hopo_indesignservers] on [smart_indesignservers]([hostname], [portnumber]) ;

CREATE TABLE [smart_indesignserverjobs] (
  [jobid] varchar(40) not null  default '',
  [foreground] char(2) not null  default '',
  [objid] int not null  default 0,
  [objectmajorversion] int not null  default '0',
  [objectminorversion] int not null  default '0',
  [jobtype] varchar(32) not null ,
  [jobscript] text not null  default '',
  [jobparams] text not null  default '',
  [locktoken] varchar(40) not null  default '',
  [queuetime] varchar(20) not null  default '',
  [starttime] varchar(30) not null  default '',
  [readytime] varchar(20) not null  default '',
  [errorcode] varchar(32) not null  default '',
  [errormessage] varchar(1024) not null  default '',
  [scriptresult] text not null  default '',
  [jobstatus] int not null  default 0,
  [jobcondition] int not null  default 0,
  [jobprogress] int not null  default 0,
  [attempts] int not null  default 0,
  [assignedserverid] int not null  default 0,
  [minservermajorversion] int not null  default '0',
  [minserverminorversion] int not null  default '0',
  [maxservermajorversion] int not null  default '0',
  [maxserverminorversion] int not null  default '0',
  [prio] int not null  default '3',
  [ticketseal] varchar(40) not null  default '',
  [ticket] varchar(40) not null  default '',
  [actinguser] varchar(40) not null  default '',
  [initiator] varchar(40) not null  default '',
  [servicename] varchar(32) not null  default '',
  [context] varchar(64) not null  default '',
  PRIMARY KEY ([jobid])
);
CREATE  INDEX [asre_indesignserverjobs] on [smart_indesignserverjobs]([assignedserverid], [readytime]) ;
CREATE  INDEX [qt_indesignserverjobs] on [smart_indesignserverjobs]([queuetime]) ;
CREATE  INDEX [objid_indesignserverjobs] on [smart_indesignserverjobs]([objid]) ;
CREATE  INDEX [prid_indesignserverjobs] on [smart_indesignserverjobs]([prio], [jobid]) ;
CREATE  INDEX [ts_indesignserverjobs] on [smart_indesignserverjobs]([ticketseal]) ;
CREATE  INDEX [ttjtstrt_indesignserverjobs] on [smart_indesignserverjobs]([ticket], [jobtype], [starttime], [readytime]) ;
CREATE  INDEX [jp_indesignserverjobs] on [smart_indesignserverjobs]([jobprogress]) ;
CREATE  INDEX [jspr_indesignserverjobs] on [smart_indesignserverjobs]([jobstatus], [prio], [queuetime]) ;
CREATE  INDEX [lt_indesignserverjobs] on [smart_indesignserverjobs]([locktoken]) ;

CREATE TABLE [smart_servers] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(64) not null  default '',
  [type] varchar(32) not null  default '',
  [url] varchar(1024) not null  default '',
  [description] varchar(255) not null  default '',
  [jobsupport] char(1) not null  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [hopo_servers] on [smart_servers]([name]) ;

CREATE TABLE [smart_serverjobs] (
  [jobid] varchar(40) not null  default '',
  [attempts] int not null  default 0,
  [queuetime] varchar(30) not null  default '',
  [servicename] varchar(32) not null  default '',
  [context] varchar(32) not null  default '',
  [servertype] varchar(32) not null  default '',
  [jobtype] varchar(32) not null  default '',
  [assignedserverid] int not null  default 0,
  [starttime] varchar(30) not null  default '0000-00-00T00:00:00',
  [readytime] varchar(30) not null  default '0000-00-00T00:00:00',
  [errormessage] varchar(1024) not null  default '',
  [locktoken] varchar(40) not null  default '',
  [ticketseal] varchar(40) not null  default '',
  [actinguser] varchar(40) not null  default '',
  [jobstatus] int not null  default 0,
  [jobcondition] int not null  default 0,
  [jobprogress] int not null  default 0,
  [jobdata] text not null  default '',
  [dataentity] varchar(20) not null  default '',
  PRIMARY KEY ([jobid])
);
CREATE  INDEX [qt_serverjobs] on [smart_serverjobs]([queuetime]) ;
CREATE  INDEX [jobinfo] on [smart_serverjobs]([locktoken], [jobstatus], [jobprogress]) ;
CREATE  INDEX [aslt_serverjobs] on [smart_serverjobs]([assignedserverid], [locktoken]) ;
CREATE  INDEX [paged_results] on [smart_serverjobs]([queuetime], [servertype], [jobtype], [jobstatus], [actinguser]) ;

CREATE TABLE [smart_serverjobtypesonhold] (
  [guid] varchar(40) not null  default '',
  [jobtype] varchar(32) not null  default '',
  [retrytimestamp] varchar(20) not null  default '',
  PRIMARY KEY ([guid])
);
CREATE  INDEX [jobtype] on [smart_serverjobtypesonhold]([jobtype]) ;
CREATE  INDEX [retrytime] on [smart_serverjobtypesonhold]([retrytimestamp]) ;

CREATE TABLE [smart_serverjobconfigs] (
  [id] int not null  IDENTITY(1,1),
  [jobtype] varchar(32) not null  default '',
  [servertype] varchar(32) not null  default '',
  [attempts] int not null  default 0,
  [active] char(1) not null  default 'N',
  [sysadmin] char(1) not null  default '-',
  [userid] int not null  default 0,
  [userconfigneeded] char(1) not null  default 'Y',
  [recurring] char(1) not null  default 'N',
  [selfdestructive] char(1) not null  default 'N',
  [workingdays] char(1) not null  default 'N',
  [dailystarttime] varchar(30) not null  default '00-00-00T00:00:00',
  [dailystoptime] varchar(30) not null  default '00-00-00T00:00:00',
  [timeinterval] int not null  default 0,
  PRIMARY KEY ([id])
);

CREATE TABLE [smart_serverjobsupports] (
  [id] int not null  IDENTITY(1,1),
  [serverid] int not null  default 0,
  [jobconfigid] int not null  default 0,
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [sjs_serverconfigs] on [smart_serverjobsupports]([serverid], [jobconfigid]) ;

CREATE TABLE [smart_serverplugins] (
  [id] int not null  IDENTITY(1,1),
  [uniquename] varchar(64) not null  default '',
  [displayname] varchar(128) not null  default '',
  [version] varchar(64) not null  default '',
  [description] varchar(255) not null  default '',
  [copyright] varchar(128) not null  default '',
  [active] char(2) not null  default '',
  [system] char(2) not null  default '',
  [installed] char(2) not null  default '',
  [modified] varchar(30) not null  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_serverplugins] ON
INSERT INTO [smart_serverplugins] ([id], [uniquename], [displayname], [version], [description], [copyright], [active], [system], [installed], [modified]) VALUES (1, 'PreviewMetaPHP', 'PHP Preview and Meta Data', 'v6.1', 'Using internal PHP libraries (such as GD) to generate previews and read metadata', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', 'on', 'on', 'on', '2008-10-02T09:00:00');
INSERT INTO [smart_serverplugins] ([id], [uniquename], [displayname], [version], [description], [copyright], [active], [system], [installed], [modified]) VALUES (2, 'ImageMagick', 'ImageMagick', 'v6.1', 'Use ImageMagick to support extra formats for preview generation', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', '', 'on', '', '2008-10-02T09:00:00');
INSERT INTO [smart_serverplugins] ([id], [uniquename], [displayname], [version], [description], [copyright], [active], [system], [installed], [modified]) VALUES (3, 'InCopyHTMLConversion', 'InCopy HTML Conversion', 'v6.1', 'Have InCopy and InDesign edit HTML articles by converting the article to text', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', 'on', 'on', 'on', '2008-11-30T09:00:00');
SET IDENTITY_INSERT [smart_serverplugins] OFF

CREATE TABLE [smart_serverconnectors] (
  [id] int not null  IDENTITY(1,1),
  [pluginid] int not null  default '0',
  [classname] varchar(128) not null  default '',
  [interface] varchar(128) not null  default '',
  [type] varchar(32) not null  default '',
  [prio] int not null  default '0',
  [runmode] varchar(16) not null  default '',
  [classfile] varchar(255) not null  default '',
  [modified] varchar(30) not null  default '',
  PRIMARY KEY ([id])
);
CREATE  INDEX [seco_pluginid] on [smart_serverconnectors]([pluginid]) ;
CREATE  INDEX [seco_typeinterface] on [smart_serverconnectors]([type], [interface]) ;
SET IDENTITY_INSERT [smart_serverconnectors] ON
INSERT INTO [smart_serverconnectors] ([id], [pluginid], [classname], [interface], [type], [prio], [runmode], [classfile], [modified]) VALUES (1, 1, 'PreviewMetaPHP_Preview', 'Preview', '', 500, 'Synchron', '/server/plugins/PreviewMetaPHP/PreviewMetaPHP_Preview.class.php', '2008-10-02T09:00:00');
INSERT INTO [smart_serverconnectors] ([id], [pluginid], [classname], [interface], [type], [prio], [runmode], [classfile], [modified]) VALUES (2, 1, 'PreviewMetaPHP_MetaData', 'MetaData', '', 500, 'Synchron', '/server/plugins/PreviewMetaPHP/PreviewMetaPHP_MetaData.class.php', '2008-10-02T09:00:00');
INSERT INTO [smart_serverconnectors] ([id], [pluginid], [classname], [interface], [type], [prio], [runmode], [classfile], [modified]) VALUES (3, 3, 'InCopyHTMLConversion_WflGetObjects', 'WflGetObjects', 'WorkflowService', 500, 'After', '/server/plugins/InCopyHTMLConversion/InCopyHTMLConversion_WflGetObjects.class.php', '2008-11-30T09:00:00');
SET IDENTITY_INSERT [smart_serverconnectors] OFF

CREATE TABLE [smart_semaphores] (
  [id] int not null  IDENTITY(1,1),
  [entityid] varchar(40) not null  default '0',
  [lastupdate] int not null  default '0',
  [lifetime] int not null  default '0',
  [user] varchar(40) not null  default '',
  [ip] varchar(30) not null  default '',
  PRIMARY KEY ([id])
);
CREATE UNIQUE INDEX [idx_entity] on [smart_semaphores]([entityid]) ;
CREATE  INDEX [idx_entityuser] on [smart_semaphores]([entityid], [user]) ;

CREATE TABLE [smart_outputdevices] (
  [id] int not null  IDENTITY(1,1),
  [name] varchar(255) not null  default '',
  [code] int not null  default '0',
  [description] text not null  default '',
  [landscapewidth] int not null  default '0',
  [landscapeheight] int not null  default '0',
  [portraitwidth] int not null  default '0',
  [portraitheight] int not null  default '0',
  [previewquality] int not null  default '0',
  [landscapelayoutwidth] real not null  default '0',
  [pixeldensity] int not null  default '0',
  [pngcompression] int not null  default '0',
  [textviewpadding] varchar(50) not null  default '',
  PRIMARY KEY ([id])
);
SET IDENTITY_INSERT [smart_outputdevices] ON
INSERT INTO [smart_outputdevices] ([id], [name], [code], [description], [landscapewidth], [landscapeheight], [portraitwidth], [portraitheight], [previewquality], [landscapelayoutwidth], [pixeldensity], [pngcompression], [textviewpadding]) VALUES (1, 'iPad - DM', 0, '', 1024, 748, 768, 1004, 4, 558.5, 132, 9, '');
INSERT INTO [smart_outputdevices] ([id], [name], [code], [description], [landscapewidth], [landscapeheight], [portraitwidth], [portraitheight], [previewquality], [landscapelayoutwidth], [pixeldensity], [pngcompression], [textviewpadding]) VALUES (2, 'iPad', 10, '', 1024, 768, 768, 1024, 4, 1024, 132, 9, '');
INSERT INTO [smart_outputdevices] ([id], [name], [code], [description], [landscapewidth], [landscapeheight], [portraitwidth], [portraitheight], [previewquality], [landscapelayoutwidth], [pixeldensity], [pngcompression], [textviewpadding]) VALUES (3, 'Kindle Fire', 20, '', 1024, 600, 600, 1024, 4, 1024, 169, 9, '');
INSERT INTO [smart_outputdevices] ([id], [name], [code], [description], [landscapewidth], [landscapeheight], [portraitwidth], [portraitheight], [previewquality], [landscapelayoutwidth], [pixeldensity], [pngcompression], [textviewpadding]) VALUES (4, 'Xoom', 30, '', 1280, 800, 800, 1280, 4, 1280, 160, 9, '');
SET IDENTITY_INSERT [smart_outputdevices] OFF

CREATE TABLE [smart_placementtiles] (
  [id] int not null  IDENTITY(1,1),
  [placementid] int not null  default '0',
  [pagesequence] int not null  default '0',
  [left] real not null  default '0',
  [top] real not null  default '0',
  [width] real not null  default '0',
  [height] real not null  default '0',
  PRIMARY KEY ([id])
);
CREATE  INDEX [pi_placementtiles] on [smart_placementtiles]([placementid]) ;

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

CREATE TABLE [smart_channeldata] (
  [publication] int not null  default '0',
  [pubchannel] int not null  default '0',
  [issue] int not null  default '0',
  [section] int not null  default '0',
  [name] varchar(200) not null  default '',
  [value] text not null  default '',
  PRIMARY KEY ([publication], [pubchannel], [issue], [section], [name])
);
UPDATE [smart_config] set [value] = '10.1' where [name] = 'version';
