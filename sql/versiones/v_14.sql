CREATE TABLE `relojes_sincronizar_marcaciones` (
  `id` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `nodo` int(11) NOT NULL,
  `fecha_marcacion` varchar(50) NOT NULL,
  `id_marcacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `relojes_sincronizar_marcaciones`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `relojes_sincronizar_marcaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  CREATE TABLE `relojes_sincronizar_lotes` (
  `id` int(11) NOT NULL,
  `nodo` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `estado` varchar(30) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `relojes_sincronizar_lotes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `relojes_sincronizar_lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  CREATE TABLE IF NOT EXISTS `codigos_errores` (
  `codigo` varchar(8) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `tipo` ENUM('c#','php'),
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `codigos_errores` (codigo, descripcion, tipo) VALUES ("1","Conexion exitosa", "c#");
INSERT INTO `codigos_errores` (codigo, descripcion, tipo) VALUES ("1013","El estado de la conexion es incorrecto", "c#");
INSERT INTO `codigos_errores` (codigo, descripcion, tipo) VALUES ("1014","El tiempo para establecer la conexion expiro", "c#");
INSERT INTO `codigos_errores` (codigo, descripcion, tipo) VALUES ("P001","Fichada de persona que no es empleado ni visita enrolada durante la sincronizacion", "php");
INSERT INTO `codigos_errores` (codigo, descripcion, tipo) VALUES ("P002","Fichada de persona que no es empleado ni visita enrolada en el acceso", "php");

INSERT INTO db_version VALUES('14', now());