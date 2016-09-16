ALTER TABLE `smart_actionproperties` CHANGE `orderid`   `orderid` int(11) not null  default '0';
ALTER TABLE `smart_authorizations`
ADD   `bundle` int(11) not null  default '0';
ALTER TABLE `smart_deletedobjects` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_objects` CHANGE `dpi`   `dpi` double not null  default '0';
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
ALTER TABLE `smart_states`
ADD   `skipidsa` char(2) not null  default '';
ALTER TABLE `smart_tickets`
ADD   `masterticketid` varchar(40) not null  default '';
CREATE  INDEX `mtid_tickets` on `smart_tickets`(`masterticketid`) ;

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
ADD   `prio` mediumint(1) not null  default '3',
ADD   `ticketseal` varchar(40) not null  default '',
ADD   `ticket` varchar(40) not null  default '',
ADD   `actinguser` varchar(40) not null  default '',
ADD   `initiator` varchar(40) not null  default '',
ADD   `servicename` varchar(32) not null  default '',
ADD   `context` varchar(64) not null  default '';
ALTER TABLE `smart_indesignserverjobs` CHANGE `errormessage`   `errormessage` varchar(1024) not null  default '';
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
ADD   `errormessage` varchar(1024) not null  default '';
ALTER TABLE `smart_semaphores`
ADD   `lifetime` int(11) not null  default '0';
ALTER TABLE `smart_objectlabels` CHANGE `name`   `name` varchar(250) not null  default '';
UPDATE `smart_config` set `value` = '10.1' where `name` = 'version';
