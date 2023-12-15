INSERT INTO `tipo_novedades`(`nombre`) VALUES('Franco Compensatorio');

ALTER TABLE 'solicitudes' ADD COLUMN 'requiere_antiguedad' tinyint(1) NULL AFTER 'requiere_firma_certificada';
ALTER TABLE 'solicitudes' ADD COLUMN 'requiere_remuneracion' tinyint(1) NULL AFTER 'requiere_antiguedad';
ALTER TABLE 'solicitudes' ADD COLUMN 'requiere_horario_laboral' tinyint(1) NULL AFTER 'requiere_remuneracion';
ALTER TABLE 'solicitudes' ADD COLUMN 'requiere_domicilio_laboral' tinyint(1) NULL AFTER 'requiere_horario_laboral';


CREATE TABLE `relojes_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_reloj` int(10) unsigned NOT NULL,
  `cod_error` text,
  `mensaje` text,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `alerta_relojes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `borrado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `relojes_log`
CHANGE COLUMN `id_reloj` `nodo` int(10) unsigned NOT NULL ;

INSERT INTO db_version VALUES('10', now());