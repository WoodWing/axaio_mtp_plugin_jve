-- phpMyAdmin SQL Dump
-- version 2.9.2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Dec 09, 2009 at 03:41 PM
-- Server version: 5.0.41
-- PHP Version: 5.2.3
-- 
-- Database: `Enterprise7`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `smart_ticketmapping`
-- 

CREATE TABLE IF NOT EXISTS `smart_ticketmapping` (
  `id` mediumint(9) NOT NULL auto_increment,
  `localticket` varchar(40) NOT NULL,
  `remoteticket` varchar(40) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `localticket` (`localticket`,`remoteticket`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
