ALTER TABLE `smart_authorizations`
ADD   `bundle` int(11) not null  default '0';
ALTER TABLE `smart_deletedobjects` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_objects` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_objectversions` CHANGE `dpi`   `dpi` double not null  default '0';

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
UPDATE `smart_config` set `value` = '10.1' where `name` = 'version';
