 CREATE TABLE `smart_claro` (
	`id` MEDIUMINT( 9 ) NOT NULL AUTO_INCREMENT ,
	`oid` MEDIUMINT( 9 ) NOT NULL ,
	`cropx` DOUBLE NOT NULL ,
	`cropy` DOUBLE NOT NULL ,
	`rotate` DOUBLE NOT NULL ,
	`width` DOUBLE NOT NULL ,
	`height` DOUBLE NOT NULL,
	PRIMARY KEY (`id`)
)DEFAULT CHARSET=utf8;

CREATE  INDEX `oid_carlo` on `smart_claro`(`oid`) ;