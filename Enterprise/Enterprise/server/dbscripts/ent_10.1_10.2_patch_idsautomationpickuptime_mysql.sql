ALTER TABLE `smart_indesignserverjobs`
ADD   `pickuptime` varchar(30) NOT NULL  default '';
INSERT INTO `smart_config` (`name`, `value`) VALUES ('idsautomationpickuptime', 'yes');
