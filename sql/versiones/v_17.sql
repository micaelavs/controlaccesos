CREATE TABLE IF NOT EXISTS `informes_configuracion` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` int(10) unsigned NOT NULL,
  `empleado_logueado_id` int(10) unsigned DEFAULT NULL,
  `fecha_ultimo_envio` datetime DEFAULT NULL,
  `dependencias` varchar(255) NOT NULL,
  `contratos` varchar(255) NOT NULL,
  `borrado` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `empleado_id` (`empleado_id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

ALTER TABLE  `informes_configuracion` ADD INDEX (  `empleado_id` ) ;
ALTER TABLE  `informes_configuracion` ADD INDEX (  `id` ) ;


INSERT INTO db_version VALUES('17', now());