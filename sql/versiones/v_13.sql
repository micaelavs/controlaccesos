CREATE TABLE IF NOT EXISTS `textmecon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dni` decimal(11,0) DEFAULT NULL,
  `fecha` date,
  `hora` datetime,
  `puerta` varchar(50),
  `informado` tinyint(4) DEFAULT '0',
  `num_procesamiento` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO db_version VALUES('13', now());