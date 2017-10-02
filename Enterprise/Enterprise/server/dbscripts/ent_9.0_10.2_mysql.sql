ALTER TABLE `smart_actionproperties`
ADD   `multipleobjects` char(2) NOT NULL  default '';
ALTER TABLE `smart_actionproperties` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_actionproperties` CHANGE `orderid`   `orderid` int(11) NOT NULL  default '0';
ALTER TABLE `smart_authorizations`
ADD   `bundle` int(11) NOT NULL  default '0';
ALTER TABLE `smart_authorizations` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_authorizations` CHANGE `rights`   `rights` varchar(1024) NOT NULL  default '';
ALTER TABLE `smart_config` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_deletedobjects`
ADD   `orientation` tinyint(4) NOT NULL  default '0';
ALTER TABLE `smart_deletedobjects` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_deletedobjects` CHANGE `dpi`   `dpi` double NOT NULL  default '0';
ALTER TABLE `smart_log` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_log` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_log` CHANGE `parent`   `parent` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectlocks` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectlocks` CHANGE `object`   `object` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectrelations` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectrelations` CHANGE `parent`   `parent` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectrelations` CHANGE `child`   `child` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objects`
ADD   `orientation` tinyint(4) NOT NULL  default '0';
ALTER TABLE `smart_objects` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objects` CHANGE `dpi`   `dpi` double NOT NULL  default '0';
ALTER TABLE `smart_objectversions`
ADD   `orientation` tinyint(4) NOT NULL  default '0';
ALTER TABLE `smart_objectversions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectversions` CHANGE `objid`   `objid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectversions` CHANGE `dpi`   `dpi` double NOT NULL  default '0';
ALTER TABLE `smart_objectrenditions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectrenditions` CHANGE `objid`   `objid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_pages` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_pages` CHANGE `objid`   `objid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_placements`
ADD   `frametype` varchar(20) NOT NULL  default '',
ADD   `splineid` varchar(200) NOT NULL  default '';
ALTER TABLE `smart_placements` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_placements` CHANGE `parent`   `parent` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_placements` CHANGE `child`   `child` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_elements` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_elements` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;

CREATE TABLE `smart_indesignarticles` (
  `objid` bigint(11) NOT NULL  default 0,
  `artuid` varchar(40) NOT NULL  default '',
  `name` varchar(200) NOT NULL  default '',
  `code` int(11) NOT NULL  default '0',
  PRIMARY KEY (`objid`, `artuid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_idarticlesplacements` (
  `objid` bigint(11) NOT NULL  default 0,
  `artuid` varchar(40) NOT NULL  default '',
  `plcid` bigint(11) NOT NULL  default 0,
  PRIMARY KEY (`objid`, `artuid`, `plcid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `plcid_idarticlesplacements` ON `smart_idarticlesplacements`(`plcid`) ;

CREATE TABLE `smart_objectoperations` (
  `id` bigint(11) NOT NULL  auto_increment,
  `objid` bigint(11) NOT NULL  default 0,
  `guid` varchar(40) NOT NULL  default '',
  `type` varchar(200) NOT NULL  default '',
  `name` varchar(200) NOT NULL  default '',
  `params` blob NOT NULL ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objid_objectoperations` ON `smart_objectoperations`(`objid`) ;
ALTER TABLE `smart_properties`
ADD   `termentityid` int(11) NOT NULL  default '0',
ADD   `suggestionentity` varchar(200) NOT NULL  default '';
ALTER TABLE `smart_publications`
ADD   `calculatedeadlines` char(2) NOT NULL  default '';
ALTER TABLE `smart_publobjects` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_issueeditions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_routing` CHANGE `routeto`   `routeto` varchar(255) NOT NULL  default '';
ALTER TABLE `smart_settings` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_states`
ADD   `phase` varchar(40) NOT NULL  default 'Production',
ADD   `skipidsa` char(2) NOT NULL  default '';
CREATE  INDEX `cost_states` ON `smart_states`(`code`, `state`) ;
ALTER TABLE `smart_tickets`
ADD   `masterticketid` varchar(40) NOT NULL  default '';
ALTER TABLE `smart_tickets` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
CREATE  INDEX `mtid_tickets` ON `smart_tickets`(`masterticketid`) ;

CREATE TABLE `smart_termentities` (
  `id` bigint(11) NOT NULL  auto_increment,
  `name` varchar(255) NOT NULL  default '',
  `provider` varchar(40) NOT NULL  default '',
  `publishsystemid` varchar(40) NOT NULL  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `te_name` ON `smart_termentities`(`name`) ;
CREATE  INDEX `te_provider` ON `smart_termentities`(`provider`) ;
CREATE  INDEX `te_termentity` ON `smart_termentities`(`name`, `provider`) ;

CREATE TABLE `smart_terms` (
  `entityid` bigint(11) NOT NULL  default '0',
  `displayname` varchar(255) NOT NULL  default '',
  `normalizedname` varchar(255) NOT NULL  default '',
  `ligatures` varchar(255) NOT NULL  default '',
  PRIMARY KEY (`entityid`, `displayname`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `tm_entityid` ON `smart_terms`(`entityid`) ;
CREATE  INDEX `tm_normalizedname` ON `smart_terms`(`entityid`, `normalizedname`) ;
ALTER TABLE `smart_users`
ADD   `importonlogon` char(2) NOT NULL  default '';
ALTER TABLE `smart_users` CHANGE `pass`   `pass` varchar(128) NOT NULL  default '';
ALTER TABLE `smart_mtpsentobjects` CHANGE `objid`   `objid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_messagelog` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_objectflags` CHANGE `objid`   `objid` bigint(11) NOT NULL ;
ALTER TABLE `smart_profilefeatures` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;

CREATE TABLE `smart_featureaccess` (
  `featurename` varchar(255) NOT NULL  default '',
  `featureid` int(4) NOT NULL  default '0',
  `accessflag` varchar(4) NOT NULL  default '',
  PRIMARY KEY (`featurename`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `faid_profiles` ON `smart_featureaccess`(`featureid`) ;
CREATE UNIQUE INDEX `faaf_profiles` ON `smart_featureaccess`(`accessflag`) ;
ALTER TABLE `smart_appsessions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_appsessions` CHANGE `articleid`   `articleid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_appsessions` CHANGE `layoutid`   `layoutid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_dsqueryplacements` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_dsobjupdates` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_channels`
ADD   `suggestionprovider` varchar(64) NOT NULL  default '',
ADD   `publishsystemid` varchar(40) NOT NULL  default '';
ALTER TABLE `smart_issues`
ADD   `calculatedeadlines` char(2) NOT NULL  default '';
ALTER TABLE `smart_targets` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_targets` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_targets` CHANGE `objectrelationid`   `objectrelationid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_publishhistory`
ADD   `user` varchar(255) NOT NULL  default '';
ALTER TABLE `smart_publishhistory` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_publishhistory` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_pubpublishedissues` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_publishedobjectshist`
ADD   `objectname` varchar(255) NOT NULL  default '',
ADD   `objecttype` varchar(40) NOT NULL  default '',
ADD   `objectformat` varchar(128) NOT NULL  default '';
ALTER TABLE `smart_publishedobjectshist` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_publishedobjectshist` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_publishedobjectshist` CHANGE `publishid`   `publishid` bigint(11) NOT NULL  default '0';

CREATE TABLE `smart_publishedplcmtshist` (
  `id` bigint(11) NOT NULL  auto_increment,
  `objectid` bigint(11) NOT NULL  default '0',
  `publishid` bigint(11) NOT NULL  default '0',
  `majorversion` mediumint(9) NOT NULL  default '0',
  `minorversion` mediumint(9) NOT NULL  default '0',
  `externalid` varchar(200) NOT NULL  default '',
  `placementhash` varchar(64) NOT NULL ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `obpu_publplchist` ON `smart_publishedplcmtshist`(`objectid`, `publishid`) ;
CREATE  INDEX `puob_publplchist` ON `smart_publishedplcmtshist`(`publishid`, `objectid`) ;
ALTER TABLE `smart_targeteditions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_targeteditions` CHANGE `targetid`   `targetid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_indesignservers`
ADD   `prio1` char(2) NOT NULL  default 'on',
ADD   `prio2` char(2) NOT NULL  default 'on',
ADD   `prio3` char(2) NOT NULL  default 'on',
ADD   `prio4` char(2) NOT NULL  default 'on',
ADD   `prio5` char(2) NOT NULL  default 'on',
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
ADD   `maxservermajorversion` mediumint(9) NOT NULL  default '0',
ADD   `maxserverminorversion` mediumint(9) NOT NULL  default '0',
ADD   `prio` mediumint(1) NOT NULL  default '3',
ADD   `ticketseal` varchar(40) NOT NULL  default '',
ADD   `ticket` varchar(40) NOT NULL  default '',
ADD   `actinguser` varchar(40) NOT NULL  default '',
ADD   `initiator` varchar(40) NOT NULL  default '',
ADD   `servicename` varchar(32) NOT NULL  default '',
ADD   `context` varchar(64) NOT NULL  default '';
ALTER TABLE `smart_indesignserverjobs` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_indesignserverjobs` CHANGE `errormessage`   `errormessage` varchar(1024) NOT NULL  default '';
ALTER TABLE `smart_indesignserverjobs` CHANGE `servermajorversion`   `minservermajorversion` mediumint(9) NOT NULL  default '0';
ALTER TABLE `smart_indesignserverjobs` CHANGE `serverminorversion`   `minserverminorversion` mediumint(9) NOT NULL  default '0';
CREATE  INDEX `objid_indesignserverjobs` ON `smart_indesignserverjobs`(`objid`) ;
CREATE  INDEX `prid_indesignserverjobs` ON `smart_indesignserverjobs`(`prio`, `jobid`) ;
CREATE  INDEX `ts_indesignserverjobs` ON `smart_indesignserverjobs`(`ticketseal`) ;
CREATE  INDEX `ttjtstrt_indesignserverjobs` ON `smart_indesignserverjobs`(`ticket`, `jobtype`, `starttime`, `readytime`) ;
CREATE  INDEX `jp_indesignserverjobs` ON `smart_indesignserverjobs`(`jobprogress`) ;
CREATE  INDEX `jspr_indesignserverjobs` ON `smart_indesignserverjobs`(`jobstatus`, `prio`, `queuetime`) ;
CREATE  INDEX `lt_indesignserverjobs` ON `smart_indesignserverjobs`(`locktoken`) ;
ALTER TABLE `smart_indesignserverjobs` CHANGE `id` `id` int(11) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`jobid`);
ALTER TABLE `smart_indesignserverjobs` DROP `id`;
ALTER TABLE `smart_indesignserverjobs` DROP `exclusivelock`;
ALTER TABLE `smart_serverjobs`
ADD   `jobid` varchar(40) NOT NULL  default '',
ADD   `attempts` int(11) NOT NULL  default 0,
ADD   `errormessage` varchar(1024) NOT NULL  default '',
ADD   `jobdata` mediumblob NOT NULL ,
ADD   `dataentity` varchar(20) NOT NULL  default '';
ALTER TABLE `smart_serverjobs` CHANGE `queuetime`   `queuetime` varchar(30) NOT NULL  default '';
CREATE  INDEX `jobinfo` ON `smart_serverjobs`(`locktoken`, `jobstatus`, `jobprogress`) ;
CREATE  INDEX `aslt_serverjobs` ON `smart_serverjobs`(`assignedserverid`, `locktoken`) ;
CREATE  INDEX `paged_results` ON `smart_serverjobs`(`queuetime`, `servertype`, `jobtype`, `jobstatus`, `actinguser`) ;
ALTER TABLE `smart_serverjobs` CHANGE `id` `id` int(11) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`jobid`);
ALTER TABLE `smart_serverjobs` DROP `id`;
ALTER TABLE `smart_serverjobs` DROP `objid`;
ALTER TABLE `smart_serverjobs` DROP `minorversion`;
ALTER TABLE `smart_serverjobs` DROP `majorversion`;

CREATE TABLE `smart_serverjobtypesonhold` (
  `guid` varchar(40) NOT NULL  default '',
  `jobtype` varchar(32) NOT NULL  default '',
  `retrytimestamp` varchar(20) NOT NULL  default '',
  PRIMARY KEY (`guid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `jobtype` ON `smart_serverjobtypesonhold`(`jobtype`) ;
CREATE  INDEX `retrytime` ON `smart_serverjobtypesonhold`(`retrytimestamp`) ;
ALTER TABLE `smart_serverjobconfigs`
ADD   `userconfigneeded` char(1) NOT NULL  default 'Y',
ADD   `selfdestructive` char(1) NOT NULL  default 'N';
ALTER TABLE `smart_serverplugins`
ADD   `dbprefix` varchar(10) NOT NULL  default '',
ADD   `dbversion` varchar(10) NOT NULL  default '';
ALTER TABLE `smart_semaphores`
ADD   `lifetime` int(11) NOT NULL  default '0';
ALTER TABLE `smart_semaphores` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_placementtiles` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_placementtiles` CHANGE `placementid`   `placementid` bigint(11) NOT NULL  default '0';

CREATE TABLE `smart_objectlabels` (
  `id` bigint(11) NOT NULL  auto_increment,
  `objid` bigint(11) NOT NULL  default '0',
  `name` varchar(250) NOT NULL  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objlabels_objid` ON `smart_objectlabels`(`objid`) ;

CREATE TABLE `smart_objectrelationlabels` (
  `labelid` bigint(11) NOT NULL  default '0',
  `childobjid` bigint(11) NOT NULL  default '0',
  PRIMARY KEY (`labelid`, `childobjid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objrellabels_childobjid` ON `smart_objectrelationlabels`(`childobjid`) ;
UPDATE `smart_config` set `value` = '10.2' where `name` = 'version';
