ALTER TABLE `smart_states`
ADD   `skipidsa` char(2) NOT NULL  default '';
INSERT INTO `smart_config` (`name`, `value`) VALUES ('idsautomationskipstatus', 'yes');
