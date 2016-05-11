CREATE TABLE `axaio_mtp_trigger` (
  `publication_id` int(11) not null ,
  `issue_id` int(11) not null  default '0',
  `state_trigger_layout` int(11) not null ,
  `state_trigger_article` int(11) not null  default 0,
  `state_trigger_image` int(11) not null  default 0,
  `state_after_layout` int(11) not null  default 0,
  `state_after_article` int(11) not null  default 0,
  `state_after_image` int(11) not null  default 0,
  `mtp_jobname` varchar(2048) not null  default '',
  `state_error_layout` int(11) NOT NULL DEFAULT '0',
  `quiet` tinyint(1) unsigned DEFAULT '0',
  `prio` tinyint(1) unsigned DEFAULT '2',
   PRIMARY KEY (`publication_id`,`issue_id`,`state_trigger_layout`,`state_trigger_article`,`state_trigger_image`)

) DEFAULT CHARSET=utf8;
CREATE  INDEX `ii_mtp` on `axaio_mtp_trigger`(`issue_id`) ;

CREATE TABLE `axaio_mtp_sentobjects` (
  `objid` int(11) not null  default '0',
  `publication_id` int(11) not null ,
  `issue_id` int(11) not null  default '0',
  `state_trigger_layout` int(11) not null ,
  `printstate` mediumint(1) not null ,
  PRIMARY KEY (`objid`, `publication_id`, `issue_id`, `state_trigger_layout`, `printstate`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `ii_mtpsentobjects` on `axaio_mtp_sentobjects`(`issue_id`) ;
CREATE  INDEX `ls_mtpsentobjects` on `axaio_mtp_sentobjects`(`state_trigger_layout`) ;

CREATE TABLE `axaio_mtp_process_options` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(255) DEFAULT NULL,
  `option_value` longtext,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
