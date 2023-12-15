CREATE TABLE `plantilla_horarios` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(80) NOT NULL,
  `horario` VARCHAR(250) NOT NULL,
  `borrado` TINYINT(1) NULL DEFAULT '0',
  PRIMARY KEY (`id`));

CREATE TABLE `empleado_horarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `horarios` varchar(250) NOT NULL,
  `borrado` tinyint(2) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_empleado_UNIQUE` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO db_version VALUES('7', now());