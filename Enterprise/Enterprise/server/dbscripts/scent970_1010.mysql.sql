ALTER TABLE `smart_states`
ADD   `skipidsa` char(2) not null  default '';
CREATE  INDEX `lt_indesignserverjobs` on `smart_indesignserverjobs`(`locktoken`) ;
UPDATE `smart_config` set `value` = '10.1' where `name` = 'version';
