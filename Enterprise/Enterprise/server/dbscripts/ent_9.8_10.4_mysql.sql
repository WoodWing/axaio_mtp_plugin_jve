ALTER TABLE `smart_authorizations`
ADD   `bundle` int(11) NOT NULL  default '0';
ALTER TABLE `smart_authorizations` CHANGE `rights`   `rights` varchar(1024) NOT NULL  default '';
ALTER TABLE `smart_deletedobjects`
ADD   `masterid` bigint(11) NOT NULL  default '0',
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
ADD   `masterid` bigint(11) NOT NULL  default '0',
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
ALTER TABLE `smart_placements` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_placements` CHANGE `parent`   `parent` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_placements` CHANGE `child`   `child` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_elements` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_elements` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_indesignarticles` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_idarticlesplacements`
ADD   `code` int(11) NOT NULL  default '0';
ALTER TABLE `smart_idarticlesplacements` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_idarticlesplacements` CHANGE `plcid`   `plcid` bigint(11) NOT NULL  default 0;
CREATE  INDEX `plcid_idarticlesplacements` ON `smart_idarticlesplacements`(`plcid`) ;
ALTER TABLE `smart_objectoperations` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectoperations` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_properties` CHANGE `templateid`   `templateid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_publobjects` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_settings` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
CREATE  INDEX `cost_states` ON `smart_states`(`code`, `state`) ;
ALTER TABLE `smart_tickets` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_terms` CHANGE `entityid`   `entityid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_mtpsentobjects` CHANGE `objid`   `objid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_messagelog` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_objectflags` CHANGE `objid`   `objid` bigint(11) NOT NULL  default '0';

CREATE TABLE `smart_featureaccess` (
  `featurename` varchar(255) NOT NULL  default '',
  `featureid` int(4) NOT NULL  default '0',
  `accessflag` int(4) NOT NULL  default '0',
  PRIMARY KEY (`featurename`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `faid_profiles` ON `smart_featureaccess`(`featureid`) ;
CREATE  INDEX `fafl_profiles` ON `smart_featureaccess`(`accessflag`) ;
ALTER TABLE `smart_appsessions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_appsessions` CHANGE `articleid`   `articleid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_appsessions` CHANGE `templateid`   `templateid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_appsessions` CHANGE `layoutid`   `layoutid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_dsqueryplacements` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_dsobjupdates` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_targets` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_targets` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_targets` CHANGE `objectrelationid`   `objectrelationid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_publishhistory` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_publishhistory` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_pubpublishedissues` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
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
ALTER TABLE `smart_indesignserverjobs`
ADD   `pickuptime` varchar(30) NOT NULL  default '';
ALTER TABLE `smart_indesignserverjobs` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_serverplugins`
ADD   `dbprefix` varchar(10) NOT NULL  default '',
ADD   `dbversion` varchar(10) NOT NULL  default '';
ALTER TABLE `smart_semaphores` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_placementtiles` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_placementtiles` CHANGE `placementid`   `placementid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectlabels` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectlabels` CHANGE `objid`   `objid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectrelationlabels` CHANGE `labelid`   `labelid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectrelationlabels` CHANGE `childobjid`   `childobjid` bigint(11) NOT NULL  default '0';
UPDATE `smart_config` set `value` = '10.4' where `name` = 'version';
