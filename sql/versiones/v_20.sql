ALTER TABLE credenciales
ADD COLUMN acceso_id INT
(10) NOT NULL AFTER ubicacion_id;

ALTER TABLE credenciales
MODIFY codigo VARCHAR
(8) NOT NULL;

ALTER TABLE accesos
MODIFY hora_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE `reloj_tarjetas` (
  `id` int(11) NOT NULL,
  `id_reloj` int(11) NOT NULL,
  `id_tarjeta` int(11) NOT NULL,
  `borrado` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `reloj_tarjetas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `reloj_tarjetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `relojes` ADD `acceso_tarjeta` TINYINT NULL AFTER `acceso_restringido`;



CREATE TABLE `tarjetas` (
  `id` int(11) NOT NULL,
  `access_id` int(11) NOT NULL,
  `borrado` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `tarjetas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tarjetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  INSERT INTO db_version VALUES('20', now());