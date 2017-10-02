ALTER TABLE `smart_actionproperties` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_authorizations` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_authorizations` CHANGE `rights`   `rights` varchar(1024) NOT NULL  default '';
ALTER TABLE `smart_config` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_deletedobjects` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_log` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_log` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_log` CHANGE `parent`   `parent` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectlocks` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectlocks` CHANGE `object`   `object` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectrelations` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectrelations` CHANGE `parent`   `parent` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objectrelations` CHANGE `child`   `child` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objects` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectversions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectversions` CHANGE `objid`   `objid` bigint(11) NOT NULL  default '0';
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
ALTER TABLE `smart_idarticlesplacements` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_idarticlesplacements` CHANGE `plcid`   `plcid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_objectoperations` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_objectoperations` CHANGE `objid`   `objid` bigint(11) NOT NULL  default 0;
ALTER TABLE `smart_publobjects` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_issueeditions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_settings` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_tickets` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_termentities` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_terms` CHANGE `entityid`   `entityid` bigint(11) NOT NULL  default '0';
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
ALTER TABLE `smart_targets` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_targets` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_targets` CHANGE `objectrelationid`   `objectrelationid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_publishhistory` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_publishhistory` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_pubpublishedissues` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_publishedobjectshist` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_publishedobjectshist` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_publishedobjectshist` CHANGE `publishid`   `publishid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_publishedplcmtshist` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_publishedplcmtshist` CHANGE `objectid`   `objectid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_publishedplcmtshist` CHANGE `publishid`   `publishid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_targeteditions` CHANGE `id`   `id` bigint(11) NOT NULL  auto_increment;
ALTER TABLE `smart_targeteditions` CHANGE `targetid`   `targetid` bigint(11) NOT NULL  default '0';
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
UPDATE `smart_config` set `value` = '10.2' where `name` = 'version';
