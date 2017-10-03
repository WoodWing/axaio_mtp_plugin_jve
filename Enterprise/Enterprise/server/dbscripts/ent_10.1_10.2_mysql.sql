ALTER TABLE `smart_authorizations` CHANGE `rights`   `rights` varchar(1024) NOT NULL  default '';

CREATE TABLE `smart_featureaccess` (
  `featurename` varchar(255) NOT NULL  default '',
  `featureid` int(4) NOT NULL  default '0',
  `accessflag` int(4) NOT NULL  default '0',
  PRIMARY KEY (`featurename`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `faid_profiles` ON `smart_featureaccess`(`featureid`) ;
CREATE UNIQUE INDEX `faaf_profiles` ON `smart_featureaccess`(`accessflag`) ;
ALTER TABLE `smart_serverplugins`
ADD   `dbprefix` varchar(10) NOT NULL  default '',
ADD   `dbversion` varchar(10) NOT NULL  default '';
UPDATE `smart_config` set `value` = '10.2' where `name` = 'version';
