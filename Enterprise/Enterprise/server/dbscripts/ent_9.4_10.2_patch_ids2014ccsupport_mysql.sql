ALTER TABLE `smart_indesignserverjobs`
ADD   `maxservermajorversion` mediumint(9) not null  default '0',
ADD   `maxserverminorversion` mediumint(9) not null  default '0';
ALTER TABLE `smart_indesignserverjobs` CHANGE `servermajorversion`   `minservermajorversion` mediumint(9) not null  default '0';
ALTER TABLE `smart_indesignserverjobs` CHANGE `serverminorversion`   `minserverminorversion` mediumint(9) not null  default '0';
INSERT INTO `smart_config` (`name`, `value`) VALUES ('ids2014ccsupport', 'yes');
