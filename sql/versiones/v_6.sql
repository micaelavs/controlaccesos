ALTER TABLE `novedades`
CHANGE COLUMN `fecha` `fecha_desde`  date NOT NULL AFTER `id_tipo_novedad`,
ADD COLUMN `fecha_hasta`  date NOT NULL AFTER `fecha_desde`;

INSERT INTO db_version VALUES('6', now());