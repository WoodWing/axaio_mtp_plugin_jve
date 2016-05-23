ALTER TABLE `smart_states`
ADD   `skipidsa` char(2) not null  default '';
INSERT INTO `smart_config` (`name`, `value`) VALUES ('idsautomationskipstatus', 'yes');
