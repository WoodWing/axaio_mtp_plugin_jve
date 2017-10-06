ALTER TABLE `smart_actionproperties` CHANGE `orderid`   `orderid` int(11) NOT NULL  default '0';
ALTER TABLE `smart_authorizations`
ADD   `bundle` int(11) NOT NULL  default '0';
ALTER TABLE `smart_authorizations` CHANGE `rights`   `rights` varchar(1024) NOT NULL  default '';
ALTER TABLE `smart_deletedobjects`
ADD   `orientation` tinyint(4) NOT NULL  default '0';
ALTER TABLE `smart_deletedobjects` CHANGE `dpi`   `dpi` double NOT NULL  default '0';
ALTER TABLE `smart_objects`
ADD   `orientation` tinyint(4) NOT NULL  default '0';
ALTER TABLE `smart_objects` CHANGE `dpi`   `dpi` double NOT NULL  default '0';
ALTER TABLE `smart_objectversions`
ADD   `orientation` tinyint(4) NOT NULL  default '0';
ALTER TABLE `smart_objectversions` CHANGE `dpi`   `dpi` double NOT NULL  default '0';
ALTER TABLE `smart_placements`
ADD   `frametype` varchar(20) NOT NULL  default '',
ADD   `splineid` varchar(200) NOT NULL  default '';

CREATE TABLE `smart_indesignarticles` (
  `objid` int(11) NOT NULL  default 0,
  `artuid` varchar(40) NOT NULL  default '',
  `name` varchar(200) NOT NULL  default '',
  `code` int(11) NOT NULL  default '0',
  PRIMARY KEY (`objid`, `artuid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_idarticlesplacements` (
  `objid` int(11) NOT NULL  default 0,
  `artuid` varchar(40) NOT NULL  default '',
  `plcid` int(11) NOT NULL  default 0,
  PRIMARY KEY (`objid`, `artuid`, `plcid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_objectoperations` (
  `id` int(11) NOT NULL  auto_increment,
  `objid` int(11) NOT NULL  default 0,
  `guid` varchar(40) NOT NULL  default '',
  `type` varchar(200) NOT NULL  default '',
  `name` varchar(200) NOT NULL  default '',
  `params` blob NOT NULL ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objid_objectoperations` ON `smart_objectoperations`(`objid`) ;
ALTER TABLE `smart_states`
ADD   `skipidsa` char(2) NOT NULL  default '';
ALTER TABLE `smart_tickets`
ADD   `masterticketid` varchar(40) NOT NULL  default '';
CREATE  INDEX `mtid_tickets` ON `smart_tickets`(`masterticketid`) ;

CREATE TABLE `smart_featureaccess` (
  `featurename` varchar(255) NOT NULL  default '',
  `featureid` int(4) NOT NULL  default '0',
  `accessflag` int(4) NOT NULL  default '0',
  PRIMARY KEY (`featurename`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `faid_profiles` ON `smart_featureaccess`(`featureid`) ;
CREATE UNIQUE INDEX `faaf_profiles` ON `smart_featureaccess`(`accessflag`) ;

CREATE TABLE `smart_publishedplcmtshist` (
  `id` int(11) NOT NULL  auto_increment,
  `objectid` int(11) NOT NULL  default '0',
  `publishid` int(11) NOT NULL  default '0',
  `majorversion` mediumint(9) NOT NULL  default '0',
  `minorversion` mediumint(9) NOT NULL  default '0',
  `externalid` varchar(200) NOT NULL  default '',
  `placementhash` varchar(64) NOT NULL ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `obpu_publplchist` ON `smart_publishedplcmtshist`(`objectid`, `publishid`) ;
CREATE  INDEX `puob_publplchist` ON `smart_publishedplcmtshist`(`publishid`, `objectid`) ;
ALTER TABLE `smart_indesignservers`
ADD   `locktoken` varchar(40) NOT NULL  default '';
ALTER TABLE `smart_indesignserverjobs`
ADD   `jobid` varchar(40) NOT NULL  default '',
ADD   `objectmajorversion` mediumint(9) NOT NULL  default '0',
ADD   `objectminorversion` mediumint(9) NOT NULL  default '0',
ADD   `locktoken` varchar(40) NOT NULL  default '',
ADD   `jobstatus` int(11) NOT NULL  default 0,
ADD   `jobcondition` int(11) NOT NULL  default 0,
ADD   `jobprogress` int(11) NOT NULL  default 0,
ADD   `attempts` int(11) NOT NULL  default 0,
ADD   `pickuptime` varchar(30) NOT NULL  default '',
ADD   `ticketseal` varchar(40) NOT NULL  default '',
ADD   `ticket` varchar(40) NOT NULL  default '',
ADD   `actinguser` varchar(40) NOT NULL  default '',
ADD   `initiator` varchar(40) NOT NULL  default '',
ADD   `servicename` varchar(32) NOT NULL  default '',
ADD   `context` varchar(64) NOT NULL  default '';
ALTER TABLE `smart_indesignserverjobs` CHANGE `errormessage`   `errormessage` varchar(1024) NOT NULL  default '';
CREATE  INDEX `ts_indesignserverjobs` ON `smart_indesignserverjobs`(`ticketseal`) ;
CREATE  INDEX `ttjtstrt_indesignserverjobs` ON `smart_indesignserverjobs`(`ticket`, `jobtype`, `starttime`, `readytime`) ;
CREATE  INDEX `jp_indesignserverjobs` ON `smart_indesignserverjobs`(`jobprogress`) ;
CREATE  INDEX `jspr_indesignserverjobs` ON `smart_indesignserverjobs`(`jobstatus`, `prio`, `queuetime`) ;
CREATE  INDEX `lt_indesignserverjobs` ON `smart_indesignserverjobs`(`locktoken`) ;
ALTER TABLE `smart_indesignserverjobs` CHANGE `id` `id` int(11) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`jobid`);
ALTER TABLE `smart_indesignserverjobs` DROP INDEX `prid_indesignserverjobs`, ADD INDEX `prid_indesignserverjobs` (`prio`, `jobid`) ;
ALTER TABLE `smart_indesignserverjobs` DROP `id`;
ALTER TABLE `smart_indesignserverjobs` DROP `exclusivelock`;
ALTER TABLE `smart_serverplugins`
ADD   `dbprefix` varchar(10) NOT NULL  default '',
ADD   `dbversion` varchar(10) NOT NULL  default '';
UPDATE `smart_config` set `value` = '10.2' where `name` = 'version';
