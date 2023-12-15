CREATE TABLE `visitas` (
  `visita_id` int(11) NOT NULL AUTO_INCREMENT,
  `ubicacion_id` int(11) NOT NULL,
  `autorizante_id` int(11) NOT NULL,
  `aclaracion_autorizacion` varchar(45) default '',
  `fecha_desde` date default NULL,
  `fecha_hasta` date default NULL,
  `persona_id` int(11) NOT NULL,
  `borrado` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`visita_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `accesos_visitas_enroladas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `visita_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO db_version VALUES('11', now());