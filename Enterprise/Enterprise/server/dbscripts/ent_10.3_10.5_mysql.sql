ALTER TABLE `smart_deletedobjects`
ADD   `masterid` bigint(11) NOT NULL  default '0';
ALTER TABLE `smart_objects`
ADD   `masterid` bigint(11) NOT NULL  default '0';
CREATE  INDEX `tmid_messagelog` ON `smart_messagelog`(`threadmessageid`) ;
UPDATE `smart_config` set `value` = '10.5' where `name` = 'version';