ALTER TABLE relojes ADD acceso_restringido tinyint(1) DEFAULT 0;

  CREATE TABLE IF NOT EXISTS `accesos_restringidos` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_persona` int(11)  unsigned NOT NULL,
  `id_reloj` int(11) unsigned NOT NULL,
  `fecha_alta` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_ultima_modificacion` timestamp NULL DEFAULT NULL,
  `id_persona_operador` int(11) unsigned NOT NULL,
  `borrado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO db_version VALUES('15', now());