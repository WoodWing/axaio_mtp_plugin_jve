ALTER TABLE `smart_actionproperties`
ADD   `multipleobjects` char(2) not null  default '';
ALTER TABLE `smart_actionproperties` CHANGE `orderid`   `orderid` int(11) not null  default '0';
ALTER TABLE `smart_authorizations`
ADD   `bundle` int(11) not null  default '0';
ALTER TABLE `smart_deletedobjects`
ADD   `orientation` tinyint(4) not null  default '0';
ALTER TABLE `smart_deletedobjects` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_objects`
ADD   `orientation` tinyint(4) not null  default '0';
ALTER TABLE `smart_objects` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_objectversions`
ADD   `orientation` tinyint(4) not null  default '0';
ALTER TABLE `smart_objectversions` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_placements`
ADD   `frametype` varchar(20) not null  default '',
ADD   `splineid` varchar(200) not null  default '';

CREATE TABLE `smart_indesignarticles` (
  `objid` int(11) not null  default 0,
  `artuid` varchar(40) not null  default '',
  `name` varchar(200) not null  default '',
  `code` int(11) not null  default '0',
  PRIMARY KEY (`objid`, `artuid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_idarticlesplacements` (
  `objid` int(11) not null  default 0,
  `artuid` varchar(40) not null  default '',
  `plcid` int(11) not null  default 0,
  PRIMARY KEY (`objid`, `artuid`, `plcid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_objectoperations` (
  `id` int(11) not null  auto_increment,
  `objid` int(11) not null  default 0,
  `guid` varchar(40) not null  default '',
  `type` varchar(200) not null  default '',
  `name` varchar(200) not null  default '',
  `params` blob not null ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objid_objectoperations` on `smart_objectoperations`(`objid`) ;
ALTER TABLE `smart_properties`
ADD   `termentityid` int(11) not null  default '0',
ADD   `suggestionentity` varchar(200) not null  default '';
ALTER TABLE `smart_publications`
ADD   `calculatedeadlines` char(2) not null  default '';
ALTER TABLE `smart_routing` CHANGE `routeto`   `routeto` varchar(255) not null  default '';
ALTER TABLE `smart_states`
ADD   `phase` varchar(40) not null  default 'Production',
ADD   `skipidsa` char(2) not null  default '';
ALTER TABLE `smart_tickets`
ADD   `masterticketid` varchar(40) not null  default '';
CREATE  INDEX `mtid_tickets` on `smart_tickets`(`masterticketid`) ;

CREATE TABLE `smart_termentities` (
  `id` int(11) not null  auto_increment,
  `name` varchar(255) not null  default '',
  `provider` varchar(40) not null  default '',
  `publishsystemid` varchar(40) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `te_name` on `smart_termentities`(`name`) ;
CREATE  INDEX `te_provider` on `smart_termentities`(`provider`) ;
CREATE  INDEX `te_termentity` on `smart_termentities`(`name`, `provider`) ;

CREATE TABLE `smart_terms` (
  `entityid` int(11) not null  default '0',
  `displayname` varchar(255) not null  default '',
  `normalizedname` varchar(255) not null  default '',
  `ligatures` varchar(255) not null  default '',
  PRIMARY KEY (`entityid`, `displayname`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `tm_entityid` on `smart_terms`(`entityid`) ;
CREATE  INDEX `tm_normalizedname` on `smart_terms`(`entityid`, `normalizedname`) ;
ALTER TABLE `smart_users`
ADD   `importonlogon` char(2) not null  default '';
ALTER TABLE `smart_users` CHANGE `pass`   `pass` varchar(128) not null  default '';
ALTER TABLE `smart_channels`
ADD   `suggestionprovider` varchar(64) not null  default '',
ADD   `publishsystemid` varchar(40) not null  default '';
ALTER TABLE `smart_issues`
ADD   `calculatedeadlines` char(2) not null  default '';
ALTER TABLE `smart_publishhistory`
ADD   `user` varchar(255) not null  default '';
ALTER TABLE `smart_publishedobjectshist`
ADD   `objectname` varchar(255) not null  default '',
ADD   `objecttype` varchar(40) not null  default '',
ADD   `objectformat` varchar(128) not null  default '';

CREATE TABLE `smart_publishedplcmtshist` (
  `id` int(11) not null  auto_increment,
  `objectid` int(11) not null  default '0',
  `publishid` int(11) not null  default '0',
  `majorversion` mediumint(9) not null  default '0',
  `minorversion` mediumint(9) not null  default '0',
  `externalid` varchar(200) not null  default '',
  `placementhash` varchar(64) not null ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `obpu_publplchist` on `smart_publishedplcmtshist`(`objectid`, `publishid`) ;
CREATE  INDEX `puob_publplchist` on `smart_publishedplcmtshist`(`publishid`, `objectid`) ;
ALTER TABLE `smart_indesignservers`
ADD   `prio1` char(2) not null  default 'on',
ADD   `prio2` char(2) not null  default 'on',
ADD   `prio3` char(2) not null  default 'on',
ADD   `prio4` char(2) not null  default 'on',
ADD   `prio5` char(2) not null  default 'on',
ADD   `locktoken` varchar(40) not null  default '';
ALTER TABLE `smart_indesignserverjobs`
ADD   `jobid` varchar(40) not null  default '',
ADD   `objectmajorversion` mediumint(9) not null  default '0',
ADD   `objectminorversion` mediumint(9) not null  default '0',
ADD   `locktoken` varchar(40) not null  default '',
ADD   `jobstatus` int(11) not null  default 0,
ADD   `jobcondition` int(11) not null  default 0,
ADD   `jobprogress` int(11) not null  default 0,
ADD   `attempts` int(11) not null  default 0,
ADD   `maxservermajorversion` mediumint(9) not null  default '0',
ADD   `maxserverminorversion` mediumint(9) not null  default '0',
ADD   `prio` mediumint(1) not null  default '3',
ADD   `ticketseal` varchar(40) not null  default '',
ADD   `ticket` varchar(40) not null  default '',
ADD   `actinguser` varchar(40) not null  default '',
ADD   `initiator` varchar(40) not null  default '',
ADD   `servicename` varchar(32) not null  default '',
ADD   `context` varchar(64) not null  default '';
ALTER TABLE `smart_indesignserverjobs` CHANGE `errormessage`   `errormessage` varchar(1024) not null  default '';
ALTER TABLE `smart_indesignserverjobs` CHANGE `servermajorversion`   `minservermajorversion` mediumint(9) not null  default '0';
ALTER TABLE `smart_indesignserverjobs` CHANGE `serverminorversion`   `minserverminorversion` mediumint(9) not null  default '0';
CREATE  INDEX `objid_indesignserverjobs` on `smart_indesignserverjobs`(`objid`) ;
CREATE  INDEX `prid_indesignserverjobs` on `smart_indesignserverjobs`(`prio`, `jobid`) ;
CREATE  INDEX `ts_indesignserverjobs` on `smart_indesignserverjobs`(`ticketseal`) ;
CREATE  INDEX `ttjtstrt_indesignserverjobs` on `smart_indesignserverjobs`(`ticket`, `jobtype`, `starttime`, `readytime`) ;
CREATE  INDEX `jp_indesignserverjobs` on `smart_indesignserverjobs`(`jobprogress`) ;
CREATE  INDEX `jspr_indesignserverjobs` on `smart_indesignserverjobs`(`jobstatus`, `prio`, `queuetime`) ;
CREATE  INDEX `lt_indesignserverjobs` on `smart_indesignserverjobs`(`locktoken`) ;
ALTER TABLE `smart_indesignserverjobs` CHANGE `id` `id` int(11) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`jobid`);
ALTER TABLE `smart_indesignserverjobs` DROP `id`;
ALTER TABLE `smart_indesignserverjobs` DROP `exclusivelock`;
ALTER TABLE `smart_serverjobs`
ADD   `jobid` varchar(40) not null  default '',
ADD   `attempts` int(11) not null  default 0,
ADD   `errormessage` varchar(1024) not null  default '',
ADD   `jobdata` mediumblob not null ,
ADD   `dataentity` varchar(20) not null  default '';
ALTER TABLE `smart_serverjobs` CHANGE `queuetime`   `queuetime` varchar(30) not null  default '';
CREATE  INDEX `jobinfo` on `smart_serverjobs`(`locktoken`, `jobstatus`, `jobprogress`) ;
CREATE  INDEX `aslt_serverjobs` on `smart_serverjobs`(`assignedserverid`, `locktoken`) ;
CREATE  INDEX `paged_results` on `smart_serverjobs`(`queuetime`, `servertype`, `jobtype`, `jobstatus`, `actinguser`) ;
ALTER TABLE `smart_serverjobs` CHANGE `id` `id` int(11) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`jobid`);
ALTER TABLE `smart_serverjobs` DROP `id`;
ALTER TABLE `smart_serverjobs` DROP `objid`;
ALTER TABLE `smart_serverjobs` DROP `minorversion`;
ALTER TABLE `smart_serverjobs` DROP `majorversion`;

CREATE TABLE `smart_serverjobtypesonhold` (
  `guid` varchar(40) not null  default '',
  `jobtype` varchar(32) not null  default '',
  `retrytimestamp` varchar(20) not null  default '',
  PRIMARY KEY (`guid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `jobtype` on `smart_serverjobtypesonhold`(`jobtype`) ;
CREATE  INDEX `retrytime` on `smart_serverjobtypesonhold`(`retrytimestamp`) ;
ALTER TABLE `smart_serverjobconfigs`
ADD   `userconfigneeded` char(1) not null  default 'Y',
ADD   `selfdestructive` char(1) not null  default 'N';
ALTER TABLE `smart_semaphores`
ADD   `lifetime` int(11) not null  default '0';

CREATE TABLE `smart_objectlabels` (
  `id` int(11) not null  auto_increment,
  `objid` int(11) not null  default '0',
  `name` varchar(250) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objlabels_objid` on `smart_objectlabels`(`objid`) ;

CREATE TABLE `smart_objectrelationlabels` (
  `labelid` int(11) not null  default '0',
  `childobjid` int(11) not null  default '0',
  PRIMARY KEY (`labelid`, `childobjid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objrellabels_childobjid` on `smart_objectrelationlabels`(`childobjid`) ;
UPDATE `smart_config` set `value` = '10.1' where `name` = 'version';
