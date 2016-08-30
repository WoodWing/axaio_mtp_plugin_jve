ALTER TABLE `smart_deletedobjects` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_objects` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_objectversions` CHANGE `dpi`   `dpi` double not null  default '0';
ALTER TABLE `smart_states`
ADD   `skipidsa` char(2) not null  default '';
CREATE  INDEX `lt_indesignserverjobs` on `smart_indesignserverjobs`(`locktoken`) ;
UPDATE `smart_config` set `value` = '10.1' where `name` = 'version';
