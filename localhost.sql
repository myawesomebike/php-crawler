-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 29, 2014 at 09:19 PM
-- Server version: 5.5.8
-- PHP Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `crawls`
--
CREATE DATABASE `crawls` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `crawls`;

-- --------------------------------------------------------

--
-- Table structure for table `crawl_index`
--

CREATE TABLE IF NOT EXISTS `crawl_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `entry_url` text COLLATE utf8_bin NOT NULL,
  `start` int(11) NOT NULL,
  `end` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=16 ;

-- --------------------------------------------------------

--
-- Table structure for table `domains`
--

CREATE TABLE IF NOT EXISTS `domains` (
  `domain_id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_name` text COLLATE utf8_bin NOT NULL,
  `creation_timestamp` int(11) NOT NULL,
  `source` int(11) NOT NULL,
  `conductor_name` text COLLATE utf8_bin NOT NULL,
  `conductor_account_id` int(11) NOT NULL,
  `conductor_domain_id` int(11) NOT NULL,
  PRIMARY KEY (`domain_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=267 ;

-- --------------------------------------------------------

--
-- Table structure for table `error_log`
--

CREATE TABLE IF NOT EXISTS `error_log` (
  `crawl_id` int(11) NOT NULL,
  `crawl_url` int(11) NOT NULL,
  `error_message` text COLLATE utf8_bin NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `ga_accounts`
--

CREATE TABLE IF NOT EXISTS `ga_accounts` (
  `id` int(11) NOT NULL,
  `account` text COLLATE utf8_bin NOT NULL,
  `pw` text COLLATE utf8_bin NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `keyword_density`
--

CREATE TABLE IF NOT EXISTS `keyword_density` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` text COLLATE utf8_bin NOT NULL,
  `count` int(11) NOT NULL,
  `crawl_id` int(11) NOT NULL,
  `connected_pages` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `keyword_index` (`keyword`(40))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=39755 ;

-- --------------------------------------------------------

--
-- Table structure for table `keyword_reports`
--

CREATE TABLE IF NOT EXISTS `keyword_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(11) NOT NULL DEFAULT '0',
  `domain_id` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `conductor_date` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1253 ;

-- --------------------------------------------------------

--
-- Table structure for table `keywords`
--

CREATE TABLE IF NOT EXISTS `keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `keyword_report_id` int(11) NOT NULL,
  `url` text COLLATE utf8_bin NOT NULL,
  `keyword` text COLLATE utf8_bin NOT NULL,
  `rank` int(11) NOT NULL,
  `volume` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1826 ;

-- --------------------------------------------------------

--
-- Table structure for table `raw_html`
--

CREATE TABLE IF NOT EXISTS `raw_html` (
  `url_id` int(11) NOT NULL AUTO_INCREMENT,
  `raw_html` blob NOT NULL,
  PRIMARY KEY (`url_id`),
  KEY `url_id` (`url_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=10750 ;

-- --------------------------------------------------------

--
-- Table structure for table `related_searches`
--

CREATE TABLE IF NOT EXISTS `related_searches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `term` text COLLATE utf8_bin NOT NULL,
  `depth` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=178 ;

-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

CREATE TABLE IF NOT EXISTS `urls` (
  `url_id` int(11) NOT NULL AUTO_INCREMENT,
  `crawl_id` int(11) NOT NULL,
  `crawl_url` text COLLATE utf8_bin NOT NULL,
  `crawl_timestamp` int(11) NOT NULL,
  `response_time` float NOT NULL,
  `crawl_status` int(11) NOT NULL,
  `keyword_status` int(11) NOT NULL DEFAULT '0',
  `redirect_depth` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `http_status` text COLLATE utf8_bin NOT NULL,
  `http_full_header` text COLLATE utf8_bin NOT NULL,
  `hash` text COLLATE utf8_bin NOT NULL,
  `links_out` blob NOT NULL,
  `links_in` blob NOT NULL,
  PRIMARY KEY (`url_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=10750 ;
