ALTER TABLE `smart_authorizations`
ADD   `bundle` int(11) not null  default '0';
UPDATE `smart_config` set `value` = '10.1' where `name` = 'version';
