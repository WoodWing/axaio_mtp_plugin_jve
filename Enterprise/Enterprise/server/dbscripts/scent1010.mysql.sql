
CREATE TABLE `smart_actionproperties` (
  `id` int(11) not null  auto_increment,
  `publication` int(11) not null  default '0',
  `orderid` int(11) not null  default '0',
  `property` varchar(200) not null  default '',
  `edit` char(2) not null  default '',
  `mandatory` char(2) not null  default '',
  `action` varchar(40) not null  default '',
  `type` varchar(40) not null  default '',
  `restricted` char(2) not null  default '',
  `refreshonchange` char(2) not null  default '',
  `parentfieldid` int(11) not null  default '0',
  `documentid` varchar(512) not null  default '',
  `initialheight` int(4) not null  default '0',
  `multipleobjects` char(2) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `pbac_actionproperties` on `smart_actionproperties`(`publication`, `action`) ;

CREATE TABLE `smart_authorizations` (
  `id` int(11) not null  auto_increment,
  `grpid` int(11) not null  default '0',
  `publication` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `state` int(11) not null  default '0',
  `rights` varchar(40) not null  default '',
  `issue` int(11) not null  default '0',
  `profile` int(11) not null  default '0',
  `bundle` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `gipu_authorizations` on `smart_authorizations`(`grpid`, `publication`) ;
CREATE  INDEX `gipr_authorizations` on `smart_authorizations`(`grpid`, `profile`) ;
INSERT INTO `smart_authorizations` (`id`, `grpid`, `publication`, `section`, `state`, `rights`, `issue`, `profile`, `bundle`) VALUES (1, 2, 1, 0, 0, 'VRWDCKSF', 0, 1, 0);

CREATE TABLE `smart_config` (
  `id` int(11) not null  auto_increment,
  `name` varchar(200) not null  default '',
  `value` blob not null ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
INSERT INTO `smart_config` (`id`, `name`, `value`) VALUES (1, 'version', '00');

CREATE TABLE `smart_deletedobjects` (
  `id` int(11) not null  auto_increment,
  `documentid` varchar(512) not null  default '',
  `type` varchar(20) not null  default '',
  `name` varchar(255) not null  default '',
  `publication` int(11) not null  default '0',
  `issue` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `state` int(11) not null  default '0',
  `routeto` varchar(255) not null  default '',
  `copyright` varchar(255) not null  default '',
  `slugline` varchar(255) not null  default '',
  `comment` varchar(255) not null  default '',
  `author` varchar(255) not null  default '',
  `deadline` varchar(30) not null  default '',
  `urgency` varchar(40) not null  default '',
  `format` varchar(128) not null  default '',
  `width` double not null  default '0',
  `depth` double not null  default '0',
  `dpi` double not null  default '0',
  `lengthwords` int(11) not null  default '0',
  `lengthchars` int(11) not null  default '0',
  `lengthparas` int(11) not null  default '0',
  `lengthlines` int(11) not null  default '0',
  `keywords` blob not null ,
  `modifier` varchar(40) not null  default '',
  `modified` varchar(30) not null  default '',
  `creator` varchar(40) not null  default '',
  `created` varchar(30) not null  default '',
  `deletor` varchar(40) not null  default '',
  `deleted` varchar(30) not null  default '',
  `copyrightmarked` varchar(255) not null  default '',
  `copyrighturl` varchar(255) not null  default '',
  `credit` varchar(255) not null  default '',
  `source` varchar(255) not null  default '',
  `description` blob not null  default '',
  `descriptionauthor` varchar(255) not null  default '',
  `_columns` int(11) not null  default '0',
  `plaincontent` blob not null ,
  `filesize` int(15) not null  default '0',
  `colorspace` varchar(20) not null  default '',
  `pagenumber` int(11) not null  default '0',
  `types` blob not null ,
  `storename` blob not null ,
  `pagerange` varchar(50) not null  default '',
  `highresfile` varchar(255) not null  default '',
  `deadlinesoft` varchar(30) not null  default '',
  `deadlinechanged` char(1) not null  default '',
  `plannedpagerange` varchar(50) not null  default '',
  `majorversion` mediumint(9) not null  default '-1',
  `minorversion` mediumint(9) not null  default '0',
  `encoding` varchar(100) not null  default '',
  `compression` varchar(100) not null  default '',
  `keyframeeveryframes` mediumint(9) not null  default '0',
  `channels` varchar(100) not null  default '',
  `aspectratio` varchar(100) not null  default '',
  `contentsource` varchar(100) not null  default '',
  `rating` tinyint(4) not null  default 0,
  `indexed` char(2) not null  default '',
  `closed` char(2) not null  default '',
  `orientation` tinyint(4) not null  default '0',
  `routetouserid` int(11) not null  default '0',
  `routetogroupid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_groups` (
  `id` int(11) not null  auto_increment,
  `name` varchar(100) not null  default '',
  `descr` varchar(255) not null  default '',
  `admin` char(2) not null  default '',
  `routing` char(2) not null  default '',
  `externalid` varchar(200) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `idnaro_groups` on `smart_groups`(`id`, `name`, `routing`) ;
CREATE  INDEX `na_groups` on `smart_groups`(`name`) ;
INSERT INTO `smart_groups` (`id`, `name`, `descr`, `admin`, `routing`, `externalid`) VALUES (2, 'admin', 'System Admins', 'on', '', '');

CREATE TABLE `smart_log` (
  `id` int(11) not null  auto_increment,
  `user` varchar(50) not null  default '',
  `service` varchar(50) not null  default '',
  `ip` varchar(30) not null  default '',
  `date` varchar(30) not null  default '',
  `objectid` int(11) not null  default '0',
  `publication` int(11) not null  default '0',
  `issue` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `state` int(11) not null  default '0',
  `parent` int(11) not null  default '0',
  `lock` varchar(1) not null  default '',
  `rendition` varchar(10) not null  default '',
  `type` varchar(20) not null  default '',
  `routeto` varchar(255) not null  default '',
  `edition` varchar(255) not null  default '',
  `minorversion` mediumint(9) not null  default '0',
  `channelid` int(11) not null  default '0',
  `majorversion` mediumint(9) not null  default '-1',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_namedqueries` (
  `id` int(11) not null  auto_increment,
  `query` varchar(200) not null  default '',
  `interface` blob not null ,
  `sql` blob not null ,
  `comment` blob not null ,
  `checkaccess` varchar(2) not null  default 'on',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `qe_namedqueries` on `smart_namedqueries`(`query`) ;
INSERT INTO `smart_namedqueries` (`id`, `query`, `interface`, `sql`, `comment`, `checkaccess`) VALUES (2, 'Templates', '', 0x2f2a53454c4543542a2f0d0a73656c656374206f2e6069646020617320604944602c206f2e607479706560206173206054797065602c206f2e606e616d656020617320604e616d65602c2073742e6073746174656020617320605374617465602c20756c2e6066756c6c6e616d656020617320604c6f636b65644279602c20702e607075626c69636174696f6e6020617320605075626c69636174696f6e602c20732e6073656374696f6e60206173206053656374696f6e602c206f2e60636f6d6d656e74602061732060436f6d6d656e74602c206f2e60726f757465746f602061732060526f757465546f602c20756e2e6066756c6c6e616d6560206173206043726561746f72602c206f2e60666f726d6174602061732060466f726d6174602c20756d2e6066756c6c6e616d656020617320604d6f646966696572602c20702e6069646020617320605075626c69636174696f6e4964602c20732e60696460206173206053656374696f6e4964602c2073742e60696460206173206053746174654964602c2073742e60636f6c6f726020617320605374617465436f6c6f72602c206c2e606c6f636b6f66666c696e656020617320604c6f636b466f724f66666c696e65600d0a2f2a46524f4d2a2f0d0a66726f6d2060736d6172745f6f626a6563747360206f0d0a2f2a4a4f494e532a2f0d0a6c656674206a6f696e2060736d6172745f7075626c69636174696f6e73602070206f6e20286f2e607075626c69636174696f6e60203d20702e60696460290d0a6c656674206a6f696e2060736d6172745f7075626c73656374696f6e73602073206f6e20286f2e6073656374696f6e60203d20732e60696460290d0a6c656674206a6f696e2060736d6172745f73746174657360207374206f6e20286f2e60737461746560203d2073742e60696460290d0a6c656674206a6f696e2060736d6172745f6f626a6563746c6f636b7360206c206f6e20286f2e60696460203d206c2e606f626a65637460290d0a6c656674206a6f696e2060736d6172745f75736572736020756c206f6e20286c2e6075737260203d20756c2e607573657260290d0a6c656674206a6f696e2060736d6172745f75736572736020756d206f6e20286f2e606d6f64696669657260203d20756d2e607573657260290d0a6c656674206a6f696e2060736d6172745f75736572736020756e206f6e20286f2e6063726561746f7260203d20756e2e607573657260290d0a2f2a57484552452a2f0d0a776865726520286f2e607479706560203d20274c61796f757454656d706c61746527206f72206f2e607479706560203d202741727469636c6554656d706c61746527206f72206f2e607479706560203d20274c61796f75744d6f64756c6554656d706c6174652729, 0x53686f777320616c6c206c61796f75742074656d706c617465732e, 'on');
INSERT INTO `smart_namedqueries` (`id`, `query`, `interface`, `sql`, `comment`, `checkaccess`) VALUES (5, 'Libraries', '', 0x2f2a53454c4543542a2f0d0a73656c656374206f2e6069646020617320604944602c206f2e607479706560206173206054797065602c206f2e606e616d656020617320604e616d65602c2073742e6073746174656020617320605374617465602c20756c2e6066756c6c6e616d656020617320604c6f636b65644279602c20702e607075626c69636174696f6e6020617320605075626c69636174696f6e602c20732e6073656374696f6e60206173206053656374696f6e602c206f2e60636f6d6d656e74602061732060436f6d6d656e74602c206f2e60726f757465746f602061732060526f757465546f602c20756e2e6066756c6c6e616d6560206173206043726561746f72602c206f2e60666f726d6174602061732060466f726d6174602c20756d2e6066756c6c6e616d656020617320604d6f646966696572602c20702e6069646020617320605075626c69636174696f6e4964602c20732e60696460206173206053656374696f6e4964602c2073742e60696460206173206053746174654964602c2073742e60636f6c6f726020617320605374617465436f6c6f72602c206c2e606c6f636b6f66666c696e656020617320604c6f636b466f724f66666c696e65600d0a2f2a46524f4d2a2f0d0a66726f6d2060736d6172745f6f626a6563747360206f0d0a2f2a4a4f494e532a2f0d0a6c656674206a6f696e2060736d6172745f7075626c69636174696f6e73602070206f6e20286f2e607075626c69636174696f6e60203d20702e60696460290d0a6c656674206a6f696e2060736d6172745f7075626c73656374696f6e73602073206f6e20286f2e6073656374696f6e60203d20732e60696460290d0a6c656674206a6f696e2060736d6172745f73746174657360207374206f6e20286f2e60737461746560203d2073742e60696460290d0a6c656674206a6f696e2060736d6172745f6f626a6563746c6f636b7360206c206f6e20286f2e60696460203d206c2e606f626a65637460290d0a6c656674206a6f696e2060736d6172745f75736572736020756c206f6e20286c2e6075737260203d20756c2e607573657260290d0a6c656674206a6f696e2060736d6172745f75736572736020756d206f6e20286f2e606d6f64696669657260203d20756d2e607573657260290d0a6c656674206a6f696e2060736d6172745f75736572736020756e206f6e20286f2e6063726561746f7260203d20756e2e607573657260290d0a2f2a57484552452a2f0d0a776865726520286f2e607479706560203d20274c6962726172792729, 0x53686f777320616c6c206c69627261726965732e, 'on');

CREATE TABLE `smart_objectlocks` (
  `id` int(11) not null  auto_increment,
  `object` int(11) not null  default '0',
  `usr` varchar(40) not null  default '',
  `timestamp` timestamp not null ,
  `ip` varchar(30) not null  default '',
  `lockoffline` varchar(2) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `ob_objectlocks` on `smart_objectlocks`(`object`) ;
CREATE  INDEX `obusr_objectlocks` on `smart_objectlocks`(`object`, `usr`) ;

CREATE TABLE `smart_objectrelations` (
  `id` int(11) not null  auto_increment,
  `parent` int(11) not null  default '0',
  `child` int(11) not null  default '0',
  `type` varchar(40) not null  default '',
  `subid` varchar(20) not null  default '',
  `pagerange` varchar(50) not null  default '',
  `rating` tinyint(4) not null  default 0,
  `parenttype` varchar(20) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `ch_objectrelations` on `smart_objectrelations`(`parent`, `child`, `subid`, `type`) ;
CREATE  INDEX `pachty_objectrelations` on `smart_objectrelations`(`parent`, `child`, `type`) ;
CREATE  INDEX `child_type_id` on `smart_objectrelations`(`child`, `type`, `id`) ;

CREATE TABLE `smart_objects` (
  `id` int(11) not null  auto_increment,
  `documentid` varchar(512) not null  default '',
  `type` varchar(20) not null  default '',
  `name` varchar(255) not null  default '',
  `publication` int(11) not null  default '0',
  `issue` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `state` int(11) not null  default '0',
  `routeto` varchar(255) not null  default '',
  `copyright` varchar(255) not null  default '',
  `slugline` varchar(255) not null  default '',
  `comment` varchar(255) not null  default '',
  `author` varchar(255) not null  default '',
  `deadline` varchar(30) not null  default '',
  `urgency` varchar(40) not null  default '',
  `format` varchar(128) not null  default '',
  `width` double not null  default '0',
  `depth` double not null  default '0',
  `dpi` double not null  default '0',
  `lengthwords` int(11) not null  default '0',
  `lengthchars` int(11) not null  default '0',
  `lengthparas` int(11) not null  default '0',
  `lengthlines` int(11) not null  default '0',
  `keywords` blob not null ,
  `modifier` varchar(40) not null  default '',
  `modified` varchar(30) not null  default '',
  `creator` varchar(40) not null  default '',
  `created` varchar(30) not null  default '',
  `deletor` varchar(40) not null  default '',
  `deleted` varchar(30) not null  default '',
  `copyrightmarked` varchar(255) not null  default '',
  `copyrighturl` varchar(255) not null  default '',
  `credit` varchar(255) not null  default '',
  `source` varchar(255) not null  default '',
  `description` blob not null  default '',
  `descriptionauthor` varchar(255) not null  default '',
  `_columns` int(11) not null  default '0',
  `plaincontent` blob not null ,
  `filesize` int(15) not null  default '0',
  `colorspace` varchar(20) not null  default '',
  `types` blob not null ,
  `pagenumber` int(11) not null  default '0',
  `storename` blob not null ,
  `pagerange` varchar(50) not null  default '',
  `highresfile` varchar(255) not null  default '',
  `deadlinesoft` varchar(30) not null  default '',
  `deadlinechanged` char(1) not null  default '',
  `plannedpagerange` varchar(50) not null  default '',
  `majorversion` mediumint(9) not null  default '-1',
  `minorversion` mediumint(9) not null  default '0',
  `encoding` varchar(100) not null  default '',
  `compression` varchar(100) not null  default '',
  `keyframeeveryframes` mediumint(9) not null  default '0',
  `channels` varchar(100) not null  default '',
  `aspectratio` varchar(100) not null  default '',
  `contentsource` varchar(100) not null  default '',
  `rating` tinyint(4) not null  default 0,
  `indexed` char(2) not null  default '',
  `closed` char(2) not null  default '',
  `routetouserid` int(11) not null  default '0',
  `routetogroupid` int(11) not null  default '0',
  `orientation` tinyint(4) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `nm_objects` on `smart_objects`(`name`) ;
CREATE  INDEX `pbsectstate_objects` on `smart_objects`(`publication`, `section`, `state`, `closed`) ;
CREATE  INDEX `pubid_objects` on `smart_objects`(`publication`, `id`, `closed`) ;
CREATE  INDEX `mo_objects` on `smart_objects`(`modifier`) ;
CREATE  INDEX `roid_objects` on `smart_objects`(`routeto`, `id`, `closed`) ;
CREATE  INDEX `codo_objects` on `smart_objects`(`contentsource`, `documentid`) ;

CREATE TABLE `smart_objectversions` (
  `id` int(11) not null  auto_increment,
  `objid` int(11) not null  default '0',
  `minorversion` mediumint(9) not null  default '0',
  `modifier` varchar(40) not null  default '',
  `comment` varchar(255) not null  default '',
  `slugline` varchar(255) not null  default '',
  `created` varchar(30) not null  default '',
  `types` blob not null ,
  `format` varchar(128) not null  default '',
  `width` double not null  default '0',
  `depth` double not null  default '0',
  `dpi` double not null  default '0',
  `lengthwords` int(11) not null  default '0',
  `lengthchars` int(11) not null  default '0',
  `lengthparas` int(11) not null  default '0',
  `lengthlines` int(11) not null  default '0',
  `keywords` blob not null ,
  `description` blob not null ,
  `descriptionauthor` varchar(255) not null  default '',
  `_columns` int(11) not null  default '0',
  `plaincontent` blob not null ,
  `filesize` int(15) not null  default '0',
  `colorspace` varchar(20) not null  default '',
  `orientation` tinyint(4) not null  default '0',
  `state` int(11) not null  default '0',
  `majorversion` mediumint(9) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `oive_objectversions` on `smart_objectversions`(`objid`, `majorversion`, `minorversion`) ;

CREATE TABLE `smart_objectrenditions` (
  `id` int(11) not null  auto_increment,
  `objid` int(11) not null  default '0',
  `editionid` int(11) not null  default '0',
  `rendition` varchar(10) not null  default '',
  `format` varchar(128) not null  default '',
  `majorversion` mediumint(9) not null  default '0',
  `minorversion` mediumint(9) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `obed_objectrenditions` on `smart_objectrenditions`(`objid`, `editionid`, `rendition`) ;

CREATE TABLE `smart_pages` (
  `id` int(11) not null  auto_increment,
  `objid` int(11) not null  default '0',
  `width` double not null  default '0',
  `height` double not null  default '0',
  `pagenumber` varchar(20) not null  default '',
  `pageorder` mediumint(9) not null  default '0',
  `nr` mediumint(9) not null  default '0',
  `types` blob not null ,
  `edition` int(11) not null  default '0',
  `master` varchar(255) not null  default '',
  `instance` varchar(40) not null  default 'Production',
  `pagesequence` mediumint(9) not null  default '0',
  `orientation` varchar(9) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `obpaed_pages` on `smart_pages`(`objid`, `pageorder`, `edition`) ;

CREATE TABLE `smart_placements` (
  `id` int(11) not null  auto_increment,
  `parent` int(11) not null  default '0',
  `child` int(11) not null  default '0',
  `page` mediumint(9) not null  default '0',
  `element` varchar(200) not null  default '',
  `elementid` varchar(200) not null  default '',
  `frameorder` mediumint(9) not null  default '0',
  `frameid` varchar(200) not null  default '',
  `_left` double not null  default '0',
  `top` double not null  default '0',
  `width` double not null  default '0',
  `height` double not null  default '0',
  `overset` double not null  default '0',
  `oversetchars` int(11) not null  default '0',
  `oversetlines` int(11) not null  default '0',
  `layer` varchar(200) not null  default '',
  `content` blob not null ,
  `type` varchar(40) not null ,
  `edition` int(11) not null  default '0',
  `contentdx` double not null  default 0,
  `contentdy` double not null  default 0,
  `scalex` double not null  default 1,
  `scaley` double not null  default 1,
  `pagesequence` mediumint(9) not null  default '0',
  `pagenumber` varchar(20) not null  default '',
  `formwidgetid` varchar(200) not null  default '',
  `frametype` varchar(20) not null  default '',
  `splineid` varchar(200) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `pachty_placements` on `smart_placements`(`parent`, `child`, `type`) ;
CREATE  INDEX `ei_placements` on `smart_placements`(`elementid`) ;
CREATE  INDEX `chty_placements` on `smart_placements`(`child`, `type`) ;

CREATE TABLE `smart_elements` (
  `id` int(11) not null  auto_increment,
  `guid` varchar(200) not null  default '',
  `name` varchar(200) not null  default '',
  `objid` int(11) not null  default 0,
  `lengthwords` int(11) not null  default '0',
  `lengthchars` int(11) not null  default '0',
  `lengthparas` int(11) not null  default '0',
  `lengthlines` int(11) not null  default '0',
  `snippet` varchar(255) not null  default '',
  `version` varchar(50) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `oigu_elements` on `smart_elements`(`objid`, `guid`) ;

CREATE TABLE `smart_indesignarticles` (
  `objid` int(11) not null  default 0,
  `artuid` varchar(40) not null  default '',
  `name` varchar(200) not null  default '',
  `code` int(11) not null  default '0',
  PRIMARY KEY (`objid`, `artuid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_idarticlesplacements` (
  `objid` int(11) not null  default 0,
  `artuid` varchar(40) not null  default '',
  `plcid` int(11) not null  default 0,
  PRIMARY KEY (`objid`, `artuid`, `plcid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_objectoperations` (
  `id` int(11) not null  auto_increment,
  `objid` int(11) not null  default 0,
  `guid` varchar(40) not null  default '',
  `type` varchar(200) not null  default '',
  `name` varchar(200) not null  default '',
  `params` blob not null ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objid_objectoperations` on `smart_objectoperations`(`objid`) ;

CREATE TABLE `smart_properties` (
  `id` int(11) not null  auto_increment,
  `publication` int(11) not null  default '0',
  `objtype` varchar(40) not null  default '',
  `name` varchar(200) not null  default '',
  `dispname` varchar(200) not null  default '',
  `category` varchar(200) not null  default '',
  `type` varchar(40) not null  default '',
  `defaultvalue` varchar(200) not null  default '',
  `valuelist` blob not null ,
  `minvalue` varchar(200) not null  default '',
  `maxvalue` varchar(200) not null  default '',
  `maxlen` bigint(8) not null  default '0',
  `dbupdated` tinyint(4) not null  default '0',
  `entity` varchar(20) not null  default 'Object',
  `serverplugin` varchar(64) not null  default '',
  `adminui` varchar(2) not null  default 'on',
  `propertyvalues` blob not null ,
  `minresolution` varchar(200) not null  default '',
  `maxresolution` varchar(200) not null  default '',
  `publishsystem` varchar(64) not null  default '',
  `templateid` int(11) not null  default 0,
  `termentityid` int(11) not null  default '0',
  `suggestionentity` varchar(200) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `puob_properties` on `smart_properties`(`publication`, `objtype`) ;
CREATE  INDEX `pudb_properties` on `smart_properties`(`publication`, `dbupdated`) ;

CREATE TABLE `smart_publadmin` (
  `id` int(11) not null  auto_increment,
  `publication` int(11) not null  default '0',
  `grpid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `pugi_publadmin` on `smart_publadmin`(`publication`, `grpid`) ;

CREATE TABLE `smart_publications` (
  `id` int(11) not null  auto_increment,
  `publication` varchar(255) not null  default '',
  `code` int(4) not null  default '0',
  `email` char(2) not null  default '',
  `description` blob not null ,
  `readingorderrev` varchar(2) not null  default '',
  `autopurge` int(5) not null  default 0,
  `defaultchannelid` int(11) not null  default '0',
  `calculatedeadlines` char(2) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `pb_publications` on `smart_publications`(`publication`) ;
CREATE  INDEX `idpb_publications` on `smart_publications`(`id`, `publication`) ;
INSERT INTO `smart_publications` (`id`, `publication`, `code`, `email`, `description`, `readingorderrev`, `autopurge`, `defaultchannelid`, `calculatedeadlines`) VALUES (1, 'WW News', 0, '', '', '', 0, 1, '');

CREATE TABLE `smart_publsections` (
  `id` int(11) not null  auto_increment,
  `publication` int(11) not null  default '0',
  `section` varchar(255) not null  default '',
  `issue` int(11) not null  default '0',
  `code` int(4) not null  default '0',
  `description` blob not null ,
  `pages` int(4) not null  default '0',
  `deadline` varchar(30) not null  default '',
  `deadlinerelative` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `pbis_publsections` on `smart_publsections`(`publication`, `issue`) ;
CREATE  INDEX `se_publsections` on `smart_publsections`(`section`) ;
INSERT INTO `smart_publsections` (`id`, `publication`, `section`, `issue`, `code`, `description`, `pages`, `deadline`, `deadlinerelative`) VALUES (1, 1, 'News', 0, 10, '', 0, '', 0 );
INSERT INTO `smart_publsections` (`id`, `publication`, `section`, `issue`, `code`, `description`, `pages`, `deadline`, `deadlinerelative`) VALUES (2, 1, 'Sport', 0, 20, '', 0, '', 0 );

CREATE TABLE `smart_publobjects` (
  `id` int(11) not null  auto_increment,
  `publicationid` int(11) not null  default '0',
  `issueid` int(11) not null  default '0',
  `objectid` int(11) not null  default '0',
  `grpid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `puisobgr_publobjects` on `smart_publobjects`(`publicationid`, `issueid`, `objectid`, `grpid`) ;

CREATE TABLE `smart_issueeditions` (
  `id` int(11) not null  auto_increment,
  `issue` int(11) not null  default '0',
  `edition` int(11) not null  default '0',
  `deadline` varchar(30) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `is_issueeditions` on `smart_issueeditions`(`issue`) ;
CREATE  INDEX `ed_issueeditions` on `smart_issueeditions`(`edition`) ;

CREATE TABLE `smart_routing` (
  `id` int(11) not null  auto_increment,
  `publication` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `state` int(11) not null  default '0',
  `routeto` varchar(255) not null  default '',
  `issue` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `pbisse_routing` on `smart_routing`(`publication`, `issue`, `section`) ;
CREATE  INDEX `st_routing` on `smart_routing`(`state`) ;

CREATE TABLE `smart_settings` (
  `id` int(11) not null  auto_increment,
  `user` varchar(200) not null  default '',
  `setting` varchar(200) not null  default '',
  `value` mediumblob not null ,
  `appname` varchar(200) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `us_settings` on `smart_settings`(`user`) ;
CREATE  INDEX `se_settings` on `smart_settings`(`setting`) ;

CREATE TABLE `smart_states` (
  `id` int(11) not null  auto_increment,
  `publication` int(11) not null  default '0',
  `type` varchar(40) not null  default '',
  `state` varchar(40) not null  default '',
  `produce` char(2) not null  default '',
  `color` varchar(11) not null  default '',
  `nextstate` int(11) not null  default '0',
  `code` int(4) not null  default '0',
  `issue` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `deadlinestate` int(11) not null  default '0',
  `deadlinerelative` int(11) not null  default '0',
  `createpermanentversion` char(2) not null  default '',
  `removeintermediateversions` char(2) not null  default '',
  `readyforpublishing` char(2) not null  default '',
  `automaticallysendtonext` char(2) not null  default '',
  `phase` varchar(40) not null  default 'Production',
  `skipidsa` char(2) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `st_states` on `smart_states`(`state`) ;
CREATE  INDEX `pbistyse_states` on `smart_states`(`publication`, `issue`, `type`, `section`) ;
CREATE  INDEX `istyse_states` on `smart_states`(`issue`, `type`, `section`) ;
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (1, 1, 'Article', 'Draft text', '', '#FF0000', 2, 10, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (2, 1, 'Article', 'Ready', '', '#00FF00', 0, 20, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (3, 1, 'Layout', 'Layouts', '', '#0000FF', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (4, 1, 'LayoutTemplate', 'Layout Templates', '', '#FFFF99', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (5, 1, 'ArticleTemplate', 'Article Templates', '', '#FFFF99', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (6, 1, 'Image', 'Images', '', '#FFFF00', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (7, 1, 'Advert', 'Adverts', '', '#99CCFF', 0, 0, 0, 0, 0, 0, '', '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (8, 1, 'Video', 'Videos', '', '#FFFF00', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (9, 1, 'Audio', 'Audios', '', '#FFFF00', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (10, 1, 'Library', 'Libraries', '', '#888888', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (11, 1, 'Dossier', 'Dossiers', '', '#BBBBBB', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (12, 1, 'DossierTemplate', 'Dossier Templates', '', '#BBBBBB', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (13, 1, 'LayoutModule', 'Layout Modules', '', '#D7C101', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (14, 1, 'LayoutModuleTemplate', 'Layout Module Templates', '', '#FFE553', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (15, 1, 'Task', 'Assigned', '', '#AAAAAA', 15, 10, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (16, 1, 'Task', 'In progress', '', '#AAAAAA', 16, 20, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (17, 1, 'Task', 'Completed', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (18, 1, 'Hyperlink', 'Hyperlinks', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (19, 1, 'Other', 'Others', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (20, 1, 'Archive', 'Archives', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (21, 1, 'Presentation', 'Presentations', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (22, 1, 'Spreadsheet', 'Draft', '', '#FF0000', 23, 10, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (23, 1, 'Spreadsheet', 'Ready', '', '#00FF00', 0, 20, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (24, 1, 'PublishForm', 'Publish Forms', '', '#AAAAAA', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');
INSERT INTO `smart_states` (`id`, `publication`, `type`, `state`, `produce`, `color`, `nextstate`, `code`, `issue`, `section`, `deadlinestate`, `deadlinerelative`, `createpermanentversion`, `removeintermediateversions`, `readyforpublishing`, `automaticallysendtonext`, `phase`, `skipidsa`) VALUES (25, 1, 'PublishFormTemplate', 'Publish Form Templates', '', '#AAAAAA', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', '');

CREATE TABLE `smart_tickets` (
  `id` int(11) not null  auto_increment,
  `ticketid` varchar(40) not null  default '',
  `usr` varchar(40) not null  default '',
  `db` varchar(255) not null  default '',
  `clientname` varchar(255) not null  default '',
  `clientip` varchar(40) not null  default '',
  `appname` varchar(200) not null  default '',
  `appversion` varchar(200) not null  default '',
  `appserial` varchar(200) not null  default '',
  `logon` varchar(20) not null  default '',
  `expire` varchar(30) not null  default '',
  `appproductcode` varchar(40) not null  default '',
  `masterticketid` varchar(40) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `ti_tickets` on `smart_tickets`(`ticketid`) ;
CREATE  INDEX `us_tickets` on `smart_tickets`(`usr`) ;
CREATE  INDEX `mtid_tickets` on `smart_tickets`(`masterticketid`) ;

CREATE TABLE `smart_termentities` (
  `id` int(11) not null  auto_increment,
  `name` varchar(255) not null  default '',
  `provider` varchar(40) not null  default '',
  `publishsystemid` varchar(40) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `te_name` on `smart_termentities`(`name`) ;
CREATE  INDEX `te_provider` on `smart_termentities`(`provider`) ;
CREATE  INDEX `te_termentity` on `smart_termentities`(`name`, `provider`) ;

CREATE TABLE `smart_terms` (
  `entityid` int(11) not null  default '0',
  `displayname` varchar(255) not null  default '',
  `normalizedname` varchar(255) not null  default '',
  `ligatures` varchar(255) not null  default '',
  PRIMARY KEY (`entityid`, `displayname`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `tm_entityid` on `smart_terms`(`entityid`) ;
CREATE  INDEX `tm_normalizedname` on `smart_terms`(`entityid`, `normalizedname`) ;

CREATE TABLE `smart_users` (
  `id` int(11) not null  auto_increment,
  `user` varchar(40) not null  default '',
  `fullname` varchar(255) not null  default '',
  `pass` varchar(128) not null  default '',
  `disable` char(2) not null  default '',
  `fixedpass` char(2) not null  default '',
  `email` varchar(100) not null  default '',
  `emailgrp` char(2) not null  default '',
  `emailusr` char(2) not null  default '',
  `language` varchar(4) not null  default '',
  `startdate` varchar(30) not null  default '',
  `enddate` varchar(30) not null  default '',
  `expirepassdate` varchar(30) not null  default '',
  `expiredays` int(4) not null  default '0',
  `trackchangescolor` varchar(11) not null  default '',
  `lastlogondate` varchar(30) not null  default '',
  `organization` varchar(255) not null  default '',
  `location` varchar(255) not null  default '',
  `externalid` varchar(200) not null  default '',
  `importonlogon` char(2) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `us_users` on `smart_users`(`user`) ;
CREATE  INDEX `fu_users` on `smart_users`(`fullname`) ;
INSERT INTO `smart_users` (`id`, `user`, `fullname`, `pass`, `disable`, `fixedpass`, `email`, `emailgrp`, `emailusr`, `language`, `startdate`, `enddate`, `expirepassdate`, `expiredays`, `trackchangescolor`, `lastlogondate`, `organization`, `location`, `externalid`, `importonlogon`) VALUES (1, 'woodwing', 'WoodWing Software', '', '', '', '', '', '', 'enUS', '', '', '', 0, '#FF0000', '', '', '', '', '' );

CREATE TABLE `smart_usrgrp` (
  `id` int(11) not null  auto_increment,
  `usrid` int(11) not null  default '0',
  `grpid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `usgi_usrgrp` on `smart_usrgrp`(`usrid`, `grpid`) ;
CREATE  INDEX `gi_usrgrp` on `smart_usrgrp`(`grpid`) ;
INSERT INTO `smart_usrgrp` (`id`, `usrid`, `grpid`) VALUES (2, 1, 2);

CREATE TABLE `smart_mtp` (
  `publid` int(11) not null ,
  `issueid` int(11) not null  default '0',
  `laytriggerstate` int(11) not null ,
  `arttriggerstate` int(11) not null  default 0,
  `imgtriggerstate` int(11) not null  default 0,
  `layprogstate` int(11) not null  default 0,
  `artprogstate` int(11) not null  default 0,
  `imgprogstate` int(11) not null  default 0,
  `mtptext` blob not null  default '',
  PRIMARY KEY (`publid`, `issueid`, `laytriggerstate`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `ii_mtp` on `smart_mtp`(`issueid`) ;

CREATE TABLE `smart_mtpsentobjects` (
  `objid` int(11) not null  default '0',
  `publid` int(11) not null ,
  `issueid` int(11) not null  default '0',
  `laytriggerstate` int(11) not null ,
  `printstate` mediumint(1) not null ,
  PRIMARY KEY (`objid`, `publid`, `issueid`, `laytriggerstate`, `printstate`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `ii_mtpsentobjects` on `smart_mtpsentobjects`(`issueid`) ;
CREATE  INDEX `ls_mtpsentobjects` on `smart_mtpsentobjects`(`laytriggerstate`) ;

CREATE TABLE `smart_messagelog` (
  `id` bigint(20) not null  auto_increment,
  `objid` int(11) not null  default 0,
  `userid` int(11) not null  default 0,
  `messagetype` varchar(255) not null ,
  `messagetypedetail` varchar(255) not null ,
  `message` blob not null ,
  `date` varchar(30) not null  default '',
  `expirationdate` varchar(30) not null  default '',
  `messagelevel` varchar(255) not null  default '',
  `fromuser` varchar(255) not null  default '',
  `msgid` varchar(200) not null  default '',
  `anchorx` double not null  default '0',
  `anchory` double not null  default '0',
  `left` double not null  default '0',
  `top` double not null  default '0',
  `width` double not null  default '0',
  `height` double not null  default '0',
  `page` mediumint(9) not null  default '0',
  `version` varchar(200) not null  default '',
  `color` varchar(11) not null  default '',
  `pagesequence` mediumint(9) not null  default '0',
  `threadmessageid` varchar(200) not null  default '',
  `replytomessageid` varchar(200) not null  default '',
  `messagestatus` varchar(15) not null  default 'None',
  `majorversion` mediumint(9) not null  default '0',
  `minorversion` mediumint(9) not null  default '0',
  `isread` varchar(2) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `oimtpa_messagelog` on `smart_messagelog`(`objid`, `messagetype`, `page`) ;
CREATE  INDEX `oimtd_messagelog` on `smart_messagelog`(`objid`, `messagetypedetail`) ;
CREATE  INDEX `mi_messagelog` on `smart_messagelog`(`msgid`) ;
CREATE  INDEX `uid_messagelog` on `smart_messagelog`(`userid`) ;

CREATE TABLE `smart_objectflags` (
  `objid` int(11) not null ,
  `flagorigin` varchar(255) not null ,
  `flag` mediumint(9) not null ,
  `severity` mediumint(9) not null ,
  `message` blob not null  default '',
  `locked` mediumint(1) not null  default 0,
  PRIMARY KEY (`objid`, `flagorigin`, `flag`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_issuesection` (
  `id` int(11) not null  auto_increment,
  `issue` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `deadline` varchar(30) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `isse_issuesection` on `smart_issuesection`(`issue`, `section`) ;

CREATE TABLE `smart_issuesectionstate` (
  `id` int(11) not null  auto_increment,
  `issue` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `state` int(11) not null  default '0',
  `deadline` varchar(30) not null  default '',
  `deadlinerelative` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `issest_issuesectionstate` on `smart_issuesectionstate`(`issue`, `section`, `state`) ;

CREATE TABLE `smart_sectionstate` (
  `id` int(11) not null  auto_increment,
  `section` int(11) not null  default '0',
  `state` int(11) not null  default '0',
  `deadlinerelative` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `sest_sectionstate` on `smart_sectionstate`(`section`, `state`) ;

CREATE TABLE `smart_profiles` (
  `id` int(11) not null  auto_increment,
  `profile` varchar(255) not null  default '',
  `code` int(4) not null  default '0',
  `description` blob not null ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `pr_profiles` on `smart_profiles`(`profile`) ;
INSERT INTO `smart_profiles` (`id`, `profile`, `code`, `description`) VALUES (1, 'Full Control', 0, 'All features enabled');

CREATE TABLE `smart_profilefeatures` (
  `id` int(11) not null  auto_increment,
  `profile` int(11) not null  default '0',
  `feature` mediumint(9) not null  default '0',
  `value` varchar(20) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `prfe_profiles` on `smart_profilefeatures`(`profile`, `feature`) ;
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (1, 1, 1, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (2, 1, 2, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (3, 1, 3, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (4, 1, 4, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (5, 1, 5, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (6, 1, 6, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (7, 1, 7, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (8, 1, 8, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (9, 1, 9, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (10, 1, 10, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (11, 1, 99, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (12, 1, 101, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (13, 1, 102, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (14, 1, 103, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (15, 1, 104, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (16, 1, 105, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (17, 1, 106, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (18, 1, 107, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (19, 1, 108, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (20, 1, 109, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (21, 1, 110, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (22, 1, 111, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (23, 1, 112, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (24, 1, 113, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (25, 1, 114, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (26, 1, 115, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (27, 1, 116, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (28, 1, 117, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (29, 1, 118, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (30, 1, 119, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (31, 1, 120, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (32, 1, 121, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (33, 1, 122, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (34, 1, 124, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (35, 1, 125, 'No');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (36, 1, 126, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (37, 1, 127, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (38, 1, 128, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (39, 1, 129, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (40, 1, 130, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (41, 1, 131, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (42, 1, 132, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (43, 1, 133, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (44, 1, 134, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (45, 1, 135, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (46, 1, 1001, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (47, 1, 1002, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (48, 1, 1003, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (49, 1, 1004, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (52, 1, 1007, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (53, 1, 1008, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (54, 1, 91, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (55, 1, 92, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (56, 1, 93, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (57, 1, 90, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (58, 1, 98, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (59, 1, 88, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (61, 1, 87, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (62, 1, 86, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (63, 1, 85, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (64, 1, 1009, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (65, 1, 11, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (66, 1, 12, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (67, 1, 13, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (68, 1, 136, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (69, 1, 70, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (70, 1, 71, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (71, 1, 72, 'Yes');
INSERT INTO `smart_profilefeatures` (`id`, `profile`, `feature`, `value`) VALUES (72, 1, 84, 'Yes');

CREATE TABLE `smart_appsessions` (
  `id` int(11) not null  auto_increment,
  `sessionid` varchar(40) not null  default '',
  `userid` varchar(40) not null  default '',
  `appname` varchar(40) not null  default '',
  `lastsaved` varchar(20) not null  default '',
  `readonly` char(2) not null  default '',
  `articleid` int(11) not null  default 0,
  `articlename` varchar(255) not null  default '',
  `articleformat` varchar(128) not null  default '',
  `articleminorversion` mediumint(9) not null  default 0,
  `templateid` int(11) not null  default 0,
  `templatename` varchar(255) not null  default '',
  `templateformat` varchar(128) not null  default '',
  `layoutid` int(11) not null  default 0,
  `layoutminorversion` mediumint(9) not null  default 0,
  `articlemajorversion` mediumint(9) not null  default 0,
  `layoutmajorversion` mediumint(9) not null  default 0,
  `dommajorversion` mediumint(9) not null  default '5',
  `domminorversion` mediumint(9) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_datasources` (
  `id` int(11) not null  auto_increment,
  `type` varchar(255) not null  default '',
  `name` varchar(255) not null  default '',
  `bidirectional` char(2) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `na_datasources` on `smart_datasources`(`name`) ;

CREATE TABLE `smart_dspublications` (
  `id` int(11) not null  auto_increment,
  `datasourceid` int(11) not null  default '0',
  `publicationid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `dsid_dspublications` on `smart_dspublications`(`datasourceid`) ;
CREATE  INDEX `pubid_dspublications` on `smart_dspublications`(`publicationid`) ;

CREATE TABLE `smart_dsqueries` (
  `id` int(11) not null  auto_increment,
  `name` varchar(255) not null  default '',
  `query` blob not null  default '',
  `comment` blob not null  default '',
  `interface` blob not null  default '',
  `datasourceid` int(11) not null  default '0',
  `recordid` varchar(255) not null  default '',
  `recordfamily` varchar(255) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `dsid_dsqueries` on `smart_dsqueries`(`datasourceid`) ;

CREATE TABLE `smart_dsqueryfields` (
  `id` int(11) not null  auto_increment,
  `queryid` int(11) not null  default '0',
  `priority` tinyint(4) not null  default '0',
  `name` varchar(255) not null  default '',
  `readonly` tinyint(4) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `queryid_dsqueryfields` on `smart_dsqueryfields`(`queryid`) ;

CREATE TABLE `smart_dssettings` (
  `id` int(11) not null  auto_increment,
  `name` varchar(255) not null  default '',
  `value` blob not null  default '',
  `datasourceid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `dsid_dssettings` on `smart_dssettings`(`datasourceid`) ;

CREATE TABLE `smart_dsqueryplacements` (
  `id` int(11) not null  auto_increment,
  `objectid` int(11) not null  default '0',
  `datasourceid` int(11) not null  default '0',
  `dirty` char(2) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objid_dsqueryplacements` on `smart_dsqueryplacements`(`objectid`) ;
CREATE  INDEX `dsid_dsqueryplacements` on `smart_dsqueryplacements`(`datasourceid`) ;

CREATE TABLE `smart_dsqueryfamilies` (
  `id` int(11) not null  auto_increment,
  `queryplacementid` int(11) not null  default '0',
  `familyfield` varchar(255) not null  default '',
  `familyvalue` blob not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_dsupdates` (
  `id` int(11) not null  auto_increment,
  `recordset` longblob not null  default '',
  `familyvalue` blob not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_dsobjupdates` (
  `id` int(11) not null  auto_increment,
  `updateid` int(11) not null  default '0',
  `objectid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_channels` (
  `id` int(11) not null  auto_increment,
  `name` varchar(255) not null  default '',
  `publicationid` int(11) not null  default '0',
  `type` varchar(32) not null  default 'print',
  `description` varchar(255) not null  default '',
  `code` int(4) not null  default '0',
  `deadlinerelative` int(11) not null  default '0',
  `currentissueid` int(11) not null  default '0',
  `publishsystem` varchar(64) not null  default '',
  `suggestionprovider` varchar(64) not null  default '',
  `publishsystemid` varchar(40) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
INSERT INTO `smart_channels` (`id`, `name`, `publicationid`, `type`, `description`, `code`, `deadlinerelative`, `currentissueid`, `publishsystem`, `suggestionprovider`, `publishsystemid`) VALUES (1, 'Print', 1, 'print', 'Print Channel', 10, 0, 1, '', '', '' );
INSERT INTO `smart_channels` (`id`, `name`, `publicationid`, `type`, `description`, `code`, `deadlinerelative`, `currentissueid`, `publishsystem`, `suggestionprovider`, `publishsystemid`) VALUES (2, 'Web', 1, 'web', 'Web Channel', 20, 0, 0, '', '', '' );

CREATE TABLE `smart_editions` (
  `id` int(11) not null  auto_increment,
  `name` varchar(255) not null  default '',
  `channelid` int(11) not null  default '0',
  `issueid` int(11) not null  default '0',
  `code` int(4) not null  default '0',
  `deadlinerelative` int(11) not null  default '0',
  `description` blob not null ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
INSERT INTO `smart_editions` (`id`, `name`, `channelid`, `issueid`, `code`, `deadlinerelative`, `description`) VALUES (1, 'North', 1, 0, '10', '', '' );
INSERT INTO `smart_editions` (`id`, `name`, `channelid`, `issueid`, `code`, `deadlinerelative`, `description`) VALUES (2, 'South', 1, 0, '20', '', '' );

CREATE TABLE `smart_issues` (
  `id` int(11) not null  auto_increment,
  `name` varchar(255) not null  default '',
  `channelid` int(11) not null  default '0',
  `overrulepub` char(2) not null  default '',
  `code` int(4) not null  default '0',
  `publdate` varchar(30) not null  default '',
  `deadline` varchar(30) not null  default '',
  `pages` int(4) not null  default '0',
  `subject` blob not null ,
  `description` blob not null ,
  `active` char(2) not null  default '',
  `readingorderrev` varchar(2) not null  default '',
  `calculatedeadlines` char(2) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `ch_issues` on `smart_issues`(`channelid`) ;
CREATE  INDEX `na_issues` on `smart_issues`(`name`) ;
INSERT INTO `smart_issues` (`id`, `name`, `channelid`, `overrulepub`, `code`, `publdate`, `deadline`, `pages`, `subject`, `description`, `active`, `readingorderrev`, `calculatedeadlines`) VALUES (1, '1st Issue', 1, '', '10', '', '', 16, '', '', 'on', '', '' );
INSERT INTO `smart_issues` (`id`, `name`, `channelid`, `overrulepub`, `code`, `publdate`, `deadline`, `pages`, `subject`, `description`, `active`, `readingorderrev`, `calculatedeadlines`) VALUES (2, '2nd Issue', 1, '', '20', '', '', 16, '', '', 'on', 'on', '' );
INSERT INTO `smart_issues` (`id`, `name`, `channelid`, `overrulepub`, `code`, `publdate`, `deadline`, `pages`, `subject`, `description`, `active`, `readingorderrev`, `calculatedeadlines`) VALUES (3, 'webissue', 2, '', '10', '', '', 16, '', '', 'on', 'on', '' );

CREATE TABLE `smart_targets` (
  `id` int(11) not null  auto_increment,
  `objectid` int(11) not null  default '0',
  `channelid` int(11) not null  default '0',
  `issueid` int(11) not null  default '0',
  `externalid` varchar(200) not null  default '',
  `objectrelationid` int(11) not null  default '0',
  `publisheddate` varchar(30) not null  default '',
  `publishedmajorversion` mediumint(9) not null  default '0',
  `publishedminorversion` mediumint(9) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `obchisobr_targets` on `smart_targets`(`objectid`, `channelid`, `issueid`, `objectrelationid`) ;
CREATE UNIQUE INDEX `obrobid_targets` on `smart_targets`(`objectrelationid`, `objectid`, `id`) ;
CREATE  INDEX `issueid_targets` on `smart_targets`(`issueid`) ;

CREATE TABLE `smart_publishhistory` (
  `id` int(11) not null  auto_increment,
  `externalid` varchar(200) not null  default '',
  `objectid` int(11) not null  default '0',
  `channelid` int(11) not null  default '0',
  `issueid` int(11) not null  default '0',
  `editionid` int(11) not null  default '0',
  `publisheddate` varchar(30) not null  default '',
  `fields` blob not null  default '',
  `fieldsmajorversion` mediumint(9) not null  default '0',
  `fieldsminorversion` mediumint(9) not null  default '0',
  `actiondate` varchar(30) not null  default '',
  `action` varchar(20) not null  default '',
  `user` varchar(255) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `obchis_publhist` on `smart_publishhistory`(`objectid`, `channelid`, `issueid`) ;
CREATE  INDEX `chis_publhist` on `smart_publishhistory`(`channelid`, `issueid`) ;

CREATE TABLE `smart_pubpublishedissues` (
  `id` int(11) not null  auto_increment,
  `externalid` varchar(200) not null  default '',
  `channelid` int(11) not null  default '0',
  `issueid` int(11) not null  default '0',
  `editionid` int(11) not null  default '0',
  `report` blob not null  default '',
  `dossierorder` blob not null  default '',
  `publishdate` varchar(30) not null  default '',
  `issuemajorversion` mediumint(9) not null  default '0',
  `issueminorversion` mediumint(9) not null  default '0',
  `fields` blob not null  default '',
  `fieldsmajorversion` mediumint(9) not null  default '0',
  `fieldsminorversion` mediumint(9) not null  default '0',
  `actiondate` varchar(30) not null  default '',
  `action` varchar(20) not null  default '',
  `userid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `chised_publhist` on `smart_pubpublishedissues`(`channelid`, `issueid`, `editionid`) ;

CREATE TABLE `smart_publishedobjectshist` (
  `id` int(11) not null  auto_increment,
  `objectid` int(11) not null  default '0',
  `publishid` int(11) not null  default '0',
  `majorversion` mediumint(9) not null  default '0',
  `minorversion` mediumint(9) not null  default '0',
  `externalid` varchar(200) not null  default '',
  `objectname` varchar(255) not null  default '',
  `objecttype` varchar(40) not null  default '',
  `objectformat` varchar(128) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `obpu_publobjhist` on `smart_publishedobjectshist`(`objectid`, `publishid`) ;
CREATE  INDEX `puob_publobjhist` on `smart_publishedobjectshist`(`publishid`, `objectid`) ;

CREATE TABLE `smart_publishedplcmtshist` (
  `id` int(11) not null  auto_increment,
  `objectid` int(11) not null  default '0',
  `publishid` int(11) not null  default '0',
  `majorversion` mediumint(9) not null  default '0',
  `minorversion` mediumint(9) not null  default '0',
  `externalid` varchar(200) not null  default '',
  `placementhash` varchar(64) not null ,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `obpu_publplchist` on `smart_publishedplcmtshist`(`objectid`, `publishid`) ;
CREATE  INDEX `puob_publplchist` on `smart_publishedplcmtshist`(`publishid`, `objectid`) ;

CREATE TABLE `smart_targeteditions` (
  `id` int(11) not null  auto_increment,
  `targetid` int(11) not null  default '0',
  `editionid` int(11) not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `taed_targeteditions` on `smart_targeteditions`(`targetid`, `editionid`) ;
CREATE UNIQUE INDEX `edta_targeteditions` on `smart_targeteditions`(`editionid`, `targetid`) ;

CREATE TABLE `smart_indesignservers` (
  `id` int(11) not null  auto_increment,
  `hostname` varchar(64) not null  default '',
  `portnumber` mediumint(9) not null  default '0',
  `description` varchar(255) not null  default '',
  `active` char(2) not null  default '',
  `servermajorversion` mediumint(9) not null  default '5',
  `serverminorversion` mediumint(9) not null  default '0',
  `prio1` char(2) not null  default 'on',
  `prio2` char(2) not null  default 'on',
  `prio3` char(2) not null  default 'on',
  `prio4` char(2) not null  default 'on',
  `prio5` char(2) not null  default 'on',
  `locktoken` varchar(40) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `hopo_indesignservers` on `smart_indesignservers`(`hostname`, `portnumber`) ;

CREATE TABLE `smart_indesignserverjobs` (
  `jobid` varchar(40) not null  default '',
  `foreground` char(2) not null  default '',
  `objid` int(11) not null  default 0,
  `objectmajorversion` mediumint(9) not null  default '0',
  `objectminorversion` mediumint(9) not null  default '0',
  `jobtype` varchar(32) not null ,
  `jobscript` blob not null ,
  `jobparams` blob not null  default '',
  `locktoken` varchar(40) not null  default '',
  `queuetime` varchar(20) not null  default '',
  `starttime` varchar(30) not null  default '',
  `readytime` varchar(20) not null  default '',
  `errorcode` varchar(32) not null  default '',
  `errormessage` varchar(1024) not null  default '',
  `scriptresult` blob not null  default '',
  `jobstatus` int(11) not null  default 0,
  `jobcondition` int(11) not null  default 0,
  `jobprogress` int(11) not null  default 0,
  `attempts` int(11) not null  default 0,
  `assignedserverid` int(9) not null  default 0,
  `minservermajorversion` mediumint(9) not null  default '0',
  `minserverminorversion` mediumint(9) not null  default '0',
  `maxservermajorversion` mediumint(9) not null  default '0',
  `maxserverminorversion` mediumint(9) not null  default '0',
  `prio` mediumint(1) not null  default '3',
  `ticketseal` varchar(40) not null  default '',
  `ticket` varchar(40) not null  default '',
  `actinguser` varchar(40) not null  default '',
  `initiator` varchar(40) not null  default '',
  `servicename` varchar(32) not null  default '',
  `context` varchar(64) not null  default '',
  PRIMARY KEY (`jobid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `asre_indesignserverjobs` on `smart_indesignserverjobs`(`assignedserverid`, `readytime`) ;
CREATE  INDEX `qt_indesignserverjobs` on `smart_indesignserverjobs`(`queuetime`) ;
CREATE  INDEX `objid_indesignserverjobs` on `smart_indesignserverjobs`(`objid`) ;
CREATE  INDEX `prid_indesignserverjobs` on `smart_indesignserverjobs`(`prio`, `jobid`) ;
CREATE  INDEX `ts_indesignserverjobs` on `smart_indesignserverjobs`(`ticketseal`) ;
CREATE  INDEX `ttjtstrt_indesignserverjobs` on `smart_indesignserverjobs`(`ticket`, `jobtype`, `starttime`, `readytime`) ;
CREATE  INDEX `jp_indesignserverjobs` on `smart_indesignserverjobs`(`jobprogress`) ;
CREATE  INDEX `jspr_indesignserverjobs` on `smart_indesignserverjobs`(`jobstatus`, `prio`, `queuetime`) ;
CREATE  INDEX `lt_indesignserverjobs` on `smart_indesignserverjobs`(`locktoken`) ;

CREATE TABLE `smart_servers` (
  `id` int(11) not null  auto_increment,
  `name` varchar(64) not null  default '',
  `type` varchar(32) not null  default '',
  `url` varchar(1024) not null  default '',
  `description` varchar(255) not null  default '',
  `jobsupport` char(1) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `hopo_servers` on `smart_servers`(`name`) ;

CREATE TABLE `smart_serverjobs` (
  `jobid` varchar(40) not null  default '',
  `attempts` int(11) not null  default 0,
  `queuetime` varchar(30) not null  default '',
  `servicename` varchar(32) not null  default '',
  `context` varchar(32) not null  default '',
  `servertype` varchar(32) not null  default '',
  `jobtype` varchar(32) not null  default '',
  `assignedserverid` int(11) not null  default 0,
  `starttime` varchar(30) not null  default '0000-00-00T00:00:00',
  `readytime` varchar(30) not null  default '0000-00-00T00:00:00',
  `errormessage` varchar(1024) not null  default '',
  `locktoken` varchar(40) not null  default '',
  `ticketseal` varchar(40) not null  default '',
  `actinguser` varchar(40) not null  default '',
  `jobstatus` int(11) not null  default 0,
  `jobcondition` int(11) not null  default 0,
  `jobprogress` int(11) not null  default 0,
  `jobdata` mediumblob not null ,
  `dataentity` varchar(20) not null  default '',
  PRIMARY KEY (`jobid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `qt_serverjobs` on `smart_serverjobs`(`queuetime`) ;
CREATE  INDEX `jobinfo` on `smart_serverjobs`(`locktoken`, `jobstatus`, `jobprogress`) ;
CREATE  INDEX `aslt_serverjobs` on `smart_serverjobs`(`assignedserverid`, `locktoken`) ;
CREATE  INDEX `paged_results` on `smart_serverjobs`(`queuetime`, `servertype`, `jobtype`, `jobstatus`, `actinguser`) ;

CREATE TABLE `smart_serverjobtypesonhold` (
  `guid` varchar(40) not null  default '',
  `jobtype` varchar(32) not null  default '',
  `retrytimestamp` varchar(20) not null  default '',
  PRIMARY KEY (`guid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `jobtype` on `smart_serverjobtypesonhold`(`jobtype`) ;
CREATE  INDEX `retrytime` on `smart_serverjobtypesonhold`(`retrytimestamp`) ;

CREATE TABLE `smart_serverjobconfigs` (
  `id` int(11) not null  auto_increment,
  `jobtype` varchar(32) not null  default '',
  `servertype` varchar(32) not null  default '',
  `attempts` mediumint(9) not null  default 0,
  `active` char(1) not null  default 'N',
  `sysadmin` char(1) not null  default '-',
  `userid` int(11) not null  default 0,
  `userconfigneeded` char(1) not null  default 'Y',
  `recurring` char(1) not null  default 'N',
  `selfdestructive` char(1) not null  default 'N',
  `workingdays` char(1) not null  default 'N',
  `dailystarttime` varchar(30) not null  default '00-00-00T00:00:00',
  `dailystoptime` varchar(30) not null  default '00-00-00T00:00:00',
  `timeinterval` mediumint(9) not null  default 0,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `smart_serverjobsupports` (
  `id` int(11) not null  auto_increment,
  `serverid` int(11) not null  default 0,
  `jobconfigid` int(11) not null  default 0,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `sjs_serverconfigs` on `smart_serverjobsupports`(`serverid`, `jobconfigid`) ;

CREATE TABLE `smart_serverplugins` (
  `id` int(11) not null  auto_increment,
  `uniquename` varchar(64) not null  default '',
  `displayname` varchar(128) not null  default '',
  `version` varchar(64) not null  default '',
  `description` varchar(255) not null  default '',
  `copyright` varchar(128) not null  default '',
  `active` char(2) not null  default '',
  `system` char(2) not null  default '',
  `installed` char(2) not null  default '',
  `modified` varchar(30) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
INSERT INTO `smart_serverplugins` (`id`, `uniquename`, `displayname`, `version`, `description`, `copyright`, `active`, `system`, `installed`, `modified`) VALUES (1, 'PreviewMetaPHP', 'PHP Preview and Meta Data', 'v6.1', 'Using internal PHP libraries (such as GD) to generate previews and read metadata', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', 'on', 'on', 'on', '2008-10-02T09:00:00');
INSERT INTO `smart_serverplugins` (`id`, `uniquename`, `displayname`, `version`, `description`, `copyright`, `active`, `system`, `installed`, `modified`) VALUES (2, 'ImageMagick', 'ImageMagick', 'v6.1', 'Use ImageMagick to support extra formats for preview generation', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', '', 'on', '', '2008-10-02T09:00:00');
INSERT INTO `smart_serverplugins` (`id`, `uniquename`, `displayname`, `version`, `description`, `copyright`, `active`, `system`, `installed`, `modified`) VALUES (3, 'InCopyHTMLConversion', 'InCopy HTML Conversion', 'v6.1', 'Have InCopy and InDesign edit HTML articles by converting the article to text', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', 'on', 'on', 'on', '2008-11-30T09:00:00');

CREATE TABLE `smart_serverconnectors` (
  `id` int(11) not null  auto_increment,
  `pluginid` int(11) not null  default '0',
  `classname` varchar(128) not null  default '',
  `interface` varchar(128) not null  default '',
  `type` varchar(32) not null  default '',
  `prio` mediumint(9) not null  default '0',
  `runmode` varchar(16) not null  default '',
  `classfile` varchar(255) not null  default '',
  `modified` varchar(30) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `seco_pluginid` on `smart_serverconnectors`(`pluginid`) ;
CREATE  INDEX `seco_typeinterface` on `smart_serverconnectors`(`type`, `interface`) ;
INSERT INTO `smart_serverconnectors` (`id`, `pluginid`, `classname`, `interface`, `type`, `prio`, `runmode`, `classfile`, `modified`) VALUES (1, 1, 'PreviewMetaPHP_Preview', 'Preview', '', 500, 'Synchron', '/server/plugins/PreviewMetaPHP/PreviewMetaPHP_Preview.class.php', '2008-10-02T09:00:00');
INSERT INTO `smart_serverconnectors` (`id`, `pluginid`, `classname`, `interface`, `type`, `prio`, `runmode`, `classfile`, `modified`) VALUES (2, 1, 'PreviewMetaPHP_MetaData', 'MetaData', '', 500, 'Synchron', '/server/plugins/PreviewMetaPHP/PreviewMetaPHP_MetaData.class.php', '2008-10-02T09:00:00');
INSERT INTO `smart_serverconnectors` (`id`, `pluginid`, `classname`, `interface`, `type`, `prio`, `runmode`, `classfile`, `modified`) VALUES (3, 3, 'InCopyHTMLConversion_WflGetObjects', 'WflGetObjects', 'WorkflowService', 500, 'After', '/server/plugins/InCopyHTMLConversion/InCopyHTMLConversion_WflGetObjects.class.php', '2008-11-30T09:00:00');

CREATE TABLE `smart_semaphores` (
  `id` int(11) not null  auto_increment,
  `entityid` varchar(40) not null  default '0',
  `lastupdate` int(11) not null  default '0',
  `lifetime` int(11) not null  default '0',
  `user` varchar(40) not null  default '',
  `ip` varchar(30) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `idx_entity` on `smart_semaphores`(`entityid`) ;
CREATE  INDEX `idx_entityuser` on `smart_semaphores`(`entityid`, `user`) ;

CREATE TABLE `smart_outputdevices` (
  `id` int(11) not null  auto_increment,
  `name` varchar(255) not null  default '',
  `code` int(4) not null  default '0',
  `description` blob not null ,
  `landscapewidth` int(11) not null  default '0',
  `landscapeheight` int(11) not null  default '0',
  `portraitwidth` int(11) not null  default '0',
  `portraitheight` int(11) not null  default '0',
  `previewquality` int(11) not null  default '0',
  `landscapelayoutwidth` double not null  default '0',
  `pixeldensity` int(11) not null  default '0',
  `pngcompression` int(11) not null  default '0',
  `textviewpadding` varchar(50) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
INSERT INTO `smart_outputdevices` (`id`, `name`, `code`, `description`, `landscapewidth`, `landscapeheight`, `portraitwidth`, `portraitheight`, `previewquality`, `landscapelayoutwidth`, `pixeldensity`, `pngcompression`, `textviewpadding`) VALUES (1, 'iPad - DM', 0, '', 1024, 748, 768, 1004, 4, 558.5, 132, 9, '');
INSERT INTO `smart_outputdevices` (`id`, `name`, `code`, `description`, `landscapewidth`, `landscapeheight`, `portraitwidth`, `portraitheight`, `previewquality`, `landscapelayoutwidth`, `pixeldensity`, `pngcompression`, `textviewpadding`) VALUES (2, 'iPad', 10, '', 1024, 768, 768, 1024, 4, 1024, 132, 9, '');
INSERT INTO `smart_outputdevices` (`id`, `name`, `code`, `description`, `landscapewidth`, `landscapeheight`, `portraitwidth`, `portraitheight`, `previewquality`, `landscapelayoutwidth`, `pixeldensity`, `pngcompression`, `textviewpadding`) VALUES (3, 'Kindle Fire', 20, '', 1024, 600, 600, 1024, 4, 1024, 169, 9, '');
INSERT INTO `smart_outputdevices` (`id`, `name`, `code`, `description`, `landscapewidth`, `landscapeheight`, `portraitwidth`, `portraitheight`, `previewquality`, `landscapelayoutwidth`, `pixeldensity`, `pngcompression`, `textviewpadding`) VALUES (4, 'Xoom', 30, '', 1280, 800, 800, 1280, 4, 1280, 160, 9, '');

CREATE TABLE `smart_placementtiles` (
  `id` int(11) not null  auto_increment,
  `placementid` int(11) not null  default '0',
  `pagesequence` mediumint(9) not null  default '0',
  `left` double not null  default '0',
  `top` double not null  default '0',
  `width` double not null  default '0',
  `height` double not null  default '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `pi_placementtiles` on `smart_placementtiles`(`placementid`) ;

CREATE TABLE `smart_objectlabels` (
  `id` int(11) not null  auto_increment,
  `objid` int(11) not null  default '0',
  `name` varchar(250) not null  default '',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objlabels_objid` on `smart_objectlabels`(`objid`) ;

CREATE TABLE `smart_objectrelationlabels` (
  `labelid` int(11) not null  default '0',
  `childobjid` int(11) not null  default '0',
  PRIMARY KEY (`labelid`, `childobjid`)
) DEFAULT CHARSET=utf8;
CREATE  INDEX `objrellabels_childobjid` on `smart_objectrelationlabels`(`childobjid`) ;

CREATE TABLE `smart_channeldata` (
  `publication` int(11) not null  default '0',
  `pubchannel` int(11) not null  default '0',
  `issue` int(11) not null  default '0',
  `section` int(11) not null  default '0',
  `name` varchar(200) not null  default '',
  `value` blob not null  default '',
  PRIMARY KEY (`publication`, `pubchannel`, `issue`, `section`, `name`)
) DEFAULT CHARSET=utf8;
UPDATE `smart_config` set `value` = '10.1' where `name` = 'version';
