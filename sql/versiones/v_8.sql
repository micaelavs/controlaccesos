CREATE TABLE `pertenencias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `persona_id` int(10) unsigned NOT NULL,
  `ubicacion_id` int(10) unsigned DEFAULT NULL,
  `solicitante_id` int(10) unsigned DEFAULT NULL,
  `texto` text NOT NULL,
  `borrado` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `persona_index` (`persona_id`),
  KEY `ubicacion_index` (`ubicacion_id`),
  KEY `borrado_index` (`borrado`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

ALTER TABLE `empleado_contrato` 
ADD INDEX `empleado_index` (`id_empleado` ASC),
ADD INDEX `contrato_index` (`id_tipo_contrato` ASC),
ADD INDEX `desde_index` (`fecha_desde` ASC),
ADD INDEX `hasta_index` (`fecha_hasta` ASC),
ADD INDEX `borrado_index` (`borrado` ASC);

ALTER TABLE `empleado_dependencia_principal` 
ADD INDEX `empleado_index` (`id_empleado` ASC),
ADD INDEX `dependencia_index` (`id_dependencia_principal` ASC),
ADD INDEX `desde_index` (`fecha_desde` ASC),
ADD INDEX `hasta_index` (`fecha_hasta` ASC),
ADD INDEX `borrado_index` (`borrado` ASC);

INSERT INTO db_version VALUES('8', now());