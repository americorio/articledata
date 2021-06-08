<?php

$app="vanilla";



$s1="CREATE TABLE IF NOT EXISTS `" . $app . "_version` (
  `id` int(3) NOT NULL DEFAULT '0',
  `version` varchar(12) DEFAULT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$s2="CREATE TABLE IF NOT EXISTS `" . $app . "` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL,
  `file` varchar(1000) NOT NULL,
  `projectfile` varchar(1000) NOT NULL,
  `beginline` varchar(11) NOT NULL,
  `endline` varchar(11) NOT NULL,
  `rule` varchar(500) NOT NULL,
  `ruleset` varchar(1000) NOT NULL,
  `externalInfoUrl` varchar(500) NOT NULL,
  `package` varchar(100) NOT NULL,
  `class` varchar(100) NOT NULL,
  `function` varchar(100) NOT NULL,
  `priority` int(11) NOT NULL,
  `violation` varchar(1000) NOT NULL,
  `violation_nonumbers` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file` (`file`),
  KEY `function` (`function`),
  KEY `package` (`package`),
  KEY `rule` (`rule`),
  KEY `violation_nonumbers` (`violation_nonumbers`),
  KEY `class` (`class`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1";

$s3="CREATE TABLE IF NOT EXISTS `" . $app . "_ss_evol` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idversion_init` int(11) NOT NULL,
  `idversion_end` int(11) NOT NULL COMMENT 'ja nao aparece nesta versao',
  `file` varchar(1000) NOT NULL,
  `begin_line` int(11) NOT NULL,
  `end_line` int(11) NOT NULL,
  `rule` varchar(1000) NOT NULL,
  `ruleset` varchar(1000) NOT NULL,
  `package` varchar(100) NOT NULL,
  `class` varchar(100) NOT NULL,
  `function` varchar(100) NOT NULL,
  `priority` varchar(20) NOT NULL,
  `violation` varchar(1000) NOT NULL,
  `violation_nonumbers` varchar(1000) NOT NULL,
  `version_init` varchar(12) NOT NULL,
  `version_end` varchar(12) NOT NULL,
  `date_init` date NOT NULL,
  `date_end` date NOT NULL,
  `diff` int(11) NOT NULL,
  `censored` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file` (`file`),
  KEY `function` (`function`),
  KEY `package` (`package`),
  KEY `rule` (`rule`),
  KEY `violation_nonumbers` (`violation_nonumbers`),
  KEY `class` (`class`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1";

$db= new mysqli('localhost', 'root', '', 'serversmells');

create($s1);
create($s2);
create($s3);

function create($sql){
	global $db;
	
	if ($db->query($sql) === TRUE) {
		echo "Table created successfully";
	} else {
		echo "Error creating table: " . $db->error;
	}
	echo "\n";
}

$db->close();
echo "create tables for $app done";